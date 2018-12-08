<?php
namespace SabaiApps\Directories\Component\System\Helper;

use SabaiApps\Directories\Application;

class DateHelper
{
    public function help(Application $application, $timestamp, $html = false)
    {
        return $this->_render($application, $application->getPlatform()->getDateFormat(), $timestamp, $html);
    }

    public function time(Application $application, $timediff, $html = false)
    {
        $timestamp = mktime(0, 0, 0) + $timediff;
        $ret = $application->getPlatform()->getDate($application->getPlatform()->getTimeFormat(), $timestamp, false);
        return $html ? '<time class="drts-datetime">' . $ret . '</time>' : $ret;
    }

    public function datetime(Application $application, $timestamp, $html = false)
    {
        return $this->_render(
            $application,
            sprintf(
                _x('%s %s', 'date/time format', 'directories'),
                $application->getPlatform()->getDateFormat(),
                $application->getPlatform()->getTimeFormat()
            ),
            $timestamp,
            $html
        );
    }

    protected function _render(Application $application, $format, $timestamp, $html = false)
    {
        $ret = $application->getPlatform()->getDate($format, $timestamp, true);
        return $html ? '<time class="drts-datetime" datetime="' . date('c' , $timestamp) . '">' . $ret . '</time>' : $ret;
    }
}
