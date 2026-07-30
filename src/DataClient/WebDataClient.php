<?php
namespace Cosmologist\Gears\DataClient;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

abstract class WebDataClient implements WebDataClientInterface
{
    public function __construct(
        protected ClientInterface $client,
        protected RequestFactoryInterface $requestFactory,
        protected StreamFactoryInterface $streamFactory
    ) {}

    public function fetch(RequestInterface $request): string
    {
        $response = $this->request($request);
        return (string) $response->getBody();
    }

    public function fetchBy(string $uri): string
    {
        return $this->fetch($this->createRequest('GET', $uri));
    }

    protected function createRequest(string $method, string $uri): RequestInterface
    {
        return $this->requestFactory->createRequest($method, $uri)
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36')
            ->withHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->withHeader('Accept-Encoding', 'gzip, deflate, br')
            ->withHeader('Upgrade-Insecure-Requests', '1')
            ->withHeader('Sec-Fetch-Dest', 'document')
            ->withHeader('Sec-Fetch-Mode', 'navigate')
            ->withHeader('Sec-Fetch-Site', 'none')
            ->withHeader('Sec-Fetch-User', '?1')
            ->withHeader('Cache-Control', 'max-age=0');
    }

    protected function request(RequestInterface $request): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('HTTP Request failed with status ' . $response->getStatusCode());
        }
        return $response;
    }
}
