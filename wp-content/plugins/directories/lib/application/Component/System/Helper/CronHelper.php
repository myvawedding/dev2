<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\System\Progress;

class CronHelper
{
    public function help(Application $application, Progress $progress = null, $force = false)
    {
        // Init progress
        if (!isset($progress)) {
            $progress = $application->System_Progress('system_cron')
                ->start(null, __('Running cron... %3$s', 'directories'));
        }
        // Get timestamp of last cron
        if ($last_run_timestamp = (int)$application->getPlatform()->getOption('system_cron_last')) {
            $progress->set(sprintf(__('Cron was last run at %s', 'directories'), $application->System_Date_datetime($last_run_timestamp)));
        }
        // Reset timestamp if forcing
        if ($force) $last_run_timestamp = 0;
        // Invoke cron
        $application->Action('system_cron', [$progress, $last_run_timestamp]);
        // Stop progress
        $progress->done();
        // Save timestamp
        $application->getPlatform()->setOption('system_cron_last', time());
    }
}
