<?php

namespace Cosmologist\Gears\Tests;

use Cosmologist\Gears\StringType;
use PHPUnit\Framework\TestCase;

class StringTypeTest extends TestCase
{
    public function testWords()
    {
        $this->assertSame(StringType::words('Roland'), ['Roland']);
        $this->assertSame(StringType::words('Roland TB303'), ['Roland', 'TB303']);
        $this->assertSame(StringType::words('Roland TB-303'), ['Roland', 'TB-303']);
        $this->assertSame(StringType::words('Roland TB-303.'), ['Roland', 'TB-303']);
        $this->assertSame(StringType::words('Roland TB-303â'), ['Roland', 'TB-303â']);
        $this->assertSame(StringType::words('âRoland TB-303'), ['âRoland', 'TB-303']);
        $this->assertSame(StringType::words('Roland - TB303'), ['Roland', 'TB303']);
        $this->assertSame(StringType::words("Roland'â - TB303"), ["Roland'â", 'TB303']);
        $this->assertSame(StringType::words('"Roland" - TB303'), ['Roland', 'TB303']);
        $this->assertSame(StringType::words('R.O.L.A.N.D. - TB303'), ['R.O.L.A.N.D', 'TB303']);
    }
}
