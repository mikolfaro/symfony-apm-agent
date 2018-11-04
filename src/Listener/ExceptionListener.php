<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 03/11/2018
 * Time: 09:05
 */

namespace MikolFaro\SymfonyApmAgentBundle\Listener;


use MikolFaro\SymfonyApmAgentBundle\Factory\ErrorMessageFactoryInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ExceptionListener
{
    private $errorMessageFactory;

    public function __construct(ErrorMessageFactoryInterface $errorMessageFactory)
    {
        $this->errorMessageFactory = $errorMessageFactory;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request = $event->getRequest();
        // $response = $event->getResponse();
        $exception = $event->getException();

        $errorMessage = $this->errorMessageFactory->build($exception);
        if ($request->attributes->has('apm_errors')) {
            $apmErrors = $request->attributes->get('apm_errors');
            $apmErrors[] = $errorMessage;
            $request->attributes->set('apm_errors', $apmErrors);
        } else {
            $request->attributes->set('apm_errors', [$errorMessage]);
        }
    }
}
