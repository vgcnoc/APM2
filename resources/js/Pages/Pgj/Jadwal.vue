<template>
    <AppLayout title="Jadwal & Material Jalur (PGJ)" subtitle="Penjadwalan Tim Lapangan & Alokasi Material FO oleh Kepala Teknisi">
        <div class="space-y-6">


            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div v-if="can('pgj_filter')" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGJ, ODC, ODP, lokasi..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                
                <div class="text-xs text-gray-500 font-medium">
                    Tiket gangguan jalur yang menunggu jadwal tim teknisi & alokasi material FO dari gudang.
                </div>
            </div>

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="p-4">No. Tiket & Segmen</th>
                                <th class="p-4">Alasan Eskalasi & Kerusakan</th>
                                <th class="p-4">Status & Jadwal Tim</th>
                                <th class="p-4">Tim & Material Disiapkan</th>
                                <th class="p-4 text-center">Aksi Ka. Teknisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-amber-50/20 transition-colors group">
                                <!-- 1. No Tiket & Segmen -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-amber-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="ticket.priority === 'critical' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200'">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-gray-900 mt-1">
                                        {{ ticket.location_name }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-0.5 flex flex-wrap gap-1.5">
                                        <span class="font-extrabold text-[10px] px-2 py-0.5 rounded uppercase" :class="getSegmentBadgeClass(ticket.segment_type)">
                                            {{ ticket.segment_type }}
                                        </span>
                                        <span v-if="ticket.odc" class="text-rose-700 font-semibold">ODC: {{ ticket.odc.name }}</span>
                                        <span v-if="ticket.odp" class="text-amber-700 font-semibold">ODP: {{ ticket.odp.name }}</span>
                                    </div>
                                    <div class="mt-1 text-xs text-red-600 font-bold">
                                        🚨 {{ ticket.affected_customers_count }} Pelanggan Terdampak
                                    </div>
                                </td>

                                <!-- 2. Alasan Eskalasi & Kerusakan -->
                                <td class="p-4">
                                    <div class="text-xs font-semibold text-gray-800">
                                        Kategori: {{ ticket.category }}
                                    </div>
                                    <p class="text-xs text-red-600 font-medium mt-1">
                                        "{{ ticket.reported_issue }}"
                                    </p>
                                    <div class="mt-2 p-2 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                                        <div class="font-bold text-[11px] uppercase text-amber-800">Alasan Eskalasi Admin:</div>
                                        <div class="mt-0.5">{{ ticket.escalation_reason || '-' }}</div>
                                    </div>
                                </td>

                                <!-- 3. Status & Jadwal -->
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="ticket.status === 'scheduled' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="ticket.status === 'scheduled' ? 'bg-indigo-500' : 'bg-amber-500 animate-pulse'"></span>
                                        {{ ticket.status === 'scheduled' ? 'Sudah Dijadwalkan' : 'Menunggu Jadwal' }}
                                    </span>

                                    <div v-if="ticket.scheduled_date" class="mt-2 text-xs text-gray-700 font-semibold flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ formatDate(ticket.scheduled_date) }}</span>
                                        <span class="text-gray-500 font-normal">({{ ticket.scheduled_time || 'Segera' }})</span>
                                    </div>
                                </td>

                                <!-- 4. Tim & Material -->
                                <td class="p-4">
                                    <!-- Daftar Teknisi -->
                                    <div class="text-xs">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Tim Ditugaskan:</div>
                                        <div v-if="ticket.technicians && ticket.technicians.length > 0" class="font-bold text-gray-800 mt-0.5">
                                            {{ ticket.technicians.map(t => t.name).join(', ') }}
                                        </div>
                                        <div v-else class="text-gray-400 italic text-xs mt-0.5">Belum ditentukan</div>
                                    </div>

                                    <!-- Material Disiapkan -->
                                    <div class="mt-2 text-xs">
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">Material FO Disiapkan:</div>
                                        <div v-if="ticket.materials && ticket.materials.length > 0" class="space-y-1 mt-1">
                                            <div v-for="m in ticket.materials" :key="m.id" class="text-[11px] bg-gray-50 px-2 py-0.5 rounded border border-gray-100 flex justify-between items-center gap-1">
                                                <div class="flex items-center gap-1.5 truncate">
                                                    <span class="font-medium text-gray-700 truncate">{{ m.material?.name }}</span>
                                                    <span v-if="m.trx_number" class="text-[9px] font-mono font-bold text-amber-700 bg-amber-100 px-1 py-0.5 rounded border border-amber-200">PO: {{ m.trx_number }}</span>
                                                </div>
                                                <span class="font-bold text-gray-900 shrink-0">{{ parseFloat(m.quantity_prepared) }} {{ m.unit || m.material?.unit }}</span>
                                            </div>
                                        </div>
                                        <div v-else class="text-gray-400 italic text-xs mt-0.5">Belum ada material disiapkan</div>
                                    </div>
                                </td>

                                <!-- 5. Aksi Ka. Teknisi -->
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button 
                                            v-if="can('pgj_btn_assign')"
                                            @click="openScheduleModal(ticket)" 
                                            class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md shadow-amber-500/20 transition-all flex items-center gap-1.5"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                            <span>{{ ticket.status === 'scheduled' ? 'Ubah Jadwal' : 'Atur Jadwal & Tim' }}</span>
                                        </button>

                                        <a :href="`/pgj/${ticket.id}/print`" target="_blank" title="Cetak SPK Restorasi Jalur" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Tidak ada tiket gangguan jalur yang membutuhkan penjadwalan saat ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.links.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ tickets.from || 0 }} - {{ tickets.to || 0 }} dari {{ tickets.total || 0 }} kasus jalur
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, idx) in tickets.links" :key="idx">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="px-3 py-1 text-xs rounded-lg transition-colors border"
                                :class="link.active ? 'bg-amber-500 text-white border-amber-500 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-xs rounded-lg text-gray-300 border border-transparent"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL ATUR JADWAL & MATERIAL JALUR ════════════ -->
        <div v-if="showScheduleModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-5 bg-gradient-to-r from-amber-500 to-orange-600 text-white flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-lg">Jadwal Restorasi & Material Jalur PGJ #{{ selectedTicket?.ticket_number }}</h3>
                        <p class="text-xs text-amber-100 mt-0.5">Tugaskan tim teknisi, jadwalkan jam pengerjaan, dan bawa material FO gudang</p>
                    </div>
                    <button @click="showScheduleModal = false" class="text-white/80 hover:text-white text-2xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitSchedule" class="p-6 space-y-4 max-h-[85vh] overflow-y-auto">
                    <!-- Ringkasan Gangguan & Dampak -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-100 text-xs text-amber-900 space-y-1">
                        <div class="font-bold flex justify-between">
                            <span>Titik: {{ selectedTicket?.location_name }}</span>
                            <span class="uppercase font-extrabold text-amber-800">Segmen: {{ selectedTicket?.segment_type }}</span>
                        </div>
                        <div class="text-red-700 font-bold">Dampak: {{ selectedTicket?.affected_customers_count }} Pelanggan Terputus</div>
                        <div class="text-red-700 font-medium">Kerusakan: "{{ selectedTicket?.reported_issue }}"</div>
                        <div class="text-amber-800 font-semibold">Alasan Eskalasi: "{{ selectedTicket?.escalation_reason }}"</div>
                    </div>

                    <!-- Tanggal & Jam Restorasi -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Restorasi Lapangan <span class="text-red-500">*</span></label>
                            <input 
                                type="date" 
                                v-model="scheduleForm.scheduled_date" 
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm" 
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Estimasi Jam Pengerjaan</label>
                            <input 
                                type="text" 
                                v-model="scheduleForm.scheduled_time" 
                                placeholder="Contoh: 13:00 - 17:00 WIB / Segera"
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm" 
                            />
                        </div>
                    </div>

                    <!-- Pilih Tim Teknisi Lapangan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Pilih Tim Teknisi Lapangan (Splicer / Helper) <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-2xl bg-gray-50 border border-gray-200 max-h-36 overflow-y-auto">
                            <label 
                                v-for="t in technicians" 
                                :key="t.id" 
                                class="flex items-center gap-2 p-2 rounded-xl bg-white border border-gray-200 text-xs font-semibold cursor-pointer hover:border-amber-400 transition-colors"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="t.id" 
                                    v-model="scheduleForm.technician_ids" 
                                    class="rounded text-amber-500 focus:ring-amber-400"
                                />
                                <span class="truncate">{{ t.name }}</span>
                            </label>
                        </div>
                        <p v-if="scheduleForm.technician_ids.length === 0" class="text-[11px] text-red-500 mt-1">Pilih minimal 1 orang teknisi pelaksana.</p>
                    </div>

                    <!-- Instruksi Kerja Teknis Ka. Teknisi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Instruksi Teknis Kerja dari Kepala Teknisi</label>
                        <textarea 
                            v-model="scheduleForm.lead_notes" 
                            rows="2" 
                            placeholder="Contoh: Bawa Fusion Splicer, tangga 6m, closure dome 24 core. Cek tiang depan Alfamart, sambung kembali core 1-8 yang putus."
                            class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                        ></textarea>
                    </div>

                    <!-- Penyiapan Material Jalur FO dari Inventory Gudang -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase">Material dari Inventory Gudang</label>
                                <span class="text-[11px] text-gray-500">Pilih material kabel & aksesoris FO dari persediaan Inventory Gudang yang tersedia</span>
                            </div>
                            <button type="button" @click="addMaterialRow" class="text-xs font-bold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded-lg border border-amber-200 flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Tambah Material Gudang</span>
                            </button>
                        </div>

                        <div v-if="scheduleForm.materials.length > 0" class="space-y-2.5">
                            <div v-for="(item, idx) in scheduleForm.materials" :key="idx" class="p-2.5 rounded-xl bg-gray-50 border border-gray-200/80 shadow-xs space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <select 
                                            v-model="item.selected_key" 
                                            @change="onMaterialItemChange(item)"
                                            class="w-full rounded-xl border-gray-300 text-xs py-2 bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 truncate" 
                                            required
                                        >
                                            <option value="">-- Pilih Material dari Inventory Gudang --</option>
                                            <option v-for="ord in availableMaterialItems" :key="ord.unique_key" :value="ord.unique_key">
                                                {{ ord.name }} — Stok Gudang: {{ ord.area_stock }} {{ ord.unit }} {{ ord.trx_number ? '(PO: ' + ord.trx_number + ')' : '' }} [{{ ord.area_name }}]
                                            </option>
                                            <!-- Fallback jika record sebelumnya tidak memiliki key baru -->
                                            <option v-if="!item.selected_key && item.material_id" :value="''">
                                                📦 {{ item.name || 'Material Sebelumnya' }} (Data Lama)
                                            </option>
                                        </select>
                                    </div>

                                    <div class="w-24 shrink-0 flex items-center gap-1">
                                        <input 
                                            type="number" 
                                            step="0.01" 
                                            min="0.01" 
                                            :max="item.max_stock || undefined"
                                            v-model.number="item.quantity" 
                                            @input="validateMaterialQty(item)"
                                            placeholder="Qty" 
                                            class="w-full rounded-xl border-gray-300 text-xs py-2 text-center font-bold text-amber-700 bg-amber-50/50 focus:border-amber-500" 
                                            required
                                        />
                                    </div>

                                    <span class="text-xs font-semibold text-gray-500 w-12 text-center truncate shrink-0">{{ item.unit || '-' }}</span>

                                    <button type="button" @click="removeMaterialRow(idx)" class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-colors shrink-0" title="Hapus Material">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div v-if="item.trx_number || item.max_stock" class="flex items-center justify-between text-[10px] text-gray-500 px-1">
                                    <span class="truncate">
                                        <span class="font-medium text-gray-400">Sumber:</span> 
                                        <b class="font-mono text-amber-800 ml-1">{{ item.trx_number ? 'PO ' + item.trx_number : 'Inventory Gudang' }}</b>
                                    </span>
                                    <span v-if="item.max_stock" class="shrink-0 text-emerald-700 font-medium">
                                        Stok Tersedia: {{ item.max_stock }} {{ item.unit }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 italic">* Stok diambil langsung dari persediaan Inventory Gudang dan otomatis dialokasikan untuk penugasan ini. Sisa kabel/material dapat dikembalikan ke Inventory Gudang saat laporan teknisi selesai.</p>
                        </div>
                        <div v-else class="p-3 rounded-xl bg-amber-50/40 border border-dashed border-amber-200 text-center text-xs text-amber-700">
                            Belum ada material yang ditambahkan dari Inventory Gudang. Klik <b>+ Tambah Material Gudang</b> jika tim membawa material.
                        </div>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showScheduleModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button 
                            type="submit" 
                            :disabled="scheduleForm.processing || scheduleForm.technician_ids.length === 0" 
                            class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow-md shadow-amber-500/20 disabled:opacity-50"
                        >
                            {{ scheduleForm.processing ? 'Menyimpan...' : 'Simpan Jadwal & Berangkatkan Tim' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    tickets: Object,
    areas: Array,
    technicians: Array,
    materials: Array,
    materialTransactions: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');

const showScheduleModal = ref(false);
const selectedTicket = ref(null);

const customerAreaId = computed(() => {
    return selectedTicket.value?.area_id || null;
});

const filteredMaterialTransactions = computed(() => {
    const allTrx = props.materialTransactions || [];
    const areaId = customerAreaId.value;
    if (!areaId) return allTrx;
    const matched = allTrx.filter(trx => !trx.area_id || trx.area_id == areaId);
    return matched.length > 0 ? matched : allTrx;
});

const availableMaterialItems = computed(() => {
    let items = [];
    const areaId = customerAreaId.value;
    const handledMaterialIds = new Set();

    // 1. Ambil dari Order Toko / Restock Area jika ada
    const trxs = filteredMaterialTransactions.value || [];
    trxs.forEach(trx => {
        if (trx.items && trx.items.length > 0) {
            trx.items.forEach(item => {
                if (!item.material) return;
                const name = (item.material.name || '').toLowerCase();
                const category = (item.material.category || '').toLowerCase();
                const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;

                if (!isRegistered && !name.includes('modem') && !category.includes('modem')) {
                    let areaStock = 0;
                    if (item.material.stocks && areaId) {
                        const stockObj = item.material.stocks.find(s => s.area_id == areaId);
                        if (stockObj) {
                            areaStock = parseFloat(stockObj.stock) || 0;
                        }
                    }

                    if (areaStock <= 0 && item.material.stock) {
                        areaStock = parseFloat(item.material.stock) || 0;
                    }

                    const availableQty = areaStock > 0 ? areaStock : (parseFloat(item.quantity) || 0);

                    if (availableQty > 0) {
                        handledMaterialIds.add(item.material_id);
                        items.push({
                            unique_key: 'ord-' + item.id,
                            id: item.id,
                            material_id: item.material_id,
                            trx_number: trx.transaction_number,
                            trx_date: trx.date,
                            purpose: trx.purpose,
                            area_name: trx.area_model?.name || trx.area || 'Order Toko Area',
                            name: item.material.name,
                            category: item.material.category,
                            area_stock: availableQty,
                            unit: item.unit || item.material.unit || 'pcs',
                            material: item.material,
                        });
                    }
                }
            });
        }
    });

    // 2. Sertakan SEMUA Material dari Inventory Gudang
    const allMaterials = props.materials || [];
    allMaterials.forEach(m => {
        const name = (m.name || '').toLowerCase();
        const category = (m.category || '').toLowerCase();
        if (name.includes('modem') || category.includes('modem') || name.includes('ont') || category.includes('ont')) {
            return;
        }

        let areaStock = 0;
        if (m.stocks && areaId) {
            const stockObj = m.stocks.find(s => s.area_id == areaId);
            if (stockObj) {
                areaStock = parseFloat(stockObj.stock) || 0;
            }
        }
        if (areaStock <= 0 && m.stock) {
            areaStock = parseFloat(m.stock) || 0;
        }

        if (!handledMaterialIds.has(m.id)) {
            items.push({
                unique_key: 'mat-' + m.id,
                id: 'mat-' + m.id,
                material_id: m.id,
                trx_number: '',
                area_name: 'Inventory Gudang',
                name: m.name,
                category: m.category,
                area_stock: areaStock,
                unit: m.unit || 'pcs',
                material: m,
            });
        }
    });

    return items;
});

const scheduleForm = useForm({
    scheduled_date: '',
    scheduled_time: '',
    lead_notes: '',
    technician_ids: [],
    materials: [],
});

const performSearch = () => {
    router.get('/pgj/jadwal', {
        search: search.value,
        area_id: filterArea.value,
    }, { preserveState: true, replace: true });
};

const openScheduleModal = (ticket) => {
    selectedTicket.value = ticket;
    scheduleForm.reset();

    scheduleForm.scheduled_date = ticket.scheduled_date || new Date().toISOString().split('T')[0];
    scheduleForm.scheduled_time = ticket.scheduled_time || 'Segera';
    scheduleForm.lead_notes = ticket.lead_notes || '';
    scheduleForm.technician_ids = ticket.technicians ? ticket.technicians.map(t => t.id) : [];

    if (ticket.materials && ticket.materials.length > 0) {
        scheduleForm.materials = ticket.materials.map(m => {
            const matchedOrder = availableMaterialItems.value.find(
                i => i.id === m.material_transaction_item_id || (i.material_id === m.material_id && (!m.trx_number || i.trx_number === m.trx_number))
            );
            const selKey = m.material_transaction_item_id ? ('ord-' + m.material_transaction_item_id) : (matchedOrder ? matchedOrder.unique_key : ('mat-' + m.material_id));
            return {
                selected_key: selKey,
                material_transaction_item_id: m.material_transaction_item_id || null,
                material_id: m.material_id,
                trx_number: m.trx_number || (matchedOrder ? matchedOrder.trx_number : ''),
                name: m.material?.name || 'Material FO',
                quantity: parseFloat(m.quantity_prepared || m.quantity || 1),
                quantity_prepared: parseFloat(m.quantity_prepared || m.quantity || 1),
                unit: m.unit || m.material?.unit || '',
                max_stock: matchedOrder ? matchedOrder.area_stock : (parseFloat(m.material?.stock) || 9999),
                notes: m.notes || '',
            };
        });
    } else {
        scheduleForm.materials = [];
    }

    showScheduleModal.value = true;
};

const addMaterialRow = () => {
    scheduleForm.materials.push({
        selected_key: '',
        material_transaction_item_id: null,
        material_id: '',
        trx_number: '',
        name: '',
        quantity: 1,
        quantity_prepared: 1,
        unit: '',
        max_stock: null,
        notes: '',
    });
};

const removeMaterialRow = (index) => {
    scheduleForm.materials.splice(index, 1);
};

const onMaterialItemChange = (item) => {
    const selectedKey = item.selected_key || item.material_transaction_item_id;
    const selectedOrder = availableMaterialItems.value.find(i => i.unique_key === selectedKey || i.id === selectedKey || i.material_id === selectedKey);
    if (selectedOrder) {
        item.material_id = selectedOrder.material_id;
        item.trx_number = selectedOrder.trx_number || '';
        item.name = selectedOrder.name;
        item.unit = selectedOrder.unit;
        item.max_stock = selectedOrder.area_stock;
        item.material_transaction_item_id = selectedOrder.unique_key?.startsWith('ord-') ? (selectedOrder.id || null) : null;
        
        if (item.quantity && item.quantity > selectedOrder.area_stock) {
            item.quantity = selectedOrder.area_stock;
        } else if (!item.quantity || item.quantity <= 0) {
            item.quantity = 1;
        }
    }
};

const validateMaterialQty = (item) => {
    if (item.max_stock !== undefined && item.max_stock !== null) {
        if (item.quantity > item.max_stock) {
            item.quantity = item.max_stock;
            alert(`Stok tidak mencukupi! Maksimal stok untuk material dari order ini adalah ${item.max_stock} ${item.unit}`);
        }
    }
};

const submitSchedule = () => {
    if (!selectedTicket.value) return;

    scheduleForm.materials.forEach(m => {
        m.quantity_prepared = m.quantity;
    });

    scheduleForm.post(`/pgj/${selectedTicket.value.id}/schedule`, {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleModal.value = false;
            selectedTicket.value = null;
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            const msg = Object.values(errors).flat().join('\n');
            alert('Gagal menyimpan jadwal jalur:\n' + msg);
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
};

const getSegmentBadgeClass = (segment) => {
    switch (segment) {
        case 'backbone': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'odc': return 'bg-rose-100 text-rose-800 border-rose-200';
        case 'odp': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'distribution': return 'bg-cyan-100 text-cyan-800 border-cyan-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};
</script>
