<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class UserWorkspaceService
{
    public function catalog(): array
    {
        $catalog = config('workspaces.catalog', []);

        return collect($catalog)
            ->mapWithKeys(function (array $definition, string $slug): array {
                $normalized = $this->normalizeCatalogItem($slug, $definition);

                return [$normalized['slug'] => $normalized];
            })
            ->all();
    }

    public function availableForUser(User $user): array
    {
        $user->loadMissing('roles');

        return $this->availableForRoles($user->roles->all());
    }

    public function availableForRoleNames(iterable $roleNames): array
    {
        $normalizedRoles = $this->normalizeRoles($roleNames);

        $roles = Role::query()
            ->get(['name', 'layout_web_name', 'layout_web_slug'])
            ->filter(function (Role $role) use ($normalizedRoles): bool {
                return in_array($this->normalizeRoleName((string) $role->name), $normalizedRoles, true);
            })
            ->values()
            ->all();

        return $this->availableForRoles($roles);
    }

    public function findAvailableWorkspace(array $availableWorkspaces, ?string $workspaceKey): ?array
    {
        $workspaceKey = $this->normalizeRoleName($workspaceKey);
        if ($workspaceKey === '') {
            return null;
        }

        foreach ($availableWorkspaces as $workspace) {
            if ($workspace['key'] === $workspaceKey) {
                return $workspace;
            }
        }

        return null;
    }

    public function findForUser(User $user, ?string $workspaceKey): ?array
    {
        return $this->findAvailableWorkspace($this->availableForUser($user), $workspaceKey);
    }

    public function findForRoleNames(iterable $roleNames, ?string $workspaceKey): ?array
    {
        return $this->findAvailableWorkspace($this->availableForRoleNames($roleNames), $workspaceKey);
    }

    public function resolveSessionWorkspace(User $user, ?string $activeWorkspace, ?string $activeRole = null): ?array
    {
        $availableWorkspaces = $this->availableForUser($user);

        $workspace = $this->findAvailableWorkspace($availableWorkspaces, $activeWorkspace);
        if ($workspace !== null) {
            return $workspace;
        }

        $activeRole = $this->normalizeRoleName($activeRole);
        if ($activeRole === '') {
            return null;
        }

        return collect($availableWorkspaces)
            ->first(function (array $workspace) use ($activeRole): bool {
                return in_array($activeRole, $workspace['matched_roles'], true);
            });
    }

    public function resolveAutomaticWorkspace(User $user): ?array
    {
        $availableWorkspaces = $this->availableForUser($user);

        if (count($availableWorkspaces) === 1) {
            return $availableWorkspaces[0];
        }

        return $this->findAvailableWorkspace($availableWorkspaces, $user->default_workspace);
    }

    public function syncSession(array $workspace): void
    {
        session([
            'active_workspace' => $workspace['key'],
            'active_role' => $workspace['active_role'],
        ]);
    }

    public function clearSession(): void
    {
        session()->forget(['active_workspace', 'active_role']);
    }

    public function clearInvalidDefaultWorkspace(User $user): bool
    {
        $defaultWorkspace = $this->normalizeRoleName($user->default_workspace);
        if ($defaultWorkspace === '') {
            return false;
        }

        if ($this->findForUser($user, $defaultWorkspace) !== null) {
            return false;
        }

        $user->forceFill(['default_workspace' => null])->save();

        return true;
    }

    private function availableForRoles(iterable $roles): array
    {
        $catalog = $this->catalog();
        $workspaces = [];

        foreach ($roles as $role) {
            $roleName = $this->normalizeRoleName((string) ($role->name ?? ''));
            $layoutSlug = $this->normalizeRoleName((string) ($role->layout_web_slug ?? ''));

            if ($roleName === '' || $layoutSlug === '' || !isset($catalog[$layoutSlug])) {
                continue;
            }

            $catalogItem = $catalog[$layoutSlug];
            if (!Route::has($catalogItem['route'])) {
                continue;
            }

            if (!isset($workspaces[$layoutSlug])) {
                $workspaces[$layoutSlug] = [
                    'key' => $layoutSlug,
                    'label' => trim((string) ($role->layout_web_name ?? '')) !== ''
                        ? trim((string) $role->layout_web_name)
                        : $catalogItem['label'],
                    'description' => $catalogItem['description'],
                    'platform' => $catalogItem['platform'],
                    'route' => $catalogItem['route'],
                    'matched_roles' => [],
                    'active_role' => $roleName,
                ];
            }

            $workspaces[$layoutSlug]['matched_roles'][] = $roleName;
        }

        return collect($workspaces)
            ->map(function (array $workspace): array {
                $workspace['matched_roles'] = array_values(array_unique($workspace['matched_roles']));

                return $workspace;
            })
            ->values()
            ->all();
    }

    private function normalizeCatalogItem(string $slug, array $item): array
    {
        return [
            'slug' => $this->normalizeRoleName($slug),
            'label' => (string) ($item['label'] ?? $slug),
            'description' => (string) ($item['description'] ?? ''),
            'platform' => (string) ($item['platform'] ?? 'website'),
            'route' => (string) ($item['route'] ?? ''),
        ];
    }

    private function normalizeRoles(iterable $roleNames): array
    {
        return collect($roleNames)
            ->map(fn ($roleName) => $this->normalizeRoleName((string) $roleName))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeRoleName(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}