<?php

declare(strict_types=1);

namespace Canvas\Providers;

use Canvas\Http\Request;
use Canvas\Http\SwooleRequest;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\DiInterface;
use function Canvas\Core\isSwooleServer;

class RequestProvider implements ServiceProviderInterface
{
    /**
     * @param DiInterface $container
     */
    public function register(DiInterface $container)
    {
        if (isSwooleServer()) {
            $container->setShared('request', new SwooleRequest());
        } else {
            $container->setShared('request', new Request());
        }
    }
}
