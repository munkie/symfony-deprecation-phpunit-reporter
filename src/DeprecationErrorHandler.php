<?php

namespace Deprecation;

class DeprecationErrorHandler
{
    /**
     * Report mode
     */
    const MODE_WEAK = 'weak';
    const MODE_STRICT = 'strict';

    /**
     * Deprecation groups
     */
    const GROUP_UNSILENCED = 'unsilenced';
    const GROUP_LEGACY = 'legacy';
    const GROUP_REMAINING = 'remaining';
    const GROUP_OTHER = 'other';

    /**
     * @var bool
     */
    private static $isRegistered = false;

    /**
     * List of registered deprecations
     *
     * @var array
     */
    private $deprecations = array();

    /**
     * DeprecationErrorHandler constructor.
     *
     * @param string $mode
     */
    public function __construct()
    {
        $this->deprecations = array(
            self::GROUP_UNSILENCED => array(),
            self::GROUP_REMAINING => array(),
            self::GROUP_LEGACY => array(),
            self::GROUP_OTHER => array(),
        );
    }

    /**
     * Registers and configures the deprecation handler.
     *
     * @param string $mode The reporting mode. Defaults to not allowing any deprecations.
     *
     * @return DeprecationErrorHandler|null
     */
    public static function register()
    {
        if (self::$isRegistered) {
            return null;
        }

        $handler = new self();
        $oldErrorHandler = set_error_handler(array($handler, 'handle'));

        if (null !== $oldErrorHandler) {
            restore_error_handler();
            if (array('PHPUnit_Util_ErrorHandler', 'handleError') === $oldErrorHandler) {
                restore_error_handler();
                self::register();
            }
        }

        self::$isRegistered = true;

        return $handler;
    }

    /**
     * @param string $type
     * @param string $msg
     * @param string $file
     * @param int $line
     * @param $context
     *
     * @return bool
     */
    public function handle($type, $msg, $file, $line, $context)
    {
        if (E_USER_DEPRECATED !== $type) {
            //return false; ?
            return \PHPUnit_Util_ErrorHandler::handleError($type, $msg, $file, $line, $context);
        }

        $debugOptions = PHP_VERSION_ID >= 50400 ? DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT : true;
        $trace = debug_backtrace($debugOptions);

        $method = $this->findDeprecatedMethod($trace);

        $group = $this->getGroup($method);

        $this->addDeprecation($group, $msg, $trace, $method);
    }

    /**
     * @param array $trace
     * @return \ReflectionMethod|null
     */
    private function findDeprecatedMethod(array $trace)
    {
        $i = count($trace);
        while (isset($trace[--$i]['class'])
            && ('ReflectionMethod' === $trace[$i]['class'] || 0 === strpos($trace[$i]['class'], 'PHPUnit_'))
        ) {
            // No-op
        }
        if (isset($trace[$i]['object']) || isset($trace[$i]['class'])) {
            $class = isset($trace[$i]['object']) ? get_class($trace[$i]['object']) : $trace[$i]['class'];
            $method = $trace[$i]['function'];
            return new \ReflectionMethod($class, $method);
        } else {
            return null;
        }
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    private function getGroup(\ReflectionMethod $method = null)
    {
        if (0 !== error_reporting()) {
            $group = self::GROUP_UNSILENCED;
        } elseif (null !== $method && $this->isLegacyTestMethod($method)) {
            $group = self::GROUP_LEGACY;
        } elseif (null !== $method) {
            $group = self::GROUP_REMAINING;
        } else {
            $group = self::GROUP_OTHER;
        }
        return $group;
    }

    /**
     * @param $msg
     * @param $trace
     * @param $i
     * @param $group
     * @param $class
     * @param $method
     */
    private function printErrorStackTrace($msg, $trace, $i, $group, $class, $method)
    {
        $e = new \Exception($msg);
        $r = new \ReflectionProperty($e, 'trace');
        $r->setAccessible(true);
        $r->setValue($e, array_slice($trace, 1, $i));

        echo "\n" . ucfirst($group) . ' deprecation triggered by ' . $class . '::' . $method . ':';
        echo "\n" . $msg;
        echo "\nStack trace:";
        echo "\n" . str_replace(' ' . getcwd() . DIRECTORY_SEPARATOR, ' ', $e->getTraceAsString());
        echo "\n";

        exit(1);
    }

    /**
     * @param string $group
     * @param string $msg Deprecation message
     * @param \ReflectionMethod $method
     * @param array $trace
     */
    private function addDeprecation($group, $msg, array $trace, \ReflectionMethod $method = null)
    {
        $this->deprecations[$group][$msg][] = array(
            'method' => $method ? $method->class.'::'.$method->name : null,
            //'trace' => $trace
        );
    }

    /**
     * Check if method where deprecation happened is marked as legacy
     *
     * @param \ReflectionMethod  $method
     *
     * @return bool
     */
    private function isLegacyTestMethod(\ReflectionMethod $method)
    {
        return 0 === strpos($method->getName(), 'testLegacy')
        || 0 === strpos($method->getName(), 'provideLegacy')
        || 0 === strpos($method->getName(), 'getLegacy')
        || strpos($method->getNamespaceName(), '\Legacy')
        || in_array('legacy', \PHPUnit_Util_Test::getGroups($method->class, $method->name), true);
    }

    private function finish($deprecationHandler, $colorize, $deprecations, $mode)
    {
        $currErrorHandler = set_error_handler('var_dump');
        restore_error_handler();

        if ($currErrorHandler !== $deprecationHandler) {
            echo "\n", $colorize('THE ERROR HANDLER HAS CHANGED!', true), "\n";
        }

        $cmp = function ($a, $b) {
            return $b['count'] - $a['count'];
        };

        foreach (['unsilenced', 'remaining', 'legacy', 'other'] as $group) {
            if ($deprecations[$group . 'Count']) {
                echo "\n", $colorize(sprintf('%s deprecation notices (%d)', ucfirst($group), $deprecations[$group . 'Count']), 'legacy' !== $group), "\n";

                uasort($deprecations[$group], $cmp);

                foreach ($deprecations[$group] as $msg => $notices) {
                    echo "\n", rtrim($msg, '.'), ': ', $notices['count'], "x\n";

                    arsort($notices);

                    foreach ($notices as $method => $count) {
                        if ('count' !== $method) {
                            echo '    ', $count, 'x in ', preg_replace('/(.*)\\\\(.*?::.*?)$/', '$2 from $1', $method), "\n";
                        }
                    }
                }
            }
        }
        if (!empty($notices)) {
            echo "\n";
        }
        if (DeprecationErrorHandler::MODE_WEAK !== $mode && $mode < $deprecations['unsilencedCount'] + $deprecations['remainingCount'] + $deprecations['otherCount']) {
            exit(1);
        }
    }

    /**
     * @return array
     */
    public function getDeprecations()
    {
        return $this->deprecations;
    }

}
