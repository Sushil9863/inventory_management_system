<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Case-sensitive exact match
    $stmt = $pdo->prepare("SELECT * FROM users WHERE BINARY username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        header("Location: index.php");
        exit();
    } else {
        // Generic error message (don't reveal which was wrong)
        $error = "Invalid username or password";
        
        // Security: Add delay to prevent brute force
        usleep(500000); // 0.5 second delay
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6 col-lg-4">
                <div class="card bg-dark-2 border-primary">
                    <div class="card-header bg-primary text-center py-3">
                        <h3><i class="bi bi-box-seam"></i> Inventory System</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" autocomplete="off">
                            <div class="mb-3">
                                <label for="username" class="form-label text-muted">Username</label>
                                <input type="text" class="form-control bg-dark text-light" id="username" 
                                       name="username" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label text-muted">Password</label>
                                <input type="password" class="form-control bg-dark text-light" id="password" 
                                       name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center text-muted">
                        <small>Inventory System v1.0</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Focus the username field on page load
        document.getElementById('username').focus();
        
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>