<?php
namespace SabaiApps\Directories\Component\WooCommerce;

use SabaiApps\Directories\Component\Payment\IPlan;

class PaymentPlan implements IPlan
{
    protected $_product;
    
    public function __construct(IProduct $product)
    {
        $this->_product = $product;
    }
    
    public function paymentPlanId()
    {
        return $this->_product->get_id();
    }

    public function paymentPlanName()
    {
        return $this->_product->get_slug();
    }
    
    public function paymentPlanBundleName()
    {
        return $this->_product->get_sabai_entity_bundle_name();
    }
        
    public function paymentPlanType()
    {
        return $this->_product->get_sabai_plan_type();
    }
        
    public function paymentPlanTitle()
    {
        return $this->_product->get_name();
    }
    
    public function paymentPlanDescription()
    {
        return ($desc = $this->_product->get_short_description()) ? $desc : $this->_product->get_description();
    }
    
    public function paymentPlanFeatures()
    {
        return $this->_product->get_sabai_entity_features();
    }
    
    public function paymentPlanIsFeatured()
    {
        return $this->_product->get_featured();
    }
    
    public function paymentPlanPrice($html = false)
    {
        if (!$html) return $this->_product->get_price();
        
        // Remove unwanted zero from price html
        add_filter('woocommerce_price_trim_zeros', '__return_true', 99999);
        $html = $this->_product->get_price_html();
        // Remove filter added
        remove_filter('woocommerce_price_trim_zeros', '__return_true', 99999);
        return $html;
    }
    
    public function paymentPlanTotal()
    {
        return wc_get_price_including_tax($this->_product);
    }
}