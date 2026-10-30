<?php

namespace App\Services\Ai;

/**
 * Validates the agent's answer against the fixed output schema
 * (resources/ai/schema.json), common to every analysed type.
 *
 * Supports the subset of JSON Schema used there: object (required,
 * properties, additionalProperties), array (items), string (enum).
 * Missing or ill-typed values are errors; nothing is invented.
 * Unknown properties are dropped.
 */
class AiResponseValidator
{
    private const SCHEMA_FILE = 'ai/schema.json';

    /** @var array<string, mixed>|null */
    private static ?array $schema = null;

    /** @return array<string, mixed> */
    public static function schema(): array
    {
        if (self::$schema === null) {
            self::$schema = json_decode(file_get_contents(resource_path(self::SCHEMA_FILE)), true, 512, JSON_THROW_ON_ERROR);
        }

        return self::$schema;
    }

    // Schema as appended to the system prompt
    public static function schemaJson(): string
    {
        return json_encode(self::schema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, mixed> the validated data
     *
     * @throws AiAgentException
     */
    public function validate(mixed $data): array
    {
        // The root of the schema is an object: check() returns an array or fails
        return $this->check($data, self::schema());
    }

    /** @param  array<string, mixed>  $schema */
    private function check(mixed $value, array $schema): mixed
    {
        switch ($schema['type'] ?? null) {
            case 'object':
                if (! is_array($value) || ($value !== [] && array_is_list($value))) {
                    $this->fail();
                }
                $result = [];
                foreach ($schema['required'] ?? [] as $key) {
                    if (! array_key_exists($key, $value)) {
                        $this->fail();
                    }
                }
                foreach ($schema['properties'] ?? [] as $key => $property) {
                    if (array_key_exists($key, $value)) {
                        $result[$key] = $this->check($value[$key], $property);
                    }
                }
                return $result;

            case 'array':
                if (! is_array($value) || ! array_is_list($value)) {
                    $this->fail();
                }
                return array_map(fn ($item) => $this->check($item, $schema['items'] ?? []), $value);

            case 'string':
                if (! is_string($value)) {
                    $this->fail();
                }
                if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
                    $this->fail();
                }
                return $value;
        }

        return $value;
    }

    private function fail(): never
    {
        throw new AiAgentException(AiAgentException::INVALID_RESPONSE);
    }
}
