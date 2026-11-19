<?php ob_start(); $co = config('site.company'); ?>
<div class="wrap sec">
  <h1 style="margin:0 0 .4rem;font-size:1.9rem;letter-spacing:-.025em"><?= e($title) ?></h1>
  <p style="margin:0 0 1.75rem;color:var(--mut);max-width:62ch">
    Call <a href="tel:<?= e(preg_replace('/\D/', '', $co['phone'])) ?>"><?= e($co['phone']) ?></a>,
    email <a href="mailto:<?= e($co['email']) ?>"><?= e($co['email']) ?></a>,
    or send the form below. <?= e($co['hours']) ?>.
  </p>

  <table style="width:100%;border-collapse:collapse">
    <tr>
      <td style="vertical-align:top;padding-right:2rem">
        <?= view('site._enquiry_form', [
            'about' => $about, 'back' => $back, 'sent' => $sent, 'error' => $error, 'quote' => $quote,
        ]) ?>
      </td>
      <td style="vertical-align:top;width:20rem" class="side">
        <div style="border:1px solid var(--line);border-radius:10px;padding:1.25rem;margin-bottom:1rem;background:var(--soft)">
          <h3 style="margin:0 0 .4rem;font-size:1rem">Retail chain stencils</h3>
          <p style="margin:0 0 .6rem;font-size:.88rem;color:#374151">Striping a Walmart, Target, Chick-fil-A, McDonald's or
            another national chain? We stock stencils made to each chain's site specifications.</p>
          <a href="/shop/stencils/retail" style="font-size:.88rem;font-weight:600">Browse retail chain stencils →</a>
        </div>
        <div style="border:1px solid var(--line);border-radius:10px;padding:1.25rem;background:var(--soft)">
          <h3 style="margin:0 0 .4rem;font-size:1rem">Custom work</h3>
          <p style="margin:0;font-size:.88rem;color:#374151">Logos, endzones, midfields, rooftops and multi-piece stencils
            are quoted by our art team. Send your artwork or a sketch with dimensions.</p>
        </div>
      </td>
    </tr>
  </table>
</div>
<style>@media (max-width:860px){.sec > table td{display:block;width:auto!important;padding:0 0 1.5rem!important}}</style>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
