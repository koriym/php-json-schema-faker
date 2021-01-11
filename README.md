
# PHP JSON Schema Faker
![Continuous Integration](https://github.com/koriym/php-json-schema-faker/workflows/Continuous%20Integration/badge.svg)
![Coding Standards](https://github.com/koriym/php-json-schema-faker/workflows/Coding%20Standards/badge.svg)

Generates fake JSON with JSON schema.

 * `$ref` supported
 * CLI command available

forked from [leko/json-schema-faker](https://github.com/Leko/php-json-schema-faker) (deprecated)

## Getting started

```bash
composer require koriym/json-schema-faker
```

### Usage

```php
$schema = json_decode(file_get_contents(__DIR__ . '/schema.json'));
$fake = (new Faker)->generate($schema);
```

or

```php
// pass SplFileInfo to support local $ref schema file
$fake = (new Faker)->generate(new SplFileInfo(__DIR__ . '/schema.json'));
```

### Command

```
// convert all json schema jsons in the directory
./vendor/bin/fakejsons {$soruceDir} {$disDir}
```
