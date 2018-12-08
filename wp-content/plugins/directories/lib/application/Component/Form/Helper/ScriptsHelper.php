<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class ScriptsHelper
{
    public function help(Application $application, array $options = [])
    {
        $platform = $application->getPlatform();
        $platform->addJsFile('form.min.js', 'drts-form', array('drts'));
        if (empty($options) || in_array('file', $options)) {
            $platform->loadJqueryUiJs(array('widget'))
                ->addJsFile('jquery.iframe-transport.min.js', 'jquery-iframe-transform', 'jquery', null, true, true)
                ->addJsFile('jquery.fileupload.min.js', 'jquery-fileupload', 'jquery-ui-widget', null, true, true)
                ->addJsFile('form-field-file.min.js', 'drts-form-field-file', array('jquery-fileupload', 'drts-form'));
        }
        if (empty($options) || in_array('slider', $options) || in_array('range', $options)) {
            $platform->addJsFile('ion.rangeSlider.min.js', 'ion-range-slider', array('jquery'), null, true, true)
                ->addJsFile('form-field-slider.min.js', 'drts-form-field-slider', array('drts-form', 'ion-range-slider'))
                ->addCssFile('ion.rangeSlider.min.css', 'ion-range-slider', null, null, null, true)
                ->addCssFile('ion.rangeSlider.skinNice.min.css', 'ion-range-slider-skin-nice', array('ion-range-slider'), null, null, true);
        }
        if (empty($options) || in_array('tableselect', $options) || in_array('options', $options)) {
            $platform->loadJqueryUiJs(array('sortable'));
        }
        if (empty($options) || in_array('text_maskedinput', $options)) {
            $platform->addJsFile('jquery.maskedinput.min.js', 'jquery-maskedinput', array('jquery'), null, true, true);
        }
        if (empty($options) || in_array('latinise', $options)) {
            $platform->addJsFile('latinise.min.js', 'latinise', null, null, true, true);
        }
        if (empty($options) || in_array('select', $options) || in_array('autocomplete', $options) || in_array('user', $options)) {
            $this->select2($application);
            $platform->addJsFile('form-field-select.min.js', 'drts-form-field-select', array('drts-form'));
        }
        if (empty($options) || in_array('iconpicker', $options)) {
            $this->iconpicker($application);
        }
        if (empty($options) || in_array('colorpicker', $options)) {
            $platform->addJsFile('huebee.pkgd.min.js', 'huebee', 'jquery', null, true, true)
                ->addCssFile('huebee.min.css', 'huebee', null, null, null, true)
                ->addJsFile('form-field-colorpicker.min.js', 'drts-form-field-colorpicker', array('drts-form', 'huebee'));
        }
        if (empty($options) || in_array('options', $options)) {
            $platform->addJsFile('form-field-options.min.js', 'drts-form-field-options', array('drts-form'));
        }
        if (empty($options) || in_array('selecthierarchical', $options)) {
            $platform->addJsFile('form-field-selecthierarchical.min.js', 'drts-form-field-selecthierarchical', array('drts-form'));
        }
        if (empty($options) || in_array('timepicker', $options)) {
            $this->date($application, true);
        }
        if (empty($options) || in_array('datepicker', $options) || in_array('daterange', $options)) {
            $this->date($application);
        }
        if (empty($options) || in_array('addmore', $options)) {
            $platform->addJsFile('form-field-addmore.min.js', 'drts-form-field-addmore', array('drts-form'));
        }
        if (empty($options) || in_array('upload', $options)) {
            $this->file($application);
        }
        if (empty($options) || in_array('editor', $options)) {
            $platform->addJsFile('codemirror.min.js', 'codemirror', null, null, true, true)
                ->addCssFile('codemirror.min.css', 'codemirror', null, null, null, true)
                ->addCssFile('codemirror/theme/mdn-like.min.css', 'codemirror-theme-midnight', array('codemirror'), null, null, true);
        }
        $application->Action('form_scripts', array($options));
    }

    public function date(Application $application, $time = false, $locale = null)
    {
        $platform = $application->getPlatform();
        $platform->addJsFile('flatpickr.min.js', 'flatpickr', 'jquery', null, true, true)
            ->addCssFile('flatpickr.min.css', 'flatpickr', null, null, null, true);
        $js_file = $time ? 'form-field-timepicker' : 'form-field-datepicker';
        $platform->addJsFile($js_file . '.min.js', 'drts-' . $js_file, ['flatpickr', 'drts-form']);
        if (isset($locale)
            || ($locale = $this->locale($application))
        ) {
            foreach ((array)$locale as $_locale) {
                if (in_array($_locale, ['ar', 'at', 'be', 'bg', 'bn', 'cat', 'cs', 'cy', 'da', 'de',
                    'eo', 'es', 'et', 'fa', 'fi', 'fr', 'gr', 'he', 'hi', 'hr', 'hu', 'id', 'it', 'ja', 'ko', 'lt', 'lv',
                    'mk', 'mn', 'ms', 'my', 'nl', 'no', 'pa', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr', 'sv',
                    'th', 'tr', 'uk', 'vn', 'zh'
                ])) {
                    $platform->addJsFile('flatpickr/l10n/' . $_locale . '.min.js', 'flatpickr-l10n', ['flatpickr'], null, true, true);
                }
            }
        }
    }

    public function locale(Application $application)
    {
        if ($locale = $application->getPlatform()->getLocale()) {
            if (strpos($locale, '_')) {
                $locale = explode('_', $locale)[0];
            }
        }
        return (string)$locale;
    }

    public function file(Application $application)
    {
        $application->getPlatform()->loadJqueryUiJs(['widget', 'sortable', 'effects-highlight'])
            ->addJsFile('jquery.iframe-transport.min.js', 'jquery-iframe-transport', 'jquery', null, true, true)
            ->addJsFile('jquery.fileupload.min.js', 'jquery-fileupload', 'jquery-ui-widget', null, true, true)
            ->addJsFile('form-field-upload.min.js', 'drts-form-field-upload', ['jquery-fileupload', 'jquery-ui-sortable', 'drts-form']);
    }

    public function select2(Application $application)
    {
        $application->getPlatform()->addJsFile('select2.min.js', 'select2', array('jquery'), null, true, true)
            ->addCssFile('select2.min.css', 'select2', null, null, null, true)
            ->addCssFile('form-select2.min.css', 'drts-form-select2', 'select2');
    }

    public function iconpicker(Application $application)
    {
        $application->getPlatform()->addJsFile('form-field-picker.min.js', 'drts-form-field-picker', 'drts-form')
            ->addJsFile('form-field-iconpicker.min.js', 'drts-form-field-iconpicker', 'drts-form-field-picker');
    }
}
