<?php

namespace Cosmologist\Gears;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * File object-oriented implementation
 *
 * @template T of object
 */
final readonly class File
{
    public function __construct(private string $path)
    {
    }

    public function parent(): self
    {
        return new self(dirname($this->path));
    }

    public function mkdir(): void
    {
        if (!file_exists($this->path)) {
            mkdir($this->path);
        }
    }

    /**
     * Serializes data and immediately writes it to the file corresponding to the object
     *
     * <code>
     * $storage = new File('storage/abc.json');
     * $storage->serialize($fooObject);
     * </code>
     *
     * @param array|T $data
     */
    public function serialize(array|object $data): void
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $json = $serializer->serialize($data, 'json');
        $this->parent()->mkdir();
        file_put_contents($this->path, $json);
    }

    /**
     * Unserializes data from the file corresponding to the object
     *
     * <code>
     * $storage = new File('storage/abc.json');
     * $storage->serialize($fooObject);
     * $barObject = $storage->unserialize(MyObject::class); // object(MyObject)
     * </code>
     *
     * @param class-string<T> $class The expected class to instantiate
     */
    public function unserialize(string $class): array|object
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        return $serializer->deserialize(file_get_contents($this->path), $class, 'json');
    }
}
