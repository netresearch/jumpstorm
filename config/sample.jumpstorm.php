<?php
/* Sample php configuration file for jumpstorm */
return array(
    // some settings to be asked if not set
    'ask' => array(
        "common.magento.target",
        "common.db.name"
    ),
    // some settings to be explicitly confirmed by the user
    'confirm' => array(
        "common.magento.target",
        "common.db.name"
    ),
    'common' => array(
        'magento' => array(
            'target' => '/home/user/public/htdocs',
            'version'=> ''
        ),
        'db' => array(
            'name'   => 'magento',
            'host'   => 'localhost',
            'user'   => 'root',
            'pass'   => '<your_database_password>',
            'prefix' => null
        ),
        'backup' => array(
            'target' => null
        )
    ),
    'magento' => array(
        'source'         => 'git://github.com/LokeyCoding/magento-mirror.git',
        'branch'         => 'magento-1.6.2.0',
        'baseUrl'        => 'mymachine.local',
        'adminFirstname' => 'Firstname',
        'adminLastname'  => 'Lastname',
        'adminEmail'     => 'firstname.lastname@example.org',
        'adminUser'      => 'admin',
        'adminPass'      => 'admin123',

        'sampledata' => array(
            'source' => 'git://git.example.org/magento/sampledata.git',
            'branch' => '1.6.1.0'
        )
    ),

    'unittesting' => array(
        'framework' => 'ecomdev',
        'extension' => array(
            'source' => 'git://github.com/EcomDev/EcomDev_PHPUnit.git',
            'branch' => 'master'
        )
    ),

    'extensions' => array(
    //    'my_ext' => array(
    //        'source' => 'git@git.example.org:extensions/my_ext.git',
    //        'branch' => 'master', // optional

        'fooman_speedster' => 'magentoconnect://community/Fooman_Speedster',

        'germansetup'      => 'git://github.com/firegento/firegento-germansetup.git',

    //    /* simple product import, required by plugin DummyProducts */
    //    'FastSimpleImport'  => 'git://github.com/avstudnitz/AvS_FastSimpleImport.git',

    ),
    /* settings needed by the plugins to be executed */
    'plugins' => array(
        'DisableAdminNotifications' => array(
            'enabled' => '0'
        ),

        'CreateBackendUser' => array(
            'ini' => 'plugins/CreateBackendUser/CreateBackendUser.sample.ini'
        ),

        'ApplyConfigSettings' => array(
            'design/head/demonotice' => 1
        ),

        'FlushCache' => 1,

        # add 15 sample products
        # requires sample data
        # requires AvS_FastSimpleImport
        #'DummyProducts' => array(
        #   'simpleProducts' => 15
        #),

        'Reindex' => array(
            //'catalog_product_attribute',
            'catalog_product_price',
            'catalog_url',
            //'catalog_product_flat',
            //'catalog_category_flat',
            //'catalog_category_product',
            //'catalogsearch_fulltext',
            'cataloginventory_stock',
            //'tag_summary',
        )
    )
);
