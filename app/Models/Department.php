<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // <-- 1. TAMBAHKAN INI
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'max_clock_in_time',
        'max_clock_out_time',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}