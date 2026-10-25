<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Area;
use App\Models\InternetPackage;
use App\Models\User;
use App\Models\Olt;
use App\Models\OltPon;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Ont;
use App\Models\Survey;
use App\Models\TechnicianSchedule;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\CustomerInstalledMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DataMigrationService
{
    /**
     * Parse flexible date (handles YYYY-MM-DD, DD/MM/YYYY, and Excel serial numbers like 43770)
     */
    public static function parseFlexibleDate($value, $fallback = null): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return $fallback;
        }

        // If numeric Excel serial date (e.g. 43770, 44089, 45363)
        // Range 10000..75000 covers 1927 to 2105
        if (is_numeric($value)) {
            $num = (float) $value;
            if ($num >= 10000 && $num <= 75000) {
                // Excel 1900 date system (leap year bug offset: 25569 days to 1970-01-01)
                $timestamp = ($num - 25569) * 86400;
                return gmdate('Y-m-d', (int) $timestamp);
            }
        }

        $str = trim((string) $value);

        // 1. DD/MM/YYYY or DD-MM-YYYY (Tanggal, Bulan, Tahun)
        if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})$/', $str, $matches)) {
            $d = (int) $matches[1];
            $m = (int) $matches[2];
            $y = (int) $matches[3];
            if (checkdate($m, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $m, $d);
            }
        }

        // 2. YYYY-MM-DD or YYYY/MM/DD (Tahun, Bulan, Tanggal)
        if (preg_match('/^(\d{4})[\/\.-](\d{1,2})[\/\.-](\d{1,2})$/', $str, $matches)) {
            $y = (int) $matches[1];
            $m = (int) $matches[2];
            $d = (int) $matches[3];
            if (checkdate($m, $d, $y)) {
                return sprintf('%04d-%02d-%02d', $y, $m, $d);
            }
        }

        // 3. Standard Carbon parse
        try {
            return \Carbon\Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Parse & sanitize geographic coordinates (auto-correcting missing/shifted decimal points)
     * e.g. 1067.08877 -> 106.708877, -7010714 -> -7.010714
     */
    public static function parseCoordinate($val, string $type = 'lat'): ?float
    {
        if ($val === null || $val === '' || $val === '-' || $val === '?' || $val === 'none' || $val === 'null') {
            return null;
        }

        $str = trim(str_replace([' ', "\t", "\r", "\n"], '', (string)$val));
        if ($str === '' || $str === '-' || $str === '0' || $str === '?') {
            return null;
        }

        // Replace comma with dot if comma is used as decimal separator (e.g. "106,708877")
        if (str_contains($str, ',') && !str_contains($str, '.')) {
            $str = str_replace(',', '.', $str);
        }

        if (!is_numeric($str)) {
            return null;
        }

        $num = (float) $str;
        if (abs($num) < 0.000001) {
            return null;
        }

        if ($type === 'lat') {
            // Latitude range: [-90, 90]
            $limit = 10;
            while (abs($num) > 90 && --$limit > 0) {
                $num /= 10;
            }
            if ($num < -90 || $num > 90) {
                return null;
            }
            return round($num, 8);
        } else {
            // Longitude range: [-180, 180]
            $limit = 10;
            while (abs($num) > 180 && --$limit > 0) {
                $num /= 10;
            }
            if ($num < -180 || $num > 180) {
                return null;
            }
            return round($num, 8);
        }
    }

    /**
     * Sanitize & standardize MAC address into XX:XX:XX:XX:XX:XX format.
     * Supports legacy strings with suffixes like E42D7DBB7A4F-5B(13) -> E4:2D:7D:BB:7A:4F
     */
    public static function sanitizeMacAddress(?string $val): ?string
    {
        if (empty($val) || $val === '-' || $val === '?' || $val === 'null' || $val === 'none') {
            return null;
        }
        $val = trim((string)$val);

        // 1. Check if contains standard colon/hyphen separated MAC: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX
        if (preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $val, $matches)) {
            return strtoupper(str_replace('-', ':', $matches[0]));
        }

        // 2. Check if contains Cisco dot notation: XXXX.XXXX.XXXX
        if (preg_match('/([0-9A-Fa-f]{4})\.([0-9A-Fa-f]{4})\.([0-9A-Fa-f]{4})/', $val, $matches)) {
            $hex = $matches[1] . $matches[2] . $matches[3];
            return strtoupper(implode(':', str_split($hex, 2)));
        }

        // 3. Check if starts with 12 consecutive hex characters (e.g. E42D7DBB7A4F or E42D7DBB7A4F-5B(13))
        if (preg_match('/^([0-9A-Fa-f]{12})/i', $val, $matches)) {
            return strtoupper(implode(':', str_split($matches[1], 2)));
        }

        // 4. Any 12 hex characters in the string
        $allHex = preg_replace('/[^0-9A-Fa-f]/', '', $val);
        if (strlen($allHex) >= 12) {
            return strtoupper(implode(':', str_split(substr($allHex, 0, 12), 2)));
        }

        // 5. Fallback: sanitize and limit to max 45 chars
        $clean = substr(preg_replace('/[^A-Za-z0-9:\-\._]/', '', $val), 0, 45);
        return !empty($clean) ? $clean : null;
    }

    /**
     * Sanitize & parse optical Rx power (dBm)
     */
    public static function parseRxPower($val, float $default = -20.0): float
    {
        if ($val === null || $val === '' || $val === '-' || $val === '?') {
            return $default;
        }
        $str = trim(str_replace(['dBm', 'dbm', ' '], '', (string)$val));
        $str = str_replace(',', '.', $str);
        if (!is_numeric($str)) {
            return $default;
        }
        $num = (float)$str;
        // Rx power is typically negative (-15 to -32 dBm). If entered as positive (e.g. 19.5), convert to -19.5
        if ($num > 0 && $num <= 40) {
            $num = -$num;
        }
        return round($num, 2);
    }

    /**
     * Normalisasi status pelanggan toleran istilah legacy/Indonesian ISP
     * Menghasilkan [ string $normalizedStatus, bool $isValid, ?string $label ]
     */
    public static function normalizeCustomerStatus(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return ['active', true, null];
        }

        $clean = strtolower(trim($raw));
        $clean = str_replace(['-', ' ', '/'], '_', $clean);

        // 1. Terminated / Stop Permanen / Putus / Cabut
        if (in_array($clean, [
            'stop_permanen', 'stop', 'berhenti', 'terminated', 'putus', 'cabut', 
            'cbp', 'non_aktif_permanen', 'nonaktif_permanen', 'off_permanen', 
            'berhenti_berlangganan', 'tutup', 'selesai', 'diberhentikan'
        ])) {
            return ['terminated', true, 'Stop Permanen (Terminated)'];
        }

        // 2. Suspended / Terisolir / Nonaktif Sementara
        if (in_array($clean, [
            'suspended', 'suspend', 'isolir', 'terisolir', 'nonaktif', 'non_aktif', 
            'mati', 'cuti', 'tunggak', 'menunggak', 'off', 'off_sementara', 
            'blokir', 'terblokir', 'isolir_sementara'
        ])) {
            return ['suspended', true, 'Terisolir (Suspended)'];
        }

        // 3. Active / Aktif
        if (in_array($clean, [
            'active', 'aktif', 'normal', 'lancar', 'connect', 'connected', 
            'on', 'hidup', 'berjalan', 'ok', 'yes'
        ])) {
            return ['active', true, null];
        }

        // 4. Installing / Pemasangan
        if (in_array($clean, [
            'installing', 'installed', 'pasang', 'pemasangan', 'instalasi', 
            'sedang_pasang', 'progres', 'progres_pasang', 'jadwal_pasang', 'antrian_pasang'
        ])) {
            return ['installing', true, 'Pemasangan (Installing)'];
        }

        // 5. Survey
        if (in_array($clean, [
            'survey', 'survei', 'cek_lokasi', 'jadwal_survey', 'proses_survey', 
            'antrian_survey', 'feasibility'
        ])) {
            return ['survey', true, 'Survey'];
        }

        // 6. Booking
        if (in_array($clean, [
            'booking', 'daftar', 'registrasi', 'baru', 'order', 
            'calon_pelanggan', 'draft', 'pending'
        ])) {
            return ['booking', true, 'Booking'];
        }

        // 7. Canceled / Batal
        if (in_array($clean, [
            'canceled', 'cancelled', 'cancel', 'batal', 'dibatalkan', 
            'reject', 'rejected', 'gagal', 'tidak_jadi'
        ])) {
            return ['canceled', true, 'Dibatalkan (Canceled)'];
        }

        return ['active', false, null];
    }

    /**
     * Resolve area ID from cache supporting tolerant matching (e.g. 'BC-3', 'BC 3', 'BC_3' -> 'bc3')
     */
    public static function resolveAreaIdFromCache(?string $name, array $areasCache, array $areasNormalizedCache = []): ?int
    {
        if (empty($name)) {
            return null;
        }
        $lower = strtolower(trim($name));
        if (isset($areasCache[$lower])) {
            return $areasCache[$lower];
        }
        $norm = preg_replace('/[^a-z0-9]/', '', $lower);
        if ($norm !== '' && isset($areasNormalizedCache[$norm])) {
            return $areasNormalizedCache[$norm];
        }
        return null;
    }

    /**
     * Find area model by flexible name (e.g. 'BC-3' matches 'BC 3')
     */
    public static function findAreaByFlexibleName(?string $name): ?Area
    {
        if (empty($name)) {
            return null;
        }
        $trimmed = trim($name);
        $direct = Area::whereRaw('LOWER(name) = ?', [strtolower($trimmed)])->first();
        if ($direct) {
            return $direct;
        }

        $norm = preg_replace('/[^a-z0-9]/', '', strtolower($trimmed));
        if ($norm === '') {
            return null;
        }

        $allAreas = Area::all();
        foreach ($allAreas as $area) {
            if (preg_replace('/[^a-z0-9]/', '', strtolower($area->name)) === $norm) {
                return $area;
            }
        }

        return null;
    }

    /**
     * Resolve ODP model flexibly:
     * - Handles multiple candidates split by / , | & atau or (e.g. "ODP V-BTK20 A / V-BTK 20 B")
     * - Normalizes spaces, dashes, punctuation (e.g. "ODP V-BTK20 A" matches "ODP V-BTK 20A")
     * - Handles prefix variations ("ODP " vs without "ODP ")
     * - Strips parenthetical notes (e.g. "ODP V-BTK 27 (Reseller Rohendi)")
     *
     * @return array [ ?Odp $odp, string $method, ?string $matchedCandidate, int $totalCandidates ]
     */
    public static function resolveOdpFlexible(?string $input, $odpsCache, array $odpsNormalizedCache = [], array $odpsStrippedNormCache = []): array
    {
        if (empty($input)) {
            return [null, 'none', null, 0];
        }

        $trimmedInput = trim((string) $input);
        if ($trimmedInput === '' || $trimmedInput === '-') {
            return [null, 'none', null, 0];
        }

        // Split candidates by / , | & atau or
        $candidates = preg_split('/\s*(\/|,|\||&|\batau\b|\bor\b)\s*/i', $trimmedInput);
        $totalCandidates = count($candidates);

        foreach ($candidates as $cand) {
            $cand = trim($cand);
            if ($cand === '' || $cand === '-') continue;

            $candLower = strtolower($cand);

            // 1. Direct lowercase match
            if (isset($odpsCache[$candLower])) {
                return [$odpsCache[$candLower], 'exact', $cand, $totalCandidates];
            }

            // 2. Normalized alphanumeric match (removes spaces, hyphens, underscores)
            $candNorm = preg_replace('/[^a-z0-9]/', '', $candLower);
            if ($candNorm !== '') {
                if (isset($odpsNormalizedCache[$candNorm])) {
                    return [$odpsNormalizedCache[$candNorm], 'norm', $cand, $totalCandidates];
                }
                if (isset($odpsStrippedNormCache[$candNorm])) {
                    return [$odpsStrippedNormCache[$candNorm], 'stripped_norm', $cand, $totalCandidates];
                }

                // 3. Try adding or removing 'odp' prefix
                if (str_starts_with($candNorm, 'odp')) {
                    $withoutOdp = substr($candNorm, 3);
                    if ($withoutOdp !== '') {
                        if (isset($odpsNormalizedCache[$withoutOdp])) {
                            return [$odpsNormalizedCache[$withoutOdp], 'without_odp', $cand, $totalCandidates];
                        }
                        if (isset($odpsStrippedNormCache[$withoutOdp])) {
                            return [$odpsStrippedNormCache[$withoutOdp], 'stripped_without_odp', $cand, $totalCandidates];
                        }
                    }
                } else {
                    $withOdp = 'odp' . $candNorm;
                    if (isset($odpsNormalizedCache[$withOdp])) {
                        return [$odpsNormalizedCache[$withOdp], 'with_odp', $cand, $totalCandidates];
                    }
                    if (isset($odpsStrippedNormCache[$withOdp])) {
                        return [$odpsStrippedNormCache[$withOdp], 'stripped_with_odp', $cand, $totalCandidates];
                    }
                }

                // 4. Prefix or substring matching if normalized is at least 4 chars
                if (strlen($candNorm) >= 4) {
                    foreach ($odpsNormalizedCache as $normKey => $odpObj) {
                        if (str_starts_with($normKey, $candNorm) || str_starts_with($candNorm, $normKey)) {
                            return [$odpObj, 'prefix_norm', $cand, $totalCandidates];
                        }
                    }
                    foreach ($odpsStrippedNormCache as $normKey => $odpObj) {
                        if (str_starts_with($normKey, $candNorm) || str_starts_with($candNorm, $normKey)) {
                            return [$odpObj, 'prefix_stripped_norm', $cand, $totalCandidates];
                        }
                    }
                }
            }
        }

        return [null, 'none', null, $totalCandidates];
    }

    /**
     * Cari atau otomatis buat ODP baru jika belum ada di master data saat proses migrasi
     */
    public static function getOrCreateOdp(
        string $rawOdpName,
        ?int $areaId = null,
        ?float $latitude = null,
        ?float $longitude = null,
        int $requiredPort = 1,
        array &$runtimeOdpsCache = []
    ): ?Odp {
        $rawOdpName = trim($rawOdpName);
        if ($rawOdpName === '' || $rawOdpName === '-') {
            return null;
        }

        // Ambil kandidat pertama jika dipisah slash, koma, dsb
        $candidates = preg_split('/\s*(\/|,|\||&|\batau\b|\bor\b)\s*/i', $rawOdpName);
        $firstCand = trim($candidates[0] ?? $rawOdpName);
        if (empty($firstCand) || $firstCand === '-') {
            $firstCand = $rawOdpName;
        }

        $odpLower = strtolower($firstCand);
        if (isset($runtimeOdpsCache[$odpLower])) {
            $odp = Odp::find($runtimeOdpsCache[$odpLower]);
            if ($odp) {
                if ($requiredPort > (int)$odp->total_ports) {
                    $newTotal = max($requiredPort, (int)$odp->total_ports);
                    $odp->update(['total_ports' => $newTotal]);
                    for ($p = 1; $p <= $newTotal; $p++) {
                        OdpPort::firstOrCreate(['odp_id' => $odp->id, 'port_number' => $p], ['status' => 'available']);
                    }
                }
                return $odp;
            }
        }

        // Cek di DB eksisting
        $odp = Odp::where('name', $firstCand)->first();
        if (!$odp) {
            $odp = Odp::whereRaw('LOWER(name) = ?', [$odpLower])->first();
        }

        if (!$odp) {
            // Selesaikan ODC & OLT induk
            $odc = null;
            if ($areaId) {
                $odc = Odc::where('area_id', $areaId)->first();
            }
            if (!$odc) {
                $odc = Odc::where('name', 'like', '%BTK%')->orWhere('name', 'like', '%BK%')->first();
            }
            if (!$odc) {
                $odc = Odc::first();
            }
            if (!$odc) {
                $olt = Olt::first() ?? Olt::create([
                    'name' => 'OLT-DEFAULT',
                    'ip_address' => '192.168.1.1',
                    'area_id' => $areaId,
                    'total_pon_ports' => 16,
                    'status' => 'active',
                ]);
                $odc = Odc::create([
                    'name' => 'ODC-DEFAULT',
                    'olt_id' => $olt->id,
                    'area_id' => $areaId,
                    'capacity' => 48,
                    'splitter_ratio' => '1:4',
                    'status' => 'active',
                ]);
            }

            $totalPorts = 8;
            if ($requiredPort > 16) {
                $totalPorts = 24;
            } elseif ($requiredPort > 8) {
                $totalPorts = 16;
            }

            $odp = Odp::create([
                'name' => $firstCand,
                'odc_id' => $odc ? $odc->id : null,
                'area_id' => $areaId ?: ($odc ? $odc->area_id : null),
                'total_ports' => $totalPorts,
                'used_ports' => 0,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'status' => 'active',
            ]);

            // Buat port fisik 1..total_ports
            for ($p = 1; $p <= (int)$odp->total_ports; $p++) {
                OdpPort::firstOrCreate(
                    ['odp_id' => $odp->id, 'port_number' => $p],
                    ['status' => 'available']
                );
            }
        } else {
            if ($requiredPort > (int)$odp->total_ports) {
                $odp->update(['total_ports' => $requiredPort]);
            }
            for ($p = 1; $p <= max((int)$odp->total_ports, $requiredPort); $p++) {
                OdpPort::firstOrCreate(
                    ['odp_id' => $odp->id, 'port_number' => $p],
                    ['status' => 'available']
                );
            }
        }

        $runtimeOdpsCache[$odpLower] = $odp->id;
        return $odp;
    }

    /**
     * Resolve Sales User ID with fuzzy matching, name typo tolerance, soundex/similar_text,
     * employee table fallback, and multi-field identification (email, phone, NIK, referral code).
     *
     * @return array [ ?int $userId, string $matchMethod, ?string $matchedUserName ]
     */
    public static function resolveSalesUser(
        ?string $salesRef,
        array $usersCache,              // [strtolower(email) => id]
        array $userNamesCache,          // [strtolower(name) => id]
        array $userPhonesCache,         // [digits => id]
        array $affiliateCodesCache,     // [code => id]
        array $employeesIakCache,       // [iak => empId]
        $allUsersList = [],             // collection/array of User models/arrays
        $allEmployeesList = []          // collection/array of Employee models/arrays
    ): array
    {
        if (empty($salesRef)) {
            return [null, 'none', null];
        }

        $trimmed = trim((string) $salesRef);
        if ($trimmed === '' || $trimmed === '-') {
            return [null, 'none', null];
        }

        $lowerRef = strtolower($trimmed);

        // 1. Direct Email Match
        if (isset($usersCache[$lowerRef])) {
            return [$usersCache[$lowerRef], 'email_exact', $trimmed];
        }

        // 2. Direct Name Match
        if (isset($userNamesCache[$lowerRef])) {
            return [$userNamesCache[$lowerRef], 'name_exact', $trimmed];
        }

        // 3. Referral / Affiliate Code Match
        if (!empty($affiliateCodesCache) && isset($affiliateCodesCache[$lowerRef])) {
            return [$affiliateCodesCache[$lowerRef], 'affiliate_code', $trimmed];
        }

        // 4. Stripped Titles/Parentheses (e.g. "Deden Kurnia (Teknisi)" -> "Deden Kurnia")
        $strippedName = strtolower(trim(preg_replace('/\s*[\(\[\-].*?[\)\]\-]?$/', '', $trimmed)));
        if (!empty($strippedName) && isset($userNamesCache[$strippedName])) {
            return [$userNamesCache[$strippedName], 'stripped_name', $strippedName];
        }

        // 5. Phone digits match
        $cleanDigits = preg_replace('/[^0-9]/', '', $trimmed);
        if (strlen($cleanDigits) >= 8 && !empty($userPhonesCache) && isset($userPhonesCache[$cleanDigits])) {
            return [$userPhonesCache[$cleanDigits], 'phone', $cleanDigits];
        }

        // 6. Employee NIK / IAK match
        if (!empty($employeesIakCache) && isset($employeesIakCache[$lowerRef])) {
            $empId = $employeesIakCache[$lowerRef];
            $emp = \App\Models\Employee::find($empId);
            if ($emp && $emp->email && isset($usersCache[strtolower(trim($emp->email))])) {
                return [$usersCache[strtolower(trim($emp->email))], 'employee_iak', $emp->name];
            }
        }

        // 7. Alphanumeric Normalized Name Match (e.g. "Hikmah Aprilianti" -> "hikmahaprilianti")
        $normInput = preg_replace('/[^a-z0-9]/', '', $lowerRef);
        if ($normInput !== '') {
            foreach ($userNamesCache as $nameKey => $uid) {
                if (preg_replace('/[^a-z0-9]/', '', $nameKey) === $normInput) {
                    return [$uid, 'name_normalized', $nameKey];
                }
            }
        }

        // 8. High Similarity / Levenshtein / Word Matching (handles "HIKMAH APRILIAYANTI" -> "Hikmah Aprilianti")
        $bestUid = null;
        $highestScore = 0;
        $matchedName = null;

        $inputWords = array_values(array_filter(explode(' ', preg_replace('/[^a-z0-9\s]/', '', $lowerRef))));

        foreach ($allUsersList as $u) {
            $uName = is_array($u) ? ($u['name'] ?? '') : ($u->name ?? '');
            $uId = is_array($u) ? ($u['id'] ?? null) : ($u->id ?? null);
            if (!$uId || !$uName) continue;

            $uNameLower = strtolower(trim($uName));

            // similar_text percentage
            similar_text($lowerRef, $uNameLower, $percent);
            if ($percent >= 80 && $percent > $highestScore) {
                $highestScore = $percent;
                $bestUid = $uId;
                $matchedName = $uName;
            }

            // Word-level matching
            $uWords = array_values(array_filter(explode(' ', preg_replace('/[^a-z0-9\s]/', '', $uNameLower))));
            if (!empty($inputWords) && !empty($uWords)) {
                // If first word matches exactly (e.g. "Hikmah" == "Hikmah")
                if ($inputWords[0] === $uWords[0]) {
                    if (count($inputWords) > 1 && count($uWords) > 1) {
                        $lev = levenshtein($inputWords[1], $uWords[1]);
                        if ($lev <= 3 && 90 > $highestScore) {
                            $highestScore = 90;
                            $bestUid = $uId;
                            $matchedName = $uName;
                        }
                    } elseif (75 > $highestScore) {
                        $highestScore = 75;
                        $bestUid = $uId;
                        $matchedName = $uName;
                    }
                }
            }
        }

        if ($bestUid && $highestScore >= 75) {
            return [$bestUid, "fuzzy_similarity_{$highestScore}", $matchedName];
        }

        // 9. Employee table check if not found in User list
        foreach ($allEmployeesList as $emp) {
            $empName = is_array($emp) ? ($emp['name'] ?? '') : ($emp->name ?? '');
            $empEmail = is_array($emp) ? ($emp['email'] ?? '') : ($emp->email ?? '');
            if (!$empName) continue;

            $empNameLower = strtolower(trim($empName));
            similar_text($lowerRef, $empNameLower, $percent);
            if ($percent >= 80) {
                $matchedEmail = strtolower(trim($empEmail));
                if ($matchedEmail && isset($usersCache[$matchedEmail])) {
                    return [$usersCache[$matchedEmail], "employee_fuzzy_{$percent}", $empName];
                }
            }
        }

        return [null, 'none', null];
    }

    /**
     * Master schema definition per category
     */
    public static function getCategoryDefinitions(): array
    {
        return [
            'customers' => [
                'title' => 'Data Pelanggan (Master 6 Tahap)',
                'description' => 'Template utama dan terlengkap untuk seluruh siklus pelanggan: Booking, Jadwal Survey, Hasil Survey, Jadwal Pasang, Laporan Instalasi & ONT, hingga Aktivasi Layanan.',
                'target_table' => 'customers',
                'id_column' => 'customer_code',
                'headers' => [
                    // ── 1. BOOKING & BIODATA ───────────────────────────────
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan (CUS-XXXXXX / Kosongkan untuk auto-generate)', 'required' => false, 'db' => 'customer_code'],
                    'nama_lengkap' => ['label' => 'Nama Lengkap Pelanggan', 'required' => true, 'db' => 'name'],
                    'nomor_wa' => ['label' => 'Nomor WhatsApp / HP', 'required' => true, 'db' => 'phone'],
                    'email' => ['label' => 'Alamat Email', 'required' => false, 'db' => 'email'],
                    'nama_area' => ['label' => 'Nama Area / Wilayah (Otomatis cocokkan/buat baru)', 'required' => true, 'db' => 'area'],
                    'alamat_jalan' => ['label' => 'Detail Alamat / Jalan', 'required' => true, 'db' => 'address_detail'],
                    'rt_rw' => ['label' => 'RT / RW (misal: 02/05)', 'required' => false, 'db' => 'rt_rw'],
                    'kelurahan_desa' => ['label' => 'Desa / Kelurahan', 'required' => false, 'db' => 'village'],
                    'kecamatan' => ['label' => 'Kecamatan', 'required' => false, 'db' => 'district'],
                    'latitude' => ['label' => 'Latitude Koordinat Lokasi', 'required' => false, 'db' => 'latitude'],
                    'longitude' => ['label' => 'Longitude Koordinat Lokasi', 'required' => false, 'db' => 'longitude'],
                    'username_sales' => ['label' => 'Sales / Karyawan Pendaftar (Nama / Email / No HP / ID)', 'required' => false, 'db' => 'sales_ref'],
                    'catatan' => ['label' => 'Catatan Tambahan / Booking', 'required' => false, 'db' => 'notes'],

                    // ── 2. JADWAL SURVEY ───────────────────────────────────
                    'tgl_jadwal_survey' => ['label' => 'Tanggal Jadwal Survey (DD/MM/YYYY)', 'required' => false, 'db' => 'survey_scheduled_date'],
                    'jam_jadwal_survey' => ['label' => 'Jam Jadwal Survey (HH:MM, misal 09:00)', 'required' => false, 'db' => 'survey_scheduled_time'],
                    'teknisi_survey' => ['label' => 'Teknisi / Surveyor (Nama / Email / No HP)', 'required' => false, 'db' => 'surveyor_id'],
                    'status_jadwal_survey' => ['label' => 'Status Jadwal Survey (scheduled / working / done / cancelled)', 'required' => false, 'db' => 'survey_schedule_status'],

                    // ── 3. HASIL LAPORAN SURVEY ────────────────────────────
                    'tgl_survey' => ['label' => 'Tanggal Realisasi Survey (DD/MM/YYYY)', 'required' => false, 'db' => 'survey_date'],
                    'nama_odp' => ['label' => 'Nama ODP Terpilih / Terpasang', 'required' => false, 'db' => 'odp_name'],
                    'nomor_port_odp' => ['label' => 'Nomor Port ODP (1..N)', 'required' => false, 'db' => 'port_number'],
                    'jarak_tarikan_meter' => ['label' => 'Jarak Tarikan Kabel (Meter, misal: 50 - Auto isi Jarak Survey & Material Kabel Frecon)', 'required' => false, 'db' => 'distance_meters'],
                    'kelayakan_survey' => ['label' => 'Kelayakan Survey (layak / feasible / tidak_layak / not_feasible)', 'required' => false, 'db' => 'feasibility'],
                    'catatan_survey' => ['label' => 'Catatan Hasil Laporan Survey', 'required' => false, 'db' => 'survey_notes'],

                    // ── 4. JADWAL PASANG ───────────────────────────────────
                    'tgl_jadwal_pasang' => ['label' => 'Tanggal Jadwal Pasang (DD/MM/YYYY)', 'required' => false, 'db' => 'installation_scheduled_date'],
                    'jam_jadwal_pasang' => ['label' => 'Jam Jadwal Pasang (HH:MM, misal 10:00)', 'required' => false, 'db' => 'installation_scheduled_time'],
                    'teknisi_pasang' => ['label' => 'Teknisi Instalasi Pasang (Nama / Email / No HP)', 'required' => false, 'db' => 'technician_id'],
                    'status_jadwal_pasang' => ['label' => 'Status Jadwal Pasang (scheduled / working / done / cancelled)', 'required' => false, 'db' => 'installation_schedule_status'],
                    'catatan_jadwal_pasang' => ['label' => 'Catatan Penugasan Pemasangan', 'required' => false, 'db' => 'installation_schedule_notes'],

                    // ── 5. LAPORAN INSTALASI & PERANGKAT ONT ───────────────
                    'sn_ont' => ['label' => 'Serial Number Modem ONT (ZTEG..., HWTC... - Auto masuk Data ONT & Alokasi Perangkat)', 'required' => false, 'db' => 'serial_number'],
                    'mac_ont' => ['label' => 'MAC Address Modem ONT (XX:XX:XX:XX:XX:XX)', 'required' => false, 'db' => 'mac_address'],
                    'merk_ont' => ['label' => 'Merk Modem (ZTE / Huawei / Fiberhome)', 'required' => false, 'db' => 'brand'],
                    'tipe_model' => ['label' => 'Tipe Model Modem (F609 / HG8546M, dll)', 'required' => false, 'db' => 'model'],
                    'redaman_rx' => ['label' => 'Redaman Optik Rx (dBm, misal: -20.50)', 'required' => false, 'db' => 'rx_power'],
                    'jam_mulai_pasang' => ['label' => 'Jam Pasang Mulai (HH:MM)', 'required' => false, 'db' => 'start_time'],
                    'jam_selesai_pasang' => ['label' => 'Jam Pasang Selesai (HH:MM)', 'required' => false, 'db' => 'end_time'],
                    'ip_login_ont' => ['label' => 'IP Address Login ONT (misal: 192.168.1.1 - Auto masuk Data ONT)', 'required' => false, 'db' => 'ip_login'],
                    'user_login_ont' => ['label' => 'Username Login ONT (default: admin - Auto masuk Data ONT)', 'required' => false, 'db' => 'login_user'],
                    'pass_login_ont' => ['label' => 'Password Login ONT (default: admin - Auto masuk Data ONT)', 'required' => false, 'db' => 'login_password'],
                    'free_hotspot' => ['label' => 'Hotspot Gratis ONT (ya / tidak)', 'required' => false, 'db' => 'free_hotspot'],
                    'user_hotspot' => ['label' => 'Username Hotspot ONT (Opsional)', 'required' => false, 'db' => 'hotspot_user'],
                    'pass_hotspot' => ['label' => 'Password Hotspot ONT (Opsional)', 'required' => false, 'db' => 'hotspot_password'],

                    // ── 6. AKTIVASI & BILLING LAYANAN ──────────────────────
                    'nama_paket' => ['label' => 'Nama Paket Internet (Master Paket)', 'required' => false, 'db' => 'package_name'],
                    'tarif_bulanan' => ['label' => 'Tarif Bulanan (Rp)', 'required' => true, 'db' => 'base_amount'],
                    'biaya_pasang' => ['label' => 'Biaya Pasang Baru / PSB (Rp)', 'required' => false, 'db' => 'installation_fee'],
                    'status_layanan' => ['label' => 'Status Layanan (berbayar / gratis / free_home)', 'required' => false, 'db' => 'service_status'],
                    'jenis_billing' => ['label' => 'Jenis Billing (prabayar / pascabayar)', 'required' => false, 'db' => 'billing_type'],
                    'status_pelanggan' => ['label' => 'Status Pelanggan (active / booking / survey / installing / suspended / terminated)', 'required' => true, 'db' => 'status'],
                    'tgl_registrasi' => ['label' => 'Tanggal Registrasi (DD/MM/YYYY)', 'required' => false, 'db' => 'registration_date'],
                    'tgl_aktivasi' => ['label' => 'Tanggal Aktivasi Layanan (DD/MM/YYYY)', 'required' => false, 'db' => 'activation_date'],
                    'username_pppoe' => ['label' => 'Username Akun PPPoE (Auto masuk Data ONT & Pelanggan)', 'required' => false, 'db' => 'pppoe_user'],
                    'password_pppoe' => ['label' => 'Password Akun PPPoE (Auto masuk Data ONT & Pelanggan)', 'required' => false, 'db' => 'pppoe_password'],
                    'vlan_id' => ['label' => 'VLAN ID PPPoE (Opsional)', 'required' => false, 'db' => 'vlan_id'],
                    'mode_akses' => ['label' => 'Mode Akses (PPPOE / STATIC / DHCP / VOUCHER / BRIDGE)', 'required' => false, 'db' => 'access_mode'],
                ],
                'sample' => [
                    [
                        // Sample 1: Pelanggan Aktif Berbayar (Siklus Lengkap 6 Tahap)
                        'kode_pelanggan' => 'CUS-001001',
                        'nama_lengkap' => 'Ahmad Subardjo',
                        'nomor_wa' => '081234567890',
                        'email' => 'ahmad@example.com',
                        'nama_area' => 'Area Timur',
                        'alamat_jalan' => 'Jl. Mawar No. 12',
                        'rt_rw' => '02/05',
                        'kelurahan_desa' => 'Sukasari',
                        'kecamatan' => 'Cimahi',
                        'latitude' => '-6.891234',
                        'longitude' => '107.567890',
                        'username_sales' => 'Deden Kurnia',
                        'catatan' => 'Pelanggan Aktif Berbayar (Home 20 Mbps)',
                        'tgl_jadwal_survey' => '08/01/2026',
                        'jam_jadwal_survey' => '09:00',
                        'teknisi_survey' => 'Dasep Juliandi',
                        'status_jadwal_survey' => 'done',
                        'tgl_survey' => '08/01/2026',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'nomor_port_odp' => 1,
                        'jarak_tarikan_meter' => 45,
                        'kelayakan_survey' => 'layak',
                        'catatan_survey' => 'Jalur tiang PLN aman dan redaman bagus',
                        'tgl_jadwal_pasang' => '10/01/2026',
                        'jam_jadwal_pasang' => '10:00',
                        'teknisi_pasang' => 'Dasep Juliandi',
                        'status_jadwal_pasang' => 'done',
                        'catatan_jadwal_pasang' => 'Pemasangan rapi dan ONT normal',
                        'sn_ont' => 'ZTEG98765432',
                        'mac_ont' => 'CC:2D:21:4A:88:01',
                        'merk_ont' => 'ZTE',
                        'tipe_model' => 'F609',
                        'redaman_rx' => -20.50,
                        'jam_mulai_pasang' => '10:00',
                        'jam_selesai_pasang' => '11:30',
                        'ip_login_ont' => '192.168.1.1',
                        'user_login_ont' => 'admin',
                        'pass_login_ont' => 'admin',
                        'free_hotspot' => 'tidak',
                        'user_hotspot' => '',
                        'pass_hotspot' => '',
                        'nama_paket' => 'Home 20 Mbps',
                        'tarif_bulanan' => 175000,
                        'biaya_pasang' => 0,
                        'status_layanan' => 'berbayar',
                        'jenis_billing' => 'prabayar',
                        'status_pelanggan' => 'active',
                        'tgl_registrasi' => '08/01/2026',
                        'tgl_aktivasi' => '10/01/2026',
                        'username_pppoe' => 'ahmad_subardjo',
                        'password_pppoe' => 'subardjo123',
                        'vlan_id' => '100',
                        'mode_akses' => 'PPPOE',
                    ],
                    [
                        // Sample 2: Pelanggan Aktif Free Home (Voucher Rumahan ONT)
                        'kode_pelanggan' => 'CUS-001002',
                        'nama_lengkap' => 'Siti Nurhaliza',
                        'nomor_wa' => '085811223344',
                        'email' => '',
                        'nama_area' => 'Area Timur',
                        'alamat_jalan' => 'Jl. Melati No. 8',
                        'rt_rw' => '01/02',
                        'kelurahan_desa' => 'Sukasari',
                        'kecamatan' => 'Cimahi',
                        'latitude' => '-6.892300',
                        'longitude' => '107.568100',
                        'username_sales' => 'Deden Kurnia',
                        'catatan' => 'Pelanggan Aktif Free Home (Voucher Rumahan ONT)',
                        'tgl_jadwal_survey' => '14/01/2026',
                        'jam_jadwal_survey' => '13:00',
                        'teknisi_survey' => 'Dasep Juliandi',
                        'status_jadwal_survey' => 'done',
                        'tgl_survey' => '14/01/2026',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'nomor_port_odp' => 2,
                        'jarak_tarikan_meter' => 60,
                        'kelayakan_survey' => 'layak',
                        'catatan_survey' => 'Dekat dengan ODP',
                        'tgl_jadwal_pasang' => '15/01/2026',
                        'jam_jadwal_pasang' => '09:00',
                        'teknisi_pasang' => 'Dasep Juliandi',
                        'status_jadwal_pasang' => 'done',
                        'catatan_jadwal_pasang' => 'Hotspot voucher aktif',
                        'sn_ont' => 'HWTC11223344',
                        'mac_ont' => 'CC:2D:21:4A:88:02',
                        'merk_ont' => 'Huawei',
                        'tipe_model' => 'HG8546M',
                        'redaman_rx' => -20.10,
                        'jam_mulai_pasang' => '09:00',
                        'jam_selesai_pasang' => '10:15',
                        'ip_login_ont' => '192.168.1.1',
                        'user_login_ont' => 'admin',
                        'pass_login_ont' => 'admin',
                        'free_hotspot' => 'ya',
                        'user_hotspot' => 'hotspot_siti',
                        'pass_hotspot' => 'siti123',
                        'nama_paket' => '',
                        'tarif_bulanan' => 0,
                        'biaya_pasang' => 0,
                        'status_layanan' => 'free_home',
                        'jenis_billing' => 'prabayar',
                        'status_pelanggan' => 'active',
                        'tgl_registrasi' => '14/01/2026',
                        'tgl_aktivasi' => '15/01/2026',
                        'username_pppoe' => '',
                        'password_pppoe' => '',
                        'vlan_id' => '',
                        'mode_akses' => 'VOUCHER',
                    ],
                    [
                        // Sample 3: Pelanggan Tahap Penugasan Pasang (Installing - Menunggu Pasang)
                        'kode_pelanggan' => 'CUS-001003',
                        'nama_lengkap' => 'Bambang Irawan',
                        'nomor_wa' => '085712345678',
                        'email' => '',
                        'nama_area' => 'Area Barat',
                        'alamat_jalan' => 'Jl. Anggrek No. 45',
                        'rt_rw' => '01/03',
                        'kelurahan_desa' => 'Bojongsoang',
                        'kecamatan' => 'Bojongsoang',
                        'latitude' => '-6.912345',
                        'longitude' => '107.612345',
                        'username_sales' => 'Deden Kurnia',
                        'catatan' => 'Survey Selesai, Menunggu Jadwal Pasang',
                        'tgl_jadwal_survey' => '18/01/2026',
                        'jam_jadwal_survey' => '10:00',
                        'teknisi_survey' => 'Dasep Juliandi',
                        'status_jadwal_survey' => 'done',
                        'tgl_survey' => '18/01/2026',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'nomor_port_odp' => 3,
                        'jarak_tarikan_meter' => 75,
                        'kelayakan_survey' => 'layak',
                        'catatan_survey' => 'Jalur aman, tarikan kabel 75 meter',
                        'tgl_jadwal_pasang' => '22/01/2026',
                        'jam_jadwal_pasang' => '10:00',
                        'teknisi_pasang' => 'Dasep Juliandi',
                        'status_jadwal_pasang' => 'scheduled',
                        'catatan_jadwal_pasang' => 'Siapkan kabel dropcore 100m',
                        'sn_ont' => '',
                        'mac_ont' => '',
                        'merk_ont' => 'ZTE',
                        'tipe_model' => 'F609',
                        'redaman_rx' => '',
                        'jam_mulai_pasang' => '',
                        'jam_selesai_pasang' => '',
                        'ip_login_ont' => '',
                        'user_login_ont' => '',
                        'pass_login_ont' => '',
                        'free_hotspot' => 'tidak',
                        'user_hotspot' => '',
                        'pass_hotspot' => '',
                        'nama_paket' => 'Home 30 Mbps',
                        'tarif_bulanan' => 220000,
                        'biaya_pasang' => 50000,
                        'status_layanan' => 'berbayar',
                        'jenis_billing' => 'prabayar',
                        'status_pelanggan' => 'installing',
                        'tgl_registrasi' => '18/01/2026',
                        'tgl_aktivasi' => '',
                        'username_pppoe' => 'bambang_irawan',
                        'password_pppoe' => 'irawan30',
                        'vlan_id' => '100',
                        'mode_akses' => 'PPPOE',
                    ],
                    [
                        // Sample 4: Calon Pelanggan Baru Tahap Booking / Jadwal Survey
                        'kode_pelanggan' => 'CUS-001004',
                        'nama_lengkap' => 'Rina Wijayanti',
                        'nomor_wa' => '081399887766',
                        'email' => '',
                        'nama_area' => 'Area Barat',
                        'alamat_jalan' => 'Jl. Kenanga No. 17',
                        'rt_rw' => '03/01',
                        'kelurahan_desa' => 'Bojongsoang',
                        'kecamatan' => 'Bojongsoang',
                        'latitude' => '-6.914500',
                        'longitude' => '107.615200',
                        'username_sales' => 'Deden Kurnia',
                        'catatan' => 'Calon Pelanggan Baru Tahap Booking & Jadwal Survey',
                        'tgl_jadwal_survey' => '25/01/2026',
                        'jam_jadwal_survey' => '14:00',
                        'teknisi_survey' => 'Dasep Juliandi',
                        'status_jadwal_survey' => 'scheduled',
                        'tgl_survey' => '',
                        'nama_odp' => '',
                        'nomor_port_odp' => '',
                        'jarak_tarikan_meter' => '',
                        'kelayakan_survey' => '',
                        'catatan_survey' => '',
                        'tgl_jadwal_pasang' => '',
                        'jam_jadwal_pasang' => '',
                        'teknisi_pasang' => '',
                        'status_jadwal_pasang' => '',
                        'catatan_jadwal_pasang' => '',
                        'sn_ont' => '',
                        'mac_ont' => '',
                        'merk_ont' => '',
                        'tipe_model' => '',
                        'redaman_rx' => '',
                        'jam_mulai_pasang' => '',
                        'jam_selesai_pasang' => '',
                        'ip_login_ont' => '',
                        'user_login_ont' => '',
                        'pass_login_ont' => '',
                        'free_hotspot' => 'tidak',
                        'user_hotspot' => '',
                        'pass_hotspot' => '',
                        'nama_paket' => 'Home 20 Mbps',
                        'tarif_bulanan' => 175000,
                        'biaya_pasang' => 0,
                        'status_layanan' => 'berbayar',
                        'jenis_billing' => 'prabayar',
                        'status_pelanggan' => 'booking',
                        'tgl_registrasi' => '24/01/2026',
                        'tgl_aktivasi' => '',
                        'username_pppoe' => '',
                        'password_pppoe' => '',
                        'vlan_id' => '',
                        'mode_akses' => 'PPPOE',
                    ],
                ]
            ],

            'network' => [
                'title' => 'Data Jaringan (OLT, ODC, ODP, Port)',
                'description' => 'Migrasi struktur topologi jaringan FTTH: OLT, ODC, ODP, dan Port ke Pelanggan.',
                'target_table' => 'odps',
                'id_column' => 'nama_odp',
                'headers' => [
                    'nama_olt' => ['label' => 'Nama OLT / Server Induk', 'required' => true, 'db' => 'olt_name'],
                    'ip_olt' => ['label' => 'IP Address OLT', 'required' => false, 'db' => 'olt_ip'],
                    'pon_port_olt' => ['label' => 'Nomor PON Port OLT (1..16)', 'required' => false, 'db' => 'pon_port'],
                    'nama_odc' => ['label' => 'Nama ODC', 'required' => true, 'db' => 'odc_name'],
                    'splitter_odc' => ['label' => 'Splitter Ratio ODC (misal 1:4)', 'required' => false, 'db' => 'splitter_ratio'],
                    'nama_odp' => ['label' => 'Nama ODP', 'required' => true, 'db' => 'odp_name'],
                    'total_port_odp' => ['label' => 'Total Port ODP (8 / 16)', 'required' => true, 'db' => 'total_ports'],
                    'nomor_port' => ['label' => 'Nomor Port Fisik (1..N, Opsional - bisa diisi saat instalasi)', 'required' => false, 'db' => 'port_number'],
                    'status_port' => ['label' => 'Status Port (available/used/reserved/fault/stop)', 'required' => false, 'db' => 'port_status'],
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan (Jika Terhubung)', 'required' => false, 'db' => 'customer_code'],
                    'sn_ont' => ['label' => 'Serial Number ONT', 'required' => false, 'db' => 'serial_number'],
                    'mac_ont' => ['label' => 'MAC Address ONT', 'required' => false, 'db' => 'mac_address'],
                    'vlan_id' => ['label' => 'VLAN ID PPPoE', 'required' => false, 'db' => 'vlan_id'],
                    'nama_area' => ['label' => 'Nama Area Wilayah ODP', 'required' => true, 'db' => 'area_name'],
                    'latitude' => ['label' => 'Latitude ODP', 'required' => false, 'db' => 'latitude'],
                    'longitude' => ['label' => 'Longitude ODP', 'required' => false, 'db' => 'longitude'],
                    'foto_odp' => ['label' => 'Nama Berkas / URL Foto Fisik ODP (Opsional)', 'required' => false, 'db' => 'photo'],
                ],
                'sample' => [
                    [
                        'nama_olt' => 'HIOSO HA7308C',
                        'ip_olt' => '10.10.10.1',
                        'pon_port_olt' => 1,
                        'nama_odc' => 'ODC-SNJY',
                        'splitter_odc' => '1:8',
                        'nama_odp' => 'ODP-CLD26',
                        'total_port_odp' => 8,
                        'nomor_port' => 1,
                        'status_port' => 'used',
                        'kode_pelanggan' => 'CUS-001001',
                        'sn_ont' => 'HWTC12345678',
                        'mac_ont' => 'CC:2D:21:4A:88:01',
                        'vlan_id' => '100',
                        'nama_area' => 'Area Timur',
                        'latitude' => '-6.892000',
                        'longitude' => '107.568000',
                    ],
                    [
                        'nama_olt' => 'HIOSO HA7308C',
                        'ip_olt' => '10.10.10.1',
                        'pon_port_olt' => 1,
                        'nama_odc' => 'ODC-SNJY',
                        'splitter_odc' => '1:8',
                        'nama_odp' => 'ODP-CLD26',
                        'total_port_odp' => 8,
                        'nomor_port' => 2,
                        'status_port' => 'used',
                        'kode_pelanggan' => 'CUS-001002',
                        'sn_ont' => 'HWTC87654321',
                        'mac_ont' => 'CC:2D:21:4A:88:02',
                        'vlan_id' => '100',
                        'nama_area' => 'Area Timur',
                        'latitude' => '-6.892000',
                        'longitude' => '107.568000',
                    ],
                    [
                        'nama_olt' => 'HIOSO HA7308C',
                        'ip_olt' => '10.10.10.1',
                        'pon_port_olt' => 1,
                        'nama_odc' => 'ODC-SNJY',
                        'splitter_odc' => '1:8',
                        'nama_odp' => 'ODP-CLD27',
                        'total_port_odp' => 16,
                        'nomor_port' => 1,
                        'status_port' => 'available',
                        'kode_pelanggan' => '',
                        'sn_ont' => '',
                        'mac_ont' => '',
                        'vlan_id' => '',
                        'nama_area' => 'Area Timur',
                        'latitude' => '-6.893100',
                        'longitude' => '107.569200',
                    ],
                    [
                        'nama_olt' => 'HIOSO HA7308C',
                        'ip_olt' => '10.10.10.1',
                        'pon_port_olt' => 1,
                        'nama_odc' => 'ODC-BTG',
                        'splitter_odc' => '1:4',
                        'nama_odp' => 'ODP-BTG01',
                        'total_port_odp' => 8,
                        'nomor_port' => 1,
                        'status_port' => 'used',
                        'kode_pelanggan' => 'CUS-001010',
                        'sn_ont' => 'ZTEG99998888',
                        'mac_ont' => 'A4:12:42:11:22:33',
                        'vlan_id' => '101',
                        'nama_area' => 'Area Barat',
                        'latitude' => '-6.885000',
                        'longitude' => '107.551000',
                    ],
                    [
                        'nama_olt' => 'HIOSO HA7308C',
                        'ip_olt' => '10.10.10.1',
                        'pon_port_olt' => 2,
                        'nama_odc' => 'ODC-BLK',
                        'splitter_odc' => '1:16',
                        'nama_odp' => 'ODP-BLK01',
                        'total_port_odp' => 8,
                        'nomor_port' => 1,
                        'status_port' => 'available',
                        'kode_pelanggan' => '',
                        'sn_ont' => '',
                        'mac_ont' => '',
                        'vlan_id' => '',
                        'nama_area' => 'Area Selatan',
                        'latitude' => '-6.910000',
                        'longitude' => '107.580000',
                    ],
                ]
            ],

            'booking_survey' => [
                'title' => 'Data Booking & Survey',
                'description' => 'Migrasi data calon pelanggan, hasil survey kelayakan ODP, dan petugas surveyor.',
                'target_table' => 'surveys',
                'id_column' => 'kode_pelanggan',
                'headers' => [
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan', 'required' => true, 'db' => 'customer_code'],
                    'email_petugas_survey' => ['label' => 'Email / Username Petugas Survey', 'required' => true, 'db' => 'surveyor_email'],
                    'nama_odp' => ['label' => 'Nama ODP Terpilih', 'required' => false, 'db' => 'odp_name'],
                    'nomor_port' => ['label' => 'Nomor Port ODP', 'required' => false, 'db' => 'port_number'],
                    'jarak_meter' => ['label' => 'Jarak Penarikan Kabel (meter)', 'required' => false, 'db' => 'distance_meters'],
                    'hasil_kelayakan' => ['label' => 'Hasil Kelayakan (feasible/not_feasible)', 'required' => true, 'db' => 'feasibility'],
                    'redaman_sinyal' => ['label' => 'Redaman Sinyal (dBm)', 'required' => false, 'db' => 'signal_loss'],
                    'tgl_survey' => ['label' => 'Tanggal Survey (Tanggal/Bulan/Tahun: DD/MM/YYYY)', 'required' => false, 'db' => 'survey_date'],
                    'catatan_survey' => ['label' => 'Catatan Hasil Survey', 'required' => false, 'db' => 'notes'],
                ],
                'sample' => [
                    [
                        'kode_pelanggan' => 'CUS-001001',
                        'email_petugas_survey' => 'teknisi1@example.com',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'nomor_port' => 1,
                        'jarak_meter' => 85,
                        'hasil_kelayakan' => 'feasible',
                        'redaman_sinyal' => -19.5,
                        'tgl_survey' => '11/01/2026',
                        'catatan_survey' => 'Jalur kabel aman lewat tiang PLN',
                    ]
                ]
            ],

            'installation_activation' => [
                'title' => 'Data Instalasi & Aktivasi Historis',
                'description' => 'Migrasi perangkat ONT, redaman, akun PPPoE, VLAN, dan riwayat aktivasi layanan.',
                'target_table' => 'onts',
                'id_column' => 'sn_ont',
                'headers' => [
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan', 'required' => true, 'db' => 'customer_code'],
                    'sn_ont' => ['label' => 'Serial Number Modem ONT', 'required' => true, 'db' => 'serial_number'],
                    'mac_address' => ['label' => 'MAC Address Modem', 'required' => false, 'db' => 'mac_address'],
                    'merk_ont' => ['label' => 'Merk Modem (ZTE/Huawei/dll)', 'required' => false, 'db' => 'brand'],
                    'tipe_model' => ['label' => 'Tipe Model Modem', 'required' => false, 'db' => 'model'],
                    'nama_odp' => ['label' => 'Nama ODP Terpasang', 'required' => true, 'db' => 'odp_name'],
                    'nomor_port_odp' => ['label' => 'Nomor Port ODP Terpasang (Opsional, diisi saat instalasi)', 'required' => false, 'db' => 'port_number'],
                    'redaman_rx' => ['label' => 'Redaman Optik Rx (dBm, misal: -21.50)', 'required' => true, 'db' => 'rx_power'],
                    'username_pppoe' => ['label' => 'Username PPPoE', 'required' => false, 'db' => 'pppoe_user'],
                    'password_pppoe' => ['label' => 'Password PPPoE', 'required' => false, 'db' => 'pppoe_password'],
                    'vlan_id' => ['label' => 'VLAN ID PPPoE', 'required' => false, 'db' => 'vlan_id'],
                    'mode_akses' => ['label' => 'Mode Akses (PPPOE/IPOE/BRIDGE)', 'required' => false, 'db' => 'access_mode'],
                    'tgl_aktivasi' => ['label' => 'Tanggal Aktivasi (Tanggal/Bulan/Tahun: DD/MM/YYYY)', 'required' => true, 'db' => 'activation_date'],
                    'lulus_audit_qc' => ['label' => 'Status Audit QC (1 = Lulus, 0 = Belum)', 'required' => false, 'db' => 'is_audited'],
                    'jam_pasang_mulai' => ['label' => 'Jam Pasang Mulai (HH:MM)', 'required' => false, 'db' => 'start_time'],
                    'jam_pasang_selesai' => ['label' => 'Jam Pasang Selesai (HH:MM)', 'required' => false, 'db' => 'end_time'],
                    'foto_redaman' => ['label' => 'Nama Berkas / URL Foto Redaman (Opsional)', 'required' => false, 'db' => 'photo_redaman'],
                    'foto_odp' => ['label' => 'Nama Berkas / URL Foto ODP (Opsional)', 'required' => false, 'db' => 'photo_odp'],
                    'foto_modem' => ['label' => 'Nama Berkas / URL Foto Modem ONT (Opsional)', 'required' => false, 'db' => 'photo_ont'],
                    'foto_pelanggan' => ['label' => 'Nama Berkas / URL Foto Pelanggan / Rumah (Opsional)', 'required' => false, 'db' => 'photo_customer'],
                    'foto_instalasi' => ['label' => 'Nama Berkas / URL Foto Jalur Instalasi (Opsional)', 'required' => false, 'db' => 'photo_installation'],
                ],
                'sample' => [
                    [
                        'kode_pelanggan' => 'CUS-001001',
                        'sn_ont' => 'ZTEG98765432',
                        'mac_address' => 'CC:2D:21:4A:88:01',
                        'merk_ont' => 'ZTE',
                        'tipe_model' => 'F609',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'nomor_port_odp' => 1,
                        'redaman_rx' => -21.40,
                        'username_pppoe' => 'ahmad_subardjo',
                        'password_pppoe' => 'subardjo123',
                        'vlan_id' => '100',
                        'mode_akses' => 'PPPOE',
                        'tgl_aktivasi' => '12/01/2026',
                        'lulus_audit_qc' => 1,
                        'jam_pasang_mulai' => '09:00',
                        'jam_pasang_selesai' => '11:30',
                    ]
                ]
            ],

            'invoices' => [
                'title' => 'Data Tagihan / Invoice Historis',
                'description' => 'Migrasi invoice tagihan bulanan pelanggan yang lunas maupun belum lunas.',
                'target_table' => 'invoices',
                'id_column' => 'nomor_invoice',
                'headers' => [
                    'nomor_invoice' => ['label' => 'Nomor Invoice (INV-YYYYMM-XXXX)', 'required' => false, 'db' => 'invoice_number'],
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan Terkait', 'required' => true, 'db' => 'customer_code'],
                    'bulan_periode' => ['label' => 'Bulan Periode (1 s/d 12)', 'required' => true, 'db' => 'period_month'],
                    'tahun_periode' => ['label' => 'Tahun Periode (misal: 2026)', 'required' => true, 'db' => 'period_year'],
                    'total_tagihan' => ['label' => 'Total Nominal Tagihan (Rp)', 'required' => true, 'db' => 'amount'],
                    'tgl_terbit' => ['label' => 'Tanggal Invoice Terbit (Tanggal/Bulan/Tahun: DD/MM/YYYY)', 'required' => true, 'db' => 'issued_date'],
                    'tgl_jatuh_tempo' => ['label' => 'Tanggal Jatuh Tempo (Tanggal/Bulan/Tahun: DD/MM/YYYY)', 'required' => true, 'db' => 'due_date'],
                    'status_invoice' => ['label' => 'Status Tagihan (unpaid/paid/partial/cancelled)', 'required' => true, 'db' => 'status'],
                    'jenis_tagihan' => ['label' => 'Jenis Tagihan (prabayar/pascabayar/prorata)', 'required' => false, 'db' => 'billing_type'],
                    'catatan_invoice' => ['label' => 'Catatan / Keterangan Tagihan', 'required' => false, 'db' => 'notes'],
                ],
                'sample' => [
                    [
                        'nomor_invoice' => 'INV-202601-0012',
                        'kode_pelanggan' => 'CUS-001001',
                        'bulan_periode' => 1,
                        'tahun_periode' => 2026,
                        'total_tagihan' => 175000,
                        'tgl_terbit' => '12/01/2026',
                        'tgl_jatuh_tempo' => '20/01/2026',
                        'status_invoice' => 'paid',
                        'jenis_tagihan' => 'prabayar',
                        'catatan_invoice' => 'Tagihan Periode Januari 2026',
                    ]
                ]
            ],

            'payments' => [
                'title' => 'Data Pembayaran Historis',
                'description' => 'Migrasi transaksi pembayaran pelanggan untuk rekonsiliasi invoice.',
                'target_table' => 'payments',
                'id_column' => 'nomor_referensi',
                'headers' => [
                    'nomor_invoice' => ['label' => 'Nomor Invoice yang Dibayar', 'required' => true, 'db' => 'invoice_number'],
                    'nominal_bayar' => ['label' => 'Nominal Pembayaran (Rp)', 'required' => true, 'db' => 'amount'],
                    'tgl_bayar' => ['label' => 'Tanggal Pembayaran (Tanggal/Bulan/Tahun: DD/MM/YYYY)', 'required' => true, 'db' => 'payment_date'],
                    'metode_bayar' => ['label' => 'Metode Bayar (cash/transfer/ewallet/auto_debit)', 'required' => true, 'db' => 'payment_method'],
                    'nomor_referensi' => ['label' => 'Nomor Bukti / Referensi Pembayaran', 'required' => false, 'db' => 'reference_number'],
                    'email_penerima' => ['label' => 'Email / Username Kasir Penerima', 'required' => false, 'db' => 'collector_email'],
                    'catatan' => ['label' => 'Catatan Pembayaran', 'required' => false, 'db' => 'notes'],
                ],
                'sample' => [
                    [
                        'nomor_invoice' => 'INV-202601-0012',
                        'nominal_bayar' => 175000,
                        'tgl_bayar' => '15/01/2026',
                        'metode_bayar' => 'transfer',
                        'nomor_referensi' => 'TRF-BCA-887192',
                        'email_penerima' => 'finance@example.com',
                        'catatan' => 'Lunas via Transfer BCA',
                    ]
                ]
            ],

            'employees' => [
                'title' => 'Data Karyawan & SDM',
                'description' => 'Migrasi data personalia, jabatan, area, tanggal bergabung (masa kerja), status kepegawaian, rekening bank/DANA/GoPay, dan akun login user.',
                'target_table' => 'employees',
                'id_column' => 'email',
                'headers' => [
                    'nama_lengkap' => ['label' => 'Nama Lengkap Karyawan', 'required' => true, 'db' => 'name'],
                    'nomor_iak' => ['label' => 'Nomor IAK / NIP Karyawan (Opsional)', 'required' => false, 'db' => 'iak_number'],
                    'posisi_jabatan' => ['label' => 'Posisi / Jabatan (Teknisi/CS/Sales/HRD/Manager/dll)', 'required' => false, 'db' => 'position'],
                    'email' => ['label' => 'Email Karyawan (Unik / Akun Login)', 'required' => true, 'db' => 'email'],
                    'nomor_telepon' => ['label' => 'Nomor HP / WhatsApp (Contoh: 085737412700)', 'required' => false, 'db' => 'phone'],
                    'nama_area' => ['label' => 'Nama Area Penempatan (Contoh: BC 1, BC 2, BC 3)', 'required' => false, 'db' => 'area_name'],
                    'tgl_bergabung' => ['label' => 'Tanggal Bergabung / Join Date (Tanggal/Bulan/Tahun: DD/MM/YYYY)', 'required' => false, 'db' => 'join_date'],
                    'tipe_karyawan' => ['label' => 'Tipe Kepegawaian (Tetap/Kontrak/Freelance/Magang)', 'required' => false, 'db' => 'employee_type'],
                    'gaji_pokok' => ['label' => 'Gaji Pokok (Rp, opsional)', 'required' => false, 'db' => 'base_salary'],
                    'metode_gaji' => ['label' => 'Metode Pembayaran (Transfer Bank/Cash/dll)', 'required' => false, 'db' => 'payment_method'],
                    'nama_bank' => ['label' => 'Nama Bank / E-Wallet (BCA/Mandiri/BRI/BNI/BSI/DANA/GoPay/dll)', 'required' => false, 'db' => 'bank_name'],
                    'nomor_rekening' => ['label' => 'Nomor Rekening / No. HP E-Wallet (DANA/GoPay)', 'required' => false, 'db' => 'bank_account_number'],
                    'nama_pemilik_rekening' => ['label' => 'Nama Pemilik Rekening / Akun (Atas Nama / a.n)', 'required' => false, 'db' => 'bank_account_holder'],
                    'alamat' => ['label' => 'Alamat Domisili Karyawan', 'required' => false, 'db' => 'address'],
                    'buat_akun_login' => ['label' => 'Buat Akun Login User? (ya/tidak - default: ya)', 'required' => false, 'db' => 'create_user'],
                    'password_login' => ['label' => 'Password Login Awal (Kosongkan = password123)', 'required' => false, 'db' => 'initial_password'],
                ],
                'sample' => [
                    [
                        'nama_lengkap' => 'Budi Santoso',
                        'nomor_iak' => 'EMP-2024-001',
                        'posisi_jabatan' => 'Teknisi',
                        'email' => 'budi.santoso@example.com',
                        'nomor_telepon' => '085737412700',
                        'nama_area' => 'BC 1',
                        'tgl_bergabung' => '15/03/2023',
                        'tipe_karyawan' => 'Tetap',
                        'gaji_pokok' => 3500000,
                        'metode_gaji' => 'Transfer Bank',
                        'nama_bank' => 'BCA',
                        'nomor_rekening' => '1234567890',
                        'nama_pemilik_rekening' => 'Budi Santoso',
                        'alamat' => 'Jl. Merdeka No. 45, Bandung',
                        'buat_akun_login' => 'ya',
                        'password_login' => '',
                    ],
                    [
                        'nama_lengkap' => 'Siti Rahmawati',
                        'nomor_iak' => 'EMP-2025-002',
                        'posisi_jabatan' => 'CS',
                        'email' => 'siti.rahmawati@example.com',
                        'nomor_telepon' => '085603000044',
                        'nama_area' => 'BC 2',
                        'tgl_bergabung' => '01/06/2025',
                        'tipe_karyawan' => 'Kontrak',
                        'gaji_pokok' => 3000000,
                        'metode_gaji' => 'Transfer Bank',
                        'nama_bank' => 'DANA',
                        'nomor_rekening' => '085603000044',
                        'nama_pemilik_rekening' => 'Siti Rahmawati',
                        'alamat' => 'Jl. Kenanga No. 12, Bandung',
                        'buat_akun_login' => 'ya',
                        'password_login' => '',
                    ],
                    [
                        'nama_lengkap' => 'Ahmad Fauzi',
                        'nomor_iak' => 'EMP-2025-003',
                        'posisi_jabatan' => 'Sales',
                        'email' => 'ahmad.fauzi@example.com',
                        'nomor_telepon' => '088213360441',
                        'nama_area' => 'BC 3',
                        'tgl_bergabung' => '10/01/2025',
                        'tipe_karyawan' => 'Tetap',
                        'gaji_pokok' => 3200000,
                        'metode_gaji' => 'Transfer Bank',
                        'nama_bank' => 'GoPay',
                        'nomor_rekening' => '088213360441',
                        'nama_pemilik_rekening' => 'Ahmad Fauzi',
                        'alamat' => 'Jl. Asia Afrika No. 88, Bandung',
                        'buat_akun_login' => 'ya',
                        'password_login' => '',
                    ],
                ]
            ],
        ];
    }

    /**
     * Generate CSV content with UTF-8 BOM
     */
    public function generateTemplateCsv(string $category): string
    {
        $defs = self::getCategoryDefinitions();
        if (!isset($defs[$category])) {
            throw new \InvalidArgumentException("Kategori {$category} tidak valid.");
        }

        $def = $defs[$category];
        $headers = array_keys($def['headers']);
        
        $output = fopen('php://memory', 'r+');
        // Add UTF-8 BOM so Excel opens it with correct encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        // Add Excel separator directive so Windows Excel immediately splits columns by comma
        fwrite($output, "sep=,\r\n");

        // Write header row
        fputcsv($output, $headers, ',');

        // Write sample data rows
        foreach ($def['sample'] as $row) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = $row[$h] ?? '';
            }
            fputcsv($output, $line, ',');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Parse raw CSV string or uploaded file into associative array
     */
    public function parseCsv(string $content): array
    {
        // Strip BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        if (empty($lines)) return [];

        // Check if first line specifies Excel separator directive "sep=," or "sep=;"
        if (preg_match('/^sep=([;,|\t])/i', trim($lines[0]), $m)) {
            $delimiter = $m[1];
            array_shift($lines);
        } else {
            // Detect delimiter: check first line
            $firstLine = $lines[0] ?? '';
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        }

        $data = [];
        $headers = null;

        foreach ($lines as $lineIndex => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;

            $row = str_getcsv($trimmed, $delimiter);
            if (!$headers) {
                $headers = array_map(function ($h) {
                    return trim(strtolower(str_replace([' ', '-', '.'], '_', $h)));
                }, $row);
                continue;
            }

            if (count($row) === count($headers)) {
                $combined = array_combine($headers, array_map('trim', $row));
                $data[] = $combined;
            } elseif (count($row) > 1) {
                // Pad or slice
                $padded = array_pad($row, count($headers), '');
                $data[] = array_combine($headers, array_slice($padded, 0, count($headers)));
            }
        }

        return $data;
    }

    /**
     * Dry Run Validator
     */
    public function dryRun(string $category, array $rows, array $options = []): array
    {
        $defs = self::getCategoryDefinitions();
        if (!isset($defs[$category])) {
            throw new \InvalidArgumentException("Kategori {$category} tidak ditemukan.");
        }

        $results = [];
        $totalValid = 0;
        $totalWarnings = 0;
        $totalErrors = 0;

        // Preload caches for fast lookups
        $areasCache = Area::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower(trim($name)) => $id])->toArray();
        $areasNormalizedCache = [];
        foreach ($areasCache as $aName => $aId) {
            $norm = preg_replace('/[^a-z0-9]/', '', $aName);
            if ($norm !== '') {
                $areasNormalizedCache[$norm] = $aId;
            }
        }
        $packagesCache = InternetPackage::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id])->toArray();
        $usersCache = User::pluck('id', 'email')->mapWithKeys(fn($id, $email) => [strtolower($email) => $id])->toArray();
        $userNamesCache = User::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id])->toArray();
        $customersCache = Customer::pluck('id', 'customer_code')->toArray();
        $odpsCollection = Odp::with('area')->get();
        $odpsCache = $odpsCollection->keyBy(fn($o) => strtolower(trim($o->name)));
        $odpsNormalizedCache = [];
        $odpsStrippedNormCache = [];
        foreach ($odpsCollection as $odp) {
            $rawLower = strtolower(trim($odp->name));
            $norm = preg_replace('/[^a-z0-9]/', '', $rawLower);
            if ($norm !== '') {
                $odpsNormalizedCache[$norm] = $odp;
            }
            $stripped = trim(preg_replace('/\s*\([^)]*\)/', '', $rawLower));
            $strippedNorm = preg_replace('/[^a-z0-9]/', '', $stripped);
            if ($strippedNorm !== '') {
                $odpsStrippedNormCache[$strippedNorm] = $odp;
            }
        }
        $allUsersList = User::all();
        $allEmployeesList = Employee::all();
        $invoicesCache = Invoice::pluck('id', 'invoice_number')->toArray();
        $employeesCache = Employee::pluck('id', 'email')->mapWithKeys(fn($id, $email) => [strtolower($email) => $id])->toArray();
        $employeesIakCache = Employee::whereNotNull('iak_number')->pluck('id', 'iak_number')->mapWithKeys(fn($id, $iak) => [strtolower($iak) => $id])->toArray();

        // Phone caches for users and employees
        $userPhonesCache = [];
        foreach (User::whereNotNull('phone')->get(['id', 'phone']) as $u) {
            $digits = preg_replace('/[^0-9]/', '', (string)$u->phone);
            if ($digits !== '') {
                $userPhonesCache[$digits] = $u->id;
                if (str_starts_with($digits, '62')) {
                    $userPhonesCache['0' . substr($digits, 2)] = $u->id;
                } elseif (str_starts_with($digits, '0')) {
                    $userPhonesCache['62' . substr($digits, 1)] = $u->id;
                }
            }
        }
        foreach (Employee::whereNotNull('phone')->get(['id', 'email', 'phone']) as $emp) {
            $digits = preg_replace('/[^0-9]/', '', (string)$emp->phone);
            if ($digits !== '' && $emp->email && isset($usersCache[strtolower(trim($emp->email))])) {
                $uId = $usersCache[strtolower(trim($emp->email))];
                $userPhonesCache[$digits] = $uId;
                if (str_starts_with($digits, '62')) {
                    $userPhonesCache['0' . substr($digits, 2)] = $uId;
                } elseif (str_starts_with($digits, '0')) {
                    $userPhonesCache['62' . substr($digits, 1)] = $uId;
                }
            }
        }

        $affiliateCodesCache = [];
        try {
            $affiliateCodesCache = \App\Models\AffiliateProfile::pluck('user_id', 'referral_code')
                ->mapWithKeys(fn($uid, $code) => [strtolower(trim((string)$code)) => $uid])
                ->toArray();
        } catch (\Throwable $e) {}

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;
            $rowErrors = [];
            $rowWarnings = [];
            $rowInfos = [];
            $mapped = [];

            switch ($category) {
                case 'customers':
                    $this->validateCustomerRow(
                        $row, $rowErrors, $rowWarnings, $mapped,
                        $areasCache, $packagesCache, $usersCache, $userNamesCache, $customersCache,
                        $options, $areasNormalizedCache, $userPhonesCache, $affiliateCodesCache, $employeesIakCache,
                        $odpsCache, $odpsNormalizedCache, $odpsStrippedNormCache, $allUsersList, $allEmployeesList,
                        $rowInfos
                    );
                    break;
                case 'network':
                    $this->validateNetworkRow($row, $rowErrors, $rowWarnings, $mapped, $areasCache, $customersCache, $odpsCache, $options, $rowInfos);
                    break;
                case 'booking_survey':
                    $this->validateSurveyRow($row, $rowErrors, $rowWarnings, $mapped, $customersCache, $usersCache, $odpsCache, $odpsNormalizedCache, $odpsStrippedNormCache, $allUsersList, $allEmployeesList, $rowInfos);
                    break;
                case 'installation_activation':
                    $this->validateInstallationRow($row, $rowErrors, $rowWarnings, $mapped, $customersCache, $odpsCache, $odpsNormalizedCache, $odpsStrippedNormCache, $rowInfos);
                    break;
                case 'invoices':
                    $this->validateInvoiceRow($row, $rowErrors, $rowWarnings, $mapped, $customersCache, $invoicesCache, $rowInfos);
                    break;
                case 'payments':
                    $this->validatePaymentRow($row, $rowErrors, $rowWarnings, $mapped, $invoicesCache, $usersCache, $rowInfos);
                    break;
                case 'employees':
                    $this->validateEmployeeRow($row, $rowErrors, $rowWarnings, $mapped, $areasCache, $employeesCache, $employeesIakCache, $options, $areasNormalizedCache, $rowInfos);
                    break;
            }

            $status = count($rowErrors) > 0 ? 'error' : (count($rowWarnings) > 0 ? 'warning' : 'valid');
            if ($status === 'valid') $totalValid++;
            elseif ($status === 'warning') $totalWarnings++;
            else $totalErrors++;

            $results[] = [
                'row_index' => $rowNum,
                'status' => $status,
                'raw' => $row,
                'mapped' => $mapped,
                'errors' => $rowErrors,
                'warnings' => $rowWarnings,
                'infos' => $rowInfos,
            ];
        }

        return [
            'summary' => [
                'total' => count($rows),
                'valid' => $totalValid,
                'warnings' => $totalWarnings,
                'errors' => $totalErrors,
                'total_rows' => count($rows),
                'valid_count' => $totalValid,
                'warning_count' => $totalWarnings,
                'error_count' => $totalErrors,
            ],
            'rows' => $results,
        ];
    }

    /**
     * Execute Import Transaction
     */
    public function execute(string $category, array $rows, array $options = []): array
    {
        // 1. Dry run first to verify consistency
        $dryRun = $this->dryRun($category, $rows, $options);
        if ($dryRun['summary']['errors'] > 0 && empty($options['skip_errors'])) {
            return [
                'success' => false,
                'message' => "Impor dibatalkan: ditemukan {$dryRun['summary']['errors']} baris dengan kesalahan. Perbaiki data sebelum melanjutkan.",
                'summary' => $dryRun['summary'],
                'errors' => array_filter($dryRun['rows'], fn($r) => $r['status'] === 'error')
            ];
        }

        $duplicateMode = $options['duplicate_mode'] ?? 'skip'; // skip | update
        $autoCreateArea = $options['auto_create_area'] ?? true;
        $batchId = 'BATCH-' . strtoupper($category) . '-' . date('YmdHis');

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $logs = [];

        $affectedOdpIds = [];
        $runtimeAreasCache = [];
        $runtimeSalesCache = [];
        $runtimeOdpsCache = [];

        DB::beginTransaction();
        try {
            foreach ($dryRun['rows'] as $r) {
                if ($r['status'] === 'error') {
                    $skippedCount++;
                    continue;
                }

                $data = $r['mapped'];

                switch ($category) {
                    case 'customers':
                        $res = $this->importCustomerItem($data, $duplicateMode, $autoCreateArea, $options, $affectedOdpIds, $runtimeAreasCache, $runtimeSalesCache, $runtimeOdpsCache);
                        break;
                    case 'network':
                        $res = $this->importNetworkItem($data, $duplicateMode, $autoCreateArea);
                        break;
                    case 'booking_survey':
                        $res = $this->importSurveyItem($data, $duplicateMode);
                        break;
                    case 'installation_activation':
                        $res = $this->importInstallationItem($data, $duplicateMode);
                        break;
                    case 'invoices':
                        $res = $this->importInvoiceItem($data, $duplicateMode);
                        break;
                    case 'payments':
                        $res = $this->importPaymentItem($data, $duplicateMode);
                        break;
                    case 'employees':
                        $res = $this->importEmployeeItem($data, $duplicateMode, $autoCreateArea);
                        break;
                    default:
                        $res = 'skip';
                }

                if ($res === 'inserted') $insertedCount++;
                elseif ($res === 'updated') $updatedCount++;
                else $skippedCount++;
            }

            // Sync port counts and status for all touched ODPs in one fast pass
            if (!empty($affectedOdpIds)) {
                foreach (array_keys($affectedOdpIds) as $touchedOdpId) {
                    $odp = Odp::find($touchedOdpId);
                    if ($odp) {
                        $usedCount = OdpPort::where('odp_id', $touchedOdpId)->where('status', 'used')->count();
                        if ($usedCount === 0) {
                            $usedCount = Ont::where('odp_id', $touchedOdpId)->count();
                        }
                        $odp->update([
                            'used_ports' => $usedCount,
                            'status' => ($usedCount >= $odp->total_ports) ? 'full' : 'active'
                        ]);
                    }
                }
            }

            // Record to Audit Log
            $logEntity = match($category) {
                'employees' => new Employee(),
                'invoices' => new Invoice(),
                'payments' => new Payment(),
                default => new Customer(),
            };

            AuditLog::createLog(
                "Data Migration ({$category})",
                $logEntity,
                null,
                'imported',
                "Batch {$batchId}: Sukses {$insertedCount} baru, {$updatedCount} diperbarui, {$skippedCount} dilewati."
            );

            DB::commit();

            return [
                'success' => true,
                'batch_id' => $batchId,
                'message' => "Migrasi data {$category} berhasil diselesaikan.",
                'summary' => [
                    'total' => count($rows),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                ],
                'created_count' => $insertedCount,
                'updated_count' => $updatedCount,
                'skipped_count' => $skippedCount,
                'failed_count' => 0,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("[DataMigrationService] Execute error on {$category}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan ke database: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve photo: download from URL or normalize local storage path
     */
    protected function resolvePhotoPath(?string $pathOrUrl, string $subfolder): ?string
    {
        if (empty($pathOrUrl)) return null;
        $pathOrUrl = trim($pathOrUrl);
        if ($pathOrUrl === '' || $pathOrUrl === '-') return null;

        // If it's a web URL, download and save to public disk
        if (filter_var($pathOrUrl, FILTER_VALIDATE_URL)) {
            try {
                $ctx = stream_context_create([
                    'http' => ['timeout' => 5],
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]);
                $content = @file_get_contents($pathOrUrl, false, $ctx);
                if ($content) {
                    $ext = pathinfo(parse_url($pathOrUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $fileName = $subfolder . '/' . Str::random(32) . '.' . strtolower($ext);
                    Storage::disk('public')->put($fileName, $content);
                    return $fileName;
                }
            } catch (\Throwable $e) {
                Log::warning("[DataMigrationService] Failed downloading photo from URL {$pathOrUrl}: " . $e->getMessage());
            }
        }

        // If it's a relative path or filename
        $cleaned = ltrim($pathOrUrl, '/\\');
        if (str_starts_with($cleaned, 'storage/')) {
            $cleaned = substr($cleaned, 8);
        }

        return $cleaned;
    }

    // ── Row Validators ──────────────────────────────────────────────

    protected function validateCustomerRow(
        $row,
        &$errors,
        &$warnings,
        &$mapped,
        $areasCache,
        $packagesCache,
        $usersCache,
        $userNamesCache,
        $customersCache,
        $options,
        $areasNormalizedCache = [],
        $userPhonesCache = [],
        $affiliateCodesCache = [],
        $employeesIakCache = [],
        $odpsCache = [],
        $odpsNormalizedCache = [],
        $odpsStrippedNormCache = [],
        $allUsersList = [],
        $allEmployeesList = [],
        &$infos = []
    ) {
        $name = trim((string)($row['nama_lengkap'] ?? $row['nama'] ?? ''));
        $rawPhone = trim((string)($row['nomor_wa'] ?? $row['phone'] ?? $row['no_wa'] ?? ''));
        $areaName = trim((string)($row['nama_area'] ?? $row['area'] ?? ''));
        $customerCode = trim((string)($row['kode_pelanggan'] ?? $row['customer_code'] ?? ''));

        if (empty($name)) $errors[] = 'Nama Lengkap wajib diisi.';
        if (empty($rawPhone)) $errors[] = 'Nomor WA / Telepon wajib diisi.';
        if (empty($areaName)) $errors[] = 'Nama Area / Wilayah wajib diisi.';

        // Parse phone: support multiple numbers (e.g. "089510544258, 088290073037" or "08123456789 / 08571234567")
        $phoneCandidates = [];
        $splitParts = preg_split('/[\/,;|\s+dan\s+]+/i', $rawPhone);
        foreach ($splitParts as $part) {
            $cleaned = preg_replace('/[^0-9+]/', '', trim($part));
            if (strlen(preg_replace('/[^0-9]/', '', $cleaned)) >= 6) {
                $phoneCandidates[] = $cleaned;
            }
        }

        $phone = '';
        $extraPhones = [];
        if (!empty($phoneCandidates)) {
            $phone = $phoneCandidates[0];
            if (count($phoneCandidates) > 1) {
                $extraPhones = array_slice($phoneCandidates, 1);
            }
        } else {
            $phone = preg_replace('/[^0-9+]/', '', $rawPhone);
        }

        if (strlen($phone) > 30) {
            $phone = substr($phone, 0, 30);
        }

        // Check duplicate
        $isExisting = false;
        if (!empty($customerCode) && isset($customersCache[$customerCode])) {
            $isExisting = true;
            if (($options['duplicate_mode'] ?? 'skip') === 'update') {
                $warnings[] = "Kode pelanggan '{$customerCode}' sudah ada; data akan diperbarui.";
            } else {
                $warnings[] = "Kode pelanggan '{$customerCode}' sudah terdaftar; baris ini akan dilewati.";
            }
        }

        // Area check (flexible matching: 'BC-3' matches 'BC 3')
        $areaId = self::resolveAreaIdFromCache($areaName, $areasCache, $areasNormalizedCache);
        if (!$areaId && !empty($areaName)) {
            $warnings[] = "Area '{$areaName}' belum ada di master data; sistem akan otomatis membuatnya.";
        }

        // Package check
        $packageName = $row['nama_paket'] ?? $row['paket'] ?? '';
        $packageId = null;
        if (!empty($packageName)) {
            $packageId = $packagesCache[strtolower($packageName)] ?? null;
            if (!$packageId) {
                $warnings[] = "Paket '{$packageName}' tidak ditemukan di master paket. Pelanggan diimpor tanpa kaitan paket.";
            }
        }

        // Sales / Referrer check dengan Auto-Match Cerdas & Toleransi Typo/Spelling
        $salesRef = trim((string)($row['username_sales'] ?? $row['sales'] ?? $row['sales_karyawan'] ?? $row['karyawan'] ?? $row['referral'] ?? $row['petugas_booking'] ?? $row['nama_sales'] ?? ''));
        $salesId = null;
        if (!empty($salesRef)) {
            [$resolvedSalesId, $salesMethod, $matchedSalesName] = self::resolveSalesUser(
                $salesRef,
                $usersCache,
                $userNamesCache,
                $userPhonesCache,
                $affiliateCodesCache,
                $employeesIakCache,
                $allUsersList,
                $allEmployeesList
            );

            if ($resolvedSalesId) {
                $salesId = $resolvedSalesId;
            } else {
                $autoCreateSales = !isset($options['auto_create_sales']) || !empty($options['auto_create_sales']);
                if ($autoCreateSales) {
                    $infos[] = "Petugas sales '{$salesRef}' belum terdaftar di sistem; akun sales baru akan otomatis dibuatkan.";
                } else {
                    $warnings[] = "Petugas sales / karyawan '{$salesRef}' tidak ditemukan di sistem. Kolom sales dikosongkan.";
                }
            }
        }

        // Address compilation
        $street = $row['alamat_jalan'] ?? $row['address'] ?? '';
        $rtRw = $row['rt_rw'] ?? '';
        $village = $row['kelurahan_desa'] ?? $row['desa'] ?? '';
        $district = $row['kecamatan'] ?? '';

        $addressParts = array_filter([
            $street,
            $rtRw ? 'RT/RW: ' . $rtRw : null,
            $village ? 'Kel. ' . $village : null,
            $district ? 'Kec. ' . $district : null,
        ]);
        $address = implode(', ', $addressParts) ?: '-';

        $rawStatus = trim((string)($row['status_pelanggan'] ?? $row['status'] ?? 'active'));
        [$status, $isValidStatus, $statusLabel] = self::normalizeCustomerStatus($rawStatus);
        if (!$isValidStatus) {
            $warnings[] = "Status '{$rawStatus}' tidak dikenal di sistem, dialihkan ke default 'active'.";
            $status = 'active';
        } elseif ($statusLabel) {
            $infos[] = "Status: {$statusLabel}";
        }

        $serviceStatus = strtolower(trim((string)($row['status_layanan'] ?? 'berbayar')));
        if (!in_array($serviceStatus, ['berbayar', 'gratis', 'free_home'])) {
            $serviceStatus = 'berbayar';
        }

        $baseAmount = (float) ($row['tarif_bulanan'] ?? $row['base_amount'] ?? 0);
        if ($serviceStatus === 'gratis' || $serviceStatus === 'free_home') {
            $baseAmount = 0;
        }
        if ($serviceStatus === 'free_home') {
            $packageId = null;
        }

        // Optional ONT & ODP linkage dengan Pemecahan Slash & Normalisasi Cerdas
        $rawOdpName = trim((string)($row['nama_odp'] ?? $row['odp'] ?? $row['nama_odp_terpasang'] ?? $row['odp_terpasang'] ?? $row['odp_name'] ?? ''));
        $snOnt = trim((string)($row['sn_ont'] ?? $row['serial_number'] ?? $row['sn'] ?? $row['no_seri_ont'] ?? $row['sn_modem'] ?? ''));
        $portNumber = !empty($row['nomor_port_odp']) ? (int)$row['nomor_port_odp'] : (!empty($row['nomor_port']) ? (int)$row['nomor_port'] : (!empty($row['port_odp']) ? (int)$row['port_odp'] : (!empty($row['port']) ? (int)$row['port'] : null)));
        $odpId = null;
        $odpName = $rawOdpName;

        if (!empty($rawOdpName)) {
            [$odpObj, $odpMethod, $matchedCand, $totalCands] = self::resolveOdpFlexible(
                $rawOdpName,
                $odpsCache,
                $odpsNormalizedCache,
                $odpsStrippedNormCache
            );

            if ($odpObj) {
                $odpId = $odpObj->id;
                $odpName = $odpObj->name;
            } else {
                $autoCreateOdps = !isset($options['auto_create_odps']) || !empty($options['auto_create_odps']);
                if ($autoCreateOdps) {
                    $infos[] = "ODP '{$rawOdpName}' belum terdaftar di master data ODP; sistem akan otomatis membuat ODP baru, menghubungkan ke Area/ODC, dan menyiapkan port fisiknya saat migrasi.";
                } else {
                    $warnings[] = "ODP '{$rawOdpName}' belum terdaftar di master data ODP. Aktifkan opsi buat ODP otomatis untuk melanjutkan.";
                }
            }
        }

        $notes = trim((string)($row['catatan'] ?? $row['catatan_booking'] ?? $row['notes'] ?? ''));
        if (!empty($extraPhones)) {
            $altPhoneText = "No. HP Cadangan: " . implode(', ', $extraPhones);
            $notes = ($notes !== '') ? ($notes . " | " . $altPhoneText) : $altPhoneText;
        }

        // ── 2. JADWAL SURVEY FIELDS ─────────────────────────────────
        $surveyScheduleDate = self::parseFlexibleDate($row['tgl_jadwal_survey'] ?? $row['jadwal_survey'] ?? $row['tgl_survey_jadwal'] ?? null);
        $surveyScheduleTime = trim((string)($row['jam_jadwal_survey'] ?? $row['jam_survey'] ?? '09:00'));
        $rawSurveyTech = trim((string)($row['teknisi_survey'] ?? $row['surveyor'] ?? $row['petugas_survey'] ?? ''));
        $surveyTechId = null;
        if (!empty($rawSurveyTech)) {
            [$resolvedTechId] = self::resolveSalesUser($rawSurveyTech, $usersCache, $userNamesCache, $userPhonesCache, $affiliateCodesCache, $employeesIakCache, $allUsersList, $allEmployeesList);
            $surveyTechId = $resolvedTechId;
        }
        $surveyScheduleStatus = strtolower(trim((string)($row['status_jadwal_survey'] ?? '')));
        if (!in_array($surveyScheduleStatus, ['scheduled', 'on_the_way', 'working', 'done', 'cancelled'])) {
            $surveyScheduleStatus = null;
        }

        // ── 3. LAPORAN SURVEY FIELDS ────────────────────────────────
        $surveyDate = self::parseFlexibleDate($row['tgl_survey'] ?? $row['tanggal_survey'] ?? null);
        $distanceMeters = !empty($row['jarak_tarikan_meter']) ? (float)$row['jarak_tarikan_meter'] : (!empty($row['jarak_kabel']) ? (float)$row['jarak_kabel'] : (!empty($row['panjang_kabel']) ? (float)$row['panjang_kabel'] : (!empty($row['tarikan_kabel']) ? (float)$row['tarikan_kabel'] : (!empty($row['jarak_meter']) ? (float)$row['jarak_meter'] : (!empty($row['jarak']) ? (float)$row['jarak'] : 0.0)))));
        $rawFeasibility = strtolower(trim((string)($row['kelayakan_survey'] ?? $row['kelayakan'] ?? $row['feasibility'] ?? 'feasible')));
        $feasibility = in_array($rawFeasibility, ['tidak_layak', 'not_feasible', 'unfeasible']) ? 'not_feasible' : 'feasible';
        $surveyNotes = trim((string)($row['catatan_survey'] ?? $row['survey_notes'] ?? ''));

        // Info notifikasi cerdas untuk migrasi
        if (!empty($snOnt)) {
            $infos[] = "Perangkat ONT SN '{$snOnt}' terdeteksi; otomatis sinkron ke Menu Data ONT, Detail Pelanggan & Alokasi Perangkat.";
        }
        if ($distanceMeters > 0) {
            $infos[] = "Tarikan kabel {$distanceMeters}m terdeteksi; otomatis mengisi Jarak Survey, Material Kabel Frecon & Transaksi Gudang.";
        }

        // ── 4. JADWAL PASANG FIELDS ─────────────────────────────────
        $installScheduleDate = self::parseFlexibleDate($row['tgl_jadwal_pasang'] ?? $row['jadwal_pasang'] ?? $row['tgl_pasang_jadwal'] ?? null);
        $installScheduleTime = trim((string)($row['jam_jadwal_pasang'] ?? $row['jam_pasang'] ?? '10:00'));
        $rawInstallTech = trim((string)($row['teknisi_pasang'] ?? $row['teknisi_instalasi'] ?? $row['teknisi'] ?? ''));
        $installTechId = null;
        if (!empty($rawInstallTech)) {
            [$resolvedInstallTechId] = self::resolveSalesUser($rawInstallTech, $usersCache, $userNamesCache, $userPhonesCache, $affiliateCodesCache, $employeesIakCache, $allUsersList, $allEmployeesList);
            $installTechId = $resolvedInstallTechId;
        }
        $installScheduleStatus = strtolower(trim((string)($row['status_jadwal_pasang'] ?? '')));
        if (!in_array($installScheduleStatus, ['scheduled', 'on_the_way', 'working', 'done', 'cancelled'])) {
            $installScheduleStatus = null;
        }
        $installScheduleNotes = trim((string)($row['catatan_jadwal_pasang'] ?? $row['catatan_pasang'] ?? ''));

        // ── 5. ONT DEVICE EXTRA FIELDS ──────────────────────────────
        $startTime = trim((string)($row['jam_mulai_pasang'] ?? $row['jam_pasang_mulai'] ?? $row['start_time'] ?? '09:00'));
        $endTime = trim((string)($row['jam_selesai_pasang'] ?? $row['jam_pasang_selesai'] ?? $row['end_time'] ?? '11:00'));
        $ipLogin = trim((string)($row['ip_login_ont'] ?? $row['ip_ont'] ?? $row['ip_login'] ?? $row['ip_address_ont'] ?? '192.168.1.1'));
        $userLogin = trim((string)($row['user_login_ont'] ?? $row['user_ont'] ?? $row['login_user'] ?? $row['username_ont'] ?? $row['user_login'] ?? 'admin'));
        $passLogin = trim((string)($row['pass_login_ont'] ?? $row['pass_ont'] ?? $row['login_password'] ?? $row['password_ont'] ?? $row['pass_login'] ?? 'admin'));
        $pppoeUser = trim((string)($row['username_pppoe'] ?? $row['pppoe_user'] ?? $row['user_pppoe'] ?? $row['akun_pppoe'] ?? ''));
        $pppoePass = trim((string)($row['password_pppoe'] ?? $row['pppoe_password'] ?? $row['pass_pppoe'] ?? ''));
        $freeHotspot = in_array(strtolower(trim((string)($row['free_hotspot'] ?? $row['hotspot_gratis'] ?? ''))), ['1', 'true', 'ya', 'yes']);
        $userHotspot = trim((string)($row['user_hotspot'] ?? $row['hotspot_user'] ?? ''));
        $passHotspot = trim((string)($row['pass_hotspot'] ?? $row['hotspot_password'] ?? ''));

        $mapped = [
            'customer_code' => $customerCode ?: null,
            'name' => $name,
            'phone' => $phone,
            'email' => $row['email'] ?? null,
            'area' => $areaName,
            'area_id' => $areaId,
            'address' => $address,
            'package_id' => $packageId,
            'base_amount' => $baseAmount,
            'installation_fee' => (float) ($row['biaya_pasang'] ?? 0),
            'service_status' => $serviceStatus,
            'billing_type' => in_array(strtolower($row['jenis_billing'] ?? ''), ['prabayar', 'pascabayar']) ? strtolower($row['jenis_billing']) : 'prabayar',
            'status' => $status,
            'registration_date' => self::parseFlexibleDate($row['tgl_registrasi'] ?? null, now()->toDateString()),
            'activation_date' => ($status === 'active') ? self::parseFlexibleDate($row['tgl_aktivasi'] ?? null, now()->toDateString()) : self::parseFlexibleDate($row['tgl_aktivasi'] ?? null),
            'sales_id' => $salesId,
            'sales_name' => $salesRef ?: null,
            'latitude' => self::parseCoordinate($row['latitude'] ?? null, 'lat'),
            'longitude' => self::parseCoordinate($row['longitude'] ?? null, 'lng'),
            'identity_photo' => $row['foto_ktp'] ?? $row['identity_photo'] ?? null,
            'notes' => $notes ?: null,
            'is_existing' => $isExisting,
            'odp_id' => $odpId,
            'odp_name' => $odpName ?: null,
            'port_number' => $portNumber,
            'sn_ont' => $snOnt ?: null,
            'mac_ont' => self::sanitizeMacAddress($row['mac_ont'] ?? $row['mac_address'] ?? null),
            'merk_ont' => $row['merk_ont'] ?? $row['brand'] ?? 'ZTE',
            'tipe_model' => $row['tipe_model'] ?? $row['model'] ?? 'F609',
            'redaman_rx' => self::parseRxPower($row['redaman_rx'] ?? null, -20.0),
            'username_pppoe' => $pppoeUser ?: null,
            'password_pppoe' => $pppoePass ?: null,
            'vlan_id' => $row['vlan_id'] ?? null,
            'mode_akses' => $row['mode_akses'] ?? ($serviceStatus === 'free_home' ? 'VOUCHER' : 'PPPOE'),
            'tgl_jadwal_survey' => $surveyScheduleDate,
            'jam_jadwal_survey' => $surveyScheduleTime,
            'teknisi_survey_id' => $surveyTechId,
            'status_jadwal_survey' => $surveyScheduleStatus,
            'tgl_survey' => $surveyDate,
            'jarak_tarikan_meter' => $distanceMeters,
            'kelayakan_survey' => $feasibility,
            'catatan_survey' => $surveyNotes,
            'tgl_jadwal_pasang' => $installScheduleDate,
            'jam_jadwal_pasang' => $installScheduleTime,
            'teknisi_pasang_id' => $installTechId,
            'status_jadwal_pasang' => $installScheduleStatus,
            'catatan_jadwal_pasang' => $installScheduleNotes,
            'jam_mulai_pasang' => $startTime,
            'jam_selesai_pasang' => $endTime,
            'ip_login_ont' => $ipLogin,
            'user_login_ont' => $userLogin,
            'pass_login_ont' => $passLogin,
            'free_hotspot' => $freeHotspot,
            'user_hotspot' => $userHotspot,
            'pass_hotspot' => $passHotspot,
        ];
    }

    protected function validateNetworkRow($row, &$errors, &$warnings, &$mapped, $areasCache, $customersCache, $odpsCache, $options)
    {
        $oltName = trim($row['nama_olt'] ?? $row['olt'] ?? '');
        $odcName = trim($row['nama_odc'] ?? $row['odc'] ?? '');
        $odpName = trim($row['nama_odp'] ?? $row['odp'] ?? '');
        
        $rawPortNum = $row['nomor_port'] ?? $row['port_number'] ?? $row['port'] ?? '';
        $portNum = ($rawPortNum !== '' && $rawPortNum !== null && $rawPortNum !== '-') ? (int) preg_replace('/[^0-9]/', '', (string)$rawPortNum) : 0;
        
        $rawTotalPorts = $row['total_port_odp'] ?? $row['total_ports'] ?? 8;
        $totalPorts = (int) preg_replace('/[^0-9]/', '', (string)$rawTotalPorts) ?: 8;

        $rawPon = $row['pon_port_olt'] ?? $row['pon_port'] ?? $row['pon'] ?? 1;
        $ponPort = (int) preg_replace('/[^0-9]/', '', (string)$rawPon) ?: 1;

        $splitterRatio = trim((string)($row['splitter_odc'] ?? $row['splitter_ratio'] ?? $row['splitter'] ?? '1:4'));
        if (empty($splitterRatio)) $splitterRatio = '1:4';

        $areaName = trim($row['nama_area'] ?? '');

        if (empty($oltName)) {
            $oltName = 'OLT-DEFAULT';
            $warnings[] = 'Nama OLT tidak diisi; sistem menggunakan OLT default.';
        }
        if (empty($odcName)) {
            $odcName = 'ODC-DEFAULT';
            $warnings[] = 'Nama ODC tidak diisi; sistem menggunakan ODC default.';
        }
        if (empty($odpName)) $errors[] = 'Nama ODP wajib diisi.';
        if ($totalPorts < 1) $totalPorts = 8;
        // Nomor port bersifat opsional (bisa diisi nanti saat proses instalasi)
        if ($portNum > 0 && $portNum > $totalPorts) {
            $totalPorts = $portNum;
            $warnings[] = "Nomor port ({$portNum}) melebihi total port ODP ({$rawTotalPorts}); total kapasitas port dinaikkan menjadi {$totalPorts}.";
        }

        $customerCode = trim($row['kode_pelanggan'] ?? '');
        if ($customerCode === '-') $customerCode = '';
        $customerId = null;
        if (!empty($customerCode)) {
            $customerId = $customersCache[$customerCode] ?? null;
            if (!$customerId) {
                $warnings[] = "Kode pelanggan '{$customerCode}' tidak ditemukan di sistem. Port diimpor tanpa link pelanggan.";
            }
        }

        $snOnt = trim($row['sn_ont'] ?? '');
        if ($snOnt === '-') $snOnt = '';
        if ($customerId && empty($snOnt)) {
            $warnings[] = "Port terhubung ke pelanggan {$customerCode} tetapi SN ONT kosong.";
        }

        $rawStatus = trim($row['status_port'] ?? '');
        $portStatus = strtolower($rawStatus ?: ($customerId ? 'used' : 'available'));
        if (!in_array($portStatus, ['available', 'used', 'reserved', 'fault', 'stop'])) {
            $portStatus = $customerId ? 'used' : 'available';
        }

        $mapped = [
            'olt_name' => $oltName,
            'olt_ip' => $row['ip_olt'] ?? null,
            'pon_port' => $ponPort,
            'odc_name' => $odcName,
            'splitter_ratio' => $splitterRatio,
            'odp_name' => $odpName,
            'total_ports' => $totalPorts,
            'port_number' => $portNum > 0 ? $portNum : null,
            'port_status' => $portStatus,
            'customer_code' => $customerCode,
            'customer_id' => $customerId,
            'serial_number' => $snOnt ?: null,
            'mac_address' => self::sanitizeMacAddress($row['mac_ont'] ?? null),
            'vlan_id' => $row['vlan_id'] ?? null,
            'area_name' => $areaName ?: 'Default',
            'latitude' => self::parseCoordinate($row['latitude'] ?? null, 'lat'),
            'longitude' => self::parseCoordinate($row['longitude'] ?? null, 'lng'),
        ];
    }

    protected function validateSurveyRow($row, &$errors, &$warnings, &$mapped, $customersCache, $usersCache, $odpsCache, $odpsNormalizedCache = [], $odpsStrippedNormCache = [], $allUsersList = [], $allEmployeesList = [], &$infos = [])
    {
        $customerCode = $row['kode_pelanggan'] ?? '';
        if (empty($customerCode)) $errors[] = 'Kode Pelanggan wajib diisi.';
        $customerId = $customersCache[$customerCode] ?? null;
        if (!$customerId) $errors[] = "Pelanggan '{$customerCode}' tidak ditemukan di sistem.";

        $surveyorRef = $row['email_petugas_survey'] ?? $row['petugas_survey'] ?? '';
        $surveyorId = null;
        if (!empty($surveyorRef)) {
            [$resolvedSurveyorId] = self::resolveSalesUser($surveyorRef, $usersCache, [], [], [], [], $allUsersList, $allEmployeesList);
            $surveyorId = $resolvedSurveyorId;
        }
        if (!$surveyorId && !empty($surveyorRef)) {
            $errors[] = "Petugas survey '{$surveyorRef}' tidak ditemukan.";
        }

        $feasibility = strtolower($row['hasil_kelayakan'] ?? 'feasible');
        if (!in_array($feasibility, ['feasible', 'not_feasible', 'conditional'])) {
            $feasibility = 'feasible';
        }

        $odpName = trim((string)($row['nama_odp'] ?? ''));
        $odpId = null;
        if (!empty($odpName)) {
            [$odpObj] = self::resolveOdpFlexible($odpName, $odpsCache, $odpsNormalizedCache, $odpsStrippedNormCache);
            if ($odpObj) {
                $odpId = $odpObj->id;
                $odpName = $odpObj->name;
            } else {
                $infos[] = "ODP '{$odpName}' belum terdaftar di master ODP; sistem akan otomatis membuat ODP baru saat migrasi.";
            }
        }
        if ($feasibility === 'feasible' && empty($odpName)) {
            $warnings[] = "Hasil feasible tetapi nama ODP kosong.";
        }

        $mapped = [
            'customer_id' => $customerId,
            'surveyor_id' => $surveyorId,
            'odp_id' => $odpId,
            'odp_name' => $odpName ?: null,
            'port_number' => !empty($row['nomor_port']) ? (int)$row['nomor_port'] : null,
            'distance_meters' => !empty($row['jarak_meter']) ? (float)$row['jarak_meter'] : null,
            'feasibility' => $feasibility,
            'signal_loss' => !empty($row['redaman_sinyal']) ? (float)$row['redaman_sinyal'] : null,
            'survey_date' => self::parseFlexibleDate($row['tgl_survey'] ?? null, now()->toDateString()),
            'notes' => $row['catatan_survey'] ?? null,
        ];
    }

    protected function validateInstallationRow($row, &$errors, &$warnings, &$mapped, $customersCache, $odpsCache, $odpsNormalizedCache = [], $odpsStrippedNormCache = [], &$infos = [])
    {
        $customerCode = $row['kode_pelanggan'] ?? '';
        if (empty($customerCode)) $errors[] = 'Kode Pelanggan wajib diisi.';
        $customerId = $customersCache[$customerCode] ?? null;
        if (!$customerId) $errors[] = "Pelanggan '{$customerCode}' tidak ditemukan.";

        $sn = $row['sn_ont'] ?? '';
        if (empty($sn)) $errors[] = 'Serial Number ONT wajib diisi.';

        $odpName = trim((string)($row['nama_odp'] ?? ''));
        $odpId = null;
        if (!empty($odpName)) {
            [$odpObj] = self::resolveOdpFlexible($odpName, $odpsCache, $odpsNormalizedCache, $odpsStrippedNormCache);
            if ($odpObj) {
                $odpId = $odpObj->id;
                $odpName = $odpObj->name;
            } else {
                $infos[] = "ODP '{$odpName}' belum terdaftar di master ODP; sistem akan otomatis membuat ODP baru saat migrasi.";
            }
        } else {
            $warnings[] = "Nama ODP tidak diisi; penautan port ODP akan dilewati jika belum ada data.";
        }

        $portNum = (int) ($row['nomor_port_odp'] ?? 1);
        $rxPower = (float) ($row['redaman_rx'] ?? -20.0);

        $mapped = [
            'customer_id' => $customerId,
            'serial_number' => $sn,
            'mac_address' => $row['mac_address'] ?? null,
            'brand' => $row['merk_ont'] ?? 'ZTE',
            'model' => $row['tipe_model'] ?? 'F609',
            'odp_id' => $odpId,
            'odp_name' => $odpName ?: null,
            'port_number' => $portNum,
            'rx_power' => $rxPower,
            'pppoe_user' => $row['username_pppoe'] ?? null,
            'pppoe_password' => $row['password_pppoe'] ?? null,
            'vlan_id' => $row['vlan_id'] ?? null,
            'access_mode' => $row['mode_akses'] ?? 'PPPOE',
            'activation_date' => self::parseFlexibleDate($row['tgl_aktivasi'] ?? null, now()->toDateString()),
            'is_audited' => !empty($row['lulus_audit_qc']) ? (bool)$row['lulus_audit_qc'] : true,
            'start_time' => $row['jam_pasang_mulai'] ?? null,
            'end_time' => $row['jam_pasang_selesai'] ?? null,
            'photo_redaman' => $row['foto_redaman'] ?? $row['photo_redaman'] ?? null,
            'photo_odp' => $row['foto_odp'] ?? $row['photo_odp'] ?? null,
            'photo_ont' => $row['foto_modem'] ?? $row['foto_ont'] ?? $row['photo_ont'] ?? null,
            'photo_customer' => $row['foto_pelanggan'] ?? $row['foto_customer'] ?? $row['photo_customer'] ?? null,
            'photo_installation' => $row['foto_instalasi'] ?? $row['foto_installation'] ?? $row['photo_installation'] ?? null,
        ];
    }

    protected function validateInvoiceRow($row, &$errors, &$warnings, &$mapped, $customersCache, $invoicesCache)
    {
        $customerCode = $row['kode_pelanggan'] ?? '';
        if (empty($customerCode)) $errors[] = 'Kode Pelanggan wajib diisi.';
        $customerId = $customersCache[$customerCode] ?? null;
        if (!$customerId) $errors[] = "Pelanggan '{$customerCode}' tidak ditemukan.";

        $month = (int) ($row['bulan_periode'] ?? 0);
        $year = (int) ($row['tahun_periode'] ?? 0);
        if ($month < 1 || $month > 12) $errors[] = 'Bulan periode harus bernilai 1 - 12.';
        if ($year < 2000 || $year > 2099) $errors[] = 'Tahun periode tidak valid.';

        $amount = (float) ($row['total_tagihan'] ?? 0);
        if ($amount <= 0) $warnings[] = 'Nominal tagihan bernilai 0 atau negatif.';

        $invNum = $row['nomor_invoice'] ?? null;
        $isExisting = false;
        if ($invNum && isset($invoicesCache[$invNum])) {
            $isExisting = true;
            $warnings[] = "Invoice {$invNum} sudah ada; data akan disinkronkan.";
        }

        $status = strtolower($row['status_invoice'] ?? 'unpaid');
        if (!in_array($status, ['unpaid', 'paid', 'partial', 'cancelled'])) $status = 'unpaid';

        $mapped = [
            'invoice_number' => $invNum,
            'customer_id' => $customerId,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => $amount,
            'issued_date' => self::parseFlexibleDate($row['tgl_terbit'] ?? null, now()->toDateString()),
            'due_date' => self::parseFlexibleDate($row['tgl_jatuh_tempo'] ?? null, now()->addDays(7)->toDateString()),
            'status' => $status,
            'billing_type' => in_array(strtolower($row['jenis_tagihan'] ?? ''), ['prabayar', 'pascabayar', 'prorata']) ? strtolower($row['jenis_tagihan']) : 'prabayar',
            'notes' => $row['catatan_invoice'] ?? null,
            'is_existing' => $isExisting,
        ];
    }

    protected function validatePaymentRow($row, &$errors, &$warnings, &$mapped, $invoicesCache, $usersCache)
    {
        $invNum = $row['nomor_invoice'] ?? '';
        if (empty($invNum)) $errors[] = 'Nomor Invoice wajib diisi.';
        $invoiceId = $invoicesCache[$invNum] ?? null;
        if (!$invoiceId) $errors[] = "Nomor Invoice '{$invNum}' tidak terdaftar.";

        $amount = (float) ($row['nominal_bayar'] ?? 0);
        if ($amount <= 0) $errors[] = 'Nominal pembayaran harus > 0.';

        $method = strtolower($row['metode_bayar'] ?? 'transfer');
        if (!in_array($method, ['cash', 'transfer', 'ewallet', 'auto_debit'])) $method = 'transfer';

        $collectorId = null;
        $collectorEmail = $row['email_penerima'] ?? '';
        if (!empty($collectorEmail)) {
            $collectorId = $usersCache[strtolower($collectorEmail)] ?? null;
        }

        $mapped = [
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => self::parseFlexibleDate($row['tgl_bayar'] ?? null, now()->toDateString()),
            'payment_method' => $method,
            'reference_number' => $row['nomor_referensi'] ?? null,
            'collected_by' => $collectorId,
            'notes' => $row['catatan'] ?? null,
        ];
    }

    // ── Item Importers ──────────────────────────────────────────────

    protected function importCustomerItem(
        array $data,
        string $dupMode,
        bool $autoCreateArea,
        array $options = [],
        array &$affectedOdpIds = [],
        array &$runtimeAreasCache = [],
        array &$runtimeSalesCache = [],
        array &$runtimeOdpsCache = []
    ): string
    {
        // 1. Resolve area flexibly
        $areaId = $data['area_id'] ?? null;
        if (!$areaId && !empty($data['area'])) {
            $rawArea = trim($data['area']);
            $areaLower = strtolower($rawArea);
            if (isset($runtimeAreasCache[$areaLower])) {
                $areaId = $runtimeAreasCache[$areaLower];
            } else {
                $matchedArea = self::findAreaByFlexibleName($rawArea);
                if ($matchedArea) {
                    $areaId = $matchedArea->id;
                } elseif ($autoCreateArea) {
                    $area = Area::firstOrCreate(['name' => $rawArea]);
                    $areaId = $area->id;
                }
                if ($areaId) {
                    $runtimeAreasCache[$areaLower] = $areaId;
                }
            }
        }

        // Auto-create sales user if missing and auto_create_sales is enabled
        $salesId = $data['sales_id'] ?? null;
        if (!$salesId && !empty($options['auto_create_sales']) && !empty($data['sales_name'])) {
            $rawSales = trim((string)$data['sales_name']);
            $salesLower = strtolower($rawSales);
            if (isset($runtimeSalesCache[$salesLower])) {
                $salesId = $runtimeSalesCache[$salesLower];
            } else {
                $salesEmail = Str::slug($rawSales, '.') . '@isp.local';
                $salesUser = User::where('email', $salesEmail)->first();
                if (!$salesUser) {
                    $salesUser = User::create([
                        'name' => ucwords(strtolower($rawSales)),
                        'email' => $salesEmail,
                        'password' => bcrypt('password123'),
                        'role' => 'sales',
                        'job_position' => 'Sales',
                    ]);
                    try {
                        $salesUser->assignRole('sales');
                    } catch (\Throwable $e) {}
                }
                $salesId = $salesUser->id;
                $runtimeSalesCache[$salesLower] = $salesId;
            }
        }

        // Auto-create ODP if missing or not yet in master data
        $odpId = $data['odp_id'] ?? null;
        if (!empty($data['odp_name'])) {
            $reqPort = !empty($data['port_number']) ? (int)$data['port_number'] : 1;
            $odpObj = self::getOrCreateOdp(
                $data['odp_name'],
                $areaId,
                self::parseCoordinate($data['latitude'] ?? null, 'lat'),
                self::parseCoordinate($data['longitude'] ?? null, 'lng'),
                $reqPort,
                $runtimeOdpsCache
            );
            if ($odpObj) {
                $odpId = $odpObj->id;
            }
        }

        $customerCode = $data['customer_code'];
        $existing = null;
        if ($customerCode) {
            $existing = Customer::where('customer_code', $customerCode)->first();
        }

        if ($existing) {
            if ($dupMode === 'skip') {
                return 'skipped';
            }
            $identityPhoto = $this->resolvePhotoPath($data['identity_photo'] ?? null, 'customers');

            // Update
            $existing->update([
                'name' => $data['name'],
                'phone' => substr($data['phone'], 0, 50),
                'email' => $data['email'] ?? $existing->email,
                'area' => $data['area'],
                'area_id' => $areaId ?: $existing->area_id,
                'address' => $data['address'],
                'package_id' => $data['package_id'] ?: $existing->package_id,
                'base_amount' => $data['base_amount'],
                'installation_fee' => $data['installation_fee'],
                'service_status' => $data['service_status'],
                'billing_type' => $data['billing_type'],
                'status' => $data['status'],
                'sales_id' => $salesId ?: $existing->sales_id,
                'latitude' => self::parseCoordinate($data['latitude'] ?? null, 'lat') ?: $existing->latitude,
                'longitude' => self::parseCoordinate($data['longitude'] ?? null, 'lng') ?: $existing->longitude,
                'identity_photo' => $identityPhoto ?: $existing->identity_photo,
                'notes' => $data['notes'] ?: $existing->notes,
            ]);
            $targetCust = $existing;
        } else {
            $identityPhoto = $this->resolvePhotoPath($data['identity_photo'] ?? null, 'customers');

            // Insert new
            $customer = Customer::create([
                'customer_code' => $customerCode,
                'name' => $data['name'],
                'phone' => substr($data['phone'], 0, 50),
                'email' => $data['email'],
                'area' => $data['area'],
                'area_id' => $areaId,
                'address' => $data['address'],
                'package_id' => $data['package_id'],
                'base_amount' => $data['base_amount'],
                'installation_fee' => $data['installation_fee'],
                'service_status' => $data['service_status'],
                'billing_type' => $data['billing_type'],
                'status' => $data['status'],
                'registration_date' => $data['registration_date'],
                'activation_date' => $data['activation_date'],
                'sales_id' => $salesId,
                'latitude' => self::parseCoordinate($data['latitude'] ?? null, 'lat'),
                'longitude' => self::parseCoordinate($data['longitude'] ?? null, 'lng'),
                'identity_photo' => $identityPhoto,
                'notes' => $data['notes'],
            ]);
            $targetCust = $customer;
        }

        // ── A. Resolve ODP & Port Fisik ─────────────────────────────
        $odp = null;
        $portToUse = null;
        $odpPort = null;
        if (!empty($odpId) && $targetCust) {
            $odp = Odp::find($odpId);
            if ($odp) {
                $affectedOdpIds[$odp->id] = true;
                if (empty($targetCust->area_id) && !empty($odp->area_id)) {
                    $targetCust->update(['area_id' => $odp->area_id]);
                }

                $portToUse = !empty($data['port_number']) && (int)$data['port_number'] > 0 ? (int)$data['port_number'] : null;
                if (!$portToUse) {
                    $firstAvail = OdpPort::where('odp_id', $odp->id)->where('status', 'available')->orderBy('port_number')->first();
                    $portToUse = $firstAvail ? $firstAvail->port_number : 1;
                }

                $odpPort = OdpPort::updateOrCreate(
                    ['odp_id' => $odp->id, 'port_number' => $portToUse],
                    ['status' => in_array($targetCust->status, ['terminated', 'canceled']) ? 'available' : 'used']
                );
            }
        }

        // ── B. Sinkronisasi Perangkat Modem ONT ke Menu Data ONT & Customer ──
        $hasOntData = !empty($data['sn_ont']) || !empty($data['username_pppoe']) || !empty($data['user_login_ont']) || !empty($data['mac_ont']) || !empty($data['merk_ont']);
        $ontSn = !empty($data['sn_ont']) ? trim((string)$data['sn_ont']) : ($hasOntData ? ('ONT-' . ($targetCust->customer_code ?: $targetCust->id)) : null);
        $ontMac = self::sanitizeMacAddress($data['mac_ont'] ?? null);
        $ontRx = self::parseRxPower($data['redaman_rx'] ?? null, -20.0);
        $ontBrand = substr(trim($data['merk_ont'] ?? 'ZTE'), 0, 100);
        $ontModel = substr(trim($data['tipe_model'] ?? 'F609'), 0, 100);
        $ont = null;

        if ($hasOntData && $targetCust) {
            $ont = Ont::where('serial_number', $ontSn)->orWhere('customer_id', $targetCust->id)->first();
            $ontPayload = [
                'customer_id' => $targetCust->id,
                'serial_number' => $ontSn,
                'mac_address' => $ontMac ?: ($ont?->mac_address),
                'brand' => $ontBrand ?: ($ont?->brand ?: 'ZTE'),
                'model' => $ontModel ?: ($ont?->model ?: 'F609'),
                'odp_id' => $odp ? $odp->id : ($ont?->odp_id),
                'odp_port_id' => $odpPort ? $odpPort->id : ($ont?->odp_port_id),
                'port_number' => $portToUse ?: ($ont?->port_number),
                'area_id' => $areaId ?: ($odp ? $odp->area_id : ($targetCust->area_id ?: $ont?->area_id)),
                'rx_power' => $ontRx,
                'initial_rx_power' => $ontRx,
                'pppoe_user' => !empty($data['username_pppoe']) ? substr(trim((string)$data['username_pppoe']), 0, 100) : ($ont?->pppoe_user),
                'pppoe_password' => !empty($data['password_pppoe']) ? substr(trim((string)$data['password_pppoe']), 0, 100) : ($ont?->pppoe_password),
                'vlan_id' => !empty($data['vlan_id']) ? substr(trim((string)$data['vlan_id']), 0, 50) : ($ont?->vlan_id),
                'access_mode' => $data['mode_akses'] ?? ($data['service_status'] === 'free_home' ? 'VOUCHER' : ($ont?->access_mode ?: 'PPPOE')),
                'ip_login' => !empty($data['ip_login_ont']) ? $data['ip_login_ont'] : ($ont?->ip_login ?: '192.168.1.1'),
                'login_user' => !empty($data['user_login_ont']) ? $data['user_login_ont'] : ($ont?->login_user ?: 'admin'),
                'login_password' => !empty($data['pass_login_ont']) ? $data['pass_login_ont'] : ($ont?->login_password ?: 'admin'),
                'start_time' => !empty($data['jam_mulai_pasang']) ? $data['jam_mulai_pasang'] : ($ont?->start_time ?: '09:00'),
                'end_time' => !empty($data['jam_selesai_pasang']) ? $data['jam_selesai_pasang'] : ($ont?->end_time ?: '11:00'),
                'free_hotspot' => (bool)($data['free_hotspot'] ?? ($ont?->free_hotspot ?? false)),
                'hotspot_user' => !empty($data['user_hotspot']) ? substr(trim((string)$data['user_hotspot']), 0, 100) : ($ont?->hotspot_user),
                'hotspot_password' => !empty($data['pass_hotspot']) ? substr(trim((string)$data['pass_hotspot']), 0, 100) : ($ont?->hotspot_password),
                'status' => in_array($targetCust->status, ['terminated', 'canceled']) ? 'inactive' : 'active',
            ];

            if ($ont) {
                $ont->update($ontPayload);
            } else {
                $ont = Ont::create($ontPayload);
            }
        }

        // ── C. Auto-detect Material Freecon / Dropcore & Cable Distance ───
        $cableDistance = !empty($data['jarak_tarikan_meter']) ? (float)$data['jarak_tarikan_meter'] : 0.0;
        if ($cableDistance <= 0 && in_array($targetCust->status, ['installing', 'active', 'terminated'])) {
            $cableDistance = 50.0; // Standar awal tarikan kabel FTTH
        }

        $freeconMaterial = Material::where(function ($q) {
            $q->where('name', 'like', '%frecon%')
              ->orWhere('name', 'like', '%freecon%')
              ->orWhere('name', 'like', '%precon%')
              ->orWhere('name', 'like', '%dropcore%')
              ->orWhere('name', 'like', '%kabel drop%');
        })->first();

        if (!$freeconMaterial) {
            $freeconMaterial = Material::where('category', 'like', '%frecon%')
                ->orWhere('category', 'like', '%kabel%')
                ->first();
        }

        if (!$freeconMaterial) {
            $freeconMaterial = Material::firstOrCreate(
                ['name' => 'Kabel Dropcore / Frecon 1 Core'],
                [
                    'category' => 'Kabel Drop / Frecon',
                    'unit' => 'meter',
                    'stock' => 0,
                    'price_per_unit' => 1500,
                    'selling_price' => 2000,
                    'meter_per_roll' => 1000,
                    'is_active' => true,
                ]
            );
        }

        $cableItemName = $freeconMaterial ? $freeconMaterial->name : 'Kabel Dropcore / Frecon 1 Core';

        // ── D. Auto-fill Material Terpasang Pelanggan (CustomerInstalledMaterial) ───
        if ($targetCust) {
            $instDate = $targetCust->activation_date ?: ($targetCust->registration_date ?: now());

            if ($cableDistance > 0) {
                CustomerInstalledMaterial::updateOrCreate(
                    [
                        'customer_id' => $targetCust->id,
                        'item_name' => $cableItemName,
                    ],
                    [
                        'material_id' => $freeconMaterial?->id,
                        'quantity' => $cableDistance,
                        'unit' => 'meter',
                        'installed_at' => $instDate,
                        'installation_source' => 'migration',
                        'status' => 'installed',
                        'notes' => "Kabel tarikan migrasi ({$cableDistance} meter)" . (!empty($data['odp_name']) ? " dari ODP {$data['odp_name']}" : ''),
                    ]
                );
            }

            if (!empty($ontSn)) {
                $ontDeviceName = "ONT {$ontBrand} {$ontModel}";
                $ontMat = Material::where(function($q) use ($ontBrand) {
                    $q->where('category', 'like', '%ont%')
                      ->orWhere('category', 'like', '%modem%')
                      ->orWhere('name', 'like', "%{$ontBrand}%");
                })->first();

                CustomerInstalledMaterial::updateOrCreate(
                    [
                        'customer_id' => $targetCust->id,
                        'serial_number' => $ontSn,
                    ],
                    [
                        'material_id' => $ontMat?->id,
                        'item_name' => $ontDeviceName,
                        'quantity' => 1,
                        'unit' => 'unit',
                        'installed_at' => $instDate,
                        'installation_source' => 'migration',
                        'status' => 'installed',
                        'notes' => "Modem ONT aktif pelanggan (SN: {$ontSn}) dari migrasi sistem",
                    ]
                );
            }
        }

        // ── E. Auto-fill MaterialTransaction & Item (Menu Gudang & Riwayat Alokasi Material) ───
        if ($cableDistance > 0 && $targetCust) {
            $transNumber = 'OUT-MIG-' . ($targetCust->customer_code ?: 'CUS-' . $targetCust->id);
            $matTrans = MaterialTransaction::updateOrCreate(
                ['transaction_number' => $transNumber],
                [
                    'type' => 'out',
                    'status' => 'approved',
                    'date' => $targetCust->activation_date ? $targetCust->activation_date->format('Y-m-d') : now()->toDateString(),
                    'technician_name' => !empty($data['sales_name']) ? $data['sales_name'] : 'Teknisi Migrasi',
                    'purpose' => "Pemakaian Material Tarikan Kabel Migrasi Pelanggan {$targetCust->name}",
                    'source_type' => 'instalasi',
                    'source_id' => $targetCust->id,
                    'source_reference' => $targetCust->customer_code,
                    'user_id' => $salesId ?: 1,
                    'customer_id' => $targetCust->id,
                    'area_id' => $targetCust->area_id,
                    'notes' => "Material migrasi terpasang:\n- {$cableItemName}: {$cableDistance} meter" . (!empty($ontSn) ? "\n- ONT: {$ontBrand} {$ontModel} (SN: {$ontSn})" : ""),
                    'total_cost' => ($freeconMaterial ? (float)$freeconMaterial->price_per_unit : 0) * $cableDistance,
                ]
            );

            if ($freeconMaterial) {
                MaterialTransactionItem::updateOrCreate(
                    [
                        'material_transaction_id' => $matTrans->id,
                        'material_id' => $freeconMaterial->id,
                    ],
                    [
                        'quantity' => $cableDistance,
                        'unit' => 'meter',
                        'price_per_unit' => $freeconMaterial->price_per_unit ?? 0,
                        'total_price' => ($freeconMaterial->price_per_unit ?? 0) * $cableDistance,
                        'stock_before' => $freeconMaterial->stock ?? 0,
                        'stock_after' => $freeconMaterial->stock ?? 0,
                        'condition' => 'Terpasang/Digunakan',
                    ]
                );
            }
        }

        // ── F. Auto-generate / sync complete lifecycle audit trail (Survey Schedule, Survey Report, Installation Schedule) ───
        if ($targetCust) {
            $regDate = $targetCust->registration_date ? $targetCust->registration_date->format('Y-m-d') : ($targetCust->created_at ? $targetCust->created_at->format('Y-m-d') : date('Y-m-d'));
            $actDate = $targetCust->activation_date ? $targetCust->activation_date->format('Y-m-d') : $regDate;
            $techOrSalesId = $salesId ?: (auth()->id() ?: 1);

            $surveyTechId = !empty($data['teknisi_survey_id']) ? $data['teknisi_survey_id'] : $techOrSalesId;
            $surveySchedDate = !empty($data['tgl_jadwal_survey']) ? $data['tgl_jadwal_survey'] : $regDate;
            $surveySchedTime = !empty($data['jam_jadwal_survey']) ? $data['jam_jadwal_survey'] : '09:00';
            $surveySchedStatus = !empty($data['status_jadwal_survey']) ? $data['status_jadwal_survey'] : (in_array($targetCust->status, ['survey', 'installing', 'active']) ? 'done' : 'scheduled');

            $installTechId = !empty($data['teknisi_pasang_id']) ? $data['teknisi_pasang_id'] : $techOrSalesId;
            $installSchedDate = !empty($data['tgl_jadwal_pasang']) ? $data['tgl_jadwal_pasang'] : $actDate;
            $installSchedTime = !empty($data['jam_jadwal_pasang']) ? $data['jam_jadwal_pasang'] : '10:00';
            $installSchedStatus = !empty($data['status_jadwal_pasang']) ? $data['status_jadwal_pasang'] : (in_array($targetCust->status, ['active', 'terminated']) ? 'done' : 'scheduled');

            // 1. Jadwal Survey (TechnicianSchedule type 'survey')
            if (!empty($data['tgl_jadwal_survey']) || in_array($targetCust->status, ['survey', 'installing', 'active', 'terminated'])) {
                $surveySched = TechnicianSchedule::where('customer_id', $targetCust->id)->where('type', 'survey')->first();
                $surveySchedPayload = [
                    'customer_id' => $targetCust->id,
                    'technician_id' => $surveyTechId,
                    'scheduled_date' => $surveySchedDate,
                    'scheduled_time' => $surveySchedTime,
                    'type' => 'survey',
                    'status' => $surveySchedStatus,
                    'notes' => !empty($data['catatan_jadwal_survey']) ? $data['catatan_jadwal_survey'] : 'Jadwal survey lapangan migrasi sistem',
                ];
                if ($surveySched) {
                    $surveySched->update($surveySchedPayload);
                } else {
                    TechnicianSchedule::create($surveySchedPayload);
                }
            }

            // 2. Laporan Hasil Survey (Survey)
            if (!empty($odpId) || !empty($data['tgl_survey']) || in_array($targetCust->status, ['installing', 'active', 'terminated'])) {
                $survey = Survey::where('customer_id', $targetCust->id)->first();
                $surveyPayload = [
                    'customer_id' => $targetCust->id,
                    'odp_id' => $odpId,
                    'port_number' => $portToUse ?? 1,
                    'surveyor_id' => $surveyTechId,
                    'feasibility' => !empty($data['kelayakan_survey']) ? $data['kelayakan_survey'] : 'feasible',
                    'distance_meters' => $cableDistance,
                    'signal_loss' => $ontRx ?? -20.0,
                    'survey_date' => !empty($data['tgl_survey']) ? $data['tgl_survey'] : $regDate,
                    'notes' => !empty($data['catatan_survey']) ? $data['catatan_survey'] : ("Survey historis migrasi sistem (Tarikan kabel: {$cableDistance} m dari ODP " . ($data['odp_name'] ?? '-') . ")"),
                ];
                if ($survey) {
                    $survey->update($surveyPayload);
                } else {
                    Survey::create($surveyPayload);
                }
            }

            // 3. Jadwal Pasang (TechnicianSchedule type 'installation') - Auto-render ke Card "Riwayat Alokasi Material & ONT"
            if (!empty($data['tgl_jadwal_pasang']) || in_array($targetCust->status, ['installing', 'active', 'terminated'])) {
                $installSched = TechnicianSchedule::where('customer_id', $targetCust->id)->where('type', 'installation')->first();
                
                $schedNotes = [];
                if (!empty($ontSn)) {
                    $schedNotes[] = "ONT: {$ontBrand} {$ontModel} (SN: {$ontSn})";
                }
                if ($cableDistance > 0 && $freeconMaterial) {
                    $schedNotes[] = "Material: {$freeconMaterial->name} ({$cableDistance} meter)";
                }
                if (!empty($data['catatan_jadwal_pasang'])) {
                    $schedNotes[] = "Catatan: " . $data['catatan_jadwal_pasang'];
                }
                $installNotesText = count($schedNotes) > 0 ? implode("\n", $schedNotes) : 'Jadwal pasang historis migrasi sistem';

                $installSchedPayload = [
                    'customer_id' => $targetCust->id,
                    'technician_id' => $installTechId,
                    'scheduled_date' => $installSchedDate,
                    'scheduled_time' => $installSchedTime,
                    'type' => 'installation',
                    'status' => $installSchedStatus,
                    'notes' => $installNotesText,
                ];
                if ($installSched) {
                    $installSched->update($installSchedPayload);
                } else {
                    TechnicianSchedule::create($installSchedPayload);
                }
            }

            // 4. Mark customer as audited if active or terminated
            if (in_array($targetCust->status, ['active', 'terminated']) && !$targetCust->is_audited) {
                $targetCust->update(['is_audited' => true]);
            }
            if ($targetCust->status === 'terminated' && empty($targetCust->cancel_stage)) {
                $targetCust->update([
                    'cancel_stage' => 'terminated',
                    'cancel_reason' => 'Stop Permanen (Migrasi Sistem)',
                    'cancel_date' => $targetCust->activation_date ? $targetCust->activation_date->format('Y-m-d') : date('Y-m-d'),
                ]);
            }
        }

        return $existing ? 'updated' : 'inserted';
    }

    protected function importNetworkItem(array $data, string $dupMode, bool $autoCreateArea): string
    {
        // 1. Resolve area
        $area = Area::firstOrCreate(['name' => $data['area_name']]);

        // 2. Resolve or create OLT
        $olt = Olt::firstOrCreate(
            ['name' => $data['olt_name']],
            [
                'area_id' => $area->id,
                'ip_address' => $data['olt_ip'] ?? '192.168.1.1',
                'brand' => 'ZTE',
                'total_pon_ports' => 16,
                'status' => 'active'
            ]
        );
        if ($data['pon_port'] > $olt->total_pon_ports) {
            $olt->update(['total_pon_ports' => $data['pon_port']]);
        }

        // 3. Resolve OLT PON
        $oltPon = OltPon::firstOrCreate(
            ['olt_id' => $olt->id, 'port_number' => $data['pon_port']],
            ['name' => "PON {$data['pon_port']}", 'capacity' => 64, 'status' => 'active']
        );

        // Calculate ODC capacity from splitter ratio (e.g. 1:8 -> 8, 1:16 -> 16) or default 48
        $odcCapacity = 48;
        if (!empty($data['splitter_ratio']) && preg_match('/(?:1[:\/])?(\d+)/', $data['splitter_ratio'], $m)) {
            $ratioNum = (int) $m[1];
            if ($ratioNum > 0) $odcCapacity = $ratioNum;
        }

        // 4. Resolve ODC (Pastikan pon_port, splitter_ratio, dan pon_id tersimpan dan diperbarui)
        $odc = Odc::where('name', $data['odc_name'])->first();
        if (!$odc) {
            $odc = Odc::create([
                'name' => $data['odc_name'],
                'olt_id' => $olt->id,
                'pon_id' => $oltPon->id,
                'pon_port' => $data['pon_port'],
                'area_id' => $area->id,
                'splitter_ratio' => $data['splitter_ratio'] ?? '1:4',
                'capacity' => $odcCapacity,
                'status' => 'active',
            ]);
        } else {
            $odcUpdates = [];
            if ($dupMode === 'update' || empty($odc->pon_port)) {
                $odcUpdates['pon_port'] = $data['pon_port'];
            }
            if ($dupMode === 'update' || empty($odc->pon_id)) {
                $odcUpdates['pon_id'] = $oltPon->id;
            }
            if ($dupMode === 'update' || empty($odc->splitter_ratio) || $odc->splitter_ratio === '1:4') {
                if (!empty($data['splitter_ratio'])) {
                    $odcUpdates['splitter_ratio'] = $data['splitter_ratio'];
                }
            }
            if ($dupMode === 'update' || empty($odc->olt_id)) {
                $odcUpdates['olt_id'] = $olt->id;
            }
            if ($dupMode === 'update' || empty($odc->area_id)) {
                $odcUpdates['area_id'] = $area->id;
            }
            if (!empty($odcUpdates)) {
                $odc->update($odcUpdates);
            }
        }

        // 5. Resolve ODP (Pastikan total_ports selalu terbaca dan diperbarui)
        $odp = Odp::where('name', $data['odp_name'])->first();
        if (!$odp) {
            $odp = Odp::create([
                'name' => $data['odp_name'],
                'odc_id' => $odc->id,
                'area_id' => $area->id,
                'total_ports' => $data['total_ports'],
                'used_ports' => 0,
                'status' => 'active',
                'latitude' => self::parseCoordinate($data['latitude'] ?? null, 'lat'),
                'longitude' => self::parseCoordinate($data['longitude'] ?? null, 'lng'),
            ]);
        } else {
            $odpUpdates = [];
            if ($dupMode === 'update' || empty($odp->total_ports) || $odp->total_ports != $data['total_ports']) {
                $odpUpdates['total_ports'] = $data['total_ports'];
            }
            if ($dupMode === 'update' || empty($odp->odc_id)) {
                $odpUpdates['odc_id'] = $odc->id;
            }
            if ($dupMode === 'update' || empty($odp->area_id)) {
                $odpUpdates['area_id'] = $area->id;
            }
            $parsedLat = self::parseCoordinate($data['latitude'] ?? null, 'lat');
            if ($parsedLat !== null && ($dupMode === 'update' || empty($odp->latitude))) {
                $odpUpdates['latitude'] = $parsedLat;
            }
            $parsedLng = self::parseCoordinate($data['longitude'] ?? null, 'lng');
            if ($parsedLng !== null && ($dupMode === 'update' || empty($odp->longitude))) {
                $odpUpdates['longitude'] = $parsedLng;
            }
            if (!empty($odpUpdates)) {
                $odp->update($odpUpdates);
            }
        }

        // 6. Pre-generate seluruh port fisik ODP (1..total_ports) sebagai available jika belum terdaftar
        $targetTotalPorts = max((int)$odp->total_ports, (int)$data['total_ports']);
        for ($p = 1; $p <= $targetTotalPorts; $p++) {
            OdpPort::firstOrCreate(
                ['odp_id' => $odp->id, 'port_number' => $p],
                ['status' => 'available']
            );
        }

        // Simpan / update status nomor port spesifik dari file jika port_number diisi
        $odpPort = null;
        if (!empty($data['port_number']) && (int)$data['port_number'] > 0) {
            $odpPort = OdpPort::updateOrCreate(
                ['odp_id' => $odp->id, 'port_number' => (int)$data['port_number']],
                ['status' => $data['port_status']]
            );
        }

        // 7. Connect Customer & ONT if provided
        if (!empty($data['customer_id']) && !empty($data['serial_number'])) {
            $portToUse = !empty($data['port_number']) && (int)$data['port_number'] > 0 ? (int)$data['port_number'] : null;
            if (!$portToUse) {
                $firstAvail = OdpPort::where('odp_id', $odp->id)->where('status', 'available')->orderBy('port_number')->first();
                $portToUse = $firstAvail ? $firstAvail->port_number : 1;
            }
            if (!$odpPort) {
                $odpPort = OdpPort::updateOrCreate(
                    ['odp_id' => $odp->id, 'port_number' => $portToUse],
                    ['status' => 'used']
                );
            }
            $ont = Ont::where('serial_number', $data['serial_number'])->first();
            if ($ont) {
                $ont->update([
                    'customer_id' => $data['customer_id'],
                    'odp_id' => $odp->id,
                    'odp_port_id' => $odpPort?->id,
                    'port_number' => $portToUse,
                    'mac_address' => self::sanitizeMacAddress($data['mac_address'] ?? null) ?: $ont->mac_address,
                    'vlan_id' => $data['vlan_id'] ?: $ont->vlan_id,
                    'status' => 'active',
                ]);
            } else {
                Ont::create([
                    'customer_id' => $data['customer_id'],
                    'serial_number' => $data['serial_number'],
                    'mac_address' => self::sanitizeMacAddress($data['mac_address'] ?? null),
                    'odp_id' => $odp->id,
                    'odp_port_id' => $odpPort?->id,
                    'port_number' => $portToUse,
                    'area_id' => $area->id,
                    'vlan_id' => $data['vlan_id'],
                    'brand' => 'ZTE',
                    'model' => 'F609',
                    'status' => 'active',
                ]);
            }
        }

        // Sinkronisasi jumlah port terpakai di ODP (hitung dari port yang berstatus used atau ont aktif)
        $usedCount = OdpPort::where('odp_id', $odp->id)->where('status', 'used')->count();
        if ($usedCount === 0) {
            $usedCount = Ont::where('odp_id', $odp->id)->count();
        }
        $odp->update([
            'used_ports' => $usedCount,
            'status' => ($usedCount >= $odp->total_ports) ? 'full' : 'active'
        ]);

        return 'inserted';
    }

    protected function importSurveyItem(array $data, string $dupMode): string
    {
        // Otomatis buat / temukan ODP jika belum ada
        if (empty($data['odp_id']) && !empty($data['odp_name'])) {
            $cust = Customer::find($data['customer_id']);
            $odpObj = self::getOrCreateOdp(
                $data['odp_name'],
                $cust?->area_id,
                $cust?->latitude ? (float)$cust->latitude : null,
                $cust?->longitude ? (float)$cust->longitude : null,
                (int)($data['port_number'] ?? 1)
            );
            if ($odpObj) {
                $data['odp_id'] = $odpObj->id;
            }
        }
        unset($data['odp_name']);

        // Avoid duplicate survey on same customer and same date
        $existing = Survey::where('customer_id', $data['customer_id'])
            ->whereDate('survey_date', $data['survey_date'])
            ->first();

        if ($existing && $dupMode === 'skip') {
            return 'skipped';
        }

        Survey::create($data);

        // Update customer status to survey
        Customer::where('id', $data['customer_id'])->update(['status' => 'survey']);

        return 'inserted';
    }

    protected function importInstallationItem(array $data, string $dupMode): string
    {
        // Otomatis buat / temukan ODP jika belum ada
        if (empty($data['odp_id']) && !empty($data['odp_name'])) {
            $cust = Customer::find($data['customer_id']);
            $odpObj = self::getOrCreateOdp(
                $data['odp_name'],
                $cust?->area_id,
                $cust?->latitude ? (float)$cust->latitude : null,
                $cust?->longitude ? (float)$cust->longitude : null,
                (int)($data['port_number'] ?? 1)
            );
            if ($odpObj) {
                $data['odp_id'] = $odpObj->id;
            }
        }
        unset($data['odp_name']);

        $sn = $data['serial_number'];
        $ont = Ont::where('serial_number', $sn)->first();

        $photoRedaman = $this->resolvePhotoPath($data['photo_redaman'] ?? null, 'onts');
        $photoOdp = $this->resolvePhotoPath($data['photo_odp'] ?? null, 'onts');
        $photoOnt = $this->resolvePhotoPath($data['photo_ont'] ?? null, 'onts');
        $photoCustomer = $this->resolvePhotoPath($data['photo_customer'] ?? null, 'onts');
        $photoInstallation = $this->resolvePhotoPath($data['photo_installation'] ?? null, 'onts');

        if ($ont) {
            if ($dupMode === 'skip') {
                return 'skipped';
            }
            $ont->update([
                'customer_id' => $data['customer_id'],
                'odp_id' => $data['odp_id'],
                'port_number' => $data['port_number'],
                'rx_power' => $data['rx_power'],
                'pppoe_user' => $data['pppoe_user'],
                'pppoe_password' => $data['pppoe_password'],
                'vlan_id' => $data['vlan_id'],
                'access_mode' => $data['access_mode'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'photo_redaman' => $photoRedaman ?: $ont->photo_redaman,
                'photo_odp' => $photoOdp ?: $ont->photo_odp,
                'photo_ont' => $photoOnt ?: $ont->photo_ont,
                'photo_customer' => $photoCustomer ?: $ont->photo_customer,
                'photo_installation' => $photoInstallation ?: $ont->photo_installation,
                'status' => 'active',
            ]);
        } else {
            Ont::create([
                'serial_number' => $sn,
                'customer_id' => $data['customer_id'],
                'odp_id' => $data['odp_id'],
                'port_number' => $data['port_number'],
                'rx_power' => $data['rx_power'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'mac_address' => $data['mac_address'],
                'pppoe_user' => $data['pppoe_user'],
                'pppoe_password' => $data['pppoe_password'],
                'vlan_id' => $data['vlan_id'],
                'access_mode' => $data['access_mode'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'photo_redaman' => $photoRedaman,
                'photo_odp' => $photoOdp,
                'photo_ont' => $photoOnt,
                'photo_customer' => $photoCustomer,
                'photo_installation' => $photoInstallation,
                'status' => 'active',
            ]);
        }

        // Mark port as used
        if ($data['odp_id'] && $data['port_number']) {
            OdpPort::updateOrCreate(
                ['odp_id' => $data['odp_id'], 'port_number' => $data['port_number']],
                ['status' => 'used']
            );
            $odp = Odp::find($data['odp_id']);
            if ($odp) {
                $used = Ont::where('odp_id', $odp->id)->count();
                $odp->update([
                    'used_ports' => $used,
                    'status' => ($used >= $odp->total_ports) ? 'full' : 'active'
                ]);
            }
        }

        // Update customer activation & audit flag
        Customer::where('id', $data['customer_id'])->update([
            'status' => 'active',
            'activation_date' => $data['activation_date'],
            'is_audited' => $data['is_audited'],
        ]);

        // Auto-register ONT in CustomerInstalledMaterial
        if (!empty($sn) && !empty($data['customer_id'])) {
            $brand = trim($data['brand'] ?? 'ONT');
            $model = trim($data['model'] ?? 'Modem');
            $ontDeviceName = "ONT {$brand} {$model}";

            $ontMat = Material::where(function($q) use ($brand) {
                $q->where('category', 'like', '%ont%')
                  ->orWhere('category', 'like', '%modem%')
                  ->orWhere('name', 'like', "%{$brand}%");
            })->first();

            CustomerInstalledMaterial::updateOrCreate(
                [
                    'customer_id' => $data['customer_id'],
                    'serial_number' => $sn,
                ],
                [
                    'material_id' => $ontMat?->id,
                    'item_name' => $ontDeviceName,
                    'quantity' => 1,
                    'unit' => 'unit',
                    'installed_at' => $data['activation_date'] ?? now(),
                    'installation_source' => 'migration',
                    'status' => 'installed',
                    'notes' => "Modem ONT aktif pelanggan (SN: {$sn}) dari migrasi instalasi",
                ]
            );
        }

        return 'inserted';
    }

    protected function importInvoiceItem(array $data, string $dupMode): string
    {
        $invNum = $data['invoice_number'];
        $existing = null;
        if ($invNum) {
            $existing = Invoice::where('invoice_number', $invNum)->first();
        }

        if ($existing) {
            if ($dupMode === 'skip') {
                return 'skipped';
            }
            $existing->update([
                'amount' => $data['amount'],
                'status' => $data['status'],
                'due_date' => $data['due_date'],
                'issued_date' => $data['issued_date'],
                'billing_type' => $data['billing_type'],
                'notes' => $data['notes'],
            ]);
            return 'updated';
        }

        Invoice::create([
            'invoice_number' => $invNum,
            'customer_id' => $data['customer_id'],
            'period_month' => $data['period_month'],
            'period_year' => $data['period_year'],
            'amount' => $data['amount'],
            'due_date' => $data['due_date'],
            'issued_date' => $data['issued_date'],
            'status' => $data['status'],
            'billing_type' => $data['billing_type'],
            'notes' => $data['notes'],
        ]);

        return 'inserted';
    }

    protected function importPaymentItem(array $data, string $dupMode): string
    {
        $ref = $data['reference_number'];
        if ($ref) {
            $existing = Payment::where('reference_number', $ref)->first();
            if ($existing && $dupMode === 'skip') {
                return 'skipped';
            }
        }

        $invoice = Invoice::findOrFail($data['invoice_id']);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $ref,
            'collected_by' => $data['collected_by'],
            'notes' => $data['notes'],
            'status' => 'verified',
        ]);

        // Auto reconcile invoice status based on total paid
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid >= $invoice->amount) {
            $invoice->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partial']);
        }

        return 'inserted';
    }

    protected function validateEmployeeRow($row, &$errors, &$warnings, &$mapped, $areasCache, $employeesCache, $employeesIakCache, $options, $areasNormalizedCache = [])
    {
        $name = trim($row['nama_lengkap'] ?? $row['nama'] ?? $row['name'] ?? '');
        $email = trim(strtolower($row['email'] ?? $row['alamat_email'] ?? ''));
        $iak = trim($row['nomor_iak'] ?? $row['iak_number'] ?? $row['nip'] ?? $row['no_iak'] ?? '');
        $position = trim($row['posisi_jabatan'] ?? $row['posisi'] ?? $row['jabatan'] ?? $row['position'] ?? '');
        $phone = trim($row['nomor_telepon'] ?? $row['phone'] ?? $row['telepon'] ?? $row['no_hp'] ?? $row['nomor_hp'] ?? $row['no_wa'] ?? '');
        $areaName = trim($row['nama_area'] ?? $row['area'] ?? $row['cabang'] ?? $row['wilayah'] ?? '');
        $joinDateStr = trim($row['tgl_bergabung'] ?? $row['tanggal_bergabung'] ?? $row['join_date'] ?? $row['tgl_masuk'] ?? '');
        $empType = trim($row['tipe_karyawan'] ?? $row['employee_type'] ?? $row['tipe_pegawai'] ?? $row['status_karyawan'] ?? 'Tetap');
        $baseSalary = $row['gaji_pokok'] ?? $row['base_salary'] ?? $row['gaji'] ?? 0;
        $payMethod = trim($row['metode_gaji'] ?? $row['payment_method'] ?? $row['metode_pembayaran'] ?? 'Transfer Bank');
        $address = trim($row['alamat'] ?? $row['address'] ?? $row['domisili'] ?? '');
        
        $createUserRaw = strtolower(trim($row['buat_akun_login'] ?? $row['create_user'] ?? 'ya'));
        $createUser = !in_array($createUserRaw, ['tidak', 'no', 'false', '0', 'n', 't']);
        $initialPassword = trim($row['password_login'] ?? $row['password'] ?? $row['initial_password'] ?? '');

        // Bank / E-Wallet fields
        $bankName = trim($row['nama_bank'] ?? $row['bank_name'] ?? $row['bank'] ?? $row['ewallet'] ?? $row['nama_ewallet'] ?? '');
        $bankAccountNum = trim($row['nomor_rekening'] ?? $row['no_rekening'] ?? $row['bank_account_number'] ?? $row['rekening'] ?? $row['no_rek'] ?? $row['nomor_dana'] ?? $row['no_dana'] ?? $row['nomor_gopay'] ?? $row['no_gopay'] ?? $row['account_number'] ?? '');
        $bankAccountHolder = trim($row['nama_pemilik_rekening'] ?? $row['nama_pemilik'] ?? $row['bank_account_holder'] ?? $row['pemilik_rekening'] ?? $row['atas_nama'] ?? $row['an_rekening'] ?? $row['account_holder'] ?? '');

        // Auto-assign bank account holder with employee name if account number is filled but holder name is empty
        if (!empty($bankAccountNum) && empty($bankAccountHolder)) {
            $bankAccountHolder = $name;
        }

        if (!empty($bankAccountNum) && empty($bankName)) {
            $warnings[] = "Nomor rekening/DANA ({$bankAccountNum}) terisi tetapi Nama Bank / E-Wallet kosong.";
        }

        if (empty($name)) {
            $errors[] = 'Nama Lengkap Karyawan wajib diisi.';
        }

        if (empty($email)) {
            $errors[] = 'Email Karyawan wajib diisi (sebagai identitas & akun login).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email '{$email}' tidak valid.";
        } else {
            // Check existing employee by email
            if (isset($employeesCache[$email])) {
                if (($options['duplicate_mode'] ?? 'skip') === 'update') {
                    $warnings[] = "Karyawan dengan email '{$email}' sudah ada; data profil akan diperbarui.";
                } else {
                    $warnings[] = "Karyawan dengan email '{$email}' sudah terdaftar; baris ini akan dilewati.";
                }
            }
        }

        // Check duplicate IAK
        if (!empty($iak) && isset($employeesIakCache[strtolower($iak)])) {
            $existingId = $employeesIakCache[strtolower($iak)];
            $currentEmpId = $employeesCache[$email] ?? null;
            if (!$currentEmpId || $existingId != $currentEmpId) {
                $warnings[] = "Nomor IAK/NIP '{$iak}' telah digunakan oleh karyawan lain.";
            }
        }

        // Area check (flexible matching: 'BC-3' matches 'BC 3')
        $areaId = null;
        if (!empty($areaName)) {
            $areaId = self::resolveAreaIdFromCache($areaName, $areasCache, $areasNormalizedCache);
            if (!$areaId) {
                if (!empty($options['auto_create_areas'])) {
                    $warnings[] = "Area '{$areaName}' belum ada di database; akan otomatis dibuatkan baru.";
                } else {
                    $errors[] = "Area penempatan '{$areaName}' tidak ditemukan di database.";
                }
            }
        }

        // Join Date & Tenure preview
        $joinDate = null;
        if (!empty($joinDateStr) && $joinDateStr !== '-') {
            $parsedDate = self::parseFlexibleDate($joinDateStr);
            if ($parsedDate) {
                $joinDate = $parsedDate;
                $c = \Carbon\Carbon::parse($joinDate);
                $now = \Carbon\Carbon::now()->startOfDay();
                if ($c->isAfter($now)) {
                    $warnings[] = "Tanggal bergabung ({$joinDate}) di masa depan (status: Akan Mulai).";
                }
            } else {
                $errors[] = "Format tanggal bergabung '{$joinDateStr}' tidak valid (gunakan format YYYY-MM-DD atau format tanggal Excel).";
            }
        }

        // Clean salary
        if (is_string($baseSalary)) {
            $cleanSalary = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $baseSalary));
            $baseSalary = (float) $cleanSalary;
        }

        $mapped = [
            'name' => $name,
            'email' => $email,
            'iak_number' => $iak ?: null,
            'position' => $position ?: null,
            'phone' => $phone ?: null,
            'area_id' => $areaId,
            'area_name' => $areaName ?: null,
            'join_date' => $joinDate,
            'employee_type' => $empType ?: 'Tetap',
            'base_salary' => (float) $baseSalary,
            'payment_method' => $payMethod ?: 'Transfer Bank',
            'bank_name' => $bankName ?: null,
            'bank_account_number' => $bankAccountNum ?: null,
            'bank_account_holder' => $bankAccountHolder ?: null,
            'address' => $address ?: null,
            'create_user' => $createUser,
            'initial_password' => $initialPassword ?: 'password123',
        ];
    }

    protected function importEmployeeItem(array $data, string $dupMode, bool $autoCreateArea): string
    {
        $email = strtolower($data['email']);
        $existing = \App\Models\Employee::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existing && $dupMode === 'skip') {
            return 'skipped';
        }

        // Resolve area_id flexibly
        $areaId = $data['area_id'] ?? null;
        if (!$areaId && !empty($data['area_name'])) {
            $matchedArea = self::findAreaByFlexibleName($data['area_name']);
            if ($matchedArea) {
                $areaId = $matchedArea->id;
            } elseif ($autoCreateArea) {
                $area = Area::firstOrCreate(['name' => trim($data['area_name'])]);
                $areaId = $area->id;
            }
        }

        $payload = [
            'name' => $data['name'],
            'iak_number' => $data['iak_number'] ?: null,
            'position' => $data['position'] ?: null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'area_id' => $areaId,
            'join_date' => $data['join_date'] ?: null,
            'employee_type' => $data['employee_type'] ?: 'Tetap',
            'base_salary' => $data['base_salary'] ?? 0,
            'payment_method' => $data['payment_method'] ?: 'Transfer Bank',
            'bank_name' => $data['bank_name'] ?: null,
            'bank_account_number' => $data['bank_account_number'] ?: null,
            'bank_account_holder' => $data['bank_account_holder'] ?: null,
            'address' => $data['address'] ?: null,
        ];

        $isUpdated = false;
        if ($existing) {
            $existing->update($payload);
            $employee = $existing;
            $isUpdated = true;
        } else {
            $employee = \App\Models\Employee::create($payload);
        }

        // Auto Create or Sync User Account
        if (!empty($data['create_user'])) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            $initialPassword = !empty($data['initial_password']) ? $data['initial_password'] : 'password123';
            $mappedRole = !empty($data['position']) ? \App\Http\Controllers\EmployeeController::mapPositionToRole($data['position']) : 'cs';

            if (!$user) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => $mappedRole,
                    'password' => bcrypt($initialPassword),
                    'phone' => $data['phone'] ?: null,
                    'area_id' => $areaId,
                    'bank_name' => $data['bank_name'] ?: null,
                    'bank_account_number' => $data['bank_account_number'] ?: null,
                    'bank_account_holder' => $data['bank_account_holder'] ?: null,
                ]);
            } else {
                $user->update([
                    'name' => $data['name'],
                    'role' => $mappedRole,
                    'phone' => $data['phone'] ?: ($user->phone ?? null),
                    'area_id' => $areaId,
                    'bank_name' => $data['bank_name'] ?: ($user->bank_name ?? null),
                    'bank_account_number' => $data['bank_account_number'] ?: ($user->bank_account_number ?? null),
                    'bank_account_holder' => $data['bank_account_holder'] ?: ($user->bank_account_holder ?? null),
                ]);
            }

            // Sync role based on position
            if (!empty($data['position'])) {
                $roleName = strtolower(trim($data['position']));
                \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $user->syncRoles([$roleName]);
            }
        }

        return $isUpdated ? 'updated' : 'inserted';
    }
}
