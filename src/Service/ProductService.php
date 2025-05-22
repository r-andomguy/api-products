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

    public function getAll($adminUserId, $filters, $orderBy, $lang = 'en')
    {
        $query = "
            SELECT p.*, 
                  (CASE WHEN ct.label IS NULL THEN c.title ELSE ct.label END) AS category
            FROM product p
                INNER JOIN product_category pc ON p.id = pc.product_id 
                INNER JOIN category c ON pc.cat_id = c.id 
                LEFT JOIN category_translation ct ON c.id = ct.category_id AND ct.lang_code IN ('{$lang}')
            WHERE p.company_id = {$adminUserId}
        ";

        if($filters !== null) {
            $query .= $filters;
        } 
        
        if($orderBy !== null) {
            $query .= $orderBy;
        }
        
        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
    }

    public function getOne($id)
    {
        $stm = $this->pdo->prepare("
            SELECT *
            FROM product
            WHERE id = {$id}
        ");
        $stm->execute();

        return $stm;
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
                {$body['company_id']},
                '{$body['title']}',
                {$body['price']},
                {$body['active']}
            )
        ");
        if (!$stm->execute())
            return false;

        $productId = $this->pdo->lastInsertId();

        $stm = $this->pdo->prepare("
            INSERT INTO product_category (
                product_id,
                cat_id
            ) VALUES (
                {$productId},
                {$body['category_id']}
            );
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$productId},
                {$adminUserId},
                'create'
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            UPDATE product
            SET company_id = {$body['company_id']},
                title = '{$body['title']}',
                price = {$body['price']},
                active = {$body['active']}
            WHERE id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = {$body['category_id']}
            WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'update'
            )
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE FROM product_category WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;
        
        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'delete'
            )
        ");

        return $stm->execute();
    }

    public function getLog($id)
    {
        $stm = $this->pdo->prepare("
            SELECT pl.*,
                   admin.name AS admin_name
            FROM product_log pl
                LEFT JOIN admin_user AS admin ON pl.admin_user_id = admin.id 
            WHERE pl.product_id = {$id}
        ");
        $stm->execute();

        return $stm;
    }

    public function getLastLog($id)
    {
        $stm = $this->pdo->prepare("
            SELECT pl.*,
                   admin.name AS admin_name
            FROM product_log pl
                LEFT JOIN admin_user AS admin ON pl.admin_user_id = admin.id 
            WHERE pl.product_id = {$id}
            ORDER BY pl.timestamp DESC 
            LIMIT 1
        ");
        $stm->execute();

        return $stm;
    }

    public function setOrderBy($direction = 'DESC'): string
    {
        $allowed = ['ASC', 'DESC'];
        $dir = strtoupper($direction);

        if (!in_array($dir, $allowed)) {
            $dir = 'DESC'; 
        }

        return " ORDER BY p.created_at $dir";
    }
    
    public function setFilter(string $key, $value): string
    {
        $filter = '';

        switch ($key) {
            case 'active':
                $value = (int) $value;
                $filter = 'AND p.active = ' . $value;
                break;

            case 'category':
                $value = (int) $value;
                $filter = ' AND pc.cat_id = ' . $value;
                break;

            default:
            break;
        }

        return $filter;
    }

    public function getCategoryTranslation($categoryId, $lang = 'en') 
    {
        $query = "SELECT (CASE WHEN ct.label IS NULL THEN c.title ELSE ct.label END) AS title 
                  FROM category_translation ct
                    LEFT JOIN category c ON ct.category_id = c.id
                  WHERE ct.category_id = {$categoryId} AND ct.lang_code IN ('{$lang}')"; 
        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return  $stm->fetchColumn();
    }
}
