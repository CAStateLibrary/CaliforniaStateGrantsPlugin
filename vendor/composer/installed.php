<?php return array(
    'root' => array(
        'pretty_version' => 'dev-trunk',
        'version' => 'dev-trunk',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => '55f95f0acf7b1dcb10ca83c451b051f002826cf4',
        'name' => '10up/ca-grants-plugin',
        'dev' => false,
    ),
    'versions' => array(
        '10up/ca-grants-plugin' => array(
            'pretty_version' => 'dev-trunk',
            'version' => 'dev-trunk',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => '55f95f0acf7b1dcb10ca83c451b051f002826cf4',
            'dev_requirement' => false,
        ),
        '10up/post-finder' => array(
            'pretty_version' => '0.4.0',
            'version' => '0.4.0.0',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../10up/post-finder',
            'aliases' => array(),
            'reference' => 'f236d99e4c4e7dce634abbc50bbe5c64316da416',
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
