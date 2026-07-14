<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card shadow-sm">
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li>
                                    <?= htmlspecialchars(
                                        (string) $error,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php
                $isEdit = isset($client);
                $action = $isEdit
                    ? '/clients/update'
                    : '/clients/store';
                ?>

                <form
                    action="<?= htmlspecialchars(
                        $action,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    method="POST"
                >
                    <?= \App\Core\Csrf::field() ?>

                    <?php if ($isEdit): ?>
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $client['id'] ?>"
                        >
                    <?php endif; ?>

                    <h2 class="h5 mb-3">
                        General Information
                    </h2>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label
                                for="client_type"
                                class="form-label"
                            >
                                Client Type
                            </label>

                            <select
                                id="client_type"
                                name="client_type"
                                class="form-select"
                            >
                                <option
                                    value="company"
                                    <?php if (
                                        (string) (
                                            $old['client_type']
                                            ?? 'company'
                                        ) === 'company'
                                    ): ?>
                                        selected
                                    <?php endif; ?>
                                >
                                    Company
                                </option>

                                <option
                                    value="individual"
                                    <?php if (
                                        (string) (
                                            $old['client_type']
                                            ?? 'company'
                                        ) === 'individual'
                                    ): ?>
                                        selected
                                    <?php endif; ?>
                                >
                                    Individual
                                </option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label
                                for="name"
                                class="form-label"
                            >
                                Client Display Name *
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                maxlength="255"
                                required
                                value="<?= htmlspecialchars(
                                    (string) ($old['name'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >

                            <div class="form-text">
                                The name shown in lists and searches.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label
                                for="phone"
                                class="form-label"
                            >
                                Phone
                            </label>

                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="form-control"
                                maxlength="50"
                                value="<?= htmlspecialchars(
                                    (string) ($old['phone'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label
                                for="email"
                                class="form-label"
                            >
                                General Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                maxlength="255"
                                value="<?= htmlspecialchars(
                                    (string) ($old['email'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label
                                for="contact_person"
                                class="form-label"
                            >
                                Contact Person
                            </label>

                            <input
                                type="text"
                                id="contact_person"
                                name="contact_person"
                                class="form-control"
                                maxlength="255"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $old['contact_person'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-12">
                            <label
                                for="address"
                                class="form-label"
                            >
                                General Address
                            </label>

                            <textarea
                                id="address"
                                name="address"
                                class="form-control"
                                rows="2"
                            ><?= htmlspecialchars(
                                (string) ($old['address'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h2 class="h5 mb-3">
                        Legal Information
                    </h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label
                                for="company_name"
                                class="form-label"
                            >
                                Company / Legal Name
                            </label>

                            <input
                                type="text"
                                id="company_name"
                                name="company_name"
                                class="form-control"
                                maxlength="255"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $old['company_name'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-3">
                            <label
                                for="eik"
                                class="form-label"
                            >
                                EIK
                            </label>

                            <input
                                type="text"
                                id="eik"
                                name="eik"
                                class="form-control"
                                maxlength="50"
                                value="<?= htmlspecialchars(
                                    (string) ($old['eik'] ?? ''),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-3">
                            <label
                                for="vat_number"
                                class="form-label"
                            >
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
                                    (string) (
                                        $old['vat_number'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>
                    </div>

                    <hr class="my-4">

                    <div
                        class="d-flex justify-content-between
                        align-items-center mb-3"
                    >
                        <h2 class="h5 mb-0">
                            Billing Information
                        </h2>

                        <button
                            type="button"
                            id="copy-general-address"
                            class="btn btn-sm btn-outline-secondary"
                        >
                            Copy General Address
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label
                                for="billing_address"
                                class="form-label"
                            >
                                Billing Address
                            </label>

                            <input
                                type="text"
                                id="billing_address"
                                name="billing_address"
                                class="form-control"
                                maxlength="255"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $old['billing_address'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label
                                for="billing_city"
                                class="form-label"
                            >
                                City
                            </label>

                            <input
                                type="text"
                                id="billing_city"
                                name="billing_city"
                                class="form-control"
                                maxlength="100"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $old['billing_city'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label
                                for="billing_postal_code"
                                class="form-label"
                            >
                                Postal Code
                            </label>

                            <input
                                type="text"
                                id="billing_postal_code"
                                name="billing_postal_code"
                                class="form-control"
                                maxlength="20"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $old['billing_postal_code']
                                        ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label
                                for="billing_country"
                                class="form-label"
                            >
                                Country
                            </label>

                            <input
                                type="text"
                                id="billing_country"
                                name="billing_country"
                                class="form-control"
                                maxlength="100"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $old['billing_country']
                                        ?? 'Bulgaria'
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <label
                                for="billing_email"
                                class="form-label"
                            >
                                Billing Email
                            </label>

                            <input
                                type="email"
                                id="billing_email"
                                name="billing_email"
                                class="form-control"
                                maxlength="255"
                                value="<?= htmlspecialchars(
                                    (string) (
                                        $old['billing_email'] ?? ''
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                        </div>
                    </div>

                    <div class="form-check mt-4 mb-4">
                        <input
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            class="form-check-input"
                            value="1"
                            <?php if (
                                (string) (
                                    $old['is_active'] ?? '1'
                                ) === '1'
                            ): ?>
                                checked
                            <?php endif; ?>
                        >

                        <label
                            for="is_active"
                            class="form-check-label"
                        >
                            Active client
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <?= $isEdit
                                ? 'Update Client'
                                : 'Create Client' ?>
                        </button>

                        <a
                            href="/clients"
                            class="btn btn-outline-secondary"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {
        const copyButton = document.getElementById(
            'copy-general-address'
        );

        const generalAddress =
            document.getElementById('address');

        const billingAddress =
            document.getElementById('billing_address');

        if (
            copyButton === null ||
            generalAddress === null ||
            billingAddress === null
        ) {
            return;
        }

        copyButton.addEventListener(
            'click',
            function () {
                billingAddress.value =
                    generalAddress.value.trim();

                billingAddress.focus();
            }
        );
    }
);
</script>