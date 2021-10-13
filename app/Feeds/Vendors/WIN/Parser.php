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

        unset($attributes['Category'], $attributes['Item #'], $attributes['Collection']);

        $this->description_and_attributes = FeedHelper::getShortsAndAttributesInDescription($this->getDescriptionSource(), [], [], $attributes);

        $node = $this->node->filter('.main-meta ul.dimensions', 0);
        if (!$node->count()) {
            $text = '';
        } else {
            $text = $node->text();
        }

        $this->dims = FeedHelper::getDimsInString($text, 'x',1, 2, 0);
    }

    private function getDescriptionSource(): string
    {
        if ($this->exists('.components')) {
            $result = $this->filter('li.item')->each(function (ParserCrawler $c) {
                return '<p>' . $c->getText('h3 a') . '</p>'
                    . $c->filter('ul.meta')->outerHtml();
            });
        }

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
        return isset($this->attributes_list['Category']) ? [$this->attributes_list['Category']] : [];
    }

    public function getDimX(): ?float
    {
        return $this->dims['x'] ?? null;
    }

    public function getDimY(): ?float
    {
        return $this->dims['y'] ?? null;
    }

    public function getDimZ(): ?float
    {
        return $this->dims['z'] ?? null;
    }

    public function getAttributes(): ?array
    {
        return $this->description_and_attributes['attributes'] ?? null;
    }

    public function getDescription(): string
    {
        return StringHelper::isNotEmpty($this->description_and_attributes['description']) ? $this->description_and_attributes['description'] : $this->getProduct();
    }
}