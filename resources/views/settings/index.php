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
                        required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Currency *</label>

                    <select name="currency" class="form-select" required>
                        <?php foreach ($currencies as $currency): ?>
                            <option
                                value="<?= htmlspecialchars($currency) ?>"
                                <?php if ($settings['currency'] === $currency): ?>
                                selected
                                <?php endif; ?>>
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
                        required>
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
                        required>

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
                                <?php endif; ?>>
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
<div
    class="card shadow-sm mt-4"
    id="company-billing">
    <div class="card-header">
        <h2 class="h5 mb-0">
            Company Billing Information
        </h2>
    </div>

    <div class="card-body">
        <p class="text-muted">
            These details will be used on invoices,
            credit notes and printed documents.
        </p>

        <form
            method="POST"
            action="/settings/company/update">
            <?= \App\Core\Csrf::field() ?>

            <h3 class="h6 mb-3">
                Legal Information
            </h3>

            <div class="row g-3">
                <div class="col-md-8">
                    <label
                        for="legal_name"
                        class="form-label">
                        Legal Company Name *
                    </label>

                    <input
                        type="text"
                        id="legal_name"
                        name="legal_name"
                        class="form-control"
                        maxlength="255"
                        required
                        value="<?= htmlspecialchars(
                                    (string) ($company['legal_name'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-4">
                    <label
                        for="eik"
                        class="form-label">
                        EIK / Company Number *
                    </label>

                    <input
                        type="text"
                        id="eik"
                        name="eik"
                        class="form-control"
                        maxlength="20"
                        required
                        value="<?= htmlspecialchars(
                                    (string) ($company['eik'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-4">
                    <label
                        for="vat_number"
                        class="form-label">
                        VAT Number
                    </label>

                    <input
                        type="text"
                        id="vat_number"
                        name="vat_number"
                        class="form-control"
                        maxlength="30"
                        placeholder="BG123456789"
                        value="<?= htmlspecialchars(
                                    (string) ($company['vat_number'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-8">
                    <label
                        for="manager_name"
                        class="form-label">
                        Manager / Representative
                    </label>

                    <input
                        type="text"
                        id="manager_name"
                        name="manager_name"
                        class="form-control"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                                    (string) ($company['manager_name'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>
            </div>

            <hr class="my-4">

            <h3 class="h6 mb-3">
                Address and Contacts
            </h3>

            <div class="row g-3">
                <div class="col-md-8">
                    <label
                        for="billing_address"
                        class="form-label">
                        Address *
                    </label>

                    <input
                        type="text"
                        id="billing_address"
                        name="billing_address"
                        class="form-control"
                        maxlength="255"
                        required
                        value="<?= htmlspecialchars(
                                    (string) ($company['billing_address'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-4">
                    <label
                        for="billing_city"
                        class="form-label">
                        City *
                    </label>

                    <input
                        type="text"
                        id="billing_city"
                        name="billing_city"
                        class="form-control"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars(
                                    (string) ($company['billing_city'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-4">
                    <label
                        for="billing_postal_code"
                        class="form-label">
                        Postal Code
                    </label>

                    <input
                        type="text"
                        id="billing_postal_code"
                        name="billing_postal_code"
                        class="form-control"
                        maxlength="20"
                        value="<?= htmlspecialchars(
                                    (string) ($company['billing_postal_code'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-4">
                    <label
                        for="billing_country"
                        class="form-label">
                        Country *
                    </label>

                    <input
                        type="text"
                        id="billing_country"
                        name="billing_country"
                        class="form-control"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars(
                                    (string) ($company['billing_country'] ?? 'Bulgaria'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-4">
                    <label
                        for="billing_phone"
                        class="form-label">
                        Phone
                    </label>

                    <input
                        type="text"
                        id="billing_phone"
                        name="billing_phone"
                        class="form-control"
                        maxlength="50"
                        value="<?= htmlspecialchars(
                                    (string) ($company['billing_phone'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-6">
                    <label
                        for="billing_email"
                        class="form-label">
                        Billing Email *
                    </label>

                    <input
                        type="email"
                        id="billing_email"
                        name="billing_email"
                        class="form-control"
                        maxlength="255"
                        required
                        value="<?= htmlspecialchars(
                                    (string) ($company['billing_email'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-6">
                    <label
                        for="billing_website"
                        class="form-label">
                        Website
                    </label>

                    <input
                        type="url"
                        id="billing_website"
                        name="billing_website"
                        class="form-control"
                        maxlength="255"
                        placeholder="https://example.com"
                        value="<?= htmlspecialchars(
                                    (string) ($company['billing_website'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>
            </div>

            <hr class="my-4">

            <h3 class="h6 mb-3">
                Bank Information
            </h3>

            <div class="row g-3">
                <div class="col-md-6">
                    <label
                        for="bank_name"
                        class="form-label">
                        Bank Name
                    </label>

                    <input
                        type="text"
                        id="bank_name"
                        name="bank_name"
                        class="form-control"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                                    (string) ($company['bank_name'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-4">
                    <label
                        for="iban"
                        class="form-label">
                        IBAN
                    </label>

                    <input
                        type="text"
                        id="iban"
                        name="iban"
                        class="form-control"
                        maxlength="50"
                        value="<?= htmlspecialchars(
                                    (string) ($company['iban'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>

                <div class="col-md-2">
                    <label
                        for="bic"
                        class="form-label">
                        BIC
                    </label>

                    <input
                        type="text"
                        id="bic"
                        name="bic"
                        class="form-control"
                        maxlength="20"
                        value="<?= htmlspecialchars(
                                    (string) ($company['bic'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">
                </div>
            </div>

            <div class="mt-4">
                <button
                    type="submit"
                    class="btn btn-primary">
                    Save Billing Information
                </button>
            </div>
        </form>
    </div>
</div>