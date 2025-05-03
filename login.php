<?php
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Vérifier les identifiants lors de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Identifiants par défaut
    $defaultUsername = 'akish';
    $defaultPassword = 'akish123';

    if ($username === $defaultUsername && $password === $defaultPassword) {
        // Authentification réussie
        $_SESSION['user_id'] = 1;
        $_SESSION['user_name'] = 'Akish';
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Identifiants incorrects";
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - AK Business</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        }
        
        .dark body {
            background-color: #111827;
            background-image: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .dark .login-card {
            background: #1f2937;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
        .logo-container {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            padding: 2rem;
            text-align: center;
        }
        
        .logo-text {
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
            letter-spacing: 1px;
        }
        
        .login-btn {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(79, 70, 229, 0.4);
        }
        
        .input-field {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .input-field:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        
        .dark .input-field {
            background: #374151;
            border-color: #4b5563;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="login-card bg-white dark:bg-gray-800 w-full max-w-md">
            <!-- En-tête avec logo -->
            <div class="logo-container">
                <div class="flex justify-center items-center space-x-2">
                    <i class="fas fa-shopping-bag text-white text-3xl"></i>
                    <span class="logo-text">AK BUSINESS</span>
                </div>
                <p class="text-white opacity-90 mt-2">Gestion de votre boutique</p>
            </div>
            
            <!-- Formulaire -->
            <div class="p-8">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white text-center mb-6">Connexion</h1>
                
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" required 
                               class="input-field w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-light dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Mot de passe</label>
                        <input type="password" id="password" name="password" required 
                               class="input-field w-full px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-light dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div class="pt-2">
                        <button type="submit" 
                                class="login-btn w-full py-3 px-4 text-white font-bold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-light transition duration-200">
                            <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Gestion du thème sombre
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>