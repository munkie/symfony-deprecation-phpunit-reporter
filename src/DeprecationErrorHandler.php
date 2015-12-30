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
        $this->deprecations = array();
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
        $oldErrorHandler = set_error_handler(array($handler, 'handle'), E_USER_DEPRECATED);

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
            return false;
            //return \PHPUnit_Util_ErrorHandler::handleError($type, $msg, $file, $line, $context);
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
     * @return string Group of deprecation resolved by test method name
     */
    private function getGroup(\ReflectionMethod $method = null)
    {
        $group = self::GROUP_OTHER;

        if (0 !== error_reporting()) {
            $group = self::GROUP_UNSILENCED;
        } elseif (null !== $method && $this->isLegacyTestMethod($method)) {
            $group = self::GROUP_LEGACY;
        } elseif (null !== $method) {
            $group = self::GROUP_REMAINING;
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
    }

    /**
     * @param string $group
     * @param string $message Deprecation message
     * @param \ReflectionMethod $method
     * @param array $trace
     */
    private function addDeprecation($group, $message, array $trace, \ReflectionMethod $method = null)
    {
        $this->deprecations[] = new Deprecation($group, $message, $trace, $method);
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
        return 0 === strpos($method->name, 'testLegacy')
        || 0 === strpos($method->name, 'provideLegacy')
        || 0 === strpos($method->name, 'getLegacy')
        || strpos($method->class, '\Legacy')
        || in_array('legacy', \PHPUnit_Util_Test::getGroups($method->class, $method->name), true);
    }

    /**
     * @return array
     */
    public function getDeprecations()
    {
        return $this->deprecations;
    }

}
