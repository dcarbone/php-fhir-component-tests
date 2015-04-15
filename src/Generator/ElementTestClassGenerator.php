<?php namespace FHIR\ComponentTests\Generator;

/**
 * Class ElementTestClassGenerator
 * @package FHIR\ComponentTests\Generator
 */
class ElementTestClassGenerator extends AbstractTestClassGenerator
{
    /**
     * @return string
     */
    protected function getTemplateClass()
    {
        return '\\FHIR\\ComponentTests\\Template\\ElementTestClassTemplate';
    }
}