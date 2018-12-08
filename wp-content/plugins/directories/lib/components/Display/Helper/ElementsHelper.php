<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class ElementsHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $useCache = true)
    {
        if (!$useCache
            || (!$elements = $application->getPlatform()->getCache('display_elements_' . $bundle->name))
        ) {
            $elements = [];
            foreach ($application->InstalledComponentsByInterface('Display\IElements') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->displayGetElementNames($bundle) as $element_name) {
                    if (!$application->getComponent($component_name)->displayGetElement($element_name)) {
                        continue;
                    }
                    $elements[$element_name] = $component_name;
                }
            }
            $elements = $application->Filter('display_elements', $elements, array($bundle));
            $application->getPlatform()->setCache($elements, 'display_elements_' . $bundle->name, 0);
        }

        return $elements;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of Display\IElement interface for a given element name
     * @param Application $application
     * @param string $element
     */
    public function impl(Application $application, Entity\Model\Bundle $bundle, $element, $returnFalse = false)
    {
        if (!isset($this->_impls[$element])) {            
            if ((!$elements = $application->Display_Elements($bundle))
                || !isset($elements[$element])
                || !$application->isComponentLoaded($elements[$element])
            ) {                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid element: %s', $element));
            }
            $this->_impls[$element] = $application->getComponent($elements[$element])->displayGetElement($element);
        }

        return $this->_impls[$element];
    }

    protected $_types = [];

    public function types(Application $application, Entity\Model\Bundle $bundle)
    {
        if (!isset($this->_types[$bundle->name])) {
            $types = [
                'field' => _x('Field', 'display element type', 'directories'),
            ];
            if (!empty($bundle->info['parent'])
                && ($parent_bundle = $application->Entity_Bundle($bundle->info['parent']))
            ) {
                $types['parent'] = $parent_bundle->getLabel('singular'); // parent entity bundle label
            }
            $types += [
                'content' => _x('Content', 'display element type', 'directories'),
                'utility' => _x('Utility', 'display element type', 'directories'),
            ];

            $this->_types[$bundle->name] = $application->Filter('display_element_types', array_filter($types));
        }

        return $this->_types[$bundle->name];
    }
}