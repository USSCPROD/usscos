<?php
/**
 * Public site layout. Deliberately not layouts/app.php — there is no route by which
 * a staff sidebar, an internal action or an employee's flash message can reach a
 * page a customer is reading.
 *
 * On layout: this uses flex and grid rather than the table layout CLAUDE.md calls for.
 * That convention carries its own note saying it stands until someone tests flex
 * properly, and the shipping screens now in daily use are built on flex page headers
 * that render correctly. A storefront has to work on a phone, which is the one thing
 * a fixed-width table layout cannot do. Flagging it rather than quietly diverging.
 */
$siteNav = $nav ?? [];
$co      = config('site.company');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? $co['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#222b59; --navy-d:#19204180; --blue:#0A3D91; --ink:#1a1d29; --mut:#6b7280;
  --line:#e5e7eb; --bg:#fff; --soft:#f6f7fa; --hot:#c2410c;
  --wrap:1240px;
}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,-apple-system,sans-serif;color:var(--ink);background:var(--bg);line-height:1.5}
a{color:var(--blue);text-decoration:none}
a:hover{text-decoration:underline}
img{max-width:100%;display:block}
.wrap{max-width:var(--wrap);margin:0 auto;padding:0 20px}

/* ---- utility bar ---- */
.util{background:var(--navy);color:#c7d2fe;font-size:.78rem}
.util .wrap{display:flex;justify-content:space-between;align-items:center;gap:.4rem 1rem;min-height:38px;padding-block:.35rem;flex-wrap:wrap}
.util a{color:#e0e7ff}
.util nav{display:flex;gap:1.1rem;flex-wrap:wrap}
.util strong{color:#fff}

/* ---- masthead ---- */
.mast{border-bottom:1px solid var(--line)}
.mast .wrap{display:flex;align-items:center;gap:1.5rem;padding-top:18px;padding-bottom:18px}
.logo{font-weight:800;font-size:1.35rem;color:var(--navy);letter-spacing:-.02em;line-height:1.1;white-space:nowrap}
.logo span{display:block;font-size:.66rem;font-weight:600;color:var(--mut);letter-spacing:.06em;text-transform:uppercase}
.srch{flex:1;display:flex;max-width:520px}
.srch input{flex:1;padding:.6rem .85rem;border:1px solid var(--line);border-right:0;border-radius:6px 0 0 6px;font:inherit;font-size:.9rem}
.srch button{padding:.6rem 1.1rem;border:0;border-radius:0 6px 6px 0;background:var(--navy);color:#fff;font:inherit;font-weight:600;font-size:.9rem;cursor:pointer}
.mast-cta{margin-left:auto;text-align:right;white-space:nowrap}
.mast-cta .tel{font-weight:800;font-size:1.05rem;color:var(--navy)}
.mast-cta .lb{font-size:.72rem;color:var(--mut)}

/* ---- category nav ---- */
.catnav{background:var(--soft);border-bottom:1px solid var(--line)}
.catnav .wrap{display:flex;gap:.25rem}
/* No overflow-x here. It creates a clipping context, which cut the dropdown off
   at the bar - the panel was in the page and invisible. Horizontal scrolling is
   restored below 760px, where the dropdowns are suppressed anyway. */
.catnav .wrap::-webkit-scrollbar{display:none}
.catnav a{padding:.8rem .85rem;font-size:.84rem;font-weight:600;color:var(--ink);white-space:nowrap;border-bottom:3px solid transparent}
.catnav a:hover{color:var(--blue);border-bottom-color:var(--blue);text-decoration:none}
.catnav a.on{color:var(--blue);border-bottom-color:var(--blue)}

/* ---- category dropdowns ---- */
.catnav .item{position:relative}
.catnav .item > a{display:block}
.drop{
  position:absolute;left:0;top:100%;z-index:40;min-width:17rem;max-width:min(92vw,30rem);
  background:#fff;border:1px solid var(--line);border-top:0;border-radius:0 0 10px 10px;
  box-shadow:0 10px 30px #0f172a1f;padding:.5rem 0;
  display:none;
}
/* Shown on hover for a mouse, and on keyboard focus so it is reachable by tabbing -
   a menu that only answers to a pointer is closed to anyone using a keyboard. */
.catnav .item:hover > .drop,
.catnav .item:focus-within > .drop{display:block}
.drop a{display:flex;justify-content:space-between;gap:1rem;padding:.5rem 1rem;font-size:.86rem;
        font-weight:500;color:var(--ink);border:0;white-space:normal}
.drop a:hover{background:var(--soft);color:var(--blue);text-decoration:none}
.drop .n{color:var(--mut);font-size:.78rem;font-variant-numeric:tabular-nums}
.drop .all{border-top:1px solid var(--line);margin-top:.35rem;padding-top:.6rem;font-weight:700;color:var(--blue)}
@media (max-width:760px){
  /* No hover on a phone, and a panel pinned under a scrolling bar would be unusable.
     The section page lists the same categories as chips, which is the mobile answer. */
  .drop{display:none !important}
  .catnav .wrap{overflow-x:auto;scrollbar-width:none}
  .catnav .wrap::-webkit-scrollbar{display:none}
}

/* ---- buttons ---- */
.b{display:inline-block;padding:.65rem 1.25rem;border-radius:6px;font-weight:600;font-size:.88rem;cursor:pointer;border:1px solid transparent}
.b:hover{text-decoration:none}
.b-p{background:var(--blue);color:#fff}
.b-p:hover{background:#082f6f}
.b-s{background:#fff;color:var(--navy);border-color:var(--line)}
.b-s:hover{border-color:var(--navy)}
.b-w{background:#fff;color:var(--navy)}

/* ---- hero ---- */
.hero{background:linear-gradient(135deg,var(--navy) 0%,#2d3a73 100%);color:#fff}
.hero .wrap{padding:3.5rem 20px}
.hero h1{margin:0 0 .6rem;font-size:2.5rem;line-height:1.1;letter-spacing:-.025em;max-width:16ch}
.hero p{margin:0 0 1.5rem;color:#c7d2fe;font-size:1.05rem;max-width:52ch}
.hero .btns{display:flex;gap:.6rem;flex-wrap:wrap}

/* ---- generic section ---- */
.sec{padding:3rem 0}
.sec-h{display:flex;justify-content:space-between;align-items:baseline;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap}
.sec-h h2{margin:0;font-size:1.5rem;letter-spacing:-.02em}
.sec-h p{margin:.3rem 0 0;color:var(--mut);font-size:.9rem}

/* ---- grids ---- */
.grid{display:grid;gap:1rem}
.g4{grid-template-columns:repeat(auto-fill,minmax(230px,1fr))}
.g3{grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}

/* ---- cards ---- */
.card{border:1px solid var(--line);border-radius:10px;overflow:hidden;background:#fff;display:flex;flex-direction:column;transition:box-shadow .15s,border-color .15s}
.card:hover{border-color:#cbd5e1;box-shadow:0 4px 16px #0f172a14}
.ph{aspect-ratio:4/3;background:var(--soft);display:flex;align-items:center;justify-content:center;color:#9aa3b2;font-size:.72rem;text-align:center;padding:1rem;border-bottom:1px solid var(--line)}
.card-b{padding:.9rem 1rem 1.1rem;display:flex;flex-direction:column;flex:1}
.card-b h3{margin:0 0 .3rem;font-size:.95rem;font-weight:600;line-height:1.3}
.card-b h3 a{color:var(--ink)}
.card-b h3 a:hover{color:var(--blue)}
.sku{font-family:ui-monospace,monospace;font-size:.7rem;color:var(--mut);margin-bottom:.45rem}
.desc{font-size:.8rem;color:var(--mut);margin:0 0 .8rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.price{margin-top:auto;font-size:1.15rem;font-weight:700;color:var(--navy)}
.callfor{margin-top:auto;font-size:.82rem;font-weight:600;color:var(--hot)}

/* ---- promo tiles ---- */
.promo{border:1px solid var(--line);border-radius:10px;padding:1.5rem;background:var(--soft)}
.promo h3{margin:0 0 .4rem;font-size:1.05rem;letter-spacing:-.01em}
.promo p{margin:0 0 1rem;font-size:.87rem;color:var(--mut)}

/* ---- pillars ---- */
.pill{text-align:center;padding:1.5rem}
.pill h3{margin:0 0 .5rem;font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;color:var(--blue)}
.pill p{margin:0;color:var(--mut);font-size:.9rem}

/* ---- footer ---- */
.foot{background:var(--navy);color:#c7d2fe;margin-top:3rem;padding:2.5rem 0 1.5rem;font-size:.86rem}
.foot .cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:2rem;margin-bottom:2rem}
.foot h4{margin:0 0 .7rem;color:#fff;font-size:.74rem;text-transform:uppercase;letter-spacing:.08em}
.foot a{color:#c7d2fe;display:block;padding:.18rem 0}
.foot a:hover{color:#fff}
.foot .bot{border-top:1px solid #ffffff1f;padding-top:1.2rem;display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;font-size:.78rem}

/* ---- notice ---- */
.note{background:#fffbeb;border:1px solid #fcd34d;color:#92400e;padding:.7rem 1rem;border-radius:8px;font-size:.84rem;margin-bottom:1.5rem}

@media (max-width:760px){
  /* On a phone the utility bar's right-hand text wrapped under a fixed height and
     was clipped mid-line. The phone number is already in the masthead below it. */
  .util .wrap > div:last-child{display:none}
  .catnav a{padding:.7rem .6rem;font-size:.8rem}
  .mast .wrap{flex-wrap:wrap}
  .srch{order:3;flex-basis:100%;max-width:none}
  .mast-cta{margin-left:0}
  .hero h1{font-size:1.85rem}
  .hero .wrap{padding:2.5rem 20px}
}
</style>
</head>
<body>

<div class="util">
  <div class="wrap">
    <nav>
      <a href="/about">About</a><a href="/catalogs">Catalogs</a>
      <a href="/quote">Quote request</a><a href="/contact">Contact</a>
    </nav>
    <div><strong><?= e($co['phone_alpha']) ?></strong> &nbsp;·&nbsp; Call or email for best pricing</div>
  </div>
</div>

<header class="mast">
  <div class="wrap">
    <a href="/shop" class="logo">US Specialty Coatings<span>American made since 1989</span></a>
    <form class="srch" method="GET" action="/search">
      <input type="search" name="q" placeholder="Search paint, stencils, machines…"
             value="<?= e($q ?? '') ?>" aria-label="Search products">
      <button type="submit">Search</button>
    </form>
    <div class="mast-cta">
      <div class="tel"><?= e($co['phone']) ?></div>
      <div class="lb"><?= e($co['hours']) ?></div>
    </div>
  </div>
</header>

<nav class="catnav">
  <div class="wrap">
    <?php foreach ($siteNav as $s): ?>
      <?php $kids = $s['children'] ?? []; ?>
      <div class="item">
        <a href="/shop/<?= e($s['slug']) ?>"
           class="<?= (($activeSlug ?? '') === $s['slug']) ? 'on' : '' ?>"><?= e($s['label']) ?></a>
        <?php if ($kids !== []): ?>
          <div class="drop">
            <?php foreach ($kids as $k): ?>
              <a href="/shop/<?= e($s['slug']) ?>/<?= e($k['slug']) ?>">
                <span><?= e($k['label']) ?></span>
                <span class="n"><?= (int)$k['count'] ?></span>
              </a>
            <?php endforeach; ?>
            <a class="all" href="/shop/<?= e($s['slug']) ?>">
              <span>All <?= e($s['label']) ?></span>
              <span class="n"><?= (int)($s['count'] ?? 0) ?></span>
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</nav>

<?= $content ?>

<footer class="foot">
  <div class="wrap">
    <div class="cols">
      <div>
        <h4>Shop</h4>
        <?php foreach (array_slice($siteNav, 0, 5) as $s): ?>
          <a href="/shop/<?= e($s['slug']) ?>"><?= e($s['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <div>
        <h4>Company</h4>
        <a href="/about">About us</a><a href="/catalogs">Catalogs</a>
        <a href="/quote">Request a quote</a><a href="/contact">Contact</a>
      </div>
      <div>
        <h4>Contact</h4>
        <div style="line-height:1.8">
          <?= e($co['address']['city']) ?>, <?= e($co['address']['state']) ?><br>
          <a href="tel:<?= e(preg_replace('/\D/','',$co['phone'])) ?>"><?= e($co['phone']) ?></a>
          <a href="mailto:<?= e($co['email']) ?>"><?= e($co['email']) ?></a>
          <?= e($co['hours']) ?>
        </div>
      </div>
    </div>
    <div class="bot">
      <div>&copy; <?= date('Y') ?> US Specialty Coatings. All rights reserved.</div>
      <div><a href="/privacy">Privacy policy</a> &nbsp; <a href="/terms">Terms &amp; conditions</a></div>
    </div>
  </div>
</footer>

</body>
</html>
