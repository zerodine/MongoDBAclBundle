<?php

namespace PWalkow\MongoDBAclBundle\Tests\Functional\Security\Problematic\Domain;

use PWalkow\MongoDBAclBundle\Security\Problematic\Domain\AclManager;
use PWalkow\MongoDBAclBundle\Security\Problematic\Model\AclManagerInterface;
use PWalkow\MongoDBAclBundle\Tests\App\AbstractFunctionalTest;

class AclManagerTest extends AbstractFunctionalTest
{
    public function testServiceExistence()
    {
        $sut = $this->container->get('pwalkow.acl_manager');

        $this->assertInstanceOf(AclManagerInterface::class, $sut);
        $this->assertInstanceOf(AclManager::class, $sut);
    }
}