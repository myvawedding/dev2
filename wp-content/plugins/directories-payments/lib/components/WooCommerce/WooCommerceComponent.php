<?php
namespace SabaiApps\Directories\Component\WooCommerce;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class WooCommerceComponent extends AbstractComponent implements Payment\IPayment
{
    const VERSION = '1.2.15', PACKAGE = 'directories-payments';

    public static function description()
    {
        return 'Enables integration with WooCommerce for accepting payments.';
    }

    public function onCorePlatformWordpressInit()
    {
        if (!$this->isWooCommerceActive()) return;

        // Register product types
        $product_types = $this->_registerProductTypes();

        if (is_admin()) {
            // Product admin page
            add_action('admin_footer', [$this, 'adminFooterAction']);
            add_filter('product_type_selector', [$this, 'productTypeSelectorFilter']);
            add_filter('woocommerce_product_data_tabs', [$this, 'woocommerceProductDataTabsFilter'], 11);
            add_action('woocommerce_product_data_panels', [$this, 'woocommerceProductDataPanelsAction']);
            foreach ($product_types as $type) {
                add_action('woocommerce_process_product_meta_' . $type, [$this, 'woocommerceProcessProductMetaAction']);
            }
        }

        // Cart
        add_filter('woocommerce_get_item_data', [$this, 'woocommerceGetItemDataFilter'], 10, 2);
        add_filter('woocommerce_cart_calculate_fees', [$this, 'woocommerceCartCalculateFeesFilter']);

        // Checkout
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'woocommerceCheckoutCreateOrderLineItemAction'], 10, 4);
        add_action('woocommerce_checkout_order_processed', [$this, 'woocommerceCheckoutOrderProcessedAction'], 10, 3);
        add_filter('woocommerce_order_again_cart_item_data', [$this, 'woocommerceOrderAgainCartItemDataFilter'], 10, 3);

        // Monitor order status
        add_action('woocommerce_order_status_changed', [$this, 'woocommerceOrderStatusChangedAction'], 10, 3);

        // Remove order again button
        add_filter('wc_get_template', [$this, 'wcGetTemplateFilter'], 10, 3);

        // Show order details
        add_filter('woocommerce_order_item_display_meta_key', [$this, 'woocommerceOrderItemDisplayMetaKeyFilter'], 10, 2);
        add_filter('woocommerce_order_item_display_meta_value', [$this, 'woocommerceOrderItemDisplayMetaValueFilter'], 10, 2);

        // My Account page
        $this->_initMyAccountPage();

        // WooCommerce Subscriptions
        if ($this->isWCSActive()) {
            // Required to save subscription meta data
            add_filter('woocommerce_subscription_product_types', [$this, 'wcSubscriptionProductTypesFilter']);

            // Required to show price HTML
            add_filter('woocommerce_is_subscription', [$this, 'wcIsSubscriptionFilter'], 10, 3);

            // Monitor subscription status
            add_action('woocommerce_subscription_status_updated', [$this, 'wcSubscriptionStatusUpdatedAction'], 10, 3);
        }
    }

    protected function _registerProductTypes()
    {
        $ret = [];
        $product_types = $this->_getProductTypes(false);
        foreach (array_keys($product_types) as $plan_type) {
            foreach (array_keys($product_types[$plan_type]['name']) as $type) {
                $ret[] = $type;
                $class = isset($product_types[$plan_type]['class']) ? $product_types[$plan_type]['class'] : '\WC_Product_Simple';
                $code = 'class WC_Product_' . $type . ' extends ' . $class . ' implements \SabaiApps\Directories\Component\WooCommerce\IProduct
{
    public function get_type()
    {
        return "' . $type . '";
    }

    public function get_sabai_entity_bundle_name()
    {
        $plan_type = $this->get_sabai_plan_type();
        return $plan_type === "base" ?
            substr($this->get_type(), strlen("drts_")) : // remove prefix
            substr($this->get_type(), strlen("drts_"), -1 * (strlen($plan_type) + 2)); // remove prefix and "__{$plan_type}" suffix      
    }

    public function get_sabai_plan_type()
    {
        return "' . $plan_type . '";
    }

    public function get_sabai_entity_features()
    {
        return (array)get_post_meta($this->get_id(), "_drts_entity_features", true);
    }
    
    public function get_reviews_allowed($context = "view") {
        return "closed";
    }
}';
                eval($code);
            }
        }

        return $ret;
    }

    public function adminFooterAction()
    {
        if ('product' !== get_post_type()) return;

        ?><script type='text/javascript'>
        jQuery('body').bind('woocommerce-product-type-change',function() {
            jQuery('#woocommerce-product-data .panel-wrap').attr('data-drts-woocommerce-product-type', jQuery('select#product-type').val());
        });
        </script><?php

        $product_types = array_keys($this->_getProductTypes());
        $show_class = 'show_if_' . implode(' show_if_', $product_types);
        $hide_class = 'hide_if_' . implode(' hide_if_', $product_types);
        ?>
        <script type='text/javascript'>
            document.addEventListener("DOMContentLoaded", function(e) {
                var $ = jQuery;
                $('#woocommerce-product-data')
                    .find('.options_group.pricing').addClass('<?php echo $show_class;?>').end()
                    .find('.options_group.reviews').addClass('<?php echo $hide_class;?>').end()
                    .find('.options_group.show_if_downloadable').addClass('<?php echo $hide_class;?>').end()
                    .find('._tax_status_field').closest('.options_group').addClass('<?php echo $show_class;?>');
                if ($.inArray($('select#product-type').val(), ['<?php echo implode("', '", $product_types);?>']) !== -1) {
                    $('#woocommerce-product-data')
                        .find('.options_group.pricing').css('display', 'block').end()
                        .find('.options_group.reviews').css('display', 'none').end()
                        .find('.options_group.show_if_downloadable').css('display', 'none').end()
                        .find('.product_data_tabs .general_options').css('display', 'block')
                        .find('a').click();
                }
            });
        </script>
        <?php
        $product_types = $this->_application->WooCommerce_ProductTypes();
        if (!empty($product_types['subscription']['name'])) {
            $subscription_product_type_names = array_keys($product_types['subscription']['name']);
            $show_class = 'show_if_' . implode(' show_if_', $subscription_product_type_names);
            ?>
            <script type='text/javascript'>
                document.addEventListener("DOMContentLoaded", function (e) {
                    jQuery('#woocommerce-product-data')
                        .find('.options_group.subscription_pricing').addClass('<?php echo $show_class;?>').end()
                        .find('.options_group.limit_subscription').addClass('<?php echo $show_class;?>');
                    if ($.inArray($('select#product-type').val(), ['<?php echo implode("', '", $subscription_product_type_names);?>']) !== -1) {
                        $('#woocommerce-product-data')
                            .find('.options_group.subscription_pricing').css('display', 'block').end()
                            .find('.options_group.limit_subscription').css('display', 'block');
                    }
                });
                jQuery('body').bind('woocommerce-product-type-change.drts', function () {
                    var $ = jQuery;
                    if ($.inArray($('select#product-type').val(), ['<?php echo implode("', '", $subscription_product_type_names);?>']) !== -1) {
                        setTimeout(function () {
                            $('#woocommerce-product-data')
                                .find('.options_group.pricing ._regular_price_field').css('display', 'none').end()
                                .find('#sale-price-period').css('display', 'inline').end()
                                .find('.hide_if_subscription').css('display', 'none');
                        }, 100);
                    }
                });
            </script>
            <?php
        }
    }

    public function productTypeSelectorFilter($types)
    {
        $types += $this->_getProductTypes();

        return $types;
    }

    public function woocommerceProductDataTabsFilter($tabs)
    {
        $product_types = $this->_getProductTypes();

        // Hide tabs except for General and Advanced tabs
        $class = 'hide_if_' . implode(' hide_if_', array_keys($product_types));
        foreach (array_keys($tabs) as $tab) {
            if (!in_array($tab, array('general', 'advanced'))) {
                $tabs[$tab]['class'][] = $class;
            }
        }

        // Add product type specific tabs
        foreach (array_keys($product_types) as $type) {
            $tab_name_suffix = substr($type, strlen('drts_')); // remove drts_
            $tabs['drts_settings_' . $tab_name_suffix] = array(
                'label' => __('Plan Features', 'directories-payments'),
                'target' => 'drts_settings_' . $tab_name_suffix,
                'class' => array('show_if_' . $type, 'drts_plan_features'),
            );
        }

        return $tabs;
    }

    public function woocommerceProductDataPanelsAction()
    {
        $post_id = intval(empty($GLOBALS['thepostid']) ? $GLOBALS['post']->ID : $GLOBALS['thepostid']);
        $product_types = $this->_getProductTypes();
        foreach (array_keys($product_types) as $type) {
            $tab_name_suffix = substr($type, strlen('drts_')); // remove drts_
            $tab_name = 'drts_settings_' . $tab_name_suffix;
            if ($dash_pos = strpos($tab_name_suffix, '__')) {
                $plan_type = substr($tab_name_suffix, $dash_pos + 2);
                $bundle_name = substr($tab_name_suffix, 0, $dash_pos);
            } else {
                $plan_type = 'base';
                $bundle_name = $tab_name_suffix;
            }
            if ((!$bundle = $this->_application->Entity_Bundle($bundle_name))
                || empty($bundle->info['payment_enable'])
            ) continue;

            $form_settings = array('#tree' => true);
            $form_settings['_' . $type]['features'] = $this->_application->Payment_Features_form(
                $bundle,
                $plan_type,
                (array)get_post_meta($post_id, '_drts_entity_features', true) + (array)get_post_meta($post_id, '_drts_entity_features_disabled', true),
                array('_' . $type, 'features')
            );
            $form = $this->_getPlanFeaturesForm($bundle, $type, $plan_type, $post_id)->render();
            echo '<div id="' . $tab_name . '" class="panel woocommerce_options_panel drts">';
            echo '<div class="' . $form->getFormTagClass() . '" id="' . $form->settings['#id'] . '">';
            echo $form->getHtml();
            echo '</div></div>';
            echo $form->getHiddenHtml();
            echo $form->getJsHtml();
        }
        if (isset($form)) {
            $this->_application->getPlatform()
                ->loadDefaultAssets()
                ->addCssFile('woocommerce-admin-product.min.css', 'drts-woocommerce-admin-product', array('drts'), 'directories-payments');
        }
    }

    protected function _getPlanFeaturesForm(Entity\Model\Bundle $bundle, $productType, $planType, $postId)
    {
        return $this->_application->Form_Build(array(
            '#tree' => true,
            '#build_id' => false,
            '#token' => false,
            '_' . $productType => array(
                'features' => $this->_application->Payment_Features_form(
                    $bundle,
                    $planType,
                    (array)get_post_meta($postId, '_drts_entity_features', true) + (array)get_post_meta($postId, '_drts_entity_features_disabled', true),
                    array('_' . $productType, 'features')
                ),
            ),
        ));
    }

    protected function _submitPlanFeaturesForm(Entity\Model\Bundle $bundle, $productType, $planType, $postId, array $values)
    {
        $form = $this->_getPlanFeaturesForm($bundle, $productType, $planType, $postId);
        if (!$form->submit($values)) return;

        $features = $form->values['_' . $productType]['features'];
        update_post_meta($postId, '_drts_entity_features', $features['enabled']);
        update_post_meta($postId, '_drts_entity_features_disabled', $features['disabled']);
        return true;
    }

    public function woocommerceProcessProductMetaAction($postId)
    {
        $type = $_POST['product-type'];
        if (strpos($type, 'drts_') === 0
            && !empty($_POST['_' . $type])
        ) {
            if ($dash_pos = strpos($type, '__')) {
                $plan_type = substr($type, $dash_pos + 2);
                $bundle_name = substr($type, strlen('drts_'), -1 * (strlen($plan_type) + 2));
            } else {
                $plan_type = 'base';
                $bundle_name = substr($type, strlen('drts_'));
            }
            if ((!$bundle = $this->_application->Entity_Bundle($bundle_name))
                || !$this->_submitPlanFeaturesForm($bundle, $type, $plan_type, $postId, $_POST)
            ) return;

            update_post_meta($postId, '_virtual', 'yes');
            update_post_meta($postId, '_downloadable', 'yes');
            update_post_meta($postId, '_sold_individually', 'yes'); // prevent quantity change

            // Set catalog visibility hidden
            wp_set_object_terms($postId, array('exclude-from-search', 'exclude-from-catalog'), 'product_visibility', true);
        }
    }

    public function woocommerceGetItemDataFilter($itemData, $cartItem)
    {
        if (!$cartItem['data'] instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct) return $itemData;

        if (!$entity = $this->_application->Entity_Entity($cartItem['_drts_entity_type'], $cartItem['_drts_entity_id'])) {
            $this->_application->logError('Failed fetching entity from cart data.');
            return $itemData;
        }

        // Show item data in cart
        $itemData[] = array(
            'name' =>  $this->_application->Payment_Util_actionLabel($cartItem['_drts_action'], !empty($cartItem['_drts_was_deactivated'])),
            'value' => $this->_application->Entity_Title($entity),
        );

        return $itemData;
    }

    public function woocommerceCartCalculateFeesFilter()
    {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!$cart_item['data'] instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
                || empty($cart_item['_drts_entity_type'])
                || empty($cart_item['_drts_entity_id'])
                || (!$entity = $this->_application->Entity_Entity($cart_item['_drts_entity_type'], $cart_item['_drts_entity_id']))
                || (!$fees = $this->_application->Payment_CalculateFees($entity, $cart_item['data'], $cart_item['_drts_action']))
            ) continue;

            foreach (array_keys($fees) as $fee_name) {
                $fee = $fees[$fee_name];
                if (empty($fee['is_discount'])) {
                    WC()->cart->add_fee($fee['label'], $fee['amount'], true, '');
                } else {
                    if (0 < $discount = wc_cart_round_discount($fee['amount'], wc_get_price_decimals())) {
                        WC()->cart->add_fee($fee['label'], -1 * $discount);
                    }
                }
            }
        }
    }

    public function woocommerceCheckoutCreateOrderLineItemAction($item, $cartItemKey, $values, $order)
    {
        if (($product = $item->get_product())
            && $product instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
        ) {
            foreach (array_keys($values) as $key) {
                if (strpos($key, '_drts_') === 0 // private meta keys
                    || strpos($key, 'drts_') === 0 // public meta keys
                ) {
                    $item->add_meta_data($key, $values[$key]);
                }
            }
        }
    }

    public function woocommerceCheckoutOrderProcessedAction($orderId, $postedData, $order)
    {
        foreach ($order->get_items() as $item) {
            if ((!$product = $item->get_product())
                || !$product instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
            ) continue; // not a sabai product

            if ((!$entity_type = $item->get_meta('_drts_entity_type'))
                || (!$entity_id = $item->get_meta('_drts_entity_id'))
                || (!$entity = $this->_application->Entity_Entity($entity_type, $entity_id))
            ) {
                $this->_application->logError('Failed fetching entity. Entity type: ' . $entity_type . '; Entity ID: ' . $entity_id);
                continue;
            }

            if ($product->get_sabai_plan_type() !== 'addon') {
                if (!$entity->isPublished() // may already be published if renew/upgrade/downgrade
                    && !$entity->isPending()
                ) {
                    // Update post status to pending
                    try {
                        $entity = $this->_application->Entity_Save($entity, array(
                            'status' => $this->_application->Entity_Status($entity->getType(), 'pending'),
                        ));
                    } catch (Exception\IException $e) {
                        $this->_application->logError(
                            'Failed updating post status to pending. Entity ID: ' . $entity->getId() . '; Error: ' . $e->getMessage()
                        );
                    }
                }
            }

            try {
                // Create features
                $this->_application->Payment_Features_create($entity, new PaymentPlan($product), $item->get_id());
            } catch (Exception\IException $e) {
                $this->_application->logError('Failed creating features for entity. Entity type: ' . $entity_type . '; Entity ID: ' . $entity_id . '; Order item ID: ' . $item->get_id());
                continue;
            }
        }
    }

    public function woocommerceOrderAgainCartItemDataFilter($cartItemData, $item, $order)
    {
        foreach (['subscription_resubscribe' => 'resubscribe', 'subscription_renewal' => 'renew'] as $key => $action) {
            if (isset($cartItemData[$key])) {
                $cartItemData = $cartItemData[$key]['custom_line_item_meta'];
                $cartItemData['_drts_action'] = $action;
                $cartItemData['drts_action'] = $this->_application->Payment_Util_actionLabel($action);
            }
        }
        return $cartItemData;
    }

    public function woocommerceOrderStatusChangedAction($orderId, $oldStatus, $newStatus)
    {
        switch ($newStatus) {
            case 'completed':
                if (!in_array($oldStatus, ['cancelled', 'refunded', 'failed'])) {
                    $this->_onOrderStatusChanged($orderId);
                }
                break;
            case 'cancelled':
            case 'refunded':
            case 'failed':
                $this->_onOrderStatusChanged($orderId, true);
                break;
        }
    }

    protected function _onOrderStatusChanged($orderId, $unapply = false)
    {
        if (!$order = wc_get_order($orderId)) return;

        foreach ($order->get_items() as $item) {
            if ((!$product = $item->get_product())
                || !$product instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
                || !in_array($product->get_sabai_plan_type(), ['base', 'addon'])
            ) continue;

            if ((!$entity_id = $item->get_meta('_drts_entity_id'))
                || (!$entity = $this->_application->Entity_Entity('post', $entity_id))
            ) {
                $this->_application->logError('Failed fetching post for order item ' . $item->get_id());
                continue;
            }

            try {
                if ($unapply) {
                    $this->_application->WooCommerce_Features_unapply($entity, $product, $item);
                } else {
                    $this->_application->WooCommerce_Features_apply($entity, $product, $item);
                }
            } catch (Exception\IException $e) {
                $this->_application->logError($e);
            }
        }
    }

    public function wcGetTemplateFilter($located, $templateName, $args)
    {
        // Do not show the default order again button if the order includes products from SabaiApps applications
        if ($templateName === 'order/order-again.php'
            && isset($args['order'])
        ) {
            foreach ($args['order']->get_items() as $item) {
                if (($product = $item->get_product())
                    && $product instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
                ) {
                    return $this->_application->getPlatform()->getAssetsDir('directories-payments') . '/templates/woocommerce_order_again.html.php';
                }
            }
        }
        return $located;
    }

    public function paymentIsEnabled()
    {
        return $this->isWooCommerceActive();
    }

    public function paymentIsGuestCheckoutEnabled()
    {
        return get_option('woocommerce_enable_guest_checkout') === 'yes';
    }

    public function paymentGetCurrency($symbol = false)
    {
        return $symbol ? get_woocommerce_currency_symbol() : get_woocommerce_currency();
    }

    public function paymentGetPlanIds($bundleName)
    {
        $base = 'drts_' . $bundleName;
        $terms = [$base];
        foreach (array_keys($this->_getProductTypes()) as $product_type) {
            if (strpos($product_type, $base . '__') === 0) {
                $terms[] = $product_type;
            }
        }

        return get_posts([
            'fields' => 'ids',
            'post_type' => 'product',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'tax_query' => [
                [
                    'taxonomy' => 'product_type',
                    'field' => 'slug',
                    'terms' => $terms,
                    'operator' => 'IN'
                ]
            ],
        ]);
    }

    public function paymentGetPlan($id)
    {
        if (!$product = wc_get_product($id)) return;

        return new PaymentPlan($product);
    }

    public function paymentGetOrders($entityId, $limit = 0, $offset = 0)
    {
        global $wpdb;
        $order_items = $ret = [];
        $sql = 'SELECT items.order_item_id, items.order_item_name, items.order_id, itemmeta2.meta_value, itemmeta3.meta_value FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id AND items.order_item_type = \'line_item\''
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta2 ON itemmeta2.order_item_id = itemmeta.order_item_id AND itemmeta2.meta_key = \'_drts_action\''
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta3 ON itemmeta3.order_item_id = itemmeta.order_item_id AND itemmeta3.meta_key = \'_drts_was_deactivated\''
            . ' WHERE itemmeta.meta_key = \'_drts_entity_id\' AND itemmeta.meta_value = %d ORDER BY order_id DESC';
        if (!empty($limit)) {
            $sql .= ' LIMIT %d, %d';
            $query = $wpdb->prepare($sql, $entityId, $offset, $limit);
        } else {
            // WPDB::prepare needs to be passed parameters exactly tne same number of placeholders
            $query = $wpdb->prepare($sql, $entityId);
        }
        foreach ($wpdb->get_results($query, ARRAY_N) as $row) {
            $order_items[$row[2]][$row[0]] = array($row[1], ['action' => $row[3], 'was_deactivated' => !empty($row[4])]);
        }
        if (!empty($order_items)) {
            foreach ($order_items as $order_id => $_order_items) {
                $order = wc_get_order($order_id);
                foreach ($_order_items as $item_id => $item) {
                    $ret[] = new PaymentOrder($order, $item_id, $item[0], $item[1], $entityId);
                }
            }
        }

        return $ret;
    }

    public function paymentGetOrder($orderItemId = null)
    {
        if (($order_id = wc_get_order_id_by_order_item_id($orderItemId))
            && ($order = wc_get_order($order_id))
        ) {
            foreach ($order->get_items() as $item_id => $item) {
                if ($item_id == $orderItemId) {
                    return new PaymentOrder(
                        $order,
                        $item_id,
                        $item->get_name(),
                        ['action' => $item->get_meta('_drts_action'), 'was_deactivated' => $item->get_meta('_drts_was_deactivated') ? true : false],
                        $item->get_meta('_drts_entity_id')
                    );
                }
            }
        }
    }

    public function paymentGetUserOrders($userId, $limit = 0, $offset = 0)
    {
        global $wpdb;
        $order_items = $ret = [];
        $sql = 'SELECT items.order_item_id, items.order_item_name, items.order_id, itemmeta.meta_value AS action, itemmeta2.meta_value AS entity_id, itemmeta3.meta_value AS was_deactivated'
            . ' FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
                . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta2 ON itemmeta2.order_item_id = itemmeta.order_item_id'
                . ' AND itemmeta2.meta_key = \'_drts_entity_id\''
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta3 ON itemmeta3.order_item_id = itemmeta.order_item_id'
                . ' AND itemmeta3.meta_key = \'_drts_was_deactivated\''
            . ' WHERE itemmeta.meta_key = \'_drts_action\''
                . ' AND posts.post_parent = 0'
                . ' AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d'
            . ' ORDER BY order_id DESC';
        if (!empty($limit)) {
            $sql .= ' LIMIT %d, %d';
        }
        $query = $wpdb->prepare($sql, $userId, $offset, $limit);
        foreach ($wpdb->get_results($query, ARRAY_N) as $row) {
            $order_items[$row[2]][$row[0]] = array($row[1], ['action' => $row[3], 'was_deactivated' => !empty($row[5])], $row[4]);
        }
        if (!empty($order_items)) {
            foreach ($order_items as $order_id => $_order_items) {
                $order = wc_get_order($order_id);
                foreach ($_order_items as $item_id => $item) {
                    $ret[] = new PaymentOrder($order, $item_id, $item[0], $item[1], $item[2]);
                }
            }
        }

        return $ret;
    }

    public function paymentCountUserOrders($userId)
    {
        global $wpdb;
        $sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
                . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' WHERE itemmeta.meta_key = \'_drts_action\' AND posts.post_parent = 0 AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d';
        return $wpdb->get_var($wpdb->prepare($sql, $userId));
    }

    public function paymentHasPendingOrder(Entity\Type\IEntity $entity, array $actions)
    {
        if ($this->_application->getPlatform()->isTranslatable($entity->getType(), $entity->getBundleName())) {
            // Include orders for translated entities
            $entity_ids = $this->_application->Entity_Translations_ids($entity);
        } else {
            $entity_ids = [];
        }
        $entity_ids[] = $entity->getId();
        $db = $this->_application->getDB();
        $sql = sprintf('
            SELECT COUNT(*) FROM %1$sposts posts
              LEFT JOIN %1$swoocommerce_order_items item ON item.order_id = posts.ID
              LEFT JOIN %1$swoocommerce_order_itemmeta meta1 ON meta1.order_item_id = item.order_item_id
              LEFT JOIN %1$swoocommerce_order_itemmeta meta2 ON meta2.order_item_id = item.order_item_id
              WHERE posts.post_status NOT IN (\'wc-completed\', \'wc-refunded\', \'wc-cancelled\', \'wc-failed\', \'trash\')
                AND meta1.meta_key = \'_drts_entity_id\' AND meta1.meta_value IN (%2$s)
                AND meta2.meta_key = \'_drts_action\' AND meta2.meta_value IN (%3$s)',
            $GLOBALS['wpdb']->prefix,
            implode(',', $entity_ids),
            implode(',', array_map([$db, 'escapeString'], $actions))
        );

        return $this->_application->getDB()->query($sql)->fetchSingle() > 0;
    }

    public function paymentOnSubmit(Entity\Type\IEntity $entity, Payment\IPlan $plan, $action)
    {
        // Remove from cart if already added
        if ($cart_item_key = $this->_isEntityInCart($entity)) {
            WC()->cart->remove_cart_item($cart_item_key);
        }

        // Add to cart
        $meta = [
            '_drts_action' => $action,
            '_drts_entity_id' => $entity->getId(),
            '_drts_entity_type' => $entity->getType(),
            'drts_post_id' => $entity->getId(), // for displaying order details
        ];
        if ($action === 'submit') {
            $was_deactivated = $entity->getSingleFieldValue('payment_plan', 'deactivated_at') ? 1 : 0;
            $meta['_drts_was_deactivated'] = $was_deactivated;
            $meta['drts_action'] = $this->_application->Payment_Util_actionLabel($action, $was_deactivated);  // for displaying order details
        } else {
            $meta['drts_action'] = $this->_application->Payment_Util_actionLabel($action); // for displaying order details
        }
        WC()->cart->add_to_cart(
            $plan->paymentPlanId(),
            1, // quantity
            0, // variation ID
            [], // variation attributes
            $meta
        );
    }

    public function paymentCheckoutUrl()
    {
        $bypass_cart = $this->_application->getComponent('Payment')
            ->getConfig('payment', 'component_settings', $this->_name, 'bypass_cart');
        return !empty($bypass_cart) ? wc_get_checkout_url() : wc_get_cart_url();
    }

    public function paymentIsSubscriptionEnabled()
    {
        return $this->isWCSActive();
    }

    public function paymentGetSubscription($subscriptionId, $itemId)
    {
        if ($subscription = wcs_get_subscription($subscriptionId)) {
            foreach ($subscription->get_items() as $item_id => $item) {
                if ($item_id == $itemId) {
                    return new PaymentSubscription(
                        $subscription,
                        $item_id,
                        $item->get_name(),
                        $item->get_meta('_drts_entity_id')
                    );
                }
            }
        }
    }

    public function paymentGetUserSubscriptions($userId, $limit = 0, $offset = 0)
    {
        global $wpdb;
        $order_items = $ret = [];
        $sql = 'SELECT items.order_item_id, items.order_item_name, items.order_id, itemmeta2.meta_value AS entity_id'
            . ' FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
            . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta2 ON itemmeta2.order_item_id = itemmeta.order_item_id'
            . ' AND itemmeta2.meta_key = \'_drts_entity_id\''
            . ' WHERE itemmeta.meta_key = \'_drts_action\''
            . ' AND posts.post_parent != 0'
            . ' AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d'
            . ' ORDER BY order_id DESC';
        if (!empty($limit)) {
            $sql .= ' LIMIT %d, %d';
        }
        $query = $wpdb->prepare($sql, $userId, $offset, $limit);
        foreach ($wpdb->get_results($query, ARRAY_N) as $row) {
            $order_items[$row[2]][$row[0]] = array($row[1], $row[3]);
        }
        if (!empty($order_items)) {
            foreach ($order_items as $order_id => $_order_items) {
                $subscription = wcs_get_subscription($order_id);
                foreach ($_order_items as $item_id => $item) {
                    $ret[] = new PaymentSubscription($subscription, $item_id, $item[0], $item[1]);
                }
            }
        }

        return $ret;
    }

    public function paymentCountUserSubscriptions($userId)
    {
        global $wpdb;
        $sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
            . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' WHERE itemmeta.meta_key = \'_drts_action\' AND posts.post_parent != 0 AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d';
        return $wpdb->get_var($wpdb->prepare($sql, $userId));
    }

    public function paymentGetClaimOrderId($claimId)
    {
        global $wpdb;
        $sql = 'SELECT items.order_item_id FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id AND items.order_item_type = \'line_item\''
            . ' WHERE itemmeta.meta_key = \'_drts_entity_id\' AND itemmeta.meta_value = %d';
        return $wpdb->get_var($wpdb->prepare($sql, $claimId));
    }

    public function paymentRefundOrder($orderId, $reason = '')
    {
        $this->_application->WooCommerce_RefundOrder($orderId, $reason);
    }

    protected function _isEntityInCart(Entity\Type\IEntity $entity)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
            if ($values['data'] instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
                && isset($values['_drts_entity_id'])
                && $values['_drts_entity_id'] === $entity->getId()
            ) {
                return $cart_item_key;
            }
        }
        return false;
    }

    public function isWooCommerceActive()
    {
        return defined('WC_VERSION');
    }

    protected function _getProductTypes($flatten = true)
    {
        $product_types = $this->_application->WooCommerce_ProductTypes();
        if (!$flatten) return $product_types;

        $ret = [];
        foreach (array_keys($product_types) as $plan_type) {
            $ret += $product_types[$plan_type]['name'];
        }
        asort($ret);
        return $ret;
    }

    public function woocommerceOrderItemDisplayMetaKeyFilter($displayKey, $meta)
    {
        if ($meta->key === 'drts_action') {
            $displayKey = __('Purchase type', 'directories-payments');
        } elseif ($meta->key === 'drts_post_id') {
            $displayKey = __('Purchase item', 'directories-payments');
        }
        return $displayKey;
    }

    public function woocommerceOrderItemDisplayMetaValueFilter($displayValue, $meta)
    {
        if ($meta->key === 'drts_post_id') {
            if ($entity = $this->_application->Entity_Entity('post', $displayValue)) {
                $displayValue = sprintf('%s (ID: %d)', $this->_application->Entity_Title($entity), $displayValue);
            } else {
                $displayValue = 'ID: ' . $displayValue;
            }
        }
        return $displayValue;
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        if (!$this->_application->isComponentLoaded('Dashboard')) return;

        $form['Dashboard']['woocommerce'] = [
            '#title' => __('WooCommerce "My account" Page Integration', 'directories-payments'),
            '#states' => [
                'visible' => [
                    'select[name="Payment[payment][component]"]' => ['value' => $this->_name],
                ]
            ],
            'account_show' => [
                '#title' => __('Show dashboard panels', 'directories-payments'),
                '#type' => 'checkbox',
                '#default_value' => $this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_show'),
                '#horizontal' => true,
                '#description' => __('Check this option to show dashboard panels on the WooCommerce "My account" page.', 'directories-payments'),
            ],
            'account_redirect' => [
                '#title' => __('Redirect dashboard access', 'directories-payments'),
                '#type' => 'checkbox',
                '#default_value' => $this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_redirect'),
                '#horizontal' => true,
                '#description' => __('Check this option to redirect dashboard access to the WooCommerce "My account" page.', 'directories-payments'),
                '#states' => [
                    'visible' => [
                        'input[name="Dashboard[woocommerce][account_show]"]' => ['type' => 'checked', 'value' => true],
                    ]
                ],
            ],
        ];
    }

    public function onFormSubmitDirectoryAdminSettingsSuccess($form)
    {
        if (!empty($form->values['Payment']['payment']['component'])
            && $form->values['Payment']['payment']['component'] === $this->_name
            && !empty($form->values['Dashboard']['woocommerce']['account_show'])
        ) {
            $mask = WC()->query->get_endpoints_mask();
            foreach ($this->_getAccountEndpoints(true) as $endpoint) {
                add_rewrite_endpoint($endpoint, $mask);
            }
            $this->_application->getPlatform()->flushRewriteRules();
        }
    }

    protected function _initMyAccountPage()
    {
        if ($endpoints = $this->_getAccountEndpoints()) {
            add_filter('woocommerce_account_menu_items', [$this, 'woocommerceAccountMenuItemsFilter']);
            add_filter('woocommerce_get_query_vars', [$this, 'woocommerceGetQueryVars']);
            $application = $this->_application;
            foreach ($endpoints as $panel_name => $endpoint) {
                add_filter('woocommerce_endpoint_' . $endpoint .  '_title', function ($title) use ($application, $panel_name) {
                    if ($panel = $application->Dashboard_Panels_impl($panel_name, true)) {
                        $title = $panel->dashboardPanelLabel();
                    }
                    return $title;
                });
                add_action('woocommerce_account_' . $endpoint .  '_endpoint', function () use ($application, $panel_name, $endpoint) {
                    if (!$panel = $application->Dashboard_Panels_impl($panel_name, true)) return;

                    $panel->dashboardPanelOnLoad();
                    $container = 'drts-dashboard-main';
                    $path = '/' . $application->getComponent('Dashboard')->getSlug('dashboard') . '/'. $panel_name;
                    if ($endpoint_extra_path = get_query_var($endpoint)) {
                        $path .= '/' . $endpoint_extra_path;
                        // extract link name if posts panel
                        if (strpos($endpoint_extra_path, 'posts/') === 0) {
                            $link_name = substr($endpoint_extra_path, strlen('posts/'));
                        }
                    }
                    // Render links as tabs
                    if (($links = $panel->panelHtmlLinks(isset($link_name) ? $link_name : true))
                        && count($links) > 1
                    ) {
                        echo '<div class="drts"><nav class="drts-dashboard-links ' . DRTS_BS_PREFIX . 'nav ' . DRTS_BS_PREFIX . 'nav-tabs ' . DRTS_BS_PREFIX . 'nav-justified ' . DRTS_BS_PREFIX . 'mb-4">';
                        foreach ($links as $link) {
                            echo '<a href="#" class="' . DRTS_BS_PREFIX . 'nav-item ' . DRTS_BS_PREFIX . 'nav-link ' . $application->H($link['attr']['class']) . '"' . $application->Attr($link['attr'], 'class') . '>' . $link['title'] . '</a>';
                        }
                        echo '</nav></div>';
                    }
                    echo $application->Dashboard_Panels_js('#' . $container, false, false);
                    echo $application->getPlatform()->render(
                        $path,
                        ['is_dashboard' => false], // attributes
                        false, // cache
                        false, // title
                        $container
                    );
                    if (($theme = wp_get_theme())
                        && $theme['Name'] === 'Storefront'
                    ) {
                        $application->getPlatform()->addCssFile('woocommerce-dashboard.min.css', 'woocommerce-dashboard', [], 'directories-payments');
                    }
                });
            }
        }
    }

    protected function _getAccountEndpoints($skipEnabledCheck = false)
    {
        $ret = [];
        if ($this->_application->isComponentLoaded('Dashboard')
            && $this->_application->getComponent('Payment')->getConfig('payment', 'component') === $this->_name
            && ($dashboard_config = $this->_application->getComponent('Dashboard')->getConfig())
            && ($skipEnabledCheck || !empty($dashboard_config['woocommerce']['account_show']))
            && ($panels = $this->_application->Dashboard_Panels())
        ) {
            foreach (array_keys($panels) as $panel_name) {
                if ($panel_name === 'payment_payments'
                    || (isset($dashboard_config['panel']['panels']['default']) && !in_array($panel_name, $dashboard_config['panel']['panels']['default']))
                ) continue;

                $ret[$panel_name] = 'drts-' . $panel_name;
            }
        }
        return $ret;
    }

    public function woocommerceGetQueryVars($vars)
    {
        foreach ($this->_getAccountEndpoints() as $endpoint) {
            $vars[$endpoint] = $endpoint;
        }
        return $vars;
    }

    public function woocommerceAccountMenuItemsFilter($items)
    {
        foreach ($this->_getAccountEndpoints() as $panel_name => $endpoint) {
            if (!$panel = $this->_application->Dashboard_Panels_impl($panel_name, true)) continue;

            $items[$endpoint] = $panel->dashboardPanelLabel();
        }
        // Move logout menu item to the end
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);
        $items['customer-logout'] = $logout;

        return $items;
    }

    protected function _getMyAccountPageUrl()
    {
        if (($page_id = wc_get_page_id('myaccount'))
            && ($permalink = get_permalink($page_id)) // need to use get_permalink to fetch the current language page
        ) {
            return $permalink;
        }
    }

    public function onCoreAccessRouteFilter(&$result, $context, $route, $paths)
    {
        if (!$result
            || Request::isXhr()
            || Request::isPostMethod()
            || $context->isEmbed()
            || !$this->_application->isComponentLoaded('Dashboard')
            || $paths[0] !== $this->_application->getComponent('Dashboard')->getSlug('dashboard')
            || $this->_application->getComponent('Payment')->getConfig('payment', 'component') !== $this->_name
            || !$this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_show')
            || !$this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_redirect')
            || (!$url = $this->_getMyAccountPageUrl())
        ) return;

        $result = false;
        unset($paths[0]);
        if (isset($paths[1])) {
            $paths[1] = 'drts-' . $paths[1];
            $url = rtrim($url, '/');
            $url .= '/' . implode('/', $paths);
        }
        $params = $context->getRequest()->getParams();
        // Remove panel_name from params since it is already in the path
        unset($params['panel_name']);
        $context->setRedirect($url . '?' . implode('&', $params));
    }

    public function onEntityCreateBundlesCommitted(array $bundles, array $bundleInfo)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function onEntityUpdateBundlesCommitted(array $bundles, array $bundleInfo)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function onEntityDeleteBundlesCommitted(array $bundles, $deleteContent)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function onEntityAdminBundleInfoEdited($bundle)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function isWCSActive()
    {
        return class_exists('\WC_Subscriptions');
    }

    public function wcSubscriptionProductTypesFilter($productTypes)
    {
        $product_types = $this->_application->WooCommerce_ProductTypes('subscription');
        foreach (array_keys($product_types['name']) as $product_type) {
            $productTypes[] = $product_type;
        }
        return $productTypes;
    }

    public function wcIsSubscriptionFilter($isSubscription, $productId, $product)
    {
        if ($product instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
            && $product->get_sabai_plan_type() === 'subscription'
        ) {
            $isSubscription = true;
        }
        return $isSubscription;
    }

    public function wcSubscriptionStatusUpdatedAction($subscription, $newStatus, $oldStatus)
    {
        switch ($newStatus) {
            case 'active':
                if (!in_array($oldStatus, ['cancelled', 'expired'])) { // WCS does not allow this, but just in case
                    $this->_onSubscriptionStatusUpdated($subscription);
                }
                break;
            case 'cancelled':
            case 'expired':
                $this->_onSubscriptionStatusUpdated($subscription, true);
                break;
            case 'on-hold':
                if ($oldStatus === 'active') {
                    $this->_onSubscriptionStatusUpdated($subscription, true);
                }
                break;
        }
    }

    protected function _onSubscriptionStatusUpdated($subscription, $unapply = false)
    {
        if (!$order = $subscription->get_parent()) {
            $this->_application->logError('Failed fetching parent order for subscription ' . $subscription->get_id());
            return;
        }

        foreach ($order->get_items() as $item) {
            if ((!$product = $item->get_product())
                || !$product instanceof \SabaiApps\Directories\Component\WooCommerce\IProduct
                || $product->get_sabai_plan_type() !== 'subscription'
            ) continue;

            if ((!$entity_id = $item->get_meta('_drts_entity_id'))
                || (!$entity = $this->_application->Entity_Entity('post', $entity_id))
            ) {
                $this->_application->logError('Failed fetching post for order item ' . $item->get_id());
                continue;
            }

            try {
                if ($unapply) {
                    $this->_application->WooCommerce_Features_unapply($entity, $product, $item);
                } else {
                    $this->_application->WooCommerce_Features_apply($entity, $product, $item);
                }
            } catch (Exception\IException $e) {
                $this->_application->logError($e);
            }
        }
    }

    public function onWooCommerceProductTypesFilter(&$productTypes)
    {
        if (!$this->isWCSActive()) return;

        $productTypes['subscription'] = ['name' => [], 'class' => '\WC_Product_Subscription'];
        foreach (array_keys($productTypes['base']['name']) as $product_type) {
            $base_label = $productTypes['base']['name'][$product_type];
            $productTypes['subscription']['name'][$product_type . '__subscription'] = $base_label . ' ' . __('(Subscription plan)', 'directories-payments');
        }
    }

    public function onPaymentBasePlanTypesFilter(&$planTypes)
    {
        if (!$this->isWCSActive()) return;

        $planTypes[] = 'subscription';
    }

    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        if (version_compare($current->version, '1.2.0-dev.0', '<')) {
            global $wpdb;
            $product_types = $this->_application->WooCommerce_ProductTypes(null, true);
            foreach (array_keys($product_types['base']['name']) as $base_name) {
                $sql = sprintf(
                    'UPDATE %1$sterms SET name = \'%2$s\', slug = \'%2$s\' WHERE slug = \'%3$s\'',
                    $wpdb->prefix,
                    esc_sql($base_name . '__addon'),
                    esc_sql($base_name . '_addon')
                );
                $wpdb->query($sql);
            }
        }
    }
}
