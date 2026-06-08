<?php

$file = 'app/Http/Controllers/Api/Mobile/Concerns/ResolvesMobileRole.php';
$content = file_get_contents($file);

$newContent = preg_replace('/private function resolvePrimaryRole.*?private function resolveSelectedMobileRole/s', 'private function resolvePrimaryRole(User $user): string
    {
        if ($user->defaultRole) {
            return $user->defaultRole->name;
        }

        $role = $user->roles->first();
        return $role ? $role->name : \'user\';
    }

    private function resolveSelectedMobileRole', $content);

$newContent = preg_replace('/private function resolveSelectedMobileRole.*?private function mobileWorkspaces/s', 'private function resolveSelectedMobileRole(User $user): string
    {
        if ($user->defaultRole) {
            return $user->defaultRole->name;
        }

        return $this->resolvePrimaryRole($user);
    }

    private function mobileWorkspaces', $newContent);

$newContent = preg_replace('/private function mobileWorkspaces.*?private function resolveLayout/s', 'private function mobileWorkspaces(User $user): array
    {
        $workspaces = [];

        foreach ($user->roles as $role) {
            $layout = $role->layout_mobile_slug ?? \'unsupported\';
            if (!isset($workspaces[$layout]) && $layout !== \'unsupported\') {
                $workspaces[$layout] = [
                    \'role\' => $role->name,
                    \'layout\' => $layout,
                    \'label\' => $role->layout_mobile_name ?? $role->name,
                    \'menu\' => $this->mobileMenuByLayout($layout, $user->hasRole(\'manager_shipper\') || $user->hasRole(\'admin\')),
                ];
            }
        }

        return array_values($workspaces);
    }

    private function resolveLayout', $newContent);

$newContent = preg_replace('/private function resolveLayout\(string \$role\): string.*?private function mobileMenuByLayout/s', 'private function resolveLayout(string $roleName): string
    {
        $role = \App\Models\Role::where(\'name\', $roleName)->first();
        return $role->layout_mobile_slug ?? \'unsupported\';
    }

    private function mobileMenuByLayout', $newContent);

file_put_contents($file, $newContent);
echo "Updated ResolvesMobileRole.php\n";

$authFile = 'app/Http/Controllers/Api/Mobile/AuthApiController.php';
$authContent = file_get_contents($authFile);

// Remove the hardcoded layout checks
$authContent = preg_replace('/if \(\!in_array\(\$layout, \[\'shipper\', \'manager_shipper\', \'warehouse\', \'sale\', \'accounting\', \'ceo\'\], true\) && \!\$user->hasRole\(\'admin\'\)\) \{.*?return \$this->fail\(\'Tai khoan nay khong thuoc role mobile duoc ho tro.\', 403\);\s*\}/s', 'if ($layout === \'unsupported\' && !$user->hasRole(\'admin\')) {
            return $this->fail(\'Tai khoan nay khong thuoc role mobile duoc ho tro.\', 403);
        }', $authContent);

$authContent = preg_replace('/if \(\!in_array\(\$layout, \[\'shipper\', \'manager_shipper\', \'warehouse\', \'sale\', \'accounting\', \'ceo\'\], true\) && \!\$user->hasRole\(\'admin\'\)\) \{.*?return \$this->fail\(\'Tai khoan Google nay chua duoc gan role mobile duoc ho tro.\', 403\);\s*\}/s', 'if ($layout === \'unsupported\' && !$user->hasRole(\'admin\')) {
            return $this->fail(\'Tai khoan Google nay chua duoc gan role mobile duoc ho tro.\', 403);
        }', $authContent);

// Fix $workspace search in switchRole
$authContent = preg_replace('/\$workspace = collect\(\$this->mobileWorkspaces\(\$user\)\)\s*->first\(fn \(array \$item\) => \(string\) \$item\[\'role\'\] === \$role\);/', '$workspace = collect($this->mobileWorkspaces($user))
            ->first(fn (array $item) => strtolower((string) $item[\'role\']) === $role);', $authContent);

file_put_contents($authFile, $authContent);
echo "Updated AuthApiController.php\n";

