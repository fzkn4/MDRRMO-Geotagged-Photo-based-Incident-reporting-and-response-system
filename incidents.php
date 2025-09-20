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
    <title>Incidents | MDRRMO Incident Reporting</title>

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

    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles/incidents.css" />
    
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
          
          <a href="incidents.php" class="nav-item active">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-light-emergency-on" data-unfilled="fi fi-rr-light-emergency-on"></i>
            </div>
            <div class="nav-text">Incidents</div>
            <div class="nav-badge warning" id="incidentCount">0</div>
          </a>
          
          <a href="map-view.php" class="nav-item">
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

      <main class="container-fluid py-4">
        <!-- Page Header -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h1 class="h3 mb-1">All Incidents</h1>
                <p class="text-muted mb-0">Comprehensive view of all reported incidents</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnRefresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <a href="index.php" class="btn btn-primary">
                  <i class="bi bi-plus-circle"></i> Add New Incident
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm stats-card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                      <i class="bi bi-flag text-primary fs-4"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="card-title text-muted mb-1">Total Incidents</h6>
                    <h4 class="mb-0" id="totalIncidents">0</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm stats-card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                      <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="card-title text-muted mb-1">New Incidents</h6>
                    <h4 class="mb-0" id="newIncidents">0</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm stats-card">
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
          
          <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm stats-card">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0">
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                      <i class="bi bi-truck text-info fs-4"></i>
                    </div>
                  </div>
                  <div class="flex-grow-1 ms-3">
                    <h6 class="card-title text-muted mb-1">Dispatched</h6>
                    <h4 class="mb-0" id="dispatchedIncidents">0</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters and Controls -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">
                    <i class="bi bi-funnel me-2"></i>Filters & Controls
                  </h5>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="btnClearFilters">
                      <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                    <button class="btn btn-sm btn-outline-success" id="btnExportAll">
                      <i class="bi bi-download"></i> Export All
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="btnClearAll">
                      <i class="bi bi-trash"></i> Clear All
                    </button>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="row g-3">
                  <!-- Search -->
                  <div class="col-md-4">
                    <label for="searchInput" class="form-label">Search</label>
                    <div class="input-group">
                      <span class="input-group-text">
                        <i class="bi bi-search"></i>
                      </span>
                      <input type="text" class="form-control" id="searchInput" placeholder="Search incidents...">
                    </div>
                  </div>

                  <!-- Status Filter -->
                  <div class="col-md-2">
                    <label for="filterStatus" class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                      <option value="All">All Statuses</option>
                      <option value="New">New</option>
                      <option value="Dispatched">Dispatched</option>
                      <option value="Resolved">Resolved</option>
                      <option value="Cancelled">Cancelled</option>
                    </select>
                  </div>

                  <!-- Type Filter -->
                  <div class="col-md-2">
                    <label for="filterType" class="form-label">Type</label>
                    <select id="filterType" class="form-select">
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

                  <!-- Severity Filter -->
                  <div class="col-md-2">
                    <label for="filterSeverity" class="form-label">Severity</label>
                    <select id="filterSeverity" class="form-select">
                      <option value="All">All Severities</option>
                      <option value="Low">Low</option>
                      <option value="Moderate">Moderate</option>
                      <option value="High">High</option>
                      <option value="Critical">Critical</option>
                    </select>
                  </div>

                  <!-- Sort By -->
                  <div class="col-md-2">
                    <label for="sortBy" class="form-label">Sort By</label>
                    <select id="sortBy" class="form-select">
                      <option value="newest">Newest First</option>
                      <option value="oldest">Oldest First</option>
                      <option value="severity">Severity</option>
                      <option value="status">Status</option>
                      <option value="type">Type</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Incidents List -->
        <div class="row">
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>Incidents List
                    <span class="badge bg-secondary ms-2" id="incidentCountBadge">0</span>
                  </h5>
                  <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="bulkSelectMode">
                      <label class="form-check-label" for="bulkSelectMode">
                        Bulk Select
                      </label>
                    </div>
                    <div class="btn-group" role="group" id="bulkActions" style="display: none;">
                      <button class="btn btn-sm btn-outline-primary" id="btnBulkDispatch">
                        <i class="bi bi-truck"></i> Dispatch Selected
                      </button>
                      <button class="btn btn-sm btn-outline-success" id="btnBulkResolve">
                        <i class="bi bi-check2-circle"></i> Resolve Selected
                      </button>
                      <button class="btn btn-sm btn-outline-danger" id="btnBulkDelete">
                        <i class="bi bi-trash"></i> Delete Selected
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body p-0">
                <!-- Loading State -->
                <div id="loadingState" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-2 text-muted">Loading incidents...</p>
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="text-center py-5" style="display: none;">
                  <i class="bi bi-inbox display-1 text-muted"></i>
                  <h5 class="mt-3">No incidents found</h5>
                  <p class="text-muted">Try adjusting your filters or add a new incident.</p>
                  <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Incident
                  </a>
                </div>

                <!-- Incidents Grid -->
                <div id="incidentsGrid" class="incidents-grid"></div>

                <!-- Pagination -->
                <nav aria-label="Incidents pagination" class="p-3" id="paginationContainer" style="display: none;">
                  <ul class="pagination justify-content-center mb-0" id="pagination">
                    <!-- Pagination items will be generated by JavaScript -->
                  </ul>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal fade" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="imageModalLabel">Incident Photo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <img id="modalImage" src="" alt="Incident Photo" class="img-fluid rounded">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="downloadModalImage">Download</button>
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

    <script src="scripts/incidents.js"></script>
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
