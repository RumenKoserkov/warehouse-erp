<?php

$csvExportPath =
    isset($csvExportPath) &&
    is_string($csvExportPath)
        ? $csvExportPath
        : '';

$csvExportLabel =
    isset($csvExportLabel) &&
    is_string($csvExportLabel)
        ? $csvExportLabel
        : 'Export CSV';

$csvExportClass =
    isset($csvExportClass) &&
    is_string($csvExportClass)
        ? $csvExportClass
        : 'btn btn-outline-success';

$csvExportParameters = [];

foreach ($_GET as $key => $value) {
    if (
        !is_string($key) ||
        $key === 'page' ||
        !is_scalar($value)
    ) {
        continue;
    }

    $value = trim(
        (string) $value
    );

    if ($value === '') {
        continue;
    }

    $csvExportParameters[$key] =
        $value;
}

$csvExportQuery =
    http_build_query(
        $csvExportParameters
    );

$csvExportUrl =
    $csvExportPath;

if ($csvExportQuery !== '') {
    $csvExportUrl .=
        '?' . $csvExportQuery;
}
?>

<a
    href="<?= htmlspecialchars(
        $csvExportUrl,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
    class="<?= htmlspecialchars(
        $csvExportClass,
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
    <?= htmlspecialchars(
        $csvExportLabel,
        ENT_QUOTES,
        'UTF-8'
    ) ?>
</a>

<?php
unset(
    $csvExportPath,
    $csvExportLabel,
    $csvExportClass,
    $csvExportParameters,
    $csvExportQuery,
    $csvExportUrl
);
?>