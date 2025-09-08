<?php
session_start();
require_once 'config.php';

class InventoryManagement {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * ดึงข้อมูลสต็อกสินค้าทั้งหมด
     */
    public function getAllStock($filters = []) {
        try {
            $where = "WHERE 1=1";
            $params = [];
            
            // ฟิลเตอร์หมวดหมู่
            if (!empty($filters['category_id'])) {
                $where .= " AND p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            
            // ฟิลเตอร์สถานะสต็อก
            if (!empty($filters['stock_status'])) {
                switch ($filters['stock_status']) {
                    case 'low':
                        $where .= " AND p.stock_quantity <= p.reorder_level";
                        break;
                    case 'out':
                        $where .= " AND p.stock_quantity = 0";
                        break;
                    case 'available':
                        $where .= " AND p.stock_quantity > p.reorder_level";
                        break;
                }
            }
            
            // ฟิลเตอร์การค้นหา
            if (!empty($filters['search'])) {
                $where .= " AND (p.name ILIKE ? OR p.sku ILIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $stmt = $this->conn->prepare("
                SELECT 
                    p.*,
                    c.name as category_name,
                    s.name as supplier_name,
                    CASE 
                        WHEN p.stock_quantity = 0 THEN 'out'
                        WHEN p.stock_quantity <= p.reorder_level THEN 'low'
                        ELSE 'available'
                    END as stock_status,
                    (p.stock_quantity * p.price) as stock_value
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                $where
                ORDER BY p.stock_quantity ASC, p.name ASC
            ");
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get all stock error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ดึงข้อมูลสต็อกสินค้าที่ต่ำ
     */
    public function getLowStockItems($limit = 20) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.*,
                    c.name as category_name,
                    s.name as supplier_name,
                    (p.reorder_level - p.stock_quantity) as needed_quantity
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.stock_quantity <= p.reorder_level
                ORDER BY (p.reorder_level - p.stock_quantity) DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get low stock items error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * สร้างรายงานสต็อก
     */
    public function generateStockReport($reportType = 'summary') {
        try {
            switch ($reportType) {
                case 'summary':
                    return $this->getStockSummary();
                case 'low_stock':
                    return $this->getLowStockReport();
                case 'value':
                    return $this->getStockValueReport();
                default:
                    return $this->getStockSummary();
            }
        } catch (Exception $e) {
            error_log("Generate stock report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * รายงานสรุปสต็อก
     */
    private function getStockSummary() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN stock_quantity <= reorder_level AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN stock_quantity > reorder_level THEN 1 ELSE 0 END) as available,
                    SUM(stock_quantity) as total_quantity,
                    SUM(stock_quantity * price) as total_value
                FROM products
            ");
            
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get stock summary error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * รายงานสินค้าสต็อกต่ำ
     */
    private function getLowStockReport() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.name,
                    p.sku,
                    p.stock_quantity,
                    p.reorder_level,
                    (p.reorder_level - p.stock_quantity) as needed_quantity,
                    c.name as category_name,
                    s.name as supplier_name,
                    p.price,
                    (p.stock_quantity * p.price) as stock_value
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN suppliers s ON p.supplier_id = s.id
                WHERE p.stock_quantity <= p.reorder_level
                ORDER BY (p.reorder_level - p.stock_quantity) DESC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get low stock report error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * รายงานมูลค่าสต็อก
     */
    private function getStockValueReport() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    c.name as category_name,
                    COUNT(p.id) as product_count,
                    SUM(p.stock_quantity) as total_quantity,
                    SUM(p.stock_quantity * p.price) as total_value,
                    AVG(p.price) as avg_price
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                GROUP BY c.id, c.name
                ORDER BY total_value DESC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get stock value report error: " . $e->getMessage());
            return [];
        }
    }
}

// ใช้งานระบบจัดการสต็อก
$inventory = new InventoryManagement($conn);

// ดึงข้อมูลสำหรับแสดงผล
$stockSummary = $inventory->generateStockReport('summary');
$lowStockItems = $inventory->getLowStockItems(10);

// ฟิลเตอร์
$filters = [
    'category_id' => $_GET['category_id'] ?? null,
    'stock_status' => $_GET['stock_status'] ?? null,
    'search' => $_GET['search'] ?? null
];

$allStock = $inventory->getAllStock($filters);

// ดึงหมวดหมู่สำหรับฟิลเตอร์
$categories = [];
try {
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE is_active = true ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Get categories error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการสต็อกสินค้า - ZapShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/design-system.css" rel="stylesheet">
    <style>
        .dashboard-card {
            background: var(--surface-color);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stock-status {
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-full);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .stock-status.out {
            background: var(--danger-color);
            color: white;
        }
        
        .stock-status.low {
            background: var(--warning-color);
            color: white;
        }
        
        .stock-status.available {
            background: var(--success-color);
            color: white;
        }
        
        .filter-section {
            background: var(--surface-color);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .table-responsive {
            border-radius: var(--border-radius-lg);
            overflow: hidden;
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .alert-section {
            margin-bottom: 2rem;
        }
        
        .alert-card {
            border-left: 4px solid var(--warning-color);
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-radius: var(--border-radius-lg);
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'include/menu.php'; ?>
    
    <div class="container-fluid py-5">
        <div class="container">
            <!-- Header Section -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-gradient mb-3">
                    <i class="fas fa-boxes me-3"></i>
                    ระบบจัดการสต็อกสินค้า
                </h1>
                <p class="lead text-muted">
                    ติดตามและจัดการสต็อกสินค้าแบบ Real-time พร้อมระบบแจ้งเตือนอัจฉริยะ
                </p>
            </div>
            
            <!-- Stock Summary Dashboard -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number text-primary"><?= number_format($stockSummary['total_products'] ?? 0) ?></div>
                        <div class="stat-label">สินค้าทั้งหมด</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number text-success"><?= number_format($stockSummary['available'] ?? 0) ?></div>
                        <div class="stat-label">สต็อกเพียงพอ</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number text-warning"><?= number_format($stockSummary['low_stock'] ?? 0) ?></div>
                        <div class="stat-label">สต็อกต่ำ</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="dashboard-card text-center">
                        <div class="stat-number text-danger"><?= number_format($stockSummary['out_of_stock'] ?? 0) ?></div>
                        <div class="stat-label">หมดสต็อก</div>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock Alerts -->
            <?php if (!empty($lowStockItems)): ?>
            <div class="alert-section">
                <h3 class="mb-3">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    แจ้งเตือนสต็อกต่ำ
                </h3>
                <?php foreach (array_slice($lowStockItems, 0, 5) as $item): ?>
                <div class="alert-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                            <span class="badge bg-warning ms-2"><?= htmlspecialchars($item['sku']) ?></span>
                            <span class="text-muted ms-2">หมวดหมู่: <?= htmlspecialchars($item['category_name']) ?></span>
                        </div>
                        <div class="text-end">
                            <div class="text-danger fw-bold">
                                สต็อก: <?= number_format($item['stock_quantity']) ?> 
                                (ต้องการ: <?= number_format($item['needed_quantity']) ?>)
                            </div>
                            <small class="text-muted">ซัพพลายเออร์: <?= htmlspecialchars($item['supplier_name']) ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($lowStockItems) > 5): ?>
                <div class="text-center">
                    <a href="#low-stock-table" class="btn btn-outline-warning">
                        ดูทั้งหมด <?= count($lowStockItems) ?> รายการ
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filter-section">
                <h4 class="mb-3">
                    <i class="fas fa-filter me-2"></i>
                    ตัวกรองข้อมูล
                </h4>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">หมวดหมู่</label>
                        <select name="category_id" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= ($filters['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">สถานะสต็อก</label>
                        <select name="stock_status" class="form-select">
                            <option value="">ทั้งหมด</option>
                            <option value="available" <?= ($filters['stock_status'] == 'available') ? 'selected' : '' ?>>สต็อกเพียงพอ</option>
                            <option value="low" <?= ($filters['stock_status'] == 'low') ? 'selected' : '' ?>>สต็อกต่ำ</option>
                            <option value="out" <?= ($filters['stock_status'] == 'out') ? 'selected' : '' ?>>หมดสต็อก</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ค้นหา</label>
                        <input type="text" name="search" class="form-control" placeholder="ชื่อสินค้าหรือ SKU" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>ค้นหา
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Stock Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>สินค้า</th>
                            <th>SKU</th>
                            <th>หมวดหมู่</th>
                            <th>สต็อก</th>
                            <th>สถานะ</th>
                            <th>ราคา</th>
                            <th>มูลค่าสต็อก</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allStock as $product): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($product['supplier_name']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($product['sku']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($product['category_name']) ?></td>
                            <td>
                                <div class="fw-bold"><?= number_format($product['stock_quantity']) ?></div>
                                <small class="text-muted">Reorder: <?= number_format($product['reorder_level']) ?></small>
                            </td>
                            <td>
                                <span class="stock-status <?= $product['stock_status'] ?>">
                                    <?= ucfirst($product['stock_status']) ?>
                                </span>
                            </td>
                            <td>฿<?= number_format($product['price'], 2) ?></td>
                            <td>฿<?= number_format($product['stock_value'], 2) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline-primary" onclick="editStock(<?= $product['id'] ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewHistory(<?= $product['id'] ?>)">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <?php if ($product['stock_status'] == 'low' || $product['stock_status'] == 'out'): ?>
                                    <button class="btn btn-sm btn-outline-warning" onclick="createReorder(<?= $product['id'] ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // แก้ไขสต็อก
        function editStock(productId) {
            showToast('ฟีเจอร์นี้จะเปิดใช้งานในเวอร์ชันถัดไป', 'info');
        }
        
        // ดูประวัติสต็อก
        function viewHistory(productId) {
            showToast('ฟีเจอร์นี้จะเปิดใช้งานในเวอร์ชันถัดไป', 'info');
        }
        
        // สร้างคำสั่งซื้อใหม่
        function createReorder(productId) {
            showToast('ฟีเจอร์นี้จะเปิดใช้งานในเวอร์ชันถัดไป', 'info');
        }
        
        // แสดง Toast notification
        function showToast(message, type = 'info') {
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
            } else {
                alert(message);
            }
        }
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            // Refresh only if user is active
            if (!document.hidden) {
                location.reload();
            }
        }, 5 * 60 * 1000);
    </script>
</body>
</html>
