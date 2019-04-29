<?php

declare(strict_types=1);

namespace JSONSchemaFaker\Test;

use JsonSchema\Validator;
use JSONSchemaFaker\FakeJsons;

class FakeJsonsTest extends TestCase
{
    /**
     * @var FakeJsons
     */
    protected $fakeJsons;

    protected function setUp() : void
    {
        $this->fakeJsons = new FakeJsons;
    }

    public function testInvoke() : void
    {
        ($this->fakeJsons)(__DIR__ . '/fixture', __DIR__ . '/dist', 'http://example.com/schema');
        $validator = new Validator;
        $data = json_decode((string) file_get_contents(__DIR__ . '/dist/ref_file_double.json'));
        $validator->validate($data, (object) ['$ref' => 'file://' . __DIR__ . '/fixture/ref_file_double.json']);
        foreach ($validator->getErrors() as $error) {
            echo sprintf("[%s] %s\n", $error['property'], $error['message']);
        }
        $this->assertTrue($validator->isValid());
    }
}
