<?php
namespace SabaiApps\Directories\Component\Display\Model;

class Display extends Base\Display
{
    public function isAmp()
    {
        return strpos($this->name, 'amp_') === 0;
    }
    
    public function getCssClass()
    {
        if ($this->type === 'entity') {
            return 'drts-display--' . str_replace('_', '-', $this->name);
        }
        return 'drts-display-name-' . $this->type . '-' . str_replace('_', '-', $this->name);
    }
}

class DisplayRepository extends Base\DisplayRepository
{
}