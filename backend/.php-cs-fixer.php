<?php

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR2'           => true,
        'array_syntax'    => ['syntax' => 'short'],
        'ordered_imports' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([
                __DIR__ . '/app',
                __DIR__ . '/Modules/*/app',
            ])
            ->name('*.php'),
    );
