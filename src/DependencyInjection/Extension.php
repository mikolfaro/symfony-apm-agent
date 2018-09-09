<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 09/09/2018
 * Time: 22:48
 */

namespace MikolFaro\SymfonyApmAgent\DependencyInjection;


use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use \Symfony\Component\HttpKernel\DependencyInjection\Extension as BaseExtension;

class Extension extends BaseExtension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        // ... you'll load the files here later
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../config')
        );
        $loader->load('services.yaml');
    }
}
