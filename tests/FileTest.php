<?php

namespace Cosmologist\Gears\Tests;

use Cosmologist\Gears\File;
use Cosmologist\Gears\File\Exception\FileException;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testCloneIsForbidden()
    {
        $file = new File('php://temp');

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('__clone');
        clone $file;
    }

    public function testFailedCloneDoesNotCloseOriginalStream()
    {
        $path = tempnam(sys_get_temp_dir(), 'gears-clone-');
        $file = new File($path);

        try {
            $file->fwrite('original');

            try {
                clone $file;
                $this->fail('Expected cloning to be forbidden');
            } catch (\Error $exception) {
                $this->assertStringContainsString('__clone', $exception->getMessage());
            }

            $file->fwrite(':continued');
            $this->assertSame('original:continued', file_get_contents($path));
        } finally {
            unset($file);
            unlink($path);
        }
    }

    public function testFileTypeChecksUseOpenedStreamOnEveryCall()
    {
        stream_wrapper_register('gears-file-test', FileTestStreamWrapper::class);
        FileTestStreamWrapper::$statCalls = 0;
        FileTestStreamWrapper::$mode = 0100644;

        try {
            $file = new File('gears-file-test://data');
            $file->fwrite('contents');

            for ($i = 0; $i < 3; ++$i) {
                $this->assertTrue($file->isFile());
                $this->assertFalse($file->isDirectory());
            }

            $this->assertSame(6, FileTestStreamWrapper::$statCalls);
        } finally {
            unset($file);
            stream_wrapper_unregister('gears-file-test');
        }
    }

    public function testIsFileChecksOpenedFileAfterPathReplacement()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Replacing an open file requires POSIX file semantics');
        }

        $path = tempnam(sys_get_temp_dir(), 'gears-fwrite-');
        $file = new File($path);

        try {
            $file->fwrite('original');
            rename($path, $path . '.original');
            mkdir($path);
            clearstatcache(true, $path);

            $this->assertTrue($file->isFile());
            $this->assertFalse($file->isDirectory());
            $file->fwrite(':modified');
            $this->assertSame('original:modified', file_get_contents($path . '.original'));

            $replacement = new File($path);
            $this->assertFalse($replacement->isFile());
            $this->assertTrue($replacement->isDirectory());
        } finally {
            unset($file);
            if (is_dir($path)) {
                rmdir($path);
            } else {
                unlink($path);
            }
            if (is_file($path . '.original')) {
                unlink($path . '.original');
            }
        }
    }

    public function testFileTypeChecksUsePathWhenNoStreamIsOpen()
    {
        $path = sys_get_temp_dir() . '/gears-file-type-' . bin2hex(random_bytes(8));
        $file = new File($path);
        mkdir($path);

        try {
            $this->assertTrue($file->isDirectory());
            $this->assertFalse($file->isFile());

            rmdir($path);
            file_put_contents($path, 'replacement');
            clearstatcache(true, $path);

            $this->assertFalse($file->isDirectory());
            $this->assertTrue($file->isFile());
        } finally {
            if (is_dir($path)) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }
    }

    public function testFileTypeChecksReturnFalseForUnknownStreamType()
    {
        stream_wrapper_register('gears-file-test', FileTestStreamWrapper::class);
        FileTestStreamWrapper::$mode = 0;

        try {
            $file = new File('gears-file-test://data');
            $file->fwrite('contents');

            $this->assertFalse($file->isFile());
            $this->assertFalse($file->isDirectory());
        } finally {
            unset($file);
            stream_wrapper_unregister('gears-file-test');
        }
    }

    public function testChmodChangesPermissionsAndReturnsSameFile()
    {
        $path = tempnam(sys_get_temp_dir(), 'gears-chmod-');
        $file = new File($path);

        try {
            foreach ([0640, 0600] as $permissions) {
                $this->assertSame($file, $file->chmod($permissions));
                clearstatcache(true, $path);
                $this->assertSame($permissions, fileperms($path) & 0777);
            }
        } finally {
            unlink($path);
        }
    }

    public function testChmodChangesDirectoryPermissions()
    {
        $path = sys_get_temp_dir() . '/gears-chmod-' . bin2hex(random_bytes(8));
        mkdir($path, 0700);

        try {
            (new File($path))->chmod(0750);
            clearstatcache(true, $path);
            $this->assertSame(0750, fileperms($path) & 0777);
        } finally {
            rmdir($path);
        }
    }

    public function testChmodThrowsForMissingPath()
    {
        $path = sys_get_temp_dir() . '/gears-chmod-missing-' . bin2hex(random_bytes(8));

        $this->expectException(FileException::class);
        $this->expectExceptionMessage($path);

        (new File($path))->chmod(0600);
    }
}

class FileTestStreamWrapper
{
    public $context;

    public static int $mode = 0100644;

    public static int $statCalls = 0;

    public function stream_open($path, $mode, $options, &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        return strlen($data);
    }

    public function stream_stat(): array
    {
        ++self::$statCalls;

        return ['mode' => self::$mode];
    }

    public function url_stat($path, $flags): array
    {
        throw new \RuntimeException('Opened streams must not be checked by path');
    }
}
