<?php
namespace SabaiApps\Directories\Component\Location\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class QueryTimezone extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$context->getRequest()->isAjax()
            || (!$latlng = trim($context->getRequest()->asStr('latlng')))
            || (!$latlng = explode(',', $latlng))
            || count($latlng) !== 2
        ) {
            $context->setError();
            return;
        }

        $context->addTemplate('system_list')->setAttributes(['list' => $this->Location_Api_timezone($latlng)]);
    }
}
