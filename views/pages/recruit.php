<?php
declare(strict_types=1);

$user = currentUser();
$isOwner = $user && in_array('owner', $user['roles'] ?? [], true);
$mine = null;
foreach (array_reverse($_SESSION['pw_apps']) as $app) {
    if (($app['acct'] ?? '') === ($_SESSION['pw_acct'] ?? '')) {
        $mine = $app;
        break;
    }
}
?>
<section id="recruit" class="recruit" aria-labelledby="recruitTitle">
  <a class="recruit-back" href="index.php?view=workspace&page=Dashboard">← Back to Customer</a>
  <div class="recruit-grid">
    <div class="recruit-copy">
      <div class="eyebrow">FOR PARKING OWNERS</div>
      <h1 id="recruitTitle">Your parking space deserves more drivers.</h1>
      <p>Join ParkWise as a verified owner. Show live availability and your hourly rate, assign guards to record every entry and exit, and keep bills and activity in one place.</p>
      <ul class="recruit-points">
        <li>Drivers see your free spaces before they arrive</li>
        <li>Guards record time-in and time-out with just a plate number</li>
        <li>Bills, receipts and activity logs, ready when you need them</li>
      </ul>
      <div class="recruit-cta">
        <?php if ($isOwner): ?>
          <button class="btn btn-primary" disabled>You are already an owner</button>
          <small>Switch to your Owner account from the account menu.</small>
        <?php elseif ($mine && $mine['status'] === 'Pending'): ?>
          <button class="btn btn-primary" disabled>Application pending verification</button>
          <small>The Super Admin is verifying your documents. You can keep using your customer account.</small>
        <?php else: ?>
          <a class="btn btn-primary" href="index.php?view=recruit&modal=owner_apply">
            <?= ($mine && $mine['status'] === 'Rejected') ? 'Apply again →' : 'Join as Owner →' ?>
          </a>
          <small>
            <?php if ($mine && $mine['status'] === 'Rejected'): ?>
              Your last application was rejected: <?= e($mine['reason'] ?: 'no reason recorded') ?>.
            <?php else: ?>
              Applying does not change your customer account. You keep it while the Super Admin verifies your documents.
            <?php endif; ?>
          </small>
        <?php endif; ?>
      </div>
    </div>
    <div class="recruit-photos">
      <div class="rp rp-a" role="img" aria-label="A parking lot with cars in marked spaces"></div>
      <div class="rp rp-b" role="img" aria-label="A parking garage entrance"></div>
      <div class="rp rp-c" role="img" aria-label="Cars parked in a lot at dusk"></div>
      <span class="rp-chip">Live vacancy</span>
    </div>
  </div>
</section>
