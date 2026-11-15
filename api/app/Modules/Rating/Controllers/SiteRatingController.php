<?php

namespace App\Modules\Rating\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rating\Models\SiteRating;
use App\Modules\Rating\Requests\StoreSiteRatingRequest;
use Illuminate\Http\Request;

class SiteRatingController extends Controller
{
    /** GET /api/ratings?path=/json-toolkit */
    public function show(Request $request)
    {
        $path = (string) $request->query('path', '');
        abort_unless(preg_match('#^/[A-Za-z0-9/_-]*$#', $path) && strlen($path) <= 255, 422, 'Invalid path.');

        return response()->json(SiteRating::summary($path));
    }

    /** POST /api/ratings - a visitor's second rating of a page replaces the first. */
    public function store(StoreSiteRatingRequest $request)
    {
        $data = $request->validated();
        $fingerprint = hash_hmac('sha256', $data['visitor'], (string) config('app.key'));
        $comment = trim((string) ($data['comment'] ?? ''));

        SiteRating::updateOrCreate(
            ['path' => $data['path'], 'fingerprint' => $fingerprint],
            [
                'stars' => $data['stars'],
                'comment' => $comment !== '' ? $comment : null,
                'locale' => $data['locale'] ?? 'en',
            ],
        );

        return response()->json(SiteRating::summary($data['path']), 201);
    }
}
