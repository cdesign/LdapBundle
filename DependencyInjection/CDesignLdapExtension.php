<?php

namespace CDesign\LdapBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class CDesignLdapExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Load config params into the parameter bag...
        foreach($config as $key => $value){
            $container->setParameter(str_replace('_', '.', $this->getAlias()) . '.' . $key, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'cdesign_ldap';
    }
}