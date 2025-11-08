document.addEventListener("DOMContentLoaded", function () {
  // Role selection
  document.querySelectorAll(".role-option").forEach((option) => {
    option.addEventListener("click", function () {
      document
        .querySelectorAll(".role-option")
        .forEach((opt) => opt.classList.remove("selected"));
      this.classList.add("selected");
      const selectedRole = document.getElementById("selectedRole");
      if (selectedRole) {
        selectedRole.value = this.dataset.role;
      }
    });
  });

  // Password strength indicator
  const passwordInput = document.getElementById("password");
  if (passwordInput) {
    passwordInput.addEventListener("input", function () {
      const password = this.value;
      const strengthBar = document.getElementById("passwordStrength");
      if (!strengthBar) return;

      let strength = 0;
      if (password.length >= 6) strength++;
      if (password.match(/[a-z]/)) strength++;
      if (password.match(/[A-Z]/)) strength++;
      if (password.match(/[0-9]/)) strength++;
      if (password.match(/[^a-zA-Z0-9]/)) strength++;

      strengthBar.className = "password-strength";
      if (strength <= 2) {
        strengthBar.classList.add("strength-weak");
      } else if (strength === 3) {
        strengthBar.classList.add("strength-fair");
      } else if (strength === 4) {
        strengthBar.classList.add("strength-good");
      } else {
        strengthBar.classList.add("strength-strong");
      }
    });
  }

  // Password confirmation validation
  const confirmPasswordInput = document.getElementById("confirm_password");
  if (confirmPasswordInput && passwordInput) {
    confirmPasswordInput.addEventListener("input", function () {
      const password = passwordInput.value;
      const confirmPassword = this.value;

      if (password !== confirmPassword) {
        this.setCustomValidity("Passwords do not match");
      } else {
        this.setCustomValidity("");
      }
    });
  }

  // Form validation
  (function () {
    "use strict";
    window.addEventListener(
      "load",
      function () {
        var forms = document.getElementsByClassName("needs-validation");
        Array.prototype.filter.call(forms, function (form) {
          form.addEventListener(
            "submit",
            function (event) {
              if (form.checkValidity() === false) {
                event.preventDefault();
                event.stopPropagation();
              }
              form.classList.add("was-validated");
            },
            false
          );
        });
      },
      false
    );
  })();

  // Auto-hide alerts after 5 seconds
  setTimeout(function () {
    var alerts = document.querySelectorAll(".alert");
    alerts.forEach(function (alert) {
      var bsAlert = new bootstrap.Alert(alert);
      bsAlert.close();
    });
  }, 5000);

  // Floating balls effect
  const colors = ["#c82333"];

  const numBalls = 50;
  const balls = [];

  const signupContainer = document.querySelector(".signup-container");
  if (signupContainer) {
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
      signupContainer.appendChild(ball);
    }

    // Keyframes
    balls.forEach((el, i) => {
      let to = {
        x: Math.random() * (i % 2 === 0 ? -11 : 11),
        y: Math.random() * 12,
      };

      el.animate(
        [
          { transform: "translate(0, 0)" },
          { transform: `translate(${to.x}rem, ${to.y}rem)` },
        ],
        {
          duration: (Math.random() + 1) * 2000, // random duration
          direction: "alternate",
          fill: "both",
          iterations: Infinity,
          easing: "ease-in-out",
        }
      );
    });
  }
});
