<?php
session_start();
include 'config.php';

// ดึงข้อมูลหมวดหมู่ทั้งหมด
$categories = [];
try {
    $conn = getConnection();
    $category_query = "SELECT id, name, description FROM categories ORDER BY name";
    $category_result = pg_query($conn, $category_query);
    if ($category_result) {
        while ($row = pg_fetch_assoc($category_result)) {
            $categories[] = $row;
        }
    }
    pg_close($conn);
} catch (Exception $e) {
    error_log("Category error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แท็บเมนูหมวดหมู่ - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        
        .page-header {
            background: linear-gradient(135deg, #e31837 0%, #c41230 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .category-tabs-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .category-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .category-tab {
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: 10px;
            padding: 15px 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: 150px;
        }
        
        .category-tab:hover {
            background: white;
            border-color: #ff6b35;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .category-tab.active {
            background: linear-gradient(135deg, #e31837 0%, #c41230 100%);
            color: white;
            border-color: #e31837;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(227, 24, 55, 0.3);
        }
        
        .category-tab-icon {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
        
        .category-tab-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .category-tab-desc {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .category-content {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            min-height: 400px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .stat-icon {
            font-size: 2rem;
            color: #e31837;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #e31837;
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .category-tabs {
                gap: 10px;
            }
            
            .category-tab {
                min-width: 120px;
                padding: 12px 20px;
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1 class="display-4 fw-bold">🏷️ แท็บเมนูหมวดหมู่สินค้า</h1>
        <p class="lead">เลือกหมวดหมู่สินค้าที่คุณสนใจ</p>
    </div>
</div>

<div class="container">
    <!-- Category Tabs Container -->
    <div class="category-tabs-container">
        <div class="text-center mb-4">
            <h2 class="text-danger">หมวดหมู่สินค้าทั้งหมด</h2>
            <p class="text-muted">คลิกที่แท็บเพื่อดูรายละเอียดของแต่ละหมวดหมู่</p>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs">
            <?php foreach ($categories as $index => $category): ?>
                <div class="category-tab <?php echo ($index === 0) ? 'active' : ''; ?>" 
                     data-category-name="<?php echo htmlspecialchars($category['name']); ?>"
                     data-category-desc="<?php echo htmlspecialchars($category['description']); ?>">
                    
                    <?php
                    // กำหนดไอคอนตามชื่อหมวดหมู่
                    $icon = '📦';
                    $name = strtolower($category['name']);
                    
                    if (strpos($name, 'โทรศัพท์') !== false) $icon = '📱';
                    elseif (strpos($name, 'คอมพิวเตอร์') !== false) $icon = '💻';
                    elseif (strpos($name, 'กีฬา') !== false) $icon = '⚽';
                    elseif (strpos($name, 'หนังสือ') !== false) $icon = '📚';
                    elseif (strpos($name, 'ของเล่น') !== false) $icon = '🧸';
                    elseif (strpos($name, 'เครื่องสำอาง') !== false) $icon = '💄';
                    elseif (strpos($name, 'สุขภาพ') !== false) $icon = '💊';
                    elseif (strpos($name, 'รถยนต์') !== false) $icon = '🚗';
                    elseif (strpos($name, 'บ้าน') !== false) $icon = '🏠';
                    elseif (strpos($name, 'เครื่องดื่ม') !== false) $icon = '🥤';
                    elseif (strpos($name, 'ขนม') !== false) $icon = '🍰';
                    elseif (strpos($name, 'เสื้อผ้า') !== false) $icon = '👕';
                    elseif (strpos($name, 'เกม') !== false) $icon = '🎮';
                    elseif (strpos($name, 'เทคโนโลยี') !== false) $icon = '🚀';
                    ?>
                    
                    <span class="category-tab-icon"><?php echo $icon; ?></span>
                    <div class="category-tab-name"><?php echo htmlspecialchars($category['name']); ?></div>
                    <div class="category-tab-desc"><?php echo htmlspecialchars($category['description']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Category Content -->
        <div class="category-content">
            <div class="text-center mb-4">
                <h3 class="text-danger" id="content-title">เลือกหมวดหมู่สินค้า</h3>
                <p class="text-muted" id="content-desc">คลิกที่แท็บด้านบนเพื่อดูรายละเอียด</p>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-number"><?php echo count($categories); ?></div>
                        <div class="text-muted">หมวดหมู่ทั้งหมด</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon">🛍️</div>
                        <div class="stat-number">0</div>
                        <div class="text-muted">สินค้าในหมวดหมู่</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-number">฿0</div>
                        <div class="text-muted">มูลค่ารวม</div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                <a href="product-list1.php" class="btn btn-danger btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>ดูสินค้าทั้งหมด
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryTabs = document.querySelectorAll('.category-tab');
    const contentTitle = document.getElementById('content-title');
    const contentDesc = document.getElementById('content-desc');

    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // ลบ active class จากแท็บทั้งหมด
            categoryTabs.forEach(t => t.classList.remove('active'));
            
            // เพิ่ม active class ให้แท็บที่คลิก
            this.classList.add('active');
            
            // อัปเดตเนื้อหา
            const categoryName = this.getAttribute('data-category-name');
            const categoryDesc = this.getAttribute('data-category-desc');
            
            contentTitle.textContent = categoryName;
            contentDesc.textContent = categoryDesc;
        });
    });
});
</script>
</body>
</html>
