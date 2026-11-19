<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\StorefrontRepository;
use App\Services\Stencils\DesignerError;
use App\Services\Stencils\EndzoneDesigner;
use App\Services\Stencils\MidfieldDesigner;
use App\Services\Stencils\StencilProof;
use App\Services\Stencils\TextDesigner;
use App\Services\WebsiteLeadService;

/**
 * The online stencil designers: custom endzone lettering and custom midfield logos.
 *
 * At the stencil shop's own addresses, /custom/endzone and /custom/midfield, so its links
 * and its old URLs land here. Public: no authentication.
 *
 * WHERE THEY END, FOR NOW. On the shop a design went into the cart, and the designers sat
 * behind a customer sign-in. USSCOS has neither yet (docs/STENCIL_SHOP_PORT.md, phases 3
 * and 4), so a design ends in "Request this stencil": the design, its price and the logo
 * become a stencil quote lead, routed like every website lead. The proof works the same.
 */
class DesignerController extends Controller
{
    /** The shop's preview colors: [hex, name]. Validated against this list. */
    public const COLORS = [['#c8102e', 'Red'], ['#f2a900', 'Gold'], ['#13294b', 'Navy'], ['#4b2e83', 'Purple'],
                           ['#e35205', 'Orange'], ['#00843d', 'Green'], ['#111111', 'Black'], ['#f6f6f2', 'White']];

    private EndzoneDesigner $endzone;
    private MidfieldDesigner $midfield;
    private TextDesigner $text;
    private StorefrontRepository $shop;

    public function __construct()
    {
        parent::__construct();
        $this->shop     = new StorefrontRepository();
        $this->endzone  = new EndzoneDesigner($this->shop);
        $this->midfield = new MidfieldDesigner();
        $this->text     = new TextDesigner($this->shop);
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public function endzonePage(Request $request, Response $response): Response
    {
        return $this->view('site.designer_endzone', [
            'title'  => 'Custom Endzone Lettering',
            'nav'    => (new SiteController())->navForDesigner(),
            'sizes'  => $this->endzone->sizes(),
            'ready'  => $this->endzone->ready(),
            'colors' => self::COLORS,
            'sent'   => (string)($request->query('sent') ?? ''),
            'error'  => (string)($request->query('error') ?? ''),
        ]);
    }

    /** The custom stencil builder: type your text, see it in our lettering, priced. */
    public function textPage(Request $request, Response $response): Response
    {
        $sizes = $this->text->sizes();
        $default = null;

        foreach ($sizes as $s) {
            if ($s['height'] === 12.0 && !$s['widthStated']) {
                $default = $s['key'];
            }
        }

        return $this->view('site.designer_text', [
            'title'     => 'Custom Stencil',
            'nav'       => (new SiteController())->navForDesigner(),
            'ready'     => $this->text->ready(),
            'sizes'     => $sizes,
            'default'   => $default ?? ($sizes[0]['key'] ?? ''),
            'materials' => TextDesigner::MATERIALS,
            'sent'      => (string)($request->query('sent') ?? ''),
            'error'     => (string)($request->query('error') ?? ''),
        ]);
    }

    public function textQuote(Request $request, Response $response): Response
    {
        try {
            return $response->json($this->text->quote(
                (string)$request->post('text', ''), (string)$request->post('size', ''), (string)$request->post('thickness', '')
            ));
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }
    }

    public function midfieldPage(Request $request, Response $response): Response
    {
        return $this->view('site.designer_midfield', [
            'title'   => 'Custom Midfield Logo',
            'nav'     => (new SiteController())->navForDesigner(),
            'options' => MidfieldDesigner::options(),
            'sent'    => (string)($request->query('sent') ?? ''),
            'error'   => (string)($request->query('error') ?? ''),
        ]);
    }

    // ── JSON ─────────────────────────────────────────────────────────────────

    public function endzoneQuote(Request $request, Response $response): Response
    {
        try {
            return $response->json($this->endzone->quote(
                (string)$request->post('text', ''), (string)$request->post('size', ''),
                (string)$request->post('style', ''), (string)$request->post('format', '')
            ));
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }
    }

    public function midfieldUpload(Request $request, Response $response): Response
    {
        $file    = isset($_FILES['logo']) && is_array($_FILES['logo']) ? $_FILES['logo'] : null;
        $preview = isset($_FILES['preview']) && is_array($_FILES['preview']) ? $_FILES['preview'] : null;

        if ($file === null) {
            return $response->json(['error' => 'Please choose your logo file.'], 422);
        }

        try {
            return $response->json($this->midfield->process($file, $preview));
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }
    }

    public function midfieldQuote(Request $request, Response $response): Response
    {
        try {
            return $response->json($this->midfield->quote(
                (string)$request->post('logo', ''), (string)$request->post('field', ''), $request->post('width')
            ));
        } catch (DesignerError $e) {
            return $response->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * The cleaned logo preview. Re-encoded by GD on upload, so it is a plain PNG and safe
     * to show inline; served with nosniff all the same.
     */
    public function midfieldLogo(Request $request, Response $response): Response
    {
        $file = MidfieldDesigner::path((string)$request->query('id', ''), 'logo.png');

        if ($file === null) {
            return $response->status(404)->body('Not found');
        }

        return $response->header('Content-Type', 'image/png')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, max-age=86400')
            ->body((string)file_get_contents($file));
    }

    // ── Proof ────────────────────────────────────────────────────────────────

    /** The proof, as a print-ready page: the browser's print dialog saves it as PDF. */
    public function proof(Request $request, Response $response): Response
    {
        $for = [
            'name'  => mb_substr((string)$request->query('name', ''), 0, 80),
            'org'   => mb_substr((string)$request->query('org', ''), 0, 120),
            'email' => mb_substr((string)$request->query('email', ''), 0, 120),
            'phone' => mb_substr((string)$request->query('phone', ''), 0, 40),
        ];

        try {
            if ($request->query('kind') === 'midfield') {
                $q    = $this->midfield->quote((string)$request->query('logo', ''), (string)$request->query('field', ''), $request->query('width'));
                $logo = (string)file_get_contents((string)MidfieldDesigner::path($q['logo'], 'logo.png'));
                $svg  = StencilProof::midfield($q, $logo, $for);
                $name = 'Midfield logo proof';
            } else {
                $q   = $this->endzoneFromQuery($request);
                $svg = StencilProof::endzone($q, self::color($request->query('fill'), '#c8102e'), self::color($request->query('line'), '#f6f6f2'), $for);
                $name = 'Endzone proof — ' . $q['text'];
            }
        } catch (DesignerError $e) {
            return $response->status(422)->body(e($e->getMessage()));
        }

        return $this->view('site.designer_proof', ['title' => $name, 'svg' => $svg]);
    }

    // ── Request this stencil ─────────────────────────────────────────────────

    /**
     * The design, priced again here rather than trusting the page, becomes a stencil quote
     * lead. For a midfield logo the customer's original file goes with it, so the art
     * team has what it cuts from.
     */
    public function request(Request $request, Response $response): Response
    {
        $kind = in_array($request->post('kind'), ['midfield', 'text'], true) ? (string)$request->post('kind') : 'endzone';
        $back = $kind === 'text' ? '/custom' : '/custom/' . $kind;

        $started = (int)$request->post('started', 0);
        if (trim((string)$request->post('website', '')) !== '' || ($started > 0 && time() - $started < 3)) {
            return $response->redirect($back . '?sent=1#request');
        }

        $d = [
            'name'  => trim((string)$request->post('name', '')),
            'email' => trim((string)$request->post('email', '')),
            'phone' => trim((string)$request->post('phone', '')),
            'org'   => trim((string)$request->post('org', '')),
            'note'  => trim((string)$request->post('message', '')),
        ];

        $problem = $d['name'] === '' ? 'Please tell us your name.'
            : (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) ? 'Please give an email address we can reply to.' : null);

        try {
            if ($problem !== null) {
                throw new DesignerError($problem);
            }

            if ($kind === 'text') {
                $q = $this->text->quote((string)$request->post('text', ''), (string)$request->post('size', ''), (string)$request->post('thickness', ''));
                $summary = sprintf("Custom stencil: “%s”\n%s letters, %s · lettering %s\" × %s\"\n%s · ships %s lbs\nOnline price: $%s",
                    implode(' / ', $q['lines']), $q['size'], $q['material'], $q['text']['width'], $q['text']['height'],
                    isset($q['sheets']) ? $q['pieces'] . ' letter stencils' : sprintf('Sheet %s" × %s"%s', $q['plate']['width'], $q['plate']['height'], $q['pieces'] > 1 ? ', ' . $q['pieces'] . ' pieces' : ''),
                    $q['weight'], number_format($q['price'], 2));
                $proof = '';
                $attach = null;
            } elseif ($kind === 'midfield') {
                $q = $this->midfield->quote((string)$request->post('logo', ''), (string)$request->post('field', ''), $request->post('width'));
                $summary = sprintf("Custom midfield logo — %s field\nLogo %s wide × %s tall (%s)\nOne stencil, %d panels · ships %d lbs in %d box(es)\nOnline price: $%s",
                    $q['fieldLabel'], EndzoneDesigner::ft($q['width']), EndzoneDesigner::ft($q['height']), $q['name'] ?: 'uploaded logo',
                    $q['panels'], $q['weight'], $q['boxes'], number_format($q['price'], 2));
                $proof = '/custom/proof?' . http_build_query(['kind' => 'midfield', 'logo' => $q['logo'], 'field' => $q['field'], 'width' => $q['width'],
                                                              'name' => $d['name'], 'org' => $d['org']]);
                $file = MidfieldDesigner::path($q['logo'], 'original');
                $attach = $file !== null ? ['tmp' => $file, 'name' => basename($file), 'logo' => $q['logo']] : null;
            } else {
                $q = $this->endzoneFromRequest($request);
                $summary = sprintf("Custom endzone lettering: “%s”\n%s letters, %s tall · %s\n%s · ships %d lbs in %d box(es)\nOnline price: $%s",
                    $q['text'], $q['styleLabel'], $q['size'], $q['formatLabel'],
                    $q['format'] === 'letters' ? $q['letters'] . ' letter stencils' : sprintf('%s × %s stencil, %d panels in %d section(s)',
                        EndzoneDesigner::ft($q['sheet']['width']), EndzoneDesigner::ft($q['sheet']['height']), $q['panels'], $q['sections']),
                    $q['weight'], $q['boxes'], number_format($q['price'], 2));
                $proof = '/custom/proof?' . http_build_query(['kind' => 'endzone', 'text' => $q['text'], 'size' => $q['sizeKey'], 'style' => $q['style'],
                                                              'format' => $q['format'], 'fill' => self::color($request->post('fill'), '#c8102e'),
                                                              'line' => self::color($request->post('line'), '#f6f6f2'),
                                                              'name' => $d['name'], 'org' => $d['org']]);
                $attach = null;
            }
        } catch (DesignerError $e) {
            return $response->redirect($back . '?error=' . rawurlencode($e->getMessage()) . '#request');
        }

        $about = ['midfield' => 'Custom Midfield Logo', 'endzone' => 'Custom Endzone Lettering', 'text' => 'Custom Stencil'][$kind];

        (new WebsiteLeadService())->designerRequest($d, $about,
            $summary . ($proof !== '' ? "\n\nProof: " . $proof : '') . ($d['note'] !== '' ? "\n\nTheir note:\n" . $d['note'] : ''), $attach);

        return $response->redirect($back . '?sent=1#request');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** @throws DesignerError */
    private function endzoneFromQuery(Request $r): array
    {
        return $this->endzone->quote((string)$r->query('text', ''), (string)$r->query('size', ''),
                                     (string)$r->query('style', ''), (string)$r->query('format', ''));
    }

    /** @throws DesignerError */
    private function endzoneFromRequest(Request $r): array
    {
        return $this->endzone->quote((string)$r->post('text', ''), (string)$r->post('size', ''),
                                     (string)$r->post('style', ''), (string)$r->post('format', ''));
    }

    /** A preview color, only from the list. */
    private static function color($value, string $default): string
    {
        $value = strtolower((string)$value);

        foreach (self::COLORS as [$hex]) {
            if ($hex === $value) {
                return $hex;
            }
        }

        return $default;
    }
}
