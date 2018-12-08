<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class DisplaysHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $activeOnly = true, $useCache = true)
    {
        $displays = [];
        if (!empty($bundle->info['public'])) {
            $displays['detailed'] = _x('Detailed', 'display name', 'directories');
            if (empty($bundle->info['is_taxonomy'])
                && empty($bundle->info['internal'])
            ) {
                if (!$activeOnly
                    || $application->getPlatform()->isAmpEnabled($bundle->name)
                ) {
                    $displays['amp_detailed'] = 'AMP - ' . $displays['detailed'];
                }
            }
        }

        foreach (array_keys($application->View_Modes(false, $useCache)) as $view_mode_name) {
            if ((!$view_mode = $application->View_Modes_impl($view_mode_name, true))
                || (!$_displays = $view_mode->viewModeInfo('displays'))
                || !$view_mode->viewModeSupports($bundle)
            ) continue;
            
            $displays += $_displays;
        }

        return $displays;
    }
}