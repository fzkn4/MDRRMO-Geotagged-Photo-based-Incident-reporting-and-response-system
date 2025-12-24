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
    <title>Activities | MDRRMO Incident Reporting</title>

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
          
          <a href="incidents.php" class="nav-item" id="incidentsLink">
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
          
          <a href="activities.php" class="nav-item active">
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
                <h1 class="h3 mb-1">Activities</h1>
                <p class="text-muted mb-0">View and manage system activities and logs</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnRefresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <button class="btn btn-primary" id="btnAddActivity" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                  <i class="bi bi-plus-circle"></i> Add Activity
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Activities Content -->
        <div class="row">
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                  <i class="bi bi-calendar-check text-primary"></i> Activity Log
                </h5>
              </div>
              <div class="card-body">
                <!-- Loading State -->
                <div id="activitiesLoading" class="text-center py-5" style="display: none;">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-3 text-muted">Loading activities...</p>
                </div>

                <!-- Empty State -->
                <div id="activitiesEmpty" class="text-center py-5">
                  <div class="mb-4">
                    <i class="bi bi-calendar-event fs-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-semibold mb-2">No Activities Added Yet</h5>
                    <p class="text-muted mb-4">Start documenting activities by adding your first activity.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                      <i class="bi bi-plus-circle me-1"></i> Add First Activity
                    </button>
                  </div>
                </div>

                <!-- Activities List -->
                <div id="activitiesList" class="activities-list" style="display: none;"></div>
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

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <!-- Add Activity Modal -->
    <div class="modal fade" id="addActivityModal" tabindex="-1" aria-labelledby="addActivityModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header border-0 bg-primary text-white">
            <h5 class="modal-title fw-bold" id="addActivityModalLabel">
              <i class="bi bi-calendar-plus me-2"></i>Add Activity
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="addActivityForm">
            <div class="modal-body p-4">
              <div class="mb-3">
                <label for="activityTitle" class="form-label fw-semibold">Activity Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="activityTitle" required placeholder="e.g., Fire Drill, Training Session, Emergency Response">
              </div>
              
              <div class="mb-3">
                <label for="activityDate" class="form-label fw-semibold">Date & Time Conducted <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" id="activityDate" required>
                <small class="text-muted d-block mt-1">Select when this activity was conducted</small>
              </div>
              
              <div class="mb-3">
                <label for="activityDescription" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="activityDescription" rows="4" placeholder="Describe the activity, participants, location, and outcomes..."></textarea>
              </div>
              
              <div class="mb-3">
                <label for="activityImages" class="form-label fw-semibold">Activity Images</label>
                <input type="file" class="form-control" id="activityImages" accept="image/*" multiple>
                <small class="text-muted d-block mt-1">You can select multiple images. JPG, PNG, or GIF formats.</small>
                
                <!-- Image Preview Gallery -->
                <div class="mt-3" id="activityImagesPreviewContainer" style="display: none;">
                  <div class="d-flex flex-wrap gap-2" id="activityImagesPreview"></div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 bg-light">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Add Activity
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Image Gallery Modal -->
    <div class="modal fade" id="imageGalleryModal" tabindex="-1" aria-labelledby="imageGalleryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg bg-dark">
          <div class="modal-header border-0 bg-dark">
            <h5 class="modal-title text-white fw-bold" id="imageGalleryModalLabel">
              <i class="bi bi-images me-2"></i>Activity Images
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div id="galleryCarousel" class="carousel slide" data-bs-ride="carousel">
              <div class="carousel-inner" id="galleryCarouselInner"></div>
              <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
              </button>
            </div>
            <div class="p-3 bg-dark text-white text-center">
              <span id="galleryImageCounter">1 / 1</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="../scripts/sidebar-counts.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/sidebar-counts.js')); ?>"></script>
    <script src="../scripts/dashboard.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/dashboard.js')); ?>"></script>
    <script src="../scripts/activities.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/activities.js')); ?>"></script>
    <style>
      /* Activities List Styles */
      .activities-list {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
      }

      .activity-card {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      }

      .activity-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        border-color: #dc3545;
      }

      .activity-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
      }

      .activity-card-title {
        font-weight: 700;
        font-size: 1.15rem;
        color: #212529;
        margin: 0;
      }

      .activity-card-date {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 0.25rem;
      }

      .activity-card-body {
        padding: 1.5rem;
      }

      .activity-card-description {
        color: #495057;
        line-height: 1.6;
        margin-bottom: 1.25rem;
      }

      .activity-images-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
      }

      .activity-image-thumb {
        position: relative;
        width: 100%;
        aspect-ratio: 1;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #e9ecef;
      }

      .activity-image-thumb:hover {
        transform: scale(1.05);
        border-color: #dc3545;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
      }

      .activity-image-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .activity-image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
      }

      .activity-image-thumb:hover .activity-image-overlay {
        opacity: 1;
      }

      .activity-image-overlay i {
        color: white;
        font-size: 1.5rem;
      }

      /* Image Preview in Modal */
      .image-preview-item {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #dee2e6;
        cursor: pointer;
      }

      .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }

      .image-preview-remove {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.75rem;
        transition: all 0.2s ease;
      }

      .image-preview-remove:hover {
        background: #dc3545;
        transform: scale(1.1);
      }

      /* Gallery Carousel */
      #galleryCarousel .carousel-item img {
        max-height: 70vh;
        width: auto;
        margin: 0 auto;
        object-fit: contain;
      }

      #galleryCarousel .carousel-control-prev,
      #galleryCarousel .carousel-control-next {
        width: 50px;
        height: 50px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0, 0, 0, 0.5);
        border-radius: 50%;
        opacity: 0.7;
      }

      #galleryCarousel .carousel-control-prev:hover,
      #galleryCarousel .carousel-control-next:hover {
        opacity: 1;
      }

      #galleryCarousel .carousel-control-prev {
        left: 20px;
      }

      #galleryCarousel .carousel-control-next {
        right: 20px;
      }

      /* Modal Styles */
      #addActivityModal .modal-content {
        border-radius: 12px;
        overflow: hidden;
      }

      #addActivityModal .modal-header {
        padding: 1.5rem;
      }

      #addActivityModal .modal-body {
        padding: 1.5rem;
      }

      #addActivityModal .form-label {
        margin-bottom: 0.5rem;
        color: #495057;
      }

      #addActivityModal .form-control,
      #addActivityModal textarea {
        border-radius: 8px;
        border: 1.5px solid #dee2e6;
        padding: 0.625rem 0.875rem;
        transition: all 0.2s ease;
      }

      #addActivityModal .form-control:focus,
      #addActivityModal textarea:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
      }

      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }

      .spinning {
        animation: spin 1s linear;
      }

      /* Responsive */
      @media (max-width: 768px) {
        .activity-images-gallery {
          grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
          gap: 0.5rem;
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
