<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;

class PermissionSettingsFormHelper
{
    protected $_roles, $_adminRoles = [], $_rolePerms;
    
    public function help(Application $application, $componentName, $group = null, array $parents = [])
    {
        if (!isset($this->_roles)) {
            $this->_roles = $this->_rolePerms = [];
            foreach ($application->WordPress_Roles() as $role_name => $role_info) {
                if (!empty($role_info['capabilities'][DRTS_WORDPRESS_ADMIN_CAPABILITY])
                    || !empty($role_info['capabilities']['manage_directories'])
                ) {
                    $this->_adminRoles[$role_name] = $role_name;
                }
                $this->_roles[$role_name] = $role_info['name'];
                $this->_rolePerms[$role_name] = $role_info['capabilities'];
            }
            $this->_roles['_guest_'] = __('Guest', 'directories');
            $this->_rolePerms['_guest_'] = $application->getPlatform()->getOption('guest_permissions', []);
        }
        
        $perms = [];
        // Add entity perms
        foreach ($application->Entity_Bundles_sort(null, $componentName, $group) as $bundle) {            
            if (!$_perms = $application->Entity_Permissions($bundle)) continue;
            
            $perms[$bundle->name] = array(
                'title' => $bundle->getLabel('singular'),
                'perms' => $_perms,
                'suffix' =>  '_' . $bundle->name,
            );
        }

        // Add extra perms if any
        $perms = $application->Filter('wordpress_permissions', $perms, array($componentName, $group));
        
        $form = array('#tabs' => [], '#tab_style' => 'pill_less_margin', '#perms' => $perms);
        $all_perms = [];
        $weight = 1;
        foreach ($perms as $key => $_perms) {
            $form['#tabs'][$key] = array(
                '#active' => $weight === 1,
                '#title' => $_perms['title'],
                '#weight' => ++$weight,
            );
            $form[$key] = array(
                '#tab' => $key,
            ) + $this->_getPermsForm($_perms['perms'], $all_perms, isset($_perms['suffix']) ? $_perms['suffix'] : null);
        }
        $form['#submit'][9][] = array(array($this, 'submitForm'), array($application, $parents, $all_perms));

        return $form;
    }
    
    protected function _getPermsForm($perms, &$allPerms, $suffix = '')
    {
        $form = array(
            '#type' => 'grid',
            '#class' => 'drts-data-table',
            'label' => array(
                '#type' => 'item',
                '#title' => '',
            ),
            '#row_attributes' => array(
                '@all' => array('label' => array('style' => 'width:20%;')),
            ),
        );
        // Add columns
        $role_weight = 0;
        foreach ($this->_roles as $role_name => $role) {
            $form[$role_name] = array(
                '#type' => 'checkbox',
                '#title' => $role,
                '#disabled' => $is_admin_role = in_array($role_name, $this->_adminRoles),
                '#weight' => $is_admin_role ? 0 : ++$role_weight,
                '#switch' => false,
            );
        }
        // Add rows
        foreach ($perms as $perm_name => $perm) {
            $perm_name .= $suffix;
            $form['#default_value'][$perm_name] = array('label' => $perm['title']);
            foreach ($this->_roles as $role_name => $role) {
                if (isset($this->_adminRoles[$role_name])) {
                    $form['#default_value'][$perm_name][$role_name] = true; 
                } elseif ($role_name === '_guest_') {
                    if (empty($perm['guest_allowed'])) {
                        $form['#row_settings'][$perm_name][$role_name] = array('#attributes' => array('disabled' => 'disabled'));
                    } else {
                        $form['#default_value'][$perm_name][$role_name] = !empty($this->_rolePerms[$role_name]['drts_' . $perm_name]);
                    }
                } else {
                    $form['#default_value'][$perm_name][$role_name] = !empty($this->_rolePerms[$role_name]['drts_' . $perm_name]);
                }
            }
            $allPerms[$perm_name] = $perm_name;
        }
        
        return $form;
    }
    
    public function submitForm(Form\Form $form, Application $application, $parents, $allPerms)
    {
        $values = $form->getValue($parents);
        $roles_processed = $guest_perms = [];
        foreach ($this->_extractPermissionsByRole($form, $values) as $role_name => $perms) {
            $roles_processed[$role_name] = 1;
            if ($role_name === '_guest_') {
                $guest_perms = array_keys($perms);
                continue;
            }
            if (in_array($role_name, $this->_adminRoles)
                || (!$role = get_role($role_name))
            ) continue;

            // Remove all perms first and then add back perms selected
            foreach ($allPerms as $perm) {
                $role->remove_cap('drts_' . $perm);
            }
            foreach (array_keys($perms) as $perm) {
                $role->add_cap('drts_' . $perm);
            }
        }
        // Add all perms to admin roles
        foreach (array_keys($this->_adminRoles) as $role_name) {
            if (!$role = get_role($role_name)) continue;
            
            foreach ($allPerms as $perm) {
                $role->add_cap('drts_' . $perm);
            }
        }
        // Remove perms from roles without any perms selected
        foreach (array_keys($this->_roles) as $role_name) {
            if (isset($roles_processed[$role_name])
                || in_array($role_name, $this->_adminRoles)
                || (!$role = get_role($role_name))
            ) continue;
            
            foreach ($allPerms as $perm) {
                $role->remove_cap('drts_' . $perm);
            }
        }
        // Update guest perms
        $current_guest_perms = $application->getPlatform()->getOption('guest_permissions', []);
        foreach ($allPerms as $perm) {
            if (!in_array($perm, $guest_perms)) {
                unset($current_guest_perms['drts_' . $perm]);
            } else {
                $current_guest_perms['drts_' . $perm] = 1;
            }
        }
        $application->getPlatform()->setOption('guest_permissions', $current_guest_perms);
    }
    
    protected function _extractPermissionsByRole($form, $values)
    {
        $ret = [];
        foreach (array_intersect_key($values, $form->settings['#perms']) as $perms) {
            foreach ($perms as $perm_name => $roles) {
                foreach ($roles as $role_name => $value) {
                    if (!isset($this->_roles[$role_name]) || empty($value)) continue;
                        
                    $ret[$role_name][$perm_name] = $value;
                }
            }
        }
        return $ret;
    }
}