<?php

namespace Cosmologist\Gears\Twig;

use Cosmologist\Gears\ArrayType;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GearsUtilsExtension extends AbstractExtension
{
    #[Override]
    public function getFilters()
    {
        return [
            new TwigFilter('ceil', 'ceil'), // required for pagination.html.twig
            new TwigFilter('htmlAttributes', $this->mapToHtmlAttributes(...), ['is_safe' => ['html']])
        ];
    }

    /**
     * A convenient way to render HTML attributes in Twig templates
     *
     * ```
     * {% set linkAttrs = {
     *     'href': 'https://github.com/Cosmologist/Gears',
     *     'class': ['important', 'disabled'],
     *     'style': {'display': 'block', 'color': 'green'},
     *     'data-attribute': 'bar',
     *     'data-json': {'foo': 'bar'}} %}
     *
     * <a{{ linkAttrs | htmlAttributes }}>Click me</a>
     *
     * rendered as:
     * <a href="https://github.com/Cosmologist/Gears"
     *     class="important disabled"
     *     style="display: block; color: green;"
     *     data-attribute="bar"
     *     data-json="{'foo':'bar'}">Click me</a>
     *
     * Use the "merge" filter to manage the attribute dictionary.
     * {% set linkAttrs = linkAttrs | merge({'class': 'disabled'}) %}
     *
     * The "class" attribute may contain null values - they will be filtered out.
     * <a{{ {'class': ['important', null]} | htmlAttributes }}>Click me</a> - <a class="important">Click me</a>
     *
     * Non-unique values of the "class" attribute will be grouped.
     * <a{{ {'class': ['important', 'important', null]} | htmlAttributes }}>Click me</a> - <a class="important">Click me</a>
     *
     * You can pass the "class" attribute as a scalar.
     * <a{{ {'class': 'important'} | htmlAttributes }}>Click me</a> - <a class="important">Click me</a>
     *
     * You can pass the "style" attribute as a scalar.
     * <a{{ {'style': 'display: none'} | htmlAttributes }}>Click me</a> - <a style="display: none">Click me</a>
     *
     * You can pass the "style" attribute as a list.
     * <a{{ {'style': ['display: none', 'color: green']} | htmlAttributes }}>Click me</a> - <a style="display: none; color: green">Click me</a>
     * ```
     *
     * Don't forget to enable this extension
     * ```
     * # config/services.yaml
     * services:
     *     _defaults:
     *         autoconfigure: true
     *
     *     Cosmologist\Gears\Twig\GearsUtilsExtension:
     * ```
     *
     * @see https://github.com/timkelty/htmlattributes-craft/blob/master/htmlattributes/twigextensions/HtmlAttributesTwigExtension.php#L26
     * @see https://github.com/timkelty/htmlattributes-craft
     *
     * @param array $attributes Attributes map
     */
    public function mapToHtmlAttributes(array $attributes): string
    {
        $str = trim(implode(' ', array_map(function ($attrName) use ($attributes) {
            $attrVal = $attributes[$attrName];
            $quote   = '"';
            if (is_null($attrVal) || $attrVal === true) {
                return $attrName;
            } elseif ($attrVal === false) {
                return '';
            } elseif (is_array($attrVal)) {
                switch (strtolower($attrName)) {
                    case 'class':
                        $attrVal = implode(' ', array_unique(array_filter($attrVal)));
                        break;
                    case 'style':
                        if (ArrayType::checkAssoc($attrVal)) {
                            array_walk($attrVal, function (&$val, $key) {
                                $val = $key . ': ' . $val;
                            });
                        }
                        $attrVal = implode('; ', $attrVal) . ';';
                        break;
                    // Default to json, for data-* attributes
                    default:
                        $quote   = '\'';
                        $attrVal = json_encode($attrVal);
                        break;
                }
            } else {
                return $attrName . '="' . htmlspecialchars($attrVal, ENT_COMPAT) . '"';
            }

            return $attrName . '=' . $quote . $attrVal . $quote;
        }, array_keys($attributes))));

        if (strlen($str) > 0) {
            $str = ' ' . $str;
        }

        return $str;
    }
}
