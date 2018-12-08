<?php
namespace SabaiApps\Directories\Component\Form\Controller;

use SabaiApps\Directories\Context;

abstract class AbstractUploadFile extends UploadFile
{
    protected function _render(Context $context, array $file, array $token)
    {
        $context->addTemplate('form_uploadfile2');
        $context->files = array($this->_saveFile($file, $token));
    }
    
    abstract protected function _saveFile(array $file, array $token);
}