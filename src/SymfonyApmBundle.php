<?php
/**
 * Created by IntelliJ IDEA.
 * User: Mikol Faro <m.faro@engaged.it>
 * Date: 17/10/2018
 * Time: 10:27
 */

namespace MikolFaro\SymfonyApmAgentBundle;


use Jean85\PrettyVersions;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SymfonyApmBundle extends Bundle
{
    public static function getVersion(): string
    {
        return PrettyVersions::getVersion('mikolfaro/symfony-apm-agent')
            ->getPrettyVersion();
    }
}
