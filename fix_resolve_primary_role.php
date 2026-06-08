<?php

$file = 'app/Http/Controllers/Api/Mobile/Concerns/ResolvesMobileRole.php';
$content = file_get_contents($file);

$newContent = str_replace(
    'if ($user->defaultRole) {',
    'if ($user->defaultRole && $user->roles->contains($user->defaultRole)) {',
    $content
);

file_put_contents($file, $newContent);
echo "Fixed ResolvesMobileRole.php\n";

