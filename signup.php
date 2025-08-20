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

    <link rel="stylesheet" href="style.css" />
    
    <style>
      .signup-container {
        min-height: 100vh;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        z-index: 20;
        overflow: hidden;
      }
      
      .signup-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: none;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        width: 100%;
        position: relative;
        z-index: 30;
      }
      
      .signup-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 2rem;
        text-align: center;
      }
      
      .signup-body {
        padding: 2rem;
      }
      
      .form-floating {
        margin-bottom: 1rem;
      }
      
      .btn-signup {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
      }
      
      .btn-signup:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
      }
      
      .alert {
        border-radius: 8px;
        border: none;
      }
      
      .form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
      }
      
      .floating-shapes {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
      }
      
      .shape {
        position: absolute;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        animation: float 6s ease-in-out infinite;
      }
      
      .shape:nth-child(1) {
        width: 80px;
        height: 80px;
        top: 20%;
        left: 10%;
        animation-delay: 0s;
      }
      
      .shape:nth-child(2) {
        width: 120px;
        height: 120px;
        top: 60%;
        right: 10%;
        animation-delay: 2s;
      }
      
      .shape:nth-child(3) {
        width: 60px;
        height: 60px;
        bottom: 20%;
        left: 20%;
        animation-delay: 4s;
      }
      
      @keyframes float {
        0%, 100% {
          transform: translateY(0px) rotate(0deg);
        }
        50% {
          transform: translateY(-20px) rotate(180deg);
        }
      }
      
      .ball {
        position: absolute;
        border-radius: 100%;
        opacity: 0.8;
        pointer-events: none;
        z-index: 10;
      }
      
      .role-selector {
        display: flex;
        gap: 10px;
        margin-bottom: 1rem;
      }
      
      .role-option {
        flex: 1;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
      }
      
      .role-option:hover {
        border-color: #dc3545;
        background: #fff5f5;
      }
      
      .role-option.selected {
        border-color: #dc3545;
        background: #dc3545;
        color: white;
      }
      
      .role-option i {
        font-size: 1.5rem;
        margin-bottom: 8px;
        display: block;
      }
      
      .password-strength {
        height: 4px;
        border-radius: 2px;
        margin-top: 5px;
        transition: all 0.3s ease;
      }
      
      .strength-weak { background: #dc3545; }
      .strength-fair { background: #fd7e14; }
      .strength-good { background: #ffc107; }
      .strength-strong { background: #198754; }
    </style>
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

    <script>
      // Role selection
      document.querySelectorAll('.role-option').forEach(option => {
        option.addEventListener('click', function() {
          document.querySelectorAll('.role-option').forEach(opt => opt.classList.remove('selected'));
          this.classList.add('selected');
          document.getElementById('selectedRole').value = this.dataset.role;
        });
      });
      
      // Password strength indicator
      document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrength');
        
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;
        
        strengthBar.className = 'password-strength';
        if (strength <= 2) {
          strengthBar.classList.add('strength-weak');
        } else if (strength === 3) {
          strengthBar.classList.add('strength-fair');
        } else if (strength === 4) {
          strengthBar.classList.add('strength-good');
        } else {
          strengthBar.classList.add('strength-strong');
        }
      });
      
      // Password confirmation validation
      document.getElementById('confirm_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (password !== confirmPassword) {
          this.setCustomValidity('Passwords do not match');
        } else {
          this.setCustomValidity('');
        }
      });
      
      // Form validation
      (function() {
        'use strict';
        window.addEventListener('load', function() {
          var forms = document.getElementsByClassName('needs-validation');
          var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
              if (form.checkValidity() === false) {
                event.preventDefault();
                event.stopPropagation();
              }
              form.classList.add('was-validated');
            }, false);
          });
        }, false);
      })();
      
      // Auto-hide alerts after 5 seconds
      setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
          var bsAlert = new bootstrap.Alert(alert);
          bsAlert.close();
        });
      }, 5000);
      
      // Floating balls effect
      const colors = ["#c82333"];
      
      const numBalls = 50;
      const balls = [];
      
      for (let i = 0; i < numBalls; i++) {
        let ball = document.createElement("div");
        ball.classList.add("ball");
        ball.style.background = colors[Math.floor(Math.random() * colors.length)];
        ball.style.left = `${Math.floor(Math.random() * 100)}vw`;
        ball.style.top = `${Math.floor(Math.random() * 100)}vh`;
        ball.style.transform = `scale(${Math.random()})`;
        ball.style.width = `${Math.random() * 0.3 + 0.1}em`;
        ball.style.height = ball.style.width;
        
        balls.push(ball);
        document.querySelector('.signup-container').appendChild(ball);
      }
      
      // Keyframes
      balls.forEach((el, i, ra) => {
        let to = {
          x: Math.random() * (i % 2 === 0 ? -11 : 11),
          y: Math.random() * 12
        };
      
        let anim = el.animate(
          [
            { transform: "translate(0, 0)" },
            { transform: `translate(${to.x}rem, ${to.y}rem)` }
          ],
          {
            duration: (Math.random() + 1) * 2000, // random duration
            direction: "alternate",
            fill: "both",
            iterations: Infinity,
            easing: "ease-in-out"
          }
        );
      });
    </script>
  </body>
</html>
