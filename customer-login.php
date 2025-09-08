<?php
session_start();
include 'config.php';

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

// ตรวจสอบว่าตาราง customers มีอยู่หรือไม่
$checkTableSQL = "SELECT EXISTS (
    SELECT FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_name = 'customers'
);";
$tableExists = pg_query($conn, $checkTableSQL);
$tableExistsResult = pg_fetch_assoc($tableExists);

if (!$tableExistsResult['exists']) {
    $_SESSION['message'] = "❌ ตาราง customers ยังไม่ได้สร้าง กรุณารัน create_customer_tables.php ก่อน";
    header("Location: create_customer_tables.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = pg_escape_string($conn, $_POST['email']);
    $password = pg_escape_string($conn, $_POST['password']);
    
    // ค้นหาลูกค้าในตาราง customers
    $stmt = pg_prepare($conn, "customer_login", "SELECT id, email, password_hash, first_name, last_name, is_active FROM customers WHERE email = $1");
    $result = pg_execute($conn, "customer_login", [$email]);
    
    if ($result && pg_num_rows($result) > 0) {
        $customer = pg_fetch_assoc($result);
        
        if ($customer['is_active'] && password_verify($password, $customer['password_hash'])) {
            // เข้าสู่ระบบสำเร็จ
            $_SESSION['customer_id'] = $customer['id'];
            $_SESSION['customer_email'] = $customer['email'];
            $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
            $_SESSION['customer_logged_in'] = true;
            
            // อัปเดต last_login
            $update_query = "UPDATE customers SET last_login = CURRENT_TIMESTAMP WHERE id = $1";
            pg_query_params($conn, $update_query, [$customer['id']]);
            
            // บันทึก Activity Log (ใช้ตาราง customers)
            logActivity($customer['id'], 'login', 'Customer logged in', 'customers', $customer['id']);
            
            $_SESSION['message'] = "เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับ " . $customer['first_name'];
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['message'] = "รหัสผ่านไม่ถูกต้องหรือบัญชีถูกระงับ!";
        }
    } else {
        $_SESSION['message'] = "ไม่พบอีเมลนี้ในระบบ!";
    }
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ลูกค้า</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .demo-accounts {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .demo-accounts h6 {
            color: #495057;
            margin-bottom: 10px;
        }
        
        .demo-accounts small {
            color: #6c757d;
            display: block;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</h2>
            <p>เข้าสู่ระบบในฐานะลูกค้า</p>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo strpos($_SESSION['message'], 'สำเร็จ') !== false ? 'success' : 'danger'; ?>">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">อีเมล</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
            </button>
        </form>
        
        <div class="demo-accounts">
            <h6><i class="fas fa-info-circle"></i> บัญชีทดสอบลูกค้า:</h6>
            <small><strong>customer1@example.com</strong> / password123 (สมชาย ใจดี)</small>
            <small><strong>customer2@example.com</strong> / password123 (สมหญิง รักดี)</small>
            <small><strong>customer3@example.com</strong> / password123 (John Smith)</small>
        </div>
        
        <div class="register-link">
            <p>ยังไม่มีบัญชี? <a href="customer-register.php">สมัครสมาชิก</a></p>
            <p><a href="index.php"><i class="fas fa-home"></i> กลับหน้าแรก</a></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
