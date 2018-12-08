<?php
namespace SabaiApps\Directories\Component\Directory\Model;

use SabaiApps\Directories\Component\Directory\Model\Base;

class Directory extends Base\Directory
{
    public function getLabel($language = null)
    {
        return $this->_model->Platform()->translateString($this->data['label'], $this->name, 'directory_directory', $language);
    }
    
    public function getIcon()
    {
        return $this->data['icon'];
    }
}

class DirectoryRepository extends Base\DirectoryRepository
{
}