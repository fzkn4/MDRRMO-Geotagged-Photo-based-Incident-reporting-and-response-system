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
   * Render a pending report card (matching incidents page style)
   */
  function renderPendingReportCard(incident) {
    const date = new Date(incident.createdAt || Date.now());
    const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const timeAgo = getTimeAgo(date);
    const statusBadge = getStatusBadge(incident.status || 'New');
    const typeIcon = getTypeIcon(incident.type);
    const severityClass = getSeverityClass(incident.severity);
    const hasLocation = incident.lat != null && incident.lng != null;

    return `
      <div class="incident-grid-item">
        <div class="incident-card-square hover-lift">
          <!-- Image Section -->
          <div class="incident-card-image-wrapper" onclick="viewReportImage('${incident.id}')">
            ${incident.photoDataUrl ? `
              <img src="${incident.photoDataUrl}" 
                   alt="Incident photo" 
                   class="incident-card-image"
                   loading="lazy">
            ` : `
              <div class="incident-card-image-placeholder">
                <i class="${typeIcon}"></i>
              </div>
            `}
            <!-- Status Badge Overlay -->
            <div class="incident-card-status-overlay">
              <span class="badge ${statusBadge.class} incident-status-badge">${escapeHtml(statusBadge.text)}</span>
            </div>
            <!-- Type Icon Overlay -->
            <div class="incident-card-type-overlay">
              <div class="incident-type-icon ${severityClass}">
                <i class="${typeIcon}"></i>
              </div>
            </div>
          </div>
          
          <!-- Content Section -->
          <div class="incident-card-content">
            <!-- Header -->
            <div class="incident-card-header">
              <h6 class="incident-card-title" title="${escapeHtml(incident.type || 'Unknown')}">
                ${escapeHtml(incident.type || 'Unknown')}
              </h6>
              <small class="incident-card-time">${timeAgo}</small>
            </div>
            
            <!-- Description -->
            <p class="incident-card-description" title="${escapeHtml(incident.description || 'No description provided')}">
              ${escapeHtml(incident.description || 'No description provided')}
            </p>
            
            <!-- Metadata -->
            <div class="incident-card-meta">
              <div class="incident-meta-item">
                <i class="bi bi-calendar3"></i>
                <span>${dateStr}</span>
              </div>
              ${hasLocation ? `
                <div class="incident-meta-item">
                  <i class="bi bi-geo-alt-fill"></i>
                  <span>Located</span>
                </div>
              ` : ''}
            </div>
            
            <!-- Actions -->
            <div class="incident-card-actions">
              <button class="btn btn-sm btn-primary incident-action-btn" onclick="window.location.href='admin/incidents.php?id=${incident.id}'" title="View Details">
                <i class="bi bi-eye"></i>
              </button>
              ${hasLocation ? `
                <button class="btn btn-sm btn-outline-secondary incident-action-btn" onclick="viewOnMap(${incident.lat}, ${incident.lng})" title="View on Map">
                  <i class="bi bi-geo-alt"></i>
                </button>
              ` : ''}
              <button class="btn btn-sm btn-outline-success incident-action-btn" onclick="updateIncidentStatus('${incident.id}', 'Dispatched')" title="Dispatch">
                <i class="bi bi-truck"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger incident-action-btn" onclick="updateIncidentStatus('${incident.id}', 'Resolved')" title="Resolve">
                <i class="bi bi-check-circle"></i>
              </button>
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
   * Get status badge class and text
   */
  function getStatusBadge(status) {
    const statusLower = (status || 'New').toLowerCase().trim();
    switch (statusLower) {
      case 'new':
      case 'pending':
        return { class: 'bg-secondary', text: 'New' };
      case 'dispatched':
        return { class: 'bg-primary', text: 'Dispatched' };
      case 'resolved':
        return { class: 'bg-success', text: 'Resolved' };
      case 'cancelled':
      case 'canceled':
        return { class: 'bg-danger', text: 'Cancelled' };
      default:
        return { class: 'bg-secondary', text: status || 'New' };
    }
  }

  /**
   * Get severity CSS class
   */
  function getSeverityClass(severity) {
    if (!severity) return 'bg-secondary';
    const sev = severity.toLowerCase();
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
   * Note: sidebar-counts.js handles the main updates, this is for dashboard-specific stats
   */
  function updateSidebarCounts() {
    // Delegate to sidebar-counts.js if available
    if (window.updateSidebarCounts && typeof window.updateSidebarCounts === 'function') {
      window.updateSidebarCounts();
    } else {
      // Fallback: update locally
      try {
        const incidents = loadIncidents();
        const pendingCount = incidents.filter(inc => {
          const status = (inc.status || 'New').toLowerCase().trim();
          return status === 'new' || status === 'pending';
        }).length;
        
        const incidentBadge = document.getElementById('incidentCount');
        if (incidentBadge) {
          incidentBadge.textContent = pendingCount;
        }
      } catch (error) {
        console.error('Error updating sidebar counts:', error);
      }
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
    // For now, just show coordinates
    alert(`Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}\n\nMap view integration coming soon.`);
  };

  window.viewIncidentDetails = function(incidentId) {
    window.location.href = `admin/incidents.php?id=${incidentId}`;
  };

  window.updateIncidentStatus = function(incidentId, newStatus) {
    try {
      const incidents = loadIncidents();
      const incidentIndex = incidents.findIndex(inc => inc.id === incidentId);
      
      if (incidentIndex === -1) {
        alert('Incident not found');
        return;
      }

      if (!confirm(`Change status to "${newStatus}"?`)) {
        return;
      }

      incidents[incidentIndex].status = newStatus;
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(incidents));
      } catch (error) {
        console.error('Error saving incidents:', error);
      }
      
      // Reload dashboard
      loadPendingReports();
      loadIncidentStats();

      // Dispatch events to update other pages and sidebar counts
      window.dispatchEvent(new CustomEvent('incidentUpdated', { 
        detail: { id: incidentId, status: newStatus } 
      }));
      
      // Also dispatch incidentAdded to trigger sidebar count update
      window.dispatchEvent(new CustomEvent('incidentAdded'));
    } catch (error) {
      console.error('Error updating incident status:', error);
      alert('Error updating incident status');
    }
  };

})();

