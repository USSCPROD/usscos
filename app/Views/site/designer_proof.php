<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<style>
  /* One letter sheet, landscape, no margins: the SVG is exactly 792 x 612 points. */
  @page { size: letter landscape; margin: 0; }
  html, body { margin: 0; background: #e5e7eb; font-family: Arial, sans-serif; }
  .bar { display: flex; gap: .6rem; align-items: center; justify-content: center; padding: .8rem; }
  .bar button { padding: .6rem 1.1rem; border: 0; border-radius: 6px; background: #222b59; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
  .bar span { color: #374151; font-size: .9rem; }
  .sheet { width: 11in; height: 8.5in; margin: 0 auto 2rem; background: #fff; box-shadow: 0 2px 12px #0002; }
  .sheet svg { display: block; width: 100%; height: 100%; }
  @media print {
    html, body { background: #fff; }
    .bar { display: none; }
    .sheet { margin: 0; box-shadow: none; }
  }
</style>
</head>
<body>
  <div class="bar">
    <button type="button" onclick="window.print()">Print or save as PDF</button>
    <span>In the print dialog, choose “Save as PDF” to keep a copy.</span>
  </div>
  <!-- Built by App\Services\Stencils\StencilProof from priced, validated values only. -->
  <div class="sheet"><?= $svg ?></div>
  <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>
