@php
    $steps = old('steps', $flowChart->steps ?? []);
@endphp

<form method="POST" action="{{ $action }}" class="card p-2" data-flow-chart-category="{{ old('category', $flowChart->category ?? 'website') }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    @if ($method === 'POST' && !empty($projectId))
        <input type="hidden" name="project_id" value="{{ $projectId }}">
    @endif
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="fw-semibold mb-1">Please correct the following:</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label" for="title">Flow chart name</label>
            <input class="form-control" id="title" name="title" value="{{ old('title', $flowChart->title) }}"
                maxlength="255" required>
            @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label class="form-label" for="category">Flow chart type</label>
            <select class="form-select" id="category" name="category" required>
                @foreach (\App\Models\FlowChart::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $flowChart->category ?? 'website') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4" id="sop-subject-field">
            <label class="form-label" for="subject_name">Product, service, or programme name <span class="text-body-secondary">(for SOPs)</span></label>
            <input class="form-control" id="subject_name" name="subject_name" value="{{ old('subject_name', $flowChart->subject_name) }}" maxlength="255">
            @error('subject_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label class="form-label" for="description">Description <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $flowChart->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="form-check mb-4">
            <input type="hidden" name="is_example" value="0">
            <input class="form-check-input" type="checkbox" id="is_example" name="is_example" value="1"
                @checked(old('is_example', $flowChart->is_example ?? false))>
            <label class="form-check-label" for="is_example">Example flow chart or SOP</label>
            <div class="form-text">Examples appear in the SOP library and can only be duplicated into projects.</div>
        </div>
        @error('is_example')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
            <div>
                <h2 class="h5 mb-1" id="flow-steps-heading">Website journey pages and connections</h2>
                <p class="small text-body-secondary mb-0" id="flow-steps-help">Use Connect to link pages and create branches.</p>
            </div>
            <button class="btn btn-sm btn-outline-primary" type="button" id="add-flow-step">
                <i class="fas fa-plus me-1"></i>Add step
            </button>
        </div>

        <div id="flow-steps" class="d-grid gap-2">
            @foreach ($steps as $index => $step)
                <div class="flow-step-row flow-chart-editor-node border rounded-2 p-3" data-step-id="{{ $step['id'] ?? '' }}">
                    <input class="step-id-input" type="hidden" name="steps[{{ $index }}][id]" value="{{ $step['id'] ?? '' }}">
                    @if (isset($step['x'], $step['y']))
                        <input class="step-position-x" type="hidden" name="steps[{{ $index }}][x]" value="{{ $step['x'] }}">
                        <input class="step-position-y" type="hidden" name="steps[{{ $index }}][y]" value="{{ $step['y'] }}">
                    @endif
                    <div class="flow-chart-editor-heading mb-2">
                        <span class="flow-chart-node-badge">{{ $index + 1 }}</span>
                        <span class="fw-semibold">Node {{ $index + 1 }}</span>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label" for="step-label-{{ $index }}">Step name</label>
                            <input class="form-control" id="step-label-{{ $index }}"
                                name="steps[{{ $index }}][label]" value="{{ $step['label'] ?? '' }}"
                                maxlength="255" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="step-kind-{{ $index }}">Step type</label>
                            <select class="form-select" id="step-kind-{{ $index }}" name="steps[{{ $index }}][kind]" required>
                                @foreach (['page' => 'Website page', 'action' => 'User action', 'decision' => 'Decision', 'start' => 'Start', 'end' => 'End'] as $kind => $label)
                                    <option value="{{ $kind }}" @selected(($step['kind'] ?? 'page') === $kind)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-outline-danger remove-flow-step" type="button" aria-label="Remove step">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <fieldset class="flow-step-connections mt-3">
                        <legend class="form-label fs-9 mb-2">Connected steps</legend>
                        <span class="badge text-bg-light border" data-flow-connection-count>{{ count($step['connections'] ?? []) }}</span>
                        <div class="flow-step-targets" hidden>
                            @foreach ($steps as $targetIndex => $target)
                                @if ($targetIndex !== $index && in_array($target['id'] ?? '', $step['connections'] ?? [], true))
                                    <input class="flow-step-connection-input" type="hidden"
                                        name="steps[{{ $index }}][connections][]" value="{{ $target['id'] ?? '' }}">
                                    @if (isset($step['connection_sides'][$target['id'] ?? '']))
                                        @php
                                            $savedSides = $step['connection_sides'][$target['id']];
                                            $sourceSide = is_array($savedSides) ? ($savedSides['source'] ?? 'bottom') : $savedSides;
                                            $targetSide = is_array($savedSides) ? ($savedSides['target'] ?? 'top') : match ($savedSides) {
                                                'top' => 'bottom',
                                                'bottom' => 'top',
                                                'left' => 'right',
                                                'right' => 'left',
                                                default => 'top',
                                            };
                                        @endphp
                                        <input class="flow-step-connection-side-input" type="hidden"
                                            name="steps[{{ $index }}][connection_sides][{{ $target['id'] }}][source]"
                                            data-target-id="{{ $target['id'] }}" data-side-endpoint="source"
                                            value="{{ $sourceSide }}">
                                        <input class="flow-step-connection-side-input" type="hidden"
                                            name="steps[{{ $index }}][connection_sides][{{ $target['id'] }}][target]"
                                            data-target-id="{{ $target['id'] }}" data-side-endpoint="target"
                                            value="{{ $targetSide }}">
                                    @endif
                                @endif
                            @endforeach
                        </div>
                        <div class="flow-step-connection-list d-flex flex-wrap gap-2 mt-2"></div>
                    </fieldset>
                </div>
            @endforeach
        </div>
        @error('steps')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('steps.*.label')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('steps.*.kind')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('steps.*.connections')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('steps.*.connection_sides.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

        <section class="mt-4 pt-4 border-top" aria-labelledby="flow-chart-preview-heading">
            <div class="mb-3">
                <h2 class="h5 mb-1" id="flow-chart-preview-heading">Live preview</h2>
                <p class="small text-body-secondary mb-0">Choose a side arrow on the source node, then choose a side arrow on the destination node.</p>
            </div>
            <div class="flow-chart-viewport" data-flow-chart-preview tabindex="0" role="region" aria-label="Flow chart preview">
                <div class="flow-chart-canvas">
                    <svg class="flow-chart-wires" aria-hidden="true">
                        <defs>
                            <marker id="flow-chart-preview-arrow" viewBox="0 0 10 10" markerWidth="12" markerHeight="12"
                                refX="10" refY="5" orient="auto" markerUnits="userSpaceOnUse">
                                <path d="M 0 0 L 10 5 L 0 10 z" fill="var(--falcon-primary)" stroke="none"></path>
                            </marker>
                        </defs>
                    </svg>
                    <p class="small text-body-secondary position-absolute top-50 start-50 translate-middle mb-0 text-center"
                        data-flow-chart-preview-empty>Add a step above to preview your flow chart.</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2" aria-live="polite">
                <p class="small text-body-secondary mb-0" data-flow-chart-connection-status>
                    Select a source side, then select a destination side on another node.
                </p>
                <button class="btn btn-sm btn-link p-0" type="button" data-flow-chart-connection-cancel hidden>Cancel connection</button>
            </div>
        </section>
    </div>
    <div class="card-footer bg-body d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('development.flowcharts.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">{{ $buttonLabel }}</button>
    </div>
</form>

@push('scripts')
<style>
.flow-chart-preview-node{cursor:default}
.flow-chart-preview-node.is-connection-target{cursor:pointer}
.flow-chart-preview-node.is-connection-source{outline:2px dashed var(--falcon-primary);outline-offset:3px}
.flow-chart-preview-port{position:absolute;z-index:3;display:grid;width:1.6rem;height:1.6rem;place-items:center;padding:0;border:1px solid var(--falcon-primary);border-radius:50%;background:var(--falcon-card-bg,var(--falcon-body-bg));color:var(--falcon-primary);font-size:.7rem}
.flow-chart-preview-node.is-connection-target .flow-chart-preview-port{width:1.9rem;height:1.9rem;background:var(--falcon-primary);color:#fff;box-shadow:0 0 0 .2rem rgba(var(--falcon-primary-rgb),.18)}
.flow-chart-preview-port:hover,.flow-chart-preview-port.is-selected{background:var(--falcon-primary);color:#fff}
.flow-chart-preview-port-top{top:-.8rem;left:50%;transform:translateX(-50%)}
.flow-chart-preview-port-bottom{bottom:-.8rem;left:50%;transform:translateX(-50%)}
.flow-chart-preview-port-left{top:50%;left:-.8rem;transform:translateY(-50%)}
.flow-chart-preview-port-right{top:50%;right:-.8rem;transform:translateY(-50%)}
</style>
<script>
    (() => {
        const container = document.getElementById('flow-steps');
        const addButton = document.getElementById('add-flow-step');
        const maxSteps = 30;
        const categorySelect = document.getElementById('category');
        const subjectField = document.getElementById('sop-subject-field');
        const previewViewport = document.querySelector('[data-flow-chart-preview]');
        const previewCanvas = previewViewport.querySelector('.flow-chart-canvas');
        const previewSvg = previewCanvas.querySelector('.flow-chart-wires');
        const previewEmpty = previewCanvas.querySelector('[data-flow-chart-preview-empty]');
        const previewArrowId = 'flow-chart-preview-arrow';
        const connectionStatus = document.querySelector('[data-flow-chart-connection-status]');
        const cancelConnectionButton = document.querySelector('[data-flow-chart-connection-cancel]');
        let pendingConnection = null;
        const connectionSides = ['top', 'bottom', 'left', 'right'];
        const oppositeSide = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' };

        const renderPreview = () => {
            const rows = [...container.querySelectorAll('.flow-step-row')];
            const steps = rows.map((row, index) => ({
                id: row.dataset.stepId,
                label: row.querySelector('[name$="[label]"]').value.trim() || `Untitled step ${index + 1}`,
                kind: row.querySelector('[name$="[kind]"]').value,
                connections: [...row.querySelectorAll('.flow-step-connection-input')].map((input) => input.value),
                connectionSides: [...row.querySelectorAll('.flow-step-connection-side-input')]
                    .reduce((sides, input) => {
                        sides[input.dataset.targetId] ||= {};
                        sides[input.dataset.targetId][input.dataset.sideEndpoint || 'source'] = input.value;
                        return sides;
                    }, {}),
                positionX: row.querySelector('.step-position-x')?.value,
                positionY: row.querySelector('.step-position-y')?.value,
            }));
            const nodes = steps.map((step) => {
                const node = document.createElement('article');
                node.className = `flow-chart-node-card flow-chart-node-${step.kind} flow-chart-preview-node`;
                node.dataset.previewNodeId = step.id;
                if (pendingConnection?.sourceId === step.id) node.classList.add('is-connection-source');
                if (pendingConnection && pendingConnection.sourceId !== step.id) node.classList.add('is-connection-target');

                const kind = document.createElement('span');
                kind.className = 'flow-chart-node-kind';
                kind.textContent = step.kind === 'page' && !['website', 'website_structure', 'sitemap'].includes(categorySelect.value) ? 'step' : step.kind;

                const label = document.createElement('strong');
                label.textContent = step.label;
                node.append(kind, label);
                connectionSides.forEach((side) => {
                    const port = document.createElement('button');
                    port.className = `flow-chart-preview-port flow-chart-preview-port-${side}`;
                    port.type = 'button';
                    port.dataset.connectionSide = side;
                    const isPendingTarget = pendingConnection && pendingConnection.sourceId !== step.id;
                    port.setAttribute('aria-label', isPendingTarget
                        ? `Connect to ${side} side of ${step.label}`
                        : `Connect from ${side} side of ${step.label}`);
                    port.title = `${isPendingTarget ? 'Connect to' : 'Connect from'} ${side}`;
                    port.textContent = ({ top: '↑', bottom: '↓', left: '←', right: '→' })[side];
                    if (pendingConnection?.sourceId === step.id && pendingConnection.sourceSide === side) {
                        port.classList.add('is-selected');
                    }
                    node.append(port);
                });
                return node;
            });

            previewEmpty.hidden = steps.length > 0;
            previewSvg.hidden = steps.length === 0;
            previewCanvas.replaceChildren(previewSvg, previewEmpty, ...nodes);

            if (!steps.length) {
                previewCanvas.style.width = '';
                previewCanvas.style.height = '';
                return;
            }

            const gapX = 250;
            const gapY = 340;
            const padding = 40;
            const connections = new Map(steps.map((step) => [step.id, step.connections]));
            const incoming = new Map(steps.map((step) => [step.id, 0]));
            connections.forEach((targets) => targets.forEach((targetId) => {
                if (incoming.has(targetId)) incoming.set(targetId, incoming.get(targetId) + 1);
            }));

            const levels = new Map();
            const placeComponent = (start) => {
                if (levels.has(start.id)) return;
                const queue = [start];
                levels.set(start.id, 0);
                for (let index = 0; index < queue.length; index += 1) {
                    const source = queue[index];
                    const nextLevel = levels.get(source.id) + 1;
                    (connections.get(source.id) || []).forEach((targetId) => {
                        if (levels.has(targetId)) return;
                        const target = steps.find((step) => step.id === targetId);
                        if (!target) return;
                        levels.set(targetId, nextLevel);
                        queue.push(target);
                    });
                }
            };

            steps.filter((step) => incoming.get(step.id) === 0).forEach(placeComponent);
            steps.forEach(placeComponent);

            const rowsByLevel = new Map();
            steps.forEach((step) => {
                const level = levels.get(step.id);
                if (!rowsByLevel.has(level)) rowsByLevel.set(level, []);
                rowsByLevel.get(level).push(step);
            });

            const widestRow = Math.max(...[...rowsByLevel.values()].map((row) => row.length));
            const width = Math.max(previewViewport.clientWidth - 2, widestRow * gapX + padding * 2);
            const height = Math.max(220, (Math.max(...levels.values()) + 1) * gapY + 122);
            previewCanvas.style.width = `${width}px`;
            previewCanvas.style.height = `${height}px`;
            previewSvg.setAttribute('viewBox', `0 0 ${width} ${height}`);
            previewSvg.setAttribute('width', width);
            previewSvg.setAttribute('height', height);
            previewSvg.querySelectorAll('.flow-chart-wire').forEach((wire) => wire.remove());

            rowsByLevel.forEach((row, level) => {
                const rowWidth = row.length * gapX;
                const startX = Math.max(padding, (width - rowWidth) / 2);
                row.forEach((step, index) => {
                    const nodeIndex = steps.indexOf(step);
                    const node = nodes[nodeIndex];
                    const hasSavedPosition = Number.isFinite(Number(step.positionX))
                        && Number.isFinite(Number(step.positionY))
                        && step.positionX !== undefined && step.positionY !== undefined;
                    node.style.left = `${hasSavedPosition ? Number(step.positionX) : startX + index * gapX}px`;
                    node.style.top = `${hasSavedPosition ? Number(step.positionY) : padding + level * gapY}px`;
                });
            });

            steps.forEach((step, index) => {
                const source = nodes[index];
                (connections.get(step.id) || []).forEach((targetId) => {
                    const target = nodes[steps.findIndex((candidate) => candidate.id === targetId)];
                    if (!target) return;
                    const sourceCenterX = source.offsetLeft + source.offsetWidth / 2;
                    const sourceCenterY = source.offsetTop + source.offsetHeight / 2;
                    const targetCenterX = target.offsetLeft + target.offsetWidth / 2;
                    const targetCenterY = target.offsetTop + target.offsetHeight / 2;
                    const dx = targetCenterX - sourceCenterX;
                    const dy = targetCenterY - sourceCenterY;
                    const savedSides = step.connectionSides[targetId] || {};
                    const side = savedSides.source
                        || (typeof savedSides === 'string' ? savedSides : null)
                        || (Math.abs(dx) > Math.abs(dy) ? (dx >= 0 ? 'right' : 'left') : (dy >= 0 ? 'bottom' : 'top'));
                    const sourcePoint = nodePort(source, side, 0);
                    const targetSide = savedSides.target || oppositeSide[side];
                    const targetPoint = nodePort(target, targetSide, 9);
                    const distance = Math.hypot(targetPoint.x - sourcePoint.x, targetPoint.y - sourcePoint.y);
                    const control = Math.max(40, Math.min(150, distance * 0.45));
                    const sourceVector = sideVector(side);
                    const targetVector = sideVector(targetSide);
                    const wire = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    wire.setAttribute('class', 'flow-chart-wire');
                    wire.setAttribute('d', `M ${sourcePoint.x} ${sourcePoint.y} C ${sourcePoint.x + sourceVector.x * control} ${sourcePoint.y + sourceVector.y * control}, ${targetPoint.x + targetVector.x * control} ${targetPoint.y + targetVector.y * control}, ${targetPoint.x} ${targetPoint.y}`);
                    wire.setAttribute('marker-end', `url(#${previewArrowId})`);
                    previewSvg.append(wire);
                });
            });
        };

        const sideVector = (side) => ({
            top: { x: 0, y: -1 },
            bottom: { x: 0, y: 1 },
            left: { x: -1, y: 0 },
            right: { x: 1, y: 0 },
        })[side];

        const nodePort = (node, side, offset) => {
            const vector = sideVector(side);
            const centerX = node.offsetLeft + node.offsetWidth / 2;
            const centerY = node.offsetTop + node.offsetHeight / 2;
            return {
                x: centerX + vector.x * (node.offsetWidth / 2 + offset),
                y: centerY + vector.y * (node.offsetHeight / 2 + offset),
            };
        };

        const setPendingConnection = (nodeId, side) => {
            if (!pendingConnection) {
                pendingConnection = { sourceId: nodeId, sourceSide: side };
                connectionStatus.textContent = `Source: ${side}. Choose a side on the destination node.`;
            } else if (pendingConnection.sourceId === nodeId) {
                pendingConnection = { sourceId: nodeId, sourceSide: side };
                connectionStatus.textContent = `Source: ${side}. Choose a side on the destination node.`;
            } else {
                saveConnection(
                    pendingConnection.sourceId,
                    nodeId,
                    pendingConnection.sourceSide,
                    side,
                );
                updateRows();
                clearPendingConnection('Connection added. Choose another source side to continue.');
                return;
            }
            cancelConnectionButton.hidden = false;
            renderPreview();
        };

        const clearPendingConnection = (message) => {
            pendingConnection = null;
            connectionStatus.textContent = message || 'Select a source side, then select a destination side on another node.';
            cancelConnectionButton.hidden = true;
            renderPreview();
        };

        const saveConnection = (sourceId, targetId, sourceSide, targetSide) => {
            const sourceRow = [...container.querySelectorAll('.flow-step-row')]
                .find((row) => row.dataset.stepId === sourceId);
            if (!sourceRow) return;
            let connection = [...sourceRow.querySelectorAll('.flow-step-connection-input')]
                .find((input) => input.value === targetId);
            if (!connection) {
                connection = document.createElement('input');
                connection.className = 'flow-step-connection-input';
                connection.type = 'hidden';
                connection.value = targetId;
                sourceRow.querySelector('.flow-step-targets').append(connection);
            }

            ['source', 'target'].forEach((endpoint) => {
                let sideInput = [...sourceRow.querySelectorAll('.flow-step-connection-side-input')]
                    .find((input) => input.dataset.targetId === targetId && input.dataset.sideEndpoint === endpoint);
                if (!sideInput) {
                    sideInput = document.createElement('input');
                    sideInput.className = 'flow-step-connection-side-input';
                    sideInput.type = 'hidden';
                    sideInput.dataset.targetId = targetId;
                    sideInput.dataset.sideEndpoint = endpoint;
                    sourceRow.querySelector('.flow-step-targets').append(sideInput);
                }
                sideInput.value = endpoint === 'source' ? sourceSide : targetSide;
            });
        };

        cancelConnectionButton.addEventListener('click', () => clearPendingConnection());
        previewCanvas.addEventListener('click', (event) => {
            const clickedElement = event.target instanceof Element ? event.target : null;
            const port = clickedElement?.closest('[data-connection-side]');
            const node = port?.closest('[data-preview-node-id]')
                || clickedElement?.closest('[data-preview-node-id]');
            if (!node) return;
            if (port) {
                setPendingConnection(node.dataset.previewNodeId, port.dataset.connectionSide);
                return;
            }

            if (pendingConnection && pendingConnection.sourceId !== node.dataset.previewNodeId) {
                setPendingConnection(
                    node.dataset.previewNodeId,
                    oppositeSide[pendingConnection.sourceSide],
                );
            }
        });

        const syncPurpose = () => {
            const website = ['website', 'website_structure', 'sitemap'].includes(categorySelect.value);
            const websiteTypeLabels = {
                website: 'Website journey',
                website_structure: 'Website structure',
                sitemap: 'Sitemap',
            };
            document.getElementById('flow-steps-heading').textContent = website
                ? `${websiteTypeLabels[categorySelect.value]} pages and connections`
                : 'Process steps and connections';
            document.getElementById('flow-steps-help').textContent = website
                ? 'Add website pages, then use the preview arrows to connect them.'
                : 'Describe the actions, decisions and hand-offs, then connect them from the preview.';
            subjectField.hidden = !['product', 'service', 'education'].includes(categorySelect.value);
            document.getElementById('subject_name').required = !subjectField.hidden;
            document.querySelectorAll('.flow-chart-editor-node select[name$="[kind]"] option[value="page"]').forEach((option) => {
                option.textContent = website ? 'Website page' : 'Step';
            });
            renderPreview();
        };
        categorySelect.addEventListener('change', syncPurpose);

        const updateRows = () => {
            const rows = [...container.querySelectorAll('.flow-step-row')];
            rows.forEach((row, index) => {
                const stepId = row.dataset.stepId || (window.crypto?.randomUUID
                    ? window.crypto.randomUUID()
                    : `step-${Date.now()}-${Math.random().toString(36).slice(2)}`);
                row.dataset.stepId = stepId;
                row.querySelector('.flow-chart-node-badge').textContent = index + 1;
                row.querySelector('.flow-chart-editor-heading .fw-semibold').textContent = `Node ${index + 1}`;
                const idInput = row.querySelector('.step-id-input');
                idInput.name = `steps[${index}][id]`;
                idInput.value = stepId;
                const positionX = row.querySelector('.step-position-x');
                const positionY = row.querySelector('.step-position-y');
                if (positionX) positionX.name = `steps[${index}][x]`;
                if (positionY) positionY.name = `steps[${index}][y]`;
                row.querySelector('label[for^="step-label-"]').htmlFor = `step-label-${index}`;
                row.querySelector('[name$="[label]"]').id = `step-label-${index}`;
                row.querySelector('label[for^="step-kind-"]').htmlFor = `step-kind-${index}`;
                row.querySelector('[name$="[kind]"]').id = `step-kind-${index}`;
                row.querySelector('[name$="[label]"]').name = `steps[${index}][label]`;
                row.querySelector('[name$="[kind]"]').name = `steps[${index}][kind]`;

                const selectedConnections = new Map(
                    [...row.querySelectorAll('.flow-step-connection-input')].map((input) => {
                        const sides = [...row.querySelectorAll('.flow-step-connection-side-input')]
                            .filter((candidate) => candidate.dataset.targetId === input.value)
                            .reduce((savedSides, candidate) => {
                                savedSides[candidate.dataset.sideEndpoint || 'source'] = candidate.value;
                                return savedSides;
                            }, {});
                        return [input.value, sides];
                    })
                );
                const targets = row.querySelector('.flow-step-targets');
                targets.replaceChildren();
                const connectionList = row.querySelector('.flow-step-connection-list');
                connectionList.replaceChildren();
                rows.forEach((target, targetIndex) => {
                    if (target === row) return;

                    const targetId = target.dataset.stepId;
                    if (!selectedConnections.has(targetId)) return;

                    const input = document.createElement('input');
                    input.className = 'flow-step-connection-input';
                    input.type = 'hidden';
                    input.name = `steps[${index}][connections][]`;
                    input.value = targetId;
                    targets.append(input);

                    const sides = selectedConnections.get(targetId);
                    if (Object.keys(sides).length) {
                        const sourceSide = sides.source || (typeof sides === 'string' ? sides : '');
                        const targetSide = sides.target || (sourceSide ? oppositeSide[sourceSide] : '');
                        [['source', sourceSide], ['target', targetSide]].forEach(([endpoint, side]) => {
                            if (!side) return;
                            const sideInput = document.createElement('input');
                            sideInput.className = 'flow-step-connection-side-input';
                            sideInput.type = 'hidden';
                            sideInput.name = `steps[${index}][connection_sides][${targetId}][${endpoint}]`;
                            sideInput.dataset.targetId = targetId;
                            sideInput.dataset.sideEndpoint = endpoint;
                            sideInput.value = side;
                            targets.append(sideInput);
                        });
                    }

                    const connection = document.createElement('span');
                    connection.className = 'badge text-bg-light border d-inline-flex align-items-center gap-1';
                    const label = document.createElement('span');
                    label.textContent = target.querySelector('[name$="[label]"]').value.trim()
                        || `Untitled step ${targetIndex + 1}`;
                    const direction = document.createElement('span');
                    const sourceSide = sides.source || '';
                    const targetSide = sides.target || (sourceSide ? oppositeSide[sourceSide] : '');
                    direction.className = 'text-body-secondary';
                    direction.textContent = sourceSide && targetSide
                        ? `(${sourceSide} → ${targetSide})`
                        : '';
                    const remove = document.createElement('button');
                    remove.className = 'btn-close flow-step-remove-connection';
                    remove.type = 'button';
                    remove.dataset.targetId = targetId;
                    remove.setAttribute('aria-label', `Remove connection to ${label.textContent}`);
                    remove.title = 'Remove connection';
                    connection.append(label, direction, remove);
                    connectionList.append(connection);
                });

                if (connectionList.children.length === 0) {
                    const empty = document.createElement('span');
                    empty.className = 'small text-body-secondary';
                    empty.textContent = 'No connections yet.';
                    connectionList.append(empty);
                }
                row.querySelector('[data-flow-connection-count]').textContent = selectedConnections.size;
            });
            addButton.disabled = container.children.length >= maxSteps;
            renderPreview();
        };

        addButton.addEventListener('click', () => {
            if (container.children.length >= maxSteps) return;

            const index = container.children.length;
            const row = document.createElement('div');
            row.className = 'flow-step-row flow-chart-editor-node border rounded-2 p-3';
            row.dataset.stepId = window.crypto?.randomUUID
                ? window.crypto.randomUUID()
                : `step-${Date.now()}-${Math.random().toString(36).slice(2)}`;
            row.innerHTML = `
                <input class="step-id-input" type="hidden">
                <div class="flow-chart-editor-heading mb-2">
                    <span class="flow-chart-node-badge"></span>
                    <span class="fw-semibold"></span>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label" for="step-label-${index}">Step name</label>
                        <input class="form-control" id="step-label-${index}" name="steps[${index}][label]" maxlength="255" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="step-kind-${index}">Step type</label>
                        <select class="form-select" id="step-kind-${index}" name="steps[${index}][kind]" required>
                            <option value="page">${['website', 'website_structure', 'sitemap'].includes(categorySelect.value) ? 'Website page' : 'Step'}</option>
                            <option value="action">User action</option>
                            <option value="decision">Decision</option>
                            <option value="start">Start</option>
                            <option value="end">End</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-outline-danger remove-flow-step" type="button" aria-label="Remove step">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <fieldset class="flow-step-connections mt-3">
                    <legend class="form-label fs-9 mb-2">Connected steps <span class="badge text-bg-light border" data-flow-connection-count>0</span></legend>
                    <div class="flow-step-targets" hidden></div>
                    <div class="flow-step-connection-list d-flex flex-wrap gap-2 mt-2"></div>
                </fieldset>`;
            container.append(row);
            updateRows();
            row.querySelector('[name$="[label]"]').focus();
        });

        container.addEventListener('click', (event) => {
            const removeConnection = event.target.closest('.flow-step-remove-connection');
            if (removeConnection) {
                const row = removeConnection.closest('.flow-step-row');
                const targetId = removeConnection.dataset.targetId;
                row.querySelectorAll('.flow-step-connection-input, .flow-step-connection-side-input')
                    .forEach((input) => {
                        if (input.value === targetId || input.dataset.targetId === targetId) input.remove();
                    });
                updateRows();
                return;
            }

            const button = event.target.closest('.remove-flow-step');
            if (!button) return;
            if (container.children.length <= 1) {
                container.querySelector('[name$="[label]"]').setCustomValidity('A flow chart needs at least one step.');
                container.querySelector('[name$="[label]"]').reportValidity();
                container.querySelector('[name$="[label]"]').setCustomValidity('');
                return;
            }
            const removedRow = button.closest('.flow-step-row');
            if (pendingConnection?.sourceId === removedRow.dataset.stepId) {
                pendingConnection = null;
                connectionStatus.textContent = 'Select Top, Bottom, Left, or Right on a node, then choose a destination node.';
                cancelConnectionButton.hidden = true;
            }
            removedRow.remove();
            updateRows();
        });

        container.addEventListener('input', (event) => {
            if (event.target.matches('[name$="[label]"]')) updateRows();
        });
        container.addEventListener('change', renderPreview);

        updateRows();
        syncPurpose();
    })();
</script>
@endpush
