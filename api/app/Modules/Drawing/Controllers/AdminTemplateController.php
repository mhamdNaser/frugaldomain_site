<?php

namespace App\Modules\Drawing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Drawing\Models\DrawingTemplate;
use App\Modules\Drawing\Resources\DrawingTemplateResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Managing the starter templates from the dashboard.
 *
 * Behind `role:admin` at the route level. Templates are site-owned content,
 * so unlike user drawings there is no ownership check here - the role is the
 * whole authorisation story.
 */
class AdminTemplateController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);

        $query = DrawingTemplate::query();

        // Admins need to see disabled templates too, so `active()` is not
        // applied here the way it is on the public endpoint.
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->input('search') . '%');
        }

        $templates = $query->orderBy('category')->orderBy('sort_order')->paginate($perPage);

        return DrawingTemplateResource::collection($templates);
    }

    public function show(DrawingTemplate $template)
    {
        return (new DrawingTemplateResource($template))->withDocument();
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['slug'] = DrawingTemplate::where('slug', Str::slug($data['title']))->exists()
            ? Str::slug($data['title']) . '-' . Str::random(5)
            : Str::slug($data['title']);

        $data['width'] = (int) round($data['document']['width']);
        $data['height'] = (int) round($data['document']['height']);

        $template = DrawingTemplate::create($data);

        return (new DrawingTemplateResource($template))->withDocument()
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, DrawingTemplate $template)
    {
        $data = $this->validated($request);

        $data['width'] = (int) round($data['document']['width']);
        $data['height'] = (int) round($data['document']['height']);

        $template->update($data);

        return (new DrawingTemplateResource($template->refresh()))->withDocument();
    }

    public function destroy(DrawingTemplate $template)
    {
        $template->delete();

        return response()->json(['message' => 'Template deleted.']);
    }

    /** Show or hide a template without deleting it. */
    public function toggle(DrawingTemplate $template)
    {
        $template->update(['is_active' => ! $template->is_active]);

        return new DrawingTemplateResource($template->refresh());
    }

    /**
     * Shared rules for create and update.
     *
     * The document is validated the same way a user drawing's is, because it
     * ends up on the same canvas and is read by the same code.
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'category' => ['required', 'string', 'max:60'],

            'document' => ['required', 'array'],
            'document.paths' => ['present', 'array'],
            'document.textItems' => ['present', 'array'],
            'document.width' => ['required', 'numeric', 'min:1', 'max:20000'],
            'document.height' => ['required', 'numeric', 'min:1', 'max:20000'],
            'document.background' => ['nullable', 'string', 'max:64'],

            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
            'tags' => ['sometimes', 'array', 'max:10'],
            'tags.*' => ['string', 'max:30'],
        ]);
    }
}
