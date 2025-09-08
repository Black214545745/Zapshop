<?php
session_start();
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: user-login.php");
    exit();
}

include 'config.php';

// ดึงข้อมูลผู้ใช้
$conn = getConnection();
$user = null;

// ลองดึงข้อมูลจาก user_id ก่อน
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = $1";
    $result = pg_query_params($conn, $query, [$user_id]);
    
    if ($result && pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);
    }
}

// ถ้าไม่มี user_id หรือไม่พบข้อมูล ลองใช้ username
if (!$user && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $query = "SELECT * FROM users WHERE username = $1";
    $result = pg_query_params($conn, $query, [$username]);
    
    if ($result && pg_num_rows($result) > 0) {
        $user = pg_fetch_assoc($result);
        // อัปเดต session ให้มี user_id
        $_SESSION['user_id'] = $user['id'];
    }
}

// ถ้ายังไม่พบข้อมูล ให้ redirect ไปหน้า login
if (!$user) {
    header("Location: user-login.php");
    exit();
}

$user_id = $user['id'];

// จัดการการอัปเดตข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $full_name = $_POST['full_name'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $address = $_POST['address'];
                
                $update_query = "UPDATE users SET full_name = $1, email = $2, phone = $3, address = $4 WHERE id = $5";
                $update_result = pg_query_params($conn, $update_query, [$full_name, $email, $phone, $address, $user_id]);
                
                if ($update_result) {
                    $_SESSION['message'] = 'อัปเดตข้อมูลสำเร็จ';
                    $_SESSION['message_type'] = 'success';
                    // อัปเดตข้อมูลใน session
                    $_SESSION['full_name'] = $full_name;
                    header("Location: profile.php");
                    exit();
                } else {
                    $_SESSION['message'] = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล';
                    $_SESSION['message_type'] = 'danger';
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // ตรวจสอบรหัสผ่านปัจจุบัน
                if (!password_verify($current_password, $user['password'])) {
                    $_SESSION['message'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
                    $_SESSION['message_type'] = 'danger';
                } elseif ($new_password !== $confirm_password) {
                    $_SESSION['message'] = 'รหัสผ่านใหม่ไม่ตรงกัน';
                    $_SESSION['message_type'] = 'danger';
                } elseif (strlen($new_password) < 6) {
                    $_SESSION['message'] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
                    $_SESSION['message_type'] = 'danger';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_query = "UPDATE users SET password = $1 WHERE id = $2";
                    $password_result = pg_query_params($conn, $query, [$hashed_password, $user_id]);
                    
                    if ($password_result) {
                        $_SESSION['message'] = 'เปลี่ยนรหัสผ่านสำเร็จ';
                        $_SESSION['message_type'] = 'success';
                    } else {
                        $_SESSION['message'] = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน';
                        $_SESSION['message_type'] = 'danger';
                    }
                }
                break;
        }
    }
}

pg_close($conn);

// บันทึก Activity Log
logActivity($_SESSION['user_id'], 'view', 'User viewed profile page', 'profile', null);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --makro-red: #e31837;
            --makro-red-dark: #c41230;
            --makro-blue: #0066cc;
            --makro-green: #28a745;
            --makro-gray: #6c757d;
            --makro-light-gray: #f8f9fa;
            --makro-border: #e9ecef;
            --makro-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --makro-shadow-hover: 0 4px 16px rgba(0, 0, 0, 0.15);
            --makro-radius: 12px;
            --makro-radius-sm: 8px;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: #2c3e50;
        }

        .page-header {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,0 1000,100 1000,0"/></svg>');
            background-size: cover;
        }

        .page-header-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .profile-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 25px;
            border-left: 4px solid var(--makro-red);
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            box-shadow: var(--makro-shadow-hover);
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--makro-light-gray);
        }

        .card-header i {
            color: var(--makro-red);
            font-size: 2rem;
        }

        .card-header h4 {
            margin: 0;
            color: var(--makro-red);
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--makro-red);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            border: none;
            padding: 12px 30px;
            border-radius: var(--makro-radius-sm);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--makro-red-dark) 0%, var(--makro-red) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 24, 55, 0.3);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--makro-blue) 0%, #0056b3 100%);
            border: none;
            padding: 12px 30px;
            border-radius: var(--makro-radius-sm);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #0056b3 0%, var(--makro-blue) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
            color: white;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 3rem;
            box-shadow: var(--makro-shadow);
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            margin-bottom: 15px;
        }

        .info-item i {
            color: var(--makro-red);
            font-size: 1.2rem;
            width: 25px;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
            min-width: 100px;
        }

        .info-value {
            color: var(--makro-gray);
            flex: 1;
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .profile-card {
                padding: 20px;
            }
            
            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="page-header">
    <div class="container">
        <div class="page-header-content">
            <h1 class="page-title">
                <i class="fas fa-user-circle"></i> โปรไฟล์
            </h1>
            <p class="page-subtitle">จัดการข้อมูลส่วนตัวและบัญชีของคุณ</p>
        </div>
    </div>
</div>

<div class="container">
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo ($_SESSION['message_type'] ?? 'info'); ?> alert-dismissible fade show fade-in-up" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ข้อมูลโปรไฟล์ -->
    <div class="profile-card fade-in-up">
        <div class="card-header">
            <i class="fas fa-user"></i>
            <h4>ข้อมูลส่วนตัว</h4>
        </div>
        
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span class="info-label">ชื่อผู้ใช้:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-item">
                    <i class="fas fa-id-card"></i>
                    <span class="info-label">ชื่อ-นามสกุล:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['full_name'] ?? 'ไม่ระบุ'); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span class="info-label">อีเมล:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'ไม่ระบุ'); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span class="info-label">เบอร์โทร:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'ไม่ระบุ'); ?></span>
                </div>
            </div>
            <div class="col-12">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="info-label">ที่อยู่:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['address'] ?? 'ไม่ระบุ'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- แก้ไขข้อมูลส่วนตัว -->
    <div class="profile-card fade-in-up">
        <div class="card-header">
            <i class="fas fa-edit"></i>
            <h4>แก้ไขข้อมูลส่วนตัว</h4>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">อีเมล</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">เบอร์โทร</label>
                        <input type="tel" class="form-control" name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">ที่อยู่</label>
                        <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>

    <!-- เปลี่ยนรหัสผ่าน -->
    <div class="profile-card fade-in-up">
        <div class="card-header">
            <i class="fas fa-lock"></i>
            <h4>เปลี่ยนรหัสผ่าน</h4>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="change_password">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" name="new_password" 
                               minlength="6" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" class="form-control" name="confirm_password" 
                               minlength="6" required>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
