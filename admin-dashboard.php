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
                <div id="pendingReportsList" class="incidents-grid">
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

    <script src="scripts/sidebar-counts.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/scripts/sidebar-counts.js')); ?>"></script>
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
      
      /* Incident Cards - Responsive Rectangle Aspect Ratio (matching incidents page) */
      .incident-card-square {
        position: relative;
        width: 100%;
        min-height: 200px;
        background: white;
        border-radius: clamp(8px, 1.5vw, 12px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: visible;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: row;
        cursor: pointer;
        max-width: 100%;
        box-sizing: border-box;
        align-items: stretch;
      }
      
      .incident-card-square.hover-lift:hover {
        transform: translateY(clamp(-4px, -1vw, -8px));
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
      }
      
      /* Image Section - Rectangle Layout (Left Side) */
      .incident-card-image-wrapper {
        position: relative;
        flex: 0 0 clamp(35%, 40%, 45%);
        max-width: 45%;
        min-width: 120px;
        min-height: 180px;
        height: auto;
        align-self: stretch;
        overflow: hidden;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        flex-shrink: 0;
      }
      
      .incident-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      }
      
      .incident-card-square:hover .incident-card-image {
        transform: scale(1.1);
      }
      
      .incident-card-image-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      }
      
      .incident-card-image-placeholder i {
        font-size: clamp(2rem, 8vw, 3rem);
        color: #adb5bd;
      }
      
      /* Status Badge Overlay - Responsive */
      .incident-card-status-overlay {
        position: absolute;
        top: clamp(4px, 1vw, 8px);
        right: clamp(4px, 1vw, 8px);
        z-index: 2;
      }
      
      .incident-status-badge {
        font-size: clamp(0.6rem, 1.5vw, 0.7rem);
        padding: clamp(0.25rem, 0.8vw, 0.35rem) clamp(0.4rem, 1.2vw, 0.6rem);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        white-space: nowrap;
      }
      
      /* Type Icon Overlay - Responsive */
      .incident-card-type-overlay {
        position: absolute;
        bottom: clamp(4px, 1vw, 8px);
        left: clamp(4px, 1vw, 8px);
        z-index: 2;
      }
      
      .incident-type-icon {
        width: clamp(32px, 8vw, 40px);
        height: clamp(32px, 8vw, 40px);
        border-radius: clamp(8px, 2vw, 10px);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: clamp(1rem, 3vw, 1.2rem);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
      
      /* Content Section - Rectangle Layout (Right Side) */
      .incident-card-content {
        flex: 1 1 0;
        min-width: 0;
        padding: clamp(8px, 2vw, 14px);
        display: flex;
        flex-direction: column;
        gap: clamp(6px, 1.5vw, 10px);
        background: white;
        min-height: 0;
        overflow: visible;
        position: relative;
        max-width: 100%;
        box-sizing: border-box;
        justify-content: space-between;
        height: auto;
        flex-grow: 1;
      }
      
      .incident-card-header {
        margin-bottom: clamp(4px, 1.5vw, 8px);
        flex-shrink: 0;
        flex-grow: 0;
      }
      
      .incident-card-title {
        font-size: clamp(0.8rem, 2.2vw, 0.9rem);
        font-weight: 700;
        color: #212529;
        margin: 0;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      
      .incident-card-time {
        font-size: clamp(0.65rem, 1.8vw, 0.7rem);
        color: #6c757d;
        font-weight: 500;
      }
      
      .incident-card-description {
        font-size: clamp(0.7rem, 1.9vw, 0.75rem);
        color: #495057;
        margin: 0 0 clamp(8px, 2vw, 10px) 0;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 0 1 auto;
        min-height: clamp(3rem, 8vw, 3.5rem);
        max-height: clamp(3rem, 8vw, 3.5rem);
        flex-grow: 0;
      }
      
      .incident-card-meta {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: clamp(8px, 2vw, 12px);
        margin-bottom: clamp(8px, 2vw, 10px);
        padding-top: clamp(6px, 1.5vw, 8px);
        border-top: 1px solid #e9ecef;
        flex-shrink: 0;
        flex-grow: 0;
        align-items: center;
      }
      
      .incident-meta-item {
        display: flex;
        align-items: center;
        gap: clamp(4px, 1.5vw, 6px);
        font-size: clamp(0.65rem, 1.8vw, 0.7rem);
        color: #6c757d;
      }
      
      .incident-meta-item i {
        font-size: clamp(0.7rem, 1.9vw, 0.75rem);
        width: clamp(12px, 3.5vw, 14px);
        flex-shrink: 0;
      }
      
      /* Actions - Always at Footer/Bottom */
      .incident-card-actions {
        display: flex !important;
        gap: clamp(6px, 1.5vw, 10px);
        margin-top: auto;
        padding-top: clamp(8px, 1.5vw, 12px);
        border-top: 1px solid #f1f3f5;
        flex-shrink: 0;
        flex-grow: 0;
        min-height: clamp(40px, 10vw, 50px);
        align-items: stretch;
        justify-content: flex-start;
        flex-wrap: nowrap;
        width: 100%;
        max-width: 100%;
        overflow: visible;
        position: relative;
        visibility: visible !important;
        opacity: 1 !important;
        box-sizing: border-box;
      }
      
      .incident-action-btn {
        flex: 1 1 0;
        min-width: 0;
        max-width: none;
        padding: clamp(0.4rem, 1vw, 0.55rem) clamp(0.5rem, 1.2vw, 0.8rem);
        font-size: clamp(0.65rem, 1.7vw, 0.75rem);
        border-radius: clamp(4px, 1.5vw, 6px);
        transition: all 0.2s ease;
        display: flex !important;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        box-sizing: border-box;
        visibility: visible !important;
        opacity: 1 !important;
      }
      
      .incident-action-btn:hover {
        transform: translateY(-1px);
      }
      
      .incident-action-btn:active {
        transform: translateY(0);
      }
      
      .incident-action-btn i {
        font-size: clamp(0.7rem, 1.9vw, 0.85rem);
        flex-shrink: 0;
        display: inline-block;
      }
      
      .incident-action-btn.btn-sm {
        padding: clamp(0.4rem, 1vw, 0.55rem) clamp(0.5rem, 1.2vw, 0.8rem) !important;
        font-size: clamp(0.65rem, 1.7vw, 0.75rem) !important;
        line-height: 1.5 !important;
      }
      
      /* Responsive Grid Adjustments */
      .incidents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: clamp(12px, 2vw, 18px);
        padding: clamp(0.25rem, 1vw, 0.75rem);
        align-items: stretch;
      }
      
      .incidents-grid .incident-grid-item {
        display: flex;
        flex-direction: column;
        min-height: 0;
      }
      
      .incidents-grid .incident-card-square {
        height: auto;
        min-height: 200px;
        overflow: visible;
      }
      
      /* Responsive adjustments */
      @media (max-width: 575.98px) {
        .incidents-grid {
          grid-template-columns: 1fr;
          gap: clamp(10px, 3vw, 14px);
        }
        
        .incident-card-square {
          min-height: 250px;
          flex-direction: column;
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 auto;
          max-width: 100%;
          min-height: 120px;
          max-height: 200px;
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          width: 100%;
          padding: clamp(8px, 2vw, 10px);
          overflow: visible;
          height: auto;
        }
        
        .incident-card-description {
          min-height: 2.5rem;
          max-height: 2.5rem;
          -webkit-line-clamp: 2;
          margin-bottom: 6px;
        }
        
        .incident-card-meta {
          flex-direction: column;
          gap: 4px;
          margin-bottom: 6px;
          padding-top: 6px;
        }
        
        .incident-card-actions {
          gap: clamp(6px, 1.5vw, 8px);
          min-height: 40px;
          flex-wrap: nowrap;
          padding-top: 6px;
          width: 100%;
          max-width: 100%;
        }
        
        .incident-action-btn {
          padding: 0.4rem 0.5rem;
          flex: 1 1 0 !important;
          min-width: 0 !important;
          max-width: none !important;
          font-size: 0.65rem;
          display: flex !important;
          visibility: visible !important;
          opacity: 1 !important;
        }
      }
      
      @media (min-width: 576px) and (max-width: 767.98px) {
        .incidents-grid {
          grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        
        .incident-card-square {
          min-height: 220px;
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 38%;
          max-width: 40%;
          min-height: 180px;
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          padding: clamp(8px, 2vw, 12px);
          height: auto;
        }
        
        .incident-card-description {
          min-height: 3rem;
          max-height: 3rem;
        }
        
        .incident-card-actions {
          gap: clamp(6px, 1.5vw, 8px);
          flex-wrap: nowrap;
        }
        
        .incident-action-btn {
          flex: 1 1 0 !important;
          min-width: 0 !important;
          max-width: none !important;
          padding: 0.4rem 0.5rem;
          display: flex !important;
          visibility: visible !important;
          opacity: 1 !important;
        }
      }
      
      @media (min-width: 768px) and (max-width: 991.98px) {
        .incidents-grid {
          grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }
        
        .incident-card-square {
          min-height: 240px;
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 40%;
          max-width: 42%;
          min-height: 200px;
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          padding: clamp(10px, 2.2vw, 12px);
          height: auto;
        }
        
        .incident-card-description {
          min-height: 3.2rem;
          max-height: 3.2rem;
        }
        
        .incident-card-actions {
          gap: clamp(6px, 1.5vw, 10px);
          flex-wrap: nowrap;
        }
        
        .incident-action-btn {
          flex: 1 1 0 !important;
          min-width: 0 !important;
          max-width: none !important;
          display: flex !important;
          visibility: visible !important;
          opacity: 1 !important;
        }
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
