<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="mb-4">
            <h1 class="h3 mb-1">
                New Promotion
            </h1>

            <p class="text-muted mb-0">
                Create an automatic promotion
                or promotion code.
            </p>
        </div>

        <form
            method="POST"
            action="/promotions/store"
        >
            <?= \App\Core\Csrf::field() ?>

            <?php require __DIR__ .
                '/_form.php'; ?>
        </form>
    </div>
</div>