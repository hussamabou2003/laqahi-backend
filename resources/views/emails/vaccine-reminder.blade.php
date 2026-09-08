<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Tahoma, Arial, sans-serif; background-color: #f4f7f6; padding: 24px; margin: 0;">
    <div style="max-width: 560px; margin: auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb;">
        <div style="background-color: #0e6b60; color: #ffffff; padding: 20px 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 20px;">منصة لقاحي</h1>
        </div>
        <div style="padding: 24px; color: #1f2937; line-height: 1.9;">
            <p>مرحباً <strong>{{ $parentName }}</strong>،</p>

            @if (!empty($childName))
                <p>بخصوص طفلكم: <strong>{{ $childName }}</strong></p>
            @endif

            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin: 16px 0;">
                <p style="margin: 4px 0;">{!! nl2br(e($bodyMessage)) !!}</p>
            </div>

            @if (count($details) > 0)
                <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
                    @foreach ($details as $detail)
                        <tr>
                            <td style="padding: 8px; border: 1px solid #e5e7eb; background-color: #f9fafb; width: 40%;">{{ $detail['label'] }}</td>
                            <td style="padding: 8px; border: 1px solid #e5e7eb;"><strong>{{ $detail['value'] }}</strong></td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <p>مع خالص التقدير،<br>فريق منصة لقاحي</p>
            <p style="color: #6b7280; font-size: 13px;">هذه رسالة تلقائية من منصة لقاحي، يرجى عدم الرد عليها.</p>
        </div>
    </div>
</body>
</html>