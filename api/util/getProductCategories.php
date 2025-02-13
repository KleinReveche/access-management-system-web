<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

function getProductCategories(string $loginToken): string
{
    $login = verifyLoginToken($loginToken);

    if ($login[0] === true) {
        try {
            global $pdo;
            $stmt = $pdo->prepare('SELECT * FROM product_categories');
            $stmt->execute();
            $categories = $stmt->fetchAll();

            $categories_list = [];
            foreach ($categories as $category) {
                $categories_list[] = array(
                    'id' => (int)$category['id'],
                    'name' => $category['name'],
                    'added_by' => $category['added_by'],
                    'created_at' => $category['created_at'],
                    'updated_at' => $category['updated_at'],
                    'updated_by' => $category['updated_by'] ?? null,
                    'deleted_by' => $category['deleted_by'] ?? null,
                    'deleted_at' => $category['deleted_at'] ?? null
                );
            }

            return (new JsonResponse(
                RequestType::GET_PRODUCT_CATEGORIES,
                ResponseType::SUCCESS,
                json_encode($categories_list)
            ))->toJson();

        } catch (Exception $e) {
            return (new JsonResponse(
                RequestType::GET_PRODUCT_CATEGORIES,
                ResponseType::ERROR,
                "Database error: " . $e->getMessage()
            ))->toJson();
        }
    } else {
        return (new JsonResponse(
            RequestType::GET_PRODUCT_CATEGORIES,
            ResponseType::ERROR,
            "Invalid login token."
        ))->toJson();
    }
}
{

}