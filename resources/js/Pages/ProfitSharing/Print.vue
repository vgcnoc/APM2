<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-900 p-4 sm:p-8 font-sans print:p-0 print:bg-white text-slate-800">
        <!-- Floating Print Toolbar -->
        <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between print:hidden">
            <button 
                type="button" 
                @click="closeWindow" 
                class="px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 shadow-xs hover:bg-slate-50">
                ← Kembali
            </button>
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="triggerPrint" 
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-200 flex items-center gap-2">
                    <span>🖨️</span>
                    <span>Cetak Dokumen (PDF)</span>
                </button>
            </div>
        </div>

        <!-- Paper Container (A4 Style) -->
        <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-3xl shadow-lg border border-slate-200 print:shadow-none print:border-none print:p-6 print:rounded-none">
            <!-- Header Letterhead -->
            <div class="flex items-start justify-between pb-6 border-b-2 border-slate-900 gap-4">
                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 uppercase">
                        {{ company.name }}
                    </h1>
                    <p class="text-xs text-slate-500 max-w-md leading-relaxed">
                        {{ company.address }}
                    </p>
                    <p class="text-xs text-slate-500 font-mono">
                        Telp: {{ company.phone }} • Email: {{ company.email }}
                    </p>
                </div>
                <div class="text-right space-y-1">
                    <span class="inline-block px-3 py-1 bg-slate-100 rounded-full text-[10px] font-black uppercase text-slate-800 border border-slate-200 font-mono">
                        BUKTI REKAPITULASI BAGI HASIL
                    </span>
                    <p class="text-xs font-bold text-slate-900 font-mono mt-1">{{ settlement.settlement_number }}</p>
                    <p class="text-[11px] text-slate-500">Tanggal: {{ formatDate(settlement.created_at) }}</p>
                </div>
            </div>

            <!-- Settlement Title & Period -->
            <div class="my-6 text-center space-y-1">
                <h2 class="text-lg font-black uppercase text-slate-900 tracking-wider">
                    LAPORAN PENYALURAN PROFIT SHARING MITRA
                </h2>
                <p class="text-xs font-bold text-indigo-700">
                    Periode Tagihan: {{ settlement.period_label }}
                </p>
            </div>

            <!-- Partner & Agreement Info Box -->
            <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs mb-6">
                <div class="space-y-1">
                    <p class="text-[10px] uppercase font-bold text-slate-400">Pihak Kedua (Mitra / Investor):</p>
                    <p class="text-sm font-bold text-slate-900">{{ settlement.partnership?.partner_name }}</p>
                    <p class="text-slate-600">Wilayah / Target: <strong class="text-slate-900">{{ settlement.partnership?.target_display_name }}</strong></p>
                    <p class="text-slate-600 font-mono">Kontak: {{ settlement.partnership?.partner_phone || '-' }}</p>
                </div>
                <div class="space-y-1 text-right sm:text-left">
                    <p class="text-[10px] uppercase font-bold text-slate-400">Skema & Rekening Tujuan:</p>
                    <p class="text-sm font-bold text-slate-900">{{ calculation.scheme_name }}</p>
                    <p class="text-slate-600">Model: {{ calculation.scheme_model_label }}</p>
                    <p class="text-slate-800 font-mono font-bold" v-if="settlement.partnership?.bank_name">
                        {{ settlement.partnership.bank_name }} - {{ settlement.partnership.bank_account_number }} a/n {{ settlement.partnership.bank_account_name }}
                    </p>
                </div>
            </div>

            <!-- Main Calculation Table -->
            <div class="border border-slate-200 rounded-2xl overflow-hidden mb-6">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4">Komponen Perhitungan</th>
                            <th class="py-3 px-4 text-center">Volume / Dasar</th>
                            <th class="py-3 px-4 text-right">Nominal (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-mono">
                        <tr>
                            <td class="py-2.5 px-4 font-sans font-medium text-slate-700">Total Pelanggan Membayar (Lunas)</td>
                            <td class="py-2.5 px-4 text-center">{{ settlement.total_customers }} Pelanggan</td>
                            <td class="py-2.5 px-4 text-right">{{ settlement.total_paid_invoices }} Tagihan</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 font-sans font-bold text-slate-900">Total Omset Kotor Tagihan Pelanggan</td>
                            <td class="py-2.5 px-4 text-center">Gross Revenue</td>
                            <td class="py-2.5 px-4 text-right font-bold text-slate-900">{{ formatCurrency(settlement.total_gross_revenue) }}</td>
                        </tr>
                        <tr v-if="settlement.total_tax_deductions > 0">
                            <td class="py-2.5 px-4 font-sans text-slate-600">Potongan Pajak Resmi (PPN, BHP, USO)</td>
                            <td class="py-2.5 px-4 text-center">Negara</td>
                            <td class="py-2.5 px-4 text-right text-rose-600">- {{ formatCurrency(settlement.total_tax_deductions) }}</td>
                        </tr>
                        <tr v-if="settlement.total_operational_deductions > 0">
                            <td class="py-2.5 px-4 font-sans text-slate-600">Potongan Operasional & Pemeliharaan Jaringan</td>
                            <td class="py-2.5 px-4 text-center">Pemeliharaan</td>
                            <td class="py-2.5 px-4 text-right text-rose-600">- {{ formatCurrency(settlement.total_operational_deductions) }}</td>
                        </tr>
                        <tr class="bg-slate-50 font-bold">
                            <td class="py-2.5 px-4 font-sans text-slate-900">Dasar Perhitungan Omset Bersih</td>
                            <td class="py-2.5 px-4 text-center">Net Revenue</td>
                            <td class="py-2.5 px-4 text-right text-slate-900">{{ formatCurrency(settlement.net_revenue) }}</td>
                        </tr>
                        <tr class="bg-indigo-50/70 border-t-2 border-indigo-200">
                            <td class="py-3.5 px-4 font-sans font-black text-indigo-950 text-sm">
                                HAK BAGI HASIL MITRA (PENYALURAN)
                                <span class="block text-[11px] font-normal text-indigo-800 font-mono mt-0.5">{{ calculation.formula_detail }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-indigo-900">{{ calculation.partner_percentage_effective }}%</td>
                            <td class="py-3.5 px-4 text-right font-black text-indigo-900 text-base font-mono">{{ formatCurrency(settlement.partner_share_amount) }}</td>
                        </tr>
                        <tr class="bg-emerald-50/70">
                            <td class="py-3.5 px-4 font-sans font-black text-emerald-950 text-sm">
                                HAK PENDAPATAN BERSIH ISP
                                <span class="block text-[11px] font-normal text-emerald-800 font-mono mt-0.5">Setelah dikurangi bagi hasil mitra</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-emerald-900">{{ (100 - (calculation.partner_percentage_effective || 0)).toFixed(1) }}%</td>
                            <td class="py-3.5 px-4 text-right font-black text-emerald-900 text-base font-mono">{{ formatCurrency(settlement.isp_share_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Terbilang Banner -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs mb-8">
                <span class="font-bold text-slate-500 uppercase text-[10px] block">Terbilang Bagi Hasil Mitra:</span>
                <span class="font-bold text-slate-900 italic capitalize leading-relaxed">{{ terbilangRupiah(settlement.partner_share_amount) }}</span>
            </div>

            <!-- Signatures Section -->
            <div class="grid grid-cols-2 gap-8 text-xs text-center pt-6 border-t border-slate-200">
                <div class="space-y-16">
                    <p class="font-bold text-slate-700">Pihak Pertama (Penyedia ISP)<br><span class="font-normal text-slate-500">{{ company.name }}</span></p>
                    <div class="space-y-1">
                        <p class="font-bold text-slate-900 underline font-mono">( _______________________ )</p>
                        <p class="text-[11px] text-slate-500">Direktur / Finance Manager</p>
                    </div>
                </div>

                <div class="space-y-16">
                    <p class="font-bold text-slate-700">Pihak Kedua (Mitra Pengelola)<br><span class="font-normal text-slate-500">{{ settlement.partnership?.target_display_name }}</span></p>
                    <div class="space-y-1">
                        <p class="font-bold text-slate-900 underline font-mono">( {{ settlement.partnership?.partner_name }} )</p>
                        <p class="text-[11px] text-slate-500">Mitra Jaringan / Investor</p>
                    </div>
                </div>
            </div>

            <!-- Footer Stamp -->
            <div class="mt-10 pt-4 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                <span>Dokumen ini dicetak otomatis dan sah melalui Sistem Manajemen ISP.</span>
                <span>ID Settlement: {{ settlement.settlement_number }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';

const props = defineProps({
    settlement: { type: Object, required: true },
    calculation: { type: Object, required: true },
    company: { type: Object, default: () => ({}) },
});

const closeWindow = () => {
    window.close();
};

const triggerPrint = () => {
    window.print();
};

const formatCurrency = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

// Simple Terbilang generator in Indonesian
const terbilangRupiah = (number) => {
    const bilangan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
    const num = Math.floor(number);
    
    const konversi = (n) => {
        if (n < 12) return bilangan[n];
        if (n < 20) return konversi(n - 10) + ' belas';
        if (n < 100) return konversi(Math.floor(n / 10)) + ' puluh ' + konversi(n % 10);
        if (n < 200) return 'seratus ' + konversi(n - 100);
        if (n < 1000) return konversi(Math.floor(n / 100)) + ' ratus ' + konversi(n % 100);
        if (n < 2000) return 'seribu ' + konversi(n - 1000);
        if (n < 1000000) return konversi(Math.floor(n / 1000)) + ' ribu ' + konversi(n % 1000);
        if (n < 1000000000) return konversi(Math.floor(n / 1000000)) + ' juta ' + konversi(n % 1000000);
        if (n < 1000000000000) return konversi(Math.floor(n / 1000000000)) + ' milyar ' + konversi(n % 1000000000);
        return '';
    };

    const hasil = konversi(num).replace(/\s+/g, ' ').trim();
    return hasil ? `${hasil} rupiah` : 'nol rupiah';
};

onMounted(() => {
    // Optionally trigger auto print
});
</script>

<style scoped>
@media print {
    @page {
        size: A4;
        margin: 1.2cm;
    }
}
</style>

