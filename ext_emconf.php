<?php

$EM_CONF[$_EXTKEY] = [
    'author'           => 'Daniel Ablass',
    'author_email'     => 'dn@phantasie-schmiede.de',
    'category'         => 'plugin',
    'clearCacheOnLoad' => true,
    'constraints'      => [
        'conflicts' => [],
        'depends'   => [
            'foundation' => '3.0',
            'php'        => '8.3',
            'typo3'      => '13.4.0-13.4.99',
            'templates'  => '0.1.0',
        ],
        'suggests'  => [],
    ],
    'description'      => 'Create your own episode guide library.',
    'state'            => 'alpha',
    'title'            => 'PSBits | Episode Guide',
    'version'          => '0.1.0',
];
