<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Context;

class Views extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        // Load scripts for add directory modal form
        $this->Form_Scripts(array('addmore', 'slider', 'latinise'));
        $this->getPlatform()->loadJqueryUiJs(array('effects-highlight'));
        
        $admin_view_path = rtrim($context->getRoute(), '/');
        $form = array(
            '#bundle' => $context->bundle,
            'views' => array(
                '#type' => 'tableselect',
                '#header' => array(
                    'name' => __('Name', 'directories'),
                    'mode' => __('View Mode', 'directories'),
                    'default' => __('Default', 'directories'),
                    'shortcode' => __('Shortcode', 'directories'),
                    'links' => '',
                ),
                '#disabled' => true,
                '#multiple' => true,
                '#js_select' => true,
                '#options' => [],
                '#class' => 'drts-data-table',
                '#row_attributes' => array(
                    '@all' => array(
                        'name' => array(
                            'style' => 'width:25%;',
                        ),
                        'mode' => array(
                            'style' => 'width:25%;',
                        ),
                        'default' => array(
                            'style' => '',
                        ),
                        'links' => array(
                            'style' => 'white-space:nowrap;text-align:' . ($this->getPlatform()->isRtl() ? 'left' : 'right') . ';',
                        ),
                    ),
                ),
                '#views' => [],
            ),
            '#js_ready' => array(
                'var params = {}; params[DRTS.params.ajax] = "#drts-modal"; $.get("' . $this->Url(rtrim($context->getRoute(), '/') . '/add') . '", params, function (_data) {
                    DRTS.cache("drts-view-add-view", _data);
                });',
                '$(".drts-view-set-default").on("click", function(e) {
                    var $this = $(this);
                    if ($this.hasClass("drts-view-is-default")) return;
                        
                    e.preventDefault();
                    
                    $this.closest("table").find(".drts-view-set-default").each(function() {
                        var $that = $(this);
                        if ($that.hasClass("drts-bs-text-success")) {
                            $that.removeClass("drts-bs-text-success").addClass("drts-bs-text-muted");
                        } else {
                            $that.removeClass("drts-bs-text-muted").addClass("drts-bs-text-success");
                        }
                    });
                    
                    DRTS.ajax({
                        loadingImage:false,
                        onSendData:function(data, trigger) {
                            data.name = trigger.data("name");
                            data["' . Request::PARAM_TOKEN . '"]= "' . $this->Form_Token_create('view_admin_views', 1800, true) . '";
                        },
                        onSuccess:function(result, target, trigger) {
                            trigger.closest("table").find(".drts-view-set-default").each(function() {
                                var $that = $(this);
                                $that.toggleClass("drts-bs-text-success drts-view-is-default", result.name === $that.data("name"))
                                    .toggleClass("drts-bs-text-muted", result.name !== $that.data("name"));
                            });
                        },
                        onError:function(error, target, trigger, status) {
                            trigger.addClass("drts-bs-text-muted").removeClass("drts-bs-text-success");
                            DRTS.flash(error, "danger", 0);
                        },
                        type:"post",
                        trigger:jQuery(this),
                        container:"#drts-content",
                        url:"' . $this->Url($admin_view_path . '/set_default') . '"
                    });
                });',
            ),
        );

        $default_icon = '<i class="fas fa-check-circle fa-2x"></i>';
        foreach ($this->getModel('View', 'View')->bundleName_is($context->bundle->name)->fetch(0, 0, array('view_name', 'view_mode'), array('ASC', 'ASC')) as $view) {
            if (!$view_mode = $this->View_Modes_impl($view->mode, true)) continue;
            
            if ($view->default) {
                $default_class = 'drts-view-set-default ' . DRTS_BS_PREFIX . 'text-success drts-view-is-default';
                $default = '<span class="' . $default_class . '" data-name="' . $this->H($view->name) . '">' . $default_icon . '</span>';
            } else {
                $default = '<span class="drts-view-set-default ' . DRTS_BS_PREFIX . 'text-muted" data-name="' . $this->H($view->name) . '">' . $default_icon . '</span>';
            }
            $form['#views'][$view->id] = $view;
            $form['views']['#options'][$view->id] = array(
                'name' => $this->H($view->getLabel()). ' <small>(' . $this->H($view->name) . ')</small>',
                'mode' => $this->H($view_mode->viewModeInfo('label')),
                'default' => $default,
                'shortcode' => '<code>' . $this->_getShortcode($context->bundle, $view) . '</code>',
                'links' => implode(PHP_EOL, array(
                    $this->LinkTo(
                        '',
                        $this->Url($admin_view_path . '/' . $view->id),
                        array('no_escape' => true, 'icon' => 'fas fa-cog', 'container' => 'modal'),
                        array(
                            'class' => 'drts-bs-btn drts-bs-btn-outline-secondary',
                            'data-modal-title' => __('Edit View', 'directories') . ' - ' . $view->getLabel(),
                        )
                    ),
                    $this->LinkTo(
                        '',
                        $this->Url($admin_view_path . '/' . $view->id . '/clone'),
                        array('no_escape' => true, 'icon' => 'far fa-clone', 'container' => 'modal'),
                        array(
                            'class' => 'drts-bs-btn drts-bs-btn-outline-secondary',
                            'data-modal-title' => __('Clone View', 'directories') . ' - ' . $view->getLabel(),
                        )
                    ),
                    $this->LinkTo(
                        '',
                        $this->Url($admin_view_path . '/' . $view->id . '/delete'),
                        array('no_escape' => true, 'icon' => 'fas fa-trash-alt', 'container' => 'modal'),
                        array(
                            'class' => 'drts-bs-btn drts-bs-btn-outline-danger',
                            'data-modal-title' => __('Delete View', 'directories') . ' - ' . $view->getLabel(),
                        )
                    ),
                )),
            );
        }
        
        return $form;
    }
    
    protected function _getShortcode($bundle, $view)
    {
        return sprintf(
            '[drts-directory-view directory="%1$s"%2$s%3$s]',
            $this->_application->H($bundle->group),
            empty($bundle->info['is_primary']) ? ' type="' . $this->_application->H($bundle->type) . '"' : '',
            $view->default ? '' : ' name="' . $this->_application->H($view->name) . '"'
        );
    }
        
    public function onFormBuildViewAdminViews(&$form)
    {   
        $form['views']['#header']['wp_shortcode'] = array('order' => 35, 'label' => __('Shortcode', 'directories'));
        foreach (array_keys($form['views']['#options']) as $view_id) {
            $form['views']['#options'][$view_id]['wp_shortcode'] = sprintf(
                '<code>[drts-view directory="%1$s" type="%2$s"%3$s]</code>',
                $form['#bundle']->group,
                $form['#bundle']->type,
                $form['#views'][$view_id]->default ? '' : ' name="' . $this->_application->H($form['#views'][$view_id]->name) . '"'
            );
        }
    }
}