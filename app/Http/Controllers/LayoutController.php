<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LayoutController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()->orderBy('name')->get();
        $layoutCatalog = config('workspaces.catalog', []);

        return view('layouts-management.index', compact('roles', 'layoutCatalog'));
    }

    public function update(Request $request): RedirectResponse
    {
        $catalog = config('workspaces.catalog', []);
        $websiteSlugs = $this->slugsForPlatform($catalog, 'website');
        $mobileSlugs = $this->slugsForPlatform($catalog, 'my_app');

        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*.layout_web_slug' => ['required', 'string', 'in:' . implode(',', $websiteSlugs)],
            'roles.*.layout_web_name' => ['nullable', 'string', 'max:255'],
            'roles.*.layout_mobile_slug' => ['required', 'string', 'in:' . implode(',', $mobileSlugs)],
            'roles.*.layout_mobile_name' => ['nullable', 'string', 'max:255'],
        ]);

        $roles = Role::query()
            ->whereIn('id', array_keys($validated['roles']))
            ->get()
            ->keyBy('id');

        foreach ($validated['roles'] as $roleId => $input) {
            $role = $roles->get((int) $roleId);
            if (!$role) {
                continue;
            }

            $webSlug = (string) $input['layout_web_slug'];
            $mobileSlug = (string) $input['layout_mobile_slug'];

            $this->ensureCompatible($role, $webSlug, $catalog);
            $this->ensureCompatible($role, $mobileSlug, $catalog);

            $role->update([
                'layout_web_slug' => $webSlug,
                'layout_web_name' => $this->layoutName($input['layout_web_name'] ?? null, $webSlug, $catalog),
                'layout_mobile_slug' => $mobileSlug,
                'layout_mobile_name' => $this->layoutName($input['layout_mobile_name'] ?? null, $mobileSlug, $catalog),
            ]);
        }

        return redirect()->route('layouts.index')->with('success', 'Đã cập nhật layout Website và Mobile.');
    }

    private function slugsForPlatform(array $catalog, string $platform): array
    {
        return collect($catalog)
            ->filter(fn (array $layout) => ($layout['platform'] ?? 'website') === $platform)
            ->keys()
            ->all();
    }

    private function ensureCompatible(Role $role, string $slug, array $catalog): void
    {
        $hints = collect($catalog[$slug]['role_hints'] ?? [])
            ->map(fn ($roleName) => strtolower(trim((string) $roleName)))
            ->filter()
            ->all();

        if ($hints !== [] && !in_array(strtolower((string) $role->name), $hints, true)) {
            abort(422, "Layout {$slug} không phù hợp với role {$role->name}.");
        }
    }

    private function layoutName(?string $name, string $slug, array $catalog): string
    {
        $name = trim((string) $name);

        return $name !== '' ? $name : (string) ($catalog[$slug]['label'] ?? $slug);
    }
}
