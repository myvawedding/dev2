<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\System\Progress;

class ToolsHelper
{
    public function syncPaymentPlan(Application $application, Progress $progress)
    {
        foreach ($application->Entity_Bundles() as $bundle) {
            if (!$application->isComponentLoaded($bundle->component)
                || empty($bundle->info['payment_enable'])
            ) continue;

            $paginator = $application->Entity_Query($bundle->entitytype_name)
                ->fieldIs('bundle_name', $bundle->name)
                ->sortById()
                ->paginate(50);
            foreach ($paginator as $page) {
                $paginator->setCurrentPage($page);
                $offset = $paginator->getElementOffset();
                $progress->set(sprintf(
                    'Syncing payment plan for %s (%d - %d)',
                    $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                ));
                foreach ($paginator->getElements() as $entity) {
                    if (!$translated_entities = $application->Entity_Translations($entity)) continue;

                    if ((!$payment_plan = $entity->getSingleFieldValue('payment_plan'))
                        || empty($payment_plan['plan_id'])
                    ) {
                        $payment_plan = false;
                    }
                    foreach (array_keys($translated_entities) as $lang) {
                        $application->Entity_Save(
                            $translated_entities[$lang],
                            ['payment_plan' => $payment_plan],
                            ['payment_skip_plan_sync' => true] // prevents loop
                        );
                    }
                }
            }
        }
    }
}