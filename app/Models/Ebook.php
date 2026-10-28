<?php

namespace App\Models;

use App\Services\EbookFileService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ebook extends Model
{
protected $fillable = [
    'title',
    'author',
    'isbn',
    'publisher',
    'publish_year',
    'category_id',
    'description',
    'keywords',

    'cover',
    'file_path',
    'preview_file',

    'total_pages',
    'status',

    'call_number',
    'call_number_copy',
    'edition',

    'main_category_code',

    'imprint',
    'physical_description',
    'series_name',
    'subject_heading',

    'added_personal_name',
    'added_corporate_name',

    'budget_year',
    'registration_number',

    'campus',
    'faculty',
    'cataloguer',
    'source',

    'view_count',
    'borrow_count',
];

    /** ไม่ส่ง path ไฟล์ออกไปเมื่อแปลงเป็น JSON */
    protected $hidden = [
        'file_path',
        'preview_file',
    ];

    /**
     * URL ปกหนังสือ: รูปปกที่อัปโหลด → ปกอัตโนมัติจากหน้าแรก PDF → null (ใช้ปกจำลอง)
     */
    public function getCoverUrlAttribute(): ?string
    {
        return app(EbookFileService::class)->coverUrl($this);
    }

    /** หนังสือที่เปิดให้บริการ (status = 1) */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where($query->getModel()->qualifyColumn('status'), 1);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

        public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }
}

