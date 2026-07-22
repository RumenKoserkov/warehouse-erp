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

$statusClass = match (
    (string) $batch['status']
) {
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
?>

<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            CSV Import
            #<?= (int) $batch['id'] ?>
        </h1>

        <p class="text-muted mb-0">
            <?= $escape(
                $batch[
                    'original_filename'
                ]
            ) ?>
        </p>
    </div>

    <a
        href="/imports/csv"
        class="btn btn-outline-secondary"
    >
        Back to Imports
    </a>
</div>

<?php if (
    trim(
        (string) (
            $batch[
                'error_message'
            ] ?? ''
        )
    ) !== ''
): ?>
    <div class="alert alert-danger">
        <?= $escape(
            $batch['error_message']
        ) ?>
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">
                    Type
                </div>

                <div class="fw-semibold">
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
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Mode
                </div>

                <div class="fw-semibold">
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
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Status
                </div>

                <div>
                    <span
                        class="badge <?= $statusClass ?>"
                    >
                        <?= $escape(
                            $batch['status']
                        ) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Delimiter
                </div>

                <div class="fw-semibold">
                    <?= $escape(
                        $batch[
                            'delimiter_name'
                        ] ?? '—'
                    ) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Total Rows
                </div>

                <div class="fs-4 fw-bold">
                    <?= (int) $batch[
                        'total_rows'
                    ] ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Successful Rows
                </div>

                <div
                    class="fs-4 fw-bold
                    text-success"
                >
                    <?= (int) $batch[
                        'successful_rows'
                    ] ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Failed Rows
                </div>

                <div
                    class="fs-4 fw-bold
                    text-danger"
                >
                    <?= (int) $batch[
                        'failed_rows'
                    ] ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">
                    Validation Only
                </div>

                <div class="fw-semibold">
                    <?= (int) $batch[
                        'validate_only'
                    ] === 1
                        ? 'Yes'
                        : 'No' ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">
                    User
                </div>

                <div class="fw-semibold">
                    <?= $escape(
                        $batch[
                            'created_by_user_name'
                        ]
                    ) ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">
                    Started
                </div>

                <div>
                    <?= $escape(
                        $batch['started_at']
                    ) ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="text-muted small">
                    Completed
                </div>

                <div>
                    <?= $escape(
                        $batch[
                            'completed_at'
                        ] ?? '—'
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Row Errors
        </h2>
    </div>

    <?php if (empty($errors)): ?>
        <div class="card-body">
            <div class="alert alert-success mb-0">
                No row errors were recorded.
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
                        <th>CSV Row</th>
                        <th>Column</th>
                        <th>Error</th>
                        <th>Row Data</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $errors as $error
                    ): ?>
                        <?php
                        $rowData = json_decode(
                            (string) (
                                $error[
                                    'row_data'
                                ] ?? ''
                            ),
                            true
                        );
                        ?>

                        <tr>
                            <td class="fw-semibold">
                                <?= (int) $error[
                                    'row_number'
                                ] ?>
                            </td>

                            <td>
                                <?= trim(
                                    (string) (
                                        $error[
                                            'column_name'
                                        ] ?? ''
                                    )
                                ) !== ''
                                    ? $escape(
                                        $error[
                                            'column_name'
                                        ]
                                    )
                                    : '—' ?>
                            </td>

                            <td class="text-danger">
                                <?= $escape(
                                    $error[
                                        'error_message'
                                    ]
                                ) ?>
                            </td>

                            <td style="min-width: 350px;">
                                <pre
                                    class="small mb-0
                                    text-wrap"
                                ><?= $escape(
                                    json_encode(
                                        is_array(
                                            $rowData
                                        )
                                            ? $rowData
                                            : [],
                                        JSON_PRETTY_PRINT |
                                        JSON_UNESCAPED_UNICODE |
                                        JSON_UNESCAPED_SLASHES
                                    )
                                ) ?></pre>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>