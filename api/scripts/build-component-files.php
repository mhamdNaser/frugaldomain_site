<?php

/*
 * Writes every seeded component's HTML file to public/components/<slug>/.
 *
 * The seeder does this too, so running it is not required - this exists so
 * the files can be regenerated, inspected or committed without a database or
 * a Laravel boot:
 *
 *     php scripts/build-component-files.php
 *
 * The output is byte-identical to what ComponentLibrarySeeder writes, because
 * both go through the same ComponentKit renderer.
 */

$library = __DIR__ . '/../app/Modules/Component/database/seeders/Library/';

require $library . 'ComponentKit.php';
require $library . 'TableLibrary.php';
require $library . 'FormLibrary.php';
require $library . 'DashboardLibrary.php';
require $library . 'UiElementLibrary.php';

use App\Modules\Component\database\seeders\Library\ComponentKit;
use App\Modules\Component\database\seeders\Library\DashboardLibrary;
use App\Modules\Component\database\seeders\Library\FormLibrary;
use App\Modules\Component\database\seeders\Library\TableLibrary;
use App\Modules\Component\database\seeders\Library\UiElementLibrary;

$root = realpath(__DIR__ . '/../public') . DIRECTORY_SEPARATOR . 'components';
$written = 0;
$bytes = 0;

foreach ([
    'tables' => TableLibrary::class,
    'forms' => FormLibrary::class,
    'dashboards' => DashboardLibrary::class,
    'ui-elements' => UiElementLibrary::class,
] as $category => $class) {
    $definitions = $class::all();

    foreach ($definitions as $definition) {
        $slug = $definition['slug'];

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            fwrite(STDERR, "Refusing to write an invalid slug: {$slug}\n");
            exit(1);
        }

        $html = ComponentKit::page($definition);
        $directory = $root . DIRECTORY_SEPARATOR . $slug;

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            fwrite(STDERR, "Could not create {$directory}\n");
            exit(1);
        }

        file_put_contents($directory . DIRECTORY_SEPARATOR . 'template.html', $html);
        $written++;
        $bytes += strlen($html);
    }

    printf("  %-12s %d components\n", $category, count($definitions));
}

printf("\n%d component files written to public/components (%s KB)\n", $written, number_format($bytes / 1024));
