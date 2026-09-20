<?php

namespace App\Modules\Drawing\database\seeders\Library;

use App\Modules\Drawing\database\seeders\Library\DrawKit as K;

/**
 * The Badges category: bursts, seals, ribbons, stamps and price tags.
 *
 * A badge is read at a glance and usually at small size, so these are built
 * from few, large shapes with one word doing the work. Where a badge carries
 * two pieces of information - a number and a unit, a word and a date - the
 * second is deliberately much smaller, because two things shouting is one
 * thing too many.
 */
final class BadgeTemplates
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $out = [];

        foreach ([
            ['coral', '50%', 'OFF', 12, 'Sale burst - 50 percent'],
            ['ember', '30%', 'OFF', 16, 'Sale burst - 30 percent'],
            ['gold', 'BLACK', 'FRIDAY', 20, 'Sale burst - black friday'],
            ['berry', '2 FOR', '1', 10, 'Sale burst - two for one'],
            ['forest', 'FREE', 'SHIPPING', 14, 'Sale burst - free shipping'],
        ] as [$palette, $top, $bottom, $points, $title]) {
            $out[] = self::saleBurst($title, $top, $bottom, $points, $palette);
        }

        foreach ([
            ['midnight', 'NEW', 'Ribbon banner - new'],
            ['coral', 'SALE', 'Ribbon banner - sale'],
            ['forest', 'ECO', 'Ribbon banner - eco'],
            ['gold', 'PREMIUM', 'Ribbon banner - premium'],
            ['indigo', 'LIMITED', 'Ribbon banner - limited'],
        ] as [$palette, $word, $title]) {
            $out[] = self::ribbon($title, $word, $palette);
        }

        foreach ([
            ['gold', 'CERTIFIED', 'QUALITY ASSURED', 'Circular seal - certified'],
            ['forest', 'ORGANIC', '100% NATURAL', 'Circular seal - organic'],
            ['midnight', 'APPROVED', 'INSPECTED 2026', 'Circular seal - approved'],
            ['berry', 'HANDMADE', 'SMALL BATCH', 'Circular seal - handmade'],
            ['slate', 'ORIGINAL', 'AUTHENTIC GOODS', 'Circular seal - original'],
        ] as [$palette, $word, $sub, $title]) {
            $out[] = self::seal($title, $word, $sub, $palette);
        }

        foreach ([
            ['forest', 'SECURE', 'tick', 'Shield badge - secure'],
            ['indigo', 'VERIFIED', 'tick', 'Shield badge - verified'],
            ['ember', 'PROTECTED', 'lock', 'Shield badge - protected'],
            ['ocean', 'INSURED', 'lock', 'Shield badge - insured'],
        ] as [$palette, $word, $glyph, $title]) {
            $out[] = self::shieldBadge($title, $word, $glyph, $palette);
        }

        foreach ([
            ['gold', '30 DAY', 'MONEY BACK', 'Guarantee stamp - 30 day'],
            ['forest', '2 YEAR', 'WARRANTY', 'Guarantee stamp - warranty'],
            ['slate', 'LIFETIME', 'SUPPORT', 'Guarantee stamp - lifetime'],
            ['coral', 'NO RISK', 'FREE RETURNS', 'Guarantee stamp - no risk'],
        ] as [$palette, $top, $bottom, $title]) {
            $out[] = self::guarantee($title, $top, $bottom, $palette);
        }

        foreach ([
            ['coral', '-20%', 'THIS WEEK', 'Discount tag - 20 percent'],
            ['ember', '-40%', 'CLEARANCE', 'Discount tag - clearance'],
            ['berry', '-15%', 'FIRST ORDER', 'Discount tag - first order'],
            ['indigo', '-25%', 'STUDENTS', 'Discount tag - students'],
            ['mint', '-10%', 'BUNDLE', 'Discount tag - bundle'],
        ] as [$palette, $amount, $note, $title]) {
            $out[] = self::discountTag($title, $amount, $note, $palette);
        }

        foreach ([
            ['gold', 'WINNER', '2026', 'Laurel award - winner'],
            ['slate', 'TOP RATED', 'FIVE STARS', 'Laurel award - top rated'],
            ['forest', 'BEST OF', 'THE YEAR', 'Laurel award - best of'],
            ['berry', 'EDITORS', 'CHOICE', 'Laurel award - editors choice'],
        ] as [$palette, $top, $bottom, $title]) {
            $out[] = self::laurel($title, $top, $bottom, $palette);
        }

        foreach ([
            ['ocean', 'Verified tick - blue'],
            ['forest', 'Verified tick - green'],
            ['gold', 'Verified tick - gold'],
            ['indigo', 'Verified tick - indigo'],
        ] as [$palette, $title]) {
            $out[] = self::verified($title, $palette);
        }

        foreach ([
            ['coral', 'NEW', 'Corner flash - new'],
            ['ember', 'HOT', 'Corner flash - hot'],
            ['forest', 'SALE', 'Corner flash - sale'],
            ['indigo', 'BETA', 'Corner flash - beta'],
            ['gold', 'TOP', 'Corner flash - top'],
        ] as [$palette, $word, $title]) {
            $out[] = self::cornerFlash($title, $word, $palette);
        }

        foreach ([
            ['midnight', '$39', 'Price bubble - 39'],
            ['forest', '$180', 'Price bubble - 180'],
            ['coral', 'FREE', 'Price bubble - free'],
            ['gold', '$9/mo', 'Price bubble - subscription'],
            ['plum', 'SAR 420', 'Price bubble - riyal'],
        ] as [$palette, $price, $title]) {
            $out[] = self::priceBubble($title, $price, $palette);
        }

        foreach ([
            ['slate', 'LEVEL 1', 'BRONZE', 'Rank badge - bronze'],
            ['ocean', 'LEVEL 2', 'SILVER', 'Rank badge - silver'],
            ['gold', 'LEVEL 3', 'GOLD', 'Rank badge - gold'],
            ['plum', 'LEVEL 4', 'PLATINUM', 'Rank badge - platinum'],
        ] as [$palette, $level, $name, $title]) {
            $out[] = self::rank($title, $level, $name, $palette);
        }

        foreach ([
            ['ember', 'LIMITED EDITION', 'Banner strip - limited edition'],
            ['forest', 'CARBON NEUTRAL', 'Banner strip - carbon neutral'],
            ['midnight', 'MEMBERS ONLY', 'Banner strip - members only'],
        ] as [$palette, $word, $title]) {
            $out[] = self::strip($title, $word, $palette);
        }

        foreach ([
            ['coral', '24H', 'FLASH SALE', 'Timer badge - 24 hours'],
            ['indigo', '48H', 'EARLY ACCESS', 'Timer badge - 48 hours'],
            ['gold', '7D', 'FREE TRIAL', 'Timer badge - 7 days'],
        ] as [$palette, $big, $small, $title]) {
            $out[] = self::timer($title, $big, $small, $palette);
        }

        return $out;
    }

    /* ================================================================== */
    /* Builders                                                            */
    /* ================================================================== */

    /** A starburst with two lines of type - the classic offer badge. */
    private static function saleBurst(string $title, string $top, string $bottom, int $points, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $topSize = mb_strlen($top) > 4 ? 52 : 74;

        return K::make(
            $title,
            'A starburst offer badge. The burst is a single polygon, so pulling any spike reshapes it without breaking the rest.',
            420,
            420,
            [
                K::poly(K::starPoints($points, 210, 210, 196, 0.76), K::filled($second, ['opacity' => 0.55])),
                K::poly(K::starPoints($points, 210, 210, 180, 0.74), array_merge(K::filled($accent), K::gradient($accent, $second, 140))),
                K::poly(K::starPoints($points, 210, 210, 180, 0.74), K::outlined($ink, 4)),
                K::circle(210, 210, 118, K::outlined($ground, 3, ['opacity' => 0.55])),
            ],
            [
                K::text($top, 210, 210, ['fill' => $ground, 'fontSize' => $topSize, 'fontWeight' => 'bold']),
                K::text($bottom, 210, 264, ['fill' => $light, 'fontSize' => 34, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'sale', 'burst', $palette]
        );
    }

    /** A banner with folded ends. */
    private static function ribbon(string $title, string $word, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A ribbon banner drawn as three polygons - the body and two folded tails, each its own shape.',
            560,
            220,
            [
                K::poly('24,150 72,84 120,150 120,196 24,196', K::filled($second, ['opacity' => 0.85])),
                K::poly('440,150 488,84 536,150 536,196 440,196', K::filled($second, ['opacity' => 0.85])),
                K::rect(72, 62, 416, 96, 10, array_merge(K::filled($accent), K::gradient($accent, $second, 140))),
                K::rect(72, 62, 416, 96, 10, K::outlined($ink, 4)),
                K::line(96, 110, 464, 110, K::outlined($ground, 2, ['opacity' => 0.3])),
            ],
            [
                K::text($word, 280, 128, ['fill' => $ground, 'fontSize' => mb_strlen($word) > 5 ? 44 : 54, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'ribbon', 'banner', $palette]
        );
    }

    /** A double-ringed seal with a notched edge. */
    private static function seal(string $title, string $word, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A seal with a scalloped edge made from a high-point star, then two rings and a rule.',
            420,
            420,
            array_merge(
                [
                    K::poly(K::starPoints(40, 210, 210, 196, 0.93), K::filled($accent)),
                    K::circle(210, 210, 176, K::filled($ink)),
                    K::circle(210, 210, 158, K::outlined($accent, 4)),
                    K::circle(210, 210, 132, K::outlined($light, 2, ['opacity' => 0.5, 'strokeDasharray' => '5 9'])),
                    K::line(110, 210, 310, 210, K::outlined($accent, 3, ['opacity' => 0.8])),
                    K::poly(K::starPoints(5, 210, 136, 22, 0.42), K::filled($accent)),
                ],
                K::dotRing(210, 210, 176, 32, 3, $light, 0.4)
            ),
            [
                K::text($word, 210, 200, ['fill' => $ground, 'fontSize' => mb_strlen($word) > 8 ? 34 : 42, 'fontWeight' => 'bold']),
                K::text($sub, 210, 248, ['fill' => $light, 'fontSize' => 18, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'seal', 'stamp', $palette]
        );
    }

    /** A shield with a glyph above the word. */
    private static function shieldBadge(string $title, string $word, string $glyph, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $mark = $glyph === 'tick'
            ? K::path(K::tick(210, 176, 90), K::outlined($ground, 14))
            : K::flatten([
                K::rect(174, 166, 72, 56, 10, K::filled($ground)),
                K::path('M188 166 V148 A22 22 0 0 1 232 148 V166', K::outlined($ground, 10)),
            ]);

        return K::make(
            $title,
            'A shield badge with a glyph above the word. Shield, glyph and banner are all separate geometry.',
            420,
            460,
            K::flatten(
                [
                    K::path(K::shield(210, 40, 280, 330), array_merge(K::filled($accent), K::gradient($accent, $second, 160))),
                    K::path(K::shield(210, 40, 280, 330), K::outlined($ink, 6)),
                    K::path(K::shield(210, 62, 228, 280), K::outlined($ground, 2, ['opacity' => 0.4])),
                ],
                is_array($mark) && isset($mark['type']) ? [$mark] : $mark,
                [
                    K::rect(62, 348, 296, 64, 10, K::filled($ink)),
                    K::poly('62,348 62,412 30,380', K::filled($second)),
                    K::poly('358,348 358,412 390,380', K::filled($second)),
                ]
            ),
            [
                K::text($word, 210, 390, ['fill' => $ground, 'fontSize' => mb_strlen($word) > 7 ? 32 : 40, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'shield', 'trust', $palette]
        );
    }

    /** A guarantee stamp: a ring with two lines inside it. */
    private static function guarantee(string $title, string $top, string $bottom, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A guarantee stamp on a double ring, with a rule separating the promise from its term.',
            420,
            420,
            [
                K::circle(210, 210, 192, K::filled($ground)),
                K::circle(210, 210, 192, K::outlined($accent, 10)),
                K::circle(210, 210, 166, K::outlined($ink, 4, ['strokeDasharray' => '3 11'])),
                K::line(96, 216, 324, 216, K::outlined($accent, 4)),
                K::poly(K::starPoints(5, 210, 300, 26, 0.42), K::filled($accent)),
                K::path(K::arc(210, 210, 140, 200, 340), K::outlined($second, 6, ['opacity' => 0.6])),
            ],
            [
                K::text($top, 210, 196, ['fill' => $ink, 'fontSize' => mb_strlen($top) > 7 ? 40 : 52, 'fontWeight' => 'bold']),
                K::text($bottom, 210, 258, ['fill' => $accent, 'fontSize' => 28, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'guarantee', 'stamp', $palette]
        );
    }

    /** A price tag with a punched hole. */
    private static function discountTag(string $title, string $amount, string $note, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A swing tag with a punched hole and a string. The tag body is a polygon, so the point can be moved.',
            460,
            300,
            [
                K::poly('120,40 430,40 430,260 120,260 40,150', array_merge(K::filled($accent), K::gradient($accent, $second, 150))),
                K::poly('120,40 430,40 430,260 120,260 40,150', K::outlined($ink, 5)),
                K::circle(104, 150, 20, K::filled($ground)),
                K::circle(104, 150, 20, K::outlined($ink, 5)),
                K::path('M84 150 C40 120 24 96 18 62', K::outlined($ink, 5)),
                K::line(160, 206, 400, 206, K::outlined($ground, 2, ['opacity' => 0.45])),
            ],
            [
                K::text($amount, 280, 168, ['fill' => $ground, 'fontSize' => 76, 'fontWeight' => 'bold']),
                K::text($note, 280, 234, ['fill' => $light, 'fontSize' => 24, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'tag', 'discount', $palette]
        );
    }

    /** A laurel wreath around two lines - the award badge. */
    private static function laurel(string $title, string $top, string $bottom, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        // Two mirrored arcs of leaves. Each leaf is placed on a circle and
        // turned to sit tangent to it, then the whole arc is mirrored across
        // the centre line - so the wreath stays symmetrical if the radius or
        // the leaf count changes.
        $leaves = [];
        for ($i = 0; $i < 8; $i++) {
            $angle = 132 + ($i / 7) * 96;
            [$x, $y] = K::onCircle(210, 226, 152, $angle);
            $leaves[] = K::ellipse($x, $y, 27, 11, K::filled($accent, ['opacity' => 0.92, 'rotation' => $angle + 90]));
            $leaves[] = K::ellipse(420 - $x, $y, 27, 11, K::filled($accent, ['opacity' => 0.92, 'rotation' => -($angle + 90)]));
        }

        return K::make(
            $title,
            'A laurel award badge: two arcs of leaves, each an ellipse you can rotate or delete one at a time.',
            420,
            420,
            array_merge(
                [K::circle(210, 220, 186, K::filled($light, ['opacity' => 0.35]))],
                $leaves,
                [
                    K::poly(K::starPoints(5, 210, 118, 30, 0.42), K::filled($second)),
                    K::line(126, 262, 294, 262, K::outlined($accent, 3)),
                ]
            ),
            [
                K::text($top, 210, 236, ['fill' => $ink, 'fontSize' => mb_strlen($top) > 7 ? 38 : 46, 'fontWeight' => 'bold']),
                K::text($bottom, 210, 300, ['fill' => $ink, 'fontSize' => 26, 'fontWeight' => 'bold', 'opacity' => 0.75]),
            ],
            '#ffffff',
            ['badge', 'award', 'laurel', $palette]
        );
    }

    /** The scalloped tick badge every platform uses for verification. */
    private static function verified(string $title, string $palette): array
    {
        [$ink, $accent, $second, , $ground] = K::palette($palette);

        return K::make(
            $title,
            'A verification badge: a scalloped disc with a tick. The tick is an open path, so its weight can be changed.',
            320,
            320,
            [
                K::poly(K::starPoints(11, 160, 160, 138, 0.86), array_merge(K::filled($accent), K::gradient($accent, $second, 140))),
                K::poly(K::starPoints(11, 160, 160, 138, 0.86), K::outlined($ink, 3, ['opacity' => 0.35])),
                K::path(K::tick(160, 160, 110), K::outlined($ground, 18)),
            ],
            [],
            '#ffffff',
            ['badge', 'verified', 'tick', $palette]
        );
    }

    /** A triangular corner flash, for the top corner of a card. */
    private static function cornerFlash(string $title, string $word, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A corner flash sized for the top-right of a product card. The strip is rotated text on a triangle.',
            360,
            360,
            [
                K::rect(20, 20, 320, 320, 22, K::filled($light, ['opacity' => 0.5])),
                K::rect(20, 20, 320, 320, 22, K::outlined($ink, 3, ['opacity' => 0.25, 'strokeDasharray' => '8 10'])),
                K::poly('180,20 340,20 340,180', K::filled($accent)),
                K::poly('248,20 340,20 340,112', K::filled($second, ['opacity' => 0.55])),
            ],
            [
                K::text($word, 288, 86, ['fill' => $ground, 'fontSize' => 30, 'fontWeight' => 'bold', 'rotation' => 45]),
                K::text('Card corner', 180, 300, ['fill' => $ink, 'fontSize' => 22, 'opacity' => 0.55]),
            ],
            '#ffffff',
            ['badge', 'corner', 'flash', $palette]
        );
    }

    /** A price in a disc with a soft shadow. */
    private static function priceBubble(string $title, string $price, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A price bubble on a soft shadow, with a ring inside it. Drops straight onto a product image.',
            320,
            320,
            [
                K::circle(160, 160, 128, K::filled($light, ['opacity' => 0.45])),
                K::circle(160, 160, 112, array_merge(K::filled($accent), K::gradient($accent, $second, 150), K::shadow(10, 20, $ink, 0.3))),
                K::circle(160, 160, 96, K::outlined($ground, 3, ['opacity' => 0.55])),
            ],
            [
                K::text($price, 160, 176, ['fill' => $ground, 'fontSize' => mb_strlen($price) > 4 ? 40 : 54, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'price', 'bubble', $palette]
        );
    }

    /** A hexagonal rank badge with a level and a tier name. */
    private static function rank(string $title, string $level, string $name, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A hexagonal rank badge with a chevron below it - stack several to show progress through tiers.',
            360,
            420,
            [
                K::poly(K::polygonPoints(6, 180, 170, 140, 0), array_merge(K::filled($accent), K::gradient($second, $ink, 150))),
                K::poly(K::polygonPoints(6, 180, 170, 118, 0), K::outlined($ground, 4, ['opacity' => 0.8])),
                K::poly(K::starPoints(5, 180, 148, 46, 0.42), K::filled($ground)),
                K::poly('80,318 280,318 280,370 180,398 80,370', K::filled($ink)),
                K::poly('80,318 280,318 280,330 80,330', K::filled($ground, ['opacity' => 0.16])),
            ],
            [
                K::text($level, 180, 238, ['fill' => $light, 'fontSize' => 26, 'fontWeight' => 'bold']),
                K::text($name, 180, 358, ['fill' => $ground, 'fontSize' => 32, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'rank', 'tier', $palette]
        );
    }

    /** A flat strip badge - the kind that sits above a card. */
    private static function strip(string $title, string $word, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A flat strip badge with a notch at each end. Set it above a card or across the top of an image.',
            620,
            160,
            [
                K::poly('20,40 600,40 600,120 20,120', array_merge(K::filled($accent), K::gradient($accent, $second, 120))),
                K::poly('20,40 20,120 54,80', K::filled($ground)),
                K::poly('600,40 600,120 566,80', K::filled($ground)),
                K::line(70, 62, 550, 62, K::outlined($ground, 2, ['opacity' => 0.35])),
                K::line(70, 98, 550, 98, K::outlined($ink, 2, ['opacity' => 0.25])),
            ],
            [
                K::text($word, 310, 92, ['fill' => $ground, 'fontSize' => 36, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'strip', 'label', $palette]
        );
    }

    /** A timer badge - a ring, a duration and a reason. */
    private static function timer(string $title, string $big, string $small, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A countdown badge: an arc showing time left, the duration in the middle, and the offer beneath.',
            360,
            360,
            [
                K::circle(180, 170, 132, K::filled($ink)),
                K::circle(180, 170, 116, K::outlined($light, 10, ['opacity' => 0.25])),
                K::path(K::arc(180, 170, 116, -90, 140), K::outlined($accent, 10)),
                K::circle(180, 54, 11, K::filled($accent)),
                K::rect(50, 292, 260, 48, 12, K::filled($accent)),
                K::path('M180 108 V170 L222 196', K::outlined($second, 7, ['opacity' => 0.9])),
            ],
            [
                K::text($big, 180, 202, ['fill' => $ground, 'fontSize' => 58, 'fontWeight' => 'bold']),
                K::text($small, 180, 324, ['fill' => $ground, 'fontSize' => 26, 'fontWeight' => 'bold']),
            ],
            '#ffffff',
            ['badge', 'timer', 'urgency', $palette]
        );
    }
}
