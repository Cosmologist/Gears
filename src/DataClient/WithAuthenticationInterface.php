<?php
namespace Cosmologist\Gears\DataClient;

use Psr\Http\Message\ResponseInterface;

interface WithAuthenticationInterface
{
    public function auth(string $email, string $password): void;
    public function isUnauthorized(ResponseInterface $response): bool;
}
