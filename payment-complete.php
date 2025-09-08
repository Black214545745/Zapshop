<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit();
}

include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = getConnection();
        
        // รับข้อมูลการชำระเงิน
        $payment_method = $_POST['payment_method'] ?? '';
        $transaction_id = $_POST['transaction_id'] ?? '';
        $order_id = $_POST['order_id'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $currency = $_POST['currency'] ?? 'THB';
        $bank = $_POST['bank'] ?? null;
        $usd_amount = $_POST['usd_amount'] ?? null;
        
        // เริ่ม Transaction
        pg_query($conn, "BEGIN");
        
        // อัปเดตสถานะการชำระเงิน
        $update_payment = "UPDATE customer_payments 
                           SET payment_status = 'completed', 
                               completed_at = NOW(),
                               additional_data = $1
                           WHERE transaction_id = $2";
        
        $additional_data = json_encode([
            'bank' => $bank,
            'usd_amount' => $usd_amount,
            'completion_time' => date('Y-m-d H:i:s')
        ]);
        
        $update_result = pg_query_params($conn, $update_payment, [$additional_data, $transaction_id]);
        
        if (!$update_result) {
            throw new Exception("Error updating payment status: " . pg_last_error($conn));
        }
        
        // อัปเดตสถานะคำสั่งซื้อ
        $update_order = "UPDATE orders 
                         SET status = 'paid', 
                             payment_completed_at = NOW()
                         WHERE id = $1";
        
        $order_result = pg_query_params($conn, $update_order, [$order_id]);
        
        if (!$order_result) {
            throw new Exception("Error updating order status: " . pg_last_error($conn));
        }
        
        // บันทึก Activity Log
        $activity_description = "Payment completed: {$payment_method} - {$amount} {$currency}";
        if ($bank) {
            $activity_description .= " via {$bank}";
        }
        
        logActivity($_SESSION['user_id'], 'payment_completed', $activity_description, 'payments', $transaction_id);
        
        // สร้าง Notification
        createNotification($_SESSION['user_id'], 'payment_success', 'การชำระเงินของคุณเสร็จสิ้นแล้ว', 'payments', $transaction_id);
        
        // Commit Transaction
        pg_query($conn, "COMMIT");
        
        // ส่งข้อมูลกลับ
        echo json_encode([
            'success' => true,
            'message' => 'การชำระเงินเสร็จสิ้นแล้ว',
            'transaction_id' => $transaction_id,
            'order_id' => $order_id,
            'payment_method' => $payment_method,
            'amount' => $amount,
            'currency' => $currency
        ]);
        
    } catch (Exception $e) {
        // Rollback Transaction
        if (isset($conn)) {
            pg_query($conn, "ROLLBACK");
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    } finally {
        if (isset($conn)) {
            pg_close($conn);
        }
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
