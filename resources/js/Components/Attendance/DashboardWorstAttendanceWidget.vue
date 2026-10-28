<template>
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden transition-all duration-300 hover:shadow-md">
        <!-- TOP ACCENT HEADER -->
        <div class="px-5 py-4 bg-slate-900 text-white flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-800">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="px-2.5 py-1 bg-rose-500/20 text-rose-300 rounded-lg text-[11px] font-black tracking-wider uppercase border border-rose-500/30 flex items-center gap-1.5 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                    ⚠️ Evaluasi Pelanggaran & Denda Presensi
                </span>

                <span class="text-xs text-slate-500">•</span>
                <span class="text-xs font-semibold text-slate-300">
                    Periode {{ data?.summary?.month_name || 'Bulan Berjalan' }}
                </span>

                <span v-if="data?.summary?.total_employees_with_penalties > 0" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                    {{ data.summary.total_employees_with_penalties }} Karyawan Perlu Evaluasi
                </span>
                <span v-else class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Disiplin Normal
                </span>
            </div>

            <div class="flex items-center gap-3">
                <a 
                    href="/settings/attendance" 
                    class="text-xs font-bold text-slate-400 hover:text-slate-200 flex items-center gap-1 transition"
                    title="Buka Pengaturan Aturan & Denda Potongan Absensi"
                >
                    <span>⚙️ Aturan Denda</span>
                </a>
                <span class="text-slate-700">|</span>
                <a 
                    href="/attendances" 
                    class="text-xs font-bold text-rose-400 hover:text-rose-300 hover:underline flex items-center gap-1 transition"
                >
                    <span>Rekap Lengkap Absensi</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        <!-- MAIN BODY -->
        <div class="p-5 sm:p-6 space-y-6">
            <!-- 1. STAT CARDS ROW (4 METRICS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1: Total Potongan Denda Absensi -->
                <div class="p-4 rounded-2xl bg-rose-50/60 border border-rose-100 dark:border-rose-900/40 relative overflow-hidden group hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700">Total Potongan Denda</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm">
                            💸
                        </div>
                    </div>
                    <div class="text-2xl font-black text-rose-900 mt-2 tracking-tight">
                        {{ formatRupiah(data?.summary?.total_deductions_amount || 0) }}
                    </div>
                    <div class="text-xs text-rose-600 font-semibold mt-1 flex items-center gap-1">
                        <span>{{ data?.summary?.total_employees_with_penalties || 0 }} Karyawan Terkena Penalti</span>
                    </div>
                </div>

                <!-- Card 2: Keterlambatan -->
                <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-100 dark:border-amber-900/40 relative overflow-hidden group hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700">Keterlambatan</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                            ⏱️
                        </div>
                    </div>
                    <div class="text-2xl font-black text-amber-900 mt-2 tracking-tight">
                        {{ data?.summary?.total_lates_count || 0 }} <span class="text-sm font-bold text-amber-700">Kejadian</span>
                    </div>
                    <div class="text-xs text-amber-700 mt-1 flex items-center justify-between">
                        <span>Akumulasi: <b>{{ data?.summary?.total_late_minutes || 0 }} mnt</b></span>
                        <span class="font-bold text-amber-800">{{ formatRupiah(data?.summary?.total_late_deductions || 0) }}</span>
                    </div>
                </div>

                <!-- Card 3: Lupa Absen Pulang & Cepat -->
                <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-100 dark:border-purple-900/40 relative overflow-hidden group hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-700">Tanpa Pulang / Cepat</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                            🚪
                        </div>
                    </div>
                    <div class="text-2xl font-black text-purple-900 mt-2 tracking-tight">
                        {{ (data?.summary?.total_missing_outs_count || 0) + (data?.summary?.total_early_leaves_count || 0) }} <span class="text-sm font-bold text-purple-700">Kejadian</span>
                    </div>
                    <div class="text-xs text-purple-700 mt-1 flex items-center justify-between">
                        <span>{{ data?.summary?.total_missing_outs_count || 0 }} No-Out · {{ data?.summary?.total_early_leaves_count || 0 }} Cepat</span>
                        <span class="font-bold text-purple-800">{{ formatRupiah((data?.summary?.total_missing_out_deductions || 0) + (data?.summary?.total_early_leave_deductions || 0)) }}</span>
                    </div>
                </div>

                <!-- Card 4: Alpha / Mangkir -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 relative overflow-hidden group hover:shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-600">Alpha / Mangkir Kerja</span>
                        <div class="w-8 h-8 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm">
                            ❌
                        </div>
                    </div>
                    <div class="text-2xl font-black text-slate-900 mt-2 tracking-tight">
                        {{ data?.summary?.total_alphas_count || 0 }} <span class="text-sm font-bold text-slate-500">Hari</span>
                    </div>
                    <div class="text-xs text-slate-600 mt-1 flex items-center justify-between">
                        <span>Mangkir Jadwal Kerja</span>
                        <span class="font-bold text-rose-700">{{ formatRupiah(data?.summary?.total_alpha_deductions || 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- 2. PAPAN PERINGKAT ABSEN TERBURUK (TABEL DAFTAR KARYAWAN) -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-tight flex items-center gap-1.5">
                            <span>📉</span>
                            <span>Papan Peringkat Absen Terburuk (Pelanggaran Tertinggi)</span>
                        </h4>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                            Evaluasi Disiplin
                        </span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium hidden sm:inline">
                        Diurutkan dari total denda potongan & pelanggaran tertinggi
                    </span>
                </div>

                <!-- List/Table -->
                <div v-if="hasInfractions" class="space-y-2.5">
                    <div 
                        v-for="(emp, idx) in worstEmployeesWithInfractions" 
                        :key="emp.id"
                        class="p-4 rounded-2xl border transition-all duration-200 bg-white hover:bg-slate-50/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4"
                        :class="[
                            idx === 0 ? 'border-rose-300 ring-1 ring-rose-300/40 bg-rose-50/15' : 
                            idx === 1 ? 'border-amber-300/80 bg-amber-50/10' : 
                            'border-slate-200/80'
                        ]"
                    >
                        <!-- Left: Rank Badge + Avatar + Info -->
                        <div class="flex items-center gap-3.5 min-w-0 md:w-1/3">
                            <!-- Rank Number -->
                            <div 
                                class="w-8 h-8 rounded-xl font-black text-xs flex items-center justify-center shrink-0 shadow-2xs"
                                :class="[
                                    idx === 0 ? 'bg-rose-600 text-white shadow-rose-500/25 ring-2 ring-rose-200' :
                                    idx === 1 ? 'bg-amber-500 text-white shadow-amber-500/25' :
                                    idx === 2 ? 'bg-orange-500 text-white' :
                                    'bg-slate-100 text-slate-600'
                                ]"
                            >
                                #{{ idx + 1 }}
                            </div>

                            <!-- Avatar -->
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-slate-700 to-slate-900 text-white font-bold flex items-center justify-center overflow-hidden shadow-xs">
                                    <img v-if="emp.photo_url" :src="emp.photo_url" :alt="emp.name" class="w-full h-full object-cover" />
                                    <span v-else>{{ emp.name.charAt(0).toUpperCase() }}</span>
                                </div>
                            </div>

                            <!-- Name & Position -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h5 class="text-sm font-bold text-slate-900 truncate">
                                        {{ emp.name }}
                                    </h5>
                                    <span v-if="idx === 0" class="px-1.5 py-0.2 rounded text-[9px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                                        TERBURUK #1
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5 truncate">
                                    <span class="font-semibold text-slate-700">{{ emp.position }}</span>
                                    <span>•</span>
                                    <span>{{ emp.branch }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Center: Breakdown Pelanggaran -->
                        <div class="flex flex-wrap items-center gap-3 text-xs md:flex-1">
                            <!-- Kehadiran -->
                            <div class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700">
                                <span class="text-[10px] text-slate-400 font-bold block">HADIR</span>
                                <span class="font-bold">{{ emp.presents }}/{{ emp.target_days }} Hari</span>
                            </div>

                            <!-- Telat -->
                            <div class="px-2.5 py-1 rounded-xl" :class="emp.lates > 0 ? 'bg-amber-100/70 text-amber-800' : 'bg-slate-50 text-slate-400'">
                                <span class="text-[10px] font-bold block" :class="emp.lates > 0 ? 'text-amber-700' : 'text-slate-400'">TELAT</span>
                                <span class="font-bold">{{ emp.lates }}x ({{ emp.late_minutes }}m)</span>
                            </div>

                            <!-- Lupa Pulang -->
                            <div class="px-2.5 py-1 rounded-xl" :class="emp.missing_outs > 0 ? 'bg-purple-100/70 text-purple-800' : 'bg-slate-50 text-slate-400'">
                                <span class="text-[10px] font-bold block" :class="emp.missing_outs > 0 ? 'text-purple-700' : 'text-slate-400'">NO-OUT</span>
                                <span class="font-bold">{{ emp.missing_outs }}x</span>
                            </div>

                            <!-- Alpha -->
                            <div class="px-2.5 py-1 rounded-xl" :class="emp.alphas > 0 ? 'bg-rose-100/70 text-rose-800' : 'bg-slate-50 text-slate-400'">
                                <span class="text-[10px] font-bold block" :class="emp.alphas > 0 ? 'text-rose-700' : 'text-slate-400'">ALPHA</span>
                                <span class="font-bold">{{ emp.alphas }} Hari</span>
                            </div>

                            <!-- Skor Kehadiran -->
                            <div class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-700">
                                <span class="text-[10px] text-slate-400 font-bold block">SKOR</span>
                                <span class="font-black" :class="emp.score < 70 ? 'text-rose-600' : 'text-amber-600'">{{ emp.score }}%</span>
                            </div>
                        </div>

                        <!-- Right: Total Denda & WhatsApp Action -->
                        <div class="flex items-center justify-between md:justify-end gap-3 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                            <div class="text-right">
                                <span class="text-[10px] font-bold text-rose-500 uppercase tracking-wider block">Potongan Denda</span>
                                <span class="text-base font-black text-rose-700 font-mono">
                                    {{ formatRupiah(emp.total_deduction) }}
                                </span>
                            </div>

                            <a 
                                v-if="emp.phone && emp.phone !== '-'"
                                :href="getWhatsAppTeguranLink(emp)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition flex items-center gap-1 shadow-2xs"
                                title="Kirim Pesan Teguran Sopan via WhatsApp"
                            >
                                <span>💬</span>
                                <span>Teguran WA</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Empty State (Jika Semua Karyawan Tertib Presensi) -->
                <div v-else class="p-8 text-center rounded-2xl border border-dashed border-emerald-200 bg-emerald-50/40 text-emerald-800 space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-2xl">
                        🎖️
                    </div>
                    <h5 class="text-sm font-black text-emerald-900">
                        Seluruh Karyawan Tertib Presensi!
                    </h5>
                    <p class="text-xs text-emerald-700 max-w-md mx-auto">
                        Tidak ditemukan pelanggaran keterlambatan, alpha, atau lupa absen pulang untuk periode {{ data?.summary?.month_name || 'ini' }}. Disiplin kerja berjalan dengan sangat baik.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            summary: {},
            worst_employees: [],
            all_infractions_count: 0
        })
    }
});

const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

const hasInfractions = computed(() => {
    return (props.data?.worst_employees || []).some(e => e.infractions_count > 0 || e.total_deduction > 0);
});

const worstEmployeesWithInfractions = computed(() => {
    const list = props.data?.worst_employees || [];
    // Prioritaskan yang memiliki pelanggaran/potongan
    const withViolations = list.filter(e => e.infractions_count > 0 || e.total_deduction > 0);
    return withViolations.length > 0 ? withViolations : list.slice(0, 5);
});

const getWhatsAppTeguranLink = (emp) => {
    let clean = (emp.phone || '').replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.substring(1);
    }
    const text = `Halo rekan ${emp.name},\n\nKami menginformasikan catatan presensi Anda periode ${props.data?.summary?.month_name || 'bulan ini'}:\n- Keterlambatan: ${emp.lates}x (${emp.late_minutes} menit)\n- Lupa Absen Pulang: ${emp.missing_outs}x\n- Alpha: ${emp.alphas} hari\n- Estimasi Potongan Denda: ${formatRupiah(emp.total_deduction)}\n\nMohon untuk lebih memperhatikan kedisiplinan jadwal kerja. Semangat bertugas!`;
    return `https://wa.me/${clean}?text=${encodeURIComponent(text)}`;
};
</script>

