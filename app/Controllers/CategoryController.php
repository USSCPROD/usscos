<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CategoryRepository;

class CategoryController extends Controller
{
    private CategoryRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new CategoryRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view('categories.index', [
            'title'           => 'Categories',
            'tree'            => $this->repo->tree(),
            'uncategorised'   => $this->repo->uncategorisedCount(),
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        return $this->view('categories.form', [
            'title'    => 'New Category',
            'category' => null,
            'parents'  => $this->repo->selectOptions(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'A category needs a name.');
            return $response->redirect('/categories/create');
        }

        $data         = $_POST;
        $data['slug'] = trim($_POST['slug'] ?? '') !== ''
            ? $this->repo->uniqueSlug($_POST['slug'])
            : $this->repo->uniqueSlug($name);

        $id = $this->repo->insert($data);
        Session::flash('success', 'Category created.');
        return $response->redirect('/categories/' . $id . '/edit');
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        $category = $this->repo->find((int)$id);
        if (!$category) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('categories.form', [
            'title'    => 'Edit ' . $category['name'],
            'category' => $category,
            'parents'  => $this->repo->selectOptions((int)$id),
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $category = $this->repo->find((int)$id);
        if (!$category) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'A category needs a name.');
            return $response->redirect('/categories/' . (int)$id . '/edit');
        }

        $data         = $_POST;
        $submitted    = trim($_POST['slug'] ?? '');
        $data['slug'] = $submitted !== '' && $submitted !== $category['slug']
            ? $this->repo->uniqueSlug($submitted, (int)$id)
            : ($submitted !== '' ? $submitted : $this->repo->uniqueSlug($name, (int)$id));

        $this->repo->update((int)$id, $data);
        Session::flash('success', 'Category saved.');
        return $response->redirect('/categories');
    }

    /** Assign a batch of products to one category, from the products list. */
    public function bulkAssign(Request $request, Response $response): Response
    {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $productIds = (array)($_POST['product_ids'] ?? []);
        $back       = $_POST['redirect'] ?? '/products';

        if ($categoryId <= 0 || !$this->repo->find($categoryId)) {
            Session::flash('error', 'Choose a category to assign to.');
            return $response->redirect($back);
        }

        $productIds = array_filter(array_map('intval', $productIds), fn($i) => $i > 0);
        if (empty($productIds)) {
            Session::flash('error', 'No products were selected.');
            return $response->redirect($back);
        }

        $added    = $this->repo->assignProducts($categoryId, $productIds);
        $category = $this->repo->find($categoryId);
        $skipped  = count($productIds) - $added;

        Session::flash('success', sprintf(
            '%d product%s added to %s.%s',
            $added,
            $added === 1 ? '' : 's',
            $category['name'],
            $skipped > 0 ? " {$skipped} were already in it." : ''
        ));

        return $response->redirect($back);
    }

    public function destroy(Request $request, Response $response, string $id = '0'): Response
    {
        $category = $this->repo->find((int)$id);
        if (!$category) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        // Products keep existing; they simply lose this classification. Warn rather
        // than block, but make the consequence visible in the confirmation.
        $this->repo->delete((int)$id);
        Session::flash('success', 'Category deleted. Any products in it are now uncategorised.');
        return $response->redirect('/categories');
    }
}
