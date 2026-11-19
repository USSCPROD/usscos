<?php
/**
 * The way into an online designer: a box on a product page, or a card in a grid.
 *
 * $kind  'endzone' | 'midfield'
 * $card  true for the grid card
 * The shop's own words for both.
 */
$d = [
    'endzone'  => ['href' => '/custom/endzone', 'name' => 'Custom Endzone Lettering',
                   'text' => "Type your team or school name, pick 15' to 25' letters in solid or outline, and see your end zone and price right away.",
                   'meta' => "Type your text · solid or outline · 15' to 25' letters", 'button' => 'Design Your Endzone →'],
    'midfield' => ['href' => '/custom/midfield', 'name' => 'Custom Midfield Logo',
                   'text' => "Upload your school or team logo, pick a high school, 8-man or college field and size it from 15' to 55' wide. See it on the field with the price.",
                   'meta' => "Upload your logo · any colors · 15' to 55' wide", 'button' => 'Design Your Midfield →'],
][$kind];
?>
<?php if (!empty($card)): ?>
  <div class="card" style="border-color:var(--blue)">
    <a href="<?= e($d['href']) ?>" style="display:flex;align-items:center;justify-content:center;aspect-ratio:4/3;background:#3b7a2a;
       color:#f6f6f2;font-weight:900;font-size:1.6rem;letter-spacing:.08em;text-decoration:none;border-bottom:1px solid var(--line)">
      <?= $kind === 'endzone' ? 'ENDZONE' : 'LOGO' ?>
    </a>
    <div class="card-b">
      <h3><a href="<?= e($d['href']) ?>"><?= e($d['name']) ?></a></h3>
      <div style="font-size:.78rem;color:var(--mut)"><?= e($d['meta']) ?></div>
      <div class="price" style="font-size:.9rem">Design online &amp; see your price →</div>
    </div>
  </div>
<?php else: ?>
  <a href="<?= e($d['href']) ?>" style="display:block;margin:0 0 1.25rem;padding:1.1rem 1.2rem;border-radius:10px;
     background:linear-gradient(135deg,#2f6b22,#3b7a2a);color:#fff;text-decoration:none">
    <span style="display:inline-block;font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;
          background:#ffffff26;padding:.2rem .5rem;border-radius:4px">Design online</span>
    <strong style="display:block;font-size:1.15rem;margin:.45rem 0 .25rem"><?= e($d['name']) ?></strong>
    <span style="display:block;font-size:.88rem;opacity:.92"><?= e($d['text']) ?></span>
    <span style="display:inline-block;margin-top:.7rem;background:#fff;color:#2f6b22;font-weight:800;font-size:.86rem;
          padding:.5rem .9rem;border-radius:6px"><?= e($d['button']) ?></span>
  </a>
<?php endif; ?>
