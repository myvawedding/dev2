<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class RunTool extends Controller
{
    protected function _doExecute(Context $context)
    {
        // Must be an Ajax request
        if (!$context->getRequest()->isAjax()
            || (!$tool = $context->getRequest()->asStr('tool'))
        ) {
            $context->setBadRequestError();
            return;
        }

        // Check request token
        if (!$this->_checkToken($context, 'system_admin_run_tool')) {
            return;
        }
        
        // Check if valid tool
        $tools = $this->Filter('system_admin_system_tools', []);
        if (!isset($tools[$tool])) {
            return;
        }
        
        try {
            // Invoke tool
            $this->Action('system_admin_run_tool', array($tool, null, array()));
            
            // Send success if reaches this point
            if ($route = $context->getRequest()->asStr('redirect', false)) {
                $context->setSuccess($this->Url($route, array('tab' => 'tools')));
                $context->addFlash(sprintf(__('The selected tool (%s) was run successfully.', 'directories'), $tools[$tool]['label']));
            } else {
                $context->setSuccess(false); // set false to prevent redirection
            }
        } catch (\Exception $e) {
            $context->setError($e->getMessage());
        }
    }
}