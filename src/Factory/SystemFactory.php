<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 14:06
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use GuzzleHttp\Psr7\Uri;
use Jean85\PrettyVersions;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use TechDeCo\ElasticApmAgent\Message\Context;
use TechDeCo\ElasticApmAgent\Message\Request as RequestMessage;
use TechDeCo\ElasticApmAgent\Message\Response as ResponseMessage;
use TechDeCo\ElasticApmAgent\Message\Service;
use TechDeCo\ElasticApmAgent\Message\System;
use TechDeCo\ElasticApmAgent\Message\Url;
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

    public function enrichContext(Context $context, Request $request, Response $response): Context
    {
        return $context
            ->withRequest($this->buildRequestMessage($request))
            ->withResponse($this->buildResponseMessage($response));
    }

    private function buildRequestMessage(Request $request): RequestMessage
    {
        $url = Url::fromUri(new Uri($request->getUri()));
        $requestMessage = new RequestMessage($request->getMethod(), $url);
        return $requestMessage;
    }

    private function buildResponseMessage(Response $response): ResponseMessage
    {
        return (new ResponseMessage())
            ->resultingInStatusCode($response->getStatusCode())
            ->thatIsFinished();
    }
}
