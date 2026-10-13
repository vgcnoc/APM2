<template>
    <AppLayout title="Pengaturan Absensi">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Potongan Absensi Reguler (Staf)</h1>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        Atur nominal potongan gaji otomatis untuk setiap pelanggaran absensi karyawan berbasis shift
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        href="/attendances"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5"
                    >
                        <span>←</span>
                        <span>Kembali ke Presensi</span>
                    </Link>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm shadow-blue-500/25 flex items-center gap-2 active:scale-95 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>💾 Simpan Pengaturan</span>
                    </button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- ============================================================== -->
                <!-- 1. POTONGAN TELAT MASUK (YELLOW ACCENT)                        -->
                <!-- ============================================================== -->
                <div class="bg-white rounded-2xl border border-amber-300/80 p-5 shadow-2xs space-y-4 hover:border-amber-400 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-amber-800 font-bold text-sm">
                            <span class="text-base text-amber-500">⚠️</span>
                            <span>Potongan Telat Masuk</span>
                        </div>
                        <!-- Toggle Switch -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.attendance_late_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Potongan (Rp)</label>
                            <input
                                type="number"
                                v-model="form.attendance_late_amount"
                                :disabled="!form.attendance_late_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="10000"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Mode Perhitungan</label>
                            <select
                                v-model="form.attendance_late_mode"
                                :disabled="!form.attendance_late_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400 cursor-pointer"
                            >
                                <option value="Per Kejadian">Per Kejadian</option>
                                <option value="Per Menit">Per Menit</option>
                                <option value="Bertingkat">Bertingkat</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Toleransi (menit)</label>
                            <input
                                type="number"
                                v-model="form.attendance_late_tolerance"
                                :disabled="!form.attendance_late_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="10"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Keterlambatan di bawah toleransi tidak dipotong</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- 2. POTONGAN LUPA ABSEN PULANG (ROSE/RED ACCENT)                -->
                <!-- ============================================================== -->
                <div class="bg-white rounded-2xl border border-rose-300/80 p-5 shadow-2xs space-y-4 hover:border-rose-400 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-rose-800 font-bold text-sm">
                            <span class="text-base text-rose-500">⚠️</span>
                            <span>Potongan Lupa Absen Pulang</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.attendance_missing_out_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Potongan (Rp)</label>
                            <input
                                type="number"
                                v-model="form.attendance_missing_out_amount"
                                :disabled="!form.attendance_missing_out_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="10000"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Dipotong per kejadian tidak absen pulang (clock-out)</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- 3. POTONGAN PULANG AWAL (BLUE ACCENT)                          -->
                <!-- ============================================================== -->
                <div class="bg-white rounded-2xl border border-blue-300/80 p-5 shadow-2xs space-y-4 hover:border-blue-400 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-blue-800 font-bold text-sm">
                            <span class="text-base text-blue-500">⚠️</span>
                            <span>Potongan Pulang Awal</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.attendance_early_leave_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Potongan (Rp)</label>
                            <input
                                type="number"
                                v-model="form.attendance_early_leave_amount"
                                :disabled="!form.attendance_early_leave_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="10000"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Toleransi (menit)</label>
                            <input
                                type="number"
                                v-model="form.attendance_early_leave_tolerance"
                                :disabled="!form.attendance_early_leave_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="0"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Pulang lebih awal (sebelum jam shift berakhir)</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- 4. POTONGAN SAKIT TANPA SURAT DOKTER (ORANGE ACCENT)           -->
                <!-- ============================================================== -->
                <div class="bg-white rounded-2xl border border-orange-300/80 p-5 shadow-2xs space-y-4 hover:border-orange-400 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-orange-800 font-bold text-sm">
                            <span class="text-base text-orange-500">⚠️</span>
                            <span>Potongan Sakit (Tanpa Surat Dokter)</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.attendance_sick_no_cert_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Potongan (Rp)</label>
                            <input
                                type="number"
                                v-model="form.attendance_sick_no_cert_amount"
                                :disabled="!form.attendance_sick_no_cert_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="20000"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Dipotong per hari izin sakit tanpa surat dokter</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- 5. POTONGAN IZIN TIDAK MASUK (PURPLE ACCENT)                   -->
                <!-- ============================================================== -->
                <div class="bg-white rounded-2xl border border-purple-300/80 p-5 shadow-2xs space-y-4 hover:border-purple-400 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-purple-800 font-bold text-sm">
                            <span class="text-base text-purple-500">⚠️</span>
                            <span>Potongan Izin Tidak Masuk</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.attendance_permit_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Potongan (Rp)</label>
                            <input
                                type="number"
                                v-model="form.attendance_permit_amount"
                                :disabled="!form.attendance_permit_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="25000"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Dipotong per hari izin tidak masuk kerja</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- 6. POTONGAN ALPHA TANPA KETERANGAN (RED ACCENT)                -->
                <!-- ============================================================== -->
                <div class="bg-white rounded-2xl border border-red-300/80 p-5 shadow-2xs space-y-4 hover:border-red-400 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-red-800 font-bold text-sm">
                            <span class="text-base text-red-500">⚠️</span>
                            <span>Potongan Alpha (Tanpa Keterangan)</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.attendance_alpha_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Potongan (Rp)</label>
                            <input
                                type="number"
                                v-model="form.attendance_alpha_amount"
                                :disabled="!form.attendance_alpha_enabled"
                                class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                placeholder="35000"
                            />
                            <p class="text-[10px] text-slate-400 mt-1">Dipotong per hari tidak hadir tanpa keterangan apapun</p>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- 7. CARD RINGKASAN POTONGAN ABSENSI                             -->
                <!-- ============================================================== -->
                <div class="bg-slate-50/80 rounded-2xl border border-slate-200 p-5 space-y-3">
                    <div class="flex items-center gap-2 text-slate-800 font-bold text-sm">
                        <span>📋</span>
                        <span>Ringkasan Potongan Absensi</span>
                    </div>

                    <ul class="space-y-1.5 text-xs text-slate-600 pl-1">
                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>
                                <strong>Telat Masuk:</strong>
                                <span v-if="form.attendance_late_enabled" class="text-amber-700 font-semibold">
                                    Rp {{ Number(form.attendance_late_amount).toLocaleString('id-ID') }}
                                    ({{ form.attendance_late_mode.toLowerCase() }}, toleransi {{ form.attendance_late_tolerance }} menit)
                                </span>
                                <span v-else class="text-slate-400 italic">Nonaktif</span>
                            </span>
                        </li>

                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>
                                <strong>Lupa Absen Pulang:</strong>
                                <span v-if="form.attendance_missing_out_enabled" class="text-rose-700 font-semibold">
                                    Rp {{ Number(form.attendance_missing_out_amount).toLocaleString('id-ID') }} per kejadian
                                </span>
                                <span v-else class="text-slate-400 italic">Nonaktif</span>
                            </span>
                        </li>

                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>
                                <strong>Pulang Awal:</strong>
                                <span v-if="form.attendance_early_leave_enabled" class="text-blue-700 font-semibold">
                                    Rp {{ Number(form.attendance_early_leave_amount).toLocaleString('id-ID') }} per kejadian (toleransi {{ form.attendance_early_leave_tolerance }} menit)
                                </span>
                                <span v-else class="text-slate-400 italic">Nonaktif</span>
                            </span>
                        </li>

                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>
                                <strong>Sakit (tanpa surat):</strong>
                                <span v-if="form.attendance_sick_no_cert_enabled" class="text-orange-700 font-semibold">
                                    Rp {{ Number(form.attendance_sick_no_cert_amount).toLocaleString('id-ID') }} per hari
                                </span>
                                <span v-else class="text-slate-400 italic">Nonaktif</span>
                            </span>
                        </li>

                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>
                                <strong>Izin Tidak Masuk:</strong>
                                <span v-if="form.attendance_permit_enabled" class="text-purple-700 font-semibold">
                                    Rp {{ Number(form.attendance_permit_amount).toLocaleString('id-ID') }} per hari
                                </span>
                                <span v-else class="text-slate-400 italic">Nonaktif</span>
                            </span>
                        </li>

                        <li class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>
                                <strong>Alpha:</strong>
                                <span v-if="form.attendance_alpha_enabled" class="text-red-700 font-semibold">
                                    Rp {{ Number(form.attendance_alpha_amount).toLocaleString('id-ID') }} per hari
                                </span>
                                <span v-else class="text-slate-400 italic">Nonaktif</span>
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- ============================================================== -->
                <!-- 8. JADWAL OTOMATIS POTONGAN ABSENSI (CRON)                     -->
                <!-- ============================================================== -->
                <div class="pt-6 space-y-4 border-t border-slate-200">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 tracking-tight">Jadwal Otomatis Potongan Absensi (Cron)</h2>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            Atur jam eksekusi otomatis potongan absensi harian. Waktu ideal: memotong saat pergantian shift/malamnya.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-cyan-300/80 p-5 shadow-2xs space-y-4 hover:border-cyan-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-cyan-900 font-bold text-sm">
                                <span class="text-base text-cyan-600">⏰</span>
                                <span>Aktifkan Cron Potongan Absensi</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.attendance_cron_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Eksekusi</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="23"
                                    v-model="form.attendance_cron_hour"
                                    :disabled="!form.attendance_cron_enabled"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400 text-center font-mono font-bold"
                                    placeholder="23"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Menit</label>
                                <input
                                    type="number"
                                    min="0"
                                    max="59"
                                    v-model="form.attendance_cron_minute"
                                    :disabled="!form.attendance_cron_enabled"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400 text-center font-mono font-bold"
                                    placeholder="30"
                                />
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-500">
                            Cron akan berjalan setiap hari jam
                            <span class="font-bold text-slate-800">
                                {{ String(form.attendance_cron_hour).padStart(2, '0') }}:{{ String(form.attendance_cron_minute).padStart(2, '0') }} WIB
                            </span>
                            untuk memproses potongan absensi hari sebelumnya.
                        </p>
                    </div>
                </div>

                <!-- BOTTOM SUBMIT BAR -->
                <div class="pt-4 flex items-center justify-end gap-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-blue-500/25 flex items-center gap-2 active:scale-95 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Menyimpan Perubahan...</span>
                        <span v-else>💾 Simpan Semua Pengaturan</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    attendance_late_enabled: props.settings?.attendance_late_enabled ?? true,
    attendance_late_amount: props.settings?.attendance_late_amount ?? 10000,
    attendance_late_mode: props.settings?.attendance_late_mode ?? 'Per Kejadian',
    attendance_late_tolerance: props.settings?.attendance_late_tolerance ?? 10,

    attendance_missing_out_enabled: props.settings?.attendance_missing_out_enabled ?? true,
    attendance_missing_out_amount: props.settings?.attendance_missing_out_amount ?? 10000,

    attendance_early_leave_enabled: props.settings?.attendance_early_leave_enabled ?? true,
    attendance_early_leave_amount: props.settings?.attendance_early_leave_amount ?? 10000,
    attendance_early_leave_tolerance: props.settings?.attendance_early_leave_tolerance ?? 0,

    attendance_sick_no_cert_enabled: props.settings?.attendance_sick_no_cert_enabled ?? true,
    attendance_sick_no_cert_amount: props.settings?.attendance_sick_no_cert_amount ?? 20000,

    attendance_permit_enabled: props.settings?.attendance_permit_enabled ?? true,
    attendance_permit_amount: props.settings?.attendance_permit_amount ?? 25000,

    attendance_alpha_enabled: props.settings?.attendance_alpha_enabled ?? true,
    attendance_alpha_amount: props.settings?.attendance_alpha_amount ?? 35000,

    attendance_cron_enabled: props.settings?.attendance_cron_enabled ?? true,
    attendance_cron_hour: props.settings?.attendance_cron_hour ?? 23,
    attendance_cron_minute: props.settings?.attendance_cron_minute ?? 30,
});

const submit = () => {
    form.post(route('settings.attendance.update'), {
        preserveScroll: true,
    });
};
</script>
