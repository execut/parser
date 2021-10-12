<?php

namespace App\Feeds\Vendors\WIN;

use App\Feeds\Feed\FeedItem;
use App\Feeds\Processor\HttpProcessor;

class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ '.pagination .page a' ];
    public const PRODUCT_LINK_CSS_SELECTORS = [ '.item h3 a' ];

    public array $first = [ 'http://winsomewood.com/products?query=' ];

    public function isValidFeedItem(FeedItem $fi ): bool
    {
        return !empty($fi->getMpn());
    }
}
