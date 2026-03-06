<?php
// clear.php
// Place this in your Laravel root directory (same level as index.php)

echo "<pre>";

// Change directory to Laravel root (in case it's executed from elsewhere)
chdir(__DIR__);

// Run Laravel cache clear commands
$commands = [
    // 'php artisan storage:link',

    'php artisan cache:clear',
    'php artisan route:clear',
    'php artisan config:clear',
    'php artisan view:clear',
    'php artisan optimize:clear',
];

foreach ($commands as $cmd) {
    echo "Running: $cmd\n";
    echo shell_exec($cmd);
    echo "\n-----------------------------------\n";
}

echo "✅ All Laravel caches cleared successfully!\n";
echo "</pre>";
?>
