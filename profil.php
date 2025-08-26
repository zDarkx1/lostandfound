<?php
require_once 'config/db.php';
require_once 'partials/navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Handle status update
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    $listing_id = intval($_POST['listing_id'] ?? 0);
    
    if ($action === 'mark_returned' && $listing_id) {
        $stmt = $pdo->prepare("UPDATE listings SET status = 'returned' WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$listing_id, $_SESSION['user_id']])) {
            $success = 'Item marked as returned successfully!';
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'mark_returned', 'listings', $listing_id]);
        } else {
            $error = 'Failed to update item status.';
        }
    } elseif ($action === 'delete_listing' && $listing_id) {
        $stmt = $pdo->prepare("DELETE FROM listings WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$listing_id, $_SESSION['user_id']])) {
            $success = 'Item deleted successfully!';
            
            // Log the action
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], 'delete_listing', 'listings', $listing_id]);
        } else {
            $error = 'Failed to delete item.';
        }
    }
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's listings
$stmt = $pdo->prepare("
    SELECT l.*, c.name as category_name 
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    WHERE l.user_id = ? 
    ORDER BY l.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_listings = $stmt->fetchAll();

// Get user's messages
$stmt = $pdo->prepare("
    SELECT m.*, l.title as listing_title, l.id as listing_id, 
           sender.name as sender_name, receiver.name as receiver_name,
           l.status as listing_status
    FROM messages m 
    JOIN listings l ON m.listing_id = l.id 
    JOIN users sender ON m.sender_id = sender.id
    JOIN users receiver ON m.receiver_id = receiver.id
    WHERE m.receiver_id = ? OR m.sender_id = ?
    ORDER BY m.created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM listings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_listings = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM listings WHERE user_id = ? AND status = 'returned'");
$stmt->execute([$_SESSION['user_id']]);
$returned_items = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM messages WHERE receiver_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$received_messages = $stmt->fetch()['total'];


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Lost & Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>
<body class="bg-gray-50">

    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">My Profile</h1>
                <p class="text-lg text-gray-600">
                    Manage your items, messages, and account settings
                </p>
            </div>

            <?php if ($error || $success): ?>
                <div class="mb-6">
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- User Info -->
                    <div class="card p-6">
                        <div class="flex items-center space-x-4 mb-6">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-bold text-xl">
                                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                                <?php if ($user['phone']): ?>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($user['phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500">
                            Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>

                    <!-- Statistics -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Statistics</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Total Items</span>
                                <span class="font-semibold text-gray-900"><?php echo $total_listings; ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Returned Items</span>
                                <span class="font-semibold text-green-600"><?php echo $returned_items; ?></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Messages Received</span>
                                <span class="font-semibold text-blue-600"><?php echo $received_messages; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="laporan.php" class="btn-primary w-full text-center">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Report New Item
                            </a>
                            <a href="cari.php" class="btn-secondary w-full text-center">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Search Items
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- My Items -->
                    <div class="card p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">My Items</h3>
                            <a href="laporan.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm">
                                Add New Item →
                            </a>
                        </div>

                        <?php if (count($user_listings) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($user_listings as $listing): ?>
                                    <div class="border rounded-lg p-4 hover:shadow-sm transition-shadow">
                                        <div class="flex items-start space-x-4">
                                            <?php if ($listing['image_path']): ?>
                                                <img src="<?php echo htmlspecialchars($listing['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                                     class="w-20 h-20 object-cover rounded-lg">
                                            <?php else: ?>
                                                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-2">
                                                    <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($listing['title']); ?></h4>
                                                    <span class="inline-block px-2 py-1 text-xs font-medium rounded-full 
                                                        <?php 
                                                        if ($listing['status'] == 'lost') echo 'bg-red-100 text-red-800';
                                                        elseif ($listing['status'] == 'found') echo 'bg-green-100 text-green-800';
                                                        else echo 'bg-gray-100 text-gray-800';
                                                        ?>">
                                                        <?php echo ucfirst($listing['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <p class="text-sm text-gray-600 mb-2">
                                                    <?php echo htmlspecialchars(substr($listing['description'], 0, 100)) . (strlen($listing['description']) > 100 ? '...' : ''); ?>
                                                </p>
                                                
                                                <div class="flex items-center text-xs text-gray-500 mb-3">
                                                    <span class="mr-4"><?php echo htmlspecialchars($listing['category_name']); ?></span>
                                                    <span class="mr-4"><?php echo htmlspecialchars($listing['location']); ?></span>
                                                    <span><?php echo date('M j, Y', strtotime($listing['created_at'])); ?></span>
                                                </div>
                                                
                                                <div class="flex items-center space-x-2">
                                                    <a href="detail.php?id=<?php echo $listing['id']; ?>" class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                                                        View Details
                                                    </a>
                                                    
                                                    <?php if ($listing['status'] != 'returned'): ?>
                                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to mark this item as returned?')">
                                                            <input type="hidden" name="action" value="mark_returned">
                                                            <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                                            <button type="submit" class="text-sm text-green-600 hover:text-green-700 font-medium">
                                                                Mark as Returned
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this item? This action cannot be undone.')">
                                                        <input type="hidden" name="action" value="delete_listing">
                                                        <input type="hidden" name="listing_id" value="<?php echo $listing['id']; ?>">
                                                        <button type="submit" class="text-sm text-red-600 hover:text-red-700 font-medium">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">No Items Yet</h4>
                                <p class="text-gray-600 mb-4">You haven't reported any items yet. Start by reporting a lost or found item.</p>
                                <a href="laporan.php" class="btn-primary">
                                    Report Your First Item
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Messages -->
                    <div class="card p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900">Recent Messages</h3>
                        </div>

                        <?php if (count($messages) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($messages as $message): ?>
                                    <div class="border-l-4 border-purple-200 pl-4 py-2">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium text-gray-900">
                                                    <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'You' : htmlspecialchars($message['sender_name']); ?>
                                                </span>
                                                <span class="text-gray-500">→</span>
                                                <span class="font-medium text-gray-900">
                                                    <?php echo $message['receiver_id'] == $_SESSION['user_id'] ? 'You' : htmlspecialchars($message['receiver_name']); ?>
                                                </span>
                                            </div>
                                            <span class="text-xs text-gray-500"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></span>
                                        </div>
                                        <p class="text-sm text-gray-700 mb-2"><?php echo htmlspecialchars($message['message']); ?></p>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs text-gray-500">Re:</span>
                                            <a href="detail.php?id=<?php echo $message['listing_id']; ?>" class="text-xs text-purple-600 hover:text-purple-700">
                                                <?php echo htmlspecialchars($message['listing_title']); ?>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">No Messages Yet</h4>
                                <p class="text-gray-600">Your conversations about lost and found items will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>