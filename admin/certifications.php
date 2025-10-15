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
    if ($user['role'] === 'student') {
        header('Location: ../dashboard.php');
        exit();
    }
    header('Location: ../login-admin.php');
    exit();
}

$pdo = getDB();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_certification':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $category = $_POST['category'];
                $level = $_POST['level'];
                $estimated_hours = (int)$_POST['estimated_hours'];
                
                if (empty($title) || empty($description)) {
                    setFlashMessage('error', 'Tous les champs obligatoires doivent être remplis');
                } else {
                    $stmt = $pdo->prepare("INSERT INTO certification_paths (title, description, category, level, estimated_hours, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                    $stmt->execute([$title, $description, $category, $level, $estimated_hours]);
                    setFlashMessage('success', 'Certification créée avec succès');
                }
                break;
                
            case 'update_certification':
                $cert_id = (int)$_POST['certification_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $category = $_POST['category'];
                $level = $_POST['level'];
                $estimated_hours = (int)$_POST['estimated_hours'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($title) || empty($description)) {
                    setFlashMessage('error', 'Tous les champs obligatoires doivent être remplis');
                } else {
                    $stmt = $pdo->prepare("UPDATE certification_paths SET title = ?, description = ?, category = ?, level = ?, estimated_hours = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $category, $level, $estimated_hours, $is_active, $cert_id]);
                    setFlashMessage('success', 'Certification mise à jour avec succès');
                }
                break;
                
            case 'toggle_status':
                $cert_id = (int)$_POST['certification_id'];
                $stmt = $pdo->prepare("UPDATE certification_paths SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$cert_id]);
                setFlashMessage('success', 'Statut de la certification mis à jour');
                break;
                
            case 'delete_certification':
                $cert_id = (int)$_POST['certification_id'];
                $stmt = $pdo->prepare("DELETE FROM certification_paths WHERE id = ?");
                $stmt->execute([$cert_id]);
                setFlashMessage('success', 'Certification supprimée');
                break;
        }
        
        header('Location: certifications.php');
        exit();
    }
}

// Récupération des certifications
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

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

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM certification_paths $where_clause");
$count_stmt->execute($params);
$total_certifications = $count_stmt->fetchColumn();
$total_pages = ceil($total_certifications / $limit);

$certifications_stmt = $pdo->prepare("SELECT * FROM certification_paths $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$certifications_stmt->execute($params);
$certifications = $certifications_stmt->fetchAll();

$categories = [
    'embedded-systems' => 'Systèmes Embarqués',
    'artificial-intelligence' => 'Intelligence Artificielle',
    'machine-learning' => 'Machine Learning',
    'deep-learning' => 'Deep Learning',
    'software-engineering' => 'Génie Logiciel'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Certifications - Académie IA</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 overflow-x-hidden">
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
                        <h1 class="text-3xl font-bold text-gray-900">Gestion des Certifications</h1>
                        <p class="text-gray-600">Gérez les parcours de certification</p>
                    </div>
                    <button onclick="openCreateModal()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i>Nouvelle Certification
                    </button>
                </div>
            </div>

            <?php 
            $flash = getFlashMessage();
            if ($flash): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Rechercher..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                    </div>
                    <div>
                        <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $key => $name): ?>
                                <option value="<?php echo $key; ?>" <?php echo $category_filter === $key ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Rechercher
                    </button>
                    <a href="certifications.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Réinitialiser
                    </a>
                </form>
            </div>

            <!-- Tableau -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-certificate mr-2 text-yellow-600"></i>
                        Liste des Certifications (<?php echo $total_certifications; ?>)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certification</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($certifications as $cert): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-certificate text-white"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($cert['title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($cert['description'], 0, 60)) . '...'; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $categories[$cert['category']] ?? ucfirst($cert['category']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getLevelColor($cert['level']); ?>">
                                        <?php echo ucfirst($cert['level']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $cert['estimated_hours']; ?>h
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $cert['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $cert['is_active'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($cert)); ?>)" 
                                                class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Changer le statut ?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="certification_id" value="<?php echo $cert['id']; ?>">
                                            <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer cette certification ?')">
                                            <input type="hidden" name="action" value="delete_certification">
                                            <input type="hidden" name="certification_id" value="<?php echo $cert['id']; ?>">
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
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $i === $page ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Création -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Nouvelle Certification</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="create_certification">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Titre *</label>
                            <input type="text" name="title" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description *</label>
                            <textarea name="description" required rows="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Catégorie *</label>
                            <select name="category" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <option value="">Sélectionner</option>
                                <?php foreach ($categories as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Niveau *</label>
                            <select name="level" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <option value="">Sélectionner</option>
                                <option value="beginner">Débutant</option>
                                <option value="intermediate">Intermédiaire</option>
                                <option value="advanced">Avancé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Durée estimée (heures)</label>
                            <input type="number" name="estimated_hours" value="0" min="0" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Modifier Certification</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="update_certification">
                    <input type="hidden" name="certification_id" id="edit_certification_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Titre *</label>
                            <input type="text" name="title" id="edit_title" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Description *</label>
                            <textarea name="description" id="edit_description" required rows="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Catégorie *</label>
                            <select name="category" id="edit_category" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <option value="">Sélectionner</option>
                                <?php foreach ($categories as $key => $name): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Niveau *</label>
                            <select name="level" id="edit_level" required class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                                <option value="">Sélectionner</option>
                                <option value="beginner">Débutant</option>
                                <option value="intermediate">Intermédiaire</option>
                                <option value="advanced">Avancé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Durée estimée (heures)</label>
                            <input type="number" name="estimated_hours" id="edit_estimated_hours" min="0" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-900">Certification active</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
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

        function openEditModal(cert) {
            document.getElementById('edit_certification_id').value = cert.id;
            document.getElementById('edit_title').value = cert.title;
            document.getElementById('edit_description').value = cert.description;
            document.getElementById('edit_category').value = cert.category;
            document.getElementById('edit_level').value = cert.level;
            document.getElementById('edit_estimated_hours').value = cert.estimated_hours;
            document.getElementById('edit_is_active').checked = cert.is_active == 1;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

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
