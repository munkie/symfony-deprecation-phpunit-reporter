<?php

namespace Deprecation;

use Deprecation\Report\Checkstyle;
use Deprecation\Report\Output;
use Exception;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

class DeprecationListener extends \PHPUnit_Util_Printer implements \PHPUnit_Framework_TestListener
{
    /**
     * @var DeprecationErrorHandler
     */
    private $handler;

    public function __construct()
    {
        parent::__construct(null);

        $this->handler = DeprecationErrorHandler::register();
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
    }

    public function startTest(PHPUnit_Framework_Test $test)
    {
    }

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
    }

    public function flush()
    {
        parent::flush();

        $this->saveCheckstyleReport();
        $this->printOutputReport();
    }

    private function saveCheckstyleReport()
    {
        $checkstyleFile = getenv('DEPRECATIONS_CHECKSTYLE');

        if ($checkstyleFile) {
            $checkstyle = new Checkstyle($checkstyleFile);
            $checkstyle->report($this->handler->getDeprecations());
        }
    }

    private function printOutputReport()
    {
        $report = new Output();
        $report->report($this->handler->getDeprecations());
    }

}
