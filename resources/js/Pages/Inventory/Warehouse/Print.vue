<template>
    <div class="min-h-screen bg-slate-100 p-4 sm:p-8 font-sans print:p-0 print:bg-white text-slate-800 print:text-black">
        <!-- Floating Toolbar (Disembunyikan saat dicetak) -->
        <div class="max-w-6xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 bg-white/90 backdrop-blur-md p-4 rounded-2xl shadow-sm border border-slate-200 print:hidden">
            <div class="flex items-center gap-2">
                <Link 
                    href="/warehouse-stocks" 
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>Kembali ke Inventory</span>
                </Link>

                <div class="h-5 w-px bg-slate-200"></div>

                <!-- Toggle Orientasi Halaman -->
                <button 
                    type="button" 
                    @click="isLandscape = !isLandscape" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold transition-all"
                    :class="[isLandscape ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-slate-50 border-slate-200 text-slate-700']"
                    title="Ubah Orientasi Kertas"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                    <span>Orientasi: {{ isLandscape ? 'Lanskap (Rekomendasi)' : 'Potret' }}</span>
                </button>

                <!-- Toggle Tanda Tangan Digital vs Basah -->
                <button 
                    type="button" 
                    @click="sigMode = (sigMode === 'digital' ? 'manual' : 'digital')" 
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-bold transition-all"
                    :class="[sigMode === 'digital' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-amber-50 border-amber-200 text-amber-700']"
                    title="Ganti Mode Tanda Tangan"
                >
                    <span>{{ sigMode === 'digital' ? '✍️ TTD Digital Otomatis' : '📝 TTD Fisik / Basah' }}</span>
                </button>
            </div>

            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="triggerPrint" 
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-xs font-black shadow-md shadow-indigo-500/20 hover:shadow-lg hover:shadow-indigo-500/30 transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Cetak Sekarang (Ctrl + P)</span>
                </button>
            </div>
        </div>

        <!-- Lembar Kertas Dokumen Laporan (Printable Paper) -->
        <div 
            class="mx-auto bg-white p-6 sm:p-10 rounded-3xl shadow-xl border border-slate-200 print:shadow-none print:border-none print:p-0 print:rounded-none transition-all print-page-container"
            :class="[isLandscape ? 'max-w-6xl page-landscape' : 'max-w-4xl page-portrait']"
        >
            <!-- 1. KOP SURAT RESMI (Letterhead) -->
            <div class="border-b-2 border-slate-900 pb-4 mb-5">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center text-white font-black text-2xl shadow-sm print:border print:border-slate-800 shrink-0">
                            {{ company?.name?.substring(0, 1) || 'V' }}
                        </div>
                        <div>
                            <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight uppercase leading-tight">
                                {{ company?.name || 'PT VIRUZS GLOBAL CONNECTION' }}
                            </h1>
                            <p v-if="company?.brand_name || company?.tagline" class="text-xs font-bold text-indigo-700 uppercase tracking-wider">
                                {{ company?.brand_name || 'VIZBILL' }} • {{ company?.tagline || 'Internet & Digital Infrastructure Services' }}
                            </p>
                            <p class="text-[11px] text-slate-600 leading-tight mt-0.5">
                                {{ company?.address || 'Desa Sirnajaya, Kec. Gunungguruh, Kab. Sukabumi, Jawa Barat' }}
                            </p>
                            <p class="text-[10px] text-slate-500 font-mono mt-0.5">
                                Telp: {{ company?.phone || '0823-1864-5178' }} • Email: {{ company?.email || 'support@viruzs.net' }} • Web: {{ company?.website || 'viruzs.net' }}
                            </p>
                        </div>
                    </div>

                    <!-- Badge Nomor Dokumen & Tanggal -->
                    <div class="text-right shrink-0">
                        <span class="inline-block px-2.5 py-1 bg-slate-900 text-white rounded-md text-[9px] font-black uppercase tracking-wider font-mono">
                            DOKUMEN RESMI LOGISTIK
                        </span>
                        <div class="text-xs font-bold text-slate-800 font-mono mt-1">
                            {{ meta?.report_number || 'INV-RPT' }}
                        </div>
                        <div class="text-[10px] text-slate-500 font-medium">
                            Dicetak: {{ formatFullDateTime(meta?.printed_at) }}
                        </div>
                    </div>
                </div>
                <div class="mt-3 border-t border-slate-300"></div>
            </div>

            <!-- 2. JUDUL DOKUMEN & DESKRIPSI -->
            <div class="text-center my-4">
                <h2 class="text-base sm:text-lg font-black text-slate-900 uppercase tracking-wider">
                    LAPORAN REKAPITULASI INVENTORI & STOK GUDANG
                </h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">
                    Data Posisi Persediaan Material Fisik di Seluruh Area Operasional
                </p>
            </div>

            <!-- 3. PARAMETER & METADATA LAPORAN -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs mb-5 print:bg-slate-50">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Cakupan Wilayah / Area:</span>
                    <span class="font-bold text-slate-900">
                        {{ selectedArea?.name ? '📍 ' + selectedArea.name : '🌐 Semua Area Gudang (' + (areas?.length || 0) + ' Area)' }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Filter Kategori:</span>
                    <span class="font-bold text-slate-900">
                        {{ filters?.category ? filters.category : 'Semua Kategori' }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Periode Tanggal:</span>
                    <span class="font-bold text-slate-900">
                        <span v-if="meta?.has_date_filter" class="text-indigo-800">
                            📅 {{ meta?.date_from || '...' }} s/d {{ meta?.date_to || '...' }}
                            <span class="text-[9px] block text-indigo-600 font-semibold font-mono">({{ meta?.date_filter_by === 'created_at' ? 'Didaftarkan' : (meta?.date_filter_by === 'updated_at' ? 'Terakhir Update' : 'Mutasi Stok') }})</span>
                        </span>
                        <span v-else class="text-slate-500">Semua Waktu</span>
                    </span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Kondisi Barang:</span>
                    <span class="font-bold text-slate-900">
                        {{ filters?.condition ? (filters.condition === 'layak' ? 'Layak Pakai' : filters.condition === 'tidak_layak' ? 'Tidak Layak' : 'Rusak / Afkir') : 'Semua Kondisi' }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Petugas Pelapor:</span>
                    <span class="font-bold text-slate-900">
                        👤 {{ meta?.signer_name || user?.name || 'Super Admin' }}
                    </span>
                </div>
            </div>

            <!-- 4. RINGKASAN EKSEKUTIF (KPI CARDS PRINT FRIENDLY) -->
            <div class="grid grid-cols-4 gap-3 mb-5 text-center">
                <div class="p-2.5 rounded-xl border border-slate-200 bg-white">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Varian Barang</p>
                    <p class="text-lg font-black text-slate-900 mt-0.5">{{ formatNumber(summary?.total || materials?.length || 0) }}</p>
                    <p class="text-[9px] text-slate-400">Master material</p>
                </div>
                <div class="p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/50">
                    <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Stok Tersedia</p>
                    <p class="text-lg font-black text-emerald-700 mt-0.5">{{ formatNumber(summary?.available || 0) }}</p>
                    <p class="text-[9px] text-emerald-600">Barang memiliki stok</p>
                </div>
                <div class="p-2.5 rounded-xl border border-rose-200 bg-rose-50/50">
                    <p class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">Stok Kosong / Habis</p>
                    <p class="text-lg font-black text-rose-700 mt-0.5">{{ formatNumber(summary?.out_of_stock || 0) }}</p>
                    <p class="text-[9px] text-rose-600">Perlu restock</p>
                </div>
                <div class="p-2.5 rounded-xl border border-indigo-200 bg-indigo-50/50">
                    <p class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">Status Kondisi</p>
                    <p class="text-xs font-black text-indigo-900 mt-1">
                        <span class="text-emerald-700 font-bold">L: {{ summary?.layak || 0 }}</span> • 
                        <span class="text-amber-700 font-bold">TL: {{ summary?.tidak_layak || 0 }}</span> • 
                        <span class="text-rose-700 font-bold">R: {{ summary?.rusak || 0 }}</span>
                    </p>
                    <p class="text-[9px] text-indigo-500">Layak / T.Layak / Rusak</p>
                </div>
            </div>

            <!-- 5. TABEL RINCIAN MATERIAL & STOK -->
            <div class="border border-slate-300 rounded-xl overflow-hidden mb-6">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[10px]">
                            <th class="py-2.5 px-3 text-center w-10">No</th>
                            <th class="py-2.5 px-3">Nama Barang / Material</th>
                            <th class="py-2.5 px-3">Kategori</th>
                            <th class="py-2.5 px-3 text-center">Satuan</th>
                            <th class="py-2.5 px-3 text-center">Kondisi</th>
                            <th class="py-2.5 px-3">Detail Stok per Area Gudang</th>
                            <th class="py-2.5 px-3 text-right">Total Fisik</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-sans">
                        <tr v-for="(item, index) in materials" :key="item.id" class="hover:bg-slate-50 transition-colors">
                            <td class="py-2 px-3 text-center font-mono text-slate-500 font-bold">{{ index + 1 }}</td>
                            <td class="py-2 px-3">
                                <div class="font-bold text-slate-900">{{ item.name }}</div>
                                <div v-if="item.supplier" class="text-[10px] text-slate-400">{{ item.supplier }}</div>
                            </td>
                            <td class="py-2 px-3">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-semibold border border-slate-200">
                                    {{ item.category || '-' }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-center font-mono font-bold text-slate-700 uppercase">
                                {{ item.unit || 'pcs' }}
                            </td>
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <span 
                                    class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider border"
                                    :class="{
                                        'bg-emerald-50 text-emerald-800 border-emerald-300': item.condition_label === 'Layak Pakai',
                                        'bg-amber-50 text-amber-800 border-amber-300': item.condition_label === 'Tidak Layak',
                                        'bg-rose-50 text-rose-800 border-rose-300': item.condition_label === 'Rusak'
                                    }"
                                >
                                    {{ item.condition_label }}
                                </span>
                            </td>
                            <td class="py-2 px-3">
                                <div v-if="item.stocks && item.stocks.length > 0" class="flex flex-wrap items-center gap-1.5 text-[10px]">
                                    <span 
                                        v-for="stk in item.stocks" 
                                        :key="stk.id"
                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded font-mono font-medium border"
                                        :class="[stk.stock > 0 ? 'bg-indigo-50 border-indigo-200 text-indigo-900' : 'bg-slate-50 border-slate-200 text-slate-400']"
                                    >
                                        <strong class="font-sans text-slate-700">{{ stk.area?.name || 'Area' }}:</strong>
                                        <span class="font-bold" :class="stk.stock > 0 ? 'text-indigo-700' : 'text-slate-400'">{{ formatNumber(stk.stock) }}</span>
                                    </span>
                                </div>
                                <span v-else class="text-[10px] text-slate-400 italic">Belum dialokasikan</span>
                                <div v-if="meta?.has_date_filter && item.has_period_activity" class="mt-1 flex items-center gap-1.5 text-[9px] font-mono">
                                    <span class="text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">📥 +{{ formatNumber(item.period_in || 0) }} {{ item.unit }}</span>
                                    <span class="text-rose-800 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">📤 -{{ formatNumber(item.period_out || 0) }} {{ item.unit }}</span>
                                </div>
                            </td>
                            <td class="py-2 px-3 text-right font-mono">
                                <span class="text-xs font-black" :class="getItemTotalStock(item) > 0 ? 'text-slate-900' : 'text-rose-600'">
                                    {{ formatNumber(getItemTotalStock(item)) }}
                                </span>
                                <span class="text-[9px] text-slate-400 uppercase font-sans ml-0.5">{{ item.unit }}</span>
                            </td>
                            <td class="py-2 px-3 text-center whitespace-nowrap">
                                <span 
                                    v-if="getItemTotalStock(item) <= 0" 
                                    class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-300"
                                >
                                    Habis
                                </span>
                                <span 
                                    v-else-if="getItemTotalStock(item) <= 20" 
                                    class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-300"
                                >
                                    Menipis
                                </span>
                                <span 
                                    v-else 
                                    class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-300"
                                >
                                    Tersedia
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!materials || materials.length === 0">
                            <td colspan="8" class="py-8 text-center text-slate-400 italic">
                                Tidak ada data material yang sesuai dengan kriteria cetak.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 6. BAGIAN TANGGAL & TANDA TANGAN PEMILIK AKUN -->
            <div class="mt-8 pt-4 border-t border-slate-200 break-inside-avoid">
                <div class="grid grid-cols-2 gap-8 items-start">
                    <!-- Kolom Kiri: Mengetahui (Pimpinan / Manager) -->
                    <div v-if="meta?.show_approver !== false" class="text-center">
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Mengetahui,</p>
                        <p class="text-xs text-slate-500 font-medium mb-16">{{ meta?.approver_title || 'Pimpinan / Manager Operasional' }}</p>
                        <div class="inline-block min-w-[200px] border-b-2 border-slate-900 pb-1">
                            <p class="text-xs font-bold text-slate-900 uppercase">( .................................................... )</p>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">Direktur / General Manager</p>
                    </div>
                    <div v-else></div>

                    <!-- Kolom Kanan: Dibuat Oleh (Pemilik Akun Login) -->
                    <div class="text-center ml-auto w-full sm:w-80">
                        <!-- Format Tempat & Tanggal Indonesia -->
                        <p class="text-xs text-slate-700 mb-1">
                            <span class="font-bold text-slate-900">{{ meta?.city || 'Sukabumi' }}</span>, 
                            <span class="font-semibold text-slate-800">{{ formatIndonesianDate(meta?.date) }}</span>
                        </p>
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Dibuat & Diverifikasi Oleh:</p>
                        
                        <!-- Area Tanda Tangan: Digital vs Basah -->
                        <div v-if="sigMode === 'digital'" class="my-2 p-2.5 rounded-2xl bg-indigo-50/50 border border-indigo-200/80 inline-flex flex-col items-center justify-center relative overflow-hidden shadow-2xs">
                            <div class="text-[9px] font-mono font-black text-indigo-700 uppercase tracking-widest flex items-center gap-1 mb-1">
                                <svg class="w-3 h-3 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>DIGITALLY SIGNED & VERIFIED</span>
                            </div>
                            
                            <!-- Calligraphic Electronic Signature Representation -->
                            <div class="font-serif italic text-xl font-bold text-indigo-900 tracking-wider py-1 transform -rotate-2 select-none">
                                {{ meta?.signer_name || user?.name || 'Super Admin' }}
                            </div>

                            <div class="text-[9px] text-indigo-500 font-mono mt-0.5">
                                Ref: {{ meta?.report_number || 'INV' }} • {{ meta?.date || '2026-11-20' }}
                            </div>
                        </div>
                        <div v-else class="h-20 flex items-center justify-center">
                            <span class="text-[10px] text-slate-300 italic">( Tanda Tangan Basah & Stempel )</span>
                        </div>

                        <!-- Nama Terang & Jabatan Pemilik Akun -->
                        <div class="border-b-2 border-slate-900 pb-1 mt-1 inline-block min-w-[200px]">
                            <p class="text-xs font-black text-slate-900 uppercase">
                                {{ meta?.signer_name || user?.name || 'Super Admin' }}
                            </p>
                        </div>
                        <p class="text-[11px] font-bold text-slate-600 mt-0.5">
                            {{ meta?.signer_role || 'Penanggung Jawab Gudang & Logistik' }}
                        </p>
                        <p class="text-[10px] text-slate-400 font-mono">
                            ID Akun: {{ user?.email || user?.username || 'admin@viruzs.net' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 7. FOOTER LEGALITAS DOKUMEN -->
            <div class="mt-8 pt-3 border-t border-slate-200 flex flex-wrap items-center justify-between text-[9px] text-slate-400 font-mono">
                <span>Dokumen dicetak resmi melalui Sistem APM2 (VIZBILL ERP) • Bukti inventori sah internal perusahaan.</span>
                <span>Halaman 1 / 1 • Cetak: {{ meta?.printed_at }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    materials: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    company: { type: Object, default: () => ({}) },
    user: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    selectedArea: { type: Object, default: () => null },
    summary: { type: Object, default: () => ({}) },
    meta: { type: Object, default: () => ({}) },
});

const isLandscape = ref(true);
const sigMode = ref(props.meta?.signature_mode || 'digital');

function getItemTotalStock(item) {
    if (!item?.stocks || !item.stocks.length) return 0;
    return item.stocks.reduce((acc, curr) => acc + Number(curr.stock || 0), 0);
}

function formatNumber(num) {
    return Number(num || 0).toLocaleString('id-ID');
}

const indonesianMonths = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

function formatIndonesianDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = d.getDate();
    const month = indonesianMonths[d.getMonth()] || '';
    const year = d.getFullYear();
    return `${day} ${month} ${year}`;
}

function formatFullDateTime(dtStr) {
    if (!dtStr) return '-';
    const d = new Date(dtStr);
    if (isNaN(d.getTime())) return dtStr;
    const day = d.getDate();
    const month = indonesianMonths[d.getMonth()] || '';
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day} ${month} ${year}, ${hours}:${minutes} WIB`;
}

function triggerPrint() {
    window.print();
}

onMounted(() => {
    // Otomatis tawarkan cetak jika parameter autoprint aktif
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
        setTimeout(() => {
            window.print();
        }, 600);
    }
});
</script>

<style scoped>
@media print {
    @page {
        margin: 8mm 10mm;
    }
    
    .page-landscape {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .page-portrait {
        width: 100% !important;
        max-width: 100% !important;
    }

    body {
        background-color: white !important;
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-page-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    /* Hindari potongan tabel di tengah baris */
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    thead {
        display: table-header-group;
    }
    tfoot {
        display: table-footer-group;
    }
}
</style>

