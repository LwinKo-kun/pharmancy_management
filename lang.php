<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Default language = English
$lang = $_SESSION['lang'] ?? 'en';

// Load language file
if ($lang == 'mm') {
    $langData = include 'lang_mm.php';
} else {
    $langData = include 'lang_en.php';
}
