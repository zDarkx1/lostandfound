<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$listing_id = intval($_POST['listing_id']);
$reason = trim($_POST['reason']);

if (!$listing_id || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Check if listing exists
$stmt = $pdo->prepare("SELECT id FROM listings WHERE id = ?");
$stmt->execute([$listing_id]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Listing not found']);
    exit;
}

// Check if user already reported this listing
$stmt = $pdo->prepare("SELECT id FROM reports WHERE listing_id = ? AND reporter_id = ?");
$stmt->execute([$listing_id, $_SESSION['user_id']]);

if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reported this listing']);
    exit;
}

// Insert report
$stmt = $pdo->prepare("INSERT INTO reports (listing_id, reporter_id, reason) VALUES (?, ?, ?)");

if ($stmt->execute([$listing_id, $_SESSION['user_id'], $reason])) {
    // Log the action
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, 'report', 'reports', ?)");
    $logStmt->execute([$_SESSION['user_id'], $pdo->lastInsertId()]);
    
    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit report']);
}
?>