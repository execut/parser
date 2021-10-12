<?php

namespace App\Feeds\Vendors\WIN;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;

class Parser extends HtmlParser
{
    private const MAIN_DOMAIN = 'http://winsomewood.com/';
    protected array $attributesList = [];
    protected function initAttributesList(): void
    {
        $contents = $this->node->getContent('ul.main-meta>li:not(.dimensions)');
        $attributes = [];
        foreach ($contents as $content) {
            $parts = explode(': ', $content, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$key, $value] = $parts;
            $attributes[$key] = $value;
        }

        $this->attributesList = $attributes;
    }

    public function getMpn(): string
    {
        return $this->attributesList['Item #'] ?? '';
    }

    public function beforeParse(): void
    {
        $this->initAttributesList();
    }

    public function getCostToUs(): float
    {
        return 1;
    }

    public function getProduct(): string
    {
        return $this->getText('h1');
    }

    public function getImages(): array
    {
        $images = array_values(array_unique($this->getLinks('.thumbnails a')));
        if (!count($images)) {
            $images = [ self::MAIN_DOMAIN . $this->getAttr('img.large', 'src') ];
        }

        return $images;
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getBrand(): ?string
    {
        return 'Winsome Trading';
    }

    public function getCategories(): array
    {
        if (array_key_exists('Category', $this->attributesList)) {
            return [
                $this->attributesList['Category']
            ];
        }

        return [];
    }

    public function getAttributes(): ?array
    {
        $attributes = $this->attributesList;
        unset($attributes['Category'], $attributes['Item #']);

        return $attributes;
    }

    protected function getDimension(int $col): ?float
    {
        $node = $this->node->filter('.main-meta ul.dimensions', 0);
        if (!$node->count()) {
            return null;
        }

        $text = $node->text();
        $parts = explode(' x ', $text);

        return (float) str_replace(' in', '', $parts[$col]);
    }

    public function getDimX(): ?float
    {
        return $this->getDimension(1);
    }

    public function getDimY(): ?float
    {
        return $this->getDimension(2);
    }

    public function getDimZ(): ?float
    {
        return $this->getDimension(0);
    }

    public function getDescription(): string
    {
        $result = $this->node->filterXPath('//li[contains(@class, "item")]')->each( function (ParserCrawler $c ) {
            return '<p>' . $c->getText('h3 a') . '</p>'
                . $c->filter('ul.meta')->outerHtml();
        });

        if (!empty($result)) {
            return '<h2>Set components</h2>' . implode('', $result);
        }

        return '';
    }
}