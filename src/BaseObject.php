<?php

namespace EndorphinStudio\DataObject;

/**
 * BaseObject
 * @package EndorphinStudio\DataObject
 * @author Serhii Nekhaienko <serhii.nekhaienko@gmail.com>
 */
abstract class BaseObject implements \JsonSerializable
{
    public const CAMEL_CASE = '';
    public const SNAKE_LASE = '_';

    /**
     * @var array $data
     * Array of data fields with data
     */
    protected array $data;

    /**
     * @var string[]
     * Define mappings of fields
     */
    protected array $fieldTypeMapping = [

    ];

    /**
     * @var string[]
     * Define fields which are list of objects
     */
    protected array $listFields = [

    ];

    /**
     * @var string[]
     * List of primitives
     */
    private array $primitiveTypes = [
        'int',
        'integer',
        'string'
    ];

    public function __construct(array $data = [])
    {
        if (!is_array($data)) {
            throw new \RuntimeException('$data should be an array');
        }
        $this->data = $data;

        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                $this->setProperty($key, $this->fromArray($key, $value));
            }
        }

        /**
         * Call init$PropertyName if method exist
         */
        foreach (array_keys($this->data) as $option) {
            $key = $this->convertFieldName($option);

            $method = 'init' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    /**
     * @param string $field
     * @param array $value
     * @return DataObject|mixed|BaseObject
     */
    private function fromArray(string $field, array $value)
    {
        /**
         * Check if this field has type mapping
         */
        if (array_key_exists($field, $this->fieldTypeMapping)) {
            $className = $this->fieldTypeMapping[$field];
            if (class_exists($className)) {
                return new $className($value);
            }
        }

        /**
         * Check if this field is list of objects
         */
        if (array_key_exists($field, $this->listFields)) {
            $className = $this->listFields[$field];

            /**
             * Check if class is primitive (int, string, etc.)
             */
            if (in_array($className, $this->primitiveTypes, true)) {
                return $value;
            }

            if (class_exists($className)) {
                $list = [];
                foreach ($value as $item) {
                    $list[] = new $className($item);
                }
                return $list;
            }
        }

        return new DataObject($value);
    }

    /**
     * @param $name
     * @return mixed|null
     * Return object
     */
    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * @param $name
     * @param $value
     * Set object
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * @param $name
     * @return bool
     * Check if field exist
     */
    public function __isset($name): bool
    {
        return $this->_has($name);
    }

    /**
     * @param $name
     * @return bool
     * Check if field exist
     */
    private function _has($name): bool
    {
        return array_key_exists(
                $name,
                $this->data
            ) && $this->data[$name];
    }

    /**
     * @param $name
     * @param $arguments
     * @return boolean|mixed|null
     */
    public function __call($name, $arguments)
    {
        $words = preg_split('/(?=[A-Z])/', $name);
        $methodType = $words[0];
        unset($words[0]);
        $field_name = $this->getFieldName(implode('', $words));
        switch ($methodType) {
            case 'get':
                return $this->$field_name;
                break;
            case 'has':
            case 'is':
                return $this->_has($field_name);
                break;
        }
    }

    /**
     * Return field name for data
     * @param $name
     * @return string
     */
    private function getFieldName($name): string
    {
        $name = lcfirst($name);
        $camelName = $this->convertFieldName($name, self::CAMEL_CASE);
        $snakeName = $this->convertFieldName($name, self::SNAKE_LASE);
        if (array_key_exists($camelName, $this->data)) {
            return $camelName;
        }
        if (array_key_exists($snakeName, $this->data)) {
            return $snakeName;
        }
        return $name;
    }

    /**
     * Convert MyField to my_field or my_field to MyField
     * @param string $name
     * @param string $replacement
     * @return string
     */
    private function convertFieldName($name, $replacement = ''): string
    {
        $words = preg_split('/(?=[A-Z_])/', $name);
        foreach ($words as &$word) {
            $word = str_replace('_', '', $word);
        }
        if (!empty($replacement)) {
            return strtolower(implode($replacement, $words));
        }
        foreach ($words as $i => &$word) {
            if ($i === 0) {
                continue;
            }
            $word = ucfirst($word);
        }
        return implode($replacement, $words);
    }

    /**
     * @param $key
     * @return bool
     * Check that fieldExist
     */
    public function has($key): bool
    {
        return $this->_has($key);
    }

    /**
     * @return bool
     * Check if errors exist in object
     */
    public function hasErrors(): bool
    {
        return $this->has('error') || $this->has('errors');
    }

    /**
     * @return string
     * Return error
     */
    public function getError(): string
    {
        return ($this->has('error')) ? $this->data['error'] : $this->data['errors'];
    }

    /**
     * @return array
     * Return all fields
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $propertyName
     * @return mixed|null
     * Return field
     */
    public function getProperty(string $propertyName)
    {
        if ($this->has($propertyName)) {
            return $this->$propertyName;
        }
        return null;
    }

    /**
     * @return array
     * Used during jsonSerialization
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param string $propertyName
     * @param $value
     * Set field
     */
    public function setProperty(string $propertyName, $value): void
    {
        $this->data[$propertyName] = $value;
    }

    /**
     * @return array
     * Return array with data
     */
    public function toArray(): array
    {
        return $this->getData();
    }

    /**
     * @param string $json
     * @return BaseObject
     * @throws \JsonException
     * Create object from JSON string
     */
    public static function fromJsonString(string $json): BaseObject
    {
        $jsonObject = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return new static($jsonObject);
    }
}
