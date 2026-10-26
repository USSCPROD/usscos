<?php
/**
 * A small rich text editor: bold, italic, lists, headings and links.
 *
 * Hand-rolled rather than pulled from a CDN. The need is eight buttons over a
 * contenteditable div, and a third-party editor would add a script this project
 * does not otherwise load, on a page that edits pricing.
 *
 * The contenteditable is the editing surface; a hidden textarea carries the value
 * to the server, because a contenteditable div does not submit with a form. Whatever
 * arrives is sanitised by App\Services\RichText before it is stored, so the browser
 * side is a convenience and never the thing keeping the HTML safe.
 *
 * Expects: $rtName, $rtValue, $rtLabel, optional $rtRows, $rtHint
 */
$rtId    = 'rt_' . preg_replace('/[^a-z0-9]/i', '_', $rtName);
$rtRows  = $rtRows ?? 6;
$rtHint  = $rtHint ?? '';
?>
<div style="margin-bottom:1rem">
    <label style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;
                  letter-spacing:.05em;color:#6b7280;margin-bottom:.35rem"><?= e($rtLabel) ?></label>

    <div style="border:1px solid #d1d5db;border-radius:7px;overflow:hidden;background:#fff">
        <div style="display:flex;gap:.15rem;padding:.35rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb;flex-wrap:wrap"
             data-rt-toolbar="<?= e($rtId) ?>">
            <?php
            $buttons = [
                ['bold',          'B',  'Bold',        'font-weight:800'],
                ['italic',        'I',  'Italic',      'font-style:italic'],
                ['underline',     'U',  'Underline',   'text-decoration:underline'],
                ['formatBlock:h3','H3', 'Heading',     'font-weight:700;font-size:.72rem'],
                ['insertUnorderedList', '• List', 'Bulleted list', ''],
                ['insertOrderedList',   '1. List', 'Numbered list', ''],
                ['createLink',    'Link', 'Add a link', ''],
                ['unlink',        'Unlink', 'Remove link', ''],
                ['removeFormat',  'Clear', 'Strip formatting', ''],
            ];
            foreach ($buttons as [$cmd, $text, $title, $css]):
            ?>
                <button type="button" data-rt-cmd="<?= e($cmd) ?>" title="<?= e($title) ?>"
                        style="appearance:none;border:1px solid #e5e7eb;background:#fff;border-radius:5px;
                               padding:.3rem .55rem;font:inherit;font-size:.78rem;cursor:pointer;<?= $css ?>">
                    <?= e($text) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div contenteditable="true" id="<?= e($rtId) ?>"
             style="min-height:<?= (int)$rtRows * 1.6 ?>rem;padding:.7rem .85rem;font-size:.92rem;
                    line-height:1.6;outline:none"><?= $rtValue /* already sanitised on save */ ?></div>
    </div>

    <textarea name="<?= e($rtName) ?>" id="<?= e($rtId) ?>_input" style="display:none"></textarea>

    <?php if ($rtHint !== ''): ?>
        <div style="font-size:.72rem;color:#9ca3af;margin-top:.3rem"><?= e($rtHint) ?></div>
    <?php endif; ?>
</div>

<?php if (!defined('RICHTEXT_SCRIPT_DONE')): define('RICHTEXT_SCRIPT_DONE', true); ?>
<script>
(function () {
    // One handler for every editor on the page, bound once however many are rendered.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-rt-cmd]');
        if (!btn) { return; }

        var toolbar = btn.closest('[data-rt-toolbar]');
        var surface = document.getElementById(toolbar.dataset.rtToolbar);
        if (!surface) { return; }

        var cmd = btn.dataset.rtCmd, arg = null;

        if (cmd.indexOf(':') > -1) { var p = cmd.split(':'); cmd = p[0]; arg = p[1]; }

        if (cmd === 'createLink') {
            arg = window.prompt('Link address', 'https://');
            if (!arg) { return; }
        }

        surface.focus();
        // Deprecated, and still the only thing every browser implements for this.
        // Whatever it produces is sanitised server-side, so its output does not have
        // to be trusted - only useful.
        document.execCommand(cmd, false, arg);
        sync(surface);
    });

    function sync(surface) {
        var input = document.getElementById(surface.id + '_input');
        if (input) { input.value = surface.innerHTML; }
    }

    document.addEventListener('input', function (e) {
        if (e.target.isContentEditable) { sync(e.target); }
    });

    // Paste as plain text: pasting from Word otherwise carries a kilobyte of styling
    // that the sanitiser strips anyway, leaving the editor showing something the
    // saved page will not.
    document.addEventListener('paste', function (e) {
        if (!e.target.isContentEditable) { return; }
        e.preventDefault();
        var text = (e.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, text);
    });

    // Fill the hidden inputs before the form goes, in case nothing was typed.
    document.addEventListener('submit', function (e) {
        e.target.querySelectorAll('[contenteditable]').forEach(sync);
    }, true);

    document.querySelectorAll('[contenteditable]').forEach(sync);
}());
</script>
<?php endif; ?>
