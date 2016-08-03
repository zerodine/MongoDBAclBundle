<?php

namespace PWalkow\MongoDBAclBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Set the indexes required by the MongoDB ACL provider
 *
 * @author Richard Shank <develop@zestic.com>
 */
class InitAclMongoDBCommand extends ContainerAwareCommand
{
    const NAME = 'init:acl:mongodb';
    const MESSAGE_SUCCESS = 'ACL indexes have been initialized successfully.';

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Set the indexes required by the MongoDB ACL provider')
        ;
    }

    /**
     * @see Command
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // todo: change services and parameters when the configuration has been finalized
        $container = $this->getContainer();
        $mongo = $container->get('doctrine_mongodb.odm.default_connection');
        $dbName = $container->getParameter('doctrine_mongodb.odm.security.acl.database');
        $db = $mongo->selectDatabase($dbName);

        $oidCollection = $db->selectCollection($container->getParameter('doctrine_mongodb.odm.security.acl.oid_collection'));
        $oidCollection->ensureIndex(['randomKey' => 1], []);
        $oidCollection->ensureIndex(['identifier' => 1, 'type' => 1]);

        $entryCollection = $db->selectCollection($container->getParameter('doctrine_mongodb.odm.security.acl.entry_collection'));
        $entryCollection->ensureIndex(['objectIdentity.$id' => 1]);

        $output->writeln(self::MESSAGE_SUCCESS);
    }
}
