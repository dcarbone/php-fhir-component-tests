<?php namespace FHIR\ComponentTests\Command;

/**
 * Class GenerateElementTestsCommand
 * @package FHIR\ComponentTests\Command
 */
class GenerateElementTestsCommand extends AbstractTestGeneratorCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('generate:element-tests')
            ->setDescription('Generate test classes for php-fhir-elements package');
    }

    /**
     * @return string
     */
    protected function getSourceClassSearchDir()
    {
        return FHIR_ELEMENTS_LIB_DIR;
    }

    /**
     * @return string
     */
    protected function getTestClassOutputDir()
    {
        return FHIR_TEST_LIB_TEST_CLASS_DIR.'elements'.DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    protected function getGeneratorClassName()
    {
        return '\\FHIR\\ComponentTests\\Generator\\ElementTestClassGenerator';
    }

    /**
     * @return string
     */
    protected function getSourceClassBaseNamespace()
    {
        return '\\FHIR\\Elements\\';
    }
}