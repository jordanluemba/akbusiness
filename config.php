<?php
// config.php - Configuration et fonctions CRUD

error_reporting(0);
ini_set('display_errors', 0);

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'akbusiness');
define('DB_USER', 'root');
define('DB_PASS', '');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_PATH', 'uploads/');

// Connexion PDO
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// ==================== FONCTIONS PRODUITS ==================== //

function getProducts($page = 1, $perPage = 5) {
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name
        FROM produit p
        LEFT JOIN category c ON p.idcategory = c.idcategory
        LIMIT :offset, :perPage");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getProductById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM produit WHERE idproduit = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function handleImageUpload() {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = uniqid() . '_' . basename($_FILES['image']['name']);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $targetPath = UPLOAD_DIR . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        return UPLOAD_PATH . $filename;
    }

    return null;
}

function saveProduct($data) {
    global $pdo;

    if (empty($data['name'])) {
        throw new Exception("Le nom du produit est obligatoire.");
    }

    // Gestion de l'image uploadée
    $imageName = 'default.jpg';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = 'uploads/produits/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Crée le dossier s’il n’existe pas
        }

        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
    } elseif (!empty($data['image'])) {
        $imageName = $data['image']; // Lors d’une mise à jour sans changement d’image
    }

    if (isset($data['idproduit'])) {
        // Mise à jour
        $stmt = $pdo->prepare("UPDATE produit SET name = ?, idcategory = ?, price = ?, description = ?, image = ? WHERE idproduit = ?");
        $stmt->execute([
            $data['name'],
            $data['idcategory'],
            $data['price'],
            $data['description'],
            $imageName,
            $data['idproduit']
        ]);
        return $data['idproduit'];
    } else {
        // Insertion
        $stmt = $pdo->prepare("INSERT INTO produit (name, idcategory, price, description, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['name'],
            $data['idcategory'],
            $data['price'],
            $data['description'],
            $imageName
        ]);
        return $pdo->lastInsertId();
    }
}


function deleteProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM produit WHERE idproduit = ?");
    return $stmt->execute([$id]);
}

function countProducts() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM produit");
    return $stmt->fetchColumn();
}

// ==================== FONCTIONS CATÉGORIES ==================== //

function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT c.*, COUNT(p.idproduit) as product_count
        FROM category c
        LEFT JOIN produit p ON p.idcategory = c.idcategory
        GROUP BY c.idcategory");
    return $stmt->fetchAll();
}

function getCategoryById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM category WHERE idcategory = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function saveCategory($data) {
    global $pdo;

    if (empty($data['name'])) {
        throw new Exception("Le nom de la catégorie est obligatoire.");
    }

    if (isset($data['idcategory'])) {
        $stmt = $pdo->prepare("UPDATE category SET name = ? WHERE idcategory = ?");
        $stmt->execute([$data['name'], $data['idcategory']]);
        return $data['idcategory'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO category (name) VALUES (?)");
        $stmt->execute([$data['name']]);
        return $pdo->lastInsertId();
    }
}

function deleteCategory($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produit WHERE idcategory = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Impossible de supprimer cette catégorie car elle est utilisée par des produits.");
    }

    $stmt = $pdo->prepare("DELETE FROM category WHERE idcategory = ?");
    return $stmt->execute([$id]);
}

// ==================== GESTION AJAX ==================== //

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');

    try {
        $response = ['success' => false];

        switch ($_POST['action'] ?? '') {
            case 'get_products':
                $page = $_POST['page'] ?? 1;
                $response = [
                    'success' => true,
                    'data' => getProducts($page),
                    'total' => countProducts(),
                    'page' => $page
                ];
                break;

            case 'get_product':
                if (!empty($_POST['id'])) {
                    $response = [
                        'success' => true,
                        'data' => getProductById($_POST['id'])
                    ];
                }
                break;

            case 'save_product':
                $data = [
                    'name' => $_POST['name'],
                    'idcategory' => $_POST['idcategory'],
                    'price' => $_POST['price'],
                    'description' => $_POST['description']
                ];
                if (!empty($_POST['id'])) {
                    $data['idproduit'] = $_POST['id'];
                }
                $id = saveProduct($data);
                $response = [
                    'success' => true,
                    'id' => $id,
                    'message' => empty($_POST['id']) ? 'Produit ajouté avec succès.' : 'Produit mis à jour avec succès.'
                ];
                break;

            case 'delete_product':
                if (!empty($_POST['id'])) {
                    deleteProduct($_POST['id']);
                    $response = ['success' => true, 'message' => 'Produit supprimé avec succès.'];
                }
                break;

            case 'get_categories':
                $response = ['success' => true, 'data' => getCategories()];
                break;

            case 'get_category':
                if (!empty($_POST['id'])) {
                    $response = ['success' => true, 'data' => getCategoryById($_POST['id'])];
                }
                break;

            case 'save_category':
                $data = ['name' => $_POST['name']];
                if (!empty($_POST['id'])) {
                    $data['idcategory'] = $_POST['id'];
                }
                $id = saveCategory($data);
                $response = [
                    'success' => true,
                    'id' => $id,
                    'message' => empty($_POST['id']) ? 'Catégorie ajoutée avec succès.' : 'Catégorie mise à jour avec succès.'
                ];
                break;

            case 'delete_category':
                if (!empty($_POST['id'])) {
                    deleteCategory($_POST['id']);
                    $response = ['success' => true, 'message' => 'Catégorie supprimée avec succès.'];
                }
                break;
        }

    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}
