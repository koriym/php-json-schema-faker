
# PHP JSON Schema Faker
[![CircleCI](https://circleci.com/gh/Leko/php-json-schema-faker.svg?style=svg)](https://circleci.com/gh/Leko/php-json-schema-faker)
[![codecov](https://codecov.io/gh/Leko/php-json-schema-faker/branch/master/graph/badge.svg)](https://codecov.io/gh/Leko/php-json-schema-faker)

Generates fake JSON by JSON schema.

forked from [leko/json-schema-faker](https://github.com/Leko/php-json-schema-faker) (no more maintained.)

## Getting started

```bash
composer require koriym/json-schema-faker
```

```php
<?php

$schema = json_decode(file_get_contents(__DIR__ . '/schema.json'));
$fake = (new Faker)->generate($schema);

or 
// to to support external $ref schema file
$dummy = (new Faker)->generate(new SplFileInfo(__DIR__ . '/schema.json'));
