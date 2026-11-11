<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeoLocationMasterService
{
    /**
     * Master data Kecamatan dan Desa/Kelurahan resmi wilayah operasional ISP (Kabupaten Sukabumi)
     */
    public static function getMasterDirectory(): array
    {
        return [
            'Warungkiara' => [
                'name' => 'Warungkiara',
                'type' => 'Kecamatan',
                'default_area' => 'BC 3',
                'villages' => [
                    'Sirnajaya'    => ['name' => 'Sirnajaya', 'type' => 'Desa', 'area' => 'BC 1'],
                    'Bantarkalong' => ['name' => 'Bantarkalong', 'type' => 'Desa', 'area' => 'BC 1'],
                    'Hegarmanah'   => ['name' => 'Hegarmanah', 'type' => 'Desa', 'area' => 'BC 1'],
                    'Warungkiara'  => ['name' => 'Warungkiara', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Tarisi'       => ['name' => 'Tarisi', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Ubrug'        => ['name' => 'Ubrug', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Damarraja'    => ['name' => 'Damarraja', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Kertamukti'   => ['name' => 'Kertamukti', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Sukaharja'    => ['name' => 'Sukaharja', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Bojongkerta'  => ['name' => 'Bojongkerta', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Girijaya'     => ['name' => 'Girijaya', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Mekarjaya'    => ['name' => 'Mekarjaya', 'type' => 'Desa', 'area' => 'BC 3'],
                ]
            ],
            'Cikembar' => [
                'name' => 'Cikembar',
                'type' => 'Kecamatan',
                'default_area' => 'BC 2',
                'villages' => [
                    'Cikembar'     => ['name' => 'Cikembar', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Cimanggu'     => ['name' => 'Cimanggu', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Bojongkembar' => ['name' => 'Bojongkembar', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Bojongraharja'=> ['name' => 'Bojongraharja', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Kertaraharja' => ['name' => 'Kertaraharja', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Cibatu'       => ['name' => 'Cibatu', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Parakanlima'  => ['name' => 'Parakanlima', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Sukamaju'     => ['name' => 'Sukamaju', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Sukamulya'    => ['name' => 'Sukamulya', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Bojong'       => ['name' => 'Bojong', 'type' => 'Desa', 'area' => 'BC 2'],
                ]
            ],
            'Bantargadung' => [
                'name' => 'Bantargadung',
                'type' => 'Kecamatan',
                'default_area' => 'BC 3',
                'villages' => [
                    'Bantargadung' => ['name' => 'Bantargadung', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Boyongsari'   => ['name' => 'Boyongsari', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Mangunjaya'   => ['name' => 'Mangunjaya', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Buanajaya'    => ['name' => 'Buanajaya', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Bantargebang' => ['name' => 'Bantargebang', 'type' => 'Desa', 'area' => 'BC 3'],
                    'Limusnunggal' => ['name' => 'Limusnunggal', 'type' => 'Desa', 'area' => 'BC 3'],
                ]
            ],
            'Cibadak' => [
                'name' => 'Cibadak',
                'type' => 'Kecamatan',
                'default_area' => 'BC 4',
                'villages' => [
                    'Cibadak'          => ['name' => 'Cibadak', 'type' => 'Kelurahan', 'area' => 'BC 4'],
                    'Karangtengah'    => ['name' => 'Karangtengah', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Sukasirna'        => ['name' => 'Sukasirna', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Sekarwangi'       => ['name' => 'Sekarwangi', 'type' => 'Kelurahan', 'area' => 'BC 4'],
                    'Batununggal'      => ['name' => 'Batununggal', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Ciheulang Tonggoh'=> ['name' => 'Ciheulang Tonggoh', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Neglasari'        => ['name' => 'Neglasari', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Pamuruyan'        => ['name' => 'Pamuruyan', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Tenjojaya'        => ['name' => 'Tenjojaya', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Warnajati'        => ['name' => 'Warnajati', 'type' => 'Desa', 'area' => 'BC 4'],
                ]
            ],
            'Cicantayan' => [
                'name' => 'Cicantayan',
                'type' => 'Kecamatan',
                'default_area' => 'BC 2',
                'villages' => [
                    'Cicantayan'  => ['name' => 'Cicantayan', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Cimanggis'   => ['name' => 'Cimanggis', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Cijalingan'  => ['name' => 'Cijalingan', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Cimahi'      => ['name' => 'Cimahi', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Cisande'     => ['name' => 'Cisande', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Lembursawah' => ['name' => 'Lembursawah', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Sikareumbi'  => ['name' => 'Sikareumbi', 'type' => 'Desa', 'area' => 'BC 2'],
                    'Sukadamai'   => ['name' => 'Sukadamai', 'type' => 'Desa', 'area' => 'BC 2'],
                ]
            ],
            'Parungkuda' => [
                'name' => 'Parungkuda',
                'type' => 'Kecamatan',
                'default_area' => 'BC 4',
                'villages' => [
                    'Parungkuda'    => ['name' => 'Parungkuda', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Bojongkokosan' => ['name' => 'Bojongkokosan', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Kompa'         => ['name' => 'Kompa', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Langkensari'   => ['name' => 'Langkensari', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Palasarihilir' => ['name' => 'Palasarihilir', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Sundawenang'   => ['name' => 'Sundawenang', 'type' => 'Desa', 'area' => 'BC 4'],
                ]
            ],
            'Nagrak' => [
                'name' => 'Nagrak',
                'type' => 'Kecamatan',
                'default_area' => 'BC 4',
                'villages' => [
                    'Nagrak Selatan' => ['name' => 'Nagrak Selatan', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Nagrak Utara'   => ['name' => 'Nagrak Utara', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Cihanjawar'     => ['name' => 'Cihanjawar', 'type' => 'Desa', 'area' => 'BC 4'],
                    'Cisarua'        => ['name' => 'Cisarua', 'type' => 'Desa', 'area' => 'BC 4'],
                ]
            ],
        ];
    }

    /**
     * Normalisasi cerdas nama Kecamatan dari ketikan pengguna atau data riwayat (Fuzzy Correction)
     */
    public static function normalizeDistrict(?string $raw): ?string
    {
        if (empty($raw)) return null;
        $clean = strtolower(trim(preg_replace('/^(?:Kecamatan|Kec\.?|matan)\s*/i', '', $raw)));
        $clean = preg_replace('/[^a-z0-9]/', '', $clean);

        // Alias & typo rules
        if (in_array($clean, [
            'warungkiara', 'warkir', 'waeungkiara', 'wwarungkiara', 'wrkiara', 
            'warubgkiara', 'warungnyakiara', 'warungkir', 'wr'
        ])) {
            return 'Warungkiara';
        }

        if (in_array($clean, [
            'cikembar', 'cikemba', 'cikrmbar', 'cikembang', 'ckembar'
        ])) {
            return 'Cikembar';
        }

        if (in_array($clean, [
            'bantargadung', 'bantargadug', 'bantar'
        ])) {
            return 'Bantargadung';
        }

        if (in_array($clean, [
            'cibadak', 'cbadak'
        ])) {
            return 'Cibadak';
        }

        if (in_array($clean, [
            'cicantayan', 'cantayan'
        ])) {
            return 'Cicantayan';
        }

        if (in_array($clean, [
            'parungkuda', 'pkuda'
        ])) {
            return 'Parungkuda';
        }

        if (in_array($clean, [
            'nagrak'
        ])) {
            return 'Nagrak';
        }

        // Cek kemiripan dengan master directory
        foreach (array_keys(self::getMasterDirectory()) as $d) {
            $dClean = strtolower(preg_replace('/[^a-z0-9]/', '', $d));
            if ($clean === $dClean || str_contains($clean, $dClean) || str_contains($dClean, $clean)) {
                return $d;
            }
        }

        return ucwords(strtolower(trim($raw)));
    }

    /**
     * Normalisasi cerdas nama Desa/Kelurahan dari ketikan pengguna (Fuzzy Correction)
     */
    public static function normalizeVillage(?string $raw): ?string
    {
        if (empty($raw)) return null;
        $clean = strtolower(trim(preg_replace('/^(?:Desa|Kelurahan|Kel\.?)\s*/i', '', $raw)));
        $clean = preg_replace('/[^a-z0-9]/', '', $clean);

        // Typo mappings
        $typoMap = [
            'sinarjaya'   => 'Sirnajaya',
            'sirnjaya'    => 'Sirnajaya',
            'sirnajaya'   => 'Sirnajaya',
            'bantarkalong'=> 'Bantarkalong',
            'hegarmanah'  => 'Hegarmanah',
            'damaraja'    => 'Damarraja',
            'damarraja'   => 'Damarraja',
            'warungkiara' => 'Warungkiara',
            'tarisi'      => 'Tarisi',
            'ubrug'       => 'Ubrug',
            'kertamukti'  => 'Kertamukti',
            'sukaharja'   => 'Sukaharja',
            'cikembar'    => 'Cikembar',
            'cimanggu'    => 'Cimanggu',
            'bojongkembar'=> 'Bojongkembar',
            'bojongraharja'=>'Bojongraharja',
            'kertaraharja'=> 'Kertaraharja',
            'bantargadung'=> 'Bantargadung',
            'boyongsari'  => 'Boyongsari',
            'mangunjaya'  => 'Mangunjaya',
            'cibadak'     => 'Cibadak',
            'karangtengah'=> 'Karangtengah',
            'sukasirna'   => 'Sukasirna',
            'sekarwangi'  => 'Sekarwangi',
            'cimanggis'   => 'Cimanggis',
            'cicantayan'  => 'Cicantayan',
        ];

        if (isset($typoMap[$clean])) {
            return $typoMap[$clean];
        }

        // Cek dalam master villages
        foreach (self::getMasterDirectory() as $district) {
            foreach ($district['villages'] as $vKey => $vData) {
                $vClean = strtolower(preg_replace('/[^a-z0-9]/', '', $vKey));
                if ($clean === $vClean) {
                    return $vData['name'];
                }
            }
        }

        return ucwords(strtolower(trim($raw)));
    }

    /**
     * Dapatkan daftar Kecamatan untuk pilihan form
     */
    public static function getDistrictsList(): array
    {
        return array_keys(self::getMasterDirectory());
    }

    /**
     * Dapatkan daftar Desa/Kelurahan berdasarkan Kecamatan
     */
    public static function getVillagesByDistrict(string $districtName): array
    {
        $d = self::normalizeDistrict($districtName);
        $master = self::getMasterDirectory();
        if ($d && isset($master[$d])) {
            return array_keys($master[$d]['villages']);
        }
        return [];
    }

    /**
     * Dapatkan rekomendasi Kecamatan dan Desa berdasarkan Area yang dipilih
     */
    public static function getSuggestionsForArea(string $areaName): array
    {
        $cleanArea = strtoupper(trim($areaName));
        $primaryDistrict = 'Warungkiara';
        $villages = [];

        if (str_contains($cleanArea, 'BC 1') || str_contains($cleanArea, 'BC-1') || str_contains($cleanArea, 'SIRNAJAYA')) {
            $primaryDistrict = 'Warungkiara';
            $villages = ['Sirnajaya', 'Bantarkalong', 'Hegarmanah'];
        } elseif (str_contains($cleanArea, 'BC 2') || str_contains($cleanArea, 'BC-2') || str_contains($cleanArea, 'CIKEMBAR')) {
            $primaryDistrict = 'Cikembar';
            $villages = ['Cikembar', 'Cimanggu', 'Bojongkembar', 'Bojongraharja', 'Kertaraharja', 'Cibatu', 'Parakanlima', 'Cimanggis', 'Cicantayan'];
        } elseif (str_contains($cleanArea, 'BC 3') || str_contains($cleanArea, 'BC-3') || str_contains($cleanArea, 'WARUNGKIARA')) {
            $primaryDistrict = 'Warungkiara';
            $villages = ['Warungkiara', 'Tarisi', 'Ubrug', 'Damarraja', 'Kertamukti', 'Sukaharja', 'Bantargadung', 'Boyongsari', 'Mangunjaya'];
        } elseif (str_contains($cleanArea, 'BC 4') || str_contains($cleanArea, 'BC-4') || str_contains($cleanArea, 'CIBADAK')) {
            $primaryDistrict = 'Cibadak';
            $villages = ['Cibadak', 'Karangtengah', 'Sukasirna', 'Sekarwangi', 'Batununggal', 'Ciheulang Tonggoh', 'Pamuruyan', 'Parungkuda'];
        }

        return [
            'district' => $primaryDistrict,
            'villages' => $villages,
        ];
    }

    /**
     * Dapatkan pasangan Desa -> Kecamatan & Area untuk deteksi otomatis
     */
    public static function getVillageLookupMap(): array
    {
        $map = [];
        foreach (self::getMasterDirectory() as $distName => $distData) {
            foreach ($distData['villages'] as $vName => $vData) {
                $map[$vName] = [
                    'name' => $vName,
                    'district' => $distName,
                    'area' => $vData['area'],
                    'type' => $vData['type'],
                ];
            }
        }
        return $map;
    }

    /**
     * Bersihkan alamat existing di tabel customers agar bebas typo penulisan Kecamatan & Desa
     * Tanpa mengubah nomor rumah, RT/RW, atau jalan pelanggan!
     */
    public static function cleanDatabaseAddresses(): array
    {
        $customers = Customer::whereNotNull('address')->get(['id', 'customer_code', 'address', 'area_id', 'area']);
        $updatedCount = 0;

        foreach ($customers as $c) {
            $orig = $c->address;
            $newAddr = $orig;

            // 1. Perbaiki typo Kecamatan (misal: Kec. Warkir, Kec. cikemba, Kec warkir)
            $newAddr = preg_replace_callback('/\b(?:Kec\.?|Kecamatan)\s*([a-zA-Z0-9]+)/i', function($m) {
                $normalized = self::normalizeDistrict($m[1]);
                return "Kec. " . ($normalized ?: trim($m[1]));
            }, $newAddr);

            // 2. Perbaiki typo Kelurahan / Desa (misal: Kel. sinarjaya, Ds.sinarjaya, Desa damaraja)
            $newAddr = preg_replace_callback('/\b(?:Kel\.?|Kelurahan|Ds\.?|Desa)\s*([a-zA-Z0-9]+)/i', function($m) {
                $normalized = self::normalizeVillage($m[1]);
                return "Kel. " . ($normalized ?: trim($m[1]));
            }, $newAddr);

            // 3. Bersihkan sisa tanda hubung atau koma di akhir penulisan kecamatan
            $newAddr = preg_replace('/Kec\.\s*([a-zA-Z\s]+?)\s*-\s*(?=,|$)/i', 'Kec. $1', $newAddr);

            // 4. Bersihkan spasi ganda atau koma berlebih
            $newAddr = preg_replace('/\s*,\s*/', ', ', $newAddr);
            $newAddr = preg_replace('/\s+/', ' ', $newAddr);
            $newAddr = trim($newAddr, " ,\t\n\r\0\x0B-");

            if ($newAddr !== $orig) {
                $c->update(['address' => $newAddr]);
                $updatedCount++;
            }
        }

        // 4. Standarkan juga coverage_villages di tabel areas
        $areas = Area::all();
        foreach ($areas as $a) {
            $suggest = self::getSuggestionsForArea($a->name);
            if (!empty($suggest['villages'])) {
                $formattedVillages = implode(', ', $suggest['villages']);
                $a->update(['coverage_villages' => $formattedVillages]);
            }
        }

        return [
            'total_scanned' => $customers->count(),
            'updated_customers' => $updatedCount,
        ];
    }
}

