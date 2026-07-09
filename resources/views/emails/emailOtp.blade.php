<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>رمز التحقق - Clinco الطبي</title>
    <style type="text/css">
        body, table, td, p, div, h1 { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        @media only screen and (max-width: 500px) {
            .otp-text { font-size: 34px !important; letter-spacing: 4px !important; padding: 12px 20px !important; }
            .card-container { padding: 20px 10px !important; }
            .content-padding { padding-left: 20px !important; padding-right: 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #e0f2f7; direction: rtl;" bgcolor="#e0f2f7">

    <!-- الجدول الرئيسي المحيط بالإيميل بالكامل -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #e0f2f7;" bgcolor="#e0f2f7">
        <tr>
            <td class="card-container" align="center" style="padding: 40px 15px;">

                <!-- الكرت الأبيض الأساسي المحتضن للبيانات -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px; background-color: #ffffff; border-radius: 32px; overflow: hidden; border: 1px solid rgba(90, 170, 200, 0.2); box-shadow: 0 20px 40px rgba(0, 30, 40, 0.08);" bgcolor="#ffffff">

                    <!-- الهيدر والشعار -->
                    <tr>
                        <td class="content-padding" align="center" style="padding: 40px 35px 15px 35px;">
                            @php($logoPath = public_path('storage/logo/logo.jpg'))
                            @if(file_exists($logoPath))
                            <div style="margin-bottom: 16px;">
                                <img src="{{ $message->embed($logoPath) }}" alt="Clinco" width="80" style="display: block; border-radius: 20px; outline: none; text-decoration: none;" />
                            </div>
                            @endif
                            <h1 style="color: #146b8a; font-size: 24px; font-weight: 800; margin: 8px 0 10px 0;">🌿 رمز التحقق لـ Clinco</h1>
                            <p style="color: #2f86a6; font-size: 13px; font-weight: 600; margin: 0; background-color: #e6f4f9; display: inline-block; padding: 6px 20px; border-radius: 40px;">منظومة رعاية صحية ذكية</p>
                        </td>
                    </tr>

                    <!-- رسالة الترحيب -->
                    <tr>
                        <td class="content-padding" align="center" style="padding: 10px 35px; color: #1e4a5f; font-size: 15px; line-height: 1.6; text-align: center;">
                            @if($name)
                                <p style="margin: 0 0 12px 0;">✨ مرحباً بك <strong style="background-color: #e1f0f5; padding: 4px 14px; border-radius: 50px; color: #0f5e7c; font-weight: 800;">{{ $name }}</strong></p>
                            @else
                                <p style="margin: 0 0 12px 0;">أهلاً بك في رحلة العناية بصحتك ✨</p>
                            @endif

                            <p style="margin: 0; color: #456877;">
                                <strong>خطوة واحدة تفصل بينك وبين خدمات Clinco المتكاملة</strong><br />
                                استخدم الرمز السري أدناه لإتمام عملية التحقق والوصول لحسابك:
                            </p>
                        </td>
                    </tr>

                    <!-- منطقة كرت الـ OTP (تعديل الحواف والأبعاد لتطابق الهيكل) -->
                    <tr>
                        <td class="content-padding" style="padding: 20px 35px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fafefe; border-radius: 24px; border: 1px solid #d4edf5;" bgcolor="#fafefe">
                                <tr>
                                    <td align="center" style="padding: 25px 15px;">
                                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                            <tr>
                                                <td class="otp-text" align="center" style="background-color: #ffffff; border: 1px solid #e2f0f5; border-radius: 20px; color: #0b5e7e; font-size: 40px; font-weight: 800; padding: 12px 40px; letter-spacing: 8px; direction: ltr; font-family: 'Courier New', Courier, monospace; box-shadow: 0 4px 12px rgba(14, 94, 126, 0.04);" bgcolor="#ffffff">
                                                    {{ $otp }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- شريط المعلومات الأمني متطابق العرض -->
                    <tr>
                        <td class="content-padding" style="padding: 0 35px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #eef6fa; border-radius: 14px;">
                                <tr>
                                    <td align="center" style="padding: 12px 15px; color: #146b8a; font-size: 13px; font-weight: 600; line-height: 1.4;">
                                        🔐 صالح لمرة واحدة فقط ⏱️ ينتهي خلال 10 دقائق
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- عبارة الفريق الترويجية -->
                    <tr>
                        <td class="content-padding" align="center" style="padding: 20px 35px 25px 35px;">
                            <p style="margin: 0; background-color: #f3f8fa; border-radius: 40px; padding: 8px 22px; display: inline-block; font-size: 13px; font-weight: 700; color: #1f7a9c;">
                                ⚕️ فريق Clinco يضع صحتك أولاً
                            </p>
                        </td>
                    </tr>

                    <!-- الفوتر وحقوق الحفظ -->
                    <tr>
                        <td class="content-padding" align="center" style="padding: 30px 35px 35px 35px; border-top: 1px solid #ebf4f7; color: #7aa9bf; font-size: 12px; line-height: 1.6; text-align: center;">
                            <p style="margin: 0 0 10px 0; color: #8dafc1;">
                                📧 إذا لم تطلب هذا الرمز، يمكنك تجاهل هذا البريد بأمان.<br />
                                جميع الحقوق محفوظة لمنصة Clinco الطبية © {{ date('Y') }}
                            </p>
                            <div style="color: #9bc0d1; font-size: 12px;">
                                برعاية <span style="color: #e06666;">❤️</span> منظومة العناية المتكاملة
                            </div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
