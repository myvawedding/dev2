<?php
namespace SabaiApps\Directories\Component\Payment\Controller;

use SabaiApps\Directories\Component\FrontendSubmit;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;

class ClaimEntity extends FrontendSubmit\Controller\AddChildEntity
{
    protected $_reloadStepsOnNextStep = true;

    protected function _getSteps(Context $context, array &$formStorage)
    {
        if (false === $steps = parent::_getSteps($context, $formStorage)) return false;

        if (!$this->_isPaymentEnabled($context, $formStorage)) return $steps;

        if (!$this->getUser()->isAnonymous()
            || $this->_isGuestCheckoutEnabled()
        ) {
            $steps['select_plan'] = array('order' => 6);
        }

        return $steps;
    }

    public function _getFormForStepSelectPlan(Context $context, array &$formStorage)
    {
        $parent_bundle = $this->Entity_Bundle($context->entity->getBundleName(), null, '', true);
        $form = $this->Payment_Plans_form(
            $parent_bundle->name,
            $this->Filter('payment_base_plan_types', ['base'], [$parent_bundle])
        );
        if (!empty($form['plan']['#options'])) {
            $this->_submitable = true;
            if ($this->getComponent('Payment')->getConfig('selection', 'allow_none')) {
                $none_label = $this->getComponent('Payment')->getConfig('selection', 'none_label');
                $form['plan']['#options'][0] = $this->getPlatform()->translateString($none_label, 'no_payment_plan_label', 'payment');
            }
            $form['#action'] = $this->_getFormAction($context);
        } else {
            $this->_submitable = false;
        }
        $this->_cancelUrl = $this->Entity_Url($context->entity);
        return $form;
    }

    public function _submitFormForStepAdd(Context $context, Form\Form $form)
    {
        parent::_submitFormForStepAdd($context, $form);

        if (!$this->_isPaymentEnabled($context, $form->storage)
            || (!$plan = $this->_getSelectedPlan($context, $form->storage))
        ) return;

        if (!$entity = $this->_getEntity($context, $form->storage)) return false; // this should not happen

        $this->_getPaymentComponent(true)->paymentOnSubmit($entity, $plan, 'claim');
    }

    protected function _complete(Context $context, array $formStorage)
    {
        if (!$this->_isPaymentEnabled($context, $formStorage)
            || !$this->_getSelectedPlan($context, $formStorage)
        ) {
            parent::_complete($context, $formStorage);
            return;
        }

        $context->setSuccess($this->_getPaymentComponent(true)->paymentCheckoutUrl());
    }

    protected function _getEntityStatus(Context $context, Form\Form $form, Entity\Model\Bundle $bundle)
    {
        if (!$this->_isPaymentEnabled($context, $form->storage)
            || !$this->_getSelectedPlan($context, $form->storage)
        ) {
            return parent::_getEntityStatus($context, $form, $bundle);
        }
        return $this->Entity_Status($bundle->entitytype_name, 'draft');
    }

    protected function _getSelectedPlan(Context $context, array $formStorage)
    {
        if (!empty($formStorage['values']['select_plan']['plan'])) {
            return $this->_getPaymentComponent(true)
                ->paymentGetPlan($formStorage['values']['select_plan']['plan']);
        }
    }

    protected function _getSubmitButtonForStepAdd(Context $context, array &$formStorage)
    {
        if (!$this->_isPaymentEnabled($context, $formStorage)
            || !$this->_getSelectedPlan($context, $formStorage)
        ) {
            return parent::_getSubmitButtonForStepAdd($context, $formStorage);
        }

        return $this->Filter(
            'payment_add_to_cart_text',
            __('Add to cart', 'directories-payments'),
            [$this->_getBundle($context, $formStorage)->name]
        );
    }

    protected function _getRedirectGuestUrlParams(Context $context, array $formStorage)
    {
        $ret = parent::_getRedirectGuestUrlParams($context, $formStorage);
        $ret[] = 'plan';

        return $ret;
    }

    protected function _getPaymentComponent($throwError = true)
    {
        return $this->getComponent('Payment')->getPaymentComponent($throwError);
    }

    protected function _isGuestCheckoutEnabled()
    {
        if (!$payment_component = $this->_getPaymentComponent(false)) return false;

        return $payment_component->paymentIsGuestCheckoutEnabled();
    }

    protected function _isPaymentEnabled(Context $context, array $formStorage)
    {
        if ($this->getUser()->isAnonymous()
            && !$this->_isGuestCheckoutEnabled()
        ) return false;

        if ((!$parent_bundle = $this->Entity_Bundle($context->entity->getBundleName()))
            || empty($parent_bundle->info['payment_enable'])
        ) return false;

        return $this->_getPaymentComponent(false) ? true : false;
    }

    protected function _getSubmitEntityFormOptions(Context $context, array &$formStorage, $isEdit = null, $wrap = null)
    {
        $ret = parent::_getSubmitEntityFormOptions($context, $formStorage, $isEdit, $wrap);
        if ($this->_isPaymentEnabled($context, $formStorage)
            && ($plan = $this->_getSelectedPlan($context, $formStorage))
        ) {
            $ret['payment_plan'] = $plan;
        }
        return $ret;
    }
}
