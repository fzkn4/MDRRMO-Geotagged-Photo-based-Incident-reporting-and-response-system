(() => {
  "use strict";

  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  const STORAGE_KEY = "mdrrmo_incidents_v1";

  // Map elements
  let map, markerClusterGroup;

  // Filter elements
  const filterType = $("#filterType");
  const filterStatus = $("#filterStatus");
  const filterSeverity = $("#filterSeverity");
  const filterDateRange = $("#filterDateRange");
  const btnRefresh = $("#btnRefresh");
  const fullscreenBtn = $("#fullscreenBtn");

  // Stats elements
  const visibleCount = $("#visibleCount");
  const totalCount = $("#totalCount");
  const newCount = $("#newCount");
  const dispatchedCount = $("#dispatchedCount");
  const resolvedCount = $("#resolvedCount");
  const cancelledCount = $("#cancelledCount");

  // Initialize
  initMap();
  loadAndDisplayIncidents();
  setupEventListeners();
  updateStats();

  function initMap() {
    // Initialize the map centered on Philippines
    map = L.map("map");

    // Add OpenStreetMap tiles
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    // Set default view to Philippines
    map.setView([12.8797, 121.774], 5);

    // Initialize marker cluster group
    markerClusterGroup = L.markerClusterGroup({
      chunkedLoading: true,
      maxClusterRadius: 50,
      spiderfyOnMaxZoom: true,
      showCoverageOnHover: false,
      zoomToBoundsOnClick: true,
      iconCreateFunction: function (cluster) {
        const count = cluster.getChildCount();
        let size, className;

        if (count < 10) {
          size = "small";
          className = "marker-cluster-small";
        } else if (count < 100) {
          size = "medium";
          className = "marker-cluster-medium";
        } else {
          size = "large";
          className = "marker-cluster-large";
        }

        return L.divIcon({
          html: `<div><span>${count}</span></div>`,
          className: `marker-cluster ${className}`,
          iconSize: L.point(40, 40),
        });
      },
    });

    map.addLayer(markerClusterGroup);
  }

  function loadAndDisplayIncidents() {
    const incidents = loadIncidents();
    displayIncidentsOnMap(incidents);
    updateStats();
  }

  function loadIncidents() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (_) {
      return [];
    }
  }

  function displayIncidentsOnMap(incidents) {
    // Clear existing markers
    markerClusterGroup.clearLayers();

    // Filter incidents based on current filter values
    const filteredIncidents = filterIncidents(incidents);

    // Add markers for each incident
    filteredIncidents.forEach((incident) => {
      if (incident.lat && incident.lng) {
        const marker = createIncidentMarker(incident);
        markerClusterGroup.addLayer(marker);
      }
    });

    // Update stats
    updateVisibleCount(filteredIncidents.length);

    // Fit map to show all markers if there are any
    if (filteredIncidents.length > 0) {
      const bounds = markerClusterGroup.getBounds();
      if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [20, 20] });
      }
    }
  }

  function filterIncidents(incidents) {
    return incidents.filter((incident) => {
      // Type filter
      if (filterType.value !== "All" && incident.type !== filterType.value) {
        return false;
      }

      // Status filter
      if (
        filterStatus.value !== "All" &&
        incident.status !== filterStatus.value
      ) {
        return false;
      }

      // Severity filter
      if (
        filterSeverity.value !== "All" &&
        incident.severity !== filterSeverity.value
      ) {
        return false;
      }

      // Date range filter
      if (filterDateRange.value !== "All") {
        const incidentDate = new Date(incident.createdAt);
        const now = new Date();

        switch (filterDateRange.value) {
          case "Today":
            const today = new Date(
              now.getFullYear(),
              now.getMonth(),
              now.getDate()
            );
            if (incidentDate < today) return false;
            break;
          case "Week":
            const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
            if (incidentDate < weekAgo) return false;
            break;
          case "Month":
            const monthAgo = new Date(
              now.getFullYear(),
              now.getMonth() - 1,
              now.getDate()
            );
            if (incidentDate < monthAgo) return false;
            break;
          case "Year":
            const yearAgo = new Date(now.getFullYear(), 0, 1);
            if (incidentDate < yearAgo) return false;
            break;
        }
      }

      return true;
    });
  }

  function createIncidentMarker(incident) {
    // Create custom icon based on incident type and severity
    const icon = createIncidentIcon(incident);

    // Create marker
    const marker = L.marker([incident.lat, incident.lng], { icon });

    // Create popup content
    const popupContent = createIncidentPopup(incident);
    marker.bindPopup(popupContent, {
      maxWidth: 300,
      className: "incident-popup",
    });

    // Add click event to center on marker without changing zoom
    marker.on("click", () => {
      map.panTo([incident.lat, incident.lng]);
    });

    return marker;
  }

  function createIncidentIcon(incident) {
    // Color based on severity
    const severityColors = {
      Low: "#28a745",
      Moderate: "#ffc107",
      High: "#fd7e14",
      Critical: "#dc3545",
    };

    const color = severityColors[incident.severity] || "#6c757d";

    // Icon based on incident type
    const typeIcons = {
      Fire: "🔥",
      Flood: "💧",
      "Road Accident": "🚗",
      Medical: "🏥",
      Landslide: "⛰️",
      Earthquake: "🌋",
      "Power Outage": "⚡",
      Other: "⚠️",
    };

    const emoji = typeIcons[incident.type] || "⚠️";

    return L.divIcon({
      html: `<div style="
        background-color: ${color};
        color: white;
        border: 2px solid white;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        cursor: pointer;
      ">${emoji}</div>`,
      className: "incident-marker",
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    });
  }

  function createIncidentPopup(incident) {
    const date = new Date(incident.createdAt).toLocaleString();
    const statusBadgeClass = getStatusBadgeClass(incident.status);
    const severityBadgeClass = getSeverityBadgeClass(incident.severity);

    return `
      <div class="incident-popup">
        <img src="${incident.photoDataUrl}" alt="${incident.type}" />
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="mb-0">${escapeHtml(incident.type)}</h6>
          <span class="badge ${statusBadgeClass}">${incident.status}</span>
        </div>
        <div class="mb-2">
          <span class="badge ${severityBadgeClass}">${incident.severity}</span>
          <small class="text-muted ms-2">${date}</small>
        </div>
        <p class="mb-2 small">${escapeHtml(incident.description)}</p>
        <div class="small text-muted">
          <i class="bi bi-geo-alt"></i> 
          ${incident.lat.toFixed(6)}, ${incident.lng.toFixed(6)}
        </div>
        <div class="mt-2 d-flex gap-1">
          <button class="btn btn-sm btn-outline-primary" onclick="viewIncidentOnDashboard('${
            incident.id
          }')">
            <i class="bi bi-eye"></i> View
          </button>
          <button class="btn btn-sm btn-outline-secondary" onclick="copyIncidentDetails('${
            incident.id
          }')">
            <i class="bi bi-clipboard"></i> Copy
          </button>
        </div>
      </div>
    `;
  }

  function getStatusBadgeClass(status) {
    const statusClasses = {
      New: "bg-secondary",
      Dispatched: "bg-primary",
      Resolved: "bg-success",
      Cancelled: "bg-danger",
    };
    return statusClasses[status] || "bg-secondary";
  }

  function getSeverityBadgeClass(severity) {
    const severityClasses = {
      Low: "bg-success",
      Moderate: "bg-warning",
      High: "bg-orange",
      Critical: "bg-danger",
    };
    return severityClasses[severity] || "bg-secondary";
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function updateStats() {
    const incidents = loadIncidents();

    // Update total count
    totalCount.textContent = incidents.length;

    // Update status counts
    newCount.textContent = incidents.filter((i) => i.status === "New").length;
    dispatchedCount.textContent = incidents.filter(
      (i) => i.status === "Dispatched"
    ).length;
    resolvedCount.textContent = incidents.filter(
      (i) => i.status === "Resolved"
    ).length;
    cancelledCount.textContent = incidents.filter(
      (i) => i.status === "Cancelled"
    ).length;

    // Update incident count in sidebar
    const incidentCount = document.getElementById("incidentCount");
    if (incidentCount) {
      incidentCount.textContent = incidents.length;
    }
  }

  function updateVisibleCount(count) {
    visibleCount.textContent = count;
  }

  function setupEventListeners() {
    // Filter change events
    filterType.addEventListener("change", () => {
      loadAndDisplayIncidents();
    });

    filterStatus.addEventListener("change", () => {
      loadAndDisplayIncidents();
    });

    filterSeverity.addEventListener("change", () => {
      loadAndDisplayIncidents();
    });

    filterDateRange.addEventListener("change", () => {
      loadAndDisplayIncidents();
    });

    // Refresh button
    btnRefresh.addEventListener("click", () => {
      loadAndDisplayIncidents();
    });

    // Fullscreen button
    fullscreenBtn.addEventListener("click", toggleFullscreen);

    // Map events
    map.on("zoomend", () => {
      // Update cluster visibility based on zoom level
      const zoom = map.getZoom();
      if (zoom < 10) {
        markerClusterGroup.options.maxClusterRadius = 100;
      } else if (zoom < 15) {
        markerClusterGroup.options.maxClusterRadius = 50;
      } else {
        markerClusterGroup.options.maxClusterRadius = 20;
      }
    });
  }

  function toggleFullscreen() {
    const mapContainer = document.getElementById("mapContainer");

    if (!document.fullscreenElement) {
      mapContainer
        .requestFullscreen()
        .then(() => {
          fullscreenBtn.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
          fullscreenBtn.title = "Exit Fullscreen";
        })
        .catch(() => {
          // Fullscreen not supported or denied
        });
    } else {
      document
        .exitFullscreen()
        .then(() => {
          fullscreenBtn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
          fullscreenBtn.title = "Toggle Fullscreen";
        })
        .catch(() => {
          // Exit fullscreen failed
        });
    }
  }

  // Global functions for popup buttons
  window.viewIncidentOnDashboard = function (incidentId) {
    // Redirect to dashboard with incident focus
    window.location.href = `index.php?focus=${incidentId}`;
  };

  window.copyIncidentDetails = async function (incidentId) {
    const incidents = loadIncidents();
    const incident = incidents.find((i) => i.id === incidentId);

    if (!incident) return;

    const text = [
      `Type: ${incident.type}`,
      `Severity: ${incident.severity}`,
      `Status: ${incident.status}`,
      `When: ${new Date(incident.createdAt).toLocaleString()}`,
      `Location: ${incident.lat.toFixed(6)}, ${incident.lng.toFixed(6)}`,
      `Description: ${incident.description}`,
    ].join("\n");

    try {
      await navigator.clipboard.writeText(text);
      alert("Incident details copied to clipboard");
    } catch (_) {
      alert("Copy failed");
    }
  };

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

    // Initialize sidebar state
    initializeSidebarState();
  });
})();
