<?php

namespace Contatoseguro\TesteBackend\Controller;


use Contatoseguro\TesteBackend\Model\Product;
use Contatoseguro\TesteBackend\Service\CategoryService;
use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;



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

        $products = $this->service->getAll($adminUserId);
        $response->getBody()->write(json_encode($products));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }


    public function getOne(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $product = $this->service->getOne($id);

        if (!$product) {
            return $response->withStatus(404)
                ->withBody(new \Slim\Psr7\Stream(fopen('php://temp', 'r+')))
                ->getBody()->write(json_encode(['error' => 'Product not found']));
        }

        try {
            if (is_object($product)) {
                $categories = [];
                foreach ($product->categories as $category) {
                    $categories[] = $category->title;
                }
                unset($product->category);

                $product->categories = $categories;
            }

            $response = new Response();
            $response->getBody()->write(json_encode($product));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $response->withStatus(500)
                ->withBody(new \Slim\Psr7\Stream(fopen('php://temp', 'r+')))
                ->getBody()->write(json_encode(['error' => 'Internal Server Error']));
        }
    }

    
    public function getFiltered(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeaderLine('admin_user_id');

        if (empty($adminUserId)) {
            $data = ['message' => 'Header admin_user_id não fornecido'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $queryParams = $request->getQueryParams();
        $queryParams['company_id'] = $adminUserId;

        try {
            $products = $this->service->getFiltered($queryParams);

            if (!empty($products)) {
                foreach ($products as &$product) {
                    if (isset($product['created_at'])) {
                        $product['created_at'] = date('d/m/Y H:i:s', strtotime($product['created_at']));
                    }
                }

                $response->getBody()->write(json_encode($products));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $data = ['message' => 'Nenhum produto encontrado com os filtros especificados'];
                $response->getBody()->write(json_encode($data));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        } catch (\Exception $e) {
            $data = ['message' => 'Erro ao processar a solicitação'];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
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
}
