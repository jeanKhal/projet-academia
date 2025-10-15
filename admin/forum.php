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
            case 'approve_post':
                $post_id = (int)$_POST['post_id'];
                $stmt = $pdo->prepare("UPDATE forum_posts SET is_active = 1, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$post_id]);
                setFlashMessage('success', 'Post approuvé');
                break;
                
            case 'reject_post':
                $post_id = (int)$_POST['post_id'];
                $reason = trim($_POST['reason']);
                $stmt = $pdo->prepare("UPDATE forum_posts SET is_active = 0, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$post_id]);
                setFlashMessage('success', 'Post rejeté');
                break;
                
            case 'delete_post':
                $post_id = (int)$_POST['post_id'];
                $stmt = $pdo->prepare("DELETE FROM forum_posts WHERE id = ?");
                $stmt->execute([$post_id]);
                setFlashMessage('success', 'Post supprimé');
                break;
                
            case 'ban_user':
                $user_id = (int)$_POST['user_id'];
                $reason = trim($_POST['reason']);
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
                setFlashMessage('success', 'Utilisateur banni');
                break;
                
            case 'unban_user':
                $user_id = (int)$_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user_id]);
                setFlashMessage('success', 'Utilisateur débanni');
                break;
        }
        
        header('Location: forum.php');
        exit();
    }
}

// Récupération des posts en attente de modération
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$where_conditions = [];
$params = [];

// Ajouter une condition selon le filtre de statut
if ($status_filter === 'active') {
    $where_conditions[] = "fp.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = "fp.is_active = 0";
} elseif ($status_filter === 'pinned') {
    $where_conditions[] = "fp.is_pinned = 1";
}

if ($search) {
    $where_conditions[] = "(fp.title LIKE ? OR fp.content LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Total des posts
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) FROM forum_posts fp 
    LEFT JOIN users u ON fp.user_id = u.id 
    $where_clause
");
$count_stmt->execute($params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// Récupération des posts
$posts_stmt = $pdo->prepare("
    SELECT fp.*, u.full_name, u.email,
           (SELECT COUNT(*) FROM forum_replies WHERE post_id = fp.id) as replies_count
    FROM forum_posts fp 
    LEFT JOIN users u ON fp.user_id = u.id 
    $where_clause 
    ORDER BY fp.created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $limit;
$params[] = $offset;
$posts_stmt->execute($params);
$posts = $posts_stmt->fetchAll();

// Statistiques
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_posts,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_posts,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_posts,
        SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END) as pinned_posts
    FROM forum_posts
");
$stats_stmt->execute();
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modération Forum - Administration</title>
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
                        <h1 class="text-3xl font-bold text-gray-900">Modération du Forum</h1>
                        <p class="text-gray-600">Gérez les posts et les utilisateurs du forum</p>
                    </div>
                </div>
            </div>

            <?php 
            $flash = getFlashMessage();
            if ($flash): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $flash['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-comments text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Posts</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_posts']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Inactifs</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['inactive_posts']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Actifs</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active_posts']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-times text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Épinglés</p>
                            <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['pinned_posts']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Rechercher par titre, contenu ou auteur..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tous les posts</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Actifs</option>
                            <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactifs</option>
                            <option value="pinned" <?php echo $status_filter === 'pinned' ? 'selected' : ''; ?>>Épinglés</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Rechercher
                    </button>
                    <a href="forum.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-times mr-2"></i>Réinitialiser
                    </a>
                </form>
            </div>

            <!-- Tableau des posts -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-comments mr-2 text-orange-600"></i>
                        Posts du Forum (<?php echo $total_posts; ?>)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Post</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auteur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réponses</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($posts as $post): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-600 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-comment text-white"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($post['title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($post['content'], 0, 60)) . '...'; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($post['full_name']); ?></div>
                                        <div class="text-gray-500"><?php echo htmlspecialchars($post['email']); ?></div>
                                        <?php if (!$post['is_active']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-ban mr-1"></i>Banni
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_colors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'inactive' => 'bg-red-100 text-red-800',
                                        'pinned' => 'bg-blue-100 text-blue-800'
                                    ];
                                    $status_labels = [
                                        'active' => 'Actif',
                                        'inactive' => 'Inactif',
                                        'pinned' => 'Épinglé'
                                    ];
                                    ?>
                                    <?php
                                    $current_status = 'active';
                                    if (!$post['is_active']) {
                                        $current_status = 'inactive';
                                    } elseif ($post['is_pinned']) {
                                        $current_status = 'pinned';
                                    }
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_colors[$current_status]; ?>">
                                        <?php echo $status_labels[$current_status]; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $post['replies_count']; ?> réponses
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <?php if ($post['is_active'] == 0): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Approuver ce post ?')">
                                                <input type="hidden" name="action" value="approve_post">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <button type="submit" class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <button onclick="openRejectModal(<?php echo $post['id']; ?>)" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!$post['is_active']): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Débannir cet utilisateur ?')">
                                                <input type="hidden" name="action" value="unban_user">
                                                <input type="hidden" name="user_id" value="<?php echo $post['user_id']; ?>">
                                                <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button onclick="openBanModal(<?php echo $post['user_id']; ?>)" class="text-orange-600 hover:text-orange-900">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                        <?php endif; ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('Supprimer ce post ?')">
                                            <input type="hidden" name="action" value="delete_post">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
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
                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $i === $page ? 'bg-orange-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Rejet -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Rejeter le Post</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="reject_post">
                    <input type="hidden" name="post_id" id="reject_post_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Raison du rejet *</label>
                        <textarea name="reason" required rows="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            Rejeter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Bannissement -->
    <div id="banModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Bannir l'Utilisateur</h3>
                </div>
                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="ban_user">
                    <input type="hidden" name="user_id" id="ban_user_id">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Raison du bannissement *</label>
                        <textarea name="reason" required rows="3" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeBanModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                            Bannir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRejectModal(postId) {
            document.getElementById('reject_post_id').value = postId;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        function openBanModal(userId) {
            document.getElementById('ban_user_id').value = userId;
            document.getElementById('banModal').classList.remove('hidden');
        }

        function closeBanModal() {
            document.getElementById('banModal').classList.add('hidden');
        }

        window.onclick = function(event) {
            const rejectModal = document.getElementById('rejectModal');
            const banModal = document.getElementById('banModal');
            if (event.target === rejectModal) {
                closeRejectModal();
            }
            if (event.target === banModal) {
                closeBanModal();
            }
        }
    </script>
</body>
</html>
