<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Settings</h1>

    <a href="/dashboard" class="btn btn-outline-secondary">
        Back to Dashboard
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header">
        Company Settings
    </div>

    <div class="card-body">
        <form action="/settings/update" method="POST">
            <?= \App\Core\Csrf::field() ?>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name *</label>

                    <input 
                        type="text" 
                        name="company_name" 
                        class="form-control"
                        value="<?= htmlspecialchars($settings['company_name']) ?>"
                        required
                    >
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Currency *</label>

                    <select name="currency" class="form-select" required>
                        <?php foreach ($currencies as $currency): ?>
                            <option 
                                value="<?= htmlspecialchars($currency) ?>"
                                <?php if ($settings['currency'] === $currency): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars($currency) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">VAT Rate (%) *</label>

                    <input 
                        type="number" 
                        step="0.01" 
                        name="vat_rate" 
                        class="form-control"
                        value="<?= htmlspecialchars($settings['vat_rate']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Invoice Prefix *</label>

                    <input 
                        type="text" 
                        name="invoice_prefix" 
                        class="form-control"
                        value="<?= htmlspecialchars($settings['invoice_prefix']) ?>"
                        required
                    >

                    <small class="text-muted">
                        Example: INV, SALE, DOC
                    </small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Date Format *</label>

                    <select name="date_format" class="form-select" required>
                        <?php foreach ($dateFormats as $format => $example): ?>
                            <option 
                                value="<?= htmlspecialchars($format) ?>"
                                <?php if ($settings['date_format'] === $format): ?>
                                    selected
                                <?php endif; ?>
                            >
                                <?= htmlspecialchars($format . ' — ' . $example) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-success">
                Save Settings
            </button>
        </form>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        Current Settings Preview
    </div>

    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tr>
                <th>Company Name</th>
                <td><?= htmlspecialchars($settings['company_name']) ?></td>
            </tr>

            <tr>
                <th>Currency</th>
                <td><?= htmlspecialchars($settings['currency']) ?></td>
            </tr>

            <tr>
                <th>VAT Rate</th>
                <td><?= htmlspecialchars($settings['vat_rate']) ?>%</td>
            </tr>

            <tr>
                <th>Invoice Prefix</th>
                <td><?= htmlspecialchars($settings['invoice_prefix']) ?></td>
            </tr>

            <tr>
                <th>Date Format</th>
                <td><?= htmlspecialchars($settings['date_format']) ?></td>
            </tr>
        </table>
    </div>
</div>