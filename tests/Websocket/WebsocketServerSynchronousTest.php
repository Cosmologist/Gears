<?php

namespace Cosmologist\Gears\Tests\Websocket;

use Cosmologist\Gears\Websocket\WebsocketServerSynchronous;
use PHPUnit\Framework\TestCase;

class WebsocketServerSynchronousTest extends TestCase
{
    public function testEcho()
    {
        $port = 8082;
        $mockScript = __DIR__ . '/ws-echo.py';
        
        $cmd = sprintf('sleep 0.5 && python3 %s %d > /dev/null 2>&1 & echo $!', escapeshellarg($mockScript), $port);
        $pid = trim(shell_exec($cmd));
        
        try {
            $server = new WebsocketServerSynchronous();
            $server->listen($port, 5);
            
            $server->send('hello');
            $this->assertEquals('echo: hello', $server->receive());
            
            $server->send('world');
            $this->assertEquals('echo: world', $server->receive());
            
            $server->send('stop');
            $server->disconnect();
        } finally {
            if ($pid) {
                exec(sprintf('kill -9 %s > /dev/null 2>&1', escapeshellarg($pid)));
            }
        }
    }
}
