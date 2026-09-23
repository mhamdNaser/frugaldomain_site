@php($ar = $lang === 'ar')
<!doctype html>
<html lang="{{ $ar ? 'ar' : 'en' }}" dir="{{ $ar ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $ar ? 'تم تغيير كلمة المرور' : 'Password changed' }}</title>
</head>
<body style="margin:0; padding:24px; background:#f1f5f9; font-family: Tahoma, Arial, sans-serif; color:#0f172a;">
    <div style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:16px; padding:32px; text-align:{{ $ar ? 'right' : 'left' }};">
        <p style="margin:0 0 16px; font-size:16px;">
            {{ $ar ? 'مرحباً ' . $name . '،' : 'Hello ' . $name . ',' }}
        </p>
        <p style="margin:0 0 16px; font-size:15px; line-height:1.7;">
            {{ $ar
                ? 'تم تغيير كلمة المرور لحسابك على Fruga للتو، وسُجّل خروجك من كل الأجهزة.'
                : 'The password of your Fruga account was just changed, and you were signed out on every device.' }}
        </p>
        @if ($ip)
            <p style="margin:0 0 16px; font-size:13px; color:#64748b;" dir="ltr">IP: {{ $ip }}</p>
        @endif
        <p style="margin:0; font-size:14px; line-height:1.7; color:#b91c1c;">
            {{ $ar
                ? 'إن لم تكن أنت من فعل ذلك، فأعد تعيين كلمة المرور فوراً من صفحة «نسيت كلمة المرور» وتواصل معنا.'
                : 'If this was not you, reset your password straight away from the "Forgot password" page and contact us.' }}
        </p>
    </div>
</body>
</html>
