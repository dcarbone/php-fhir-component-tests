<?php namespace FHIR\ComponentTests\Template;

use DCarbone\FileObjectPlus;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class ElementTestClassTemplate
 * @package FHIR\ComponentTests\Template
 */
class ElementTestClassTemplate
{
    /** @var string */
    protected $sourceClassName;

    /** @var SplFileInfo */
    protected $sourceClassFile;

    /** @var string */
    protected $testClassName;

    /** @var \ReflectionClass */
    protected $sourceClass;

    /** @var  */
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
        $this->fileObject = new FileObjectPlus($sourceClassFile->getPath().DIRECTORY_SEPARATOR.$sourceClassFile->getFilename());
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
     * @param string $methodName
     * @return string
     */
    protected function getMethodCode($methodName)
    {
        $export = \ReflectionMethod::export($this->sourceClassName, $methodName, true);
        if ($export)
        {
            preg_match('{@@.+\s(\d+)\s-\s(\d+)+}', $export, $match);

            $start = (int)$match[1];
            $end = (int)$match[2];
            $i = $start;

            $code = '';
            while ($i <= $end)
            {
                $this->fileObject->seek($i);
                $code .= $this->fileObject->current();
                $i++;
            }

            return $code;
        }

        throw new \RuntimeException('Could not get definition of method "'.$this->sourceClassName.'::'.$methodName.'".');
    }

    /**
     * @return string
     */
    public function generateClassCode()
    {
        $testClassName = $this->getTestClassName();

        $classCode = <<<PHP
<?php

class {$testClassName} extends \\PHPUnit_Framework_TestCase {
PHP;

        $this->addConstructorDefinitionTest($classCode);

        return $classCode."}\n";
    }

    /**
     * @param string $classCode
     */
    protected function addConstructorDefinitionTest(&$classCode)
    {
        if (method_exists($this->sourceClassName, '__construct'))
        {
            $this->getMethodCode('__construct');
        }
    }
}