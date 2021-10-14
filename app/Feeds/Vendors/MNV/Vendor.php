<?php
namespace App\Feeds\Vendors\MNV;
use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\Link;

class Vendor extends SitemapHttpProcessor
{
//            2412
    public const CATEGORY_LINK_CSS_SELECTORS = ['sitemap loc'];
    public const PRODUCT_LINK_CSS_SELECTORS = ['url loc'];
    public array $first = ['https://www.monave.com/sitemap_index.xml'];
//    public array $custom_products = [
//        'https://www.monave.com/wholesale/private-label-packaged-cosmetics/vegan-mousse-foundation/paula-vegan-mousse-foundation/',
//    ];
    public function getCategoriesLinks(Data $data, string $url): array
    {
        $links = parent::getCategoriesLinks($data, $url);
        $links = array_filter($links, fn($link) => strpos($link->getUrl(), 'product-sitemap') !== false);

        return $links;
    }

    public function isValidFeedItem(FeedItem $fi ): bool
    {
        return !empty($fi->getMpn());
    }

    public function filterProductLinks(Link $link): bool
    {
        if ($link->getUrl() === 'https://www.monave.com/shop/') {
            return false;
        }

        return parent::filterProductLinks($link);
    }
}