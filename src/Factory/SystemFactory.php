<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 14:06
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use Jean85\PrettyVersions;
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
    const PACKAGE_NAME = 'mikolfaro/symfony-apm-agent';

    private $kernel;
    private $logger;

    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }

    public function buildSystem(): System
    {
        $system = (new System())
            ->onArchitecture(php_uname('a'))
            ->onPlatform(php_uname('s'));

        $hostname = gethostname();
        if ($hostname) {
            $system = $system->atHost($hostname);
        }

        return $system;
    }

    public function buildService(): Service
    {
        $version = PrettyVersions::getVersion(self::PACKAGE_NAME);
        $agent = new VersionedName(self::PACKAGE_NAME, $version->getPrettyVersion());
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
