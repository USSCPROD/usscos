<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — <?= SITE_NAME ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; background: #f3f4f6; color: #111827; }
.header { background: #222b59; padding: 1.25rem 2rem; }
.header h1 { color: #fff; font-size: 1.4rem; font-weight: 700; letter-spacing: .02em; }
.header p { color: #a5b4d4; font-size: .9rem; margin-top: .2rem; }
.container { max-width: 860px; margin: 2.5rem auto; padding: 0 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #222b59; margin-bottom: .5rem; }
.page-sub { color: #6b7280; font-size: .95rem; margin-bottom: 2rem; }
.cards { display: table; width: 100%; border-collapse: separate; border-spacing: 1rem; margin: 0 -1rem; }
.cards-row { display: table-row; }
.card-wrap { display: table-cell; width: 33.33%; vertical-align: top; }
.card { background: #fff; border: 1px solid #d1d5db; border-radius: 10px; padding: 1.75rem 1.5rem; text-decoration: none; display: block; box-shadow: 0 1px 4px rgba(0,0,0,.07); transition: box-shadow .15s; }
.card:hover { box-shadow: 0 4px 12px rgba(34,43,89,.15); }
.card-icon { width: 42px; height: 42px; background: #eef1f9; border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center; }
.card-title { font-size: 1.05rem; font-weight: 700; color: #222b59; margin-bottom: .4rem; }
.card-desc { font-size: .875rem; color: #6b7280; line-height: 1.55; }
.card-cta { margin-top: 1.25rem; font-size: .85rem; font-weight: 700; color: #222b59; }
@media (max-width: 640px) {
    .cards { display: block; }
    .cards-row { display: block; }
    .card-wrap { display: block; width: 100%; margin-bottom: 1rem; }
}
</style>
</head>
<body>
<div class="header">
    <h1><?= SITE_NAME ?></h1>
    <p>Get in touch with our team</p>
</div>
<div class="container">
    <div class="page-title">How can we help?</div>
    <p class="page-sub">Choose the option below that best fits your needs and we'll get back to you shortly.</p>

    <table style="width:100%;border-collapse:separate;border-spacing:1rem;margin:0 -1rem">
        <tr style="vertical-align:top">
            <td style="width:33.33%;padding:.5rem">
                <a href="contact.php" class="card">
                    <div class="card-icon">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#222b59" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="card-title">Contact Us</div>
                    <div class="card-desc">General questions, pricing, or product information. We'll respond within one business day.</div>
                    <div class="card-cta">Send a message &rsaquo;</div>
                </a>
            </td>
            <td style="width:33.33%;padding:.5rem">
                <a href="paint-quote.php" class="card">
                    <div class="card-icon">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#222b59" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </div>
                    <div class="card-title">Paint Quote Request</div>
                    <div class="card-desc">Request pricing on marking paint, athletic paint, field marking products, or aerosol paint.</div>
                    <div class="card-cta">Request a quote &rsaquo;</div>
                </a>
            </td>
            <td style="width:33.33%;padding:.5rem">
                <a href="stencil-quote.php" class="card">
                    <div class="card-icon">
                        <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#222b59" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="card-title">Stencil Quote Request</div>
                    <div class="card-desc">Custom stencil design and production. Upload your artwork and we'll provide a quote.</div>
                    <div class="card-cta">Request a quote &rsaquo;</div>
                </a>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
