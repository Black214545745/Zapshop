<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['id']) ? $_POST['id'] : '';
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    
    if (!empty($product_id) && $quantity > 0) {
        // ตรวจสอบว่าสินค้ามีอยู่จริงและมีสต็อกหรือไม่
        $conn = getConnection();
        $query = "SELECT id, name, current_stock FROM products WHERE id = $1";
        $result = pg_query_params($conn, $query, [$product_id]);
        
        if ($result && pg_num_rows($result) > 0) {
            $product = pg_fetch_assoc($result);
            
            // ตรวจสอบสต็อก
            if ($quantity > $product['current_stock']) {
                echo json_encode(['success' => false, 'message' => 'จำนวนสินค้าเกินสต็อกที่มี']);
                pg_close($conn);
                exit();
            }
            
            // อัปเดตจำนวนในตะกร้า
            $_SESSION['cart'][$product_id] = $quantity;
            
            // บันทึก Activity Log (ถ้ามีฟังก์ชัน)
            try {
                if (function_exists('logActivity')) {
                    logActivity($_SESSION['user_id'], 'cart_update', 'Updated cart quantity for: ' . $product['name'], 'products', $product_id);
                }
            } catch (Exception $e) {
                // ไม่ critical ถ้า log ไม่สำเร็จ
                error_log('Log activity failed: ' . $e->getMessage());
            }
            
            pg_close($conn);
            echo json_encode(['success' => true, 'message' => 'อัปเดตจำนวนสินค้าแล้ว']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้าที่ระบุ']);
            pg_close($conn);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>