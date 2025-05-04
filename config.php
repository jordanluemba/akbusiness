<?php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'votre_base');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_mdp');

// Connexion PDO
try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// Créer le répertoire uploads s'il n'existe pas
if (!file_exists('uploads/produits')) {
    mkdir('uploads/produits', 0777, true);
}

// Gestion des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    try {
        switch ($_POST['action']) {
            case 'get_product':
                $stmt = $pdo->prepare("SELECT * FROM produit WHERE idproduit = ?");
                $stmt->execute([$_POST['id']]);
                $product = $stmt->fetch();
                if ($product) {
                    $response = ['success' => true, 'data' => $product];
                } else {
                    $response['message'] = 'Produit non trouvé';
                }
                break;

            case 'add_product':
            case 'update_product':
                $name = trim($_POST['name']);
                $price = floatval($_POST['price']);
                $idcategory = intval($_POST['idcategory']);
                $description = trim($_POST['description']);

                if (empty($name)) {
                    throw new Exception('Le nom du produit est requis');
                }

                $data = [
                    'name' => $name,
                    'price' => $price,
                    'idcategory' => $idcategory,
                    'description' => $description
                ];

                // Gestion de l'image
                if (!empty($_FILES['image']['name'])) {
                    $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = uniqid() . '.' . $fileExt;
                    $filePath = 'uploads/produits/' . $fileName;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        $data['image'] = $fileName;
                    }
                }

                if ($_POST['action'] === 'add_product') {
                    $stmt = $pdo->prepare("INSERT INTO produit (name, price, idcategory, description, image) 
                                         VALUES (:name, :price, :idcategory, :description, :image)");
                } else {
                    $data['idproduit'] = $_POST['id'];
                    $sql = "UPDATE produit SET 
                            name = :name, 
                            price = :price, 
                            idcategory = :idcategory, 
                            description = :description";
                    
                    if (isset($data['image'])) {
                        $sql .= ", image = :image";
                    }
                    
                    $sql .= " WHERE idproduit = :idproduit";
                    $stmt = $pdo->prepare($sql);
                }

                $stmt->execute($data);
                $response = ['success' => true, 'message' => 'Produit ' . ($_POST['action'] === 'add_product' ? 'ajouté' : 'mis à jour') . ' avec succès'];
                break;

            case 'delete_product':
                $stmt = $pdo->prepare("DELETE FROM produit WHERE idproduit = ?");
                $stmt->execute([$_POST['id']]);
                $response = ['success' => true, 'message' => 'Produit supprimé avec succès'];
                break;

            case 'save_category':
                $name = trim($_POST['name']);
                if (empty($name)) {
                    throw new Exception('Le nom de la catégorie est requis');
                }

                if (isset($_POST['id'])) {
                    $stmt = $pdo->prepare("UPDATE category SET name = ? WHERE idcategory = ?");
                    $stmt->execute([$name, $_POST['id']]);
                    $message = 'Catégorie mise à jour';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO category (name) VALUES (?)");
                    $stmt->execute([$name]);
                    $message = 'Catégorie ajoutée';
                }
                $response = ['success' => true, 'message' => $message];
                break;

            case 'delete_category':
                // D'abord mettre à jour les produits de cette catégorie
                $stmt = $pdo->prepare("UPDATE produit SET idcategory = NULL WHERE idcategory = ?");
                $stmt->execute([$_POST['id']]);
                
                // Puis supprimer la catégorie
                $stmt = $pdo->prepare("DELETE FROM category WHERE idcategory = ?");
                $stmt->execute([$_POST['id']]);
                
                $response = ['success' => true, 'message' => 'Catégorie supprimée et produits mis à jour'];
                break;

            default:
                $response['message'] = 'Action non reconnue';
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Fonctions pour le dashboard
function getProducts($page = 1, $perPage = 5) {
    global $pdo;
    $offset = ($page - 1) * $perPage;
    
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                          FROM produit p 
                          LEFT JOIN category c ON p.idcategory = c.idcategory 
                          LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function countProducts() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM produit")->fetchColumn();
}

function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT c.*, COUNT(p.idproduit) as product_count 
                        FROM category c 
                        LEFT JOIN produit p ON c.idcategory = p.idcategory 
                        GROUP BY c.idcategory");
    return $stmt->fetchAll();
}