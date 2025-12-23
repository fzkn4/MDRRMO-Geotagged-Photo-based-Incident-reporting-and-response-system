(() => {
  "use strict";

  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  // DOM Elements
  const incidentsGrid = $("#incidentsGrid");
  const loadingState = $("#loadingState");
  const emptyState = $("#emptyState");
  const paginationContainer = $("#paginationContainer");
  const pagination = $("#pagination");

  // Filter elements
  const searchInput = $("#searchInput");
  const filterStatus = $("#filterStatus");
  const filterType = $("#filterType");
  const filterSeverity = $("#filterSeverity");
  const sortBy = $("#sortBy");

  // Control buttons
  const btnRefresh = $("#btnRefresh");
  const btnClearFilters = $("#btnClearFilters");
  const btnExportAll = $("#btnExportAll");
  const btnClearAll = $("#btnClearAll");

  // Bulk selection elements
  const bulkSelectMode = $("#bulkSelectMode");
  const bulkActions = $("#bulkActions");
  const btnBulkDispatch = $("#btnBulkDispatch");
  const btnBulkResolve = $("#btnBulkResolve");
  const btnBulkDelete = $("#btnBulkDelete");

  // Stats elements
  const totalIncidents = $("#totalIncidents");
  const newIncidents = $("#newIncidents");
  const resolvedIncidents = $("#resolvedIncidents");
  const dispatchedIncidents = $("#dispatchedIncidents");
  const incidentCountBadge = $("#incidentCountBadge");

  // Constants
  const STORAGE_KEY = "mdrrmo_incidents_v1";
  const ITEMS_PER_PAGE = 12;

  // State
  let allIncidents = [];
  let filteredIncidents = [];
  let currentPage = 1;
  let selectedIncidents = new Set();

  // Initialize
  init();

  function init() {
    loadIncidents();
    setupEventListeners();
    setupSidebar();
    setupDropdown();
    renderIncidents();
  }

  function loadIncidents() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      allIncidents = raw ? JSON.parse(raw) : [];
      filteredIncidents = [...allIncidents];
    } catch (error) {
      console.error("Error loading incidents:", error);
      allIncidents = [];
      filteredIncidents = [];
    }
  }

  function setupEventListeners() {
    // Filter events
    searchInput.addEventListener("input", debounce(applyFilters, 300));
    filterStatus.addEventListener("change", applyFilters);
    filterType.addEventListener("change", applyFilters);
    filterSeverity.addEventListener("change", applyFilters);
    sortBy.addEventListener("change", applyFilters);

    // Control events
    btnRefresh.addEventListener("click", refreshData);
    btnClearFilters.addEventListener("click", clearFilters);
    btnExportAll.addEventListener("click", exportAllIncidents);
    btnClearAll.addEventListener("click", clearAllIncidents);

    // Bulk selection events
    bulkSelectMode.addEventListener("change", toggleBulkSelectMode);
    btnBulkDispatch.addEventListener("click", () => bulkAction("dispatch"));
    btnBulkResolve.addEventListener("click", () => bulkAction("resolve"));
    btnBulkDelete.addEventListener("click", () => bulkAction("delete"));

    // Image modal events
    setupImageModal();
  }

  function setupSidebar() {
    // Sidebar functionality (reused from dashboard.js)
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("mainContent");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const mobileMenuToggle = document.getElementById("mobileMenuToggle");
    const sidebarOverlay = document.getElementById("sidebarOverlay");
    const brandText = document.getElementById("brandText");
    const navTitle = document.getElementById("navTitle");

    function toggleSidebar() {
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("expanded");
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

    function initializeSidebarState() {
      const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
      if (isCollapsed) {
        sidebar.classList.add("collapsed");
        mainContent.classList.add("expanded");
        brandText.style.display = "none";
        navTitle.style.display = "none";
      }
    }

    function toggleMobileMenu() {
      sidebar.classList.toggle("show");
      sidebarOverlay.classList.toggle("show");
    }

    sidebarToggle.addEventListener("click", toggleSidebar);
    mobileMenuToggle.addEventListener("click", toggleMobileMenu);
    sidebarOverlay.addEventListener("click", toggleMobileMenu);

    window.addEventListener("resize", () => {
      if (window.innerWidth > 768) {
        sidebar.classList.remove("show");
        sidebarOverlay.classList.remove("show");
      }
    });

    initializeSidebarState();
    
    // Use sidebar-counts.js if available, otherwise use local function
    if (window.updateSidebarCounts && typeof window.updateSidebarCounts === 'function') {
      // Wait a bit for sidebar-counts.js to be ready
      setTimeout(function() {
        window.updateSidebarCounts();
      }, 100);
    } else {
      updateSidebarCounts();
    }
  }

  function setupDropdown() {
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
        document
          .querySelectorAll(".dropdown-menu.show")
          .forEach(function (menu) {
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
        document
          .querySelectorAll(".dropdown-menu.show")
          .forEach(function (menu) {
            menu.classList.remove("show");
          });
      }
    });
  }

  function updateSidebarCounts() {
    // Delegate to sidebar-counts.js if available, otherwise update locally
    if (window.updateSidebarCounts && typeof window.updateSidebarCounts === 'function') {
      window.updateSidebarCounts();
    } else {
      // Fallback: update locally
      const incidentCount = document.getElementById("incidentCount");
      const userCount = document.getElementById("userCount");

      if (incidentCount) {
        const pendingCount = allIncidents.filter(inc => {
          const status = (inc.status || 'New').toLowerCase().trim();
          return status === 'new' || status === 'pending';
        }).length;
        incidentCount.textContent = pendingCount;
      }
      
      if (userCount) {
        fetch("users.php?action=count")
          .then((response) => response.json())
          .then((data) => {
            if (userCount) userCount.textContent = data.count || 0;
          })
          .catch(() => {
            if (userCount) userCount.textContent = "0";
          });
      }
    }
  }

  function applyFilters() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusFilter = filterStatus.value;
    const typeFilter = filterType.value;
    const severityFilter = filterSeverity.value;
    const sortOption = sortBy.value;

    // Filter incidents
    filteredIncidents = allIncidents.filter((incident) => {
      const matchesSearch =
        !searchTerm ||
        incident.type.toLowerCase().includes(searchTerm) ||
        incident.description.toLowerCase().includes(searchTerm);

      const matchesStatus =
        statusFilter === "All" || incident.status === statusFilter;
      const matchesType = typeFilter === "All" || incident.type === typeFilter;
      const matchesSeverity =
        severityFilter === "All" || incident.severity === severityFilter;

      return matchesSearch && matchesStatus && matchesType && matchesSeverity;
    });

    // Sort incidents
    sortIncidents(sortOption);

    // Reset to first page
    currentPage = 1;

    // Render
    renderIncidents();
    updateStats();
  }

  function sortIncidents(sortOption) {
    switch (sortOption) {
      case "newest":
        filteredIncidents.sort((a, b) => b.createdAt - a.createdAt);
        break;
      case "oldest":
        filteredIncidents.sort((a, b) => a.createdAt - b.createdAt);
        break;
      case "severity":
        const severityOrder = { Critical: 4, High: 3, Moderate: 2, Low: 1 };
        filteredIncidents.sort(
          (a, b) =>
            (severityOrder[b.severity] || 0) - (severityOrder[a.severity] || 0)
        );
        break;
      case "status":
        const statusOrder = {
          New: 1,
          Dispatched: 2,
          Resolved: 3,
          Cancelled: 4,
        };
        filteredIncidents.sort(
          (a, b) => (statusOrder[a.status] || 0) - (statusOrder[b.status] || 0)
        );
        break;
      case "type":
        filteredIncidents.sort((a, b) => a.type.localeCompare(b.type));
        break;
    }
  }

  function renderIncidents() {
    loadingState.style.display = "none";

    if (filteredIncidents.length === 0) {
      emptyState.style.display = "block";
      incidentsGrid.innerHTML = "";
      paginationContainer.style.display = "none";
      return;
    }

    emptyState.style.display = "none";

    // Calculate pagination
    const totalPages = Math.ceil(filteredIncidents.length / ITEMS_PER_PAGE);
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;
    const pageIncidents = filteredIncidents.slice(startIndex, endIndex);

    // Render incidents
    incidentsGrid.innerHTML = pageIncidents
      .map((incident) => renderIncidentCard(incident))
      .join("");

    // Render pagination
    if (totalPages > 1) {
      renderPagination(totalPages);
      paginationContainer.style.display = "block";
    } else {
      paginationContainer.style.display = "none";
    }

    // Update count badge
    incidentCountBadge.textContent = filteredIncidents.length;

    // Setup incident card event listeners
    setupIncidentCardListeners();
  }

  function renderIncidentCard(incident) {
    const date = new Date(incident.createdAt).toLocaleString();
    const timeAgo = getTimeAgo(incident.createdAt);
    const gps =
      incident.lat != null && incident.lng != null
        ? `${incident.lat.toFixed(6)}, ${incident.lng.toFixed(6)}`
        : "No GPS";

    return `
      <div class="incident-card-enhanced" data-incident-id="${incident.id}">
        <input type="checkbox" class="bulk-select-checkbox" data-incident-id="${
          incident.id
        }" style="display: none;">
        <div class="incident-card-header">
          <div class="incident-card-title">
            <div class="incident-type-icon ${getTypeClass(incident.type)}">
              <i class="bi ${getTypeIcon(incident.type)}"></i>
            </div>
            <div class="incident-title-text">
              <h6>${escapeHtml(incident.type)}</h6>
              <div class="incident-meta">
                <span class="badge ${getStatusBadgeClass(incident.status)}">${
      incident.status
    }</span>
                <span class="badge ${getSeverityBadgeClass(
                  incident.severity
                )}">${incident.severity}</span>
                <small class="text-muted">${timeAgo}</small>
              </div>
            </div>
          </div>
        </div>
        
        <div class="incident-card-body">
          ${
            incident.photoDataUrl
              ? `
            <div class="incident-image-container" data-image="${incident.photoDataUrl}" data-title="${incident.type} - ${incident.severity}">
              <img src="${incident.photoDataUrl}" alt="${incident.type} photo">
              <div class="incident-image-overlay">
                <i class="bi bi-zoom-in"></i>
              </div>
            </div>
          `
              : ""
          }
          
          <p class="incident-description">${escapeHtml(
            incident.description
          )}</p>
          
          <div class="incident-details">
            <div class="incident-detail-item">
              <i class="bi bi-calendar3"></i>
              <span>${date}</span>
            </div>
            <div class="incident-detail-item">
              <i class="bi bi-geo-alt"></i>
              <span>${gps}</span>
            </div>
          </div>
          
          <div class="incident-actions">
            <button class="btn btn-outline-secondary incident-action-btn" data-action="view" data-id="${
              incident.id
            }">
              <i class="bi bi-map"></i> Map
            </button>
            <button class="btn btn-outline-primary incident-action-btn" data-action="dispatch" data-id="${
              incident.id
            }">
              <i class="bi bi-truck"></i> Dispatch
            </button>
            <button class="btn btn-outline-success incident-action-btn" data-action="resolve" data-id="${
              incident.id
            }">
              <i class="bi bi-check2-circle"></i> Resolve
            </button>
            <button class="btn btn-outline-danger incident-action-btn" data-action="cancel" data-id="${
              incident.id
            }">
              <i class="bi bi-x-circle"></i> Cancel
            </button>
            <button class="btn btn-outline-dark incident-action-btn" data-action="download" data-id="${
              incident.id
            }">
              <i class="bi bi-download"></i> Photo
            </button>
            <button class="btn btn-outline-danger incident-action-btn" data-action="delete" data-id="${
              incident.id
            }">
              <i class="bi bi-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>
    `;
  }

  function renderPagination(totalPages) {
    let paginationHTML = "";

    // Previous button
    paginationHTML += `
      <li class="page-item ${currentPage === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${
          currentPage - 1
        }">Previous</a>
      </li>
    `;

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
      paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
      if (startPage > 2) {
        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      paginationHTML += `
        <li class="page-item ${i === currentPage ? "active" : ""}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
      paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    // Next button
    paginationHTML += `
      <li class="page-item ${currentPage === totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
      </li>
    `;

    pagination.innerHTML = paginationHTML;

    // Add event listeners to pagination links
    pagination.addEventListener("click", (e) => {
      e.preventDefault();
      if (
        e.target.classList.contains("page-link") &&
        !e.target.parentElement.classList.contains("disabled")
      ) {
        const page = parseInt(e.target.dataset.page);
        if (page && page !== currentPage) {
          currentPage = page;
          renderIncidents();
        }
      }
    });
  }

  function setupIncidentCardListeners() {
    // Action buttons
    incidentsGrid.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;

      const id = btn.getAttribute("data-id");
      const action = btn.getAttribute("data-action");

      switch (action) {
        case "view":
          viewOnMap(id);
          break;
        case "dispatch":
          updateStatus(id, "Dispatched");
          break;
        case "resolve":
          updateStatus(id, "Resolved");
          break;
        case "cancel":
          updateStatus(id, "Cancelled");
          break;
        case "delete":
          deleteIncident(id);
          break;
        case "download":
          downloadPhoto(id);
          break;
      }
    });

    // Image clicks
    incidentsGrid.addEventListener("click", (e) => {
      const imageContainer = e.target.closest(".incident-image-container");
      if (imageContainer) {
        const imageSrc = imageContainer.getAttribute("data-image");
        const imageTitle = imageContainer.getAttribute("data-title");
        showImageModal(imageSrc, imageTitle);
      }
    });

    // Bulk selection checkboxes
    incidentsGrid.addEventListener("change", (e) => {
      if (e.target.classList.contains("bulk-select-checkbox")) {
        const incidentId = e.target.getAttribute("data-incident-id");
        const card = e.target.closest(".incident-card-enhanced");

        if (e.target.checked) {
          selectedIncidents.add(incidentId);
          card.classList.add("bulk-selected");
        } else {
          selectedIncidents.delete(incidentId);
          card.classList.remove("bulk-selected");
        }

        updateBulkActionsVisibility();
      }
    });
  }

  function toggleBulkSelectMode() {
    const isEnabled = bulkSelectMode.checked;
    const checkboxes = $$(".bulk-select-checkbox");
    const cards = $$(".incident-card-enhanced");

    checkboxes.forEach((checkbox) => {
      checkbox.style.display = isEnabled ? "block" : "none";
    });

    if (!isEnabled) {
      selectedIncidents.clear();
      cards.forEach((card) => card.classList.remove("bulk-selected"));
    }

    updateBulkActionsVisibility();
  }

  function updateBulkActionsVisibility() {
    bulkActions.style.display = selectedIncidents.size > 0 ? "flex" : "none";
  }

  function bulkAction(action) {
    if (selectedIncidents.size === 0) return;

    const actionText = action === "delete" ? "delete" : action;
    if (
      !confirm(
        `Are you sure you want to ${actionText} ${selectedIncidents.size} selected incident(s)?`
      )
    ) {
      return;
    }

    selectedIncidents.forEach((incidentId) => {
      const incident = allIncidents.find((inc) => inc.id === incidentId);
      if (incident) {
        switch (action) {
          case "dispatch":
            incident.status = "Dispatched";
            break;
          case "resolve":
            incident.status = "Resolved";
            break;
          case "delete":
            const index = allIncidents.findIndex(
              (inc) => inc.id === incidentId
            );
            if (index > -1) allIncidents.splice(index, 1);
            break;
        }
      }
    });

    saveIncidents();
    selectedIncidents.clear();
    bulkSelectMode.checked = false;
    toggleBulkSelectMode();
    applyFilters();
  }

  function updateStatus(id, status) {
    const incident = allIncidents.find((inc) => inc.id === id);
    if (incident) {
      incident.status = status;
      saveIncidents();
      applyFilters();
    }
  }

  function deleteIncident(id) {
    if (!confirm("Are you sure you want to delete this incident?")) return;

    const index = allIncidents.findIndex((inc) => inc.id === id);
    if (index > -1) {
      allIncidents.splice(index, 1);
      saveIncidents();
      applyFilters();
    }
  }

  function downloadPhoto(id) {
    const incident = allIncidents.find((inc) => inc.id === id);
    if (incident && incident.photoDataUrl) {
      const a = document.createElement("a");
      a.href = incident.photoDataUrl;
      a.download = `${incident.type}_${id}.jpg`;
      a.click();
    }
  }

  function viewOnMap(id) {
    const incident = allIncidents.find((inc) => inc.id === id);
    if (incident && incident.lat != null && incident.lng != null) {
      // Redirect to map view with incident coordinates
      window.location.href = `map-view.php?lat=${incident.lat}&lng=${incident.lng}&zoom=15`;
    } else {
      alert("No GPS coordinates available for this incident.");
    }
  }

  function showImageModal(imageSrc, imageTitle) {
    const modalImage = document.getElementById("modalImage");
    const modalLabel = document.getElementById("imageModalLabel");

    modalImage.src = imageSrc;
    modalLabel.textContent = imageTitle;

    const modal = new bootstrap.Modal(document.getElementById("imageModal"));
    modal.show();
  }

  function setupImageModal() {
    document
      .getElementById("downloadModalImage")
      .addEventListener("click", () => {
        const imageSrc = document.getElementById("modalImage").src;
        const a = document.createElement("a");
        a.href = imageSrc;
        a.download = `incident_photo_${Date.now()}.jpg`;
        a.click();
      });
  }

  function clearFilters() {
    searchInput.value = "";
    filterStatus.value = "All";
    filterType.value = "All";
    filterSeverity.value = "All";
    sortBy.value = "newest";
    applyFilters();
  }

  function refreshData() {
    loadIncidents();
    applyFilters();
    updateSidebarCounts();
  }

  function exportAllIncidents() {
    const blob = new Blob([JSON.stringify(allIncidents, null, 2)], {
      type: "application/json",
    });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = `incidents_${new Date()
      .toISOString()
      .replace(/[:.]/g, "-")}.json`;
    a.click();
    URL.revokeObjectURL(a.href);
  }

  function clearAllIncidents() {
    if (allIncidents.length === 0) return;
    if (
      !confirm(
        "Are you sure you want to clear ALL incidents? This action cannot be undone."
      )
    )
      return;

    allIncidents = [];
    saveIncidents();
    applyFilters();
    updateSidebarCounts();
  }

  function saveIncidents() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(allIncidents));
  }

  function updateStats() {
    const stats = {
      total: allIncidents.length,
      new: allIncidents.filter((inc) => inc.status === "New").length,
      resolved: allIncidents.filter((inc) => inc.status === "Resolved").length,
      dispatched: allIncidents.filter((inc) => inc.status === "Dispatched")
        .length,
    };

    if (totalIncidents) totalIncidents.textContent = stats.total;
    if (newIncidents) newIncidents.textContent = stats.new;
    if (resolvedIncidents) resolvedIncidents.textContent = stats.resolved;
    if (dispatchedIncidents) dispatchedIncidents.textContent = stats.dispatched;
  }

  // Utility functions
  function getTypeIcon(type) {
    const icons = {
      Fire: "bi-fire",
      Flood: "bi-droplet",
      "Road Accident": "bi-car-front",
      Medical: "bi-heart-pulse",
      Landslide: "bi-triangle",
      Earthquake: "bi-activity",
      "Power Outage": "bi-lightning",
      Other: "bi-exclamation-octagon",
    };
    return icons[type] || "bi-exclamation-octagon";
  }

  function getTypeClass(type) {
    return type.toLowerCase().replace(/\s+/g, "-");
  }

  function getStatusBadgeClass(status) {
    const classes = {
      New: "status-badge-new",
      Dispatched: "status-badge-dispatched",
      Resolved: "status-badge-resolved",
      Cancelled: "status-badge-cancelled",
    };
    return classes[status] || "status-badge-new";
  }

  function getSeverityBadgeClass(severity) {
    const classes = {
      Low: "severity-badge-low",
      Moderate: "severity-badge-moderate",
      High: "severity-badge-high",
      Critical: "severity-badge-critical",
    };
    return classes[severity] || "severity-badge-low";
  }

  function getTimeAgo(timestamp) {
    const now = Date.now();
    const diff = now - timestamp;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return "Just now";
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
  }

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Listen for storage changes (for real-time updates when incidents are added from dashboard)
  window.addEventListener("storage", (e) => {
    if (e.key === STORAGE_KEY) {
      refreshData();
    }
  });

  // Listen for custom events from dashboard
  window.addEventListener("incidentAdded", () => {
    refreshData();
  });
})();
