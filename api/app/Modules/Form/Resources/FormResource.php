<?php

namespace App\Modules\Form\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A form as its owner sees it: the definition, plus the two numbers the list
 * screen needs so it never has to ask the other database for them.
 */
class FormResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'title' => $this->title,
            'title_ar' => $this->title_ar,
            'description' => $this->description,
            'description_ar' => $this->description_ar,
            'status' => $this->status,
            'settings' => $this->settingsWithDefaults(),
            'submissions_count' => (int) $this->submissions_count,
            'last_submission_at' => $this->last_submission_at,
            'published_at' => $this->published_at,
            'closes_at' => $this->closes_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'is_open' => $this->isAcceptingSubmissions(),
            'closed_reason' => $this->closedReason(),
            'fields' => $this->whenLoaded('fields', fn () => $this->fields->map(fn ($field) => [
                'id' => $field->id,
                'key' => $field->key,
                'type' => $field->type,
                'label' => $field->label,
                'label_ar' => $field->label_ar,
                'placeholder' => $field->placeholder,
                'help' => $field->help,
                'help_ar' => $field->help_ar,
                'required' => (bool) $field->required,
                'unique' => (bool) $field->unique,
                'rules' => $field->rules ?? [],
                'options' => $field->options ?? [],
                'default_value' => $field->default_value,
                'position' => (int) $field->position,
            ])->values()),
        ];
    }
}
