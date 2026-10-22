<?php

namespace Cosmologist\Gears\Tests;

use Cosmologist\Gears\File;
use Cosmologist\Gears\FileException;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
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
