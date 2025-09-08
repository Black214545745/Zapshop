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
    $confirm_password = pg_escape_string($conn, $_POST['confirm_password']);
    $first_name = pg_escape_string($conn, $_POST['first_name']);
    $last_name = pg_escape_string($conn, $_POST['last_name']);
    $phone = pg_escape_string($conn, $_POST['phone']);
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    
    // ตรวจสอบรหัสผ่าน
    if ($password !== $confirm_password) {
        $_SESSION['message'] = "รหัสผ่านไม่ตรงกัน!";
        header("Location: customer-register.php");
        exit();
    }
    
    if (strlen($password) < 8) {
        $_SESSION['message'] = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร!";
        header("Location: customer-register.php");
        exit();
    }
    
    // ตรวจสอบว่ามีอีเมลนี้อยู่แล้วหรือไม่ในตาราง customers
    $check_query = "SELECT * FROM customers WHERE email = $1";
    $check_result = pg_query_params($conn, $check_query, [$email]);
    
    if (pg_num_rows($check_result) > 0) {
        $_SESSION['message'] = "อีเมลนี้ถูกใช้งานแล้ว!";
        header("Location: customer-register.php");
        exit();
    }
    
    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // บันทึกลงฐานข้อมูลตาราง customers
    $query = "INSERT INTO customers (email, password_hash, first_name, last_name, phone, date_of_birth, gender, is_verified) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";
    if (pg_query_params($conn, $query, [$email, $hashed_password, $first_name, $last_name, $phone, $date_of_birth, $gender, true])) {
        // สร้าง customer profile ในตาราง customer_profiles
        $customer_id_query = "SELECT id FROM customers WHERE email = $1";
        $customer_result = pg_query_params($conn, $customer_id_query, [$email]);
        $customer_data = pg_fetch_assoc($customer_result);
        
        if ($customer_data) {
            $profile_query = "INSERT INTO customer_profiles (customer_id, preferences, newsletter_subscription) VALUES ($1, $2, $3)";
            $preferences = json_encode(['theme' => 'light', 'currency' => 'THB']);
            pg_query_params($conn, $profile_query, [$customer_data['id'], $preferences, true]);
        }
        
        $_SESSION['message'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
        header("Location: customer-login.php");
        exit();
    } else {
        $_SESSION['message'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก!";
    }
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ลูกค้า</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .register-header p {
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
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-link a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .row {
            margin: 0 -10px;
        }
        
        .col-md-6 {
            padding: 0 10px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-plus"></i> สมัครสมาชิก</h2>
            <p>สร้างบัญชีลูกค้าใหม่</p>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo strpos($_SESSION['message'], 'สำเร็จ') !== false ? 'success' : 'danger'; ?>">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ *</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">นามสกุล *</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">อีเมล *</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">เบอร์โทรศัพท์</label>
                <input type="tel" class="form-control" name="phone" placeholder="0812345678">
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">วันเกิด</label>
                        <input type="date" class="form-control" name="date_of_birth">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">เพศ</label>
                        <select class="form-control" name="gender">
                            <option value="">เลือกเพศ</option>
                            <option value="male">ชาย</option>
                            <option value="female">หญิง</option>
                            <option value="other">อื่นๆ</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">รหัสผ่าน *</label>
                <input type="password" class="form-control" name="password" required minlength="8">
            </div>
            
            <div class="form-group">
                <label class="form-label">ยืนยันรหัสผ่าน *</label>
                <input type="password" class="form-control" name="confirm_password" required minlength="8">
            </div>
            
            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus"></i> สมัครสมาชิก
            </button>
        </form>
        
        <div class="login-link">
            <p>มีบัญชีแล้ว? <a href="customer-login.php">เข้าสู่ระบบ</a></p>
            <p><a href="index.php"><i class="fas fa-home"></i> กลับหน้าแรก</a></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
