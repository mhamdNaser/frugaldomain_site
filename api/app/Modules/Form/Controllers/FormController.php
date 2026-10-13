<?php

namespace App\Modules\Form\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Form\Models\Form;
use App\Modules\Form\Requests\SaveFormRequest;
use App\Modules\Form\Resources\FormResource;
use App\Modules\Form\Support\FieldTypes;
use App\Modules\Form\Support\FormBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The owner's side of a form: build it, edit it, publish it, delete it.
 *
 * Every lookup here goes through `owned()`, which filters by the caller's own
 * id and 404s otherwise. No role middleware, because there is no privilege to
 * check - any signed-in person may have forms, and nobody may touch anyone
 * else's. A missing form and someone else's form are deliberately the same
 * answer, so the endpoint cannot be used to find out which forms exist.
 */
class FormController extends Controller
{
    /** The field catalogue, so the builder offers exactly what the server accepts. */
    public function types()
    {
        return response()->json(['data' => FieldTypes::catalogue()]);
    }

    public function index(Request $request)
    {
        $forms = Form::ownedBy($request->user()->id)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . $request->input('q') . '%';
                $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('title_ar', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 20), 1), 100));

        return FormResource::collection($forms);
    }

    public function store(SaveFormRequest $request)
    {
        // Created first so it has an id, a uuid and a slug; the builder then
        // fills in everything else and attaches the fields.
        $form = Form::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'status' => Form::STATUS_DRAFT,
        ]);

        $form = FormBuilder::save($form, $request->validated());

        return (new FormResource($form))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $id)
    {
        return new FormResource($this->owned($request, $id)->load('fields'));
    }

    public function update(SaveFormRequest $request, int $id)
    {
        $form = FormBuilder::save($this->owned($request, $id), $request->validated());

        return new FormResource($form);
    }

    /**
     * Publish, close or return a form to draft without sending the whole
     * definition back - which is what the switch on the list screen wants.
     */
    public function setStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Form::STATUS_DRAFT, Form::STATUS_PUBLISHED, Form::STATUS_CLOSED])],
        ]);

        $form = $this->owned($request, $id);

        if ($data['status'] === Form::STATUS_PUBLISHED) {
            // Publishing a form with no fields would hand out a link to an
            // empty page, so it is refused here.
            if ($form->fields()->count() === 0) {
                return response()->json([
                    'message' => 'Add at least one field before publishing this form.',
                ], 422);
            }

            $form->markPublished();
        } else {
            $form->status = $data['status'];
            $form->save();
        }

        return new FormResource($form->load('fields'));
    }

    /**
     * Soft-deletes the definition.
     *
     * The answers are not deleted with it: they are other people's data and a
     * mis-click must not destroy them. They stay in the answers database,
     * reachable once the form is restored.
     */
    public function destroy(Request $request, int $id)
    {
        $this->owned($request, $id)->delete();

        return response()->json(['message' => 'Form deleted.']);
    }

    private function owned(Request $request, int $id): Form
    {
        return Form::ownedBy($request->user()->id)->whereKey($id)->firstOrFail();
    }
}
