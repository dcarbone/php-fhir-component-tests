<?php

use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag\VarTag;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ElementClassDefinitionTest
 */
class ElementClassDefinitionTest extends \PHPUnit_Framework_TestCase
{
    /** @var Finder */
    protected $elementClassFinder;

    /** @var string */
    protected $elementNamespace = '\\FHIR\\Elements\\';

    /**
     * Build Internal array of FHIR Element classes
     */
    protected function setup()
    {

    }

    /**
     *
     */
    public function testSingleItemClassPropertyDefinitions()
    {
        foreach($this->elementClassFinder as $classFile)
        {
            /** @var SplFileInfo $classFile */
            $className = $this->elementNamespace.str_replace(array('\\', 'src/', '/', '.php'), array('/', '', '\\', ''), $classFile->getRelativePathname());

            $reflection = new \ReflectionClass($className);

            foreach($reflection->getProperties() as $property)
            {
                $name = $property->getName();

                $docBlock = new DocBlock($property->getDocComment());
                foreach($docBlock->getTags() as $tag)
                {
                    if ($tag instanceof VarTag)
                    {
                        $content = $tag->getContent();
                        if (false === strpos($content, '[]'))
                        {
                            $getter = 'get'.ucfirst($name);
                            $setter = 'set'.ucfirst($name);
                            $this->assertTrue(
                                method_exists($className, $getter),
                                'Property "'.$name.'" on class "'.$className.'" does not have a getter method.');
                            $this->assertTrue(
                                method_exists($className, $setter),
                                'Property "'.$name.'" on class "'.$className.'" does not have a setter method.');
                        }
                    }
                }
            }
        }
    }
}