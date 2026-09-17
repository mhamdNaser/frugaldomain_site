<?php

namespace App\Modules\App\Support;

use InvalidArgumentException;

/**
 * Everything that touches the filesystem for an app goes through here, so the
 * path-traversal guard lives in exactly one place.
 *
 * Paths are kept relative to public/ — the same convention Icon uses for
 * file_svg / file_png — so the web server can serve them directly.
 */
trait AppStorage
{
    /** Root folder (relative to public/) that holds every app's assets. */
    public const ROOT = 'apps';

    /**
     * Validates a slug and returns the directory it maps to, relative to
     * public/. Anything that is not strictly [a-z0-9-] is rejected rather
     * than sanitised, so a caller can never smuggle "..", a slash or a
     * backslash into a filesystem path.
     */
    protected function appDirectory(?string $slug): string
    {
        $slug = (string) $slug;

        if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException('Invalid app slug: it may only contain lowercase letters, digits and single hyphens.');
        }

        return self::ROOT . '/' . $slug;
    }
}
