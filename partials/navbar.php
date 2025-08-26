<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="index.php" class="flex items-center space-x-2">
                    <img src="http://localhost/LostNFound/image/lnf.png" alt="Logo" style="width: 30px; height: 30px;">
                    <span class="text-xl font-bold text-gray-800">Lost & Found</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-700 hover:text-purple-600'; ?> px-3 py-2 text-sm font-medium transition-colors">
                    Home
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="laporan.php" class="<?php echo $current_page == 'laporan.php' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-700 hover:text-purple-600'; ?> px-3 py-2 text-sm font-medium transition-colors">
                        Report
                    </a>
                    <a href="cari.php" class="<?php echo $current_page == 'cari.php' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-700 hover:text-purple-600'; ?> px-3 py-2 text-sm font-medium transition-colors">
                        Search
                    </a>
                    <a href="profil.php" class="<?php echo $current_page == 'profil.php' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-700 hover:text-purple-600'; ?> px-3 py-2 text-sm font-medium transition-colors">
                        My Profile
                    </a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Sign out
                    </a>
                <?php else: ?>
                    <a href="cari.php" class="<?php echo $current_page == 'cari.php' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-700 hover:text-purple-600'; ?> px-3 py-2 text-sm font-medium transition-colors">
                        Search
                    </a>
                    <a href="login.php" class="text-gray-700 hover:text-purple-600 px-3 py-2 text-sm font-medium transition-colors">
                        Sign in
                    </a>
                    <a href="register.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Sign up
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button type="button" id="mobile-menu-button" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t">
                <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'bg-purple-50 text-purple-600' : 'text-gray-700 hover:bg-gray-50'; ?> block px-3 py-2 text-base font-medium rounded-md">
                    Home
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="laporan.php" class="<?php echo $current_page == 'laporan.php' ? 'bg-purple-50 text-purple-600' : 'text-gray-700 hover:bg-gray-50'; ?> block px-3 py-2 text-base font-medium rounded-md">
                        Report
                    </a>
                    <a href="cari.php" class="<?php echo $current_page == 'cari.php' ? 'bg-purple-50 text-purple-600' : 'text-gray-700 hover:bg-gray-50'; ?> block px-3 py-2 text-base font-medium rounded-md">
                        Search
                    </a>
                    <a href="profil.php" class="<?php echo $current_page == 'profil.php' ? 'bg-purple-50 text-purple-600' : 'text-gray-700 hover:bg-gray-50'; ?> block px-3 py-2 text-base font-medium rounded-md">
                        My Profile
                    </a>
                    <a href="logout.php" class="text-red-600 hover:bg-red-50 block px-3 py-2 text-base font-medium rounded-md">
                        Sign out
                    </a>
                <?php else: ?>
                    <a href="cari.php" class="<?php echo $current_page == 'cari.php' ? 'bg-purple-50 text-purple-600' : 'text-gray-700 hover:bg-gray-50'; ?> block px-3 py-2 text-base font-medium rounded-md">
                        Search
                    </a>
                    <a href="login.php" class="text-gray-700 hover:bg-gray-50 block px-3 py-2 text-base font-medium rounded-md">
                        Sig in
                    </a>
                    <a href="register.php" class="bg-purple-600 text-white hover:bg-purple-700 block px-3 py-2 text-base font-medium rounded-md">
                        Sign up
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
    });
</script>