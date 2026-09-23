<?php

namespace App\Modules\Account\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Account\Repositories\Interfaces\AccountRepositoryInterface;
use App\Modules\Component\Resources\ComponentResource;
use App\Modules\Icon\Resources\IconResource;
use Illuminate\Http\Request;

/**
 * What a user keeps: favourite icons, their download history and the
 * components they saved. Drawings have their own routes in the Drawing
 * module (/drawings), which the account pages use as they are.
 */
class AccountLibraryController extends Controller
{
    public function __construct(private AccountRepositoryInterface $accounts) {}

    public function favoriteIcons(Request $request)
    {
        return response()->json([
            'data' => IconResource::collection($this->accounts->favoriteIcons($request->user()))->resolve(),
        ]);
    }

    public function toggleFavoriteIcon(Request $request, int $iconId)
    {
        $favorited = $this->accounts->toggleFavoriteIcon($request->user(), $iconId);

        return response()->json([
            'favorited' => $favorited,
            'message' => $favorited ? 'Added to favorites.' : 'Removed from favorites.',
        ]);
    }

    public function downloads(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 200), 1), 500);

        $rows = $this->accounts->downloads($request->user(), $limit)->map(fn($download) => [
            'id' => $download->id,
            'type' => $download->download_type,
            'downloaded_at' => $download->downloaded_at ?? $download->created_at,
            'icon' => $download->icon ? (new IconResource($download->icon))->resolve() : null,
        ]);

        return response()->json(['data' => $rows->values()]);
    }

    public function savedComponents(Request $request)
    {
        $rows = $this->accounts->savedComponents($request->user())->map(fn($saved) => [
            'saved_at' => $saved->created_at,
            'component' => (new ComponentResource($saved->component))->resolve(),
        ]);

        return response()->json(['data' => $rows->values()]);
    }

    public function savedComponentIds(Request $request)
    {
        return response()->json(['data' => $this->accounts->savedComponentIds($request->user())]);
    }

    public function toggleSavedComponent(Request $request, int $componentId)
    {
        $saved = $this->accounts->toggleSavedComponent($request->user(), $componentId);

        return response()->json([
            'saved' => $saved,
            'message' => $saved ? 'Saved to your account.' : 'Removed from your account.',
        ]);
    }
}
