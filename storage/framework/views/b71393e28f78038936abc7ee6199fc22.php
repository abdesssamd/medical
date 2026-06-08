

<?php $__env->startSection('title', 'Templates RIS'); ?>
<?php $__env->startSection('page-title', 'Gestionnaire de templates RIS'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .template-page { display: grid; gap: 18px; color: #0f172a; }
    .template-hero { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 18px; border: 1px solid #dbe8f3; border-radius: 18px; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 18px 40px rgba(15,23,42,0.06); }
    .template-hero h2 { margin: 0 0 8px; font-size: 1.25rem; font-weight: 900; }
    .template-hero p { margin: 0; color: #64748b; }
    .template-grid { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(380px, 0.92fr); gap: 18px; align-items: start; }
    .template-card { background: #fff; border: 1px solid #dbe8f3; border-radius: 18px; box-shadow: 0 18px 40px rgba(15,23,42,0.06); }
    .template-card-inner { padding: 18px; }
    .template-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
    .template-title { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
    .template-muted { color: #64748b; }
    .template-link { color:#2563eb; text-decoration:none; font-weight:700; }
    .template-list { display:flex; flex-direction:column; gap:12px; }
    .template-item { border:1px solid #e2e8f0; border-radius:16px; padding:16px; background: #fff; }
    .template-item-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
    .template-badge { display:inline-flex; padding:4px 10px; border-radius:999px; background:#e0f2fe; color:#075985; font-size:12px; font-weight:700; }
    .template-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .template-btn { display:inline-flex; align-items:center; justify-content:center; min-height: 38px; border:1px solid #cbd5e1; background:#fff; color:#0f172a; text-decoration:none; padding:8px 12px; border-radius:10px; font-weight:700; cursor:pointer; }
    .template-btn:hover { border-color:#93c5fd; background:#eff6ff; color:#1d4ed8; }
    .template-btn-danger { border-color:#fca5a5; background:#fee2e2; color:#b91c1c; }
    .template-btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; }
    .template-btn-primary:hover { background:#1d4ed8; color:#fff; border-color:#1d4ed8; }
    .template-form label { display:block; margin-bottom:6px; font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; }
    .template-form input,.template-form textarea,.template-form select { width:100%; border:1px solid #cbd5e1; border-radius:12px; padding:10px 12px; font: inherit; background:#fff; }
    .template-form textarea { min-height: 280px; resize: vertical; line-height: 1.55; }
    .template-grid-form { display:grid; gap:12px; }
    .template-helper { font-size: 12px; color:#64748b; margin-top:6px; }
    .template-empty { padding:18px; border:1px dashed #cbd5e1; border-radius:14px; color:#64748b; }
    .template-editor-shell { overflow:hidden; }
    .template-editor-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:8px; padding:10px; border-bottom:1px solid #e2e8f0; background:#f8fafc; }
    .template-editor { width:100%; min-height: 260px; padding:14px; background:#fff; border:0; outline:none; overflow:auto; font:inherit; line-height:1.5; }
    .template-editor[contenteditable="false"] { background:#f1f5f9; color:#64748b; cursor:not-allowed; }
    .template-select-inline { width:auto; min-width:130px; }
    body.template-no-scroll { overflow:hidden; }
    .template-editor-shell.is-fullscreen { position: fixed; inset: 0; z-index: 9999; border-radius: 0; border: 0; box-shadow: none; display:grid; grid-template-rows:auto minmax(0, 1fr); }
    .template-editor-shell.is-fullscreen .template-editor { min-height: 100%; height: 100%; font-size: 1rem; padding: 18px; }
    .template-footer { display:flex; justify-content:flex-end; gap:8px; margin-top:12px; }
    .template-pill-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .template-warning { padding: 10px 12px; border-radius: 10px; background: #fff7ed; color:#9a3412; border:1px solid #fdba74; font-weight:700; }
    @media (max-width: 1100px) { .template-grid { grid-template-columns: 1fr; } .template-hero { flex-direction:column; } }
</style>

<div class="template-page">
    <?php if(session('success')): ?>
        <div class="template-warning" style="background:#dcfce7; border-color:#86efac; color:#166534;"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="template-warning" style="background:#fee2e2; border-color:#fecaca; color:#991b1b;"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    <section class="template-hero">
        <div>
            <h2>Gestionnaire de templates</h2>
            <p>Créer, classer, modifier ou supprimer les modèles de compte-rendu avec un éditeur enrichi identique à la fiche examen.</p>
        </div>
        <a href="<?php echo e(route('ris.exams.index')); ?>" class="template-link">Retour aux examens</a>
    </section>

    <div class="template-grid">
        <section class="template-card">
            <div class="template-card-inner">
                <div class="template-toolbar">
                    <div>
                        <div class="template-title">Templates enregistrés</div>
                        <div class="template-muted">Liste des modèles disponibles pour insertion rapide dans les rapports.</div>
                    </div>
                </div>

                <div class="template-list">
                    <?php $__empty_1 = true; $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="template-item">
                            <div class="template-item-head">
                                <div>
                                    <div style="font-weight:800; font-size:16px;"><?php echo e($template->title); ?></div>
                                    <div style="margin-top:6px;"><span class="template-badge"><?php echo e($template->category ?: 'Général'); ?></span></div>
                                </div>
                                <div class="template-actions">
                                    <a class="template-btn" href="<?php echo e(route('ris.templates.edit', $template)); ?>">Modifier</a>
                                    <form method="POST" action="<?php echo e(route('ris.templates.destroy', $template)); ?>" onsubmit="return confirm('Supprimer ce template ?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="template-btn template-btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                            <div style="margin-top:12px; white-space:pre-line; color:#334155; font-size:13px; line-height:1.6;"><?php echo e(\Illuminate\Support\Str::limit(strip_tags($template->content), 260)); ?></div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="template-empty">Aucun template enregistré pour le moment.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <aside class="template-card">
            <div class="template-card-inner">
                <div class="template-toolbar" style="align-items:flex-start;">
                    <div>
                        <div class="template-title"><?php echo e($editingTemplate ? 'Modifier le template' : 'Nouveau template'); ?></div>
                        <div class="template-muted">Les catégories aident à regrouper Scanner, Radio, Echo et autres modèles.</div>
                    </div>
                </div>

                <form id="template-form" class="template-form" method="POST" action="<?php echo e($editingTemplate ? route('ris.templates.update', $editingTemplate) : route('ris.templates.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if($editingTemplate): ?>
                        <?php echo method_field('PUT'); ?>
                    <?php endif; ?>

                    <div class="template-grid-form">
                        <div>
                            <label for="category">Catégorie</label>
                            <input id="category" name="category" list="template_categories" value="<?php echo e(old('category', $editingTemplate?->category ?? 'Général')); ?>" placeholder="Scanner, Radio, Echo...">
                            <datalist id="template_categories">
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($category); ?>"></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <option value="Scanner"></option>
                                <option value="Radio"></option>
                                <option value="Echo"></option>
                            </datalist>
                            <div class="template-helper">Utilisée pour classer et filtrer les modèles.</div>
                        </div>

                        <div>
                            <label for="title">Titre</label>
                            <input id="title" name="title" value="<?php echo e(old('title', $editingTemplate?->title)); ?>" required placeholder="Ex: Panoramique standard">
                        </div>

                        <div>
                            <label>Outils</label>
                            <div class="template-pill-row">
                                <button type="button" id="template-fullscreen-btn" class="template-btn">Plein ecran</button>
                                <button type="button" id="template-import-btn" class="template-btn">Charger modele Word</button>
                                <button type="button" id="template-export-btn" class="template-btn">Exporter .docx</button>
                                <button type="button" data-editor-command="bold" class="template-btn">B</button>
                                <button type="button" data-editor-command="italic" class="template-btn">I</button>
                                <button type="button" data-editor-command="underline" class="template-btn">U</button>
                                <button type="button" data-editor-command="insertUnorderedList" class="template-btn">• Liste</button>
                                <select id="template-block-format" class="template-form select template-select-inline">
                                    <option value="">Style</option>
                                    <option value="p">Paragraphe</option>
                                    <option value="h2">Titre</option>
                                    <option value="h3">Sous-titre</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="template_preview_editor">Contenu</label>
                            <div class="template-editor-shell template-card" id="template-editor-shell">
                                <div class="template-editor-toolbar">
                                    <button type="button" data-editor-command="justifyLeft" class="template-btn">Gauche</button>
                                    <button type="button" data-editor-command="justifyCenter" class="template-btn">Centre</button>
                                    <button type="button" data-editor-command="justifyRight" class="template-btn">Droite</button>
                                    <button type="button" data-editor-command="undo" class="template-btn">Annuler</button>
                                    <button type="button" data-editor-command="redo" class="template-btn">Refaire</button>
                                    <button type="button" data-editor-snippet="normal" class="template-btn">Snippet</button>
                                </div>
                                <div id="template_preview_editor" class="template-editor" contenteditable="true"><?php echo old('content', $editingTemplate?->content); ?></div>
                                <textarea id="content" name="content" hidden required><?php echo e(old('content', $editingTemplate?->content)); ?></textarea>
                                <input id="template-word-input" type="file" accept=".docx" hidden>
                            </div>
                            <div class="template-helper">Le contenu est sauvegardé en HTML enrichi, puis nettoyé côté serveur.</div>
                        </div>
                    </div>

                    <div class="template-footer">
                        <button type="submit" class="template-btn template-btn-primary"><?php echo e($editingTemplate ? 'Mettre à jour' : 'Enregistrer'); ?></button>
                        <?php if($editingTemplate): ?>
                            <a href="<?php echo e(route('ris.templates.index')); ?>" class="template-btn">Annuler</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </aside>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canEdit = true;
        const templateEditor = document.getElementById('template_preview_editor');
        const templateHidden = document.getElementById('content');
        const templateForm = document.getElementById('template-form');
        const fullscreenButton = document.getElementById('template-fullscreen-btn');
        const importButton = document.getElementById('template-import-btn');
        const exportButton = document.getElementById('template-export-btn');
        const blockFormatSelect = document.getElementById('template-block-format');
        const editorShell = document.getElementById('template-editor-shell');
        const fileInput = document.getElementById('template-word-input');
        const toolbarButtons = document.querySelectorAll('[data-editor-command], [data-editor-snippet]');

        let savedRange = null;
        let isFullscreen = false;

        const ensureScript = (src, globalName) => new Promise((resolve, reject) => {
            if (globalName && window[globalName]) {
                resolve(window[globalName]);
                return;
            }

            const existing = document.querySelector(`script[src="${src}"]`);
            if (existing) {
                existing.addEventListener('load', () => resolve(globalName ? window[globalName] : true), { once: true });
                existing.addEventListener('error', () => reject(new Error(`Chargement impossible: ${src}`)), { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve(globalName ? window[globalName] : true);
            script.onerror = () => reject(new Error(`Chargement impossible: ${src}`));
            document.head.appendChild(script);
        });

        const syncHidden = () => {
            if (templateHidden && templateEditor) {
                templateHidden.value = templateEditor.innerHTML || '';
            }
        };

        const focusEditor = () => templateEditor?.focus();

        const rememberSelection = () => {
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0 || !templateEditor?.contains(selection.anchorNode)) {
                return;
            }

            savedRange = selection.getRangeAt(0).cloneRange();
        };

        const restoreSelection = () => {
            if (!savedRange) return false;
            const selection = window.getSelection();
            if (!selection) return false;
            selection.removeAllRanges();
            selection.addRange(savedRange);
            return true;
        };

        const placeCaretAtEnd = () => {
            if (!templateEditor) return;
            const range = document.createRange();
            range.selectNodeContents(templateEditor);
            range.collapse(false);
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(range);
            savedRange = range.cloneRange();
        };

        const setContent = (html) => {
            if (!templateEditor) return;
            templateEditor.innerHTML = html || '';
            syncHidden();
        };

        const insertContent = (html) => {
            if (!canEdit || !templateEditor) return;
            focusEditor();
            if (!restoreSelection()) placeCaretAtEnd();

            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) {
                templateEditor.insertAdjacentHTML('beforeend', html);
                syncHidden();
                return;
            }

            const range = selection.getRangeAt(0);
            range.deleteContents();
            const holder = document.createElement('div');
            holder.innerHTML = html;
            const fragment = document.createDocumentFragment();
            let lastNode = null;

            while (holder.firstChild) {
                lastNode = fragment.appendChild(holder.firstChild);
            }

            range.insertNode(fragment);

            if (lastNode) {
                range.setStartAfter(lastNode);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
                savedRange = range.cloneRange();
            }

            syncHidden();
        };

        const toggleFullscreen = () => {
            if (!editorShell) return;
            isFullscreen = !isFullscreen;
            editorShell.classList.toggle('is-fullscreen', isFullscreen);
            document.body.classList.toggle('template-no-scroll', isFullscreen);
            if (fullscreenButton) {
                fullscreenButton.textContent = isFullscreen ? 'Quitter plein ecran' : 'Plein ecran';
            }
            focusEditor();
        };

        templateEditor?.addEventListener('input', syncHidden);
        templateEditor?.addEventListener('keyup', syncHidden);
        templateEditor?.addEventListener('mouseup', rememberSelection);
        templateEditor?.addEventListener('keyup', rememberSelection);
        templateEditor?.addEventListener('focus', rememberSelection);
        templateForm?.addEventListener('submit', syncHidden);
        syncHidden();

        blockFormatSelect?.addEventListener('change', () => {
            const tag = blockFormatSelect.value;
            if (!tag) return;
            restoreSelection();
            focusEditor();
            document.execCommand('formatBlock', false, tag);
            syncHidden();
            rememberSelection();
            blockFormatSelect.selectedIndex = 0;
        });

        toolbarButtons.forEach(btn => btn.addEventListener('mousedown', (event) => {
            event.preventDefault();
            const command = btn.dataset.editorCommand;
            const snippet = btn.dataset.editorSnippet;

            if (command) {
                restoreSelection();
                focusEditor();
                document.execCommand(command, false, null);
                syncHidden();
                rememberSelection();
                return;
            }

            if (snippet === 'normal') {
                insertContent('<p><strong>Conclusion :</strong> Modèle sans anomalie significative.</p>');
            }
        }));

        fullscreenButton?.addEventListener('click', toggleFullscreen);

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isFullscreen) {
                toggleFullscreen();
            }
        });

        importButton?.addEventListener('click', () => fileInput?.click());

        fileInput?.addEventListener('change', async (event) => {
            const file = event.target?.files?.[0];
            if (!file) return;

            if (!/\.docx$/i.test(file.name)) {
                alert('Veuillez charger un fichier .docx.');
                event.target.value = '';
                return;
            }

            try {
                await ensureScript('https://unpkg.com/mammoth@1.8.0/mammoth.browser.min.js', 'mammoth');
                const arrayBuffer = await file.arrayBuffer();
                const result = await window.mammoth.convertToHtml({ arrayBuffer });
                const html = (result?.value || '').trim();
                if (!html) {
                    alert('Le modele Word est vide ou non reconnu.');
                } else {
                    setContent(html);
                    placeCaretAtEnd();
                }
            } catch (error) {
                alert('Import Word impossible. Verifiez la connexion et le format du fichier.');
            } finally {
                event.target.value = '';
            }
        });

        exportButton?.addEventListener('click', async () => {
            try {
                await ensureScript('https://cdnjs.cloudflare.com/ajax/libs/html-docx-js/0.3.1/html-docx.min.js', 'htmlDocx');
                await ensureScript('https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js', 'saveAs');

                const html = (templateEditor?.innerHTML || '').trim();
                if (!html) {
                    alert('Aucun contenu a exporter.');
                    return;
                }

                const wrappedHtml = `<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>${html}</body></html>`;
                const blob = window.htmlDocx.asBlob(wrappedHtml);
                window.saveAs(blob, 'template-ris.docx');
            } catch (error) {
                alert('Export Word impossible pour le moment.');
            }
        });
    });
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp8.2\htdocs\fils_attente\Modules\RIS\Providers/../Resources/views/templates/index.blade.php ENDPATH**/ ?>