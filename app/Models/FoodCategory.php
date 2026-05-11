<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodCategory extends Model
{
    use HasFactory;

    protected $guarded = [];

    // প্যারেন্ট ক্যাটাগরি বের করার জন্য রিলেশন
    public function parent()
    {
        return $this->belongsTo(FoodCategory::class, 'parent_category_id');
    }

    // এই ক্যাটাগরির আন্ডারে থাকা সব সাব-ক্যাটাগরি বের করার জন্য রিলেশন
    public function subcategories()
    {
        return $this->hasMany(FoodCategory::class, 'parent_category_id');
    }
}
