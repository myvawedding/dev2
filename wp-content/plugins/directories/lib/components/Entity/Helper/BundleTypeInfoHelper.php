<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class BundleTypeInfoHelper
{    
    public function help(Application $application, $bundleType, $key = null)
    {
        if ($bundleType instanceof \SabaiApps\Directories\Component\Entity\Model\Bundle) {
            $bundleType = $bundleType->type;
        }
        $cache_id = 'entity_bundle_type_' . $bundleType;
        if (!$ret = $application->getPlatform()->getCache($cache_id)) {
            try {
                $ret = $application->Entity_BundleTypes_impl($bundleType)->entityBundleTypeInfo();
                // Remove info that are most likely not needed to be cached
                unset($ret['fields'], $ret['displays'], $ret['views']);
            } catch (Exception\IException $e) {
                $application->logError($e);
                return;
            }
            $application->getPlatform()->setCache($ret, $cache_id, 0);
        }
        
        return isset($key) ? (isset($ret[$key]) ? $ret[$key] : null) : $ret;
    }
}