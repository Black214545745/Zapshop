<?php
session_start();
require_once 'config.php';

class ProductRecommendations {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * ระบบแนะนำสินค้าอัจฉริยะ - AI-based recommendations
     */
    public function getRecommendations($userId = null, $limit = 8) {
        $recommendations = [];
        
        // 1. Collaborative Filtering - วิเคราะห์พฤติกรรมการซื้อ
        $collaborativeRecs = $this->getCollaborativeRecommendations($userId, $limit);
        $recommendations = array_merge($recommendations, $collaborativeRecs);
        
        // 2. Content-based Filtering - วิเคราะห์คุณสมบัติสินค้า
        $contentRecs = $this->getContentBasedRecommendations($userId, $limit);
        $recommendations = array_merge($recommendations, $contentRecs);
        
        // 3. Popular Products - สินค้าขายดี
        $popularRecs = $this->getPopularProducts($limit);
        $recommendations = array_merge($recommendations, $popularRecs);
        
        // 4. New Arrivals - สินค้าใหม่
        $newRecs = $this->getNewArrivals($limit);
        $recommendations = array_merge($recommendations, $newRecs);
        
        // 5. Trending Products - สินค้าที่กำลังเป็นที่นิยม
        $trendingRecs = $this->getTrendingProducts($limit);
        $recommendations = array_merge($recommendations, $trendingRecs);
        
        // ลบสินค้าซ้ำและจำกัดจำนวน
        $uniqueRecs = $this->removeDuplicates($recommendations);
        return array_slice($uniqueRecs, 0, $limit);
    }
    
    /**
     * Collaborative Filtering - วิเคราะห์พฤติกรรมการซื้อของผู้ใช้
     */
    private function getCollaborativeRecommendations($userId, $limit) {
        if (!$userId) return [];
        
        try {
            // หาสินค้าที่ผู้ใช้เคยซื้อ
            $stmt = $this->conn->prepare("
                SELECT DISTINCT p.category_id, p.supplier_id
                FROM order_details od
                JOIN products p ON od.product_id = p.id
                JOIN orders o ON od.order_id = o.id
                WHERE o.user_id = ? AND o.status = 'completed'
            ");
            $stmt->execute([$userId]);
            $userPreferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($userPreferences)) return [];
            
            // หาสินค้าที่คล้ายกันจากผู้ใช้อื่น
            $categoryIds = array_column($userPreferences, 'category_id');
            $supplierIds = array_column($userPreferences, 'supplier_id');
            
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            $supplierPlaceholders = str_repeat('?,', count($supplierIds) - 1) . '?';
            
            $stmt = $this->conn->prepare("
                SELECT p.*, 
                       COUNT(od.id) as purchase_count,
                       AVG(od.quantity) as avg_quantity
                FROM products p
                LEFT JOIN order_details od ON p.id = od.product_id
                LEFT JOIN orders o ON od.order_id = o.id
                WHERE (p.category_id IN ($placeholders) OR p.supplier_id IN ($supplierPlaceholders))
                AND p.id NOT IN (
                    SELECT DISTINCT od2.product_id
                    FROM order_details od2
                    JOIN orders o2 ON od2.order_id = o2.id
                    WHERE o2.user_id = ?
                )
                AND p.stock_quantity > 0
                GROUP BY p.id
                ORDER BY purchase_count DESC, avg_quantity DESC
                LIMIT ?
            ");
            
            $params = array_merge($categoryIds, $supplierIds, [$userId, $limit]);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Collaborative filtering error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Content-based Filtering - วิเคราะห์คุณสมบัติสินค้า
     */
    private function getContentBasedRecommendations($userId, $limit) {
        if (!$userId) return [];
        
        try {
            // หาสินค้าที่ผู้ใช้เคยดูหรือซื้อ
            $stmt = $this->conn->prepare("
                SELECT p.category_id, p.price, p.stock_quantity
                FROM order_details od
                JOIN products p ON od.product_id = p.id
                JOIN orders o ON od.order_id = o.id
                WHERE o.user_id = ? AND o.status = 'completed'
                ORDER BY o.created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            $userHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($userHistory)) return [];
            
            // คำนวณค่าเฉลี่ยของราคาและหมวดหมู่ที่ชอบ
            $avgPrice = array_sum(array_column($userHistory, 'price')) / count($userHistory);
            $preferredCategories = array_count_values(array_column($userHistory, 'category_id'));
            arsort($preferredCategories);
            $topCategories = array_slice(array_keys($preferredCategories), 0, 3);
            
            $placeholders = str_repeat('?,', count($topCategories) - 1) . '?';
            
            $stmt = $this->conn->prepare("
                SELECT p.*,
                       ABS(p.price - ?) as price_diff,
                       CASE WHEN p.category_id IN ($placeholders) THEN 1 ELSE 0 END as category_match
                FROM products p
                WHERE p.id NOT IN (
                    SELECT DISTINCT od2.product_id
                    FROM order_details od2
                    JOIN orders o2 ON od2.order_id = o2.id
                    WHERE o2.user_id = ?
                )
                AND p.stock_quantity > 0
                ORDER BY category_match DESC, price_diff ASC
                LIMIT ?
            ");
            
            $params = array_merge([$avgPrice], $topCategories, [$userId, $limit]);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Content-based filtering error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * สินค้าขายดี - Popular Products
     */
    private function getPopularProducts($limit) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, 
                       COUNT(od.id) as order_count,
                       SUM(od.quantity) as total_quantity
                FROM products p
                LEFT JOIN order_details od ON p.id = od.product_id
                LEFT JOIN orders o ON od.order_id = o.id
                WHERE o.status = 'completed' OR o.status IS NULL
                AND p.stock_quantity > 0
                GROUP BY p.id
                ORDER BY order_count DESC, total_quantity DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Popular products error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * สินค้าใหม่ - New Arrivals
     */
    private function getNewArrivals($limit) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*
                FROM products p
                WHERE p.stock_quantity > 0
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("New arrivals error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * สินค้าที่กำลังเป็นที่นิยม - Trending Products
     */
    private function getTrendingProducts($limit) {
        try {
            // สินค้าที่ขายดีในช่วง 7 วันล่าสุด
            $stmt = $this->conn->prepare("
                SELECT p.*, 
                       COUNT(od.id) as recent_orders,
                       SUM(od.quantity) as recent_quantity
                FROM products p
                LEFT JOIN order_details od ON p.id = od.product_id
                LEFT JOIN orders o ON od.order_id = o.id
                WHERE (o.created_at >= NOW() - INTERVAL '7 days' OR o.created_at IS NULL)
                AND p.stock_quantity > 0
                GROUP BY p.id
                ORDER BY recent_orders DESC, recent_quantity DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Trending products error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * ลบสินค้าซ้ำออก
     */
    private function removeDuplicates($products) {
        $seen = [];
        $unique = [];
        
        foreach ($products as $product) {
            if (!isset($seen[$product['id']])) {
                $seen[$product['id']] = true;
                $unique[] = $product;
            }
        }
        
        return $unique;
    }
    
    /**
     * วิเคราะห์พฤติกรรมการซื้อของผู้ใช้
     */
    public function analyzeUserBehavior($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT o.id) as total_orders,
                    SUM(od.quantity) as total_items,
                    AVG(od.quantity) as avg_items_per_order,
                    COUNT(DISTINCT od.product_id) as unique_products,
                    AVG(od.unit_price) as avg_order_value
                FROM orders o
                JOIN order_details od ON o.id = od.order_id
                WHERE o.user_id = ? AND o.status = 'completed'
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("User behavior analysis error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * สร้างรายงานการวิเคราะห์
     */
    public function generateRecommendationReport($userId = null) {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'recommendations' => [],
            'user_behavior' => null,
            'system_performance' => []
        ];
        
        if ($userId) {
            $report['user_behavior'] = $this->analyzeUserBehavior($userId);
        }
        
        // ทดสอบประสิทธิภาพระบบ
        $startTime = microtime(true);
        $recommendations = $this->getRecommendations($userId, 20);
        $endTime = microtime(true);
        
        $report['system_performance'] = [
            'execution_time' => round(($endTime - $startTime) * 1000, 2) . 'ms',
            'recommendations_generated' => count($recommendations),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB'
        ];
        
        $report['recommendations'] = $recommendations;
        
        return $report;
    }
}

// ใช้งานระบบแนะนำสินค้า
$recommendations = new ProductRecommendations($conn);
$userId = $_SESSION['user_id'] ?? null;

// ดึงข้อมูลแนะนำสินค้า
$userRecommendations = $recommendations->getRecommendations($userId, 12);
$popularProducts = $recommendations->getPopularProducts(6);
$newArrivals = $recommendations->getNewArrivals(6);
$trendingProducts = $recommendations->getTrendingProducts(6);

// วิเคราะห์พฤติกรรมผู้ใช้ (ถ้ามีการล็อกอิน)
$userBehavior = null;
if ($userId) {
    $userBehavior = $recommendations->analyzeUserBehavior($userId);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบแนะนำสินค้าอัจฉริยะ - ZapShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/design-system.css" rel="stylesheet">
    <style>
        .recommendation-section {
            margin-bottom: 3rem;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .section-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-right: 0.75rem;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: var(--surface-color);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
            overflow: hidden;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-image {
            height: 200px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        
        .product-info {
            padding: 1.5rem;
        }
        
        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .recommendation-badge {
            background: linear-gradient(135deg, var(--success-color), var(--info-color));
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-full);
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .ai-insights {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
        }
        
        .insight-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .insight-icon {
            font-size: 1.25rem;
            margin-right: 1rem;
            opacity: 0.9;
        }
        
        .behavior-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--surface-color);
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
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
                    <i class="fas fa-brain me-3"></i>
                    ระบบแนะนำสินค้าอัจฉริยะ
                </h1>
                <p class="lead text-muted">
                    ใช้ AI วิเคราะห์พฤติกรรมการซื้อและแนะนำสินค้าที่เหมาะกับคุณ
                </p>
            </div>
            
            <!-- AI Insights -->
            <div class="ai-insights">
                <h3 class="mb-3">
                    <i class="fas fa-lightbulb me-2"></i>
                    AI Insights
                </h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="insight-item">
                            <i class="fas fa-chart-line insight-icon"></i>
                            <span>วิเคราะห์พฤติกรรมการซื้อของคุณ</span>
                        </div>
                        <div class="insight-item">
                            <i class="fas fa-users insight-icon"></i>
                            <span>เปรียบเทียบกับผู้ใช้อื่น</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="insight-item">
                            <i class="fas fa-tags insight-icon"></i>
                            <span>แนะนำสินค้าที่คล้ายกัน</span>
                        </div>
                        <div class="insight-item">
                            <i class="fas fa-star insight-icon"></i>
                            <span>สินค้าที่กำลังเป็นที่นิยม</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Behavior Stats (ถ้ามีการล็อกอิน) -->
            <?php if ($userBehavior): ?>
            <div class="behavior-stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $userBehavior['total_orders'] ?></div>
                    <div class="stat-label">คำสั่งซื้อทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $userBehavior['total_items'] ?></div>
                    <div class="stat-label">สินค้าที่ซื้อ</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= round($userBehavior['avg_items_per_order'], 1) ?></div>
                    <div class="stat-label">สินค้าเฉลี่ยต่อคำสั่ง</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $userBehavior['unique_products'] ?></div>
                    <div class="stat-label">สินค้าที่ไม่ซ้ำ</div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Personalized Recommendations -->
            <?php if ($userId && !empty($userRecommendations)): ?>
            <div class="recommendation-section">
                <div class="section-header">
                    <i class="fas fa-user-check section-icon"></i>
                    <h2>สินค้าแนะนำสำหรับคุณ</h2>
                </div>
                <div class="product-grid">
                    <?php foreach (array_slice($userRecommendations, 0, 6) as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-price">฿<?= number_format($product['price'], 2) ?></div>
                            <div class="product-meta">
                                <span>สต็อก: <?= $product['stock_quantity'] ?></span>
                                <span class="recommendation-badge">AI แนะนำ</span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>ดูรายละเอียด
                                </a>
                                <button class="btn btn-outline-primary" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>เพิ่มลงตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Popular Products -->
            <div class="recommendation-section">
                <div class="section-header">
                    <i class="fas fa-fire section-icon"></i>
                    <h2>สินค้าขายดี</h2>
                </div>
                <div class="product-grid">
                    <?php foreach ($popularProducts as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-price">฿<?= number_format($product['price'], 2) ?></div>
                            <div class="product-meta">
                                <span>สต็อก: <?= $product['stock_quantity'] ?></span>
                                <span class="recommendation-badge">ขายดี</span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>ดูรายละเอียด
                                </a>
                                <button class="btn btn-outline-primary" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>เพิ่มลงตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- New Arrivals -->
            <div class="recommendation-section">
                <div class="section-header">
                    <i class="fas fa-star section-icon"></i>
                    <h2>สินค้าใหม่</h2>
                </div>
                <div class="product-grid">
                    <?php foreach ($newArrivals as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-price">฿<?= number_format($product['price'], 2) ?></div>
                            <div class="product-meta">
                                <span>สต็อก: <?= $product['stock_quantity'] ?></span>
                                <span class="recommendation-badge">ใหม่</span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>ดูรายละเอียด
                                </a>
                                <button class="btn btn-outline-primary" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>เพิ่มลงตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Trending Products -->
            <div class="recommendation-section">
                <div class="section-header">
                    <i class="fas fa-trending-up section-icon"></i>
                    <h2>สินค้าที่กำลังเป็นที่นิยม</h2>
                </div>
                <div class="product-grid">
                    <?php foreach ($trendingProducts as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="product-info">
                            <div class="product-title"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-price">฿<?= number_format($product['price'], 2) ?></div>
                            <div class="product-meta">
                                <span>สต็อก: <?= $product['stock_quantity'] ?></span>
                                <span class="recommendation-badge">กำลังนิยม</span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="product-detail.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye me-2"></i>ดูรายละเอียด
                                </a>
                                <button class="btn btn-outline-primary" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="fas fa-cart-plus me-2"></i>เพิ่มลงตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme-manager.js"></script>
    <script>
        // เพิ่มลงตะกร้า
        function addToCart(productId) {
            // เรียกใช้ฟังก์ชันเพิ่มลงตะกร้าที่มีอยู่
            if (typeof addToCartFromRecommendations === 'function') {
                addToCartFromRecommendations(productId);
            } else {
                // Fallback: ใช้ AJAX เพิ่มลงตะกร้า
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('เพิ่มลงตะกร้าแล้ว!', 'success');
                    } else {
                        showToast('เกิดข้อผิดพลาด: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                });
            }
        }
        
        // แสดง Toast notification
        function showToast(message, type = 'info') {
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
            } else {
                alert(message);
            }
        }
        
        // เพิ่ม animation เมื่อ scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, observerOptions);
        
        // Observe all product cards
        document.querySelectorAll('.product-card').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
