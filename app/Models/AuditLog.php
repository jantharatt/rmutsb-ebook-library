<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * บันทึกการกระทำของผู้ดูแลระบบ (อ่านอย่างเดียว ไม่มีหน้าแก้ไข/ลบ)
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = [
        'created' => 'เพิ่ม',
        'updated' => 'แก้ไข',
        'deleted' => 'ลบ',
        'login' => 'เข้าสู่ระบบ',
        'logout' => 'ออกจากระบบ',
        'archived' => 'เก็บถาวร Log',
    ];

    public const ACTION_BADGES = [
        'created' => 'badge-green',
        'updated' => 'badge-blue',
        'deleted' => 'badge-red',
        'archived' => 'badge-amber',
    ];

    public const SUBJECTS = [
        'Ebook' => 'Ebook',
        'Category' => 'หมวดหมู่',
        'BorrowPolicy' => 'นโยบายการยืม',
        'User' => 'ผู้ใช้งาน',
        'Borrow' => 'การยืม',
    ];

    /** ชื่อฟิลด์ภาษาไทยสำหรับหน้ารายละเอียด (ไม่มีในรายการ → แสดงชื่อฟิลด์เดิม) */
    public const FIELDS = [
        'title' => 'ชื่อเรื่อง', 'subtitle' => 'ชื่อเรื่องรอง', 'author' => 'ผู้แต่ง', 'isbn' => 'ISBN',
        'publisher' => 'สำนักพิมพ์', 'publish_year' => 'ปีที่พิมพ์', 'category_id' => 'หมวดหมู่ (ID)',
        'description' => 'รายละเอียด', 'keywords' => 'คำสำคัญ', 'cover' => 'รูปปก', 'file_path' => 'ไฟล์ PDF',
        'total_pages' => 'จำนวนหน้า', 'status' => 'สถานะ', 'call_number' => 'เลขเรียก', 'edition' => 'ครั้งที่พิมพ์',
        'price' => 'ราคา', 'source' => 'แหล่งที่มา', 'code' => 'รหัส', 'name' => 'ชื่อ', 'parent_id' => 'หมวดแม่ (ID)',
        'role' => 'สิทธิ์/ประเภทผู้ใช้', 'max_books' => 'ยืมได้สูงสุด (เล่ม)', 'borrow_days' => 'จำนวนวันยืม',
        'active' => 'เปิดใช้งาน', 'email' => 'อีเมล', 'user_code' => 'รหัสผู้ใช้', 'faculty' => 'คณะ',
        'department' => 'หน่วยงาน', 'user_id' => 'ผู้ใช้ (ID)', 'ebook_id' => 'Ebook (ID)',
        'borrow_date' => 'วันที่ยืม', 'due_date' => 'วันครบกำหนด', 'return_date' => 'วันที่คืน',
        'renew_count' => 'จำนวนครั้งที่ต่ออายุ', 'id' => 'ID',
    ];

    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id', 'subject_label',
        'old_values', 'new_values', 'description', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionNameAttribute(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }

    public function getActionBadgeAttribute(): string
    {
        return self::ACTION_BADGES[$this->action] ?? 'badge-ink';
    }

    public function getSubjectNameAttribute(): ?string
    {
        return $this->subject_type ? (self::SUBJECTS[$this->subject_type] ?? $this->subject_type) : null;
    }

    public static function fieldName(string $field): string
    {
        return self::FIELDS[$field] ?? $field;
    }

    /** ฟิลด์ทั้งหมดที่ปรากฏในค่าเดิมหรือค่าใหม่ */
    public function changedFields(): array
    {
        return array_values(array_unique(array_merge(
            array_keys($this->old_values ?? []),
            array_keys($this->new_values ?? []),
        )));
    }

    /** แปลงค่าเป็นข้อความสำหรับแสดงผล */
    public static function display($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
