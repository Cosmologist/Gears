<?php

namespace Cosmologist\Gears\Twig;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GearsUtilsExtension extends AbstractExtension
{
    #[Override]
    public function getFilters()
    {
        return [
            new TwigFilter('ceil', 'ceil'),
        ];
    }
}
