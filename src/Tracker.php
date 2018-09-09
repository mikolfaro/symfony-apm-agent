<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 09/09/2018
 * Time: 17:27
 */

namespace MikolFaro\SymfonyApmAgent;


use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class Tracker
{
    private $apmReporter;
    private $logger;
    private $kernel;
    private $stopWatch;

    public function __construct(
        KernelInterface $kernel,
        LoggerInterface $logger,
        Reporter $apmReporter,
        Stopwatch $stopWatch
    ) {
        $this->apmReporter = $apmReporter;
        $this->logger = $logger;
        $this->kernel = $kernel;

        $this->apmDataAfterResponse();
        $this->stopWatch = $stopWatch;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->apmReporter->reportException($event->getException(), $event->getRequest(), $event->getResponse());
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->stopWatch->start('apm.requestTiming');
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $requestTiming = $this->stopWatch->stop('apm.requestTiming');
        $this->apmReporter->reportRequest($event->getRequest(), $event->getResponse(), $requestTiming);
    }

    private function apmDataAfterResponse()
    {
        if (!function_exists('fastcgi_finish_request')) {
            $this->logger->warning(get_class($this) . " relies on kernel.terminate Event to send data to Elastic APM");
            $this->logger->warning("This event blocks server response, please use PHP-FPM");
            $this->logger->warning("https://symfony.com/doc/current/components/http_kernel.html#the-kernel-terminate-event");
        }
    }
}