<?php

namespace Nfse\Dto;

use Nfse\Dto\Attributes\CastWith;
use Nfse\Dto\Attributes\MapFrom;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

/** @phpstan-consistent-constructor */
abstract class Dto
{
    public function __construct(...$args)
    {
        $input = isset($args[0]) && is_array($args[0]) ? $args[0] : $args;

        foreach ($this->publicProperties() as $property) {
            $name = $property->getName();
            $map = $property->getAttributes(MapFrom::class);
            $path = $map === [] ? $name : $map[0]->newInstance()->name;

            if ($this->hasPath($input, $path)) {
                $value = $this->getPath($input, $path);
            } elseif (array_key_exists($name, $input)) {
                $value = $input[$name];
            } else {
                continue;
            }

            $property->setValue($this, $this->castProperty($property, $value));
        }
    }

    /** @return static[] */
    public static function arrayOf(array $items): array
    {
        return array_map(static fn ($item) => $item instanceof static ? $item : new static($item), $items);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        $values = [];
        foreach ($this->publicProperties() as $property) {
            if ($property->isInitialized($this)) {
                $values[$property->getName()] = $property->getValue($this);
            }
        }

        return $values;
    }

    public function only(string ...$keys): static
    {
        $selected = array_flip($keys);
        $copy = new static(array_intersect_key($this->all(), $selected));

        foreach ($copy->publicProperties() as $property) {
            if (! isset($selected[$property->getName()])) {
                unset($copy->{$property->getName()});
            }
        }

        return $copy;
    }

    public function except(string ...$keys): static
    {
        $excluded = array_flip($keys);
        $copy = new static(array_diff_key($this->all(), $excluded));

        foreach ($copy->publicProperties() as $property) {
            if (isset($excluded[$property->getName()])) {
                unset($copy->{$property->getName()});
            }
        }

        return $copy;
    }

    public function clone(...$args): static
    {
        $overrides = isset($args[0]) && is_array($args[0]) ? $args[0] : $args;

        return new static(array_replace($this->all(), $overrides));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $values = [];
        foreach ($this->all() as $name => $value) {
            $values[$name] = $this->serializeValue($value);
        }

        return $values;
    }

    /** @return ReflectionProperty[] */
    private function publicProperties(): array
    {
        return (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC);
    }

    private function castProperty(ReflectionProperty $property, mixed $value): mixed
    {
        $castAttributes = $property->getAttributes(CastWith::class);
        if ($castAttributes !== []) {
            /** @var CastWith $attribute */
            $attribute = $castAttributes[0]->newInstance();
            $casterClass = $attribute->caster;
            $caster = new $casterClass($this->propertyTypes($property), ...$attribute->arguments);

            return $caster->cast($value);
        }

        if ($value === null) {
            return null;
        }

        $type = $property->getType();
        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $value;
        }

        $class = $type->getName();
        if (is_subclass_of($class, self::class) && is_array($value)) {
            return new $class($value);
        }

        return $value;
    }

    /** @return string[] */
    private function propertyTypes(ReflectionProperty $property): array
    {
        $type = $property->getType();
        if ($type instanceof ReflectionNamedType) {
            return [$type->getName()];
        }

        return [];
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->serializeValue($item);
            }
        }

        return $value;
    }

    private function hasPath(array $input, string $path): bool
    {
        if (array_key_exists($path, $input)) {
            return true;
        }

        $value = $input;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    private function getPath(array $input, string $path): mixed
    {
        if (array_key_exists($path, $input)) {
            return $input[$path];
        }

        $value = $input;
        foreach (explode('.', $path) as $segment) {
            $value = $value[$segment];
        }

        return $value;
    }
}
