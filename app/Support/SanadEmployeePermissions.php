<?php

namespace App\Support;

use App\Models\User;

class SanadEmployeePermissions
{
    public const ACTIONS = ['read', 'write', 'delete'];

    public static function modules(string $context): array
    {
        $adminModules = [
            'dashboard' => ['label' => 'Dashboard', 'description' => 'Admin overview cards and shortcuts.', 'permissions' => ['read' => ['booking list']]],
            'orders' => ['label' => 'Orders', 'description' => 'Request and order workspace.', 'permissions' => ['read' => ['booking list'], 'write' => ['booking add', 'booking edit'], 'delete' => ['booking delete']]],
            'assignment' => ['label' => 'Assignment', 'description' => 'Assign requests and employees.', 'permissions' => ['read' => ['booking list'], 'write' => ['booking edit']]],
            'request_documents' => ['label' => 'Request Documents', 'description' => 'Review and manage request documents.', 'permissions' => ['read' => ['document list'], 'write' => ['document add', 'document edit'], 'delete' => ['document delete']], 'flags' => ['write' => ['review_documents']]],
            'customer_chat' => ['label' => 'Customer Chat', 'description' => 'Read and respond to request chats.', 'permissions' => ['read' => ['booking list']], 'flags' => ['write' => ['customer_chat']]],
            'quality_control' => ['label' => 'Quality Control', 'description' => 'Quality queue and review activity.', 'permissions' => ['read' => ['handyman list'], 'write' => ['handyman edit']]],
            'partner' => ['label' => 'Partner', 'description' => 'Partner records and status.', 'permissions' => ['read' => ['provider list'], 'write' => ['provider add', 'provider edit'], 'delete' => ['provider delete']]],
            'employee' => ['label' => 'Employee', 'description' => 'Employee records and permissions.', 'permissions' => ['read' => ['handyman list', 'handymantype list'], 'write' => ['handyman add', 'handyman edit', 'handymantype add', 'handymantype edit'], 'delete' => ['handyman delete', 'handymantype delete']], 'flags' => ['write' => ['manage_employees']]],
            'customer' => ['label' => 'Customer', 'description' => 'Customer and user lists.', 'permissions' => ['read' => ['user list'], 'write' => ['user add', 'user edit'], 'delete' => ['user delete']]],
            'service_catalog' => ['label' => 'Service Catalog', 'description' => 'Categories, subcategories, and services.', 'permissions' => ['read' => ['category list', 'subcategory list', 'service list'], 'write' => ['category add', 'category edit', 'subcategory add', 'subcategory edit', 'service add', 'service edit'], 'delete' => ['category delete', 'subcategory delete', 'service delete']]],
            'service_bundles' => ['label' => 'Service Bundles', 'description' => 'Bundled service packages.', 'permissions' => ['read' => ['servicepackage list'], 'write' => ['servicepackage add', 'servicepackage edit'], 'delete' => ['servicepackage delete']]],
            'additional_services' => ['label' => 'Additional Services', 'description' => 'Service add-ons and extras.', 'permissions' => ['read' => ['service add on list'], 'write' => ['service add on add', 'service add on edit'], 'delete' => ['service add on delete']]],
            'payments' => ['label' => 'Payments', 'description' => 'Payment and payout status.', 'permissions' => ['read' => ['payment list']], 'flags' => ['read' => ['view_payment_status']]],
            'documents' => ['label' => 'Documents', 'description' => 'System document configuration.', 'permissions' => ['read' => ['document list', 'providerdocument list'], 'write' => ['document add', 'document edit', 'providerdocument add', 'providerdocument edit'], 'delete' => ['document delete', 'providerdocument delete']]],
            'ai_tools' => ['label' => 'AI Tools', 'description' => 'Knowledge base, assistant, escalations, and chat.', 'permissions' => ['read' => ['booking list'], 'write' => ['booking edit']]],
            'settings' => ['label' => 'Settings/System', 'description' => 'Roles, permissions, and system settings.', 'permissions' => ['read' => ['role list', 'permission list'], 'write' => ['role add', 'role edit', 'permission add', 'permission edit'], 'delete' => ['role delete', 'permission delete']]],
        ];

        $partnerModules = [
            'dashboard' => ['label' => 'Dashboard', 'description' => 'Partner employee operational overview.', 'permissions' => ['read' => ['booking list']]],
            'my_tasks' => ['label' => 'My Assigned Orders/Tasks', 'description' => 'Assigned partner work only.', 'permissions' => ['read' => ['booking list'], 'write' => ['booking edit']]],
            'request_documents' => ['label' => 'Request Documents', 'description' => 'Review required documents.', 'permissions' => ['read' => ['document list'], 'write' => ['document add', 'document edit']], 'flags' => ['write' => ['review_documents']]],
            'upload_documents' => ['label' => 'Upload Documents', 'description' => 'Upload request evidence and files.', 'permissions' => ['write' => ['document add', 'document edit']], 'flags' => ['write' => ['upload_documents']]],
            'customer_chat' => ['label' => 'Customer Chat', 'description' => 'Customer request messages.', 'permissions' => ['read' => ['booking list']], 'flags' => ['write' => ['customer_chat']]],
            'buzz_customer' => ['label' => 'Buzz Customer', 'description' => 'Send customer buzz alerts.', 'permissions' => ['write' => ['booking edit']], 'flags' => ['write' => ['send_buzz']]],
            'stage_progress' => ['label' => 'Stage Progress', 'description' => 'Update assigned stage progress.', 'permissions' => ['read' => ['booking list'], 'write' => ['booking edit']], 'flags' => ['write' => ['complete_stage']]],
            'payment_status' => ['label' => 'Payment Status', 'description' => 'Read payment status only.', 'permissions' => ['read' => ['payment list']], 'flags' => ['read' => ['view_payment_status']]],
            'internal_notes' => ['label' => 'Internal Notes', 'description' => 'Write internal request notes.', 'permissions' => ['write' => ['booking edit']], 'flags' => ['write' => ['internal_notes']]],
            'partner_profile' => ['label' => 'Partner Profile', 'description' => 'Limited partner profile operations.', 'permissions' => ['read' => ['provider list'], 'write' => ['provider edit']]],
            'team_employees' => ['label' => 'Team Employees', 'description' => 'Manage partner team employees.', 'permissions' => ['read' => ['handyman list'], 'write' => ['handyman add', 'handyman edit'], 'delete' => ['handyman delete']], 'flags' => ['write' => ['team_collaboration', 'manage_employees']]],
        ];

        return $context === 'partner' ? $partnerModules : $adminModules;
    }

    public static function routeMap(string $context): array
    {
        $adminRoutes = [
            'dashboard' => [
                ['method' => 'GET', 'uri' => '/home', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/sanad/dashboard', 'action' => 'read'],
            ],
            'orders' => [
                ['method' => 'GET', 'uri' => '/booking', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/Handyman_booking', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/booking/create', 'action' => 'write'],
            ],
            'assignment' => [
                ['method' => 'GET', 'uri' => '/sanad/assignments', 'action' => 'read'],
            ],
            'request_documents' => [
                ['method' => 'GET', 'uri' => '/sanad/request-documents', 'action' => 'read'],
            ],
            'partner' => [
                ['method' => 'GET', 'uri' => '/provider', 'action' => 'read'],
            ],
            'employee' => [
                ['method' => 'GET', 'uri' => '/handyman', 'action' => 'read'],
            ],
            'customer' => [
                ['method' => 'GET', 'uri' => '/user/list/all', 'action' => 'read'],
            ],
            'payments' => [
                ['method' => 'GET', 'uri' => '/payment', 'action' => 'read'],
            ],
            'documents' => [
                ['method' => 'GET', 'uri' => '/document', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/providerdocument', 'action' => 'read'],
            ],
            'ai_tools' => [
                ['method' => 'GET', 'uri' => '/sanad/knowledge-base', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/sanad/ai', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/sanad/ai/escalations', 'action' => 'read'],
            ],
            'service_catalog' => [
                ['method' => 'GET', 'uri' => '/service', 'action' => 'read'],
            ],
            'service_bundles' => [
                ['method' => 'GET', 'uri' => '/servicepackage', 'action' => 'read'],
            ],
            'additional_services' => [
                ['method' => 'GET', 'uri' => '/serviceaddon', 'action' => 'read'],
            ],
            'settings' => [
                ['method' => 'GET', 'uri' => '/setting', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/role', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/permission', 'action' => 'read'],
            ],
        ];

        $partnerRoutes = [
            'dashboard' => [
                ['method' => 'GET', 'uri' => '/home', 'action' => 'read'],
                ['method' => 'GET', 'uri' => '/sanad/dashboard', 'action' => 'read'],
            ],
            'my_tasks' => [
                ['method' => 'GET', 'uri' => '/sanad/requests', 'action' => 'read'],
            ],
            'request_documents' => [
                ['method' => 'GET', 'uri' => '/sanad/request-documents', 'action' => 'read'],
            ],
            'payment_status' => [
                ['method' => 'GET', 'uri' => '/payment', 'action' => 'read'],
            ],
            'team_employees' => [
                ['method' => 'GET', 'uri' => '/handyman', 'action' => 'read'],
            ],
        ];

        return $context === 'partner' ? $partnerRoutes : $adminRoutes;
    }

    public static function contextFor(?User $employee, ?User $actor = null, $providerId = null): string
    {
        if ($actor && $actor->user_type === 'provider') {
            return 'partner';
        }

        if ($providerId !== null && $providerId !== '') {
            return 'partner';
        }

        if ($employee && !empty($employee->provider_id)) {
            return 'partner';
        }

        return 'admin';
    }

    public static function normalize(array $submittedModules, string $context): array
    {
        $allowedModules = self::modules($context);
        $modules = [];

        foreach ($submittedModules as $moduleKey => $actions) {
            if (!isset($allowedModules[$moduleKey]) || !is_array($actions)) {
                continue;
            }

            foreach (self::ACTIONS as $action) {
                $hasCapability = !empty($allowedModules[$moduleKey]['permissions'][$action] ?? [])
                    || !empty($allowedModules[$moduleKey]['flags'][$action] ?? []);

                if ($hasCapability && !empty($actions[$action])) {
                    $modules[$moduleKey][$action] = true;
                }
            }
        }

        return [
            'context' => $context === 'partner' ? 'partner' : 'admin',
            'modules' => $modules,
        ];
    }

    public static function selectedModulesFromMatrix(?array $matrix, string $context): ?array
    {
        if (empty($matrix['modules']) || ($matrix['context'] ?? null) !== $context) {
            return null;
        }

        return self::normalize($matrix['modules'], $context)['modules'];
    }

    public static function selectedModulesFromLegacy(User $employee, string $context): array
    {
        if (!$employee->exists) {
            return [];
        }

        $directPermissions = $employee->getDirectPermissions()->pluck('name')->all();
        $workflowFlags = $employee->sanad_permissions ?: [];
        $selected = [];

        foreach (self::modules($context) as $moduleKey => $module) {
            foreach (self::ACTIONS as $action) {
                $permissionNames = $module['permissions'][$action] ?? [];
                $flagNames = $module['flags'][$action] ?? [];

                if (
                    count(array_intersect($permissionNames, $directPermissions)) > 0 ||
                    count(array_intersect($flagNames, $workflowFlags)) > 0
                ) {
                    $selected[$moduleKey][$action] = true;
                }
            }
        }

        return $selected;
    }

    public static function spatiePermissions(array $matrix): array
    {
        $context = $matrix['context'] ?? 'admin';
        $allowedModules = self::modules($context);
        $permissions = [];

        foreach (($matrix['modules'] ?? []) as $moduleKey => $actions) {
            if (!isset($allowedModules[$moduleKey]) || !is_array($actions)) {
                continue;
            }

            foreach (array_keys(array_filter($actions)) as $action) {
                $permissions = array_merge($permissions, $allowedModules[$moduleKey]['permissions'][$action] ?? []);
            }
        }

        return array_values(array_unique(array_filter($permissions)));
    }

    public static function syncUser(User $user): array
    {
        $context = self::contextFor($user);
        $matrix = $user->sanad_permission_matrix ?: [
            'context' => $context,
            'modules' => self::selectedModulesFromLegacy($user, $context),
        ];

        $matrix = self::normalize($matrix['modules'] ?? [], $matrix['context'] ?? $context);
        $permissions = self::spatiePermissions($matrix);
        $flags = self::workflowFlags($matrix);

        $user->forceFill([
            'sanad_permission_matrix' => $matrix,
            'sanad_permissions' => $flags,
        ])->save();

        $user->syncPermissions($permissions);

        return [
            'context' => $matrix['context'],
            'modules' => $matrix['modules'],
            'spatie_permissions' => $permissions,
            'workflow_flags' => $flags,
        ];
    }

    public static function workflowFlags(array $matrix): array
    {
        $context = $matrix['context'] ?? 'admin';
        $allowedModules = self::modules($context);
        $flags = [];

        foreach (($matrix['modules'] ?? []) as $moduleKey => $actions) {
            if (!isset($allowedModules[$moduleKey]) || !is_array($actions)) {
                continue;
            }

            foreach (array_keys(array_filter($actions)) as $action) {
                $flags = array_merge($flags, $allowedModules[$moduleKey]['flags'][$action] ?? []);
            }
        }

        return array_values(array_unique(array_filter($flags)));
    }

    public static function userCan(User $user, string $module, string $action = 'read'): bool
    {
        if ($user->hasAnyRole(['admin', 'demo_admin'])) {
            return true;
        }

        $matrix = $user->sanad_permission_matrix ?: [];
        if (empty($matrix['modules'])) {
            $context = self::contextFor($user);
            $matrix = [
                'context' => $context,
                'modules' => self::selectedModulesFromLegacy($user, $context),
            ];
        }

        return !empty($matrix['modules'][$module][$action]);
    }
}
