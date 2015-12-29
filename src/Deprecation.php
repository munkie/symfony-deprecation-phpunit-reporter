<?php

namespace Deprecation;

class Deprecation
{
    /**
     * Deprecation group
     *
     * @var string
     */
    protected $group;

    /**
     * Deprecation message
     *
     * @var string
     */
    protected $message;

    /**
     * Test method where deprecation happened
     *
     * @var \ReflectionMethod
     */
    protected $method;

    /**
     * Trace
     *
     * @var array
     */
    protected $trace;

    /**
     * Deprecation constructor.
     *
     * @param string $group
     * @param string $message
     * @param \ReflectionMethod|null $method
     * @param array $trace
     */
    public function __construct($group, $message, array $trace, \ReflectionMethod $method = null)
    {
        $this->group = $group;
        $this->message = $message;
        $this->method = $method;
        $this->trace = $trace;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \ReflectionMethod|null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getTrace()
    {
        return $this->trace;
    }
}
