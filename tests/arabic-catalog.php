<?php

// Run with: php tests/arabic-catalog.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../system/autoload/Lang.php';

$catalogPath = __DIR__ . '/../system/lan/arabic.json';
$arabic = json_decode(file_get_contents($catalogPath), true, 512, JSON_THROW_ON_ERROR);
$english = json_decode(file_get_contents(__DIR__ . '/../system/lan/english.json'), true, 512, JSON_THROW_ON_ERROR);
$missing = array_diff_key($english, $arabic);
if ($missing) {
    throw new RuntimeException('Missing Arabic keys: ' . implode(', ', array_keys($missing)));
}

foreach ($arabic as $key => $value) {
    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException('Empty or invalid Arabic translation: ' . $key);
    }
}

// Check both literal and sanitized lookups without calling the online fallback.
$_L = $arabic;
$config = ['language' => 'arabic'];
$lan_file = $catalogPath;
$beforeHash = hash_file('sha256', $catalogPath);
foreach (['Dashboard', 'Go Back', 'Toggle navigation', 'Privacy Policy', 'Terms and Conditions'] as $label) {
    $key = isset($arabic[$label]) ? $label : Lang::sanitize($label);
    if (!isset($arabic[$key]) || !preg_match('/\p{Arabic}/u', $arabic[$key])) {
        throw new RuntimeException('Missing Arabic UI label: ' . $label);
    }
    if (Lang::T($label) !== $arabic[$key]) {
        throw new RuntimeException('Translation lookup failed: ' . $label);
    }
}
if (hash_file('sha256', $catalogPath) !== $beforeHash) {
    throw new RuntimeException('Translation verification must not mutate the catalog.');
}

echo 'PASS: ' . count($english) . ' English keys covered, ' . count($arabic) . " valid Arabic entries, offline UI lookups.\n";
