<?php namespace FHIR\ComponentTests\Generator;

/**
 * Class ResourceTestClassGenerator
 * @package FHIR\ComponentTests\Generator
 */
class ResourceTestClassGenerator extends AbstractTestClassGenerator
{
    /**
     * @return string
     */
    protected function getTemplateClass()
    {
        return '\\FHIR\\ComponentTests\\Template\\ResourceTestClassTemplate';
    }
}