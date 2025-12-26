<?php
define('SECURE_ACCESS', true);
require_once '../auth.php';

// Check if user is logged in
checkLogin();

if (getUserRole() === 'admin') {
    header('Location: ../admin-dashboard.php');
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
    <title>My Incidents | MDRRMO Incident Reporting</title>

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
  <body data-current-user="<?php echo htmlspecialchars(getCurrentUser(), ENT_QUOTES); ?>">
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <img src="../assets/icon.png" alt="MDRRMO Logo" style="max-width: 32px; height: auto; margin-right: 0.5rem;" />
          <span id="brandText">MDRRMO Client</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="bi bi-list"></i>
        </button>
      </div>
      
      <div class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title" id="navTitle">Navigation</div>
          
          <a href="../client-dashboard.php" class="nav-item">
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
          
          <a href="activities.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-calendar-check" data-unfilled="fi fi-rr-calendar-check"></i>
            </div>
            <div class="nav-text">Activities</div>
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
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
              <div>
                <h1 class="h3 mb-1 fw-bold">Reported Incidents</h1>
                <p class="text-muted mb-0">View reported incidents</p>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnRefresh">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
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
                  </div>
                  <div class="d-flex gap-2 align-items-center">
                    <label for="filterStatus" class="form-label small text-muted mb-0 d-none d-md-block">Filter:</label>
                    <select id="filterStatus" class="form-select form-select-sm incident-filter-select">
                      <option value="All" selected>All statuses</option>
                      <option value="New">New</option>
                      <option value="Pending">Pending</option>
                      <option value="Approved">Approved</option>
                      <option value="Dispatched">Dispatched</option>
                      <option value="Resolved">Resolved</option>
                      <option value="Decline">Declined</option>
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
                </div>

                <!-- Incidents List -->
                <div id="incidentsList" class="incidents-grid">
                  <!-- Incidents will be dynamically inserted here -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer class="container pb-4 small text-center text-muted">
        <span class="d-inline-flex align-items-center gap-1">
          <i class="bi bi-info-circle"></i> MDRRMO Client Dashboard - Geotagged Incident Reporting System
        </span>
      </footer>
    </div>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <script src="../scripts/sidebar-counts.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/sidebar-counts.js')); ?>"></script>
    <script src="../scripts/dashboard.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/dashboard.js')); ?>"></script>
    <script>
      // Set current user for client-incidents.js
      window.CURRENT_USER = '<?php echo htmlspecialchars(getCurrentUser(), ENT_QUOTES); ?>';
    </script>
    <script src="../scripts/client-incidents.js?v=<?php echo urlencode((string) @filemtime(__DIR__ . '/../scripts/client-incidents.js')); ?>"></script>
    <style>
      /* Incident Cards - Responsive Rectangle Aspect Ratio */
      .incident-card-square {
        position: relative;
        width: 100%;
        min-height: 200px; /* Minimum height instead of fixed aspect ratio */
        background: white;
        border-radius: clamp(8px, 1.5vw, 12px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        overflow: visible; /* Allow content to be fully visible */
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: row; /* Horizontal layout for rectangle */
        cursor: pointer;
        max-width: 100%; /* Ensure card doesn't exceed container */
        box-sizing: border-box;
        align-items: stretch; /* Stretch to fit content */
      }
      
      .incident-card-square.hover-lift:hover {
        transform: translateY(clamp(-4px, -1vw, -8px));
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
      }
      
      /* Image Section - Rectangle Layout (Left Side) */
      .incident-card-image-wrapper {
        position: relative;
        flex: 0 0 clamp(35%, 40%, 45%); /* Takes left portion but never grows */
        max-width: 45%;
        min-width: 120px;
        min-height: 180px; /* Minimum height for image */
        height: auto; /* Let image determine height, but match card */
        align-self: stretch; /* Stretch to match card height */
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
        background: #6c757d; /* Default fallback color */
      }
      
      /* Incident Type Colors */
      .incident-type-icon.incident-type-fire {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      }
      
      .incident-type-icon.incident-type-flood {
        background: linear-gradient(135deg, #4ecdc4, #44a08d);
      }
      
      .incident-type-icon.incident-type-road-accident {
        background: linear-gradient(135deg, #feca57, #ff9ff3);
      }
      
      .incident-type-icon.incident-type-medical {
        background: linear-gradient(135deg, #ff9ff3, #f368e0);
      }
      
      .incident-type-icon.incident-type-landslide {
        background: linear-gradient(135deg, #a55eea, #8b5cf6);
      }
      
      .incident-type-icon.incident-type-earthquake {
        background: linear-gradient(135deg, #fd79a8, #e84393);
      }
      
      .incident-type-icon.incident-type-power-outage {
        background: linear-gradient(135deg, #fdcb6e, #e17055);
      }
      
      .incident-type-icon.incident-type-other {
        background: linear-gradient(135deg, #6c5ce7, #a29bfe);
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
        flex-shrink: 0; /* Prevent header from being compressed */
        flex-grow: 0; /* Don't grow */
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
        -webkit-line-clamp: 3; /* Show 3 lines in rectangle format */
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 0 1 auto; /* Can shrink but don't grow */
        min-height: clamp(3rem, 8vw, 3.5rem);
        max-height: clamp(3rem, 8vw, 3.5rem);
        flex-grow: 0; /* Don't take extra space */
      }
      
      .incident-card-meta {
        display: flex;
        flex-direction: row; /* Horizontal layout for calendar and location */
        flex-wrap: wrap;
        gap: clamp(8px, 2vw, 12px);
        margin-bottom: clamp(8px, 2vw, 10px);
        padding-top: clamp(6px, 1.5vw, 8px);
        border-top: 1px solid #e9ecef;
        flex-shrink: 0;
        flex-grow: 0; /* Don't grow */
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
        flex-shrink: 0; /* Prevent icon from shrinking */
        display: inline-block; /* Ensure icon is visible */
      }
      
      /* Override Bootstrap btn-sm if it's causing issues */
      .incident-action-btn.btn-sm {
        padding: clamp(0.4rem, 1vw, 0.55rem) clamp(0.5rem, 1.2vw, 0.8rem) !important;
        font-size: clamp(0.65rem, 1.7vw, 0.75rem) !important;
        line-height: 1.5 !important;
      }
      
      /* Hide button text on very small screens, show only icons */
      .incident-action-btn span,
      .incident-action-btn:not(:only-child) {
        display: none;
      }
      
      @media (min-width: 576px) {
        .incident-action-btn span {
          display: inline;
        }
      }
      
      /* Filter Select - Responsive */
      .incident-filter-select {
        min-width: clamp(140px, 20vw, 160px);
        border: 2px solid #dee2e6;
        border-radius: clamp(6px, 2vw, 8px);
        font-size: clamp(0.8rem, 2.2vw, 0.875rem);
        padding: clamp(0.4rem, 1.2vw, 0.5rem) clamp(0.6rem, 1.8vw, 0.75rem);
        transition: all 0.3s ease;
      }
      
      .incident-filter-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
      }
      
      /* Loading and Empty States - Responsive */
      #incidentsLoading,
      #incidentsEmpty {
        min-height: clamp(300px, 50vh, 400px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: clamp(1rem, 4vw, 2rem);
      }
      
      #incidentsEmpty i {
        font-size: clamp(3rem, 15vw, 4rem);
      }
      
      /* Responsive Grid Adjustments */
      #incidentsList {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: clamp(12px, 2vw, 18px);
        padding: clamp(0.25rem, 1vw, 0.75rem);
        align-items: stretch;
      }
      
      #incidentsList .incident-grid-item {
        display: flex;
        flex-direction: column;
        min-height: 0; /* Allow grid items to size naturally */
      }
      
      #incidentsList .incident-card-square {
        height: auto; /* Let card grow to fit content */
        min-height: 200px; /* But maintain minimum height */
        overflow: visible; /* Allow content to be fully visible */
      }
      
      /* Extra Small Devices (phones, less than 576px) */
      @media (max-width: 575.98px) {
        #incidentsList {
          grid-template-columns: 1fr;
          gap: clamp(10px, 3vw, 14px);
        }
        
        .incident-card-square {
          min-height: 250px; /* Minimum height for mobile */
          flex-direction: column; /* Stack vertically on very small screens */
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 auto;
          max-width: 100%;
          min-height: 120px; /* Fixed minimum height for image */
          max-height: 200px; /* Maximum height to prevent it from taking too much space */
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          width: 100%;
          padding: clamp(8px, 2vw, 10px);
          overflow: visible;
          height: auto; /* Let content determine height */
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
        
        .incident-action-btn i {
          font-size: 0.75rem;
        }
      }
      
      /* Small Devices (landscape phones, 576px and up) */
      @media (min-width: 576px) and (max-width: 767.98px) {
        #incidentsList {
          grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        
        .incident-card-square {
          min-height: 220px; /* Minimum height for small devices */
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 38%;
          max-width: 40%;
          min-height: 180px; /* Ensure image has minimum height */
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          padding: clamp(8px, 2vw, 12px);
          height: auto; /* Let content determine height */
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
      
      /* Medium Devices (tablets, 768px and up) */
      @media (min-width: 768px) and (max-width: 991.98px) {
        #incidentsList {
          grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }
        
        .incident-card-square {
          min-height: 240px; /* Minimum height for medium devices */
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 40%;
          max-width: 42%;
          min-height: 200px; /* Ensure image has minimum height */
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          padding: clamp(10px, 2.2vw, 12px);
          height: auto; /* Let content determine height */
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
      
      /* Large Devices (desktops, 992px and up) */
      @media (min-width: 992px) and (max-width: 1199.98px) {
        #incidentsList {
          grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        
        .incident-card-square {
          min-height: 260px; /* Minimum height for large devices */
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 42%;
          max-width: 44%;
          min-height: 220px; /* Ensure image has minimum height */
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          height: auto; /* Let content determine height */
        }
        
        .incident-card-description {
          min-height: 3.5rem;
          max-height: 3.5rem;
        }
      }
      
      /* Extra Large Devices (large desktops, 1200px and up) */
      @media (min-width: 1200px) {
        #incidentsList {
          grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .incident-card-square {
          min-height: 280px; /* Minimum height for extra large devices */
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 38%;
          max-width: 40%;
          min-height: 240px; /* Ensure image has minimum height */
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          height: auto; /* Let content determine height */
        }
        
        .incident-card-description {
          min-height: 3.8rem;
          max-height: 3.8rem;
        }
      }
      
      /* Ultra Wide Screens (1400px and up) */
      @media (min-width: 1400px) {
        #incidentsList {
          grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
        
        .incident-card-image-wrapper {
          flex: 0 0 40%;
          max-width: 42%;
          min-height: 250px; /* Ensure image has minimum height */
        }
        
        .incident-card-content {
          flex: 1 1 auto;
          min-width: 0;
          height: auto; /* Let content determine height */
        }
      }
      
      /* Touch Device Optimizations */
      @media (hover: none) and (pointer: coarse) {
        .incident-card-square.hover-lift:active {
          transform: translateY(-4px);
          box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        }
        
        .incident-action-btn {
          min-height: 44px; /* Better touch target */
        }
      }
      
      @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
      
      .spinning {
        animation: spin 1s linear;
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

