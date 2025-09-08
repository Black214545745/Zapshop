<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit();
}

$product_id = isset($_GET['id']) ? $_GET['id'] : null;
$user_id = $_SESSION['user_id'];

if (!$product_id) {
    header('Location: index.php');
    exit();
}

// ดึงข้อมูลสินค้า
$product = getProductById($product_id);

// ดึงรีวิวทั้งหมดของสินค้านี้
$reviews = getProductReviews($product_id);

// จัดการการส่งรีวิว
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    
    if (addProductReview($user_id, $product_id, $rating, $comment)) {
        header("Location: product_reviews.php?id=$product_id&success=1");
        exit();
    } else {
        $error = "เกิดข้อผิดพลาดในการเพิ่มรีวิว";
    }
}

// ตรวจสอบว่าผู้ใช้เคยรีวิวสินค้านี้แล้วหรือไม่
$user_reviewed = checkUserReviewed($user_id, $product_id);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีวิวสินค้า - <?php echo htmlspecialchars($product['name']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #fd7e14;
            --accent-color: #ffc107;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--light-color);
        }

        .review-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .product-info {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .product-image {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 3px solid rgba(255,255,255,0.3);
        }

        .rating-stars {
            font-size: 1.5rem;
            color: var(--accent-color);
            margin: 10px 0;
        }

        .star {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .star:hover {
            transform: scale(1.2);
        }

        .star.active {
            color: var(--accent-color);
        }

        .star.inactive {
            color: #e9ecef;
        }

        .review-form {
            background: var(--light-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .review-item {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reviewer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .review-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .review-rating {
            color: var(--accent-color);
            font-size: 1.1rem;
        }

        .review-comment {
            color: var(--dark-color);
            line-height: 1.6;
        }

        .btn-submit-review {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-submit-review:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }

        .btn-back {
            background: var(--dark-color);
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #495057;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .success-message {
            background: var(--success-color);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .review-container {
                padding: 20px;
            }
            
            .product-info {
                padding: 20px;
            }
            
            .review-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- กลับไปหน้าสินค้า -->
            <div class="mb-4">
                <a href="product-detail.php?id=<?php echo $product_id; ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> กลับไปหน้าสินค้า
                </a>
            </div>

            <!-- ข้อความแจ้งเตือน -->
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> เพิ่มรีวิวสำเร็จแล้ว!
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- ข้อมูลสินค้า -->
            <div class="product-info">
                <img src="<?php echo !empty($product['image_url']) ? 'upload_image/' . htmlspecialchars($product['image_url']) : 'https://placehold.co/120x120/cccccc/333333?text=No+Image'; ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p class="mb-0"><?php echo htmlspecialchars($product['description'] ?: 'ไม่มีรายละเอียด'); ?></p>
            </div>

            <!-- สถิติรีวิว -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($reviews); ?></div>
                        <div class="stats-label">รีวิวทั้งหมด</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number">
                            <?php 
                            if (count($reviews) > 0) {
                                $total_rating = 0;
                                foreach ($reviews as $review) {
                                    $total_rating += $review['rating'];
                                }
                                echo number_format($total_rating / count($reviews), 1);
                            } else {
                                echo "0.0";
                            }
                            ?>
                        </div>
                        <div class="stats-label">คะแนนเฉลี่ย</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $product['current_stock']; ?></div>
                        <div class="stats-label">สินค้าคงเหลือ</div>
                    </div>
                </div>
            </div>

            <!-- ฟอร์มรีวิว -->
            <?php if (!$user_reviewed): ?>
                <div class="review-container">
                    <h3><i class="fas fa-edit"></i> เขียนรีวิวสินค้า</h3>
                    <form method="POST" class="review-form">
                        <div class="mb-3">
                            <label class="form-label">คะแนน</label>
                            <div class="rating-stars" id="ratingStars">
                                <i class="fas fa-star star" data-rating="1"></i>
                                <i class="fas fa-star star" data-rating="2"></i>
                                <i class="fas fa-star star" data-rating="3"></i>
                                <i class="fas fa-star star" data-rating="4"></i>
                                <i class="fas fa-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="selectedRating" value="5" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comment" class="form-label">ความคิดเห็น</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" 
                                      placeholder="แบ่งปันประสบการณ์การใช้งานสินค้านี้..." required></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" name="submit_review" class="btn btn-submit-review">
                                <i class="fas fa-paper-plane"></i> ส่งรีวิว
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="review-container">
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4 class="text-success">คุณได้รีวิวสินค้านี้แล้ว</h4>
                        <p class="text-muted">ขอบคุณสำหรับรีวิวของคุณ</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- รายการรีวิว -->
            <div class="review-container">
                <h3><i class="fas fa-comments"></i> รีวิวจากลูกค้า</h3>
                
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?php echo strtoupper(substr($review['username'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($review['username']); ?></div>
                                        <div class="review-date">
                                            <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : 'inactive'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-comment">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">ยังไม่มีรีวิว</h4>
                        <p class="text-muted">เป็นคนแรกที่รีวิวสินค้านี้!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // ระบบ Rating Stars
    const stars = document.querySelectorAll('.star');
    const selectedRatingInput = document.getElementById('selectedRating');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            selectedRatingInput.value = rating;
            
            // อัปเดตการแสดงผลดาว
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.add('active');
                    s.classList.remove('inactive');
                } else {
                    s.classList.remove('active');
                    s.classList.add('inactive');
                }
            });
        });
        
        // Hover effect
        star.addEventListener('mouseenter', function() {
            const rating = this.dataset.rating;
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#e9ecef';
                }
            });
        });
        
        star.addEventListener('mouseleave', function() {
            const rating = selectedRatingInput.value;
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#e9ecef';
                }
            });
        });
    });
    
    // Initialize stars
    stars.forEach((star, index) => {
        if (index < 5) {
            star.classList.add('active');
        }
    });
</script>
</body>
</html>
