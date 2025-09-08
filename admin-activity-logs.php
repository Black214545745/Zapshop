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

$search_query = "";
$filter_action = "";
$filter_date = "";
$filter_role = "";

// จัดการการค้นหาและกรอง
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search_query = isset($_POST['search']) ? trim($_POST['search']) : "";
    $filter_action = isset($_POST['filter_action']) ? $_POST['filter_action'] : "";
    $filter_date = isset($_POST['filter_date']) ? $_POST['filter_date'] : "";
    $filter_role = isset($_POST['filter_role']) ? $_POST['filter_role'] : "";
}

// สร้าง SQL query สำหรับดึงข้อมูลกิจกรรม
$sql = "
    SELECT 
        al.id,
        al.action,
        al.description,
        al.table_name,
        al.record_id,
        al.ip_address,
        al.user_agent,
        al.created_at,
        u.username,
        up.full_name,
        u.role as user_role,
        c.email as customer_email,
        c.first_name || ' ' || c.last_name as customer_name,
        CASE 
            WHEN u.id IS NOT NULL AND u.role = 'admin' THEN 'admin'
            WHEN u.id IS NOT NULL AND u.role = 'employee' THEN 'employee'
            WHEN u.id IS NOT NULL THEN 'user'
            WHEN c.id IS NOT NULL THEN 'customer'
            ELSE 'system'
        END as user_type
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN customers c ON al.user_id = c.id
    WHERE 1=1
";

$params = [];
$param_count = 0;

// เพิ่มเงื่อนไขการค้นหา
if (!empty($search_query)) {
    $param_count++;
    $sql .= " AND (al.description ILIKE $" . $param_count . " OR al.action ILIKE $" . $param_count . " OR u.username ILIKE $" . $param_count . " OR up.full_name ILIKE $" . $param_count . " OR c.email ILIKE $" . $param_count . " OR (c.first_name || ' ' || c.last_name) ILIKE $" . $param_count . ")";
    $params[] = "%" . $search_query . "%";
}

// เพิ่มเงื่อนไขการกรอง action
if (!empty($filter_action)) {
    $param_count++;
    $sql .= " AND al.action = $" . $param_count;
    $params[] = $filter_action;
}

// เพิ่มเงื่อนไขการกรองวันที่
if (!empty($filter_date)) {
    $param_count++;
    $sql .= " AND DATE(al.created_at) = $" . $param_count;
    $params[] = $filter_date;
}

// เพิ่มเงื่อนไขการกรองบทบาท
if (!empty($filter_role)) {
    $param_count++;
    if ($filter_role == 'admin') {
        $sql .= " AND u.id IS NOT NULL AND u.role = 'admin'";
    } elseif ($filter_role == 'employee') {
        $sql .= " AND u.id IS NOT NULL AND u.role = 'employee'";
    } elseif ($filter_role == 'customer') {
        $sql .= " AND c.id IS NOT NULL";
    } elseif ($filter_role == 'system') {
        $sql .= " AND u.id IS NULL AND c.id IS NULL";
    }
}

$sql .= " ORDER BY al.created_at DESC LIMIT 100";

// ดึงข้อมูลกิจกรรม
if (!empty($params)) {
    $result = pg_query_params($conn, $sql, $params);
} else {
    $result = pg_query($conn, $sql);
}

$activities = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $activities[] = $row;
    }
}

// ดึงรายการ action ทั้งหมดสำหรับ dropdown
$actions_result = pg_query($conn, "SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = [];
if ($actions_result) {
    while ($row = pg_fetch_assoc($actions_result)) {
        $actions[] = $row['action'];
    }
}

pg_close($conn);

// บันทึก Activity Log
logActivity($_SESSION['admin_id'] ?? null, 'view', 'Admin viewed activity logs', 'admin', null);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กิจกรรมล่าสุด | Admin Dashboard</title>
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
        .form-control, .form-select {
            border-radius: 8px;
        }
        .activity-item {
            border-left: 4px solid #e53e3e;
            padding-left: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .activity-item:hover {
            background-color: #f8f9fa;
            border-left-color: #c53030;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }
        .badge {
            font-size: 0.75rem;
        }
        .badge-sm {
            font-size: 0.65rem;
            padding: 0.25em 0.5em;
        }
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="container-xl py-4">

    <!-- ฟิลเตอร์และค้นหา -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i>ค้นหาและกรองข้อมูล
        </div>
        <div class="card-body">
            <form method="POST" action="admin-activity-logs.php">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="search" class="form-label">ค้นหา</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="ค้นหาจากคำอธิบาย, การกระทำ, หรือชื่อผู้ใช้..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="filter_action" class="form-label">กรองตามการกระทำ</label>
                            <select class="form-select" id="filter_action" name="filter_action">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo htmlspecialchars($action); ?>" 
                                            <?php echo $filter_action == $action ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="filter_date" class="form-label">กรองตามวันที่</label>
                            <input type="date" class="form-control" id="filter_date" name="filter_date" 
                                   value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="filter_role" class="form-label">กรองตามบทบาท</label>
                            <select class="form-select" id="filter_role" name="filter_role">
                                <option value="">ทั้งหมด</option>
                                <option value="admin" <?php echo $filter_role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="employee" <?php echo $filter_role == 'employee' ? 'selected' : ''; ?>>พนักงาน</option>
                                <option value="customer" <?php echo $filter_role == 'customer' ? 'selected' : ''; ?>>ลูกค้า</option>
                                <option value="system" <?php echo $filter_role == 'system' ? 'selected' : ''; ?>>ระบบ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> ค้นหา
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- รายการกิจกรรม -->
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>รายการกิจกรรม (<?php echo count($activities); ?> รายการ)</span>
            <div>
                <a href="admin-activity-logs.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-refresh"></i> รีเฟรช
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <div class="activity-icon bg-<?php echo getActivityColor($activity['action']); ?>">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></h6>
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?php 
                                    $user_name = $activity['full_name'] ?: $activity['customer_name'] ?: $activity['username'] ?: $activity['customer_email'] ?: 'ไม่ระบุ';
                                    echo htmlspecialchars($user_name);
                                    ?>
                                    <span class="ms-2">
                                        <span class="badge bg-<?php echo getRoleColor($activity['user_type']); ?> badge-sm">
                                            <?php echo getRoleText($activity['user_type']); ?>
                                        </span>
                                    </span>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <span class="badge bg-<?php echo getActivityColor($activity['action']); ?> me-2">
                                    <?php echo htmlspecialchars($activity['action']); ?>
                                </span>
                                <?php if ($activity['table_name']): ?>
                                    <span class="badge bg-secondary">
                                        <?php echo htmlspecialchars($activity['table_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2 text-end">
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                </small>
                                <br>
                                <button class="btn btn-sm btn-outline-primary mt-1" 
                                        onclick="showActivityDetails(<?php echo htmlspecialchars(json_encode($activity)); ?>)">
                                    <i class="fas fa-eye"></i> รายละเอียด
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-history fa-3x mb-3 d-block"></i>
                    <h5>ไม่พบกิจกรรม</h5>
                    <p>ไม่มีกิจกรรมที่ตรงกับเงื่อนไขการค้นหา</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal รายละเอียดกิจกรรม -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>รายละเอียดกิจกรรม</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="activityModalBody">
                <!-- เนื้อหาจะถูกใส่โดย JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function showActivityDetails(activity) {
    const modalBody = document.getElementById('activityModalBody');
    
    let userInfo = '';
    if (activity.full_name) {
        userInfo = `${activity.full_name} (${activity.username})`;
    } else if (activity.customer_name) {
        userInfo = `${activity.customer_name} (${activity.customer_email})`;
    } else if (activity.username) {
        userInfo = activity.username;
    } else if (activity.customer_email) {
        userInfo = activity.customer_email;
    } else {
        userInfo = 'ไม่ระบุ';
    }
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-info-circle me-2"></i>ข้อมูลพื้นฐาน</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>การกระทำ:</strong></td>
                        <td><span class="badge bg-${getActivityColor(activity.action)}">${activity.action}</span></td>
                    </tr>
                    <tr>
                        <td><strong>คำอธิบาย:</strong></td>
                        <td>${activity.description}</td>
                    </tr>
                    <tr>
                        <td><strong>ผู้ใช้:</strong></td>
                        <td>${userInfo} <span class="badge bg-${getRoleColor(activity.user_type)} ms-2">${getRoleText(activity.user_type)}</span></td>
                    </tr>
                    <tr>
                        <td><strong>วันที่/เวลา:</strong></td>
                        <td>${new Date(activity.created_at).toLocaleString('th-TH')}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-database me-2"></i>ข้อมูลเทคนิค</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>ตาราง:</strong></td>
                        <td>${activity.table_name || 'ไม่ระบุ'}</td>
                    </tr>
                    <tr>
                        <td><strong>Record ID:</strong></td>
                        <td>${activity.record_id || 'ไม่ระบุ'}</td>
                    </tr>
                    <tr>
                        <td><strong>IP Address:</strong></td>
                        <td>${activity.ip_address || 'ไม่ระบุ'}</td>
                    </tr>
                    <tr>
                        <td><strong>User Agent:</strong></td>
                        <td><small>${activity.user_agent || 'ไม่ระบุ'}</small></td>
                    </tr>
                </table>
            </div>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('activityModal'));
    modal.show();
}

function getActivityColor(action) {
    const colors = {
        'login': 'success',
        'logout': 'secondary',
        'register': 'primary',
        'view': 'info',
        'create': 'success',
        'update': 'warning',
        'delete': 'danger',
        'cart_add': 'primary',
        'cart_update': 'warning',
        'cart_remove': 'danger'
    };
    return colors[action] || 'secondary';
}

function getRoleColor(role) {
    const colors = {
        'admin': 'danger',
        'employee': 'warning',
        'customer': 'primary',
        'system': 'secondary'
    };
    return colors[role] || 'light';
}

function getRoleText(role) {
    const texts = {
        'admin': 'Admin',
        'employee': 'พนักงาน',
        'customer': 'ลูกค้า',
        'system': 'ระบบ'
    };
    return texts[role] || 'ไม่ระบุ';
}
</script>
</body>
</html>

<?php
function getActivityIcon($action) {
    switch ($action) {
        case 'login': return 'sign-in-alt';
        case 'logout': return 'sign-out-alt';
        case 'register': return 'user-plus';
        case 'view': return 'eye';
        case 'create': return 'plus';
        case 'update': return 'edit';
        case 'delete': return 'trash';
        case 'cart_add': return 'cart-plus';
        case 'cart_update': return 'edit';
        case 'cart_remove': return 'trash';
        default: return 'circle';
    }
}

function getActivityColor($action) {
    switch ($action) {
        case 'login': return 'success';
        case 'logout': return 'secondary';
        case 'register': return 'primary';
        case 'view': return 'info';
        case 'create': return 'success';
        case 'update': return 'warning';
        case 'delete': return 'danger';
        case 'cart_add': return 'primary';
        case 'cart_update': return 'warning';
        case 'cart_remove': return 'danger';
        default: return 'secondary';
    }
}

function getRoleColor($role) {
    switch ($role) {
        case 'admin': return 'danger';
        case 'employee': return 'warning';
        case 'customer': return 'primary';
        case 'system': return 'secondary';
        default: return 'light';
    }
}

function getRoleText($role) {
    switch ($role) {
        case 'admin': return 'Admin';
        case 'employee': return 'พนักงาน';
        case 'customer': return 'ลูกค้า';
        case 'system': return 'ระบบ';
        default: return 'ไม่ระบุ';
    }
}
?>
