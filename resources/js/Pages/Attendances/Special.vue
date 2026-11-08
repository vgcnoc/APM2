<template>
    <AppLayout title="Absen Khusus">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- 1. TOP HEADER & GRUP SELECTOR -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center font-black shadow-md shadow-blue-500/20 text-lg">
                            ⚡
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                                <span>Absen Khusus</span>
                                <span class="px-2.5 py-0.5 text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/70 rounded-full">
                                    {{ currentTeam?.name || 'TIM KHUSUS' }}
                                </span>
                            </h1>
                            <p class="text-xs text-slate-500 font-medium mt-0.5 flex flex-wrap items-center gap-2">
                                <span>Grup: <strong>{{ currentTeam?.name }}</strong></span>
                                <span>•</span>
                                <span>🕒 {{ formatTimeDisplay(currentTeam?.start_time) }} - {{ formatTimeDisplay(currentTeam?.end_time) }}</span>
                                <span>•</span>
                                <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                                    {{ currentTeam?.without_radius ? 'Tanpa radius' : 'Dengan radius' }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT ACTION BUTTONS: KELOLA & GRUP BARU -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Dropdown Switcher Grup -->
                    <div class="relative">
                        <select
                            :value="currentTeam?.id"
                            @change="switchTeam($event.target.value)"
                            class="text-xs font-bold rounded-xl border-slate-200 bg-slate-50/80 text-slate-700 focus:border-blue-500 focus:ring-blue-500/20 py-2 pl-3 pr-8 shadow-2xs cursor-pointer"
                        >
                            <option v-for="team in teams" :key="team.id" :value="team.id">
                                👥 {{ team.name }} ({{ team.members_count || 0 }} Anggota)
                            </option>
                        </select>
                    </div>

                    <button
                        v-if="can('attendances_special_manage')"
                        type="button"
                        @click="openKelolaModal"
                        class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5 active:scale-95"
                    >
                        <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Kelola</span>
                    </button>

                    <button
                        v-if="can('attendances_special_create_group')"
                        type="button"
                        @click="openCreateGroupModal"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-sm shadow-blue-500/25 flex items-center gap-1.5 active:scale-95"
                    >
                        <span>+</span>
                        <span>Grup Baru</span>
                    </button>
                </div>
            </div>

            <!-- 2. TAB NAVIGATOR SESUAI SCREENSHOT -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none border-b border-slate-200/80">
                <button
                    type="button"
                    @click="activeTab = 'absen'"
                    :class="[
                        activeTab === 'absen'
                            ? 'bg-blue-900 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap'
                    ]"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <span>Absen</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'riwayat'"
                    :class="[
                        activeTab === 'riwayat'
                            ? 'bg-blue-900 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap'
                    ]"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Riwayat</span>
                </button>

                <button
                    v-if="can('attendances_special_tab_admin')"
                    type="button"
                    @click="activeTab = 'admin'"
                    :class="[
                        activeTab === 'admin'
                            ? 'bg-blue-900 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap'
                    ]"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>Data Admin</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'best'"
                    :class="[
                        activeTab === 'best'
                            ? 'bg-blue-900 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap'
                    ]"
                >
                    <span>🏆</span>
                    <span>Absen Terbaik</span>
                </button>

                <!-- TAB POTONGAN TIM KHUSUS -->
                <button
                    type="button"
                    @click="activeTab = 'potongan_khusus'"
                    :class="[
                        activeTab === 'potongan_khusus'
                            ? 'bg-blue-900 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap'
                    ]"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span>Potongan Tim Khusus</span>
                </button>

                <!-- TAB POTONGAN TIM SERBU -->
                <button
                    type="button"
                    @click="activeTab = 'potongan_serbu'"
                    :class="[
                        activeTab === 'potongan_serbu'
                            ? 'bg-blue-900 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap'
                    ]"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span>Potongan Tim Serbu</span>
                </button>

                <!-- TAB POTONGAN TIM BC-4 -->
                <button
                    type="button"
                    @click="activeTab = 'potongan_bc4'"
                    :class="[
                        activeTab === 'potongan_bc4'
                            ? 'bg-blue-900 text-white shadow-sm'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 whitespace-nowrap'
                    ]"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span>Potongan Tim BC-4</span>
                </button>
            </div>

            <!-- 3. ACCORDION PANDUAN ABSENSI (MASUK & PULANG) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Panduan Masuk -->
                <div class="bg-blue-50/40 border border-blue-200/80 rounded-2xl p-4 transition-all">
                    <button
                        type="button"
                        @click="showGuideIn = !showGuideIn"
                        class="w-full flex items-center justify-between text-left"
                    >
                        <div class="flex items-center gap-2.5 text-xs font-bold text-blue-900">
                            <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px]">ℹ️</span>
                            <span>Panduan Absensi Masuk</span>
                        </div>
                        <span class="text-xs text-blue-600 font-bold transition-transform" :class="{ 'rotate-180': showGuideIn }">
                            ▼
                        </span>
                    </button>
                    <div v-show="showGuideIn" class="mt-3 pt-3 border-t border-blue-200/60 text-xs text-slate-600 space-y-1.5 leading-relaxed">
                        <p>• <strong>Jam Masuk Standar:</strong> {{ formatTimeDisplay(currentTeam?.start_time) }} WIB dengan toleransi keterlambatan {{ currentTeam?.tolerance_minutes || 15 }} menit.</p>
                        <p>• <strong>Foto Selfie:</strong> Wajib mengambil foto selfie wajah langsung melalui kamera perangkat saat berada di titik operasional.</p>
                        <p>• <strong>Tanpa Radius:</strong> Absensi tim khusus tidak dibatasi jarak radius ke kantor cabang, dapat dilakukan langsung dari lokasi penugasan.</p>
                    </div>
                </div>

                <!-- Panduan Pulang -->
                <div class="bg-blue-50/40 border border-blue-200/80 rounded-2xl p-4 transition-all">
                    <button
                        type="button"
                        @click="showGuideOut = !showGuideOut"
                        class="w-full flex items-center justify-between text-left"
                    >
                        <div class="flex items-center gap-2.5 text-xs font-bold text-blue-900">
                            <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[10px]">ℹ️</span>
                            <span>Panduan Absensi Pulang</span>
                        </div>
                        <span class="text-xs text-blue-600 font-bold transition-transform" :class="{ 'rotate-180': showGuideOut }">
                            ▼
                        </span>
                    </button>
                    <div v-show="showGuideOut" class="mt-3 pt-3 border-t border-blue-200/60 text-xs text-slate-600 space-y-1.5 leading-relaxed">
                        <p>• <strong>Jam Pulang Standar:</strong> Mulai pukul {{ formatTimeDisplay(currentTeam?.end_time) }} WIB.</p>
                        <p>• <strong>Foto Selesai Tugas:</strong> Lakukan foto selfie dan cantumkan ringkasan hasil pekerjaan/tiket yang diselesaikan pada kolom catatan.</p>
                        <p>• <strong>Kedisiplinan Pulang:</strong> Melewatkan absen pulang akan dikenakan potongan tidak absen pulang pada modul penggajian.</p>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 1: ABSEN (MAIN VIEW SESUAI SCREENSHOT)                 -->
            <!-- ========================================================= -->
            <div v-if="activeTab === 'absen'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- KOLOM KIRI: ABSEN HARI INI -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-5">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-slate-800">Absen Hari Ini</h2>
                            <!-- Switcher Karyawan untuk Admin -->
                            <div v-if="allEmployees.length > 1" class="text-right">
                                <select
                                    :value="currentEmployee?.id"
                                    @change="switchEmployee($event.target.value)"
                                    class="text-[11px] rounded-lg border-slate-200 py-1 pl-2 pr-6 font-semibold text-slate-600"
                                >
                                    <option v-for="emp in allEmployees" :key="emp.id" :value="emp.id">
                                        {{ emp.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Card Karyawan Aktif -->
                        <div class="p-4 rounded-xl border border-slate-200/90 bg-slate-50/50 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-black text-slate-900 tracking-tight">
                                        {{ currentEmployee?.name || 'Karyawan' }}
                                    </h3>
                                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                                        {{ currentEmployee?.position || 'Staff' }} • {{ currentTeam?.name }}
                                    </p>
                                </div>

                                <!-- Status Badge Sesuai Screenshot -->
                                <div>
                                    <span
                                        v-if="!todayAttendance?.clock_in"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-200/80 text-slate-700"
                                    >
                                        Belum Masuk
                                    </span>
                                    <span
                                        v-else-if="todayAttendance?.clock_in && !todayAttendance?.clock_out"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        Sudah Masuk: {{ todayAttendance.clock_in }}
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-100 text-blue-800 border border-blue-200"
                                    >
                                        Selesai Pulang: {{ todayAttendance.clock_out }}
                                    </span>
                                </div>
                            </div>

                            <!-- Tombol Aksi Lebar (Navy Sesuai Gambar) -->
                            <div class="pt-2">
                                <!-- Banner Penolakan Kamera Sebelum Jam Yang Ditentukan -->
                                <div
                                    v-if="!todayAttendance?.clock_in && isEarlyClockInRestricted"
                                    class="mb-3 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5 shadow-2xs"
                                >
                                    <span class="text-base shrink-0">⏳</span>
                                    <div class="space-y-1">
                                        <div class="font-bold flex items-center gap-1.5">
                                            <span>Presensi Masuk Belum Dibuka</span>
                                            <span class="px-2 py-0.5 rounded-md bg-amber-200/90 text-[10px] text-amber-900 font-extrabold font-mono">
                                                Buka {{ clockInWindowInfo?.earliest_full_label }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-amber-800 leading-relaxed">
                                            Kamera menolak absensi sebelum jam yang ditentukan. Jam kerja {{ currentTeam?.name }} ({{ clockInWindowInfo?.shift_start }} WIB) baru dapat diabsen maksimal {{ clockInWindowInfo?.early_limit_minutes }} menit sebelum shift.
                                            Tersisa <strong>{{ formatEarlyLimitCountdown(clockInWindowInfo?.minutes_until_open) }}</strong>.
                                        </p>
                                    </div>
                                </div>

                                <button
                                    v-if="!todayAttendance?.clock_in"
                                    type="button"
                                    @click="triggerAttendanceAction('in')"
                                    :disabled="isEarlyClockInRestricted"
                                    class="w-full py-3 bg-[#1e3a8a] hover:bg-[#172554] disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center justify-center gap-2 active:scale-98"
                                >
                                    <span v-if="isEarlyClockInRestricted">🔒</span>
                                    <span v-else>→]</span>
                                    <span>{{ isEarlyClockInRestricted ? `Kamera Ditolak (Buka ${clockInWindowInfo?.earliest_full_label})` : 'Absen Masuk' }}</span>
                                </button>

                                <div
                                    v-if="todayAttendance?.clock_in && !todayAttendance?.clock_out && isEarlyClockOutRestricted"
                                    class="p-3.5 rounded-xl bg-amber-50/90 border border-amber-300 text-amber-950 flex items-start gap-2.5 shadow-2xs mb-2 text-left"
                                >
                                    <span class="text-base">⏳</span>
                                    <div class="min-w-0 flex-1 leading-snug">
                                        <div class="font-bold flex items-center gap-1.5">
                                            <span>Presensi Pulang Belum Dibuka</span>
                                            <span class="px-2 py-0.5 rounded-md bg-amber-200/90 text-[10px] text-amber-900 font-extrabold font-mono">
                                                Buka {{ clockOutWindowInfo?.earliest_full_label }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-amber-800 leading-relaxed mt-0.5">
                                            Kamera menolak absensi sebelum jam shift selesai ({{ clockOutWindowInfo?.shift_end }} WIB).
                                            Tersisa <strong>{{ formatEarlyLimitCountdown(clockOutWindowInfo?.minutes_until_open) }}</strong>.
                                        </p>
                                    </div>
                                </div>

                                <button
                                    v-else-if="todayAttendance?.clock_in && !todayAttendance?.clock_out"
                                    type="button"
                                    @click="triggerAttendanceAction('out')"
                                    :disabled="isEarlyClockOutRestricted"
                                    :class="[
                                        isEarlyClockOutRestricted ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' : 'bg-[#0f766e] hover:bg-[#115e59] text-white shadow-sm active:scale-98',
                                        'w-full py-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2'
                                    ]"
                                >
                                    <span v-if="isEarlyClockOutRestricted">🔒</span>
                                    <span v-else>[←</span>
                                    <span>{{ isEarlyClockOutRestricted ? `Kamera Ditolak (Buka ${clockOutWindowInfo?.earliest_full_label})` : 'Absen Pulang' }}</span>
                                </button>

                                <div
                                    v-else
                                    class="w-full py-3 bg-emerald-50 text-emerald-800 border border-emerald-200/90 rounded-xl text-xs font-bold flex items-center justify-center gap-2"
                                >
                                    <span>✓</span>
                                    <span>Absensi Lengkap Hari Ini</span>
                                </div>

                                <div
                                    v-if="checkoutDeadlineLabel && (!todayAttendance?.clock_out)"
                                    class="text-[11px] text-slate-500 text-center pt-1.5 font-medium"
                                >
                                    ⏰ Batas terakhir absen pulang Tim Khusus: <strong class="text-slate-700">{{ checkoutDeadlineLabel }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Info Realtime Jam & Koordinat -->
                        <div class="flex items-center justify-between text-[11px] text-slate-500 font-medium px-1">
                            <span>🕒 Waktu Sekarang: <strong class="text-slate-800">{{ currentRealTime }} WIB</strong></span>
                            <span v-if="gpsStatus === 'ready'" class="text-emerald-600 font-semibold flex items-center gap-1">
                                <span>📍 GPS Siap (±{{ Math.round(userCoords.accuracy || 15) }}m)</span>
                            </span>
                            <span v-else-if="gpsStatus === 'detecting'" class="text-amber-600 animate-pulse flex items-center gap-1">
                                <span>📍 Mendeteksi GPS{{ currentTeam?.without_radius ? ' (Opsional)' : '' }}...</span>
                            </span>
                            <span v-else-if="currentTeam?.without_radius" class="text-slate-600 font-medium flex items-center gap-1" title="Absen Khusus beroperasi tanpa batasan radius kantor">
                                <span class="text-emerald-600 font-bold">✓</span>
                                <span>Tanpa Radius (GPS Opsional)</span>
                            </span>
                            <span v-else class="text-rose-500 font-semibold flex items-center gap-1">
                                <span>⚠️ GPS Tidak Terdeteksi</span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: SEMUA ABSEN HARI INI -->
                <div class="lg:col-span-7 space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span>Semua Absen Hari Ini</span>
                                <span class="px-2 py-0.5 text-[11px] bg-slate-100 text-slate-700 font-bold rounded-full">
                                    {{ todayAttendances.length }} Karyawan
                                </span>
                            </h2>
                            <span class="text-xs text-slate-400 font-medium">{{ todayDateFormatted }}</span>
                        </div>

                        <!-- Daftar Absensi Karyawan Hari Ini (Sesuai Screenshot) -->
                        <div v-if="todayAttendances.length > 0" class="divide-y divide-slate-100">
                            <div
                                v-for="att in todayAttendances"
                                :key="att.id"
                                class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/70 rounded-xl px-2 transition-colors"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <!-- Avatar -->
                                    <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs overflow-hidden flex-shrink-0 border border-slate-300">
                                        <img
                                            v-if="att.photo_in"
                                            :src="'/storage/' + att.photo_in"
                                            :alt="att.employee?.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else>{{ getInitials(att.employee?.name) }}</span>
                                    </div>

                                    <!-- Nama & Jam -->
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-900 truncate">
                                            {{ att.employee?.name }}
                                        </div>
                                        <div class="text-[11px] text-slate-500 font-medium">
                                            Masuk: <span class="font-semibold text-slate-700">{{ att.clock_in }}</span> • Pulang: <span class="font-semibold text-slate-700">{{ att.clock_out || '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Thumbnail Foto & Badge OK -->
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button
                                        v-if="att.photo_in"
                                        type="button"
                                        @click="previewPhotoModal('/storage/' + att.photo_in, att)"
                                        class="w-8 h-8 rounded-lg overflow-hidden border border-slate-200 shadow-2xs hover:scale-105 transition-transform"
                                        title="Lihat foto selfie"
                                    >
                                        <img :src="'/storage/' + att.photo_in" class="w-full h-full object-cover" />
                                    </button>

                                    <!-- Badge OK / Telat -->
                                    <span
                                        v-if="att.status === 'present'"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-500 text-white"
                                    >
                                        OK
                                    </span>
                                    <span
                                        v-else-if="att.status === 'late'"
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-500 text-white"
                                    >
                                        Telat {{ att.late_minutes }}m
                                    </span>
                                    <span
                                        v-else
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-slate-400 text-white"
                                    >
                                        {{ att.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- State Kosong -->
                        <div v-else class="text-center py-8 text-slate-400 space-y-2">
                            <span class="text-3xl">👥</span>
                            <p class="text-xs">Belum ada anggota tim yang melakukan absensi hari ini.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 2: RIWAYAT                                            -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'riwayat'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Riwayat Kehadiran Seluruh Karyawan Tim Khusus</h2>
                            <p class="text-xs text-slate-500">Mencakup seluruh catatan kehadiran dari Tim Khusus, Tim Serbu, dan Tim BC-4</p>
                        </div>
                        <!-- Filter Form -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Filter Tim Khusus -->
                            <select
                                v-model="filterHistoryTeamId"
                                class="text-xs rounded-xl border-slate-200 py-1.5 px-2.5 font-semibold text-slate-700 bg-white"
                            >
                                <option value="all">Semua Tim Khusus (Seluruh Karyawan)</option>
                                <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>

                            <!-- Filter Karyawan -->
                            <select
                                v-model="filterHistoryEmployeeId"
                                class="text-xs rounded-xl border-slate-200 py-1.5 px-2.5 font-semibold text-slate-700 bg-white"
                            >
                                <option value="all">Semua Karyawan</option>
                                <option v-for="emp in (allSpecialEmployees || allEmployees)" :key="emp.id" :value="emp.id">
                                    {{ emp.name }}
                                </option>
                            </select>

                            <!-- Filter Status -->
                            <select
                                v-model="filterHistoryStatus"
                                class="text-xs rounded-xl border-slate-200 py-1.5 px-2.5 font-semibold text-slate-700 bg-white"
                            >
                                <option value="all">Semua Status</option>
                                <option value="present">Tepat Waktu (OK)</option>
                                <option value="late">Terlambat</option>
                            </select>

                            <input
                                type="date"
                                v-model="filterStartDate"
                                class="text-xs rounded-xl border-slate-200 py-1.5 px-2.5 font-semibold text-slate-700"
                            />
                            <span class="text-xs text-slate-400">s/d</span>
                            <input
                                type="date"
                                v-model="filterEndDate"
                                class="text-xs rounded-xl border-slate-200 py-1.5 px-2.5 font-semibold text-slate-700"
                            />
                            <button
                                type="button"
                                @click="applyHistoryFilter"
                                class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-2xs"
                            >
                                Filter
                            </button>
                            <button
                                type="button"
                                @click="resetHistoryFilter"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition"
                                title="Reset filter"
                            >
                                Reset
                            </button>
                        </div>
                    </div>

                    <!-- Tabel Riwayat -->
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Tim Khusus</th>
                                    <th class="p-3">Karyawan</th>
                                    <th class="p-3">Masuk</th>
                                    <th class="p-3">Pulang</th>
                                    <th class="p-3">Foto</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Catatan / Lokasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr v-for="item in history.data" :key="item.id" class="hover:bg-slate-50/50">
                                    <td class="p-3 font-semibold">{{ item.date }}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ item.team?.name || '-' }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-bold text-slate-900">{{ item.employee?.name }}</td>
                                    <td class="p-3 font-mono font-semibold">{{ item.clock_in || '-' }}</td>
                                    <td class="p-3 font-mono font-semibold">{{ item.clock_out || '-' }}</td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                v-if="item.photo_in"
                                                type="button"
                                                @click="previewPhotoModal('/storage/' + item.photo_in, item)"
                                                class="w-7 h-7 rounded overflow-hidden border border-slate-200 hover:scale-105 transition-transform"
                                            >
                                                <img :src="'/storage/' + item.photo_in" class="w-full h-full object-cover" />
                                            </button>
                                            <button
                                                v-if="item.photo_out"
                                                type="button"
                                                @click="previewPhotoModal('/storage/' + item.photo_out, item)"
                                                class="w-7 h-7 rounded overflow-hidden border border-slate-200 hover:scale-105 transition-transform"
                                            >
                                                <img :src="'/storage/' + item.photo_out" class="w-full h-full object-cover" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span
                                            :class="[
                                                item.status === 'present' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' :
                                                item.status === 'late' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-rose-100 text-rose-800 border-rose-200',
                                                'px-2 py-0.5 rounded text-[10px] font-bold border'
                                            ]"
                                        >
                                            {{ item.status === 'present' ? 'OK' : item.status === 'late' ? 'Telat ' + item.late_minutes + 'm' : item.status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-slate-500 max-w-xs truncate">
                                        {{ item.notes || item.location_in || '-' }}
                                    </td>
                                </tr>
                                <tr v-if="!history.data || history.data.length === 0">
                                    <td colspan="8" class="p-6 text-center text-slate-400">
                                        Tidak ada catatan riwayat absensi pada rentang tanggal ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 3: DATA ADMIN                                         -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'admin'" class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-800">Rekapitulasi Kehadiran Anggota Tim</h2>
                            <p class="text-xs text-slate-500">Monitoring rekap bulanan untuk grup {{ currentTeam?.name }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                type="month"
                                :value="selectedAdminMonth"
                                @change="switchAdminMonth($event.target.value)"
                                class="text-xs rounded-xl border-slate-200 font-bold text-slate-700 py-1.5 px-3"
                            />
                            <button
                                v-if="can('attendances_special_manage')"
                                type="button"
                                @click="openAdminManualModal"
                                class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-2xs"
                            >
                                + Input Manual
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Nama Karyawan</th>
                                    <th class="p-3">Jabatan / Role</th>
                                    <th class="p-3 text-center">Total Hadir</th>
                                    <th class="p-3 text-center">Total Telat</th>
                                    <th class="p-3 text-center">Menit Telat</th>
                                    <th class="p-3 text-center">Tanpa Pulang</th>
                                    <th class="p-3 text-center">Skor Poin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr v-for="row in adminMatrix" :key="row.employee_id" class="hover:bg-slate-50/50">
                                    <td class="p-3 font-bold text-slate-900">{{ row.name }}</td>
                                    <td class="p-3 text-slate-500">{{ row.position }} • {{ row.role }}</td>
                                    <td class="p-3 text-center font-bold text-emerald-600">{{ row.presents }} hari</td>
                                    <td class="p-3 text-center font-bold" :class="row.lates > 0 ? 'text-amber-600' : 'text-slate-400'">{{ row.lates }}x</td>
                                    <td class="p-3 text-center font-bold" :class="row.late_minutes > 0 ? 'text-amber-600' : 'text-slate-400'">{{ row.late_minutes }}m</td>
                                    <td class="p-3 text-center font-bold" :class="row.no_clock_out > 0 ? 'text-rose-600' : 'text-slate-400'">{{ row.no_clock_out }}x</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-bold border border-blue-200">
                                            {{ row.score }} pts
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 4: ABSEN TERBAIK (LEADERBOARD PERLOMBAAN 3 TIM)       -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'best'" class="space-y-6">
                <!-- Header Card Absen Terbaik -->
                <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl p-6 text-white shadow-lg space-y-2">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-bold text-amber-300 tracking-wider uppercase flex items-center gap-1.5">
                                <span>🏆</span>
                                <span>Perlombaan Absen Terbaik Global (Seluruh Tim Khusus)</span>
                            </div>
                            <h2 class="text-2xl font-black mt-1">Klasemen Global: Seluruh Tim Khusus</h2>
                            <p class="text-xs text-blue-100 max-w-xl">
                                Seluruh anggota tim khusus berlomba dalam satu klasemen global. Juara 1 s/d 3 teratas secara global berhak mendapatkan reward tunai bulanan.
                            </p>
                        </div>
                        <button
                            v-if="can('attendances_sync_payroll')"
                            type="button"
                            @click="triggerSyncPayroll"
                            class="px-5 py-2.5 bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-xs rounded-xl transition shadow-md flex items-center gap-2 self-start active:scale-95"
                        >
                            <span>⚡</span>
                            <span>Sinkronkan ke Payroll</span>
                        </button>
                    </div>
                </div>

                <!-- Podium Top 3 Global Cards (Seluruh Tim Khusus) -->
                <div
                    v-if="globalTop3 && globalTop3.length > 0 && globalTop3[0].presents > 0"
                    class="grid gap-5"
                    :class="globalTop3.length === 1 ? 'grid-cols-1 max-w-sm mx-auto' : globalTop3.length === 2 ? 'grid-cols-1 md:grid-cols-2 max-w-2xl mx-auto' : 'grid-cols-1 md:grid-cols-3'"
                >
                    <!-- Juara 1 Global -->
                    <div v-if="globalTop3[0]" class="bg-white rounded-2xl border-2 border-amber-300 p-5 shadow-sm relative overflow-hidden space-y-3">
                        <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center text-2xl font-bold">
                            🥇
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800">Juara 1 Global</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200">
                                {{ globalTop3[0]?.team_name }}
                            </span>
                        </div>
                        <div class="pt-1">
                            <h3 class="text-base font-black text-slate-900">{{ globalTop3[0]?.name || '-' }}</h3>
                            <p class="text-xs text-slate-500">{{ globalTop3[0]?.position || 'Karyawan' }}</p>
                        </div>
                        <div class="p-3 bg-amber-50/70 rounded-xl border border-amber-200/60">
                            <div class="text-xs text-amber-900 font-semibold">Reward Tunai (Global):</div>
                            <div class="text-lg font-black text-amber-700">Rp {{ rewardsConfig.juara_1.toLocaleString('id-ID') }}</div>
                        </div>
                        <div class="text-xs text-slate-600 flex justify-between pt-1">
                            <span>Hadir: <strong>{{ globalTop3[0]?.presents || 0 }} hari</strong></span>
                            <span>Skor: <strong>{{ globalTop3[0]?.score || 0 }} pts</strong></span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex justify-between pt-1 border-t border-slate-100">
                            <span>Rata-rata Masuk:</span>
                            <strong class="text-slate-700 font-mono">{{ globalTop3[0]?.avg_clock_in_formatted || '-' }}</strong>
                        </div>
                    </div>

                    <!-- Juara 2 Global -->
                    <div v-if="globalTop3[1]" class="bg-white rounded-2xl border-2 border-slate-300 p-5 shadow-sm relative overflow-hidden space-y-3">
                        <div class="absolute -right-4 -top-4 w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-2xl font-bold">
                            🥈
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700">Juara 2 Global</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200">
                                {{ globalTop3[1]?.team_name }}
                            </span>
                        </div>
                        <div class="pt-1">
                            <h3 class="text-base font-black text-slate-900">{{ globalTop3[1]?.name || '-' }}</h3>
                            <p class="text-xs text-slate-500">{{ globalTop3[1]?.position || 'Karyawan' }}</p>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="text-xs text-slate-700 font-semibold">Reward Tunai (Global):</div>
                            <div class="text-lg font-black text-slate-800">Rp {{ rewardsConfig.juara_2.toLocaleString('id-ID') }}</div>
                        </div>
                        <div class="text-xs text-slate-600 flex justify-between pt-1">
                            <span>Hadir: <strong>{{ globalTop3[1]?.presents || 0 }} hari</strong></span>
                            <span>Skor: <strong>{{ globalTop3[1]?.score || 0 }} pts</strong></span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex justify-between pt-1 border-t border-slate-100">
                            <span>Rata-rata Masuk:</span>
                            <strong class="text-slate-700 font-mono">{{ globalTop3[1]?.avg_clock_in_formatted || '-' }}</strong>
                        </div>
                    </div>

                    <!-- Juara 3 Global -->
                    <div v-if="globalTop3[2]" class="bg-white rounded-2xl border-2 border-orange-300 p-5 shadow-sm relative overflow-hidden space-y-3">
                        <div class="absolute -right-4 -top-4 w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center text-2xl font-bold">
                            🥉
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-800">Juara 3 Global</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200">
                                {{ globalTop3[2]?.team_name }}
                            </span>
                        </div>
                        <div class="pt-1">
                            <h3 class="text-base font-black text-slate-900">{{ globalTop3[2]?.name || '-' }}</h3>
                            <p class="text-xs text-slate-500">{{ globalTop3[2]?.position || 'Karyawan' }}</p>
                        </div>
                        <div class="p-3 bg-orange-50/70 rounded-xl border border-orange-200/60">
                            <div class="text-xs text-orange-900 font-semibold">Reward Tunai (Global):</div>
                            <div class="text-lg font-black text-orange-700">Rp {{ rewardsConfig.juara_3.toLocaleString('id-ID') }}</div>
                        </div>
                        <div class="text-xs text-slate-600 flex justify-between pt-1">
                            <span>Hadir: <strong>{{ globalTop3[2]?.presents || 0 }} hari</strong></span>
                            <span>Skor: <strong>{{ globalTop3[2]?.score || 0 }} pts</strong></span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex justify-between pt-1 border-t border-slate-100">
                            <span>Rata-rata Masuk:</span>
                            <strong class="text-slate-700 font-mono">{{ globalTop3[2]?.avg_clock_in_formatted || '-' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Empty State Leaderboard -->
                <div v-else class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center space-y-3 shadow-2xs">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-3xl mx-auto border border-amber-200/60 shadow-2xs">
                        🏆
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Belum Ada Peringkat Absen Terbaik Bulan Ini</h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                        Peringkat dan reward perlombaan absen terbaik seluruh tim khusus akan otomatis dihitung dan ditampilkan setelah anggota tim memiliki catatan kehadiran terverifikasi pada periode bulan ini.
                    </p>
                </div>

                <!-- Status Papan Peringkat Absen Terbaik (All Seluruh Tim Khusus) -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-blue-50/70 border border-blue-200/80 rounded-2xl px-5 py-3.5 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">🏆</span>
                        <div>
                            <span class="text-xs font-bold text-blue-950">Klasemen Absen Terbaik Gabungan Seluruh Tim Khusus (All)</span>
                            <p class="text-[11px] text-blue-700">Tidak ada juara per grup tim — seluruh anggota tim (Grup Tim Khusus, Tim Serbu, Tim BC-4) dinilai dan diperingkatkan dalam satu klasemen global terpadu.</p>
                        </div>
                    </div>
                    <span class="text-xs font-bold text-blue-800 bg-white px-3 py-1.5 rounded-xl border border-blue-200 shadow-2xs">
                        {{ (leaderboard || []).length }} Peserta Berkompetisi
                    </span>
                </div>

                <!-- Tabel Peringkat Perlombaan Tim Khusus Lengkap -->
                <div v-if="filteredLeaderboard && filteredLeaderboard.length > 0" class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Papan Peringkat Global Tim Khusus</h3>
                            <p class="text-xs text-slate-500">Hasil akumulasi poin kedisiplinan dan absensi tepat waktu secara global</p>
                        </div>
                        <span class="text-xs font-bold text-slate-500">{{ filteredLeaderboard.length }} Peserta</span>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-3 text-center">Peringkat Global</th>
                                    <th class="p-3">Nama Karyawan</th>
                                    <th class="p-3">Grup Tim Khusus</th>
                                    <th class="p-3 text-center">Hadir</th>
                                    <th class="p-3 text-center">Avg Jam Masuk</th>
                                    <th class="p-3 text-center">Telat</th>
                                    <th class="p-3 text-center">Menit Telat</th>
                                    <th class="p-3 text-center">Tanpa Pulang</th>
                                    <th class="p-3 text-center">Skor Poin</th>
                                    <th class="p-3 text-center">Status Hadiah (Global)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr
                                    v-for="row in filteredLeaderboard"
                                    :key="row.employee_id"
                                    class="hover:bg-slate-50/50"
                                    :class="(row.global_rank || 99) <= 3 ? 'bg-amber-50/20' : ''"
                                >
                                    <td class="p-3 text-center font-bold">
                                        <span v-if="row.global_rank === 1" class="text-base text-amber-600">🥇 #1</span>
                                        <span v-else-if="row.global_rank === 2" class="text-base text-slate-600">🥈 #2</span>
                                        <span v-else-if="row.global_rank === 3" class="text-base text-orange-600">🥉 #3</span>
                                        <span v-else class="text-slate-500">#{{ row.global_rank }}</span>
                                    </td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ row.name }}</div>
                                        <div class="text-[11px] text-slate-500">{{ row.position }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            {{ row.team_name }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-center font-bold text-emerald-600">{{ row.presents }} hari</td>
                                    <td class="p-3 text-center font-mono font-semibold text-slate-700">{{ row.avg_clock_in_formatted || '-' }}</td>
                                    <td class="p-3 text-center font-bold" :class="row.lates > 0 ? 'text-amber-600' : 'text-slate-400'">{{ row.lates }}x</td>
                                    <td class="p-3 text-center font-bold" :class="row.late_minutes > 0 ? 'text-amber-600' : 'text-slate-400'">{{ row.late_minutes }}m</td>
                                    <td class="p-3 text-center font-bold" :class="row.no_clock_out > 0 ? 'text-rose-600' : 'text-slate-400'">{{ row.no_clock_out }}x</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-bold border border-blue-200">
                                            {{ row.score }} pts
                                        </span>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span v-if="row.global_rank === 1" class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]">Juara 1 Global (Rp {{ rewardsConfig.juara_1.toLocaleString('id-ID') }})</span>
                                        <span v-else-if="row.global_rank === 2" class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[10px]">Juara 2 Global (Rp {{ rewardsConfig.juara_2.toLocaleString('id-ID') }})</span>
                                        <span v-else-if="row.global_rank === 3" class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 font-bold text-[10px]">Juara 3 Global (Rp {{ rewardsConfig.juara_3.toLocaleString('id-ID') }})</span>
                                        <span v-else class="text-slate-400 text-[11px]">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- TAB 5, 6, 7: POTONGAN TIM KHUSUS / SERBU / BC-4           -->
            <!-- ========================================================= -->
            <div v-else-if="activeTab === 'potongan_khusus' || activeTab === 'potongan_serbu' || activeTab === 'potongan_bc4'" class="space-y-5">
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">
                                Potongan Absensi {{ activeDeductionTeam?.name }}
                            </h2>
                            <p class="text-xs text-slate-500">
                                Monitoring nominal potongan keterlambatan dan absen pulang periode {{ selectedAdminMonth }} yang disinkronkan ke Payroll.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                type="month"
                                :value="selectedAdminMonth"
                                @change="switchAdminMonth($event.target.value)"
                                class="text-xs rounded-xl border-slate-200 font-bold text-slate-700 py-1.5 px-3"
                            />
                            <button
                                type="button"
                                @click="triggerSyncPayroll"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-2xs flex items-center gap-1.5"
                            >
                                <span>⚙️ Sinkron ke Payroll</span>
                            </button>
                        </div>
                    </div>

                    <!-- Ringkasan Tarif & Akumulasi Potongan (4 Cards) -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="text-[11px] text-slate-500 font-medium">Total Keterlambatan</div>
                            <div class="text-lg font-black text-amber-600 mt-1">
                                {{ currentTeamDeductions?.late_count || 0 }}x
                                <span class="text-xs font-normal text-slate-500">({{ currentTeamDeductions?.late_minutes || 0 }}m)</span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Tarif: Rp {{ Number(activeDeductionTeam?.late_deduction_amount || 10000).toLocaleString('id-ID') }}/kejadian</div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="text-[11px] text-slate-500 font-medium">Tanpa Absen Pulang</div>
                            <div class="text-lg font-black text-rose-600 mt-1">
                                {{ currentTeamDeductions?.no_out_count || 0 }}x
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Tarif: Rp {{ Number(activeDeductionTeam?.no_out_deduction_amount || 10000).toLocaleString('id-ID') }}/kejadian</div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                            <div class="text-[11px] text-slate-500 font-medium">Tarif Alpa</div>
                            <div class="text-lg font-black text-slate-800 mt-1">
                                Rp {{ Number(activeDeductionTeam?.alpha_deduction_amount || 35000).toLocaleString('id-ID') }}
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5">Potongan per hari kerja</div>
                        </div>

                        <div class="p-4 bg-amber-50/60 rounded-xl border border-amber-200/80">
                            <div class="text-[11px] text-amber-900 font-bold">Total Potongan Bulan Ini</div>
                            <div class="text-lg font-black text-amber-700 mt-1">
                                Rp {{ Number(currentTeamDeductions?.total_amount || 0).toLocaleString('id-ID') }}
                            </div>
                            <div class="text-[10px] text-amber-600 mt-0.5">Akumulasi ke payroll</div>
                        </div>
                    </div>

                    <!-- Tabel Rincian Pelanggaran Potongan -->
                    <div class="space-y-3 pt-2">
                        <div class="text-xs font-bold text-slate-800">Rincian Pelanggaran Absensi Anggota Tim:</div>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                    <tr>
                                        <th class="p-3">Tanggal</th>
                                        <th class="p-3">Nama Anggota</th>
                                        <th class="p-3">Jam Masuk</th>
                                        <th class="p-3">Jam Pulang</th>
                                        <th class="p-3 text-center">Menit Telat</th>
                                        <th class="p-3 text-right">Potongan Telat</th>
                                        <th class="p-3 text-right">Tanpa Pulang</th>
                                        <th class="p-3 text-right">Total Potongan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-700">
                                    <tr v-for="rec in currentTeamDeductions?.records" :key="rec.id" class="hover:bg-slate-50/60">
                                        <td class="p-3 font-semibold">{{ rec.date }}</td>
                                        <td class="p-3 font-bold text-slate-900">{{ rec.employee_name }} <span class="text-[10px] font-normal text-slate-400">({{ rec.position }})</span></td>
                                        <td class="p-3 font-mono">{{ rec.clock_in || '-' }}</td>
                                        <td class="p-3 font-mono">{{ rec.clock_out || '-' }}</td>
                                        <td class="p-3 text-center font-bold" :class="rec.late_minutes > 0 ? 'text-amber-600' : 'text-slate-400'">
                                            {{ rec.late_minutes > 0 ? rec.late_minutes + 'm' : '-' }}
                                        </td>
                                        <td class="p-3 text-right font-mono" :class="rec.late_fine > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'">
                                            Rp {{ rec.late_fine.toLocaleString('id-ID') }}
                                        </td>
                                        <td class="p-3 text-right font-mono" :class="rec.no_out_fine > 0 ? 'text-rose-600 font-bold' : 'text-slate-400'">
                                            Rp {{ rec.no_out_fine.toLocaleString('id-ID') }}
                                        </td>
                                        <td class="p-3 text-right font-mono font-black text-rose-700">
                                            Rp {{ rec.total_fine.toLocaleString('id-ID') }}
                                        </td>
                                    </tr>
                                    <tr v-if="!currentTeamDeductions?.records || currentTeamDeductions.records.length === 0">
                                        <td colspan="8" class="p-6 text-center text-slate-400">
                                            ✨ Tidak ada catatan keterlambatan atau pelanggaran absensi pada periode ini. Seluruh anggota tim disiplin!
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 1: KAMERA WEBCAM SELFIE                             -->
            <!-- ========================================================= -->
            <div
                v-if="isCameraModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 space-y-4 p-5 animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>📷</span>
                            <span>{{ actionType === 'in' ? 'Absen Masuk' : 'Absen Pulang' }} — {{ currentTeam?.name }}</span>
                        </h3>
                        <button
                            type="button"
                            @click="closeCameraModal"
                            class="text-slate-400 hover:text-slate-600 text-lg font-bold"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Video / Snapshot Preview Area -->
                    <div class="relative rounded-2xl overflow-hidden bg-slate-900 aspect-4/3 flex items-center justify-center border border-slate-800">
                        <!-- Overlay Kamera Masuk Ditolak Jika Belum Jam Yang Ditentukan -->
                        <div
                            v-if="actionType === 'in' && isEarlyClockInRestricted"
                            class="absolute inset-0 z-30 bg-slate-950/95 flex flex-col items-center justify-center p-6 text-center space-y-3"
                        >
                            <div class="w-16 h-16 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-3xl border border-amber-500/30">
                                ⛔
                            </div>
                            <div class="text-base font-bold text-amber-400">Kamera Masuk Ditolak</div>
                            <p class="text-xs text-slate-300 max-w-xs leading-relaxed">
                                Absen masuk belum dibuka. Jam kerja <strong>{{ currentTeam?.name }}</strong> ({{ clockInWindowInfo?.shift_start }} WIB) baru dapat diabsen mulai pukul <strong class="text-amber-300 font-mono">{{ clockInWindowInfo?.earliest_full_label }}</strong>.
                            </p>
                            <div class="px-3.5 py-1.5 rounded-xl bg-amber-900/60 border border-amber-600/40 text-xs text-amber-200 font-mono font-bold">
                                ⏳ Sisa Waktu Buka: {{ formatEarlyLimitCountdown(clockInWindowInfo?.minutes_until_open) }}
                            </div>
                        </div>

                        <!-- Overlay Kamera Pulang Ditolak Jika Belum Waktunya -->
                        <div
                            v-else-if="actionType === 'out' && isEarlyClockOutRestricted"
                            class="absolute inset-0 z-30 bg-slate-950/95 flex flex-col items-center justify-center p-6 text-center space-y-3"
                        >
                            <div class="w-16 h-16 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-3xl border border-rose-500/30">
                                ⛔
                            </div>
                            <div class="text-base font-bold text-rose-400">Kamera Pulang Ditolak</div>
                            <p class="text-xs text-slate-300 max-w-xs leading-relaxed">
                                Absen pulang belum dibuka. Jam operasional <strong>{{ currentTeam?.name }}</strong> belum selesai (selesai pukul {{ clockOutWindowInfo?.shift_end }} WIB). Presensi pulang baru dapat dilakukan mulai pukul <strong class="text-rose-300 font-mono">{{ clockOutWindowInfo?.earliest_full_label }}</strong>.
                            </p>
                            <div class="px-3.5 py-1.5 rounded-xl bg-rose-950/80 border border-rose-600/40 text-xs text-rose-200 font-mono font-bold">
                                ⏳ Sisa Waktu Buka: {{ formatEarlyLimitCountdown(clockOutWindowInfo?.minutes_until_open) }}
                            </div>
                        </div>

                        <video
                            v-show="!capturedPhoto && !cameraError"
                            ref="videoRef"
                            autoplay
                            playsinline
                            class="w-full h-full object-cover"
                            :class="{ '-scale-x-100': cameraFacing === 'user' }"
                        ></video>

                        <img
                            v-if="capturedPhoto"
                            :src="capturedPhoto"
                            class="w-full h-full object-cover"
                        />

                        <!-- Fallback jika kamera bermasalah -->
                        <div v-if="cameraError && !capturedPhoto" class="flex flex-col items-center justify-center p-6 text-center space-y-3">
                            <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center text-2xl">
                                📷
                            </div>
                            <div class="text-sm font-bold text-white">Kamera Langsung Tidak Tersedia</div>
                            <p class="text-xs text-slate-400 max-w-xs leading-relaxed">
                                Kamera tidak dapat diakses langsung. Anda tetap dapat melakukan absensi dengan memilih foto atau menggunakan kamera bawaan ponsel.
                            </p>
                            <button
                                type="button"
                                @click="fileInputRef?.click()"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow"
                            >
                                <span>📁</span>
                                <span>Pilih / Ambil Foto</span>
                            </button>
                        </div>

                        <!-- Overlay Switch Kamera -->
                        <button
                            v-if="!capturedPhoto && !cameraError"
                            type="button"
                            @click="switchCameraFacing"
                            class="absolute top-3 right-3 px-3 py-1.5 bg-black/50 text-white rounded-full text-xs font-semibold backdrop-blur-xs hover:bg-black/70 flex items-center gap-1.5"
                        >
                            <span>🔄 Putar Kamera</span>
                        </button>
                    </div>

                    <!-- Hidden File Input & Canvas untuk capture/upload & watermark -->
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/*"
                        capture="user"
                        class="hidden"
                        @change="handleFileUpload"
                    />
                    <canvas ref="canvasRef" class="hidden"></canvas>

                    <!-- Lokasi GPS & Catatan -->
                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Catatan / Keterangan Tugas (Opsional)</label>
                            <textarea
                                v-model="attendanceForm.notes"
                                rows="2"
                                class="w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20 text-xs font-medium"
                                placeholder="Contoh: Maintenance ODP-01 Cikoneng..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Tombol Aksi Kamera -->
                    <div class="flex items-center justify-between gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="fileInputRef?.click()"
                            class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1.5"
                            title="Pilih file foto dari galeri atau kamera"
                        >
                            <span>📁</span>
                            <span>Upload Foto</span>
                        </button>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="closeCameraModal"
                                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition"
                            >
                                Batal
                            </button>

                            <button
                                v-if="!capturedPhoto && !cameraError"
                                type="button"
                                @click="takeSnapshot"
                                :disabled="(actionType === 'in' && isEarlyClockInRestricted) || (actionType === 'out' && isEarlyClockOutRestricted)"
                                :class="[
                                    (actionType === 'in' && isEarlyClockInRestricted) || (actionType === 'out' && isEarlyClockOutRestricted) ? 'bg-slate-400 hover:bg-slate-400 opacity-60 cursor-not-allowed shadow-none' : 'bg-blue-600 hover:bg-blue-700 shadow-sm',
                                    'px-5 py-2.5 text-white rounded-xl text-xs font-bold transition flex items-center gap-2'
                                ]"
                            >
                                <span v-if="(actionType === 'in' && isEarlyClockInRestricted) || (actionType === 'out' && isEarlyClockOutRestricted)">🔒 Kamera Ditolak</span>
                                <span v-else>📸 Ambil Foto Selfie</span>
                            </button>

                            <button
                                v-if="capturedPhoto"
                                type="button"
                                @click="retakeSnapshot"
                                class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition"
                            >
                                Foto Ulang
                            </button>

                            <button
                                v-if="capturedPhoto"
                                type="button"
                                @click="submitAttendance"
                                :disabled="attendanceForm.processing"
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2"
                            >
                                <span v-if="attendanceForm.processing">Mengirim...</span>
                                <span v-else>Kirim Absensi</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 2: LIGHTBOX FOTO PREVIEW                            -->
            <!-- ========================================================= -->
            <div
                v-if="isLightboxOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xs"
                @click.self="isLightboxOpen = false"
            >
                <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-bold text-slate-900">
                            {{ activePreviewItem?.employee?.name || 'Foto Selfie' }}
                        </div>
                        <button type="button" @click="isLightboxOpen = false" class="text-slate-400 hover:text-slate-700 font-bold">
                            ✕
                        </button>
                    </div>
                    <div class="rounded-2xl overflow-hidden bg-slate-900 border border-slate-200 aspect-4/3">
                        <img :src="lightboxImg" class="w-full h-full object-cover" />
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium space-y-0.5">
                        <p>🕒 Jam: <strong>{{ activePreviewItem?.clock_in }} WIB</strong></p>
                        <p>📍 Lokasi: <strong>{{ activePreviewItem?.location_in || 'Tanpa radius' }}</strong></p>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 3: + GRUP BARU                                      -->
            <!-- ========================================================= -->
            <div
                v-if="isCreateGroupModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>➕</span>
                            <span>Tambah Grup Tim Khusus Baru</span>
                        </h3>
                        <button type="button" @click="isCreateGroupModalOpen = false" class="text-slate-400 font-bold">✕</button>
                    </div>

                    <form @submit.prevent="submitCreateGroup" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Grup</label>
                            <input
                                type="text"
                                v-model="groupForm.name"
                                required
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                placeholder="Contoh: TIM FIBER OPTIK"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Masuk (WIB)</label>
                                <input
                                    type="time"
                                    v-model="groupForm.start_time"
                                    required
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Pulang (WIB)</label>
                                <input
                                    type="time"
                                    v-model="groupForm.end_time"
                                    required
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Toleransi Telat (Menit)</label>
                                <input
                                    type="number"
                                    v-model="groupForm.tolerance_minutes"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tarif Potongan Telat (Rp)</label>
                                <input
                                    type="number"
                                    v-model="groupForm.late_deduction_amount"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="isCreateGroupModalOpen = false"
                                class="px-4 py-2 bg-slate-100 rounded-xl font-bold"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="groupForm.processing"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-sm"
                            >
                                Simpan Grup
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 4: KELOLA GRUP & ANGGOTA                            -->
            <!-- ========================================================= -->
            <div
                v-if="isKelolaModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl border border-slate-200 p-6 space-y-4 max-h-[90vh] flex flex-col">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>👥</span>
                            <span>Kelola Grup {{ currentTeam?.name }}</span>
                        </h3>
                        <button type="button" @click="isKelolaModalOpen = false" class="text-slate-400 font-bold">✕</button>
                    </div>

                    <form @submit.prevent="submitUpdateKelola" class="space-y-4 text-xs overflow-y-auto pr-1">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Grup</label>
                            <input
                                type="text"
                                v-model="kelolaForm.name"
                                required
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Masuk (WIB)</label>
                                <input
                                    type="time"
                                    v-model="kelolaForm.start_time"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Pulang (WIB)</label>
                                <input
                                    type="time"
                                    v-model="kelolaForm.end_time"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Toleransi Telat (Menit)</label>
                                <input
                                    type="number"
                                    v-model="kelolaForm.tolerance_minutes"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tarif Telat (Rp)</label>
                                <input
                                    type="number"
                                    v-model="kelolaForm.late_deduction_amount"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tanpa Pulang (Rp)</label>
                                <input
                                    type="number"
                                    v-model="kelolaForm.no_out_deduction_amount"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                        </div>

                        <div class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200">
                            <input
                                type="checkbox"
                                id="kelola_without_radius"
                                v-model="kelolaForm.without_radius"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label for="kelola_without_radius" class="font-bold text-slate-700 cursor-pointer">
                                Tanpa Batasan Radius Kantor (Bebas Lokasi / Tanpa Geofencing)
                            </label>
                        </div>

                        <!-- Pilih Anggota Tim -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block font-bold text-slate-700">Pilih Anggota Tim Khusus:</label>
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ kelolaForm.member_ids.length }} Dipilih
                                </span>
                            </div>

                            <!-- Search bar & Quick actions -->
                            <div class="space-y-1.5 mb-2">
                                <div class="relative">
                                    <input
                                        type="text"
                                        v-model="memberSearchQuery"
                                        placeholder="Cari nama karyawan atau jabatan..."
                                        class="w-full text-xs rounded-xl border-slate-200 pl-8 pr-7 py-1.5 focus:border-blue-500 focus:ring-blue-500 bg-white"
                                    />
                                    <span class="absolute left-2.5 top-1.5 text-slate-400 text-xs">🔍</span>
                                    <button
                                        v-if="memberSearchQuery"
                                        type="button"
                                        @click="memberSearchQuery = ''"
                                        class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600 text-xs font-bold"
                                    >✕</button>
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-slate-500 px-1">
                                    <span>Menampilkan {{ filteredKelolaEmployees.length }} dari {{ (allEmployees || []).length }} karyawan</span>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="selectAllFiltered"
                                            class="text-blue-600 hover:text-blue-700 font-bold hover:underline"
                                        >
                                            Pilih Semua
                                        </button>
                                        <span class="text-slate-300">|</span>
                                        <button
                                            type="button"
                                            @click="deselectAllFiltered"
                                            class="text-rose-600 hover:text-rose-700 font-bold hover:underline"
                                        >
                                            Batal Pilih
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- List Karyawan -->
                            <div class="max-h-52 overflow-y-auto rounded-xl border border-slate-200 p-2 space-y-1.5 bg-slate-50/50">
                                <label
                                    v-for="emp in filteredKelolaEmployees"
                                    :key="emp.id"
                                    :class="[
                                        'flex items-center justify-between p-2 rounded-xl cursor-pointer transition-all border',
                                        kelolaForm.member_ids.includes(emp.id)
                                            ? 'bg-blue-50/70 border-blue-200 text-blue-900 shadow-2xs'
                                            : 'bg-white hover:bg-slate-50 border-slate-100 text-slate-700'
                                    ]"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <input
                                            type="checkbox"
                                            :value="emp.id"
                                            v-model="kelolaForm.member_ids"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                                        />
                                        <div>
                                            <div class="font-bold text-xs">{{ emp.name }}</div>
                                            <div class="text-[10px] text-slate-400">{{ emp.position || 'Staff' }} {{ emp.branch ? '• ' + emp.branch : '' }}</div>
                                        </div>
                                    </div>
                                    <span
                                        v-if="kelolaForm.member_ids.includes(emp.id)"
                                        class="text-[10px] font-bold text-blue-600 bg-white px-2 py-0.5 rounded-md border border-blue-200"
                                    >
                                        ✓ Terpilih
                                    </span>
                                </label>

                                <div
                                    v-if="filteredKelolaEmployees.length === 0"
                                    class="py-8 text-center text-slate-400 text-xs font-medium space-y-1"
                                >
                                    <div class="text-lg">🔍</div>
                                    <div>Tidak ada karyawan yang sesuai pencarian.</div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-between gap-2 border-t border-slate-100">
                            <button
                                v-if="teams.length > 1"
                                type="button"
                                @click="deleteTeam"
                                class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl font-bold text-xs transition"
                            >
                                🗑️ Hapus Grup
                            </button>
                            <div v-else></div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="isKelolaModalOpen = false"
                                    class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl font-bold text-xs"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    :disabled="kelolaForm.processing"
                                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs shadow-sm"
                                >
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- MODAL 5: INPUT MANUAL ADMIN                               -->
            <!-- ========================================================= -->
            <div
                v-if="isAdminManualModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
            >
                <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-slate-200 p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <span>📝</span>
                            <span>Input Absensi Manual Admin</span>
                        </h3>
                        <button type="button" @click="isAdminManualModalOpen = false" class="text-slate-400 font-bold">✕</button>
                    </div>

                    <form @submit.prevent="submitAdminManual" class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Karyawan</label>
                            <select
                                v-model="adminManualForm.employee_id"
                                required
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                            >
                                <option v-for="emp in allEmployees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }} ({{ emp.position || 'Staff' }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tanggal</label>
                            <input
                                type="date"
                                v-model="adminManualForm.date"
                                required
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Masuk</label>
                                <input
                                    type="time"
                                    v-model="adminManualForm.clock_in"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Jam Pulang</label>
                                <input
                                    type="time"
                                    v-model="adminManualForm.clock_out"
                                    class="w-full rounded-xl border-slate-200 font-bold text-xs"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Status</label>
                            <select
                                v-model="adminManualForm.status"
                                class="w-full rounded-xl border-slate-200 font-bold text-xs"
                            >
                                <option value="present">Hadir (OK)</option>
                                <option value="late">Terlambat</option>
                                <option value="sick">Sakit</option>
                                <option value="permit">Izin</option>
                                <option value="absent">Alpa</option>
                            </select>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="isAdminManualModalOpen = false"
                                class="px-4 py-2 bg-slate-100 rounded-xl font-bold"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="adminManualForm.processing"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-sm"
                            >
                                Simpan Absensi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const { can, hasRole } = usePermission();

const props = defineProps({
    currentTeam: Object,
    teams: Array,
    currentEmployee: Object,
    todayAttendance: Object,
    todayAttendances: Array,
    teamMembers: Array,
    history: Object,
    adminMatrix: Array,
    leaderboard: Array,
    rewardsConfig: Object,
    allTeamsDeductions: Object,
    allEmployees: Array,
    allSpecialEmployees: Array,
    isCurrentEmployeeMember: Boolean,
    checkoutDeadlineLabel: String,
    clockInWindowInfo: Object,
    clockOutWindowInfo: Object,
    filters: Object,
});

// Early clock-in restriction
const isEarlyClockInRestricted = computed(() => {
    return Boolean(
        props.clockInWindowInfo?.enabled &&
        props.clockInWindowInfo?.is_restricted &&
        !props.todayAttendance?.clock_in
    );
});

// Early clock-out restriction
const isEarlyClockOutRestricted = computed(() => {
    return Boolean(
        props.clockOutWindowInfo?.enabled &&
        props.clockOutWindowInfo?.is_restricted &&
        props.todayAttendance?.clock_in &&
        !props.todayAttendance?.clock_out
    );
});

const formatEarlyLimitCountdown = (mins) => {
    const m = Math.max(1, Number(mins || 0));
    if (m >= 60) {
        const h = Math.floor(m / 60);
        const r = m % 60;
        return r > 0 ? `${h} jam ${r} menit` : `${h} jam`;
    }
    return `${m} menit`;
};

// Tab Active State
const activeTab = ref('absen'); // 'absen', 'riwayat', 'admin', 'best', 'potongan_khusus', 'potongan_serbu', 'potongan_bc4'

const globalTop3 = computed(() => {
    return (props.leaderboard || []).slice(0, 3);
});

const filteredLeaderboard = computed(() => {
    return props.leaderboard || [];
});

// Collapsible Guide State
const showGuideIn = ref(false);
const showGuideOut = ref(false);

// Realtime Clock
const currentRealTime = ref('');
let timerId = null;

const updateClock = () => {
    const d = new Date();
    currentRealTime.value = d.toLocaleTimeString('id-ID', { hour12: false });
};

// User GPS Coords & Status
const userCoords = ref({ lat: null, lng: null, accuracy: null });
const gpsStatus = ref('detecting'); // 'detecting', 'ready', 'offline', 'denied'

const fetchGeolocation = () => {
    gpsStatus.value = 'detecting';
    if (typeof navigator !== 'undefined' && 'geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                userCoords.value = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: pos.coords.accuracy,
                };
                gpsStatus.value = 'ready';
            },
            (err) => {
                console.warn('Geolocation warning: ' + err.message);
                if (err.code === 1) {
                    gpsStatus.value = 'denied';
                } else {
                    gpsStatus.value = 'offline';
                }
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 }
        );
    } else {
        gpsStatus.value = 'offline';
    }
};

// Date Formatters
const todayDateFormatted = new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const formatTimeDisplay = (timeStr) => {
    if (!timeStr) return '-';
    return timeStr.slice(0, 5);
};

const getInitials = (name) => {
    if (!name) return 'K';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
};

// Team & Employee Switchers
const switchTeam = (teamId) => {
    router.get(route('special-attendances.index'), { team_id: teamId }, { preserveState: true });
};

const switchEmployee = (empId) => {
    router.get(route('special-attendances.index'), { team_id: props.currentTeam?.id, employee_id: empId }, { preserveState: true });
};

// Filters Riwayat Seluruh Karyawan Tim Khusus
const filterHistoryTeamId = ref(props.filters?.history_team_id || 'all');
const filterHistoryEmployeeId = ref(props.filters?.filter_employee_id || 'all');
const filterHistoryStatus = ref(props.filters?.filter_status || 'all');
const filterStartDate = ref(props.filters?.start_date || '');
const filterEndDate = ref(props.filters?.end_date || '');

const applyHistoryFilter = () => {
    router.get(
        route('special-attendances.index'),
        {
            team_id: props.currentTeam?.id,
            history_team_id: filterHistoryTeamId.value,
            filter_employee_id: filterHistoryEmployeeId.value,
            filter_status: filterHistoryStatus.value,
            start_date: filterStartDate.value,
            end_date: filterEndDate.value,
        },
        { preserveState: true }
    );
};

const resetHistoryFilter = () => {
    filterHistoryTeamId.value = 'all';
    filterHistoryEmployeeId.value = 'all';
    filterHistoryStatus.value = 'all';
    filterStartDate.value = '';
    filterEndDate.value = '';
    applyHistoryFilter();
};

// Admin Month Filter
const selectedAdminMonth = ref(props.filters?.month || new Date().toISOString().slice(0, 7));
const switchAdminMonth = (monthVal) => {
    selectedAdminMonth.value = monthVal;
    router.get(route('special-attendances.index'), { team_id: props.currentTeam?.id, month: monthVal }, { preserveState: true });
};

// Deductions Team Computation for Tabs 5, 6 & 7
const activeDeductionTeam = computed(() => {
    if (activeTab.value === 'potongan_khusus') {
        const found = (props.teams || []).find((t) => t.name.toLowerCase().includes('khusus'));
        return found || props.currentTeam;
    }
    if (activeTab.value === 'potongan_serbu') {
        const found = (props.teams || []).find((t) => t.name.toLowerCase().includes('serbu'));
        return found || props.currentTeam;
    }
    if (activeTab.value === 'potongan_bc4') {
        const found = (props.teams || []).find((t) => {
            const name = (t.name || '').toLowerCase();
            const code = (t.code || '').toLowerCase();
            return name.includes('bc-4') || name.includes('bc 4') || name.includes('bc4') || code.includes('bc-04') || code.includes('bc-4');
        });
        return found || props.currentTeam;
    }
    return props.currentTeam;
});

const currentTeamDeductions = computed(() => {
    if (!activeDeductionTeam.value?.id || !props.allTeamsDeductions) return null;
    return props.allTeamsDeductions[activeDeductionTeam.value.id] || null;
});

// ========================================================
// CAMERA WEBCAM LOGIC, FILE UPLOAD & WATERMARK OVERLAY
// ========================================================
const isCameraModalOpen = ref(false);
const actionType = ref('in'); // 'in' or 'out'
const videoRef = ref(null);
const canvasRef = ref(null);
const fileInputRef = ref(null);
const capturedPhoto = ref(null);
const cameraError = ref(null);
const cameraFacing = ref('user');
let mediaStream = null;

const attendanceForm = useForm({
    special_team_id: props.currentTeam?.id,
    employee_id: props.currentEmployee?.id,
    photo: '',
    latitude: null,
    longitude: null,
    location_name: 'Lapangan / Titik Operasional',
    notes: '',
});

const triggerAttendanceAction = (type) => {
    if (type === 'in' && isEarlyClockInRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Masuk Ditolak',
            html: `
                <div class="text-left text-xs space-y-2.5">
                    <p class="text-slate-700">Presensi Masuk belum dibuka untuk <strong>${props.currentTeam?.name || 'Tim'}</strong>.</p>
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 space-y-1">
                        <div class="flex justify-between font-bold">
                            <span>Jam Buka Presensi:</span>
                            <span class="font-mono text-blue-700">${props.clockInWindowInfo?.earliest_full_label || '--:--'}</span>
                        </div>
                        <div class="flex justify-between text-slate-500 text-[11px]">
                            <span>Jam Shift Dimulai:</span>
                            <span class="font-mono">${props.clockInWindowInfo?.shift_start || '--:--'} WIB</span>
                        </div>
                        <div class="flex justify-between text-slate-500 text-[11px]">
                            <span>Batas Awal:</span>
                            <span>Maksimal ${props.clockInWindowInfo?.early_limit_minutes || 60} menit</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-amber-800 text-center pt-1 font-semibold">
                        ⏳ Kamera ditolak. Silakan kembali dalam <strong>${formatEarlyLimitCountdown(props.clockInWindowInfo?.minutes_until_open)}</strong>.
                    </p>
                </div>
            `,
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#1e3a8a',
        });
        return;
    }
    if (type === 'out' && isEarlyClockOutRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Pulang Ditolak',
            html: `
                <div class="text-left text-xs space-y-2.5">
                    <p class="text-slate-700">Presensi Pulang belum dibuka untuk <strong>${props.currentTeam?.name || 'Tim'}</strong>.</p>
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200 text-rose-900 space-y-1">
                        <div class="flex justify-between font-bold">
                            <span>Jam Buka Presensi Pulang:</span>
                            <span class="font-mono text-blue-700">${props.clockOutWindowInfo?.earliest_full_label || '--:--'}</span>
                        </div>
                        <div class="flex justify-between text-slate-500 text-[11px]">
                            <span>Jam Shift Selesai:</span>
                            <span class="font-mono">${props.clockOutWindowInfo?.shift_end || '--:--'} WIB</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-rose-800 text-center pt-1 font-semibold">
                        ⏳ Kamera ditolak. Silakan kembali dalam <strong>${formatEarlyLimitCountdown(props.clockOutWindowInfo?.minutes_until_open)}</strong>.
                    </p>
                </div>
            `,
            confirmButtonText: 'Mengerti',
            confirmButtonColor: '#1e3a8a',
        });
        return;
    }
    actionType.value = type;
    attendanceForm.special_team_id = props.currentTeam?.id;
    attendanceForm.employee_id = props.currentEmployee?.id;
    attendanceForm.photo = '';
    attendanceForm.notes = '';
    cameraError.value = null;
    isCameraModalOpen.value = true;
    startCamera();
};

const startCamera = async () => {
    cameraError.value = null;
    capturedPhoto.value = null;
    try {
        if (mediaStream) {
            mediaStream.getTracks().forEach((track) => track.stop());
            mediaStream = null;
        }

        if (!navigator?.mediaDevices?.getUserMedia) {
            throw new Error('Akses kamera langsung tidak tersedia di browser ini.');
        }

        const constraintAttempts = [
            { video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: cameraFacing.value }, audio: false },
            { video: { facingMode: cameraFacing.value }, audio: false },
            { video: true, audio: false },
        ];

        let stream = null;
        let lastError = null;

        for (const constraints of constraintAttempts) {
            try {
                stream = await navigator.mediaDevices.getUserMedia(constraints);
                if (stream) break;
            } catch (err) {
                lastError = err;
            }
        }

        if (!stream) {
            throw lastError || new Error('Kamera tidak dapat diakses.');
        }

        mediaStream = stream;

        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
            videoRef.value.muted = true;
            try {
                await videoRef.value.play();
            } catch (playErr) {
                console.warn('Autoplay video error:', playErr);
            }
        }
    } catch (err) {
        console.warn('Camera error:', err);
        cameraError.value = err.message || 'Kamera tidak dapat diakses.';
    }
};

const switchCameraFacing = () => {
    cameraFacing.value = cameraFacing.value === 'user' ? 'environment' : 'user';
    startCamera();
};

const takeSnapshot = () => {
    if (actionType.value === 'in' && isEarlyClockInRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Masuk Ditolak',
            text: `Absen masuk belum dibuka. Jam kerja ${props.currentTeam?.name || 'Tim'} (${props.clockInWindowInfo?.shift_start || '--:--'} WIB) baru dapat diabsen mulai pukul ${props.clockInWindowInfo?.earliest_full_label || '--:--'}.`,
            confirmButtonColor: '#1e3a8a',
        });
        return;
    }
    if (actionType.value === 'out' && isEarlyClockOutRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Pulang Ditolak',
            text: `Absen pulang belum dibuka. Jam operasional ${props.currentTeam?.name || 'Tim'} (${props.clockOutWindowInfo?.shift_end || '--:--'} WIB) belum selesai. Presensi pulang baru dibuka pukul ${props.clockOutWindowInfo?.earliest_full_label || '--:--'}.`,
            confirmButtonColor: '#1e3a8a',
        });
        return;
    }
    if (!videoRef.value || !canvasRef.value) return;

    const video = videoRef.value;
    const canvas = canvasRef.value;
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const ctx = canvas.getContext('2d');
    if (cameraFacing.value === 'user') {
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
    } else {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    }

    // Watermark Canggih
    drawWatermark(ctx, canvas.width, canvas.height);

    capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.88);
    attendanceForm.photo = capturedPhoto.value;
    attendanceForm.latitude = userCoords.value.lat;
    attendanceForm.longitude = userCoords.value.lng;

    stopCamera();
};

const handleFileUpload = (event) => {
    if (actionType.value === 'in' && isEarlyClockInRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Masuk Ditolak',
            text: `Absen masuk belum dibuka. Jam kerja ${props.currentTeam?.name || 'Tim'} (${props.clockInWindowInfo?.shift_start || '--:--'} WIB) baru dapat diabsen mulai pukul ${props.clockInWindowInfo?.earliest_full_label || '--:--'}.`,
            confirmButtonColor: '#1e3a8a',
        });
        if (event.target) event.target.value = '';
        return;
    }
    if (actionType.value === 'out' && isEarlyClockOutRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Pulang Ditolak',
            text: `Absen pulang belum dibuka. Jam kerja ${props.currentTeam?.name || 'Tim'} (${props.clockOutWindowInfo?.shift_end || '--:--'} WIB) belum selesai. Presensi pulang baru dibuka pukul ${props.clockOutWindowInfo?.earliest_full_label || '--:--'}.`,
            confirmButtonColor: '#1e3a8a',
        });
        if (event.target) event.target.value = '';
        return;
    }
    const file = event.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            const canvas = canvasRef.value;
            if (!canvas) return;

            let targetW = img.width;
            let targetH = img.height;
            const maxDim = 800;
            if (targetW > maxDim || targetH > maxDim) {
                if (targetW > targetH) {
                    targetH = Math.round((targetH * maxDim) / targetW);
                    targetW = maxDim;
                } else {
                    targetW = Math.round((targetW * maxDim) / targetH);
                    targetH = maxDim;
                }
            }

            canvas.width = targetW;
            canvas.height = targetH;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, targetW, targetH);

            drawWatermark(ctx, targetW, targetH);

            capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.88);
            attendanceForm.photo = capturedPhoto.value;
            attendanceForm.latitude = userCoords.value.lat;
            attendanceForm.longitude = userCoords.value.lng;

            stopCamera();
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
};

const drawWatermark = (ctx, w, h) => {
    ctx.save();
    const barHeight = 44;
    ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
    ctx.fillRect(0, h - barHeight, w, barHeight);

    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 12px sans-serif';
    const empName = props.currentEmployee?.name || 'Karyawan';
    const teamName = props.currentTeam?.name || 'TIM KHUSUS';
    const timeStr = `${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')} WIB`;
    ctx.fillText(`${empName} • ${teamName} • ${timeStr}`, 12, h - 24);

    ctx.fillStyle = '#38bdf8';
    ctx.font = '10px sans-serif';
    const coordsStr = userCoords.value.lat ? `GPS: ${userCoords.value.lat.toFixed(5)}, ${userCoords.value.lng.toFixed(5)} (Tanpa Radius)` : 'Lokasi: Lapangan (Tanpa Radius)';
    ctx.fillText(coordsStr, 12, h - 8);
    ctx.restore();
};

const retakeSnapshot = () => {
    capturedPhoto.value = null;
    attendanceForm.photo = '';
    startCamera();
};

const stopCamera = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
};

const closeCameraModal = () => {
    stopCamera();
    isCameraModalOpen.value = false;
    capturedPhoto.value = null;
    cameraError.value = null;
};

const submitAttendance = () => {
    if (actionType.value === 'in' && isEarlyClockInRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Masuk Ditolak',
            text: `Absen masuk belum dibuka. Jam kerja ${props.currentTeam?.name || 'Tim'} (${props.clockInWindowInfo?.shift_start || '--:--'} WIB) baru dapat diabsen mulai pukul ${props.clockInWindowInfo?.earliest_full_label || '--:--'}.`,
            confirmButtonColor: '#1e3a8a',
        });
        return;
    }
    if (actionType.value === 'out' && isEarlyClockOutRestricted.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Kamera Pulang Ditolak',
            text: `Absen pulang belum dibuka. Jam kerja ${props.currentTeam?.name || 'Tim'} (${props.clockOutWindowInfo?.shift_end || '--:--'} WIB) belum selesai. Presensi pulang baru dibuka pukul ${props.clockOutWindowInfo?.earliest_full_label || '--:--'}.`,
            confirmButtonColor: '#1e3a8a',
        });
        return;
    }
    const routeName = actionType.value === 'in' ? 'special-attendances.clock-in' : 'special-attendances.clock-out';
    attendanceForm.post(route(routeName), {
        onSuccess: () => {
            closeCameraModal();
            Swal.fire({
                icon: 'success',
                title: actionType.value === 'in' ? 'Absen Masuk Berhasil!' : 'Absen Pulang Berhasil!',
                text: `Absensi ${props.currentTeam?.name} berhasil dicatat.`,
                timer: 2000,
                showConfirmButton: false,
            });
        },
        onError: (errs) => {
            const firstErr = Object.values(errs)[0] || 'Gagal menyimpan absensi.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Absen',
                text: firstErr,
            });
        }
    });
};

// ========================================================
// LIGHTBOX MODAL
// ========================================================
const isLightboxOpen = ref(false);
const lightboxImg = ref('');
const activePreviewItem = ref(null);

const previewPhotoModal = (imgUrl, item) => {
    lightboxImg.value = imgUrl;
    activePreviewItem.value = item;
    isLightboxOpen.value = true;
};

// ========================================================
// MODAL + GRUP BARU
// ========================================================
const isCreateGroupModalOpen = ref(false);
const groupForm = useForm({
    name: '',
    start_time: '08:30',
    end_time: '18:00',
    tolerance_minutes: 15,
    without_radius: true,
    late_deduction_amount: 10000,
    no_out_deduction_amount: 10000,
    alpha_deduction_amount: 35000,
    description: '',
});

const openCreateGroupModal = () => {
    groupForm.reset();
    isCreateGroupModalOpen.value = true;
};

const submitCreateGroup = () => {
    groupForm.post(route('special-attendances.teams.store'), {
        onSuccess: () => {
            isCreateGroupModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Grup Dibuat',
                text: 'Grup tim khusus baru berhasil ditambahkan.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

// ========================================================
// MODAL KELOLA GRUP & ANGGOTA
// ========================================================
const isKelolaModalOpen = ref(false);
const memberSearchQuery = ref('');

const filteredKelolaEmployees = computed(() => {
    const list = props.allEmployees || [];
    if (!memberSearchQuery.value || !memberSearchQuery.value.trim()) {
        return list;
    }
    const q = memberSearchQuery.value.toLowerCase().trim();
    return list.filter((emp) =>
        (emp.name && emp.name.toLowerCase().includes(q)) ||
        (emp.position && emp.position.toLowerCase().includes(q)) ||
        (emp.branch && emp.branch.toLowerCase().includes(q))
    );
});

const selectAllFiltered = () => {
    const ids = new Set(kelolaForm.member_ids);
    filteredKelolaEmployees.value.forEach((emp) => ids.add(emp.id));
    kelolaForm.member_ids = Array.from(ids);
};

const deselectAllFiltered = () => {
    const idsToRemove = new Set(filteredKelolaEmployees.value.map((e) => e.id));
    kelolaForm.member_ids = kelolaForm.member_ids.filter((id) => !idsToRemove.has(id));
};

const kelolaForm = useForm({
    name: '',
    start_time: '08:30',
    end_time: '18:00',
    tolerance_minutes: 15,
    without_radius: true,
    late_deduction_amount: 10000,
    no_out_deduction_amount: 10000,
    alpha_deduction_amount: 35000,
    description: '',
    member_ids: [],
});

const openKelolaModal = () => {
    if (!props.currentTeam) return;
    memberSearchQuery.value = '';
    kelolaForm.name = props.currentTeam.name;
    kelolaForm.start_time = (props.currentTeam.start_time || '08:30:00').slice(0, 5);
    kelolaForm.end_time = (props.currentTeam.end_time || '18:00:00').slice(0, 5);
    kelolaForm.tolerance_minutes = props.currentTeam.tolerance_minutes || 15;
    kelolaForm.without_radius = props.currentTeam.without_radius ?? true;
    kelolaForm.late_deduction_amount = props.currentTeam.late_deduction_amount || 10000;
    kelolaForm.no_out_deduction_amount = props.currentTeam.no_out_deduction_amount || 10000;
    kelolaForm.alpha_deduction_amount = props.currentTeam.alpha_deduction_amount || 35000;
    kelolaForm.description = props.currentTeam.description || '';
    kelolaForm.member_ids = (props.teamMembers || []).map((m) => m.employee_id);
    isKelolaModalOpen.value = true;
};

const submitUpdateKelola = () => {
    kelolaForm.put(route('special-attendances.teams.update', props.currentTeam.id), {
        onSuccess: () => {
            isKelolaModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Konfigurasi grup berhasil diperbarui.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

const deleteTeam = async () => {
    if (!props.currentTeam) return;
    const confirmResult = await Swal.fire({
        title: 'Hapus Grup Tim?',
        text: `Apakah Anda yakin ingin menghapus grup '${props.currentTeam.name}'? Seluruh data konfigurasi grup ini akan dihapus.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
    });

    if (!confirmResult.isConfirmed) return;

    router.delete(route('special-attendances.teams.destroy', props.currentTeam.id), {
        onSuccess: () => {
            isKelolaModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: 'Grup berhasil dihapus.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

// ========================================================
// MODAL INPUT MANUAL ADMIN
// ========================================================
const isAdminManualModalOpen = ref(false);
const adminManualForm = useForm({
    special_team_id: props.currentTeam?.id,
    employee_id: props.currentEmployee?.id,
    date: new Date().toISOString().slice(0, 10),
    clock_in: '08:30',
    clock_out: '18:00',
    status: 'present',
    late_minutes: 0,
    notes: '',
});

const openAdminManualModal = () => {
    adminManualForm.special_team_id = props.currentTeam?.id;
    adminManualForm.date = new Date().toISOString().slice(0, 10);
    isAdminManualModalOpen.value = true;
};

const submitAdminManual = () => {
    adminManualForm.post(route('special-attendances.admin.store'), {
        onSuccess: () => {
            isAdminManualModalOpen.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'Absensi manual berhasil disimpan.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

// ========================================================
// SINKRONISASI KE PAYROLL
// ========================================================
const triggerSyncPayroll = async () => {
    const isBestTab = activeTab.value === 'best';
    const isDeductionTab = ['potongan_khusus', 'potongan_serbu', 'potongan_bc4'].includes(activeTab.value);
    
    let targetTeamId = props.currentTeam?.id;
    let targetLabel = `grup '${props.currentTeam?.name}'`;

    if (isBestTab) {
        targetTeamId = 'all';
        targetLabel = 'Seluruh Tim Khusus (3 Tim Perlombaan: Khusus, Serbu, BC-4)';
    } else if (isDeductionTab && activeDeductionTeam.value) {
        targetTeamId = activeDeductionTeam.value.id;
        targetLabel = `grup '${activeDeductionTeam.value.name}'`;
    }

    const confirmResult = await Swal.fire({
        title: 'Sinkron ke Payroll?',
        text: `Sinkronkan seluruh data potongan dan reward absen terbaik ${targetLabel} ke modul Penggajian (Payroll) periode ${selectedAdminMonth.value}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Sinkronkan!',
        cancelButtonText: 'Batal',
    });

    if (!confirmResult.isConfirmed) return;

    router.post(
        route('special-attendances.sync-payroll'),
        {
            special_team_id: targetTeamId,
            month: selectedAdminMonth.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: `Data potongan dan reward absen terbaik ${targetLabel} berhasil disinkronkan ke payroll.`,
                    timer: 2000,
                    showConfirmButton: false,
                });
            }
        }
    );
};

onMounted(() => {
    updateClock();
    timerId = setInterval(updateClock, 1000);
    fetchGeolocation();
});

onUnmounted(() => {
    if (timerId) clearInterval(timerId);
    stopCamera();
});
</script>
