<?php namespace FHIR\ComponentTests\Template;

//{
//
//    $getter = 'get'.ucfirst($name);
//    $setter = 'set'.ucfirst($name);
//    $this->assertTrue(
//        method_exists($className, $getter),
//        'Property "'.$name.'" on class "'.$className.'" does not have a getter method.');
//    $this->assertTrue(
//        method_exists($className, $setter),
//        'Property "'.$name.'" on class "'.$className.'" does not have a setter method.');
//}

use DCarbone\FileObjectPlus;
use FHIR\ComponentTests\Util\MiscUtils;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag\VarTag;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ElementTestClassTemplate
 * @package FHIR\ComponentTests\Template
 *
 * TODO Determine location of Reflections and Helper methods, inline in each class / in util class / abstract to generator??
 */
class ElementTestClassTemplate
{
    /** @var string */
    private $sourceClassName;

    /** @var SplFileInfo */
    private $sourceClassFile;

    /** @var \ReflectionClass */
    private $sourceClass;

    /** @var array */
    private $singleProperties = array();

    /** @var array */
    private $collectionProperties = array();

    /** @var FileObjectPlus */
    private $fileObject;

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
    public function getTestClassName()
    {
        $fileName = $this->sourceClassFile->getFilename();
        return str_replace('.php', '', $fileName).'Test';
    }

    /**
     * @return bool|\ReflectionMethod
     */
    protected function anyParentHasConstructor()
    {
        $hasConstructor = false;
        $parent = $this->sourceClass->getParentClass();
        while (!$hasConstructor && $parent)
        {
            $hasConstructor = method_exists($parent->getName(), '__construct');
            $parent = $parent->getParentClass();
        }
        return $hasConstructor;
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

/**
 * This is an auto-generated test class, please do not modify.
 *
 * SourceClass: {$this->sourceClassName}
 * CreatedDate: {$now}
*/
class {$testClassName} extends \\PHPUnit_Framework_TestCase
{
PHP;
        $this->addConstructorDefinitionTest($classCode);

        return $classCode."\n}\n";
    }

    /**
     * @param string $classCode
     */
    protected function addConstructorDefinitionTest(&$classCode)
    {
        if (method_exists($this->sourceClassName, '__construct'))
        {
            if ($this->anyParentHasConstructor())
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
        $methodCode = MiscUtils::getMethodCode(
            $this->fileObject,
            $this->sourceClassName,
            '__construct',
            true);
        $methodCode = MiscUtils::prettyVarExport($methodCode);

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
        $methodCode = MiscUtils::getMethodCode(
            $this->fileObject,
            $this->sourceClassName,
            '__construct',
            true);
        $methodCode = MiscUtils::prettyVarExport($methodCode);

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
        foreach($this->collectionProperties as $propertyName=>$property)
        {
            /** @var \ReflectionProperty $property */

            $name = $property->getName();
            $docBlock = new DocBlock($property->getDocComment());
            foreach($docBlock->getTags() as $tag)
            {
                if ($tag instanceof VarTag)
                {
                    $exp = explode('|', $tag->getContent());
                    foreach($exp as $class)
                    {
                        if (false !== strpos($class, 'Collection'))
                        {
                            $collectionPropertyClass[$name] = $class;
                            break;
                        }
                    }
                }
            }
        }

        $collectionPropertyClass = MiscUtils::prettyVarExport($collectionPropertyClass);

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
        $classCode .= "\t}\n";
    }
}