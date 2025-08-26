<?php
require_once 'config/db.php';
include 'partials/navbar.php';

// Get search parameters
$keyword = trim($_GET['keyword'] ?? '');
$category_id = $_GET['category_id'] ?? '';
$location = trim($_GET['location'] ?? '');
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get popular categories (top 6 by item count)
$stmt = $pdo->query("
    SELECT c.*, COUNT(l.id) as item_count 
    FROM categories c 
    LEFT JOIN listings l ON c.id = l.category_id 
    WHERE (l.status != 'returned' OR l.status IS NULL)
      AND (l.is_verified = 1 OR l.id IS NULL)
    GROUP BY c.id 
    ORDER BY item_count DESC 
    LIMIT 6
");
$popular_categories = $stmt->fetchAll();


// Get latest reports for sidebar
$stmt = $pdo->prepare("
    SELECT l.*, c.name as category_name 
    FROM listings l 
    JOIN categories c ON l.category_id = c.id 
    WHERE l.status != 'returned' 
      AND l.is_verified = 1
    ORDER BY l.created_at DESC 
    LIMIT 8
");
$stmt->execute();
$recent_reports = $stmt->fetchAll();

// Build search query
$where_conditions = ["l.status != 'returned'"];
$params = [];

if ($keyword) {
    $where_conditions[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if ($category_id) {
    $where_conditions[] = "l.category_id = ?";
    $params[] = $category_id;
}

if ($location) {
    $where_conditions[] = "l.location LIKE ?";
    $params[] = "%$location%";
}

if ($status) {
    $where_conditions[] = "l.status = ?";
    $params[] = $status;
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM listings l WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetch()['total'];
$total_pages = ceil($total_items / $limit);

// Get items
$sql = "SELECT l.*, c.name as category_name, u.name as user_name 
        FROM listings l 
        JOIN categories c ON l.category_id = c.id 
        JOIN users u ON l.user_id = u.id 
        WHERE l.is_verified = 1 AND $where_clause 
        ORDER BY l.created_at DESC 
        LIMIT $limit OFFSET $offset";

$items_stmt = $pdo->prepare($sql);
$items_stmt->execute($params);
$items = $items_stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Items - Lost & Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">

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
                        <i class="fas fa-search text-4xl text-primary-600"></i>
                    </div>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                    Search Lost & Found Items
                </h1>
                <p class="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto leading-relaxed">
                    Use our advanced filters to find specific items or browse through all recent reports from the community
                </p>
            </div>
        </div>
    </section>

    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Popular Categories -->
            <?php if (count($popular_categories) > 0): ?>
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-fire text-orange-500 mr-2"></i>Popular Categories
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <?php foreach ($popular_categories as $cat): ?>
                            <a href="?category_id=<?php echo $cat['id']; ?>" class="group bg-white p-4 rounded-xl border border-gray-200 hover:border-purple-300 hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform duration-300">
                                        <i class="fas fa-tag text-xl text-white"></i>
                                    </div>
                                    <h3 class="font-medium text-gray-900 mb-1 text-sm"><?php echo htmlspecialchars($cat['name']); ?></h3>
                                    <p class="text-xs text-gray-500"><?php echo $cat['item_count']; ?> items</p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Search Filters -->
                    <div class="card p-6 mb-8 shadow-lg">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-filter mr-2"></i>Search Filters
                            </h3>
                            <?php if ($keyword || $category_id || $location || $status): ?>
                                <a href="cari.php" class="text-sm text-purple-600 hover:text-purple-700 flex items-center">
                                    <i class="fas fa-times mr-1"></i>Clear All
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <form method="GET" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Keyword Search -->
                                <div>
                                    <label for="keyword" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-search mr-1"></i>Keyword
                                    </label>
                                    <input id="keyword" name="keyword" type="text" 
                                           class="form-input" placeholder="Search items..."
                                           value="<?php echo htmlspecialchars($keyword); ?>">
                                </div>

                                <!-- Category Filter -->
                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-list mr-1"></i>Category
                                    </label>
                                    <select id="category_id" name="category_id" class="form-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Location Filter -->
                                <div>
                                    <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-map-marker-alt mr-1"></i>Location
                                    </label>
                                    <input id="location" name="location" type="text" 
                                           class="form-input" placeholder="Location..."
                                           value="<?php echo htmlspecialchars($location); ?>">
                                </div>

                                <!-- Status Filter -->
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-info-circle mr-1"></i>Status
                                    </label>
                                    <select id="status" name="status" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="lost" <?php echo $status == 'lost' ? 'selected' : ''; ?>>Lost</option>
                                        <option value="found" <?php echo $status == 'found' ? 'selected' : ''; ?>>Found</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Search Button -->
                            <div class="flex justify-between items-center pt-4 border-t">
                                <div>
                                    <?php if ($keyword || $category_id || $location || $status): ?>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Found <span class="font-semibold"><?php echo number_format($total_items); ?></span> items
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" class="btn-primary transform hover:scale-105 transition-all duration-300">
                                    <i class="fas fa-search mr-2"></i>Search
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Results -->
                    <?php if (count($items) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                            <?php foreach ($items as $item): ?>
                                <div class="card overflow-hidden group hover:-translate-y-2 transition-all duration-300 hover:shadow-xl">
                                    <div class="aspect-w-16 aspect-h-12 bg-gray-200 relative">
                                        <?php if ($item['image_path']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                 class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                                        <?php else: ?>
                                            <div class="w-full h-48 bg-gradient-to-br from-purple-100 to-blue-100 flex items-center justify-center">
                                                <i class="fas fa-image text-4xl text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Status Badge -->
                                        <div class="absolute top-2 left-2">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-white/90 backdrop-blur-sm <?php echo $item['status'] == 'lost' ? 'text-red-800' : 'text-green-800'; ?>">
                                                <i class="fas <?php echo $item['status'] == 'lost' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> mr-1"></i>
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Date Badge -->
                                        <div class="absolute top-2 right-2">
                                            <span class="inline-block px-2 py-1 text-xs font-medium rounded-full bg-black/50 text-white backdrop-blur-sm">
                                                <?php echo date('M j', strtotime($item['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="p-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </h3>
                                        <p class="text-sm text-gray-600 mb-3 line-clamp-2">
                                            <?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                        
                                        <!-- Location and Category -->
                                        <div class="flex items-center text-xs text-gray-500 mb-3 space-x-4">
                                            <div class="flex items-center">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                <?php echo htmlspecialchars(substr($item['location'], 0, 20)) . (strlen($item['location']) > 20 ? '...' : ''); ?>
                                            </div>
                                            <div class="flex items-center">
                                                <i class="fas fa-tag mr-1"></i>
                                                <?php echo htmlspecialchars($item['category_name']); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Button -->
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500">
                                                By <?php echo htmlspecialchars($item['user_name']); ?>
                                            </span>
                                            <a href="detail.php?id=<?php echo $item['id']; ?>" 
                                               class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transform hover:scale-105 transition-all duration-300">
                                                <i class="fas fa-eye mr-1"></i>View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="flex items-center justify-center space-x-2 mt-8">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:text-gray-700 hover:border-gray-400 transition-colors duration-300">
                                        <i class="fas fa-chevron-left mr-1"></i>Previous
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="px-4 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-purple-600 border-purple-600' : 'text-gray-500 bg-white border-gray-300 hover:text-gray-700 hover:border-gray-400'; ?> border rounded-lg transition-colors duration-300">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:text-gray-700 hover:border-gray-400 transition-colors duration-300">
                                        Next<i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Enhanced Empty State -->
                        <div class="text-center py-16 bg-white rounded-xl shadow-lg">
                            <div class="max-w-md mx-auto">
                                <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                    <i class="fas fa-search text-4xl text-gray-400"></i>
                                </div>
                                <h3 class="text-2xl font-semibold text-gray-900 mb-4">No Items Found</h3>
                                <p class="text-gray-600 mb-8">
                                    <?php if ($keyword || $category_id || $location || $status): ?>
                                        We couldn't find any items matching your search criteria. Try adjusting your filters or search terms.
                                    <?php else: ?>
                                        No items have been reported yet. Be the first to report an item and help build our community!
                                    <?php endif; ?>
                                </p>
                                <div class="space-y-4">
                                    <?php if ($keyword || $category_id || $location || $status): ?>
                                        <a href="cari.php" class="btn-secondary inline-flex items-center">
                                            <i class="fas fa-redo mr-2"></i>Clear All Filters
                                        </a>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div>
                                            <a href="laporan.php" class="btn-primary inline-flex items-center">
                                                <i class="fas fa-plus mr-2"></i>Report an Item
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <a href="register.php" class="btn-primary inline-flex items-center">
                                                <i class="fas fa-user-plus mr-2"></i>Join Our Community
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Quick Actions -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                        </h3>
                        <div class="space-y-3">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="laporan.php" class="block w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white text-center py-3 rounded-lg font-medium hover:from-purple-600 hover:to-blue-600 transition-all duration-300 transform hover:scale-105">
                                    <i class="fas fa-plus mr-2"></i>Report Item
                                </a>
                            <?php else: ?>
                                <a href="register.php" class="block w-full bg-gradient-to-r from-purple-500 to-blue-500 text-white text-center py-3 rounded-lg font-medium hover:from-purple-600 hover:to-blue-600 transition-all duration-300 transform hover:scale-105">
                                    <i class="fas fa-user-plus mr-2"></i>Join Community
                                </a>
                            <?php endif; ?>
                            <a href="?status=lost" class="block w-full bg-red-50 text-red-700 text-center py-3 rounded-lg font-medium hover:bg-red-100 transition-colors duration-300">
                                <i class="fas fa-exclamation-circle mr-2"></i>Browse Lost Items
                            </a>
                            <a href="?status=found" class="block w-full bg-green-50 text-green-700 text-center py-3 rounded-lg font-medium hover:bg-green-100 transition-colors duration-300">
                                <i class="fas fa-check-circle mr-2"></i>Browse Found Items
                            </a>
                        </div>
                    </div>

                    <!-- Recent Reports -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-clock text-blue-500 mr-2"></i>Recent Reports
                        </h3>
                        <?php if (count($recent_reports) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach (array_slice($recent_reports, 0, 5) as $report): ?>
                                    <div class="border-l-4 <?php echo $report['status'] == 'lost' ? 'border-red-400' : 'border-green-400'; ?> pl-3 py-2 hover:bg-gray-50 rounded-r-lg transition-colors duration-300">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium <?php echo $report['status'] == 'lost' ? 'text-red-600' : 'text-green-600'; ?>">
                                                <i class="fas <?php echo $report['status'] == 'lost' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> mr-1"></i>
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                <?php echo date('M j', strtotime($report['created_at'])); ?>
                                            </span>
                                        </div>
                                        <h4 class="text-sm font-medium text-gray-900 mb-1 hover:text-purple-600 transition-colors duration-300">
                                            <a href="detail.php?id=<?php echo $report['id']; ?>">
                                                <?php echo htmlspecialchars(substr($report['title'], 0, 35)) . (strlen($report['title']) > 35 ? '...' : ''); ?>
                                            </a>
                                        </h4>
                                        <p class="text-xs text-gray-600">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?php echo htmlspecialchars(substr($report['location'], 0, 25)) . (strlen($report['location']) > 25 ? '...' : ''); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4">
                                <a href="cari.php" class="text-sm text-purple-600 hover:text-purple-700">
                                    View all reports <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox text-2xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500">No reports yet</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Search Tips -->
                    <div class="card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Search Tips
                        </h3>
                        <div class="space-y-3 text-sm text-gray-600">
                            <div class="flex items-start">
                                <i class="fas fa-search text-purple-500 mr-2 mt-1 flex-shrink-0"></i>
                                <span>Use specific keywords like brand names or colors</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-filter text-blue-500 mr-2 mt-1 flex-shrink-0"></i>
                                <span>Combine multiple filters for better results</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-bell text-green-500 mr-2 mt-1 flex-shrink-0"></i>
                                <span>Check back regularly for new reports</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-map-marker-alt text-red-500 mr-2 mt-1 flex-shrink-0"></i>
                                <span>Search by location for nearby items</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'partials/footer.php'; ?>
</body>
</html>