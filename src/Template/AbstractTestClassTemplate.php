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
}