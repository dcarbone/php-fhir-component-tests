<?php namespace FHIR\ComponentTests\Command;

/**
 * Class GenerateResourceTestsCommand
 * @package FHIR\ComponentTests\Command
 */
class GenerateResourceTestsCommand extends AbstractTestGeneratorCommand
{
    /**
     * Configure this command
     */
    protected function configure()
    {
        $this
            ->setName('generate:resource-tests')
            ->setDescription('Generate test classes for php-fhir-resources package');
    }

    /**
     * @return string
     */
    protected function getSourceClassSearchDir()
    {
        return FHIR_RESOURCES_LIB_DIR;
    }

    /**
     * @return string
     */
    protected function getTestClassOutputDir()
    {
        return FHIR_TEST_LIB_TEST_CLASS_DIR.'resources'.DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    protected function getGeneratorClassName()
    {
        return '\\FHIR\\ComponentTests\\Generator\\ResourceTestClassGenerator';
    }

    /**
     * @return string
     */
    protected function getSourceClassBaseNamespace()
    {
        return '\\FHIR\\Resources\\';
    }
}