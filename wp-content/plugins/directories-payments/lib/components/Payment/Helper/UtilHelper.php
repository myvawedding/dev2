<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;

class UtilHelper
{
    public function actionLabel(Application $application, $action, $wasDeactivated = false)
    {
        switch ($action) {
            case 'upgrade':
                return __('Upgrade', 'directories-payments');
            case 'renew':
                return __('Renewal', 'directories-payments');
            case 'order_addon':
                return __('Order Add-on', 'directories-payments');
            case 'resubscribe':
                return __('Resubscribe', 'directories-payments');
            case 'claim':
                return __('Claim', 'directories-payments');
            default:
                return $wasDeactivated ? __('Re-activation', 'directories-payments') : __('Initial Post', 'directories-payments');
        }
    }
    
    public function hasPendingOrder(Application $application, Entity\Type\IEntity $entity, array $actions = null)
    {
        if (!$payment_component = $application->getComponent('Payment')->getPaymentComponent()) return false;
        
        return $payment_component->paymentHasPendingOrder($entity, isset($actions) ? $actions: ['add', 'submit', 'renew', 'upgrade', 'order_addon']);
    }
}
