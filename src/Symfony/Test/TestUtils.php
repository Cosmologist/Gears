<?php

namespace Cosmologist\Gears\Symfony\Test;

use Symfony\Component\BrowserKit\AbstractBrowser;

class TestUtils
{
    /**
     * Add a specified HTTP-header to the kernel-browser request
     *
     * <code>
     * use Cosmologist\Gears\Symfony\Test\TestUtils;
     *
     * class FooTest extends WebTestCase
     * {
     *     protected function testBar(): void
     *     {
     *         $browser = self::createClient();
     *         TestUtils::addHeader($browser, 'User-Agent', 'Symfony KernelBrowser');
     *         ...
     *     }
     * }
     * </code>
     */
    public static function addHeader(AbstractBrowser $client, string $name, string $value): void
    {
        $client->setServerParameter('http-' . $name, $value);
    }
}
