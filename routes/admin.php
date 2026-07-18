<?php

declare(strict_types=1);

use App\Middleware\AccountStatusMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Modules\Admin\AdminController;

$router = $app->router();
$superAdminMiddleware = [
    AuthMiddleware::class,
    AccountStatusMiddleware::class,
    [RoleMiddleware::class, ['super_admin', 'hr_only']],
];

$userManagementMiddleware = [
    AuthMiddleware::class,
    AccountStatusMiddleware::class,
    [RoleMiddleware::class, ['super_admin', 'hr_only', 'hr_admin']],
];

$router->get('/admin/users', [AdminController::class, 'users'], $userManagementMiddleware);
$router->get('/admin/users/create', [AdminController::class, 'createUser'], $userManagementMiddleware);
$router->post('/admin/users/create', [AdminController::class, 'storeUser'], $userManagementMiddleware);
$router->get('/admin/users/{id}/edit', [AdminController::class, 'editUser'], $userManagementMiddleware);
$router->post('/admin/users/{id}/edit', [AdminController::class, 'updateUser'], $userManagementMiddleware);
$router->post('/admin/users/{id}/welcome-email', [AdminController::class, 'sendWelcomeEmail'], $userManagementMiddleware);

$router->get('/admin/roles', [AdminController::class, 'roles'], $superAdminMiddleware);
$router->post('/admin/roles', [AdminController::class, 'storeRole'], $superAdminMiddleware);
$router->get('/admin/roles/{id}/permissions', [AdminController::class, 'rolePermissions'], $superAdminMiddleware);
$router->post('/admin/roles/{id}/permissions', [AdminController::class, 'updateRolePermissions'], $superAdminMiddleware);