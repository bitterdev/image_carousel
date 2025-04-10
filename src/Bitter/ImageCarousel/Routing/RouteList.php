<?php

namespace Bitter\ImageCarousel\Routing;

use Bitter\ImageCarousel\API\V1\Middleware\FractalNegotiatorMiddleware;
use Bitter\ImageCarousel\API\V1\Configurator;
use Concrete\Core\Routing\RouteListInterface;
use Concrete\Core\Routing\Router;

class RouteList implements RouteListInterface
{
    public function loadRoutes(Router $router)
    {
        $router
            ->buildGroup()
            ->setNamespace('Concrete\Package\ImageCarousel\Controller\Dialog\Support')
            ->setPrefix('/ccm/system/dialogs/image_carousel')
            ->routes('dialogs/support.php', 'image_carousel');
    }
}