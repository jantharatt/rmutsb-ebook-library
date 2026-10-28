<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'parent_id',
    ];

    // Ebook ในหมวดนี้
    public function ebooks(): HasMany
    {
        return $this->hasMany(Ebook::class);
    }

    // หมวดแม่
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // หมวดย่อย
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->orderBy('code');
    }
}