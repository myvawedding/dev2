<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

class DownloadUrlHelper
{
    public function help(Application $application, $file = null, $tokenLifetime = 86400)
    {
        $params = array(Request::PARAM_TOKEN => $application->Form_Token_create('system_admin_download', $tokenLifetime));
        if (isset($file)) {
            $params['file'] = $file;
        }
        return $application->Url('/_drts/system/download', $params);
    }
}