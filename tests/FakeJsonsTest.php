<?php

declare(strict_types=1);

namespace JSONSchemaFaker\Test;

use JsonSchema\Validator;
use JSONSchemaFaker\FakeJsons;

use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function mkdir;
use function ob_get_clean;
use function ob_start;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class FakeJsonsTest extends TestCase
{
    /** @var FakeJsons */
    protected $fakeJsons;

    protected function setUp(): void
    {
        $this->fakeJsons = new FakeJsons();
    }

    public function testInvoke(): void
    {
        ($this->fakeJsons)(__DIR__ . '/fixture', __DIR__ . '/dist', 'http://example.com/schema');
        $validator = new Validator();
        $data = json_decode((string) file_get_contents(__DIR__ . '/dist/ref_file_double.json'));
        $validator->validate($data, (object) ['$ref' => 'file://' . __DIR__ . '/fixture/ref_file_double.json']);
        foreach ($validator->getErrors() as $error) {
            echo sprintf("[%s] %s\n", $error['property'], $error['message']);
        }

        $this->assertTrue($validator->isValid());
    }

    public function testInvokeWithInvalidSchema(): void
    {
        $tmpDir = sys_get_temp_dir() . '/json-schema-faker-test-' . uniqid();
        $distDir = $tmpDir . '/dist';
        $schemaDir = $tmpDir . '/schema';
        mkdir($schemaDir, 0755, true);
        mkdir($distDir, 0755, true);

        file_put_contents($schemaDir . '/invalid.json', '{invalid json}');

        ob_start();
        ($this->fakeJsons)($schemaDir, $distDir);
        $output = ob_get_clean();

        $this->assertStringContainsString('invalid.json:', $output);

        unlink($schemaDir . '/invalid.json');
        rmdir($schemaDir);
        if (is_dir($distDir)) {
            rmdir($distDir);
        }

        rmdir($tmpDir);
    }
}
