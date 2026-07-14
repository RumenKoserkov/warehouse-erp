<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Suppliers</h1>

    <a href="/suppliers/create" class="btn btn-primary">
        Create Supplier
    </a>
</div>

<form method="GET" action="/suppliers" class="mb-3">
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by name, phone, email, company, EIK..."
            value="<?= htmlspecialchars($search ?? '') ?>"
        >

        <button type="submit" class="btn btn-outline-secondary">
            Search
        </button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($suppliers)): ?>
            <p class="text-muted mb-0">No suppliers found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
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
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$supplier['id']) ?></td>
                                <td><?= htmlspecialchars($supplier['name']) ?></td>
                                <td><?= htmlspecialchars($supplier['company_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($supplier['eik'] ?? '') ?></td>
                                <td><?= htmlspecialchars($supplier['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($supplier['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($supplier['contact_person'] ?? '') ?></td>

                                <td>
                                    <?php if ((int)$supplier['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            href="/suppliers/edit?id=<?= htmlspecialchars((string)$supplier['id']) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <?php if ((int)$supplier['is_active'] === 1): ?>
                                            <form
                                                action="/suppliers/deactivate"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to deactivate this supplier?');"
                                            >
                                                <?= \App\Core\Csrf::field() ?>
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars((string)$supplier['id']) ?>"
                                                >

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Deactivate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        <?php endif; ?>
    </div>
</div>