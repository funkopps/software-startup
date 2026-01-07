<?php

namespace App\ValueObjects;

use Exception;
use Illuminate\Contracts\Support\Arrayable;

abstract class ValueObject implements Arrayable
{
    public function toArray(): array
    {
        $properties = get_object_vars($this);

        $normalize = function (mixed $value) use (&$normalize) {
            if ($value instanceof Arrayable) {
                return $value->toArray();
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = $normalize($v);
                }
            }

            return $value;
        };

        foreach ($properties as $key => $property) {
            $properties[$key] = $normalize($property);
        }

        return $properties;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @throws Exception
     */
    public function pick(string ...$keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            if (!property_exists($this, $key)) {
                throw new Exception("Property {$key} does not exist");
            }

            $result[$key] = $this->$key;
        }

        return $result;
    }

    public function shallowClone(array $overrides): static
    {
        $clone = clone $this;

        foreach ($overrides as $property => $value) {
            $clone->{$property} = $value;
        }

        return $clone;
    }
}
