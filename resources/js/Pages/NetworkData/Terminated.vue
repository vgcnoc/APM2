<script setup>
import { ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    customers: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Custom debounce
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

watch(search, debounce((value) => {
    router.get(
        route('network-data.terminated'),
        { search: value },
        { preserveState: true, replace: true }
    );
}, 300));
</script>

<template>
    <Head title="Cabut Perangkat (Stop Permanen)" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h2 class="font-black text-xl text-gray-800 leading-tight flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500 animate-pulse"></span>
                        Cabut Perangkat (Stop Permanen)
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">Daftar pelanggan stop permanen dan status real-time proses Cabut Perangkat (CBP) sebelum port steril</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('network-data.index')" class="px-3.5 py-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        &larr; Data Jaringan
                    </Link>
                    <Link :href="route('cbp.jadwal')" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Jadwal & Penugasan CBP
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Banner Keterangan Jaringan -->
                <div class="p-4 rounded-xl bg-gradient-to-r from-rose-50 via-orange-50 to-amber-50 border border-rose-200 text-rose-950 flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-xs">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-rose-900">Perhatian: Port ODP Belum Kosong Sebelum Selesai Cabut</h4>
                            <p class="text-xs text-rose-800/90 mt-0.5 leading-relaxed">
                                Pelanggan berstatus Stop Permanen masih tercatat menempati port fisik di ODP sampai teknisi selesai melakukan cabut perangkat fisik di lapangan. Pastikan tim teknisi menyelesaikan tugas cabut perangkat agar port dapat dialokasikan untuk pelanggan baru.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="relative flex-1 max-w-md w-full">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama, kode pelanggan, atau ODP..."
                            class="block w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-xs focus:ring-rose-500 focus:border-rose-500 transition-colors"
                        />
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm border border-gray-100 rounded-xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Port & ODP</th>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status Proses CBP</th>
                                    <th class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Keterangan Port</th>
                                    <th class="px-6 py-3.5 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-rose-50/20 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-start gap-3">
                                            <div class="w-8 h-8 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 font-black text-xs shrink-0 mt-0.5">
                                                {{ customer.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <a :href="`/customers/${customer.id}`" class="text-sm font-bold text-gray-900 hover:text-indigo-600 hover:underline">
                                                    {{ customer.name }}
                                                </a>
                                                <div class="text-xs text-gray-500 font-mono mt-0.5">{{ customer.customer_code }}</div>
                                                <div v-if="customer.phone" class="text-[11px] text-gray-400 mt-0.5 flex items-center gap-1">
                                                    📞 {{ customer.phone }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="customer.ont" class="space-y-1">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                <span>ODP: {{ customer.ont.odp?.name || '-' }}</span>
                                                <span class="text-indigo-300">•</span>
                                                <span class="text-rose-600 font-black">Port {{ customer.ont.port_number || '-' }}</span>
                                            </div>
                                            <div class="text-[11px] text-gray-500 font-mono">
                                                SN: {{ customer.ont.sn || '-' }}
                                            </div>
                                            <div v-if="customer.ont.odp?.address" class="text-[10px] text-gray-400 max-w-xs truncate" :title="customer.ont.odp.address">
                                                📍 {{ customer.ont.odp.address }}
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-gray-400 italic">Tidak ada port/ONT terkait</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="customer.latest_cbp_request" class="space-y-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <span v-if="customer.latest_cbp_request.status === 'pending'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                    ⏳ Menunggu Jadwal Cabut
                                                </span>
                                                <span v-else-if="customer.latest_cbp_request.status === 'assigned'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                                    🔧 Teknisi Ditugaskan
                                                </span>
                                                <span v-else-if="customer.latest_cbp_request.status === 'completed'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    ✅ Selesai Dicabut
                                                </span>
                                                <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gray-100 text-gray-800 border border-gray-300">
                                                    {{ customer.latest_cbp_request.status }}
                                                </span>

                                                <span class="text-[10px] font-mono font-bold text-gray-500">
                                                    {{ customer.latest_cbp_request.cbp_number }}
                                                </span>
                                            </div>

                                            <!-- Detail Teknisi / Jadwal -->
                                            <div v-if="customer.latest_cbp_request.technicians?.length" class="text-xs text-gray-700">
                                                <span class="font-bold text-gray-600">Teknisi:</span> 
                                                <span class="font-semibold text-indigo-700"> {{ customer.latest_cbp_request.technicians.map(t => t.name).join(', ') }}</span>
                                            </div>
                                            <div v-if="customer.latest_cbp_request.scheduled_date" class="text-[11px] text-gray-500">
                                                📅 Jadwal: {{ customer.latest_cbp_request.scheduled_date }}
                                            </div>
                                        </div>
                                        <div v-else class="space-y-1">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">
                                                ⚠️ Belum Ada Tiket CBP
                                            </span>
                                            <div class="text-[11px] text-gray-400 italic">Perlu dibuatkan tiket cabut perangkat</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="customer.latest_cbp_request?.status === 'completed'" class="text-xs text-emerald-700 font-semibold flex items-center gap-1">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Perangkat sudah dicabut. Port siap digunakan.
                                        </div>
                                        <div v-else class="text-xs text-rose-700 font-bold bg-rose-50 p-2 rounded-lg border border-rose-200 space-y-1">
                                            <div class="flex items-center gap-1 text-[11px]">
                                                <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Port fisik belum kosong
                                            </div>
                                            <div class="text-[10px] font-normal text-rose-800">
                                                Masih dalam proses penarikan ONT oleh teknisi.
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('cbp.jadwal')" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-lg border border-indigo-200 transition flex items-center gap-1">
                                                Jadwal CBP &rarr;
                                            </Link>
                                            <Link v-if="!customer.latest_cbp_request" :href="route('cbp.index')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg transition shadow-xs">
                                                Buat CBP
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="customers.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                                        Tidak ada data perangkat yang harus dicabut saat ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-center gap-2" v-if="customers.links && customers.links.length > 3">
                        <template v-for="(link, p) in customers.links" :key="p">
                            <div v-if="link.url === null" class="px-3 py-1.5 text-xs text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-3 py-1.5 text-xs rounded-lg transition-colors" :class="link.active ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200 hover:border-gray-300'" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
