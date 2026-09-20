<?php

namespace App\Modules\Drawing\database\seeders\Library;

use App\Modules\Drawing\database\seeders\Library\DrawKit as K;

/**
 * The Social category: posts, stories, thumbnails and channel art.
 *
 * Every template is built at the real pixel size of its platform - 1080
 * square for a post, 1080 by 1920 for a story, 1280 by 720 for a thumbnail -
 * so what leaves the editor needs no resizing and nothing important sits
 * where a platform crops.
 *
 * The type sizes are chosen for the thumbnail, not for the full-size export:
 * a headline that only works at 100% is a headline nobody reads in a feed.
 */
final class SocialTemplates
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        $out = [];

        foreach ([
            ['midnight', 'Quote post - midnight', 'Design is how it works, not how it looks.', 'STEVE, PROBABLY'],
            ['ember', 'Quote post - ember', 'Ship the small thing. Then ship the next small thing.', 'THE BUILD LOG'],
            ['forest', 'Quote post - forest', 'Simplicity is a feature you have to keep paying for.', 'FIELD NOTES'],
            ['berry', 'Quote post - berry', 'The best interface is the one you stop noticing.', 'ON CRAFT'],
        ] as [$palette, $title, $quote, $author]) {
            $out[] = self::quotePost($title, $quote, $author, $palette);
        }

        foreach ([
            ['indigo', 'Headline post - indigo', 'TWO HUNDRED COMPONENTS', 'Free, single-file, no dependencies'],
            ['coral', 'Headline post - coral', 'NEW IN THE EDITOR', 'Three hundred editable templates'],
            ['ocean', 'Headline post - ocean', 'WE REBUILT SEARCH', 'Ten times faster, half the code'],
            ['gold', 'Headline post - gold', 'WEEKEND SALE', 'Twenty percent off everything'],
        ] as [$palette, $title, $headline, $sub]) {
            $out[] = self::headlinePost($title, $headline, $sub, $palette);
        }

        foreach ([
            ['mint', 'Stat post - growth', '312%', 'growth in template downloads since June'],
            ['plum', 'Stat post - users', '12,480', 'designers now use the component library'],
            ['slate', 'Stat post - performance', '184ms', 'median response time across every endpoint'],
        ] as [$palette, $title, $figure, $caption]) {
            $out[] = self::statPost($title, $figure, $caption, $palette);
        }

        foreach ([
            ['ocean', 'Tip list post - shortcuts', '5 SHORTCUTS', ['Ctrl K opens the palette', 'V snaps back to select', 'Alt drag duplicates', 'Shift constrains the angle', 'Ctrl G groups the selection']],
            ['sand', 'Tip list post - habits', '4 HABITS', ['Name the layer immediately', 'Keep one artboard per idea', 'Export at the end, not during', 'Version before the client call']],
            ['forest', 'Tip list post - checklist', 'BEFORE YOU SHIP', ['Check both themes', 'Tab through every control', 'Read it at 320px wide', 'Print it in black and white']],
        ] as [$palette, $title, $heading, $items]) {
            $out[] = self::listPost($title, $heading, $items, $palette);
        }

        foreach ([
            ['berry', 'Announcement post - launch', 'WE ARE LIVE', 'The component gallery opens today'],
            ['ember', 'Announcement post - hiring', 'WE ARE HIRING', 'Senior front-end engineer, remote'],
            ['indigo', 'Announcement post - event', 'SAVE THE DATE', 'Frugal Conf, 14 November, Riyadh'],
        ] as [$palette, $title, $headline, $sub]) {
            $out[] = self::announcementPost($title, $headline, $sub, $palette);
        }

        foreach ([
            ['coral', 'Product post - lamp', 'AURORA DESK LAMP', '$39', 'Warm, dimmable, and it folds flat'],
            ['forest', 'Product post - chair', 'NIMBUS CHAIR', '$180', 'Eight hours comfortable, ten years old'],
            ['gold', 'Product post - offer', 'BUNDLE OFFER', '$210', 'Lamp and table, twenty percent off'],
        ] as [$palette, $title, $name, $price, $line]) {
            $out[] = self::productPost($title, $name, $price, $line, $palette);
        }

        foreach ([
            ['midnight', 'Event post - conference', 'FRUGAL CONF', '14 NOV 2026', 'RIYADH · 400 SEATS'],
            ['plum', 'Event post - workshop', 'SVG WORKSHOP', '02 OCT 2026', 'ONLINE · 90 MINUTES'],
        ] as [$palette, $title, $name, $date, $where]) {
            $out[] = self::eventPost($title, $name, $date, $where, $palette);
        }

        foreach ([
            ['ocean', 'Testimonial post - northwind', 'We replaced a design system nobody maintained with forty of these files.', 'Maya Rahman', 'Engineering lead, Northwind'],
            ['sand', 'Testimonial post - harbor', 'The Arabic support is real, and the layouts flip properly. Rarer than it should be.', 'Sara Aziz', 'Content lead, Harbor Studio'],
        ] as [$palette, $title, $quote, $name, $role]) {
            $out[] = self::testimonialPost($title, $quote, $name, $role, $palette);
        }

        foreach ([
            ['midnight', 'Carousel cover - guide', 'THE GUIDE', 'Swipe for all eight steps'],
            ['ember', 'Carousel cover - mistakes', '7 MISTAKES', 'Swipe if you build tables'],
        ] as [$palette, $title, $heading, $sub]) {
            $out[] = self::carouselCover($title, $heading, $sub, $palette);
        }

        foreach ([
            ['midnight', 'Carousel slide - numbered', '03', 'Never rely on colour alone', 'Pair every status colour with a word or an icon. A colourblind reader sees the label, and so does a black-and-white printer.'],
            ['forest', 'Carousel slide - tip', '05', 'Right-align your numbers', 'Tabular figures and right alignment make a column of numbers comparable at a glance. Left-aligned money is a puzzle.'],
        ] as [$palette, $title, $number, $heading, $body]) {
            $out[] = self::carouselSlide($title, $number, $heading, $body, $palette);
        }

        foreach ([
            ['coral', 'Story - swipe up', 'NEW DROP', 'Swipe up to see the whole collection'],
            ['indigo', 'Story - announcement', 'WE SHIPPED IT', 'Three hundred templates, live now'],
            ['mint', 'Story - tip', 'QUICK TIP', 'Alt-drag duplicates any shape'],
        ] as [$palette, $title, $heading, $sub]) {
            $out[] = self::story($title, $heading, $sub, $palette);
        }

        foreach ([
            ['plum', 'Story - quote', 'The best interface is the one you stop noticing.'],
            ['midnight', 'Story - quote dark', 'Ship the small thing. Then ship the next small thing.'],
        ] as [$palette, $title, $quote]) {
            $out[] = self::quoteStory($title, $quote, $palette);
        }

        foreach ([
            ['ember', 'Story - countdown', '02', 'DAYS TO GO', 'Doors open 14 November'],
            ['berry', 'Story - sale countdown', '12', 'HOURS LEFT', 'Twenty percent off ends at midnight'],
        ] as [$palette, $title, $number, $unit, $sub]) {
            $out[] = self::countdownStory($title, $number, $unit, $sub, $palette);
        }

        foreach ([
            ['ocean', 'Story - poll', 'WHICH ONE?', 'Tables', 'Dashboards'],
            ['gold', 'Story - this or that', 'PICK ONE', 'Light mode', 'Dark mode'],
        ] as [$palette, $title, $question, $left, $right]) {
            $out[] = self::pollStory($title, $question, $left, $right, $palette);
        }

        foreach ([
            ['forest', 'Story - product', 'NIMBUS CHAIR', '$180', 'Tap to shop'],
            ['coral', 'Story - offer', 'TODAY ONLY', '-30%', 'Tap for the code'],
        ] as [$palette, $title, $heading, $price, $cta]) {
            $out[] = self::productStory($title, $heading, $price, $cta, $palette);
        }

        foreach ([
            ['ember', 'Thumbnail - tutorial', 'BUILD A TABLE', 'IN 8 MINUTES'],
            ['indigo', 'Thumbnail - review', 'I TESTED 12', 'COMPONENT LIBRARIES'],
            ['forest', 'Thumbnail - explainer', 'HOW SVG', 'ACTUALLY WORKS'],
            ['berry', 'Thumbnail - listicle', '7 MISTAKES', 'EVERY DEV MAKES'],
        ] as [$palette, $title, $line1, $line2]) {
            $out[] = self::thumbnail($title, $line1, $line2, $palette);
        }

        foreach ([
            ['midnight', 'LinkedIn banner - studio', 'FRUGAL', 'Single-file interface templates · frugaldomain.site'],
            ['slate', 'LinkedIn banner - personal', 'LINA HADDAD', 'Head of design · writes about interfaces and type'],
        ] as [$palette, $title, $name, $line]) {
            $out[] = self::linkedinBanner($title, $name, $line, $palette);
        }

        foreach ([
            ['ocean', 'X header - product', 'TWO HUNDRED COMPONENTS', 'Free · no dependencies · no build step'],
            ['plum', 'X header - creator', 'DESIGN NOTES', 'Every other Tuesday · frugaldomain.site'],
        ] as [$palette, $title, $heading, $sub]) {
            $out[] = self::xHeader($title, $heading, $sub, $palette);
        }

        foreach ([
            ['mint', 'Facebook cover - shop', 'THE AUTUMN DROP', 'Free delivery over $120'],
            ['sand', 'Facebook cover - studio', 'MARJAN CERAMICS', 'Handmade in Riyadh since 2019'],
        ] as [$palette, $title, $heading, $sub]) {
            $out[] = self::facebookCover($title, $heading, $sub, $palette);
        }

        foreach ([
            ['midnight', 'Podcast cover - tech', 'THE FRUGAL', 'PODCAST', 'INTERFACES & CRAFT'],
            ['ember', 'Podcast cover - business', 'SMALL', 'MARGINS', 'RUNNING A STUDIO'],
            ['forest', 'Podcast cover - interview', 'FIELD', 'NOTES', 'CONVERSATIONS'],
        ] as [$palette, $title, $line1, $line2, $sub]) {
            $out[] = self::podcastCover($title, $line1, $line2, $sub, $palette);
        }

        foreach ([
            ['berry', 'Pinterest pin - guide', 'THE COMPLETE', 'TABLE GUIDE', 'frugaldomain.site'],
            ['gold', 'Pinterest pin - checklist', 'SHIP-READY', 'CHECKLIST', '12 things to check'],
        ] as [$palette, $title, $line1, $line2, $foot]) {
            $out[] = self::pin($title, $line1, $line2, $foot, $palette);
        }

        return $out;
    }

    /* ================================================================== */
    /* Square posts - 1080 x 1080                                          */
    /* ================================================================== */

    private static function quotePost(string $title, string $quote, string $author, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $lines = self::wrap($quote, 22);

        return K::make(
            $title,
            'A quote post at 1080 square. The quote mark, rule and attribution are separate shapes you can move freely.',
            1080,
            1080,
            array_merge(
                [
                    K::circle(940, 160, 280, K::filled($accent, ['opacity' => 0.16])),
                    K::circle(120, 960, 220, K::filled($second, ['opacity' => 0.12])),
                    K::rect(72, 72, 936, 936, 44, K::outlined($accent, 3, ['opacity' => 0.55])),
                    K::rect(140, 660, 180, 8, 4, K::filled($accent)),
                ],
                K::dotGrid(880, 820, 120, 120, 30, 4, $light, 0.45)
            ),
            array_merge(
                [K::text('"', 150, 320, ['fill' => $accent, 'fontSize' => 220, 'fontWeight' => 'bold', 'alignment' => 'left'])],
                self::stackedText($lines, 140, 420, 74, $ground, 'left', 'bold'),
                [
                    K::text($author, 140, 730, ['fill' => $accent, 'fontSize' => 30, 'fontWeight' => 'bold', 'alignment' => 'left']),
                    K::text('@frugaldomain', 140, 960, ['fill' => $light, 'fontSize' => 26, 'alignment' => 'left']),
                ]
            ),
            $ink,
            ['social', 'post', 'quote', $palette]
        );
    }

    private static function headlinePost(string $title, string $headline, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);
        $lines = self::wrap($headline, 14);

        return K::make(
            $title,
            'A headline post at 1080 square, with a tinted band behind the type so it holds up over any background.',
            1080,
            1080,
            [
                K::rect(0, 0, 1080, 1080, 0, array_merge(K::filled($ink), K::gradient($ink, $accent, 150))),
                K::poly('0,760 1080,620 1080,1080 0,1080', K::filled($ground, ['opacity' => 0.08])),
                K::rect(96, 96, 888, 888, 36, K::outlined($ground, 3, ['opacity' => 0.35])),
                K::rect(160, 300, 120, 12, 6, K::filled($second)),
                K::circle(910, 220, 90, K::outlined($ground, 6, ['opacity' => 0.5])),
                K::poly(K::starPoints(4, 910, 220, 44, 0.3), K::filled($second)),
            ],
            array_merge(
                self::stackedText($lines, 160, 440, 96, $ground, 'left', 'bold'),
                [
                    K::text($sub, 160, 700, ['fill' => $light, 'fontSize' => 38, 'alignment' => 'left']),
                    K::text('frugaldomain.site', 160, 930, ['fill' => $ground, 'fontSize' => 30, 'fontWeight' => 'bold', 'alignment' => 'left', 'opacity' => 0.8]),
                ]
            ),
            $ink,
            ['social', 'post', 'headline', $palette]
        );
    }

    private static function statPost(string $title, string $figure, string $caption, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'One number, set enormous, with the caption that makes it mean something. The ring behind it is editable.',
            1080,
            1080,
            [
                K::circle(540, 470, 330, K::outlined($accent, 22, ['opacity' => 0.35])),
                K::path(K::arc(540, 470, 330, -90, 160), K::outlined($accent, 22)),
                K::circle(540, 140, 18, K::filled($second)),
                K::rect(300, 800, 480, 6, 3, K::filled($light, ['opacity' => 0.5])),
            ],
            array_merge(
                [K::text($figure, 540, 520, ['fill' => $ground, 'fontSize' => 190, 'fontWeight' => 'bold'])],
                self::stackedText(self::wrap($caption, 28), 540, 900, 42, $light, 'center')
            ),
            $ink,
            ['social', 'post', 'statistic', $palette]
        );
    }

    private static function listPost(string $title, string $heading, array $items, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        $shapes = [
            K::rect(0, 0, 1080, 210, 0, K::filled($accent)),
            K::poly('0,210 1080,210 1080,270 0,240', K::filled($accent, ['opacity' => 0.4])),
        ];
        $texts = [K::text($heading, 80, 140, ['fill' => $ground, 'fontSize' => 76, 'fontWeight' => 'bold', 'alignment' => 'left'])];

        $y = 380;
        foreach ($items as $index => $item) {
            $shapes[] = K::circle(130, $y - 16, 34, K::filled($second, ['opacity' => 0.9]));
            $texts[] = K::text((string) ($index + 1), 130, $y - 4, ['fill' => $ground, 'fontSize' => 34, 'fontWeight' => 'bold']);
            $texts[] = K::text($item, 200, $y, ['fill' => $ground, 'fontSize' => 44, 'alignment' => 'left']);
            $y += 120;
        }

        $shapes[] = K::rect(80, 960, 240, 8, 4, K::filled($light, ['opacity' => 0.6]));
        $texts[] = K::text('@frugaldomain', 80, 1020, ['fill' => $light, 'fontSize' => 28, 'alignment' => 'left']);

        return K::make(
            $title,
            'A numbered list post. Each row is a disc, a number and a line of text - add or remove a row freely.',
            1080,
            1080,
            $shapes,
            $texts,
            $ink,
            ['social', 'post', 'list', $palette]
        );
    }

    private static function announcementPost(string $title, string $headline, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A centred announcement with a burst behind it. Everything is centred on 540, so retyping keeps the balance.',
            1080,
            1080,
            array_merge(
                [
                    K::poly(K::starPoints(24, 540, 540, 460, 0.88), K::filled($accent, ['opacity' => 0.16])),
                    K::circle(540, 540, 380, K::filled($accent, ['opacity' => 0.14])),
                    K::circle(540, 540, 300, K::outlined($accent, 5)),
                    K::rect(420, 690, 240, 8, 4, K::filled($second)),
                ],
                K::dotRing(540, 540, 420, 24, 7, $light, 0.5)
            ),
            array_merge(
                self::stackedText(self::wrap($headline, 12), 540, 500, 88, $ground, 'center', 'bold'),
                [K::text($sub, 540, 780, ['fill' => $light, 'fontSize' => 38])]
            ),
            $ink,
            ['social', 'post', 'announcement', $palette]
        );
    }

    private static function productPost(string $title, string $name, string $price, string $line, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A product post with a drawn stand-in for the photograph. Delete the shape, drop a photo in, clip it to the frame.',
            1080,
            1080,
            [
                K::rect(0, 0, 1080, 1080, 0, K::filled($light)),
                K::rect(90, 90, 900, 620, 40, array_merge(K::filled($accent), K::gradient($accent, $second, 140))),
                K::circle(760, 240, 120, K::filled($ground, ['opacity' => 0.18])),
                K::path(K::blob(430, 400, 190, 7, 0.16, 3), K::filled($ground, ['opacity' => 0.28])),
                K::rect(90, 620, 900, 90, 0, K::filled($ink, ['opacity' => 0.18])),
                K::circle(880, 830, 92, array_merge(K::filled($ink), K::shadow(10, 20, $ink, 0.35))),
                K::rect(90, 980, 200, 8, 4, K::filled($accent)),
            ],
            [
                K::text($name, 90, 820, ['fill' => $ink, 'fontSize' => 60, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text($line, 90, 890, ['fill' => $ink, 'fontSize' => 34, 'alignment' => 'left', 'opacity' => 0.75]),
                K::text($price, 880, 848, ['fill' => $ground, 'fontSize' => 48, 'fontWeight' => 'bold']),
                K::text('SHOP NOW', 90, 1030, ['fill' => $accent, 'fontSize' => 28, 'fontWeight' => 'bold', 'alignment' => 'left']),
            ],
            $light,
            ['social', 'post', 'product', $palette]
        );
    }

    private static function eventPost(string $title, string $name, string $date, string $where, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'An event post with a ticket-style band across the middle, notched on both sides like a real stub.',
            1080,
            1080,
            [
                K::rect(0, 0, 1080, 1080, 0, array_merge(K::filled($ink), K::gradient($ink, $second, 160))),
                K::rect(80, 380, 920, 320, 28, K::filled($ground)),
                K::circle(80, 540, 46, K::filled($ink)),
                K::circle(1000, 540, 46, K::filled($ink)),
                K::line(540, 420, 540, 660, K::outlined($ink, 4, ['opacity' => 0.25, 'strokeDasharray' => '14 16'])),
                K::poly(K::starPoints(6, 540, 200, 70, 0.42), K::filled($accent)),
                K::rect(340, 900, 400, 10, 5, K::filled($accent)),
            ],
            [
                K::text($name, 310, 530, ['fill' => $ink, 'fontSize' => 58, 'fontWeight' => 'bold']),
                K::text($where, 310, 590, ['fill' => $ink, 'fontSize' => 28, 'opacity' => 0.7]),
                K::text($date, 800, 545, ['fill' => $accent, 'fontSize' => 42, 'fontWeight' => 'bold']),
                K::text('REGISTER AT FRUGALDOMAIN.SITE', 540, 970, ['fill' => $light, 'fontSize' => 28, 'fontWeight' => 'bold']),
            ],
            $ink,
            ['social', 'post', 'event', $palette]
        );
    }

    private static function testimonialPost(string $title, string $quote, string $name, string $role, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A testimonial card on a tinted ground, with a drawn avatar disc and five stars.',
            1080,
            1080,
            array_merge(
                [
                    K::rect(0, 0, 1080, 1080, 0, K::filled($accent, ['opacity' => 0.16])),
                    K::card(90, 210, 900, 660, 44, $ground),
                    K::circle(200, 740, 60, K::filled($accent, ['opacity' => 0.2])),
                ],
                array_map(
                    fn($i) => K::poly(K::starPoints(5, 170 + $i * 62, 330, 26, 0.42), K::filled($second)),
                    range(0, 4)
                )
            ),
            array_merge(
                self::stackedText(self::wrap($quote, 30), 150, 460, 46, $ink, 'left'),
                [
                    K::text(mb_substr($name, 0, 1), 200, 758, ['fill' => $accent, 'fontSize' => 52, 'fontWeight' => 'bold']),
                    K::text($name, 290, 728, ['fill' => $ink, 'fontSize' => 36, 'fontWeight' => 'bold', 'alignment' => 'left']),
                    K::text($role, 290, 772, ['fill' => $ink, 'fontSize' => 26, 'alignment' => 'left', 'opacity' => 0.65]),
                ]
            ),
            $light,
            ['social', 'post', 'testimonial', $palette]
        );
    }

    private static function carouselCover(string $title, string $heading, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'The first slide of a carousel, with a swipe cue in the corner that tells people there is more.',
            1080,
            1080,
            array_merge(
                [
                    K::rect(0, 0, 1080, 1080, 0, array_merge(K::filled($ink), K::gradient($second, $ink, 140))),
                    K::poly(K::starPoints(3, 880, 880, 180, 0.62), K::filled($accent, ['opacity' => 0.3])),
                    K::rect(90, 90, 900, 900, 40, K::outlined($ground, 3, ['opacity' => 0.3])),
                    K::rect(150, 330, 160, 12, 6, K::filled($accent)),
                    K::poly(K::arrow(840, 960, 960, 960, 6, 26), K::filled($ground, ['opacity' => 0.85])),
                ],
                K::dotGrid(150, 760, 200, 100, 34, 5, $light, 0.4)
            ),
            array_merge(
                self::stackedText(self::wrap($heading, 11), 150, 500, 110, $ground, 'left', 'bold'),
                [
                    K::text($sub, 150, 640, ['fill' => $light, 'fontSize' => 38, 'alignment' => 'left']),
                    K::text('SWIPE', 780, 952, ['fill' => $ground, 'fontSize' => 26, 'fontWeight' => 'bold', 'opacity' => 0.85]),
                ]
            ),
            $ink,
            ['social', 'carousel', 'cover', $palette]
        );
    }

    private static function carouselSlide(string $title, string $number, string $heading, string $body, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'An inner carousel slide: big step number, heading, and body copy already wrapped to a sensible measure.',
            1080,
            1080,
            [
                K::rect(0, 0, 1080, 1080, 0, K::filled($ground)),
                K::rect(0, 0, 1080, 16, 0, K::filled($accent)),
                K::circle(160, 240, 82, K::filled($accent, ['opacity' => 0.16])),
                K::rect(100, 430, 130, 8, 4, K::filled($second)),
                K::rect(100, 980, 880, 3, 0, K::filled($ink, ['opacity' => 0.14])),
            ],
            array_merge(
                [
                    K::text($number, 160, 262, ['fill' => $accent, 'fontSize' => 76, 'fontWeight' => 'bold']),
                ],
                self::stackedText(self::wrap($heading, 20), 100, 380, 62, $ink, 'left', 'bold'),
                self::stackedText(self::wrap($body, 34), 100, 540, 42, $ink, 'left'),
                [K::text('@frugaldomain', 100, 1030, ['fill' => $ink, 'fontSize' => 26, 'alignment' => 'left', 'opacity' => 0.55])]
            ),
            $ground,
            ['social', 'carousel', 'slide', $palette]
        );
    }

    /* ================================================================== */
    /* Stories - 1080 x 1920                                               */
    /* ================================================================== */

    private static function story(string $title, string $heading, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A 9:16 story with the type kept inside the safe area, clear of the platform chrome at both ends.',
            1080,
            1920,
            array_merge(
                [
                    K::rect(0, 0, 1080, 1920, 0, array_merge(K::filled($ink), K::gradient($ink, $second, 170))),
                    K::path(K::blob(540, 620, 340, 7, 0.2, 5), K::filled($accent, ['opacity' => 0.25])),
                    K::rect(80, 250, 920, 10, 5, K::filled($accent)),
                    K::rect(80, 1560, 920, 200, 30, K::outlined($ground, 3, ['opacity' => 0.4])),
                    K::poly(K::arrow(540, 1730, 540, 1640, 7, 30), K::filled($ground)),
                ],
                K::dotRing(540, 620, 400, 18, 6, $light, 0.45)
            ),
            array_merge(
                self::stackedText(self::wrap($heading, 11), 540, 1100, 118, $ground, 'center', 'bold'),
                [
                    K::text($sub, 540, 1260, ['fill' => $light, 'fontSize' => 44]),
                    K::text('SWIPE UP', 540, 1680, ['fill' => $ground, 'fontSize' => 40, 'fontWeight' => 'bold']),
                    K::text('@frugaldomain', 540, 210, ['fill' => $light, 'fontSize' => 30, 'fontWeight' => 'bold']),
                ]
            ),
            $ink,
            ['social', 'story', 'vertical', $palette]
        );
    }

    private static function quoteStory(string $title, string $quote, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A quote story with generous margins, so the type survives being read on a phone at arm’s length.',
            1080,
            1920,
            [
                K::rect(0, 0, 1080, 1920, 0, K::filled($ink)),
                K::circle(880, 420, 300, K::filled($accent, ['opacity' => 0.2])),
                K::circle(180, 1520, 240, K::filled($second, ['opacity' => 0.18])),
                K::rect(120, 880, 160, 10, 5, K::filled($accent)),
                K::rect(120, 260, 840, 1400, 40, K::outlined($light, 2, ['opacity' => 0.3])),
            ],
            array_merge(
                [K::text('"', 150, 800, ['fill' => $accent, 'fontSize' => 260, 'fontWeight' => 'bold', 'alignment' => 'left'])],
                self::stackedText(self::wrap($quote, 18), 150, 1010, 78, $ground, 'left', 'bold'),
                [K::text('@frugaldomain', 150, 1560, ['fill' => $light, 'fontSize' => 32, 'alignment' => 'left'])]
            ),
            $ink,
            ['social', 'story', 'quote', $palette]
        );
    }

    private static function countdownStory(string $title, string $number, string $unit, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A countdown story built on a ring and one enormous figure - change the number, nothing else moves.',
            1080,
            1920,
            array_merge(
                [
                    K::rect(0, 0, 1080, 1920, 0, array_merge(K::filled($ink), K::gradient($second, $ink, 200))),
                    K::circle(540, 900, 380, K::outlined($accent, 20, ['opacity' => 0.3])),
                    K::path(K::arc(540, 900, 380, -90, 130), K::outlined($accent, 20)),
                    K::circle(540, 520, 22, K::filled($accent)),
                    K::rect(300, 1420, 480, 8, 4, K::filled($light, ['opacity' => 0.6])),
                ],
                K::dotRing(540, 900, 450, 30, 6, $light, 0.35)
            ),
            [
                K::text($number, 540, 980, ['fill' => $ground, 'fontSize' => 280, 'fontWeight' => 'bold']),
                K::text($unit, 540, 1120, ['fill' => $accent, 'fontSize' => 60, 'fontWeight' => 'bold']),
                K::text($sub, 540, 1520, ['fill' => $light, 'fontSize' => 42]),
            ],
            $ink,
            ['social', 'story', 'countdown', $palette]
        );
    }

    private static function pollStory(string $title, string $question, string $left, string $right, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A this-or-that story: two panels split down the middle, with a VS disc where they meet.',
            1080,
            1920,
            [
                K::rect(0, 0, 540, 1920, 0, K::filled($accent)),
                K::rect(540, 0, 540, 1920, 0, K::filled($second)),
                K::rect(0, 0, 1080, 260, 0, K::filled($ink, ['opacity' => 0.85])),
                K::circle(540, 960, 110, K::filled($ink)),
                K::circle(540, 960, 92, K::outlined($ground, 4, ['opacity' => 0.6])),
                K::rect(120, 1620, 840, 140, 24, K::filled($ink, ['opacity' => 0.72])),
            ],
            [
                K::text($question, 540, 165, ['fill' => $ground, 'fontSize' => 72, 'fontWeight' => 'bold']),
                K::text($left, 270, 700, ['fill' => $ground, 'fontSize' => 64, 'fontWeight' => 'bold']),
                K::text($right, 810, 700, ['fill' => $ground, 'fontSize' => 64, 'fontWeight' => 'bold']),
                K::text('VS', 540, 985, ['fill' => $ground, 'fontSize' => 58, 'fontWeight' => 'bold']),
                K::text('TAP TO VOTE', 540, 1710, ['fill' => $ground, 'fontSize' => 42, 'fontWeight' => 'bold']),
            ],
            $ink,
            ['social', 'story', 'poll', $palette]
        );
    }

    private static function productStory(string $title, string $heading, string $price, string $cta, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A product story with a drawn placeholder above and the price in a disc - clip a photo into the frame to finish it.',
            1080,
            1920,
            [
                K::rect(0, 0, 1080, 1920, 0, K::filled($light)),
                K::rect(80, 300, 920, 940, 48, array_merge(K::filled($accent), K::gradient($accent, $second, 150))),
                K::path(K::blob(540, 760, 280, 8, 0.18, 7), K::filled($ground, ['opacity' => 0.3])),
                K::circle(860, 1180, 130, array_merge(K::filled($ink), K::shadow(12, 24, $ink, 0.35))),
                K::rect(80, 1620, 920, 150, 28, K::filled($ink)),
                K::rect(80, 1420, 260, 10, 5, K::filled($accent)),
            ],
            [
                K::text($heading, 80, 1380, ['fill' => $ink, 'fontSize' => 76, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text($price, 860, 1200, ['fill' => $ground, 'fontSize' => 64, 'fontWeight' => 'bold']),
                K::text($cta, 540, 1718, ['fill' => $ground, 'fontSize' => 46, 'fontWeight' => 'bold']),
                K::text('@frugaldomain', 80, 230, ['fill' => $ink, 'fontSize' => 32, 'fontWeight' => 'bold', 'alignment' => 'left']),
            ],
            $light,
            ['social', 'story', 'product', $palette]
        );
    }

    /* ================================================================== */
    /* Covers and channel art                                              */
    /* ================================================================== */

    private static function thumbnail(string $title, string $line1, string $line2, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A 1280 by 720 video thumbnail. Two short lines, huge type, and a stripe that keeps the corner busy.',
            1280,
            720,
            [
                K::rect(0, 0, 1280, 720, 0, array_merge(K::filled($ink), K::gradient($ink, $second, 150))),
                K::poly('880,0 1280,0 1280,720 1040,720', K::filled($accent, ['opacity' => 0.9])),
                K::poly('1060,0 1280,0 1280,720 1220,720', K::filled($ground, ['opacity' => 0.12])),
                K::rect(70, 70, 1140, 580, 22, K::outlined($ground, 4, ['opacity' => 0.3])),
                K::rect(120, 250, 130, 14, 7, K::filled($accent)),
                K::poly(K::starPoints(4, 1120, 360, 90, 0.34), K::filled($ground, ['opacity' => 0.85])),
            ],
            [
                K::text($line1, 120, 400, ['fill' => $ground, 'fontSize' => 104, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text($line2, 120, 510, ['fill' => $accent, 'fontSize' => 76, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text('FRUGALDOMAIN.SITE', 120, 620, ['fill' => $light, 'fontSize' => 26, 'fontWeight' => 'bold', 'alignment' => 'left']),
            ],
            $ink,
            ['social', 'thumbnail', 'video', $palette]
        );
    }

    private static function linkedinBanner(string $title, string $name, string $line, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A 1584 by 396 profile banner. Everything sits right of centre, clear of the avatar that covers the left.',
            1584,
            396,
            array_merge(
                [
                    K::rect(0, 0, 1584, 396, 0, array_merge(K::filled($ink), K::gradient($ink, $second, 160))),
                    K::circle(210, 300, 200, K::filled($accent, ['opacity' => 0.18])),
                    K::rect(560, 150, 90, 8, 4, K::filled($accent)),
                    K::poly(K::starPoints(6, 1420, 120, 70, 0.4), K::filled($accent, ['opacity' => 0.5])),
                ],
                K::dotGrid(1200, 240, 300, 100, 34, 4, $light, 0.35)
            ),
            [
                K::text($name, 560, 220, ['fill' => $ground, 'fontSize' => 66, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text($line, 560, 280, ['fill' => $light, 'fontSize' => 30, 'alignment' => 'left']),
            ],
            $ink,
            ['social', 'banner', 'linkedin', $palette]
        );
    }

    private static function xHeader(string $title, string $heading, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A 1500 by 500 header. The avatar sits bottom left, so the type is placed right and above the fold.',
            1500,
            500,
            [
                K::rect(0, 0, 1500, 500, 0, K::filled($ink)),
                K::poly('0,500 1500,240 1500,500', K::filled($accent, ['opacity' => 0.25])),
                K::poly('0,500 1500,340 1500,500', K::filled($second, ['opacity' => 0.35])),
                K::circle(1320, 130, 76, K::outlined($accent, 6)),
                K::poly(K::starPoints(4, 1320, 130, 40, 0.3), K::filled($accent)),
                K::rect(520, 190, 100, 8, 4, K::filled($accent)),
            ],
            [
                K::text($heading, 520, 270, ['fill' => $ground, 'fontSize' => 60, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text($sub, 520, 325, ['fill' => $light, 'fontSize' => 28, 'alignment' => 'left']),
            ],
            $ink,
            ['social', 'header', 'twitter', $palette]
        );
    }

    private static function facebookCover(string $title, string $heading, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A 1640 by 664 page cover, with the important type centred so it survives the mobile crop.',
            1640,
            664,
            array_merge(
                [
                    K::rect(0, 0, 1640, 664, 0, K::filled($light)),
                    K::path(K::wave(-40, 540, 1720, 60, 3), K::outlined($accent, 90, ['opacity' => 0.35])),
                    K::path(K::wave(-40, 600, 1720, 50, 3), K::outlined($second, 70, ['opacity' => 0.4])),
                    K::rect(620, 200, 400, 8, 4, K::filled($accent)),
                ],
                K::dotRing(820, 332, 300, 24, 6, $ink, 0.18)
            ),
            [
                K::text($heading, 820, 320, ['fill' => $ink, 'fontSize' => 76, 'fontWeight' => 'bold']),
                K::text($sub, 820, 380, ['fill' => $ink, 'fontSize' => 32, 'opacity' => 0.7]),
            ],
            $light,
            ['social', 'cover', 'facebook', $palette]
        );
    }

    private static function podcastCover(string $title, string $line1, string $line2, string $sub, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A 1400 square podcast cover. It has to read at 55 pixels in a podcast app, which is why the type is this big.',
            1400,
            1400,
            [
                K::rect(0, 0, 1400, 1400, 0, array_merge(K::filled($ink), K::gradient($ink, $second, 150))),
                K::circle(1150, 250, 260, K::filled($accent, ['opacity' => 0.22])),
                K::rect(100, 100, 1200, 1200, 40, K::outlined($ground, 4, ['opacity' => 0.3])),
                K::rect(180, 500, 180, 14, 7, K::filled($accent)),
                K::path(K::wave(180, 1120, 1040, 34, 5), K::outlined($accent, 12, ['opacity' => 0.8])),
            ],
            [
                K::text($line1, 180, 700, ['fill' => $ground, 'fontSize' => 150, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text($line2, 180, 850, ['fill' => $accent, 'fontSize' => 150, 'fontWeight' => 'bold', 'alignment' => 'left']),
                K::text($sub, 180, 960, ['fill' => $light, 'fontSize' => 42, 'alignment' => 'left']),
            ],
            $ink,
            ['social', 'podcast', 'cover', $palette]
        );
    }

    private static function pin(string $title, string $line1, string $line2, string $foot, string $palette): array
    {
        [$ink, $accent, $second, $light, $ground] = K::palette($palette);

        return K::make(
            $title,
            'A 2:3 pin, the aspect ratio Pinterest actually favours, with a band for the title across the middle.',
            1000,
            1500,
            [
                K::rect(0, 0, 1000, 1500, 0, array_merge(K::filled($accent), K::gradient($accent, $second, 160))),
                K::rect(60, 60, 880, 1380, 30, K::outlined($ground, 4, ['opacity' => 0.45])),
                K::rect(60, 540, 880, 420, 0, K::filled($ground)),
                K::rect(140, 1180, 720, 120, 22, K::filled($ink)),
                K::poly(K::starPoints(5, 500, 300, 90, 0.42), K::filled($ground, ['opacity' => 0.85])),
            ],
            [
                K::text($line1, 500, 700, ['fill' => $ink, 'fontSize' => 76, 'fontWeight' => 'bold']),
                K::text($line2, 500, 800, ['fill' => $accent, 'fontSize' => 86, 'fontWeight' => 'bold']),
                K::text($foot, 500, 1254, ['fill' => $ground, 'fontSize' => 40, 'fontWeight' => 'bold']),
            ],
            $accent,
            ['social', 'pin', 'pinterest', $palette]
        );
    }

    /* ================================================================== */
    /* Text helpers                                                        */
    /* ================================================================== */

    /**
     * Wraps a string to a rough character measure.
     *
     * Text items in the editor are single lines, so a paragraph has to be
     * broken into separate items - and broken here rather than by hand, so
     * the line breaks stay even when the copy is edited.
     *
     * @return array<int,string>
     */
    private static function wrap(string $text, int $chars): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($candidate) > $chars && $current !== '') {
                $lines[] = $current;
                $current = $word;
                continue;
            }
            $current = $candidate;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /**
     * Turns wrapped lines into evenly spaced text items.
     *
     * @param array<int,string> $lines
     * @return array<int,array<string,mixed>>
     */
    private static function stackedText(array $lines, float $x, float $y, float $size, string $colour, string $align = 'left', string $weight = 'normal'): array
    {
        $items = [];
        $leading = $size * 1.16;

        foreach (array_values($lines) as $index => $line) {
            $items[] = K::text($line, $x, $y + $index * $leading, [
                'fill' => $colour,
                'fontSize' => $size,
                'fontWeight' => $weight,
                'alignment' => $align,
            ]);
        }

        return $items;
    }
}
