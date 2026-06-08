<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$compiler = app('blade.compiler');
$input = file_get_contents(resource_path('views/modules/clinical-workflow.blade.php'));
$output = $compiler->compileString($input);
$target = storage_path('framework/views/_debug_compiled_clinical.php');
file_put_contents($target, $output);
echo "compiled";
