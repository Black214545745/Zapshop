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

// ใช้ฟังก์ชันจาก config.php เพื่อดึงข้อมูลสถิติ
$conn = getConnection();

// นับจำนวนสินค้า
$result = pg_query($conn, "SELECT COUNT(*) as total FROM products");
$total_products = ($result && $row = pg_fetch_assoc($result)) ? $row['total'] : 0;

// นับจำนวนหมวดหมู่
$result = pg_query($conn, "SELECT COUNT(*) as total FROM categories");
$total_categories = ($result && $row = pg_fetch_assoc($result)) ? $row['total'] : 0;

// นับจำนวนผู้ใช้
$result = pg_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = ($result && $row = pg_fetch_assoc($result)) ? $row['total'] : 0;

// นับจำนวนผู้ใช้ที่ลงทะเบียนในเดือนนี้
$current_month = date('Y-m');
$result = pg_query_params($conn, "SELECT COUNT(*) as total FROM users WHERE to_char(created_at, 'YYYY-MM') = $1", [$current_month]);
$new_users_this_month = ($result && $row = pg_fetch_assoc($result)) ? $row['total'] : 0;

// สินค้าที่มีสต็อกต่ำ (น้อยกว่า 20 ชิ้น)
$result = pg_query($conn, "SELECT COUNT(*) as total FROM products WHERE current_stock < 20");
$low_stock_products = ($result && $row = pg_fetch_assoc($result)) ? $row['total'] : 0;

// สินค้าที่มีสต็อกต่ำ (น้อยกว่า 20 ชิ้น) - ข้อมูลจริง
$result = pg_query($conn, "SELECT name, current_stock, price FROM products WHERE current_stock < 20 ORDER BY current_stock ASC LIMIT 5");
$low_stock_products_list = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $low_stock_products_list[] = $row;
    }
}

// กิจกรรมล่าสุด 5 รายการ
$result = pg_query($conn, "SELECT al.action, al.description, al.created_at, u.username 
                          FROM activity_logs al 
                          LEFT JOIN users u ON al.user_id = u.id 
                          ORDER BY al.created_at DESC LIMIT 5");
$recent_activities = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $recent_activities[] = $row;
    }
}

// รับพารามิเตอร์การแสดงสถิติ
$period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
$sales_period = isset($_GET['sales_period']) ? $_GET['sales_period'] : 'monthly';

// สถิติการใช้งานตามช่วงเวลาที่เลือก
$stats_data = [];
$chart_title = '';
$chart_labels = [];

switch ($period) {
    case 'daily':
        // สถิติรายวัน (7 วันย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(created_at, 'YYYY-MM-DD') as date, COUNT(*) as total_activities
                                  FROM activity_logs 
                                  WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
                                  GROUP BY to_char(created_at, 'YYYY-MM-DD')
                                  ORDER BY date DESC");
        $chart_title = 'สถิติการใช้งานรายวัน';
        break;
        
    case 'weekly':
        // สถิติรายสัปดาห์ (8 สัปดาห์ย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(created_at, 'YYYY-\"W\"WW') as week, COUNT(*) as total_activities
                                  FROM activity_logs 
                                  WHERE created_at >= CURRENT_DATE - INTERVAL '8 weeks'
                                  GROUP BY to_char(created_at, 'YYYY-\"W\"WW')
                                  ORDER BY week DESC");
        $chart_title = 'สถิติการใช้งานรายสัปดาห์';
        break;
        
    case 'yearly':
        // สถิติรายปี (5 ปีย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(created_at, 'YYYY') as year, COUNT(*) as total_activities
                                  FROM activity_logs 
                                  WHERE created_at >= CURRENT_DATE - INTERVAL '5 years'
                                  GROUP BY to_char(created_at, 'YYYY')
                                  ORDER BY year DESC");
        $chart_title = 'สถิติการใช้งานรายปี';
        break;
        
    default: // monthly
        // สถิติรายเดือน (6 เดือนย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(created_at, 'YYYY-MM') as month, COUNT(*) as total_activities
                                  FROM activity_logs 
                                  WHERE created_at >= CURRENT_DATE - INTERVAL '6 months'
                                  GROUP BY to_char(created_at, 'YYYY-MM')
                                  ORDER BY month DESC");
        $chart_title = 'สถิติการใช้งานรายเดือน';
        break;
}

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $stats_data[] = $row;
    }
}

// สถิติการขาย (รายได้) ตามช่วงเวลาที่เลือก
$sales_data = [];
$sales_chart_title = '';

switch ($sales_period) {
    case 'daily':
        // สถิติการขายรายวัน (7 วันย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(order_date, 'YYYY-MM-DD') as date, 
                                  COALESCE(SUM(grand_total), 0) as total_sales,
                                  COUNT(*) as total_orders
                                  FROM orders 
                                  WHERE order_date >= CURRENT_DATE - INTERVAL '7 days'
                                  AND status = 'completed'
                                  GROUP BY to_char(order_date, 'YYYY-MM-DD')
                                  ORDER BY date DESC");
        $sales_chart_title = 'สถิติการขายรายวัน';
        break;
        
    case 'weekly':
        // สถิติการขายรายสัปดาห์ (8 สัปดาห์ย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(order_date, 'YYYY-\"W\"WW') as week, 
                                  COALESCE(SUM(grand_total), 0) as total_sales,
                                  COUNT(*) as total_orders
                                  FROM orders 
                                  WHERE order_date >= CURRENT_DATE - INTERVAL '8 weeks'
                                  AND status = 'completed'
                                  GROUP BY to_char(order_date, 'YYYY-\"W\"WW')
                                  ORDER BY week DESC");
        $sales_chart_title = 'สถิติการขายรายสัปดาห์';
        break;
        
    case 'yearly':
        // สถิติการขายรายปี (5 ปีย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(order_date, 'YYYY') as year, 
                                  COALESCE(SUM(grand_total), 0) as total_sales,
                                  COUNT(*) as total_orders
                                  FROM orders 
                                  WHERE order_date >= CURRENT_DATE - INTERVAL '5 years'
                                  AND status = 'completed'
                                  GROUP BY to_char(order_date, 'YYYY')
                                  ORDER BY year DESC");
        $sales_chart_title = 'สถิติการขายรายปี';
        break;
        
    default: // monthly
        // สถิติการขายรายเดือน (6 เดือนย้อนหลัง)
        $result = pg_query($conn, "SELECT to_char(order_date, 'YYYY-MM') as month, 
                                  COALESCE(SUM(grand_total), 0) as total_sales,
                                  COUNT(*) as total_orders
                                  FROM orders 
                                  WHERE order_date >= CURRENT_DATE - INTERVAL '6 months'
                                  AND status = 'completed'
                                  GROUP BY to_char(order_date, 'YYYY-MM')
                                  ORDER BY month DESC");
        $sales_chart_title = 'สถิติการขายรายเดือน';
        break;
}

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $sales_data[] = $row;
    }
}

pg_close($conn);

// บันทึก Activity Log
logActivity($_SESSION['admin_id'] ?? null, 'view', 'Admin viewed dashboard', 'admin', null);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        
        .card { 
            border-radius: 15px; 
            box-shadow: 0 4px 20px rgba(220, 53, 69, 0.1); 
            border: none; 
            border-top: 3px solid #dc3545;
        }
        
        .card-header { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; 
            border-top-left-radius: 15px; 
            border-top-right-radius: 15px; 
            font-weight: 600; 
        }
        
        .stats-card { 
            color: white; 
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card.products { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
        }
        
        .stats-card.categories { 
            background: linear-gradient(135deg, #fd7e14 0%, #f39c12 100%); 
        }
        
        .stats-card.users { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
        }
        
        .stats-card.low-stock { 
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); 
        }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .activity-icon.login { background: #28a745; }
        .activity-icon.register { background: #17a2b8; }
        .activity-icon.view { background: #6c757d; }
        .activity-icon.cart_add { background: #fd7e14; }
        .activity-icon.cart_update { background: #ffc107; }
        .activity-icon.cart_remove { background: #dc3545; }
        
        .btn-light {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        
        .btn-light:hover {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .page-header {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .btn-outline-light {
            border-color: rgba(255,255,255,0.3);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.5);
            color: white;
        }
        
    </style>
</head>
<body>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="text-center">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt me-2"></i>แดชบอร์ดผู้ดูแลระบบ
            </h1>
            <p class="page-subtitle">จัดการและติดตามระบบ ZapShop</p>
        </div>
    </div>
    <!-- สถิติหลัก -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stats-card products">
                <div class="card-body text-center">
                    <i class="fas fa-boxes stats-icon"></i>
                    <div class="stats-number"><?php echo number_format($total_products); ?></div>
                    <div class="stats-label">สินค้าทั้งหมด</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card categories">
                <div class="card-body text-center">
                    <i class="fas fa-tags stats-icon"></i>
                    <div class="stats-number"><?php echo number_format($total_categories); ?></div>
                    <div class="stats-label">หมวดหมู่</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card users">
                <div class="card-body text-center">
                    <i class="fas fa-users stats-icon"></i>
                    <div class="stats-number"><?php echo number_format($total_users); ?></div>
                    <div class="stats-label">ผู้ใช้ทั้งหมด</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stats-card low-stock">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle stats-icon"></i>
                    <div class="stats-number"><?php echo number_format($low_stock_products); ?></div>
                    <div class="stats-label">สินค้าใกล้หมด</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- สินค้าขายดี -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-line"></i> สินค้าที่มีสต็อกต่ำ</span>
                    <a href="admin-products.php?filter=low_stock" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-eye me-1"></i>ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($low_stock_products_list) > 0): ?>
                        <?php foreach ($low_stock_products_list as $product): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">สต็อก: <?php echo $product['current_stock']; ?> ชิ้น</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning">฿<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">ไม่มีสินค้าที่มีสต็อกต่ำ (ต่ำกว่า 20 ชิ้น)</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- กิจกรรมล่าสุด -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history"></i> กิจกรรมล่าสุด</span>
                    <a href="admin-activity-logs.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-eye me-1"></i>ดูทั้งหมด
                    </a>
                </div>
                <div class="card-body">
                    <?php if (count($recent_activities) > 0): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item d-flex align-items-center" style="cursor: pointer;" 
                                 onclick="window.location.href='admin-activity-logs.php'">
                                <div class="activity-icon <?php echo $activity['action']; ?> me-3">
                                    <i class="fas fa-<?php echo getActivityIcon($activity['action']); ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?php echo htmlspecialchars($activity['description']); ?></div>
                                    <small class="text-muted">
                                        โดย <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?> • 
                                        <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">ไม่มีกิจกรรมล่าสุด</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- กราฟสถิติการขาย -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-line"></i> <?php echo $sales_chart_title; ?></span>
                    <div class="btn-group" role="group">
                        <a href="?period=<?php echo $period; ?>&sales_period=daily" class="btn btn-sm <?php echo $sales_period == 'daily' ? 'btn-success' : 'btn-outline-success'; ?>">
                            <i class="fas fa-calendar-day"></i> รายวัน
                        </a>
                        <a href="?period=<?php echo $period; ?>&sales_period=weekly" class="btn btn-sm <?php echo $sales_period == 'weekly' ? 'btn-success' : 'btn-outline-success'; ?>">
                            <i class="fas fa-calendar-week"></i> รายสัปดาห์
                        </a>
                        <a href="?period=<?php echo $period; ?>&sales_period=monthly" class="btn btn-sm <?php echo $sales_period == 'monthly' ? 'btn-success' : 'btn-outline-success'; ?>">
                            <i class="fas fa-calendar-alt"></i> รายเดือน
                        </a>
                        <a href="?period=<?php echo $period; ?>&sales_period=yearly" class="btn btn-sm <?php echo $sales_period == 'yearly' ? 'btn-success' : 'btn-outline-success'; ?>">
                            <i class="fas fa-calendar"></i> รายปี
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- กราฟสถิติการใช้งาน -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-bar"></i> <?php echo $chart_title; ?></span>
                    <div class="btn-group" role="group">
                        <a href="?period=daily&sales_period=<?php echo $sales_period; ?>" class="btn btn-sm <?php echo $period == 'daily' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                            <i class="fas fa-calendar-day"></i> รายวัน
                        </a>
                        <a href="?period=weekly&sales_period=<?php echo $sales_period; ?>" class="btn btn-sm <?php echo $period == 'weekly' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                            <i class="fas fa-calendar-week"></i> รายสัปดาห์
                        </a>
                        <a href="?period=monthly&sales_period=<?php echo $sales_period; ?>" class="btn btn-sm <?php echo $period == 'monthly' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                            <i class="fas fa-calendar-alt"></i> รายเดือน
                        </a>
                        <a href="?period=yearly&sales_period=<?php echo $sales_period; ?>" class="btn btn-sm <?php echo $period == 'yearly' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                            <i class="fas fa-calendar"></i> รายปี
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="statsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// กราฟสถิติตามช่วงเวลาที่เลือก
const ctx = document.getElementById('statsChart').getContext('2d');
const statsData = <?php echo json_encode($stats_data); ?>;
const period = '<?php echo $period; ?>';

let labels = [];
let data = [];

// จัดรูปแบบข้อมูลตามช่วงเวลา
switch (period) {
    case 'daily':
        labels = statsData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
        }).reverse();
        data = statsData.map(item => parseInt(item.total_activities)).reverse();
        break;
        
    case 'weekly':
        labels = statsData.map(item => {
            const weekNum = item.week.split('W')[1];
            const year = item.week.split('W')[0];
            return `สัปดาห์ที่ ${weekNum} ${year}`;
        }).reverse();
        data = statsData.map(item => parseInt(item.total_activities)).reverse();
        break;
        
    case 'yearly':
        labels = statsData.map(item => {
            return `ปี ${parseInt(item.year) + 543}`; // แปลงเป็น พ.ศ.
        }).reverse();
        data = statsData.map(item => parseInt(item.total_activities)).reverse();
        break;
        
    default: // monthly
        labels = statsData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short' });
        }).reverse();
        data = statsData.map(item => parseInt(item.total_activities)).reverse();
        break;
}

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'จำนวนกิจกรรม',
            data: data,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#dc3545',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#dc3545',
                borderWidth: 1
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                },
                ticks: {
                    color: '#666'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#666'
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});

// กราฟสถิติการขาย
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesData = <?php echo json_encode($sales_data); ?>;
const salesPeriod = '<?php echo $sales_period; ?>';

let salesLabels = [];
let salesAmounts = [];
let salesOrders = [];

// จัดรูปแบบข้อมูลการขายตามช่วงเวลา
switch (salesPeriod) {
    case 'daily':
        salesLabels = salesData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
        }).reverse();
        salesAmounts = salesData.map(item => parseFloat(item.total_sales)).reverse();
        salesOrders = salesData.map(item => parseInt(item.total_orders)).reverse();
        break;
        
    case 'weekly':
        salesLabels = salesData.map(item => {
            const weekNum = item.week.split('W')[1];
            const year = item.week.split('W')[0];
            return `สัปดาห์ที่ ${weekNum} ${year}`;
        }).reverse();
        salesAmounts = salesData.map(item => parseFloat(item.total_sales)).reverse();
        salesOrders = salesData.map(item => parseInt(item.total_orders)).reverse();
        break;
        
    case 'yearly':
        salesLabels = salesData.map(item => {
            return `ปี ${parseInt(item.year) + 543}`; // แปลงเป็น พ.ศ.
        }).reverse();
        salesAmounts = salesData.map(item => parseFloat(item.total_sales)).reverse();
        salesOrders = salesData.map(item => parseInt(item.total_orders)).reverse();
        break;
        
    default: // monthly
        salesLabels = salesData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short' });
        }).reverse();
        salesAmounts = salesData.map(item => parseFloat(item.total_sales)).reverse();
        salesOrders = salesData.map(item => parseInt(item.total_orders)).reverse();
        break;
}

new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'รายได้ (บาท)',
            data: salesAmounts,
            backgroundColor: 'rgba(40, 167, 69, 0.8)',
            borderColor: '#28a745',
            borderWidth: 2,
            yAxisID: 'y'
        }, {
            label: 'จำนวนออเดอร์',
            data: salesOrders,
            type: 'line',
            backgroundColor: 'rgba(255, 193, 7, 0.2)',
            borderColor: '#ffc107',
            borderWidth: 3,
            fill: false,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#28a745',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        if (context.datasetIndex === 0) {
                            return 'รายได้: ฿' + context.parsed.y.toLocaleString('th-TH');
                        } else {
                            return 'จำนวนออเดอร์: ' + context.parsed.y + ' รายการ';
                        }
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                },
                ticks: {
                    color: '#666',
                    callback: function(value) {
                        return '฿' + value.toLocaleString('th-TH');
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false,
                },
                ticks: {
                    color: '#666'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#666'
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    }
});
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
        case 'cart_add': return 'cart-plus';
        case 'cart_update': return 'edit';
        case 'cart_remove': return 'trash';
        default: return 'circle';
    }
}
?>