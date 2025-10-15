<?php
$isLoggedIn = isLoggedIn();
$user = null;

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
}
?>
<style>
/* Cross-browser baseline for consistent rendering */
*, *::before, *::after { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Inter, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji"; }
img, video { max-width: 100%; height: auto; display: block; }
a { color: inherit; text-decoration: none; }
button, input, select, textarea { font: inherit; }
.sidebar-scroll { scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc; }
.sidebar-scroll::-webkit-scrollbar { width: 8px; }
.sidebar-scroll::-webkit-scrollbar-track { background: #f7fafc; border-radius: 4px; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 4px; }
.sidebar-scroll::-webkit-scrollbar-thumb:hover { background: #a0aec0; }
</style>
<nav class="bg-white shadow-lg fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <a href="index.php" class="flex items-center">
                        <img src="images/EDUTECH.png" alt="Académie IA" class="w-28 h-28 object-contain drop-shadow-lg">
                    </a>
                </div>
            </div>
            
            <!-- Menu mobile -->
            <div class="flex items-center sm:hidden">
                <button type="button" class="mobile-menu-button text-gray-700 hover:text-blue-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            
            <!-- Menu desktop -->
            <div class="hidden sm:flex items-center space-x-2 lg:space-x-4">
                <?php if ($isLoggedIn): ?>
                                    <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="text-gray-700 hover:text-red-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-cog mr-1"></i>Administration
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-1"></i>Tableau de bord
                    </a>
                <?php endif; ?>
                    <a href="courses.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-graduation-cap mr-1"></i>Cours
                    </a>
                    <a href="bibliotheque.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-book mr-1"></i>Bibliothèque
                    </a>
                    <a href="resources.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-folder mr-1"></i>Ressources
                    </a>
                    <a href="certifications.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-certificate mr-1"></i>Certifications
                    </a>
                    <a href="forum.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-comments mr-1"></i>Forum
                    </a>
                    
                    <!-- Menu utilisateur -->
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <div class="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-full flex items-center justify-center">
                                <span class="text-white font-medium text-sm">
                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                </span>
                            </div>
                            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 hidden">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Profil
                            </a>
                            <?php if ($user['role'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-cog mr-2"></i>Administration
                            </a>
                            <?php endif; ?>
                            <hr class="my-1">
                            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 logout-button">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="bibliotheque.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-book mr-1"></i>Bibliothèque
                    </a>
                    <a href="resources.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-folder mr-1"></i>Ressources
                    </a>
                    <a href="certifications.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-certificate mr-1"></i>Certifications
                    </a>
                    <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-in-alt mr-1"></i>Connexion
                    </a>
                    <a href="register.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user-plus mr-1"></i>Inscription
                    </a>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
    
    <!-- Menu mobile -->
    <div class="mobile-menu hidden sm:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t border-gray-200">
            <?php if ($isLoggedIn): ?>
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="block px-3 py-2 text-gray-700 hover:text-red-600 text-base font-medium">
                        <i class="fas fa-cog mr-2"></i>Administration
                    </a>
                <?php else: ?>
                    <a href="dashboard.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                        <i class="fas fa-home mr-2"></i>Tableau de bord
                    </a>
                <?php endif; ?>
                <a href="courses.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-graduation-cap mr-2"></i>Cours
                </a>
                <a href="bibliotheque.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-book mr-2"></i>Bibliothèque
                </a>
                <a href="resources.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-folder mr-2"></i>Ressources
                </a>
                <a href="certifications.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-certificate mr-2"></i>Certifications
                </a>
                <a href="forum.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-comments mr-2"></i>Forum
                </a>
                <hr class="my-2">
                <a href="profile.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-user mr-2"></i>Profil
                </a>
                <?php if ($user['role'] === 'teacher' || $user['role'] === 'admin'): ?>
                <a href="admin.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-cog mr-2"></i>Administration
                </a>
                <?php endif; ?>
                <hr class="my-2">
                <a href="logout.php" class="block px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 text-base font-medium border-l-4 border-red-500 logout-button">
                    <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                </a>
            <?php else: ?>
                <a href="bibliotheque.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-book mr-2"></i>Bibliothèque
                </a>
                <a href="resources.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-folder mr-2"></i>Ressources
                </a>
                <a href="certifications.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-certificate mr-2"></i>Certifications
                </a>
                <a href="login.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                </a>
                <a href="register.php" class="block px-3 py-2 text-gray-700 hover:text-blue-600 text-base font-medium">
                    <i class="fas fa-user-plus mr-2"></i>Inscription
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script src="assets/js/app.js"></script>


