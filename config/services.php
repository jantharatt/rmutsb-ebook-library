<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],


    /*
    | เครื่องมือแปลง PDF (ไม่บังคับ) ใช้สร้างปกจากหน้าแรกและไฟล์ตัวอย่างฝั่งเซิร์ฟเวอร์
    | ถ้าไม่ได้ติดตั้ง ระบบจะให้เบราว์เซอร์ของผู้ดูแลสร้างให้อัตโนมัติ
    | ตัวอย่าง Windows/XAMPP: PDFTOPPM_PATH="C:\poppler\Library\bin\pdftoppm.exe"
    */
    'pdf_tools' => [
        'pdftoppm' => env('PDFTOPPM_PATH'),
        'ghostscript' => env('GHOSTSCRIPT_PATH'),
        'qpdf' => env('QPDF_PATH'),
    ],

    /*
    | การเข้าสู่ระบบด้วยอีเมลมหาวิทยาลัย
    | LOGIN_EMAIL_ONLY=true  → เข้าด้วยอีเมลอย่างเดียว (ไม่ต้องใช้รหัสผ่าน) ใช้ทดสอบบน XAMPP
    |                          ระบบจะเปิดให้เฉพาะ APP_ENV=local เท่านั้น
    | ADMIN_EMAILS           → อีเมลที่ให้สิทธิ์แอดมินอัตโนมัติ คั่นด้วยจุลภาค
    */
    'login' => [
        'email_only' => (bool) env('LOGIN_EMAIL_ONLY', false),
        'domain' => env('LOGIN_EMAIL_DOMAIN', 'rmutsb.ac.th'),
        'admin_emails' => array_values(array_filter(array_map(
            fn ($e) => strtolower(trim($e)),
            explode(',', (string) env('ADMIN_EMAILS', ''))
        ))),
    ],

];
