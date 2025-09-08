<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}

include 'config.php';

// กำหนด base_url สำหรับเมนู
$base_url = '';

include 'include/admin-menu.php';

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

$result = null;
$edit_mode = false;
$search_query = "";

// จัดการการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['full_name']) && isset($_POST['email'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $role = $_POST['role'] ?? 'user';
        $user_id = isset($_POST['id']) ? $_POST['id'] : null;

        if ($user_id) {
            // อัปเดตผู้ใช้
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = $1, password_hash = $2, role = $3, updated_at = CURRENT_TIMESTAMP WHERE id = $4";
                $result = pg_query_params($conn, $sql, [$username, $hashed_password, $role, $user_id]);
            } else {
                $sql = "UPDATE users SET username = $1, role = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3";
                $result = pg_query_params($conn, $sql, [$username, $role, $user_id]);
            }

            if ($result) {
                // อัปเดต user_profiles
                $sql_profile = "UPDATE user_profiles SET full_name = $1, email = $2, updated_at = CURRENT_TIMESTAMP WHERE user_id = $3";
                $result_profile = pg_query_params($conn, $sql_profile, [$full_name, $email, $user_id]);
                
                if ($result_profile) {
                    $_SESSION['message'] = "ผู้ใช้ได้รับการอัปเดตเรียบร้อยแล้ว!";
                } else {
                    $_SESSION['message'] = "เกิดข้อผิดพลาดในการอัปเดตข้อมูลส่วนตัว: " . pg_last_error($conn);
                }
            } else {
                $_SESSION['message'] = "เกิดข้อผิดพลาดในการอัปเดตผู้ใช้: " . pg_last_error($conn);
            }
        } else {
            // เพิ่มผู้ใช้ใหม่
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // เริ่ม transaction
            pg_query($conn, "BEGIN");
            
            $sql = "INSERT INTO users (username, password_hash, role, created_at, updated_at) VALUES ($1, $2, $3, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) RETURNING id";
            $result = pg_query_params($conn, $sql, [$username, $hashed_password, $role]);
            
            if ($result && pg_num_rows($result) > 0) {
                $row = pg_fetch_assoc($result);
                $new_user_id = $row['id'];
                
                // เพิ่มข้อมูลใน user_profiles
                $sql_profile = "INSERT INTO user_profiles (user_id, full_name, email, created_at, updated_at) VALUES ($1, $2, $3, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
                $result_profile = pg_query_params($conn, $sql_profile, [$new_user_id, $full_name, $email]);
                
                if ($result_profile) {
                    pg_query($conn, "COMMIT");
                    $_SESSION['message'] = "ผู้ใช้ใหม่ถูกเพิ่มเรียบร้อยแล้ว!";
                } else {
                    pg_query($conn, "ROLLBACK");
                    $_SESSION['message'] = "เกิดข้อผิดพลาดในการเพิ่มข้อมูลส่วนตัว: " . pg_last_error($conn);
                }
            } else {
                pg_query($conn, "ROLLBACK");
                $_SESSION['message'] = "เกิดข้อผิดพลาดในการเพิ่มผู้ใช้: " . pg_last_error($conn);
            }
        }
        header("Location: admin-users.php");
        exit();
    }
}

// จัดการการลบผู้ใช้
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    // ตรวจสอบว่าไม่ใช่ admin คนเดียว
    $sql_check_admin = "SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'";
    $result_check = pg_query($conn, $sql_check);
    $admin_count = 0;
    if ($result_check && pg_num_rows($result_check) > 0) {
        $row = pg_fetch_assoc($result_check);
        $admin_count = $row['admin_count'];
    }

    if ($admin_count <= 1) {
        $_SESSION['message'] = "ไม่สามารถลบ admin คนสุดท้ายได้!";
    } else {
        $sql_delete = "DELETE FROM users WHERE id = $1";
        $result_delete = pg_query_params($conn, $sql_delete, [$delete_id]);

        if ($result_delete) {
            $_SESSION['message'] = "ผู้ใช้ถูกลบเรียบร้อยแล้ว!";
        } else {
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการลบผู้ใช้: " . pg_last_error($conn);
        }
    }
    header("Location: admin-users.php");
    exit();
}

// จัดการการแก้ไขผู้ใช้
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "
        SELECT 
            u.id, 
            u.username, 
            u.role, 
            u.created_at,
            up.full_name, 
            up.email
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = $1
    ";
    $result_edit = pg_query_params($conn, $sql, [$edit_id]);
    if ($result_edit && pg_num_rows($result_edit) > 0) {
        $result = pg_fetch_assoc($result_edit);
        $edit_mode = true;
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$sql_users = "
    SELECT 
        u.id, 
        u.username, 
        u.role, 
        u.created_at, 
        u.updated_at,
        up.full_name, 
        up.email
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
";

if (isset($_POST['search']) && !empty(trim($_POST['search']))) {
    $search_query = trim($_POST['search']);
    $sql_users .= " WHERE u.username LIKE $1 OR up.full_name LIKE $2 OR up.email LIKE $3";
    $search_param = "%" . $search_query . "%";
    $result_users = pg_query_params($conn, $sql_users, [$search_param, $search_param, $search_param]);
} else {
    $sql_users .= " ORDER BY u.created_at DESC";
    $result_users = pg_query($conn, $sql_users);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ | Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding-top: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #e53e3e;
            border-color: #e53e3e;
        }
        .btn-primary:hover {
            background-color: #c53030;
            border-color: #c53030;
        }
        .btn-success {
            background-color: #48bb78;
            border-color: #48bb78;
        }
        .btn-success:hover {
            background-color: #38a169;
            border-color: #38a169;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #333;
        }
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            border-radius: 10px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-radius: 10px;
        }
        .badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<div class="container-xl py-4">

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- ฟอร์มเพิ่ม/แก้ไขผู้ใช้ -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <i class="fas fa-user-plus me-2"></i><?php echo $edit_mode ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่'; ?>
        </div>
        <div class="card-body">
            <form action="admin-users.php" method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo $result['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($result['username']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   <?php echo $edit_mode ? '' : 'required'; ?>>
                            <?php if ($edit_mode): ?>
                                <small class="text-muted">ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">ชื่อเต็ม</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($result['full_name']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($result['email']) : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="role" class="form-label">บทบาท</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?php echo ($edit_mode && $result['role'] == 'user') ? 'selected' : ''; ?>>ผู้ใช้ทั่วไป</option>
                                <option value="employee" <?php echo ($edit_mode && $result['role'] == 'employee') ? 'selected' : ''; ?>>พนักงาน</option>
                                <option value="admin" <?php echo ($edit_mode && $result['role'] == 'admin') ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo $edit_mode ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้'; ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="admin-users.php" class="btn btn-secondary">
                            <i class="fas fa-times-circle me-2"></i>ยกเลิก
                        </a>
                    <?php else: ?>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i>ล้างฟอร์ม
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ตารางผู้ใช้ -->
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>รายการผู้ใช้</span>
            <form class="d-flex" method="POST" action="admin-users.php">
                <input class="form-control me-2" type="search" placeholder="ค้นหาผู้ใช้..." 
                       name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-light" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-danger">
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ใช้</th>
                        <th>ชื่อเต็ม</th>
                            <th>อีเมล</th>
                        <th>บทบาท</th>
                        <th>วันที่สร้าง</th>
                        <th>การกระทำ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result_users && pg_num_rows($result_users) > 0): ?>
                        <?php $i = 1; while ($row = pg_fetch_assoc($result_users)): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <?php if ($row['role'] == 'admin'): ?>
                                        <span class="badge bg-danger">ผู้ดูแลระบบ</span>
                                    <?php elseif ($row['role'] == 'employee'): ?>
                                        <span class="badge bg-warning text-dark">พนักงาน</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">ผู้ใช้ทั่วไป</span>
                                    <?php endif; ?>
                                </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>
                                    <a href="admin-users.php?edit_id=<?php echo $row['id']; ?>" 
                                       class="btn btn-warning btn-sm me-1" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="admin-users.php?delete_id=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบผู้ใช้นี้?');" 
                                       title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                ไม่พบผู้ใช้
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
pg_close($conn);
?>