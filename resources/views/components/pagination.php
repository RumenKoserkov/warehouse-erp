<?php

use App\Core\Paginator;

/** @var Paginator $paginator */
?>

<?php if ($paginator->total() > 0): ?>
    <div
        class="d-flex flex-column flex-md-row
        justify-content-between align-items-md-center gap-3 mt-4"
    >
        <div class="text-muted">
            Showing
            <strong><?= $paginator->from() ?></strong>
            to
            <strong><?= $paginator->to() ?></strong>
            of
            <strong><?= $paginator->total() ?></strong>
            results
        </div>

        <?php if ($paginator->lastPage() > 1): ?>
            <nav aria-label="Pagination">
                <ul class="pagination mb-0">
                    <li
                        class="page-item
                        <?php if (!$paginator->hasPreviousPage()): ?>
                            disabled
                        <?php endif; ?>"
                    >
                        <a
                            class="page-link"
                            href="<?= htmlspecialchars(
                                $paginator->url(
                                    $paginator->previousPage()
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            Previous
                        </a>
                    </li>

                    <?php foreach ($paginator->pages() as $pageNumber): ?>
                        <li
                            class="page-item
                            <?php if (
                                $pageNumber ===
                                $paginator->currentPage()
                            ): ?>
                                active
                            <?php endif; ?>"
                        >
                            <a
                                class="page-link"
                                href="<?= htmlspecialchars(
                                    $paginator->url($pageNumber),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            >
                                <?= $pageNumber ?>
                            </a>
                        </li>
                    <?php endforeach; ?>

                    <li
                        class="page-item
                        <?php if (!$paginator->hasNextPage()): ?>
                            disabled
                        <?php endif; ?>"
                    >
                        <a
                            class="page-link"
                            href="<?= htmlspecialchars(
                                $paginator->url(
                                    $paginator->nextPage()
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >
                            Next
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>