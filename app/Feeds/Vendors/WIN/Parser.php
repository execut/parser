<?php

namespace App\Feeds\Vendors\WIN;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\FeedHelper;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $attributes_list;
    private array $description_and_attributes;
    private array $dims;
    public function beforeParse(): void
    {
        $this->initAttributesList();
        $this->initDescriptionAndAttributes();
        $this->initDims();
    }

    private function initAttributesList(): void
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

        $this->attributes_list = $attributes;
    }

    private function initDims(): void
    {
        $node = $this->node->filter('.main-meta ul.dimensions', 0);
        if (!$node->count()) {
            $text = '';
        } else {
            $text = $node->text();
        }

        $this->dims = FeedHelper::getDimsInString($text, 'x',1, 2, 0);
    }

    private function initDescriptionAndAttributes(): void
    {
        $this->description_and_attributes = FeedHelper::getShortsAndAttributesInDescription($this->getDescriptionSource(), [], [], $this->getAttributesSource());
    }

    private function getAttributesSource(): ?array
    {
        $attributes = $this->attributes_list;
        unset($attributes['Category'], $attributes['Item #'], $attributes['Collection']);

        return $attributes;
    }

    private function getDescriptionSource(): string
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

    public function getMpn(): string
    {
        return $this->attributes_list['Item #'] ?? '';
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
            $images = $this->getSrcImages('img.large');
        }

        return $images;
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

    public function getBrand(): ?string
    {
        return $this->attributes_list['Collection'] ?? null;
    }

    public function getCategories(): array
    {
        $categories = [];
        if (array_key_exists('Category', $this->attributes_list)) {
            $categories[] = $this->attributes_list['Category'];
        }

        return $categories;
    }

    public function getDimX(): ?float
    {
        return $this->dims['x'];
    }

    public function getDimY(): ?float
    {
        return $this->dims['y'];
    }

    public function getDimZ(): ?float
    {
        return $this->dims['z'];
    }

    public function getAttributes(): ?array
    {
        return $this->description_and_attributes['attributes'];
    }

    public function getDescription(): string
    {
        return StringHelper::isNotEmpty($this->description_and_attributes['description']) ? $this->description_and_attributes['description'] : $this->getProduct();
    }
}