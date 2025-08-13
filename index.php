<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MDRRMO | Geotagged Incident Reporting</title>

    <!-- Bootstrap CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
      crossorigin="anonymous"
    />

    <!-- Bootstrap Icons -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    />

    <!-- Leaflet CSS -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />

    <link rel="stylesheet" href="style.css" />
  </head>
  <body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger">
      <div class="container-fluid">
        <span class="navbar-brand d-flex align-items-center gap-2">
          <i class="bi bi-shield-exclamation"></i>
          MDRRMO Incident Desk
        </span>
        <span class="navbar-text small text-white-50">Geotagged Photo Reporting</span>
      </div>
    </nav>

    <main class="container py-3 py-md-4">
      <div class="row g-3">
        <!-- List / Actions Column (moved first) -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center">
              <div class="d-flex align-items-center gap-2 me-auto">
                <i class="bi bi-funnel"></i>
                <select id="filterStatus" class="form-select form-select-sm" style="width: 170px">
                  <option value="All" selected>All statuses</option>
                  <option value="New">New</option>
                  <option value="Dispatched">Dispatched</option>
                  <option value="Resolved">Resolved</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>
              <button class="btn btn-sm btn-outline-success" id="btnExportAll"><i class="bi bi-download"></i> Export</button>
              <button class="btn btn-sm btn-outline-danger" id="btnClearAll"><i class="bi bi-trash"></i> Clear All</button>
            </div>
          </div>

          <div id="incidentList" class="d-grid gap-2"></div>
        </div>

        <!-- Form Column (moved second) -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <div class="d-flex align-items-center justify-content-between">
                <h6 class="mb-0 d-flex align-items-center gap-2">
                  <i class="bi bi-flag"></i> New Incident
                </h6>
                <span class="badge text-bg-secondary" id="clockBadge">--:--</span>
              </div>
            </div>
            <div class="card-body">
              <form id="incidentForm" class="needs-validation" novalidate>
                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <label for="incidentType" class="form-label">Type</label>
                    <select id="incidentType" class="form-select" required>
                      <option value="" selected disabled>Choose...</option>
                      <option value="Fire">Fire</option>
                      <option value="Flood">Flood</option>
                      <option value="Road Accident">Road Accident</option>
                      <option value="Medical">Medical</option>
                      <option value="Landslide">Landslide</option>
                      <option value="Earthquake">Earthquake</option>
                      <option value="Power Outage">Power Outage</option>
                      <option value="Other">Other</option>
                    </select>
                    <div class="invalid-feedback">Please select a type.</div>
                  </div>
                  <div class="col-12 col-md-6">
                    <label for="severity" class="form-label">Severity</label>
                    <select id="severity" class="form-select" required>
                      <option value="" selected disabled>Choose...</option>
                      <option value="Low">Low</option>
                      <option value="Moderate">Moderate</option>
                      <option value="High">High</option>
                      <option value="Critical">Critical</option>
                    </select>
                    <div class="invalid-feedback">Please select severity.</div>
                  </div>
                  <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" class="form-control" rows="3" placeholder="Brief details (what/where/obstructions/injuries)" required></textarea>
                    <div class="invalid-feedback">Please enter a description.</div>
                  </div>
                  <div class="col-12">
                    <label class="form-label d-flex align-items-center gap-2"
                      ><i class="bi bi-camera"></i> Photo (geotag preferred)</label
                    >
                    <input
                      id="photo"
                      type="file"
                      class="form-control"
                      accept="image/*"
                      capture="environment"
                      required
                    />
                    <div class="invalid-feedback">Photo is required.</div>
                    <div class="form-text" id="photoMeta">Awaiting image...</div>
                    <div class="ratio ratio-16x9 mt-2 border rounded overflow-hidden bg-body" id="photoPreviewWrap">
                      <img id="photoPreview" alt="Preview" class="object-fit-cover w-100 h-100 d-none" />
                      <div class="d-flex align-items-center justify-content-center text-muted" id="photoPlaceholder">
                        <div class="text-center small">
                          <i class="bi bi-image fs-3 d-block mb-1"></i>
                          Photo preview
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                      <label class="form-label mb-0 d-flex align-items-center gap-2"
                        ><i class="bi bi-geo-alt"></i> Location</label
                      >
                      <div class="btn-group btn-group-sm" role="group">
                        <button type="button" id="btnUseMyLocation" class="btn btn-outline-primary">
                          <i class="bi bi-crosshair"></i> Use my location
                        </button>
                        <button type="button" id="btnClearLocation" class="btn btn-outline-secondary">
                          <i class="bi bi-x"></i>
                        </button>
                      </div>
                    </div>
                    <div class="row g-2 align-items-center mb-2">
                      <div class="col-6">
                        <input id="lat" class="form-control" placeholder="Latitude" readonly />
                      </div>
                      <div class="col-6">
                        <input id="lng" class="form-control" placeholder="Longitude" readonly />
                      </div>
                    </div>
                    <div id="locationNote" class="small text-muted mb-2">No location yet</div>
                    <div id="map" class="rounded"></div>
                  </div>

                  <div class="col-12 d-grid gap-2 mt-2">
                    <button class="btn btn-danger" id="btnAddIncident" type="submit">
                      <i class="bi bi-plus-circle"></i> Add Incident
                    </button>
                    <button class="btn btn-outline-secondary" id="btnResetForm" type="button">
                      <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="container pb-4 small text-center text-muted">
      <span class="d-inline-flex align-items-center gap-1">
        <i class="bi bi-info-circle"></i> Local-only demo. No database. Data stored in your browser.
      </span>
    </footer>

    <!-- Bootstrap JS -->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
      crossorigin="anonymous"
    ></script>

    <!-- Leaflet JS -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>

    <!-- EXIF reader -->
    <script src="https://cdn.jsdelivr.net/npm/exif-js@2.3.0/exif.min.js"></script>

    <script src="script.js"></script>
  </body>
  </html>



