<?php
namespace SabaiApps\Directories\Component\Voting\DisplayButton;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

class DisplayButton extends Display\Button\AbstractButton
{
    protected $_type, $_downVote = false;
    
    public function __construct(Application $application, $name)
    {
        parent::__construct($application, $name);
        if (substr($this->_name, -5) === '_down') {
            $this->_downVote = true;
            $this->_type = substr($this->_name, 7, -5); // remove voting_ prefix and _down suffix
        } else {
            $this->_type = substr($this->_name, 7); // remove voting_ prefix
        }
    }
    
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        $info = $this->_application->Voting_Types_impl($this->_type)->votingTypeInfo();
        $color_key = $this->_downVote ? 'color_down' : 'color';
        return array(
            'label' => $this->_downVote ? $info['label_button_down'] : $info['label_button'],
            'default_settings' => array(
                '_color' => isset($info[$color_key]) ? $info[$color_key] : 'outline-secondary',
                'show_count' => false,
            ),
            'labellable' => false,
            'iconable' => false,
        );
    }
    
    public function displayButtonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            'show_count' => array(
                '#type' => 'checkbox',
                '#title' => __('Show count', 'directories'),
                '#default_value' => !empty($settings['show_count']),
                '#horizontal' => true,
            ),
        );
    }
    
    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        if (!$this->_application->Voting_CanVote($entity, $this->_type, $this->_downVote)) {
            if (!$this->_application->getUser()->isAnonymous()) return;
            
            $type_info = $this->_application->Voting_Types_impl($this->_type)->votingTypeInfo();
            return $this->_getLoginButton(
                $this->_getLabelHtml($entity, $settings, $type_info),
                $this->_getVoteUrl($entity, false),
                ['no_escape' => true, 'icon' => $type_info['icon']],
                ['class' => $settings['_class'], 'style' => $settings['_style']]
            );
        }

        $type_info = $this->_application->Voting_Types_impl($this->_type)->votingTypeInfo();
        $active = false;
        $class = $settings['_class'];
        $value = $this->_downVote ? -1 : 1;
        if (!empty($entity->data['voting_' . $this->_type . '_voted'])
            && intval($entity->data['voting_' . $this->_type . '_voted']) === $value
        ) {
            $active = true;
            $class .= ' ' . DRTS_BS_PREFIX . 'active';
        }
        if ($this->_downVote) {
            $label_action = $type_info['label_action_down'];
            $label_unaction = $type_info['label_unaction_down'];
            $icon = $type_info['icon_down'];
            $active_icon = isset($type_info['icon_down_active']) ? $type_info['icon_down_active'] : $icon;
        } else {
            $label_action = $type_info['label_action'];
            $label_unaction = $type_info['label_unaction'];
            $icon = $type_info['icon'];
            $active_icon = isset($type_info['icon_active']) ? $type_info['icon_active'] : $icon;
        }
        return $this->_application->LinkTo(
            $this->_getLabelHtml($entity, $settings, $type_info, $active),
            '',
            array(
                'container' => '',
                'icon' => $active ? $active_icon : $icon,
                'url' => $this->_getVoteUrl($entity),
                'post' => true,
                'loadingImage' => false,
                'sendData' => 'DRTS.Voting.onSendData("' . $this->_type . '", trigger, data);',
                'success' => 'DRTS.Voting.onSuccess("' . $this->_type . '", trigger, result);',
                'error' => 'DRTS.Voting.onError("' . $this->_type . '", trigger, error);',
                'no_escape' => true,
                'btn' => true,
            ),
            array(
                'class' => $class,
                'style' => $settings['_style'],
                'rel' => 'nofollow',
                'data-success-label' => $active ? $label_action : $label_unaction,
                'data-active-value' => $this->_downVote ? -1 : 1,
                'data-voting-type' => $this->_type,
                'data-voting-icon-active' => $active_icon,
                'data-voting-icon' => $icon,
            )
        );
    }
    
    protected function _getVoteUrl(Entity\Type\IEntity $entity, $withToken = true)
    {
        $params = ['value' => $this->_downVote ? -1 : 1];
        if ($withToken) {
            $params[Request::PARAM_TOKEN] = $this->_application->Form_Token_create('voting_vote_entity', 1800, true);
        }
        return $this->_application->Entity_Url($entity, '/vote/' . $this->_type,  $params);
    }
    
    protected function _getLabelHtml(Entity\Type\IEntity $entity, array $settings, array $info, $active = false)
    {
        if ($active) {
            $label = $this->_downVote ? $info['label_unaction_down'] : $info['label_unaction'];
        } else {
            $label = $this->_downVote ? $info['label_action_down'] : $info['label_action'];
        }
        $html = '<span class="drts-voting-vote-label">' . $this->_application->H($label) . '</span>';
        if (!empty($settings['show_count'])) {
            if ($val = $entity->getSingleFieldValue('voting_' . $this->_type, '')) {
                $num = $this->_application->Voting_Types_impl($this->_type)->votingTypeFormat($val, $this->_downVote ? 'num_down' : 'num');
            } else {
                $num = 0;
            }
            $html .= '<span class="drts-voting-vote-num ' . DRTS_BS_PREFIX . 'ml-2">' . $num . '</span>';
        }
        return $html;
    }
    
    public function displayButtonIsPreRenderable(Entity\Model\Bundle $bundle, array $settings)
    {
        return true;
    }
    
    public function displayButtonPreRender(Entity\Model\Bundle $bundle, array $settings, array $entities)
    {
        $this->_application->getPlatform()
            ->addJsFile('voting-updown.min.js', 'drts-voting-updown', array('drts'), 'directories');
        $this->_application->Voting_LoadEntities($bundle, $entities);
    }
}
