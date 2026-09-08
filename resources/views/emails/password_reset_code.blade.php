<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>إعادة تعيين كلمة المرور</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; text-align: right;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
        <h2 style="color: #0f766e;">مرحباً، {{ $userName }}</h2>
        <p>لقد طلبت إعادة تعيين كلمة المرور لحسابك في منصة لقاحي.</p>
        <p>رمز التحقق الخاص بك هو:</p>
        <div style="text-align: center; margin: 20px 0;">
            <span style="font-size: 24px; font-weight: bold; background: #f1f5f9; padding: 10px 20px; border-radius: 8px; color: #0f766e; letter-spacing: 2px;">{{ $code }}</span>
        </div>
        <p>هذا الرمز صالح لمدة 15 دقيقة.</p>
        <p>إذا لم تقم بطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني.</p>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
        <p style="font-size: 12px; color: #64748b; text-align: center;">مع تحيات فريق منصة لقاحي</p>
    </div>
</body>
</html>
