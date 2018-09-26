<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 30/09/2018
 * Time: 11:19
 */

namespace MikolFaro\SymfonyApmAgentBundle\Tests;


use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

trait MockUtils
{
    private $mockLogger;

    private $mockKernel;

    protected function setUpMocks()
    {
        $this->mockLogger = $this->buildMockLogger();
        $this->mockKernel = $this->buildMockKernel();
    }

    private function buildMockLogger()
    {
        return $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function buildMockKernel()
    {
        return $this->getMockBuilder(KernelInterface::class)->getMock();
    }
}
