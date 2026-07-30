<?php
namespace Cosmologist\Gears\DataClient;

use Psr\Http\Message\ResponseInterface;

interface WithAntifraudInterface
{
    public function isAntifraudTriggered(ResponseInterface $response): bool;
}
