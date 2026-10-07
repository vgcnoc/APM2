<template>
    <AppLayout title="Laporan Keuangan Keseluruhan" subtitle="Master Dashboard Keuangan">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Filter Bulan & Tahun -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-3xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Periode Laporan</h2>
                        <p class="text-xs text-gray-500">Pilih bulan dan tahun</p>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <select v-model="form.month" class="block w-full sm:w-32 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-sm font-medium focus:ring-blue-500 focus:border-blue-500">
                        <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                    </select>
                    <select v-model="form.year" class="block w-full sm:w-28 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-sm font-medium focus:ring-blue-500 focus:border-blue-500">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button @click="applyFilter" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition-all hover:shadow">
                        Filter
                    </button>
                </div>
            </div>

            <!-- Grand Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Omzet -->
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-indigo-100 text-sm font-semibold mb-1 uppercase tracking-wider">Total Pemasukan (Omzet)</p>
                    <h3 class="text-3xl font-black mb-4">Rp {{ formatRupiah(summary.total_omzet) }}</h3>
                    <div class="flex flex-col gap-1 text-xs text-indigo-50 font-medium">
                        <div class="flex justify-between border-t border-indigo-400/30 pt-2">
                            <span>Pelanggan Reguler:</span>
                            <span>Rp {{ formatRupiah(summary.omzet_customer) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Reseller Voucher:</span>
                            <span>Rp {{ formatRupiah(summary.omzet_reseller) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pengeluaran -->
                <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-rose-100 text-sm font-semibold mb-1 uppercase tracking-wider">Total Pengeluaran Kas</p>
                    <h3 class="text-3xl font-black mb-4">Rp {{ formatRupiah(summary.total_pengeluaran) }}</h3>
                    <div class="flex justify-between border-t border-rose-400/30 pt-2 mt-auto text-xs text-rose-50 font-medium">
                        <span>Lihat detail di Buku Kas Umum</span>
                        <span>↓</span>
                    </div>
                </div>

                <!-- Laba Bersih -->
                <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-20 rounded-full blur-xl animate-pulse"></div>
                    <p class="text-emerald-100 text-sm font-semibold mb-1 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-200" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A12.014 12.014 0 0010.378 1l-.156.003a11.967 11.967 0 00-6.19 2.502L3.6 3.902a1 1 0 00-.472.934L3.6 15a1 1 0 00.563.896l5.378 2.534c.185.087.39.087.575 0l5.378-2.534A1 1 0 0016.4 15l.473-10.164a1 1 0 00-.473-.934l-.431-.397a11.96 11.96 0 00-4.669-2.459zm-1.896 4.398a1 1 0 00-1.229.588l-2 5a1 1 0 001.858.742L8.5 10.428l2.036-1.018a1 1 0 00.32-1.637l-1.452-2.324z" clip-rule="evenodd"></path></svg>
                        Laba Bersih Perusahaan
                    </p>
                    <h3 class="text-3xl font-black mb-4">Rp {{ formatRupiah(summary.laba_bersih) }}</h3>
                    <div class="flex justify-between border-t border-emerald-400/30 pt-2 text-xs text-emerald-50 font-medium">
                        <span>(Omzet - Pengeluaran)</span>
                    </div>
                </div>
                
                <!-- Piutang Keseluruhan -->
                <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-amber-100 text-sm font-semibold mb-1 uppercase tracking-wider">Total Piutang Berjalan</p>
                    <h3 class="text-3xl font-black mb-4">Rp {{ formatRupiah(summary.total_piutang) }}</h3>
                    <div class="flex flex-col gap-1 text-xs text-amber-50 font-medium">
                        <div class="flex justify-between border-t border-amber-400/30 pt-2">
                            <span>Tunggakan Pelanggan:</span>
                            <span>Rp {{ formatRupiah(summary.piutang_customer) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Kasbon Reseller:</span>
                            <span>Rp {{ formatRupiah(summary.piutang_reseller) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Grafik -->
                <div class="lg:col-span-2 bg-white p-6 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100">
                    <h3 class="text-base font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
                        Grafik Pendapatan & Pengeluaran (6 Bulan Terakhir)
                    </h3>
                    <div class="h-80 w-full relative z-10">
                        <apexchart type="area" height="100%" :options="chartOptions" :series="chartSeries"></apexchart>
                    </div>
                </div>

                <!-- KPI Penagih -->
                <div class="bg-white p-6 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 flex flex-col">
                    <h3 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                        Papan Skor Penagih (Bulan Ini)
                    </h3>
                    <div v-if="kpi.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-400 py-8">
                        <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <p class="text-sm font-medium">Belum ada setoran penagih bulan ini.</p>
                    </div>
                    <ul v-else class="space-y-4">
                        <li v-for="(person, idx) in kpi" :key="idx" class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100 hover:border-amber-200 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                    {{ idx + 1 }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ person.name }}</p>
                                    <p class="text-xs text-gray-500">{{ person.count }} Transaksi</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-emerald-600">Rp {{ formatRupiah(person.total_collected) }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Buku Kas Umum (Unified Ledger) -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                            Buku Kas Umum (General Ledger)
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Rekapitulasi riwayat keluar masuk uang bulan ini</p>
                    </div>
                    <button @click="showExpenseModal = true" class="inline-flex items-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-xl shadow-sm transition-all hover:shadow text-sm font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Catat Pengeluaran
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipe / Kategori</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Mutasi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="item in ledger" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(item.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="item.type === 'income'" class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-emerald-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                                        Pemasukan ({{ item.category === 'reseller' ? 'Reseller' : 'Pelanggan' }})
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 bg-rose-50 text-rose-700 px-2.5 py-1 rounded-lg text-xs font-bold border border-rose-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                                        Pengeluaran Kas
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900 font-medium">{{ item.description }}</p>
                                    <p class="text-xs text-gray-500">Oleh: {{ item.user }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <p v-if="item.type === 'income'" class="text-sm font-black text-emerald-600">+ Rp {{ formatRupiah(item.amount) }}</p>
                                    <p v-else class="text-sm font-black text-rose-600">- Rp {{ formatRupiah(item.amount) }}</p>
                                </td>
                            </tr>
                            <tr v-if="ledger.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-medium">Belum ada transaksi di bulan ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Modal Form Pengeluaran -->
        <div v-if="showExpenseModal" class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showExpenseModal = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-gray-100">
                    <div class="bg-gray-50/50 px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center gap-2">
                            <span class="bg-rose-100 text-rose-600 p-1.5 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </span>
                            Catat Pengeluaran Baru
                        </h3>
                        <button @click="showExpenseModal = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="px-6 py-5">
                        <form @submit.prevent="submitExpense" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Pengeluaran</label>
                                <input type="date" v-model="expenseForm.expense_date" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nominal (Rp)</label>
                                <input type="number" v-model="expenseForm.amount" min="1" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500 font-bold text-lg" placeholder="Contoh: 150000" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Keterangan / Tujuan Pengeluaran</label>
                                <textarea v-model="expenseForm.description" rows="2" class="mt-1 block w-full border-gray-300 rounded-xl shadow-sm focus:ring-rose-500 focus:border-rose-500" placeholder="Contoh: Beli kabel LAN, bensin teknisi, dll" required></textarea>
                            </div>
                            
                            <div class="pt-4 flex justify-end gap-3">
                                <button type="button" @click="showExpenseModal = false" class="bg-white py-2.5 px-5 border border-gray-300 rounded-xl shadow-sm text-sm font-bold text-gray-700 hover:bg-gray-50 transition-all">
                                    Batal
                                </button>
                                <button type="submit" :disabled="expenseForm.processing" class="inline-flex justify-center py-2.5 px-5 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-all disabled:opacity-50">
                                    {{ expenseForm.processing ? 'Menyimpan...' : 'Simpan Pengeluaran' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    month: Number,
    year: Number,
    summary: Object,
    chartData: Array,
    ledger: Array,
    kpi: Array
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const currentYear = new Date().getFullYear();
const years = Array.from({length: 5}, (_, i) => currentYear - i);

const form = useForm({
    month: props.month,
    year: props.year
});

const applyFilter = () => {
    form.get(route('financial-reports.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};

const showExpenseModal = ref(false);
const expenseForm = useForm({
    amount: '',
    description: '',
    expense_date: new Date().toISOString().split('T')[0],
});

const submitExpense = () => {
    expenseForm.post(route('expenses.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showExpenseModal.value = false;
            expenseForm.reset();
        }
    });
};

// Setup Chart
const apexchart = VueApexCharts;

const chartOptions = computed(() => ({
    chart: {
        type: 'area',
        fontFamily: 'inherit',
        toolbar: { show: false },
        zoom: { enabled: false },
        parentHeightOffset: 0
    },
    colors: ['#4f46e5', '#10b981', '#f43f5e'],
    dataLabels: { enabled: false },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    xaxis: {
        categories: props.chartData.map(d => d.month),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#6b7280', fontSize: '12px', fontWeight: 500 }
        }
    },
    yaxis: {
        labels: {
            formatter: (val) => {
                if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + ' Jt';
                if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + ' Rb';
                return 'Rp ' + val;
            },
            style: { colors: '#6b7280', fontSize: '11px', fontWeight: 500 }
        }
    },
    grid: {
        borderColor: '#f3f4f6',
        strokeDashArray: 4,
        yaxis: { lines: { show: true } },
        xaxis: { lines: { show: false } },
        padding: { top: 0, right: 0, bottom: 0, left: 10 }
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
        offsetY: -20,
        markers: { radius: 12 },
        itemMargin: { horizontal: 10 }
    },
    tooltip: {
        y: { formatter: (val) => 'Rp ' + formatRupiah(val) }
    }
}));

const chartSeries = computed(() => [
    {
        name: 'Omzet Pelanggan',
        data: props.chartData.map(d => d.customer)
    },
    {
        name: 'Omzet Reseller',
        data: props.chartData.map(d => d.reseller)
    },
    {
        name: 'Pengeluaran',
        data: props.chartData.map(d => d.expense)
    }
]);
</script>
