<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

abstract class AbstractSubmitEntity extends Form\AbstractMultiStepController
{
    protected function _getSubmitEntityForm(Context $context, array &$formStorage, $entityOrBundle, $btnLabel = null, $isEdit = null, $wrap = null)
    {
        $context->addTemplate('entity_form');
        $btn = is_array($btnLabel) ? $btnLabel : array('#btn_label' => $btnLabel);
        $this->_submitButtons[$context->currentStep] = $btn + array(
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
        );

        return $this->Entity_Form($entityOrBundle, $this->_getSubmitEntityFormOptions($context, $formStorage, $isEdit, $wrap));
    }

    protected function _getSubmitEntityFormOptions(Context $context, array &$formStorage, $isEdit = null, $wrap = null)
    {
        return [
            'values' => $this->_getSubimttedValues($context, $formStorage),
            'pre_render_display' => true,
            'wrap' => isset($wrap) ? $wrap : 'drts',
            'is_edit' => $isEdit,
        ];
    }

    protected function _getBundle(Context $context, array $formStorage)
    {
        if ($entity = $this->_getEntity($context, $formStorage)) {
            return $this->Entity_Bundle($entity->getBundleName(), null, '', true);
        }

        throw new Exception\RuntimeException(get_class($this) . '::_getBundle() needs to be implemented!');
    }

    abstract protected function _getEntity(Context $context, array $formStorage);
}
