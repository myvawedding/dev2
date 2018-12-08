<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

class RunToolWithProgress extends Form\Controller
{
    protected $_toolName, $_tool;
    
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        if (!$tool = $context->getRequest()->asStr('tool')) {
            $context->setBadRequestError();
            return;
        }
        
        // Check if valid tool
        $tools = $this->Filter('system_admin_system_tools', []);
        if (!isset($tools[$tool])
            || empty($tools[$tool]['with_progress'])
        ) {
            return;
        }

        $this->_toolName = $tool;
        $this->_tool = $tools[$tool];
        $context->addTemplate('system_progress');
        $this->_ajaxSubmit = true;
        $this->_ajaxOnSubmit = $this->System_Progress_formSubmitJs('system_run_tool');
        $this->_ajaxLoadingImage = false; // prevent modal content/window from being cleared
        $this->_submitButtons[] = array(
            '#btn_label' => __('Run Tool', 'directories'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
            '#attributes' => array('data-modal-title' => 'false'),
        );
        $form = isset($this->_tool['form']) ? $this->_tool['form'] : [];
        $form['#header'] = [
            '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-info">'
                . $this->H(__('This may take a while to complete, please do not close the window or click other buttons.', 'directories'))
                . '</div>',
        ];
        $form = $this->Filter('system_admin_run_tool_form', $form, array($this->_toolName, $this->_tool));

        return [
            'tool' => [
                '#type' => 'hidden',
                '#value' => $tool,
            ],
            'redirect' => [
                '#type' => 'hidden',
                '#value' => $context->getRequest()->asStr('redirect'),
            ],
        ] + $form;
    }
    
    public function submitForm(Form\Form $form, Context $context)
    {
        @set_time_limit(0);
        
        $progress = $this->System_Progress('system_run_tool');
        if (!isset($this->_tool['start'])
            || $this->_tool['start']
        ) {
            $progress->start(null, __('Running tool... %3$s', 'directories'));
        }
        
        try {
            // Invoke tool
            $this->Action('system_admin_run_tool', array($this->_toolName, $progress, $form->values));
            
            // Send success if reaches this point
            if ($redirect = $context->getRequest()->asStr('redirect')) {
                $context->setSuccess($this->Url($redirect, array('tab' => 'tools')));
                $context->addFlash(sprintf(__('The selected tool (%s) was run successfully.', 'directories'), $this->_tool['label']));
            } else {
                $context->setSuccess(false); // set false to prevent redirection
            }
        } catch (\Exception $e) {
            $context->setError($e->getMessage());
        }
        if ($progress->isRunning()) {
            $progress->done(null, false, isset($redirect) ? ['sleep' => 2] : []); // wait a bit longer if redirecting
        }
        
        // Notify of success
        if ($context->isSuccess()) {
            $this->Action('system_admin_run_tool_success', array($this->_toolName, $form->values));
        }
    }
}