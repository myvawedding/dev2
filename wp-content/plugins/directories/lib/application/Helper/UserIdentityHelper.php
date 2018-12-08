<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;

class UserIdentityHelper
{
    public function help(Application $application, $userId)
    {
        return is_array($userId)
            ? $application->getPlatform()->getUserIdentityFetcher()->fetchByIds($userId)
            : $application->getPlatform()->getUserIdentityFetcher()->fetchById($userId);
    }
}