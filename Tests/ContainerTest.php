<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IamPersistent\MongoDBAclBundle\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use IamPersistent\MongoDBAclBundle\DependencyInjection\IamPersistentMongoDBAclExtension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerTest extends TestCase
{
    public function getContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.bundles'     => array('YamlBundle' => 'DoctrineMongoDBBundle\Tests\DependencyInjection\Fixtures\Bundles\YamlBundle\YamlBundle'),
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.debug'       => false,
        )));
        $loader = new IamPersistentMongoDBAclExtension();
        $container->registerExtension($loader);

        $configs = array();
        $configs[] = array('connections' => array('default' => array()), 'document_managers' => array('default' => array('mappings' => array('YamlBundle' => array()))));
        $loader->load($configs, $container);

        $container->set('annotation_reader', new AnnotationReader());

        return $container;
    }

    public function testContainer()
    {
        $this->markTestSkipped('not sure what the point is in the current tests');

        $container = $this->getContainer();
        $this->assertInstanceOf('IamPersistent\MongoDBAclBundle\Logger\DoctrineMongoDBLogger', $container->get('doctrine.odm.mongodb.logger'));
        $this->assertInstanceOf('IamPersistent\MongoDBAclBundle\DataCollector\DoctrineMongoDBDataCollector', $container->get('doctrine.odm.mongodb.data_collector'));
    }
}
