<div
    class="d-flex justify-content-between
    align-items-start mb-4"
>
    <div>
        <h1 class="h3 mb-1">
            Promotions
        </h1>

        <p class="text-muted mb-0">
            Discounts, campaigns and
            promotion codes.
        </p>
    </div>

    <a
        href="/promotions/create"
        class="btn btn-primary"
    >
        New Promotion
    </a>
</div>

<form
    method="GET"
    action="/promotions"
    class="card shadow-sm mb-4"
>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label
                    for="search"
                    class="form-label"
                >
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    placeholder="Promotion name or code..."
                    value="<?= htmlspecialchars(
                        (string) $filters[
                            'search'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-md-4">
                <label
                    for="active"
                    class="form-label"
                >
                    Status
                </label>

                <select
                    id="active"
                    name="active"
                    class="form-select"
                >
                    <option value="">
                        All
                    </option>

                    <option
                        value="1"
                        <?= $filters['active'] ===
                            '1'
                                ? 'selected'
                                : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="0"
                        <?= $filters['active'] ===
                            '0'
                                ? 'selected'
                                : '' ?>
                    >
                        Inactive
                    </option>
                </select>
            </div>

            <div class="col-12">
                <button
                    class="btn btn-outline-primary"
                >
                    Filter
                </button>

                <a
                    href="/promotions"
                    class="btn btn-outline-secondary"
                >
                    Clear
                </a>
            </div>
        </div>
    </div>
</form>

<?php if (empty($promotions)): ?>
    <div class="alert alert-info">
        No promotions found.
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table
                class="table table-hover
                align-middle mb-0"
            >
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Period</th>
                        <th>Minimum</th>
                        <th>Usage</th>
                        <th>Total Discount</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach (
                        $promotions as $promotion
                    ): ?>
                        <?php
                        $today = date('Y-m-d');

                        $periodStatus = 'active';

                        if (
                            (string) $promotion[
                                'starts_on'
                            ] > $today
                        ) {
                            $periodStatus =
                                'scheduled';
                        }

                        if (
                            $promotion['ends_on'] !==
                                null &&
                            (string) $promotion[
                                'ends_on'
                            ] < $today
                        ) {
                            $periodStatus =
                                'expired';
                        }
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    <?= htmlspecialchars(
                                        (string) $promotion[
                                            'name'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <div class="small text-muted">
                                    <?= htmlspecialchars(
                                        $discountTypes[
                                            $promotion[
                                                'discount_type'
                                            ]
                                        ] ??
                                        (string) $promotion[
                                            'discount_type'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </div>
                            </td>

                            <td class="font-monospace">
                                <?= $promotion['code'] !==
                                    null
                                        ? htmlspecialchars(
                                            (string) $promotion[
                                                'code'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        )
                                        : 'Automatic' ?>
                            </td>

                            <td>
                                <?php if (
                                    $promotion[
                                        'discount_type'
                                    ] === 'percentage'
                                ): ?>
                                    <?= number_format(
                                        (float) $promotion[
                                            'discount_value'
                                        ],
                                        2
                                    ) ?>%
                                <?php else: ?>
                                    <?= number_format(
                                        (float) $promotion[
                                            'discount_value'
                                        ],
                                        2
                                    ) ?>
                                <?php endif; ?>

                                <?php if (
                                    $promotion[
                                        'maximum_discount_amount'
                                    ] !== null
                                ): ?>
                                    <div class="small text-muted">
                                        Max:
                                        <?= number_format(
                                            (float) $promotion[
                                                'maximum_discount_amount'
                                            ],
                                            2
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    (string) $promotion[
                                        'starts_on'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                <div class="small text-muted">
                                    to
                                    <?= $promotion['ends_on'] !==
                                        null
                                            ? htmlspecialchars(
                                                (string) $promotion[
                                                    'ends_on'
                                                ],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                            : 'No end date' ?>
                                </div>

                                <span
                                    class="badge text-bg-secondary"
                                >
                                    <?= ucfirst(
                                        $periodStatus
                                    ) ?>
                                </span>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $promotion[
                                        'minimum_order_amount'
                                    ],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $promotion[
                                    'used_count'
                                ] ?>

                                <?php if (
                                    $promotion[
                                        'max_uses'
                                    ] !== null
                                ): ?>
                                    /
                                    <?= (int) $promotion[
                                        'max_uses'
                                    ] ?>
                                <?php else: ?>
                                    / Unlimited
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= number_format(
                                    (float) $promotion[
                                        'total_discount'
                                    ],
                                    2
                                ) ?>
                            </td>

                            <td>
                                <span
                                    class="badge <?= (int) $promotion[
                                        'is_active'
                                    ] === 1
                                        ? 'text-bg-success'
                                        : 'text-bg-secondary' ?>"
                                >
                                    <?= (int) $promotion[
                                        'is_active'
                                    ] === 1
                                        ? 'Active'
                                        : 'Inactive' ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-2">
                                    <a
                                        href="/promotions/edit?id=<?= (int) $promotion['id'] ?>"
                                        class="btn btn-sm
                                        btn-outline-primary"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="/promotions/toggle"
                                    >
                                        <?= \App\Core\Csrf::field() ?>

                                        <input
                                            type="hidden"
                                            name="promotion_id"
                                            value="<?= (int) $promotion['id'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="active"
                                            value="<?= (int) $promotion[
                                                'is_active'
                                            ] === 1
                                                ? '0'
                                                : '1' ?>"
                                        >

                                        <button
                                            class="btn btn-sm
                                            btn-outline-secondary"
                                        >
                                            <?= (int) $promotion[
                                                'is_active'
                                            ] === 1
                                                ? 'Deactivate'
                                                : 'Activate' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>