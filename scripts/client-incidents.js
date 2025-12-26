/**
 * Client Incidents Management JavaScript
 * Handles loading and displaying ALL incidents (view-only)
 * Fetches incidents from database via API
 */

(function() {
  'use strict';

  const API_URL = '../api/incidents.php';
  
  // Get current user from PHP
  const CURRENT_USER = document.body.getAttribute('data-current-user') || 
                       (window.CURRENT_USER || '');
  
  // DOM Elements
  const incidentsList = document.getElementById('incidentsList');
  const filterStatus = document.getElementById('filterStatus');
  const btnRefresh = document.getElementById('btnRefresh');
  const loadingState = document.getElementById('incidentsLoading');
  const emptyState = document.getElementById('incidentsEmpty');
  
  // Store incidents in memory for quick access
  let allUserIncidents = [];

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

    // Listen for new incidents being added (refresh when incident is added)
    // This will trigger a refresh from the API
  }

  /**
   * Load and display incidents from database API (ALL incidents, not filtered by user)
   * Falls back to localStorage if database is empty
   */
  async function loadAndDisplayIncidents() {
    try {
      // Show loading state
      if (loadingState) loadingState.style.display = 'flex';
      if (emptyState) emptyState.style.display = 'none';
      if (incidentsList) incidentsList.innerHTML = '';
      
      // Fetch incidents from API (now returns ALL incidents for clients)
      const statusFilter = filterStatus ? filterStatus.value : 'All';
      const url = statusFilter !== 'All' 
        ? `${API_URL}?status=${encodeURIComponent(statusFilter)}`
        : API_URL;
      
      let allIncidents = [];
      const STORAGE_KEY = 'mdrrmo_incidents_v1';
      
      // Try to fetch from database API first
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
        
        allIncidents = Array.isArray(data) ? data : [];
        console.info(`Loaded ${allIncidents.length} incidents from database`);
      } catch (apiError) {
        console.warn('Failed to fetch from database API:', apiError);
        allIncidents = [];
      }
      
      // Also load from localStorage and merge (for backward compatibility)
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (raw) {
          const localStorageIncidents = JSON.parse(raw);
          
          // Merge with database incidents, avoiding duplicates
          const dbIds = new Set(allIncidents.map(inc => inc.id));
          const newIncidents = localStorageIncidents.filter(inc => !dbIds.has(inc.id));
          
          if (newIncidents.length > 0) {
            console.info(`Found ${newIncidents.length} additional incidents in localStorage`);
            allIncidents = [...allIncidents, ...newIncidents];
            
            // Try to migrate these to database in the background (non-blocking)
            migrateIncidentsToDatabase(newIncidents).catch(err => {
              console.warn('Failed to migrate incidents to database:', err);
            });
          }
        }
      } catch (localStorageError) {
        console.warn('Failed to load from localStorage:', localStorageError);
      }
      
      // If no incidents found in database, try localStorage as fallback
      if (allIncidents.length === 0) {
        try {
          const raw = localStorage.getItem(STORAGE_KEY);
          if (raw) {
            allIncidents = JSON.parse(raw);
            console.info(`Loaded ${allIncidents.length} incidents from localStorage as fallback`);
          }
        } catch (localStorageError) {
          console.error('Failed to load from localStorage:', localStorageError);
        }
      }
      
      // Store incidents for quick access in modal functions
      allUserIncidents = allIncidents;
      
      // Filter by status on client side as well (API handles it, but double-check for consistency)
      let filteredIncidents = allIncidents;
      if (statusFilter !== 'All') {
        filteredIncidents = allIncidents.filter(inc => {
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
          
          // Handle other statuses
          if (filterStatusLower === 'dispatched') {
            return incStatus === 'dispatched';
          }
          
          if (filterStatusLower === 'resolved') {
            return incStatus === 'resolved';
          }
          
          if (filterStatusLower === 'cancelled' || filterStatusLower === 'canceled') {
            return incStatus === 'cancelled' || incStatus === 'canceled';
          }
          
          // Exact match for other statuses
          return incStatus === filterStatusLower;
        });
      }

      // Sort by newest first (should already be sorted by API, but ensure it)
      filteredIncidents.sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0));

      // Hide loading state
      if (loadingState) loadingState.style.display = 'none';

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
   * Render an incident card (read-only view)
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
            
            <!-- Actions (View and Download only - no approve/decline) -->
            <div class="incident-card-actions">
              <button class="btn btn-sm btn-outline-primary incident-action-btn" onclick="viewIncidentDetails('${incident.id}')" title="View Details">
                <i class="bi bi-eye me-1"></i>
                <span>View</span>
              </button>
              ${incident.photoDataUrl ? `
                <button class="btn btn-sm btn-outline-dark incident-action-btn" onclick="downloadIncidentPhoto('${incident.id}')" title="Download Photo">
                  <i class="bi bi-download me-1"></i>
                  <span>Download</span>
                </button>
              ` : ''}
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
   * Get current user (try multiple methods)
   */
  function getCurrentUser() {
    // Try to get from meta tag or global variable
    const metaUser = document.querySelector('meta[name="current-user"]');
    if (metaUser) {
      return metaUser.getAttribute('content');
    }
    
    // Try to get from data attribute
    if (CURRENT_USER) {
      return CURRENT_USER;
    }
    
    // Try to get from window variable (set by PHP)
    if (window.CURRENT_USER) {
      return window.CURRENT_USER;
    }
    
    // Fallback: try to extract from page
    const userElement = document.querySelector('[data-current-user]');
    if (userElement) {
      return userElement.getAttribute('data-current-user');
    }
    
    console.warn('Current user not found, using empty string');
    return '';
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
   * Fetch incidents from API (cached in memory)
   * Returns the cached incidents, or fetches fresh if needed
   */
  async function fetchIncidents() {
    try {
      const response = await fetch(API_URL);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      const incidents = await response.json();
      allUserIncidents = incidents;
      return incidents;
    } catch (error) {
      console.error('Error fetching incidents:', error);
      return allUserIncidents; // Return cached if fetch fails
    }
  }
  
  /**
   * Migrate incidents from localStorage to database
   */
  async function migrateIncidentsToDatabase(incidents) {
    if (!incidents || incidents.length === 0) return;
    
    const API_URL = '../api/incidents.php';
    
    for (const incident of incidents) {
      try {
        const response = await fetch(API_URL, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(incident)
        });
        
        if (response.ok) {
          console.info(`Migrated incident ${incident.id} to database`);
        } else {
          console.warn(`Failed to migrate incident ${incident.id}`);
        }
      } catch (error) {
        console.warn(`Error migrating incident ${incident.id}:`, error);
      }
    }
  }
  
  /**
   * Get incident by ID from cached data or fetch if needed
   */
  async function getIncidentById(incidentId) {
    // First try cached data
    let incident = allUserIncidents.find(inc => inc.id === incidentId);
    
    // If not found, fetch fresh data
    if (!incident) {
      await fetchIncidents();
      incident = allUserIncidents.find(inc => inc.id === incidentId);
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

  window.viewIncidentDetails = async function(incidentId) {
    try {
      const incident = await getIncidentById(incidentId);
      if (!incident) {
        alert('Incident not found');
        return;
      }

      const date = new Date(incident.createdAt || Date.now()).toLocaleString();
      const statusBadge = getStatusBadge(incident.status || 'New');
      
      // Get type icon
      const typeIcon = getTypeIcon(incident.type);
      const typeClass = getTypeClass(incident.type);
      
      // Create a nice modal for details
      const modal = document.createElement('div');
      modal.className = 'modal fade';
      modal.id = 'incidentDetailsModal';
      modal.innerHTML = `
        <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content border-0 shadow-lg" style="overflow: hidden;">
            <!-- Enhanced Header -->
            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 1.5rem 1.75rem;">
              <div class="d-flex align-items-center w-100">
                <div class="flex-grow-1 d-flex align-items-center gap-3 pb-3">
                  <h5 class="modal-title mb-0 fw-bold" style="font-size: 1.5rem; color: #212529;">
                    ${escapeHtml(incident.type || 'Unknown Incident')}
                  </h5>
                  <span class="badge ${statusBadge.class} px-3 py-2" style="font-size: 0.85rem; font-weight: 600;">
                    ${escapeHtml(statusBadge.text)}
                  </span>
                </div>
                <button type="button" class="btn-close pb-3" data-bs-dismiss="modal" style="opacity: 0.7;"></button>
              </div>
            </div>
            
            <div class="modal-body" style="padding: 1.75rem;">
              <div class="row g-4">
                <!-- Left Column: Information -->
                <div class="col-lg-6">
                  <!-- Incident Information Card -->
                  <div class="card border-0 shadow-sm mb-4" style="background: #f8f9fa;">
                    <div class="card-body p-4">
                      <h6 class="card-title fw-bold mb-3 d-flex align-items-center" style="color: #495057;">
                        <i class="bi bi-info-circle-fill me-2 text-primary"></i>
                        Incident Information
                      </h6>
                      
                      <!-- Type -->
                      <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                        <div class="flex-shrink-0 me-3">
                          <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-tag-fill text-primary"></i>
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <small class="text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Type</small>
                          <div class="fw-semibold" style="color: #212529; font-size: 0.95rem;">
                            ${escapeHtml(incident.type || 'Unknown')}
                          </div>
                        </div>
                      </div>
                      
                      <!-- Date & Time -->
                      <div class="d-flex align-items-start">
                        <div class="flex-shrink-0 me-3">
                          <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-calendar3-fill text-success"></i>
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <small class="text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Date & Time</small>
                          <div class="fw-semibold" style="color: #212529; font-size: 0.95rem;">
                            ${escapeHtml(date)}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Description Card -->
                  <div class="card border-0 shadow-sm" style="background: #f8f9fa;">
                    <div class="card-body p-4">
                      <h6 class="card-title fw-bold mb-3 d-flex align-items-center" style="color: #495057;">
                        <i class="bi bi-file-text-fill me-2 text-info"></i>
                        Description
                      </h6>
                      <p class="mb-0" style="color: #495057; line-height: 1.6; font-size: 0.95rem;">
                        ${escapeHtml(incident.description || 'No description provided')}
                      </p>
                    </div>
                  </div>
                </div>
                
                <!-- Right Column: Photo -->
                <div class="col-lg-6">
                  ${incident.photoDataUrl ? `
                    <div class="card border-0 shadow-sm h-100" style="background: #f8f9fa;">
                      <div class="card-body p-4">
                        <h6 class="card-title fw-bold mb-3 d-flex align-items-center" style="color: #495057;">
                          <i class="bi bi-image-fill me-2 text-danger"></i>
                          Incident Photo
                        </h6>
                        <div class="text-center" style="background: white; border-radius: 12px; padding: 1rem; box-shadow: inset 0 2px 8px rgba(0,0,0,0.05);">
                          <img src="${incident.photoDataUrl}" 
                               alt="Incident Photo" 
                               class="img-fluid rounded shadow-sm" 
                               style="max-height: 450px; width: auto; cursor: pointer; transition: transform 0.3s ease;"
                               onclick="viewReportImage('${incident.id}')"
                               onmouseover="this.style.transform='scale(1.02)'"
                               onmouseout="this.style.transform='scale(1)'">
                        </div>
                      </div>
                    </div>
                  ` : `
                    <div class="card border-0 shadow-sm h-100" style="background: #f8f9fa;">
                      <div class="card-body p-4 d-flex align-items-center justify-content-center" style="min-height: 300px;">
                        <div class="text-center text-muted">
                          <i class="bi bi-image" style="font-size: 4rem; opacity: 0.3;"></i>
                          <p class="mt-3 mb-0">No photo available</p>
                        </div>
                      </div>
                    </div>
                  `}
                </div>
              </div>
            </div>
            
            <div class="modal-footer border-top bg-light" style="padding: 1rem 1.75rem;">
              ${incident.photoDataUrl ? `
                <button type="button" class="btn btn-outline-danger" onclick="downloadIncidentPhoto('${incident.id}'); bootstrap.Modal.getInstance(document.getElementById('incidentDetailsModal')).hide();">
                  <i class="bi bi-download me-1"></i> Download Photo
                </button>
              ` : ''}
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bi bi-x-circle me-1"></i> Close
              </button>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(modal);
      const bsModal = new bootstrap.Modal(modal);
      bsModal.show();
      modal.addEventListener('hidden.bs.modal', () => modal.remove());
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

})();

