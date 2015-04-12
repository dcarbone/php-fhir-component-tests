<?php namespace FHIR\ComponentTests;

define('FHIR_TEST_LIB_ROOT_DIR', realpath(__DIR__.'/../').DIRECTORY_SEPARATOR);
define('FHIR_TEST_LIB_SRC_DIR', FHIR_TEST_LIB_ROOT_DIR.'src'.DIRECTORY_SEPARATOR);
define('FHIR_TEST_LIB_TEST_CLASS_DIR', FHIR_TEST_LIB_ROOT_DIR.'tests'.DIRECTORY_SEPARATOR);


if (!is_dir(FHIR_TEST_LIB_TEST_CLASS_DIR))
{
    $ok = @mkdir(FHIR_TEST_LIB_TEST_CLASS_DIR);
    if (!$ok)
        throw new \RuntimeException('Unable to create Test Class output directory, please check permissions.');
}

use FHIR\ComponentTests\Command\GenerateElementTestsCommand;
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

        return $commands;
    }
}