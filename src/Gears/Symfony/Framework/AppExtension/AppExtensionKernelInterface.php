<?php

namespace Cosmologist\Gears\Symfony\Framework\AppExtension;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

interface AppExtensionKernelInterface
{
    public function getAppExtension(): ExtensionInterface;
}
