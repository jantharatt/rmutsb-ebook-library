<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
</head>
<body>

<h2>แจ้งเตือนกำหนดคืน Ebook</h2>

<p>เรียน {{ $borrow->user->name }}</p>

<p>
Ebook ที่คุณยืมใกล้ครบกำหนดคืน
</p>

<table>

<tr>
<td><b>ชื่อหนังสือ</b></td>
<td>{{ $borrow->ebook->title }}</td>
</tr>

<tr>
<td><b>ผู้แต่ง</b></td>
<td>{{ $borrow->ebook->author }}</td>
</tr>

<tr>
<td><b>กำหนดคืน</b></td>
<td>{{ $borrow->due_date->format('d/m/Y') }}</td>
</tr>

</table>

<br>

<p style="color:#d97706">

หากต้องการต่ออายุ Ebook
กรุณาติดต่อบรรณารักษ์ห้องสมุด

</p>

<p>

ขอบคุณที่ใช้บริการ
ระบบ Ebook Library

</p>

</body>
</html>