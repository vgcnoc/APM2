<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DashboardAttendanceWidget from '@/Components/Attendance/DashboardAttendanceWidget.vue';
import DashboardWorstAttendanceWidget from '@/Components/Attendance/DashboardWorstAttendanceWidget.vue';
import CustomerSearchWidget from '@/Components/Dashboard/CustomerSearchWidget.vue';

defineProps({
    stats: Object,
    surveyTasks: Array,
    installationTasks: Array,
    ticketTasks: Array,
    maintenanceTasks: { type: Array, default: () => [] },
    attendanceData: { type: Object, default: () => ({}) },
    worstAttendanceData: { type: Object, default: () => ({}) },
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) {
        return dateStr;
    }
};
</script>

<template>
    <AppLayout title="Dashboard Teknisi & Maintenance" subtitle="Ringkasan tugas lapangan, pemeliharaan jalur, dan jadwal kerja Anda">
        <!-- Presensi Reguler Teknisi Widget -->
        <DashboardAttendanceWidget :attendance-data="attendanceData" class="mb-6" />

        <!-- ═══ Evaluasi Pelanggaran Presensi, Absen Terburuk & Potongan Denda ═══ -->
        <DashboardWorstAttendanceWidget :data="worstAttendanceData" class="mb-6" />

        <!-- ═══ BANNER UTAMA: PUSAT EKSEKUSI & LAPORAN LAPANGAN ═══ -->
        <div class="mb-6 bg-gradient-to-r from-teal-600 via-teal-700 to-emerald-800 rounded-3xl p-5 md:p-6 text-white shadow-xl shadow-teal-900/10 flex flex-col md:flex-row items-center justify-between gap-5 border border-teal-500/30">
            <div class="space-y-1.5 text-center md:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-xs text-xs font-bold text-white uppercase tracking-wider">
                    ⚡ Pusat Kerja Lapangan Terpadu
                </div>
                <h2 class="text-xl md:text-2xl font-black tracking-tight">Progress Harian Operasional</h2>
                <p class="text-xs md:text-sm text-teal-100 max-w-2xl leading-relaxed">
                    Halaman utama untuk mengerjakan tugas hari ini: update status <b>Sedang Dikerjakan / Selesai</b>, catat jam mulai & selesai, ukur redaman optik (OPM dBm), dan upload foto bukti pekerjaan di lapangan.
                </p>
            </div>
            <Link 
                href="/operational/daily-progress" 
                class="shrink-0 px-6 py-3.5 rounded-2xl bg-white text-teal-800 hover:bg-teal-50 font-extrabold text-xs md:text-sm shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-0.5 flex items-center gap-2.5 cursor-pointer"
            >
                <span>Buka Progress Harian</span>
                <span class="text-lg leading-none">➔</span>
            </Link>
        </div>

        <!-- ═══ Pencarian Cepat Pelanggan & Foto Rumah (Tampil untuk Semua User) ═══ -->
        <CustomerSearchWidget class="mb-6" />

        <!-- Statistik Cards (4 Kolom Lengkap) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-8">
            <StatCard 
                title="Tugas Survey" 
                :value="stats.survey_assigned || 0" 
                icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" 
                color="blue" 
            />
            <StatCard 
                title="Tugas Instalasi" 
                :value="stats.installation_assigned || 0" 
                icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" 
                color="emerald" 
            />
            <StatCard 
                title="Tiket Gangguan" 
                :value="stats.tickets_assigned || 0" 
                icon="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" 
                color="rose" 
            />
            <StatCard 
                title="Pemeliharaan Jalur" 
                :value="stats.maintenance_assigned || 0" 
                icon="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" 
                color="teal" 
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Daftar Tugas Pemeliharaan Jalur (ODP, ODC, Jalur Utama) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-100 dark:border-slate-800 overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-gray-100 dark:border-slate-800 bg-teal-50/40 dark:bg-teal-950/20 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <span class="text-lg">🛠️</span>
                        Tugas Pemeliharaan Jalur (ODP / ODC / Jalur Utama)
                        <span class="text-xs bg-teal-100 text-teal-800 font-bold px-2 py-0.5 rounded-full ml-1">{{ maintenanceTasks.length }}</span>
                    </h3>
                    <Link href="/operational/daily-progress" class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 flex items-center gap-1">
                        Buka di Progress Harian &rarr;
                    </Link>
                </div>
                <div class="p-0 overflow-x-auto">
                    <div v-if="maintenanceTasks.length === 0" class="p-8 text-center text-gray-500 dark:text-slate-400 text-sm">
                        Belum ada tugas pemeliharaan jalur yang ditugaskan kepada Anda hari ini.
                    </div>
                    <table v-else class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-50/75 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-semibold">
                                <th class="p-3">No. Perawatan</th>
                                <th class="p-3">Kategori & Judul</th>
                                <th class="p-3">Target Lokasi / Aset</th>
                                <th class="p-3">Prioritas</th>
                                <th class="p-3">Status</th>
                                <th class="p-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                            <tr v-for="task in maintenanceTasks" :key="task.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="p-3 font-mono font-bold text-teal-700 dark:text-teal-400">{{ task.maintenance_number }}</td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-800 dark:text-white flex items-center gap-1.5">
                                        <span>{{ task.category_icon || '🛠️' }}</span>
                                        <span>{{ task.title }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400">{{ task.category_label }}</span>
                                </td>
                                <td class="p-3 font-mono text-slate-700 dark:text-slate-300">
                                    {{ task.location }} <span class="text-slate-400">({{ task.area_name }})</span>
                                </td>
                                <td class="p-3">
                                    <span v-if="task.priority === 'urgent'" class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-rose-100 text-rose-700">Mendesak</span>
                                    <span v-else class="px-2 py-0.5 text-[11px] font-semibold rounded-md bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Normal</span>
                                </td>
                                <td class="p-3">
                                    <span v-if="task.status === 'completed'" class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-emerald-100 text-emerald-700">✅ Selesai</span>
                                    <span v-else-if="task.status === 'in_progress'" class="px-2 py-0.5 text-[11px] font-bold rounded-md bg-blue-100 text-blue-700">🛠️ Sedang Dikerjakan</span>
                                    <span v-else class="px-2 py-0.5 text-[11px] font-semibold rounded-md bg-amber-100 text-amber-700">⏳ Jadwal</span>
                                </td>
                                <td class="p-3 text-right">
                                    <Link 
                                        href="/operational/daily-progress" 
                                        class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:text-teal-700 bg-teal-50 dark:bg-teal-900/30 border border-teal-200 dark:border-teal-800 px-3 py-1 rounded-xl transition-all inline-flex items-center gap-1"
                                    >
                                        Kerjakan 📝
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daftar Tugas Instalasi -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-100 dark:border-slate-800 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Tugas Instalasi ({{ installationTasks.length }})
                    </h3>
                    <Link href="/customers/installed" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Lihat Semua &rarr;</Link>
                </div>
                <div class="p-0">
                    <div v-if="installationTasks.length === 0" class="p-8 text-center text-gray-500 dark:text-slate-400 text-sm">
                        Tidak ada tugas instalasi.
                    </div>
                    <ul v-else class="divide-y divide-gray-100 dark:divide-slate-800">
                        <li v-for="task in installationTasks" :key="task.id" class="p-4 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-white">{{ task.name }}</h4>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ task.address }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-1 rounded-md">{{ formatDate(task.schedule_date) }}</span>
                                <Link :href="`/customers/${task.id}?source=instalasi`" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors">Buka</Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Daftar Tugas Survey -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-100 dark:border-slate-800 overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Tugas Survey ({{ surveyTasks.length }})
                    </h3>
                    <Link href="/customers/survey" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Lihat Semua &rarr;</Link>
                </div>
                <div class="p-0">
                    <div v-if="surveyTasks.length === 0" class="p-8 text-center text-gray-500 dark:text-slate-400 text-sm">
                        Tidak ada tugas survey.
                    </div>
                    <ul v-else class="divide-y divide-gray-100 dark:divide-slate-800">
                        <li v-for="task in surveyTasks" :key="task.id" class="p-4 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-white">{{ task.name }}</h4>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ task.address }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-xs font-medium text-blue-600 bg-blue-50 dark:bg-blue-950/40 px-2 py-1 rounded-md">{{ formatDate(task.schedule_date) }}</span>
                                <Link :href="`/customers/survey?open_report=${task.id}`" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors">Buka</Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Daftar Tiket Gangguan -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xs border border-gray-100 dark:border-slate-800 overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Tiket Gangguan ({{ ticketTasks.length }})
                    </h3>
                    <Link href="/tickets" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Lihat Semua &rarr;</Link>
                </div>
                <div class="p-0 overflow-x-auto">
                    <div v-if="ticketTasks.length === 0" class="p-8 text-center text-gray-500 dark:text-slate-400 text-sm">
                        Tidak ada tiket gangguan yang ditugaskan.
                    </div>
                    <table v-else class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-slate-800/50 border-b border-gray-100 dark:border-slate-800">
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">No Tiket</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Prioritas</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="p-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                            <tr v-for="task in ticketTasks" :key="task.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="p-3 text-sm font-medium text-gray-800 dark:text-white">{{ task.ticket_number }}</td>
                                <td class="p-3 text-sm text-gray-600 dark:text-slate-300">{{ task.customer_name }}</td>
                                <td class="p-3">
                                    <span v-if="task.priority === 'high'" class="px-2 py-1 text-xs font-bold rounded-md bg-rose-100 text-rose-700">Tinggi</span>
                                    <span v-else-if="task.priority === 'medium'" class="px-2 py-1 text-xs font-bold rounded-md bg-amber-100 text-amber-700">Sedang</span>
                                    <span v-else class="px-2 py-1 text-xs font-bold rounded-md bg-blue-100 text-blue-700">Rendah</span>
                                </td>
                                <td class="p-3 text-sm"><StatusBadge :status="task.status" /></td>
                                <td class="p-3 text-right">
                                    <Link :href="`/tickets/${task.id}`" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-800 px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors">Buka</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
