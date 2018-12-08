<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class FieldNameHelper
{
    /**
     * @param Application $application
     * @param array $names
     */
    public function help(Application $application, array $names)
    {
        if (is_array($names[0])) {
            $ret = $names[0][0];
            unset($names[0][0]);
        } else {
            $ret = array_shift($names);
        }
        foreach ($names as $name) {
            if (is_array($name)) {
                foreach ($name as $_name) {
                    $ret .= '[' . $_name . ']';
                }
            } else {
                $ret .= '[' . $name . ']';
            }
        }

        return $ret;
    }
}