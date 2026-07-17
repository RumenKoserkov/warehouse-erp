<?php

$escape = static function (
    mixed $value
): string {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$severityBadge = static function (
    string $severity
): string {
    if ($severity === 'critical') {
        return 'text-bg-dark';
    }

    if ($severity === 'error') {
        return 'text-bg-danger';
    }

    if ($severity === 'warning') {
        return 'text-bg-warning';
    }

    return 'text-bg-info';
};

$actionBadge = static function (
    string $action
): string {
    if (
        in_array(
            $action,
            [
                'cancel',
                'delete',
                'deactivate',
            ],
            true
        )
    ) {
        return 'text-bg-danger';
    }

    if (
        in_array(
            $action,
            [
                'create',
                'issue',
                'activate',
                'login',
            ],
            true
        )
    ) {
        return 'text-bg-success';
    }

    if ($action === 'update') {
        return 'text-bg-primary';
    }

    return 'text-bg-secondary';
};

$formatDateTime = static function (
    mixed $value
): string {
    $timestamp = strtotime(
        (string) $value
    );

    if ($timestamp === false) {
        return (string) $value;
    }

    return date(
        'd.m.Y H:i:s',
        $timestamp
    );
};
?>

<div
    class="d-flex justify-content-between
    align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">
            Activity Log
        </h1>

        <p class="text-muted mb-0">
            Security and business activity
            performed in the company account.
        </p>
    </div>

    <a
        href="/dashboard"
        class="btn btn-outline-secondary">
        Back to Dashboard
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl col-md-4 col-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Total Events
                </div>

                <div class="fs-3 fw-bold">
                    <?= (int) $summary['total_count'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-4 col-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Today
                </div>

                <div class="fs-3 fw-bold">
                    <?= (int) $summary['today_count'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-4 col-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Last 7 Days
                </div>

                <div class="fs-3 fw-bold">
                    <?= (int) $summary['last_seven_days_count'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-4 col-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Warnings — 30 Days
                </div>

                <div
                    class="fs-3 fw-bold
                    text-warning">
                    <?= (int) $summary['warning_count'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl col-md-4 col-6">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">
                    Errors — 30 Days
                </div>

                <div
                    class="fs-3 fw-bold
                    text-danger">
                    <?= (int) $summary['error_count'] ?>
                </div>
            </div>
        </div>
    </div>
</div>

<form
    method="GET"
    action="/audit-logs"
    class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-xl-2 col-md-4">
                <label
                    for="action"
                    class="form-label">
                    Action
                </label>

                <select
                    id="action"
                    name="action"
                    class="form-select">
                    <option value="">
                        All Actions
                    </option>

                    <?php foreach (
                        $actions as $action
                    ): ?>
                        <option
                            value="<?= $escape(
                                        $action
                                    ) ?>"
                            <?php if (
                                $filters['action'] === $action
                            ): ?>
                            selected
                            <?php endif; ?>>
                            <?= $escape(
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $action
                                    )
                                )
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label
                    for="severity"
                    class="form-label">
                    Severity
                </label>

                <select
                    id="severity"
                    name="severity"
                    class="form-select">
                    <option value="">
                        All Severities
                    </option>

                    <?php foreach (
                        $severities as
                        $value => $label
                    ): ?>
                        <option
                            value="<?= $escape(
                                        $value
                                    ) ?>"
                            <?php if (
                                $filters['severity'] === $value
                            ): ?>
                            selected
                            <?php endif; ?>>
                            <?= $escape($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label
                    for="entity_type"
                    class="form-label">
                    Entity Type
                </label>

                <select
                    id="entity_type"
                    name="entity_type"
                    class="form-select">
                    <option value="">
                        All Entities
                    </option>

                    <?php foreach (
                        $entityTypes as $entityType
                    ): ?>
                        <option
                            value="<?= $escape(
                                        $entityType
                                    ) ?>"
                            <?php if (
                                $filters['entity_type'] === $entityType
                            ): ?>
                            selected
                            <?php endif; ?>>
                            <?= $escape(
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $entityType
                                    )
                                )
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-xl-2 col-md-4">
                <label
                    for="entity_id"
                    class="form-label">
                    Entity ID
                </label>

                <input
                    type="number"
                    id="entity_id"
                    name="entity_id"
                    class="form-control"
                    min="1"
                    step="1"
                    value="<?= $escape(
                                $filters['entity_id']
                            ) ?>">
            </div>

            <div class="col-xl-4 col-md-8">
                <label
                    for="user_id"
                    class="form-label">
                    User
                </label>

                <select
                    id="user_id"
                    name="user_id"
                    class="form-select">
                    <option value="">
                        All Users
                    </option>

                    <?php foreach (
                        $users as $user
                    ): ?>
                        <?php
                        $userLabel =
                            (string) $user['name'] .
                            ' — ' .
                            (string) $user['email'];

                        if (
                            (int) $user['is_active'] !== 1
                        ) {
                            $userLabel .=
                                ' (inactive)';
                        }
                        ?>

                        <option
                            value="<?= (int) $user['id'] ?>"
                            <?php if (
                                (int) $filters['user_id'] ===
                                (int) $user['id']
                            ): ?>
                            selected
                            <?php endif; ?>>
                            <?= $escape(
                                $userLabel
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label
                    for="date_from"
                    class="form-label">
                    Date From
                </label>

                <input
                    type="date"
                    id="date_from"
                    name="date_from"
                    class="form-control"
                    value="<?= $escape(
                                $filters['date_from']
                            ) ?>">
            </div>

            <div class="col-md-3">
                <label
                    for="date_to"
                    class="form-label">
                    Date To
                </label>

                <input
                    type="date"
                    id="date_to"
                    name="date_to"
                    class="form-control"
                    value="<?= $escape(
                                $filters['date_to']
                            ) ?>">
            </div>

            <div class="col-md-6">
                <label
                    for="search"
                    class="form-label">
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    maxlength="255"
                    placeholder="Description, user, email, IP, request ID or URL..."
                    value="<?= $escape(
                                $filters['search']
                            ) ?>">
            </div>

            <div class="col-12">
                <div class="d-flex gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary">
                        Apply Filters
                    </button>

                    <a
                        href="/audit-logs"
                        class="btn
                        btn-outline-secondary">
                        Clear Filters
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if (empty($logs)): ?>
    <div class="alert alert-info">
        No activity log records were found.
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Severity</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>Request</th>
                        <th>IP</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $logs as $log
                    ): ?>
                        <tr>
                            <td class="text-nowrap">
                                <?= $escape(
                                    $formatDateTime(
                                        $log['created_at']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?php if (
                                    trim(
                                        (string) $log['user_name']
                                    ) !== ''
                                ): ?>
                                    <div class="fw-semibold">
                                        <?= $escape(
                                            $log['user_name']
                                        ) ?>
                                    </div>

                                    <div
                                        class="small
                                        text-muted">
                                        <?= $escape(
                                            $log['user_email']
                                        ) ?>
                                    </div>
                                <?php else: ?>
                                    <span
                                        class="text-muted">
                                        System
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span
                                    class="badge <?= $escape(
                                                        $severityBadge(
                                                            (string) $log['severity']
                                                        )
                                                    ) ?>">
                                    <?= $escape(
                                        ucfirst(
                                            (string) $log['severity']
                                        )
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <span
                                    class="badge <?= $escape(
                                                        $actionBadge(
                                                            (string) $log['action']
                                                        )
                                                    ) ?>">
                                    <?= $escape(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                (string) $log['action']
                                            )
                                        )
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (
                                    trim(
                                        (string) $log['entity_type']
                                    ) !== ''
                                ): ?>
                                    <div class="fw-semibold">
                                        <?= $escape(
                                            ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    (string) $log['entity_type']
                                                )
                                            )
                                        ) ?>
                                    </div>

                                    <?php if (
                                        $log['entity_id'] !== null
                                    ): ?>
                                        <div
                                            class="small
                                            text-muted">
                                            ID:
                                            <?= (int) $log['entity_id'] ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td style="min-width: 260px;">
                                <?= $escape(
                                    $log['description']
                                ) ?>

                                <?php if (
                                    trim(
                                        (string) $log['context']
                                    ) !== ''
                                ): ?>
                                    <div class="mt-1">
                                        <span
                                            class="badge
                                            text-bg-light
                                            border">
                                            Context available
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (
                                    trim(
                                        (string) $log['request_method']
                                    ) !== ''
                                ): ?>
                                    <span
                                        class="badge
                                        text-bg-light
                                        border">
                                        <?= $escape(
                                            $log['request_method']
                                        ) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if (
                                    trim(
                                        (string) $log['request_id']
                                    ) !== ''
                                ): ?>
                                    <div
                                        class="small
                                        text-muted
                                        font-monospace
                                        mt-1">
                                        <?= $escape(
                                            substr(
                                                (string) $log['request_id'],
                                                0,
                                                8
                                            )
                                        ) ?>…
                                    </div>
                                <?php endif; ?>

                                <?php if (
                                    trim(
                                        (string) $log['request_method']
                                    ) === '' &&
                                    trim(
                                        (string) $log['request_id']
                                    ) === ''
                                ): ?>
                                    <span class="text-muted">
                                        —
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="text-nowrap">
                                <?= $escape(
                                    $log['ip_address']
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/audit-logs/show?id=<?= (int) $log['id'] ?>"
                                    class="btn btn-sm
                                    btn-outline-primary">
                                    Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            <?php require dirname(
                __DIR__
            ) . '/components/pagination.php'; ?>
        </div>
    </div>
<?php endif; ?>