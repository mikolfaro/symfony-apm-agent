<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 19/10/2018
 * Time: 10:49
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests\Unit\DependencyInjection;


use MikolFaro\SymfonyApmAgentBundle\DependencyInjection\SymfonyApmAgentExtension;
use MikolFaro\SymfonyApmAgentBundle\EmptyClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TechDeCo\ElasticApmAgent\Client\HttplugAsyncClient;

class SymfonyApmAgentBundleTest extends TestCase
{
    /** @var SymfonyApmAgentExtension */
    private $extension;
    private $root;
    /** @var ContainerBuilder */
    private $container;

    public function setUp()
    {
        parent::setUp();

        $this->extension = $this->buildExtension();
        $this->container = $this->buildContainer();
        $this->root      = "symfony_apm_bundle";
    }

    /**
     * @throws \Exception
     */
    public function testDefaultConfig(): void
    {
        $this->extension->load([], $this->container);

        $this->assertTrue($this->container->hasDefinition('TechDeCo\ElasticApmAgent\Client'));
        $this->assertEquals(
            HttplugAsyncClient::class,
            $this->container->getDefinition('TechDeCo\ElasticApmAgent\Client')->getClass()
        );
    }

    /**
     * @throws \Exception
     */
    public function testConfigForTestEnv()
    {
        $this->container->setParameter('kernel.environment', 'test');
        $this->extension->load([], $this->container);

        $this->assertTrue($this->container->hasDefinition('TechDeCo\ElasticApmAgent\Client'));
        $this->assertEquals(
            EmptyClient::class,
            $this->container->getDefinition('TechDeCo\ElasticApmAgent\Client')->getClass()
        );
    }

    protected function buildExtension(): SymfonyApmAgentExtension
    {
        return new SymfonyApmAgentExtension();
    }

    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('symfony_apm_agent.server_url', 'http://localhost:8100');
        $container->setParameter('kernel.environment', 'prod');
        return $container;
    }
}
