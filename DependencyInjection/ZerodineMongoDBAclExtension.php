<?php

/*
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zerodine\MongoDBAclBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

/**
 * @author Richard Shank <develop@zestic.com>
 */
class ZerodineMongoDBAclExtension extends Extension
{
    /**
     * Responds to the doctrine_mongodb configuration parameter.
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Load DoctrineMongoDBBundle/Resources/config/mongodb.xml
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('security.xml');

        $processor = new Processor();
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config = $processor->processConfiguration($configuration, $configs);

        if (isset($config['acl_provider']) && isset($config['acl_provider']['default_database'])) {
            $this->loadAcl($config['acl_provider'], $container);
        }
    }

    protected function loadAcl($config, ContainerBuilder $container)
    {
        $container->setParameter('doctrine_mongodb.odm.security.acl.database', $config['default_database']);

        $container->setParameter('doctrine_mongodb.odm.security.acl.entry_collection', $config['collections']['entry']);
        $container->setParameter('doctrine_mongodb.odm.security.acl.oid_collection', $config['collections']['object_identity']);
    }

    public function getAlias()
    {
        return 'iam_persistent_mongo_db_acl';
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://symfony.com/schema/dic/doctrine/odm/mongodb';
    }
}
