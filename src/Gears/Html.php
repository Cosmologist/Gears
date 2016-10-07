<?php

namespace Cosmologist\Gears;

use DOMDocument;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Collection of commonly used methods for working with HTML
 */
class Html
{
    /**
     * Paragraph tag name
     */
    const TAG_PARAGRAPH = 'p';

    /**
     * Truncates html with tags preserving
     *
     * HTML truncating based on the roots html elements.
     * The algorithm collects the roots html elements, when text length of collected elements reach the limit,
     * then left elements will be truncated
     *
     * @param string $html   Html to truncate
     * @param int    $limit  Length of returned string
     * @param string $ending Will be used as ending and appended to the trimmed string
     *
     * @return string Truncated HTML
     */
    public static function truncate($html, $limit = 1000, $ending = '')
    {
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);

        // Passed html content automatically wrapped by DomDocument with html and body tags
        // Find body node that contains passed content nodes
        $bodyNodes = $doc->getElementsByTagName('body');
        if ($bodyNodes->length !== 1) {
            throw new \RuntimeException('Body tag not found');
        }
        $bodyNode = $bodyNodes->item(0);

        $result = '';
        $length = 0;
        foreach ($bodyNode->childNodes as $node) {

            // Collect the node outerHtml
            $result .= $node->ownerDocument->saveHTML($node);

            // Increase summary length by the node text length
            $length += strlen($node->textContent);

            // Limit reached - stop the iteration
            if ($length > $limit) {
                break;
            }
        }

        // Add ending
        if ($result !== '') {
            $result .= $ending;
        }

        return $result;
    }

    /**
     * Decorates nodes that contains another nodes by attributes and class
     *
     * Nodes selected by selector will be decorated if contain nodes by another selector.
     *
     * Cases of use like cases of use the :has pseudo-selector
     *
     * @see https://drafts.csswg.org/selectors-4/#has-pseudo Selector Level 4
     *
     * @param string $html       Html for processing
     * @param string $selector   CSS-selector for children nodes
     * @param array  $attributes Attributes that will be set
     * @param string $class      Class that will be appended
     *
     * @return string
     */
    public static function decorateParent($html, $selector, $attributes = [], $class = '')
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html, 'UTF-8');

        foreach ($crawler->filter($selector) as $node) {
            $parent = $node->parentNode;
            foreach ($attributes as $name => $value) {
                $parent->setAttribute($name, $value);
            }
            if ($parent->hasAttribute('class')) {
                $parent->setAttribute('class', $parent->getAttribute('class') . ' ' . $class);
            } else {
                $parent->setAttribute('class', $class);
            }
        }

        return $crawler->html();
    }

    /**
     * Extracts short description from HTML
     *
     * We believe that the description it is content of first paragraph
     *
     * @param string $html            HTML
     * @param string $contentSelector CSS-selector of content node
     *
     * @return string|null
     */
    public static function extractDescription($html, $contentSelector = '')
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($html, 'UTF-8');

        foreach ($crawler->filter($contentSelector . ' ' . self::TAG_PARAGRAPH) as $paragraph) {
            /** DOMElement $content */
            return $paragraph->ownerDocument->saveHTML($paragraph);
        }

        return null;
    }
}