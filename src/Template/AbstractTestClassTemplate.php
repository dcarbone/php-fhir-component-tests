<?php namespace FHIR\ComponentTests\Template;

use DCarbone\FileObjectPlus;
use FHIR\ComponentTests\Util\ReflectionUtils;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag\VarTag;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class AbstractTestClassTemplate
 * @package FHIR\ComponentTests\Template
 */
abstract class AbstractTestClassTemplate
{
    /** @var string */
    protected $sourceClassName;

    /** @var SplFileInfo */
    protected $sourceClassFile;

    /** @var \ReflectionClass */
    protected $sourceClass;

    /** @var array */
    protected $singleProperties = array();

    /** @var array */
    protected $collectionProperties = array();

    /** @var FileObjectPlus */
    protected $fileObject;

    /**
     * @param $sourceClassName
     * @param SplFileInfo $sourceClassFile
     * @param \ReflectionClass $sourceClass
     */
    public function __construct($sourceClassName,
                                SplFileInfo $sourceClassFile,
                                \ReflectionClass $sourceClass)
    {
        $this->sourceClassName = $sourceClassName;
        $this->sourceClassFile = $sourceClassFile;
        $this->sourceClass = $sourceClass;
        $this->fileObject = new FileObjectPlus(
            $sourceClassFile->getPath().DIRECTORY_SEPARATOR.$sourceClassFile->getFilename());

        // TODO Implement this as a test to ensure that each property has a docblock
        // TODO Much of the below code is repeated further on, maybe clean it up??
        foreach($sourceClass->getProperties() as $property)
        {
            // We're only interested in properties directly declared on this class
            if ('\\'.$property->getDeclaringClass()->name == $sourceClassName)
            {
                $name = $property->getName();
                $docBlock = new DocBlock($property->getDocComment());
                foreach($docBlock->getTags() as $tag)
                {
                    if ($tag instanceof VarTag)
                    {
                        $content = $tag->getContent();
                        if (false === strpos($content, '[]'))
                            $this->singleProperties[$name] = $property;
                        else
                            $this->collectionProperties[$name] = $property;
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    public function generateClassCode()
    {
        $testClassName = $this->getTestClassName();
        $now = date('Y-m-d H:i:s e');
        $classCode = <<<PHP
<?php

use FHIR\ComponentTests\Util\ReflectionUtils;

/**
 * This is an auto-generated test class, please do not modify.
 *
 * @see {$this->sourceClassName}
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
        \$this->sourceClass = new \\ReflectionClass('{$this->sourceClassName}');
    }

PHP;
        $this->addConstructorDefinitionTest($classCode);
        $this->addGetterMethodsExistenceTest($classCode);
        $this->addSinglePropertySetterMethodsExistenceTest($classCode);
        $this->addCollectionPropertyAdderMethodsExistenceTests($classCode);
        $this->addInitializationTest($classCode);

        return $classCode."\n}\n";
    }

    /**
     * @return string
     */
    public function getTestClassName()
    {
        $fileName = $this->sourceClassFile->getFilename();
        return str_replace('.php', '', $fileName).'Test';
    }

    /**
     * @param string $classCode
     */
    protected function addConstructorDefinitionTest(&$classCode)
    {
        if (ReflectionUtils::classImplementsMethod($this->sourceClass, '__construct'))
        {
            if (ReflectionUtils::anyParentImplementsMethod($this->sourceClass, '__construct'))
                $this->addParentConstructorCallTest($classCode);

            if (0 < count($this->collectionProperties))
                $this->addCollectionPropertiesInitializedInConstructorTest($classCode);
        }
    }

    /**
     * If a parent of this class has a constructor, the child class must call
     * the parent constructor somewhere in it's own implementation
     *
     * @param string $classCode
     */
    protected function addParentConstructorCallTest(&$classCode)
    {
        $methodCode = ReflectionUtils::getMethodCode(
            $this->fileObject,
            $this->sourceClassName,
            '__construct',
            true);
        $methodCode = ReflectionUtils::prettyVarExport($methodCode);

        $classCode .= <<<PHP

    /**
     * @covers {$this->sourceClassName}::__construct
     */
    public function testConstructorCallsParent()
    {
        \$parentIsCalled = false;
        foreach({$methodCode} as \$line)
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
     * @param string $classCode
     */
    protected function addCollectionPropertiesInitializedInConstructorTest(&$classCode)
    {
        $methodCode = ReflectionUtils::getMethodCode(
            $this->fileObject,
            $this->sourceClassName,
            '__construct',
            true);
        $methodCode = ReflectionUtils::prettyVarExport($methodCode);

        $classCode .= <<<PHP

    /**
     * @covers {$this->sourceClassName}::__construct
     */
    public function testConstructorInitializesCollectionProperties()
    {
        \$propertyInitializations = array();
        foreach({$methodCode} as \$line)
        {
            if (preg_match('{\\\$this->([a-zA-Z]+)\s=\snew\s([a-zA-Z]+)}S', trim(\$line), \$matches))
            {
                if (3 === count(\$matches))
                    \$propertyInitializations[\$matches[1]] = \$matches[2];
            }
        }
PHP;
        // TODO Maybe move this into the test class directly??
        $collectionPropertyClass = array();
        foreach($this->collectionProperties as $propertyName=>$propertyReflection)
        {
            /** @var \ReflectionProperty $propertyReflection */

            $docBlock = new DocBlock($propertyReflection->getDocComment());
            foreach($docBlock->getTags() as $tag)
            {
                if ($tag instanceof VarTag)
                {
                    $exp = explode('|', $tag->getContent());
                    foreach($exp as $class)
                    {
                        if (false !== strpos($class, 'Collection'))
                        {
                            $collectionPropertyClass[$propertyName] = $class;
                            break;
                        }
                    }
                }
            }
        }

        $collectionPropertyClass = ReflectionUtils::prettyVarExport($collectionPropertyClass);

        $classCode .= <<<PHP

        \$collectionPropertyClass = {$collectionPropertyClass};

        ksort(\$propertyInitializations);
        ksort(\$collectionPropertyClass);

        \$diff = array_diff_assoc(\$collectionPropertyClass, \$propertyInitializations);

        \$this->assertCount(
            0,
            \$diff,
            'The following collection class properties are initialized incorrectly: ["'.implode('", "', array_keys(\$diff)).'"]');

PHP;
        $classCode .= "    }\n";
    }

    /**
     * @param string $classCode
     */
    protected function addGetterMethodsExistenceTest(&$classCode)
    {
        $properties = array_merge(array_keys($this->singleProperties), array_keys($this->collectionProperties));

        if (0 < count($properties))
        {
            $expectedGetters = array();

            foreach($properties as $property)
            {
                $expectedGetters[$property] = 'get'.ucfirst($property);
            }
            $classCode .= "\n    /**\n";
            foreach($expectedGetters as $propertyName=>$getter)
            {
                $classCode .= "     * @covers {$this->sourceClassName}::{$getter}\n";
            }

            $classCode .= "     */\n    public function testPropertyGetterMethodsExistence()\n    {\n";

            foreach($expectedGetters as $propertyName=>$getter)
            {
                $classCode .= <<<PHP
        \$this->assertTrue(
            ReflectionUtils::classImplementsMethod(\$this->sourceClass, '{$getter}'),
            'Property "{$propertyName}" does not have a valid getter method (expected existence of method named "{$getter}").');

PHP;
            }

            $classCode .= "    }\n";
        }
    }

    /**
     * TODO: This is pretty messy right now, clean it up.
     *
     * @param string $classCode
     */
    protected function addSinglePropertySetterMethodsExistenceTest(&$classCode)
    {
        if (0 < count($this->singleProperties))
        {
            $expectedSetters = array();

            foreach(array_keys($this->singleProperties) as $propertyName)
            {
                /** @var \ReflectionProperty $property */
                $expectedSetters[$propertyName] = 'set'.ucfirst($propertyName);
            }

            $classCode .= "\n    /**\n";
            foreach($expectedSetters as $propertyName=>$setter)
            {
                $classCode .= "     * @covers {$this->sourceClassName}::{$setter}\n";
            }

            $classCode .= "     */\n    public function testSinglePropertySetterMethodsExistence()\n    {\n";

            foreach($expectedSetters as $propertyName=>$setter)
            {
                $classCode .= <<<PHP
        \$this->assertTrue(
            ReflectionUtils::classImplementsMethod(\$this->sourceClass, '{$setter}'),
            'Property "{$propertyName}" does not have a valid setter method (expected existence of method named "{$setter}").');

PHP;
            }

            $classCode .= "    }\n";
        }
    }

    /**
     * TODO: This is pretty messy right now, clean it up.
     *
     * @param string $classCode
     */
    protected function addCollectionPropertyAdderMethodsExistenceTests(&$classCode)
    {
        if (0 < count($this->collectionProperties))
        {
            $expectedAdders = array();

            foreach(array_keys($this->collectionProperties) as $propertyName)
            {
                /** @var \ReflectionProperty $property */
                $expectedAdders[$propertyName] = 'add'.ucfirst($propertyName);
            }

            $classCode .= "\n    /**\n";
            foreach($expectedAdders as $propertyName=>$adder)
            {
                $classCode .= "     * @covers {$this->sourceClassName}::{$adder}\n";
            }

            $classCode .= "     */\n    public function testCollectionPropertyAdderMethodsExistence()\n    {\n";

            foreach($expectedAdders as $propertyName=>$adder)
            {
                $classCode .= <<<PHP
        \$this->assertTrue(
            ReflectionUtils::classImplementsMethod(\$this->sourceClass, '{$adder}'),
            'Property "{$propertyName}" does not have a valid adder method (expected existence of method named "{$adder}").');

PHP;

            }

            $classCode .= "    }\n";
        }
    }

    /**
     * @param string $classCode
     */
    protected function addInitializationTest(&$classCode)
    {
        if (ReflectionUtils::classImplementsMethod($this->sourceClass, '__construct'))
            $constructorClass = $this->sourceClassName;
        else if ($parent = ReflectionUtils::getParentThatImplementsMethod($this->sourceClass, '__construct'))
            $constructorClass = '\\'.$parent->getName();
        else
            $constructorClass = null;

        if ($constructorClass)
            $coversBlock = '@covers '.$constructorClass.'::__construct';
        else
            $coversBlock = '';

        $classCode .= <<<PHP

    /**
     * {$coversBlock}
     * @return {$this->sourceClassName}
     */
    public function testCanInitializeObject()
    {
        \$object = new {$this->sourceClassName};

        \$this->assertInstanceOf(
            '{$this->sourceClassName}',
            \$object);

        return \$object;
    }
PHP;
    }
}