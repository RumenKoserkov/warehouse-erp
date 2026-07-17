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

$contextJson = '';

if (
    isset($log['context_data']) &&
    is_array($log['context_data']) &&
    !empty($log['context_data'])
) {
    $encodedContext = json_encode(
        $log['context_data'],
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    if (is_string($encodedContext)) {
        $contextJson = $encodedContext;
    }
}

$severityClass = 'text-bg-info';

if ($log['severity'] === 'warning') {
    $severityClass = 'text-bg-warning';
}

if ($log['severity'] === 'error') {
    $severityClass = 'text-bg-danger';
}

if ($log['severity'] === 'critical') {
    $severityClass = 'text-bg-dark';
}
?>

<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Activity Log #<?= (int) $log['id'] ?>
        </h1>

        <span
            class="badge <?= $escape(
                $severityClass
            ) ?>"
        >
            <?= $escape(
                ucfirst(
                    (string) $log[
                        'severity'
                    ]
                )
            ) ?>
        </span>
    </div>

    <a
        href="/audit-logs"
        class="btn btn-outline-secondary"
    >
        Back to Activity Log
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Event</strong>
            </div>

            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">
                        Date
                    </dt>

                    <dd class="col-sm-8">
                        <?= $escape(
                            $formatDateTime(
                                $log['created_at']
                            )
                        ) ?>
                    </dd>

                    <dt class="col-sm-4">
                        Action
                    </dt>

                    <dd class="col-sm-8">
                        <?= $escape(
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    (string) $log[
                                        'action'
                                    ]
                                )
                            )
                        ) ?>
                    </dd>

                    <dt class="col-sm-4">
                        Entity
                    </dt>

                    <dd class="col-sm-8">
                        <?php if (
                            trim(
                                (string) $log[
                                    'entity_type'
                                ]
                            ) !== ''
                        ): ?>
                            <?= $escape(
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        (string) $log[
                                            'entity_type'
                                        ]
                                    )
                                )
                            ) ?>

                            <?php if (
                                $log['entity_id'] !== null
                            ): ?>
                                #<?= (int) $log[
                                    'entity_id'
                                ] ?>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">
                        Description
                    </dt>

                    <dd class="col-sm-8">
                        <?= nl2br(
                            $escape(
                                $log[
                                    'description'
                                ]
                            )
                        ) ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <strong>Actor</strong>
            </div>

            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">
                        User
                    </dt>

                    <dd class="col-sm-8">
                        <?php if (
                            trim(
                                (string) $log[
                                    'user_name'
                                ]
                            ) !== ''
                        ): ?>
                            <?= $escape(
                                $log['user_name']
                            ) ?>
                        <?php else: ?>
                            System
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">
                        Email
                    </dt>

                    <dd class="col-sm-8">
                        <?= trim(
                            (string) $log[
                                'user_email'
                            ]
                        ) !== ''
                            ? $escape(
                                $log[
                                    'user_email'
                                ]
                            )
                            : '—'
                        ?>
                    </dd>

                    <dt class="col-sm-4">
                        User ID
                    </dt>

                    <dd class="col-sm-8">
                        <?= $log['user_id'] !== null
                            ? (int) $log[
                                'user_id'
                            ]
                            : '—'
                        ?>
                    </dd>

                    <dt class="col-sm-4">
                        IP Address
                    </dt>

                    <dd class="col-sm-8">
                        <span
                            class="font-monospace"
                        >
                            <?= $escape(
                                $log[
                                    'ip_address'
                                ]
                            ) ?>
                        </span>
                    </dd>

                    <dt class="col-sm-4">
                        User Agent
                    </dt>

                    <dd class="col-sm-8">
                        <?= $escape(
                            $log['user_agent']
                        ) ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <strong>Request Information</strong>
    </div>

    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-md-3">
                Request ID
            </dt>

            <dd class="col-md-9">
                <?php if (
                    trim(
                        (string) $log[
                            'request_id'
                        ]
                    ) !== ''
                ): ?>
                    <span
                        class="font-monospace"
                    >
                        <?= $escape(
                            $log['request_id']
                        ) ?>
                    </span>
                <?php else: ?>
                    —
                <?php endif; ?>
            </dd>

            <dt class="col-md-3">
                HTTP Method
            </dt>

            <dd class="col-md-9">
                <?= trim(
                    (string) $log[
                        'request_method'
                    ]
                ) !== ''
                    ? $escape(
                        $log[
                            'request_method'
                        ]
                    )
                    : '—'
                ?>
            </dd>

            <dt class="col-md-3">
                Request URI
            </dt>

            <dd class="col-md-9">
                <?php if (
                    trim(
                        (string) $log[
                            'request_uri'
                        ]
                    ) !== ''
                ): ?>
                    <code>
                        <?= $escape(
                            $log[
                                'request_uri'
                            ]
                        ) ?>
                    </code>
                <?php else: ?>
                    —
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <strong>Structured Context</strong>
    </div>

    <div class="card-body">
        <?php if ($contextJson !== ''): ?>
            <pre
                class="bg-light border rounded
                p-3 mb-0"
                style="white-space: pre-wrap;"
            ><code><?= $escape(
                $contextJson
            ) ?></code></pre>
        <?php else: ?>
            <p class="text-muted mb-0">
                This event does not contain
                structured context.
            </p>
        <?php endif; ?>
    </div>
</div>