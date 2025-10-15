<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!isLoggedIn()) {
    header('Location: ../login-admin.php');
    exit();
}

$user = getUserById($_SESSION['user_id']);

// Vérification stricte du rôle admin
if ($user['role'] !== 'admin') {
    // Rediriger les étudiants vers leur dashboard
    if ($user['role'] === 'student') {
        header('Location: ../dashboard.php');
        exit();
    }
    // Pour tout autre rôle, rediriger vers la page de connexion
    header('Location: ../login-admin.php');
    exit();
}

$pdo = getDB();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_user':
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $password = $_POST['password'];
                
                // Validation
                if (empty($full_name) || empty($email) || empty($password)) {
                    setFlashMessage('error', 'Tous les champs sont obligatoires');
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    setFlashMessage('error', 'Email invalide');
                } else {
                    // Vérifier si l'email existe déjà
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $check_stmt->execute([$email]);
                    if ($check_stmt->fetch()) {
                        setFlashMessage('error', 'Cet email est déjà utilisé');
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
                        $stmt->execute([$full_name, $email, $hashed_password, $role]);
                        setFlashMessage('success', 'Utilisateur créé avec succès');
                    }
                }
                break;
                
            case 'update_user':
                $user_id = (int)$_POST['user_id'];
                $full_name = trim($_POST['full_name']);
                $email = trim($_POST['email']);
                $role = $_POST['role'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Validation
                if (empty($full_name) || empty($email)) {
                    setFlashMessage('error', 'Le nom et l\'email sont obligatoires');
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    setFlashMessage('error', 'Email invalide');
                } else {
                    // Vérifier si l'email existe déjà (sauf pour cet utilisateur)
                    $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $check_stmt->execute([$email, $user_id]);
                    if ($check_stmt->fetch()) {
                        setFlashMessage('error', 'Cet email est déjà utilisé');
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$full_name, $email, $role, $is_active, $user_id]);
                        setFlashMessage('success', 'Utilisateur mis à jour avec succès');
                    }
                }
                break;
                
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$user_id]);
                setFlashMessage('success', 'Statut de l\'utilisateur mis à jour');
                break;
                
            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                if ($user_id != $_SESSION['user_id']) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    setFlashMessage('success', 'Utilisateur supprimé');
                } else {
                    setFlashMessage('error', 'Vous ne pouvez pas supprimer votre propre compte');
                }
                break;
        }
        
        header('Location: users.php');
        exit();
    }
}

// Récupération des utilisateurs avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// Construction de la requête
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Total des utilisateurs
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where_clause");
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Récupération des utilisateurs
$users_stmt = $pdo->prepare("SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$users_stmt->execute($params);
$users = $users_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 overflow-x-hidden">
    <!-- Navigation Admin -->
    <?php include 'includes/header.php'; ?>

    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Séparateur visuel -->
        <div class="hidden md:block w-px bg-gradient-to-b from-gray-200 to-gray-300 mt-16 h-[calc(100vh-4rem-1.5rem)]" style="margin-left: 256px;"></div>

        <!-- Contenu principal -->
        <div class="flex-1 p-4 md:p-6 mt-16 pb-16 bg-white rounded-l-2xl shadow-sm border-l-2 border-gray-100 min-h-[calc(100vh-4rem-1.5rem)]" style="margin-left: -4px;">
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Gestion des Utilisateurs</h1>
                        <p class="text-sm md:text-base text-gray-600">Gérez les comptes utilisateurs de la plateforme</p>
                    </div>
                    <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i>Nouvel Utilisateur
                    </button>
                </div>
            </div>

            <!-- Messages flash -->
            <?php 
            $flash = getFlashMessage();
            if ($flash): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Filtres et recherche -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Rechercher par nom ou email..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <select name="role" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les rôles</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>Étudiant</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Rechercher
                    </button>
                    <a href="users.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Réinitialiser
                    </a>
                </form>
            </div>

            <!-- Tableau des utilisateurs -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-users mr-2 text-blue-600"></i>
                        Liste des Utilisateurs (<?php echo $total_users; ?>)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user_item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-medium text-sm">
                                                <?php echo strtoupper(substr($user_item['full_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user_item['full_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user_item['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $user_item['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo ucfirst($user_item['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $user_item['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $user_item['is_active'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($user_item['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user_item)); ?>)" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir changer le statut de cet utilisateur ?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user_item['id']; ?>">
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </form>
                                        <?php if ($user_item['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user_item['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="flex space-x-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Création Utilisateur -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Nouvel Utilisateur</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="create_user">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom complet</label>
                            <input type="text" name="full_name" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mot de passe</label>
                            <input type="password" name="password" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select name="role" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="student">Étudiant</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition Utilisateur -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Modifier Utilisateur</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom complet</label>
                            <input type="text" name="full_name" id="edit_full_name" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="edit_email" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rôle</label>
                            <select name="role" id="edit_role" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="student">Étudiant</option>
                                <option value="admin">Administrateur</option>
                            </select>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label class="ml-2 block text-sm text-gray-900">Compte actif</label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Fermer les modals en cliquant à l'extérieur
        window.onclick = function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            if (event.target === createModal) {
                closeCreateModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
