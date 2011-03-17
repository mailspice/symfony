<?php

namespace Symfony\Bundle\DoctrineMongoDBBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * FrameworkExtension configuration structure.
 *
 * @author Ryan Weaver <ryan@thatsquality.com>
 */
class Configuration
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
     * Generates the configuration tree.
     *
     * @return \Symfony\Component\DependencyInjection\Configuration\NodeInterface
     */
    public function getConfigTree()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('doctrine_mongo_db', 'array');

        $this->addDocumentManagersSection($rootNode);
        $this->addConnectionsSection($rootNode);

        $rootNode
            ->scalarNode('proxy_namespace')->defaultValue('Proxies')->end()
            ->scalarNode('auto_generate_proxy_classes')->defaultValue(false)->end()
            ->scalarNode('hydrator_namespace')->defaultValue('Hydrators')->end()
            ->scalarNode('auto_generate_hydrator_classes')->defaultValue(false)->end()
            ->scalarNode('default_document_manager')->end()
            ->scalarNode('default_connection')->end()
            ->scalarNode('default_database')->defaultValue('default')->end()
        ;

        return $treeBuilder->buildTree();
    }

    /**
     * Configures the "document_managers" section
     */
    private function addDocumentManagersSection(NodeBuilder $rootNode)
    {
        $rootNode
            ->fixXmlConfig('document_manager')
            ->arrayNode('document_managers')
                ->useAttributeAsKey('id')
                ->prototype('array')
                    //->performNoDeepMerging()
                    ->treatNullLike(array())
                    ->builder($this->getMetadataCacheDriverNode())
                    ->scalarNode('connection')->end()
                    ->scalarNode('database')->end()
                    ->booleanNode('logging')->defaultValue($this->debug)->end()
                    ->fixXmlConfig('mapping')
                    ->builder($this->getMappingsNode())
                ->end()
            ->end()
        ;
    }

    /**
     * Adds the configuration for the "connections" key
     */
    private function addConnectionsSection(NodeBuilder $rootNode)
    {
        $rootNode
            ->fixXmlConfig('connection')
            ->arrayNode('connections')
                ->useAttributeAsKey('id')
                ->prototype('array')
                    ->performNoDeepMerging()
                    ->scalarNode('server')->defaultNull()->end()
                    ->builder($this->addConnectionOptionsNode())
                ->end()
            ->end()
        ;
    }

    /**
     * Returns the array node used for "mappings".
     *
     * This is used in two different parts of the tree.
     *
     * @param NodeBuilder $rootNode The parent node
     * @return NodeBuilder
     */
    protected function getMappingsNode()
    {
        $node = new Nodebuilder('mappings', 'array');
        $node
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->beforeNormalization()
                    // if it's not an array, then the scalar is the type key
                    ->ifString()
                    ->then(function($v) { return array ('type' => $v); })
                ->end()
                // I believe that "null" should *not* set the type
                // it's guessed in AbstractDoctrineExtension::detectMetadataDriver
                ->treatNullLike(array())
                ->scalarNode('type')->end()
                ->scalarNode('dir')->end()
                ->scalarNode('prefix')->end()
                ->scalarNode('alias')->end()
                ->booleanNode('is_bundle')->end()
                ->performNoDeepMerging()
            ->end()
        ;

        return $node;
    }

    /**
     * Adds the NodeBuilder for the "options" key of a connection.
     */
    private function addConnectionOptionsNode()
    {
        $node = new NodeBuilder('options', 'array');

        $node
            ->performNoDeepMerging()
            ->addDefaultsIfNotSet() // adds an empty array of omitted

            // options go into the Mongo constructor
            // http://www.php.net/manual/en/mongo.construct.php
            ->booleanNode('connect')->end()
            ->scalarNode('persist')->end()
            ->scalarNode('timeout')->end()
            ->booleanNode('replicaSet')->end()
            ->scalarNode('username')->end()
            ->scalarNode('password')->end()
        ->end();

        return $node;
    }

    private function getMetadataCacheDriverNode()
    {
        $node = new NodeBuilder('metadata_cache_driver', 'array');

        $node
            ->beforeNormalization()
                // if scalar
                ->ifTrue(function($v) { return !is_array($v); })
                ->then(function($v) { return array('type' => $v); })
            ->end()
            ->scalarNode('type')->end()
            ->scalarNode('class')->end()
            ->scalarNode('host')->end()
            ->scalarNode('port')->end()
            ->scalarNode('instance_class')->end()
        ->end();

        return $node;
    }
}
