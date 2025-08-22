<?php
session_start();
require_once __DIR__ . '/htpasswd.php';

$env = file_exists(__DIR__.'/.env') ? parse_ini_file(__DIR__.'/.env') : [];
$htpasswdPath = isset($env['HTPASSWD_PATH']) ? $env['HTPASSWD_PATH'] : __DIR__ . '/.htpasswd';
$backupDir = isset($env['BACKUP_DIR']) ? $env['BACKUP_DIR'] : __DIR__ . '/backups';

$manager = new Htpasswd($htpasswdPath, $backupDir);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
    if (isset($_POST['restore'])) {
        try {
            $manager->restore($_POST['restore']);
        } catch (Exception $e) {
            // ignore
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $entries = [];
    $usernames = isset($_POST['username']) ? $_POST['username'] : [];
    foreach ($usernames as $i => $username) {
        $username = trim($username);
        if ($username === '' || preg_match('/[\s:]/', $username)) {
            continue;
        }
        $plain = isset($_POST['password_plain'][$i]) ? $_POST['password_plain'][$i] : '';
        $hash = isset($_POST['password_hash'][$i]) ? $_POST['password_hash'][$i] : '';
        if ($plain !== '') {
            $hash = password_hash($plain, PASSWORD_BCRYPT);
        } elseif ($hash === '' || preg_match('/[\r\n:]/', $hash)) {
            continue;
        }
        $commentText = isset($_POST['comment'][$i]) ? str_replace("\r", '', $_POST['comment'][$i]) : '';
        $comments = explode("\n", $commentText);
        $entries[] = [
            'active' => isset($_POST['active'][$i]),
            'username' => $username,
            'hash' => $hash,
            'comments' => $comments
        ];
    }
    $manager->write($entries);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

$entries = $manager->read();
$backups = $manager->listBackups();
include __DIR__ . '/template.php';
