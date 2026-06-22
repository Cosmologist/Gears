<?php

namespace Cosmologist\Gears;

use FilesystemIterator;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use GuzzleHttp;

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
     * @todo
     */
    public function absolute(): string
    {
        return FileType::isAbsolutePath($this->path) ? $this->path : getcwd() . DIRECTORY_SEPARATOR . $this->path;
    }

    /**
     * @todo
     */
    function uri(): string
    {
        $path = $this->absolute();
        $path = str_replace('\\', '/', $path);

        $parts        = explode('/', $path);
        $encodedParts = array_map('rawurlencode', $parts);
        $encodedPath  = implode('/', $encodedParts);

        if (strpos($encodedPath, '/') !== 0) {
            $encodedPath = '/' . $encodedPath;
        }

        return 'file://' . $encodedPath;
    }

    /**
     * Get the base name of the file
     *
     * @return string The file name with leading directory paths removed
     */
    public function basename(): string
    {
        return pathinfo($this->path, PATHINFO_BASENAME);
    }

    /**
     * @todo
     */
    public function filename(): string
    {
        return pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Get the file extension
     *
     * @param  bool  $lowercase  Return extension in lowercase
     *
     * @return string|null The extension without the leading dot, or null if none exists
     */
    public function extension(bool $lowercase = true): string|null
    {
        $ext = pathinfo($this->path, PATHINFO_EXTENSION);

        if ($ext === '') {
            return null;
        }

        return $lowercase ? strtolower($ext) : $ext;
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
     * @param  string  $name  The name of the child file or directory
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
     * Check if the file is a directory
     *
     * @return bool True if the path is a directory, false otherwise
     */
    public function isDirectory(): bool
    {
        return is_dir($this->path);
    }

    /**
     * @todo
     */
    public function isFile(): bool
    {
        return is_file($this->path);
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
     * Assert that the path exists and points to a regular file
     *
     * @throws FileException If the path does not exist or is not a regular file
     */
    public function assertFile(): void
    {
        $this->assertExists();

        if (!$this->isFile()) {
            throw FileException::notFile($this->path);
        }
    }

    /**
     * Rename the file and return a new File instance
     *
     * @param  string  $newName  The new filename (without path)
     *
     * @return File New File instance with the new path
     *
     * @throws FileException If unable to rename the file
     */
    public function rename(string $newName): File
    {
        $newFile = $this->parent()->child($newName);

        if (!rename($this->path, $newFile->path)) {
            throw FileException::unableToOpen($this->path);
        }

        return $newFile;
    }

    /**
     * Compare file extension with MIME type and rename if needed
     *
     * @return File New File instance if renamed, current File otherwise
     */
    public function fixExtension(): File
    {
        $currentExt = $this->extension();
        $guessedExt = FileType::guessExtension($this->path);

        if ($currentExt === $guessedExt || $guessedExt === null) {
            return $this;
        }

        $newName = pathinfo($this->basename(), PATHINFO_FILENAME) . '.' . $guessedExt;

        return $this->rename($newName);
    }

    /**
     * List directory contents with recursion support
     *
     * @return iterable<File> A generator of File objects representing the children
     */
    public function list(bool $recursive = false): iterable
    {
        $iterator = $recursive
            ? new \RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS)
            : new \DirectoryIterator($this->path);

        foreach ($iterator as $fileInfo) {
            yield new self($fileInfo->getPathname());
        }
    }

    /**
     * Iterate over directory contents and pass each File to callback
     *
     * @param  callable(File):void  $callback
     */
    public function iterate(callable $callback, bool $recursive = false): void
    {
        foreach ($this->list($recursive) as $file) {
            $callback($file);
        }
    }

    /**
     * Create the directory if it doesn't exist
     *
     * @return $this
     */
    public function mkdir(): self
    {
        if (!file_exists($this->path)) {
            mkdir($this->path, recursive: true);
        }

        return $this;
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
     * @param  mixed  $data  The data to write
     *
     * @return $this
     */
    public function put(mixed $data): self
    {
        $this->parent()->mkdir();
        file_put_contents($this->path, $data);

        return $this;
    }

    /**
     * Download file from URL and save to current path
     *
     * @param  string  $url                     HTTP URL to download from
     * @param  GuzzleHttp\Client|null  $guzzle  Guzzle HTTP-client - if passed, will be used to get the contents of the URL
     */
    public function putFromUrl(string $url, ?GuzzleHttp\Client $guzzle = null): self
    {
        if ($guzzle !== null) {
            $response = $guzzle->get($url);
            $content  = $response->getBody()->getContents();
        } else {
            $content = file_get_contents($url);

            if ($content === false) {
                throw new \RuntimeException("Failed to download from {$url}");
            }
        }

        return $this->put($content);
    }

    /**
     * Get the MIME type of the file
     *
     * @return string The MIME type (e.g., text/plain, image/jpeg)
     */
    public function mime(): string
    {
        return FileType::guessMime($this->path);
    }

    /**
     * Returns file contents as base64 string with data URI prefix.
     *
     * Uses finfo to detect actual MIME type from file content.
     *
     * <code>
     * $file = new File('image.jpg');
     * $base64 = $file->toBase64(); // data:image/jpeg;base64,/9j/4AAQ...
     * </code>
     *
     * @return string Base64 string with prefix (e.g., data:image/jpeg;base64,...)
     */
    public function toBase64(): string
    {
        $mimeType = FileType::guessMime($this->path) ?? 'application/octet-stream';

        return 'data:' . $mimeType . ';base64,' . base64_encode($this->get());
    }

    /**
     * Start a one-time HTTP server and serve the file once by secret URL
     *
     * The server starts on the given IP and port, generates a unique URL with a hash,
     * passes that URL to the callback, then waits until the matching HTTP request arrives.
     * After the file is fully sent, the server stops and the method returns.
     *
     * <code>
     * $file = new File('storage/report.pdf');
     * $file->serve(function (string $url) {
     *     // send URL somewhere
     * });
     * </code>
     *
     * @param  callable(string):void  $urlRecipient
     * @param  string  $ip
     * @param  int  $port
     *
     * @throws FileException If the target does not exist, is not a regular file, or server startup fails
     */
    public function serve(callable $urlRecipient, string $ip = '0.0.0.0', int $port = 0): void
    {
        Network::serve($this, $urlRecipient, $ip, $port);
    }

    /**
     * Acquire a lock on the file
     *
     * Opens the file and acquires a lock on it.
     * The lock handle is stored internally and can be released via unlock().
     *
     * Reentrant locking is enabled by default: calling lock() multiple times within
     * the same process will succeed without blocking or throwing an exception.
     *
     * <code>
     * $file = new File('storage/data.json');
     * $file->lock();                    // exclusive lock, wait until acquired
     * $file->lock(exclusive: false);    // shared lock, wait until acquired
     * $file->lock(waitForLock: false);  // exclusive lock, fail immediately if unavailable
     * $file->lock(reentrant: false);    // throw exception if already locked
     * // ... perform operations ...
     * $file->unlock();
     * </code>
     *
     * @param  bool  $exclusive  Whether to acquire an exclusive lock (true) or shared lock (false)
     * @param  bool  $waitForLock  Whether to wait for the lock to be acquired or fail immediately
     * @param  bool  $reentrant  Whether to allow reentrant locking (skip if already locked in same process)
     *
     * @return $this
     *
     * @throws FileException If the file is already locked and reentrant is false
     *
     * @throws FileException If the lock cannot be acquired when waitForLock is false
     * @see self::unlock()
     */
    public function lock(bool $exclusive = true, bool $waitForLock = false, bool $reentrant = true): self
    {
        if ($this->lockHandle !== null && !$reentrant) {
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

        return $this;
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
     * @param  bool  $throwOnError  Whether to throw exception on errors (true) or silently ignore (false)
     *
     * @return $this
     *
     * @throws FileException If flock() fails and throwOnError is true
     *
     * @throws FileException If no lock is currently held and throwOnError is true
     * @see self::lock()
     */
    public function unlock(bool $throwOnError = false): self
    {
        if ($this->lockHandle === null) {
            if ($throwOnError) {
                throw FileException::notLocked();
            }

            return $this;
        }

        $released = flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;

        if (!$released && $throwOnError) {
            throw FileException::unableToReleaseLock($this->path);
        }

        return $this;
    }

    /**
     * Serializes data and immediately writes it to the file corresponding to the object
     *
     * <code>
     * $storage = new File('storage/abc.json');
     * $storage->serialize($fooObject);
     * </code>
     *
     * @param  array|T  $data
     *
     * @return $this
     */
    public function serialize(array|object $data): self
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $json       = $serializer->serialize($data, 'json');
        $this->put($json);

        return $this;
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
        $extractors            = [new PhpDocExtractor(), new ReflectionExtractor()];
        $propertyInfoExtractor = new PropertyInfoExtractor($extractors, $extractors);
        $objectNormalizer      = new ObjectNormalizer(null, null, null, $propertyInfoExtractor);
        $serializer            = new Serializer([$objectNormalizer, new ArrayDenormalizer()], [new JsonEncoder()]);
        $data                  = file_get_contents($this->path);

        return ($class === null)
            ? $serializer->decode($data, 'json')
            : $serializer->deserialize($data, $class, 'json');
    }

    /**
     * Delete the file or directory
     *
     * @param  bool  $recursive  Whether to delete directories recursively (true) or throw exception (false)
     */
    public function delete(bool $recursive = false): self
    {
        if (!$this->exists()) {
            return $this;
        }

        if ($this->isDirectory()) {
            $this->iterate(fn(File $child) => $child->delete($recursive), $recursive);
            rmdir($this->path);
        } else {
            unlink($this->path);
        }

        return $this;
    }
}
