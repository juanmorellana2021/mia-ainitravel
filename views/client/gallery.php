<?php
/**
 * mia/views/client/gallery.php — Business photo gallery (standalone page)
 */
$base         = App::basePath();
$pageTitle    = 'Galería — Mia';
$pageTopTitle = 'Galería de fotos';
$activeNav    = 'gallery';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>
<!-- ── Main content ──────────────────────────────────────────────────────────── -->
<div class="mc-main-content">
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title"><?= $pageTopTitle ?></h1>
            <p class="mc-page-subtitle">Sube fotos de tu negocio y agrega nombre, descripción y precio a cada una. Mia las mostrará a tus clientes por WhatsApp.</p>
        </div>
        <label class="btn btn-success fw-semibold" id="photoUploadLabel" style="cursor:pointer">
            <i class="bi bi-cloud-upload me-1"></i> Subir fotos
            <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/webp" multiple hidden>
        </label>
    </div>

    <?php if ($saved): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <span>Foto actualizada correctamente.</span>
    </div>
    <?php endif; ?>

    <!-- Drag-and-drop zone -->
    <div id="dropZone" class="drop-zone mb-4">
        <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem;opacity:.5"></i>
        <p class="mb-1 fw-semibold">Arrastra y suelta tus imágenes aquí</p>
        <p class="text-muted small mb-0">o haz clic en "Subir fotos" — JPEG, PNG, WebP (máx. 5 MB c/u)</p>
    </div>

    <!-- Upload progress -->
    <div id="photoUploadProgress" class="d-none mb-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <div class="progress flex-grow-1" style="height:6px">
                <div id="uploadProgressBar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width:0%"></div>
            </div>
            <small id="uploadCountLabel" class="text-muted text-nowrap">0/0</small>
        </div>
        <small id="uploadStatusLabel" class="text-muted">Subiendo...</small>
    </div>
    <div id="photoUploadError" class="alert alert-danger d-none py-2 small mb-3"></div>

    <!-- Photo cards grid -->
    <div id="photoGrid" class="row g-3"></div>
    <p id="photoEmpty" class="text-muted text-center py-5 d-none">
        <i class="bi bi-images" style="font-size:2.5rem;opacity:.3"></i><br>
        No hay fotos aún. ¡Sube la primera para que Mia las comparta con tus clientes!
    </p>
</div>

<style>
.gallery-card {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow .2s;
}
.gallery-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.25); }
.gallery-card img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
    display: block;
}
.gallery-card .card-body { padding: 12px 14px; }
.gallery-card .form-control.mc-form-control {
    font-size: .8rem;
    padding: 5px 8px;
}
.gallery-card .form-label {
    font-size: .7rem;
    margin-bottom: 2px;
    opacity: .7;
}
.gallery-card .btn-row {
    display: flex;
    gap: 6px;
    margin-top: 8px;
}
.gallery-card .btn-row .btn { font-size: .75rem; padding: 3px 10px; }
.gallery-saved {
    font-size: .7rem;
    color: #25d366;
    opacity: 0;
    transition: opacity .3s;
}
.gallery-saved.show { opacity: 1; }
/* Drag-and-drop zone */
.drop-zone {
    border: 2px dashed rgba(255,255,255,.15);
    border-radius: 14px;
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.drop-zone:hover,
.drop-zone.drag-over {
    border-color: #25d366;
    background: rgba(37,211,102,.06);
}
.drop-zone.drag-over i { opacity: 1; color: #25d366; }
</style>

<script>
(function(){
    const BASE       = '<?= $base ?>';
    const CSRF       = '<?= App::csrfToken() ?>';
    const grid       = document.getElementById('photoGrid');
    const emptyMsg   = document.getElementById('photoEmpty');
    const progress   = document.getElementById('photoUploadProgress');
    const errBox     = document.getElementById('photoUploadError');
    const fileInput  = document.getElementById('photoFileInput');
    const uploadLabel= document.getElementById('photoUploadLabel');

    function escHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function renderGrid(photos){
        grid.innerHTML = '';
        if (!photos.length){ emptyMsg.classList.remove('d-none'); return; }
        emptyMsg.classList.add('d-none');

        photos.forEach(function(p){
            var col = document.createElement('div');
            col.className = 'col-12 col-sm-6 col-lg-4';
            col.dataset.id = p.id;
            col.innerHTML =
                '<div class="gallery-card">' +
                    '<div class="position-relative">' +
                        '<img src="' + escHtml(p.url) + '" alt="' + escHtml(p.photo_name || p.caption || '') + '">' +
                        '<button class="photo-del-btn btn btn-sm position-absolute top-0 end-0 m-2" ' +
                                'style="background:rgba(220,53,69,0.9);border:none;color:#fff;border-radius:8px;padding:4px 8px" ' +
                                'title="Eliminar foto" data-id="' + p.id + '">' +
                            '<i class="bi bi-trash3"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="card-body">' +
                        (!p.photo_name ? '<div class="photo-no-name-warn d-flex align-items-center gap-1 mb-2 px-2 py-1 rounded" style="background:rgba(255,193,7,.13);font-size:.72rem;color:#ffc107"><i class="bi bi-exclamation-triangle-fill"></i> Agrega un nombre para que Mia describa esta foto</div>' : '') +
                        '<div class="mb-2">' +
                            '<label class="form-label">Nombre</label>' +
                            '<input type="text" class="form-control mc-form-control photo-field" data-field="photo_name" ' +
                                   'value="' + escHtml(p.photo_name || '') + '" placeholder="ej: Suite Deluxe" maxlength="150">' +
                        '</div>' +
                        '<div class="mb-2">' +
                            '<label class="form-label">Descripción</label>' +
                            '<textarea class="form-control mc-form-control photo-field" data-field="description" ' +
                                      'rows="2" placeholder="ej: Habitación con vista al mar, cama king..." maxlength="500">' + escHtml(p.description || '') + '</textarea>' +
                        '</div>' +
                        '<div class="mb-2">' +
                            '<label class="form-label">Precio</label>' +
                            '<input type="text" class="form-control mc-form-control photo-field" data-field="price" ' +
                                   'value="' + escHtml(p.price || '') + '" placeholder="ej: 120" maxlength="50">' +
                        '</div>' +
                        '<div class="btn-row">' +
                            '<button class="btn btn-outline-success photo-save-btn" data-id="' + p.id + '">' +
                                '<i class="bi bi-check2 me-1"></i>Guardar' +
                            '</button>' +
                            '<span class="gallery-saved" id="saved-' + p.id + '">✓ Guardado</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            grid.appendChild(col);
        });
    }

    function loadPhotos(){
        fetch(BASE + '/dashboard/settings/photos', { credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(photos){ renderGrid(photos); })
            .catch(function(){});
    }

    // Save photo attributes
    function savePhoto(id, card){
        var btn = card.querySelector('.photo-save-btn');
        var fd  = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('photo_id', id);
        card.querySelectorAll('.photo-field').forEach(function(f){
            fd.append(f.dataset.field, f.value);
        });
        if (btn){ btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>...'; }
        fetch(BASE + '/dashboard/gallery/update', {
            method: 'POST', credentials: 'same-origin', body: fd
        }).then(function(r){
              if (!r.ok) throw new Error('HTTP ' + r.status);
              return r.json();
          })
          .then(function(d){
              if (btn){ btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Guardar'; }
              if (d.ok){
                  var badge = document.getElementById('saved-' + id);
                  if (badge){ badge.classList.add('show'); setTimeout(function(){ badge.classList.remove('show'); }, 2000); }
                  // Remove "no name" warning if name is now filled
                  var nameInput = card.querySelector('[data-field="photo_name"]');
                  var warn = card.querySelector('.photo-no-name-warn');
                  if (warn && nameInput && nameInput.value.trim()) warn.remove();
              } else {
                  alert('Error al guardar: ' + (d.error || 'desconocido'));
              }
          })
          .catch(function(err){
              if (btn){ btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Guardar'; }
              alert('No se pudo guardar. Error: ' + err.message);
          });
    }

    // Manual save button
    grid.addEventListener('click', function(e){
        var btn = e.target.closest('.photo-save-btn');
        if (!btn) return;
        var col = btn.closest('[data-id]');
        savePhoto(btn.dataset.id, btn.closest('.gallery-card'));
    });

    // Auto-save on input change (debounced 1.5s)
    var autoSaveTimers = {};
    grid.addEventListener('input', function(e){
        var field = e.target.closest('.photo-field');
        if (!field) return;
        var col = field.closest('[data-id]');
        if (!col) return;
        var id   = col.dataset.id;
        var card = col.querySelector('.gallery-card');
        if (!card) return;
        clearTimeout(autoSaveTimers[id]);
        var badge = document.getElementById('saved-' + id);
        if (badge){ badge.style.color='#aaa'; badge.textContent='● editando...'; badge.classList.add('show'); }
        autoSaveTimers[id] = setTimeout(function(){
            if (badge){ badge.style.color=''; badge.textContent='✓ Guardado'; }
            savePhoto(id, card);
        }, 1500);
    });

    // Delete photo
    grid.addEventListener('click', function(e){
        var btn = e.target.closest('.photo-del-btn');
        if (!btn) return;
        var id = btn.dataset.id;
        if (!confirm('¿Eliminar esta foto?')) return;
        var fd = new FormData();
        fd.append('_csrf', CSRF);
        fetch(BASE + '/dashboard/settings/photos/' + id + '/delete', {
            method: 'POST', credentials: 'same-origin', body: fd
        }).then(function(r){ return r.json(); })
          .then(function(d){
              if (d.ok){
                  var col = btn.closest('[data-id]');
                  if (col) col.remove();
                  if (!grid.children.length) emptyMsg.classList.remove('d-none');
              }
          })
          .catch(function(){});
    });

    // ── Upload queue (supports multiple files) ──────────────────────────
    var progressBar   = document.getElementById('uploadProgressBar');
    var countLabel    = document.getElementById('uploadCountLabel');
    var statusLabel   = document.getElementById('uploadStatusLabel');
    var dropZone      = document.getElementById('dropZone');
    var ALLOWED_TYPES = ['image/jpeg','image/png','image/webp'];
    var MAX_SIZE      = 5 * 1024 * 1024;

    function uploadFiles(files){
        var queue = [];
        var errors = [];
        for (var i = 0; i < files.length; i++){
            var f = files[i];
            if (ALLOWED_TYPES.indexOf(f.type) === -1){ errors.push(f.name + ': tipo no permitido'); continue; }
            if (f.size > MAX_SIZE){ errors.push(f.name + ': supera 5 MB'); continue; }
            queue.push(f);
        }
        if (!queue.length){
            if (errors.length){ errBox.innerHTML = errors.join('<br>'); errBox.classList.remove('d-none'); }
            return;
        }
        errBox.classList.add('d-none');
        progress.classList.remove('d-none');
        uploadLabel.classList.add('disabled');
        progressBar.style.width = '0%';
        countLabel.textContent = '0/' + queue.length;
        statusLabel.textContent = 'Subiendo...';

        var done = 0;
        function next(){
            if (done >= queue.length){
                progress.classList.add('d-none');
                uploadLabel.classList.remove('disabled');
                if (errors.length){ errBox.innerHTML = errors.join('<br>'); errBox.classList.remove('d-none'); }
                loadPhotos();
                return;
            }
            var file = queue[done];
            statusLabel.textContent = 'Subiendo ' + file.name + '...';
            var fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('photo', file);
            fetch(BASE + '/dashboard/settings/photos/upload', {
                method: 'POST', credentials: 'same-origin', body: fd
            }).then(function(r){ return r.json(); })
              .then(function(d){
                  done++;
                  var pct = Math.round((done / queue.length) * 100);
                  progressBar.style.width = pct + '%';
                  countLabel.textContent = done + '/' + queue.length;
                  if (d.error) errors.push(file.name + ': ' + d.error);
                  next();
              })
              .catch(function(err){
                  done++;
                  errors.push(file.name + ': error de red');
                  var pct = Math.round((done / queue.length) * 100);
                  progressBar.style.width = pct + '%';
                  countLabel.textContent = done + '/' + queue.length;
                  next();
              });
        }
        next();
    }

    // File input (multi)
    fileInput.addEventListener('change', function(){
        if (!fileInput.files.length) return;
        uploadFiles(fileInput.files);
        fileInput.value = '';
    });

    // Drag-and-drop
    dropZone.addEventListener('click', function(){ fileInput.click(); });
    dropZone.addEventListener('dragover', function(e){ e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', function(e){ e.preventDefault(); dropZone.classList.remove('drag-over'); });
    dropZone.addEventListener('drop', function(e){
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        if (e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
    });

    // Prevent full-page drop
    document.addEventListener('dragover', function(e){ e.preventDefault(); });
    document.addEventListener('drop', function(e){ e.preventDefault(); });

    loadPhotos();
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
