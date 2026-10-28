<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f1a35">

    <title>ห้องสมุดอิเล็กทรอนิกส์ · มทร.สุวรรณภูมิ</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_rus.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="m-0 min-h-screen bg-paper font-sans text-ink-900 antialiased">
    {{ $slot }}
</body>

</html>
