<?php

namespace App\Modules\Billing\Requests;

use App\Modules\Billing\Models\Plan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Same rules for creating (POST) and editing (PUT) a plan.
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:255',
                Rule::unique('plans', 'name')
                    ->where('billing_interval', $this->input('billing_interval', $this->route('id') ? Plan::query()->whereKey($this->route('id'))->value('billing_interval') : null))
                    ->ignore($this->route('id'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => [$this->isMethod('post') ? 'required' : 'nullable', 'numeric', 'min:0'],
            'billing_interval' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'in:month,year'],
            'features' => ['nullable', 'array', 'max:30'],
            'features.*' => ['string', 'max:200'],
            'trial_days' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'max_team_members' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
