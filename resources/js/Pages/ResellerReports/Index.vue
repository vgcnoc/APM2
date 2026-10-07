<template>
    <AppLayout title="Laporan Keuangan Reseller" subtitle="Ringkasan kasbon, setoran, dan saldo beredar">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Dashboard Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Saldo Beredar -->
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-3xl p-6 text-white shadow-[0_8px_30px_rgb(79,70,229,0.2)] relative overflow-hidden group">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9zdmc+')] opacity-50 group-hover:opacity-75 transition-opacity"></div>
                    <div class="relative z-10">
                        <p class="text-indigo-100 font-medium tracking-wide uppercase text-xs mb-1">Total Saldo Beredar</p>
                        <h2 class="text-3xl font-black mb-2">Rp {{ formatRupiah(stats.saldo_beredar) }}</h2>
                        <p class="text-indigo-200 text-xs line-clamp-2">Jumlah saldo yang saat ini dipegang oleh seluruh reseller dan siap ditukar menjadi voucher.</p>
                    </div>
                    <div class="absolute -bottom-6 -right-6 text-indigo-400/30">
                        <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                </div>

                <!-- Total Piutang Kasbon -->
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-3xl p-6 text-white shadow-[0_8px_30px_rgb(239,68,68,0.2)] relative overflow-hidden group">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9zdmc+')] opacity-50 group-hover:opacity-75 transition-opacity"></div>
                    <div class="relative z-10">
                        <p class="text-red-100 font-medium tracking-wide uppercase text-xs mb-1">Total Piutang Kasbon</p>
                        <h2 class="text-3xl font-black mb-2">Rp {{ formatRupiah(stats.piutang) }}</h2>
                        <p class="text-red-200 text-xs line-clamp-2">Jumlah uang yang masih belum disetorkan oleh penagih atau belum dilunasi reseller.</p>
                    </div>
                    <div class="absolute -bottom-6 -right-6 text-red-400/30">
                        <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                </div>

                <!-- Pemasukan Bersih -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-3xl p-6 text-white shadow-[0_8px_30px_rgb(34,197,94,0.2)] relative overflow-hidden group">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9zdmc+')] opacity-50 group-hover:opacity-75 transition-opacity"></div>
                    <div class="relative z-10">
                        <p class="text-green-100 font-medium tracking-wide uppercase text-xs mb-1">Total Pemasukan (Tervalidasi)</p>
                        <h2 class="text-3xl font-black mb-2">Rp {{ formatRupiah(stats.pemasukan) }}</h2>
                        <p class="text-green-200 text-xs line-clamp-2">Total uang kasbon maupun pembelian tunai yang sudah divalidasi dan masuk kas perusahaan.</p>
                    </div>
                    <div class="absolute -bottom-6 -right-6 text-green-400/30">
                        <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    </div>
                </div>
            </div>

            <!-- Tabel Reseller -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Rincian Per Reseller</h3>
                    <p class="text-sm text-gray-500">Analisis keuangan dan performa masing-masing reseller.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Reseller</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Saldo Tersisa</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Kasbon (Hutang)</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sisa Piutang</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Lunas</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="reseller in resellers" :key="reseller.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ reseller.name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-indigo-600">Rp {{ formatRupiah(reseller.balance) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-600">Rp {{ formatRupiah(reseller.total_kasbon) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-black text-red-500">Rp {{ formatRupiah(reseller.piutang) }}</div>
                                    <div v-if="reseller.piutang > 0" class="text-[10px] text-gray-400 italic">Perlu ditagih</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-green-600">Rp {{ formatRupiah(reseller.total_paid) }}</div>
                                </td>
                            </tr>
                            <tr v-if="!resellers.length">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <p class="font-medium">Belum ada data reseller terdaftar.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">5 Riwayat Pemasukan Terakhir</h3>
                <div v-if="recent_payments.length" class="space-y-3">
                    <div v-for="pay in recent_payments" :key="pay.id" class="flex items-center justify-between p-3 rounded-2xl border border-gray-100 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="bg-green-100 text-green-600 p-2.5 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ pay.customer?.name || 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ pay.invoice?.invoice_number }} &middot; {{ new Date(pay.updated_at).toLocaleDateString('id-ID') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-black text-green-600">+ Rp {{ formatRupiah(pay.amount) }}</p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-bold">Via {{ pay.payment_method }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-6 text-gray-500 text-sm">
                    Belum ada riwayat pemasukan yang divalidasi.
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    stats: Object,
    resellers: Array,
    recent_payments: Array
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};
</script>
