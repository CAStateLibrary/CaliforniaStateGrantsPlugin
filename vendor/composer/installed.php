<?php return array(
    'root' => array(
        'name' => '10up/ca-grants-plugin',
        'pretty_version' => 'dev-trunk',
        'version' => 'dev-trunk',
        'reference' => '0697c7580b7e409bbec08f97101daa517aab4e7b',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        '10up/ca-grants-plugin' => array(
            'pretty_version' => 'dev-trunk',
            'version' => 'dev-trunk',
            'reference' => '0697c7580b7e409bbec08f97101daa517aab4e7b',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        '10up/post-finder' => array(
            'pretty_version' => '0.4.0',
            'version' => '0.4.0.0',
            'reference' => 'f236d99e4c4e7dce634abbc50bbe5c64316da416',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../10up/post-finder',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
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
