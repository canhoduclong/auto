<?php

namespace App\Support;

class VietnameseMoneyWords
{
    public static function format(int $number, string $thousands = 'nghìn'): string
    {
        if ($number === 0) {
            return 'Không đồng.';
        }

        $digits = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $units = ['', $thousands, 'triệu', 'tỷ', $thousands.' tỷ', 'triệu tỷ', 'tỷ tỷ'];

        $readThree = function (int $value, bool $full) use ($digits): string {
            $hundreds = intdiv($value, 100);
            $tens = intdiv($value % 100, 10);
            $ones = $value % 10;
            $parts = [];

            if ($hundreds > 0 || $full) {
                $parts[] = $digits[$hundreds] . ' trăm';
            }

            if ($tens > 1) {
                $parts[] = $digits[$tens] . ' mươi';
                if ($ones === 1) {
                    $parts[] = 'mốt';
                } elseif ($ones === 5) {
                    $parts[] = 'lăm';
                } elseif ($ones > 0) {
                    $parts[] = $digits[$ones];
                }
            } elseif ($tens === 1) {
                $parts[] = 'mười';
                if ($ones === 5) {
                    $parts[] = 'lăm';
                } elseif ($ones > 0) {
                    $parts[] = $digits[$ones];
                }
            } elseif ($ones > 0) {
                if ($hundreds > 0 || $full) {
                    $parts[] = 'lẻ';
                }
                $parts[] = $digits[$ones];
            }

            return trim(implode(' ', $parts));
        };

        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = intdiv($number, 1000);
        }

        $words = [];
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            $group = (int) $groups[$i];
            if ($group === 0) {
                continue;
            }

            $full = $i < count($groups) - 1 && $group < 100;
            $words[] = trim($readThree($group, $full) . ' ' . ($units[$i] ?? ''));
        }

        $text = trim(implode(' ', $words)) . ' đồng.';
        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
}
