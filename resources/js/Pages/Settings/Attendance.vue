<template>
    <AppLayout title="Pengaturan Absensi">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- TOP HEADER SECTION -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ activeTab === 'rewards' ? 'Reward Absen Terbaik' : 'Potongan Absensi Reguler (Staf)' }}
                    </h1>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        {{ activeTab === 'rewards'
                            ? 'Karyawan terbaik peringkat 1-3 per cabang mendapat reward cabang. Juara 1 tiap cabang otomatis masuk nominasi global — Top 3 global mendapat reward tambahan.'
                            : 'Atur nominal potongan gaji otomatis untuk setiap pelanggaran absensi karyawan berbasis shift'
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

            <!-- TAB NAVIGATOR: REWARD ABSEN TERBAIK vs POTONGAN ABSENSI -->
            <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3">
                <button
                    type="button"
                    @click="activeTab = 'rewards'"
                    :class="[
                        activeTab === 'rewards'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2'
                    ]"
                >
                    <span>🏆</span>
                    <span>Reward Absen Terbaik</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'deductions'"
                    :class="[
                        activeTab === 'deductions'
                            ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20'
                            : 'bg-white text-slate-600 hover:text-slate-900 border border-slate-200/80 hover:bg-slate-50',
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2'
                    ]"
                >
                    <span>💳</span>
                    <span>Potongan Absensi & Cron</span>
                </button>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- ============================================================== -->
                <!-- SECTION 1: REWARD ABSEN TERBAIK (LENGKAP SEMUA KATEGORI)       -->
                <!-- ============================================================== -->
                <div v-show="activeTab === 'rewards'" class="space-y-5">
                    <!-- 1. MAIN TOGGLE CARD: AKTIFKAN REWARD ABSEN TERBAIK -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                <span class="text-base">🏆</span>
                                <span>Aktifkan Reward Absen Terbaik</span>
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

                    <!-- 2. SUB TOGGLE: ABSEN TERBAIK PER CABANG -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-slate-900">Absen Terbaik Per Cabang</div>
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

                    <!-- 3. SUB TOGGLE: ABSEN TERBAIK GLOBAL -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-slate-900">Absen Terbaik Global</div>
                            <p class="text-xs text-slate-500 mt-0.5">Juara 1 tiap cabang berkompetisi — Top 3 terbaik perusahaan</p>
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

                    <!-- 4. CARD: REWARD PER CABANG (BLUE BORDER) -->
                    <div class="bg-white rounded-2xl border border-blue-300/90 p-5 shadow-2xs space-y-4 hover:border-blue-400 transition-colors">
                        <div>
                            <div class="text-sm font-bold text-blue-900 flex items-center gap-2">
                                <span class="text-base text-blue-600">🏆</span>
                                <span>Reward Per Cabang (Top 3 per cabang)</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">Nominal reward untuk peringkat 1-3 di masing-masing cabang</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
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
                            </div>
                        </div>
                    </div>

                    <!-- 5. CARD: REWARD GLOBAL (EMERALD/GREEN BORDER) -->
                    <div class="bg-white rounded-2xl border border-emerald-300/90 p-5 shadow-2xs space-y-4 hover:border-emerald-400 transition-colors">
                        <div>
                            <div class="text-sm font-bold text-emerald-900 flex items-center gap-2">
                                <span class="text-base text-emerald-600">🏆</span>
                                <span>Reward Global (Juara 1 tiap cabang — Top 3)</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Nominal reward untuk Top 3 dari nominasi global. Karyawan bisa dapat reward cabang + global sekaligus.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
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
                            </div>
                        </div>
                    </div>

                    <!-- 6. TOGGLE CARD: REWARD ABSEN MAINTENANCE -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-slate-900">Reward Absen Maintenance</div>
                            <p class="text-xs text-slate-500 mt-0.5">Top 3 karyawan maintenance terbaik secara global mendapat reward</p>
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

                    <!-- 7. CARD: REWARD MAINTENANCE (ORANGE BORDER) -->
                    <div class="bg-white rounded-2xl border border-orange-200/90 p-5 shadow-2xs space-y-4 hover:border-orange-300 transition-colors">
                        <div>
                            <div class="text-sm font-bold text-orange-600 flex items-center gap-2">
                                <svg class="w-4 h-4 text-orange-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                </svg>
                                <span>Reward Maintenance (Top 3 Global)</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Nominal reward untuk peringkat 1-3 karyawan maintenance terbaik.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
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
                            </div>
                        </div>
                    </div>

                    <!-- 8. TOGGLE CARD: REWARD TIM KHUSUS -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-2xs flex items-center justify-between">
                        <div>
                            <div class="text-sm font-bold text-slate-900">Reward Tim Khusus</div>
                            <p class="text-xs text-slate-500 mt-0.5">Top 3 karyawan terbaik per grup Tim Khusus mendapat reward</p>
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

                    <!-- 9. CARD: REWARD TIM KHUSUS (CYAN BORDER) -->
                    <div class="bg-white rounded-2xl border border-cyan-200/90 p-5 shadow-2xs space-y-4 hover:border-cyan-300 transition-colors">
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

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1">
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
                            </div>
                        </div>
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

const props = defineProps({
    settings: Object,
});

const activeTab = ref('rewards'); // Default tab reward absen terbaik

const form = useForm({
    // Potongan Absensi
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
    });
};
</script>
