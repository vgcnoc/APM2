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

            <!-- Mutasi Transaksi -->
            <div class="bg-white rounded-3xl shadow-[0_4px_20px_rgb(0,0,0,0.03)] border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Riwayat Mutasi Saldo & Voucher</h3>
                    <span class="text-xs font-bold bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-lg">15 Transaksi Terakhir</span>
                </div>
                
                <div v-if="recent_transactions.length" class="space-y-3">
                    <div v-for="trx in recent_transactions" :key="trx.id" class="flex items-center justify-between p-3.5 rounded-2xl border border-gray-100 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div :class="trx.type === 'credit' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="p-2.5 rounded-xl">
                                <svg v-if="trx.type === 'credit'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900">{{ trx.reseller?.customer?.name || 'Unknown' }}</p>
                                <p class="text-xs text-gray-500 max-w-sm truncate" :title="trx.description">{{ trx.description }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ new Date(trx.created_at).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p :class="trx.type === 'credit' ? 'text-green-600' : 'text-red-600'" class="text-sm font-black whitespace-nowrap">
                                {{ trx.type === 'credit' ? '+' : '-' }} Rp {{ formatRupiah(trx.amount) }}
                            </p>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide font-bold">{{ trx.reference_id }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-8 bg-gray-50 rounded-2xl border border-gray-100 border-dashed text-gray-500 text-sm">
                    Belum ada riwayat mutasi transaksi. <br>
                    <span class="text-xs">Beli voucher atau tambah saldo untuk melihat transaksi di sini.</span>
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
    recent_transactions: Array
});

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number);
};
</script>
