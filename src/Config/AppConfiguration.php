<?php declare(strict_types=1);

namespace DanielPieper\MergeReminder\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class AppConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app');

        $rootNode
            ->children()
                ->scalarNode('gitlab_url')->isRequired()->cannotBeEmpty()->defaultValue('https://gitlab.com')->end()
                ->scalarNode('gitlab_token')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('gitlab_projects')
                    ->requiresAtLeastOneElement()
                    ->integerPrototype()->end()
                ->end()
                ->scalarNode('slack_webhook_url')->isRequired()->cannotBeEmpty()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
