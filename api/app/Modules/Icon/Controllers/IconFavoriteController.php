<?php

namespace App\Modules\Icon\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Icon\Models\IconDownloads;
use App\Modules\Icon\Models\IconFavorite;
use Illuminate\Http\Request;

class IconFavoriteController extends Controller
{
    public function myFavorites(Request $request)
    {
        $favorites = IconFavorite::with('icon.files', 'icon.category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    public function toggle(Request $request, int $iconId)
    {
        $userId = $request->user()->id;

        // The table soft-deletes but also has unique(user_id, icon_id), and a
        // soft-deleted row still holds its place in that index. So removing is
        // a real delete, and re-adding an icon removed before this fix brings
        // its old row back - inserting a second one failed on the index.
        $favorite = IconFavorite::withTrashed()
            ->where('user_id', $userId)
            ->where('icon_id', $iconId)
            ->first();

        if ($favorite && !$favorite->trashed()) {
            $favorite->forceDelete();

            return response()->json([
                'success' => true,
                'favorited' => false,
                'message' => 'Removed from favorites.',
            ]);
        }

        if ($favorite) {
            $favorite->restore();
        } else {
            IconFavorite::create([
                'user_id' => $userId,
                'icon_id' => $iconId,
            ]);
        }

        return response()->json([
            'success' => true,
            'favorited' => true,
            'message' => 'Added to favorites.',
        ]);
    }

    public function myDownloads(Request $request)
    {
        $downloads = IconDownloads::with('icon', 'iconFile')
            ->where('user_id', $request->user()->id)
            ->latest('downloaded_at')
            ->limit(200)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $downloads,
        ]);
    }
}
