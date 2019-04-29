<?php

declare(strict_types=1);


namespace JSONSchemaFaker;

use function dirname;
use Faker\Factory;
use Faker\Provider\Base;
use Faker\Provider\DateTime;
use Faker\Provider\Internet;
use Faker\Provider\Lorem;
use function file_get_contents;
use function json_decode;
use function substr;

class Faker
{
    /**
     * @var string
     */
    private $schemaDir;


    /**
     * Create dummy data with JSON schema
     *
     * @param \SplFileInfo|\stdClass $schema       Data structure writen in JSON Schema
     * @param \stdClass              $parentSchema parent schema when it is subschema
     * @param string                 $schemaDir    forced directory in object loop
     *
     * @throws \Exception Throw when unsupported type specified
     *
     * @return mixed dummy data
     */
    public function generate($schema, \stdClass $parentSchema = null, string $schemaDir = null)
    {
        if ($schema instanceof \SplFileInfo) {
            $file = $schema->getRealPath();
            $this->schemaDir = dirname($file);
            $schema = json_decode(file_get_contents($file));
        }
        if (! $schema instanceof \stdClass) {
            throw new \InvalidArgumentException(gettype($schema));
        }
        $schema = $this->resolveOf($schema);
        $fakers = $this->getFakers();
        if (property_exists($schema, '$ref')) {
            $currentDir = $schemaDir ?? $this->schemaDir;

            return (new Ref($this, $currentDir))($schema, $parentSchema);
        }
        $type = is_array($schema->type) ? Base::randomElement($schema->type) : $schema->type;

        if (isset($schema->enum)) {
            return Base::randomElement($schema->enum);
        }

        if (! isset($fakers[$type])) {
            throw new \Exception("Unsupported type: {$type}");
        }

        return $fakers[$type]($schema);
    }

    public function mergeObject()
    {
        $merged = [];
        $objList = func_get_args();

        foreach ($objList as $obj) {
            $merged = array_merge($merged, (array) $obj);
        }

        return (object) $merged;
    }

    public function getMaximum($schema) : int
    {
        $offset = ($schema->exclusiveMaximum ?? false) ? 1 : 0;

        return (int) ($schema->maximum ?? mt_getrandmax()) - $offset;
    }

    public function getMinimum($schema) : int
    {
        $offset = ($schema->exclusiveMinimum ?? false) ? 1 : 0;

        return (int) ($schema->minimum ?? -mt_getrandmax()) + $offset;
    }

    public function resolveDependencies(\stdClass $schema, array $keys) : array
    {
        $resolved = [];
        $dependencies = $schema->dependencies ?? new \stdClass();

        foreach ($keys as $key) {
            $resolved = array_merge($resolved, [$key], $dependencies->{$key} ?? []);
        }

        return $resolved;
    }

    private function getFakers()
    {
        return [
            'null' => [$this, 'fakeNull'],
            'boolean' => [$this, 'fakeBoolean'],
            'integer' => [$this, 'fakeInteger'],
            'number' => [$this, 'fakeNumber'],
            'string' => [$this, 'fakeString'],
            'array' => [$this, 'fakeArray'],
            'object' => [$this, 'fakeObject']
        ];
    }

    /**
     * Create null
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function fakeNull()
    {
        return null;
    }

    /**
     * Create dummy boolean with JSON schema
     *
     * @return bool true or false
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function fakeBoolean()
    {
        return Base::randomElement([true, false]);
    }

    /**
     * Create dummy integer with JSON schema
     *
     * @param \stdClass $schema Data structure
     *
     * @return int
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function fakeInteger(\stdClass $schema)
    {
        $minimum = $this->getMinimum($schema);
        $maximum = $this->getMaximum($schema);
        $multipleOf = $this->getMultipleOf($schema);

        return (int) Base::numberBetween($minimum, $maximum) * $multipleOf;
    }

    /**
     * Create dummy floating number with JSON schema
     *
     * @param \stdClass $schema Data structure
     *
     * @return float
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function fakeNumber(\stdClass $schema)
    {
        $minimum = $this->getMinimum($schema);
        $maximum = $this->getMaximum($schema);
        $multipleOf = $this->getMultipleOf($schema);

        return Base::randomFloat(null, $minimum, $maximum) * $multipleOf;
    }

    /**
     * @param \stdClass $schema Data structure
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function fakeString(\stdClass $schema)
    {
        if (isset($schema->format)) {
            return $this->getFormattedValue($schema);
        }
        if (isset($schema->pattern)) {
            return Lorem::regexify($schema->pattern);
        }
        $min = $schema->minLength ?? 1;
        $max = $schema->maxLength ?? max(5, $min + 1);
        if ($max < 5) {
            return substr(Lorem::text(5), 0, $max);
        }
        $lorem = Lorem::text($max);

        if (mb_strlen($lorem) < $min) {
            $lorem = str_repeat($lorem, $min);
        }

        return mb_substr($lorem, 0, $max);
    }

    /**
     * @param \stdClass $schema Data structure
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function fakeArray(\stdClass $schema)
    {
        if (! isset($schema->items)) {
            $subschemas = [$this->getRandomSchema()];
        // List
        } elseif (is_object($schema->items)) {
            $subschemas = [$schema->items];
        // Tuple
        } elseif (is_array($schema->items)) {
            $subschemas = $schema->items;
        } else {
            throw new \Exception('Invalid items');
        }

        $dummies = [];
        $itemSize = Base::numberBetween(($schema->minItems ?? 0), $schema->maxItems ?? count($subschemas));
        $subschemas = array_slice($subschemas, 0, $itemSize);
        $dir = $this->schemaDir;
        for ($i = 0; $i < $itemSize; $i++) {
            $subschema = $subschemas[$i % count($subschemas)];
            $dummies[] = $this->generate($subschema, $schema, $dir);
        }
        $this->schemaDir = $dir;

        return ($schema->uniqueItems ?? false) ? array_unique($dummies) : $dummies;
    }

    /**
     * @param \stdClass $schema Data structure
     *
     * @return \stdClass
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function fakeObject(\stdClass $schema)
    {
        $dir = $this->schemaDir;
        $properties = $schema->properties ?? new \stdClass();
        $propertyNames = $this->getProperties($schema);

        $dummy = new \stdClass();
        $schemaDir = $this->schemaDir;
        foreach ($propertyNames as $key) {
            if (isset($properties->{$key})) {
                $subschema = $properties->{$key};
            } else {
                $subschema = $this->getAdditionalPropertySchema($schema, $key) ?: $this->getRandomSchema();
            }

            $dummy->{$key} = $this->generate($subschema, $schema, $schemaDir);
        }
        $this->schemaDir = $dir;

        return $dummy;
    }

    public function getRandomSchema()
    {
        $fakerNames = array_keys($this->getFakers());

        return (object) [
            'type' => Base::randomElement($fakerNames)
        ];
    }

    public function resolveOf(\stdClass $schema)
    {
        if (isset($schema->allOf)) {
            return call_user_func_array([$this,'mergeObject'], $schema->allOf);
        }
        if (isset($schema->anyOf)) {
            return call_user_func_array([$this,'mergeObject'], Base::randomElements($schema->anyOf));
        }
        if (isset($schema->oneOf)) {
            return Base::randomElement($schema->oneOf);
        }

        return $schema;
    }

    public function getMultipleOf($schema) : int
    {
        return $schema->multipleOf ?? 1;
    }

    public function getInternetFakerInstance() : Internet
    {
        return new Internet(Factory::create());
    }

    public function getFormattedValue($schema)
    {
        switch ($schema->format) {
            // Date representation, as defined by RFC 3339, section 5.6.
            case 'date-time':
                return DateTime::dateTime()->format(DATE_RFC3339);
            // Internet email address, see RFC 5322, section 3.4.1.
            case 'email':
                return $this->getInternetFakerInstance()->safeEmail();
            // Internet host name, see RFC 1034, section 3.1.
            case 'hostname':
                return $this->getInternetFakerInstance()->domainName();
            // IPv4 address, according to dotted-quad ABNF syntax as defined in RFC 2673, section 3.2.
            case 'ipv4':
                return $this->getInternetFakerInstance()->ipv4();
            // IPv6 address, as defined in RFC 2373, section 2.2.
            case 'ipv6':
                return $this->getInternetFakerInstance()->ipv6();
            // A universal resource identifier (URI), according to RFC3986.
            case 'uri':
                return $this->getInternetFakerInstance()->url();
            default:
                throw new \Exception("Unsupported type: {$schema->format}");
        }
    }

    /**
     * @return string[] Property names
     */
    public function getProperties(\stdClass $schema) : array
    {
        $requiredKeys = $schema->required ?? [];
        $optionalKeys = array_keys((array) ($schema->properties ?? new \stdClass()));
        $maxProperties = $schema->maxProperties ?? count($optionalKeys) - count($requiredKeys);
        $pickSize = Base::numberBetween(0, min(count($optionalKeys), $maxProperties));
        $additionalKeys = $this->resolveDependencies($schema, Base::randomElements($optionalKeys, $pickSize));
        $propertyNames = array_unique(array_merge($requiredKeys, $additionalKeys));

        $additionalProperties = $schema->additionalProperties ?? true;
        $patternProperties = $schema->patternProperties ?? new \stdClass();
        $patterns = array_keys((array) $patternProperties);
        while (count($propertyNames) < ($schema->minProperties ?? 0)) {
            $name = $additionalProperties ? Lorem::word() : Lorem::regexify(Base::randomElement($patterns));
            if (! in_array($name, $propertyNames, true)) {
                $propertyNames[] = $name;
            }
        }

        return $propertyNames;
    }

    private function getAdditionalPropertySchema(\stdClass $schema, $property)
    {
        $patternProperties = $schema->patternProperties ?? new \stdClass();
        $additionalProperties = $schema->additionalProperties ?? true;

        foreach ($patternProperties as $pattern => $sub) {
            if (preg_match("/{$pattern}/", $property)) {
                return $sub;
            }
        }

        if (is_object($additionalProperties)) {
            return $additionalProperties;
        }
    }
}
