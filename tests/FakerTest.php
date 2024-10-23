<?php

declare(strict_types=1);

namespace JSONSchemaFaker\Test;

use InvalidArgumentException;
use JsonSchema\Validator;
use JSONSchemaFaker\Faker;
use JSONSchemaFaker\UnsupportedTypeException;
use SplFileInfo;

use function json_encode;

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
}
