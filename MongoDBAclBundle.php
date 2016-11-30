<?php

namespace PWalkow\MongoDBAclBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use PWalkow\MongoDBAclBundle\DependencyInjection\MongoDBAclExtension;

/**
 * @author Richard Shank <develop@zestic.com>
 * @author Piotr Walków <walkowpiotr@gmail.com>
 */
class MongoDBAclBundle extends Bundle
{
    /**
     * @return ExtensionInterface
     */
    public function getContainerExtension()
    {
        return new MongoDBAclExtension();
    }
}
