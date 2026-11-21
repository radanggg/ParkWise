<?php
declare(strict_types=1);

$lots = getLots();
$searchQ = strtolower(trim((string)($_GET['q'] ?? '')));
$areaFilter = trim((string)($_GET['area'] ?? ''));
$sortFilter = trim((string)($_GET['sort'] ?? 'recommended'));

$filteredLots = array_values(array_filter($lots, static function (array $l) use ($searchQ, $areaFilter): bool {
    if ($areaFilter !== '' && $l['area'] !== $areaFilter) {
        return false;
    }
    if ($searchQ !== '') {
        $haystack = strtolower($l['name'] . ' ' . $l['area'] . ' ' . $l['owner'] . ' ' . $l['address']);
        if (strpos($haystack, $searchQ) === false) {
            return false;
        }
    }
    return true;
}));

if ($sortFilter === 'price') {
    usort($filteredLots, static fn(array $a, array $b): int => $a['rate'] <=> $b['rate']);
} elseif ($sortFilter === 'rating') {
    usort($filteredLots, static fn(array $a, array $b): int => $b['rating'] <=> $a['rating']);
}
?>
<header class="public-header">
  <a class="brand" href="index.php"><span class="brand-mark">P</span><span>park<span class="brand-blue">wise</span></span></a>
  <nav class="public-nav">
    <a href="#find-parking">Find parking</a>
    <a href="#how-it-works">How it works</a>
    <a href="#featured">Featured lots</a>
  </nav>
  <div class="header-actions">
    <?php if (currentUser()): ?>
      <a class="btn btn-white" href="index.php?view=workspace&page=Dashboard">Open Workspace →</a>
    <?php else: ?>
      <a class="btn btn-ghost" href="index.php?modal=login">Log in</a>
      <a class="btn btn-primary" href="index.php?modal=register">Create account <span>↗</span></a>
    <?php endif; ?>
  </div>
  <button class="mobile-menu" type="button" data-action="mobile-menu" aria-label="Open menu">☰</button>
</header>

<main id="landing">
  <section class="hero">
    <div class="hero-slides" aria-hidden="true">
      <div class="hero-slide slide-one active"></div>
      <div class="hero-slide slide-two"></div>
      <div class="hero-slide slide-three"></div>
    </div>
    <div class="hero-shade"></div>
    <div class="hero-content">
      <div class="eyebrow eyebrow-light"><span class="live-dot"></span> SMARTER PARKING STARTS HERE</div>
      <h1>YOUR SPACE.<br><span>YOUR WAY.</span></h1>
      <p>Find trusted parking lots, compare rates, and get on with your day. Simple parking, made wiser.</p>
      <div class="hero-cta">
        <a class="btn btn-white" href="#find-parking">Explore parking <span>→</span></a>
        <a class="btn btn-outline-light" href="index.php?modal=register">Join ParkWise</a>
      </div>
      <div class="hero-proof">
        <span><b>01</b> Discover nearby lots</span>
        <span><b>02</b> Check live availability</span>
        <span><b>03</b> Park and pay</span>
      </div>
    </div>
    <div class="hero-bottom-curve"></div>
    <div class="hero-pagination"><i class="active"></i><i></i><i></i></div>
  </section>

  <section class="search-section page-wrap" id="find-parking">
    <div class="section-heading">
      <div>
        <div class="eyebrow">FIND YOUR PARKING</div>
        <h2>A better spot is closer<br class="desktop-only"> than you think.</h2>
      </div>
      <p>Explore verified parking businesses and see their rates, ratings, and current space availability.</p>
    </div>
    <form class="search-panel" method="get" action="index.php#featured">
      <label class="search-field">
        <span class="field-icon">⌕</span>
        <span>
          <small>LOCATION OR PARKING LOT</small>
          <input name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search by area or business name">
        </span>
      </label>
      <label class="search-field">
        <span class="field-icon">⌖</span>
        <span>
          <small>AREA</small>
          <select name="area">
            <option value="">All areas</option>
            <?php foreach (['Iligan City', 'Downtown', 'Tibanga'] as $ar): ?>
              <option value="<?= e($ar) ?>" <?= $areaFilter === $ar ? 'selected' : '' ?>><?= e($ar) ?></option>
            <?php endforeach; ?>
          </select>
        </span>
      </label>
      <label class="search-field">
        <span class="field-icon">↕</span>
        <span>
          <small>SORT BY</small>
          <select name="sort">
            <option value="recommended" <?= $sortFilter === 'recommended' ? 'selected' : '' ?>>Recommended</option>
            <option value="price" <?= $sortFilter === 'price' ? 'selected' : '' ?>>Lowest hourly rate</option>
            <option value="rating" <?= $sortFilter === 'rating' ? 'selected' : '' ?>>Highest rated</option>
          </select>
        </span>
      </label>
      <button class="btn btn-primary search-btn" type="submit">Search lots <span>→</span></button>
    </form>
  </section>

  <section class="map-section page-wrap">
    <div class="map-copy">
      <div class="eyebrow">PARKING AROUND YOU</div>
      <h2>Know where to go<br>before you get there.</h2>
      <p>See parking businesses on the map, check their current availability, and choose what works for you.</p>
      <div class="map-legend">
        <span><i class="legend-dot green"></i> Spaces available</span>
        <span><i class="legend-dot yellow"></i> Limited spaces</span>
        <span><i class="legend-dot orange"></i> Almost full</span>
        <span><i class="legend-dot red"></i> Full</span>
        <span><i class="legend-dot grey"></i> Closed</span>
      </div>
    </div>
    <div class="map-visual" role="img" aria-label="Illustrative map showing parking business locations">
      <div class="map-road road-a"></div>
      <div class="map-road road-b"></div>
      <div class="map-road road-c"></div>
      <div class="map-water"></div>
      <span class="map-label label-one">DOWNTOWN</span>
      <span class="map-label label-two">TIBANGA</span>
      <span class="map-label label-three">CITY CENTER</span>
      <?php
      $pinClasses = [1 => 'pin-one', 2 => 'pin-two', 3 => 'pin-three'];
      foreach ($lots as $l):
          [$lbl, $col] = vacancyInfo($l);
          $extraCls = ($col === 'amber' || $col === 'orange') ? 'limited' : (($col === 'red' || $col === 'grey') ? 'full' : '');
      ?>
        <a class="map-pin <?= $pinClasses[$l['id']] ?? 'pin-one' ?> <?= $extraCls ?>" href="index.php?lot=<?= (int)$l['id'] ?>">
          <b><?= !empty($l['closed']) ? '–' : (int)$l['available'] ?></b>
          <small><?= $col === 'red' ? 'Full' : ($col === 'grey' ? 'Closed' : '₱' . (int)$l['rate'] . '/hr') ?></small>
        </a>
      <?php endforeach; ?>
      <div class="map-attribution">Illustrative map · Sample locations</div>
    </div>
  </section>

  <section class="featured-section page-wrap" id="featured">
    <div class="section-heading">
      <div>
        <div class="eyebrow">PARKWISE PICKS</div>
        <h2>Parking worth<br>knowing about.</h2>
      </div>
      <?php if ($searchQ !== '' || $areaFilter !== ''): ?>
        <a class="text-link" href="index.php#featured">Clear filters ✕</a>
      <?php else: ?>
        <a class="text-link" href="#find-parking">Explore all lots <span>→</span></a>
      <?php endif; ?>
    </div>
    <div class="lot-grid">
      <?php if (empty($filteredLots)): ?>
        <div class="empty-state" style="grid-column:1/-1">No parking businesses match your search.</div>
      <?php else: ?>
        <?php foreach ($filteredLots as $lot): ?>
          <?= renderLotCard($lot, 'index.php#featured', true) ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section class="how-section" id="how-it-works">
    <div class="page-wrap">
      <div class="eyebrow eyebrow-light">A SIMPLE WAY TO PARK</div>
      <h2>Less circling.<br><span>More living.</span></h2>
      <div class="steps-grid">
        <article>
          <b>01</b>
          <div class="step-icon">⌕</div>
          <h3>Discover</h3>
          <p>Find parking businesses and compare location, rates, and reviews.</p>
        </article>
        <article>
          <b>02</b>
          <div class="step-icon">P</div>
          <h3>Park</h3>
          <p>Visit your chosen lot. The guard records your vehicle's entry.</p>
        </article>
        <article>
          <b>03</b>
          <div class="step-icon">✓</div>
          <h3>Go</h3>
          <p>Pay when you leave, receive your printed receipt, and share your experience.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="owner-cta page-wrap">
    <div>
      <div class="eyebrow">OWN A PARKING BUSINESS?</div>
      <h2>Bring your parking<br>business to ParkWise.</h2>
      <p>Apply to become a verified owner and manage your parking operations in one place.</p>
    </div>
    <a class="btn btn-primary" href="index.php?modal=register">Get started <span>→</span></a>
  </section>
</main>
