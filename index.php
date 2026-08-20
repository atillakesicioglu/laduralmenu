<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$uri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('#/index\.php(?:/|\?|$)#i', $uri)) {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: /' . ($qs !== '' ? '?' . $qs : ''), true, 301);
    exit;
}

try {
    $pdo = db();
    $ready = db_installed($pdo);
} catch (Throwable $e) {
    $ready = false;
}

if (!$ready) {
    header('Location: install.php');
    exit;
}

$categories = $pdo->query(
    'SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, id'
)->fetchAll();

$products = $pdo->query(
    'SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM products p
     JOIN categories c ON c.id = p.category_id
     WHERE p.is_active = 1 AND c.is_active = 1
     ORDER BY p.sort_order, p.id'
)->fetchAll();

$byCat = [];
foreach ($products as $p) {
    $byCat[(int) $p['category_id']][] = $p;
}

$featured = array_values(array_filter($products, static fn($p) => (int) $p['is_featured'] === 1));

$brand = setting($pdo, 'brand', 'La Dural');
$city = setting($pdo, 'city', 'Kdz. Ereğli');
$slogan = setting($pdo, 'slogan', 'sıcak · hızlı · lezzetli');
$addressShort = setting($pdo, 'address_short', 'Orhanlar Mah.');
$address = setting($pdo, 'address', 'Orhanlar Mahallesi Atatürk Bulvarı No:29');
$hours = setting($pdo, 'hours', '07:30 – 01:00');
$ig = setting($pdo, 'instagram', 'https://instagram.com/laduralcafe');
$igLabel = setting($pdo, 'instagram_label', '@laduralcafe');
$notice = setting($pdo, 'notice', '');
$noticeSub = setting($pdo, 'notice_sub', '');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($brand) ?> Menü</title>
  <link rel="stylesheet" href="assets/css/menu.css?v=13">
</head>
<body class="is-loading">
  <div id="pageLoader" class="page-loader" aria-hidden="true">
    <div class="page-loader-content">
      <img class="page-loader-logo" src="assets/img/logo.png?v=3" alt="<?= e($brand) ?>" width="180" height="44">
      <div class="page-loader-spinner" aria-hidden="true"></div>
    </div>
  </div>

<div class="page">
  <div class="sticky-shell">
    <header class="hero">
      <div class="hero-inner">
        <div class="brand-row">
          <img class="brand-logo" src="assets/img/logo.png?v=3" alt="<?= e($brand) ?>" width="95" height="22">
          <div class="city"><?= e($city) ?></div>
        </div>
        <div class="slogan"><?= e($slogan) ?></div>
        <div class="details">
          <span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8.2"/><circle cx="12" cy="12" r="2.1" fill="currentColor" stroke="none"/></svg>
            <?= e($addressShort) ?>
          </span>
          <span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="8.2"/><path d="M12 7.4v5l3 1.9"/></svg>
            <?= e($hours) ?>
          </span>
        </div>
      </div>
    </header>
    <nav class="category-nav" aria-label="Kategoriler">
      <button id="searchToggle" class="icon-button" type="button" aria-label="Menüde ara" aria-expanded="false">
        <svg id="searchIcon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <svg id="closeIcon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
      <div class="nav-divider" aria-hidden="true"></div>
      <div class="chip-rail">
        <?php foreach ($categories as $i => $cat): ?>
          <button class="chip<?= $i === 0 ? ' active' : '' ?>" type="button" data-target="<?= e($cat['slug']) ?>">
            <?= e($cat['name']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </nav>
    <div id="searchPanel" class="search-panel" aria-hidden="true">
      <div class="search-control">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9a8c87" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input id="menuSearch" type="search" inputmode="search" enterkeyhint="search" autocomplete="off" tabindex="-1" aria-label="Menüde ara" placeholder="Menüde ara">
        <button id="clearSearch" class="clear-button" type="button" aria-label="Aramayı temizle" hidden>
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
    </div>
  </div>

  <main>
    <section id="resultsBlock" class="results" aria-live="polite" hidden>
      <div class="eyebrow">Arama Sonuçları</div>
      <div id="resultSummary" class="result-summary"></div>
      <div id="resultList" class="result-list"></div>
      <div id="emptyResult" class="empty-result" hidden>Aramanızla eşleşen ürün bulunamadı.</div>
    </section>

    <div id="normalContent">
      <?php if ($featured): ?>
      <section class="featured-wrap" aria-labelledby="featured-title">
        <div class="eyebrow" id="featured-title">Öne Çıkanlar</div>
        <div class="featured-list">
          <?php foreach ($featured as $p): ?>
          <article class="featured-card">
            <div class="featured-category"><?= e($p['category_name']) ?></div>
            <div class="featured-heading">
              <h2><?= e($p['name']) ?></h2>
              <div class="price"><?= e(format_price($p['price'])) ?></div>
            </div>
            <?php if ($p['description'] !== ''): ?><p><?= e($p['description']) ?></p><?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <?php foreach ($categories as $cat): ?>
        <?php $items = $byCat[(int) $cat['id']] ?? []; ?>
        <?php if (!$items) { continue; } ?>
        <section class="menu-section" id="sec-<?= e($cat['slug']) ?>" data-section="<?= e($cat['slug']) ?>">
          <h2><?= e($cat['heading']) ?></h2>
          <div class="rule" aria-hidden="true"></div>
          <div class="section-items">
            <?php foreach ($items as $p): ?>
            <article class="menu-item"
              role="button"
              tabindex="0"
              data-name="<?= e($p['name']) ?>"
              data-description="<?= e($p['description']) ?>"
              data-note="<?= e($p['note']) ?>"
              data-category="<?= e($p['category_name']) ?>"
              data-price="<?= e(format_price($p['price'])) ?>"
              data-image="<?= e($p['image_path'] ?? '') ?>">
              <?php if (!empty($p['image_path'])): ?>
                <img class="item-thumb" src="<?= e($p['image_path']) ?>" alt="" width="72" height="72">
              <?php else: ?>
                <div class="item-thumb item-thumb-empty" aria-hidden="true"></div>
              <?php endif; ?>
              <div class="item-copy">
                <h3><?= e($p['name']) ?></h3>
                <p><?= e($p['description']) ?></p>
                <?php if ($p['note'] !== ''): ?><div class="item-meta"><?= e($p['note']) ?></div><?php endif; ?>
              </div>
              <div class="price"><?= e(format_price($p['price'])) ?></div>
            </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>

      <?php if ($notice !== ''): ?>
      <div class="notice-wrap">
        <div class="notice">
          <strong><?= e($notice) ?></strong>
          <?php if ($noticeSub !== ''): ?><span><?= e($noticeSub) ?></span><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>

  <footer>
    <img class="footer-logo" src="assets/img/logo-wine.png?v=3" alt="<?= e($brand) ?>" width="95" height="22">
    <div class="footer-info"><?= nl2br(e($address)) ?></div>
    <div class="footer-info"><?= e($hours) ?></div>
    <?php if ($ig !== ''): ?>
      <a href="<?= e($ig) ?>" target="_blank" rel="noopener"><?= e($igLabel) ?></a>
    <?php endif; ?>
    <div class="footer-slogan"><?= e($slogan) ?>.</div>
  </footer>

  <button id="toTop" class="to-top" type="button" aria-label="Sayfanın başına dön">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V6M6 12l6-6 6 6"/></svg>
  </button>
</div>

  <div id="productSheet" class="product-sheet" hidden aria-hidden="true">
    <button class="product-sheet-backdrop" type="button" aria-label="Kapat"></button>
    <div class="product-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="sheetTitle">
      <div class="product-sheet-media">
        <div class="product-sheet-grab" id="sheetGrab">
          <div class="product-sheet-handle" aria-hidden="true"></div>
        </div>
        <button id="sheetClose" class="product-sheet-close" type="button" aria-label="Kapat">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
        <img id="sheetImage" class="product-sheet-image" src="" alt="">
        <div id="sheetImageEmpty" class="product-sheet-image-empty" hidden>Fotoğraf yok</div>
      </div>
      <div class="product-sheet-body">
        <div class="product-sheet-top">
          <div class="product-sheet-copy">
            <div id="sheetCategory" class="product-sheet-category"></div>
            <h2 id="sheetTitle" class="product-sheet-title"></h2>
            <p id="sheetDescription" class="product-sheet-desc"></p>
            <div id="sheetNote" class="product-sheet-note" hidden></div>
          </div>
          <div id="sheetPrice" class="product-sheet-price"></div>
        </div>
      </div>
    </div>
  </div>

<script src="assets/js/menu.js?v=8"></script>
</body>
</html>
