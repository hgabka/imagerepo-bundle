<?php

namespace HG\ImageRepositoryBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HGImageRepositoryExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('hg_image_repository.image_transform_settings', $config['image_transform']);
        $container->setParameter('hg_image_repository.image_creator_class', $config['image_transform']['creator_class']);
        $container->setParameter('hg_image_repository.config_settings', $config);
        $container->setParameter('hg_image_repository.manager_class', $config['class']);
        $container->setParameter('hg_image_repository.upload_request_type', $config['upload_request_type']);
        $container->setParameter('hg_image_repository.auto_delete_relations', $config['auto_delete_relations']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('twig.form.resources', array_merge(
            $container->getParameter('twig.form.resources'),
            array('HGImageRepositoryBundle:Form:image_repository_widget.html.twig', 'HGImageRepositoryBundle:Form:image_uploadify_widget.html.twig')
        ));
    }
}
