<?php

namespace Bitter\ImageCarousel\Provider;

use Concrete\Core\Application\Application;
use Concrete\Core\Asset\AssetList;
use Concrete\Core\Foundation\Service\Provider;
use Concrete\Core\Routing\RouterInterface;
use Bitter\ImageCarousel\Routing\RouteList;

class ServiceProvider extends Provider
{
    protected RouterInterface $router;

    public function __construct(
        Application     $app,
        RouterInterface $router
    )
    {
        parent::__construct($app);

        $this->router = $router;
    }

    public function register()
    {
        $this->registerRoutes();
        $this->registerAssets();
    }


    private function registerAssets()
    {
        $al = AssetList::getInstance();

        $al->register('javascript', 'slick-slider', 'js/slick.min.js', [], 'image_carousel');
        $al->register('css', 'slick-slider', 'css/slick.css', [], 'image_carousel');
        $al->register('css', 'slick-slider/theme', 'css/slick-theme.css', [], 'image_carousel');
        $al->registerGroup('slick-slider', [
            ['javascript', 'slick-slider'],
            ['css', 'slick-slider'],
            ['css', 'slick-slider/theme']
        ]);
    }

    private function registerRoutes()
    {
        $this->router->loadRouteList(new RouteList());
    }
}