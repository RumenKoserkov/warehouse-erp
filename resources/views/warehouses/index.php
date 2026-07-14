<?php

/** @var array $warehouses */
/** @var string $search */
/** @var bool $canManage */

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Warehouses</h1>

    <?php if ($canManage): ?>
        <a href="/warehouses/create" class="btn btn-primary">
            Create Warehouse
        </a>
    <?php endif; ?>
</div>

<form method="GET" action="/warehouses" class="mb-3">
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by name, code, address..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit" class="btn btn-outline-secondary">
            Search
        </button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($warehouses)): ?>
            <p class="text-muted mb-0">No warehouses found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created At</th>

                            <?php if ($canManage): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $warehouse['id']) ?></td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= htmlspecialchars($warehouse['code']) ?>
                                    </span>
                                </td>

                                <td><?= htmlspecialchars($warehouse['name']) ?></td>
                                <td><?= htmlspecialchars($warehouse['address'] ?? '') ?></td>
                                <td><?= htmlspecialchars($warehouse['description'] ?? '') ?></td>

                                <td>
                                    <?php if ((int) $warehouse['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= htmlspecialchars($warehouse['created_at']) ?></td>

                                <?php if ($canManage): ?>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a
                                                href="/warehouses/edit?id=<?= htmlspecialchars((string) $warehouse['id']) ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Edit
                                            </a>

                                            <?php if ((int) $warehouse['is_active'] === 1): ?>
                                                <form
                                                    action="/warehouses/deactivate"
                                                    method="POST"
                                                    onsubmit="return confirm('Are you sure you want to deactivate this warehouse?');"
                                                >
                                                    <?= \App\Core\Csrf::field() ?>
                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= htmlspecialchars((string) $warehouse['id']) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
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