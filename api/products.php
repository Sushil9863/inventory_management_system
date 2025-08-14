<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_product':
            $id = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as category_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if ($product) {
                echo json_encode($product);
            } else {
                echo json_encode(['error' => 'Product not found']);
            }
            break;
            
        case 'search_products':
            $searchTerm = $_GET['term'] ?? '';
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.sku, p.price, i.quantity 
                FROM products p
                JOIN inventory i ON p.id = i.product_id
                WHERE p.name LIKE ? OR p.sku LIKE ?
                LIMIT 10
            ");
            $stmt->execute(["%$searchTerm%", "%$searchTerm%"]);
            $products = $stmt->fetchAll();
            echo json_encode($products);
            break;
            
        case 'get_products_by_category':
            $categoryId = $_GET['category_id'] ?? 0;
            
            // Validate category exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['error' => 'Category not found']);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.sku, i.quantity, p.price
                FROM products p
                JOIN inventory i ON p.id = i.product_id
                WHERE p.category_id = ?
                ORDER BY p.name
            ");
            $stmt->execute([$categoryId]);
            $products = $stmt->fetchAll();
            
            echo json_encode($products);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>