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

                <?php
                    $isEdit = false;
                    $action = '/categories/store';
                    $buttonText = 'Create Category';

                    if (isset($category)) {
                        $isEdit = true;
                        $action = '/categories/update';
                        $buttonText = 'Update Category';
                    }
                ?>

                <form action="<?= htmlspecialchars($action) ?>" method="POST">
                    <?= \App\Core\Csrf::field() ?>

                    <?php if ($isEdit): ?>
                        <input
                            type="hidden"
                            name="id"
                            value="<?= htmlspecialchars((string)$category['id']) ?>"
                        >
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">
                            Category Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            value="<?= htmlspecialchars($old['name']) ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"
                        ><?= htmlspecialchars($old['description']) ?></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input
                            type="checkbox"
                            name="is_active"
                            class="form-check-input"
                            value="1"
                            <?php if ((string)$old['is_active'] === '1'): ?>
                                checked
                            <?php endif; ?>
                        >

                        <label class="form-check-label">
                            Active category
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <?= htmlspecialchars($buttonText) ?>
                    </button>

                </form>

            </div>
        </div>
    </div>
</div>