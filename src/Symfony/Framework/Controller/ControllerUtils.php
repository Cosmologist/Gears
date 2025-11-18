<?php

namespace Cosmologist\Gears\Symfony\Framework\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

readonly class ControllerUtils
{
    public function __construct(private HttpKernelInterface $httpKernel)
    {
    }

    /**
     * Forwards the request to another controller that matches the passed URI.
     *
     * Like {@link Symfony\Bundle\FrameworkBundle\Controller\Controller::forward}, but for URIs.
     *
     * ```
     * ControllerUtils::forwardByUri('/blog/my-post');
     * ```
     */
    public function forwardByUri(string $uri): Response
    {
        return $this->httpKernel->handle(Request::create($uri), HttpKernelInterface::SUB_REQUEST);
    }
}
