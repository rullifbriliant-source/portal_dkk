<?php
/**
 * Shared SDM name matching — SINGLE SOURCE OF TRUTH.
 * Dipakai oleh:
 *   - admin/crud/sdmk.php (STEP 4 matching import SDMK per faskes)
 *   - admin/crud/sdmk_kecamatan_rekap.php (import rekap SDMK per kecamatan)
 * JANGAN duplikasi fungsi ini di file lain — require file ini.
 */
if (!function_exists('normalizeNama')) {
    function normalizeNama($s){
        $s = trim((string)$s);
        $s = preg_replace('/\s+/', ' ', $s);
        $s = strtolower($s);
        $s = preg_replace('/^[\-\•\*\s]+/', '', $s);
        $s = preg_replace('/^[a-z]\.\s*/', '', $s);
        $s = preg_replace('/^\d+[\.\)]\s*/', '', $s);
        $s = trim($s);
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }
}

if (!function_exists('sdm_match_item')) {
    /**
     * Cocokkan nama item mentah ke master via exact-match nama ternormalisasi.
     * @param string $rawName  teks dari form/file
     * @param array  $mapNorm  [normalized_name => id|array(id,...)]
     * @return array ['status'=>'ok','id'=>int]
     *             | ['status'=>'ambigu','candidates'=>[ids]]
     *             | ['status'=>'notfound']
     */
    function sdm_match_item($rawName, $mapNorm){
        $norm = normalizeNama($rawName);
        if ($norm === '' || !isset($mapNorm[$norm])) {
            return ['status' => 'notfound'];
        }
        $hit = $mapNorm[$norm];
        if (is_array($hit)) {
            if (count($hit) === 1) {
                return ['status' => 'ok', 'id' => (int)$hit[0]];
            }
            return ['status' => 'ambigu', 'candidates' => array_map('intval', $hit)];
        }
        return ['status' => 'ok', 'id' => (int)$hit];
    }
}

if (!function_exists('sdm_suggest_similar')) {
    /**
     * Saran nama master paling mirip (peringatan/saran saja, TIDAK auto-pakai).
     * @return array|null ['name'=>..., 'score'=>...] bila skor >= threshold
     */
    function sdm_suggest_similar($rawName, $existingNames, $threshold = 75){
        $bestName = '';
        $bestPct = 0;
        foreach ($existingNames as $en) {
            similar_text(strtolower(trim((string)$rawName)), strtolower((string)$en), $pct);
            if ($pct > $bestPct) { $bestPct = $pct; $bestName = $en; }
        }
        if ($bestPct >= $threshold && $bestName !== '') {
            return ['name' => $bestName, 'score' => (int)round($bestPct)];
        }
        return null;
    }
}
