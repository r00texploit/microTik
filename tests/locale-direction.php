<?php

// Run with: php tests/locale-direction.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../system/autoload/Lang.php';

$isolang = json_decode(file_get_contents(__DIR__ . '/../system/lan/country.json'), true, 512, JSON_THROW_ON_ERROR);
$cases = [
    ['arabic', '0', 'ar', 'rtl'],
    ['english', '1', 'en', 'ltr'],
    ['arabic', '1', 'ar', 'rtl'],
    ['english-uk', '0', 'en-gb', 'ltr'],
    ['urdu', '0', 'ur', 'rtl'],
    ['hebrew', '0', 'iw', 'rtl'],
    ['not-a-language', '1', 'en', 'ltr'],
    ['"><script>', '0', 'en', 'ltr'],
];

foreach ($cases as [$language, $legacyRtl, $expectedLanguage, $expectedDirection]) {
    // init.php already resolved session/cookie/account/default precedence.
    $config = ['language' => $language, 'rtl' => $legacyRtl];
    if (Lang::htmlLang() !== $expectedLanguage || Lang::direction() !== $expectedDirection) {
        throw new RuntimeException('Incorrect document language/direction for ' . $language);
    }
}

$config = [];
if (Lang::htmlLang() !== 'en' || Lang::direction() !== 'ltr') {
    throw new RuntimeException('Missing language must fall back to English/LTR.');
}

foreach (['en" onload="alert(1)', "ar\n"] as $invalidTag) {
    $isolang['invalid-tag'] = $invalidTag;
    $config = ['language' => 'invalid-tag'];
    if (Lang::htmlLang() !== 'en') {
        throw new RuntimeException('Invalid language tags must not reach HTML attributes.');
    }
}

echo "PASS: document language, Arabic/English switching, and safe fallback (11 cases).\n";
