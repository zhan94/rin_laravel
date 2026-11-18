<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $db = $app->make('db')->connection();
        if (! $app->environment('testing')
            || $db->getDriverName() !== 'sqlite'
            || $db->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('The Laravel test suite requires its isolated SQLite in-memory database.');
        }

        $app->make('config')->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        return $app;
    }

    protected function pngUpload(string $name): UploadedFile
    {
        // A real one-pixel PNG exercises the upload validation without requiring GD.
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0E8AAAAASUVORK5CYII=');

        return UploadedFile::fake()->createWithContent($name, $image);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['app.url' => 'http://localhost', 'sanctum.stateful' => ['localhost', 'app.example.test']]);
        $this->withHeader('Origin', 'http://localhost');
    }
}
