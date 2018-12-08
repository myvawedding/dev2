<?php
namespace SabaiApps\Directories\Component\Voting\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Voting\Model\Vote;

class DashboardVotes extends AbstractVotes
{
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {
        $form = parent::_doGetFormSettings($context, $formStorage);
        $form['#action'] = $this->_application->getComponent('Dashboard')->getPanelUrl('voting_votes', $this->_getVotingType($context), '/votes', [], true);
        return $form;
    }
    
    protected function _getHeaders(Context $context)
    {
        $ret = [
            'title' => __('Title', 'directories'),
            'type' => __('Content Type', 'directories'),
        ] + (array)$this->Voting_Types_impl($this->_getVotingType($context))->votingTypeInfo('table_headers');
        
        unset($ret['author']); // make sure these columns aren't displayed on the dashboard
        
        return $ret;
    }
    
    protected function _getSortableHeaders(Context $context)
    {
        return $this->Voting_Types_impl($this->_getVotingType($context))->votingTypeInfo('table_sortable_headers');
    }
    
    protected function _getSortableHeaderParams(Context $context)
    {
        return ['link' => $context->dashboard_panel_link];
    }
    
    protected function _getDefaultHeader(Context $context)
    {
        return $this->Voting_Types_impl($this->_getVotingType($context))->votingTypeInfo('table_default_header');
    }
    
    protected function _getTimestampHeaders(Context $context)
    {
        return $this->Voting_Types_impl($this->_getVotingType($context))->votingTypeInfo('table_timestamp_headers');
    }
    
    protected function _getVoteRow(Context $context, Vote $vote, array $tableHeaders)
    {
        if (!$vote->Entity) return;
        
        return array(
            'title' => $this->Entity_Permalink($vote->Entity),
            'type' => $this->Entity_Bundle($vote->Entity->getBundleName())->getLabel('singular'),
        ) + $this->Voting_Types_impl($this->_getVotingType($context))->votingTypeTableRow($vote, $tableHeaders);
    }
    
    protected function _getQuery(Context $context)
    {
        return parent::_getQuery($context)->userId_is($this->getUser()->id);
    }

    protected function _getVotes(Context $context, $limit, $offset, $sort, $order)
    {
        return parent::_getVotes($context, $limit, $offset, $sort, $order)->with('Entity');
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return $this->_application->getComponent('Dashboard')->getPanelUrl('voting_votes', $this->_getVotingType($context));
    }
    
    protected function _getVotingType(Context $context)
    {
        return $context->dashboard_panel_link;
    }
}