<?php

namespace App\Support;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderVisibility
{
    /**
     * Safely read the global random-half visibility setting.
     * If the migration has not been run yet, the existing full-list behaviour remains active.
     */
    public static function isRandomHalfEnabled(): bool
    {
        if (!Schema::hasTable('pos_settings')
            || !Schema::hasColumn('pos_settings', 'order_list_random_half_enabled')) {
            return false;
        }

        return (bool) (DB::table('pos_settings')->value('order_list_random_half_enabled') ?? false);
    }

    /**
     * Return the visible order IDs for the supplied base query.
     * The result contains half of the complete matching set from historical dates,
     * plus every matching order created today.
     */
    public static function visibleIds($query, array $seedContext = []): array
    {
        $matchingIds = (clone $query)
            ->reorder()
            ->pluck('orders.id');

        if ($matchingIds->isEmpty()) {
            return [];
        }

        $todayIds = (clone $query)
            ->reorder()
            ->whereDate('orders.created_at', Carbon::today())
            ->pluck('orders.id');

        $historicalIds = $matchingIds->diff($todayIds)->values();
        $randomHistoricalCount = min(
            $historicalIds->count(),
            (int) ceil($matchingIds->count() / 2)
        );

        $normalizedContext = self::normalizeSeedContext($seedContext);
        $seed = Carbon::today()->format('Y-m-d') . '|'
            . hash('sha256', json_encode($normalizedContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $randomHistoricalIds = $historicalIds
            ->sortBy(function ($id) use ($seed) {
                return hash('sha256', $seed . '|' . $id);
            })
            ->take($randomHistoricalCount);

        return $todayIds
            ->merge($randomHistoricalIds)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Apply the visibility rule to an Order query only when the option is enabled.
     */
    public static function apply($query, array $seedContext = [])
    {
        if (!self::isRandomHalfEnabled()) {
            return $query;
        }

        return $query->whereIn('orders.id', self::visibleIds($query, $seedContext));
    }

    /**
     * Calculate the shared global visible IDs once for Dashboard aggregates.
     * Null means the option is Off and no visibility restriction should be applied.
     */
    public static function globalVisibleIds(): ?array
    {
        if (!self::isRandomHalfEnabled()) {
            return null;
        }

        return self::visibleIds(Order::query());
    }

    /**
     * Constrain any query that contains the orders table to the shared visible IDs.
     */
    public static function constrain($query, ?array $visibleIds)
    {
        if ($visibleIds === null) {
            return $query;
        }

        return $query->whereIn('orders.id', $visibleIds);
    }

    /**
     * Empty URL filter values are removed so the unfiltered Order List and Dashboard
     * use exactly the same deterministic sample for the current day.
     */
    private static function normalizeSeedContext(array $context): array
    {
        $normalized = [];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $value = self::normalizeSeedContext($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $normalized[$key] = $value;
        }

        ksort($normalized);

        return $normalized;
    }
}
