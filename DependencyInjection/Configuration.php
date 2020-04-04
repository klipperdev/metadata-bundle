<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\MetadataBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('klipper_metadata');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->append($this->getDefaultsNode())
            ->append($this->getObjectsNode())
        ;

        return $treeBuilder;
    }

    /**
     * Get defaults node.
     */
    private function getDefaultsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('defaults');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('global')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('translation_domain')->defaultNull()->end()
            ->booleanNode('public')->defaultNull()->end()
            ->booleanNode('sortable')->defaultNull()->end()
            ->booleanNode('multi_sortable')->defaultNull()->end()
            ->booleanNode('filterable')->defaultNull()->end()
            ->booleanNode('searchable')->defaultNull()->end()
            ->arrayNode('available_contexts')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->append($this->getActionMetadatasNode())
            ->append($this->getFieldMetadatasNode())
            ->append($this->getAssociationMetadatasNode())
            ->end()
        ;

        return $node;
    }

    /**
     * Get objects node.
     */
    private function getObjectsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('objects');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('class', false)
            ->normalizeKeys(false)

            ->prototype('array')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('name')->defaultNull()->end()
            ->scalarNode('plural_name')->defaultNull()->end()
            ->scalarNode('label')->defaultNull()->end()
            ->scalarNode('description')->defaultNull()->end()
            ->scalarNode('translation_domain')->defaultNull()->end()
            ->booleanNode('public')->defaultNull()->end()
            ->booleanNode('multi_sortable')->defaultNull()->end()
            ->arrayNode('default_sortable')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('available_contexts')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->scalarNode('field_identifier')->defaultNull()->end()
            ->scalarNode('field_label')->defaultNull()->end()
            ->scalarNode('form_type')->defaultNull()->end()
            ->arrayNode('form_options')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('groups')
            ->prototype('scalar')->end()
            ->end()
            ->booleanNode('build_default_actions')->defaultNull()->end()
            ->append($this->getActionMetadataNode('default_action'))
            ->append($this->getActionMetadatasNode())
            ->append($this->getFieldMetadatasNode())
            ->append($this->getAssociationMetadatasNode())
            ->end()
            ->end()
        ;

        return $node;
    }

    /**
     * Get action metadatas node.
     */
    private function getActionMetadatasNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('actions');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('action', false)
            ->normalizeKeys(false)
            ->beforeNormalization()
            ->ifTrue(static function () {
                return true;
            })
            ->then(static function ($v) {
                foreach ($v as $action => $aConfig) {
                    $v[$action]['name'] = $action;
                }

                return $v;
            })
            ->end()
        ;

        $this->configureActionMetadataNode($node->arrayPrototype());

        return $node;
    }

    /**
     * Get action metadata node.
     *
     * @param string $name The node name
     */
    private function getActionMetadataNode(string $name = 'action'): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder($name);
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();

        return $this->configureActionMetadataNode($node);
    }

    /**
     * Get action metadata node.
     */
    private function configureActionMetadataNode(ArrayNodeDefinition $node): ArrayNodeDefinition
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('name')->defaultNull()->end()
            ->scalarNode('host')->defaultNull()->end()
            ->scalarNode('path')->defaultNull()->end()
            ->scalarNode('fragment')->defaultNull()->end()
            ->scalarNode('controller')->defaultNull()->end()
            ->scalarNode('format')->defaultNull()->end()
            ->scalarNode('locale')->defaultNull()->end()
            ->scalarNode('condition')->defaultNull()->end()
            ->arrayNode('methods')
            ->beforeNormalization()
            ->ifString()
            ->then(static function ($v) {
                return (array) $v;
            })
            ->end()
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('schemes')
            ->beforeNormalization()
            ->ifString()
            ->then(static function ($v) {
                return (array) $v;
            })
            ->end()
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('defaults')
            ->beforeNormalization()
            ->ifString()
            ->then(static function ($v) {
                return (array) $v;
            })
            ->end()
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('requirements')
            ->beforeNormalization()
            ->ifString()
            ->then(static function ($v) {
                return (array) $v;
            })
            ->end()
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('options')
            ->beforeNormalization()
            ->ifString()
            ->then(static function ($v) {
                return (array) $v;
            })
            ->end()
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('configurations')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;

        return $node;
    }

    /**
     * Get field metadatas node.
     */
    private function getFieldMetadatasNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('fields');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('field', false)
            ->normalizeKeys(false)

            ->prototype('array')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('name')->defaultNull()->end()
            ->scalarNode('type')->defaultNull()->end()
            ->scalarNode('label')->defaultNull()->end()
            ->scalarNode('description')->defaultNull()->end()
            ->scalarNode('translation_domain')->defaultNull()->end()
            ->booleanNode('public')->defaultNull()->end()
            ->booleanNode('sortable')->defaultNull()->end()
            ->booleanNode('filterable')->defaultNull()->end()
            ->booleanNode('searchable')->defaultNull()->end()
            ->booleanNode('translatable')->defaultNull()->end()
            ->booleanNode('read_only')->defaultNull()->end()
            ->booleanNode('required')->defaultNull()->end()
            ->scalarNode('input')->defaultNull()->end()
            ->arrayNode('input_config')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->scalarNode('form_type')->defaultNull()->end()
            ->arrayNode('form_options')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('groups')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
        ;

        return $node;
    }

    /**
     * Get association metadatas node.
     */
    private function getAssociationMetadatasNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('associations');
        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('association', false)
            ->normalizeKeys(false)

            ->prototype('array')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('name')->defaultNull()->end()
            ->scalarNode('type')->defaultNull()->end()
            ->scalarNode('target')->defaultNull()->end()
            ->scalarNode('label')->defaultNull()->end()
            ->scalarNode('description')->defaultNull()->end()
            ->scalarNode('translation_domain')->defaultNull()->end()
            ->booleanNode('public')->defaultNull()->end()
            ->booleanNode('read_only')->defaultNull()->end()
            ->booleanNode('required')->defaultNull()->end()
            ->scalarNode('input')->defaultNull()->end()
            ->arrayNode('input_config')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->scalarNode('form_type')->defaultNull()->end()
            ->arrayNode('form_options')
            ->useAttributeAsKey('name', false)
            ->normalizeKeys(false)
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('groups')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
        ;

        return $node;
    }
}
