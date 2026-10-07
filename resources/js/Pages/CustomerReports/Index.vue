<template>
    <AppLayout title="Laporan Keuangan Pelanggan" subtitle="Rekap khusus pelanggan reguler">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Filter Bulan & Tahun -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-3xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-sky-50 text-sky-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Periode Laporan</h2>
                        <p class="text-xs text-gray-500">Pilih bulan dan tahun</p>
                    </div>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <select v-model="form.month" class="block w-full sm:w-32 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-sm font-medium focus:ring-sky-500 focus:border-sky-500">
                        <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                    </select>
                    <select v-model="form.year" class="block w-full sm:w-28 border-gray-200 bg-gray-50 focus:bg-white rounded-xl text-sm font-medium focus:ring-sky-500 focus:border-sky-500">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <button @click="applyFilter" class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-sm transition-all hover:shadow">
                        Filter
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Omzet -->
                <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-sky-100 text-sm font-semibold mb-1 uppercase tracking-wider">Total Pemasukan Pelanggan</p>
                    <h3 class="text-3xl font-black mb-2">Rp {{ formatRupiah(summary.pemasukan) }}</h3>
                    <p class="text-xs text-sky-100 font-medium opacity-80">Bulan ini</p>
                </div>

                <!-- Piutang -->
                <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-3xl p-6 text-white shadow-lg relative overflow-hidden group">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl group-hover:scale-150 transition-transform duration-700"></div>
                    <p class="text-amber-100 text-sm font-semibold mb-1 uppercase tracking-wider">Total Piutang Berjalan</p>
                    <h3 class="text-3xl font-black mb-2">Rp {{ formatRupiah(summary.piutang) }}</h3>
                    <p class="text-xs text-amber-100 font-medium opacity-80">Tunggakan aktif pelanggan</p>
                </div>
            </div>

            <!-- Grafik -->
            <div class="bg-white p-6 rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100">
                <h3 class="text-base font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Tren Pemasukan Pelanggan (6 Bulan Terakhir)
                </h3>
                <div class="h-80 w-full relative z-10">
                    <apexchart type="area" height="100%" :options="chartOptions" :series="chartSeries"></apexchart>
                </div>
            </div>

            <!-- Riwayat Mutasi Pelanggan -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Riwayat Pembayaran Pelanggan
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Uang masuk dari pelanggan bulan ini</p>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tanggal & Waktu</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pelanggan / Invoice</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Metode & Penagih</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="item in ledger" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(item.date).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'}) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900 font-bold">{{ item.customer }}</p>
                                    <p class="text-xs text-gray-500">INV: {{ item.invoice_number }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <p class="text-sm text-gray-900 capitalize">{{ item.method }}</p>
                                    <p class="text-xs text-gray-500">Oleh: {{ item.collector }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <p class="text-sm font-black text-emerald-600">+ Rp {{ formatRupiah(item.amount) }}</p>
                                </td>
                            </tr>
                            <tr v-if="ledger.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-medium">Belum ada pembayaran pelanggan bulan ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
    ledger: Array
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
    form.get(route('customer-reports.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
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
    colors: ['#0ea5e9'],
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
    },
    tooltip: {
        y: { formatter: (val) => 'Rp ' + formatRupiah(val) }
    }
}));

const chartSeries = computed(() => [
    {
        name: 'Omzet Pelanggan',
        data: props.chartData.map(d => d.amount)
    }
]);
</script>
