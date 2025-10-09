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
<div class="hidden md:block w-48 bg-white shadow-lg overflow-y-scroll relative h-[calc(100vh-4rem-1.5rem)] border-r border-gray-200 sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
    <div class="p-3 md:p-4">
        <!-- En-tête du sidebar -->
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-gray-800 mb-1">Administration</h2>
            <div class="w-6 h-0.5 bg-red-500 rounded"></div>
        </div>
        
        <nav class="space-y-1">
            <a href="dashboard.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg text-xs transition-all duration-200 <?php echo isActivePage('dashboard', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-tachometer-alt w-4 <?php echo isActivePage('dashboard', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Dashboard</span>
                <?php if (isActivePage('dashboard', $current_page, $current_path)): ?>
                    <div class="ml-auto w-2 h-2 bg-red-500 rounded-full"></div>
                <?php endif; ?>
            </a>
            
            <a href="users.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg text-xs transition-all duration-200 <?php echo isActivePage('users', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-users w-4 <?php echo isActivePage('users', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Utilisateurs</span>
                <?php if (isActivePage('users', $current_page, $current_path)): ?>
                    <div class="ml-auto w-2 h-2 bg-red-500 rounded-full"></div>
                <?php endif; ?>
            </a>
            
            <a href="courses.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg text-xs transition-all duration-200 <?php echo isActivePage('courses', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-graduation-cap w-4 <?php echo isActivePage('courses', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Cours</span>
                <?php if (isActivePage('courses', $current_page, $current_path)): ?>
                    <div class="ml-auto w-2 h-2 bg-red-500 rounded-full"></div>
                <?php endif; ?>
            </a>
            
            <a href="resources.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg text-xs transition-all duration-200 <?php echo isActivePage('resources', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-book w-4 <?php echo isActivePage('resources', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Ressources</span>
                <?php if (isActivePage('resources', $current_page, $current_path)): ?>
                    <div class="ml-auto w-2 h-2 bg-red-500 rounded-full"></div>
                <?php endif; ?>
            </a>
            
            <a href="certifications.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg text-xs transition-all duration-200 <?php echo isActivePage('certifications', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-certificate w-4 <?php echo isActivePage('certifications', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Certifications</span>
                <?php if (isActivePage('certifications', $current_page, $current_path)): ?>
                    <div class="ml-auto w-2 h-2 bg-red-500 rounded-full"></div>
                <?php endif; ?>
            </a>
            
            <a href="forum.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg text-xs transition-all duration-200 <?php echo isActivePage('forum', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-comments w-4 <?php echo isActivePage('forum', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Modération Forum</span>
                <?php if (isActivePage('forum', $current_page, $current_path)): ?>
                    <div class="ml-auto w-2 h-2 bg-red-500 rounded-full"></div>
                <?php endif; ?>
            </a>
            
            <a href="settings.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg text-xs transition-all duration-200 <?php echo isActivePage('settings', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-cog w-4 <?php echo isActivePage('settings', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Paramètres</span>
                <?php if (isActivePage('settings', $current_page, $current_path)): ?>
                    <div class="ml-auto w-2 h-2 bg-red-500 rounded-full"></div>
                <?php endif; ?>
            </a>
        </nav>
        
        <!-- Séparateur -->
        <div class="my-6 border-t border-gray-200"></div>
        
        <!-- Lien de retour -->
        <div class="mt-4">
            <a href="../index.php" class="flex items-center space-x-3 p-3 rounded-lg text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                <i class="fas fa-arrow-left w-5"></i>
                <span>Retour au site</span>
            </a>
        </div>
    </div>
</div>

<!-- Mobile sidebar drawer -->
<div id="adminMobileBackdrop" class="hidden fixed inset-0 bg-black bg-opacity-40 z-50"></div>
<div id="adminMobileSidebar" class="hidden md:hidden fixed left-0 top-16 h-[calc(100vh-4rem-1.5rem)] w-64 bg-white shadow-lg z-50 overflow-y-scroll overscroll-contain border-r border-gray-200 sidebar-scroll" style="scrollbar-width: thin; scrollbar-color: #cbd5e0 #f7fafc;">
    <div class="p-3 pb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-800">Menu</h2>
            <button id="closeAdminMobileSidebar" class="p-2 rounded hover:bg-gray-100">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <nav class="space-y-1 text-xs">
            <a href="dashboard.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg transition-all duration-200 <?php echo isActivePage('dashboard', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-tachometer-alt w-4 <?php echo isActivePage('dashboard', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg transition-all duration-200 <?php echo isActivePage('users', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-users w-4 <?php echo isActivePage('users', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Utilisateurs</span>
            </a>
            <a href="courses.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg transition-all duration-200 <?php echo isActivePage('courses', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-graduation-cap w-4 <?php echo isActivePage('courses', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Cours</span>
            </a>
            <a href="resources.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg transition-all duration-200 <?php echo isActivePage('resources', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-book w-4 <?php echo isActivePage('resources', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Ressources</span>
            </a>
            <a href="certifications.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg transition-all duration-200 <?php echo isActivePage('certifications', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-certificate w-4 <?php echo isActivePage('certifications', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Gestion Certifications</span>
            </a>
            <a href="forum.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg transition-all duration-200 <?php echo isActivePage('forum', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-comments w-4 <?php echo isActivePage('forum', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Modération Forum</span>
            </a>
            <a href="settings.php" class="group flex items-center space-x-2.5 p-2.5 rounded-lg transition-all duration-200 <?php echo isActivePage('settings', $current_page, $current_path) ? $active_classes : $inactive_classes; ?>">
                <i class="fas fa-cog w-4 <?php echo isActivePage('settings', $current_page, $current_path) ? $icon_active_classes : $icon_inactive_classes; ?>"></i>
                <span>Paramètres</span>
            </a>
            <div class="my-4 border-t border-gray-200"></div>
            <a href="../index.php" class="flex items-center space-x-2.5 p-2.5 rounded-lg text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200 text-xs">
                <i class="fas fa-arrow-left w-4"></i>
                <span>Retour au site</span>
            </a>
        </nav>
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
