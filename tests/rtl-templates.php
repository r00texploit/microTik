<?php

// Run with: php tests/rtl-templates.php [--preview]
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../system/vendor/autoload.php';
require_once __DIR__ . '/../system/autoload/Lang.php';
require_once __DIR__ . '/../system/autoload/Text.php';

define('U', 'http://127.0.0.1:8000/?_route=');
$templateDirectory = realpath(__DIR__ . '/../ui/ui');
$compileDirectory = sys_get_temp_dir() . '/phpnuxbill-rtl-' . bin2hex(random_bytes(8));
mkdir($compileDirectory);
$smarty = new Smarty();
$smarty->registerClass('Lang', 'Lang');
$smarty->registerClass('Text', 'Text');
foreach (['ucwords', 'number_format', 'strtotime', 'date'] as $modifier) {
    $smarty->registerPlugin('modifier', $modifier, $modifier);
}
$smarty->setTemplateDir($templateDirectory);
$smarty->setCompileDir($compileDirectory);
$isolang = json_decode(file_get_contents(__DIR__ . '/../system/lan/country.json'), true, 512, JSON_THROW_ON_ERROR);

try {
    $compiled = 0;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($templateDirectory)) as $file) {
        if ($file->getExtension() !== 'tpl' || strpos(file_get_contents($file->getPathname()), '<html') === false) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($templateDirectory) + 1));
        $smarty->createTemplate($relative)->compileTemplateSource();
        $compiled++;
    }

    foreach (['arabic' => ['ar', 'rtl'], 'english' => ['en', 'ltr']] as $language => [$htmlLanguage, $direction]) {
        $_L = json_decode(file_get_contents(__DIR__ . '/../system/lan/' . $language . '.json'), true, 512, JSON_THROW_ON_ERROR);
        // A fixture never invokes remote translation or writes a language file.
        $lan_file = $compileDirectory . '/unused-catalog.json';
        $config = ['language' => $language, 'url_canonical' => 'no'];
        $header = file_get_contents($templateDirectory . '/customer/header.tpl');
        preg_match_all('/Lang::T\(\x27([^\x27]*)\x27\)/', $header, $matches);
        foreach ($matches[1] as $label) {
            if (!isset($_L[$label]) && !isset($_L[Lang::sanitize($label)])) {
                if ($language === 'arabic') {
                    throw new RuntimeException('Missing customer header translation: ' . $label);
                }
                $_L[$label] = $label;
            }
        }
        $smarty->assign([
            '_title' => Lang::T('Dashboard'), 'app_url' => 'http://127.0.0.1:8000',
            '_c' => ['CompanyName' => 'PHPNuxBill', 'enable_balance' => 'no', 'disable_voucher' => 'no', 'payment_gateway' => 'none'],
            '_user' => ['fullname' => 'Layout Preview', 'photo' => '/user.default.jpg', 'phonenumber' => '', 'email' => ''],
            'UPLOAD_PATH' => 'system/uploads', '_system_menu' => 'home', 'user_language' => $language,
            '_MENU_AFTER_DASHBOARD' => '', '_MENU_AFTER_INBOX' => '', '_MENU_AFTER_ORDER' => '', '_MENU_AFTER_HISTORY' => '',
        ]);
        $rendered = $smarty->fetch('customer/header.tpl');
        if (strpos($rendered, 'lang="' . $htmlLanguage . '"') === false || strpos($rendered, 'dir="' . $direction . '"') === false) {
            throw new RuntimeException('Incorrect customer document attributes for ' . $language);
        }
        if ((strpos($rendered, 'phpnuxbill.rtl.css') !== false) !== ($direction === 'rtl')) {
            throw new RuntimeException('Incorrect RTL stylesheet selection for ' . $language);
        }
        if ($language === 'arabic' && in_array('--preview', $argv, true)) {
            $rendered .= '<p>RTL layout fixture — no customer account or session.</p></section></div></div>';
            $rendered .= '<script src="/ui/ui/scripts/jquery.min.js"></script><script src="/ui/ui/scripts/bootstrap.min.js"></script><script src="/ui/ui/scripts/adminlte.min.js"></script></body></html>';
            file_put_contents(__DIR__ . '/../system/cache/rtl-customer-preview.html', $rendered);
        }
    }
    echo "PASS: $compiled full-page templates compile; customer shell renders Arabic/RTL and English/LTR without database access.\n";
} finally {
    // Only remove flat files inside the unique directory created by this test.
    foreach (glob($compileDirectory . '/*') as $compiledFile) {
        if (is_file($compiledFile)) {
            unlink($compiledFile);
        }
    }
    rmdir($compileDirectory);
}
