<?php

namespace Contatoseguro\TesteBackend\Controller;

use Contatoseguro\TesteBackend\Service\ProductService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ReportController
{
    private ProductService $productService;

    public function __construct()
    {
        $this->productService = new ProductService();
    }

    public function generate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $adminUserId = $request->getHeader('admin_user_id')[0];

        try {
            $stm = $this->productService->getLog($adminUserId);

            if ($stm instanceof \PDOStatement) {
                $logs = $stm->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($logs)) {
                    $formattedLogs = [];
                    foreach ($logs as $log) {
                        $timestamp = strtotime($log['timestamp']);
                        $formattedTimestamp = date('d/m/Y H:i:s', $timestamp);
                        $status = ($log['action'] == 'create') ? 'Criacao' : (($log['action'] == 'update') ? 'Atualizacao' : 'Remocao');
                        $formattedLogs[] = [
                            'id' => $log['id'],
                            'id_usuario' => $log['admin_user_id'],
                            'Nome_usuario' => $log['user_name'],
                            $status => $formattedTimestamp,
                            'Id_produto' => $log['product_id'],
                            'Nome_do_Produto' => $log['product_name'],
                        ];
                    }

                    $response = new Response();
                    $response->getBody()->write(json_encode($formattedLogs));

                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(200);
                } else {
                    $response = new Response();
                    $response->getBody()->write(json_encode(["message" => "Nenhum log encontrado para o usuário com ID $adminUserId"]));

                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(404);
                }
            } else {
                $response = new Response();
                $response->getBody()->write(json_encode(["message" => "Erro ao obter logs do banco de dados."]));

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        } catch (\PDOException $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(["message" => "Erro ao executar consulta SQL: " . $e->getMessage()]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(["message" => "Erro inesperado: " . $e->getMessage()]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
