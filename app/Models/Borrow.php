<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrow extends Model
{
    use LogsActivity;

    /** ชื่อรายการใน audit log: ชื่อหนังสือ — ชื่อผู้ยืม */
    public function auditLabel(): ?string
    {
        return ($this->ebook?->title ?? 'Ebook #'.$this->ebook_id).' — '.($this->user?->name ?? 'ผู้ใช้ #'.$this->user_id);
    }

    protected $fillable = [
        'user_id',
        'ebook_id',
        'borrow_date',
        'due_date',
        'return_date',
        'status',
        'renew_count',
        'reminder_sent_at',
        'auto_returned',
    ];

    /** ข้อมูลที่ระบบตั้งเอง ไม่ใช่การแก้ไขของแอดมิน */
    protected array $auditExclude = ['reminder_sent_at'];

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'renew_count' => 'integer',
            'reminder_sent_at' => 'datetime',
            'auto_returned' => 'boolean',
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
