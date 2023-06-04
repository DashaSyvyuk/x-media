<?php

namespace App\Service;

use Closure;
use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Symfony\Component\PropertyAccess\PropertyAccess;

class HydratorService
{
    public function __construct(private readonly ArrayTransformerInterface $serializer)
    {
    }

    /**
     * @param object $sourceObject
     * @param string $destinationClass
     * @param array $groups
     * @param Closure|null $callback
     * @param bool $skipNull
     * @return object|array
     */
    public function hydrate(object $sourceObject, string $destinationClass, array $groups = [], Closure $callback = null, bool $skipNull = false): object|array
    {
        $context = $this->getSerializationContext($groups);

        $normalize = $this->serializer->toArray($sourceObject, $context);
        if ($callback) {
            $normalize = $callback($normalize);
        }

        return $this->fromArray($normalize, $destinationClass, [], $skipNull);
    }

    /**
     * @param array $array
     * @param string $destinationClass
     * @param array $groups
     * @param bool $skipNull
     * @return object|array
     */
    public function fromArray(array $array, string $destinationClass, array $groups = [], bool $skipNull = false): object|array
    {
        $context = $this->getDeserializationContext($groups);

        $destinationObject = $this->serializer->fromArray($array, $destinationClass, $context);

        if ($skipNull) {
            $result = [];

            foreach ($destinationObject as $key => $value) {
                if (!empty($value)) {
                    $result[$key] = $value;
                }
            }
        }

        return $result ?? $destinationObject;
    }

    /**
     * @param object $object
     * @param array $groups
     * @return array
     */
    public function toArray(object $object, array $groups = []): array
    {
        $context = $this->getSerializationContext($groups);

        return $this->serializer->toArray($object, $context);
    }

    public function convertObjects($from, $to, $exclude = [], $excludeNulls = false)
    {
        $getableProps = $this->getGetableProperties($from);
        $setableProps = $this->getSetableProperties($to);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($getableProps as $getableProp) {
            if ($propertyAccessor->isReadable($from, $getableProp)) {
                $value = $propertyAccessor->getValue($from, $getableProp);

                if (in_array($getableProp, $setableProps)) {
                    if ($propertyAccessor->isWritable($to, $getableProp) && !in_array($getableProp, $exclude)) {
                        if (!(is_null($value) && $excludeNulls)) {
                            $propertyAccessor->setValue($to, $getableProp, $value);
                        }
                    }
                }
            }
        }

        return $to;
    }

    /**
     * @param object $object
     * @return array
     */
    private function getSetableProperties(object $object): array
    {
        return $this->getCallablePropertiesByPrefix($object, 'set');
    }

    /**
     * @param object $object
     * @return array
     */
    private function getGetableProperties(object $object): array
    {
        return $this->getCallablePropertiesByPrefix($object, 'get');
    }

    /**
     * @param $object
     * @param $prefix
     * @return array
     */
    private function getCallablePropertiesByPrefix($object, $prefix): array
    {
        $properties = array_keys(get_object_vars($object));
        $methods    = get_class_methods($object);

        /**
         * @codeCoverageIgnoreStart
         */
        $setMethods = array_filter($methods, function ($item) use ($prefix) {
            return substr($item, 0, 3) === $prefix;
        });

        /**
         * @codeCoverageIgnoreStart
         */
        $privateProperties = array_map(function ($item) use ($prefix) {
            return lcfirst(preg_replace('/^' . preg_quote($prefix) . '/', '', $item));
        }, $setMethods);

        return array_unique(array_merge($properties, $privateProperties));
    }

    /**
     * @param array $groups
     * @return SerializationContext
     */
    protected function getSerializationContext(array $groups): SerializationContext
    {
        $context = SerializationContext::create();

        if (!empty($groups)) {
            $context->setGroups($groups);
        }
        return $context;
    }

    /**
     * @param array $groups
     * @return DeserializationContext
     */
    protected function getDeserializationContext(array $groups): DeserializationContext
    {
        $context = DeserializationContext::create();

        if (!empty($groups)) {
            $context->setGroups($groups);
        }
        return $context;
    }
}
