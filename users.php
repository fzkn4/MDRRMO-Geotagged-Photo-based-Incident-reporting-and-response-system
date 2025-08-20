<?php
define('SECURE_ACCESS', true);
require_once 'auth.php';

// Check if user is logged in and is admin
checkLogin();

if (getUserRole() !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($action === 'delete' && $user_id) {
        deleteUser($user_id);
        $success_message = 'User deleted successfully.';
    } elseif ($action === 'update' && $user_id) {
        $update_data = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'organization' => $_POST['organization'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'status' => $_POST['status'] ?? 'active'
        ];
        
        if (updateUser($user_id, $update_data)) {
            $success_message = 'User updated successfully.';
        } else {
            $error_message = 'Failed to update user.';
        }
    }
}

$users = getAllUsers();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MDRRMO | User Management</title>

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

    <link rel="stylesheet" href="style.css" />
    <style>
      /* Sidebar Styles */
      .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 280px;
        background: white;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        z-index: 1000;
        overflow-y: auto;
      }
      
      .sidebar.collapsed {
        width: 70px;
      }
      
      .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }
      
      .sidebar-brand {
        font-weight: bold;
        font-size: 1.2rem;
        color: #dc3545;
        white-space: nowrap;
        overflow: hidden;
      }
      
      .sidebar-toggle {
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 0.375rem;
        transition: all 0.2s ease;
      }
      
      .sidebar-toggle:hover {
        background: #f8f9fa;
        color: #dc3545;
      }
      
      .sidebar-nav {
        padding: 1rem 0;
      }
      
      .nav-section {
        margin-bottom: 2rem;
      }
      
      .nav-section-title {
        padding: 0.5rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
        overflow: hidden;
      }
      
      .nav-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1.5rem;
        color: #495057;
        text-decoration: none;
        transition: all 0.2s ease;
        position: relative;
        white-space: nowrap;
        overflow: hidden;
      }
      
      .nav-item:hover {
        background: #f8f9fa;
        color: #dc3545;
        text-decoration: none;
      }
      
      .nav-item.active {
        background: #e3f2fd;
        color: #dc3545;
        border-right: 3px solid #dc3545;
      }
      
      .nav-icon {
        width: 20px;
        margin-right: 0.75rem;
        text-align: center;
        flex-shrink: 0;
      }
      
      .nav-text {
        flex: 1;
        overflow: hidden;
      }
      
      .nav-badge {
        background: #dc3545;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: auto;
        flex-shrink: 0;
        transition: all 0.3s ease;
      }
      
      .sidebar.collapsed .nav-badge {
        display: none;
      }
      
      .sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 0.75rem 0.5rem;
      }
      
      .sidebar.collapsed .nav-icon {
        margin-right: 0;
        width: auto;
      }
      
      .sidebar.collapsed .nav-text {
        display: none;
      }
      
      .nav-badge.success { background: #198754; }
      .nav-badge.warning { background: #fd7e14; }
      .nav-badge.info { background: #0dcaf0; }
      .nav-badge.danger { background: #dc3545; }
      .nav-badge.primary { background: #0d6efd; }
      .nav-badge.secondary { background: #6c757d; }
      

      
      /* Main content adjustment */
      .main-content {
        margin-left: 280px;
        transition: all 0.3s ease;
        min-height: 100vh;
        background: #f8f9fa;
      }
      
      .main-content.expanded {
        margin-left: 70px;
      }
      
      /* Responsive */
      @media (max-width: 768px) {
        .sidebar {
          transform: translateX(-100%);
        }
        
        .sidebar.show {
          transform: translateX(0);
        }
        
        .main-content {
          margin-left: 0;
        }
        
        .main-content.expanded {
          margin-left: 0;
        }
      }
      
      /* Overlay for mobile */
      .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
        display: none;
      }
      
      .sidebar-overlay.show {
        display: block;
      }
      
      /* Dropdown fixes */
      .dropdown-menu {
        z-index: 9999 !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(0, 0, 0, 0.1);
        position: absolute !important;
        top: 100% !important;
        min-width: 200px !important;
        background: white !important;
        border-radius: 0.375rem !important;
        padding: 0.5rem 0 !important;
        margin-top: 0.125rem !important;
      }
      
      .dropdown-menu-end {
        right: 0;
        left: auto;
      }
      
      .nav-item.dropdown {
        position: relative;
      }
      
      /* Ensure navbar doesn't clip dropdown */
      .navbar {
        overflow: visible !important;
      }
      
      .navbar-nav {
        overflow: visible !important;
      }
      
      .nav-item.dropdown {
        overflow: visible !important;
      }
      
      /* Ensure navbar dropdowns are above sidebar */
      .navbar .dropdown-menu {
        z-index: 9999 !important;
      }
      
      /* Navbar positioning */
      .navbar {
        position: relative;
        z-index: 1001;
      }
      
      /* Ensure dropdown container is properly positioned */
      .navbar-nav .nav-item.dropdown {
        position: relative;
      }
      
      /* Force dropdown to show above everything */
      .navbar .dropdown-menu.show {
        display: block !important;
        z-index: 9999 !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
      }
      
      /* Ensure dropdown menu is hidden by default */
      .dropdown-menu {
        display: none !important;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.2s ease;
        pointer-events: none;
      }
      
      .dropdown-menu.show {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
        pointer-events: auto;
      }
      
      /* Override any Bootstrap dropdown styles */
      .dropdown-menu[data-bs-popper] {
        display: none !important;
      }
      
      .dropdown-menu.show[data-bs-popper] {
        display: block !important;
      }
      
      /* Dropdown item styling */
      .dropdown-item {
        display: block !important;
        width: 100% !important;
        padding: 0.5rem 1rem !important;
        clear: both !important;
        font-weight: 400 !important;
        color: #212529 !important;
        text-align: inherit !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        background-color: transparent !important;
        border: 0 !important;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out !important;
      }
      
      .dropdown-item:hover {
        color: #1e2125 !important;
        background-color: #f8f9fa !important;
      }
      
      .dropdown-divider {
        height: 0 !important;
        margin: 0.5rem 0 !important;
        border: 0 !important;
        border-top: 1px solid rgba(0, 0, 0, 0.175) !important;
      }
    </style>
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
              <i class="bi bi-speedometer2"></i>
            </div>
            <div class="nav-text">Dashboard</div>
          </a>
          
          <a href="#" class="nav-item">
            <div class="nav-icon">
              <i class="bi bi-flag"></i>
            </div>
            <div class="nav-text">Incidents</div>
            <div class="nav-badge warning" id="incidentCount">0</div>
          </a>
          
          <a href="#" class="nav-item">
            <div class="nav-icon">
              <i class="bi bi-geo-alt"></i>
            </div>
            <div class="nav-text">Map View</div>
          </a>
          
          <a href="users.php" class="nav-item active">
            <div class="nav-icon">
              <i class="bi bi-people"></i>
            </div>
            <div class="nav-text">Users</div>
            <div class="nav-badge primary" id="userCount">0</div>
          </a>
          
          <a href="#" class="nav-item">
            <div class="nav-icon">
              <i class="bi bi-graph-up"></i>
            </div>
            <div class="nav-text">Reports</div>
          </a>
          
          <a href="#" class="nav-item">
            <div class="nav-icon">
              <i class="bi bi-gear"></i>
            </div>
            <div class="nav-text">Settings</div>
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
                <h1 class="h3 mb-1">User Management</h1>
                <p class="text-muted mb-0">Manage system users and permissions</p>
              </div>
              <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <a href="signup.php" class="btn btn-success">
                  <i class="bi bi-person-plus me-1"></i>
                  Add New User
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white border-0">
                <div class="d-flex align-items-center justify-content-between">
                  <h5 class="mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-people text-primary"></i> All Users
                  </h5>
                  <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" id="btnRefresh">
                      <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                  </div>
                </div>
              </div>
            <div class="card-body">
              <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="bi bi-check-circle me-2"></i>
                  <?php echo htmlspecialchars($success_message); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              
              <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  <?php echo htmlspecialchars($error_message); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>

              <div class="table-responsive">
                <table class="table table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>ID</th>
                      <th>Username</th>
                      <th>Full Name</th>
                      <th>Email</th>
                      <th>Role</th>
                      <th>Organization</th>
                      <th>Status</th>
                      <th>Created</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($users)): ?>
                      <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                          <i class="bi bi-people fs-1 d-block mb-2"></i>
                          No users found. <a href="signup.php">Create the first user</a>
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($users as $user): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($user['id']); ?></td>
                          <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                          </td>
                          <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                          <td><?php echo htmlspecialchars($user['email']); ?></td>
                          <td>
                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                              <?php echo ucfirst($user['role']); ?>
                            </span>
                          </td>
                          <td><?php echo htmlspecialchars($user['organization']); ?></td>
                          <td>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                              <?php echo ucfirst($user['status']); ?>
                            </span>
                          </td>
                          <td>
                            <small class="text-muted">
                              <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </small>
                          </td>
                          <td>
                            <div class="btn-group btn-group-sm" role="group">
                              <button type="button" class="btn btn-outline-primary" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                <i class="bi bi-pencil"></i>
                              </button>
                              <button type="button" class="btn btn-outline-danger" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#deleteUserModal<?php echo $user['id']; ?>">
                                <i class="bi bi-trash"></i>
                              </button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Edit User Modals -->
    <?php foreach ($users as $user): ?>
      <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit User: <?php echo htmlspecialchars($user['username']); ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
              <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                
                <div class="mb-3">
                  <label class="form-label">Full Name</label>
                  <input type="text" class="form-control" name="full_name" 
                         value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control" name="email" 
                         value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Organization</label>
                  <input type="text" class="form-control" name="organization" 
                         value="<?php echo htmlspecialchars($user['organization']); ?>" required>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Phone</label>
                  <input type="tel" class="form-control" name="phone" 
                         value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Delete User Modal -->
      <div class="modal fade" id="deleteUserModal<?php echo $user['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Delete User</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
              <p class="text-danger small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <button type="submit" class="btn btn-danger">Delete User</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>
    
    <script>
      // Initialize Bootstrap dropdowns
      document.addEventListener('DOMContentLoaded', function() {
        // Manual dropdown functionality
        document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
          toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Find the dropdown menu (ul element) within the same dropdown container
            const dropdownContainer = this.closest('.dropdown');
            const dropdownMenu = dropdownContainer.querySelector('.dropdown-menu');
            
            // Force remove any Bootstrap classes and check our show class
            dropdownMenu.classList.remove('show');
            const isOpen = dropdownMenu.classList.contains('show');
            
            console.log('Dropdown container:', dropdownContainer);
            console.log('Dropdown menu:', dropdownMenu);
            console.log('Is open after cleanup:', isOpen);
            
            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
              menu.classList.remove('show');
            });
            
            // Toggle current dropdown
            if (!isOpen) {
              dropdownMenu.classList.add('show');
              console.log('Added show class to dropdown menu');
            } else {
              dropdownMenu.classList.remove('show');
              console.log('Removed show class from dropdown menu');
            }
            
            console.log('Dropdown clicked, menu visible:', !isOpen);
          });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
          if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
              menu.classList.remove('show');
            });
          }
        });
        
        // Sidebar functionality
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const brandText = document.getElementById('brandText');
        const navTitle = document.getElementById('navTitle');
        
        // Toggle sidebar
        function toggleSidebar() {
          sidebar.classList.toggle('collapsed');
          mainContent.classList.toggle('expanded');
          
          // Store sidebar state in localStorage
          const isCollapsed = sidebar.classList.contains('collapsed');
          localStorage.setItem('sidebarCollapsed', isCollapsed);
          
          if (isCollapsed) {
            brandText.style.display = 'none';
            navTitle.style.display = 'none';
          } else {
            brandText.style.display = 'inline';
            navTitle.style.display = 'block';
          }
        }
        
        // Initialize sidebar state from localStorage
        function initializeSidebarState() {
          const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
          if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            brandText.style.display = 'none';
            navTitle.style.display = 'none';
          } else {
            // Default to expanded state
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            brandText.style.display = 'inline';
            navTitle.style.display = 'block';
          }
        }
        
        // Mobile menu toggle
        function toggleMobileMenu() {
          sidebar.classList.toggle('show');
          sidebarOverlay.classList.toggle('show');
        }
        
        // Event listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        mobileMenuToggle.addEventListener('click', toggleMobileMenu);
        sidebarOverlay.addEventListener('click', toggleMobileMenu);
        
        // Close mobile menu on window resize
        window.addEventListener('resize', function() {
          if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
          }
        });
        
        // Update user count
        function updateUserCount() {
          const userCount = document.getElementById('userCount');
          if (userCount) {
            const userRows = document.querySelectorAll('tbody tr');
            userCount.textContent = userRows.length;
          }
        }
        
        // Initialize sidebar state and counts
        initializeSidebarState();
        updateUserCount();
        
        // Refresh button
        document.getElementById('btnRefresh').addEventListener('click', function() {
          location.reload();
        });
      });
    </script>
  </body>
</html>
