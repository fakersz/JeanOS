<?php
$isEdit = (bool) $node;
$statuses = life_statuses_for($config['type']);
$dt = static fn (?string $value): string => $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
$date = static fn (?string $value): string => $value ? date('Y-m-d', strtotime($value)) : '';
$currentStatus = $node['status'] ?? life_default_status($config['type']);
$currentPriority = $node['priority'] ?? 'normal';
$checklist = life_lines_to_text($metadata['checklist'] ?? []);
$technologies = life_lines_to_text($metadata['technologies'] ?? []);
?>

<section class="module-head">
    <div>
        <p class="eyebrow"><?= e($isEdit ? 'Editar' : 'Criar') ?></p>
        <h2><?= e($title) ?></h2>
        <p><?= e($config['description']) ?></p>
    </div>
</section>

<section class="card card-form life-form-card">
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-grid">
            <div class="field span-2">
                <label for="title">Título *</label>
                <input type="text" id="title" name="title" maxlength="220" required
                       value="<?= e($node['title'] ?? '') ?>"
                       placeholder="<?= e($config['singular']) ?>">
            </div>

            <div class="field span-2">
                <label for="summary">Resumo</label>
                <input type="text" id="summary" name="summary" maxlength="255"
                       value="<?= e($node['summary'] ?? '') ?>"
                       placeholder="Uma frase para reconhecer este item rapidamente">
            </div>

            <?php if ($statuses): ?>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $currentStatus === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (in_array($config['type'], ['task', 'project'], true)): ?>
                <div class="field">
                    <label for="priority">Prioridade</label>
                    <select id="priority" name="priority">
                        <?php foreach (life_priorities() as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $currentPriority === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (empty($config['forced_category'])): ?>
                <div class="field">
                    <label for="category">Categoria</label>
                    <input type="text" id="category" name="category" maxlength="120"
                           value="<?= e($node['category'] ?? '') ?>"
                           placeholder="Pessoal, trabalho, bancada, software...">
                </div>
            <?php else: ?>
                <div class="field">
                    <label>Categoria</label>
                    <input type="text" value="<?= e($config['forced_category']) ?>" disabled>
                </div>
            <?php endif; ?>

            <?php if ($config['type'] !== 'project'): ?>
                <div class="field">
                    <label for="project_id">Projeto</label>
                    <select id="project_id" name="project_id">
                        <option value="">Sem projeto</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?= $project['id'] ?>" <?= (int) $selectedProjectId === (int) $project['id'] ? 'selected' : '' ?>>
                                <?= e($project['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="field">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags" maxlength="500"
                       value="<?= e($node['tags'] ?? '') ?>"
                       placeholder="macbook, cliente, urgente">
            </div>

            <?php
            // Dia clicado no calendário da agenda: pré-preenche o início às 09:00.
            $prefillDate = null;
            if (!$isEdit && $config['type'] === 'agenda_event'
                && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? ''))) {
                $prefillDate = $_GET['date'] . 'T09:00';
            }
            ?>
            <?php if (in_array($config['type'], ['agenda_event', 'diary'], true)): ?>
                <div class="field">
                    <label for="starts_at"><?= $config['type'] === 'diary' ? 'Dia' : 'Início' ?></label>
                    <input type="<?= $config['type'] === 'diary' ? 'date' : 'datetime-local' ?>"
                           id="starts_at" name="starts_at"
                           value="<?= e($config['type'] === 'diary' ? $date($node['starts_at'] ?? date('Y-m-d')) : ($dt($node['starts_at'] ?? null) ?: (string) $prefillDate)) ?>">
                </div>
            <?php endif; ?>

            <?php if (in_array($config['type'], ['task', 'project', 'agenda_event'], true)): ?>
                <div class="field">
                    <label for="due_at"><?= $config['type'] === 'agenda_event' ? 'Fim' : 'Prazo' ?></label>
                    <input type="datetime-local" id="due_at" name="due_at" value="<?= e($dt($node['due_at'] ?? null)) ?>">
                </div>
            <?php endif; ?>

            <?php if (in_array($config['type'], ['task', 'project'], true)): ?>
                <div class="field">
                    <label for="time_minutes">Tempo investido (min)</label>
                    <input type="number" id="time_minutes" name="time_minutes" min="0" step="5"
                           value="<?= e((string) ($node['time_minutes'] ?? 0)) ?>">
                </div>
            <?php endif; ?>

            <div class="field span-2">
                <label for="source_url">Link externo</label>
                <input type="url" id="source_url" name="source_url" maxlength="600"
                       value="<?= e($node['source_url'] ?? '') ?>"
                       placeholder="https://...">
            </div>

            <?php if (!empty($config['has_file'])): ?>
                <div class="field span-2">
                    <label for="file">Arquivo <?= !empty($node['file_path']) ? '(enviar outro substitui o atual)' : '' ?></label>
                    <input type="file" id="file" name="file">
                    <small class="hint">Aceita PDFs, imagens, vídeos, drivers, firmware, pacotes e boardviews até 40 MB.</small>
                    <?php if (!empty($node['file_path'])): ?>
                        <small class="hint">Arquivo atual: <a href="/documents/file?id=<?= $node['id'] ?>" target="_blank" class="link">abrir</a></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($config['type'] === 'project'): ?>
                <div class="field span-2">
                    <label for="objective">Objetivo</label>
                    <textarea id="objective" name="objective" rows="2"><?= e($metadata['objective'] ?? '') ?></textarea>
                </div>
                <div class="field">
                    <label for="github">GitHub</label>
                    <input type="url" id="github" name="github" value="<?= e($metadata['github'] ?? '') ?>" placeholder="https://github.com/...">
                </div>
                <div class="field">
                    <label for="domain">Domínio</label>
                    <input type="text" id="domain" name="domain" value="<?= e($metadata['domain'] ?? '') ?>" placeholder="exemplo.com">
                </div>
                <div class="field">
                    <label for="server">Servidor</label>
                    <input type="text" id="server" name="server" value="<?= e($metadata['server'] ?? '') ?>" placeholder="VPS, IP, provedor...">
                </div>
                <div class="field">
                    <label for="links">Links</label>
                    <input type="text" id="links" name="links" value="<?= e($metadata['links'] ?? '') ?>" placeholder="URLs importantes">
                </div>
                <div class="field span-2">
                    <label for="technologies">Tecnologias</label>
                    <textarea id="technologies" name="technologies" rows="3" placeholder="Uma por linha"><?= e($technologies) ?></textarea>
                </div>
            <?php endif; ?>

            <?php if ($config['type'] === 'task'): ?>
                <div class="field">
                    <label for="responsible">Responsável</label>
                    <input type="text" id="responsible" name="responsible" value="<?= e($metadata['responsible'] ?? (current_user()['name'] ?? 'Usuário')) ?>">
                </div>
                <div class="field span-2">
                    <label for="checklist">Checklist</label>
                    <textarea id="checklist" name="checklist" rows="4" placeholder="Uma linha por item"><?= e($checklist) ?></textarea>
                </div>
            <?php endif; ?>

            <?php if ($config['type'] === 'diary'): ?>
                <div class="field">
                    <label for="mood">Humor</label>
                    <input type="text" id="mood" name="mood" value="<?= e($metadata['mood'] ?? '') ?>" placeholder="Calmo, focado, cansado...">
                </div>
                <div class="field">
                    <label for="productivity">Produtividade (0-10)</label>
                    <input type="number" id="productivity" name="productivity" min="0" max="10" value="<?= e((string) ($metadata['productivity'] ?? '')) ?>">
                </div>
                <div class="field span-2">
                    <label for="done">O que fiz</label>
                    <textarea id="done" name="done" rows="2"><?= e($metadata['done'] ?? '') ?></textarea>
                </div>
                <div class="field span-2">
                    <label for="learned">O que aprendi</label>
                    <textarea id="learned" name="learned" rows="2"><?= e($metadata['learned'] ?? '') ?></textarea>
                </div>
                <div class="field span-2">
                    <label for="improve">O que melhorar</label>
                    <textarea id="improve" name="improve" rows="2"><?= e($metadata['improve'] ?? '') ?></textarea>
                </div>
            <?php endif; ?>

            <?php if ($config['type'] === 'agenda_event'): ?>
                <div class="field">
                    <label for="location">Local</label>
                    <input type="text" id="location" name="location" value="<?= e($metadata['location'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="attendees">Participantes</label>
                    <input type="text" id="attendees" name="attendees" value="<?= e($metadata['attendees'] ?? '') ?>">
                </div>
            <?php endif; ?>

            <?php if ($config['type'] === 'vehicle'): ?>
                <div class="field">
                    <label for="plate">Placa</label>
                    <input type="text" id="plate" name="plate" value="<?= e($metadata['plate'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="vehicle_model">Modelo</label>
                    <input type="text" id="vehicle_model" name="vehicle_model" value="<?= e($metadata['model'] ?? '') ?>">
                </div>
                <div class="field">
                    <label for="vehicle_year">Ano</label>
                    <input type="number" id="vehicle_year" name="vehicle_year" min="1900" max="2100" value="<?= e((string) ($metadata['year'] ?? '')) ?>">
                </div>
                <div class="field">
                    <label for="km">Quilometragem</label>
                    <input type="number" id="km" name="km" min="0" value="<?= e((string) ($metadata['km'] ?? '')) ?>">
                </div>
            <?php endif; ?>

            <div class="field span-2">
                <label for="body"><?= e($config['body_label']) ?> <span class="hint">Markdown</span></label>
                <textarea id="body" name="body" rows="12" class="mono"
                          placeholder="<?= e($config['body_hint']) ?>"><?= e($node['body'] ?? '') ?></textarea>
            </div>

            <label class="check-inline span-2">
                <input type="checkbox" name="favorite" value="1" <?= !empty($node['favorite']) ? 'checked' : '' ?>>
                Favorito
            </label>
        </div>

        <div class="form-actions">
            <a href="/<?= e($config['route']) ?><?= $node ? '/view?id=' . $node['id'] : '' ?>" class="btn btn-ghost">Cancelar</a>
            <button type="submit" class="btn btn-primary"><?= $node ? 'Salvar alterações' : 'Criar' ?></button>
        </div>
    </form>
</section>
