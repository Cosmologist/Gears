<?php

namespace Cosmologist\Gears\Tests;

use Cosmologist\Gears\File;
use Cosmologist\Gears\File\Exception\AppendModeRequiredException;
use Cosmologist\Gears\File\Exception\FileNotOpenException;
use Cosmologist\Gears\File\Exception\OpenFileException;
use Cosmologist\Gears\File\Exception\WriteFileException;
use PHPUnit\Framework\TestCase;

class FileWriteTest extends TestCase
{
    public function testFwriteUsesStoredHandleAndPreservesBytes()
    {
        $path = tempnam(sys_get_temp_dir(), 'gears-fwrite-');
        file_put_contents($path, 'existing:');
        $file = new File($path);
        $handle = $file->fopen('ab');

        try {
            $this->assertSame(5, $file->fwrite("a\0b\r\n"));
            $this->assertSame(3, $file->fwrite('end'));
            fflush($handle);
            $this->assertSame("existing:a\0b\r\nend", file_get_contents($path));
        } finally {
            fclose($handle);
            unlink($path);
        }
    }

    public function testFappendLineCompletesPartialWritesWithoutAttemptLimit()
    {
        $this->withPartialStream(function (File $file): void {
            $data = str_repeat('ab', 32);
            $this->assertSame(strlen($data . PHP_EOL), $file->fappendLine($data));
            $this->assertSame($data . PHP_EOL, FileWriteStream::$data);
            $this->assertSame(1, FileWriteStream::$openCalls);
        }, 'ab');
    }

    public function testFwriteReportsFailureAfterPartialWriteAndKeepsWrittenPrefix()
    {
        $this->withPartialStream(function (File $file): void {
            try {
                FileWriteStream::$stopAfter = 4;
                $file->fwrite('abcdef');
                $this->fail('Expected zero progress after a partial write to fail');
            } catch (WriteFileException $exception) {
                $this->assertStringContainsString('4 of 6 bytes', $exception->getMessage());
                $this->assertSame('abcd', FileWriteStream::$data);
            }
        });
    }

    public function testFwriteStopsImmediatelyWhenNoProgressIsMade()
    {
        $this->withPartialStream(function (File $file): void {
            FileWriteStream::$stalled = true;

            try {
                $file->fwrite('abcdef');
                $this->fail('Expected zero progress to fail');
            } catch (WriteFileException $exception) {
                $this->assertStringContainsString('0 of 6 bytes', $exception->getMessage());
                $this->assertSame(1, FileWriteStream::$writeCalls);
            }
        });
    }

    public function testFwriteEmptyDataMakesNoWriteCalls()
    {
        $this->withPartialStream(function (File $file): void {
            $this->assertSame(0, $file->fwrite(''));
            $this->assertSame(0, FileWriteStream::$writeCalls);
        });
    }

    public function testFappendCreatesFileWhenNoHandleIsStored()
    {
        $path = sys_get_temp_dir() . '/gears-fwrite-' . bin2hex(random_bytes(8));
        $file = new File($path);

        try {
            $this->assertSame(4, $file->fappend('data'));
            unset($file);
            $this->assertSame('data', file_get_contents($path));
        } finally {
            unset($file);
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testFappendReopensClosedHandleInAppendMode()
    {
        $path = tempnam(sys_get_temp_dir(), 'gears-fwrite-');
        $file = new File($path);
        $handle = $file->fopen('wb');
        fwrite($handle, 'existing:');
        fclose($handle);

        try {
            $this->assertSame(4, $file->fappend('data'));
            unset($file);
            $this->assertSame('existing:data', file_get_contents($path));
        } finally {
            unset($file);
            unlink($path);
        }
    }

    public function testFappendReportsAutomaticOpenFailure()
    {
        $path = sys_get_temp_dir() . '/gears-fwrite-missing-' . bin2hex(random_bytes(8)) . '/data';

        $this->expectException(OpenFileException::class);
        (new File($path))->fappend('data');
    }

    public function testFwriteRejectsMissingHandleWithoutCreatingFile()
    {
        $path = sys_get_temp_dir() . '/gears-fwrite-' . bin2hex(random_bytes(8));

        try {
            $this->expectException(FileNotOpenException::class);
            $this->expectExceptionMessage("File '{$path}' has no open handle");
            (new File($path))->fwrite('data');
        } finally {
            $this->assertFileDoesNotExist($path);
        }
    }

    public function testFwriteRejectsClosedHandle()
    {
        $file = new File('php://temp');
        $handle = $file->fopen('w+b');
        fclose($handle);

        $this->expectException(FileNotOpenException::class);
        $this->expectExceptionMessage("File 'php://temp' has no open handle");
        $file->fwrite('data');
    }

    public function testFwriteUsesCurrentPositionOfNonAppendHandle()
    {
        $file = new File('php://temp');
        $handle = $file->fopen('w+b');

        try {
            fwrite($handle, 'abcd');
            rewind($handle);
            $this->assertSame(2, $file->fwrite('XY'));
            rewind($handle);
            $this->assertSame('XYcd', stream_get_contents($handle));
        } finally {
            fclose($handle);
        }
    }

    public function testFappendRejectsNonAppendHandleWithoutChangingIt()
    {
        $file = new File('php://temp');
        $handle = $file->fopen('w+b');
        fwrite($handle, 'abcd');
        rewind($handle);

        try {
            $this->expectException(AppendModeRequiredException::class);
            $this->expectExceptionMessage("handle mode 'w+b' is not an append mode");
            $file->fappend('XY');
        } finally {
            $this->assertSame(0, ftell($handle));
            $this->assertSame('abcd', stream_get_contents($handle));
            fclose($handle);
        }
    }

    public function testFappendLazilyOpensStreamOnceAcrossMultipleWrites()
    {
        $this->withPartialStream(function (File $file): void {
            FileWriteStream::$partial = false;
            $this->assertSame(0, FileWriteStream::$openCalls);
            $this->assertSame(3, $file->fappend('abc'));
            $this->assertSame(3, $file->fappend('def'));
            $this->assertSame(1, FileWriteStream::$openCalls);
            $this->assertSame('abcdef', FileWriteStream::$data);
        }, null);
    }

    public function testFappendUsesAppendModeAfterRewind()
    {
        $path = tempnam(sys_get_temp_dir(), 'gears-fappend-');
        file_put_contents($path, 'existing:');
        $file = new File($path);
        $handle = $file->fopen('a+b');
        rewind($handle);

        try {
            $this->assertSame(4, $file->fappend('data'));
            fflush($handle);
            $this->assertSame('existing:data', file_get_contents($path));
        } finally {
            fclose($handle);
            unlink($path);
        }
    }

    public function testFwriteRejectsReadOnlyHandle()
    {
        $path = tempnam(sys_get_temp_dir(), 'gears-fwrite-');
        $file = new File($path);
        $handle = $file->fopen('rb');

        try {
            $this->expectException(WriteFileException::class);
            $file->fwrite('data');
        } finally {
            fclose($handle);
            unlink($path);
        }
    }

    private function withPartialStream(callable $test, ?string $mode = 'wb'): void
    {
        stream_wrapper_register('gears-file-write-test', FileWriteStream::class);
        FileWriteStream::$data = '';
        FileWriteStream::$openCalls = 0;
        FileWriteStream::$writeCalls = 0;
        FileWriteStream::$stalled = false;
        FileWriteStream::$stopAfter = null;
        FileWriteStream::$partial = true;

        try {
            $file = new File('gears-file-write-test://data');
            if ($mode !== null) {
                $file->fopen($mode);
            }

            try {
                $test($file);
            } finally {
                unset($file);
            }
        } finally {
            stream_wrapper_unregister('gears-file-write-test');
        }
    }
}

class FileWriteStream
{
    public $context;

    public static string $data = '';
    public static int $openCalls = 0;
    public static int $writeCalls = 0;
    public static bool $stalled = false;
    public static ?int $stopAfter = null;
    public static bool $partial = true;

    public function stream_open($path, $mode, $options, &$openedPath): bool
    {
        ++self::$openCalls;

        return true;
    }

    public function stream_eof(): bool
    {
        return false;
    }

    public function stream_write(string $data): int
    {
        ++self::$writeCalls;

        // PHP may call stream_write() repeatedly within one native fwrite().
        // Returning zero after each chunk makes that fwrite() return a short count.
        if (self::$stalled || (self::$partial && self::$writeCalls % 2 === 0)
            || (self::$stopAfter !== null && strlen(self::$data) >= self::$stopAfter)) {
            return 0;
        }

        $chunk = self::$partial ? substr($data, 0, 2) : $data;
        self::$data .= $chunk;

        return strlen($chunk);
    }
}
