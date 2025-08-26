<?php

require_once '../config/db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT l.*, u.name AS user_name 
    FROM listings l
    JOIN users u ON l.user_id = u.id
    WHERE l.is_verified = 0
    ORDER BY l.created_at DESC
");
$stmt->execute();
$items = $stmt->fetchAll();

// count all report items
$stmt = $pdo->query("SELECT COUNT(*) as total FROM listings");
$total_items = $stmt->fetchColumn();


// count all pending report items
$stmt = $pdo->query("SELECT COUNT(*) FROM listings WHERE is_verified = 0");
$pending_reports = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar-item {
            transition: all 0.2s ease;
        }

        .sidebar-item:hover {
            background-color: #6366f1;
            color: white;
        }

        .sidebar-item.active {
            background-color: #6366f1;
            color: white;
        }

        .mobile-menu-overlay {
            display: none;
        }

        .mobile-menu-overlay.active {
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 px-4 lg:px-6 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <!-- Mobile Menu Button -->
            <button id="mobileMenuBtn" class="md:hidden text-gray-600 hover:text-indigo-600">
                <i class="fas fa-bars text-lg"></i>
            </button>

            <div class="text-white p-2 rounded">
                <img src="../image/lnf.png" alt="" class="w-7 h-7">
            </div>
            <h1 class="text-lg font-semibold text-gray-800">Lost & Found Admin</h1>

            <!-- Desktop Navigation -->
            <div class="hidden lg:flex space-x-6 ml-8">
                <a href="#" class="text-gray-600 hover:text-indigo-600 font-medium">Dashboard</a>
                <a href="#" class="text-gray-600 hover:text-indigo-600">Items</a>
                <a href="#" class="text-gray-600 hover:text-indigo-600">Users</a>
                <a href="#" class="text-gray-600 hover:text-indigo-600">Reports</a>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <div class="relative">
                <i class="fas fa-bell text-gray-500"></i>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">3</span>
            </div>
            <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                <span class="text-white text-sm font-medium">A</span>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div id="mobileMenuOverlay" class="mobile-menu-overlay fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden">
    </div>

    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="sidebar fixed md:relative w-64 bg-white shadow-sm min-h-screen border-r border-gray-200 z-50 md:z-auto">
            <div class="p-4 md:p-6">
                <!-- Mobile Close Button -->
                <div class="flex items-center justify-between mb-4 md:hidden">
                    <h2 class="text-lg font-semibold text-gray-800">Menu</h2>
                    <button id="closeSidebarBtn" class="text-gray-600 hover:text-indigo-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <div class="space-y-2">
                    <div class="sidebar-item active px-4 py-2 rounded-lg cursor-pointer flex items-center space-x-3">
                        <i class="fas fa-tachometer-alt text-sm"></i>
                        <span class="font-medium">Dashboard</span>
                    </div>
                    <form action="../logout.php" method="POST">
                        <button type="submit"
                            class="sidebar-item px-4 py-2 rounded-lg cursor-pointer flex items-center space-x-3 text-gray-600 w-full text-left">
                            <i class="fas fa-sign-out-alt text-sm"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-4 md:p-6 md:ml-0">
            <!-- Dashboard Overview Header -->
            <div class="mb-6 md:mb-8">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 mb-2">Dashboard Overview</h2>
                <p class="text-gray-600 text-sm md:text-base">Manage and track lost and found items efficiently</p>
            </div>

            <!-- Search Bar -->
            <div class="mb-6 md:mb-8">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" placeholder="Search items, users, or reports..." class="w-full pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm md:text-base">
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6 md:mb-8">
                <!-- Total Items -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm text-gray-600 mb-1">Total Items</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900">
                                <?php echo $total_items; ?>
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-box text-blue-600 text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Pending Reports -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm text-gray-600 mb-1">Pending Reports</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900">
                                <?php echo $pending_reports; ?>
                            </p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-orange-600 text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Resolved Cases -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm text-gray-600 mb-1">Resolved Cases</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900">189</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs md:text-sm text-gray-600 mb-1">Active Users</p>
                            <p class="text-2xl md:text-3xl font-bold text-gray-900">156</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-purple-600 text-lg md:text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unverified Reports Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 md:mb-8">
                <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-2 sm:space-y-0">
                    <h3 class="text-lg font-semibold text-gray-900">Unverified Reports</h3>
                    <span class="bg-orange-100 text-orange-800 text-sm font-medium px-3 py-1 rounded-full self-start">23 Pending</span>
                </div>
                <div class="p-4 md:p-6">
                    <!-- Report Items -->
                    <div class="space-y-4">
                        <!-- unverified item list -->
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border border-gray-200 rounded-lg space-y-3 sm:space-y-0">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-mobile-alt text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 text-sm md:text-base">
                                                <?php echo htmlspecialchars($item['title']); ?> - <?php echo ucfirst($item['status']); ?>
                                            </h4>
                                            <p class="text-xs md:text-sm text-gray-600">
                                                Reported by <?php echo htmlspecialchars($item['user_name']); ?> •
                                                <?php echo date('M j, H:i', strtotime($item['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2 self-end sm:self-auto">
                                        <a href="detail.php?id=<?php echo $item['id']; ?>"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 md:px-4 py-1 rounded text-xs md:text-sm font-medium">
                                            Details
                                        </a>

                                        <a href="approve.php?id=<?php echo $item['id']; ?>"
                                            class="bg-green-600 hover:bg-green-700 text-white px-3 md:px-4 py-1 rounded text-xs md:text-sm font-medium">
                                            Approve
                                        </a>

                                        <a href="reject.php?id=<?php echo $item['id']; ?>"
                                            class="bg-red-600 hover:bg-red-700 text-white px-3 md:px-4 py-1 rounded text-xs md:text-sm font-medium">
                                            Reject
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500">No reports found.</p>
                        <?php endif; ?>

                        <!-- View All Link -->
                        <div class="mt-6 text-center">
                            <a href="#" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm md:text-base">View All Pending Reports</a>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Mobile menu functionality
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            const closeSidebarBtn = document.getElementById('closeSidebarBtn');

            function openSidebar() {
                sidebar.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            mobileMenuBtn.addEventListener('click', openSidebar);
            closeSidebarBtn.addEventListener('click', closeSidebar);
            mobileMenuOverlay.addEventListener('click', closeSidebar);

            // Close sidebar when clicking on sidebar items on mobile
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            sidebarItems.forEach(item => {
                item.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        closeSidebar();
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 768) {
                    closeSidebar();
                }
            });
        </script>
    </div>

</html>