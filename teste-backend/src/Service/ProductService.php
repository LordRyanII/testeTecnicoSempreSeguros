<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class ProductService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId)
    {
        $query = "
        SELECT p.*, c.title as category
        FROM product p
        INNER JOIN product_category pc ON pc.product_id = p.id
        INNER JOIN category c ON c.id = pc.cat_id
        WHERE p.company_id = {$adminUserId}
    ";

        $stm = $this->pdo->prepare($query);
        $stm->execute();

        $results = $stm->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

    public function getFiltered(array $queryParams)
    {
        $sql = "SELECT p.*, c.title as category FROM product p
            INNER JOIN product_category pc ON pc.product_id = p.id
            INNER JOIN category c ON c.id = pc.cat_id
            WHERE p.company_id = :company_id";

        $bindings = ['company_id' => $queryParams['company_id']];
        $conditions = [];

        if (isset($queryParams['active'])) {
            $conditions[] = "p.active = :active";
            $bindings['active'] = $queryParams['active'];
        }

        if (isset($queryParams['category'])) {
            $conditions[] = "c.title = :category";
            $bindings['category'] = $queryParams['category'];
        }

        if (isset($queryParams['created'])) {
            $conditions[] = "DATE(p.created_at) = :created";
            $bindings['created'] = $queryParams['created'];
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(" AND ", $conditions);
        }

        $orderBy = "p.created_at DESC";
        if (isset($queryParams['sort']) && $queryParams['sort'] == 'asc') {
            $orderBy = "p.created_at ASC";
        }

        $sql .= " ORDER BY " . $orderBy;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($products)) {
            throw new \Exception("Nenhum produto encontrado com os filtros especificados", 404);
        }

        return $products;
    }


    public function getOne($id)
    {
        $stm = $this->pdo->prepare("
        SELECT p.*, c.title AS category
        FROM product p
        LEFT JOIN product_category pc ON pc.product_id = p.id
        LEFT JOIN category c ON c.id = pc.cat_id
        WHERE p.id = :id
    ");

        $stm->bindParam(':id', $id);
        $stm->execute();

        $result = $stm->fetchAll(\PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        $categories = [];
        foreach ($result as $row) {
            $categories[] = $row['category'];
        }
        $uniqueCategories = array_unique($categories);

        $product = [
            'id' => $result[0]['id'],
            'company_id' => $result[0]['company_id'],
            'title' => $result[0]['title'],
            'price' => $result[0]['price'],
            'active' => $result[0]['active'],
            'created_at' => $result[0]['created_at'],
            'categories' => $uniqueCategories,
        ];

        if (count($uniqueCategories) > 1) {
            unset($product['category']);
        } else {
            $product['category'] = reset($uniqueCategories);
        }

        return $product;
    }



    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO product (
                company_id,
                title,
                price,
                active
            ) VALUES (
                :company_id,
                :title,
                :price,
                :active
            )
        ");
        $stm->bindParam(':company_id', $body['company_id']);
        $stm->bindParam(':title', $body['title']);
        $stm->bindParam(':price', $body['price']);
        $stm->bindParam(':active', $body['active']);

        if (!$stm->execute())
            return false;

        $productId = $this->pdo->lastInsertId();

        $stm = $this->pdo->prepare("
            INSERT INTO product_category (
                product_id,
                cat_id
            ) VALUES (
                :product_id,
                :category_id
            )
        ");
        $stm->bindParam(':product_id', $productId);
        $stm->bindParam(':category_id', $body['category_id']);

        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                :product_id,
                :admin_user_id,
                'create'
            )
        ");
        $stm->bindParam(':product_id', $productId);
        $stm->bindParam(':admin_user_id', $adminUserId);

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            UPDATE product
            SET company_id = :company_id,
                title = :title,
                price = :price,
                active = :active
            WHERE id = :id
        ");
        $stm->bindParam(':company_id', $body['company_id']);
        $stm->bindParam(':title', $body['title']);
        $stm->bindParam(':price', $body['price']);
        $stm->bindParam(':active', $body['active']);
        $stm->bindParam(':id', $id);

        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = :category_id
            WHERE product_id = :product_id
        ");
        $stm->bindParam(':category_id', $body['category_id']);
        $stm->bindParam(':product_id', $id);

        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                :product_id,
                :admin_user_id,
                'update'
            )
        ");
        $stm->bindParam(':product_id', $id);
        $stm->bindParam(':admin_user_id', $adminUserId);

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE FROM product_category WHERE product_id = :id
        ");
        $stm->bindParam(':id', $id);
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = :id");
        $stm->bindParam(':id', $id);
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                :product_id,
                :admin_user_id,
                'delete'
            )
        ");
        $stm->bindParam(':product_id', $id);
        $stm->bindParam(':admin_user_id', $adminUserId);

        return $stm->execute();
    }

    public function getLog($id)
    {
        $stm = $this->pdo->prepare("
        SELECT pl.*, pl.timestamp AS log_timestamp, au.name AS user_name, p.title AS product_name
        FROM product_log pl
        LEFT JOIN admin_user au ON pl.admin_user_id = au.id
        LEFT JOIN product p ON pl.product_id = p.id
        WHERE (:id = 0 OR pl.admin_user_id = :id)");
        $stm->bindParam(':id', $id);
        $stm->execute();

        return $stm;
    }
}
