<?php



// Define the base path for your Laravel project

$basePath = __DIR__;



// Commands to run

$commands = [
    'php artisan make:middleware EnsureTokenIsValid';
    'php artisan route:clear',

    'composer dump-autoload', // Add this line

    'php artisan config:clear',

    'php artisan cache:clear',

    'php artisan view:clear'

];



foreach ($commands as $command) {

    // Construct the full command

    $fullCommand = "$command";



    // Execute the command and capture output

    $output = shell_exec($fullCommand . ' 2>&1');



    // Output the result for debugging

    echo "Running: $fullCommand\n";

    echo "Output: $output\n";
}
