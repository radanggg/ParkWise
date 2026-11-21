<?php
declare(strict_types=1);

function renderLotCard(array $lot, string $redirectUrl = 'index.php#featured', bool $showFav = true): string {
    [$label, $color] = vacancyInfo($lot);
    $isClosed = !empty($lot['closed']);
    $avail = $isClosed ? 0 : (int)$lot['available'];
    $total = max(1, (int)$lot['total']);
    $pct = $isClosed ? 0 : (int)round(($avail / $total) * 100);
    $favs = $_SESSION['pw_favorites'] ?? [];
    $isFav = in_array((int)$lot['id'], $favs, true);
    $amenities = $lot['amenities'] ?? ['24/7 CCTV', 'Guard on Duty'];
    $chipsHtml = implode('', array_map(static fn(string $a): string => '<span class="chip">' . e($a) . '</span>', $amenities));

    $favBtn = '';
    if ($showFav) {
        $favBtn = '<form method="post" action="index.php" class="fav-form">'
            . '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">'
            . '<input type="hidden" name="action" value="toggle_fav">'
            . '<input type="hidden" name="lot_id" value="' . (int)$lot['id'] . '">'
            . '<input type="hidden" name="redirect" value="' . e($redirectUrl) . '">'
            . '<button type="submit" class="fav-btn ' . ($isFav ? 'active' : '') . '" title="' . ($isFav ? 'Remove from favorites' : 'Save lot') . '">' . ($isFav ? '♥' : '♡') . '</button>'
            . '</form>';
    }

    $detailHref = strpos($redirectUrl, 'view=workspace') !== false
        ? 'index.php?view=workspace&page=' . urlencode($_GET['page'] ?? 'Find Parking') . '&lot=' . (int)$lot['id']
        : 'index.php?lot=' . (int)$lot['id'];

    $imgUrl = e($lot['image']);

    return '<article class="lot-card">'
        . '<div class="lot-photo" style="background-image:url(\'' . $imgUrl . '\')">'
        . '<span class="lot-status ' . e($color) . '">' . ($isClosed ? '–' : $avail) . ' free · ' . e($label) . '</span>'
        . '<span class="lot-hours-badge">◷ ' . e($lot['hours'] ?? 'Open 24/7') . '</span>'
        . $favBtn
        . '</div>'
        . '<div class="lot-body">'
        . '<div class="lot-title-row"><h3>' . e($lot['name']) . '</h3><span class="verified-tag">✓ Verified</span></div>'
        . '<div class="lot-meta"><span>⌖ ' . e($lot['address']) . '</span><span>• ' . e($lot['distance']) . '</span></div>'
        . '<div class="lot-owner-line">Managed by <b>' . e($lot['owner']) . '</b></div>'
        . '<div class="occupancy-wrap">'
        . '<div class="occupancy-labels"><span>Live Capacity</span><b>' . ($isClosed ? 'Closed' : $avail . ' / ' . $total . ' slots free') . '</b></div>'
        . '<div class="occupancy-bar"><div class="occupancy-fill ' . e($color) . '" style="width:' . $pct . '%"></div></div>'
        . '</div>'
        . '<div class="chip-row">' . $chipsHtml . '</div>'
        . '<div class="lot-foot">'
        . '<div><span class="lot-price">₱' . number_format((float)$lot['rate'], 0) . '<small> / hour</small></span>'
        . '<div class="lot-rating-sub">★ ' . number_format((float)$lot['rating'], 1) . ' (' . (int)$lot['reviews'] . ' reviews)</div></div>'
        . '<a class="btn btn-primary btn-sm" href="' . e($detailHref) . '">View details →</a>'
        . '</div>'
        . '</div>'
        . '</article>';
}

function renderStatCard(string $label, string $num, string $foot, string $ico): string {
    return '<div class="stat-card">'
        . '<div class="stat-top"><span>' . e($label) . '</span><span class="stat-icon">' . e($ico) . '</span></div>'
        . '<div class="stat-number">' . e($num) . '</div>'
        . '<div class="stat-foot">' . e($foot) . '</div>'
        . '</div>';
}

function renderSpaceCard(array $sp, bool $interactive = false): string {
    $st = $sp['status'];
    $pillType = $st === 'Occupied' ? 'danger' : ($st === 'Unavailable' ? 'neutral' : '');
    $infoStrip = '';
    if ($st === 'Occupied' && !empty($sp['plate'])) {
        $infoStrip = '<div class="info-strip">'
            . '<div><small>PARKED PLATE</small><b>' . e($sp['plate']) . '</b></div>'
            . '<div><small>TIME-IN</small><b>' . e($sp['timeIn'] ?? '—') . '</b></div>'
            . '</div>';
    } else {
        $infoStrip = '<div class="info-strip muted-strip">'
            . '<div><small>SLOT TYPE</small><b>' . e($sp['type'] ?? 'Standard') . '</b></div>'
            . '<div><small>STATUS</small><b>' . ($st === 'Available' ? 'Ready for entry' : 'Blocked / Maintenance') . '</b></div>'
            . '</div>';
    }

    $toggleHtml = '';
    if ($interactive && $st !== 'Occupied') {
        $toggleHtml = '<form method="post" action="index.php" style="margin-top:10px">'
            . '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">'
            . '<input type="hidden" name="action" value="toggle_space">'
            . '<input type="hidden" name="space_id" value="' . (int)$sp['id'] . '">'
            . '<button type="submit" class="table-action">' . ($st === 'Available' ? 'Mark unavailable' : 'Mark available') . '</button>'
            . '</form>';
    }

    return '<div class="space-card">'
        . '<div class="space-card-top"><h4>Space ' . e($sp['code']) . '</h4><span class="chip">' . e($sp['type'] ?? 'Standard') . '</span></div>'
        . '<p>' . e($sp['zone']) . '</p>'
        . statusPill($st, $pillType)
        . $infoStrip
        . $toggleHtml
        . '</div>';
}

function renderPageHeader(string $title, string $desc, string $actionHtml = ''): string {
    return '<div class="page-title-row"><div><h1>' . e($title) . '</h1><p>' . e($desc) . '</p></div>'
        . ($actionHtml !== '' ? '<div class="page-actions">' . $actionHtml . '</div>' : '')
        . '</div>';
}

function renderTablePanel(string $title, array $headers, string $rowsHtml, string $extraHtml = ''): string {
    $th = implode('', array_map(static fn(string $h): string => '<th>' . e($h) . '</th>', $headers));
    $body = $rowsHtml !== '' ? $rowsHtml : '<tr><td colspan="' . count($headers) . '" class="empty-state">No records to display.</td></tr>';
    return '<section class="table-panel">'
        . '<div class="table-head"><h3>' . e($title) . '</h3>' . $extraHtml . '</div>'
        . '<div class="table-wrap"><table><thead><tr>' . $th . '</tr></thead><tbody>' . $body . '</tbody></table></div>'
        . '</section>';
}