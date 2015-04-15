<?php namespace FHIR\ComponentTests\Util;

use DCarbone\FileObjectPlus;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlock\Tag\VarTag;

/**
 * Class ReflectionUtils
 * @package FHIR\ComponentTests\Util
 */
abstract class ReflectionUtils
{
    /**
     * @param \ReflectionClass $class
     * @param string $methodName
     * @return bool
     */
    public static function classImplementsMethod(\ReflectionClass $class, $methodName)
    {
        if ($class->hasMethod($methodName))
        {
            $method = $class->getMethod($methodName);
            return $method->getDeclaringClass()->getName() == $class->getName();
        }

        return false;
    }

    /**
     * @param \ReflectionClass $class
     * @param string $methodName
     * @return null|\ReflectionClass
     */
    public static function getParentThatImplementsMethod(\ReflectionClass $class, $methodName)
    {
        $parent = $class->getParentClass();
        while ($parent)
        {
            if ($parent->hasMethod($methodName))
            {
                $method = $parent->getMethod($methodName);
                if($method->getDeclaringClass()->getName() == $parent->getName())
                    return $parent;
            }

            $parent = $parent->getParentClass();
        }

        return null;
    }

    /**
     * @param \ReflectionClass $class
     * @param string $methodName
     * @return bool
     */
    public static function anyParentImplementsMethod(\ReflectionClass $class, $methodName)
    {
        return null !== static::getParentThatImplementsMethod($class, $methodName);
    }

    /**
     * @param FileObjectPlus $fileObject
     * @param string $sourceClassName
     * @param string $methodName
     * @param bool $asArray
     * @return array|string
     */
    public static function getMethodCode(FileObjectPlus $fileObject,
                                         $sourceClassName,
                                         $methodName,
                                         $asArray = false)
    {
        $export = \ReflectionMethod::export($sourceClassName, $methodName, true);
        if ($export)
        {
            preg_match('{@@.+\s(\d+)\s-\s(\d+)+}S', $export, $match);

            $start = (int)$match[1];
            $end = (int)$match[2];
            $i = $start;

            if ($asArray)
            {
                $code = array();
                while ($i <= $end)
                {
                    $fileObject->seek($i++);
                    $code[] = $fileObject->current();
                }
            }
            else
            {
                $code = '';
                while ($i <= $end)
                {
                    $fileObject->seek($i++);
                    $code .= $fileObject->current();
                }
            }
            $fileObject->rewind();

            return $code;
        }

        throw new \RuntimeException('Could not get definition of method "'.$sourceClassName.'::'.$methodName.'".');
    }

    /**
     * @param \ReflectionProperty $property
     * @param bool $asArray
     * @return null|string
     */
    public static function getClassFromPropertyDocBlock(\ReflectionProperty $property, $asArray = false)
    {
        $docBlock = new DocBlock($property->getDocComment());
        foreach($docBlock->getTags() as $tag)
        {
            if ($tag instanceof VarTag)
            {
                if ($asArray)
                    return explode('|', $tag->getContent());
                else
                    return $tag->getContent();
            }
        }

        return null;
    }

    /**
     * TODO: Do a better job with this method...
     *
     * @param mixed $var
     * @return string
     */
    public static function prettyVarExport($var)
    {
        ob_start();
        var_export($var);
        return ob_get_clean();
//        return preg_replace(
//            array(
//                '{\t|\s{2,}}S',
//                '{\n}S'
//            ),
//            array(
//                '',
//                ' '
//            ),
//            ob_get_clean());
    }
}