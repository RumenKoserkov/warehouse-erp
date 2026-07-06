<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Warehouse;
use App\Services\AuthService;

class WarehouseController extends Controller
{
    private Warehouse $warehouseModel;
    private AuthService $authService;

    public function __construct()
    {
        $this->warehouseModel = new Warehouse();
        $this->authService = new AuthService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        $search = '';

        if (isset($_GET['search'])) {
            $search = trim((string) $_GET['search']);
        }

        $warehouses = $this->warehouseModel->allByCompany(
            (int) $currentUser['company_id'],
            $search
        );

        $this->view('warehouses/index', [
            'title' => 'Warehouses',
            'warehouses' => $warehouses,
            'search' => $search,
            'canManage' => $this->authService->hasAnyRole([
                'administrator',
                'manager',
            ]),
        ]);
    }

    public function create(): void
    {
        $this->view('warehouses/create', [
            'title' => 'Create Warehouse',
            'errors' => [],
            'old' => $this->emptyOldData(),
        ]);
    }

    public function store(): void
    {
        $currentUser = $this->authService->user();

        $data = $this->getFormData();

        $validator = new Validator($_POST);

        $validator
            ->required('name', 'Warehouse name is required.')
            ->max('name', 255, 'Warehouse name must be maximum 255 characters.')
            ->required('code', 'Warehouse code is required.')
            ->max('code', 50, 'Warehouse code must be maximum 50 characters.');

        $errors = $validator->all();

        if ($this->warehouseModel->codeExistsInCompany(
            $data['code'],
            (int) $currentUser['company_id']
        )) {
            $errors[] = 'Warehouse with this code already exists.';
        }

        if (!empty($errors)) {
            $this->view('warehouses/create', [
                'title' => 'Create Warehouse',
                'errors' => $errors,
                'old' => $data,
            ]);

            return;
        }

        $data['company_id'] = (int) $currentUser['company_id'];

        $this->warehouseModel->create($data);

        Flash::success('Warehouse created successfully.');

        $this->redirect('/warehouses');
    }

    public function edit(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
        }

        if ($id <= 0) {
            $this->abort(404);
        }

        $warehouse = $this->warehouseModel->findByIdAndCompany(
            $id,
            (int) $currentUser['company_id']
        );

        if ($warehouse === null) {
            $this->abort(404);
        }

        $this->view('warehouses/edit', [
            'title' => 'Edit Warehouse',
            'warehouse' => $warehouse,
            'errors' => [],
            'old' => $warehouse,
        ]);
    }

    public function update(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        if ($id <= 0) {
            $this->abort(404);
        }

        $warehouse = $this->warehouseModel->findByIdAndCompany(
            $id,
            (int) $currentUser['company_id']
        );

        if ($warehouse === null) {
            $this->abort(404);
        }

        $data = $this->getFormData();

        $validator = new Validator($_POST);

        $validator
            ->required('name', 'Warehouse name is required.')
            ->max('name', 255, 'Warehouse name must be maximum 255 characters.')
            ->required('code', 'Warehouse code is required.')
            ->max('code', 50, 'Warehouse code must be maximum 50 characters.');

        $errors = $validator->all();

        if ($this->warehouseModel->codeExistsInCompanyExceptWarehouse(
            $data['code'],
            (int) $currentUser['company_id'],
            $id
        )) {
            $errors[] = 'Warehouse with this code already exists.';
        }

        if (!empty($errors)) {
            $this->view('warehouses/edit', [
                'title' => 'Edit Warehouse',
                'warehouse' => $warehouse,
                'errors' => $errors,
                'old' => $data,
            ]);

            return;
        }

        $data['company_id'] = (int) $currentUser['company_id'];

        $this->warehouseModel->update($id, $data);

        Flash::success('Warehouse updated successfully.');

        $this->redirect('/warehouses');
    }

    public function deactivate(): void
    {
        $currentUser = $this->authService->user();

        $id = 0;

        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        if ($id <= 0) {
            $this->abort(404);
        }

        $warehouse = $this->warehouseModel->findByIdAndCompany(
            $id,
            (int) $currentUser['company_id']
        );

        if ($warehouse === null) {
            $this->abort(404);
        }

        $this->warehouseModel->deactivate(
            $id,
            (int) $currentUser['company_id']
        );

        Flash::success('Warehouse deactivated successfully.');

        $this->redirect('/warehouses');
    }

    private function getFormData(): array
    {
        $name = '';
        $code = '';
        $address = '';
        $description = '';
        $isActive = 0;

        if (isset($_POST['name'])) {
            $name = trim((string) $_POST['name']);
        }

        if (isset($_POST['code'])) {
            $code = strtoupper(trim((string) $_POST['code']));
        }

        if (isset($_POST['address'])) {
            $address = trim((string) $_POST['address']);
        }

        if (isset($_POST['description'])) {
            $description = trim((string) $_POST['description']);
        }

        if (isset($_POST['is_active'])) {
            $isActive = 1;
        }

        return [
            'name' => $name,
            'code' => $code,
            'address' => $address,
            'description' => $description,
            'is_active' => $isActive,
        ];
    }

    private function emptyOldData(): array
    {
        return [
            'name' => '',
            'code' => '',
            'address' => '',
            'description' => '',
            'is_active' => '1',
        ];
    }
}