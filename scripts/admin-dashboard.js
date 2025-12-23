/**
 * Admin Dashboard JavaScript
 * Handles dashboard statistics and pending reports display
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'mdrrmo_incidents_v1';
  const MAX_PENDING_DISPLAY = 6; // Maximum pending reports to display on dashboard

  // Initialize dashboard when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    setupEventListeners();
  });

  /**
   * Initialize dashboard - load all statistics
   */
  function initializeDashboard() {
    loadDashboardStats();
    loadPendingReports();
    updateSidebarCounts();
  }

  /**
   * Setup event listeners
   */
  function setupEventListeners() {
    const refreshBtn = document.getElementById('btnRefreshDashboard');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function() {
        initializeDashboard();
        // Add visual feedback
        const icon = refreshBtn.querySelector('i');
        if (icon) {
          icon.classList.add('spinning');
          setTimeout(() => icon.classList.remove('spinning'), 1000);
        }
      });
    }

    // Listen for new incidents being added
    window.addEventListener('incidentAdded', function() {
      initializeDashboard();
    });
  }

  /**
   * Load dashboard statistics from API
   */
  async function loadDashboardStats() {
    try {
      const response = await fetch('api/dashboard-stats.php');
      if (!response.ok) {
        throw new Error('Failed to fetch dashboard stats');
      }
      
      const data = await response.json();
      
      // Update user statistics
      updateElement('totalUsers', data.users.total || 0);
      updateElement('activeUsers', data.users.active || 0);
      updateElement('pendingUsers', data.users.pending || 0);
      
      // Update sidebar counts
      const userCountBadge = document.getElementById('userCount');
      if (userCountBadge) {
        userCountBadge.textContent = data.users.total || 0;
      }

    } catch (error) {
      console.error('Error loading dashboard stats:', error);
      // Set defaults on error
      updateElement('totalUsers', 0);
      updateElement('activeUsers', 0);
      updateElement('pendingUsers', 0);
    }

    // Load incident statistics from localStorage
    loadIncidentStats();
  }

  /**
   * Load incident statistics from localStorage
   */
  function loadIncidentStats() {
    try {
      const incidents = loadIncidents();
      const totalIncidents = incidents.length;
      const pendingIncidents = incidents.filter(inc => inc.status === 'New' || inc.status === 'pending').length;
      
      // Update incident statistics
      updateElement('totalIncidents', totalIncidents);
      updateElement('totalPendingIncidents', pendingIncidents);
      
      // Update progress bar
      const progressBar = document.getElementById('pendingIncidentsProgress');
      if (progressBar && totalIncidents > 0) {
        const percentage = Math.min((pendingIncidents / totalIncidents) * 100, 100);
        progressBar.style.width = percentage + '%';
      } else if (progressBar) {
        progressBar.style.width = '0%';
      }

      // Update sidebar incident count
      const incidentCountBadge = document.getElementById('incidentCount');
      if (incidentCountBadge) {
        incidentCountBadge.textContent = pendingIncidents;
      }

    } catch (error) {
      console.error('Error loading incident stats:', error);
      updateElement('totalIncidents', 0);
      updateElement('totalPendingIncidents', 0);
    }
  }

  /**
   * Load and display pending reports
   */
  function loadPendingReports() {
    const loadingEl = document.getElementById('pendingReportsLoading');
    const emptyEl = document.getElementById('pendingReportsEmpty');
    const listEl = document.getElementById('pendingReportsList');
    const viewMoreEl = document.getElementById('pendingReportsViewMore');

    try {
      const incidents = loadIncidents();
      const pendingIncidents = incidents
        .filter(inc => inc.status === 'New' || inc.status === 'pending')
        .sort((a, b) => b.createdAt - a.createdAt) // Sort by newest first
        .slice(0, MAX_PENDING_DISPLAY);

      // Hide loading
      if (loadingEl) loadingEl.style.display = 'none';

      if (pendingIncidents.length === 0) {
        // Show empty state
        if (emptyEl) emptyEl.style.display = 'block';
        if (listEl) listEl.innerHTML = '';
        if (viewMoreEl) viewMoreEl.style.display = 'none';
      } else {
        // Hide empty state
        if (emptyEl) emptyEl.style.display = 'none';
        
        // Render pending reports
        if (listEl) {
          listEl.innerHTML = pendingIncidents.map(incident => renderPendingReportCard(incident)).join('');
        }

        // Show view more if there are more incidents than displayed
        const allPending = incidents.filter(inc => inc.status === 'New' || inc.status === 'pending');
        if (viewMoreEl) {
          viewMoreEl.style.display = allPending.length > MAX_PENDING_DISPLAY ? 'block' : 'none';
        }
      }
    } catch (error) {
      console.error('Error loading pending reports:', error);
      if (loadingEl) loadingEl.style.display = 'none';
      if (emptyEl) emptyEl.style.display = 'block';
      if (listEl) listEl.innerHTML = '';
    }
  }

  /**
   * Render a pending report card
   */
  function renderPendingReportCard(incident) {
    const date = new Date(incident.createdAt);
    const timeAgo = getTimeAgo(date);
    const severityClass = getSeverityClass(incident.severity);
    const typeIcon = getTypeIcon(incident.type);

    return `
      <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100 pending-report-card hover-lift">
          <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between mb-2">
              <div class="d-flex align-items-center gap-2">
                <div class="report-icon-wrapper ${severityClass}">
                  <i class="${typeIcon}"></i>
                </div>
                <div>
                  <h6 class="mb-0 fw-semibold">${escapeHtml(incident.type)}</h6>
                  <small class="text-muted">${timeAgo}</small>
                </div>
              </div>
              <span class="badge ${severityClass}">${escapeHtml(incident.severity)}</span>
            </div>
            
            <p class="card-text text-muted small mb-3" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
              ${escapeHtml(incident.description || 'No description provided')}
            </p>

            ${incident.photoDataUrl ? `
              <div class="mb-3">
                <img src="${incident.photoDataUrl}" 
                     alt="Incident photo" 
                     class="img-fluid rounded pending-report-image"
                     style="max-height: 120px; width: 100%; object-fit: cover; cursor: pointer;"
                     onclick="viewReportImage('${incident.id}')">
              </div>
            ` : ''}

            <div class="d-flex gap-2">
              <a href="admin/incidents.php?id=${incident.id}" class="btn btn-sm btn-primary flex-fill">
                <i class="bi bi-eye me-1"></i> View Details
              </a>
              ${incident.lat && incident.lng ? `
                <button class="btn btn-sm btn-outline-secondary" onclick="viewOnMap(${incident.lat}, ${incident.lng})" title="View on Map">
                  <i class="bi bi-geo-alt"></i>
                </button>
              ` : ''}
            </div>
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Get time ago string
   */
  function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
    
    return date.toLocaleDateString();
  }

  /**
   * Get severity CSS class
   */
  function getSeverityClass(severity) {
    const sev = (severity || '').toLowerCase();
    if (sev === 'critical') return 'bg-danger';
    if (sev === 'high') return 'bg-warning';
    if (sev === 'moderate') return 'bg-info';
    return 'bg-secondary';
  }

  /**
   * Get type icon
   */
  function getTypeIcon(type) {
    const icons = {
      'Fire': 'bi bi-fire',
      'Flood': 'bi bi-droplet',
      'Road Accident': 'bi bi-car-front',
      'Medical': 'bi bi-heart-pulse',
      'Landslide': 'bi bi-triangle',
      'Earthquake': 'bi bi-activity',
      'Power Outage': 'bi bi-lightning',
    };
    return icons[type] || 'bi bi-exclamation-octagon';
  }

  /**
   * Load incidents from localStorage
   */
  function loadIncidents() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (error) {
      console.error('Error loading incidents:', error);
      return [];
    }
  }

  /**
   * Update element text content
   */
  function updateElement(id, value) {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = value;
    }
  }

  /**
   * Escape HTML to prevent XSS
   */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Update sidebar counts
   */
  function updateSidebarCounts() {
    try {
      const incidents = loadIncidents();
      const pendingCount = incidents.filter(inc => inc.status === 'New' || inc.status === 'pending').length;
      
      const incidentBadge = document.getElementById('incidentCount');
      if (incidentBadge) {
        incidentBadge.textContent = pendingCount;
      }
    } catch (error) {
      console.error('Error updating sidebar counts:', error);
    }
  }

  // Global functions for onclick handlers
  window.viewReportImage = function(incidentId) {
    try {
      const incidents = loadIncidents();
      const incident = incidents.find(inc => inc.id === incidentId);
      if (incident && incident.photoDataUrl) {
        // Create and show modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'reportImageModal';
        modal.innerHTML = `
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Incident Photo - ${escapeHtml(incident.type)}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body text-center">
                <img src="${incident.photoDataUrl}" alt="Incident Photo" class="img-fluid rounded">
              </div>
            </div>
          </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        modal.addEventListener('hidden.bs.modal', () => modal.remove());
      }
    } catch (error) {
      console.error('Error viewing report image:', error);
    }
  };

  window.viewOnMap = function(lat, lng) {
    // Redirect to incidents page with map view (you can implement this based on your routing)
    window.location.href = `admin/incidents.php?lat=${lat}&lng=${lng}`;
  };

})();

