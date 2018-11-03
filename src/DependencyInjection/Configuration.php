<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 17/10/2018
 * Time: 18:43
 */

namespace MikolFaro\SymfonyApmAgentBundle\DependencyInjection;


use MikolFaro\SymfonyApmAgentBundle\Factory\OpenTransactionFactory;
use MikolFaro\SymfonyApmAgentBundle\Listener\CloseTransactionListener;
use MikolFaro\SymfonyApmAgentBundle\Listener\ExceptionListener;
use MikolFaro\SymfonyApmAgentBundle\Listener\OpenTransactionListener;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root('symfony_apm_agent');

        $rootNode
            ->children()
                ->scalarNode('open_transaction_listener')
                    ->defaultValue(OpenTransactionListener::class)
                ->end()
                ->scalarNode('exception_listener')
                    ->defaultValue(ExceptionListener::class)
                ->end()
                ->scalarNode('close_transaction_listener')
                    ->defaultValue(CloseTransactionListener::class)
                ->end()
                ->scalarNode('open_transaction_factory')
                    ->defaultValue(OpenTransactionFactory::class)
                ->end()
                ->scalarNode('server_url')
                    ->defaultValue('http://localhost:8200')
                ->end()
            ->end()
        ;

        $rootNode
            ->children()
                ->arrayNode('listener_priorities')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('request')->defaultValue(0)->end()
                        ->scalarNode('terminate')->defaultValue(20)->end()
                        ->scalarNode('exception')->defaultValue(100)->end()
                    ->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
