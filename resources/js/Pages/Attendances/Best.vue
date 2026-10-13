<template>
    <AppLayout title="Absen Terbaik" subtitle="Papan Peringkat Kedisiplinan & Penghargaan Absensi Karyawan">
        <div class="space-y-6 pb-12">
            <!-- ========================================================= -->
            <!-- 1. HEADER SECTION & FILTER BULAN/TAHUN                    -->
            <!-- ========================================================= -->
            <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                    <!-- Title & Subtitle -->
                    <div class="flex items-start sm:items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-emerald-500/20 text-2xl">
                            🏆
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                                Absen Terbaik
                            </h1>
                            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-0.5">
                                <span v-if="currentScope === 'global'">
                                    Nominasi global: Juara 1 tiap cabang berkompetisi — Top 3 mendapat reward
                                </span>
                                <span v-else>
                                    Peringkat kedisiplinan dan absensi terbaik tingkat cabang — Top 3 mendapat reward cabang
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Month, Year & Action Controls -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Dropdown Bulan -->
                        <div class="relative">
                            <select
                                v-model="selectedMonth"
                                @change="applyFilters"
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs sm:text-sm font-bold rounded-xl pl-3.5 pr-8 py-2.5 hover:bg-slate-100 transition focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer"
                            >
                                <option v-for="(mName, mIdx) in monthNames" :key="mIdx" :value="mIdx + 1">
                                    {{ mName }}
                                </option>
                            </select>
                            <svg class="w-4 h-4 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/>
                            </svg>
                        </div>

                        <!-- Dropdown Tahun -->
                        <div class="relative">
                            <select
                                v-model="selectedYear"
                                @change="applyFilters"
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs sm:text-sm font-bold rounded-xl pl-3.5 pr-8 py-2.5 hover:bg-slate-100 transition focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer"
                            >
                                <option v-for="y in yearOptions" :key="y" :value="y">
                                    {{ y }}
                                </option>
                            </select>
                            <svg class="w-4 h-4 text-slate-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/>
                            </svg>
                        </div>

                        <!-- Action: Print / Cetak Pengumuman -->
                        <button
                            type="button"
                            @click="showPrintModal = true"
                            class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5"
                            title="Cetak Pengumuman Juara"
                        >
                            <span>🖨️</span>
                            <span class="hidden sm:inline">Cetak Pengumuman</span>
                        </button>

                        <!-- Action: Sinkronkan ke Payroll -->
                        <button
                            type="button"
                            @click="syncToPayroll"
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-md shadow-emerald-600/20 flex items-center gap-1.5 active:scale-95"
                            title="Kirim bonus reward ke modul penggajian"
                        >
                            <span>⚡</span>
                            <span>Sinkron ke Payroll</span>
                        </button>
                    </div>
                </div>

                <!-- ===================================================== -->
                <!-- 2. FILTER TABS (DEPARTEMEN & SCOPE GLOBAL / CABANG)  -->
                <!-- ===================================================== -->
                <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <!-- Left: Departemen Filter -->
                    <div class="flex items-center gap-1.5 bg-slate-100/80 p-1 rounded-xl">
                        <button
                            type="button"
                            @click="setDepartment('all')"
                            :class="[
                                'px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5',
                                currentDepartment === 'all'
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Semua</span>
                        </button>
                        <button
                            type="button"
                            @click="setDepartment('maintenance')"
                            :class="[
                                'px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5',
                                currentDepartment === 'maintenance'
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'
                            ]"
                        >
                            <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            </svg>
                            <span>Maintenance</span>
                        </button>
                    </div>

                    <!-- Right: Scope Global vs Per Cabang -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 bg-slate-100/80 p-1 rounded-xl">
                            <button
                                type="button"
                                @click="setScope('global')"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5',
                                    currentScope === 'global'
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span>🏆</span>
                                <span>Global</span>
                            </button>
                            <button
                                type="button"
                                @click="setScope('branch')"
                                :class="[
                                    'px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5',
                                    currentScope === 'branch'
                                        ? 'bg-white text-slate-900 shadow-sm'
                                        : 'text-slate-600 hover:text-slate-900'
                                ]"
                            >
                                <span>🏢</span>
                                <span>Per Cabang</span>
                            </button>
                        </div>

                        <!-- Dropdown Cabang saat scope branch aktif -->
                        <div v-if="currentScope === 'branch'" class="relative">
                            <select
                                v-model="selectedAreaId"
                                @change="applyFilters"
                                class="appearance-none bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl pl-3 pr-7 py-2 hover:bg-slate-100 transition focus:ring-2 focus:ring-emerald-500 focus:outline-none cursor-pointer"
                            >
                                <option :value="null">Semua Cabang</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    {{ area.name }}
                                </option>
                            </select>
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 3. ACCORDION CARA PERHITUNGAN SKOR & PERINGKAT           -->
            <!-- ========================================================= -->
            <div class="bg-blue-50/60 border border-blue-200/70 rounded-2xl overflow-hidden transition-all duration-200">
                <button
                    type="button"
                    @click="showFormulaInfo = !showFormulaInfo"
                    class="w-full px-5 py-3.5 flex items-center justify-between text-left hover:bg-blue-100/40 transition"
                >
                    <div class="flex items-center gap-2 text-blue-900 text-xs sm:text-sm font-bold">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Cara Perhitungan Skor & Peringkat</span>
                    </div>
                    <svg
                        class="w-4 h-4 text-blue-600 transition-transform duration-200"
                        :class="showFormulaInfo ? 'rotate-180' : ''"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/>
                    </svg>
                </button>

                <div v-show="showFormulaInfo" class="px-5 pb-5 pt-2 text-xs text-blue-950/80 border-t border-blue-100 space-y-3">
                    <p class="leading-relaxed">
                        Sistem penilaian kedisiplinan dihitung secara otomatis dan real-time dari data absensi harian karyawan dengan rumus transparan:
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="bg-white/80 p-3 rounded-xl border border-blue-100">
                            <span class="font-bold text-blue-900 block mb-1">1. Base Kehadiran:</span>
                            <code class="text-[11px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-mono">
                                (Hadir ÷ Target Hari) × 100
                            </code>
                            <p class="text-[11px] text-slate-500 mt-1">Mengukur rasio kepatuhan kehadiran terhadap jadwal kerja.</p>
                        </div>
                        <div class="bg-white/80 p-3 rounded-xl border border-blue-100">
                            <span class="font-bold text-blue-900 block mb-1">2. Penalti Terlambat:</span>
                            <code class="text-[11px] bg-rose-50 text-rose-700 px-1.5 py-0.5 rounded font-mono">
                                -(Telat × 0.25) - (Menit × 0.01)
                            </code>
                            <p class="text-[11px] text-slate-500 mt-1">Pengurangan poin setiap kali terlambat & durasi menit keterlambatan.</p>
                        </div>
                        <div class="bg-white/80 p-3 rounded-xl border border-blue-100">
                            <span class="font-bold text-blue-900 block mb-1">3. Penalti Alpha:</span>
                            <code class="text-[11px] bg-rose-50 text-rose-700 px-1.5 py-0.5 rounded font-mono">
                                -(Alpha × 2.0 Poin)
                            </code>
                            <p class="text-[11px] text-slate-500 mt-1">Pemotongan skor signifikan untuk ketidakhadiran tanpa keterangan.</p>
                        </div>
                        <div class="bg-white/80 p-3 rounded-xl border border-blue-100">
                            <span class="font-bold text-blue-900 block mb-1">4. Reward Pemenang:</span>
                            <p class="text-[11px] text-emerald-700 font-bold mt-1">
                                🥇 Juara 1: Rp {{ formatRupiah(rewardsConfig.global[1] || 200000) }}<br>
                                🥈 Juara 2: Rp {{ formatRupiah(rewardsConfig.global[2] || 150000) }}<br>
                                🥉 Juara 3: Rp {{ formatRupiah(rewardsConfig.global[3] || 100000) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 4. KPI STAT CARDS (5 KARTU STATISTIK DARI GAMBAR)         -->
            <!-- ========================================================= -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
                <!-- Card 1: Total Karyawan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-3.5 shadow-sm">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-none">
                            {{ kpi.total_employees }}
                        </div>
                        <div class="text-xs text-slate-500 font-semibold mt-1">Total Karyawan</div>
                    </div>
                </div>

                <!-- Card 2: Rata-rata Skor -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-3.5 shadow-sm">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-none">
                            {{ kpi.avg_score }}
                        </div>
                        <div class="text-xs text-slate-500 font-semibold mt-1">Rata-rata Skor</div>
                    </div>
                </div>

                <!-- Card 3: Skor Sempurna -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-3.5 shadow-sm">
                    <div class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-none">
                            {{ kpi.perfect_score_count }}
                        </div>
                        <div class="text-xs text-slate-500 font-semibold mt-1">Skor Sempurna</div>
                    </div>
                </div>

                <!-- Card 4: Total Keterlambatan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-3.5 shadow-sm">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-none">
                            {{ kpi.total_lates }}
                        </div>
                        <div class="text-xs text-slate-500 font-semibold mt-1">Total Keterlambatan</div>
                    </div>
                </div>

                <!-- Card 5: Total Alpha -->
                <div class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 flex items-center gap-3.5 shadow-sm col-span-2 lg:col-span-1">
                    <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 leading-none">
                            {{ kpi.total_alphas }}
                        </div>
                        <div class="text-xs text-slate-500 font-semibold mt-1">Total Alpha</div>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 5. PODIUM TOP 3 CARDS (DESAIN MEWAH PERSIS SCREENSHOT)   -->
            <!-- ========================================================= -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
                <!-- JUARA #1 (EMAS / GOLD) -->
                <div
                    v-if="podium[0]"
                    class="bg-gradient-to-b from-amber-50/70 via-amber-50/30 to-white rounded-3xl border-2 border-amber-300 p-6 flex flex-col justify-between text-center relative shadow-md shadow-amber-500/10 hover:shadow-lg transition-all"
                >
                    <!-- Background subtle trophy watermark -->
                    <div class="absolute right-4 top-4 text-4xl opacity-10 select-none pointer-events-none">
                        🥇
                    </div>

                    <div>
                        <!-- Badge Juara -->
                        <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-200/80 text-xs font-black mx-auto">
                            <span>🥇</span>
                            <span>Absen Terbaik #1</span>
                        </div>

                        <!-- Avatar Photo -->
                        <div class="mt-5 relative inline-block">
                            <img
                                v-if="podium[0].photo"
                                :src="'/storage/' + podium[0].photo"
                                :alt="podium[0].name"
                                class="w-20 h-20 rounded-full mx-auto object-cover border-4 border-amber-400 p-0.5 shadow-md"
                            />
                            <div
                                v-else
                                class="w-20 h-20 rounded-full mx-auto bg-amber-200 border-4 border-amber-400 flex items-center justify-center text-amber-900 font-black text-2xl shadow-md"
                            >
                                {{ getInitials(podium[0].name) }}
                            </div>
                        </div>

                        <!-- Name & Position -->
                        <h2 class="text-lg font-black text-slate-900 mt-3 truncate px-2" :title="podium[0].name">
                            {{ podium[0].name }}
                        </h2>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide truncate mt-0.5">
                            {{ podium[0].position }}
                        </p>

                        <!-- Cabang Badge -->
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-bold">
                                🏢 {{ podium[0].branch }}
                            </span>
                        </div>

                        <!-- Score Pill -->
                        <div class="mt-4">
                            <span class="inline-block bg-emerald-600 text-white font-black text-xl px-7 py-1.5 rounded-full shadow-sm">
                                {{ podium[0].score }}
                            </span>
                        </div>

                        <!-- Reward Pill -->
                        <div class="mt-2.5">
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-emerald-800 bg-emerald-100/90 border border-emerald-300/80 px-3.5 py-1 rounded-full">
                                <span>💵</span>
                                <span>Rp {{ formatRupiah(podium[0].reward_amount) }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Attendance Stats -->
                    <div class="mt-6 pt-4 border-t border-amber-200/60 grid grid-cols-2 gap-2 text-xs text-slate-600 font-medium">
                        <div class="text-left">
                            Hadir: <strong class="text-slate-900 font-bold">{{ podium[0].presents }}/{{ podium[0].target_days }}</strong>
                        </div>
                        <div class="text-right">
                            Terlambat: <strong class="text-slate-900 font-bold">{{ podium[0].lates }}</strong>
                        </div>
                        <div class="text-left">
                            Alpha: <strong class="text-slate-900 font-bold">{{ podium[0].alphas }}</strong>
                        </div>
                        <div class="text-right">
                            Pulang cepat: <strong class="text-slate-900 font-bold">{{ podium[0].early_leaves }}</strong>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button
                        type="button"
                        @click="openDetail(podium[0])"
                        class="mt-4 w-full py-2 bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1"
                    >
                        <span>👁️ Lihat Detail Presensi</span>
                    </button>
                </div>

                <!-- Empty placeholder if no Juara 1 -->
                <div v-else class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-8 text-center flex flex-col items-center justify-center text-slate-400">
                    <span class="text-3xl mb-2">🥇</span>
                    <span class="text-xs font-bold">Belum ada kandidat Juara #1</span>
                </div>

                <!-- JUARA #2 (PERAK / SILVER) -->
                <div
                    v-if="podium[1]"
                    class="bg-gradient-to-b from-slate-50/80 via-slate-50/30 to-white rounded-3xl border-2 border-slate-300 p-6 flex flex-col justify-between text-center relative shadow-sm hover:shadow-md transition-all"
                >
                    <div class="absolute right-4 top-4 text-4xl opacity-10 select-none pointer-events-none">
                        🥈
                    </div>

                    <div>
                        <!-- Badge Juara -->
                        <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-slate-200 text-slate-800 border border-slate-300 text-xs font-black mx-auto">
                            <span>🥈</span>
                            <span>Absen Terbaik #2</span>
                        </div>

                        <!-- Avatar Photo -->
                        <div class="mt-5 relative inline-block">
                            <img
                                v-if="podium[1].photo"
                                :src="'/storage/' + podium[1].photo"
                                :alt="podium[1].name"
                                class="w-20 h-20 rounded-full mx-auto object-cover border-4 border-slate-300 p-0.5 shadow-md"
                            />
                            <div
                                v-else
                                class="w-20 h-20 rounded-full mx-auto bg-slate-200 border-4 border-slate-300 flex items-center justify-center text-slate-800 font-black text-2xl shadow-md"
                            >
                                {{ getInitials(podium[1].name) }}
                            </div>
                        </div>

                        <!-- Name & Position -->
                        <h2 class="text-lg font-black text-slate-900 mt-3 truncate px-2" :title="podium[1].name">
                            {{ podium[1].name }}
                        </h2>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide truncate mt-0.5">
                            {{ podium[1].position }}
                        </p>

                        <!-- Cabang Badge -->
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-bold">
                                🏢 {{ podium[1].branch }}
                            </span>
                        </div>

                        <!-- Score Pill -->
                        <div class="mt-4">
                            <span class="inline-block bg-emerald-600 text-white font-black text-xl px-7 py-1.5 rounded-full shadow-sm">
                                {{ podium[1].score }}
                            </span>
                        </div>

                        <!-- Reward Pill -->
                        <div class="mt-2.5">
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-emerald-800 bg-emerald-100/90 border border-emerald-300/80 px-3.5 py-1 rounded-full">
                                <span>💵</span>
                                <span>Rp {{ formatRupiah(podium[1].reward_amount) }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Attendance Stats -->
                    <div class="mt-6 pt-4 border-t border-slate-200 grid grid-cols-2 gap-2 text-xs text-slate-600 font-medium">
                        <div class="text-left">
                            Hadir: <strong class="text-slate-900 font-bold">{{ podium[1].presents }}/{{ podium[1].target_days }}</strong>
                        </div>
                        <div class="text-right">
                            Terlambat: <strong class="text-slate-900 font-bold">{{ podium[1].lates }}</strong>
                        </div>
                        <div class="text-left">
                            Alpha: <strong class="text-slate-900 font-bold">{{ podium[1].alphas }}</strong>
                        </div>
                        <div class="text-right">
                            Pulang cepat: <strong class="text-slate-900 font-bold">{{ podium[1].early_leaves }}</strong>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button
                        type="button"
                        @click="openDetail(podium[1])"
                        class="mt-4 w-full py-2 bg-slate-200 hover:bg-slate-300 text-slate-900 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1"
                    >
                        <span>👁️ Lihat Detail Presensi</span>
                    </button>
                </div>

                <!-- Empty placeholder if no Juara 2 -->
                <div v-else class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-8 text-center flex flex-col items-center justify-center text-slate-400">
                    <span class="text-3xl mb-2">🥈</span>
                    <span class="text-xs font-bold">Belum ada kandidat Juara #2</span>
                </div>

                <!-- JUARA #3 (PERUNGGU / BRONZE) -->
                <div
                    v-if="podium[2]"
                    class="bg-gradient-to-b from-amber-100/40 via-orange-50/20 to-white rounded-3xl border-2 border-amber-400/80 p-6 flex flex-col justify-between text-center relative shadow-sm hover:shadow-md transition-all"
                >
                    <div class="absolute right-4 top-4 text-4xl opacity-10 select-none pointer-events-none">
                        🥉
                    </div>

                    <div>
                        <!-- Badge Juara -->
                        <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-xs font-black mx-auto">
                            <span>🥉</span>
                            <span>Absen Terbaik #3</span>
                        </div>

                        <!-- Avatar Photo -->
                        <div class="mt-5 relative inline-block">
                            <img
                                v-if="podium[2].photo"
                                :src="'/storage/' + podium[2].photo"
                                :alt="podium[2].name"
                                class="w-20 h-20 rounded-full mx-auto object-cover border-4 border-amber-500/80 p-0.5 shadow-md"
                            />
                            <div
                                v-else
                                class="w-20 h-20 rounded-full mx-auto bg-amber-100 border-4 border-amber-400 flex items-center justify-center text-amber-900 font-black text-2xl shadow-md"
                            >
                                {{ getInitials(podium[2].name) }}
                            </div>
                        </div>

                        <!-- Name & Position -->
                        <h2 class="text-lg font-black text-slate-900 mt-3 truncate px-2" :title="podium[2].name">
                            {{ podium[2].name }}
                        </h2>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide truncate mt-0.5">
                            {{ podium[2].position }}
                        </p>

                        <!-- Cabang Badge -->
                        <div class="mt-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-bold">
                                🏢 {{ podium[2].branch }}
                            </span>
                        </div>

                        <!-- Score Pill -->
                        <div class="mt-4">
                            <span class="inline-block bg-emerald-600 text-white font-black text-xl px-7 py-1.5 rounded-full shadow-sm">
                                {{ podium[2].score }}
                            </span>
                        </div>

                        <!-- Reward Pill -->
                        <div class="mt-2.5">
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-emerald-800 bg-emerald-100/90 border border-emerald-300/80 px-3.5 py-1 rounded-full">
                                <span>💵</span>
                                <span>Rp {{ formatRupiah(podium[2].reward_amount) }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Bottom Attendance Stats -->
                    <div class="mt-6 pt-4 border-t border-amber-200/60 grid grid-cols-2 gap-2 text-xs text-slate-600 font-medium">
                        <div class="text-left">
                            Hadir: <strong class="text-slate-900 font-bold">{{ podium[2].presents }}/{{ podium[2].target_days }}</strong>
                        </div>
                        <div class="text-right">
                            Terlambat: <strong class="text-slate-900 font-bold">{{ podium[2].lates }}</strong>
                        </div>
                        <div class="text-left">
                            Alpha: <strong class="text-slate-900 font-bold">{{ podium[2].alphas }}</strong>
                        </div>
                        <div class="text-right">
                            Pulang cepat: <strong class="text-slate-900 font-bold">{{ podium[2].early_leaves }}</strong>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <button
                        type="button"
                        @click="openDetail(podium[2])"
                        class="mt-4 w-full py-2 bg-amber-200 hover:bg-amber-300 text-slate-900 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1"
                    >
                        <span>👁️ Lihat Detail Presensi</span>
                    </button>
                </div>

                <!-- Empty placeholder if no Juara 3 -->
                <div v-else class="bg-white rounded-3xl border-2 border-dashed border-slate-200 p-8 text-center flex flex-col items-center justify-center text-slate-400">
                    <span class="text-3xl mb-2">🥉</span>
                    <span class="text-xs font-bold">Belum ada kandidat Juara #3</span>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 6. LEADERBOARD LENGKAP (TABEL SELURUH KARYAWAN)            -->
            <!-- ========================================================= -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                <!-- Table Header & Search -->
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <span>📊</span>
                            <span>Klasemen Seluruh Karyawan</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Menampilkan seluruh peringkat kedisiplinan periode {{ monthNames[selectedMonth - 1] }} {{ selectedYear }}
                        </p>
                    </div>

                    <!-- Search Input -->
                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Cari nama, jabatan..."
                            class="w-full text-xs pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition"
                        />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/80">
                            <tr>
                                <th class="px-5 py-3.5 w-16 text-center">Rank</th>
                                <th class="px-5 py-3.5">Karyawan</th>
                                <th class="px-5 py-3.5">Cabang / Divisi</th>
                                <th class="px-5 py-3.5 text-center">Kehadiran</th>
                                <th class="px-5 py-3.5 text-center">Kedisiplinan</th>
                                <th class="px-5 py-3.5 text-center">Skor Akhir</th>
                                <th class="px-5 py-3.5 text-center">Status Reward</th>
                                <th class="px-5 py-3.5 text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr
                                v-for="emp in filteredLeaderboard"
                                :key="emp.id"
                                :class="[
                                    'hover:bg-slate-50/80 transition',
                                    emp.rank === 1 ? 'bg-amber-50/30 font-bold' : '',
                                    emp.rank === 2 ? 'bg-slate-50/50' : '',
                                    emp.rank === 3 ? 'bg-orange-50/20' : ''
                                ]"
                            >
                                <!-- Rank -->
                                <td class="px-5 py-4 text-center">
                                    <span v-if="emp.rank === 1" class="text-xl" title="Juara 1">🥇</span>
                                    <span v-else-if="emp.rank === 2" class="text-xl" title="Juara 2">🥈</span>
                                    <span v-else-if="emp.rank === 3" class="text-xl" title="Juara 3">🥉</span>
                                    <span v-else class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 font-bold text-xs">
                                        #{{ emp.rank }}
                                    </span>
                                </td>

                                <!-- Karyawan -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="emp.photo"
                                            :src="'/storage/' + emp.photo"
                                            :alt="emp.name"
                                            class="w-10 h-10 rounded-full object-cover shrink-0 border border-slate-200"
                                        />
                                        <div
                                            v-else
                                            class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-700 font-bold text-sm shrink-0"
                                        >
                                            {{ getInitials(emp.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-slate-900 font-bold truncate">{{ emp.name }}</div>
                                            <div class="text-slate-500 text-[11px] truncate">{{ emp.position }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Cabang / Divisi -->
                                <td class="px-5 py-4">
                                    <div class="font-bold text-slate-800">🏢 {{ emp.branch }}</div>
                                    <span
                                        v-if="emp.is_maintenance"
                                        class="inline-block px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-bold mt-0.5"
                                    >
                                        Teknisi / Maint
                                    </span>
                                </td>

                                <!-- Kehadiran -->
                                <td class="px-5 py-4 text-center">
                                    <div class="text-slate-900 font-bold">{{ emp.presents }} / {{ emp.target_days }} hari</div>
                                    <div class="w-24 bg-slate-200 h-1.5 rounded-full mx-auto mt-1.5 overflow-hidden">
                                        <div
                                            class="bg-emerald-500 h-full rounded-full transition-all duration-300"
                                            :style="{ width: Math.min(100, Math.round((emp.presents / Math.max(1, emp.target_days)) * 100)) + '%' }"
                                        ></div>
                                    </div>
                                </td>

                                <!-- Kedisiplinan -->
                                <td class="px-5 py-4 text-center">
                                    <div class="space-y-0.5 text-[11px]">
                                        <div v-if="emp.lates > 0" class="text-amber-700 font-semibold">
                                            ⚠️ Telat: {{ emp.lates }}x ({{ emp.late_minutes }}m)
                                        </div>
                                        <div v-else class="text-emerald-600 font-semibold">
                                            ✓ Tepat Waktu
                                        </div>
                                        <div v-if="emp.alphas > 0" class="text-rose-600 font-bold">
                                            ✕ Alpha: {{ emp.alphas }} hari
                                        </div>
                                    </div>
                                </td>

                                <!-- Skor Akhir -->
                                <td class="px-5 py-4 text-center">
                                    <span
                                        :class="[
                                            'px-3 py-1 rounded-full font-black text-xs inline-block shadow-sm',
                                            emp.score >= 80 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' :
                                            emp.score >= 50 ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                                            'bg-rose-100 text-rose-800 border border-rose-300'
                                        ]"
                                    >
                                        {{ emp.score }}
                                    </span>
                                </td>

                                <!-- Status Reward -->
                                <td class="px-5 py-4 text-center">
                                    <span
                                        v-if="emp.reward_amount > 0"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-600 text-white text-[11px] font-black shadow-sm"
                                    >
                                        <span>💵</span>
                                        <span>Rp {{ formatRupiah(emp.reward_amount) }}</span>
                                    </span>
                                    <span v-else-if="emp.is_branch_winner" class="text-amber-700 font-bold text-[11px]">
                                        Nominasi Cabang
                                    </span>
                                    <span v-else class="text-slate-400 text-[11px]">
                                        -
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 text-center">
                                    <button
                                        type="button"
                                        @click="openDetail(emp)"
                                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition"
                                        title="Rincian Skor & Log Presensi"
                                    >
                                        <span>👁️ Detail</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="filteredLeaderboard.length === 0">
                                <td colspan="8" class="text-center py-12 text-slate-400">
                                    Tidak ada data karyawan yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 7. MODAL DETAIL LOG PRESENSI KARYAWAN                     -->
            <!-- ========================================================= -->
            <div
                v-if="showDetailModal"
                class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4"
                @click.self="showDetailModal = false"
            >
                <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-fade-in-up">
                    <!-- Modal Header -->
                    <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 border-2 border-emerald-400 flex items-center justify-center font-bold text-emerald-800 text-lg">
                                {{ getInitials(detailEmployee?.name) }}
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black text-slate-900">{{ detailEmployee?.name }}</h3>
                                <p class="text-xs text-slate-500 font-medium">
                                    {{ detailEmployee?.position }} • 🏢 {{ detailEmployee?.branch }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showDetailModal = false"
                            class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-slate-200/60 transition"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-5 sm:p-6 overflow-y-auto space-y-5">
                        <!-- Score Breakdown Box -->
                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                            <div>
                                <div class="text-xs text-slate-500 font-medium">Kehadiran</div>
                                <div class="text-base font-black text-slate-900 mt-0.5">
                                    {{ detailEmployee?.presents }} / {{ detailEmployee?.target_days }} hari
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 font-medium">Keterlambatan</div>
                                <div class="text-base font-black text-amber-700 mt-0.5">
                                    {{ detailEmployee?.lates }}x ({{ detailEmployee?.late_minutes }}m)
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 font-medium">Alpha</div>
                                <div class="text-base font-black text-rose-600 mt-0.5">
                                    {{ detailEmployee?.alphas }} hari
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500 font-medium">Skor Akhir</div>
                                <div class="text-xl font-black text-emerald-700 mt-0.5">
                                    {{ detailEmployee?.score }}
                                </div>
                            </div>
                        </div>

                        <!-- Riwayat Tanggal Kehadiran -->
                        <div>
                            <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider mb-2.5">
                                Log Kehadiran Periode Ini ({{ monthNames[selectedMonth - 1] }} {{ selectedYear }})
                            </h4>

                            <div v-if="loadingDetail" class="text-center py-8 text-slate-400 text-xs">
                                Memuat riwayat presensi...
                            </div>
                            <div v-else-if="employeeHistory.length === 0" class="text-center py-8 text-slate-400 text-xs bg-slate-50 rounded-xl">
                                Belum ada data absensi tercatat pada bulan ini.
                            </div>
                            <div v-else class="space-y-2 max-h-64 overflow-y-auto pr-1">
                                <div
                                    v-for="(hist, hIdx) in employeeHistory"
                                    :key="hIdx"
                                    class="p-3 bg-white border border-slate-100 rounded-xl flex items-center justify-between text-xs hover:border-slate-200 transition"
                                >
                                    <div>
                                        <div class="font-bold text-slate-800">{{ hist.formatted_date || hist.date }}</div>
                                        <div class="text-slate-500 text-[11px] mt-0.5">
                                            Masuk: <strong class="text-slate-700">{{ hist.clock_in ? hist.clock_in.substring(11, 16) : '-' }}</strong> •
                                            Pulang: <strong class="text-slate-700">{{ hist.clock_out ? hist.clock_out.substring(11, 16) : '-' }}</strong>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            v-if="hist.status === 'late'"
                                            class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold text-[10px]"
                                        >
                                            Telat {{ hist.late_minutes }}m
                                        </span>
                                        <span
                                            v-else-if="hist.clock_in"
                                            class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]"
                                        >
                                            Tepat Waktu
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[10px]"
                                        >
                                            Alpha
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 text-right">
                        <button
                            type="button"
                            @click="showDetailModal = false"
                            class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold text-xs rounded-xl transition"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 8. MODAL CETAK / PENGUMUMAN JUARA (PRINT-READY)          -->
            <!-- ========================================================= -->
            <div
                v-if="showPrintModal"
                class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 print:p-0 print:bg-white"
                @click.self="showPrintModal = false"
            >
                <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl print:shadow-none print:border-none print:w-full space-y-6">
                    <!-- Print Top Bar -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200 print:hidden">
                        <span class="text-sm font-bold text-slate-700">Pratinjau Pengumuman Juara Absensi</span>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="printWindow"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow"
                            >
                                <span>🖨️</span>
                                <span>Cetak Sekarang</span>
                            </button>
                            <button
                                type="button"
                                @click="showPrintModal = false"
                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>

                    <!-- Certificate / Official Poster Content -->
                    <div class="border-4 border-double border-amber-300 p-6 sm:p-8 rounded-2xl bg-gradient-to-b from-amber-50/30 to-white text-center space-y-6">
                        <div>
                            <span class="text-xs font-black tracking-widest uppercase text-amber-700">
                                PENGUMUMAN RESMI PERUSAHAAN
                            </span>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">
                                KARYAWAN ABSEN TERBAIK
                            </h2>
                            <p class="text-sm text-slate-600 font-medium">
                                Periode: <strong>{{ monthNames[selectedMonth - 1] }} {{ selectedYear }}</strong>
                            </p>
                        </div>

                        <!-- Top 3 Winners in Print -->
                        <div class="grid grid-cols-3 gap-4 pt-2">
                            <!-- Juara 1 -->
                            <div class="border-2 border-amber-400 bg-amber-50/50 p-4 rounded-xl space-y-2">
                                <span class="text-2xl">🥇</span>
                                <div class="text-xs font-black text-amber-800">JUARA 1</div>
                                <div class="font-black text-sm text-slate-900 truncate">{{ podium[0]?.name || '-' }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ podium[0]?.position || '-' }}</div>
                                <div class="text-xs font-black text-emerald-700">Rp {{ formatRupiah(podium[0]?.reward_amount || 0) }}</div>
                            </div>
                            <!-- Juara 2 -->
                            <div class="border-2 border-slate-300 bg-slate-50 p-4 rounded-xl space-y-2">
                                <span class="text-2xl">🥈</span>
                                <div class="text-xs font-black text-slate-800">JUARA 2</div>
                                <div class="font-black text-sm text-slate-900 truncate">{{ podium[1]?.name || '-' }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ podium[1]?.position || '-' }}</div>
                                <div class="text-xs font-black text-emerald-700">Rp {{ formatRupiah(podium[1]?.reward_amount || 0) }}</div>
                            </div>
                            <!-- Juara 3 -->
                            <div class="border-2 border-amber-500/60 bg-orange-50/30 p-4 rounded-xl space-y-2">
                                <span class="text-2xl">🥉</span>
                                <div class="text-xs font-black text-amber-900">JUARA 3</div>
                                <div class="font-black text-sm text-slate-900 truncate">{{ podium[2]?.name || '-' }}</div>
                                <div class="text-[11px] text-slate-500 truncate">{{ podium[2]?.position || '-' }}</div>
                                <div class="text-xs font-black text-emerald-700">Rp {{ formatRupiah(podium[2]?.reward_amount || 0) }}</div>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 italic max-w-lg mx-auto pt-2">
                            Selamat kepada para pemenang atas dedikasi kedisiplinan dan ketepatan waktu. Terus tingkatkan performa kerja dan jadilah teladan bagi seluruh rekan kerja.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    kpi: {
        type: Object,
        default: () => ({
            total_employees: 0,
            avg_score: 0,
            perfect_score_count: 0,
            total_lates: 0,
            total_alphas: 0,
        }),
    },
    podium: {
        type: Array,
        default: () => [],
    },
    leaderboard: {
        type: Array,
        default: () => [],
    },
    areas: {
        type: Array,
        default: () => [],
    },
    positions: {
        type: Array,
        default: () => [],
    },
    rewardsConfig: {
        type: Object,
        default: () => ({
            global: { 1: 200000, 2: 150000, 3: 100000 },
            branch: { 1: 200000, 2: 150000, 3: 100000 },
        }),
    },
});

// Month & Year state
const selectedMonth = ref(Number(props.filters.month) || 10);
const selectedYear = ref(Number(props.filters.year) || 2026);
const currentDepartment = ref(props.filters.department || 'all');
const currentScope = ref(props.filters.scope || 'global');
const selectedAreaId = ref(props.filters.area_id || null);

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const yearOptions = [2024, 2025, 2026, 2027, 2028];

const showFormulaInfo = ref(false);
const searchQuery = ref('');

// Modals
const showDetailModal = ref(false);
const detailEmployee = ref(null);
const loadingDetail = ref(false);
const employeeHistory = ref([]);
const showPrintModal = ref(false);

const applyFilters = () => {
    router.get(
        '/absen-terbaik',
        {
            month: selectedMonth.value,
            year: selectedYear.value,
            department: currentDepartment.value,
            scope: currentScope.value,
            area_id: selectedAreaId.value,
        },
        { preserveState: true, replace: true }
    );
};

const setDepartment = (dept) => {
    currentDepartment.value = dept;
    applyFilters();
};

const setScope = (scope) => {
    currentScope.value = scope;
    if (scope === 'global') {
        selectedAreaId.value = null;
    }
    applyFilters();
};

const filteredLeaderboard = computed(() => {
    if (!searchQuery.value) return props.leaderboard;
    const q = searchQuery.value.toLowerCase();
    return props.leaderboard.filter(
        (emp) =>
            emp.name.toLowerCase().includes(q) ||
            (emp.position && emp.position.toLowerCase().includes(q)) ||
            (emp.branch && emp.branch.toLowerCase().includes(q))
    );
});

const getInitials = (name) => {
    if (!name) return 'A';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const formatRupiah = (val) => {
    if (!val) return '0';
    return Number(val).toLocaleString('id-ID');
};

const openDetail = async (emp) => {
    detailEmployee.value = emp;
    showDetailModal.value = true;
    loadingDetail.value = true;
    employeeHistory.value = [];

    try {
        const res = await fetch(`/absen-terbaik/employee-detail/${emp.id}?month=${selectedMonth.value}&year=${selectedYear.value}`);
        const data = await res.json();
        employeeHistory.value = data.attendances || [];
    } catch (e) {
        console.error('Error fetching employee detail:', e);
    } finally {
        loadingDetail.value = false;
    }
};

const syncToPayroll = () => {
    if (!props.podium || props.podium.length === 0) {
        alert('Tidak ada data juara untuk disinkronkan.');
        return;
    }

    const monthName = monthNames[selectedMonth.value - 1];
    if (
        !confirm(
            `Sinkronkan seluruh reward juara (${props.podium.length} pemenang) periode ${monthName} ${selectedYear.value} langsung ke modul Payroll?`
        )
    ) {
        return;
    }

    router.post(
        '/absen-terbaik/sync-payroll',
        {
            month: selectedMonth.value,
            year: selectedYear.value,
            winners: props.podium,
        },
        {
            preserveScroll: true,
        }
    );
};

const printWindow = () => {
    window.print();
};
</script>
