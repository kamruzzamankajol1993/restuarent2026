<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    // ৬ ডিজিট ইউনিক অর্ডার আইডি অটো-জেনারেট করার লজিক
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            do {
                $orderNumber = mt_rand(100000, 999999);
            } while (self::where('order_number', $orderNumber)->exists());

            $order->order_number = $orderNumber;
        });
    }

    // রিলেশনশিপস
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
