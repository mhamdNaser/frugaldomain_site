<?php

namespace App\Modules\Form\Support;

/**
 * Ready-made forms.
 *
 * A form rebuilt from a spreadsheet somebody already uses is eighteen fields
 * of typing, and every one of them is a chance to pick the wrong type and
 * collect answers that then have to be cleaned by hand. A template is the
 * same definition entered once, correctly, and editable afterwards like any
 * other form.
 *
 * `serial_label` is the heading of the running-number column in an export,
 * so a template taken from an existing sheet produces a file whose columns
 * line up with that sheet, in that order, under the same names.
 */
class FormTemplates
{
    /** The governorates, as the source sheet lists them. */
    private const GOVERNORATES = [
        'دمشق', 'ريف دمشق', 'حلب', 'حمص', 'حماة', 'اللاذقية', 'طرطوس',
        'إدلب', 'درعا', 'السويداء', 'القنيطرة', 'الرقة', 'دير الزور', 'الحسكة',
    ];

    /** The areas, as the source sheet lists them. */
    private const AREAS = [
        'الغوطة الشرقية', 'القطيفة', 'النبك و يبرود', 'التل', 'قدسيا',
        'الزبداني', 'قطنا', 'داريا', 'الكسوة و جنوب العاصمة',
    ];

    private const YES_NO = ['نعم', 'لا'];

    public static function all(): array
    {
        return [
            [
                'key' => 'family-registration',
                'title' => 'Family registration',
                'title_ar' => 'استمارة تسجيل العائلات',
                'description' => 'Household details, collected one family at a time.',
                'description_ar' => 'تُعبّأ استمارة واحدة لكل عائلة. البيانات المطلوبة أدناه كما في كشف تسجيل العائلات.',
                'fields' => count(self::familyRegistrationFields()),
            ],
            [
                'key' => 'contact',
                'title' => 'Contact form',
                'title_ar' => 'نموذج تواصل',
                'description' => 'A name, a way to reply, and a message.',
                'description_ar' => 'اسم، وطريقة للرد، ورسالة.',
                'fields' => count(self::contactFields()),
            ],
        ];
    }

    public static function exists(string $key): bool
    {
        return in_array($key, array_column(self::all(), 'key'), true);
    }

    /** The payload FormBuilder::save expects, for one template. */
    public static function payload(string $key): array
    {
        return match ($key) {
            'family-registration' => [
                'title' => 'Family registration',
                'title_ar' => 'استمارة تسجيل العائلات',
                'description' => 'To help identify the families most in need of subsidised heating fuel, '
                    . 'and to avoid registering on paper, the information below is collected through this link.',
                'description_ar' => 'حرصاً على تسهيل عملية حصر الأسر الأشد فقراً والأكثر حاجة للاستفادة من مازوت '
                    . 'التدفئة المدعوم، وتجنباً للتسجيل الورقي وتسهيل تنظيم البيانات، نضع بين أيديكم رابطاً '
                    . 'إلكترونياً لتعبئة المعلومات المطلوبة.',
                'settings' => [
                    // So the exported sheet's first column reads the way the
                    // sheet this form came from reads.
                    'serial_label' => 'تسلسل العائلة',
                    'success_message_ar' => 'تم تسجيل بيانات العائلة. شكراً لك.',
                    'allow_multiple_per_user' => true,
                ],
                'fields' => self::familyRegistrationFields(),
            ],
            'contact' => [
                'title' => 'Contact form',
                'title_ar' => 'نموذج تواصل',
                'description_ar' => 'اكتب لنا، وسنعود إليك.',
                'settings' => ['serial_label' => 'م'],
                'fields' => self::contactFields(),
            ],
            default => throw new \InvalidArgumentException('Unknown template: ' . $key),
        };
    }

    /**
     * The household sheet, column by column.
     *
     * Two columns of the original are deliberately not fields. The running
     * number is produced by the export, because a person filling the form in
     * cannot know which row they will land on. Everything else keeps the
     * source's wording, so the exported file reads exactly like the sheet it
     * replaces.
     */
    private static function familyRegistrationFields(): array
    {
        return [
            self::field('select', 'Area', 'المنطقة', required: true, options: self::AREAS),
            self::field('text', 'Administrative unit', 'الوحدة الإدارية (مدينة , بلدة , بلدية)', required: true),

            /*
             * The Syrian national id is eleven digits and it identifies the
             * household here, so it is both fixed-length and unique: the same
             * father cannot be registered twice, which is the check that stops
             * a list being padded.
             */
            self::field('digits', "Father's national ID", 'الرقم الوطني ( للأب )', required: true, unique: true, rules: ['length' => 11]),

            self::field('letters', 'First name', 'الاسم الأول', required: true),
            self::field('letters', 'Family name', 'الكنية', required: true),
            self::field("letters", "Father's name", 'اسم والده', required: true),
            self::field('letters', "Mother's name", 'اسم والدته', required: true),

            // "إن وجد" in the source: not everyone has one, so it is optional
            // and kept as digits of no fixed length.
            self::field('text', 'Family ration card number', 'رقم الطاقة العائلية (إن وجد)'),

            self::field('phone', 'Contact number', 'رقم التواصل', required: true),
            self::field('select', 'Place of birth', 'مكان الولادة', required: true, options: self::GOVERNORATES),
            /*
             * An age range rather than two fixed dates: a form left open for a
             * year would otherwise start accepting people it was written to
             * exclude. Seventeen at the youngest, ninety at the oldest - a
             * household head outside that is almost always a typing slip, and
             * a slip in a national id year is easy to make.
             */
            self::field('date', 'Date of birth', 'تاريخ الميلاد', required: true, rules: ['min_age' => 17, 'max_age' => 90]),

            // The form registers families, so a household with no children at
            // all is a form somebody has not finished filling in.
            self::field('number', 'Number of children', 'عدد الأبناء', required: true, rules: ['integer' => true, 'min' => 1, 'max' => 30]),
            self::field('number', 'Number of wives', 'عدد الزوجات', required: true, rules: ['integer' => true, 'min' => 0, 'max' => 4]),

            self::field('select', 'Home ownership', 'نوع ملكية البيت (أجار | ملك)', required: true, options: ['آجار', 'ملك']),
            self::field('select', 'Father died in the revolution', 'الأب شهيد في الثورة (نعم \\ لا )', required: true, options: self::YES_NO),
            self::field('number', 'Family members with special needs', 'عدد أفراد العائلة من ذوي الاحتياجات الخاصة', required: true, rules: ['integer' => true, 'min' => 0, 'max' => 30]),
            self::field('select', 'Has a breadwinner', 'يوجد معيل للعائلة (نعم \\ لا)', required: true, options: self::YES_NO),

            /*
             * The one open question on the form. "Additional criteria" is a
             * box nobody knows what to put in; asking the household to say why
             * they need the fuel is a question they can answer, and the answer
             * is the one thing on the sheet a person rather than a rule has to
             * weigh.
             */
            self::field(
                'textarea',
                'Tell us about your family, and why you believe you should receive the subsidised fuel',
                'اكتب شرحاً أوفى عن حالة عائلتك، ولماذا برأيك يجب أن تستحق المازوت المدعوم',
                rules: ['max_length' => 1500],
                help: 'Anything that helps whoever reads this understand your situation.',
                helpAr: 'أي شيء يساعد من يقرأ الاستمارة على فهم وضعكم.'
            ),

            /*
             * Either document, in one field. Asking for the family book and
             * the identity card as two questions would leave one of them
             * permanently empty, and a household that has only the card would
             * be unable to finish a form that demanded the book.
             */
            self::field(
                'image',
                'Family book photo, or personal ID',
                'صورة دفتر العائلة، أو الهوية الشخصية',
                required: true,
                rules: ['max_size' => 3072],
                help: 'Photograph the family book if you have one; otherwise the personal ID.',
                helpAr: 'صوّر دفتر العائلة إن توفّر، وإن لم يتوفّر فصوّر الهوية الشخصية.'
            ),
        ];
    }

    private static function contactFields(): array
    {
        return [
            self::field('text', 'Name', 'الاسم', required: true),
            self::field('email', 'Email', 'البريد الإلكتروني', required: true),
            self::field('phone', 'Phone', 'رقم الهاتف'),
            self::field('textarea', 'Message', 'الرسالة', required: true, rules: ['max_length' => 2000]),
        ];
    }

    private static function field(
        string $type,
        string $label,
        ?string $labelAr = null,
        bool $required = false,
        bool $unique = false,
        array $rules = [],
        array $options = [],
        ?string $help = null,
        ?string $helpAr = null
    ): array {
        return [
            'type' => $type,
            'label' => $label,
            'label_ar' => $labelAr,
            'help' => $help,
            'help_ar' => $helpAr,
            'required' => $required,
            'unique' => $unique,
            'rules' => $rules,
            // Plain strings become {value, label} pairs: the stored answer is
            // the Arabic word itself, which is what the export has to contain.
            'options' => array_map(fn ($option) => ['value' => $option, 'label' => $option, 'label_ar' => $option], $options),
        ];
    }
}
