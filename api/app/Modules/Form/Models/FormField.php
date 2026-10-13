<?php

namespace App\Modules\Form\Models;

use App\Modules\Form\Support\FieldTypes;
use Illuminate\Database\Eloquent\Model;

/**
 * One field of a form.
 *
 * `key` is frozen on creation. It is the name every answer is filed under, so
 * renaming the label of a field that has already collected answers must not
 * change it - otherwise an export would split one question across two columns.
 */
class FormField extends Model
{
    protected $fillable = [
        'form_id',
        'key',
        'type',
        'label',
        'label_ar',
        'placeholder',
        'help',
        'help_ar',
        'required',
        'unique',
        'rules',
        'options',
        'default_value',
        'position',
    ];

    protected $casts = [
        'required' => 'boolean',
        'unique' => 'boolean',
        'rules' => 'array',
        'options' => 'array',
        'position' => 'integer',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    /** The definition as FieldTypes expects it. */
    public function definition(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'required' => (bool) $this->required,
            'unique' => (bool) $this->unique,
            'rules' => $this->rules ?? [],
            'options' => $this->options ?? [],
        ];
    }

    public function validationRules(): array
    {
        return FieldTypes::rulesFor($this->definition());
    }

    public function itemValidationRules(): ?array
    {
        return FieldTypes::itemRulesFor($this->definition());
    }

    /**
     * What the public form page needs to render this field - and nothing else.
     *
     * Deliberately not the whole row: a visitor has no business knowing the
     * internal id or when the owner last edited the field.
     */
    public function toPublicSchema(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'label_ar' => $this->label_ar,
            'placeholder' => $this->placeholder,
            'help' => $this->help,
            'help_ar' => $this->help_ar,
            'required' => (bool) $this->required,
            'unique' => (bool) $this->unique,
            'rules' => $this->rules ?? [],
            'options' => $this->options ?? [],
            'default_value' => $this->default_value,
        ];
    }
}
