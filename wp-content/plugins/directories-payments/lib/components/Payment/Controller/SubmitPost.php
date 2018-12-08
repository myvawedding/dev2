<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\Dashboard;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Framework\User\AbstractIdentity;

class SubmitPost extends Dashboard\Controller\SubmitPost
{
    protected function _getSteps(Context $context, array &$formStorage)
    {   
        if (!$this->getComponent('Payment')->getPaymentComponent()) {
            return parent::_getSteps($context, $formStorage);
        }
        
        if ($this->Payment_Util_hasPendingOrder($context->entity)) {
            $context->setError(
                __('There are currently one or more pending orders for the item selected.', 'directories-payments'),
                '/' . $this->getPlatform()->getSlug('Dashboard', 'dashboard')
            );
            return;
        }
        
        return array('select_plan' => array('order' => 3));
    }
    
    public function _getFormForStepSelectPlan(Context $context, array &$formStorage)
    {
        $form = $this->Payment_Plans_form(
            $entity = $this->_getEntity($context, $formStorage),
            $this->Filter('payment_base_types', ['base'], [$entity->getBundleName()]),
            $context->action === 'upgrade'
        );
        if (empty($form['plan']['#options'])) {
            $this->_submitable = false;
        } else {
            $this->_submitable = true;
            $this->_submitButtons[] = array(
                '#btn_color' => 'primary',
                '#btn_label' => $this->Filter(
                    'payment_add_to_cart_text',
                    __('Add to cart', 'directories-payments'),
                    [$entity->getBundleName()]
                ),
                '#btn_size' => 'lg',
                '#attributes' => ['data-modal-title' => ''], // prevents modal title from changing on submit
            );
        }
        return $form;
    }
    
    public function _submitFormForStepSelectPlan(Context $context, Form\Form $form)
    {
        if ((!$plan = $this->_getSelectedPlan($context, $form->storage))
            || (!$entity = $this->_getEntity($context, $form->storage))
        ) return false; // this should not happen
        
        $this->getComponent('Payment')->getPaymentComponent(true)
            ->paymentOnSubmit($entity, $plan, $context->action); 
    }
    
    protected function _getSelectedPlan(Context $context, array $formStorage)
    {
        if (!empty($formStorage['values']['select_plan']['plan'])) {
            return $this->getComponent('Payment')->getPaymentComponent(true)
                ->paymentGetPlan($formStorage['values']['select_plan']['plan']);
        }
    }
    
    protected function _getEntityStatus(Context $context, Entity\Type\IEntity $entity, AbstractIdentity $identity = null)
    {
        if (!$this->getComponent('Payment')->getPaymentComponent()) {
            return parent::_getEntityStatus($context, $entity, $identity);
        }
        
        return $this->Entity_Status($entity->getType(), 'draft');
    }
    
    protected function _complete(Context $context, array $formStorage)
    {
        $context->setSuccess($this->getComponent('Payment')->getPaymentComponent(true)->paymentCheckoutUrl());
    }
}