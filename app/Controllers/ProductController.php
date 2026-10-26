<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Barcode;
use App\Services\ProductService;

class ProductController extends Controller
{
    private ProductService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ProductService();
    }

    public function index(Request $request, Response $response): Response
    {
        $page    = max(1, (int)($request->query('page') ?? 1));
        $perPage = 50;
        $search  = trim($request->query('q') ?? '');
        $brand   = trim($request->query('brand') ?? '');
        $filter  = $request->query('filter') ?? 'active';
        $category = trim((string)($request->query('category') ?? ''));

        // ?exact=1 means "only this category", for auditing what is filed where.
        $exact = (string)($request->query('exact') ?? '') === '1';

        $data = $this->service->list($page, $perPage, $search, $brand, $filter, $category, $exact);

        return $this->view('products.index', ['title' => 'Products', ...$data]);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\PDOException $e) {
            // PDOException extends RuntimeException — let real database errors
            // surface instead of being disguised as a 404.
            throw $e;
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('products.show', [
            'title' => $data['product']['sku'] . ' — ' . $data['product']['name'],
            ...$data,
        ]);
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\PDOException $e) {
            throw $e;
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('products.edit', [
            'title' => 'Edit ' . $data['product']['sku'],
            ...$data,
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->service->update((int)$id, $_POST);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        Session::flash('success', 'Product saved.');
        return $response->redirect('/products/' . (int)$id);
    }

    public function autocomplete(Request $request, Response $response): Response
    {
        $term = trim($request->query('q') ?? '');
        if ($term === '') {
            return $this->json([]);
        }

        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT id, sku, quickbooks_item, name, price, cost, uom_code
            FROM products
            WHERE is_active = 1
              AND item_type != 'raw_material'
              AND (sku LIKE :s OR quickbooks_item LIKE :s2 OR name LIKE :s3)
            ORDER BY sku
            LIMIT 15
        ");
        $like = '%' . $term . '%';
        $stmt->execute([':s' => $like, ':s2' => $like, ':s3' => $like]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json($rows);
    }

    // Searches all products including raw materials — used by PO form
    public function autocompleteAll(Request $request, Response $response): Response
    {
        $term = trim($request->query('q') ?? '');
        if ($term === '') {
            return $this->json([]);
        }

        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT id, sku, name, cost, uom_code, item_type,
                   vendor_part_number, preferred_vendor_name
            FROM products
            WHERE is_active = 1
              AND (sku LIKE :s OR name LIKE :s2 OR vendor_part_number LIKE :s3)
            ORDER BY item_type = 'raw_material' DESC, name ASC
            LIMIT 20
        ");
        $like = '%' . $term . '%';
        $stmt->execute([':s' => $like, ':s2' => $like, ':s3' => $like]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $this->json($rows);
    }

    public function rawMaterials(Request $request, Response $response): Response
    {
        $search = trim($request->query('q') ?? '');
        $page   = max(1, (int)($request->query('page') ?? 1));

        $paginator = $this->service->listRawMaterials($page, 50, $search);

        return $this->view('raw_materials.index', [
            'title'     => 'Raw Materials',
            'paginator' => $paginator,
            'search'    => $search,
        ]);
    }

    public function rawMaterialsCreate(Request $request, Response $response): Response
    {
        return $this->view('raw_materials.form', [
            'title' => 'Add Raw Material',
            'item'  => null,
        ]);
    }

    public function rawMaterialsStore(Request $request, Response $response): Response
    {
        $id = $this->service->storeRawMaterial($_POST);
        Session::flash('success', 'Raw material added.');
        return $response->redirect('/raw-materials/' . $id . '/edit');
    }

    public function rawMaterialsEdit(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->service->getRawMaterial((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        return $this->view('raw_materials.form', [
            'title' => 'Edit — ' . $item['name'],
            'item'  => $item,
        ]);
    }

    public function rawMaterialsUpdate(Request $request, Response $response, string $id = '0'): Response
    {
        $item = $this->service->getRawMaterial((int)$id);
        if (!$item) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $this->service->updateRawMaterial((int)$id, $_POST);
        Session::flash('success', 'Raw material saved.');
        return $response->redirect('/raw-materials/' . (int)$id . '/edit');
    }

    // ------------------------------------------------------- Images

    private const IMAGE_EXT  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const IMAGE_MAX  = 10485760;   // 10 MB

    private const DOC_EXT    = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'jpg', 'jpeg', 'png'];
    private const DOC_MAX    = 26214400;   // 25 MB

    public function uploadImage(Request $request, Response $response, string $id = '0'): Response
    {
        $productId = (int)$id;
        $back      = '/products/' . $productId;

        $stored = $this->storeUpload($_FILES['image'] ?? null, $productId, self::IMAGE_EXT, self::IMAGE_MAX);
        if (is_string($stored)) {
            Session::flash('error', $stored);
            return $response->redirect($back);
        }

        $repo = new \App\Repositories\ProductRepository();
        $user = \App\Core\Auth::user();

        $repo->insertImage([
            'product_id'  => $productId,
            'file_path'   => $stored['web_path'],
            'file_name'   => $stored['original'],
            'caption'     => $_POST['caption'] ?? null,
            // first image uploaded becomes the primary automatically
            'is_primary'  => $repo->countImages($productId) === 0 ? 1 : 0,
            'sort_order'  => $repo->nextImageSort($productId),
            'uploaded_by' => $user['id'] ?? null,
        ]);

        Session::flash('success', 'Image uploaded.');
        return $response->redirect($back);
    }

    public function deleteImage(Request $request, Response $response, string $id = '0', string $imageId = '0'): Response
    {
        $productId = (int)$id;
        $repo      = new \App\Repositories\ProductRepository();
        $image     = $repo->findImage((int)$imageId, $productId);

        if ($image) {
            $wasPrimary = (int)$image['is_primary'] === 1;
            $this->deleteUploadedFile($image['file_path']);
            $repo->deleteImage((int)$imageId, $productId);

            // Promote another image if we just removed the primary one
            if ($wasPrimary) {
                $remaining = $repo->getImages($productId);
                if (!empty($remaining)) {
                    $repo->setPrimaryImage((int)$remaining[0]['id'], $productId);
                }
            }
            Session::flash('success', 'Image deleted.');
        }

        return $response->redirect('/products/' . $productId);
    }

    public function setPrimaryImage(Request $request, Response $response, string $id = '0', string $imageId = '0'): Response
    {
        $productId = (int)$id;
        $repo      = new \App\Repositories\ProductRepository();

        if ($repo->findImage((int)$imageId, $productId)) {
            $repo->setPrimaryImage((int)$imageId, $productId);
            Session::flash('success', 'Primary image updated.');
        }

        return $response->redirect('/products/' . $productId);
    }

    // ---------------------------------------------------- Documents

    public function uploadDocument(Request $request, Response $response, string $id = '0'): Response
    {
        $productId = (int)$id;
        $back      = '/products/' . $productId;

        $stored = $this->storeUpload($_FILES['document'] ?? null, $productId, self::DOC_EXT, self::DOC_MAX);
        if (is_string($stored)) {
            Session::flash('error', $stored);
            return $response->redirect($back);
        }

        $allowedTypes = ['sds', 'tds', 'sales_flyer', 'catalog', 'color_chart', 'spec_sheet', 'certificate', 'other'];
        $docType      = in_array($_POST['doc_type'] ?? '', $allowedTypes, true) ? $_POST['doc_type'] : 'other';
        $title        = trim($_POST['title'] ?? '') ?: $stored['original'];

        $repo = new \App\Repositories\ProductRepository();
        $user = \App\Core\Auth::user();

        $repo->insertDocument([
            'product_id'  => $productId,
            'doc_type'    => $docType,
            'title'       => $title,
            'file_path'   => $stored['web_path'],
            'file_name'   => $stored['original'],
            'file_size'   => $stored['size'],
            'mime_type'   => $stored['mime'],
            'is_public'   => isset($_POST['is_public']) ? 1 : 0,
            'uploaded_by' => $user['id'] ?? null,
        ]);

        Session::flash('success', 'Document uploaded.');
        return $response->redirect($back);
    }

    public function deleteDocument(Request $request, Response $response, string $id = '0', string $docId = '0'): Response
    {
        $productId = (int)$id;
        $repo      = new \App\Repositories\ProductRepository();
        $doc       = $repo->findDocument((int)$docId, $productId);

        if ($doc) {
            $this->deleteUploadedFile($doc['file_path']);
            $repo->deleteDocument((int)$docId, $productId);
            Session::flash('success', 'Document deleted.');
        }

        return $response->redirect('/products/' . $productId);
    }

    // ------------------------------------------------- Upload helpers

    /**
     * Validate and move an upload into /public/uploads/products/{id}/.
     * Returns an array on success, or an error message string on failure.
     *
     * The stored filename is always generated here — the client-supplied name is
     * never used on disk, which rules out path traversal and .php uploads.
     */
    private function storeUpload(?array $file, int $productId, array $allowedExt, int $maxBytes): array|string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'No file was selected.';
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return match ((int)$file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is too large for the server to accept.',
                UPLOAD_ERR_PARTIAL                        => 'The upload was interrupted — please try again.',
                default                                   => 'Upload failed (error ' . (int)$file['error'] . ').',
            };
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return 'Invalid upload.';
        }
        if ($file['size'] > $maxBytes) {
            return 'File is too large. Maximum is ' . round($maxBytes / 1048576) . ' MB.';
        }

        $original = (string)$file['name'];
        $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return 'File type ".' . $ext . '" is not allowed. Accepted: ' . implode(', ', $allowedExt) . '.';
        }

        $dir = BASE_PATH . '/public/uploads/products/' . $productId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return 'Could not create the upload folder.';
        }

        // Readable slug from the original name + short random suffix for uniqueness
        $base = pathinfo($original, PATHINFO_FILENAME);
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $base) ?? '');
        $slug = trim($slug, '-');
        if ($slug === '') $slug = 'file';
        $slug = substr($slug, 0, 60);

        $filename = $slug . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
            return 'Could not save the uploaded file.';
        }
        @chmod($dir . '/' . $filename, 0644);

        return [
            'web_path' => '/uploads/products/' . $productId . '/' . $filename,
            'original' => $original,
            'size'     => (int)$file['size'],
            'mime'     => $file['type'] ?? null,
        ];
    }

    /** Remove a stored upload, refusing anything outside the products upload tree. */
    private function deleteUploadedFile(?string $webPath): void
    {
        if (!$webPath || !str_starts_with($webPath, '/uploads/products/')) {
            return;
        }
        if (str_contains($webPath, '..')) {
            return;
        }
        $full = BASE_PATH . '/public' . $webPath;
        if (is_file($full)) {
            @unlink($full);
        }
    }
    /**
     * A sheet of barcode labels, sized for a laser printer and plain Avery stock.
     *
     * Symbol choice matters more than it looks. A product with a real GTIN should
     * carry it, because that is the code Amazon and a customer's own receiving scanner
     * expect. A product without one still needs to be scannable here, and its SKU is
     * a perfectly good code for our own four walls - resolveScan already matches on
     * sku, so a Code 128 of the SKU works the moment it is printed. 'auto' picks the
     * best available per product rather than making somebody sort them into two runs.
     */
    public function labels(Request $request, Response $response): Response
    {
        $ids = array_filter(array_map('trim', explode(',', (string)($request->query('ids') ?? ''))));

        $products = (new \App\Repositories\ProductRepository())->forLabels(
            $ids,
            trim((string)($request->query('q') ?? '')),
            (string)($request->query('filter') ?? 'active')
        );

        $symbol = in_array($request->query('symbol'), ['auto', 'sku', 'upc', 'case'], true)
            ? (string)$request->query('symbol')
            : 'auto';

        // One label per shelf position is the normal case; a run of identical labels
        // is for a pallet of cases, so cap it well short of a jammed printer.
        $copies = min(50, max(1, (int)($request->query('copies') ?? 1)));

        $rendered = [];
        $skipped  = [];

        foreach ($products as $p) {
            $gtin12 = preg_replace('/\D/', '', (string)($p['gtin12'] ?? '')) ?? '';
            $gtin14 = preg_replace('/\D/', '', (string)($p['gtin14'] ?? '')) ?? '';

            $want = $symbol === 'auto'
                ? ($gtin12 !== '' ? 'upc' : 'sku')
                : $symbol;

            try {
                [$svg, $kind, $human] = match ($want) {
                    'upc'  => [Barcode::upcA($gtin12),    'UPC-A',    $gtin12],
                    'case' => [Barcode::gs1_128($gtin14), 'GS1-128',  '(01) ' . $gtin14],
                    default=> [Barcode::code128((string)$p['sku']), 'Code 128', (string)$p['sku']],
                };
            } catch (\InvalidArgumentException $e) {
                // Never print a symbol we could not build correctly. A label with a
                // wrong check digit scans as nothing, or worse as something else, and
                // it does that silently at the moment somebody is relying on it.
                $skipped[] = ['sku' => $p['sku'], 'reason' => $e->getMessage()];
                continue;
            }

            $rendered[] = $p + ['svg' => $svg, 'kind' => $kind, 'human' => $human];
        }

        return $this->view('products.labels', [
            'title'    => 'Print barcode labels',
            'products' => $rendered,
            'skipped'  => $skipped,
            'symbol'   => $symbol,
            'copies'   => $copies,
            'query'    => trim((string)($request->query('q') ?? '')),
        ]);
    }
}
