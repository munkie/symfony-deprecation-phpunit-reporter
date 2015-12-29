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
        /** @var Deprecation[][] $grouped */
        $grouped = array();
        foreach ($deprecations as $deprecation) {
            $grouped[$deprecation->getMessage()][(string) $deprecation->getMethod()] = $deprecation;
        }

        foreach ($grouped as $message => $deps) {
            echo "\n";
            $depsCount = count($deps);
            echo sprintf("%s %dx:\n", $message, $depsCount);
            foreach (array_slice($deps, 0, 3) as $dep) {
                echo sprintf("\t%s::%s\n", $dep->getMethod()->class, $dep->getMethod()->name);
            }
            if ($depsCount > 3) {
                echo sprintf("\t.. and %d more\n", $depsCount - 3);
            }
        }
    }

}
