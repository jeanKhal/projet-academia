<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

// Récupérer le fichier PDF à afficher
$fileUrl = $_GET['file'] ?? '';
$title = $_GET['title'] ?? 'Document PDF';

if (empty($fileUrl)) {
    header('Location: bibliotheque.php');
    exit;
}

// Vérifier que le fichier existe
$filePath = __DIR__ . DIRECTORY_SEPARATOR . $fileUrl;
if (!file_exists($filePath)) {
    header('Location: bibliotheque.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - Lecteur PDF</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    
    <style>
        #pdf-container {
            height: calc(100vh - 80px);
            overflow-y: auto;
            overflow-x: hidden;
            background: #f5f5f5;
            padding: 20px;
        }
        
        #pdf-content {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .pdf-page {
            margin: 0 auto 20px auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
            display: block;
            max-width: 100%;
            height: auto;
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .toolbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .btn-toolbar {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-toolbar:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        .btn-toolbar:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Scrollbar personnalisée */
        #pdf-container::-webkit-scrollbar {
            width: 8px;
        }
        
        #pdf-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        #pdf-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        #pdf-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Animation de chargement des pages */
        .pdf-page {
            opacity: 0;
            animation: fadeInUp 0.5s ease-out forwards;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Placeholders de chargement */
        .pdf-page.placeholder {
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
        }
        
        .loading-placeholder {
            text-align: center;
            color: #6c757d;
        }
        
        .spinner-small {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px auto;
        }
        
        .error-placeholder {
            text-align: center;
            color: #dc3545;
            padding: 20px;
        }
        
        .error-placeholder i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        /* Optimisations de performance */
        .pdf-page canvas {
            will-change: transform;
            backface-visibility: hidden;
        }
        
        /* Lazy loading pour les pages non visibles */
        .pdf-page:not(.rendered) {
            opacity: 0.3;
        }
        
        .pdf-page.rendered {
            opacity: 1;
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header avec toolbar -->
    <div class="toolbar shadow-lg">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center space-x-4">
                <button onclick="goBack()" class="btn-toolbar px-4 py-2 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </button>
                <h1 class="text-white text-lg font-semibold truncate max-w-md">
                    <?php echo htmlspecialchars($title); ?>
                </h1>
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Navigation pages -->
                <button id="prevPage" onclick="previousPage()" class="btn-toolbar px-3 py-2 rounded-lg" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <span id="pageInfo" class="text-white text-sm px-3 py-2 bg-black bg-opacity-20 rounded-lg">
                    Page <span id="currentPage">1</span> sur <span id="totalPages">1</span>
                </span>
                
                <button id="nextPage" onclick="nextPage()" class="btn-toolbar px-3 py-2 rounded-lg" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
                
                <!-- Zoom -->
                <div class="flex items-center space-x-2">
                    <button onclick="zoomOut()" class="btn-toolbar px-3 py-2 rounded-lg">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <span id="zoomLevel" class="text-white text-sm px-2">100%</span>
                    <button onclick="zoomIn()" class="btn-toolbar px-3 py-2 rounded-lg">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </div>
                
                <!-- Actions -->
                <button onclick="fitToWidth()" class="btn-toolbar px-3 py-2 rounded-lg">
                    <i class="fas fa-expand-arrows-alt mr-1"></i>Ajuster
                </button>
                
                <button onclick="toggleFullscreen()" class="btn-toolbar px-3 py-2 rounded-lg">
                    <i class="fas fa-expand mr-1"></i>Plein écran
                </button>
            </div>
        </div>
    </div>
    
    <!-- Barre de progression -->
    <div id="progress-bar" class="hidden bg-blue-600 h-1 transition-all duration-300">
        <div id="progress-fill" class="bg-blue-400 h-full transition-all duration-300" style="width: 0%"></div>
    </div>
    
    <!-- Conteneur PDF -->
    <div id="pdf-container" class="bg-gray-200 p-4">
        <div id="loading" class="loading">
            <div class="text-center">
                <div class="spinner"></div>
                <p class="mt-4 text-gray-600">Chargement du document...</p>
                <div id="loading-progress" class="mt-2 text-sm text-gray-500">Initialisation...</div>
            </div>
        </div>
        <div id="pdf-content" class="hidden"></div>
    </div>
    
    <!-- Messages d'erreur -->
    <div id="error-message" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md mx-4">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Erreur de chargement</h3>
                <p class="text-gray-600 mb-4">Impossible de charger le document PDF.</p>
                <button onclick="goBack()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Retour à la bibliothèque
                </button>
            </div>
        </div>
    </div>

    <script>
        // Configuration PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        let pdfDoc = null;
        let currentPage = 1;
        let totalPages = 0;
        let currentScale = 1.0;
        let isFullscreen = false;
        
        // URL du PDF
        const pdfUrl = '<?php echo htmlspecialchars($fileUrl); ?>';
        
        // Charger le PDF
        async function loadPDF() {
            try {
                // Afficher la barre de progression
                document.getElementById('progress-bar').classList.remove('hidden');
                updateProgress(10, 'Téléchargement du document...');
                
                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                pdfDoc = await loadingTask.promise;
                
                totalPages = pdfDoc.numPages;
                document.getElementById('totalPages').textContent = totalPages;
                document.getElementById('nextPage').disabled = totalPages <= 1;
                
                updateProgress(30, `Document chargé (${totalPages} pages)`);
                
                // Masquer le loading initial
                document.getElementById('loading').classList.add('hidden');
                document.getElementById('pdf-content').classList.remove('hidden');
                
                // Charger toutes les pages avec progression
                await renderAllPagesWithProgress();
                
                // Détecter la page visible au scroll
                setupScrollDetection();
                
                // Masquer la barre de progression
                setTimeout(() => {
                    document.getElementById('progress-bar').classList.add('hidden');
                }, 1000);
                
            } catch (error) {
                console.error('Erreur lors du chargement du PDF:', error);
                showError();
            }
        }
        
        // Mettre à jour la barre de progression
        function updateProgress(percentage, message) {
            document.getElementById('progress-fill').style.width = percentage + '%';
            const progressText = document.getElementById('loading-progress');
            if (progressText) {
                progressText.textContent = message;
            }
        }
        
        // Rendre toutes les pages avec progression
        async function renderAllPagesWithProgress() {
            const pdfContent = document.getElementById('pdf-content');
            pdfContent.innerHTML = '';
            
            // Créer des placeholders pour toutes les pages
            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                const placeholder = document.createElement('div');
                placeholder.className = 'pdf-page placeholder';
                placeholder.id = `page-${pageNum}`;
                placeholder.dataset.pageNum = pageNum;
                placeholder.innerHTML = `
                    <div class="loading-placeholder">
                        <div class="spinner-small"></div>
                        <p>Chargement page ${pageNum}...</p>
                    </div>
                `;
                pdfContent.appendChild(placeholder);
            }
            
            // Charger les pages par batch avec progression
            const batchSize = 2; // Réduire à 2 pages par batch pour plus de fluidité
            const batches = [];
            
            for (let i = 0; i < totalPages; i += batchSize) {
                const batch = [];
                for (let j = i; j < Math.min(i + batchSize, totalPages); j++) {
                    batch.push(j + 1);
                }
                batches.push(batch);
            }
            
            let completedPages = 0;
            
            // Charger les batches séquentiellement
            for (let batchIndex = 0; batchIndex < batches.length; batchIndex++) {
                const batch = batches[batchIndex];
                
                await Promise.all(batch.map(pageNum => renderPageOptimized(pageNum)));
                
                completedPages += batch.length;
                const progress = 30 + (completedPages / totalPages) * 60; // 30% à 90%
                updateProgress(progress, `Rendu des pages... ${completedPages}/${totalPages}`);
                
                // Pause entre les batches pour éviter de bloquer l'UI
                if (batchIndex < batches.length - 1) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                }
            }
            
            updateProgress(100, 'Chargement terminé !');
            
            // Mettre à jour l'interface
            updatePageInfo(1);
            
            // Re-configurer la détection de scroll
            setTimeout(() => {
                setupScrollDetection();
            }, 100);
        }
        
        // Rendre toutes les pages avec chargement progressif (version originale)
        async function renderAllPages() {
            const pdfContent = document.getElementById('pdf-content');
            pdfContent.innerHTML = '';
            
            // Créer des placeholders pour toutes les pages
            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                const placeholder = document.createElement('div');
                placeholder.className = 'pdf-page placeholder';
                placeholder.id = `page-${pageNum}`;
                placeholder.dataset.pageNum = pageNum;
                placeholder.innerHTML = `
                    <div class="loading-placeholder">
                        <div class="spinner-small"></div>
                        <p>Chargement page ${pageNum}...</p>
                    </div>
                `;
                pdfContent.appendChild(placeholder);
            }
            
            // Charger les pages par batch pour optimiser les performances
            const batchSize = 3; // Charger 3 pages à la fois
            const batches = [];
            
            for (let i = 0; i < totalPages; i += batchSize) {
                const batch = [];
                for (let j = i; j < Math.min(i + batchSize, totalPages); j++) {
                    batch.push(j + 1);
                }
                batches.push(batch);
            }
            
            // Charger les batches séquentiellement
            for (const batch of batches) {
                await Promise.all(batch.map(pageNum => renderPageOptimized(pageNum)));
                
                // Petite pause entre les batches pour éviter de bloquer l'UI
                await new Promise(resolve => setTimeout(resolve, 50));
            }
            
            // Mettre à jour l'interface
            updatePageInfo(1);
            
            // Re-configurer la détection de scroll
            setTimeout(() => {
                setupScrollDetection();
            }, 100);
        }
        
        // Rendre une page optimisée
        async function renderPageOptimized(pageNum) {
            try {
                const page = await pdfDoc.getPage(pageNum);
                const viewport = page.getViewport({ scale: currentScale });
                
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.className = 'pdf-page';
                canvas.id = `page-${pageNum}`;
                canvas.dataset.pageNum = pageNum;
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                await page.render(renderContext).promise;
                
                // Remplacer le placeholder par le canvas
                const placeholder = document.getElementById(`page-${pageNum}`);
                if (placeholder) {
                    placeholder.replaceWith(canvas);
                }
                
            } catch (error) {
                console.error(`Erreur lors du rendu de la page ${pageNum}:`, error);
                // En cas d'erreur, afficher un message d'erreur
                const placeholder = document.getElementById(`page-${pageNum}`);
                if (placeholder) {
                    placeholder.innerHTML = `
                        <div class="error-placeholder">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Erreur de chargement page ${pageNum}</p>
                        </div>
                    `;
                }
            }
        }
        
        // Rendre une page spécifique (pour les boutons de navigation)
        async function renderPage(pageNum) {
            try {
                const page = await pdfDoc.getPage(pageNum);
                const viewport = page.getViewport({ scale: currentScale });
                
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                canvas.className = 'pdf-page';
                canvas.id = `page-${pageNum}`;
                canvas.dataset.pageNum = pageNum;
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                await page.render(renderContext).promise;
                
                // Remplacer le contenu
                const pdfContent = document.getElementById('pdf-content');
                pdfContent.innerHTML = '';
                pdfContent.appendChild(canvas);
                
                // Mettre à jour l'interface
                updatePageInfo(pageNum);
                
            } catch (error) {
                console.error('Erreur lors du rendu de la page:', error);
            }
        }
        
        // Mettre à jour les informations de page
        function updatePageInfo(pageNum) {
            currentPage = pageNum;
            document.getElementById('currentPage').textContent = pageNum;
            document.getElementById('prevPage').disabled = pageNum <= 1;
            document.getElementById('nextPage').disabled = pageNum >= totalPages;
            document.getElementById('zoomLevel').textContent = Math.round(currentScale * 100) + '%';
        }
        
        // Détecter la page visible au scroll
        function setupScrollDetection() {
            const container = document.getElementById('pdf-container');
            const pages = document.querySelectorAll('.pdf-page');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const pageNum = parseInt(entry.target.dataset.pageNum);
                        updatePageInfo(pageNum);
                    }
                });
            }, {
                threshold: 0.5,
                rootMargin: '-20% 0px -20% 0px'
            });
            
            pages.forEach(page => {
                observer.observe(page);
            });
        }
        
        // Navigation
        function previousPage() {
            if (currentPage > 1) {
                scrollToPage(currentPage - 1);
            }
        }
        
        function nextPage() {
            if (currentPage < totalPages) {
                scrollToPage(currentPage + 1);
            }
        }
        
        // Scroller vers une page spécifique
        function scrollToPage(pageNum) {
            const pageElement = document.getElementById(`page-${pageNum}`);
            if (pageElement) {
                pageElement.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
        
        // Zoom optimisé
        function zoomIn() {
            currentScale = Math.min(currentScale * 1.2, 3.0);
            renderAllPagesOptimized();
        }
        
        function zoomOut() {
            currentScale = Math.max(currentScale / 1.2, 0.5);
            renderAllPagesOptimized();
        }
        
        function fitToWidth() {
            currentScale = 1.0;
            renderAllPagesOptimized();
        }
        
        // Rendu optimisé pour le zoom
        async function renderAllPagesOptimized() {
            const pdfContent = document.getElementById('pdf-content');
            const existingPages = pdfContent.querySelectorAll('.pdf-page canvas');
            
            // Afficher un indicateur de chargement
            updateProgress(0, 'Mise à jour du zoom...');
            
            let completedPages = 0;
            
            for (const canvas of existingPages) {
                const pageNum = parseInt(canvas.dataset.pageNum);
                
                try {
                    const page = await pdfDoc.getPage(pageNum);
                    const viewport = page.getViewport({ scale: currentScale });
                    
                    // Redimensionner le canvas
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    
                    const context = canvas.getContext('2d');
                    const renderContext = {
                        canvasContext: context,
                        viewport: viewport
                    };
                    
                    await page.render(renderContext).promise;
                    
                    completedPages++;
                    const progress = (completedPages / existingPages.length) * 100;
                    updateProgress(progress, `Mise à jour page ${pageNum}...`);
                    
                } catch (error) {
                    console.error(`Erreur lors du zoom de la page ${pageNum}:`, error);
                }
            }
            
            updateProgress(100, 'Zoom mis à jour !');
            document.getElementById('zoomLevel').textContent = Math.round(currentScale * 100) + '%';
            
            // Masquer la barre de progression après un délai
            setTimeout(() => {
                document.getElementById('progress-bar').classList.add('hidden');
            }, 500);
        }
        
        // Plein écran
        function toggleFullscreen() {
            if (!isFullscreen) {
                document.documentElement.requestFullscreen();
                isFullscreen = true;
            } else {
                document.exitFullscreen();
                isFullscreen = false;
            }
        }
        
        // Retour
        function goBack() {
            window.history.back();
        }
        
        // Afficher erreur
        function showError() {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('error-message').classList.remove('hidden');
        }
        
        // Raccourcis clavier
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    previousPage();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    nextPage();
                    break;
                case '+':
                case '=':
                    e.preventDefault();
                    zoomIn();
                    break;
                case '-':
                    e.preventDefault();
                    zoomOut();
                    break;
                case '0':
                    e.preventDefault();
                    fitToWidth();
                    break;
                case 'F11':
                    e.preventDefault();
                    toggleFullscreen();
                    break;
                case 'Escape':
                    if (isFullscreen) {
                        document.exitFullscreen();
                        isFullscreen = false;
                    }
                    break;
            }
        });
        
        // Démarrer le chargement
        loadPDF();
    </script>
</body>
</html>
