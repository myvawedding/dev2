<?php
namespace SabaiApps\Directories\Component\Payment\DashboardPanel;

use SabaiApps\Directories\Component\Dashboard;

class PaymentsDashboardPanel extends Dashboard\Panel\AbstractPanel
{    
    protected function _dashboardPanelInfo()
    {   
        return array(
            'weight' => 10,
        );
    }
    
    public function dashboardPanelLabel()
    {
        return __('Payments', 'directories-payments');
    }
    
    protected function _dashboardPanelLinks()
    {
        if (!$payment_component = $this->_application->getComponent('Payment')->getPaymentComponent()) return;
        
        $ret = [
            'orders' => [
                'title' => __('Orders', 'directories-payments'),
                'icon' => 'fas fa-shopping-basket',
            ],
        ];
        if ($payment_component->paymentIsSubscriptionEnabled()) {
            $ret['subscriptions'] = [
                'title' => __('Subscriptions', 'directories-payments'),
                'icon' => 'fas fa-sync-alt',
            ];
        }

        return $ret;
    }
    
    public function dashboardPanelContent($link, array $params)
    {
        switch ($link) {
            case 'subscriptions':
                $path = '/payment_subscriptions';
                break;
            default:
                $path = '/payment_orders';
        }
        return $this->_application->getPlatform()->render(
            $this->_application->getComponent('Dashboard')->getPanelUrl('payment_payments', $link, $path, [], true),
            ['is_dashboard' => false] // prevent rendering duplicate panel sections on reload panel
        );
    }
}