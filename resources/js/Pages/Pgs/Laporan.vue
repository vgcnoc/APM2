<template>
    <AppLayout title="Laporan Penanganan PGS" subtitle="Pelaporan Hasil Pengerjaan Lapangan & Material Terpakai oleh Teknisi">
        <div class="space-y-6">
            <!-- Nav Tabs -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto">
                <Link href="/pgs" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>1. Temuan & Penanganan (Admin)</span>
                </Link>
                <Link href="/pgs/jadwal" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>2. Jadwal & Material (Ka. Teknisi)</span>
                </Link>
                <Link href="/pgs/laporan" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shrink-0 bg-emerald-600 text-white shadow-sm shadow-emerald-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>3. Laporan Penanganan (Teknisi)</span>
                </Link>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div class="relative flex-1 sm:w-80">
                    <input 
                        type="text" 
                        v-model="search" 
                        placeholder="Cari No. PGS, pelanggan, tindakan..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm transition-all"
                        @keyup.enter="performSearch"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                <div class="text-xs text-gray-500 font-medium">
                    Halaman ini digunakan teknisi untuk melaporkan hasil tindakan, redaman optik, dan material terpakai.
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
                                <th class="p-4">Instruksi & Material Disiapkan</th>
                                <th class="p-4">Status & Waktu Pengerjaan</th>
                                <th class="p-4">Hasil Tindakan & Redaman</th>
                                <th class="p-4 text-center">Aksi Teknisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-emerald-50/20 transition-colors group">
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-emerald-600 text-xs">{{ ticket.ticket_number }}</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border" :class="ticket.priority === 'critical' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200'">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-gray-900 mt-1">
                                        {{ ticket.customer?.name || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5 mt-0.5">
                                        <span class="px-1.5 py-0.2 rounded bg-gray-100 border text-gray-700 font-mono text-[10px]">{{ ticket.customer?.customer_code }}</span>
                                        <span>· {{ ticket.customer?.phone || '-' }}</span>
                                        <span>· {{ ticket.customer?.address || '-' }}</span>
                                    </div>
                                </td>

                                <td class="p-4 max-w-xs">
                                    <div class="text-xs text-gray-800">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase block">Instruksi Ka. Teknisi:</span>
                                        <p class="mt-0.5 line-clamp-2 italic text-gray-700">{{ ticket.lead_notes || 'Selesaikan sesuai SOP kendala ' + ticket.category }}</p>
                                    </div>

                                    <div v-if="ticket.materials?.length" class="mt-2 text-xs">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase block">Material Dibawa:</span>
                                        <div class="flex flex-wrap gap-1 mt-0.5">
                                            <span v-for="m in ticket.materials" :key="m.id" class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px] border">
                                                {{ m.material?.name }}: <strong>{{ m.quantity_prepared }} {{ m.unit || m.material?.unit }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border"
                                        :class="ticket.status === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="ticket.status === 'completed' ? 'bg-emerald-500' : 'bg-indigo-500 animate-pulse'"></span>
                                        {{ ticket.status === 'completed' ? 'Selesai Dilaporkan' : 'Siap Ditangani' }}
                                    </span>

                                    <div v-if="ticket.scheduled_date" class="text-xs text-gray-600 mt-1.5">
                                        Jadwal: {{ formatDate(ticket.scheduled_date) }} ({{ ticket.scheduled_time || 'Fleksibel' }})
                                    </div>
                                    <div v-if="ticket.resolved_at" class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                                        Selesai: {{ formatDateTime(ticket.resolved_at) }}
                                    </div>
                                </td>

                                <td class="p-4 max-w-xs">
                                    <div v-if="ticket.status === 'completed'">
                                        <div class="text-xs font-bold text-gray-900">{{ ticket.action_taken || '-' }}</div>
                                        <div class="text-[11px] text-gray-500 flex items-center gap-2 mt-1">
                                            <span v-if="ticket.optical_power_before">Awal: <strong>{{ ticket.optical_power_before }}</strong></span>
                                            <span v-if="ticket.optical_power_after" class="text-emerald-700">Akhir: <strong>{{ ticket.optical_power_after }}</strong></span>
                                        </div>
                                        <div v-if="ticket.photo_after || ticket.photo_before" class="mt-1 flex items-center gap-1.5">
                                            <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-1.5 py-0.2 rounded border border-indigo-200">Ada Foto Bukti</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Menunggu laporan perbaikan teknisi...
                                    </div>
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Tombol Lapor Selesai jika belum completed -->
                                        <button 
                                            v-if="ticket.status !== 'completed'"
                                            @click="openReportModal(ticket)" 
                                            class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/20"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Lapor Selesai</span>
                                        </button>

                                        <!-- Jika sudah selesai: Edit / Lihat Laporan -->
                                        <button 
                                            v-else
                                            @click="openReportModal(ticket)" 
                                            class="px-2.5 py-1.5 text-xs font-bold rounded-lg transition-colors bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200"
                                        >
                                            <span>Lihat / Edit Laporan</span>
                                        </button>

                                        <button 
                                            @click="printTicket(ticket)" 
                                            class="p-1.5 text-gray-600 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-colors"
                                            title="Cetak Berita Acara PGS"
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
                                        <span class="font-bold text-gray-600">Tidak ada penanganan untuk dilaporkan</span>
                                        <span class="text-xs text-gray-400 mt-0.5">Semua tugas teknisi telah diselesaikan</span>
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
                            <Link v-else :href="link.url" class="px-3 py-1.5 border rounded-lg transition-colors font-medium" :class="link.active ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════ MODAL LAPORAN HASIL PENANGANAN (TEKNISI) ════════════ -->
        <div v-if="showReportModal" class="fixed inset-0 z-50 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-gray-100 overflow-hidden animate-fade-in-up">
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-black text-lg">Laporan Penanganan Lapangan</h3>
                        <p class="text-xs text-emerald-100 mt-0.5">Laporkan tindakan perbaikan, redaman, dan material terpakai</p>
                    </div>
                    <button @click="showReportModal = false" class="p-1 rounded-full hover:bg-white/20 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitReport" class="p-6 overflow-y-auto space-y-5 text-sm">
                    <!-- Info Pelanggan & Masalah -->
                    <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl text-xs space-y-1 text-emerald-950">
                        <div class="font-bold flex justify-between">
                            <span>{{ selectedTicket?.ticket_number }} ({{ selectedTicket?.category }})</span>
                            <span>{{ selectedTicket?.customer?.name }} ({{ selectedTicket?.customer?.customer_code }})</span>
                        </div>
                        <div><strong>Alamat:</strong> {{ selectedTicket?.customer?.address }} · ODP: {{ selectedTicket?.customer?.ont?.odp?.name || '-' }}</div>
                        <div class="pt-1 border-t border-emerald-200/60">
                            <strong>Instruksi Ka. Teknisi:</strong> {{ selectedTicket?.lead_notes || 'Perbaiki kendala dan pastikan koneksi stabil.' }}
                        </div>
                    </div>

                    <!-- Tindakan Perbaikan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tindakan Perbaikan yang Dilakukan <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            v-model="reportForm.action_taken" 
                            placeholder="Contoh: Splicing ulang kabel dropcore putus di tiang & ganti fast connector" 
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-medium" 
                            required
                        >
                    </div>

                    <!-- Redaman Optik Sebelum & Sesudah -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Redaman Optik Sebelum (dBm)</label>
                            <input 
                                type="text" 
                                v-model="reportForm.optical_power_before" 
                                placeholder="Contoh: -35 dBm atau LOS" 
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Redaman Optik Sesudah (dBm)</label>
                            <input 
                                type="text" 
                                v-model="reportForm.optical_power_after" 
                                placeholder="Contoh: -19 dBm (Bagus)" 
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm font-semibold text-emerald-700"
                            >
                        </div>
                    </div>

                    <!-- Waktu Pengerjaan -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Mulai Pengerjaan</label>
                            <input 
                                type="time" 
                                v-model="reportForm.start_time" 
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Jam Selesai Pengerjaan</label>
                            <input 
                                type="time" 
                                v-model="reportForm.end_time" 
                                class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm"
                            >
                        </div>
                    </div>

                    <!-- Material Terpakai Aktual -->
                    <div class="border-t pt-4 space-y-3">
                        <label class="block text-xs font-bold text-gray-800 uppercase">Konfirmasi Pemakaian Material</label>
                        <p class="text-[11px] text-gray-400">Verifikasi material yang terpakai. Sisa material otomatis dikembalikan ke inventori.</p>

                        <div v-if="!reportForm.materials.length" class="p-3 rounded-xl bg-gray-50 text-gray-500 text-xs italic">
                            Tidak ada material yang dialokasikan sebelumnya.
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="item in reportForm.materials" :key="item.id" class="p-3 bg-gray-50 rounded-xl border border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                                <div>
                                    <span class="font-bold text-gray-900">{{ item.material_name }}</span>
                                    <span class="text-gray-500 block">Disiapkan: <strong>{{ item.quantity_prepared }} {{ item.unit }}</strong></span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div>
                                        <label class="text-[10px] text-gray-500 font-bold uppercase block">Terpakai:</label>
                                        <input 
                                            type="number" 
                                            step="any" 
                                            min="0" 
                                            :max="item.quantity_prepared"
                                            v-model.number="item.quantity_used" 
                                            class="w-20 px-2 py-1 rounded border-gray-300 text-xs font-bold focus:ring-emerald-500" 
                                            required
                                        >
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-gray-500 font-bold uppercase block">Kembali:</label>
                                        <span class="font-bold text-gray-700 text-xs px-2 py-1 bg-white rounded border border-gray-200 inline-block min-w-14 text-center">
                                            {{ Math.max(0, (item.quantity_prepared - (item.quantity_used || 0))).toFixed(1) }} {{ item.unit }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Foto Bukti -->
                    <div class="border-t pt-4 space-y-3">
                        <label class="block text-xs font-bold text-gray-800 uppercase">Upload Foto Bukti Perbaikan (Opsional)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Foto Sebelum Perbaikan (Kondisi Rusak)</label>
                                <input 
                                    type="file" 
                                    accept="image/*" 
                                    @change="onPhotoBeforeChange" 
                                    class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                >
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Foto Sesudah Perbaikan (Redaman / Rumah)</label>
                                <input 
                                    type="file" 
                                    accept="image/*" 
                                    @change="onPhotoAfterChange" 
                                    class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Akhir / Hasil Penanganan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Catatan Kesimpulan / Hasil Penanganan <span class="text-red-500">*</span></label>
                        <textarea 
                            v-model="reportForm.resolution_notes" 
                            rows="2" 
                            placeholder="Contoh: Pekerjaan selesai pukul 11:30. Koneksi internet pelanggan kembali normal dan stabil. Pelanggan telah konfirmasi puas."
                            class="w-full rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 text-sm"
                            required
                        ></textarea>
                    </div>

                    <div class="pt-3 border-t flex justify-end gap-2">
                        <button type="button" @click="showReportModal = false" class="px-4 py-2 border rounded-xl text-gray-600 text-sm font-semibold hover:bg-gray-50">Batal</button>
                        <button type="submit" :disabled="reportForm.processing" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-md shadow-emerald-600/20 disabled:opacity-50">
                            {{ reportForm.processing ? 'Menyimpan...' : 'Kirim Laporan & Selesaikan' }}
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
    materials: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showReportModal = ref(false);
const selectedTicket = ref(null);

const reportForm = useForm({
    action_taken: '',
    optical_power_before: '',
    optical_power_after: '',
    start_time: '',
    end_time: '',
    resolution_notes: '',
    photo_before: null,
    photo_after: null,
    materials: [],
});

function performSearch() {
    router.get('/pgs/laporan', {
        search: search.value,
    }, { preserveState: true, replace: true });
}

function openReportModal(ticket) {
    selectedTicket.value = ticket;
    
    reportForm.action_taken = ticket.action_taken || '';
    reportForm.optical_power_before = ticket.optical_power_before || '';
    reportForm.optical_power_after = ticket.optical_power_after || '';
    reportForm.start_time = ticket.start_time || '';
    reportForm.end_time = ticket.end_time || '';
    reportForm.resolution_notes = ticket.resolution_notes || '';
    reportForm.photo_before = null;
    reportForm.photo_after = null;

    reportForm.materials = (ticket.materials || []).map(m => ({
        id: m.id,
        material_id: m.material_id,
        material_name: m.material?.name || 'Material',
        quantity_prepared: Number(m.quantity_prepared) || 0,
        quantity_used: Number(m.quantity_used) || Number(m.quantity_prepared) || 0,
        quantity_returned: Number(m.quantity_returned) || 0,
        unit: m.unit || m.material?.unit || 'pcs',
    }));

    showReportModal.value = true;
}

function onPhotoBeforeChange(e) {
    reportForm.photo_before = e.target.files[0] || null;
}

function onPhotoAfterChange(e) {
    reportForm.photo_after = e.target.files[0] || null;
}

function submitReport() {
    if (!selectedTicket.value) return;

    reportForm.post(`/pgs/${selectedTicket.value.id}/report`, {
        forceFormData: true,
        onSuccess: () => {
            showReportModal.value = false;
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

function formatDateTime(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
