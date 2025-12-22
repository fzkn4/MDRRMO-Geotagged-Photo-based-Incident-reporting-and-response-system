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
        if (deleteUser($user_id)) {
            $success_message = 'User deleted successfully.';
        } else {
            $error_message = 'Failed to delete user.';
        }
    } elseif ($action === 'update' && $user_id) {
        $update_data = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'organization' => $_POST['organization'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'status' => $_POST['status'] ?? 'approved',
            'role' => $_POST['role'] ?? 'client'
        ];
        
        if (updateUser($user_id, $update_data)) {
            $success_message = 'User updated successfully.';
        } else {
            $error_message = 'Failed to update user.';
        }
    } elseif ($action === 'approve' && $user_id) {
        if (approveUser($user_id)) {
            $success_message = 'User approved successfully.';
        } else {
            $error_message = 'Failed to approve user.';
        }
    } elseif ($action === 'reject' && $user_id) {
        if (rejectUser($user_id)) {
            $success_message = 'User rejected successfully.';
        } else {
            $error_message = 'Failed to reject user.';
        }
    } elseif ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = trim($_POST['full_name'] ?? '');
        $role = $_POST['role'] ?? 'client';
        $organization = trim($_POST['organization'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        } elseif (userExists($username)) {
            $errors[] = 'Username already exists.';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (emailExists($email)) {
            $errors[] = 'Email already exists.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        
        if (empty($organization)) {
            $errors[] = 'Organization is required.';
        }
        
        if (empty($errors)) {
            $user_data = [
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $full_name,
                'role' => $role,
                'organization' => $organization,
                'phone' => $phone,
                'status' => 'approved' // Admin-created accounts are auto-approved
            ];
            
            if (createUser($user_data)) {
                $success_message = 'User created successfully.';
            } else {
                $error_message = 'Failed to create user. Please try again.';
            }
        } else {
            $error_message = implode('<br>', $errors);
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
          
          <a href="index.php" class="nav-item">
            <div class="nav-icon">
              <i data-filled="fi fi-sr-apps" data-unfilled="fi fi-rr-apps"></i>
            </div>
            <div class="nav-text">Dashboard</div>
          </a>
          
          <a href="incidents.php" class="nav-item">
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
          
          <a href="users.php" class="nav-item active">
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
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                  <i class="bi bi-person-plus me-1"></i>
                  Add New User
                </button>
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
                          No users found. <button type="button" class="btn btn-link p-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#createUserModal">Create the first user</button>
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
                          <td><?php echo htmlspecialchars($user['organization'] ?? ''); ?></td>
                          <td>
                            <?php 
                            $status = $user['status'] ?? 'pending';
                            $statusClass = 'secondary';
                            if ($status === 'approved' || $status === 'active') {
                                $statusClass = 'success';
                            } elseif ($status === 'pending') {
                                $statusClass = 'warning';
                            } elseif ($status === 'inactive') {
                                $statusClass = 'danger';
                            }
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>">
                              <?php echo ucfirst($status); ?>
                            </span>
                          </td>
                          <td>
                            <small class="text-muted">
                              <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                            </small>
                          </td>
                          <td>
                            <div class="btn-group btn-group-sm" role="group">
                              <?php if (($user['status'] ?? 'pending') === 'pending'): ?>
                                <button type="button" class="btn btn-outline-success" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#approveUserModal<?php echo $user['id']; ?>"
                                        title="Approve">
                                  <i class="bi bi-check-circle"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#rejectUserModal<?php echo $user['id']; ?>"
                                        title="Reject">
                                  <i class="bi bi-x-circle"></i>
                                </button>
                              <?php endif; ?>
                              <button type="button" class="btn btn-outline-primary" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#editUserModal<?php echo $user['id']; ?>"
                                      title="Edit">
                                <i class="bi bi-pencil"></i>
                              </button>
                              <button type="button" class="btn btn-outline-danger" 
                                      data-bs-toggle="modal" 
                                      data-bs-target="#deleteUserModal<?php echo $user['id']; ?>"
                                      title="Delete">
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
                         value="<?php echo htmlspecialchars($user['organization'] ?? ''); ?>" required>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Phone</label>
                  <input type="tel" class="form-control" name="phone" 
                         value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" name="status">
                    <option value="pending" <?php echo ($user['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo ($user['status'] ?? 'pending') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="active" <?php echo ($user['status'] ?? 'pending') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($user['status'] ?? 'pending') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Role</label>
                  <select class="form-select" name="role">
                    <option value="client" <?php echo ($user['role'] ?? 'client') === 'client' ? 'selected' : ''; ?>>Client</option>
                    <option value="admin" <?php echo ($user['role'] ?? 'client') === 'admin' ? 'selected' : ''; ?>>Admin</option>
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

      <!-- Approve User Modal -->
      <div class="modal fade" id="approveUserModal<?php echo $user['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Approve User</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to approve user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
              <p class="text-muted small">This will allow the user to log in to the system.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <button type="submit" class="btn btn-success">Approve User</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Reject User Modal -->
      <div class="modal fade" id="rejectUserModal<?php echo $user['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Reject User</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to reject user <strong><?php echo htmlspecialchars($user['username']); ?></strong>?</p>
              <p class="text-danger small">This will mark the user as inactive and prevent them from logging in.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <form method="POST" class="d-inline">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <button type="submit" class="btn btn-danger">Reject User</button>
              </form>
            </div>
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

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Create New User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST">
            <div class="modal-body">
              <input type="hidden" name="action" value="create">
              
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Username <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="username" required>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Email <span class="text-danger">*</span></label>
                  <input type="email" class="form-control" name="email" required>
                </div>
                
                <div class="col-12">
                  <label class="form-label">Full Name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="full_name" required>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Role <span class="text-danger">*</span></label>
                  <select class="form-select" name="role" required>
                    <option value="client" selected>Client</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Organization <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="organization" required>
                </div>
                
                <div class="col-12">
                  <label class="form-label">Phone</label>
                  <input type="tel" class="form-control" name="phone">
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" name="password" required minlength="6">
                  <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="col-md-6">
                  <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" name="confirm_password" required minlength="6">
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-success">Create User</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>
    
    <script src="scripts/users.js"></script>
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
