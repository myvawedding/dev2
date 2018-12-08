<?php
namespace SabaiApps\Directories\Component\Display\Button;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class CustomButton extends AbstractButton
{    
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        $info = array(
            'label' => __('Custom buttons', 'directories'),
            'default_settings' => array(
                '_label' => '',
                '_color' => 'secondary',
                '_icon' => '',
                'link_type' => 'current',
                'url' => null,
                'path' => null,
                'fragment' => null,
            ),
            'multiple' => [],
            'weight' => 50,
        );
        foreach ($this->_application->Filter('entity_button_custom_button_num', range(1, 3), array($bundle)) as $num) {
            $info['multiple'][$num] = array(
                'default_checked' => $num === 1,
                'label' => sprintf(__('Custom button #%d', 'directories'), $num)
            );
        }
        
        return $info;
    }
    
    public function displayButtonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $ret = array(
            'link_type' => array(
                '#type' => 'select',
                '#options' => array(
                    'current' => __('Link to current content URL', 'directories'),
                    'url' => __('Link to external URL', 'directories'),
                ),
                '#title' => __('Button link type', 'directories'),
                '#horizontal' => true,
                '#default_value' => $settings['link_type'],
            ),
            'path' => array(
                '#title' => __('Extra URL path', 'directories'),
                '#type' => 'textfield',
                '#field_prefix' => '/',
                '#default_value' => $settings['path'],
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'url')
                    ),
                ),
            ),
            'fragment' => array(
                '#title' => __('URL fragment identifier', 'directories'),
                '#description' => __('Add a fragment identifier to the link URL in order to link to a specific section of the page.', 'directories'),
                '#type' => 'textfield',
                '#field_prefix' => '#',
                '#default_value' => $settings['fragment'],
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'url')
                    ),
                ),
            ),
            'url' => array(
                '#type' => 'url',
                '#title' => __('External URL', 'directories'),
                '#placeholder' => 'http://',
                '#default_value' => $settings['url'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'url')
                    ),
                ),
            ),
        );
        if (!empty($bundle->info['parent'])) {
            $ret['link_type']['#options']['parent'] = __('Link to parent page', 'directories'); 
        }
        
        return $ret;
    }
    
    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        switch ($settings['link_type']) {
            case 'current':
                $url = (string)$this->_application->Entity_Url($entity, $settings['path'] ? '/' . $settings['path'] : '', [], $settings['fragment']);
                break;
            case 'parent':
                if (!$parent = $this->_application->Entity_ParentEntity($entity)) return;
                
                $url = (string)$this->_application->Entity_Url($parent, $settings['path'] ? '/' . $settings['path'] : '', [], $settings['fragment']);
                break;
            case 'url':
                $url = $settings['url'];
                break;
            default:
                return;
        }
        return $this->_application->LinkTo(
            $settings['_label'],
            array('script_url' => $url),
            array('icon' => $settings['_icon']),
            array('class' => $settings['_class'], 'style' => $settings['_style'])
        );
    }
}
