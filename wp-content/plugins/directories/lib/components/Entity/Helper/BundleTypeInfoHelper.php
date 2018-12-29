<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class BundleTypeInfoHelper
{    
    public function help(Application $application, $bundleType, $key = null, $cache = true)
    {
        if ($bundleType instanceof \SabaiApps\Directories\Component\Entity\Model\Bundle) {
            $bundleType = $bundleType->type;
        }
        $cache_id = 'entity_bundle_type_' . $bundleType;
        if (!$cache
            || (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            try {
                $ret = $application->Entity_BundleTypes_impl($bundleType)->entityBundleTypeInfo();
            } catch (Exception\IException $e) {
                $application->logError($e);
                return;
            }
            if ($cache) {
                // Remove info that are most likely not needed to be cached
                unset($ret['fields'], $ret['displays'], $ret['views']);
                $application->getPlatform()->setCache($ret, $cache_id, 0);
            }
        }
        
        return isset($key) ? (isset($ret[$key]) ? $ret[$key] : null) : $ret;
    }
}