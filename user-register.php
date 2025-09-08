<?php
session_start();
include 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); // ใช้ email เป็นหลัก
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $_SESSION['message'] = "กรุณากรอกข้อมูลให้ครบถ้วน!";
        $_SESSION['message_type'] = "error";
        header("Location: user-register.php");
        exit();
    }

    // ตรวจสอบความยาวรหัสผ่าน
    if (strlen($password) < 8) {
        $_SESSION['message'] = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร!";
        $_SESSION['message_type'] = "error";
        header("Location: user-register.php");
        exit();
    }

    // ตรวจสอบรูปแบบอีเมล
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "รูปแบบอีเมลไม่ถูกต้อง!";
        $_SESSION['message_type'] = "error";
        header("Location: user-register.php");
        exit();
    }

    try {
        // ตรวจสอบว่าอีเมลนี้มีอยู่ในระบบหรือไม่
        $conn = getConnection();
        
        $check_query = "SELECT id FROM customers WHERE email = $1";
        $check_result = pg_query_params($conn, $check_query, [$email]);

        if ($check_result && pg_num_rows($check_result) > 0) {
            $_SESSION['message'] = "อีเมลนี้ถูกใช้งานแล้ว!";
            $_SESSION['message_type'] = "error";
            pg_close($conn);
            header("Location: user-register.php");
            exit();
        }

        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // บันทึกลงฐานข้อมูลตาราง customers
        $query = "INSERT INTO customers (email, password_hash, first_name, last_name, phone, date_of_birth, gender, is_verified) VALUES ($1, $2, $3, $4, $5, $6, $7, $8)";
        $result = pg_query_params($conn, $query, [$email, $hashed_password, $first_name, $last_name, $phone, $date_of_birth, $gender, true]);
        
        if ($result) {
            // ดึง customer_id ที่เพิ่งสร้าง
            $customer_id_query = "SELECT id FROM customers WHERE email = $1";
            $customer_result = pg_query_params($conn, $customer_id_query, [$email]);
            $customer_data = pg_fetch_assoc($customer_result);
            
            if ($customer_data) {
                // สร้าง customer profile
                $profile_query = "INSERT INTO customer_profiles (customer_id, preferences, newsletter_subscription) VALUES ($1, $2, $3)";
                $preferences = json_encode(['theme' => 'light', 'currency' => 'THB']);
                pg_query_params($conn, $profile_query, [$customer_data['id'], $preferences, true]);
                
                // บันทึก Activity Log
                logActivity($customer_data['id'], 'register', 'New customer registered', 'customers', $customer_data['id']);
            }
            
            pg_close($conn);
            
            $_SESSION['message'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            $_SESSION['message_type'] = "success";
            header("Location: user-login.php");
            exit();
        } else {
            pg_close($conn);
            throw new Exception("ไม่สามารถสร้างลูกค้าได้");
        }
        
    } catch (Exception $e) {
        $_SESSION['message'] = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ZapShop</title>
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
        
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(220, 53, 69, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }
        
        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h3 {
            color: #333;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .register-header p {
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
        
        .btn-register {
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
        
        .btn-register:hover {
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
        
        .register-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .register-footer a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .register-footer a:hover {
            color: #c82333;
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .password-requirements h6 {
            margin: 0 0 10px 0;
            color: #495057;
            font-weight: 600;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #6c757d;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-header">
        <h3><i class="fas fa-user-plus"></i> สมัครสมาชิก</h3>
                        <p>สร้างบัญชีใหม่สำหรับระบบ ZapShop</p>
    </div>
    
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo ($_SESSION['message_type'] ?? 'info'); ?>" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
        </div>
    <?php endif; ?>
    
    <div class="password-requirements">
        <h6><i class="fas fa-info-circle"></i> ข้อกำหนดรหัสผ่าน:</h6>
        <ul>
            <li>ต้องมีอย่างน้อย 8 ตัวอักษร</li>
            <li>ควรมีตัวอักษรตัวใหญ่และตัวเล็ก</li>
            <li>ควรมีตัวเลขและสัญลักษณ์พิเศษ</li>
        </ul>
    </div>
    
    <form action="user-register.php" method="post">
        <div class="form-group">
            <label for="full_name" class="form-label">
                <i class="fas fa-user"></i> ชื่อ-นามสกุล
            </label>
            <div class="input-group">
                <input type="text" class="form-control" name="full_name" id="full_name" required 
                       placeholder="กรอกชื่อ-นามสกุล" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                <span class="input-group-icon">
                    <i class="fas fa-user"></i>
                </span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="first_name" class="form-label">
                        <i class="fas fa-user"></i> ชื่อ
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="first_name" id="first_name" required 
                               placeholder="กรอกชื่อ" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        <span class="input-group-icon">
                            <i class="fas fa-user"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="last_name" class="form-label">
                        <i class="fas fa-user"></i> นามสกุล
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="last_name" id="last_name" required 
                               placeholder="กรอกนามสกุล" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        <span class="input-group-icon">
                            <i class="fas fa-user"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">
                <i class="fas fa-envelope"></i> อีเมล
            </label>
            <div class="input-group">
                <input type="email" class="form-control" name="email" id="email" required 
                       placeholder="กรอกอีเมล" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <span class="input-group-icon">
                    <i class="fas fa-envelope"></i>
                </span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="phone" class="form-label">
                <i class="fas fa-phone"></i> เบอร์โทรศัพท์
            </label>
            <div class="input-group">
                <input type="tel" class="form-control" name="phone" id="phone" 
                       placeholder="0812345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                <span class="input-group-icon">
                    <i class="fas fa-phone"></i>
                </span>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="date_of_birth" class="form-label">
                        <i class="fas fa-calendar"></i> วันเกิด
                    </label>
                    <div class="input-group">
                        <input type="date" class="form-control" name="date_of_birth" id="date_of_birth" 
                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                        <span class="input-group-icon">
                            <i class="fas fa-calendar"></i>
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="gender" class="form-label">
                        <i class="fas fa-venus-mars"></i> เพศ
                    </label>
                    <div class="input-group">
                        <select class="form-control" name="gender" id="gender">
                            <option value="">เลือกเพศ</option>
                            <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>ชาย</option>
                            <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>หญิง</option>
                            <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>อื่นๆ</option>
                        </select>
                        <span class="input-group-icon">
                            <i class="fas fa-venus-mars"></i>
                        </span>
                    </div>
                </div>
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
        
        <button type="submit" class="btn-register">
            <i class="fas fa-user-plus"></i> สมัครสมาชิก
        </button>
    </form>
    
    <div class="register-footer">
        <p>มีบัญชีแล้ว? <a href="user-login.php">เข้าสู่ระบบ</a></p>
        <p><a href="index.php"><i class="fas fa-home"></i> กลับหน้าหลัก</a></p>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
// เพิ่ม animation เมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    const registerContainer = document.querySelector('.register-container');
    registerContainer.style.opacity = '0';
    registerContainer.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        registerContainer.style.transition = 'all 0.5s ease';
        registerContainer.style.opacity = '1';
        registerContainer.style.transform = 'translateY(0)';
    }, 100);
});

// เพิ่มการตรวจสอบ form
document.querySelector('form').addEventListener('submit', function(e) {
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    
    if (!firstName || !lastName || !email || !password) {
        e.preventDefault();
        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
        return false;
    }
    
    if (!email.includes('@')) {
        e.preventDefault();
        alert('กรุณากรอกอีเมลให้ถูกต้อง');
        return false;
    }
});

// เพิ่มการตรวจสอบรหัสผ่านแบบ real-time
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const requirements = document.querySelector('.password-requirements');
    
    if (password.length >= 8) {
        requirements.style.borderColor = '#28a745';
    } else {
        requirements.style.borderColor = '#e9ecef';
    }
});
</script>
</body>
</html>
