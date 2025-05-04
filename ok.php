<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les données pour l'affichage
try {
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $products = getProducts($currentPage, 5);
    $totalProducts = countProducts();
    $categories = getCategories();
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard AK Business</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            light: '#4f46e5',
                            dark: '#6366f1'
                        },
                        secondary: {
                            light: '#f59e0b',
                            dark: '#fbbf24'
                        },
                        success: {
                            light: '#10b981',
                            dark: '#34d399'
                        },
                        danger: {
                            light: '#ef4444',
                            dark: '#f87171'
                        },
                        info: {
                            light: '#3b82f6',
                            dark: '#60a5fa'
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'wave': 'wave 2s linear infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        wave: {
                            '0%': { transform: 'rotate(0deg)' },
                            '10%': { transform: 'rotate(14deg)' },
                            '20%': { transform: 'rotate(-8deg)' },
                            '30%': { transform: 'rotate(14deg)' },
                            '40%': { transform: 'rotate(-4deg)' },
                            '50%': { transform: 'rotate(10deg)' },
                            '60%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(0deg)' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .dark .glass-effect {
            background: rgba(0, 0, 0, 0.1);
        }
        
        .gradient-card {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
        }
        
        .dashboard-card {
            transition: all 0.3s ease;
        }
        
        .progress-ring__circle {
            transition: stroke-dashoffset 0.8s ease;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        
        .animate-bounce-slow {
            animation: bounce 3s infinite;
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .cart-item-enter {
            opacity: 0;
            transform: translateY(-20px);
        }
        .cart-item-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: all 300ms ease-out;
        }
        .cart-item-exit {
            opacity: 1;
        }
        .cart-item-exit-active {
            opacity: 0;
            transform: translateY(-20px);
            transition: all 300ms ease-out;
        }
        
        .sidebar {
            transition: all 0.3s ease;
        }
        
        .mobile-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-sidebar.open {
            transform: translateX(0);
        }
        
        .overlay {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .overlay.open {
            display: block;
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <!-- Hamburger menu for mobile -->
                <button id="mobile-menu-button" class="md:hidden p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="relative">
                    <i class="fas fa-shopping-bag text-2xl text-primary-light dark:text-primary-dark animate-waving-hand"></i>
                    <span class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                </div>
                <h1 class="text-xl font-bold">Dashboard</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- View Website Button -->
                <a href="index.php" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300" title="Voir le site web">
                    <i class="fas fa-eye"></i>
                </a>
                
                <!-- User Profile -->
                <div class="flex items-center space-x-2">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-medium"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Administrateur</p>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-primary-light dark:bg-primary-dark flex items-center justify-center text-white font-bold relative">
                        <span><?= substr($_SESSION['user_name'] ?? 'A', 0, 1) ?></span>
                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-green-500 border-2 border-white dark:border-gray-800"></span>
                    </div>
                </div>
                
                <!-- Dark/Light Mode Toggle -->
                <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                
                <!-- Logout Button -->
                <a href="logout.php" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </header>

    <!-- Mobile Sidebar -->
    <div class="mobile-sidebar fixed inset-y-0 left-0 w-64 bg-white dark:bg-gray-800 shadow-lg z-50 overflow-y-auto md:hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-bold flex items-center">
                <i class="fas fa-chart-line text-xl text-primary-light dark:text-primary-dark mr-2"></i>
                Menu
            </h2>
            <button id="close-mobile-menu" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="flex-1 overflow-y-auto p-2">
            <ul class="space-y-1">
                <li>
                    <a href="#" class="flex items-center p-2 rounded-lg bg-primary-light dark:bg-primary-dark text-white">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        <span>Tableau de bord</span>
                    </a>
                </li>
                <li>
                    <a href="#products-section" class="flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-box-open mr-3"></i>
                        <span>Produits</span>
                    </a>
                </li>
                <li>
                    <a href="#categories-section" class="flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-tags mr-3"></i>
                        <span>Catégories</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- Overlay for mobile menu -->
    <div id="mobile-menu-overlay" class="overlay fixed inset-0 bg-black bg-opacity-50 z-40"></div>

    <!-- Main Content -->
    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar -->
        <aside class="hidden md:flex md:flex-shrink-0 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold flex items-center">
                    <i class="fas fa-chart-line text-xl text-primary-light dark:text-primary-dark mr-2"></i>
                    Menu
                </h2>
            </div>
            
            <nav class="flex-1 overflow-y-auto">
                <ul class="p-2 space-y-1">
                    <li>
                        <a href="#" class="flex items-center p-2 rounded-lg bg-primary-light dark:bg-primary-dark text-white">
                            <i class="fas fa-tachometer-alt mr-3"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="#products-section" class="flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-box-open mr-3"></i>
                            <span>Produits</span>
                        </a>
                    </li>
                    <li>
                        <a href="#categories-section" class="flex items-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                            <i class="fas fa-tags mr-3"></i>
                            <span>Catégories</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <!-- Welcome Banner -->
            <div class="gradient-card rounded-xl p-6 mb-6 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <h2 class="text-2xl font-bold mb-2">Bonjour, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> 👋</h2>
                    <p class="mb-4">Voici ce qui se passe avec votre boutique aujourd'hui</p>
                    <div class="flex flex-wrap gap-4">
                        <div class="bg-white bg-opacity-20 px-4 py-2 rounded-md font-medium flex items-center">
                            <i class="fas fa-boxes mr-2"></i>
                            <span><?= $totalProducts ?> Produits</span>
                        </div>
                        <div class="bg-white bg-opacity-20 px-4 py-2 rounded-md font-medium flex items-center">
                            <i class="fas fa-tags mr-2"></i>
                            <span><?= count($categories) ?> Catégories</span>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-10 -right-10 opacity-20">
                    <i class="fas fa-chart-pie text-9xl"></i>
                </div>
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-primary-light to-primary-dark opacity-90"></div>
            </div>
            
            <!-- Products Section -->
            <section id="products-section" class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Gestion des produits</h3>
                    <button id="add-product-btn" class="px-4 py-2 bg-primary-light dark:bg-primary-dark text-white rounded-md hover:bg-opacity-90 flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter un produit
                    </button>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="dashboard-card bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead>
        <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Catégorie</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="products-table-body">
        <?php if (empty($products)): ?>
            <tr>
                <td colspan="5" class="px-4 py-4 text-center">Aucun produit trouvé</td>
            </tr>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                            <img src="uploads/produits/<?= htmlspecialchars($product['image'] ?? 'default-product.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover">
                        </div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium"><?= htmlspecialchars($product['name']) ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($product['category_name'] ?? 'Non catégorisé') ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold">$<?= number_format($product['price'], 2) ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                        <button class="edit-product-btn mr-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" data-id="<?= $product['idproduit'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="delete-product-btn text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300" data-id="<?= $product['idproduit'] ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Affichage de <span id="products-start"><?= (($currentPage - 1) * 5) + 1 ?></span> à <span id="products-end"><?= min($currentPage * 5, $totalProducts) ?></span> sur <span id="products-total"><?= $totalProducts ?></span> produits
                        </div>
                        <div class="flex space-x-2">
                            <a href="?page=<?= $currentPage - 1 ?>" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-md <?= $currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <a href="?page=<?= $currentPage + 1 ?>" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-md <?= ($currentPage * 5) >= $totalProducts ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Categories Section -->
            <section id="categories-section" class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Gestion des catégories</h3>
                    <button id="add-category-btn" class="px-4 py-2 bg-primary-light dark:bg-primary-dark text-white rounded-md hover:bg-opacity-90 flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter une catégorie
                    </button>
                </div>
                
                <div class="dashboard-card bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nom</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nombre de produits</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="categories-table-body">
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-center">Aucune catégorie trouvée</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <span class="font-medium"><?= htmlspecialchars($category['name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= $category['product_count'] ?? 0 ?></td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                                <button class="edit-category-btn mr-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" data-id="<?= $category['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="delete-category-btn text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300" data-id="<?= $category['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Product Modal -->
            <div id="product-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4" id="product-modal-title">Ajouter un produit</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="product-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom du produit</label>
                                    <input type="text" id="product-name" name="name" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-light focus:border-primary-light dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label for="product-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Catégorie</label>
                                    <select id="product-category" name="idcategory" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-light focus:border-primary-light dark:bg-gray-700 dark:text-white">
                                        <option value="">-- Sélectionner une catégorie --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['idcategory'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="product-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prix</label>
                                    <input type="number" step="0.01" id="product-price" name="price" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-light focus:border-primary-light dark:bg-gray-700 dark:text-white">
                                </div>
                                <div>
                                    <label for="product-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                    <textarea id="product-description" name="description" rows="3" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-light focus:border-primary-light dark:bg-gray-700 dark:text-white"></textarea>
                                </div>
                                <div>
                                    <label for="product-image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Image</label>
                                    <input type="file" id="product-image" name="image" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-light file:text-white hover:file:bg-primary-dark">
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" id="save-product-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-light dark:bg-primary-dark text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-light sm:ml-3 sm:w-auto sm:text-sm">
                                Enregistrer
                            </button>
                            <button type="button" id="cancel-product-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-light sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Category Modal -->
            <div id="category-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75"></div>
                    </div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4" id="category-modal-title">Ajouter une catégorie</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="category-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom de la catégorie</label>
                                    <input type="text" id="category-name" class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-light focus:border-primary-light dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" id="save-category-btn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-light dark:bg-primary-dark text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-light sm:ml-3 sm:w-auto sm:text-sm">
                                Enregistrer
                            </button>
                            <button type="button" id="cancel-category-btn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-light sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-lg z-50">
        <div class="flex justify-around items-center p-2">
            <a href="#" class="p-2 rounded-full text-primary-light dark:text-primary-dark">
                <i class="fas fa-tachometer-alt text-xl"></i>
            </a>
            <a href="#products-section" class="p-2 rounded-full text-gray-500 dark:text-gray-400">
                <i class="fas fa-box-open text-xl"></i>
            </a>
            <a href="#categories-section" class="p-2 rounded-full text-gray-500 dark:text-gray-400">
                <i class="fas fa-tags text-xl"></i>
            </a>
            <a href="#" class="p-2 rounded-full text-gray-500 dark:text-gray-400">
                <i class="fas fa-cog text-xl"></i>
            </a>
        </div>
    </div>

    <script>
    // Gestion du CRUD
    document.addEventListener('DOMContentLoaded', function() {
        // Variables globales
        let currentProductId = null;
        let currentCategoryId = null;
        
        // Éléments du DOM
        const productModal = document.getElementById('product-modal');
        const categoryModal = document.getElementById('category-modal');
        
        // Ouvrir le modal produit
        document.getElementById('add-product-btn')?.addEventListener('click', () => {
            currentProductId = null;
            document.getElementById('product-modal-title').textContent = 'Ajouter un produit';
            document.getElementById('product-form').reset();
            productModal.classList.remove('hidden');
        });
        
        // Éditer un produit
        document.querySelectorAll('.edit-product-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                currentProductId = this.dataset.id;
                document.getElementById('product-modal-title').textContent = 'Modifier le produit';
                
                try {
                    const response = await fetch('config.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_product&id=${currentProductId}`
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        document.getElementById('product-name').value = data.data.name;
                        document.getElementById('product-category').value = data.data.idcategory;
                        document.getElementById('product-price').value = data.data.price;
                        document.getElementById('product-description').value = data.data.description;
                        productModal.classList.remove('hidden');
                    } else {
                        Swal.fire('Erreur', data.message || 'Erreur lors du chargement du produit', 'error');
                    }
                } catch (error) {
                    Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                    console.error(error);
                }
            });
        });
        
        // Sauvegarder un produit
        document.getElementById('save-product-btn')?.addEventListener('click', async function() {
            const formData = new FormData(document.getElementById('product-form'));
            formData.append('action', currentProductId ? 'update_product' : 'add_product');
            if (currentProductId) formData.append('id', currentProductId);
            
            try {
                const response = await fetch('config.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Succès', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                console.error(error);
            }
        });
        
        // Supprimer un produit
        document.querySelectorAll('.delete-product-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productId = this.dataset.id;
                Swal.fire({
                    title: 'Êtes-vous sûr?',
                    text: "Vous ne pourrez pas revenir en arrière!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Oui, supprimer!'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await fetch('config.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=delete_product&id=${productId}`
                            });
                            const data = await response.json();
                            
                            if (data.success) {
                                Swal.fire('Supprimé!', data.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Erreur', data.message, 'error');
                            }
                        } catch (error) {
                            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                            console.error(error);
                        }
                    }
                });
            });
        });
        
        // Gestion des catégories
        document.getElementById('add-category-btn')?.addEventListener('click', function() {
            currentCategoryId = null;
            document.getElementById('category-modal-title').textContent = 'Ajouter une catégorie';
            document.getElementById('category-name').value = '';
            categoryModal.classList.remove('hidden');
        });
        
        document.querySelectorAll('.edit-category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentCategoryId = this.dataset.id;
                document.getElementById('category-modal-title').textContent = 'Modifier la catégorie';
                document.getElementById('category-name').value = this.closest('tr').querySelector('td span').textContent;
                categoryModal.classList.remove('hidden');
            });
        });
        
        document.getElementById('save-category-btn')?.addEventListener('click', async function() {
            const name = document.getElementById('category-name').value.trim();
            if (!name) {
                Swal.fire('Erreur', 'Le nom de la catégorie est requis', 'error');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_category');
                formData.append('name', name);
                if (currentCategoryId) formData.append('id', currentCategoryId);
                
                const response = await fetch('config.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('Succès', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            } catch (error) {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                console.error(error);
            }
        });
        
        // Supprimer une catégorie
        document.querySelectorAll('.delete-category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const categoryId = this.dataset.id;
                Swal.fire({
                    title: 'Êtes-vous sûr?',
                    text: "Tous les produits de cette catégorie seront déplacés dans 'Non catégorisé'!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#ef4444',
                    confirmButtonText: 'Oui, supprimer!'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const response = await fetch('config.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=delete_category&id=${categoryId}`
                            });
                            const data = await response.json();
                            
                            if (data.success) {
                                Swal.fire('Supprimé!', data.message, 'success').then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire('Erreur', data.message, 'error');
                            }
                        } catch (error) {
                            Swal.fire('Erreur', 'Une erreur est survenue', 'error');
                            console.error(error);
                        }
                    }
                });
            });
        });
        
        // Fermer les modals
        document.getElementById('cancel-product-btn')?.addEventListener('click', () => productModal.classList.add('hidden'));
        document.getElementById('cancel-category-btn')?.addEventListener('click', () => categoryModal.classList.add('hidden'));
    });
    </script>

</body>
</html>