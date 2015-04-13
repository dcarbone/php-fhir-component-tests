<?php namespace FHIR\ComponentTests\Template;

use phpDocumentor\Reflection\DocBlock;

/**
 * Class ElementTestClassTemplate
 * @package FHIR\ComponentTests\Template
 *
 * TODO Determine location of Reflections and Helper methods, inline in each class / in util class / abstract to generator??
 */
class ElementTestClassTemplate extends AbstractTestClassTemplate
{
    /**
     * @return string
     */
    public function generateClassCode()
    {
        $testClassName = $this->getTestClassName();
        $now = date('Y-m-d H:i:s e');
        $classCode = <<<PHP
<?php

/**
 * This is an auto-generated test class, please do not modify.
 *
 * @see {$this->sourceClassName}
 * @created {$now}
*/
class {$testClassName} extends \\PHPUnit_Framework_TestCase
{
PHP;
        $this->addConstructorDefinitionTest($classCode);
        $this->addGetterMethodsExistenceTest($classCode);
        $this->addSinglePropertySetterMethodsExistenceTest($classCode);
        $this->addCollectionPropertyAdderMethodsExistenceTests($classCode);

        return $classCode."\n}\n";
    }
}