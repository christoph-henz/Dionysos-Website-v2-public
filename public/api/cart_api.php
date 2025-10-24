<?php
require_once '../../vendor/autoload.php';

// Sichere Session-Initialisierung
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    
    // Validate JSON
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }

    if (!isset($data['article_id']) && !isset($data['cart_key'])) {
        throw new Exception('Missing required parameters: article_id or cart_key');
    }

    if (!isset($data['delta'])) {
        throw new Exception('Missing required parameter: delta');
    }

    $cartController = new \Dionysosv2\Controller\CartController();
    
    // Check if we're using cart_key or article_id
    if (isset($data['cart_key'])) {
        // Handle by cart key (for items with options)
        $result = $cartController->handleQuantityChangeByKey(
            $data['cart_key'], 
            (int)$data['delta']
        );
    } else {
        // Handle by article_id
        $options = isset($data['options']) ? $data['options'] : [];
        
        if (!empty($options)) {
            // Handle cart with options
            $result = $cartController->handleQuantityChangeWithOptions(
                (int)$data['article_id'], 
                (int)$data['delta'],
                $options
            );
        } else {
            // Handle cart without options
            $result = $cartController->handleQuantityChange(
                (int)$data['article_id'], 
                (int)$data['delta']
            );
        }
    }

    if (!is_array($result)) {
        throw new Exception('Invalid result format from CartController');
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}