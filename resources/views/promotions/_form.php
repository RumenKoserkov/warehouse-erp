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

<div class="card shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label
                    for="name"
                    class="form-label"
                >
                    Promotion Name
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
            </div>

            <div class="col-md-4">
                <label
                    for="code"
                    class="form-label"
                >
                    Promotion Code
                </label>

                <input
                    type="text"
                    id="code"
                    name="code"
                    class="form-control text-uppercase"
                    maxlength="100"
                    placeholder="Optional"
                    value="<?= htmlspecialchars(
                        (string) ($old['code'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <div class="form-text">
                    Leave empty for an automatic
                    promotion.
                </div>
            </div>

            <div class="col-md-4">
                <label
                    for="discount_type"
                    class="form-label"
                >
                    Discount Type
                </label>

                <select
                    id="discount_type"
                    name="discount_type"
                    class="form-select"
                    required
                >
                    <?php foreach (
                        $discountTypes as
                        $value => $label
                    ): ?>
                        <option
                            value="<?= htmlspecialchars(
                                $value,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                            <?= (
                                $old[
                                    'discount_type'
                                ] ?? ''
                            ) === $value
                                ? 'selected'
                                : '' ?>
                        >
                            <?= htmlspecialchars(
                                $label,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label
                    for="discount_value"
                    class="form-label"
                >
                    Discount Value
                </label>

                <input
                    type="number"
                    id="discount_value"
                    name="discount_value"
                    class="form-control"
                    min="0.0001"
                    step="0.0001"
                    required
                    value="<?= htmlspecialchars(
                        (string) (
                            $old[
                                'discount_value'
                            ] ?? ''
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-md-4">
                <label
                    for="maximum_discount_amount"
                    class="form-label"
                >
                    Maximum Discount
                </label>

                <input
                    type="number"
                    id="maximum_discount_amount"
                    name="maximum_discount_amount"
                    class="form-control"
                    min="0.01"
                    step="0.01"
                    placeholder="Optional"
                    value="<?= htmlspecialchars(
                        (string) (
                            $old[
                                'maximum_discount_amount'
                            ] ?? ''
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-md-4">
                <label
                    for="minimum_order_amount"
                    class="form-label"
                >
                    Minimum Order Amount
                </label>

                <input
                    type="number"
                    id="minimum_order_amount"
                    name="minimum_order_amount"
                    class="form-control"
                    min="0"
                    step="0.01"
                    required
                    value="<?= htmlspecialchars(
                        (string) (
                            $old[
                                'minimum_order_amount'
                            ] ?? '0'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-md-4">
                <label
                    for="max_uses"
                    class="form-label"
                >
                    Maximum Uses
                </label>

                <input
                    type="number"
                    id="max_uses"
                    name="max_uses"
                    class="form-control"
                    min="1"
                    step="1"
                    placeholder="Unlimited"
                    value="<?= htmlspecialchars(
                        (string) (
                            $old['max_uses'] ?? ''
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-md-4">
                <label
                    for="starts_on"
                    class="form-label"
                >
                    Starts On
                </label>

                <input
                    type="date"
                    id="starts_on"
                    name="starts_on"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars(
                        (string) (
                            $old['starts_on'] ?? ''
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-md-4">
                <label
                    for="ends_on"
                    class="form-label"
                >
                    Ends On
                </label>

                <input
                    type="date"
                    id="ends_on"
                    name="ends_on"
                    class="form-control"
                    value="<?= htmlspecialchars(
                        (string) (
                            $old['ends_on'] ?? ''
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >
            </div>

            <div class="col-12">
                <label
                    for="notes"
                    class="form-label"
                >
                    Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    class="form-control"
                    rows="3"
                    maxlength="2000"
                ><?= htmlspecialchars(
                    (string) (
                        $old['notes'] ?? ''
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        class="form-check-input"
                        value="1"
                        <?= (int) (
                            $old['is_active'] ?? 0
                        ) === 1
                            ? 'checked'
                            : '' ?>
                    >

                    <label
                        for="is_active"
                        class="form-check-label"
                    >
                        Active
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <button
            type="submit"
            class="btn btn-primary"
        >
            Save Promotion
        </button>

        <a
            href="/promotions"
            class="btn btn-outline-secondary"
        >
            Cancel
        </a>
    </div>
</div>