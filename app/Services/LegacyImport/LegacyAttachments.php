<?php

declare(strict_types=1);

namespace App\Services\LegacyImport;

use DomainException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\FilesystemManager;

final readonly class LegacyAttachments
{
    public function __construct(private Repository $config, private FilesystemManager $files) {}

    public function check(string $name, int $size, bool $copy): void
    {
        if ($name === '' || $name === '.' || $name === '..' || preg_match('~[/\\\\[:cntrl:]]~', $name) === 1) {
            throw new DomainException('stored_name is not a safe attachment basename');
        }

        $directory = $this->config->get('legacy-import.attachment_directory');
        $root = is_string($directory) && $directory !== '' ? realpath($directory) : false;
        $source = $root === false ? false : realpath($root.DIRECTORY_SEPARATOR.$name);
        if ($source === false || dirname($source) !== $root || ! is_file($source) || ! is_readable($source) || filesize($source) !== $size) {
            throw new DomainException('attachment source is missing, unreadable or does not match file_size');
        }

        $target = $this->files->disk('local')->path('issue_attachments/'.$name);
        $sourceHash = hash_file('sha256', $source);
        if ($sourceHash === false) {
            throw new DomainException('attachment source could not be hashed');
        }
        if (file_exists($target) || is_link($target)) {
            $this->verifyTarget($target, $sourceHash);

            return;
        }

        if (! $copy) {
            return;
        }

        $parent = dirname($target);
        if (! is_dir($parent) && ! @mkdir($parent, 0750, true) && ! is_dir($parent)) {
            throw new DomainException('could not create private attachment directory');
        }

        $temporary = tempnam($parent, '.legacy-');
        if ($temporary === false) {
            throw new DomainException('could not stage attachment');
        }

        try {
            if (! @copy($source, $temporary) || hash_file('sha256', $temporary) !== $sourceHash) {
                throw new DomainException('attachment changed while copying or could not be staged');
            }

            // A hard link publishes a complete file and fails if another file already exists.
            if (! @link($temporary, $target)) {
                $this->verifyTarget($target, $sourceHash);
            }
        } finally {
            @unlink($temporary);
        }
    }

    private function verifyTarget(string $target, string $sourceHash): void
    {
        if (is_link($target) || ! is_file($target) || ! is_readable($target) || @hash_file('sha256', $target) !== $sourceHash) {
            throw new DomainException('attachment destination exists with different content or could not be published');
        }
    }
}
