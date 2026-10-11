<template>
    <AppLayout title="Jadwal & Material PGS" subtitle="Penjadwalan Penanganan ke Teknisi & Penyiapan Material oleh Kepala Teknisi">
        <div class="space-y-6">
            <!-- Nav Tabs -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto">
                <Link href="/pgs" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>1. Temuan & Penanganan (Admin)</span>
                </Link>
                <Link href="/pgs/jadwal" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shrink-0 bg-amber-500 text-white shadow-sm shadow-amber-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>2. Jadwal & Material (Ka. Teknisi)</span>
                </Link>
                <Link href="/pgs/laporan" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>3. Laporan Penanganan (Teknisi)</span>
                </Link>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari No. PGS, pelanggan, ODP..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                
                <div class="text-xs text-gray-500 font-medium">
                    Halaman ini menampilkan tiket yang diserahkan ke Kepala Teknisi untuk dijadwalkan & disiapkan materialnya.
                </div>
            </div>

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="p-4">No. Tiket & Pelanggan</th>
                                <th class="p-4">Alasan Eskalasi Admin</th>
                                <th class="p-4">Status & Jadwal</th>
                                <th class="p-4">Teknisi & Material Disiapkan</th>
                                <th class="p-4 text-center">Aksi Ka. Teknisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-amber-50/20 transition-colors group">
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-amber-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="ticket.priority === 'critical' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200'">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-gray-900 mt-1">
                                        {{ ticket.customer?.name || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.2 rounded bg-gray-100 border text-gray-700 font-mono text-[10px]">{{ ticket.customer?.customer_code }}</span>
                                        <span>· {{ ticket.customer?.address || '-' }}</span>
                                        <span v-if="ticket.area?.name || ticket.customer?.area_model?.name" class="px-1.5 py-0.5 rounded-md bg-amber-50 text-amber-700 font-semibold text-[10px] border border-amber-200">
                                            📍 {{ ticket.area?.name || ticket.customer?.area_model?.name }}
                                        </span>
                                    </div>
                                </td>

                                <td class="p-4 max-w-xs">
                                    <div class="text-xs font-bold text-gray-900">{{ ticket.category }}</div>
                                    <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900 mt-1">
                                        <div class="font-semibold text-[11px] text-amber-800">Catatan Admin:</div>
                                        <p class="line-clamp-2">{{ ticket.escalation_reason || ticket.reported_issue }}</p>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border"
                                        :class="ticket.status === 'escalated_to_lead' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="ticket.status === 'escalated_to_lead' ? 'bg-amber-500 animate-pulse' : 'bg-indigo-500'"></span>
                                        {{ ticket.status === 'escalated_to_lead' ? 'Menunggu Jadwal' : 'Dijadwalkan' }}
                                    </span>
                                    
                                    <div v-if="ticket.scheduled_date" class="mt-1.5 text-xs text-gray-700 font-semibold flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ formatDate(ticket.scheduled_date) }}</span>
                                        <span v-if="ticket.scheduled_time" class="text-gray-500">({{ ticket.scheduled_time }})</span>
                                    </div>
                                    <div v-else class="text-[11px] text-gray-400 italic mt-1">Belum ada tanggal jadwal</div>
                                </td>

                                <td class="p-4">
                                    <!-- Teknisi -->
                                    <div v-if="ticket.technicians?.length" class="flex flex-wrap items-center gap-1.5">
                                        <span v-for="t in ticket.technicians" :key="t.id" class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold">
                                            {{ t.name }}
                                        </span>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">Belum ditunjuk teknisi</div>

                                    <!-- Material Disiapkan -->
                                    <div v-if="ticket.materials?.length" class="mt-2 text-xs text-gray-600">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase block">Material Disiapkan:</span>
                                        <div class="flex flex-wrap gap-1 mt-0.5">
                                            <span v-for="m in ticket.materials" :key="m.id" class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px] border">
                                                {{ m.material?.name }}: <strong>{{ m.quantity_prepared }} {{ m.unit || m.material?.unit }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else-if="ticket.status === 'scheduled'" class="text-[11px] text-gray-400 italic mt-1">
                                        Tanpa alokasi material
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button 
                                            @click="openScheduleModal(ticket)" 
                                            class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5"
                                            :class="ticket.status === 'escalated_to_lead' ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-amber-500/20' : 'bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200'"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <span>{{ ticket.status === 'escalated_to_lead' ? 'Jadwalkan & Siapkan Material' : 'Edit Jadwal/Material' }}</span>
                                        </button>

                                        <button 
                                            @click="printTicket(ticket)" 
                                            class="p-1.5 text-gray-600 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors"
                                            title="Cetak SPK Penanganan"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!tickets.data.length">
                                <td colspan="5" class="p-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="font-bold text-gray-600">Tidak ada tiket menunggu jadwal</span>
                                        <span class="text-xs text-gray-400 mt-0.5">Semua eskalasi kendala telah diproses</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links && tickets.data.length" class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
                    <div>
                        Menampilkan <span class="font-bold text-gray-800">{{ tickets.from }}</span> - <span class="font-bold text-gray-800">{{ tickets.to }}</span> dari <span class="font-bold text-gray-800">{{ tickets.total }}</span> tiket
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in tickets.links" :key="i">
                            <div v-if="link.url === null" class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-300" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-3 py-1.5 border rounded-lg transition-colors font-medium" :class="link.active ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL JADWALKAN & SIAPKAN MATERIAL (KEPALA TEKNISI) ════════════ -->
        <div v-if="showScheduleModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-4 bg-gradient-to-r from-amber-500 to-orange-600 text-white flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-black text-lg">Penjadwalan & Penyiapan Material</h3>
                        <p class="text-xs text-amber-100 mt-0.5">Tugaskan teknisi dan alokasikan material dari gudang</p>
                    </div>
                    <button @click="showScheduleModal = false" class="p-1 rounded-full hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitSchedule" class="p-6 overflow-y-auto space-y-5 text-sm">
                    <!-- Info Tiket & Pelanggan -->
                    <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-xs space-y-1 text-amber-900">
                        <div class="flex justify-between font-bold">
                            <span>{{ selectedTicket?.ticket_number }} ({{ selectedTicket?.category }})</span>
                            <span class="uppercase">Prioritas: {{ selectedTicket?.priority }}</span>
                        </div>
                        <div><strong>Pelanggan:</strong> {{ selectedTicket?.customer?.name }} ({{ selectedTicket?.customer?.customer_code }})</div>
                        <div><strong>Alamat:</strong> {{ selectedTicket?.customer?.address }} · ODP: {{ selectedTicket?.customer?.ont?.odp?.name || '-' }}</div>
                        <div class="pt-1 border-t border-amber-200/60 text-amber-950 font-medium">
                            <strong>Alasan Eskalasi Admin:</strong> {{ selectedTicket?.escalation_reason || selectedTicket?.reported_issue }}
                        </div>
                    </div>

                    <!-- Waktu & Tanggal Jadwal -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tanggal Jadwal <span class="text-red-500">*</span></label>
                            <input 
                                type="date" 
                                v-model="scheduleForm.scheduled_date" 
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm" 
                                required
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Pelaksanaan (Opsional)</label>
                            <input 
                                type="text" 
                                v-model="scheduleForm.scheduled_time" 
                                placeholder="Contoh: 10:00 WIB atau Pagi" 
                                class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                            >
                        </div>
                    </div>

                    <!-- Pilih Teknisi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Pilih Teknisi Pelaksana <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 p-3 rounded-xl border border-gray-200 bg-gray-50/50 max-h-40 overflow-y-auto">
                            <label v-for="tech in technicians" :key="tech.id" class="flex items-center gap-2 p-2 rounded-lg bg-white border border-gray-200/80 cursor-pointer hover:border-amber-500 transition-colors">
                                <input 
                                    type="checkbox" 
                                    :value="tech.id" 
                                    v-model="scheduleForm.technicians" 
                                    class="rounded text-amber-600 focus:ring-amber-500"
                                >
                                <span class="text-xs font-semibold text-gray-800 truncate">{{ tech.name }}</span>
                            </label>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Bisa memilih lebih dari 1 teknisi jika pengerjaan tim.</p>
                    </div>

                    <!-- Instruksi Kepala Teknisi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Instruksi Pengerjaan untuk Teknisi</label>
                        <textarea 
                            v-model="scheduleForm.lead_notes" 
                            rows="2" 
                            placeholder="Contoh: Cek sambungan kabel di tiang no. 4 depan gang. Bawa splicer dan OPM. Target redaman di bawah -22 dBm."
                            class="w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 text-sm"
                        ></textarea>
                    </div>

                    <!-- Penyiapan Material dari Gudang -->
                    <div class="border-t pt-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-xs font-bold text-gray-800 uppercase">Material yang Disiapkan (Inventori)</label>
                                <span class="text-[11px] text-gray-400">Alokasikan barang yang perlu dibawa teknisi</span>
                            </div>
                            <button type="button" @click="addMaterialRow" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Tambah Barang</span>
                            </button>
                        </div>

                        <div v-if="scheduleForm.materials.length === 0" class="p-4 rounded-xl border border-dashed border-gray-200 text-center text-xs text-gray-400">
                            Belum ada material yang ditambahkan (opsional jika tidak memerlukan sparepart).
                        </div>

                        <div v-else class="space-y-2.5">
                            <div v-for="(item, idx) in scheduleForm.materials" :key="idx" class="flex flex-col sm:flex-row items-start sm:items-center gap-2 p-3 bg-gray-50 rounded-xl border border-gray-200">
                                <div class="flex-1 w-full sm:w-auto">
                                    <select v-model="item.material_id" @change="onMaterialChange(item)" class="w-full rounded-lg border-gray-200 text-xs focus:ring-amber-500" required>
                                        <option value="">-- Pilih Barang Gudang --</option>
                                        <option v-for="mat in materials" :key="mat.id" :value="mat.id">
                                            {{ mat.name }} (Stok: {{ mat.stock }} {{ mat.unit }})
                                        </option>
                                    </select>
                                </div>

                                <div class="w-32 flex items-center gap-1">
                                    <input 
                                        type="number" 
                                        step="any" 
                                        min="0.01" 
                                        v-model.number="item.quantity_prepared" 
                                        placeholder="Jumlah" 
                                        class="w-full rounded-lg border-gray-200 text-xs font-bold focus:ring-amber-500" 
                                        required
                                    >
                                    <span class="text-xs text-gray-500 font-semibold w-12 truncate">{{ item.unit || '-' }}</span>
                                </div>

                                <div class="flex-1 w-full sm:w-auto">
                                    <input 
                                        type="text" 
                                        v-model="item.notes" 
                                        placeholder="Keterangan (misal: cadangan)" 
                                        class="w-full rounded-lg border-gray-200 text-xs focus:ring-amber-500"
                                    >
                                </div>

                                <button type="button" @click="removeMaterialRow(idx)" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showScheduleModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="scheduleForm.processing || !scheduleForm.technicians.length" class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow-md shadow-amber-500/20 disabled:opacity-50">
                            {{ scheduleForm.processing ? 'Menyimpan...' : 'Simpan & Jadwalkan ke Teknisi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tickets: Object,
    areas: Array,
    technicians: Array,
    materials: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const showScheduleModal = ref(false);
const selectedTicket = ref(null);

const scheduleForm = useForm({
    scheduled_date: '',
    scheduled_time: '',
    technicians: [],
    lead_notes: '',
    materials: [],
});

function performSearch() {
    router.get('/pgs/jadwal', {
        search: search.value,
        area_id: filterArea.value,
    }, { preserveState: true, replace: true });
}

function openScheduleModal(ticket) {
    selectedTicket.value = ticket;
    
    // Set default date to today or existing scheduled_date
    const today = new Date().toISOString().split('T')[0];
    scheduleForm.scheduled_date = ticket.scheduled_date ? ticket.scheduled_date.substring(0, 10) : today;
    scheduleForm.scheduled_time = ticket.scheduled_time || '09:00';
    scheduleForm.technicians = ticket.technicians?.map(t => t.id) || [];
    scheduleForm.lead_notes = ticket.lead_notes || '';
    
    // Existing materials if any
    scheduleForm.materials = (ticket.materials || []).map(m => ({
        material_id: m.material_id,
        quantity_prepared: Number(m.quantity_prepared) || 1,
        unit: m.unit || m.material?.unit,
        notes: m.notes || '',
    }));

    showScheduleModal.value = true;
}

function addMaterialRow() {
    scheduleForm.materials.push({
        material_id: '',
        quantity_prepared: 1,
        unit: '',
        notes: '',
    });
}

function removeMaterialRow(idx) {
    scheduleForm.materials.splice(idx, 1);
}

function onMaterialChange(item) {
    const mat = props.materials.find(m => m.id === item.material_id);
    if (mat) {
        item.unit = mat.unit;
    }
}

function submitSchedule() {
    if (!selectedTicket.value) return;
    scheduleForm.post(`/pgs/${selectedTicket.value.id}/schedule`, {
        onSuccess: () => {
            showScheduleModal.value = false;
        }
    });
}

function printTicket(ticket) {
    if (!ticket) return;
    window.open(`/pgs/${ticket.id}/print`, '_blank');
}

function formatDate(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}
</script>
