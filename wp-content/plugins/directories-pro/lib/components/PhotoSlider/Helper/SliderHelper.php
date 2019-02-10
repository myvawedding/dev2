<?php
namespace SabaiApps\Directories\Component\PhotoSlider\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;

class SliderHelper
{
    private static $_count = 0, $_jsLoaded, $_zoomLoaded;

    public function help(Application $application, array $photos, array $options = [], $addJs = true)
    {
        // Init options
        if (empty($options['photoslider_columns'])) $options['photoslider_columns'] = 4;
        if ($options['photoslider_columns'] > $photo_count = count($photos)) {
            $options['photoslider_columns'] = $photo_count;
        }
        if (!isset($options['photoslider_responsive'])) {
            $options['photoslider_responsive'] = [];
            if ($options['photoslider_columns'] >= 5) {
                $options['photoslider_responsive'][] = array(
                    'breakpoint' => 1300,
                    'settings' => array(
                        'slidesToShow' => 5,
                    ),
                );
            }
            if ($options['photoslider_columns'] >= 4) {
                $options['photoslider_responsive'][] = array(
                    'breakpoint' => 1024,
                    'settings' => array(
                        'slidesToShow' => 4,
                    ),
                );
            }
            if ($options['photoslider_columns'] >= 3) {
                $options['photoslider_responsive'][] = array(
                    'breakpoint' => 820,
                    'settings' => array(
                        'arrows' => false,
                        'centerPadding' => '40px',
                        'slidesToShow' => 3,
                    ),
                );
                $options['photoslider_responsive'][] = array(
                    'breakpoint' => 540,
                    'settings' => array(
                        'arrows' => false,
                        'centerPadding' => '40px',
                        'slidesToShow' => 2,
                        'dots' => false,
                    ),
                );
                $options['photoslider_responsive'][] = array(
                    'breakpoint' => 360,
                    'settings' => array(
                        'arrows' => false,
                        'centerPadding' => '40px',
                        'slidesToShow' => 1,
                        'dots' => false,
                    ),
                );
            }
        }
        $id = isset($options['photoslider_id']) ? $options['photoslider_id'] : 'drts-photoslider-' . uniqid() . '-' . ++self::$_count;
        $slick_options = [];
        if (!empty($options['photoslider_thumbs'])
            && is_array($options['photoslider_thumbs'])
        ) {
            $thumbs_id = $id . '-thumbs';
            $slick_options['asNavFor'] = '#' . $thumbs_id;
            $thumbs_slick_options = array(
                'asNavFor' => '#' . $id,
                'dots' => !empty($options['photoslider_pager']),
                'slidesToShow' => empty($options['photoslider_thumbs_columns']) ? 5 : $options['photoslider_thumbs_columns'],
                'centerMode' => !empty($options['photoslider_thumbs_center']),
                'arrows' => !isset($options['photoslider_controls']) || !empty($options['photoslider_controls']),
                'focusOnSelect' => true,
            );
            $options['photoslider_pager'] = false;
            $options['photoslider_controls'] = false;
        }
        $slick_options += array(
            'centerMode' => !empty($options['photoslider_center']),
            'dots' => !empty($options['photoslider_pager']),
            'autoplay' => !empty($options['photoslider_auto']),
            'autoplaySpeed' => isset($options['photoslider_auto_speed']) ? $options['photoslider_auto_speed'] : 3000,
            'speed' => 260,
            'centerPadding' => '90px',
            'slidesToShow' => $options['photoslider_columns'],
            'arrows' => !isset($options['photoslider_controls']) || !empty($options['photoslider_controls']),
            'fade' => !empty($options['photoslider_fade']) && $options['photoslider_columns'] === 1,
            'adaptiveHeight' => true,
            'responsive' => $options['photoslider_responsive'],
            'focusOnSelect' => $options['photoslider_columns'] > 1,
            'infinite' => !isset($options['photoslider_infinite']) || $options['photoslider_infinite'],
            'lazyLoad' => empty($options['photoslider_lazyload']) ? false : 'progressive',
            'rtl' => $is_rtl = $application->getPlatform()->isRtl(),
        );

        // HTML
        $class = 'drts-photoslider';
        if (isset($options['photoslider_class'])) $class .= ' ' . $application->H($options['photoslider_class']);
        $html = [
            '<div class="' . $class . '">',
            '<div class="drts-photoslider-main" id="' . $id . '" data-slick-options="' . $application->H($application->JsonEncode($slick_options)) . '" dir="' . ($is_rtl ? 'rtl' : '') . '">',
        ];
        $height = empty($options['photoslider_height']) ? '' : ' style="max-height:' . $application->H($options['photoslider_height']) . 'px"';
        $padding = empty($options['photoslider_padding']) ? '' : ' style="padding:0 ' . $application->H($options['photoslider_padding']) . 'px"';
        $img_zoom_attr = !empty($options['photoslider_zoom']) ? ' data-action="zoom"' : '';
        foreach (array_keys($photos) as $k) {
            if (!isset($photos[$k]['type'])) $photos[$k]['type'] = 'image';
            $photo = $photos[$k];
            switch ($photo['type']) {
                case 'image':
                    if (!isset($photo['img'])) {
                        if (empty($options['photoslider_allow_no_image'])) {
                            unset($photos[$k]);
                            continue;
                        }
                        if (!isset($no_image)) $no_image = $application->NoImage();
                        $photo['img'] = $no_image;
                    }
                    $item = '';
                    $title = isset($photo['title']) ? $application->H($photo['title']) : '';
                    $do_link = isset($photo['url']) && empty($options['photoslider_zoom']);
                    $alt = $do_link ? '' : $title;
                    $img = $application->H($photo['img']);
                    if (empty($options['photoslider_lazyload'])) {
                        $item .= '<figure' . $height . '><img src="' . $img . '" alt="' . $alt . '"' . $img_zoom_attr . ' />';
                    } else {
                        $item .= '<figure' . $height . '><img data-lazy="' . $img . '" alt="' . $alt . '"' . $img_zoom_attr . ' />';
                    }
                    if (isset($photo['tag'])) {
                        $item .= '<span class="drts-photoslider-tag ' . DRTS_BS_PREFIX . 'bg-warning">' . $application->H($photo['tag']) . '</span>';
                    }
                    if (!empty($options['photoslider_caption'])) {
                        $item .= '<figcaption>' . $title . '</figcaption>';
                    }
                    $item .= '</figure>';
                    if ($do_link) {
                        $item = '<a href="' . $application->H($photo['url']) . '" title="' . $title .'">' . $item . '</a>';
                    } elseif (empty($options['photoslider_zoom'])) {
                        $full_img = !empty($photo['full_img']) ? $application->H($photo['full_img']) : $img;
                        $item = '<a href="' . $full_img . '" title="' . $title .'" data-rel="lightbox-gallery-' . $id . '">' . $item . '</a>';
                    }
                    break;
                case 'youtube':
                    $item = sprintf(
                        '<iframe height="400" src="https://www.youtube.com/embed/%1$s?enablejsapi=1&controls=2&fs=1&iv_load_policy=3&rel=0&showinfo=1&loop=0&start=0" frameborder="0" allowfullscreen></iframe>',
                        $photo['video_id']
                    );
                    break;
                case 'vimeo':
                    $item = sprintf(
                        '<iframe height="400" src="https://player.vimeo.com/video/%s?api=1&byline=0&portrait=1&title=1&background=0&mute=0&loop=0&autoplay=0" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>',
                        $photo['video_id']
                    );
                    break;
                case 'video':
                    break;
                default:
                    unset($photos[$k]);
                    continue;
            }
            $html[] = '<div' . $padding . ' data-type="' . $photo['type'] . '">' . $item . '</div>';
        }
        $html[] = '</div>';
        // Add thumbnail nav?
        if (isset($thumbs_id)) {
            $html[] = '<div class="drts-photoslider-thumbnails" id="' . $thumbs_id . '" data-slick-options="' . $application->H($application->JsonEncode($thumbs_slick_options)) . '">';
            foreach (array_keys($options['photoslider_thumbs']) as $k) {
                if (!isset($photos[$k])) continue;

                $thumb = $options['photoslider_thumbs'][$k];
                if (isset($thumb['img'])) {
                    $img = '<img src="' . $application->H($thumb['img']) . '" alt="" />';
                } else {
                    if (!isset($no_image)) $no_image = $application->NoImage();
                    $img = $no_image;
                }
                $html[] = '<div data-type="' . $photos[$k]['type'] . '">' . $img . '</div>';
            }
            $html[] = '</div>';
        }
        $html[] = '</div>';
        $html = $application->Filter('photoslider_html', implode(PHP_EOL, $html));

        if (!self::$_jsLoaded) {
            $application->getPlatform()
                ->addJsFile('slick.custom.min.js', 'slick', array('jquery'), 'directories-pro')
                ->addJsFile('photoslider.min.js', 'drts-photoslider', array('drts', 'slick'), 'directories-pro')
                ->addCssFile('photoslider.min.css', 'drts-photoslider', [], 'directories-pro');
            self::$_jsLoaded = true;
        }
        if (!empty($options['photoslider_zoom'])
            && !self::$_zoomLoaded
        ) {
            $application->getPlatform()
                ->addJsFile('zoom-vanilla.min.js', 'zoom-vanilla', null, 'directories-pro', true, true)
                ->addCssFile('zoom.min.css', 'zoom', null, 'directories-pro', null, true);
        }

        if ($addJs) {
            if (Request::isXhr()) {
                $html .= '<script type="text/javascript">jQuery(function($) {';
            } else {
                $html .= '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function(event) {';
            }
            $html .= 'setTimeout(function(){DRTS.photoSlider("#' . $id . '");}, 100);';
            $html .= '});</script>';
        }

        return $html;
    }
}
