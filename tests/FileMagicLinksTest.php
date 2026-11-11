<?php

namespace Cosmologist\Gears\Tests;

use Composer\Autoload\ClassLoader;
use Cosmologist\Gears\File;
use Cosmologist\Gears\File\Exception\LinkDepthExceededException;
use Cosmologist\Gears\File\Exception\MagicLinuxLinksUnsupportedException;
use Cosmologist\Gears\File\Exception\OpenFileException;
use Cosmologist\Gears\File\Exception\ReadLinkException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FileMagicLinksTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        if (PHP_OS_FAMILY !== 'Linux' || !is_dir('/proc/self/fd')) {
            $this->markTestSkipped('Linux procfs FD links are required');
        }

        $this->directory = sys_get_temp_dir() . '/gears-magic-links-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        if (!isset($this->directory)) {
            return;
        }

        foreach (glob($this->directory . '/*') as $path) {
            if (is_dir($path) && !is_link($path)) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($this->directory);
    }

    public function testPipeFdAliasesThrowTheSpecificException(): void
    {
        foreach (['/dev/stdout', '/dev/stderr', '/dev/fd/2', '/proc/self/fd/2'] as $path) {
            [$result, $stderr] = $this->openInChild($path);

            $this->assertSame(MagicLinuxLinksUnsupportedException::class, $result['exception'], $path);
            $this->assertStringContainsString($path, $result['message']);
            $this->assertStringContainsString('pipe:[', $result['message']);
            $this->assertStringContainsString('php://fd/N', $result['message']);
            $this->assertSame('', $stderr);
        }
    }

    public function testRelativeSymlinkChainToPipeIsDiagnosed(): void
    {
        symlink('/dev/stderr', $this->directory . '/inner');
        symlink('inner', $this->directory . '/outer');

        [$result] = $this->openInChild($this->directory . '/outer');

        $this->assertSame(MagicLinuxLinksUnsupportedException::class, $result['exception']);
    }

    public function testFdRedirectedToRegularFileStillOpens(): void
    {
        $log = $this->directory . '/stderr.log';
        [$result] = $this->openInChild('/dev/stderr', $log);

        $this->assertNull($result['exception']);
        $this->assertSame("probe\n", file_get_contents($log));
    }

    public function testPhpWrapperStillWritesToPipe(): void
    {
        [$result, $stderr] = $this->openInChild('php://stderr');

        $this->assertNull($result['exception']);
        $this->assertSame("probe\n", $stderr);
    }

    public function testOrdinarySymlinkStillOpens(): void
    {
        $path = $this->directory . '/target';
        touch($path);
        symlink('target', $this->directory . '/ordinary-link');

        [$result] = $this->openInChild($this->directory . '/ordinary-link');

        $this->assertNull($result['exception']);
        $this->assertSame("probe\n", file_get_contents($path));
    }

    public function testMissingParentKeepsGenericException(): void
    {
        [$result] = $this->openInChild($this->directory . '/missing/file');

        $this->assertSame(OpenFileException::class, $result['exception']);
    }

    public function testCyclicSymlinksThrowDepthException(): void
    {
        symlink('second', $this->directory . '/first');
        symlink('first', $this->directory . '/second');

        [$result] = $this->openInChild($this->directory . '/first');

        $this->assertSame(LinkDepthExceededException::class, $result['exception']);
        $this->assertNull($result['previous']);
    }

    public function testResolveThrowsForPipeTarget(): void
    {
        $path = $this->directory . '/pipe-link';
        symlink('pipe:[123]', $path);

        $this->expectException(MagicLinuxLinksUnsupportedException::class);
        $this->expectExceptionMessage('pipe:[123]');
        $this->resolve(new File($path));
    }

    public function testResolveReturnsAnUnlinkedPathUnchanged(): void
    {
        $path = $this->directory . '/missing';

        $this->assertSame($path, $this->resolve(new File($path)));
    }

    public function testResolveFollowsRelativeAndAbsoluteTargets(): void
    {
        $path = $this->directory . '/target';
        touch($path);
        symlink($path, $this->directory . '/absolute');
        symlink('absolute', $this->directory . '/relative');

        $this->assertSame($path, $this->resolve(new File($this->directory . '/relative')));
    }

    public function testResolveAllowsTheDepthLimitButRejectsAnotherLink(): void
    {
        $path = $this->directory . '/target';
        touch($path);
        $target = $path;
        for ($depth = 1; $depth <= 6; $depth++) {
            $link = $this->directory . '/link-' . $depth;
            symlink(basename($target), $link);
            $target = $link;
        }

        $this->assertSame($path, $this->resolve(new File($this->directory . '/link-5')));
        $this->expectException(LinkDepthExceededException::class);
        $this->resolve(new File($this->directory . '/link-6'));
    }

    public function testOpeningRejectsSixLinksBeforeTruncatingTheFile(): void
    {
        $path = $this->directory . '/target';
        file_put_contents($path, 'keep');
        $target = $path;
        for ($depth = 1; $depth <= 6; $depth++) {
            $link = $this->directory . '/link-' . $depth;
            symlink(basename($target), $link);
            $target = $link;
        }

        try {
            $this->expectException(LinkDepthExceededException::class);
            (new \ReflectionMethod(File::class, 'fopen'))->invoke(new File($target), 'wb');
        } finally {
            $this->assertSame('keep', file_get_contents($path));
        }
    }

    private function resolve(File $file): string
    {
        return (new \ReflectionMethod(File::class, 'resolve'))->invoke($file);
    }

    /**
     * Use actual child-process pipes rather than inferring behavior from the test runner's descriptors.
     * Catch FileException to inspect errors propagated directly by the resolver.
     */
    private function openInChild(string $path, ?string $stderrFile = null): array
    {
        $script = <<<'PHP'
require_once $argv[1];
foreach (array_slice($argv, 2, -1) as $source) {
    require_once $source;
}
try {
    (new \Cosmologist\Gears\File($argv[array_key_last($argv)]))->fwrite("probe\n");
    echo json_encode(['exception' => null]);
} catch (\Cosmologist\Gears\File\Exception\FileException $exception) {
    echo json_encode([
        'exception' => $exception::class,
        'message' => $exception->getMessage(),
        'previous' => $exception->getPrevious() === null ? null : $exception->getPrevious()::class,
    ]);
}
PHP;
        $autoload = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2) . '/autoload.php';
        $command = [PHP_BINARY, '-r', $script, $autoload];
        foreach ([OpenFileException::class, ReadLinkException::class, LinkDepthExceededException::class,
            MagicLinuxLinksUnsupportedException::class, File::class] as $class) {
            $command[] = (new ReflectionClass($class))->getFileName();
        }
        $command[] = $path;

        $process = proc_open($command, [
            0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'],
            2 => $stderrFile === null ? ['pipe', 'w'] : ['file', $stderrFile, 'w'],
        ], $pipes);
        $this->assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = isset($pipes[2]) ? stream_get_contents($pipes[2]) : '';
        if (isset($pipes[2])) {
            fclose($pipes[2]);
        }
        $this->assertSame(0, proc_close($process), $stdout . $stderr);

        return [json_decode($stdout, true, flags: JSON_THROW_ON_ERROR), $stderr];
    }
}
