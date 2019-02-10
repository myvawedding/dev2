<?php
namespace SabaiApps\Directories\Component\WordPressContent\EntityType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\User\AbstractIdentity;

class PostEntityType extends Entity\Type\AbstractType
{
    protected function _entityTypeInfo()
    {
        return [
            'label' => __('Post', 'directories'),
            'table_name' => $GLOBALS['wpdb']->posts,
            'table_joins' => array(
                $this->_application->getDB()->getResourcePrefix() . 'entity_bundle' => array(
                    'alias' => 'bundle',
                    'on' => 'bundle_name = %2$s.post_type'
                ),
            ),
            'properties' => array(
                'id' => array(
                    'type' => 'entity_id',
                    'column_type' => Application::COLUMN_INTEGER,
                    'column' => 'ID',
                ),
                'title' => array(
                    'type' => 'entity_title',
                    'column_type' => Application::COLUMN_VARCHAR, 
                    'column' => 'post_title',
                ),
                'slug' => array(
                    'type' => 'entity_slug',
                    'column_type' => Application::COLUMN_VARCHAR, 
                    'column' => 'post_slug',
                ),
                'bundle_name' => array(
                    'type' => 'entity_bundle_name',
                    'column_type' => Application::COLUMN_VARCHAR,
                    'column' => 'post_type',
                ),
                'bundle_type' => array(
                    'type' => 'entity_bundle_type',
                    'column_type' => Application::COLUMN_VARCHAR,
                    'column' => 'bundle.bundle_type',
                ),
                'parent' => array(
                    'type' => 'wp_post_parent',
                    'column_type' => Application::COLUMN_INTEGER,
                    'column' => 'post_parent',
                    'required' => true,
                ),
                'content' => array(
                    'type' => 'wp_post_content',
                    'column_type' => Application::COLUMN_TEXT, 
                    'column' => 'post_content',
                ),
                'published' => array(
                    'type' => 'entity_published',
                    'column_type' => Application::COLUMN_VARCHAR,
                    'column' => 'post_date_gmt',
                ),
                'modified' => array(
                    'type' => 'entity_modified',
                    'column_type' => Application::COLUMN_VARCHAR,
                    'column' => 'post_modified_gmt',
                ),
                'author' => array(
                    'type' => 'entity_author',
                    'column_type' => Application::COLUMN_INTEGER,
                    'column' => 'post_author',
                ),
                'status' => array(
                    'type' => 'wp_post_status',
                    'column_type' => Application::COLUMN_VARCHAR,
                    'column' => 'post_status',
                ),
            ),
        ];
    }
    
    public function entityTypeEntityById($entityId)
    {
        if (!$post = get_post($entityId)) {
            return false;
        }
        return $this->_toEntity($post);
    }
    
    public function entityTypeEntityBySlug($bundleName, $slug)
    {
        if (!$post = get_page_by_path($slug, OBJECT, $bundleName)) {
            return false;
        }

        // Get in current language if WPML enabled
        if (defined('ICL_SITEPRESS_VERSION')
            && is_post_type_translated($post->post_type)
        ) {
            if ((!$post_id = apply_filters('wpml_object_id', $post->ID, $post->post_type))
                || (!$post = get_post($post_id))
            ) return false;
        }

        return $this->_toEntity($post);
    }
    
    public function entityTypeEntityByTitle($bundleName, $title)
    {
        if (!$post = get_page_by_title($title, $bundleName)) return false;

        // Get in current language if WPML enabled
        if (defined('ICL_SITEPRESS_VERSION')
            && is_post_type_translated($post->post_type)
        ) {
            if ((!$post_id = apply_filters('wpml_object_id', $post->ID, $post->post_type))
                || (!$post = get_post($post_id))
            ) return false;
        }
        
        return $this->_toEntity($post);
    }

    public function entityTypeEntitiesByIds(array $entityIds)
    {
        return $this->_getEntities(array(
            'post__in' => $entityIds,
        ));
    }
    
    public function entityTypeEntitiesBySlugs($bundleName, array $slugs)
    {
        return $this->_getEntities(array(
            'post_name__in' => $slugs,
            'post_type' => $bundleName,
        ));
    }
    
    protected function _getEntities(array $args)
    {
        $args += array(
            'post_type' => 'any',
            'post_status' => array('publish', 'pending', 'draft', 'future', 'private', 'trash', 'inherit'),
            'numberposts' => -1
        );
        $entities = [];
        foreach (get_posts($args) as $post) {
            $entities[$post->ID] = $this->_toEntity($post);
        }
        return $entities;
    }

    public function entityTypeCreateEntity(Entity\Model\Bundle $bundle, array $properties, AbstractIdentity $identity)
    {
        $post = array(
            'post_type' => $bundle->name,
            'post_title' => isset($properties['title']) ? $properties['title'] : '',
            'post_status' => isset($properties['status']) ? $properties['status'] : 'publish',
            'post_author' => isset($properties['author']) ? $properties['author'] : $identity->id,
            'post_date' => date('Y-m-d H:i:s', !empty($properties['published']) ? $properties['published'] : current_time('timestamp')),
            'post_parent' => !empty($properties['parent']) ? $properties['parent'] : 0,
        );
        if (isset($properties['content'])) {
            $post['post_content'] = $properties['content'];
        }
        $post_id = wp_insert_post($post, true);
        if (is_wp_error($post_id)) {
            throw new Exception\RuntimeException($post_id->get_error_message());
        }
        return $this->_toEntity(get_post($post_id));
    }

    
    public function entityTypeUpdateEntity(Entity\Type\IEntity $entity, Entity\Model\Bundle $bundle, array $properties)
    {
        if (!$post = get_post($entity->getId(), 'ARRAY_A')) {
            throw new Exception\RuntimeException(sprintf('Cannot save non existent entity (Bundle: %s, ID: %d).', $bundle->name, $entity->getId()));
        }
        
        // Is trashing?
        if (isset($properties['status'])
            && $properties['status'] === 'trash'
        ) {
            if ($post['post_status'] !== 'trash') {
                if (false === wp_trash_post($post['ID'])) {
                    throw new Exception\RuntimeException('Failed saving post to the database.');
                }
            }
            return $this->_toEntity(get_post($post['ID']));
        }
        
        foreach ($properties as $property => $value) {
            switch ($property) {
                case 'title':
                    $post['post_title'] = $value;
                    break;
                case 'status':
                    $post['post_status'] = $value;
                    break;
                case 'author':
                    $post['post_author'] = $value;
                    break;
                case 'published':
                    $post['post_date']= date('Y-m-d H:i:s', (int)$value <= 0 ? time() : $value);
                    break;
                case 'content':
                    $post['post_content'] = $value;
                    break;
                case 'parent':
                    $post['post_parent'] = $value;
                    break;
            }
        }        
        $post_id = wp_update_post($post, true);
        if (is_wp_error($post_id)) {
            throw new Exception\RuntimeException($post_id->get_error_message());
        }
        return $this->_toEntity(get_post($post_id));
    }
    
    public function entityTypeTrashEntities(array $entities, array $formValues = null)
    {
        foreach ($entities as $entity) {
            wp_trash_post($entity->getId());
        }
    }

    public function entityTypeDeleteEntities(array $entities)
    {
        foreach ($entities as $entity) {
            wp_delete_post($entity->getId(), true);
        }
    }
    
    public function entityTypeRandomEntityIds($bundleName, $num)
    {
        $args = array( 
            'orderby' => 'rand',
            'posts_per_page' => $num, 
            'post_type' => $bundleName,
            'offset' => 0,
            'post_status' => 'publish',
        );
        $query = new \WP_Query($args);
        $ret = [];
        while ($query->have_posts()) {
            $query->the_post();
            $ret[] = get_the_ID();
        }
        return $ret;
    }
    
    public function entityTypeEntityStatusLabel($status)
    {
        if ($obj = get_post_status_object($status)) {
            return $obj->label;
        }
    }
    
    public function entityTypeBundleExists($bundleName)
    {
        return post_type_exists($bundleName);
    }
    
    protected function _getFieldQuery($operator)
    {
        return new PostFieldQuery($operator);
    }

    protected function _toEntity($post)
    {
        return new PostEntity($post);
    }
}