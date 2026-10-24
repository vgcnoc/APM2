<template>
    <AppLayout title="Pengaturan Absensi">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- TOP HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ activeTab === 'rules' ? 'Peraturan Semua Jenis Absen & Jam Pulang' : (activeTab === 'deductions' ? 'Potongan Absensi Reguler (Staf)' : 'Reward Absen Terbaik') }}
                    </h1>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        {{ activeTab === 'rules'
                            ? 'Konfigurasi lengkap batas waktu terakhir absen pulang, durasi minimal kerja, serta regulasi presensi Reguler, Tim Khusus, dan Tugas Luar.'
                            : (activeTab === 'deductions'
                                ? 'Atur nominal potongan gaji otomatis untuk setiap pelanggaran absensi karyawan berbasis shift'
                                : 'Karyawan terbaik peringkat 1-3 per cabang mendapat reward cabang. Juara 1 tiap cabang otomatis masuk nominasi global — Top 3 global mendapat reward tambahan.')
                        }}
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

            <!-- TAB NAVIGATOR: PERATURAN ABSEN vs REWARD vs POTONGAN -->
            <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3 overflow-x-auto">
                <button
                    type="button"
                    @click="activeTab = 'rules'"
                    :class="[
                        activeTab === 'rules'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0'
                    ]"
                >
                    <span>📋</span>
                    <span>Peraturan Semua Jenis Absen & Jam Pulang</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'deductions'"
                    :class="[
                        activeTab === 'deductions'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0'
                    ]"
                >
                    <span>💳</span>
                    <span>Potongan Absensi & Cron</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'rewards'"
                    :class="[
                        activeTab === 'rewards'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0'
                    ]"
                >
                    <span>🏆</span>
                    <span>Reward Absen Terbaik</span>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- ============================================================== -->
                <!-- SECTION 0: PERATURAN SEMUA JENIS ABSEN & JAM PULANG             -->
                <!-- ============================================================== -->
                <div v-show="activeTab === 'rules'" class="space-y-6">
                    <!-- HERO GUIDE BANNER: JAWABAN CERDAS KAPAN TERAKHIR BISA ABSEN PULANG -->
                    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-950 to-blue-950 p-6 sm:p-8 text-white shadow-xl border border-blue-500/20">
                        <div class="absolute -right-16 -top-16 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                        <div class="relative z-10 space-y-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-semibold">
                                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>PANDUAN & REGULASI RESMI PRESENSI APM2</span>
                                </div>
                                <div class="text-[11px] text-slate-400 font-mono">
                                    Status Auto-Lock:
                                    <span :class="form.attendance_checkout_auto_lock ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold'">
                                        {{ form.attendance_checkout_auto_lock ? 'AKTIF (Terkunci Otomatis)' : 'NONAKTIF' }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white flex items-center gap-2.5">
                                    <span>🕒</span>
                                    <span>Kapan Terakhir Karyawan Bisa Absen Pulang?</span>
                                </h2>
                                <p class="text-xs sm:text-sm text-slate-300 mt-1.5 max-w-3xl leading-relaxed">
                                    Sistem absensi menerapkan regulasi presensi presisi untuk memastikan kedisiplinan dan perhitungan payroll yang akurat. Berikut adalah batas waktu dan ketentuan absen pulang:
                                </p>
                            </div>

                            <!-- 4 KEY METRICS PILLS -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                                <!-- Cut-off Terakhir -->
                                <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 space-y-1">
                                    <div class="text-[11px] text-blue-200 font-semibold flex items-center gap-1.5">
                                        <span>⏰</span>
                                        <span>Jam Cut-Off Terakhir</span>
                                    </div>
                                    <div class="text-2xl font-black text-amber-300 font-mono">
                                        {{ form.attendance_checkout_deadline_time || '23:30' }} <span class="text-xs font-normal text-slate-300">WIB</span>
                                    </div>
                                    <div class="text-[10px] text-slate-300">
                                        {{ form.attendance_checkout_deadline_mode === 'shift_relative' ? `Maks +${form.attendance_checkout_deadline_hours} Jam pasca shift` : 'Sebelum cron cut-off harian' }}
                                    </div>
                                </div>

                                <!-- Durasi Minimal Kerja -->
                                <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 space-y-1">
                                    <div class="text-[11px] text-blue-200 font-semibold flex items-center gap-1.5">
                                        <span>⏳</span>
                                        <span>Durasi Kerja Minimal</span>
                                    </div>
                                    <div class="text-2xl font-black text-emerald-300 font-mono">
                                        {{ form.attendance_checkout_min_work_hours || 4 }} <span class="text-xs font-normal text-slate-300">Jam</span>
                                    </div>
                                    <div class="text-[10px] text-slate-300">
                                        Sejak jam clock-in masuk
                                    </div>
                                </div>

                                <!-- Toleransi Pulang Cepat -->
                                <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 space-y-1">
                                    <div class="text-[11px] text-blue-200 font-semibold flex items-center gap-1.5">
                                        <span>⏱️</span>
                                        <span>Toleransi Pulang Cepat</span>
                                    </div>
                                    <div class="text-2xl font-black text-sky-300 font-mono">
                                        {{ form.attendance_checkout_early_tolerance || 15 }} <span class="text-xs font-normal text-slate-300">Menit</span>
                                    </div>
                                    <div class="text-[10px] text-slate-300">
                                        Sebelum shift usai tanpa denda
                                    </div>
                                </div>

                                <!-- Status Melewati Batas -->
                                <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 space-y-1">
                                    <div class="text-[11px] text-blue-200 font-semibold flex items-center gap-1.5">
                                        <span>🚫</span>
                                        <span>Jika Melewati Batas</span>
                                    </div>
                                    <div class="text-lg font-black text-rose-300 truncate">
                                        Lupa Absen Pulang
                                    </div>
                                    <div class="text-[10px] text-slate-300">
                                        Denda Rp {{ Number(form.attendance_missing_out_amount || 10000).toLocaleString('id-ID') }}
                                    </div>
                                </div>
                            </div>

                            <!-- TIMELINE VISUAL ALUR ABSENSI -->
                            <div class="bg-black/25 rounded-2xl p-4 border border-white/10 space-y-2">
                                <div class="text-xs font-bold text-slate-200 flex items-center gap-1.5">
                                    <span>🧭</span>
                                    <span>Kronologi & Alur Siklus Absensi Kerja Harian:</span>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-5 gap-2 text-xs">
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/5">
                                        <div class="font-bold text-blue-300 text-[11px]">1. Absen Masuk</div>
                                        <div class="text-[10px] text-slate-300 mt-0.5">Sesuai shift kerja (toleransi telat {{ form.attendance_late_tolerance }} mnt).</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/5">
                                        <div class="font-bold text-emerald-300 text-[11px]">2. Durasi Min. Kerja</div>
                                        <div class="text-[10px] text-slate-300 mt-0.5">Wajib kerja min. {{ form.attendance_checkout_min_work_hours }} jam sebelum tombol pulang aktif.</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/5">
                                        <div class="font-bold text-sky-300 text-[11px]">3. Selesai Shift</div>
                                        <div class="text-[10px] text-slate-300 mt-0.5">Boleh pulang s/d {{ form.attendance_checkout_early_tolerance }} mnt lebih awal tanpa denda.</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/5">
                                        <div class="font-bold text-amber-300 text-[11px]">4. Batas Pulang</div>
                                        <div class="text-[10px] text-slate-300 mt-0.5">Maksimal pukul {{ form.attendance_checkout_deadline_time }} WIB hari yang sama.</div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-white/5 border border-white/5">
                                        <div class="font-bold text-rose-300 text-[11px]">5. Lewat Batas</div>
                                        <div class="text-[10px] text-slate-300 mt-0.5">Terkunci otomatis, potong gaji Lupa Absen Pulang.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 1: PENGATURAN BATAS WAKTU PULANG & AUTO-LOCK -->
                    <div class="bg-white rounded-2xl border border-blue-300/90 p-5 sm:p-6 shadow-2xs space-y-5 hover:border-blue-400 transition-colors">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <div class="text-sm font-bold text-blue-900 flex items-center gap-2">
                                    <span class="text-base text-blue-600">🕒</span>
                                    <span>Aturan Batas Terakhir Absen Pulang (Clock-Out Cut-Off & Auto-Lock)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Atur jam maksimal karyawan dapat melakukan clock-out sebelum tombol terkunci otomatis
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.attendance_checkout_auto_lock" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Mode Perhitungan Batas Pulang
                                </label>
                                <select
                                    v-model="form.attendance_checkout_deadline_mode"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                                >
                                    <option value="before_cron">Sebelum Jam Cut-Off Malam (Rekomendasi - Tetap)</option>
                                    <option value="shift_relative">Berdasarkan Shift (+N Jam Setelah Shift Selesai)</option>
                                    <option value="fixed_time">Jam Khusus Tertentu</option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    {{ form.attendance_checkout_deadline_mode === 'before_cron'
                                        ? 'Karyawan bebas absen pulang kapan pun setelah shift hingga jam cut-off malam'
                                        : (form.attendance_checkout_deadline_mode === 'shift_relative'
                                            ? 'Batas pulang dihitung dinamis dari jam akhir shift masing-masing karyawan'
                                            : 'Batas pulang dipatok pada jam tepat di bawah ini') }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Jam Cut-Off Terakhir Absen Pulang (WIB)
                                </label>
                                <input
                                    type="time"
                                    v-model="form.attendance_checkout_deadline_time"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 font-mono font-semibold"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Standar default: <strong class="text-slate-700">23:30 WIB</strong>. Lewat jam ini tombol absen pulang terkunci.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Maksimal Jam Pasca Shift Usai (Jam)
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    max="12"
                                    v-model="form.attendance_checkout_deadline_hours"
                                    :disabled="form.attendance_checkout_deadline_mode !== 'shift_relative'"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Hanya berlaku jika mode shift dipilih (contoh: 4 jam setelah shift).
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2 border-t border-slate-100">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Durasi Kerja Minimal Sebelum Diizinkan Pulang (Jam)
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="12"
                                    v-model="form.attendance_checkout_min_work_hours"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                                    placeholder="4"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Mencegah karyawan absen masuk lalu langsung absen pulang. Default: <strong class="text-slate-700">4 jam</strong>.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Toleransi Pulang Awal (Menit)
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="120"
                                    v-model="form.attendance_checkout_early_tolerance"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                                    placeholder="15"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Pulang lebih cepat di bawah batas ini tidak dikenakan denda potongan. Default: <strong class="text-slate-700">15 menit</strong>.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: PERATURAN PRESENSI REGULER (STAF / WFO / SHIFT) -->
                    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 shadow-2xs space-y-5 hover:border-slate-300 transition-colors">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <div class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <span class="text-base text-blue-600">🏢</span>
                                    <span>Peraturan Presensi Reguler (Staf Kantor & Karyawan Shift / WFO)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Presensi standar karyawan kantor berbasis master shift, geofencing lokasi, dan kamera live
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-bold">
                                Menu: /attendances
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Toggle Selfie -->
                            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Wajib Foto Wajah (Selfie)</div>
                                    <div class="text-[10px] text-slate-500">Live camera anti upload galeri</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_regular_require_photo" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Toggle GPS -->
                            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Wajib Titik GPS Live</div>
                                    <div class="text-[10px] text-slate-500">Akurasi koordinat satelit</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_regular_require_gps" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Toggle Geofencing Strict -->
                            <div class="p-3.5 rounded-xl border border-slate-200/80 bg-slate-50/50 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Kunci Radius Kantor</div>
                                    <div class="text-[10px] text-slate-500">Geofencing kantor cabang</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_regular_strict_radius" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2 border-t border-slate-100">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Radius Default Kantor (Meter)
                                </label>
                                <input
                                    type="number"
                                    min="10"
                                    max="5000"
                                    v-model="form.attendance_regular_default_radius"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                                    placeholder="200"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Radius standar bila cabang belum diset.</p>
                            </div>

                            <!-- Toggle WFH -->
                            <div class="p-3 rounded-xl border border-slate-200/80 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Izinkan WFH</div>
                                    <div class="text-[10px] text-slate-500">Bekerja dari rumah</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_regular_allow_wfh" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Toggle Field Duty in Reguler -->
                            <div class="p-3 rounded-xl border border-slate-200/80 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Izinkan Dinas Luar</div>
                                    <div class="text-[10px] text-slate-500">Tugas keluar kantor</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_regular_allow_field" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <!-- Toggle Overtime -->
                            <div class="p-3 rounded-xl border border-slate-200/80 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-slate-800">Izinkan Lembur</div>
                                    <div class="text-[10px] text-slate-500">Overtime kerja shift</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_regular_allow_overtime" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: PERATURAN PRESENSI TIM KHUSUS (MOBILE & LAPANGAN) -->
                    <div class="bg-white rounded-2xl border border-purple-300/90 p-5 sm:p-6 shadow-2xs space-y-5 hover:border-purple-400 transition-colors">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <div class="text-sm font-bold text-purple-900 flex items-center gap-2">
                                    <span class="text-base text-purple-600">⚡</span>
                                    <span>Peraturan Presensi Tim Khusus (Operasional Lapangan, Proyek, & Serbu)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Presensi untuk tim lapangan fleksibel yang mobile tanpa batasan radius kantor tertentu
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-200 text-[11px] font-bold">
                                Menu: /absen-khusus
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Jam Mulai Standar
                                </label>
                                <input
                                    type="time"
                                    v-model="form.attendance_special_default_start"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20 font-mono font-semibold"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Default: 08:30 WIB</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Jam Selesai Standar
                                </label>
                                <input
                                    type="time"
                                    v-model="form.attendance_special_default_end"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20 font-mono font-semibold"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Default: 18:00 WIB</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Toleransi Telat (Menit)
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    max="120"
                                    v-model="form.attendance_special_default_tolerance"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20"
                                    placeholder="15"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Default: 15 Menit</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Batas Terakhir Pulang Tim Khusus
                                </label>
                                <input
                                    type="time"
                                    v-model="form.attendance_special_deadline_time"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20 font-mono font-semibold"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">Default: 23:30 WIB</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2 border-t border-slate-100">
                            <!-- Toggle Bebas Radius -->
                            <div class="p-3 rounded-xl border border-purple-200/80 bg-purple-50/40 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-purple-900">Bebas Radius Kantor</div>
                                    <div class="text-[10px] text-slate-500">Mobile area tanpa batas GPS radius</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_special_without_radius" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Denda Telat Tim Khusus (Rp)</label>
                                <input
                                    type="number"
                                    min="0"
                                    v-model="form.attendance_special_late_deduction"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Denda Lupa Pulang (Rp)</label>
                                <input
                                    type="number"
                                    min="0"
                                    v-model="form.attendance_special_missing_out_deduction"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Denda Alpha (Rp)</label>
                                <input
                                    type="number"
                                    min="0"
                                    v-model="form.attendance_special_alpha_deduction"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- CARD 4: PERATURAN PRESENSI TUGAS LUAR (FIELD DUTIES / TEKNISI) -->
                    <div class="bg-white rounded-2xl border border-emerald-300/90 p-5 sm:p-6 shadow-2xs space-y-5 hover:border-emerald-400 transition-colors">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div>
                                <div class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                                    <span class="text-base text-emerald-600">🚗</span>
                                    <span>Peraturan Presensi Tugas Luar (Dinas Luar / Field Duties Teknisi)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Pemberian izin dan penggantian absensi bagi teknisi lapangan atau staf dengan surat penugasan
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold">
                                Modul: Field Duties
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Mode Penggantian Presensi Default
                                </label>
                                <select
                                    v-model="form.attendance_field_default_mode"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20"
                                >
                                    <option value="replace_both">Gantikan Absen Masuk & Pulang Sekaligus (Rekomendasi)</option>
                                    <option value="replace_in">Hanya Gantikan Absen Masuk (Wajib Pulang Manual)</option>
                                    <option value="replace_out">Hanya Gantikan Absen Pulang (Wajib Masuk Manual)</option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Saat surat tugas luar diverifikasi/disetujui, kehadiran karyawan otomatis tercatat hadir.
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Batas Terakhir Penyelesaian Tugas Luar (WIB)
                                </label>
                                <input
                                    type="time"
                                    v-model="form.attendance_field_deadline_time"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 font-mono font-semibold"
                                />
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Batas waktu pelaporan dan upload bukti hasil pekerjaan dinas luar. Default: <strong class="text-slate-700">22:00 WIB</strong>.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-slate-100">
                            <!-- Toggle Foto Bukti -->
                            <div class="p-3.5 rounded-xl border border-emerald-200/80 bg-emerald-50/40 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-emerald-950">Wajib Foto Bukti Kerja</div>
                                    <div class="text-[10px] text-slate-500">Unggah foto dokumentasi lokasi</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_field_require_photo" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            <!-- Toggle GPS -->
                            <div class="p-3.5 rounded-xl border border-emerald-200/80 bg-emerald-50/40 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-emerald-950">Wajib Koordinat GPS Lapangan</div>
                                    <div class="text-[10px] text-slate-500">Pinpoint lokasi kerja riil</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_field_require_gps" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>

                            <!-- Toggle Auto Sync On-Duty -->
                            <div class="p-3.5 rounded-xl border border-emerald-200/80 bg-emerald-50/40 flex items-center justify-between">
                                <div>
                                    <div class="text-xs font-bold text-emerald-950">Auto-Sync Status ON-DUTY</div>
                                    <div class="text-[10px] text-slate-500">Sinkronkan ke status teknisi aktif</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.attendance_field_auto_sync" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- SECTION 1: REWARD ABSEN TERBAIK (LENGKAP SEMUA KATEGORI)       -->
                <!-- ============================================================== -->
                <div v-show="activeTab === 'rewards'" class="space-y-5">
                    <!-- 1. MAIN TOGGLE CARD: AKTIFKAN SISTEM REWARD ABSEN TERBAIK -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <span class="text-base">🏆</span>
                                <span>Aktifkan Sistem Reward Absen Terbaik</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Karyawan terbaik peringkat 1-3 per cabang mendapat reward cabang. Juara 1 tiap cabang otomatis masuk nominasi global — Top 3 global mendapat reward tambahan.
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.attendance_reward_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- 2. CARD: REWARD PER CABANG (BLUE BORDER) -->
                    <div class="bg-white rounded-2xl border border-blue-300/90 p-5 shadow-2xs space-y-4 hover:border-blue-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-blue-900 flex items-center gap-2">
                                    <span class="text-base text-blue-600">🏢</span>
                                    <span>Reward Per Cabang (Top 3 per Cabang)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">Top 3 karyawan terbaik di masing-masing cabang</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.attendance_reward_branch_enabled"
                                    :disabled="!form.attendance_reward_enabled"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                            </label>
                        </div>

                        <div v-show="form.attendance_reward_branch_enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span>🥇</span>
                                    <span>Juara 1</span>
                                </label>
                                <div class="relative rounded-xl shadow-2xs">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_branch_1"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_branch_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="200000"
                                    />
                                </div>
                                <p class="text-[11px] text-blue-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_branch_1 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span>🥈</span>
                                    <span>Juara 2</span>
                                </label>
                                <div class="relative rounded-xl shadow-2xs">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_branch_2"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_branch_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="150000"
                                    />
                                </div>
                                <p class="text-[11px] text-blue-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_branch_2 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span>🥉</span>
                                    <span>Juara 3</span>
                                </label>
                                <div class="relative rounded-xl shadow-2xs">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_branch_3"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_branch_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="100000"
                                    />
                                </div>
                                <p class="text-[11px] text-blue-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_branch_3 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                        <p v-show="!form.attendance_reward_branch_enabled" class="text-xs text-slate-400 italic">
                            Reward kategori per cabang sedang dinonaktifkan.
                        </p>
                    </div>

                    <!-- 3. CARD: REWARD GLOBAL (EMERALD/GREEN BORDER) -->
                    <div class="bg-white rounded-2xl border border-emerald-300/90 p-5 shadow-2xs space-y-4 hover:border-emerald-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                                    <span class="text-base text-emerald-600">🌐</span>
                                    <span>Reward Global (Juara 1 Tiap Cabang — Top 3)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Nominal reward untuk Top 3 dari nominasi global. Karyawan bisa dapat reward cabang + global sekaligus.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.attendance_reward_global_enabled"
                                    :disabled="!form.attendance_reward_enabled"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                            </label>
                        </div>

                        <div v-show="form.attendance_reward_global_enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span>🥇</span>
                                    <span>Juara 1</span>
                                </label>
                                <div class="relative rounded-xl shadow-2xs">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_global_1"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_global_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="200000"
                                    />
                                </div>
                                <p class="text-[11px] text-emerald-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_global_1 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span>🥈</span>
                                    <span>Juara 2</span>
                                </label>
                                <div class="relative rounded-xl shadow-2xs">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_global_2"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_global_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="150000"
                                    />
                                </div>
                                <p class="text-[11px] text-emerald-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_global_2 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span>🥉</span>
                                    <span>Juara 3</span>
                                </label>
                                <div class="relative rounded-xl shadow-2xs">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_global_3"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_global_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="100000"
                                    />
                                </div>
                                <p class="text-[11px] text-emerald-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_global_3 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                        <p v-show="!form.attendance_reward_global_enabled" class="text-xs text-slate-400 italic">
                            Reward kategori global sedang dinonaktifkan.
                        </p>
                    </div>

                    <!-- 4. CARD: REWARD MAINTENANCE (ORANGE BORDER) -->
                    <div class="bg-white rounded-2xl border border-orange-200/90 p-5 shadow-2xs space-y-4 hover:border-orange-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-orange-600 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-orange-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                    </svg>
                                    <span>Reward Maintenance (Top 3 Global)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Nominal reward untuk peringkat 1-3 karyawan maintenance terbaik.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.attendance_reward_maintenance_enabled"
                                    :disabled="!form.attendance_reward_enabled"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                            </label>
                        </div>

                        <div v-show="form.attendance_reward_maintenance_enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    <span>Juara 1</span>
                                </label>
                                <div class="relative rounded-xl">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_maint_1"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_maintenance_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="200000"
                                    />
                                </div>
                                <p class="text-[11px] text-orange-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_maint_1 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    <span>Juara 2</span>
                                </label>
                                <div class="relative rounded-xl">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_maint_2"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_maintenance_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="150000"
                                    />
                                </div>
                                <p class="text-[11px] text-orange-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_maint_2 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    <span>Juara 3</span>
                                </label>
                                <div class="relative rounded-xl">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_maint_3"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_maintenance_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-orange-500 focus:ring-orange-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="100000"
                                    />
                                </div>
                                <p class="text-[11px] text-orange-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_maint_3 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                        <p v-show="!form.attendance_reward_maintenance_enabled" class="text-xs text-slate-400 italic">
                            Reward kategori maintenance sedang dinonaktifkan.
                        </p>
                    </div>

                    <!-- 5. CARD: REWARD TIM KHUSUS (CYAN BORDER) -->
                    <div class="bg-white rounded-2xl border border-cyan-200/90 p-5 shadow-2xs space-y-4 hover:border-cyan-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-bold text-cyan-600 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-cyan-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    <span>Reward Tim Khusus (Top 3 per Grup)</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Nominal reward untuk peringkat 1-3 di setiap grup Tim Khusus (berlaku seragam semua grup).
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.attendance_reward_special_team_enabled"
                                    :disabled="!form.attendance_reward_enabled"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                            </label>
                        </div>

                        <div v-show="form.attendance_reward_special_team_enabled" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    <span>Juara 1</span>
                                </label>
                                <div class="relative rounded-xl">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_special_1"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_special_team_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-cyan-500 focus:ring-cyan-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="200000"
                                    />
                                </div>
                                <p class="text-[11px] text-cyan-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_special_1 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    <span>Juara 2</span>
                                </label>
                                <div class="relative rounded-xl">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_special_2"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_special_team_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-cyan-500 focus:ring-cyan-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="150000"
                                    />
                                </div>
                                <p class="text-[11px] text-cyan-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_special_2 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="8" r="6" />
                                        <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11" />
                                    </svg>
                                    <span>Juara 3</span>
                                </label>
                                <div class="relative rounded-xl">
                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                        <span class="text-xs font-semibold text-slate-400">Rp</span>
                                    </div>
                                    <input
                                        type="number"
                                        v-model="form.attendance_reward_special_3"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_special_team_enabled"
                                        class="block w-full pl-9 text-xs rounded-xl border-slate-200 focus:border-cyan-500 focus:ring-cyan-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                        placeholder="100000"
                                    />
                                </div>
                                <p class="text-[11px] text-cyan-600 font-medium mt-1">Rp {{ Number(form.attendance_reward_special_3 || 0).toLocaleString('id-ID') }}</p>
                            </div>
                        </div>
                        <p v-show="!form.attendance_reward_special_team_enabled" class="text-xs text-slate-400 italic">
                            Reward kategori tim khusus sedang dinonaktifkan.
                        </p>
                    </div>

                    <!-- 10. CARD: AUTO GENERATE REWARD (PURPLE BORDER) -->
                    <div class="bg-white rounded-2xl border border-purple-200/90 p-5 shadow-2xs space-y-4 hover:border-purple-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-purple-600 font-bold text-sm">
                                <svg class="w-4 h-4 text-purple-600 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                                </svg>
                                <span>Auto Generate Reward</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.attendance_reward_auto_generate_enabled"
                                    :disabled="!form.attendance_reward_enabled"
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 peer-disabled:opacity-50"></div>
                            </label>
                        </div>

                        <p class="text-xs text-slate-500">
                            Jika aktif, reward absen terbaik akan otomatis di-generate pada tanggal yang ditentukan setiap bulan (untuk periode bulan sebelumnya).
                        </p>

                        <div class="space-y-4 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Generate (1-28)</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="28"
                                    v-model="form.attendance_reward_auto_date"
                                    :disabled="!form.attendance_reward_enabled || !form.attendance_reward_auto_generate_enabled"
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20 disabled:bg-slate-100 disabled:text-slate-400 font-semibold"
                                    placeholder="1"
                                />
                                <p class="text-[11px] text-slate-400 mt-1.5">
                                    Contoh: Isi 1 → reward bulan Maret auto di-generate pada tanggal 1 April.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jam (WIB)</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="23"
                                        v-model="form.attendance_reward_auto_hour"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_auto_generate_enabled"
                                        class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20 disabled:bg-slate-100 disabled:text-slate-400 text-center font-mono font-semibold"
                                        placeholder="00"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Menit</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="59"
                                        v-model="form.attendance_reward_auto_minute"
                                        :disabled="!form.attendance_reward_enabled || !form.attendance_reward_auto_generate_enabled"
                                        class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring-purple-500/20 disabled:bg-slate-100 disabled:text-slate-400 text-center font-mono font-semibold"
                                        placeholder="06"
                                    />
                                </div>
                            </div>

                            <p class="text-xs text-slate-500 pt-1">
                                Cron akan berjalan pada tanggal
                                <span class="font-bold text-slate-800">{{ form.attendance_reward_auto_date }}</span>
                                setiap bulan jam
                                <span class="font-bold text-slate-800">
                                    {{ String(form.attendance_reward_auto_hour).padStart(2, '0') }}:{{ String(form.attendance_reward_auto_minute).padStart(2, '0') }} WIB
                                </span>.
                            </p>
                        </div>
                    </div>

                    <!-- 11. CARD: RINGKASAN REWARD ABSEN TERBAIK LENGKAP -->
                    <div class="bg-slate-50/90 rounded-2xl border border-slate-200 p-5 space-y-4">
                        <div class="text-xs font-bold text-slate-800 flex items-center gap-2">
                            <span>📋</span>
                            <span>Ringkasan Reward Absen Terbaik:</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs text-slate-700">
                            <!-- Kolom Per Cabang -->
                            <div class="space-y-1.5 p-3 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="font-bold text-blue-900 flex items-center gap-1.5">
                                    <span>🏢</span>
                                    <span>Per Cabang (Top 3):</span>
                                </div>
                                <div class="space-y-0.5 text-slate-600 text-[11px]">
                                    <div>🥇 Juara 1: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_branch_1).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥈 Juara 2: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_branch_2).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥉 Juara 3: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_branch_3).toLocaleString('id-ID') }}</strong></div>
                                </div>
                            </div>

                            <!-- Kolom Global -->
                            <div class="space-y-1.5 p-3 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="font-bold text-emerald-900 flex items-center gap-1.5">
                                    <span>🌐</span>
                                    <span>Global (Top 3):</span>
                                </div>
                                <div class="space-y-0.5 text-slate-600 text-[11px]">
                                    <div>🥇 Juara 1: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_global_1).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥈 Juara 2: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_global_2).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥉 Juara 3: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_global_3).toLocaleString('id-ID') }}</strong></div>
                                </div>
                            </div>

                            <!-- Kolom Maintenance -->
                            <div class="space-y-1.5 p-3 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="font-bold text-orange-900 flex items-center gap-1.5">
                                    <span>🛠️</span>
                                    <span>Maintenance (Top 3):</span>
                                </div>
                                <div class="space-y-0.5 text-slate-600 text-[11px]">
                                    <div>🥇 Juara 1: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_maint_1).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥈 Juara 2: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_maint_2).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥉 Juara 3: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_maint_3).toLocaleString('id-ID') }}</strong></div>
                                </div>
                            </div>

                            <!-- Kolom Tim Khusus -->
                            <div class="space-y-1.5 p-3 bg-white rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="font-bold text-cyan-900 flex items-center gap-1.5">
                                    <span>👥</span>
                                    <span>Tim Khusus (Top 3):</span>
                                </div>
                                <div class="space-y-0.5 text-slate-600 text-[11px]">
                                    <div>🥇 Juara 1: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_special_1).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥈 Juara 2: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_special_2).toLocaleString('id-ID') }}</strong></div>
                                    <div>🥉 Juara 3: <strong class="text-slate-800">Rp {{ Number(form.attendance_reward_special_3).toLocaleString('id-ID') }}</strong></div>
                                </div>
                            </div>
                        </div>

                        <p class="text-[11px] text-amber-700 italic pt-1 flex items-center gap-1.5">
                            <span>⚡</span>
                            <span>Karyawan Juara 1 cabang dapat memperoleh reward cabang + global sekaligus apabila memenuhi kualifikasi poin kehadiran.</span>
                        </p>
                    </div>

                    <!-- 12. CARD: KETERANGAN REWARD -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-2">
                        <label class="block text-xs font-bold text-slate-700">Keterangan Reward</label>
                        <input
                            type="text"
                            v-model="form.attendance_reward_note"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                            placeholder="Contoh: Reward diberikan setiap akhir bulan bersama gaji"
                        />
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- SECTION 2: POTONGAN ABSENSI REGULER & CRON                     -->
                <!-- ============================================================== -->
                <div v-show="activeTab === 'deductions'" class="space-y-6">
                    <!-- 1. POTONGAN TELAT MASUK (YELLOW ACCENT) -->
                    <div class="bg-white rounded-2xl border border-amber-300/80 p-5 shadow-2xs space-y-4 hover:border-amber-400 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-amber-800 font-bold text-sm">
                                <span class="text-base text-amber-500">⚠️</span>
                                <span>Potongan Telat Masuk</span>
                            </div>
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

                    <!-- 2. POTONGAN LUPA ABSEN PULANG (ROSE/RED ACCENT) -->
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

                    <!-- 3. POTONGAN PULANG AWAL (BLUE ACCENT) -->
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

                    <!-- 4. POTONGAN SAKIT TANPA SURAT DOKTER (ORANGE ACCENT) -->
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

                    <!-- 5. POTONGAN IZIN TIDAK MASUK (PURPLE ACCENT) -->
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

                    <!-- 6. POTONGAN ALPHA TANPA KETERANGAN (RED ACCENT) -->
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

                    <!-- 7. CARD RINGKASAN POTONGAN ABSENSI -->
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

                    <!-- 8. JADWAL OTOMATIS POTONGAN ABSENSI (CRON) -->
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
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    settings: Object,
});

const activeTab = ref('rules'); // Default tab peraturan semua jenis absen & jam pulang

const form = useForm({
    // PERATURAN SEMUA JENIS ABSEN & BATAS TERAKHIR PULANG
    attendance_checkout_deadline_mode: props.settings?.attendance_checkout_deadline_mode ?? 'before_cron',
    attendance_checkout_deadline_time: props.settings?.attendance_checkout_deadline_time ?? '23:30',
    attendance_checkout_deadline_hours: props.settings?.attendance_checkout_deadline_hours ?? 4,
    attendance_checkout_min_work_hours: props.settings?.attendance_checkout_min_work_hours ?? 4,
    attendance_checkout_early_tolerance: props.settings?.attendance_checkout_early_tolerance ?? 15,
    attendance_checkout_auto_lock: props.settings?.attendance_checkout_auto_lock ?? true,

    // Reguler (Staf & Shift Kantor / WFO)
    attendance_regular_require_photo: props.settings?.attendance_regular_require_photo ?? true,
    attendance_regular_require_gps: props.settings?.attendance_regular_require_gps ?? true,
    attendance_regular_strict_radius: props.settings?.attendance_regular_strict_radius ?? true,
    attendance_regular_default_radius: props.settings?.attendance_regular_default_radius ?? 200,
    attendance_regular_allow_wfh: props.settings?.attendance_regular_allow_wfh ?? false,
    attendance_regular_allow_field: props.settings?.attendance_regular_allow_field ?? true,
    attendance_regular_allow_overtime: props.settings?.attendance_regular_allow_overtime ?? true,

    // Tim Khusus (Mobile / Proyek / Lapangan)
    attendance_special_default_start: props.settings?.attendance_special_default_start ?? '08:30',
    attendance_special_default_end: props.settings?.attendance_special_default_end ?? '18:00',
    attendance_special_default_tolerance: props.settings?.attendance_special_default_tolerance ?? 15,
    attendance_special_deadline_time: props.settings?.attendance_special_deadline_time ?? '23:30',
    attendance_special_without_radius: props.settings?.attendance_special_without_radius ?? true,
    attendance_special_late_deduction: props.settings?.attendance_special_late_deduction ?? 10000,
    attendance_special_missing_out_deduction: props.settings?.attendance_special_missing_out_deduction ?? 10000,
    attendance_special_alpha_deduction: props.settings?.attendance_special_alpha_deduction ?? 35000,

    // Tugas Luar (Field Duties / Teknisi)
    attendance_field_default_mode: props.settings?.attendance_field_default_mode ?? 'replace_both',
    attendance_field_require_photo: props.settings?.attendance_field_require_photo ?? true,
    attendance_field_require_gps: props.settings?.attendance_field_require_gps ?? true,
    attendance_field_auto_sync: props.settings?.attendance_field_auto_sync ?? true,
    attendance_field_deadline_time: props.settings?.attendance_field_deadline_time ?? '22:00',

    // Potongan Absensi Reguler (Staf)
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

    // Reward Cabang & Global
    attendance_reward_enabled: props.settings?.attendance_reward_enabled ?? true,
    attendance_reward_branch_enabled: props.settings?.attendance_reward_branch_enabled ?? true,
    attendance_reward_global_enabled: props.settings?.attendance_reward_global_enabled ?? true,
    attendance_reward_branch_1: props.settings?.attendance_reward_branch_1 ?? 200000,
    attendance_reward_branch_2: props.settings?.attendance_reward_branch_2 ?? 150000,
    attendance_reward_branch_3: props.settings?.attendance_reward_branch_3 ?? 100000,
    attendance_reward_global_1: props.settings?.attendance_reward_global_1 ?? 200000,
    attendance_reward_global_2: props.settings?.attendance_reward_global_2 ?? 150000,
    attendance_reward_global_3: props.settings?.attendance_reward_global_3 ?? 100000,
    attendance_reward_note: props.settings?.attendance_reward_note ?? 'Reward diberikan setiap akhir bulan bersama gaji',

    // Reward Maintenance
    attendance_reward_maintenance_enabled: props.settings?.attendance_reward_maintenance_enabled ?? true,
    attendance_reward_maint_1: props.settings?.attendance_reward_maint_1 ?? 200000,
    attendance_reward_maint_2: props.settings?.attendance_reward_maint_2 ?? 150000,
    attendance_reward_maint_3: props.settings?.attendance_reward_maint_3 ?? 100000,

    // Reward Tim Khusus
    attendance_reward_special_team_enabled: props.settings?.attendance_reward_special_team_enabled ?? true,
    attendance_reward_special_1: props.settings?.attendance_reward_special_1 ?? 200000,
    attendance_reward_special_2: props.settings?.attendance_reward_special_2 ?? 150000,
    attendance_reward_special_3: props.settings?.attendance_reward_special_3 ?? 100000,

    // Auto Generate Reward
    attendance_reward_auto_generate_enabled: props.settings?.attendance_reward_auto_generate_enabled ?? true,
    attendance_reward_auto_date: props.settings?.attendance_reward_auto_date ?? 1,
    attendance_reward_auto_hour: props.settings?.attendance_reward_auto_hour ?? 0,
    attendance_reward_auto_minute: props.settings?.attendance_reward_auto_minute ?? 6,
});

const submit = () => {
    form.post(route('settings.attendance.update'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: 'Pengaturan absensi & reward terbaik berhasil diperbarui.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors || {})[0] || 'Gagal menyimpan pengaturan absensi. Silakan periksa kembali data Anda.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstError,
                confirmButtonColor: '#2563eb'
            });
        }
    });
};
</script>
