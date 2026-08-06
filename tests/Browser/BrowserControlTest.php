<?php

namespace Cosmologist\Gears\Tests\Browser;

use Cosmologist\Gears\Browser\BrowserControl;
use Cosmologist\Gears\Browser\Exception\BrowserControlException;
use PHPUnit\Framework\TestCase;

class BrowserControlTest extends TestCase
{
    public function testBrowserControl()
    {
        $port = 8081; // Use a different port to avoid conflicts
        
        // Start the python mock extension in the background
        $mockScript = __DIR__ . '/ext-mock.py';
        
        // Wait briefly for the server to be ready before starting the client?
        // Actually, BrowserControl listen() is blocking. So we need to start the mock extension with a slight delay
        // in the background, then call listen() immediately in PHP.
        
        $cmd = sprintf('sleep 1 && python3 %s > /dev/null 2>&1 &', escapeshellarg($mockScript));
        // We will change the port in the python script dynamically or just hardcode it here.
        // Let's modify the python script to accept a port, or just run it as is since it uses 8080.
        // I will use 8080.
        $port = 8080;
        $cmd = sprintf('sleep 0.5 && python3 %s > /dev/null 2>&1 & echo $!', escapeshellarg($mockScript));
        $pid = trim(shell_exec($cmd));
        
        try {
            $browser = new BrowserControl();
            $browser->listen($port, 5); // 5 seconds timeout
            
            $browser->navigate('http://example.com');
            
            $html = $browser->getHtml();
            $this->assertEquals('<html><body>mock</body></html>', $html);
            
            $value = $browser->execute('return 42;');
            $this->assertEquals(42, $value);
            
            $items = $browser->select('#test');
            $this->assertCount(1, $items);
            $this->assertEquals("<div id='test'></div>", $items[0]);
            
            $browser->disconnect();
        } finally {
            if ($pid) {
                // Ensure the background process is killed
                exec(sprintf('kill -9 %s > /dev/null 2>&1', escapeshellarg($pid)));
            }
        }
    }
}
