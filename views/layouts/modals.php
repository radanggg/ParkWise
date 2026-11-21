<?php
declare(strict_types=1);

$modal = (string)($_GET['modal'] ?? '');
$lotModalId = (int)($_GET['lot'] ?? 0);
$appModalId = (int)($_GET['app'] ?? 0);
$docModalIdx = isset($_GET['doc']) ? (int)$_GET['doc'] : null;
$warnWho = (string)($_GET['warn'] ?? '');
$warnLot = (int)($_GET['warn_lot'] ?? 0);
$receiptId = (int)($_GET['receipt'] ?? 0);

$view = (string)($_GET['view'] ?? 'landing');
$page = (string)($_GET['page'] ?? 'Dashboard');
$closeUrl = $view === 'workspace'
    ? 'index.php?view=workspace&page=' . urlencode($page)
    : ($view === 'recruit' ? 'index.php?view=recruit' : 'index.php');

$csrf = csrfToken();
$user = currentUser();
$role = currentRole();

$isOpen = $modal !== '' || $lotModalId > 0 || $appModalId > 0 || $warnWho !== '' || $receiptId > 0;
if (!$isOpen) {
    return;
}
?>
<div class="modal-backdrop" id="modalBackdrop">
  <section class="modal <?= $modal === 'owner_apply' ? 'wide' : '' ?>" role="dialog" aria-modal="true">
    <a class="modal-close" href="<?= e($closeUrl) ?>">×</a>

    <?php if ($modal === 'login' || $modal === 'register'): $isReg = $modal === 'register'; ?>
      <h2><?= $isReg ? 'Create your ParkWise account' : 'Welcome back' ?></h2>
      <p><?= $isReg ? 'Create an account to manage your vehicles, parking activity and reviews.' : 'Log in to access your ParkWise workspace.' ?></p>
      <form class="modal-form" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="<?= $isReg ? 'register' : 'login' ?>">
        <?php if ($isReg): ?>
          <div class="form-field"><label>FULL NAME</label><input name="full_name" required placeholder="Your name"></div>
        <?php endif; ?>
        <div class="form-field"><label>EMAIL ADDRESS</label><input name="email" type="email" required placeholder="jordan@email.com"></div>
        <div class="form-field"><label>PASSWORD</label><input name="password" type="password" required placeholder="Enter your password"></div>
        <button class="btn btn-primary" type="submit"><?= $isReg ? 'Create customer account' : 'Log in' ?> →</button>
      </form>
      <div class="modal-note">
        <?= $isReg ? 'Already have an account?' : 'New to ParkWise?' ?>
        <a style="color:var(--blue);font-weight:800" href="index.php?modal=<?= $isReg ? 'login' : 'register' ?>"><?= $isReg ? 'Log in' : 'Create an account' ?></a>
      </div>
      <div class="modal-note" style="margin-top:18px">DEMO PREVIEW · Choose a workspace</div>
      <div class="form-grid" style="margin-top:10px">
        <?php foreach (['customer' => 'Customer demo', 'owner' => 'Owner demo', 'guard' => 'Guard demo', 'superadmin' => 'Super Admin demo', 'guest' => 'Browse as guest'] as $rKey => $rLbl): ?>
          <form method="post" action="index.php" style="<?= $rKey === 'guest' ? 'grid-column:1/-1' : '' ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="demo">
            <input type="hidden" name="role" value="<?= e($rKey) ?>">
            <button type="submit" class="btn btn-ghost" style="width:100%;color:var(--blue);border:1px solid var(--line)"><?= e($rLbl) ?></button>
          </form>
        <?php endforeach; ?>
      </div>

    <?php elseif ($lotModalId > 0): $lot = findLot($lotModalId); if ($lot):
        [$lbl] = vacancyInfo($lot);
        $lotReviews = array_filter($_SESSION['pw_reviews'], static fn(array $r): bool => $r['business'] === $lot['name']);
        $canRev = canReviewLot($lot);
    ?>
      <h2><?= e($lot['name']) ?></h2>
      <div class="lot-photo" style="height:170px;border-radius:12px;background-image:url('<?= e($lot['image']) ?>')"></div>
      <div class="profile-row" style="margin:14px 0">
        <div>
          <b>Owner: <?= e($lot['owner']) ?></b>
          <small><?= e($lot['address']) ?> · <?= e($lot['hours'] ?? 'Open 24/7') ?></small>
        </div>
      </div>
      <div class="stats-grid" style="grid-template-columns:repeat(2,1fr)">
        <?= renderStatCard('HOURLY RATE', '₱' . (int)$lot['rate'], 'Per hour', '₱') ?>
        <?= renderStatCard('AVAILABLE', !empty($lot['closed']) ? '–' : (string)$lot['available'], $lbl . ' · of ' . $lot['total'] . ' spaces', '▦') ?>
      </div>
      <p>★ <?= number_format((float)$lot['rating'], 1) ?> rating · <?= (int)$lot['reviews'] + count($lotReviews) ?> reviews · <?= e($lot['distance']) ?> away</p>
      <h4 style="margin:12px 0 6px">Verified Reviews</h4>
      <?php if (empty($lotReviews)): ?>
        <p>No reviews yet.</p>
      <?php else: ?>
        <div class="activity-list">
          <?php foreach ($lotReviews as $rv): ?>
            <div class="activity-item">
              <span class="activity-dot">★</span>
              <div><b><?= (int)$rv['rating'] ?>/5 · <?= e($rv['text']) ?></b><small><?= e($rv['by'] ?? 'Customer') ?> · <?= e($rv['date']) ?></small></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($canRev): ?>
        <a class="btn btn-primary" style="width:100%;margin:12px 0 8px" href="index.php?view=workspace&page=<?= urlencode('My Reviews') ?>&modal=review">Write a review</a>
      <?php endif; ?>
      <a class="btn btn-ghost" style="width:100%;color:var(--blue);border:1px solid var(--line);margin-top:8px" href="<?= e($closeUrl) ?>">Close</a>
    <?php endif; ?>

    <?php elseif ($modal === 'add_vehicle'): ?>
      <h2>Add vehicle</h2>
      <form class="modal-form" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="add_vehicle">
        <div class="form-field"><label>VEHICLE MAKE & MODEL</label><input name="make_model" required placeholder="e.g. Toyota Vios"></div>
        <div class="form-field"><label>PLATE NUMBER</label><input name="plate_number" required placeholder="ABC 1234"></div>
        <div class="form-grid">
          <div class="form-field"><label>VEHICLE TYPE</label><select name="vehicle_type"><option>Sedan</option><option>SUV</option><option>Motorcycle</option><option>Van</option></select></div>
          <div class="form-field"><label>COLOR</label><input name="color" placeholder="Silver"></div>
        </div>
        <button class="btn btn-primary" type="submit">Save vehicle</button>
      </form>

    <?php elseif ($modal === 'add_space'): ?>
      <h2>Add parking space</h2>
      <form class="modal-form" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="add_space">
        <div class="form-field"><label>SPACE CODE</label><input name="space_code" required placeholder="D-01"></div>
        <div class="form-grid">
          <div class="form-field"><label>AREA / WING</label><select name="zone"><option>Main entrance</option><option>East wing</option><option>West wing</option></select></div>
          <div class="form-field"><label>SLOT TYPE</label><select name="slot_type"><option>Standard</option><option>Covered</option><option>Motorcycle</option></select></div>
        </div>
        <button class="btn btn-primary" type="submit">Save parking space</button>
      </form>

    <?php elseif ($modal === 'add_guard'): ?>
      <h2>Add guard account</h2>
      <form class="modal-form" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="add_guard">
        <div class="form-field"><label>GUARD FULL NAME</label><input name="guard_name" required placeholder="Full name"></div>
        <div class="form-grid">
          <div class="form-field"><label>ENTRANCE</label><input name="entrance" value="Main entrance"></div>
          <div class="form-field"><label>SHIFT</label><input name="shift" value="8 AM - 4 PM"></div>
        </div>
        <button class="btn btn-primary" type="submit">Add guard</button>
      </form>

    <?php elseif ($modal === 'review'):
        $reviewable = array_values(array_filter(getLots(), static fn(array $l): bool => canReviewLot($l)));
    ?>
      <h2>Write a parking review</h2>
      <?php if (empty($reviewable)): ?>
        <p>You can review a lot after your visit ends, you have paid, and you received the printed bill. There is nothing to review yet.</p>
      <?php else: ?>
        <p>Reviews are available only after a completed and paid parking session.</p>
        <form class="modal-form" method="post" action="index.php">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="action" value="submit_review">
          <div class="form-field"><label>PARKING BUSINESS</label>
            <select name="business">
              <?php foreach ($reviewable as $rl): ?><option><?= e($rl['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-field"><label>RATING</label><select name="rating"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select></div>
          <div class="form-field"><label>YOUR REVIEW</label><textarea name="review_text" rows="3" required placeholder="Share your experience"></textarea></div>
          <button class="btn btn-primary" type="submit">Submit review</button>
        </form>
      <?php endif; ?>

    <?php elseif ($modal === 'owner_apply'): ?>
      <h2>Join as Owner</h2>
      <p>Tell us who you are and attach the documents that prove you own the parking lot. The Super Admin verifies them before any owner access is opened.</p>
      <form class="modal-form" method="post" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="submit_owner_app">
        <div class="form-grid">
          <div class="form-field"><label>FULL NAME</label><input name="full_name" required value="<?= e($user['name'] ?? '') ?>"></div>
          <div class="form-field"><label>EMAIL ADDRESS</label><input name="email" type="email" required value="<?= e($user['email'] ?? '') ?>"></div>
          <div class="form-field"><label>CONTACT NUMBER</label><input name="phone" required placeholder="+63 912 345 6789"></div>
          <div class="form-field"><label>PARKING BUSINESS NAME</label><input name="business_name" required placeholder="Your parking lot name"></div>
          <div class="form-field full"><label>CONFIRM PASSWORD</label><input name="password" type="password" required placeholder="Minimum 6 characters"></div>
        </div>
        <?php foreach (['1. Land Title', '2. Business Permit', '3. Valid ID'] as $i => $lbl): ?>
          <div class="doc-slot">
            <div class="doc-slot-head"><b><?= e($lbl) ?></b><small>JPG, PNG or PDF (optional in demo, required for live upload).</small></div>
            <input type="file" name="doc_<?= $i ?>" accept="image/*,.pdf">
          </div>
        <?php endforeach; ?>
        <div class="page-actions" style="margin-top:14px">
          <button class="btn btn-primary" type="submit">Submit Application</button>
          <a class="btn btn-ghost" style="color:var(--blue);border:1px solid var(--line)" href="index.php?view=recruit">Cancel</a>
        </div>
      </form>

    <?php elseif ($appModalId > 0):
        $app = null;
        foreach ($_SESSION['pw_apps'] as $a) {
            if ((int)$a['id'] === $appModalId) { $app = $a; break; }
        }
        if ($app && $docModalIdx !== null && isset($app['docs'][$docModalIdx])):
            $doc = $app['docs'][$docModalIdx];
    ?>
      <h2><?= e($doc['type']) ?></h2>
      <div class="doc-tile">
        <b><?= e($doc['type']) ?></b>
        <small>Applicant: <?= e($app['who']) ?> · Name on document: <?= e($doc['name'] ?: $app['who']) ?></small>
        <?php if (!empty($doc['file']['path'])): ?>
          <a class="table-action" target="_blank" href="<?= e($doc['file']['path']) ?>">Open uploaded file ↗</a>
        <?php endif; ?>
      </div>
      <form class="modal-form" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="doc_decision">
        <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
        <input type="hidden" name="doc_idx" value="<?= $docModalIdx ?>">
        <?php if ($docModalIdx === 0): ?>
          <label style="display:flex;gap:8px;font-size:12px;align-items:center">
            <input type="checkbox" name="title_match" value="1" <?= !empty($app['titleMatch']) ? 'checked' : '' ?> style="width:auto">
            The name on this land title matches <?= e($app['who']) ?>
          </label>
        <?php endif; ?>
        <div class="form-field"><label>REASON (REQUIRED IF REJECTING)</label><input name="doc_reason" placeholder="Reason for rejection"></div>
        <div class="page-actions">
          <button class="btn btn-primary" type="submit" name="decision" value="verify">Mark verified</button>
          <button class="btn" type="submit" name="decision" value="reject" style="background:#fff0ef;color:#c63e37">Reject document</button>
          <a class="btn btn-ghost" style="color:var(--blue);border:1px solid var(--line)" href="index.php?view=workspace&page=<?= urlencode('Owner Applications') ?>&app=<?= (int)$app['id'] ?>">Back</a>
        </div>
      </form>

    <?php elseif ($app):
        $unverified = count(array_filter($app['docs'], static fn(array $d): bool => $d['st'] !== 'verified'));
        $isDone = $app['status'] !== 'Pending';
        $canApprove = !$isDone && $unverified === 0 && !empty($app['titleMatch']);
    ?>
      <h2>Review owner application</h2>
      <div class="profile-row">
        <div class="avatar"><?= e(initials($app['who'])) ?></div>
        <div><b><?= e($app['who']) ?></b><small><?= e($app['biz'] ?: $app['email']) ?> · Submitted <?= e($app['date']) ?></small></div>
      </div>
      <div class="activity-list">
        <?php foreach ($app['docs'] as $idx => $d):
            $stLbl = $d['st'] === 'verified' ? 'Verified' : ($d['st'] === 'rejected' ? 'Rejected' : 'Pending check');
            $stCls = $d['st'] === 'verified' ? '' : ($d['st'] === 'rejected' ? 'danger' : 'pending');
        ?>
          <div class="activity-item">
            <div style="flex:1"><b><?= e($d['type']) ?></b><small>Name: <?= e($d['name'] ?: $app['who']) ?></small></div>
            <?= statusPill($stLbl, $stCls) ?>
            <a class="table-action" href="index.php?view=workspace&page=<?= urlencode('Owner Applications') ?>&app=<?= (int)$app['id'] ?>&doc=<?= $idx ?>">View</a>
          </div>
        <?php endforeach; ?>
      </div>
      <p style="font-size:11px;margin:10px 0">
        <?= $isDone ? 'Application status: ' . e($app['status']) : ($unverified > 0 ? $unverified . ' of 3 documents still need verification.' : (empty($app['titleMatch']) ? 'Land title name does not match applicant.' : 'All documents verified. Ready to approve.')) ?>
      </p>
      <form class="modal-form" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="app_decision">
        <input type="hidden" name="app_id" value="<?= (int)$app['id'] ?>">
        <div class="form-field"><label>REASON (REQUIRED TO REJECT)</label><textarea name="decision_reason" rows="2"></textarea></div>
        <div class="page-actions">
          <button class="btn btn-primary" type="submit" name="decision" value="approve" <?= $canApprove ? '' : 'disabled style="opacity:.4"' ?>>Approve and promote to owner</button>
          <button class="btn" type="submit" name="decision" value="reject" style="background:#fff0ef;color:#c63e37" <?= $isDone ? 'disabled' : '' ?>>Reject application</button>
        </div>
      </form>
    <?php endif; ?>

    <?php elseif ($warnWho !== ''): ?>
      <h2>Account action · <?= e($warnWho) ?></h2>
      <p>Record a warning or restriction. A reason is required and is logged.</p>
      <form class="modal-form" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="account_warn">
        <input type="hidden" name="who" value="<?= e($warnWho) ?>">
        <input type="hidden" name="lot_id" value="<?= $warnLot ?>">
        <input type="hidden" name="return_page" value="<?= e($page) ?>">
        <div class="form-field"><label>ACTION</label><select name="warn_act"><option>Issue warning</option><option>Temporarily suspend</option><option>Revoke access</option></select></div>
        <div class="form-field"><label>REASON</label><textarea name="warn_reason" rows="3" required placeholder="Explain the reason"></textarea></div>
        <button class="btn btn-primary" type="submit">Record action</button>
      </form>

    <?php elseif ($receiptId > 0):
        $rec = null;
        foreach ($_SESSION['pw_sessions'] as $s) {
            if ((int)$s['id'] === $receiptId) { $rec = $s; break; }
        }
        if ($rec):
    ?>
      <h2>ParkWise receipt</h2>
      <small><?= e($rec['no'] ?: 'PW-000001') ?></small>
      <div class="activity-list" style="margin-top:12px">
        <div class="activity-item"><div style="flex:1"><small>Business</small></div><b><?= e($rec['business']) ?></b></div>
        <div class="activity-item"><div style="flex:1"><small>Plate number</small></div><b><?= e($rec['plate']) ?></b></div>
        <div class="activity-item"><div style="flex:1"><small>Time-in</small></div><b><?= e($rec['date'] . ' ' . $rec['timeIn']) ?></b></div>
        <div class="activity-item"><div style="flex:1"><small>Time-out</small></div><b><?= e($rec['timeOut']) ?></b></div>
        <div class="activity-item"><div style="flex:1"><small>Rate</small></div><b><?= (int)($rec['hours'] ?? 1) ?> hr × ₱<?= number_format((float)$rec['rate'], 0) ?></b></div>
        <div class="activity-item"><div style="flex:1"><small>Total paid</small></div><b>₱<?= number_format((float)$rec['total'], 2) ?></b></div>
      </div>
      <div class="page-actions" style="margin-top:16px">
        <button class="btn btn-primary" type="button" onclick="window.print()">Print bill</button>
        <a class="btn btn-ghost" style="color:var(--blue);border:1px solid var(--line)" href="<?= e($closeUrl) ?>">Close</a>
      </div>
    <?php endif; ?>

    <?php elseif ($modal === 'notifications'): ?>
      <h2>Notifications</h2>
      <div class="activity-list">
        <div class="activity-item"><span class="activity-dot">♧</span><div><b>Welcome to ParkWise</b><small>Your workspace is ready.</small></div></div>
        <div class="activity-item"><span class="activity-dot">✓</span><div><b>Live occupancy active</b><small>Real-time slot tracking enabled.</small></div></div>
      </div>
    <?php endif; ?>
  </section>
</div>