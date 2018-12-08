<?php
namespace SabaiApps\Directories\Component\WordPress\Helper;

use SabaiApps\Directories\Application;

class RolesHelper
{
    protected $_rolesEnabled;
    
    public function help(Application $application)
    {
        if (!isset($this->_rolesEnabled)) {
            $this->_rolesEnabled = $application->Filter('wordpress_roles', false);
            if (is_array($this->_rolesEnabled)) {
                foreach ($application->getPlatform()->getAdministratorRoles() as $admin_role_name) {
                    if (!in_array($admin_role_name, $this->_rolesEnabled)) {
                        $this->_rolesEnabled[] = $admin_role_name;
                    }
                }
            }
        }
        $roles = wp_roles()->roles;
        if (is_array($this->_rolesEnabled)) {
            foreach (array_keys($roles) as $role_name) {
                if (!in_array($role_name, $this->_rolesEnabled)) {
                    unset($roles[$role_name]);
                }
            }
        }
        return $roles;
    }
}