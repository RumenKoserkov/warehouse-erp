<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Create User</h1>

    <a href="/users" class="btn btn-outline-secondary">
        Back to Users
    </a>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="/users/store" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control"
                            value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">Role</label>

                        <select
                            name="role_id"
                            id="role_id"
                            class="form-select"
                            required
                        >
                            <option value="">Select role</option>

                            <?php foreach ($roles as $role): ?>
                                <option
                                    value="<?= htmlspecialchars((string)$role['id']) ?>"
                                    <?php if (($old['role_id'] ?? '') === (string)$role['id']): ?>
                                        selected
                                    <?php endif; ?>
                                >
                                    <?= htmlspecialchars($role['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="form-check mb-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            id="is_active"
                            class="form-check-input"
                            value="1"
                            <?php if (($old['is_active'] ?? '1') === '1'): ?>
                                checked
                            <?php endif; ?>
                        >

                        <label for="is_active" class="form-check-label">
                            Active user
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Create User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>