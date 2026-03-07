<?php
/**
 * mia/controllers/PageController.php
 *
 * Handles all public-facing page requests.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/App.php';

class PageController
{
    public function landing(): void
    {
        require __DIR__ . '/../views/pages/landing.php';
    }

    public function pricing(): void
    {
        require __DIR__ . '/../views/pages/pricing.php';
    }

    public function demo(): void
    {
        require __DIR__ . '/../views/pages/demo.php';
    }

    public function features(): void
    {
        require __DIR__ . '/../views/pages/features.php';
    }

    public function notFound(): void
    {
        http_response_code(404);
        require __DIR__ . '/../views/pages/404.php';
    }
}
