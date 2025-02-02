<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\Helpers;

use Bitrix\Main\Type\DateTime;
use DateTime as NormalDateTime;

/**
 * Class DateHelper
 *
 * @package FourPaws\Helpers
 */
class DateHelper
{
    /** именительный падеж */
    public const NOMINATIVE = 'Nominative';
    
    /** родительный падеж */
    public const GENITIVE = 'Genitive';
    
    /** именительный падеж короткий*/
    public const SHORT_NOMINATIVE = 'ShortNominative';
    
    /** родительный падеж короткий */
    public const SHORT_GENITIVE = 'ShortGenitive';

    /** дательный падеж множ. число */
    const DATIVE_PLURAL = 'DativePlural';

    /**Месяца в родительном падеже*/
    private static $monthGenitive = [
        '#1#'  => 'Января',
        '#2#'  => 'Февраля',
        '#3#'  => 'Марта',
        '#4#'  => 'Апреля',
        '#5#'  => 'Мая',
        '#6#'  => 'Июня',
        '#7#'  => 'Июля',
        '#8#'  => 'Августа',
        '#9#'  => 'Сентября',
        '#10#' => 'Октября',
        '#11#' => 'Ноября',
        '#12#' => 'Декабря',
    ];
    
    /** Месяца в именительном падеже  */
    private static $monthNominative = [
        '#1#'  => 'Январь',
        '#2#'  => 'Февраль',
        '#3#'  => 'Март',
        '#4#'  => 'Апрель',
        '#5#'  => 'Май',
        '#6#'  => 'Июнь',
        '#7#'  => 'Июль',
        '#8#'  => 'Август',
        '#9#'  => 'Сентябрь',
        '#10#' => 'Октябрь',
        '#11#' => 'Ноябрь',
        '#12#' => 'Декабрь',
    ];
    
    /** кратские месяца в именительном падеже  */
    private static $monthShortNominative = [
        '#1#'  => 'янв',
        '#2#'  => 'фев',
        '#3#'  => 'мар',
        '#4#'  => 'апр',
        '#5#'  => 'май',
        '#6#'  => 'июн',
        '#7#'  => 'июл',
        '#8#'  => 'авг',
        '#9#'  => 'сен',
        '#10#' => 'окт',
        '#11#' => 'ноя',
        '#12#' => 'дек',
    ];
    
    /**кратские месяца в родительном падеже*/
    private static $monthShortGenitive = [
        '#1#'  => 'янв',
        '#2#'  => 'фев',
        '#3#'  => 'мар',
        '#4#'  => 'апр',
        '#5#'  => 'мая',
        '#6#'  => 'июн',
        '#7#'  => 'июл',
        '#8#'  => 'авг',
        '#9#'  => 'сен',
        '#10#' => 'окт',
        '#11#' => 'ноя',
        '#12#' => 'дек',
    ];
    
    /**дни недели в именительном падеже*/
    private static $dayOfWeekNominative = [
        '#1#' => 'Понедельник',
        '#2#' => 'Вторник',
        '#3#' => 'Среда',
        '#4#' => 'Четверг',
        '#5#' => 'Пятница',
        '#6#' => 'Суббота',
        '#7#' => 'Воскресенье',
    ];

    /** дни недели в множ. числе дат. падеже */
    private static $dayOfWeekDativePlural = [
        '#1#' => 'Понедельникам',
        '#2#' => 'Вторникам',
        '#3#' => 'Средам',
        '#4#' => 'Четвергам',
        '#5#' => 'Пятницам',
        '#6#' => 'Субботам',
        '#7#' => 'Воскресеньям',
    ];

    /**краткие дни недели*/
    private static $dayOfWeekShortNominative = [
        '#1#' => 'пн',
        '#2#' => 'вт',
        '#3#' => 'ср',
        '#4#' => 'чт',
        '#5#' => 'пт',
        '#6#' => 'сб',
        '#7#' => 'вс',
    ];
    
    /**
     * @param string $date
     *
     * @param string $case
     *
     * @param bool $lower
     *
     * @return string
     */
    public static function replaceRuMonth(string $date, string $case = 'Nominative', bool $lower = false) : string
    {
        $res = static::replaceStringByArray(
            [
                'date'    => $date,
                'case'    => $case,
                'type'    => 'month',
                'pattern' => '|#\d{1,2}#|',
            ]
        );
        if($lower){
            $res = ToLower($res);
        }

        return $res;
    }
    
    private static function replaceStringByArray(array $params)
    {
        preg_match($params['pattern'], $params['date'], $matches);
        if (!empty($matches[0]) && !empty($params['case'])) {
            $items = static::${$params['type'] . $params['case']};
            if (!empty($items)) {
                return str_replace($matches[0], $items[$matches[0]], $params['date']);
            }
        }
        
        return $params['date'];
    }
    
    /**
     * @param string $date
     *
     * @param string $case
     *
     * @return string
     */
    public static function replaceRuDayOfWeek(string $date, string $case = 'Nominative') : string
    {
        return static::replaceStringByArray(
            [
                'date'    => $date,
                'case'    => $case,
                'type'    => 'dayOfWeek',
                'pattern' => '|#\d{1}#|',
            ]
        );
    }

    /**
     * @param DateTime $bxDatetime
     *
     * @return NormalDateTime
     */
    public static function convertToDateTime(DateTime $bxDatetime): NormalDateTime
    {
        return (new NormalDateTime())->setTimestamp($bxDatetime->getTimestamp());
    }

    /**
     * Враппер для FormatDate. Доп. возможности
     *  - ll - отображение для недели в винительном падеже (в пятницу, в субботу)
     *  - XX - 'Сегодня', 'Завтра'
     * @param string $dateFormat
     * @param int $timestamp
     *
     * @return string
     */
    public static function formatDate(string $dateFormat, int $timestamp)
    {
        $date = (new \DateTime)->setTimestamp($timestamp);
        if (false !== mb_strpos($dateFormat, 'll')) {
            $str = null;
            switch ($date->format('w')) {
                case 0:
                    $str = 'в воскресенье';
                    break;
                case 1:
                    $str = 'в понедельник';
                    break;
                case 2:
                    $str = 'во вторник';
                    break;
                case 3:
                    $str = 'в среду';
                    break;
                case 4:
                    $str = 'в четверг';
                    break;
                case 5:
                    $str = 'в пятницу';
                    break;
                case 6:
                    $str = 'в субботу';
                    break;
            }
            if (null !== $str) {
                $dateFormat = str_replace('ll', $str, $dateFormat);
            }
        }
        if (false !== mb_strpos($dateFormat, 'XX')) {
            $tmpDate = clone $date;
            $currentDate = new \DateTime();
            $tmpDate->setTime(0,0,0,0);
            $currentDate->setTime(0,0,0,0);

            $diff = $tmpDate->diff($currentDate)->days;
            switch (true) {
                case $diff === 0:
                    $str = 'Сегодня';
                    break;
                case $diff === 1:
                    $str = 'Завтра';
                    break;
                default:
                    $str = 'j F';
            }
            $dateFormat = str_replace('XX', $str, $dateFormat);
        }

        return FormatDate($dateFormat, $timestamp);
    }

    /**
     * @param \DateTime $date1
     * @param \DateTime $date2
     *
     * @return int
     */
    public static function diffDays(\DateTime $date1, \DateTime $date2): int
    {
        $clone1 = (clone $date1)->setTime(0,0,0,0);
        $clone2 = (clone $date2)->setTime(0,0,0,0);

        return $clone1->diff($clone2)->days;
    }
}
