<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Categories</h1>

    <a href="/categories/create" class="btn btn-primary">
        Create Category
    </a>
</div>

<form method="GET" action="/categories" class="mb-3">
    <div class="input-group">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Search by name or description..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <button type="submit" class="btn btn-outline-secondary">
            Search
        </button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if (empty($categories)): ?>
            <p class="text-muted mb-0">
                No categories found.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$category['id']) ?></td>

                                <td>
                                    <?= htmlspecialchars($category['name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($category['description'] ?? '') ?>
                                </td>

                                <td>
                                    <?php if ((int)$category['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($category['created_at']) ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a
                                            href="/categories/edit?id=<?= htmlspecialchars((string)$category['id']) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <?php if ((int)$category['is_active'] === 1): ?>
                                            <form
                                                action="/categories/deactivate"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to deactivate this category?');"
                                            >
                                                <?= \App\Core\Csrf::field() ?>
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars((string)$category['id']) ?>"
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>