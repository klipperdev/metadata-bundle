<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\MetadataBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class MetadataRegistryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('klipper_metadata.registry')) {
            return;
        }

        $def = $container->getDefinition('klipper_metadata.registry');
        $builders = $this->findTags($container, 'klipper_metadata.object_builder', $def->getArgument(0));
        $loaders = $this->findTags($container, 'klipper_metadata.object_loader', $def->getArgument(1));
        $guessers = $this->findTags($container, 'klipper_metadata.guess', $def->getArgument(2));

        $def->replaceArgument(0, $builders);
        $def->replaceArgument(1, $loaders);
        $def->replaceArgument(2, $guessers);
    }

    /**
     * Find and returns the services with the tag.
     *
     * @param ContainerBuilder $container The container service
     * @param string           $tag       The tag name
     * @param Reference[]      $list      The list of services
     *
     * @return Reference[]
     */
    protected function findTags(ContainerBuilder $container, $tag, array $list): array
    {
        foreach ($this->findAndSortTaggedServices($tag, $container) as $service) {
            $list[] = $service;
        }

        return $list;
    }
}
