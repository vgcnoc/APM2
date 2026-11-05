<?php

namespace App\Support;

class NumberHelper
{
    /**
     * Konversi angka nominal ke kalimat terbilang Bahasa Indonesia.
     */
    public static function terbilang($number): string
    {
        $number = abs((float)$number);
        $huruf = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
            'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'
        ];

        $temp = '';

        if ($number < 12) {
            $temp = ' ' . $huruf[(int)$number];
        } elseif ($number < 20) {
            $temp = self::terbilang($number - 10) . ' Belas';
        } elseif ($number < 100) {
            $temp = self::terbilang((int)($number / 10)) . ' Puluh' . self::terbilang($number % 10);
        } elseif ($number < 200) {
            $temp = ' Seratus' . self::terbilang($number - 100);
        } elseif ($number < 1000) {
            $temp = self::terbilang((int)($number / 100)) . ' Ratus' . self::terbilang($number % 100);
        } elseif ($number < 2000) {
            $temp = ' Seribu' . self::terbilang($number - 1000);
        } elseif ($number < 1000000) {
            $temp = self::terbilang((int)($number / 1000)) . ' Ribu' . self::terbilang($number % 1000);
        } elseif ($number < 1000000000) {
            $temp = self::terbilang((int)($number / 1000000)) . ' Juta' . self::terbilang($number % 1000000);
        } elseif ($number < 1000000000000) {
            $temp = self::terbilang((int)($number / 1000000000)) . ' Miliar' . self::terbilang(fmod($number, 1000000000));
        } elseif ($number < 1000000000000000) {
            $temp = self::terbilang((int)($number / 1000000000000)) . ' Triliun' . self::terbilang(fmod($number, 1000000000000));
        }

        return trim(preg_replace('/\s+/', ' ', $temp));
    }

    /**
     * Format angka ke Rupiah dengan kalimat terbilang lengkap.
     */
    public static function terbilangRupiah($number): string
    {
        $hasil = self::terbilang($number);
        if (empty($hasil) || $number == 0) {
            return 'Nol Rupiah';
        }
        return $hasil . ' Rupiah';
    }
}

