<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin System',
            'email' => 'admin@rmutsb.ac.th',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Student Demo',
            'email' => 'student@rmutsb.ac.th',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);

        User::create([
            'name' => 'Teacher Demo',
            'email' => 'teacher@rmutsb.ac.th',
            'password' => Hash::make('password'),
            'role' => 'teacher',
        ]);
    }
}
