<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowPolicy extends Model
{
    protected $fillable = [
        'role',
        'max_books',
        'borrow_days',
        'active',
    ];
}
