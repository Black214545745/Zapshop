<?php
session_start();
include 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

// ตรวจสอบว่ามี admin ในระบบหรือไม่
$checkAdminSQL = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
$adminExists = pg_query($conn, $checkAdminSQL);
$adminExistsResult = pg_fetch_assoc($adminExists);

if ($adminExistsResult['admin_count'] == 0) {
    // เพิ่มแอดมินเริ่มต้น
    $adminUsername = 'admin';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $adminEmail = 'admin@zapshop.com';
    $adminFullName = 'Administrator';
    
    // เริ่ม transaction
    pg_query($conn, "BEGIN");
    
    $insertUserSQL = "
    INSERT INTO users (username, password_hash, role, is_active, created_at, updated_at) 
    VALUES ($1, $2, $3, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) RETURNING id
    ";
    
    $userResult = pg_query_params($conn, $insertUserSQL, [
        $adminUsername,
        $adminPassword,
        'admin'
    ]);
    
    if ($userResult && pg_num_rows($userResult) > 0) {
        $row = pg_fetch_assoc($userResult);
        $adminId = $row['id'];
        
        $insertProfileSQL = "
        INSERT INTO user_profiles (user_id, full_name, email, created_at, updated_at) 
        VALUES ($1, $2, $3, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ";
        
        $profileResult = pg_query_params($conn, $insertProfileSQL, [
            $adminId,
            $adminFullName,
            $adminEmail
        ]);
        
        if ($profileResult) {
            pg_query($conn, "COMMIT");
            $_SESSION['message'] = "✅ ระบบแอดมินถูกสร้างเรียบร้อยแล้ว! Username: admin, Password: admin123";
        } else {
            pg_query($conn, "ROLLBACK");
        }
    } else {
        pg_query($conn, "ROLLBACK");
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // เตรียม SQL แบบป้องกัน SQL Injection - ตรวจสอบเฉพาะ admin
    $stmt = pg_prepare($conn, "admin_login", "
        SELECT 
            u.id, 
            u.username, 
            u.password_hash, 
            u.role,
            up.email, 
            up.full_name 
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.username = $1 AND u.role = 'admin' AND u.is_active = true
    ");
    $result = pg_execute($conn, "admin_login", [$username]);

    if ($result && pg_num_rows($result) === 1) {
        $user = pg_fetch_assoc($result);

        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); // ป้องกัน Session Fixation
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_full_name'] = $user['full_name'] ?? $user['username'];

            header("Location: admin-dashboard.php"); // ไปหน้าแดชบอร์ด
            exit();
        } else {
            $_SESSION['message'] = "❌ รหัสผ่านไม่ถูกต้อง!";
        }
    } else {
        $_SESSION['message'] = "❌ ไม่พบชื่อผู้ใช้หรือไม่มีสิทธิ์เข้าสู่ระบบแอดมิน!";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ (Admin)</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Kanit', sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: none;
        }
        h3.text-center {
            font-weight: 600;
            color: #c53030;
        }
        hr {
            border-top: 1px solid #c53030;
        }
        .btn-custom {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            border: none;
            color: white;
            font-weight: 600;
        }
        .btn-custom:hover {
            opacity: 0.9;
            color: white;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-container">
        <h3 class="text-center"><i class="fas fa-user-shield me-2"></i> เข้าสู่ระบบ (Admin)</h3>
        <hr>
        <?php if (!empty($_SESSION['message'])): ?>
            <div class="alert <?php echo strpos($_SESSION['message'], 'สำเร็จ') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="mb-3">
                <label class="form-label">Username:</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password:</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-custom w-100"><i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ</button>
        </form>
        
        <div class="text-center mt-3">
            <div class="alert alert-info">
                <h6><i class="fas fa-shield-alt"></i> ข้อมูลแอดมินหลัก:</h6>
                <p class="mb-1"><strong>Username:</strong> admin</p>
                <p class="mb-0"><strong>Password:</strong> admin123</p>
                <small class="text-muted">แอดมินหลักเท่านั้นที่สามารถแต่งตั้งแอดมินคนอื่นได้</small>
            </div>
            <a href="index.php" class="text-muted">
                <i class="fas fa-home me-1"></i> กลับหน้าแรก
            </a>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>