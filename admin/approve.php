<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE listings SET is_verified = 1 WHERE id = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], 'approve', 'listings', $id]);
}

header("Location: dashboard.php");
exit;
