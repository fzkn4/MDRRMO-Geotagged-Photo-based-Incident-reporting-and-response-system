/**
 * Equipment Management JavaScript
 * Handles equipment inventory management and display
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'mdrrmo_equipment_v1';
  
  // DOM Elements
  const btnRefresh = document.getElementById('btnRefresh');
  const addEquipmentForm = document.getElementById('addEquipmentForm');
  const addEquipmentModal = document.getElementById('addEquipmentModal');
  const equipmentNameInput = document.getElementById('equipmentName');
  const equipmentCountInput = document.getElementById('equipmentCount');
  const equipmentImageInput = document.getElementById('equipmentImage');
  const equipmentImagePreview = document.getElementById('equipmentImagePreview');
  const equipmentImagePreviewContainer = document.getElementById('equipmentImagePreviewContainer');
  const removeEquipmentImageBtn = document.getElementById('removeEquipmentImage');
  const equipmentGrid = document.getElementById('equipmentGrid');
  const equipmentEmpty = document.getElementById('equipmentEmpty');
  const equipmentLoading = document.getElementById('equipmentLoading');
  const totalEquipmentTypes = document.getElementById('totalEquipmentTypes');
  const totalEquipmentCount = document.getElementById('totalEquipmentCount');

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    loadAndDisplayEquipment();
    setupEventListeners();
  });

  /**
   * Setup event listeners
   */
  function setupEventListeners() {
    // Refresh button
    if (btnRefresh) {
      btnRefresh.addEventListener('click', function() {
        loadAndDisplayEquipment();
        const icon = btnRefresh.querySelector('i');
        if (icon) {
          icon.classList.add('spinning');
          setTimeout(() => icon.classList.remove('spinning'), 1000);
        }
      });
    }

    // Form submission
    if (addEquipmentForm) {
      addEquipmentForm.addEventListener('submit', handleAddEquipment);
    }

    // Modal reset when shown
    if (addEquipmentModal) {
      addEquipmentModal.addEventListener('show.bs.modal', function() {
        addEquipmentForm.reset();
        if (equipmentImagePreviewContainer) equipmentImagePreviewContainer.style.display = 'none';
        if (equipmentImagePreview) equipmentImagePreview.src = '';
        if (equipmentImageInput) equipmentImageInput.value = '';
      });
    }

    // Handle image upload preview
    if (equipmentImageInput) {
      equipmentImageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          if (file.size > 5 * 1024 * 1024) { // 5MB limit
            alert('Image size must be less than 5MB');
            this.value = '';
            return;
          }
          
          const reader = new FileReader();
          reader.onload = function(event) {
            if (equipmentImagePreview) equipmentImagePreview.src = event.target.result;
            if (equipmentImagePreviewContainer) equipmentImagePreviewContainer.style.display = 'block';
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // Handle image removal
    if (removeEquipmentImageBtn) {
      removeEquipmentImageBtn.addEventListener('click', function() {
        if (equipmentImageInput) equipmentImageInput.value = '';
        if (equipmentImagePreview) equipmentImagePreview.src = '';
        if (equipmentImagePreviewContainer) equipmentImagePreviewContainer.style.display = 'none';
      });
    }
  }

  /**
   * Load equipment from localStorage
   */
  function loadEquipment() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (error) {
      console.error('Error loading equipment:', error);
      return [];
    }
  }

  /**
   * Save equipment to localStorage
   */
  function saveEquipment(equipment) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(equipment));
    } catch (error) {
      console.error('Error saving equipment:', error);
      alert('Error saving equipment data');
    }
  }

  /**
   * Handle add equipment form submission
   */
  function handleAddEquipment(e) {
    e.preventDefault();
    
    const name = equipmentNameInput.value.trim();
    const count = parseInt(equipmentCountInput.value);

    if (!name || !count || count < 1) {
      alert('Please fill in all required fields with valid values');
      return;
    }

    let imageDataUrl = null;

    // Handle image upload
    if (equipmentImageInput && equipmentImageInput.files && equipmentImageInput.files[0]) {
      const file = equipmentImageInput.files[0];
      if (file.size > 5 * 1024 * 1024) {
        alert('Image size must be less than 5MB');
        return;
      }
      
      // Image should already be loaded in preview from the change event
      if (equipmentImagePreview && equipmentImagePreview.src && equipmentImagePreview.src.startsWith('data:')) {
        imageDataUrl = equipmentImagePreview.src;
      }
    }

    // Check if equipment with same name already exists
    const equipment = loadEquipment();
    const existingIndex = equipment.findIndex(eq => eq.name.toLowerCase() === name.toLowerCase());
    
    if (existingIndex !== -1) {
      if (!confirm(`Equipment "${equipment[existingIndex].name}" already exists. Update the count instead?`)) {
        return;
      }
      // Update existing equipment count
      equipment[existingIndex].count += count;
      if (imageDataUrl) {
        equipment[existingIndex].imageDataUrl = imageDataUrl;
      }
      equipment[existingIndex].updatedAt = Date.now();
      saveEquipment(equipment);
    } else {
      // Create new equipment object
      const newEquipment = {
        id: generateId(),
        name: name,
        count: count,
        imageDataUrl: imageDataUrl,
        createdAt: Date.now(),
        updatedAt: Date.now()
      };

      // Add to storage
      equipment.push(newEquipment);
      saveEquipment(equipment);
    }

    // Close modal and refresh display
    const bsModal = bootstrap.Modal.getInstance(addEquipmentModal);
    if (bsModal) bsModal.hide();
    
    loadAndDisplayEquipment();
  }

  /**
   * Generate unique ID
   */
  function generateId() {
    return 'equipment_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  /**
   * Render equipment grid
   */
  function renderEquipment() {
    const equipment = loadEquipment();
    
    if (equipment.length === 0) {
      equipmentGrid.style.display = 'none';
      equipmentEmpty.style.display = 'block';
      equipmentLoading.style.display = 'none';
      updateStats(equipment);
      return;
    }

    equipmentEmpty.style.display = 'none';
    equipmentLoading.style.display = 'none';
    equipmentGrid.style.display = 'grid';

    // Clear existing grid
    equipmentGrid.innerHTML = '';

    // Sort by name
    const sortedEquipment = [...equipment].sort((a, b) => a.name.localeCompare(b.name));

    // Render each equipment card
    sortedEquipment.forEach(item => {
      const card = createEquipmentCard(item);
      equipmentGrid.appendChild(card);
    });

    updateStats(equipment);
  }

  /**
   * Create equipment card element
   */
  function createEquipmentCard(equipment) {
    const card = document.createElement('div');
    card.className = 'equipment-card';
    card.setAttribute('data-equipment-id', equipment.id);

    // Image
    if (equipment.imageDataUrl) {
      const img = document.createElement('img');
      img.className = 'equipment-card-image';
      img.src = equipment.imageDataUrl;
      img.alt = equipment.name;
      card.appendChild(img);
    } else {
      const placeholder = document.createElement('div');
      placeholder.className = 'equipment-card-placeholder';
      const icon = document.createElement('i');
      icon.className = 'bi bi-tool';
      placeholder.appendChild(icon);
      card.appendChild(placeholder);
    }

    // Card body
    const cardBody = document.createElement('div');
    cardBody.className = 'equipment-card-body';

    // Title
    const title = document.createElement('div');
    title.className = 'equipment-card-title';
    title.textContent = equipment.name;
    cardBody.appendChild(title);

    // Count badge
    const countBadge = document.createElement('div');
    countBadge.className = 'equipment-card-count';
    countBadge.textContent = `${equipment.count} ${equipment.count === 1 ? 'item' : 'items'}`;
    cardBody.appendChild(countBadge);

    card.appendChild(cardBody);

    return card;
  }

  /**
   * Update statistics
   */
  function updateStats(equipment) {
    const totalTypes = equipment.length;
    const totalCount = equipment.reduce((sum, item) => sum + (item.count || 0), 0);

    if (totalEquipmentTypes) totalEquipmentTypes.textContent = totalTypes;
    if (totalEquipmentCount) totalEquipmentCount.textContent = totalCount;
  }

  /**
   * Load and display equipment
   */
  function loadAndDisplayEquipment() {
    equipmentLoading.style.display = 'block';
    setTimeout(() => {
      renderEquipment();
    }, 300);
  }

})();

