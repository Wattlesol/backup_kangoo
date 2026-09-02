<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\SanadEmployeePermissions;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SanadPermissionAuditCommand extends Command
{
    protected $signature = 'sanad:permission-audit {email} {--fail-on-mismatch : Return a non-zero exit code when mismatches are found}';

    protected $description = 'Audit a Sanad employee permission matrix against derived permissions, sidebar labels, and mapped routes.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        $context = $user->sanadPermissionContext();
        $modules = SanadEmployeePermissions::modules($context);
        $routeMap = SanadEmployeePermissions::routeMap($context);
        $derivedPermissions = SanadEmployeePermissions::spatiePermissions($user->sanad_permission_matrix ?: []);
        $routeResults = $this->routeResults($user, $routeMap);
        $sidebarLabels = $this->sidebarLabels($user);
        $mismatches = $this->mismatches($user, $routeResults, $sidebarLabels);

        $payload = [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'user_type' => $user->user_type,
                'provider_id' => $user->provider_id,
                'context' => $context,
            ],
            'matrix' => $user->sanad_permission_matrix,
            'derived_spatie_permissions' => array_values($derivedPermissions),
            'actual_direct_permissions' => $user->getDirectPermissions()->pluck('name')->sort()->values(),
            'workflow_flags' => $user->sanad_permissions ?: [],
            'catalog_modules' => array_keys($modules),
            'visible_sidebar_labels' => $sidebarLabels,
            'route_results' => $routeResults,
            'mismatches' => $mismatches,
        ];

        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $this->option('fail-on-mismatch') && !empty($mismatches)
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function routeResults(User $user, array $routeMap): array
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $results = [];

        foreach ($routeMap as $module => $routes) {
            foreach ($routes as $route) {
                Auth::login($user);
                $request = Request::create($route['uri'], $route['method']);
                $request->setLaravelSession(app('session')->driver());
                $request->setUserResolver(fn () => $user);
                $response = $kernel->handle($request);

                $expectedAllowed = $user->hasSanadModulePermission($module, $route['action']);
                $actualAllowed = $response->getStatusCode() < 400;

                $results[] = [
                    'module' => $module,
                    'action' => $route['action'],
                    'method' => $route['method'],
                    'uri' => $route['uri'],
                    'expected_allowed' => $expectedAllowed,
                    'status' => $response->getStatusCode(),
                    'actual_allowed' => $actualAllowed,
                ];

                $kernel->terminate($request, $response);
            }
        }

        return $results;
    }

    private function sidebarLabels(User $user): array
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        Auth::login($user);
        $request = Request::create('/home', 'GET');
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => $user);
        $response = $kernel->handle($request);
        $html = method_exists($response, 'getContent') ? $response->getContent() : '';
        $kernel->terminate($request, $response);

        if (!preg_match('/<ul[^>]+id="iq-sidebar-toggle".*?<\/ul>/is', $html, $match)) {
            return [];
        }

        preg_match_all('/<a[^>]*class="[^"]*nav-link[^"]*"[^>]*>(.*?)<\/a>/is', $match[0], $links);

        return collect($links[1])
            ->map(fn ($link) => trim(preg_replace('/\s+/', ' ', strip_tags($link))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function mismatches(User $user, array $routeResults, array $sidebarLabels): array
    {
        $mismatches = [];

        foreach ($routeResults as $result) {
            if ($result['expected_allowed'] !== $result['actual_allowed']) {
                $mismatches[] = [
                    'type' => 'route',
                    'message' => "{$result['uri']} expected " . ($result['expected_allowed'] ? 'allowed' : 'blocked') . " but returned {$result['status']}.",
                    'route' => $result,
                ];
            }
        }

        if ($user->user_type === 'handyman' && empty($user->provider_id)) {
            foreach (['Package Requests', 'Setting'] as $label) {
                if (collect($sidebarLabels)->contains(fn ($visible) => Str::contains($visible, $label))) {
                    $module = $label === 'Setting' ? 'settings' : 'orders';
                    if ($label === 'Setting' && !$user->hasSanadModulePermission($module)) {
                        $mismatches[] = [
                            'type' => 'sidebar',
                            'message' => "{$label} is visible without an allowed {$module} matrix grant.",
                        ];
                    }
                    if ($label === 'Package Requests') {
                        $mismatches[] = [
                            'type' => 'sidebar',
                            'message' => 'Package Requests is visible as a standalone unmapped menu item.',
                        ];
                    }
                }
            }
        }

        return $mismatches;
    }
}
