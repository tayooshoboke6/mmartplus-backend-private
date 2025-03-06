<?php
/**
 * Script to update CORS configuration in Laravel
 * 
 * Run with: php scripts/update-cors.php
 */

$corsConfigPath = __DIR__ . '/../config/cors.php';

// Check if file exists
if (!file_exists($corsConfigPath)) {
    echo "Error: CORS config file not found at {$corsConfigPath}\n";
    exit(1);
}

// Read current CORS config
$corsConfig = file_get_contents($corsConfigPath);

// Add all possible Vercel domains
$vercelDomains = [
    'https://mmartplus-frontend-private.vercel.app',
    'https://mmartplus-frontend-private-git-master-tayooshoboke6s-projects.vercel.app',
    'https://mmartplus-frontend-private-tayooshoboke6s-projects.vercel.app',
    // Add any additional deployment URLs here
];

// Check if each domain already exists in the config
foreach ($vercelDomains as $domain) {
    if (strpos($corsConfig, $domain) === false) {
        echo "Adding {$domain} to allowed_origins...\n";
        // Add domain to allowed_origins array
        $corsConfig = preg_replace(
            "/'allowed_origins' => \[(.*?)\],/s",
            "'allowed_origins' => [$1\n        '{$domain}',\n    ],",
            $corsConfig
        );
    } else {
        echo "{$domain} already exists in allowed_origins\n";
    }
}

// Save updated CORS config
file_put_contents($corsConfigPath, $corsConfig);
echo "CORS configuration updated successfully!\n";
echo "Remember to deploy these changes to your production server.\n";
