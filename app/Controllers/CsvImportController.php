<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\CsvResponse;
use App\Core\Flash;
use App\Models\CsvImport;
use App\Services\AuthService;
use App\Services\CsvImportService;

class CsvImportController extends Controller
{
    private CsvImport $importModel;

    private CsvImportService $importService;

    private AuthService $authService;

    public function __construct()
    {
        $this->importModel =
            new CsvImport();

        $this->importService =
            new CsvImportService();

        $this->authService =
            new AuthService();
    }

    public function index(): void
    {
        $user = $this->currentUser();

        $this->view(
            'imports/csv/index',
            [
                'title' =>
                    'CSV Import',

                'importTypes' =>
                    $this->importService
                        ->importTypes(),

                'importModes' =>
                    $this->importService
                        ->importModes(),

                'batches' =>
                    $this->importModel
                        ->recentByCompany(
                            (int) $user[
                                'company_id'
                            ]
                        ),
            ]
        );
    }

    public function process(): void
    {
        $user = $this->currentUser();

        $result =
            $this->importService
                ->process(
                    isset(
                        $_FILES['csv_file']
                    ) &&
                    is_array(
                        $_FILES['csv_file']
                    )
                        ? $_FILES['csv_file']
                        : [],

                    $this->postString(
                        'import_type'
                    ),

                    $this->postString(
                        'import_mode'
                    ),

                    isset(
                        $_POST['validate_only']
                    ),

                    (int) $user[
                        'company_id'
                    ],

                    (int) $user['id']
                );

        if (
            $result['batch_id'] !== null
        ) {
            if ($result['success']) {
                Flash::success(
                    'CSV process finished. Successful rows: ' .
                    (int) $result[
                        'successful_rows'
                    ] .
                    ', failed rows: ' .
                    (int) $result[
                        'failed_rows'
                    ] .
                    '.'
                );
            } else {
                Flash::danger(
                    (string) $result['error']
                );
            }

            $this->redirect(
                '/imports/csv/show?id=' .
                (int) $result[
                    'batch_id'
                ]
            );

            return;
        }

        Flash::danger(
            (string) $result['error']
        );

        $this->redirect(
            '/imports/csv'
        );
    }

    public function show(): void
    {
        $user = $this->currentUser();

        $batchId =
            $this->queryId('id');

        $companyId =
            (int) $user['company_id'];

        $batch =
            $this->importModel
                ->findBatch(
                    $batchId,
                    $companyId
                );

        if ($batch === null) {
            $this->abort(404);
            return;
        }

        $this->view(
            'imports/csv/show',
            [
                'title' =>
                    'CSV Import #' .
                    $batchId,

                'batch' => $batch,

                'errors' =>
                    $this->importModel
                        ->errorsByBatch(
                            $batchId,
                            $companyId
                        ),

                'importTypes' =>
                    $this->importService
                        ->importTypes(),

                'importModes' =>
                    $this->importService
                        ->importModes(),
            ]
        );
    }

    public function template(): void
    {
        $this->currentUser();

        $type =
            $this->queryString('type');

        try {
            $template =
                $this->importService
                    ->template($type);

            CsvResponse::download(
                $type .
                '_import_template.csv',

                $template['headers'],
                $template['rows']
            );
        } catch (\Throwable) {
            $this->abort(404);
        }
    }

    private function currentUser(): array
    {
        $user =
            $this->authService->user();

        if ($user === null) {
            $this->redirect('/login');
            exit;
        }

        return $user;
    }

    private function queryId(
        string $field
    ): int {
        $value = filter_var(
            $_GET[$field] ?? null,
            FILTER_VALIDATE_INT
        );

        return $value !== false &&
            $value > 0
                ? $value
                : 0;
    }

    private function queryString(
        string $field
    ): string {
        return isset($_GET[$field]) &&
            is_scalar($_GET[$field])
                ? trim(
                    (string) $_GET[$field]
                )
                : '';
    }

    private function postString(
        string $field
    ): string {
        return isset($_POST[$field]) &&
            is_scalar($_POST[$field])
                ? trim(
                    (string) $_POST[$field]
                )
                : '';
    }
}