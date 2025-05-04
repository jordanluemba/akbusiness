<?php
// dashboard.php - Page principale du dashboard avec intégration CRUD

// Inclure la configuration et les fonctions CRUD
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer les données pour l'affichage
try {
    // Produits pour la première page
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $products = getProducts($currentPage, 5);
    $totalProducts = countProducts();
    
    // Catégories
    $categories = getCategories();
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des données: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr" class="light">
<!-- [HEAD reste identique] -->
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <!-- [HEADER reste identique] -->

    <!-- Main Content -->
    <div class="flex flex-1 overflow-hidden">
        <!-- [SIDEBAR reste identique] -->

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <!-- [Welcome Banner reste identique] -->
            
            <!-- Products Section -->
            <section id="products-section" class="mb-8">
                <!-- [En-tête reste identique] -->
                
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
                                                    <img src="<?= htmlspecialchars($product['image'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover">
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
                    <!-- [Pagination reste identique] -->
                </div>
            </section>
            
            <!-- Categories Section -->
            <section id="categories-section" class="mb-8">
                <!-- [En-tête reste identique] -->
                
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
                                                <button class="edit-category-btn mr-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" data-id="<?= $category['idcategory'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="delete-category-btn text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300" data-id="<?= $category['idcategory'] ?>">
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
                <!-- [Structure du modal reste identique] -->
            </div>
            
            <!-- Category Modal -->
            <div id="category-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
                <!-- [Structure du modal reste identique] -->
            </div>
        </main>
    </div>

    <!-- [Mobile Bottom Navigation reste identique] -->

    <script>
    // [Initialisation des éléments DOM reste identique]

    // État
    let currentProductId = null;
    let currentCategoryId = null;

    // Initialisation
    function init() {
        setupEventListeners();
    }

    // Ouvrir le modal produit
    async function openProductModal(productId = null) {
        const modalTitle = document.getElementById('product-modal-title');
        currentProductId = productId;

        if (productId) {
            modalTitle.textContent = 'Modifier le produit';
            try {
                const response = await fetch('config.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=get_product&id=${productId}`
                });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('product-name').value = data.data.name;
                    document.getElementById('product-category').value = data.data.idcategory;
                    document.getElementById('product-price').value = data.data.price;
                    document.getElementById('product-description').value = data.data.description;
                    // Note: L'image existante n'est pas modifiable ici, elle est gérée dans saveProduct()
                } else {
                    showError(data.message || 'Erreur lors du chargement du produit');
                }
            } catch (error) {
                console.error(error);
                showError('Une erreur est survenue');
            }
        } else {
            modalTitle.textContent = 'Ajouter un produit';
            document.getElementById('product-name').value = '';
            document.getElementById('product-category').value = '';
            document.getElementById('product-price').value = '';
            document.getElementById('product-description').value = '';
            document.getElementById('product-image').value = '';
        }

        productModal.classList.remove('hidden');
    }

    // Sauvegarder un produit
    async function saveProduct() {
        const name = document.getElementById('product-name').value.trim();
        const categoryId = document.getElementById('product-category').value;
        const price = document.getElementById('product-price').value;
        const description = document.getElementById('product-description').value.trim();

        if (!name || !categoryId || !price || !description) {
            showError('Veuillez remplir tous les champs obligatoires');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'save_product');
        formData.append('name', name);
        formData.append('idcategory', categoryId);
        formData.append('price', price);
        formData.append('description', description);
        
        if (currentProductId) {
            formData.append('idproduit', currentProductId);
        }

        const imageInput = document.getElementById('product-image');
        if (imageInput.files.length > 0) {
            formData.append('image', imageInput.files[0]);
        }

        try {
            const response = await fetch('config.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            
            if (data.success) {
                showSuccess(data.message || (currentProductId ? 'Produit mis à jour' : 'Produit ajouté'), () => {
                    productModal.classList.add('hidden');
                    window.location.reload();
                });
            } else {
                showError(data.message || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error(error);
            showError('Une erreur est survenue');
        }
    }

    // Sauvegarder une catégorie
    async function saveCategory() {
        const name = document.getElementById('category-name').value.trim();
        if (!name) {
            showError('Le nom de la catégorie est obligatoire');
            return;
        }

        try {
            const response = await fetch('config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=save_category&name=${encodeURIComponent(name)}${currentCategoryId ? '&idcategory=' + currentCategoryId : ''}`
            });
            const data = await response.json();
            
            if (data.success) {
                showSuccess(data.message || (currentCategoryId ? 'Catégorie mise à jour' : 'Catégorie ajoutée'), () => {
                    categoryModal.classList.add('hidden');
                    window.location.reload();
                });
            } else {
                showError(data.message || 'Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error(error);
            showError('Une erreur est survenue');
        }
    }

    // Supprimer un produit
    async function deleteProduct(productId) {
        Swal.fire({
            title: 'Êtes-vous sûr?',
            text: "Vous ne pourrez pas revenir en arrière!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Oui, supprimer!',
            cancelButtonText: 'Annuler'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('config.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=delete_product&id=${productId}`
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        showSuccess(data.message || 'Produit supprimé', () => {
                            window.location.reload();
                        });
                    } else {
                        showError(data.message || 'Échec de la suppression');
                    }
                } catch (error) {
                    console.error(error);
                    showError('Une erreur est survenue');
                }
            }
        });
    }

    // Supprimer une catégorie
    async function deleteCategory(categoryId) {
        Swal.fire({
            title: 'Êtes-vous sûr?',
            text: "Cette action ne peut être annulée!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Oui, supprimer!',
            cancelButtonText: 'Annuler'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('config.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: `action=delete_category&id=${categoryId}`
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        showSuccess(data.message || 'Catégorie supprimée', () => {
                            window.location.reload();
                        });
                    } else {
                        showError(data.message || 'Échec de la suppression');
                    }
                } catch (error) {
                    console.error(error);
                    showError('Une erreur est survenue');
                }
            }
        });
    }

    // Fonctions utilitaires
    function showSuccess(message, callback = null) {
        Swal.fire({
            icon: 'success',
            title: 'Succès',
            text: message,
            confirmButtonColor: '#4f46e5'
        }).then(() => {
            if (callback) callback();
        });
    }

    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Erreur',
            text: message,
            confirmButtonColor: '#4f46e5'
        });
    }

    // [setupEventListeners et initialisation restent identiques]
    document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>