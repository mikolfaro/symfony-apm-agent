<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 16:21
 */

namespace MikolFaro\SymfonyApmAgentBundle\DependencyInjection;


use MikolFaro\SymfonyApmAgentBundle\EmptyClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SymfonyApmAgentExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');

        if (
            'test' === $container->getParameter("kernel.environment")
        ) {
            $this->replaceDefaultClient($container);
        }

        foreach ($config as $key => $value) {
            $container->setParameter('symfony_apm_agent.' . $key, $value);
        }

        foreach ($config['listener_priorities'] as $key => $priority) {
            $container->setParameter('symfony_apm_agent.listener_priorities.' . $key, $priority);
        }
    }

    private function replaceDefaultClient(ContainerBuilder $container)
    {
        $emptyClientDefinition = new Definition();
        $emptyClientDefinition->setClass(EmptyClient::class);
        $emptyClientDefinition->setArguments([LoggerInterface::class]);
        $container->setDefinition('TechDeCo\ElasticApmAgent\Client', $emptyClientDefinition);
    }
}
