<?php

namespace App\Modules\Component\Support;

use InvalidArgumentException;

/**
 * Filesystem access for components, so the path-traversal guard lives in
 * exactly one place - the same arrangement App uses.
 *
 * Paths stay relative to public/ so the web server can serve an uploaded
 * template directly.
 */
trait ComponentStorage
{
    /** Root folder (relative to public/) holding every component's file. */
    public const ROOT = 'components';

    /**
     * Validates a slug and returns its directory relative to public/.
     * Anything outside [a-z0-9-] is rejected rather than sanitised, so "..",
     * a slash or a backslash can never reach a filesystem path.
     */
    protected function componentDirectory(?string $slug): string
    {
        $slug = (string) $slug;

        if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException(
                'Invalid component slug: it may only contain lowercase letters, digits and single hyphens.'
            );
        }

        return self::ROOT . '/' . $slug;
    }
}
