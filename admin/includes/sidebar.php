<?php
// Déterminer la page active pour le menu avec une logique plus robuste
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_path = $_SERVER['PHP_SELF'];

// Fonction pour vérifier si une page est active
function isActivePage($page_name, $current_page, $current_path) {
    // Vérification exacte
    if ($current_page === $page_name) {
        return true;
    }
    
    // Vérification pour les sous-pages (ex: edit_user.php dans la section users)
    if (strpos($current_path, $page_name) !== false) {
        return true;
    }
    
    return false;
}

// Classes CSS pour les états
$active_classes = 'bg-red-50 border-l-4 border-red-500 text-red-700 font-medium shadow-sm';
$inactive_classes = 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200';
$icon_active_classes = 'text-red-600';
$icon_inactive_classes = 'text-gray-500 group-hover:text-gray-700';
?>
<style>
    /* Barre de défilement personnalisée pour WebKit */
    .sidebar-scroll::-webkit-scrollbar {
        width: 8px;
    }
    .sidebar-scroll::-webkit-scrollbar-track {
        background: #f7fafc;
        border-radius: 4px;
    }
    .sidebar-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 4px;
    }
    .sidebar-scroll::-webkit-scrollbar-thumb:hover {
        background: #a0aec0;
    }
</style>
<!-- Desktop sidebar -->
<div class="hidden md:block w-64 bg-white shadow-md border-r border-gray-200 rounded-r-xl fixed left-0 top-16 h-[calc(100vh-4rem-1.5rem)] z-30">
    <div class="px-3 pt-3 pb-3 h-full overflow-y-auto">
        <!-- Profil administrateur -->
        <div class="text-center mb-3">
            <div class="w-14 h-14 bg-gradient-to-r from-red-600 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-2 shadow-sm">
                <i class="fas fa-user-shield text-white text-lg"></i>
            </div>
            <h3 class="font-semibold text-gray-900 text-sm tracking-tight"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h3>
            <p class="text-xs text-gray-500">Administrateur</p>
        </div>
        
        <!-- Navigation -->
        <nav class="space-y-1 mb-0">
            <a href="dashboard.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('dashboard', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-tachometer-alt mr-2.5 w-4 <?php echo isActivePage('dashboard', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Tableau de bord
            </a>
            <a href="users.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('users', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-users mr-2.5 w-4 <?php echo isActivePage('users', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Utilisateurs
            </a>
            <a href="courses.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('courses', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-graduation-cap mr-2.5 w-4 <?php echo isActivePage('courses', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Cours
            </a>
            <a href="resources.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('resources', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-book mr-2.5 w-4 <?php echo isActivePage('resources', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Ressources
            </a>
            <a href="certifications.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('certifications', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-certificate mr-2.5 w-4 <?php echo isActivePage('certifications', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Certifications
            </a>
            <a href="forum.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('forum', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-comments mr-2.5 w-4 <?php echo isActivePage('forum', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Modération Forum
            </a>
            <a href="settings.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('settings', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-cog mr-2.5 w-4 <?php echo isActivePage('settings', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Paramètres
            </a>
        </nav>
        
        <!-- Actions rapides -->
        <div class="mt-6 space-y-2">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Actions</div>
            <a href="users.php" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-user-plus mr-2 w-3"></i>
                Ajouter un utilisateur
            </a>
            <a href="settings.php" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-cog mr-2 w-3"></i>
                Configuration système
            </a>
        </div>
        
        <!-- Lien de retour -->
        <div class="mt-6">
            <a href="../index.php" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200 rounded-lg">
                <i class="fas fa-arrow-left mr-2 w-4"></i>
                <span>Retour au site</span>
            </a>
        </div>
    </div>
</div>

<!-- Mobile sidebar drawer -->
<div id="adminMobileBackdrop" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50"></div>
<div id="adminMobileSidebar" class="hidden md:hidden fixed left-0 top-16 h-[calc(100vh-4rem-1.5rem)] w-64 bg-white shadow-lg z-50 overflow-y-scroll overscroll-contain border-r border-gray-200 sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
    <div class="px-3 pt-3 pb-3 h-full overflow-y-auto">
        <!-- Profil administrateur mobile -->
        <div class="text-center mb-3">
            <div class="w-12 h-12 bg-gradient-to-r from-red-600 to-pink-600 rounded-xl flex items-center justify-center mx-auto mb-2 shadow-sm">
                <i class="fas fa-user-shield text-white text-sm"></i>
            </div>
            <h3 class="font-semibold text-gray-900 text-xs tracking-tight"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h3>
            <p class="text-xs text-gray-500">Administrateur</p>
        </div>
        <!-- Navigation mobile -->
        <nav class="space-y-1 mb-0">
            <a href="dashboard.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('dashboard', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-tachometer-alt mr-2.5 w-4 <?php echo isActivePage('dashboard', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Tableau de bord
            </a>
            <a href="users.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('users', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-users mr-2.5 w-4 <?php echo isActivePage('users', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Utilisateurs
            </a>
            <a href="courses.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('courses', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-graduation-cap mr-2.5 w-4 <?php echo isActivePage('courses', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Cours
            </a>
            <a href="resources.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('resources', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-book mr-2.5 w-4 <?php echo isActivePage('resources', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Ressources
            </a>
            <a href="certifications.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('certifications', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-certificate mr-2.5 w-4 <?php echo isActivePage('certifications', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Gestion Certifications
            </a>
            <a href="forum.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('forum', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-comments mr-2.5 w-4 <?php echo isActivePage('forum', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Modération Forum
            </a>
            <a href="settings.php" class="group flex items-center px-3 py-2 rounded-lg text-sm <?php echo isActivePage('settings', $current_page, $current_path) ? 'text-red-600 bg-red-50 ring-1 ring-red-100 font-medium' : 'text-gray-700 hover:text-red-600 hover:bg-red-50 transition-colors'; ?>">
                <i class="fas fa-cog mr-2.5 w-4 <?php echo isActivePage('settings', $current_page, $current_path) ? 'text-red-600' : 'text-gray-500 group-hover:text-red-600'; ?>"></i>
                Paramètres
            </a>
        </nav>

        <!-- Actions rapides mobile -->
        <div class="mt-6 space-y-2">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Actions</div>
            <a href="users.php" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-user-plus mr-2 w-3"></i>
                Ajouter un utilisateur
            </a>
            <a href="settings.php" class="flex items-center px-3 py-2 text-xs text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="fas fa-cog mr-2 w-3"></i>
                Configuration système
            </a>
        </div>
        
        <!-- Lien de retour mobile -->
        <div class="mt-6">
            <a href="../index.php" class="flex items-center px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200 rounded-lg">
                <i class="fas fa-arrow-left mr-2 w-4"></i>
                <span>Retour au site</span>
            </a>
        </div>
    </div>
</div>

<script>
(function(){
  const openBtn = document.getElementById('openAdminMobileSidebar');
  const closeBtn = document.getElementById('closeAdminMobileSidebar');
  const sidebar = document.getElementById('adminMobileSidebar');
  const backdrop = document.getElementById('adminMobileBackdrop');
  function open(){ if(sidebar) sidebar.classList.remove('hidden'); if(backdrop) backdrop.classList.remove('hidden'); }
  function close(){ if(sidebar) sidebar.classList.add('hidden'); if(backdrop) backdrop.classList.add('hidden'); }
  if(openBtn) openBtn.addEventListener('click', open);
  if(closeBtn) closeBtn.addEventListener('click', close);
  if(backdrop) backdrop.addEventListener('click', close);
})();
</script>
