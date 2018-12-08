<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class LabelsHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $useCache = true)
    {
        if (!$useCache
            || (!$labels = $application->getPlatform()->getCache('display_labels_' . $bundle->type))
        ) {
            $labels = [];
            foreach ($application->InstalledComponentsByInterface('Display\ILabels') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->displayGetLabelNames($bundle) as $label_name) {
                    if (!$application->getComponent($component_name)->displayGetLabel($label_name)) {
                        continue;
                    }
                    $labels[$label_name] = $component_name;
                }
            }
            $labels = $application->Filter('display_labels', $labels, array($bundle));
            $application->getPlatform()->setCache($labels, 'display_labels_' . $bundle->type, 0);
        }

        return $labels;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Display\ILabel interface for a given label name
     * @param Application $application
     * @param string $label
     */
    public function impl(Application $application, Entity\Model\Bundle $bundle, $label, $returnFalse = false)
    {
        if (!isset($this->_impls[$label])) {            
            if ((!$labels = $application->Display_Labels($bundle))
                || !isset($labels[$label])
                || !$application->isComponentLoaded($labels[$label])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid label: %s', $label));
            }
            $this->_impls[$label] = $application->getComponent($labels[$label])->displayGetLabel($label);
        }

        return $this->_impls[$label];
    }
}