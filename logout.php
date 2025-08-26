<?php
session_start();

// Log the logout
if (isset($_SESSION['user_id'])) {
    require_once 'config/db.php';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']]);
}

// Destroy session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit;
?>