<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Audit Logs</h1>

    <a href="/dashboard" class="btn btn-outline-secondary">
        Back to Dashboard
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="/audit-logs">
            <div class="row">
                <div class="col-md-2 mb-3">
                    <label class="form-label">Action</label>

                    <select name="action" class="form-select">
                        <option value="">All actions</option>

                        <?php foreach ($actions as $action): ?>
                            <option 
                                value="<?= htmlspecialchars($action) ?>"
                                <?php if ($filters['action'] === $action): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars($action) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Entity Type</label>

                    <select name="entity_type" class="form-select">
                        <option value="">All entities</option>

                        <?php foreach ($entityTypes as $entityType): ?>
                            <option 
                                value="<?= htmlspecialchars($entityType) ?>"
                                <?php if ($filters['entity_type'] === $entityType): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars($entityType) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Date From</label>

                    <input 
                        type="date" 
                        name="date_from" 
                        class="form-control"
                        value="<?= htmlspecialchars($filters['date_from']) ?>"
                    >
                </div>

                <div class="col-md-2 mb-3">
                    <label class="form-label">Date To</label>

                    <input 
                        type="date" 
                        name="date_to" 
                        class="form-control"
                        value="<?= htmlspecialchars($filters['date_to']) ?>"
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Search</label>

                    <input 
                        type="text" 
                        name="search" 
                        class="form-control"
                        placeholder="Description, user, email, IP..."
                        value="<?= htmlspecialchars($filters['search']) ?>"
                    >
                </div>

                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        Filter
                    </button>
                </div>
            </div>

            <a href="/audit-logs" class="btn btn-sm btn-outline-secondary">
                Clear Filters
            </a>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <p class="text-muted mb-0">
                No audit logs found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Description</th>
                            <th>IP</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($log['created_at']) ?>
                                </td>

                                <td>
                                    <?php if (!empty($log['user_name'])): ?>
                                        <strong>
                                            <?= htmlspecialchars($log['user_name']) ?>
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            <?= htmlspecialchars($log['user_email']) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">System</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($log['action'] === 'create'): ?>
                                        <span class="badge text-bg-success">create</span>
                                    <?php elseif ($log['action'] === 'update'): ?>
                                        <span class="badge text-bg-primary">update</span>
                                    <?php elseif ($log['action'] === 'deactivate' || $log['action'] === 'cancel'): ?>
                                        <span class="badge text-bg-danger">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    <?php elseif (
                                        $log['action'] === 'stock_in' ||
                                        $log['action'] === 'stock_out' ||
                                        $log['action'] === 'stock_transfer'
                                    ): ?>
                                        <span class="badge text-bg-warning">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($log['entity_type']) ?>
                                    </strong>

                                    <?php if (!empty($log['entity_id'])): ?>
                                        <br>
                                        <small class="text-muted">
                                            ID: <?= htmlspecialchars((string)$log['entity_id']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($log['description']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($log['ip_address']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="text-muted mt-3 mb-0">
                Showing latest 200 records.
            </p>
        <?php endif; ?>
    </div>
</div>