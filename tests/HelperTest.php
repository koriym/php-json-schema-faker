<?php

declare(strict_types=1);

namespace JSONSchemaFaker\Test;

use Faker\Provider\Internet;
use JsonSchema\Validator;
use JSONSchemaFaker\Faker;
use Throwable;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
class HelperTest extends TestCase
{
    public function testGetMaximumMustReturnMaximumMinusOneIfExclusiveMaximumTrue(): void
    {
        $maximum = 300;
        $schema = (object) ['exclusiveMaximum' => true, 'maximum' => $maximum];

        $actual = (new Faker())->getMaximum($schema);

        // -1 mean exclusive
        $this->assertSame($actual, $maximum - 1);
    }

    public function testGetMaximumMustReturnMaximumIfExclusiveMaximumFalse(): void
    {
        $maximum = 300;
        $schema = (object) ['exclusiveMaximum' => false, 'maximum' => $maximum];

        $actual = (new Faker())->getMaximum($schema);

        $this->assertSame($actual, $maximum);
    }

    public function testGetMaximumMustReturnMaximumIfExclusiveMaximumAbsent(): void
    {
        $maximum = 300;
        $schema = (object) ['maximum' => $maximum];

        $actual = (new Faker())->getMaximum($schema);

        $this->assertSame($actual, $maximum);
    }

    public function testGetMinimumMustReturnMinimumMinusOneIfExclusiveMinimumTrue(): void
    {
        $minimum = 300;
        $schema = (object) ['exclusiveMinimum' => true, 'minimum' => $minimum];

        $actual = (new Faker())->getMinimum($schema);

        // +1 mean exclusive
        $this->assertSame($actual, $minimum + 1);
    }

    public function testGetMinimumMustReturnMinimumIfExclusiveMinimumFalse(): void
    {
        $minimum = 300;
        $schema = (object) ['exclusiveMinimum' => false, 'minimum' => $minimum];

        $actual = (new Faker())->getMinimum($schema);

        $this->assertSame($actual, $minimum);
    }

    public function testGetMinimumMustReturnMinimumIfExclusiveMinimumAbsent(): void
    {
        $minimum = 300;
        $schema = (object) ['minimum' => $minimum];

        $actual = (new Faker())->getMinimum($schema);

        $this->assertSame($actual, $minimum);
    }

    public function testGetMultipleOfMustReturnValueIfPresent(): void
    {
        $expected = 7;
        $schema = (object) ['multipleOf' => $expected];

        $actual = (new Faker())->getMultipleOf($schema);

        $this->assertSame($actual, $expected);
    }

    public function testGetMultipleOfMustReturnOneIfAbsent(): void
    {
        $expected = 1;
        $schema = (object) [];

        $actual = (new Faker())->getMultipleOf($schema);

        $this->assertSame($actual, $expected);
    }

    public function testGetInternetFakerInstanceMustReturnInstance(): void
    {
        $actual = (new Faker())->getInternetFakerInstance();

        $this->assertTrue($actual instanceof Internet);
    }

    /** @dataProvider getFormats */
    public function testGetFormattedValueMustReturnValidValue($format): void
    {
        $schema = (object) ['type' => 'string', 'format' => $format];
        $validator = new Validator();

        $actual = (new Faker())->getFormattedValue($schema);
        $validator->check($actual, $schema);

        $this->assertTrue($validator->isValid());
    }

    public function testGetFormattedValueMustThrowExceptionIfInvalidFormat(): void
    {
        $this->expectException(Throwable::class);

        (new Faker())->getFormattedValue((object) ['format' => 'xxxxx']);
    }

    /** @SuppressWarnings(PHPMD.UnusedPrivateMethod) */
    public function getFormats()
    {
        return [
            ['date-time'],
            ['date'],
            ['time'],
            ['email'],
            ['hostname'],
            ['ipv4'],
            ['ipv6'],
            ['uri'],
        ];
    }
}
