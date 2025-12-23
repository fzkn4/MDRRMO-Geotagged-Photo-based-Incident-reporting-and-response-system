<?php
define('SECURE_ACCESS', true);
require_once 'auth.php';

// Check if user is logged in and is admin
checkLogin();

if (getUserRole() !== 'admin') {
    header('Location: client-dashboard.php');
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
    <title>Admin Dashboard | MDRRMO Incident Reporting</title>

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
          <img src="assets/icon.png" alt="MDRRMO Logo" style="max-width: 32px; height: auto; margin-right: 0.5rem;" />
          <span id="brandText">MDRRMO Admin</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="bi bi-list"></i>
        </button>
      </div>
      
      <div class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title" id="navTitle">Navigation</div>
          
          <a href="admin-dashboard.php" class="nav-item active">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-apps" data-unfilled="fi fi-rr-apps"></i>
            </div>
            <div class="nav-text">Dashboard</div>
          </a>
          
          <a href="admin/organization-chart.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-sitemap" data-unfilled="fi fi-rr-sitemap"></i>
            </div>
            <div class="nav-text">Organization Chart</div>
          </a>
          
          <a href="admin/incidents.php" class="nav-item" id="incidentsLink">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-light-emergency-on" data-unfilled="fi fi-rr-light-emergency-on"></i>
            </div>
            <div class="nav-text">Incidents</div>
            <div class="nav-badge warning" id="incidentCount">0</div>
          </a>
          
          <a href="admin/equipment.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-box" data-unfilled="fi fi-rr-box"></i>
            </div>
            <div class="nav-text">Equipment</div>
          </a>
          
          <a href="admin/activities.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-calendar-check" data-unfilled="fi fi-rr-calendar-check"></i>
            </div>
            <div class="nav-text">Activities</div>
          </a>
          
          <a href="admin/users.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-users" data-unfilled="fi fi-br-users"></i>
            </div>
            <div class="nav-text">Users</div>
            <div class="nav-badge primary" id="userCount">0</div>
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
          
          <!-- Welcome Message on Left Side -->
          <div class="navbar-nav me-auto d-none d-lg-flex align-items-center">
            <span class="navbar-text welcome-text">
              <i class="bi bi-hand-thumbs-up me-2 text-primary"></i>
              <strong>Welcome back, <?php 
                $userData = getUserData();
                $displayName = $userData['full_name'] ?? getCurrentUser();
                echo htmlspecialchars($displayName);
              ?>!</strong>
              <span class="text-muted ms-2">Ready to manage today's operations</span>
            </span>
          </div>
          
          <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
                <?php echo htmlspecialchars(getCurrentUser()); ?>
                <span class="badge bg-danger ms-1">Admin</span>
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
        <!-- Welcome Message for Mobile -->
        <div class="row mb-4 d-lg-none">
          <div class="col-12">
            <div class="alert alert-primary d-flex align-items-center mb-0" role="alert">
              <i class="bi bi-hand-thumbs-up me-2 fs-4"></i>
              <div>
                <strong>Welcome back, <?php 
                  $userData = getUserData();
                  $displayName = $userData['full_name'] ?? getCurrentUser();
                  echo htmlspecialchars($displayName);
                ?>!</strong>
                <div class="small">Ready to manage today's operations</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Page Header -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
              <div>
                <h1 class="h3 mb-1 fw-bold">Admin Dashboard</h1>
                <p class="text-muted mb-0">Overview of system metrics and pending reports</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnRefreshDashboard">
                  <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncidentModal">
                  <i class="bi bi-plus-circle me-1"></i> Add New Incident
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
          <!-- Total Pending Incidents/Reports -->
          <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card hover-lift">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="stats-icon-wrapper bg-warning bg-opacity-10">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                  </div>
                  <div class="text-end">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Pending Reports</div>
                    <h2 class="mb-0 fw-bold" id="totalPendingIncidents">0</h2>
                  </div>
                </div>
                <div class="progress" style="height: 4px;">
                  <div class="progress-bar bg-warning" role="progressbar" style="width: 0%" id="pendingIncidentsProgress"></div>
                </div>
                <div class="mt-2">
                  <small class="text-muted">
                    <i class="bi bi-clock-history me-1"></i>
                    Requires immediate attention
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Users -->
          <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card hover-lift">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="stats-icon-wrapper bg-primary bg-opacity-10">
                    <i class="bi bi-people-fill text-primary fs-3"></i>
                  </div>
                  <div class="text-end">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Total Users</div>
                    <h2 class="mb-0 fw-bold" id="totalUsers">0</h2>
                  </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                  <small class="text-success">
                    <i class="bi bi-check-circle me-1"></i>
                    <span id="activeUsers">0</span> Active
                  </small>
                  <small class="text-warning">
                    <i class="bi bi-clock me-1"></i>
                    <span id="pendingUsers">0</span> Pending
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Incidents -->
          <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card hover-lift">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="stats-icon-wrapper bg-info bg-opacity-10">
                    <i class="bi bi-flag-fill text-info fs-3"></i>
                  </div>
                  <div class="text-end">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Total Incidents</div>
                    <h2 class="mb-0 fw-bold" id="totalIncidents">0</h2>
                  </div>
                </div>
                <div class="mt-2">
                  <small class="text-muted">
                    <i class="bi bi-calendar-event me-1"></i>
                    All time incident reports
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending Reports Section -->
        <div class="row">
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                  <div>
                    <h5 class="mb-1 fw-bold">
                      <i class="bi bi-list-check text-warning me-2"></i>
                      Pending Reports
                    </h5>
                    <p class="text-muted small mb-0">Incident reports awaiting review and action</p>
                  </div>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addIncidentModal">
                      <i class="bi bi-plus-circle me-1"></i> Add New Incident
                    </button>
                    <a href="admin/incidents.php" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-arrow-right me-1"></i> View All
                    </a>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <!-- Loading State -->
                <div id="pendingReportsLoading" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-3 text-muted">Loading pending reports...</p>
                </div>

                <!-- Empty State -->
                <div id="pendingReportsEmpty" class="text-center py-5" style="display: none;">
                  <div class="mb-3">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                  </div>
                  <h5 class="fw-semibold mb-2">No Pending Reports</h5>
                  <p class="text-muted mb-4">All reports have been reviewed. Great job!</p>
                  <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addIncidentModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New Incident
                  </button>
                  <a href="admin/incidents.php" class="btn btn-outline-primary">
                    <i class="bi bi-flag me-1"></i> View All Incidents
                  </a>
                </div>

                <!-- Pending Reports List -->
                <div id="pendingReportsList" class="row g-3">
                  <!-- Reports will be dynamically inserted here -->
                </div>

                <!-- View More Link (if more than displayed) -->
                <div id="pendingReportsViewMore" class="text-center mt-4" style="display: none;">
                  <a href="admin/incidents.php" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-right me-1"></i> View All Pending Reports
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer class="container pb-4 small text-center text-muted">
        <span class="d-inline-flex align-items-center gap-1">
          <i class="bi bi-info-circle"></i> MDRRMO Admin Dashboard - Geotagged Incident Reporting System
        </span>
      </footer>
    </div>

    <!-- Add Incident Modal -->
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

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <script src="scripts/dashboard.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/scripts/dashboard.js')); ?>"></script>
    <script src="scripts/admin-dashboard.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/scripts/admin-dashboard.js')); ?>"></script>
    <script src="scripts/add-incident-modal.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/scripts/add-incident-modal.js')); ?>"></script>
    <style>
      /* Dashboard-specific styles */
      .welcome-text {
        font-size: 0.95rem;
      }
      
      .welcome-text strong {
        color: #0d6efd;
      }
      
      .stats-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
      }
      
      .stats-card.hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
      }
      
      .stats-icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
      }
      
      .stats-card:hover .stats-icon-wrapper {
        transform: scale(1.1);
      }
      
      .pending-report-card {
        transition: all 0.3s ease;
        border-left: 3px solid #ffc107;
      }
      
      .pending-report-card.hover-lift:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1) !important;
      }
      
      .report-icon-wrapper {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
      }
      
      .report-icon-wrapper i {
        font-size: 1.2rem;
      }
      
      .pending-report-image {
        transition: all 0.3s ease;
      }
      
      .pending-report-image:hover {
        transform: scale(1.02);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }
      
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
      
      .spinning {
        animation: spin 1s linear;
      }
      
      @media (max-width: 991px) {
        .welcome-text {
          font-size: 0.85rem;
        }
        
        .welcome-text .text-muted {
          display: none;
        }
      }
      
      /* Add Incident Modal Styles */
      #addIncidentModal .modal-content {
        border-radius: 1rem;
      }
      
      #addIncidentModal .form-select:focus,
      #addIncidentModal .form-control:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
      }
      
      #addIncidentModal .form-select:hover,
      #addIncidentModal .form-control:hover {
        border-color: #adb5bd;
      }
      
      #addIncidentModal .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(220, 53, 69, 0.4) !important;
      }
      
      #addIncidentModal .btn-danger:active {
        transform: translateY(0);
      }
      
      #addIncidentModal .btn-outline-secondary:hover {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
      }
    </style>
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
