<?php
namespace SabaiApps\Directories\Component\Voting\Helper;

use SabaiApps\Directories\Application;

class RenderRatingHelper
{
    protected $_cssLoaded = false;
    
    public function help(Application $application, $value, array $options = [])
    {
        $this->_loadCss($application);
        
        $options += array(
            'count' => null,
            'color' => null,
            'default_color' => 'warning',
            'decimals' => 1,
        );
        $color_class = $color_style = '';
        if (isset($options['color']['type'])
            && $options['color']['type'] === 'custom'
        ) {
            $color_style = 'color:' . $application->H($options['color']['value']) . ';';
        } else {
            $color_class = DRTS_BS_PREFIX . 'text-' . $options['default_color'];
        }
        $rounded = round($value, 1) * 10;
        $remainder = $rounded % 5;
        $rounded -= $remainder;
        if ($remainder > 2) {
            $rounded += 5;
        }
        $html = sprintf(
            '<span class="drts-voting-rating-stars drts-voting-rating-stars-%2$d %5$s" style="%6$s" title="%3$s"></span><span class="drts-voting-rating-average %1$sml-1">%4$s</span>',
            DRTS_BS_PREFIX,
            $rounded,
            $application->H(sprintf(__('%.2f out of 5 stars', 'directories'), $value)),
            number_format($value, $options['decimals']),
            $color_class,
            $color_style
        );
        if (isset($options['count'])) {
            $html .= sprintf('<span class="drts-voting-rating-count %1$sml-1">%2$s</span>', DRTS_BS_PREFIX, $options['count']);
        }
        
        return $html;
    }
    
    public function bar(Application $application, $value, $label = null, array $options = [])
    {
        $this->_loadCss($application);
        
        $options += array(
            'color' => null,
            'default_color' => 'primary',
            'decimals' => 1,
            'show_value' => true,
            'percent' => null,
            'height' => 12,
            'style' => 'margin-bottom:calc(0.5em + %2$dpx);',
            'inline' => false,
        );
        if (isset($options['percent'])) {
            $formatted_value = round($options['percent']) . '%';
        } else {
            $formatted_value = isset($value) ? number_format($value, $options['decimals']) : _x('N/A', 'no rating', 'directories');
        }

        $color_class = $color_style = '';
        if (isset($options['color']['type'])
            && $options['color']['type'] === 'custom'
        ) {
            $color_style = 'background-color:' . $application->H($options['color']['value']) . ';';
        } else {
            $color_class = DRTS_BS_PREFIX . 'bg-' . $options['default_color'];
        }
        return sprintf(
            '<div class="drts-voting-rating-bar %9$s" style="%8$s">
    <div class="drts-voting-rating-bar-title">%2$s</div>
    <div class="drts-voting-rating-bar-progress">
        <div class="%1$sprogress" style="height:%7$dpx;">
            <div class="%1$sprogress-bar %6$s" style="width:%3$d%%;%10$s" role="progressbar" aria-valuenow="%4$d" aria-valuemin="0" aria-valuemax="5"></div>
        </div>
    </div>
    <div class="drts-voting-rating-bar-value">%5$s</div>
</div>',
            DRTS_BS_PREFIX,
            $application->H($label),
            isset($options['percent']) ? $options['percent'] : $value * 20,
            $value,
            $formatted_value,
            $color_class,
            $options['height'],
            isset($options['style']) ? sprintf($options['style'], $options['height'], $options['height'] / 3) : '',
            $options['inline'] ? 'drts-voting-rating-bar-inline' : '',
            $color_style
        );
    }
    
    public function barHeightForm(Application $application, $value = null)
    {
        return array(
            '#title' => __('Bar height', 'directories'),
            '#type' => 'slider',
            '#default_value' => $value,
            '#min_value' => 5,
            '#max_value' => 50,
            '#integer' => true,
            '#step' => 1,
            '#horizontal' => true,
            '#field_suffix' => 'px',
        );
    }
    
    protected function _loadCss(Application $application)
    {
        if (!$this->_cssLoaded) {
            $application->getPlatform()->addCssFile('voting-rating.min.css', 'drts-voting-rating', array('drts'), 'directories');
            $this->_cssLoaded = true;
        }
    }
    
    public function barsByLevel(Application $application, $bundleName, $entityId, $fieldName, $name, array $options = [])
    {
        $options += array(
            'default_color' => 'warning',
            'level_max' => 5,
            'level_label' => __('%d star', 'directories'),
            'style' => 'margin-bottom:0;',
        );
        $counts = $application->getModel(null, 'Voting')->getGateway('Vote')->countByLevel($bundleName, $entityId, $fieldName, $name);
        $num = array_sum($counts);
        $html = [];
        for ($i = $options['level_max']; $i > 0; --$i) {
            $html[] = $this->bar(
                $application,
                $count = isset($counts[$i]) ? $counts[$i] : 0,
                sprintf($options['level_label'], $i),
                array('percent' => $count ? ($count / $num) * 100 : 0) + $options
            );
        }
        return implode(PHP_EOL, $html);
    }
    
    public function options(Application $application, $isSelect = false, $emptyValue = '', $color = '#ffb900')
    {
        if (!$isSelect) {
            $this->_loadCss($application);

            $class = 'drts-voting-rating-stars';
            if ($application->getPlatform()->isRtl()) {
                $class .= ' drts-voting-rating-stars-rtl';
            }
            $style = '';
            if (isset($color)) {
                $color = $application->H($color);
                if (strpos($color, '#') === 0) {
                    $style .= 'color:' . $color . ';';
                } else {
                    $class .= ' ' . DRTS_BS_PREFIX . 'text-' . $color;
                }
            }
            $options = array(5 => '<span class="' . $class . ' drts-voting-rating-stars-50" style="' . $style . '"></span>');
            for ($i = 4; $i > 0; --$i) {
                $options[$i] = sprintf(__('%s & Up', 'directories'), '<span class="' . $class . ' drts-voting-rating-stars-'. $i * 10 .'" style="' . $style . '"></span>');
            }
            $options[$emptyValue] = _x('Any', 'option', 'directories');
        } else {
            $options = array($emptyValue => _x('Any', 'option', 'directories'), 5 => sprintf(__('%d stars', 'directories'), 5));
            for ($i = 4; $i > 0; --$i) {
                $options[$i] = sprintf(__('%d+ stars', 'directories'), $i);
            }
        }
        return $options;
    }
}