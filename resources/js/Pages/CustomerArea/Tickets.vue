<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    customer: Object,
    tickets: Object
});

const showCreateModal = ref(false);
const selectedTicket = ref(null);

const form = useForm({
    category: 'Internet Lambat',
    priority: 'medium',
    subject: '',
    description: ''
});

const categories = [
    'Internet Lambat / Lemot',
    'Internet Mati Total / LOS Merah',
    'Lampu Indikator Modem Kedap-Kedip',
    'Ganti Password WiFi',
    'Pertanyaan Tagihan / Invoice',
    'Pindah Posisi Modem / Relokasi',
    'Lain-lain'
];

const submitTicket = () => {
    form.post(route('customer-area.tickets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return dateStr;
    }
};

const openDetail = (ticket) => {
    selectedTicket.value = ticket;
};
</script>

<template>
    <Head title="Tiket Bantuan — Area Pelanggan" />

    <CustomerLayout>
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tiket Bantuan & Pengaduan</h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-500">Laporkan keluhan teknis, gangguan koneksi, atau kendala layanan internet Anda.</p>
            </div>
            <button
                @click="showCreateModal = true"
                type="button"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl text-xs font-bold transition-all shadow-md shadow-indigo-600/30"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Tiket Baru
            </button>
        </div>

        <!-- Tickets Table Card -->
        <div class="bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 text-sm">Riwayat Laporan Gangguan</h3>
                <span class="text-xs text-slate-500">{{ tickets?.data?.length || 0 }} tiket tercatat</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200/70 text-left">
                    <thead class="bg-slate-50/70 text-slate-600">
                        <tr>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">No. Tiket</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Kategori</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Subjek Keluhan</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Prioritas</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Waktu Lapor</th>
                            <th class="px-6 py-3.5 text-xs font-bold uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-right text-xs font-bold uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <tr v-for="ticket in (tickets?.data || [])" :key="ticket.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-slate-900 whitespace-nowrap">
                                {{ ticket.ticket_number }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-700 whitespace-nowrap">
                                {{ ticket.category }}
                            </td>
                            <td class="px-6 py-4 text-slate-900 max-w-xs truncate font-semibold">
                                {{ ticket.subject || 'Laporan Gangguan' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="ticket.priority === 'critical' || ticket.priority === 'high'" class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-rose-50 text-rose-700 border border-rose-200">
                                    Tinggi
                                </span>
                                <span v-else-if="ticket.priority === 'medium'" class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                    Sedang
                                </span>
                                <span v-else class="px-2.5 py-1 text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Rendah
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                {{ formatDate(ticket.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="ticket.status === 'open'" class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                                    Menunggu
                                </span>
                                <span v-else-if="ticket.status === 'in_progress'" class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200">
                                    Diproses
                                </span>
                                <span v-else-if="ticket.status === 'resolved'" class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Selesai
                                </span>
                                <span v-else class="px-2.5 py-1 inline-flex text-[11px] font-bold rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                    Ditutup
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <button
                                    @click="openDetail(ticket)"
                                    type="button"
                                    class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs transition-colors"
                                >
                                    Detail
                                </button>
                            </td>
                        </tr>

                        <tr v-if="!tickets?.data || tickets.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                Belum ada laporan tiket gangguan yang dibuat.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tickets?.links && tickets.links.length > 3" class="px-6 py-4 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
                <div class="flex flex-wrap gap-1">
                    <template v-for="(link, p) in tickets.links" :key="p">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-1 text-xs text-slate-400 border border-slate-200 rounded-lg bg-slate-50" v-html="link.label" />
                        <a v-else :href="link.url" class="mr-1 mb-1 px-3 py-1 text-xs border rounded-lg hover:bg-slate-100 transition-colors" :class="{ 'bg-indigo-600 border-indigo-600 text-white font-bold': link.active, 'border-slate-200 text-slate-600 bg-white': !link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal: Buat Tiket Baru -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-base">Buat Tiket Bantuan</h3>
                            <p class="text-xs text-slate-500">Kirim laporan keluhan teknis Anda ke tim support</p>
                        </div>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="submitTicket" class="mt-6 space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kategori Masalah</label>
                        <select
                            v-model="form.category"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
                        >
                            <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tingkat Urgensi (Prioritas)</label>
                        <select
                            v-model="form.priority"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
                        >
                            <option value="low">Rendah (Pertanyaan umum / Kendala ringan)</option>
                            <option value="medium">Sedang (Internet lambat / Putus-nyambung)</option>
                            <option value="high">Tinggi (Internet mati total / LOS merah)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Judul / Subjek Keluhan</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            required
                            placeholder="Contoh: Lampu LOS modem berkedip merah sejak pagi"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Keterangan Rinci Gangguan</label>
                        <textarea
                            v-model="form.description"
                            required
                            rows="4"
                            placeholder="Jelaskan secara singkat gejala gangguan yang terjadi di rumah/lokasi Anda..."
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600"
                        ></textarea>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl font-bold transition-all shadow-md shadow-indigo-600/30 flex items-center gap-2"
                        >
                            <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>{{ form.processing ? 'Mengirim...' : 'Kirim Tiket Gangguan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Detail Tiket -->
        <div v-if="selectedTicket" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-100 animate-in fade-in zoom-in-95 duration-150 space-y-4 text-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="font-mono text-xs font-bold text-indigo-600">{{ selectedTicket.ticket_number }}</span>
                        <h3 class="font-bold text-slate-900 text-base mt-0.5">{{ selectedTicket.subject || 'Detail Tiket' }}</h3>
                    </div>
                    <button @click="selectedTicket = null" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-2xl">
                        <div>
                            <p class="text-slate-400 text-[11px]">Kategori</p>
                            <p class="font-bold text-slate-800">{{ selectedTicket.category }}</p>
                        </div>
                        <div>
                            <p class="text-slate-400 text-[11px]">Waktu Diajukan</p>
                            <p class="font-bold text-slate-800">{{ formatDate(selectedTicket.created_at) }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="font-bold text-slate-700 mb-1">Keterangan Gangguan:</p>
                        <div class="p-3 bg-slate-50 rounded-2xl text-slate-700 leading-relaxed whitespace-pre-line border border-slate-100">
                            {{ selectedTicket.description || '-' }}
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end">
                    <button
                        type="button"
                        @click="selectedTicket = null"
                        class="px-5 py-2 bg-slate-800 text-white rounded-xl font-bold transition-colors"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
