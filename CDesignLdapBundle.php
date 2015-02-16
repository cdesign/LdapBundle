<?php

namespace CDesign\LdapBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use CDesign\LdapBundle\DependencyInjection\CDesignLdapExtension;

class CDesignLdapBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new CDesignLdapExtension();
    }
}
