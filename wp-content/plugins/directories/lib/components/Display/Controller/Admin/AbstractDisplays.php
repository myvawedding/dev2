<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Component\Display\Model\Display as DisplayModel;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\System;

abstract class AbstractDisplays extends System\Controller\Admin\AbstractSettings
{
    protected $_displayType = 'entity', $_enableCSS = false, $_hideTabsIfSingle = true;
    
    abstract protected function _getDisplays(Context $context);
    
    protected function _getDisplay(Context $context, $displayName)
    {
        return $this->Display_Display($context->bundle->name, $displayName, $this->_displayType, true, true);
    }
        
    protected function _getDisplayWeight(array $display)
    {
        return $display['name'] === 'default' ? 1 : ($display['is_amp'] ? 20 : 10);
    }
    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $form = array('#tabs' => array(), '#tab_style' => 'pill_less_margin', '#listen_tabs' => true, '#displays' => []);
        foreach ($this->_getDisplays($context) as $display_name => $display_label) {
            $this->_addDisplays($context, $display_name, $display_label, $form);
        }
        if ($this->_hideTabsIfSingle
            && count($form['#tabs']) <= 1
        ) {
            $form['#tabs'] = [];
        }
        
        return $form;
    }

    protected function _addDisplays(Context $context, $name, $label, array &$form)
    {
        if (!$display = $this->_getDisplay($context, $name)) return;

        $form['#displays'][$name] = [$display['name']];
        $form['#tabs'][$name] = [
            '#title' => $label,
            '#weight' => $this->_getDisplayWeight($display),
            '#id' => 'drts-display-tab-' . $name,
        ];
        $form[$name] = [
            '#tree' => true,
            '#tab' => $name,
        ];
        $is_creatable = $this->Filter(
            'display_is_creatable',
            $this->_displayType === 'entity' && $name === 'summary',
            [$this->_displayType, $name]
        );
        $this->_addDisplay($display, $form[$name], $is_creatable, true);
        if ($is_creatable) {
            $display_names = [];
            foreach ($this->getModel('Display', 'Display')
                ->bundleName_is($context->bundle->name)
                ->type_is($this->_displayType)
                ->name_startsWith($name . '-')
                ->fetch() as $_display
            ) {
                $display_names[] = $_display->name;
            }
            $navs = [
                '<a class="' . DRTS_BS_PREFIX . 'nav-link drts-display-tab2-link drts-display-tab2-link-default ' . DRTS_BS_PREFIX . 'active" data-toggle="' . DRTS_BS_PREFIX . 'pill" href="#drts-display-tab2-' . $name . '" title="' . $name . '">'
                    . $this->H(__('Default', 'directories'))
                    . '</a>'
            ];
            foreach ($display_names as $display_name) {
                if ((!$display_name = trim($display_name))
                    || (!$display = $this->_getDisplay($context, $display_name))
                ) continue;

                $form['#displays'][$name][] = $display['name'];
                $this->_addDisplay($display, $form[$name], true);
                $display_name = $this->H($display['name']);
                $navs[] = '<a class="' . DRTS_BS_PREFIX . 'nav-link drts-display-tab2-link" data-toggle="' . DRTS_BS_PREFIX . 'pill" href="#drts-display-tab2-' . $display_name . '" data-display-type="' . $this->_displayType . '" data-display-name="' . $display_name . '" title="' . $display_name . '">'
                    . substr($display_name, strpos($display_name, '-') + 1)
                    . ' <i class="drts-display-delete-display fas fa-times-circle drts-clear ' . DRTS_BS_PREFIX . 'text-danger"></i></a>';
            }

            $add_display_title = $this->H(__('Add Display', 'directories'));
            $form[$name]['#prefix'] = '<div class="' . DRTS_BS_PREFIX . 'row">'
                . '<div class="' . DRTS_BS_PREFIX . 'col-sm-2">'
                . '<div class="' . DRTS_BS_PREFIX . 'nav ' . DRTS_BS_PREFIX . 'flex-sm-column ' . DRTS_BS_PREFIX . 'nav-pills">' . implode(PHP_EOL, $navs) . '</div>'
                . '<a class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-outline-primary ' . DRTS_BS_PREFIX . 'btn-sm ' . DRTS_BS_PREFIX . 'btn-block ' . DRTS_BS_PREFIX . 'my-3 drts-display-add-display" title="' . $add_display_title . '" data-modal-title="' . $add_display_title . ' - ' . $this->H($label) .'" rel="sabaitooltip" data-display-type="' . $display['type'] . '" data-display-name="' . $name . '"><i class="fas fa-plus"></i></a>'
                . '</div>'
                . '<div class="' . DRTS_BS_PREFIX . 'col-sm-10"><div class="' . DRTS_BS_PREFIX . 'tab-content">';
            $form[$name]['#suffix'] = '</div></div></div>';
        }
    }

    protected function _addDisplay($display, array &$form, $addPrefixSuffix = false, $active = false)
    {
        $form[$display['name']] = [
            'elements' => [
                '#type' => 'display_elements',
                '#display' => $display,
                '#clear_display_cache' => false,
                '#prefix' => $addPrefixSuffix ? '<div id="drts-display-tab2-' . $display['name'] .'" class="' . DRTS_BS_PREFIX . 'tab-pane ' . DRTS_BS_PREFIX . 'fade ' . DRTS_BS_PREFIX . 'show ' . ($active ? DRTS_BS_PREFIX . 'active' : '') . '">' : null,
                '#suffix' => $addPrefixSuffix ? ($this->_enableCSS ? null : '</div>') : null,
            ],
        ];
        if ($this->_enableCSS) {
            $form[$display['name']]['css'] = [
                '#title' => __('Custom CSS', 'directories'),
                '#description' => sprintf(
                    $this->H(__('Enter custom CSS for the display above. You can use %s to target the display with a CSS class.', 'directories')),
                    '<code>.' . DisplayModel::cssClass($display['name'], $display['type']) . '</code>'
                ),
                '#description_top' => true,
                '#description_no_escape' => true,
                '#type' => 'editor',
                '#language' => 'css',
                '#default_value' => $display['css'],
                '#suffix' => $addPrefixSuffix ? '</div>' : null,
            ];
        }
    }
    
    protected function _saveConfig(Context $context, array $config, Form\Form $form)
    {
        if ($this->_enableCSS) {
            foreach (array_keys($form->settings['#displays']) as $default_display_name) {
                $displays = $this->getModel('Display', 'Display')
                    ->bundleName_is($context->bundle->name)
                    ->type_is($this->_displayType)
                    ->name_in($form->settings['#displays'][$default_display_name])
                    ->fetch();
                foreach ($displays as $display) {
                    $data = $display->data ?: [];
                    if (isset($config[$default_display_name][$display->name]['css'])) {
                        $data['css'] = $config[$default_display_name][$display->name]['css'];
                    } else {
                        unset($data['css']);
                    }
                    $display->data = $data;
                    // Clear display cache
                    $this->Display_Display_clearCache($context->bundle->name, $this->_displayType, $display->name);
                }
            }
            $this->getModel(null, 'Display')->commit();
        } else {
            foreach (array_keys($form->settings['#displays']) as $default_display_name) {
                foreach ($form->settings['#displays'][$default_display_name] as $display_name) {
                    // Clear display cache
                    $this->Display_Display_clearCache($context->bundle->name, $this->_displayType, $display_name);
                }
            }
        }

        // Clear elements cache
        $this->getPlatform()->deleteCache('display_elements_' . $context->bundle->name);
    }
}