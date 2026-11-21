<?php
declare(strict_types=1);

$user = currentUser();
$role = currentRole();
$page = (string)($_GET['page'] ?? ($role === 'guest' ? 'Find Parking' : 'Dashboard'));
$menus = menusByRole()[$role] ?? menusByRole()['customer'];
$icons = pageIcons();
$roleLabels = roleLabels();
$lots = getLots();
$csrf = csrfToken();
?>
<div id="workspace" class="workspace">
  <aside class="sidebar" id="sidebar">
    <a class="brand sidebar-brand" href="index.php?view=workspace&page=Dashboard">
      <span class="brand-mark">P</span><span>park<span class="brand-blue">wise</span></span>
    </a>
    <div class="side-user">
      <div class="avatar"><?= e(initials($user['name'])) ?></div>
      <div>
        <b><?= e($user['name']) ?></b>
        <small><?= e(($roleLabels[$role] ?? 'Customer') . ' account') ?></small>
      </div>
    </div>
    <div id="sideNav">
      <?php foreach ($menus as [$heading, $items]): ?>
        <div class="nav-group">
          <div class="nav-heading"><?= e($heading) ?></div>
          <?php foreach ($items as $item):
              $href = $item === 'Owner Application'
                  ? 'index.php?view=recruit'
                  : 'index.php?view=workspace&page=' . urlencode($item);
          ?>
            <a class="nav-link <?= $page === $item ? 'active' : '' ?>" href="<?= e($href) ?>">
              <span class="nav-ico"><?= e($icons[$item] ?? '•') ?></span><?= e($item) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="sidebar-bottom">
      <?php if ($role !== 'guard'): ?>
        <div class="help-card">
          <span>✦</span>
          <b>Need a hand?</b>
          <small>Visit our help center for support.</small>
          <a href="index.php?view=workspace&page=Settings">Get support →</a>
        </div>
      <?php endif; ?>
      <form method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="logout">
        <button class="side-logout" type="submit">↪ <span>Log out</span></button>
      </form>
    </div>
  </aside>

  <div class="workspace-main">
    <header class="topbar">
      <button class="icon-btn mobile-only" type="button" data-action="sidebar-toggle">☰</button>
      <div class="breadcrumb">
        <span>Workspace</span><b>/</b><strong><?= e($page) ?></strong>
      </div>
      <div class="topbar-actions">
        <a class="icon-btn notification-btn" title="Notifications" href="index.php?view=workspace&page=<?= urlencode($page) ?>&modal=notifications">♧<i></i></a>
        <div class="account-wrap">
          <button class="account-trigger" type="button" data-action="account-menu">
            <span class="avatar small"><?= e(initials($user['name'])) ?></span>
            <span class="account-label">
              <b><?= e($user['name']) ?></b>
              <small><?= e($roleLabels[$role] ?? 'Customer') ?></small>
            </span>
            <span>⌄</span>
          </button>
          <div class="account-menu hidden" id="accountMenu">
            <small class="menu-caption">SWITCH ACCOUNT</small>
            <?php foreach (($user['roles'] ?? ['customer']) as $r): ?>
              <form method="post" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="switch_role">
                <input type="hidden" name="role" value="<?= e($r) ?>">
                <button type="submit" class="role-option <?= $r === $role ? 'selected' : '' ?>">
                  <?= e($roleLabels[$r] ?? $r) ?> <?= $r === $role ? '✓' : '' ?>
                </button>
              </form>
            <?php endforeach; ?>
            <a href="index.php?view=workspace&page=<?= urlencode('My Profile') ?>">◉   My profile</a>
            <form method="post" action="index.php">
              <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
              <input type="hidden" name="action" value="logout">
              <button type="submit">↪   Log out</button>
            </form>
          </div>
        </div>
      </div>
    </header>

    <main class="dashboard-content">
      <?php if ($page === 'Dashboard'): ?>
        <?php if ($role === 'customer'):
            $mySess = mySessions();
            $activeSess = array_values(array_filter($mySess, static fn(array $s): bool => $s['status'] === 'Active'));
            $firstName = explode(' ', $user['name'])[0] ?? 'Driver';
        ?>
          <?= renderPageHeader("Good day, {$firstName} 👋", "Here's what's happening with your parking activity.", '<a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=' . urlencode('Find Parking') . '">Find parking →</a>') ?>

          <div class="stats-grid">
            <?= renderStatCard('PARKING SESSIONS', (string)count($mySess), 'All recorded sessions', '▣') ?>
            <?= renderStatCard('ACTIVE SESSIONS', (string)count($activeSess), 'Currently parked', '◷') ?>
            <?= renderStatCard('REGISTERED VEHICLES', (string)count($_SESSION['pw_vehicles']), 'Manage your vehicles', '▤') ?>
            <?= renderStatCard('SAVED PARKING LOTS', (string)count($_SESSION['pw_favorites']), 'Your favorites', '♡') ?>
          </div>

          <?php if (!empty($activeSess)): $cur = $activeSess[0]; ?>
            <section class="panel active-session-card" style="margin-bottom:18px">
              <div class="panel-header">
                <h3>Active parking session</h3>
                <?= statusPill('Currently Parked', 'pending') ?>
              </div>
              <div class="info-strip" style="margin-top:0">
                <div><small>PARKING LOT</small><b><?= e($cur['business']) ?></b></div>
                <div><small>PLATE NUMBER</small><b><?= e($cur['plate']) ?></b></div>
                <div><small>TIME-IN</small><b><?= e($cur['timeIn']) ?></b></div>
                <div><small>RATE</small><b>₱<?= number_format((float)$cur['rate'], 0) ?>/hr</b></div>
              </div>
            </section>
          <?php endif; ?>

          <div class="content-grid">
            <section class="panel">
              <div class="panel-header">
                <h3>Explore parking businesses</h3>
                <a href="index.php?view=workspace&page=<?= urlencode('Find Parking') ?>">View all →</a>
              </div>
              <div class="lot-admin-grid" style="grid-template-columns:repeat(2,1fr)">
                <?php foreach (array_slice($lots, 0, 2) as $l):
                    [$lbl, $col] = vacancyInfo($l);
                ?>
                  <div class="space-card">
                    <div class="space-card-top">
                      <h4><?= e($l['name']) ?></h4>
                      <span class="chip">★ <?= number_format((float)$l['rating'], 1) ?></span>
                    </div>
                    <p><?= e($l['address']) ?> · ₱<?= (int)$l['rate'] ?>/hr</p>
                    <?= statusPill($l['available'] ? $l['available'] . ' spaces available' : 'Currently full', $l['available'] ? '' : 'danger') ?>
                    <div style="margin-top:10px">
                      <a class="table-action" href="index.php?view=workspace&page=Dashboard&lot=<?= (int)$l['id'] ?>">Inspect lot →</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

            <section class="panel">
              <div class="panel-header">
                <h3>My registered vehicles</h3>
                <a href="index.php?view=workspace&page=<?= urlencode('My Vehicles') ?>">Manage →</a>
              </div>
              <div class="activity-list">
                <?php foreach ($_SESSION['pw_vehicles'] as $v): ?>
                  <div class="activity-item">
                    <span class="activity-dot">▣</span>
                    <div style="flex:1">
                      <b><?= e($v['model']) ?> · <span class="plate-badge"><?= e($v['plate']) ?></span></b>
                      <small><?= e($v['type']) ?> · <?= e($v['color']) ?></small>
                    </div>
                    <?php if (!empty($v['default'])): ?><?= statusPill('Default') ?><?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>
          </div>

          <?php
          $histRows = '';
          foreach (array_slice(array_reverse($mySess), 0, 4) as $s) {
              $histRows .= '<tr>'
                  . '<td><strong>' . e($s['business']) . '</strong></td>'
                  . '<td><span class="plate-badge">' . e($s['plate']) . '</span></td>'
                  . '<td>' . e($s['date']) . ' (' . e($s['timeIn']) . ')</td>'
                  . '<td>' . ($s['total'] ? '₱' . number_format((float)$s['total'], 2) : '—') . '</td>'
                  . '<td>' . statusPill($s['status'], $s['status'] === 'Active' ? 'pending' : '') . '</td>'
                  . '</tr>';
          }
          echo renderTablePanel('Recent parking history', ['BUSINESS', 'PLATE NUMBER', 'DATE & TIME', 'BILL', 'STATUS'], $histRows);
          ?>

        <?php elseif ($role === 'owner'):
            $activeCount = count(array_filter($_SESSION['pw_sessions'], static fn(array $s): bool => $s['status'] === 'Active'));
            $occupiedSpaces = count(array_filter($_SESSION['pw_spaces'], static fn(array $sp): bool => $sp['status'] === 'Occupied'));
            $availSpaces = count(array_filter($_SESSION['pw_spaces'], static fn(array $sp): bool => $sp['status'] === 'Available'));
        ?>
          <?= renderPageHeader('Business overview', 'Monitor your parking operations and business performance.', '<a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=Dashboard&modal=add_space">＋ Add parking space</a>') ?>

          <div class="stats-grid">
            <?= renderStatCard('TOTAL SPACES', (string)count($_SESSION['pw_spaces']), 'Across Northgate Hub', '▦') ?>
            <?= renderStatCard('OCCUPIED NOW', (string)($occupiedSpaces + $activeCount), 'Live parking occupancy', '▣') ?>
            <?= renderStatCard('AVAILABLE SPACES', (string)$availSpaces, 'Ready for walk-ins', '✓') ?>
            <?= renderStatCard("TODAY'S REVENUE", '₱4,850', '+12% from yesterday', '₱') ?>
          </div>

          <div class="content-grid">
            <section class="panel">
              <div class="panel-header"><h3>Parking activity</h3><span class="status">Live overview</span></div>
              <div class="chart">
                <?php
                $bars = [42, 65, 48, 82, 58, 91, 69];
                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                foreach ($bars as $i => $h): ?>
                  <div class="chart-col">
                    <div class="chart-bars"><i style="height:<?= round($h * 0.72) ?>%"></i><i style="height:<?= $h ?>%"></i></div>
                    <small><?= $days[$i] ?></small>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

            <section class="panel">
              <div class="panel-header">
                <h3>On-duty guards & shifts</h3>
                <a href="index.php?view=workspace&page=Guards">Manage →</a>
              </div>
              <div class="activity-list">
                <?php foreach ($_SESSION['pw_guards'] as $g): ?>
                  <div class="activity-item">
                    <span class="activity-dot">♟</span>
                    <div style="flex:1">
                      <b><?= e($g['name']) ?></b>
                      <small><?= e($g['ent']) ?> · <?= e($g['shift']) ?></small>
                    </div>
                    <?= acctStatusBadge($g['name']) ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>
          </div>

          <section class="panel">
            <div class="panel-header">
              <h3>Parking space status</h3>
              <a href="index.php?view=workspace&page=<?= urlencode('Parking Spaces') ?>">Manage spaces →</a>
            </div>
            <div class="lot-admin-grid">
              <?php foreach (array_slice($_SESSION['pw_spaces'], 0, 6) as $sp): ?>
                <?= renderSpaceCard($sp, false) ?>
              <?php endforeach; ?>
            </div>
          </section>

        <?php elseif ($role === 'superadmin'):
            $pendingApps = array_values(array_filter($_SESSION['pw_apps'], static fn(array $a): bool => $a['status'] === 'Pending'));
        ?>
          <?= renderPageHeader('Platform overview', 'A live overview of ParkWise accounts, businesses and activity.', '<a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=' . urlencode('Owner Applications') . '">Review applications</a>') ?>

          <div class="stats-grid">
            <?= renderStatCard('CUSTOMERS', '2,481', '+8.2% this month', '♙') ?>
            <?= renderStatCard('VERIFIED OWNERS', '86', 'Across the platform', '▤') ?>
            <?= renderStatCard('PARKING BUSINESSES', (string)count($lots), 'Registered businesses', '⌂') ?>
            <?= renderStatCard('PENDING APPLICATIONS', (string)count($pendingApps), 'Awaiting document check', '♧') ?>
          </div>

          <div class="content-grid">
            <section class="panel">
              <div class="panel-header"><h3>Platform activity</h3><span class="status">This week</span></div>
              <div class="chart">
                <?php
                $bars = [42, 65, 48, 82, 58, 91, 69];
                $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                foreach ($bars as $i => $h): ?>
                  <div class="chart-col">
                    <div class="chart-bars"><i style="height:<?= round($h * 0.72) ?>%"></i><i style="height:<?= $h ?>%"></i></div>
                    <small><?= $days[$i] ?></small>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

            <section class="panel">
              <div class="panel-header">
                <h3>Needs your attention</h3>
                <a href="index.php?view=workspace&page=<?= urlencode('Platform Reports') ?>">View logs →</a>
              </div>
              <div class="activity-list">
                <div class="activity-item"><span class="activity-dot">♧</span><div><b><?= count($pendingApps) ?> owner applications pending</b><small>Verify Land Title, Business Permit & Valid ID</small></div></div>
                <div class="activity-item"><span class="activity-dot">⚑</span><div><b>3 reports need investigation</b><small>Customer and business operational reports</small></div></div>
                <div class="activity-item"><span class="activity-dot">★</span><div><b>2 reviews flagged</b><small>Moderation queue ready</small></div></div>
              </div>
            </section>
          </div>

          <?php
          $appRows = '';
          foreach ($_SESSION['pw_apps'] as $a) {
              $stType = $a['status'] === 'Pending' ? 'pending' : ($a['status'] === 'Rejected' ? 'danger' : '');
              $appRows .= '<tr>'
                  . '<td><strong>' . e($a['who']) . '</strong></td>'
                  . '<td>' . e($a['biz'] ?: '—') . '</td>'
                  . '<td>' . e($a['date']) . '</td>'
                  . '<td>' . statusPill($a['status'], $stType) . '</td>'
                  . '<td><a class="table-action" href="index.php?view=workspace&page=' . urlencode('Owner Applications') . '&app=' . (int)$a['id'] . '">Review</a></td>'
                  . '</tr>';
          }
          echo renderTablePanel('Recent owner applications', ['APPLICANT', 'BUSINESS', 'SUBMITTED', 'STATUS', 'ACTION'], $appRows);
          ?>

        <?php else: // Guard Terminal
            $guardMode = $_SESSION['pw_guard_mode'] ?? 'in';
            $assignedLot = $lots[0];
            $activeNow = count(array_filter($_SESSION['pw_sessions'], static fn(array $s): bool => $s['status'] === 'Active'));
            $completedNow = count(array_filter($_SESSION['pw_sessions'], static fn(array $s): bool => $s['status'] === 'Completed'));
            $gRes = $_SESSION['pw_guard_result'] ?? null;
        ?>
          <?= renderPageHeader('Guard terminal', 'Record vehicle entry and exit for your assigned parking business.') ?>

          <div class="guard-terminal">
            <div class="stats-grid">
              <?= renderStatCard('VEHICLES PARKED', (string)$activeNow, 'Current active sessions', '▣') ?>
              <?= renderStatCard("TODAY'S ENTRIES", (string)count($_SESSION['pw_sessions']), 'Recorded arrivals', '↘') ?>
              <?= renderStatCard("TODAY'S EXITS", (string)$completedNow, 'Completed sessions', '↗') ?>
              <?= renderStatCard('AVAILABLE SLOTS', $assignedLot['available'] . ' / ' . $assignedLot['total'], $assignedLot['name'] . ' · ₱' . (int)$assignedLot['rate'] . '/hr', '⌂') ?>
            </div>

            <div class="guard-switch">
              <form method="post" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="guard_mode">
                <input type="hidden" name="mode" value="in">
                <button type="submit" class="guard-tab <?= $guardMode === 'in' ? 'active' : '' ?>" style="width:100%">
                  ↘ <b>TIME-IN</b><span>Record a vehicle entering</span>
                </button>
              </form>
              <form method="post" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="guard_mode">
                <input type="hidden" name="mode" value="out">
                <button type="submit" class="guard-tab <?= $guardMode === 'out' ? 'active' : '' ?>" style="width:100%">
                  ↗ <b>TIME-OUT</b><span>Find a vehicle and check out</span>
                </button>
              </form>
            </div>

            <section class="guard-form">
              <div class="eyebrow"><?= $guardMode === 'in' ? 'VEHICLE ARRIVAL' : 'VEHICLE DEPARTURE' ?></div>
              <h2 style="font:800 21px Manrope;margin:0 0 7px"><?= $guardMode === 'in' ? 'Record vehicle time-in' : 'Process vehicle time-out' ?></h2>
              <p><?= $guardMode === 'in' ? 'Enter the plate number to create a new parking session.' : 'Search for an active session using the vehicle plate number.' ?></p>
              <form method="post" action="index.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="guard_submit">
                <div class="form-field">
                  <label>VEHICLE PLATE NUMBER</label>
                  <input class="form-control plate-input" name="plate" placeholder="ABC 1234" maxlength="12" required autocomplete="off">
                </div>
                <button class="btn btn-primary" style="width:100%;margin-top:15px" type="submit">
                  <?= $guardMode === 'in' ? 'Confirm vehicle entry →' : 'Find active parking session →' ?>
                </button>
              </form>

              <?php if ($gRes): ?>
                <div class="guard-result">
                  <?php if ($gRes['type'] === 'in'): ?>
                    <?= statusPill('Entry recorded') ?>
                    <div class="info-strip">
                      <div><small>PLATE NUMBER</small><b><?= e($gRes['plate']) ?></b></div>
                      <div><small>LOT</small><b><?= e($gRes['business']) ?></b></div>
                      <div><small>TIME-IN</small><b><?= e($gRes['timeIn']) ?></b></div>
                      <div><small>SLOTS LEFT</small><b><?= (int)$gRes['available'] ?></b></div>
                    </div>
                  <?php elseif ($gRes['type'] === 'missing'): ?>
                    <?= statusPill('No active session', 'danger') ?>
                    <p style="margin:8px 0 0">No active parking session found for <b><?= e($gRes['plate']) ?></b>.</p>
                  <?php elseif ($gRes['type'] === 'out'): $s = $gRes['session']; ?>
                    <b>Parking bill preview</b>
                    <div class="info-strip">
                      <div><small>PLATE</small><b><?= e($s['plate']) ?></b></div>
                      <div><small>TIME-IN</small><b><?= e($s['timeIn']) ?></b></div>
                      <div><small>TIME-OUT</small><b><?= e($s['timeOut']) ?></b></div>
                      <div><small>DURATION</small><b><?= (int)$s['hours'] ?> hr × ₱<?= (int)$s['rate'] ?></b></div>
                    </div>
                    <h2 style="margin:12px 0">₱<?= number_format((float)$s['total'], 2) ?></h2>
                    <form method="post" action="index.php">
                      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                      <input type="hidden" name="action" value="confirm_payment">
                      <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                      <button class="btn btn-primary btn-sm" type="submit">Confirm payment & print bill</button>
                    </form>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </section>
          </div>
        <?php endif; ?>

      <?php elseif ($page === 'Find Parking'):
          $q = strtolower(trim((string)($_GET['q'] ?? '')));
          $area = trim((string)($_GET['area'] ?? ''));
          $list = array_filter($lots, static function (array $l) use ($q, $area): bool {
              if ($area !== '' && $l['area'] !== $area) return false;
              if ($q !== '' && strpos(strtolower($l['name'] . ' ' . $l['area'] . ' ' . $l['address']), $q) === false) return false;
              return true;
          });
      ?>
        <?= renderPageHeader('Find parking', 'Browse verified parking businesses and compare current availability.') ?>
        <form class="filters" method="get" action="index.php" style="margin-bottom:16px">
          <input type="hidden" name="view" value="workspace">
          <input type="hidden" name="page" value="Find Parking">
          <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search parking business or area">
          <select name="area">
            <option value="">All areas</option>
            <?php foreach (['Iligan City', 'Downtown', 'Tibanga'] as $ar): ?>
              <option value="<?= e($ar) ?>" <?= $area === $ar ? 'selected' : '' ?>><?= e($ar) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary btn-sm" type="submit">Filter</button>
        </form>
        <div class="lot-grid">
          <?php foreach ($list as $lot): ?>
            <?= renderLotCard($lot, 'index.php?view=workspace&page=' . urlencode('Find Parking'), true) ?>
          <?php endforeach; ?>
        </div>

      <?php elseif ($page === 'My Vehicles'): ?>
        <?= renderPageHeader('My vehicles', 'Manage the vehicles you use when visiting parking businesses.', '<a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=' . urlencode('My Vehicles') . '&modal=add_vehicle">＋ Add vehicle</a>') ?>
        <div class="lot-admin-grid">
          <?php foreach ($_SESSION['pw_vehicles'] as $v): ?>
            <div class="space-card">
              <div class="space-card-top">
                <span class="plate-badge"><?= e($v['plate']) ?></span>
                <?= !empty($v['default']) ? statusPill('Default') : statusPill('Active', 'neutral') ?>
              </div>
              <h4 style="margin-top:12px"><?= e($v['model']) ?></h4>
              <p><?= e($v['type']) ?> · <?= e($v['color']) ?> · Added <?= e($v['added']) ?></p>
              <?php if (empty($v['default'])): ?>
                <form method="post" action="index.php">
                  <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                  <input type="hidden" name="action" value="set_default_vehicle">
                  <input type="hidden" name="vehicle_id" value="<?= (int)$v['id'] ?>">
                  <button type="submit" class="table-action">Set as default vehicle</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif ($page === 'Favorites'):
          $favIds = $_SESSION['pw_favorites'] ?? [];
          $favLots = array_filter($lots, static fn(array $l): bool => in_array((int)$l['id'], $favIds, true));
      ?>
        <?= renderPageHeader('Favorite parking lots', 'Your saved parking businesses.') ?>
        <div class="lot-grid">
          <?php if (empty($favLots)): ?>
            <div class="empty-state" style="grid-column:1/-1">No favorite parking lots yet. Save any lot from Find Parking.</div>
          <?php else: ?>
            <?php foreach ($favLots as $lot): ?>
              <?= renderLotCard($lot, 'index.php?view=workspace&page=Favorites', true) ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      <?php elseif ($page === 'Parking History' || $page === 'Session Log' || $page === 'Transactions'):
          $list = $role === 'customer' ? mySessions() : $_SESSION['pw_sessions'];
          $rows = '';
          foreach (array_reverse($list) as $s) {
              $billHtml = $s['total'] ? '₱' . number_format((float)$s['total'], 2) . ' ' : '—';
              if (!empty($s['paid'])) {
                  $billHtml .= '<a class="table-action" href="index.php?view=workspace&page=' . urlencode($page) . '&receipt=' . (int)$s['id'] . '">Bill</a>';
              }
              $rows .= '<tr>'
                  . '<td><strong>' . e($s['business']) . '</strong></td>'
                  . '<td><span class="plate-badge">' . e($s['plate']) . '</span></td>'
                  . '<td>' . e($s['timeIn']) . '</td>'
                  . '<td>' . e($s['timeOut'] ?: '—') . '</td>'
                  . '<td>' . $billHtml . '</td>'
                  . '<td>' . statusPill($s['status'], $s['status'] === 'Active' ? 'pending' : '') . '</td>'
                  . '</tr>';
          }
      ?>
        <?= renderPageHeader($page, 'Review parking sessions and payment bills.') ?>
        <?= renderTablePanel('Parking session records', ['BUSINESS', 'PLATE NUMBER', 'TIME IN', 'TIME OUT', 'BILL', 'STATUS'], $rows) ?>

      <?php elseif ($page === 'Parking Spaces'): ?>
        <?= renderPageHeader('Parking spaces', 'Monitor and manage spaces across your parking business.', '<a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=' . urlencode('Parking Spaces') . '&modal=add_space">＋ Add space</a>') ?>
        <div class="lot-admin-grid">
          <?php foreach ($_SESSION['pw_spaces'] as $sp): ?>
            <?= renderSpaceCard($sp, true) ?>
          <?php endforeach; ?>
        </div>

      <?php elseif ($page === 'Owner Applications'):
          $rows = '';
          foreach ($_SESSION['pw_apps'] as $a) {
              $verifiedCount = count(array_filter($a['docs'], static fn(array $d): bool => $d['st'] === 'verified'));
              $stType = $a['status'] === 'Pending' ? 'pending' : ($a['status'] === 'Rejected' ? 'danger' : '');
              $rows .= '<tr>'
                  . '<td><strong>' . e($a['who']) . '</strong></td>'
                  . '<td>' . e($a['biz'] ?: '—') . '</td>'
                  . '<td>' . $verifiedCount . ' of 3 verified</td>'
                  . '<td>' . e($a['date']) . '</td>'
                  . '<td>' . statusPill($a['status'], $stType) . '</td>'
                  . '<td><a class="table-action" href="index.php?view=workspace&page=' . urlencode('Owner Applications') . '&app=' . (int)$a['id'] . '">View documents</a></td>'
                  . '</tr>';
          }
      ?>
        <?= renderPageHeader('Owner applications', 'Open an application to see every document the applicant submitted.') ?>
        <?= renderTablePanel('Applications', ['APPLICANT', 'BUSINESS', 'DOCUMENTS', 'DATE', 'STATUS', 'ACTION'], $rows) ?>

      <?php elseif ($page === 'My Businesses' || $page === 'Business Directory'):
          if ($role === 'superadmin'):
              $rows = '';
              foreach ($lots as $l) {
                  [$lbl] = vacancyInfo($l);
                  $isBad = !empty($l['closed']) || (int)$l['available'] === 0;
                  $rows .= '<tr>'
                      . '<td><strong>' . e($l['name']) . '</strong></td>'
                      . '<td>' . e($l['owner']) . '</td>'
                      . '<td>' . (int)$l['total'] . '</td>'
                      . '<td>' . statusPill(!empty($l['closed']) ? 'Closed' : $l['available'] . ' free · ' . $lbl, $isBad ? 'danger' : '') . '</td>'
                      . '<td>★ ' . number_format((float)$l['rating'], 1) . '</td>'
                      . '<td><a class="table-action" href="index.php?view=workspace&page=' . urlencode('Business Directory') . '&warn=' . urlencode($l['owner']) . '&warn_lot=' . (int)$l['id'] . '">Manage</a> '
                      . (!empty($_SESSION['pw_status'][$l['owner']]) ? acctStatusBadge($l['owner']) : '')
                      . '</td></tr>';
              }
              echo renderPageHeader('Business directory', 'Monitor every parking business. Warn or revoke an owner with a reason.');
              echo renderTablePanel('Parking businesses', ['BUSINESS', 'OWNER', 'SPACES', 'VACANCY', 'RATING', 'ACTION'], $rows);
          else:
              echo renderPageHeader($page, 'View verified parking businesses and their details.');
              echo '<div class="lot-grid">';
              foreach ($lots as $lot) {
                  echo renderLotCard($lot, 'index.php?view=workspace&page=' . urlencode($page), false);
              }
              echo '</div>';
          endif;
      ?>

      <?php elseif ($page === 'Guards' || $page === 'Guard Accounts'):
          $rows = '';
          foreach ($_SESSION['pw_guards'] as $g) {
              $rows .= '<tr>'
                  . '<td><strong>' . e($g['name']) . '</strong></td>'
                  . '<td>' . e($g['biz']) . '</td>'
                  . '<td>' . e($g['ent']) . '</td>'
                  . '<td>' . e($g['shift']) . '</td>'
                  . '<td>' . acctStatusBadge($g['name']) . '</td>'
                  . '<td><a class="table-action" href="index.php?view=workspace&page=' . urlencode($page) . '&warn=' . urlencode($g['name']) . '">Manage</a></td>'
                  . '</tr>';
          }
      ?>
        <?= renderPageHeader('Guard management', 'Guards can only record time-in and time-out at their assigned lot.', '<a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=' . urlencode($page) . '&modal=add_guard">＋ Add guard</a>') ?>
        <?= renderTablePanel('Guard accounts', ['GUARD', 'ASSIGNED BUSINESS', 'ENTRANCE', 'SHIFT', 'STATUS', 'ACTION'], $rows) ?>

      <?php elseif ($page === 'Customer Accounts'):
          $rows = '';
          foreach ($_SESSION['pw_customers'] as $c) {
              $isProm = in_array($c['name'], $_SESSION['pw_promoted'], true) || $c['name'] === 'Alex Morgan';
              $rows .= '<tr>'
                  . '<td><strong>' . e($c['name']) . '</strong></td>'
                  . '<td>' . e($c['email']) . '</td>'
                  . '<td>Customer' . ($isProm ? ' + Owner' : '') . '</td>'
                  . '<td>' . acctStatusBadge($c['name']) . '</td>'
                  . '<td><a class="table-action" href="index.php?view=workspace&page=' . urlencode('Customer Accounts') . '&warn=' . urlencode($c['name']) . '">Manage</a></td>'
                  . '</tr>';
          }
      ?>
        <?= renderPageHeader('Customer accounts', 'Monitor registered customers and account status.') ?>
        <?= renderTablePanel('Customer directory', ['CUSTOMER', 'EMAIL', 'ROLES', 'STATUS', 'ACTION'], $rows) ?>

      <?php elseif ($page === 'My Reviews' || $page === 'Reports & Reviews'):
          $btn = $role === 'customer' ? '<a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=' . urlencode($page) . '&modal=review">＋ Write a review</a>' : '';
      ?>
        <?= renderPageHeader($page, 'Customer reviews from completed and paid parking visits.', $btn) ?>
        <div class="lot-admin-grid">
          <?php foreach ($_SESSION['pw_reviews'] as $rev): ?>
            <div class="space-card">
              <div class="space-card-top">
                <h4><?= e($rev['business']) ?></h4>
                <span class="chip">★ <?= (int)$rev['rating'] ?>/5</span>
              </div>
              <p style="color:var(--ink);font-size:12px;line-height:1.6;margin:10px 0">"<?= e($rev['text']) ?>"</p>
              <div style="display:flex;justify-content:space-between;align-items:center">
                <small style="color:var(--muted);font-size:10px">By <?= e($rev['by'] ?? 'Customer') ?> · <?= e($rev['date']) ?></small>
                <?= statusPill('Published') ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php elseif ($page === 'Platform Reports' || $page === 'Activity Logs'):
          $logRows = '';
          foreach (array_reverse($_SESSION['pw_logs']) as $l) {
              $logRows .= '<tr>'
                  . '<td><strong>' . e($l['actor']) . '</strong></td>'
                  . '<td>' . e($l['action']) . '</td>'
                  . '<td>' . e($l['target']) . '</td>'
                  . '<td>' . e($l['note'] ?: '—') . '</td>'
                  . '<td>' . e($l['when']) . '</td>'
                  . '</tr>';
          }
      ?>
        <?= renderPageHeader($page, 'Administrative actions and every time-in and time-out. Entries cannot be edited.') ?>
        <?= renderTablePanel('Administrative & terminal audit logs', ['ACTOR', 'ACTION', 'TARGET', 'NOTE / REASON', 'WHEN'], $logRows) ?>

      <?php elseif ($page === 'My Profile'): ?>
        <?= renderPageHeader('My profile', 'Manage your personal account information.') ?>
        <section class="panel" style="max-width:720px">
          <div class="profile-row">
            <div class="avatar"><?= e(initials($user['name'])) ?></div>
            <div>
              <b><?= e($user['name']) ?></b>
              <small><?= e($user['email']) ?> · Roles: <?= e(implode(', ', $user['roles'] ?? ['customer'])) ?></small>
            </div>
          </div>
          <form method="post" action="index.php">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="save_profile">
            <div class="form-grid">
              <div class="form-field"><label>FULL NAME</label><input name="full_name" required value="<?= e($user['name']) ?>"></div>
              <div class="form-field"><label>EMAIL ADDRESS</label><input name="email" type="email" required value="<?= e($user['email']) ?>"></div>
              <div class="form-field"><label>CONTACT NUMBER</label><input name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+63"></div>
              <div class="form-field"><label>ACTIVE WORKSPACE ROLE</label><input value="<?= e($roleLabels[$role] ?? $role) ?>" disabled></div>
            </div>
            <button class="btn btn-primary btn-sm" style="margin-top:18px" type="submit">Save changes</button>
          </form>
        </section>

      <?php elseif ($page === 'Settings'): ?>
        <?= renderPageHeader('Settings', 'Manage your ParkWise preferences and storage connection.') ?>
        <div class="lot-admin-grid" style="grid-template-columns:repeat(2,1fr)">
          <section class="panel">
            <h3>Data Storage Status</h3>
            <p style="font-size:12px;color:var(--muted);line-height:1.6">
              <?= usingDatabase()
                  ? 'Connected to MySQL database (`parkwise`) via PDO.'
                  : 'Running in Session Mode (no MySQL database detected yet). Import <code>database.sql</code> and update <code>config/database.php</code> whenever you are ready.' ?>
            </p>
            <?= statusPill(usingDatabase() ? 'MySQL Connected' : 'PHP Session Active', usingDatabase() ? '' : 'pending') ?>
          </section>
          <section class="panel">
            <h3>Notification & Receipt Preferences</h3>
            <p style="font-size:12px;color:var(--muted);line-height:1.6">Session alerts, printable bills, and audit trail logging are enabled for your workspace.</p>
            <a class="btn btn-primary btn-sm" href="index.php?view=workspace&page=Settings&modal=notifications">View notifications</a>
          </section>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>