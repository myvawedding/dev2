<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class BundlePathHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $permalink = false, $lang = null)
    {
        if (empty($bundle->info['parent'])) {
            $path = '/' . $application->getComponent($bundle->component)->getSlug($bundle->group, $lang);
            if ($permalink
                || empty($bundle->info['is_primary'])
            ) {
                $path .= '/' . $bundle->info['slug'];
            }
        } else {
            if (!$parent_bundle = $application->Entity_Bundle($bundle->info['parent'])) {
                // probably during installation of component, so fetch by bundle type, 
                $parent_bundle = $application->Entity_Bundle($bundle->info['parent'], $bundle->component, $bundle->group, true); 
            }
            if ($permalink) {
                $path = $this->help($application, $parent_bundle, true, $lang) . '/:slug';
            } else {
                $path = $this->help($application, $parent_bundle, false, $lang);
            }
            $path .= '/' . $bundle->info['slug'];
        }
        
        return $path;
    }
}