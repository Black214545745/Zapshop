<?php
/**
 * Payment Handler for ZapShop
 * จัดการการชำระเงินและสร้าง prepared statements ที่จำเป็น
 */

require_once 'config.php';
require_once 'promptpay_config.php';

/**
 * สร้าง prepared statements สำหรับการชำระเงิน
 */
function createPaymentStatements($conn) {
    try {
        // สร้างตาราง payments ถ้ายังไม่มี
        $createTableQuery = "
            CREATE TABLE IF NOT EXISTS payments (
                id SERIAL PRIMARY KEY,
                order_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_status VARCHAR(50) DEFAULT 'pending',
                payment_details TEXT,
                promptpay_id VARCHAR(50),
                qr_code_generated BOOLEAN DEFAULT FALSE,
                payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        pg_query($conn, $createTableQuery);
        
        // สร้าง prepared statement สำหรับเพิ่มข้อมูลการชำระเงิน
        $insertPaymentQuery = "
            INSERT INTO payments (order_id, user_id, payment_method, amount, payment_details, promptpay_id, qr_code_generated)
            VALUES ($1, $2, $3, $4, $5, $6, $7)
            RETURNING id
        ";
        
        pg_prepare($conn, 'insert_payment', $insertPaymentQuery);
        
        // สร้าง prepared statement สำหรับอัปเดตสถานะการชำระเงิน
        $updatePaymentStatusQuery = "
            UPDATE payments 
            SET payment_status = $1, updated_at = CURRENT_TIMESTAMP
            WHERE id = $2
        ";
        
        pg_prepare($conn, 'update_payment_status', $updatePaymentStatusQuery);
        
        // สร้าง prepared statement สำหรับดึงข้อมูลการชำระเงิน
        $getPaymentQuery = "
            SELECT * FROM payments WHERE id = $1
        ";
        
        pg_prepare($conn, 'get_payment', $getPaymentQuery);
        
        // สร้าง prepared statement สำหรับดึงข้อมูลการชำระเงินตาม order_id
        $getPaymentByOrderQuery = "
            SELECT * FROM payments WHERE order_id = $1
        ";
        
        pg_prepare($conn, 'get_payment_by_order', $getPaymentByOrderQuery);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating payment statements: " . $e->getMessage());
        return false;
    }
}

/**
 * บันทึกข้อมูลการชำระเงิน
 */
function savePayment($orderId, $userId, $paymentMethod, $amount, $paymentDetails = null, $promptpayId = null, $qrCodeGenerated = false) {
    try {
        $conn = getConnection();
        
        // สร้าง prepared statements ถ้ายังไม่มี
        createPaymentStatements($conn);
        
        // บันทึกข้อมูลการชำระเงิน
        $result = pg_execute($conn, 'insert_payment', [
            $orderId,
            $userId,
            $paymentMethod,
            $amount,
            $paymentDetails ? json_encode($paymentDetails) : null,
            $promptpayId,
            $qrCodeGenerated ? 'true' : 'false'
        ]);
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            $paymentId = $row['id'];
            
            // บันทึก log การสร้าง QR Code (ถ้าเป็น QR payment)
            if ($paymentMethod === 'qr_code' && $qrCodeGenerated) {
                logQRGeneration($orderId, $amount, $promptpayId);
            }
            
            pg_close($conn);
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'message' => 'บันทึกข้อมูลการชำระเงินสำเร็จ'
            ];
        } else {
            pg_close($conn);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูลการชำระเงิน'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error saving payment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * อัปเดตสถานะการชำระเงิน
 */
function updatePaymentStatus($paymentId, $status) {
    try {
        $conn = getConnection();
        
        // สร้าง prepared statements ถ้ายังไม่มี
        createPaymentStatements($conn);
        
        $result = pg_execute($conn, 'update_payment_status', [$status, $paymentId]);
        
        if ($result) {
            pg_close($conn);
            return [
                'success' => true,
                'message' => 'อัปเดตสถานะการชำระเงินสำเร็จ'
            ];
        } else {
            pg_close($conn);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตสถานะ'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error updating payment status: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ดึงข้อมูลการชำระเงิน
 */
function getPayment($paymentId) {
    try {
        $conn = getConnection();
        
        // สร้าง prepared statements ถ้ายังไม่มี
        createPaymentStatements($conn);
        
        $result = pg_execute($conn, 'get_payment', [$paymentId]);
        
        if ($result && pg_num_rows($result) > 0) {
            $payment = pg_fetch_assoc($result);
            pg_close($conn);
            return [
                'success' => true,
                'data' => $payment
            ];
        } else {
            pg_close($conn);
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลการชำระเงิน'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting payment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ดึงข้อมูลการชำระเงินตาม order_id
 */
function getPaymentByOrder($orderId) {
    try {
        $conn = getConnection();
        
        // สร้าง prepared statements ถ้ายังไม่มี
        createPaymentStatements($conn);
        
        $result = pg_execute($conn, 'get_payment_by_order', [$orderId]);
        
        if ($result && pg_num_rows($result) > 0) {
            $payment = pg_fetch_assoc($result);
            pg_close($conn);
            return [
                'success' => true,
                'data' => $payment
            ];
        } else {
            pg_close($conn);
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลการชำระเงินสำหรับคำสั่งซื้อนี้'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting payment by order: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * สร้างคำสั่งซื้อใหม่
 */
function createOrder($userId, $products, $totalAmount, $shippingInfo) {
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($userId)) {
            throw new Exception('User ID ไม่ถูกต้อง');
        }
        
        if (empty($products)) {
            throw new Exception('ไม่มีสินค้าในตะกร้า');
        }
        
        if ($totalAmount <= 0) {
            throw new Exception('จำนวนเงินไม่ถูกต้อง');
        }
        
        if (empty($shippingInfo['address']) || empty($shippingInfo['tel']) || empty($shippingInfo['email'])) {
            throw new Exception('ข้อมูลการจัดส่งไม่ครบถ้วน');
        }
        
        $conn = getConnection();
        if (!$conn) {
            throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
        }
        
        // เริ่ม transaction
        $beginResult = pg_query($conn, 'BEGIN');
        if (!$beginResult) {
            throw new Exception('ไม่สามารถเริ่ม transaction ได้: ' . pg_last_error($conn));
        }
        
        // ตรวจสอบและสร้าง/อัปเดตตาราง orders
        $checkOrdersTableQuery = "
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_name = 'orders'
            )
        ";
        
        $checkOrdersResult = pg_query($conn, $checkOrdersTableQuery);
        if (!$checkOrdersResult) {
            throw new Exception('ไม่สามารถตรวจสอบตาราง orders ได้: ' . pg_last_error($conn));
        }
        
        $ordersTableExists = pg_fetch_result($checkOrdersResult, 0, 0) === 't';
        
        if ($ordersTableExists) {
            // ตรวจสอบคอลัมน์ที่จำเป็น
            $checkColumnsQuery = "
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = 'orders'
            ";
            
            $checkColumnsResult = pg_query($conn, $checkColumnsQuery);
            if (!$checkColumnsResult) {
                throw new Exception('ไม่สามารถตรวจสอบคอลัมน์ได้: ' . pg_last_error($conn));
            }
            
            $existingColumns = [];
            while ($row = pg_fetch_assoc($checkColumnsResult)) {
                $existingColumns[] = $row['column_name'];
            }
            
            // เพิ่มคอลัมน์ที่ขาดหายไป
            if (!in_array('order_number', $existingColumns)) {
                $addOrderNumberQuery = "ALTER TABLE orders ADD COLUMN order_number VARCHAR(50)";
                $addOrderNumberResult = pg_query($conn, $addOrderNumberQuery);
                if (!$addOrderNumberResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ order_number ได้: ' . pg_last_error($conn));
                }
                
                // เพิ่ม unique constraint
                $addUniqueConstraintQuery = "ALTER TABLE orders ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number)";
                pg_query($conn, $addUniqueConstraintQuery);
            }
            
            if (!in_array('total_amount', $existingColumns)) {
                $addTotalAmountQuery = "ALTER TABLE orders ADD COLUMN total_amount DECIMAL(10,2)";
                $addTotalAmountResult = pg_query($conn, $addTotalAmountQuery);
                if (!$addTotalAmountResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ total_amount ได้: ' . pg_last_error($conn));
                }
            }
            
            if (!in_array('shipping_address', $existingColumns)) {
                $addShippingAddressQuery = "ALTER TABLE orders ADD COLUMN shipping_address TEXT";
                $addShippingAddressResult = pg_query($conn, $addShippingAddressQuery);
                if (!$addShippingAddressResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ shipping_address ได้: ' . pg_last_error($conn));
                }
            }
            
            if (!in_array('shipping_phone', $existingColumns)) {
                $addShippingPhoneQuery = "ALTER TABLE orders ADD COLUMN shipping_phone VARCHAR(20)";
                $addShippingPhoneResult = pg_query($conn, $addShippingPhoneQuery);
                if (!$addShippingPhoneResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ shipping_phone ได้: ' . pg_last_error($conn));
                }
            }
            
            if (!in_array('shipping_email', $existingColumns)) {
                $addShippingEmailQuery = "ALTER TABLE orders ADD COLUMN shipping_email VARCHAR(100)";
                $addShippingEmailResult = pg_query($conn, $addShippingEmailQuery);
                if (!$addShippingEmailResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ shipping_email ได้: ' . pg_last_error($conn));
                }
            }
            
            if (!in_array('order_status', $existingColumns)) {
                $addOrderStatusQuery = "ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) DEFAULT 'pending'";
                $addOrderStatusResult = pg_query($conn, $addOrderStatusQuery);
                if (!$addOrderStatusResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ order_status ได้: ' . pg_last_error($conn));
                }
            }
            
            if (!in_array('created_at', $existingColumns)) {
                $addCreatedAtQuery = "ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                $addCreatedAtResult = pg_query($conn, $addCreatedAtQuery);
                if (!$addCreatedAtResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ created_at ได้: ' . pg_last_error($conn));
                }
            }
            
            if (!in_array('updated_at', $existingColumns)) {
                $addUpdatedAtQuery = "ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
                $addUpdatedAtResult = pg_query($conn, $addUpdatedAtQuery);
                if (!$addUpdatedAtResult) {
                    throw new Exception('ไม่สามารถเพิ่มคอลัมน์ updated_at ได้: ' . pg_last_error($conn));
                }
            }
            
        } else {
            // สร้างตาราง orders ใหม่ (ใช้โครงสร้างเดิม)
            $createOrdersTableQuery = "
                CREATE TABLE orders (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    fullname VARCHAR(255) NOT NULL,
                    tel VARCHAR(20) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    address TEXT NOT NULL,
                    grand_total DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(50) DEFAULT 'pending',
                    payment_status VARCHAR(20) DEFAULT 'pending',
                    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            $createOrdersResult = pg_query($conn, $createOrdersTableQuery);
            if (!$createOrdersResult) {
                throw new Exception('ไม่สามารถสร้างตาราง orders ได้: ' . pg_last_error($conn));
            }
        }
        
        // สร้างตาราง order_items ถ้ายังไม่มี
        $createOrderItemsTableQuery = "
            CREATE TABLE IF NOT EXISTS order_items (
                id SERIAL PRIMARY KEY,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $createOrderItemsResult = pg_query($conn, $createOrderItemsTableQuery);
        if (!$createOrderItemsResult) {
            throw new Exception('ไม่สามารถสร้างตาราง order_items ได้: ' . pg_last_error($conn));
        }
        
        // สร้าง order number
        $orderNumber = 'ORD' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // เพิ่มข้อมูลคำสั่งซื้อ
        $insertOrderQuery = "
            INSERT INTO orders (
                user_id, 
                order_number, 
                total_amount, 
                shipping_address, 
                shipping_phone, 
                shipping_email,
                fullname,
                tel,
                email,
                address,
                grand_total,
                payment_method,
                payment_status,
                order_status,
                order_date,
                status
            )
            VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)
            RETURNING id
        ";
        
        $result = pg_query_params($conn, $insertOrderQuery, [
            $userId,
            $orderNumber,
            $totalAmount,
            $shippingInfo['address'],
            $shippingInfo['tel'],
            $shippingInfo['email'],
            $shippingInfo['fullname'] ?? 'N/A',
            $shippingInfo['tel'],
            $shippingInfo['email'],
            $shippingInfo['address'],
            $totalAmount,
            'qr_code',
            'pending',
            'pending',
            date('Y-m-d H:i:s'),
            'pending'
        ]);
        
        if (!$result) {
            throw new Exception('ไม่สามารถสร้างคำสั่งซื้อได้: ' . pg_last_error($conn));
        }
        
        $orderRow = pg_fetch_assoc($result);
        if (!$orderRow) {
            throw new Exception('ไม่สามารถดึง Order ID ได้');
        }
        
        $orderId = $orderRow['id'];
        
        // เพิ่มข้อมูลสินค้าในคำสั่งซื้อ
        foreach ($products as $index => $product) {
            $insertOrderItemQuery = "
                INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price)
                VALUES ($1, $2, $3, $4, $5, $6)
            ";
            
            $result = pg_query_params($conn, $insertOrderItemQuery, [
                $orderId,
                $product['id'],
                $product['name'],
                $product['quantity'],
                $product['price'],
                $product['price'] * $product['quantity']
            ]);
            
            if (!$result) {
                throw new Exception("ไม่สามารถเพิ่มสินค้า {$product['name']} ได้: " . pg_last_error($conn));
            }
        }
        
        // commit transaction
        $commitResult = pg_query($conn, 'COMMIT');
        if (!$commitResult) {
            throw new Exception('ไม่สามารถ commit transaction ได้: ' . pg_last_error($conn));
        }
        
        pg_close($conn);
        
        error_log("Order created successfully: ID=$orderId, Number=$orderNumber, User=$userId, Amount=$totalAmount");
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'message' => 'สร้างคำสั่งซื้อสำเร็จ'
        ];
        
    } catch (Exception $e) {
        // rollback transaction
        if (isset($conn)) {
            $rollbackResult = pg_query($conn, 'ROLLBACK');
            if (!$rollbackResult) {
                error_log("Failed to rollback transaction: " . pg_last_error($conn));
            }
            pg_close($conn);
        }
        
        $errorMessage = 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ: ' . $e->getMessage();
        error_log($errorMessage);
        
        return [
            'success' => false,
            'message' => $errorMessage
        ];
    }
}

/**
 * จัดการการชำระเงินแบบ QR Code
 */
function handleQRCodePayment($orderId, $userId, $amount) {
    try {
        $promptpayId = getPromptPayId();
        
        // บันทึกข้อมูลการชำระเงิน
        $paymentResult = savePayment(
            $orderId,
            $userId,
            'qr_code',
            $amount,
            [
                'promptpay_id' => $promptpayId,
                'payment_type' => 'PromptPay QR Code'
            ],
            $promptpayId,
            true
        );
        
        if ($paymentResult['success']) {
            return [
                'success' => true,
                'payment_id' => $paymentResult['payment_id'],
                'promptpay_id' => $promptpayId,
                'message' => 'บันทึกข้อมูลการชำระเงิน QR Code สำเร็จ'
            ];
        } else {
            return $paymentResult;
        }
        
    } catch (Exception $e) {
        error_log("Error handling QR code payment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการจัดการการชำระเงิน QR Code: ' . $e->getMessage()
        ];
    }
}

// สร้าง prepared statements เมื่อโหลดไฟล์
if (function_exists('getConnection')) {
    try {
        $conn = getConnection();
        createPaymentStatements($conn);
        pg_close($conn);
    } catch (Exception $e) {
        error_log("Error initializing payment statements: " . $e->getMessage());
    }
}
?>
