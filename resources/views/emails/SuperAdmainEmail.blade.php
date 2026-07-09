<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ar" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>بيانات الدخول إلى نظام المستودعات</title>
    <style type="text/css">
        body, table, td, a { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        a { text-decoration: none; }
    </style>
</head>
<!-- تم تغيير لون الخلفية ليتناسب مع اللون الرمادي/الأزرق الفاتح جداً في خلفية النظام -->
<body style="margin: 0; padding: 0; background-color: #f3f4f6; direction: rtl;" bgcolor="#f3f4f6">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #f3f4f6;" bgcolor="#f3f4f6">
        <tr>
            <td align="center" style="padding: 40px 10px;">

                <!-- الكرت الرئيسي الأبيض -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px rgba(0,0,0,0.05);" bgcolor="#ffffff">

                    <!-- الهيدر والشعار -->
                    <tr>
                        <td align="center" style="padding: 35px 30px 10px 30px;">
                            @php($logoPath = public_path('storage/logo/logo.jpg'))
                            @if(file_exists($logoPath))
                            <img src="{{ $message->embed($logoPath) }}" alt="WMS System" width="80" style="display: block; border-radius: 12px; outline: none; text-decoration: none;" />
                            @endif
                            <!-- تم استخدام اللون الأزرق الداكن المطابق للقائمة الجانبية والأزرار في الصور -->
                            <h1 style="color: #1552a8; font-size: 22px; font-weight: 800; margin: 15px 0 5px 0; font-family: 'Segoe UI', Tahoma, sans-serif;">مرحباً بك في فريق المستودع 📦</h1>
                            <p style="color: #1e40af; font-size: 13px; font-weight: 600; margin: 0; background-color: #dbeafe; display: inline-block; padding: 5px 15px; border-radius: 20px;">إشعار نظام إدارة المستودعات (WMS)</p>
                        </td>
                    </tr>

                    <!-- الرسالة الترحيبية -->
                    <tr>
                        <td align="center" style="padding: 10px 30px 20px 30px; color: #374151; font-size: 15px; line-height: 1.6; text-align: center;">
                            زميلنا العزيز، تم إنشاء حسابك الرسمي على <strong>نظام إدارة المستودعات</strong> بنجاح.
                            يمكنك الآن استخدام البيانات التالية للوصول إلى لوحة القيادة (Dashboard) وإدارة العمليات المخزنية:
                        </td>
                    </tr>

                    <!-- منطقة البيانات -->
                    <tr>
                        <td style="padding: 0 30px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0;" bgcolor="#f8fafc">

                                <!-- حقل البريد -->
                                <tr>
                                    <td align="right" style="padding-bottom: 5px; color: #64748b; font-size: 12px; font-weight: 700;">👤 اسم المستخدم / البريد الإلكتروني:</td>
                                </tr>
                                <tr>
                                    <td align="left" style="padding: 10px 14px; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-weight: 600; font-size: 14px; direction: ltr; font-family: Arial, sans-serif;" bgcolor="#ffffff">
                                        {{ $email }}
                                    </td>
                                </tr>

                                <tr><td height="15"></td></tr>

                                <!-- حقل كلمة المرور -->
                                <tr>
                                    <td align="right" style="padding-bottom: 5px; color: #64748b; font-size: 12px; font-weight: 700;">🔑 كلمة المرور الافتراضية:</td>
                                </tr>
                                <tr>
                                    <td align="left" style="padding: 10px 14px; background-color: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; color: #0f172a; font-weight: 600; font-size: 14px; direction: ltr; font-family: Arial, sans-serif;" bgcolor="#ffffff">
                                        {{ $password }}
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- زر الدخول المباشر للوحة التحكم -->
                    <tr>
                        <td align="center" style="padding: 30px 30px 15px 30px;">
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate;">
                                <tr>
                                    <!-- لون الزر مطابق للون زر "إضافة فئة جديدة" أو "تسجيل إدخال جديد" في الصور -->
                                    <td align="center" style="border-radius: 6px; background-color: #1552a8;" bgcolor="#1552a8">
                                        <a href="{{ url('/login') }}" target="_blank" style="font-size: 14px; font-weight: bold; color: #ffffff; padding: 12px 35px; display: inline-block; background-color: #1552a8; border-radius: 6px; border: 1px solid #1552a8; font-family: 'Segoe UI', Tahoma, sans-serif;">💻 الدخول إلى النظام</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- التنبيه الأمني مدمج بألوان هادئة تتناسب مع طابع النظام -->
                    <tr>
                        <td style="padding: 10px 30px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f0fdf4; border-radius: 8px; border: 1px solid #bbf7d0;" bgcolor="#f0fdf4">
                                <tr>
                                    <td align="center" style="padding: 10px 15px; color: #166534; font-size: 12px; font-weight: 700; line-height: 1.4;">
                                        🔒 ملاحظة أمنية: يرجى تغيير كلمة المرور الافتراضية فور تسجيل دخولك الأول لحماية صلاحياتك في المستودع.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- الفوتر -->
                    <tr>
                        <td align="center" style="padding: 30px 30px; border-top: 1px solid #e5e7eb; color: #94a3b8; font-size: 11px; line-height: 1.5; text-align: center;">
                            هذا البريد الإلكتروني مخصص لموظفي المستودع فقط ويحتوي على معلومات حساسة.<br />
                            نظام إدارة المستودعات المتكامل © {{ date('Y') }}
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
