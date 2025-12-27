/**
 * Admin Incidents Management JavaScript
 * Handles loading and displaying all incidents for admin management
 */

(function() {
  'use strict';

  const API_URL = '../api/incidents.php';
  
  // DOM Elements
  const incidentsList = document.getElementById('incidentsList');
  const filterStatus = document.getElementById('filterStatus');
  const btnRefresh = document.getElementById('btnRefresh');
  const loadingState = document.getElementById('incidentsLoading');
  const emptyState = document.getElementById('incidentsEmpty');
  
  // Store incidents in memory for quick access
  let allIncidents = [];

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

    // Listen for custom events when incidents are added/updated
  }

  /**
   * Load and display incidents from database API
   */
  async function loadAndDisplayIncidents() {
    try {
      // Show loading state
      if (loadingState) loadingState.style.display = 'flex';
      if (emptyState) emptyState.style.display = 'none';
      if (incidentsList) incidentsList.innerHTML = '';
      
      // Fetch incidents from API
      const statusFilter = filterStatus ? filterStatus.value : 'All';
      const url = statusFilter !== 'All' 
        ? `${API_URL}?status=${encodeURIComponent(statusFilter)}`
        : API_URL;
      
      let incidents = [];
      
      try {
        const response = await fetch(url);
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Check if response is an error object
        if (data.error) {
          throw new Error(data.error);
        }
        
        incidents = Array.isArray(data) ? data : [];
        console.info(`Loaded ${incidents.length} incidents from database`);
      } catch (apiError) {
        console.error('Failed to fetch from database API:', apiError);
        incidents = [];
      }
      
      // Store incidents for quick access
      allIncidents = incidents;
      
      // Filter by status on client side as well (API handles it, but double-check for consistency)
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
          
          // Exact match for other statuses
          return incStatus === filterStatusLower;
        });
      }

      // Sort by newest first (should already be sorted by API, but ensure it)
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
      if (emptyState) emptyState.style.display = 'flex';
      if (incidentsList) incidentsList.innerHTML = '';
      
      // Show error message in empty state
      const emptyStateEl = document.getElementById('incidentsEmpty');
      if (emptyStateEl) {
        emptyStateEl.innerHTML = `
          <div class="mb-4">
            <i class="bi bi-exclamation-triangle" style="font-size: 4rem; color: #dc3545;"></i>
          </div>
          <h5 class="fw-semibold mb-2 text-danger">Error Loading Incidents</h5>
          <p class="text-muted mb-4">Failed to load incidents from the database. Please try again.</p>
          <button class="btn btn-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise me-2"></i> Retry
          </button>
        `;
      }
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
   * Get incident by ID from cached data or fetch if needed
   */
  async function getIncidentById(incidentId) {
    // First try cached data
    let incident = allIncidents.find(inc => inc.id === incidentId);
    
    // If not found, fetch fresh data
    if (!incident) {
      await loadAndDisplayIncidents();
      incident = allIncidents.find(inc => inc.id === incidentId);
    }
    
    return incident;
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
  window.viewReportImage = async function(incidentId) {
    try {
      const incident = await getIncidentById(incidentId);
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
      alert('Error loading incident photo');
    }
  };

  window.viewOnMap = function(lat, lng) {
    // For now, just show coordinates
    alert(`Location: ${lat.toFixed(6)}, ${lng.toFixed(6)}\n\nMap view integration coming soon.`);
  };

  window.viewIncidentDetails = async function(incidentId) {
    try {
      const incident = await getIncidentById(incidentId);
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

  window.downloadIncidentPhoto = async function(incidentId) {
    try {
      const incident = await getIncidentById(incidentId);
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

  window.updateIncidentStatus = async function(incidentId, newStatus) {
    try {
      if (!confirm(`Change status to "${newStatus}"?`)) {
        return;
      }

      // Update via API
      const response = await fetch(API_URL, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          id: incidentId,
          status: newStatus
        })
      });
      
      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
        throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
      }
      
      // Reload incidents to reflect the change
      await loadAndDisplayIncidents();

      // Dispatch events to update other pages and sidebar counts
      window.dispatchEvent(new CustomEvent('incidentUpdated', { 
        detail: { id: incidentId, status: newStatus } 
      }));
      
      // Also dispatch incidentAdded to trigger sidebar count update
      window.dispatchEvent(new CustomEvent('incidentAdded'));
    } catch (error) {
      console.error('Error updating incident status:', error);
      alert('Error updating incident status: ' + error.message);
    }
  };

})();


