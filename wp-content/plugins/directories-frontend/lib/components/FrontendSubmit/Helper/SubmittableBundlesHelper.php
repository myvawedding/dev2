<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;

class SubmittableBundlesHelper
{
    public function help(Application $application, $bundleType)
    {
        $bundles = $application->Entity_Bundles_addable($bundleType);
        foreach (array_keys($bundles) as $bundle_name) {
            if ($bundle = $application->Entity_Bundle($bundle_name)) {
                if (empty($bundle->info['parent'])
                    && isset($bundle->info['frontendsubmit_enable'])
                    && empty($bundle->info['frontendsubmit_enable'])
                ) {
                    unset($bundles[$bundle_name]);
                }
            } else {
                unset($bundles[$bundle_name]);
            }
        }
        return $bundles;
    }
}