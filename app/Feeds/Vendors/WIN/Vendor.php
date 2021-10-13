<?php

namespace App\Feeds\Vendors\WIN;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\Link;
use App\Feeds\Utils\ParserCrawler;

class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ '.select_category .with_caption a'];
    public const PRODUCT_LINK_CSS_SELECTORS = [ '.item h3 a' ];

    public array $first = [ 'http://winsomewood.com/select_category' , 'http://winsomewood.com/products?query='];

    public function isValidFeedItem(FeedItem $fi ): bool
    {
        return !empty($fi->getMpn());
    }

    public function getCategoriesLinks(Data $data, string $url): array
    {
        $pagination_links = [];
        $crawler = new ParserCrawler($data->getData());
        if ($crawler->exists('.pagination')) {
            $last_page_uri = $crawler->getAttr('.last a', 'href');
            $parts = explode('page=', $last_page_uri);
            if (count($parts) === 2) {
                $pages_count = (int) $parts[1];
                for ($page = 2; $page <= $pages_count; $page++) {
                    $pagination_links[] = new Link("$url&page=$page");
                }
            }
        }

        return array_merge($pagination_links, parent::getCategoriesLinks($data, $url));
    }
}
