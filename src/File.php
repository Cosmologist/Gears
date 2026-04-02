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
final class File
{
    public function __construct(private(set) readonly string $path)
    {
    }

    /**
     * Lock file handle resource
     *
     * Used by lock() and unlock() methods to track the lock state.
     *
     * @var resource|null
     */
    private $lockHandle = null;

    /**
     * Get the base name of the file
     *
     * @return string The file name with leading directory paths removed
     */
    public function basename(): string
    {
        return basename($this->path);
    }

    /**
     * Get the file extension
     *
     * @return string|null The extension without the leading dot, or null if none exists
     */
    public function extension(): string|null
    {
        $ext = pathinfo($this->path, PATHINFO_EXTENSION);

        return ($ext === '') ? null : $ext;
    }

    /**
     * Get the parent directory of the current file
     *
     * @return File A new File instance representing the parent directory
     */
    public function parent(): self
    {
        return new self(dirname($this->path));
    }

    /**
     * Create a child file or directory path relative to the current file
     *
     * @param string $name The name of the child file or directory
     *
     * @return File A new File instance representing the child path
     */
    public function child(string $name): self
    {
        return new self(FileType::joinPaths($this->path, $name));
    }

    /**
     * Check if the file or directory exists
     *
     * @return bool True if the file exists, false otherwise
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Assert that the file exists
     *
     * @throws FileException If the file does not exist
     */
    public function assertExists(): void
    {
        if (!$this->exists()) {
            throw FileException::notFound($this->path);
        }
    }

    /**
     * List all files and directories in the current directory
     *
     * @return File[] An array of File objects representing the children
     */
    public function list(): array
    {
        return array_reduce(scandir($this->path), function(array $result, string $item) {
            if ($item !== '.' && $item !== '..') {
                $result[] = $this->child($item);
            }
            return $result;
        }, []);

    }

    /**
     * Create the directory if it doesn't exist
     */
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

    /**
     * Get the file contents
     *
     * @return string The contents of the file
     */
    public function get(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * Write data to the file
     *
     * @param mixed $data The data to write
     */
    public function put(mixed $data): void
    {
        $this->parent()->mkdir();
        file_put_contents($this->path, $data);
    }

    /**
     * Acquire a lock on the file
     *
     * Opens the file and acquires a lock on it.
     * The lock handle is stored internally and can be released via unlock().
     *
     * <code>
     * $file = new File('storage/data.json');
     * $file->lock();                    // exclusive lock, wait until acquired
     * $file->lock(exclusive: false);    // shared lock, wait until acquired
     * $file->lock(waitForLock: false);  // exclusive lock, fail immediately if unavailable
     * // ... perform operations ...
     * $file->unlock();
     * </code>
     *
     * @param bool $exclusive   Whether to acquire an exclusive lock (true) or shared lock (false)
     * @param bool $waitForLock Whether to wait for the lock to be acquired or fail immediately
     *
     * @throws FileException If the lock cannot be acquired when waitForLock is false
     * @throws FileException If the file is already locked
     *
     * @see self::unlock()
     */
    public function lock(bool $exclusive = true, bool $waitForLock = false): void
    {
        if ($this->lockHandle !== null) {
            throw FileException::alreadyLocked();
        }

        $this->parent()->mkdir();
        $handle = fopen($this->path, 'c+');

        if ($handle === false) {
            throw FileException::unableToOpen($this->path);
        }

        $operation = $exclusive ? LOCK_EX : LOCK_SH;

        if (!$waitForLock) {
            $operation |= LOCK_NB;
        }

        if (!flock($handle, $operation)) {
            fclose($handle);
            throw FileException::unableToAcquireLock($this->path);
        }

        $this->lockHandle = $handle;
    }

    /**
     * Release the lock on the file
     *
     * Releases the lock acquired by lock() and closes the file handle.
     *
     * <code>
     * $file = new File('storage/data.json');
     * $file->lock();
     * // ... perform operations ...
     * $file->unlock();
     * </code>
     *
     * @throws FileException If no lock is currently held
     *
     * @see self::lock()
     */
    public function unlock(): void
    {
        if ($this->lockHandle === null) {
            throw FileException::notLocked();
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);

        $this->lockHandle = null;
    }
}
