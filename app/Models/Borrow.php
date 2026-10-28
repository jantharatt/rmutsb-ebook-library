<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrow extends Model
{
    protected $fillable = [
        'user_id',
        'ebook_id',
        'borrow_date',
        'due_date',
        'return_date',
        'status',
        'renew_count',
    ];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'renew_count' => 'integer',
        ];
    }

    /** ยังอยู่ในช่วงยืม (สถานะ borrowed และยังไม่เลยวันครบกำหนด) */
    public function isActive(): bool
    {
        return $this->status === 'borrowed'
            && $this->due_date
            && now()->lte($this->due_date->copy()->endOfDay());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ebook(): BelongsTo
    {
        return $this->belongsTo(Ebook::class);
    }
}
