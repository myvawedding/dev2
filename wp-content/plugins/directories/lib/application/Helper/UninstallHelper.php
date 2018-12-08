<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class UninstallHelper
{
    /**
     * @param Application $application
     */
    public function help(Application $application)
    {
        $remove_data = $application->Filter('core_uninstall_remove_data', false);
        $application->Action('core_uninstall', [$remove_data]);
        
        // Uninstall addons
        if ($components = $application->getModel('Component', 'System')->fetch()) {
            foreach ($components as $component) {
                try {
                    $application->getComponent($component->name)->uninstall($remove_data);
                } catch (\Exception $e) {
                    $application->logWarning(sprintf('Component %s could not be uninstalled. Error: %s', $component->name, $e->getMessage()));
                }
            }
        }

        // Clear options and cache
        if ($remove_data) {
            $application->getPlatform()->clearOptions();
        }
        $application->getPlatform()->clearCache();
    }
}
