<?php namespace FHIR\ComponentTests\Template;

use FHIR\ComponentTests\Util\ReflectionUtils;
use phpDocumentor\Reflection\DocBlock;

/**
 * Class AbstractTestClassTemplate
 * @package FHIR\ComponentTests\Template
 */
abstract class AbstractTestClassTemplate
{
    /**
     * @param string $sourceClassName
     * @param string $testClassName
     * @return string
     */
    public function getClassStartCode($sourceClassName, $testClassName)
    {
        $now = date('Y-m-d H:i:s e');
        return <<<PHP
<?php

use FHIR\ComponentTests\Util\ReflectionUtils;

/**
 * This is an auto-generated test class, please do not modify.
 *
 * @see {$sourceClassName}
 * @created {$now}
*/
class {$testClassName} extends \\PHPUnit_Framework_TestCase
{
    /** @var \\ReflectionClass */
    private \$sourceClass;

    /**
     * Test Setup
     */
    protected function setup()
    {
        \$this->sourceClass = new \\ReflectionClass('{$sourceClassName}');
    }

PHP;
    }

    /**
     * @param string $sourceClassName
     * @param string|array $constructorCode
     * @return string
     */
    public function getParentCallConstructorTestCode($sourceClassName, $constructorCode)
    {
        if (is_array($constructorCode))
            $constructorCode = ReflectionUtils::prettyVarExport($constructorCode);

        return <<<PHP

    /**
     * @covers {$sourceClassName}::__construct
     */
    public function testConstructorCallsParent()
    {
        \$parentIsCalled = false;
        foreach({$constructorCode} as \$line)
        {
            if (strpos(\$line, 'parent::__construct()') !== false)
            {
                \$parentIsCalled = true;
                break;
            }
        }

        \$this->assertTrue(
            \$parentIsCalled,
            'Constructor method does not call parent constructor!');
    }

PHP;
    }

    /**
     * @param $sourceClassName
     * @param array|string $constructorCode
     * @param array|string $collectionPropertyClasses
     * @return string
     */
    public function getCollectionPropertiesInitializedInConstructorTestCode($sourceClassName,
                                                                            $constructorCode,
                                                                            $collectionPropertyClasses)
    {
        if (is_array($constructorCode))
            $constructorCode = ReflectionUtils::prettyVarExport($constructorCode);

        if (is_array($collectionPropertyClasses))
            $collectionPropertyClasses = ReflectionUtils::prettyVarExport($collectionPropertyClasses);

        return <<<PHP

    /**
     * @covers {$sourceClassName}::__construct
     */
    public function testConstructorInitializesCollectionProperties()
    {
        \$propertyInitializations = array();
        foreach({$constructorCode} as \$line)
        {
            if (preg_match('{\\\$this->([a-zA-Z]+)\s=\snew\s([a-zA-Z]+)}S', trim(\$line), \$matches))
            {
                if (3 === count(\$matches))
                    \$propertyInitializations[\$matches[1]] = \$matches[2];
            }
        }

        \$collectionPropertyClasses = {$collectionPropertyClasses};

        ksort(\$propertyInitializations);
        ksort(\$collectionPropertyClasses);

        \$diff = array_diff_assoc(\$collectionPropertyClasses, \$propertyInitializations);

        \$this->assertCount(
            0,
            \$diff,
            'The following collection class properties are initialized incorrectly: ["'.implode('", "', array_keys(\$diff)).'"]');
    }

PHP;
    }

    /**
     * @param string $sourceClassName
     * @param string $propertyName
     * @return string
     */
    public function getGetterMethodExistsTestCode($sourceClassName, $propertyName)
    {
        $getter = 'get'.ucfirst($propertyName);
        $methodName = 'test'.ucfirst($getter).'MethodExists';
        return <<<PHP

    /**
     * @covers {$sourceClassName}::{$getter}
     */
    public function {$methodName}()
    {
        \$this->assertTrue(
            ReflectionUtils::classImplementsMethod(\$this->sourceClass, '{$getter}'),
            'Property "{$propertyName}" does not have a valid getter method (expected existence of method named "{$getter}").');
    }

PHP;
    }

    /**
     * @param string $sourceClassName
     * @param string $propertyName
     * @return string
     */
    public function getSinglePropertySetterMethodExistsCode($sourceClassName, $propertyName)
    {
        $setter = 'set'.ucfirst($propertyName);
        $methodName = 'test'.ucfirst($setter).'MethodExists';
        return <<<PHP

    /**
     * @covers {$sourceClassName}::{$setter}
     */
    public function {$methodName}()
    {
        \$this->assertTrue(
            ReflectionUtils::classImplementsMethod(\$this->sourceClass, '{$setter}'),
            'Property "{$propertyName}" does not have a valid setter method (expected existence of method named "{$setter}").');
    }

PHP;
    }

    /**
     * @param string $sourceClassName
     * @param string $propertyName
     * @return string
     */
    public function getCollectionPropertyAdderMethodExistsCode($sourceClassName, $propertyName)
    {
        $adder = 'add'.ucfirst($propertyName);
        $methodName = 'test'.ucfirst($adder).'MethodExists';
        return <<<PHP

    /**
     * @covers {$sourceClassName}::{$adder}
     */
    public function {$methodName}()
    {
        \$this->assertTrue(
            ReflectionUtils::classImplementsMethod(\$this->sourceClass, '{$adder}'),
            'Property "{$propertyName}" does not have a valid adder method (expected existence of method named "{$adder}").');
    }

PHP;
    }

    /**
     * @param string $sourceClassName
     * @param string|null $constructorClass
     * @return string
     */
    public function getObjectInitializationTestCode($sourceClassName, $constructorClass = null)
    {
        if ($constructorClass)
            $coversBlock = '@covers '.$constructorClass.'::__construct';
        else
            $coversBlock = '';

        return <<<PHP

    /**
     * {$coversBlock}
     * @return {$sourceClassName}
     */
    public function testCanInitializeObject()
    {
        \$object = new {$sourceClassName};

        \$this->assertInstanceOf(
            '{$sourceClassName}',
            \$object);

        return \$object;
    }

PHP;
    }
}