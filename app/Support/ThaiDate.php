<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * แสดงวันที่แบบไทย (พ.ศ.)
 */
class ThaiDate
{
    public const MONTHS = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];

    public const MONTHS_SHORT = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

    public const DAYS_SHORT = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

    public static function parse($date): ?CarbonInterface
    {
        if (! $date) {
            return null;
        }

        return $date instanceof CarbonInterface ? $date : Carbon::parse($date);
    }

    /** 28 ต.ค. 2569 */
    public static function short($date): string
    {
        $d = self::parse($date);

        return $d ? $d->day.' '.self::MONTHS_SHORT[$d->month].' '.($d->year + 543) : '-';
    }

    /** 28 ตุลาคม 2569 */
    public static function long($date): string
    {
        $d = self::parse($date);

        return $d ? $d->day.' '.self::MONTHS[$d->month].' '.($d->year + 543) : '-';
    }

    /** 28 ต.ค. 2569 14:05 น. */
    public static function dateTime($date): string
    {
        $d = self::parse($date);

        return $d ? self::short($d).' '.$d->format('H:i').' น.' : '-';
    }

    /** ตุลาคม 2569 */
    public static function monthYear($date): string
    {
        $d = self::parse($date);

        return $d ? self::MONTHS[$d->month].' '.($d->year + 543) : '-';
    }
}
