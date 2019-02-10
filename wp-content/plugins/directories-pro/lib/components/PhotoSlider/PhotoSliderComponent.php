<?php
namespace SabaiApps\Directories\Component\PhotoSlider;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\View;
use SabaiApps\Directories\Component\Field;

class PhotoSliderComponent extends AbstractComponent implements
    View\IModes,
    Field\IRenderers
{
    const VERSION = '1.2.23', PACKAGE = 'directories-pro';
    
    public static function description()
    {
        return 'Displays a list of content or content feilds in a photo slider.';
    }
    
    public function viewGetModeNames()
    {
        return array('photoslider');
    }
    
    public function viewGetMode($name)
    {
        return new ViewMode\PhotoSliderViewMode($this->_application, $name);
    }
    
    public function fieldGetRendererNames()
    {
        return array('photoslider');
    }
    
    public function fieldGetRenderer($name)
    {
        return new FieldRenderer\PhotoSliderFieldRenderer($this->_application, $name);
    }
}