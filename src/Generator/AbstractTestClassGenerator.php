<?php namespace FHIR\ComponentTests\Generator;

use DCarbone\FileObjectPlus;
use FHIR\ComponentTests\Util\ReflectionUtils;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag\VarTag;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class AbstractTestClassGenerator
 * @package FHIR\ComponentTests\Generator
 */
abstract class AbstractTestClassGenerator
{
    /** @var string */
    protected $sourceClassName;

    /** @var SplFileInfo */
    protected $sourceClassFile;

    /** @var \ReflectionClass */
    protected $sourceClass;

    /** @var \ReflectionProperty[] */
    protected $singleProperties = array();

    /** @var \ReflectionProperty[] */
    protected $collectionProperties = array();

    /** @var FileObjectPlus */
    protected $fileObject;

    /** @var \FHIR\ComponentTests\Template\AbstractTestClassTemplate */
    protected $templateClass;

    /**
     * @param $sourceClassName
     * @param SplFileInfo $sourceClassFile
     * @param \ReflectionClass $sourceClass
     */
    public function __construct($sourceClassName,
                                SplFileInfo $sourceClassFile,
                                \ReflectionClass $sourceClass)
    {
        $templateClass = $this->getTemplateClass();
        $this->templateClass = new $templateClass;

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
    abstract protected function getTemplateClass();

    /**
     * @return string
     */
    public function generateClassCode()
    {
        $classCode = $this->templateClass->getClassStartCode(
            $this->sourceClassName,
            $this->getTestClassName());

        $this->addConstructorDefinitionTest($classCode);
        $this->addGetterMethodsExistsTest($classCode);
        $this->addSinglePropertySetterMethodsExistsTest($classCode);
        $this->addCollectionPropertyAdderMethodsExistsTests($classCode);
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

        $classCode .= $this->templateClass->getParentCallConstructorTestCode(
            $this->sourceClassName,
            ReflectionUtils::prettyVarExport($methodCode));
    }

    /**
     * @param string $classCode
     */
    protected function addCollectionPropertiesInitializedInConstructorTest(&$classCode)
    {
        $constructorCode = ReflectionUtils::getMethodCode(
            $this->fileObject,
            $this->sourceClassName,
            '__construct',
            true);

        $collectionPropertyClasses = array();
        foreach($this->collectionProperties as $propertyName=>$propertyReflection)
        {
            foreach(ReflectionUtils::getClassesFromPropertyDocBlock($propertyReflection, true) as $class)
            {
                if (false !== strpos($class, 'Collection'))
                {
                    $collectionPropertyClasses[$propertyName] = $class;
                    break;
                }
            }
        }

        $classCode .= $this->templateClass->getCollectionPropertiesInitializedInConstructorTestCode(
            $this->sourceClassName,
            ReflectionUtils::prettyVarExport($constructorCode),
            ReflectionUtils::prettyVarExport($collectionPropertyClasses));
    }

    /**
     * @param string $classCode
     */
    protected function addGetterMethodsExistsTest(&$classCode)
    {
        $propertyNames = array_merge(array_keys($this->singleProperties), array_keys($this->collectionProperties));

        if (0 < count($propertyNames))
        {
            foreach($propertyNames as $propertyName)
            {
                $classCode .= $this->templateClass->getGetterMethodExistsTestCode($this->sourceClassName, $propertyName);
            }
        }
    }

    /**
     * @param string $classCode
     */
    protected function addSinglePropertySetterMethodsExistsTest(&$classCode)
    {
        if (0 < count($this->singleProperties))
        {
            foreach(array_keys($this->singleProperties) as $propertyName)
            {
                $classCode .= $this->templateClass->getSinglePropertySetterMethodExistsCode($this->sourceClassName, $propertyName);
            }
        }
    }

    /**
     * TODO: This is pretty messy right now, clean it up.
     *
     * @param string $classCode
     */
    protected function addCollectionPropertyAdderMethodsExistsTests(&$classCode)
    {
        if (0 < count($this->collectionProperties))
        {
            foreach(array_keys($this->collectionProperties) as $propertyName)
            {
                $classCode .= $this->templateClass->getCollectionPropertyAdderMethodExistsCode($this->sourceClassName, $propertyName);
            }
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

        $classCode .= $this->templateClass->getObjectInitializationTestCode($this->sourceClassName, $constructorClass);
    }
}