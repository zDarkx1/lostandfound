<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get latest reports for sidebar
$stmt = $pdo->prepare("
    SELECT l.*, c.name as category_name, u.name as user_name
    FROM listings l
    JOIN categories c ON l.category_id = c.id
    JOIN users u ON l.user_id = u.id
    WHERE l.status != 'returned' 
      AND l.is_verified = 1
    ORDER BY l.created_at DESC
    LIMIT 10
");
$stmt->execute();
$latest_reports = $stmt->fetchAll();



if ($_POST) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $date_lost_found = $_POST['date_lost_found'] ?? '';
    $status = $_POST['status'] ?? 'lost';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $contact_name = trim($_POST['contact_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');

    // Validation
    if (empty($title) || empty($description) || empty($category_id) || empty($location) || empty($date_lost_found)) {
        $error = 'Please fill all required fields.';
    } else {
        $image_path = null;

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $error = 'Only JPG and PNG images are allowed.';
            } elseif ($_FILES['image']['size'] > $max_size) {
                $error = 'Image size must be less than 2MB.';
            } else {
                // Create uploads directory if it doesn't exist
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $file_extension;
                $image_path = 'uploads/' . $filename;

                if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $error = 'Failed to upload image.';
                    $image_path = null;
                }
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO listings (user_id, category_id, title, description, location, latitude, longitude, date_lost_found, status, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                if ($stmt->execute([$_SESSION['user_id'], $category_id, $title, $description, $location, $latitude, $longitude, $date_lost_found, $status, $image_path])) {
                    $success = 'Report submitted successfully! Your item has been posted.';

                    // Log the action
                    $listing_id = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, table_name, record_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], 'create_listing', 'listings', $listing_id]);
                } else {
                    $error = 'Failed to submit report. Please try again.';
                }
            } catch (Exception $e) {
                $error = 'Database error occurred. Please try again.';
            }
        }
    }
}

include 'partials/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report an Item - Lost & Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        .slide-up {
            animation: slideUp 0.8s ease-out forwards;
            transform: translateY(30px);
            opacity: 0;
        }

        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 via-blue-50/30 to-indigo-50/40 min-h-screen">

    <!-- Modern Hero Section -->
    <section class="relative bg-gradient-to-br from-purple-600 via-blue-600 to-purple-800 text-white overflow-hidden py-20">
        <div class="absolute inset-0 bg-black/10"></div>

        <!-- Background decoration -->
        <div class="absolute top-20 left-10 w-32 h-32 bg-white/10 rounded-full floating-animation"></div>
        <div class="absolute bottom-20 right-20 w-24 h-24 bg-white/10 rounded-full floating-animation" style="animation-delay: -2s;"></div>
        <div class="absolute top-40 right-40 w-16 h-16 bg-white/10 rounded-full floating-animation" style="animation-delay: -4s;"></div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center fade-in">
                <div class="mb-8">
                    <div class="w-24 h-24 glass-morphism rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl">
                        <i class="fas fa-plus text-4xl text-primary-600"></i>
                    </div>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                    Report an Item
                </h1>
                <p class="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto leading-relaxed">
                    Help reunite lost items with their owners by providing detailed information
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="py-16 -mt-10 relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

                <!-- Main Form -->
                <div class="lg:col-span-3">
                    <div class="card p-8 slide-up">
                        <?php if ($error): ?>
                            <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-xl flex items-center shadow-sm">
                                <i class="fas fa-exclamation-triangle mr-3 text-red-500"></i>
                                <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="mb-8 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-xl shadow-sm">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-check-circle mr-3 text-green-500"></i>
                                    <span class="font-medium"><?php echo htmlspecialchars($success); ?></span>
                                </div>
                                <a href="index.php" class="inline-flex items-center font-medium text-green-800 hover:text-green-900 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>Return to homepage
                                </a>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="space-y-8" id="reportForm">
                            <!-- Item Status Selection -->
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <i class="fas fa-clipboard-check mr-2 text-primary-600"></i>
                                    Item Status <span class="text-red-500">(Required)</span>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <!-- Lost -->
                                    <div class="status-card <?php echo (!isset($_POST['status']) || $_POST['status'] == 'lost') ? 'active' : ''; ?>" onclick="selectStatus('lost')">
                                        <input id="lost" name="status" type="radio" value="lost" required
                                            <?php echo (!isset($_POST['status']) || $_POST['status'] == 'lost') ? 'checked' : ''; ?>
                                            class="absolute top-4 right-4 h-5 w-5 text-primary-600" onchange="updateStatusCards()">
                                        <div class="text-center pr-8">
                                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                                <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
                                            </div>
                                            <h4 class="font-semibold text-gray-900 mb-1">Lost Item</h4>
                                            <p class="text-sm text-gray-500">I lost something and need help finding it</p>
                                        </div>
                                    </div>

                                    <!-- Found -->
                                    <div class="status-card <?php echo (isset($_POST['status']) && $_POST['status'] == 'found') ? 'active' : ''; ?>" onclick="selectStatus('found')">
                                        <input id="found" name="status" type="radio" value="found" required
                                            <?php echo (isset($_POST['status']) && $_POST['status'] == 'found') ? 'checked' : ''; ?>
                                            class="absolute top-4 right-4 h-5 w-5 text-primary-600" onchange="updateStatusCards()">
                                        <div class="text-center pr-8">
                                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                                <i class="fas fa-check-circle text-2xl text-green-600"></i>
                                            </div>
                                            <h4 class="font-semibold text-gray-900 mb-1">Found Item</h4>
                                            <p class="text-sm text-gray-500">I found something and want to return it</p>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- Item Details -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center border-b pb-2">
                                    <i class="fas fa-info-circle mr-2 text-primary-600"></i>
                                    Item Details
                                </h3>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <!-- Item Name -->
                                    <div class="lg:col-span-2">
                                        <label for="title" class="block text-sm font-medium text-gray-700 mb-3">
                                            Item Name <span class="text-red-500">(Required)</span>
                                        </label>
                                        <input id="title" name="title" type="text" required
                                            class="form-input text-lg" placeholder="e.g., iPhone 14 Pro, Blue Backpack..."
                                            value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-3">
                                            Category <span class="text-red-500">(Required)</span>
                                        </label>
                                        <select id="category_id" name="category_id" required class="form-select">
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>"
                                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Date -->
                                    <div>
                                        <label for="date_lost_found" class="block text-sm font-medium text-gray-700 mb-3">
                                            Date <span class="text-red-500">(Required)</span>
                                        </label>
                                        <input id="date_lost_found" name="date_lost_found" type="date" required
                                            class="form-input"
                                            value="<?php echo htmlspecialchars($_POST['date_lost_found'] ?? ''); ?>">
                                    </div>

                                    <!-- Map-->
                                    <div>
                                        <label for="map" class="block text-sm font-medium text-gray-700 mb-2">
                                            Map Location <span class="text-red-500">(Required)</span>
                                        </label>
                                        <div id="map" class="w-full h-64 rounded-lg border"></div>
                                        <input type="hidden" id="latitude" name="latitude" required>
                                        <input type="hidden" id="longitude" name="longitude" required>
                                    </div>

                                    <!-- Location -->
                                    <div class="lg:col-span-2">
                                        <label for="location" class="block text-sm font-medium text-gray-700 mb-3">
                                            Location <span class="text-red-500">(Required)</span>
                                        </label>
                                        <input id="location" name="location" type="text" required
                                            class="form-input" placeholder="Where was it lost/found? Be specific..."
                                            value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                                    </div>

                                    <!-- Description -->
                                    <div class="lg:col-span-2">
                                        <label for="description" class="block text-sm font-medium text-gray-700 mb-3">
                                            Detailed Description <span class="text-red-500">(Required)</span>
                                        </label>
                                        <textarea id="description" name="description" rows="4" required
                                            class="form-textarea"
                                            placeholder="Provide details like color, brand, size, distinctive features, condition, etc..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Image Upload Section -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center border-b pb-2">
                                    <i class="fas fa-camera mr-2 text-primary-600"></i>
                                    Add Photos
                                </h3>
                                <div class="drop-zone mt-1 flex justify-center px-6 pt-8 pb-8" id="dropZone">
                                    <div class="space-y-2 text-center">
                                        <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-cloud-upload-alt text-2xl text-primary-600"></i>
                                        </div>
                                        <div class="flex text-base text-gray-600 justify-center items-center">
                                            <label for="image" class="relative cursor-pointer">
                                                <span class="text-purple-400 font-medium hover:underline">Choose files</span>
                                                <input id="image" name="image" type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png" onchange="previewImage(event)">
                                            </label>
                                            <p class="pl-2">or drag and drop</p>
                                        </div>
                                        <p class="text-sm text-gray-500">PNG, JPG up to 2MB</p>
                                    </div>
                                </div>


                                <!-- Image Preview -->
                                <div id="imagePreview" class="hidden">
                                    <div class="text-sm font-medium text-gray-700 mb-3">Preview:</div>
                                    <div class="relative inline-block">
                                        <img id="previewImg" class="image-preview" src="" alt="Preview">
                                        <button type="button" onclick="removeImage()" class="absolute -top-2 -right-2 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors shadow-lg">
                                            <i class="fas fa-times text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="space-y-6">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center border-b pb-2">
                                    <i class="fas fa-user mr-2 text-primary-600"></i>
                                    Contact Information
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="contact_name" class="block text-sm font-medium text-gray-700 mb-3">Your Name <span class="text-red-500">(Required)</span></label>
                                        <input id="contact_name" name="contact_name" type="text" required
                                            class="form-input" placeholder="Full name"
                                            value="<?php echo htmlspecialchars($_POST['contact_name'] ?? $_SESSION['user_name'] ?? ''); ?>">
                                    </div>
                                    <div>
                                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-3">Email Address <span class="text-red-500">(Required)</span></label>
                                        <input id="contact_email" name="contact_email" type="email" required
                                            class="form-input" placeholder="your@email.com"
                                            value="<?php echo htmlspecialchars($_POST['contact_email'] ?? $_SESSION['user_email'] ?? ''); ?>">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-3">Phone Number (Optional)</label>
                                        <input id="contact_phone" name="contact_phone" type="tel"
                                            class="form-input" placeholder="+62 812-3456-7890"
                                            value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Submit -->
                            <div class="space-y-6 pt-6 border-t">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="terms" name="terms" type="checkbox" required
                                            class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="terms" class="text-gray-700">
                                            I agree to the <a href="#" class="text-primary-600 hover:text-primary-500 font-medium">Terms of Service</a>
                                            and <a href="#" class="text-primary-600 hover:text-primary-500 font-medium">Privacy Policy</a>.
                                            I understand that my contact information will be shared with potential claimants.
                                        </label>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-4">
                                    <button type="submit" class="btn-primary flex-1 text-lg py-4">
                                        <i class="fas fa-paper-plane mr-2"></i>Submit Report
                                    </button>
                                    <button type="button" class="btn-secondary flex-1 text-lg py-4" onclick="saveDraft()">
                                        <i class="fas fa-save mr-2"></i>Save Draft
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modern Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Tips Section -->
                    <div class="card p-6 slide-up" style="animation-delay: 0.2s;">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                            Helpful Tips
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-camera text-primary-500 mr-3 mt-1 flex-shrink-0"></i>
                                <span class="text-sm text-gray-700">Use clear, well-lit photos from multiple angles</span>
                            </div>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-map-marker-alt text-red-500 mr-3 mt-1 flex-shrink-0"></i>
                                <span class="text-sm text-gray-700">Be specific about the exact location</span>
                            </div>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-info-circle text-blue-500 mr-3 mt-1 flex-shrink-0"></i>
                                <span class="text-sm text-gray-700">Include distinctive features and serial numbers</span>
                            </div>
                            <div class="flex items-start p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-clock text-green-500 mr-3 mt-1 flex-shrink-0"></i>
                                <span class="text-sm text-gray-700">Report as soon as possible for better results</span>
                            </div>
                        </div>
                    </div>

                    <!-- Latest Reports -->
                    <div class="card p-6 slide-up" style="animation-delay: 0.4s;">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-clock text-primary-500 mr-2"></i>
                            Recent Reports
                        </h3>
                        <?php if (count($latest_reports) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($latest_reports as $report): ?>
                                    <div class="bg-gray-50 rounded-lg p-4 border-l-4 <?php echo $report['status'] == 'lost' ? 'border-red-400' : 'border-green-400'; ?>">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $report['status'] == 'lost' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                                                <i class="fas <?php echo $report['status'] == 'lost' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> mr-1"></i>
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                <?php echo date('M j', strtotime($report['created_at'])); ?>
                                            </span>
                                        </div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-1">
                                            <?php echo htmlspecialchars(substr($report['title'], 0, 30)) . (strlen($report['title']) > 30 ? '...' : ''); ?>
                                        </h4>
                                        <p class="text-xs text-gray-600 flex items-center">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?php echo htmlspecialchars(substr($report['location'], 0, 25)) . (strlen($report['location']) > 25 ? '...' : ''); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6">
                                <a href="cari.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium flex items-center">
                                    View all reports <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-6">
                                <i class="fas fa-inbox text-gray-300 text-3xl mb-3"></i>
                                <p class="text-sm text-gray-500">No reports yet. Be the first!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Map functionality
        var map = L.map('map').setView([-6.2, 106.8], 13); // default Jakarta
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var marker;
        map.on('click', function(e) {
            var lat = e.latlng.lat;
            var lng = e.latlng.lng;

            document.getElementById("latitude").value = lat;
            document.getElementById("longitude").value = lng;

            if (marker) {
                map.removeLayer(marker);
            }
            marker = L.marker([lat, lng]).addTo(map);
        });

        // Status card updates
        function updateStatusCards() {
            const cards = document.querySelectorAll('.status-card');
            cards.forEach(card => {
                const input = card.querySelector('input[type="radio"]');
                if (input.checked) {
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                }
            });
        }

        function selectStatus(status) {
            const radioButton = document.getElementById(status);
            radioButton.checked = true;
            updateStatusCards();
        }

        // Image preview functionality
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        function removeImage() {
            document.getElementById('image').value = '';
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('previewImg').src = '';
        }

        // Enhanced drag and drop functionality
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('image');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('dragover');
        }

        function unhighlight(e) {
            dropZone.classList.remove('dragover');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                fileInput.files = files;
                previewImage({
                    target: {
                        files: files
                    }
                });
            }
        }

        // Save draft functionality with modern notifications
        function saveDraft() {
            const formData = new FormData(document.getElementById('reportForm'));
            const draftData = {};

            for (let [key, value] of formData.entries()) {
                if (key !== 'image') { // Don't save file data
                    draftData[key] = value;
                }
            }

            localStorage.setItem('reportDraft', JSON.stringify(draftData));

            // Modern notification
            showNotification('Draft saved successfully!', 'success');
        }

        // Modern notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-6 right-6 z-50 max-w-sm bg-white border border-gray-200 rounded-xl shadow-lg transform translate-x-full transition-transform duration-300 ease-out`;

            const bgColor = type === 'success' ? 'bg-green-50 border-green-200' :
                type === 'error' ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200';
            const textColor = type === 'success' ? 'text-green-700' :
                type === 'error' ? 'text-red-700' : 'text-blue-700';
            const iconClass = type === 'success' ? 'fa-check-circle text-green-500' :
                type === 'error' ? 'fa-exclamation-circle text-red-500' : 'fa-info-circle text-blue-500';

            notification.innerHTML = `
            <div class="p-4 ${bgColor}">
                <div class="flex items-center">
                    <i class="fas ${iconClass} mr-3"></i>
                    <span class="font-medium ${textColor}">${message}</span>
                </div>
            </div>
        `;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Animate out and remove
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Load draft on page load
        window.addEventListener('load', function() {
            const draft = localStorage.getItem('reportDraft');
            if (draft && !document.querySelector('.bg-green-50')) { // Don't load if success message is shown
                const draftData = JSON.parse(draft);

                const shouldRestore = confirm('Would you like to restore your saved draft?');
                if (shouldRestore) {
                    Object.keys(draftData).forEach(key => {
                        const element = document.querySelector(`[name="${key}"]`);
                        if (element) {
                            if (element.type === 'radio') {
                                if (element.value === draftData[key]) {
                                    element.checked = true;
                                    updateStatusCards();
                                }
                            } else if (element.type === 'checkbox') {
                                element.checked = draftData[key] === 'on' || draftData[key] === true;
                            } else {
                                element.value = draftData[key];
                            }
                        }
                    });
                    showNotification('Draft restored successfully!', 'success');
                }
            }

            // Initialize animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            });

            document.querySelectorAll('.slide-up').forEach(el => {
                observer.observe(el);
            });
        });

        // Enhanced form validation with modern styling
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            const errors = [];

            requiredFields.forEach(field => {
                const value = field.value.trim();
                field.classList.remove('border-red-300', 'bg-red-50');

                if (!value) {
                    field.classList.add('border-red-300', 'bg-red-50');
                    isValid = false;

                    // Get field label
                    const label = document.querySelector(`label[for="${field.id}"]`);
                    const fieldName = label ? label.textContent.replace('*', '').trim() : field.name;
                    errors.push(fieldName);
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification(`Please fill in: ${errors.join(', ')}`, 'error');

                // Scroll to first error field
                const firstError = this.querySelector('.border-red-300');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstError.focus();
                }
            } else {
                // Clear draft on successful submission
                localStorage.removeItem('reportDraft');
            }
        });

        // Real-time validation feedback
        document.querySelectorAll('[required]').forEach(field => {
            field.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.classList.remove('border-red-300', 'bg-red-50');
                    this.classList.add('border-green-300', 'bg-green-50');
                }
            });

            field.addEventListener('input', function() {
                if (this.classList.contains('border-red-300')) {
                    this.classList.remove('border-red-300', 'bg-red-50');
                }
            });
        });

        // Initialize status cards
        updateStatusCards();
    </script>

    <?php include 'partials/footer.php'; ?>
</body>

</html>