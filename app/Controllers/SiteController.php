<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\StorefrontRepository;
use App\Services\WebsiteLeadService;

/**
 * The public website. No authentication: everything here is served to strangers.
 *
 * Every page runs through the site layout rather than the application one, so there
 * is no path by which a sidebar, a staff action or a flash message intended for an
 * employee can appear on a page a customer is reading.
 */
class SiteController extends Controller
{
    private StorefrontRepository $shop;

    public function __construct()
    {
        parent::__construct();
        $this->shop = new StorefrontRepository();
    }

    public function home(Request $request, Response $response): Response
    {
        // Already counted, and already filtered to the sections that hold something.
        $nav = $this->stockedNav();

        return $this->view('site.home', [
            'title'    => config('site.company.name') . ' — ' . config('site.company.tagline'),
            'nav'      => $nav,
            'promos'   => config('site.promos') ?? [],
            'pillars'  => config('site.pillars') ?? [],
            'featured' => $this->shop->featured(12),
        ]);
    }

    public function section(Request $request, Response $response, string $slug = '', string $child = ''): Response
    {
        $nav     = $this->stockedNav();
        $section = null;

        foreach ($nav as $s) {
            if (($s['slug'] ?? '') === $slug) {
                $section = $s;
                break;
            }
        }

        if ($section === null) {
            return $this->view('site.404', ['title' => 'Not found', 'nav' => $nav], 404);
        }

        // Children already arrive counted and filtered from stockedNav().
        $children = $section['children'] ?? [];

        // A subcategory narrows the section; the section's own rules no longer apply,
        // because a child is defined by what it contains, not by its parent's net.
        $active = null;

        $parent = null;

        if ($child !== '') {
            // A child, or a child's child: Endzone sits under Sports and each chain under
            // Retail, but every one has its own flat address, /shop/stencils/endzone.
            foreach ($children as $c) {
                if ($c['slug'] === $child) {
                    $active = $c;
                    break;
                }

                foreach ($c['children'] ?? [] as $g) {
                    if ($g['slug'] === $child) {
                        $active = $g;
                        $parent = $c;
                        break 2;
                    }
                }
            }

            if ($active === null) {
                return $this->view('site.404', ['title' => 'Not found', 'nav' => $nav], 404);
            }

            $section = array_merge($section, [
                'categories' => $active['categories'] ?? [],
                'brands'     => $active['brands'] ?? [],
                'name_like'  => $active['name_like'] ?? [],
                'blurb'      => ($active['blurb'] ?? '') !== '' ? $active['blurb'] : ($section['blurb'] ?? ''),
            ]);
        }

        // Long copy under the products: the category's own, or on a section's landing
        // page the copy of the tree it is built from.
        $root = $active === null && !empty($section['tree']) ? $this->shop->categoryCopy((string)$section['tree']) : [];
        $copy = $active !== null ? (string)($active['copy'] ?? '') : (string)($root['website_copy'] ?? '');

        // A category whose listings all sit in its own children (Retail Chain Stencils:
        // every stencil is filed under its chain) shows those children as cards, the
        // way the shop showed a card per chain, rather than one long undivided list.
        $groups = $active !== null && !empty($active['children']) && (int)($active['own'] ?? 0) === 0
            ? $active['children'] : [];

        // The total, not the size of the page. count($products) is the LIMIT, so a
        // 208-product section was telling customers it held 60.
        return $this->view('site.section', [
            'title'    => $active !== null ? $active['label'] . ' — ' . $section['label'] : $section['label'],
            'nav'      => $nav,
            'section'  => $section,
            'children' => $children,
            'active'   => $active,
            'parent'   => $parent,
            'copy'     => $copy,
            'groups'   => $groups,
            'total'    => $this->shop->sectionCount(
                $section['categories'] ?? [], $section['brands'] ?? [], $section['name_like'] ?? []
            ),
            'products' => $this->shop->section(
                $section['categories'] ?? [], $section['brands'] ?? [], 60, 0, $section['name_like'] ?? []
            ),
        ]);
    }

    public function product(Request $request, Response $response, string $sku = ''): Response
    {
        $nav     = $this->stockedNav();
        $product = $this->shop->productBySku($sku);

        if ($product === false) {
            return $this->view('site.404', ['title' => 'Not found', 'nav' => $nav], 404);
        }

        return $this->view('site.product', [
            // The browser tab and any shared link read the website name too, so the
            // page is called one thing everywhere a customer sees it.
            'title'    => trim((string)($product['website_name'] ?? '')) ?: $product['name'],
            'nav'      => $nav,
            'product'  => $product,
            'variants' => $this->shop->variantsOf($product),
            'images'   => $this->shop->imagesOf((int)$product['id']),
            'crumbs'   => $this->shop->crumbsFor((int)$product['id'], 'stencils'),
            'sent'     => (string)($request->raw('sent') ?? ''),
            'error'    => (string)($request->raw('error') ?? ''),
            'docs'     => $this->shop->documentsFor((int)$product['id']),
            'videos'   => $this->shop->videosFor($product),
        ]);
    }

    /**
     * A stencil by its listing address: /stencils/no-parking, /stencils/no-parking/4-inch-letters,
     * and ?material=3-32 for the thickness. The addresses the stencil shop used, so its
     * copy links and its old URLs land here one-for-one.
     */
    public function stencil(Request $request, Response $response, string $slug = '', string $size = ''): Response
    {
        $sku = $this->shop->skuForListing($slug, $size, (string)($request->raw('material') ?? ''));

        if ($sku === null) {
            return $this->view('site.404', ['title' => 'Not found', 'nav' => $this->stockedNav()], 404);
        }

        return $this->product($request, $response, $sku);
    }

    /**
     * The contact and quote form, posted. Becomes a lead, routed like every website lead.
     *
     * Spam: a honeypot field and a minimum time on the page, the same two checks the old
     * usscos.com forms use. Both fail open in the sense that matters - a bot is told it
     * worked, and nothing is written.
     */
    public function enquire(Request $request, Response $response): Response
    {
        $back  = (string)($request->raw('back') ?? '/contact');
        $back  = str_starts_with($back, '/') && !str_starts_with($back, '//') ? $back : '/contact';
        $sep   = str_contains($back, '?') ? '&' : '?';

        $started = (int)($request->raw('started') ?? 0);

        if (trim((string)($request->raw('website') ?? '')) !== '' || ($started > 0 && time() - $started < 3)) {
            return $response->redirect($back . $sep . 'sent=1#enquiry');
        }

        $d = [
            'name'    => trim((string)($request->raw('name') ?? '')),
            'email'   => trim((string)($request->raw('email') ?? '')),
            'phone'   => trim((string)($request->raw('phone') ?? '')),
            'org'     => trim((string)($request->raw('org') ?? '')),
            'topic'   => trim((string)($request->raw('topic') ?? '')),
            'message' => trim((string)($request->raw('message') ?? '')),
            'about'   => mb_substr(trim((string)($request->raw('about') ?? '')), 0, 200),
        ];

        $file    = isset($_FILES['artwork']) && is_array($_FILES['artwork']) ? $_FILES['artwork'] : null;
        $problem = $d['name'] === '' ? 'Please tell us your name.'
            : (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) ? 'Please give an email address we can reply to.'
            : ($d['message'] === '' ? 'Please tell us what you need.'
            : WebsiteLeadService::fileProblem($file)));

        if ($problem !== null) {
            return $response->redirect($back . $sep . 'error=' . rawurlencode($problem) . '#enquiry');
        }

        (new WebsiteLeadService())->siteEnquiry($d, $file);

        return $response->redirect($back . $sep . 'sent=1#enquiry');
    }

    public function search(Request $request, Response $response): Response
    {
        $q = trim((string)($request->raw('q') ?? ''));

        return $this->view('site.search', [
            'title'    => $q === '' ? 'Search' : 'Search: ' . $q,
            'nav'      => $this->stockedNav(),
            'q'        => $q,
            'products' => $q === '' ? [] : $this->shop->searchProducts($q),
        ]);
    }
    /**
     * The flat pages the shell links to but which have no content yet.
     *
     * A design review is derailed by broken links — people chase the 404 instead of
     * looking at the page. These render in the site layout and say plainly that they
     * are not written, which is the truth and reviews better than a dead end.
     */
    public function page(Request $request, Response $response, string $slug = ''): Response
    {
        $pages = [
            'about'    => ['About us', 'Founded in 1989 in Alpharetta, Georgia.'],
            'contact'  => ['Contact', 'Phone, email, address and hours.'],
            'quote'    => ['Request a quote', 'The form that becomes a lead in USSCOS.'],
            'catalogs' => ['Catalogs', 'Athletic, golf, soccer, robot paint and robots.'],
            'privacy'  => ['Privacy policy', ''],
            'terms'    => ['Terms and conditions', ''],
        ];

        // /about and friends are registered without a {slug} placeholder, so fall back
        // to the path itself rather than 404ing on a link the shell itself prints.
        if ($slug === '') {
            $slug = trim($request->path(), '/');
        }

        $nav = $this->stockedNav();

        // Written content first: a page in site_pages is the real thing.
        $stored = $this->shop->sitePage($slug);

        if ($stored !== false) {
            return $this->view('site.page', [
                'title'   => $stored['title'],
                'nav'     => $nav,
                'heading' => $stored['title'],
                'blurb'   => '',
                'html'    => $stored['html'],
            ]);
        }

        // Contact and quote are the enquiry form.
        if ($slug === 'contact' || $slug === 'quote') {
            $about = trim((string)($request->raw('about') ?? $request->raw('sku') ?? ''));

            return $this->view('site.contact', [
                'title' => $slug === 'quote' ? 'Request a quote' : 'Contact us',
                'nav'   => $nav,
                'quote' => $slug === 'quote',
                'about' => $about,
                'back'  => '/' . $slug . ($about !== '' ? '?about=' . rawurlencode($about) : ''),
                'sent'  => (string)($request->raw('sent') ?? ''),
                'error' => (string)($request->raw('error') ?? ''),
            ]);
        }

        if (!isset($pages[$slug])) {
            return $this->view('site.404', ['title' => 'Not found', 'nav' => $nav], 404);
        }

        return $this->view('site.page', [
            'title'   => $pages[$slug][0],
            'nav'     => $nav,
            'heading' => $pages[$slug][0],
            'blurb'   => $pages[$slug][1],
            'sku'     => trim((string)($request->raw('sku') ?? '')),
        ]);
    }
    /**
     * The sections that actually have something in them.
     *
     * A nav entry leading to "nothing is filed under this section yet" is worse than
     * no entry: it reads as a broken shop rather than a catalogue that does not carry
     * that line. Counted once per request and handed to every page, so the nav, the
     * homepage tiles and the footer cannot disagree about what exists.
     *
     * @return array<int,array<string,mixed>>
     */
    /** The navigation, for the stencil designers' pages (DesignerController). */
    public function navForDesigner(): array
    {
        return $this->stockedNav();
    }

    private function stockedNav(): array
    {
        $out = [];

        foreach (config('site.nav') ?? [] as $s) {
            $n = $this->shop->sectionCount($s['categories'] ?? [], $s['brands'] ?? [], $s['name_like'] ?? []);

            if ($n <= 0) {
                continue;
            }

            // Children from the category tree, so the menu follows the data. A section
            // that still lists its own children in config keeps them - the stencil
            // groupings are ours, not the website's, and have no tree to come from.
            $children = $s['children'] ?? [];

            if (!empty($s['tree'])) {
                $fromTree = $this->shop->treeChildren((string)$s['tree']);

                if ($fromTree !== []) {
                    $children = $fromTree;
                }
            }

            $out[] = $s + ['count' => $n, 'children' => $children];
        }

        return $out;
    }
}
