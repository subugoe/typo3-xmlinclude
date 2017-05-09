<?php
$finder = PhpCsFixer\Finder::create()
    ->exclude('build')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->in(__DIR__);
$config = PhpCsFixer\Config::create();
$config
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
return $config;
