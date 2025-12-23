/**
 * Sidebar Counts Updater
 * Shared script to update sidebar badge counts across all pages
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'mdrrmo_incidents_v1';
  let initialized = false;
  
  // Determine API path based on current page location
  function getDashboardStatsApi() {
    const path = window.location.pathname;
    if (path.includes('/admin/')) {
      return '../api/dashboard-stats.php';
    }
    return 'api/dashboard-stats.php';
  }

  /**
   * Initialize sidebar counts on page load
   */
  function init() {
    // Prevent multiple initializations
    if (initialized) {
      return;
    }
    
    // Update counts immediately
    updateSidebarCounts();
    
    // Set up event listeners only once
    if (!window.sidebarCountsListenersSetup) {
      // Listen for storage changes (when incidents are updated in another tab)
      window.addEventListener('storage', function(e) {
        if (e.key === STORAGE_KEY) {
          updateSidebarCounts();
        }
      });

      // Listen for custom events when incidents are added/updated
      window.addEventListener('incidentAdded', function() {
        // Small delay to ensure localStorage is updated
        setTimeout(updateSidebarCounts, 50);
      });
      window.addEventListener('incidentUpdated', function() {
        setTimeout(updateSidebarCounts, 50);
      });
      window.addEventListener('userUpdated', function() {
        setTimeout(updateUserCount, 50);
      });
      
      window.sidebarCountsListenersSetup = true;
    }
    
    initialized = true;
  }

  /**
   * Update all sidebar counts
   */
  function updateSidebarCounts() {
    updateIncidentCount();
    updateUserCount();
  }

  /**
   * Update incident count badge
   */
  function updateIncidentCount() {
    try {
      const incidents = loadIncidents();
      // Count pending incidents (New or pending status)
      const pendingCount = incidents.filter(inc => {
        const status = (inc.status || 'New').toLowerCase().trim();
        return status === 'new' || status === 'pending';
      }).length;
      
      const incidentBadge = document.getElementById('incidentCount');
      if (incidentBadge) {
        // Always update, even if other scripts might have changed it
        incidentBadge.textContent = pendingCount;
        // Hide badge if count is 0
        if (pendingCount === 0) {
          incidentBadge.style.display = 'none';
        } else {
          incidentBadge.style.display = 'flex';
        }
      }
    } catch (error) {
      console.error('Error updating incident count:', error);
      const incidentBadge = document.getElementById('incidentCount');
      if (incidentBadge) {
        incidentBadge.textContent = '0';
        incidentBadge.style.display = 'none';
      }
    }
  }

  /**
   * Update user count badge (for admin pages)
   */
  function updateUserCount() {
    // Only update if user is admin (check if userCount element exists)
    const userCountBadge = document.getElementById('userCount');
    if (!userCountBadge) {
      return; // Not an admin page or user count not needed
    }

    // Try to fetch from API first
    fetch(getDashboardStatsApi())
      .then(response => {
        if (!response.ok) {
          throw new Error('Failed to fetch user stats');
        }
        return response.json();
      })
      .then(data => {
        if (userCountBadge) {
          const totalUsers = data.users?.total || 0;
          userCountBadge.textContent = totalUsers;
          // Hide badge if count is 0
          if (totalUsers === 0) {
            userCountBadge.style.display = 'none';
          } else {
            userCountBadge.style.display = 'flex';
          }
        }
      })
      .catch(error => {
        console.error('Error fetching user count:', error);
        // Fallback: try to get from localStorage or set to 0
        if (userCountBadge) {
          userCountBadge.textContent = '0';
        }
      });
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

  // Mark that sidebar-counts.js is loaded
  window.sidebarCountsLoaded = true;

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      init();
      // Also run after a short delay to catch any late DOM changes
      setTimeout(init, 100);
    });
  } else {
    // DOM is already ready
    init();
    // Also run after a short delay to catch any late DOM changes
    setTimeout(init, 100);
  }

  // Also update on page visibility change (when user switches back to tab)
  document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
      updateSidebarCounts();
    }
  });

  // Run update after all scripts have loaded (use requestAnimationFrame for better timing)
  requestAnimationFrame(function() {
    setTimeout(updateSidebarCounts, 200);
  });

  // Expose update function globally for manual updates
  window.updateSidebarCounts = updateSidebarCounts;
  window.updateIncidentCount = updateIncidentCount;
  window.updateUserCount = updateUserCount;

})();

