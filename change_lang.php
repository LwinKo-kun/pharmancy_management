<?php
session_start();

if (isset($_POST['lang'])) {
    $lang = $_POST['lang'];
    if (in_array($lang, ['en', 'mm'])) {
        $_SESSION['lang'] = $lang;
    }
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit();
