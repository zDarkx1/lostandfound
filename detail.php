<?php
require_once 'config/db.php';
session_start();

$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if (!$id) {
    header('Location: cari.php');
    exit;
}

// Get item details
$stmt = $pdo->prepare("
    SELECT l.*, 
           c.name as category_name, 
           u.name as user_name, 
           u.email as user_email, 
           u.phone as user_phone,
           l.latitude,
           l.longitude
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    JOIN users u ON l.user_id = u.id 
    WHERE l.id = ?
");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: cari.php');
    exit;
}

// Handle contact form submission
if ($_POST && isset($_SESSION['user_id'])) {
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $error = 'Please enter a message.';
    } elseif ($_SESSION['user_id'] == $item['user_id']) {
        $error = 'You cannot send a message to yourself.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO messages (listing_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");

        if ($stmt->execute([$id, $_SESSION['user_id'], $item['user_id'], $message])) {
            $success = 'Your message has been sent successfully! The owner will be notified.';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

// Get related items (same category, excluding current)
$stmt = $pdo->prepare("
    SELECT l.*, c.name as category_name 
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    WHERE l.category_id = ? AND l.id != ? AND l.status != 'returned' 
    ORDER BY l.created_at DESC 
    LIMIT 4
");
$stmt->execute([$item['category_id'], $id]);
$related_items = $stmt->fetchAll();

include 'partials/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['title']); ?> - Lost & Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="assets/css/tailwind.css">
</head>

<body class="bg-gray-50">

    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="flex items-center space-x-2 text-sm text-gray-500 mb-8">
                <a href="index.php" class="hover:text-purple-600">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="cari.php" class="hover:text-purple-600">Search</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-900"><?php echo htmlspecialchars($item['title']); ?></span>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Item Image -->
                    <div class="card overflow-hidden">
                        <?php if ($item['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                                alt="<?php echo htmlspecialchars($item['title']); ?>"
                                class="w-full h-96 object-cover">
                        <?php else: ?>
                            <div class="w-full h-96 bg-gradient-to-br from-purple-100 to-blue-100 flex items-center justify-center">
                                <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Item Details -->
                    <div class="card p-8">
                        <div class="flex items-center justify-between mb-6">
                            <span class="inline-block px-4 py-2 text-sm font-medium rounded-full <?php echo $item['status'] == 'lost' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst($item['status']); ?>
                            </span>
                            <span class="text-sm text-gray-500">
                                Reported on <?php echo date('M j, Y', strtotime($item['created_at'])); ?>
                            </span>
                        </div>

                        <h1 class="text-3xl font-bold text-gray-900 mb-4">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h1>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                <?php echo htmlspecialchars($item['category_name']); ?>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?php echo htmlspecialchars($item['location']); ?>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4v10m6-10v10" />
                                </svg>
                                <?php echo date('M j, Y', strtotime($item['date_lost_found'])); ?>
                            </div>
                        </div>

                        <div class="prose max-w-none">
                            <h3 class="text-lg font-semibold text-gray-900 mb-3">Description</h3>
                            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($item['description']); ?></p>
                        </div>
                    </div>

                    <!-- Related Items -->
                    <?php if (count($related_items) > 0): ?>
                        <div class="card p-8">
                            <h3 class="text-xl font-semibold text-gray-900 mb-6">Related Items</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($related_items as $related_item): ?>
                                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-center space-x-3">
                                            <?php if ($related_item['image_path']): ?>
                                                <img src="<?php echo htmlspecialchars($related_item['image_path']); ?>"
                                                    alt="<?php echo htmlspecialchars($related_item['title']); ?>"
                                                    class="w-16 h-16 object-cover rounded-lg">
                                            <?php else: ?>
                                                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1">
                                                <h4 class="font-medium text-gray-900 line-clamp-1"><?php echo htmlspecialchars($related_item['title']); ?></h4>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($related_item['category_name']); ?></p>
                                                <a href="detail.php?id=<?php echo $related_item['id']; ?>" class="text-sm text-purple-600 hover:text-purple-700">View Details →</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Contact Owner/Finder -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Contact <?php echo $item['status'] == 'lost' ? 'Owner' : 'Finder'; ?>
                        </h3>

                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-lg">
                                    <?php echo strtoupper(substr($item['user_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['user_name']); ?></p>
                                <p class="text-sm text-gray-500">
                                    Member since <?php echo date('M Y', strtotime($item['created_at'])); ?>
                                </p>
                            </div>
                        </div>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['user_id'] != $item['user_id']): ?>
                                <?php if ($error || $success): ?>
                                    <div class="mb-4">
                                        <?php if ($error): ?>
                                            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                                                <?php echo htmlspecialchars($error); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($success): ?>
                                            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                                                <?php echo htmlspecialchars($success); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="space-y-4">
                                    <div>
                                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                            Send a Message
                                        </label>
                                        <textarea id="message" name="message" rows="4" required
                                            class="form-textarea text-sm"
                                            placeholder="Hi, I believe this item belongs to me. I can provide proof of ownership..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn-primary w-full">
                                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Send Message
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg text-sm">
                                    This is your item. You can manage it from your profile.
                                </div>
                                <div>
                                    <label for="map" class="btn-secondary w-full mt-4">
                                        <i class="fas fa-map-marker-alt mr-1"></i> Item Location
                                    </label>
                                    <div id="map" class="w-full h-64 rounded-lg border"></div>
                                </div>
                                <a href="profil.php" class="btn-secondary w-full mt-4">
                                    Manage My Items
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="bg-gray-50 border border-gray-200 text-gray-700 px-4 py-3 rounded-lg text-sm mb-4">
                                Please login to contact the <?php echo $item['status'] == 'lost' ? 'owner' : 'finder'; ?>.
                            </div>
                            <div class="space-y-2">
                                <a href="login.php" class="btn-primary w-full">
                                    Login to Contact
                                </a>
                                <a href="register.php" class="btn-secondary w-full">
                                    Create Account
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Safety Tips -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Safety Tips</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Meet in a public, well-lit location
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Verify ownership with proof
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Bring a friend or family member
                            </li>
                            <li class="flex items-start">
                                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Trust your instincts
                            </li>
                        </ul>
                    </div>

                    <!-- Report Item -->
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $item['user_id']): ?>
                        <div class="card p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Report Issue</h3>
                            <p class="text-sm text-gray-600 mb-4">
                                If you believe this listing violates our community guidelines, please report it.
                            </p>
                            <button onclick="reportItem(<?php echo $item['id']; ?>)" class="text-sm text-red-600 hover:text-red-700 font-medium">
                                Report this listing
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fetch latitude and longitude item
        var lat = <?php echo json_encode($item['latitude']); ?>;
        var lng = <?php echo json_encode($item['longitude']); ?>;

        if (lat && lng) {
            var map = L.map('map').setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            L.marker([lat, lng]).addTo(map)
                .bindPopup("Last known location of the item")
                .openPopup();
        } else {
            document.getElementById('map').innerHTML = "<p class='text-gray-500 p-4'>Location not available</p>";
        }

        function reportItem(itemId) {
            if (confirm('Are you sure you want to report this item? This action cannot be undone.')) {
                // Here you would typically make an AJAX call to report the item
                alert('Thank you for your report. We will review this item shortly.');
            }
        }
    </script>

    <?php include 'partials/footer.php'; ?>
</body>

</html>