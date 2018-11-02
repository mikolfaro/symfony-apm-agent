<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 02/11/2018
 * Time: 15:41
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;
use TechDeCo\ElasticApmAgent\Request\Transaction as TransactionRequest;

interface TransactionRequestFactoryInterface
{
    public function build(OpenTransaction $openTransaction, Request $request, Response $response): TransactionRequest;
}
