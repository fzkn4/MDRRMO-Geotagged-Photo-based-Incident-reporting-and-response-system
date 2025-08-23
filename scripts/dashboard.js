(() => {
  "use strict";

  const $ = (sel) => document.querySelector(sel);
  const $$ = (sel) => Array.from(document.querySelectorAll(sel));

  const form = $("#incidentForm");
  const photoInput = $("#photo");
  const photoMeta = $("#photoMeta");
  const photoPreview = $("#photoPreview");
  const photoPlaceholder = $("#photoPlaceholder");
  const latEl = $("#lat");
  const lngEl = $("#lng");
  const mapNote = $("#locationNote");
  const useMyLocationBtn = $("#btnUseMyLocation");
  const clearLocationBtn = $("#btnClearLocation");
  const listEl = $("#incidentList");
  const filterStatus = $("#filterStatus");
  const exportBtn = $("#btnExportAll");
  const clearAllBtn = $("#btnClearAll");
  const clockBadge = $("#clockBadge");

  const STORAGE_KEY = "mdrrmo_incidents_v1";
  const incidents = loadIncidents();

  let locationMap, locationMarker;

  initClock();
  initLocationMap();
  renderList();

  function initClock() {
    updateClock();
    setInterval(updateClock, 15_000);
  }

  function updateClock() {
    const now = new Date();
    clockBadge.textContent = now.toLocaleString();
  }

  function initLocationMap() {
    locationMap = L.map("locationMap");
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 18,
      attribution: "© OpenStreetMap",
    }).addTo(locationMap);

    // Default to Philippines center
    locationMap.setView([12.8797, 121.774], 5);

    // Add click event to set location
    locationMap.on("click", (e) => {
      setLocation(e.latlng.lat, e.latlng.lng, "map-click");
    });
  }

  function setLocation(lat, lng, source = "") {
    latEl.value = Number(lat).toFixed(6);
    lngEl.value = Number(lng).toFixed(6);
    mapNote.textContent = `Location set ${source ? "via " + source : ""}`;

    // Update map view and marker
    if (locationMap) {
      locationMap.setView([lat, lng], 14);

      // Remove existing marker if any
      if (locationMarker) {
        locationMap.removeLayer(locationMarker);
      }

      // Add new marker
      locationMarker = L.marker([lat, lng]).addTo(locationMap);
    }
  }

  function clearLocation() {
    latEl.value = "";
    lngEl.value = "";
    mapNote.textContent = "No location yet";

    // Clear map marker
    if (locationMarker && locationMap) {
      locationMap.removeLayer(locationMarker);
      locationMarker = null;
    }
  }

  useMyLocationBtn.addEventListener("click", async () => {
    if (!navigator.geolocation) {
      alert("Geolocation not supported in this browser.");
      return;
    }
    useMyLocationBtn.disabled = true;
    useMyLocationBtn.innerHTML =
      '<span class="spinner-border spinner-border-sm"></span> Locating...';
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const { latitude, longitude } = pos.coords;
        setLocation(latitude, longitude, "gps");
        useMyLocationBtn.disabled = false;
        useMyLocationBtn.innerHTML =
          '<i class="bi bi-crosshair"></i> Use my location';
      },
      (err) => {
        alert("Unable to get location: " + err.message);
        useMyLocationBtn.disabled = false;
        useMyLocationBtn.innerHTML =
          '<i class="bi bi-crosshair"></i> Use my location';
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
  });

  clearLocationBtn.addEventListener("click", clearLocation);

  photoInput.addEventListener("change", async (e) => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    previewPhoto(file);
    tryExtractExif(file);
  });

  function previewPhoto(file) {
    const reader = new FileReader();
    reader.onload = () => {
      photoPreview.src = reader.result;
      photoPreview.classList.remove("d-none");
      photoPlaceholder.classList.add("d-none");
    };
    reader.readAsDataURL(file);
  }

  function tryExtractExif(file) {
    photoMeta.textContent = "Reading EXIF...";
    EXIF.getData(file, function () {
      const lat = EXIF.getTag(this, "GPSLatitude");
      const latRef = EXIF.getTag(this, "GPSLatitudeRef");
      const lng = EXIF.getTag(this, "GPSLongitude");
      const lngRef = EXIF.getTag(this, "GPSLongitudeRef");
      const make = EXIF.getTag(this, "Make");
      const model = EXIF.getTag(this, "Model");
      const dateTime =
        EXIF.getTag(this, "DateTimeOriginal") || EXIF.getTag(this, "DateTime");

      let exifInfo = [];
      if (make || model) exifInfo.push([make, model].filter(Boolean).join(" "));
      if (dateTime) exifInfo.push(String(dateTime));

      if (lat && lng && latRef && lngRef) {
        const latDec = dmsToDd(lat, latRef);
        const lngDec = dmsToDd(lng, lngRef);
        setLocation(latDec, lngDec, "photo EXIF");
        photoMeta.textContent = `EXIF: ${exifInfo.join(
          " • "
        )} • Lat ${latDec.toFixed(6)}, Lng ${lngDec.toFixed(6)}`;
      } else {
        photoMeta.textContent = `No GPS EXIF found${
          exifInfo.length ? " • " + exifInfo.join(" • ") : ""
        }`;
      }
    });
  }

  function dmsToDd(dms, ref) {
    // dms is array of rationals: [deg, min, sec]
    const [d, m, s] = dms.map((v) =>
      typeof v === "number" ? v : v.numerator / v.denominator
    );
    let dd = d + m / 60 + s / 3600;
    if (ref === "S" || ref === "W") dd *= -1;
    return dd;
  }

  function loadIncidents() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (_) {
      return [];
    }
  }

  function saveIncidents() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(incidents));
  }

  function uid() {
    return (
      "inc_" +
      Math.random().toString(36).slice(2, 9) +
      Date.now().toString(36).slice(-4)
    );
  }

  function serializeFormToIncident() {
    const type = $("#incidentType").value;
    const severity = $("#severity").value;
    const description = $("#description").value.trim();
    const lat = latEl.value ? Number(latEl.value) : null;
    const lng = lngEl.value ? Number(lngEl.value) : null;
    const file = photoInput.files && photoInput.files[0];
    if (!file) throw new Error("Photo missing");
    // Persist a lightweight resized image (canvas) to reduce storage impact
    return resizeImageToDataURL(file, 1280, 1280).then((dataUrl) => ({
      id: uid(),
      type,
      severity,
      description,
      status: "New",
      createdAt: Date.now(),
      lat,
      lng,
      photoDataUrl: dataUrl,
    }));
  }

  function resizeImageToDataURL(file, maxW, maxH) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      const reader = new FileReader();
      reader.onload = () => {
        img.onload = () => {
          let { width, height } = img;
          const ratio = Math.min(1, maxW / width, maxH / height);
          const canvas = document.createElement("canvas");
          canvas.width = Math.round(width * ratio);
          canvas.height = Math.round(height * ratio);
          const ctx = canvas.getContext("2d");
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          resolve(canvas.toDataURL("image/jpeg", 0.8));
        };
        img.onerror = reject;
        img.src = reader.result;
      };
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  }

  function renderList() {
    const statusFilter = filterStatus.value;
    const items = incidents
      .slice()
      .sort((a, b) => b.createdAt - a.createdAt)
      .filter((x) => statusFilter === "All" || x.status === statusFilter);
    listEl.innerHTML = items.map(renderIncidentCard).join("");
  }

  function renderIncidentCard(inc) {
    const date = new Date(inc.createdAt).toLocaleString();
    const badgeClass = statusToBadge(inc.status);
    const gps =
      inc.lat != null && inc.lng != null
        ? `${inc.lat.toFixed(6)}, ${inc.lng.toFixed(6)}`
        : "No GPS";
    return `
			<div class="card incident-card sev-${inc.severity} shadow-sm">
				<div class="row g-0">
					<div class="col-4 col-sm-3">
						<img src="${inc.photoDataUrl}" alt="${
      inc.type
    } photo" class="w-100 h-100" style="object-fit:cover;min-height:100%"/>
					</div>
					<div class="col-8 col-sm-9">
						<div class="card-body py-2">
							<div class="d-flex align-items-center justify-content-between">
								<div class="d-flex align-items-center gap-2">
									<i class="bi ${typeToIcon(inc.type)}"></i>
									<strong>${escapeHtml(inc.type)}</strong>
									<span class="badge bg-light text-dark">${escapeHtml(inc.severity)}</span>
								</div>
								<span class="badge ${badgeClass} status-badge">${inc.status}</span>
							</div>
							<div class="small text-muted">${date} • ${gps}</div>
							<p class="mb-2 mt-1">${escapeHtml(inc.description)}</p>
							<div class="d-flex flex-wrap gap-2">
								<div class="btn-group btn-group-sm" role="group">
									<button class="btn btn-outline-secondary" data-action="view" data-id="${
                    inc.id
                  }"><i class="bi bi-map"></i> Map</button>
									<button class="btn btn-outline-primary" data-action="dispatch" data-id="${
                    inc.id
                  }"><i class="bi bi-truck"></i> Dispatch</button>
									<button class="btn btn-outline-success" data-action="resolve" data-id="${
                    inc.id
                  }"><i class="bi bi-check2-circle"></i> Resolve</button>
									<button class="btn btn-outline-danger" data-action="cancel" data-id="${
                    inc.id
                  }"><i class="bi bi-x-circle"></i> Cancel</button>
								</div>
								<div class="btn-group btn-group-sm" role="group">
									<button class="btn btn-outline-dark" data-action="download" data-id="${
                    inc.id
                  }"><i class="bi bi-download"></i> Photo</button>
									<button class="btn btn-outline-dark" data-action="copy" data-id="${
                    inc.id
                  }"><i class="bi bi-clipboard"></i> Copy</button>
									<button class="btn btn-outline-danger" data-action="delete" data-id="${
                    inc.id
                  }"><i class="bi bi-trash"></i></button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		`;
  }

  function typeToIcon(type) {
    switch (type) {
      case "Fire":
        return "bi-fire";
      case "Flood":
        return "bi-droplet";
      case "Road Accident":
        return "bi-car-front";
      case "Medical":
        return "bi-heart-pulse";
      case "Landslide":
        return "bi-triangle";
      case "Earthquake":
        return "bi-activity";
      case "Power Outage":
        return "bi-lightning";
      default:
        return "bi-exclamation-octagon";
    }
  }

  function statusToBadge(status) {
    switch (status) {
      case "New":
        return "text-bg-secondary";
      case "Dispatched":
        return "text-bg-primary";
      case "Resolved":
        return "text-bg-success";
      case "Cancelled":
        return "text-bg-danger";
      default:
        return "text-bg-secondary";
    }
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  listEl.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;
    const id = btn.getAttribute("data-id");
    const action = btn.getAttribute("data-action");
    switch (action) {
      case "view":
        viewOnMap(id);
        break;
      case "dispatch":
        updateStatus(id, "Dispatched");
        break;
      case "resolve":
        updateStatus(id, "Resolved");
        break;
      case "cancel":
        updateStatus(id, "Cancelled");
        break;
      case "delete":
        deleteIncident(id);
        break;
      case "download":
        downloadPhoto(id);
        break;
      case "copy":
        copyIncident(id);
        break;
    }
  });

  function viewOnMap(id) {
    const inc = incidents.find((x) => x.id === id);
    if (!inc) return;
    if (inc.lat != null && inc.lng != null) {
      setLocation(inc.lat, inc.lng, inc.status);
    } else {
      alert("No GPS for this report.");
    }
  }

  function updateStatus(id, status) {
    const inc = incidents.find((x) => x.id === id);
    if (!inc) return;
    inc.status = status;
    saveIncidents();
    renderList();
  }

  function deleteIncident(id) {
    const idx = incidents.findIndex((x) => x.id === id);
    if (idx === -1) return;
    if (!confirm("Delete this incident?")) return;
    incidents.splice(idx, 1);
    saveIncidents();
    renderList();
  }

  function downloadPhoto(id) {
    const inc = incidents.find((x) => x.id === id);
    if (!inc) return;
    const a = document.createElement("a");
    a.href = inc.photoDataUrl;
    a.download = `${inc.type}_${id}.jpg`;
    a.click();
  }

  async function copyIncident(id) {
    const inc = incidents.find((x) => x.id === id);
    if (!inc) return;
    const text = [
      `Type: ${inc.type}`,
      `Severity: ${inc.severity}`,
      `Status: ${inc.status}`,
      `When: ${new Date(inc.createdAt).toLocaleString()}`,
      `Location: ${inc.lat != null ? inc.lat.toFixed(6) : "-"}, ${
        inc.lng != null ? inc.lng.toFixed(6) : "-"
      }`,
      `Description: ${inc.description}`,
    ].join("\n");
    try {
      await navigator.clipboard.writeText(text);
      alert("Copied to clipboard");
    } catch (_) {
      alert("Copy failed");
    }
  }

  filterStatus.addEventListener("change", renderList);

  exportBtn.addEventListener("click", () => {
    const blob = new Blob([JSON.stringify(incidents, null, 2)], {
      type: "application/json",
    });
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = `incidents_${new Date()
      .toISOString()
      .replace(/[:.]/g, "-")}.json`;
    a.click();
    URL.revokeObjectURL(a.href);
  });

  clearAllBtn.addEventListener("click", () => {
    if (!incidents.length) return;
    if (!confirm("Clear ALL incidents?")) return;
    incidents.splice(0, incidents.length);
    saveIncidents();
    renderList();
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      return;
    }
    try {
      const inc = await serializeFormToIncident();
      incidents.push(inc);
      saveIncidents();
      form.reset();
      photoPreview.classList.add("d-none");
      photoPlaceholder.classList.remove("d-none");
      clearLocation();
      form.classList.remove("was-validated");
      renderList();
      window.scrollTo({ top: 0, behavior: "smooth" });
    } catch (err) {
      alert(err.message || "Failed to add incident");
    }
  });

  $("#btnResetForm").addEventListener("click", () => {
    form.reset();
    photoPreview.classList.add("d-none");
    photoPlaceholder.classList.remove("d-none");
    clearLocation();
    form.classList.remove("was-validated");
  });
})();

// Initialize Bootstrap dropdowns
document.addEventListener("DOMContentLoaded", function () {
  // Manual dropdown functionality
  document.querySelectorAll(".dropdown-toggle").forEach(function (toggle) {
    toggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      // Find the dropdown menu (ul element) within the same dropdown container
      const dropdownContainer = this.closest(".dropdown");
      const dropdownMenu = dropdownContainer.querySelector(".dropdown-menu");

      // Force remove any Bootstrap classes and check our show class
      dropdownMenu.classList.remove("show");
      const isOpen = dropdownMenu.classList.contains("show");

      console.log("Dropdown container:", dropdownContainer);
      console.log("Dropdown menu:", dropdownMenu);
      console.log("Is open after cleanup:", isOpen);

      // Close all other dropdowns
      document.querySelectorAll(".dropdown-menu.show").forEach(function (menu) {
        menu.classList.remove("show");
      });

      // Toggle current dropdown
      if (!isOpen) {
        dropdownMenu.classList.add("show");
        console.log("Added show class to dropdown menu");
      } else {
        dropdownMenu.classList.remove("show");
        console.log("Removed show class from dropdown menu");
      }

      console.log("Dropdown clicked, menu visible:", !isOpen);
    });
  });

  // Close dropdowns when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown-menu.show").forEach(function (menu) {
        menu.classList.remove("show");
      });
    }
  });

  // Sidebar functionality
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("mainContent");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const mobileMenuToggle = document.getElementById("mobileMenuToggle");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const brandText = document.getElementById("brandText");
  const navTitle = document.getElementById("navTitle");

  // Toggle sidebar
  function toggleSidebar() {
    sidebar.classList.toggle("collapsed");
    mainContent.classList.toggle("expanded");

    // Store sidebar state in localStorage
    const isCollapsed = sidebar.classList.contains("collapsed");
    localStorage.setItem("sidebarCollapsed", isCollapsed);

    if (isCollapsed) {
      brandText.style.display = "none";
      navTitle.style.display = "none";
    } else {
      brandText.style.display = "inline";
      navTitle.style.display = "block";
    }
  }

  // Initialize sidebar state from localStorage
  function initializeSidebarState() {
    const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (isCollapsed) {
      sidebar.classList.add("collapsed");
      mainContent.classList.add("expanded");
      brandText.style.display = "none";
      navTitle.style.display = "none";
    } else {
      // Default to expanded state
      sidebar.classList.remove("collapsed");
      mainContent.classList.remove("expanded");
      brandText.style.display = "inline";
      navTitle.style.display = "block";
    }
  }

  // Mobile menu toggle
  function toggleMobileMenu() {
    sidebar.classList.toggle("show");
    sidebarOverlay.classList.toggle("show");
  }

  // Event listeners
  sidebarToggle.addEventListener("click", toggleSidebar);
  mobileMenuToggle.addEventListener("click", toggleMobileMenu);
  sidebarOverlay.addEventListener("click", toggleMobileMenu);

  // Close mobile menu on window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      sidebar.classList.remove("show");
      sidebarOverlay.classList.remove("show");
    }
  });

  // Update incident count in sidebar
  function updateSidebarCounts() {
    const incidents = JSON.parse(localStorage.getItem("incidents") || "[]");
    const incidentCount = document.getElementById("incidentCount");
    const totalIncidents = document.getElementById("totalIncidents");
    const newIncidents = document.getElementById("newIncidents");
    const resolvedIncidents = document.getElementById("resolvedIncidents");

    if (incidentCount) incidentCount.textContent = incidents.length;
    if (totalIncidents) totalIncidents.textContent = incidents.length;
    if (newIncidents)
      newIncidents.textContent = incidents.filter(
        (i) => i.status === "New"
      ).length;
    if (resolvedIncidents)
      resolvedIncidents.textContent = incidents.filter(
        (i) => i.status === "Resolved"
      ).length;
  }

  // Update user count for admin
  function updateUserCount() {
    const userCount = document.getElementById("userCount");
    const activeUsers = document.getElementById("activeUsers");
    if (userCount || activeUsers) {
      fetch("users.php?action=count")
        .then((response) => response.json())
        .then((data) => {
          if (userCount) userCount.textContent = data.count || 0;
          if (activeUsers) activeUsers.textContent = data.count || 0;
        })
        .catch(() => {
          if (userCount) userCount.textContent = "0";
          if (activeUsers) activeUsers.textContent = "0";
        });
    }
  }

  // Initialize sidebar state and counts
  initializeSidebarState();
  updateSidebarCounts();
  updateUserCount();

  // Refresh button
  document.getElementById("btnRefresh").addEventListener("click", function () {
    updateSidebarCounts();
    updateUserCount();
    location.reload();
  });
});
