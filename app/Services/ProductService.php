<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;

class ProductService
{
    private ProductRepository $repo;

    public function __construct()
    {
        $this->repo = new ProductRepository();
    }

    public function list(int $page, int $perPage, string $search, string $brand, string $filter): array
    {
        $paginated = $this->repo->paginateWithBrand($page, $perPage, $search, $brand, $filter);
        $brands    = $this->repo->getAllBrands();

        return compact('paginated', 'brands', 'search', 'brand', 'filter');
    }

    public function show(int $id): array
    {
        $product = $this->repo->findWithBrand($id);
        if (!$product) {
            throw new \RuntimeException("Product $id not found");
        }
        $brands    = $this->repo->getAllBrands();
        $images    = $this->repo->getImages($id);
        $documents = $this->repo->getDocuments($id);

        // Primary image is the flagged one, else the first uploaded
        $primaryImage = null;
        foreach ($images as $img) {
            if ((int)$img['is_primary'] === 1) { $primaryImage = $img; break; }
        }
        if (!$primaryImage && !empty($images)) {
            $primaryImage = $images[0];
        }

        return compact('product', 'brands', 'images', 'documents', 'primaryImage');
    }

    public function update(int $id, array $post): void
    {
        $product = $this->repo->findWithBrand($id);
        if (!$product) {
            throw new \RuntimeException("Product $id not found");
        }
        $this->repo->update($id, $post);
    }

    public function listRawMaterials(int $page, int $perPage, string $search): array
    {
        return $this->repo->paginateRawMaterials($page, $perPage, $search);
    }

    public function getRawMaterial(int $id): array|false
    {
        $item = $this->repo->findWithBrand($id);
        if (!$item || $item['item_type'] !== 'raw_material') return false;
        return $item;
    }

    public function storeRawMaterial(array $post): int
    {
        return $this->repo->insertRawMaterial($post);
    }

    public function updateRawMaterial(int $id, array $post): void
    {
        $this->repo->update($id, array_merge($post, ['item_type' => 'raw_material']));
    }
}
