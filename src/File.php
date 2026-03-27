<?php

namespace Cosmologist\Gears;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * File object-oriented implementation
 *
 * @template T of object
 */
final readonly class File
{
    public function __construct(private(set) string $path)
    {
    }

    public function parent(): self
    {
        return new self(dirname($this->path));
    }

    public function child(string $name): self
    {
        return new self(FileType::joinPaths($this->path, $name));
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }

    public function assertExists(): void
    {
        if (!$this->exists()) {
            throw new \InvalidArgumentException("File '{$this->path}' doesn't exist");
        }
    }

    public function mkdir(): void
    {
        if (!file_exists($this->path)) {
            mkdir($this->path, recursive: true);
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
        $json       = $serializer->serialize($data, 'json');
        $this->put($json);
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
     * @param  class-string<T>|null  $class  The expected class to instantiate
     *
     * @return T|array
     */
    public function unserialize(string|null $class = null): array|object
    {
        $serializer = new Serializer([new ObjectNormalizer(), new ArrayDenormalizer()], [new JsonEncoder()]);
        $data       = file_get_contents($this->path);

        return ($class === null)
            ? $serializer->decode($data, 'json')
            : $serializer->deserialize($data, $class, 'json');
    }

    public function get(): string
    {
        return file_get_contents($this->path);
    }

    public function put($data): void
    {
        $this->parent()->mkdir();
        file_put_contents($this->path, $data);
    }
}
