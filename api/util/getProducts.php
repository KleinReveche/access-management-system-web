<?php

use api\models\JsonResponse;
use api\models\RequestType;
use api\models\ResponseType;

function getProducts(string $loginToken): string
{
    global $pdo;
    $login = verifyLoginToken($loginToken);

    if ($login[0] === true) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM products');
            $stmt->execute();
            $products = $stmt->fetchAll();
            $products = array_filter($products, function($product) {
                return $product['deleted_at'] === null;
            });

            $products_list = [];
            foreach ($products as $product) {
                $products_list[] = array(
                    'id' => (int)$product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'added_by' => $product['added_by'],
                    'category_id' => (int)$product['category_id'],
                    'image' => $product['image'],
                    'created_at' => $product['created_at'],
                    'updated_at' => $product['updated_at'],
                    'updated_by' => $product['updated_by'] ?? null,
                    'deleted_by' => $product['deleted_by'] ?? null,
                    'deleted_at' => $product['deleted_at'] ?? null
                );
            }

            return (new JsonResponse(
                RequestType::GET_PRODUCTS,
                ResponseType::SUCCESS,
                json_encode($products_list)
            ))->toJson();

        } catch (Exception $e) {
            return (new JsonResponse(
                RequestType::GET_PRODUCTS,
                ResponseType::ERROR,
                "Database error: " . $e->getMessage()
            ))->toJson();
        }
    } else {
        return (new JsonResponse(
            RequestType::GET_PRODUCTS,
            ResponseType::ERROR,
            "Invalid login token."
        ))->toJson();
    }
}