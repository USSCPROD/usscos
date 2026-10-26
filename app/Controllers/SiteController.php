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
        $nav = config('site.nav') ?? [];

        // Count each section now so the homepage tiles can say how much is behind
        // them, and so an empty section is obvious here rather than at launch.
        foreach ($nav as $i => $s) {
            $nav[$i]['count'] = $this->shop->sectionCount($s['categories'] ?? [], $s['brands'] ?? [], $s['name_like'] ?? []);
        }

        return $this->view('site.home', [
            'title'    => config('site.company.name') . ' — ' . config('site.company.tagline'),
            'nav'      => $nav,
            'promos'   => config('site.promos') ?? [],
            'pillars'  => config('site.pillars') ?? [],
            'featured' => $this->shop->featured(12),
        ]);
    }

    public function section(Request $request, Response $response, string $slug = ''): Response
    {
        $nav     = config('site.nav') ?? [];
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

        return $this->view('site.section', [
            'title'    => $section['label'],
            'nav'      => $nav,
            'section'  => $section,
            'products' => $this->shop->section(
                $section['categories'] ?? [], $section['brands'] ?? [], 60, 0, $section['name_like'] ?? []
            ),
        ]);
    }

    public function product(Request $request, Response $response, string $sku = ''): Response
    {
        $nav     = config('site.nav') ?? [];
        $product = $this->shop->productBySku($sku);

        if ($product === false) {
            return $this->view('site.404', ['title' => 'Not found', 'nav' => $nav], 404);
        }

        return $this->view('site.product', [
            'title'   => $product['name'],
            'nav'     => $nav,
            'product' => $product,
        ]);
    }

    public function search(Request $request, Response $response): Response
    {
        $q = trim((string)($request->query('q') ?? ''));

        return $this->view('site.search', [
            'title'    => $q === '' ? 'Search' : 'Search: ' . $q,
            'nav'      => config('site.nav') ?? [],
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

        $nav = config('site.nav') ?? [];

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
}
