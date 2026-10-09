<?php
namespace App\Console;
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$soas = \App\Models\StatementOfAccount::where('is_paid', 'false')->get();
echo "Count: " . $soas->count() . "\n";
