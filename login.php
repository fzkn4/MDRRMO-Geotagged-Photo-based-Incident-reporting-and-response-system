<?php
define('SECURE_ACCESS', true);
require_once 'auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $userRole = getUserRole();
    if ($userRole === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: client-dashboard.php');
    }
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $auth_result = authenticateUser($username, $password);
        
        if ($auth_result) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();
            
            // Redirect based on user role
            $userRole = getUserRole();
            if ($userRole === 'admin') {
                header('Location: admin-dashboard.php');
            } else {
                header('Location: client-dashboard.php');
            }
            exit();
        } else {
            $error_message = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MDRRMO | Login</title>

    <!-- Tab Icon / Favicon -->
    <link rel="icon" type="image/png" href="assets/icon.png" />
    <link rel="shortcut icon" type="image/png" href="assets/icon.png" />

    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />

    <!-- Bootstrap Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    />

    <link rel="stylesheet" href="styles/login.css" />
  </head>
  <body style="overflow: hidden;">
    <!-- Floating background shapes -->
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>

    <div class="login-container">
      <div class="card login-card">
        <div class="login-header">
          <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
            <img src="assets/icon.png" alt="MDRRMO Logo" style="max-width: 80px; height: auto;" />
          </div>
          <h4 class="mb-1">MDRRMO Incident Desk</h4>
          <p class="mb-0 small opacity-75">Geotagged Photo Reporting System</p>
        </div>
        
        <div class="login-body">
          
          <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <?php echo htmlspecialchars($error_message); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          
          <form method="POST" action="" class="needs-validation" novalidate>
            <div class="form-floating mb-3">
              <input 
                type="text" 
                class="form-control" 
                id="username" 
                name="username" 
                placeholder="Username"
                required
                autocomplete="username"
              >
              <label for="username">
                <i class="bi bi-person me-2"></i>Username
              </label>
              <div class="invalid-feedback">
                Please enter your username.
              </div>
            </div>
            
            <div class="form-floating mb-4">
              <input 
                type="password" 
                class="form-control" 
                id="password" 
                name="password" 
                placeholder="Password"
                required
                autocomplete="current-password"
              >
              <label for="password">
                <i class="bi bi-lock me-2"></i>Password
              </label>
              <div class="invalid-feedback">
                Please enter your password.
              </div>
            </div>
            
            <div class="d-grid">
              <button type="submit" class="btn btn-danger btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Sign In
              </button>
            </div>
          </form>
          
          <div class="text-center mt-4">
            <small class="text-muted">
              Don't have an account?
              <a href="signup.php" class="text-decoration-none">Sign up here</a>
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <script src="scripts/login.js"></script>
  </body>
</html>
