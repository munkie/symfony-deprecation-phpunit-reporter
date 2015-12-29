<?php

namespace Deprecation\Report;

use Deprecation\Deprecation;

class Checkstyle
{
    /**
     * Xml writter for checkstyle xml report file
     *
     * @var \XMLWriter
     */
    private $xml;

    /**
     * Checkstyle constructor.
     *
     * @param string $filename Filename to save report to
     */
    public function __construct($filename)
    {
        $this->initWriter($filename);
    }

    /**
     * Write and save xml report
     *
     * @param Deprecation[] $deprecations List of deprecations
     */
    public function write(array $deprecations)
    {
        $this->start();
        foreach ($deprecations as $deprecation) {
            $this->writeDeprecation($deprecation);
        }
        $this->end();
        $this->flush();
    }

    /**
     * @param Deprecation $deprecation
     */
    private function writeDeprecation(Deprecation $deprecation)
    {
        // start file tag
        $this->xml->startElement('file');
        $this->xml->writeAttribute('name', $deprecation->getMethod()->getFileName());
        // start error tag
        $this->xml->startElement('error');
        $this->xml->writeAttribute('line', $deprecation->getMethod()->getStartLine());
        $this->xml->writeAttribute('column', 1);
        $this->xml->writeAttribute('severity', $deprecation->getGroup());
        $this->xml->writeAttribute('source', $deprecation->getMessage());
        $this->xml->writeAttribute('message', $deprecation->getMessage());
        // close error tag
        $this->xml->endElement();
        // close file tag
        $this->xml->endElement();
    }

    /**
     * Init writter
     *
     * @param string $filename
     */
    private function initWriter($filename)
    {
        $this->xml = new \XMLWriter();
        $this->xml->openUri($filename);
        $this->xml->setIndent(true);
    }

    /**
     * Write xml start
     */
    private function start()
    {
        $this->xml->startDocument('1.0', 'UTF-8');
        $this->xml->startElement('checkstyle');
        $this->xml->writeAttribute('version', '2.5.0');
    }

    /**
     * Write xml end
     */
    private function end()
    {
        // close checkstyle element
        $this->xml->endElement();
    }

    /**
     * Save xml file
     */
    private function flush()
    {
        $this->xml->flush();
    }
}
