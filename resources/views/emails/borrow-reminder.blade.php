@php
    use App\Support\ThaiDate;

    $daysLeft = (int) today()->diffInDays($borrow->due_date, false);
    $whenText = match (true) {
        $daysLeft <= 0 => 'วันนี้',
        $daysLeft === 1 => 'พรุ่งนี้',
        default => "ในอีก {$daysLeft} วัน",
    };
@endphp
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="margin:0;padding:24px;background:#f5f6f8;font-family:Tahoma,'Sarabun',sans-serif;color:#1f2937;">

<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;border:1px solid #e5e7eb;">

    <h2 style="margin:0 0 16px;font-size:20px;color:#1e3a8a;">แจ้งเตือนกำหนดคืน Ebook</h2>

    <p style="margin:0 0 12px;">เรียน {{ $borrow->user->name }}</p>

    <p style="margin:0 0 16px;">
        Ebook ที่คุณยืมจะครบกำหนดคืน<strong>{{ $whenText }}</strong>
        ({{ ThaiDate::long($borrow->due_date) }})
    </p>

    <table style="width:100%;border-collapse:collapse;margin-bottom:20px;font-size:14px;">
        <tr>
            <td style="padding:8px 0;color:#6b7280;width:110px;">ชื่อหนังสือ</td>
            <td style="padding:8px 0;font-weight:bold;">{{ $borrow->ebook->title }}</td>
        </tr>
        @if ($borrow->ebook->author)
            <tr>
                <td style="padding:8px 0;color:#6b7280;">ผู้แต่ง</td>
                <td style="padding:8px 0;">{{ $borrow->ebook->author }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:8px 0;color:#6b7280;">วันที่ยืม</td>
            <td style="padding:8px 0;">{{ ThaiDate::long($borrow->borrow_date) }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0;color:#6b7280;">กำหนดคืน</td>
            <td style="padding:8px 0;font-weight:bold;color:#b45309;">{{ ThaiDate::long($borrow->due_date) }}</td>
        </tr>
    </table>

    <p style="margin:0 0 20px;">
        <a href="{{ route('ebooks.reader', $borrow->ebook) }}"
           style="display:inline-block;background:#1e3a8a;color:#ffffff;text-decoration:none;padding:10px 20px;border-radius:8px;">
            อ่านต่อ
        </a>
    </p>

    <p style="margin:0 0 12px;padding:12px;background:#fffbeb;border-radius:8px;color:#92400e;font-size:14px;">
        เมื่อพ้นวันครบกำหนด ระบบจะคืน Ebook ให้อัตโนมัติ
        หากต้องการต่ออายุ กรุณาติดต่อบรรณารักษ์ห้องสมุดก่อนวันครบกำหนด
        หรือยืมใหม่ได้หลังจากระบบคืนหนังสือแล้ว
    </p>

    <p style="margin:16px 0 0;font-size:13px;color:#6b7280;">
        ขอบคุณที่ใช้บริการ<br>
        ระบบ Ebook Library
    </p>

</div>

</body>
</html>
