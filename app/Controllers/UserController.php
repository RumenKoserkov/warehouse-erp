<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Core\Validator;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;

class UserController extends Controller
{
    private User $userModel;

    private Role $roleModel;

    private AuthService $authService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->roleModel = new Role();
        $this->authService = new AuthService();
    }

    public function index(): void
    {
        $currentUser = $this->authService->user();

        $users = $this->userModel->allByCompany((int)$currentUser['company_id']);

        $this->view('users/index', [
            'title' => 'Users',
            'users' => $users,
        ]);
    }

    public function create(): void
    {
        $roles = $this->roleModel->all();

        $this->view('users/create', [
            'title' => 'Create User',
            'roles' => $roles,
            'errors' => [],
            'old' => [
                'name' => '',
                'email' => '',
                'role_id' => '',
                'is_active' => '1',
            ],
        ]);
    }

    public function store(): void
    {
        $currentUser = $this->authService->user();

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $roleId = (int)($_POST['role_id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $validator = new Validator($_POST);

        $validator
            ->required('name', 'Name is required.')
            ->max('name', 255, 'Name must be maximum 255 characters.')
            ->required('email', 'Email is required.')
            ->email('email', 'Email must be a valid email address.')
            ->required('role_id', 'Role is required.')
            ->required('password', 'Password is required.')
            ->min('password', 6, 'Password must be at least 6 characters.')
            ->required('password_confirmation', 'Password confirmation is required.');

        $errors = $validator->all();

        if ($roleId <= 0) {
            $errors[] = 'Please select a valid role.';
        }

        if ($password !== $passwordConfirmation) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($this->userModel->emailExistsInCompany($email, (int)$currentUser['company_id'])) {
            $errors[] = 'A user with this email already exists.';
        }

        if (!empty($errors)) {
            $roles = $this->roleModel->all();

            $this->view('users/create', [
                'title' => 'Create User',
                'roles' => $roles,
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'email' => $email,
                    'role_id' => (string)$roleId,
                    'is_active' => (string)$isActive,
                ],
            ]);

            return;
        }

        $this->userModel->create([
            'company_id' => (int)$currentUser['company_id'],
            'role_id' => $roleId,
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'is_active' => $isActive,
        ]);

        Flash::success('User created successfully.');

        $this->redirect('/users');
    }

    public function edit(): void
    {
        $currentUser = $this->authService->user();

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404);
        }

        $user = $this->userModel->findByIdAndCompany(
            $id,
            (int)$currentUser['company_id']
        );

        if ($user === null) {
            $this->abort(404);
        }

        $roles = $this->roleModel->all();

        $this->view('users/edit', [
            'title' => 'Edit User',
            'user' => $user,
            'roles' => $roles,
            'errors' => [],
            'old' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'role_id' => (string)$user['role_id'],
                'is_active' => (string)$user['is_active'],
            ],
        ]);
    }

    public function update(): void
    {
        $currentUser = $this->authService->user();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404);
        }

        $user = $this->userModel->findByIdAndCompany(
            $id,
            (int)$currentUser['company_id']
        );

        if ($user === null) {
            $this->abort(404);
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $roleId = (int)($_POST['role_id'] ?? 0);
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        $validator = new Validator($_POST);

        $validator
            ->required('name', 'Name is required.')
            ->max('name', 255, 'Name must be maximum 255 characters.')
            ->required('email', 'Email is required.')
            ->email('email', 'Email must be a valid email address.')
            ->required('role_id', 'Role is required.');

        if ($password !== '') {
            $validator->min('password', 6, 'Password must be at least 6 characters.');
        }

        $errors = $validator->all();

        if ($roleId <= 0) {
            $errors[] = 'Please select a valid role.';
        }

        if ($password !== '' && $password !== $passwordConfirmation) {
            $errors[] = 'Password confirmation does not match.';
        }

        if ($this->userModel->emailExistsInCompanyExceptUser($email, (int)$currentUser['company_id'], $id)) {
            $errors[] = 'A user with this email already exists.';
        }

        if ((int)$currentUser['id'] === $id && $isActive === 0) {
            $errors[] = 'You cannot deactivate your own account.';
        }

        if (!empty($errors)) {
            $roles = $this->roleModel->all();

            $this->view('users/edit', [
                'title' => 'Edit User',
                'user' => $user,
                'roles' => $roles,
                'errors' => $errors,
                'old' => [
                    'name' => $name,
                    'email' => $email,
                    'role_id' => (string)$roleId,
                    'is_active' => (string)$isActive,
                ],
            ]);

            return;
        }

        $data = [
            'company_id' => (int)$currentUser['company_id'],
            'role_id' => $roleId,
            'name' => $name,
            'email' => $email,
            'is_active' => $isActive,
            'password' => null,
        ];

        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $data);

        Flash::success('User updated successfully.');

        $this->redirect('/users');
    }

    public function deactivate(): void
    {
        $currentUser = $this->authService->user();

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404);
        }

        if ((int)$currentUser['id'] === $id) {
            Flash::danger('You cannot deactivate your own account.');
            $this->redirect('/users');
        }

        $user = $this->userModel->findByIdAndCompany(
            $id,
            (int)$currentUser['company_id']
        );

        if ($user === null) {
            $this->abort(404);
        }

        $this->userModel->deactivate($id, (int)$currentUser['company_id']);

        Flash::success('User deactivated successfully.');

        $this->redirect('/users');
    }
}