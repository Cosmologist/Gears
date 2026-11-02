<?php

namespace Cosmologist\Gears\Tests;

use Cosmologist\Gears\File;
use Cosmologist\Gears\FileException;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testFopenUsesNativeMode()
    {
        $handle = (new File('php://temp'))->fopen('w+b');

        try {
            $this->assertIsResource($handle);
            fwrite($handle, "contents\r\n");
            rewind($handle);
            $this->assertSame("contents\r\n", stream_get_contents($handle));
        } finally {
            fclose($handle);
        }
    }

    public function testFopenPassesContextAndCachesStreamType()
    {
        stream_wrapper_register('gears-file-test', FileTestStreamWrapper::class);
        FileTestStreamWrapper::$statCalls = 0;

        try {
            $context = stream_context_create(['gears' => ['option' => 'value']]);
            $file    = new File('gears-file-test://data');
            $handle  = $file->fopen('rb', context: $context);

            try {
                $this->assertSame(stream_context_get_options($context), FileTestStreamWrapper::$options);

                for ($i = 0; $i < 3; ++$i) {
                    $this->assertTrue($file->isFile());
                    $this->assertFalse($file->isDirectory());
                }

                $this->assertSame(1, FileTestStreamWrapper::$statCalls);
            } finally {
                fclose($handle);
            }
        } finally {
            stream_wrapper_unregister('gears-file-test');
        }
    }

    public function testFopenUsesIncludePath()
    {
        $directory   = sys_get_temp_dir() . '/gears-fopen-' . bin2hex(random_bytes(8));
        $includePath = get_include_path();
        mkdir($directory);
        file_put_contents($directory . '/data.txt', 'contents');

        try {
            set_include_path($directory);
            $file   = new File('data.txt');
            $handle = $file->fopen('rb', useIncludePath: true);

            try {
                $this->assertTrue($file->isFile());
                $this->assertFalse($file->isDirectory());
                $this->assertSame('contents', stream_get_contents($handle));
            } finally {
                fclose($handle);
            }
        } finally {
            set_include_path($includePath);
            unlink($directory . '/data.txt');
            rmdir($directory);
        }
    }

    public function testFopenThrowsForMissingParentWithoutCreatingIt()
    {
        $directory = sys_get_temp_dir() . '/gears-fopen-missing-' . bin2hex(random_bytes(8));
        $path      = $directory . '/data.txt';

        try {
            $this->expectException(FileException::class);
            $this->expectExceptionMessage("Unable to open file '{$path}' in mode 'wb'");

            @(new File($path))->fopen('wb');
        } finally {
            $this->assertDirectoryDoesNotExist($directory);
        }
    }

    public function testIsFileChecksOpenedFileAfterPathReplacement()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Replacing an open file requires POSIX file semantics');
        }

        $path = tempnam(sys_get_temp_dir(), 'gears-fopen-');
        $file = new File($path);
        file_put_contents($path, 'original');
        $handle = $file->fopen('r+b');

        try {
            rename($path, $path . '.original');
            mkdir($path);
            clearstatcache(true, $path);

            $this->assertTrue($file->isFile());
            $this->assertFalse($file->isDirectory());
            $this->assertSame(0, ftell($handle));
            $this->assertSame('original', stream_get_contents($handle));
            rewind($handle);
            fwrite($handle, 'modified');
            fflush($handle);
            $this->assertSame('modified', file_get_contents($path . '.original'));

            fclose($handle);
            $this->assertFalse($file->isFile());
            $this->assertTrue($file->isDirectory());
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
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

    public function testIsDirectoryChecksOpenedDirectoryAndRefreshesTypeOnReopen()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Native fopen() cannot open directories on Windows');
        }

        $path = sys_get_temp_dir() . '/gears-fopen-directory-' . bin2hex(random_bytes(8));
        $file = new File($path);
        mkdir($path);
        $handle = $file->fopen('rb');

        try {
            rename($path, $path . '.original');
            file_put_contents($path, 'replacement');
            clearstatcache(true, $path);

            $this->assertTrue($file->isDirectory());
            $this->assertFalse($file->isFile());

            $newHandle = $file->fopen('rb');

            try {
                $this->assertIsResource($handle);
                $this->assertTrue($file->isFile());
                $this->assertFalse($file->isDirectory());
                $this->assertSame('replacement', stream_get_contents($newHandle));
            } finally {
                fclose($newHandle);
            }
        } finally {
            fclose($handle);
            if (is_dir($path)) {
                rmdir($path);
            } else {
                unlink($path);
            }
            if (is_dir($path . '.original')) {
                rmdir($path . '.original');
            }
        }
    }

    public function testFopenSucceedsWhenStreamTypeIsUnavailable()
    {
        $file   = new File('php://input');
        $handle = $file->fopen('rb');

        try {
            $this->assertIsResource($handle);
            $this->assertFalse($file->isFile());
            $this->assertFalse($file->isDirectory());
        } finally {
            fclose($handle);
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

    public static array $options = [];

    public static int $statCalls = 0;

    public function stream_open($path, $mode, $options, &$openedPath): bool
    {
        self::$options = stream_context_get_options($this->context);

        return true;
    }

    public function stream_stat(): array
    {
        ++self::$statCalls;

        return ['mode' => 0100644];
    }

    public function url_stat($path, $flags): array
    {
        throw new \RuntimeException('Opened streams must not be checked by path');
    }
}
