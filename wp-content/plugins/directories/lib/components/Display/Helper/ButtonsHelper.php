<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class ButtonsHelper
{
    private $_impls = [];
    
    public function help(Application $application, Entity\Model\Bundle $bundle, $useCache = true)
    {
        if (!$useCache
            || (!$buttons = $application->getPlatform()->getCache('display_buttons_' . $bundle->type))
        ) {
            $buttons = [];
            foreach ($application->InstalledComponentsByInterface('Display\IButtons') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->displayGetButtonNames($bundle) as $button_name) {
                    if (!$application->getComponent($component_name)->displayGetButton($button_name)) {
                        continue;
                    }
                    $buttons[$button_name] = $component_name;
                }
            }
            $buttons = $application->Filter('display_buttons', $buttons, array($bundle));
            $application->getPlatform()->setCache($buttons, 'display_buttons_' . $bundle->type, 0);
        }

        return $buttons;
    }
    
    /**
     * Gets an implementation of Display\IButton interface for a given button name
     * @param Application $application
     * @param string $button
     */
    public function impl(Application $application, Entity\Model\Bundle $bundle, $button, $returnFalse = false)
    {
        if (!isset($this->_impls[$button])) {            
            if ((!$buttons = $application->Display_Buttons($bundle))
                || !isset($buttons[$button])
                || (!$application->isComponentLoaded($buttons[$button]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid button: %s', $button));
            }
            $this->_impls[$button] = $application->getComponent($buttons[$button])->displayGetButton($button);
        }

        return $this->_impls[$button];
    }
}