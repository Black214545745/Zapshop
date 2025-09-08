<?php
// ========================================
// การตั้งค่าที่ใช้ร่วมกันสำหรับเว็บไซต์ PHP
// เชื่อมต่อกับฐานข้อมูลเดียวกันกับแอพ React Native
// ========================================

// Load environment variables
function loadEnv($file) {
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

// Load environment file
loadEnv(__DIR__ . '/../env.shared.example');

// ========================================
// Database Configuration
// ========================================

$databaseConfig = [
    'postgresql' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => getenv('DB_PORT') ?: 5432,
        'database' => getenv('DB_NAME') ?: 'zapstock_db',
        'user' => getenv('DB_USER') ?: 'postgres',
        'password' => getenv('DB_PASSWORD') ?: '123456',
        'ssl' => getenv('NODE_ENV') === 'production' ? 'sslmode=require' : '',
    ],
    'mysql' => [
        'host' => getenv('MYSQL_HOST') ?: 'localhost',
        'port' => getenv('MYSQL_PORT') ?: 3306,
        'database' => getenv('MYSQL_DB') ?: 'zapstock_db',
        'user' => getenv('MYSQL_USER') ?: 'root',
        'password' => getenv('MYSQL_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
    'api' => [
        'base_url' => getenv('API_BASE_URL') ?: 'http://localhost:3000',
        'port' => getenv('API_PORT') ?: 3000,
        'cors_origin' => getenv('CORS_ORIGIN') ?: '*',
    ],
    'environment' => getenv('NODE_ENV') ?: 'development',
    'timezone' => 'Asia/Bangkok',
];

// ========================================
// Database Connection Functions
// ========================================

function getDatabaseConnection($type = 'postgresql') {
    global $databaseConfig;
    
    if ($type === 'postgresql') {
        $config = $databaseConfig['postgresql'];
        $conn_string = "host={$config['host']} port={$config['port']} dbname={$config['database']} user={$config['user']} password={$config['password']}";
        
        if (!empty($config['ssl'])) {
            $conn_string .= " {$config['ssl']}";
        }
        
        $conn = pg_connect($conn_string);
        
        if (!$conn) {
            throw new Exception("PostgreSQL connection failed: " . pg_last_error());
        }
        
        // Set timezone
        pg_query($conn, "SET timezone = '{$databaseConfig['timezone']}'");
        
        return $conn;
        
    } elseif ($type === 'mysql') {
        $config = $databaseConfig['mysql'];
        $conn = new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
        
        if ($conn->connect_error) {
            throw new Exception("MySQL connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset($config['charset']);
        
        return $conn;
    }
    
    throw new Exception("Unsupported database type: $type");
}

// ========================================
// API Functions
// ========================================

function callSharedAPI($endpoint, $method = 'GET', $data = null, $headers = []) {
    global $databaseConfig;
    
    $url = $databaseConfig['api']['base_url'] . $endpoint;
    
    $curl = curl_init();
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json',
        ], $headers),
    ];
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $options[CURLOPT_POSTFIELDS] = json_encode($data);
    }
    
    curl_setopt_array($curl, $options);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    curl_close($curl);
    
    if ($err) {
        throw new Exception("cURL Error: $err");
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode >= 400) {
        $error = isset($result['message']) ? $result['message'] : 'API request failed';
        throw new Exception($error);
    }
    
    return $result;
}

// ========================================
// Utility Functions
// ========================================

function isPostgreSQL() {
    return getenv('DATABASE_URL') !== false || getenv('DB_HOST') !== false;
}

function executeQuery($sql, $params = [], $dbType = null) {
    if ($dbType === null) {
        $dbType = isPostgreSQL() ? 'postgresql' : 'mysql';
    }
    
    $conn = getDatabaseConnection($dbType);
    
    try {
        if ($dbType === 'postgresql') {
            $result = pg_query_params($conn, $sql, $params);
            if (!$result) {
                throw new Exception("PostgreSQL query failed: " . pg_last_error($conn));
            }
            return $result;
        } else {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("MySQL prepare failed: " . $conn->error);
            }
            
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            return $stmt->get_result();
        }
    } finally {
        if ($dbType === 'postgresql') {
            pg_close($conn);
        } else {
            $conn->close();
        }
    }
}

function fetchData($result, $dbType = null) {
    if ($dbType === null) {
        $dbType = isPostgreSQL() ? 'postgresql' : 'mysql';
    }
    
    if ($dbType === 'postgresql') {
        return pg_fetch_assoc($result);
    } else {
        return $result->fetch_assoc();
    }
}

function numRows($result, $dbType = null) {
    if ($dbType === null) {
        $dbType = isPostgreSQL() ? 'postgresql' : 'mysql';
    }
    
    if ($dbType === 'postgresql') {
        return pg_num_rows($result);
    } else {
        return $result->num_rows;
    }
}

// ========================================
// Authentication Functions
// ========================================

function authenticateUser($username, $password) {
    try {
        $result = callSharedAPI('/api/auth/login', 'POST', [
            'username' => $username,
            'password' => $password
        ]);
        
        if (isset($result['data']['token'])) {
            $_SESSION['user_token'] = $result['data']['token'];
            $_SESSION['user_data'] = $result['data']['user'];
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

function logoutUser() {
    if (isset($_SESSION['user_token'])) {
        try {
            callSharedAPI('/api/auth/logout', 'POST', [
                'token' => $_SESSION['user_token']
            ]);
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
    
    session_destroy();
}

function isAuthenticated() {
    return isset($_SESSION['user_token']) && isset($_SESSION['user_data']);
}

function getCurrentUser() {
    return $_SESSION['user_data'] ?? null;
}

// ========================================
// Product Functions
// ========================================

function getProducts($page = 1, $limit = 10, $category_id = null, $search = null) {
    try {
        $params = http_build_query([
            'page' => $page,
            'limit' => $limit,
            'category_id' => $category_id,
            'search' => $search
        ]);
        
        $result = callSharedAPI("/api/products?$params");
        return $result['data'] ?? [];
    } catch (Exception $e) {
        error_log("Get products error: " . $e->getMessage());
        return ['products' => [], 'pagination' => []];
    }
}

function getProduct($id) {
    try {
        $result = callSharedAPI("/api/products/$id");
        return $result['data'] ?? null;
    } catch (Exception $e) {
        error_log("Get product error: " . $e->getMessage());
        return null;
    }
}

function createProduct($productData) {
    try {
        $result = callSharedAPI('/api/products', 'POST', $productData);
        return $result['data'] ?? null;
    } catch (Exception $e) {
        error_log("Create product error: " . $e->getMessage());
        throw $e;
    }
}

function getCategories() {
    try {
        $result = callSharedAPI('/api/categories');
        return $result['data'] ?? [];
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        return [];
    }
}

// ========================================
// Dashboard Functions
// ========================================

function getDashboardStats() {
    try {
        $result = callSharedAPI('/api/dashboard/stats');
        return $result['data'] ?? [];
    } catch (Exception $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        return [];
    }
}

// ========================================
// Error Handling
// ========================================

function handleError($error, $context = '') {
    error_log("Error in $context: " . $error->getMessage());
    
    if ($databaseConfig['environment'] === 'development') {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($error->getMessage());
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        echo "เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง";
        echo "</div>";
    }
}

// ========================================
// Session Management
// ========================================

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', $databaseConfig['environment'] === 'production');
        session_start();
    }
}

// ========================================
// Security Functions
// ========================================

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token validation failed');
        }
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ========================================
// Initialize
// ========================================

// Set timezone
date_default_timezone_set($databaseConfig['timezone']);

// Start session
startSecureSession();

// Generate CSRF token
generateCSRFToken();

?>


