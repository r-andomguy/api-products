<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class CategoryService
{
    private \PDO $pdo;
    public function __construct() {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId, $lang  = 'en')
    {
        $query = "
            SELECT c.company_id,
                   (CASE WHEN 
                   ct.label IS NULL THEN c.title ELSE ct.label END) AS title,
                   c.active
            FROM category c
                LEFT JOIN category_translation ct ON c.id = ct.category_id AND ct.lang_code IN('{$lang}')
            WHERE c.company_id = {$this->getCompanyFromAdminUser($adminUserId)}
        ";
        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
    }

    public function getOne($adminUserId, $categoryId, $lang  = 'en')
    {
        $query = "
            SELECT c.company_id,
                   (CASE WHEN 
                   ct.label IS NULL THEN c.title ELSE ct.label END) AS title,
                   c.active
            FROM category c
                LEFT JOIN category_translation ct ON c.id = ct.category_id AND ct.lang_code = '{$lang}'
            WHERE c.active = 1
            AND c.company_id = {$this->getCompanyFromAdminUser($adminUserId)}
            AND c.id = {$categoryId}
        ";

        $stm = $this->pdo->prepare($query);
        $stm->execute();

        return $stm;
    }

    public function getProductCategory($productId)
    {
        $query = "
            SELECT c.id
            FROM category c
            INNER JOIN product_category pc
                ON pc.cat_id = c.id
            WHERE pc.product_id = {$productId}
        ";

        $stm = $this->pdo->prepare($query);

        $stm->execute();

        return $stm;
    }

    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO category (
                company_id,
                title,
                active
            ) VALUES (
                {$this->getCompanyFromAdminUser($adminUserId)},
                '{$body['title']}',
                {$body['active']}
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $active = (int)$body['active'];

        $stm = $this->pdo->prepare("
            UPDATE category
            SET title = '{$body['title']}',
                active = {$active}
            WHERE id = {$id}
            AND company_id = {$this->getCompanyFromAdminUser($adminUserId)}
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE
            FROM category
            WHERE id = {$id}
            AND company_id = {$this->getCompanyFromAdminUser($adminUserId)}
        ");

        return $stm->execute();
    }

    private function getCompanyFromAdminUser($adminUserId)
    {
        $query = "
            SELECT company_id
            FROM admin_user
            WHERE id = {$adminUserId}
        ";

        $stm = $this->pdo->prepare($query);
        
        $stm->execute();

        return $stm->fetch()->company_id;
    }

    public function insertTranslations($categoryId, array $translations): array
    {
        $codes = array_column($translations, 'lang_code');

        if (count($codes) !== count(array_unique($codes))) {
            return [
                'success' => false,
                'error' => 'Existem traduções repetidas no envio. Nenhuma foi salva.'
            ];
        }

        $this->pdo->beginTransaction();

        foreach ($translations as $translation) {
            $lang = $translation['lang_code'];
            $label = $translation['label'];
            $query = "INSERT INTO category_translation (category_id, lang_code, label) VALUES ({$categoryId}, '{$lang}', '{$label}')";
            $this->pdo->prepare($query)->execute();
        }

        $this->pdo->commit();
        return ['success' => true];
    }
    
}
