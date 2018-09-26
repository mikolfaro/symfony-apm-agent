<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 14:06
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use TechDeCo\ElasticApmAgent\Message\Service;
use TechDeCo\ElasticApmAgent\Message\System;
use TechDeCo\ElasticApmAgent\Message\VersionedName;
use function \gethostname;
use function \php_uname;

class SystemFactory implements SystemFactoryInterface
{
    private $kernel;
    private $logger;

    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    public function buildSystem(): System
    {
        return (new System())
            ->atHost(gethostname())
            ->onArchitecture(php_uname('a'))
            ->onPlatform(php_uname('s'));
    }

    public function buildService(): Service
    {
        $agent = new VersionedName('techdeco/elastic-apm-agent', 'dev-master');
        $framework = new VersionedName('Symfony', Kernel::VERSION);
        $language = new VersionedName('PHP', PHP_VERSION);
        $runtime = new VersionedName(php_sapi_name(), '');

        $service = (new Service($agent, 'All4Contests'))
            ->usingFramework($framework)
            ->usingLanguage($language)
            ->withRuntime($runtime)
            ->inEnvironment($this->kernel->getEnvironment());
        return $service;
    }
}
