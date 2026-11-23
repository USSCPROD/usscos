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
    /* Pinned to the sheet exactly, and clipped: without this a stray line box after the
       SVG is enough on its own to spill onto a second page. */
    html, body { background: #fff; width: 11in; height: 8.5in; overflow: hidden; }
    .bar { display: none; }
    .sheet { margin: 0; box-shadow: none; }
    /* Real inches, not percentages. Safari resolves a percentage height against a print
       container it has not laid out yet and draws nothing - the page comes out blank
       through Save as PDF while printing correctly on paper, because the two use
       different backends. Chrome is happy either way, so there is no cost to being
       explicit. */
    .sheet svg { width: 11in; height: 8.5in; }
  }
  /*
   * THERE WAS A PORTRAIT FALLBACK HERE AND IT WAS THE BUG. It rotated the sheet a
   * quarter turn for "a browser that ignores @page", but in Chrome the print
   * `orientation` feature reports portrait even when @page has already set landscape,
   * so the rule fired on every normal print: the sheet became 8.5 x 11, taller than the
   * printable height, and Save as PDF produced a second, empty page. Reported 23 Nov
   * 2026 as "I went to print and save and it was a blank page".
   *
   * Removed rather than re-scoped - @page size works in every browser we support, and a
   * fallback that misfires in the ordinary case is worse than no fallback. Verified by
   * printing this page headlessly: one page, with the proof on it.
   */
</style>
</head>
<body>
  <div class="bar">
    <button type="button" onclick="window.print()">Print or save as PDF</button>
    <span>In the print dialog, choose Landscape, then “Save as PDF” to keep a copy.</span>
  </div>
  <!-- Built by App\Services\Stencils\StencilProof from priced, validated values only. -->
  <div class="sheet"><?= $svg ?></div>
  <script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
</body>
</html>
