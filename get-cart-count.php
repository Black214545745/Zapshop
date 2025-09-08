<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'count' => 0];

if (isset($_SESSION['user_id']) && isset($_SESSION['cart'])) {
    $total_items = 0;
    foreach ($_SESSION['cart'] as $quantity) {
        $total_items += $quantity;
    }
    
    $response = [
        'success' => true,
        'count' => $total_items
    ];
}

echo json_encode($response);
?>
