<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Application;

class AlgoliaAutocompleteApi implements IAutocompleteApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
        $this->_info = [
            'label' => __('Algolia Places Autocomplete', 'directories-pro'),
            'default_settings' => [
                'autocomplete' => [
                    'app_id' => '',
                    'api_key' => '',
                    'country' => null,
                ],
            ],
        ];
    }

    public function locationApiInfo($key = null)
    {
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function locationApiLoad(array $settings)
    {
        $this->_application->getPlatform()
            ->addJsFile(
                '//cdn.jsdelivr.net/algoliasearch/3/algoliasearch.min.js',
                'algolia-search',
                null,
                false
            )
            ->addJsFile(
                '//cdn.jsdelivr.net/npm/places.js@1.10.0',
                'algolia-places',
                null,
                false
            )
            ->addJsFile(
                'location-algolia-autocomplete.min.js',
                'drts-location-algolia-autocomplete',
                ['algolia-places', 'drts-location-api'],
                'directories-pro'
            )
            ->addJsInline(
                'drts-location-algolia-autocomplete',
                sprintf(
                    'var DRTS_Location_algoliaAutocomplete = %s;',
                    $this->_application->JsonEncode(isset($settings['autocomplete']) ? $settings['autocomplete'] : [])
                )
            );
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            'autocomplete' => [
                '#title' => __('Algolia Places Autocomplete', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible' => [
                        '[name="Map[lib][location_autocomplete]"]' => ['type' => 'value' , 'value' => 'location_algolia'],
                    ],
                ],
                'app_id' => [
                    '#type' => 'textfield',
                    '#title' => __('Application ID', 'directories-pro'),
                    '#default_value' => isset($settings['autocomplete']['app_id']) ? $settings['autocomplete']['app_id'] : null,
                    '#horizontal' => true,
                    '#description' => sprintf(
                        $this->_application->H(__('Visit the following page to sign up for Algolia Places: %s')),
                        '<a href="https://www.algolia.com/users/sign_up/places" target="_blank" rel="nofollow noopener">https://www.algolia.com/users/sign_up/places</a>'
                    ),
                    '#description_no_escape' => true,
                    '#required' => function($form) {
                        return $form->getValue(['Map', 'lib', 'location_autocomplete']) === $this->_name;
                    },
                ],
                'api_key' => [
                    '#type' => 'textfield',
                    '#title' => __('API Key', 'directories-pro'),
                    '#default_value' => isset($settings['autocomplete']['api_key']) ? $settings['autocomplete']['api_key'] : null,
                    '#horizontal' => true,
                    '#description' => sprintf(
                        $this->_application->H(__('Visit the following page to sign up for Algolia Places: %s')),
                        '<a href="https://www.algolia.com/users/sign_up/places" target="_blank" rel="nofollow noopener">https://www.algolia.com/users/sign_up/places</a>'
                    ),
                    '#description_no_escape' => true,
                    '#required' => function($form) {
                        return $form->getValue(['Map', 'lib', 'location_autocomplete']) === $this->_name;
                    },
                ],
                'country' => [
                    '#title' => isset($title) ? $title : __('Country code', 'directories-pro'),
                    '#description' => __('Enter two-letter ISO 3166-1 Alpha-2 compatible country codes separated by commas to restrict address suggestions to specific countries.', 'directories-pro'),
                    '#type' => 'textfield',
                    '#default_value' => $settings['autocomplete']['country'],
                    '#min_length' => 2,
                    '#max_length' => 2,
                    '#horizontal' => true,
                    '#placeholder' => 'US,JP',
                    '#separator' => ',',
                    '#alpha' => true,
                ],
            ],
        ];
    }
}