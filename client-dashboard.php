<?php
define('SECURE_ACCESS', true);
require_once 'auth.php';

// Check if user is logged in
checkLogin();

if (getUserRole() === 'admin') {
    header('Location: admin-dashboard.php');
    exit();
}

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
    <title>Client Dashboard | MDRRMO Incident Reporting</title>

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
          
          <a href="client-dashboard.php" class="nav-item active">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-apps" data-unfilled="fi fi-rr-apps"></i>
            </div>
            <div class="nav-text">Dashboard</div>
          </a>
          
          <a href="client/incidents.php" class="nav-item" id="incidentsLink">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-light-emergency-on" data-unfilled="fi fi-rr-light-emergency-on"></i>
            </div>
            <div class="nav-text">My Incidents</div>
            <div class="nav-badge warning" id="incidentCount">0</div>
          </a>
          
          <a href="map-view.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-map-marker" data-unfilled="fi fi-rr-map-marker"></i>
            </div>
            <div class="nav-text">Map View</div>
          </a>
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
                <span class="badge bg-primary ms-1">Client</span>
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

      <main class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h1 class="h3 mb-1">Dashboard</h1>
                <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars(getCurrentUser()); ?>! Report and view incidents.</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnRefresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <a href="client/incidents.php" class="btn btn-primary">
                  <i class="bi bi-eye"></i> View My Incidents
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
          <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                      <i class="bi bi-flag text-primary fs-4"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="card-title text-muted mb-1">My Reported Incidents</h6>
                    <h4 class="mb-0" id="myIncidents">0</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                      <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="card-title text-muted mb-1">Pending</h6>
                    <h4 class="mb-0" id="pendingIncidents">0</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-xl-4 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                      <i class="bi bi-check-circle text-success fs-4"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="card-title text-muted mb-1">Resolved</h6>
                    <h4 class="mb-0" id="resolvedIncidents">0</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- My Incidents List -->
          <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">My Recent Incidents</h5>
                  <div class="d-flex gap-2">
                    <select id="filterStatus" class="form-select form-select-sm" style="width: 150px">
                      <option value="All" selected>All statuses</option>
                      <option value="New">New</option>
                      <option value="Dispatched">Dispatched</option>
                      <option value="Resolved">Resolved</option>
                      <option value="Cancelled">Cancelled</option>
                    </select>
                    <a href="client/incidents.php" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-eye"></i> View All
                    </a>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div id="incidentList" class="d-grid gap-3"></div>
              </div>
            </div>
          </div>

          <!-- New Incident Form -->
          <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0">
                <div class="d-flex align-items-center justify-content-between">
                  <h5 class="mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-plus-circle text-primary"></i> Report New Incident
                  </h5>
                  <span class="badge bg-secondary" id="clockBadge">--:--</span>
                </div>
              </div>
              <div class="card-body">
                <form id="incidentForm" class="needs-validation" novalidate>
                  <div class="row g-2">
                    <div class="col-12 col-md-6">
                      <label for="incidentType" class="form-label">Type</label>
                      <select id="incidentType" class="form-select" required>
                        <option value="" selected disabled>Choose...</option>
                        <option value="Fire">Fire</option>
                        <option value="Flood">Flood</option>
                        <option value="Road Accident">Road Accident</option>
                        <option value="Medical">Medical</option>
                        <option value="Landslide">Landslide</option>
                        <option value="Earthquake">Earthquake</option>
                        <option value="Power Outage">Power Outage</option>
                        <option value="Other">Other</option>
                      </select>
                      <div class="invalid-feedback">Please select a type.</div>
                    </div>
                    <div class="col-12 col-md-6">
                      <label for="severity" class="form-label">Severity</label>
                      <select id="severity" class="form-select" required>
                        <option value="" selected disabled>Choose...</option>
                        <option value="Low">Low</option>
                        <option value="Moderate">Moderate</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                      </select>
                      <div class="invalid-feedback">Please select severity.</div>
                    </div>
                    <div class="col-12">
                      <label for="description" class="form-label">Description</label>
                      <textarea id="description" class="form-control" rows="3" placeholder="Brief details (what/where/obstructions/injuries)" required></textarea>
                      <div class="invalid-feedback">Please enter a description.</div>
                    </div>
                    <div class="col-12">
                      <label class="form-label d-flex align-items-center gap-2"
                        ><i class="bi bi-camera"></i> Photo (geotag preferred)</label
                      >
                      <input
                        id="photo"
                        type="file"
                        class="form-control"
                        accept="image/*"
                        capture="environment"
                        required
                      />
                      <div class="invalid-feedback">Photo is required.</div>
                      <div class="form-text" id="photoMeta">Awaiting image...</div>
                      <div class="ratio ratio-16x9 mt-2 border rounded overflow-hidden bg-body" id="photoPreviewWrap">
                        <img id="photoPreview" alt="Preview" class="object-fit-cover w-100 h-100 d-none" />
                        <div class="d-flex align-items-center justify-content-center text-muted" id="photoPlaceholder">
                          <div class="text-center small">
                            <i class="bi bi-image fs-3 d-block mb-1"></i>
                            Photo preview
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="col-12">
                      <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label mb-0 d-flex align-items-center gap-2"
                          ><i class="bi bi-geo-alt"></i> Location</label
                        >
                        <div class="btn-group btn-group-sm" role="group">
                          <button type="button" id="btnUseMyLocation" class="btn btn-outline-primary">
                            <i class="bi bi-crosshair"></i> Use my location
                          </button>
                          <button type="button" id="btnClearLocation" class="btn btn-outline-secondary">
                            <i class="bi bi-x"></i>
                          </button>
                        </div>
                      </div>
                      <div class="row g-2 align-items-center mb-2">
                        <div class="col-6">
                          <input id="lat" class="form-control" placeholder="Latitude" readonly />
                        </div>
                        <div class="col-6">
                          <input id="lng" class="form-control" placeholder="Longitude" readonly />
                        </div>
                      </div>
                      <div id="locationNote" class="small text-muted mb-2">No location yet</div>
                      
                      <!-- Location Map -->
                      <div class="mt-2">
                        <div id="locationMap" class="rounded border" style="height: 200px; width: 100%;"></div>
                        <div class="form-text small mt-1">Click on the map to set location or use the buttons above</div>
                      </div>
                    </div>

                    <div class="col-12 d-grid gap-2 mt-2">
                      <button class="btn btn-danger" id="btnAddIncident" type="submit">
                        <i class="bi bi-plus-circle"></i> Report Incident
                      </button>
                      <button class="btn btn-outline-secondary" id="btnResetForm" type="button">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer class="container pb-4 small text-center text-muted">
        <span class="d-inline-flex align-items-center gap-1">
          <i class="bi bi-info-circle"></i> MDRRMO Geotagged Incident Reporting System
        </span>
      </footer>
    </div>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <!-- Leaflet CSS -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />

    <!-- Leaflet JS -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>

    <!-- EXIF reader -->
    <script src="https://cdn.jsdelivr.net/npm/exif-js@2.3.0/exif.min.js"></script>

    <script src="scripts/sidebar-counts.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/scripts/sidebar-counts.js')); ?>"></script>
    <script src="scripts/dashboard.js"></script>
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
