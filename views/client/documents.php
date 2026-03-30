<?php
/**
 * mia/views/client/documents.php — Business document library
 */
$base         = App::basePath();
$pageTitle    = 'Documentos — Mia';
$pageTopTitle = 'Documentos';
$activeNav    = 'documents';

require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>
<!-- ── Main content ──────────────────────────────────────────────────────────── -->
<div class="mc-main-content">
    <div class="mc-page-header">
        <div>
            <h1 class="mc-page-title"><?= $pageTopTitle ?></h1>
            <p class="mc-page-subtitle">Sube PDFs, Word, Excel y PowerPoint. Mia los enviará a tus clientes automáticamente por WhatsApp cuando los pidan.</p>
        </div>
        <label class="btn btn-success fw-semibold" id="docUploadLabel" style="cursor:pointer">
            <i class="bi bi-cloud-upload me-1"></i> Subir documentos
            <input type="file" id="docFileInput"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                   multiple hidden>
        </label>
    </div>

    <!-- Drag-and-drop zone -->
    <div id="dropZone" class="drop-zone mb-4">
        <i class="bi bi-file-earmark-arrow-up" style="font-size:2.5rem;opacity:.5"></i>
        <p class="mb-1 fw-semibold">Arrastra y suelta tus archivos aquí</p>
        <p class="text-muted small mb-0">o haz clic en "Subir documentos" — PDF, Word, Excel, PowerPoint (máx. 20 MB c/u)</p>
    </div>

    <!-- Upload progress -->
    <div id="docUploadProgress" class="d-none mb-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <div class="progress flex-grow-1" style="height:6px">
                <div id="uploadProgressBar" class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width:0%"></div>
            </div>
            <small id="uploadCountLabel" class="text-muted text-nowrap">0/0</small>
        </div>
        <small id="uploadStatusLabel" class="text-muted">Subiendo...</small>
    </div>
    <div id="docUploadError" class="alert alert-danger d-none py-2 small mb-3"></div>

    <!-- Document cards grid -->
    <div id="docGrid" class="row g-3"></div>
    <p id="docEmpty" class="text-muted text-center py-5 d-none">
        <i class="bi bi-file-earmark-text" style="font-size:2.5rem;opacity:.3"></i><br>
        No hay documentos aún. ¡Sube el primero para que Mia los comparta con tus clientes!
    </p>
</div>

<style>
.doc-card {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow .2s;
}
.doc-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.25); }
.doc-card .doc-icon-box {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 0 16px;
    font-size: 3rem;
}
.doc-card .card-body { padding: 10px 14px 14px; }
.doc-card .form-control.mc-form-control {
    font-size: .8rem;
    padding: 5px 8px;
}
.doc-card .form-label {
    font-size: .7rem;
    margin-bottom: 2px;
    opacity: .7;
}
.doc-saved {
    font-size: .7rem;
    color: #25d366;
    opacity: 0;
    transition: opacity .3s;
}
.doc-saved.show { opacity: 1; }
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
    const BASE  = <?= json_encode($base) ?>;
    const CSRF  = <?= json_encode(App::csrfToken()) ?>;
    const grid  = document.getElementById('docGrid');
    const empty = document.getElementById('docEmpty');
    const progress  = document.getElementById('docUploadProgress');
    const errBox    = document.getElementById('docUploadError');
    const fileInput = document.getElementById('docFileInput');
    const dropZone  = document.getElementById('dropZone');

    const FILE_ICONS = {
        pdf:  { icon: 'bi-file-earmark-pdf',   color: '#e53e3e' },
        docx: { icon: 'bi-file-earmark-word',   color: '#2b6cb0' },
        xlsx: { icon: 'bi-file-earmark-excel',  color: '#276749' },
        pptx: { icon: 'bi-file-earmark-ppt',    color: '#c05621' },
    };

    function escHtml(s){ return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function renderGrid(docs){
        grid.innerHTML = '';
        if (!docs.length){ empty.classList.remove('d-none'); return; }
        empty.classList.add('d-none');

        docs.forEach(function(d){
            const fi    = FILE_ICONS[d.file_type] || { icon: 'bi-file-earmark', color: '#718096' };
            const size  = d.file_size_label || '';
            const col   = document.createElement('div');
            col.className = 'col-12 col-sm-6 col-lg-4';
            col.dataset.id = d.id;
            col.innerHTML =
                '<div class="doc-card">' +
                    '<div class="doc-icon-box position-relative">' +
                        '<i class="bi ' + fi.icon + '" style="color:' + fi.color + '"></i>' +
                        '<span class="badge position-absolute top-0 start-0 m-2" style="background:' + fi.color + ';font-size:.65rem;">' + escHtml(d.file_type.toUpperCase()) + '</span>' +
                        (size ? '<span class="badge bg-secondary position-absolute top-0 end-0 m-2" style="font-size:.65rem;">' + escHtml(size) + '</span>' : '') +
                        '<button class="doc-del-btn btn btn-sm position-absolute bottom-0 end-0 m-2" ' +
                                'style="background:rgba(220,53,69,0.9);border:none;color:#fff;border-radius:8px;padding:4px 9px" ' +
                                'title="Eliminar" data-id="' + d.id + '">' +
                            '<i class="bi bi-trash3"></i>' +
                        '</button>' +
                    '</div>' +
                    '<div class="card-body">' +
                        '<div class="mb-2">' +
                            '<label class="form-label">Nombre para Mia</label>' +
                            '<input type="text" class="form-control mc-form-control doc-field" data-field="doc_name" ' +
                                   'value="' + escHtml(d.doc_name || '') + '" placeholder="ej: Catálogo 2026" maxlength="150">' +
                        '</div>' +
                        '<div class="mb-2">' +
                            '<label class="form-label">Cuándo enviarlo (describe brevemente)</label>' +
                            '<textarea class="form-control mc-form-control doc-field" data-field="description" ' +
                                      'rows="2" placeholder="ej: Enviar cuando el cliente pida precios o el catálogo" maxlength="500">' + escHtml(d.description || '') + '</textarea>' +
                        '</div>' +
                        '<div class="d-flex align-items-center gap-2 mt-1">' +
                            '<button class="btn btn-outline-success btn-sm doc-save-btn" data-id="' + d.id + '">' +
                                '<i class="bi bi-check2 me-1"></i>Guardar' +
                            '</button>' +
                            '<span class="doc-saved" id="dsaved-' + d.id + '">✓ Guardado</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            grid.appendChild(col);
        });
    }

    function loadDocs(){
        fetch(BASE + '/dashboard/settings/docs', { credentials:'same-origin' })
            .then(r => r.json())
            .then(docs => renderGrid(docs))
            .catch(() => {});
    }

    // ── Save ──────────────────────────────────────────────────────────────────
    function saveDoc(id, card){
        const btn = card.querySelector('.doc-save-btn');
        const fd  = new FormData();
        fd.append('_csrf', CSRF);
        fd.append('doc_id', id);
        card.querySelectorAll('.doc-field').forEach(f => fd.append(f.dataset.field, f.value));
        if (btn){ btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>...'; }
        fetch(BASE + '/dashboard/documents/update', {
            method: 'POST', credentials: 'same-origin', body: fd,
        }).then(r => r.json())
          .then(data => {
              if (btn){ btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Guardar'; }
              if (data.ok){
                  const badge = document.getElementById('dsaved-' + id);
                  if (badge){ badge.classList.add('show'); setTimeout(() => badge.classList.remove('show'), 2000); }
              } else {
                  alert('Error al guardar.');
              }
          }).catch(() => {
              if (btn){ btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Guardar'; }
          });
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    grid.addEventListener('click', function(e){
        const delBtn = e.target.closest('.doc-del-btn');
        if (delBtn){
            if (!confirm('¿Eliminar este documento?')) return;
            const col = delBtn.closest('[data-id]');
            const id  = delBtn.dataset.id;
            const fd  = new FormData();
            fd.append('_csrf', CSRF);
            fetch(BASE + '/dashboard/documents/' + id + '/delete', {
                method: 'POST', credentials: 'same-origin', body: fd,
            }).then(r => r.json())
              .then(data => { if (data.ok && col) col.remove(); if (!grid.children.length) empty.classList.remove('d-none'); })
              .catch(() => {});
            return;
        }
        const saveBtn = e.target.closest('.doc-save-btn');
        if (saveBtn){
            saveDoc(saveBtn.dataset.id, saveBtn.closest('.doc-card'));
        }
    });

    // ── Auto-save on input (debounced) ────────────────────────────────────────
    const timers = {};
    grid.addEventListener('input', function(e){
        const field = e.target.closest('.doc-field');
        if (!field) return;
        const col = field.closest('[data-id]');
        if (!col) return;
        clearTimeout(timers[col.dataset.id]);
        timers[col.dataset.id] = setTimeout(() => saveDoc(col.dataset.id, col.querySelector('.doc-card')), 1500);
    });

    // ── Upload ────────────────────────────────────────────────────────────────
    async function uploadFiles(files){
        if (!files.length) return;
        const arr   = Array.from(files);
        const total = arr.length;
        const bar   = document.getElementById('uploadProgressBar');
        const count = document.getElementById('uploadCountLabel');
        const label = document.getElementById('uploadStatusLabel');
        errBox.classList.add('d-none');
        progress.classList.remove('d-none');

        for (let i = 0; i < total; i++){
            const file = arr[i];
            count.textContent = (i + 1) + '/' + total;
            label.textContent = 'Subiendo ' + file.name + '…';
            bar.style.width   = Math.round(((i) / total) * 100) + '%';

            const fd = new FormData();
            fd.append('_csrf', CSRF);
            fd.append('doc', file);
            try {
                const r = await fetch(BASE + '/dashboard/documents/upload', {
                    method: 'POST', credentials: 'same-origin', body: fd,
                });
                const data = await r.json();
                if (data.error){
                    errBox.textContent = file.name + ': ' + data.error;
                    errBox.classList.remove('d-none');
                } else if (data.ok && data.doc) {
                    empty.classList.add('d-none');
                    // Append new card quickly by re-rendering
                    loadDocs();
                }
            } catch(err){
                errBox.textContent = 'Error de red al subir ' + file.name;
                errBox.classList.remove('d-none');
            }
        }

        bar.style.width = '100%';
        label.textContent = 'Completado.';
        setTimeout(() => progress.classList.add('d-none'), 1500);
        fileInput.value = '';
    }

    fileInput.addEventListener('change', () => uploadFiles(fileInput.files));

    // ── Drag-and-drop ─────────────────────────────────────────────────────────
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        uploadFiles(e.dataTransfer.files);
    });
    dropZone.addEventListener('click', () => fileInput.click());

    // ── Initial load ──────────────────────────────────────────────────────────
    loadDocs();
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
