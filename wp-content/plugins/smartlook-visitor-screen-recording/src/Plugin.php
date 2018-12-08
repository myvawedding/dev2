<?php
/**
 * @author Tomáš Blatný
 */

namespace Smartlook;

use Smartlook\Webapi\Client;


class Plugin
{

	const OPTION_NAME = 'smartlook';
	const OLD_OPTION_NAME = 'smartlook-visitor-screen-recording';
	const DOMAIN = 'smartlook';
	const AUTH_KEY = '47a2435f1f3673ffce7385bc57bbe3e7353ab02e';

	private $pluginUrl;


	public function install()
	{
		global $wp_version;

		$checks = array(
			'Your Wordpress version is not compatible with Smartlook plugin which requires at least version 3.1. Please update your Wordpress or insert Smartlook chat code into your website manually (you will find the chat code in the email we have sent you upon registration)' => version_compare($wp_version, '3.1', '<'),
			'This plugin requires at least PHP version 5.3.0, your version: ' . PHP_VERSION . '. Please ask your hosting company to bring your PHP version up to date.' => version_compare(PHP_VERSION, '5.3.0', '<'),
			'This plugin requires PHP extension \'curl\' installed and enbaled.' => !function_exists('curl_init'),
			'This plugin requires PHP extension \'json\' installed and enbaled.' => !function_exists('json_decode'),
		);

		foreach ($checks as $message => $disable) {
			if ($disable) {
				deactivate_plugins(basename(__FILE__));
				wp_die($message);
			}
		}
	}


	public function uninstall()
	{
		//
	}


	public function init()
	{
		$that = $this;
		add_action('admin_notices', function () use ($that) {
			$oldOptions = get_option($that::OLD_OPTION_NAME);
			if (isset($oldOptions['code']) && $oldOptions['code'] && !$that->getOption('chatKey', NULL)) {
				echo '<div class="notice notice-warning is-dismissible"><p>' .
					__('Your SmartLook plugin has been updated to new version. Please login to your SmartLook account through plugin <a href="options-general.php?page=smartlook">settings</a> page.', $that::DOMAIN) .
					'</p></div>';
			}
		});

		add_action('admin_menu', function () use ($that) {
			add_options_page(
				__('Smartlook', $that::DOMAIN),
				__('Smartlook', $that::DOMAIN),
				'manage_options',
				$that::DOMAIN,
				array($that, 'actionSettings')
			);

			add_menu_page(
				__('Smartlook', $that::DOMAIN),
				__('Smartlook', $that::DOMAIN),
				'manage_options',
				$that::DOMAIN,
				array($that, 'actionSettings'),
				plugins_url( 'img/icon-20x20.png', __DIR__)
			);
		});

        $plugin_basename = plugin_basename(plugin_dir_path(realpath(dirname(__FILE__))) . $that::DOMAIN . '.php');

		add_filter( 'plugin_action_links_' . $plugin_basename, function ($links) use ($that) {
            array_unshift($links, '<a href="options-general.php?page=' . $that::DOMAIN . '">' . __('Settings', $that::DOMAIN) . '</a>');
			return $links;
		});


		add_action('wp_footer', array($this, 'renderChat'));
	}


	public function actionSettings()
	{
		$message = NULL;
		$formAction = NULL;
        $termsConsent = $email = NULL;

		if (isset($_GET['slaction'])) {
			switch ($_GET['slaction']) {
				case 'disable':
					$this->updateOptions(array(
						'email' => NULL,
						'chatId' => NULL,
						'chatKey' => NULL,
						'projectId' => NULL,
					));
					break;
				case 'login':
				case 'register':
					$api = new Client;
					$result = $_GET['slaction'] === 'register' ?
						$api->signUp(array('authKey' => self::AUTH_KEY, 'email' => $_POST['email'], 'password' => $_POST['password'], 'lang' => $this->convertLocale(get_locale()), 'consentTerms' => 1)) :
						$api->signIn(array('authKey' => self::AUTH_KEY, 'email' => $_POST['email'], 'password' => $_POST['password'],));

					if ($result['ok']) {
						$projectId = NULL;
						$chatKey = NULL;
						if ($_GET['slaction'] === 'register') {
							$api->authenticate($result['account']['apiKey']);
							$project = $api->projectsCreate(array(
								'name' => get_bloginfo('name'),
							));
							$projectId = $project['project']['id'];
							$chatKey = $project['project']['key'];
						} else {
							$api->authenticate($result['account']['apiKey']);
						}
						$this->updateOptions(array(
							'email' => $result['user']['email'],
							'chatId' => $result['account']['apiKey'],
							'chatKey' => $chatKey,
							'customCode' => '',
							'projectId' => $projectId,
						));
						delete_option(self::OLD_OPTION_NAME);

                        $termsConsent = $_POST['termsConsent'];
					} else {
						$message = $result['error'];
						$formAction = $_GET['slaction'] === 'register' ? NULL : 'login';
						$email = $_POST['email'];
                        $termsConsent = $_POST['termsConsent'];
					}

					break;
				case 'update':
					$api = new Client;
					$options = $this->getOptions();
					$api->authenticate($options['chatId']);
					$project = $_POST['project'];
					if (substr($project, 0, 1) === '_') {
						$project = $api->projectsCreate(array(
							'name' => substr($project, 1),
						));
					} else {
						$project = $api->projectsGet(array(
							'id' => $project,
						));
					}
					$this->updateOptions(array(
						'projectId' => $project['project']['id'],
						'chatKey' => $project['project']['key'],
					));
					break;
			}
		}

		$this->renderSettingsPage($message, $formAction, $email ?: $this->getOption('email'), $termsConsent);
	}


	public function renderChat()
	{
		if ($token = $this->getOption('chatKey', NULL)) {
			echo '<script type="text/javascript">
				window.smartlook||(function(d) {
				var o=smartlook=function(){ o.api.push(arguments)},h=d.getElementsByTagName(\'head\')[0];
				var c=d.createElement(\'script\');o.api=new Array();c.async=true;c.type=\'text/javascript\';
				c.charset=\'utf-8\';c.src=\'//rec.smartlook.com/recorder.js\';h.appendChild(c);
				})(document);
				smartlook(\'init\', \'' . $token . '\');';

			if (is_user_logged_in()) {
				$user = wp_get_current_user();
				echo 'smartlook(\'tag\', \'email\', ' . json_encode($user->user_email) . ');';
				echo 'smartlook(\'tag\', \'name\', ' . json_encode($user->first_name . ' ' . $user->last_name) . ');';
				echo 'smartlook(\'tag\', \'roles\', ' . json_encode(implode(', ', $user->roles)) . ');';
				echo 'smartlook(\'tag\', \'login\', ' . json_encode($user->user_login) . ');';
			}

			echo '</script>';
		}
	}


	public function register($file)
	{
		$that = $this;
		register_activation_hook($file, function () use ($that) {
			$that->install();
		});
		register_deactivation_hook($file, function () use ($that) {
			$that->uninstall();
		});

		$this->pluginUrl = plugins_url('', $file);
		if (is_admin()) {
			add_action('plugins_loaded', function () use ($that) {
				$that->init();
			});
		} else {
			add_action('wp_footer', function () use ($that) {
				$that->renderChat();
			});
		}
	}


	private function renderSettingsPage($message = NULL, $formAction = NULL, $email = NULL, $termsConsent = NULL)
	{
		$project = NULL;
		$projects = NULL;
		if ($chatId = $this->getOption('chatId')) {
			$api = new Client;
			$api->authenticate($chatId);
			$projects = $api->projectsList();
			$projects = $projects['projects'];
			if (count($projects) === 1) {
				$this->updateOptions(array('projectId' => $projects[0]['id'], 'chatKey' => $projects[0]['key']));
			}
			if ($projectId = $this->getOption('projectId')) {
				$project = $projectId;
			}
		}

		if ($message) {
			$mapping = array(
				'invalid_param' => $formAction ? __('Email not found.', self::DOMAIN) : __('Email already registered.', self::DOMAIN),
				'not_found' => $formAction ? __('Email not found.', self::DOMAIN) : __('Email already registered.', self::DOMAIN),
				'sign:invalid_password' => __('Invalid password.', self::DOMAIN),
				'sign:login_failure' => __('Login failed, please try again.', self::DOMAIN),
			);
			if (isset($mapping[$message])) {
				$message = $mapping[$message];
			} else {
				$message = ''; // better fail silently than display unknown message from API
			}
		}

		$this->render('templates/settings.php', array(
			'base' => $this->pluginUrl,
			'domain' => self::DOMAIN,
			'options' => $this->getOptions(),
			'message' => (string) $message,
			'formAction' => $formAction,
			'email' => $email,
			'enabled' => (bool) $this->getOption('email'),
			'projects' => $projects,
			'project' => $project,
			'displayForm' => !$project,
            'termsConsent' => $termsConsent,
		));
	}


	private function render($template, $vars = array())
	{
		call_user_func_array(function () use ($template, $vars) {
			extract($vars);
			include $template;
		}, array());
	}


	private function updateOptions(array $options)
	{
		$current = $this->getOptions();
		foreach ($options as $key => $option) {
			$current[$key] = $option;
		}
		update_option(self::OPTION_NAME, $current);
	}


	/**
	 * @return array
	 */
	private function getOptions()
	{
		return get_option(self::OPTION_NAME);
	}


	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	private function getOption($name, $default = NULL)
	{
		$options = $this->getOptions();
		return isset($options[$name]) ? $options[$name] : $default;
	}


	private function convertLocale($locale)
	{
		$available = array('en', 'cs', 'da', 'nl', 'fr', 'de', 'hu', 'it', 'ja', 'pl', 'br', 'pt', 'es', 'tr', 'eu', 'cn', 'tw', 'ro', 'ru', 'sk');
		$part = strtolower(substr($locale, 0, 2));
		$locale = strtolower(substr($locale, 0, 5));
		if (!in_array($part, $available)) {
			return 'en';
		} else {
			if ($part === 'pt') {
				if ($locale === 'pt_br') {
					$part = 'br';
				}
			} elseif ($part === 'zh') {
				if ($locale === 'zh_cn') {
					$part = 'cn';
				} elseif ($locale === 'zh_tw') {
					$part = 'tw';
				} else {
					$part = NULL;
				}
			} elseif ($part === 'eu') {
				$part = NULL;
			}
			return $part ?: 'en';
		}
	}

}
