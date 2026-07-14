<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Clients</h1>

    <a href="/clients/create" class="btn btn-primary">
        Create Client
    </a>
</div>

<form method="GET" action="/clients" class="mb-3">
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
        <?php if (empty($clients)): ?>
            <p class="text-muted mb-0">No clients found.</p>
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
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$client['id']) ?></td>
                                <td><?= htmlspecialchars($client['name']) ?></td>
                                <td><?= htmlspecialchars($client['company_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($client['eik'] ?? '') ?></td>
                                <td><?= htmlspecialchars($client['phone'] ?? '') ?></td>
                                <td><?= htmlspecialchars($client['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($client['contact_person'] ?? '') ?></td>

                                <td>
                                    <?php if ((int)$client['is_active'] === 1): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <a 
                                            href="/clients/edit?id=<?= htmlspecialchars((string)$client['id']) ?>" 
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <?php if ((int)$client['is_active'] === 1): ?>
                                            <form 
                                                action="/clients/deactivate" 
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to deactivate this client?');"
                                            >
                                                <?= \App\Core\Csrf::field() ?>
                                                <input 
                                                    type="hidden" 
                                                    name="id" 
                                                    value="<?= htmlspecialchars((string)$client['id']) ?>"
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