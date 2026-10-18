<template>
    <AppLayout title="Jadwal Kerja">
        <div class="space-y-5 print:space-y-2 print:m-0 print:p-0">
            <!-- 1. HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-800">Jadwal Kerja</h1>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Kelola roster & pola shift karyawan</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="openPatternModal = true"
                        type="button"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm active:scale-95"
                    >
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Buat Pola</span>
                    </button>

                    <button
                        @click="openGenerateScheduleModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-sm shadow-blue-500/20 active:scale-95"
                    >
                        <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        <span>Generate Jadwal</span>
                    </button>
                </div>
            </div>

            <!-- Flash Alert Messages -->
            <div v-if="$page.props.flash?.success" class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs shadow-xs print:hidden animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="text-base">✅</span>
                    <span class="font-bold">{{ $page.props.flash.success }}</span>
                </div>
                <button type="button" @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-800 p-1 font-bold">✕</button>
            </div>
            <div v-if="$page.props.flash?.error" class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between text-xs shadow-xs print:hidden animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="text-base">⚠️</span>
                    <span class="font-bold">{{ $page.props.flash.error }}</span>
                </div>
                <button type="button" @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-800 p-1 font-bold">✕</button>
            </div>

            <!-- 2. SUMMARY STAT CARDS (4 KARTU SEPERTI SCREENSHOT) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
                <!-- Card 1: Karyawan -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Karyawan</div>
                        <div class="text-2xl font-bold text-slate-800 leading-none mt-1">{{ stats.totalEmployees }}</div>
                    </div>
                </div>

                <!-- Card 2: Total Jadwal -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Jadwal</div>
                        <div class="text-2xl font-bold text-slate-800 leading-none mt-1">{{ stats.totalSchedules }}</div>
                    </div>
                </div>

                <!-- Card 3: Hari Libur -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Hari Libur</div>
                        <div class="text-2xl font-bold text-slate-800 leading-none mt-1">{{ stats.totalOff }}</div>
                    </div>
                </div>

                <!-- Card 4: Shift Terbanyak -->
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm flex flex-col justify-center">
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Shift Terbanyak</div>
                    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5">
                        <template v-if="Object.keys(stats.shiftCounts || {}).length > 0">
                            <span
                                v-for="(count, shiftName) in topShifts"
                                :key="shiftName"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100/90 text-slate-700 whitespace-nowrap border border-slate-200/60"
                            >
                                <span>{{ shiftName }}:</span>
                                <span class="font-bold text-blue-600">{{ count }}</span>
                            </span>
                        </template>
                        <span v-else class="text-xs text-slate-400 italic">Belum ada data shift</span>
                    </div>
                </div>
            </div>

            <!-- 3. CONTROL TOOLBAR (NAV BULAN, FILTER, SEARCH, SIMPAN, DLL) -->
            <div class="bg-white rounded-2xl p-3 border border-slate-100 shadow-sm space-y-3 print:hidden">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <!-- Bulan Selector & Navigasi -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="changeMonth(-1)"
                            type="button"
                            title="Bulan Sebelumnya"
                            class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        <div class="relative">
                            <input
                                type="month"
                                :value="currentMonthStr"
                                @change="onMonthInputChange($event)"
                                class="text-sm font-bold text-slate-800 bg-slate-50 border border-slate-200/80 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
                            />
                        </div>

                        <button
                            @click="changeMonth(1)"
                            type="button"
                            title="Bulan Berikutnya"
                            class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center text-slate-600 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <button
                            @click="goToToday"
                            type="button"
                            class="px-2.5 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition active:scale-95 ml-1"
                        >
                            Hari Ini
                        </button>
                    </div>

                    <!-- Filter Area/Wilayah, Jabatan, & Search -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter Area/Wilayah -->
                        <div class="relative min-w-[155px]">
                            <select
                                v-model="filterBranch"
                                @change="applyFilters"
                                class="w-full text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 pr-7 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
                            >
                                <option value="">🏢 Semua Area/Wilayah</option>
                                <option v-for="br in (areas || branches)" :key="br.id" :value="br.id">{{ br.name }}</option>
                            </select>
                        </div>

                        <!-- Filter Jabatan -->
                        <div class="relative min-w-[140px]">
                            <select
                                v-model="filterPosition"
                                @change="applyFilters"
                                class="w-full text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 pr-7 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
                            >
                                <option value="">💼 Semua Jabatan</option>
                                <option v-for="pos in positions" :key="pos.id" :value="pos.name">{{ pos.name }}</option>
                            </select>
                        </div>

                        <!-- Pencarian Karyawan -->
                        <div class="relative min-w-[160px]">
                            <input
                                v-model="searchQuery"
                                @input="applyFiltersDebounced"
                                type="text"
                                placeholder="🔍 Cari karyawan..."
                                class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-xl px-3 py-1.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            />
                        </div>
                    </div>

                    <!-- Tombol Aksi Kanan (Simpan, Print, Copy, Reset, dll) -->
                    <div class="flex flex-wrap items-center gap-2 ml-auto">
                        <!-- Tombol Simpan -->
                        <button
                            @click="saveBatchChanges"
                            :disabled="unsavedChangesCount === 0 || isSaving"
                            type="button"
                            :class="[
                                unsavedChangesCount > 0
                                    ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 animate-pulse'
                                    : 'bg-slate-800 text-white opacity-90 hover:opacity-100',
                                'inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold rounded-xl transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed'
                            ]"
                        >
                            <svg v-if="!isSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <svg v-else class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span>Simpan</span>
                            <span v-if="unsavedChangesCount > 0" class="ml-1 px-1.5 py-0.2 text-[10px] bg-white text-blue-700 rounded-full font-bold">
                                {{ unsavedChangesCount }}
                            </span>
                        </button>

                        <!-- Print -->
                        <button
                            @click="printSchedule"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Print</span>
                        </button>

                        <!-- Copy Bulan -->
                        <button
                            @click="openCopyModal = true"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
                            </svg>
                            <span>Copy Bulan</span>
                        </button>

                        <!-- Lanjutkan dari Bulan Lalu -->
                        <button
                            @click="continueFromPrevMonth"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50/70 border border-indigo-200/60 rounded-xl hover:bg-indigo-100/70 transition active:scale-95"
                        >
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span>Lanjutkan dari Bulan Lalu</span>
                        </button>

                        <!-- Mode Penghapus Cepat (Eraser Tool) -->
                        <button
                            @click="toggleEraserMode"
                            type="button"
                            :class="[
                                isEraserMode
                                    ? 'bg-rose-600 text-white shadow-md shadow-rose-500/25 ring-2 ring-rose-400 animate-pulse font-bold'
                                    : 'bg-white text-slate-700 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 font-medium',
                                'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs rounded-xl transition active:scale-95 shadow-2xs'
                            ]"
                            :title="isEraserMode ? 'Klik untuk mematikan mode penghapus' : 'Mode Penghapus Cepat: klik kotak mana saja di kalender untuk menghapusnya seketika'"
                        >
                            <span>🧹</span>
                            <span>{{ isEraserMode ? 'Mode Hapus AKTIF' : 'Mode Hapus' }}</span>
                        </button>

                        <!-- Hapus Jadwal Cerdas -->
                        <button
                            @click="openClearScheduleModal = true"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 hover:text-rose-700 rounded-xl transition active:scale-95 shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus Jadwal</span>
                        </button>
                    </div>
                </div>

                <!-- 4. SHIFT LEGEND BAR -->
                <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2 text-xs">
                    <span class="text-slate-400 font-semibold text-[11px] uppercase tracking-wider">Legend:</span>
                    
                    <!-- Shift Badges di Legend -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            v-for="shift in shifts"
                            :key="shift.id"
                            :class="[getShiftBadgeStyle(shift.color), 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border cursor-default']"
                        >
                            <svg class="w-3 h-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ shift.name }} ({{ shift.start_time }}-{{ shift.end_time }})</span>
                        </span>

                        <!-- Badge OFF -->
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-600 border border-rose-200">
                            OFF
                        </span>
                    </div>

                    <!-- Tombol Tambah Shift Cepat -->
                    <button
                        @click="openAddShiftModal = true"
                        type="button"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 bg-white border border-dashed border-slate-300 rounded-full hover:bg-slate-50 hover:text-blue-600 transition ml-1"
                    >
                        <span>+ Tambah Shift</span>
                    </button>
                </div>
            </div>

            <!-- 5. TABEL MATRIKS ROSTER KALENDER (SANGAT DETAIL SESUAI SCREENSHOT) -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden relative">
                <!-- Banner Mode Penghapus Aktif -->
                <div v-if="isEraserMode" class="bg-gradient-to-r from-rose-600 to-red-600 text-white px-4 py-2.5 flex flex-wrap items-center justify-between gap-2 text-xs print:hidden shadow-inner">
                    <div class="flex items-center gap-2">
                        <span class="text-base animate-bounce">🧹</span>
                        <span><strong class="font-bold uppercase tracking-wider">Mode Penghapus Aktif:</strong> Klik langsung pada cell/kotak jadwal mana saja untuk menghapusnya seketika tanpa popup. Jangan lupa klik <strong class="bg-white/20 px-1.5 py-0.5 rounded font-bold">Simpan</strong> setelah selesai.</span>
                    </div>
                    <button
                        @click="isEraserMode = false"
                        type="button"
                        class="px-2.5 py-1 bg-white text-rose-700 hover:bg-rose-50 rounded-lg font-bold text-[11px] shadow-xs transition"
                    >
                        ✕ Selesai Menghapus
                    </button>
                </div>

                <!-- Banner Unsaved Changes Notice -->
                <div v-if="unsavedChangesCount > 0" class="bg-amber-50 border-b border-amber-200 px-4 py-2 flex items-center justify-between text-xs text-amber-800 print:hidden">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        <span class="font-medium">Ada <b>{{ unsavedChangesCount }}</b> perubahan jadwal yang belum disimpan.</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="discardChanges" class="underline text-amber-700 hover:text-amber-900">Batalkan</button>
                        <button @click="saveBatchChanges" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold shadow-xs">Simpan Sekarang</button>
                    </div>
                </div>

                <div class="overflow-x-auto max-w-full">
                    <table class="w-full border-collapse text-center select-none">
                        <!-- HEADER TABEL (KARYAWAN + HARI/TANGGAL) -->
                        <thead>
                            <tr class="bg-slate-50/90 text-slate-600 text-[11px] font-semibold border-b border-slate-200">
                                <!-- Kolom Sticky Karyawan -->
                                <th class="sticky left-0 z-20 bg-slate-100/95 backdrop-blur-xs text-left px-4 py-3 min-w-[220px] max-w-[240px] uppercase tracking-wider text-slate-500 font-bold border-r border-slate-200 shadow-[2px_0_5px_rgba(0,0,0,0.03)]">
                                    Karyawan
                                </th>

                                <!-- Kolom Hari & Tanggal -->
                                <th
                                    v-for="day in days"
                                    :key="day.date"
                                    :class="[
                                        'px-1 py-2 min-w-[58px] border-r border-slate-200/70 last:border-r-0 group/col relative',
                                        day.is_sunday ? 'bg-rose-50/40 text-rose-600 font-bold' : '',
                                        day.is_today ? 'bg-blue-50/60 ring-1 ring-blue-400/30' : ''
                                    ]"
                                >
                                    <div :class="day.is_sunday ? 'text-rose-500 font-bold' : 'text-slate-500'">
                                        {{ day.day_name }}
                                    </div>
                                    <div :class="['text-xs font-bold mt-0.5', day.is_sunday ? 'text-rose-600' : 'text-slate-800']">
                                        {{ day.day }}
                                    </div>
                                    <!-- Tombol Cepat Hapus Tanggal Ini -->
                                    <button
                                        type="button"
                                        @click.stop="quickClearDate(day.date, day.day, day.day_name)"
                                        title="Kosongkan jadwal semua karyawan pada tanggal ini"
                                        class="opacity-0 group-hover/col:opacity-100 absolute top-1 right-0.5 w-3.5 h-3.5 rounded-full bg-rose-100 hover:bg-rose-600 text-rose-600 hover:text-white flex items-center justify-center text-[9px] font-bold transition shadow-2xs"
                                    >
                                        ✕
                                    </button>
                                </th>
                            </tr>
                        </thead>

                        <!-- BODY TABEL: LIST KARYAWAN -->
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr
                                v-for="emp in employees"
                                :key="emp.id"
                                class="hover:bg-blue-50/20 transition-colors group"
                            >
                                <!-- Sticky Karyawan Cell -->
                                <td class="sticky left-0 z-10 bg-white group-hover:bg-slate-50/90 text-left px-3.5 py-2.5 border-r border-slate-200 shadow-[2px_0_5px_rgba(0,0,0,0.03)] transition-colors">
                                    <div class="flex items-center justify-between gap-1">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-slate-800 leading-tight truncate" :title="emp.name">
                                                {{ emp.name }}
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-medium mt-0.5 truncate uppercase">
                                                <span>{{ emp.position || 'Staf' }}</span>
                                                <span v-if="emp.area?.name || emp.branch" class="text-slate-300 mx-1">•</span>
                                                <span v-if="emp.area?.name || emp.branch" class="text-slate-500 font-semibold">{{ emp.area?.name || emp.branch }}</span>
                                            </div>
                                        </div>
                                        <!-- Tombol Cepat Hapus Jadwal Karyawan Ini -->
                                        <button
                                            type="button"
                                            @click.stop="quickClearEmployee(emp)"
                                            title="Kosongkan jadwal karyawan ini di bulan ini"
                                            class="opacity-0 group-hover:opacity-100 p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition shrink-0"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>

                                <!-- Tanggal Cells (1 .. N) -->
                                <td
                                    v-for="day in days"
                                    :key="day.date"
                                    @click="handleCellClick(emp, day.date, $event)"
                                    @contextmenu.prevent="openContextMenu(emp, day.date, $event)"
                                    :class="[
                                        'px-0.5 py-1.5 border-r border-slate-100/80 last:border-r-0 cursor-pointer transition relative',
                                        isEraserMode ? 'hover:bg-rose-200/50 cursor-crosshair' : 'hover:bg-blue-100/40',
                                        day.is_sunday ? 'bg-rose-50/20' : '',
                                        hasChange(emp.id, day.date) ? 'ring-1 ring-inset ring-blue-500 bg-blue-50/50' : ''
                                    ]"
                                    :title="isEraserMode ? '🧹 Klik untuk menghapus jadwal ini' : getCellTooltip(emp, day.date)"
                                >
                                    <!-- Cell Pill Content -->
                                    <div class="flex items-center justify-center min-h-[30px]">
                                        <!-- Jika ada jadwal terisi -->
                                        <span
                                            v-if="getSchedule(emp.id, day.date)"
                                            :class="[
                                                getCellBadgeClass(getSchedule(emp.id, day.date)),
                                                'inline-flex items-center justify-center px-1.5 py-1 rounded-md text-[10px] font-bold tracking-tight text-center leading-none max-w-[54px] truncate shadow-2xs'
                                            ]"
                                        >
                                            {{ getScheduleLabel(getSchedule(emp.id, day.date)) }}
                                        </span>
                                        
                                        <!-- Jika cell kosong -->
                                        <span v-else class="text-slate-200 text-xs font-light hover:text-slate-400">
                                            -
                                        </span>
                                    </div>

                                    <!-- Dot indikator jika diedit lokal -->
                                    <div
                                        v-if="hasChange(emp.id, day.date)"
                                        class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-blue-600"
                                    ></div>
                                </td>
                            </tr>

                            <!-- Empty State jika tidak ada karyawan -->
                            <tr v-if="employees.length === 0">
                                <td :colspan="days.length + 1" class="py-12 text-center text-slate-400 italic">
                                    Tidak ada data karyawan ditemukan dengan filter saat ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 6. FLOATING QUICK CELL PICKER (POPOVER) -->
            <div
                v-if="activePopover"
                :style="{ top: activePopover.top + 'px', left: activePopover.left + 'px' }"
                class="fixed z-50 bg-white rounded-2xl shadow-xl border border-slate-200 p-2.5 w-64 text-left animate-in fade-in zoom-in-95 duration-100"
            >
                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                    <div>
                        <div class="text-[11px] font-bold text-slate-800 leading-tight truncate max-w-[170px]">
                            {{ activePopover.employee.name }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ formatLocalDate(activePopover.date) }}
                        </div>
                    </div>
                    <button
                        @click="closeCellPicker"
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-md"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-1 max-h-56 overflow-y-auto pr-1">
                    <!-- Option OFF (Libur) -->
                    <button
                        @click="selectShiftForActiveCell('OFF')"
                        type="button"
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 transition"
                    >
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span>OFF (Libur)</span>
                        </div>
                        <span class="text-[10px] text-rose-400">Hari Libur</span>
                    </button>

                    <!-- Shift Options -->
                    <button
                        v-for="shift in shifts"
                        :key="shift.id"
                        @click="selectShiftForActiveCell(shift.id)"
                        type="button"
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-100 transition"
                    >
                        <div class="flex items-center gap-2 truncate">
                            <span :class="[getDotColor(shift.color), 'w-2.5 h-2.5 rounded-full shrink-0']"></span>
                            <span class="font-semibold text-slate-800 truncate">{{ shift.name }}</span>
                        </div>
                        <span class="text-[10px] text-slate-400 shrink-0 ml-1">{{ shift.start_time }}-{{ shift.end_time }}</span>
                    </button>

                    <!-- Reset / Kosongkan Jadwal -->
                    <button
                        @click="selectShiftForActiveCell(null)"
                        type="button"
                        class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition border-t border-slate-100 mt-1"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>Kosongkan Jadwal</span>
                    </button>
                </div>
            </div>

            <!-- BACKDROP UNTUK CELL PICKER -->
            <div
                v-if="activePopover"
                @click="closeCellPicker"
                class="fixed inset-0 z-40 bg-black/5"
            ></div>

            <!-- 7. MODAL GENERATE JADWAL OTOMATIS (CANGGIH & CERDAS DENGAN PERIODE & FASE MULAI ABSEN) -->
            <div v-if="openGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openGenerateModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl flex flex-col max-h-[92vh] border border-slate-100">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-900">Generate Jadwal Otomatis</h3>
                                <p class="text-xs text-slate-500">Periode cerdas, tanggal mulai absen, dan proteksi jadwal</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="openGenerateModal = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitGenerateSchedule" class="space-y-4 overflow-y-auto pr-1 text-xs flex-1 py-1">
                        <!-- BAGIAN 1: PERIODE JADWAL & TANGGAL MULAI ABSEN -->
                        <div class="bg-blue-50/40 rounded-2xl p-3.5 border border-blue-100/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5 font-bold text-blue-950 text-xs">
                                    <span>📅</span>
                                    <span>Periode Jadwal & Tanggal Mulai Absen</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">
                                    {{ genDurationDays }} Hari Terpilih
                                </span>
                            </div>

                            <!-- Preset Cepat -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                                <button
                                    type="button"
                                    @click="applyPeriodPreset('full_month')"
                                    :class="[
                                        genPeriodPreset === 'full_month' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80',
                                        'px-2.5 py-1.5 rounded-xl text-[11px] transition text-center'
                                    ]"
                                >
                                    🗓️ 1 Bulan Penuh
                                </button>
                                <button
                                    type="button"
                                    @click="applyPeriodPreset('remaining_month')"
                                    :class="[
                                        genPeriodPreset === 'remaining_month' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80',
                                        'px-2.5 py-1.5 rounded-xl text-[11px] transition text-center'
                                    ]"
                                >
                                    ⏳ Sisa Bulan Ini
                                </button>
                                <button
                                    type="button"
                                    @click="applyPeriodPreset('payroll_cutoff')"
                                    :class="[
                                        genPeriodPreset === 'payroll_cutoff' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80',
                                        'px-2.5 py-1.5 rounded-xl text-[11px] transition text-center'
                                    ]"
                                >
                                    💳 Cut-Off (21-20)
                                </button>
                                <button
                                    type="button"
                                    @click="genPeriodPreset = 'custom'"
                                    :class="[
                                        genPeriodPreset === 'custom' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80',
                                        'px-2.5 py-1.5 rounded-xl text-[11px] transition text-center'
                                    ]"
                                >
                                    ✏️ Kustom
                                </button>
                            </div>

                            <!-- Input Tanggal Mulai Absen & Selesai -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-0.5">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                        Tanggal Mulai Absen <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="genStartDate"
                                        @change="genPeriodPreset = 'custom'"
                                        type="date"
                                        required
                                        class="w-full text-xs text-slate-800 bg-white border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs"
                                    />
                                    <p class="text-[10px] text-slate-500 mt-0.5">Mulai berlakunya giliran shift.</p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">
                                        Tanggal Selesai <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="genEndDate"
                                        @change="genPeriodPreset = 'custom'"
                                        type="date"
                                        required
                                        class="w-full text-xs text-slate-800 bg-white border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-2xs"
                                    />
                                    <p class="text-[10px] text-slate-500 mt-0.5">Batas akhir rotasi diterapkan.</p>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 2: PILIH KARYAWAN -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs">
                                    <span>👥</span>
                                    <span>Pilih Karyawan Target</span>
                                    <span class="text-slate-400 font-normal">({{ selectedGenEmployees.length }}/{{ filteredGenEmployees.length }})</span>
                                </div>
                                <button
                                    type="button"
                                    @click="toggleSelectAllEmployees"
                                    class="text-[11px] font-bold text-blue-600 hover:text-blue-700 hover:underline"
                                >
                                    {{ selectedGenEmployees.length === filteredGenEmployees.length ? 'Batal Semua' : 'Pilih Semua' }}
                                </button>
                            </div>

                            <!-- Filter Area & Jabatan -->
                            <div class="grid grid-cols-2 gap-2">
                                <select
                                    v-model="modalFilterBranch"
                                    class="w-full text-[11px] font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 cursor-pointer focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">🏢 Semua Area/Wilayah</option>
                                    <option v-for="br in (areas || branches)" :key="br.id" :value="br.id">{{ br.name }}</option>
                                </select>
                                <select
                                    v-model="modalFilterPosition"
                                    class="w-full text-[11px] font-medium text-slate-700 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 cursor-pointer focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">💼 Semua Jabatan</option>
                                    <option v-for="pos in positions" :key="pos.id" :value="pos.name">{{ pos.name }}</option>
                                </select>
                            </div>

                            <!-- List Karyawan -->
                            <div class="max-h-36 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1 text-xs bg-slate-50/30">
                                <label
                                    v-for="emp in filteredGenEmployees"
                                    :key="emp.id"
                                    class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white hover:shadow-2xs cursor-pointer transition"
                                >
                                    <input
                                        type="checkbox"
                                        :value="emp.id"
                                        v-model="selectedGenEmployees"
                                        class="rounded text-blue-600 focus:ring-blue-500"
                                    />
                                    <div class="truncate">
                                        <span class="font-semibold text-slate-800">{{ emp.name }}</span>
                                        <span class="text-[11px] text-slate-400 ml-1.5">
                                            ({{ emp.position || 'Staf' }}<span v-if="emp.area?.name || emp.branch"> • {{ emp.area?.name || emp.branch }}</span>)
                                        </span>
                                    </div>
                                </label>
                                <div v-if="filteredGenEmployees.length === 0" class="text-center py-4 text-slate-400 text-xs italic">
                                    Tidak ada karyawan yang cocok dengan filter.
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 3: POLA ROSTER & FASE AWAL MULAI ABSEN -->
                        <div class="space-y-3 bg-slate-50/70 p-3.5 rounded-2xl border border-slate-200/80">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">Pola Roster</label>
                                <select
                                    v-model="selectedPatternId"
                                    @change="genStartOffset = 0"
                                    class="w-full text-xs text-slate-800 bg-white border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                >
                                    <option value="6-1">Pola Standar (6 Kerja : 1 Libur Hari Minggu)</option>
                                    <option value="5-2">Pola 5 Kerja : 2 Libur (Sabtu & Minggu)</option>
                                    <option v-for="p in patterns" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>

                            <!-- Pilih Shift Kerja jika Pola 6-1 atau 5-2 -->
                            <div v-if="selectedPatternId === '6-1' || selectedPatternId === '5-2'">
                                <label class="block text-xs font-bold text-slate-800 mb-1">Pilih Shift Kerja Standar</label>
                                <select
                                    v-model="genDefaultShiftId"
                                    class="w-full text-xs text-slate-800 bg-white border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                >
                                    <option v-for="s in shifts" :key="s.id" :value="s.id">{{ s.name }} ({{ s.start_time }}-{{ s.end_time }})</option>
                                </select>
                            </div>

                            <!-- FASE AWAL SHIFT PADA TANGGAL MULAI ABSEN (CERDAS & CANGGIH) -->
                            <div class="pt-1 border-t border-slate-200/70">
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                        <span>🎯</span>
                                        <span>Fase Awal Shift (Pada Tanggal Mulai Absen)</span>
                                    </label>
                                    <span class="text-[10px] text-slate-500">Mulai rotasi dari shift ini</span>
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 mb-2">
                                    <button
                                        v-for="phase in currentPatternPhases"
                                        :key="phase.offset"
                                        type="button"
                                        @click="genStartOffset = phase.offset"
                                        :class="[
                                            genStartOffset === phase.offset
                                                ? 'bg-blue-600 text-white ring-2 ring-blue-400/40 shadow-xs font-bold'
                                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200',
                                            'px-2.5 py-1.5 rounded-xl text-left text-[11px] transition flex flex-col'
                                        ]"
                                    >
                                        <span class="text-[9px] opacity-75 font-semibold uppercase">{{ phase.label }}</span>
                                        <span class="truncate font-medium">{{ phase.shiftName }}</span>
                                    </button>
                                </div>

                                <!-- Flow Preview Sequence Pola -->
                                <div class="bg-white rounded-xl p-2 border border-slate-200 flex items-center gap-1.5 overflow-x-auto text-[11px]">
                                    <span class="text-[10px] font-bold text-slate-400 shrink-0 uppercase tracking-wider pl-1">Alur:</span>
                                    <div
                                        v-for="(phase, pIdx) in currentPatternPhases"
                                        :key="'flow_' + pIdx"
                                        class="flex items-center gap-1 shrink-0"
                                    >
                                        <span
                                            :class="[
                                                genStartOffset === phase.offset
                                                    ? 'bg-blue-600 text-white font-bold ring-2 ring-blue-300'
                                                    : phase.isOff
                                                        ? 'bg-rose-50 text-rose-600 border border-rose-200'
                                                        : 'bg-slate-100 text-slate-700 border border-slate-200',
                                                'px-2 py-0.5 rounded-lg text-[10px]'
                                            ]"
                                        >
                                            <span v-if="genStartOffset === phase.offset" class="mr-0.5">▶</span>
                                            {{ phase.shiftName }}
                                        </span>
                                        <span v-if="pIdx < currentPatternPhases.length - 1" class="text-slate-300 text-xs">➔</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Opsi Stagger Rotasi -->
                            <div class="flex items-start gap-2 pt-1.5">
                                <input
                                    id="stagger"
                                    type="checkbox"
                                    v-model="genStagger"
                                    class="rounded text-blue-600 focus:ring-blue-500 mt-0.5"
                                />
                                <label for="stagger" class="text-xs text-slate-700 select-none cursor-pointer leading-snug">
                                    <b class="text-slate-800">Rotasi Selang-Seling (Staggered):</b> Geser fase rotasi & hari libur antar teknisi agar tidak libur atau shift bersamaan.
                                </label>
                            </div>
                        </div>

                        <!-- BAGIAN 4: PROTEKSI OVERWRITE JADWAL -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                <span>🛡️</span>
                                <span>Mode Proteksi Penimpaan Jadwal</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <label
                                    :class="[
                                        genOverwriteMode === 'all'
                                            ? 'border-blue-600 bg-blue-50/50 text-blue-900 ring-2 ring-blue-500/20'
                                            : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex flex-col p-2.5 rounded-xl border cursor-pointer transition'
                                    ]"
                                >
                                    <div class="flex items-center gap-1.5 font-bold text-[11px]">
                                        <input type="radio" v-model="genOverwriteMode" value="all" class="text-blue-600 focus:ring-blue-500"/>
                                        <span>🔄 Timpa Semua</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 leading-tight">
                                        Perbarui seluruh jadwal yang sudah ada dalam rentang tanggal.
                                    </span>
                                </label>

                                <label
                                    :class="[
                                        genOverwriteMode === 'empty_only'
                                            ? 'border-blue-600 bg-blue-50/50 text-blue-900 ring-2 ring-blue-500/20'
                                            : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex flex-col p-2.5 rounded-xl border cursor-pointer transition'
                                    ]"
                                >
                                    <div class="flex items-center gap-1.5 font-bold text-[11px]">
                                        <input type="radio" v-model="genOverwriteMode" value="empty_only" class="text-blue-600 focus:ring-blue-500"/>
                                        <span>🛡️ Isi Kosong Saja</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 leading-tight">
                                        Hanya isi tanggal kosong. Jadwal yang sudah ada tidak akan diubah.
                                    </span>
                                </label>

                                <label
                                    :class="[
                                        genOverwriteMode === 'preserve_off'
                                            ? 'border-blue-600 bg-blue-50/50 text-blue-900 ring-2 ring-blue-500/20'
                                            : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex flex-col p-2.5 rounded-xl border cursor-pointer transition'
                                    ]"
                                >
                                    <div class="flex items-center gap-1.5 font-bold text-[11px]">
                                        <input type="radio" v-model="genOverwriteMode" value="preserve_off" class="text-blue-600 focus:ring-blue-500"/>
                                        <span>🏖️ Jaga Hari Libur</span>
                                    </div>
                                    <span class="text-[10px] text-slate-500 mt-1 leading-tight">
                                        Jadwal Libur (OFF) / cuti yang sudah ada tidak akan terhapus.
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- BAGIAN 5: ESTIMASI CERDAS CARD -->
                        <div class="bg-gradient-to-r from-slate-900 to-blue-950 text-white rounded-2xl p-3 shadow-sm flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-sm font-bold">
                                    ⚡
                                </div>
                                <div>
                                    <div class="text-[10px] text-blue-200 font-semibold uppercase tracking-wider">Ringkasan Estimasi Jadwal</div>
                                    <div class="text-xs font-bold flex items-center gap-2">
                                        <span>👥 {{ selectedGenEmployees.length }} Karyawan</span>
                                        <span>•</span>
                                        <span>📅 {{ genDurationDays }} Hari</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right pl-2 sm:border-l sm:border-white/10">
                                <div class="text-[10px] text-blue-200">Total Slot Dihasilkan</div>
                                <div class="text-base font-extrabold text-blue-300">~{{ genTotalSlotCount }} Jadwal</div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 shrink-0">
                            <button
                                type="button"
                                @click="openGenerateModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="selectedGenEmployees.length === 0 || genDurationDays <= 0 || isGenerating"
                                class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-md shadow-blue-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="isGenerating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                <span>{{ isGenerating ? 'Memproses Jadwal...' : 'Terapkan Jadwal Otomatis' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 8. MODAL BUAT POLA ROSTER -->
            <div v-if="openPatternModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openPatternModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800">Master Pola Shift</h3>
                                <p class="text-xs text-slate-500">Buat template urutan shift berulang</p>
                            </div>
                        </div>
                        <button @click="openPatternModal = false" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Form Buat Pola Baru -->
                    <form @submit.prevent="submitCreatePattern" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Pola</label>
                            <input
                                v-model="patternForm.name"
                                type="text"
                                placeholder="Contoh: Pola Rotasi Pagi-Siang-Malam-OFF"
                                required
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            />
                        </div>

                        <!-- Urutan Hari Pola -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700">Urutan Hari Perputaran</label>
                                <button
                                    type="button"
                                    @click="addDayToPattern"
                                    class="text-[11px] font-bold text-blue-600 hover:underline"
                                >
                                    + Tambah Hari
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-2 max-h-44 overflow-y-auto p-1">
                                <div
                                    v-for="(val, idx) in patternForm.days"
                                    :key="idx"
                                    class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-lg border border-slate-200 text-xs"
                                >
                                    <span class="font-bold text-slate-400 w-5">H{{ idx + 1 }}</span>
                                    <select
                                        v-model="patternForm.days[idx]"
                                        class="w-full text-xs bg-white border border-slate-200 rounded-md py-1 px-1.5"
                                    >
                                        <option value="OFF">🔴 OFF (Libur)</option>
                                        <option v-for="s in shifts" :key="s.id" :value="s.id">
                                            🟢 {{ s.name }}
                                        </option>
                                    </select>
                                    <button
                                        v-if="patternForm.days.length > 2"
                                        type="button"
                                        @click="removeDayFromPattern(idx)"
                                        class="text-rose-500 hover:text-rose-700 p-0.5"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openPatternModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Tutup
                            </button>
                            <button
                                type="submit"
                                :disabled="isSavingPattern"
                                class="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-md shadow-indigo-500/20"
                            >
                                {{ isSavingPattern ? 'Menyimpan...' : 'Simpan Pola' }}
                            </button>
                        </div>
                    </form>

                    <!-- Daftar Pola yang Tersimpan -->
                    <div v-if="patterns.length > 0" class="pt-3 border-t border-slate-100 space-y-2">
                        <div class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Pola Tersimpan:</div>
                        <div class="space-y-1.5 max-h-32 overflow-y-auto">
                            <div
                                v-for="p in patterns"
                                :key="p.id"
                                class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200 text-xs"
                            >
                                <div>
                                    <div class="font-bold text-slate-800">{{ p.name }}</div>
                                    <div class="text-[10px] text-slate-500">{{ (p.pattern_data || []).length }} Hari siklus</div>
                                </div>
                                <button
                                    @click="deletePattern(p.id)"
                                    class="text-rose-500 hover:text-rose-700 p-1"
                                    title="Hapus Pola"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 9. MODAL COPY BULAN -->
            <div v-if="openCopyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openCopyModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Salin Jadwal Bulan</h3>
                        <button @click="openCopyModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitCopyMonth" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Salin dari Bulan:</label>
                            <input
                                v-model="copySourceMonth"
                                type="month"
                                required
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ke Bulan Tujuan:</label>
                            <input
                                :value="currentMonthStr"
                                disabled
                                class="w-full text-xs text-slate-500 bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 font-bold cursor-not-allowed"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openCopyModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isCopying"
                                class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-md shadow-blue-500/20"
                            >
                                {{ isCopying ? 'Menyalin...' : 'Salin Jadwal' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 10. MODAL QUICK ADD SHIFT -->
            <div v-if="openAddShiftModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs" @click="openAddShiftModal = false"></div>
                <div class="relative bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800">Tambah Shift Baru</h3>
                        <button @click="openAddShiftModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitAddShift" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Shift</label>
                            <input
                                v-model="newShiftForm.name"
                                type="text"
                                placeholder="Contoh: Shift 3 (Malam)"
                                required
                                class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Masuk</label>
                                <input
                                    v-model="newShiftForm.start_time"
                                    type="time"
                                    required
                                    class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Jam Pulang</label>
                                <input
                                    v-model="newShiftForm.end_time"
                                    type="time"
                                    required
                                    class="w-full text-xs text-slate-800 border border-slate-200 rounded-xl px-3 py-2"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Warna Badge</label>
                            <div class="flex items-center gap-2">
                                <label
                                    v-for="color in ['blue', 'emerald', 'purple', 'amber', 'rose', 'cyan']"
                                    :key="color"
                                    :class="[
                                        getDotColor(color),
                                        'w-7 h-7 rounded-full cursor-pointer flex items-center justify-center transition',
                                        newShiftForm.color === color ? 'ring-2 ring-offset-2 ring-slate-800' : ''
                                    ]"
                                >
                                    <input type="radio" v-model="newShiftForm.color" :value="color" class="sr-only"/>
                                    <svg v-if="newShiftForm.color === color" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="openAddShiftModal = false"
                                class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isSavingShift"
                                class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition shadow-md shadow-blue-500/20"
                            >
                                {{ isSavingShift ? 'Menyimpan...' : 'Simpan Shift' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ════ MODAL HAPUS / KOSONGKAN JADWAL CANGGIH ════ -->
            <div
                v-if="openClearScheduleModal"
                class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                @click.self="openClearScheduleModal = false"
            >
                <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 p-6 sm:p-7 max-h-[90vh] flex flex-col animate-in fade-in zoom-in-95 duration-150">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold shrink-0">
                                🗑️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Hapus / Kosongkan Jadwal Kerja</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Bulan: <strong class="text-slate-800">{{ monthName }}</strong></p>
                            </div>
                        </div>
                        <button type="button" @click="openClearScheduleModal = false" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">✕</button>
                    </div>

                    <form @submit.prevent="submitClearSchedule" class="space-y-4 text-xs overflow-y-auto pr-1 flex-1">
                        <!-- Pilihan Lingkup / Scope -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih Lingkup Jadwal yang Ingin Dihapus:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label
                                    :class="[
                                        clearScope === 'all' ? 'border-rose-500 bg-rose-50/40 text-rose-900 ring-2 ring-rose-400/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition'
                                    ]"
                                >
                                    <input type="radio" v-model="clearScope" value="all" class="mt-0.5 text-rose-600 focus:ring-rose-500"/>
                                    <div>
                                        <div class="font-bold text-xs">🌐 Semua Karyawan</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-tight">Reset bersih seluruh jadwal 1 bulan penuh</div>
                                    </div>
                                </label>

                                <label
                                    :class="[
                                        clearScope === 'employee' ? 'border-rose-500 bg-rose-50/40 text-rose-900 ring-2 ring-rose-400/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition'
                                    ]"
                                >
                                    <input type="radio" v-model="clearScope" value="employee" class="mt-0.5 text-rose-600 focus:ring-rose-500"/>
                                    <div>
                                        <div class="font-bold text-xs">👤 Karyawan Tertentu</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-tight">Hanya hapus karyawan yang Anda pilih</div>
                                    </div>
                                </label>

                                <label
                                    :class="[
                                        clearScope === 'date_range' ? 'border-rose-500 bg-rose-50/40 text-rose-900 ring-2 ring-rose-400/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition'
                                    ]"
                                >
                                    <input type="radio" v-model="clearScope" value="date_range" class="mt-0.5 text-rose-600 focus:ring-rose-500"/>
                                    <div>
                                        <div class="font-bold text-xs">📅 Rentang Tanggal</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-tight">Hapus jadwal pada periode tanggal tertentu</div>
                                    </div>
                                </label>

                                <label
                                    :class="[
                                        clearScope === 'branch' ? 'border-rose-500 bg-rose-50/40 text-rose-900 ring-2 ring-rose-400/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition'
                                    ]"
                                >
                                    <input type="radio" v-model="clearScope" value="branch" class="mt-0.5 text-rose-600 focus:ring-rose-500"/>
                                    <div>
                                        <div class="font-bold text-xs">🏢 Cabang / Area</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-tight">Hanya hapus tim di wilayah/cabang tertentu</div>
                                    </div>
                                </label>

                                <label
                                    :class="[
                                        clearScope === 'shift' ? 'border-rose-500 bg-rose-50/40 text-rose-900 ring-2 ring-rose-400/20' : 'border-slate-200 hover:bg-slate-50 text-slate-700',
                                        'flex items-start gap-2.5 p-3 rounded-2xl border cursor-pointer transition col-span-1 sm:col-span-2'
                                    ]"
                                >
                                    <input type="radio" v-model="clearScope" value="shift" class="mt-0.5 text-rose-600 focus:ring-rose-500"/>
                                    <div>
                                        <div class="font-bold text-xs">🏷️ Jenis Shift Tertentu</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-tight">Hanya hapus jadwal yang menggunakan shift tertentu (misal: Shift 2-CS saja)</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Opsi Dinamis: Scope 'employee' -->
                        <div v-if="clearScope === 'employee'" class="space-y-2 p-3 bg-slate-50 rounded-2xl border border-slate-200 animate-fade-in">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-800">Pilih Karyawan:</label>
                                <button
                                    type="button"
                                    @click="toggleSelectAllClearEmployees"
                                    class="text-[11px] font-bold text-blue-600 hover:underline"
                                >
                                    {{ selectedClearEmployees.length === filteredClearEmployees.length ? 'Batal Semua' : 'Pilih Semua (' + filteredClearEmployees.length + ')' }}
                                </button>
                            </div>

                            <input
                                v-model="clearEmployeeSearch"
                                type="text"
                                placeholder="🔍 Filter nama karyawan..."
                                class="w-full text-xs px-3 py-1.5 bg-white border border-slate-200 rounded-xl"
                            />

                            <div class="max-h-44 overflow-y-auto space-y-1 pr-1 bg-white border border-slate-200 rounded-xl p-2">
                                <label
                                    v-for="emp in filteredClearEmployees"
                                    :key="emp.id"
                                    class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-slate-50 cursor-pointer"
                                >
                                    <input
                                        type="checkbox"
                                        :value="emp.id"
                                        v-model="selectedClearEmployees"
                                        class="rounded text-rose-600 focus:ring-rose-500"
                                    />
                                    <div class="truncate">
                                        <span class="font-semibold text-slate-800">{{ emp.name }}</span>
                                        <span class="text-[11px] text-slate-400 ml-1.5">
                                            ({{ emp.position || 'Staf' }}<span v-if="emp.area?.name || emp.branch"> • {{ emp.area?.name || emp.branch }}</span>)
                                        </span>
                                    </div>
                                </label>
                            </div>
                            <p class="text-[11px] text-rose-600 font-medium">
                                Terpilih: <strong>{{ selectedClearEmployees.length }}</strong> karyawan.
                            </p>
                        </div>

                        <!-- Opsi Dinamis: Scope 'date_range' -->
                        <div v-if="clearScope === 'date_range'" class="space-y-2 p-3 bg-slate-50 rounded-2xl border border-slate-200 animate-fade-in">
                            <label class="block font-bold text-slate-800">Tentukan Rentang Tanggal yang Ingin Dikosongkan:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="block text-[11px] text-slate-500 font-semibold mb-1">Dari Tanggal:</span>
                                    <input
                                        type="date"
                                        v-model="clearStartDate"
                                        required
                                        class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
                                    />
                                </div>
                                <div>
                                    <span class="block text-[11px] text-slate-500 font-semibold mb-1">Sampai Tanggal:</span>
                                    <input
                                        type="date"
                                        v-model="clearEndDate"
                                        required
                                        class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Opsi Dinamis: Scope 'branch' -->
                        <div v-if="clearScope === 'branch'" class="space-y-2 p-3 bg-slate-50 rounded-2xl border border-slate-200 animate-fade-in">
                            <label class="block font-bold text-slate-800">Pilih Cabang / Area:</label>
                            <select
                                v-model="clearBranch"
                                required
                                class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="" disabled>-- Pilih Cabang / Area --</option>
                                <option v-for="br in (areas || branches)" :key="br.id" :value="br.id">{{ br.name }}</option>
                            </select>
                        </div>

                        <!-- Opsi Dinamis: Scope 'shift' -->
                        <div v-if="clearScope === 'shift'" class="space-y-2 p-3 bg-slate-50 rounded-2xl border border-slate-200 animate-fade-in">
                            <label class="block font-bold text-slate-800">Pilih Shift yang Ingin Dihapus:</label>
                            <select
                                v-model="clearShiftId"
                                required
                                class="w-full text-xs px-3 py-2 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500"
                            >
                                <option value="" disabled>-- Pilih Shift --</option>
                                <option v-for="s in shifts" :key="s.id" :value="s.id">{{ s.name }} ({{ s.start_time }}-{{ s.end_time }})</option>
                                <option value="OFF">Hari Libur (OFF) Saja</option>
                            </select>
                        </div>

                        <!-- Warning Confirmation Note -->
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 flex items-start gap-2">
                            <span class="text-base shrink-0">⚠️</span>
                            <div class="text-[11px] leading-relaxed">
                                Tindakan ini akan <strong>menghapus/mengosongkan</strong> data jadwal kerja yang sesuai filter di atas dari database. Data yang sudah dihapus tidak dapat dibatalkan kembali.
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 shrink-0">
                            <button
                                type="button"
                                @click="openClearScheduleModal = false"
                                class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="isClearing || (clearScope === 'employee' && selectedClearEmployees.length === 0)"
                                class="px-5 py-2 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white font-bold text-xs rounded-xl shadow-md shadow-rose-500/20 transition active:scale-95 disabled:opacity-50 flex items-center gap-1.5"
                            >
                                <span v-if="isClearing" class="inline-block animate-spin">⏳</span>
                                <span>{{ isClearing ? 'Menghapus...' : 'Hapus Jadwal Sekarang' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ════ CONTEXT MENU KLIK KANAN PADA CELL JADWAL ════ -->
            <div
                v-if="contextMenu"
                :style="{ top: contextMenu.top + 'px', left: contextMenu.left + 'px' }"
                class="fixed z-50 bg-white rounded-2xl shadow-2xl border border-slate-200 p-1.5 w-60 text-left text-xs animate-in fade-in zoom-in-95 duration-100"
            >
                <div class="px-2.5 py-1.5 border-b border-slate-100 mb-1">
                    <div class="font-bold text-slate-800 truncate">{{ contextMenu.employee.name }}</div>
                    <div class="text-[10px] text-slate-400">{{ formatLocalDate(contextMenu.date) }}</div>
                </div>

                <button
                    type="button"
                    @click="contextActionClear"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-rose-600 hover:bg-rose-50 font-bold transition"
                >
                    <span class="text-sm">🗑️</span>
                    <span>Kosongkan / Hapus Jadwal Ini</span>
                </button>

                <button
                    type="button"
                    @click="contextActionSetOff"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-slate-700 hover:bg-slate-100 font-medium transition"
                >
                    <span class="text-sm">🏖️</span>
                    <span>Set Jadi Libur (OFF)</span>
                </button>

                <div class="my-1 border-t border-slate-100"></div>

                <div class="px-2.5 py-0.5 text-[10px] uppercase font-bold text-slate-400">Ganti Shift Cepat:</div>
                <div class="max-h-28 overflow-y-auto space-y-0.5 pr-0.5">
                    <button
                        v-for="s in shifts"
                        :key="s.id"
                        type="button"
                        @click="contextActionSetShift(s.id)"
                        class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-slate-700 hover:bg-slate-100 text-[11px] font-medium transition"
                    >
                        <span class="truncate">{{ s.name }}</span>
                        <span class="text-[10px] text-slate-400">{{ s.start_time }}</span>
                    </button>
                </div>

                <div class="my-1 border-t border-slate-100"></div>

                <button
                    type="button"
                    @click="contextActionClearMonth"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 rounded-xl text-slate-600 hover:text-rose-600 hover:bg-rose-50 text-[11px] font-medium transition"
                >
                    <span>👤</span>
                    <span>Kosongkan Jadwal {{ contextMenu.employee.name.split(' ')[0] }} (Bulan Ini)</span>
                </button>
            </div>
            <div v-if="contextMenu" @click="contextMenu = null" class="fixed inset-0 z-40"></div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    month: String,
    monthName: String,
    days: Array,
    employees: Array,
    shifts: Array,
    patterns: Array,
    schedulesMap: Object,
    branches: Array,
    areas: Array,
    positions: Array,
    filters: Object,
    stats: Object,
});

// State Filter Toolbar
const currentMonthStr = ref(props.month);
const filterBranch = ref(props.filters.branch || '');
const filterPosition = ref(props.filters.position || '');
const searchQuery = ref(props.filters.search || '');

// Local Schedules state (untuk batch edit real-time)
// localMap: { [empId]: { [dateStr]: { shift_id, status } } }
const localMap = ref(JSON.parse(JSON.stringify(props.schedulesMap || {})));

// Sinkronkan localMap secara otomatis setiap kali props.schedulesMap diperbarui dari server (generate, reset, copy, dsb)
watch(
    () => props.schedulesMap,
    (newMap) => {
        localMap.value = JSON.parse(JSON.stringify(newMap || {}));
    },
    { deep: true }
);

// List perubahan yang belum disimpan: { employee_id, date, shift_id, status }
const pendingChanges = ref({});
const isSaving = ref(false);

// Unsaved changes count
const unsavedChangesCount = computed(() => Object.keys(pendingChanges.value).length);

// Top shifts list computed for Card 4
const topShifts = computed(() => {
    const counts = props.stats?.shiftCounts || {};
    // ambil maksimal 3 teratas
    const entries = Object.entries(counts).slice(0, 3);
    const res = {};
    for (const [k, v] of entries) {
        res[k] = v;
    }
    return res;
});

// ── MODE PENGHAPUS CEPAT (ERASER TOOL) ──
const isEraserMode = ref(false);
const toggleEraserMode = () => {
    isEraserMode.value = !isEraserMode.value;
    if (isEraserMode.value) {
        closeCellPicker();
        contextMenu.value = null;
    }
};

const handleCellClick = (emp, date, event) => {
    if (isEraserMode.value) {
        quickEraseCell(emp.id, date);
        return;
    }
    openCellPicker(emp, date, event);
};

const quickEraseCell = (empId, date) => {
    const changeKey = `${empId}_${date}`;
    if (localMap.value[empId]) {
        delete localMap.value[empId][date];
    }
    pendingChanges.value[changeKey] = {
        employee_id: empId,
        date: date,
        shift_id: null,
        status: '',
    };
};

// ── CONTEXT MENU (KLIK KANAN PADA CELL) ──
const contextMenu = ref(null);
const openContextMenu = (emp, date, event) => {
    closeCellPicker();
    const rect = event.currentTarget.getBoundingClientRect();
    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 4;
    if (left + 240 > window.innerWidth) {
        left = window.innerWidth - 250;
    }
    contextMenu.value = {
        employee: emp,
        date: date,
        left: Math.max(10, left),
        top: Math.max(10, top),
    };
};

const contextActionClear = () => {
    if (!contextMenu.value) return;
    quickEraseCell(contextMenu.value.employee.id, contextMenu.value.date);
    contextMenu.value = null;
};

const contextActionSetOff = () => {
    if (!contextMenu.value) return;
    const empId = contextMenu.value.employee.id;
    const date = contextMenu.value.date;
    const changeKey = `${empId}_${date}`;
    if (!localMap.value[empId]) localMap.value[empId] = {};
    localMap.value[empId][date] = {
        employee_id: empId,
        date: date,
        shift_id: null,
        status: 'off',
    };
    pendingChanges.value[changeKey] = {
        employee_id: empId,
        date: date,
        shift_id: 'OFF',
        status: 'off',
    };
    contextMenu.value = null;
};

const contextActionSetShift = (shiftId) => {
    if (!contextMenu.value) return;
    const empId = contextMenu.value.employee.id;
    const date = contextMenu.value.date;
    const changeKey = `${empId}_${date}`;
    const found = props.shifts.find(s => s.id === shiftId);
    if (!localMap.value[empId]) localMap.value[empId] = {};
    localMap.value[empId][date] = {
        employee_id: empId,
        date: date,
        shift_id: shiftId,
        status: 'work',
        shift_name: found?.name,
        shift_color: found?.color,
    };
    pendingChanges.value[changeKey] = {
        employee_id: empId,
        date: date,
        shift_id: shiftId,
        status: 'work',
    };
    contextMenu.value = null;
};

const contextActionClearMonth = () => {
    if (!contextMenu.value) return;
    const emp = contextMenu.value.employee;
    contextMenu.value = null;
    quickClearEmployee(emp);
};

// ── AKSI CEPAT HAPUS PER KARYAWAN & PER TANGGAL ──
const quickClearEmployee = (emp) => {
    if (confirm(`Kosongkan seluruh jadwal ${emp.name} pada bulan ${props.monthName}?`)) {
        router.post(route('work-schedules.reset-month'), {
            month: currentMonthStr.value,
            scope: 'employee',
            employee_ids: [emp.id],
        }, {
            preserveScroll: true,
            onSuccess: () => {
                if (localMap.value[emp.id]) {
                    delete localMap.value[emp.id];
                }
            }
        });
    }
};

const quickClearDate = (date, dayNum, dayName) => {
    if (confirm(`Kosongkan jadwal semua karyawan pada hari ${dayName} tanggal ${dayNum} (${date})?`)) {
        router.post(route('work-schedules.reset-month'), {
            month: currentMonthStr.value,
            scope: 'single_day',
            date: date,
        }, {
            preserveScroll: true,
        });
    }
};

// Cell Popover State
const activePopover = ref(null);

const openCellPicker = (employee, date, event) => {
    const rect = event.currentTarget.getBoundingClientRect();
    const popoverWidth = 260;
    const popoverHeight = 280;

    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + 6;

    // Boundary check window width
    if (left + popoverWidth > window.innerWidth) {
        left = window.innerWidth - popoverWidth - 20;
    }

    // Boundary check window height
    if (top + popoverHeight > window.innerHeight + window.scrollY) {
        top = rect.top + window.scrollY - popoverHeight - 6;
    }

    activePopover.value = {
        employee,
        date,
        top: Math.max(10, top),
        left: Math.max(10, left),
    };
};

const closeCellPicker = () => {
    activePopover.value = null;
};

// Select Shift untuk active cell
const selectShiftForActiveCell = (shiftVal) => {
    if (!activePopover.value) return;

    const empId = activePopover.value.employee.id;
    const date = activePopover.value.date;

    const changeKey = `${empId}_${date}`;

    let shiftId = null;
    let status = 'work';
    let shiftName = '';
    let shiftColor = '';

    if (shiftVal === 'OFF') {
        shiftId = null;
        status = 'off';
    } else if (shiftVal !== null) {
        shiftId = Number(shiftVal);
        status = 'work';
        const found = props.shifts.find(s => s.id === shiftId);
        if (found) {
            shiftName = found.name;
            shiftColor = found.color;
        }
    } else {
        // Kosongkan
        shiftId = null;
        status = '';
    }

    // Update localMap
    if (!localMap.value[empId]) {
        localMap.value[empId] = {};
    }

    if (status === '' && shiftId === null) {
        delete localMap.value[empId][date];
    } else {
        localMap.value[empId][date] = {
            employee_id: empId,
            date: date,
            shift_id: shiftId,
            status: status,
            shift_name: shiftName,
            shift_color: shiftColor,
        };
    }

    // Tambahkan ke pending changes
    pendingChanges.value[changeKey] = {
        employee_id: empId,
        date: date,
        shift_id: shiftVal === 'OFF' ? 'OFF' : shiftId,
        status: status,
    };

    closeCellPicker();
};

const hasChange = (empId, date) => {
    return !!pendingChanges.value[`${empId}_${date}`];
};

const getSchedule = (empId, date) => {
    return localMap.value?.[empId]?.[date] || null;
};

const getScheduleLabel = (sched) => {
    if (!sched) return '';
    if (sched.status === 'off' || sched.shift_id === null) return 'OFF';
    return sched.shift_name || 'Shift';
};

const getCellBadgeClass = (sched) => {
    if (!sched) return '';
    if (sched.status === 'off' || sched.shift_id === null) {
        return 'bg-rose-50 text-rose-600 border border-rose-200/80';
    }
    const color = sched.shift_color || 'blue';
    return getShiftBadgeStyle(color);
};

const getShiftBadgeStyle = (color) => {
    switch (color) {
        case 'emerald':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200/80';
        case 'purple':
            return 'bg-purple-50 text-purple-700 border-purple-200/80';
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200/80';
        case 'rose':
            return 'bg-rose-50 text-rose-700 border-rose-200/80';
        case 'cyan':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200/80';
        case 'blue':
        default:
            return 'bg-blue-50 text-blue-700 border-blue-200/80';
    }
};

const getDotColor = (color) => {
    switch (color) {
        case 'emerald': return 'bg-emerald-500';
        case 'purple': return 'bg-purple-500';
        case 'amber': return 'bg-amber-500';
        case 'rose': return 'bg-rose-500';
        case 'cyan': return 'bg-cyan-500';
        case 'blue':
        default: return 'bg-blue-500';
    }
};

const getCellTooltip = (emp, date) => {
    const sched = getSchedule(emp.id, date);
    if (!sched) return `${emp.name} (${date}): Belum ada jadwal`;
    if (sched.status === 'off') return `${emp.name} (${date}): Hari Libur (OFF)`;
    return `${emp.name} (${date}): ${sched.shift_name}`;
};

const formatLocalDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

// Batch Save
const saveBatchChanges = () => {
    if (unsavedChangesCount.value === 0) return;
    isSaving.value = true;

    const payload = {
        month: currentMonthStr.value,
        changes: Object.values(pendingChanges.value),
    };

    router.post(route('work-schedules.save-batch'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            pendingChanges.value = {};
            isSaving.value = false;
        },
        onError: () => {
            isSaving.value = false;
        }
    });
};

const discardChanges = () => {
    pendingChanges.value = {};
    localMap.value = JSON.parse(JSON.stringify(props.schedulesMap || {}));
};

// Navigasi Bulan
const changeMonth = (delta) => {
    const [year, month] = currentMonthStr.value.split('-').map(Number);
    const newDate = new Date(year, month - 1 + delta, 1);
    const newMonthStr = `${newDate.getFullYear()}-${String(newDate.getMonth() + 1).padStart(2, '0')}`;
    navigateToMonth(newMonthStr);
};

const onMonthInputChange = (event) => {
    if (event.target.value) {
        navigateToMonth(event.target.value);
    }
};

const goToToday = () => {
    const now = new Date();
    const todayMonth = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    navigateToMonth(todayMonth);
};

const navigateToMonth = (monthStr) => {
    if (unsavedChangesCount.value > 0) {
        if (!confirm('Anda memiliki perubahan yang belum disimpan. Pindah bulan akan membatalkan perubahan tersebut. Lanjutkan?')) {
            return;
        }
    }
    router.get(route('work-schedules.index'), {
        month: monthStr,
        branch: filterBranch.value,
        position: filterPosition.value,
        search: searchQuery.value,
    }, { preserveState: false });
};

// Filter Cabang & Jabatan
let debounceTimeout = null;
const applyFiltersDebounced = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const applyFilters = () => {
    if (unsavedChangesCount.value > 0) {
        if (!confirm('Anda memiliki perubahan yang belum disimpan. Menerapkan filter akan membatalkan perubahan tersebut. Lanjutkan?')) {
            return;
        }
    }
    router.get(route('work-schedules.index'), {
        month: currentMonthStr.value,
        branch: filterBranch.value,
        position: filterPosition.value,
        search: searchQuery.value,
    }, { preserveState: false, preserveScroll: true });
};

// Print Schedule
const printSchedule = () => {
    window.print();
};

// Lanjutkan dari bulan lalu
const continueFromPrevMonth = () => {
    if (confirm(`Apakah Anda yakin ingin melanjutkan pola roster dari bulan lalu ke bulan ${props.monthName}?`)) {
        router.post(route('work-schedules.continue-previous-month'), {
            month: currentMonthStr.value,
        }, {
            preserveScroll: true,
        });
    }
};

// ======================== MODAL HAPUS JADWAL CANGGIH ========================
const openClearScheduleModal = ref(false);
const isClearing = ref(false);
const clearScope = ref('all'); // 'all', 'employee', 'date_range', 'branch', 'shift'
const clearEmployeeSearch = ref('');
const selectedClearEmployees = ref([]);
const clearStartDate = ref(props.days?.[0]?.date || `${currentMonthStr.value}-01`);
const clearEndDate = ref(props.days?.[props.days.length - 1]?.date || `${currentMonthStr.value}-28`);
const clearBranch = ref('');
const clearShiftId = ref('');

const filteredClearEmployees = computed(() => {
    if (!clearEmployeeSearch.value) return props.employees;
    const q = clearEmployeeSearch.value.toLowerCase();
    return props.employees.filter(e =>
        e.name.toLowerCase().includes(q) ||
        (e.position && e.position.toLowerCase().includes(q)) ||
        (e.branch && e.branch.toLowerCase().includes(q)) ||
        (e.area?.name && e.area.name.toLowerCase().includes(q))
    );
});

const toggleSelectAllClearEmployees = () => {
    const ids = filteredClearEmployees.value.map(e => e.id);
    const allSelected = ids.length > 0 && ids.every(id => selectedClearEmployees.value.includes(id));
    if (allSelected) {
        selectedClearEmployees.value = selectedClearEmployees.value.filter(id => !ids.includes(id));
    } else {
        const union = new Set([...selectedClearEmployees.value, ...ids]);
        selectedClearEmployees.value = Array.from(union);
    }
};

const submitClearSchedule = () => {
    isClearing.value = true;
    router.post(route('work-schedules.reset-month'), {
        month: currentMonthStr.value,
        scope: clearScope.value,
        employee_ids: clearScope.value === 'employee' ? selectedClearEmployees.value : null,
        start_date: clearScope.value === 'date_range' ? clearStartDate.value : null,
        end_date: clearScope.value === 'date_range' ? clearEndDate.value : null,
        branch: clearScope.value === 'branch' ? clearBranch.value : null,
        shift_id: clearScope.value === 'shift' ? clearShiftId.value : null,
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            openClearScheduleModal.value = false;
            isClearing.value = false;
            pendingChanges.value = {};
            if (page?.props?.schedulesMap) {
                localMap.value = JSON.parse(JSON.stringify(page.props.schedulesMap));
            }
            Swal.fire({
                icon: 'success',
                title: 'Jadwal Dikosongkan!',
                text: page?.props?.flash?.success || 'Jadwal kerja berhasil dihapus/dikosongkan.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        },
        onError: (errors) => {
            isClearing.value = false;
            const errMsg = Object.values(errors).flat().join(', ') || 'Gagal mengosongkan jadwal.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Hapus Jadwal',
                text: errMsg,
            });
        }
    });
};

// ======================== MODAL GENERATE ========================
const openGenerateModal = ref(false);
const isGenerating = ref(false);
const modalFilterBranch = ref('');
const modalFilterPosition = ref('');
const selectedGenEmployees = ref(props.employees.map(e => e.id));
const selectedPatternId = ref('6-1');
const genDefaultShiftId = ref(props.shifts[0]?.id || 1);
const genStagger = ref(false);

// Fitur Cerdas: Periode & Tanggal Mulai Absen
const genPeriodPreset = ref('full_month'); // 'full_month', 'remaining_month', 'payroll_cutoff', 'custom'
const genStartDate = ref(props.days?.[0]?.date || `${currentMonthStr.value}-01`);
const genEndDate = ref(props.days?.[props.days?.length - 1]?.date || `${currentMonthStr.value}-28`);
const genStartOffset = ref(0); // Titik awal rotasi / fase shift
const genOverwriteMode = ref('all'); // 'all', 'empty_only', 'preserve_off'

const applyPeriodPreset = (preset) => {
    genPeriodPreset.value = preset;
    const firstDay = props.days?.[0]?.date || `${currentMonthStr.value}-01`;
    const lastDay = props.days?.[props.days?.length - 1]?.date || `${currentMonthStr.value}-28`;

    if (preset === 'full_month') {
        genStartDate.value = firstDay;
        genEndDate.value = lastDay;
    } else if (preset === 'remaining_month') {
        const todayStr = new Date().toISOString().slice(0, 10);
        if (todayStr >= firstDay && todayStr <= lastDay) {
            genStartDate.value = todayStr;
        } else {
            genStartDate.value = firstDay;
        }
        genEndDate.value = lastDay;
    } else if (preset === 'payroll_cutoff') {
        try {
            const [yr, mo] = currentMonthStr.value.split('-').map(Number);
            const prevMonthDate = new Date(yr, mo - 2, 21);
            const curMonthDate = new Date(yr, mo - 1, 20);
            const pad = (n) => String(n).padStart(2, '0');
            genStartDate.value = `${prevMonthDate.getFullYear()}-${pad(prevMonthDate.getMonth() + 1)}-21`;
            genEndDate.value = `${curMonthDate.getFullYear()}-${pad(curMonthDate.getMonth() + 1)}-20`;
        } catch (e) {
            genStartDate.value = firstDay;
            genEndDate.value = lastDay;
        }
    }
};

const openGenerateScheduleModal = () => {
    applyPeriodPreset(genPeriodPreset.value);
    selectedGenEmployees.value = filteredGenEmployees.value.map(e => e.id);
    openGenerateModal.value = true;
};

const genDurationDays = computed(() => {
    if (!genStartDate.value || !genEndDate.value) return 0;
    const start = new Date(genStartDate.value);
    const end = new Date(genEndDate.value);
    if (isNaN(start.getTime()) || isNaN(end.getTime()) || start > end) return 0;
    const diffTime = Math.abs(end - start);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const genTotalSlotCount = computed(() => {
    return selectedGenEmployees.value.length * genDurationDays.value;
});

const currentPatternPhases = computed(() => {
    if (selectedPatternId.value === '6-1') {
        const defaultShift = props.shifts.find(s => s.id == genDefaultShiftId.value);
        const shiftName = defaultShift ? defaultShift.name : 'Kerja';
        return [
            { offset: 0, label: 'Hari 1', shiftName: shiftName, isOff: false },
            { offset: 1, label: 'Hari 2', shiftName: shiftName, isOff: false },
            { offset: 2, label: 'Hari 3', shiftName: shiftName, isOff: false },
            { offset: 3, label: 'Hari 4', shiftName: shiftName, isOff: false },
            { offset: 4, label: 'Hari 5', shiftName: shiftName, isOff: false },
            { offset: 5, label: 'Hari 6', shiftName: shiftName, isOff: false },
            { offset: 6, label: 'Hari 7', shiftName: 'Libur (OFF)', isOff: true },
        ];
    }
    if (selectedPatternId.value === '5-2') {
        const defaultShift = props.shifts.find(s => s.id == genDefaultShiftId.value);
        const shiftName = defaultShift ? defaultShift.name : 'Kerja';
        return [
            { offset: 0, label: 'Hari 1', shiftName: shiftName, isOff: false },
            { offset: 1, label: 'Hari 2', shiftName: shiftName, isOff: false },
            { offset: 2, label: 'Hari 3', shiftName: shiftName, isOff: false },
            { offset: 3, label: 'Hari 4', shiftName: shiftName, isOff: false },
            { offset: 4, label: 'Hari 5', shiftName: shiftName, isOff: false },
            { offset: 5, label: 'Hari 6', shiftName: 'Libur (Sabtu)', isOff: true },
            { offset: 6, label: 'Hari 7', shiftName: 'Libur (Minggu)', isOff: true },
        ];
    }
    const found = props.patterns.find(p => p.id == selectedPatternId.value);
    if (!found || !Array.isArray(found.pattern_data)) {
        return [
            { offset: 0, label: 'Langkah 1', shiftName: 'Kerja', isOff: false },
            { offset: 1, label: 'Langkah 2', shiftName: 'Libur (OFF)', isOff: true }
        ];
    }
    return found.pattern_data.map((item, idx) => {
        const isOff = item === 'OFF' || !item;
        let sName = 'Libur (OFF)';
        if (!isOff) {
            const sh = props.shifts.find(s => s.id == item);
            sName = sh ? sh.name : `Shift #${item}`;
        }
        return {
            offset: idx,
            label: `Langkah ${idx + 1}`,
            shiftName: sName,
            isOff: isOff
        };
    });
});

const filteredGenEmployees = computed(() => {
    return props.employees.filter(emp => {
        if (modalFilterBranch.value) {
            const bVal = modalFilterBranch.value;
            const matchArea = emp.area_id == bVal || emp.area?.name == bVal;
            const matchBranch = emp.branch == bVal;
            if (!matchArea && !matchBranch) return false;
        }
        if (modalFilterPosition.value) {
            if (emp.position !== modalFilterPosition.value) return false;
        }
        return true;
    });
});

const toggleSelectAllEmployees = () => {
    const currentFilteredIds = filteredGenEmployees.value.map(e => e.id);
    const allSelected = currentFilteredIds.length > 0 && currentFilteredIds.every(id => selectedGenEmployees.value.includes(id));
    if (allSelected) {
        selectedGenEmployees.value = selectedGenEmployees.value.filter(id => !currentFilteredIds.includes(id));
    } else {
        const union = new Set([...selectedGenEmployees.value, ...currentFilteredIds]);
        selectedGenEmployees.value = Array.from(union);
    }
};

const submitGenerateSchedule = () => {
    if (selectedGenEmployees.value.length === 0 || genDurationDays.value <= 0) return;
    isGenerating.value = true;

    let patternType = 'cycle';
    let patternDays = [];

    if (selectedPatternId.value === '6-1') {
        patternType = 'weekly_6_1';
        patternDays = [
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            'OFF'
        ];
    } else if (selectedPatternId.value === '5-2') {
        patternType = 'weekly_5_2';
        patternDays = [
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            genDefaultShiftId.value,
            'OFF',
            'OFF'
        ];
    } else {
        patternType = 'cycle';
        const found = props.patterns.find(p => p.id == selectedPatternId.value);
        patternDays = found?.pattern_data || [genDefaultShiftId.value, 'OFF'];
    }

    router.post(route('work-schedules.generate'), {
        month: currentMonthStr.value,
        start_date: genStartDate.value,
        end_date: genEndDate.value,
        employee_ids: selectedGenEmployees.value,
        pattern_type: patternType,
        pattern_days: patternDays,
        default_shift_id: genDefaultShiftId.value,
        start_offset: Number(genStartOffset.value) || 0,
        offset: Number(genStartOffset.value) || 0,
        stagger: genStagger.value,
        overwrite_mode: genOverwriteMode.value,
    }, {
        preserveScroll: true,
        onSuccess: (page) => {
            openGenerateModal.value = false;
            isGenerating.value = false;
            pendingChanges.value = {};
            if (page?.props?.schedulesMap) {
                localMap.value = JSON.parse(JSON.stringify(page.props.schedulesMap));
            }
            Swal.fire({
                icon: 'success',
                title: 'Jadwal Berhasil Diterapkan & Tersimpan!',
                text: page?.props?.flash?.success || `Sebanyak ${genTotalSlotCount.value || 42} slot jadwal berhasil diterapkan langsung ke kalender kerja.`,
                timer: 2500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        },
        onError: (errors) => {
            isGenerating.value = false;
            const errMsg = Object.values(errors).flat().join(', ') || 'Terjadi kesalahan saat menerapkan jadwal otomatis.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan Jadwal',
                text: errMsg,
            });
        }
    });
};

// ======================== MODAL BUAT POLA ========================
const openPatternModal = ref(false);
const isSavingPattern = ref(false);
const patternForm = reactive({
    name: '',
    days: [props.shifts[0]?.id || 1, props.shifts[0]?.id || 1, 'OFF'],
});

const addDayToPattern = () => {
    patternForm.days.push(props.shifts[0]?.id || 1);
};

const removeDayFromPattern = (idx) => {
    patternForm.days.splice(idx, 1);
};

const submitCreatePattern = () => {
    isSavingPattern.value = true;
    router.post(route('work-schedules.patterns.store'), {
        name: patternForm.name,
        pattern_data: patternForm.days,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            patternForm.name = '';
            patternForm.days = [props.shifts[0]?.id || 1, props.shifts[0]?.id || 1, 'OFF'];
            isSavingPattern.value = false;
        },
        onError: () => {
            isSavingPattern.value = false;
        }
    });
};

const deletePattern = (patternId) => {
    if (confirm('Hapus pola roster ini?')) {
        router.delete(route('work-schedules.patterns.destroy', patternId), {
            preserveScroll: true,
        });
    }
};

// ======================== MODAL COPY BULAN ========================
const openCopyModal = ref(false);
const isCopying = ref(false);
const copySourceMonth = ref('');

const submitCopyMonth = () => {
    if (!copySourceMonth.value) return;
    isCopying.value = true;
    router.post(route('work-schedules.copy-month'), {
        from_month: copySourceMonth.value,
        to_month: currentMonthStr.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            openCopyModal.value = false;
            isCopying.value = false;
        },
        onError: () => {
            isCopying.value = false;
        }
    });
};

// ======================== MODAL QUICK ADD SHIFT ========================
const openAddShiftModal = ref(false);
const isSavingShift = ref(false);
const newShiftForm = reactive({
    name: '',
    start_time: '08:00',
    end_time: '17:00',
    color: 'blue',
});

const submitAddShift = () => {
    isSavingShift.value = true;
    router.post(route('work-schedules.quick-shift'), newShiftForm, {
        preserveScroll: true,
        onSuccess: () => {
            openAddShiftModal.value = false;
            isSavingShift.value = false;
            newShiftForm.name = '';
        },
        onError: () => {
            isSavingShift.value = false;
        }
    });
};
</script>

<style scoped>
/* Styling khusus print landscape untuk cetak A4/Folio yang rapi */
@media print {
    @page {
        size: landscape;
        margin: 8mm;
    }
    body {
        background: white !important;
        color: black !important;
    }
    table {
        font-size: 8px !important;
    }
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
