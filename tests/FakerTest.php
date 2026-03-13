<?php

declare(strict_types=1);

namespace JSONSchemaFaker\Test;

use InvalidArgumentException;
use JsonSchema\Validator;
use JSONSchemaFaker\Faker;
use JSONSchemaFaker\InvalidItemsException;
use JSONSchemaFaker\UnsupportedTypeException;
use RuntimeException;
use SplFileInfo;
use stdClass;

use function file_put_contents;
use function json_encode;
use function mb_strlen;
use function sys_get_temp_dir;
use function unlink;

use const JSON_PRETTY_PRINT;

class FakerTest extends TestCase
{
    /** @dataProvider getTypes */
    public function testFakeMustReturnValidValue($type): void
    {
        $schema = $this->getFixture($type);
        $validator = new Validator();

        $actual = (new Faker())->generate($schema);
        $validator->check($actual, $schema);

        $this->assertTrue($validator->isValid(), (string) json_encode($validator->getErrors(), JSON_PRETTY_PRINT));
    }

    /** @dataProvider getTypesAndFile */
    public function testFakeFromFile($type): void
    {
        $schema = $this->getFile($type);
        $validator = new Validator();

        $actual = (new Faker())->generate(new SplFileInfo($schema));
        $validator->check($actual, $schema);

        $this->assertTrue($validator->isValid(), (string) json_encode($validator->getErrors(), JSON_PRETTY_PRINT));
    }

    public function testGenerateInvalidParameter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Faker())->generate(null);
    }

    public function getTypes()
    {
        return [
            ['boolean'],
            ['null'],
            ['integer'],
            ['number'],
            ['string'],
            ['array'],
            ['object'],
            ['combining'],
            ['ref_inline'],
        ];
    }

    public function getTypesAndFile()
    {
        return [
            ['boolean'],
            ['null'],
            ['integer'],
            ['number'],
            ['string'],
            ['array'],
            ['object'],
            ['combining'],
            ['ref_file'],
            ['ref_file_ref'],
            ['ref_file_double'],
            ['ref_array'],
        ];
    }

    public function testFakeMustThrowExceptionIfInvalidType(): void
    {
        $this->expectException(UnsupportedTypeException::class);

        (new Faker())->generate((object) ['type' => 'xxxxx']);
    }

    public function testConst(): void
    {
        $schema = (object) ['type' => 'string', 'const' => 'fixed_value'];

        $actual = (new Faker())->generate($schema);

        $this->assertSame('fixed_value', $actual);
    }

    public function testStringWithShortMaxLength(): void
    {
        $schema = (object) ['type' => 'string', 'maxLength' => 3];

        $actual = (new Faker())->generate($schema);

        $this->assertLessThanOrEqual(3, mb_strlen($actual));
    }

    public function testInvalidItemsThrowsException(): void
    {
        $this->expectException(InvalidItemsException::class);

        (new Faker())->generate((object) ['type' => 'array', 'items' => 'invalid']);
    }

    public function testPatternPropertiesMatch(): void
    {
        $schema = (object) [
            'type' => 'object',
            'required' => ['testKey'],
            'properties' => new stdClass(),
            'patternProperties' => (object) [
                '^test' => (object) ['type' => 'string'],
            ],
            'additionalProperties' => false,
        ];

        $actual = (new Faker())->generate($schema);

        $this->assertObjectHasProperty('testKey', $actual);
        $this->assertIsString($actual->testKey);
    }

    public function testExternalRefWithInlineRef(): void
    {
        $schema = new SplFileInfo(__DIR__ . '/fixture/ref_external_inline.json');

        $actual = (new Faker())->generate($schema);

        $this->assertObjectHasProperty('inlineRef', $actual);
        $this->assertIsArray($actual->inlineRef);
    }

    public function testExternalRefFileNotFound(): void
    {
        $this->expectException(RuntimeException::class);

        $schema = (object) [
            'type' => 'object',
            'required' => ['ref'],
            'properties' => (object) [
                'ref' => (object) ['$ref' => 'nonexistent.json'],
            ],
        ];

        $faker = new Faker();
        $faker->generate(new SplFileInfo(__DIR__ . '/fixture/boolean.json'));
        $faker->generate($schema);
    }

    public function testExternalRefWithInlineRefFileNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Referenced schema file not found');

        $schema = (object) [
            'type' => 'object',
            'required' => ['ref'],
            'properties' => (object) [
                'ref' => (object) ['$ref' => 'nonexistent.json#/definitions/foo'],
            ],
        ];

        $faker = new Faker();
        $faker->generate(new SplFileInfo(__DIR__ . '/fixture/boolean.json'));
        $faker->generate($schema);
    }

    public function testExternalRefWithInvalidRefFormat(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid reference format');

        $tmpFile = sys_get_temp_dir() . '/test#schema#invalid.json';
        file_put_contents($tmpFile, '{"type": "string"}');

        $schema = (object) [
            'type' => 'object',
            'required' => ['ref'],
            'properties' => (object) [
                'ref' => (object) ['$ref' => $tmpFile . '#/definitions/foo'],
            ],
        ];

        try {
            $faker = new Faker();
            $faker->generate(new SplFileInfo(__DIR__ . '/fixture/boolean.json'));
            $faker->generate($schema);
        } finally {
            @unlink($tmpFile);
        }
    }
}
