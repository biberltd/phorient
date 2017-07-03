<?php

namespace BiberLtd\Bundle\Phorient\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class PhorientExtension extends Extension{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');
    }

    /**
     * {@inheritDoc}
     *
     * @author          Can Berkol
     * @since			1.0.0
     * @version         1.0.0
     *
     * @return	        string
     */
    public function getAlias(){
        return 'biber_ltd_php_orient';
    }
}
