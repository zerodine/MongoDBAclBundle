<?php

/*
 *
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IamPersistent\MongoDBAclBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler\CreateHydratorDirectoryPass;
use Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler\CreateProxyDirectoryPass;
use Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection\Compiler\EventManagerPass;
use IamPersistent\MongoDBAclBundle\DependencyInjection\DoctrineMongoDBExtension;

/**
 * @author Richard Shank <develop@zestic.com>
 */
class IamPersistentMongoDBAclBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EventManagerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new CreateProxyDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new CreateHydratorDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
