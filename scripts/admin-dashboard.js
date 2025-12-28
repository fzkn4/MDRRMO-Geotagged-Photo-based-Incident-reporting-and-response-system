/**
 * Admin Dashboard JavaScript
 * Handles dashboard statistics and pending reports display
 */

(function() {
  'use strict';

  const API_URL = 'api/incidents.php';
  const MAX_PENDING_DISPLAY = 6; // Maximum pending reports to display on dashboard
  
  // Store incidents in memory for quick access
  let allIncidents = [];

  // Initialize dashboard when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    setupEventListeners();
  });

  /**
   * Initialize dashboard - load all statistics
   */
  async function initializeDashboard() {
    await loadDashboardStats();
    await loadPendingReports();
    await updateSidebarCounts();
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

    // Load incident statistics from database
    loadIncidentStats();
  }

  /**
   * Load incident statistics from database API
   */
  async function loadIncidentStats() {
    try {
      const response = await fetch(API_URL);
      if (!response.ok) {
        throw new Error('Failed to fetch incidents');
      }
      const data = await response.json();
      if (data.error) {
        throw new Error(data.error);
      }
      
      const incidents = Array.isArray(data) ? data : [];
      const totalIncidents = incidents.length;
      const pendingIncidents = incidents.filter(inc => {
        const status = (inc.status || 'New').toLowerCase().trim();
        return status === 'new' || status === 'pending';
      }).length;
      
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
   * Load and display pending reports from database API
   */
  async function loadPendingReports() {
    const loadingEl = document.getElementById('pendingReportsLoading');
    const emptyEl = document.getElementById('pendingReportsEmpty');
    const listEl = document.getElementById('pendingReportsList');
    const viewMoreEl = document.getElementById('pendingReportsViewMore');

    try {
      const response = await fetch(API_URL);
      if (!response.ok) {
        throw new Error('Failed to fetch incidents');
      }
      const data = await response.json();
      if (data.error) {
        throw new Error(data.error);
      }
      
      const incidents = Array.isArray(data) ? data : [];
      
      // Store incidents for quick access
      allIncidents = incidents;
      
      const pendingIncidents = incidents
        .filter(inc => {
          const status = (inc.status || 'New').toLowerCase().trim();
          return status === 'new' || status === 'pending';
        })
        .sort((a, b) => (b.createdAt || 0) - (a.createdAt || 0)) // Sort by newest first
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
        const allPending = incidents.filter(inc => {
          const status = (inc.status || 'New').toLowerCase().trim();
          return status === 'new' || status === 'pending';
        });
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
    const typeClass = getTypeClass(incident.type);
    const hasLocation = incident.lat != null && incident.lng != null;

    return `
      <div class="incident-grid-item">
        <div class="incident-card-square hover-lift" onclick="viewIncidentDetails('${incident.id}')" style="cursor: pointer;">
          <!-- Image Section -->
          <div class="incident-card-image-wrapper" onclick="event.stopPropagation(); viewIncidentDetails('${incident.id}')">
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
            <div class="incident-card-actions" onclick="event.stopPropagation();">
              <button class="btn btn-sm btn-outline-dark incident-action-btn" onclick="event.stopPropagation(); downloadIncidentPhoto('${incident.id}')" title="Download">
                <i class="bi bi-download"></i>
              </button>
              <button class="btn btn-sm btn-outline-success incident-action-btn" onclick="event.stopPropagation(); updateIncidentStatus('${incident.id}', 'Approved')" title="Approve">
                <i class="bi bi-check-circle"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger incident-action-btn" onclick="event.stopPropagation(); updateIncidentStatus('${incident.id}', 'Decline')" title="Decline">
                <i class="bi bi-x-circle"></i>
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
  async function updateSidebarCounts() {
    // Delegate to sidebar-counts.js if available
    if (window.updateSidebarCounts && typeof window.updateSidebarCounts === 'function') {
      window.updateSidebarCounts();
    } else {
      // Fallback: update locally from API
      try {
        const response = await fetch(API_URL);
        if (response.ok) {
          const data = await response.json();
          if (!data.error && Array.isArray(data)) {
            const pendingCount = data.filter(inc => {
              const status = (inc.status || 'New').toLowerCase().trim();
              return status === 'new' || status === 'pending';
            }).length;
            
            const incidentBadge = document.getElementById('incidentCount');
            if (incidentBadge) {
              incidentBadge.textContent = pendingCount;
            }
          }
        }
      } catch (error) {
        console.error('Error updating sidebar counts:', error);
      }
    }
  }

  // Global functions for onclick handlers
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

  /**
   * Get incident by ID from cached data or fetch if needed
   */
  async function getIncidentById(incidentId) {
    // First try cached data
    let incident = allIncidents.find(inc => inc.id === incidentId);
    
    // If not found, fetch fresh data
    if (!incident) {
      try {
        const response = await fetch(API_URL);
        if (response.ok) {
          const data = await response.json();
          if (!data.error && Array.isArray(data)) {
            allIncidents = data;
            incident = allIncidents.find(inc => inc.id === incidentId);
          }
        }
      } catch (error) {
        console.error('Error fetching incidents:', error);
      }
    }
    
    return incident;
  }

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
                      <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
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
                      
                      <!-- Status -->
                      <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                        <div class="flex-shrink-0 me-3">
                          <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-flag-fill text-info"></i>
                          </div>
                        </div>
                        <div class="flex-grow-1">
                          <small class="text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Status</small>
                          <div class="fw-semibold" style="color: #212529; font-size: 0.95rem;">
                            <span class="badge ${statusBadge.class}">${escapeHtml(statusBadge.text)}</span>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Location -->
                      ${(incident.lat != null && incident.lng != null) ? `
                        <div class="d-flex align-items-start">
                          <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                              <i class="bi bi-geo-alt-fill text-warning"></i>
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <small class="text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Location</small>
                            <div class="fw-semibold" style="color: #212529; font-size: 0.95rem;">
                              ${incident.lat.toFixed(6)}, ${incident.lng.toFixed(6)}
                            </div>
                          </div>
                        </div>
                      ` : `
                        <div class="d-flex align-items-start">
                          <div class="flex-shrink-0 me-3">
                            <div class="bg-secondary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                              <i class="bi bi-geo-alt text-secondary"></i>
                            </div>
                          </div>
                          <div class="flex-grow-1">
                            <small class="text-muted d-block mb-1" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Location</small>
                            <div class="fw-semibold text-muted" style="font-size: 0.95rem;">
                              Not available
                            </div>
                          </div>
                        </div>
                      `}
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
              ${(incident.status || 'New').toLowerCase() === 'new' || (incident.status || 'New').toLowerCase() === 'pending' ? `
                <button type="button" class="btn btn-outline-success" onclick="updateIncidentStatus('${incident.id}', 'Approved'); bootstrap.Modal.getInstance(document.getElementById('incidentDetailsModal')).hide();">
                  <i class="bi bi-check-circle me-1"></i> Approve
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="updateIncidentStatus('${incident.id}', 'Decline'); bootstrap.Modal.getInstance(document.getElementById('incidentDetailsModal')).hide();">
                  <i class="bi bi-x-circle me-1"></i> Decline
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

  window.updateIncidentStatus = async function(incidentId, newStatus) {
    try {
      if (!confirm(`Change status to "${newStatus}"?`)) {
        return;
      }

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
      
      // Reload dashboard
      await loadPendingReports();
      await loadIncidentStats();

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

