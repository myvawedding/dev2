<?php
namespace SabaiApps\Directories\Component\reCAPTCHA\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception;

class CaptchaHelper
{
    protected static $_jsLoaded, $_count = 0, $_preRenderAdded = [];

    public function help(Application $application, array $options = [])
    {
        $config = $application->getComponent('reCAPTCHA')->getConfig();
        if (empty($config['sitekey'])
            || empty($config['secret'])
        ) {
            return array(
                '#type' => 'item',
                '#default_value' => __('reCAPTCHA site/secret keys must be obtained from Google and configured under Security settings.', 'directories-frontend'),
            );
        }

        $options += array(
            'size' => $config['size'],
            'type' => $config['type'],
            'theme' => $config['theme'],
            'weight' => 0,
            'trigger' => null,
            'name' => ++self::$_count,
        );

        if (!self::$_jsLoaded) {
            $js = sprintf(
                'var sabaiReCaptchaCallback = function() {
    jQuery(".drts-recaptcha-form-field").each(function(i) {
        var $this = jQuery(this), options = {
            sitekey: "%s",
            size: $this.data("size"),
            type: $this.data("type"),
            theme: $this.data("theme")
        };
        $this.data("recaptcha-widget-id", grecaptcha.render($this.attr("id"), options))
            .find("textarea").attr("name", $this.attr("id"));
    });
};',
                $application->H($config['sitekey'])
            );
            $locale = $application->getPlatform()->getLocale();
            if (strpos($locale, '_')) {
                $locale = explode('_', $locale);
                $locale = in_array($locale[0], array('zh', 'pt')) ? $locale[0] . '-' . $locale[1] : $locale[0];
            }
            $application->getPlatform()->addHead(
                '<script type="text/javascript">' . $js . '</script>
<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?onload=sabaiReCaptchaCallback&render=explicit&hl=' . $locale . '" async defer></script>',
                'recaptcha'
            );
            self::$_jsLoaded = true;
        }

        $id = 'drts-recaptcha-form-field-' . $options['name'];
        return array(
            '#type' => 'item',
            '#element_validate' => array(array(array($this, '_validateCaptcha'), array($application, $config['secret'], $options['trigger'], $id))),
            '#markup' => '<div class="drts-recaptcha-form-field" id="' . $id . '" data-size="' . $application->H($options['size']) . '" data-type="' . $application->H($options['type']) . '" data-theme="' . $application->H($options['theme']) . '" data-trigger="' . $application->H($options['trigger']) . '"></div>',
            '#weight' => $options['weight'],
        );
    }

    public function _validateCaptcha(Form\Form $form, &$value, $element, Application $application, $secret, $trigger, $id)
    {
        if (isset($trigger) && !$form->getValue($trigger)) return;

        if (!isset($_POST[$id])
            || !strlen($_POST[$id])
        ) {
            $form->setError(__('Please fill out this field.', 'directories-frontend'), $element);
        } else {
            if (true !== $result = $application->reCAPTCHA_Captcha_verify($secret, $_POST[$id])) {
                $error = $result['error'];
                if (!empty($result['error_codes'])) {
                    $error .= ' (' . implode(',', $result['error_codes']) . ')';
                }
                $form->setError($error, $element);
            }
        }
        if (empty(self::$_preRenderAdded[$form->settings['#id']])) {
            $form->settings['#pre_render'][] = array($this, '_preRenderCallback');
            self::$_preRenderAdded[$form->settings['#id']] = true;
        }
    }

    public function _preRenderCallback($form)
    {
        if ($form->hasError()) {
            $form->settings['#js_ready'][] = 'sabaiReCaptchaCallback();';
        }
    }

    public function verify(Application $application, $secret, $value)
    {
        if (empty($secret)) {
            throw new Exception\InvalidArgumentException(__('Invalid reCAPTCHA secret key.', 'directories-frontend'));
        }

        if (empty($value)) {
            throw new Exception\InvalidArgumentException(__('Invalid reCAPTCHA value.', 'directories-frontend'));
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify?' . http_build_query(array(
            'secret' => $secret,
            'response' => $value,
            'remoteip' => $this->_getIp(),
        ));
        if ((!$json = $this->_getResponse($url))
            || (!$response = json_decode($json, true))
        ) {
            throw new Exception\RuntimeException(__('Failed obtaining valid reCAPTCHA verify response.', 'directories-frontend'));
        }

        if (empty($response['success'])) {
            $error = __('Unknown error.', 'directories-frontend');
            if (!empty($response['error-codes'])) {
                switch ($response['error-codes'][0]) {
                    case 'missing-input-secret':
                    case 'invalid-input-secret':
                        $error = __('Invalid or missing secret parameter.', 'directories-frontend');
                        break;
                    case 'missing-input-response':
                    case 'invalid-input-response':
                        $error = __('Invalid or missing response parameter', 'directories-frontend');
                        break;
                    case 'bad-request':
                        $error = __('Invalid or malformed request.', 'directories-frontend');
                        break;
                }
            }
            return array(
                'error' => $error,
                'error_codes' => empty($response['error-codes']) ? [] : $response['error-codes'],
            );
        }

        return true;
    }

    protected function _getResponse($url)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('User-Agent: PHP/' . PHP_VERSION),
            ));

            return curl_exec($curl);
        }

        return file_get_contents($url);
    }

    protected function _getIp()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR') as $key) {
            if (!empty($_SERVER[$key])) return $_SERVER[$key];
        }
        return '';
    }
}
