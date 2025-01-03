<?php

declare(strict_types=1);

namespace JSONSchemaFaker;

use RuntimeException;
use SplFileInfo;
use stdClass;

use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function is_int;
use function json_decode;
use function realpath;
use function sprintf;
use function strpos;
use function substr;

final class Ref
{
    /** @var Faker */
    private $faker;

    /** @var string */
    private $schemaDir;

    public function __construct(Faker $faker, $schemaDir)
    {
        $this->faker = $faker;
        $this->schemaDir = $schemaDir;
    }

    public function __invoke(stdClass $schema, ?stdClass $parentSchema = null)
    {
        $path = (string) $schema->{'$ref'};
        if ($path[0] === '#') {
            $parentSchema = $parentSchema instanceof stdClass ? $parentSchema : $schema;

            return $this->inlineRef($parentSchema, $path);
        }

        return $this->externalRef($path, $parentSchema);
    }

    private function inlineRef(stdClass $parentSchema, string $path)
    {
        $paths = explode('/', substr($path, 2));
        $prop = $parentSchema;
        foreach ($paths as $schemaPath) {
            $prop = $prop->{$schemaPath};
        }

        return $this->faker->generate($prop, null);
    }

    private function externalRef(string $path, ?stdClass $parentSchema = null)
    {
        $jsonFileName = substr($path, 0, 2) === './' ? substr($path, 2) : $path;
        $jsonPath = sprintf('%s/%s', $this->schemaDir, $jsonFileName);
        $realPath = (string) realpath($jsonPath);
        if (is_int(strpos($jsonPath, '#'))) {
            return $this->inlineRefInExternalRef($jsonPath);
        }

        if (! file_exists($jsonPath)) {
            throw new RuntimeException("External JSON schema file not found: {$jsonPath}");
        }

        if (! file_exists($realPath)) {
            return $this->inlineRefInExternalRef($realPath);
        }

        return $this->faker->generate(new SplFileInfo($realPath), $parentSchema, dirname($jsonPath));
    }

    private function inlineRefInExternalRef(string $jsonPath)
    {
        $paths = explode('#', $jsonPath);
        if (count($paths) !== 2) {
            throw new RuntimeException("Invalid reference format in path: {$jsonPath}");
        }

        $schemaFile = $paths[0];
        $path = '.' . $paths[1];
        if (! file_exists($schemaFile)) {
            throw new RuntimeException("Referenced schema file not found: {$schemaFile}");
        }

        $contents = file_get_contents($schemaFile);
        if ($contents === false) {
            throw new RuntimeException("Failed to read schema file: {$schemaFile}");
        }

        $json = json_decode($contents);

        return $this->inlineRef($json, $path);
    }
}
