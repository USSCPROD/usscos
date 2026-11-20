<?php
/**
 * The top of /shop/stencils: the old stencil shop's home page (stencils.usscproducts.com),
 * with its wording and pictures, pointed at this site's addresses. The full stencil
 * listing follows it on the section page, under #all.
 *
 * $tiles  [label, href, count|null, image] for each stencil category, built in section.php
 */
$co    = config('site.company');
$phone = e($co['phone']);
$img   = '/assets/img/stencils/';
$about = [
    ['Parking lot and traffic stencils', '/shop/stencils/words',
     'NO PARKING, FIRE LANE, STOP, RESERVED and loading zone stencils, handicap parking and EV charging symbols, arrows, bike lane markings and stop bars, plus <a href="/shop/stencils/kits">letter and number kits</a> for stall numbers and custom messages. Pair them with our <a href="/shop/traffic-paint">federal-spec traffic paint</a>.'],
    ['Retail chain stencils', '/shop/stencils/retail',
     "Ready-made stencils made to the site specifications of national retail and restaurant chains, including Walmart, Target, The Home Depot, Lowe's, Kroger, McDonald's, Starbucks, Chick-fil-A, Dunkin', Zaxby's and 7 Brew: drive-thru and pickup markings, directional arrows and logos."],
    ['Athletic field stencils', '/shop/stencils/sports',
     'Football numbers, hashmarks and block letters, soccer, baseball, track and pickleball stencils. Design <a href="/custom/endzone">custom endzone lettering</a> or upload your logo for a <a href="/custom/midfield">custom midfield logo</a>, then mark it with our <a href="/shop/field-marking-paint">DURASTRIPE and ECOSTRIPE field marking paint</a>.'],
    ['Rooftop stencils', '/shop/stencils/rooftop',
     'Company names, logos and signs painted on rooftops near airports and on other high-visibility buildings, at any size. We draw a mockup, cut the stencils and supply the roof coatings, or do the whole job. Call or email for a quote.'],
    ['Yard and lawn stencils', '/shop/stencils/yard',
     'Team logos, mascots, flags, ribbons and messages painted on home yards and business lawns with a one-piece stencil that folds up for storage. Pair it with our grass-safe marking paint in over 100 colors.'],
    ['Custom stencils', '/custom',
     "Need wording we don't stock? Type your text, pick a letter size and material, and see your stencil drawn in the exact lettering we cut, with the size and price, before you order."],
];
$faq = [
    ['Do you sell the paint too?', "Yes. US Specialty Coatings manufactures field marking paint, federal-spec traffic paint and golf course paint, and sells striping machines. Shop them here or call us at {$phone}."],
    ['What thickness stencil should I choose?', '1/8" Heavy-Duty is our most rigid plastic: it stays flat in the wind, stands up to rough handling and is the best choice for contractors and frequent reuse. 3/32" Standard is lighter and more flexible, and a great value for occasional jobs. Field stencils are 10-mil polyethylene that lies flat on grass or turf and folds up small.'],
    ['Can you make a custom stencil?', 'Yes. Use our <a href="/custom">custom stencil builder</a> to type your text, pick a letter size and material, and see the exact stencil and price before you order. For logos, special layouts or anything else, call us at ' . $phone . '.'],
    ['How do I design endzone lettering or a midfield logo?', 'Create a free account and use our online designers. Type your team name or upload your logo, choose your field and size, and see it on the field with the price. You can save your design and download a proof to share with your athletic director, school administration or board.'],
    ['Are your stencils reusable?', 'Yes. Every stencil is cut from durable plastic made to be used job after job. Let the paint dry, clean the stencil and store it flat or rolled for next time.'],
    ['How is shipping calculated?', 'Enter your ZIP code in the cart to see live FedEx rates. Stencils ship rolled in boxes sized to fit, and smaller stencils are rolled up together to save on shipping.'],
];
?>
<div class="wrap">
  <table style="width:100%;border-collapse:collapse;margin:0 0 2rem"><tr>
    <td style="vertical-align:middle;padding:0 2rem 0 0">
      <div style="font-size:.75rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--hot)">Stencils + marking paint</div>
      <h2 style="margin:.4rem 0 .6rem;font-size:2.4rem;line-height:1.05;letter-spacing:-.03em;text-transform:uppercase;color:var(--ink)">
        Stencils and paint.<br><span style="color:var(--blue)">One supplier.</span></h2>
      <p style="margin:0 0 1.3rem;color:var(--mut);max-width:34rem;font-size:1.02rem">Reusable pavement marking stencils from US Specialty
        Coatings, an American manufacturer of field and traffic marking paint since 1989.</p>
      <a class="b b-p" href="#all">Shop Stencils →</a>
      <a class="b b-s" href="/custom" style="margin-left:.4rem">Create a Custom Stencil</a>
    </td>
    <td style="width:42%;vertical-align:middle" class="st-hero-img">
      <img src="<?= $img ?>hero.svg" alt="NO PARKING stencil on asphalt" style="width:100%;border-radius:10px;display:block">
    </td>
  </tr></table>

  <div class="grid g3" style="margin:0 0 2.5rem">
    <?php foreach ([['Stock &amp; custom stencils', 'Hundreds of ready-made stencils, or <a href="/custom">design your own online</a>.'],
                    ['Cut to order', 'Every stencil is cut in the size you need from 1/8" or 3/32" plastic.'],
                    ['Paint for the job', '<a href="/shop/field-marking-paint">Field</a> and <a href="/shop/traffic-paint">traffic</a> marking paint, made in the USA.']] as [$ft, $fh]): ?>
      <div style="border:1px solid var(--line);border-radius:10px;padding:1rem 1.1rem;font-size:.9rem;color:#374151">
        <strong style="display:block;color:var(--ink);margin-bottom:.2rem"><?= $ft ?></strong><?= $fh ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="sec-h" style="margin-bottom:1rem"><div><h2>Shop by category</h2></div></div>
  <div class="grid g3" style="margin:0 0 2.5rem">
    <?php foreach ($tiles as [$label, $href, $count, $pic]): ?>
      <a class="card" href="<?= e($href) ?>" style="text-decoration:none;color:inherit">
        <img src="<?= e($pic) ?>" alt="<?= e($label) ?>" loading="lazy" style="aspect-ratio:600/440;object-fit:cover;width:100%;display:block;border-bottom:1px solid var(--line)">
        <div class="card-b" style="text-align:center">
          <h3 style="margin:0;text-transform:uppercase;letter-spacing:.02em"><?= e($label) ?></h3>
          <div style="font-size:.8rem;color:var(--mut)"><?= $count === null ? 'Design online, see your price' : (int)$count . ' stencil' . ((int)$count === 1 ? '' : 's') ?></div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <div style="background:var(--navy);color:#fff;border-radius:12px;padding:1.6rem 1.8rem;margin:0 0 2.5rem">
    <table style="width:100%;border-collapse:collapse"><tr>
      <td style="vertical-align:middle">
        <h2 style="margin:0 0 .35rem;font-size:1.4rem">Need something custom?</h2>
        <p style="margin:0;opacity:.9;max-width:44rem">Type your text, pick a letter size and material, and see exactly how your stencil
          will look and what it costs before you order.</p>
      </td>
      <td style="vertical-align:middle;text-align:right;white-space:nowrap;padding-left:1rem">
        <a class="b b-w" href="/custom">Build Your Stencil →</a>
      </td>
    </tr></table>
  </div>

  <section class="rt" style="max-width:62rem;font-size:.95rem;color:#374151;line-height:1.7;margin:0 0 2rem">
    <h2 style="margin-top:0">Pavement Marking Stencils and Paint from One American Manufacturer</h2>
    <p class="lead">US Specialty Coatings has made field marking and traffic paint in the USA since 1989, for groundskeepers, parks
      departments, schools, sports facilities and striping contractors in all 50 states. Our stencils are reusable, CNC-cut from
      durable plastic sheet and cut to order in the size you need, so your stencils and your paint come from the same supplier.</p>
    <?php foreach ($about as [$t, $h, $html]): ?>
      <h3><a href="<?= e($h) ?>"><?= e($t) ?></a></h3>
      <p><?= $html ?></p>
    <?php endforeach; ?>

    <h2>Frequently asked questions</h2>
    <?php foreach ($faq as [$q, $a]): ?>
      <h3><?= e($q) ?></h3>
      <p><?= $a ?></p>
    <?php endforeach; ?>
  </section>
</div>
<style>@media (max-width:760px){.st-hero-img{display:none}}</style>
