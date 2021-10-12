<?php

namespace App\Feeds\Vendors\WIN;

use App\Feeds\Processor\HttpProcessor;
use App\Feeds\Utils\Data;
use App\Feeds\Utils\Link;

class Vendor extends HttpProcessor
{
    public const CATEGORY_LINK_CSS_SELECTORS = [ '.pagination .page a' ];
    public const PRODUCT_LINK_CSS_SELECTORS = [ '.item h3 a' ];

    public array $first = [ 'http://winsomewood.com/products?query=' ];
    public function getCategoriesLinks(Data $data, string $url): array
    {
        return [ 'http://winsomewood.com/products?query=' ];
    }
}
