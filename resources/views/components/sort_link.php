<?php

declare(strict_types=1);

use App\Core\Sorter;

/** @var Sorter $sorter */
/** @var string $sortKey */
/** @var string $sortLabel */

$sortUrl = $sorter->url($sortKey);
?>

<a
    href="<?= htmlspecialchars(
        $sortUrl,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    class="text-decoration-none text-reset"
>
    <?= htmlspecialchars(
        $sortLabel,
        ENT_QUOTES,
        'UTF-8'
    ) ?>

    <?php if ($sorter->isActive($sortKey)): ?>
        <?php if ($sorter->direction() === 'asc'): ?>
            <span aria-hidden="true">▲</span>
        <?php else: ?>
            <span aria-hidden="true">▼</span>
        <?php endif; ?>
    <?php endif; ?>
</a>