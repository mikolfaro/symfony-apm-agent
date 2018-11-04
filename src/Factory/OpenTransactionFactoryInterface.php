<?php
declare(strict_types=1);
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 17/10/2018
 * Time: 20:58
 */

namespace MikolFaro\SymfonyApmAgentBundle\Factory;

use Symfony\Component\HttpFoundation\Request;
use TechDeCo\ElasticApmAgent\Convenience\OpenTransaction;

interface OpenTransactionFactoryInterface
{
    public function build(Request $request): OpenTransaction;
}
