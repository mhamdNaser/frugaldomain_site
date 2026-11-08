# خطة إنجاز دروس منصة التعلّم

خطة إكمال كل دروس المنهج المأخوذ من `Cybersecurity_Roadmap.pptx`. كل درس يُكتب
بالإنجليزية والعربية الفصحى، بأسلوب Cisco NetAcad وW3Schools: شرح مقسّم،
ورسم متحرك يوضح العملية، وكود أو جلسة طرفية، وتمرين عملي، واختبار.

- **مواصفات المحتوى:** [README.md](README.md) (أنواع البلوكات والرسوم المتحركة واللغتان).
- **تعليمات الكتّاب:** [tools/WRITER_BRIEF.md](tools/WRITER_BRIEF.md).
- **التحقق والتقدّم:** `python -I app/Modules/Learning/tools/validate_lessons.py --progress` (من مجلد `api`).

---

## 1. الوضع الحالي

| | العدد |
|:--|--:|
| دروس المنهج | **655** |
| دروس مكتوبة باللغتين | **71** |
| دروس مراجعة (تُولَّد تلقائياً وتحيل إلى الدرس الأصلي) | **90** |
| **دروس متبقية للكتابة** | **494** |

> **ملاحظة:** درس المراجعة لا يُكتب له محتوى. يُنشر تلقائياً عند كتابة الدرس
> الأصلي الذي يحيل إليه وإعادة تشغيل الـ seeder. لذلك ترتيب الدفعات أدناه
> يقدّم المراحل التي تفتح أكبر عدد من دروس المراجعة.

### المكتمل
- [x] Computer & Networking Fundamentals (8 دروس، والـ 10 الباقية مراجعة)
- [x] Cisco CCST Cybersecurity (17)
- [x] Cisco CCNA Cybersecurity (19)
- [x] Cisco CCNA 200-301 (27) — الدفعة 1

---

## 2. الدفعات

كل دفعة تُكتب بنحو 7 كتّاب يعملون بالتوازي، 3 إلى 4 دروس لكل كاتب. حجم
الدفعة 15 إلى 27 درساً.

> **مبدأ الترتيب:** الأساسيات أولاً، ثم إكمال مسار Cisco الأمني، ثم البنية
> التحتية والعمليات، ثم المستويات الاحترافية والخبيرة (CCNP/CCIE) في
> النهاية.

| # | الدورة | المرحلة | الدروس | تفتح مراجعات في | تم |
|--:|:--|:--|--:|:--|:-:|
| 1 | Networking Foundations | Cisco CCNA (200-301) | 27 | الأساسيات، أمن الشبكات | [x] |
| 2 | Network Automation & DevOps | Cisco CCNA Automation (200-901) | 26 | الأتمتة والهندسة الأمنية | [ ] |
| 3 | Cisco Security Track | Cisco CCNP Security (SCOR 350-701) | 27 | أمن الشبكات، الهوية، الويب | [ ] |
| 4 | Cisco Security Track | Cisco CCNP Cybersecurity (CBRCOR 350-201) | 27 | المراقبة، الاستجابة للحوادث | [ ] |
| 5 | Offensive Security / OT | Ethical Hacking (15) + OT Security (12) | 27 | اكتشاف الثغرات، الاختبار الهجومي | [ ] |
| 6 | Systems & Infrastructure | Secure Systems Administration (19) + Professional Network Security (8) | 27 | المراقبة، الأتمتة | [ ] |
| 7 | Systems & Infrastructure | Proxmox & Virtualization Security | 23 | النسخ الاحتياطي | [ ] |
| 8 | Systems & Infrastructure | Database Security | 21 | — | [ ] |
| 9 | Systems & Infrastructure | Web Server & Application Security | 20 | الحاويات | [ ] |
| 10 | Systems / Offensive | Container Security (15) + Vulnerability Discovery (12) | 27 | الاختبار الهجومي المتقدم | [ ] |
| 11 | Security Operations & DFIR | Security Monitoring & Defensive Architecture | 17 | — | [ ] |
| 12 | DFIR / Offensive | Incident Response & Forensics (15) + Advanced Offensive Testing (11) | 26 | التخصص المتقدم | [ ] |
| 13 | Identity & Architecture | Identity & Access Security | 19 | — | [ ] |
| 14 | Identity & Architecture | Backup & Continuity (14) + Automation & Security Engineering (12) | 26 | — | [ ] |
| 15 | Architecture / Labs | Security Engineering & Design (15) + Career Paths (12) | 27 | — | [ ] |
| 16 | Security Operations & DFIR | Advanced Professional Specialization | 18 | — | [ ] |
| 17 | Hands-On Labs | Hands-On Labs & Building Experience | 20 | — | [ ] |
| 18 | Network Automation & DevOps | Cisco CCNP Automation (350-901) | 23 | — | [ ] |
| 19 | Enterprise & Data Center | Cisco CCNP Enterprise (ENCOR + ENARSI) | 25 | — | [ ] |
| 20 | Enterprise & Data Center | Cisco CCNP Data Center (DCCOR 350-601) | 23 | — | [ ] |
| 21 | Cisco Security Track | Cisco CCIE Security (v6.1 Lab) | 22 | — | [ ] |
| 22 | Enterprise & Data Center | Cisco CCIE Data Center (v3.1 Lab) | 23 | — | [ ] |
| | | **المجموع** | **521** (المتبقي 494) | | |

**المحطات:**
- **بعد الدفعة 4:** تكتمل الدورة الأولى للمبتدئين (Networking Foundations) ومراحل Cisco الأمنية حتى CCNP.
- **بعد الدفعة 12:** تكتمل دورات المبتدئ والمتوسط في الأمن السيبراني.
- **بعد الدفعة 17:** تكتمل كل دورات الأمن السيبراني ما عدا CCIE.
- **بعد الدفعة 22:** يكتمل المنهج كاملاً.

---

## 3. خطوات تنفيذ كل دفعة

1. **التحضير.** استخرج قائمة الدروس غير المكتوبة في المرحلة مع وحداتها. الأمر `validate_lessons.py --progress` يبيّن العدد، وملف المنهج في `database/seeders/curriculum/<course>.json` يبيّن الـ slugs. تجاهل دروس المراجعة (`review_of`).
2. **التوزيع.** قسّم الدروس على الكتّاب بحسب الوحدة: كل كاتب يأخذ 3 إلى 4 دروس متتالية من وحدة واحدة أو وحدتين متجاورتين، حتى يبقى السيناريو والمصطلحات متسقة.
3. **التكليف.** كل كاتب يقرأ [tools/WRITER_BRIEF.md](tools/WRITER_BRIEF.md) ويُعطى:
   - قائمة الـ slugs والعناوين.
   - مستوى الجمهور: مبتدئ أو محلل أو مهندس أو خبير.
   - نقاط التركيز المطلوبة في كل درس.
   - الدروس المكتوبة التي يربط إليها ولا يكررها.
4. **التحقق الآلي.** يجب أن يعطي `python -I app/Modules/Learning/tools/validate_lessons.py` النتيجة 0 problems.
5. **اختبارات الـ API.** `php artisan test tests/Feature/LearningApiTest.php` (تحتاج MySQL محلياً). اختبار الـ seeder يحمّل كل الدروس عبر تحقق الباك أند نفسه.
6. **مراجعة بشرية سريعة.** افتح درسين أو ثلاثة من الدفعة على `/learn` و`/ar/learn`. تأكد من:
   - تشغيل الرسوم المتحركة.
   - صحة اتجاه النص العربي.
   - سلامة الأوامر.
7. **النشر:**
   ```bash
   git add -A && git commit -m "Learning: batch N - <stage>" && git push
   # على السيرفر
   php artisan db:seed --class="App\Modules\Learning\database\seeders\LearningCurriculumSeeder"
   ```
   لا يلزم بيلد للفرونت عند إضافة دروس فقط، لأن المحتوى يأتي من الـ API.
8. **التحديث.** ضع ✓ للدفعة في الجدول أعلاه.

---

## 4. معايير القبول لكل درس

- [ ] العنوان مطابق للمنهج، بالإنجليزية (`title`) والعربية (`title_ar`).
- [ ] ملخص وأهداف تبدأ بفعل: Explain / Configure / Identify، وبالعربية: يشرح / يهيّئ / يميّز.
- [ ] قسم `##` واحد في كل بلوك نص، وعنوان واضح لكل قسم.
- [ ] رسم متحرك واحد على الأقل يوضّح عملية حقيقية، من 4 إلى 10 خطوات.
- [ ] كود أو جلسة طرفية واقعية بأوامر دفاعية، والمخرجات مشروحة.
- [ ] تنبيه في الموضع الذي يخطئ فيه المتعلمون عادة، وتمرين «جرّب بنفسك» آمن.
- [ ] بلوك مصطلحات أساسية من 6 إلى 10 مصطلحات.
- [ ] اختبار «تحقّق من فهمك» من 4 إلى 5 أسئلة، لكل سؤال شرح.
- [ ] خلاصة في آخر الدرس.
- [ ] إنجليزية صحيحة بالإملاء الأمريكي.
- [ ] عربية فصحى، والمصطلح الإنجليزي بين قوسين عند أول ذكر.
- [ ] النسخة العربية تطابق الإنجليزية بلوكاً ببلوك، والكود والأوامر كما هي.
- [ ] لا كود استغلال جاهز، ولا عناوين حقيقية: نطاقات التوثيق ونطاقات `example.*` فقط.

---

## 5. ملاحظات مفتوحة

- [ ] **توحيد الإملاء الإنجليزي.** بعض الدروس الـ 44 بالإملاء البريطاني (behaviour, organisation). يُوحَّد على الأمريكي في جولة تدقيق واحدة.
- [ ] **سيناريو الحوادث.** سيناريو درس `incident-response-procedures` لا يطابق الجدول الزمني في `post-incident-analysis`. يُوحَّد على قصة واحدة.
- [ ] **روابط لدروس غير مكتوبة.** نحو 16 رابطاً (كانت 24 قبل الدفعة 1) تشير الآن إلى دروس غير مكتوبة، وتعرض صفحة «قيد الكتابة». تُحلّ تلقائياً مع تقدّم الدفعات. شغّل `validate_lessons.py` بعد كل دفعة.
- [ ] **مسرد موحّد.** يُكتب ملف `tools/GLOSSARY.md` لترجمات المصطلحات، مثل الإشارات الدورية (Beaconing) والأجهزة الطرفية (Endpoints)، حتى تبقى الترجمة متسقة بين الكتّاب.
- [ ] **وسائط حقيقية (اختياري).** صور ومخططات طوبولوجيا أو فيديو لبعض المختبرات، تُرفع من لوحة التحكم.

---

## 6. ما بعد اكتمال المنهج

- إضافة تصنيفات جديدة، مثل AI، من لوحة التحكم. التصنيف الجديد يظهر له تبويب تلقائياً.
- اختبار نهائي لكل مرحلة يجمع أسئلة دروسها.
- شهادة إتمام للمستخدم المسجّل. يحتاج ذلك حفظ التقدّم في الحساب بدل المتصفح.
