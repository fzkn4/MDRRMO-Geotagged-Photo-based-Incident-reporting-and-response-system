<?php
define('SECURE_ACCESS', true);
require_once '../auth.php';

// Check if user is logged in and is admin
checkLogin();

if (getUserRole() !== 'admin') {
    header('Location: ../client-dashboard.php');
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
    <title>Incidents | MDRRMO Incident Reporting</title>

    <!-- Tab Icon / Favicon -->
    <link rel="icon" type="image/png" href="../assets/icon.png" />
    <link rel="shortcut icon" type="image/png" href="../assets/icon.png" />

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

    <link rel="stylesheet" href="../styles/dashboard.css" />
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
          <img src="../assets/icon.png" alt="MDRRMO Logo" style="max-width: 32px; height: auto; margin-right: 0.5rem;" />
          <span id="brandText">MDRRMO Admin</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="bi bi-list"></i>
        </button>
      </div>
      
      <div class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title" id="navTitle">Navigation</div>
          
          <a href="../admin-dashboard.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-apps" data-unfilled="fi fi-rr-apps"></i>
            </div>
            <div class="nav-text">Dashboard</div>
          </a>
          
          <a href="organization-chart.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-sitemap" data-unfilled="fi fi-rr-sitemap"></i>
            </div>
            <div class="nav-text">Organization Chart</div>
          </a>
          
          <a href="incidents.php" class="nav-item active" id="incidentsLink">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-light-emergency-on" data-unfilled="fi fi-rr-light-emergency-on"></i>
            </div>
            <div class="nav-text">Incidents</div>
            <div class="nav-badge warning" id="incidentCount">0</div>
          </a>
          
          <a href="equipment.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-box" data-unfilled="fi fi-rr-box"></i>
            </div>
            <div class="nav-text">Equipment</div>
          </a>
          
          <a href="activities.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-calendar-check" data-unfilled="fi fi-rr-calendar-check"></i>
            </div>
            <div class="nav-text">Activities</div>
          </a>
          
          <a href="users.php" class="nav-item">
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
                <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
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
                <h1 class="h3 mb-1">Incidents Management</h1>
                <p class="text-muted mb-0">Manage and monitor all incident reports</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnRefresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncidentModal">
                  <i class="bi bi-plus-circle"></i> Add New Incident
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Incidents Content -->
        <div class="row">
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0 pb-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                  <div>
                    <h5 class="mb-1 fw-bold d-flex align-items-center gap-2">
                      <i class="bi bi-flag-fill text-primary"></i> All Incidents
                    </h5>
                    <p class="text-muted small mb-0" id="incidentsCountText">Loading incidents...</p>
                  </div>
                  <div class="d-flex gap-2 align-items-center">
                    <label for="filterStatus" class="form-label small text-muted mb-0 d-none d-md-block">Filter:</label>
                    <select id="filterStatus" class="form-select form-select-sm incident-filter-select">
                      <option value="All" selected>All statuses</option>
                      <option value="New">New</option>
                      <option value="Dispatched">Dispatched</option>
                      <option value="Resolved">Resolved</option>
                      <option value="Cancelled">Cancelled</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <!-- Loading State -->
                <div id="incidentsLoading" class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-3 text-muted">Loading incidents...</p>
                </div>

                <!-- Empty State -->
                <div id="incidentsEmpty" class="text-center py-5" style="display: none;">
                  <div class="mb-4">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                  </div>
                  <h5 class="fw-semibold mb-2 text-muted">No Incidents Found</h5>
                  <p class="text-muted mb-4">There are no incidents matching your filter criteria.</p>
                  <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addIncidentModal">
                    <i class="bi bi-plus-circle me-2"></i> Add New Incident
                  </button>
                </div>

                <!-- Incidents List -->
                <div id="incidentsList" class="row g-3">
                  <!-- Incidents will be dynamically inserted here -->
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

    <script src="../scripts/dashboard.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/dashboard.js')); ?>"></script>
    <script src="../scripts/add-incident-modal.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/add-incident-modal.js')); ?>"></script>
    <script src="../scripts/admin-incidents.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/admin-incidents.js')); ?>"></script>
    <style>
      /* Incident Cards - 1:1 Square Aspect Ratio */
      .incident-card-square {
        position: relative;
        width: 100%;
        aspect-ratio: 1 / 1;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        cursor: pointer;
      }
      
      .incident-card-square.hover-lift:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
      }
      
      /* Image Section */
      .incident-card-image-wrapper {
        position: relative;
        width: 100%;
        height: 60%;
        overflow: hidden;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
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
      
      /* Status Badge Overlay */
      .incident-card-status-overlay {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 2;
      }
      
      .incident-status-badge {
        font-size: 0.7rem;
        padding: 0.35rem 0.6rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
      
      /* Type Icon Overlay */
      .incident-card-type-overlay {
        position: absolute;
        bottom: 8px;
        left: 8px;
        z-index: 2;
      }
      
      .incident-type-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
      
      /* Content Section */
      .incident-card-content {
        flex: 1;
        padding: 12px;
        display: flex;
        flex-direction: column;
        background: white;
      }
      
      .incident-card-header {
        margin-bottom: 8px;
      }
      
      .incident-card-title {
        font-size: 0.9rem;
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
        font-size: 0.7rem;
        color: #6c757d;
        font-weight: 500;
      }
      
      .incident-card-description {
        font-size: 0.75rem;
        color: #495057;
        margin: 0 0 8px 0;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
      }
      
      .incident-card-meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-bottom: 8px;
        padding-top: 8px;
        border-top: 1px solid #e9ecef;
      }
      
      .incident-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.7rem;
        color: #6c757d;
      }
      
      .incident-meta-item i {
        font-size: 0.75rem;
        width: 14px;
      }
      
      /* Actions */
      .incident-card-actions {
        display: flex;
        gap: 4px;
        margin-top: auto;
        padding-top: 8px;
        border-top: 1px solid #f1f3f5;
      }
      
      .incident-action-btn {
        flex: 1;
        padding: 0.35rem;
        font-size: 0.75rem;
        border-radius: 6px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      
      .incident-action-btn:hover {
        transform: translateY(-1px);
      }
      
      .incident-action-btn i {
        font-size: 0.85rem;
      }
      
      /* Filter Select */
      .incident-filter-select {
        min-width: 160px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
        transition: all 0.3s ease;
      }
      
      .incident-filter-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
      }
      
      /* Loading and Empty States */
      #incidentsLoading,
      #incidentsEmpty {
        min-height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }
      
      /* Responsive adjustments */
      @media (max-width: 576px) {
        .incident-card-square {
          aspect-ratio: 1 / 1.1;
        }
        
        .incident-card-image-wrapper {
          height: 55%;
        }
      }
      
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
      
      .spinning {
        animation: spin 1s linear;
      }
      
      /* Card grid improvements */
      #incidentsList {
        padding: 0.5rem;
      }
      
      @media (min-width: 1400px) {
        #incidentsList .col-xl-2 {
          flex: 0 0 auto;
          width: 16.66666667%;
        }
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
