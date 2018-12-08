<?php
namespace SabaiApps\Directories\Component\Dashboard\Controller;

use SabaiApps\Directories\Component\FrontendSubmit;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;
use SabaiApps\Framework\User\AbstractIdentity;

class SubmitPost extends FrontendSubmit\Controller\AbstractSubmitEntity
{   
    protected function _getSteps(Context $context, array &$formStorage)
    {
        if (!isset($formStorage['redirect'])) {
            $formStorage['redirect'] = $context->getRequest()->asBool('redirect');
        }
        return array('submit' => array('order' => 3));
    }
    
    protected function _getEntity(Context $context, array $formStorage)
    {
        return $context->entity;
    }
    
    public function _getFormForStepSubmit(Context $context, array &$formStorage)
    {
        $form = [];
        $form['#header'][] = '<div class="drts-bs-alert drts-bs-alert-info">' . $this->H(__('Press the button below to submit for review.', 'directories-frontend')) . '</div>';
        
        return $form;
    }
    
    public function _submitFormForStepSubmit(Context $context, Form\Form $form)
    {
        if (!$context->entity->isPublished()) { // keep published 
            $status = $this->_getEntityStatus($context, $context->entity, $this->getUser()->getIdentity());
            if ($status !== $context->entity->getStatus()) {
                $context->entity = $this->Entity_Save($context->entity, array('status' => $status));
            }
        }
        // Updated translated posts
        foreach ($this->Entity_Translations($context->entity, false) as $entity) {
            if (!$entity->isPublished()) { // keep published 
                $status = $this->_getEntityStatus($context, $entity);
                if ($status !== $entity->getStatus()) {
                    $this->Entity_Save($entity, array('status' => $status));
                }
            }
        }
    }
    
    protected function _getEntityStatus(Context $context, Entity\Type\IEntity $entity, AbstractIdentity $identity = null)
    {        
        $bundle = $this->Entity_Bundle($entity);
        if (!empty($bundle->info['public'])
            && !$this->HasPermission('entity_publish_' . $bundle->name, isset($identity) ? $identity : $this->Entity_Author($entity))
        ) {
            $status = 'pending';
        } else {
            $status = 'publish';
        }
        return $this->Entity_Status($entity->getType(), $status);
    }
    
    protected function _complete(Context $context, array $formStorage)
    {
        $context->setSuccess($this->_getSuccessUrl($context, $formStorage));
        if (!$context->entity->isPublished()) {
            $context->addFlash(__('Your item has been submitted successfully. We will review your submission and publish it when it is approved.', 'directories-frontend'));
        } else {
            $context->addFlash(__('Your item has been submitted and published successfully.', 'directories-frontend'));
        }
    }
    
    protected function _getSuccessUrl(Context $context, array $formStorage)
    {
        if (!empty($formStorage['redirect'])) {
            return $this->Entity_PermalinkUrl($context->entity);
        }
        
        return $this->getComponent('Dashboard')->getPostsPanelUrl($context->entity);
    }
}