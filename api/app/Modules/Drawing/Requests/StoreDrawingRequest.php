<?php

namespace App\Modules\Drawing\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDrawingRequest extends FormRequest
{
    /** Route middleware already requires an authenticated user. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * An upper bound on the stored document.
     *
     * The editor embeds placed images as data URIs, so a drawing with a few
     * photos in it is genuinely large - but without a ceiling, a single save
     * could push tens of megabytes into a JSON column and there would be no
     * way to serve the gallery. 8 MB is comfortably above a realistic
     * illustration and far below what would hurt.
     */
    public const MAX_DOCUMENT_BYTES = 8 * 1024 * 1024;

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],

            'document' => ['required', 'array'],
            // Mirrors the front end's own file format, so a document saved to
            // disk and one saved to the server cannot drift apart.
            'document.paths' => ['present', 'array'],
            'document.textItems' => ['present', 'array'],
            'document.width' => ['required', 'numeric', 'min:1', 'max:20000'],
            'document.height' => ['required', 'numeric', 'min:1', 'max:20000'],
            'document.background' => ['nullable', 'string', 'max:64'],

            // A PNG data URI produced by the editor's own exporter.
            'thumbnail' => ['nullable', 'string'],

            'allow_reuse' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30'],

            // The client may ask to publish straight away; that only ever
            // puts the drawing into the review queue, never live.
            'submit_for_review' => ['sometimes', 'boolean'],

            'status' => ['sometimes', Rule::in(['private'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $document = $this->input('document');
            if (! is_array($document)) {
                return;
            }

            // Measured after decoding rather than trusting Content-Length,
            // which says nothing about the field we actually store.
            $encoded = json_encode($document);
            if ($encoded === false) {
                $validator->errors()->add('document', 'That drawing could not be read.');

                return;
            }

            if (strlen($encoded) > self::MAX_DOCUMENT_BYTES) {
                $validator->errors()->add(
                    'document',
                    'That drawing is too large to save. Try reducing the number or size of placed images.'
                );
            }
        });
    }
}
