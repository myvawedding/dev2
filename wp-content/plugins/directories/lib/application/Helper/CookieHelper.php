<?php
namespace SabaiApps\Directories\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

class CookieHelper
{
    public function help(Application $application, $name, $value = null, $expire = 0, $httpOnly = false)
    {
        if (isset($value)) {
            $platform = $application->getPlatform();
            return @setcookie($name, $value, $expire, $platform->getCookiePath(), $platform->getCookieDomain(), false, $httpOnly);
        }
        return Request::cookie($name);
    }
}