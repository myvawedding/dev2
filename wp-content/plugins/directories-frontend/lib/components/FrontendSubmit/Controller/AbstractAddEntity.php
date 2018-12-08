<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

abstract class AbstractAddEntity extends AbstractSubmitEntity
{
    protected function _getSteps(Context $context, array &$formStorage)
    {
        // Save reference field name and ID to storage for later use if requested
        if (($entity_reference_field = $context->getRequest()->asStr('entity_reference_field'))
            && ($entity_reference_id = $context->getRequest()->asStr('entity_reference_id'))
        ) {
            $formStorage['entity_reference_field'] = $entity_reference_field;
            $formStorage['entity_reference_id'] = $entity_reference_id;
        }

        return array(
            'add' => array('order' => 10),
        );
    }

    protected function _getPageTitle(Context $context, array $formStorage){}

    public function _getFormForStepAdd(Context $context, array &$formStorage)
    {
        // Assign custom page title if any
        $context->setTitle(($title = $this->_getPageTitle($context, $formStorage)) ? $title : null);

        if (!$entity_or_bundle = $this->_getEntity($context, $formStorage)) {
            $entity_or_bundle = $this->_getBundle($context, $formStorage);
        }
        $form = $this->_getSubmitEntityForm(
            $context,
            $formStorage,
            $entity_or_bundle,
            $this->_getSubmitButtonForStepAdd($context, $formStorage)
        );

        // Remove reference type field that will be populated automatically during save
        if (!empty($formStorage['entity_reference_field'])
            && isset($form['drts'][$formStorage['entity_reference_field']])
        ) {
            unset($form['drts'][$formStorage['entity_reference_field']]);
        }

        return $form;
    }

    public function _submitFormForStepAdd(Context $context, Form\Form $form)
    {
        $values = $this->_getEntityValues($context, $form);
        $bundle = $this->_getBundle($context, $form->storage);

        // Set referencing entity if any
        if (!empty($form->storage['entity_reference_field'])
            && $this->Entity_Field($bundle, $form->storage['entity_reference_field']) // Make sure field belongs to current bundle
        ) {
            $values[$form->storage['entity_reference_field']] = $form->storage['entity_reference_id'];
        }

        // Set max num items if any
        $extra_args = [];
        if (isset($form->settings['#max_num_items'])) {
            $extra_args['entity_field_max_num_items'] = $form->settings['#max_num_items'];
        }

        // Save
        if (!$entity = $this->_getEntity($context, $form->storage)) {
            $bundle = $this->_getBundle($context, $form->storage);
            // Create entity and save entity id into session for later use
            $entity = $this->Entity_Save($bundle->name, array('status' => $this->_getEntityStatus($context, $form, $bundle)) + $values, $extra_args);
            $form->storage['entity_id'] = $entity->getId();
        } else {
            $entity = $this->Entity_Save($entity, $values, $extra_args);
        }
        $form->settings['#entity'] = $entity;
    }

    protected function _getSubmitButtonForStepAdd(Context $context, array &$formStorage){}

    protected function _getEntityStatus(Context $context, Form\Form $form, Entity\Model\Bundle $bundle)
    {
        if (!empty($bundle->info['public'])
            && !$this->HasPermission('entity_publish_' . $bundle->name)
        ) {
            $status = 'pending';
        } else {
            $status = 'publish';
        }
        return $this->Entity_Status($bundle->entitytype_name, $status);
    }

    protected function _getEntityValues(Context $context, Form\Form $form)
    {
        $values = $form->values['drts'];
        if ($this->getUser()->isAnonymous()
            && $this->_isCollectGuestInfo()
        ) {
            if (!empty($form->storage['_guest'])) {
                $guest_info = $form->storage['_guest'];
                $values['frontendsubmit_guest'] = [
                    'name' => isset($guest_info['name']) ? $guest_info['name'] : '',
                    'email' => isset($guest_info['email']) ? $guest_info['email'] : '',
                    'url' => isset($guest_info['url']) ? $guest_info['url'] : '',
                ];
            }
        }

        return $values;
    }

    protected function _complete(Context $context, array $formStorage)
    {
        $entity = $this->_getEntity($context, $formStorage);

        // Set cookie to track guest user
        if ($this->getUser()->isAnonymous()) {
            $this->FrontendSubmit_GuestAuthorCookie($entity);
        }

        $results = array(
            'success' => [],
            'error' => [],
        );
        if ($entity->isPublished()) {
            $bundle = $this->_getBundle($context, $formStorage);
            $msg = __('Your item has been submitted successfully.', 'directories-frontend');
            if (!empty($bundle->info['public'])) {
                $context->setSuccess($this->_getSuccessUrl($context, $formStorage, $entity))->addFlash($msg);
                return;
            } else {
                $results['success']['default'] = $this->H($msg);
            }
        } else {
            $results['notice']['default'] = $this->H(__('Your item has been submitted successfully. We will review your submission and post it on this site when it is approved.', 'directories-frontend'));
        }

        // Display success message
        $context->addTemplate('form_results')->setAttributes(array(
            'results' => $this->Filter('frontendsubmit_add_entity_results', $results, array($entity)),
        ));
    }

    protected function _getSuccessUrl($context, $formStorage, Entity\Type\IEntity $entity)
    {
        if (!empty($formStorage['entity_reference_id'])
            && ($referenced_entity = $this->Entity_Entity($entity->getType(), $formStorage['entity_reference_id']))
        ) {
            return $this->Entity_PermalinkUrl($referenced_entity);
        }
        return $this->Entity_PermalinkUrl($entity);
    }

    protected function _getEntity(Context $context, array $formStorage)
    {
        return (!empty($formStorage['entity_id'])
            && ($entity = $this->Entity_Entity($this->_getBundle($context, $formStorage)->entitytype_name, $formStorage['entity_id']))
        ) ? $entity : null;
    }

    protected function _isCollectGuestInfo()
    {
        $config = $this->getComponent('FrontendSubmit')->getConfig('guest');
        return (!isset($config['collect_name']) || !empty($config['collect_name']))
            || !empty($config['collect_email'])
            || !empty($config['collect_url']);
    }

    protected function _isGuestInfoRequired(Context $context, array &$formStorage)
    {
        if (!$this->_isCollectGuestInfo()
            || isset($formStorage['_guest'])
        ) return false;

        if ((!$guest_info = $context->getRequest()->get('_guest'))
            || !is_array($guest_info)
        ) return true;

        $config = $this->getComponent('FrontendSubmit')->getConfig('guest');

        // Guest name
        if (!empty($config['collect_name']) || !isset($config['collect_name'])) {
            if (!isset($guest_info['name'])
                || !strlen($guest_info['name'] = trim($guest_info['name']))
            ) {
                if (!empty($config['require_name']) || !isset($config['require_name'])) return true;
            }
        }

        // Guest e-mail address
        if (!empty($config['collect_email'])) {
            if (!isset($guest_info['email'])
                || !strlen($guest_info['email'] = trim($guest_info['email']))
            ) {
                if (!empty($config['require_email'])) return true;
            } else {
                // Validate
                try {
                    $guest_info['email'] = $this->Form_Validate_email($guest_info['email'], !empty($config['check_mx']), !empty($config['check_exists']));
                } catch (Exception\IException $e) {
                    return true;
                }
            }
        }
        // Guest website URL
        if (!empty($config['collect_url'])) {
            if (!isset($guest_info['url'])
                || !strlen($guest_info['url'] = trim($guest_info['url']))
            ) {
                if (!empty($config['require_url'])) return true;
            } else {
                // Validate
                try {
                    $guest_info['url'] = $this->Form_Validate_url($guest_info['url']);
                } catch (Exception\IException $e) {
                    return true;
                }
            }
        }

        $formStorage['_guest'] = $guest_info;
        return false;
    }

    protected function _getRedirectGuestUrlParams(Context $context, array $formStorage)
    {
        return [];
    }

    protected function _redirectGuest(Context $context, array $formStorage, $bundle = null, Entity\Type\IEntity $entity = null)
    {
        if (!$this->getUser()->isAnonymous()) return;

        $params = [];
        if (isset($bundle)) {
            $params['redirect_action'] = 'add';
            if ($bundle instanceof \SabaiApps\Directories\Component\Entity\Model\Bundle) {
                $params['redirect_bundle'] = $bundle->name;
                if (isset($entity)) {
                    $params['redirect_entity'] = $entity->getId();
                }
            } else {
                $params['redirect_bundle_type'] = $bundle;
            }
        }
        // Add extra params if any
        if ($param_names = $this->_getRedirectGuestUrlParams($context, $formStorage)) {
            foreach ($param_names as $param) {
                if (isset($_GET[$param])) {
                    $params[$param] = $_GET[$param];
                }
            }
        }
        $context->setUnauthorizedError($this->Url((string)$context->getRoute(), $params, '', '&'));
    }
}
