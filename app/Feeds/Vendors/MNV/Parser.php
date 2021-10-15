<?php

namespace App\Feeds\Vendors\MNV;

use App\Feeds\Parser\HtmlParser;
use App\Helpers\FeedHelper;
use App\Helpers\StringHelper;
use App\Feeds\Feed\FeedItem;
use PhpParser\JsonDecoder;

class Parser extends HtmlParser
{
    private ?array $variations = null;
    private ?array $dims = null;
    private array $description_data;
    public function beforeParse(): void
    {
        $json = $this->getAttr('.variations_form', 'data-product_variations');
        if ($json) {
            $decoder = new JsonDecoder();
            $this->variations = $decoder->decode($json);
        } else {
            $this->dims = FeedHelper::getDimsInString($this->getText('.woocommerce-product-attributes-item--dimensions .woocommerce-product-attributes-item__value'), '×');
        }

        $desc = FeedHelper::getShortsAndAttributesInDescription( $this->getHtml( '.tab-description' ) );
        $desc[ 'description' ] = FeedHelper::cleanProductData( $desc[ 'description' ], [ '/.*\$.*/s', '/.*\breviews\b.*/', '/.*logged\sin\scustomers.*/' ] );
        $desc[ 'description' ] = str_replace( '�', '', $desc[ 'description' ] );
        $desc[ 'description' ] = preg_replace( '/(.*?)(<table.*?<\/table>)?/s', '${1}', $desc[ 'description' ] );

        $this->description_data = $desc;
    }

    public function getMpn(): string
    {
        $sku = $this->getText('.sku');
        if (strtolower($sku) === 'n/a') {
            $sku = '';
        }

        return $sku;
    }

    public function getProduct(): string
    {
        return $this->getText('.product_title');
    }

    public function getCostToUs(): float
    {
        $money = $this->getMoney('.woocommerce-Price-amount');
        if (!$money) {
            $money = StringHelper::getMoney($this->getAttr('meta[property="og:price:amount"]', 'content'));
        }

        return $money;
    }

    public function isGroup(): bool
    {
        return (bool) $this->variations;
    }

    public function getDescription(): string
    {
        return StringHelper::isNotEmpty($this->description_data['description']) ? $this->description_data['description'] : $this->getProduct();
    }

    public function getAttributes(): ?array
    {
        return $this->description_data['attributes'] ?? null;
    }

    public function getShortDescription(): array
    {
        return $this->description_data['short_description'] ?? [];
    }

    public function getCategories(): array
    {
        return $this->filter('.product-details .posted_in a')->each(fn($e) => $e->text());
    }

    public function getImages(): array
    {
        return $this->getSrcImages('.woocommerce-product-gallery__image img, .more-photos img');
    }

    public function getWeight(): ?float
    {
        if ($this->isGroup()) {
            return null;
        }

        return StringHelper::getFloat($this->getText('.woocommerce-product-attributes-item--weight .woocommerce-product-attributes-item__value'));
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

    public function getAvail(): ?int
    {
        return $this->exists('.product-details .out-of-stock') ? 0 : self::DEFAULT_AVAIL_NUMBER;
    }

    public function getChildProducts(FeedItem $parent_fi): array
    {
        if (!$this->isGroup()) {
            return [];
        }

        $child_products = [];
        foreach ($this->variations as $variation) {
            $variation = array_filter($variation);
            $child_product = clone $parent_fi;
            if (!empty($variation['sku'])) {
                $child_product->setMpn($variation['sku']);
            } else {
                continue;
            }

            if (!empty($variation['attributes'])) {
                $key = key($variation['attributes']);

                $attributeId = str_replace(['attribute_pa_choose-', 'attribute_pa_'], '', $key);
                $sizeValue = current($variation['attributes']);
                if (StringHelper::isNotEmpty($sizeValue)) {
                    $sizeText = $this->getText('#pa_choose-' . $attributeId . ' option[value="' . $sizeValue . '"]');
                    if (!$sizeText) {
                        $sizeText = ucwords(str_replace('-', ' ', $sizeValue));
                    }

                    $child_product->setAttributes([
                        ucfirst($attributeId) => $sizeText,
                    ]);
                }
            }

            $child_product->setRAvail(!empty($variation['is_in_stock']) && !empty($variation['is_purchasable']) ? self::DEFAULT_AVAIL_NUMBER : 0);
            $dims = $variation['dimensions'];
            if (!empty($dims['length'])) {
                $child_product->setDimX(StringHelper::getFloat($dims['length']));
            }

            if (!empty($dims['height'])) {
                $child_product->setDimY(StringHelper::getFloat($dims['height']));
            }

            if (!empty($dims['width'])) {
                $child_product->setDimZ(StringHelper::getFloat($dims['width']));
            }

            if (!empty($variation['display_price'])) {
                $price = StringHelper::getMoney($variation['display_price']);
                if ($price <= 0) {
                    $price = 1;
                }
            } else {
                $price = 1;
            }

            $child_product->setCostToUs($price);

            if (!empty($variation['image']['url'])) {
                $child_product->setImages([
                    $variation['image']['url']
                ]);
            }

            if (!empty($variation['weight'])) {
                $child_product->setWeight(StringHelper::getFloat($variation['weight']));
            }

            $child_products[] = $child_product;
        }

        return $child_products;
    }

    public function getVideos(): array
    {
        $videos = [];
        if ($src = $this->getAttr('#tab-description iframe', 'src')) {
            $videos[] = [
                'name' => $this->getProduct(),
                'provider' => 'youtube',
                'video' => $src,
            ];
        }

        return $videos;
    }
}