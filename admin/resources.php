<?php
session_start();
require_once '../includes/functions.php';

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
            case 'create_resource':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $type = $_POST['type'];
                $url = trim($_POST['url']);
                $category = $_POST['category'];
                $level = $_POST['level'];
                
                // Validation
                if (empty($title) || empty($description) || empty($url)) {
                    setFlashMessage('error', 'Tous les champs obligatoires doivent être remplis');
                } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                    setFlashMessage('error', 'URL invalide');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO resources (title, description, type, file_url, category, level, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$title, $description, $type, $url, $category, $level]);
                    setFlashMessage('success', 'Ressource créée avec succès');
                }
                break;
                
            case 'update_resource':
                $resource_id = (int)$_POST['resource_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $type = $_POST['type'];
                $url = trim($_POST['url']);
                $category = $_POST['category'];
                $level = $_POST['level'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                // Validation
                if (empty($title) || empty($description) || empty($url)) {
                    setFlashMessage('error', 'Tous les champs obligatoires doivent être remplis');
                } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                    setFlashMessage('error', 'URL invalide');
                } else {
                    $stmt = $pdo->prepare("UPDATE resources SET title = ?, description = ?, type = ?, file_url = ?, category = ?, level = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $type, $url, $category, $level, $is_active, $resource_id]);
                    setFlashMessage('success', 'Ressource mise à jour avec succès');
                }
                break;
                
            case 'toggle_status':
                $resource_id = (int)$_POST['resource_id'];
                $stmt = $pdo->prepare("UPDATE resources SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$resource_id]);
                setFlashMessage('success', 'Statut de la ressource mis à jour');
                break;
                
            case 'delete_resource':
                $resource_id = (int)$_POST['resource_id'];
                $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
                $stmt->execute([$resource_id]);
                setFlashMessage('success', 'Ressource supprimée');
                break;
        }
        
        header('Location: resources.php');
        exit();
    }
}

// Récupération des ressources avec pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Construction de la requête
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
}

if ($type_filter) {
    $where_conditions[] = "type = ?";
    $params[] = $type_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Total des ressources
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM resources $where_clause");
$count_stmt->execute($params);
$total_resources = $count_stmt->fetchColumn();
$total_pages = ceil($total_resources / $limit);

// Récupération des ressources
$resources_stmt = $pdo->prepare("SELECT * FROM resources $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$resources_stmt->execute($params);
$resources = $resources_stmt->fetchAll();

// Catégories disponibles
$categories = [
    'embedded-systems' => 'Systèmes Embarqués',
    'artificial-intelligence' => 'Intelligence Artificielle',
    'machine-learning' => 'Machine Learning',
    'deep-learning' => 'Deep Learning',
    'software-engineering' => 'Génie Logiciel'
];

// Types de ressources
$types = [
    'video' => 'Vidéo',
    'document' => 'Document',
    'tutorial' => 'Tutoriel',
    'article' => 'Article',
    'code' => 'Code',
    'presentation' => 'Présentation',
    'dataset' => 'Dataset'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Ressources - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 overflow-x-hidden">
    <!-- Navigation Admin -->
    <?php include 'includes/header.php'; ?>

    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Contenu principal -->
        <div class="flex-1 p-4 md:p-8 mt-16 pb-16">
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Gestion des Ressources</h1>
                        <p class="text-gray-600">Gérez les ressources pédagogiques de la plateforme</p>
                    </div>
                    <button onclick="openCreateModal()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i>Nouvelle Ressource
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
                               placeholder="Rechercher par titre ou description..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $key => $name): ?>
                                <option value="<?php echo $key; ?>" <?php echo $category_filter === $key ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Tous les types</option>
                            <?php foreach ($types as $key => $name): ?>
                                <option value="<?php echo $key; ?>" <?php echo $type_filter === $key ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Rechercher
                    </button>
                    <a href="resources.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Réinitialiser
                    </a>
                </form>
            </div>

            <!-- Tableau des ressources -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-book mr-2 text-purple-600"></i>
                        Liste des Ressources (<?php echo $total_resources; ?>)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ressource</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($resources as $resource): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($resource['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($resource['description'], 0, 60)) . '...'; ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 <?php echo getResourceTypeBackground($resource['type']); ?> rounded-lg flex items-center justify-center mr-2">
                                            <i class="fas fa-<?php echo getResourceTypeIcon($resource['type']); ?> text-white text-xs"></i>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">
                                            <?php echo getResourceTypeLabel($resource['type']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $categories[$resource['category']] ?? ucfirst($resource['category']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getLevelColor($resource['level'] ?? 'intermediate'); ?>">
                                        <?php echo ucfirst($resource['level'] ?? 'intermediate'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if (!empty($resource['file_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($resource['file_url']); ?>" target="_blank" class="text-purple-600 hover:text-purple-900">
                                            <i class="fas fa-external-link-alt mr-1"></i>Voir
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400">Aucune URL</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $resource['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $resource['is_active'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($resource)); ?>)" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir changer le statut de cette ressource ?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette ressource ?')">
                                            <input type="hidden" name="action" value="delete_resource">
                                            <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&type=<?php echo urlencode($type_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $i === $page ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Création Ressource -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Nouvelle Ressource</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="create_resource">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Titre de la ressource *</label>
                            <input type="text" name="title" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description *</label>
                            <textarea name="description" required rows="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type *</label>
                            <select name="type" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Sélectionner un type</option>
                                <option value="document">📄 Document</option>
                                <option value="video">🎥 Vidéo</option>
                                <option value="code">💻 Code</option>
                                <option value="book">📚 Livre</option>
                                <option value="presentation">📊 Présentation</option>
                                <option value="tutorial">🎓 Tutoriel</option>
                                <option value="article">📰 Article</option>
                                <option value="dataset">🗄️ Dataset</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Catégorie *</label>
                            <select name="category" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Niveau</label>
                            <select name="level" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Sélectionner un niveau</option>
                                <option value="beginner">Débutant</option>
                                <option value="intermediate">Intermédiaire</option>
                                <option value="advanced">Avancé</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">URL *</label>
                            <input type="url" name="url" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition Ressource -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Modifier Ressource</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="update_resource">
                    <input type="hidden" name="resource_id" id="edit_resource_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Titre de la ressource *</label>
                            <input type="text" name="title" id="edit_title" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description *</label>
                            <textarea name="description" id="edit_description" required rows="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type *</label>
                            <select name="type" id="edit_type" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Sélectionner un type</option>
                                <?php foreach ($types as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Catégorie *</label>
                            <select name="category" id="edit_category" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Niveau</label>
                            <select name="level" id="edit_level" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Sélectionner un niveau</option>
                                <option value="beginner">Débutant</option>
                                <option value="intermediate">Intermédiaire</option>
                                <option value="advanced">Avancé</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">URL *</label>
                            <input type="url" name="url" id="edit_url" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-900">Ressource active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
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

        function openEditModal(resource) {
            document.getElementById('edit_resource_id').value = resource.id;
            document.getElementById('edit_title').value = resource.title;
            document.getElementById('edit_description').value = resource.description;
            document.getElementById('edit_type').value = resource.type;
            document.getElementById('edit_category').value = resource.category;
            document.getElementById('edit_level').value = resource.level;
            document.getElementById('edit_url').value = resource.file_url || '';
            document.getElementById('edit_is_active').checked = resource.is_active == 1;
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
