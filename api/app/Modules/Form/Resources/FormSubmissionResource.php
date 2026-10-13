<?php

namespace App\Modules\Form\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One response, for the owner's table.
 *
 * The IP hash never leaves the server: it exists to spot a flood, not to be
 * shown to anyone. The user agent is kept, because "all of these came from
 * the same browser" is sometimes the answer to "are these real".
 */
class FormSubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'answers' => $this->data ?? [],
            'submitted_by' => $this->submitted_by,
            'locale' => $this->locale,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
        ];
    }
}
