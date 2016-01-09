<?php

namespace CPANA\GeneratorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CPANAGeneratorBundle extends Bundle
{
    public function getParent()
    {
        return 'SensioGeneratorBundle';
    }
}
