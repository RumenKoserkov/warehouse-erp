<?php

/** @var array $user */
/** @var array $roles */
/** @var array $errors */
/** @var array $old */

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Edit User</h1>

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

                <form action="/users/update" method="POST">

                    <input
                        type="hidden"
                        name="id"
                        value="<?= htmlspecialchars((string)$user['id']) ?>"
                    >

                    <div class="mb-3">
                        <label for="name" class="form-label">
                            Name
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="name"
                            name="name"
                            value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            Email
                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="role_id" class="form-label">
                            Role
                        </label>

                        <select
                            name="role_id"
                            id="role_id"
                            class="form-select"
                            required
                        >
                            <option value="">
                                Select role
                            </option>

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

                    <hr>

                    <p class="text-muted">
                        Leave password fields empty if you do not want to change the password.
                    </p>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            New Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                        >
                    </div>

                    <div class="mb-3">
                        <label
                            for="password_confirmation"
                            class="form-label"
                        >
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password_confirmation"
                            name="password_confirmation"
                        >
                    </div>

                    <div class="form-check mb-3">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="is_active"
                            name="is_active"
                            value="1"
                            <?php if (($old['is_active'] ?? '1') === '1'): ?>
                                checked
                            <?php endif; ?>
                        >

                        <label
                            class="form-check-label"
                            for="is_active"
                        >
                            Active user
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Update User
                    </button>

                </form>

            </div>
        </div>
    </div>
</div>