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
    
    if (!empty($product_id)) {
        // ตรวจสอบว่าสินค้ามีอยู่ในตะกร้าหรือไม่
        if (isset($_SESSION['cart'][$product_id])) {
            // ตรวจสอบข้อมูลสินค้าเพื่อบันทึก Activity Log
            $conn = getConnection();
            $query = "SELECT id, name FROM products WHERE id = $1";
            $result = pg_query_params($conn, $query, [$product_id]);
            
            $product_name = 'Unknown Product';
            if ($result && pg_num_rows($result) > 0) {
                $product = pg_fetch_assoc($result);
                $product_name = $product['name'];
            }
            
            // ลบสินค้าออกจากตะกร้า
            unset($_SESSION['cart'][$product_id]);
            
            // บันทึก Activity Log (ถ้ามีฟังก์ชัน)
            try {
                if (function_exists('logActivity')) {
                    logActivity($_SESSION['user_id'], 'cart_remove', 'Removed from cart: ' . $product_name, 'products', $product_id);
                }
            } catch (Exception $e) {
                // ไม่ critical ถ้า log ไม่สำเร็จ
                error_log('Log activity failed: ' . $e->getMessage());
            }
            
            pg_close($conn);
            echo json_encode(['success' => true, 'message' => 'ลบสินค้าออกจากตะกร้าแล้ว']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบสินค้าในตะกร้า']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'รหัสสินค้าไม่ถูกต้อง']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>