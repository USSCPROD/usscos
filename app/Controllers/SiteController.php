<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\StorefrontRepository;

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

        if ($child !== '') {
            foreach ($children as $c) {
                if ($c['slug'] === $child) {
                    $active = $c;
                    break;
                }
            }

            if ($active === null) {
                return $this->view('site.404', ['title' => 'Not found', 'nav' => $nav], 404);
            }

            $section = array_merge($section, [
                'categories' => $active['categories'] ?? [],
                'brands'     => $active['brands'] ?? [],
                'name_like'  => $active['name_like'] ?? [],
                'blurb'      => $active['blurb'] ?? $section['blurb'] ?? '',
            ]);
        }

        // The total, not the size of the page. count($products) is the LIMIT, so a
        // 208-product section was telling customers it held 60.
        return $this->view('site.section', [
            'title'    => $active !== null ? $active['label'] . ' — ' . $section['label'] : $section['label'],
            'nav'      => $nav,
            'section'  => $section,
            'children' => $children,
            'active'   => $active,
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
            'docs'     => $this->shop->documentsFor((int)$product['id']),
            'videos'   => $this->shop->videosFor($product),
        ]);
    }

    public function search(Request $request, Response $response): Response
    {
        $q = trim((string)($request->query('q') ?? ''));

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

        if (!isset($pages[$slug])) {
            return $this->view('site.404', ['title' => 'Not found', 'nav' => $nav], 404);
        }

        return $this->view('site.page', [
            'title'   => $pages[$slug][0],
            'nav'     => $nav,
            'heading' => $pages[$slug][0],
            'blurb'   => $pages[$slug][1],
            'sku'     => trim((string)($request->query('sku') ?? '')),
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
