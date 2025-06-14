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

        if ($filters !== null) {
            $query .= $filters;
        }

        if ($orderBy !== null) {
            $query .= $orderBy;
        }

        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
    }

    public function getOne($id, $stock)
    {
        $query = "
            SELECT *
            FROM product
            WHERE id = {$id}
        ";

        if ($stock !== null) {
            $query .= " AND stock >= {$stock}";
        }

        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
    }

    public function getComments($productId)
    {
        $query = "SELECT pc.*, 
                         u.name AS user_name,
                  (SELECT COUNT(*) FROM comment_likes WHERE comment_id = pc.id) AS likes
                  FROM product_comments pc
                    JOIN admin_user u ON u.id = pc.user_id
                  WHERE pc.product_id = {$productId}
                ORDER BY pc.created_at ASC
            ";
        $stm = $this->pdo->prepare($query);
        $stm->execute();
        $all = $stm->fetchAll();

        $byParent = [];
        foreach ($all as $comment) {
            $byParent[$comment->parent_id][] = $comment;
        }

        $buildTree = function ($parentId) use (&$buildTree, $byParent) {
            $allComments = [];
            if (isset($byParent[$parentId])) {
                foreach ($byParent[$parentId] as $comment) {
                    $comment->replies = $buildTree($comment->id);
                    $allComments[] = $comment;
                }
            }

            return $allComments;
        };

        return $buildTree(null);
    }

    public function insertOne($body, $adminUserId)
    {
        $stock = $body['stock'] ?? 0;
        $stm = $this->pdo->prepare("
            INSERT INTO product (
                company_id,
                title,
                price,
                active,
                stock
            ) VALUES (
                {$body['company_id']},
                '{$body['title']}',
                {$body['price']},
                {$body['active']},
                {$stock}
            )
        ");

        if (!$stm->execute()) {
            return false;
        }

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
        if (!$stm->execute()) {
            return false;
        }

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

    public function insertComment($body, $userId)
    {
        $parentId = $body['parentId'] ?? null;

        if ($parentId !== null && !$this->hasParentId($parentId)) {
            $parentId = null;
        }

        $query = "INSERT INTO product_comments (
                product_id, 
                user_id, 
                content, 
                parent_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?)";

        $stm = $this->pdo->prepare($query);

        $stm->execute([
            $body['productId'],
            $userId,
            $body['content'],
            $parentId,
            $body['createdAt']
        ]);

        return $stm;
    }

    public function insertCommentLike($body, $commentId)
    {
        $query = "INSERT INTO comment_likes (
                comment_id, 
                user_id, 
                created_at
            ) VALUES (
                {$commentId},
                {$body['userId']},
                '{$body['createdAt']}'
            )";

        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
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
        if (!$stm->execute()) {
            return false;
        }

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = {$body['category_id']}
            WHERE product_id = {$id}
        ");
        if (!$stm->execute()) {
            return false;
        }

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
        if (!$stm->execute()) {
            return false;
        }

        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
        if (!$stm->execute()) {
            return false;
        }

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

    public function deleteComment($idComment, $userId)
    {
        if (!$this->isCommentAuthor($idComment, $userId)) {
            return false;
        }

        $stm = $this->pdo->prepare("
            DELETE FROM product_comments WHERE id = {$idComment} AND user_id = {$userId}
        ");

        if (!$stm->execute()) {
            return false;
        }

        $stm->execute();
        return true;
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

    public function updateProductStock(int $id, int $stock)
    {

        $product = $this->getOne($id, null)->fetch();

        if (!$product) {
            return false;
        }

        $query = "
            UPDATE product
            SET stock = {$stock}
            WHERE id = {$id}
        ";
        $stm = $this->pdo->prepare($query);

        if (!$stm->execute()) {
            return false;
        }

        return true;
    }

    public function hasParentId($id)
    {
        $query = "SELECT * FROM product_comments WHERE id = {$id}";

        $stm = $this->pdo->prepare($query);
        $stm->execute();

        if (empty($stm->fetch())) {
            return false;
        }

        return true;
    }

    public function isCommentAuthor($id, $userId)
    {
        $query = "SELECT * FROM product_comments WHERE id = {$id} AND user_id = {$userId}";

        var_dump($query);
        $stm = $this->pdo->prepare($query);
        $stm->execute();

        if (empty($stm->fetch())) {
            return false;
        }

        return true;
    }
}
