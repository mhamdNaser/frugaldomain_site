<?php

namespace App\Modules\Component\database\seeders\Library;

use App\Modules\Component\database\seeders\Library\ComponentKit as Kit;

/**
 * The Tables category: data tables, grids and list views.
 *
 * Every entry is a whole, believable screen rather than a bare `<table>` -
 * a toolbar, real sample rows and a footer - because that is what someone
 * lifting a template actually needs, and it is what makes the preview thumb
 * legible at gallery size.
 *
 * The private helpers below are the reason fifty tables fit in one readable
 * file: each definition spends its lines on what makes it different, not on
 * re-declaring a card, a header and a pager.
 */
final class TableLibrary
{
    /** @return array<int,array<string,mixed>> */
    public static function all(): array
    {
        return array_merge(self::setOne(), self::setTwo(), self::setThree());
    }

    /* ================================================================== */
    /* Shared pieces                                                       */
    /* ================================================================== */

    /** Card wrapper: every table in the category sits in one of these. */
    private static function wrap(string ...$parts): string
    {
        return '<div class="wrap"><div class="card">' . implode('', $parts) . '</div></div>';
    }

    /** Card header: title, optional caption, optional toolbar on the right. */
    private static function head(string $title, string $sub = '', string $tools = ''): string
    {
        $caption = $sub === '' ? '' : '<p class="sub">' . $sub . '</p>';

        return '<div class="hd"><div><h2>' . $title . '</h2>' . $caption . '</div>'
            . ($tools === '' ? '' : '<div class="row" style="gap:8px;flex-wrap:wrap">' . $tools . '</div>')
            . '</div>';
    }

    /** A search box with the magnifier tucked inside it. */
    private static function search(string $placeholder = 'Search', string $id = 'q'): string
    {
        return '<span style="position:relative;display:inline-flex;align-items:center">'
            . '<span style="position:absolute;left:11px;color:var(--faint);pointer-events:none">' . Kit::icon('search', 15) . '</span>'
            . '<input id="' . $id . '" class="in" type="search" placeholder="' . $placeholder . '" aria-label="' . $placeholder . '" style="padding-left:33px;width:210px">'
            . '</span>';
    }

    /** A button with a leading icon. */
    private static function btn(string $label, string $icon = '', string $kind = ''): string
    {
        $glyph = $icon === '' ? '' : Kit::icon($icon, 15);

        return '<button class="btn ' . $kind . '" type="button">' . $glyph . $label . '</button>';
    }

    /** An icon-only button, for row actions and toolbars. */
    private static function iconBtn(string $icon, string $label): string
    {
        return '<button class="btn gh" type="button" aria-label="' . $label . '" style="padding:7px">' . Kit::icon($icon, 16) . '</button>';
    }

    /**
     * The table itself.
     *
     * `right` lists the column indexes that carry numbers, which are right
     * aligned so the digits line up - the single biggest readability win a
     * data table has.
     */
    private static function grid(array $cols, array $rows, array $opt = []): string
    {
        $right = $opt['right'] ?? [];
        $head = '';
        foreach (array_values($cols) as $i => $col) {
            $head .= '<th' . (in_array($i, $right, true) ? ' class="right"' : '') . '>' . $col . '</th>';
        }

        $body = '';
        foreach ($rows as $row) {
            $cells = '';
            foreach (array_values($row) as $i => $cell) {
                $cells .= '<td' . (in_array($i, $right, true) ? ' class="right"' : '') . '>' . $cell . '</td>';
            }
            $body .= '<tr>' . $cells . '</tr>';
        }

        $table = '<table><thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody></table>';

        return ($opt['scroll'] ?? true) ? '<div style="overflow-x:auto">' . $table . '</div>' : $table;
    }

    /** Footer with a row count on the left and pagination on the right. */
    private static function pager(string $note, int $page = 1, int $pages = 8): string
    {
        $buttons = '';
        foreach ([1, 2, 3] as $n) {
            $on = $n === $page;
            $style = $on
                ? 'background:var(--acc);border-color:var(--acc);color:#fff'
                : '';
            $buttons .= '<button class="btn tiny" type="button" style="' . $style . '"' . ($on ? ' aria-current="page"' : '') . '>' . $n . '</button>';
        }

        return '<div class="ft"><span>' . $note . '</span>'
            . '<span class="row" style="gap:5px">'
            . '<button class="btn tiny" type="button" aria-label="Previous">' . Kit::icon('chevron-left', 14) . '</button>'
            . $buttons
            . '<span class="xs mut" style="padding:0 4px">of ' . $pages . '</span>'
            . '<button class="btn tiny" type="button" aria-label="Next">' . Kit::icon('chevron-right', 14) . '</button>'
            . '</span></div>';
    }

    /** A person cell: avatar, name, secondary line. */
    private static function person(string $name, string $meta, int $size = 34): string
    {
        return '<span class="row">' . Kit::avatar($name, $size)
            . '<span><span class="bold" style="display:block">' . $name . '</span>'
            . '<span class="xs mut">' . $meta . '</span></span></span>';
    }

    /** A two-line cell with no avatar. */
    private static function two(string $main, string $meta): string
    {
        return '<span class="bold" style="display:block">' . $main . '</span><span class="xs mut">' . $meta . '</span>';
    }

    /** The hover-revealed action cluster at the end of a row. */
    private static function actions(string ...$icons): string
    {
        $icons = $icons ?: ['eye', 'edit', 'more'];
        $out = '<span class="acts row" style="gap:2px;justify-content:flex-end">';
        foreach ($icons as $icon) {
            $out .= self::iconBtn($icon, ucfirst($icon));
        }

        return $out . '</span>';
    }

    /** Hover behaviour shared by the tables that reveal actions. */
    private static function hoverCss(): string
    {
        return "tbody tr:hover{background:var(--soft)}\n"
            . ".acts{opacity:0;transition:opacity .15s}\ntr:hover .acts,.acts:focus-within{opacity:1}";
    }

    /** A checkbox styled to the accent, for selectable rows. */
    private static function check(bool $checked = false, string $label = 'Select row'): string
    {
        return '<input type="checkbox" aria-label="' . $label . '"' . ($checked ? ' checked' : '')
            . ' style="width:16px;height:16px;accent-color:var(--acc);cursor:pointer">';
    }

    /** A column header that advertises its sort direction. */
    private static function sortable(string $label, string $direction = ''): string
    {
        $arrow = $direction === '' ? '' : Kit::icon($direction === 'asc' ? 'chevron-up' : 'chevron-down', 12, 2.4);
        $colour = $direction === '' ? '' : 'color:var(--acc)';

        return '<span class="row" style="gap:4px;cursor:pointer;' . $colour . '">' . $label . $arrow . '</span>';
    }

    /* ================================================================== */
    /* Definitions                                                         */
    /* ================================================================== */

    /** @return array<int,array<string,mixed>> */
    private static function setOne(): array
    {
        return [
            [
                'slug' => 'users-directory-table',
                'name' => 'Users directory table',
                'name_ar' => 'جدول دليل المستخدمين',
                'tagline' => 'Searchable people list with roles, status chips and row actions.',
                'summary' => 'The everyday admin list: avatars drawn from initials, a role column, a status chip and actions that appear on hover so the row stays quiet until you reach for it. Search filters the rows as you type.',
                'accent' => '#2563eb',
                'tags' => ['users', 'directory', 'search', 'admin'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Live search over name, email and role', 'Initial-based avatars - no image requests', 'Row actions revealed on hover and on focus'],
                'height' => 520,
                'max' => 900,
                'css' => self::hoverCss(),
                'js' => "const q=document.getElementById('q');\n"
                    . "q.addEventListener('input',()=>{\n"
                    . "  const term=q.value.trim().toLowerCase();\n"
                    . "  document.querySelectorAll('tbody tr').forEach(row=>{\n"
                    . "    row.hidden=term!==''&&!row.textContent.toLowerCase().includes(term);\n"
                    . "  });\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Team members', '1,284 people in this workspace', self::search('Search people') . self::btn('Invite', 'plus', 'pri')),
                    self::grid(
                        ['Name', 'Role', 'Teams', 'Status', '<span class="right" style="display:block">Actions</span>'],
                        [
                            [self::person('Lina Haddad', 'lina@frugal.io'), 'Owner', Kit::pill('Design', 'info'), Kit::pill('Active', 'ok', true), self::actions()],
                            [self::person('Omar Saleh', 'omar@frugal.io'), 'Admin', Kit::pill('Engineering', 'info'), Kit::pill('Active', 'ok', true), self::actions()],
                            [self::person('Maya Rahman', 'maya@frugal.io'), 'Editor', Kit::pill('Content', 'info'), Kit::pill('Invited', 'warn', true), self::actions()],
                            [self::person('Karim Nasser', 'karim@frugal.io'), 'Viewer', Kit::pill('Support', 'info'), Kit::pill('Active', 'ok', true), self::actions()],
                            [self::person('Sara Aziz', 'sara@frugal.io'), 'Editor', Kit::pill('Marketing', 'info'), Kit::pill('Suspended', 'bad', true), self::actions()],
                        ],
                        ['right' => [4]]
                    ),
                    self::pager('Showing 5 of 1,284')
                ),
            ],
            [
                'slug' => 'sortable-data-table',
                'name' => 'Sortable data table',
                'name_ar' => 'جدول قابل للفرز',
                'tagline' => 'Click any header to sort - ascending, descending, numeric aware.',
                'summary' => 'Sorting in about twenty lines of vanilla JavaScript. Numeric columns are detected and compared as numbers rather than as text, so 9 never sorts after 10, and the active header carries an arrow so the current order is visible.',
                'accent' => '#0891b2',
                'tags' => ['table', 'sort', 'vanilla-js'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Three-state sorting on every column', 'Numeric columns compared as numbers', 'Sort direction shown in the header'],
                'height' => 500,
                'max' => 820,
                'css' => "th{cursor:pointer;user-select:none}\nth:hover{color:var(--acc)}\nth .ar{opacity:0;transition:opacity .15s}\nth[data-dir] .ar{opacity:1;color:var(--acc)}\ntbody tr:hover{background:var(--soft)}",
                'js' => "const table=document.querySelector('table');\n"
                    . "table.querySelectorAll('th').forEach((th,index)=>{\n"
                    . "  th.addEventListener('click',()=>{\n"
                    . "    const dir=th.dataset.dir==='asc'?'desc':'asc';\n"
                    . "    table.querySelectorAll('th').forEach(h=>h.removeAttribute('data-dir'));\n"
                    . "    th.dataset.dir=dir;\n"
                    . "    const body=table.tBodies[0];\n"
                    . "    const rows=[...body.rows];\n"
                    . "    const value=row=>row.cells[index].dataset.v??row.cells[index].innerText.trim();\n"
                    . "    rows.sort((a,b)=>{\n"
                    . "      const x=value(a),y=value(b);\n"
                    . "      const numeric=!isNaN(parseFloat(x))&&!isNaN(parseFloat(y));\n"
                    . "      const result=numeric?parseFloat(x)-parseFloat(y):x.localeCompare(y);\n"
                    . "      return dir==='asc'?result:-result;\n"
                    . "    });\n"
                    . "    rows.forEach(row=>body.appendChild(row));\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Product performance', 'Click a column heading to reorder'),
                    self::grid(
                        ['Product', 'Category', 'Units', 'Revenue', 'Margin'],
                        [
                            ['Aurora Desk Lamp', 'Lighting', '1,204', '<span data-v="48160">$48,160</span>', '<span data-v="42">42%</span>'],
                            ['Nimbus Chair', 'Seating', '932', '<span data-v="139800">$139,800</span>', '<span data-v="38">38%</span>'],
                            ['Terra Side Table', 'Tables', '618', '<span data-v="30900">$30,900</span>', '<span data-v="51">51%</span>'],
                            ['Halo Floor Lamp', 'Lighting', '2,140', '<span data-v="85600">$85,600</span>', '<span data-v="34">34%</span>'],
                            ['Vista Shelving', 'Storage', '405', '<span data-v="60750">$60,750</span>', '<span data-v="46">46%</span>'],
                        ],
                        ['right' => [2, 3, 4]]
                    ),
                    self::pager('5 products')
                ),
            ],
            [
                'slug' => 'transactions-ledger-table',
                'name' => 'Transactions ledger',
                'name_ar' => 'جدول الحركات المالية',
                'tagline' => 'Money in, money out - signed amounts and settlement state.',
                'summary' => 'A ledger reads best when credits and debits are told apart by colour and sign rather than by a separate column. Statuses cover the three that matter operationally: settled, pending and failed.',
                'accent' => '#059669',
                'tags' => ['finance', 'transactions', 'ledger'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Signed, colour-coded amounts', 'Settlement state per row', 'Running balance in the footer'],
                'height' => 500,
                'max' => 880,
                'css' => "tbody tr:hover{background:var(--soft)}\n.in-amt{color:var(--ok);font-weight:650}\n.out-amt{color:var(--ink);font-weight:650}",
                'body' => self::wrap(
                    self::head('Transactions', 'Account ending 4417', self::btn('This month', 'calendar') . self::btn('Export', 'download')),
                    self::grid(
                        ['Date', 'Description', 'Method', 'Status', 'Amount'],
                        [
                            ['<span class="num">12 Sep</span>', self::two('Stripe payout', 'po_1P8kQ2'), 'Bank transfer', Kit::pill('Settled', 'ok'), '<span class="in-amt num">+$4,820.00</span>'],
                            ['<span class="num">11 Sep</span>', self::two('Adobe Creative Cloud', 'Subscription'), 'Visa •• 4242', Kit::pill('Settled', 'ok'), '<span class="out-amt num">-$59.99</span>'],
                            ['<span class="num">10 Sep</span>', self::two('Refund - order #3092', 'Customer request'), 'Visa •• 4242', Kit::pill('Pending', 'warn'), '<span class="out-amt num">-$128.40</span>'],
                            ['<span class="num">09 Sep</span>', self::two('Invoice #2281', 'Northwind Ltd'), 'ACH', Kit::pill('Settled', 'ok'), '<span class="in-amt num">+$12,000.00</span>'],
                            ['<span class="num">08 Sep</span>', self::two('Card payment', 'Declined by issuer'), 'Mastercard •• 7781', Kit::pill('Failed', 'bad'), '<span class="out-amt num">-$340.00</span>'],
                        ],
                        ['right' => [4]]
                    ),
                    '<div class="ft"><span>5 transactions</span><span class="bold num">Net +$16,291.61</span></div>'
                ),
            ],
            [
                'slug' => 'invoice-line-items-table',
                'name' => 'Invoice line items',
                'name_ar' => 'جدول بنود الفاتورة',
                'tagline' => 'Billing lines with quantity, rate and a totals block.',
                'summary' => 'The half of an invoice that actually has to be right: line items, then a totals stack that separates subtotal, discount, tax and the amount due. The due figure is the only thing set in the accent colour, so the eye lands on it first.',
                'accent' => '#4f46e5',
                'tags' => ['invoice', 'billing', 'totals'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Quantity, rate and line total per row', 'Totals block with tax and discount', 'Print-friendly - no JavaScript at all'],
                'height' => 560,
                'max' => 780,
                'css' => ".totals{display:grid;gap:9px;padding:16px 20px;border-top:1px solid var(--bd);margin-left:auto;width:min(320px,100%)}\n.totals div{display:flex;justify-content:space-between;font-size:13px}\n.totals .due{padding-top:10px;border-top:1px dashed var(--bd);font-size:16px;font-weight:700;color:var(--acc)}",
                'body' => self::wrap(
                    self::head('Invoice INV-2281', 'Due 30 September 2026 · Northwind Ltd', Kit::pill('Unpaid', 'warn')),
                    self::grid(
                        ['Description', 'Qty', 'Rate', 'Amount'],
                        [
                            [self::two('Design retainer', 'September 2026'), '<span class="num">1</span>', '<span class="num">$6,000.00</span>', '<span class="num bold">$6,000.00</span>'],
                            [self::two('Front-end development', '48 hours at $95'), '<span class="num">48</span>', '<span class="num">$95.00</span>', '<span class="num bold">$4,560.00</span>'],
                            [self::two('Usability testing', '6 sessions'), '<span class="num">6</span>', '<span class="num">$220.00</span>', '<span class="num bold">$1,320.00</span>'],
                            [self::two('Hosting', 'Annual, prepaid'), '<span class="num">1</span>', '<span class="num">$480.00</span>', '<span class="num bold">$480.00</span>'],
                        ],
                        ['right' => [1, 2, 3]]
                    ),
                    '<div class="totals">'
                    . '<div><span class="mut">Subtotal</span><span class="num">$12,360.00</span></div>'
                    . '<div><span class="mut">Discount (5%)</span><span class="num">-$618.00</span></div>'
                    . '<div><span class="mut">VAT (15%)</span><span class="num">$1,761.30</span></div>'
                    . '<div class="due"><span>Amount due</span><span class="num">$13,503.30</span></div>'
                    . '</div>'
                ),
            ],
            [
                'slug' => 'orders-status-table',
                'name' => 'Orders status table',
                'name_ar' => 'جدول حالات الطلبات',
                'tagline' => 'Order pipeline with fulfilment state and a filter bar.',
                'summary' => 'An order list built around its status filter: the chips above the table are the primary control, and the count beside each one means you never have to filter just to find out whether anything is there.',
                'accent' => '#d97706',
                'tags' => ['orders', 'ecommerce', 'filters'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Filter chips with live counts', 'Fulfilment state per order', 'Filtering with no dependencies'],
                'height' => 560,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\n.chips{display:flex;gap:7px;padding:12px 20px;border-bottom:1px solid var(--bd);flex-wrap:wrap}\n.chip{padding:6px 12px;border:1px solid var(--bd);border-radius:999px;background:var(--card);font-size:12.5px;font-weight:600;cursor:pointer;color:var(--mut);transition:.15s}\n.chip:hover{border-color:var(--acc);color:var(--acc)}\n.chip[aria-pressed=true]{background:var(--acc);border-color:var(--acc);color:#fff}",
                'js' => "document.querySelectorAll('.chip').forEach(chip=>{\n"
                    . "  chip.addEventListener('click',()=>{\n"
                    . "    document.querySelectorAll('.chip').forEach(c=>c.setAttribute('aria-pressed','false'));\n"
                    . "    chip.setAttribute('aria-pressed','true');\n"
                    . "    const want=chip.dataset.state;\n"
                    . "    document.querySelectorAll('tbody tr').forEach(row=>{\n"
                    . "      row.hidden=want!=='all'&&row.dataset.state!==want;\n"
                    . "    });\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Orders', 'Updated two minutes ago', self::btn('Export', 'download')),
                    '<div class="chips">'
                    . '<button class="chip" type="button" data-state="all" aria-pressed="true">All · 5</button>'
                    . '<button class="chip" type="button" data-state="paid" aria-pressed="false">Paid · 2</button>'
                    . '<button class="chip" type="button" data-state="packed" aria-pressed="false">Packed · 1</button>'
                    . '<button class="chip" type="button" data-state="shipped" aria-pressed="false">Shipped · 1</button>'
                    . '<button class="chip" type="button" data-state="refunded" aria-pressed="false">Refunded · 1</button>'
                    . '</div>',
                    '<div style="overflow-x:auto"><table><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Status</th><th class="right">Total</th></tr></thead><tbody>'
                    . '<tr data-state="paid"><td>' . self::two('#3104', '12 Sep, 09:41') . '</td><td>' . self::person('Rana Khalil', 'Riyadh, SA', 30) . '</td><td class="num">3</td><td>' . Kit::pill('Paid', 'ok') . '</td><td class="right num bold">$248.00</td></tr>'
                    . '<tr data-state="packed"><td>' . self::two('#3103', '12 Sep, 08:12') . '</td><td>' . self::person('Tarek Fadel', 'Amman, JO', 30) . '</td><td class="num">1</td><td>' . Kit::pill('Packed', 'info') . '</td><td class="right num bold">$89.00</td></tr>'
                    . '<tr data-state="shipped"><td>' . self::two('#3102', '11 Sep, 17:30') . '</td><td>' . self::person('Hala Mansour', 'Dubai, AE', 30) . '</td><td class="num">5</td><td>' . Kit::pill('Shipped', 'info') . '</td><td class="right num bold">$612.50</td></tr>'
                    . '<tr data-state="paid"><td>' . self::two('#3101', '11 Sep, 14:02') . '</td><td>' . self::person('Yusuf Barak', 'Cairo, EG', 30) . '</td><td class="num">2</td><td>' . Kit::pill('Paid', 'ok') . '</td><td class="right num bold">$134.90</td></tr>'
                    . '<tr data-state="refunded"><td>' . self::two('#3100', '10 Sep, 11:48') . '</td><td>' . self::person('Nadia Osman', 'Doha, QA', 30) . '</td><td class="num">1</td><td>' . Kit::pill('Refunded', 'bad') . '</td><td class="right num bold">-$42.00</td></tr>'
                    . '</tbody></table></div>',
                    self::pager('5 of 1,902 orders')
                ),
            ],
            [
                'slug' => 'inventory-stock-table',
                'name' => 'Inventory stock levels',
                'name_ar' => 'جدول مستويات المخزون',
                'tagline' => 'Stock on hand with a level bar and a reorder warning.',
                'summary' => 'Stock is a quantity and a threshold, so each row carries both: a meter showing how full the bin is against its reorder point, and a chip that turns amber the moment it drops below. Reading the whole table takes one pass.',
                'accent' => '#0d9488',
                'tags' => ['inventory', 'stock', 'warehouse'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Stock meter against the reorder point', 'Low-stock and out-of-stock chips', 'SKU and bin location per row'],
                'height' => 540,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\n.lvl{min-width:130px}",
                'body' => self::wrap(
                    self::head('Inventory', 'Warehouse A · 412 SKUs', self::search('Find a SKU') . self::btn('Receive stock', 'plus', 'pri')),
                    self::grid(
                        ['Item', 'SKU', 'Bin', 'Level', 'On hand', 'State'],
                        [
                            [self::two('Aurora Desk Lamp', 'Lighting'), '<span class="num mut">LMP-0041</span>', 'A-12-3', '<span class="lvl" style="display:block">' . Kit::meter(82, '#0d9488') . '</span>', '<span class="num bold">412</span>', Kit::pill('In stock', 'ok')],
                            [self::two('Nimbus Chair', 'Seating'), '<span class="num mut">CHR-2210</span>', 'B-04-1', '<span class="lvl" style="display:block">' . Kit::meter(34, '#f59e0b') . '</span>', '<span class="num bold">57</span>', Kit::pill('Low', 'warn')],
                            [self::two('Terra Side Table', 'Tables'), '<span class="num mut">TBL-1187</span>', 'B-09-2', '<span class="lvl" style="display:block">' . Kit::meter(61, '#0d9488') . '</span>', '<span class="num bold">188</span>', Kit::pill('In stock', 'ok')],
                            [self::two('Halo Floor Lamp', 'Lighting'), '<span class="num mut">LMP-0088</span>', 'A-15-4', '<span class="lvl" style="display:block">' . Kit::meter(6, '#e11d48') . '</span>', '<span class="num bold">4</span>', Kit::pill('Reorder', 'bad')],
                            [self::two('Vista Shelving', 'Storage'), '<span class="num mut">SHL-0302</span>', 'C-01-1', '<span class="lvl" style="display:block">' . Kit::meter(0, '#e11d48') . '</span>', '<span class="num bold">0</span>', Kit::pill('Out of stock', 'bad')],
                        ],
                        ['right' => [4]]
                    ),
                    self::pager('5 of 412 items')
                ),
            ],
            [
                'slug' => 'leaderboard-rank-table',
                'name' => 'Leaderboard table',
                'name_ar' => 'جدول المتصدرين',
                'tagline' => 'Ranked standings with medals, movement and points.',
                'summary' => 'A leaderboard has to answer two questions at a glance: who is on top, and who is climbing. The first three ranks get medal discs, and every row carries its movement since the last period as a signed, coloured delta.',
                'accent' => '#f59e0b',
                'tags' => ['leaderboard', 'ranking', 'gamification'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Medal treatment for the top three', 'Signed movement column', 'Points bar relative to the leader'],
                'height' => 540,
                'max' => 780,
                'css' => "tbody tr:hover{background:var(--soft)}\n.rank{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;font-weight:700;font-size:13px;background:var(--soft);color:var(--mut)}\n.rank.g{background:linear-gradient(140deg,#fde68a,#f59e0b);color:#78350f}\n.rank.s{background:linear-gradient(140deg,#e2e8f0,#94a3b8);color:#1e293b}\n.rank.b{background:linear-gradient(140deg,#fed7aa,#c2703b);color:#3f2412}",
                'body' => self::wrap(
                    self::head('Season standings', 'Week 12 · resets in 4 days', Kit::pill('Live', 'ok', true)),
                    self::grid(
                        ['#', 'Player', 'Streak', 'Movement', 'Points'],
                        [
                            ['<span class="rank g">1</span>', self::person('Maya Rahman', '@mrahman'), '<span class="num">18 days</span>', Kit::delta('+3', true), '<span class="num bold">18,420</span>'],
                            ['<span class="rank s">2</span>', self::person('Omar Saleh', '@osaleh'), '<span class="num">12 days</span>', Kit::delta('-1', false), '<span class="num bold">17,980</span>'],
                            ['<span class="rank b">3</span>', self::person('Lina Haddad', '@lhaddad'), '<span class="num">9 days</span>', Kit::delta('+1', true), '<span class="num bold">16,340</span>'],
                            ['<span class="rank">4</span>', self::person('Karim Nasser', '@knasser'), '<span class="num">21 days</span>', Kit::delta('-2', false), '<span class="num bold">15,120</span>'],
                            ['<span class="rank">5</span>', self::person('Sara Aziz', '@saziz'), '<span class="num">4 days</span>', Kit::delta('+6', true), '<span class="num bold">14,870</span>'],
                        ],
                        ['right' => [4]]
                    ),
                    '<div class="ft"><span>Top 5 of 2,140 players</span><a href="#">View full standings</a></div>'
                ),
            ],
            [
                'slug' => 'pricing-comparison-table',
                'name' => 'Pricing comparison table',
                'name_ar' => 'جدول مقارنة الباقات',
                'tagline' => 'Plan feature matrix with a highlighted recommended column.',
                'summary' => 'Three plans across, features down, ticks and dashes in between. The recommended column is tinted end to end rather than merely badged, so the eye follows it down the page instead of re-reading the header.',
                'accent' => '#7c3aed',
                'tags' => ['pricing', 'comparison', 'saas'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Recommended column tinted top to bottom', 'Tick and dash marks instead of yes/no text', 'Per-plan call to action in the footer row'],
                'height' => 620,
                'max' => 820,
                'css' => "th,td{text-align:center}\nth:first-child,td:first-child{text-align:left}\n.pick{background:var(--acc-soft)}\nth.pick{background:var(--acc);color:#fff}\n.yes{color:var(--ok);display:inline-flex}\n.no{color:var(--faint)}\n.price{font-size:22px;font-weight:700;display:block;margin-top:3px}\n.per{font-size:11.5px;font-weight:500;opacity:.75}",
                'body' => self::wrap(
                    self::head('Compare plans', 'Billed annually · cancel any time'),
                    self::grid(
                        [
                            'Feature',
                            'Starter<span class="price">$0<span class="per">/mo</span></span>',
                            '<span class="pick" style="display:block">Studio<span class="price">$29<span class="per">/mo</span></span></span>',
                            'Agency<span class="price">$79<span class="per">/mo</span></span>',
                        ],
                        [
                            ['Projects', '<span class="num">3</span>', '<span class="pick num">Unlimited</span>', '<span class="num">Unlimited</span>'],
                            ['Team seats', '<span class="num">1</span>', '<span class="pick num">10</span>', '<span class="num">50</span>'],
                            ['Custom domain', '<span class="no">—</span>', '<span class="pick"><span class="yes">' . Kit::icon('check', 17, 2.4) . '</span></span>', '<span class="yes">' . Kit::icon('check', 17, 2.4) . '</span>'],
                            ['Priority support', '<span class="no">—</span>', '<span class="pick no">—</span>', '<span class="yes">' . Kit::icon('check', 17, 2.4) . '</span>'],
                            ['Audit log', '<span class="no">—</span>', '<span class="pick no">—</span>', '<span class="yes">' . Kit::icon('check', 17, 2.4) . '</span>'],
                            ['', '<button class="btn" type="button">Start free</button>', '<span class="pick" style="display:block;padding:8px 0"><button class="btn pri" type="button">Choose Studio</button></span>', '<button class="btn" type="button">Contact sales</button>'],
                        ]
                    )
                ),
            ],
            [
                'slug' => 'subscription-billing-table',
                'name' => 'Subscription billing table',
                'name_ar' => 'جدول الاشتراكات والفوترة',
                'tagline' => 'Active subscriptions, renewal dates and MRR per customer.',
                'summary' => 'Built for the question a billing screen is usually opened with: what renews next, and what is it worth. Renewal dates carry a relative caption, and anything renewing inside a week is highlighted.',
                'accent' => '#2563eb',
                'tags' => ['billing', 'subscriptions', 'saas', 'mrr'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Relative renewal dates with a soon warning', 'Plan and seat count per customer', 'MRR total in the footer'],
                'height' => 520,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\n.soon{color:var(--warn);font-weight:650}",
                'body' => self::wrap(
                    self::head('Subscriptions', '312 active · $48,120 MRR', self::btn('Filter', 'filter') . self::btn('New subscription', 'plus', 'pri')),
                    self::grid(
                        ['Customer', 'Plan', 'Seats', 'Renews', 'Status', 'MRR'],
                        [
                            [self::person('Northwind Ltd', 'billing@northwind.co'), Kit::pill('Agency', 'info'), '<span class="num">42</span>', self::two('30 Sep 2026', '<span class="soon">in 6 days</span>'), Kit::pill('Active', 'ok', true), '<span class="num bold">$3,318</span>'],
                            [self::person('Bluebird Media', 'ops@bluebird.io'), Kit::pill('Studio', 'info'), '<span class="num">9</span>', self::two('14 Oct 2026', 'in 3 weeks'), Kit::pill('Active', 'ok', true), '<span class="num bold">$261</span>'],
                            [self::person('Harbor Studio', 'hello@harbor.design'), Kit::pill('Studio', 'info'), '<span class="num">6</span>', self::two('02 Oct 2026', '<span class="soon">in 8 days</span>'), Kit::pill('Past due', 'bad', true), '<span class="num bold">$174</span>'],
                            [self::person('Copper & Co', 'finance@copper.co'), Kit::pill('Agency', 'info'), '<span class="num">28</span>', self::two('19 Nov 2026', 'in 2 months'), Kit::pill('Active', 'ok', true), '<span class="num bold">$2,212</span>'],
                            [self::person('Juno Labs', 'accounts@juno.dev'), Kit::pill('Starter', 'neutral'), '<span class="num">1</span>', self::two('—', 'free plan'), Kit::pill('Trialling', 'warn', true), '<span class="num bold">$0</span>'],
                        ],
                        ['right' => [2, 5]]
                    ),
                    '<div class="ft"><span>5 of 312 subscriptions</span><span class="bold num">Total MRR $5,965</span></div>'
                ),
            ],
            [
                'slug' => 'support-tickets-table',
                'name' => 'Support tickets queue',
                'name_ar' => 'جدول تذاكر الدعم',
                'tagline' => 'Ticket queue with priority stripes and SLA countdowns.',
                'summary' => 'A queue triaged by eye: a coloured stripe down the left of each row carries priority, and the SLA column counts down, turning red once a ticket has breached. Assignees are shown as avatars so the workload spread is obvious.',
                'accent' => '#e11d48',
                'tags' => ['support', 'tickets', 'sla', 'helpdesk'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Priority stripe on every row', 'SLA countdown that flips to breached', 'Assignee avatars with unassigned state'],
                'height' => 540,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\ntbody td:first-child{position:relative;padding-left:22px}\ntbody td:first-child::before{content:'';position:absolute;left:8px;top:10px;bottom:10px;width:3px;border-radius:3px;background:var(--faint)}\ntr[data-p=urgent] td:first-child::before{background:#e11d48}\ntr[data-p=high] td:first-child::before{background:#f59e0b}\ntr[data-p=normal] td:first-child::before{background:#2563eb}\n.breach{color:var(--bad);font-weight:650}",
                'body' => self::wrap(
                    self::head('Open tickets', '18 unassigned · 4 breaching today', self::search('Search tickets') . self::btn('New ticket', 'plus', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Ticket</th><th>Customer</th><th>Priority</th><th>Assignee</th><th>SLA</th><th class="right">Updated</th></tr></thead><tbody>'
                    . '<tr data-p="urgent"><td>' . self::two('Checkout fails on Safari', '#4821 · Billing') . '</td><td>Northwind Ltd</td><td>' . Kit::pill('Urgent', 'bad') . '</td><td>' . Kit::avatar('Omar Saleh', 28) . '</td><td><span class="breach">Breached 40m</span></td><td class="right mut num">4m ago</td></tr>'
                    . '<tr data-p="high"><td>' . self::two('Cannot invite teammates', '#4820 · Accounts') . '</td><td>Bluebird Media</td><td>' . Kit::pill('High', 'warn') . '</td><td>' . Kit::avatar('Maya Rahman', 28) . '</td><td><span class="num">1h 12m left</span></td><td class="right mut num">22m ago</td></tr>'
                    . '<tr data-p="normal"><td>' . self::two('Export missing columns', '#4816 · Reports') . '</td><td>Harbor Studio</td><td>' . Kit::pill('Normal', 'info') . '</td><td><span class="mut xs">Unassigned</span></td><td><span class="num">6h left</span></td><td class="right mut num">1h ago</td></tr>'
                    . '<tr data-p="normal"><td>' . self::two('Invoice address wrong', '#4809 · Billing') . '</td><td>Copper &amp; Co</td><td>' . Kit::pill('Normal', 'info') . '</td><td>' . Kit::avatar('Karim Nasser', 28) . '</td><td><span class="num">1d left</span></td><td class="right mut num">3h ago</td></tr>'
                    . '<tr data-p="high"><td>' . self::two('API returning 502', '#4802 · Platform') . '</td><td>Juno Labs</td><td>' . Kit::pill('High', 'warn') . '</td><td>' . Kit::avatar('Lina Haddad', 28) . '</td><td><span class="num">38m left</span></td><td class="right mut num">6h ago</td></tr>'
                    . '</tbody></table></div>',
                    self::pager('5 of 74 open tickets')
                ),
            ],
            [
                'slug' => 'selectable-rows-table',
                'name' => 'Selectable rows with bulk bar',
                'name_ar' => 'جدول بتحديد متعدد',
                'tagline' => 'Row checkboxes, a select-all header and a bulk action bar.',
                'summary' => 'Selection done properly: the header checkbox reflects a partial selection with the indeterminate state, and the bulk bar only appears once something is selected, so it never takes up room it has not earned.',
                'accent' => '#4f46e5',
                'tags' => ['selection', 'bulk-actions', 'checkbox'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Indeterminate select-all checkbox', 'Bulk bar that appears with the first selection', 'Selected rows tinted for confirmation'],
                'height' => 560,
                'max' => 880,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.on{background:var(--acc-soft)!important}\n.bulk{display:none;align-items:center;justify-content:space-between;gap:12px;padding:11px 20px;background:var(--acc-soft);border-bottom:1px solid var(--bd);flex-wrap:wrap}\n.bulk.show{display:flex}\n.bulk b{color:var(--acc)}",
                'js' => "const all=document.getElementById('all');\n"
                    . "const boxes=[...document.querySelectorAll('tbody input[type=checkbox]')];\n"
                    . "const bar=document.querySelector('.bulk');\n"
                    . "const count=document.getElementById('count');\n"
                    . "function sync(){\n"
                    . "  const picked=boxes.filter(b=>b.checked);\n"
                    . "  boxes.forEach(b=>b.closest('tr').classList.toggle('on',b.checked));\n"
                    . "  bar.classList.toggle('show',picked.length>0);\n"
                    . "  count.textContent=picked.length;\n"
                    . "  all.checked=picked.length===boxes.length;\n"
                    . "  all.indeterminate=picked.length>0&&picked.length<boxes.length;\n"
                    . "}\n"
                    . "all.addEventListener('change',()=>{boxes.forEach(b=>b.checked=all.checked);sync();});\n"
                    . "boxes.forEach(b=>b.addEventListener('change',sync));\nsync();",
                'body' => self::wrap(
                    self::head('Media library', '1,204 files', self::btn('Upload', 'upload', 'pri')),
                    '<div class="bulk"><span><b id="count">0</b> selected</span>'
                    . '<span class="row" style="gap:7px">' . self::btn('Move', 'folder') . self::btn('Download', 'download') . self::btn('Delete', 'trash') . '</span></div>',
                    '<div style="overflow-x:auto"><table><thead><tr>'
                    . '<th style="width:44px"><input id="all" type="checkbox" aria-label="Select all rows" style="width:16px;height:16px;accent-color:var(--acc);cursor:pointer"></th>'
                    . '<th>File</th><th>Owner</th><th>Type</th><th class="right">Size</th></tr></thead><tbody>'
                    . '<tr><td>' . self::check() . '</td><td>' . self::two('brand-guide-2026.pdf', 'Updated 2 days ago') . '</td><td>' . Kit::avatar('Lina Haddad', 26) . '</td><td>' . Kit::pill('PDF', 'neutral') . '</td><td class="right num">4.2 MB</td></tr>'
                    . '<tr><td>' . self::check(true) . '</td><td>' . self::two('hero-render.png', 'Updated 5 hours ago') . '</td><td>' . Kit::avatar('Omar Saleh', 26) . '</td><td>' . Kit::pill('Image', 'info') . '</td><td class="right num">1.8 MB</td></tr>'
                    . '<tr><td>' . self::check() . '</td><td>' . self::two('q3-report.xlsx', 'Updated last week') . '</td><td>' . Kit::avatar('Maya Rahman', 26) . '</td><td>' . Kit::pill('Sheet', 'ok') . '</td><td class="right num">812 KB</td></tr>'
                    . '<tr><td>' . self::check(true) . '</td><td>' . self::two('launch-clip.mp4', 'Updated yesterday') . '</td><td>' . Kit::avatar('Karim Nasser', 26) . '</td><td>' . Kit::pill('Video', 'warn') . '</td><td class="right num">64.7 MB</td></tr>'
                    . '<tr><td>' . self::check() . '</td><td>' . self::two('pitch-deck.key', 'Updated 3 weeks ago') . '</td><td>' . Kit::avatar('Sara Aziz', 26) . '</td><td>' . Kit::pill('Slides', 'neutral') . '</td><td class="right num">22.1 MB</td></tr>'
                    . '</tbody></table></div>',
                    self::pager('5 of 1,204 files')
                ),
            ],
            [
                'slug' => 'expandable-rows-table',
                'name' => 'Expandable detail rows',
                'name_ar' => 'جدول بصفوف قابلة للتوسيع',
                'tagline' => 'Click a row to reveal its detail panel underneath.',
                'summary' => 'Detail without navigation: the chevron opens a panel in the row below, so a shipment, an order or a deploy can be inspected without losing the list. Built on a hidden table row, so the columns stay aligned.',
                'accent' => '#0891b2',
                'tags' => ['expandable', 'accordion', 'details'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Detail panel inside the table, not a modal', 'Chevron rotates to show the open state', 'Keyboard reachable - the trigger is a button'],
                'height' => 560,
                'max' => 880,
                'css' => "tbody tr.main:hover{background:var(--soft)}\n.exp{background:var(--soft)}\n.exp td{padding:0;border-bottom:1px solid var(--bd)}\n.exp .in-pad{padding:16px 20px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}\n.tog svg{transition:transform .18s}\ntr.open .tog svg{transform:rotate(90deg);color:var(--acc)}\n.kv span{display:block}\n.kv .k{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--mut);margin-bottom:3px}",
                'js' => "document.querySelectorAll('.tog').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    const row=button.closest('tr');\n"
                    . "    const open=row.classList.toggle('open');\n"
                    . "    row.nextElementSibling.hidden=!open;\n"
                    . "    button.setAttribute('aria-expanded',open);\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Deployments', 'Production · last 24 hours', self::btn('Rollback', 'refresh')),
                    '<div style="overflow-x:auto"><table><thead><tr><th style="width:46px"></th><th>Release</th><th>Author</th><th>Status</th><th class="right">Duration</th></tr></thead><tbody>'
                    . '<tr class="main open"><td><button class="btn gh tog" type="button" aria-expanded="true" aria-label="Toggle details" style="padding:6px">' . Kit::icon('chevron-right', 16) . '</button></td>'
                    . '<td>' . self::two('v4.12.0', 'commit 9f2ac41') . '</td><td>' . Kit::avatar('Omar Saleh', 26) . '</td><td>' . Kit::pill('Live', 'ok', true) . '</td><td class="right num">2m 14s</td></tr>'
                    . '<tr class="exp"><td colspan="5"><div class="in-pad kv">'
                    . '<span><span class="k">Environment</span><span class="bold">production-eu</span></span>'
                    . '<span><span class="k">Build</span><span class="bold num">#2 940</span></span>'
                    . '<span><span class="k">Tests</span><span class="bold">412 passed</span></span>'
                    . '<span><span class="k">Rolled out</span><span class="bold">100% of traffic</span></span>'
                    . '</div></td></tr>'
                    . '<tr class="main"><td><button class="btn gh tog" type="button" aria-expanded="false" aria-label="Toggle details" style="padding:6px">' . Kit::icon('chevron-right', 16) . '</button></td>'
                    . '<td>' . self::two('v4.11.3', 'commit 1b77de0') . '</td><td>' . Kit::avatar('Maya Rahman', 26) . '</td><td>' . Kit::pill('Rolled back', 'bad') . '</td><td class="right num">4m 02s</td></tr>'
                    . '<tr class="exp" hidden><td colspan="5"><div class="in-pad kv">'
                    . '<span><span class="k">Environment</span><span class="bold">production-eu</span></span>'
                    . '<span><span class="k">Build</span><span class="bold num">#2 938</span></span>'
                    . '<span><span class="k">Failure</span><span class="bold">health check timeout</span></span>'
                    . '<span><span class="k">Reverted to</span><span class="bold">v4.11.2</span></span>'
                    . '</div></td></tr>'
                    . '<tr class="main"><td><button class="btn gh tog" type="button" aria-expanded="false" aria-label="Toggle details" style="padding:6px">' . Kit::icon('chevron-right', 16) . '</button></td>'
                    . '<td>' . self::two('v4.11.2', 'commit c40f8a9') . '</td><td>' . Kit::avatar('Karim Nasser', 26) . '</td><td>' . Kit::pill('Superseded', 'neutral') . '</td><td class="right num">1m 58s</td></tr>'
                    . '<tr class="exp" hidden><td colspan="5"><div class="in-pad kv">'
                    . '<span><span class="k">Environment</span><span class="bold">production-eu</span></span>'
                    . '<span><span class="k">Build</span><span class="bold num">#2 931</span></span>'
                    . '<span><span class="k">Tests</span><span class="bold">409 passed</span></span>'
                    . '<span><span class="k">Rolled out</span><span class="bold">100% of traffic</span></span>'
                    . '</div></td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>3 deployments today</span><a href="#">Deployment history</a></div>'
                ),
            ],
            [
                'slug' => 'compact-dense-table',
                'name' => 'Compact dense table',
                'name_ar' => 'جدول مضغوط كثيف',
                'tagline' => 'Maximum rows per screen - for operators, not for browsing.',
                'summary' => 'A deliberately dense grid: tight row height, monospaced figures, hairline separators and a sticky header. This is the table you want when someone scans two hundred rows a minute, and the opposite of the airy marketing table.',
                'accent' => '#334155',
                'tags' => ['dense', 'compact', 'monospace', 'operations'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['28px row height with a sticky header', 'Tabular figures throughout', 'Scroll region with the header pinned'],
                'height' => 520,
                'max' => 880,
                'css' => ".dense{max-height:360px;overflow:auto}\n.dense th{position:sticky;top:0;z-index:1;padding:7px 12px;font-size:10.5px}\n.dense td{padding:5px 12px;font-size:12.5px;font-variant-numeric:tabular-nums}\n.dense tbody tr:nth-child(even){background:var(--soft)}\n.dense tbody tr:hover{background:var(--acc-soft)}\n.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}",
                'body' => self::wrap(
                    self::head('Trade blotter', 'FIX session 08:00-16:30 · 8,412 fills today', Kit::pill('Streaming', 'ok', true)),
                    '<div class="dense"><table><thead><tr><th>Time</th><th>Symbol</th><th>Side</th><th class="right">Qty</th><th class="right">Price</th><th class="right">Value</th><th>Venue</th></tr></thead><tbody>'
                    . implode('', array_map(function ($row) {
                        [$time, $symbol, $side, $qty, $price, $value, $venue] = $row;
                        $tone = $side === 'BUY' ? 'var(--ok)' : 'var(--bad)';

                        return '<tr><td class="mono mut">' . $time . '</td><td class="mono bold">' . $symbol . '</td>'
                            . '<td class="bold" style="color:' . $tone . '">' . $side . '</td>'
                            . '<td class="right">' . $qty . '</td><td class="right">' . $price . '</td>'
                            . '<td class="right bold">' . $value . '</td><td class="mut">' . $venue . '</td></tr>';
                    }, [
                        ['09:31:02.441', 'AAPL', 'BUY', '1,200', '214.38', '257,256', 'NASDAQ'],
                        ['09:31:02.502', 'MSFT', 'SELL', '480', '431.20', '206,976', 'NASDAQ'],
                        ['09:31:03.118', 'TSLA', 'BUY', '900', '248.71', '223,839', 'NASDAQ'],
                        ['09:31:04.007', 'NVDA', 'BUY', '2,100', '118.44', '248,724', 'NASDAQ'],
                        ['09:31:04.612', 'AMZN', 'SELL', '640', '186.02', '119,052', 'NASDAQ'],
                        ['09:31:05.330', 'META', 'BUY', '310', '512.88', '158,992', 'NASDAQ'],
                        ['09:31:06.041', 'GOOG', 'SELL', '1,050', '167.41', '175,780', 'NASDAQ'],
                        ['09:31:06.884', 'AMD', 'BUY', '3,400', '142.09', '483,106', 'NYSE'],
                        ['09:31:07.220', 'INTC', 'SELL', '5,000', '31.77', '158,850', 'NASDAQ'],
                        ['09:31:08.019', 'ORCL', 'BUY', '760', '148.63', '112,958', 'NYSE'],
                        ['09:31:08.744', 'CRM', 'BUY', '520', '268.15', '139,438', 'NYSE'],
                        ['09:31:09.503', 'ADBE', 'SELL', '280', '544.90', '152,572', 'NASDAQ'],
                    ]))
                    . '</tbody></table></div>',
                    '<div class="ft"><span class="mono">Last update 09:31:09.503</span><span>8,412 fills · $42.1M notional</span></div>'
                ),
            ],
            [
                'slug' => 'analytics-sparkline-table',
                'name' => 'Analytics table with sparklines',
                'name_ar' => 'جدول تحليلات مع رسوم مصغرة',
                'tagline' => 'Per-row trend charts beside the totals.',
                'summary' => 'Numbers say where you are; the sparkline says how you got there. Each row carries a fourteen-day trend drawn as inline SVG, so no chart library is involved and the file still prints cleanly.',
                'accent' => '#2563eb',
                'tags' => ['analytics', 'sparkline', 'trends', 'svg'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Inline SVG sparkline per row', 'Signed change against the previous period', 'Share of total as a meter'],
                'height' => 520,
                'max' => 920,
                'categories' => ['dashboards'],
                'css' => "tbody tr:hover{background:var(--soft)}\ntd .sp{display:flex;justify-content:flex-end}",
                'body' => self::wrap(
                    self::head('Traffic by channel', 'Last 14 days', self::btn('14 days', 'calendar') . self::btn('Export', 'download')),
                    self::grid(
                        ['Channel', 'Trend', 'Sessions', 'Share', 'Change'],
                        [
                            ['<span class="row">' . Kit::iconTile('search', '#2563eb', 32) . '<span class="bold">Organic search</span></span>', '<span class="sp">' . Kit::spark([12, 18, 15, 22, 26, 24, 31, 29, 34, 38, 36, 42, 45, 48], '#2563eb', 120, 36) . '</span>', '<span class="num bold">48,210</span>', '<span style="display:block;width:90px;margin-left:auto">' . Kit::meter(46, '#2563eb') . '</span>', Kit::delta('+18.4%', true)],
                            ['<span class="row">' . Kit::iconTile('link', '#7c3aed', 32) . '<span class="bold">Referral</span></span>', '<span class="sp">' . Kit::spark([20, 19, 22, 21, 24, 23, 25, 24, 22, 26, 28, 27, 30, 32], '#7c3aed', 120, 36) . '</span>', '<span class="num bold">21,904</span>', '<span style="display:block;width:90px;margin-left:auto">' . Kit::meter(21, '#7c3aed') . '</span>', Kit::delta('+6.1%', true)],
                            ['<span class="row">' . Kit::iconTile('users', '#059669', 32) . '<span class="bold">Social</span></span>', '<span class="sp">' . Kit::spark([30, 28, 26, 27, 24, 22, 23, 20, 19, 21, 18, 17, 16, 15], '#059669', 120, 36) . '</span>', '<span class="num bold">15,382</span>', '<span style="display:block;width:90px;margin-left:auto">' . Kit::meter(15, '#059669') . '</span>', Kit::delta('-9.7%', false)],
                            ['<span class="row">' . Kit::iconTile('mail', '#d97706', 32) . '<span class="bold">Email</span></span>', '<span class="sp">' . Kit::spark([8, 9, 11, 10, 12, 14, 13, 15, 17, 16, 18, 20, 19, 22], '#d97706', 120, 36) . '</span>', '<span class="num bold">12,047</span>', '<span style="display:block;width:90px;margin-left:auto">' . Kit::meter(12, '#d97706') . '</span>', Kit::delta('+24.9%', true)],
                            ['<span class="row">' . Kit::iconTile('zap', '#0891b2', 32) . '<span class="bold">Paid</span></span>', '<span class="sp">' . Kit::spark([16, 15, 17, 14, 13, 15, 12, 14, 11, 12, 10, 11, 9, 8], '#0891b2', 120, 36) . '</span>', '<span class="num bold">6,118</span>', '<span style="display:block;width:90px;margin-left:auto">' . Kit::meter(6, '#0891b2') . '</span>', Kit::delta('-31.2%', false)],
                        ],
                        ['right' => [2, 3]]
                    ),
                    '<div class="ft"><span>5 channels</span><span class="bold num">103,661 sessions</span></div>'
                ),
            ],
            [
                'slug' => 'audit-log-table',
                'name' => 'Audit log table',
                'name_ar' => 'جدول سجل التدقيق',
                'tagline' => 'Who did what, to which record, from where.',
                'summary' => 'An audit trail is only useful if each line answers actor, action, target and origin without being opened. Actions are colour-keyed by kind - created, updated, deleted - and IP plus user agent sit in the secondary line.',
                'accent' => '#475569',
                'tags' => ['audit', 'security', 'logs', 'compliance'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Action verbs colour-keyed by kind', 'Actor, target and origin on every line', 'Monospaced record ids for scanning'],
                'height' => 540,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}\n.verb{font-weight:700;font-size:12px;letter-spacing:.02em}",
                'body' => self::wrap(
                    self::head('Audit log', 'Retained for 24 months', self::search('Search events') . self::btn('Filter', 'filter')),
                    self::grid(
                        ['When', 'Actor', 'Action', 'Target', 'Origin'],
                        [
                            [self::two('12 Sep 2026', '09:41:08 UTC'), self::person('Omar Saleh', 'Admin', 30), '<span class="verb" style="color:var(--bad)">DELETED</span>', self::two('Customer record', '<span class="mono">cus_9f2ac41</span>'), self::two('102.44.18.7', 'Chrome · macOS')],
                            [self::two('12 Sep 2026', '09:12:44 UTC'), self::person('Maya Rahman', 'Editor', 30), '<span class="verb" style="color:var(--warn)">UPDATED</span>', self::two('Pricing plan', '<span class="mono">plan_studio</span>'), self::two('88.12.204.61', 'Firefox · Windows')],
                            [self::two('12 Sep 2026', '08:55:02 UTC'), self::person('Lina Haddad', 'Owner', 30), '<span class="verb" style="color:var(--ok)">CREATED</span>', self::two('API key', '<span class="mono">key_live_2f81</span>'), self::two('102.44.18.7', 'Safari · iOS')],
                            [self::two('11 Sep 2026', '23:04:19 UTC'), self::person('System', 'Automation', 30), '<span class="verb" style="color:var(--acc)">EXPORTED</span>', self::two('Monthly report', '<span class="mono">rep_2026_08</span>'), self::two('10.0.4.2', 'Scheduled job')],
                            [self::two('11 Sep 2026', '18:31:57 UTC'), self::person('Karim Nasser', 'Viewer', 30), '<span class="verb" style="color:var(--mut)">VIEWED</span>', self::two('Payroll sheet', '<span class="mono">doc_44f0b2</span>'), self::two('41.203.9.88', 'Edge · Windows')],
                        ]
                    ),
                    self::pager('5 of 128,402 events')
                ),
            ],
            [
                'slug' => 'api-keys-table',
                'name' => 'API keys table',
                'name_ar' => 'جدول مفاتيح الواجهة البرمجية',
                'tagline' => 'Masked secrets with reveal, copy and last-used.',
                'summary' => 'Keys are masked by default and revealed one at a time, because a screen-shared dashboard full of live secrets is the classic way they leak. Copy writes to the clipboard and confirms in place.',
                'accent' => '#0f766e',
                'tags' => ['api', 'keys', 'security', 'developer'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Masked keys with per-row reveal', 'Copy to clipboard with inline confirmation', 'Scope chips and last-used timestamps'],
                'height' => 520,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\n.key{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12.5px;background:var(--soft);padding:5px 9px;border-radius:7px;border:1px solid var(--bd)}",
                'js' => "document.querySelectorAll('[data-reveal]').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    const cell=button.closest('td').querySelector('.key');\n"
                    . "    const hidden=cell.dataset.masked==='1';\n"
                    . "    cell.textContent=hidden?cell.dataset.full:cell.dataset.mask;\n"
                    . "    cell.dataset.masked=hidden?'0':'1';\n"
                    . "    button.setAttribute('aria-label',hidden?'Hide key':'Reveal key');\n"
                    . "  });\n"
                    . "});\n"
                    . "document.querySelectorAll('[data-copy]').forEach(button=>{\n"
                    . "  button.addEventListener('click',async()=>{\n"
                    . "    const full=button.closest('td').querySelector('.key').dataset.full;\n"
                    . "    try{await navigator.clipboard.writeText(full);}catch{}\n"
                    . "    const was=button.innerHTML;\n"
                    . "    button.textContent='Copied';\n"
                    . "    setTimeout(()=>button.innerHTML=was,1400);\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('API keys', 'Live and test keys for this project', self::btn('Create key', 'plus', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Name</th><th>Key</th><th>Scopes</th><th>Last used</th><th class="right">State</th></tr></thead><tbody>'
                    . '<tr><td>' . self::two('Production server', 'Created 14 Mar 2026') . '</td>'
                    . '<td><span class="row" style="gap:6px"><code class="key" data-masked="1" data-mask="sk_live_••••••••••••2f81" data-full="sk_live_9f2ac41b77de02f81">sk_live_••••••••••••2f81</code>'
                    . '<button class="btn gh" type="button" data-reveal aria-label="Reveal key" style="padding:6px">' . Kit::icon('eye', 15) . '</button>'
                    . '<button class="btn gh" type="button" data-copy aria-label="Copy key" style="padding:6px">' . Kit::icon('copy', 15) . '</button></span></td>'
                    . '<td>' . Kit::pill('read', 'info') . ' ' . Kit::pill('write', 'info') . '</td><td class="mut num">4 minutes ago</td><td class="right">' . Kit::pill('Active', 'ok', true) . '</td></tr>'
                    . '<tr><td>' . self::two('Staging worker', 'Created 02 Jun 2026') . '</td>'
                    . '<td><span class="row" style="gap:6px"><code class="key" data-masked="1" data-mask="sk_test_••••••••••••7c04" data-full="sk_test_1b77de0c40f87c04">sk_test_••••••••••••7c04</code>'
                    . '<button class="btn gh" type="button" data-reveal aria-label="Reveal key" style="padding:6px">' . Kit::icon('eye', 15) . '</button>'
                    . '<button class="btn gh" type="button" data-copy aria-label="Copy key" style="padding:6px">' . Kit::icon('copy', 15) . '</button></span></td>'
                    . '<td>' . Kit::pill('read', 'info') . '</td><td class="mut num">2 days ago</td><td class="right">' . Kit::pill('Active', 'ok', true) . '</td></tr>'
                    . '<tr><td>' . self::two('Legacy import', 'Created 19 Nov 2024') . '</td>'
                    . '<td><span class="row" style="gap:6px"><code class="key" data-masked="1" data-mask="sk_live_••••••••••••a190" data-full="sk_live_c40f8a9e2201a190">sk_live_••••••••••••a190</code>'
                    . '<button class="btn gh" type="button" data-reveal aria-label="Reveal key" style="padding:6px">' . Kit::icon('eye', 15) . '</button>'
                    . '<button class="btn gh" type="button" data-copy aria-label="Copy key" style="padding:6px">' . Kit::icon('copy', 15) . '</button></span></td>'
                    . '<td>' . Kit::pill('read', 'info') . '</td><td class="mut num">11 months ago</td><td class="right">' . Kit::pill('Revoked', 'bad') . '</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>3 keys · rotate every 90 days</span><a href="#">Key rotation policy</a></div>'
                ),
            ],
            [
                'slug' => 'server-status-table',
                'name' => 'Server status table',
                'name_ar' => 'جدول حالة الخوادم',
                'tagline' => 'Fleet health with CPU, memory and uptime meters.',
                'summary' => 'One row per node, each carrying the three numbers an on-call engineer actually looks at, drawn as meters that turn amber and red at the thresholds rather than leaving a percentage to be interpreted.',
                'accent' => '#16a34a',
                'tags' => ['monitoring', 'servers', 'devops', 'status'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['CPU and memory meters with threshold colours', 'Region and uptime per node', 'Health chip driven by the worst metric'],
                'height' => 540,
                'max' => 940,
                'theme' => 'dark',
                'categories' => ['dashboards'],
                'css' => "tbody tr:hover{background:var(--soft)}\n.m{min-width:120px}\n.m span{display:block}\n.m .v{font-size:11.5px;color:var(--mut);margin-bottom:4px;font-variant-numeric:tabular-nums}",
                'body' => self::wrap(
                    self::head('Fleet status', '18 nodes · eu-west, us-east', Kit::pill('All systems operational', 'ok', true)),
                    self::grid(
                        ['Node', 'Region', 'CPU', 'Memory', 'Uptime', 'Health'],
                        [
                            [self::two('web-01', 'c6i.2xlarge'), 'eu-west-1a', '<span class="m"><span class="v">34%</span>' . Kit::meter(34, '#34d399') . '</span>', '<span class="m"><span class="v">61%</span>' . Kit::meter(61, '#34d399') . '</span>', '<span class="num">184d</span>', Kit::pill('Healthy', 'ok', true)],
                            [self::two('web-02', 'c6i.2xlarge'), 'eu-west-1b', '<span class="m"><span class="v">78%</span>' . Kit::meter(78, '#fbbf24') . '</span>', '<span class="m"><span class="v">72%</span>' . Kit::meter(72, '#fbbf24') . '</span>', '<span class="num">184d</span>', Kit::pill('Warning', 'warn', true)],
                            [self::two('db-primary', 'r6g.4xlarge'), 'eu-west-1a', '<span class="m"><span class="v">52%</span>' . Kit::meter(52, '#34d399') . '</span>', '<span class="m"><span class="v">88%</span>' . Kit::meter(88, '#f87171') . '</span>', '<span class="num">412d</span>', Kit::pill('At risk', 'bad', true)],
                            [self::two('worker-04', 'm6i.large'), 'us-east-1c', '<span class="m"><span class="v">12%</span>' . Kit::meter(12, '#34d399') . '</span>', '<span class="m"><span class="v">28%</span>' . Kit::meter(28, '#34d399') . '</span>', '<span class="num">61d</span>', Kit::pill('Healthy', 'ok', true)],
                            [self::two('cache-01', 'r6g.xlarge'), 'us-east-1a', '<span class="m"><span class="v">44%</span>' . Kit::meter(44, '#34d399') . '</span>', '<span class="m"><span class="v">55%</span>' . Kit::meter(55, '#34d399') . '</span>', '<span class="num">240d</span>', Kit::pill('Healthy', 'ok', true)],
                        ]
                    ),
                    '<div class="ft"><span>Polled every 30 seconds</span><span class="num">p95 latency 184ms</span></div>'
                ),
            ],
            [
                'slug' => 'project-tasks-table',
                'name' => 'Project tasks table',
                'name_ar' => 'جدول مهام المشروع',
                'tagline' => 'Task list with assignee, due date and progress.',
                'summary' => 'The list view of a project tool: a checkbox that strikes the row through, a due date that turns red once it is past, a progress meter and an assignee. Enough to run a small project without leaving the table.',
                'accent' => '#7c3aed',
                'tags' => ['tasks', 'project', 'checklist', 'progress'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Checkbox that completes the row in place', 'Overdue dates called out in red', 'Per-task progress meter'],
                'height' => 540,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.done td{opacity:.55}\ntr.done .title{text-decoration:line-through}\n.late{color:var(--bad);font-weight:650}",
                'js' => "document.querySelectorAll('tbody input[type=checkbox]').forEach(box=>{\n"
                    . "  box.addEventListener('change',()=>box.closest('tr').classList.toggle('done',box.checked));\n"
                    . "  box.dispatchEvent(new Event('change'));\n"
                    . '});',
                'body' => self::wrap(
                    self::head('Sprint 24 tasks', '8 of 21 done · ends Friday', self::btn('Add task', 'plus', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th style="width:44px"></th><th>Task</th><th>Assignee</th><th>Due</th><th>Progress</th><th class="right">Priority</th></tr></thead><tbody>'
                    . '<tr><td>' . self::check(false, 'Complete task') . '</td><td><span class="title bold">Rewrite the onboarding flow</span><div class="xs mut">Design · 3 subtasks</div></td><td>' . Kit::avatar('Lina Haddad', 28) . '</td><td class="num">26 Sep</td><td style="min-width:120px">' . Kit::meter(72, '#7c3aed') . '</td><td class="right">' . Kit::pill('High', 'warn') . '</td></tr>'
                    . '<tr><td>' . self::check(true, 'Complete task') . '</td><td><span class="title bold">Migrate the billing webhooks</span><div class="xs mut">Backend · done 2 days ago</div></td><td>' . Kit::avatar('Omar Saleh', 28) . '</td><td class="num">18 Sep</td><td style="min-width:120px">' . Kit::meter(100, '#059669') . '</td><td class="right">' . Kit::pill('Normal', 'info') . '</td></tr>'
                    . '<tr><td>' . self::check(false, 'Complete task') . '</td><td><span class="title bold">Accessibility audit of the editor</span><div class="xs mut">QA · blocked on design</div></td><td>' . Kit::avatar('Maya Rahman', 28) . '</td><td><span class="late num">12 Sep</span></td><td style="min-width:120px">' . Kit::meter(30, '#e11d48') . '</td><td class="right">' . Kit::pill('Urgent', 'bad') . '</td></tr>'
                    . '<tr><td>' . self::check(false, 'Complete task') . '</td><td><span class="title bold">Add Arabic copy to the gallery</span><div class="xs mut">Content</div></td><td>' . Kit::avatar('Sara Aziz', 28) . '</td><td class="num">02 Oct</td><td style="min-width:120px">' . Kit::meter(45, '#7c3aed') . '</td><td class="right">' . Kit::pill('Normal', 'info') . '</td></tr>'
                    . '<tr><td>' . self::check(false, 'Complete task') . '</td><td><span class="title bold">Cache the component previews</span><div class="xs mut">Platform</div></td><td>' . Kit::avatar('Karim Nasser', 28) . '</td><td class="num">04 Oct</td><td style="min-width:120px">' . Kit::meter(15, '#7c3aed') . '</td><td class="right">' . Kit::pill('Low', 'neutral') . '</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>5 of 21 tasks</span><span>Sprint burndown on track</span></div>'
                ),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private static function setTwo(): array
    {
        return [
            [
                'slug' => 'crypto-market-table',
                'name' => 'Crypto market table',
                'name_ar' => 'جدول سوق العملات الرقمية',
                'tagline' => 'Live prices, 24h change and market cap on a dark board.',
                'summary' => 'A market board reads best dark: green and red carry the change, the sparkline carries the shape of the day, and nothing else competes for attention. Prices use tabular figures so the columns stay still as they tick.',
                'accent' => '#f59e0b',
                'tags' => ['crypto', 'market', 'finance', 'dark'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Dark board with red and green change columns', '24-hour sparkline per asset', 'Market cap and volume in tabular figures'],
                'height' => 540,
                'max' => 940,
                'theme' => 'dark',
                'css' => "tbody tr:hover{background:var(--soft)}\n.up{color:var(--ok);font-weight:650}\n.down{color:var(--bad);font-weight:650}\n.sym{font-weight:700;letter-spacing:.02em}",
                'body' => self::wrap(
                    self::head('Markets', 'Spot · USD', self::search('Search assets') . Kit::pill('Live', 'ok', true)),
                    self::grid(
                        ['#', 'Asset', 'Price', '24h', '24h chart', 'Market cap'],
                        [
                            ['<span class="mut num">1</span>', '<span class="row">' . Kit::iconTile('zap', '#f59e0b', 32) . '<span><span class="sym" style="display:block">BTC</span><span class="xs mut">Bitcoin</span></span></span>', '<span class="num bold">$64,218.40</span>', '<span class="up num">+2.41%</span>', Kit::spark([58, 59, 61, 60, 63, 62, 64, 66, 65, 67, 69, 68, 70, 72], '#34d399', 110, 34), '<span class="num">$1.27T</span>'],
                            ['<span class="mut num">2</span>', '<span class="row">' . Kit::iconTile('box', '#6366f1', 32) . '<span><span class="sym" style="display:block">ETH</span><span class="xs mut">Ethereum</span></span></span>', '<span class="num bold">$3,412.88</span>', '<span class="up num">+1.08%</span>', Kit::spark([30, 31, 30, 32, 33, 32, 34, 33, 35, 34, 36, 37, 36, 38], '#34d399', 110, 34), '<span class="num">$410.2B</span>'],
                            ['<span class="mut num">3</span>', '<span class="row">' . Kit::iconTile('circle', '#0ea5e9', 32) . '<span><span class="sym" style="display:block">SOL</span><span class="xs mut">Solana</span></span></span>', '<span class="num bold">$148.02</span>', '<span class="down num">-4.72%</span>', Kit::spark([44, 43, 42, 43, 41, 40, 41, 39, 38, 37, 38, 36, 35, 34], '#f87171', 110, 34), '<span class="num">$69.4B</span>'],
                            ['<span class="mut num">4</span>', '<span class="row">' . Kit::iconTile('globe', '#22c55e', 32) . '<span><span class="sym" style="display:block">USDT</span><span class="xs mut">Tether</span></span></span>', '<span class="num bold">$1.0001</span>', '<span class="up num">+0.01%</span>', Kit::spark([50, 50, 51, 50, 50, 50, 51, 50, 50, 51, 50, 50, 50, 51], '#34d399', 110, 34), '<span class="num">$118.9B</span>'],
                            ['<span class="mut num">5</span>', '<span class="row">' . Kit::iconTile('star', '#a855f7', 32) . '<span><span class="sym" style="display:block">ADA</span><span class="xs mut">Cardano</span></span></span>', '<span class="num bold">$0.4182</span>', '<span class="down num">-1.94%</span>', Kit::spark([26, 27, 26, 25, 26, 24, 25, 24, 23, 24, 22, 23, 22, 21], '#f87171', 110, 34), '<span class="num">$14.8B</span>'],
                        ],
                        ['right' => [2, 3, 5]]
                    ),
                    '<div class="ft"><span>Prices delayed by 15 seconds</span><span class="num">Global cap $2.31T</span></div>'
                ),
            ],
            [
                'slug' => 'stock-watchlist-table',
                'name' => 'Stock watchlist',
                'name_ar' => 'جدول قائمة متابعة الأسهم',
                'tagline' => 'Watchlist rows with bid, ask, spread and day range.',
                'summary' => 'A watchlist built around the day range bar: the marker shows where the last trade sits between the low and the high, which is the one thing a plain price column cannot tell you.',
                'accent' => '#0891b2',
                'tags' => ['stocks', 'watchlist', 'trading'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Day-range bar with the last price marked', 'Bid, ask and spread columns', 'Signed change in both value and percent'],
                'height' => 520,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.up{color:var(--ok);font-weight:650}\n.down{color:var(--bad);font-weight:650}\n.range{position:relative;height:6px;border-radius:999px;background:var(--soft);min-width:110px}\n.range i{position:absolute;top:-3px;width:3px;height:12px;border-radius:2px;background:var(--acc)}\n.range b{position:absolute;inset:0;border-radius:999px;background:linear-gradient(90deg,var(--bad-bg),var(--ok-bg))}",
                'body' => self::wrap(
                    self::head('Watchlist', 'US equities · 16:00 close', self::btn('Add symbol', 'plus', 'pri')),
                    self::grid(
                        ['Symbol', 'Last', 'Change', 'Bid / Ask', 'Day range', 'Volume'],
                        [
                            [self::two('AAPL', 'Apple Inc.'), '<span class="num bold">214.38</span>', '<span class="up num">+2.14 (+1.01%)</span>', '<span class="num mut">214.36 / 214.40</span>', '<span class="range"><b></b><i style="left:78%"></i></span>', '<span class="num">54.2M</span>'],
                            [self::two('MSFT', 'Microsoft Corp.'), '<span class="num bold">431.20</span>', '<span class="down num">-3.88 (-0.89%)</span>', '<span class="num mut">431.18 / 431.24</span>', '<span class="range"><b></b><i style="left:31%"></i></span>', '<span class="num">22.8M</span>'],
                            [self::two('NVDA', 'NVIDIA Corp.'), '<span class="num bold">118.44</span>', '<span class="up num">+5.02 (+4.43%)</span>', '<span class="num mut">118.42 / 118.47</span>', '<span class="range"><b></b><i style="left:94%"></i></span>', '<span class="num">312.6M</span>'],
                            [self::two('TSLA', 'Tesla Inc.'), '<span class="num bold">248.71</span>', '<span class="down num">-6.31 (-2.48%)</span>', '<span class="num mut">248.66 / 248.75</span>', '<span class="range"><b></b><i style="left:12%"></i></span>', '<span class="num">98.1M</span>'],
                            [self::two('AMZN', 'Amazon.com Inc.'), '<span class="num bold">186.02</span>', '<span class="up num">+0.44 (+0.24%)</span>', '<span class="num mut">186.00 / 186.05</span>', '<span class="range"><b></b><i style="left:55%"></i></span>', '<span class="num">41.9M</span>'],
                        ],
                        ['right' => [1, 2, 5]]
                    ),
                    '<div class="ft"><span>5 symbols</span><span>Delayed quotes</span></div>'
                ),
            ],
            [
                'slug' => 'employee-directory-table',
                'name' => 'Employee directory',
                'name_ar' => 'جدول دليل الموظفين',
                'tagline' => 'Staff list grouped by department with contact shortcuts.',
                'summary' => 'A directory people actually use to reach someone: department group headers, a job title under each name, and mail and phone buttons at the end of the row instead of a details page two clicks away.',
                'accent' => '#0f766e',
                'tags' => ['hr', 'directory', 'employees', 'contacts'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Rows grouped under department headers', 'Mail and call shortcuts per person', 'Tenure and location in the secondary line'],
                'height' => 560,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.grp td{background:var(--soft);font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700;padding:8px 16px}\ntr.grp:hover td{background:var(--soft)}",
                'body' => self::wrap(
                    self::head('Directory', '142 people · 9 departments', self::search('Search staff')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Person</th><th>Title</th><th>Location</th><th>Started</th><th class="right">Contact</th></tr></thead><tbody>'
                    . '<tr class="grp"><td colspan="5">Engineering · 42</td></tr>'
                    . '<tr><td>' . self::person('Omar Saleh', 'omar@frugal.io') . '</td><td>Staff engineer</td><td>Amman</td><td class="num">Mar 2021</td><td class="right">' . self::actions('mail', 'phone') . '</td></tr>'
                    . '<tr><td>' . self::person('Nour Sabbagh', 'nour@frugal.io') . '</td><td>Platform engineer</td><td>Remote</td><td class="num">Aug 2023</td><td class="right">' . self::actions('mail', 'phone') . '</td></tr>'
                    . '<tr class="grp"><td colspan="5">Design · 11</td></tr>'
                    . '<tr><td>' . self::person('Lina Haddad', 'lina@frugal.io') . '</td><td>Head of design</td><td>Beirut</td><td class="num">Jan 2020</td><td class="right">' . self::actions('mail', 'phone') . '</td></tr>'
                    . '<tr><td>' . self::person('Sara Aziz', 'sara@frugal.io') . '</td><td>Product designer</td><td>Cairo</td><td class="num">Jun 2024</td><td class="right">' . self::actions('mail', 'phone') . '</td></tr>'
                    . '<tr class="grp"><td colspan="5">Support · 18</td></tr>'
                    . '<tr><td>' . self::person('Karim Nasser', 'karim@frugal.io') . '</td><td>Support lead</td><td>Dubai</td><td class="num">Sep 2022</td><td class="right">' . self::actions('mail', 'phone') . '</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>5 of 142 people</span><a href="#">Download the org chart</a></div>'
                ),
            ],
            [
                'slug' => 'file-manager-table',
                'name' => 'File manager list',
                'name_ar' => 'جدول مدير الملفات',
                'tagline' => 'Folders first, then files, with a breadcrumb path.',
                'summary' => 'The list half of a file manager: a breadcrumb that shows where you are, folders sorted above files the way every file browser does it, and coloured type tiles so the eye can find a PDF without reading the extension.',
                'accent' => '#2563eb',
                'tags' => ['files', 'manager', 'breadcrumb', 'storage'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Breadcrumb navigation above the list', 'Folders sorted before files', 'Colour-coded type tiles'],
                'height' => 560,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft);cursor:pointer}\n.crumbs{display:flex;align-items:center;gap:6px;padding:11px 20px;border-bottom:1px solid var(--bd);font-size:12.5px;color:var(--mut);flex-wrap:wrap}\n.crumbs b{color:var(--ink)}",
                'body' => self::wrap(
                    self::head('Files', '42.8 GB of 200 GB used', self::btn('New folder', 'folder') . self::btn('Upload', 'upload', 'pri')),
                    '<div class="crumbs"><a href="#">Workspace</a>' . Kit::icon('chevron-right', 13) . '<a href="#">Brand</a>' . Kit::icon('chevron-right', 13) . '<b>2026</b></div>',
                    self::grid(
                        ['Name', 'Owner', 'Modified', 'Size'],
                        [
                            ['<span class="row">' . Kit::iconTile('folder', '#f59e0b', 32) . '<span class="bold">Campaign assets</span></span>', Kit::avatar('Lina Haddad', 26), '<span class="mut num">12 Sep 2026</span>', '<span class="mut">18 items</span>'],
                            ['<span class="row">' . Kit::iconTile('folder', '#f59e0b', 32) . '<span class="bold">Logo exports</span></span>', Kit::avatar('Sara Aziz', 26), '<span class="mut num">08 Sep 2026</span>', '<span class="mut">6 items</span>'],
                            ['<span class="row">' . Kit::iconTile('file', '#dc2626', 32) . '<span class="bold">brand-guide-2026.pdf</span></span>', Kit::avatar('Lina Haddad', 26), '<span class="mut num">05 Sep 2026</span>', '<span class="num">4.2 MB</span>'],
                            ['<span class="row">' . Kit::iconTile('image', '#0891b2', 32) . '<span class="bold">hero-render.png</span></span>', Kit::avatar('Omar Saleh', 26), '<span class="mut num">04 Sep 2026</span>', '<span class="num">1.8 MB</span>'],
                            ['<span class="row">' . Kit::iconTile('chart', '#059669', 32) . '<span class="bold">q3-report.xlsx</span></span>', Kit::avatar('Maya Rahman', 26), '<span class="mut num">01 Sep 2026</span>', '<span class="num">812 KB</span>'],
                        ],
                        ['right' => [3]]
                    ),
                    '<div class="ft"><span>2 folders, 3 files</span><span>Synced a minute ago</span></div>'
                ),
            ],
            [
                'slug' => 'email-campaign-table',
                'name' => 'Email campaign results',
                'name_ar' => 'جدول نتائج الحملات البريدية',
                'tagline' => 'Sends, opens and clicks as rates rather than raw counts.',
                'summary' => 'Campaign reporting where the rates are the point: opens and clicks are drawn as meters against the list average, so a campaign that under-performed is obvious without doing arithmetic in your head.',
                'accent' => '#db2777',
                'tags' => ['email', 'marketing', 'campaigns', 'rates'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Open and click rates drawn as meters', 'Delivery state per campaign', 'Send volume and unsubscribes'],
                'height' => 540,
                'max' => 940,
                'categories' => ['dashboards'],
                'css' => "tbody tr:hover{background:var(--soft)}\n.rate{min-width:110px}\n.rate span.v{display:block;font-size:11.5px;color:var(--mut);margin-bottom:4px;font-variant-numeric:tabular-nums}",
                'body' => self::wrap(
                    self::head('Campaigns', 'Last 90 days · list of 48,210', self::btn('New campaign', 'plus', 'pri')),
                    self::grid(
                        ['Campaign', 'Sent', 'Opens', 'Clicks', 'Unsubs', 'State'],
                        [
                            [self::two('September product update', 'Sent 12 Sep'), '<span class="num">46,802</span>', '<span class="rate"><span class="v">42.1%</span>' . Kit::meter(42, '#db2777') . '</span>', '<span class="rate"><span class="v">8.4%</span>' . Kit::meter(8.4 * 4, '#db2777') . '</span>', '<span class="num">61</span>', Kit::pill('Sent', 'ok')],
                            [self::two('Back to work offer', 'Sent 02 Sep'), '<span class="num">44,120</span>', '<span class="rate"><span class="v">38.7%</span>' . Kit::meter(38.7, '#db2777') . '</span>', '<span class="rate"><span class="v">6.1%</span>' . Kit::meter(6.1 * 4, '#db2777') . '</span>', '<span class="num">88</span>', Kit::pill('Sent', 'ok')],
                            [self::two('Feature spotlight: templates', 'Sending now'), '<span class="num">12,004</span>', '<span class="rate"><span class="v">51.2%</span>' . Kit::meter(51, '#db2777') . '</span>', '<span class="rate"><span class="v">11.8%</span>' . Kit::meter(11.8 * 4, '#db2777') . '</span>', '<span class="num">9</span>', Kit::pill('Sending', 'warn', true)],
                            [self::two('Win-back sequence', 'Scheduled 24 Sep'), '<span class="mut">—</span>', '<span class="mut">—</span>', '<span class="mut">—</span>', '<span class="mut">—</span>', Kit::pill('Scheduled', 'info')],
                            [self::two('August newsletter', 'Sent 03 Aug'), '<span class="num">43,870</span>', '<span class="rate"><span class="v">29.4%</span>' . Kit::meter(29, '#db2777') . '</span>', '<span class="rate"><span class="v">3.2%</span>' . Kit::meter(3.2 * 4, '#db2777') . '</span>', '<span class="num">140</span>', Kit::pill('Sent', 'ok')],
                        ],
                        ['right' => [1, 4]]
                    ),
                    '<div class="ft"><span>5 campaigns</span><span>Average open rate 40.4%</span></div>'
                ),
            ],
            [
                'slug' => 'seo-keywords-table',
                'name' => 'SEO keyword rankings',
                'name_ar' => 'جدول ترتيب الكلمات المفتاحية',
                'tagline' => 'Position, movement, volume and the ranking URL.',
                'summary' => 'Rank tracking in one row: the current position as a disc coloured by page one, two or beyond, the movement since last week, and the URL that actually ranks - which is the column that tells you whether the right page is winning.',
                'accent' => '#16a34a',
                'tags' => ['seo', 'keywords', 'rankings', 'marketing'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Position disc coloured by SERP page', 'Weekly movement with direction', 'Ranking URL and search volume'],
                'height' => 540,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.pos{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;font-weight:700;font-size:13px}\n.p1{background:var(--ok-bg);color:var(--ok)}\n.p2{background:var(--warn-bg);color:var(--warn)}\n.p3{background:var(--soft);color:var(--mut)}\n.url{font-size:12px;color:var(--acc)}",
                'body' => self::wrap(
                    self::head('Keyword rankings', 'frugaldomain.site · Google, desktop', self::btn('Last 7 days', 'calendar') . self::btn('Export', 'download')),
                    self::grid(
                        ['Keyword', 'Position', 'Change', 'Volume', 'Difficulty', 'Ranking URL'],
                        [
                            ['<span class="bold">free svg editor online</span>', '<span class="pos p1">3</span>', Kit::delta('+4', true), '<span class="num">18,100</span>', '<span style="display:block;width:70px">' . Kit::meter(62, '#f59e0b') . '</span>', '<span class="url">/drower</span>'],
                            ['<span class="bold">html table template</span>', '<span class="pos p1">7</span>', Kit::delta('+1', true), '<span class="num">9,900</span>', '<span style="display:block;width:70px">' . Kit::meter(44, '#16a34a') . '</span>', '<span class="url">/components?c=tables</span>'],
                            ['<span class="bold">icon library download</span>', '<span class="pos p2">14</span>', Kit::delta('-3', false), '<span class="num">27,400</span>', '<span style="display:block;width:70px">' . Kit::meter(78, '#e11d48') . '</span>', '<span class="url">/IconsGalary</span>'],
                            ['<span class="bold">convert image to webp</span>', '<span class="pos p1">2</span>', Kit::delta('0', true), '<span class="num">40,500</span>', '<span style="display:block;width:70px">' . Kit::meter(55, '#f59e0b') . '</span>', '<span class="url">/ImageConvert</span>'],
                            ['<span class="bold">dashboard ui kit free</span>', '<span class="pos p3">28</span>', Kit::delta('+9', true), '<span class="num">6,600</span>', '<span style="display:block;width:70px">' . Kit::meter(69, '#f59e0b') . '</span>', '<span class="url">/components</span>'],
                        ],
                        ['right' => [3]]
                    ),
                    '<div class="ft"><span>5 of 412 tracked keywords</span><span>Average position 10.8</span></div>'
                ),
            ],
            [
                'slug' => 'bug-tracker-table',
                'name' => 'Bug tracker table',
                'name_ar' => 'جدول تتبع الأخطاء',
                'tagline' => 'Issues with severity, labels, reporter and age.',
                'summary' => 'An issue list that carries its own triage: severity as a left border, labels as chips, and an age column that quietly shames anything left open too long. The id is monospaced so it can be copied into a commit message.',
                'accent' => '#dc2626',
                'tags' => ['issues', 'bugs', 'engineering', 'triage'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Severity shown as a row border', 'Label chips per issue', 'Age column that highlights stale issues'],
                'height' => 540,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\ntbody td:first-child{border-left:3px solid transparent}\ntr[data-s=blocker] td:first-child{border-left-color:#dc2626}\ntr[data-s=major] td:first-child{border-left-color:#f59e0b}\ntr[data-s=minor] td:first-child{border-left-color:#0891b2}\n.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;color:var(--mut)}\n.stale{color:var(--bad);font-weight:650}",
                'body' => self::wrap(
                    self::head('Open issues', '74 open · 12 unassigned', self::search('Search issues') . self::btn('New issue', 'plus', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Issue</th><th>Labels</th><th>Reporter</th><th>Assignee</th><th>Severity</th><th class="right">Age</th></tr></thead><tbody>'
                    . '<tr data-s="blocker"><td>' . self::two('Canvas export drops clip masks', '<span class="mono">FRG-4821</span>') . '</td><td>' . Kit::pill('editor', 'info') . ' ' . Kit::pill('regression', 'bad') . '</td><td>' . Kit::avatar('Maya Rahman', 26) . '</td><td>' . Kit::avatar('Omar Saleh', 26) . '</td><td>' . Kit::pill('Blocker', 'bad') . '</td><td class="right"><span class="stale num">31 days</span></td></tr>'
                    . '<tr data-s="major"><td>' . self::two('Template panel scrolls to top', '<span class="mono">FRG-4809</span>') . '</td><td>' . Kit::pill('ui', 'info') . '</td><td>' . Kit::avatar('Sara Aziz', 26) . '</td><td>' . Kit::avatar('Lina Haddad', 26) . '</td><td>' . Kit::pill('Major', 'warn') . '</td><td class="right num">9 days</td></tr>'
                    . '<tr data-s="minor"><td>' . self::two('Arabic label clipped in toolbar', '<span class="mono">FRG-4802</span>') . '</td><td>' . Kit::pill('i18n', 'info') . ' ' . Kit::pill('good first issue', 'ok') . '</td><td>' . Kit::avatar('Karim Nasser', 26) . '</td><td><span class="mut xs">Unassigned</span></td><td>' . Kit::pill('Minor', 'info') . '</td><td class="right num">4 days</td></tr>'
                    . '<tr data-s="major"><td>' . self::two('Download counter double counts', '<span class="mono">FRG-4791</span>') . '</td><td>' . Kit::pill('api', 'info') . '</td><td>' . Kit::avatar('Omar Saleh', 26) . '</td><td>' . Kit::avatar('Nour Sabbagh', 26) . '</td><td>' . Kit::pill('Major', 'warn') . '</td><td class="right num">2 days</td></tr>'
                    . '<tr data-s="minor"><td>' . self::two('Tooltip stays open on touch', '<span class="mono">FRG-4788</span>') . '</td><td>' . Kit::pill('ui', 'info') . ' ' . Kit::pill('mobile', 'neutral') . '</td><td>' . Kit::avatar('Lina Haddad', 26) . '</td><td><span class="mut xs">Unassigned</span></td><td>' . Kit::pill('Minor', 'info') . '</td><td class="right num">17 hours</td></tr>'
                    . '</tbody></table></div>',
                    self::pager('5 of 74 open issues')
                ),
            ],
            [
                'slug' => 'shipment-tracking-table',
                'name' => 'Shipment tracking table',
                'name_ar' => 'جدول تتبع الشحنات',
                'tagline' => 'Every shipment with an inline progress track from pickup to delivery.',
                'summary' => 'Each row carries the whole journey as a four-stop track, so the question a tracking screen is opened with - how far along is it - is answered without opening anything. Late shipments turn their track red.',
                'accent' => '#7c3aed',
                'tags' => ['logistics', 'shipping', 'tracking', 'progress'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Four-stop progress track inside each row', 'Late shipments flagged on the track itself', 'Carrier, ETA and tracking number'],
                'height' => 560,
                'max' => 960,
                'css' => "tbody tr:hover{background:var(--soft)}\n.track{display:flex;align-items:center;gap:0;min-width:200px}\n.stop{width:12px;height:12px;border-radius:50%;background:var(--soft);border:2px solid var(--bd);flex:none;z-index:1}\n.stop.on{background:var(--acc);border-color:var(--acc)}\n.stop.late{background:var(--bad);border-color:var(--bad)}\n.leg{flex:1;height:2px;background:var(--bd)}\n.leg.on{background:var(--acc)}\n.leg.late{background:var(--bad)}\n.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px}",
                'body' => self::wrap(
                    self::head('Shipments', 'In transit · 312 parcels', self::search('Tracking number')),
                    self::grid(
                        ['Shipment', 'Carrier', 'Progress', 'ETA', 'State'],
                        [
                            [self::two('Order #3104', '<span class="mono mut">1Z9V8A7364</span>'), 'Aramex', '<span class="track"><i class="stop on"></i><b class="leg on"></b><i class="stop on"></i><b class="leg on"></b><i class="stop on"></i><b class="leg"></b><i class="stop"></i></span>', self::two('14 Sep', 'Out for delivery'), Kit::pill('In transit', 'info', true)],
                            [self::two('Order #3101', '<span class="mono mut">1Z9V8A7190</span>'), 'DHL', '<span class="track"><i class="stop on"></i><b class="leg on"></b><i class="stop on"></i><b class="leg on"></b><i class="stop on"></i><b class="leg on"></b><i class="stop on"></i></span>', self::two('12 Sep', 'Delivered 09:41'), Kit::pill('Delivered', 'ok', true)],
                            [self::two('Order #3098', '<span class="mono mut">1Z9V8A6802</span>'), 'FedEx', '<span class="track"><i class="stop on"></i><b class="leg on"></b><i class="stop late"></i><b class="leg late"></b><i class="stop"></i><b class="leg"></b><i class="stop"></i></span>', self::two('11 Sep', '<span style="color:var(--bad)">2 days late</span>'), Kit::pill('Delayed', 'bad', true)],
                            [self::two('Order #3094', '<span class="mono mut">1Z9V8A6544</span>'), 'Aramex', '<span class="track"><i class="stop on"></i><b class="leg"></b><i class="stop"></i><b class="leg"></b><i class="stop"></i><b class="leg"></b><i class="stop"></i></span>', self::two('16 Sep', 'Collected'), Kit::pill('Picked up', 'info', true)],
                            [self::two('Order #3090', '<span class="mono mut">1Z9V8A6120</span>'), 'DHL', '<span class="track"><i class="stop on"></i><b class="leg on"></b><i class="stop on"></i><b class="leg"></b><i class="stop"></i><b class="leg"></b><i class="stop"></i></span>', self::two('15 Sep', 'At hub'), Kit::pill('In transit', 'info', true)],
                        ]
                    ),
                    '<div class="ft"><span>5 of 312 shipments</span><span>Pickup · Hub · Out for delivery · Delivered</span></div>'
                ),
            ],
            [
                'slug' => 'student-grades-table',
                'name' => 'Student grades table',
                'name_ar' => 'جدول درجات الطلاب',
                'tagline' => 'Marks per assessment with a weighted final grade.',
                'summary' => 'A gradebook row reads left to right as the term progressed, ending in a letter grade on a coloured disc. Missing work is a dash rather than a zero, because the two mean very different things to a teacher.',
                'accent' => '#4f46e5',
                'tags' => ['education', 'grades', 'school', 'gradebook'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Assessment columns with weightings in the header', 'Letter grade disc coloured by band', 'Missing work distinguished from a zero'],
                'height' => 540,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\nth small{display:block;font-weight:500;text-transform:none;letter-spacing:0;font-size:10.5px;opacity:.75}\n.g{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;font-weight:700}\n.ga{background:var(--ok-bg);color:var(--ok)}\n.gb{background:var(--acc-soft);color:var(--acc)}\n.gc{background:var(--warn-bg);color:var(--warn)}\n.gf{background:var(--bad-bg);color:var(--bad)}",
                'body' => self::wrap(
                    self::head('Grade 10 · Physics', 'Term 2 · 28 students', self::btn('Export', 'download')),
                    self::grid(
                        ['Student', 'Quiz 1<small>10%</small>', 'Lab<small>20%</small>', 'Midterm<small>30%</small>', 'Final<small>40%</small>', 'Result'],
                        [
                            [self::person('Rana Khalil', 'ID 10-214'), '<span class="num">92</span>', '<span class="num">88</span>', '<span class="num">95</span>', '<span class="num">91</span>', '<span class="g ga">A</span>'],
                            [self::person('Yusuf Barak', 'ID 10-218'), '<span class="num">74</span>', '<span class="num">81</span>', '<span class="num">69</span>', '<span class="num">77</span>', '<span class="g gb">B</span>'],
                            [self::person('Hala Mansour', 'ID 10-221'), '<span class="num">61</span>', '<span class="mut">—</span>', '<span class="num">58</span>', '<span class="num">66</span>', '<span class="g gc">C</span>'],
                            [self::person('Tarek Fadel', 'ID 10-229'), '<span class="num">88</span>', '<span class="num">94</span>', '<span class="num">84</span>', '<span class="num">89</span>', '<span class="g ga">A</span>'],
                            [self::person('Nadia Osman', 'ID 10-233'), '<span class="num">42</span>', '<span class="num">51</span>', '<span class="mut">—</span>', '<span class="num">39</span>', '<span class="g gf">F</span>'],
                        ],
                        ['right' => [1, 2, 3, 4]]
                    ),
                    '<div class="ft"><span>5 of 28 students</span><span>Class average 74.6</span></div>'
                ),
            ],
            [
                'slug' => 'attendance-grid-table',
                'name' => 'Attendance grid',
                'name_ar' => 'جدول الحضور',
                'tagline' => 'A month of attendance as a dot grid per person.',
                'summary' => 'Thirty cells to a row, each a coloured dot: present, remote, absent or holiday. A month of attendance for a whole team fits on one screen, and the pattern - every Monday off, say - is visible in a way a list of dates never is.',
                'accent' => '#0d9488',
                'tags' => ['hr', 'attendance', 'calendar', 'grid'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['One dot per day with a four-state legend', 'Whole month on a single screen', 'Totals column per person'],
                'height' => 520,
                'max' => 960,
                'css' => ".days{display:flex;gap:3px}\n.d{width:13px;height:13px;border-radius:4px;background:var(--soft);flex:none}\n.d.p{background:#0d9488}\n.d.r{background:#60a5fa}\n.d.a{background:#f43f5e}\n.d.h{background:var(--bd)}\n.legend{display:flex;gap:14px;flex-wrap:wrap;font-size:11.5px;color:var(--mut)}\n.legend span{display:inline-flex;align-items:center;gap:5px}\ntbody tr:hover{background:var(--soft)}",
                'body' => self::wrap(
                    self::head('Attendance', 'September 2026', '<div class="legend"><span><i class="d p"></i>Office</span><span><i class="d r"></i>Remote</span><span><i class="d a"></i>Absent</span><span><i class="d h"></i>Holiday</span></div>'),
                    self::grid(
                        ['Person', 'September', 'Office', 'Remote', 'Absent'],
                        array_map(function ($row) {
                            [$name, $meta, $pattern] = $row;
                            $dots = '';
                            foreach (str_split($pattern) as $day) {
                                $dots .= '<i class="d ' . $day . '"></i>';
                            }
                            $counts = array_count_values(str_split($pattern));

                            return [
                                self::person($name, $meta, 30),
                                '<span class="days">' . $dots . '</span>',
                                '<span class="num bold">' . ($counts['p'] ?? 0) . '</span>',
                                '<span class="num">' . ($counts['r'] ?? 0) . '</span>',
                                '<span class="num">' . ($counts['a'] ?? 0) . '</span>',
                            ];
                        }, [
                            ['Lina Haddad', 'Design', 'pprphhpprrphhppprphhppprpphhpp'],
                            ['Omar Saleh', 'Engineering', 'rrrrphhrrrrphhrrrrphhrrrrphhrr'],
                            ['Maya Rahman', 'QA', 'ppaphhppprphhpppprhhpppppphhpp'],
                            ['Karim Nasser', 'Support', 'ppprphhppprphhaaaprhhppprphhpp'],
                            ['Sara Aziz', 'Content', 'rprprhhrprprhhrprprhhrprprhhrp'],
                        ]),
                        ['right' => [2, 3, 4]]
                    ),
                    '<div class="ft"><span>5 of 142 people</span><span>Team attendance 91.4%</span></div>'
                ),
            ],
            [
                'slug' => 'expense-claims-table',
                'name' => 'Expense claims table',
                'name_ar' => 'جدول مطالبات المصاريف',
                'tagline' => 'Claims with receipts, approval state and inline approve.',
                'summary' => 'An approver queue: the receipt is a link rather than an attachment you have to hunt for, and approve and reject sit in the row so a batch of small claims can be cleared without opening any of them.',
                'accent' => '#059669',
                'tags' => ['finance', 'expenses', 'approval', 'workflow'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Approve and reject buttons in the row', 'Receipt links with a page count', 'Policy breach flagged on the amount'],
                'height' => 540,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.over{color:var(--bad);font-weight:700}\n.rc{display:inline-flex;align-items:center;gap:5px;font-size:12px;color:var(--acc)}",
                'body' => self::wrap(
                    self::head('Expense claims', '12 awaiting your approval', self::btn('Approve all', 'check', 'pri')),
                    self::grid(
                        ['Claimant', 'Description', 'Category', 'Receipt', 'Amount', 'Decision'],
                        [
                            [self::person('Omar Saleh', 'Engineering', 30), self::two('Conference travel', '12-14 Sep · Berlin'), Kit::pill('Travel', 'info'), '<a class="rc" href="#">' . Kit::icon('file', 14) . '3 pages</a>', '<span class="num bold">$1,284.00</span>', '<span class="row" style="gap:6px;justify-content:flex-end">' . self::btn('Approve', 'check', 'pri') . self::btn('Reject', 'x') . '</span>'],
                            [self::person('Sara Aziz', 'Content', 30), self::two('Stock photography', 'Annual licence'), Kit::pill('Software', 'info'), '<a class="rc" href="#">' . Kit::icon('file', 14) . '1 page</a>', '<span class="num bold">$349.00</span>', '<span class="row" style="gap:6px;justify-content:flex-end">' . self::btn('Approve', 'check', 'pri') . self::btn('Reject', 'x') . '</span>'],
                            [self::person('Karim Nasser', 'Support', 30), self::two('Client dinner', '6 guests'), Kit::pill('Meals', 'info'), '<a class="rc" href="#">' . Kit::icon('file', 14) . '2 pages</a>', '<span class="over num">$642.80</span>', '<span class="row" style="gap:6px;justify-content:flex-end">' . self::btn('Approve', 'check', 'pri') . self::btn('Reject', 'x') . '</span>'],
                            [self::person('Maya Rahman', 'QA', 30), self::two('Device for testing', 'Pixel 9'), Kit::pill('Hardware', 'info'), '<a class="rc" href="#">' . Kit::icon('file', 14) . '1 page</a>', '<span class="num bold">$799.00</span>', '<span class="row" style="gap:6px;justify-content:flex-end">' . self::btn('Approve', 'check', 'pri') . self::btn('Reject', 'x') . '</span>'],
                        ],
                        ['right' => [4, 5]]
                    ),
                    '<div class="ft"><span>Claims over $500 need a second approver</span><span class="bold num">$3,074.80 pending</span></div>'
                ),
            ],
            [
                'slug' => 'payroll-run-table',
                'name' => 'Payroll run table',
                'name_ar' => 'جدول مسير الرواتب',
                'tagline' => 'Gross, deductions and net pay with a run summary.',
                'summary' => 'A payroll run reviewed before it is committed: gross across, deductions broken out rather than lumped, and net in bold at the end. The footer totals the whole run so the transfer amount is never re-derived by hand.',
                'accent' => '#1d4ed8',
                'tags' => ['payroll', 'hr', 'finance', 'salary'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Deductions broken out per employee', 'Net pay emphasised over gross', 'Run totals in the footer'],
                'height' => 540,
                'max' => 960,
                'css' => "tbody tr:hover{background:var(--soft)}\ntfoot td{padding:13px 16px;border-top:2px solid var(--bd);font-weight:700;background:var(--soft)}",
                'body' => self::wrap(
                    self::head('Payroll · September 2026', 'Draft run · pays on 28 Sep', Kit::pill('Awaiting approval', 'warn') . self::btn('Approve run', 'check', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Employee</th><th class="right">Gross</th><th class="right">Tax</th><th class="right">Social</th><th class="right">Other</th><th class="right">Net</th></tr></thead><tbody>'
                    . '<tr><td>' . self::person('Lina Haddad', 'Head of design') . '</td><td class="right num">$9,400.00</td><td class="right num mut">-$1,692.00</td><td class="right num mut">-$658.00</td><td class="right num mut">-$120.00</td><td class="right num bold">$6,930.00</td></tr>'
                    . '<tr><td>' . self::person('Omar Saleh', 'Staff engineer') . '</td><td class="right num">$11,200.00</td><td class="right num mut">-$2,240.00</td><td class="right num mut">-$784.00</td><td class="right num mut">-$120.00</td><td class="right num bold">$8,056.00</td></tr>'
                    . '<tr><td>' . self::person('Maya Rahman', 'QA lead') . '</td><td class="right num">$7,800.00</td><td class="right num mut">-$1,326.00</td><td class="right num mut">-$546.00</td><td class="right num mut">-$80.00</td><td class="right num bold">$5,848.00</td></tr>'
                    . '<tr><td>' . self::person('Karim Nasser', 'Support lead') . '</td><td class="right num">$6,200.00</td><td class="right num mut">-$992.00</td><td class="right num mut">-$434.00</td><td class="right num mut">-$80.00</td><td class="right num bold">$4,694.00</td></tr>'
                    . '</tbody><tfoot><tr><td>4 employees</td><td class="right num">$34,600.00</td><td class="right num">-$6,250.00</td><td class="right num">-$2,422.00</td><td class="right num">-$400.00</td><td class="right num">$25,528.00</td></tr></tfoot></table></div>',
                    '<div class="ft"><span>Bank file generated on approval</span><span>Cost to company $38,412.00</span></div>'
                ),
            ],
            [
                'slug' => 'hotel-bookings-table',
                'name' => 'Hotel bookings table',
                'name_ar' => 'جدول حجوزات الفندق',
                'tagline' => 'Reservations with stay dates, room type and balance.',
                'summary' => 'A front-desk list: check-in and check-out with the night count between them, the room and rate, and what is still owed. Arrivals due today are pulled out with a tinted row rather than a separate screen.',
                'accent' => '#b45309',
                'tags' => ['hotel', 'bookings', 'hospitality', 'reservations'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Stay dates with the night count derived', 'Today arrivals highlighted in place', 'Outstanding balance per booking'],
                'height' => 540,
                'max' => 960,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.today{background:var(--acc-soft)}\ntr.today:hover{background:var(--acc-soft)}\n.stay{display:inline-flex;align-items:center;gap:8px}\n.stay b{font-weight:650}\n.owe{color:var(--bad);font-weight:700}",
                'body' => self::wrap(
                    self::head('Bookings', '18 arrivals today · 64% occupancy', self::search('Guest name') . self::btn('New booking', 'plus', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Guest</th><th>Stay</th><th>Room</th><th>Guests</th><th>Status</th><th class="right">Balance</th></tr></thead><tbody>'
                    . '<tr class="today"><td>' . self::person('Rana Khalil', 'Booking #88412') . '</td><td><span class="stay"><b class="num">20 Sep</b>' . Kit::icon('arrow-right', 13) . '<b class="num">24 Sep</b><span class="xs mut">4 nights</span></span></td><td>' . self::two('Deluxe king', 'Room 412') . '</td><td class="num">2</td><td>' . Kit::pill('Arriving today', 'info', true) . '</td><td class="right num bold">$0.00</td></tr>'
                    . '<tr class="today"><td>' . self::person('Tarek Fadel', 'Booking #88407') . '</td><td><span class="stay"><b class="num">20 Sep</b>' . Kit::icon('arrow-right', 13) . '<b class="num">22 Sep</b><span class="xs mut">2 nights</span></span></td><td>' . self::two('Twin standard', 'Room 218') . '</td><td class="num">2</td><td>' . Kit::pill('Arriving today', 'info', true) . '</td><td class="right owe num">$310.00</td></tr>'
                    . '<tr><td>' . self::person('Hala Mansour', 'Booking #88390') . '</td><td><span class="stay"><b class="num">18 Sep</b>' . Kit::icon('arrow-right', 13) . '<b class="num">25 Sep</b><span class="xs mut">7 nights</span></span></td><td>' . self::two('Suite', 'Room 901') . '</td><td class="num">3</td><td>' . Kit::pill('In house', 'ok', true) . '</td><td class="right num bold">$0.00</td></tr>'
                    . '<tr><td>' . self::person('Yusuf Barak', 'Booking #88361') . '</td><td><span class="stay"><b class="num">26 Sep</b>' . Kit::icon('arrow-right', 13) . '<b class="num">28 Sep</b><span class="xs mut">2 nights</span></span></td><td>' . self::two('Deluxe twin', 'Unassigned') . '</td><td class="num">1</td><td>' . Kit::pill('Confirmed', 'neutral') . '</td><td class="right owe num">$248.00</td></tr>'
                    . '<tr><td>' . self::person('Nadia Osman', 'Booking #88344') . '</td><td><span class="stay"><b class="num">14 Sep</b>' . Kit::icon('arrow-right', 13) . '<b class="num">19 Sep</b><span class="xs mut">5 nights</span></span></td><td>' . self::two('Standard queen', 'Room 305') . '</td><td class="num">2</td><td>' . Kit::pill('Checked out', 'neutral') . '</td><td class="right num bold">$0.00</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>5 of 214 bookings</span><span class="bold num">Outstanding $558.00</span></div>'
                ),
            ],
            [
                'slug' => 'flight-schedule-table',
                'name' => 'Flight schedule board',
                'name_ar' => 'جدول مواعيد الرحلات',
                'tagline' => 'Departures board with gate, status and delay.',
                'summary' => 'An airport board translated to the web: scheduled time struck through when it changes, the revised time beside it, and status in the colour the terminal screens use. Dark by default, the way these are always read.',
                'accent' => '#0ea5e9',
                'tags' => ['travel', 'flights', 'schedule', 'dark'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Revised times shown against the scheduled one', 'Status colours matching airport convention', 'Gate and terminal columns'],
                'height' => 520,
                'max' => 940,
                'theme' => 'dark',
                'css' => "tbody tr:hover{background:var(--soft)}\n.was{text-decoration:line-through;color:var(--faint);margin-right:7px}\n.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;letter-spacing:.04em}\n.gate{display:inline-flex;align-items:center;justify-content:center;min-width:42px;padding:4px 8px;border-radius:8px;background:var(--soft);font-weight:700}",
                'body' => self::wrap(
                    self::head('Departures', 'Terminal 3 · 20 September', Kit::pill('Updated 12s ago', 'ok', true)),
                    self::grid(
                        ['Time', 'Flight', 'Destination', 'Gate', 'Status'],
                        [
                            ['<span class="num bold">09:40</span>', '<span class="mono">FR 2281</span>', self::two('Dubai', 'DXB'), '<span class="gate">A12</span>', Kit::pill('Boarding', 'ok', true)],
                            ['<span class="was num">10:05</span><span class="num bold">10:50</span>', '<span class="mono">MS 4410</span>', self::two('Cairo', 'CAI'), '<span class="gate">B04</span>', Kit::pill('Delayed', 'warn', true)],
                            ['<span class="num bold">11:15</span>', '<span class="mono">RJ 118</span>', self::two('Amman', 'AMM'), '<span class="gate">A07</span>', Kit::pill('On time', 'info')],
                            ['<span class="num bold">11:55</span>', '<span class="mono">TK 802</span>', self::two('Istanbul', 'IST'), '<span class="gate">C21</span>', Kit::pill('Gate closed', 'bad', true)],
                            ['<span class="num bold">12:30</span>', '<span class="mono">QR 1044</span>', self::two('Doha', 'DOH'), '<span class="gate">—</span>', Kit::pill('Scheduled', 'neutral')],
                        ]
                    ),
                    '<div class="ft"><span>Next 5 departures</span><span>All times local</span></div>'
                ),
            ],
            [
                'slug' => 'restaurant-menu-table',
                'name' => 'Restaurant menu table',
                'name_ar' => 'جدول قائمة المطعم',
                'tagline' => 'Dishes with dietary marks, calories and price.',
                'summary' => 'A menu as a table, which is what a kitchen or a back office actually needs: allergen and dietary marks as chips, calories beside the price, and availability that can be switched off for the evening without deleting the dish.',
                'accent' => '#c2410c',
                'tags' => ['restaurant', 'menu', 'food', 'pricing'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Dietary and allergen chips per dish', 'Availability toggle per row', 'Course group headers'],
                'height' => 560,
                'max' => 880,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.grp td{background:var(--soft);font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700;padding:8px 16px}\n.sw{position:relative;display:inline-block;width:38px;height:21px}\n.sw input{opacity:0;width:0;height:0}\n.sw i{position:absolute;inset:0;border-radius:999px;background:var(--bd);transition:.2s}\n.sw i::before{content:'';position:absolute;width:15px;height:15px;left:3px;top:3px;border-radius:50%;background:#fff;transition:.2s}\n.sw input:checked+i{background:var(--acc)}\n.sw input:checked+i::before{transform:translateX(17px)}\n.off td:not(:last-child){opacity:.45}",
                'body' => self::wrap(
                    self::head('Menu', 'Dinner service · updated today', self::btn('Add dish', 'plus', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Dish</th><th>Diet</th><th class="right">Calories</th><th class="right">Price</th><th class="right">Available</th></tr></thead><tbody>'
                    . '<tr class="grp"><td colspan="5">Starters</td></tr>'
                    . '<tr><td>' . self::two('Burrata &amp; heirloom tomato', 'Basil oil, sourdough') . '</td><td>' . Kit::pill('Vegetarian', 'ok') . ' ' . Kit::pill('Gluten', 'warn') . '</td><td class="right num">420</td><td class="right num bold">$14.00</td><td class="right"><label class="sw"><input type="checkbox" checked aria-label="Available"><i></i></label></td></tr>'
                    . '<tr><td>' . self::two('Charred octopus', 'Smoked paprika, potato') . '</td><td>' . Kit::pill('Seafood', 'info') . '</td><td class="right num">380</td><td class="right num bold">$18.00</td><td class="right"><label class="sw"><input type="checkbox" checked aria-label="Available"><i></i></label></td></tr>'
                    . '<tr class="grp"><td colspan="5">Mains</td></tr>'
                    . '<tr><td>' . self::two('Dry-aged ribeye', '300g, bone marrow butter') . '</td><td>' . Kit::pill('Dairy', 'warn') . '</td><td class="right num">910</td><td class="right num bold">$42.00</td><td class="right"><label class="sw"><input type="checkbox" checked aria-label="Available"><i></i></label></td></tr>'
                    . '<tr class="off"><td>' . self::two('Line-caught sea bass', 'Fennel, saffron broth') . '</td><td>' . Kit::pill('Seafood', 'info') . '</td><td class="right num">540</td><td class="right num bold">$34.00</td><td class="right"><label class="sw"><input type="checkbox" aria-label="Available"><i></i></label></td></tr>'
                    . '<tr><td>' . self::two('Wild mushroom risotto', 'Aged parmesan, truffle') . '</td><td>' . Kit::pill('Vegetarian', 'ok') . ' ' . Kit::pill('Dairy', 'warn') . '</td><td class="right num">670</td><td class="right num bold">$26.00</td><td class="right"><label class="sw"><input type="checkbox" checked aria-label="Available"><i></i></label></td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>5 of 34 dishes</span><span>1 dish unavailable tonight</span></div>'
                ),
            ],
            [
                'slug' => 'property-listings-table',
                'name' => 'Property listings table',
                'name_ar' => 'جدول العقارات',
                'tagline' => 'Listings with a drawn thumbnail, price and price per m².',
                'summary' => 'Property rows need a picture, and this one draws it: a CSS gradient tile with the property type marked on it, so the table keeps its visual rhythm without a single image request. Price per square metre is the column agents compare on.',
                'accent' => '#0f766e',
                'tags' => ['real-estate', 'listings', 'property'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Gradient thumbnail tiles - no image files', 'Price per square metre derived per row', 'Listing state and days on market'],
                'height' => 560,
                'max' => 960,
                'css' => "tbody tr:hover{background:var(--soft)}\n.thumb{width:58px;height:44px;border-radius:9px;flex:none;display:flex;align-items:flex-end;padding:5px;color:#fff}\n.thumb span{font-size:10px;font-weight:700;letter-spacing:.04em;text-shadow:0 1px 2px rgba(0,0,0,.35)}\n.spec{display:flex;gap:12px;font-size:12px;color:var(--mut)}\n.spec b{color:var(--ink);font-weight:650}",
                'body' => self::wrap(
                    self::head('Listings', '64 active · Riyadh', self::search('Search listings') . self::btn('Add listing', 'plus', 'pri')),
                    self::grid(
                        ['Property', 'Specification', 'Price', 'Per m²', 'Days listed', 'State'],
                        [
                            ['<span class="row">' . '<span class="thumb" style="background:linear-gradient(140deg,#0f766e,#14b8a6)"><span>VILLA</span></span>' . '<span>' . self::two('Al Nakheel villa', 'Al Nakheel · 420 m²') . '</span></span>', '<span class="spec"><span><b>5</b> beds</span><span><b>6</b> baths</span><span><b>2</b> floors</span></span>', '<span class="num bold">SAR 4,200,000</span>', '<span class="num">10,000</span>', '<span class="num">12</span>', Kit::pill('Active', 'ok', true)],
                            ['<span class="row">' . '<span class="thumb" style="background:linear-gradient(140deg,#1d4ed8,#60a5fa)"><span>APT</span></span>' . '<span>' . self::two('Olaya tower apartment', 'Olaya · 180 m²') . '</span></span>', '<span class="spec"><span><b>3</b> beds</span><span><b>3</b> baths</span><span><b>18</b>th floor</span></span>', '<span class="num bold">SAR 1,620,000</span>', '<span class="num">9,000</span>', '<span class="num">4</span>', Kit::pill('Active', 'ok', true)],
                            ['<span class="row">' . '<span class="thumb" style="background:linear-gradient(140deg,#b45309,#f59e0b)"><span>LAND</span></span>' . '<span>' . self::two('Corner plot, Al Yasmin', 'Al Yasmin · 900 m²') . '</span></span>', '<span class="spec"><span><b>Residential</b></span><span><b>2</b> streets</span></span>', '<span class="num bold">SAR 2,700,000</span>', '<span class="num">3,000</span>', '<span class="num">61</span>', Kit::pill('Reduced', 'warn')],
                            ['<span class="row">' . '<span class="thumb" style="background:linear-gradient(140deg,#7c3aed,#c084fc)"><span>OFFICE</span></span>' . '<span>' . self::two('King Fahd office floor', 'King Fahd Rd · 640 m²') . '</span></span>', '<span class="spec"><span><b>Open plan</b></span><span><b>24</b> parking</span></span>', '<span class="num bold">SAR 5,120,000</span>', '<span class="num">8,000</span>', '<span class="num">28</span>', Kit::pill('Under offer', 'info', true)],
                            ['<span class="row">' . '<span class="thumb" style="background:linear-gradient(140deg,#be123c,#fb7185)"><span>SHOP</span></span>' . '<span>' . self::two('Retail unit, Al Malqa', 'Al Malqa · 120 m²') . '</span></span>', '<span class="spec"><span><b>Ground</b> floor</span><span><b>6</b>m frontage</span></span>', '<span class="num bold">SAR 960,000</span>', '<span class="num">8,000</span>', '<span class="num">96</span>', Kit::pill('Stale', 'bad')],
                        ],
                        ['right' => [2, 3, 4]]
                    ),
                    '<div class="ft"><span>5 of 64 listings</span><span>Median SAR 8,500 per m²</span></div>'
                ),
            ],
            [
                'slug' => 'applicants-pipeline-table',
                'name' => 'Applicants pipeline table',
                'name_ar' => 'جدول مسار المتقدمين',
                'tagline' => 'Candidates with an inline stage tracker and score.',
                'summary' => 'Hiring is a pipeline, so each row shows where the candidate sits in it as a five-stop tracker rather than as a word. The score is a rating out of five, and rejected rows fade back without disappearing.',
                'accent' => '#4f46e5',
                'tags' => ['hiring', 'recruitment', 'pipeline', 'hr'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Five-stage tracker drawn in the row', 'Interview score as stars', 'Rejected candidates faded rather than hidden'],
                'height' => 560,
                'max' => 960,
                'css' => "tbody tr:hover{background:var(--soft)}\n.stages{display:flex;gap:4px}\n.st{width:26px;height:6px;border-radius:999px;background:var(--bd)}\n.st.on{background:var(--acc)}\n.st.no{background:var(--bad)}\ntr.out td:not(:last-child){opacity:.5}",
                'body' => self::wrap(
                    self::head('Senior front-end engineer', '42 applicants · 6 in interview', self::btn('Move stage', 'arrow-right') . self::btn('Add candidate', 'plus', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Candidate</th><th>Source</th><th>Stage</th><th>Score</th><th>Applied</th><th class="right">Next step</th></tr></thead><tbody>'
                    . '<tr><td>' . self::person('Rana Khalil', 'rana.k@mail.com') . '</td><td>' . Kit::pill('Referral', 'ok') . '</td><td><span class="stages"><i class="st on"></i><i class="st on"></i><i class="st on"></i><i class="st on"></i><i class="st"></i></span><span class="xs mut">Final interview</span></td><td>' . Kit::stars(4.5) . '</td><td class="mut num">02 Sep</td><td class="right">' . self::two('Offer review', 'Thu 14:00') . '</td></tr>'
                    . '<tr><td>' . self::person('Yusuf Barak', 'y.barak@mail.com') . '</td><td>' . Kit::pill('LinkedIn', 'info') . '</td><td><span class="stages"><i class="st on"></i><i class="st on"></i><i class="st on"></i><i class="st"></i><i class="st"></i></span><span class="xs mut">Technical</span></td><td>' . Kit::stars(4) . '</td><td class="mut num">05 Sep</td><td class="right">' . self::two('System design', 'Mon 11:00') . '</td></tr>'
                    . '<tr><td>' . self::person('Hala Mansour', 'hala.m@mail.com') . '</td><td>' . Kit::pill('Careers page', 'neutral') . '</td><td><span class="stages"><i class="st on"></i><i class="st on"></i><i class="st"></i><i class="st"></i><i class="st"></i></span><span class="xs mut">Screening</span></td><td>' . Kit::stars(3.5) . '</td><td class="mut num">09 Sep</td><td class="right">' . self::two('Call to book', 'This week') . '</td></tr>'
                    . '<tr class="out"><td>' . self::person('Tarek Fadel', 't.fadel@mail.com') . '</td><td>' . Kit::pill('Agency', 'warn') . '</td><td><span class="stages"><i class="st on"></i><i class="st on"></i><i class="st no"></i><i class="st"></i><i class="st"></i></span><span class="xs mut">Rejected</span></td><td>' . Kit::stars(2) . '</td><td class="mut num">01 Sep</td><td class="right mut">Closed</td></tr>'
                    . '<tr><td>' . self::person('Nadia Osman', 'n.osman@mail.com') . '</td><td>' . Kit::pill('Referral', 'ok') . '</td><td><span class="stages"><i class="st on"></i><i class="st"></i><i class="st"></i><i class="st"></i><i class="st"></i></span><span class="xs mut">Applied</span></td><td><span class="mut xs">Not scored</span></td><td class="mut num">12 Sep</td><td class="right">' . self::two('CV review', 'Unassigned') . '</td></tr>'
                    . '</tbody></table></div>',
                    self::pager('5 of 42 applicants')
                ),
            ],
            [
                'slug' => 'fleet-vehicles-table',
                'name' => 'Fleet vehicles table',
                'name_ar' => 'جدول أسطول المركبات',
                'tagline' => 'Vehicles with mileage, fuel level and next service.',
                'summary' => 'Fleet rows carry the two things that take a vehicle off the road: fuel and the service interval. Both are meters, and the service one turns red once the odometer has passed the due figure.',
                'accent' => '#334155',
                'tags' => ['fleet', 'vehicles', 'maintenance', 'logistics'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Fuel and service meters per vehicle', 'Overdue service flagged in red', 'Driver and plate on every row'],
                'height' => 540,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.plate{display:inline-block;padding:3px 8px;border:1.5px solid var(--bd);border-radius:6px;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700;font-size:12px;letter-spacing:.06em}\n.m{min-width:100px}\n.m .v{display:block;font-size:11.5px;color:var(--mut);margin-bottom:4px;font-variant-numeric:tabular-nums}",
                'body' => self::wrap(
                    self::head('Fleet', '24 vehicles · 3 in workshop', self::btn('Add vehicle', 'plus', 'pri')),
                    self::grid(
                        ['Vehicle', 'Plate', 'Driver', 'Odometer', 'Fuel', 'Next service', 'State'],
                        [
                            [self::two('Toyota Hilux', '2023 · diesel'), '<span class="plate">RUH 4412</span>', Kit::avatar('Omar Saleh', 26), '<span class="num">84,210 km</span>', '<span class="m"><span class="v">72%</span>' . Kit::meter(72, '#16a34a') . '</span>', '<span class="m"><span class="v">in 5,790 km</span>' . Kit::meter(42, '#16a34a') . '</span>', Kit::pill('On route', 'ok', true)],
                            [self::two('Isuzu NPR', '2021 · diesel'), '<span class="plate">RUH 8820</span>', Kit::avatar('Karim Nasser', 26), '<span class="num">212,480 km</span>', '<span class="m"><span class="v">18%</span>' . Kit::meter(18, '#e11d48') . '</span>', '<span class="m"><span class="v">overdue 2,480 km</span>' . Kit::meter(100, '#e11d48') . '</span>', Kit::pill('Service due', 'bad', true)],
                            [self::two('Ford Transit', '2024 · petrol'), '<span class="plate">RUH 1904</span>', Kit::avatar('Nour Sabbagh', 26), '<span class="num">31,004 km</span>', '<span class="m"><span class="v">94%</span>' . Kit::meter(94, '#16a34a') . '</span>', '<span class="m"><span class="v">in 8,996 km</span>' . Kit::meter(10, '#16a34a') . '</span>', Kit::pill('Idle', 'neutral')],
                            [self::two('Hyundai Porter', '2022 · diesel'), '<span class="plate">RUH 6631</span>', '<span class="mut xs">Unassigned</span>', '<span class="num">146,902 km</span>', '<span class="m"><span class="v">44%</span>' . Kit::meter(44, '#f59e0b') . '</span>', '<span class="m"><span class="v">in 1,098 km</span>' . Kit::meter(89, '#f59e0b') . '</span>', Kit::pill('In workshop', 'warn', true)],
                        ],
                        ['right' => [3]]
                    ),
                    '<div class="ft"><span>4 of 24 vehicles</span><span>Fleet utilisation 78%</span></div>'
                ),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private static function setThree(): array
    {
        return [
            [
                'slug' => 'patient-records-table',
                'name' => 'Patient records table',
                'name_ar' => 'جدول سجلات المرضى',
                'tagline' => 'Patient list with age, ward, consultant and admission state.',
                'summary' => 'A ward list built to be scanned at a nurses station: patient and MRN together, the bed they are in, and a state chip that separates admitted, discharged and waiting. Allergy flags sit next to the name where they cannot be missed.',
                'accent' => '#0891b2',
                'tags' => ['healthcare', 'patients', 'records', 'clinical'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Allergy flag beside the patient name', 'Ward and bed in one column', 'Admission state chips'],
                'height' => 540,
                'max' => 960,
                'css' => "tbody tr:hover{background:var(--soft)}\n.mrn{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11.5px;color:var(--mut)}\n.allergy{display:inline-flex;align-items:center;gap:4px;color:var(--bad);font-size:11px;font-weight:700}",
                'body' => self::wrap(
                    self::head('Ward B · patients', '18 beds · 2 free', self::search('Name or MRN') . self::btn('Admit', 'plus', 'pri')),
                    self::grid(
                        ['Patient', 'Age', 'Bed', 'Consultant', 'Admitted', 'State'],
                        [
                            ['<span class="row">' . Kit::avatar('Rana Khalil', 34) . '<span><span class="bold" style="display:block">Rana Khalil <span class="allergy">' . Kit::icon('alert', 12, 2.4) . 'Penicillin</span></span><span class="mrn">MRN 884-2019</span></span></span>', '<span class="num">34</span>', 'B-04', 'Dr. Haddad', '<span class="num">18 Sep</span>', Kit::pill('Admitted', 'ok', true)],
                            ['<span class="row">' . Kit::avatar('Yusuf Barak', 34) . '<span><span class="bold" style="display:block">Yusuf Barak</span><span class="mrn">MRN 884-2044</span></span></span>', '<span class="num">61</span>', 'B-07', 'Dr. Nasser', '<span class="num">16 Sep</span>', Kit::pill('Admitted', 'ok', true)],
                            ['<span class="row">' . Kit::avatar('Hala Mansour', 34) . '<span><span class="bold" style="display:block">Hala Mansour <span class="allergy">' . Kit::icon('alert', 12, 2.4) . 'Latex</span></span><span class="mrn">MRN 884-2077</span></span></span>', '<span class="num">28</span>', 'B-11', 'Dr. Haddad', '<span class="num">20 Sep</span>', Kit::pill('Observation', 'warn', true)],
                            ['<span class="row">' . Kit::avatar('Tarek Fadel', 34) . '<span><span class="bold" style="display:block">Tarek Fadel</span><span class="mrn">MRN 884-1990</span></span></span>', '<span class="num">47</span>', 'B-02', 'Dr. Aziz', '<span class="num">12 Sep</span>', Kit::pill('Discharge today', 'info', true)],
                            ['<span class="row">' . Kit::avatar('Nadia Osman', 34) . '<span><span class="bold" style="display:block">Nadia Osman</span><span class="mrn">MRN 884-2101</span></span></span>', '<span class="num">73</span>', '<span class="mut">Waiting</span>', 'Dr. Nasser', '<span class="num">20 Sep</span>', Kit::pill('Awaiting bed', 'neutral')],
                        ],
                        ['right' => [1]]
                    ),
                    '<div class="ft"><span>5 of 18 patients</span><span>Average stay 4.2 days</span></div>'
                ),
            ],
            [
                'slug' => 'lab-results-table',
                'name' => 'Lab results table',
                'name_ar' => 'جدول نتائج المختبر',
                'tagline' => 'Analyte values against reference ranges with high and low flags.',
                'summary' => 'A results sheet is unreadable without its reference range, so every row carries one, and the value is flagged high or low against it. The flag is an arrow as well as a colour, so it still reads when printed in black and white.',
                'accent' => '#7c3aed',
                'tags' => ['healthcare', 'lab', 'results', 'clinical'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Reference range beside every value', 'High and low flags shown by arrow and colour', 'Panel group headers'],
                'height' => 560,
                'max' => 880,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.grp td{background:var(--soft);font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700;padding:8px 16px}\n.hi{color:var(--bad);font-weight:700}\n.lo{color:var(--acc);font-weight:700}\n.flag{display:inline-flex;align-items:center;gap:3px}",
                'body' => self::wrap(
                    self::head('Results · Rana Khalil', 'Collected 20 Sep 07:14 · MRN 884-2019', Kit::pill('3 out of range', 'bad')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Analyte</th><th class="right">Result</th><th>Unit</th><th>Reference</th><th class="right">Flag</th></tr></thead><tbody>'
                    . '<tr class="grp"><td colspan="5">Haematology</td></tr>'
                    . '<tr><td class="bold">Haemoglobin</td><td class="right num lo">10.2</td><td class="mut">g/dL</td><td class="mut num">12.0 - 15.5</td><td class="right"><span class="flag lo">' . Kit::icon('arrow-down', 13, 2.4) . 'Low</span></td></tr>'
                    . '<tr><td class="bold">White cell count</td><td class="right num">7.4</td><td class="mut">10⁹/L</td><td class="mut num">4.0 - 11.0</td><td class="right mut">—</td></tr>'
                    . '<tr><td class="bold">Platelets</td><td class="right num hi">486</td><td class="mut">10⁹/L</td><td class="mut num">150 - 400</td><td class="right"><span class="flag hi">' . Kit::icon('arrow-up', 13, 2.4) . 'High</span></td></tr>'
                    . '<tr class="grp"><td colspan="5">Biochemistry</td></tr>'
                    . '<tr><td class="bold">Sodium</td><td class="right num">139</td><td class="mut">mmol/L</td><td class="mut num">135 - 145</td><td class="right mut">—</td></tr>'
                    . '<tr><td class="bold">Creatinine</td><td class="right num hi">118</td><td class="mut">µmol/L</td><td class="mut num">45 - 90</td><td class="right"><span class="flag hi">' . Kit::icon('arrow-up', 13, 2.4) . 'High</span></td></tr>'
                    . '<tr><td class="bold">Glucose (fasting)</td><td class="right num">5.4</td><td class="mut">mmol/L</td><td class="mut num">3.9 - 5.5</td><td class="right mut">—</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>Reported by Dr. Haddad · 20 Sep 09:02</span><a href="#">Previous results</a></div>'
                ),
            ],
            [
                'slug' => 'donations-table',
                'name' => 'Donations table',
                'name_ar' => 'جدول التبرعات',
                'tagline' => 'Gifts with campaign, method, recurrence and gift aid.',
                'summary' => 'A fundraising ledger: recurring gifts are marked so monthly income can be told apart from one-offs, anonymous donors are respected in the display, and the campaign column is what the report is eventually grouped by.',
                'accent' => '#be123c',
                'tags' => ['nonprofit', 'donations', 'fundraising'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Recurring gifts marked distinctly', 'Anonymous donors handled in the row', 'Campaign totals in the footer'],
                'height' => 520,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.rec{display:inline-flex;align-items:center;gap:4px;color:var(--acc);font-size:11.5px;font-weight:650}",
                'body' => self::wrap(
                    self::head('Donations', 'September · $48,120 raised', self::btn('Export', 'download') . self::btn('Record gift', 'plus', 'pri')),
                    self::grid(
                        ['Donor', 'Campaign', 'Method', 'Date', 'Amount'],
                        [
                            [self::person('Copper & Co', 'Corporate partner'), Kit::pill('Winter appeal', 'info'), 'Bank transfer', '<span class="num mut">18 Sep</span>', '<span class="num bold">$12,000.00</span>'],
                            ['<span class="row">' . Kit::avatar('Anonymous', 34) . '<span><span class="bold" style="display:block">Anonymous</span><span class="xs mut">Gift aid claimed</span></span></span>', Kit::pill('General fund', 'neutral'), 'Card', '<span class="num mut">17 Sep</span>', '<span class="num bold">$250.00</span>'],
                            [self::person('Rana Khalil', '<span class="rec">' . Kit::icon('refresh', 12, 2.4) . 'Monthly since 2023</span>'), Kit::pill('Education', 'info'), 'Direct debit', '<span class="num mut">15 Sep</span>', '<span class="num bold">$45.00</span>'],
                            [self::person('Bluebird Media', 'Matched giving'), Kit::pill('Winter appeal', 'info'), 'Card', '<span class="num mut">14 Sep</span>', '<span class="num bold">$3,400.00</span>'],
                            [self::person('Yusuf Barak', '<span class="rec">' . Kit::icon('refresh', 12, 2.4) . 'Monthly since 2025</span>'), Kit::pill('Water', 'info'), 'Direct debit', '<span class="num mut">12 Sep</span>', '<span class="num bold">$20.00</span>'],
                        ],
                        ['right' => [4]]
                    ),
                    '<div class="ft"><span>5 of 412 gifts</span><span class="bold num">Recurring income $2,840 / month</span></div>'
                ),
            ],
            [
                'slug' => 'event-attendees-table',
                'name' => 'Event attendees check-in',
                'name_ar' => 'جدول حضور الفعالية',
                'tagline' => 'Guest list with ticket type and a one-tap check-in.',
                'summary' => 'A door list: tap to check someone in, the row turns green and the counter at the top moves. Everything is local state, so it keeps working when the venue wifi does not - which is the whole point of a door list.',
                'accent' => '#059669',
                'tags' => ['events', 'check-in', 'tickets', 'attendees'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['One-tap check-in with a live counter', 'Ticket type and seat per guest', 'Works entirely offline'],
                'height' => 560,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.in{background:var(--ok-bg)}\ntr.in:hover{background:var(--ok-bg)}\n.tick{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:11.5px;color:var(--mut)}",
                'js' => "const counter=document.getElementById('checked');\n"
                    . "function refresh(){counter.textContent=document.querySelectorAll('tr.in').length;}\n"
                    . "document.querySelectorAll('[data-check]').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    const row=button.closest('tr');\n"
                    . "    const inside=row.classList.toggle('in');\n"
                    . "    button.textContent=inside?'Checked in':'Check in';\n"
                    . "    button.classList.toggle('pri',!inside);\n"
                    . "    refresh();\n"
                    . '  });'
                    . "\n});\nrefresh();",
                'body' => self::wrap(
                    self::head('Door list', '<span id="checked">0</span> of 5 checked in · Hall A', self::search('Search guests')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Guest</th><th>Ticket</th><th>Seat</th><th>Company</th><th class="right">Check-in</th></tr></thead><tbody>'
                    . '<tr><td>' . self::person('Rana Khalil', 'rana.k@mail.com') . '</td><td>' . Kit::pill('VIP', 'warn') . '<div class="tick">TKT-88412</div></td><td class="num">A-12</td><td>Northwind Ltd</td><td class="right"><button class="btn pri" type="button" data-check>Check in</button></td></tr>'
                    . '<tr><td>' . self::person('Yusuf Barak', 'y.barak@mail.com') . '</td><td>' . Kit::pill('Standard', 'neutral') . '<div class="tick">TKT-88433</div></td><td class="num">C-04</td><td>Juno Labs</td><td class="right"><button class="btn pri" type="button" data-check>Check in</button></td></tr>'
                    . '<tr><td>' . self::person('Hala Mansour', 'hala.m@mail.com') . '</td><td>' . Kit::pill('Speaker', 'info') . '<div class="tick">TKT-88440</div></td><td class="num">Stage</td><td>Harbor Studio</td><td class="right"><button class="btn pri" type="button" data-check>Check in</button></td></tr>'
                    . '<tr><td>' . self::person('Tarek Fadel', 't.fadel@mail.com') . '</td><td>' . Kit::pill('Standard', 'neutral') . '<div class="tick">TKT-88451</div></td><td class="num">D-18</td><td>Bluebird Media</td><td class="right"><button class="btn pri" type="button" data-check>Check in</button></td></tr>'
                    . '<tr><td>' . self::person('Nadia Osman', 'n.osman@mail.com') . '</td><td>' . Kit::pill('VIP', 'warn') . '<div class="tick">TKT-88460</div></td><td class="num">A-15</td><td>Copper &amp; Co</td><td class="right"><button class="btn pri" type="button" data-check>Check in</button></td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>5 of 214 guests</span><span>Doors close at 19:30</span></div>'
                ),
            ],
            [
                'slug' => 'course-progress-table',
                'name' => 'Course progress table',
                'name_ar' => 'جدول تقدم الدورات',
                'tagline' => 'Enrolled learners with lesson progress and last activity.',
                'summary' => 'Learning progress as a ring rather than a percentage: at a glance you can see who has stalled, and the last-activity column says whether that is recent or three weeks old, which is the difference between a nudge and a refund.',
                'accent' => '#4f46e5',
                'tags' => ['education', 'lms', 'progress', 'courses'],
                'stack' => ['HTML', 'CSS', 'SVG'],
                'features' => ['Progress ring per learner', 'Lessons completed against the total', 'Stalled learners called out by last activity'],
                'height' => 540,
                'max' => 900,
                'categories' => ['dashboards'],
                'css' => "tbody tr:hover{background:var(--soft)}\n.stall{color:var(--warn);font-weight:650}",
                'body' => self::wrap(
                    self::head('Advanced SVG animation', '312 enrolled · 14 lessons', self::btn('Message learners', 'mail')),
                    self::grid(
                        ['Learner', 'Progress', 'Lessons', 'Quiz average', 'Last activity', 'State'],
                        [
                            [self::person('Rana Khalil', 'Enrolled 12 Aug'), Kit::ring(93, '#4f46e5', 52), '<span class="num">13 / 14</span>', '<span class="num">94%</span>', '<span class="num mut">2 hours ago</span>', Kit::pill('On track', 'ok', true)],
                            [self::person('Yusuf Barak', 'Enrolled 18 Aug'), Kit::ring(57, '#4f46e5', 52), '<span class="num">8 / 14</span>', '<span class="num">81%</span>', '<span class="num mut">Yesterday</span>', Kit::pill('On track', 'ok', true)],
                            [self::person('Hala Mansour', 'Enrolled 02 Aug'), Kit::ring(21, '#f59e0b', 52), '<span class="num">3 / 14</span>', '<span class="num">62%</span>', '<span class="stall num">23 days ago</span>', Kit::pill('Stalled', 'warn', true)],
                            [self::person('Tarek Fadel', 'Enrolled 28 Aug'), Kit::ring(100, '#059669', 52), '<span class="num">14 / 14</span>', '<span class="num">97%</span>', '<span class="num mut">4 days ago</span>', Kit::pill('Completed', 'ok', true)],
                            [self::person('Nadia Osman', 'Enrolled 09 Sep'), Kit::ring(7, '#f59e0b', 52), '<span class="num">1 / 14</span>', '<span class="mut">—</span>', '<span class="stall num">11 days ago</span>', Kit::pill('At risk', 'bad', true)],
                        ],
                        ['right' => [2, 3]]
                    ),
                    '<div class="ft"><span>5 of 312 learners</span><span>Completion rate 42%</span></div>'
                ),
            ],
            [
                'slug' => 'suppliers-table',
                'name' => 'Suppliers table',
                'name_ar' => 'جدول الموردين',
                'tagline' => 'Vendors with rating, lead time and on-time delivery.',
                'summary' => 'Procurement compares suppliers on three numbers, so those three are the columns: quality as stars, lead time in days, and on-time delivery as a meter. Everything else is secondary text.',
                'accent' => '#0f766e',
                'tags' => ['procurement', 'suppliers', 'vendors', 'ratings'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Star rating and on-time meter side by side', 'Lead time with a variance caption', 'Preferred supplier marking'],
                'height' => 520,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.pref{color:var(--warn)}",
                'body' => self::wrap(
                    self::head('Suppliers', '48 active vendors', self::search('Search suppliers') . self::btn('Add supplier', 'plus', 'pri')),
                    self::grid(
                        ['Supplier', 'Category', 'Rating', 'Lead time', 'On time', 'Spend YTD'],
                        [
                            ['<span class="row">' . Kit::iconTile('box', '#0f766e', 34) . '<span><span class="bold" style="display:block">Aurora Components <span class="pref" title="Preferred">' . Kit::icon('star', 12, 2.4) . '</span></span><span class="xs mut">Shenzhen, CN</span></span></span>', Kit::pill('Electronics', 'info'), Kit::stars(4.5), self::two('18 days', '± 2 days'), '<span style="display:block;width:90px">' . Kit::meter(96, '#0f766e') . '</span>', '<span class="num bold">$412,800</span>'],
                            ['<span class="row">' . Kit::iconTile('truck', '#2563eb', 34) . '<span><span class="bold" style="display:block">Levant Logistics</span><span class="xs mut">Amman, JO</span></span></span>', Kit::pill('Freight', 'info'), Kit::stars(4), self::two('4 days', '± 1 day'), '<span style="display:block;width:90px">' . Kit::meter(88, '#0f766e') . '</span>', '<span class="num bold">$96,400</span>'],
                            ['<span class="row">' . Kit::iconTile('tag', '#d97706', 34) . '<span><span class="bold" style="display:block">Copper Packaging</span><span class="xs mut">Istanbul, TR</span></span></span>', Kit::pill('Packaging', 'info'), Kit::stars(3.5), self::two('12 days', '± 5 days'), '<span style="display:block;width:90px">' . Kit::meter(71, '#f59e0b') . '</span>', '<span class="num bold">$58,120</span>'],
                            ['<span class="row">' . Kit::iconTile('zap', '#7c3aed', 34) . '<span><span class="bold" style="display:block">Nile Textiles</span><span class="xs mut">Cairo, EG</span></span></span>', Kit::pill('Materials', 'info'), Kit::stars(2.5), self::two('26 days', '± 9 days'), '<span style="display:block;width:90px">' . Kit::meter(54, '#e11d48') . '</span>', '<span class="num bold">$22,940</span>'],
                        ],
                        ['right' => [5]]
                    ),
                    '<div class="ft"><span>4 of 48 suppliers</span><span>Average on-time 82%</span></div>'
                ),
            ],
            [
                'slug' => 'contracts-expiry-table',
                'name' => 'Contracts and renewals',
                'name_ar' => 'جدول العقود والتجديدات',
                'tagline' => 'Agreements with a countdown to expiry and notice period.',
                'summary' => 'Contracts are managed by deadline, so the table sorts by one: the countdown column turns amber inside ninety days and red inside the notice period, which is when doing nothing starts to auto-renew something.',
                'accent' => '#b45309',
                'tags' => ['legal', 'contracts', 'renewals', 'compliance'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Expiry countdown with notice-period thresholds', 'Auto-renew flag per contract', 'Owner and annual value'],
                'height' => 540,
                'max' => 960,
                'css' => "tbody tr:hover{background:var(--soft)}\n.warnx{color:var(--warn);font-weight:700}\n.badx{color:var(--bad);font-weight:700}\n.auto{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;color:var(--mut)}",
                'body' => self::wrap(
                    self::head('Contracts', '4 renew within 90 days', self::btn('Filter', 'filter') . self::btn('Upload contract', 'upload', 'pri')),
                    self::grid(
                        ['Contract', 'Counterparty', 'Owner', 'Expires', 'Notice', 'Annual value'],
                        [
                            [self::two('Master services agreement', 'MSA-2024-011'), 'Northwind Ltd', Kit::avatar('Lina Haddad', 28), '<span class="badx num">in 21 days</span>', '<span class="auto">' . Kit::icon('refresh', 13) . '30 days · auto-renews</span>', '<span class="num bold">$180,000</span>'],
                            [self::two('Cloud hosting', 'SUB-2025-204'), 'Skyline Cloud', Kit::avatar('Omar Saleh', 28), '<span class="warnx num">in 74 days</span>', '<span class="auto">' . Kit::icon('refresh', 13) . '60 days · auto-renews</span>', '<span class="num bold">$96,400</span>'],
                            [self::two('Office lease', 'LSE-2022-002'), 'Marjan Estates', Kit::avatar('Karim Nasser', 28), '<span class="num">in 11 months</span>', '<span class="auto">' . Kit::icon('x', 13) . '90 days · no renewal</span>', '<span class="num bold">$220,000</span>'],
                            [self::two('Support retainer', 'RET-2025-088'), 'Harbor Studio', Kit::avatar('Maya Rahman', 28), '<span class="warnx num">in 46 days</span>', '<span class="auto">' . Kit::icon('x', 13) . '30 days · no renewal</span>', '<span class="num bold">$42,000</span>'],
                        ],
                        ['right' => [5]]
                    ),
                    '<div class="ft"><span>4 of 61 contracts</span><span class="bold num">Total committed $538,400</span></div>'
                ),
            ],
            [
                'slug' => 'licenses-seats-table',
                'name' => 'Software licences table',
                'name_ar' => 'جدول تراخيص البرمجيات',
                'tagline' => 'Tool spend with seats used against seats paid for.',
                'summary' => 'The table that finds wasted software spend: seats used against seats bought, the gap costed out in its own column, and a renewal date so the saving can actually be taken at the right moment.',
                'accent' => '#475569',
                'tags' => ['saas', 'licences', 'it', 'spend'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Seat utilisation meter per tool', 'Wasted spend costed per row', 'Renewal dates for acting on the gap'],
                'height' => 520,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.waste{color:var(--bad);font-weight:700}\n.fine{color:var(--ok);font-weight:650}",
                'body' => self::wrap(
                    self::head('Software licences', '$18,420 per month across 24 tools', self::btn('Export', 'download')),
                    self::grid(
                        ['Tool', 'Owner', 'Seats used', 'Utilisation', 'Monthly cost', 'Idle spend'],
                        [
                            ['<span class="row">' . Kit::iconTile('image', '#dc2626', 32) . '<span class="bold">Design suite</span></span>', 'Design', '<span class="num">9 / 24</span>', '<span style="display:block;width:100px">' . Kit::meter(37, '#e11d48') . '</span>', '<span class="num">$1,440</span>', '<span class="waste num">$900</span>'],
                            ['<span class="row">' . Kit::iconTile('code', '#2563eb', 32) . '<span class="bold">Code hosting</span></span>', 'Engineering', '<span class="num">41 / 45</span>', '<span style="display:block;width:100px">' . Kit::meter(91, '#16a34a') . '</span>', '<span class="num">$945</span>', '<span class="fine num">$84</span>'],
                            ['<span class="row">' . Kit::iconTile('mail', '#db2777', 32) . '<span class="bold">Email marketing</span></span>', 'Marketing', '<span class="num">4 / 10</span>', '<span style="display:block;width:100px">' . Kit::meter(40, '#f59e0b') . '</span>', '<span class="num">$620</span>', '<span class="waste num">$372</span>'],
                            ['<span class="row">' . Kit::iconTile('users', '#0891b2', 32) . '<span class="bold">CRM</span></span>', 'Sales', '<span class="num">18 / 20</span>', '<span style="display:block;width:100px">' . Kit::meter(90, '#16a34a') . '</span>', '<span class="num">$2,400</span>', '<span class="fine num">$240</span>'],
                            ['<span class="row">' . Kit::iconTile('chart', '#7c3aed', 32) . '<span class="bold">Analytics</span></span>', 'Product', '<span class="num">6 / 25</span>', '<span style="display:block;width:100px">' . Kit::meter(24, '#e11d48') . '</span>', '<span class="num">$1,250</span>', '<span class="waste num">$950</span>'],
                        ],
                        ['right' => [2, 4, 5]]
                    ),
                    '<div class="ft"><span>5 of 24 tools</span><span class="waste num">$2,546 per month on idle seats</span></div>'
                ),
            ],
            [
                'slug' => 'grouped-subtotals-table',
                'name' => 'Grouped table with subtotals',
                'name_ar' => 'جدول مجمع بمجاميع فرعية',
                'tagline' => 'Rows grouped by region, each group carrying its own subtotal.',
                'summary' => 'A report table: rows gathered under a group header and closed by a subtotal line, with a grand total in the foot. This is the shape almost every exported finance report wants and almost no component library provides.',
                'accent' => '#1d4ed8',
                'tags' => ['report', 'grouping', 'subtotals', 'finance'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Group header and subtotal per region', 'Grand total in a table foot', 'Percentage of total per line'],
                'height' => 580,
                'max' => 900,
                'css' => "tr.grp td{background:var(--acc-soft);font-weight:700;color:var(--acc);padding:9px 16px;font-size:12.5px}\ntr.sub td{background:var(--soft);font-weight:700;border-top:1px solid var(--bd)}\ntfoot td{padding:13px 16px;border-top:2px solid var(--bd);font-weight:700;font-size:14px}\ntbody tr:not(.grp):not(.sub):hover{background:var(--soft)}",
                'body' => self::wrap(
                    self::head('Revenue by region', 'Q3 2026 · closed books'),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Account</th><th>Segment</th><th class="right">Revenue</th><th class="right">Share</th></tr></thead><tbody>'
                    . '<tr class="grp"><td colspan="4">Middle East</td></tr>'
                    . '<tr><td>Northwind Ltd</td><td class="mut">Enterprise</td><td class="right num">$180,400</td><td class="right num">24.1%</td></tr>'
                    . '<tr><td>Copper &amp; Co</td><td class="mut">Mid-market</td><td class="right num">$96,200</td><td class="right num">12.9%</td></tr>'
                    . '<tr class="sub"><td colspan="2">Middle East subtotal</td><td class="right num">$276,600</td><td class="right num">37.0%</td></tr>'
                    . '<tr class="grp"><td colspan="4">Europe</td></tr>'
                    . '<tr><td>Bluebird Media</td><td class="mut">Mid-market</td><td class="right num">$142,800</td><td class="right num">19.1%</td></tr>'
                    . '<tr><td>Harbor Studio</td><td class="mut">SMB</td><td class="right num">$61,400</td><td class="right num">8.2%</td></tr>'
                    . '<tr class="sub"><td colspan="2">Europe subtotal</td><td class="right num">$204,200</td><td class="right num">27.3%</td></tr>'
                    . '<tr class="grp"><td colspan="4">North America</td></tr>'
                    . '<tr><td>Juno Labs</td><td class="mut">Enterprise</td><td class="right num">$204,600</td><td class="right num">27.4%</td></tr>'
                    . '<tr><td>Redwood Group</td><td class="mut">SMB</td><td class="right num">$62,400</td><td class="right num">8.3%</td></tr>'
                    . '<tr class="sub"><td colspan="2">North America subtotal</td><td class="right num">$267,000</td><td class="right num">35.7%</td></tr>'
                    . '</tbody><tfoot><tr><td colspan="2">Total revenue</td><td class="right num">$747,800</td><td class="right num">100%</td></tr></tfoot></table></div>',
                    '<div class="ft"><span>8 accounts across 3 regions</span><span>Up 14.2% on Q2</span></div>'
                ),
            ],
            [
                'slug' => 'collapsible-groups-table',
                'name' => 'Collapsible group table',
                'name_ar' => 'جدول مجموعات قابلة للطي',
                'tagline' => 'Group headers that fold their rows away.',
                'summary' => 'When a table has more groups than fit on a screen, folding beats scrolling. The header row is a button, the chevron rotates, and the row count stays visible while the group is closed so nothing is lost by folding it.',
                'accent' => '#7c3aed',
                'tags' => ['grouping', 'collapse', 'accordion'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Group headers that fold and unfold', 'Row counts stay visible when closed', 'Whole header row is the control'],
                'height' => 560,
                'max' => 880,
                'css' => "tr.grp td{background:var(--soft);padding:0}\ntr.grp button{width:100%;display:flex;align-items:center;gap:9px;padding:11px 16px;background:none;border:0;font:inherit;font-weight:700;font-size:12.5px;color:var(--ink);cursor:pointer;text-align:left}\ntr.grp button:hover{color:var(--acc)}\ntr.grp svg{transition:transform .18s}\ntr.grp[aria-expanded=false] svg{transform:rotate(-90deg)}\ntr.grp .n{margin-left:auto;color:var(--mut);font-weight:600}\ntbody tr:not(.grp):hover{background:var(--soft)}",
                'js' => "document.querySelectorAll('tr.grp button').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    const header=button.closest('tr');\n"
                    . "    const open=header.getAttribute('aria-expanded')!=='false';\n"
                    . "    header.setAttribute('aria-expanded',String(!open));\n"
                    . "    let row=header.nextElementSibling;\n"
                    . "    while(row&&!row.classList.contains('grp')){row.hidden=open;row=row.nextElementSibling;}\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Budget by department', 'Financial year 2026'),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Line</th><th class="right">Budget</th><th class="right">Spent</th><th class="right">Remaining</th></tr></thead><tbody>'
                    . '<tr class="grp" aria-expanded="true"><td colspan="4"><button type="button">' . Kit::icon('chevron-down', 15) . 'Engineering<span class="n">3 lines · $980,000</span></button></td></tr>'
                    . '<tr><td>Salaries</td><td class="right num">$820,000</td><td class="right num">$612,400</td><td class="right num bold">$207,600</td></tr>'
                    . '<tr><td>Cloud and tooling</td><td class="right num">$120,000</td><td class="right num">$98,200</td><td class="right num bold">$21,800</td></tr>'
                    . '<tr><td>Training</td><td class="right num">$40,000</td><td class="right num">$11,400</td><td class="right num bold">$28,600</td></tr>'
                    . '<tr class="grp" aria-expanded="true"><td colspan="4"><button type="button">' . Kit::icon('chevron-down', 15) . 'Marketing<span class="n">2 lines · $420,000</span></button></td></tr>'
                    . '<tr><td>Paid acquisition</td><td class="right num">$300,000</td><td class="right num">$284,900</td><td class="right num bold">$15,100</td></tr>'
                    . '<tr><td>Events</td><td class="right num">$120,000</td><td class="right num">$44,000</td><td class="right num bold">$76,000</td></tr>'
                    . '<tr class="grp" aria-expanded="false"><td colspan="4"><button type="button">' . Kit::icon('chevron-down', 15) . 'Operations<span class="n">2 lines · $260,000</span></button></td></tr>'
                    . '<tr hidden><td>Office</td><td class="right num">$180,000</td><td class="right num">$134,000</td><td class="right num bold">$46,000</td></tr>'
                    . '<tr hidden><td>Insurance</td><td class="right num">$80,000</td><td class="right num">$80,000</td><td class="right num bold">$0</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>3 departments · 7 lines</span><span>$1.66M budgeted</span></div>'
                ),
            ],
            [
                'slug' => 'frozen-column-table',
                'name' => 'Frozen first column table',
                'name_ar' => 'جدول بعمود مثبت',
                'tagline' => 'Wide table that scrolls sideways with the labels pinned.',
                'summary' => 'Twelve months across is more than any screen holds, so the first column is pinned with sticky positioning and the rest scrolls under it. Without this, a wide table forces you to remember which row you were on.',
                'accent' => '#0891b2',
                'tags' => ['sticky', 'scroll', 'wide', 'matrix'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['First column pinned while the rest scrolls', 'Sticky header as well as sticky column', 'Shadow on the pinned edge to show the split'],
                'height' => 520,
                'max' => 900,
                'css' => ".scroll{overflow:auto;max-height:340px}\n.scroll th:first-child,.scroll td:first-child{position:sticky;left:0;background:var(--card);z-index:2;box-shadow:1px 0 0 var(--bd)}\n.scroll th:first-child{z-index:4;background:var(--soft)}\n.scroll thead th{position:sticky;top:0;z-index:3}\n.scroll td{white-space:nowrap;font-variant-numeric:tabular-nums}\ntbody tr:hover td{background:var(--soft)}\ntbody tr:hover td:first-child{background:var(--soft)}",
                'body' => self::wrap(
                    self::head('Monthly revenue by product', 'FY2026 · scroll sideways'),
                    '<div class="scroll"><table><thead><tr>'
                    . '<th>Product</th>'
                    . implode('', array_map(fn($m) => '<th class="right">' . $m . '</th>', ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']))
                    . '</tr></thead><tbody>'
                    . implode('', array_map(function ($row) {
                        $cells = implode('', array_map(fn($v) => '<td class="right">$' . number_format($v) . '</td>', $row[1]));

                        return '<tr><td class="bold">' . $row[0] . '</td>' . $cells . '</tr>';
                    }, [
                        ['Aurora Lamp', [18400, 19100, 22400, 21800, 24200, 26100, 25400, 27800, 29100, 31400, 34200, 41800]],
                        ['Nimbus Chair', [42100, 39800, 44200, 46100, 48800, 51200, 49600, 52400, 55100, 58200, 61400, 72800]],
                        ['Terra Table', [12400, 13100, 12800, 14200, 15100, 16400, 15800, 17200, 18400, 19100, 21200, 26400]],
                        ['Halo Lamp', [8200, 9100, 11400, 10800, 12100, 13400, 12900, 14100, 15400, 16800, 18900, 24100]],
                        ['Vista Shelf', [22100, 21400, 24800, 26200, 27100, 28400, 27900, 29800, 31200, 33400, 36100, 44200]],
                    ]))
                    . '</tbody></table></div>',
                    '<div class="ft"><span>5 products · 12 months</span><span>Total FY $1.84M</span></div>'
                ),
            ],
            [
                'slug' => 'editable-cells-table',
                'name' => 'Editable cells table',
                'name_ar' => 'جدول بخلايا قابلة للتحرير',
                'tagline' => 'Edit in place, with a dirty marker and a save bar.',
                'summary' => 'Spreadsheet behaviour without a spreadsheet: click a cell, type, and the row is marked as changed until it is saved. The save bar counts the pending edits, so nothing is committed by accident and nothing is lost silently.',
                'accent' => '#059669',
                'tags' => ['editable', 'inline-edit', 'crud', 'grid'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Click-to-edit cells with contenteditable', 'Changed rows marked until saved', 'Save bar that counts pending edits'],
                'height' => 560,
                'max' => 880,
                'css' => "td[contenteditable]{cursor:text;outline:none;border-radius:6px}\ntd[contenteditable]:hover{box-shadow:inset 0 0 0 1px var(--bd)}\ntd[contenteditable]:focus{box-shadow:inset 0 0 0 2px var(--acc);background:var(--acc-soft)}\ntr.dirty td:first-child{position:relative}\ntr.dirty td:first-child::before{content:'';position:absolute;left:6px;top:50%;transform:translateY(-50%);width:6px;height:6px;border-radius:50%;background:var(--acc)}\n.savebar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 20px;border-top:1px solid var(--bd);background:var(--soft);flex-wrap:wrap}",
                'js' => "const pending=document.getElementById('pending');\n"
                    . "function refresh(){\n"
                    . "  const n=document.querySelectorAll('tr.dirty').length;\n"
                    . "  pending.textContent=n===0?'No unsaved changes':n+' row'+(n===1?'':'s')+' changed';\n"
                    . "  document.getElementById('save').disabled=n===0;\n"
                    . "}\n"
                    . "document.querySelectorAll('td[contenteditable]').forEach(cell=>{\n"
                    . "  cell.addEventListener('input',()=>{cell.closest('tr').classList.add('dirty');refresh();});\n"
                    . "});\n"
                    . "document.getElementById('save').addEventListener('click',()=>{\n"
                    . "  document.querySelectorAll('tr.dirty').forEach(row=>row.classList.remove('dirty'));\n"
                    . "  refresh();\n"
                    . "});\nrefresh();",
                'body' => self::wrap(
                    self::head('Price list', 'Click any price or stock figure to edit it'),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Product</th><th>SKU</th><th class="right">Cost</th><th class="right">Price</th><th class="right">Stock</th></tr></thead><tbody>'
                    . '<tr><td class="bold" style="padding-left:20px">Aurora Desk Lamp</td><td class="mut num">LMP-0041</td><td class="right num" contenteditable="true">$18.40</td><td class="right num" contenteditable="true">$39.00</td><td class="right num" contenteditable="true">412</td></tr>'
                    . '<tr><td class="bold" style="padding-left:20px">Nimbus Chair</td><td class="mut num">CHR-2210</td><td class="right num" contenteditable="true">$88.00</td><td class="right num" contenteditable="true">$180.00</td><td class="right num" contenteditable="true">57</td></tr>'
                    . '<tr><td class="bold" style="padding-left:20px">Terra Side Table</td><td class="mut num">TBL-1187</td><td class="right num" contenteditable="true">$24.60</td><td class="right num" contenteditable="true">$58.00</td><td class="right num" contenteditable="true">188</td></tr>'
                    . '<tr><td class="bold" style="padding-left:20px">Halo Floor Lamp</td><td class="mut num">LMP-0088</td><td class="right num" contenteditable="true">$41.20</td><td class="right num" contenteditable="true">$92.00</td><td class="right num" contenteditable="true">4</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="savebar"><span class="sm mut" id="pending">No unsaved changes</span>'
                    . '<span class="row" style="gap:8px"><button class="btn" type="button">Discard</button>'
                    . '<button class="btn pri" type="button" id="save">Save changes</button></span></div>'
                ),
            ],
            [
                'slug' => 'responsive-stacked-table',
                'name' => 'Responsive stacked table',
                'name_ar' => 'جدول متجاوب متراص',
                'tagline' => 'A table on desktop, a stack of cards on a phone.',
                'summary' => 'A data table cannot survive a 380px screen, so below 640px this one stops being a table: each row becomes a card and each cell grows a label from its data attribute. One markup, two layouts, no JavaScript.',
                'accent' => '#2563eb',
                'tags' => ['responsive', 'mobile', 'cards', 'adaptive'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Rows become cards under 640px', 'Cell labels from data attributes', 'No JavaScript and no duplicate markup'],
                'height' => 600,
                'max' => 820,
                'css' => "tbody tr:hover{background:var(--soft)}\n@media (max-width:640px){\n"
                    . "  thead{display:none}\n"
                    . "  table,tbody,tr,td{display:block;width:100%}\n"
                    . "  tr{border:1px solid var(--bd);border-radius:12px;margin:12px;padding:6px 0;background:var(--card)}\n"
                    . "  td{display:flex;justify-content:space-between;gap:16px;border:0;padding:8px 14px;text-align:right}\n"
                    . "  td::before{content:attr(data-label);font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--mut);font-weight:700;text-align:left}\n"
                    . '}',
                'body' => self::wrap(
                    self::head('Recent orders', 'Resize the frame to see it stack'),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Order</th><th>Customer</th><th>Status</th><th class="right">Total</th></tr></thead><tbody>'
                    . '<tr><td data-label="Order" class="bold">#3104</td><td data-label="Customer">Rana Khalil</td><td data-label="Status">' . Kit::pill('Paid', 'ok') . '</td><td data-label="Total" class="right num bold">$248.00</td></tr>'
                    . '<tr><td data-label="Order" class="bold">#3103</td><td data-label="Customer">Tarek Fadel</td><td data-label="Status">' . Kit::pill('Packed', 'info') . '</td><td data-label="Total" class="right num bold">$89.00</td></tr>'
                    . '<tr><td data-label="Order" class="bold">#3102</td><td data-label="Customer">Hala Mansour</td><td data-label="Status">' . Kit::pill('Shipped', 'info') . '</td><td data-label="Total" class="right num bold">$612.50</td></tr>'
                    . '<tr><td data-label="Order" class="bold">#3101</td><td data-label="Customer">Yusuf Barak</td><td data-label="Status">' . Kit::pill('Refunded', 'bad') . '</td><td data-label="Total" class="right num bold">-$42.00</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>4 orders</span><span>Breakpoint at 640px</span></div>'
                ),
            ],
            [
                'slug' => 'tree-hierarchy-table',
                'name' => 'Tree hierarchy table',
                'name_ar' => 'جدول هرمي شجري',
                'tagline' => 'Nested rows with expand arrows and indentation guides.',
                'summary' => 'A folder tree, an org chart or a chart of accounts - any hierarchy that also has columns. Depth is shown by indentation and a guide line, and parent rows total their children so a collapsed branch still tells you its size.',
                'accent' => '#d97706',
                'tags' => ['tree', 'hierarchy', 'nested', 'expand'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Indentation guides showing depth', 'Parent rows total their children', 'Branches fold recursively'],
                'height' => 560,
                'max' => 880,
                'css' => "tbody tr:hover{background:var(--soft)}\n.node{display:flex;align-items:center;gap:7px}\n.node .tg{background:none;border:0;padding:2px;cursor:pointer;color:var(--mut);display:inline-flex}\n.node .tg:hover{color:var(--acc)}\n.node .tg svg{transition:transform .18s}\ntr[aria-expanded=true] .tg svg{transform:rotate(90deg)}\n.d1{padding-left:26px}\n.d2{padding-left:52px}\n.leaf{width:18px;display:inline-block}",
                'js' => "document.querySelectorAll('.tg').forEach(button=>{\n"
                    . "  button.addEventListener('click',()=>{\n"
                    . "    const row=button.closest('tr');\n"
                    . "    const open=row.getAttribute('aria-expanded')!=='false';\n"
                    . "    row.setAttribute('aria-expanded',String(!open));\n"
                    . "    const depth=Number(row.dataset.depth||0);\n"
                    . "    let next=row.nextElementSibling;\n"
                    . "    while(next&&Number(next.dataset.depth||0)>depth){\n"
                    . "      next.hidden=open;\n"
                    . "      if(open)next.setAttribute('aria-expanded','false');\n"
                    . "      next=next.nextElementSibling;\n"
                    . "    }\n"
                    . '  });'
                    . "\n});",
                'body' => self::wrap(
                    self::head('Chart of accounts', 'FY2026 · consolidated'),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Account</th><th>Code</th><th class="right">Budget</th><th class="right">Actual</th></tr></thead><tbody>'
                    . '<tr data-depth="0" aria-expanded="true"><td><span class="node"><button class="tg" type="button" aria-label="Toggle">' . Kit::icon('chevron-right', 15) . '</button><b>Operating expenses</b></span></td><td class="mut num">6000</td><td class="right num">$1,660,000</td><td class="right num bold">$1,266,900</td></tr>'
                    . '<tr data-depth="1" aria-expanded="true"><td class="d1"><span class="node"><button class="tg" type="button" aria-label="Toggle">' . Kit::icon('chevron-right', 15) . '</button><b>People</b></span></td><td class="mut num">6100</td><td class="right num">$1,100,000</td><td class="right num bold">$846,800</td></tr>'
                    . '<tr data-depth="2"><td class="d2"><span class="node"><span class="leaf"></span>Salaries</span></td><td class="mut num">6110</td><td class="right num">$980,000</td><td class="right num">$742,400</td></tr>'
                    . '<tr data-depth="2"><td class="d2"><span class="node"><span class="leaf"></span>Benefits</span></td><td class="mut num">6120</td><td class="right num">$120,000</td><td class="right num">$104,400</td></tr>'
                    . '<tr data-depth="1" aria-expanded="true"><td class="d1"><span class="node"><button class="tg" type="button" aria-label="Toggle">' . Kit::icon('chevron-right', 15) . '</button><b>Technology</b></span></td><td class="mut num">6200</td><td class="right num">$420,000</td><td class="right num bold">$332,100</td></tr>'
                    . '<tr data-depth="2"><td class="d2"><span class="node"><span class="leaf"></span>Cloud hosting</span></td><td class="mut num">6210</td><td class="right num">$300,000</td><td class="right num">$248,900</td></tr>'
                    . '<tr data-depth="2"><td class="d2"><span class="node"><span class="leaf"></span>Software licences</span></td><td class="mut num">6220</td><td class="right num">$120,000</td><td class="right num">$83,200</td></tr>'
                    . '<tr data-depth="1"><td class="d1"><span class="node"><span class="leaf"></span><b>Facilities</b></span></td><td class="mut num">6300</td><td class="right num">$140,000</td><td class="right num bold">$88,000</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>8 accounts · 3 levels</span><span>76% of budget used</span></div>'
                ),
            ],
            [
                'slug' => 'timesheet-week-table',
                'name' => 'Weekly timesheet',
                'name_ar' => 'جدول الدوام الأسبوعي',
                'tagline' => 'Hours per project per weekday with daily and weekly totals.',
                'summary' => 'The grid a week of work is actually entered into: projects down, days across, totals on both edges. Cells over eight hours are tinted so an accidental double entry is caught before the week is submitted.',
                'accent' => '#0891b2',
                'tags' => ['timesheet', 'hours', 'projects', 'billing'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Row and column totals on both edges', 'Overlong days tinted for review', 'Weekend columns visually recessed'],
                'height' => 540,
                'max' => 900,
                'css' => "td,th{text-align:center}\ntd:first-child,th:first-child{text-align:left}\n.we{background:var(--soft);color:var(--faint)}\n.over{background:var(--warn-bg);color:var(--warn);font-weight:700;border-radius:6px}\ntfoot td{border-top:2px solid var(--bd);font-weight:700;background:var(--soft)}\ntbody tr:hover{background:var(--soft)}",
                'body' => self::wrap(
                    self::head('Timesheet · week 38', '15 - 21 September 2026 · Omar Saleh', Kit::pill('Draft', 'warn') . self::btn('Submit week', 'check', 'pri')),
                    '<div style="overflow-x:auto"><table><thead><tr><th>Project</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th class="we">Sat</th><th class="we">Sun</th><th class="right">Total</th></tr></thead><tbody>'
                    . '<tr><td class="bold">Component gallery</td><td class="num">4.0</td><td class="num">6.5</td><td class="num">3.0</td><td class="num">5.0</td><td class="num">2.5</td><td class="we">—</td><td class="we">—</td><td class="right num bold">21.0</td></tr>'
                    . '<tr><td class="bold">Drawing editor</td><td class="num">3.5</td><td class="num">1.5</td><td class="num">5.5</td><td class="num">3.0</td><td class="num">4.0</td><td class="we">—</td><td class="we">—</td><td class="right num bold">17.5</td></tr>'
                    . '<tr><td class="bold">Platform on-call</td><td class="num">1.0</td><td class="num">1.0</td><td class="num over">9.0</td><td class="num">0.5</td><td class="num">1.0</td><td class="we num">2.0</td><td class="we">—</td><td class="right num bold">14.5</td></tr>'
                    . '<tr><td class="bold">Internal / admin</td><td class="num">0.5</td><td class="num">1.0</td><td class="num">0.5</td><td class="num">1.5</td><td class="num">1.0</td><td class="we">—</td><td class="we">—</td><td class="right num bold">4.5</td></tr>'
                    . '</tbody><tfoot><tr><td>Daily total</td><td class="num">9.0</td><td class="num">10.0</td><td class="num">18.0</td><td class="num">10.0</td><td class="num">8.5</td><td class="we num">2.0</td><td class="we">—</td><td class="right num">57.5</td></tr></tfoot></table></div>',
                    '<div class="ft"><span>Billable 53.0 h · internal 4.5 h</span><span>Utilisation 92%</span></div>'
                ),
            ],
            [
                'slug' => 'feature-matrix-table',
                'name' => 'Feature matrix table',
                'name_ar' => 'جدول مصفوفة الميزات',
                'tagline' => 'Product comparison with tri-state support marks.',
                'summary' => 'Comparison tables usually lie by reducing everything to yes or no. This one has three states - full, partial and none - so "supported with limitations" has somewhere honest to live, with the caveat in a caption.',
                'accent' => '#7c3aed',
                'tags' => ['comparison', 'matrix', 'features', 'product'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Three support states instead of two', 'Caveats carried in a caption under the mark', 'Winning column tinted throughout'],
                'height' => 600,
                'max' => 880,
                'css' => "th,td{text-align:center}\nth:first-child,td:first-child{text-align:left}\n.us{background:var(--acc-soft)}\nth.us{background:var(--acc);color:#fff}\n.full{color:var(--ok)}\n.part{color:var(--warn)}\n.none{color:var(--faint)}\n.cap{display:block;font-size:10.5px;color:var(--mut);margin-top:3px}",
                'body' => self::wrap(
                    self::head('How we compare', 'Feature support as of September 2026'),
                    self::grid(
                        ['Capability', '<span class="us" style="display:block">Frugal</span>', 'Competitor A', 'Competitor B'],
                        [
                            ['Single-file export', '<span class="us"><span class="full">' . Kit::icon('check', 18, 2.4) . '</span></span>', '<span class="part">' . Kit::icon('minus', 18, 2.4) . '<span class="cap">paid tier only</span></span>', '<span class="none">' . Kit::icon('x', 18, 2.4) . '</span>'],
                            ['Offline editing', '<span class="us"><span class="full">' . Kit::icon('check', 18, 2.4) . '</span></span>', '<span class="none">' . Kit::icon('x', 18, 2.4) . '</span>', '<span class="part">' . Kit::icon('minus', 18, 2.4) . '<span class="cap">desktop app</span></span>'],
                            ['Arabic interface', '<span class="us"><span class="full">' . Kit::icon('check', 18, 2.4) . '</span></span>', '<span class="none">' . Kit::icon('x', 18, 2.4) . '</span>', '<span class="none">' . Kit::icon('x', 18, 2.4) . '</span>'],
                            ['Team libraries', '<span class="us"><span class="part">' . Kit::icon('minus', 18, 2.4) . '<span class="cap">in beta</span></span></span>', '<span class="full">' . Kit::icon('check', 18, 2.4) . '</span>', '<span class="full">' . Kit::icon('check', 18, 2.4) . '</span>'],
                            ['Free tier', '<span class="us"><span class="full">' . Kit::icon('check', 18, 2.4) . '</span></span>', '<span class="part">' . Kit::icon('minus', 18, 2.4) . '<span class="cap">14-day trial</span></span>', '<span class="full">' . Kit::icon('check', 18, 2.4) . '</span>'],
                        ]
                    ),
                    '<div class="ft"><span><span class="full">' . Kit::icon('check', 13, 2.4) . '</span> full · <span class="part">' . Kit::icon('minus', 13, 2.4) . '</span> partial · <span class="none">' . Kit::icon('x', 13, 2.4) . '</span> none</span><span>Compiled from public documentation</span></div>'
                ),
            ],
            [
                'slug' => 'release-changelog-table',
                'name' => 'Release changelog table',
                'name_ar' => 'جدول سجل الإصدارات',
                'tagline' => 'Versions with change type tags and a diff summary.',
                'summary' => 'A changelog that can be scanned rather than read: the version is monospaced, the change type is a chip in the colour convention people already know, and the added and removed line counts sit where a diff would put them.',
                'accent' => '#16a34a',
                'tags' => ['changelog', 'releases', 'versions', 'developer'],
                'stack' => ['HTML', 'CSS'],
                'features' => ['Semantic version tags with type chips', 'Added and removed line counts', 'Breaking changes flagged separately'],
                'height' => 540,
                'max' => 940,
                'css' => "tbody tr:hover{background:var(--soft)}\n.ver{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700}\n.add{color:var(--ok);font-weight:650}\n.del{color:var(--bad);font-weight:650}",
                'body' => self::wrap(
                    self::head('Changelog', 'frugal-ui · published to npm', self::btn('Subscribe', 'bell')),
                    self::grid(
                        ['Version', 'Released', 'Type', 'Summary', 'Diff'],
                        [
                            ['<span class="ver">4.12.0</span>', '<span class="num mut">12 Sep 2026</span>', Kit::pill('Feature', 'ok'), self::two('Template panel in the editor', '6 commits · Omar Saleh'), '<span class="add num">+842</span> <span class="del num">-91</span>'],
                            ['<span class="ver">4.11.3</span>', '<span class="num mut">04 Sep 2026</span>', Kit::pill('Fix', 'info'), self::two('Clip masks survive export', '2 commits · Maya Rahman'), '<span class="add num">+64</span> <span class="del num">-38</span>'],
                            ['<span class="ver">4.11.0</span>', '<span class="num mut">21 Aug 2026</span>', Kit::pill('Breaking', 'bad'), self::two('Icon API renamed', '11 commits · Lina Haddad'), '<span class="add num">+1,204</span> <span class="del num">-980</span>'],
                            ['<span class="ver">4.10.2</span>', '<span class="num mut">09 Aug 2026</span>', Kit::pill('Security', 'warn'), self::two('Upgrade SVG sanitiser', '1 commit · Nour Sabbagh'), '<span class="add num">+18</span> <span class="del num">-12</span>'],
                            ['<span class="ver">4.10.0</span>', '<span class="num mut">28 Jul 2026</span>', Kit::pill('Feature', 'ok'), self::two('Dark theme tokens', '8 commits · Sara Aziz'), '<span class="add num">+611</span> <span class="del num">-142</span>'],
                        ]
                    ),
                    '<div class="ft"><span>5 of 118 releases</span><a href="#">Migration guides</a></div>'
                ),
            ],
            [
                'slug' => 'warehouse-picking-table',
                'name' => 'Warehouse picking list',
                'name_ar' => 'جدول قائمة التجهيز',
                'tagline' => 'Pick list ordered by aisle with quantity check-off.',
                'summary' => 'A pick list sorted the way a picker walks: by aisle and bin, not by order. Each line is checked off as it is picked, the counter follows, and short picks get their own state so the packer is warned before the box is sealed.',
                'accent' => '#c2410c',
                'tags' => ['warehouse', 'picking', 'fulfilment', 'operations'],
                'stack' => ['HTML', 'CSS', 'JS'],
                'features' => ['Sorted by walking route, not by order', 'Check-off with a live progress counter', 'Short-pick state distinct from picked'],
                'height' => 580,
                'max' => 900,
                'css' => "tbody tr:hover{background:var(--soft)}\ntr.picked{background:var(--ok-bg)}\ntr.picked:hover{background:var(--ok-bg)}\ntr.picked .item{text-decoration:line-through;opacity:.7}\n.bin{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-weight:700;font-size:12.5px;padding:4px 8px;border-radius:7px;background:var(--soft);border:1px solid var(--bd)}\n.bar{height:8px;border-radius:999px;background:var(--soft);overflow:hidden;margin-top:8px}\n.bar i{display:block;height:100%;background:var(--acc);transition:width .2s}",
                'js' => "const boxes=[...document.querySelectorAll('tbody input[type=checkbox]')];\n"
                    . "const fill=document.getElementById('fill');\n"
                    . "const label=document.getElementById('done');\n"
                    . "function refresh(){\n"
                    . "  const done=boxes.filter(b=>b.checked).length;\n"
                    . "  fill.style.width=(done/boxes.length*100)+'%';\n"
                    . "  label.textContent=done+' of '+boxes.length+' lines picked';\n"
                    . "}\n"
                    . "boxes.forEach(box=>box.addEventListener('change',()=>{\n"
                    . "  box.closest('tr').classList.toggle('picked',box.checked);\n"
                    . "  refresh();\n"
                    . "}));\nrefresh();",
                'body' => self::wrap(
                    self::head('Pick list PL-4482', 'Wave 3 · 6 orders · zone A-C', Kit::pill('In progress', 'warn', true)),
                    '<div class="pad" style="padding-bottom:0"><span class="sm mut" id="done">0 of 5 lines picked</span><span class="bar"><i id="fill" style="width:0"></i></span></div>',
                    '<div style="overflow-x:auto"><table><thead><tr><th style="width:44px"></th><th>Bin</th><th>Item</th><th class="right">Qty</th><th>Order</th><th class="right">State</th></tr></thead><tbody>'
                    . '<tr><td>' . self::check(false, 'Mark picked') . '</td><td><span class="bin">A-04-2</span></td><td class="item">' . self::two('Aurora Desk Lamp', 'LMP-0041') . '</td><td class="right num bold">2</td><td class="mut num">#3104</td><td class="right">' . Kit::pill('Pending', 'neutral') . '</td></tr>'
                    . '<tr><td>' . self::check(false, 'Mark picked') . '</td><td><span class="bin">A-09-1</span></td><td class="item">' . self::two('Halo Floor Lamp', 'LMP-0088') . '</td><td class="right num bold">1</td><td class="mut num">#3101</td><td class="right">' . Kit::pill('Pending', 'neutral') . '</td></tr>'
                    . '<tr><td>' . self::check(false, 'Mark picked') . '</td><td><span class="bin">B-02-4</span></td><td class="item">' . self::two('Nimbus Chair', 'CHR-2210') . '</td><td class="right num bold">4</td><td class="mut num">#3098</td><td class="right">' . Kit::pill('Short 1', 'bad') . '</td></tr>'
                    . '<tr><td>' . self::check(false, 'Mark picked') . '</td><td><span class="bin">B-07-3</span></td><td class="item">' . self::two('Terra Side Table', 'TBL-1187') . '</td><td class="right num bold">3</td><td class="mut num">#3104</td><td class="right">' . Kit::pill('Pending', 'neutral') . '</td></tr>'
                    . '<tr><td>' . self::check(false, 'Mark picked') . '</td><td><span class="bin">C-01-1</span></td><td class="item">' . self::two('Vista Shelving', 'SHL-0302') . '</td><td class="right num bold">1</td><td class="mut num">#3090</td><td class="right">' . Kit::pill('Pending', 'neutral') . '</td></tr>'
                    . '</tbody></table></div>',
                    '<div class="ft"><span>Route A-04 to C-01 · 68 m</span><span>Target 9 minutes</span></div>'
                ),
            ],
        ];
    }
}
