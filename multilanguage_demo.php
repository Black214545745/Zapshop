<?php
session_start();
require_once 'config.php';
require_once 'includes/language_manager.php';
?>

<!DOCTYPE html>
<html lang="<?= $languageManager->getCurrentLanguage() ?>" dir="<?= $languageManager->getCurrentDirection() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('multilanguage_demo_title', ['business_name' => __('business_name')]) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/design-system.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/6.6.6/css/flag-icons.min.css" rel="stylesheet">
    <?= $languageManager->renderLanguageStyles() ?>
    <style>
        .demo-section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: var(--surface-color);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .demo-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .language-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .translation-example {
            background: var(--light-color);
            padding: 1rem;
            border-radius: var(--border-radius-md);
            margin-bottom: 1rem;
        }
        
        .translation-key {
            font-family: monospace;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .translation-value {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: white;
            border-radius: var(--border-radius-sm);
            border-left: 3px solid var(--success-color);
        }
        
        .rtl-demo {
            direction: rtl;
            text-align: right;
            padding: 1rem;
            background: linear-gradient(135deg, var(--warning-color), var(--info-color));
            color: white;
            border-radius: var(--border-radius-lg);
            margin: 1rem 0;
        }
        
        .currency-demo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .currency-item {
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius-md);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        
        .date-demo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .date-item {
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius-md);
            text-align: center;
            box-shadow: var(--shadow-sm);
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .feature-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-normal);
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .code-example {
            background: var(--dark-color);
            color: var(--light-color);
            padding: 1rem;
            border-radius: var(--border-radius-md);
            font-family: monospace;
            margin: 1rem 0;
            overflow-x: auto;
        }
        
        .language-info {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
        }
        
        .current-language-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-full);
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="lang-<?= $languageManager->getCurrentLanguage() ?> direction-<?= $languageManager->getCurrentDirection() ?>">
    
    <!-- Language Switcher -->
    <div class="language-switcher">
        <?= $languageManager->renderLanguageSelector(true, true) ?>
    </div>
    
    <div class="container-fluid py-5">
        <div class="container">
            
            <!-- Header Section -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-gradient mb-3">
                    <i class="fas fa-globe me-3"></i>
                    <?= __('multilanguage_demo_title', ['business_name' => __('business_name')]) ?>
                </h1>
                <p class="lead text-muted">
                    <?= __('multilanguage_demo_subtitle') ?>
                </p>
            </div>
            
            <!-- Current Language Info -->
            <div class="language-info">
                <div class="current-language-badge">
                    <i class="fas fa-language me-2"></i>
                    <?= __('current_language') ?>: <?= $languageManager->getCurrentLanguageName() ?>
                </div>
                <h3 class="mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= __('language_information') ?>
                </h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-2">
                            <strong><?= __('language_code') ?>:</strong> <?= $languageManager->getCurrentLanguage() ?>
                        </div>
                        <div class="mb-2">
                            <strong><?= __('language_name') ?>:</strong> <?= $languageManager->getCurrentLanguageName() ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-2">
                            <strong><?= __('text_direction') ?>:</strong> <?= __('direction_' . $languageManager->getCurrentDirection()) ?>
                        </div>
                        <div class="mb-2">
                            <strong><?= __('fallback_language') ?>:</strong> <?= __('language_thai') ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-2">
                            <strong><?= __('available_languages') ?>:</strong> <?= count($languageManager->getAvailableLanguages()) ?>
                        </div>
                        <div class="mb-2">
                            <strong><?= __('total_translations') ?>:</strong> <?= count($languageManager->getAllTranslations()) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Translation Examples -->
            <div class="demo-section">
                <h2 class="demo-title">
                    <i class="fas fa-language me-2"></i>
                    <?= __('translation_examples') ?>
                </h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="translation-example">
                            <div class="translation-key"><?= __('home_title') ?></div>
                            <div class="translation-value"><?= __('home_title') ?></div>
                        </div>
                        
                        <div class="translation-example">
                            <div class="translation-key"><?= __('home_subtitle') ?></div>
                            <div class="translation-value"><?= __('home_subtitle') ?></div>
                        </div>
                        
                        <div class="translation-example">
                            <div class="translation-key"><?= __('product_name') ?></div>
                            <div class="translation-value"><?= __('product_name') ?></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="translation-example">
                            <div class="translation-key"><?= __('cart_title') ?></div>
                            <div class="translation-value"><?= __('cart_title') ?></div>
                        </div>
                        
                        <div class="translation-example">
                            <div class="translation-key"><?= __('order_title') ?></div>
                            <div class="translation-value"><?= __('order_title') ?></div>
                        </div>
                        
                        <div class="translation-example">
                            <div class="translation-key"><?= __('admin_title') ?></div>
                            <div class="translation-value"><?= __('admin_title') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Currency & Number Formatting -->
            <div class="demo-section">
                <h2 class="demo-title">
                    <i class="fas fa-coins me-2"></i>
                    <?= __('currency_number_formatting') ?>
                </h2>
                
                <div class="currency-demo">
                    <div class="currency-item">
                        <h5><?= __('currency_example') ?></h5>
                        <div class="h4 text-primary"><?= __c(1234.56) ?></div>
                        <small class="text-muted"><?= __('current_language') ?>: <?= $languageManager->getCurrentLanguage() ?></small>
                    </div>
                    
                    <div class="currency-item">
                        <h5><?= __('number_example') ?></h5>
                        <div class="h4 text-success"><?= __n(9876543.21, 2) ?></div>
                        <small class="text-muted"><?= __('with_decimals') ?>: 2</small>
                    </div>
                    
                    <div class="currency-item">
                        <h5><?= __('percentage_example') ?></h5>
                        <div class="h4 text-warning"><?= __n(85.5, 1) ?>%</div>
                        <small class="text-muted"><?= __('discount_rate') ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Date & Time Formatting -->
            <div class="demo-section">
                <h2 class="demo-title">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <?= __('date_time_formatting') ?>
                </h2>
                
                <div class="date-demo">
                    <div class="date-item">
                        <h5><?= __('long_format') ?></h5>
                        <div class="h6"><?= __d('now', 'long') ?></div>
                        <small class="text-muted"><?= __('full_date_time') ?></small>
                    </div>
                    
                    <div class="date-item">
                        <h5><?= __('short_format') ?></h5>
                        <div class="h6"><?= __d('now', 'short') ?></div>
                        <small class="text-muted"><?= __('date_only') ?></small>
                    </div>
                    
                    <div class="date-item">
                        <h5><?= __('time_format') ?></h5>
                        <div class="h6"><?= __d('now', 'time') ?></div>
                        <small class="text-muted"><?= __('time_only') ?></small>
                    </div>
                    
                    <div class="date-item">
                        <h5><?= __('month_format') ?></h5>
                        <div class="h6"><?= __d('now', 'month') ?></div>
                        <small class="text-muted"><?= __('month_year') ?></small>
                    </div>
                </div>
            </div>
            
            <!-- RTL Support Demo -->
            <div class="demo-section">
                <h2 class="demo-title">
                    <i class="fas fa-arrows-alt-h me-2"></i>
                    <?= __('rtl_support_demo') ?>
                </h2>
                
                <div class="rtl-demo">
                    <h4><?= __('rtl_text_example') ?></h4>
                    <p><?= __('rtl_description') ?></p>
                    <div class="mt-3">
                        <button class="btn btn-light me-2"><?= __('btn_previous') ?></button>
                        <button class="btn btn-light me-2"><?= __('btn_next') ?></button>
                        <button class="btn btn-light"><?= __('btn_finish') ?></button>
                    </div>
                </div>
                
                <p class="text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    <?= __('rtl_info') ?>
                </p>
            </div>
            
            <!-- Features Grid -->
            <div class="demo-section">
                <h2 class="demo-title">
                    <i class="fas fa-star me-2"></i>
                    <?= __('key_features') ?>
                </h2>
                
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-language"></i>
                        </div>
                        <h4><?= __('feature_language_selection') ?></h4>
                        <p><?= __('feature_language_selection_desc') ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h4><?= __('feature_translation_system') ?></h4>
                        <p><?= __('feature_translation_system_desc') ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-globe-asia"></i>
                        </div>
                        <h4><?= __('feature_localized_content') ?></h4>
                        <p><?= __('feature_localized_content_desc') ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-arrows-alt-h"></i>
                        </div>
                        <h4><?= __('feature_rtl_support') ?></h4>
                        <p><?= __('feature_rtl_support_desc') ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-code"></i>
                        </div>
                        <h4><?= __('feature_language_files') ?></h4>
                        <p><?= __('feature_language_files_desc') ?></p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-magic"></i>
                        </div>
                        <h4><?= __('feature_auto_detection') ?></h4>
                        <p><?= __('feature_auto_detection_desc') ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Code Examples -->
            <div class="demo-section">
                <h2 class="demo-title">
                    <i class="fas fa-code me-2"></i>
                    <?= __('code_examples') ?>
                </h2>
                
                <h4><?= __('basic_usage') ?></h4>
                <div class="code-example">
// <?= __('basic_usage_example') ?>
echo __('home_title'); // <?= __('outputs') ?>: <?= __('home_title') ?>

// <?= __('with_parameters') ?>
echo __('footer_copyright', ['year' => '2024']); // <?= __('outputs') ?>: <?= __('footer_copyright', ['year' => '2024']) ?>
                </div>
                
                <h4><?= __('helper_functions') ?></h4>
                <div class="code-example">
// <?= __('short_translation') ?>
echo __t('product_name'); // <?= __('same_as') ?> __('product_name')

// <?= __('plural_translation') ?>
echo __p('item', 5); // <?= __('outputs') ?>: <?= __('item_plural', ['count' => 5]) ?>

// <?= __('date_translation') ?>
echo __d('now', 'long'); // <?= __('outputs') ?>: <?= __d('now', 'long') ?>

// <?= __('number_translation') ?>
echo __n(1234.56, 2); // <?= __('outputs') ?>: <?= __n(1234.56, 2) ?>

// <?= __('currency_translation') ?>
echo __c(1234.56); // <?= __('outputs') ?>: <?= __c(1234.56) ?>
                </div>
                
                <h4><?= __('language_switching') ?></h4>
                <div class="code-example">
// <?= __('switch_language_programmatically') ?>
$languageManager->setLanguage('en');

// <?= __('get_current_language') ?>
$currentLang = $languageManager->getCurrentLanguage(); // <?= __('returns') ?>: <?= $languageManager->getCurrentLanguage() ?>

// <?= __('get_available_languages') ?>
$languages = $languageManager->getAvailableLanguages();
                </div>
            </div>
            
            <!-- Language Statistics -->
            <div class="demo-section">
                <h2 class="demo-title">
                    <i class="fas fa-chart-bar me-2"></i>
                    <?= __('language_statistics') ?>
                </h2>
                
                <div class="row">
                    <div class="col-md-6">
                        <h4><?= __('translation_coverage') ?></h4>
                        <div class="progress mb-3">
                            <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                100%
                            </div>
                        </div>
                        <p class="text-muted"><?= __('all_keys_translated') ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <h4><?= __('supported_languages') ?></h4>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($languageManager->getAvailableLanguages() as $code => $lang): ?>
                            <span class="badge bg-<?= $lang['is_current'] ? 'primary' : 'secondary' ?>">
                                <span class="flag-icon flag-icon-<?= $code ?> me-1"></span>
                                <?= $lang['name'] ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?= $languageManager->renderLanguageScript() ?>
    <script>
        // <?= __('demo_interactions') ?>
        document.addEventListener('DOMContentLoaded', function() {
            // <?= __('show_language_change_notification') ?>
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('lang')) {
                const newLang = urlParams.get('lang');
                const langNames = {
                    'th': '<?= __('language_thai') ?>',
                    'en': '<?= __('language_english') ?>'
                };
                
                if (langNames[newLang]) {
                    showToast(`<?= __('language_changed_to') ?>: ${langNames[newLang]}`, 'success');
                }
            }
        });
        
        // <?= __('show_toast_notification') ?>
        function showToast(message, type = 'info') {
            // <?= __('simple_toast_implementation') ?>
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} position-fixed`;
            toast.style.cssText = 'top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999;';
            toast.innerHTML = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // <?= __('language_switcher_interaction') ?>
        document.querySelectorAll('.language-selector .dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const langCode = this.getAttribute('href').split('=')[1];
                window.location.href = `?lang=${langCode}`;
            });
        });
    </script>
</body>
</html>
