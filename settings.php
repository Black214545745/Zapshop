<?php
session_start();
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

include 'config.php';

// ดึงข้อมูลการตั้งค่าของผู้ใช้
$user_id = $_SESSION['user_id'];
$conn = getConnection();

// จัดการการอัปเดตการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_notifications':
                $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
                $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
                $order_updates = isset($_POST['order_updates']) ? 1 : 0;
                $promotions = isset($_POST['promotions']) ? 1 : 0;
                
                // อัปเดตการตั้งค่าการแจ้งเตือน (ถ้ามีตาราง settings)
                // หรือเก็บใน session สำหรับตัวอย่างนี้
                $_SESSION['settings'] = [
                    'email_notifications' => $email_notifications,
                    'sms_notifications' => $sms_notifications,
                    'order_updates' => $order_updates,
                    'promotions' => $promotions
                ];
                
                $_SESSION['message'] = 'อัปเดตการตั้งค่าการแจ้งเตือนสำเร็จ';
                $_SESSION['message_type'] = 'success';
                break;
                
            case 'update_language':
                $language = $_POST['language'];
                $_SESSION['language'] = $language;
                
                $_SESSION['message'] = 'เปลี่ยนภาษาสำเร็จ';
                $_SESSION['message_type'] = 'success';
                break;
                
            case 'update_display':
                $theme = $_POST['theme'];
                $font_size = $_POST['font_size'];
                $compact_mode = isset($_POST['compact_mode']) ? 1 : 0;
                
                $_SESSION['display_settings'] = [
                    'theme' => $theme,
                    'font_size' => $font_size,
                    'compact_mode' => $compact_mode
                ];
                
                $_SESSION['message'] = 'อัปเดตการตั้งค่าการแสดงผลสำเร็จ';
                $_SESSION['message_type'] = 'success';
                break;
        }
    }
}

// ดึงการตั้งค่าปัจจุบัน
$current_settings = $_SESSION['settings'] ?? [
    'email_notifications' => 1,
    'sms_notifications' => 0,
    'order_updates' => 1,
    'promotions' => 0
];

$current_language = $_SESSION['language'] ?? 'th';
$current_display = $_SESSION['display_settings'] ?? [
    'theme' => 'light',
    'font_size' => 'medium',
    'compact_mode' => 0
];

pg_close($conn);

// บันทึก Activity Log
logActivity($_SESSION['user_id'], 'view', 'User viewed settings page', 'settings', null);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า - ZapShop</title>
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

        .settings-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 25px;
            border-left: 4px solid var(--makro-red);
            transition: all 0.3s ease;
        }

        .settings-card:hover {
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

        .form-control, .form-select {
            border: 2px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--makro-red);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
        }

        .form-check {
            margin-bottom: 15px;
        }

        .form-check-input:checked {
            background-color: var(--makro-red);
            border-color: var(--makro-red);
        }

        .form-check-label {
            font-weight: 500;
            color: #2c3e50;
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

        .btn-success {
            background: linear-gradient(135deg, var(--makro-green) 0%, #20c997 100%);
            border: none;
            padding: 12px 30px;
            border-radius: var(--makro-radius-sm);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, var(--makro-green) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }

        .setting-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            margin-bottom: 15px;
        }

        .setting-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .setting-title {
            font-weight: 600;
            color: #2c3e50;
        }

        .setting-description {
            color: var(--makro-gray);
            font-size: 0.9rem;
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
            
            .settings-card {
                padding: 20px;
            }
            
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
                <i class="fas fa-cog"></i> ตั้งค่า
            </h1>
            <p class="page-subtitle">ปรับแต่งการตั้งค่าตามความต้องการของคุณ</p>
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

    <!-- การแจ้งเตือน -->
    <div class="settings-card fade-in-up">
        <div class="card-header">
            <i class="fas fa-bell"></i>
            <h4>การแจ้งเตือน</h4>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_notifications">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="email_notifications" 
                               id="email_notifications" <?php echo $current_settings['email_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="email_notifications">
                            แจ้งเตือนทางอีเมล
                        </label>
                        <div class="form-text">รับการแจ้งเตือนผ่านอีเมล</div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="sms_notifications" 
                               id="sms_notifications" <?php echo $current_settings['sms_notifications'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="sms_notifications">
                            แจ้งเตือนทาง SMS
                        </label>
                        <div class="form-text">รับการแจ้งเตือนผ่าน SMS</div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="order_updates" 
                               id="order_updates" <?php echo $current_settings['order_updates'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="order_updates">
                            อัปเดตคำสั่งซื้อ
                        </label>
                        <div class="form-text">แจ้งเตือนเมื่อมีการอัปเดตคำสั่งซื้อ</div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="promotions" 
                               id="promotions" <?php echo $current_settings['promotions'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="promotions">
                            โปรโมชั่นและส่วนลด
                        </label>
                        <div class="form-text">รับข่าวสารโปรโมชั่นและส่วนลด</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>

    <!-- ภาษา -->
    <div class="settings-card fade-in-up">
        <div class="card-header">
            <i class="fas fa-language"></i>
            <h4>ภาษา</h4>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_language">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">เลือกภาษา</label>
                        <select class="form-select" name="language" required>
                            <option value="th" <?php echo $current_language === 'th' ? 'selected' : ''; ?>>
                                ไทย
                            </option>
                            <option value="en" <?php echo $current_language === 'en' ? 'selected' : ''; ?>>
                                English
                            </option>
                            <option value="zh" <?php echo $current_language === 'zh' ? 'selected' : ''; ?>>
                                中文
                            </option>
                        </select>
                        <div class="form-text">เลือกภาษาที่คุณต้องการใช้</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-globe"></i> เปลี่ยนภาษา
                </button>
            </div>
        </form>
    </div>

    <!-- การแสดงผล -->
    <div class="settings-card fade-in-up">
        <div class="card-header">
            <i class="fas fa-palette"></i>
            <h4>การแสดงผล</h4>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_display">
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">ธีม</label>
                        <select class="form-select" name="theme" required>
                            <option value="light" <?php echo $current_display['theme'] === 'light' ? 'selected' : ''; ?>>
                                สีอ่อน
                            </option>
                            <option value="dark" <?php echo $current_display['theme'] === 'dark' ? 'selected' : ''; ?>>
                                สีเข้ม
                            </option>
                            <option value="auto" <?php echo $current_display['theme'] === 'auto' ? 'selected' : ''; ?>>
                                อัตโนมัติ
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">ขนาดตัวอักษร</label>
                        <select class="form-select" name="font_size" required>
                            <option value="small" <?php echo $current_display['font_size'] === 'small' ? 'selected' : ''; ?>>
                                เล็ก
                            </option>
                            <option value="medium" <?php echo $current_display['font_size'] === 'medium' ? 'selected' : ''; ?>>
                                ปานกลาง
                            </option>
                            <option value="large" <?php echo $current_display['font_size'] === 'large' ? 'selected' : ''; ?>>
                                ใหญ่
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-check" style="margin-top: 32px;">
                        <input class="form-check-input" type="checkbox" name="compact_mode" 
                               id="compact_mode" <?php echo $current_display['compact_mode'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="compact_mode">
                            โหมดกะทัดรัด
                        </label>
                        <div class="form-text">แสดงเนื้อหาแบบกะทัดรัด</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-eye"></i> บันทึกการแสดงผล
                </button>
            </div>
        </form>
    </div>

    <!-- การตั้งค่าอื่นๆ -->
    <div class="settings-card fade-in-up">
        <div class="card-header">
            <i class="fas fa-tools"></i>
            <h4>การตั้งค่าอื่นๆ</h4>
        </div>
        
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-title">ล้างแคช</div>
                <div class="setting-description">ล้างข้อมูลแคชของเว็บไซต์</div>
            </div>
            <button class="btn btn-outline-secondary" onclick="clearCache()">
                <i class="fas fa-broom"></i> ล้างแคช
            </button>
        </div>
        
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-title">ส่งข้อมูลการใช้งาน</div>
                <div class="setting-description">ช่วยปรับปรุงเว็บไซต์โดยส่งข้อมูลการใช้งาน</div>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="analytics" checked>
                <label class="form-check-label" for="analytics"></label>
            </div>
        </div>
        
        <div class="setting-item">
            <div class="setting-info">
                <div class="setting-title">โหมดทดสอบ</div>
                <div class="setting-description">เปิดใช้งานฟีเจอร์ใหม่ที่ยังอยู่ในขั้นทดสอบ</div>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="beta_mode">
                <label class="form-check-label" for="beta_mode"></label>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    function clearCache() {
        if (confirm('คุณต้องการล้างแคชหรือไม่? การดำเนินการนี้อาจใช้เวลาสักครู่')) {
            // แสดง loading
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังล้าง...';
            button.disabled = true;
            
            // จำลองการล้างแคช
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-check"></i> สำเร็จ';
                button.className = 'btn btn-success';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.className = 'btn btn-outline-secondary';
                    button.disabled = false;
                }, 2000);
            }, 2000);
        }
    }
</script>
</body>
</html>
