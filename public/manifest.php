<?php
require_once __DIR__ . '/../app/bootstrap.php';

header('Content-Type: application/manifest+json');

$baseUrl = SITE_URL;
echo json_encode([
    'name' => APP_NAME,
    'short_name' => 'Stores',
    'start_url' => $baseUrl . 'dashboard.php',
    'scope' => $baseUrl,
    'display' => 'standalone',
    'background_color' => '#f5f1e8',
    'theme_color' => '#667eea',
    'description' => 'Hotel inventory and stock management system for Manica Skyview Stores.',
    'icons' => [
        [
            'src' => $baseUrl . 'public/img/pwa-icon-192.svg',
            'sizes' => '192x192',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
        [
            'src' => $baseUrl . 'public/img/pwa-icon-512.svg',
            'sizes' => '512x512',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);