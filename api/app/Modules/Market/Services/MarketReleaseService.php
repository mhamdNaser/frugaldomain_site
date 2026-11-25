<?php

namespace App\Modules\Market\Services;

use App\Modules\Market\Models\MarketItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Keeps a GitHub-sourced item at the newest Release of its private repo.
 * The server downloads the release's .zip asset with a read-only token; buyers
 * only ever download our private copy, never the repo.
 */
class MarketReleaseService
{
    public function sync(MarketItem $item): MarketItem
    {
        if ($item->source !== 'github' || ! $item->github_repo || ! $item->github_token) {
            throw new RuntimeException('This item is not linked to a GitHub repository.');
        }

        $api = 'https://api.github.com/repos/'.$item->github_repo;
        $http = fn () => Http::withToken($item->github_token)->timeout(60)
            ->withHeaders(['X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'frugaldomain-market']);

        $release = $http()->accept('application/vnd.github+json')->get("{$api}/releases/latest");
        if ($release->status() === 404) {
            throw new RuntimeException('No published release found (or the token cannot read the repository).');
        }
        $release->throw();

        $tag = (string) $release->json('tag_name');
        $asset = collect($release->json('assets') ?? [])->first(fn ($a) => str_ends_with(strtolower($a['name'] ?? ''), '.zip'));
        if (! $asset) {
            throw new RuntimeException("Release {$tag} has no .zip asset.");
        }

        if ($tag !== $item->version || ! $item->file_path || ! Storage::disk('local')->exists($item->file_path)) {
            // The asset endpoint redirects to a signed storage URL; Guzzle drops the token on that hop.
            $zip = $http()->accept('application/octet-stream')->get("{$api}/releases/assets/{$asset['id']}")->throw()->body();
            $path = 'market/'.$item->id.'/'.preg_replace('/[^A-Za-z0-9._-]/', '_', $tag).'.zip';
            Storage::disk('local')->put($path, $zip);

            $item->forceFill([
                'file_path' => $path,
                'version' => $tag,
                'release_notes' => $release->json('body'),
                'released_at' => $release->json('published_at'),
            ]);
        }

        $item->forceFill(['checked_at' => now()])->save();

        return $item;
    }
}
