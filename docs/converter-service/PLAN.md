# تكامل خدمة تحويل الملفات

## الحالة

كود الخدمة والتكامل جاهزان محلياً. لم تُرفع الخدمة إلى Hugging Face ولم يُختبر
تحويل DOCX عربي حقيقي بعد؛ يلزم إنشاء Space وإضافة المفتاح السري ثم اختبارها.

## مستودعات المشروع التي تم التحقق منها

- `mhamdNaser/frugaldomain_site`: مستودع النشر، ويحتوي Laravel API وCDN وخدمة
  التحويل وملفات الموقع المبنية.
- `mhamdNaser/sydev-front`: مستودع React/Vite المصدر. يبني ملفات الموقع التي
  تُنسخ إلى جذر مستودع النشر.
- `api/` و`cdn/` و`services/convert-api/` مجلدات ضمن مستودع النشر وليست
  مستودعات Git مستقلة. Hugging Face Space سيكون مستودعاً ثالثاً منفصلاً بعد
  إنشائه.

## تدفق الطلب

```text
المتصفح
  → Laravel على api.frugaldomain.site
  → FastAPI داخل Hugging Face Space
  → PDF أو ملف صوتي محوّل
```

يبقى مفتاح الخدمة على Hugging Face وHostinger فقط. ينتظر Laravel استيقاظ
الـ Space ثم يرسل الملف؛ وتعرض صفحة التحويل مؤشّر انتظار أثناء رفعه ومعالجته.
تحويل الصور يبقى في المتصفح.

المستندات المدعومة تشمل DOC/DOCX وXLS/XLSX وPPT/PPTX وODT/ODS/ODP وRTF وTXT
وCSV. الصوت يمر عبر ffmpeg. تحفظ الخدمة الملفات في مساحة مؤقتة وتحذف مجلد
العمل بعد إرسال النتيجة أو عند فشل التحويل.

## Hugging Face المجاني

تسعير Hugging Face الحالي يعرض CPU Basic (2 vCPU و16 GB RAM) مجاناً. اختر
Gradio SDK وCPU Basic، وليس Docker أو عتاداً مدفوعاً. قد ينام Space المجاني عند
الخمول؛ يحاول Laravel إيقاظه حتى 90 ثانية، وقد يطلب من المستخدم المحاولة
مجدداً إن لم يصبح جاهزاً.

`fonts-amiri` ليس اسم حزمة Debian المتاحة؛ تستخدم الخدمة `fonts-hosny-amiri`
إلى جانب `fonts-noto` و`fonts-noto-core` و`fonts-kacst`.

## ما يلزم إكماله خارج الكود

1. إنشاء Gradio Space باسم تختاره تحت حساب Hugging Face.
2. رفع محتويات `services/convert-api/` إلى مستودع الـ Space.
3. إنشاء `CONVERT_API_KEY` محلياً وإضافته كسِرّ في Space و`api/.env` على
   Hostinger. لا ترسل المفتاح أو رمز Hugging Face في المحادثة.
4. وضع عنوان الـ Space في `CONVERT_SERVICE_URL`، ثم تشغيل
   `php artisan config:clear` على Hostinger.
5. التحقق من مهلة PHP وحدود رفع الملفات في hPanel؛ قد تقطع الاستضافة الطلب
   المتزامن قبل أن يستيقظ Space.
6. اختبار `/health` ثم ملف DOCX عربي غير حساس يحوي «مكاتب gis في كل
   المحافظات»، وفحص أن الناتج عربي متصل ويحافظ على التنسيق. اختبارات الكود
   المحلية لا تستطيع إثبات جودة LibreOffice الفعلية على Space.

خطوات الإنشاء والرفع والأوامر التفصيلية موجودة في
[`services/convert-api/DEPLOY-HUGGINGFACE.md`](../../services/convert-api/DEPLOY-HUGGINGFACE.md).
