<?php
// ตรวจสอบว่าเป็น PostgreSQL หรือไม่
if (!function_exists('isPostgreSQL')) {
    function isPostgreSQL() {
        return true; // ใช้ PostgreSQL เสมอ
    }
}

// การเชื่อมต่อฐานข้อมูล PostgreSQL สำหรับ Render.com
if (!function_exists('getConnection')) {
function getConnection() {
    $host = 'dpg-d2q1vder433s73dqf0lg-a.oregon-postgres.render.com';
    $port = '5432';
    $dbname = 'zapstock_db';
    $user = 'zapstock_user';
    $password = 'jb3uWpZlFoG3f2d1PI21ZFX0frHSGrDW';
    
    $conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
    $conn = pg_connect($conn_string);
    
    if (!$conn) {
        die("Connection failed: " . pg_last_error());
    }
    
    return $conn;
}
}

// ฟังก์ชันสำหรับดึงข้อมูลผู้ใช้จากตาราง users และ user_profiles
if (!function_exists('getUserByUsername')) {
function getUserByUsername($username) {
    $conn = getConnection();
    
    $query = "
        SELECT 
            u.id,
            u.username,
            u.password_hash,
            u.role,
            u.is_active,
            up.full_name,
            up.email,
            up.phone,
            up.address
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.username = $1 AND u.is_active = true
    ";
    
    $result = pg_query_params($conn, $query, [$username]);
    
    if ($result && pg_num_rows($result) == 1) {
        $user = pg_fetch_assoc($result);
        pg_close($conn);
        return $user;
    }
    
    pg_close($conn);
    return false;
}
}

// ฟังก์ชันสำหรับสร้างผู้ใช้ใหม่
if (!function_exists('createUser')) {
function createUser($username, $password, $full_name, $email, $role = 'user') {
    $conn = getConnection();
    
    // เริ่ม transaction
    pg_query($conn, "BEGIN");
    
    try {
        // สร้างผู้ใช้ในตาราง users
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_query = "
            INSERT INTO users (username, password_hash, role) 
            VALUES ($1, $2, $3) 
            RETURNING id
        ";
        
        $user_result = pg_query_params($conn, $user_query, [$username, $hashed_password, $role]);
        
        if (!$user_result) {
            throw new Exception("Failed to create user: " . pg_last_error($conn));
        }
        
        $user_row = pg_fetch_assoc($user_result);
        $user_id = $user_row['id'];
        
        // สร้างโปรไฟล์ในตาราง user_profiles
        $profile_query = "
            INSERT INTO user_profiles (user_id, full_name, email) 
            VALUES ($1, $2, $3)
        ";
        
        $profile_result = pg_query_params($conn, $profile_query, [$user_id, $full_name, $email]);
        
        if (!$profile_result) {
            throw new Exception("Failed to create user profile: " . pg_last_error($conn));
        }
        
        // Commit transaction
        pg_query($conn, "COMMIT");
        pg_close($conn);
        
        return $user_id;
        
    } catch (Exception $e) {
        // Rollback transaction
        pg_query($conn, "ROLLBACK");
        pg_close($conn);
        throw $e;
    }
}
}

// ฟังก์ชันสำหรับดึงข้อมูลสินค้าทั้งหมด
if (!function_exists('getAllProducts')) {
function getAllProducts() {
    $conn = getConnection();
    
    $query = "
        SELECT 
            p.id,
            p.name,
            p.description,
            p.sku,
            p.current_stock,
            p.price,
            p.image_url,
            p.status,
            c.name as category_name,
            s.name as supplier_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN suppliers s ON p.supplier_id = s.id
        WHERE p.status = 'active'
        ORDER BY p.name
    ";
    
    $result = pg_query($conn, $query);
    $products = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    pg_close($conn);
    return $products;
}
}

// ฟังก์ชันสำหรับดึงข้อมูลสินค้าของสด
if (!function_exists('getAllFreshProducts')) {
function getAllFreshProducts() {
    $conn = getConnection();
    
    $query = "
        SELECT 
            fp.id,
            fp.name,
            fp.description,
            fp.sku,
            fp.current_stock,
            fp.unit,
            fp.price_per_unit,
            fp.status,
            fc.name as category_name,
            s.name as supplier_name
        FROM fresh_products fp
        LEFT JOIN fresh_categories fc ON fp.category_id = fc.id
        LEFT JOIN suppliers s ON fp.supplier_id = s.id
        WHERE fp.status = 'active'
        ORDER BY fp.name
    ";
    
    $result = pg_query($conn, $query);
    $products = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    pg_close($conn);
    return $products;
}
}

// ฟังก์ชันสำหรับดึงข้อมูลหมวดหมู่
if (!function_exists('getAllCategories')) {
function getAllCategories() {
    $conn = getConnection();
    
    $query = "SELECT id, name, description FROM categories WHERE is_active = true ORDER BY name";
    $result = pg_query($conn, $query);
    $categories = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    
    pg_close($conn);
    return $categories;
}
}

// ฟังก์ชันสำหรับดึงข้อมูลหมวดหมู่สินค้าของสด
if (!function_exists('getAllFreshCategories')) {
function getAllFreshCategories() {
    $conn = getConnection();
    
    $query = "SELECT id, name, description, shelf_life_days, storage_condition FROM fresh_categories ORDER BY name";
    $result = pg_query($conn, $query);
    $categories = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    
    pg_close($conn);
    return $categories;
}
}

// ฟังก์ชันสำหรับบันทึก Activity Log
if (!function_exists('logActivity')) {
function logActivity($user_id, $action, $description, $table_name = null, $record_id = null) {
    $conn = getConnection();
    
    $query = "
        INSERT INTO activity_logs (user_id, action, description, table_name, record_id, ip_address, user_agent) 
        VALUES ($1, $2, $3, $4, $5, $6, $7)
    ";
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    // แปลง user_id เป็น UUID หรือ null
    $user_uuid = null;
    if ($user_id && $user_id !== '1' && is_string($user_id) && strlen($user_id) > 10) {
        $user_uuid = $user_id;
    }
    
    $result = pg_query_params($conn, $query, [
        $user_uuid, 
        $action, 
        $description, 
        $table_name, 
        $record_id, 
        $ip_address, 
        $user_agent
    ]);
    
    pg_close($conn);
    return $result;
}
}

// ฟังก์ชันสำหรับสร้างการแจ้งเตือน
if (!function_exists('createNotification')) {
function createNotification($user_id, $title, $message, $type = 'info', $action_url = null) {
    $conn = getConnection();
    
    $query = "
        INSERT INTO notifications (user_id, title, message, type, action_url) 
        VALUES ($1, $2, $3, $4, $5)
    ";
    
    $result = pg_query_params($conn, $query, [$user_id, $title, $message, $type, $action_url]);
    
    pg_close($conn);
    return $result;
}
}

// ฟังก์ชันสำหรับดึงการแจ้งเตือนที่ยังไม่ได้อ่าน
if (!function_exists('getUnreadNotifications')) {
function getUnreadNotifications($user_id) {
    $conn = getConnection();
    
    $query = "
        SELECT id, title, message, type, action_url, created_at 
        FROM notifications 
        WHERE user_id = $1 AND is_read = false 
        ORDER BY created_at DESC
    ";
    
    $result = pg_query_params($conn, $query, [$user_id]);
    $notifications = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $notifications[] = $row;
        }
    }
    
    pg_close($conn);
    return $notifications;
}
}

// ฟังก์ชันสำหรับตรวจสอบสินค้าที่ใกล้หมดอายุ
if (!function_exists('getExpiringProducts')) {
function getExpiringProducts($days = 7) {
    $conn = getConnection();
    
    $query = "
        SELECT 
            fp.name,
            pl.lot_number,
            pl.expiry_date,
            pl.remaining_quantity,
            fp.unit,
            pl.expiry_date - CURRENT_DATE as days_until_expiry
        FROM fresh_products fp
        JOIN product_lots pl ON fp.id = pl.product_id
        WHERE pl.expiry_date <= CURRENT_DATE + INTERVAL '$1 days'
        AND pl.remaining_quantity > 0
        AND pl.quality_status = 'good'
        ORDER BY pl.expiry_date ASC
    ";
    
    $result = pg_query_params($conn, $query, [$days]);
    $products = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    pg_close($conn);
    return $products;
}
}

// ฟังก์ชันสำหรับตรวจสอบสินค้าที่สต็อกต่ำ
if (!function_exists('getLowStockProducts')) {
function getLowStockProducts() {
    $conn = getConnection();
    
    $query = "
        SELECT 
            p.name,
            p.sku,
            p.current_stock,
            p.min_stock_quantity,
            c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.current_stock <= p.min_stock_quantity
        AND p.status = 'active'
        ORDER BY p.current_stock ASC
    ";
    
    $result = pg_query($conn, $query);
    $products = [];
    
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
    
    pg_close($conn);
    return $products;
}
}

// ฟังก์ชันสำหรับดึงการตั้งค่าระบบ
if (!function_exists('getSystemSetting')) {
function getSystemSetting($key) {
    $conn = getConnection();
    
    $query = "SELECT value FROM system_settings WHERE key = $1 AND is_active = true";
    $result = pg_query_params($conn, $query, [$key]);
    
    if ($result && pg_num_rows($result) == 1) {
        $row = pg_fetch_assoc($result);
        pg_close($conn);
        return $row['value'];
    }
    
    pg_close($conn);
    return null;
}
}

// ฟังก์ชันสำหรับอัปเดตการตั้งค่าระบบ
if (!function_exists('updateSystemSetting')) {
function updateSystemSetting($key, $value) {
    $conn = getConnection();
    
    $query = "
        UPDATE system_settings 
        SET value = $2, updated_at = CURRENT_TIMESTAMP 
        WHERE key = $1
    ";
    
    $result = pg_query_params($conn, $query, [$key, $value]);
    pg_close($conn);
    
    return $result;
}
}

// ฟังก์ชันสำหรับตรวจสอบสิทธิ์ผู้ใช้
if (!function_exists('hasPermission')) {
function hasPermission($user_id, $permission) {
    $conn = getConnection();
    
    $query = "SELECT role FROM users WHERE id = $1 AND is_active = true";
    $result = pg_query_params($conn, $query, [$user_id]);
    
    if ($result && pg_num_rows($result) == 1) {
        $row = pg_fetch_assoc($result);
        $role = $row['role'];
        pg_close($conn);
        
        // ตรวจสอบสิทธิ์ตาม role
        switch ($permission) {
            case 'admin':
                return $role === 'admin';
            case 'manage_products':
                return in_array($role, ['admin', 'employee']);
            case 'view_reports':
                return in_array($role, ['admin', 'employee']);
            case 'manage_users':
                return $role === 'admin';
            default:
                return false;
        }
    }
    
    pg_close($conn);
    return false;
}
}

// ฟังก์ชันสำหรับสร้าง UUID
if (!function_exists('generateUUID')) {
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
}

// ฟังก์ชันสำหรับตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!function_exists('testDatabaseConnection')) {
function testDatabaseConnection() {
    try {
        $conn = getConnection();
        $result = pg_query($conn, "SELECT version()");
        
        if ($result) {
            $row = pg_fetch_assoc($result);
            pg_close($conn);
            return [
                'success' => true,
                'version' => $row['version']
            ];
        }
        
        pg_close($conn);
        return ['success' => false, 'error' => 'Query failed'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
}

// ฟังก์ชันสำหรับปิดการเชื่อมต่อ
if (!function_exists('closeConnection')) {
function closeConnection($conn) {
    if ($conn) {
        pg_close($conn);
    }
}
}

// ตั้งค่า timezone
date_default_timezone_set('Asia/Bangkok');

// เริ่มต้น session ถ้ายังไม่ได้เริ่ม
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูลเมื่อโหลดไฟล์
$db_test = testDatabaseConnection();
if (!$db_test['success']) {
    error_log("Database connection failed: " . $db_test['error']);
}
?>
