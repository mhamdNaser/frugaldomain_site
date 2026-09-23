<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * One-off data fill: gives the known icon categories an Arabic name.
 *
 * Matches a category by slug, or failing that by its exact English name, and
 * only writes where name_ar is still null — an Arabic name an admin already
 * set is never overwritten, and running it again changes nothing.
 */
return new class extends Migration
{
    /** English name => [slug, Arabic name]. */
    private const NAMES = [
        // The library as seeded by IconSeeder (and on production).
        'Arrows & Navigation' => ['arrows-and-navigation', 'الأسهم والتنقل'],
        'Charts & Data' => ['charts-and-data', 'الرسوم البيانية والبيانات'],
        'Commerce & Finance' => ['commerce-and-finance', 'التجارة والمالية'],
        'Communication' => ['communication', 'التواصل'],
        'Devices & Technology' => ['devices-and-technology', 'الأجهزة والتقنية'],
        'Files & Documents' => ['files-and-documents', 'الملفات والمستندات'],
        'Flags & Countries' => ['flags-and-countries', 'الأعلام والدول'],
        'Interface & Layout' => ['interface-and-layout', 'الواجهة والتخطيط'],
        'Maps & Places' => ['maps-and-places', 'الخرائط والأماكن'],
        'Media & Playback' => ['media-and-playback', 'الوسائط والتشغيل'],
        'Objects & Misc' => ['objects-and-misc', 'أغراض ومتفرقات'],
        'Security & Privacy' => ['security-and-privacy', 'الأمان والخصوصية'],
        'Status & Alerts' => ['status-and-alerts', 'الحالات والتنبيهات'],
        'Text & Editing' => ['text-and-editing', 'النصوص والتحرير'],
        'Time & Calendar' => ['time-and-calendar', 'الوقت والتقويم'],
        'Users & Accounts' => ['users-and-accounts', 'المستخدمون والحسابات'],
        'Weather & Nature' => ['weather-and-nature', 'الطقس والطبيعة'],

        // Single-word categories an admin may have created on their own.
        'Arrows' => ['arrows', 'الأسهم'],
        'Navigation' => ['navigation', 'التنقل'],
        'Charts' => ['charts', 'الرسوم البيانية'],
        'Data' => ['data', 'البيانات'],
        'Commerce' => ['commerce', 'التجارة'],
        'Finance' => ['finance', 'المالية'],
        'Devices' => ['devices', 'الأجهزة'],
        'Technology' => ['technology', 'التقنية'],
        'Files' => ['files', 'الملفات'],
        'Documents' => ['documents', 'المستندات'],
        'Flags' => ['flags', 'الأعلام'],
        'Countries' => ['countries', 'الدول'],
        'Interface' => ['interface', 'الواجهة'],
        'Layout' => ['layout', 'التخطيط'],
        'Maps' => ['maps', 'الخرائط'],
        'Places' => ['places', 'الأماكن'],
        'Media' => ['media', 'الوسائط'],
        'Playback' => ['playback', 'التشغيل'],
        'Objects' => ['objects', 'أغراض'],
        'Misc' => ['misc', 'متفرقات'],
        'Security' => ['security', 'الأمان'],
        'Privacy' => ['privacy', 'الخصوصية'],
        'Status' => ['status', 'الحالات'],
        'Alerts' => ['alerts', 'التنبيهات'],
        'Text' => ['text', 'النصوص'],
        'Editing' => ['editing', 'التحرير'],
        'Time' => ['time', 'الوقت'],
        'Calendar' => ['calendar', 'التقويم'],
        'Users' => ['users', 'المستخدمون'],
        'Accounts' => ['accounts', 'الحسابات'],
        'Weather' => ['weather', 'الطقس'],
        'Nature' => ['nature', 'الطبيعة'],
    ];

    public function up(): void
    {
        foreach (self::NAMES as $name => [$slug, $arabic]) {
            DB::table('icon_categories')
                ->whereNull('name_ar')
                ->where(function ($q) use ($slug, $name) {
                    $q->where('slug', $slug)->orWhere('name', $name);
                })
                ->update(['name_ar' => $arabic]);
        }

        // The admin list and the public gallery cache category names.
        Cache::forget('icon_categories_all');
        Cache::forever('icons_cache_version', (int) Cache::get('icons_cache_version', 1) + 1);
    }

    /** Data only; the column itself is dropped by the previous migration. */
    public function down(): void
    {
    }
};
