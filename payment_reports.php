<?php
/**
 * ระบบรายงานและสถิติการชำระเงิน
 * แสดงข้อมูลสถิติและรายงานต่างๆ
 */

session_start();
require_once 'config.php';

// ฟังก์ชันสำหรับดึงข้อมูลสถิติ
function getPaymentStatistics($startDate = null, $endDate = null) {
    global $pdo;
    
    try {
        $whereClause = "";
        $params = [];
        
        if ($startDate && $endDate) {
            $whereClause = "WHERE p.created_at BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }
        
        $sql = "
            SELECT 
                COUNT(*) as total_transactions,
                SUM(CASE WHEN p.status = 'success' THEN 1 ELSE 0 END) as successful_payments,
                SUM(CASE WHEN p.status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
                SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                SUM(CASE WHEN p.status = 'success' THEN p.amount ELSE 0 END) as total_amount,
                AVG(CASE WHEN p.status = 'success' THEN p.amount ELSE NULL END) as average_amount
            FROM payments p
            {$whereClause}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [
            'total_transactions' => 0,
            'successful_payments' => 0,
            'failed_payments' => 0,
            'pending_payments' => 0,
            'total_amount' => 0,
            'average_amount' => 0
        ];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลรายการล่าสุด
function getRecentPayments($limit = 10) {
    global $pdo;
    
    try {
        $sql = "
            SELECT 
                p.*,
                o.order_number,
                u.username
            FROM payments p
            LEFT JOIN orders o ON p.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลสถิติรายวัน
function getDailyStatistics($days = 30) {
    global $pdo;
    
    try {
        $sql = "
            SELECT 
                DATE(p.created_at) as date,
                COUNT(*) as transactions,
                SUM(CASE WHEN p.status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN p.status = 'success' THEN p.amount ELSE 0 END) as amount
            FROM payments p
            WHERE p.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(p.created_at)
            ORDER BY date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$days]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลสถิติตาม Provider
function getProviderStatistics() {
    global $pdo;
    
    try {
        $sql = "
            SELECT 
                p.provider,
                COUNT(*) as transactions,
                SUM(CASE WHEN p.status = 'success' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN p.status = 'success' THEN p.amount ELSE 0 END) as amount
            FROM payments p
            GROUP BY p.provider
            ORDER BY transactions DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ดึงข้อมูลสถิติ
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$statistics = getPaymentStatistics($startDate, $endDate);
$recentPayments = getRecentPayments(10);
$dailyStats = getDailyStatistics(30);
$providerStats = getProviderStatistics();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการชำระเงิน - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid mt-4">
        <h1><i class="fas fa-chart-line me-2"></i>รายงานการชำระเงิน</h1>
        
        <!-- ฟิลเตอร์วันที่ -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>ดูรายงาน
                            </button>
                            <a href="payment_reports.php" class="btn btn-secondary">
                                <i class="fas fa-refresh me-2"></i>รีเซ็ต
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- สถิติโดยรวม -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <h4><?php echo number_format($statistics['total_transactions']); ?></h4>
                        <p class="mb-0">รายการทั้งหมด</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h4><?php echo number_format($statistics['successful_payments']); ?></h4>
                        <p class="mb-0">สำเร็จ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4><?php echo number_format($statistics['failed_payments']); ?></h4>
                        <p class="mb-0">ล้มเหลว</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4><?php echo number_format($statistics['pending_payments']); ?></h4>
                        <p class="mb-0">รอดำเนินการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4>฿<?php echo number_format($statistics['total_amount'], 0); ?></h4>
                        <p class="mb-0">ยอดรวม</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <h4>฿<?php echo number_format($statistics['average_amount'], 0); ?></h4>
                        <p class="mb-0">เฉลี่ยต่อรายการ</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- กราฟและสถิติ -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-area me-2"></i>สถิติรายวัน</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-pie me-2"></i>สถิติตาม Provider</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="providerChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- รายการล่าสุด -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list me-2"></i>รายการล่าสุด</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>Order ID</th>
                                        <th>ผู้ใช้</th>
                                        <th>Provider</th>
                                        <th>จำนวนเงิน</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPayments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td><?php echo $payment['order_number'] ?? 'N/A'; ?></td>
                                            <td><?php echo $payment['username'] ?? 'N/A'; ?></td>
                                            <td><?php echo $payment['provider'] ?? 'N/A'; ?></td>
                                            <td>฿<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                switch ($payment['status']) {
                                                    case 'success':
                                                        $statusClass = 'badge bg-success';
                                                        $statusText = 'สำเร็จ';
                                                        break;
                                                    case 'failed':
                                                        $statusClass = 'badge bg-danger';
                                                        $statusText = 'ล้มเหลว';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'badge bg-warning';
                                                        $statusText = 'รอดำเนินการ';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge bg-secondary';
                                                        $statusText = $payment['status'];
                                                }
                                                ?>
                                                <span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <hr>
        <p><a href="payment_gateway_dashboard.php" class="btn btn-secondary">กลับไป Payment Gateway Dashboard</a></p>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // กราฟสถิติรายวัน
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        const dailyChart = new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($dailyStats), 'date')); ?>,
                datasets: [{
                    label: 'จำนวนรายการ',
                    data: <?php echo json_encode(array_column(array_reverse($dailyStats), 'transactions')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'ยอดรวม (พันบาท)',
                    data: <?php echo json_encode(array_map(function($item) { return $item['amount'] / 1000; }, array_reverse($dailyStats))); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
        
        // กราฟสถิติตาม Provider
        const providerCtx = document.getElementById('providerChart').getContext('2d');
        const providerChart = new Chart(providerCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($providerStats, 'provider')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($providerStats, 'transactions')); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
