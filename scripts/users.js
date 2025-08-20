// Initialize Bootstrap dropdowns
document.addEventListener("DOMContentLoaded", function () {
  // Manual dropdown functionality
  document.querySelectorAll(".dropdown-toggle").forEach(function (toggle) {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Find the dropdown menu (ul element) within the same dropdown container
      const dropdownContainer = this.closest(".dropdown");
      const dropdownMenu = dropdownContainer.querySelector(".dropdown-menu");

      // Force remove any Bootstrap classes and check our show class
      dropdownMenu.classList.remove("show");
      const isOpen = dropdownMenu.classList.contains("show");

      console.log("Dropdown container:", dropdownContainer);
      console.log("Dropdown menu:", dropdownMenu);
      console.log("Is open after cleanup:", isOpen);

      // Close all other dropdowns
      document.querySelectorAll(".dropdown-menu.show").forEach(function (menu) {
        menu.classList.remove("show");
      });

      // Toggle current dropdown
      if (!isOpen) {
        dropdownMenu.classList.add("show");
        console.log("Added show class to dropdown menu");
      } else {
        dropdownMenu.classList.remove("show");
        console.log("Removed show class from dropdown menu");
      }

      console.log("Dropdown clicked, menu visible:", !isOpen);
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown-menu.show").forEach(function (menu) {
        menu.classList.remove("show");
      });
    }
  });

  // Sidebar functionality
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("mainContent");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const mobileMenuToggle = document.getElementById("mobileMenuToggle");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const brandText = document.getElementById("brandText");
  const navTitle = document.getElementById("navTitle");

  // Toggle sidebar
  function toggleSidebar() {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");

    // Store sidebar state in localStorage
    const isCollapsed = sidebar.classList.contains("collapsed");
    localStorage.setItem("sidebarCollapsed", isCollapsed);

    if (isCollapsed) {
      brandText.style.display = "none";
      navTitle.style.display = "none";
    } else {
      brandText.style.display = "inline";
      navTitle.style.display = "block";
    }
  }

  // Initialize sidebar state from localStorage
  function initializeSidebarState() {
    const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (isCollapsed) {
      sidebar.classList.add("collapsed");
      mainContent.classList.add("expanded");
      brandText.style.display = "none";
      navTitle.style.display = "none";
    } else {
      // Default to expanded state
      sidebar.classList.remove("collapsed");
      mainContent.classList.remove("expanded");
      brandText.style.display = "inline";
      navTitle.style.display = "block";
    }
  }

  // Mobile menu toggle
  function toggleMobileMenu() {
    sidebar.classList.toggle("show");
    sidebarOverlay.classList.toggle("show");
  }

  // Event listeners
  sidebarToggle.addEventListener("click", toggleSidebar);
  mobileMenuToggle.addEventListener("click", toggleMobileMenu);
  sidebarOverlay.addEventListener("click", toggleMobileMenu);

  // Close mobile menu on window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      sidebar.classList.remove("show");
      sidebarOverlay.classList.remove("show");
    }
  });

  // Update user count
  function updateUserCount() {
    const userCount = document.getElementById("userCount");
    if (userCount) {
      const userRows = document.querySelectorAll("tbody tr");
      userCount.textContent = userRows.length;
    }
  }

  // Initialize sidebar state and counts
  initializeSidebarState();
  updateUserCount();

  // Refresh button
  document.getElementById("btnRefresh").addEventListener("click", function () {
    location.reload();
  });
});
