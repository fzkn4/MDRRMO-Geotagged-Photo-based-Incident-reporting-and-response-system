/**
 * Organization Chart JavaScript
 * Handles personnel management and hierarchical chart display
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'mdrrmo_organization_v1';
  
  // DOM Elements
  const btnRefresh = document.getElementById('btnRefresh');
  const addPersonnelForm = document.getElementById('addPersonnelForm');
  const addPersonnelModal = document.getElementById('addPersonnelModal');
  const addPersonnelModalLabel = document.getElementById('addPersonnelModalLabel');
  const addPersonnelSubmitBtn = addPersonnelForm ? addPersonnelForm.querySelector('button[type="submit"]') : null;
  const personnelNameInput = document.getElementById('personnelName');
  const personnelRoleInput = document.getElementById('personnelRole');
  const personnelPhotoInput = document.getElementById('personnelPhoto');
  const photoPreview = document.getElementById('photoPreview');
  const photoPreviewContainer = document.getElementById('photoPreviewContainer');
  const removePhotoBtn = document.getElementById('removePhoto');
  const isCEOCheckbox = document.getElementById('isCEO');
  const personnelReportsToSelect = document.getElementById('personnelReportsTo');
  const reportsToContainer = document.getElementById('reportsToContainer');
  
  let editingPersonnelId = null; // Track which personnel is being edited
  const orgChartContainer = document.getElementById('orgChartContainer');
  const orgChartEmpty = document.getElementById('orgChartEmpty');
  const orgChartLoading = document.getElementById('orgChartLoading');
  const orgChart = document.getElementById('orgChart');

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    loadAndRenderChart();
    setupEventListeners();
  });

  /**
   * Setup event listeners
   */
  function setupEventListeners() {
    // Refresh button
    if (btnRefresh) {
      btnRefresh.addEventListener('click', function() {
        loadAndRenderChart();
        const icon = btnRefresh.querySelector('i');
        if (icon) {
          icon.classList.add('spinning');
          setTimeout(() => icon.classList.remove('spinning'), 1000);
        }
      });
    }

    // CEO checkbox toggle
    if (isCEOCheckbox) {
      isCEOCheckbox.addEventListener('change', function() {
        if (this.checked) {
          reportsToContainer.style.display = 'none';
          personnelReportsToSelect.value = '';
        } else {
          reportsToContainer.style.display = 'block';
          updateReportsToDropdown();
        }
      });
    }

    // Form submission
    if (addPersonnelForm) {
      addPersonnelForm.addEventListener('submit', handleAddPersonnel);
    }

    // Update dropdown when modal is shown
    if (addPersonnelModal) {
      addPersonnelModal.addEventListener('show.bs.modal', function(e) {
        // Check if this is an edit action (triggered by edit button)
        const editButton = e.relatedTarget;
        if (editButton && editButton.hasAttribute('data-personnel-id')) {
          // Edit mode
          editingPersonnelId = editButton.getAttribute('data-personnel-id');
          loadPersonnelForEdit(editingPersonnelId);
          if (addPersonnelModalLabel) addPersonnelModalLabel.innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Personnel';
          if (addPersonnelSubmitBtn) addPersonnelSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Personnel';
        } else {
          // Add mode
          editingPersonnelId = null;
          updateReportsToDropdown();
          addPersonnelForm.reset();
          reportsToContainer.style.display = 'block';
          if (isCEOCheckbox) isCEOCheckbox.checked = false;
          if (photoPreviewContainer) photoPreviewContainer.style.display = 'none';
          if (personnelPhotoInput) personnelPhotoInput.value = '';
          if (addPersonnelModalLabel) addPersonnelModalLabel.innerHTML = '<i class="bi bi-person-plus me-2"></i>Add Personnel';
          if (addPersonnelSubmitBtn) addPersonnelSubmitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Add Personnel';
        }
      });
    }

    // Handle photo upload preview
    if (personnelPhotoInput) {
      personnelPhotoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          if (file.size > 5 * 1024 * 1024) { // 5MB limit
            alert('Image size must be less than 5MB');
            this.value = '';
            return;
          }
          
          const reader = new FileReader();
          reader.onload = function(event) {
            if (photoPreview) {
              photoPreview.src = event.target.result;
              photoPreview.removeAttribute('data-removed');
            }
            if (photoPreviewContainer) photoPreviewContainer.style.display = 'block';
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // Handle photo removal
    if (removePhotoBtn) {
      removePhotoBtn.addEventListener('click', function() {
        if (personnelPhotoInput) personnelPhotoInput.value = '';
        if (photoPreview) photoPreview.src = '';
        if (photoPreviewContainer) photoPreviewContainer.style.display = 'none';
        // Mark photo as removed (set to empty string to clear it on save)
        if (photoPreview) photoPreview.setAttribute('data-removed', 'true');
      });
    }
  }

  /**
   * Load personnel from localStorage
   */
  function loadPersonnel() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (error) {
      console.error('Error loading personnel:', error);
      return [];
    }
  }

  /**
   * Save personnel to localStorage
   */
  function savePersonnel(personnel) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(personnel));
    } catch (error) {
      console.error('Error saving personnel:', error);
      alert('Error saving personnel data');
    }
  }

  /**
   * Load personnel data for editing
   */
  function loadPersonnelForEdit(personnelId) {
    const personnel = loadPersonnel();
    const person = personnel.find(p => p.id === personnelId);
    
    if (!person) {
      alert('Personnel not found');
      return;
    }

    // Populate form fields
    if (personnelNameInput) personnelNameInput.value = person.name;
    if (personnelRoleInput) personnelRoleInput.value = person.role;
    if (isCEOCheckbox) {
      isCEOCheckbox.checked = person.isCEO;
      if (person.isCEO) {
        reportsToContainer.style.display = 'none';
        if (personnelReportsToSelect) personnelReportsToSelect.value = '';
      } else {
        reportsToContainer.style.display = 'block';
        updateReportsToDropdown();
        if (personnelReportsToSelect && person.reportsTo) {
          personnelReportsToSelect.value = person.reportsTo;
        }
      }
    }

    // Load photo if available
    if (person.photoDataUrl) {
      if (photoPreview) {
        photoPreview.src = person.photoDataUrl;
        photoPreview.removeAttribute('data-removed');
      }
      if (photoPreviewContainer) photoPreviewContainer.style.display = 'block';
    } else {
      if (photoPreview) {
        photoPreview.src = '';
        photoPreview.removeAttribute('data-removed');
      }
      if (photoPreviewContainer) photoPreviewContainer.style.display = 'none';
    }
    if (personnelPhotoInput) personnelPhotoInput.value = '';
  }

  /**
   * Handle add/edit personnel form submission
   */
  function handleAddPersonnel(e) {
    e.preventDefault();
    
    const name = personnelNameInput.value.trim();
    const role = personnelRoleInput.value.trim();
    const isCEO = isCEOCheckbox.checked;
    const reportsTo = isCEO ? null : personnelReportsToSelect.value;
    let photoDataUrl = null;

    if (!name || !role) {
      alert('Please fill in all required fields');
      return;
    }

    const personnel = loadPersonnel();
    let existingPerson = null;
    if (editingPersonnelId) {
      existingPerson = personnel.find(p => p.id === editingPersonnelId);
      if (!existingPerson) {
        alert('Personnel not found');
        return;
      }
      // Keep existing photo if new one not uploaded
      photoDataUrl = existingPerson.photoDataUrl;
    }

    // Handle photo upload - get from preview (already loaded when user selects file)
    if (personnelPhotoInput && personnelPhotoInput.files && personnelPhotoInput.files[0]) {
      const file = personnelPhotoInput.files[0];
      if (file.size > 5 * 1024 * 1024) {
        alert('Image size must be less than 5MB');
        return;
      }
      
      // Photo should already be loaded in preview from the change event
      if (photoPreview && photoPreview.src && photoPreview.src.startsWith('data:')) {
        photoDataUrl = photoPreview.src;
        photoPreview.removeAttribute('data-removed');
      }
    } else if (photoPreview && photoPreview.getAttribute('data-removed') === 'true') {
      // Photo was explicitly removed
      photoDataUrl = null;
    } else if (editingPersonnelId && photoPreview && photoPreview.src && photoPreview.src.startsWith('data:')) {
      // If editing and preview has new data URL, use it
      photoDataUrl = photoPreview.src;
    }

    // Check if CEO already exists (only for new personnel or if changing to CEO)
    if (isCEO) {
      const existingCEO = personnel.find(p => p.isCEO && p.id !== editingPersonnelId);
      if (existingCEO) {
        if (!confirm(`A CEO already exists (${existingCEO.name}). Replace with this person?`)) {
          return;
        }
        // Remove existing CEO's CEO status
        const ceoIndex = personnel.findIndex(p => p.id === existingCEO.id);
        if (ceoIndex !== -1) {
          personnel[ceoIndex].isCEO = false;
        }
      }
    }

    if (editingPersonnelId) {
      // Update existing personnel
      const index = personnel.findIndex(p => p.id === editingPersonnelId);
      if (index !== -1) {
        personnel[index] = {
          ...personnel[index],
          name: name,
          role: role,
          isCEO: isCEO,
          reportsTo: reportsTo || null,
          photoDataUrl: photoDataUrl
        };
        savePersonnel(personnel);
      }
    } else {
      // Create new personnel object
      const newPersonnel = {
        id: generateId(),
        name: name,
        role: role,
        isCEO: isCEO,
        reportsTo: reportsTo || null,
        photoDataUrl: photoDataUrl,
        createdAt: Date.now()
      };

      // Add to storage
      personnel.push(newPersonnel);
      savePersonnel(personnel);
    }

    // Close modal and refresh chart
    const bsModal = bootstrap.Modal.getInstance(addPersonnelModal);
    if (bsModal) bsModal.hide();
    
    editingPersonnelId = null;
    loadAndRenderChart();
  }

  /**
   * Generate unique ID
   */
  function generateId() {
    return 'personnel_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  /**
   * Update reports to dropdown
   */
  function updateReportsToDropdown() {
    const personnel = loadPersonnel();
    personnelReportsToSelect.innerHTML = '<option value="">Select supervisor...</option>';
    
    // Exclude the person being edited (can't report to themselves)
    personnel.forEach(person => {
      if (person.id !== editingPersonnelId) {
        const option = document.createElement('option');
        option.value = person.id;
        option.textContent = `${person.name} - ${person.role}`;
        personnelReportsToSelect.appendChild(option);
      }
    });
  }

  /**
   * Build hierarchical tree structure
   */
  function buildHierarchy(personnel) {
    if (personnel.length === 0) return null;

    // Find root (CEO or top-level personnel without reportsTo)
    let root = personnel.find(p => p.isCEO);
    
    // If no CEO, find someone who doesn't report to anyone
    if (!root) {
      root = personnel.find(p => !p.reportsTo);
    }
    
    if (!root) return null;

    // Build tree recursively
    function buildTree(personId) {
      const person = personnel.find(p => p.id === personId);
      if (!person) return null;

      const children = personnel
        .filter(p => p.reportsTo === personId)
        .map(child => buildTree(child.id))
        .filter(Boolean)
        .sort((a, b) => {
          // Sort by name for consistent display
          return a.name.localeCompare(b.name);
        });

      return {
        ...person,
        children: children.length > 0 ? children : undefined
      };
    }

    return buildTree(root.id);
  }

  /**
   * Render organizational chart
   */
  function renderChart() {
    const personnel = loadPersonnel();
    
    if (personnel.length === 0) {
      orgChartContainer.style.display = 'none';
      orgChartEmpty.style.display = 'block';
      orgChartLoading.style.display = 'none';
      return;
    }

    const tree = buildHierarchy(personnel);
    
    if (!tree) {
      orgChartContainer.style.display = 'none';
      orgChartEmpty.style.display = 'block';
      orgChartLoading.style.display = 'none';
      return;
    }

    orgChartEmpty.style.display = 'none';
    orgChartLoading.style.display = 'none';
    orgChartContainer.style.display = 'block';

    // Clear existing chart
    orgChart.innerHTML = '';

    // Render tree
    renderNode(tree, orgChart, 0);
  }

  /**
   * Render a node in the chart
   */
  function renderNode(node, container, level) {
    const nodeElement = document.createElement('div');
    nodeElement.className = 'org-node';
    nodeElement.setAttribute('data-level', level);
    
    // Node card
    const card = document.createElement('div');
    card.className = 'org-node-card';
    if (node.isCEO) {
      card.classList.add('org-node-ceo');
    }

    // Edit button
    const editButton = document.createElement('button');
    editButton.className = 'org-node-edit-btn';
    editButton.setAttribute('type', 'button');
    editButton.setAttribute('data-bs-toggle', 'modal');
    editButton.setAttribute('data-bs-target', '#addPersonnelModal');
    editButton.setAttribute('data-personnel-id', node.id);
    editButton.setAttribute('title', 'Edit Personnel');
    const editIcon = document.createElement('i');
    editIcon.className = 'bi bi-pencil';
    editButton.appendChild(editIcon);
    card.appendChild(editButton);

    // Photo (if available)
    if (node.photoDataUrl) {
      const photoWrapper = document.createElement('div');
      photoWrapper.className = 'org-node-photo-wrapper';
      const photo = document.createElement('img');
      photo.className = 'org-node-photo';
      photo.src = node.photoDataUrl;
      photo.alt = node.name;
      photoWrapper.appendChild(photo);
      card.appendChild(photoWrapper);
    } else {
      // Placeholder icon if no photo
      const photoWrapper = document.createElement('div');
      photoWrapper.className = 'org-node-photo-wrapper';
      const placeholder = document.createElement('div');
      placeholder.className = 'org-node-photo-placeholder';
      const icon = document.createElement('i');
      icon.className = 'bi bi-person-fill';
      placeholder.appendChild(icon);
      photoWrapper.appendChild(placeholder);
      card.appendChild(photoWrapper);
    }

    // Name
    const name = document.createElement('div');
    name.className = 'org-node-name';
    name.textContent = node.name;
    card.appendChild(name);

    // Role
    const role = document.createElement('div');
    role.className = 'org-node-role';
    role.textContent = node.role;
    card.appendChild(role);

    nodeElement.appendChild(card);

    // Children container
    if (node.children && node.children.length > 0) {
      const childrenContainer = document.createElement('div');
      childrenContainer.className = 'org-children';

      node.children.forEach((child, index) => {
        const childWrapper = document.createElement('div');
        childWrapper.className = 'org-child-wrapper';

        renderNode(child, childWrapper, level + 1);
        childrenContainer.appendChild(childWrapper);
      });

      nodeElement.appendChild(childrenContainer);
    }

    container.appendChild(nodeElement);
  }

  /**
   * Load and render chart
   */
  function loadAndRenderChart() {
    orgChartLoading.style.display = 'block';
    setTimeout(() => {
      renderChart();
    }, 300);
  }

})();

