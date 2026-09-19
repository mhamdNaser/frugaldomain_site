# برومبت: بناء خدمة تحويل الملفات على Hugging Face

> انسخ كل ما بين الأسطر الفاصلة أدناه والصقه في جلسة جديدة.
> المعلومات التقنية فيه مُتحقَّق منها من توثيق Hugging Face الرسمي (سبتمبر 2026).

---

## البرومبت

```
أنشئ لي خدمة باك-إند على Hugging Face Space تحوّل الملفات، وتتكامل مع
موقعي القائم على https://frugaldomain.site

## السياق المهم (تحقّقت منه مسبقاً — لا تخالفه)

1. استضافتي الحالية Hostinger مشتركة، وتحققت أنه:
   - لا يوجد libreoffice / soffice
   - لا يوجد apt / yum / dnf ولا sudo
   - دوال PHP: exec و shell_exec و passthru و system كلها DISABLED
   لذلك التحويل على الاستضافة الحالية مستحيل، ولهذا نبني خدمة منفصلة.

2. حسابي على Hugging Face اسم المستخدم فيه: 92naser92
   ولدي Space يعمل بالفعل: 92naser92-arabic-gestures.hf.space
   أي أن نمط ربط Space بموقعي مُجرَّب وناجح.

3. توثيق Hugging Face الرسمي ينص على:
   - "Gradio and Docker Spaces run on compute and require a paid plan to
     create: PRO for personal accounts."
     => إن كان حسابي مجانياً فلا أستطيع إنشاء Docker Space.
   - لكن Gradio Spaces تدعم حزم النظام:
     "Debian dependencies are also supported. Add a packages.txt file at the
      root of your repository... each line will be read and installed by
      apt-get install."
     => أي أن libreoffice و ffmpeg قابلان للتثبيت على Gradio Space.
   - "On free hardware, your Space will go to sleep after a period of time
      if unused."
   - العتاد المجاني: CPU Basic = 2 vCPU / 16 GB RAM / 50 GB قرص غير دائم.
   - الشبكة: المنافذ 80 و 443 و 8080 فقط.

**اسألني أولاً: هل حسابي PRO أم مجاني؟** لأن الجواب يحدد SDK:
- PRO    => استخدم Docker SDK (تحكّم أنظف بالصورة)
- مجاني  => استخدم Gradio SDK مع packages.txt، وركّب FastAPI داخله عبر
            gr.mount_gradio_app حتى أحصل على REST API حقيقي.
لا تفترض الجواب.

## المطلوب بناؤه

خدمة FastAPI فيها نقطتان أساسيتان:

### 1) POST /convert/document
- تستقبل ملفاً (multipart/form-data، اسم الحقل: file)
- الصيغ المقبولة: docx, doc, xlsx, xls, pptx, ppt, odt, ods, odp, rtf, txt, csv
- تحوّله إلى PDF عبر:
    soffice --headless --norestore --convert-to pdf --outdir <tmp> <input>
- ترجع ملف الـ PDF مباشرة (StreamingResponse أو FileResponse)

### 2) POST /convert/audio
- تستقبل ملفاً + حقل target_format
- الصيغ: mp3, wav, ogg, m4a, aac, flac, opus, webm
- تحوّله عبر ffmpeg
- ترجع الملف الناتج

### 3) GET /health
- ترجع {"status":"ok"} + تتحقق فعلياً أن soffice و ffmpeg موجودان
- الواجهة ستستدعيها لإيقاظ الـ Space وللتحقق من الجاهزية

## متطلبات إلزامية

**أ. دعم العربي — هذا أهم شرط.**
هذه المشكلة التي دفعتني لبناء الخدمة أصلاً: التحويل في المتصفح كان يُخرج
العربي كرموز مثل þ”þóþ®þôþ¨þßþ• لأن الخط المستخدم لاتيني بترميز WinAnsi.
لذلك ضع في packages.txt خطوطاً عربية إلزاماً:
    fonts-noto
    fonts-noto-core
    fonts-amiri
    fonts-kacst
    fonts-hosny-amiri
وبعد البناء نفّذ fc-cache -f إن لزم.
**اختبر فعلياً** بملف docx عربي فيه فقرة مختلطة عربي+إنجليزي مثل
"مكاتب gis في كل المحافظات"، وأكّد لي أن الناتج عربي سليم لا مربعات ولا رموز.

**ب. الأمان**
- حدّ أقصى لحجم الملف (مثلاً 25 ميغابايت) مع رسالة خطأ واضحة
- تحقّق من الامتداد ومن نوع MIME، وارفض ما عداه
- استخدم tempfile.TemporaryDirectory واحذف كل شيء بعد الرد مباشرة
  (القرص غير دائم وغير خاص)
- **لا تمرّر اسم الملف الذي يرفعه المستخدم إلى الـ shell إطلاقاً**؛
  استخدم subprocess بقائمة وسائط لا shell=True، وولّد اسماً عشوائياً داخلياً
- ضع timeout على subprocess (مثلاً 60 ثانية) وإلا علق الطلب للأبد
- CORS: اسمح فقط لـ https://frugaldomain.site و https://www.frugaldomain.site
  و http://localhost:5173 للتطوير — لا تستخدم "*"

**ج. المتانة**
- إن فشل soffice أو ffmpeg أعد 422 برسالة مفهومة لا 500 صامتاً
- سجّل الأخطاء في اللوغ
- تعامل مع البرد الأول: LibreOffice يأخذ ثوانٍ إضافية في أول تشغيل

## المخرجات المطلوبة منك

1. app.py كاملاً وجاهزاً للصق
2. requirements.txt
3. packages.txt
4. README.md بترويسة YAML الصحيحة (sdk، sdk_version، app_file، pinned)
5. خطوات الرفع بالتفصيل عبر git أو واجهة الويب
6. أمر curl جاهز لاختبار كل نقطة
7. اذكر لي صراحةً أي شيء لم تستطع التحقق منه أو قد يفشل

## أسلوب العمل المطلوب

- اشرح كل قرار تقني ولماذا اخترته
- لا تدّعِ أن شيئاً يعمل قبل أن تختبره فعلياً
- إن اكتشفت أن معلومة في هذا البرومبت خاطئة فصحّحها لي صراحةً بدل المجاراة
- اكتب التعليقات في الكود بالإنجليزية والشرح لي بالعربية
```

---

## ملاحظات لك قبل الإرسال

**١. تأكّد من نوع حسابك أولاً**
افتح <https://huggingface.co/settings/billing> — إن لم يكن PRO فالمسار هو
Gradio + `packages.txt` لا Docker. البرومبت يطلب من النموذج أن يسألك، فلا
تدعه يفترض.

**٢. الخطوط العربية هي بيت القصيد**
دون `fonts-amiri` أو `fonts-noto` سيُخرج LibreOffice مربعات فارغة بدل الحروف.
هذه أكثر خطوة يُغفل عنها.

**٣. لا ترفع ملفات حسّاسة**
ملفات المستخدمين ستغادر سيرفرك إلى طرف ثالث. إن كانت الوثائق سرّية (كمحضر
اجتماعك) ففكّر في VPS خاص بدلاً من ذلك.

**٤. النوم بعد الخمول أمر مؤكّد**
على العتاد المجاني ينام الـ Space، وأول طلب بعدها ينتظر ٣٠–٦٠ ثانية. خطة
التكامل في `PLAN.md` تعالج هذا بنداء إيقاظ مبكر وآلية احتياط.
