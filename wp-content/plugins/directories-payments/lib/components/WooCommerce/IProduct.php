<?php
namespace SabaiApps\Directories\Component\WooCommerce;

interface IProduct
{
    public function get_sabai_entity_bundle_name();
    public function get_sabai_plan_type();
    public function get_sabai_entity_features();
}