<div class="row">
    <div class="col-md-8">
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

                <?php
                    $isEdit = isset($client);
                    $action = $isEdit ? '/clients/update' : '/clients/store';
                ?>

                <form action="<?= htmlspecialchars($action) ?>" method="POST">
                    <?= \App\Core\Csrf::field() ?>
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$client['id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Client Name *</label>
                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea
                            name="address"
                            class="form-control"
                            rows="3"
                        ><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input
                            type="text"
                            name="company_name"
                            class="form-control"
                            value="<?= htmlspecialchars($old['company_name'] ?? '') ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">EIK</label>
                        <input
                            type="text"
                            name="eik"
                            class="form-control"
                            value="<?= htmlspecialchars($old['eik'] ?? '') ?>"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input
                            type="text"
                            name="contact_person"
                            class="form-control"
                            value="<?= htmlspecialchars($old['contact_person'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-check mb-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            class="form-check-input"
                            value="1"
                            <?php if ((string)($old['is_active'] ?? '1') === '1'): ?>
                                checked
                            <?php endif; ?>
                        >

                        <label class="form-check-label">
                            Active client
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= $isEdit ? 'Update Client' : 'Create Client' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>