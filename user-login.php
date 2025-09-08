<?php
session_start();
include 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username']; // ใช้ username เหมือนเดิม
    $password = $_POST['password'];

    // เชื่อมต่อฐานข้อมูล
    $conn = getConnection();
    
    // ค้นหาลูกค้าในตาราง customers โดยใช้ email เป็น username
    $stmt = pg_prepare($conn, "customer_login", "SELECT id, email, password_hash, first_name, last_name, is_active FROM customers WHERE email = $1");
    $result = pg_execute($conn, "customer_login", [$username]);
    
    if ($result && pg_num_rows($result) > 0) {
        $customer = pg_fetch_assoc($result);
        
        if ($customer['is_active'] && password_verify($password, $customer['password_hash'])) {
            session_regenerate_id(true); // ป้องกัน Session Fixation
            
            // เก็บข้อมูลใน session (ใช้ชื่อเดิมเพื่อไม่ให้กระทบระบบเดิม)
            $_SESSION['user_id'] = $customer['id'];
            $_SESSION['username'] = $customer['email']; // ใช้ email แทน username
            $_SESSION['email'] = $customer['email'];
            $_SESSION['full_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
            $_SESSION['role'] = 'customer'; // กำหนด role เป็น customer
            
            // อัปเดต last_login
            $update_query = "UPDATE customers SET last_login = CURRENT_TIMESTAMP WHERE id = $1";
            pg_query_params($conn, $update_query, [$customer['id']]);
            
            // บันทึก Activity Log
            logActivity($customer['id'], 'login', 'Customer logged in successfully', 'customers', $customer['id']);
            
            // สร้างการแจ้งเตือนต้อนรับ
            createNotification(
                $customer['id'], 
                'ยินดีต้อนรับ!', 
                'คุณได้เข้าสู่ระบบสำเร็จแล้ว', 
                'success'
            );
            
            $_SESSION['message'] = "เข้าสู่ระบบสำเร็จ! ยินดีต้อนรับ " . $customer['first_name'];
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['message'] = "รหัสผ่านไม่ถูกต้องหรือบัญชีถูกระงับ!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "ไม่พบอีเมลนี้ในระบบ!";
        $_SESSION['message_type'] = "error";
    }
    
    pg_close($conn);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ZapShop</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(220, 53, 69, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #dc3545;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-right: 45px;
        }
        
        .input-group-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 10;
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .login-footer a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #c82333;
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .demo-credentials h6 {
            margin: 0 0 10px 0;
            color: #495057;
            font-weight: 600;
        }
        
        .demo-credentials p {
            margin: 5px 0;
            color: #6c757d;
        }
        
        .demo-credentials strong {
            color: #495057;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
                            <h3><i class="fas fa-store"></i> ZapShop</h3>
        <p>ระบบจัดการสต็อกสินค้า</p>
    </div>
    
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo ($_SESSION['message_type'] ?? 'info'); ?>" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>
    
    <div class="demo-credentials">
        <h6><i class="fas fa-info-circle"></i> บัญชีทดสอบลูกค้า:</h6>
        <p><strong>Username:</strong> customer1@example.com</p>
        <p><strong>Password:</strong> password123</p>
        <hr>
        <p><strong>Username:</strong> customer2@example.com</p>
        <p><strong>Password:</strong> password123</p>
        <hr>
        <p><strong>Username:</strong> customer3@example.com</p>
        <p><strong>Password:</strong> password123</p>
    </div>
    
    <form action="user-login.php" method="post">
        <div class="form-group">
            <label for="username" class="form-label">
                <i class="fas fa-user"></i> ชื่อผู้ใช้ (อีเมล)
            </label>
            <div class="input-group">
                <input type="text" class="form-control" name="username" id="username" required 
                       placeholder="กรอกอีเมล" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                <span class="input-group-icon">
                    <i class="fas fa-user"></i>
                </span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="password" class="form-label">
                <i class="fas fa-lock"></i> รหัสผ่าน
            </label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" required 
                       placeholder="กรอกรหัสผ่าน">
                <span class="input-group-icon">
                    <i class="fas fa-lock"></i>
                </span>
            </div>
        </div>
        
        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
        </button>
    </form>
    
    <div class="login-footer">
        <p>ยังไม่มีบัญชี? <a href="user-register.php">สมัครสมาชิก</a></p>
        <p><a href="index.php"><i class="fas fa-home"></i> กลับหน้าหลัก</a></p>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
// เพิ่ม animation เมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    const loginContainer = document.querySelector('.login-container');
    loginContainer.style.opacity = '0';
    loginContainer.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        loginContainer.style.transition = 'all 0.5s ease';
        loginContainer.style.opacity = '1';
        loginContainer.style.transform = 'translateY(0)';
    }, 100);
});

// เพิ่มการตรวจสอบ form
document.querySelector('form').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    
    if (!username || !password) {
        e.preventDefault();
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        return false;
    }
    
    // ตรวจสอบรูปแบบอีเมล
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(username)) {
        e.preventDefault();
        alert('กรุณากรอกอีเมลให้ถูกต้อง');
        return false;
    }
});
</script>
</body>
</html>
