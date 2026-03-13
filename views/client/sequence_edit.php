<?php
/**
 * mia/views/client/sequence_edit.php — Create / Edit a follow-up sequence
 * $sequence = Sequence|null, $steps = SequenceStep[]
 */
$base = App::basePath();
$isNew = ($sequence === null);
require __DIR__ . '/_head.php';
require __DIR__ . '/_sidebar.php';
?>

<!-- Breadcrumb -->
<nav class="mb-3" style="font-size:.85rem">
    <a href="<?= $base ?>/dashboard/sequences" class="text-muted text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i>Automatizaciones
    </a>
</nav>

<!-- Error alerts -->
<?php if (isset($_GET['error'])): ?>
<div class="alert alert-warning border-0 mb-4">
    <i class="bi bi-exclamation-circle me-2"></i>
    <?php if ($_GET['error'] === 'name'): ?>
        El nombre de la secuencia es obligatorio.
    <?php elseif ($_GET['error'] === 'steps'): ?>
        Debes agregar al menos un paso con mensaje.
    <?php else: ?>
        Corrige los errores antes de guardar.
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= $base ?>/dashboard/sequences/save" id="seqForm">
    <input type="hidden" name="_csrf" value="<?= App::csrfToken() ?>">
    <input type="hidden" name="sequence_id" value="<?= $isNew ? 0 : $sequence->id ?>">

    <div class="row g-4">

        <!-- ── Left: header info ──────────────────────────────────────────── -->
        <div class="col-lg-4">
            <div class="mc-table-card p-4 mb-3">
                <h6 class="fw-bold mb-3">
                    <i class="bi bi-send-check me-2" style="color:#25d366"></i>
                    <?= $isNew ? 'Nueva' : 'Editar' ?> secuencia
                </h6>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Nombre</label>
                    <input type="text" name="name" class="form-control"
                           value="<?= htmlspecialchars($sequence?->name ?? '') ?>"
                           placeholder="Ej: Seguimiento post-consulta"
                           maxlength="120" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Activador</label>
                    <select name="trigger" class="form-select">
                        <option value="manual"
                            <?= ($sequence?->trigger ?? 'manual') === 'manual' ? 'selected' : '' ?>>
                            Manual — inscribir leads a mano
                        </option>
                        <option value="on_new"
                            <?= ($sequence?->trigger ?? '') === 'on_new' ? 'selected' : '' ?>>
                            Automático — al crear un lead
                        </option>
                        <option value="on_interested"
                            <?= ($sequence?->trigger ?? '') === 'on_interested' ? 'selected' : '' ?>>
                            Automático — al pasar a Interesado
                        </option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Los trigésimos automáticos inscriben al lead sin que tengas que hacer nada.
                    </div>
                </div>
            </div>

            <!-- Summary box -->
            <div class="mc-table-card p-3 text-muted small">
                <div class="d-flex justify-content-between mb-1">
                    <span>Pasos en secuencia</span>
                    <strong id="stepCountBadge" class="text-dark">0</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Duración total</span>
                    <strong id="totalDaysBadge" class="text-dark">0 días</strong>
                </div>
            </div>
        </div>

        <!-- ── Right: step builder ────────────────────────────────────────── -->
        <div class="col-lg-8">
            <div class="mc-table-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-list-ol me-2 text-primary"></i>Pasos de la secuencia
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-success" id="addStepBtn"
                            style="border-color:#25d366;color:#25d366">
                        <i class="bi bi-plus me-1"></i>Agregar paso
                    </button>
                </div>

                <!-- Step list -->
                <div id="stepList">
                <?php
                // Pre-populate existing steps (or one blank step for new)
                $initialSteps = !empty($steps) ? $steps : [null];
                foreach ($initialSteps as $i => $step):
                    $delay   = $step?->delay_days ?? 1;
                    $message = $step?->message ?? '';
                ?>
                <div class="step-item border rounded-3 p-3 mb-3 position-relative" data-index="<?= $i ?>">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary step-num" style="min-width:24px"><?= $i + 1 ?></span>
                        <div class="flex-fill d-flex align-items-center gap-2">
                            <label class="text-muted small mb-0 text-nowrap">Esperar</label>
                            <input type="number" name="delay_days[]"
                                   class="form-control form-control-sm delay-input"
                                   value="<?= $delay ?>"
                                   min="0" max="365" style="width:70px">
                            <label class="text-muted small mb-0">
                                día<?= $delay === 1 ? '' : 's' ?> antes de enviar
                                <span class="first-step-note text-primary" <?= $i > 0 ? 'style="display:none"' : '' ?>
                                      style="font-size:.73rem">(desde inscripción)</span>
                            </label>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-step ms-auto"
                                title="Eliminar paso">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                    <textarea name="step_message[]"
                              class="form-control message-input"
                              rows="3"
                              placeholder="Ej: Hola <?= '{name}' ?>, ¿pudiste revisar la información que te enviamos? Estamos para ayudarte 👋"
                              maxlength="1000"
                              required><?= htmlspecialchars($message) ?></textarea>
                    <div class="text-end text-muted" style="font-size:.72rem;margin-top:3px">
                        <span class="char-count">0</span>/1000
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Tip: variable hints -->
                <div class="text-muted small mb-4">
                    <i class="bi bi-lightbulb me-1 text-warning"></i>
                    Usa <code><?= '{name}' ?></code> para insertar el nombre del contacto.
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4"
                            style="background:#25d366;border-color:#25d366">
                        <i class="bi bi-check-lg me-2"></i>
                        <?= $isNew ? 'Crear secuencia' : 'Guardar cambios' ?>
                    </button>
                    <a href="<?= $base ?>/dashboard/sequences"
                       class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</form>

<script>
(function () {
    const list    = document.getElementById('stepList');
    const addBtn  = document.getElementById('addStepBtn');

    function getSteps() { return list.querySelectorAll('.step-item'); }

    function renumberSteps() {
        getSteps().forEach((el, i) => {
            el.dataset.index = i;
            el.querySelector('.step-num').textContent = i + 1;
            const note = el.querySelector('.first-step-note');
            if (note) note.style.display = i === 0 ? '' : 'none';
        });
        updateSummary();
    }

    function updateSummary() {
        const items = getSteps();
        document.getElementById('stepCountBadge').textContent = items.length;
        let total = 0;
        items.forEach(el => {
            const d = parseInt(el.querySelector('.delay-input')?.value || 0, 10);
            total += isNaN(d) ? 0 : d;
        });
        document.getElementById('totalDaysBadge').textContent = total + ' día' + (total === 1 ? '' : 's');
    }

    function attachCharCount(textarea) {
        const counter = textarea.closest('.step-item').querySelector('.char-count');
        const update  = () => { if (counter) counter.textContent = textarea.value.length; };
        textarea.addEventListener('input', update);
        update();
    }

    function attachRemove(item) {
        item.querySelector('.remove-step').addEventListener('click', () => {
            if (getSteps().length <= 1) return; // keep at least 1
            item.remove();
            renumberSteps();
        });
    }

    function attachDelay(item) {
        item.querySelector('.delay-input').addEventListener('input', updateSummary);
    }

    // Attach to existing steps
    getSteps().forEach(item => {
        attachRemove(item);
        attachDelay(item);
        attachCharCount(item.querySelector('.message-input'));
    });

    // Add step
    addBtn.addEventListener('click', () => {
        const idx     = getSteps().length;
        const tpl     = document.createElement('div');
        tpl.className = 'step-item border rounded-3 p-3 mb-3 position-relative';
        tpl.dataset.index = idx;
        tpl.innerHTML = `
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary step-num">${idx + 1}</span>
                <div class="flex-fill d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0 text-nowrap">Esperar</label>
                    <input type="number" name="delay_days[]"
                           class="form-control form-control-sm delay-input"
                           value="1" min="0" max="365" style="width:70px">
                    <label class="text-muted small mb-0">días antes de enviar
                        <span class="first-step-note text-primary" style="display:none;font-size:.73rem">(desde inscripción)</span>
                    </label>
                </div>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-step ms-auto"
                        title="Eliminar paso"><i class="bi bi-trash3"></i></button>
            </div>
            <textarea name="step_message[]"
                      class="form-control message-input"
                      rows="3"
                      placeholder="Escribe el mensaje de seguimiento…"
                      maxlength="1000" required></textarea>
            <div class="text-end text-muted" style="font-size:.72rem;margin-top:3px">
                <span class="char-count">0</span>/1000
            </div>`;
        list.appendChild(tpl);
        attachRemove(tpl);
        attachDelay(tpl);
        attachCharCount(tpl.querySelector('.message-input'));
        renumberSteps();
        tpl.querySelector('textarea').focus();
    });

    // Init summary
    renumberSteps();
})();
</script>

<?php require __DIR__ . '/_foot.php'; ?>
