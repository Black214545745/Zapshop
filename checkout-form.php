<?php
session_start();
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $fullname = $_POST['fullname'] ?? '';
    $tel = $_POST['tel'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $grand_total = $_POST['grand_total'] ?? 0;

    // ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
    if (empty($_SESSION['cart'])) {
        $_SESSION['message'] = "ไม่มีสินค้าในตะกร้า!";
        $_SESSION['message_type'] = "error";
        header("Location: cart.php");
        exit();
    }

    try {
        $conn = getConnection();
        $activity_logs = []; // เก็บข้อมูล Activity Log
        
        // เริ่ม Transaction
        pg_query($conn, "BEGIN");
        
        // สร้าง Order ID
        $order_id = generateUUID();
        
        // บันทึกข้อมูลคำสั่งซื้อในตาราง orders
        $order_query = "INSERT INTO orders (id, user_id, fullname, tel, email, address, grand_total, payment_method, order_date, status) 
                       VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), 'pending')";
        
        $order_params = [
            $order_id,
            $_SESSION['user_id'],
            $fullname,
            $tel,
            $email,
            $address,
            $grand_total,
            $payment_method
        ];
        
        $order_result = pg_query_params($conn, $order_query, $order_params);
        
        if (!$order_result) {
            throw new Exception("Error creating order: " . pg_last_error($conn));
        }

        // บันทึกสินค้าลงในตาราง order_details
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            // ดึงข้อมูลสินค้า (ชื่อ, ราคา) จากฐานข้อมูล
            $product_query = "SELECT name, price, current_stock FROM products WHERE id = $1";
            $product_result = pg_query_params($conn, $product_query, [$product_id]);
            
            if ($product_result && pg_num_rows($product_result) > 0) {
                $product = pg_fetch_assoc($product_result);
                $product_name = $product['name'];
                $price = $product['price'];
                $total = $price * $quantity;
                
                // ตรวจสอบสต็อก
                if ($product['current_stock'] < $quantity) {
                    throw new Exception("สินค้า {$product_name} มีสต็อกไม่เพียงพอ");
                }

                // บันทึกสินค้าในตาราง order_details
                $order_details_query = "INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total) 
                                      VALUES ($1, $2, $3, $4, $5, $6)";
                
                $order_details_params = [
                    $order_id,
                    $product_id,
                    $product_name,
                    $price,
                    $quantity,
                    $total
                ];
                
                $order_details_result = pg_query_params($conn, $order_details_query, $order_details_params);
                
                if (!$order_details_result) {
                    throw new Exception("Error creating order details: " . pg_last_error($conn));
                }
                
                // อัปเดตสต็อกสินค้า
                $new_stock = $product['current_stock'] - $quantity;
                $update_stock_query = "UPDATE products SET current_stock = $1 WHERE id = $2";
                $update_stock_result = pg_query_params($conn, $update_stock_query, [$new_stock, $product_id]);
                
                if (!$update_stock_result) {
                    throw new Exception("Error updating stock: " . pg_last_error($conn));
                }
                
                // เก็บข้อมูลสำหรับ Activity Log (ไม่บันทึกตอนนี้เพื่อไม่ให้ปิดการเชื่อมต่อ)
                $activity_logs[] = [
                    'user_id' => $_SESSION['user_id'],
                    'action' => 'order_created',
                    'description' => "Order created: {$product_name} x {$quantity}",
                    'table_name' => 'orders',
                    'record_id' => $order_id
                ];
            }
        }
        
        // Commit Transaction
        pg_query($conn, "COMMIT");
        
        // ล้างตะกร้า
        unset($_SESSION['cart']);
        
        // บันทึก Order ID ใน Session สำหรับหน้าขอบคุณ
        $_SESSION['order_id'] = $order_id;
        
        // บันทึก Activity Log หลังจาก COMMIT สำเร็จ
        foreach ($activity_logs as $log) {
            logActivity($log['user_id'], $log['action'], $log['description'], $log['table_name'], $log['record_id']);
        }
        
        // สร้าง Notification
        createNotification($_SESSION['user_id'], 'order_success', 'คำสั่งซื้อของคุณได้รับการยืนยันแล้ว', 'orders', $order_id);
        
        // ไปยังหน้าขอบคุณ
        $_SESSION['message'] = "ขอบคุณที่สั่งซื้อ! คำสั่งซื้อของคุณได้รับการยืนยันแล้ว";
        $_SESSION['message_type'] = "success";
        header("Location: checkout-success.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback Transaction
        if (isset($conn)) {
            pg_query($conn, "ROLLBACK");
            // ไม่ต้องปิดการเชื่อมต่อเพราะอาจจะปิดแล้วจาก logActivity()
        }
        
        $_SESSION['message'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: checkout.php");
        exit();
    }
} else {
    // หากไม่ใช่ POST request ให้กลับไปหน้า checkout
    header("Location: checkout.php");
    exit();
}
?>
