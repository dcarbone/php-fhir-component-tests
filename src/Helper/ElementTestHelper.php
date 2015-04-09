<?php namespace FHIR\ComponentTests\Helper;

use Symfony\Component\Console\Helper\Helper;

/**
 * Class ElementTestHelper
 * @package FHIR\ComponentTests\Helper
 */
class ElementTestHelper extends Helper
{
    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     *
     * @api
     */
    public function getName()
    {
        return 'element-test';
    }
}