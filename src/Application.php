<?php namespace FHIR\ComponentTests;

use FHIR\ComponentTests\Command\GenerateElementTestsCommand;
use FHIR\ComponentTests\Command\GenerateResourceTestsCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Class Application
 * @package FHIR\ComponentTests
 */
class Application extends BaseApplication
{
    const APP_VERSION = 1.0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('PHP FHIR Component Tests Application', self::APP_VERSION);
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new GenerateElementTestsCommand();
        $commands[] = new GenerateResourceTestsCommand();

        return $commands;
    }
}