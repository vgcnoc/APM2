<template>
    <AppLayout title="Absensi">
        <div class="space-y-5 max-w-7xl mx-auto">
            <!-- 1. HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-800">Absensi</h1>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Absen masuk & pulang dengan foto selfie & verifikasi lokasi.</p>
                </div>

                <!-- Tab Selector & Employee Switcher -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Tab Switcher -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200/70 text-xs font-semibold">
                        <button
                            type="button"
                            @click="activeTab = 'self'"
                            :class="[
                                activeTab === 'self' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500 hover:text-slate-700',
                                'px-3 py-1.5 rounded-lg transition'
                            ]"
                        >
                            👤 Absensi Saya
                        </button>
                        <button
                            type="button"
                            @click="activeTab = 'monitoring'"
                            :class="[
                                activeTab === 'monitoring' ? 'bg-white text-slate-800 shadow-2xs' : 'text-slate-500 hover:text-slate-700',
                                'px-3 py-1.5 rounded-lg transition'
                            ]"
                        >
                            📊 Monitoring Kehadiran
                        </button>
                    </div>

                    <!-- Switcher Karyawan untuk Admin -->
                    <div v-if="allEmployees && allEmployees.length > 1" class="relative">
                        <select
                            :value="currentEmployee?.id"
                            @change="switchEmployee($event.target.value)"
                            class="text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl px-3 py-2 pr-7 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer shadow-2xs"
                        >
                            <option v-for="emp in allEmployees" :key="emp.id" :value="emp.id">
                                Staf: {{ emp.name }} ({{ emp.position || 'Staf' }})
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TAB 1: ABSENSI SAYA (PERSIS SEPERTI SCREENSHOT) -->
            <div v-if="activeTab === 'self'" class="space-y-4">
                <!-- 2. BANNER STATUS JADWAL HARI INI -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200/80 flex items-center justify-center text-slate-500 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-slate-800">
                            <template v-if="shiftToday">
                                {{ shiftToday.name }} ({{ shiftToday.formatted_time || shiftToday.start_time + ' - ' + shiftToday.end_time }})
                            </template>
                            <template v-else-if="todaySchedule && todaySchedule.status === 'off'">
                                Hari Libur (OFF)
                            </template>
                            <template v-else>
                                Belum Ada Jadwal
                            </template>
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ todayDateStr }} • 
                            <span v-if="shiftToday">Shift Aktif untuk hari ini</span>
                            <span v-else-if="todaySchedule && todaySchedule.status === 'off'">Hari ini Anda dijadwalkan libur (OFF)</span>
                            <span v-else>Roster belum diatur untuk hari ini</span>
                        </div>
                    </div>
                </div>

                <!-- 3. CARD UTAMA ABSENSI (GRID 2 KOLOM) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-sm">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                        <!-- KOLOM KIRI: PROFIL & STATUS ABSENSI -->
                        <div class="space-y-4">
                            <!-- Profil Karyawan -->
                            <div class="flex items-center gap-3.5">
                                <div class="w-11 h-11 rounded-full bg-blue-600 text-white font-bold text-lg flex items-center justify-center shrink-0 shadow-sm">
                                    {{ (currentEmployee?.name || 'K').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-slate-800 tracking-tight uppercase">
                                        {{ currentEmployee?.name || 'Karyawan' }}
                                    </h2>
                                    <div class="flex items-center gap-1.5 text-xs text-slate-400 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span>Absen Selfie & Radius</span>
                                        <span v-if="currentEmployee?.area?.name" class="text-slate-300">•</span>
                                        <span v-if="currentEmployee?.area?.name" class="font-semibold text-slate-600">{{ currentEmployee.area.name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Jam Masuk & Pulang -->
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Badge Masuk -->
                                <div
                                    :class="[
                                        todayAttendance?.clock_in
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : 'bg-slate-50 text-slate-600 border-slate-200',
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border shadow-2xs'
                                    ]"
                                >
                                    <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>{{ todayAttendance?.clock_in ? 'Masuk: ' + todayAttendance.clock_in : 'Belum Masuk' }}</span>
                                    <span v-if="todayAttendance?.status === 'late'" class="ml-1 px-1.5 py-0.2 bg-rose-100 text-rose-700 rounded-md text-[10px] font-bold">
                                        Terlambat
                                    </span>
                                </div>

                                <!-- Badge Pulang -->
                                <div
                                    :class="[
                                        todayAttendance?.clock_out
                                            ? 'bg-blue-50 text-blue-700 border-blue-200'
                                            : 'bg-slate-50 text-slate-600 border-slate-200',
                                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold border shadow-2xs'
                                    ]"
                                >
                                    <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span>{{ todayAttendance?.clock_out ? 'Pulang: ' + todayAttendance.clock_out : 'Belum Pulang' }}</span>
                                </div>
                            </div>

                            <!-- Alert Shift Status -->
                            <div v-if="!shiftToday" class="p-3 bg-amber-50/80 border border-amber-200/80 rounded-xl flex items-center gap-2.5 text-xs text-amber-800">
                                <span class="text-amber-600 text-sm">⚠️</span>
                                <span>Tidak ada jadwal shift hari ini</span>
                            </div>

                            <!-- Alert GPS / Lokasi -->
                            <div v-if="!userCoordinates" class="p-3 bg-rose-50/80 border border-rose-200/80 rounded-xl flex items-center gap-2.5 text-xs text-rose-700">
                                <span class="text-rose-600 text-sm">⛔</span>
                                <span>Lokasi GPS belum terdeteksi. Aktifkan GPS dan pastikan izin lokasi diizinkan di browser sebelum absen.</span>
                            </div>
                            <div v-else-if="gpsAccuracy > 1000" class="p-3 bg-amber-50/80 border border-amber-200/80 rounded-xl flex items-center gap-2.5 text-xs text-amber-800">
                                <span class="text-amber-600 text-sm">⚠️</span>
                                <span>GPS kurang akurat (±{{ Math.round(gpsAccuracy) }}m). Coba buka area terbuka atau toggle GPS ON/OFF.</span>
                            </div>
                            <div v-else class="p-3 bg-emerald-50/80 border border-emerald-200/80 rounded-xl flex items-center justify-between gap-2 text-xs text-emerald-800">
                                <div class="flex items-center gap-2">
                                    <span class="text-emerald-600 text-sm">📍</span>
                                    <span>GPS Aktif (Akurasi: ±{{ Math.round(gpsAccuracy) }}m)<span v-if="distanceToOffice !== null"> • Jarak: {{ formatDistance(distanceToOffice) }}</span></span>
                                </div>
                                <span v-if="isWithinRadius" class="px-2 py-0.5 bg-emerald-200/80 text-emerald-800 rounded-md font-bold text-[10px]">Dalam Radius</span>
                            </div>

                            <!-- Tombol Aksi Absensi Utama -->
                            <div class="pt-2">
                                <!-- State 1: Sudah Absen Masuk dan Sudah Absen Pulang -->
                                <div v-if="todayAttendance?.clock_in && todayAttendance?.clock_out" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-center">
                                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center mx-auto mb-2 shadow-xs">
                                        ✓
                                    </div>
                                    <div class="text-sm font-bold text-emerald-800">Absensi Hari Ini Selesai</div>
                                    <div class="text-xs text-emerald-600 mt-0.5">Terima kasih atas kerja keras Anda hari ini!</div>
                                </div>

                                <!-- State 2: Belum Masuk -->
                                <button
                                    v-else-if="!todayAttendance?.clock_in"
                                    @click="submitClockIn"
                                    :disabled="!canSubmitClockIn || isSubmitting"
                                    type="button"
                                    :class="[
                                        canSubmitClockIn
                                            ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/25 active:scale-95'
                                            : 'bg-slate-300 text-slate-500 cursor-not-allowed',
                                        'w-full py-3.5 px-4 rounded-xl font-bold text-sm flex items-center justify-center gap-2 transition-all'
                                    ]"
                                >
                                    <svg v-if="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span v-if="isSubmitting">Memproses Absen...</span>
                                    <span v-else-if="!capturedPhoto">Ambil Foto Selfie Dulu</span>
                                    <span v-else-if="!userCoordinates">Menunggu Lokasi GPS...</span>
                                    <span v-else>📷 Absen Masuk Sekarang</span>
                                </button>

                                <!-- State 3: Sudah Masuk, Mau Pulang -->
                                <button
                                    v-else
                                    @click="submitClockOut"
                                    :disabled="!canSubmitClockOut || isSubmitting"
                                    type="button"
                                    :class="[
                                        canSubmitClockOut
                                            ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/25 active:scale-95'
                                            : 'bg-slate-300 text-slate-500 cursor-not-allowed',
                                        'w-full py-3.5 px-4 rounded-xl font-bold text-sm flex items-center justify-center gap-2 transition-all'
                                    ]"
                                >
                                    <svg v-if="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    <span v-if="isSubmitting">Memproses Absen...</span>
                                    <span v-else-if="!capturedPhoto">Ambil Foto Selfie Dulu</span>
                                    <span v-else>📷 Absen Pulang Sekarang</span>
                                </button>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: LIVE WEBCAM & SELFIE CAMERA -->
                        <div class="space-y-3">
                            <div class="relative w-full aspect-4/3 bg-slate-900 rounded-2xl overflow-hidden border border-slate-200/90 shadow-inner flex items-center justify-center">
                                <!-- 1. Live Video Stream -->
                                <video
                                    v-show="isCameraOpen && !capturedPhoto"
                                    ref="videoRef"
                                    autoplay
                                    playsinline
                                    class="w-full h-full object-cover transform -scale-x-100"
                                ></video>

                                <!-- 2. Snapshot Captured Image -->
                                <img
                                    v-if="capturedPhoto"
                                    :src="capturedPhoto"
                                    alt="Selfie"
                                    class="w-full h-full object-cover"
                                />

                                <!-- 3. Placeholder jika kamera mati dan belum ada foto -->
                                <div
                                    v-if="!isCameraOpen && !capturedPhoto"
                                    class="flex flex-col items-center justify-center text-slate-400 p-6 text-center space-y-3"
                                >
                                    <div class="w-14 h-14 rounded-2xl bg-slate-800 border border-slate-700/80 flex items-center justify-center text-blue-400 shadow-sm">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-slate-200">Kamera Belum Aktif</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">Buka kamera untuk mengambil foto selfie verifikasi wajah</div>
                                    </div>
                                    <button
                                        @click="startCamera"
                                        type="button"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-md transition active:scale-95"
                                    >
                                        Buka Kamera
                                    </button>
                                </div>

                                <!-- Floating Watermark Timestamp di Foto -->
                                <div
                                    v-if="capturedPhoto"
                                    class="absolute bottom-2 left-2 right-2 p-2 bg-black/60 backdrop-blur-xs rounded-xl text-[10px] text-white flex items-center justify-between"
                                >
                                    <div>
                                        <div class="font-bold truncate">{{ currentEmployee?.name }}</div>
                                        <div class="text-slate-300">{{ currentTimeStr }} • {{ todayDateStr }}</div>
                                    </div>
                                    <div class="text-right text-emerald-400 font-mono text-[9px]">
                                        GPS Verified ✓
                                    </div>
                                </div>

                                <!-- Hidden Canvas for snapshot -->
                                <canvas ref="canvasRef" class="hidden"></canvas>
                            </div>

                            <!-- Kontrol Kamera Bawah -->
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xs text-slate-500 font-medium">
                                    <span v-if="capturedPhoto" class="text-emerald-600 font-semibold flex items-center gap-1">
                                        ✓ Foto selfie siap digunakan
                                    </span>
                                    <span v-else-if="isCameraOpen" class="text-blue-600 flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                                        Kamera aktif, posisikan wajah di tengah
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Tombol Jepret Foto -->
                                    <button
                                        v-if="isCameraOpen && !capturedPhoto"
                                        @click="captureSnapshot"
                                        type="button"
                                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition active:scale-95 flex items-center gap-1.5 shadow-sm"
                                    >
                                        <span>📸 Ambil Foto</span>
                                    </button>

                                    <!-- Tombol Foto Ulang -->
                                    <button
                                        v-if="capturedPhoto"
                                        @click="retakePhoto"
                                        type="button"
                                        class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold transition active:scale-95 flex items-center gap-1"
                                    >
                                        <span>🔄 Foto Ulang</span>
                                    </button>

                                    <!-- Tombol Tutup Kamera -->
                                    <button
                                        v-if="isCameraOpen && !capturedPhoto"
                                        @click="stopCamera"
                                        type="button"
                                        class="px-3 py-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-xl text-xs font-semibold transition"
                                    >
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. ACCORDION PANDUAN ABSENSI (SESUAI SCREENSHOT) -->
                <div class="space-y-2">
                    <!-- Panduan Masuk -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        <button
                            @click="expandPanduanMasuk = !expandPanduanMasuk"
                            type="button"
                            class="w-full px-4 py-3 flex items-center justify-between text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                        >
                            <div class="flex items-center gap-2 text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-slate-800">Panduan Absensi Masuk</span>
                            </div>
                            <svg :class="['w-4 h-4 text-slate-400 transition-transform', expandPanduanMasuk ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-show="expandPanduanMasuk" class="px-4 pb-3.5 pt-1 text-xs text-slate-500 border-t border-slate-100 bg-slate-50/50 space-y-1">
                            <p>1. Pastikan fitur GPS / Lokasi pada perangkat Anda telah diaktifkan dan browser memiliki izin akses lokasi.</p>
                            <p>2. Tekan tombol <b>Buka Kamera</b> dan ambil foto selfie dengan wajah terlihat jelas dan pencahayaan cukup.</p>
                            <p>3. Pastikan Anda berada dalam radius area kantor / cabang penugasan Anda.</p>
                            <p>4. Klik tombol <b>Absen Masuk</b> sebelum jam kerja shift dimulai untuk menghindari status terlambat.</p>
                        </div>
                    </div>

                    <!-- Panduan Pulang -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-2xs overflow-hidden">
                        <button
                            @click="expandPanduanPulang = !expandPanduanPulang"
                            type="button"
                            class="w-full px-4 py-3 flex items-center justify-between text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                        >
                            <div class="flex items-center gap-2 text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-slate-800">Panduan Absensi Pulang</span>
                            </div>
                            <svg :class="['w-4 h-4 text-slate-400 transition-transform', expandPanduanPulang ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-show="expandPanduanPulang" class="px-4 pb-3.5 pt-1 text-xs text-slate-500 border-t border-slate-100 bg-slate-50/50 space-y-1">
                            <p>1. Lakukan absen pulang setelah jam kerja shift berakhir atau setelah menyelesaikan pekerjaan operasional harian.</p>
                            <p>2. Buka kamera dan ambil foto selfie di lokasi akhir kerja.</p>
                            <p>3. Klik tombol <b>Absen Pulang</b> untuk merekam waktu kepulangan secara resmi.</p>
                        </div>
                    </div>
                </div>

                <!-- 5. JADWAL SHIFT MINGGU INI (SESUAI SCREENSHOT) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-3">
                    <div class="flex items-center gap-2 text-slate-800 font-bold text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Jadwal Shift Minggu Ini</span>
                    </div>

                    <!-- 7 Kolom Hari -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                        <div
                            v-for="day in weeklySchedules"
                            :key="day.date"
                            :class="[
                                day.is_today
                                    ? 'border-2 border-blue-500 bg-blue-50/40 shadow-xs'
                                    : 'border border-slate-200/80 bg-white hover:bg-slate-50/60',
                                'rounded-xl p-3 text-center transition flex flex-col justify-between min-h-[90px]'
                            ]"
                        >
                            <div>
                                <div :class="['text-xs font-semibold', day.is_sunday ? 'text-rose-500 font-bold' : 'text-slate-500']">
                                    {{ day.day_name }}
                                </div>
                                <div :class="['text-sm font-bold mt-0.5', day.is_sunday ? 'text-rose-600' : 'text-slate-800']">
                                    {{ day.day_number }}
                                </div>
                            </div>

                            <div class="mt-2">
                                <span
                                    v-if="day.shift_label === 'OFF'"
                                    class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200"
                                >
                                    OFF
                                </span>
                                <span
                                    v-else-if="day.shift_label !== '-'"
                                    :class="[getShiftBadgeStyle(day.shift_color), 'inline-block px-1.5 py-0.5 rounded-md text-[10px] font-semibold border truncate max-w-full']"
                                    :title="day.shift_label + ' (' + day.shift_time + ')'"
                                >
                                    {{ day.shift_label }}
                                </span>
                                <span v-else class="text-xs text-slate-300">
                                    -
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: MONITORING KEHADIRAN SELURUH KARYAWAN (ADMIN VIEW) -->
            <div v-else class="space-y-4">
                <!-- Summary Cards Monitoring -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase text-slate-400">Total Karyawan</div>
                            <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ monitoring.totalEmployees }}</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase text-slate-400">Hadir Hari Ini</div>
                            <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ monitoring.presentCount }}</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase text-slate-400">Terlambat</div>
                            <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ monitoring.lateCount }}</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold uppercase text-slate-400">Belum Absen</div>
                            <div class="text-2xl font-bold text-slate-800 mt-0.5">{{ monitoring.absentCount }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Rekap Kehadiran -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-sm font-bold text-slate-800">Daftar Kehadiran Hari Ini ({{ todayDateStr }})</h3>
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
                                    <th class="px-4 py-3">Shift</th>
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
                                        <div class="text-[11px] text-slate-400">{{ att.employee?.position || 'Staf' }} • {{ att.employee?.area?.name || 'Area' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 border border-slate-200">
                                            {{ att.shift?.name || 'Reguler' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <img
                                            v-if="att.photo_in"
                                            :src="'/storage/' + att.photo_in"
                                            alt="In"
                                            @click="viewPhotoModal = '/storage/' + att.photo_in"
                                            class="w-9 h-9 rounded-lg object-cover border border-slate-200 cursor-pointer hover:opacity-80 transition"
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
                                            class="w-9 h-9 rounded-lg object-cover border border-slate-200 cursor-pointer hover:opacity-80 transition"
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
                                    <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                        Belum ada data absensi untuk hari ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    todayDateStr: String,
    todayIso: String,
    currentEmployee: Object,
    allEmployees: Array,
    todaySchedule: Object,
    shiftToday: Object,
    todayAttendance: Object,
    weeklySchedules: Array,
    officeLocation: Object,
    monitoring: Object,
    areas: Array,
    positions: Array,
    filters: Object,
});

const activeTab = ref('self');
const expandPanduanMasuk = ref(false);
const expandPanduanPulang = ref(false);
const filterAreaMonitoring = ref(props.filters?.area_id || '');
const viewPhotoModal = ref(null);

// Realtime Clock
const currentTimeStr = ref('');
let timerInterval = null;

const updateClock = () => {
    const d = new Date();
    currentTimeStr.value = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

// Geolocation GPS State
const userCoordinates = ref(null);
const gpsAccuracy = ref(0);
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
        distanceToOffice.value = 15; // default simulasi jarak dekat
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
    isWithinRadius.value = dist <= (props.officeLocation.radius_meters || 200);
};

const formatDistance = (meters) => {
    if (meters < 1000) return `${meters}m`;
    return `${(meters / 1000).toFixed(1)}km`;
};

// Webcam State
const isCameraOpen = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
const capturedPhoto = ref(null);
let mediaStream = null;

const startCamera = async () => {
    try {
        isCameraOpen.value = true;
        capturedPhoto.value = null;
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
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

const captureSnapshot = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    // Mirror snapshot
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.85);
    stopCamera();
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

// Submitting state
const isSubmitting = ref(false);

const canSubmitClockIn = computed(() => {
    return !!capturedPhoto.value && !props.todayAttendance?.clock_in;
});

const canSubmitClockOut = computed(() => {
    return !!capturedPhoto.value && !!props.todayAttendance?.clock_in && !props.todayAttendance?.clock_out;
});

const submitClockIn = () => {
    if (!canSubmitClockIn.value) return;
    isSubmitting.value = true;

    router.post(route('attendances.clock-in'), {
        employee_id: props.currentEmployee.id,
        photo: capturedPhoto.value,
        latitude: userCoordinates.value?.latitude || null,
        longitude: userCoordinates.value?.longitude || null,
        distance: distanceToOffice.value || 0,
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

const switchEmployee = (empId) => {
    router.get(route('attendances.index'), { employee_id: empId }, { preserveScroll: true });
};

const applyMonitoringFilter = () => {
    router.get(route('attendances.index'), { area_id: filterAreaMonitoring.value }, { preserveScroll: true });
};

const getShiftBadgeStyle = (color) => {
    switch (color) {
        case 'emerald': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'purple': return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'amber': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'rose': return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'cyan': return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'blue':
        default: return 'bg-blue-50 text-blue-700 border-blue-200';
    }
};

onMounted(() => {
    updateClock();
    timerInterval = setInterval(updateClock, 1000);
    initGeolocation();
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    stopCamera();
});
</script>
