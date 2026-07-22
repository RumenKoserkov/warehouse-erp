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

$statusClass = static function (
    string $status
): string {
    return match ($status) {
        'completed',
        'validated' =>
            'text-bg-success',

        'completed_with_errors',
        'validated_with_errors' =>
            'text-bg-warning',

        'failed' =>
            'text-bg-danger',

        default =>
            'text-bg-secondary',
    };
};
?>

<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            CSV Import
        </h1>

        <p class="text-muted mb-0">
            Import products, business partners
            and opening stock.
        </p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <form
            method="POST"
            action="/imports/csv/process"
            enctype="multipart/form-data"
            class="card shadow-sm"
        >
            <?= \App\Core\Csrf::field() ?>

            <div class="card-header">
                <h2 class="h5 mb-0">
                    New CSV Import
                </h2>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label
                            for="import_type"
                            class="form-label"
                        >
                            Import Type
                        </label>

                        <select
                            id="import_type"
                            name="import_type"
                            class="form-select"
                            required
                        >
                            <?php foreach (
                                $importTypes as
                                $value => $label
                            ): ?>
                                <option
                                    value="<?= $escape(
                                        $value
                                    ) ?>"
                                >
                                    <?= $escape(
                                        $label
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label
                            for="import_mode"
                            class="form-label"
                        >
                            Import Mode
                        </label>

                        <select
                            id="import_mode"
                            name="import_mode"
                            class="form-select"
                            required
                        >
                            <?php foreach (
                                $importModes as
                                $value => $label
                            ): ?>
                                <option
                                    value="<?= $escape(
                                        $value
                                    ) ?>"
                                >
                                    <?= $escape(
                                        $label
                                    ) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="form-text">
                            Opening Stock always uses
                            Create Only mode.
                        </div>
                    </div>

                    <div class="col-12">
                        <label
                            for="csv_file"
                            class="form-label"
                        >
                            CSV File
                        </label>

                        <input
                            type="file"
                            id="csv_file"
                            name="csv_file"
                            class="form-control"
                            accept=".csv,text/csv"
                            required
                        >

                        <div class="form-text">
                            Maximum 5 MB and 10,000
                            data rows.
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="validate_only"
                                name="validate_only"
                                value="1"
                                class="form-check-input"
                            >

                            <label
                                for="validate_only"
                                class="form-check-label"
                            >
                                Validate Only
                            </label>
                        </div>

                        <div class="form-text">
                            Checks every row without
                            changing the database.
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Process CSV
                </button>
            </div>
        </form>
    </div>

    <div class="col-xl-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="h5 mb-0">
                    CSV Templates
                </h2>
            </div>

            <div class="list-group list-group-flush">
                <?php foreach (
                    $importTypes as
                    $value => $label
                ): ?>
                    <a
                        href="/imports/csv/template?type=<?= urlencode($value) ?>"
                        class="list-group-item
                        list-group-item-action
                        d-flex justify-content-between
                        align-items-center"
                    >
                        <span>
                            <?= $escape($label) ?>
                        </span>

                        <span
                            class="badge
                            text-bg-success"
                        >
                            Download
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="alert alert-warning mt-4">
            <strong>Opening Stock:</strong>

            Import it only before normal warehouse
            operations begin. Existing product and
            warehouse movements block the row.
        </div>

        <div class="alert alert-info">
            Category names, product codes and
            warehouse codes must already exist
            exactly as written in the ERP.
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Recent Imports
        </h2>
    </div>

    <?php if (empty($batches)): ?>
        <div class="card-body">
            <div class="alert alert-info mb-0">
                No CSV imports have been processed.
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>File</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Rows</th>
                        <th>Successful</th>
                        <th>Failed</th>
                        <th>User</th>
                        <th>Started</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $batches as $batch
                    ): ?>
                        <tr>
                            <td>
                                #<?= (int) $batch['id'] ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $importTypes[
                                        $batch[
                                            'import_type'
                                        ]
                                    ] ??
                                    $batch[
                                        'import_type'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $batch[
                                        'original_filename'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $importModes[
                                        $batch[
                                            'import_mode'
                                        ]
                                    ] ??
                                    $batch[
                                        'import_mode'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <span
                                    class="badge <?= $statusClass(
                                        (string) $batch[
                                            'status'
                                        ]
                                    ) ?>"
                                >
                                    <?= $escape(
                                        $batch['status']
                                    ) ?>
                                </span>

                                <?php if (
                                    (int) $batch[
                                        'validate_only'
                                    ] === 1
                                ): ?>
                                    <div
                                        class="small
                                        text-muted"
                                    >
                                        Validation only
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= (int) $batch[
                                    'total_rows'
                                ] ?>
                            </td>

                            <td class="text-success">
                                <?= (int) $batch[
                                    'successful_rows'
                                ] ?>
                            </td>

                            <td class="text-danger">
                                <?= (int) $batch[
                                    'failed_rows'
                                ] ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $batch[
                                        'created_by_user_name'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <?= $escape(
                                    $batch[
                                        'started_at'
                                    ]
                                ) ?>
                            </td>

                            <td>
                                <a
                                    href="/imports/csv/show?id=<?= (int) $batch['id'] ?>"
                                    class="btn btn-sm
                                    btn-outline-primary"
                                >
                                    Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>