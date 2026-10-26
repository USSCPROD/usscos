<?php ob_start(); ?>

<section class="hero">
  <div class="wrap">
    <h1>American made field marking paint since 1989</h1>
    <p>Direct from the manufacturer in Alpharetta, Georgia. Aerosol and bulk paint,
       stencils, striping machines and the coatings that keep a field ready to play.</p>
    <div class="btns">
      <a class="b b-w" href="/shop/field-marking-paint">Shop field paint</a>
      <a class="b b-p" href="/quote">Request a quote</a>
    </div>
  </div>
</section>

<div class="wrap sec">
  <div class="sec-h">
    <div>
      <h2>Shop by category</h2>
      <p>Everything we make and everything we stock.</p>
    </div>
  </div>
  <div class="grid g4">
    <?php foreach ($nav as $s): ?>
      <a href="/shop/<?= e($s['slug']) ?>" class="promo" style="display:block;color:inherit">
        <h3 style="color:var(--navy)"><?= e($s['label']) ?></h3>
        <p><?= e($s['blurb'] ?? '') ?></p>
        <span style="font-size:.76rem;font-weight:700;color:var(--blue)">
          <?= (int)$s['count'] ?> product<?= (int)$s['count'] === 1 ? '' : 's' ?> &rarr;
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div style="background:var(--soft);border-block:1px solid var(--line)">
  <div class="wrap sec">
    <div class="grid g3">
      <?php foreach ($promos as $pr): ?>
        <div class="promo" style="background:#fff">
          <h3><?= e($pr['title']) ?></h3>
          <p><?= e($pr['body']) ?></p>
          <a class="b b-p" href="<?= e($pr['href']) ?>"><?= e($pr['cta']) ?></a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="wrap sec">
  <div class="sec-h">
    <div>
      <h2>Best selling products</h2>
      <p>What ships most, straight from the plant.</p>
    </div>
    <a href="/shop/field-marking-paint" style="font-size:.86rem;font-weight:600">See all &rarr;</a>
  </div>

  <?php if (empty($featured)): ?>
    <div class="note">
      Nothing to show here yet — this row fills from products that have both a website
      price and a short description. Add those in USSCOS and they appear automatically.
    </div>
  <?php else: ?>
    <div class="grid g4">
      <?php foreach ($featured as $p): ?><?= view('site._card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div style="background:var(--soft);border-block:1px solid var(--line)">
  <div class="wrap sec">
    <div class="grid g3">
      <?php foreach ($pillars as $pl): ?>
        <div class="pill">
          <h3><?= e($pl['title']) ?></h3>
          <p><?= e($pl['body']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="wrap sec" style="text-align:center">
  <h2 style="margin:0 0 .5rem;font-size:1.5rem">Call for customised service</h2>
  <p style="color:var(--mut);margin:0 0 1.5rem">
    Bulk pricing, custom stencils and freight-shipped items are quoted by a person.
  </p>
  <div style="display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap">
    <a class="b b-p" href="/quote">Get a quote</a>
    <a class="b b-s" href="/contact">Send a purchase order</a>
  </div>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
