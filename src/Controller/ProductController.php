<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Model\Product;
use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController
{
    private ProductService $service;
    private CategoryService $categoryService;

    public function __construct()
    {
        $this->service = new ProductService();
        $this->categoryService = new CategoryService();
    }

    public function getAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];
        $queryParams = $request->getQueryParams();
        $lang = $request->getQueryParams()['lang'] ?? 'en';
        $filters = null;
        $orderBy = null;

        if (isset($queryParams['active'])) {
          $filters = $this->service->setFilter('active', $queryParams['active']) . ' ';
        }

        if (isset($queryParams['category'])) {
            $filters .= $this->service->setFilter('category', $queryParams['category']) . ' ';
        }

        if (isset($queryParams['created_at'])) {
            $orderBy = $this->service->setOrderBy($queryParams['created_at']);
        }

        $stm = $this->service->getAll($adminUserId, $filters, $orderBy,strtolower($lang));
        $response->getBody()->write(json_encode($stm->fetchAll()));
        return $response->withStatus(200);
    }

    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $stock = $request->getQueryParams()['stock'] ?? null;
        $adminUserId = $request->getHeader('admin_user_id')[0];
        $lang = $request->getQueryParams()['lang'] ?? 'en';
        $data = [];

        $stm = $this->service->getOne($args['id'], (int) $stock);
        $dataProduct = $stm->fetch();

        if(!$dataProduct) {
            return $response->withStatus(404, 'Erro ao buscar produto.');
        }

        $product = Product::hydrateByFetch($dataProduct);
        $productCategories = $this->categoryService->getProductCategory($product->id)->fetchAll();
        
        if($productCategories) {
            foreach($productCategories as $category) {
                $fetchedCategory = $this->categoryService->getOne($adminUserId, $category->id)->fetch();        
                $productClone = clone $product;
                $productClone->setCategory($fetchedCategory->title);
                
                $categoryTitle = $productClone->title;

                if($lang) {
                    $categoryTitle = $this->service->getCategoryTranslation($category->id, $lang);
                }
                
                $data[] = [
                    'category' => $categoryTitle,
                    'id' => $productClone->id,
                    'companyId' => $productClone->companyId,
                    'title' => $productClone->title,
                    'price' => $productClone->price,
                    'stock' => $productClone->stock,
                ];
            }
        }
        
        $response->getBody()->write(json_encode(value: $data));
        return $response->withStatus(200);
    }

    public function getLastLog(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $stm = $this->service->getLastLog($args['id']);

        $response->getBody()->write(json_encode($stm->fetch()));
        return $response->withStatus(200);
    }

    public function insertOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->insertOne($body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function updateOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->updateOne($args['id'], $body, $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function deleteOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        if ($this->service->deleteOne($args['id'], $adminUserId)) {
            return $response->withStatus(200);
        } else {
            return $response->withStatus(404);
        }
    }

    public function updateProductStock(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        $body = $request->getParsedBody();
        $stock = $this->service->updateProductStock($args['id'], $body['stock']);

        if(!$stock) {
             return $response->withStatus(404, 'Erro ao atualizar estoque do produto.');
        }

        return $response->withStatus(200, 'Estoque atualizado com sucesso.');
    }

}
