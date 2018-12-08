<?php
namespace SabaiApps\Directories\Component\WooCommerce;

use SabaiApps\Directories\Component\Payment\IOrder;

class PaymentOrder implements IOrder
{
    protected $_order, $_itemId, $_itemName, $_itemMeta, $_entityId, $_orderItem, $_isOrderMultiple;
    
    public function __construct(\WC_Order $order, $itemId, $itemName, $itemMeta, $entityId)
    {
        $this->_order = $order;
        $this->_itemId = $itemId;
        $this->_itemName = $itemName;
        $this->_itemMeta = $itemMeta;
        $this->_entityId = $entityId;
        $order_items = $this->_order->get_items('line_item');
        $this->_orderItem = isset($order_items[$this->_itemId]) ? $order_items[$this->_itemId] : false;
        $this->_isOrderMultiple = count($order_items) > 1;
    }
    
    public function paymentOrderId($display = false)
    {
        return $display ? $this->_order->get_id() : $this->_itemId;
    }
    
    public function paymentOrderName()
    {
        return $this->_itemName;
    }
    
    public function paymentOrderAction()
    {
        return $this->_itemMeta['action'];
    }
    
    public function paymentOrderAdminUrl()
    {
        return admin_url('post.php?post=' . $this->_order->get_id() . '&action=edit');
    }
    
    public function paymentOrderStatus()
    {
        $status = $this->_order->get_status();
        $name = wc_get_order_status_name($status);
        switch ($status) {
            case 'completed':
            case 'active': // WC Subscriptions
                return array($name, 'success');
            case 'cancelled':
            case 'refunded':
            case 'failed':
            case 'expired': // WC Subscriptions
                return array($name, 'danger');
            case 'pending':
            case 'pending-cancel': // WC Subscriptions
                return array($name, 'warning');
            default:
                return $name;
        }
    }
    
    public function paymentOrderTotalHtml()
    {
        $html = $this->_order->get_formatted_order_total();
        if ($this->_isOrderMultiple
            && $this->_orderItem
        ) {
            $html .= ' (' . $this->_order->get_formatted_line_subtotal($this->_orderItem) . ')';
        }
        return $html;
    }
    
    public function paymentOrderTimestamp()
    {
        return ($date = $this->_order->get_date_created()) ? $date->getTimestamp() : null;
    }
    
    public function paymentOrderEntityId()
    {
        return $this->_entityId;
    }
    
    public function paymentOrderEntityType()
    {
        return 'post';
    }
    
    public function paymentOrderHtml()
    {
        ob_start();
        echo '<div class="woocommerce">';
        wc_get_template('myaccount/view-order.php', array(
            'order'     => $this->_order,
            'order_id'  => $this->_order->get_id(),
        ));
        echo '</div>';
        
        return ob_get_clean();
    }
        
    public function paymentOrderWasItemDeactivated()
    {
        return !empty($this->_itemMeta['was_deactivated']);
    }
}