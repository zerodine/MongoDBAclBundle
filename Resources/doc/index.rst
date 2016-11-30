MongoDB Acl Provider
====================

Installation using composer
---------------------------

To install MongoDBAclBundle using composer add following line to you composer.json file::

    # composer.json
    "pwalkow/mongodb-acl-bundle": "dev-master"

Use the composer update command to start the installation. After the installation add following line into the bundles array in your AppKernel.php file::

    # AppKernel.php
    new PWalkow\MongoDBAclBundle\PWalkowMongoDBAclBundle()

Configuration
-------------

To use the MongoDB Acl Provider, the minimal configuration is adding acl_provider to the MongoDb config in config.yml::

    # app/config/config.yml
    pwalkow_mongo_db_acl:
        acl_provider: 
            default_database: %mongodb_database_name%

The next requirement is to add the provider to the security configuration::

    # app/config/security.yml
    security:
        acl:
            provider: mongodb_acl_provider



The full acl provider configuration options are listed below::

    # app/config/config.yml
    iam_persistent_mongo_db_acl:
        acl_provider:
            default_database: ~
            collections:
                entry: ~
                object_identity: ~


To initialize the MongoDB ACL run the following command::

    php app/console init:acl:mongodb