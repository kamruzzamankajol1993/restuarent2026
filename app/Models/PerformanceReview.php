<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'overall_rating' => 'decimal:2',
        'rating_details' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewerEmployee()
    {
        return $this->belongsTo(Employee::class, 'reviewer_employee_id');
    }

    public function reviewerUser()
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
