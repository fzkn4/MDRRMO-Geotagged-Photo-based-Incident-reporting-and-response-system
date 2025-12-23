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
          
          <a href="#" class="nav-item disabled" style="opacity: 0.5; cursor: not-allowed;" title="Coming Soon">
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
                <?php if (getUserRole() === 'admin'): ?>
                  <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncidentModal">
                    <i class="bi bi-plus-circle"></i> Add New Incident
                  </button>
                <?php else: ?>
                  <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Incident
                  </a>
                <?php endif; ?>
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

    <!-- Add Incident Modal (Admin Only) -->
    <?php if (getUserRole() === 'admin'): ?>
    <div class="modal fade" id="addIncidentModal" tabindex="-1" aria-labelledby="addIncidentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="overflow: hidden;">
          <!-- Modal Header with Red Theme -->
          <div class="modal-header border-0" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 1.5rem 1.75rem;">
            <div class="w-100 pe-3">
              <h5 class="modal-title text-white fw-bold mb-2" id="addIncidentModalLabel" style="font-size: 1.5rem;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Report New Incident
              </h5>
              <p class="text-white mb-0" style="opacity: 0.9; font-size: 0.9rem;">Fill in the details below to create a new incident report</p>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="opacity: 0.9;"></button>
          </div>
          
          <!-- Modal Body with Improved Padding -->
          <div class="modal-body" style="padding: 2rem 1.75rem;">
            <form id="addIncidentForm" class="needs-validation" novalidate>
              <!-- Incident Type -->
              <div class="mb-4">
                <label for="modalIncidentType" class="form-label fw-semibold mb-2" style="font-size: 0.95rem; color: #495057;">
                  Incident Type <span class="text-danger">*</span>
                </label>
                <select id="modalIncidentType" class="form-select form-select-lg" required style="padding: 0.75rem 1rem; border: 2px solid #dee2e6; border-radius: 0.5rem; transition: all 0.3s ease;">
                  <option value="" selected disabled>Select incident type...</option>
                  <option value="Fire">🔥 Fire</option>
                  <option value="Flood">💧 Flood</option>
                  <option value="Road Accident">🚗 Road Accident</option>
                  <option value="Medical">❤️ Medical</option>
                  <option value="Landslide">⛰️ Landslide</option>
                  <option value="Earthquake">🌍 Earthquake</option>
                  <option value="Power Outage">⚡ Power Outage</option>
                  <option value="Other">⚠️ Other</option>
                </select>
                <div class="invalid-feedback">Please select an incident type.</div>
              </div>

              <!-- Description -->
              <div class="mb-4">
                <label for="modalDescription" class="form-label fw-semibold mb-2" style="font-size: 0.95rem; color: #495057;">
                  Description <span class="text-danger">*</span>
                </label>
                <textarea 
                  id="modalDescription" 
                  class="form-control" 
                  rows="6" 
                  placeholder="Provide detailed information about the incident:

• What happened? (Describe the incident)
• Where did it occur? (Location details)
• When did it happen? (Date and time if known)
• Who's involved? (Number of people, any injuries, etc.)" 
                  required
                  style="padding: 0.75rem 1rem; border: 2px solid #dee2e6; border-radius: 0.5rem; resize: vertical; transition: all 0.3s ease;"
                ></textarea>
                <div class="form-text mt-2" style="font-size: 0.875rem;">
                  <i class="bi bi-info-circle me-1 text-info"></i>
                  Include: What, Where, When, and Who's involved
                </div>
                <div class="invalid-feedback">Please provide a detailed description of the incident.</div>
              </div>

              <!-- Image Upload -->
              <div class="mb-0">
                <label for="modalPhoto" class="form-label fw-semibold mb-2" style="font-size: 0.95rem; color: #495057;">
                  Incident Photo <span class="text-danger">*</span>
                  <small class="text-muted fw-normal">(Geotagged photos preferred)</small>
                </label>
                <input
                  id="modalPhoto"
                  type="file"
                  class="form-control form-control-lg"
                  accept="image/*"
                  capture="environment"
                  required
                  style="padding: 0.75rem 1rem; border: 2px solid #dee2e6; border-radius: 0.5rem; transition: all 0.3s ease;"
                />
                <div class="form-text mt-2" id="modalPhotoMeta" style="font-size: 0.875rem;">
                  <i class="bi bi-clock me-1 text-muted"></i>Awaiting image upload...
                </div>
                <div class="invalid-feedback">Please upload a photo of the incident.</div>
                
                <!-- Photo Preview -->
                <div class="mt-3 border rounded-3 p-4 text-center" id="modalPhotoPreviewWrap" style="display: none; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px dashed #dc3545 !important;">
                  <img id="modalPhotoPreview" alt="Photo Preview" class="img-fluid rounded shadow-sm" style="max-height: 280px; width: auto; border: 2px solid #fff;" />
                  <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="modalRemovePhoto" style="border-radius: 0.5rem;">
                      <i class="bi bi-x-circle me-1"></i> Remove Photo
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
          
          <!-- Modal Footer with Improved Padding -->
          <div class="modal-footer border-top border-2" style="padding: 1.25rem 1.75rem; background: #f8f9fa;">
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 0.5rem; font-weight: 500;">
              <i class="bi bi-x-circle me-1"></i> Cancel
            </button>
            <button type="button" class="btn btn-danger btn-lg px-5" id="modalSubmitIncident" style="border-radius: 0.5rem; font-weight: 600; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); transition: all 0.3s ease;">
              <i class="bi bi-check-circle me-1"></i> Submit Incident Report
            </button>
          </div>
        </div>
      </div>
    </div>
    <script src="scripts/add-incident-modal.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/scripts/add-incident-modal.js')); ?>"></script>
    <?php endif; ?>

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
