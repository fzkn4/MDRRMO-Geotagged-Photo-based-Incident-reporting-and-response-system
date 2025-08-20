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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (authenticateUser($username, $password)) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        header('Location: index.php');
        exit();
    } else {
        $error_message = 'Invalid username or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MDRRMO | Login</title>

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
      .login-container {
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
      
      .login-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        filter: blur(20px);
        opacity: 0.8;
        z-index: -1;
      }
      
      .login-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: none;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        max-width: 400px;
        width: 100%;
        position: relative;
        z-index: 30;
      }
      
      .login-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border-radius: 15px 15px 0 0;
        padding: 2rem;
        text-align: center;
      }
      
      .login-body {
        padding: 2rem;
      }
      
      .form-floating {
        margin-bottom: 1rem;
      }
      
      .btn-login {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
      }
      
      .btn-login:hover {
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
    </style>
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
            <i class="bi bi-shield-exclamation fs-1"></i>
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
              <i class="bi bi-info-circle me-1"></i>
              Demo Credentials:<br>
              admin / mdrrmo2024<br>
              client / client2024
            </small>
            <div class="mt-3">
              <small class="text-muted">
                Don't have an account? 
                <a href="signup.php" class="text-decoration-none">Sign up here</a>
              </small>
            </div>
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

    <script>
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
        document.querySelector('.login-container').appendChild(ball);
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
