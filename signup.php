<?php
define('SECURE_ACCESS', true);
require_once 'auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $organization = trim($_POST['organization'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($role)) {
        $errors[] = 'Please select a role.';
    }
    
    if (empty($organization)) {
        $errors[] = 'Organization is required.';
    }
    
    // Check if username already exists
    if (empty($errors) && userExists($username)) {
        $errors[] = 'Username already exists. Please choose a different one.';
    }
    
    // Check if email already exists
    if (empty($errors) && emailExists($email)) {
        $errors[] = 'Email already exists. Please use a different email address.';
    }
    
    if (empty($errors)) {
        // Create user account
        $user_data = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $full_name,
            'role' => $role,
            'organization' => $organization,
            'phone' => $phone,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];
        
        if (createUser($user_data)) {
            $success_message = 'Account created successfully! You can now log in.';
        } else {
            $errors[] = 'Failed to create account. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MDRRMO | Sign Up</title>

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

    <link rel="stylesheet" href="styles/signup.css" />
  </head>
  <body style="overflow: hidden;">
    <!-- Floating background shapes -->
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>

    <div class="signup-container">
      <div class="card signup-card">
        <div class="signup-header">
          <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
            <i class="bi bi-shield-exclamation fs-1"></i>
          </div>
          <h4 class="mb-1">MDRRMO Incident Desk</h4>
          <p class="mb-0 small opacity-75">Create New Account</p>
        </div>
        
        <div class="signup-body">
          
          <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <?php echo $error_message; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>
          
          <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check-circle me-2"></i>
              <?php echo $success_message; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <div class="text-center mt-3">
              <a href="login.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Go to Login
              </a>
            </div>
          <?php else: ?>
          
          <form method="POST" action="" class="needs-validation" novalidate>
            <!-- Role Selection -->
            <div class="mb-3">
              <label class="form-label fw-bold">Select Your Role</label>
              <div class="role-selector">
                <div class="role-option" data-role="client">
                  <i class="bi bi-person"></i>
                  <div class="fw-bold">Client</div>
                  <small>Report incidents</small>
                </div>
                <div class="role-option" data-role="admin">
                  <i class="bi bi-shield-check"></i>
                  <div class="fw-bold">Admin</div>
                  <small>Manage system</small>
                </div>
              </div>
              <input type="hidden" name="role" id="selectedRole" required>
              <div class="invalid-feedback">Please select a role.</div>
            </div>
            
            <div class="row g-2">
              <div class="col-12 col-md-6">
                <div class="form-floating">
                  <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="Username"
                    required
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                  >
                  <label for="username">
                    <i class="bi bi-person me-2"></i>Username
                  </label>
                  <div class="invalid-feedback">Please enter a username.</div>
                </div>
              </div>
              
              <div class="col-12 col-md-6">
                <div class="form-floating">
                  <input 
                    type="email" 
                    class="form-control" 
                    id="email" 
                    name="email" 
                    placeholder="Email"
                    required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                  >
                  <label for="email">
                    <i class="bi bi-envelope me-2"></i>Email
                  </label>
                  <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
              </div>
              
              <div class="col-12">
                <div class="form-floating">
                  <input 
                    type="text" 
                    class="form-control" 
                    id="full_name" 
                    name="full_name" 
                    placeholder="Full Name"
                    required
                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                  >
                  <label for="full_name">
                    <i class="bi bi-person-badge me-2"></i>Full Name
                  </label>
                  <div class="invalid-feedback">Please enter your full name.</div>
                </div>
              </div>
              
              <div class="col-12">
                <div class="form-floating">
                  <input 
                    type="text" 
                    class="form-control" 
                    id="organization" 
                    name="organization" 
                    placeholder="Organization"
                    required
                    value="<?php echo htmlspecialchars($_POST['organization'] ?? ''); ?>"
                  >
                  <label for="organization">
                    <i class="bi bi-building me-2"></i>Organization
                  </label>
                  <div class="invalid-feedback">Please enter your organization.</div>
                </div>
              </div>
              
              <div class="col-12">
                <div class="form-floating">
                  <input 
                    type="tel" 
                    class="form-control" 
                    id="phone" 
                    name="phone" 
                    placeholder="Phone Number"
                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                  >
                  <label for="phone">
                    <i class="bi bi-telephone me-2"></i>Phone Number (Optional)
                  </label>
                </div>
              </div>
              
              <div class="col-12 col-md-6">
                <div class="form-floating">
                  <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="Password"
                    required
                  >
                  <label for="password">
                    <i class="bi bi-lock me-2"></i>Password
                  </label>
                  <div class="invalid-feedback">Please enter a password.</div>
                  <div class="password-strength" id="passwordStrength"></div>
                </div>
              </div>
              
              <div class="col-12 col-md-6">
                <div class="form-floating">
                  <input 
                    type="password" 
                    class="form-control" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Confirm Password"
                    required
                  >
                  <label for="confirm_password">
                    <i class="bi bi-lock-fill me-2"></i>Confirm Password
                  </label>
                  <div class="invalid-feedback">Please confirm your password.</div>
                </div>
              </div>
            </div>
            
            <div class="d-grid gap-2 mt-4">
              <button type="submit" class="btn btn-danger btn-signup">
                <i class="bi bi-person-plus me-2"></i>
                Create Account
              </button>
            </div>
          </form>
          
          <div class="text-center mt-4">
            <small class="text-muted">
              Already have an account? 
              <a href="login.php" class="text-decoration-none">Sign in here</a>
            </small>
          </div>
          
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <script src="scripts/signup.js"></script>
  </body>
</html>
