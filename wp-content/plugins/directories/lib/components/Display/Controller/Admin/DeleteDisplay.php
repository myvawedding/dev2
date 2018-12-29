<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class DeleteDisplay extends Controller
{
    protected function _doExecute(Context $context)
    {
        if (!$context->getRequest()->isPostMethod()
            || !$this->_checkToken($context, 'entity_admin_displays', true)
            || !$this->HasPermission('directory_admin_directory_' . $context->bundle->group)
            || (!$display_type = $context->getRequest()->asStr('display_type'))
            || (!$display_name = $context->getRequest()->asStr('display_name'))
        ) {
            $context->setBadRequestError();
            return;
        }

        // Delete model
        if ($display = $this->getModel('Display', 'Display')
            ->name_is($display_name)
            ->type_is($display_type)
            ->bundleName_is($context->bundle->name)
            ->fetchOne()
        ) {
            $display->markRemoved()->commit();
        }

        // Delete display cache
        $this->Display_Display_clearCache($context->bundle->name, $display_type, $display_name);

        $context->setSuccess();
    }
}