/**
 * Activities Management JavaScript
 * Handles activity management with multiple image support
 */

(function() {
  'use strict';

  const STORAGE_KEY = 'mdrrmo_activities_v1';
  
  // DOM Elements
  const btnRefresh = document.getElementById('btnRefresh');
  const addActivityForm = document.getElementById('addActivityForm');
  const addActivityModal = document.getElementById('addActivityModal');
  const activityTitleInput = document.getElementById('activityTitle');
  const activityDateInput = document.getElementById('activityDate');
  const activityDescriptionInput = document.getElementById('activityDescription');
  const activityImagesInput = document.getElementById('activityImages');
  const activityImagesPreview = document.getElementById('activityImagesPreview');
  const activityImagesPreviewContainer = document.getElementById('activityImagesPreviewContainer');
  const activitiesList = document.getElementById('activitiesList');
  const activitiesEmpty = document.getElementById('activitiesEmpty');
  const activitiesLoading = document.getElementById('activitiesLoading');
  const imageGalleryModal = document.getElementById('imageGalleryModal');
  const galleryCarouselInner = document.getElementById('galleryCarouselInner');
  const galleryImageCounter = document.getElementById('galleryImageCounter');

  let selectedImages = []; // Store selected images as data URLs

  // Initialize when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    loadAndDisplayActivities();
    setupEventListeners();
  });

  /**
   * Setup event listeners
   */
  function setupEventListeners() {
    // Refresh button
    if (btnRefresh) {
      btnRefresh.addEventListener('click', function() {
        loadAndDisplayActivities();
        const icon = btnRefresh.querySelector('i');
        if (icon) {
          icon.classList.add('spinning');
          setTimeout(() => icon.classList.remove('spinning'), 1000);
        }
      });
    }

    // Form submission
    if (addActivityForm) {
      addActivityForm.addEventListener('submit', handleAddActivity);
    }

    // Modal reset when shown
    if (addActivityModal) {
      addActivityModal.addEventListener('show.bs.modal', function() {
        addActivityForm.reset();
        selectedImages = [];
        if (activityImagesPreviewContainer) activityImagesPreviewContainer.style.display = 'none';
        if (activityImagesPreview) activityImagesPreview.innerHTML = '';
        if (activityImagesInput) activityImagesInput.value = '';
        
        // Set default date/time to current date/time
        if (activityDateInput) {
          const now = new Date();
          // Format: YYYY-MM-DDTHH:mm (datetime-local format)
          const year = now.getFullYear();
          const month = String(now.getMonth() + 1).padStart(2, '0');
          const day = String(now.getDate()).padStart(2, '0');
          const hours = String(now.getHours()).padStart(2, '0');
          const minutes = String(now.getMinutes()).padStart(2, '0');
          activityDateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
      });
    }

    // Handle multiple image uploads
    if (activityImagesInput) {
      activityImagesInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;

        // Check file sizes (before compression, we allow larger files as they'll be compressed)
        const oversizedFiles = files.filter(file => file.size > 10 * 1024 * 1024);
        if (oversizedFiles.length > 0) {
          alert('Some images exceed 10MB limit. Please select smaller images.');
          this.value = '';
          return;
        }
        
        // Limit number of images to prevent storage issues
        if (files.length > 10) {
          alert('Please select a maximum of 10 images at a time to avoid storage issues.');
          this.value = '';
          return;
        }

        // Clear previous previews
        selectedImages = [];
        if (activityImagesPreview) activityImagesPreview.innerHTML = '';

        // Load and compress all images
        let loadedCount = 0;
        files.forEach((file, index) => {
          // Compress image before storing
          compressImage(file, 1280, 1280, 0.8)
            .then(compressedDataUrl => {
              selectedImages.push(compressedDataUrl);
              addImagePreview(compressedDataUrl, index);
              loadedCount++;
              if (loadedCount === files.length) {
                if (activityImagesPreviewContainer) activityImagesPreviewContainer.style.display = 'block';
              }
            })
            .catch(error => {
              console.error('Error compressing image:', error);
              alert('Error processing image. Please try a different image.');
              loadedCount++;
              if (loadedCount === files.length) {
                if (activityImagesPreviewContainer) activityImagesPreviewContainer.style.display = 'block';
              }
            });
        });
      });
    }

    // Update gallery counter when carousel slides
    const galleryCarousel = document.getElementById('galleryCarousel');
    if (galleryCarousel) {
      galleryCarousel.addEventListener('slid.bs.carousel', function(e) {
        updateGalleryCounter(e.to);
      });
    }
  }

  /**
   * Compress and resize image to reduce storage size
   */
  function compressImage(file, maxWidth, maxHeight, quality = 0.8) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      const reader = new FileReader();
      
      reader.onload = () => {
        img.onload = () => {
          let { width, height } = img;
          // Calculate resize ratio to fit within max dimensions
          const ratio = Math.min(1, maxWidth / width, maxHeight / height);
          const canvas = document.createElement('canvas');
          canvas.width = Math.round(width * ratio);
          canvas.height = Math.round(height * ratio);
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          // Convert to JPEG with specified quality (smaller than PNG)
          resolve(canvas.toDataURL('image/jpeg', quality));
        };
        img.onerror = reject;
        img.src = reader.result;
      };
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  /**
   * Check available localStorage space (approximate)
   */
  function checkStorageSpace() {
    try {
      const testKey = '__storage_test__';
      localStorage.setItem(testKey, 'test');
      localStorage.removeItem(testKey);
      return true;
    } catch (e) {
      return e instanceof DOMException && (
        e.code === 22 || // QuotaExceededError
        e.code === 1014 || // NS_ERROR_DOM_QUOTA_REACHED
        e.name === 'QuotaExceededError' ||
        e.name === 'NS_ERROR_DOM_QUOTA_REACHED'
      );
    }
  }

  /**
   * Estimate storage size of activities data
   */
  function estimateStorageSize(activities) {
    try {
      const jsonString = JSON.stringify(activities);
      // Each character in UTF-16 is 2 bytes, but base64 in JSON might be different
      // This is a rough estimate
      return new Blob([jsonString]).size;
    } catch (error) {
      return 0;
    }
  }

  /**
   * Add image preview to preview container
   */
  function addImagePreview(imageDataUrl, index) {
    if (!activityImagesPreview) return;

    const previewItem = document.createElement('div');
    previewItem.className = 'image-preview-item';
    previewItem.setAttribute('data-index', index);

    const img = document.createElement('img');
    img.src = imageDataUrl;
    img.alt = 'Preview';
    previewItem.appendChild(img);

    const removeBtn = document.createElement('button');
    removeBtn.className = 'image-preview-remove';
    removeBtn.type = 'button';
    removeBtn.innerHTML = '<i class="bi bi-x"></i>';
    removeBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      const idx = parseInt(previewItem.getAttribute('data-index'));
      selectedImages.splice(idx, 1);
      previewItem.remove();
      if (selectedImages.length === 0) {
        if (activityImagesPreviewContainer) activityImagesPreviewContainer.style.display = 'none';
        if (activityImagesInput) activityImagesInput.value = '';
      } else {
        // Re-index remaining previews
        updatePreviewIndices();
      }
    });
    previewItem.appendChild(removeBtn);

    activityImagesPreview.appendChild(previewItem);
  }

  /**
   * Update preview indices after removal
   */
  function updatePreviewIndices() {
    const previewItems = activityImagesPreview.querySelectorAll('.image-preview-item');
    previewItems.forEach((item, index) => {
      item.setAttribute('data-index', index);
    });
  }

  /**
   * Load activities from localStorage
   */
  function loadActivities() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (error) {
      console.error('Error loading activities:', error);
      return [];
    }
  }

  /**
   * Save activities to localStorage
   */
  function saveActivities(activities) {
    try {
      const dataString = JSON.stringify(activities);
      const estimatedSize = new Blob([dataString]).size;
      const sizeInMB = (estimatedSize / (1024 * 1024)).toFixed(2);
      
      // Warn if data is getting large (localStorage typically has ~5-10MB limit)
      if (estimatedSize > 4 * 1024 * 1024) {
        if (!confirm(`Warning: Activity data is getting large (${sizeInMB}MB). localStorage may reach its limit soon. Continue?`)) {
          return false;
        }
      }
      
      localStorage.setItem(STORAGE_KEY, dataString);
      return true;
    } catch (error) {
      console.error('Error saving activities:', error);
      if (error.name === 'QuotaExceededError' || error.code === 22) {
        alert('Storage limit reached! Please delete some older activities to free up space.');
      } else {
        alert('Error saving activity data: ' + error.message);
      }
      return false;
    }
  }

  /**
   * Handle add activity form submission
   */
  function handleAddActivity(e) {
    e.preventDefault();
    
    const title = activityTitleInput.value.trim();
    const description = activityDescriptionInput.value.trim();
    const dateValue = activityDateInput.value;

    if (!title) {
      alert('Please enter an activity title');
      return;
    }

    if (!dateValue) {
      alert('Please select the date and time when the activity was conducted');
      return;
    }

    // Convert datetime-local value to timestamp
    const selectedDate = new Date(dateValue);
    const createdAt = selectedDate.getTime();

    // Create new activity object
    const newActivity = {
      id: generateId(),
      title: title,
      description: description || null,
      images: [...selectedImages], // Copy array
      createdAt: createdAt
    };

    // Add to storage
    const activities = loadActivities();
    activities.unshift(newActivity); // Add to beginning (newest first)
    
    const saved = saveActivities(activities);
    if (!saved) {
      // Don't close modal if save failed
      return;
    }

    // Close modal and refresh display
    const bsModal = bootstrap.Modal.getInstance(addActivityModal);
    if (bsModal) bsModal.hide();
    
    loadAndDisplayActivities();
  }

  /**
   * Generate unique ID
   */
  function generateId() {
    return 'activity_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  /**
   * Render activities list
   */
  function renderActivities() {
    const activities = loadActivities();
    
    if (activities.length === 0) {
      activitiesList.style.display = 'none';
      activitiesEmpty.style.display = 'block';
      activitiesLoading.style.display = 'none';
      return;
    }

    activitiesEmpty.style.display = 'none';
    activitiesLoading.style.display = 'none';
    activitiesList.style.display = 'flex';

    // Clear existing list
    activitiesList.innerHTML = '';

    // Render each activity card
    activities.forEach(activity => {
      const card = createActivityCard(activity);
      activitiesList.appendChild(card);
    });
  }

  /**
   * Create activity card element
   */
  function createActivityCard(activity) {
    const card = document.createElement('div');
    card.className = 'activity-card';

    // Card header
    const header = document.createElement('div');
    header.className = 'activity-card-header';
    
    const title = document.createElement('div');
    title.className = 'activity-card-title';
    title.textContent = activity.title;
    header.appendChild(title);

    const date = document.createElement('div');
    date.className = 'activity-card-date';
    const dateObj = new Date(activity.createdAt);
    date.textContent = dateObj.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'long', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    header.appendChild(date);

    card.appendChild(header);

    // Card body
    const body = document.createElement('div');
    body.className = 'activity-card-body';

    // Description
    if (activity.description) {
      const description = document.createElement('div');
      description.className = 'activity-card-description';
      description.textContent = activity.description;
      body.appendChild(description);
    }

    // Image gallery
    if (activity.images && activity.images.length > 0) {
      const gallery = document.createElement('div');
      gallery.className = 'activity-images-gallery';

      activity.images.forEach((imageUrl, index) => {
        const thumb = document.createElement('div');
        thumb.className = 'activity-image-thumb';
        thumb.setAttribute('data-activity-id', activity.id);
        thumb.setAttribute('data-image-index', index);
        thumb.addEventListener('click', function() {
          openImageGallery(activity.images, index);
        });

        const img = document.createElement('img');
        img.src = imageUrl;
        img.alt = `Activity image ${index + 1}`;
        thumb.appendChild(img);

        const overlay = document.createElement('div');
        overlay.className = 'activity-image-overlay';
        const icon = document.createElement('i');
        icon.className = 'bi bi-zoom-in';
        overlay.appendChild(icon);
        thumb.appendChild(overlay);

        gallery.appendChild(thumb);
      });

      body.appendChild(gallery);
    }

    card.appendChild(body);

    return card;
  }

  /**
   * Open image gallery modal
   */
  function openImageGallery(images, startIndex) {
    if (!images || images.length === 0) return;

    // Clear carousel
    galleryCarouselInner.innerHTML = '';

    // Add carousel items
    images.forEach((imageUrl, index) => {
      const item = document.createElement('div');
      item.className = 'carousel-item' + (index === startIndex ? ' active' : '');
      
      const img = document.createElement('img');
      img.src = imageUrl;
      img.className = 'd-block w-100';
      img.alt = `Activity image ${index + 1}`;
      item.appendChild(img);

      galleryCarouselInner.appendChild(item);
    });

    // Update counter
    updateGalleryCounter(startIndex);

    // Show modal
    const bsModal = new bootstrap.Modal(imageGalleryModal);
    bsModal.show();
  }

  /**
   * Update gallery image counter
   */
  function updateGalleryCounter(currentIndex) {
    const total = galleryCarouselInner.querySelectorAll('.carousel-item').length;
    if (galleryImageCounter) {
      galleryImageCounter.textContent = `${(currentIndex || 0) + 1} / ${total}`;
    }
  }

  /**
   * Load and display activities
   */
  function loadAndDisplayActivities() {
    activitiesLoading.style.display = 'block';
    setTimeout(() => {
      renderActivities();
    }, 300);
  }

})();

