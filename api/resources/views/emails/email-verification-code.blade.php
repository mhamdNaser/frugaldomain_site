@php($ar = $lang === 'ar')
<!doctype html>
<html lang="{{ $ar ? 'ar' : 'en' }}" dir="{{ $ar ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $ar ? 'رمز تأكيد البريد الإلكتروني' : 'Email confirmation code' }}</title>
</head>
<body style="margin:0; padding:24px; background:#f1f5f9; font-family: Tahoma, Arial, sans-serif; color:#0f172a;">
    <div style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:16px; padding:32px; text-align:{{ $ar ? 'right' : 'left' }};">
        <p style="margin:0 0 16px; font-size:16px;">
            {{ $ar ? 'مرحباً ' . $name . '،' : 'Hello ' . $name . ',' }}
        </p>
        <p style="margin:0 0 24px; font-size:15px; line-height:1.7;">
            {{ $ar
                ? 'أهلاً بك في Fruga. أدخل هذا الرمز لتأكيد بريدك الإلكتروني وفتح الدروس:'
                : 'Welcome to Fruga. Enter this code to confirm your email address and unlock the lessons:' }}
        </p>
        <p dir="ltr" style="margin:0 0 24px; text-align:center; font-size:34px; font-weight:bold; letter-spacing:10px; font-family: 'Courier New', monospace; background:#f1f5f9; border-radius:12px; padding:16px;">
            {{ $code }}
        </p>
        <p style="margin:0 0 12px; font-size:14px; line-height:1.7; color:#475569;">
            {{ $ar
                ? 'الرمز صالح لمدة ' . $minutes . ' دقيقة ولاستخدام واحد. لن يطلبه منك فريق Fruga أبداً، فلا تشاركه مع أحد.'
                : 'The code works once and expires in ' . $minutes . ' minutes. Fruga will never ask you for it - do not share it with anyone.' }}
        </p>
        <p style="margin:0; font-size:14px; line-height:1.7; color:#475569;">
            {{ $ar
                ? 'إن لم تنشئ حساباً على Fruga فتجاهل هذه الرسالة.'
                : 'If you did not create a Fruga account, ignore this email.' }}
        </p>
    </div>
</body>
</html>
