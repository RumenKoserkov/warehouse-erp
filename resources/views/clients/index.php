<?php

declare(strict_types=1);

use App\Services\AuthService;

$authService = new AuthService();

$canManageClients =
    $authService->hasAnyRole([
        'administrator',
        'manager',
    ]);

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Clients</h1>

    <?php if ($canManageClients): ?>
        <a
            href="/clients/create"
            class="btn btn-primary"
        >
            Create Client
        </a>
    <?php endif; ?>
</div>

<form
    method="GET"
    action="/clients"
    class="mb-3"
>
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by name, phone, email, company, EIK..."
            value="<?= htmlspecialchars(
                (string) ($search ?? ''),
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <button
            type="submit"
            class="btn btn-outline-secondary"
        >
            Search
        </button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($clients)): ?>
            <p class="text-muted mb-0">
                No clients found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table
                    class="
                        table table-striped table-hover
                        align-middle mb-0
                    "
                >
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>EIK</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Contact Person</th>
                            <th>Status</th>

                            <?php if ($canManageClients): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        (string) $client['id'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) $client['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $client['company_name'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $client['eik'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $client['phone'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $client['email'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string) (
                                            $client['contact_person'] ?? ''
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?php if (
                                        (int) $client['is_active'] === 1
                                    ): ?>
                                        <span
                                            class="badge text-bg-success"
                                        >
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span
                                            class="badge text-bg-danger"
                                        >
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($canManageClients): ?>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a
                                                href="/clients/edit?id=<?= htmlspecialchars(
                                                    (string) $client['id'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                class="
                                                    btn btn-sm
                                                    btn-outline-primary
                                                "
                                            >
                                                Edit
                                            </a>

                                            <?php if (
                                                (int) $client[
                                                    'is_active'
                                                ] === 1
                                            ): ?>
                                                <form
                                                    action="/clients/deactivate"
                                                    method="POST"
                                                    onsubmit="
                                                        return confirm(
                                                            'Are you sure you want to deactivate this client?'
                                                        );
                                                    "
                                                >
                                                    <?= \App\Core\Csrf::field() ?>

                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= htmlspecialchars(
                                                            (string) $client[
                                                                'id'
                                                            ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="
                                                            btn btn-sm
                                                            btn-outline-danger
                                                        "
                                                    >
                                                        Deactivate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>