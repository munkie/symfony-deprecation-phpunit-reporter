<?php

namespace Deprecation;

use Exception;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

class DeprecationReporter extends \PHPUnit_Util_Printer implements \PHPUnit_Framework_TestListener
{
    /**
     * @var DeprecationErrorHandler
     */
    private $handler;

    /**
     * @var \PHPUnit_Util_Printer
     */
    private $printer;

    public function __construct($out)
    {
        parent::__construct($out);

        $this->handler = DeprecationErrorHandler::register();
        $this->printer = new \PHPUnit_TextUI_ResultPrinter($out);
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->printer->addError($test, $e, $time);
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        $this->printer->addFailure($test, $e, $time);
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->printer->addIncompleteTest($test, $e, $time);
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->printer->addRiskyTest($test, $e, $time);
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        $this->printer->addSkippedTest($test, $e, $time);
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->printer->startTestSuite($suite);
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        $this->printer->endTestSuite($suite);
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
        $this->printer->startTest($test);
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->printer->endTest($test, $time);
    }

    public function flush()
    {
        parent::flush();

        $this->handler->getDeprecations();
    }
}
