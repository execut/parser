<?php
namespace App\Feeds\Vendors\MNV;
use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\SitemapHttpProcessor;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\Link;

class Vendor extends SitemapHttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = ['sitemap loc'];
    public const PRODUCT_LINK_CSS_SELECTORS = ['url loc'];
    public array $first = ['https://www.monave.com/sitemap_index.xml'];
    public array $custom_products = ['https://www.monave.com/cosmetics-skin-care/organic_cosmetics/face-92/loose-mineral-concealer-foundation/saturnina-concealer-foundation/'];
    public function getCategoriesLinks(Data $data, string $url): array
    {
        $links = parent::getCategoriesLinks($data, $url);
        return array_filter($links, static fn($link) => str_contains($link->getUrl(), 'product-sitemap'));
    }

    public function isValidFeedItem( FeedItem $fi ): bool
    {
        if ( $fi->isGroup() ) {
            $fi->setChildProducts( array_values(
                array_filter( $fi->getChildProducts(), static fn( FeedItem $item ) => !empty( $item->getMpn() ) && $item->getCostToUs() > 0 )
            ) );
            return count( $fi->getChildProducts() );
        }
        return !empty( $fi->getMpn() );
    }

    public function filterProductLinks(Link $link): bool
    {
        return !($link->getUrl() === 'https://www.monave.com/shop/') && parent::filterProductLinks($link);
    }
}