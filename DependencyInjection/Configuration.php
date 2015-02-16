<?php

namespace CDesign\LdapBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('cdesign');

        $rootNode
            ->children()
                ->scalarNode('host')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('port')
                    ->min(0)
                    ->max(65536)
                    ->defaultValue(389)
                ->end()
                    ->scalarNode('base_dn')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->enumNode('naming_attribute')
                    ->values(array('upn', 'sam', 'both'))
                    ->defaultValue('both')
                ->end()
                ->booleanNode('allow_spaces')
                    ->defaultTrue()
                ->end()
                ->booleanNode('admin_bind')
                    ->defaultTrue()
                ->end()
                ->scalarNode('admin_dn')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('admin_password')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('roles')
                    ->defaultValue('ldap_roles')
                ->end()
                ->enumNode('roles_node')
                    ->values(array('cn', 'ou', 'dc'))
                    ->defaultValue('ou')
                ->end()
                ->booleanNode('debug_enable')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}