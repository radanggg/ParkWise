<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') {
            $out .= strtoupper($p[0]);
        }
    }
    return $out ?: 'PW';
}

function flash(string $msg, string $type = 'info'): void {
    $_SESSION['pw_toast'] = ['msg' => $msg, 'type' => $type];
}

function popFlash(): ?array {
    if (!empty($_SESSION['pw_toast'])) {
        $f = $_SESSION['pw_toast'];
        unset($_SESSION['pw_toast']);
        return $f;
    }
    return null;
}

function csrfToken(): string {
    if (empty($_SESSION['pw_csrf'])) {
        $_SESSION['pw_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['pw_csrf'];
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['pw_csrf'] ?? '', $token);
}

function usingDatabase(): bool {
    return db() !== null;
}

function initStore(): void {
    if (isset($_SESSION['pw_initialized'])) {
        return;
    }
    $_SESSION['pw_initialized'] = true;

    $_SESSION['pw_demo_users'] = [
        'customer' => [
            'id' => 1,
            'name' => 'Jordan Davis',
            'email' => 'jordan@email.com',
            'phone' => '+63 917 111 2233',
            'roles' => ['customer'],
            'status' => 'Active',
        ],
        'owner' => [
            'id' => 2,
            'name' => 'Alex Morgan',
            'email' => 'alex@email.com',
            'phone' => '+63 917 222 3344',
            'roles' => ['customer', 'owner'],
            'status' => 'Active',
        ],
        'superadmin' => [
            'id' => 3,
            'name' => 'Taylor Smith',
            'email' => 'admin@parkwise.ph',
            'phone' => '+63 917 333 4455',
            'roles' => ['superadmin'],
            'status' => 'Active',
        ],
        'guard' => [
            'id' => 4,
            'name' => 'Chris Reyes',
            'email' => 'guard@parkwise.ph',
            'phone' => '+63 917 444 5566',
            'roles' => ['guard'],
            'business' => 'Northgate Parking Hub',
            'status' => 'Active',
        ],
        'guest' => [
            'id' => 0,
            'name' => 'Guest',
            'email' => '',
            'phone' => '',
            'roles' => ['guest'],
            'status' => 'Active',
        ],
    ];

    $_SESSION['pw_lots'] = [
        [
            'id' => 1,
            'name' => 'Northgate Parking Hub',
            'owner' => 'Northgate Mobility',
            'area' => 'Iligan City',
            'address' => 'Quezon Avenue, Iligan City',
            'rate' => 25,
            'available' => 6,
            'total' => 40,
            'rating' => 4.8,
            'reviews' => 126,
            'distance' => '0.8 km',
            'hours' => 'Open 24/7',
            'amenities' => ['24/7 CCTV', 'Covered Bays', 'Guard on Duty'],
            'image' => 'https://images.unsplash.com/photo-1590674899484-d5640e854abe?auto=format&fit=crop&w=900&q=80',
            'closed' => false,
        ],
        [
            'id' => 2,
            'name' => 'Central Square Parking',
            'owner' => 'CS Property Group',
            'area' => 'Downtown',
            'address' => 'Aguinaldo Street, Iligan City',
            'rate' => 30,
            'available' => 2,
            'total' => 28,
            'rating' => 4.6,
            'reviews' => 89,
            'distance' => '1.2 km',
            'hours' => '6:00 AM - 11:00 PM',
            'amenities' => ['Well-lit', 'EV Ready', 'Quick Exit'],
            'image' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=900&q=80',
            'closed' => false,
        ],
        [
            'id' => 3,
            'name' => 'Riverside Parking Deck',
            'owner' => 'Riverside Holdings',
            'area' => 'Tibanga',
            'address' => 'Tibanga Highway, Iligan City',
            'rate' => 20,
            'available' => 0,
            'total' => 32,
            'rating' => 4.4,
            'reviews' => 54,
            'distance' => '2.4 km',
            'hours' => '7:00 AM - 10:00 PM',
            'amenities' => ['24/7 CCTV', 'Motorcycle Zone'],
            'image' => 'https://images.unsplash.com/photo-1573348722427-f1d6819fdf98?auto=format&fit=crop&w=900&q=80',
            'closed' => false,
        ],
    ];

    $_SESSION['pw_spaces'] = [
        ['id' => 1, 'code' => 'A-01', 'zone' => 'Main entrance', 'type' => 'Covered', 'status' => 'Occupied', 'plate' => 'ABC 1234', 'timeIn' => '08:42 AM'],
        ['id' => 2, 'code' => 'A-02', 'zone' => 'Main entrance', 'type' => 'Covered', 'status' => 'Available', 'plate' => null, 'timeIn' => null],
        ['id' => 3, 'code' => 'A-03', 'zone' => 'Main entrance', 'type' => 'Standard', 'status' => 'Unavailable', 'plate' => null, 'timeIn' => null],
        ['id' => 4, 'code' => 'A-04', 'zone' => 'Main entrance', 'type' => 'Standard', 'status' => 'Available', 'plate' => null, 'timeIn' => null],
        ['id' => 5, 'code' => 'B-01', 'zone' => 'East wing', 'type' => 'Standard', 'status' => 'Occupied', 'plate' => 'KLM 9081', 'timeIn' => '09:15 AM'],
        ['id' => 6, 'code' => 'B-02', 'zone' => 'East wing', 'type' => 'Motorcycle', 'status' => 'Available', 'plate' => null, 'timeIn' => null],
        ['id' => 7, 'code' => 'B-03', 'zone' => 'East wing', 'type' => 'Standard', 'status' => 'Available', 'plate' => null, 'timeIn' => null],
        ['id' => 8, 'code' => 'B-04', 'zone' => 'East wing', 'type' => 'Standard', 'status' => 'Available', 'plate' => null, 'timeIn' => null],
        ['id' => 9, 'code' => 'C-01', 'zone' => 'West wing', 'type' => 'Standard', 'status' => 'Occupied', 'plate' => 'DEF 4512', 'timeIn' => '10:05 AM'],
        ['id' => 10, 'code' => 'C-02', 'zone' => 'West wing', 'type' => 'Covered', 'status' => 'Available', 'plate' => null, 'timeIn' => null],
        ['id' => 11, 'code' => 'C-03', 'zone' => 'West wing', 'type' => 'Standard', 'status' => 'Unavailable', 'plate' => null, 'timeIn' => null],
        ['id' => 12, 'code' => 'C-04', 'zone' => 'West wing', 'type' => 'Standard', 'status' => 'Available', 'plate' => null, 'timeIn' => null],
    ];

    $_SESSION['pw_vehicles'] = [
        ['id' => 1, 'model' => 'Toyota Vios', 'plate' => 'ABC 1234', 'type' => 'Sedan', 'color' => 'Silver', 'default' => true, 'added' => 'Oct 20, 2026'],
        ['id' => 2, 'model' => 'Honda Click 125i', 'plate' => 'XYZ 5678', 'type' => 'Motorcycle', 'color' => 'Matte Black', 'default' => false, 'added' => 'Oct 25, 2026'],
    ];

    $_SESSION['pw_favorites'] = [1];

    $_SESSION['pw_sessions'] = [
        [
            'id' => 1731000001,
            'lotId' => 2,
            'business' => 'Central Square Parking',
            'plate' => 'ABC 1234',
            'date' => '11/11/2026',
            'timeIn' => '09:10 AM',
            'timeOut' => '11:05 AM',
            't' => 1731000000,
            'rate' => 30,
            'hours' => 2,
            'total' => 60,
            'status' => 'Completed',
            'paid' => true,
            'no' => 'PW-000001',
            'by' => 'Chris Reyes',
        ],
    ];

    $_SESSION['pw_reviews'] = [
        [
            'id' => 1,
            'lotId' => 1,
            'business' => 'Northgate Parking Hub',
            'rating' => 5,
            'text' => 'Guard is quick and the lot is well lit.',
            'date' => '11/10/2026',
            'by' => 'Liza M.',
        ],
    ];

    $_SESSION['pw_apps'] = [
        [
            'id' => 1,
            'who' => 'Morgan Lee',
            'email' => 'morgan@leesparking.ph',
            'phone' => '+63 912 888 1001',
            'acct' => '',
            'biz' => "Lee's Parking",
            'date' => 'Nov 12, 2026',
            'status' => 'Pending',
            'reason' => '',
            'titleMatch' => true,
            'docs' => [
                ['type' => 'Land Title', 'name' => 'Morgan Lee', 'st' => 'pending', 'why' => '', 'file' => null],
                ['type' => 'Business Permit', 'name' => "Lee's Parking", 'st' => 'pending', 'why' => '', 'file' => null],
                ['type' => 'Valid ID', 'name' => 'Morgan Lee', 'st' => 'pending', 'why' => '', 'file' => null],
            ],
        ],
        [
            'id' => 2,
            'who' => 'Sam Rivera',
            'email' => 'sam@riversidedeck.ph',
            'phone' => '+63 912 888 2002',
            'acct' => '',
            'biz' => 'Riverside Deck',
            'date' => 'Nov 11, 2026',
            'status' => 'Pending',
            'reason' => '',
            'titleMatch' => false,
            'docs' => [
                ['type' => 'Land Title', 'name' => 'Samuel R. Rivera', 'st' => 'pending', 'why' => '', 'file' => null],
                ['type' => 'Business Permit', 'name' => 'Riverside Deck', 'st' => 'pending', 'why' => '', 'file' => null],
                ['type' => 'Valid ID', 'name' => 'Sam Rivera', 'st' => 'pending', 'why' => '', 'file' => null],
            ],
        ],
    ];

    $_SESSION['pw_guards'] = [
        ['id' => 1, 'name' => 'Chris Reyes', 'biz' => 'Northgate Parking Hub', 'ent' => 'Main entrance', 'shift' => '8 AM - 4 PM', 'phone' => '+63 917 444 5566'],
        ['id' => 2, 'name' => 'Pat Santos', 'biz' => 'Northgate Parking Hub', 'ent' => 'East entrance', 'shift' => '4 PM - 12 AM', 'phone' => '+63 917 777 8899'],
    ];

    $_SESSION['pw_customers'] = [
        ['name' => 'Jordan Davis', 'email' => 'jordan@email.com', 'phone' => '+63 917 111 2233', 'joined' => 'Oct 20, 2026'],
        ['name' => 'Alex Morgan', 'email' => 'alex@email.com', 'phone' => '+63 917 222 3344', 'joined' => 'Oct 15, 2026'],
        ['name' => 'Liza M.', 'email' => 'liza@email.com', 'phone' => '+63 917 555 6677', 'joined' => 'Oct 28, 2026'],
    ];

    $_SESSION['pw_logs'] = [
        ['actor' => 'Taylor Smith', 'action' => 'Approved owner application', 'target' => 'JC Parking', 'note' => '', 'when' => 'Nov 12 · 8:20 AM'],
        ['actor' => 'Alex Morgan', 'action' => 'Added parking space', 'target' => 'Northgate · A-12', 'note' => '', 'when' => 'Nov 12 · 8:05 AM'],
    ];

    $_SESSION['pw_status'] = [];
    $_SESSION['pw_promoted'] = [];
    $_SESSION['pw_guard_mode'] = 'in';
    $_SESSION['pw_guard_result'] = null;
}

function getLots(): array {
    $pdo = db();
    if ($pdo) {
        try {
            $rows = $pdo->query('SELECT * FROM parking_lots ORDER BY id ASC')->fetchAll();
            if ($rows) {
                return array_map(static function (array $r): array {
                    return [
                        'id' => (int)$r['id'],
                        'name' => $r['name'],
                        'owner' => $r['owner_display'],
                        'area' => $r['area'],
                        'address' => $r['address'],
                        'rate' => (float)$r['hourly_rate'],
                        'available' => (int)$r['available_spaces'],
                        'total' => (int)$r['total_spaces'],
                        'rating' => (float)$r['rating'],
                        'reviews' => (int)$r['review_count'],
                        'distance' => $r['distance'],
                        'hours' => $r['hours'],
                        'amenities' => array_filter(array_map('trim', explode(',', (string)$r['amenities']))),
                        'image' => $r['image_url'],
                        'closed' => (bool)$r['is_closed'],
                    ];
                }, $rows);
            }
        } catch (Throwable $e) {
            // Fall through to session store
        }
    }
    return $_SESSION['pw_lots'];
}

function findLot(int $id): ?array {
    foreach (getLots() as $l) {
        if ((int)$l['id'] === $id) {
            return $l;
        }
    }
    return null;
}

function vacancyInfo(array $lot): array {
    if (!empty($lot['closed'])) {
        return ['Closed', 'grey'];
    }
    $avail = (int)$lot['available'];
    $total = max(1, (int)$lot['total']);
    if ($avail === 0) {
        return ['Full', 'red'];
    }
    $ratio = $avail / $total;
    if ($ratio < 0.25) {
        return ['Almost full', 'orange'];
    }
    if ($ratio < 0.5) {
        return ['Limited', 'amber'];
    }
    return ['Available', 'green'];
}

function roleLabels(): array {
    return [
        'customer' => 'Customer',
        'owner' => 'Owner / Admin',
        'superadmin' => 'Super Admin',
        'guard' => 'Guard',
        'guest' => 'Guest',
    ];
}

function menusByRole(): array {
    return [
        'customer' => [
            ['OVERVIEW', ['Dashboard']],
            ['DISCOVER', ['Find Parking', 'My Vehicles', 'Favorites']],
            ['MY ACTIVITY', ['Parking History', 'My Reviews', 'Owner Application']],
            ['ACCOUNT', ['My Profile', 'Settings']],
        ],
        'owner' => [
            ['OVERVIEW', ['Dashboard']],
            ['BUSINESS', ['My Businesses', 'Parking Spaces', 'Guards']],
            ['OPERATIONS', ['Transactions', 'Reports & Reviews']],
            ['ACCOUNT', ['My Profile', 'Settings']],
        ],
        'superadmin' => [
            ['OVERVIEW', ['Dashboard']],
            ['PLATFORM', ['Owner Applications', 'Business Directory', 'Customer Accounts', 'Guard Accounts']],
            ['MONITORING', ['Platform Reports', 'Activity Logs']],
            ['ACCOUNT', ['My Profile', 'Settings']],
        ],
        'guard' => [
            ['PARKING TERMINAL', ['Time In', 'Time Out', 'Session Log']],
        ],
        'guest' => [
            ['BROWSE', ['Find Parking']],
        ],
    ];
}

function pageIcons(): array {
    return [
        'Dashboard' => '▦',
        'Find Parking' => '⌕',
        'My Vehicles' => '▣',
        'Parking History' => '◷',
        'Favorites' => '♡',
        'My Reviews' => '☆',
        'Owner Application' => '♙',
        'My Businesses' => '▤',
        'Parking Spaces' => '▦',
        'Guards' => '♟',
        'Transactions' => '₱',
        'Reports & Reviews' => '⚑',
        'Owner Applications' => '♧',
        'Business Directory' => '⌂',
        'Customer Accounts' => '♙',
        'Guard Accounts' => '♟',
        'Platform Reports' => '⚑',
        'Activity Logs' => '◷',
        'Settings' => '⚙',
        'My Profile' => '◉',
        'Time In' => '↘',
        'Time Out' => '↗',
        'Session Log' => '▤',
    ];
}

function currentUser(): ?array {
    return $_SESSION['pw_user'] ?? null;
}

function currentRole(): string {
    return $_SESSION['pw_role'] ?? 'customer';
}

function loginAsDemo(string $role, ?string $acctKey = null): void {
    $acctKey = $acctKey ?: $role;
    $_SESSION['pw_acct'] = $acctKey;
    $base = $_SESSION['pw_demo_users'][$acctKey] ?? $_SESSION['pw_demo_users']['customer'];
    $roles = $base['roles'];
    if ((in_array($base['name'], $_SESSION['pw_promoted'], true) || in_array($acctKey, $_SESSION['pw_promoted'], true)) && !in_array('owner', $roles, true)) {
        $roles[] = 'owner';
    }
    if ($role === 'superadmin' && !in_array('superadmin', $roles, true)) {
        $roles[] = 'superadmin';
    }
    $base['roles'] = $roles;
    $_SESSION['pw_user'] = $base;
    $_SESSION['pw_role'] = $role;
}

function logActivity(string $actor, string $action, string $target, string $note = ''): void {
    $when = date('M j · g:i A');
    $_SESSION['pw_logs'][] = [
        'actor' => $actor,
        'action' => $action,
        'target' => $target,
        'note' => $note,
        'when' => $when,
    ];
    $pdo = db();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('INSERT INTO activity_logs (actor, action, target, note, logged_when) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$actor, $action, $target, $note, $when]);
        } catch (Throwable $e) {
        }
    }
}

function myPlates(): array {
    return array_map(static fn(array $v): string => strtoupper($v['plate']), $_SESSION['pw_vehicles']);
}

function mySessions(): array {
    $plates = myPlates();
    return array_values(array_filter($_SESSION['pw_sessions'], static fn(array $s): bool => in_array(strtoupper($s['plate']), $plates, true)));
}

function canReviewLot(array $lot): bool {
    $user = currentUser();
    if (!$user || currentRole() !== 'customer') {
        return false;
    }
    if (($_SESSION['pw_acct'] ?? '') === 'owner' && (int)$lot['id'] === 1) {
        return false;
    }
    $hasPaid = false;
    foreach (mySessions() as $s) {
        if (!empty($s['paid']) && (int)($s['lotId'] ?? 1) === (int)$lot['id']) {
            $hasPaid = true;
            break;
        }
    }
    if (!$hasPaid) {
        return false;
    }
    foreach ($_SESSION['pw_reviews'] as $r) {
        if ($r['business'] === $lot['name'] && ($r['by'] ?? '') === $user['name']) {
            return false;
        }
    }
    return true;
}

function statusPill(string $text, string $type = ''): string {
    return '<span class="status ' . e($type) . '">' . e($text) . '</span>';
}

function acctStatusBadge(string $name): string {
    $st = $_SESSION['pw_status'][$name] ?? '';
    if ($st === '') {
        return statusPill('Active');
    }
    return statusPill($st, $st === 'Warned' ? 'pending' : 'danger');
}
