<?php
require_once 'config.php';

// Configuration des chemins d'images
define('PRODUCT_IMAGE_PATH', 'uploads/produits/');
define('DEFAULT_IMAGE', 'chemin/vers/image/par-defaut.jpg'); // À remplacer par votre image par défaut

// Paramètres de pagination
$itemsPerPage = 12;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Paramètres de recherche
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Récupération des produits depuis la base de données
$products = [];
$categories = [];
$totalProducts = 0;

try {
    // Requête de base pour les produits
    $sql = "SELECT * FROM produit";
    $params = [];
    
    // Ajout de la condition de recherche si un terme est spécifié
    if (!empty($searchTerm)) {
        $sql .= " WHERE name LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $searchTerm . '%';
    }
    
    // Requête pour le nombre total de produits (pour la pagination)
    $countStmt = $pdo->prepare(str_replace('*', 'COUNT(*)', $sql));
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetchColumn();
    
    // Ajout de la pagination à la requête principale
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $itemsPerPage;
    $params[':offset'] = $offset;
    
    // Récupérer les produits avec leur URL d'image complète
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$val) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindParam($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindParam($key, $val);
        }
    }
    $stmt->execute();
    
    while ($product = $stmt->fetch()) {
        $product['image_url'] = !empty($product['image']) ? PRODUCT_IMAGE_PATH . $product['image'] : DEFAULT_IMAGE;
        $products[] = $product;
    }
    
    // Récupérer les catégories
    $stmt = $pdo->query("SELECT DISTINCT idcategory, name FROM category");
    while ($cat = $stmt->fetch()) {
        $categories[$cat['idcategory']] = $cat['name'];
    }
} catch (PDOException $e) {
    error_log("Erreur de base de données: " . $e->getMessage());
}

// Calcul du nombre total de pages
$totalPages = ceil($totalProducts / $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AK Business</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                        }
                    }
                }
            }
        }
    </script>
    <style>
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
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .product-card {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-shopping-bag text-2xl text-primary-light dark:text-primary-dark"></i>
                <h1 class="text-xl font-bold">AK Business</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-moon dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block"></i>
                </button>
                
                <button id="cart-button" class="relative p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count" class="absolute -top-1 -right-1 bg-primary-light dark:bg-primary-dark text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        <!-- Search and Categories -->
        <div class="mb-8">
            <!-- Search Bar -->
            <div class="mb-6">
                <form method="GET" action="" class="flex">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                           placeholder="Rechercher des produits..." 
                           class="flex-grow px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-primary-light dark:focus:ring-primary-dark bg-white dark:bg-gray-800">
                    <button type="submit" class="px-4 py-2 bg-primary-light dark:bg-primary-dark text-white rounded-r-lg hover:bg-opacity-90">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <?php if (!empty($searchTerm)): ?>
                    <div class="mt-2 text-sm">
                        <span class="text-gray-600 dark:text-gray-300">Résultats pour: "<?= htmlspecialchars($searchTerm) ?>"</span>
                        <a href="?" class="ml-2 text-primary-light dark:text-primary-dark hover:underline">Effacer la recherche</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Categories -->
            <h2 class="text-xl font-semibold mb-4">Catégories</h2>
            <div class="flex flex-wrap gap-2">
                <button class="category-btn px-4 py-2 rounded-full bg-primary-light dark:bg-primary-dark text-white" data-category="all">Tous</button>
                <?php foreach ($categories as $id => $name): ?>
                <button class="category-btn px-4 py-2 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600" data-category="<?= $id ?>">
                    <?= htmlspecialchars($name) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="products-container">
            <?php foreach ($products as $product): ?>
            <div class="product-card bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden flex flex-col" data-category="<?= $product['idcategory'] ?>">
                <div class="p-4 flex-grow">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="w-full h-40 object-contain mb-4">
                    <h3 class="font-semibold mb-1"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2 line-clamp-2"><?= htmlspecialchars($product['description']) ?></p>
                    <p class="font-bold text-primary-light dark:text-primary-dark mb-3"><?= number_format($product['price'], 2) ?> $</p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button class="quantity-btn px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded" data-action="decrease" data-id="<?= $product['idproduit'] ?>">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <span class="w-8 text-center quantity-display" data-id="<?= $product['idproduit'] ?>">1</span>
                            <button class="quantity-btn px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded" data-action="increase" data-id="<?= $product['idproduit'] ?>">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                        <button class="add-to-cart-btn px-3 py-1 bg-primary-light dark:bg-primary-dark text-white text-sm rounded hover:bg-opacity-90" data-id="<?= $product['idproduit'] ?>">
                            <i class="fas fa-cart-plus mr-1"></i> Ajouter
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-8 flex justify-center">
            <nav class="flex items-center space-x-2">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded <?= $i === $currentPage ? 'bg-primary-light dark:bg-primary-dark text-white' : 'border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '' ?>" 
                       class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

        <!-- No results message -->
        <?php if (empty($products) && !empty($searchTerm)): ?>
            <div class="text-center py-12">
                <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-600 dark:text-gray-300">Aucun résultat trouvé</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Essayez avec d'autres termes de recherche</p>
                <a href="?" class="mt-4 inline-block px-4 py-2 bg-primary-light dark:bg-primary-dark text-white rounded-lg hover:bg-opacity-90">
                    Voir tous les produits
                </a>
            </div>
        <?php elseif (empty($products)): ?>
            <div class="text-center py-12">
                <i class="fas fa-box-open text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-600 dark:text-gray-300">Aucun produit disponible</h3>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 shadow-sm mt-8">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-lg font-bold flex items-center">
                        <i class="fas fa-shopping-bag text-xl text-primary-light dark:text-primary-dark mr-2"></i>
                        AK Business
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Votre boutique en ligne préférée</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Développé par <a href="https://linktr.ee/Jordane_Luemba" target="_blank" class="text-primary-light dark:text-primary-dark hover:underline">Jordane Luemba</a>
                    </p>
                </div>
                <div class="flex space-x-4">
                        <a href="https://www.facebook.com/akichmajaveli.kiaku" class="text-gray-600 dark:text-gray-300 hover:text-primary-light dark:hover:text-primary-dark">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/akichkapita?igsh=MTFpb3VrYXJmenVkcw==" class="text-gray-600 dark:text-gray-300 hover:text-primary-light dark:hover:text-primary-dark">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://wa.me/243810571546" class="text-gray-600 dark:text-gray-300 hover:text-primary-light dark:hover:text-primary-dark">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 mt-6 pt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                <p>© 2025 AK Business. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Cart Sidebar -->
    <div id="cart-sidebar" class="fixed top-0 right-0 h-full w-full sm:w-96 bg-white dark:bg-gray-800 shadow-lg transform translate-x-full transition-transform duration-300 ease-in-out z-50 overflow-y-auto">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="text-xl font-semibold">Votre Panier</h2>
            <button id="close-cart" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-4" id="cart-items">
            <div class="text-center py-8 text-gray-500" id="empty-cart-message">
                <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                <p>Votre panier est vide</p>
            </div>
        </div>
        
        <div class="p-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex justify-between mb-4">
                <span class="font-semibold">Total:</span>
                <span class="font-bold" id="cart-total">0 $</span>
            </div>
            <button id="checkout-btn" class="w-full bg-primary-light dark:bg-primary-dark text-white py-3 rounded-lg hover:bg-opacity-90 flex items-center justify-center space-x-2">
                <i class="fab fa-whatsapp"></i>
                <span>Commander via WhatsApp</span>
            </button>
        </div>
    </div>

    <!-- Overlay -->
    <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

    <script>
        // Convert PHP products to JavaScript array
        const products = <?= json_encode(array_map(function($product) {
            return [
                'id' => $product['idproduit'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'image' => $product['image_url'],
                'idcategory' => $product['idcategory'],
                'description' => $product['description']
            ];
        }, $products)) ?>;

        // DOM Elements
        const productsContainer = document.getElementById('products-container');
        const cartSidebar = document.getElementById('cart-sidebar');
        const cartButton = document.getElementById('cart-button');
        const closeCart = document.getElementById('close-cart');
        const overlay = document.getElementById('overlay');
        const cartItems = document.getElementById('cart-items');
        const cartCount = document.getElementById('cart-count');
        const cartTotal = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        const emptyCartMessage = document.getElementById('empty-cart-message');
        const categoryButtons = document.querySelectorAll('.category-btn');
        const themeToggle = document.getElementById('theme-toggle');

        // State
        let cart = [];

        // Initialize
        function init() {
            loadCart();
            setupEventListeners();
        }

        // Format price
        function formatPrice(price) {
            return `$${price.toFixed(2)}`;
        }

        // Load cart from localStorage
        function loadCart() {
            const savedCart = localStorage.getItem('cart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                updateCartUI();
            }
        }

        // Save cart to localStorage
        function saveCart() {
            localStorage.setItem('cart', JSON.stringify(cart));
        }

        // Update cart UI
        function updateCartUI() {
            if (cart.length > 0) {
                emptyCartMessage.classList.add('hidden');
                
                cartItems.innerHTML = '';
                cart.forEach((item, index) => {
                    const cartItem = document.createElement('div');
                    cartItem.className = 'cart-item flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700';
                    cartItem.innerHTML = `
                        <div class="flex items-center space-x-3">
                            <img src="${item.image}" alt="${item.name}" class="w-12 h-12 object-contain rounded">
                            <div>
                                <h4 class="font-medium">${item.name}</h4>
                                <p class="text-sm text-gray-500">${formatPrice(item.price)} × ${item.quantity}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button class="remove-item-btn p-1 text-red-500 hover:text-red-700" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    cartItems.appendChild(cartItem);
                });
            } else {
                emptyCartMessage.classList.remove('hidden');
            }
            
            const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
            cartCount.textContent = totalItems;
            
            const totalPrice = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
            cartTotal.textContent = formatPrice(totalPrice);
        }

        // Add to cart
        function addToCart(product, quantity = 1) {
            const existingItem = cart.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    ...product,
                    quantity: quantity
                });
            }
            
            saveCart();
            updateCartUI();
            
            // Show notification
            showNotification(`${product.name} ajouté au panier`);
        }

        // Remove from cart
        // Remove from cart
function removeFromCart(index) {
    // Sauvegarder l'ID du produit avant suppression pour mise à jour de l'interface
    const removedProductId = cart[index].id;
    
    // Supprimer l'élément du panier
    cart.splice(index, 1);
    saveCart();
    updateCartUI();
    
    // Mettre à jour l'affichage des quantités dans la liste des produits
    updateProductQuantityDisplays(removedProductId);
}

// Fonction pour mettre à jour l'affichage des quantités dans la liste des produits
function updateProductQuantityDisplays(productId) {
    const quantityDisplay = document.querySelector(`.quantity-display[data-id="${productId}"]`);
    if (quantityDisplay) {
        quantityDisplay.textContent = '1'; // Réinitialiser à 1
    }
}

        // Show notification
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 animate-fade-in';
            notification.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${message}</span>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('animate-fade-out');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Get quantity for a product
        function getQuantityForProduct(productId) {
            const quantityDisplay = document.querySelector(`.quantity-display[data-id="${productId}"]`);
            return quantityDisplay ? parseInt(quantityDisplay.textContent) : 1;
        }

        // Filter products by category
        function filterProducts(categoryId) {
            const allProducts = document.querySelectorAll('.product-card');
            
            allProducts.forEach(product => {
                if (categoryId === 'all' || product.dataset.category === categoryId) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        // Setup event listeners
        function setupEventListeners() {
            // Cart sidebar toggle
            cartButton.addEventListener('click', () => {
                cartSidebar.classList.remove('translate-x-full');
                overlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            });
            
            closeCart.addEventListener('click', () => {
                cartSidebar.classList.add('translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            });
            
            overlay.addEventListener('click', () => {
                cartSidebar.classList.add('translate-x-full');
                overlay.classList.add('hidden');
                document.body.style.overflow = '';
            });
            
            // Product quantity and add to cart
            productsContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('quantity-btn') || e.target.closest('.quantity-btn')) {
                    const button = e.target.classList.contains('quantity-btn') ? e.target : e.target.closest('.quantity-btn');
                    const action = button.dataset.action;
                    const productId = parseInt(button.dataset.id);
                    const quantityDisplay = document.querySelector(`.quantity-display[data-id="${productId}"]`);
                    
                    if (quantityDisplay) {
                        let quantity = parseInt(quantityDisplay.textContent);
                        
                        if (action === 'increase') {
                            quantity += 1;
                        } else if (action === 'decrease' && quantity > 1) {
                            quantity -= 1;
                        }
                        
                        quantityDisplay.textContent = quantity;
                    }
                }
                
                if (e.target.classList.contains('add-to-cart-btn') || e.target.closest('.add-to-cart-btn')) {
                    const button = e.target.classList.contains('add-to-cart-btn') ? e.target : e.target.closest('.add-to-cart-btn');
                    const productId = parseInt(button.dataset.id);
                    const product = products.find(p => p.id === productId);
                    const quantity = getQuantityForProduct(productId);
                    
                    if (product) {
                        addToCart(product, quantity);
                    }
                }
            });
            
            // Cart item removal
            cartItems.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-item-btn') || e.target.closest('.remove-item-btn')) {
                    const index = parseInt(e.target.dataset.index || e.target.closest('.remove-item-btn').dataset.index);
                    removeFromCart(index);
                }
            });
            
            // Checkout
            checkoutBtn.addEventListener('click', () => {
                if (cart.length === 0) return;
                
                const totalPrice = cart.reduce((total, item) => total + (item.price * item.quantity), 0);
                
                let message = `Bonjour *AKBUSINESS*, je souhaite commander les articles suivants:\n\n`;
                
                cart.forEach(item => {
                    message += `- ${item.name} (${item.quantity} × ${formatPrice(item.price)})\n`;
                });
                
                message += `\nTotal: ${formatPrice(totalPrice)}\n\nMerci !`;
                
                const encodedMessage = encodeURIComponent(message);
                window.open(`https://wa.me/243810571546?text=${encodedMessage}`, '_blank');
            });
            
            // Category filtering
            categoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const categoryId = button.dataset.category;
                    
                    categoryButtons.forEach(btn => {
                        if (btn === button) {
                            btn.classList.remove('bg-gray-200', 'dark:bg-gray-700');
                            btn.classList.add('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
                        } else {
                            btn.classList.remove('bg-primary-light', 'dark:bg-primary-dark', 'text-white');
                            btn.classList.add('bg-gray-200', 'dark:bg-gray-700');
                        }
                    });
                    
                    filterProducts(categoryId);
                });
            });
            
            // Theme toggle
            themeToggle.addEventListener('click', () => {
                if (document.documentElement.classList.contains('dark')) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
            });
            
            // Check for saved theme preference
            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        // Initialize the app
        init();
    </script>
</body>
</html>