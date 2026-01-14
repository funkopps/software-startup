<?php

namespace App\ValueObjects;

use ReflectionClass;

abstract class SerializableValueObject extends ValueObject
{
    public function __serialize(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = [
                    '__class' => get_class($value),
                    '__data' => $value->__serialize(),
                ];
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    public function __unserialize(array $data): void
    {
        $unpack = function (mixed $value) use (&$unpack): mixed {
            if (is_array($value) && isset($value['__class'])) {
                /** @var static $obj */
                /** @noinspection PhpUnhandledExceptionInspection */
                $obj = (new ReflectionClass($value['__class']))
                    ->newInstanceWithoutConstructor();
                $obj->__unserialize($value['__data']);

                return $obj;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = $unpack($v);
                }
            }

            return $value;
        };

        foreach ($data as $property => $value) {
            $this->{$property} = $unpack($value);
        }
    }

    public static function fromArray(array $array): static
    {
        return new static(...$array);
    }
}