<?php

namespace App\Modules\Drawing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Drawing\Models\DrawingTemplate;
use App\Modules\Drawing\Resources\DrawingTemplateResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Starter templates, as the editor sees them.
 *
 * Public and unauthenticated: a template is site-owned content, and making
 * someone sign in before they can start from one would defeat the point of
 * shipping them.
 */
class DrawingTemplateController extends Controller
{
    /**
     * Every active template, grouped by category.
     *
     * Returned whole rather than paginated: fifteen rows without their
     * documents is a few kilobytes, and the panel needs all the categories at
     * once to render its tabs.
     */
    public function index(Request $request)
    {
        $query = DrawingTemplate::active();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }
        if ($search = $request->input('search')) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $templates = $query->orderBy('category')->orderBy('sort_order')->get();

        return response()->json([
            'data' => DrawingTemplateResource::collection($templates),
            'categories' => $templates->pluck('category')->unique()->values(),
        ]);
    }

    /**
     * One template with its document, ready to drop on the canvas.
     *
     * The use counter is bumped here because this is the only call that
     * actually hands over the geometry - listing the panel is not "using" it.
     */
    public function show(DrawingTemplate $template)
    {
        abort_unless($template->is_active, 404);

        DrawingTemplate::whereKey($template->id)->update([
            'use_count' => DB::raw('use_count + 1'),
        ]);

        return (new DrawingTemplateResource($template))->withDocument();
    }
}
