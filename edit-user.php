<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}
include 'config.php';

// ตรวจสอบว่ามีค่า id ส่งมาใน URL หรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin-users.php");
    exit();
}

$id = $_GET['id'];

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "ไม่พบข้อมูลผู้ใช้";
    exit();
}

$user = $result->fetch_assoc();

// จัดการการส่งฟอร์มเพื่ออัปเดตข้อมูลผู้ใช้
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['username'];
    $new_email = $_POST['email'];
    $new_password = $_POST['password'];

    // สร้างคำสั่ง SQL สำหรับอัปเดต
    $update_sql = "UPDATE users SET username = ?, email = ?";
    $params = "ss";
    $bind_array = [$new_username, $new_email];

    // ถ้ามีการกรอกรหัสผ่านใหม่ จะทำการอัปเดตด้วย
    if (!empty($new_password)) {
        // ตรวจสอบความยาวรหัสผ่าน
        if (strlen($new_password) < 8) {
            $error = "รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร!";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql .= ", password = ?";
            $params .= "s";
            $bind_array[] = $hashed_password;
        }
    }

    $update_sql .= " WHERE id = ?";
    $params .= "i";
    $bind_array[] = $id;

    if (!isset($error)) {
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param($params, ...$bind_array);

        if ($update_stmt->execute()) {
            header("Location: admin-users.php?msg=success");
            exit();
        } else {
            $error = "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขผู้ใช้ | ShopZone Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f8f9fa; }
        .card { border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: none; }
        .card-header { background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); color: white; border-top-left-radius: 15px; border-top-right-radius: 15px; font-weight: 600; }
        .btn-red { background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); color: white; font-weight: 600; border: none; }
        .btn-red:hover { opacity: 0.9; color: white; }
    </style>
</head>
<body>
<?php include 'include/admin-menu.php'; ?>
<div class="container-xl py-4">
    <div class="mb-4">
        <h2 class="fw-bold"><i class="fas fa-edit me-2"></i>แก้ไขข้อมูลผู้ใช้</h2>
    </div>
    <div class="card">
        <div class="card-header">
            แก้ไขผู้ใช้: <?php echo htmlspecialchars($user['username']); ?>
        </div>
        <div class="card-body">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">อีเมล</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่านใหม่ (หากต้องการเปลี่ยน)</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่านใหม่">
                    <small class="text-muted">เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                </div>
                <button type="submit" class="btn btn-red"><i class="fas fa-save me-2"></i>บันทึกการแก้ไข</button>
                <a href="admin-users.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>ย้อนกลับ</a>
            </form>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>