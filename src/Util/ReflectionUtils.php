<?php namespace FHIR\ComponentTests\Util;

use DCarbone\FileObjectPlus;

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
     * @return bool
     */
    public static function anyParentImplementsMethod(\ReflectionClass $class, $methodName)
    {
        $hasMethod = false;
        $parent = $class->getParentClass();
        while (!$hasMethod && $parent)
        {
            if ($parent->hasMethod($methodName))
            {
                $method = $parent->getMethod($methodName);
                $hasMethod = ($method->getDeclaringClass()->getName() == $parent->getName());
            }

            $parent = $parent->getParentClass();
        }

        return $hasMethod;
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