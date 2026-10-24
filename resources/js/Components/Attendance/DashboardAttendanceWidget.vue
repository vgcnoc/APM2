<template>
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden transition-all duration-300 hover:shadow-md">
        <!-- TOP ACCENT HEADER -->
        <div class="px-5 py-4 bg-slate-900 text-white flex flex-col md:flex-row md:items-center justify-between gap-3 border-b border-slate-800">
            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Mode Badge: Tim Khusus vs Reguler -->
                <template v-if="isSpecialTeam">
                    <span class="px-2.5 py-1 bg-purple-500/20 text-purple-300 rounded-lg text-[11px] font-black tracking-wider uppercase border border-purple-500/30 flex items-center gap-1.5 shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                        ⚡ Absen Tim Khusus: {{ specialTeam?.name || 'TIM KHUSUS' }}
                    </span>
                </template>
                <template v-else>
                    <span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 rounded-lg text-[11px] font-bold tracking-wider uppercase border border-emerald-500/20 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Presensi Reguler
                    </span>
                </template>

                <span class="text-xs text-slate-500">•</span>
                <span class="text-xs font-semibold text-slate-300">
                    {{ attendanceData?.todayDateStr || todayDateDisplay }}
                </span>

                <!-- Shift / Jam Kerja & Keterangan Libur Badge -->
                <div v-if="isSpecialTeam" class="flex items-center gap-1.5 ml-0 sm:ml-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-purple-500/20 text-purple-200 border border-purple-500/30 flex items-center gap-1 shadow-2xs">
                        <span>⏱️</span>
                        <span>Jam Kerja: {{ formatSpecialTeamTime(specialTeam) }} (Tanpa Radius)</span>
                    </span>
                </div>
                <div v-else-if="isDayOff" class="flex items-center gap-1.5 ml-0 sm:ml-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black bg-rose-500/25 text-rose-300 border border-rose-500/40 flex items-center gap-1.5 shadow-2xs">
                        <span>🏖️</span>
                        <span>LIBUR (OFF)</span>
                        <span v-if="dayOffReason" class="font-normal opacity-90 text-[10px] hidden md:inline">({{ dayOffReason }})</span>
                    </span>
                </div>
                <div v-else-if="shiftToday" class="flex items-center gap-1.5 ml-0 sm:ml-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30 flex items-center gap-1.5 shadow-2xs">
                        <span>⏱️</span>
                        <span>{{ shiftToday.name }} ({{ formatShiftTime(shiftToday) }} WIB)</span>
                        <span class="text-[10px] text-emerald-400 font-bold hidden md:inline">• Hari Kerja</span>
                    </span>
                </div>
                <div v-else class="flex items-center gap-1.5 ml-0 sm:ml-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center gap-1">
                        <span>⚠️</span>
                        <span>Belum Ada Shift Resmi</span>
                    </span>
                </div>
            </div>

            <!-- Digital Clock HUD & Rekap Link -->
            <div class="flex items-center justify-between md:justify-end gap-3">
                <!-- Clock -->
                <div class="flex items-center gap-2.5 bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700/60">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">WIB</span>
                    <span class="w-px h-3.5 bg-slate-700"></span>
                    <span class="font-mono text-sm font-bold tracking-wider text-emerald-400">
                        {{ currentTimeStr || '--:--:--' }}
                    </span>
                </div>

                <!-- Tombol Atur Shift / Libur Cepat untuk Admin/HRD -->
                <button
                    v-if="can('attendance_btn_set_shift_holiday') || can('attendances_manage_shifts') || can('work_schedules_manage')"
                    type="button"
                    @click="openShiftModal"
                    class="hidden lg:flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-blue-300 border border-slate-700/80 transition cursor-pointer active:scale-95 shadow-2xs"
                    title="Ubah Shift Kerja atau Tetapkan Libur Hari Ini"
                >
                    <span>⚙️</span>
                    <span>Atur Shift / Libur</span>
                </button>

                <!-- Link ke Halaman Absensi Lengkap -->
                <Link
                    v-if="can('attendance_recap_link')"
                    :href="isSpecialTeam ? `/absen-khusus?team_id=${specialTeam?.id}` : '/attendances'"
                    class="text-xs font-bold text-blue-400 hover:text-blue-300 hover:underline flex items-center gap-1 transition"
                >
                    <span>{{ isSpecialTeam ? 'Portal Absen Khusus' : 'Rekap Lengkap' }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </Link>
            </div>
        </div>

        <!-- MAIN CARD BODY (3 COLUMNS) -->
        <div class="p-5 sm:p-6 grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">
            <!-- COL 1: KARYAWAN & STATUS (5 COLS) -->
            <div class="lg:col-span-5 space-y-3.5">
                <div class="flex items-center gap-3.5">
                    <div
                        :class="[
                            isSpecialTeam
                                ? 'bg-gradient-to-tr from-purple-700 via-indigo-600 to-purple-800 text-white shadow-purple-500/20'
                                : 'bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-blue-500/20',
                            'w-13 h-13 rounded-2xl font-black text-xl flex items-center justify-center shrink-0 shadow-md'
                        ]"
                    >
                        {{ (currentEmployee?.name || 'K').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black text-slate-900 tracking-tight truncate uppercase">
                                {{ currentEmployee?.name || 'Karyawan APM' }}
                            </h3>
                            <span v-if="currentEmployee?.iak_number" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                {{ currentEmployee.iak_number }}
                            </span>
                            <span v-if="isSpecialTeam" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200">
                                {{ specialTeam?.name || 'Tim Khusus' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500 mt-0.5">
                            <span class="font-semibold text-slate-700">{{ currentEmployee?.position || 'Staf Operasional' }}</span>
                            <span class="text-slate-300">•</span>
                            <span class="text-slate-500 truncate">{{ currentEmployee?.area?.name || currentEmployee?.branch || 'Kantor Pusat' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Switcher Karyawan untuk Admin/HRD -->
                <div v-if="allEmployees && allEmployees.length > 1" class="pt-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-semibold text-slate-400 shrink-0">Ganti Staf:</span>
                        <select
                            :value="currentEmployee?.id"
                            @change="switchEmployee($event.target.value)"
                            class="text-xs font-semibold text-slate-700 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer w-full transition"
                        >
                            <option v-for="emp in allEmployees" :key="emp.id" :value="emp.id">
                                {{ emp.name }} — {{ emp.position || 'Staf' }} {{ emp.is_special_team ? `[⭐ ${emp.special_team_name || 'Tim Khusus'}]` : '' }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- ═══ BLOK INFORMASI SHIFT, JAM KERJA & KETERANGAN LIBUR ═══ -->
                <div class="p-3.5 rounded-2xl border bg-slate-50/90 dark:bg-slate-800/60 border-slate-200/90 dark:border-slate-700/70 shadow-2xs space-y-2">
                    <!-- Baris Atas: Shift & Jam Kerja + Badge Status -->
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2 min-w-0">
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center text-sm font-bold shrink-0 shadow-2xs"
                                :class="isDayOff
                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200'
                                    : (isSpecialTeam
                                        ? 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border border-purple-200'
                                        : 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-200')"
                            >
                                {{ isDayOff ? '🏖️' : (isSpecialTeam ? '⚡' : '⏱️') }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500">
                                    Shift & Jam Kerja
                                </div>
                                <div class="text-xs font-black text-slate-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                    <span class="truncate">
                                        {{ isSpecialTeam ? specialTeam?.name : (isDayOff ? 'Hari Libur (OFF)' : (shiftToday?.name || 'Shift 1-Kolektor')) }}
                                    </span>
                                    <span
                                        v-if="!isDayOff"
                                        class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-white dark:bg-slate-800 text-blue-700 dark:text-blue-300 border border-blue-200/80 dark:border-blue-800/80 shadow-2xs"
                                    >
                                        {{ isSpecialTeam ? formatSpecialTeamTime(specialTeam) : (shiftToday ? formatShiftTime(shiftToday) : '08:35 - 17:10') }} WIB
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge (Libur vs Hari Kerja) -->
                        <div class="shrink-0">
                            <span
                                v-if="isDayOff"
                                class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 flex items-center gap-1 shadow-2xs"
                            >
                                <span>🏖️</span>
                                <span>LIBUR (OFF)</span>
                            </span>
                            <span
                                v-else-if="isSpecialTeam"
                                class="px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 flex items-center gap-1 shadow-2xs"
                            >
                                <span>⚡</span>
                                <span>TIM KHUSUS</span>
                            </span>
                            <span
                                v-else
                                class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1 shadow-2xs"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>HARI KERJA</span>
                            </span>
                        </div>
                    </div>

                    <!-- Baris Bawah: Keterangan Libur / Detail Jam Kerja & Tombol Ganti -->
                    <div class="pt-2 border-t border-slate-200/70 dark:border-slate-700/70 flex items-center justify-between text-xs gap-2">
                        <div class="flex items-center gap-1.5 truncate text-[11px]">
                            <span class="text-slate-400 dark:text-slate-500 font-bold shrink-0">Keterangan:</span>
                            <span
                                v-if="isDayOff"
                                class="text-rose-600 dark:text-rose-400 font-bold truncate"
                                :title="dayOffReason || 'Jadwal Libur Rutin Mingguan / Day Off'"
                            >
                                {{ dayOffReason || 'Jadwal Libur Rutin Mingguan / Day Off' }}
                            </span>
                            <span
                                v-else
                                class="text-slate-600 dark:text-slate-300 truncate"
                            >
                                Toleransi: <strong class="text-slate-800 dark:text-white font-mono">{{ shiftToday?.tolerance_minutes || 15 }}m</strong>
                                <span class="text-slate-300 dark:text-slate-600 mx-1">•</span>
                                Batas Masuk: <strong class="text-slate-800 dark:text-white font-mono">{{ maxClockInTime }} WIB</strong>
                            </span>
                        </div>

                        <!-- Tombol Ubah Shift / Libur -->
                        <button
                            v-if="can('attendance_btn_set_shift_holiday') || can('attendances_manage_shifts') || can('work_schedules_manage')"
                            type="button"
                            @click="openShiftModal"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-950/60 border border-slate-200 dark:border-slate-700 hover:border-blue-300 transition shrink-0 flex items-center gap-1 shadow-2xs cursor-pointer active:scale-95"
                            title="Atur Shift Kerja atau Tetapkan Libur Hari Ini"
                        >
                            <span>⚙️</span>
                            <span>Atur Shift / Libur</span>
                        </button>
                    </div>
                </div>

                <!-- Status Banner Kehadiran Hari Ini -->
                <div
                    :class="[
                        statusBannerConfig.bgColor,
                        statusBannerConfig.borderColor,
                        'p-3 rounded-2xl border flex items-center gap-3 transition-colors'
                    ]"
                >
                    <div :class="[statusBannerConfig.iconBg, 'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-base font-bold']">
                        {{ statusBannerConfig.icon }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div :class="[statusBannerConfig.textColor, 'text-xs font-bold leading-tight']">
                            {{ statusBannerConfig.title }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                            {{ statusBannerConfig.subtitle }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- COL 2: MINI STATS MASUK & PULANG (4 COLS) -->
            <div class="lg:col-span-4 grid grid-cols-2 gap-3">
                <!-- Card Jam Masuk -->
                <div
                    :class="[
                        todayAttendance?.clock_in
                            ? (isSpecialTeam ? 'bg-purple-50/70 border-purple-200 text-purple-900' : 'bg-emerald-50/70 border-emerald-200 text-emerald-900')
                            : 'bg-slate-50 border-slate-200/80 text-slate-700',
                        'p-3.5 rounded-2xl border shadow-2xs transition'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                        <span>Absen Masuk</span>
                        <span v-if="todayAttendance?.clock_in" class="w-2 h-2 rounded-full" :class="isSpecialTeam ? 'bg-purple-500' : 'bg-emerald-500'"></span>
                    </div>
                    <div class="text-lg font-black mt-1 font-mono tracking-tight">
                        {{ todayAttendance?.clock_in ? todayAttendance.clock_in.slice(0, 5) : '--:--' }}
                    </div>
                    <div class="mt-1 flex items-center gap-1">
                        <span
                            v-if="todayAttendance?.status === 'late'"
                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700"
                        >
                            Telat {{ todayAttendance.late_minutes }}m
                        </span>
                        <span
                            v-else-if="todayAttendance?.clock_in"
                            :class="[
                                isSpecialTeam ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-700',
                                'px-1.5 py-0.5 rounded text-[10px] font-bold'
                            ]"
                        >
                            Tepat Waktu
                        </span>
                        <span v-else class="text-[11px] text-slate-400">
                            Belum Absen
                        </span>
                    </div>
                </div>

                <!-- Card Jam Pulang -->
                <div
                    :class="[
                        todayAttendance?.clock_out
                            ? 'bg-blue-50/70 border-blue-200 text-blue-900'
                            : 'bg-slate-50 border-slate-200/80 text-slate-700',
                        'p-3.5 rounded-2xl border shadow-2xs transition'
                    ]"
                >
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 flex items-center justify-between">
                        <span>Absen Pulang</span>
                        <span v-if="todayAttendance?.clock_out" class="w-2 h-2 rounded-full bg-blue-500"></span>
                    </div>
                    <div class="text-lg font-black mt-1 font-mono tracking-tight">
                        {{ todayAttendance?.clock_out ? todayAttendance.clock_out.slice(0, 5) : '--:--' }}
                    </div>
                    <div class="mt-1 flex items-center gap-1">
                        <span
                            v-if="todayAttendance?.clock_out"
                            class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700"
                        >
                            Selesai Kerja
                        </span>
                        <span v-else-if="todayAttendance?.clock_in" class="text-[11px] text-amber-600 font-semibold">
                            Sedang Bertugas
                        </span>
                        <span v-else class="text-[11px] text-slate-400">
                            Belum Pulang
                        </span>
                    </div>
                </div>

                <!-- Geofence / Mode Lapangan Indicator -->
                <div v-if="isSpecialTeam" class="col-span-2 px-3 py-2 bg-purple-50/80 rounded-xl border border-purple-200/70 flex items-center justify-between text-[11px] text-purple-900">
                    <div class="flex items-center gap-1.5 truncate font-medium">
                        <span>🚀</span>
                        <span class="truncate">Absen Lapangan: {{ specialTeam?.name }}</span>
                    </div>
                    <span class="font-black text-purple-700 shrink-0">Bebas Radius Kantor</span>
                </div>
                <div v-else class="col-span-2 px-3 py-2 bg-slate-50/80 rounded-xl border border-slate-200/60 flex items-center justify-between text-[11px] text-slate-600">
                    <div class="flex items-center gap-1.5 truncate">
                        <span>📍</span>
                        <span class="font-medium truncate">{{ officeLocation?.name || 'Kantor Cabang Terdaftar' }}</span>
                    </div>
                    <span class="font-bold text-slate-500 shrink-0">Radius: {{ officeLocation?.radius_meters || 200 }}m</span>
                </div>
            </div>

            <!-- COL 3: ACTION CTA BUTTON (3 COLS) -->
            <div class="lg:col-span-3 flex flex-col justify-center space-y-2">
                <!-- Case 1: Belum Masuk -->
                <template v-if="!todayAttendance?.clock_in">
                    <button
                        type="button"
                        @click="openModal('in')"
                        :disabled="!currentEmployee"
                        :class="[
                            isSpecialTeam
                                ? 'bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 hover:from-purple-700 hover:to-indigo-700 shadow-purple-500/25'
                                : (isDayOff
                                    ? 'bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-black shadow-slate-500/25'
                                    : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/25'),
                            'w-full py-3.5 px-4 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 shadow-lg active:scale-95 transition-all cursor-pointer'
                        ]"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span>{{ isSpecialTeam ? 'Absen Masuk Tim Khusus' : (isDayOff ? 'Absen Masuk (Piket Libur)' : 'Absen Masuk Sekarang') }}</span>
                    </button>
                    <p class="text-[11px] text-center text-slate-400 font-medium">
                        {{ isSpecialTeam
                            ? 'Verifikasi Wajah (Bebas Radius Kantor)'
                            : (isDayOff
                                ? 'Hari ini libur terjadwal. Absen jika bertugas lembur/piket.'
                                : 'Verifikasi Kamera Selfie & GPS') }}
                    </p>
                </template>

                <!-- Case 2: Sudah Masuk, Belum Pulang -->
                <template v-else-if="!todayAttendance?.clock_out">
                    <button
                        type="button"
                        @click="openModal('out')"
                        :class="[
                            isSpecialTeam
                                ? 'bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 shadow-teal-500/25'
                                : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-emerald-500/25',
                            'w-full py-3.5 px-4 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 shadow-lg active:scale-95 transition-all cursor-pointer'
                        ]"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>{{ isSpecialTeam ? 'Absen Pulang Tim Khusus' : 'Absen Pulang Sekarang' }}</span>
                    </button>
                    <p class="text-[11px] text-center text-slate-400 font-medium">
                        Selesaikan tugas harian Anda
                    </p>
                </template>

                <!-- Case 3: Presensi Lengkap Hari Ini -->
                <template v-else>
                    <div class="w-full py-3 px-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-center">
                        <div class="text-xs font-bold text-emerald-800 flex items-center justify-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Presensi Hari Ini Selesai
                        </div>
                        <p class="text-[11px] text-emerald-600 mt-0.5">
                            Terima kasih atas kerja keras Anda!
                        </p>
                    </div>
                </template>

                <!-- Ringkasan Karyawan Hari Ini (Summary Pill) -->
                <div v-if="attendanceSummary" class="pt-1 text-center">
                    <span class="text-[11px] font-semibold text-slate-500">
                        {{ isSpecialTeam ? `Anggota ${specialTeam?.name || 'Tim'}:` : 'Kehadiran Staf:' }}
                        <strong class="text-slate-800">{{ attendanceSummary.present_today || 0 }}/{{ attendanceSummary.total_employees || 0 }}</strong> Hadir
                    </span>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL INTERAKTIF: KAMERA SELFIE & VERIFIKASI PRESENSI         -->
        <!-- ============================================================== -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity">
            <div class="bg-white rounded-3xl max-w-lg w-full p-5 sm:p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto border border-slate-100">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div
                            :class="[
                                isSpecialTeam
                                    ? (modalType === 'in' ? 'bg-purple-50 text-purple-600 border-purple-200' : 'bg-teal-50 text-teal-600 border-teal-200')
                                    : (modalType === 'in' ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-emerald-50 text-emerald-600 border-emerald-200'),
                                'w-9 h-9 rounded-xl border flex items-center justify-center text-lg'
                            ]"
                        >
                            {{ modalType === 'in' ? '📸' : '🚪' }}
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-900 tracking-tight">
                                {{ isSpecialTeam
                                    ? (modalType === 'in' ? 'Verifikasi Absen Masuk Tim Khusus' : 'Verifikasi Absen Pulang Tim Khusus')
                                    : (modalType === 'in' ? 'Verifikasi Absen Masuk' : 'Verifikasi Absen Pulang')
                                }}
                            </h4>
                            <p class="text-[11px] text-slate-500 font-medium">
                                {{ currentEmployee?.name }} • {{ isSpecialTeam ? `${specialTeam?.name} (Bebas Radius)` : (shiftToday?.name || 'Shift Reguler') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeModal"
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center text-sm font-bold transition"
                    >
                        ✕
                    </button>
                </div>

                <!-- Ringkasan Shift, Jam Kerja & Keterangan Hari Ini di Modal -->
                <div class="p-3 rounded-2xl border bg-slate-50 dark:bg-slate-800/80 border-slate-200/80 dark:border-slate-700/80 flex items-center justify-between text-xs gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="text-base shrink-0">{{ isDayOff ? '🏖️' : '⏱️' }}</span>
                        <div class="min-w-0 truncate">
                            <span class="font-extrabold text-slate-800 dark:text-slate-200">
                                {{ isSpecialTeam ? specialTeam?.name : (isDayOff ? 'Hari Libur (OFF)' : (shiftToday?.name || 'Shift Standar')) }}
                            </span>
                            <span v-if="!isDayOff" class="text-blue-600 dark:text-blue-400 font-mono text-[11px] font-bold ml-1">
                                ({{ isSpecialTeam ? formatSpecialTeamTime(specialTeam) : (shiftToday ? formatShiftTime(shiftToday) : '08:00 - 17:00') }} WIB)
                            </span>
                        </div>
                    </div>
                    <span
                        v-if="isDayOff"
                        class="px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-100 text-rose-700 border border-rose-200 shrink-0"
                    >
                        {{ dayOffReason || 'Piket / Lembur Libur' }}
                    </span>
                    <span
                        v-else
                        class="text-[11px] text-slate-500 dark:text-slate-400 shrink-0"
                    >
                        Toleransi: <strong class="text-slate-800 dark:text-white">{{ shiftToday?.tolerance_minutes || 15 }}m</strong>
                    </span>
                </div>

                <!-- Penjelasan Tim Khusus vs Pilihan WFO/Dinas Reguler -->
                <div v-if="isSpecialTeam" class="p-3 bg-purple-50/80 rounded-2xl border border-purple-200/80 text-xs text-purple-900 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-purple-800">
                        <span>⭐</span>
                        <span>Absensi Grup: {{ specialTeam?.name || 'TIM KHUSUS' }}</span>
                    </div>
                    <p class="text-[11px] text-purple-700 leading-relaxed">
                        Presensi dilakukan langsung dari titik operasional/lapangan tanpa batas radius kantor cabang. Koordinat GPS dan foto selfie akan dicatat secara realtime.
                    </p>
                    <div class="pt-1.5 space-y-1">
                        <label class="text-[11px] font-bold text-purple-900">Lokasi / Titik Penugasan (Opsional):</label>
                        <input
                            type="text"
                            v-model="dutyLocation"
                            placeholder="Contoh: Titik Operasi Serbu / Lapangan / Rumah"
                            class="w-full text-xs rounded-xl border-purple-200 focus:border-purple-500 focus:ring-purple-500/20 bg-white"
                        />
                    </div>
                </div>

                <!-- Tipe Kehadiran (Hanya saat Clock In Reguler dan diizinkan oleh Role Akses) -->
                <div v-else-if="modalType === 'in' && can('attendance_select_type')" class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-700">Tipe Kehadiran Hari Ini:</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="attendanceType = 'wfo'"
                            :class="[
                                attendanceType === 'wfo'
                                    ? 'bg-blue-50 border-blue-500 text-blue-700 ring-2 ring-blue-500/20 font-bold'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                'p-2.5 rounded-xl border text-left text-xs transition flex items-center gap-2'
                            ]"
                        >
                            <span class="text-base">🏢</span>
                            <div>
                                <div class="font-bold">Di Kantor (WFO)</div>
                                <div class="text-[10px] opacity-75">Validasi Geofence</div>
                            </div>
                        </button>

                        <button
                            type="button"
                            @click="attendanceType = 'dinas_luar'"
                            :class="[
                                attendanceType === 'dinas_luar'
                                    ? 'bg-indigo-50 border-indigo-500 text-indigo-700 ring-2 ring-indigo-500/20 font-bold'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                'p-2.5 rounded-xl border text-left text-xs transition flex items-center gap-2'
                            ]"
                        >
                            <span class="text-base">🚗</span>
                            <div>
                                <div class="font-bold">Dinas Lapangan</div>
                                <div class="text-[10px] opacity-75">Tugas Luar Kantor</div>
                            </div>
                        </button>
                    </div>

                    <!-- Input Lokasi Dinas Lapangan -->
                    <div v-if="attendanceType === 'dinas_luar'" class="pt-1.5 space-y-1">
                        <label class="text-[11px] font-bold text-indigo-700">Lokasi / Tugas Lapangan:</label>
                        <input
                            type="text"
                            v-model="dutyLocation"
                            placeholder="Contoh: Perbaikan ODP-02 / Penanganan Gangguan Pelanggan"
                            class="w-full text-xs rounded-xl border-indigo-200 focus:border-indigo-500 focus:ring-indigo-500/20"
                        />
                    </div>
                </div>

                <!-- Live Camera Feed & Snapshot Preview -->
                <div class="space-y-2.5">
                    <div class="relative w-full aspect-4/3 bg-slate-950 rounded-2xl overflow-hidden border border-slate-300 shadow-inner flex items-center justify-center">
                        <!-- Live Video -->
                        <video
                            v-show="isCameraOpen && !capturedPhoto"
                            ref="videoRef"
                            autoplay
                            playsinline
                            class="w-full h-full object-cover transform -scale-x-100"
                        ></video>

                        <!-- AI Face Guide Overlay -->
                        <div
                            v-if="isCameraOpen && !capturedPhoto"
                            class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center"
                        >
                            <div class="relative w-44 h-56 border-2 border-emerald-400/80 rounded-full shadow-[0_0_20px_rgba(52,211,153,0.3)] flex items-center justify-center overflow-hidden">
                                <div class="absolute inset-x-0 h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent animate-bounce"></div>
                            </div>
                            <div class="mt-2.5 px-3 py-1 bg-black/60 backdrop-blur-md rounded-full text-[10px] font-bold text-white tracking-wide border border-white/20">
                                Posisikan Wajah di Dalam Bingkai
                            </div>
                        </div>

                        <!-- Captured Snapshot Preview -->
                        <img
                            v-if="capturedPhoto"
                            :src="capturedPhoto"
                            alt="Selfie Presensi"
                            class="w-full h-full object-cover"
                        />

                        <!-- Kamera Belum Aktif Placeholder -->
                        <div
                            v-if="!isCameraOpen && !capturedPhoto"
                            class="flex flex-col items-center justify-center text-slate-400 p-6 text-center space-y-3"
                        >
                            <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-blue-400 text-2xl shadow-md">
                                📷
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-200">Kamera Belum Aktif</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 max-w-xs">
                                    Izinkan akses browser untuk mengambil foto selfie verifikasi.
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="startCamera"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-blue-500/20"
                            >
                                Aktifkan Kamera
                            </button>
                        </div>
                    </div>

                    <!-- Camera Action Buttons -->
                    <div class="flex items-center justify-between gap-2">
                        <button
                            v-if="isCameraOpen && !capturedPhoto"
                            type="button"
                            @click="switchCameraFacing"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-[11px] font-bold transition flex items-center gap-1"
                        >
                            🔄 Putar Kamera
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="isCameraOpen && !capturedPhoto"
                                type="button"
                                @click="captureSnapshot"
                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition flex items-center gap-1.5 shadow-md shadow-emerald-500/25 active:scale-95"
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
                                🔄 Foto Ulang
                            </button>
                        </div>
                    </div>
                </div>

                <!-- GPS Status & Haversine Distance -->
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-1.5 text-xs">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full" :class="userCoordinates ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"></span>
                            <span class="font-bold text-slate-800">
                                {{ userCoordinates ? 'GPS Terhubung' : 'Mencari Koordinat GPS...' }}
                            </span>
                        </div>
                        <button
                            type="button"
                            @click="initGeolocation"
                            class="text-[11px] font-bold text-blue-600 hover:underline"
                        >
                            🔄 Refresh GPS
                        </button>
                    </div>

                    <div v-if="userCoordinates" class="text-slate-600 space-y-1 text-[11px]">
                        <div v-if="isSpecialTeam" class="text-purple-700 font-semibold flex items-center gap-1">
                            <span>✓</span>
                            <span>Mode Tim Khusus: Tanpa batasan radius kantor (GPS dicatat realtime).</span>
                        </div>
                        <div v-else-if="attendanceType === 'wfo'" class="flex items-center justify-between">
                            <span>Jarak ke Kantor Cabang:</span>
                            <span class="font-bold" :class="isWithinRadius ? 'text-emerald-600' : 'text-amber-600'">
                                {{ formatDistance(distanceToOffice) }}
                                ({{ isWithinRadius ? 'Dalam Radius' : 'Luar Radius' }})
                            </span>
                        </div>
                        <div v-else class="text-indigo-600 font-semibold">
                            ✓ Mode Tugas Luar: Radius kantor tidak diwajibkan.
                        </div>
                    </div>

                    <!-- Toleransi Lokasi PC / Bypass Radius (Khusus Reguler WFO) -->
                    <div v-if="!isSpecialTeam && (gpsAccuracy > 1000 || !isWithinRadius)" class="pt-1.5 border-t border-slate-200">
                        <label class="flex items-center gap-2 text-[11px] text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="bypassRadiusMode"
                                class="rounded text-blue-600 focus:ring-blue-500 border-slate-300"
                            />
                            <span>Aktifkan toleransi lokasi (Mode PC / Lapangan)</span>
                        </label>
                    </div>
                </div>

                <!-- Catatan Opsional -->
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-700">Catatan Presensi (Opsional):</label>
                    <input
                        type="text"
                        v-model="attendanceNotes"
                        placeholder="Tulis catatan jika ada..."
                        class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                    />
                </div>

                <!-- Submit Action Button -->
                <div class="pt-1">
                    <button
                        type="button"
                        @click="submitAttendance"
                        :disabled="!canSubmit || isSubmitting"
                        :class="[
                            canSubmit
                                ? (isSpecialTeam
                                    ? (modalType === 'in'
                                        ? 'bg-purple-600 hover:bg-purple-700 text-white shadow-lg shadow-purple-500/25 active:scale-95'
                                        : 'bg-teal-600 hover:bg-teal-700 text-white shadow-lg shadow-teal-500/25 active:scale-95')
                                    : (modalType === 'in'
                                        ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-500/25 active:scale-95'
                                        : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-500/25 active:scale-95'))
                                : 'bg-slate-200 text-slate-400 cursor-not-allowed',
                            'w-full py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2 transition cursor-pointer'
                        ]"
                    >
                        <span v-if="isSubmitting">Memproses Presensi...</span>
                        <span v-else-if="!capturedPhoto">Ambil Foto Selfie Dulu</span>
                        <span v-else>
                            {{ isSpecialTeam
                                ? (modalType === 'in' ? 'Kirim Absen Masuk Tim Khusus' : 'Kirim Absen Pulang Tim Khusus')
                                : (modalType === 'in' ? 'Kirim Absen Masuk' : 'Kirim Absen Pulang')
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden Canvas for Watermark Processing -->
        <canvas ref="canvasRef" class="hidden"></canvas>

        <!-- ============================================================== -->
        <!-- MODAL INTERAKTIF: UBAH SHIFT & ATUR KETERANGAN LIBUR           -->
        <!-- ============================================================== -->
        <div v-if="showShiftModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity">
            <div class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 border border-slate-100 dark:border-slate-800">
                <!-- Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200/60 flex items-center justify-center text-lg">
                            ⚙️
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-slate-900 dark:text-white">Atur Shift & Keterangan Libur</h4>
                            <p class="text-[11px] text-slate-400 font-medium">{{ currentEmployee?.name }} • Hari Ini</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showShiftModal = false"
                        class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-500 flex items-center justify-center text-sm font-bold transition"
                    >
                        ✕
                    </button>
                </div>

                <!-- Form Mode: Hari Kerja vs Hari Libur -->
                <div class="space-y-3.5">
                    <!-- Switch Mode Kerja vs Libur -->
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="shiftFormMode = 'work'"
                            :class="[
                                shiftFormMode === 'work'
                                    ? 'bg-blue-50 dark:bg-blue-950/50 border-blue-500 text-blue-700 dark:text-blue-300 ring-2 ring-blue-500/20 font-bold'
                                    : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400',
                                'p-3 rounded-2xl border text-left text-xs transition flex items-center gap-2.5 cursor-pointer'
                            ]"
                        >
                            <span class="text-lg">💼</span>
                            <div>
                                <div class="font-extrabold text-xs">Hari Kerja</div>
                                <div class="text-[10px] opacity-75">Tentukan Shift & Jam</div>
                            </div>
                        </button>

                        <button
                            type="button"
                            @click="shiftFormMode = 'off'"
                            :class="[
                                shiftFormMode === 'off'
                                    ? 'bg-rose-50 dark:bg-rose-950/50 border-rose-500 text-rose-700 dark:text-rose-300 ring-2 ring-rose-500/20 font-bold'
                                    : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400',
                                'p-3 rounded-2xl border text-left text-xs transition flex items-center gap-2.5 cursor-pointer'
                            ]"
                        >
                            <span class="text-lg">🏖️</span>
                            <div>
                                <div class="font-extrabold text-xs">Hari Libur (OFF)</div>
                                <div class="text-[10px] opacity-75">Set Keterangan Libur</div>
                            </div>
                        </button>
                    </div>

                    <!-- Mode Work: Dropdown Shift -->
                    <div v-if="shiftFormMode === 'work'" class="space-y-2">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Pilih Shift & Jam Kerja:</label>
                        <select
                            v-model="targetShiftId"
                            class="w-full text-xs font-bold rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 py-2.5 px-3 focus:ring-blue-500 cursor-pointer"
                        >
                            <option v-for="s in allShifts" :key="s.id" :value="s.id">
                                ⏱️ {{ s.name }} ({{ s.start_time }} - {{ s.end_time }} WIB)
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-400">
                            Pilih shift resmi yang berlaku untuk staf ini hari ini.
                        </p>
                    </div>

                    <!-- Mode Off / Libur: Pilihan Keterangan Libur -->
                    <div v-else class="space-y-2.5">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Keterangan Libur:</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button
                                v-for="opt in holidayPresetOptions"
                                :key="opt"
                                type="button"
                                @click="targetHolidayNotes = opt"
                                :class="[
                                    targetHolidayNotes === opt
                                        ? 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-200 border-rose-300 dark:border-rose-700 font-bold'
                                        : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100',
                                    'px-2.5 py-1.5 rounded-xl border text-[11px] text-left transition cursor-pointer'
                                ]"
                            >
                                {{ opt }}
                            </button>
                        </div>
                        <input
                            type="text"
                            v-model="targetHolidayNotes"
                            placeholder="Ketik keterangan libur lain (misal: Libur Khusus / Cuti)"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-rose-500"
                        />
                    </div>

                    <!-- Catatan Tambahan (Opsional) saat Mode Work -->
                    <div v-if="shiftFormMode === 'work'" class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 dark:text-slate-300">Catatan Jadwal (Opsional):</label>
                        <input
                            type="text"
                            v-model="targetHolidayNotes"
                            placeholder="Contoh: Shift Pengganti / Tugas Khusus"
                            class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 py-2 px-3 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button
                        type="button"
                        @click="showShiftModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="saveShiftSetting"
                        :disabled="isSavingShift"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 transition flex items-center gap-1.5 shadow-sm active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        <span v-if="isSavingShift" class="animate-spin">⏳</span>
                        <span>Simpan Jadwal Hari Ini</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    attendanceData: {
        type: Object,
        default: () => ({}),
    },
});

// Extract nested props cleanly
const currentEmployee = computed(() => props.attendanceData?.currentEmployee || null);
const isSpecialTeam = computed(() => Boolean(props.attendanceData?.isSpecialTeam));
const specialTeam = computed(() => props.attendanceData?.specialTeam || null);
const specialTeamMember = computed(() => props.attendanceData?.specialTeamMember || null);
const shiftToday = computed(() => props.attendanceData?.shiftToday || null);
const isDayOff = computed(() => Boolean(props.attendanceData?.isDayOff));
const dayOffReason = computed(() => props.attendanceData?.dayOffReason || '');
const todaySchedule = computed(() => props.attendanceData?.todaySchedule || null);
const todayAttendance = computed(() => props.attendanceData?.todayAttendance || null);
const officeLocation = computed(() => props.attendanceData?.officeLocation || null);
const allEmployees = computed(() => props.attendanceData?.allEmployees || []);
const allShifts = computed(() => props.attendanceData?.allShifts || []);
const attendanceSummary = computed(() => props.attendanceData?.attendanceSummary || null);

// State Modal Ubah Shift & Keterangan Libur
const showShiftModal = ref(false);
const shiftFormMode = ref('work'); // 'work' or 'off'
const targetShiftId = ref(null);
const targetHolidayNotes = ref('');
const isSavingShift = ref(false);

const holidayPresetOptions = [
    'Libur Rutin Mingguan',
    'Libur Nasional',
    'Cuti Tahunan',
    'Libur Pengganti',
    'Izin Tidak Masuk',
];

const openShiftModal = () => {
    if (!can('attendance_btn_set_shift_holiday') && !can('attendances_manage_shifts') && !can('work_schedules_manage')) return;
    if (isDayOff.value) {
        shiftFormMode.value = 'off';
        targetHolidayNotes.value = dayOffReason.value || 'Libur Rutin Mingguan';
        targetShiftId.value = allShifts.value?.[0]?.id || null;
    } else {
        shiftFormMode.value = 'work';
        targetShiftId.value = shiftToday.value?.id || allShifts.value?.[0]?.id || null;
        targetHolidayNotes.value = dayOffReason.value || '';
    }
    showShiftModal.value = true;
};

const saveShiftSetting = () => {
    if (!can('attendance_btn_set_shift_holiday') && !can('attendances_manage_shifts') && !can('work_schedules_manage')) return;
    if (!currentEmployee.value) return;
    isSavingShift.value = true;
    router.post(
        '/attendances/quick-assign-shift',
        {
            employee_id: currentEmployee.value.id,
            shift_id: shiftFormMode.value === 'off' ? 'OFF' : targetShiftId.value,
            status: shiftFormMode.value === 'off' ? 'off' : 'work',
            notes: targetHolidayNotes.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSavingShift.value = false;
                showShiftModal.value = false;
            },
        }
    );
};

const maxClockInTime = computed(() => {
    if (!shiftToday.value?.start_time) return '08:45';
    try {
        const parts = shiftToday.value.start_time.split(':');
        const h = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10);
        const tol = shiftToday.value.tolerance_minutes || 15;
        const totalMin = h * 60 + m + tol;
        const maxH = String(Math.floor(totalMin / 60) % 24).padStart(2, '0');
        const maxM = String(totalMin % 60).padStart(2, '0');
        return `${maxH}:${maxM}`;
    } catch (e) {
        return '08:45';
    }
});

// Clock state
const currentTimeStr = ref('');
let clockTimer = null;

const updateClock = () => {
    const d = new Date();
    currentTimeStr.value = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
};

const todayDateDisplay = computed(() => {
    const d = new Date();
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
});

const formatShiftTime = (shift) => {
    if (!shift) return '-';
    const s = shift.start_time ? shift.start_time.slice(0, 5) : '08:00';
    const e = shift.end_time ? shift.end_time.slice(0, 5) : '17:00';
    return `${s} - ${e}`;
};

const formatSpecialTeamTime = (team) => {
    if (!team) return '08:30 - 18:00 WIB';
    const s = team.start_time ? team.start_time.slice(0, 5) : '08:30';
    const e = team.end_time ? team.end_time.slice(0, 5) : '18:00';
    return `${s} - ${e} WIB`;
};

// Switch active employee for admin
const switchEmployee = (empId) => {
    if (!empId) return;
    router.get(
        window.location.pathname,
        { employee_id: empId },
        { preserveState: true, preserveScroll: true }
    );
};

// Status Banner Configuration
const statusBannerConfig = computed(() => {
    if (todayAttendance.value?.clock_in && todayAttendance.value?.clock_out) {
        return {
            bgColor: isSpecialTeam.value ? 'bg-purple-50/70' : 'bg-blue-50/70',
            borderColor: isSpecialTeam.value ? 'border-purple-200' : 'border-blue-200',
            iconBg: isSpecialTeam.value ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700',
            icon: '✓',
            textColor: isSpecialTeam.value ? 'text-purple-900' : 'text-blue-900',
            title: isSpecialTeam.value
                ? `Presensi ${specialTeam.value?.name || 'Tim Khusus'} Selesai`
                : 'Presensi Lengkap Hari Ini',
            subtitle: `Masuk: ${todayAttendance.value.clock_in} • Pulang: ${todayAttendance.value.clock_out}`,
        };
    }
    if (todayAttendance.value?.clock_in) {
        const isLate = todayAttendance.value.status === 'late';
        return {
            bgColor: isLate ? 'bg-amber-50/70' : (isSpecialTeam.value ? 'bg-purple-50/70' : 'bg-emerald-50/70'),
            borderColor: isLate ? 'border-amber-200' : (isSpecialTeam.value ? 'border-purple-200' : 'border-emerald-200'),
            iconBg: isLate ? 'bg-amber-100 text-amber-700' : (isSpecialTeam.value ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700'),
            icon: isLate ? '⚠️' : (isSpecialTeam.value ? '⚡' : '🟢'),
            textColor: isLate ? 'text-amber-900' : (isSpecialTeam.value ? 'text-purple-900' : 'text-emerald-900'),
            title: isLate
                ? (isSpecialTeam.value
                    ? `Bertugas ${specialTeam.value?.name || 'Tim Khusus'} (Telat ${todayAttendance.value.late_minutes}m)`
                    : `Sedang Bertugas (Telat ${todayAttendance.value.late_minutes}m)`)
                : (isSpecialTeam.value
                    ? `Bertugas Aktif: ${specialTeam.value?.name || 'Tim Khusus'}`
                    : 'Sedang Bertugas Aktif'),
            subtitle: `Tercatat masuk pada ${todayAttendance.value.clock_in} WIB`,
        };
    }
    // Jika hari ini LIBUR (OFF) dan belum absen
    if (isDayOff.value) {
        return {
            bgColor: 'bg-rose-50/70',
            borderColor: 'border-rose-200',
            iconBg: 'bg-rose-100 text-rose-700',
            icon: '🏖️',
            textColor: 'text-rose-900',
            title: 'Hari Ini Libur Terjadwal (Day Off)',
            subtitle: dayOffReason.value
                ? `Keterangan: ${dayOffReason.value}. Anda tidak diwajibkan melakukan absensi.`
                : 'Jadwal Libur Rutin Mingguan. Anda tidak diwajibkan melakukan absensi.',
        };
    }
    return {
        bgColor: isSpecialTeam.value ? 'bg-purple-50/50' : 'bg-slate-50',
        borderColor: isSpecialTeam.value ? 'border-purple-200/90' : 'border-slate-200',
        iconBg: isSpecialTeam.value ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700',
        icon: isSpecialTeam.value ? '⚡' : '⏱️',
        textColor: isSpecialTeam.value ? 'text-purple-950' : 'text-slate-800',
        title: isSpecialTeam.value
            ? `Belum Absen Masuk (${specialTeam.value?.name || 'Tim Khusus'})`
            : 'Belum Melakukan Absen Masuk',
        subtitle: isSpecialTeam.value
            ? 'Presensi tim khusus tanpa batasan radius kantor. Silakan klik tombol untuk absen.'
            : 'Silakan klik tombol di samping untuk absen masuk sekarang',
    };
});

// Modal & Form State
const showModal = ref(false);
const modalType = ref('in'); // 'in' or 'out'
const attendanceType = ref('wfo');
const dutyLocation = ref('');
const attendanceNotes = ref('');
const bypassRadiusMode = ref(false);
const isSubmitting = ref(false);

// Geolocation GPS
const userCoordinates = ref(null);
const gpsAccuracy = ref(20);
const distanceToOffice = ref(null);
const isWithinRadius = ref(true);

const initGeolocation = () => {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            userCoordinates.value = {
                latitude: pos.coords.latitude,
                longitude: pos.coords.longitude,
            };
            gpsAccuracy.value = pos.coords.accuracy || 20;
            calcDistance();
        },
        (err) => {
            console.warn('Geolocation warning:', err.message);
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
    );
};

const calcDistance = () => {
    if (isSpecialTeam.value || officeLocation.value?.without_radius) {
        isWithinRadius.value = true;
        distanceToOffice.value = 0;
        return;
    }
    if (!userCoordinates.value || !officeLocation.value?.latitude || !officeLocation.value?.longitude) {
        isWithinRadius.value = true;
        distanceToOffice.value = 15;
        return;
    }
    const lat1 = userCoordinates.value.latitude;
    const lon1 = userCoordinates.value.longitude;
    const lat2 = officeLocation.value.latitude;
    const lon2 = officeLocation.value.longitude;

    const R = 6371e3; // meters
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
    const allowed = officeLocation.value.radius_meters || 200;
    isWithinRadius.value = dist <= allowed;
};

const formatDistance = (m) => {
    if (m === null || m === undefined) return '-';
    if (m < 1000) return `${m} meter`;
    return `${(m / 1000).toFixed(1)} km`;
};

// Camera State
const isCameraOpen = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
const capturedPhoto = ref(null);
const cameraFacing = ref('user');
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
        await nextTick();
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
    if (cameraFacing.value === 'user') {
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
    } else {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    }

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
    const empName = currentEmployee.value?.name || 'Karyawan APM';
    const timeStr = `${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')} WIB`;
    const teamBadge = isSpecialTeam.value ? ` • ${specialTeam.value?.name || 'TIM KHUSUS'}` : '';
    ctx.fillText(`${empName}${teamBadge} • ${timeStr}`, 12, h - 24);

    ctx.font = '10px sans-serif';
    ctx.fillStyle = isSpecialTeam.value ? '#c084fc' : '#38bdf8';
    const geoStr = userCoordinates.value
        ? `GPS: ${userCoordinates.value.latitude.toFixed(6)}, ${userCoordinates.value.longitude.toFixed(6)} (${isSpecialTeam.value ? 'TIM KHUSUS' : attendanceType.value.toUpperCase()})`
        : `Mode: ${isSpecialTeam.value ? 'TIM KHUSUS' : attendanceType.value.toUpperCase()}`;
    ctx.fillText(geoStr, 12, h - 10);
    ctx.restore();
};

const retakePhoto = () => {
    capturedPhoto.value = null;
    startCamera();
};

const stopCamera = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach((t) => t.stop());
        mediaStream = null;
    }
    isCameraOpen.value = false;
};

// Modal Control
const openModal = async (type) => {
    modalType.value = type;
    capturedPhoto.value = null;
    attendanceNotes.value = '';
    dutyLocation.value = '';
    attendanceType.value = 'wfo';
    showModal.value = true;
    initGeolocation();
    await nextTick();
    startCamera();
};

const closeModal = () => {
    stopCamera();
    showModal.value = false;
    capturedPhoto.value = null;
};

// Submission Logic
const canSubmit = computed(() => {
    if (!capturedPhoto.value) return false;
    if (!currentEmployee.value) return false;
    if (modalType.value === 'in' && todayAttendance.value?.clock_in) return false;
    if (modalType.value === 'out' && (!todayAttendance.value?.clock_in || todayAttendance.value?.clock_out)) return false;
    return true;
});

const submitAttendance = () => {
    if (!canSubmit.value || isSubmitting.value) return;
    isSubmitting.value = true;

    // JIKA KARYAWAN TIM KHUSUS: Gunakan Route Absen Tim Khusus
    if (isSpecialTeam.value) {
        if (modalType.value === 'in') {
            router.post(
                route('special-attendances.clock-in'),
                {
                    special_team_id: specialTeam.value?.id,
                    employee_id: currentEmployee.value.id,
                    photo: capturedPhoto.value,
                    latitude: userCoordinates.value?.latitude || null,
                    longitude: userCoordinates.value?.longitude || null,
                    location_name: dutyLocation.value || (specialTeam.value?.name ? `Penugasan ${specialTeam.value.name}` : 'Dashboard Presensi'),
                    notes: attendanceNotes.value || null,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        isSubmitting.value = false;
                        closeModal();
                    },
                    onError: () => {
                        isSubmitting.value = false;
                    },
                }
            );
        } else {
            router.post(
                route('special-attendances.clock-out'),
                {
                    special_team_id: specialTeam.value?.id,
                    employee_id: currentEmployee.value.id,
                    photo: capturedPhoto.value,
                    latitude: userCoordinates.value?.latitude || null,
                    longitude: userCoordinates.value?.longitude || null,
                    location_name: dutyLocation.value || (specialTeam.value?.name ? `Penugasan ${specialTeam.value.name}` : 'Dashboard Presensi'),
                    notes: attendanceNotes.value || null,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        isSubmitting.value = false;
                        closeModal();
                    },
                    onError: () => {
                        isSubmitting.value = false;
                    },
                }
            );
        }
        return;
    }

    // PRESENSI REGULER STANDAR
    if (modalType.value === 'in') {
        router.post(
            route('attendances.clock-in'),
            {
                employee_id: currentEmployee.value.id,
                photo: capturedPhoto.value,
                shift_id: shiftToday.value?.id || undefined,
                attendance_type: can('attendance_select_type') ? attendanceType.value : 'wfo',
                duty_location: can('attendance_select_type') && attendanceType.value === 'dinas_luar' ? (dutyLocation.value || undefined) : undefined,
                latitude: userCoordinates.value?.latitude || null,
                longitude: userCoordinates.value?.longitude || null,
                distance: distanceToOffice.value || 0,
                notes: attendanceNotes.value || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isSubmitting.value = false;
                    closeModal();
                },
                onError: () => {
                    isSubmitting.value = false;
                },
            }
        );
    } else {
        router.post(
            route('attendances.clock-out'),
            {
                employee_id: currentEmployee.value.id,
                photo: capturedPhoto.value,
                latitude: userCoordinates.value?.latitude || null,
                longitude: userCoordinates.value?.longitude || null,
                distance: distanceToOffice.value || 0,
                notes: attendanceNotes.value || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isSubmitting.value = false;
                    closeModal();
                },
                onError: () => {
                    isSubmitting.value = false;
                },
            }
        );
    }
};

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 1000);
    initGeolocation();
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
    stopCamera();
});
</script>
