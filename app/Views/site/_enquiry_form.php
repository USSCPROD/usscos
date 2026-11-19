<?php
/**
 * The enquiry form: contact page, quote page, and every quote-only listing.
 *
 * $about  what it is about (a listing's name), carried into the lead
 * $back   where to come back to, with ?sent=1 or ?error=… appended
 * $sent   '1' once it has gone
 * $error  a message to show, if it was refused
 * $quote  true when it is a quote request, which changes the wording only
 *
 * Posts to /enquire as multipart, so artwork travels with it. The 'website' field is a
 * honeypot: hidden from people, filled in by bots.
 */
$about = (string)($about ?? '');
$sent  = (string)($sent ?? '');
$error = (string)($error ?? '');
$quote = (bool)($quote ?? false);
$co    = config('site.company');
?>
<div id="enquiry" style="border:1px solid var(--line);border-radius:10px;padding:1.5rem;background:#fff;max-width:40rem">
  <?php if ($sent === '1'): ?>
    <h2 style="margin:0 0 .5rem;font-size:1.25rem">Thank you</h2>
    <p style="margin:0;color:#374151">
      We have your message<?= $about !== '' ? ' about <strong>' . e($about) . '</strong>' : '' ?>
      and will get back to you soon (<?= e($co['hours']) ?>).
      Need us sooner? Call <a href="tel:<?= e(preg_replace('/\D/', '', $co['phone'])) ?>"><?= e($co['phone']) ?></a>.
    </p>
  <?php else: ?>
    <h2 style="margin:0 0 .35rem;font-size:1.25rem"><?= $quote ? 'Request a quote' : 'Send us a message' ?></h2>
    <p style="margin:0 0 1.1rem;color:var(--mut);font-size:.9rem">
      <?= $quote
          ? 'Tell us what you need, the size, and send your artwork or a sketch. We will quote it and send a proof before anything is cut.'
          : 'Questions about sizing, materials, volume pricing or stencils for your chain? We are happy to help.' ?>
    </p>

    <?php if ($error !== ''): ?>
      <div class="note" style="border-color:#fca5a5;background:#fef2f2;color:#991b1b;margin-bottom:1rem"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($about !== ''): ?>
      <div style="font-size:.85rem;margin-bottom:1rem">About: <strong><?= e($about) ?></strong></div>
    <?php endif; ?>

    <form method="POST" action="/enquire" enctype="multipart/form-data">
      <input type="hidden" name="about" value="<?= e($about) ?>">
      <input type="hidden" name="back" value="<?= e($back) ?>">
      <input type="hidden" name="started" value="<?= time() ?>">
      <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"
             style="position:absolute;left:-10000px;width:1px;height:1px;opacity:0">

      <table style="width:100%;border-collapse:separate;border-spacing:0 .7rem">
        <tr>
          <td style="width:50%;padding-right:.5rem;vertical-align:top">
            <label class="fl" for="enq-name">Your name</label>
            <input class="fi" id="enq-name" name="name" autocomplete="name" required>
          </td>
          <td style="width:50%;padding-left:.5rem;vertical-align:top">
            <label class="fl" for="enq-email">Email</label>
            <input class="fi" id="enq-email" name="email" type="email" autocomplete="email" required>
          </td>
        </tr>
        <tr>
          <td style="padding-right:.5rem;vertical-align:top">
            <label class="fl" for="enq-phone">Phone <span style="font-weight:400;color:var(--mut)">(optional)</span></label>
            <input class="fi" id="enq-phone" name="phone" type="tel" autocomplete="tel">
          </td>
          <td style="padding-left:.5rem;vertical-align:top">
            <label class="fl" for="enq-org">School, team or company <span style="font-weight:400;color:var(--mut)">(optional)</span></label>
            <input class="fi" id="enq-org" name="org" autocomplete="organization">
          </td>
        </tr>
        <?php if (!$quote && $about === ''): ?>
        <tr>
          <td colspan="2">
            <label class="fl" for="enq-topic">What's it about?</label>
            <select class="fi" id="enq-topic" name="topic">
              <?php foreach (['A question', 'A quote', 'An order', 'A return', 'Something else'] as $t): ?>
                <option><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <?php endif; ?>
        <tr>
          <td colspan="2">
            <label class="fl" for="enq-message">Message</label>
            <textarea class="fi" id="enq-message" name="message" rows="6" maxlength="5000" required
                      placeholder="<?= $about !== '' ? 'Tell us about your field or site, the size you need and your artwork.' : 'How can we help?' ?>"></textarea>
          </td>
        </tr>
        <tr>
          <td colspan="2">
            <label class="fl" for="enq-file">Attach artwork or a sketch
              <span style="font-weight:400;color:var(--mut)">(optional: PDF, AI, EPS, SVG, PNG or JPG, up to 25 MB)</span></label>
            <input id="enq-file" name="artwork" type="file" accept=".pdf,.ai,.eps,.svg,.png,.jpg,.jpeg,.gif,.webp"
                   style="font:inherit;font-size:.88rem">
          </td>
        </tr>
      </table>

      <button class="b b-p" type="submit" style="margin-top:.5rem">Send</button>
    </form>
  <?php endif; ?>
</div>
<style>
.fl{display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem;color:var(--ink)}
.fi{width:100%;box-sizing:border-box;padding:.6rem .7rem;border:1px solid var(--line);border-radius:6px;font:inherit;font-size:.92rem;background:#fff}
.fi:focus{outline:2px solid var(--blue);outline-offset:-1px;border-color:var(--blue)}
@media (max-width:560px){#enquiry td{display:block;width:auto!important;padding:0!important}}
</style>
