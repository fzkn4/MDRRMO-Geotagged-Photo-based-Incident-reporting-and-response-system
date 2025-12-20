// Initialize Bootstrap dropdowns
document.addEventListener("DOMContentLoaded", function () {
  // Manual dropdown functionality - use multiple selectors to ensure we find the dropdown
  const dropdownSelectors = [
    ".dropdown-toggle",
    "#mainContent nav .nav-link.dropdown-toggle",
    "#mainContent > nav > div > div.navbar-nav > div.nav-item > a.nav-link",
  ];

  let dropdownToggles = [];
  dropdownSelectors.forEach((selector) => {
    const elements = document.querySelectorAll(selector);
    elements.forEach((el) => {
      if (!dropdownToggles.includes(el)) {
        dropdownToggles.push(el);
      }
    });
  });

  // If still not found, try the specific path (accounting for button)
  if (dropdownToggles.length === 0) {
    const mainContent = document.getElementById("mainContent");
    if (mainContent) {
      const nav = mainContent.querySelector("nav");
      if (nav) {
        const navLink = nav.querySelector(".nav-link.dropdown-toggle");
        if (navLink) {
          dropdownToggles.push(navLink);
        }
      }
    }
  }

  dropdownToggles.forEach(function (toggle) {
    // Remove any existing listeners by cloning
    const newToggle = toggle.cloneNode(true);
    toggle.parentNode.replaceChild(newToggle, toggle);

    newToggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Find the dropdown menu (ul element) within the same dropdown container
      const dropdownContainer =
        this.closest(".dropdown") || this.closest(".nav-item.dropdown");
      if (!dropdownContainer) {
        return;
      }

      const dropdownMenu = dropdownContainer.querySelector(".dropdown-menu");
      if (!dropdownMenu) {
        return;
      }

      // Check current state
      const isOpen = dropdownMenu.classList.contains("show");

      // Close all other dropdowns first
      document.querySelectorAll(".dropdown-menu.show").forEach(function (menu) {
        if (menu !== dropdownMenu) {
          menu.classList.remove("show");
        }
      });

      // Toggle current dropdown
      if (!isOpen) {
        dropdownMenu.classList.add("show");
      } else {
        dropdownMenu.classList.remove("show");
      }
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
