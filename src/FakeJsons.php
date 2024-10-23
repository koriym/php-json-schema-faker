<?php

declare(strict_types=1);

namespace JSONSchemaFaker;

use FilesystemIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use RuntimeException;
use SplFileInfo;
use stdClass;
use Throwable;

use function file_exists;
use function file_put_contents;
use function is_dir;
use function is_string;
use function json_encode;
use function mkdir;
use function printf;
use function sprintf;
use function str_replace;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

final class FakeJsons
{
    public function __invoke(string $schemaDir, string $distDir, ?string $schemaUri = null): void
    {
        $faker = new Faker();
        foreach ($this->files($schemaDir, 'json') as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            try {
                $fake = $faker->generate($fileInfo);
                $schemaFilname = $fileInfo->getFilename();
                if ($fake instanceof stdClass && is_string($schemaUri)) {
                    $fake->{'$schema'} = sprintf('%s/%s', $schemaUri, $schemaFilname);
                }

                $targetPath = $distDir . str_replace($schemaDir, '', $fileInfo->getPath());
                if (! file_exists($targetPath)) {
                    if (! mkdir($targetPath, 0755, true) && ! is_dir($targetPath)) {
                        throw new RuntimeException(sprintf('Directory "%s" was not created', $targetPath));
                    }
                }

                $distFile = sprintf('%s/%s', $targetPath, $schemaFilname);
                $fakeJson = json_encode($fake, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
                echo $distFile . PHP_EOL;
                file_put_contents($distFile, $fakeJson);
            } catch (Throwable $e) {
                printf("%s: %s %s on line %d\n", $fileInfo->getFilename(), $e->getMessage(), $e->getFile(), $e->getLine());

                continue;
            }
        }
    }

    private function files(string $dir, string $ext): Iterator
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $dir,
                    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS,
                ),
                RecursiveIteratorIterator::LEAVES_ONLY,
            ),
            "/^.+\\.{$ext}/",
            RecursiveRegexIterator::MATCH,
        );
    }
}
