<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="mb-4">
            <h1 class="h3 mb-1">
                Edit Promotion
            </h1>

            <p class="text-muted mb-0">
                Used:
                <?= (int) $promotion[
                    'used_count'
                ] ?>
                time(s)
            </p>
        </div>

        <form
            method="POST"
            action="/promotions/update"
        >
            <?= \App\Core\Csrf::field() ?>

            <input
                type="hidden"
                name="promotion_id"
                value="<?= (int) $promotion['id'] ?>"
            >

            <?php require __DIR__ .
                '/_form.php'; ?>
        </form>
    </div>
</div>