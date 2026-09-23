<?php

namespace App\Modules\Component\database\seeders\Library;

use App\Modules\Component\database\seeders\Library\ComponentKit as Kit;

/**
 * The UI Elements category: the smaller pieces - buttons, dialogs, menus,
 * cards, navigation - that the other three categories are assembled from.
 *
 * Each entry shows the element in its real states rather than in one happy
 * example, because the state nobody designed is the one that breaks in
 * production: the disabled button, the empty list, the error message, the name
 * too long for its row.
 *
 * Interactive pieces are built on the element that already behaves: a
 * disclosure is a `<button>`, a dialog is `<dialog>`, a set of choices is a
 * radio group. Nothing here reimplements keyboard support that the browser
 * would have provided for free.
 */
final class UiElementLibrary
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return array_merge(self::setOne(), self::setTwo(), self::setThree());
    }

    /* ================================================================== */
    /* Shared pieces                                                       */
    /* ================================================================== */

    private static function wrap(string ...$parts): string
    {
        return '<div class="wrap">' . implode('', $parts) . '</div>';
    }

    private static function card(string $inner, string $style = ''): string
    {
        return '<div class="card"' . ($style === '' ? '' : ' style="' . $style . '"') . '>' . $inner . '</div>';
    }

    private static function head(string $title, string $sub = '', string $tools = ''): string
    {
        $caption = $sub === '' ? '' : '<p class="sub">' . $sub . '</p>';

        return '<div class="hd"><div><h2>' . $title . '</h2>' . $caption . '</div>'
            . ($tools === '' ? '' : '<div class="row" style="gap:8px;flex-wrap:wrap">' . $tools . '</div>')
            . '</div>';
    }

    /** A labelled group inside a specimen sheet. */
    private static function group(string $label, string $content, string $note = ''): string
    {
        $hint = $note === '' ? '' : '<p class="xs mut" style="margin-top:8px">' . $note . '</p>';

        return '<div class="grp"><span class="glabel">' . $label . '</span>'
            . '<div class="gbody">' . $content . '</div>' . $hint . '</div>';
    }

    /** The CSS behind {@see group()}. */
    private static function groupCss(): string
    {
        return ".grp{padding:18px 20px;border-bottom:1px solid var(--bd)}\n"
            . ".grp:last-child{border-bottom:0}\n"
            . ".glabel{display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);font-weight:700;margin-bottom:12px}\n"
            . '.gbody{display:flex;gap:10px;flex-wrap:wrap;align-items:center}';
    }

    private static function btn(string $label, string $icon = '', string $kind = '', string $extra = ''): string
    {
        $glyph = $icon === '' ? '' : Kit::icon($icon, 15);

        return '<button class="btn ' . $kind . '" type="button" ' . $extra . '>' . $glyph . $label . '</button>';
    }

    private static function grid(int $min, string ...$cards): string
    {
        return '<div style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(' . $min . 'px,1fr))">'
            . implode('', $cards) . '</div>';
    }

    /* ================================================================== */
    /* Definitions                                                         */
    /* ================================================================== */

    /** @return array<int,array<string,mixed>> */
    private static function setOne(): array
    {
        return [
            [
                'slug' => 'button-set',
                'name' => 'Button set',
                'name_ar' => 'مجموعة الأزرار',
                'tagline' => 'Every variant, size and state on one sheet.',
                'tagline_ar' => 'كل الأنواع والأحجام والحالات في لوحة واحدة.',
                'summary' => 'A button specimen sheet: primary through ghost, three sizes, and the states that get forgotten - hover, focus ring, disabled and loading. The focus ring is visible on every variant, which is what makes a button usable without a mouse.',
                'summary_ar' => 'لوحة عرض شاملة للأزرار: من الأساسي حتى الشفاف، بثلاثة أحجام، مع الحالات التي تُنسى عادةً، مثل التمرير فوقه وحلقة التركيز والتعطيل والتحميل. حلقة التركيز ظاهرة في كل الأنواع، وهذا ما يجعل الزر قابلًا للاستخدام دون فأرة.',
                'accent' => '#2563eb',
                'tags' => ['buttons', 'variants', 'states', 'specimen'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Five variants including a destructive one', 'Focus ring visible on every variant', 'Loading state with a spinner that respects reduced motion'],
                'features_ar' => ['خمسة أنواع، منها نوع للإجراءات الخطرة', 'حلقة تركيز ظاهرة في كل الأنواع', 'حالة تحميل بمؤشر دوّار يحترم تفضيل تقليل الحركة'],
                'height' => 640,
                'max' => 720,
                'css' => self::groupCss() . "\n@keyframes spin{to{transform:rotate(360deg)}}\n.spin{animation:spin .8s linear infinite}\n@media (prefers-reduced-motion:reduce){.spin{animation-duration:2.4s}}\n.btn.danger{background:var(--bad);border-color:var(--bad);color:#fff}\n.btn.danger:hover{filter:brightness(1.08);color:#fff}\n.btn.big{padding:12px 20px;font-size:14.5px;border-radius:12px}\n.btn.block{width:100%}",
                'body' => self::wrap(
                    self::card(
                        self::head('Buttons', 'Variants, sizes and states')
                        . self::group('Variants', self::btn('Primary', '', 'pri') . self::btn('Secondary') . self::btn('Ghost', '', 'gh')
                            . self::btn('Destructive', 'trash', 'danger') . '<a class="btn" href="#">Link button</a>')
                        . self::group('Sizes', self::btn('Small', '', 'tiny') . self::btn('Medium') . self::btn('Large', '', 'big'))
                        . self::group('With icons', self::btn('Add item', 'plus', 'pri') . self::btn('Download', 'download')
                            . self::btn('Settings', 'settings', 'gh') . '<button class="btn" type="button" aria-label="More options" style="padding:9px">' . Kit::icon('more', 16) . '</button>')
                        . self::group('States', self::btn('Default') . self::btn('Disabled', '', '', 'disabled')
                            . '<button class="btn pri" type="button" disabled><span class="spin" style="display:inline-flex">' . Kit::icon('refresh', 15) . '</span>Saving…</button>'
                            . self::btn('Focus me'), 'Tab to any button to see the focus ring - it is never removed, only styled.')
                        . self::group('Full width', '<div style="width:100%;display:grid;gap:9px">'
                            . self::btn('Continue to payment', 'arrow-right', 'pri block')
                            . self::btn('Back to basket', '', 'block') . '</div>')
                    )
                ),
            ],
            [
                'slug' => 'modal-dialog',
                'name' => 'Modal dialog',
                'name_ar' => 'نافذة منبثقة',
                'tagline' => 'A real dialog element with backdrop, focus trap and Escape.',
                'tagline_ar' => 'عنصر dialog حقيقي مع خلفية معتمة وحصر للتركيز والإغلاق بـ Esc.',
                'summary' => 'Built on the native dialog element, which brings the focus trap, the backdrop and Escape-to-close without a line of code. A div-based modal has to reimplement all three, and usually only manages one.',
                'summary_ar' => 'مبنية على عنصر dialog الأصلي، الذي يوفّر حصر التركيز والخلفية المعتمة والإغلاق بـ Esc دون سطر برمجي واحد. أما النافذة المبنية من div فعليها إعادة تنفيذ الثلاثة، وغالبًا لا تنجح إلا في واحد منها.',
                'accent' => '#4f46e5',
                'tags' => ['modal', 'dialog', 'native', 'overlay'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Native dialog: focus trap and Escape come free', 'Backdrop click closes the dialog', 'Returns focus to the trigger on close'],
                'features_ar' => ['عنصر dialog أصلي: حصر التركيز والإغلاق بـ Esc دون جهد', 'النقر على الخلفية يغلق النافذة', 'يعود التركيز إلى زر الفتح عند الإغلاق'],
                'height' => 520,
                'max' => 620,
                'css' => "dialog{border:0;padding:0;border-radius:16px;max-width:440px;width:calc(100% - 36px);background:var(--card);color:var(--ink);box-shadow:0 24px 60px -24px rgba(0,0,0,.5)}\ndialog::backdrop{background:rgba(15,23,42,.55);backdrop-filter:blur(2px)}\ndialog .body{padding:22px 24px}\ndialog .acts{display:flex;justify-content:flex-end;gap:9px;padding:14px 24px;border-top:1px solid var(--bd)}\n.iconmark{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:var(--bad-bg);color:var(--bad);margin-bottom:14px}",
                'js' => "const dialog=document.getElementById('dlg');\n"
                    . "document.getElementById('open').addEventListener('click',()=>dialog.showModal());\n"
                    . "dialog.querySelectorAll('[data-close]').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>dialog.close());\n"
                    . "});\n"
                    . "dialog.addEventListener('click',event=>{\n"
                    . "  const box=dialog.getBoundingClientRect();\n"
                    . "  const outside=event.clientY<box.top||event.clientY>box.bottom||event.clientX<box.left||event.clientX>box.right;\n"
                    . "  if(outside)dialog.close();\n"
                    . '});',
                'body' => self::wrap(
                    self::card(
                        self::head('Modal dialog', 'Press Escape, click the backdrop, or use the buttons')
                        . '<div class="pad"><button class="btn pri" type="button" id="open">' . Kit::icon('trash', 15) . 'Delete project</button>'
                        . '<p class="hint">Focus moves into the dialog and returns to this button when it closes.</p></div>'
                    ),
                    '<dialog id="dlg" aria-labelledby="dlgtitle">'
                    . '<div class="body"><div class="iconmark">' . Kit::icon('alert', 22) . '</div>'
                    . '<h2 id="dlgtitle">Delete this project?</h2>'
                    . '<p class="sub" style="margin-top:8px">Aurora Redesign and its 142 files will be removed. Team members lose access immediately and this cannot be undone.</p></div>'
                    . '<div class="acts"><button class="btn" type="button" data-close>Cancel</button>'
                    . '<button class="btn pri" type="button" data-close style="background:var(--bad);border-color:var(--bad)">Delete project</button></div>'
                    . '</dialog>'
                ),
            ],
            [
                'slug' => 'alert-banners',
                'name' => 'Alert banners',
                'name_ar' => 'شرائط التنبيه',
                'tagline' => 'Four severities, each with an icon and a title.',
                'tagline_ar' => 'أربع درجات خطورة، لكل منها أيقونة وعنوان.',
                'summary' => 'Alerts that work without colour: every severity carries its own icon and a word, so the difference between a warning and an error survives a colourblind reader, a greyscale print and a forced-colours mode.',
                'summary_ar' => 'تنبيهات تعمل دون الاعتماد على اللون: لكل درجة خطورة أيقونتها وكلمتها، فيبقى الفرق بين التحذير والخطأ واضحًا لمن لديه عمى ألوان، وفي الطباعة بالأبيض والأسود، وفي وضع الألوان القسرية.',
                'accent' => '#d97706',
                'tags' => ['alerts', 'banners', 'status', 'accessibility'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Icon and title per severity, never colour alone', 'Dismissible variant with an accessible close button', 'Inline action inside the banner'],
                'features_ar' => ['أيقونة وعنوان لكل درجة، لا لون وحده أبدًا', 'نوع قابل للإغلاق بزر إغلاق سهل الوصول', 'إجراء مضمَّن داخل الشريط'],
                'height' => 620,
                'max' => 680,
                'css' => ".al{display:flex;gap:12px;padding:14px 16px;border-radius:13px;border:1px solid;align-items:flex-start}\n.al .txt{flex:1;min-width:0}\n.al b{display:block;font-size:13.5px;margin-bottom:2px}\n.al p{font-size:12.5px;opacity:.92}\n.al.info{background:var(--acc-soft);border-color:transparent;color:var(--acc)}\n.al.ok{background:var(--ok-bg);border-color:transparent;color:var(--ok)}\n.al.warn{background:var(--warn-bg);border-color:transparent;color:var(--warn)}\n.al.bad{background:var(--bad-bg);border-color:transparent;color:var(--bad)}\n.al .x{border:0;background:none;padding:2px;cursor:pointer;color:inherit;opacity:.7;display:inline-flex}\n.al .x:hover{opacity:1}\n.al.plain{background:var(--card);border-color:var(--bd);color:var(--ink)}\n.stack{display:grid;gap:12px;padding:18px 20px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Alerts', 'Severity is carried by the icon and the words, not the colour')
                        . '<div class="stack">'
                        . '<div class="al info" role="status">' . Kit::icon('info', 19) . '<span class="txt"><b>Scheduled maintenance</b>'
                        . '<p>The API will be read-only on Sunday between 02:00 and 03:00 UTC.</p></span></div>'
                        . '<div class="al ok" role="status">' . Kit::icon('check', 19) . '<span class="txt"><b>Component published</b>'
                        . '<p>Analytics table with sparklines is now live in the gallery.</p></span></div>'
                        . '<div class="al warn" role="status">' . Kit::icon('alert', 19) . '<span class="txt"><b>Your card expires in 9 days</b>'
                        . '<p>Update it before 30 September to avoid an interrupted subscription.</p></span>'
                        . '<button class="btn tiny" type="button">Update card</button></div>'
                        . '<div class="al bad" role="alert">' . Kit::icon('x', 19) . '<span class="txt"><b>Upload failed</b>'
                        . '<p>brand-guide-2026.pdf is 24 MB. The limit is 10 MB per file.</p></span>'
                        . '<button class="x" type="button" aria-label="Dismiss">' . Kit::icon('x', 17) . '</button></div>'
                        . '<div class="al plain">' . Kit::icon('zap', 19) . '<span class="txt"><b>Tip</b>'
                        . '<p>Press <kbd>Ctrl</kbd> + <kbd>K</kbd> anywhere to jump to a component.</p></span></div>'
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'toast-notifications',
                'name' => 'Toast notifications',
                'name_ar' => 'إشعارات منبثقة',
                'tagline' => 'Stacked toasts with an undo action and auto-dismiss.',
                'tagline_ar' => 'إشعارات متراكبة مع إجراء تراجع وإخفاء تلقائي.',
                'summary' => 'Toasts that can be acted on: the undo button is the reason most toasts exist, and the progress line shows how long is left to click it. They stack from the bottom and can be dismissed by hand.',
                'summary_ar' => 'إشعارات منبثقة يمكن التفاعل معها: زر التراجع هو سبب وجود معظم هذه الإشعارات، وخط التقدم يبيّن الوقت المتبقي للنقر عليه. تتراكب من الأسفل ويمكن إغلاقها يدويًا.',
                'accent' => '#059669',
                'tags' => ['toast', 'notifications', 'undo', 'feedback'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Undo action with a visible countdown', 'Toasts stack rather than replacing each other', 'Announced to screen readers through a live region'],
                'features_ar' => ['إجراء تراجع مع عدّ تنازلي ظاهر', 'تتراكب الإشعارات بدل أن يحل بعضها محل بعض', 'يُعلَن عنها لقارئ الشاشة عبر منطقة حية'],
                'height' => 560,
                'max' => 620,
                'css' => "@keyframes slidein{from{transform:translateY(14px);opacity:0}to{transform:none;opacity:1}}\n@keyframes shrink{from{width:100%}to{width:0}}\n.toasts{position:fixed;right:18px;bottom:18px;display:grid;gap:10px;width:min(340px,calc(100% - 36px));z-index:50}\n.toast{background:var(--card);border:1px solid var(--bd);border-radius:13px;box-shadow:var(--sh);overflow:hidden;animation:slidein .22s ease-out}\n.toast .row{padding:13px 14px;gap:11px;align-items:flex-start}\n.toast .txt{flex:1}\n.toast b{display:block;font-size:13.5px}\n.toast p{font-size:12px;color:var(--mut);margin-top:2px}\n.toast .line{height:3px;background:var(--acc);animation:shrink 6s linear forwards}\n.toast .x{border:0;background:none;padding:2px;cursor:pointer;color:var(--mut);display:inline-flex}",
                'js' => "let count=0;\n"
                    . "const holder=document.querySelector('.toasts');\n"
                    . "function toast(title,message,tone){\n"
                    . "  count++;\n"
                    . "  const element=document.createElement('div');\n"
                    . "  element.className='toast';\n"
                    . "  element.innerHTML='<div class=\"row\"><span class=\"mark\"></span><span class=\"txt\"><b></b><p></p></span>'\n"
                    . "    +'<button class=\"btn tiny\" type=\"button\">Undo</button>'\n"
                    . "    +'<button class=\"x\" type=\"button\" aria-label=\"Dismiss\">&times;</button></div><div class=\"line\"></div>';\n"
                    . "  element.querySelector('b').textContent=title;\n"
                    . "  element.querySelector('p').textContent=message;\n"
                    . "  element.querySelector('.mark').style.color=tone;\n"
                    . "  element.querySelector('.x').addEventListener('click',()=>element.remove());\n"
                    . "  element.querySelector('.btn').addEventListener('click',()=>element.remove());\n"
                    . "  holder.appendChild(element);\n"
                    . "  setTimeout(()=>element.remove(),6000);\n"
                    . "}\n"
                    . "document.getElementById('t1').addEventListener('click',()=>toast('Component archived','Users directory table moved to the archive.','#047857'));\n"
                    . "document.getElementById('t2').addEventListener('click',()=>toast('Upload failed','launch-clip.mp4 exceeded the size limit.','#be123c'));\n"
                    . 'toast(\'Draft saved\',\'Your changes were saved automatically.\',\'#047857\');',
                'body' => self::wrap(
                    self::card(
                        self::head('Toasts', 'They stack, count down, and can be undone')
                        . '<div class="pad" style="display:flex;gap:9px;flex-wrap:wrap">'
                        . '<button class="btn pri" type="button" id="t1">' . Kit::icon('check', 15) . 'Show a success toast</button>'
                        . '<button class="btn" type="button" id="t2">' . Kit::icon('alert', 15) . 'Show an error toast</button></div>'
                        . '<div class="ft"><span>Each toast clears itself after six seconds</span><span>Undo cancels the action</span></div>'
                    ),
                    '<div class="toasts" role="region" aria-live="polite" aria-label="Notifications"></div>'
                ),
            ],
            [
                'slug' => 'tabs-underline',
                'name' => 'Tabs',
                'name_ar' => 'تبويبات',
                'tagline' => 'Underline tabs with arrow-key navigation.',
                'tagline_ar' => 'تبويبات بخط سفلي مع تنقل بمفاتيح الأسهم.',
                'summary' => 'Tabs with the ARIA pattern implemented properly: one tab in the tab order, arrow keys move between them, and the panel is associated with its tab. This is the part most tab components skip.',
                'summary_ar' => 'تبويبات تطبّق نمط ARIA كما ينبغي: تبويب واحد فقط في ترتيب التنقل بـ Tab، ومفاتيح الأسهم تنقل بينها، وكل لوحة مرتبطة بتبويبها. وهذا الجزء بالذات تتجاهله معظم مكونات التبويبات.',
                'accent' => '#2563eb',
                'tags' => ['tabs', 'navigation', 'aria', 'keyboard'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Arrow-key navigation following the ARIA tabs pattern', 'One stop in the tab order, not one per tab', 'Badge counts inside the tab labels'],
                'features_ar' => ['تنقل بمفاتيح الأسهم وفق نمط تبويبات ARIA', 'محطة واحدة في ترتيب Tab، لا محطة لكل تبويب', 'عدّادات على شكل شارات داخل عناوين التبويبات'],
                'height' => 520,
                'max' => 680,
                'css' => "[role=tablist]{display:flex;gap:2px;padding:0 20px;border-bottom:1px solid var(--bd);overflow-x:auto}\n[role=tab]{border:0;background:none;padding:14px 14px 12px;font:inherit;font-size:13.5px;font-weight:600;color:var(--mut);cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap;display:inline-flex;align-items:center;gap:8px}\n[role=tab]:hover{color:var(--ink)}\n[role=tab][aria-selected=true]{color:var(--acc);border-bottom-color:var(--acc)}\n[role=tab] .n{background:var(--soft);color:var(--mut);border-radius:999px;padding:1px 7px;font-size:11px;font-weight:700}\n[role=tab][aria-selected=true] .n{background:var(--acc-soft);color:var(--acc)}\n[role=tabpanel]{padding:20px}",
                'js' => "const tabs=[...document.querySelectorAll('[role=tab]')];\n"
                    . "function select(tab){\n"
                    . "  tabs.forEach(item=>{\n"
                    . "    const on=item===tab;\n"
                    . "    item.setAttribute('aria-selected',String(on));\n"
                    . "    item.tabIndex=on?0:-1;\n"
                    . "    document.getElementById(item.getAttribute('aria-controls')).hidden=!on;\n"
                    . "  });\n"
                    . "  tab.focus();\n"
                    . "}\n"
                    . "tabs.forEach((tab,index)=>{\n"
                    . "  tab.addEventListener('click',()=>select(tab));\n"
                    . "  tab.addEventListener('keydown',event=>{\n"
                    . "    if(event.key==='ArrowRight')select(tabs[(index+1)%tabs.length]);\n"
                    . "    if(event.key==='ArrowLeft')select(tabs[(index-1+tabs.length)%tabs.length]);\n"
                    . "    if(event.key==='Home')select(tabs[0]);\n"
                    . "    if(event.key==='End')select(tabs[tabs.length-1]);\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::card(
                        self::head('Project', 'Aurora Redesign')
                        . '<div role="tablist" aria-label="Project sections">'
                        . '<button role="tab" id="t-over" aria-controls="p-over" aria-selected="true" tabindex="0">Overview</button>'
                        . '<button role="tab" id="t-tasks" aria-controls="p-tasks" aria-selected="false" tabindex="-1">Tasks<span class="n">12</span></button>'
                        . '<button role="tab" id="t-files" aria-controls="p-files" aria-selected="false" tabindex="-1">Files<span class="n">48</span></button>'
                        . '<button role="tab" id="t-team" aria-controls="p-team" aria-selected="false" tabindex="-1">Team</button>'
                        . '</div>'
                        . '<div role="tabpanel" id="p-over" aria-labelledby="t-over">'
                        . '<h3>Overview</h3><p class="sub" style="margin-top:6px">A full rebuild of the marketing site and the component gallery, running to the end of November. Arrow keys move between these tabs.</p></div>'
                        . '<div role="tabpanel" id="p-tasks" aria-labelledby="t-tasks" hidden>'
                        . '<h3>Tasks</h3><p class="sub" style="margin-top:6px">12 open, 8 of them due this week.</p></div>'
                        . '<div role="tabpanel" id="p-files" aria-labelledby="t-files" hidden>'
                        . '<h3>Files</h3><p class="sub" style="margin-top:6px">48 files, 182 MB, last uploaded two hours ago.</p></div>'
                        . '<div role="tabpanel" id="p-team" aria-labelledby="t-team" hidden>'
                        . '<h3>Team</h3><div style="margin-top:10px">' . Kit::avatarStack(['Lina Haddad', 'Omar Saleh', 'Maya Rahman', 'Sara Aziz'], 34) . '</div></div>'
                    )
                ),
            ],
            [
                'slug' => 'accordion-faq',
                'name' => 'Accordion',
                'name_ar' => 'قائمة قابلة للطي',
                'tagline' => 'Disclosure rows built on details and summary.',
                'tagline_ar' => 'صفوف قابلة للطي مبنية على details وsummary.',
                'summary' => 'An accordion using the native details element, so it is keyboard accessible, findable by in-page search and works before JavaScript loads. The only script is the one that closes siblings, and it is optional.',
                'summary_ar' => 'قائمة قابلة للطي تستخدم عنصر details الأصلي، فهي متاحة من لوحة المفاتيح، ويصل إليها البحث داخل الصفحة، وتعمل قبل تحميل JavaScript. السكربت الوحيد هو الذي يغلق العناصر الأخرى، وهو اختياري.',
                'accent' => '#7c3aed',
                'tags' => ['accordion', 'faq', 'details', 'disclosure'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Native details and summary - works with no JavaScript', 'Optional single-open behaviour', 'Marker rotates without a custom icon swap'],
                'features_ar' => ['عنصرا details وsummary الأصليان، تعمل دون JavaScript', 'خيار فتح عنصر واحد فقط في كل مرة', 'مؤشر يدور دون الحاجة إلى تبديل أيقونة مخصصة'],
                'height' => 600,
                'max' => 680,
                'css' => "details{border-bottom:1px solid var(--bd)}\ndetails:last-of-type{border-bottom:0}\nsummary{list-style:none;cursor:pointer;padding:16px 20px;display:flex;align-items:center;gap:12px;font-weight:650;font-size:14px}\nsummary::-webkit-details-marker{display:none}\nsummary:hover{color:var(--acc)}\nsummary:focus-visible{outline:2px solid var(--acc);outline-offset:-2px}\nsummary .chev{margin-left:auto;color:var(--mut);transition:transform .2s}\ndetails[open] summary .chev{transform:rotate(180deg);color:var(--acc)}\ndetails .answer{padding:0 20px 18px 52px;font-size:13.5px;color:var(--mut);line-height:1.65}",
                'js' => "const all=[...document.querySelectorAll('details')];\n"
                    . "all.forEach(item=>{\n"
                    . "  item.addEventListener('toggle',()=>{\n"
                    . "    if(!item.open)return;\n"
                    . "    all.forEach(other=>{if(other!==item)other.open=false;});\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::card(
                        self::head('Frequently asked', 'Only one answer stays open at a time')
                        . self::faq('file', 'What exactly do I get when I download a component?', 'One HTML file. Styles and any behaviour are inside it, there are no build steps and nothing is fetched from a CDN. Open it in a browser and it works.')
                        . self::faq('card', 'Do I need a licence to use these commercially?', 'No. Everything in the gallery can be used in commercial work, modified beyond recognition and shipped without attribution.')
                        . self::faq('image', 'Why are there no images in the templates?', 'Every picture is drawn as inline SVG or a CSS gradient, so a template never depends on an image host, never shows a broken icon and stays sharp on any screen.')
                        . self::faq('globe', 'Do the components support Arabic and right-to-left?', 'The layouts use logical properties where it matters, so adding dir="rtl" to the html element flips them. Arabic names are carried on every component in the gallery.')
                        . self::faq('refresh', 'How often is the library updated?', 'New components are added most weeks. Existing ones are only changed to fix a bug, so a file you downloaded will not shift under you.')
                    )
                ),
            ],
            [
                'slug' => 'dropdown-menu',
                'name' => 'Dropdown menu',
                'name_ar' => 'قائمة منسدلة',
                'tagline' => 'Action menu with sections, shortcuts and a danger item.',
                'tagline_ar' => 'قائمة إجراءات بأقسام واختصارات وعنصر للإجراء الخطر.',
                'summary' => 'A menu that closes when you click away or press Escape, groups its items, shows keyboard shortcuts beside them, and keeps the destructive action visually separated at the bottom where it cannot be hit by accident.',
                'summary_ar' => 'قائمة تُغلق عند النقر خارجها أو الضغط على Esc، وتجمع عناصرها في أقسام، وتعرض اختصارات لوحة المفاتيح بجانبها، وتُبقي الإجراء الخطر منفصلًا بصريًا في الأسفل حيث لا يُنقر عليه خطأً.',
                'accent' => '#475569',
                'tags' => ['dropdown', 'menu', 'actions', 'shortcuts'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Closes on outside click and on Escape', 'Sections with keyboard shortcut hints', 'Destructive item separated at the bottom'],
                'features_ar' => ['تُغلق عند النقر خارجها وعند الضغط على Esc', 'أقسام مع تلميحات لاختصارات لوحة المفاتيح', 'الإجراء الخطر منفصل في الأسفل'],
                'height' => 540,
                'max' => 560,
                'css' => ".menuwrap{position:relative;display:inline-block}\n.menu{position:absolute;top:calc(100% + 7px);left:0;min-width:238px;background:var(--card);border:1px solid var(--bd);border-radius:13px;box-shadow:var(--sh);padding:6px;z-index:20}\n.menu[hidden]{display:none}\n.menu button{display:flex;align-items:center;gap:10px;width:100%;border:0;background:none;padding:9px 10px;border-radius:9px;font:inherit;font-size:13.5px;color:var(--ink);cursor:pointer;text-align:left}\n.menu button:hover{background:var(--soft)}\n.menu button kbd{margin-left:auto;font-size:11px;color:var(--mut);font-family:inherit;background:var(--soft);border:1px solid var(--bd);border-radius:5px;padding:1px 5px}\n.menu .sep{height:1px;background:var(--bd);margin:6px 4px}\n.menu .lbl{font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700;padding:8px 10px 4px}\n.menu .danger{color:var(--bad)}\n.menu .danger:hover{background:var(--bad-bg)}",
                'js' => "const trigger=document.getElementById('trigger');\n"
                    . "const menu=document.getElementById('menu');\n"
                    . "function close(){menu.hidden=true;trigger.setAttribute('aria-expanded','false');}\n"
                    . "trigger.addEventListener('click',event=>{\n"
                    . "  event.stopPropagation();\n"
                    . "  menu.hidden=!menu.hidden;\n"
                    . "  trigger.setAttribute('aria-expanded',String(!menu.hidden));\n"
                    . "});\n"
                    . "document.addEventListener('click',close);\n"
                    . "document.addEventListener('keydown',event=>{if(event.key==='Escape'){close();trigger.focus();}});\n"
                    . "menu.addEventListener('click',event=>event.stopPropagation());",
                'body' => self::wrap(
                    self::card(
                        self::head('Dropdown menu', 'Click away or press Escape to close')
                        . '<div class="pad"><div class="menuwrap">'
                        . '<button class="btn" type="button" id="trigger" aria-haspopup="true" aria-expanded="false" aria-controls="menu">'
                        . Kit::icon('settings', 15) . 'Actions' . Kit::icon('chevron-down', 14) . '</button>'
                        . '<div class="menu" id="menu" role="menu" hidden>'
                        . '<div class="lbl">Edit</div>'
                        . '<button role="menuitem" type="button">' . Kit::icon('edit', 16) . 'Rename<kbd>F2</kbd></button>'
                        . '<button role="menuitem" type="button">' . Kit::icon('copy', 16) . 'Duplicate<kbd>Ctrl D</kbd></button>'
                        . '<button role="menuitem" type="button">' . Kit::icon('folder', 16) . 'Move to…</button>'
                        . '<div class="sep"></div>'
                        . '<div class="lbl">Share</div>'
                        . '<button role="menuitem" type="button">' . Kit::icon('link', 16) . 'Copy link<kbd>Ctrl L</kbd></button>'
                        . '<button role="menuitem" type="button">' . Kit::icon('download', 16) . 'Download</button>'
                        . '<div class="sep"></div>'
                        . '<button role="menuitem" type="button" class="danger">' . Kit::icon('trash', 16) . 'Delete<kbd>Del</kbd></button>'
                        . '</div></div>'
                        . '<p class="hint" style="margin-top:14px">The menu is a sibling of its trigger, so it inherits the theme and needs no portal.</p></div>'
                    )
                ),
            ],
            [
                'slug' => 'breadcrumbs-nav',
                'name' => 'Breadcrumbs',
                'name_ar' => 'مسار التنقل',
                'tagline' => 'Path navigation that collapses in the middle when long.',
                'tagline_ar' => 'مسار تنقل ينطوي من المنتصف حين يطول.',
                'summary' => 'Breadcrumbs that survive a deep path: the middle collapses into an overflow button rather than wrapping to three lines or scrolling sideways, and the current page is marked with aria-current rather than just being unlinked.',
                'summary_ar' => 'مسار تنقل يصمد أمام المسارات العميقة: ينطوي منتصفه في زر للعناصر الزائدة بدل أن يمتد على ثلاثة أسطر أو يتمرر أفقيًا، وتُميَّز الصفحة الحالية بـ aria-current لا بمجرد إزالة الرابط منها.',
                'accent' => '#0891b2',
                'tags' => ['breadcrumbs', 'navigation', 'path', 'overflow'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Middle of a long path collapses into an overflow control', 'Current page marked with aria-current', 'Three densities on one sheet'],
                'features_ar' => ['منتصف المسار الطويل ينطوي في عنصر تحكم للعناصر الزائدة', 'الصفحة الحالية مميَّزة بـ aria-current', 'ثلاث كثافات في لوحة واحدة'],
                'height' => 480,
                'max' => 680,
                'css' => self::groupCss() . "\n.crumbs{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:13px}\n.crumbs a{color:var(--mut);display:inline-flex;align-items:center;gap:6px}\n.crumbs a:hover{color:var(--acc)}\n.crumbs [aria-current]{color:var(--ink);font-weight:650}\n.crumbs .sep{color:var(--faint);display:inline-flex}\n.crumbs .more{border:1px solid var(--bd);background:var(--card);border-radius:7px;padding:1px 7px;cursor:pointer;color:var(--mut);font:inherit;font-size:12px}\n.crumbs .more:hover{border-color:var(--acc);color:var(--acc)}",
                'body' => self::wrap(
                    self::card(
                        self::head('Breadcrumbs', 'Three variants of the same path')
                        . self::group('With a home icon', '<nav class="crumbs" aria-label="Breadcrumb">'
                            . '<a href="#">' . Kit::icon('home', 15) . 'Home</a><span class="sep">' . Kit::icon('chevron-right', 13) . '</span>'
                            . '<a href="#">Components</a><span class="sep">' . Kit::icon('chevron-right', 13) . '</span>'
                            . '<a href="#">Tables</a><span class="sep">' . Kit::icon('chevron-right', 13) . '</span>'
                            . '<span aria-current="page">Users directory table</span></nav>')
                        . self::group('Collapsed middle', '<nav class="crumbs" aria-label="Breadcrumb">'
                            . '<a href="#">Workspace</a><span class="sep">' . Kit::icon('chevron-right', 13) . '</span>'
                            . '<button class="more" type="button" aria-label="Show 3 hidden levels">…</button>'
                            . '<span class="sep">' . Kit::icon('chevron-right', 13) . '</span>'
                            . '<a href="#">2026</a><span class="sep">' . Kit::icon('chevron-right', 13) . '</span>'
                            . '<span aria-current="page">brand-guide.pdf</span></nav>', 'Use this once a path runs past four levels, rather than letting it wrap.')
                        . self::group('Slash separators', '<nav class="crumbs" aria-label="Breadcrumb" style="font-size:12.5px">'
                            . '<a href="#">api</a><span class="sep">/</span><a href="#">app</a><span class="sep">/</span>'
                            . '<a href="#">Modules</a><span class="sep">/</span><a href="#">Component</a><span class="sep">/</span>'
                            . '<span aria-current="page">ComponentKit.php</span></nav>')
                    )
                ),
            ],
            [
                'slug' => 'pagination-controls',
                'name' => 'Pagination',
                'name_ar' => 'ترقيم الصفحات',
                'tagline' => 'Numbered pages with ellipsis, plus two lighter variants.',
                'tagline_ar' => 'صفحات مرقَّمة مع علامات حذف، ونوعان أخف.',
                'summary' => 'Three ways to page through a list, from a full numbered control to a simple previous and next pair. The numbered one keeps the first, last and neighbouring pages and elides the rest, which is the only version that scales to 400 pages.',
                'summary_ar' => 'ثلاث طرق للتنقل بين صفحات قائمة، من عنصر ترقيم كامل إلى زوج بسيط للسابق والتالي. يُبقي النوع المرقَّم على الصفحة الأولى والأخيرة والصفحات المجاورة ويختصر الباقي، وهو الوحيد الذي يصلح لـ 400 صفحة.',
                'accent' => '#2563eb',
                'tags' => ['pagination', 'navigation', 'lists', 'variants'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Ellipsis that keeps first, last and current neighbours', 'Rows-per-page select alongside the pager', 'Simple and load-more variants included'],
                'features_ar' => ['علامات حذف تُبقي على الأولى والأخيرة وجيران الصفحة الحالية', 'قائمة لاختيار عدد الصفوف في الصفحة بجانب أداة الترقيم', 'يتضمن نوعًا بسيطًا ونوع «تحميل المزيد»'],
                'height' => 520,
                'max' => 720,
                'css' => self::groupCss() . "\n.pg{display:flex;align-items:center;gap:5px;flex-wrap:wrap}\n.pg button{min-width:34px;height:34px;padding:0 9px;border:1px solid var(--bd);border-radius:9px;background:var(--card);font:inherit;font-size:13px;font-weight:600;color:var(--ink);cursor:pointer;font-variant-numeric:tabular-nums}\n.pg button:hover:not([disabled]):not([aria-current]){border-color:var(--acc);color:var(--acc)}\n.pg button[aria-current]{background:var(--acc);border-color:var(--acc);color:#fff}\n.pg button[disabled]{opacity:.4;cursor:not-allowed}\n.pg .gap{color:var(--faint);padding:0 4px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Pagination', 'Pick the lightest one the list can live with')
                        . self::group('Numbered', '<nav class="pg" aria-label="Pagination">'
                            . '<button type="button" aria-label="Previous page">' . Kit::icon('chevron-left', 15) . '</button>'
                            . '<button type="button">1</button><span class="gap">…</span>'
                            . '<button type="button">7</button><button type="button" aria-current="page">8</button><button type="button">9</button>'
                            . '<span class="gap">…</span><button type="button">412</button>'
                            . '<button type="button" aria-label="Next page">' . Kit::icon('chevron-right', 15) . '</button></nav>')
                        . self::group('With rows per page', '<div class="row" style="justify-content:space-between;width:100%;gap:14px;flex-wrap:wrap">'
                            . '<span class="row" style="gap:9px;font-size:12.5px;color:var(--mut)">Rows per page'
                            . '<select class="in" aria-label="Rows per page" style="width:auto;padding:6px 30px 6px 10px;font-size:12.5px"><option>10</option><option selected>25</option><option>50</option><option>100</option></select></span>'
                            . '<span class="row" style="gap:12px;font-size:12.5px;color:var(--mut)">176 - 200 of 10,284'
                            . '<span class="pg"><button type="button" aria-label="Previous page">' . Kit::icon('chevron-left', 15) . '</button>'
                            . '<button type="button" aria-label="Next page">' . Kit::icon('chevron-right', 15) . '</button></span></span></div>')
                        . self::group('Simple', '<nav class="pg" aria-label="Pagination">'
                            . '<button type="button" disabled>' . Kit::icon('chevron-left', 15) . 'Previous</button>'
                            . '<span class="xs mut" style="padding:0 10px">Page 1 of 12</span>'
                            . '<button type="button">Next' . Kit::icon('chevron-right', 15) . '</button></nav>')
                        . self::group('Load more', '<div style="width:100%;text-align:center">'
                            . '<button class="btn" type="button">' . Kit::icon('refresh', 15) . 'Load 25 more</button>'
                            . '<p class="hint">Showing 25 of 412</p></div>', 'Best where the order matters more than the position - a feed rather than a report.')
                    )
                ),
            ],
            [
                'slug' => 'badges-and-chips',
                'name' => 'Badges and chips',
                'name_ar' => 'الشارات والوسوم',
                'tagline' => 'Status pills, counters, removable chips and dot markers.',
                'tagline_ar' => 'شارات حالة وعدّادات وشارات قابلة للإزالة ونقاط تنبيه.',
                'summary' => 'The whole small-label family in one sheet: status pills that pair a colour with a word, numeric counters, removable chips for filters, and dot markers for a nav item that has something new behind it.',
                'summary_ar' => 'عائلة التسميات الصغيرة كلها في لوحة واحدة: شارات حالة تقرن اللون بكلمة، وعدّادات رقمية، وشارات قابلة للإزالة للمرشحات، ونقاط تنبيه لعنصر تنقل وراءه جديد.',
                'accent' => '#7c3aed',
                'tags' => ['badges', 'chips', 'labels', 'status'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Status pills that never rely on colour alone', 'Removable chips with accessible close buttons', 'Counter badges including a 99+ overflow'],
                'features_ar' => ['شارات حالة لا تعتمد على اللون وحده أبدًا', 'شارات قابلة للإزالة بأزرار إغلاق سهلة الوصول', 'شارات عدّاد تشمل تجاوز الحد بصيغة 99+'],
                'height' => 560,
                'max' => 680,
                'css' => self::groupCss() . "\n.chip{display:inline-flex;align-items:center;gap:7px;padding:5px 7px 5px 11px;border-radius:999px;background:var(--soft);border:1px solid var(--bd);font-size:12.5px;font-weight:600}\n.chip button{border:0;background:none;padding:1px;cursor:pointer;color:var(--mut);display:inline-flex;border-radius:50%}\n.chip button:hover{background:var(--bd);color:var(--ink)}\n.count{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;border-radius:999px;background:var(--bad);color:#fff;font-size:11px;font-weight:700}\n.withdot{position:relative;display:inline-flex}\n.withdot::after{content:'';position:absolute;top:-2px;right:-2px;width:8px;height:8px;border-radius:50%;background:var(--bad);box-shadow:0 0 0 2px var(--card)}",
                'body' => self::wrap(
                    self::card(
                        self::head('Badges and chips', 'Small labels, all the way down')
                        . self::group('Status', Kit::pill('Active', 'ok', true) . Kit::pill('Pending', 'warn', true)
                            . Kit::pill('Failed', 'bad', true) . Kit::pill('Draft', 'neutral', true) . Kit::pill('Beta', 'info'))
                        . self::group('Counters', '<span class="row" style="gap:8px">' . Kit::icon('bell', 20) . '<span class="count">3</span></span>'
                            . '<span class="row" style="gap:8px">' . Kit::icon('mail', 20) . '<span class="count">12</span></span>'
                            . '<span class="row" style="gap:8px">' . Kit::icon('cart', 20) . '<span class="count">99+</span></span>'
                            . '<span class="withdot">' . Kit::icon('user', 20) . '</span>')
                        . self::group('Removable filter chips', '<span class="chip">Tables<button type="button" aria-label="Remove the Tables filter">' . Kit::icon('x', 13) . '</button></span>'
                            . '<span class="chip">Published<button type="button" aria-label="Remove the Published filter">' . Kit::icon('x', 13) . '</button></span>'
                            . '<span class="chip">Updated this month<button type="button" aria-label="Remove the date filter">' . Kit::icon('x', 13) . '</button></span>'
                            . '<button class="btn gh tiny" type="button">Clear all</button>')
                        . self::group('With icons', Kit::pill('Verified', 'ok') . Kit::pill('Locked', 'neutral')
                            . '<span class="pill info">' . Kit::icon('zap', 13) . 'Fast</span>'
                            . '<span class="pill warn">' . Kit::icon('clock', 13) . 'Expires soon</span>')
                        . self::group('Tag colours', implode('', array_map(
                            fn($i) => '<span class="pill" style="background:' . Kit::fade(Kit::SERIES[$i], 0.14) . ';color:' . Kit::SERIES[$i] . '">'
                                . ['design', 'engineering', 'content', 'support', 'finance', 'legal'][$i] . '</span>',
                            range(0, 5)
                        )))
                    )
                ),
            ],
            [
                'slug' => 'avatar-variants',
                'name' => 'Avatars',
                'name_ar' => 'الصور الرمزية',
                'tagline' => 'Initials, sizes, status dots and overflow stacks.',
                'tagline_ar' => 'أحرف أولى وأحجام ونقاط حالة ومجموعات متراكبة.',
                'summary' => 'Avatars drawn from initials rather than fetched, with a deterministic colour per name so the same person is always the same colour. Stacks cap at four and count the rest, which is what keeps a row of twelve from becoming a mess.',
                'summary_ar' => 'صور رمزية مرسومة من الأحرف الأولى بدل جلبها من الخادم، بلون ثابت لكل اسم فيظهر الشخص نفسه باللون نفسه دائمًا. تتوقف المجموعات المتراكبة عند أربع صور وتعرض عدد الباقي، فلا يتحول صف من اثني عشر شخصًا إلى فوضى.',
                'accent' => '#4f46e5',
                'tags' => ['avatar', 'initials', 'presence', 'stack'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Deterministic colour per name, no image requests', 'Presence dots for online, away and offline', 'Stacks that cap and count the overflow'],
                'features_ar' => ['لون ثابت لكل اسم، دون طلبات صور', 'نقاط حضور لحالات متصل وغائب وغير متصل', 'مجموعات تتوقف عند حد وتعرض عدد الباقي'],
                'height' => 540,
                'max' => 680,
                'css' => self::groupCss() . "\n.pres{position:relative;display:inline-flex}\n.pres i{position:absolute;right:0;bottom:0;width:11px;height:11px;border-radius:50%;box-shadow:0 0 0 2px var(--card)}\n.on{background:var(--ok)}\n.away{background:var(--warn)}\n.off{background:var(--faint)}\n.more{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:var(--soft);color:var(--mut);font-size:11.5px;font-weight:700;margin-left:-8px;box-shadow:0 0 0 2px var(--card)}",
                'body' => self::wrap(
                    self::card(
                        self::head('Avatars', 'Drawn, never downloaded')
                        . self::group('Sizes', Kit::avatar('Lina Haddad', 24) . Kit::avatar('Lina Haddad', 32)
                            . Kit::avatar('Lina Haddad', 40) . Kit::avatar('Lina Haddad', 52) . Kit::avatar('Lina Haddad', 64))
                        . self::group('Different people', Kit::avatar('Omar Saleh', 44) . Kit::avatar('Maya Rahman', 44)
                            . Kit::avatar('Sara Aziz', 44) . Kit::avatar('Karim Nasser', 44) . Kit::avatar('Nour Sabbagh', 44),
                            'The colour comes from the name, so a person keeps the same one everywhere.')
                        . self::group('Presence', '<span class="pres">' . Kit::avatar('Omar Saleh', 44) . '<i class="on" title="Online"></i></span>'
                            . '<span class="pres">' . Kit::avatar('Maya Rahman', 44) . '<i class="away" title="Away"></i></span>'
                            . '<span class="pres">' . Kit::avatar('Karim Nasser', 44) . '<i class="off" title="Offline"></i></span>')
                        . self::group('Stack with overflow', '<span class="row" style="gap:0">'
                            . Kit::avatarStack(['Lina Haddad', 'Omar Saleh', 'Maya Rahman', 'Sara Aziz'], 34)
                            . '<span class="more">+8</span></span>')
                        . self::group('With a name beside it', '<span class="row">' . Kit::avatar('Lina Haddad', 40)
                            . '<span><span class="bold" style="display:block">Lina Haddad</span>'
                            . '<span class="xs mut">Head of design · Beirut</span></span></span>')
                    )
                ),
            ],
            [
                'slug' => 'toggle-switches',
                'name' => 'Toggles and checkboxes',
                'name_ar' => 'المفاتيح ومربعات الاختيار',
                'tagline' => 'Switches, checkboxes and radios in every state.',
                'tagline_ar' => 'مفاتيح تبديل ومربعات اختيار وأزرار اختيار بكل حالاتها.',
                'summary' => 'All three selection controls, including the states that matter and are usually missing: indeterminate on a checkbox, disabled-but-on, and a switch with a description that explains what turning it on actually does.',
                'summary_ar' => 'عناصر الاختيار الثلاثة، بما فيها الحالات المهمة التي تغيب عادةً: الحالة غير المحددة لمربع الاختيار، والمفعَّل المعطَّل، ومفتاح تبديل مع وصف يوضح ما يفعله تشغيله فعلًا.',
                'accent' => '#059669',
                'tags' => ['toggle', 'switch', 'checkbox', 'radio'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Indeterminate checkbox state included', 'Switches with explanatory descriptions', 'Disabled variants of every control'],
                'features_ar' => ['يتضمن الحالة غير المحددة لمربع الاختيار', 'مفاتيح تبديل مع أوصاف توضيحية', 'نسخ معطَّلة من كل عنصر تحكم'],
                'height' => 640,
                'max' => 620,
                'css' => self::groupCss() . "\n.sw{position:relative;display:inline-block;width:42px;height:24px;flex:none}\n.sw input{opacity:0;width:0;height:0}\n.sw i{position:absolute;inset:0;border-radius:999px;background:var(--bd);transition:.2s;cursor:pointer}\n.sw i::before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.25)}\n.sw input:checked+i{background:var(--acc)}\n.sw input:checked+i::before{transform:translateX(18px)}\n.sw input:disabled+i{opacity:.45;cursor:not-allowed}\n.sw input:focus-visible+i{outline:2px solid var(--acc);outline-offset:2px}\n.opt{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid var(--bd);width:100%}\n.opt:last-child{border-bottom:0}\n.opt b{display:block;font-size:13.5px}\n.opt span.d{font-size:11.5px;color:var(--mut)}\ninput[type=checkbox],input[type=radio]{width:17px;height:17px;accent-color:var(--acc)}\nlabel.inline{display:inline-flex;align-items:center;gap:8px;font-size:13.5px;cursor:pointer}",
                'js' => "const partial=document.getElementById('partial');\npartial.indeterminate=true;",
                'body' => self::wrap(
                    self::card(
                        self::head('Selection controls', 'Switch, checkbox, radio')
                        . self::group('Switches', '<div style="width:100%">'
                            . '<div class="opt"><span><b>Two-factor authentication</b><span class="d">Ask for a code from your authenticator app at every sign-in</span></span>'
                            . '<span class="sw"><input type="checkbox" checked aria-label="Two-factor authentication"><i></i></span></div>'
                            . '<div class="opt"><span><b>Weekly digest</b><span class="d">A summary of workspace activity every Monday</span></span>'
                            . '<span class="sw"><input type="checkbox" aria-label="Weekly digest"><i></i></span></div>'
                            . '<div class="opt"><span><b>Beta features</b><span class="d">Locked by your workspace administrator</span></span>'
                            . '<span class="sw"><input type="checkbox" checked disabled aria-label="Beta features"><i></i></span></div>'
                            . '</div>')
                        . self::group('Checkboxes', '<label class="inline"><input type="checkbox" checked>Checked</label>'
                            . '<label class="inline"><input type="checkbox">Unchecked</label>'
                            . '<label class="inline"><input type="checkbox" id="partial">Indeterminate</label>'
                            . '<label class="inline" style="opacity:.5"><input type="checkbox" checked disabled>Disabled</label>',
                            'Indeterminate is set in JavaScript - there is no HTML attribute for it.')
                        . self::group('Radios', '<label class="inline"><input type="radio" name="plan" checked>Monthly</label>'
                            . '<label class="inline"><input type="radio" name="plan">Yearly</label>'
                            . '<label class="inline" style="opacity:.5"><input type="radio" name="plan" disabled>Lifetime</label>')
                    )
                ),
            ],
            [
                'slug' => 'range-slider',
                'name' => 'Range sliders',
                'name_ar' => 'شرائح التمرير',
                'tagline' => 'Single, stepped and dual-handle price ranges.',
                'tagline_ar' => 'شرائح مفردة ومتدرجة ونطاقات أسعار بمقبضين.',
                'summary' => 'Sliders with their value always visible, because a slider with no readout is a control you cannot set precisely. The dual-handle price range is built from two inputs rather than a custom drag implementation.',
                'summary_ar' => 'شرائح تمرير تظهر قيمتها دائمًا، فالشريحة التي لا تعرض قيمتها لا يمكن ضبطها بدقة. نطاق الأسعار ذو المقبضين مبني من حقلي إدخال بدل تنفيذ مخصص للسحب.',
                'accent' => '#0891b2',
                'tags' => ['slider', 'range', 'input', 'filters'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Value readout beside every slider', 'Stepped slider with tick labels', 'Dual-handle range built from two native inputs'],
                'features_ar' => ['عرض للقيمة بجانب كل شريحة', 'شريحة متدرجة بتسميات للعلامات', 'نطاق بمقبضين مبني من حقلي إدخال أصليين'],
                'height' => 600,
                'max' => 560,
                'css' => self::groupCss() . "\ninput[type=range]{width:100%;accent-color:var(--acc);height:22px}\n.val{font-variant-numeric:tabular-nums;font-weight:700;color:var(--acc);font-size:13px}\n.ticks{display:flex;justify-content:space-between;font-size:11px;color:var(--mut);margin-top:4px}\n.dual{position:relative;height:34px}\n.dual input{position:absolute;left:0;width:100%;pointer-events:none;background:none}\n.dual input::-webkit-slider-thumb{pointer-events:auto}\n.dual input::-moz-range-thumb{pointer-events:auto}\n.gbody{display:block}",
                'js' => "document.getElementById('one').addEventListener('input',event=>{\n"
                    . "  document.getElementById('onev').textContent=event.target.value+'%';\n"
                    . "});\n"
                    . "document.getElementById('step').addEventListener('input',event=>{\n"
                    . "  document.getElementById('stepv').textContent=event.target.value+' px';\n"
                    . "});\n"
                    . "const low=document.getElementById('low');\n"
                    . "const high=document.getElementById('high');\n"
                    . "function sync(){\n"
                    . "  if(Number(low.value)>Number(high.value)-10){low.value=Number(high.value)-10;}\n"
                    . "  document.getElementById('dualv').textContent='\$'+low.value+' - \$'+high.value;\n"
                    . "}\n"
                    . "low.addEventListener('input',sync);\nhigh.addEventListener('input',sync);\nsync();",
                'body' => self::wrap(
                    self::card(
                        self::head('Sliders', 'Always paired with a readout')
                        . self::group('Single value', '<div class="row" style="justify-content:space-between"><label for="one" class="sm">Opacity</label><span class="val" id="onev">72%</span></div>'
                            . '<input type="range" id="one" min="0" max="100" value="72">')
                        . self::group('Stepped', '<div class="row" style="justify-content:space-between"><label for="step" class="sm">Corner radius</label><span class="val" id="stepv">14 px</span></div>'
                            . '<input type="range" id="step" min="0" max="32" step="4" value="14">'
                            . '<div class="ticks"><span>0</span><span>8</span><span>16</span><span>24</span><span>32</span></div>')
                        . self::group('Price range', '<div class="row" style="justify-content:space-between"><span class="sm">Price</span><span class="val" id="dualv">$40 - $240</span></div>'
                            . '<div class="dual"><input type="range" id="low" min="0" max="500" value="40" aria-label="Minimum price">'
                            . '<input type="range" id="high" min="0" max="500" value="240" aria-label="Maximum price"></div>'
                            . '<div class="ticks"><span>$0</span><span>$500</span></div>',
                            'Two overlaid native inputs - keyboard accessible, and the handles cannot cross.')
                    )
                ),
            ],
            [
                'slug' => 'progress-stepper',
                'name' => 'Progress stepper',
                'name_ar' => 'مؤشر الخطوات',
                'tagline' => 'Horizontal and vertical steppers with a failed state.',
                'tagline_ar' => 'مؤشرات خطوات أفقية وعمودية مع حالة الفشل.',
                'summary' => 'Steppers including the state every example leaves out: a step that failed. Without it, a checkout or a deploy that goes wrong has nowhere to say so, and the user is left looking at a spinner that never resolves.',
                'summary_ar' => 'مؤشرات خطوات تشمل الحالة التي تغفلها كل الأمثلة: خطوة فشلت. من دونها لا تجد عملية دفع أو نشر متعثرة مكانًا لتقول ذلك، ويبقى المستخدم أمام مؤشر تحميل لا ينتهي.',
                'accent' => '#7c3aed',
                'tags' => ['stepper', 'progress', 'wizard', 'states'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Done, current, upcoming and failed states', 'Horizontal and vertical layouts from one pattern', 'Descriptions under each step'],
                'features_ar' => ['حالات المكتملة والحالية والقادمة والفاشلة', 'تخطيط أفقي وعمودي من نمط واحد', 'وصف تحت كل خطوة'],
                'height' => 640,
                'max' => 720,
                'css' => self::groupCss() . "\n.steps{display:flex;width:100%}\n.steps li{list-style:none;flex:1;display:flex;align-items:center;gap:10px;font-size:12.5px;color:var(--mut);min-width:0}\n.steps li::after{content:'';flex:1;height:2px;background:var(--bd);margin:0 10px;min-width:12px}\n.steps li:last-child::after{display:none}\n.dot{width:28px;height:28px;border-radius:50%;background:var(--soft);border:1px solid var(--bd);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex:none}\n.done .dot{background:var(--ok-bg);border-color:var(--ok);color:var(--ok)}\n.done::after{background:var(--ok)}\n.now{color:var(--ink);font-weight:650}\n.now .dot{background:var(--acc);border-color:var(--acc);color:#fff}\n.fail .dot{background:var(--bad-bg);border-color:var(--bad);color:var(--bad)}\n.fail{color:var(--bad);font-weight:650}\n.vsteps{display:grid;gap:0;width:100%}\n.vstep{display:grid;grid-template-columns:28px 1fr;gap:14px;position:relative;padding-bottom:22px}\n.vstep:last-child{padding-bottom:0}\n.vstep::before{content:'';position:absolute;left:13px;top:30px;bottom:-2px;width:2px;background:var(--bd)}\n.vstep:last-child::before{display:none}\n.vstep.done::before{background:var(--ok)}\n.vstep b{display:block;font-size:13.5px}\n.vstep span.d{font-size:11.5px;color:var(--mut)}",
                'body' => self::wrap(
                    self::card(
                        self::head('Steppers', 'Including the state that is usually missing')
                        . self::group('Horizontal', '<ol class="steps">'
                            . '<li class="done"><span class="dot">' . Kit::icon('check', 15, 2.6) . '</span>Basket</li>'
                            . '<li class="done"><span class="dot">' . Kit::icon('check', 15, 2.6) . '</span>Address</li>'
                            . '<li class="now"><span class="dot">3</span>Payment</li>'
                            . '<li><span class="dot">4</span>Confirmation</li></ol>')
                        . self::group('With a failure', '<ol class="steps">'
                            . '<li class="done"><span class="dot">' . Kit::icon('check', 15, 2.6) . '</span>Build</li>'
                            . '<li class="done"><span class="dot">' . Kit::icon('check', 15, 2.6) . '</span>Test</li>'
                            . '<li class="fail"><span class="dot">' . Kit::icon('x', 15, 2.6) . '</span>Deploy</li>'
                            . '<li><span class="dot">4</span>Verify</li></ol>',
                            'A failed step stops the line and says so, instead of spinning for ever.')
                        . self::group('Vertical with detail', '<div class="vsteps">'
                            . '<div class="vstep done"><span class="dot">' . Kit::icon('check', 15, 2.6) . '</span>'
                            . '<span><b>Order placed</b><span class="d">12 September, 09:41</span></span></div>'
                            . '<div class="vstep done"><span class="dot">' . Kit::icon('check', 15, 2.6) . '</span>'
                            . '<span><b>Packed</b><span class="d">12 September, 14:02 · Warehouse A</span></span></div>'
                            . '<div class="vstep"><span class="dot" style="background:var(--acc);border-color:var(--acc);color:#fff">3</span>'
                            . '<span><b>In transit</b><span class="d">Left the Riyadh hub this morning</span></span></div>'
                            . '<div class="vstep"><span class="dot">4</span>'
                            . '<span><b>Delivered</b><span class="d">Expected 14 September</span></span></div>'
                            . '</div>')
                    )
                ),
            ],
            [
                'slug' => 'empty-states',
                'name' => 'Empty states',
                'name_ar' => 'حالات الفراغ',
                'tagline' => 'Nothing yet, no results and something went wrong.',
                'tagline_ar' => 'لا شيء بعد، ولا نتائج، وحدث خطأ ما.',
                'summary' => 'Three different empties that are usually treated as one: a list nobody has filled yet needs an invitation, a search with no matches needs its query back and a way out, and an error needs a retry.',
                'summary_ar' => 'ثلاث حالات فراغ مختلفة تُعامَل عادةً كأنها واحدة: القائمة التي لم يملأها أحد بعد تحتاج إلى دعوة، والبحث بلا نتائج يحتاج إلى إعادة عرض الاستعلام ومخرج، والخطأ يحتاج إلى إعادة المحاولة.',
                'accent' => '#475569',
                'tags' => ['empty-state', 'ux', 'error', 'onboarding'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Three distinct empties, each with the right action', 'Illustration drawn in SVG, no image files', 'Failed state offers a retry rather than an apology'],
                'features_ar' => ['ثلاث حالات فراغ مختلفة، لكل منها الإجراء المناسب', 'رسم توضيحي بـ SVG، دون ملفات صور', 'حالة الفشل تعرض إعادة المحاولة بدل الاعتذار'],
                'height' => 700,
                'max' => 900,
                'css' => ".empty{text-align:center;padding:38px 24px}\n.empty h3{margin-top:16px;font-size:15.5px}\n.empty p{font-size:13px;color:var(--mut);margin-top:7px;max-width:320px;margin-left:auto;margin-right:auto}\n.empty .acts{display:flex;gap:9px;justify-content:center;margin-top:18px;flex-wrap:wrap}\n.art{width:96px;height:72px;margin:0 auto}",
                'body' => self::wrap(
                    self::grid(
                        280,
                        self::card('<div class="empty">'
                            . '<svg class="art" viewBox="0 0 120 90" fill="none" aria-hidden="true">'
                            . '<rect x="16" y="20" width="88" height="58" rx="10" fill="var(--soft)" stroke="var(--bd)" stroke-width="2"/>'
                            . '<path d="M16 36h88" stroke="var(--bd)" stroke-width="2"/>'
                            . '<circle cx="60" cy="58" r="13" stroke="var(--acc)" stroke-width="2.5"/>'
                            . '<path d="M60 52v12M54 58h12" stroke="var(--acc)" stroke-width="2.5" stroke-linecap="round"/></svg>'
                            . '<h3>No components yet</h3>'
                            . '<p>Upload your first single-file template and it will appear here for the whole team.</p>'
                            . '<div class="acts">' . self::btn('Upload a component', 'upload', 'pri') . self::btn('See an example') . '</div></div>'),
                        self::card('<div class="empty">'
                            . '<svg class="art" viewBox="0 0 120 90" fill="none" aria-hidden="true">'
                            . '<circle cx="54" cy="42" r="22" stroke="var(--bd)" stroke-width="2.5"/>'
                            . '<path d="m70 58 14 14" stroke="var(--bd)" stroke-width="2.5" stroke-linecap="round"/>'
                            . '<path d="M46 34l16 16M62 34l-16 16" stroke="var(--mut)" stroke-width="2.5" stroke-linecap="round"/></svg>'
                            . '<h3>No results for &ldquo;kanban&rdquo;</h3>'
                            . '<p>Nothing in the gallery matches that yet. Try a broader word, or clear the category filters.</p>'
                            . '<div class="acts">' . self::btn('Clear filters', 'x') . self::btn('Browse everything', 'grid') . '</div></div>'),
                        self::card('<div class="empty">'
                            . '<svg class="art" viewBox="0 0 120 90" fill="none" aria-hidden="true">'
                            . '<path d="M60 18 96 74H24Z" stroke="var(--bad)" stroke-width="2.5" stroke-linejoin="round" fill="var(--bad-bg)"/>'
                            . '<path d="M60 40v16M60 63h.01" stroke="var(--bad)" stroke-width="3" stroke-linecap="round"/></svg>'
                            . '<h3>We could not load this</h3>'
                            . '<p>The request timed out after 30 seconds. Your work is safe - nothing was lost.</p>'
                            . '<div class="acts">' . self::btn('Try again', 'refresh', 'pri') . self::btn('Contact support') . '</div></div>')
                    )
                ),
            ],
            [
                'slug' => 'loading-skeletons',
                'name' => 'Loading skeletons',
                'name_ar' => 'هياكل التحميل',
                'tagline' => 'Shimmer placeholders shaped like the content they replace.',
                'tagline_ar' => 'عناصر نائبة لامعة بشكل المحتوى الذي تحل محله.',
                'summary' => 'Skeletons only work when they match the shape of what arrives, so these mirror a real card, a table row and a paragraph. The shimmer stops under reduced-motion, where a static block is kinder than a moving one.',
                'summary_ar' => 'لا تنجح هياكل التحميل إلا إذا طابقت شكل ما سيصل، لذا تحاكي هذه الهياكل بطاقة حقيقية وصف جدول وفقرة. يتوقف اللمعان عند تفضيل تقليل الحركة، فالكتلة الثابتة حينها ألطف من المتحركة.',
                'accent' => '#2563eb',
                'tags' => ['skeleton', 'loading', 'placeholder', 'shimmer'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Shapes that match the real content, not grey boxes', 'Shimmer disabled under prefers-reduced-motion', 'Card, row, list and paragraph variants'],
                'features_ar' => ['أشكال تطابق المحتوى الحقيقي، لا مربعات رمادية', 'اللمعان معطَّل مع prefers-reduced-motion', 'أنواع للبطاقة والصف والقائمة والفقرة'],
                'height' => 640,
                'max' => 760,
                'css' => "@keyframes shimmer{100%{background-position:-200% 0}}\n.sk{background:linear-gradient(90deg,var(--soft) 25%,var(--bd) 37%,var(--soft) 63%);background-size:200% 100%;animation:shimmer 1.4s linear infinite;border-radius:7px}\n@media (prefers-reduced-motion:reduce){.sk{animation:none;background:var(--soft)}}\n.skline{height:11px}\n.skdot{border-radius:50%}\n.grp{padding:18px 20px;border-bottom:1px solid var(--bd)}\n.grp:last-child{border-bottom:0}\n.glabel{display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);font-weight:700;margin-bottom:12px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Skeletons', 'Shaped like what is coming')
                        . '<div class="grp"><span class="glabel">Card</span>'
                        . '<div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))">'
                        . str_repeat('<div style="border:1px solid var(--bd);border-radius:13px;padding:14px;display:grid;gap:10px">'
                            . '<div class="sk" style="height:96px;border-radius:10px"></div>'
                            . '<div class="sk skline" style="width:70%"></div>'
                            . '<div class="sk skline" style="width:45%"></div></div>', 3)
                        . '</div></div>'
                        . '<div class="grp"><span class="glabel">Table rows</span>'
                        . '<div style="display:grid;gap:14px">'
                        . str_repeat('<div class="row" style="gap:12px"><div class="sk skdot" style="width:34px;height:34px"></div>'
                            . '<div style="flex:1;display:grid;gap:7px"><div class="sk skline" style="width:38%"></div>'
                            . '<div class="sk skline" style="width:22%;height:9px"></div></div>'
                            . '<div class="sk skline" style="width:64px"></div></div>', 4)
                        . '</div></div>'
                        . '<div class="grp"><span class="glabel">Paragraph</span>'
                        . '<div style="display:grid;gap:9px">'
                        . '<div class="sk skline" style="width:100%"></div><div class="sk skline" style="width:96%"></div>'
                        . '<div class="sk skline" style="width:98%"></div><div class="sk skline" style="width:62%"></div></div></div>'
                    )
                ),
            ],
            [
                'slug' => 'tooltip-popover',
                'name' => 'Tooltips and popovers',
                'name_ar' => 'التلميحات والنوافذ الصغيرة',
                'tagline' => 'Hover tooltips on four sides plus a click popover.',
                'tagline_ar' => 'تلميحات عند التمرير فوقها على أربعة جوانب، ونافذة صغيرة عند النقر.',
                'summary' => 'Tooltips that appear on focus as well as hover, which is what makes them reachable from a keyboard, and a popover for anything with a link or a button inside - content a tooltip can never hold because it vanishes on the way to it.',
                'summary_ar' => 'تلميحات تظهر عند التركيز كما عند التمرير فوقها، وهذا ما يجعلها في متناول لوحة المفاتيح، ونافذة صغيرة لأي محتوى فيه رابط أو زر، وهو محتوى لا يتسع له التلميح لأنه يختفي قبل الوصول إليه.',
                'accent' => '#4f46e5',
                'tags' => ['tooltip', 'popover', 'hover', 'accessibility'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Appears on focus as well as hover', 'Four placements from one class', 'Popover for interactive content, tooltip for text'],
                'features_ar' => ['يظهر عند التركيز كما عند التمرير فوقه', 'أربعة مواضع من صنف CSS واحد', 'نافذة صغيرة للمحتوى التفاعلي، وتلميح للنص'],
                'height' => 560,
                'max' => 620,
                'css' => self::groupCss() . "\n.tip{position:relative;display:inline-flex}\n.tip .bub{position:absolute;z-index:10;background:var(--ink);color:var(--card);font-size:12px;font-weight:500;padding:7px 10px;border-radius:8px;white-space:nowrap;opacity:0;pointer-events:none;transition:opacity .15s}\n.tip:hover .bub,.tip:focus-within .bub{opacity:1}\n.tip.top .bub{bottom:calc(100% + 8px);left:50%;transform:translateX(-50%)}\n.tip.bottom .bub{top:calc(100% + 8px);left:50%;transform:translateX(-50%)}\n.tip.left .bub{right:calc(100% + 8px);top:50%;transform:translateY(-50%)}\n.tip.right .bub{left:calc(100% + 8px);top:50%;transform:translateY(-50%)}\n.pop{position:relative;display:inline-block}\n.popbox{position:absolute;top:calc(100% + 9px);left:0;width:268px;background:var(--card);border:1px solid var(--bd);border-radius:13px;box-shadow:var(--sh);padding:15px;z-index:20}\n.popbox[hidden]{display:none}",
                'js' => "const trigger=document.getElementById('poptrigger');\n"
                    . "const box=document.getElementById('popbox');\n"
                    . "trigger.addEventListener('click',event=>{\n"
                    . "  event.stopPropagation();\n"
                    . "  box.hidden=!box.hidden;\n"
                    . "  trigger.setAttribute('aria-expanded',String(!box.hidden));\n"
                    . "});\n"
                    . "document.addEventListener('click',()=>{box.hidden=true;trigger.setAttribute('aria-expanded','false');});\n"
                    . "box.addEventListener('click',event=>event.stopPropagation());\n"
                    . "document.addEventListener('keydown',event=>{if(event.key==='Escape')box.hidden=true;});",
                'body' => self::wrap(
                    self::card(
                        self::head('Tooltips and popovers', 'Tab to a button to see the tooltip without a mouse')
                        . self::group('Placement', '<span class="tip top"><button class="btn" type="button">Top</button><span class="bub" role="tooltip">Appears above</span></span>'
                            . '<span class="tip bottom"><button class="btn" type="button">Bottom</button><span class="bub" role="tooltip">Appears below</span></span>'
                            . '<span class="tip left"><button class="btn" type="button">Left</button><span class="bub" role="tooltip">To the left</span></span>'
                            . '<span class="tip right"><button class="btn" type="button">Right</button><span class="bub" role="tooltip">To the right</span></span>')
                        . self::group('On an icon', '<span class="tip top"><button class="btn gh" type="button" aria-label="What is this?" style="padding:7px">' . Kit::icon('info', 18) . '</button>'
                            . '<span class="bub" role="tooltip">Monthly recurring revenue</span></span>'
                            . '<span class="tip top"><button class="btn gh" type="button" aria-label="Copy" style="padding:7px">' . Kit::icon('copy', 18) . '</button>'
                            . '<span class="bub" role="tooltip">Copy to clipboard</span></span>')
                        . self::group('Popover', '<span class="pop"><button class="btn" type="button" id="poptrigger" aria-expanded="false" aria-controls="popbox">'
                            . Kit::icon('user', 15) . 'Lina Haddad</button>'
                            . '<span class="popbox" id="popbox" hidden>'
                            . '<span class="row">' . Kit::avatar('Lina Haddad', 44) . '<span><b style="display:block">Lina Haddad</b>'
                            . '<span class="xs mut">Head of design</span></span></span>'
                            . '<p class="sm mut" style="margin-top:11px">Beirut · joined January 2020 · usually replies within an hour.</p>'
                            . '<span class="row" style="gap:8px;margin-top:12px">' . self::btn('Message', 'mail', 'pri tiny') . self::btn('View profile', '', 'tiny') . '</span>'
                            . '</span></span>', 'Anything with a link or a button inside belongs in a popover, never a tooltip.')
                    )
                ),
            ],
            [
                'slug' => 'segmented-control',
                'name' => 'Segmented control',
                'name_ar' => 'مبدل الخيارات',
                'tagline' => 'Radio-backed segments with an icon-only variant.',
                'tagline_ar' => 'مقاطع مبنية على أزرار الاختيار مع نوع بالأيقونات فقط.',
                'summary' => 'A segmented control built on radio inputs, so it is a real form control - arrow keys move between options and it submits with the form. The sliding indicator is CSS; the behaviour is the browser.',
                'summary_ar' => 'مبدل خيارات مبني على حقول radio، فهو عنصر نموذج حقيقي: مفاتيح الأسهم تنقل بين الخيارات، ويُرسَل مع النموذج. المؤشر المنزلق مصنوع بـ CSS، أما السلوك فيتولاه المتصفح.',
                'accent' => '#0891b2',
                'tags' => ['segmented', 'toggle-group', 'radio', 'switcher'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Radio inputs underneath - keyboard support for free', 'Icon-only variant with accessible labels', 'Three sizes from the same markup'],
                'features_ar' => ['حقول radio في الأساس، ودعم لوحة المفاتيح دون جهد', 'نوع بالأيقونات فقط مع تسميات سهلة الوصول', 'ثلاثة أحجام من الترميز نفسه'],
                'height' => 520,
                'max' => 620,
                'css' => self::groupCss() . "\n.seg{display:inline-flex;padding:3px;background:var(--soft);border:1px solid var(--bd);border-radius:11px}\n.seg input{position:absolute;opacity:0;pointer-events:none}\n.seg label{display:inline-flex;align-items:center;gap:7px;padding:8px 15px;border-radius:8px;font-size:13px;font-weight:650;color:var(--mut);cursor:pointer;transition:.16s;white-space:nowrap}\n.seg label:hover{color:var(--ink)}\n.seg input:checked+label{background:var(--card);color:var(--acc);box-shadow:0 1px 3px rgba(16,24,40,.12)}\n.seg input:focus-visible+label{outline:2px solid var(--acc);outline-offset:1px}\n.seg.sm label{padding:6px 11px;font-size:12px}\n.seg.icons label{padding:8px 10px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Segmented control', 'Radio inputs in a coat of paint')
                        . self::group('Text', '<span class="seg">'
                            . '<input type="radio" name="period" id="p1" checked><label for="p1">Day</label>'
                            . '<input type="radio" name="period" id="p2"><label for="p2">Week</label>'
                            . '<input type="radio" name="period" id="p3"><label for="p3">Month</label>'
                            . '<input type="radio" name="period" id="p4"><label for="p4">Year</label></span>')
                        . self::group('With icons', '<span class="seg">'
                            . '<input type="radio" name="view" id="v1" checked><label for="v1">' . Kit::icon('grid', 15) . 'Grid</label>'
                            . '<input type="radio" name="view" id="v2"><label for="v2">' . Kit::icon('list', 15) . 'List</label>'
                            . '<input type="radio" name="view" id="v3"><label for="v3">' . Kit::icon('chart', 15) . 'Chart</label></span>')
                        . self::group('Icon only', '<span class="seg icons">'
                            . '<input type="radio" name="align" id="a1" checked><label for="a1" aria-label="Align left">' . Kit::icon('list', 16) . '</label>'
                            . '<input type="radio" name="align" id="a2"><label for="a2" aria-label="Align centre">' . Kit::icon('menu', 16) . '</label>'
                            . '<input type="radio" name="align" id="a3"><label for="a3" aria-label="Align right">' . Kit::icon('list', 16) . '</label></span>'
                            . '<span class="seg icons">'
                            . '<input type="radio" name="theme" id="th1" checked><label for="th1" aria-label="Light theme">' . Kit::icon('sun', 16) . '</label>'
                            . '<input type="radio" name="theme" id="th2"><label for="th2" aria-label="Dark theme">' . Kit::icon('moon', 16) . '</label></span>')
                        . self::group('Small', '<span class="seg sm">'
                            . '<input type="radio" name="size" id="s1" checked><label for="s1">All</label>'
                            . '<input type="radio" name="size" id="s2"><label for="s2">Published</label>'
                            . '<input type="radio" name="size" id="s3"><label for="s3">Drafts</label></span>')
                    )
                ),
            ],
        ];
    }

    /** One accordion row, used by the FAQ component. */
    private static function faq(string $icon, string $question, string $answer): string
    {
        return '<details><summary>' . Kit::iconTile($icon, 'var(--acc)', 30) . $question
            . '<span class="chev">' . Kit::icon('chevron-down', 17) . '</span></summary>'
            . '<div class="answer">' . $answer . '</div></details>';
    }

    /** @return array<int,array<string,mixed>> */
    private static function setTwo(): array
    {
        return [
            [
                'slug' => 'responsive-navbar',
                'name' => 'Responsive navbar',
                'name_ar' => 'شريط تنقل متجاوب',
                'tagline' => 'Desktop bar that becomes a burger menu on a phone.',
                'tagline_ar' => 'شريط لسطح المكتب يتحول إلى قائمة همبرغر على الهاتف.',
                'summary' => 'One navigation bar with two layouts: links inline above 760px, a disclosure menu below it. The burger is a real button with an expanded state, so the menu is announced rather than silently appearing.',
                'summary_ar' => 'شريط تنقل واحد بتخطيطين: روابط في سطر واحد فوق 760px، وقائمة قابلة للطي تحته. زر الهمبرغر زر حقيقي بحالة توسّع، فيُعلَن عن القائمة بدل أن تظهر بصمت.',
                'accent' => '#2563eb',
                'tags' => ['navbar', 'navigation', 'responsive', 'header'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['One markup, two layouts at a single breakpoint', 'Burger button with aria-expanded', 'Active link marked with aria-current'],
                'features_ar' => ['ترميز واحد وتخطيطان عند نقطة تحول واحدة', 'زر همبرغر مع aria-expanded', 'الرابط النشط مميَّز بـ aria-current'],
                'height' => 480,
                'max' => 900,
                'css' => ".nav{display:flex;align-items:center;gap:20px;padding:13px 20px}\n.brand{display:flex;align-items:center;gap:9px;font-weight:750;font-size:15px}\n.mark{width:30px;height:30px;border-radius:9px;background:linear-gradient(140deg,var(--acc),var(--acc-dk));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px}\n.links{display:flex;gap:4px;margin-left:8px}\n.links a{padding:8px 12px;border-radius:9px;font-size:13.5px;font-weight:600;color:var(--mut)}\n.links a:hover{background:var(--soft);color:var(--ink);text-decoration:none}\n.links a[aria-current]{color:var(--acc);background:var(--acc-soft)}\n.navacts{margin-left:auto;display:flex;align-items:center;gap:9px}\n.burger{display:none;border:1px solid var(--bd);background:var(--card);border-radius:9px;padding:8px;cursor:pointer;color:var(--ink)}\n.drawer{display:none;padding:8px 14px 16px;border-top:1px solid var(--bd)}\n.drawer a{display:block;padding:11px 10px;border-radius:9px;font-size:14px;font-weight:600;color:var(--ink)}\n.drawer a:hover{background:var(--soft);text-decoration:none}\n@media (max-width:760px){.links{display:none}.burger{display:inline-flex}.navacts .btn.pri{display:none}.drawer.open{display:block}}",
                'js' => "const burger=document.querySelector('.burger');\n"
                    . "const drawer=document.querySelector('.drawer');\n"
                    . "burger.addEventListener('click',()=>{\n"
                    . "  const open=drawer.classList.toggle('open');\n"
                    . "  burger.setAttribute('aria-expanded',String(open));\n"
                    . '});',
                'body' => self::wrap(
                    self::card(
                        '<div class="nav">'
                        . '<span class="brand"><span class="mark">F</span>Frugal</span>'
                        . '<nav class="links" aria-label="Main">'
                        . '<a href="#" aria-current="page">Components</a><a href="#">Drawing</a><a href="#">Icons</a><a href="#">Convert</a><a href="#">Pricing</a></nav>'
                        . '<span class="navacts">'
                        . '<button class="btn gh" type="button" aria-label="Search" style="padding:8px">' . Kit::icon('search', 18) . '</button>'
                        . self::btn('Sign in', '', 'pri')
                        . '<button class="burger" type="button" aria-expanded="false" aria-label="Open the menu">' . Kit::icon('menu', 19) . '</button>'
                        . '</span></div>'
                        . '<div class="drawer">'
                        . '<a href="#" aria-current="page">Components</a><a href="#">Drawing</a><a href="#">Icons</a><a href="#">Convert</a><a href="#">Pricing</a>'
                        . '<div style="margin-top:10px">' . self::btn('Sign in', '', 'pri') . '</div></div>'
                    ),
                    '<p class="sub" style="margin-top:14px;text-align:center">Narrow the frame below 760px to see the menu collapse.</p>'
                ),
            ],
            [
                'slug' => 'sidebar-navigation',
                'name' => 'Sidebar navigation',
                'name_ar' => 'قائمة جانبية',
                'tagline' => 'Sectioned sidebar with counts, active state and a footer.',
                'tagline_ar' => 'شريط جانبي بأقسام وعدّادات وحالة نشطة وتذييل.',
                'summary' => 'The left-hand rail of an admin: grouped sections, counts where they help, a clearly marked current page, and the account block pinned at the bottom where every product eventually puts it.',
                'summary_ar' => 'العمود الأيسر في لوحة الإدارة: أقسام مجمَّعة، وعدّادات حيث تفيد، وصفحة حالية مميَّزة بوضوح، وكتلة الحساب مثبتة في الأسفل حيث تضعها كل المنتجات في النهاية.',
                'accent' => '#4f46e5',
                'tags' => ['sidebar', 'navigation', 'admin', 'layout'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Sections with counts on the items that need them', 'Current item marked with aria-current', 'Account block pinned to the bottom'],
                'features_ar' => ['أقسام مع عدّادات على العناصر التي تحتاجها', 'العنصر الحالي مميَّز بـ aria-current', 'كتلة الحساب مثبتة في الأسفل'],
                'height' => 640,
                'max' => 320,
                'css' => ".side{display:flex;flex-direction:column;height:560px}\n.side .top{padding:16px}\n.brand{display:flex;align-items:center;gap:9px;font-weight:750;font-size:15px}\n.mark{width:30px;height:30px;border-radius:9px;background:linear-gradient(140deg,var(--acc),var(--acc-dk));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px}\n.side nav{flex:1;overflow:auto;padding:0 10px}\n.side .sec{font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);font-weight:700;padding:14px 10px 6px}\n.side a{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:9px;font-size:13.5px;font-weight:600;color:var(--mut)}\n.side a:hover{background:var(--soft);color:var(--ink);text-decoration:none}\n.side a[aria-current]{background:var(--acc-soft);color:var(--acc)}\n.side a .n{margin-left:auto;font-size:11px;background:var(--soft);color:var(--mut);border-radius:999px;padding:1px 7px;font-weight:700}\n.side a[aria-current] .n{background:var(--acc);color:#fff}\n.side .foot{padding:12px;border-top:1px solid var(--bd);display:flex;align-items:center;gap:10px}",
                'body' => self::wrap(
                    self::card('<div class="side">'
                        . '<div class="top"><span class="brand"><span class="mark">F</span>Frugal admin</span></div>'
                        . '<nav aria-label="Sidebar">'
                        . '<div class="sec">Overview</div>'
                        . '<a href="#" aria-current="page">' . Kit::icon('home', 17) . 'Dashboard</a>'
                        . '<a href="#">' . Kit::icon('chart', 17) . 'Analytics</a>'
                        . '<div class="sec">Content</div>'
                        . '<a href="#">' . Kit::icon('grid', 17) . 'Components<span class="n">216</span></a>'
                        . '<a href="#">' . Kit::icon('edit', 17) . 'Drawings<span class="n">42</span></a>'
                        . '<a href="#">' . Kit::icon('image', 17) . 'Icons</a>'
                        . '<div class="sec">People</div>'
                        . '<a href="#">' . Kit::icon('users', 17) . 'Users<span class="n">1.2k</span></a>'
                        . '<a href="#">' . Kit::icon('shield', 17) . 'Roles</a>'
                        . '<div class="sec">System</div>'
                        . '<a href="#">' . Kit::icon('settings', 17) . 'Settings</a>'
                        . '<a href="#">' . Kit::icon('file', 17) . 'Audit log</a>'
                        . '</nav>'
                        . '<div class="foot">' . Kit::avatar('Lina Haddad', 34)
                        . '<span style="flex:1;min-width:0"><span class="bold" style="display:block;font-size:13px">Lina Haddad</span>'
                        . '<span class="xs mut">Owner</span></span>'
                        . '<button class="btn gh" type="button" aria-label="Account menu" style="padding:6px">' . Kit::icon('more', 16) . '</button></div>'
                        . '</div>')
                ),
            ],
            [
                'slug' => 'command-palette',
                'name' => 'Command palette',
                'name_ar' => 'لوحة الأوامر',
                'tagline' => 'Fuzzy search over actions with keyboard navigation.',
                'tagline_ar' => 'بحث تقريبي في الإجراءات مع تنقل بلوحة المفاتيح.',
                'summary' => 'The Ctrl+K pattern: type to filter, arrow keys to move, Enter to run. Results are grouped by kind and each carries its shortcut, so the palette teaches the shortcuts it is temporarily replacing.',
                'summary_ar' => 'نمط Ctrl+K: اكتب للتصفية، واستخدم الأسهم للتنقل، وEnter للتنفيذ. النتائج مجمَّعة حسب النوع وكل منها يحمل اختصاره، فتعلّمك اللوحة الاختصارات التي تنوب عنها مؤقتًا.',
                'accent' => '#475569',
                'tags' => ['command-palette', 'search', 'keyboard', 'shortcuts'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Filter as you type across grouped results', 'Arrow keys and Enter, with the active row scrolled into view', 'Shortcut hints beside each command'],
                'features_ar' => ['تصفية فورية أثناء الكتابة عبر نتائج مجمَّعة', 'مفاتيح الأسهم وEnter، مع تمرير الصف النشط إلى مجال الرؤية', 'تلميحات الاختصارات بجانب كل أمر'],
                'height' => 620,
                'max' => 560,
                'css' => ".palette{border-radius:16px;overflow:hidden}\n.pinput{display:flex;align-items:center;gap:11px;padding:15px 18px;border-bottom:1px solid var(--bd)}\n.pinput input{flex:1;border:0;background:none;outline:none;font:inherit;font-size:15px;color:var(--ink)}\n.plist{max-height:320px;overflow:auto;padding:8px}\n.psec{font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);font-weight:700;padding:10px 10px 5px}\n.pitem{display:flex;align-items:center;gap:11px;width:100%;border:0;background:none;padding:10px;border-radius:9px;font:inherit;font-size:13.5px;color:var(--ink);cursor:pointer;text-align:left}\n.pitem kbd{margin-left:auto;font-size:11px;color:var(--mut);font-family:inherit;background:var(--soft);border:1px solid var(--bd);border-radius:5px;padding:1px 6px}\n.pitem.on{background:var(--acc-soft);color:var(--acc)}\n.pitem.on kbd{background:var(--card)}\n.pfoot{display:flex;gap:16px;padding:11px 18px;border-top:1px solid var(--bd);font-size:11.5px;color:var(--mut)}\n.pfoot kbd{font-family:inherit;background:var(--soft);border:1px solid var(--bd);border-radius:4px;padding:0 5px}",
                'js' => "const input=document.getElementById('pq');\n"
                    . "const items=()=>[...document.querySelectorAll('.pitem:not([hidden])')];\n"
                    . "let index=0;\n"
                    . "function paint(){\n"
                    . "  items().forEach((item,i)=>item.classList.toggle('on',i===index));\n"
                    . "  const active=items()[index];\n"
                    . "  if(active)active.scrollIntoView({block:'nearest'});\n"
                    . "}\n"
                    . "input.addEventListener('input',()=>{\n"
                    . "  const term=input.value.trim().toLowerCase();\n"
                    . "  document.querySelectorAll('.pitem').forEach(item=>{\n"
                    . "    item.hidden=term!==''&&!item.dataset.k.includes(term);\n"
                    . "  });\n"
                    . "  document.querySelectorAll('.psec').forEach(section=>{\n"
                    . "    let next=section.nextElementSibling,any=false;\n"
                    . "    while(next&&next.classList.contains('pitem')){if(!next.hidden)any=true;next=next.nextElementSibling;}\n"
                    . "    section.hidden=!any;\n"
                    . "  });\n"
                    . "  index=0;paint();\n"
                    . "});\n"
                    . "input.addEventListener('keydown',event=>{\n"
                    . "  const list=items();\n"
                    . "  if(event.key==='ArrowDown'){event.preventDefault();index=(index+1)%list.length;paint();}\n"
                    . "  if(event.key==='ArrowUp'){event.preventDefault();index=(index-1+list.length)%list.length;paint();}\n"
                    . "  if(event.key==='Enter'&&list[index]){event.preventDefault();list[index].classList.add('on');}\n"
                    . "});\ninput.focus();paint();",
                'body' => self::wrap(
                    self::card(
                        '<div class="palette">'
                        . '<div class="pinput">' . Kit::icon('search', 19) . '<input id="pq" type="text" placeholder="Type a command or search…" aria-label="Command palette">'
                        . '<kbd style="font-family:inherit;font-size:11px;color:var(--mut);background:var(--soft);border:1px solid var(--bd);border-radius:5px;padding:2px 6px">Esc</kbd></div>'
                        . '<div class="plist">'
                        . '<div class="psec">Actions</div>'
                        . self::paletteItem('plus', 'New component', 'new component create', 'Ctrl N')
                        . self::paletteItem('upload', 'Upload a template file', 'upload template file', '')
                        . self::paletteItem('download', 'Export the gallery as JSON', 'export json gallery', '')
                        . '<div class="psec">Go to</div>'
                        . self::paletteItem('grid', 'Components', 'components gallery', 'G then C')
                        . self::paletteItem('edit', 'Drawing editor', 'drawing editor draw', 'G then D')
                        . self::paletteItem('users', 'Users', 'users people team', 'G then U')
                        . self::paletteItem('settings', 'Settings', 'settings preferences', 'G then S')
                        . '<div class="psec">Recent</div>'
                        . self::paletteItem('file', 'Analytics table with sparklines', 'analytics table sparklines', '')
                        . self::paletteItem('file', 'Checkout payment form', 'checkout payment form', '')
                        . '</div>'
                        . '<div class="pfoot"><span><kbd>↑</kbd><kbd>↓</kbd> to move</span><span><kbd>↵</kbd> to run</span><span><kbd>Esc</kbd> to close</span></div>'
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'search-autocomplete',
                'name' => 'Search with suggestions',
                'name_ar' => 'بحث مع اقتراحات',
                'tagline' => 'Typeahead with recent searches and highlighted matches.',
                'tagline_ar' => 'إكمال تلقائي مع عمليات البحث الأخيرة وتمييز التطابقات.',
                'summary' => 'A search field that shows recent queries before anything is typed and highlights the matched part of each suggestion afterwards. The highlight is what makes a list of ten suggestions scannable instead of readable.',
                'summary_ar' => 'حقل بحث يعرض الاستعلامات الأخيرة قبل كتابة أي شيء، ثم يميّز الجزء المطابق في كل اقتراح. هذا التمييز هو ما يجعل قائمة من عشرة اقتراحات تُمسح بالنظر بدل أن تُقرأ كلمة كلمة.',
                'accent' => '#0891b2',
                'tags' => ['search', 'autocomplete', 'typeahead', 'suggestions'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Recent searches shown on focus, before typing', 'Matched substring highlighted in each suggestion', 'Keyboard navigable with a listbox role'],
                'features_ar' => ['عمليات البحث الأخيرة تظهر عند التركيز، قبل الكتابة', 'الجزء المطابق مميَّز في كل اقتراح', 'قابل للتنقل بلوحة المفاتيح مع دور listbox'],
                'height' => 580,
                'max' => 560,
                'css' => ".sbox{position:relative}\n.sfield{display:flex;align-items:center;gap:10px;border:1px solid var(--bd);border-radius:12px;padding:11px 14px;background:var(--card)}\n.sfield:focus-within{border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-soft)}\n.sfield input{flex:1;border:0;background:none;outline:none;font:inherit;font-size:14px;color:var(--ink)}\n.sugg{position:absolute;left:0;right:0;top:calc(100% + 7px);background:var(--card);border:1px solid var(--bd);border-radius:13px;box-shadow:var(--sh);padding:7px;z-index:20;max-height:260px;overflow:auto}\n.sugg .lbl{font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);font-weight:700;padding:9px 10px 5px}\n.sugg button{display:flex;align-items:center;gap:11px;width:100%;border:0;background:none;padding:9px 10px;border-radius:9px;font:inherit;font-size:13.5px;color:var(--ink);cursor:pointer;text-align:left}\n.sugg button:hover{background:var(--soft)}\n.sugg mark{background:var(--acc-soft);color:var(--acc);font-weight:700;border-radius:3px;padding:0 1px}\n.sugg .cat{margin-left:auto;font-size:11px;color:var(--mut)}",
                'js' => "const field=document.getElementById('sq');\n"
                    . "const box=document.getElementById('sugg');\n"
                    . "const data=[\n"
                    . "  ['Users directory table','Tables'],['Sortable data table','Tables'],['Invoice line items','Tables'],\n"
                    . "  ['Sign in form','Forms'],['Checkout payment form','Forms'],['Multi-step wizard','Forms'],\n"
                    . "  ['Revenue overview dashboard','Dashboards'],['KPI stat tiles','Dashboards'],\n"
                    . "  ['Button set','UI Elements'],['Modal dialog','UI Elements'],['Toast notifications','UI Elements']\n"
                    . "];\n"
                    . "function render(term){\n"
                    . "  const matches=data.filter(row=>row[0].toLowerCase().includes(term)).slice(0,6);\n"
                    . "  if(!matches.length){box.innerHTML='<div class=\"lbl\">No matches</div>';return;}\n"
                    . "  box.innerHTML='<div class=\"lbl\">Components</div>'+matches.map(([name,category])=>{\n"
                    . "    const at=name.toLowerCase().indexOf(term);\n"
                    . "    const marked=term?name.slice(0,at)+'<mark>'+name.slice(at,at+term.length)+'</mark>'+name.slice(at+term.length):name;\n"
                    . "    return '<button type=\"button\" role=\"option\">'+marked+'<span class=\"cat\">'+category+'</span></button>';\n"
                    . "  }).join('');\n"
                    . "}\n"
                    . "field.addEventListener('input',()=>{\n"
                    . "  const term=field.value.trim().toLowerCase();\n"
                    . "  box.hidden=false;\n"
                    . "  if(!term){box.innerHTML=document.getElementById('recent').innerHTML;return;}\n"
                    . "  render(term);\n"
                    . "});\n"
                    . "field.addEventListener('focus',()=>{box.hidden=false;});\n"
                    . "document.addEventListener('click',event=>{if(!event.target.closest('.sbox'))box.hidden=true;});",
                'body' => self::wrap(
                    self::card(
                        self::head('Search', 'Type "table" or "form" to see the highlighting')
                        . '<div class="pad"><div class="sbox">'
                        . '<div class="sfield">' . Kit::icon('search', 18) . '<input id="sq" type="search" placeholder="Search components…" role="combobox" aria-expanded="true" aria-controls="sugg" autocomplete="off"></div>'
                        . '<div class="sugg" id="sugg" role="listbox">'
                        . '<div class="lbl">Recent searches</div>'
                        . '<button type="button" role="option">' . Kit::icon('clock', 15) . 'pricing table<span class="cat">3 results</span></button>'
                        . '<button type="button" role="option">' . Kit::icon('clock', 15) . 'dark dashboard<span class="cat">8 results</span></button>'
                        . '<button type="button" role="option">' . Kit::icon('clock', 15) . 'checkout<span class="cat">4 results</span></button>'
                        . '</div></div>'
                        . '<div id="recent" hidden><div class="lbl">Recent searches</div>'
                        . '<button type="button">' . Kit::icon('clock', 15) . 'pricing table<span class="cat">3 results</span></button>'
                        . '<button type="button">' . Kit::icon('clock', 15) . 'dark dashboard<span class="cat">8 results</span></button>'
                        . '<button type="button">' . Kit::icon('clock', 15) . 'checkout<span class="cat">4 results</span></button></div>'
                        . '<div style="height:210px"></div></div>'
                    )
                ),
            ],
            [
                'slug' => 'date-picker-calendar',
                'name' => 'Date picker calendar',
                'name_ar' => 'منتقي التاريخ',
                'tagline' => 'Month grid with today, selection and disabled days.',
                'tagline_ar' => 'شبكة شهرية تُظهر اليوم والتحديد والأيام المعطَّلة.',
                'summary' => 'A calendar that handles the states a date picker actually meets: today, the selected day, days outside the month, and days that cannot be chosen. Quick ranges sit underneath, which is how most date choices are really made.',
                'summary_ar' => 'تقويم يتعامل مع الحالات التي يواجهها منتقي التاريخ فعلًا: اليوم الحالي، واليوم المحدد، والأيام خارج الشهر، والأيام التي لا يمكن اختيارها. وتحته نطاقات سريعة، فهكذا تُختار معظم التواريخ في الواقع.',
                'accent' => '#7c3aed',
                'tags' => ['calendar', 'date-picker', 'dates', 'input'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Today, selected, outside-month and disabled days', 'Quick range shortcuts under the grid', 'Month navigation with an accessible label'],
                'features_ar' => ['اليوم الحالي والمحدد وأيام خارج الشهر والأيام المعطَّلة', 'اختصارات لنطاقات سريعة تحت الشبكة', 'تنقل بين الأشهر مع تسمية سهلة الوصول'],
                'height' => 620,
                'max' => 380,
                'css' => ".cal{padding:16px}\n.calhead{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}\n.calhead b{font-size:14.5px}\n.calhead button{border:1px solid var(--bd);background:var(--card);border-radius:9px;padding:6px;cursor:pointer;color:var(--mut);display:inline-flex}\n.calhead button:hover{border-color:var(--acc);color:var(--acc)}\n.dow{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:6px}\n.dow span{text-align:center;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);font-weight:700}\n.days{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}\n.day{aspect-ratio:1;border:0;background:none;border-radius:10px;font:inherit;font-size:13px;font-weight:600;color:var(--ink);cursor:pointer;font-variant-numeric:tabular-nums}\n.day:hover:not([disabled]){background:var(--soft)}\n.day.out{color:var(--faint)}\n.day.today{box-shadow:inset 0 0 0 1.5px var(--acc);color:var(--acc)}\n.day[aria-pressed=true]{background:var(--acc);color:#fff}\n.day[disabled]{color:var(--faint);cursor:not-allowed;text-decoration:line-through}\n.quick{display:flex;gap:7px;flex-wrap:wrap;padding:14px 16px;border-top:1px solid var(--bd)}",
                'js' => "document.querySelectorAll('.day:not([disabled])').forEach(day=>{\n"
                    . "  day.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.day').forEach(other=>other.setAttribute('aria-pressed','false'));\n"
                    . "    day.setAttribute('aria-pressed','true');\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::card(
                        self::head('Pick a date', 'September 2026')
                        . '<div class="cal">'
                        . '<div class="calhead"><button type="button" aria-label="Previous month">' . Kit::icon('chevron-left', 16) . '</button>'
                        . '<b>September 2026</b>'
                        . '<button type="button" aria-label="Next month">' . Kit::icon('chevron-right', 16) . '</button></div>'
                        . '<div class="dow"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>'
                        . '<div class="days">' . self::calendarDays() . '</div></div>'
                        . '<div class="quick">'
                        . self::btn('Today', '', 'tiny') . self::btn('Tomorrow', '', 'tiny')
                        . self::btn('Next week', '', 'tiny') . self::btn('In a month', '', 'tiny') . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'pricing-cards',
                'name' => 'Pricing cards',
                'name_ar' => 'بطاقات الأسعار',
                'tagline' => 'Three tiers with the recommended one raised.',
                'tagline_ar' => 'ثلاث باقات مع إبراز الباقة الموصى بها.',
                'summary' => 'Pricing cards where the recommended plan is raised and ringed rather than merely badged, and every feature list starts with what the plan adds to the one before it - which is the comparison people are actually making.',
                'summary_ar' => 'بطاقات أسعار تُرفع فيها الباقة الموصى بها وتُحاط بإطار بدل الاكتفاء بشارة، وتبدأ قائمة مزايا كل باقة بما تضيفه على سابقتها، وهي المقارنة التي يجريها الناس فعلًا.',
                'accent' => '#7c3aed',
                'tags' => ['pricing', 'cards', 'plans', 'marketing'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Recommended plan raised and ringed, not just labelled', 'Feature lists framed as what each tier adds', 'Annual saving stated as a figure'],
                'features_ar' => ['الباقة الموصى بها مرفوعة ومؤطرة، لا مجرد تسمية', 'قوائم المزايا مصاغة كإضافات كل باقة', 'التوفير السنوي مذكور برقم'],
                'height' => 700,
                'max' => 900,
                'css' => ".plan{display:flex;flex-direction:column;height:100%}\n.plan .body{padding:22px;flex:1}\n.plan .price{font-size:38px;font-weight:750;letter-spacing:-.035em;line-height:1;margin:12px 0 4px}\n.plan .price span{font-size:14px;font-weight:500;color:var(--mut)}\n.plan ul{list-style:none;margin:18px 0 0;padding:0;display:grid;gap:10px}\n.plan li{display:flex;gap:9px;font-size:13px;align-items:flex-start}\n.plan li svg{color:var(--ok);flex:none;margin-top:2px}\n.plan .cta{padding:0 22px 22px}\n.best{border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-soft),var(--sh);transform:translateY(-6px)}\n.tag{position:absolute;top:-11px;left:50%;transform:translateX(-50%);background:var(--acc);color:#fff;font-size:11px;font-weight:700;padding:3px 12px;border-radius:999px;letter-spacing:.03em}",
                'body' => self::wrap(
                    '<div style="text-align:center;margin-bottom:22px"><h1>Simple pricing</h1>'
                    . '<p class="sub" style="margin-top:6px">Every plan includes the full component library. Cancel any time.</p></div>',
                    self::grid(
                        250,
                        self::planCard('Starter', '$0', 'forever', ['3 projects', '1 seat', 'Community support', 'Frugal badge on published work'], 'Start free', false),
                        self::planCard('Studio', '$29', 'per month', ['Everything in Starter, plus:', 'Unlimited projects', '10 seats', 'Custom domain', 'No badge', 'Email support'], 'Choose Studio', true),
                        self::planCard('Agency', '$79', 'per month', ['Everything in Studio, plus:', '50 seats', 'Audit log and SSO', 'Priority support', 'Onboarding call'], 'Contact sales', false)
                    ),
                    '<p class="sub" style="text-align:center;margin-top:18px">Paying yearly saves two months - $58 on Studio, $158 on Agency.</p>'
                ),
            ],
            [
                'slug' => 'testimonial-cards',
                'name' => 'Testimonial cards',
                'name_ar' => 'بطاقات آراء العملاء',
                'tagline' => 'Quotes with author, role and a rating.',
                'tagline_ar' => 'اقتباسات مع الكاتب ومنصبه وتقييم.',
                'summary' => 'Testimonials that carry enough attribution to be believed: a name, a role and a company, with the rating shown as stars. The featured quote is set larger because one strong quote outperforms four weak ones.',
                'summary_ar' => 'آراء عملاء تحمل ما يكفي من النسبة لتكون موثوقة: اسم ومنصب وشركة، مع تقييم بالنجوم. الاقتباس المميز أكبر حجمًا، لأن اقتباسًا قويًا واحدًا يتفوق على أربعة ضعيفة.',
                'accent' => '#f59e0b',
                'tags' => ['testimonial', 'quotes', 'social-proof', 'marketing'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Full attribution: name, role and company', 'Featured quote set larger than the rest', 'Star rating drawn inline'],
                'features_ar' => ['نسبة كاملة: الاسم والمنصب والشركة', 'الاقتباس المميز أكبر من البقية', 'تقييم بالنجوم مرسوم ضمن الصفحة'],
                'height' => 640,
                'max' => 900,
                'css' => ".quote{padding:22px}\n.quote .mark{font-size:44px;line-height:.8;color:var(--acc);opacity:.35;font-family:Georgia,serif}\n.quote p{font-size:14px;line-height:1.7;margin-top:10px}\n.quote .who{display:flex;align-items:center;gap:11px;margin-top:18px;padding-top:16px;border-top:1px solid var(--bd)}\n.feature p{font-size:19px;line-height:1.55;font-weight:500}",
                'body' => self::wrap(
                    self::card('<div class="quote feature">'
                        . '<span class="mark">&ldquo;</span>'
                        . '<p>We replaced a design system nobody maintained with forty of these files. Two hundred lines of CSS instead of a build pipeline, and the team actually uses them.</p>'
                        . '<div class="who">' . Kit::avatar('Maya Rahman', 44)
                        . '<span style="flex:1"><b style="display:block">Maya Rahman</b>'
                        . '<span class="xs mut">Engineering lead · Northwind Ltd</span></span>'
                        . Kit::stars(5, 16) . '</div></div>'),
                    '<div style="height:14px"></div>',
                    self::grid(
                        260,
                        self::card('<div class="quote"><span class="mark">&ldquo;</span>'
                            . '<p>The drawing templates arrive as real editable shapes. That sounds obvious until you have used the tools that flatten everything to an image.</p>'
                            . '<div class="who">' . Kit::avatar('Omar Saleh', 38)
                            . '<span style="flex:1"><b style="display:block;font-size:13.5px">Omar Saleh</b>'
                            . '<span class="xs mut">Product designer · Juno Labs</span></span></div></div>'),
                        self::card('<div class="quote"><span class="mark">&ldquo;</span>'
                            . '<p>Every template is one file with no dependencies. I can read the whole thing before I paste it into a client project, which I cannot say for most libraries.</p>'
                            . '<div class="who">' . Kit::avatar('Karim Nasser', 38)
                            . '<span style="flex:1"><b style="display:block;font-size:13.5px">Karim Nasser</b>'
                            . '<span class="xs mut">Freelance developer</span></span></div></div>'),
                        self::card('<div class="quote"><span class="mark">&ldquo;</span>'
                            . '<p>The Arabic support is real rather than a machine translation, and the layouts flip properly. That is rarer than it should be.</p>'
                            . '<div class="who">' . Kit::avatar('Sara Aziz', 38)
                            . '<span style="flex:1"><b style="display:block;font-size:13.5px">Sara Aziz</b>'
                            . '<span class="xs mut">Content lead · Harbor Studio</span></span></div></div>')
                    )
                ),
            ],
            [
                'slug' => 'product-card-grid',
                'name' => 'Product cards',
                'name_ar' => 'بطاقات المنتجات',
                'tagline' => 'Shop cards with price, rating, badge and quick add.',
                'tagline_ar' => 'بطاقات متجر بالسعر والتقييم وشارة وإضافة سريعة.',
                'summary' => 'Commerce cards carrying what a shopper decides on: price with the old one struck through, the rating and its count, a stock warning where it matters, and an add button that appears on hover without hiding anything.',
                'summary_ar' => 'بطاقات تجارة إلكترونية تحمل ما يبني عليه المتسوق قراره: السعر مع القديم مشطوبًا، والتقييم وعدد المراجعات، وتحذير المخزون حيث يهم، وزر إضافة يظهر عند التمرير فوقه دون أن يحجب شيئًا.',
                'accent' => '#059669',
                'tags' => ['ecommerce', 'product', 'cards', 'shop'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Price, strike-through and discount badge', 'Rating with the number of reviews', 'Quick-add button revealed on hover and focus'],
                'features_ar' => ['السعر مع السعر المشطوب وشارة الخصم', 'التقييم مع عدد المراجعات', 'زر إضافة سريعة يظهر عند التمرير فوقه وعند التركيز'],
                'height' => 700,
                'max' => 900,
                'css' => ".prod{overflow:hidden;display:flex;flex-direction:column}\n.shot{height:150px;position:relative;display:flex;align-items:flex-end;padding:11px}\n.shot .badge{position:absolute;top:11px;left:11px;background:var(--card);color:var(--ink);font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px}\n.shot .fav{position:absolute;top:11px;right:11px;border:0;background:rgba(255,255,255,.9);border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#475569}\n.prod .body{padding:14px;display:grid;gap:7px;flex:1}\n.prod .price{font-size:17px;font-weight:700}\n.prod .was{font-size:12.5px;color:var(--mut);text-decoration:line-through;margin-left:6px;font-weight:500}\n.prod .add{opacity:0;transition:opacity .15s}\n.prod:hover .add,.prod:focus-within .add{opacity:1}\n@media (hover:none){.prod .add{opacity:1}}",
                'body' => self::wrap(
                    self::grid(
                        210,
                        self::productCard('Aurora Desk Lamp', 'Lighting', '$39.00', '$49.00', 4.5, 128, 'linear-gradient(140deg,#fbbf24,#f97316)', '-20%', ''),
                        self::productCard('Nimbus Chair', 'Seating', '$180.00', '', 5, 412, 'linear-gradient(140deg,#2563eb,#60a5fa)', 'New', ''),
                        self::productCard('Terra Side Table', 'Tables', '$58.00', '', 4, 64, 'linear-gradient(140deg,#0f766e,#14b8a6)', '', 'Only 4 left'),
                        self::productCard('Halo Floor Lamp', 'Lighting', '$92.00', '$110.00', 4.5, 218, 'linear-gradient(140deg,#7c3aed,#c084fc)', '-16%', 'Low stock')
                    )
                ),
            ],
            [
                'slug' => 'profile-card',
                'name' => 'Profile card',
                'name_ar' => 'بطاقة الملف الشخصي',
                'tagline' => 'Cover, avatar, stats and follow button.',
                'tagline_ar' => 'غلاف وصورة رمزية وإحصاءات وزر متابعة.',
                'summary' => 'A profile card with a drawn cover gradient rather than a hero image, the avatar overlapping the cover the way every social product does it, and the three counts that make a profile feel populated.',
                'summary_ar' => 'بطاقة ملف شخصي بغلاف متدرج مرسوم بدل صورة كبيرة، والصورة الرمزية تتداخل مع الغلاف كما تفعل كل المنصات الاجتماعية، مع الأعداد الثلاثة التي تُشعر بأن الملف حافل.',
                'accent' => '#4f46e5',
                'tags' => ['profile', 'card', 'social', 'user'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Cover drawn as a gradient, no image needed', 'Avatar overlapping the cover edge', 'Follow button with a followed state'],
                'features_ar' => ['غلاف مرسوم بتدرج لوني، دون الحاجة إلى صورة', 'صورة رمزية تتداخل مع حافة الغلاف', 'زر متابعة مع حالة «تتابعه»'],
                'height' => 560,
                'max' => 680,
                'css' => ".cover{height:104px;background:linear-gradient(135deg,var(--acc),var(--acc-dk) 55%,#0f172a)}\n.pbody{padding:0 20px 20px;margin-top:-34px}\n.pav{box-shadow:0 0 0 4px var(--card);border-radius:50%;display:inline-flex}\n.stats{display:flex;gap:24px;margin-top:16px;padding-top:16px;border-top:1px solid var(--bd)}\n.stats b{display:block;font-size:17px;font-weight:700;font-variant-numeric:tabular-nums}\n.stats span{font-size:11.5px;color:var(--mut)}\n.tags{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px}",
                'body' => self::wrap(
                    self::grid(
                        300,
                        self::card('<div class="cover"></div><div class="pbody">'
                            . '<span class="pav">' . Kit::avatar('Lina Haddad', 72) . '</span>'
                            . '<div class="row" style="justify-content:space-between;align-items:flex-start;margin-top:12px;gap:12px">'
                            . '<span><h2>Lina Haddad</h2><p class="sub">Head of design · Beirut</p></span>'
                            . self::btn('Follow', 'plus', 'pri') . '</div>'
                            . '<p class="sm mut" style="margin-top:12px">Building the component library. Writes about interface design, vector tooling and Arabic typography on the web.</p>'
                            . '<div class="tags">' . Kit::pill('Design systems', 'info') . Kit::pill('SVG', 'info') . Kit::pill('Typography', 'info') . '</div>'
                            . '<div class="stats"><span><b>248</b><span>Components</span></span>'
                            . '<span><b>12.4k</b><span>Followers</span></span>'
                            . '<span><b>184</b><span>Following</span></span></div></div>'),
                        self::card('<div class="cover" style="background:linear-gradient(135deg,#0f766e,#14b8a6 55%,#022c22)"></div><div class="pbody">'
                            . '<span class="pav">' . Kit::avatar('Omar Saleh', 72) . '</span>'
                            . '<div class="row" style="justify-content:space-between;align-items:flex-start;margin-top:12px;gap:12px">'
                            . '<span><h2>Omar Saleh</h2><p class="sub">Staff engineer · Amman</p></span>'
                            . self::btn('Following', 'check') . '</div>'
                            . '<p class="sm mut" style="margin-top:12px">Works on the drawing editor. Mostly geometry, path operations and the parts of SVG nobody reads the spec for.</p>'
                            . '<div class="tags">' . Kit::pill('Canvas', 'info') . Kit::pill('Performance', 'info') . '</div>'
                            . '<div class="stats"><span><b>96</b><span>Components</span></span>'
                            . '<span><b>4.1k</b><span>Followers</span></span>'
                            . '<span><b>320</b><span>Following</span></span></div></div>')
                    )
                ),
            ],
            [
                'slug' => 'notification-list',
                'name' => 'Notification list',
                'name_ar' => 'قائمة الإشعارات',
                'tagline' => 'Unread markers, grouping and a mark-all action.',
                'tagline_ar' => 'علامات غير المقروء وتجميع وإجراء لتعليم الكل.',
                'summary' => 'A notification panel where unread is a state you can see and clear: a dot on the row, a count in the header and one action to mark everything read. Each row names the actor, the object and the time.',
                'summary_ar' => 'لوحة إشعارات تجعل غير المقروء حالة تراها وتمسحها: نقطة على الصف، وعدد في الرأس، وإجراء واحد لتعليم الكل كمقروء. كل صف يذكر الفاعل والعنصر والوقت.',
                'accent' => '#2563eb',
                'tags' => ['notifications', 'list', 'unread', 'panel'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Unread rows marked and counted', 'Mark-all-read clears the state in place', 'Rows name actor, object and time'],
                'features_ar' => ['الصفوف غير المقروءة مميَّزة ومعدودة', '«تعليم الكل كمقروء» يمسح الحالة في مكانها', 'الصفوف تذكر الفاعل والعنصر والوقت'],
                'height' => 640,
                'max' => 480,
                'css' => ".note{display:flex;gap:12px;padding:14px 18px;border-bottom:1px solid var(--bd);position:relative;cursor:pointer}\n.note:last-child{border-bottom:0}\n.note:hover{background:var(--soft)}\n.note.unread{background:var(--acc-soft)}\n.note.unread::before{content:'';position:absolute;left:7px;top:50%;transform:translateY(-50%);width:6px;height:6px;border-radius:50%;background:var(--acc)}\n.note .txt{flex:1;min-width:0}\n.note .txt p{font-size:13.5px;line-height:1.5}\n.note .when{font-size:11.5px;color:var(--mut);margin-top:3px}",
                'js' => "document.getElementById('markall').addEventListener('click',()=>{\n"
                    . "  document.querySelectorAll('.note.unread').forEach(note=>note.classList.remove('unread'));\n"
                    . "  document.getElementById('count').textContent='0';\n"
                    . "});\n"
                    . "document.querySelectorAll('.note').forEach(note=>{\n"
                    . "  note.addEventListener('click',()=>{\n"
                    . "    if(!note.classList.contains('unread'))return;\n"
                    . "    note.classList.remove('unread');\n"
                    . "    const badge=document.getElementById('count');\n"
                    . "    badge.textContent=Math.max(0,Number(badge.textContent)-1);\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::card(
                        self::head('Notifications', '<span id="count">3</span> unread', '<button class="btn gh tiny" type="button" id="markall">Mark all read</button>')
                        . '<div class="note unread">' . Kit::avatar('Maya Rahman', 38)
                        . '<span class="txt"><p><b>Maya Rahman</b> commented on <b>Compact dense table</b></p>'
                        . '<span class="when">&ldquo;Could we drop the sparkline at this row height?&rdquo; · 14 minutes ago</span></span></div>'
                        . '<div class="note unread">' . Kit::iconTile('check', Kit::SERIES[2], 38)
                        . '<span class="txt"><p>Your component <b>Analytics table with sparklines</b> was approved</p>'
                        . '<span class="when">It is now live in the gallery · 42 minutes ago</span></span></div>'
                        . '<div class="note unread">' . Kit::iconTile('alert', Kit::SERIES[7], 38)
                        . '<span class="txt"><p>Deployment <b>v4.11.3</b> was rolled back</p>'
                        . '<span class="when">Health check failed after 4 minutes · 2 hours ago</span></span></div>'
                        . '<div class="note">' . Kit::avatar('Omar Saleh', 38)
                        . '<span class="txt"><p><b>Omar Saleh</b> invited you to <b>Aurora Redesign</b></p>'
                        . '<span class="when">Yesterday at 16:31</span></span></div>'
                        . '<div class="note">' . Kit::iconTile('card', Kit::SERIES[1], 38)
                        . '<span class="txt"><p>Invoice <b>INV-2281</b> was paid</p>'
                        . '<span class="when">$13,503.30 from Northwind Ltd · 2 days ago</span></span></div>'
                        . '<div class="ft"><span>Showing 5 of 42</span><a href="#">See all notifications</a></div>'
                    )
                ),
            ],
            [
                'slug' => 'vertical-timeline',
                'name' => 'Vertical timeline',
                'name_ar' => 'خط زمني عمودي',
                'tagline' => 'Dated events on a line with type markers.',
                'tagline_ar' => 'أحداث مؤرخة على خط مع علامات للنوع.',
                'summary' => 'A timeline where the line is drawn by the markers themselves rather than by a background image, so it stretches with the content. Each entry carries a type tile, a date and room for detail.',
                'summary_ar' => 'خط زمني ترسم فيه العلامات نفسها الخط بدل صورة خلفية، فيمتد مع المحتوى. كل مدخل يحمل مربعًا لنوعه وتاريخًا ومساحة للتفاصيل.',
                'accent' => '#7c3aed',
                'tags' => ['timeline', 'history', 'events', 'vertical'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Connector line that grows with the content', 'Type markers coloured by event kind', 'Date column that stays aligned'],
                'features_ar' => ['خط واصل يمتد مع المحتوى', 'علامات ملوّنة حسب نوع الحدث', 'عمود التاريخ يبقى محاذيًا'],
                'height' => 680,
                'max' => 620,
                'css' => ".tl{padding:20px}\n.item{display:grid;grid-template-columns:38px 1fr;gap:16px;position:relative;padding-bottom:26px}\n.item:last-child{padding-bottom:0}\n.item::before{content:'';position:absolute;left:18px;top:40px;bottom:-4px;width:2px;background:var(--bd)}\n.item:last-child::before{display:none}\n.item .when{font-size:11.5px;color:var(--mut);font-variant-numeric:tabular-nums}\n.item h4{font-size:14px;margin-top:3px}\n.item p{font-size:13px;color:var(--mut);margin-top:5px;line-height:1.6}",
                'body' => self::wrap(
                    self::card(
                        self::head('Order history', 'Order #3104 · placed 12 September')
                        . '<div class="tl">'
                        . self::timelineItem('check', Kit::SERIES[2], '12 Sep, 09:41', 'Order placed', 'Paid by Visa ending 4242. Confirmation sent to rana.k@mail.com.')
                        . self::timelineItem('box', Kit::SERIES[0], '12 Sep, 14:02', 'Packed at Warehouse A', 'Three items picked from bins A-04, A-09 and B-07.')
                        . self::timelineItem('truck', Kit::SERIES[0], '13 Sep, 07:18', 'Collected by Aramex', 'Tracking number 1Z9V8A7364. Expected 14 September.')
                        . self::timelineItem('map-pin', Kit::SERIES[3], '13 Sep, 19:44', 'Arrived at the Riyadh hub', 'Out for delivery on the next round.')
                        . self::timelineItem('clock', Kit::SERIES[4], '14 Sep', 'Out for delivery', 'Between 09:00 and 13:00. The courier will call on arrival.', true)
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'kanban-card',
                'name' => 'Kanban card',
                'name_ar' => 'بطاقة كانبان',
                'tagline' => 'Board cards with labels, assignee and subtask progress.',
                'tagline_ar' => 'بطاقات لوحة مهام بتسميات ومسؤول وتقدم المهام الفرعية.',
                'summary' => 'The card that makes a board readable: a colour label strip, the title, and a footer of small facts - subtasks done, comments, attachments and the due date, which turns red once it has passed.',
                'summary_ar' => 'البطاقة التي تجعل اللوحة مقروءة: شريط تسميات ملوّن، والعنوان، وتذييل من معلومات صغيرة: المهام الفرعية المنجزة، والتعليقات، والمرفقات، وتاريخ الاستحقاق الذي يتحول إلى الأحمر بعد فواته.',
                'accent' => '#0891b2',
                'tags' => ['kanban', 'board', 'cards', 'tasks'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Label strip carrying more than one label', 'Subtask progress, comments and attachments', 'Overdue date flagged in status colour'],
                'features_ar' => ['شريط تسميات يتسع لأكثر من تسمية', 'تقدم المهام الفرعية والتعليقات والمرفقات', 'التاريخ المتأخر مميَّز بلون الحالة'],
                'height' => 640,
                'max' => 820,
                'css' => ".col{background:var(--soft);border-radius:14px;padding:12px;display:grid;gap:10px;align-content:start}\n.colhead{display:flex;align-items:center;justify-content:space-between;padding:2px 4px 6px;font-size:12.5px;font-weight:700}\n.colhead .n{background:var(--card);border:1px solid var(--bd);border-radius:999px;padding:1px 8px;font-size:11px;color:var(--mut)}\n.kcard{background:var(--card);border:1px solid var(--bd);border-radius:12px;padding:12px;cursor:grab;box-shadow:0 1px 2px rgba(16,24,40,.05)}\n.kcard:hover{border-color:var(--acc)}\n.labels{display:flex;gap:4px;margin-bottom:9px}\n.labels i{height:5px;width:32px;border-radius:999px}\n.kcard h4{font-size:13.5px;line-height:1.45}\n.kfoot{display:flex;align-items:center;gap:12px;margin-top:11px;font-size:11.5px;color:var(--mut)}\n.kfoot .bit{display:inline-flex;align-items:center;gap:4px}\n.kfoot .late{color:var(--bad);font-weight:700}",
                'body' => self::wrap(
                    '<div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">'
                    . '<div class="col"><div class="colhead">To do<span class="n">4</span></div>'
                    . self::kanbanCard(['#2a78d6'], 'Rewrite the onboarding flow', '2/5', '3', '1', '26 Sep', false, ['Lina Haddad'])
                    . self::kanbanCard(['#eb6834', '#1baf7a'], 'Audit the editor for keyboard access', '0/8', '1', '', '12 Sep', true, ['Maya Rahman'])
                    . '</div>'
                    . '<div class="col"><div class="colhead">In progress<span class="n">2</span></div>'
                    . self::kanbanCard(['#1baf7a'], 'Cache the component previews', '3/4', '6', '2', '04 Oct', false, ['Omar Saleh', 'Nour Sabbagh'])
                    . self::kanbanCard(['#eda100'], 'Arabic copy for the gallery', '5/9', '2', '', '02 Oct', false, ['Sara Aziz'])
                    . '</div>'
                    . '<div class="col"><div class="colhead">Done<span class="n">1</span></div>'
                    . self::kanbanCard(['#008300'], 'Migrate the billing webhooks', '6/6', '9', '4', '18 Sep', false, ['Omar Saleh'])
                    . '</div>'
                    . '</div>'
                ),
            ],
            [
                'slug' => 'interactive-rating',
                'name' => 'Interactive rating',
                'name_ar' => 'تقييم تفاعلي',
                'tagline' => 'Stars, hearts and a thumbs pair, all keyboard usable.',
                'tagline_ar' => 'نجوم وقلوب وزوج إعجاب، وكلها تعمل بلوحة المفاتيح.',
                'summary' => 'Three rating controls built on radio inputs so they are keyboard operable and submit with a form. The hover preview fills the stars up to the pointer, and the label names the value being chosen.',
                'summary_ar' => 'ثلاثة عناصر تقييم مبنية على حقول radio، فتعمل بلوحة المفاتيح وتُرسَل مع النموذج. معاينة التمرير تملأ النجوم حتى موضع المؤشر، والتسمية تذكر القيمة التي يجري اختيارها.',
                'accent' => '#f59e0b',
                'tags' => ['rating', 'stars', 'feedback', 'input'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Radio-backed, so arrow keys work', 'Hover preview that fills up to the pointer', 'Read-only display variant with a count'],
                'features_ar' => ['مبني على radio، فتعمل مفاتيح الأسهم', 'معاينة عند التمرير تملأ حتى موضع المؤشر', 'نوع للعرض فقط مع عدد التقييمات'],
                'height' => 560,
                'max' => 560,
                'css' => self::groupCss() . "\n.rate{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:4px}\n.rate input{position:absolute;opacity:0}\n.rate label{cursor:pointer;color:var(--bd);transition:color .12s}\n.rate label:hover,.rate label:hover ~ label,.rate input:checked ~ label{color:#f59e0b}\n.rate.hearts label:hover,.rate.hearts label:hover ~ label,.rate.hearts input:checked ~ label{color:#e11d48}\n.rate input:focus-visible+label{outline:2px solid var(--acc);outline-offset:3px;border-radius:4px}\n.thumbs{display:flex;gap:9px}\n.thumbs button{border:1px solid var(--bd);background:var(--card);border-radius:11px;padding:9px 15px;cursor:pointer;color:var(--mut);display:inline-flex;align-items:center;gap:8px;font:inherit;font-size:13px;font-weight:600}\n.thumbs button:hover{border-color:var(--acc);color:var(--acc)}\n.thumbs button[aria-pressed=true]{background:var(--acc-soft);border-color:var(--acc);color:var(--acc)}",
                'js' => "document.querySelectorAll('.rate input').forEach(input=>{\n"
                    . "  input.addEventListener('change',()=>{\n"
                    . "    const out=input.closest('.grp').querySelector('.out');\n"
                    . "    if(out)out.textContent=input.value+' of 5';\n"
                    . "  });\n"
                    . "});\n"
                    . "document.querySelectorAll('.thumbs button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.thumbs button').forEach(b=>b.setAttribute('aria-pressed','false'));\n"
                    . "    button.setAttribute('aria-pressed','true');\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::card(
                        self::head('Rating controls', 'Tab into one and use the arrow keys')
                        . self::group('Stars', '<div class="rate">'
                            . self::ratingStars('st', 'm12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z', 32)
                            . '</div><span class="out sm bold" style="margin-left:12px"></span>')
                        . self::group('Hearts', '<div class="rate hearts">'
                            . self::ratingStars('ht', 'M12 20s-8-4.7-8-10a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.3-8 10-8 10Z', 30)
                            . '</div><span class="out sm bold" style="margin-left:12px"></span>')
                        . self::group('Thumbs', '<div class="thumbs">'
                            . '<button type="button" aria-pressed="false">' . Kit::icon('trend-up', 17) . 'Helpful</button>'
                            . '<button type="button" aria-pressed="false">' . Kit::icon('trend-down', 17) . 'Not helpful</button></div>')
                        . self::group('Read-only', '<span class="row" style="gap:10px">' . Kit::stars(4.5, 18)
                            . '<span class="sm"><b>4.5</b> <span class="mut">from 1,284 reviews</span></span></span>')
                    )
                ),
            ],
            [
                'slug' => 'progress-bars',
                'name' => 'Progress indicators',
                'name_ar' => 'مؤشرات التقدم',
                'tagline' => 'Determinate, indeterminate, segmented and circular.',
                'tagline_ar' => 'محدد وغير محدد ومقسَّم ودائري.',
                'summary' => 'Four kinds of progress, each for a different situation: a known percentage, an unknown wait, a multi-part upload and a compact ring. Every determinate one carries its number, since a bar without one is decoration.',
                'summary_ar' => 'أربعة أنواع من مؤشرات التقدم، لكل منها موقفه: نسبة معروفة، وانتظار مجهول المدة، ورفع متعدد الأجزاء، وحلقة مدمجة. كل مؤشر محدد يحمل رقمه، فالشريط بلا رقم مجرد زينة.',
                'accent' => '#2563eb',
                'tags' => ['progress', 'loading', 'bar', 'indicator'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Determinate bars always paired with a value', 'Indeterminate animation that respects reduced motion', 'Segmented and circular variants'],
                'features_ar' => ['أشرطة محددة مقرونة دائمًا بقيمة', 'حركة غير محددة تحترم تفضيل تقليل الحركة', 'نوعان مقسَّم ودائري'],
                'height' => 620,
                'max' => 620,
                'css' => self::groupCss() . "\n.gbody{display:block}\n.bar{height:9px;border-radius:999px;background:var(--soft);overflow:hidden}\n.bar i{display:block;height:100%;border-radius:999px;background:var(--acc)}\n@keyframes indet{0%{margin-left:-38%}100%{margin-left:100%}}\n.bar.indet i{width:38%;animation:indet 1.3s ease-in-out infinite}\n@media (prefers-reduced-motion:reduce){.bar.indet i{animation-duration:3s}}\n.segs{display:flex;gap:4px}\n.segs i{flex:1;height:7px;border-radius:999px;background:var(--soft)}\n.segs i.on{background:var(--acc)}\n.segs i.half{background:linear-gradient(90deg,var(--acc) 50%,var(--soft) 50%)}\n.lbl{display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:7px}\n.lbl b{font-variant-numeric:tabular-nums}",
                'body' => self::wrap(
                    self::card(
                        self::head('Progress', 'One for every kind of wait')
                        . self::group('Determinate', '<div class="lbl"><span>Uploading brand-guide.pdf</span><b>62%</b></div>'
                            . '<div class="bar"><i style="width:62%"></i></div>')
                        . self::group('Success and failure', '<div class="lbl"><span>Import complete</span><b style="color:var(--ok)">100%</b></div>'
                            . '<div class="bar"><i style="width:100%;background:var(--ok)"></i></div>'
                            . '<div class="lbl" style="margin-top:16px"><span>Sync failed at 38%</span><b style="color:var(--bad)">38%</b></div>'
                            . '<div class="bar"><i style="width:38%;background:var(--bad)"></i></div>')
                        . self::group('Indeterminate', '<div class="lbl"><span>Working…</span><b class="mut">no estimate</b></div>'
                            . '<div class="bar indet"><i></i></div>', 'Use this only when the length really is unknown - a fake percentage is worse than none.')
                        . self::group('Segmented', '<div class="lbl"><span>3 of 5 files processed</span><b>60%</b></div>'
                            . '<div class="segs"><i class="on"></i><i class="on"></i><i class="on"></i><i class="half"></i><i></i></div>')
                        . self::group('Circular', '<div class="row" style="gap:18px">' . Kit::ring(72, Kit::SERIES[0], 78)
                            . Kit::ring(38, Kit::SERIES[3], 78) . Kit::ring(100, Kit::SERIES[2], 78) . '</div>')
                    )
                ),
            ],
            [
                'slug' => 'colour-swatch-picker',
                'name' => 'Colour picker',
                'name_ar' => 'منتقي الألوان',
                'tagline' => 'Swatch grid, recent colours and a hex field.',
                'tagline_ar' => 'شبكة عينات وألوان حديثة وحقل hex.',
                'summary' => 'A picker that covers the three ways people choose a colour: from the brand palette, from what they used last, or by pasting a hex. The native colour input is there too, since it is the only one that opens the system picker.',
                'summary_ar' => 'منتقٍ يغطي الطرق الثلاث التي يختار بها الناس لونًا: من لوحة ألوان العلامة، أو مما استخدموه مؤخرًا، أو بلصق رمز hex. وحقل اللون الأصلي موجود أيضًا، فهو الوحيد الذي يفتح منتقي النظام.',
                'accent' => '#db2777',
                'tags' => ['colour', 'picker', 'swatches', 'input'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Palette swatches with an accessible selected state', 'Recent colours remembered in the session', 'Hex field kept in step with the native picker'],
                'features_ar' => ['عينات لوحة الألوان مع حالة تحديد سهلة الوصول', 'الألوان الحديثة محفوظة طوال الجلسة', 'حقل hex متزامن مع المنتقي الأصلي'],
                'height' => 600,
                'max' => 420,
                'css' => ".sw{display:grid;grid-template-columns:repeat(8,1fr);gap:7px}\n.sw button{aspect-ratio:1;border-radius:9px;border:0;cursor:pointer;box-shadow:inset 0 0 0 1px rgba(15,23,42,.1)}\n.sw button[aria-pressed=true]{box-shadow:0 0 0 2px var(--card),0 0 0 4px var(--acc)}\n.preview{height:74px;border-radius:13px;margin-bottom:16px;box-shadow:inset 0 0 0 1px rgba(15,23,42,.1);display:flex;align-items:flex-end;justify-content:flex-end;padding:10px}\n.preview code{background:rgba(15,23,42,.72);color:#fff;font-size:11.5px;padding:3px 8px;border-radius:6px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}\n.hexrow{display:flex;gap:8px}\n.hexrow input[type=text]{flex:1;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}\n.hexrow input[type=color]{width:46px;padding:4px;height:42px}",
                'js' => "const preview=document.querySelector('.preview');\n"
                    . "const label=document.getElementById('hexout');\n"
                    . "const hex=document.getElementById('hex');\n"
                    . "const native=document.getElementById('native');\n"
                    . "const recent=document.getElementById('recent');\n"
                    . "function apply(value){\n"
                    . "  preview.style.background=value;\n"
                    . "  label.textContent=value.toUpperCase();\n"
                    . "  hex.value=value.toUpperCase();\n"
                    . "  native.value=value;\n"
                    . "  if(![...recent.children].some(child=>child.dataset.c===value)){\n"
                    . "    const dot=document.createElement('button');\n"
                    . "    dot.type='button';dot.dataset.c=value;dot.style.background=value;\n"
                    . "    dot.setAttribute('aria-label',value);\n"
                    . "    dot.addEventListener('click',()=>apply(value));\n"
                    . "    recent.prepend(dot);\n"
                    . "    if(recent.children.length>8)recent.lastElementChild.remove();\n"
                    . "  }\n"
                    . "}\n"
                    . "document.querySelectorAll('.palette button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.palette button').forEach(b=>b.setAttribute('aria-pressed','false'));\n"
                    . "    button.setAttribute('aria-pressed','true');\n"
                    . "    apply(button.dataset.c);\n"
                    . "  });\n"
                    . "});\n"
                    . "native.addEventListener('input',()=>apply(native.value));\n"
                    . "hex.addEventListener('change',()=>{if(/^#[0-9a-f]{6}$/i.test(hex.value))apply(hex.value.toLowerCase());});",
                'body' => self::wrap(
                    self::card(
                        self::head('Colour')
                        . '<div class="pad">'
                        . '<div class="preview" style="background:#db2777"><code id="hexout">#DB2777</code></div>'
                        . '<span class="lb">Palette</span>'
                        . '<div class="sw palette">' . implode('', array_map(
                            function ($colour) {
                                $on = $colour === '#db2777' ? 'true' : 'false';

                                return '<button type="button" data-c="' . $colour . '" aria-pressed="' . $on . '" aria-label="' . $colour . '" style="background:' . $colour . '"></button>';
                            },
                            ['#0f172a', '#475569', '#94a3b8', '#e11d48', '#db2777', '#7c3aed', '#4f46e5', '#2563eb',
                                '#0891b2', '#0d9488', '#059669', '#16a34a', '#65a30d', '#d97706', '#ea580c', '#c2410c']
                        )) . '</div>'
                        . '<span class="lb" style="margin-top:16px">Recent</span>'
                        . '<div class="sw" id="recent"><button type="button" data-c="#db2777" style="background:#db2777" aria-label="#db2777"></button></div>'
                        . '<span class="lb" style="margin-top:16px">Hex</span>'
                        . '<div class="hexrow"><input class="in" type="text" id="hex" value="#DB2777" aria-label="Hex colour">'
                        . '<input class="in" type="color" id="native" value="#db2777" aria-label="Open the system colour picker"></div>'
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'cookie-consent-banner',
                'name' => 'Cookie consent banner',
                'name_ar' => 'شريط موافقة الكوكيز',
                'tagline' => 'A bottom banner where rejecting is as easy as accepting.',
                'tagline_ar' => 'شريط سفلي يكون فيه الرفض بسهولة القبول.',
                'summary' => 'Consent without the dark pattern: reject-all has the same weight as accept-all, the categories are named in the banner rather than hidden behind a settings link, and dismissing is not treated as consent.',
                'summary_ar' => 'موافقة دون أنماط مضللة: زر «رفض الكل» بوزن «قبول الكل» نفسه، والفئات مذكورة في الشريط لا مخفية خلف رابط إعدادات، وإغلاق الشريط لا يُعدّ موافقة.',
                'accent' => '#059669',
                'tags' => ['cookies', 'consent', 'privacy', 'banner'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Reject given equal visual weight to accept', 'Categories named in the banner itself', 'Expanded settings without leaving the page'],
                'features_ar' => ['الرفض بوزن بصري مساوٍ للقبول', 'الفئات مذكورة في الشريط نفسه', 'إعدادات موسَّعة دون مغادرة الصفحة'],
                'height' => 560,
                'max' => 720,
                'css' => ".banner{position:fixed;left:16px;right:16px;bottom:16px;max-width:660px;margin:0 auto;background:var(--card);border:1px solid var(--bd);border-radius:16px;box-shadow:0 20px 50px -24px rgba(0,0,0,.5);z-index:40}\n.banner .body{padding:18px 20px}\n.banner h3{font-size:14.5px}\n.banner p{font-size:13px;color:var(--mut);margin-top:7px;line-height:1.6}\n.banner .acts{display:flex;gap:9px;padding:0 20px 18px;flex-wrap:wrap}\n.banner .acts .btn{flex:1;min-width:130px}\n.more{border-top:1px solid var(--bd);padding:16px 20px;display:grid;gap:12px}\n.more[hidden]{display:none}\n.crow{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;font-size:13px}\n.crow input{width:17px;height:17px;accent-color:var(--acc);margin-top:2px}",
                'js' => "const more=document.getElementById('more');\n"
                    . "document.getElementById('customise').addEventListener('click',()=>{\n"
                    . "  more.hidden=!more.hidden;\n"
                    . "  document.getElementById('customise').textContent=more.hidden?'Customise':'Hide options';\n"
                    . "});\n"
                    . "document.querySelectorAll('[data-dismiss]').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    document.querySelector('.banner').style.display='none';\n"
                    . "    document.getElementById('again').hidden=false;\n"
                    . "  });\n"
                    . "});\n"
                    . "document.getElementById('again').addEventListener('click',()=>{\n"
                    . "  document.querySelector('.banner').style.display='';\n"
                    . "  document.getElementById('again').hidden=true;\n"
                    . '});',
                'body' => self::wrap(
                    self::card(
                        self::head('Cookie banner', 'It sits at the bottom of the page')
                        . '<div class="pad"><p class="sm mut">Accept, reject or choose - all three are one click, and closing the banner is not taken as consent.</p>'
                        . '<button class="btn" type="button" id="again" hidden style="margin-top:12px">Show the banner again</button></div>'
                    ),
                    '<div class="banner" role="dialog" aria-label="Cookie preferences">'
                    . '<div class="body"><h3>We use a few cookies</h3>'
                    . '<p>Some keep you signed in and the site working. Others tell us which pages people get stuck on. You decide about the second kind.</p></div>'
                    . '<div class="acts">'
                    . '<button class="btn" type="button" data-dismiss>Reject all</button>'
                    . '<button class="btn" type="button" id="customise">Customise</button>'
                    . '<button class="btn pri" type="button" data-dismiss>Accept all</button></div>'
                    . '<div class="more" id="more" hidden>'
                    . '<div class="crow"><span><b>Strictly necessary</b><div class="xs mut">Sign-in, security, language. Cannot be turned off.</div></span>'
                    . '<input type="checkbox" checked disabled aria-label="Strictly necessary cookies"></div>'
                    . '<div class="crow"><span><b>Analytics</b><div class="xs mut">Which pages are visited, aggregated and never sold.</div></span>'
                    . '<input type="checkbox" checked aria-label="Analytics cookies"></div>'
                    . '<div class="crow"><span><b>Preferences</b><div class="xs mut">Remembers your theme and editor layout.</div></span>'
                    . '<input type="checkbox" checked aria-label="Preference cookies"></div>'
                    . '<button class="btn pri" type="button" data-dismiss>Save my choices</button>'
                    . '</div></div>'
                ),
            ],
            [
                'slug' => 'file-upload-card',
                'name' => 'File cards',
                'name_ar' => 'بطاقات الملفات',
                'tagline' => 'File rows and tiles with type marks and actions.',
                'tagline_ar' => 'صفوف ومربعات ملفات بعلامات للنوع وإجراءات.',
                'summary' => 'Files shown two ways from one set of facts - a dense row for a list and a tile for a grid - with the type carried by a coloured mark rather than by reading the extension.',
                'summary_ar' => 'ملفات تُعرض بطريقتين من مجموعة البيانات نفسها: صف مكثف للقائمة ومربع للشبكة، ويُعرف النوع من علامة ملوّنة لا من قراءة الامتداد.',
                'accent' => '#2563eb',
                'tags' => ['files', 'cards', 'list', 'storage'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Row and tile layouts from the same data', 'Type marks coloured by file kind', 'Hover actions that do not shift the layout'],
                'features_ar' => ['تخطيطا الصف والمربع من البيانات نفسها', 'علامات ملوّنة حسب نوع الملف', 'إجراءات عند التمرير فوقه لا تحرّك التخطيط'],
                'height' => 660,
                'max' => 820,
                'css' => ".frow{display:flex;align-items:center;gap:13px;padding:12px 14px;border:1px solid var(--bd);border-radius:12px}\n.frow:hover{border-color:var(--acc)}\n.frow .meta{flex:1;min-width:0}\n.frow .meta b{display:block;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}\n.frow .acts{display:flex;gap:2px;opacity:0;transition:opacity .15s}\n.frow:hover .acts,.frow:focus-within .acts{opacity:1}\n.ftile{border:1px solid var(--bd);border-radius:13px;padding:16px;text-align:center}\n.ftile:hover{border-color:var(--acc)}\n.ftile b{display:block;font-size:13px;margin-top:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}\n.grp{padding:18px 20px;border-bottom:1px solid var(--bd)}\n.grp:last-child{border-bottom:0}\n.glabel{display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);font-weight:700;margin-bottom:12px}",
                'body' => self::wrap(
                    self::card(
                        self::head('Files', 'The same five files, two layouts')
                        . '<div class="grp"><span class="glabel">List</span><div style="display:grid;gap:9px">'
                        . self::fileRow('file', '#dc2626', 'brand-guide-2026.pdf', '4.2 MB · PDF · 2 days ago')
                        . self::fileRow('image', '#0891b2', 'hero-render.png', '1.8 MB · Image · 5 hours ago')
                        . self::fileRow('chart', '#059669', 'q3-report.xlsx', '812 KB · Spreadsheet · last week')
                        . self::fileRow('play', '#7c3aed', 'launch-clip.mp4', '64.7 MB · Video · yesterday')
                        . self::fileRow('code', '#475569', 'tokens.json', '14 KB · Data · 3 weeks ago')
                        . '</div></div>'
                        . '<div class="grp"><span class="glabel">Grid</span>'
                        . '<div style="display:grid;gap:11px;grid-template-columns:repeat(auto-fit,minmax(128px,1fr))">'
                        . self::fileTile('file', '#dc2626', 'brand-guide-2026.pdf', '4.2 MB')
                        . self::fileTile('image', '#0891b2', 'hero-render.png', '1.8 MB')
                        . self::fileTile('chart', '#059669', 'q3-report.xlsx', '812 KB')
                        . self::fileTile('play', '#7c3aed', 'launch-clip.mp4', '64.7 MB')
                        . self::fileTile('code', '#475569', 'tokens.json', '14 KB')
                        . '</div></div>'
                    )
                ),
            ],
        ];
    }

    /* ---------------------------------------------------------------- */

    private static function paletteItem(string $icon, string $label, string $keywords, string $shortcut): string
    {
        $kbd = $shortcut === '' ? '' : '<kbd>' . $shortcut . '</kbd>';

        return '<button class="pitem" type="button" data-k="' . $keywords . '">' . Kit::icon($icon, 16) . $label . $kbd . '</button>';
    }

    /** A September 2026 month grid, with the states a picker has to handle. */
    private static function calendarDays(): string
    {
        $out = '';
        foreach ([30, 31] as $day) {
            $out .= '<button class="day out" type="button" aria-pressed="false">' . $day . '</button>';
        }
        for ($day = 1; $day <= 30; $day++) {
            $classes = 'day';
            if ($day === 20) {
                $classes .= ' today';
            }
            $disabled = in_array($day, [5, 6, 12, 13], true);
            $selected = $day === 24 ? 'true' : 'false';
            $out .= '<button class="' . $classes . '" type="button" aria-pressed="' . $selected . '"'
                . ($disabled ? ' disabled' : '') . '>' . $day . '</button>';
        }
        foreach ([1, 2, 3] as $day) {
            $out .= '<button class="day out" type="button" aria-pressed="false">' . $day . '</button>';
        }

        return $out;
    }

    private static function planCard(string $name, string $price, string $per, array $features, string $cta, bool $best): string
    {
        $items = '';
        foreach ($features as $i => $feature) {
            $lead = $i === 0 && str_contains($feature, 'plus:')
                ? '<li style="font-weight:650;color:var(--mut)">' . $feature . '</li>'
                : '<li>' . Kit::icon('check', 15, 2.4) . $feature . '</li>';
            $items .= $lead;
        }

        return '<div class="card plan' . ($best ? ' best' : '') . '" style="position:relative">'
            . ($best ? '<span class="tag">Most popular</span>' : '')
            . '<div class="body"><h3 style="font-size:15px">' . $name . '</h3>'
            . '<div class="price">' . $price . '</div><span class="xs mut">' . $per . '</span>'
            . '<ul>' . $items . '</ul></div>'
            . '<div class="cta"><button class="btn ' . ($best ? 'pri' : '') . '" type="button" style="width:100%;padding:11px">' . $cta . '</button></div></div>';
    }

    private static function productCard(string $name, string $category, string $price, string $was, float $rating, int $reviews, string $gradient, string $badge, string $stock): string
    {
        $old = $was === '' ? '' : '<span class="was">' . $was . '</span>';
        $tag = $badge === '' ? '' : '<span class="badge">' . $badge . '</span>';
        $warn = $stock === '' ? '' : '<span class="pill warn">' . $stock . '</span>';

        return '<div class="card prod"><div class="shot" style="background:' . $gradient . '">' . $tag
            . '<button class="fav" type="button" aria-label="Save ' . $name . '">' . Kit::icon('heart', 16) . '</button>' . $warn . '</div>'
            . '<div class="body"><span class="xs mut">' . $category . '</span>'
            . '<b style="font-size:13.5px">' . $name . '</b>'
            . '<span class="row" style="gap:7px">' . Kit::stars($rating, 13) . '<span class="xs mut">(' . $reviews . ')</span></span>'
            . '<span class="row" style="justify-content:space-between;margin-top:2px">'
            . '<span><span class="price num">' . $price . '</span>' . $old . '</span>'
            . '<button class="btn pri tiny add" type="button" aria-label="Add ' . $name . ' to the basket">' . Kit::icon('plus', 14) . 'Add</button></span>'
            . '</div></div>';
    }

    private static function timelineItem(string $icon, string $colour, string $when, string $title, string $detail, bool $pending = false): string
    {
        $mark = $pending
            ? '<span style="width:38px;height:38px;border-radius:12px;border:2px dashed var(--bd);display:flex;align-items:center;justify-content:center;color:var(--mut)">' . Kit::icon($icon, 17) . '</span>'
            : Kit::iconTile($icon, $colour, 38);

        return '<div class="item">' . $mark
            . '<span><span class="when">' . $when . '</span><h4>' . $title . '</h4><p>' . $detail . '</p></span></div>';
    }

    private static function kanbanCard(array $labels, string $title, string $subtasks, string $comments, string $files, string $due, bool $late, array $team): string
    {
        $strip = '';
        foreach ($labels as $colour) {
            $strip .= '<i style="background:' . $colour . '"></i>';
        }
        $bits = '<span class="bit">' . Kit::icon('check', 13) . $subtasks . '</span>';
        if ($comments !== '') {
            $bits .= '<span class="bit">' . Kit::icon('mail', 13) . $comments . '</span>';
        }
        if ($files !== '') {
            $bits .= '<span class="bit">' . Kit::icon('file', 13) . $files . '</span>';
        }
        $bits .= '<span class="bit ' . ($late ? 'late' : '') . '">' . Kit::icon('calendar', 13) . $due . '</span>';

        return '<div class="kcard"><div class="labels">' . $strip . '</div>'
            . '<h4>' . $title . '</h4>'
            . '<div class="kfoot">' . $bits . '<span style="margin-left:auto">' . Kit::avatarStack($team, 22) . '</span></div></div>';
    }

    /** Five radio-and-label pairs for a rating control, largest value first. */
    private static function ratingStars(string $name, string $path, int $size): string
    {
        $out = '';
        foreach ([5, 4, 3, 2, 1] as $value) {
            $out .= '<input type="radio" name="' . $name . '" id="' . $name . $value . '" value="' . $value . '">'
                . '<label for="' . $name . $value . '" aria-label="' . $value . ' out of 5">'
                . '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="currentColor" aria-hidden="true" style="display:block">'
                . '<path d="' . $path . '"/></svg></label>';
        }

        return $out;
    }

    private static function fileRow(string $icon, string $colour, string $name, string $meta): string
    {
        return '<div class="frow">' . Kit::iconTile($icon, $colour, 38)
            . '<span class="meta"><b>' . $name . '</b><span class="xs mut">' . $meta . '</span></span>'
            . '<span class="acts">'
            . '<button class="btn gh" type="button" aria-label="Download ' . $name . '" style="padding:7px">' . Kit::icon('download', 16) . '</button>'
            . '<button class="btn gh" type="button" aria-label="More options for ' . $name . '" style="padding:7px">' . Kit::icon('more', 16) . '</button>'
            . '</span></div>';
    }

    private static function fileTile(string $icon, string $colour, string $name, string $size): string
    {
        return '<div class="ftile"><span style="display:flex;justify-content:center">' . Kit::iconTile($icon, $colour, 46) . '</span>'
            . '<b>' . $name . '</b><span class="xs mut">' . $size . '</span></div>';
    }

    /** @return array<int,array<string,mixed>> */
    private static function setThree(): array
    {
        return [
            [
                'slug' => 'hero-section',
                'name' => 'Hero section',
                'name_ar' => 'قسم رئيسي',
                'tagline' => 'Headline, subhead, two actions and a drawn product shot.',
                'tagline_ar' => 'عنوان رئيسي وعنوان فرعي وإجراءان وصورة منتج مرسومة.',
                'summary' => 'A landing hero with a product shot drawn in the markup rather than dropped in as a screenshot, so it stays sharp, themes with the page and adds nothing to the page weight.',
                'summary_ar' => 'قسم رئيسي لصفحة هبوط بصورة منتج مرسومة في الترميز بدل لقطة شاشة، فتبقى حادة وتتبع سمة الصفحة ولا تضيف شيئًا إلى حجمها.',
                'accent' => '#2563eb',
                'tags' => ['hero', 'landing', 'marketing', 'header'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Product shot drawn in CSS, not an image file', 'Primary and secondary action with clear hierarchy', 'Stacks to one column under 820px'],
                'features_ar' => ['صورة المنتج مرسومة بـ CSS، لا ملف صورة', 'إجراء أساسي وثانوي بتسلسل هرمي واضح', 'يتحول إلى عمود واحد تحت 820px'],
                'height' => 620,
                'max' => 980,
                'css' => ".hero{display:grid;grid-template-columns:1.05fr .95fr;gap:38px;align-items:center;padding:44px 34px}\n.hero h1{font-size:40px;line-height:1.1;letter-spacing:-.03em}\n.hero .lede{font-size:16px;color:var(--mut);margin-top:16px;line-height:1.65;max-width:44ch}\n.hero .acts{display:flex;gap:11px;margin-top:26px;flex-wrap:wrap}\n.hero .proof{display:flex;align-items:center;gap:11px;margin-top:26px;font-size:12.5px;color:var(--mut)}\n.shot{background:linear-gradient(140deg,var(--acc),var(--acc-dk) 60%,#0f172a);border-radius:18px;padding:18px;box-shadow:0 26px 60px -28px rgba(15,23,42,.7)}\n.win{background:var(--card);border-radius:12px;overflow:hidden}\n.winbar{display:flex;gap:5px;padding:9px 11px;border-bottom:1px solid var(--bd)}\n.winbar i{width:9px;height:9px;border-radius:50%;background:var(--bd)}\n.winbody{padding:14px;display:grid;gap:9px}\n.ln{height:9px;border-radius:999px;background:var(--soft)}\n@media (max-width:820px){.hero{grid-template-columns:1fr;padding:32px 22px}.hero h1{font-size:31px}}",
                'body' => self::wrap(
                    self::card('<div class="hero"><div>'
                        . '<span class="pill info">' . Kit::icon('zap', 13) . '216 components and counting</span>'
                        . '<h1 style="margin-top:16px">Interface templates you can read in one sitting</h1>'
                        . '<p class="lede">Every component is a single HTML file. No build step, no dependencies, no CDN. Preview it, copy it, ship it.</p>'
                        . '<div class="acts">' . self::btn('Browse the gallery', 'arrow-right', 'pri') . self::btn('See how it works', 'play') . '</div>'
                        . '<div class="proof">' . Kit::avatarStack(['Lina Haddad', 'Omar Saleh', 'Maya Rahman', 'Sara Aziz'], 26)
                        . '<span>Used by <b>12,480</b> designers and developers</span></div>'
                        . '</div>'
                        . '<div class="shot"><div class="win"><div class="winbar"><i></i><i></i><i></i></div>'
                        . '<div class="winbody">'
                        . '<div class="ln" style="width:52%"></div>'
                        . '<div class="ln" style="width:88%;height:38px"></div>'
                        . '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:9px">'
                        . '<div class="ln" style="height:52px"></div><div class="ln" style="height:52px"></div><div class="ln" style="height:52px"></div></div>'
                        . '<div class="ln" style="width:70%"></div><div class="ln" style="width:44%"></div>'
                        . '</div></div></div></div>')
                ),
            ],
            [
                'slug' => 'feature-grid',
                'name' => 'Feature grid',
                'name_ar' => 'شبكة المزايا',
                'tagline' => 'Six feature tiles with icons and one-line benefits.',
                'tagline_ar' => 'ست بطاقات مزايا بأيقونات وفوائد في سطر واحد.',
                'summary' => 'A feature section where every tile leads with the benefit and follows with the mechanism, which is the order people read in. Six tiles is the ceiling before a grid stops being scanned and starts being skipped.',
                'summary_ar' => 'قسم مزايا يبدأ فيه كل مربع بالفائدة ثم الآلية، وهو الترتيب الذي يقرأ به الناس. ستة مربعات هي الحد الأقصى قبل أن تتحول الشبكة من شيء يُتصفَّح إلى شيء يُتجاوَز.',
                'accent' => '#7c3aed',
                'tags' => ['features', 'grid', 'marketing', 'icons'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Benefit first, mechanism second, in every tile', 'Icon tiles tinted from the accent', 'Three, two and one column as the width allows'],
                'features_ar' => ['الفائدة أولًا والآلية ثانيًا في كل مربع', 'مربعات أيقونات مصبوغة من اللون المميز', 'ثلاثة أعمدة ثم اثنان ثم واحد حسب العرض المتاح'],
                'height' => 640,
                'max' => 900,
                'css' => ".feat{padding:16px}\n.feat h3{font-size:14.5px;margin-top:14px}\n.feat p{font-size:13px;color:var(--mut);margin-top:6px;line-height:1.6}",
                'body' => self::wrap(
                    '<div style="text-align:center;margin-bottom:22px"><h1>Built to be copied</h1>'
                    . '<p class="sub" style="margin-top:7px">Every decision in this library follows from one rule: a template has to be readable before it is trusted.</p></div>',
                    self::grid(
                        250,
                        self::featureTile('file', Kit::SERIES[0], 'One file, no dependencies', 'Markup, styles and behaviour travel together. Nothing is fetched at runtime, so a template works offline and behind a strict content policy.'),
                        self::featureTile('image', Kit::SERIES[1], 'Pictures without image files', 'Avatars, charts and product shots are drawn as inline SVG or CSS gradients. They stay sharp at any size and never 404.'),
                        self::featureTile('moon', Kit::SERIES[2], 'Light and dark, from tokens', 'Four variables at the top of each file carry the whole theme. Retheming a component takes a minute, not a rewrite.'),
                        self::featureTile('users', Kit::SERIES[3], 'Accessible by construction', 'Real labels, visible focus rings, keyboard paths and states that do not rely on colour alone.'),
                        self::featureTile('globe', Kit::SERIES[4], 'Arabic and right-to-left', 'Every component carries an Arabic name and uses logical properties, so adding dir="rtl" flips it properly.'),
                        self::featureTile('code', Kit::SERIES[6], 'Readable in one sitting', 'No framework, no minified blob. You can read a whole template before pasting it into a client project.')
                    )
                ),
            ],
            [
                'slug' => 'cta-banner',
                'name' => 'Call to action banner',
                'name_ar' => 'شريط دعوة للإجراء',
                'tagline' => 'Three closing blocks - gradient, bordered and split.',
                'tagline_ar' => 'ثلاث كتل ختامية: متدرجة ومؤطرة ومقسومة.',
                'summary' => 'Three ways to end a page, each with one clear action and a line that removes the last objection - no card needed, cancel any time, free for ever. The secondary action never competes visually with the primary one.',
                'summary_ar' => 'ثلاث طرق لختام صفحة، لكل منها إجراء واضح واحد وسطر يزيل آخر اعتراض: لا حاجة لبطاقة، إلغاء في أي وقت، مجاني للأبد. ولا ينافس الإجراء الثانوي الأساسي بصريًا أبدًا.',
                'accent' => '#059669',
                'tags' => ['cta', 'banner', 'conversion', 'marketing'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['One primary action per banner, never two', 'Objection-removing line under the button', 'Gradient, bordered and split variants'],
                'features_ar' => ['إجراء أساسي واحد لكل شريط، لا اثنان أبدًا', 'سطر يزيل الاعتراض تحت الزر', 'أنواع متدرجة ومؤطرة ومقسومة'],
                'height' => 680,
                'max' => 820,
                'css' => ".ctag{background:linear-gradient(135deg,var(--acc),var(--acc-dk) 60%,#0f172a);color:#fff;border-radius:18px;padding:34px 28px;text-align:center}\n.ctag h2{font-size:25px;letter-spacing:-.02em}\n.ctag p{opacity:.86;margin-top:9px;font-size:14px}\n.ctag .btn{background:#fff;border-color:#fff;color:var(--acc);margin-top:20px;padding:12px 22px;font-size:14.5px}\n.ctag .fine{font-size:11.5px;opacity:.75;margin-top:11px}\n.ctab{border:1px solid var(--bd);border-radius:18px;padding:28px;text-align:center}\n.ctas{display:grid;grid-template-columns:1fr auto;gap:22px;align-items:center;border:1px solid var(--bd);border-radius:18px;padding:24px 26px}\n@media (max-width:620px){.ctas{grid-template-columns:1fr;text-align:center}}",
                'body' => self::wrap(
                    '<div class="ctag"><h2>Start with two hundred components</h2>'
                    . '<p>Free for ever. No card, no trial clock, no seat limit on the free plan.</p>'
                    . '<button class="btn" type="button">' . Kit::icon('arrow-right', 17) . 'Open the gallery</button>'
                    . '<div class="fine">12,480 people already have</div></div>',
                    '<div style="height:14px"></div>',
                    '<div class="ctab"><h2 style="font-size:21px">Want these in your design system?</h2>'
                    . '<p class="sub" style="margin-top:8px">We can theme the whole library to your tokens and hand it over as a repository.</p>'
                    . '<div class="row" style="gap:10px;justify-content:center;margin-top:18px">'
                    . self::btn('Talk to us', 'mail', 'pri') . self::btn('See an example') . '</div></div>',
                    '<div style="height:14px"></div>',
                    '<div class="ctas"><div><h3 style="font-size:16px">Get new components by email</h3>'
                    . '<p class="sub" style="margin-top:6px">A short note every other Tuesday. Unsubscribe in one click.</p></div>'
                    . '<div class="row" style="gap:9px"><input class="in" type="email" placeholder="you@company.com" aria-label="Email address" style="width:220px">'
                    . self::btn('Subscribe', '', 'pri') . '</div></div>'
                ),
            ],
            [
                'slug' => 'footer-columns',
                'name' => 'Site footer',
                'name_ar' => 'تذييل الموقع',
                'tagline' => 'Link columns, newsletter, social row and legal line.',
                'tagline_ar' => 'أعمدة روابط ونشرة بريدية وصف للتواصل الاجتماعي وسطر قانوني.',
                'summary' => 'A site footer with everything in the place people expect it: link columns grouped by intent, a subscribe field, social links as labelled icons, and the legal line last in smaller type.',
                'summary_ar' => 'تذييل موقع يضع كل شيء حيث يتوقعه الناس: أعمدة روابط مجمَّعة حسب الغرض، وحقل اشتراك، وروابط التواصل الاجتماعي كأيقونات بتسميات، والسطر القانوني في النهاية بخط أصغر.',
                'accent' => '#475569',
                'tags' => ['footer', 'navigation', 'links', 'layout'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Link columns grouped by intent', 'Subscribe field inside the footer', 'Social icons with accessible labels'],
                'features_ar' => ['أعمدة روابط مجمَّعة حسب الغرض', 'حقل اشتراك داخل التذييل', 'أيقونات التواصل الاجتماعي بتسميات سهلة الوصول'],
                'height' => 680,
                'max' => 900,
                'css' => ".foot{padding:32px 26px 22px}\n.cols{display:grid;grid-template-columns:1.6fr repeat(3,1fr);gap:30px}\n.cols h4{font-size:12px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);margin-bottom:12px}\n.cols a{display:block;font-size:13.5px;color:var(--ink);padding:5px 0}\n.cols a:hover{color:var(--acc);text-decoration:none}\n.brand{display:flex;align-items:center;gap:10px;font-weight:750;font-size:16px}\n.mark{width:32px;height:32px;border-radius:10px;background:linear-gradient(140deg,var(--acc),var(--acc-dk));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:14px}\n.sub{max-width:34ch}\n.subrow{display:flex;gap:8px;margin-top:14px;max-width:300px}\n.legal{display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;margin-top:28px;padding-top:18px;border-top:1px solid var(--bd);font-size:12px;color:var(--mut)}\n.social{display:flex;gap:6px}\n.social a{width:34px;height:34px;border:1px solid var(--bd);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--mut)}\n.social a:hover{border-color:var(--acc);color:var(--acc)}\n@media (max-width:760px){.cols{grid-template-columns:1fr 1fr}}",
                'body' => self::wrap(
                    self::card('<div class="foot"><div class="cols">'
                        . '<div><span class="brand"><span class="mark">F</span>Frugal</span>'
                        . '<p class="sub" style="margin-top:12px;font-size:13px;color:var(--mut);line-height:1.6">Single-file interface templates, a vector editor and a few thousand icons. Free, and free of dependencies.</p>'
                        . '<div class="subrow"><input class="in" type="email" placeholder="you@company.com" aria-label="Email address">'
                        . self::btn('Join', '', 'pri') . '</div></div>'
                        . '<div><h4>Product</h4><a href="#">Components</a><a href="#">Drawing editor</a><a href="#">Icons</a><a href="#">File conversion</a><a href="#">Pricing</a></div>'
                        . '<div><h4>Resources</h4><a href="#">Documentation</a><a href="#">Changelog</a><a href="#">Status</a><a href="#">API reference</a><a href="#">Blog</a></div>'
                        . '<div><h4>Company</h4><a href="#">About</a><a href="#">Contact</a><a href="#">Privacy</a><a href="#">Terms</a></div>'
                        . '</div>'
                        . '<div class="legal"><span>&copy; 2026 Frugal. All rights reserved.</span>'
                        . '<span class="social">'
                        . '<a href="#" aria-label="Frugal on X">' . Kit::icon('send', 16) . '</a>'
                        . '<a href="#" aria-label="Frugal on GitHub">' . Kit::icon('code', 16) . '</a>'
                        . '<a href="#" aria-label="Frugal on YouTube">' . Kit::icon('play', 16) . '</a>'
                        . '<a href="#" aria-label="Email us">' . Kit::icon('mail', 16) . '</a></span></div></div>')
                ),
            ],
            [
                'slug' => 'logo-cloud',
                'name' => 'Logo cloud',
                'name_ar' => 'شعارات العملاء',
                'tagline' => 'Customer marks drawn as type, not fetched as images.',
                'tagline_ar' => 'شعارات العملاء مرسومة بالحروف، لا مجلوبة كصور.',
                'summary' => 'A trusted-by row that carries no image requests: each mark is a letterform in a tinted tile. It reads as a logo wall, stays crisp, and can be replaced with a real SVG later without changing the layout.',
                'summary_ar' => 'صف «يثق بنا» دون أي طلبات صور: كل شعار حرف داخل مربع مصبوغ. يبدو كجدار شعارات، ويبقى حادًا، ويمكن استبداله لاحقًا بملف SVG حقيقي دون تغيير التخطيط.',
                'accent' => '#475569',
                'tags' => ['logos', 'social-proof', 'marketing', 'row'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Marks drawn as letterforms, no image files', 'Even optical spacing at any count', 'Muted until hover, so it never outshouts the headline'],
                'features_ar' => ['شعارات مرسومة بالحروف، دون ملفات صور', 'تباعد بصري متوازن مهما كان العدد', 'باهتة حتى التمرير فوقها، فلا تطغى على العنوان أبدًا'],
                'height' => 420,
                'max' => 820,
                'css' => ".cloud{display:flex;flex-wrap:wrap;gap:14px;justify-content:center;padding:26px 22px}\n.logo{display:flex;align-items:center;gap:10px;padding:11px 16px;border:1px solid var(--bd);border-radius:12px;opacity:.72;transition:.16s}\n.logo:hover{opacity:1;border-color:var(--acc)}\n.logo .m{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:13px}\n.logo b{font-size:14px;letter-spacing:-.01em}",
                'body' => self::wrap(
                    self::card(
                        '<div style="text-align:center;padding:24px 20px 0">'
                        . '<span class="xs mut" style="text-transform:uppercase;letter-spacing:.08em;font-weight:700">Trusted by teams at</span></div>'
                        . '<div class="cloud">'
                        . self::logoMark('Northwind', '#2a78d6', 'N')
                        . self::logoMark('Bluebird', '#1baf7a', 'B')
                        . self::logoMark('Harbor', '#eb6834', 'H')
                        . self::logoMark('Copper &amp; Co', '#c2410c', 'C')
                        . self::logoMark('Juno Labs', '#4a3aa7', 'J')
                        . self::logoMark('Redwood', '#e34948', 'R')
                        . self::logoMark('Skyline', '#0891b2', 'S')
                        . self::logoMark('Marjan', '#eda100', 'M')
                        . '</div>'
                        . '<div class="ft" style="justify-content:center"><span>Replace each mark with a real SVG when you have one - the layout does not change</span></div>'
                    )
                ),
            ],
            [
                'slug' => 'stat-band',
                'name' => 'Statistics band',
                'name_ar' => 'شريط الأرقام',
                'tagline' => 'Four big numbers with captions and sources.',
                'tagline_ar' => 'أربعة أرقام كبيرة مع شروح ومصادر.',
                'summary' => 'A marketing stat band where each number carries a caption that says what it counts and, where it matters, when it was measured. A number without a denominator is a decoration, not evidence.',
                'summary_ar' => 'شريط أرقام تسويقي يحمل فيه كل رقم شرحًا لما يعدّه، ومتى قيس حين يكون ذلك مهمًا. الرقم بلا أساس للمقارنة زينة لا دليل.',
                'accent' => '#0891b2',
                'tags' => ['stats', 'numbers', 'marketing', 'band'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Captions that say what each number counts', 'Measured-at note where the figure moves', 'Divided band that collapses to a grid'],
                'features_ar' => ['شروح توضح ما يعدّه كل رقم', 'ملاحظة بتاريخ القياس حين يتغير الرقم', 'شريط مقسَّم يتحول إلى شبكة'],
                'height' => 420,
                'max' => 900,
                'css' => ".band{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--bd);border-radius:16px;overflow:hidden}\n.band div{background:var(--card);padding:26px 20px;text-align:center}\n.band .n{font-size:34px;font-weight:750;letter-spacing:-.03em;line-height:1;color:var(--acc)}\n.band .c{font-size:13px;font-weight:650;margin-top:9px}\n.band .s{font-size:11px;color:var(--mut);margin-top:4px}\n@media (max-width:700px){.band{grid-template-columns:1fr 1fr}}",
                'body' => self::wrap(
                    '<div style="text-align:center;margin-bottom:20px"><h1>The library in numbers</h1></div>',
                    '<div class="band">'
                    . '<div><div class="n num">216</div><div class="c">Components</div><div class="s">across four categories</div></div>'
                    . '<div><div class="n num">300</div><div class="c">Drawing templates</div><div class="s">all fully editable</div></div>'
                    . '<div><div class="n num">0</div><div class="c">Dependencies</div><div class="s">in every single file</div></div>'
                    . '<div><div class="n num">12.4k</div><div class="c">People using them</div><div class="s">measured September 2026</div></div>'
                    . '</div>'
                ),
            ],
            [
                'slug' => 'team-grid',
                'name' => 'Team grid',
                'name_ar' => 'شبكة الفريق',
                'tagline' => 'People cards with role, location and contact links.',
                'tagline_ar' => 'بطاقات أشخاص بالمنصب والموقع وروابط التواصل.',
                'summary' => 'A team page where each card gives a role, a place and a way to reach the person. Avatars are drawn from initials, so a new hire appears correctly before anyone has taken their photograph.',
                'summary_ar' => 'صفحة فريق تعطي فيها كل بطاقة المنصب والمكان وطريقة للتواصل مع الشخص. الصور الرمزية مرسومة من الأحرف الأولى، فيظهر الموظف الجديد بشكل صحيح قبل أن يلتقط أحد صورته.',
                'accent' => '#4f46e5',
                'tags' => ['team', 'people', 'about', 'grid'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Drawn avatars, correct before a photo exists', 'Role, location and contact on every card', 'Grid that reflows from four columns to one'],
                'features_ar' => ['صور رمزية مرسومة، صحيحة قبل وجود صورة', 'المنصب والموقع ووسيلة التواصل في كل بطاقة', 'شبكة تنتقل من أربعة أعمدة إلى عمود واحد'],
                'height' => 640,
                'max' => 900,
                'css' => ".person{text-align:center;padding:22px 16px}\n.person h3{font-size:14.5px;margin-top:13px}\n.person .role{font-size:12.5px;color:var(--acc);font-weight:650;margin-top:3px}\n.person .where{font-size:11.5px;color:var(--mut);margin-top:6px}\n.person .links{display:flex;gap:6px;justify-content:center;margin-top:13px}\n.person .links a{width:32px;height:32px;border:1px solid var(--bd);border-radius:9px;display:flex;align-items:center;justify-content:center;color:var(--mut)}\n.person .links a:hover{border-color:var(--acc);color:var(--acc)}",
                'body' => self::wrap(
                    '<div style="text-align:center;margin-bottom:20px"><h1>The team</h1>'
                    . '<p class="sub" style="margin-top:7px">Nine people across four countries, most of whom have never met in person.</p></div>',
                    self::grid(
                        190,
                        self::personCard('Lina Haddad', 'Head of design', 'Beirut, Lebanon'),
                        self::personCard('Omar Saleh', 'Staff engineer', 'Amman, Jordan'),
                        self::personCard('Maya Rahman', 'QA lead', 'Remote'),
                        self::personCard('Sara Aziz', 'Content lead', 'Cairo, Egypt'),
                        self::personCard('Karim Nasser', 'Support lead', 'Dubai, UAE'),
                        self::personCard('Nour Sabbagh', 'Platform engineer', 'Remote'),
                        self::personCard('Yusuf Barak', 'Designer', 'Riyadh, Saudi Arabia'),
                        self::personCard('Hala Mansour', 'Operations', 'Doha, Qatar')
                    )
                ),
            ],
            [
                'slug' => 'gallery-grid',
                'name' => 'Image gallery grid',
                'name_ar' => 'شبكة معرض الصور',
                'tagline' => 'Masonry-ish grid with hover captions and a count badge.',
                'tagline_ar' => 'شبكة شبه متدرجة بتعليقات عند التمرير وشارة عدد.',
                'summary' => 'A gallery built from CSS grid spans rather than a masonry library, with captions that appear on hover and remain reachable by keyboard. The tiles are gradients here so the file stays self-contained.',
                'summary_ar' => 'معرض مبني على امتدادات CSS grid بدل مكتبة masonry، بتعليقات تظهر عند التمرير فوقها وتبقى في متناول لوحة المفاتيح. المربعات هنا تدرجات لونية ليبقى الملف مستقلًا بذاته.',
                'accent' => '#7c3aed',
                'tags' => ['gallery', 'grid', 'images', 'masonry'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Varied tile sizes with plain CSS grid spans', 'Captions on hover and on focus', 'Overlay count on the last tile'],
                'features_ar' => ['أحجام مربعات متنوعة بامتدادات CSS grid بسيطة', 'تعليقات عند التمرير فوقها وعند التركيز', 'عدد مركَّب فوق المربع الأخير'],
                'height' => 660,
                'max' => 820,
                'css' => ".gal{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:110px;gap:10px;padding:18px}\n.gtile{border-radius:13px;position:relative;overflow:hidden;cursor:pointer}\n.gtile .cap{position:absolute;inset:auto 0 0 0;padding:20px 12px 11px;background:linear-gradient(transparent,rgba(15,23,42,.78));color:#fff;font-size:12px;font-weight:600;opacity:0;transition:opacity .16s}\n.gtile:hover .cap,.gtile:focus-visible .cap{opacity:1}\n.gtile.w2{grid-column:span 2}\n.gtile.h2{grid-row:span 2}\n.gtile .more{position:absolute;inset:0;background:rgba(15,23,42,.62);color:#fff;display:flex;align-items:center;justify-content:center;font-size:19px;font-weight:700}\n@media (max-width:620px){.gal{grid-template-columns:repeat(2,1fr)}}",
                'body' => self::wrap(
                    self::card(
                        self::head('Gallery', '42 images · Aurora Redesign', self::btn('Upload', 'upload', 'pri tiny'))
                        . '<div class="gal">'
                        . self::galleryTile('linear-gradient(140deg,#7c3aed,#c084fc)', 'Concept sketch', 'w2 h2')
                        . self::galleryTile('linear-gradient(140deg,#2563eb,#60a5fa)', 'Hero exploration', '')
                        . self::galleryTile('linear-gradient(140deg,#0f766e,#14b8a6)', 'Colour study', '')
                        . self::galleryTile('linear-gradient(140deg,#b45309,#f59e0b)', 'Type specimen', 'w2')
                        . self::galleryTile('linear-gradient(140deg,#be123c,#fb7185)', 'Icon set', '')
                        . self::galleryTile('linear-gradient(140deg,#334155,#64748b)', 'Wireframes', '')
                        . self::galleryTile('linear-gradient(140deg,#0891b2,#67e8f9)', 'Motion frames', 'w2')
                        . '<div class="gtile" style="background:linear-gradient(140deg,#475569,#94a3b8)"><span class="more">+34</span></div>'
                        . '</div>'
                    )
                ),
            ],
            [
                'slug' => 'carousel-slider',
                'name' => 'Carousel',
                'name_ar' => 'شريط عرض متحرك',
                'tagline' => 'Scroll-snap slides with dots, arrows and no library.',
                'tagline_ar' => 'شرائح بالتمرير المنضبط مع نقاط وأسهم ودون مكتبات.',
                'summary' => 'A carousel built on CSS scroll snap, so it swipes natively on a phone, scrolls with a trackpad, and needs about ten lines of JavaScript for the arrows and dots rather than a dependency.',
                'summary_ar' => 'شريط عرض مبني على CSS scroll snap، فيُسحب بسلاسة على الهاتف ويتمرر بلوحة اللمس، ولا يحتاج إلا إلى نحو عشرة أسطر JavaScript للأسهم والنقاط بدل اعتمادية كاملة.',
                'accent' => '#0891b2',
                'tags' => ['carousel', 'slider', 'scroll-snap', 'gallery'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['CSS scroll snap - native swipe and trackpad support', 'Dots reflect the slide actually in view', 'Arrows disable at the ends rather than wrapping'],
                'features_ar' => ['CSS scroll snap: سحب أصلي ودعم لوحة اللمس', 'النقاط تعكس الشريحة الظاهرة فعلًا', 'الأسهم تتعطل عند الطرفين بدل الالتفاف'],
                'height' => 600,
                'max' => 720,
                'css' => ".track{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding:18px;scrollbar-width:none}\n.track::-webkit-scrollbar{display:none}\n.slide{flex:0 0 100%;scroll-snap-align:center;border-radius:15px;height:250px;display:flex;flex-direction:column;justify-content:flex-end;padding:22px;color:#fff}\n.slide h3{font-size:19px}\n.slide p{font-size:13px;opacity:.88;margin-top:6px}\n.carfoot{display:flex;align-items:center;justify-content:space-between;padding:0 18px 18px}\n.dots{display:flex;gap:7px}\n.dots button{width:8px;height:8px;border-radius:999px;border:0;background:var(--bd);cursor:pointer;padding:0;transition:.16s}\n.dots button[aria-current=true]{background:var(--acc);width:22px}\n.arrows{display:flex;gap:7px}",
                'js' => "const track=document.querySelector('.track');\n"
                    . "const slides=[...track.children];\n"
                    . "const dots=[...document.querySelectorAll('.dots button')];\n"
                    . "function go(index){track.scrollTo({left:slides[index].offsetLeft-track.offsetLeft,behavior:'smooth'});}\n"
                    . "dots.forEach((dot,index)=>dot.addEventListener('click',()=>go(index)));\n"
                    . "document.getElementById('prev').addEventListener('click',()=>go(Math.max(0,current()-1)));\n"
                    . "document.getElementById('next').addEventListener('click',()=>go(Math.min(slides.length-1,current()+1)));\n"
                    . "function current(){\n"
                    . "  const middle=track.scrollLeft+track.clientWidth/2;\n"
                    . "  return slides.findIndex(slide=>slide.offsetLeft-track.offsetLeft+slide.clientWidth>middle);\n"
                    . "}\n"
                    . "track.addEventListener('scroll',()=>{\n"
                    . "  const index=current();\n"
                    . "  dots.forEach((dot,i)=>dot.setAttribute('aria-current',String(i===index)));\n"
                    . "  document.getElementById('prev').disabled=index<=0;\n"
                    . "  document.getElementById('next').disabled=index>=slides.length-1;\n"
                    . '});',
                'body' => self::wrap(
                    self::card(
                        self::head('What is new', 'Swipe, scroll or use the arrows')
                        . '<div class="track">'
                        . '<div class="slide" style="background:linear-gradient(140deg,#0891b2,#0f766e)"><h3>Templates in the editor</h3>'
                        . '<p>Start from a layout instead of a blank canvas. Everything arrives as editable geometry.</p></div>'
                        . '<div class="slide" style="background:linear-gradient(140deg,#7c3aed,#4f46e5)"><h3>Two hundred components</h3>'
                        . '<p>Tables, forms, dashboards and UI elements. One file each, nothing to install.</p></div>'
                        . '<div class="slide" style="background:linear-gradient(140deg,#059669,#0d9488)"><h3>Dark mode everywhere</h3>'
                        . '<p>Every template ships both themes, driven by four variables at the top of the file.</p></div>'
                        . '</div>'
                        . '<div class="carfoot"><span class="dots">'
                        . '<button type="button" aria-current="true" aria-label="Slide 1"></button>'
                        . '<button type="button" aria-current="false" aria-label="Slide 2"></button>'
                        . '<button type="button" aria-current="false" aria-label="Slide 3"></button></span>'
                        . '<span class="arrows">'
                        . '<button class="btn" type="button" id="prev" aria-label="Previous slide" disabled style="padding:8px">' . Kit::icon('chevron-left', 16) . '</button>'
                        . '<button class="btn" type="button" id="next" aria-label="Next slide" style="padding:8px">' . Kit::icon('chevron-right', 16) . '</button></span></div>'
                    )
                ),
            ],
            [
                'slug' => 'video-player-card',
                'name' => 'Video player card',
                'name_ar' => 'بطاقة مشغل فيديو',
                'tagline' => 'Poster, scrub bar, time and full player controls.',
                'tagline_ar' => 'صورة غلاف وشريط تقدم ووقت وعناصر تحكم كاملة.',
                'summary' => 'The chrome around a video: a poster with a play target big enough to hit on a phone, a scrub bar showing buffered against played, and controls that stay legible over any frame thanks to the gradient beneath them.',
                'summary_ar' => 'الإطار المحيط بالفيديو: صورة غلاف بزر تشغيل كبير يسهل لمسه على الهاتف، وشريط تقدم يُظهر المخزَّن مقابل المشغَّل، وعناصر تحكم تبقى مقروءة فوق أي إطار بفضل التدرج تحتها.',
                'accent' => '#e11d48',
                'tags' => ['video', 'player', 'media', 'controls'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Scrub bar showing played against buffered', 'Controls legible over any frame', 'Play and mute states toggle in place'],
                'features_ar' => ['شريط تقدم يُظهر المشغَّل مقابل المخزَّن', 'عناصر تحكم مقروءة فوق أي إطار', 'حالتا التشغيل وكتم الصوت تتبدلان في مكانهما'],
                'height' => 560,
                'max' => 680,
                'css' => ".player{position:relative;border-radius:16px;overflow:hidden;background:linear-gradient(140deg,#1e293b,#0f172a)}\n.poster{height:280px;display:flex;align-items:center;justify-content:center;background:linear-gradient(140deg,#e11d48,#7c3aed 60%,#0f172a)}\n.playbtn{width:68px;height:68px;border-radius:50%;border:0;background:rgba(255,255,255,.92);color:#0f172a;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.3)}\n.playbtn:hover{transform:scale(1.05)}\n.ctrls{position:absolute;left:0;right:0;bottom:0;padding:26px 14px 12px;background:linear-gradient(transparent,rgba(15,23,42,.9));color:#fff}\n.scrub{position:relative;height:5px;border-radius:999px;background:rgba(255,255,255,.26);cursor:pointer}\n.scrub .buf{position:absolute;inset:0;width:68%;background:rgba(255,255,255,.34);border-radius:999px}\n.scrub .played{position:absolute;inset:0;width:34%;background:var(--acc);border-radius:999px}\n.scrub .knob{position:absolute;left:34%;top:50%;transform:translate(-50%,-50%);width:13px;height:13px;border-radius:50%;background:#fff}\n.crow{display:flex;align-items:center;gap:13px;margin-top:11px;font-size:12px;font-variant-numeric:tabular-nums}\n.crow button{border:0;background:none;color:#fff;cursor:pointer;display:inline-flex;padding:2px}\n.crow .right{margin-left:auto;display:flex;gap:13px;align-items:center}",
                'js' => "const play=document.getElementById('play');\n"
                    . "const big=document.getElementById('big');\n"
                    . "let playing=false;\n"
                    . "function toggle(){\n"
                    . "  playing=!playing;\n"
                    . "  const icon=playing?'<svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"><path d=\"M9 5v14M15 5v14\"/></svg>'\n"
                    . "    :'<svg viewBox=\"0 0 24 24\" width=\"18\" height=\"18\" fill=\"currentColor\"><path d=\"M8 5.5v13l11-6.5Z\"/></svg>';\n"
                    . "  play.innerHTML=icon;\n"
                    . "  play.setAttribute('aria-label',playing?'Pause':'Play');\n"
                    . "  big.style.display=playing?'none':'flex';\n"
                    . "}\n"
                    . "play.addEventListener('click',toggle);\nbig.addEventListener('click',toggle);",
                'body' => self::wrap(
                    self::card(
                        '<div class="player"><div class="poster">'
                        . '<button class="playbtn" type="button" id="big" aria-label="Play the video">'
                        . '<svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5Z"/></svg></button></div>'
                        . '<div class="ctrls">'
                        . '<div class="scrub" role="slider" aria-label="Seek" aria-valuenow="34" aria-valuemin="0" aria-valuemax="100" tabindex="0">'
                        . '<span class="buf"></span><span class="played"></span><span class="knob"></span></div>'
                        . '<div class="crow">'
                        . '<button type="button" id="play" aria-label="Play"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5.5v13l11-6.5Z"/></svg></button>'
                        . '<button type="button" aria-label="Mute">' . Kit::icon('zap', 17) . '</button>'
                        . '<span>4:52 / 14:22</span>'
                        . '<span class="right"><button type="button" aria-label="Captions">CC</button>'
                        . '<button type="button" aria-label="Settings">' . Kit::icon('settings', 17) . '</button>'
                        . '<button type="button" aria-label="Full screen">' . Kit::icon('external', 17) . '</button></span>'
                        . '</div></div></div>',
                        'padding:0;overflow:hidden'
                    ),
                    '<div style="height:14px"></div>',
                    self::card('<div class="pad"><h3>Drawing editor walkthrough</h3>'
                        . '<p class="sub" style="margin-top:6px">184,210 views · published 12 September 2026</p></div>')
                ),
            ],
            [
                'slug' => 'audio-player-card',
                'name' => 'Audio player card',
                'name_ar' => 'بطاقة مشغل صوت',
                'tagline' => 'Waveform scrubber with transport controls.',
                'tagline_ar' => 'شريط تقدم على شكل موجة صوتية مع أزرار التشغيل.',
                'summary' => 'A compact audio player with the waveform as the scrub bar - played bars in the accent, unplayed in the track colour. The waveform is drawn from an array, so any clip can be represented without an image.',
                'summary_ar' => 'مشغل صوت مدمج تعمل فيه الموجة الصوتية كشريط تقدم: الأعمدة المشغَّلة باللون المميز وغير المشغَّلة بلون المسار. الموجة مرسومة من مصفوفة، فيمكن تمثيل أي مقطع دون صورة.',
                'accent' => '#7c3aed',
                'tags' => ['audio', 'player', 'waveform', 'media'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Waveform drawn from data, doubling as the scrubber', 'Click anywhere on the wave to seek', 'Speed and skip controls in the transport'],
                'features_ar' => ['موجة مرسومة من البيانات تعمل أيضًا كشريط تقدم', 'انقر في أي موضع على الموجة للانتقال إليه', 'أزرار السرعة والتخطي ضمن أدوات التشغيل'],
                'height' => 520,
                'max' => 560,
                'css' => ".wave{display:flex;align-items:center;gap:2px;height:56px;cursor:pointer}\n.wave i{flex:1;border-radius:999px;background:var(--bd);transition:background .1s}\n.wave i.on{background:var(--acc)}\n.transport{display:flex;align-items:center;gap:8px;justify-content:center;margin-top:16px}\n.transport button{border:1px solid var(--bd);background:var(--card);border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ink)}\n.transport button:hover{border-color:var(--acc);color:var(--acc)}\n.transport .main{width:50px;height:50px;background:var(--acc);border-color:var(--acc);color:#fff}\n.transport .main:hover{color:#fff;filter:brightness(1.08)}\n.times{display:flex;justify-content:space-between;font-size:11.5px;color:var(--mut);margin-top:7px;font-variant-numeric:tabular-nums}",
                'js' => "const bars=[...document.querySelectorAll('.wave i')];\n"
                    . "const wave=document.querySelector('.wave');\n"
                    . "function seekTo(index){bars.forEach((bar,i)=>bar.classList.toggle('on',i<=index));}\n"
                    . "wave.addEventListener('click',event=>{\n"
                    . "  const ratio=(event.clientX-wave.getBoundingClientRect().left)/wave.clientWidth;\n"
                    . "  seekTo(Math.round(ratio*bars.length));\n"
                    . "});\n"
                    . 'seekTo(Math.round(bars.length * 0.38));',
                'body' => self::wrap(
                    self::card(
                        '<div class="pad">'
                        . '<div class="row" style="gap:14px"><span style="width:58px;height:58px;border-radius:14px;background:linear-gradient(140deg,#7c3aed,#4f46e5);flex:none"></span>'
                        . '<span><b style="display:block;font-size:14.5px">Designing for the ninety-ninth percentile</b>'
                        . '<span class="xs mut">Episode 42 · The Frugal Podcast</span></span></div>'
                        . '<div class="wave" style="margin-top:18px">' . implode('', array_map(
                            function ($i) {
                                $height = 12 + (int) (abs(sin($i / 3.1)) * 34) + (($i * 7) % 9);

                                return '<i style="height:' . $height . 'px"></i>';
                            },
                            range(0, 59)
                        )) . '</div>'
                        . '<div class="times"><span>14:08</span><span>-22:41</span></div>'
                        . '<div class="transport">'
                        . '<button type="button" aria-label="Back 15 seconds">' . Kit::icon('arrow-left', 17) . '</button>'
                        . '<button class="main" type="button" aria-label="Pause">' . Kit::icon('pause', 20) . '</button>'
                        . '<button type="button" aria-label="Forward 30 seconds">' . Kit::icon('arrow-right', 17) . '</button>'
                        . '<button type="button" aria-label="Playback speed" style="width:auto;padding:0 13px;border-radius:999px;font-size:12.5px;font-weight:700">1.25&times;</button>'
                        . '</div></div>'
                    )
                ),
            ],
            [
                'slug' => 'chat-bubbles',
                'name' => 'Chat conversation',
                'name_ar' => 'محادثة',
                'tagline' => 'Message bubbles with status ticks, typing and a composer.',
                'tagline_ar' => 'فقاعات رسائل بعلامات الحالة ومؤشر الكتابة وحقل الإرسال.',
                'summary' => 'A messaging thread with the details that make one feel alive: delivery ticks, a typing indicator, day separators and a composer that grows with the message. Outgoing and incoming differ in side, colour and tail.',
                'summary_ar' => 'سلسلة رسائل بالتفاصيل التي تبث فيها الحياة: علامات التسليم، ومؤشر الكتابة، وفواصل الأيام، وحقل كتابة يتمدد مع الرسالة. تختلف الرسائل الصادرة عن الواردة في الجانب واللون والذيل.',
                'accent' => '#059669',
                'tags' => ['chat', 'messaging', 'bubbles', 'conversation'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Delivery and read ticks on outgoing messages', 'Typing indicator with three animated dots', 'Composer with attach and send actions'],
                'features_ar' => ['علامات التسليم والقراءة على الرسائل الصادرة', 'مؤشر كتابة بثلاث نقاط متحركة', 'حقل كتابة مع إجراءي الإرفاق والإرسال'],
                'height' => 700,
                'max' => 520,
                'css' => ".thread{padding:18px;display:grid;gap:12px;background:var(--soft);max-height:390px;overflow:auto}\n.day{text-align:center;font-size:11px;color:var(--mut);font-weight:650}\n.msg{display:flex;gap:9px;align-items:flex-end;max-width:82%}\n.msg .bub{background:var(--card);border:1px solid var(--bd);border-radius:14px 14px 14px 4px;padding:10px 13px;font-size:13.5px;line-height:1.5}\n.msg .when{font-size:10.5px;color:var(--mut);white-space:nowrap}\n.msg.out{margin-left:auto;flex-direction:row-reverse}\n.msg.out .bub{background:var(--acc);border-color:var(--acc);color:#fff;border-radius:14px 14px 4px 14px}\n.ticks{display:inline-flex;margin-left:6px;opacity:.8;vertical-align:-2px}\n@keyframes bounce{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-4px);opacity:1}}\n.typing{display:inline-flex;gap:4px;padding:12px 14px}\n.typing i{width:6px;height:6px;border-radius:50%;background:var(--mut);animation:bounce 1.2s infinite}\n.typing i:nth-child(2){animation-delay:.15s}\n.typing i:nth-child(3){animation-delay:.3s}\n.composer{display:flex;align-items:flex-end;gap:9px;padding:12px 14px;border-top:1px solid var(--bd)}\n.composer textarea{flex:1;border:1px solid var(--bd);border-radius:12px;padding:10px 12px;font:inherit;font-size:13.5px;resize:none;background:var(--card);color:var(--ink)}\n.composer textarea:focus{outline:none;border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-soft)}",
                'body' => self::wrap(
                    self::card(
                        '<div class="hd"><div class="row">' . Kit::avatar('Maya Rahman', 40)
                        . '<div><h2 style="font-size:15px">Maya Rahman</h2>'
                        . '<p class="sub" style="display:flex;align-items:center;gap:6px"><span style="width:7px;height:7px;border-radius:50%;background:var(--ok);display:inline-block"></span>Online</p></div></div>'
                        . '<button class="btn gh" type="button" aria-label="Call">' . Kit::icon('phone', 18) . '</button></div>'
                        . '<div class="thread">'
                        . '<div class="day">Today</div>'
                        . '<div class="msg">' . Kit::avatar('Maya Rahman', 28) . '<span class="bub">Did you see the compact table at 28px rows? The sparkline is unreadable at that height.</span><span class="when">09:41</span></div>'
                        . '<div class="msg out"><span class="bub">Agreed. I will drop it from the dense variant and keep it in the analytics one.'
                        . '<span class="ticks">' . Kit::icon('check', 13, 2.6) . '</span></span><span class="when">09:44</span></div>'
                        . '<div class="msg out"><span class="bub">Pushed. Have a look when you get a moment.'
                        . '<span class="ticks">' . Kit::icon('check', 13, 2.6) . Kit::icon('check', 13, 2.6) . '</span></span><span class="when">10:02</span></div>'
                        . '<div class="msg">' . Kit::avatar('Maya Rahman', 28) . '<span class="bub">Much better. The row height reads properly now.</span><span class="when">10:18</span></div>'
                        . '<div class="msg">' . Kit::avatar('Maya Rahman', 28) . '<span class="bub typing"><i></i><i></i><i></i></span></div>'
                        . '</div>'
                        . '<div class="composer">'
                        . '<button class="btn gh" type="button" aria-label="Attach a file" style="padding:9px">' . Kit::icon('upload', 18) . '</button>'
                        . '<textarea rows="1" placeholder="Write a message…" aria-label="Message"></textarea>'
                        . '<button class="btn pri" type="button" aria-label="Send" style="padding:10px">' . Kit::icon('send', 17) . '</button></div>'
                    )
                ),
            ],
            [
                'slug' => 'comment-thread',
                'name' => 'Comment thread',
                'name_ar' => 'سلسلة التعليقات',
                'tagline' => 'Nested replies with votes, author badges and actions.',
                'tagline_ar' => 'ردود متداخلة مع تصويت وشارات للكاتب وإجراءات.',
                'summary' => 'A discussion thread with one level of nesting - deeper than that and a comment section becomes unreadable on a phone. Author badges mark the original poster and the staff replies, which is what stops a thread turning into guesswork.',
                'summary_ar' => 'سلسلة نقاش بمستوى تداخل واحد، فأي تداخل أعمق يجعل قسم التعليقات غير مقروء على الهاتف. شارات الكاتب تميّز صاحب المنشور الأصلي وردود فريق العمل، وهذا ما يمنع السلسلة من التحول إلى تخمين.',
                'accent' => '#4f46e5',
                'tags' => ['comments', 'thread', 'discussion', 'replies'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['One level of nesting, drawn with a guide line', 'Author and staff badges', 'Vote control with a pressed state'],
                'features_ar' => ['مستوى تداخل واحد مرسوم بخط إرشادي', 'شارات لصاحب المنشور ولفريق العمل', 'عنصر تصويت مع حالة الضغط'],
                'height' => 700,
                'max' => 620,
                'css' => ".cmt{display:grid;grid-template-columns:auto 1fr;gap:12px;padding:16px 0;border-bottom:1px solid var(--bd)}\n.cmt:last-child{border-bottom:0}\n.cmt .who{display:flex;align-items:center;gap:8px;flex-wrap:wrap}\n.cmt .who b{font-size:13.5px}\n.cmt p{font-size:13.5px;line-height:1.65;margin-top:7px}\n.cmt .acts{display:flex;gap:14px;margin-top:10px;font-size:12px;color:var(--mut)}\n.cmt .acts button{border:0;background:none;padding:0;cursor:pointer;color:inherit;font:inherit;display:inline-flex;align-items:center;gap:5px}\n.cmt .acts button:hover{color:var(--acc)}\n.cmt .acts button[aria-pressed=true]{color:var(--acc);font-weight:700}\n.replies{margin-left:20px;padding-left:20px;border-left:2px solid var(--bd);margin-top:4px}\n.badge{font-size:10px;font-weight:700;padding:1px 7px;border-radius:999px;background:var(--acc-soft);color:var(--acc);letter-spacing:.02em}\n.badge.staff{background:var(--ok-bg);color:var(--ok)}",
                'body' => self::wrap(
                    self::card(
                        self::head('12 comments', 'On: A table component that survives a phone', '<select class="in" aria-label="Sort comments" style="width:auto;padding:6px 30px 6px 10px;font-size:12.5px"><option>Top</option><option>Newest</option><option>Oldest</option></select>')
                        . '<div class="pad">'
                        . self::comment('Maya Rahman', 'OP', '4 hours ago', 'The stacked variant is the only responsive table pattern I have seen that does not need a second markup tree. Does the data-label approach hold up with dynamic columns?', '18', true)
                        . '<div class="replies">'
                        . self::comment('Omar Saleh', 'staff', '3 hours ago', 'It does, as long as the label is written when the row is rendered. If the columns come from an API, set data-label from the same array you build the header from.', '9', false)
                        . self::comment('Maya Rahman', 'OP', '2 hours ago', 'That is what I ended up doing. Works well.', '4', false)
                        . '</div>'
                        . self::comment('Karim Nasser', '', '6 hours ago', 'One thing worth adding: below 640px the horizontal scroll container should be removed entirely, otherwise you get a scrollbar on a stack of cards.', '11', false)
                        . self::comment('Sara Aziz', '', '8 hours ago', 'Tested it with a screen reader and the cell labels are announced properly, which I did not expect from a CSS-only approach.', '7', false)
                        . '</div>'
                        . '<div class="ft"><span>Showing 4 of 12</span><a href="#">Load the rest</a></div>'
                    )
                ),
            ],
            [
                'slug' => 'tag-input',
                'name' => 'Tag input',
                'name_ar' => 'حقل الوسوم',
                'tagline' => 'Chips created on Enter, removed on Backspace.',
                'tagline_ar' => 'شارات تُنشأ بـ Enter وتُحذف بـ Backspace.',
                'summary' => 'A tag field with the keyboard behaviour people expect from one: Enter or comma commits, Backspace on an empty field removes the last chip, and suggestions appear as you type. A duplicate flashes instead of silently doing nothing.',
                'summary_ar' => 'حقل وسوم بسلوك لوحة المفاتيح المتوقع منه: Enter أو الفاصلة يثبّت الوسم، وBackspace في حقل فارغ يحذف آخر شارة، والاقتراحات تظهر أثناء الكتابة. الوسم المكرر يومض بدل أن يفشل بصمت.',
                'accent' => '#0891b2',
                'tags' => ['tags', 'chips', 'input', 'multi-value'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Enter and comma commit, Backspace removes the last', 'Suggestions filtered as you type', 'Duplicates flash rather than failing silently'],
                'features_ar' => ['Enter والفاصلة للتثبيت، وBackspace يحذف الأخير', 'اقتراحات تُصفّى أثناء الكتابة', 'المكرر يومض بدل أن يفشل بصمت'],
                'height' => 560,
                'max' => 520,
                'css' => ".tagbox{display:flex;flex-wrap:wrap;gap:7px;padding:9px;border:1px solid var(--bd);border-radius:12px;min-height:48px;cursor:text;background:var(--card)}\n.tagbox:focus-within{border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-soft)}\n.tagbox input{flex:1;min-width:130px;border:0;outline:none;background:none;font:inherit;font-size:13.5px;color:var(--ink);padding:5px}\n@keyframes flash{0%,100%{background:var(--acc-soft)}50%{background:var(--bad-bg)}}\n.tag{display:inline-flex;align-items:center;gap:6px;padding:5px 7px 5px 11px;border-radius:999px;background:var(--acc-soft);color:var(--acc);font-size:12.5px;font-weight:650}\n.tag.dup{animation:flash .5s ease 2}\n.tag button{border:0;background:none;padding:1px;cursor:pointer;color:inherit;display:inline-flex}\n.suggest{display:flex;gap:7px;flex-wrap:wrap;margin-top:12px}\n.suggest button{border:1px dashed var(--bd);background:none;border-radius:999px;padding:5px 11px;font:inherit;font-size:12.5px;color:var(--mut);cursor:pointer}\n.suggest button:hover{border-color:var(--acc);color:var(--acc);border-style:solid}",
                'js' => "const box=document.getElementById('tagbox');\n"
                    . "const entry=document.getElementById('tagentry');\n"
                    . "box.addEventListener('click',()=>entry.focus());\n"
                    . "function add(value){\n"
                    . "  const existing=[...box.querySelectorAll('.tag')].find(tag=>tag.dataset.v===value.toLowerCase());\n"
                    . "  if(existing){existing.classList.add('dup');setTimeout(()=>existing.classList.remove('dup'),1000);return;}\n"
                    . "  const tag=document.createElement('span');\n"
                    . "  tag.className='tag';tag.dataset.v=value.toLowerCase();tag.textContent=value;\n"
                    . "  const remove=document.createElement('button');\n"
                    . "  remove.type='button';remove.innerHTML='&times;';\n"
                    . "  remove.setAttribute('aria-label','Remove '+value);\n"
                    . "  remove.addEventListener('click',()=>tag.remove());\n"
                    . "  tag.appendChild(remove);\n"
                    . "  box.insertBefore(tag,entry);\n"
                    . "}\n"
                    . "entry.addEventListener('keydown',event=>{\n"
                    . "  if(event.key==='Enter'||event.key===','){\n"
                    . "    event.preventDefault();\n"
                    . "    const value=entry.value.trim().replace(/,$/,'');\n"
                    . "    if(value){add(value);entry.value='';}\n"
                    . "  }\n"
                    . "  if(event.key==='Backspace'&&!entry.value){\n"
                    . "    const tags=box.querySelectorAll('.tag');\n"
                    . "    if(tags.length)tags[tags.length-1].remove();\n"
                    . "  }\n"
                    . "});\n"
                    . "document.querySelectorAll('.suggest button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>add(button.textContent.trim()));\n"
                    . "});\n"
                    . "['table','responsive'].forEach(add);",
                'body' => self::wrap(
                    self::card(
                        self::head('Tags', 'Enter to add · Backspace to remove the last')
                        . '<div class="pad">'
                        . '<label class="lb" for="tagentry">Component tags</label>'
                        . '<div class="tagbox" id="tagbox"><input id="tagentry" type="text" placeholder="Add a tag…" aria-label="Add a tag"></div>'
                        . '<p class="hint">Up to 10 tags. They drive the gallery filters.</p>'
                        . '<span class="lb" style="margin-top:16px">Suggested</span>'
                        . '<div class="suggest">'
                        . '<button type="button">dashboard</button><button type="button">dark</button><button type="button">form</button>'
                        . '<button type="button">chart</button><button type="button">accessible</button><button type="button">mobile</button>'
                        . '</div></div>'
                    )
                ),
            ],
            [
                'slug' => 'multi-select-list',
                'name' => 'Multi-select list',
                'name_ar' => 'قائمة اختيار متعدد',
                'tagline' => 'Searchable checkbox list with a selected summary.',
                'tagline_ar' => 'قائمة مربعات اختيار قابلة للبحث مع ملخص المحدد.',
                'summary' => 'The control a plain multiple-select should have been: searchable, with a count of what is selected, select-all and clear, and the chosen items summarised above so the list can be scrolled without losing track.',
                'summary_ar' => 'العنصر الذي كان يجب أن يكونه حقل الاختيار المتعدد العادي: قابل للبحث، مع عدد للمحدد، وتحديد الكل والمسح، والعناصر المختارة ملخصة في الأعلى فيمكن تمرير القائمة دون فقدان المتابعة.',
                'accent' => '#4f46e5',
                'tags' => ['multi-select', 'listbox', 'filter', 'checkbox'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Search that filters the list as you type', 'Select-all and clear with a live count', 'Selected items summarised as chips above'],
                'features_ar' => ['بحث يصفّي القائمة أثناء الكتابة', 'تحديد الكل والمسح مع عدد مباشر', 'العناصر المحددة ملخصة كشارات في الأعلى'],
                'height' => 620,
                'max' => 460,
                'css' => ".msearch{display:flex;align-items:center;gap:9px;padding:11px 14px;border-bottom:1px solid var(--bd)}\n.msearch input{flex:1;border:0;outline:none;background:none;font:inherit;font-size:13.5px;color:var(--ink)}\n.mlist{max-height:240px;overflow:auto;padding:7px}\n.mrow{display:flex;align-items:center;gap:11px;padding:9px 10px;border-radius:9px;cursor:pointer;font-size:13.5px}\n.mrow:hover{background:var(--soft)}\n.mrow input{width:16px;height:16px;accent-color:var(--acc)}\n.mrow .n{margin-left:auto;font-size:11.5px;color:var(--mut)}\n.chips{display:flex;gap:6px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid var(--bd);min-height:50px}\n.chips .pill{background:var(--acc-soft);color:var(--acc)}\n.chips .none{font-size:12.5px;color:var(--mut)}",
                'js' => "const boxes=[...document.querySelectorAll('.mrow input')];\n"
                    . "const chips=document.getElementById('chips');\n"
                    . "const count=document.getElementById('mcount');\n"
                    . "function refresh(){\n"
                    . "  const picked=boxes.filter(box=>box.checked);\n"
                    . "  count.textContent=picked.length;\n"
                    . "  chips.innerHTML=picked.length\n"
                    . "    ? picked.map(box=>'<span class=\"pill\">'+box.dataset.label+'</span>').join('')\n"
                    . "    : '<span class=\"none\">Nothing selected yet</span>';\n"
                    . "}\n"
                    . "boxes.forEach(box=>box.addEventListener('change',refresh));\n"
                    . "document.getElementById('all').addEventListener('click',()=>{boxes.forEach(box=>box.checked=true);refresh();});\n"
                    . "document.getElementById('none').addEventListener('click',()=>{boxes.forEach(box=>box.checked=false);refresh();});\n"
                    . "document.getElementById('mq').addEventListener('input',event=>{\n"
                    . "  const term=event.target.value.trim().toLowerCase();\n"
                    . "  document.querySelectorAll('.mrow').forEach(row=>{\n"
                    . "    row.hidden=term!==''&&!row.textContent.toLowerCase().includes(term);\n"
                    . "  });\n"
                    . "});\nrefresh();",
                'body' => self::wrap(
                    self::card(
                        self::head('Categories', '<span id="mcount">0</span> selected', '<button class="btn gh tiny" type="button" id="all">Select all</button><button class="btn gh tiny" type="button" id="none">Clear</button>')
                        . '<div class="chips" id="chips"><span class="none">Nothing selected yet</span></div>'
                        . '<div class="msearch">' . Kit::icon('search', 17) . '<input id="mq" type="search" placeholder="Filter categories…" aria-label="Filter categories"></div>'
                        . '<div class="mlist">'
                        . self::multiRow('Tables', '54', true)
                        . self::multiRow('Forms', '54', true)
                        . self::multiRow('Dashboards', '54', false)
                        . self::multiRow('UI Elements', '54', false)
                        . self::multiRow('Charts', '28', false)
                        . self::multiRow('Navigation', '19', false)
                        . self::multiRow('Marketing', '22', false)
                        . self::multiRow('E-commerce', '31', false)
                        . '</div>'
                        . '<div class="ft"><button class="btn" type="button">Cancel</button><button class="btn pri" type="button">Apply filters</button></div>'
                    )
                ),
            ],
            [
                'slug' => 'context-menu',
                'name' => 'Context menu',
                'name_ar' => 'قائمة السياق',
                'tagline' => 'Right-click menu that positions itself at the pointer.',
                'tagline_ar' => 'قائمة النقر الأيمن تتموضع عند المؤشر.',
                'summary' => 'A right-click menu that opens where the pointer is and flips back inside the viewport near an edge - the part that is always missing from the tutorial version and always noticed by the person using it.',
                'summary_ar' => 'قائمة نقر أيمن تُفتح حيث المؤشر وتنقلب إلى داخل منطقة العرض قرب الحواف، وهو الجزء الغائب دائمًا عن نسخ الدروس التعليمية والذي يلاحظه المستخدم دائمًا.',
                'accent' => '#475569',
                'tags' => ['context-menu', 'right-click', 'menu', 'desktop'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Opens at the pointer and stays inside the viewport', 'Keyboard shortcut hints on each item', 'Closes on click away, Escape or scroll'],
                'features_ar' => ['تُفتح عند المؤشر وتبقى داخل منطقة العرض', 'تلميحات اختصارات لوحة المفاتيح على كل عنصر', 'تُغلق عند النقر خارجها أو الضغط على Esc أو التمرير'],
                'height' => 560,
                'max' => 620,
                'css' => ".surface{height:280px;border:2px dashed var(--bd);border-radius:14px;display:flex;align-items:center;justify-content:center;color:var(--mut);font-size:13.5px;text-align:center;padding:20px}\n.cmenu{position:fixed;min-width:220px;background:var(--card);border:1px solid var(--bd);border-radius:12px;box-shadow:var(--sh);padding:6px;z-index:40}\n.cmenu[hidden]{display:none}\n.cmenu button{display:flex;align-items:center;gap:10px;width:100%;border:0;background:none;padding:8px 10px;border-radius:8px;font:inherit;font-size:13px;color:var(--ink);cursor:pointer;text-align:left}\n.cmenu button:hover{background:var(--soft)}\n.cmenu kbd{margin-left:auto;font-size:11px;color:var(--mut);font-family:inherit}\n.cmenu .sep{height:1px;background:var(--bd);margin:5px 4px}\n.cmenu .danger{color:var(--bad)}",
                'js' => "const menu=document.getElementById('cmenu');\n"
                    . "const surface=document.querySelector('.surface');\n"
                    . "surface.addEventListener('contextmenu',event=>{\n"
                    . "  event.preventDefault();\n"
                    . "  menu.hidden=false;\n"
                    . "  const box=menu.getBoundingClientRect();\n"
                    . "  const x=Math.min(event.clientX,window.innerWidth-box.width-8);\n"
                    . "  const y=Math.min(event.clientY,window.innerHeight-box.height-8);\n"
                    . "  menu.style.left=x+'px';\n"
                    . "  menu.style.top=y+'px';\n"
                    . "});\n"
                    . "function close(){menu.hidden=true;}\n"
                    . "document.addEventListener('click',close);\n"
                    . "document.addEventListener('scroll',close,true);\n"
                    . "document.addEventListener('keydown',event=>{if(event.key==='Escape')close();});",
                'body' => self::wrap(
                    self::card(
                        self::head('Context menu', 'Right-click inside the dashed area')
                        . '<div class="pad"><div class="surface">Right-click here.<br>Near an edge, the menu flips back into view.</div></div>'
                    ),
                    '<div class="cmenu" id="cmenu" role="menu" hidden>'
                    . '<button role="menuitem" type="button">' . Kit::icon('eye', 16) . 'Open<kbd>Enter</kbd></button>'
                    . '<button role="menuitem" type="button">' . Kit::icon('external', 16) . 'Open in a new tab</button>'
                    . '<div class="sep"></div>'
                    . '<button role="menuitem" type="button">' . Kit::icon('copy', 16) . 'Copy<kbd>Ctrl C</kbd></button>'
                    . '<button role="menuitem" type="button">' . Kit::icon('edit', 16) . 'Rename<kbd>F2</kbd></button>'
                    . '<button role="menuitem" type="button">' . Kit::icon('download', 16) . 'Download</button>'
                    . '<div class="sep"></div>'
                    . '<button role="menuitem" type="button" class="danger">' . Kit::icon('trash', 16) . 'Delete<kbd>Del</kbd></button>'
                    . '</div>'
                ),
            ],
            [
                'slug' => 'drawer-panel',
                'name' => 'Slide-over drawer',
                'name_ar' => 'لوحة جانبية منزلقة',
                'tagline' => 'Side panel with a scrim, header and pinned footer.',
                'tagline_ar' => 'لوحة جانبية بطبقة تعتيم ورأس وتذييل مثبت.',
                'summary' => 'A drawer for detail work that would be cramped in a dialog: it slides from the right, dims the page behind it, keeps its actions pinned at the bottom, and closes on Escape or on the scrim.',
                'summary_ar' => 'درج جانبي للأعمال التفصيلية التي تضيق بها النافذة الحوارية: ينزلق من اليمين، ويعتّم الصفحة خلفه، ويُبقي إجراءاته مثبتة في الأسفل، ويُغلق بـ Esc أو بالنقر على طبقة التعتيم.',
                'accent' => '#4f46e5',
                'tags' => ['drawer', 'slide-over', 'panel', 'overlay'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Slides in with a dimming scrim behind it', 'Footer actions pinned while the body scrolls', 'Escape and scrim both close it'],
                'features_ar' => ['ينزلق للداخل مع طبقة تعتيم خلفه', 'إجراءات التذييل مثبتة أثناء تمرير المحتوى', 'يُغلق بـ Esc أو بالنقر على طبقة التعتيم'],
                'height' => 600,
                'max' => 620,
                'css' => "@keyframes slide{from{transform:translateX(100%)}to{transform:none}}\n.scrim{position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:30}\n.scrim[hidden],.drawer[hidden]{display:none}\n.drawer{position:fixed;top:0;right:0;bottom:0;width:min(400px,100%);background:var(--card);border-left:1px solid var(--bd);z-index:31;display:flex;flex-direction:column;animation:slide .22s ease-out}\n.drawer .dbody{flex:1;overflow:auto;padding:18px 20px;display:grid;gap:16px;align-content:start}\n.drawer .dfoot{padding:14px 20px;border-top:1px solid var(--bd);display:flex;gap:9px;justify-content:flex-end}\n.kv{display:grid;gap:4px}\n.kv span{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);font-weight:700}\n.kv b{font-size:13.5px}",
                'js' => "const scrim=document.getElementById('scrim');\n"
                    . "const drawer=document.getElementById('drawer');\n"
                    . "function open(){scrim.hidden=false;drawer.hidden=false;}\n"
                    . "function close(){scrim.hidden=true;drawer.hidden=true;}\n"
                    . "document.getElementById('opendrawer').addEventListener('click',open);\n"
                    . "scrim.addEventListener('click',close);\n"
                    . "drawer.querySelectorAll('[data-close]').forEach(button=>button.addEventListener('click',close));\n"
                    . "document.addEventListener('keydown',event=>{if(event.key==='Escape')close();});",
                'body' => self::wrap(
                    self::card(
                        self::head('Slide-over drawer', 'For detail that would crowd a dialog')
                        . '<div class="pad"><button class="btn pri" type="button" id="opendrawer">' . Kit::icon('eye', 15) . 'Open order #3104</button>'
                        . '<p class="hint">A drawer keeps the list visible behind it, which a full-page view does not.</p></div>'
                    ),
                    '<div class="scrim" id="scrim" hidden></div>',
                    '<aside class="drawer" id="drawer" role="dialog" aria-label="Order details" hidden>'
                    . '<div class="hd"><div><h2 style="font-size:15.5px">Order #3104</h2><p class="sub">Placed 12 September, 09:41</p></div>'
                    . '<button class="btn gh" type="button" data-close aria-label="Close" style="padding:7px">' . Kit::icon('x', 18) . '</button></div>'
                    . '<div class="dbody">'
                    . '<div class="row" style="gap:10px">' . Kit::pill('Paid', 'ok', true) . Kit::pill('Packed', 'info', true) . '</div>'
                    . '<div class="kv"><span>Customer</span><b>Rana Khalil</b><span class="xs mut" style="text-transform:none;letter-spacing:0;font-weight:500">rana.k@mail.com · +966 5x xxx xxxx</span></div>'
                    . '<div class="kv"><span>Deliver to</span><b>Al Nakheel, Riyadh 12388</b></div>'
                    . '<div class="kv"><span>Items</span>'
                    . '<div style="display:grid;gap:9px;margin-top:5px">'
                    . '<div class="row" style="gap:10px">' . Kit::iconTile('box', Kit::SERIES[0], 34) . '<span style="flex:1"><b style="display:block;font-size:13px">Aurora Desk Lamp</b><span class="xs mut">2 × $39.00</span></span><b class="num">$78.00</b></div>'
                    . '<div class="row" style="gap:10px">' . Kit::iconTile('box', Kit::SERIES[1], 34) . '<span style="flex:1"><b style="display:block;font-size:13px">Terra Side Table</b><span class="xs mut">1 × $58.00</span></span><b class="num">$58.00</b></div>'
                    . '<div class="row" style="gap:10px">' . Kit::iconTile('box', Kit::SERIES[2], 34) . '<span style="flex:1"><b style="display:block;font-size:13px">Vista Shelving</b><span class="xs mut">1 × $112.00</span></span><b class="num">$112.00</b></div>'
                    . '</div></div>'
                    . '<div class="kv"><span>Total</span><b style="font-size:19px">$248.00</b></div>'
                    . '</div>'
                    . '<div class="dfoot"><button class="btn" type="button" data-close>Close</button>'
                    . '<button class="btn pri" type="button">' . Kit::icon('truck', 15) . 'Mark as shipped</button></div>'
                    . '</aside>'
                ),
            ],
            [
                'slug' => 'split-button',
                'name' => 'Split button',
                'name_ar' => 'زر مقسوم',
                'tagline' => 'A default action joined to a menu of alternatives.',
                'tagline_ar' => 'إجراء افتراضي متصل بقائمة من البدائل.',
                'summary' => 'One click for the common action, one for the list of the others. The two halves are separate buttons so each gets its own label and focus stop, which is what makes the pattern usable from a keyboard.',
                'summary_ar' => 'نقرة للإجراء الشائع، وأخرى لقائمة البقية. النصفان زران منفصلان، لكل منهما تسميته ومحطة تركيزه، وهذا ما يجعل النمط قابلًا للاستخدام من لوحة المفاتيح.',
                'accent' => '#059669',
                'tags' => ['split-button', 'dropdown', 'actions', 'menu'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Two real buttons, two focus stops, two labels', 'Menu closes on outside click and Escape', 'Primary, secondary and destructive variants'],
                'features_ar' => ['زران حقيقيان، ومحطتا تركيز، وتسميتان', 'القائمة تُغلق عند النقر خارجها وعند الضغط على Esc', 'أنواع أساسية وثانوية وخطرة'],
                'height' => 520,
                'max' => 620,
                'css' => self::groupCss() . "\n.split{position:relative;display:inline-flex}\n.split .main{border-radius:10px 0 0 10px}\n.split .toggle{border-radius:0 10px 10px 0;border-left:0;padding:9px 10px}\n.split .menu{position:absolute;top:calc(100% + 6px);right:0;min-width:214px;background:var(--card);border:1px solid var(--bd);border-radius:12px;box-shadow:var(--sh);padding:6px;z-index:20}\n.split .menu[hidden]{display:none}\n.split .menu button{display:flex;align-items:center;gap:10px;width:100%;border:0;background:none;padding:8px 10px;border-radius:8px;font:inherit;font-size:13px;color:var(--ink);cursor:pointer;text-align:left}\n.split .menu button:hover{background:var(--soft)}",
                'js' => "document.querySelectorAll('.split .toggle').forEach(toggle=>{\n"
                    . "  toggle.addEventListener('click',event=>{\n"
                    . "    event.stopPropagation();\n"
                    . "    const menu=toggle.parentElement.querySelector('.menu');\n"
                    . "    document.querySelectorAll('.split .menu').forEach(other=>{if(other!==menu)other.hidden=true;});\n"
                    . "    menu.hidden=!menu.hidden;\n"
                    . "    toggle.setAttribute('aria-expanded',String(!menu.hidden));\n"
                    . "  });\n"
                    . "});\n"
                    . "document.addEventListener('click',()=>{\n"
                    . "  document.querySelectorAll('.split .menu').forEach(menu=>menu.hidden=true);\n"
                    . "  document.querySelectorAll('.split .toggle').forEach(toggle=>toggle.setAttribute('aria-expanded','false'));\n"
                    . '});',
                'body' => self::wrap(
                    self::card(
                        self::head('Split button', 'The common action, plus the rest')
                        . self::group('Primary', self::splitButton('Save', 'check', 'pri', [
                            ['copy', 'Save as a copy'],
                            ['file', 'Save as a template'],
                            ['download', 'Save and download'],
                        ]))
                        . self::group('Secondary', self::splitButton('Export CSV', 'download', '', [
                            ['file', 'Export as JSON'],
                            ['chart', 'Export as XLSX'],
                            ['image', 'Export as PDF'],
                        ]))
                        . self::group('Destructive', self::splitButton('Archive', 'box', '', [
                            ['trash', 'Delete permanently'],
                            ['x', 'Remove from this project'],
                        ]), 'Keep the safer action as the default half - the dangerous one belongs in the menu.')
                    )
                ),
            ],
            [
                'slug' => 'announcement-bar',
                'name' => 'Announcement bar',
                'name_ar' => 'شريط إعلان',
                'tagline' => 'Top-of-page strip with a link and a dismiss.',
                'tagline_ar' => 'شريط أعلى الصفحة برابط وزر إغلاق.',
                'summary' => 'The strip above the header, in the three versions that get used: a plain announcement, a countdown offer and a maintenance warning. All three can be dismissed, because an undismissable bar is a permanent layout change.',
                'summary_ar' => 'الشريط الذي يعلو الترويسة، بنسخه الثلاث المستخدمة فعلًا: إعلان بسيط، وعرض بعدّ تنازلي، وتحذير صيانة. الثلاثة قابلة للإغلاق، فالشريط الذي لا يُغلق تغيير دائم في التخطيط.',
                'accent' => '#7c3aed',
                'tags' => ['announcement', 'banner', 'top-bar', 'marketing'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Three tones: news, offer and maintenance', 'Dismiss on every variant', 'Countdown that updates in place'],
                'features_ar' => ['ثلاث نبرات: أخبار وعروض وصيانة', 'زر إغلاق في كل الأنواع', 'عدّ تنازلي يتحدث في مكانه'],
                'height' => 560,
                'max' => 820,
                'css' => ".bar{display:flex;align-items:center;justify-content:center;gap:12px;padding:11px 44px 11px 16px;position:relative;font-size:13px;font-weight:600;border-radius:12px}\n.bar a{text-decoration:underline;font-weight:700}\n.bar .x{position:absolute;right:10px;top:50%;transform:translateY(-50%);border:0;background:none;padding:4px;cursor:pointer;color:inherit;opacity:.75;display:inline-flex}\n.bar .x:hover{opacity:1}\n.bar.news{background:linear-gradient(135deg,var(--acc),var(--acc-dk));color:#fff}\n.bar.news a{color:#fff}\n.bar.offer{background:var(--warn-bg);color:var(--warn)}\n.bar.offer a{color:inherit}\n.bar.maint{background:var(--bad-bg);color:var(--bad)}\n.bar.maint a{color:inherit}\n.stack{display:grid;gap:12px;padding:18px 20px}\n.count{font-variant-numeric:tabular-nums;background:rgba(0,0,0,.08);padding:2px 8px;border-radius:6px}",
                'js' => "document.querySelectorAll('.bar .x').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>button.closest('.bar').remove());\n"
                    . "});\n"
                    . "let left=3*3600+42*60+18;\n"
                    . "const clock=document.getElementById('clock');\n"
                    . "setInterval(()=>{\n"
                    . "  if(left<=0||!clock)return;\n"
                    . "  left--;\n"
                    . "  const h=String(Math.floor(left/3600)).padStart(2,'0');\n"
                    . "  const m=String(Math.floor(left%3600/60)).padStart(2,'0');\n"
                    . "  const s=String(left%60).padStart(2,'0');\n"
                    . "  clock.textContent=h+':'+m+':'+s;\n"
                    . '},1000);',
                'body' => self::wrap(
                    self::card(
                        self::head('Announcement bars', 'Each one can be dismissed')
                        . '<div class="stack">'
                        . '<div class="bar news">' . Kit::icon('zap', 16) . '<span>Three hundred drawing templates just landed in the editor.</span>'
                        . '<a href="#">Take a look</a>'
                        . '<button class="x" type="button" aria-label="Dismiss">' . Kit::icon('x', 16) . '</button></div>'
                        . '<div class="bar offer">' . Kit::icon('tag', 16) . '<span>20% off yearly plans ends in <span class="count" id="clock">03:42:18</span></span>'
                        . '<a href="#">Upgrade</a>'
                        . '<button class="x" type="button" aria-label="Dismiss">' . Kit::icon('x', 16) . '</button></div>'
                        . '<div class="bar maint">' . Kit::icon('alert', 16) . '<span>Scheduled maintenance on Sunday, 02:00 - 03:00 UTC. The API will be read-only.</span>'
                        . '<a href="#">Details</a>'
                        . '<button class="x" type="button" aria-label="Dismiss">' . Kit::icon('x', 16) . '</button></div>'
                        . '</div>'
                        . '<div class="ft"><span>Put one of these above the header, never two</span></div>'
                    )
                ),
            ],
        ];
    }

    /* ---------------------------------------------------------------- */

    private static function featureTile(string $icon, string $colour, string $title, string $body): string
    {
        return self::card('<div class="feat">' . Kit::iconTile($icon, $colour, 42)
            . '<h3>' . $title . '</h3><p>' . $body . '</p></div>');
    }

    private static function logoMark(string $name, string $colour, string $letter): string
    {
        return '<span class="logo"><span class="m" style="background:' . $colour . '">' . $letter . '</span><b>' . $name . '</b></span>';
    }

    private static function personCard(string $name, string $role, string $where): string
    {
        return self::card('<div class="person">'
            . '<span style="display:flex;justify-content:center">' . Kit::avatar($name, 66) . '</span>'
            . '<h3>' . $name . '</h3><div class="role">' . $role . '</div>'
            . '<div class="where">' . $where . '</div>'
            . '<div class="links">'
            . '<a href="#" aria-label="Email ' . $name . '">' . Kit::icon('mail', 15) . '</a>'
            . '<a href="#" aria-label="' . $name . ' on the web">' . Kit::icon('link', 15) . '</a>'
            . '<a href="#" aria-label="Message ' . $name . '">' . Kit::icon('send', 15) . '</a>'
            . '</div></div>');
    }

    private static function galleryTile(string $background, string $caption, string $span): string
    {
        return '<div class="gtile ' . $span . '" style="background:' . $background . '" tabindex="0">'
            . '<span class="cap">' . $caption . '</span></div>';
    }

    private static function comment(string $name, string $badge, string $when, string $text, string $votes, bool $voted): string
    {
        $tag = $badge === '' ? '' : '<span class="badge' . ($badge === 'staff' ? ' staff' : '') . '">' . ($badge === 'staff' ? 'Staff' : 'Author') . '</span>';

        return '<div class="cmt">' . Kit::avatar($name, 38)
            . '<div><div class="who"><b>' . $name . '</b>' . $tag . '<span class="xs mut">' . $when . '</span></div>'
            . '<p>' . $text . '</p>'
            . '<div class="acts">'
            . '<button type="button" aria-pressed="' . ($voted ? 'true' : 'false') . '">' . Kit::icon('trend-up', 14) . $votes . '</button>'
            . '<button type="button">' . Kit::icon('mail', 14) . 'Reply</button>'
            . '<button type="button">' . Kit::icon('link', 14) . 'Link</button>'
            . '<button type="button">' . Kit::icon('flag', 14) . 'Report</button>'
            . '</div></div></div>';
    }

    private static function multiRow(string $label, string $count, bool $checked): string
    {
        return '<label class="mrow"><input type="checkbox" data-label="' . $label . '"' . ($checked ? ' checked' : '') . '>'
            . $label . '<span class="n">' . $count . '</span></label>';
    }

    private static function splitButton(string $label, string $icon, string $kind, array $items): string
    {
        $menu = '';
        foreach ($items as [$itemIcon, $itemLabel]) {
            $menu .= '<button type="button" role="menuitem">' . Kit::icon($itemIcon, 15) . $itemLabel . '</button>';
        }

        return '<span class="split">'
            . '<button class="btn main ' . $kind . '" type="button">' . Kit::icon($icon, 15) . $label . '</button>'
            . '<button class="btn toggle ' . $kind . '" type="button" aria-haspopup="true" aria-expanded="false" aria-label="More ' . strtolower($label) . ' options">'
            . Kit::icon('chevron-down', 15) . '</button>'
            . '<span class="menu" role="menu" hidden>' . $menu . '</span></span>';
    }
}
