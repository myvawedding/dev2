<?php
namespace SabaiApps\Directories\Component\View\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class ModesHelper
{
    public function help(Application $application, $excludeSystem = true, $useCache = true)
    {        
        if (!$useCache
            || (!$view_modes = $application->getPlatform()->getCache('view_modes'))
        ) {
            $view_modes = array(0 => [], 1 => []);
            foreach ($application->InstalledComponentsByInterface('View\IModes') as $component_name) {
                if (!$application->isComponentLoaded($component_name)
                    || (!$mode_names = $application->getComponent($component_name)->viewGetModeNames())
                ) continue;
   
                foreach ($mode_names as $mode_name) {
                    if (!$view_mode = $application->getComponent($component_name)->viewGetMode($mode_name)) continue;

                    $view_modes[(int)$view_mode->viewModeInfo('system')][$mode_name] = $component_name;
                }
            }
            $application->getPlatform()->setCache($view_modes, 'view_modes', 0);
        }
        
        return $excludeSystem ? $view_modes[0] : $view_modes[0] + $view_modes[1];
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\View\IMode interface for a given view name
     * @param Application $application
     * @param string $mode
     */
    public function impl(Application $application, $mode, $returnFalse = false)
    {
        if (!isset($this->_impls[$mode])) {            
            if ((!$view_modes = $this->help($application, false))
                || !isset($view_modes[$mode])
                || (!$application->isComponentLoaded($view_modes[$mode]))
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid view mode: %s', $mode));
            }
            $this->_impls[$mode] = $application->getComponent($view_modes[$mode])->viewGetMode($mode);
        }

        return $this->_impls[$mode];
    }
    
    public function settingsForm(Application $application, $mode, Entity\Model\Bundle $bundle, array $settings = [], array $parents = [], array $submitValues = null)
    {
        $view_mode = $mode instanceof \SabaiApps\Directories\Component\View\Mode\IMode ? $mode : $this->impl($application, $mode);
        $settings += (array)$view_mode->viewModeInfo('default_settings');
        $form = (array)$view_mode->viewModeSettingsForm($bundle, $settings, $parents);
        
        return $application->Filter('view_mode_settings_form', $form, array($view_mode, $bundle, $settings, $parents, $submitValues));  
    }
}