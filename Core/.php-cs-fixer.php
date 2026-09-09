<?php

    $finder = PhpCsFixer\Finder::create()
        ->in(__DIR__ . "/src");

    return (new PhpCsFixer\Config())
        ->setUnsupportedPhpVersionAllowed(true)
        ->setRules([
            "ordered_imports"      => ["sort_algorithm" => "alpha"],
            "no_unused_imports"    => true
        ])
        ->setFinder($finder);
?>