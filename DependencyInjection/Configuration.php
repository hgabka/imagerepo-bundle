<?php

namespace HG\ImageRepositoryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('hg_image_repository');

        $rootNode
            ->children()
                ->scalarNode('class')->defaultValue('HG\ImageRepositoryBundle\Model\HGImageManager')->end()
                ->scalarNode('file_repository_type')->defaultValue('image_repository')->end()
                ->scalarNode('cache_dir')->defaultValue('image_cache')->end()
                ->scalarNode('defaults_dir')->defaultValue('images/defaults')->end()
                ->scalarNode('default_method')->defaultValue('crop')->end()
                ->scalarNode('upload_request_type')->defaultValue('image_repository')->end()
                ->booleanNode('instant_cache')->defaultFalse()->end()
                ->booleanNode('watermark')->defaultFalse()->end()
                ->scalarNode('watermark_path')->defaultValue('images/watermarks/logo.png')->end()
                ->scalarNode('watermark_position')->defaultValue('bottom-right')->end()
                ->booleanNode('auto_delete_relations')->defaultTrue()->end()
                ->arrayNode('types')
                     ->isRequired()
                     ->requiresAtLeastOneElement()
                     ->useAttributeAsKey('name')
                     ->prototype('array')
                        ->children()
                         ->scalarNode('default_folder')->end()
                         ->booleanNode('instant_cache')->defaultFalse()->end()
                         ->booleanNode('watermark')->defaultFalse()->end()
                         ->scalarNode('watermark_path')->defaultValue('')->end()
                          ->arrayNode('sizes')
                          ->isRequired()
                          ->requiresAtLeastOneElement()
                          ->useAttributeAsKey('name')
                          ->prototype('array')
                            ->children()
                               ->enumNode('method')
                                 ->values(array('crop', 'resize', 'fit', 'fill', 'transparent-fit', 'exact', 'rotate', 'as_is'))
                               ->end()
                                ->arrayNode('size')
                                 ->prototype('scalar')->end()
                               ->end()
                               ->scalarNode('cropMethod')->defaultValue('scale')->end()
                               ->scalarNode('cropBackground')->defaultValue('#FFFFFF')->end()
                               ->scalarNode('background')->defaultValue('#FFFFFF')->end()
                               ->scalarNode('angle')->defaultValue(0)->end()
                               ->scalarNode('quality')->defaultValue(100)->end()
                               ->booleanNode('instant_cache')->defaultFalse()->end()
                               ->booleanNode('watermark')->defaultTrue()->end()
                               ->scalarNode('watermark_path')->defaultValue('')->end()
                               ->booleanNode('resizeInflate')->defaultTrue()->end()
                               ->booleanNode('resizeProportional')->defaultTrue()->end()
                               ->scalarNode('position')->defaultValue('center')->end()
                            ->end()
                         ->end()
                      ->end()
                      ->end()
                   ->end()
                ->end()   
                ->arrayNode('image_transform')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('creator_class')->defaultValue('HG\ImageRepositoryBundle\Image\ImageCreator')->end()
                            ->scalarNode('default_adapter')->defaultValue('GD')->end()
                            ->arrayNode('default_image')
                                ->addDefaultsIfNotSet()
                                ->children()
                                  ->scalarNode('mime_type')->defaultValue('image/png')->end()
                                  ->scalarNode('filename')->defaultValue('untitled.png')->end()
                                  ->scalarNode('width')->defaultValue(100)->end()
                                  ->scalarNode('height')->defaultValue(100)->end()
                                  ->scalarNode('color')->defaultValue('#FFFFFF')->end()
                                ->end()
                              ->end()
                            ->scalarNode('font_dir')->defaultValue('fonts')->end()
                            ->arrayNode('mime_type')
                                ->addDefaultsIfNotSet()
                                ->children()
                                  ->booleanNode('auto_detect')->defaultTrue()->end()
                                  ->scalarNode('library')->defaultValue('gd_mime_type')->end()
                                ->end()
                             ->end()
                         ->end()
                   ->end()
            ->end()    ;
        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
