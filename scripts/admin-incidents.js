/**
 * Admin Incidents Management JavaScript
 * Handles loading and displaying all incidents for admin management
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'mdrrmo_incidents_v1';
  
  // DOM Elements
  const incidentsList = document.getElementById('incidentsList');
  const filterStatus = document.getElementById('filterStatus');
  const btnRefresh = document.getElementById('btnRefresh');
  const loadingState = document.getElementById('incidentsLoading');
  const emptyState = document.getElementById('incidentsEmpty');

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    if (incidentsList) {
      loadAndDisplayIncidents();
      setupEventListeners();
    }
  });

  /**
   * Setup event listeners
   */
  function setupEventListeners() {
    if (filterStatus) {
      filterStatus.addEventListener('change', loadAndDisplayIncidents);
    }

    if (btnRefresh) {
      btnRefresh.addEventListener('click', function() {
        loadAndDisplayIncidents();
        // Add visual feedback
        const icon = btnRefresh.querySelector('i');
        if (icon) {
          icon.classList.add('spinning');
          setTimeout(() => icon.classList.remove('spinning'), 1000);
        }
      });
    }

    // Listen for new incidents being added
    window.addEventListener('incidentAdded', function() {
      loadAndDisplayIncidents();
    });

    // Listen for storage changes (if incidents are updated in another tab)
    window.addEventListener('storage', function(e) {
      if (e.key === STORAGE_KEY) {
        loadAndDisplayIncidents();
      }
    });
  }

  /**
   * Load and display incidents from localStorage
   */
  function loadAndDisplayIncidents() {
    try {
      const incidents = loadIncidents();
      const statusFilter = filterStatus ? filterStatus.value : 'All';
      
      // Filter incidents by status
      let filteredIncidents = incidents;
      if (statusFilter !== 'All') {
        filteredIncidents = incidents.filter(inc => {
          // Normalize status comparison
          const incStatus = (inc.status || 'New').trim().toLowerCase();
          const filterStatusLower = statusFilter.toLowerCase();
          
          // Handle Pending - matches both 'New' and 'pending'
          if (filterStatusLower === 'pending') {
            return incStatus === 'new' || incStatus === 'pending';
          }
          
          // Handle Approved
          if (filterStatusLower === 'approved') {
            return incStatus === 'approved';
          }
          
          // Handle Declined/Decline
          if (filterStatusLower === 'decline' || filterStatusLower === 'declined') {
            return incStatus === 'decline' || incStatus === 'declined';
          }
          
          // Exact match for other statuses (for backward compatibility)
          return incStatus === filterStatusLower;
        });
      }

      // Sort by newest first
      filteredIncidents.sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0));

      // Hide loading state
      if (loadingState) loadingState.style.display = 'none';

      // Update count text
      const countText = document.getElementById('incidentsCountText');
      if (countText) {
        const totalCount = incidents.length;
        const filteredCount = filteredIncidents.length;
        if (statusFilter === 'All') {
          countText.textContent = `${totalCount} ${totalCount === 1 ? 'incident' : 'incidents'} total`;
        } else {
          countText.textContent = `${filteredCount} of ${totalCount} ${statusFilter.toLowerCase()} ${filteredCount === 1 ? 'incident' : 'incidents'}`;
        }
      }

      if (filteredIncidents.length === 0) {
        // Show empty state
        if (emptyState) emptyState.style.display = 'flex';
        if (incidentsList) incidentsList.innerHTML = '';
      } else {
        // Hide empty state
        if (emptyState) emptyState.style.display = 'none';
        
        // Render incidents
        if (incidentsList) {
          incidentsList.innerHTML = filteredIncidents.map(incident => renderIncidentCard(incident)).join('');
        }

        // Attach event listeners to buttons
        attachEventListeners();
      }
    } catch (error) {
      console.error('Error loading incidents:', error);
      if (loadingState) loadingState.style.display = 'none';
      if (emptyState) emptyState.style.display = 'block';
      if (incidentsList) incidentsList.innerHTML = '';
    }
  }

  /**
   * Render an incident card (1:1 square aspect ratio)
   */
  function renderIncidentCard(incident) {
    const date = new Date(incident.createdAt || Date.now());
    const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const timeStr = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const timeAgo = getTimeAgo(date);
    const statusBadge = getStatusBadge(incident.status || 'New');
    const typeIcon = getTypeIcon(incident.type);
    const typeClass = getTypeClass(incident.type);
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
              <div class="incident-type-icon ${typeClass}">
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
              <button class="btn btn-sm btn-outline-dark incident-action-btn" onclick="downloadIncidentPhoto('${incident.id}')" title="Download">
                <i class="bi bi-download"></i>
              </button>
              <button class="btn btn-sm btn-outline-success incident-action-btn" onclick="updateIncidentStatus('${incident.id}', 'Approved')" title="Approve">
                <i class="bi bi-check-circle"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger incident-action-btn" onclick="updateIncidentStatus('${incident.id}', 'Decline')" title="Decline">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Attach event listeners to dynamically generated buttons
   */
  function attachEventListeners() {
    // Event listeners are attached via onclick handlers in the HTML
    // This is intentional to avoid event delegation complexity
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
      case 'approved':
        return { class: 'bg-success', text: 'Approved' };
      case 'decline':
      case 'declined':
        return { class: 'bg-danger', text: 'Declined' };
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
   * Get type CSS class for icon colors
   */
  function getTypeClass(type) {
    if (!type) return 'incident-type-other';
    const typeLower = type.toLowerCase().replace(/\s+/g, '-');
    return `incident-type-${typeLower}`;
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
   * Save incidents to localStorage
   */
  function saveIncidents(incidents) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(incidents));
    } catch (error) {
      console.error('Error saving incidents:', error);
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
   * Global functions for onclick handlers
   */
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
                <h5 class="modal-title">Incident Photo - ${escapeHtml(incident.type || 'Unknown')}</h5>
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
    try {
      const incidents = loadIncidents();
      const incident = incidents.find(inc => inc.id === incidentId);
      if (!incident) {
        alert('Incident not found');
        return;
      }

      const date = new Date(incident.createdAt || Date.now()).toLocaleString();
      const gps = (incident.lat != null && incident.lng != null) 
        ? `${incident.lat.toFixed(6)}, ${incident.lng.toFixed(6)}` 
        : 'Not available';

      const details = `
Incident Details

Type: ${incident.type || 'Unknown'}
Severity: ${incident.severity || 'Not specified'}
Status: ${incident.status || 'New'}
Date: ${date}
Location: ${gps}

Description:
${incident.description || 'No description provided'}
      `;

      alert(details);
    } catch (error) {
      console.error('Error viewing incident details:', error);
      alert('Error loading incident details');
    }
  };

  window.downloadIncidentPhoto = function(incidentId) {
    try {
      const incidents = loadIncidents();
      const incident = incidents.find(inc => inc.id === incidentId);
      if (!incident) {
        alert('Incident not found');
        return;
      }
      if (!incident.photoDataUrl) {
        alert('No photo available for this incident');
        return;
      }
      const a = document.createElement('a');
      a.href = incident.photoDataUrl;
      a.download = `${incident.type || 'incident'}_${incidentId}.jpg`;
      a.click();
    } catch (error) {
      console.error('Error downloading incident photo:', error);
      alert('Error downloading photo');
    }
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
      saveIncidents(incidents);
      loadAndDisplayIncidents();

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


