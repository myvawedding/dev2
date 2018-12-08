<?php
namespace SabaiApps\Directories\Component\Display\FormField;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class ElementsFormField extends Form\Field\AbstractField
{    
    protected static $_fields = [];
    protected $_elementTypes;
    
    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        if (empty($data['#display'])
            || empty($data['#display']['id'])
        ) throw new Exception\RuntimeException('Invalid display.');
        
        if (empty($data['#display']['bundle_name'])
            || (!$bundle = $this->_application->Entity_Bundle($data['#display']['bundle_name']))
        ) {
            throw new Exception\RuntimeException('Invalid bundle.');
        }
        $this->_elementTypes = $this->_application->Display_Elements_types($bundle);
        
        $data['#id'] = $form->getFieldId($name);
        
        if (!isset(self::$_fields[$form->settings['#id']])) {
            self::$_fields[$form->settings['#id']] = [];
        }
        self::$_fields[$form->settings['#id']][$data['#id']] = [
            'display_id' => $data['#display']['id'],
            'display_type' => $data['#display']['type'],
            'display_name' => $data['#display']['name'],
            'name' => $name
        ];
        
        if (!isset($form->settings['#pre_render'][__CLASS__])) {
            $form->settings['#pre_render'][__CLASS__] = [[$this, 'preRenderCallback'], [$bundle]];
        }
    }
    
    public function formFieldRender(array &$data, Form\Form $form)
    {
        $html = [
            '<div class="drts-display-display" data-display-id="' . $this->_application->H($data['#display']['id']) . '" style="position:relative;">',
            '<div class="drts-display-element-wrapper">',
        ];
        $name = $this->_application->H($data['#name']);
        foreach ($data['#display']['elements'] as $element) {
            $html[] = $this->_getElementHtml($name, $element, $data['#display']['bundle_name']);
        }
        $html[] = '</div>';
        $html[] = '<div class="drts-display-control">
            <button disabled class="drts-display-add-element drts-display-add-element-main drts-bs-btn drts-bs-btn-success" rel="sabaitooltip" title="' . $this->_application->H(__('Add Element', 'directories')) . '"><i class="fas fa-plus"></i></button>
        </div>';
        $html[] = '</div>';
        $this->_render(implode(PHP_EOL, $html), $data, $form);
    }
    
    protected function _getElementHtml($name, array $element, $bundleName)
    {
        ob_start();
        include __DIR__ . '/element.php';
        return ob_get_clean();
    }
    
    protected function _getElementDataArray($bundleName, array $element)
    {
        return $this->_application->Display_AdminElement_getDataArray(
            $bundleName,
            $element['element_id'],
            $element['name'],
            $this->_elementTypes[$element['type']],
            $element['label'],
            $element['title'],
            (array)$element['info'],
            (array)$element['advanced']
        );
    }
    
    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        if (!$display = $this->_application->getModel('Display', 'Display')->fetchById($data['#display']['id'])) {
            throw new Exception\RuntimeException('Invalid display.');
        }
        
        $elements = $value;
        $value = null;
        
        $current_elements = $updated_elements = [];
        foreach ($display->Elements as $element) {
            $current_elements[$element->id] = $element;
        }
        
        if (!empty($elements)) {
            $weight = $parent_id = 0;
            $prev_element_id = null;
            $parent_ids = [];
            foreach ($elements as $element_id) {                
                if ($element_id === '__CHILDREN_START__') {
                    $parent_ids[] = $parent_id;
                    $parent_id = $prev_element_id;
                    continue;
                }
                
                if ($element_id === '__CHILDREN_END__') {
                    $parent_id = array_pop($parent_ids);
                    continue;
                }
                
                if (!isset($current_elements[$element_id])) continue;

                $element = $updated_elements[$element_id] = $current_elements[$element_id];

                $element->weight = ++$weight;
                $element->parent_id = $parent_id;
                
                $prev_element_id = $element_id;
            }
        }
        
        // Remove elements
        $elements_removed = [];
        foreach (array_diff_key($current_elements, $updated_elements) as $current_element) {
            if ($current_element->system) continue;

            $elements_removed[$current_element->id] = $current_element;
        }
        $this->_application->getModel(null, 'Display')->commit();
        
        if (!empty($elements_removed)
            || !empty($updated_elements)
        ) {
            $bundle = $this->_application->Entity_Bundle($data['#display']['bundle_name']);
            foreach ($elements_removed as $element) {
                try {
                    $this->_application->Display_AdminElement_delete($bundle, $element->id);
                } catch (Exception\IException $e) {
                    $this->_application->logError($e);
                }
            }
            foreach ($updated_elements as $element) {
                if (!$element_impl = $this->_application->Display_Elements_impl($bundle, $element->name, true)) continue;
                
                $settings = (array)@$element->data['settings'] + (array)$element_impl->displayElementInfo($bundle, 'default_settings');
                try {
                    $element_impl->displayElementOnPositioned($bundle, $settings, $element->weight);
                } catch (Exception\IException $e) {
                    $this->_application->logError($e);
                }
            }
        }
        
        if (!isset($data['#clear_display_cache'])
            || false !== $data['#clear_display_cache']
        ) {
            // Clear display and elements cache
            $this->_application->Display_Display_clearCache($display);
            $this->_application->getPlatform()->deleteCache('display_elements_' . $bundle->name);
        }
    }
    
    public function preRenderCallback(Form\Form $form, $bundle)
    {   
        $options = [
            'addElementTitle' => __('Add Element', 'directories'),
            'editElementTitle' => __('Edit Element', 'directories'),
            'deleteElementTitle' => __('Delete Element', 'directories'),
            'deleteConfirm' => __('Are you sure?', 'directories'),
            'elementTypes' => $this->_application->Display_Elements_types($bundle),
        ];
        $admin_path = $this->_application->Entity_BundleTypeInfo($bundle, 'admin_path');
        $admin_path = strtr($admin_path, [':bundle_name' => $bundle->name, ':directory_name' => $bundle->group, ':bundle_group' => $bundle->group]);
        foreach (self::$_fields[$form->settings['#id']] as $id => $data) {
            $_options = [
                'selector' => '#' . $id,
                'name' => $data['name'],
                'listElementsUrl' => (string)$this->_application->Url($admin_path . '/displays/list_elements', array('display_id' => $data['display_id']), '', '&'),
                'addElementUrl' => (string)$this->_application->Url($admin_path . '/displays/add_element', array('display_id' => $data['display_id']), '', '&'),
                'editElementUrl' => (string)$this->_application->Url($admin_path . '/displays/edit_element', array('display_id' => $data['display_id']), '', '&'),
            ];
            $form->settings['#js_ready'][] = sprintf('DRTS.Display.adminDisplay(%s);', $this->_application->JsonEncode($_options + $options));
        }
        $this->_application->getPlatform()->loadJqueryUiJs(array('sortable', 'draggable', 'effects-highlight'))
            ->addJsFile('display-admin-display.min.js', 'drts-display-admin-display', array('drts', 'jquery-ui-sortable'))
            ->addCssFile('display-admin-display.min.css', 'drts-display-admin-display', array('drts'));
        if ($this->_application->getPlatform()->isRtl()) {
            $this->_application->getPlatform()->addCssFile('display-admin-display-rtl.min.css', 'drts-display-admin-display-rtl', array('drts-display-admin-display'));
        }
        $this->_application->Form_Scripts();
    }
}