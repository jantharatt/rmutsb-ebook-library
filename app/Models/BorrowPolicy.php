<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class BorrowPolicy extends Model
{
    use LogsActivity;

    protected string $auditLabelAttribute = 'role';

    protected $fillable = [
        'role',
        'max_books',
        'borrow_days',
        'active',
    ];
}
