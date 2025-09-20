<?php
define('SECURE_ACCESS', true);
require_once 'auth.php';

// Check if user is logged in
checkLogin();

// Handle logout
if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Map View | MDRRMO Incident Reporting</title>

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

    <!-- Leaflet CSS -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />

    <!-- Leaflet MarkerCluster CSS -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css"
    />
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css"
    />

    <link rel="stylesheet" href="styles/dashboard.css" />
    <!-- Flaticon UIcons -->
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-straight/css/uicons-regular-straight.css'>
  </head>
  <body>
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <i class="bi bi-shield-exclamation me-2"></i>
          <span id="brandText">MDRRMO</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="bi bi-list"></i>
        </button>
      </div>
      
      <div class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title" id="navTitle">Navigation</div>
          
          <a href="index.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-apps" data-unfilled="fi fi-rr-apps"></i>
            </div>
            <div class="nav-text">Dashboard</div>
          </a>
          
          <a href="incidents.php" class="nav-item" id="incidentsLink">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-light-emergency-on" data-unfilled="fi fi-rr-light-emergency-on"></i>
            </div>
            <div class="nav-text">Incidents</div>
            <div class="nav-badge warning" id="incidentCount">0</div>
          </a>
          
          <a href="map-view.php" class="nav-item active">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-map-marker" data-unfilled="fi fi-rr-map-marker"></i>
            </div>
            <div class="nav-text">Map View</div>
          </a>
          
          <?php if (getUserRole() === 'admin'): ?>
          <a href="users.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-users" data-unfilled="fi fi-br-users"></i>
            </div>
            <div class="nav-text">Users</div>
            <div class="nav-badge primary" id="userCount">0</div>
          </a>
          
          <a href="#" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-rectangle-list" data-unfilled="fi fi-br-rectangle-list"></i>
            </div>
            <div class="nav-text">Reports</div>
          </a>
          
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
      <!-- Top Navigation -->
      <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
          <button class="btn btn-link d-lg-none" id="mobileMenuToggle">
            <i class="bi bi-list fs-4"></i>
          </button>
          
          <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo htmlspecialchars(getCurrentUser()); ?>
                <span class="badge bg-<?php echo getUserRole() === 'admin' ? 'danger' : 'primary'; ?> ms-1">
                  <?php echo ucfirst(getUserRole()); ?>
                </span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
              </ul>
            </div>
          </div>
        </div>
      </nav>

      <main class="map-view-page">
        <!-- Map Container -->
        <div id="mapContainer">
          <div id="map"></div>
          
          <!-- Back to Dashboard Button -->
          <a href="index.php" class="btn btn-outline-light position-fixed" style="top: 25px; left: 100px; z-index: 1000; border-radius: 25px; padding: 12px 24px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.3s ease; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 2px solid rgba(255, 255, 255, 0.3);">
            <i class="bi bi-arrow-left me-2"></i> Dashboard
          </a>
          
          <!-- Fullscreen Button -->
          <button class="fullscreen-btn" id="fullscreenBtn" title="Toggle Fullscreen">
            <i class="bi bi-arrows-fullscreen"></i>
          </button>
          
          <!-- Map Controls -->
          <div class="map-controls">
            <h6><i class="bi bi-funnel me-2"></i>Filters</h6>
            
            <div class="filter-group">
              <label for="filterType">Incident Type</label>
              <select id="filterType" class="form-select form-select-sm">
                <option value="All">All Types</option>
                <option value="Fire">Fire</option>
                <option value="Flood">Flood</option>
                <option value="Road Accident">Road Accident</option>
                <option value="Medical">Medical</option>
                <option value="Landslide">Landslide</option>
                <option value="Earthquake">Earthquake</option>
                <option value="Power Outage">Power Outage</option>
                <option value="Other">Other</option>
              </select>
            </div>
            
            <div class="filter-group">
              <label for="filterStatus">Status</label>
              <select id="filterStatus" class="form-select form-select-sm">
                <option value="All">All Statuses</option>
                <option value="New">New</option>
                <option value="Dispatched">Dispatched</option>
                <option value="Resolved">Resolved</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            
            <div class="filter-group">
              <label for="filterSeverity">Severity</label>
              <select id="filterSeverity" class="form-select form-select-sm">
                <option value="All">All Severities</option>
                <option value="Low">Low</option>
                <option value="Moderate">Moderate</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
            
            <div class="filter-group">
              <label for="filterDateRange">Date Range</label>
              <select id="filterDateRange" class="form-select form-select-sm">
                <option value="All">All Time</option>
                <option value="Today">Today</option>
                <option value="Week">This Week</option>
                <option value="Month">This Month</option>
                <option value="Year">This Year</option>
              </select>
            </div>
            
            <div class="stats-summary">
              <div class="small text-muted mb-1">Showing <span id="visibleCount">0</span> of <span id="totalCount">0</span> incidents</div>
              <div class="small">
                <span class="badge bg-secondary me-1" id="newCount">0</span>
                <span class="badge bg-primary me-1" id="dispatchedCount">0</span>
                <span class="badge bg-success me-1" id="resolvedCount">0</span>
                <span class="badge bg-danger me-1" id="cancelledCount">0</span>
              </div>
            </div>
          </div>
          
          <!-- Legend -->
          <div class="legend">
            <h6 class="mb-2">Severity Levels</h6>
            <div class="legend-item">
              <div class="legend-color" style="background: #28a745;"></div>
              <span>Low</span>
            </div>
            <div class="legend-item">
              <div class="legend-color" style="background: #ffc107;"></div>
              <span>Moderate</span>
            </div>
            <div class="legend-item">
              <div class="legend-color" style="background: #fd7e14;"></div>
              <span>High</span>
            </div>
            <div class="legend-item">
              <div class="legend-color" style="background: #dc3545;"></div>
              <span>Critical</span>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <!-- Leaflet JS -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>

    <!-- Leaflet MarkerCluster JS -->
    <script
      src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"
    ></script>

    <script src="scripts/map-view.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.sidebar .nav-item').forEach(function (item) {
          const icon = item.querySelector('.nav-icon i');
          if (!icon) return;
          const filled = icon.getAttribute('data-filled');
          const unfilled = icon.getAttribute('data-unfilled');
          if (!filled || !unfilled) return;
          icon.className = item.classList.contains('active') ? filled : unfilled;
        });
      });
    </script>
  </body>
</html>
