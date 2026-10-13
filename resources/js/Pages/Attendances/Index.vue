<template>
    <AppLayout title="Presensi & Absensi Karyawan">
        <div class="space-y-6 max-w-7xl mx-auto pb-12">
            <!-- 1. HEADER SECTION & REALTIME HUD -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white/80 backdrop-blur-md rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg text-[11px] font-bold tracking-wider uppercase border border-blue-100 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Smart Attendance
                        </span>
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs font-semibold text-slate-600">{{ todayDateStr }}</span>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900 mt-1">Presensi & Kehadiran Staf</h1>
                    <p class="text-xs text-slate-500 font-medium">Verifikasi presensi berbasis AI Face Guide, Geofencing GPS & Sinkronisasi Payroll.</p>
                </div>

                <!-- HUD Digital Clock & Switcher -->
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Neon Digital Clock -->
                    <div class="bg-slate-900 text-white px-4 py-2 rounded-xl border border-slate-800 shadow-inner flex items-center gap-3">
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest leading-none">Waktu Server</div>
                            <div class="text-[11px] text-emerald-400 font-semibold leading-tight">WIB (GMT+7)</div>
                        </div>
                        <div class="h-6 w-px bg-slate-700"></div>
                        <div class="font-mono text-xl font-bold tracking-wider text-emerald-400">
                            {{ currentTimeStr || '--:--:--' }}
                        </div>
                    </div>

                    <!-- Switcher Karyawan untuk Admin -->
                    <div v-if="allEmployees && allEmployees.length > 1" class="relative">
                        <select
                            :value="currentEmployee?.id"
                            @change="switchEmployee($event.target.value)"
                            class="text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 pr-8 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer shadow-2xs"
                        >
                            <option v-for="emp in allEmployees" :key="emp.id" :value="emp.id">
                                Staf: {{ emp.name }} ({{ emp.position || 'Staf' }})
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2. MODERN TAB NAVIGATOR -->
            <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3 overflow-x-auto">
                <button
                    type="button"
                    @click="activeTab = 'self'"
                    :class="[
                        activeTab === 'self'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0'
                    ]"
                >
                    <span>📸</span>
                    <span>Presensi Saya</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'monitoring'"
                    :class="[
                        activeTab === 'monitoring'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0'
                    ]"
                >
                    <span>⚡</span>
                    <span>Live Monitoring Hari Ini</span>
                    <span v-if="monitoring?.presentCount > 0" class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                        {{ monitoring.presentCount }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'recap'"
                    :class="[
                        activeTab === 'recap'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0'
                    ]"
                >
                    <span>📊</span>
                    <span>Rekap & Sinkronisasi Gaji</span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'settings'"
                    :class="[
                        activeTab === 'settings'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0'
                    ]"
                >
                    <span>🗺️</span>
                    <span>Pengaturan Geofence Area</span>
                </button>

                <Link
                    href="/settings/attendance"
                    class="bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200/80 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 shrink-0 ml-auto"
                >
                    <span>⚙️</span>
                    <span>Aturan Potongan & Cron</span>
                </Link>
            </div>

            <!-- ============================================================== -->
            <!-- TAB 1: PRESENSI SAYA (SMART ATTENDANCE PORTAL)                -->
            <!-- ============================================================== -->
            <div v-if="activeTab === 'self'" class="space-y-5">
                <!-- 1. BANNER STATUS SHIFT DENGAN SMART SELECTOR -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-2xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div
                                :class="[
                                    shiftToday ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-amber-50 text-amber-600 border-amber-200',
                                    'w-11 h-11 rounded-2xl border flex items-center justify-center shrink-0 shadow-2xs text-lg'
                                ]"
                            >
                                <span v-if="shiftToday">⏱️</span>
                                <span v-else>⚠️</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-900">
                                        <template v-if="shiftToday">
                                            Shift: {{ shiftToday.name }} ({{ shiftToday.formatted_time || shiftToday.start_time + ' - ' + shiftToday.end_time }})
                                        </template>
                                        <template v-else-if="todaySchedule && todaySchedule.status === 'off'">
                                            Hari Libur Terjadwal (OFF)
                                        </template>
                                        <template v-else>
                                            Belum Ada Shift Terjadwal Hari Ini
                                        </template>
                                    </h3>
                                    <span v-if="shiftToday" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ todayDateStr }} • 
                                    <span v-if="shiftToday">Jadwal kerja resmi Anda hari ini</span>
                                    <span v-else>Sistem siap: Anda dapat memilih shift di bawah untuk langsung presensi masuk.</span>
                                </p>
                            </div>
                        </div>

                        <!-- Shift Quick Selector jika belum ada shift hari ini atau ingin ubah -->
                        <div class="flex items-center gap-2">
                            <div class="text-xs font-semibold text-slate-500 hidden sm:block">Pilih Shift:</div>
                            <select
                                v-model="selectedShiftId"
                                class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 cursor-pointer focus:ring-2 focus:ring-blue-500/20"
                            >
                                <option :value="null">-- Gunakan Shift Standar --</option>
                                <option v-for="s in allShifts" :key="s.id" :value="s.id">
                                    {{ s.name }} ({{ s.start_time }} - {{ s.end_time }})
                                </option>
                            </select>
                            <button
                                v-if="selectedShiftId && selectedShiftId !== shiftToday?.id"
                                type="button"
                                @click="applyQuickShift"
                                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
                            >
                                Terapkan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. CARD UTAMA PRESENSI (2 KOLOM: FORM & SMART CAMERA/MAP) -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        <!-- KOLOM KIRI: FORM & KONTROL (5 COLS) -->
                        <div class="lg:col-span-5 space-y-5">
                            <!-- Profil Karyawan Aktif -->
                            <div class="flex items-center gap-3.5 pb-4 border-b border-slate-100">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-extrabold text-xl flex items-center justify-center shrink-0 shadow-md shadow-blue-500/20">
                                    {{ (currentEmployee?.name || 'K').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="text-base font-extrabold text-slate-900 tracking-tight uppercase">
                                        {{ currentEmployee?.name || 'Karyawan' }}
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-400 mt-0.5">
                                        <span class="font-semibold text-slate-700">{{ currentEmployee?.position || 'Staf' }}</span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-slate-500">{{ currentEmployee?.area?.name || currentEmployee?.branch || 'Kantor Pusat' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Jam Masuk & Pulang Badges -->
                            <div class="grid grid-cols-2 gap-3">
                                <!-- Badge Masuk -->
                                <div
                                    :class="[
                                        todayAttendance?.clock_in
                                            ? 'bg-emerald-50/80 border-emerald-200 text-emerald-800'
                                            : 'bg-slate-50 border-slate-200 text-slate-600',
                                        'p-3 rounded-2xl border shadow-2xs'
                                    ]"
                                >
                                    <div class="text-[10px] font-bold uppercase tracking-wider opacity-70">Absen Masuk</div>
                                    <div class="text-base font-extrabold mt-0.5">
                                        {{ todayAttendance?.clock_in || 'Belum Masuk' }}
                                    </div>
                                    <div v-if="todayAttendance?.status === 'late'" class="mt-1">
                                        <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded-md text-[10px] font-bold">
                                            Telat {{ todayAttendance.late_minutes }}m
                                        </span>
                                    </div>
                                </div>

                                <!-- Badge Pulang -->
                                <div
                                    :class="[
                                        todayAttendance?.clock_out
                                            ? 'bg-blue-50/80 border-blue-200 text-blue-800'
                                            : 'bg-slate-50 border-slate-200 text-slate-600',
                                        'p-3 rounded-2xl border shadow-2xs'
                                    ]"
                                >
                                    <div class="text-[10px] font-bold uppercase tracking-wider opacity-70">Absen Pulang</div>
                                    <div class="text-base font-extrabold mt-0.5">
                                        {{ todayAttendance?.clock_out || 'Belum Pulang' }}
                                    </div>
                                    <div v-if="todayAttendance?.clock_out" class="mt-1 text-[10px] text-blue-600 font-semibold">
                                        Selesai Bertugas
                                    </div>
                                </div>
                            </div>

                            <!-- Pilihan Tipe Kehadiran (Smart Modes) -->
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700">Tipe Kehadiran Hari Ini:</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="attendanceType = 'wfo'"
                                        :class="[
                                            attendanceType === 'wfo'
                                                ? 'bg-blue-50 border-blue-500 text-blue-700 font-bold ring-2 ring-blue-500/20'
                                                : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                            'p-2.5 rounded-xl border text-left text-xs transition flex items-center gap-2'
                                        ]"
                                    >
                                        <span class="text-base">🏢</span>
                                        <div>
                                            <div class="font-bold">Di Kantor (WFO)</div>
                                            <div class="text-[10px] opacity-75">Validasi Radius Kantor</div>
                                        </div>
                                    </button>

                                    <button
                                        type="button"
                                        @click="attendanceType = 'dinas_luar'"
                                        :class="[
                                            attendanceType === 'dinas_luar'
                                                ? 'bg-indigo-50 border-indigo-500 text-indigo-700 font-bold ring-2 ring-indigo-500/20'
                                                : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                            'p-2.5 rounded-xl border text-left text-xs transition flex items-center gap-2'
                                        ]"
                                    >
                                        <span class="text-base">🚗</span>
                                        <div>
                                            <div class="font-bold">Dinas Lapangan</div>
                                            <div class="text-[10px] opacity-75">Bypass Radius Kantor</div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Input Keterangan Penugasan jika Dinas Lapangan -->
                            <div v-if="attendanceType === 'dinas_luar'" class="space-y-1">
                                <label class="text-xs font-bold text-indigo-700">Lokasi / Tugas Lapangan:</label>
                                <input
                                    type="text"
                                    v-model="dutyLocation"
                                    placeholder="Contoh: Perbaikan ODP-01 / PSB Pelanggan / Maintenance"
                                    class="w-full text-xs rounded-xl border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500/20"
                                />
                            </div>

                            <!-- Status Lokasi & GPS Engine -->
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full" :class="userCoordinates ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                                        <span class="text-xs font-bold text-slate-800">
                                            {{ userCoordinates ? 'GPS Terhubung' : 'Mencari Sinyal GPS...' }}
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="initGeolocation"
                                        class="text-[11px] font-bold text-blue-600 hover:underline flex items-center gap-1"
                                    >
                                        🔄 Refresh GPS
                                    </button>
                                </div>

                                <div v-if="userCoordinates" class="text-xs text-slate-600 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span>Akurasi Sinyal:</span>
                                        <span class="font-bold">±{{ Math.round(gpsAccuracy) }} meter</span>
                                    </div>
                                    <div v-if="attendanceType === 'wfo'" class="flex items-center justify-between">
                                        <span>Jarak ke Kantor Cabang:</span>
                                        <span class="font-bold" :class="isWithinRadius ? 'text-emerald-600' : 'text-amber-600'">
                                            {{ formatDistance(distanceToOffice) }}
                                            ({{ isWithinRadius ? 'Dalam Radius' : 'Luar Radius' }})
                                        </span>
                                    </div>
                                    <div v-else class="text-indigo-600 font-semibold text-[11px]">
                                        ✓ Mode Dinas Luar: Radius kantor tidak diwajibkan.
                                    </div>
                                </div>

                                <!-- Opsi Toleransi Desktop / Bypass GPS jika Akurasi Kasar di PC -->
                                <div v-if="gpsAccuracy > 1000 || !isWithinRadius" class="pt-2 border-t border-slate-200">
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="bypassRadiusMode"
                                            class="rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                                        />
                                        <span class="font-medium">
                                            Aktifkan toleransi lokasi (Mode PC / Lapangan)
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Catatan Presensi Opsional -->
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-700">Catatan Presensi (Opsional):</label>
                                <input
                                    type="text"
                                    v-model="attendanceNotes"
                                    placeholder="Tulis catatan jika ada..."
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                                />
                            </div>

                            <!-- TOMBOL UTAMA ABSENSI -->
                            <div class="pt-2">
                                <!-- State: Selesai Hari Ini -->
                                <div v-if="todayAttendance?.clock_in && todayAttendance?.clock_out" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-center">
                                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center mx-auto mb-2 shadow-xs font-bold text-lg">
                                        ✓
                                    </div>
                                    <div class="text-sm font-bold text-emerald-800">Presensi Hari Ini Selesai</div>
                                    <div class="text-xs text-emerald-600 mt-0.5">Terima kasih atas dedikasi dan kerja keras Anda hari ini!</div>
                                </div>

                                <!-- State: Belum Masuk -->
                                <div v-else-if="!todayAttendance?.clock_in" class="space-y-2">
                                    <button
                                        type="button"
                                        @click="submitClockIn"
                                        :disabled="!canSubmitClockIn || isSubmitting"
                                        :class="[
                                            canSubmitClockIn
                                                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg shadow-blue-500/25 active:scale-95'
                                                : 'bg-slate-300 text-slate-500 cursor-not-allowed',
                                            'w-full py-4 px-5 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 transition-all'
                                        ]"
                                    >
                                        <svg v-if="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span v-if="isSubmitting">Memproses Presensi...</span>
                                        <span v-else-if="!capturedPhoto">Ambil Foto Selfie Dulu</span>
                                        <span v-else>📷 Absen Masuk Sekarang</span>
                                    </button>
                                </div>

                                <!-- State: Mau Pulang -->
                                <div v-else class="space-y-2">
                                    <button
                                        type="button"
                                        @click="submitClockOut"
                                        :disabled="!canSubmitClockOut || isSubmitting"
                                        :class="[
                                            canSubmitClockOut
                                                ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/25 active:scale-95'
                                                : 'bg-slate-300 text-slate-500 cursor-not-allowed',
                                            'w-full py-4 px-5 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 transition-all'
                                        ]"
                                    >
                                        <svg v-if="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        <span v-if="isSubmitting">Memproses Presensi Pulang...</span>
                                        <span v-else-if="!capturedPhoto">Ambil Foto Selfie Dulu</span>
                                        <span v-else>📷 Absen Pulang Sekarang</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: SMART CAMERA FEED & LEAFLET MAP (7 COLS) -->
                        <div class="lg:col-span-7 space-y-4">
                            <!-- Toggle View: Kamera Selfie vs Peta Interaktif -->
                            <div class="flex items-center justify-between bg-slate-100 p-1 rounded-xl">
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        @click="mediaView = 'camera'"
                                        :class="[
                                            mediaView === 'camera' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 font-semibold',
                                            'px-3.5 py-1.5 rounded-lg text-xs transition'
                                        ]"
                                    >
                                        📷 Kamera Live & AI Face Guide
                                    </button>
                                    <button
                                        type="button"
                                        @click="toggleMapView"
                                        :class="[
                                            mediaView === 'map' ? 'bg-white text-slate-900 shadow-2xs font-bold' : 'text-slate-600 font-semibold',
                                            'px-3.5 py-1.5 rounded-lg text-xs transition'
                                        ]"
                                    >
                                        🗺️ Peta Lokasi & Geofence
                                    </button>
                                </div>

                                <div v-if="mediaView === 'camera' && isCameraOpen" class="flex items-center gap-2 pr-2">
                                    <button
                                        type="button"
                                        @click="switchCameraFacing"
                                        class="text-[11px] font-bold text-slate-600 hover:text-slate-900 flex items-center gap-1"
                                    >
                                        🔄 Ganti Kamera
                                    </button>
                                </div>
                            </div>

                            <!-- VIEW 1: KAMERA FEED DENGAN FACE SCANNER OVERLAY -->
                            <div v-show="mediaView === 'camera'" class="space-y-3">
                                <div class="relative w-full aspect-4/3 bg-slate-950 rounded-3xl overflow-hidden border border-slate-300 shadow-inner flex items-center justify-center">
                                    <!-- Video Live Feed -->
                                    <video
                                        v-show="isCameraOpen && !capturedPhoto"
                                        ref="videoRef"
                                        autoplay
                                        playsinline
                                        class="w-full h-full object-cover transform -scale-x-100"
                                    ></video>

                                    <!-- AI Face Guide Overlay (Oval Scan Frame) -->
                                    <div
                                        v-if="isCameraOpen && !capturedPhoto"
                                        class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center"
                                    >
                                        <div class="relative w-48 h-64 border-2 border-emerald-400/80 rounded-full shadow-[0_0_20px_rgba(52,211,153,0.3)] flex items-center justify-center overflow-hidden">
                                            <!-- Vertical Animated Scanner Line -->
                                            <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent animate-bounce"></div>
                                        </div>
                                        <div class="mt-3 px-3 py-1 bg-black/60 backdrop-blur-md rounded-full text-[11px] font-bold text-white tracking-wide border border-white/20">
                                            Posisikan Wajah di Dalam Lingkaran
                                        </div>
                                    </div>

                                    <!-- Captured Snapshot Image -->
                                    <img
                                        v-if="capturedPhoto"
                                        :src="capturedPhoto"
                                        alt="Selfie"
                                        class="w-full h-full object-cover"
                                    />

                                    <!-- Placeholder Kamera Mati -->
                                    <div
                                        v-if="!isCameraOpen && !capturedPhoto"
                                        class="flex flex-col items-center justify-center text-slate-400 p-8 text-center space-y-4"
                                    >
                                        <div class="w-16 h-16 rounded-3xl bg-slate-900 border border-slate-800 flex items-center justify-center text-blue-400 text-2xl shadow-md">
                                            📷
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-200">Kamera Belum Aktif</div>
                                            <div class="text-xs text-slate-400 mt-1 max-w-xs">
                                                Klik tombol di bawah untuk menyalakan kamera selfie dan merekam kehadiran Anda.
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            @click="startCamera"
                                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-blue-500/20"
                                        >
                                            Aktifkan Kamera Sekarang
                                        </button>
                                    </div>
                                </div>

                                <!-- Action Buttons Kamera -->
                                <div class="flex items-center justify-center gap-3">
                                    <button
                                        v-if="!isCameraOpen && !capturedPhoto"
                                        type="button"
                                        @click="startCamera"
                                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition"
                                    >
                                        Nyalakan Kamera
                                    </button>

                                    <button
                                        v-if="isCameraOpen && !capturedPhoto"
                                        type="button"
                                        @click="captureSnapshot"
                                        class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs font-black transition flex items-center gap-2 shadow-lg shadow-emerald-500/25 active:scale-95"
                                    >
                                        <span>📸</span>
                                        <span>Ambil Foto Selfie</span>
                                    </button>

                                    <button
                                        v-if="capturedPhoto"
                                        type="button"
                                        @click="retakePhoto"
                                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition"
                                    >
                                        Ambil Ulang Foto
                                    </button>

                                    <button
                                        v-if="isCameraOpen"
                                        type="button"
                                        @click="stopCamera"
                                        class="px-4 py-2 text-slate-400 hover:text-slate-600 text-xs font-medium"
                                    >
                                        Tutup Kamera
                                    </button>
                                </div>
                            </div>

                            <!-- VIEW 2: INTERACTIVE LEAFLET GEOFENCE MAP -->
                            <div v-show="mediaView === 'map'" class="space-y-2">
                                <div id="attendance-map" class="w-full aspect-4/3 rounded-3xl overflow-hidden border border-slate-300 shadow-inner z-0"></div>
                                <div class="flex items-center justify-between text-[11px] text-slate-500 px-1">
                                    <span>🔵 Titik Kantor • 🟢 Geofence Radius • 🟡 Posisi Anda</span>
                                    <button type="button" @click="refreshMap" class="text-blue-600 font-bold hover:underline">
                                        Pusatkan Peta
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. JADWAL SHIFT MINGGU INI (7-HARI MATRIX) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📅</span>
                            <h3 class="text-sm font-bold text-slate-900">Jadwal Shift Minggu Ini</h3>
                        </div>
                        <span class="text-xs text-slate-400">Senin s/d Minggu</span>
                    </div>

                    <div class="grid grid-cols-7 gap-2 overflow-x-auto">
                        <div
                            v-for="d in weeklySchedules"
                            :key="d.date"
                            :class="[
                                d.is_today
                                    ? 'bg-blue-50/80 border-blue-400 ring-2 ring-blue-500/20'
                                    : 'bg-slate-50 border-slate-200',
                                'p-3 rounded-2xl border text-center flex flex-col justify-between min-w-[70px]'
                            ]"
                        >
                            <div>
                                <div class="text-[11px] font-bold uppercase" :class="d.is_today ? 'text-blue-700' : 'text-slate-500'">
                                    {{ d.day_name }}
                                </div>
                                <div class="text-base font-extrabold mt-0.5" :class="d.is_today ? 'text-blue-900' : 'text-slate-800'">
                                    {{ d.day_number }}
                                </div>
                            </div>

                            <div class="mt-2">
                                <span
                                    :class="[
                                        d.shift_label === 'LIBUR'
                                            ? 'bg-rose-50 text-rose-700 border-rose-200'
                                            : 'bg-white text-slate-700 border-slate-200',
                                        'px-2 py-0.5 rounded-lg text-[10px] font-bold border inline-block max-w-full truncate'
                                    ]"
                                >
                                    {{ d.shift_label }}
                                </span>
                                <div v-if="d.has_attended" class="mt-1 text-[10px] font-bold text-emerald-600">
                                    ✓ Hadir
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- TAB 2: LIVE MONITORING HARI INI (ADMIN / HR DASHBOARD)        -->
            <!-- ============================================================== -->
            <div v-if="activeTab === 'monitoring'" class="space-y-5">
                <!-- KPI CARDS -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Karyawan</div>
                        <div class="text-2xl font-black text-slate-900 mt-1">{{ monitoring.totalEmployees }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Pegawai terdaftar</div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
                        <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Hadir Hari Ini</div>
                        <div class="text-2xl font-black text-emerald-700 mt-1">{{ monitoring.presentCount }}</div>
                        <div class="text-[11px] text-emerald-600 mt-0.5">{{ Math.round((monitoring.presentCount / (monitoring.totalEmployees || 1)) * 100) }}% kehadiran</div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
                        <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Tepat Waktu</div>
                        <div class="text-2xl font-black text-blue-700 mt-1">{{ monitoring.onTimeCount }}</div>
                        <div class="text-[11px] text-blue-600 mt-0.5">Sesuai jam shift</div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
                        <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Terlambat</div>
                        <div class="text-2xl font-black text-amber-700 mt-1">{{ monitoring.lateCount }}</div>
                        <div class="text-[11px] text-amber-600 mt-0.5">Melebihi toleransi</div>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
                        <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Dinas Lapangan</div>
                        <div class="text-2xl font-black text-indigo-700 mt-1">{{ monitoring.fieldDutyCount }}</div>
                        <div class="text-[11px] text-indigo-600 mt-0.5">Teknisi / On-Site</div>
                    </div>
                </div>

                <!-- DAFTAR MONITORING TABEL REAL-TIME -->
                <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs space-y-4 p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">⚡</span>
                            <h3 class="text-sm font-bold text-slate-900">Daftar Kehadiran Hari Ini ({{ todayDateStr }})</h3>
                        </div>

                        <!-- Filter Area -->
                        <div class="flex items-center gap-2">
                            <select
                                v-model="filterAreaMonitoring"
                                @change="applyMonitoringFilter"
                                class="text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 cursor-pointer"
                            >
                                <option value="">🏢 Semua Area/Wilayah</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                                    <th class="px-4 py-3">Karyawan</th>
                                    <th class="px-4 py-3">Shift & Tipe</th>
                                    <th class="px-4 py-3">Foto Masuk</th>
                                    <th class="px-4 py-3">Jam Masuk</th>
                                    <th class="px-4 py-3">Foto Pulang</th>
                                    <th class="px-4 py-3">Jam Pulang</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="att in monitoring.attendances" :key="att.id" class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-800">{{ att.employee?.name }}</div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ att.employee?.position || 'Staf' }} • {{ att.employee?.area?.name || 'Area' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-700">{{ att.shift?.name || 'Reguler' }}</div>
                                        <span
                                            :class="[
                                                att.attendance_type === 'dinas_luar'
                                                    ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                                                    : 'bg-slate-100 text-slate-600 border-slate-200',
                                                'px-1.5 py-0.5 rounded text-[10px] font-bold border inline-block mt-0.5'
                                            ]"
                                        >
                                            {{ att.attendance_type === 'dinas_luar' ? '🚗 Dinas Luar' : '🏢 Kantor (WFO)' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <img
                                            v-if="att.photo_in"
                                            :src="'/storage/' + att.photo_in"
                                            alt="In"
                                            @click="viewPhotoModal = '/storage/' + att.photo_in"
                                            class="w-10 h-10 rounded-xl object-cover border border-slate-200 cursor-pointer hover:opacity-80 transition shadow-2xs"
                                        />
                                        <span v-else class="text-slate-300">-</span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">
                                        {{ att.clock_in || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <img
                                            v-if="att.photo_out"
                                            :src="'/storage/' + att.photo_out"
                                            alt="Out"
                                            @click="viewPhotoModal = '/storage/' + att.photo_out"
                                            class="w-10 h-10 rounded-xl object-cover border border-slate-200 cursor-pointer hover:opacity-80 transition shadow-2xs"
                                        />
                                        <span v-else class="text-slate-300">-</span>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">
                                        {{ att.clock_out || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="att.status === 'late'"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            Terlambat ({{ att.late_minutes }}m)
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            Tepat Waktu
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="monitoring.attendances.length === 0">
                                    <td colspan="7" class="py-10 text-center text-slate-400 italic">
                                        Belum ada staf yang melakukan presensi hari ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- TAB 3: REKAPITULASI & SINKRONISASI GAJI (PAYROLL)              -->
            <!-- ============================================================== -->
            <div v-if="activeTab === 'recap'" class="space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-base">📊</span>
                                <h3 class="text-sm font-bold text-slate-900">Rekapitulasi Kehadiran & Sinkronisasi Gaji</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Periode: <span class="font-bold text-slate-800">{{ monthlyRecap.monthLabel }}</span>
                            </p>
                        </div>

                        <!-- Kontrol Periode Bulan & Tombol Sinkronisasi -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <input
                                type="month"
                                v-model="selectedRecapMonth"
                                @change="changeRecapMonth"
                                class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 cursor-pointer"
                            />

                            <button
                                type="button"
                                @click="openSyncPayrollModal = true"
                                class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm shadow-emerald-500/20"
                            >
                                <span>⚡</span>
                                <span>Sinkronkan ke Modul Gaji</span>
                            </button>
                        </div>
                    </div>

                    <!-- TABEL REKAP BULANAN -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[11px] border-b border-slate-200">
                                    <th class="px-4 py-3">Nama Karyawan</th>
                                    <th class="px-4 py-3">Jabatan & Area</th>
                                    <th class="px-4 py-3 text-center">Hadir</th>
                                    <th class="px-4 py-3 text-center">Dinas Luar</th>
                                    <th class="px-4 py-3 text-center">Terlambat (Kali)</th>
                                    <th class="px-4 py-3 text-center">Total Menit Telat</th>
                                    <th class="px-4 py-3 text-center">Status Payroll</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="rec in monthlyRecap.records" :key="rec.employee_id" class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-bold text-slate-800">
                                        {{ rec.name }}
                                        <div class="text-[10px] text-slate-400 font-normal">NIK: {{ rec.iak_number || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ rec.position || 'Staf' }} • {{ rec.area_name }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-emerald-700">
                                        {{ rec.total_present }} Hari
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-indigo-700">
                                        {{ rec.total_field_duty }}x
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold" :class="rec.total_late > 0 ? 'text-amber-700' : 'text-slate-400'">
                                        {{ rec.total_late }}x
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold" :class="rec.total_late_minutes > 0 ? 'text-rose-700' : 'text-slate-400'">
                                        {{ rec.total_late_minutes }} mnt
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            v-if="!rec.has_user"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-500 border"
                                        >
                                            Belum Terhubung User
                                        </span>
                                        <span
                                            v-else-if="rec.is_all_synced"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            ✓ Tersinkron
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                                        >
                                            Perlu Sinkron
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- TAB 4: PENGATURAN GEOFENCE KANTOR CABANG                       -->
            <!-- ============================================================== -->
            <div v-if="activeTab === 'settings'" class="space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-base">🗺️</span>
                                <h3 class="text-sm font-bold text-slate-900">Pengaturan Koordinat & Radius Geofence Area</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tentukan titik koordinat kantor cabang dan radius toleransi presensi presisi.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="area in areas"
                            :key="area.id"
                            class="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-3"
                        >
                            <div class="flex items-center justify-between">
                                <div class="font-bold text-slate-800 text-sm">🏢 {{ area.name }}</div>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800">
                                    Radius: {{ area.radius_meters || 200 }}m
                                </span>
                            </div>

                            <div class="text-xs text-slate-500 space-y-1">
                                <div><span class="font-semibold text-slate-700">Alamat:</span> {{ area.address || '-' }}</div>
                                <div><span class="font-semibold text-slate-700">Latitude:</span> {{ area.latitude || '-' }}</div>
                                <div><span class="font-semibold text-slate-700">Longitude:</span> {{ area.longitude || '-' }}</div>
                            </div>

                            <button
                                type="button"
                                @click="openEditArea(area)"
                                class="w-full py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs"
                            >
                                ✏️ Atur Titik Koordinat & Radius
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL SINKRONISASI PAYROLL -->
            <div v-if="openSyncPayrollModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="openSyncPayrollModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">⚡ Sinkronisasi ke Modul Gaji</h3>
                            <p class="text-xs text-slate-500">Periode: {{ monthlyRecap.monthLabel }}</p>
                        </div>
                        <button @click="openSyncPayrollModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-800">
                            Sistem akan menghitung keterlambatan setiap karyawan dan membuat potongan otomatis (Deduction) di modul Penggajian (Payroll).
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-slate-700">Denda Keterlambatan per Menit (Rp):</label>
                            <input
                                type="number"
                                v-model="syncForm.penalty_per_minute"
                                class="w-full text-xs rounded-xl border-slate-200 focus:ring-blue-500"
                                placeholder="1000"
                            />
                            <p class="text-[10px] text-slate-400">Default: Rp 1.000 / menit keterlambatan</p>
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-slate-700">Denda Tetap per Kejadian Telat (Rp):</label>
                            <input
                                type="number"
                                v-model="syncForm.fixed_penalty_per_late"
                                class="w-full text-xs rounded-xl border-slate-200 focus:ring-blue-500"
                                placeholder="0"
                            />
                            <p class="text-[10px] text-slate-400">Opsional biaya tetap per kedatangan telat</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="openSyncPayrollModal = false"
                            class="px-4 py-2 text-slate-500 text-xs font-bold hover:bg-slate-100 rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="executeSyncPayroll"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
                        >
                            Lakukan Sinkronisasi
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL EDIT AREA GEOFENCE -->
            <div v-if="editAreaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="editAreaModal = null"></div>
                <div class="relative bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Atur Geofence Area: {{ editAreaModal.name }}</h3>
                            <p class="text-xs text-slate-500">Koordinat titik kantor cabang & radius presensi</p>
                        </div>
                        <button @click="editAreaModal = null" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <button
                            type="button"
                            @click="useCurrentLocationForArea"
                            class="w-full py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5"
                        >
                            📍 Gunakan Lokasi GPS Saya Saat Ini
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Latitude:</label>
                                <input
                                    type="number"
                                    step="any"
                                    v-model="editAreaModal.latitude"
                                    class="w-full text-xs rounded-xl border-slate-200"
                                />
                            </div>
                            <div class="space-y-1">
                                <label class="font-bold text-slate-700">Longitude:</label>
                                <input
                                    type="number"
                                    step="any"
                                    v-model="editAreaModal.longitude"
                                    class="w-full text-xs rounded-xl border-slate-200"
                                />
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="font-bold text-slate-700">Radius Toleransi (Meter):</label>
                            <input
                                type="number"
                                v-model="editAreaModal.radius_meters"
                                class="w-full text-xs rounded-xl border-slate-200"
                                placeholder="200"
                            />
                            <p class="text-[10px] text-slate-400">Rekomendasi: 100 - 300 meter</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="editAreaModal = null"
                            class="px-4 py-2 text-slate-500 text-xs font-bold hover:bg-slate-100 rounded-xl"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="saveAreaLocation"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-xs"
                        >
                            Simpan Pengaturan
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL PREVIEW FOTO SELFIE -->
            <div v-if="viewPhotoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/60 backdrop-blur-xs" @click="viewPhotoModal = null"></div>
                <div class="relative bg-white rounded-3xl max-w-sm w-full p-4 shadow-2xl space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="font-bold text-slate-800 text-xs">Foto Verifikasi Selfie</div>
                        <button @click="viewPhotoModal = null" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                    </div>
                    <img :src="viewPhotoModal" class="w-full aspect-4/3 object-cover rounded-2xl border" alt="Verifikasi">
                </div>
            </div>

            <!-- Canvas tersembunyi untuk Watermark & Snapshot Processing -->
            <canvas ref="canvasRef" class="hidden"></canvas>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    todayDateStr: String,
    todayIso: String,
    currentEmployee: Object,
    allEmployees: Array,
    allShifts: Array,
    todaySchedule: Object,
    shiftToday: Object,
    todayAttendance: Object,
    weeklySchedules: Array,
    officeLocation: Object,
    monitoring: Object,
    monthlyRecap: Object,
    areas: Array,
    positions: Array,
    filters: Object,
});

const activeTab = ref(props.filters?.tab || 'self');
const mediaView = ref('camera');
const viewPhotoModal = ref(null);
const openSyncPayrollModal = ref(false);
const editAreaModal = ref(null);

const filterAreaMonitoring = ref(props.filters?.area_id || '');
const selectedRecapMonth = ref(props.monthlyRecap?.month || '');

// Form Presensi State
const selectedShiftId = ref(props.shiftToday?.id || null);
const attendanceType = ref('wfo'); // 'wfo', 'dinas_luar', 'wfh', 'lembur'
const dutyLocation = ref('');
const attendanceNotes = ref('');
const bypassRadiusMode = ref(false);

// Realtime Clock HUD
const currentTimeStr = ref('');
let timerInterval = null;

const updateClock = () => {
    const d = new Date();
    currentTimeStr.value = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

// Geolocation GPS State
const userCoordinates = ref(null);
const gpsAccuracy = ref(20);
const distanceToOffice = ref(null);
const isWithinRadius = ref(true);

const initGeolocation = () => {
    if (!navigator.geolocation) {
        console.warn('Geolocation tidak didukung browser ini.');
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            userCoordinates.value = {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
            };
            gpsAccuracy.value = pos.coords.accuracy || 20;
            calculateDistanceToOffice();
            if (mapInstance && userMarker) {
                userMarker.setLatLng([pos.coords.latitude, pos.coords.longitude]);
            }
        },
        (err) => {
            console.warn('Error geolocation:', err.message);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
    );
};

// Hitung Jarak Menggunakan Formula Haversine
const calculateDistanceToOffice = () => {
    if (!userCoordinates.value || !props.officeLocation?.latitude || !props.officeLocation?.longitude) {
        isWithinRadius.value = true;
        distanceToOffice.value = 15;
        return;
    }

    const lat1 = userCoordinates.value.latitude;
    const lon1 = userCoordinates.value.longitude;
    const lat2 = props.officeLocation.latitude;
    const lon2 = props.officeLocation.longitude;

    const R = 6371e3; // metres
    const φ1 = (lat1 * Math.PI) / 180;
    const φ2 = (lat2 * Math.PI) / 180;
    const Δφ = ((lat2 - lat1) * Math.PI) / 180;
    const Δλ = ((lon2 - lon1) * Math.PI) / 180;

    const a =
        Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    const dist = Math.round(R * c);
    distanceToOffice.value = dist;
    const allowedRadius = props.officeLocation.radius_meters || 200;
    isWithinRadius.value = dist <= allowedRadius;
};

const formatDistance = (meters) => {
    if (meters === null || meters === undefined) return '-';
    if (meters < 1000) return `${meters}m`;
    return `${(meters / 1000).toFixed(1)}km`;
};

// Webcam State
const isCameraOpen = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
const capturedPhoto = ref(null);
const cameraFacing = ref('user'); // 'user' or 'environment'
let mediaStream = null;

const startCamera = async () => {
    try {
        isCameraOpen.value = true;
        capturedPhoto.value = null;
        if (mediaStream) {
            mediaStream.getTracks().forEach((t) => t.stop());
        }
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: cameraFacing.value },
            audio: false,
        });
        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
        }
    } catch (e) {
        alert('Gagal mengakses kamera: ' + e.message);
        isCameraOpen.value = false;
    }
};

const switchCameraFacing = () => {
    cameraFacing.value = cameraFacing.value === 'user' ? 'environment' : 'user';
    startCamera();
};

const captureSnapshot = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    // Mirror snapshot jika kamera depan
    if (cameraFacing.value === 'user') {
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.setTransform(1, 0, 0, 1, 0, 0); // reset transform
    } else {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    }

    // Bubuhkan Watermark Otentikasi Canggih
    drawWatermark(ctx, canvas.width, canvas.height);

    capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.88);
    stopCamera();
};

const drawWatermark = (ctx, w, h) => {
    ctx.save();
    const barHeight = 44;
    ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
    ctx.fillRect(0, h - barHeight, w, barHeight);

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 12px sans-serif';
    const empName = props.currentEmployee?.name || 'Karyawan';
    const timeStr = `${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')} WIB`;
    ctx.fillText(`${empName} • ${timeStr}`, 12, h - 24);

    ctx.font = '10px sans-serif';
    ctx.fillStyle = '#38bdf8';
    const geoStr = userCoordinates.value
        ? `GPS: ${userCoordinates.value.latitude.toFixed(6)}, ${userCoordinates.value.longitude.toFixed(6)} (${attendanceType.value.toUpperCase()})`
        : `Mode: ${attendanceType.value.toUpperCase()}`;
    ctx.fillText(geoStr, 12, h - 10);
    ctx.restore();
};

const retakePhoto = () => {
    capturedPhoto.value = null;
    startCamera();
};

const stopCamera = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
    isCameraOpen.value = false;
};

// Interactive Leaflet Map Instance
let mapInstance = null;
let officeMarker = null;
let userMarker = null;
let radiusCircle = null;

const toggleMapView = async () => {
    mediaView.value = 'map';
    await nextTick();
    initMap();
};

const refreshMap = () => {
    if (mapInstance && userCoordinates.value) {
        mapInstance.setView([userCoordinates.value.latitude, userCoordinates.value.longitude], 16);
    }
};

const initMap = async () => {
    const mapContainer = document.getElementById('attendance-map');
    if (!mapContainer || mapInstance) return;

    const L = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    const defaultLat = props.officeLocation?.latitude || -6.2088;
    const defaultLng = props.officeLocation?.longitude || 106.8456;

    mapInstance = L.map('attendance-map').setView([defaultLat, defaultLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(mapInstance);

    // Marker Kantor
    if (props.officeLocation?.latitude && props.officeLocation?.longitude) {
        const officeIcon = L.divIcon({
            className: 'custom-office-pin',
            html: '<div style="background-color:#2563eb; color:white; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 2px 6px rgba(0,0,0,0.3); font-size:14px;">🏢</div>',
            iconSize: [28, 28],
            iconAnchor: [14, 14],
        });

        officeMarker = L.marker([props.officeLocation.latitude, props.officeLocation.longitude], { icon: officeIcon })
            .addTo(mapInstance)
            .bindPopup(`<b>${props.officeLocation.name}</b><br>${props.officeLocation.address || 'Kantor Cabang'}`);

        // Geofence Circle
        const radius = props.officeLocation.radius_meters || 200;
        radiusCircle = L.circle([props.officeLocation.latitude, props.officeLocation.longitude], {
            radius: radius,
            color: '#10b981',
            fillColor: '#10b981',
            fillOpacity: 0.15,
            weight: 2,
        }).addTo(mapInstance);
    }

    // Marker User
    if (userCoordinates.value) {
        const userIcon = L.divIcon({
            className: 'custom-user-pin',
            html: '<div style="background-color:#f59e0b; color:white; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid white; box-shadow:0 0 10px rgba(245,158,11,0.6); font-size:12px;">📍</div>',
            iconSize: [24, 24],
            iconAnchor: [12, 12],
        });

        userMarker = L.marker([userCoordinates.value.latitude, userCoordinates.value.longitude], { icon: userIcon })
            .addTo(mapInstance)
            .bindPopup('<b>Posisi Anda Saat Ini</b>')
            .openPopup();
    }
};

// Submitting state & validation
const isSubmitting = ref(false);

const canSubmitClockIn = computed(() => {
    if (!capturedPhoto.value) return false;
    if (props.todayAttendance?.clock_in) return false;
    return true;
});

const canSubmitClockOut = computed(() => {
    if (!capturedPhoto.value) return false;
    if (!props.todayAttendance?.clock_in || props.todayAttendance?.clock_out) return false;
    return true;
});

const submitClockIn = () => {
    if (!canSubmitClockIn.value) return;
    isSubmitting.value = true;

    router.post(route('attendances.clock-in'), {
        employee_id: props.currentEmployee.id,
        photo: capturedPhoto.value,
        shift_id: selectedShiftId.value || undefined,
        attendance_type: attendanceType.value,
        duty_location: dutyLocation.value || undefined,
        latitude: userCoordinates.value?.latitude || null,
        longitude: userCoordinates.value?.longitude || null,
        distance: distanceToOffice.value || 0,
        notes: attendanceNotes.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            capturedPhoto.value = null;
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const submitClockOut = () => {
    if (!canSubmitClockOut.value) return;
    isSubmitting.value = true;

    router.post(route('attendances.clock-out'), {
        employee_id: props.currentEmployee.id,
        photo: capturedPhoto.value,
        latitude: userCoordinates.value?.latitude || null,
        longitude: userCoordinates.value?.longitude || null,
        distance: distanceToOffice.value || 0,
        notes: attendanceNotes.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            capturedPhoto.value = null;
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const applyQuickShift = () => {
    if (!selectedShiftId.value) return;
    router.post(route('attendances.quick-assign-shift'), {
        employee_id: props.currentEmployee.id,
        shift_id: selectedShiftId.value,
    }, { preserveScroll: true });
};

const switchEmployee = (empId) => {
    router.get(route('attendances.index'), { employee_id: empId, tab: activeTab.value }, { preserveScroll: true });
};

const applyMonitoringFilter = () => {
    router.get(route('attendances.index'), { area_id: filterAreaMonitoring.value, tab: 'monitoring' }, { preserveScroll: true });
};

const changeRecapMonth = () => {
    router.get(route('attendances.index'), { month: selectedRecapMonth.value, tab: 'recap' }, { preserveScroll: true });
};

// Sinkronisasi Payroll
const syncForm = ref({
    penalty_per_minute: 1000,
    fixed_penalty_per_late: 0,
});

const executeSyncPayroll = () => {
    router.post(route('attendances.sync-payroll'), {
        month: selectedRecapMonth.value || props.monthlyRecap?.month,
        penalty_per_minute: syncForm.value.penalty_per_minute,
        fixed_penalty_per_late: syncForm.value.fixed_penalty_per_late,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            openSyncPayrollModal.value = false;
        }
    });
};

// Edit Area Modal
const openEditArea = (area) => {
    editAreaModal.value = {
        id: area.id,
        name: area.name,
        latitude: area.latitude || (userCoordinates.value?.latitude || -6.2),
        longitude: area.longitude || (userCoordinates.value?.longitude || 106.8),
        radius_meters: area.radius_meters || 200,
    };
};

const useCurrentLocationForArea = () => {
    if (userCoordinates.value && editAreaModal.value) {
        editAreaModal.value.latitude = userCoordinates.value.latitude;
        editAreaModal.value.longitude = userCoordinates.value.longitude;
    } else {
        alert('Lokasi GPS belum tersedia. Nyalakan GPS terlebih dahulu.');
    }
};

const saveAreaLocation = () => {
    if (!editAreaModal.value) return;
    router.post(route('attendances.update-location'), {
        area_id: editAreaModal.value.id,
        latitude: editAreaModal.value.latitude,
        longitude: editAreaModal.value.longitude,
        radius_meters: editAreaModal.value.radius_meters,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editAreaModal.value = null;
        }
    });
};

onMounted(() => {
    updateClock();
    timerInterval = setInterval(updateClock, 1000);
    initGeolocation();
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    stopCamera();
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
    }
});
</script>
