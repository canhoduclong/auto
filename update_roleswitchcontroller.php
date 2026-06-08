<?php

$file = 'app/Http/Controllers/RoleSwitchController.php';
$content = file_get_contents($file);

$newContent = preg_replace('/public function switch\(Request \$request, string \$role\).*?public function clear/s', 'public function switch(Request $request, string $role)
    {
        $user = Auth::user();
        
        // Validate that user has this role
        if (!$user->hasRole($role)) {
            return redirect()->route(\'dashboard\')->with(\'error\', \'Bạn không có quyền truy cập vai trò này.\');
        }

        $roleModel = \App\Models\Role::where(\'name\', $role)->first();
        if ($roleModel) {
            $user->update([\'default_role_id\' => $roleModel->id]);
        }

        // Web Redirect based on Role config
        $route = $roleModel->layout_web_slug ?? \'pages.my_profile\';
        
        if (\Illuminate\Support\Facades\Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect()->route(\'pages.my_profile\');
    }

    public function clear', $content);

file_put_contents($file, $newContent);
echo "Updated RoleSwitchController.php\n";

