<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class ActionHelper
{
    public function help(Application $application, $name, array $args = [])
    {
        $event_type = str_replace('_', '', $name);
        if ($application->hasEventListner($event_type)) {   
            $application->dispatchEvent($event_type, $args);
        }
    }
}