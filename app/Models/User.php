<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_code',
        'name',
        'email',
        'google_id',
        'password',
        'role',
        'faculty',
        'department',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
        protected $hidden = [
            'password',
            'remember_token',
        ];

        /**
         * Get the attributes that should be cast.
         *
         * @return array<string, string>
         */
        protected function casts(): array
        {
            return [
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
            ];
        }

        public function borrows(): HasMany
        {
            return $this->hasMany(Borrow::class);
        }

        public function getRoleNameAttribute()
        {
            return match($this->role) {
                'admin' => 'แอดมิน',
                'teacher' => 'อาจารย์',
                'staff' => 'เจ้าหน้าที่',
                'student' => 'นักศึกษา',
                'alumni' => 'นักศึกษาเก่า',
                default => 'ผู้ใช้งาน'
            };
        }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return (int) $this->status === 1;
    }
}
