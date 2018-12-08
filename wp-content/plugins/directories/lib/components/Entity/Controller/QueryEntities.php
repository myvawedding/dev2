<?php
namespace SabaiApps\Directories\Component\Entity\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class QueryEntities extends Controller
{
    protected function _doExecute(Context $context)
    {
        $entity_type = $this->Entity_BundleTypeInfo($context->bundle_type, 'entity_type');
        $bundle = $context->getRequest()->asStr('bundle');
        $num = $context->getRequest()->asInt('num', 5);
        if ($num > 100 || $num <= 0) $num = 5;
        $q = trim($context->getRequest()->asStr('query'));
        $user_id = $context->getRequest()->asInt('user_id');
        if ($q
            || $user_id
            || (false === $list = $this->getPlatform()->getCache($cache_id = 'drts-entity-list-' . $entity_type . '-' . $bundle . '-' . $num))
        ) {
            $list = [];
            $query = $this->Entity_Query($entity_type)
                ->fieldIs('status', $this->Entity_Status($entity_type, 'publish'))
                ->fieldIs('bundle_type', $context->bundle_type);
            if ($bundle) {
                $query->fieldIsIn('bundle_name', explode(',', $bundle));
            }
            if ($q) {
                $query->fieldContains('title', $q);
                $load_fields = false;
            }
            if ($user_id) {
                $query->fieldIs('author', $user_id);
                $load_fields = false;
            }
            if (!isset($load_fields)) {
                $load_fields = $this->Entity_BundleTypeInfo($context->bundle_type, 'entity_image') ? true : false;
            }
            try {
                foreach ($query->fetch($num, 0, $load_fields) as $entity) {
                    $list[] = array(
                        'id' => $entity->getId(),
                        'slug' => $entity->getSlug(),
                        'title' => $this->Entity_Title($entity),
                        'url' => (string)$this->Entity_PermalinkUrl($entity),
                        'icon_src' => $this->Entity_Image($entity, 'icon'),
                    );
                }
            } catch (\Exception $e) {
                $context->setError($e->getMessage());
                return;
            }
            if (isset($cache_id)) $this->getPlatform()->setCache($list, $cache_id, 864000); // cache 10 days
        }
        $context->addTemplate('system_list')->setAttributes(array('list' => $list));
    }
}
