<?php

namespace App\Modules\Component\database\seeders\Library;

use App\Modules\Component\database\seeders\Library\ComponentKit as Kit;

/**
 * The Forms category: sign-in screens, multi-step flows, settings panels and
 * every other place a person types something in.
 *
 * Two rules run through all of them. Every control has a real `<label>` tied
 * to it by id - a form template that teaches placeholder-as-label would do
 * more harm than good. And every form is a `<form>` with a submit button, so
 * pressing Enter does what it should before a line of JavaScript is written.
 */
final class FormLibrary
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
        return '<div class="wrap"><div class="card">' . implode('', $parts) . '</div></div>';
    }

    private static function head(string $title, string $sub = '', string $tools = ''): string
    {
        $caption = $sub === '' ? '' : '<p class="sub">' . $sub . '</p>';

        return '<div class="hd"><div><h2>' . $title . '</h2>' . $caption . '</div>'
            . ($tools === '' ? '' : '<div class="row" style="gap:8px;flex-wrap:wrap">' . $tools . '</div>')
            . '</div>';
    }

    /** A centred lockup - mark, title, subtitle - for the auth screens. */
    private static function lockup(string $icon, string $title, string $sub): string
    {
        return '<div style="text-align:center;padding:26px 24px 6px">'
            . '<span style="display:inline-flex;margin-bottom:14px">' . Kit::iconTile($icon, 'var(--acc)', 48) . '</span>'
            . '<h1>' . $title . '</h1><p class="sub" style="margin-top:6px">' . $sub . '</p></div>';
    }

    /** One labelled control, with optional hint or error underneath. */
    private static function field(string $id, string $label, string $control, string $hint = '', string $error = ''): string
    {
        $note = $error !== ''
            ? '<p class="err">' . $error . '</p>'
            : ($hint !== '' ? '<p class="hint">' . $hint . '</p>' : '');

        return '<div><label class="lb" for="' . $id . '">' . $label . '</label>' . $control . $note . '</div>';
    }

    private static function input(string $id, string $type = 'text', string $placeholder = '', string $extra = ''): string
    {
        return '<input class="in" id="' . $id . '" name="' . $id . '" type="' . $type . '"'
            . ($placeholder === '' ? '' : ' placeholder="' . $placeholder . '"') . ' ' . $extra . '>';
    }

    /** An input with an icon inside its left edge. */
    private static function iconInput(string $id, string $icon, string $type = 'text', string $placeholder = '', string $extra = ''): string
    {
        return '<span style="position:relative;display:block">'
            . '<span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--faint);pointer-events:none">' . Kit::icon($icon, 16) . '</span>'
            . '<input class="in" id="' . $id . '" name="' . $id . '" type="' . $type . '"'
            . ($placeholder === '' ? '' : ' placeholder="' . $placeholder . '"') . ' style="padding-left:38px" ' . $extra . '>'
            . '</span>';
    }

    private static function select(string $id, array $options, string $extra = ''): string
    {
        $items = '';
        foreach ($options as $value) {
            $items .= '<option>' . $value . '</option>';
        }

        return '<select class="in" id="' . $id . '" name="' . $id . '" ' . $extra . '>' . $items . '</select>';
    }

    private static function textarea(string $id, string $placeholder = '', int $rows = 4): string
    {
        return '<textarea class="in" id="' . $id . '" name="' . $id . '" rows="' . $rows . '" placeholder="' . $placeholder . '"></textarea>';
    }

    /** Fields laid out in columns that collapse to one on a narrow frame. */
    private static function cols(int $count, string ...$fields): string
    {
        $min = $count >= 3 ? 140 : 180;

        return '<div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(' . $min . 'px,1fr))">'
            . implode('', $fields) . '</div>';
    }

    /** Vertical rhythm for a form body. */
    private static function stack(string ...$parts): string
    {
        return '<form class="pad" style="display:grid;gap:16px" onsubmit="return false">' . implode('', $parts) . '</form>';
    }

    private static function submit(string $label, string $icon = ''): string
    {
        $glyph = $icon === '' ? '' : Kit::icon($icon, 16);

        return '<button class="btn pri" type="submit" style="width:100%;padding:11px">' . $glyph . $label . '</button>';
    }

    /** A labelled switch with a description - the settings-row workhorse. */
    private static function toggle(string $id, string $label, string $description, bool $on = false): string
    {
        return '<div class="tgl"><span><label for="' . $id . '">' . $label . '</label>'
            . '<span class="xs mut" style="display:block;margin-top:2px">' . $description . '</span></span>'
            . '<span class="sw"><input type="checkbox" id="' . $id . '"' . ($on ? ' checked' : '') . '><i></i></span></div>';
    }

    /** The CSS behind {@see toggle()}. */
    private static function toggleCss(): string
    {
        return ".tgl{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:13px 0;border-bottom:1px solid var(--bd)}\n"
            . ".tgl:last-child{border-bottom:0}\n"
            . ".tgl label{font-weight:600;font-size:13.5px;cursor:pointer}\n"
            . ".sw{position:relative;display:inline-block;width:40px;height:22px;flex:none}\n"
            . ".sw input{opacity:0;width:0;height:0}\n"
            . ".sw i{position:absolute;inset:0;border-radius:999px;background:var(--bd);transition:.2s;cursor:pointer}\n"
            . ".sw i::before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;border-radius:50%;background:#fff;transition:.2s}\n"
            . ".sw input:checked+i{background:var(--acc)}\n"
            . '.sw input:checked+i::before{transform:translateX(18px)}';
    }

    /** A radio card - a choice with a title, a description and a price. */
    private static function radioCard(string $name, string $id, string $title, string $description, string $meta = '', bool $checked = false): string
    {
        return '<label class="pick" for="' . $id . '">'
            . '<input type="radio" name="' . $name . '" id="' . $id . '"' . ($checked ? ' checked' : '') . '>'
            . '<span class="body"><span class="t">' . $title . '</span><span class="d">' . $description . '</span></span>'
            . ($meta === '' ? '' : '<span class="m">' . $meta . '</span>') . '</label>';
    }

    /** The CSS behind {@see radioCard()}. */
    private static function pickCss(): string
    {
        return ".pick{display:flex;align-items:center;gap:12px;padding:14px;border:1px solid var(--bd);border-radius:12px;cursor:pointer;transition:border-color .15s,background .15s}\n"
            . ".pick:hover{border-color:var(--acc)}\n"
            . ".pick input{width:18px;height:18px;accent-color:var(--acc);flex:none}\n"
            . ".pick .body{flex:1}\n"
            . ".pick .t{display:block;font-weight:650;font-size:13.5px}\n"
            . ".pick .d{display:block;font-size:12px;color:var(--mut);margin-top:2px}\n"
            . ".pick .m{font-weight:700;font-size:14px;white-space:nowrap}\n"
            . '.pick:has(input:checked){border-color:var(--acc);background:var(--acc-soft)}';
    }

    /** A step indicator for multi-step flows. */
    private static function steps(array $labels, int $active): string
    {
        $out = '<ol class="steps">';
        foreach (array_values($labels) as $i => $label) {
            $state = $i < $active ? 'done' : ($i === $active ? 'now' : '');
            $mark = $i < $active ? Kit::icon('check', 14, 2.6) : (string) ($i + 1);
            $out .= '<li class="' . $state . '"><span class="dot">' . $mark . '</span><span class="lbl">' . $label . '</span></li>';
        }

        return $out . '</ol>';
    }

    /** The CSS behind {@see steps()}. */
    private static function stepsCss(): string
    {
        return ".steps{list-style:none;display:flex;gap:0;margin:0;padding:16px 20px;border-bottom:1px solid var(--bd);overflow-x:auto}\n"
            . ".steps li{display:flex;align-items:center;gap:9px;flex:1;min-width:0;color:var(--mut);font-size:12.5px;font-weight:600}\n"
            . ".steps li::after{content:'';flex:1;height:2px;background:var(--bd);margin:0 10px;min-width:16px}\n"
            . ".steps li:last-child::after{display:none}\n"
            . ".steps .dot{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:var(--soft);border:1px solid var(--bd);font-size:12px;font-weight:700;flex:none}\n"
            . ".steps .lbl{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}\n"
            . ".steps .now{color:var(--ink)}\n"
            . ".steps .now .dot{background:var(--acc);border-color:var(--acc);color:#fff}\n"
            . ".steps .done{color:var(--ink)}\n"
            . ".steps .done .dot{background:var(--ok-bg);border-color:var(--ok);color:var(--ok)}\n"
            . '.steps .done::after{background:var(--ok)}';
    }

    /** A labelled rule across the form - "or continue with". */
    private static function divider(string $label): string
    {
        return '<div style="display:flex;align-items:center;gap:12px;color:var(--mut);font-size:12px">'
            . '<span style="flex:1;height:1px;background:var(--bd)"></span>' . $label
            . '<span style="flex:1;height:1px;background:var(--bd)"></span></div>';
    }

    /** A provider button for the social sign-in row. */
    private static function provider(string $label, string $icon): string
    {
        return '<button class="btn" type="button" style="flex:1;min-width:120px">' . Kit::icon($icon, 16) . $label . '</button>';
    }

    /** A checkbox with its label beside it. */
    private static function checkbox(string $id, string $label, bool $checked = false): string
    {
        return '<label for="' . $id . '" style="display:flex;align-items:flex-start;gap:9px;font-size:13px;cursor:pointer">'
            . '<input type="checkbox" id="' . $id . '" name="' . $id . '"' . ($checked ? ' checked' : '')
            . ' style="width:16px;height:16px;accent-color:var(--acc);margin-top:1px;flex:none"><span>' . $label . '</span></label>';
    }

    /** The footer strip carrying a form's actions. */
    private static function actions(string $primary, string $secondary = 'Cancel', string $note = ''): string
    {
        return '<div class="ft"><span>' . $note . '</span>'
            . '<span class="row" style="gap:8px"><button class="btn" type="button">' . $secondary . '</button>'
            . '<button class="btn pri" type="submit">' . $primary . '</button></span></div>';
    }

    /* ================================================================== */
    /* Definitions                                                         */
    /* ================================================================== */

    /** @return array<int,array<string,mixed>> */
    private static function setOne(): array
    {
        return [
            [
                'slug' => 'sign-in-form',
                'name' => 'Sign in form',
                'name_ar' => 'نموذج تسجيل الدخول',
                'tagline' => 'Email and password with provider buttons and remember me.',
                'tagline_ar' => 'بريد إلكتروني وكلمة مرور مع أزرار مزوّدي الدخول وخيار «تذكرني».',
                'summary' => 'The sign-in screen as it should be built: a real form so Enter submits, autocomplete tokens so password managers fill it, and the provider buttons placed after the password field rather than before it, since returning users mostly want the field they used last time.',
                'summary_ar' => 'شاشة تسجيل الدخول كما ينبغي أن تُبنى: نموذج حقيقي يُرسَل بضغط Enter، ورموز الإكمال التلقائي ليملأه مدير كلمات المرور، وأزرار مزوّدي الدخول بعد حقل كلمة المرور لا قبله، لأن المستخدم العائد يريد غالبًا الحقل الذي استخدمه في المرة السابقة.',
                'accent' => '#2563eb',
                'tags' => ['auth', 'login', 'sign-in'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Autocomplete tokens for password managers', 'Provider sign-in beneath the main form', 'Forgot-password link beside the label'],
                'features_ar' => ['رموز الإكمال التلقائي لمديري كلمات المرور', 'الدخول عبر المزوّدين أسفل النموذج الرئيسي', 'رابط «نسيت كلمة المرور» بجوار التسمية'],
                'height' => 620,
                'max' => 420,
                'body' => self::wrap(
                    self::lockup('lock', 'Welcome back', 'Sign in to your Frugal workspace'),
                    self::stack(
                        self::field('email', 'Email address', self::iconInput('email', 'mail', 'email', 'you@company.com', 'autocomplete="email" required')),
                        '<div><div class="row" style="justify-content:space-between"><label class="lb" for="password" style="margin:0">Password</label><a href="#" class="xs">Forgot?</a></div>'
                        . '<div style="margin-top:6px">' . self::iconInput('password', 'lock', 'password', 'Your password', 'autocomplete="current-password" required') . '</div></div>',
                        self::checkbox('remember', 'Keep me signed in for 30 days', true),
                        self::submit('Sign in', 'arrow-right'),
                        self::divider('or continue with'),
                        '<div class="row" style="gap:10px">' . self::provider('Google', 'globe') . self::provider('GitHub', 'code') . '</div>'
                    ),
                    '<div class="ft" style="justify-content:center"><span>New here? <a href="#">Create an account</a></span></div>'
                ),
            ],
            [
                'slug' => 'sign-up-form',
                'name' => 'Sign up form',
                'name_ar' => 'نموذج إنشاء حساب',
                'tagline' => 'Registration with a live password strength meter.',
                'tagline_ar' => 'تسجيل حساب مع مقياس حيّ لقوة كلمة المرور.',
                'summary' => 'Registration with the one thing most sign-up forms get wrong fixed: the password rules are shown as a live checklist rather than as an error after submitting, so the requirement is visible while it is being met.',
                'summary_ar' => 'نموذج تسجيل يعالج أكثر ما تخطئ فيه نماذج إنشاء الحسابات: تظهر قواعد كلمة المرور قائمةَ تحقق حيّة بدل رسالة خطأ بعد الإرسال، فيبقى الشرط ظاهرًا أثناء استيفائه.',
                'accent' => '#059669',
                'tags' => ['auth', 'register', 'password-strength'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Live strength meter driven by four rules', 'Rules shown as a checklist, not as errors', 'Terms acceptance gates the submit button'],
                'features_ar' => ['مقياس قوة حيّ تحكمه أربع قواعد', 'القواعد قائمة تحقق لا رسائل خطأ', 'الموافقة على الشروط شرط لتفعيل زر الإرسال'],
                'height' => 700,
                'max' => 440,
                'css' => ".rules{display:grid;gap:5px;margin-top:9px}\n.rule{display:flex;align-items:center;gap:7px;font-size:11.5px;color:var(--mut)}\n.rule.ok{color:var(--ok)}\n.bar{display:flex;gap:4px;margin-top:9px}\n.bar i{flex:1;height:4px;border-radius:999px;background:var(--bd);transition:background .2s}",
                'js' => "const password=document.getElementById('password');\n"
                    . "const rules=[\n"
                    . "  {el:document.getElementById('r1'),test:v=>v.length>=10},\n"
                    . "  {el:document.getElementById('r2'),test:v=>/[A-Z]/.test(v)},\n"
                    . "  {el:document.getElementById('r3'),test:v=>/[0-9]/.test(v)},\n"
                    . "  {el:document.getElementById('r4'),test:v=>/[^A-Za-z0-9]/.test(v)},\n"
                    . "];\n"
                    . "const segments=[...document.querySelectorAll('.bar i')];\n"
                    . "const colours=['#e11d48','#f59e0b','#0891b2','#059669'];\n"
                    . "password.addEventListener('input',()=>{\n"
                    . "  let score=0;\n"
                    . "  rules.forEach(rule=>{\n"
                    . "    const pass=rule.test(password.value);\n"
                    . "    rule.el.classList.toggle('ok',pass);\n"
                    . "    if(pass)score++;\n"
                    . "  });\n"
                    . "  segments.forEach((segment,i)=>{\n"
                    . "    segment.style.background=i<score?colours[score-1]:'var(--bd)';\n"
                    . "  });\n"
                    . "});\n"
                    . "document.getElementById('terms').addEventListener('change',event=>{\n"
                    . "  document.querySelector('button[type=submit]').disabled=!event.target.checked;\n"
                    . '});',
                'body' => self::wrap(
                    self::lockup('user', 'Create your account', 'Free forever, no card needed'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('first', 'First name', self::input('first', 'text', 'Lina', 'autocomplete="given-name" required')),
                            self::field('last', 'Last name', self::input('last', 'text', 'Haddad', 'autocomplete="family-name" required'))
                        ),
                        self::field('email', 'Work email', self::iconInput('email', 'mail', 'email', 'you@company.com', 'autocomplete="email" required')),
                        '<div><label class="lb" for="password">Password</label>'
                        . self::iconInput('password', 'lock', 'password', 'Choose a strong password', 'autocomplete="new-password" required')
                        . '<span class="bar"><i></i><i></i><i></i><i></i></span>'
                        . '<div class="rules">'
                        . '<span class="rule" id="r1">' . Kit::icon('check', 13, 2.6) . 'At least 10 characters</span>'
                        . '<span class="rule" id="r2">' . Kit::icon('check', 13, 2.6) . 'One capital letter</span>'
                        . '<span class="rule" id="r3">' . Kit::icon('check', 13, 2.6) . 'One number</span>'
                        . '<span class="rule" id="r4">' . Kit::icon('check', 13, 2.6) . 'One symbol</span>'
                        . '</div></div>',
                        self::checkbox('terms', 'I agree to the <a href="#">terms of service</a> and <a href="#">privacy policy</a>'),
                        '<button class="btn pri" type="submit" style="width:100%;padding:11px" disabled>' . Kit::icon('arrow-right', 16) . 'Create account</button>'
                    ),
                    '<div class="ft" style="justify-content:center"><span>Already registered? <a href="#">Sign in</a></span></div>'
                ),
            ],
            [
                'slug' => 'password-reset-form',
                'name' => 'Password reset request',
                'name_ar' => 'نموذج طلب استعادة كلمة المرور',
                'tagline' => 'Single-field reset that never confirms whether an account exists.',
                'tagline_ar' => 'استعادة بحقل واحد لا تكشف أبدًا عن وجود الحساب.',
                'summary' => 'Deliberately vague by design: the confirmation says a link has been sent if the address is registered, which is the wording that avoids turning a reset form into an account-enumeration oracle.',
                'summary_ar' => 'غامض عن قصد: تقول رسالة التأكيد إن الرابط أُرسل إذا كان العنوان مسجَّلًا، وهي الصياغة التي تمنع تحوّل نموذج الاستعادة إلى أداة لكشف الحسابات الموجودة.',
                'accent' => '#4f46e5',
                'tags' => ['auth', 'password', 'reset', 'security'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Neutral confirmation that avoids account enumeration', 'Single field, single purpose', 'Inline success state instead of a page change'],
                'features_ar' => ['تأكيد محايد يمنع كشف الحسابات', 'حقل واحد لغرض واحد', 'حالة نجاح ضمن الصفحة بدل الانتقال إلى صفحة أخرى'],
                'height' => 500,
                'max' => 420,
                'css' => "#done{display:none;text-align:center;padding:28px 24px}\n#done.show{display:block}\nform.hide{display:none}",
                'js' => "document.querySelector('form').addEventListener('submit',event=>{\n"
                    . "  event.preventDefault();\n"
                    . "  event.target.classList.add('hide');\n"
                    . "  document.getElementById('done').classList.add('show');\n"
                    . '});',
                'body' => self::wrap(
                    self::lockup('mail', 'Reset your password', 'We will email you a link to set a new one'),
                    self::stack(
                        self::field('email', 'Email address', self::iconInput('email', 'mail', 'email', 'you@company.com', 'autocomplete="email" required'), 'The link expires after one hour.'),
                        self::submit('Send reset link', 'send')
                    ),
                    '<div id="done"><span style="display:inline-flex;color:var(--ok);margin-bottom:12px">' . Kit::icon('check', 40, 2) . '</span>'
                    . '<h3>Check your inbox</h3><p class="sub" style="margin-top:6px">If that address is registered, a reset link is on its way. It expires in one hour.</p></div>',
                    '<div class="ft" style="justify-content:center"><a href="#">Back to sign in</a></div>'
                ),
            ],
            [
                'slug' => 'otp-verification-form',
                'name' => 'One-time code form',
                'name_ar' => 'نموذج رمز التحقق',
                'tagline' => 'Six code boxes that advance, paste and backspace correctly.',
                'tagline_ar' => 'ستة مربعات للرمز تتقدّم وتقبل اللصق والحذف كما يجب.',
                'summary' => 'The details that make a code field bearable: typing moves forward, backspace moves back, and pasting a six-digit code fills every box at once instead of dropping five characters. A resend timer stops the retry loop.',
                'summary_ar' => 'التفاصيل التي تجعل حقل الرمز محتملًا: الكتابة تنقلك إلى الأمام، والحذف يعيدك إلى الخلف، ولصق رمز من ستة أرقام يملأ المربعات كلها دفعة واحدة بدل ضياع خمسة أحرف. ومؤقّت إعادة الإرسال يوقف دوّامة المحاولات المتكررة.',
                'accent' => '#0891b2',
                'tags' => ['auth', 'otp', '2fa', 'verification'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Auto-advance, backspace and paste all handled', 'Countdown before resend becomes available', 'Numeric keypad on mobile via inputmode'],
                'features_ar' => ['دعم التقدّم التلقائي والحذف واللصق', 'عدّ تنازلي قبل إتاحة إعادة الإرسال', 'لوحة أرقام على الجوال عبر inputmode'],
                'height' => 520,
                'max' => 420,
                'css' => ".code{display:flex;gap:9px;justify-content:center}\n.code input{width:48px;height:56px;text-align:center;font-size:22px;font-weight:700;border:1px solid var(--bd);border-radius:12px;background:var(--card);color:var(--ink);font-variant-numeric:tabular-nums}\n.code input:focus{outline:none;border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-soft)}\n#resend{color:var(--mut)}",
                'js' => "const boxes=[...document.querySelectorAll('.code input')];\n"
                    . "boxes.forEach((box,index)=>{\n"
                    . "  box.addEventListener('input',()=>{\n"
                    . "    box.value=box.value.replace(/[^0-9]/g,'').slice(0,1);\n"
                    . "    if(box.value&&index<boxes.length-1)boxes[index+1].focus();\n"
                    . "  });\n"
                    . "  box.addEventListener('keydown',event=>{\n"
                    . "    if(event.key==='Backspace'&&!box.value&&index>0)boxes[index-1].focus();\n"
                    . "  });\n"
                    . "  box.addEventListener('paste',event=>{\n"
                    . "    event.preventDefault();\n"
                    . "    const digits=(event.clipboardData.getData('text')||'').replace(/[^0-9]/g,'').split('');\n"
                    . "    boxes.forEach((target,i)=>{if(digits[i])target.value=digits[i];});\n"
                    . "    boxes[Math.min(digits.length,boxes.length-1)].focus();\n"
                    . "  });\n"
                    . "});\n"
                    . "let left=30;\n"
                    . "const label=document.getElementById('resend');\n"
                    . "const timer=setInterval(()=>{\n"
                    . "  left--;\n"
                    . "  label.textContent=left>0?'Resend code in '+left+'s':'';\n"
                    . "  if(left<=0){clearInterval(timer);document.getElementById('again').hidden=false;}\n"
                    . '},1000);',
                'body' => self::wrap(
                    self::lockup('shield', 'Enter your code', 'We sent a six-digit code to o••@frugal.io'),
                    self::stack(
                        '<div class="code">'
                        . implode('', array_map(
                            fn($i) => '<input type="text" inputmode="numeric" autocomplete="one-time-code" aria-label="Digit ' . $i . '" maxlength="1">',
                            range(1, 6)
                        ))
                        . '</div>',
                        self::submit('Verify and continue', 'check'),
                        '<div style="text-align:center;font-size:12.5px"><span id="resend">Resend code in 30s</span>'
                        . '<button class="btn gh" type="button" id="again" hidden>Send a new code</button></div>'
                    ),
                    '<div class="ft" style="justify-content:center"><a href="#">Use a different method</a></div>'
                ),
            ],
            [
                'slug' => 'two-factor-setup-form',
                'name' => 'Two-factor setup',
                'name_ar' => 'نموذج تفعيل المصادقة الثنائية',
                'tagline' => 'Authenticator pairing with a drawn QR block and backup codes.',
                'tagline_ar' => 'ربط تطبيق المصادقة مع رمز QR مرسوم ورموز احتياطية.',
                'summary' => 'The pairing screen, including the part everyone forgets: the backup codes, shown once with a copy button and a warning that they will not be shown again. The QR block is drawn in CSS, so the template carries no image.',
                'summary_ar' => 'شاشة الربط بما فيها الجزء الذي ينساه الجميع: الرموز الاحتياطية، تُعرض مرة واحدة مع زر نسخ وتحذير بأنها لن تظهر مجددًا. ورمز QR مرسوم بـ CSS، فلا يحتاج القالب إلى أي صورة.',
                'accent' => '#7c3aed',
                'tags' => ['security', '2fa', 'setup', 'authenticator'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['QR placeholder drawn with CSS, no image file', 'Manual setup key with copy to clipboard', 'Backup codes presented once with a warning'],
                'features_ar' => ['عنصر QR نائب مرسوم بـ CSS بلا ملف صورة', 'مفتاح إعداد يدوي مع نسخ إلى الحافظة', 'رموز احتياطية تُعرض مرة واحدة مع تحذير'],
                'height' => 700,
                'max' => 480,
                'css' => ".qr{width:150px;height:150px;border-radius:12px;background:repeating-conic-gradient(var(--ink) 0 25%,transparent 0 50%) 0 0/16px 16px;border:8px solid var(--card);box-shadow:0 0 0 1px var(--bd);margin:0 auto}\n.keyline{display:flex;gap:8px;align-items:center}\n.keyline code{flex:1;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;letter-spacing:.08em;background:var(--soft);border:1px solid var(--bd);border-radius:9px;padding:10px 12px;text-align:center}\n.codes{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px}\n.codes span{background:var(--soft);border:1px dashed var(--bd);border-radius:8px;padding:8px;text-align:center}\n.warn{display:flex;gap:9px;padding:12px;border-radius:11px;background:var(--warn-bg);color:var(--warn);font-size:12.5px}",
                'js' => "document.getElementById('copy').addEventListener('click',async button=>{\n"
                    . "  const key=document.getElementById('setupkey').textContent.trim();\n"
                    . "  try{await navigator.clipboard.writeText(key);}catch{}\n"
                    . "  const target=document.getElementById('copy');\n"
                    . "  const was=target.textContent;\n"
                    . "  target.textContent='Copied';\n"
                    . "  setTimeout(()=>target.textContent=was,1400);\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Two-factor authentication', 'Step 2 of 3 · pair your authenticator app'),
                    self::stack(
                        '<div class="qr" role="img" aria-label="Pairing QR code"></div>',
                        self::field('setup', 'Or enter this key manually', '<div class="keyline"><code id="setupkey">JBSW Y3DP EHPK 3PXP</code><button class="btn" type="button" id="copy">Copy</button></div>'),
                        self::field('code', 'Enter the 6-digit code from the app', self::input('code', 'text', '000000', 'inputmode="numeric" maxlength="6" autocomplete="one-time-code"')),
                        '<div><span class="lb">Backup codes</span><div class="codes">'
                        . '<span>4f2a-91bd</span><span>7c10-44ef</span><span>b823-0d71</span><span>19ac-6e52</span><span>d470-2a88</span><span>8e31-c095</span>'
                        . '</div></div>',
                        '<div class="warn">' . Kit::icon('alert', 16) . '<span>Save these codes somewhere safe. They are shown once and each one works only a single time.</span></div>'
                    ),
                    self::actions('Turn on 2FA', 'Back', 'You can turn this off later in settings')
                ),
            ],
            [
                'slug' => 'contact-form',
                'name' => 'Contact form',
                'name_ar' => 'نموذج تواصل',
                'tagline' => 'Name, email, subject and message with a character counter.',
                'tagline_ar' => 'الاسم والبريد والموضوع والرسالة مع عدّاد للأحرف.',
                'summary' => 'A contact form that respects both sides: a subject select so messages arrive already triaged, a counter on the message so nobody writes past the limit, and an expected-reply note so the sender knows when to worry.',
                'summary_ar' => 'نموذج تواصل يراعي الطرفين: قائمة لاختيار الموضوع فتصل الرسائل مصنّفة مسبقًا، وعدّاد على الرسالة كي لا يتجاوز أحد الحد، وملاحظة بموعد الرد المتوقع ليعرف المرسل متى يقلق.',
                'accent' => '#0891b2',
                'tags' => ['contact', 'message', 'support'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Subject select that routes the message', 'Live character counter on the message', 'Expected reply time stated up front'],
                'features_ar' => ['قائمة موضوعات توجّه الرسالة إلى الجهة المناسبة', 'عدّاد أحرف حيّ للرسالة', 'موعد الرد المتوقع موضّح مسبقًا'],
                'height' => 620,
                'max' => 520,
                'css' => ".counter{font-size:11.5px;color:var(--mut);text-align:right;margin-top:5px}\n.counter.over{color:var(--bad);font-weight:650}",
                'js' => "const message=document.getElementById('message');\n"
                    . "const counter=document.getElementById('counter');\n"
                    . "const limit=500;\n"
                    . "message.addEventListener('input',()=>{\n"
                    . "  counter.textContent=message.value.length+' / '+limit;\n"
                    . "  counter.classList.toggle('over',message.value.length>limit);\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Get in touch', 'We reply to most messages within one working day'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('name', 'Your name', self::input('name', 'text', 'Lina Haddad', 'autocomplete="name" required')),
                            self::field('email', 'Email', self::input('email', 'email', 'you@company.com', 'autocomplete="email" required'))
                        ),
                        self::field('subject', 'What is this about?', self::select('subject', ['Product question', 'Billing or invoices', 'Report a bug', 'Partnership', 'Something else'])),
                        '<div><label class="lb" for="message">Message</label>' . self::textarea('message', 'Tell us what you need...', 5)
                        . '<div class="counter" id="counter">0 / 500</div></div>',
                        self::checkbox('copy', 'Email me a copy of this message')
                    ),
                    self::actions('Send message', 'Clear', 'We never share your address')
                ),
            ],
            [
                'slug' => 'newsletter-signup-form',
                'name' => 'Newsletter signup',
                'name_ar' => 'نموذج الاشتراك بالنشرة',
                'tagline' => 'Inline email capture with topic chips and proof.',
                'tagline_ar' => 'حقل بريد مدمج مع شارات للمواضيع ودليل اجتماعي.',
                'summary' => 'A compact capture block: one field on one line, topic chips so the subscription is relevant from the first send, and a subscriber count doing the work that a testimonial would otherwise do.',
                'summary_ar' => 'كتلة اشتراك مدمجة: حقل واحد في سطر واحد، وشارات للمواضيع لتكون النشرة مناسبة منذ أول إرسال، وعدد المشتركين يؤدي الدور الذي كانت ستؤديه شهادة عميل.',
                'accent' => '#db2777',
                'tags' => ['newsletter', 'email', 'marketing', 'inline'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Single-line capture with the button attached', 'Topic chips selected before subscribing', 'Social proof beneath the field'],
                'features_ar' => ['اشتراك في سطر واحد والزر ملتصق بالحقل', 'اختيار شارات المواضيع قبل الاشتراك', 'دليل اجتماعي أسفل الحقل'],
                'height' => 420,
                'max' => 560,
                'css' => ".line{display:flex;gap:9px;flex-wrap:wrap}\n.line .in{flex:1;min-width:200px}\n.chips{display:flex;gap:7px;flex-wrap:wrap}\n.chips label{cursor:pointer}\n.chips input{position:absolute;opacity:0;pointer-events:none}\n.chips span{display:inline-block;padding:6px 12px;border:1px solid var(--bd);border-radius:999px;font-size:12.5px;font-weight:600;color:var(--mut);transition:.15s}\n.chips label:hover span{border-color:var(--acc);color:var(--acc)}\n.chips input:checked+span{background:var(--acc);border-color:var(--acc);color:#fff}\n.proof{display:flex;align-items:center;gap:9px;font-size:12.5px;color:var(--mut)}",
                'body' => self::wrap(
                    '<div class="pad" style="padding:28px 24px">'
                    . '<span style="display:inline-flex;margin-bottom:14px">' . Kit::iconTile('mail', 'var(--acc)', 44) . '</span>'
                    . '<h1>Design notes, every other Tuesday</h1>'
                    . '<p class="sub" style="margin-top:6px;font-size:13.5px">Components, drawing tips and the occasional teardown. No more than two emails a month.</p>'
                    . '</div>',
                    self::stack(
                        '<div><span class="lb">What are you interested in?</span><div class="chips">'
                        . '<label><input type="checkbox" checked><span>Components</span></label>'
                        . '<label><input type="checkbox" checked><span>Drawing editor</span></label>'
                        . '<label><input type="checkbox"><span>Icons</span></label>'
                        . '<label><input type="checkbox"><span>Release notes</span></label>'
                        . '</div></div>',
                        '<div><label class="lb" for="email">Email address</label><div class="line">'
                        . self::input('email', 'email', 'you@company.com', 'autocomplete="email" required')
                        . '<button class="btn pri" type="submit" style="padding:10px 18px">Subscribe</button></div></div>',
                        '<div class="proof">' . Kit::avatarStack(['Lina Haddad', 'Omar Saleh', 'Maya Rahman', 'Sara Aziz'], 26)
                        . '<span>Joining <b>12,480</b> designers and developers</span></div>'
                    ),
                    '<div class="ft" style="justify-content:center"><span class="xs">Unsubscribe in one click, from any email</span></div>'
                ),
            ],
            [
                'slug' => 'checkout-payment-form',
                'name' => 'Checkout payment form',
                'name_ar' => 'نموذج الدفع',
                'tagline' => 'Card details with live formatting and a brand indicator.',
                'tagline_ar' => 'بيانات البطاقة مع تنسيق حيّ ومؤشر لنوعها.',
                'summary' => 'Card entry with the formatting people expect: digits group in fours as they are typed, the expiry gets its slash, and the brand mark changes as soon as the prefix identifies it. The order total stays visible while paying.',
                'summary_ar' => 'إدخال البطاقة بالتنسيق الذي يتوقعه الناس: تُجمَّع الأرقام في مجموعات من أربعة أثناء الكتابة، وتُضاف الشرطة المائلة إلى تاريخ الانتهاء، وتتغير علامة البطاقة فور تعرّف البادئة عليها. ويبقى إجمالي الطلب ظاهرًا أثناء الدفع.',
                'accent' => '#1d4ed8',
                'tags' => ['checkout', 'payment', 'card', 'ecommerce'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Card number grouped as it is typed', 'Expiry slash inserted automatically', 'Card brand detected from the prefix'],
                'features_ar' => ['رقم البطاقة يُجمَّع أثناء الكتابة', 'الشرطة المائلة في تاريخ الانتهاء تُضاف تلقائيًا', 'التعرّف على نوع البطاقة من البادئة'],
                'height' => 700,
                'max' => 460,
                'css' => ".brand{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:11px;font-weight:700;letter-spacing:.06em;color:var(--mut)}\n.total{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;background:var(--soft);border-bottom:1px solid var(--bd)}\n.total b{font-size:19px}\n.secure{display:flex;align-items:center;justify-content:center;gap:6px;font-size:11.5px;color:var(--mut)}",
                'js' => "const number=document.getElementById('cardnumber');\n"
                    . "const brand=document.getElementById('brand');\n"
                    . "number.addEventListener('input',()=>{\n"
                    . "  const digits=number.value.replace(/\\D/g,'').slice(0,16);\n"
                    . "  number.value=digits.replace(/(.{4})/g,'$1 ').trim();\n"
                    . "  brand.textContent=digits.startsWith('4')?'VISA':/^5[1-5]/.test(digits)?'MASTERCARD':/^3[47]/.test(digits)?'AMEX':'';\n"
                    . "});\n"
                    . "const expiry=document.getElementById('expiry');\n"
                    . "expiry.addEventListener('input',()=>{\n"
                    . "  const digits=expiry.value.replace(/\\D/g,'').slice(0,4);\n"
                    . "  expiry.value=digits.length>2?digits.slice(0,2)+'/'+digits.slice(2):digits;\n"
                    . "});\n"
                    . "document.getElementById('cvc').addEventListener('input',event=>{\n"
                    . "  event.target.value=event.target.value.replace(/\\D/g,'').slice(0,4);\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Payment', 'Step 3 of 3 · secure checkout'),
                    '<div class="total"><span class="mut sm">Order total</span><b class="num">$248.00</b></div>',
                    self::stack(
                        self::field('cardname', 'Name on card', self::input('cardname', 'text', 'LINA HADDAD', 'autocomplete="cc-name" required')),
                        '<div><label class="lb" for="cardnumber">Card number</label><span style="position:relative;display:block">'
                        . self::input('cardnumber', 'text', '4242 4242 4242 4242', 'inputmode="numeric" autocomplete="cc-number" required')
                        . '<span class="brand" id="brand"></span></span></div>',
                        self::cols(
                            2,
                            self::field('expiry', 'Expiry', self::input('expiry', 'text', 'MM/YY', 'inputmode="numeric" autocomplete="cc-exp" required')),
                            self::field('cvc', 'Security code', self::input('cvc', 'text', '123', 'inputmode="numeric" autocomplete="cc-csc" required'))
                        ),
                        self::field('country', 'Billing country', self::select('country', ['Saudi Arabia', 'United Arab Emirates', 'Jordan', 'Egypt', 'United Kingdom'])),
                        self::checkbox('save', 'Save this card for next time'),
                        self::submit('Pay $248.00', 'lock'),
                        '<div class="secure">' . Kit::icon('shield', 14) . 'Encrypted end to end · we never store card numbers</div>'
                    )
                ),
            ],
            [
                'slug' => 'shipping-address-form',
                'name' => 'Shipping address form',
                'name_ar' => 'نموذج عنوان الشحن',
                'tagline' => 'Address entry with country-aware fields and a saved-address picker.',
                'tagline_ar' => 'إدخال العنوان بحقول تراعي الدولة مع اختيار العناوين المحفوظة.',
                'summary' => 'Address forms fail on the details: this one keeps the second address line optional and clearly labelled, puts the postcode next to the city where it is read, and offers saved addresses above the fields rather than below them.',
                'summary_ar' => 'تفشل نماذج العناوين في التفاصيل: هذا النموذج يُبقي سطر العنوان الثاني اختياريًا وموسومًا بوضوح، ويضع الرمز البريدي بجوار المدينة حيث يُقرأ، ويعرض العناوين المحفوظة فوق الحقول لا تحتها.',
                'accent' => '#0f766e',
                'tags' => ['address', 'shipping', 'checkout', 'ecommerce'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Saved addresses offered before the blank fields', 'Optional line two marked as optional', 'Delivery instructions field for the courier'],
                'features_ar' => ['العناوين المحفوظة تُعرض قبل الحقول الفارغة', 'السطر الثاني موسوم بأنه اختياري', 'حقل تعليمات التوصيل لمندوب الشحن'],
                'height' => 720,
                'max' => 520,
                'css' => self::pickCss(),
                'body' => self::wrap(
                    self::head('Shipping address', 'Step 2 of 3 · where should this go?'),
                    self::stack(
                        '<div style="display:grid;gap:10px">'
                        . self::radioCard('saved', 'home', 'Home', 'Al Nakheel, Riyadh 12388', 'Default', true)
                        . self::radioCard('saved', 'work', 'Work', 'King Fahd Road, Riyadh 11564')
                        . self::radioCard('saved', 'new', 'Use a new address', 'Enter the details below')
                        . '</div>',
                        '<div style="height:1px;background:var(--bd)"></div>',
                        self::cols(
                            2,
                            self::field('fullname', 'Full name', self::input('fullname', 'text', 'Lina Haddad', 'autocomplete="name"')),
                            self::field('phone', 'Phone', self::input('phone', 'tel', '+966 5x xxx xxxx', 'autocomplete="tel"'))
                        ),
                        self::field('line1', 'Address', self::input('line1', 'text', 'Street and building number', 'autocomplete="address-line1"')),
                        self::field('line2', 'Apartment, floor <span class="mut" style="font-weight:500">(optional)</span>', self::input('line2', 'text', 'Apt 14, 3rd floor', 'autocomplete="address-line2"')),
                        self::cols(
                            3,
                            self::field('city', 'City', self::input('city', 'text', 'Riyadh', 'autocomplete="address-level2"')),
                            self::field('postcode', 'Postcode', self::input('postcode', 'text', '12388', 'autocomplete="postal-code"')),
                            self::field('country2', 'Country', self::select('country2', ['Saudi Arabia', 'United Arab Emirates', 'Jordan', 'Egypt']))
                        ),
                        self::field('notes', 'Delivery notes <span class="mut" style="font-weight:500">(optional)</span>', self::textarea('notes', 'Gate code, landmark, preferred time...', 3))
                    ),
                    self::actions('Continue to payment', 'Back to basket')
                ),
            ],
            [
                'slug' => 'multi-step-wizard-form',
                'name' => 'Multi-step wizard',
                'name_ar' => 'نموذج متعدد الخطوات',
                'tagline' => 'Three panels behind one step indicator, with working navigation.',
                'tagline_ar' => 'ثلاث لوحات خلف مؤشر خطوات واحد مع تنقّل فعّال.',
                'summary' => 'A real wizard rather than a picture of one: the buttons move between panels, the indicator marks completed steps, and the back button never loses what was typed - the panels are all in the document, only hidden.',
                'summary_ar' => 'معالج حقيقي لا صورة معالج: الأزرار تنقلك بين اللوحات، والمؤشر يعلّم الخطوات المكتملة، وزر الرجوع لا يُضيّع ما كُتب أبدًا، فاللوحات كلها موجودة في المستند ومخفية فقط.',
                'accent' => '#7c3aed',
                'tags' => ['wizard', 'multi-step', 'onboarding', 'stepper'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Working forward and back navigation', 'Completed steps ticked in the indicator', 'Values kept when stepping backwards'],
                'features_ar' => ['تنقّل فعّال إلى الأمام والخلف', 'علامة صح على الخطوات المكتملة في المؤشر', 'حفظ القيم عند الرجوع إلى الخلف'],
                'height' => 620,
                'max' => 560,
                'css' => self::stepsCss() . "\n.panel{display:none}\n.panel.on{display:grid;gap:16px}",
                'js' => "let index=0;\n"
                    . "const panels=[...document.querySelectorAll('.panel')];\n"
                    . "const items=[...document.querySelectorAll('.steps li')];\n"
                    . "const back=document.getElementById('back');\n"
                    . "const next=document.getElementById('next');\n"
                    . "function render(){\n"
                    . "  panels.forEach((panel,i)=>panel.classList.toggle('on',i===index));\n"
                    . "  items.forEach((item,i)=>{\n"
                    . "    item.className=i<index?'done':i===index?'now':'';\n"
                    . "    item.querySelector('.dot').textContent=i<index?'\\u2713':String(i+1);\n"
                    . "  });\n"
                    . "  back.disabled=index===0;\n"
                    . "  next.textContent=index===panels.length-1?'Finish':'Continue';\n"
                    . "}\n"
                    . "back.addEventListener('click',()=>{if(index>0){index--;render();}});\n"
                    . "next.addEventListener('click',()=>{if(index<panels.length-1){index++;render();}});\n"
                    . 'render();',
                'body' => self::wrap(
                    self::head('Set up your workspace', 'This takes about two minutes'),
                    self::steps(['Workspace', 'Team', 'Preferences'], 0),
                    '<form class="pad" onsubmit="return false" style="display:grid;gap:16px">'
                    . '<div class="panel on">'
                    . self::field('wsname', 'Workspace name', self::input('wsname', 'text', 'Acme Design'))
                    . self::field('wsurl', 'Workspace URL', self::input('wsurl', 'text', 'acme', 'aria-describedby="urlhint"'), 'frugal.app/<b>acme</b>')
                    . self::field('wssize', 'How big is your team?', self::select('wssize', ['Just me', '2 - 10 people', '11 - 50 people', '51 - 200 people', 'More than 200']))
                    . '</div>'
                    . '<div class="panel">'
                    . self::field('invite', 'Invite teammates by email', self::textarea('invite', "lina@acme.com\nomar@acme.com", 3), 'One address per line. You can do this later.')
                    . self::field('role', 'Default role for invitees', self::select('role', ['Viewer', 'Editor', 'Admin']))
                    . '</div>'
                    . '<div class="panel">'
                    . self::field('theme', 'Interface theme', self::select('theme', ['Match my system', 'Always light', 'Always dark']))
                    . self::field('lang', 'Language', self::select('lang', ['English', 'العربية', 'Français', 'Türkçe']))
                    . self::checkbox('digest', 'Send me a weekly summary of workspace activity', true)
                    . '</div>'
                    . '</form>',
                    '<div class="ft"><button class="btn" type="button" id="back">Back</button>'
                    . '<button class="btn pri" type="button" id="next">Continue</button></div>'
                ),
            ],
            [
                'slug' => 'profile-settings-form',
                'name' => 'Profile settings form',
                'name_ar' => 'نموذج إعدادات الملف الشخصي',
                'tagline' => 'Avatar, name, bio and public profile fields.',
                'tagline_ar' => 'الصورة الرمزية والاسم والنبذة وحقول الملف العام.',
                'summary' => 'The account settings panel: the avatar sits at the top with change and remove beside it, the bio carries a counter, and the sticky action bar means a long form can be saved without scrolling back to the top.',
                'summary_ar' => 'لوحة إعدادات الحساب: الصورة الرمزية في الأعلى وبجوارها خيارا التغيير والإزالة، وحقل النبذة مزوّد بعدّاد، وشريط الإجراءات الثابت يتيح حفظ نموذج طويل دون التمرير إلى الأعلى.',
                'accent' => '#2563eb',
                'tags' => ['settings', 'profile', 'account', 'avatar'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Avatar block with change and remove actions', 'Bio field with a live counter', 'Action bar pinned to the bottom of the card'],
                'features_ar' => ['كتلة الصورة الرمزية مع إجراءي التغيير والإزالة', 'حقل النبذة مع عدّاد حيّ', 'شريط إجراءات مثبّت أسفل البطاقة'],
                'height' => 700,
                'max' => 620,
                'css' => ".avline{display:flex;align-items:center;gap:16px;flex-wrap:wrap}\n.counter{font-size:11.5px;color:var(--mut);text-align:right;margin-top:5px}",
                'js' => "const bio=document.getElementById('bio');\n"
                    . "const counter=document.getElementById('biocount');\n"
                    . "bio.addEventListener('input',()=>counter.textContent=bio.value.length+' / 160');",
                'body' => self::wrap(
                    self::head('Profile', 'This is how you appear across the workspace'),
                    self::stack(
                        '<div class="avline">' . Kit::avatar('Lina Haddad', 72)
                        . '<div><div class="row" style="gap:8px"><button class="btn" type="button">' . Kit::icon('upload', 15) . 'Change photo</button>'
                        . '<button class="btn gh" type="button">Remove</button></div>'
                        . '<p class="hint">JPG or PNG, at least 256×256 and under 2 MB.</p></div></div>',
                        self::cols(
                            2,
                            self::field('dispname', 'Display name', self::input('dispname', 'text', 'Lina Haddad')),
                            self::field('handle', 'Username', self::input('handle', 'text', 'lhaddad'))
                        ),
                        self::field('title', 'Job title', self::input('title', 'text', 'Head of design')),
                        '<div><label class="lb" for="bio">Bio</label>' . self::textarea('bio', 'A sentence or two about what you do.', 3)
                        . '<div class="counter" id="biocount">0 / 160</div></div>',
                        self::cols(
                            2,
                            self::field('location', 'Location', self::iconInput('location', 'map-pin', 'text', 'Beirut, Lebanon')),
                            self::field('site', 'Website', self::iconInput('site', 'link', 'url', 'https://'))
                        ),
                        self::checkbox('public', 'Show my profile to everyone in the workspace', true)
                    ),
                    self::actions('Save changes', 'Discard', 'Changes apply immediately')
                ),
            ],
            [
                'slug' => 'notification-preferences-form',
                'name' => 'Notification preferences',
                'name_ar' => 'نموذج تفضيلات الإشعارات',
                'tagline' => 'A channel matrix plus a quiet-hours schedule.',
                'tagline_ar' => 'مصفوفة قنوات مع جدول لساعات الهدوء.',
                'summary' => 'Preferences as a matrix rather than a list of switches: event types down, channels across, so "email me but do not push" is one tick instead of a paragraph of settings. Quiet hours sit underneath as a schedule.',
                'summary_ar' => 'التفضيلات مصفوفةً لا قائمة مفاتيح: أنواع الأحداث عموديًا والقنوات أفقيًا، فيصبح «راسلني بالبريد دون إشعار فوري» علامة واحدة بدل فقرة من الإعدادات. وتأتي ساعات الهدوء أسفلها جدولًا زمنيًا.',
                'accent' => '#4f46e5',
                'tags' => ['settings', 'notifications', 'preferences', 'matrix'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Channel matrix instead of a switch list', 'Quiet hours with a timezone', 'Per-row descriptions of what triggers the alert'],
                'features_ar' => ['مصفوفة قنوات بدل قائمة مفاتيح تبديل', 'ساعات هدوء مع منطقة زمنية', 'وصف لكل صف يوضح ما يُطلق التنبيه'],
                'height' => 680,
                'max' => 640,
                'css' => self::toggleCss() . "\ntable{width:100%}\nth,td{text-align:center}\nth:first-child,td:first-child{text-align:left}\ntd input{width:17px;height:17px;accent-color:var(--acc);cursor:pointer}\n.evt{font-weight:650;font-size:13.5px}\n.evtd{font-size:11.5px;color:var(--mut)}",
                'body' => self::wrap(
                    self::head('Notifications', 'Choose what reaches you, and where'),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Event</th><th>Email</th><th>Push</th><th>SMS</th></tr></thead><tbody>'
                    . '<tr><td><span class="evt">Mentions</span><div class="evtd">Someone names you in a comment</div></td><td><input type="checkbox" checked aria-label="Email mentions"></td><td><input type="checkbox" checked aria-label="Push mentions"></td><td><input type="checkbox" aria-label="SMS mentions"></td></tr>'
                    . '<tr><td><span class="evt">Assignments</span><div class="evtd">A task is assigned to you</div></td><td><input type="checkbox" checked aria-label="Email assignments"></td><td><input type="checkbox" checked aria-label="Push assignments"></td><td><input type="checkbox" aria-label="SMS assignments"></td></tr>'
                    . '<tr><td><span class="evt">Billing</span><div class="evtd">Invoices, failed payments, renewals</div></td><td><input type="checkbox" checked aria-label="Email billing"></td><td><input type="checkbox" aria-label="Push billing"></td><td><input type="checkbox" checked aria-label="SMS billing"></td></tr>'
                    . '<tr><td><span class="evt">Security</span><div class="evtd">New sign-ins and password changes</div></td><td><input type="checkbox" checked aria-label="Email security"></td><td><input type="checkbox" checked aria-label="Push security"></td><td><input type="checkbox" checked aria-label="SMS security"></td></tr>'
                    . '<tr><td><span class="evt">Product news</span><div class="evtd">Releases and occasional tips</div></td><td><input type="checkbox" aria-label="Email product news"></td><td><input type="checkbox" aria-label="Push product news"></td><td><input type="checkbox" aria-label="SMS product news"></td></tr>'
                    . '</tbody></table></div>',
                    self::stack(
                        self::toggle('quiet', 'Quiet hours', 'Hold non-urgent notifications overnight', true),
                        self::cols(
                            3,
                            self::field('from', 'From', self::input('from', 'time', '', 'value="22:00"')),
                            self::field('to', 'Until', self::input('to', 'time', '', 'value="07:30"')),
                            self::field('tz', 'Timezone', self::select('tz', ['Asia/Riyadh', 'Asia/Amman', 'Africa/Cairo', 'Europe/London']))
                        )
                    ),
                    self::actions('Save preferences', 'Reset to defaults')
                ),
            ],
            [
                'slug' => 'billing-plan-form',
                'name' => 'Plan selection form',
                'name_ar' => 'نموذج اختيار الباقة',
                'tagline' => 'Radio cards for plans with a monthly and yearly switch.',
                'tagline_ar' => 'بطاقات اختيار للباقات مع مفتاح بين الشهري والسنوي.',
                'summary' => 'Plan choice as radio cards, which keeps it a real form control - keyboard reachable and submittable - while looking like the pricing block people expect. The billing period switch shows the saving instead of claiming one.',
                'summary_ar' => 'اختيار الباقة عبر بطاقات اختيار أحادي، فيبقى عنصر نموذج حقيقيًا يمكن الوصول إليه بلوحة المفاتيح وإرساله، مع مظهر جدول الأسعار الذي يتوقعه الناس. ومفتاح فترة الفوترة يعرض مقدار التوفير بدل الاكتفاء بادّعائه.',
                'accent' => '#7c3aed',
                'tags' => ['billing', 'plans', 'pricing', 'radio'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Plans as accessible radio cards', 'Monthly and yearly toggle that updates prices', 'Saving on yearly shown as a figure'],
                'features_ar' => ['الباقات بطاقات اختيار سهلة الوصول', 'مفتاح تبديل شهري وسنوي يحدّث الأسعار', 'التوفير في الاشتراك السنوي يظهر رقمًا'],
                'height' => 640,
                'max' => 540,
                'css' => self::pickCss() . "\n.seg{display:inline-flex;padding:3px;background:var(--soft);border:1px solid var(--bd);border-radius:999px}\n.seg button{border:0;background:none;padding:7px 15px;border-radius:999px;font:inherit;font-size:12.5px;font-weight:650;color:var(--mut);cursor:pointer}\n.seg button[aria-pressed=true]{background:var(--acc);color:#fff}\n.save{margin-left:8px;font-size:11.5px;color:var(--ok);font-weight:700}",
                'js' => "const prices={month:['\$0','\$29','\$79'],year:['\$0','\$23','\$63']};\n"
                    . "const cells=[...document.querySelectorAll('.pick .m')];\n"
                    . "document.querySelectorAll('.seg button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.seg button').forEach(b=>b.setAttribute('aria-pressed','false'));\n"
                    . "    button.setAttribute('aria-pressed','true');\n"
                    . "    const period=button.dataset.period;\n"
                    . "    cells.forEach((cell,i)=>cell.innerHTML=prices[period][i]+'<span class=\"xs mut\">/mo</span>');\n"
                    . "    document.getElementById('note').hidden=period!=='year';\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Choose a plan', 'Change or cancel at any time', '<span class="seg"><button type="button" data-period="month" aria-pressed="true">Monthly</button><button type="button" data-period="year" aria-pressed="false">Yearly<span class="save">-20%</span></button></span>'),
                    self::stack(
                        '<div style="display:grid;gap:10px">'
                        . self::radioCard('plan', 'starter', 'Starter', '3 projects · 1 seat · community support', '$0<span class="xs mut">/mo</span>')
                        . self::radioCard('plan', 'studio', 'Studio', 'Unlimited projects · 10 seats · custom domain', '$29<span class="xs mut">/mo</span>', true)
                        . self::radioCard('plan', 'agency', 'Agency', 'Everything in Studio · 50 seats · audit log · priority support', '$79<span class="xs mut">/mo</span>')
                        . '</div>',
                        '<p class="hint" id="note" hidden>Billed once a year. You save $139 on Studio.</p>',
                        self::field('promo', 'Promotion code <span class="mut" style="font-weight:500">(optional)</span>', self::input('promo', 'text', 'Enter a code'))
                    ),
                    self::actions('Continue to payment', 'Compare plans', '14-day money-back guarantee')
                ),
            ],
            [
                'slug' => 'feedback-rating-form',
                'name' => 'Feedback rating form',
                'name_ar' => 'نموذج تقييم وملاحظات',
                'tagline' => 'Star rating that reveals the right follow-up question.',
                'tagline_ar' => 'تقييم بالنجوم يُظهر سؤال المتابعة المناسب.',
                'summary' => 'The follow-up question changes with the score: a low rating asks what went wrong, a high one asks what to quote. One form, two intents, and far better answers than a single generic comment box.',
                'summary_ar' => 'يتغير سؤال المتابعة بحسب التقييم: التقييم المنخفض يسأل عمّا حدث من خطأ، والمرتفع يسأل عمّا يمكن اقتباسه. نموذج واحد لغرضين، وإجابات أفضل بكثير من مربع تعليق عام واحد.',
                'accent' => '#d97706',
                'tags' => ['feedback', 'rating', 'stars', 'survey'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Keyboard-accessible star rating built on radios', 'Follow-up question adapts to the score', 'Optional contact permission'],
                'features_ar' => ['تقييم بالنجوم مبني على أزرار اختيار ويعمل بلوحة المفاتيح', 'سؤال المتابعة يتكيّف مع التقييم', 'إذن اختياري بالتواصل'],
                'height' => 560,
                'max' => 480,
                'css' => ".rate{display:flex;flex-direction:row-reverse;justify-content:center;gap:6px}\n.rate input{position:absolute;opacity:0}\n.rate label{cursor:pointer;color:var(--bd);transition:color .15s}\n.rate label:hover,.rate label:hover ~ label,.rate input:checked ~ label{color:#f59e0b}\n.rate input:focus-visible+label{outline:2px solid var(--acc);outline-offset:3px;border-radius:4px}\n#followup{display:none}\n#followup.show{display:block}",
                'js' => "const prompts={\n"
                    . "  1:'We are sorry. What went wrong?',\n"
                    . "  2:'What would have made this better?',\n"
                    . "  3:'What is the one thing we should fix?',\n"
                    . "  4:'Thank you. What nearly made it five?',\n"
                    . "  5:'Wonderful. What should we never change?'\n"
                    . "};\n"
                    . "document.querySelectorAll('.rate input').forEach(input=>{\n"
                    . "  input.addEventListener('change',()=>{\n"
                    . "    document.getElementById('prompt').textContent=prompts[input.value];\n"
                    . "    document.getElementById('followup').classList.add('show');\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::lockup('star', 'How did we do?', 'Your answer goes straight to the team'),
                    self::stack(
                        '<div class="rate">'
                        . implode('', array_map(function ($n) {
                            return '<input type="radio" name="score" id="s' . $n . '" value="' . $n . '">'
                                . '<label for="s' . $n . '" aria-label="' . $n . ' stars">'
                                . '<svg viewBox="0 0 24 24" width="36" height="36" fill="currentColor" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/></svg></label>';
                        }, [5, 4, 3, 2, 1]))
                        . '</div>',
                        '<div id="followup"><label class="lb" for="why" id="prompt">Tell us more</label>' . self::textarea('why', 'Optional, but it helps a lot.', 4) . '</div>',
                        self::checkbox('contactme', 'You may contact me about this feedback'),
                        self::submit('Send feedback', 'send')
                    )
                ),
            ],
            [
                'slug' => 'nps-survey-form',
                'name' => 'NPS survey form',
                'name_ar' => 'نموذج استبيان NPS',
                'tagline' => 'Zero to ten scale with detractor and promoter colouring.',
                'tagline_ar' => 'مقياس من صفر إلى عشرة بألوان المنتقدين والمروّجين.',
                'summary' => 'The standard eleven-point scale, coloured in the bands the score is actually read in - red for detractors, amber for passives, green for promoters - so the answer teaches the respondent what they just said.',
                'summary_ar' => 'المقياس القياسي ذو النقاط الإحدى عشرة، ملوّنًا بالفئات التي تُقرأ بها النتيجة فعلًا: الأحمر للمنتقدين، والكهرماني للمحايدين، والأخضر للمروّجين، فتُعلّم الإجابة المستجيب ما قاله للتو.',
                'accent' => '#059669',
                'tags' => ['survey', 'nps', 'research', 'scale'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Eleven-point scale coloured by NPS band', 'Anchor labels at both ends', 'Reason box appears once a score is picked'],
                'features_ar' => ['مقياس من 11 نقطة ملوّن بفئات NPS', 'تسميات مرجعية عند الطرفين', 'مربع السبب يظهر بعد اختيار الدرجة'],
                'height' => 520,
                'max' => 560,
                'css' => ".scale{display:flex;gap:5px;flex-wrap:wrap;justify-content:center}\n.scale input{position:absolute;opacity:0}\n.scale label{display:flex;align-items:center;justify-content:center;width:42px;height:42px;border:1px solid var(--bd);border-radius:10px;font-weight:700;cursor:pointer;transition:.15s;font-variant-numeric:tabular-nums}\n.scale label:hover{border-color:var(--acc)}\n.scale input:checked+label{color:#fff}\n.scale input:checked+label[data-band=d]{background:#e11d48;border-color:#e11d48}\n.scale input:checked+label[data-band=p]{background:#f59e0b;border-color:#f59e0b}\n.scale input:checked+label[data-band=r]{background:#059669;border-color:#059669}\n.anchors{display:flex;justify-content:space-between;font-size:11.5px;color:var(--mut);margin-top:9px}\n#why{display:none}\n#why.show{display:block}",
                'js' => "document.querySelectorAll('.scale input').forEach(input=>{\n"
                    . "  input.addEventListener('change',()=>document.getElementById('why').classList.add('show'));\n"
                    . '});',
                'body' => self::wrap(
                    self::head('One quick question', 'It takes about ten seconds'),
                    self::stack(
                        '<p style="text-align:center;font-size:15px;font-weight:600">How likely are you to recommend Frugal to a colleague?</p>',
                        '<div><div class="scale">'
                        . implode('', array_map(function ($n) {
                            $band = $n <= 6 ? 'd' : ($n <= 8 ? 'p' : 'r');

                            return '<input type="radio" name="nps" id="n' . $n . '" value="' . $n . '">'
                                . '<label for="n' . $n . '" data-band="' . $band . '">' . $n . '</label>';
                        }, range(0, 10)))
                        . '</div><div class="anchors"><span>Not at all likely</span><span>Extremely likely</span></div></div>',
                        '<div id="why"><label class="lb" for="reason">What is the main reason for your score?</label>' . self::textarea('reason', 'Optional', 3) . '</div>',
                        self::submit('Submit')
                    ),
                    '<div class="ft" style="justify-content:center"><span class="xs">Answers are anonymous unless you tell us who you are</span></div>'
                ),
            ],
            [
                'slug' => 'job-application-form',
                'name' => 'Job application form',
                'name_ar' => 'نموذج طلب توظيف',
                'tagline' => 'Applicant details with a CV drop zone and portfolio links.',
                'tagline_ar' => 'بيانات المتقدّم مع منطقة إفلات للسيرة الذاتية وروابط الأعمال.',
                'summary' => 'An application form that asks only what a first screen needs: contact details, a CV, links, and one question worth reading. The drop zone reports the chosen file rather than leaving the applicant guessing whether it attached.',
                'summary_ar' => 'نموذج توظيف لا يطلب إلا ما يحتاجه الفرز الأولي: بيانات التواصل، والسيرة الذاتية، والروابط، وسؤالًا واحدًا يستحق القراءة. وتعرض منطقة الإفلات اسم الملف المختار بدل ترك المتقدّم يتساءل هل أُرفق أم لا.',
                'accent' => '#4f46e5',
                'tags' => ['hiring', 'application', 'upload', 'careers'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Drag-and-drop CV upload with a named file state', 'Portfolio and profile links', 'One open question instead of a cover letter'],
                'features_ar' => ['رفع السيرة الذاتية بالسحب والإفلات مع إظهار اسم الملف', 'روابط معرض الأعمال والملف الشخصي', 'سؤال مفتوح واحد بدل خطاب تقديم'],
                'height' => 760,
                'max' => 580,
                'css' => ".drop{border:2px dashed var(--bd);border-radius:14px;padding:26px;text-align:center;transition:.15s;cursor:pointer}\n.drop:hover,.drop.over{border-color:var(--acc);background:var(--acc-soft)}\n.drop input{display:none}\n.drop .f{display:none;align-items:center;justify-content:center;gap:9px;font-weight:650}\n.drop.has .f{display:flex}\n.drop.has .p{display:none}",
                'js' => "const drop=document.querySelector('.drop');\n"
                    . "const file=drop.querySelector('input');\n"
                    . "const name=document.getElementById('fname');\n"
                    . "drop.addEventListener('click',()=>file.click());\n"
                    . "drop.addEventListener('dragover',event=>{event.preventDefault();drop.classList.add('over');});\n"
                    . "drop.addEventListener('dragleave',()=>drop.classList.remove('over'));\n"
                    . "drop.addEventListener('drop',event=>{\n"
                    . "  event.preventDefault();\n"
                    . "  drop.classList.remove('over');\n"
                    . "  if(event.dataTransfer.files[0])show(event.dataTransfer.files[0].name);\n"
                    . "});\n"
                    . "file.addEventListener('change',()=>{if(file.files[0])show(file.files[0].name);});\n"
                    . "function show(label){name.textContent=label;drop.classList.add('has');}",
                'body' => self::wrap(
                    self::head('Senior front-end engineer', 'Remote · full time · apply by 30 September'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('appname', 'Full name', self::input('appname', 'text', 'Rana Khalil', 'autocomplete="name" required')),
                            self::field('appemail', 'Email', self::input('appemail', 'email', 'you@mail.com', 'autocomplete="email" required'))
                        ),
                        self::cols(
                            2,
                            self::field('appphone', 'Phone', self::input('appphone', 'tel', '+962 7x xxx xxxx', 'autocomplete="tel"')),
                            self::field('appwhere', 'Where are you based?', self::input('appwhere', 'text', 'City, country'))
                        ),
                        '<div><span class="lb">CV or résumé</span>'
                        . '<div class="drop"><input type="file" accept=".pdf,.doc,.docx" aria-label="Upload your CV">'
                        . '<div class="p"><span style="display:inline-flex;color:var(--acc);margin-bottom:8px">' . Kit::icon('upload', 26) . '</span>'
                        . '<div class="bold">Drop your CV here, or click to browse</div><div class="xs mut" style="margin-top:3px">PDF or Word, up to 10 MB</div></div>'
                        . '<div class="f">' . Kit::icon('file', 18) . '<span id="fname"></span></div></div></div>',
                        self::cols(
                            2,
                            self::field('portfolio', 'Portfolio', self::iconInput('portfolio', 'link', 'url', 'https://')),
                            self::field('github', 'GitHub or GitLab', self::iconInput('github', 'code', 'url', 'https://'))
                        ),
                        self::field('appwhy', 'What is a piece of work you are proud of, and why?', self::textarea('appwhy', 'A few sentences is plenty.', 4)),
                        self::checkbox('appterms', 'I consent to my details being kept for this application')
                    ),
                    self::actions('Submit application', 'Save draft', 'We reply to every applicant')
                ),
            ],
            [
                'slug' => 'support-ticket-form',
                'name' => 'Support ticket form',
                'name_ar' => 'نموذج تذكرة دعم',
                'tagline' => 'Issue report with severity, product area and attachments.',
                'tagline_ar' => 'بلاغ مشكلة مع درجة الخطورة وقسم المنتج والمرفقات.',
                'summary' => 'A ticket form written to reduce back and forth: severity is defined in words rather than left to interpretation, the product area routes the ticket, and the description field is pre-structured with what happened and what was expected.',
                'summary_ar' => 'نموذج تذكرة مصمَّم لتقليل المراسلات المتكررة: درجة الخطورة معرَّفة بالكلمات لا متروكة للتأويل، وقسم المنتج يوجّه التذكرة، وحقل الوصف منظَّم مسبقًا بما حدث وما كان متوقعًا.',
                'accent' => '#e11d48',
                'tags' => ['support', 'ticket', 'helpdesk', 'bug'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Severity options defined in plain language', 'Area select that routes to the right queue', 'Description pre-structured to cut follow-up questions'],
                'features_ar' => ['خيارات الخطورة معرَّفة بلغة واضحة', 'قائمة الأقسام توجّه التذكرة إلى الفريق المناسب', 'وصف منظَّم مسبقًا يقلّل أسئلة المتابعة'],
                'height' => 720,
                'max' => 580,
                'css' => self::pickCss(),
                'body' => self::wrap(
                    self::head('Open a support ticket', 'Median first response: 2 hours'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('tsubject', 'Subject', self::input('tsubject', 'text', 'Short summary of the problem', 'required')),
                            self::field('tarea', 'Product area', self::select('tarea', ['Components gallery', 'Drawing editor', 'Icons', 'Account and billing', 'API']))
                        ),
                        '<div><span class="lb">How urgent is it?</span><div style="display:grid;gap:10px;margin-top:2px">'
                        . self::radioCard('sev', 'sev1', 'Blocking', 'Nobody on my team can work', 'P1')
                        . self::radioCard('sev', 'sev2', 'Serious', 'A key feature is broken, there is a workaround', 'P2', true)
                        . self::radioCard('sev', 'sev3', 'Minor', 'Something is wrong but we can carry on', 'P3')
                        . '</div></div>',
                        self::field('tdesc', 'What happened?', self::textarea('tdesc', "What you did:\nWhat you expected:\nWhat happened instead:", 6), 'Steps to reproduce it save us both a round trip.'),
                        self::cols(
                            2,
                            self::field('tbrowser', 'Browser and version', self::input('tbrowser', 'text', 'Chrome 141')),
                            self::field('turl', 'Page URL', self::iconInput('turl', 'link', 'url', 'https://'))
                        ),
                        self::field('tfiles', 'Screenshots <span class="mut" style="font-weight:500">(optional)</span>', '<input class="in" type="file" id="tfiles" multiple accept="image/*" style="padding:8px">')
                    ),
                    self::actions('Open ticket', 'Cancel', 'You will get a copy by email')
                ),
            ],
            [
                'slug' => 'appointment-booking-form',
                'name' => 'Appointment booking form',
                'name_ar' => 'نموذج حجز موعد',
                'tagline' => 'Date picker, time slots and a duration choice.',
                'tagline_ar' => 'منتقي التاريخ وفترات زمنية واختيار المدة.',
                'summary' => 'Booking in the order people think: what, then when, then who they are. Time slots are buttons rather than a dropdown, unavailable ones are visibly taken, and the summary line confirms the choice before it is submitted.',
                'summary_ar' => 'الحجز بالترتيب الذي يفكر به الناس: ماذا، ثم متى، ثم من هم. الفترات الزمنية أزرار لا قائمة منسدلة، وغير المتاح منها يظهر محجوزًا بوضوح، وسطر الملخص يؤكد الاختيار قبل الإرسال.',
                'accent' => '#0891b2',
                'tags' => ['booking', 'appointment', 'calendar', 'slots'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Time slots as buttons with taken slots disabled', 'Live summary of the chosen appointment', 'Duration choice that filters the slots'],
                'features_ar' => ['الفترات الزمنية أزرار والمحجوز منها معطّل', 'ملخص حيّ للموعد المختار', 'اختيار المدة يصفّي الفترات المتاحة'],
                'height' => 700,
                'max' => 560,
                'css' => ".slots{display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(88px,1fr))}\n.slot{padding:9px;border:1px solid var(--bd);border-radius:10px;background:var(--card);font:inherit;font-size:13px;font-weight:600;cursor:pointer;font-variant-numeric:tabular-nums;transition:.15s}\n.slot:hover:not([disabled]){border-color:var(--acc);color:var(--acc)}\n.slot[aria-pressed=true]{background:var(--acc);border-color:var(--acc);color:#fff}\n.slot[disabled]{opacity:.4;cursor:not-allowed;text-decoration:line-through}\n.summary{display:flex;align-items:center;gap:10px;padding:13px;border-radius:12px;background:var(--acc-soft);color:var(--acc);font-size:13px;font-weight:600}",
                'js' => "const summary=document.getElementById('sum');\n"
                    . "document.querySelectorAll('.slot').forEach(slot=>{\n"
                    . "  slot.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.slot').forEach(s=>s.setAttribute('aria-pressed','false'));\n"
                    . "    slot.setAttribute('aria-pressed','true');\n"
                    . "    summary.textContent='Thursday 24 September at '+slot.textContent.trim()+' · 30 minutes';\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Book a consultation', '30 or 60 minutes, video or phone'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('bdate', 'Date', self::input('bdate', 'date', '', 'value="2026-09-24"')),
                            self::field('bhow', 'How should we meet?', self::select('bhow', ['Video call', 'Phone call', 'In person']))
                        ),
                        '<div><span class="lb">Length</span><div style="display:flex;gap:9px">'
                        . '<button class="btn" type="button" style="flex:1">30 minutes</button>'
                        . '<button class="btn" type="button" style="flex:1">60 minutes</button></div></div>',
                        '<div><span class="lb">Available times · Thursday 24 September</span><div class="slots">'
                        . '<button class="slot" type="button" aria-pressed="false">09:00</button>'
                        . '<button class="slot" type="button" disabled>09:30</button>'
                        . '<button class="slot" type="button" aria-pressed="false">10:00</button>'
                        . '<button class="slot" type="button" aria-pressed="false">10:30</button>'
                        . '<button class="slot" type="button" disabled>11:00</button>'
                        . '<button class="slot" type="button" aria-pressed="false">13:00</button>'
                        . '<button class="slot" type="button" aria-pressed="false">13:30</button>'
                        . '<button class="slot" type="button" aria-pressed="false">15:00</button>'
                        . '</div></div>',
                        '<div class="summary">' . Kit::icon('calendar', 17) . '<span id="sum">Pick a time to continue</span></div>',
                        self::cols(
                            2,
                            self::field('bname', 'Your name', self::input('bname', 'text', 'Rana Khalil', 'autocomplete="name"')),
                            self::field('bemail', 'Email', self::input('bemail', 'email', 'you@mail.com', 'autocomplete="email"'))
                        )
                    ),
                    self::actions('Confirm booking', 'Back', 'A calendar invite follows by email')
                ),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private static function setTwo(): array
    {
        return [
            [
                'slug' => 'event-registration-form',
                'name' => 'Event registration form',
                'name_ar' => 'نموذج تسجيل في فعالية',
                'tagline' => 'Ticket choice, attendee details and dietary needs.',
                'tagline_ar' => 'اختيار التذكرة وبيانات الحاضر والاحتياجات الغذائية.',
                'summary' => 'Registration built around the ticket: the type is chosen first because it changes the price and the questions, and the dietary field is a plain text box rather than a checklist, since no checklist has ever covered everyone.',
                'summary_ar' => 'تسجيل مبني حول التذكرة: يُختار نوعها أولًا لأنه يغيّر السعر والأسئلة، وحقل الاحتياجات الغذائية مربع نص حر لا قائمة تحقق، إذ لم تشمل أي قائمة الجميع قط.',
                'accent' => '#7c3aed',
                'tags' => ['events', 'registration', 'tickets'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Ticket types as radio cards with prices', 'Accessibility and dietary needs asked openly', 'Running total in the footer'],
                'features_ar' => ['أنواع التذاكر بطاقات اختيار مع الأسعار', 'سؤال مفتوح عن احتياجات الوصول والغذاء', 'إجمالي متجدد في التذييل'],
                'height' => 720,
                'max' => 560,
                'css' => self::pickCss(),
                'body' => self::wrap(
                    self::head('Frugal Conf 2026', '14 November · Riyadh · 400 seats'),
                    self::stack(
                        '<div><span class="lb">Ticket</span><div style="display:grid;gap:10px;margin-top:2px">'
                        . self::radioCard('ticket', 'tstd', 'Standard', 'Talks, workshops and lunch', '$180', true)
                        . self::radioCard('ticket', 'tvip', 'Front row', 'Standard plus reserved seating and the speaker dinner', '$340')
                        . self::radioCard('ticket', 'tstu', 'Student', 'Valid student ID required at the door', '$45')
                        . '</div></div>',
                        self::cols(
                            2,
                            self::field('ename', 'Attendee name', self::input('ename', 'text', 'Rana Khalil', 'autocomplete="name" required')),
                            self::field('eemail', 'Email for the ticket', self::input('eemail', 'email', 'you@mail.com', 'autocomplete="email" required'))
                        ),
                        self::cols(
                            2,
                            self::field('ecompany', 'Organisation', self::input('ecompany', 'text', 'Where you work or study')),
                            self::field('erole', 'Role', self::select('erole', ['Designer', 'Developer', 'Product', 'Founder', 'Student', 'Other']))
                        ),
                        self::field('efood', 'Dietary requirements <span class="mut" style="font-weight:500">(optional)</span>', self::input('efood', 'text', 'Anything we should know')),
                        self::field('eaccess', 'Accessibility needs <span class="mut" style="font-weight:500">(optional)</span>', self::input('eaccess', 'text', 'Step-free access, captioning, seating')),
                        self::checkbox('ephoto', 'I am happy to appear in event photography', true)
                    ),
                    '<div class="ft"><span class="bold num">Total $180.00</span><button class="btn pri" type="submit">Register</button></div>'
                ),
            ],
            [
                'slug' => 'hotel-reservation-form',
                'name' => 'Hotel reservation form',
                'name_ar' => 'نموذج حجز فندقي',
                'tagline' => 'Dates, guests and room type with a live price estimate.',
                'tagline_ar' => 'التواريخ والضيوف ونوع الغرفة مع تقدير حيّ للسعر.',
                'summary' => 'A booking widget where the numbers add up in front of you: nights are derived from the dates, the room rate multiplies out, and taxes are shown rather than sprung at the end. Guest counts are steppers, not free text.',
                'summary_ar' => 'أداة حجز تُجمع فيها الأرقام أمامك: عدد الليالي مستنتج من التواريخ، وسعر الغرفة يُضرب فيها، والضرائب ظاهرة بدل أن تُفاجئك في النهاية. وعدد الضيوف بأزرار زيادة ونقصان لا بنص حر.',
                'accent' => '#b45309',
                'tags' => ['hotel', 'booking', 'travel', 'reservation'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Nights and totals recalculated from the dates', 'Guest steppers instead of a number field', 'Taxes shown before the final figure'],
                'features_ar' => ['إعادة حساب الليالي والإجماليات من التواريخ', 'أزرار زيادة ونقصان للضيوف بدل حقل رقمي', 'الضرائب ظاهرة قبل المبلغ النهائي'],
                'height' => 720,
                'max' => 520,
                'css' => ".step{display:flex;align-items:center;justify-content:space-between;border:1px solid var(--bd);border-radius:10px;padding:6px 8px}\n.step button{width:30px;height:30px;border:1px solid var(--bd);border-radius:8px;background:var(--card);cursor:pointer;color:var(--ink);display:inline-flex;align-items:center;justify-content:center}\n.step button:hover{border-color:var(--acc);color:var(--acc)}\n.step output{font-weight:700;font-variant-numeric:tabular-nums}\n.est{display:grid;gap:8px;padding:14px 16px;border-radius:12px;background:var(--soft)}\n.est div{display:flex;justify-content:space-between;font-size:13px}\n.est .t{border-top:1px dashed var(--bd);padding-top:9px;font-weight:700;font-size:15px}",
                'js' => "const rate=420;\n"
                    . "function nights(){\n"
                    . "  const a=new Date(document.getElementById('cin').value);\n"
                    . "  const b=new Date(document.getElementById('cout').value);\n"
                    . "  const n=Math.round((b-a)/86400000);\n"
                    . "  return Number.isFinite(n)&&n>0?n:1;\n"
                    . "}\n"
                    . "function money(value){return 'SAR '+value.toLocaleString('en-US',{minimumFractionDigits:2});}\n"
                    . "function refresh(){\n"
                    . "  const n=nights();\n"
                    . "  const room=rate*n;\n"
                    . "  const tax=room*0.15;\n"
                    . "  document.getElementById('nights').textContent=n+' night'+(n===1?'':'s')+' at '+money(rate);\n"
                    . "  document.getElementById('sub').textContent=money(room);\n"
                    . "  document.getElementById('tax').textContent=money(tax);\n"
                    . "  document.getElementById('tot').textContent=money(room+tax);\n"
                    . "}\n"
                    . "['cin','cout'].forEach(id=>document.getElementById(id).addEventListener('change',refresh));\n"
                    . "document.querySelectorAll('.step').forEach(stepper=>{\n"
                    . "  const out=stepper.querySelector('output');\n"
                    . "  stepper.querySelectorAll('button').forEach(button=>{\n"
                    . "    button.addEventListener('click',()=>{\n"
                    . "      const next=Number(out.textContent)+Number(button.dataset.by);\n"
                    . "      out.textContent=String(Math.max(Number(out.dataset.min||0),next));\n"
                    . "    });\n"
                    . "  });\n"
                    . "});\nrefresh();",
                'body' => self::wrap(
                    self::head('Reserve a room', 'Marjan Hotel · Riyadh'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('cin', 'Check in', self::input('cin', 'date', '', 'value="2026-09-24"')),
                            self::field('cout', 'Check out', self::input('cout', 'date', '', 'value="2026-09-28"'))
                        ),
                        self::cols(
                            2,
                            self::field('adults', 'Adults', '<div class="step"><button type="button" data-by="-1" aria-label="Fewer adults">' . Kit::icon('minus', 15) . '</button><output data-min="1">2</output><button type="button" data-by="1" aria-label="More adults">' . Kit::icon('plus', 15) . '</button></div>'),
                            self::field('children', 'Children', '<div class="step"><button type="button" data-by="-1" aria-label="Fewer children">' . Kit::icon('minus', 15) . '</button><output data-min="0">0</output><button type="button" data-by="1" aria-label="More children">' . Kit::icon('plus', 15) . '</button></div>')
                        ),
                        self::field('room', 'Room type', self::select('room', ['Deluxe king - SAR 420', 'Twin standard - SAR 360', 'Suite - SAR 780'])),
                        self::field('requests', 'Special requests <span class="mut" style="font-weight:500">(optional)</span>', self::textarea('requests', 'High floor, late check-in, cot...', 3)),
                        '<div class="est">'
                        . '<div><span class="mut" id="nights">4 nights at SAR 420.00</span><span class="num" id="sub">SAR 1,680.00</span></div>'
                        . '<div><span class="mut">VAT and city tax (15%)</span><span class="num" id="tax">SAR 252.00</span></div>'
                        . '<div class="t"><span>Total</span><span class="num" id="tot">SAR 1,932.00</span></div>'
                        . '</div>'
                    ),
                    self::actions('Reserve now', 'Change dates', 'Free cancellation until 48h before')
                ),
            ],
            [
                'slug' => 'flight-search-form',
                'name' => 'Flight search form',
                'name_ar' => 'نموذج البحث عن رحلات',
                'tagline' => 'Return or one way, with a swap button between the airports.',
                'tagline_ar' => 'ذهاب وعودة أو ذهاب فقط، مع زر تبديل بين المطارين.',
                'summary' => 'The airport swap button is the whole trick: it is the control travellers reach for most and the one most forms leave out. Trip type switches the return date on and off rather than hiding it somewhere else.',
                'summary_ar' => 'زر تبديل المطارين هو السر كله: إنه العنصر الذي يمدّ المسافرون أيديهم إليه أكثر من غيره، والذي تُغفله معظم النماذج. ونوع الرحلة يفعّل تاريخ العودة أو يعطّله بدل إخفائه في مكان آخر.',
                'accent' => '#0ea5e9',
                'tags' => ['travel', 'flights', 'search', 'booking'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Swap button between origin and destination', 'Return field enabled by the trip type', 'Cabin and passenger selects inline'],
                'features_ar' => ['زر تبديل بين المغادرة والوجهة', 'حقل العودة يُفعَّل بحسب نوع الرحلة', 'اختيار الدرجة وعدد المسافرين في السطر نفسه'],
                'height' => 560,
                'max' => 720,
                'css' => ".seg{display:inline-flex;padding:3px;background:var(--soft);border:1px solid var(--bd);border-radius:999px;margin-bottom:2px}\n.seg button{border:0;background:none;padding:7px 15px;border-radius:999px;font:inherit;font-size:12.5px;font-weight:650;color:var(--mut);cursor:pointer}\n.seg button[aria-pressed=true]{background:var(--acc);color:#fff}\n.pair{display:grid;grid-template-columns:1fr auto 1fr;gap:9px;align-items:end}\n.swap{width:40px;height:40px;border:1px solid var(--bd);border-radius:50%;background:var(--card);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;color:var(--mut);margin-bottom:1px}\n.swap:hover{border-color:var(--acc);color:var(--acc)}\n@media (max-width:520px){.pair{grid-template-columns:1fr}.swap{margin:0 auto}}",
                'js' => "document.getElementById('swap').addEventListener('click',()=>{\n"
                    . "  const from=document.getElementById('from');\n"
                    . "  const to=document.getElementById('to');\n"
                    . "  [from.value,to.value]=[to.value,from.value];\n"
                    . "});\n"
                    . "document.querySelectorAll('.seg button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.seg button').forEach(b=>b.setAttribute('aria-pressed','false'));\n"
                    . "    button.setAttribute('aria-pressed','true');\n"
                    . "    document.getElementById('back').disabled=button.dataset.trip==='one';\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Find a flight', 'Direct and one-stop fares from 180 airlines'),
                    self::stack(
                        '<span class="seg"><button type="button" data-trip="return" aria-pressed="true">Return</button>'
                        . '<button type="button" data-trip="one" aria-pressed="false">One way</button>'
                        . '<button type="button" data-trip="multi" aria-pressed="false">Multi-city</button></span>',
                        '<div class="pair">'
                        . self::field('from', 'From', self::iconInput('from', 'send', 'text', 'City or airport', 'value="Riyadh (RUH)"'))
                        . '<button class="swap" type="button" id="swap" aria-label="Swap airports">' . Kit::icon('refresh', 17) . '</button>'
                        . self::field('to', 'To', self::iconInput('to', 'map-pin', 'text', 'City or airport', 'value="Istanbul (IST)"'))
                        . '</div>',
                        self::cols(
                            3,
                            self::field('out', 'Departing', self::input('out', 'date', '', 'value="2026-10-02"')),
                            self::field('back', 'Returning', self::input('back', 'date', '', 'value="2026-10-09"')),
                            self::field('cabin', 'Cabin', self::select('cabin', ['Economy', 'Premium economy', 'Business', 'First']))
                        ),
                        self::cols(
                            2,
                            self::field('pax', 'Passengers', self::select('pax', ['1 adult', '2 adults', '2 adults, 1 child', '2 adults, 2 children'])),
                            self::field('flex', 'Flexibility', self::select('flex', ['Exact dates', '± 1 day', '± 3 days', 'Whole month']))
                        ),
                        self::checkbox('direct', 'Direct flights only'),
                        self::submit('Search flights', 'search')
                    )
                ),
            ],
            [
                'slug' => 'restaurant-reservation-form',
                'name' => 'Restaurant reservation form',
                'name_ar' => 'نموذج حجز طاولة',
                'tagline' => 'Party size chips, date and a time slot grid.',
                'tagline_ar' => 'شارات لعدد الأشخاص وتاريخ وشبكة للأوقات.',
                'summary' => 'Two taps to a table: party size as chips because almost every booking is two to six, then a slot grid for the evening. Everything else - name, phone, occasion - comes after the hard part is settled.',
                'summary_ar' => 'طاولتك بنقرتين: عدد الأشخاص شارات لأن معظم الحجوزات بين 2 و6، ثم شبكة لأوقات المساء. وكل ما عدا ذلك، من اسم وهاتف ومناسبة، يأتي بعد حسم الجزء الصعب.',
                'accent' => '#c2410c',
                'tags' => ['restaurant', 'reservation', 'booking', 'slots'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Party size as one-tap chips', 'Evening slot grid with full slots disabled', 'Occasion field for the kitchen'],
                'features_ar' => ['عدد الأشخاص شارات بنقرة واحدة', 'شبكة أوقات مسائية والممتلئ منها معطّل', 'حقل المناسبة لإعلام المطبخ'],
                'height' => 680,
                'max' => 520,
                'css' => ".sizes{display:flex;gap:7px;flex-wrap:wrap}\n.sizes button{width:44px;height:44px;border:1px solid var(--bd);border-radius:12px;background:var(--card);font:inherit;font-weight:700;cursor:pointer;transition:.15s}\n.sizes button:hover{border-color:var(--acc);color:var(--acc)}\n.sizes button[aria-pressed=true]{background:var(--acc);border-color:var(--acc);color:#fff}\n.slots{display:grid;gap:8px;grid-template-columns:repeat(auto-fill,minmax(80px,1fr))}\n.slot{padding:9px;border:1px solid var(--bd);border-radius:10px;background:var(--card);font:inherit;font-size:13px;font-weight:600;cursor:pointer;font-variant-numeric:tabular-nums}\n.slot:hover:not([disabled]){border-color:var(--acc);color:var(--acc)}\n.slot[aria-pressed=true]{background:var(--acc);border-color:var(--acc);color:#fff}\n.slot[disabled]{opacity:.4;cursor:not-allowed}",
                'js' => "function group(selector){\n"
                    . "  document.querySelectorAll(selector).forEach(button=>{\n"
                    . "    button.addEventListener('click',()=>{\n"
                    . "      document.querySelectorAll(selector).forEach(b=>b.setAttribute('aria-pressed','false'));\n"
                    . "      button.setAttribute('aria-pressed','true');\n"
                    . "    });\n"
                    . "  });\n"
                    . "}\n"
                    . "group('.sizes button');\ngroup('.slot:not([disabled])');",
                'body' => self::wrap(
                    self::head('Book a table', 'Marjan Kitchen · dinner service from 18:00'),
                    self::stack(
                        '<div><span class="lb">How many of you?</span><div class="sizes">'
                        . implode('', array_map(
                            fn($n) => '<button type="button" aria-pressed="' . ($n === 2 ? 'true' : 'false') . '" aria-label="' . $n . ' guests">' . $n . '</button>',
                            [1, 2, 3, 4, 5, 6]
                        ))
                        . '<button type="button" aria-pressed="false" style="width:auto;padding:0 14px">7+</button></div></div>',
                        self::field('rdate', 'Date', self::input('rdate', 'date', '', 'value="2026-09-24"')),
                        '<div><span class="lb">Time</span><div class="slots">'
                        . '<button class="slot" type="button" aria-pressed="false">18:00</button>'
                        . '<button class="slot" type="button" aria-pressed="false">18:30</button>'
                        . '<button class="slot" type="button" disabled>19:00</button>'
                        . '<button class="slot" type="button" disabled>19:30</button>'
                        . '<button class="slot" type="button" aria-pressed="true">20:00</button>'
                        . '<button class="slot" type="button" aria-pressed="false">20:30</button>'
                        . '<button class="slot" type="button" aria-pressed="false">21:00</button>'
                        . '<button class="slot" type="button" aria-pressed="false">21:30</button>'
                        . '</div></div>',
                        self::cols(
                            2,
                            self::field('rname', 'Name', self::input('rname', 'text', 'Rana Khalil', 'autocomplete="name"')),
                            self::field('rphone', 'Phone', self::input('rphone', 'tel', '+966 5x xxx xxxx', 'autocomplete="tel"'))
                        ),
                        self::field('roccasion', 'Occasion <span class="mut" style="font-weight:500">(optional)</span>', self::select('roccasion', ['No occasion', 'Birthday', 'Anniversary', 'Business dinner', 'Celebration']))
                    ),
                    self::actions('Confirm table', 'Cancel', 'Held for 15 minutes after the booking time')
                ),
            ],
            [
                'slug' => 'product-review-form',
                'name' => 'Product review form',
                'name_ar' => 'نموذج تقييم منتج',
                'tagline' => 'Star rating, headline, pros and cons and photo upload.',
                'tagline_ar' => 'تقييم بالنجوم وعنوان ومزايا وعيوب ورفع صور.',
                'summary' => 'Splitting pros from cons gets far more useful reviews than one text box, and it makes them scannable later. A verified-purchase badge sits in the header so the reviewer knows why they are being asked.',
                'summary_ar' => 'فصل المزايا عن العيوب يُنتج مراجعات أنفع بكثير من مربع نص واحد، ويجعلها سهلة التصفّح لاحقًا. وشارة «شراء مؤكَّد» في الترويسة تُعلم المراجِع سبب طلب رأيه.',
                'accent' => '#f59e0b',
                'tags' => ['review', 'rating', 'ecommerce', 'ugc'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Pros and cons captured separately', 'Star rating with a live word label', 'Photo upload with a count'],
                'features_ar' => ['المزايا والعيوب تُدخَل كلٌّ على حدة', 'تقييم بالنجوم مع وصف لفظي حيّ', 'رفع الصور مع عدّاد'],
                'height' => 720,
                'max' => 560,
                'css' => ".rate{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:5px}\n.rate input{position:absolute;opacity:0}\n.rate label{cursor:pointer;color:var(--bd)}\n.rate label:hover,.rate label:hover ~ label,.rate input:checked ~ label{color:#f59e0b}\n.prod{display:flex;gap:14px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--bd);background:var(--soft)}\n.prod .img{width:56px;height:56px;border-radius:12px;background:linear-gradient(140deg,#fbbf24,#f97316);flex:none}",
                'js' => "const words={1:'Poor',2:'Fair',3:'Good',4:'Very good',5:'Excellent'};\n"
                    . "document.querySelectorAll('.rate input').forEach(input=>{\n"
                    . "  input.addEventListener('change',()=>document.getElementById('word').textContent=words[input.value]);\n"
                    . "});\n"
                    . "document.getElementById('photos').addEventListener('change',event=>{\n"
                    . "  const n=event.target.files.length;\n"
                    . "  document.getElementById('pcount').textContent=n?n+' photo'+(n===1?'':'s')+' selected':'Up to 5 photos';\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Write a review', 'Your review appears with your first name and initial', Kit::pill('Verified purchase', 'ok')),
                    '<div class="prod"><span class="img"></span><span>' . '<span class="bold" style="display:block">Aurora Desk Lamp</span>'
                    . '<span class="xs mut">Delivered 12 September 2026</span></span></div>',
                    self::stack(
                        '<div><span class="lb">Your rating</span><div class="row" style="gap:12px"><div class="rate">'
                        . implode('', array_map(function ($n) {
                            return '<input type="radio" name="stars" id="r' . $n . '" value="' . $n . '">'
                                . '<label for="r' . $n . '" aria-label="' . $n . ' stars">'
                                . '<svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/></svg></label>';
                        }, [5, 4, 3, 2, 1]))
                        . '</div><span class="sm bold" id="word"></span></div></div>',
                        self::field('rheadline', 'Headline', self::input('rheadline', 'text', 'Sum it up in a few words')),
                        self::cols(
                            2,
                            self::field('rpros', 'What worked well', self::textarea('rpros', 'Build quality, brightness...', 3)),
                            self::field('rcons', 'What did not', self::textarea('rcons', 'Anything that disappointed', 3))
                        ),
                        '<div><label class="lb" for="photos">Add photos</label>'
                        . '<input class="in" type="file" id="photos" accept="image/*" multiple style="padding:8px">'
                        . '<p class="hint" id="pcount">Up to 5 photos</p></div>',
                        self::checkbox('rrecommend', 'I would recommend this product', true)
                    ),
                    self::actions('Publish review', 'Cancel', 'Reviews are checked before they appear')
                ),
            ],
            [
                'slug' => 'add-product-form',
                'name' => 'Add product form',
                'name_ar' => 'نموذج إضافة منتج',
                'tagline' => 'Catalogue entry with pricing, stock and variant rows.',
                'tagline_ar' => 'إدخال منتج في الكتالوج مع السعر والمخزون وصفوف المتغيرات.',
                'summary' => 'The admin form behind a storefront: price and compare-at side by side so a discount is obvious, stock tracked with a toggle rather than a magic zero, and variants added as rows so a product with three sizes does not need three products.',
                'summary_ar' => 'نموذج الإدارة خلف المتجر: السعر وسعر المقارنة متجاوران فيتضح الخصم، وتتبّع المخزون بمفتاح تبديل لا بصفر سحري، والمتغيرات تُضاف صفوفًا فلا يحتاج منتج بثلاثة مقاسات إلى ثلاثة منتجات.',
                'accent' => '#059669',
                'tags' => ['ecommerce', 'admin', 'product', 'variants'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Variant rows that can be added and removed', 'Compare-at price beside the selling price', 'Stock tracking behind a toggle'],
                'features_ar' => ['صفوف متغيرات قابلة للإضافة والإزالة', 'سعر المقارنة بجوار سعر البيع', 'تتبّع المخزون خلف مفتاح تبديل'],
                'height' => 780,
                'max' => 640,
                'css' => self::toggleCss() . "\n.var{display:grid;grid-template-columns:1.4fr 1fr 1fr auto;gap:9px;align-items:center}\n.var .in{padding:8px 10px;font-size:13px}\n.rm{border:1px solid var(--bd);border-radius:9px;background:var(--card);padding:8px;cursor:pointer;color:var(--mut);display:inline-flex}\n.rm:hover{border-color:var(--bad);color:var(--bad)}\n@media (max-width:560px){.var{grid-template-columns:1fr 1fr}}",
                'js' => "const list=document.getElementById('variants');\n"
                    . "document.getElementById('addvar').addEventListener('click',()=>{\n"
                    . "  const row=list.firstElementChild.cloneNode(true);\n"
                    . "  row.querySelectorAll('input').forEach(input=>input.value='');\n"
                    . "  list.appendChild(row);\n"
                    . "});\n"
                    . "list.addEventListener('click',event=>{\n"
                    . "  const button=event.target.closest('.rm');\n"
                    . "  if(button&&list.children.length>1)button.closest('.var').remove();\n"
                    . '});',
                'body' => self::wrap(
                    self::head('New product', 'Draft · not visible in the store yet', Kit::pill('Draft', 'warn')),
                    self::stack(
                        self::field('pname', 'Product name', self::input('pname', 'text', 'Aurora Desk Lamp', 'required')),
                        self::field('pdesc', 'Description', self::textarea('pdesc', 'What it is, what it is made of, who it is for.', 4)),
                        self::cols(
                            3,
                            self::field('pprice', 'Price', self::input('pprice', 'text', '39.00', 'inputmode="decimal"')),
                            self::field('pcompare', 'Compare at', self::input('pcompare', 'text', '49.00', 'inputmode="decimal"'), 'Shown struck through'),
                            self::field('pcost', 'Cost per item', self::input('pcost', 'text', '18.40', 'inputmode="decimal"'))
                        ),
                        self::cols(
                            2,
                            self::field('psku', 'SKU', self::input('psku', 'text', 'LMP-0041')),
                            self::field('pbarcode', 'Barcode', self::input('pbarcode', 'text', 'GTIN, UPC or ISBN'))
                        ),
                        self::toggle('ptrack', 'Track stock', 'Stop selling when the quantity reaches zero', true),
                        '<div><span class="lb">Variants</span><div id="variants" style="display:grid;gap:9px">'
                        . '<div class="var"><input class="in" placeholder="Option, e.g. Warm white" aria-label="Variant name">'
                        . '<input class="in" placeholder="Price" aria-label="Variant price" inputmode="decimal">'
                        . '<input class="in" placeholder="Stock" aria-label="Variant stock" inputmode="numeric">'
                        . '<button class="rm" type="button" aria-label="Remove variant">' . Kit::icon('trash', 15) . '</button></div>'
                        . '</div><button class="btn tiny" type="button" id="addvar" style="margin-top:9px">' . Kit::icon('plus', 14) . 'Add variant</button></div>',
                        self::cols(
                            2,
                            self::field('pcat', 'Category', self::select('pcat', ['Lighting', 'Seating', 'Tables', 'Storage'])),
                            self::field('ptags', 'Tags', self::input('ptags', 'text', 'desk, warm, dimmable'))
                        )
                    ),
                    self::actions('Publish product', 'Save as draft')
                ),
            ],
            [
                'slug' => 'invoice-builder-form',
                'name' => 'Invoice builder form',
                'name_ar' => 'نموذج إنشاء فاتورة',
                'tagline' => 'Line items that add, remove and total as you type.',
                'tagline_ar' => 'بنود تُضاف وتُحذف ويُحسب مجموعها أثناء الكتابة.',
                'summary' => 'An invoice form where the arithmetic is done for you: each row multiplies quantity by rate, the subtotal follows, tax is applied and the due figure updates on every keystroke. Nobody should be adding up an invoice by hand.',
                'summary_ar' => 'نموذج فاتورة يتولى الحساب عنك: كل صف يضرب الكمية في السعر، ويتبعه المجموع الفرعي، وتُطبَّق الضريبة، ويتحدّث المبلغ المستحق مع كل ضغطة مفتاح. لا ينبغي لأحد أن يجمع فاتورة يدويًا.',
                'accent' => '#4f46e5',
                'tags' => ['invoice', 'billing', 'line-items', 'calculation'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Live per-line and grand totals', 'Rows added and removed without a reload', 'Tax rate applied to the running subtotal'],
                'features_ar' => ['مجاميع حيّة لكل بند وللإجمالي', 'إضافة الصفوف وحذفها دون إعادة تحميل', 'نسبة الضريبة تُطبَّق على المجموع الفرعي المتجدد'],
                'height' => 780,
                'max' => 720,
                'css' => ".ln{display:grid;grid-template-columns:2.2fr .7fr .9fr .9fr auto;gap:9px;align-items:center}\n.ln .in{padding:8px 10px;font-size:13px}\n.ln .amt{text-align:right;font-weight:650;font-variant-numeric:tabular-nums}\n.lnhead{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700}\n.rm{border:1px solid var(--bd);border-radius:9px;background:var(--card);padding:8px;cursor:pointer;color:var(--mut);display:inline-flex}\n.rm:hover{border-color:var(--bad);color:var(--bad)}\n.sums{display:grid;gap:8px;margin-left:auto;width:min(300px,100%)}\n.sums div{display:flex;justify-content:space-between;font-size:13px}\n.sums .t{border-top:1px dashed var(--bd);padding-top:9px;font-size:16px;font-weight:700;color:var(--acc)}\n@media (max-width:620px){.ln{grid-template-columns:1fr 1fr}.lnhead{display:none}}",
                'js' => "const rows=document.getElementById('lines');\n"
                    . "function money(value){return '\$'+value.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});}\n"
                    . "function recalc(){\n"
                    . "  let subtotal=0;\n"
                    . "  rows.querySelectorAll('.ln').forEach(row=>{\n"
                    . "    const qty=parseFloat(row.querySelector('.q').value)||0;\n"
                    . "    const rate=parseFloat(row.querySelector('.r').value)||0;\n"
                    . "    const amount=qty*rate;\n"
                    . "    row.querySelector('.amt').textContent=money(amount);\n"
                    . "    subtotal+=amount;\n"
                    . "  });\n"
                    . "  const tax=subtotal*(parseFloat(document.getElementById('vat').value)||0)/100;\n"
                    . "  document.getElementById('sub').textContent=money(subtotal);\n"
                    . "  document.getElementById('tax').textContent=money(tax);\n"
                    . "  document.getElementById('due').textContent=money(subtotal+tax);\n"
                    . "}\n"
                    . "rows.addEventListener('input',recalc);\n"
                    . "document.getElementById('vat').addEventListener('input',recalc);\n"
                    . "document.getElementById('addline').addEventListener('click',()=>{\n"
                    . "  const row=rows.firstElementChild.cloneNode(true);\n"
                    . "  row.querySelectorAll('input').forEach(input=>input.value='');\n"
                    . "  row.querySelector('.amt').textContent=money(0);\n"
                    . "  rows.appendChild(row);\n"
                    . "});\n"
                    . "rows.addEventListener('click',event=>{\n"
                    . "  const button=event.target.closest('.rm');\n"
                    . "  if(button&&rows.children.length>1){button.closest('.ln').remove();recalc();}\n"
                    . "});\nrecalc();",
                'body' => self::wrap(
                    self::head('New invoice', 'INV-2282 · draft'),
                    self::stack(
                        self::cols(
                            3,
                            self::field('client', 'Bill to', self::select('client', ['Northwind Ltd', 'Bluebird Media', 'Harbor Studio', 'Juno Labs'])),
                            self::field('issued', 'Issue date', self::input('issued', 'date', '', 'value="2026-09-20"')),
                            self::field('due', 'Due date', self::input('duedate', 'date', '', 'value="2026-10-20"'))
                        ),
                        '<div><div class="ln lnhead"><span>Description</span><span>Qty</span><span>Rate</span><span style="text-align:right">Amount</span><span></span></div>'
                        . '<div id="lines" style="display:grid;gap:9px;margin-top:8px">'
                        . '<div class="ln"><input class="in" placeholder="What are you billing for?" aria-label="Description" value="Design retainer">'
                        . '<input class="in q" inputmode="decimal" aria-label="Quantity" value="1">'
                        . '<input class="in r" inputmode="decimal" aria-label="Rate" value="6000">'
                        . '<span class="amt">$0.00</span>'
                        . '<button class="rm" type="button" aria-label="Remove line">' . Kit::icon('trash', 15) . '</button></div>'
                        . '<div class="ln"><input class="in" placeholder="What are you billing for?" aria-label="Description" value="Front-end development">'
                        . '<input class="in q" inputmode="decimal" aria-label="Quantity" value="48">'
                        . '<input class="in r" inputmode="decimal" aria-label="Rate" value="95">'
                        . '<span class="amt">$0.00</span>'
                        . '<button class="rm" type="button" aria-label="Remove line">' . Kit::icon('trash', 15) . '</button></div>'
                        . '</div><button class="btn tiny" type="button" id="addline" style="margin-top:10px">' . Kit::icon('plus', 14) . 'Add line</button></div>',
                        self::cols(
                            2,
                            self::field('vat', 'Tax rate (%)', self::input('vat', 'text', '15', 'inputmode="decimal" value="15"')),
                            self::field('terms', 'Payment terms', self::select('terms', ['Net 30', 'Net 14', 'Due on receipt', 'Net 60']))
                        ),
                        '<div class="sums">'
                        . '<div><span class="mut">Subtotal</span><span class="num" id="sub">$0.00</span></div>'
                        . '<div><span class="mut">Tax</span><span class="num" id="tax">$0.00</span></div>'
                        . '<div class="t"><span>Amount due</span><span class="num" id="due">$0.00</span></div>'
                        . '</div>'
                    ),
                    self::actions('Send invoice', 'Save draft')
                ),
            ],
            [
                'slug' => 'expense-claim-form',
                'name' => 'Expense claim form',
                'name_ar' => 'نموذج مطالبة مصاريف',
                'tagline' => 'Claim entry with currency, category and receipt upload.',
                'tagline_ar' => 'إدخال مطالبة مع العملة والفئة ورفع الإيصال.',
                'summary' => 'Expense entry that survives contact with a policy: the currency sits with the amount, the category drives the approval route, and a policy note appears under the amount rather than arriving as a rejection three days later.',
                'summary_ar' => 'إدخال مصاريف يصمد أمام السياسات: العملة ملاصقة للمبلغ، والفئة تحدد مسار الموافقة، وملاحظة السياسة تظهر أسفل المبلغ بدل أن تصل رفضًا بعد ثلاثة أيام.',
                'accent' => '#0f766e',
                'tags' => ['expenses', 'finance', 'claim', 'upload'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Currency select joined to the amount field', 'Policy limit warned about while typing', 'Receipt upload with a file name readout'],
                'features_ar' => ['قائمة العملة ملتصقة بحقل المبلغ', 'تحذير من حد السياسة أثناء الكتابة', 'رفع الإيصال مع إظهار اسم الملف'],
                'height' => 700,
                'max' => 540,
                'css' => ".money{display:flex}\n.money select{width:auto;border-radius:10px 0 0 10px;border-right:0}\n.money input{border-radius:0 10px 10px 0}\n.policy{display:none;align-items:center;gap:7px;font-size:11.5px;color:var(--warn);margin-top:6px}\n.policy.show{display:flex}",
                'js' => "const amount=document.getElementById('amount');\n"
                    . "amount.addEventListener('input',()=>{\n"
                    . "  const value=parseFloat(amount.value)||0;\n"
                    . "  document.getElementById('policy').classList.toggle('show',value>500);\n"
                    . "});\n"
                    . "document.getElementById('receipt').addEventListener('change',event=>{\n"
                    . "  const file=event.target.files[0];\n"
                    . "  document.getElementById('rname').textContent=file?file.name:'JPG, PNG or PDF';\n"
                    . '});',
                'body' => self::wrap(
                    self::head('New expense claim', 'Paid with the September payroll run'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('edate', 'Date of expense', self::input('edate', 'date', '', 'value="2026-09-18"')),
                            self::field('ecat', 'Category', self::select('ecat', ['Travel', 'Meals and entertainment', 'Software', 'Hardware', 'Training', 'Other']))
                        ),
                        '<div><label class="lb" for="amount">Amount</label><div class="money">'
                        . self::select('currency', ['USD', 'SAR', 'EUR', 'GBP', 'JOD'])
                        . self::input('amount', 'text', '0.00', 'inputmode="decimal"')
                        . '</div><div class="policy" id="policy">' . Kit::icon('alert', 14) . 'Claims over $500 need a second approver</div></div>',
                        self::field('emerchant', 'Merchant', self::input('emerchant', 'text', 'Who was paid')),
                        self::field('epurpose', 'Business purpose', self::textarea('epurpose', 'Why this was necessary - one line is enough.', 3)),
                        '<div><label class="lb" for="receipt">Receipt</label>'
                        . '<input class="in" type="file" id="receipt" accept="image/*,.pdf" style="padding:8px">'
                        . '<p class="hint" id="rname">JPG, PNG or PDF</p></div>',
                        self::checkbox('ebillable', 'This is billable to a client')
                    ),
                    self::actions('Submit claim', 'Save draft', 'Approved claims are paid within 5 days')
                ),
            ],
            [
                'slug' => 'team-invite-form',
                'name' => 'Team invite form',
                'name_ar' => 'نموذج دعوة الفريق',
                'tagline' => 'Email chips with per-invite roles and a shareable link.',
                'tagline_ar' => 'شارات بريد إلكتروني مع أدوار للدعوات ورابط قابل للمشاركة.',
                'summary' => 'Inviting five people should be one action: type an address, press Enter, it becomes a chip. The shareable link sits underneath for the times when collecting addresses is the harder half of the job.',
                'summary_ar' => 'دعوة خمسة أشخاص ينبغي أن تكون إجراءً واحدًا: اكتب العنوان واضغط Enter فيتحول إلى شارة. والرابط القابل للمشاركة في الأسفل للحالات التي يكون فيها جمع العناوين هو الجزء الأصعب.',
                'accent' => '#2563eb',
                'tags' => ['team', 'invite', 'chips', 'collaboration'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Email chips created on Enter or comma', 'Role chosen for the whole batch', 'Invite link with copy to clipboard'],
                'features_ar' => ['شارات بريد تُنشأ عند Enter أو الفاصلة', 'دور واحد يُختار للدفعة كاملة', 'رابط دعوة مع نسخ إلى الحافظة'],
                'height' => 640,
                'max' => 560,
                'css' => ".chipbox{display:flex;flex-wrap:wrap;gap:7px;padding:8px;border:1px solid var(--bd);border-radius:10px;min-height:46px;cursor:text}\n.chipbox:focus-within{border-color:var(--acc);box-shadow:0 0 0 3px var(--acc-soft)}\n.chipbox input{flex:1;min-width:140px;border:0;outline:none;background:none;font:inherit;font-size:13.5px;color:var(--ink);padding:5px}\n.chip{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:var(--acc-soft);color:var(--acc);font-size:12.5px;font-weight:600}\n.chip button{border:0;background:none;padding:0;cursor:pointer;color:inherit;display:inline-flex}\n.linkrow{display:flex;gap:8px}\n.linkrow input{flex:1;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px}",
                'js' => "const box=document.getElementById('chipbox');\n"
                    . "const entry=document.getElementById('entry');\n"
                    . "box.addEventListener('click',()=>entry.focus());\n"
                    . "entry.addEventListener('keydown',event=>{\n"
                    . "  if(event.key!=='Enter'&&event.key!==','&&event.key!=='Tab')return;\n"
                    . "  const value=entry.value.trim().replace(/,$/,'');\n"
                    . "  if(!value)return;\n"
                    . "  event.preventDefault();\n"
                    . "  add(value);\n"
                    . "  entry.value='';\n"
                    . "});\n"
                    . "function add(email){\n"
                    . "  const chip=document.createElement('span');\n"
                    . "  chip.className='chip';\n"
                    . "  chip.textContent=email;\n"
                    . "  const remove=document.createElement('button');\n"
                    . "  remove.type='button';\n"
                    . "  remove.setAttribute('aria-label','Remove '+email);\n"
                    . "  remove.innerHTML='&times;';\n"
                    . "  remove.addEventListener('click',()=>chip.remove());\n"
                    . "  chip.appendChild(remove);\n"
                    . "  box.insertBefore(chip,entry);\n"
                    . "}\n"
                    . "['lina@acme.com','omar@acme.com'].forEach(add);\n"
                    . "document.getElementById('copylink').addEventListener('click',async()=>{\n"
                    . "  const field=document.getElementById('link');\n"
                    . "  try{await navigator.clipboard.writeText(field.value);}catch{}\n"
                    . "  const button=document.getElementById('copylink');\n"
                    . "  button.textContent='Copied';\n"
                    . "  setTimeout(()=>button.textContent='Copy',1400);\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Invite your team', '7 of 10 seats used on the Studio plan'),
                    self::stack(
                        '<div><label class="lb" for="entry">Email addresses</label>'
                        . '<div class="chipbox" id="chipbox"><input id="entry" type="email" placeholder="name@company.com, then Enter" aria-label="Add an email address"></div>'
                        . '<p class="hint">Separate addresses with Enter or a comma.</p></div>',
                        self::field('invrole', 'Role for these invites', self::select('invrole', ['Viewer - can read everything', 'Editor - can create and edit', 'Admin - can manage members and billing'])),
                        self::field('invmsg', 'Add a note <span class="mut" style="font-weight:500">(optional)</span>', self::textarea('invmsg', 'Joining us on the redesign - here is the workspace.', 3)),
                        '<div style="height:1px;background:var(--bd)"></div>',
                        '<div><label class="lb" for="link">Or share an invite link</label><div class="linkrow">'
                        . '<input class="in" id="link" readonly value="https://frugal.app/join/acme-7f2ac41b">'
                        . '<button class="btn" type="button" id="copylink">Copy</button></div>'
                        . '<p class="hint">Anyone with this link can join as a Viewer. It expires in 7 days.</p></div>'
                    ),
                    self::actions('Send invites', 'Cancel', 'Invites expire after 14 days')
                ),
            ],
            [
                'slug' => 'organisation-settings-form',
                'name' => 'Organisation settings',
                'name_ar' => 'نموذج إعدادات المؤسسة',
                'tagline' => 'Company profile, logo, locale and a danger zone.',
                'tagline_ar' => 'ملف الشركة والشعار والإعدادات الإقليمية ومنطقة الخطر.',
                'summary' => 'Workspace settings arranged by risk: identity at the top, regional settings in the middle, and destructive actions fenced off at the bottom in a red-bordered block that cannot be clicked through by accident.',
                'summary_ar' => 'إعدادات مساحة العمل مرتبة بحسب الخطورة: الهوية في الأعلى، والإعدادات الإقليمية في المنتصف، والإجراءات الهدّامة معزولة في الأسفل داخل كتلة بإطار أحمر لا يمكن النقر عليها عن طريق الخطأ.',
                'accent' => '#475569',
                'tags' => ['settings', 'organisation', 'admin', 'danger-zone'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Logo block with drawn placeholder mark', 'Locale, timezone and currency together', 'Destructive actions fenced in a danger zone'],
                'features_ar' => ['كتلة الشعار مع علامة نائبة مرسومة', 'اللغة والمنطقة الزمنية والعملة معًا', 'الإجراءات الهدّامة معزولة في منطقة خطر'],
                'height' => 760,
                'max' => 620,
                'css' => ".logo{width:64px;height:64px;border-radius:16px;background:linear-gradient(140deg,var(--acc),var(--acc-dk));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:24px;flex:none}\n.danger{border:1px solid var(--bad);border-radius:14px;padding:16px;display:grid;gap:12px}\n.danger h3{color:var(--bad)}\n.danger .row{justify-content:space-between;gap:14px;flex-wrap:wrap}\n.danger .btn{border-color:var(--bad);color:var(--bad)}\n.danger .btn:hover{background:var(--bad);color:#fff}",
                'body' => self::wrap(
                    self::head('Organisation', 'Applies to everyone in this workspace'),
                    self::stack(
                        '<div class="row" style="gap:16px;flex-wrap:wrap"><span class="logo">A</span>'
                        . '<div><div class="row" style="gap:8px"><button class="btn" type="button">' . Kit::icon('upload', 15) . 'Upload logo</button>'
                        . '<button class="btn gh" type="button">Remove</button></div>'
                        . '<p class="hint">Square SVG or PNG, at least 512×512.</p></div></div>',
                        self::cols(
                            2,
                            self::field('orgname', 'Organisation name', self::input('orgname', 'text', 'Acme Design')),
                            self::field('orgslug', 'Workspace URL', self::input('orgslug', 'text', 'acme'), 'frugal.app/<b>acme</b>')
                        ),
                        self::field('orgsite', 'Website', self::iconInput('orgsite', 'link', 'url', 'https://acme.design')),
                        self::cols(
                            3,
                            self::field('orglang', 'Default language', self::select('orglang', ['English', 'العربية', 'Français'])),
                            self::field('orgtz', 'Timezone', self::select('orgtz', ['Asia/Riyadh', 'Asia/Amman', 'Africa/Cairo', 'Europe/London'])),
                            self::field('orgcur', 'Currency', self::select('orgcur', ['SAR', 'USD', 'EUR', 'JOD']))
                        ),
                        self::field('orgvat', 'Tax registration number <span class="mut" style="font-weight:500">(optional)</span>', self::input('orgvat', 'text', 'Appears on invoices')),
                        '<div class="danger"><h3>Danger zone</h3>'
                        . '<div class="row"><span><b style="display:block;font-size:13.5px">Transfer ownership</b><span class="xs mut">Hand this workspace to another admin</span></span>'
                        . '<button class="btn" type="button">Transfer</button></div>'
                        . '<div class="row"><span><b style="display:block;font-size:13.5px">Delete organisation</b><span class="xs mut">Removes every project, file and member. This cannot be undone.</span></span>'
                        . '<button class="btn" type="button">Delete</button></div></div>'
                    ),
                    self::actions('Save settings', 'Discard changes')
                ),
            ],
            [
                'slug' => 'webhook-endpoint-form',
                'name' => 'Webhook endpoint form',
                'name_ar' => 'نموذج نقطة الويب هوك',
                'tagline' => 'Endpoint URL, event subscriptions and a signing secret.',
                'tagline_ar' => 'رابط نقطة النهاية واشتراكات الأحداث ومفتاح التوقيع السري.',
                'summary' => 'Everything a developer needs to wire up a webhook on one screen: the URL, the events as checkboxes grouped by object, the signing secret with a reveal, and a test-send button that proves the endpoint answers before it is saved.',
                'summary_ar' => 'كل ما يحتاجه المطوّر لتوصيل ويب هوك في شاشة واحدة: الرابط، والأحداث مربعات اختيار مجمّعة حسب الكائن، ومفتاح التوقيع السري مع خيار إظهاره، وزر إرسال تجريبي يثبت أن نقطة النهاية تستجيب قبل الحفظ.',
                'accent' => '#0f766e',
                'tags' => ['developer', 'webhooks', 'api', 'integration'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Events grouped by object with select-all per group', 'Signing secret masked with a reveal', 'Test-send button beside the URL'],
                'features_ar' => ['أحداث مجمّعة حسب الكائن مع «تحديد الكل» لكل مجموعة', 'مفتاح التوقيع مخفي مع خيار إظهاره', 'زر إرسال تجريبي بجوار الرابط'],
                'height' => 740,
                'max' => 620,
                'css' => ".events{display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}\n.grp h4{margin-bottom:8px;color:var(--mut);text-transform:uppercase;letter-spacing:.05em;font-size:10.5px}\n.grp label{display:flex;align-items:center;gap:8px;font-size:12.5px;padding:4px 0;cursor:pointer}\n.grp input{width:15px;height:15px;accent-color:var(--acc)}\n.secret{display:flex;gap:8px}\n.secret code{flex:1;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;background:var(--soft);border:1px solid var(--bd);border-radius:10px;padding:10px 12px}",
                'js' => "const secret=document.getElementById('secret');\n"
                    . "document.getElementById('reveal').addEventListener('click',()=>{\n"
                    . "  const masked=secret.dataset.masked==='1';\n"
                    . "  secret.textContent=masked?secret.dataset.full:secret.dataset.mask;\n"
                    . "  secret.dataset.masked=masked?'0':'1';\n"
                    . "  document.getElementById('reveal').textContent=masked?'Hide':'Reveal';\n"
                    . "});\n"
                    . "document.getElementById('test').addEventListener('click',()=>{\n"
                    . "  const out=document.getElementById('testout');\n"
                    . "  out.hidden=false;\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Add an endpoint', 'Events are retried for 24 hours with backoff'),
                    self::stack(
                        '<div><label class="lb" for="url">Endpoint URL</label><div class="row" style="gap:8px">'
                        . self::iconInput('url', 'link', 'url', 'https://api.yoursite.com/webhooks/frugal')
                        . '<button class="btn" type="button" id="test">Send test</button></div>'
                        . '<p class="hint" id="testout" hidden style="color:var(--ok)">Test event delivered · 200 OK in 142 ms</p></div>',
                        self::field('desc', 'Description', self::input('desc', 'text', 'What this endpoint is for')),
                        '<div><span class="lb">Events to send</span><div class="events">'
                        . '<div class="grp"><h4>Components</h4>'
                        . '<label><input type="checkbox" checked>component.published</label>'
                        . '<label><input type="checkbox" checked>component.updated</label>'
                        . '<label><input type="checkbox">component.deleted</label>'
                        . '<label><input type="checkbox" checked>component.downloaded</label></div>'
                        . '<div class="grp"><h4>Drawings</h4>'
                        . '<label><input type="checkbox">drawing.created</label>'
                        . '<label><input type="checkbox" checked>drawing.approved</label>'
                        . '<label><input type="checkbox">drawing.rejected</label></div>'
                        . '<div class="grp"><h4>Billing</h4>'
                        . '<label><input type="checkbox" checked>invoice.paid</label>'
                        . '<label><input type="checkbox" checked>invoice.failed</label>'
                        . '<label><input type="checkbox">subscription.cancelled</label></div>'
                        . '</div></div>',
                        '<div><span class="lb">Signing secret</span><div class="secret">'
                        . '<code id="secret" data-masked="1" data-mask="whsec_••••••••••••••••2f81" data-full="whsec_9f2ac41b77de0c40f82f81">whsec_••••••••••••••••2f81</code>'
                        . '<button class="btn" type="button" id="reveal">Reveal</button></div>'
                        . '<p class="hint">Verify the <code>X-Frugal-Signature</code> header against this secret on every request.</p></div>'
                    ),
                    self::actions('Create endpoint', 'Cancel')
                ),
            ],
            [
                'slug' => 'dns-record-form',
                'name' => 'DNS record form',
                'name_ar' => 'نموذج سجل DNS',
                'tagline' => 'Record type, host, value and TTL with type-aware hints.',
                'tagline_ar' => 'نوع السجل والمضيف والقيمة وTTL مع تلميحات حسب النوع.',
                'summary' => 'The hint under the value field changes with the record type, because the single most common DNS mistake is putting an A record value into a CNAME. TTL is a select of sensible values rather than a free number.',
                'summary_ar' => 'يتغير التلميح أسفل حقل القيمة بحسب نوع السجل، لأن أكثر أخطاء DNS شيوعًا هو وضع قيمة سجل A في سجل CNAME. وTTL قائمة بقيم معقولة لا رقم حر.',
                'accent' => '#0891b2',
                'tags' => ['dns', 'domains', 'developer', 'infrastructure'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Value hint and placeholder change with the record type', 'TTL offered as named durations', 'Proxy toggle for CDN-backed records'],
                'features_ar' => ['تلميح القيمة والنص النائب يتغيران بحسب نوع السجل', 'TTL يُعرض مددًا مسمّاة', 'مفتاح الوكيل للسجلات المدعومة بـ CDN'],
                'height' => 620,
                'max' => 560,
                'css' => self::toggleCss() . "\n.mono input{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px}",
                'js' => "const hints={\n"
                    . "  A:['An IPv4 address','192.0.2.41'],\n"
                    . "  AAAA:['An IPv6 address','2001:db8::41'],\n"
                    . "  CNAME:['Another hostname, ending in a dot','target.frugaldomain.site.'],\n"
                    . "  MX:['Mail server hostname, with a priority','10 mail.frugaldomain.site.'],\n"
                    . "  TXT:['Any text - SPF, DKIM or verification','v=spf1 include:_spf.frugal.io ~all']\n"
                    . "};\n"
                    . "const type=document.getElementById('rtype');\n"
                    . "type.addEventListener('change',()=>{\n"
                    . "  const [hint,example]=hints[type.value]||hints.A;\n"
                    . "  document.getElementById('vhint').textContent=hint;\n"
                    . "  document.getElementById('rvalue').placeholder=example;\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Add DNS record', 'frugaldomain.site · changes propagate within the TTL'),
                    self::stack(
                        self::cols(
                            2,
                            '<div><label class="lb" for="rtype">Type</label><select class="in" id="rtype"><option>A</option><option>AAAA</option><option>CNAME</option><option>MX</option><option>TXT</option></select></div>',
                            self::field('rhost', 'Host', self::input('rhost', 'text', '@ or a subdomain'), 'Use <b>@</b> for the root domain.')
                        ),
                        '<div class="mono"><label class="lb" for="rvalue">Value</label>' . self::input('rvalue', 'text', '192.0.2.41')
                        . '<p class="hint" id="vhint">An IPv4 address</p></div>',
                        self::cols(
                            2,
                            self::field('rttl', 'TTL', self::select('rttl', ['Automatic', '1 minute', '5 minutes', '1 hour', '12 hours', '1 day'])),
                            self::field('rprio', 'Priority <span class="mut" style="font-weight:500">(MX only)</span>', self::input('rprio', 'text', '10', 'inputmode="numeric"'))
                        ),
                        self::toggle('proxy', 'Proxy through the CDN', 'Hides the origin address and adds caching', true),
                        self::field('rnote', 'Note <span class="mut" style="font-weight:500">(optional)</span>', self::input('rnote', 'text', 'Why this record exists'))
                    ),
                    self::actions('Save record', 'Cancel')
                ),
            ],
            [
                'slug' => 'filter-panel-form',
                'name' => 'Product filter panel',
                'name_ar' => 'نموذج تصفية المنتجات',
                'tagline' => 'Price range, categories, rating and colour swatches.',
                'tagline_ar' => 'نطاق السعر والفئات والتقييم وعيّنات الألوان.',
                'summary' => 'The sidebar of a shop: a dual price range whose numbers update as it moves, category counts so nobody filters their way to an empty page, and colour swatches as real radio inputs rather than clickable divs.',
                'summary_ar' => 'الشريط الجانبي للمتجر: نطاق سعر مزدوج تتحدث أرقامه مع الحركة، وأعداد النتائج لكل فئة كي لا تنتهي التصفية بصفحة فارغة، وعيّنات ألوان مبنية على أزرار اختيار حقيقية لا عناصر div قابلة للنقر.',
                'accent' => '#7c3aed',
                'tags' => ['filters', 'ecommerce', 'sidebar', 'range'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Range slider with a live value readout', 'Result counts beside every category', 'Colour swatches built on radio inputs'],
                'features_ar' => ['منزلق نطاق مع عرض حيّ للقيمة', 'عدد النتائج بجوار كل فئة', 'عيّنات ألوان مبنية على أزرار اختيار'],
                'height' => 720,
                'max' => 360,
                'css' => ".sec{padding:16px 20px;border-bottom:1px solid var(--bd)}\n.sec:last-child{border-bottom:0}\n.sec h4{margin-bottom:10px}\n.sec label{display:flex;align-items:center;gap:9px;font-size:13px;padding:5px 0;cursor:pointer}\n.sec label input[type=checkbox]{width:16px;height:16px;accent-color:var(--acc)}\n.sec .n{margin-left:auto;color:var(--mut);font-size:11.5px}\ninput[type=range]{width:100%;accent-color:var(--acc)}\n.prices{display:flex;justify-content:space-between;font-size:12.5px;font-weight:650;margin-top:6px;font-variant-numeric:tabular-nums}\n.sw{display:flex;gap:9px;flex-wrap:wrap}\n.sw input{position:absolute;opacity:0}\n.sw span{display:block;width:28px;height:28px;border-radius:50%;cursor:pointer;box-shadow:0 0 0 1px var(--bd)}\n.sw input:checked+span{box-shadow:0 0 0 2px var(--acc),0 0 0 4px var(--acc-soft)}",
                'js' => "const range=document.getElementById('price');\n"
                    . "range.addEventListener('input',()=>document.getElementById('pmax').textContent='\$'+range.value);",
                'body' => self::wrap(
                    self::head('Filters', '412 products', '<button class="btn gh tiny" type="button">Clear all</button>'),
                    '<form onsubmit="return false">'
                    . '<div class="sec"><h4>Price</h4>'
                    . '<input type="range" id="price" min="0" max="500" value="240" aria-label="Maximum price">'
                    . '<div class="prices"><span>$0</span><span id="pmax">$240</span></div></div>'
                    . '<div class="sec"><h4>Category</h4>'
                    . '<label><input type="checkbox" checked>Lighting<span class="n">112</span></label>'
                    . '<label><input type="checkbox">Seating<span class="n">88</span></label>'
                    . '<label><input type="checkbox" checked>Tables<span class="n">64</span></label>'
                    . '<label><input type="checkbox">Storage<span class="n">41</span></label>'
                    . '<label><input type="checkbox">Textiles<span class="n">107</span></label></div>'
                    . '<div class="sec"><h4>Colour</h4><div class="sw">'
                    . '<label><input type="radio" name="colour" aria-label="Black"><span style="background:#111827"></span></label>'
                    . '<label><input type="radio" name="colour" checked aria-label="Sand"><span style="background:#e7d8c2"></span></label>'
                    . '<label><input type="radio" name="colour" aria-label="Sage"><span style="background:#a3b8a5"></span></label>'
                    . '<label><input type="radio" name="colour" aria-label="Terracotta"><span style="background:#c2703b"></span></label>'
                    . '<label><input type="radio" name="colour" aria-label="Indigo"><span style="background:#4f46e5"></span></label>'
                    . '</div></div>'
                    . '<div class="sec"><h4>Rating</h4>'
                    . '<label><input type="checkbox" checked>' . Kit::stars(5, 13) . '<span class="n">64</span></label>'
                    . '<label><input type="checkbox">' . Kit::stars(4, 13) . '<span class="n">148</span></label>'
                    . '<label><input type="checkbox">' . Kit::stars(3, 13) . '<span class="n">96</span></label></div>'
                    . '<div class="sec"><h4>Availability</h4>'
                    . '<label><input type="checkbox" checked>In stock only<span class="n">388</span></label>'
                    . '<label><input type="checkbox">On sale<span class="n">52</span></label></div>'
                    . '</form>',
                    '<div class="ft"><button class="btn" type="button">Reset</button><button class="btn pri" type="button">Show 214 results</button></div>'
                ),
            ],
            [
                'slug' => 'advanced-search-form',
                'name' => 'Advanced search form',
                'name_ar' => 'نموذج بحث متقدم',
                'tagline' => 'Field, operator and value rows joined by AND or OR.',
                'tagline_ar' => 'صفوف حقل وعامل وقيمة تربطها AND أو OR.',
                'summary' => 'A query builder without a query language: each row is a field, an operator and a value, and the join between rows is a single switch. It is the pattern behind every saved-search feature, and it fits in fifty lines.',
                'summary_ar' => 'منشئ استعلامات بلا لغة استعلام: كل صف حقل وعامل وقيمة، والربط بين الصفوف مفتاح واحد. إنه النمط الكامن وراء كل ميزة بحث محفوظ، ويتسع في خمسين سطرًا.',
                'accent' => '#4f46e5',
                'tags' => ['search', 'query-builder', 'filters', 'advanced'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Condition rows added and removed freely', 'AND or OR chosen once for the whole query', 'Live preview of the query being built'],
                'features_ar' => ['صفوف شروط تُضاف وتُحذف بحرية', 'AND أو OR يُختار مرة واحدة للاستعلام كله', 'معاينة حيّة للاستعلام أثناء بنائه'],
                'height' => 640,
                'max' => 680,
                'css' => ".cond{display:grid;grid-template-columns:1.1fr 1fr 1.4fr auto;gap:9px;align-items:center}\n.cond .in{padding:8px 10px;font-size:13px}\n.rm{border:1px solid var(--bd);border-radius:9px;background:var(--card);padding:8px;cursor:pointer;color:var(--mut);display:inline-flex}\n.rm:hover{border-color:var(--bad);color:var(--bad)}\n.join{display:inline-flex;padding:3px;background:var(--soft);border:1px solid var(--bd);border-radius:999px}\n.join button{border:0;background:none;padding:5px 13px;border-radius:999px;font:inherit;font-size:12px;font-weight:700;color:var(--mut);cursor:pointer}\n.join button[aria-pressed=true]{background:var(--acc);color:#fff}\n.preview{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;background:var(--soft);border:1px solid var(--bd);border-radius:10px;padding:11px 13px;color:var(--mut);word-break:break-word}\n@media (max-width:560px){.cond{grid-template-columns:1fr 1fr}}",
                'js' => "const rows=document.getElementById('conds');\n"
                    . "let join='AND';\n"
                    . "function preview(){\n"
                    . "  const parts=[...rows.querySelectorAll('.cond')].map(row=>{\n"
                    . "    const selects=row.querySelectorAll('select');\n"
                    . "    const value=row.querySelector('input').value||'...';\n"
                    . "    return selects[0].value+' '+selects[1].value+' \"'+value+'\"';\n"
                    . "  });\n"
                    . "  document.getElementById('q').textContent=parts.join(' '+join+' ');\n"
                    . "}\n"
                    . "rows.addEventListener('input',preview);\n"
                    . "rows.addEventListener('change',preview);\n"
                    . "rows.addEventListener('click',event=>{\n"
                    . "  const button=event.target.closest('.rm');\n"
                    . "  if(button&&rows.children.length>1){button.closest('.cond').remove();preview();}\n"
                    . "});\n"
                    . "document.getElementById('addcond').addEventListener('click',()=>{\n"
                    . "  const row=rows.firstElementChild.cloneNode(true);\n"
                    . "  row.querySelector('input').value='';\n"
                    . "  rows.appendChild(row);\n"
                    . "  preview();\n"
                    . "});\n"
                    . "document.querySelectorAll('.join button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.join button').forEach(b=>b.setAttribute('aria-pressed','false'));\n"
                    . "    button.setAttribute('aria-pressed','true');\n"
                    . "    join=button.textContent.trim();\n"
                    . "    preview();\n"
                    . "  });\n"
                    . "});\npreview();",
                'body' => self::wrap(
                    self::head('Advanced search', 'Build a query, save it, reuse it', '<span class="join"><button type="button" aria-pressed="true">AND</button><button type="button" aria-pressed="false">OR</button></span>'),
                    self::stack(
                        '<div id="conds" style="display:grid;gap:9px">'
                        . '<div class="cond">'
                        . '<select class="in" aria-label="Field"><option>name</option><option>category</option><option>tag</option><option>downloads</option><option>updated</option></select>'
                        . '<select class="in" aria-label="Operator"><option>contains</option><option>is</option><option>is not</option><option>starts with</option><option>is greater than</option></select>'
                        . '<input class="in" placeholder="Value" aria-label="Value" value="table">'
                        . '<button class="rm" type="button" aria-label="Remove condition">' . Kit::icon('trash', 15) . '</button></div>'
                        . '<div class="cond">'
                        . '<select class="in" aria-label="Field"><option>category</option><option>name</option><option>tag</option><option>downloads</option><option>updated</option></select>'
                        . '<select class="in" aria-label="Operator"><option>is</option><option>contains</option><option>is not</option></select>'
                        . '<input class="in" placeholder="Value" aria-label="Value" value="dashboards">'
                        . '<button class="rm" type="button" aria-label="Remove condition">' . Kit::icon('trash', 15) . '</button></div>'
                        . '</div>',
                        '<button class="btn tiny" type="button" id="addcond" style="justify-self:start">' . Kit::icon('plus', 14) . 'Add condition</button>',
                        '<div><span class="lb">Query</span><div class="preview" id="q"></div></div>',
                        self::cols(
                            2,
                            self::field('sortby', 'Sort by', self::select('sortby', ['Most relevant', 'Newest first', 'Most downloaded', 'Alphabetical'])),
                            self::field('savename', 'Save this search as', self::input('savename', 'text', 'Optional name'))
                        )
                    ),
                    self::actions('Search', 'Reset', '412 components indexed')
                ),
            ],
            [
                'slug' => 'magic-link-form',
                'name' => 'Magic link sign in',
                'name_ar' => 'نموذج الدخول برابط سحري',
                'tagline' => 'Passwordless sign-in with an email-sent confirmation state.',
                'tagline_ar' => 'دخول بلا كلمة مرور مع حالة تأكيد إرسال البريد.',
                'summary' => 'One field, no password, and a confirmation that names the address so a typo is caught immediately. The open-mail-app shortcut is the difference between a link that gets clicked and one that waits in an inbox.',
                'summary_ar' => 'حقل واحد بلا كلمة مرور، وتأكيد يذكر العنوان فيُكتشف الخطأ الإملائي فورًا. واختصار فتح تطبيق البريد هو الفرق بين رابط يُنقر عليه ورابط ينتظر في صندوق الوارد.',
                'accent' => '#7c3aed',
                'tags' => ['auth', 'passwordless', 'magic-link', 'email'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Sent state names the address it went to', 'Shortcut to open the mail app', 'Change-address link without starting over'],
                'features_ar' => ['حالة الإرسال تذكر العنوان المرسَل إليه', 'اختصار لفتح تطبيق البريد', 'رابط لتغيير العنوان دون البدء من جديد'],
                'height' => 520,
                'max' => 420,
                'css' => "#sent{display:none;text-align:center;padding:26px 24px}\n#sent.show{display:block}\nform.hide{display:none}\n.addr{font-weight:700;color:var(--acc)}",
                'js' => "document.querySelector('form').addEventListener('submit',event=>{\n"
                    . "  event.preventDefault();\n"
                    . "  const value=document.getElementById('email').value||'you@company.com';\n"
                    . "  document.getElementById('addr').textContent=value;\n"
                    . "  event.target.classList.add('hide');\n"
                    . "  document.getElementById('sent').classList.add('show');\n"
                    . "});\n"
                    . "document.getElementById('again').addEventListener('click',()=>{\n"
                    . "  document.getElementById('sent').classList.remove('show');\n"
                    . "  document.querySelector('form').classList.remove('hide');\n"
                    . '});',
                'body' => self::wrap(
                    self::lockup('zap', 'Sign in without a password', 'We email you a link that signs you straight in'),
                    self::stack(
                        self::field('email', 'Email address', self::iconInput('email', 'mail', 'email', 'you@company.com', 'autocomplete="email" required')),
                        self::submit('Email me a link', 'send')
                    ),
                    '<div id="sent"><span style="display:inline-flex;color:var(--acc);margin-bottom:12px">' . Kit::icon('mail', 40, 1.6) . '</span>'
                    . '<h3>Link on its way</h3>'
                    . '<p class="sub" style="margin-top:6px">We sent a sign-in link to <span class="addr" id="addr"></span>. It works once and expires in 15 minutes.</p>'
                    . '<div class="row" style="gap:8px;justify-content:center;margin-top:16px">'
                    . '<a class="btn pri" href="#">Open mail app</a>'
                    . '<button class="btn" type="button" id="again">Use another address</button></div></div>',
                    '<div class="ft" style="justify-content:center"><span>Prefer a password? <a href="#">Sign in normally</a></span></div>'
                ),
            ],
            [
                'slug' => 'onboarding-goals-form',
                'name' => 'Onboarding goals form',
                'name_ar' => 'نموذج أهداف البدء',
                'tagline' => 'Pick-several cards that personalise the first session.',
                'tagline_ar' => 'بطاقات متعددة الاختيار تخصّص الجلسة الأولى.',
                'summary' => 'The question every product asks on day one, built as selectable cards with a limit: choosing three things is a decision, choosing everything is not. The counter tells the user where they are against the limit.',
                'summary_ar' => 'السؤال الذي يطرحه كل منتج في اليوم الأول، مبنيًا ببطاقات قابلة للتحديد مع حد أقصى: اختيار ثلاثة أشياء قرار، واختيار كل شيء ليس كذلك. والعدّاد يُعلم المستخدم أين هو من الحد.',
                'accent' => '#0891b2',
                'tags' => ['onboarding', 'cards', 'multi-select', 'personalisation'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Selectable cards with a maximum of three', 'Live counter and a disabled continue button', 'Checkbox inputs underneath, so it submits'],
                'features_ar' => ['بطاقات قابلة للتحديد بحد أقصى ثلاث', 'عدّاد حيّ وزر متابعة معطّل', 'مربعات اختيار في الأساس، فيُرسَل النموذج'],
                'height' => 640,
                'max' => 640,
                'css' => ".goals{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(170px,1fr))}\n.goal{position:relative;border:1px solid var(--bd);border-radius:14px;padding:16px;cursor:pointer;transition:.15s;display:block}\n.goal:hover{border-color:var(--acc)}\n.goal input{position:absolute;opacity:0}\n.goal .t{display:block;font-weight:650;font-size:13.5px;margin-top:10px}\n.goal .d{display:block;font-size:11.5px;color:var(--mut);margin-top:3px}\n.goal:has(input:checked){border-color:var(--acc);background:var(--acc-soft)}\n.goal .tick{position:absolute;top:12px;right:12px;opacity:0;color:var(--acc)}\n.goal:has(input:checked) .tick{opacity:1}",
                'js' => "const limit=3;\n"
                    . "const boxes=[...document.querySelectorAll('.goal input')];\n"
                    . "const counter=document.getElementById('count');\n"
                    . "const next=document.getElementById('next');\n"
                    . "function refresh(){\n"
                    . "  const picked=boxes.filter(box=>box.checked).length;\n"
                    . "  counter.textContent=picked+' of '+limit+' chosen';\n"
                    . "  next.disabled=picked===0;\n"
                    . "  boxes.forEach(box=>box.disabled=!box.checked&&picked>=limit);\n"
                    . "}\n"
                    . "boxes.forEach(box=>box.addEventListener('change',refresh));\nrefresh();",
                'body' => self::wrap(
                    self::head('What brings you here?', 'Pick up to three - it shapes what we show you first'),
                    '<form class="pad" onsubmit="return false"><div class="goals">'
                    . implode('', array_map(function ($goal) {
                        [$icon, $colour, $title, $description] = $goal;

                        return '<label class="goal"><input type="checkbox">'
                            . '<span class="tick">' . Kit::icon('check', 18, 2.6) . '</span>'
                            . Kit::iconTile($icon, $colour, 40)
                            . '<span class="t">' . $title . '</span><span class="d">' . $description . '</span></label>';
                    }, [
                        ['grid', '#2563eb', 'Build a dashboard', 'Stat tiles, charts and tables'],
                        ['edit', '#7c3aed', 'Draw and illustrate', 'Vector editing in the browser'],
                        ['image', '#059669', 'Find icons', 'Thousands, free to use'],
                        ['code', '#d97706', 'Grab components', 'Single-file templates to copy'],
                        ['refresh', '#0891b2', 'Convert files', 'Images, audio and documents'],
                        ['users', '#db2777', 'Work with a team', 'Shared libraries and reviews'],
                    ]))
                    . '</div></form>',
                    '<div class="ft"><span id="count">0 of 3 chosen</span>'
                    . '<span class="row" style="gap:8px"><button class="btn" type="button">Skip</button>'
                    . '<button class="btn pri" type="button" id="next">Continue</button></span></div>'
                ),
            ],
            [
                'slug' => 'change-email-form',
                'name' => 'Change email address form',
                'name_ar' => 'نموذج تغيير البريد الإلكتروني',
                'tagline' => 'New address, confirmation and the current password.',
                'tagline_ar' => 'العنوان الجديد وتأكيده وكلمة المرور الحالية.',
                'summary' => 'Changing the address that owns an account is a security action, so it asks for the current password and states plainly that the change only takes effect once the new address is confirmed. Both facts are in the form, not in a help article.',
                'summary_ar' => 'تغيير العنوان المالك للحساب إجراء أمني، لذا يطلب النموذج كلمة المرور الحالية ويوضح صراحة أن التغيير لا يسري إلا بعد تأكيد العنوان الجديد. والحقيقتان كلتاهما داخل النموذج، لا في مقالة مساعدة.',
                'accent' => '#475569',
                'tags' => ['account', 'email', 'security', 'settings'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Confirmation field checked as it is typed', 'Current password required for the change', 'Pending-verification state explained inline'],
                'features_ar' => ['حقل التأكيد يُتحقق منه أثناء الكتابة', 'كلمة المرور الحالية مطلوبة للتغيير', 'حالة انتظار التحقق موضّحة ضمن النموذج'],
                'height' => 580,
                'max' => 480,
                'css' => ".cur{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;background:var(--soft);font-size:13px}\n.match{font-size:11.5px;margin-top:5px;display:none}\n.match.show{display:block}\n.match.ok{color:var(--ok)}\n.match.no{color:var(--bad)}",
                'js' => "const one=document.getElementById('new1');\n"
                    . "const two=document.getElementById('new2');\n"
                    . "const note=document.getElementById('match');\n"
                    . "function check(){\n"
                    . "  if(!two.value){note.classList.remove('show');return;}\n"
                    . "  const same=one.value===two.value;\n"
                    . "  note.textContent=same?'Addresses match':'Addresses do not match';\n"
                    . "  note.className='match show '+(same?'ok':'no');\n"
                    . "}\n"
                    . "one.addEventListener('input',check);\ntwo.addEventListener('input',check);",
                'body' => self::wrap(
                    self::head('Change email address', 'You sign in with this address'),
                    self::stack(
                        '<div class="cur">' . Kit::icon('mail', 17) . '<span><span class="mut xs" style="display:block">Current address</span><b>lina@frugal.io</b></span>'
                        . Kit::pill('Verified', 'ok') . '</div>',
                        self::field('new1', 'New email address', self::input('new1', 'email', 'you@company.com', 'autocomplete="email"')),
                        '<div><label class="lb" for="new2">Confirm new address</label>' . self::input('new2', 'email', 'Type it again')
                        . '<p class="match" id="match"></p></div>',
                        self::field('curpass', 'Current password', self::iconInput('curpass', 'lock', 'password', 'To confirm it is you', 'autocomplete="current-password"')),
                        '<p class="hint">' . Kit::icon('info', 13) . ' Your address changes only after you click the link we send to the new one. Until then, keep signing in with the old address.</p>'
                    ),
                    self::actions('Send confirmation', 'Cancel')
                ),
            ],
            [
                'slug' => 'delete-account-form',
                'name' => 'Delete account confirmation',
                'name_ar' => 'نموذج تأكيد حذف الحساب',
                'tagline' => 'Type-to-confirm deletion with a list of what goes.',
                'tagline_ar' => 'حذف بالتأكيد الكتابي مع قائمة بما سيُحذف.',
                'summary' => 'Destructive confirmation done properly: it lists exactly what will be destroyed, asks why - which is the only chance to learn anything - and requires the account name to be typed before the button unlocks.',
                'summary_ar' => 'تأكيد الإجراء الهدّام كما يجب: يسرد بدقة ما سيُتلف، ويسأل عن السبب، وهي الفرصة الوحيدة لتعلّم شيء، ويشترط كتابة اسم الحساب قبل تفعيل الزر.',
                'accent' => '#be123c',
                'tags' => ['danger', 'delete', 'confirmation', 'account'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Button stays disabled until the name is typed exactly', 'Explicit list of what is destroyed', 'Export offered before the deletion'],
                'features_ar' => ['الزر معطّل حتى يُكتب الاسم مطابقًا تمامًا', 'قائمة صريحة بما سيُحذف', 'عرض التصدير قبل الحذف'],
                'height' => 660,
                'max' => 500,
                'css' => ".losses{display:grid;gap:8px;padding:14px;border-radius:12px;background:var(--bad-bg);color:var(--bad);font-size:12.5px}\n.losses span{display:flex;gap:8px;align-items:flex-start}\n#confirm{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}\n.danger-btn{background:var(--bad);border-color:var(--bad);color:#fff}\n.danger-btn:hover:not([disabled]){filter:brightness(1.08);color:#fff}",
                'js' => "const field=document.getElementById('confirm');\n"
                    . "const button=document.getElementById('kill');\n"
                    . "field.addEventListener('input',()=>{\n"
                    . "  button.disabled=field.value.trim()!=='acme-design';\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Delete this account', 'This cannot be undone', Kit::pill('Irreversible', 'bad')),
                    self::stack(
                        '<div class="losses">'
                        . '<span>' . Kit::icon('x', 15) . '<span><b>142 components</b> and every file uploaded with them</span></span>'
                        . '<span>' . Kit::icon('x', 15) . '<span><b>1,284 drawings</b>, including anything shared publicly</span></span>'
                        . '<span>' . Kit::icon('x', 15) . '<span><b>9 team members</b> lose access immediately</span></span>'
                        . '<span>' . Kit::icon('x', 15) . '<span>Your workspace URL <b>frugal.app/acme</b> is released</span></span>'
                        . '</div>',
                        '<div class="row" style="gap:9px;padding:12px 14px;border:1px solid var(--bd);border-radius:12px">'
                        . Kit::icon('download', 17) . '<span class="sm" style="flex:1">Download an export of everything first</span>'
                        . '<button class="btn tiny" type="button">Export</button></div>',
                        self::field('reason', 'Why are you leaving? <span class="mut" style="font-weight:500">(optional)</span>', self::select('reason', ['I no longer need it', 'Too expensive', 'Missing a feature I need', 'Moving to another tool', 'Something went wrong'])),
                        self::field('confirm', 'Type <b>acme-design</b> to confirm', self::input('confirm', 'text', 'acme-design', 'autocomplete="off"')),
                        '<button class="btn danger-btn" type="submit" id="kill" style="width:100%;padding:11px" disabled>' . Kit::icon('trash', 16) . 'Delete this account permanently</button>'
                    ),
                    '<div class="ft" style="justify-content:center"><a href="#">Cancel and keep my account</a></div>'
                ),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private static function setThree(): array
    {
        return [
            [
                'slug' => 'card-preview-form',
                'name' => 'Card form with live preview',
                'name_ar' => 'نموذج بطاقة مع معاينة حية',
                'tagline' => 'A drawn card that fills in as the fields are typed.',
                'tagline_ar' => 'بطاقة مرسومة تمتلئ أثناء كتابة الحقول.',
                'summary' => 'The card above the form mirrors every keystroke, which catches transposed digits far better than re-reading the field does. The card is drawn with a CSS gradient - no image, no card-brand logos to license.',
                'summary_ar' => 'البطاقة أعلى النموذج تعكس كل ضغطة مفتاح، وهذا يكشف الأرقام المتبادلة أفضل بكثير من إعادة قراءة الحقل. والبطاقة مرسومة بتدرّج CSS، فلا صور ولا شعارات علامات بطاقات تحتاج إلى ترخيص.',
                'accent' => '#4f46e5',
                'tags' => ['payment', 'card', 'preview', 'checkout'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Live card preview drawn in CSS', 'Number grouped and masked as it is typed', 'Name upper-cased the way it is embossed'],
                'features_ar' => ['معاينة حيّة للبطاقة مرسومة بـ CSS', 'الرقم يُجمَّع ويُخفى أثناء الكتابة', 'الاسم بأحرف كبيرة كما يُطبع بارزًا'],
                'height' => 720,
                'max' => 460,
                'css' => ".cardview{margin:22px auto 6px;width:300px;height:186px;border-radius:18px;padding:20px;color:#fff;background:linear-gradient(135deg,var(--acc),var(--acc-dk) 60%,#0f172a);box-shadow:0 18px 40px -22px rgba(15,23,42,.9);display:flex;flex-direction:column;justify-content:space-between}\n.cardview .chip{width:38px;height:28px;border-radius:6px;background:linear-gradient(140deg,#fde68a,#d97706)}\n.cardview .no{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:19px;letter-spacing:.09em}\n.cardview .foot{display:flex;justify-content:space-between;font-size:11px;letter-spacing:.06em;text-transform:uppercase;opacity:.9}\n.cardview .foot b{display:block;font-size:13px;letter-spacing:.04em;margin-top:3px}",
                'js' => "const number=document.getElementById('num');\n"
                    . "const holder=document.getElementById('holder');\n"
                    . "const expiry=document.getElementById('exp');\n"
                    . "number.addEventListener('input',()=>{\n"
                    . "  const digits=number.value.replace(/\\D/g,'').slice(0,16);\n"
                    . "  number.value=digits.replace(/(.{4})/g,'$1 ').trim();\n"
                    . "  document.getElementById('vno').textContent=(digits.padEnd(16,'•')).replace(/(.{4})/g,'$1 ').trim();\n"
                    . "});\n"
                    . "holder.addEventListener('input',()=>{\n"
                    . "  document.getElementById('vname').textContent=holder.value.toUpperCase()||'YOUR NAME';\n"
                    . "});\n"
                    . "expiry.addEventListener('input',()=>{\n"
                    . "  const digits=expiry.value.replace(/\\D/g,'').slice(0,4);\n"
                    . "  expiry.value=digits.length>2?digits.slice(0,2)+'/'+digits.slice(2):digits;\n"
                    . "  document.getElementById('vexp').textContent=expiry.value||'MM/YY';\n"
                    . '});',
                'body' => self::wrap(
                    '<div class="cardview"><span class="chip"></span>'
                    . '<span class="no" id="vno">•••• •••• •••• ••••</span>'
                    . '<span class="foot"><span>Card holder<b id="vname">YOUR NAME</b></span><span>Expires<b id="vexp">MM/YY</b></span></span></div>',
                    self::stack(
                        self::field('num', 'Card number', self::input('num', 'text', '4242 4242 4242 4242', 'inputmode="numeric" autocomplete="cc-number"')),
                        self::field('holder', 'Card holder', self::input('holder', 'text', 'Lina Haddad', 'autocomplete="cc-name"')),
                        self::cols(
                            2,
                            self::field('exp', 'Expires', self::input('exp', 'text', 'MM/YY', 'inputmode="numeric" autocomplete="cc-exp"')),
                            self::field('cvv', 'CVV', self::input('cvv', 'text', '123', 'inputmode="numeric" autocomplete="cc-csc"'))
                        ),
                        self::submit('Add card', 'plus')
                    )
                ),
            ],
            [
                'slug' => 'bank-transfer-form',
                'name' => 'Bank transfer form',
                'name_ar' => 'نموذج حوالة بنكية',
                'tagline' => 'IBAN entry with grouping, bank lookup and a confirm step.',
                'tagline_ar' => 'إدخال IBAN مع التجميع والتعرّف على البنك وخطوة تأكيد.',
                'summary' => 'Transfers go wrong on the account number, so the IBAN is grouped in fours as it is typed and the recognised bank name appears underneath as confirmation. The amount and reference sit together, since both end up on the recipient statement.',
                'summary_ar' => 'تخفق الحوالات عند رقم الحساب، لذا يُجمَّع IBAN في مجموعات من أربعة أثناء الكتابة ويظهر اسم البنك المتعرَّف عليه أسفله تأكيدًا. والمبلغ والمرجع متجاوران، لأن كليهما يظهر في كشف المستفيد.',
                'accent' => '#1d4ed8',
                'tags' => ['banking', 'transfer', 'iban', 'finance'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['IBAN grouped into blocks of four while typing', 'Bank identified from the IBAN prefix', 'Reference field with a character limit'],
                'features_ar' => ['IBAN يُجمَّع في كتل من أربعة أثناء الكتابة', 'التعرّف على البنك من بادئة IBAN', 'حقل المرجع مع حد للأحرف'],
                'height' => 680,
                'max' => 520,
                'css' => ".mono input{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.06em}\n.bank{display:none;align-items:center;gap:7px;margin-top:6px;font-size:12px;color:var(--ok);font-weight:650}\n.bank.show{display:flex}\n.money{display:flex}\n.money select{width:auto;border-radius:10px 0 0 10px;border-right:0}\n.money input{border-radius:0 10px 10px 0;font-size:17px;font-weight:700}",
                'js' => "const banks={SA44:'Al Rajhi Bank',SA03:'Saudi National Bank',SA80:'Riyad Bank',JO94:'Arab Bank',AE07:'Emirates NBD'};\n"
                    . "const iban=document.getElementById('iban');\n"
                    . "iban.addEventListener('input',()=>{\n"
                    . "  const clean=iban.value.toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,24);\n"
                    . "  iban.value=clean.replace(/(.{4})/g,'$1 ').trim();\n"
                    . "  const name=banks[clean.slice(0,4)];\n"
                    . "  const note=document.getElementById('bank');\n"
                    . "  note.classList.toggle('show',Boolean(name));\n"
                    . "  if(name)document.getElementById('bankname').textContent=name;\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Send a transfer', 'Arrives same day before 15:00'),
                    self::stack(
                        self::field('bname', 'Recipient name', self::input('bname', 'text', 'As it appears on their account')),
                        '<div class="mono"><label class="lb" for="iban">IBAN</label>' . self::input('iban', 'text', 'SA44 2000 0001 2345 6789 1234')
                        . '<span class="bank" id="bank">' . Kit::icon('check', 14, 2.4) . '<span id="bankname"></span></span></div>',
                        '<div><label class="lb" for="amount">Amount</label><div class="money">'
                        . self::select('cur', ['SAR', 'USD', 'EUR', 'JOD'])
                        . self::input('amount', 'text', '0.00', 'inputmode="decimal"')
                        . '</div><p class="hint">Fee SAR 1.00 · you send, they receive the full amount.</p></div>',
                        self::field('ref', 'Reference', self::input('ref', 'text', 'Invoice 2281', 'maxlength="35"'), 'Up to 35 characters, shown on their statement.'),
                        self::field('when', 'Send', self::select('when', ['Immediately', 'Tomorrow morning', 'On a specific date', 'Repeat monthly'])),
                        self::checkbox('savepayee', 'Save this recipient for next time', true)
                    ),
                    self::actions('Review transfer', 'Cancel', 'You will confirm before anything moves')
                ),
            ],
            [
                'slug' => 'identity-verification-form',
                'name' => 'Identity verification form',
                'name_ar' => 'نموذج التحقق من الهوية',
                'tagline' => 'Document type, front and back upload and a selfie step.',
                'tagline_ar' => 'نوع الوثيقة ورفع الوجهين وخطوة الصورة الذاتية.',
                'summary' => 'A KYC step that tells people what will pass before they photograph anything: the requirements sit beside the upload area, both sides of the document have their own slot, and a progress line shows how much of the check is left.',
                'summary_ar' => 'خطوة KYC تُعلم الناس بما سيُقبل قبل أن يصوّروا شيئًا: المتطلبات بجوار منطقة الرفع، ولكل وجه من الوثيقة خانة خاصة، وشريط تقدّم يبيّن ما تبقى من التحقق.',
                'accent' => '#0f766e',
                'tags' => ['kyc', 'identity', 'verification', 'upload'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Separate front and back upload slots', 'Requirements listed next to the uploader', 'Progress across the three verification steps'],
                'features_ar' => ['خانتا رفع منفصلتان للوجه الأمامي والخلفي', 'المتطلبات مسرودة بجوار أداة الرفع', 'التقدّم عبر خطوات التحقق الثلاث'],
                'height' => 760,
                'max' => 620,
                'css' => self::stepsCss() . "\n.slots{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}\n.slot{border:2px dashed var(--bd);border-radius:14px;padding:22px 14px;text-align:center;cursor:pointer;transition:.15s}\n.slot:hover{border-color:var(--acc);background:var(--acc-soft)}\n.slot.has{border-style:solid;border-color:var(--ok);background:var(--ok-bg)}\n.slot input{display:none}\n.needs{display:grid;gap:7px;font-size:12px;color:var(--mut)}\n.needs span{display:flex;gap:8px;align-items:flex-start}",
                'js' => "document.querySelectorAll('.slot').forEach(slot=>{\n"
                    . "  const input=slot.querySelector('input');\n"
                    . "  slot.addEventListener('click',()=>input.click());\n"
                    . "  input.addEventListener('change',()=>{\n"
                    . "    if(!input.files[0])return;\n"
                    . "    slot.classList.add('has');\n"
                    . "    slot.querySelector('.cap').textContent=input.files[0].name;\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Verify your identity', 'Required before withdrawals are enabled'),
                    self::steps(['Details', 'Document', 'Selfie'], 1),
                    self::stack(
                        self::cols(
                            2,
                            self::field('doctype', 'Document type', self::select('doctype', ['National ID', 'Passport', 'Driving licence', 'Residence permit'])),
                            self::field('docountry', 'Issuing country', self::select('docountry', ['Saudi Arabia', 'Jordan', 'Egypt', 'United Arab Emirates']))
                        ),
                        self::field('docnum', 'Document number', self::input('docnum', 'text', 'As printed on the document')),
                        '<div><span class="lb">Upload both sides</span><div class="slots">'
                        . '<label class="slot"><input type="file" accept="image/*" aria-label="Front of document">'
                        . '<span style="display:inline-flex;color:var(--acc)">' . Kit::icon('image', 24) . '</span>'
                        . '<span class="bold" style="display:block;margin-top:8px">Front</span>'
                        . '<span class="xs mut cap" style="display:block;margin-top:2px">JPG or PNG, under 8 MB</span></label>'
                        . '<label class="slot"><input type="file" accept="image/*" aria-label="Back of document">'
                        . '<span style="display:inline-flex;color:var(--acc)">' . Kit::icon('image', 24) . '</span>'
                        . '<span class="bold" style="display:block;margin-top:8px">Back</span>'
                        . '<span class="xs mut cap" style="display:block;margin-top:2px">JPG or PNG, under 8 MB</span></label>'
                        . '</div></div>',
                        '<div class="needs">'
                        . '<span>' . Kit::icon('check', 14) . 'All four corners visible, nothing cropped</span>'
                        . '<span>' . Kit::icon('check', 14) . 'No glare across the photograph or the text</span>'
                        . '<span>' . Kit::icon('check', 14) . 'Valid for at least three more months</span>'
                        . '</div>',
                        self::checkbox('kycagree', 'I confirm this document is mine and currently valid')
                    ),
                    self::actions('Continue to selfie', 'Back', 'Checks usually finish within 10 minutes')
                ),
            ],
            [
                'slug' => 'patient-intake-form',
                'name' => 'Patient intake form',
                'name_ar' => 'نموذج استقبال مريض',
                'tagline' => 'Demographics, symptoms, allergies and consent in one pass.',
                'tagline_ar' => 'البيانات الشخصية والأعراض والحساسية والموافقة في مرور واحد.',
                'summary' => 'The clipboard at a clinic reception, made legible: allergies get their own emphasised block because it is the field that matters most clinically, and the symptom duration is asked as a select so the answers stay comparable.',
                'summary_ar' => 'لوح الاستقبال في العيادة وقد صار مقروءًا: للحساسية كتلة بارزة خاصة بها لأنها الحقل الأهم سريريًا، ومدة الأعراض تُسأل بقائمة خيارات لتبقى الإجابات قابلة للمقارنة.',
                'accent' => '#0891b2',
                'tags' => ['healthcare', 'intake', 'clinical', 'patient'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Allergies in an emphasised block of their own', 'Symptom duration as comparable options', 'Consent captured with the treatment section'],
                'features_ar' => ['الحساسية في كتلة بارزة مستقلة', 'مدة الأعراض خيارات قابلة للمقارنة', 'الموافقة تُؤخذ مع قسم العلاج'],
                'height' => 780,
                'max' => 640,
                'css' => ".alert{border:1px solid var(--bad);border-radius:12px;padding:14px;background:var(--bad-bg)}\n.alert .lb{color:var(--bad)}\n.sec{padding-top:6px;border-top:1px solid var(--bd);margin-top:4px}\n.sec h3{margin-bottom:4px}",
                'body' => self::wrap(
                    self::head('New patient intake', 'Please complete before your appointment'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('pfull', 'Full name', self::input('pfull', 'text', 'As on your ID', 'autocomplete="name"')),
                            self::field('pdob', 'Date of birth', self::input('pdob', 'date'))
                        ),
                        self::cols(
                            3,
                            self::field('psex', 'Sex', self::select('psex', ['Female', 'Male', 'Prefer not to say'])),
                            self::field('pphone', 'Phone', self::input('pphone', 'tel', '+966 5x xxx xxxx', 'autocomplete="tel"')),
                            self::field('pins', 'Insurance', self::select('pins', ['Self-paying', 'Bupa', 'Tawuniya', 'MedGulf', 'Other']))
                        ),
                        '<div class="sec"><h3>Why are you here today?</h3></div>',
                        self::field('psymptom', 'Main complaint', self::textarea('psymptom', 'Describe what you are feeling, in your own words.', 3)),
                        self::cols(
                            2,
                            self::field('pdur', 'How long has it been going on?', self::select('pdur', ['Less than a day', '1 - 3 days', 'About a week', '2 - 4 weeks', 'More than a month'])),
                            self::field('ppain', 'Pain level right now', self::select('ppain', ['0 - none', '1 - 3 mild', '4 - 6 moderate', '7 - 9 severe', '10 - worst imaginable']))
                        ),
                        '<div class="alert"><label class="lb" for="pallergy">Allergies and reactions</label>'
                        . self::input('pallergy', 'text', 'Medicines, foods, latex - or write None')
                        . '<p class="hint" style="color:var(--bad)">Tell us even if you think we already know.</p></div>',
                        self::field('pmeds', 'Medicines you take regularly', self::textarea('pmeds', 'Name and dose, including anything over the counter.', 3)),
                        self::checkbox('pconsent', 'I consent to examination and treatment, and confirm the above is accurate')
                    ),
                    self::actions('Submit intake form', 'Save and finish later')
                ),
            ],
            [
                'slug' => 'prescription-form',
                'name' => 'Prescription form',
                'name_ar' => 'نموذج وصفة طبية',
                'tagline' => 'Drug, dose, frequency, duration and refills.',
                'tagline_ar' => 'الدواء والجرعة والتكرار والمدة وإعادة الصرف.',
                'summary' => 'Prescribing broken into its five real fields instead of one free-text line, so the instruction that reaches the pharmacy is unambiguous. The generated sig line is shown back as a sentence before it is signed.',
                'summary_ar' => 'الوصفة مقسّمة إلى حقولها الخمسة الحقيقية بدل سطر نص حر واحد، فتصل التعليمات إلى الصيدلية بلا لبس. ويُعرض سطر التعليمات المولَّد جملةً قبل التوقيع.',
                'accent' => '#4f46e5',
                'tags' => ['healthcare', 'prescription', 'clinical', 'pharmacy'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Structured fields instead of a free-text instruction', 'Generated sig line previewed as a sentence', 'Refill count and substitution consent'],
                'features_ar' => ['حقول منظّمة بدل تعليمات نصية حرة', 'سطر التعليمات المولَّد يُعاين جملةً', 'عدد مرات إعادة الصرف والموافقة على البديل'],
                'height' => 700,
                'max' => 620,
                'css' => ".sig{padding:13px 15px;border-radius:12px;background:var(--acc-soft);color:var(--acc);font-size:13.5px;font-weight:600}\n.pt{display:flex;align-items:center;gap:12px;padding:13px 20px;border-bottom:1px solid var(--bd);background:var(--soft)}",
                'js' => "const parts=['drug','dose','freq','dur'];\n"
                    . "function build(){\n"
                    . "  const drug=document.getElementById('drug').value||'[medicine]';\n"
                    . "  const dose=document.getElementById('dose').value||'[dose]';\n"
                    . "  const freq=document.getElementById('freq').value;\n"
                    . "  const dur=document.getElementById('dur').value;\n"
                    . "  document.getElementById('sig').textContent='Take '+dose+' of '+drug+' '+freq.toLowerCase()+' for '+dur.toLowerCase()+'.';\n"
                    . "}\n"
                    . "parts.forEach(id=>{\n"
                    . "  const field=document.getElementById(id);\n"
                    . "  field.addEventListener('input',build);\n"
                    . "  field.addEventListener('change',build);\n"
                    . "});\nbuild();",
                'body' => self::wrap(
                    self::head('New prescription', 'Dr. Haddad · clinic licence 44-2019'),
                    '<div class="pt">' . Kit::avatar('Rana Khalil', 38) . '<span><b style="display:block">Rana Khalil</b>'
                    . '<span class="xs mut">34 years · MRN 884-2019 · allergic to penicillin</span></span></div>',
                    self::stack(
                        self::cols(
                            2,
                            self::field('drug', 'Medicine', self::input('drug', 'text', 'Amoxicillin')),
                            self::field('dose', 'Dose', self::input('dose', 'text', '500 mg'))
                        ),
                        self::cols(
                            2,
                            self::field('freq', 'Frequency', self::select('freq', ['Once daily', 'Twice daily', 'Three times daily', 'Every 8 hours', 'As needed'])),
                            self::field('dur', 'Duration', self::select('dur', ['3 days', '5 days', '7 days', '14 days', '1 month', 'Ongoing']))
                        ),
                        '<div class="sig" id="sig"></div>',
                        self::cols(
                            2,
                            self::field('route', 'Route', self::select('route', ['By mouth', 'Topical', 'Inhaled', 'Injection'])),
                            self::field('refills', 'Refills', self::select('refills', ['None', '1', '2', '3', '5']))
                        ),
                        self::field('pnotes', 'Notes for the pharmacist <span class="mut" style="font-weight:500">(optional)</span>', self::textarea('pnotes', 'Counselling points, interactions to check.', 2)),
                        self::checkbox('generic', 'Generic substitution permitted', true)
                    ),
                    self::actions('Sign and send to pharmacy', 'Save draft')
                ),
            ],
            [
                'slug' => 'student-enrollment-form',
                'name' => 'Student enrolment form',
                'name_ar' => 'نموذج تسجيل طالب',
                'tagline' => 'Programme, term, courses and a guardian section.',
                'tagline_ar' => 'البرنامج والفصل والمقررات وقسم وليّ الأمر.',
                'summary' => 'Enrolment with the credit arithmetic done on screen: ticking courses adds up the credit hours and warns once the term load is exceeded, which is the mistake that otherwise gets found at the registrar.',
                'summary_ar' => 'تسجيل يُجري حساب الساعات المعتمدة على الشاشة: تحديد المقررات يجمع الساعات وينبّه عند تجاوز عبء الفصل، وهو الخطأ الذي يُكتشف عادةً عند مكتب التسجيل.',
                'accent' => '#7c3aed',
                'tags' => ['education', 'enrolment', 'university', 'courses'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Credit hours totalled as courses are chosen', 'Warning once the term limit is passed', 'Guardian block for under-18 students'],
                'features_ar' => ['الساعات المعتمدة تُجمع مع اختيار المقررات', 'تنبيه عند تجاوز حد الفصل', 'كتلة وليّ الأمر للطلاب دون 18 عامًا'],
                'height' => 760,
                'max' => 640,
                'css' => ".course{display:flex;align-items:center;gap:11px;padding:11px 13px;border:1px solid var(--bd);border-radius:11px;cursor:pointer}\n.course:hover{border-color:var(--acc)}\n.course input{width:17px;height:17px;accent-color:var(--acc)}\n.course .cr{margin-left:auto;font-size:12px;font-weight:700;color:var(--mut)}\n.course:has(input:checked){border-color:var(--acc);background:var(--acc-soft)}\n.load{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-radius:12px;background:var(--soft);font-size:13px;font-weight:650}\n.load.over{background:var(--bad-bg);color:var(--bad)}",
                'js' => "const limit=18;\n"
                    . "const boxes=[...document.querySelectorAll('.course input')];\n"
                    . "function refresh(){\n"
                    . "  const total=boxes.filter(box=>box.checked).reduce((sum,box)=>sum+Number(box.dataset.credits),0);\n"
                    . "  document.getElementById('credits').textContent=total+' of '+limit+' credit hours';\n"
                    . "  document.querySelector('.load').classList.toggle('over',total>limit);\n"
                    . "}\n"
                    . "boxes.forEach(box=>box.addEventListener('change',refresh));\nrefresh();",
                'body' => self::wrap(
                    self::head('Enrol for the autumn term', 'Registration closes 30 September'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('sname', 'Student name', self::input('sname', 'text', 'Full legal name', 'autocomplete="name"')),
                            self::field('sid', 'Student number', self::input('sid', 'text', '2026-10-214'))
                        ),
                        self::cols(
                            2,
                            self::field('prog', 'Programme', self::select('prog', ['Computer science BSc', 'Design BA', 'Business BBA', 'Engineering BEng'])),
                            self::field('term', 'Term', self::select('term', ['Autumn 2026', 'Spring 2027', 'Summer 2027']))
                        ),
                        '<div><span class="lb">Courses</span><div style="display:grid;gap:9px">'
                        . '<label class="course"><input type="checkbox" data-credits="3" checked><span><b style="display:block;font-size:13.5px">CS 301 · Algorithms</b><span class="xs mut">Mon, Wed 09:00</span></span><span class="cr">3 cr</span></label>'
                        . '<label class="course"><input type="checkbox" data-credits="4" checked><span><b style="display:block;font-size:13.5px">CS 320 · Databases</b><span class="xs mut">Tue, Thu 11:00</span></span><span class="cr">4 cr</span></label>'
                        . '<label class="course"><input type="checkbox" data-credits="3"><span><b style="display:block;font-size:13.5px">DS 210 · Interface design</b><span class="xs mut">Mon 13:00</span></span><span class="cr">3 cr</span></label>'
                        . '<label class="course"><input type="checkbox" data-credits="4"><span><b style="display:block;font-size:13.5px">MA 205 · Linear algebra</b><span class="xs mut">Sun, Tue 08:00</span></span><span class="cr">4 cr</span></label>'
                        . '<label class="course"><input type="checkbox" data-credits="2"><span><b style="display:block;font-size:13.5px">EN 110 · Technical writing</b><span class="xs mut">Wed 15:00</span></span><span class="cr">2 cr</span></label>'
                        . '</div></div>',
                        '<div class="load"><span id="credits">0 of 18 credit hours</span><span class="xs">Overload needs the dean&rsquo;s approval</span></div>',
                        self::cols(
                            2,
                            self::field('gname', 'Guardian name <span class="mut" style="font-weight:500">(under 18)</span>', self::input('gname', 'text', 'If applicable')),
                            self::field('gphone', 'Guardian phone', self::input('gphone', 'tel', '+966 5x xxx xxxx'))
                        )
                    ),
                    self::actions('Submit enrolment', 'Save draft', 'Timetable clashes are checked on submit')
                ),
            ],
            [
                'slug' => 'course-creation-form',
                'name' => 'Course creation form',
                'name_ar' => 'نموذج إنشاء دورة',
                'tagline' => 'Course details with a reorderable lesson list.',
                'tagline_ar' => 'تفاصيل الدورة مع قائمة دروس قابلة لإعادة الترتيب.',
                'summary' => 'The authoring side of a learning platform: lessons are rows that can be added, renamed and removed, each with its own length, and the total duration in the footer updates so the course length is never a guess.',
                'summary_ar' => 'جانب التأليف في منصة تعليمية: الدروس صفوف تُضاف وتُعاد تسميتها وتُحذف، ولكل منها مدته، ويتحدّث إجمالي المدة في التذييل فلا يكون طول الدورة تخمينًا أبدًا.',
                'accent' => '#0891b2',
                'tags' => ['education', 'lms', 'authoring', 'curriculum'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Lesson rows added and removed on the fly', 'Total course length recalculated live', 'Level and audience fields for the catalogue'],
                'features_ar' => ['صفوف دروس تُضاف وتُحذف فورًا', 'إعادة حساب طول الدورة الإجمالي مباشرة', 'حقلا المستوى والجمهور للكتالوج'],
                'height' => 760,
                'max' => 640,
                'css' => ".lesson{display:grid;grid-template-columns:auto 1fr 90px auto;gap:9px;align-items:center}\n.lesson .n{width:26px;height:26px;border-radius:50%;background:var(--acc-soft);color:var(--acc);display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:700}\n.lesson .in{padding:8px 10px;font-size:13px}\n.rm{border:1px solid var(--bd);border-radius:9px;background:var(--card);padding:8px;cursor:pointer;color:var(--mut);display:inline-flex}\n.rm:hover{border-color:var(--bad);color:var(--bad)}",
                'js' => "const list=document.getElementById('lessons');\n"
                    . "function renumber(){\n"
                    . "  [...list.children].forEach((row,index)=>{\n"
                    . "    row.querySelector('.n').textContent=index+1;\n"
                    . "  });\n"
                    . "  const total=[...list.querySelectorAll('.mins')].reduce((sum,input)=>sum+(parseInt(input.value,10)||0),0);\n"
                    . "  document.getElementById('total').textContent=Math.floor(total/60)+'h '+(total%60)+'m across '+list.children.length+' lessons';\n"
                    . "}\n"
                    . "list.addEventListener('input',renumber);\n"
                    . "list.addEventListener('click',event=>{\n"
                    . "  const button=event.target.closest('.rm');\n"
                    . "  if(button&&list.children.length>1){button.closest('.lesson').remove();renumber();}\n"
                    . "});\n"
                    . "document.getElementById('addlesson').addEventListener('click',()=>{\n"
                    . "  const row=list.firstElementChild.cloneNode(true);\n"
                    . "  row.querySelectorAll('input').forEach(input=>input.value='');\n"
                    . "  list.appendChild(row);\n"
                    . "  renumber();\n"
                    . "});\nrenumber();",
                'body' => self::wrap(
                    self::head('Create a course', 'Draft · visible only to you', Kit::pill('Draft', 'warn')),
                    self::stack(
                        self::field('ctitle', 'Course title', self::input('ctitle', 'text', 'Advanced SVG animation')),
                        self::field('csummary', 'Summary', self::textarea('csummary', 'One paragraph that sells the course honestly.', 3)),
                        self::cols(
                            3,
                            self::field('clevel', 'Level', self::select('clevel', ['Beginner', 'Intermediate', 'Advanced'])),
                            self::field('clang', 'Language', self::select('clang', ['English', 'العربية', 'Français'])),
                            self::field('cprice', 'Price', self::input('cprice', 'text', '0 for free', 'inputmode="decimal"'))
                        ),
                        '<div><span class="lb">Lessons</span><div id="lessons" style="display:grid;gap:9px">'
                        . '<div class="lesson"><span class="n">1</span><input class="in" placeholder="Lesson title" aria-label="Lesson title" value="Paths, and why they matter">'
                        . '<input class="in mins" placeholder="Minutes" aria-label="Length in minutes" inputmode="numeric" value="18">'
                        . '<button class="rm" type="button" aria-label="Remove lesson">' . Kit::icon('trash', 15) . '</button></div>'
                        . '<div class="lesson"><span class="n">2</span><input class="in" placeholder="Lesson title" aria-label="Lesson title" value="Animating along a path">'
                        . '<input class="in mins" placeholder="Minutes" aria-label="Length in minutes" inputmode="numeric" value="24">'
                        . '<button class="rm" type="button" aria-label="Remove lesson">' . Kit::icon('trash', 15) . '</button></div>'
                        . '</div><button class="btn tiny" type="button" id="addlesson" style="margin-top:9px">' . Kit::icon('plus', 14) . 'Add lesson</button></div>',
                        self::field('cfor', 'Who is this for?', self::input('cfor', 'text', 'Designers comfortable with basic SVG'))
                    ),
                    '<div class="ft"><span id="total">0h 0m across 0 lessons</span><button class="btn pri" type="submit">Publish course</button></div>'
                ),
            ],
            [
                'slug' => 'property-listing-form',
                'name' => 'Property listing form',
                'name_ar' => 'نموذج إضافة عقار',
                'tagline' => 'Listing details, features and photo ordering.',
                'tagline_ar' => 'تفاصيل العقار والمزايا وترتيب الصور.',
                'summary' => 'Everything an agent types once: the type drives which fields matter, the amenities are chips because the list is long and flat, and the photo strip makes the cover image an explicit choice rather than whatever uploaded first.',
                'summary_ar' => 'كل ما يكتبه الوكيل مرة واحدة: النوع يحدد الحقول المهمة، والمرافق شارات لأن القائمة طويلة ومسطحة، وشريط الصور يجعل صورة الغلاف اختيارًا صريحًا لا أول ما رُفع.',
                'accent' => '#0f766e',
                'tags' => ['real-estate', 'listing', 'property', 'photos'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Amenity chips instead of a long checkbox list', 'Photo strip with an explicit cover image', 'Price and area together for the per-metre figure'],
                'features_ar' => ['شارات للمرافق بدل قائمة طويلة من مربعات الاختيار', 'شريط صور مع صورة غلاف محددة صراحة', 'السعر والمساحة معًا لحساب سعر المتر'],
                'height' => 780,
                'max' => 680,
                'css' => ".chips{display:flex;gap:7px;flex-wrap:wrap}\n.chips input{position:absolute;opacity:0}\n.chips span{display:inline-block;padding:7px 13px;border:1px solid var(--bd);border-radius:999px;font-size:12.5px;font-weight:600;color:var(--mut);cursor:pointer;transition:.15s}\n.chips label:hover span{border-color:var(--acc);color:var(--acc)}\n.chips input:checked+span{background:var(--acc);border-color:var(--acc);color:#fff}\n.photos{display:flex;gap:10px;flex-wrap:wrap}\n.photo{width:96px;height:72px;border-radius:11px;position:relative;overflow:hidden}\n.photo b{position:absolute;left:6px;bottom:6px;font-size:9.5px;letter-spacing:.05em;text-transform:uppercase;background:rgba(15,23,42,.72);color:#fff;padding:2px 6px;border-radius:5px}\n.addp{width:96px;height:72px;border:2px dashed var(--bd);border-radius:11px;display:flex;align-items:center;justify-content:center;color:var(--mut);cursor:pointer}\n.addp:hover{border-color:var(--acc);color:var(--acc)}",
                'body' => self::wrap(
                    self::head('New listing', 'Published listings appear within 10 minutes'),
                    self::stack(
                        self::cols(
                            2,
                            self::field('ltitle', 'Listing title', self::input('ltitle', 'text', 'Al Nakheel villa with garden')),
                            self::field('ltype', 'Property type', self::select('ltype', ['Villa', 'Apartment', 'Townhouse', 'Land', 'Office', 'Retail unit']))
                        ),
                        self::cols(
                            3,
                            self::field('lprice', 'Price', self::input('lprice', 'text', '4,200,000', 'inputmode="numeric"')),
                            self::field('larea', 'Area (m²)', self::input('larea', 'text', '420', 'inputmode="numeric"')),
                            self::field('lpurpose', 'Purpose', self::select('lpurpose', ['For sale', 'For rent', 'For investment']))
                        ),
                        self::cols(
                            3,
                            self::field('lbeds', 'Bedrooms', self::select('lbeds', ['Studio', '1', '2', '3', '4', '5', '6+'])),
                            self::field('lbaths', 'Bathrooms', self::select('lbaths', ['1', '2', '3', '4', '5', '6+'])),
                            self::field('lage', 'Age', self::select('lage', ['Off plan', 'New build', '1 - 5 years', '6 - 10 years', 'Over 10 years']))
                        ),
                        self::field('laddress', 'Address', self::iconInput('laddress', 'map-pin', 'text', 'District, street, city')),
                        self::field('ldesc', 'Description', self::textarea('ldesc', 'What makes this property worth seeing?', 4)),
                        '<div><span class="lb">Amenities</span><div class="chips">'
                        . implode('', array_map(function ($item) {
                            [$label, $on] = $item;

                            return '<label><input type="checkbox"' . ($on ? ' checked' : '') . '><span>' . $label . '</span></label>';
                        }, [['Private pool', true], ['Garden', true], ['Maid room', false], ['Driver room', false], ['Covered parking', true], ['Elevator', false], ['Central AC', true], ['Kitchen appliances', false], ['Security', true]]))
                        . '</div></div>',
                        '<div><span class="lb">Photos</span><div class="photos">'
                        . '<span class="photo" style="background:linear-gradient(140deg,#0f766e,#14b8a6)"><b>Cover</b></span>'
                        . '<span class="photo" style="background:linear-gradient(140deg,#1d4ed8,#60a5fa)"></span>'
                        . '<span class="photo" style="background:linear-gradient(140deg,#b45309,#f59e0b)"></span>'
                        . '<span class="addp">' . Kit::icon('plus', 20) . '</span>'
                        . '</div><p class="hint">Drag to reorder. The first photo is used as the cover.</p></div>'
                    ),
                    self::actions('Publish listing', 'Save draft')
                ),
            ],
            [
                'slug' => 'vehicle-registration-form',
                'name' => 'Vehicle registration form',
                'name_ar' => 'نموذج تسجيل مركبة',
                'tagline' => 'Plate, VIN, ownership and insurance details.',
                'tagline_ar' => 'اللوحة ورقم الهيكل VIN والملكية وبيانات التأمين.',
                'summary' => 'A registration form built around its two identifiers: the plate is shown in a plate-shaped field, and the VIN is monospaced and length-checked, because seventeen characters entered wrong is the single most common rejection.',
                'summary_ar' => 'نموذج تسجيل مبني حول معرّفيه: اللوحة تُعرض في حقل على شكل لوحة، وVIN بخط ثابت العرض مع فحص للطول، لأن سبعة عشر حرفًا مُدخلة خطأً هي أكثر أسباب الرفض شيوعًا.',
                'accent' => '#334155',
                'tags' => ['vehicles', 'registration', 'government', 'fleet'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['VIN field with a live 17-character check', 'Plate rendered as an actual plate', 'Insurance and inspection expiry together'],
                'features_ar' => ['حقل VIN مع فحص حيّ لطول 17 حرفًا', 'اللوحة معروضة كلوحة حقيقية', 'انتهاء التأمين والفحص معًا'],
                'height' => 720,
                'max' => 600,
                'css' => ".plate input{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700;font-size:17px;letter-spacing:.14em;text-align:center;text-transform:uppercase;border-width:2px}\n.vin input{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.08em;text-transform:uppercase}\n.vinnote{font-size:11.5px;margin-top:5px}\n.vinnote.ok{color:var(--ok)}\n.vinnote.no{color:var(--warn)}",
                'js' => "const vin=document.getElementById('vin');\n"
                    . "vin.addEventListener('input',()=>{\n"
                    . "  const clean=vin.value.toUpperCase().replace(/[^A-HJ-NPR-Z0-9]/g,'').slice(0,17);\n"
                    . "  vin.value=clean;\n"
                    . "  const note=document.getElementById('vinnote');\n"
                    . "  note.textContent=clean.length+' of 17 characters';\n"
                    . "  note.className='vinnote '+(clean.length===17?'ok':'no');\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Register a vehicle', 'Adds the vehicle to your fleet and insurance policy'),
                    self::stack(
                        self::cols(
                            2,
                            '<div class="plate">' . self::field('plate', 'Plate number', self::input('plate', 'text', 'RUH 4412')) . '</div>',
                            self::field('year', 'Model year', self::select('year', ['2026', '2025', '2024', '2023', '2022', 'Older']))
                        ),
                        self::cols(
                            2,
                            self::field('make', 'Make', self::select('make', ['Toyota', 'Hyundai', 'Ford', 'Isuzu', 'Nissan', 'Other'])),
                            self::field('model', 'Model', self::input('model', 'text', 'Hilux'))
                        ),
                        '<div class="vin"><label class="lb" for="vin">VIN</label>' . self::input('vin', 'text', '17 characters')
                        . '<p class="vinnote" id="vinnote">0 of 17 characters</p></div>',
                        self::cols(
                            3,
                            self::field('fuel', 'Fuel', self::select('fuel', ['Petrol', 'Diesel', 'Hybrid', 'Electric'])),
                            self::field('colour', 'Colour', self::input('colour', 'text', 'White')),
                            self::field('odo', 'Odometer (km)', self::input('odo', 'text', '84210', 'inputmode="numeric"'))
                        ),
                        self::cols(
                            2,
                            self::field('insurer', 'Insurer', self::select('insurer', ['Tawuniya', 'Bupa', 'MedGulf', 'Al Rajhi Takaful', 'Not insured'])),
                            self::field('insexp', 'Insurance expires', self::input('insexp', 'date'))
                        ),
                        self::field('driver', 'Assigned driver <span class="mut" style="font-weight:500">(optional)</span>', self::select('driver', ['Unassigned', 'Omar Saleh', 'Karim Nasser', 'Nour Sabbagh']))
                    ),
                    self::actions('Register vehicle', 'Cancel')
                ),
            ],
            [
                'slug' => 'shipping-label-form',
                'name' => 'Shipping label form',
                'name_ar' => 'نموذج بوليصة شحن',
                'tagline' => 'Parcel dimensions, weight, service and a rate estimate.',
                'tagline_ar' => 'أبعاد الطرد والوزن والخدمة وتقدير السعر.',
                'summary' => 'Dimensions and weight sit on one line because they are read together, and the service options show price and transit time side by side. The volumetric weight is calculated as you type, which is the figure that actually sets the price.',
                'summary_ar' => 'الأبعاد والوزن في سطر واحد لأنها تُقرأ معًا، وخيارات الخدمة تعرض السعر ومدة النقل جنبًا إلى جنب. ويُحسب الوزن الحجمي أثناء الكتابة، وهو الرقم الذي يحدد السعر فعلًا.',
                'accent' => '#7c3aed',
                'tags' => ['shipping', 'logistics', 'parcel', 'rates'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Volumetric weight calculated while typing', 'Service options compared on price and speed', 'Insurance value with a signature option'],
                'features_ar' => ['الوزن الحجمي يُحسب أثناء الكتابة', 'مقارنة خيارات الخدمة بالسعر والسرعة', 'قيمة التأمين مع خيار التوقيع عند الاستلام'],
                'height' => 740,
                'max' => 600,
                'css' => self::pickCss() . "\n.dims{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}\n.vol{display:flex;justify-content:space-between;padding:11px 13px;border-radius:11px;background:var(--soft);font-size:13px;font-weight:650;margin-top:9px}",
                'js' => "const fields=['l','w','h','kg'];\n"
                    . "function calc(){\n"
                    . "  const l=parseFloat(document.getElementById('l').value)||0;\n"
                    . "  const w=parseFloat(document.getElementById('w').value)||0;\n"
                    . "  const h=parseFloat(document.getElementById('h').value)||0;\n"
                    . "  const actual=parseFloat(document.getElementById('kg').value)||0;\n"
                    . "  const volumetric=(l*w*h)/5000;\n"
                    . "  const charged=Math.max(actual,volumetric);\n"
                    . "  document.getElementById('vol').textContent=volumetric.toFixed(2)+' kg volumetric';\n"
                    . "  document.getElementById('charged').textContent='Charged at '+charged.toFixed(2)+' kg';\n"
                    . "}\n"
                    . "fields.forEach(id=>document.getElementById(id).addEventListener('input',calc));\ncalc();",
                'body' => self::wrap(
                    self::head('Create a shipping label', 'Order #3104 · 3 items'),
                    self::stack(
                        self::field('recipient', 'Deliver to', self::input('recipient', 'text', 'Rana Khalil, Al Nakheel, Riyadh 12388')),
                        '<div><span class="lb">Parcel size (cm)</span><div class="dims">'
                        . self::input('l', 'text', 'Length', 'inputmode="decimal" aria-label="Length in cm" value="40"')
                        . self::input('w', 'text', 'Width', 'inputmode="decimal" aria-label="Width in cm" value="30"')
                        . self::input('h', 'text', 'Height', 'inputmode="decimal" aria-label="Height in cm" value="20"')
                        . '</div><div class="vol"><span id="vol">0 kg volumetric</span><span class="mut" id="charged">Charged at 0 kg</span></div></div>',
                        self::cols(
                            2,
                            self::field('kg', 'Actual weight (kg)', self::input('kg', 'text', '3.4', 'inputmode="decimal" value="3.4"')),
                            self::field('value', 'Declared value', self::input('value', 'text', '248.00', 'inputmode="decimal"'))
                        ),
                        '<div><span class="lb">Service</span><div style="display:grid;gap:10px;margin-top:2px">'
                        . self::radioCard('svc', 'svc1', 'Standard', 'Delivered in 3 - 5 working days', '$8.40', true)
                        . self::radioCard('svc', 'svc2', 'Express', 'Next working day before 17:00', '$21.00')
                        . self::radioCard('svc', 'svc3', 'Same day', 'Within the city, before 21:00', '$34.00')
                        . '</div></div>',
                        self::checkbox('sig', 'Require a signature on delivery', true),
                        self::checkbox('ins', 'Insure for the declared value (+$4.20)')
                    ),
                    self::actions('Buy label', 'Cancel', 'Label is a PDF, ready to print')
                ),
            ],
            [
                'slug' => 'tax-settings-form',
                'name' => 'Tax settings form',
                'name_ar' => 'نموذج إعدادات الضريبة',
                'tagline' => 'Tax registration, rates per region and price display.',
                'tagline_ar' => 'التسجيل الضريبي والنسب لكل منطقة وطريقة عرض الأسعار.',
                'summary' => 'The setting that quietly changes every price on a storefront: whether displayed prices include tax. It is a single radio pair here, stated in plain words, with the regional rates listed underneath as editable rows.',
                'summary_ar' => 'الإعداد الذي يغيّر بهدوء كل سعر في المتجر: هل تشمل الأسعار المعروضة الضريبة. وهو هنا زوج أزرار اختيار واحد بكلمات واضحة، وتحته النسب الإقليمية صفوفًا قابلة للتعديل.',
                'accent' => '#475569',
                'tags' => ['tax', 'settings', 'ecommerce', 'finance'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Tax-inclusive pricing stated in plain words', 'Regional rate rows with their own overrides', 'Registration number shown on invoices'],
                'features_ar' => ['شمول الأسعار للضريبة موضّح بكلمات واضحة', 'صفوف النسب الإقليمية مع استثناءاتها الخاصة', 'رقم التسجيل يظهر على الفواتير'],
                'height' => 700,
                'max' => 620,
                'css' => self::pickCss() . self::toggleCss() . "\n.rate{display:grid;grid-template-columns:1.4fr .8fr auto;gap:9px;align-items:center}\n.rate .in{padding:8px 10px;font-size:13px}\n.rm{border:1px solid var(--bd);border-radius:9px;background:var(--card);padding:8px;cursor:pointer;color:var(--mut);display:inline-flex}\n.rm:hover{border-color:var(--bad);color:var(--bad)}",
                'body' => self::wrap(
                    self::head('Tax', 'Applies to every new order from the moment you save'),
                    self::stack(
                        self::field('taxid', 'Tax registration number', self::input('taxid', 'text', '300123456700003'), 'Printed on every invoice and receipt.'),
                        '<div><span class="lb">How should prices be shown?</span><div style="display:grid;gap:10px;margin-top:2px">'
                        . self::radioCard('disp', 'incl', 'Prices include tax', 'A $100 product is charged at $100 and the tax is shown as a breakdown', '', true)
                        . self::radioCard('disp', 'excl', 'Prices exclude tax', 'A $100 product is charged at $115 with 15% added at checkout')
                        . '</div></div>',
                        '<div><span class="lb">Rates by region</span><div style="display:grid;gap:9px">'
                        . '<div class="rate"><input class="in" value="Saudi Arabia" aria-label="Region"><input class="in" value="15%" aria-label="Rate"><button class="rm" type="button" aria-label="Remove rate">' . Kit::icon('trash', 15) . '</button></div>'
                        . '<div class="rate"><input class="in" value="United Arab Emirates" aria-label="Region"><input class="in" value="5%" aria-label="Rate"><button class="rm" type="button" aria-label="Remove rate">' . Kit::icon('trash', 15) . '</button></div>'
                        . '<div class="rate"><input class="in" value="Rest of world" aria-label="Region"><input class="in" value="0%" aria-label="Rate"><button class="rm" type="button" aria-label="Remove rate">' . Kit::icon('trash', 15) . '</button></div>'
                        . '</div><button class="btn tiny" type="button" style="margin-top:9px">' . Kit::icon('plus', 14) . 'Add a region</button></div>',
                        self::toggle('digital', 'Charge tax on digital goods', 'Applies the buyer country rate to downloads', true),
                        self::toggle('shiptax', 'Charge tax on shipping', 'Some jurisdictions require this')
                    ),
                    self::actions('Save tax settings', 'Discard', 'Existing orders are never changed')
                ),
            ],
            [
                'slug' => 'cookie-consent-form',
                'name' => 'Cookie preferences form',
                'name_ar' => 'نموذج تفضيلات الكوكيز',
                'tagline' => 'Per-category consent with a locked essential row.',
                'tagline_ar' => 'موافقة لكل فئة مع صف أساسي مقفل.',
                'summary' => 'Consent that does not dark-pattern: reject-all is as prominent as accept-all, the essential row is visibly locked rather than pretending to be a choice, and each category says what it actually does.',
                'summary_ar' => 'موافقة بلا أنماط مضلِّلة: «رفض الكل» بارز بقدر «قبول الكل»، والصف الأساسي مقفل بوضوح بدل التظاهر بأنه خيار، وكل فئة تشرح ما تفعله فعلًا.',
                'accent' => '#059669',
                'tags' => ['privacy', 'cookies', 'consent', 'gdpr'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Reject-all given the same weight as accept-all', 'Essential row locked and explained', 'Cookie counts per category'],
                'features_ar' => ['«رفض الكل» بالوزن نفسه لـ«قبول الكل»', 'الصف الأساسي مقفل ومشروح', 'عدد ملفات الكوكيز لكل فئة'],
                'height' => 620,
                'max' => 560,
                'css' => self::toggleCss() . "\n.tgl .n{font-size:11px;color:var(--mut);margin-left:8px;font-weight:600}\n.locked{opacity:.65}\n.locked .sw i{background:var(--ok)}",
                'body' => self::wrap(
                    self::head('Cookie preferences', 'You can change these at any time from the footer'),
                    self::stack(
                        '<p class="sm mut">We use cookies to run the site and, with your permission, to understand how it is used. Nothing below is shared with advertisers.</p>',
                        '<div class="locked">' . self::toggle('c1', 'Strictly necessary <span class="n">4 cookies</span>', 'Sign-in, security and your language choice. These cannot be turned off.', true) . '</div>',
                        self::toggle('c2', 'Analytics <span class="n">2 cookies</span>', 'Which pages are visited and where people get stuck. Aggregated, never sold.', true),
                        self::toggle('c3', 'Preferences <span class="n">3 cookies</span>', 'Remembers your theme, editor layout and recent files.', true),
                        self::toggle('c4', 'Marketing <span class="n">0 cookies</span>', 'We do not currently use any. The switch is here so this page stays honest.')
                    ),
                    '<div class="ft" style="justify-content:flex-end;gap:8px">'
                    . '<button class="btn" type="button">Reject all</button>'
                    . '<button class="btn" type="button">Save choices</button>'
                    . '<button class="btn pri" type="button">Accept all</button></div>'
                ),
            ],
            [
                'slug' => 'accessibility-preferences-form',
                'name' => 'Accessibility preferences',
                'name_ar' => 'نموذج تفضيلات الوصول',
                'tagline' => 'Text size, contrast, motion and reading aids.',
                'tagline_ar' => 'حجم النص والتباين والحركة ومساعدات القراءة.',
                'summary' => 'Preferences that change the page as they are set: the sample paragraph above the controls takes the chosen text size and spacing immediately, so the choice is made by looking rather than by imagining.',
                'summary_ar' => 'تفضيلات تغيّر الصفحة أثناء ضبطها: الفقرة النموذجية فوق عناصر التحكم تأخذ حجم النص والتباعد المختارين فورًا، فيكون الاختيار بالنظر لا بالتخيّل.',
                'accent' => '#4f46e5',
                'tags' => ['accessibility', 'a11y', 'preferences', 'settings'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Live sample that adopts every setting', 'Reduced-motion and high-contrast switches', 'Line spacing and text size as sliders'],
                'features_ar' => ['نموذج حيّ يتبنّى كل إعداد', 'مفتاحا تقليل الحركة والتباين العالي', 'تباعد الأسطر وحجم النص عبر منزلقات'],
                'height' => 700,
                'max' => 560,
                'css' => self::toggleCss() . "\n.sample{padding:16px 20px;border-bottom:1px solid var(--bd);background:var(--soft)}\n.sample p{margin-top:6px;color:var(--mut)}\ninput[type=range]{width:100%;accent-color:var(--acc)}\n.val{font-size:12px;font-weight:700;color:var(--acc);font-variant-numeric:tabular-nums}",
                'js' => "const sample=document.getElementById('sample');\n"
                    . "const size=document.getElementById('size');\n"
                    . "const spacing=document.getElementById('spacing');\n"
                    . "function apply(){\n"
                    . "  sample.style.fontSize=size.value+'px';\n"
                    . "  sample.style.lineHeight=spacing.value;\n"
                    . "  document.getElementById('sizeval').textContent=size.value+'px';\n"
                    . "  document.getElementById('spaceval').textContent=spacing.value;\n"
                    . "}\n"
                    . "size.addEventListener('input',apply);\n"
                    . "spacing.addEventListener('input',apply);\n"
                    . "document.getElementById('contrast').addEventListener('change',event=>{\n"
                    . "  sample.style.color=event.target.checked?'#000':'';\n"
                    . "  sample.style.background=event.target.checked?'#fff':'';\n"
                    . "});\napply();",
                'body' => self::wrap(
                    self::head('Accessibility', 'These settings follow your account everywhere'),
                    '<div class="sample" id="sample"><b>The quick brown fox jumps over the lazy dog.</b>'
                    . '<p>This sample shows your current text size and line spacing. Adjust the controls below and watch it change.</p></div>',
                    self::stack(
                        '<div><div class="row" style="justify-content:space-between"><label class="lb" for="size" style="margin:0">Text size</label><span class="val" id="sizeval">15px</span></div>'
                        . '<input type="range" id="size" min="13" max="22" value="15"></div>',
                        '<div><div class="row" style="justify-content:space-between"><label class="lb" for="spacing" style="margin:0">Line spacing</label><span class="val" id="spaceval">1.6</span></div>'
                        . '<input type="range" id="spacing" min="1.2" max="2.2" step="0.1" value="1.6"></div>',
                        self::toggle('contrast', 'Higher contrast', 'Maximises the contrast between text and background'),
                        self::toggle('motion', 'Reduce motion', 'Removes animated transitions and parallax', true),
                        self::toggle('underline', 'Always underline links', 'Links are distinguishable without relying on colour', true),
                        self::toggle('focus', 'Stronger focus outline', 'Thicker outline when moving around with a keyboard'),
                        self::field('dyslexia', 'Reading font', self::select('dyslexia', ['System default', 'Serif', 'Monospaced', 'High legibility']))
                    ),
                    self::actions('Save preferences', 'Reset to defaults')
                ),
            ],
            [
                'slug' => 'theme-customiser-form',
                'name' => 'Theme customiser form',
                'name_ar' => 'نموذج تخصيص المظهر',
                'tagline' => 'Colour, radius and density with a live preview card.',
                'tagline_ar' => 'اللون والاستدارة والكثافة مع بطاقة معاينة حيّة.',
                'summary' => 'A theme editor where the preview is the point: changing the accent, the corner radius or the density repaints the sample card immediately through CSS variables, which is also exactly how a real theming system should be wired.',
                'summary_ar' => 'محرر مظهر تكون المعاينة جوهره: تغيير اللون المميز أو استدارة الزوايا أو الكثافة يعيد تلوين البطاقة النموذجية فورًا عبر متغيرات CSS، وهي بالضبط الطريقة التي ينبغي أن يُوصَل بها نظام سمات حقيقي.',
                'accent' => '#db2777',
                'tags' => ['theme', 'customiser', 'css-variables', 'preview'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Preview repainted through CSS custom properties', 'Colour, radius and density controls', 'Preset palettes as one-click swatches'],
                'features_ar' => ['المعاينة تُعاد عبر خصائص CSS المخصّصة', 'عناصر تحكم للون والاستدارة والكثافة', 'لوحات ألوان جاهزة بنقرة واحدة'],
                'height' => 720,
                'max' => 620,
                'css' => ".demo{padding:20px;border-bottom:1px solid var(--bd);background:var(--soft)}\n.democard{background:var(--card);border:1px solid var(--bd);border-radius:var(--dr,14px);padding:var(--dp,18px);box-shadow:var(--sh)}\n.democard .btn{background:var(--da,#db2777);border-color:var(--da,#db2777);color:#fff;border-radius:calc(var(--dr,14px) * .6)}\n.swatches{display:flex;gap:9px;flex-wrap:wrap}\n.swatches button{width:34px;height:34px;border-radius:50%;border:2px solid transparent;cursor:pointer;box-shadow:0 0 0 1px var(--bd)}\n.swatches button[aria-pressed=true]{border-color:var(--ink)}\ninput[type=range]{width:100%;accent-color:var(--acc)}\n.val{font-size:12px;font-weight:700;color:var(--acc);font-variant-numeric:tabular-nums}",
                'js' => "const card=document.querySelector('.democard');\n"
                    . "const radius=document.getElementById('radius');\n"
                    . "const density=document.getElementById('density');\n"
                    . "radius.addEventListener('input',()=>{\n"
                    . "  card.style.setProperty('--dr',radius.value+'px');\n"
                    . "  document.getElementById('rval').textContent=radius.value+'px';\n"
                    . "});\n"
                    . "density.addEventListener('input',()=>{\n"
                    . "  card.style.setProperty('--dp',density.value+'px');\n"
                    . "  document.getElementById('dval').textContent=density.value+'px';\n"
                    . "});\n"
                    . "document.querySelectorAll('.swatches button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.swatches button').forEach(b=>b.setAttribute('aria-pressed','false'));\n"
                    . "    button.setAttribute('aria-pressed','true');\n"
                    . "    card.style.setProperty('--da',button.dataset.colour);\n"
                    . "    document.getElementById('hex').value=button.dataset.colour;\n"
                    . "  });\n"
                    . "});\n"
                    . "document.getElementById('hex').addEventListener('input',event=>{\n"
                    . "  card.style.setProperty('--da',event.target.value);\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Customise the theme', 'Applies to your public gallery'),
                    '<div class="demo"><div class="democard">'
                    . '<div class="row" style="gap:12px">' . Kit::iconTile('image', 'var(--da,#db2777)', 40)
                    . '<span><b style="display:block">Preview card</b><span class="xs mut">This is how your components will look</span></span></div>'
                    . '<p class="sm mut" style="margin:14px 0">Every control below changes this card through CSS custom properties - the same mechanism your published theme uses.</p>'
                    . '<button class="btn" type="button">Primary action</button></div></div>',
                    self::stack(
                        '<div><span class="lb">Accent colour</span><div class="swatches">'
                        . implode('', array_map(function ($colour) {
                            $on = $colour === '#db2777' ? 'true' : 'false';

                            return '<button type="button" data-colour="' . $colour . '" aria-pressed="' . $on . '" aria-label="' . $colour . '" style="background:' . $colour . '"></button>';
                        }, ['#db2777', '#2563eb', '#059669', '#d97706', '#7c3aed', '#0891b2', '#e11d48']))
                        . '</div></div>',
                        self::cols(
                            2,
                            self::field('hex', 'Or a specific colour', '<input class="in" type="color" id="hex" value="#db2777" style="height:44px;padding:5px">'),
                            self::field('font', 'Typeface', self::select('font', ['System sans', 'Serif', 'Monospaced', 'Rounded']))
                        ),
                        '<div><div class="row" style="justify-content:space-between"><label class="lb" for="radius" style="margin:0">Corner radius</label><span class="val" id="rval">14px</span></div>'
                        . '<input type="range" id="radius" min="0" max="28" value="14"></div>',
                        '<div><div class="row" style="justify-content:space-between"><label class="lb" for="density" style="margin:0">Padding</label><span class="val" id="dval">18px</span></div>'
                        . '<input type="range" id="density" min="8" max="32" value="18"></div>'
                    ),
                    self::actions('Save theme', 'Reset', 'Changes go live immediately')
                ),
            ],
            [
                'slug' => 'file-upload-dropzone-form',
                'name' => 'File upload with progress',
                'name_ar' => 'نموذج رفع ملفات بالتقدم',
                'tagline' => 'Drag and drop with per-file progress bars and removal.',
                'tagline_ar' => 'سحب وإفلات مع شريط تقدّم لكل ملف وخيار الإزالة.',
                'summary' => 'Uploading is shown per file rather than as one bar for the batch, because one failed file out of six should not read as a failed upload. Each row can be removed while it is in flight.',
                'summary_ar' => 'يُعرض الرفع لكل ملف على حدة لا بشريط واحد للدفعة، لأن فشل ملف واحد من ستة لا ينبغي أن يبدو فشلًا للرفع كله. ويمكن إزالة كل صف أثناء رفعه.',
                'accent' => '#2563eb',
                'tags' => ['upload', 'dropzone', 'progress', 'files'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Per-file progress rather than one batch bar', 'Rows removable mid-upload', 'Drop area responds to drag-over'],
                'features_ar' => ['تقدّم لكل ملف بدل شريط واحد للدفعة', 'إزالة الصفوف أثناء الرفع', 'منطقة الإفلات تتفاعل عند السحب فوقها'],
                'height' => 680,
                'max' => 560,
                'css' => ".drop{border:2px dashed var(--bd);border-radius:16px;padding:32px 20px;text-align:center;cursor:pointer;transition:.15s}\n.drop:hover,.drop.over{border-color:var(--acc);background:var(--acc-soft)}\n.drop input{display:none}\n.files{display:grid;gap:10px}\n.file{display:grid;grid-template-columns:auto 1fr auto;gap:11px;align-items:center;padding:11px 13px;border:1px solid var(--bd);border-radius:12px}\n.file .meta{display:flex;justify-content:space-between;font-size:11.5px;color:var(--mut);margin-bottom:6px}\n.file .bar{height:6px;border-radius:999px;background:var(--soft);overflow:hidden}\n.file .bar i{display:block;height:100%;background:var(--acc);transition:width .3s}\n.file.done .bar i{background:var(--ok)}\n.rm{border:0;background:none;cursor:pointer;color:var(--mut);display:inline-flex;padding:4px}\n.rm:hover{color:var(--bad)}",
                'js' => "const drop=document.querySelector('.drop');\n"
                    . "const picker=drop.querySelector('input');\n"
                    . "drop.addEventListener('click',()=>picker.click());\n"
                    . "drop.addEventListener('dragover',event=>{event.preventDefault();drop.classList.add('over');});\n"
                    . "drop.addEventListener('dragleave',()=>drop.classList.remove('over'));\n"
                    . "drop.addEventListener('drop',event=>{event.preventDefault();drop.classList.remove('over');});\n"
                    . "document.querySelectorAll('.rm').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>button.closest('.file').remove());\n"
                    . "});\n"
                    . "document.querySelectorAll('.file:not(.done)').forEach(row=>{\n"
                    . "  const fill=row.querySelector('.bar i');\n"
                    . "  let percent=Number(row.dataset.at||0);\n"
                    . "  const tick=setInterval(()=>{\n"
                    . "    percent=Math.min(100,percent+Math.random()*9);\n"
                    . "    fill.style.width=percent+'%';\n"
                    . "    row.querySelector('.pc').textContent=Math.round(percent)+'%';\n"
                    . "    if(percent>=100){clearInterval(tick);row.classList.add('done');row.querySelector('.pc').textContent='Done';}\n"
                    . "  },600);\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Upload files', 'Up to 25 files, 100 MB each'),
                    self::stack(
                        '<div class="drop"><input type="file" multiple aria-label="Choose files">'
                        . '<span style="display:inline-flex;color:var(--acc)">' . Kit::icon('upload', 32, 1.7) . '</span>'
                        . '<div class="bold" style="margin-top:10px;font-size:15px">Drop files here</div>'
                        . '<div class="sm mut" style="margin-top:3px">or click to choose from your device</div></div>',
                        '<div class="files">'
                        . '<div class="file done">' . Kit::iconTile('image', '#0891b2', 36)
                        . '<span><span class="meta"><b>hero-render.png</b><span class="pc">Done</span></span><span class="bar"><i style="width:100%"></i></span></span>'
                        . '<button class="rm" type="button" aria-label="Remove hero-render.png">' . Kit::icon('x', 16) . '</button></div>'
                        . '<div class="file" data-at="62">' . Kit::iconTile('file', '#dc2626', 36)
                        . '<span><span class="meta"><b>brand-guide-2026.pdf</b><span class="pc">62%</span></span><span class="bar"><i style="width:62%"></i></span></span>'
                        . '<button class="rm" type="button" aria-label="Remove brand-guide-2026.pdf">' . Kit::icon('x', 16) . '</button></div>'
                        . '<div class="file" data-at="18">' . Kit::iconTile('play', '#7c3aed', 36)
                        . '<span><span class="meta"><b>launch-clip.mp4</b><span class="pc">18%</span></span><span class="bar"><i style="width:18%"></i></span></span>'
                        . '<button class="rm" type="button" aria-label="Remove launch-clip.mp4">' . Kit::icon('x', 16) . '</button></div>'
                        . '</div>'
                    ),
                    self::actions('Finish upload', 'Cancel all', '3 files · 70.7 MB')
                ),
            ],
            [
                'slug' => 'comment-reply-form',
                'name' => 'Comment reply form',
                'name_ar' => 'نموذج رد على تعليق',
                'tagline' => 'Rich text toolbar, mentions hint and a preview toggle.',
                'tagline_ar' => 'شريط أدوات للنص المنسّق وتلميح للإشارات ومفتاح للمعاينة.',
                'summary' => 'A reply box that shows what it is replying to, keeps the formatting toolbar to the six things people actually use, and switches between write and preview instead of splitting the pane - which is unusable at this width.',
                'summary_ar' => 'مربع رد يُظهر ما يرد عليه، ويقتصر شريط أدواته على الأمور الستة التي يستخدمها الناس فعلًا، ويتنقّل بين الكتابة والمعاينة بدل تقسيم اللوحة، وهو ما لا يصلح في هذا العرض.',
                'accent' => '#0891b2',
                'tags' => ['comments', 'editor', 'rich-text', 'reply'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Quoted parent comment above the box', 'Write and preview tabs rather than a split pane', 'Six-button toolbar of what gets used'],
                'features_ar' => ['التعليق الأصلي مقتبس فوق المربع', 'تبويبا الكتابة والمعاينة بدل لوحة مقسّمة', 'شريط أدوات من ستة أزرار لما يُستخدم فعلًا'],
                'height' => 620,
                'max' => 620,
                'css' => ".quote{display:flex;gap:11px;padding:14px 20px;border-bottom:1px solid var(--bd);background:var(--soft)}\n.quote .txt{font-size:13px;color:var(--mut);margin-top:4px}\n.tabs{display:flex;gap:4px;padding:10px 14px 0}\n.tabs button{border:0;background:none;padding:8px 13px;border-radius:9px 9px 0 0;font:inherit;font-size:12.5px;font-weight:650;color:var(--mut);cursor:pointer}\n.tabs button[aria-selected=true]{background:var(--soft);color:var(--ink)}\n.tools{display:flex;gap:2px;padding:8px 14px;border-bottom:1px solid var(--bd);background:var(--soft);flex-wrap:wrap}\n.tools button{border:0;background:none;padding:6px;border-radius:7px;cursor:pointer;color:var(--mut);display:inline-flex}\n.tools button:hover{background:var(--card);color:var(--acc)}\n.tools .sep{width:1px;background:var(--bd);margin:4px 5px}\n#preview{display:none;padding:14px 20px;font-size:13.5px;min-height:110px}\n#preview.on{display:block}\ntextarea.hide{display:none}",
                'js' => "const tabs=[...document.querySelectorAll('.tabs button')];\n"
                    . "const box=document.getElementById('reply');\n"
                    . "const preview=document.getElementById('preview');\n"
                    . "tabs.forEach(tab=>{\n"
                    . "  tab.addEventListener('click',()=>{\n"
                    . "    tabs.forEach(t=>t.setAttribute('aria-selected','false'));\n"
                    . "    tab.setAttribute('aria-selected','true');\n"
                    . "    const showing=tab.dataset.tab==='preview';\n"
                    . "    box.classList.toggle('hide',showing);\n"
                    . "    preview.classList.toggle('on',showing);\n"
                    . "    if(showing){\n"
                    . "      preview.textContent=box.value.trim()||'Nothing to preview yet.';\n"
                    . "    }\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    '<div class="quote">' . Kit::avatar('Maya Rahman', 36)
                    . '<span><b>Maya Rahman</b> <span class="xs mut">· 2 hours ago</span>'
                    . '<div class="txt">Could we keep the sparkline out of the compact table? At 28px rows it is more noise than signal.</div></span></div>',
                    '<div class="tabs"><button type="button" data-tab="write" aria-selected="true">Write</button>'
                    . '<button type="button" data-tab="preview" aria-selected="false">Preview</button></div>',
                    '<div class="tools">'
                    . '<button type="button" aria-label="Bold"><b style="font-size:14px">B</b></button>'
                    . '<button type="button" aria-label="Italic"><i style="font-size:14px">I</i></button>'
                    . '<span class="sep"></span>'
                    . '<button type="button" aria-label="Link">' . Kit::icon('link', 16) . '</button>'
                    . '<button type="button" aria-label="Code">' . Kit::icon('code', 16) . '</button>'
                    . '<button type="button" aria-label="Bulleted list">' . Kit::icon('list', 16) . '</button>'
                    . '<span class="sep"></span>'
                    . '<button type="button" aria-label="Attach a file">' . Kit::icon('upload', 16) . '</button></div>',
                    '<form onsubmit="return false">'
                    . '<textarea class="in" id="reply" rows="5" placeholder="Write a reply. Use @ to mention someone." style="border:0;border-radius:0;padding:14px 20px"></textarea>'
                    . '<div id="preview"></div>'
                    . '<div class="ft"><span class="xs">Markdown supported · @ to mention</span>'
                    . '<span class="row" style="gap:8px"><button class="btn" type="button">Cancel</button>'
                    . '<button class="btn pri" type="submit">' . Kit::icon('send', 15) . 'Reply</button></span></div></form>'
                ),
            ],
            [
                'slug' => 'poll-creation-form',
                'name' => 'Poll creation form',
                'name_ar' => 'نموذج إنشاء استطلاع',
                'tagline' => 'Question, answer options and closing rules.',
                'tagline_ar' => 'السؤال وخيارات الإجابة وقواعد الإغلاق.',
                'summary' => 'Options are rows you add and remove, capped at ten because a poll with fifteen answers is a survey. The settings underneath cover the three decisions every poll needs: multiple choice, anonymity and when it closes.',
                'summary_ar' => 'الخيارات صفوف تضيفها وتحذفها، بحد أقصى عشرة لأن استطلاعًا بخمس عشرة إجابة يصبح استبيانًا. والإعدادات أسفلها تغطي القرارات الثلاثة التي يحتاجها كل استطلاع: تعدد الاختيار، وإخفاء الهوية، وموعد الإغلاق.',
                'accent' => '#7c3aed',
                'tags' => ['poll', 'voting', 'options', 'survey'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Options added up to a sensible limit', 'Multiple choice and anonymity as switches', 'Closing time with a duration shortcut'],
                'features_ar' => ['إضافة الخيارات حتى حد معقول', 'تعدد الاختيار وإخفاء الهوية بمفاتيح تبديل', 'وقت الإغلاق مع اختصار للمدة'],
                'height' => 700,
                'max' => 560,
                'css' => self::toggleCss() . "\n.opt{display:grid;grid-template-columns:auto 1fr auto;gap:9px;align-items:center}\n.opt .n{width:24px;height:24px;border-radius:50%;background:var(--acc-soft);color:var(--acc);display:inline-flex;align-items:center;justify-content:center;font-size:11.5px;font-weight:700}\n.opt .in{padding:9px 11px}\n.rm{border:1px solid var(--bd);border-radius:9px;background:var(--card);padding:8px;cursor:pointer;color:var(--mut);display:inline-flex}\n.rm:hover{border-color:var(--bad);color:var(--bad)}",
                'js' => "const list=document.getElementById('opts');\n"
                    . "const add=document.getElementById('addopt');\n"
                    . "function renumber(){\n"
                    . "  [...list.children].forEach((row,index)=>row.querySelector('.n').textContent=index+1);\n"
                    . "  add.disabled=list.children.length>=10;\n"
                    . "}\n"
                    . "add.addEventListener('click',()=>{\n"
                    . "  const row=list.firstElementChild.cloneNode(true);\n"
                    . "  row.querySelector('input').value='';\n"
                    . "  list.appendChild(row);\n"
                    . "  renumber();\n"
                    . "  row.querySelector('input').focus();\n"
                    . "});\n"
                    . "list.addEventListener('click',event=>{\n"
                    . "  const button=event.target.closest('.rm');\n"
                    . "  if(button&&list.children.length>2){button.closest('.opt').remove();renumber();}\n"
                    . "});\nrenumber();",
                'body' => self::wrap(
                    self::head('Create a poll', 'Posted to #design · 42 members'),
                    self::stack(
                        self::field('question', 'Question', self::input('question', 'text', 'What should we tackle in the next sprint?')),
                        '<div><span class="lb">Options</span><div id="opts" style="display:grid;gap:9px">'
                        . '<div class="opt"><span class="n">1</span><input class="in" placeholder="Option" aria-label="Option" value="Component gallery filters">'
                        . '<button class="rm" type="button" aria-label="Remove option">' . Kit::icon('trash', 15) . '</button></div>'
                        . '<div class="opt"><span class="n">2</span><input class="in" placeholder="Option" aria-label="Option" value="Editor performance">'
                        . '<button class="rm" type="button" aria-label="Remove option">' . Kit::icon('trash', 15) . '</button></div>'
                        . '<div class="opt"><span class="n">3</span><input class="in" placeholder="Option" aria-label="Option" value="Arabic translation pass">'
                        . '<button class="rm" type="button" aria-label="Remove option">' . Kit::icon('trash', 15) . '</button></div>'
                        . '</div><button class="btn tiny" type="button" id="addopt" style="margin-top:9px">' . Kit::icon('plus', 14) . 'Add option</button></div>',
                        self::toggle('multi', 'Allow multiple answers', 'People can choose more than one option'),
                        self::toggle('anon', 'Anonymous voting', 'Results show totals but never who voted for what', true),
                        self::cols(
                            2,
                            self::field('closes', 'Closes', self::select('closes', ['In 24 hours', 'In 3 days', 'In a week', 'When I close it', 'At a specific time'])),
                            self::field('notify', 'Notify', self::select('notify', ['Everyone in the channel', 'Only people I mention', 'Nobody']))
                        )
                    ),
                    self::actions('Post poll', 'Cancel')
                ),
            ],
            [
                'slug' => 'password-change-form',
                'name' => 'Change password form',
                'name_ar' => 'نموذج تغيير كلمة المرور',
                'tagline' => 'Current and new password with a sign-out-everywhere option.',
                'tagline_ar' => 'كلمة المرور الحالية والجديدة مع خيار الخروج من كل الأجهزة.',
                'summary' => 'Changing a password is usually a response to something, so this form offers the thing people actually came for: ending every other session. The active sessions are listed underneath so the choice is informed.',
                'summary_ar' => 'تغيير كلمة المرور يأتي عادةً ردًّا على أمر ما، لذا يقدّم هذا النموذج ما جاء الناس من أجله فعلًا: إنهاء كل الجلسات الأخرى. والجلسات النشطة مسرودة أسفله ليكون القرار عن بيّنة.',
                'accent' => '#475569',
                'tags' => ['security', 'password', 'sessions', 'account'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Show-password toggle on every field', 'Active sessions listed with device and location', 'Sign out everywhere offered with the change'],
                'features_ar' => ['مفتاح إظهار كلمة المرور في كل حقل', 'الجلسات النشطة مسرودة مع الجهاز والموقع', 'الخروج من كل الأجهزة معروض مع التغيير'],
                'height' => 720,
                'max' => 540,
                'css' => self::toggleCss() . "\n.reveal{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:0;background:none;cursor:pointer;color:var(--mut);padding:6px;display:inline-flex}\n.reveal:hover{color:var(--acc)}\n.sess{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid var(--bd)}\n.sess:last-child{border-bottom:0}\n.sess .now{color:var(--ok);font-size:11px;font-weight:700}",
                'js' => "document.querySelectorAll('.reveal').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    const field=button.previousElementSibling;\n"
                    . "    const hidden=field.type==='password';\n"
                    . "    field.type=hidden?'text':'password';\n"
                    . "    button.setAttribute('aria-label',hidden?'Hide password':'Show password');\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Password and sessions', 'Last changed 8 months ago'),
                    self::stack(
                        '<div><label class="lb" for="cur">Current password</label><span style="position:relative;display:block">'
                        . self::input('cur', 'password', 'Your current password', 'autocomplete="current-password"')
                        . '<button class="reveal" type="button" aria-label="Show password">' . Kit::icon('eye', 16) . '</button></span></div>',
                        '<div><label class="lb" for="new">New password</label><span style="position:relative;display:block">'
                        . self::input('new', 'password', 'At least 10 characters', 'autocomplete="new-password"')
                        . '<button class="reveal" type="button" aria-label="Show password">' . Kit::icon('eye', 16) . '</button></span>'
                        . '<p class="hint">Use a phrase you can remember and nobody can guess.</p></div>',
                        '<div><label class="lb" for="confirm">Confirm new password</label><span style="position:relative;display:block">'
                        . self::input('confirm', 'password', 'Type it again', 'autocomplete="new-password"')
                        . '<button class="reveal" type="button" aria-label="Show password">' . Kit::icon('eye', 16) . '</button></span></div>',
                        self::toggle('signout', 'Sign out everywhere else', 'Ends the three other sessions listed below', true),
                        '<div><span class="lb">Active sessions</span>'
                        . '<div class="sess">' . Kit::iconTile('globe', '#059669', 34) . '<span style="flex:1"><b style="display:block;font-size:13px">Chrome · macOS</b><span class="xs mut">Beirut, LB · 102.44.18.7</span></span><span class="now">This device</span></div>'
                        . '<div class="sess">' . Kit::iconTile('phone', '#2563eb', 34) . '<span style="flex:1"><b style="display:block;font-size:13px">Frugal app · iOS</b><span class="xs mut">Beirut, LB · 2 hours ago</span></span></div>'
                        . '<div class="sess">' . Kit::iconTile('server', '#7c3aed', 34) . '<span style="flex:1"><b style="display:block;font-size:13px">Firefox · Windows</b><span class="xs mut">Amman, JO · 3 days ago</span></span></div>'
                        . '</div>'
                    ),
                    self::actions('Update password', 'Cancel')
                ),
            ],
        ];
    }
}
