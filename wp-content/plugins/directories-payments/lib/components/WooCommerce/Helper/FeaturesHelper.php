<?php
namespace SabaiApps\Directories\Component\WooCommerce\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\WooCommerce\IProduct;

class FeaturesHelper
{
    /*
     * @throws \SabaiApps\Directories\Exception\IException
     */
    public function apply(Application $application, Entity\Type\IEntity $entity, IProduct $product, \WC_Order_Item $item)
    {
        if ($product->get_sabai_plan_type() !== 'addon') {
            $prev_status = [];
            $bundle = $application->Entity_Bundle($entity, null, '', true);
            $new_status = 'publish';
            if (!empty($bundle->info['public'])
                && !$application->HasPermission('entity_publish_' . $bundle->name, $application->Entity_Author($entity))
            ) {
                $new_status = 'pending';
            }
            $new_status = $application->Entity_Status($entity->getType(), $new_status);
            if (!$entity->isPublished()) {
                if ($new_status !== $entity->getStatus()) {
                    $prev_status[$entity->getId()] = $entity->getStatus();
                    $application->Entity_Save($entity, array('status' => $new_status));
                }
            }
            // Update translated posts
            foreach ($application->Entity_Translations($entity, false) as $_entity) {
                if (!$_entity->isPublished()) {
                    if ($new_status !== $_entity->getStatus()) {
                        $prev_status[$entity->getId()] = $_entity->getStatus();
                        $application->Entity_Save($_entity, array('status' => $new_status));
                    }
                }
            }
            if (!empty($prev_status)) {
                // Save previous post status so that it can be restored when order is cancelled
                wc_update_order_item_meta(
                    $item->get_id(),
                    '_drts_entity_prev_status',
                    serialize($prev_status)
                );
            }
        }
        $application->Payment_Features_apply($entity, null, $item->get_id());
    }
    
    /*
     * @throws \SabaiApps\Directories\Exception\IException
     */
    public function unapply(Application $application, Entity\Type\IEntity $entity, IProduct $product, \WC_Order_Item $item)
    {
        $application->Payment_Features_unapply($entity, null, $item->get_id());
        if ($product->get_sabai_plan_type() !== 'addon') {
            // Restore post status?
            if (($prev_status = $item->get_meta('_drts_entity_prev_status'))
                && ($prev_status = @unserialize($prev_status))
            ) {
                if (isset($prev_status[$entity->getId()])
                    && $prev_status[$entity->getId()] !== $entity->getStatus()
                ) {
                    $application->Entity_Save($entity, array('status' => $prev_status[$entity->getId()]));
                }
                // Update translated posts
                foreach ($application->Entity_Translations($entity, false) as $_entity) {
                    if (isset($prev_status[$_entity->getId()])
                        && $prev_status[$_entity->getId()] !== $_entity->getStatus()
                    ) {
                        $application->Entity_Save($_entity, array('status' => $prev_status[$_entity->getId()]));
                    }
                }
            }
        }
    }
}
