<?php

namespace Deprecation\Report;

use Deprecation\Deprecation;

class Output
{
    /**
     * @param Deprecation[] $deprecations
     */
    public function report(array $deprecations)
    {
        $grouped = array();
        foreach ($deprecations as $deprecation) {
            $grouped[$deprecation->getMessage()][$deprecation->getMethodFullName()] = $deprecation;
        }

        uasort(
            $grouped,
            function ($depsA, $depsB) {
                return count($depsB) - count($depsA);
            }
        );

        $index = 1;

        echo "\n";

        foreach ($grouped as $message => $depres) {
            echo "\n";
            $depsCount = count($depres);
            echo sprintf("%d) %dx %s:\n", $index++, $depsCount, $message);
            /** @var Deprecation $dep */
            foreach (array_slice($depres, 0, 3) as $dep) {
                echo sprintf("\t%s\n", $dep->getMethodFullName());
            }
            if ($depsCount > 3) {
                echo sprintf("\t.. and %d more\n", $depsCount - 3);
            }
        }
    }

}
