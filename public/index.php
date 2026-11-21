<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../src/helpers/store.php';
require_once __DIR__ . '/../src/helpers/actions.php';
require_once __DIR__ . '/../src/helpers/components.php';

initStore();
handlePostActions();

$view = (string)($_GET['view'] ?? 'landing');
if ($view === 'workspace' && !currentUser()) {
    header('Location: index.php?modal=login');
    exit;
}

$toast = popFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#0A4988" />
  <title>ParkWise — Find your space</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/styles.css" />
</head>
<body>
  <div id="app">
    <?php
    if ($view === 'workspace') {
        require __DIR__ . '/../views/pages/workspace.php';
    } elseif ($view === 'recruit') {
        require __DIR__ . '/../views/pages/recruit.php';
    } else {
        require __DIR__ . '/../views/pages/landing.php';
    }
    ?>
  </div>

  <?php require __DIR__ . '/../views/layouts/modals.php'; ?>

  <div class="toast <?= $toast ? 'show' : '' ?>" id="toast"><?= $toast ? e($toast['msg']) : '' ?></div>
  <script src="assets/js/app.js"></script>
</body>
</html>