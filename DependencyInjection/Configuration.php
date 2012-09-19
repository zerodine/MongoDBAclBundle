<?php

namespace IamPersistent\MongoDBAclBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Richard Shank <develop@zestic.com>
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    /**
     * Constructor.
     *
     * @param Boolean $debug The kernel.debug value
     */
    public function __construct($debug)
    {
        $this->debug = (Boolean) $debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('iam_persistent_mongo_db_acl');

        $this->addAclProviderSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds the configuration for the "acl_provider" key
     */
    private function addAclProviderSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('acl_provider')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default_database')->end()
                        ->arrayNode('collections')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('entry')->defaultValue('acl_entry')->end()
                                ->scalarNode('object_identity')->defaultValue('acl_oid')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
