<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Controller;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class LoginOrRegister extends Form\Controller
{
    protected $_bundle, $_entity, $_action, $_params = [];

    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_ajaxSubmit = true;

        $login_conf = $this->getComponent('FrontendSubmit')->getConfig('login');

        if ($redirect_to = $context->getRequest()->asStr('redirect_to', false)) {
            $redirect_url = $this->Url($redirect_to);
            $url_params = $redirect_url->params;
            if (!empty($url_params['redirect_bundle'])) {
                if (!empty($url_params['redirect_action']) // action param required to check if routable
                    && ($this->_bundle = $this->Entity_Bundle($url_params['redirect_bundle'])) // make sure requested bundle is valid
                ) {
                    $this->_action = $url_params['redirect_action'];
                    if (!empty($url_params['redirect_entity'])) {
                        if (!$this->_entity = $this->Entity_Entity($this->_bundle->entitytype_name, $url_params['redirect_entity'])) {
                            $this->_bundle = $this->_entity = $this->_action = null;
                        }
                    }
                }
            } elseif (!empty($url_params['redirect_bundle_type'])
                && isset($url_params['redirect_action'])
                && $url_params['redirect_action'] === 'add'
                && $this->FrontendSubmit_SubmittableBundles($url_params['redirect_bundle_type'])
            ) {
                // Guest user has at least one bundle that is submittable
                $this->_bundle = $url_params['redirect_bundle_type'];
                $this->_action = $url_params['redirect_action'];
            }
            unset($url_params['redirect_bundle'], $url_params['redirect_entity'], $url_params['redirect_bundle_type'], $url_params['redirect_action']);
            $this->_params = $url_params;
        }

        if (empty($login_conf['login_form'])) {
            if (!$this->getPlatform()->isUserRegisterable()
                || empty($login_conf['register_form'])
            ) {
                if (empty($redirect_to)
                    || !isset($this->_bundle)
                    || !isset($this->_action)
                ) {
                    // No form to show, so redirect to platform login
                    $context->setRedirect($this->getPlatform()->getLoginUrl($this->_getRedirectUrl($redirect_to)));
                    return;
                }
            }
        }

        $form = array(
            '#class' => 'drts-frontendsubmit-login-register-form',
            '#build_id' => false,
        );
        if (!empty($login_conf['login_form'])) {
            $form['login'] = array(
                '#tree' => true,
                '#weight' => 1,
                'login' => array(
                    '#weight' => 99,
                    '#class' => DRTS_BS_PREFIX . 'form-inline',
                    '#group' => true,
                    'submit' => array(
                        '#type' => 'submit',
                        '#btn_label' => __('Login', 'directories-frontend'),
                        '#btn_color' => 'primary',
                        '#submit' => array(
                            9 => [[[$this, '_loginUser'], [$context]]], // 9 is weight
                        ),
                        '#value' => 'login',
                        '#weight' => 1,
                    ),
                    'lost_password' => array(
                        '#type' => 'markup',
                        '#value' => '&nbsp;<a href="' . $this->getPlatform()->getLostPasswordUrl($this->_getRedirectUrl($redirect_to)) . '">'
                            . $this->H(__('Lost your password?', 'directories-frontend')) . '</a>',
                        '#weight' => 3,
                    ),
                ),
            ) + $this->getPlatform()->getLoginForm();
        } else {
            $form['login'] = array(
                '#type' => 'markup',
                '#value' => '&nbsp;<a class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-primary" href="' . $this->getPlatform()->getLoginUrl($this->_getRedirectUrl($redirect_to)) . '">'
                    . $this->H(__('Login to continue', 'directories-frontend')) . '</a>',
                '#weight' => 1,
            );
        }

        if ($this->getPlatform()->isUserRegisterable()) {
            if (!empty($login_conf['register_form'])) {
                $form['register'] = array(
                    '#tree' => true,
                    '#weight' => 2,
                    'register' => array(
                        '#weight' => 99,
                        '#class' => DRTS_BS_PREFIX . 'form-inline',
                        '#group' => true,
                        'submit' => array(
                            '#type' => 'submit',
                            '#btn_label' => __('Register', 'directories-frontend'),
                            '#btn_color' => 'primary',
                            '#submit' => array(
                                9 => [[[$this, '_registerUser'], [$context]]], // 9 is weight
                            ),
                            '#value' => 'register',
                            '#weight' => 1,
                        ),
                    ),
                ) + $this->getPlatform()->getRegisterForm();
                if (!empty($login_conf['register_privacy'])
                    && ($privacy_policy_link = $this->getPlatform()->getPrivacyPolicyLink())
                ) {
                    $form['register']['privacy_policy'] = array(
                        '#weight' => 98,
                        '#type' => 'checkbox',
                        '#switch' => false,
                        '#title_no_escape' => true,
                        '#title' => $this->_getPrivacyPolicyCheckboxLabel($privacy_policy_link, 'register'),
                    );
                }
            } else {
                $form['register'] = array(
                    '#type' => 'markup',
                    '#value' => '&nbsp;<a class="' . DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-primary" href="' . $this->getPlatform()->getRegisterUrl($this->_getRedirectUrl($redirect_to)) . '">'
                        . $this->H(__('Register an account', 'directories-frontend')) . '</a>',
                    '#weight' => 1,
                );
            }
        }

        if (!empty($redirect_to)) {
            $form['redirect_to'] = array(
                '#type' => 'hidden',
                '#value' => $redirect_to,
            );
            if (isset($this->_bundle)
                && isset($this->_action)
                && $this->Filter(
                    'frontendsubmit_guest_allowed',
                    true,
                    [is_string($this->_bundle) ? $this->_bundle : $this->_bundle->type, $this->_action]
                )
            ) {
                $config = $this->getComponent('FrontendSubmit')->getConfig('guest');
                $form['guest'] = array(
                    '#tree' => true,
                    '#weight' => 3,
                    'name' => array(
                        '#type' => 'textfield',
                        '#placeholder' => __('Your Name', 'directories-frontend'),
                        '#field_prefix' => '<i class="fas fa-fw fa-user"></i>',
                        '#weight' => 1,
                        '#default_value' => null,
                    ),
                    'continue' => array(
                        '#type' => 'submit',
                        '#btn_label' => __('Continue as guest', 'directories-frontend') . '',
                        '#btn_color' => 'primary',
                        '#value' => 'continue',
                        '#weight' => 99,
                        '#submit' => array(
                            9 => [[[$this, '_continue'], [$context]]], // 9 is weight
                        ),
                    ),
                );
                if (!empty($config['collect_email'])) {
                    $form['guest']['email'] = array(
                        '#type' => 'email',
                        '#placeholder' => __('E-mail Address', 'directories-frontend'),
                        '#field_prefix' => '<i class="fas fa-fw fa-envelope"></i>',
                        '#weight' => 3,
                        '#default_value' => null,
                        '#check_mx' => !empty($config['check_mx']),
                        '#check_exists' => !empty($config['check_exists']),
                    );
                }
                if (!empty($config['collect_url'])) {
                    $form['guest']['url'] = array(
                        '#type' => 'url',
                        '#placeholder' => __('Website URL', 'directories-frontend'),
                        '#field_prefix' => '<i class="fas fa-fw fa-globe"></i>',
                        '#weight' => 5,
                        '#default_value' => null,
                    );
                }
                if (!empty($config['collect_privacy'])
                    && ($privacy_policy_link = $this->getPlatform()->getPrivacyPolicyLink())
                ) {
                    $form['guest']['privacy_policy'] = array(
                        '#weight' => 98,
                        '#type' => 'checkbox',
                        '#switch' => false,
                        '#title_no_escape' => true,
                        '#title' => $this->_getPrivacyPolicyCheckboxLabel($privacy_policy_link, 'guest'),
                    );
                }
            }
        }

        $this->getPlatform()->addCssFile('frontendsubmit-login-register-form.min.css', 'drts-frontendsubmit-login-register-form', array('drts'), 'directories-frontend');
        $context->addTemplate($this->getPlatform()->getAssetsDir('directories-frontend') . '/templates/frontendsubmit_login_register_form');

        return $form;
    }

    protected function _getPrivacyPolicyCheckboxLabel($link, $type)
    {
        if (!$label = $this->Filter('frontendsubmit_privacy_policy_check_label', '', [$link, $type])) {
            $label = sprintf($this->H(__('I have read and agree to the %s', 'directories-frontend')), $link);
        }
        return $label;
    }

    public function _registerUser(Form\Form $form, Context $context)
    {
        $config = $this->getComponent('FrontendSubmit')->getConfig('login');
        if (!empty($config['register_privacy'])
            && isset($form->settings['register']['privacy_policy'])
            && empty($form->values['register']['privacy_policy'])
        ) {
            $form->setError(__('You must agree to continue.', 'directories-frontend'), 'register[privacy_policy]');
            return;
        }

        try {
            $user_id = $this->getPlatform()->registerUser($form->values['register']);
        } catch (Exception\RuntimeException $e) {
            $form->setError(strip_tags($e->getMessage()), 'register');
            return;
        }

        // Redirect to login page if setting current user failed for some reason
        if (!$this->getPlatform()->setCurrentUser($user_id)) {
            $redirect_url = $this->LoginUrl();
        } else {
            $redirect_url = $this->_getRedirectUrl(empty($form->values['redirect_to']) ? null : $form->values['redirect_to'], [], 'register');
        }

        $context->setSuccess($redirect_url);
    }

    public function _loginUser(Form\Form $form, Context $context)
    {
        try {
            $this->getPlatform()->loginUser($form->values['login']);
        } catch (Exception\RuntimeException $e) {
            $form->setError(strip_tags($e->getMessage()), 'login');
            return;
        }

        $context->setSuccess($this->_getRedirectUrl(empty($form->values['redirect_to']) ? null : $form->values['redirect_to']));
    }

    public function _continue(Form\Form $form, Context $context)
    {
        $config = $this->getComponent('FrontendSubmit')->getConfig('guest');

        if (!empty($config['collect_privacy'])
            && isset($form->settings['guest']['privacy_policy'])
            && empty($form->values['guest']['privacy_policy'])
        ) {
            $form->setError(__('You must agree to continue.', 'directories-frontend'), 'guest[privacy_policy]');
            return;
        }

        $info = [];
        if (!empty($config['collect_name']) || !isset($config['collect_name'])) {
            $info['name'] = trim((string)@$form->values['guest']['name']);
            if ((!empty($config['require_name']) || !isset($config['require_name']))
                && !strlen($info['name'])
            ) {
                $form->setError(__('Your name is required.', 'directories-frontend'), 'guest');
                return;
            }
        }
        if (!empty($config['collect_email'])) {
            $info['email'] = trim((string)@$form->values['guest']['email']);
            if (!empty($config['require_email'])
                && !strlen($info['email'])
            ) {
                $form->setError(__('E-mail address is required.', 'directories-frontend'), 'guest');
                return;
            }
        }
        if (!empty($config['collect_url'])) {
            $info['url'] = trim((string)@$form->values['guest']['url']);
            if (!empty($config['require_url'])
                && !strlen($info['url'])
            ) {
                $form->setError(__('Website URL is required.', 'directories-frontend'), 'guest');
                return;
            }
        }

        $context->setSuccess($this->_getRedirectUrl(empty($form->values['redirect_to']) ? null : $form->values['redirect_to'], ['_guest' => $info], 'guest'));
    }

    protected function _getRedirectUrl($url, array $params = [], $type = 'login')
    {
        if (!isset($this->_bundle)
            || !isset($this->_action)
        ) {
            if (empty($url)) {
                return $this->_getDefaultRedirectUrl($type);
            }
            return $url;
        }

        if (!isset($this->_entity)) {
            switch ($this->_action) {
                case 'add':
                    if (is_string($this->_bundle)) {
                        if ((!$component_name = $this->Entity_BundleTypes($this->_bundle))
                            || !$this->isComponentLoaded($component_name)
                        ) break;

                        $bundle_type = $this->_bundle;
                    } else {
                        $params['bundle'] = $this->_bundle->name;
                        $bundle_type = $this->_bundle->type;
                    }
                    return $this->Url('/' . $this->FrontendSubmit_AddEntitySlug($bundle_type), $params + $this->_params, '', '&');
            }
            return $this->_getDefaultRedirectUrl($type);
        }

        if (empty($this->_bundle->info['parent'])) {
            return $this->Entity_Url(
                $this->_entity,
                '/' . $this->_action,
                $params + $this->_params,
                '',
                '&'
            );
        }

        // Redirect to parent page with action path
        return $this->Entity_Url(
            $this->_entity,
            '/' . $this->_bundle->info['slug'] . (empty($this->_bundle->info['public']) ? '_' : '/') . $this->_action,
            $params + $this->_params,
            '',
            '&'
        );
    }

    protected function _getDefaultRedirectUrl($type)
    {
        if ($this->isComponentLoaded('Dashboard')
            && ($dashboard_slug = $this->getComponent('Dashboard')->getSlug('dashboard'))
        ) {
            $url = (string)$this->Url('/' . $dashboard_slug);
        } else {
            $url = $this->getPlatform()->getSiteUrl();
        }

        return $this->Filter('frontendsubmit_after_login_register_url', $url, [$type]);
    }
}
