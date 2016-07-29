<?php

namespace PWalkow\MongoDBAclBundle\Tests\Security\Acl;

use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use PWalkow\MongoDBAclBundle\Security\Acl\AclProvider;

class AclProviderBenchmarkTest extends AbstractAclProviderTest
{
    const DATABASE_NAME = 'aclBenchmark';
    const NUMBER_OF_ROWS = 300;

    protected function setUp()
    {
        parent::setUp();
        // comment the following line, and run only this test, if you need to benchmark
        $this->markTestSkipped('Benchmarking skipped');
    }

    public function testFindAcls()
    {
        $this->generateTestData();

        // get some random test object identities from the database
        $oids = array();
        $max = $this->oidCollection->find()->count();

        for ($i = 0; $i < $max; $i++) {
            $randomKey = rand(0, $max);
            $oid = $this->oidCollection->findOne(['randomKey' => $randomKey]);

            var_dump($oid['identifier'], $oid['type']);

            $oids[] = new ObjectIdentity($oid['identifier'], $oid['type']);
        }

        $provider = $this->getProvider();

        $start = microtime(true);
        $provider->findAcls($oids);
        $time = microtime(true) - $start;
        echo "Total Time: " . $time . "s\n";
    }

    /**
     * This generates a huge amount of test data to be used mainly for benchmarking
     * purposes, not so much for testing. That's why it's not called by default.
     */
    protected function generateTestData()
    {
        $this->oidCollection->drop();
        $this->entryCollection->drop();
        $this->oidCollection->ensureIndex(array('randomKey' => 1), array());
        $this->oidCollection->ensureIndex(array('identifier' => 1, 'type' => 1));
        $this->entryCollection->ensureIndex(array('objectIdentity.$id' => 1));

        for ($i = 0; $i < self::NUMBER_OF_ROWS; $i++) {
            $this->generateAclHierarchy();
        }
    }

    protected function generateAclHierarchy()
    {
        $root = $this->generateAcl($this->chooseObjectIdentity(), null, array());

        $this->generateAclLevel(rand(1, 2), $root, array($root['_id']));
    }

    protected function generateAclLevel($depth, $parent, $ancestors)
    {
        $level = count($ancestors);
        for ($i = 0, $t = rand(1, 10); $i < $t; $i++) {
            $acl = $this->generateAcl($this->chooseObjectIdentity(), $parent, $ancestors);

            if ($level < $depth) {
                $this->generateAclLevel($depth, $acl, array_merge($ancestors, array($acl['_id'])));
            }
        }
    }

    protected function chooseObjectIdentity()
    {
        return array(
            'identifier' => $this->getRandomString(rand(20, 50)),
            'type' => $this->getRandomString(rand(20, 100)),
        );
    }

    protected function generateAcl($objectIdentity, $parent, $ancestors)
    {
        static $aclRandomKeyValue = 0; // used to retrieve random objects

        $acl = array_merge($objectIdentity,
                           array(
                                'entriesInheriting' => (boolean)rand(0, 1),
                                'randomKey' => $aclRandomKeyValue,
                           )
        );
        $aclRandomKeyValue++;
        if ($parent) {
            $acl['parent'] = $parent;
            $acl['ancestors'] = $ancestors;
        }

        $this->oidCollection->insert($acl);

        $this->generateAces($acl);

        return $acl;
    }

    protected function chooseSid()
    {
        if (rand(0, 1) == 0) {
            return array('role' => $this->getRandomString(rand(10, 20)));
        } else {
            return array(
                'username' => $this->getRandomString(rand(10, 20)),
                'class' => $this->getRandomString(rand(10, 20)),
            );
        }
    }

    protected function generateAces($acl)
    {
        $sids = array();
        $fieldOrder = array();

        for ($i = 0; $i <= 30; $i++) {
            $query = array();

            $fieldName = rand(0, 1) ? null : $this->getRandomString(rand(10, 20));

            if (rand(0, 5) != 0) {
                $query['objectIdentity'] = array(
                    '$ref' => AclProvider::OID_COLLECTION_NAME,
                    '$id' => $acl['_id'],
                );
            }

            do {
                $sid = $this->chooseSid();
                $sidId = implode('-', array_values($sid));
            }
            while (array_key_exists($sidId, $sids) && in_array($fieldName, $sids[$sidId], true));

            if (!isset($sids[$sidId])) {
                $sids[$sidId] = array();
            }

            $sids[$sidId][] = $fieldName;
            $query['securityIdentity'] = $sid;

            $fieldOrder[$fieldName] = array_key_exists($fieldName, $fieldOrder) ? $fieldOrder[$fieldName] + 1 : 0;

            $strategy = rand(0, 2);
            if ($strategy === 0) {
                $query['grantingStrategy'] = PermissionGrantingStrategy::ALL;
            }
            else if ($strategy === 1) {
                $query['grantingStrategy'] = PermissionGrantingStrategy::ANY;
            }
            else {
                $query['grantingStrategy'] = PermissionGrantingStrategy::EQUAL;
            }

            $query['fieldName'] = $fieldName;
            $query['aceOrder'] = $fieldOrder[$fieldName];
            $query['securityIdentity'] = $sid;
            $query['mask'] = $this->generateMask();
            $query['granting'] = (boolean)rand(0, 1);
            $query['auditSuccess'] = (boolean)rand(0, 1);
            $query['auditFailure'] = (boolean)rand(0, 1);

            $this->entryCollection->insert($query);
        }
    }

    protected function generateMask()
    {
        $i = rand(1, 30);
        $mask = 0;

        while ($i <= 30) {
            $mask |= 1 << rand(0, 30);
            $i++;
        }

        return $mask;
    }

    protected function getRandomString($length, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $s = '';
        $cLength = strlen($chars);

        while (strlen($s) < $length) {
            $s .= $chars[mt_rand(0, $cLength - 1)];
        }

        return $s;
    }

    /**
     * @return array
     */
    protected function getCollectionNames()
    {
        return array(
            'oid_collection' => 'aclObjectIdentities',
            'entry_collection' => 'aclEntries',
        );
    }

    /**
     * @return PermissionGrantingStrategy
     */
    protected function getStrategy()
    {
        return new PermissionGrantingStrategy();
    }

    /**
     * @return AclProvider
     */
    protected function getProvider()
    {
        return new AclProvider(
            $this->connection,
            self::DATABASE_NAME,
            $this->getStrategy(),
            $this->getCollectionNames()
        );
    }
}