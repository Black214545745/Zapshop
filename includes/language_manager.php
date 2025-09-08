<?php
/**
 * Language Manager Class
 * ZapShop Multi-language Support
 */

class LanguageManager {
    private $currentLanguage = 'th';
    private $availableLanguages = ['th', 'en'];
    private $translations = [];
    private $fallbackLanguage = 'th';
    private $languageFiles = [];
    
    public function __construct() {
        $this->loadLanguageFiles();
        $this->detectLanguage();
        $this->loadTranslations();
    }
    
    /**
     * โหลดไฟล์ภาษาทั้งหมด
     */
    private function loadLanguageFiles() {
        $this->languageFiles = [
            'th' => 'assets/languages/th.php',
            'en' => 'assets/languages/en.php'
        ];
    }
    
    /**
     * ตรวจจับภาษาที่ผู้ใช้ต้องการ
     */
    private function detectLanguage() {
        // ตรวจสอบจาก Session
        if (isset($_SESSION['language']) && in_array($_SESSION['language'], $this->availableLanguages)) {
            $this->currentLanguage = $_SESSION['language'];
            return;
        }
        
        // ตรวจสอบจาก Cookie
        if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], $this->availableLanguages)) {
            $this->currentLanguage = $_COOKIE['language'];
            return;
        }
        
        // ตรวจสอบจาก Browser Language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($browserLanguages as $lang) {
                $lang = strtolower(substr($lang, 0, 2));
                if (in_array($lang, $this->availableLanguages)) {
                    $this->currentLanguage = $lang;
                    return;
                }
            }
        }
        
        // ใช้ภาษาเริ่มต้น
        $this->currentLanguage = $this->fallbackLanguage;
    }
    
    /**
     * โหลดคำแปลสำหรับภาษาปัจจุบัน
     */
    private function loadTranslations() {
        $languageFile = $this->languageFiles[$this->currentLanguage] ?? $this->languageFiles[$this->fallbackLanguage];
        
        if (file_exists($languageFile)) {
            $this->translations = include $languageFile;
        } else {
            // ใช้ไฟล์ภาษาเริ่มต้นถ้าไม่พบไฟล์ภาษาที่ต้องการ
            $fallbackFile = $this->languageFiles[$this->fallbackLanguage];
            if (file_exists($fallbackFile)) {
                $this->translations = include $fallbackFile;
            }
        }
    }
    
    /**
     * เปลี่ยนภาษา
     */
    public function setLanguage($languageCode) {
        if (in_array($languageCode, $this->availableLanguages)) {
            $this->currentLanguage = $languageCode;
            $_SESSION['language'] = $languageCode;
            setcookie('language', $languageCode, time() + (365 * 24 * 60 * 60), '/');
            $this->loadTranslations();
            return true;
        }
        return false;
    }
    
    /**
     * รับรหัสภาษาปัจจุบัน
     */
    public function getCurrentLanguage() {
        return $this->currentLanguage;
    }
    
    /**
     * รับชื่อภาษาปัจจุบัน
     */
    public function getCurrentLanguageName() {
        return $this->translations['language_name'] ?? 'Unknown';
    }
    
    /**
     * รับทิศทางการเขียนของภาษาปัจจุบัน
     */
    public function getCurrentDirection() {
        return $this->translations['direction'] ?? 'ltr';
    }
    
    /**
     * รับรายการภาษาที่รองรับ
     */
    public function getAvailableLanguages() {
        $languages = [];
        foreach ($this->availableLanguages as $code) {
            if (isset($this->languageFiles[$code]) && file_exists($this->languageFiles[$code])) {
                $langData = include $this->languageFiles[$code];
                $languages[$code] = [
                    'code' => $code,
                    'name' => $langData['language_name'] ?? $code,
                    'direction' => $langData['direction'] ?? 'ltr',
                    'is_current' => $code === $this->currentLanguage
                ];
            }
        }
        return $languages;
    }
    
    /**
     * แปลข้อความ
     */
    public function translate($key, $parameters = []) {
        $translation = $this->translations[$key] ?? $key;
        
        // แทนที่พารามิเตอร์
        if (!empty($parameters)) {
            foreach ($parameters as $param => $value) {
                $translation = str_replace('{' . $param . '}', $value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * แปลข้อความแบบย่อ
     */
    public function t($key, $parameters = []) {
        return $this->translate($key, $parameters);
    }
    
    /**
     * ตรวจสอบว่ามีคำแปลหรือไม่
     */
    public function hasTranslation($key) {
        return isset($this->translations[$key]);
    }
    
    /**
     * รับคำแปลทั้งหมด
     */
    public function getAllTranslations() {
        return $this->translations;
    }
    
    /**
     * สร้าง HTML attributes สำหรับภาษา
     */
    public function getLanguageAttributes() {
        $direction = $this->getCurrentDirection();
        $lang = $this->getCurrentLanguage();
        
        return [
            'lang' => $lang,
            'dir' => $direction,
            'class' => "lang-{$lang} direction-{$direction}"
        ];
    }
    
    /**
     * สร้าง HTML สำหรับเลือกภาษา
     */
    public function renderLanguageSelector($showCurrentLanguage = true, $showFlags = true) {
        $languages = $this->getAvailableLanguages();
        $currentLang = $this->getCurrentLanguage();
        
        $html = '<div class="language-selector dropdown">';
        $html .= '<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        
        if ($showFlags) {
            $html .= '<span class="flag-icon flag-icon-' . $currentLang . ' me-2"></span>';
        }
        
        if ($showCurrentLanguage) {
            $html .= '<span class="current-language">' . $languages[$currentLang]['name'] . '</span>';
        }
        
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu">';
        
        foreach ($languages as $code => $lang) {
            $activeClass = $lang['is_current'] ? 'active' : '';
            $html .= '<li><a class="dropdown-item ' . $activeClass . '" href="?lang=' . $code . '">';
            
            if ($showFlags) {
                $html .= '<span class="flag-icon flag-icon-' . $code . ' me-2"></span>';
            }
            
            $html .= '<span class="language-name">' . $lang['name'] . '</span>';
            $html .= '</a></li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * สร้าง JavaScript สำหรับเปลี่ยนภาษา
     */
    public function renderLanguageScript() {
        $html = '<script>';
        $html .= 'const LanguageManager = {';
        $html .= '  currentLanguage: "' . $this->getCurrentLanguage() . '",';
        $html .= '  availableLanguages: ' . json_encode($this->availableLanguages) . ',';
        $html .= '  translations: ' . json_encode($this->translations) . ',';
        $html .= '  setLanguage: function(langCode) {';
        $html .= '    if (this.availableLanguages.includes(langCode)) {';
        $html .= '      fetch("?lang=" + langCode, { method: "GET" })';
        $html .= '        .then(() => { window.location.reload(); })';
        $html .= '        .catch(error => console.error("Language change error:", error));';
        $html .= '    }';
        $html .= '  },';
        $html .= '  translate: function(key, parameters = {}) {';
        $html .= '    let translation = this.translations[key] || key;';
        $html .= '    Object.keys(parameters).forEach(param => {';
        $html .= '      translation = translation.replace("{" + param + "}", parameters[param]);';
        $html .= '    });';
        $html .= '    return translation;';
        $html .= '  }';
        $html .= '};';
        $html .= '</script>';
        
        return $html;
    }
    
    /**
     * สร้าง CSS สำหรับภาษา
     */
    public function renderLanguageStyles() {
        $direction = $this->getCurrentDirection();
        $lang = $this->getCurrentLanguage();
        
        $css = '<style>';
        $css .= '.lang-' . $lang . ' { direction: ' . $direction . '; }';
        
        // RTL Support
        if ($direction === 'rtl') {
            $css .= '.lang-' . $lang . ' .dropdown-menu { right: 0; left: auto; }';
            $css .= '.lang-' . $lang . ' .me-2 { margin-right: 0 !important; margin-left: 0.5rem !important; }';
            $css .= '.lang-' . $lang . ' .ms-2 { margin-left: 0 !important; margin-right: 0.5rem !important; }';
            $css .= '.lang-' . $lang . ' .text-start { text-align: right !important; }';
            $css .= '.lang-' . $lang . ' .text-end { text-align: left !important; }';
        }
        
        // Language-specific styles
        $css .= '.language-selector .dropdown-menu { min-width: 150px; }';
        $css .= '.language-selector .flag-icon { width: 16px; height: 11px; }';
        $css .= '.language-selector .current-language { font-weight: 500; }';
        $css .= '.language-selector .dropdown-item.active { background-color: var(--primary-color); color: white; }';
        $css .= '.language-selector .dropdown-item:hover { background-color: var(--light-color); }';
        
        $css .= '</style>';
        
        return $css;
    }
    
    /**
     * สร้าง Meta tags สำหรับภาษา
     */
    public function renderLanguageMeta() {
        $lang = $this->getCurrentLanguage();
        $direction = $this->getCurrentDirection();
        
        $html = '<meta name="language" content="' . $lang . '">';
        $html .= '<meta name="language-direction" content="' . $direction . '">';
        $html .= '<html lang="' . $lang . '" dir="' . $direction . '">';
        
        return $html;
    }
    
    /**
     * ตรวจสอบการเปลี่ยนแปลงภาษา
     */
    public function handleLanguageChange() {
        if (isset($_GET['lang'])) {
            $newLang = $_GET['lang'];
            if ($this->setLanguage($newLang)) {
                // Redirect เพื่อลบ parameter lang ออกจาก URL
                $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
                if (isset($_GET['lang'])) {
                    unset($_GET['lang']);
                    if (!empty($_GET)) {
                        $redirectUrl .= '?' . http_build_query($_GET);
                    }
                }
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
    }
    
    /**
     * สร้าง URL สำหรับเปลี่ยนภาษา
     */
    public function getLanguageUrl($languageCode) {
        $currentUrl = $_SERVER['REQUEST_URI'];
        $parsedUrl = parse_url($currentUrl);
        $queryParams = [];
        
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        
        $queryParams['lang'] = $languageCode;
        
        $newQuery = http_build_query($queryParams);
        $newUrl = $parsedUrl['path'];
        
        if (!empty($newQuery)) {
            $newUrl .= '?' . $newQuery;
        }
        
        return $newUrl;
    }
    
    /**
     * แปลข้อความแบบ Plural
     */
    public function translatePlural($key, $count, $parameters = []) {
        $baseKey = $key . '_' . ($count === 1 ? 'singular' : 'plural');
        return $this->translate($baseKey, array_merge(['count' => $count], $parameters));
    }
    
    /**
     * แปลวันที่ตามภาษา
     */
    public function translateDate($date, $format = 'long') {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $lang = $this->getCurrentLanguage();
        
        $formats = [
            'th' => [
                'long' => 'j F Y เวลา H:i น.',
                'short' => 'j/m/Y',
                'time' => 'H:i น.',
                'month' => 'F Y',
                'day' => 'l j F Y'
            ],
            'en' => [
                'long' => 'F j, Y \a\t g:i A',
                'short' => 'm/j/Y',
                'time' => 'g:i A',
                'month' => 'F Y',
                'day' => 'l, F j, Y'
            ]
        ];
        
        $dateFormat = $formats[$lang][$format] ?? $formats['en'][$format];
        
        if ($lang === 'th') {
            // แปลเดือนและวันเป็นภาษาไทย
            $thaiMonths = [
                'January' => 'มกราคม', 'February' => 'กุมภาพันธ์', 'March' => 'มีนาคม',
                'April' => 'เมษายน', 'May' => 'พฤษภาคม', 'June' => 'มิถุนายน',
                'July' => 'กรกฎาคม', 'August' => 'สิงหาคม', 'September' => 'กันยายน',
                'October' => 'ตุลาคม', 'November' => 'พฤศจิกายน', 'December' => 'ธันวาคม'
            ];
            
            $thaiDays = [
                'Monday' => 'จันทร์', 'Tuesday' => 'อังคาร', 'Wednesday' => 'พุธ',
                'Thursday' => 'พฤหัสบดี', 'Friday' => 'ศุกร์', 'Saturday' => 'เสาร์', 'Sunday' => 'อาทิตย์'
            ];
            
            $dateString = date($dateFormat, $timestamp);
            $dateString = str_replace(array_keys($thaiMonths), array_values($thaiMonths), $dateString);
            $dateString = str_replace(array_keys($thaiDays), array_values($thaiDays), $dateString);
            
            return $dateString;
        }
        
        return date($dateFormat, $timestamp);
    }
    
    /**
     * แปลตัวเลขตามภาษา
     */
    public function translateNumber($number, $decimals = 2) {
        $lang = $this->getCurrentLanguage();
        
        if ($lang === 'th') {
            // ใช้การจัดรูปแบบตัวเลขแบบไทย
            return number_format($number, $decimals, '.', ',');
        }
        
        // ใช้การจัดรูปแบบตัวเลขแบบอังกฤษ
        return number_format($number, $decimals, '.', ',');
    }
    
    /**
     * แปลสกุลเงินตามภาษา
     */
    public function translateCurrency($amount, $currency = null) {
        $lang = $this->getCurrentLanguage();
        
        if ($currency === null) {
            $currency = $lang === 'th' ? 'THB' : 'USD';
        }
        
        $formats = [
            'th' => '฿{amount}',
            'en' => '${amount}'
        ];
        
        $format = $formats[$lang] ?? $formats['en'];
        $formattedAmount = $this->translateNumber($amount, 2);
        
        return str_replace('{amount}', $formattedAmount, $format);
    }
}

// สร้าง instance ของ LanguageManager
$languageManager = new LanguageManager();

// จัดการการเปลี่ยนภาษา
$languageManager->handleLanguageChange();

// ฟังก์ชันช่วยเหลือสำหรับการแปล
function __($key, $parameters = []) {
    global $languageManager;
    return $languageManager->translate($key, $parameters);
}

function __t($key, $parameters = []) {
    global $languageManager;
    return $languageManager->translate($key, $parameters);
}

function __p($key, $count, $parameters = []) {
    global $languageManager;
    return $languageManager->translatePlural($key, $count, $parameters);
}

function __d($date, $format = 'long') {
    global $languageManager;
    return $languageManager->translateDate($date, $format);
}

function __n($number, $decimals = 2) {
    global $languageManager;
    return $languageManager->translateNumber($number, $decimals);
}

function __c($amount, $currency = null) {
    global $languageManager;
    return $languageManager->translateCurrency($amount, $currency);
}
?>
