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

use Klipper\Component\Metadata\ActionMetadataBuilder;
use Klipper\Component\Metadata\AssociationMetadataBuilder;
use Klipper\Component\Metadata\FieldMetadataBuilder;
use Klipper\Component\Metadata\ObjectMetadataBuilder;
use Klipper\Component\Metadata\Util\MetadataUtil;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperMetadataExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('metadata.xml');
        $loader->load('metadata_listener.xml');
        $loader->load('cache.xml');

        $this->loadMetadataManager($container, $config['objects'], $config['defaults']);
    }

    /**
     * Load the metadata manager.
     *
     * @param ContainerBuilder $container The container
     * @param array            $metadatas The metadatas
     * @param array            $defaults  The defaults configuration of metadatas
     */
    private function loadMetadataManager(ContainerBuilder $container, array $metadatas, array $defaults): void
    {
        $container->getDefinition('klipper_metadata.guess.default')->replaceArgument(0, $this->cleanConfig($defaults));
        $metadatas = $this->cleanConfig($metadatas);
        $configDir = $container->getParameter('kernel.project_dir').'/config';

        foreach ($metadatas as $class => $objectConfig) {
            $objectName = MetadataUtil::getObjectName($class);
            $oDef = new Definition(ObjectMetadataBuilder::class, [$class]);
            $this->addMetadataConfigs($oDef, $objectConfig, [
                'setName' => 'name',
                'setPluralName' => 'plural_name',
                'setLabel' => 'label',
                'setDescription' => 'description',
                'setTranslationDomain' => 'translation_domain',
                'setPublic' => 'public',
                'setMultiSortable' => 'multi_sortable',
                'setDefaultSortable' => 'default_sortable',
                'setAvailableContexts' => 'available_contexts',
                'setFieldIdentifier' => 'field_identifier',
                'setFieldLabel' => 'field_label',
                'setFormType' => 'form_type',
                'setFormOptions' => 'form_options',
                'setGroups' => 'groups',
                'setBuildDefaultActions' => 'build_default_actions',
            ]);

            if (isset($objectConfig['default_action'])) {
                $defaultActionDef = new Definition(ActionMetadataBuilder::class, ['_default']);
                $this->configureActionMetadata($defaultActionDef, $objectConfig['default_action']);
                $oDef->addMethodCall('setDefaultAction', [$defaultActionDef]);
            }

            if (isset($objectConfig['actions'])) {
                $this->addActionMetadatas($oDef, $objectConfig['actions']);
            }

            if (isset($objectConfig['fields'])) {
                $this->addFieldMetadatas($oDef, $objectConfig['fields']);
            }

            if (isset($objectConfig['associations'])) {
                $this->addAssociationMetadatas($oDef, $objectConfig['associations']);
            }

            $oDef
                ->setPublic(false)
                ->addTag('klipper_metadata.object_builder')
                ->addMethodCall('addResource', [
                    new Definition(DirectoryResource::class, [$configDir]),
                ])
            ;
            $container->setDefinition('klipper_metadata.object_builder.'.$objectName, $oDef);
        }
    }

    /**
     * Add the action metadatas.
     *
     * @param Definition $definition The object metadata definition
     * @param array      $metadatas  The action metadata configs
     */
    private function addActionMetadatas(Definition $definition, array $metadatas): void
    {
        foreach ($metadatas as $action => $actionConfig) {
            $aDef = new Definition(ActionMetadataBuilder::class, [$action]);
            $this->configureActionMetadata($aDef, $actionConfig);

            $definition->addMethodCall('addAction', [$aDef]);
        }
    }

    /**
     * Configure the action metadata definition.
     *
     * @param Definition $actionDefinition The action definition
     * @param array      $actionConfig     The action configuration
     */
    private function configureActionMetadata(Definition $actionDefinition, array $actionConfig): void
    {
        $this->addMetadataConfigs($actionDefinition, $actionConfig, [
            'setMethods' => 'methods',
            'setSchemes' => 'schemes',
            'setHost' => 'host',
            'setPath' => 'path',
            'setFragment' => 'fragment',
            'setController' => 'controller',
            'setFormat' => 'format',
            'setLocale' => 'locale',
            'setDefaults' => 'defaults',
            'setRequirements' => 'requirements',
            'setOptions' => 'options',
            'setCondition' => 'condition',
            'setConfigurations' => 'configurations',
        ]);
    }

    /**
     * Add the field metadatas.
     *
     * @param Definition $definition The object metadata definition
     * @param array      $metadatas  The field metadata configs
     */
    private function addFieldMetadatas(Definition $definition, array $metadatas): void
    {
        foreach ($metadatas as $field => $fieldConfig) {
            $fDef = new Definition(FieldMetadataBuilder::class, [$field]);
            $this->addMetadataConfigs($fDef, $fieldConfig, [
                'setType' => 'type',
                'setName' => 'name',
                'setLabel' => 'label',
                'setDescription' => 'description',
                'setTranslationDomain' => 'translation_domain',
                'setPublic' => 'public',
                'setSortable' => 'sortable',
                'setFilterable' => 'filterable',
                'setSearchable' => 'searchable',
                'setTranslatable' => 'translatable',
                'setReadOnly' => 'read_only',
                'setRequired' => 'required',
                'setInput' => 'input',
                'setInputConfig' => 'input_config',
                'setFormType' => 'form_type',
                'setFormOptions' => 'form_options',
                'setGroups' => 'groups',
            ]);

            $definition->addMethodCall('addField', [$fDef]);
        }
    }

    /**
     * Add the association metadatas.
     *
     * @param Definition $definition The object metadata definition
     * @param array      $metadatas  The association metadata configs
     */
    private function addAssociationMetadatas(Definition $definition, array $metadatas): void
    {
        foreach ($metadatas as $association => $associationConfig) {
            $aDef = new Definition(AssociationMetadataBuilder::class, [$association]);
            $this->addMetadataConfigs($aDef, $associationConfig, [
                'setType' => 'type',
                'setTarget' => 'target',
                'setName' => 'name',
                'setLabel' => 'label',
                'setDescription' => 'description',
                'setTranslationDomain' => 'translation_domain',
                'setPublic' => 'public',
                'setReadOnly' => 'read_only',
                'setRequired' => 'required',
                'setInput' => 'input',
                'setInputConfig' => 'input_config',
                'setFormType' => 'form_type',
                'setFormOptions' => 'form_options',
                'setGroups' => 'groups',
            ]);

            $definition->addMethodCall('addAssociation', [$aDef]);
        }
    }

    /**
     * Add the metadata configs in the child metadata builder.
     *
     * @param Definition $definition The child metadata definition
     * @param array      $config     The metadata config
     * @param array      $valueKeys  The maps of called method and config keys of values
     */
    private function addMetadataConfigs(Definition $definition, array $config, array $valueKeys): void
    {
        foreach ($valueKeys as $method => $valueKey) {
            if (!empty($config[$valueKey])) {
                $definition->addMethodCall($method, [$config[$valueKey]]);
            }
        }
    }

    /**
     * Remove the keys with null value or empty array.
     *
     * @param array $config The config
     */
    private function cleanConfig(array $config): array
    {
        foreach ($config as $key => $value) {
            if (\is_array($value)) {
                $config[$key] = $this->cleanConfig($config[$key]);
            }

            if (!$this->isValidConfig($config[$key])) {
                unset($config[$key]);
            }
        }

        return $config;
    }

    /**
     * Check if the value is not null and if array is not empty.
     *
     * @param mixed $value The value
     */
    private function isValidConfig($value): bool
    {
        $isArray = \is_array($value);

        return null !== $value && (!$isArray || ($isArray && !empty($value)));
    }
}
