<?php
namespace SabaiApps\Directories\Component\WordPressContent\EntityType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Framework\User\AbstractIdentity;
use SabaiApps\Directories\Platform\WordPress\Platform;

class PostEntity extends Entity\Type\AbstractEntity
{
    protected $_author;

    public function __construct($post)
    {
        parent::__construct(
            $post->post_type,
            null,
            array(
                'post_author' => (int)$post->post_author,
                'post_published' => strtotime('0000-00-00 00:00:00' === $post->post_date_gmt ? get_gmt_from_date($post->post_date) : $post->post_date_gmt),
                'post_modified' => strtotime('0000-00-00 00:00:00' === $post->post_modified_gmt ? get_gmt_from_date($post->post_modified) : $post->post_modified_gmt),
                'post_id' => $post->ID,
                'post_title' => $post->post_title,
                'post_status' => $post->post_status,
                'post_slug' => $post->post_name,
                'post_content' => $post->post_content,
                'post_parent' => $post->post_parent,
            )
        );
    }

    public function getType()
    {
        return 'post';
    }

    public function getBundleType()
    {
        if (!isset($this->_bundleType)) {
            $this->_bundleType = Platform::getInstance()->getApplication()->Entity_Bundle($this->_bundleName)->type;
        }
        return $this->_bundleType;
    }

    public function getAuthorId()
    {
        return $this->_properties['post_author'];
    }

    public function getAuthor()
    {
        return $this->_author;
    }

    public function setAuthor(AbstractIdentity $author)
    {
        $this->_properties['post_author'] = (int)$author->id;
        $this->_author = $author;
    }

    public function getTimestamp()
    {
        return $this->_properties['post_published'];
    }

    public function getModified()
    {
        return $this->_properties['post_modified'];
    }

    public function getId()
    {
        return $this->_properties['post_id'];
    }

    public function getTitle()
    {
        return $this->_properties['post_title'];
    }

    public function getStatus()
    {
        return $this->_properties['post_status'];
    }

    public function setStatus($status)
    {
        $this->_properties['post_status'] = $status;
    }

    public function getSlug()
    {
        return $this->_properties['post_slug'];
    }

    public function getParent()
    {
        return null;
    }

    public function getParentId()
    {
        return $this->_properties['post_parent'];
    }

    public function setParent(Entity\Type\IEntity $parent)
    {
        $this->_properties['post_parent'] = $parent->getId();
    }

    public function getContent()
    {
        return $this->_properties['post_content'];
    }

    public function isPublished()
    {
        return $this->_properties['post_status'] == 'publish';
    }

    public function isDraft()
    {
        return $this->_properties['post_status'] == 'draft';
    }

    public function isPending()
    {
        return $this->_properties['post_status'] == 'pending';
    }

    public function isPrivate()
    {
        return $this->_properties['post_status'] == 'private';
    }

    public function isScheduled()
    {
        return $this->_properties['post_status'] == 'future';
    }

    public function isTaxonomyTerm()
    {
        return false;
    }

    public function post()
    {
        return get_post($this->_properties['post_id']);
    }
}
