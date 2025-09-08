<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'No product id']);
    exit;
}

$product_id = intval($_POST['product_id']);
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product id']);
    exit;
}

// เพิ่มสินค้าลงตะกร้า
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id] = 0;
}
$_SESSION['cart'][$product_id] += $quantity;

// นับจำนวนสินค้าในตะกร้าทั้งหมด
$cart_count = 0;
foreach ($_SESSION['cart'] as $qty) {
    $cart_count += $qty;
}

echo json_encode(['success' => true, 'cart_count' => $cart_count]); 