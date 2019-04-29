<?php

declare(strict_types=1);


namespace JSONSchemaFaker;

use function dirname;
use function file_exists;
use function realpath;
use function substr;

class Ref
{
    /**
     * @var Faker
     */
    private $faker;

    /**
     * @var string
     */
    private $schemaDir;

    public function __construct(Faker $faker, $schemaDir)
    {
        $this->faker = $faker;
        $this->schemaDir = $schemaDir;
    }

    public function __invoke(\stdClass $schema, \stdClass $parentSchema = null)
    {
        $path = (string) $schema->{'$ref'};
        if ($path[0] === '#' && $parentSchema instanceof \stdClass) {
            return $this->inlineRef($parentSchema, $path);
        }

        return $this->externalRef($path, $parentSchema);
    }

    private function inlineRef(\stdClass $parentSchema, string $path)
    {
        $paths = explode('/', substr($path, 2));
        $prop = $parentSchema;
        foreach ($paths as $schemaPath) {
            $prop = $prop->{$schemaPath};
        }

        return $this->faker->generate($prop, null);
    }

    private function externalRef(string $path, \stdClass $parentSchema = null)
    {
        $jsonFileName = substr($path, 0, 2) === './' ? substr($path, 2) : $path;
        $jsonPath = sprintf('%s/%s', $this->schemaDir, $jsonFileName);
        $realPath = realpath($jsonPath);
        if (! file_exists($jsonPath)) {
            throw new \RuntimeException("JSON file not exits:{$jsonPath}");
        }
        if (! file_exists($realPath)) {
            return $this->inlineRefInExternalRef($realPath);
        }

        return $this->faker->generate(new \SplFileInfo($realPath), $parentSchema, dirname($jsonPath));
    }

    private function inlineRefInExternalRef(string $jsonPath)
    {
        $paths = explode('#', $jsonPath);
        if (count($paths) !== 2) {
            throw new \RuntimeException("JSON file not exits:{$jsonPath}");
        }
        $schemaFile = $paths[0];
        $path = '.' . $paths[1];
        if (! file_exists($schemaFile)) {
            throw new \RuntimeException("JSON file not exits:{$jsonPath}");
        }
        $json = json_decode(file_get_contents($schemaFile));

        return $this->inlineRef($json, $path);
    }
}
