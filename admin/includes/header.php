<?php
// Récupérer les informations de l'utilisateur connecté
$user = getUserById($_SESSION['user_id']);
?>
<nav class="bg-red-600 text-white shadow-lg fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-4">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                    <span class="text-red-600 font-bold text-sm">IA</span>
                </div>
                <span class="text-xl font-bold">Administration</span>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Mobile menu button -->
                <button id="openAdminMobileSidebar" class="md:hidden inline-flex items-center px-3 py-2 rounded bg-red-700 hover:bg-red-800">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="text-sm">Administrateur : <?php echo htmlspecialchars($user['full_name']); ?></span>
                <a href="../logout.php" class="bg-red-700 hover:bg-red-800 px-3 py-2 rounded-lg text-sm transition-colors">
                    <i class="fas fa-sign-out-alt mr-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>
