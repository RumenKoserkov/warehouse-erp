<?php

/** @var array $users */

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Users</h1>

    <a href="/users/create" class="btn btn-primary">
        Create User
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <?php if (empty($users)): ?>
            <p class="text-muted mb-0">
                No users found.
            </p>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($users as $user): ?>
                            <tr>

                                <td>
                                    <?= htmlspecialchars((string)$user['id']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['email']) ?>
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        <?= htmlspecialchars($user['role_name']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ((int)$user['is_active'] === 1): ?>
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
                                    <?php if ($user['last_login_at'] !== null): ?>
                                        <?= htmlspecialchars($user['last_login_at']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            Never
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($user['created_at']) ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">

                                        <a
                                            href="/users/edit?id=<?= htmlspecialchars((string)$user['id']) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            Edit
                                        </a>

                                        <?php if ((int)$user['is_active'] === 1): ?>

                                            <form
                                                action="/users/deactivate"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to deactivate this user?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= htmlspecialchars((string)$user['id']) ?>"
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