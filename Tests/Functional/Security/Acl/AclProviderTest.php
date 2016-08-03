<?php

namespace PWalkow\MongoDBAclBundle\Tests\Functional\Security\Acl;

use PWalkow\MongoDBAclBundle\Security\Acl\AclProvider;
use PWalkow\MongoDBAclBundle\Security\Acl\MutableAclProvider;
use PWalkow\MongoDBAclBundle\Tests\App\AbstractFunctionalTest;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

/**
 * @group Integration
 */
class AclProviderTest extends AbstractFunctionalTest
{
    public function testServiceExistence()
    {
        $provider = $this->container->get('security.acl.provider');

        $this->assertInstanceOf(AclProviderInterface::class, $provider);
        $this->assertInstanceOf(MutableAclProviderInterface::class, $provider);
        $this->assertInstanceOf(AclProvider::class, $provider);
        $this->assertInstanceOf(MutableAclProvider::class, $provider);
    }
}