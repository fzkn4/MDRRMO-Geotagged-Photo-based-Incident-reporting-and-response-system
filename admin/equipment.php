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
    <title>Equipment | MDRRMO Incident Reporting</title>

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
          
          <a href="equipment.php" class="nav-item active">
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
                <h1 class="h3 mb-1">Equipment Management</h1>
                <p class="text-muted mb-0">Manage equipment inventory and tracking</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnRefresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <button class="btn btn-primary" id="btnAddEquipment" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                  <i class="bi bi-plus-circle"></i> Add Equipment
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
          <!-- Total Equipment Types -->
          <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card hover-lift">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="stats-icon-wrapper bg-primary bg-opacity-10">
                    <i class="bi bi-tools text-primary fs-3"></i>
                  </div>
                  <div class="text-end">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Equipment Types</div>
                    <h2 class="mb-0 fw-bold" id="totalEquipmentTypes">0</h2>
                  </div>
                </div>
                <div class="mt-2">
                  <small class="text-muted">
                    <i class="bi bi-list-ul me-1"></i>
                    Different equipment categories
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- Overall Total Equipment -->
          <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 stats-card hover-lift">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="stats-icon-wrapper bg-success bg-opacity-10">
                    <i class="bi bi-box-seam text-success fs-3"></i>
                  </div>
                  <div class="text-end">
                    <div class="text-muted small text-uppercase fw-semibold mb-1">Total Equipment</div>
                    <h2 class="mb-0 fw-bold" id="totalEquipmentCount">0</h2>
                  </div>
                </div>
                <div class="mt-2">
                  <small class="text-muted">
                    <i class="bi bi-calculator me-1"></i>
                    Total items in inventory
                  </small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Equipment Content -->
        <div class="row">
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                  <i class="bi bi-tools text-primary"></i> Equipment Inventory
                </h5>
              </div>
              <div class="card-body">
                <!-- Loading State -->
                <div id="equipmentLoading" class="text-center py-5" style="display: none;">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <p class="mt-3 text-muted">Loading equipment...</p>
                </div>

                <!-- Empty State -->
                <div id="equipmentEmpty" class="text-center py-5">
                  <div class="mb-4">
                    <i class="bi bi-toolbox fs-1 text-muted d-block mb-3"></i>
                    <h5 class="fw-semibold mb-2">No Equipment Added Yet</h5>
                    <p class="text-muted mb-4">Start building your equipment inventory by adding equipment items.</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
                      <i class="bi bi-plus-circle me-1"></i> Add First Equipment
                    </button>
                  </div>
                </div>

                <!-- Equipment Grid -->
                <div id="equipmentGrid" class="equipment-grid" style="display: none;"></div>
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

    <!-- Add Equipment Modal -->
    <div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header border-0 bg-primary text-white">
            <h5 class="modal-title fw-bold" id="addEquipmentModalLabel">
              <i class="bi bi-tool me-2"></i>Add Equipment
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="addEquipmentForm">
            <div class="modal-body p-4">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="equipmentName" class="form-label fw-semibold">Equipment Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="equipmentName" required placeholder="e.g., Fire Truck, Ambulance, Rescue Equipment">
                </div>
                
                <div class="col-md-6 mb-3">
                  <label for="equipmentCount" class="form-label fw-semibold">Count/Quantity <span class="text-danger">*</span></label>
                  <input type="number" class="form-control" id="equipmentCount" required min="1" placeholder="Enter quantity">
                  <small class="text-muted">Number of items available</small>
                </div>
              </div>
              
              <div class="mb-3">
                <label for="equipmentImage" class="form-label fw-semibold">Equipment Image</label>
                <input type="file" class="form-control" id="equipmentImage" accept="image/*">
                <small class="text-muted d-block mt-1">Upload an image of the equipment (optional). JPG, PNG, or GIF formats.</small>
                
                <!-- Image Preview -->
                <div class="mt-3 text-center" id="equipmentImagePreviewContainer" style="display: none;">
                  <img id="equipmentImagePreview" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px; border-radius: 8px; object-fit: cover;">
                  <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeEquipmentImage">
                      <i class="bi bi-x-circle me-1"></i> Remove Image
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 bg-light">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Add Equipment
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="../scripts/sidebar-counts.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/sidebar-counts.js')); ?>"></script>
    <script src="../scripts/dashboard.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/dashboard.js')); ?>"></script>
    <script src="../scripts/equipment.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/equipment.js')); ?>"></script>
    <style>
      /* Stats Card Styles */
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

      /* Equipment Grid Styles */
      .equipment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1.25rem;
        padding: 1rem 0;
      }

      .equipment-card {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
      }

      .equipment-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.15);
        border-color: #dc3545;
      }

      .equipment-card-image {
        width: 100%;
        height: 140px;
        object-fit: cover;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      }

      .equipment-card-placeholder {
        width: 100%;
        height: 140px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .equipment-card-placeholder i {
        font-size: 2.5rem;
        color: #adb5bd;
      }

      .equipment-card-body {
        padding: 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
      }

      .equipment-card-title {
        font-weight: 700;
        font-size: 0.95rem;
        color: #212529;
        margin-bottom: 0.75rem;
        line-height: 1.3;
      }

      .equipment-card-count {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.85rem;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border-radius: 18px;
        font-weight: 600;
        font-size: 0.85rem;
        margin-top: auto;
        width: fit-content;
      }

      .equipment-card-actions {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e9ecef;
        display: flex;
        gap: 0.5rem;
      }

      .equipment-card-actions .btn {
        flex: 1;
      }

      /* Modal Styles */
      #addEquipmentModal .modal-content {
        border-radius: 12px;
        overflow: hidden;
      }

      #addEquipmentModal .modal-header {
        padding: 1.5rem;
      }

      #addEquipmentModal .modal-body {
        padding: 1.5rem;
      }

      #addEquipmentModal .form-label {
        margin-bottom: 0.5rem;
        color: #495057;
      }

      #addEquipmentModal .form-control {
        border-radius: 8px;
        border: 1.5px solid #dee2e6;
        padding: 0.625rem 0.875rem;
        transition: all 0.2s ease;
      }

      #addEquipmentModal .form-control:focus {
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
        .equipment-grid {
          grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
          gap: 1rem;
        }

        .equipment-card-image,
        .equipment-card-placeholder {
          height: 120px;
        }

        .equipment-card-placeholder i {
          font-size: 2rem;
        }

        .equipment-card-body {
          padding: 0.85rem;
        }

        .equipment-card-title {
          font-size: 0.9rem;
        }

        .equipment-card-count {
          padding: 0.35rem 0.75rem;
          font-size: 0.8rem;
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
