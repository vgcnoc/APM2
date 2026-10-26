<template>
    <AppLayout title="Bonus Target Market" subtitle="Sistem reward pencapaian target pasar & komisi closing seluruh karyawan">
        <div class="max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6 font-sans">
            <!-- Flash Message Alerts -->
            <div v-if="$page.props.flash?.error" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between text-xs font-bold shadow-xs">
                <div class="flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <button type="button" @click="$page.props.flash.error = null" class="text-rose-500 hover:text-rose-800 p-1 font-bold cursor-pointer">✕</button>
            </div>
            <div v-if="$page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs font-bold shadow-xs">
                <div class="flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <button type="button" @click="$page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-800 p-1 font-bold cursor-pointer">✕</button>
            </div>

            <!-- ═══ Top Filter Bar & Action Buttons ═══ -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Periode:</label>
                        <select v-model="selectedMonth" @change="changePeriod" class="text-xs font-bold bg-gray-50 border-gray-200 rounded-xl py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                            <option v-for="(m, idx) in months" :key="idx" :value="idx + 1">{{ m }}</option>
                        </select>
                        <select v-model="selectedYear" @change="changePeriod" class="text-xs font-bold bg-gray-50 border-gray-200 rounded-xl py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                            <option v-for="y in [2025, 2026, 2027, 2028]" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                        {{ kpi.active_programs }} Program Aktif
                    </span>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <!-- Tombol Hitung Ulang -->
                    <button v-if="can('target_market_recalculate') || can('target_market_manage')" @click="triggerRecalculate" :disabled="processingRecalc"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <svg class="w-4 h-4" :class="{ 'animate-spin': processingRecalc }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Hitung Ulang</span>
                    </button>

                    <!-- Tombol Transfer ke Payroll -->
                    <button v-if="can('target_market_sync_payroll') || can('target_market_manage')" @click="triggerSyncPayroll" :disabled="processingSync"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-sm shadow-emerald-200 transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Cairkan ke Payroll</span>
                    </button>

                    <!-- Tombol Tambah Program Target -->
                    <button v-if="can('target_market_program_create') || can('target_market_manage')" @click="openCreateModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shadow-indigo-200 transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Buat Program Target</span>
                    </button>
                </div>
            </div>

            <!-- ═══ KPI Stats Cards ═══ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Bonus Terkumpul -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Bonus Periode Ini</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                            💰
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 tracking-tight">Rp {{ formatCurrency(kpi.total_bonus) }}</div>
                    <p class="text-[11px] text-gray-400 mt-1">Akumulasi reward closing valid</p>
                    <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-amber-50 rounded-full blur-xl pointer-events-none"></div>
                </div>

                <!-- Total Pelanggan Closing -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Closing Baru</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600">
                            🎯
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 tracking-tight">{{ kpi.total_closings }} <span class="text-sm font-semibold text-gray-400">Pelanggan</span></div>
                    <p class="text-[11px] text-gray-400 mt-1">Status aktivasi aktif periode ini</p>
                    <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-blue-50 rounded-full blur-xl pointer-events-none"></div>
                </div>

                <!-- Karyawan Berpartisipasi -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Karyawan Berhasil</span>
                        <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600">
                            👥
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 tracking-tight">{{ kpi.total_employees }} <span class="text-sm font-semibold text-gray-400">Orang</span></div>
                    <p class="text-[11px] text-gray-400 mt-1">Mencatatkan penjualan closing</p>
                    <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-purple-50 rounded-full blur-xl pointer-events-none"></div>
                </div>

                <!-- Target Quota Program -->
                <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Target Kuota Utama</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                            🏆
                        </div>
                    </div>
                    <div class="text-2xl font-black text-gray-900 tracking-tight">{{ kpi.target_quota }} <span class="text-sm font-semibold text-gray-400">Closing/Orang</span></div>
                    <p class="text-[11px] text-emerald-600 font-semibold mt-1">Bonus Tembus: Rp {{ formatCurrency(kpi.reward_bonus_target) }}</p>
                    <div class="absolute -right-3 -bottom-3 w-16 h-16 bg-emerald-50 rounded-full blur-xl pointer-events-none"></div>
                </div>
            </div>

            <!-- ═══ Main Navigation Tabs ═══ -->
            <div class="flex items-center justify-between border-b border-gray-200">
                <div class="flex space-x-1 sm:space-x-3">
                    <button @click="activeTab = 'leaderboard'"
                        :class="activeTab === 'leaderboard' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <span>🏆 Leaderboard & Progres Target</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-indigo-50 text-indigo-700">{{ leaderboard.length }}</span>
                    </button>
                    <button @click="activeTab = 'programs'"
                        :class="activeTab === 'programs' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <span>🎯 Program Target Pasar</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600">{{ programs.length }}</span>
                    </button>
                    <button @click="activeTab = 'transactions'"
                        :class="activeTab === 'transactions' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-3 px-3 text-xs sm:text-sm font-bold border-b-2 transition-all flex items-center gap-2 cursor-pointer">
                        <span>📋 Rincian Closing Pelanggan</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600">{{ logs.length }}</span>
                    </button>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════════
                 TAB 1: LEADERBOARD & TARGET PROGRESS
            ═════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'leaderboard'" class="space-y-6">
                <!-- Single Top Performer Banner (jika 1 orang) -->
                <div v-if="leaderboard.length === 1" class="bg-gradient-to-r from-amber-500 via-amber-600 to-yellow-500 rounded-3xl p-6 text-white shadow-lg shadow-amber-500/20 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4 text-center sm:text-left">
                        <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-3xl shrink-0 shadow-inner">
                            👑
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-white/25 text-white tracking-wider mb-1">
                                Top Performer Periode Ini
                            </div>
                            <h3 class="text-xl font-black text-white">{{ leaderboard[0].user_name }}</h3>
                            <p class="text-xs text-amber-100 capitalize">{{ leaderboard[0].user_role }} &bull; {{ leaderboard[0].closings_count }} Pelanggan Closing ({{ leaderboard[0].percentage }}% dari kuota target)</p>
                        </div>
                    </div>
                    <div class="text-center sm:text-right bg-white/10 backdrop-blur-md px-5 py-3 rounded-2xl border border-white/20">
                        <span class="text-[11px] text-amber-100 block font-semibold">Total Bonus Terkumpul:</span>
                        <span class="text-2xl font-black text-white font-mono">Rp {{ formatCurrency(leaderboard[0].total_reward) }}</span>
                        <div class="mt-1">
                            <span v-if="leaderboard[0].is_achieved" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-400 text-slate-900 shadow-xs">
                                🎯 TARGET TERCAPAI
                            </span>
                            <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white">
                                🚀 ON PROGRESS
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Podium Top 3 (jika 2 orang atau lebih) -->
                <div v-else-if="leaderboard.length >= 2" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                    <!-- Juara 2 -->
                    <div v-if="leaderboard[1]" class="bg-gradient-to-b from-slate-50 to-white rounded-3xl p-6 border border-slate-200 shadow-sm flex flex-col items-center text-center relative order-2 md:order-1">
                        <span class="w-8 h-8 rounded-full bg-slate-200 text-slate-700 font-black text-sm flex items-center justify-center mb-2 shadow-xs">2</span>
                        <h4 class="font-bold text-gray-900 text-base">{{ leaderboard[1].user_name }}</h4>
                        <span class="text-xs text-gray-500 capitalize mb-3">{{ leaderboard[1].user_role }}</span>
                        <div class="text-2xl font-black text-indigo-600 font-mono">{{ leaderboard[1].closings_count }} <span class="text-xs text-gray-400">Closing</span></div>
                        <div class="text-xs font-bold text-emerald-600 mt-1 font-mono">Rp {{ formatCurrency(leaderboard[1].total_reward) }}</div>
                    </div>

                    <!-- Juara 1 (Champion) -->
                    <div v-if="leaderboard[0]" class="bg-gradient-to-b from-amber-50/80 via-white to-white rounded-3xl p-6 border-2 border-amber-300 shadow-md flex flex-col items-center text-center relative order-1 md:order-2 scale-105 z-10">
                        <div class="absolute -top-3 px-3 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-yellow-500 text-white font-extrabold text-[10px] uppercase tracking-wider shadow-sm">
                            👑 TOP PERFORMER
                        </div>
                        <span class="w-10 h-10 rounded-full bg-amber-400 text-white font-black text-base flex items-center justify-center mb-2 shadow-sm mt-1">1</span>
                        <h4 class="font-bold text-gray-900 text-lg">{{ leaderboard[0].user_name }}</h4>
                        <span class="text-xs text-gray-500 capitalize mb-3">{{ leaderboard[0].user_role }}</span>
                        <div class="text-3xl font-black text-amber-600 font-mono">{{ leaderboard[0].closings_count }} <span class="text-xs text-gray-400">Closing</span></div>
                        <div class="text-sm font-extrabold text-emerald-600 mt-1 font-mono">Rp {{ formatCurrency(leaderboard[0].total_reward) }}</div>
                        <span v-if="leaderboard[0].is_achieved" class="mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">
                            🎯 TARGET TERCAPAI
                        </span>
                    </div>

                    <!-- Juara 3 -->
                    <div v-if="leaderboard[2]" class="bg-gradient-to-b from-orange-50/40 to-white rounded-3xl p-6 border border-orange-200 shadow-sm flex flex-col items-center text-center relative order-3">
                        <span class="w-8 h-8 rounded-full bg-orange-200 text-orange-800 font-black text-sm flex items-center justify-center mb-2 shadow-xs">3</span>
                        <h4 class="font-bold text-gray-900 text-base">{{ leaderboard[2].user_name }}</h4>
                        <span class="text-xs text-gray-500 capitalize mb-3">{{ leaderboard[2].user_role }}</span>
                        <div class="text-2xl font-black text-indigo-600 font-mono">{{ leaderboard[2].closings_count }} <span class="text-xs text-gray-400">Closing</span></div>
                        <div class="text-xs font-bold text-emerald-600 mt-1 font-mono">Rp {{ formatCurrency(leaderboard[2].total_reward) }}</div>
                    </div>
                </div>

                <!-- Tabel Detail Performa Karyawan -->
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Performa Pencapaian Target Karyawan</h3>
                            <p class="text-xs text-gray-400">Semua karyawan yang tercatat membawa pelanggan baru</p>
                        </div>
                        <span class="text-xs text-gray-500 font-medium">Target Bulanan: <strong>{{ kpi.target_quota }} Closing</strong></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs min-w-[980px]">
                            <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                                <tr>
                                    <th class="px-5 py-3.5">Peringkat & Karyawan</th>
                                    <th class="px-4 py-3.5">Progres Closing</th>
                                    <th class="px-4 py-3.5">Status Target</th>
                                    <th class="px-4 py-3.5">Bonus Closing</th>
                                    <th class="px-4 py-3.5">Bonus Wilayah/Paket</th>
                                    <th class="px-4 py-3.5">Bonus Tembus Kuota</th>
                                    <th class="px-5 py-3.5 text-right">Total Reward</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 font-medium">
                                <tr v-for="(row, idx) in leaderboard" :key="row.user_id" class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="w-6 h-6 rounded-lg flex items-center justify-center font-bold text-xs"
                                                :class="idx === 0 ? 'bg-amber-100 text-amber-700' : (idx === 1 ? 'bg-slate-100 text-slate-700' : (idx === 2 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500'))">
                                                {{ idx + 1 }}
                                            </span>
                                            <div>
                                                <p class="font-bold text-gray-900">{{ row.user_name }}</p>
                                                <p class="text-[11px] text-gray-400 capitalize">{{ row.user_role }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 min-w-[160px]">
                                        <div class="flex items-center justify-between text-[11px] mb-1">
                                            <span class="font-bold text-gray-800">{{ row.closings_count }} / {{ row.target_quota }} Pelanggan</span>
                                            <span class="font-bold font-mono" :class="row.is_achieved ? 'text-emerald-600' : 'text-indigo-600'">{{ row.percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-100 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-500"
                                                :class="row.is_achieved ? 'bg-gradient-to-r from-emerald-400 to-green-500' : 'bg-gradient-to-r from-indigo-500 to-blue-500'"
                                                :style="{ width: row.percentage + '%' }"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span v-if="row.is_achieved" class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            🎯 TERCAPAI
                                        </span>
                                        <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            🚀 ON PROGRESS
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 font-mono">
                                        Rp {{ formatCurrency(row.base_reward + row.tier_reward) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-gray-700 font-mono">
                                        Rp {{ formatCurrency(row.area_reward + row.package_reward) }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span v-if="row.target_achieved_reward > 0" class="font-bold text-emerald-600 font-mono">
                                            +Rp {{ formatCurrency(row.target_achieved_reward) }}
                                        </span>
                                        <span v-else class="text-gray-400 font-mono">-</span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm font-black text-gray-900 font-mono block">Rp {{ formatCurrency(row.total_reward) }}</span>
                                        <span v-if="row.is_synced" class="text-[10px] text-emerald-600 font-bold">✓ Sudah di Payroll</span>
                                        <span v-else class="text-[10px] text-amber-600 font-medium">Pending Cair</span>
                                    </td>
                                </tr>
                                <tr v-if="leaderboard.length === 0">
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-400 text-xs">
                                        Belum ada pencapaian closing yang tercatat pada periode ini.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════════
                 TAB 2: MANAJEMEN PROGRAM TARGET PASAR (4 KONSEP)
            ═════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'programs'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div v-for="prog in programs" :key="prog.id"
                        class="bg-white rounded-3xl p-6 border shadow-sm transition-all relative flex flex-col justify-between"
                        :class="prog.is_active ? 'border-gray-100 hover:shadow-md' : 'border-gray-200 bg-gray-50/50 opacity-75'">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wide"
                                    :class="getConceptBadgeClass(prog.concept)">
                                    {{ getConceptLabel(prog.concept) }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <button v-if="can('target_market_program_toggle') || can('target_market_manage')" @click="toggleProgram(prog)"
                                        :class="prog.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600'"
                                        class="px-2 py-0.5 rounded-md text-[10px] font-bold cursor-pointer">
                                        {{ prog.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </div>
                            </div>

                            <h4 class="text-base font-bold text-gray-900 mb-1 leading-snug">{{ prog.title }}</h4>
                            <p class="text-xs text-gray-500 mb-4">{{ prog.description || 'Tidak ada keterangan khusus.' }}</p>

                            <!-- Program Specs -->
                            <div class="bg-gray-50 rounded-2xl p-3.5 space-y-2 text-xs border border-gray-100">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Target Kuota:</span>
                                    <span class="font-bold text-gray-900">{{ prog.target_quota }} Pelanggan / Orang</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Reward Dasar / Closing:</span>
                                    <span class="font-bold text-indigo-600">Rp {{ formatCurrency(prog.reward_flat) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Bonus Tembus Kuota:</span>
                                    <span class="font-bold text-emerald-600">Rp {{ formatCurrency(prog.reward_bonus_target) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Target Peserta:</span>
                                    <span class="font-bold text-gray-700 capitalize">{{ prog.target_role || 'Semua Karyawan' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100 text-xs">
                            <button v-if="can('target_market_program_create') || can('target_market_manage')" @click="editProgram(prog)" class="text-indigo-600 hover:text-indigo-800 font-bold cursor-pointer">
                                Edit Aturan ⚙️
                            </button>
                            <span v-else></span>
                            <button v-if="can('target_market_program_delete') || can('target_market_manage')" @click="deleteProgram(prog)" class="text-red-500 hover:text-red-700 font-medium cursor-pointer">
                                Hapus
                            </button>
                        </div>
                    </div>

                    <!-- Card Buat Baru -->
                    <button v-if="can('target_market_program_create') || can('target_market_manage')" @click="openCreateModal"
                        class="border-2 border-dashed border-gray-200 hover:border-indigo-400 rounded-3xl p-8 flex flex-col items-center justify-center text-center group cursor-pointer transition-colors min-h-[220px]">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 group-hover:bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl mb-2 transition-colors">
                            ➕
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm">Tambah Program Target</h4>
                        <p class="text-xs text-gray-400 mt-1 max-w-[200px]">Pilih antara 4 konsep (Tier, Wilayah, Paket, atau Hybrid)</p>
                    </button>
                </div>
            </div>

            <!-- ════════════════════════════════════════════════════════════════
                 TAB 3: RINCIAN CLOSING PELANGGAN
            ═════════════════════════════════════════════════════════════════ -->
            <div v-if="activeTab === 'transactions'" class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Riwayat Aktivasi & Komisi Closing</h3>
                        <p class="text-xs text-gray-400">Daftar pelanggan aktivasi baru yang dihitung dalam program target</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50/80 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3.5">Pelanggan Closing</th>
                                <th class="px-4 py-3.5">Karyawan / Sales</th>
                                <th class="px-4 py-3.5">Paket & Area</th>
                                <th class="px-4 py-3.5">Program Target</th>
                                <th class="px-4 py-3.5">Rincian Bonus</th>
                                <th class="px-5 py-3.5 text-right">Total Bonus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 font-medium">
                            <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-gray-900">{{ log.customer?.name || 'Pelanggan #' + log.customer_id }}</p>
                                    <p class="text-[11px] text-gray-400">{{ log.customer?.customer_code || '-' }} · Tgl: {{ formatDate(log.customer?.activation_date) }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-bold text-gray-800">{{ log.user?.name }}</span>
                                    <span class="text-[10px] text-gray-400 block capitalize">{{ log.user?.role }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-semibold text-gray-800">{{ log.customer?.package?.name || '-' }}</span>
                                    <span class="text-[10px] text-gray-400 block">{{ log.customer?.area_model?.name || '-' }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="text-xs font-semibold text-gray-700">{{ log.program?.title }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-[11px] text-gray-600">
                                    <div class="space-y-0.5">
                                        <div v-if="log.base_reward > 0">Dasar: Rp {{ formatCurrency(log.base_reward) }}</div>
                                        <div v-if="log.tier_reward > 0" class="text-blue-600 font-semibold">+Tier: Rp {{ formatCurrency(log.tier_reward) }}</div>
                                        <div v-if="log.area_reward > 0" class="text-purple-600 font-semibold">+Area: Rp {{ formatCurrency(log.area_reward) }}</div>
                                        <div v-if="log.package_reward > 0" class="text-cyan-600 font-semibold">+Paket: Rp {{ formatCurrency(log.package_reward) }}</div>
                                        <div v-if="log.target_achieved_reward > 0" class="text-emerald-600 font-bold">🏆 Bonus Target: Rp {{ formatCurrency(log.target_achieved_reward) }}</div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <span class="text-sm font-black text-gray-900 block">Rp {{ formatCurrency(log.total_reward) }}</span>
                                    <span v-if="log.status === 'synced_payroll'" class="text-[10px] text-emerald-600 font-bold">✓ Synced Payroll</span>
                                    <span v-else class="text-[10px] text-amber-600 font-medium">Approved</span>
                                </td>
                            </tr>
                            <tr v-if="logs.length === 0">
                                <td colspan="6" class="px-5 py-10 text-center text-gray-400 text-xs">
                                    Belum ada data closing pelanggan tercatat pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════════
             MODAL BUAT / EDIT PROGRAM TARGET (DENGAN 4 KONSEP LENGKAP)
        ═════════════════════════════════════════════════════════════════ -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ isEditing ? 'Edit Program Target Pasar' : 'Buat Program Target Pasar Baru' }}</h3>
                        <p class="text-xs text-gray-400">Atur skema bonus berbasis target market untuk seluruh karyawan</p>
                    </div>
                    <button @click="closeModal" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-700 cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitProgram" class="space-y-4">
                    <!-- Judul Program -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Program Target</label>
                        <input v-model="form.title" type="text" required placeholder="Contoh: Program Akselerasi Q4, Target Wilayah Baru"
                            class="w-full text-xs font-semibold rounded-xl border-gray-200 py-2.5 px-3 focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>

                    <!-- Pilihan 4 Konsep Perhitungan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-2">Pilih Konsep Perhitungan Program:</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <!-- Konsep 1: Tier Volume -->
                            <label class="p-3 rounded-2xl border cursor-pointer transition-all flex items-start gap-2.5"
                                :class="form.concept === 'milestone_volume' ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="milestone_volume" v-model="form.concept" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <p class="text-xs font-bold text-gray-900">📈 Tier / Milestone Volume</p>
                                    <p class="text-[11px] text-gray-500">Bonus bertingkat sesuai akumulasi jumlah closing (misal: 1-10, 11-20, >20)</p>
                                </div>
                            </label>

                            <!-- Konsep 2: Penetrasi Area -->
                            <label class="p-3 rounded-2xl border cursor-pointer transition-all flex items-start gap-2.5"
                                :class="form.concept === 'area_expansion' ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="area_expansion" v-model="form.concept" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <p class="text-xs font-bold text-gray-900">📍 Penetrasi Area / ODP Target</p>
                                    <p class="text-[11px] text-gray-500">Bonus ekstra jika aktivasi di kawasan target prioritas / ODP baru</p>
                                </div>
                            </label>

                            <!-- Konsep 3: Nilai Paket -->
                            <label class="p-3 rounded-2xl border cursor-pointer transition-all flex items-start gap-2.5"
                                :class="form.concept === 'package_tier' ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="package_tier" v-model="form.concept" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <p class="text-xs font-bold text-gray-900">💎 Nilai Paket / Revenue</p>
                                    <p class="text-[11px] text-gray-500">Bonus disesuaikan dengan tingginya nilai paket bulanan pelanggan</p>
                                </div>
                            </label>

                            <!-- Konsep 4: Hybrid -->
                            <label class="p-3 rounded-2xl border cursor-pointer transition-all flex items-start gap-2.5"
                                :class="form.concept === 'hybrid' ? 'border-indigo-600 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" value="hybrid" v-model="form.concept" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <p class="text-xs font-bold text-gray-900">⚡ Hybrid Smart Target</p>
                                    <p class="text-[11px] text-gray-500">Kombinasi fleksibel: kuota bertingkat + bonus wilayah + tipe paket</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Target Kuota & Target Role -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Target Kuota Closing (Pelanggan)</label>
                            <input v-model.number="form.target_quota" type="number" min="1" required
                                class="w-full text-xs font-semibold rounded-xl border-gray-200 py-2.5 px-3 focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="text-[10px] text-gray-400 mt-0.5">Jumlah closing untuk mencapai predikat Target Tercapai</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Peserta Karyawan</label>
                            <select v-model="form.target_role" class="w-full text-xs font-semibold rounded-xl border-gray-200 py-2.5 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                                <option :value="null">Semua Karyawan (Seluruh Divisi)</option>
                                <option value="sales">Khusus Sales & Marketing</option>
                                <option value="teknisi">Khusus Teknisi</option>
                                <option value="freelance">Khusus Mitra Freelance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Reward Dasar & Bonus Tembus Target -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Bonus Dasar per Closing (Rp)</label>
                            <input v-model.number="form.reward_flat" type="number" min="0" required
                                class="w-full text-xs font-semibold rounded-xl border-gray-200 py-2.5 px-3 focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="text-[10px] text-gray-400 mt-0.5">Bonus flat yang langsung didapat di tiap pelanggan baru</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Bonus Tembus Kuota / Lump Sum (Rp)</label>
                            <input v-model.number="form.reward_bonus_target" type="number" min="0"
                                class="w-full text-xs font-semibold rounded-xl border-gray-200 py-2.5 px-3 focus:ring-indigo-500 focus:border-indigo-500" />
                            <p class="text-[10px] text-gray-400 mt-0.5">Bonus ekstra yang cair sekaligus saat target kuota terpenuhi</p>
                        </div>
                    </div>

                    <!-- Kustomisasi Aturan Konsep (Tier / Area / Paket) -->
                    <div v-if="form.concept === 'milestone_volume' || form.concept === 'hybrid'" class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-800">📈 Aturan Jenjang Tier Closing</span>
                            <button type="button" @click="addTierRow" class="text-[11px] text-indigo-600 font-bold hover:underline cursor-pointer">+ Tambah Jenjang</button>
                        </div>
                        <div class="space-y-1.5">
                            <div v-for="(tier, tIdx) in form.rules.tiers" :key="tIdx" class="flex items-center gap-2">
                                <span class="text-[11px] text-gray-500">Closing</span>
                                <input v-model.number="tier.min" type="number" placeholder="Min" class="w-16 text-xs rounded-lg border-gray-200 p-1.5 text-center" />
                                <span class="text-[11px] text-gray-500">s/d</span>
                                <input v-model.number="tier.max" type="number" placeholder="Maks" class="w-16 text-xs rounded-lg border-gray-200 p-1.5 text-center" />
                                <span class="text-[11px] text-gray-500">: Bonus Rp</span>
                                <input v-model.number="tier.reward" type="number" placeholder="Reward" class="w-24 text-xs rounded-lg border-gray-200 p-1.5 text-right font-bold" />
                                <button type="button" @click="removeTierRow(tIdx)" class="text-red-400 hover:text-red-600 text-xs">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Catatan / Deskripsi Program</label>
                        <textarea v-model="form.description" rows="2" placeholder="Catatan syarat ketentuan program..."
                            class="w-full text-xs rounded-xl border-gray-200 p-2.5 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button type="button" @click="closeModal" class="px-4 py-2 rounded-xl text-xs font-bold bg-gray-100 text-gray-600 hover:bg-gray-200 cursor-pointer">Batal</button>
                        <button type="submit" :disabled="form.processing"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm shadow-indigo-200 cursor-pointer disabled:opacity-50">
                            {{ isEditing ? 'Simpan Perubahan' : 'Terbitkan Program' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const { can, hasRole } = usePermission();

const props = defineProps({
    month: Number,
    year: Number,
    tab: String,
    programs: Array,
    logs: Array,
    leaderboard: Array,
    kpi: Object,
    master: Object,
});

const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const selectedMonth = ref(props.month);
const selectedYear = ref(props.year);
const activeTab = ref(props.tab || 'leaderboard');

const processingRecalc = ref(false);
const processingSync = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const editingProgramId = ref(null);

const form = useForm({
    title: '',
    concept: 'hybrid',
    period_month: props.month,
    period_year: props.year,
    target_quota: 15,
    target_role: null,
    reward_flat: 25000,
    reward_bonus_target: 500000,
    rules: {
        tiers: [
            { min: 1, max: 5, reward: 25000 },
            { min: 6, max: 15, reward: 50000 },
            { min: 16, max: null, reward: 75000 },
        ],
        target_areas: [],
        target_packages: [],
    },
    description: '',
    auto_disburse: true,
});

function changePeriod() {
    router.get('/bonus-target-market', {
        month: selectedMonth.value,
        year: selectedYear.value,
        tab: activeTab.value,
    }, { preserveState: true, replace: true });
}

function triggerRecalculate() {
    processingRecalc.value = true;
    router.post('/bonus-target-market/recalculate', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        onFinish: () => {
            processingRecalc.value = false;
        },
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Perhitungan Selesai!',
                text: 'Seluruh bonus closing pelanggan berhasil dihitung ulang.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }
    });
}

function triggerSyncPayroll() {
    Swal.fire({
        title: 'Cairkan Bonus ke Payroll?',
        html: `
            <div class="text-left text-xs text-slate-600 space-y-2 mt-2">
                <p>Akumulasi bonus target market periode <b>${months[selectedMonth.value - 1]} ${selectedYear.value}</b> akan ditransfer ke modul <b>Insentif Payroll</b> karyawan.</p>
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <span class="text-[11px] text-emerald-800 font-semibold">Total Bonus yang Ditransfer:</span>
                    <div class="text-lg font-black text-emerald-700 font-mono mt-0.5">
                        Rp ${formatCurrency(props.kpi.total_bonus)}
                    </div>
                    <div class="text-[10px] text-emerald-600 mt-1">
                        Untuk ${props.kpi.total_employees} karyawan pencatat penjualan closing
                    </div>
                </div>
                <p class="text-[11px] text-slate-400 italic">Bonus yang masuk ke Payroll akan otomatis dibayarkan saat penggajian bulanan karyawan dicairkan.</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '⚡ Ya, Cairkan ke Payroll',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b'
    }).then((res) => {
        if (res.isConfirmed) {
            processingSync.value = true;
            router.post('/bonus-target-market/sync-payroll', {
                month: selectedMonth.value,
                year: selectedYear.value,
            }, {
                onFinish: () => {
                    processingSync.value = false;
                },
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Berhasil Dicairkan ke Payroll!',
                        text: 'Bonus berhasil ditransfer ke modul Insentif Payroll.',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err || {})[0] || 'Gagal menyinkronkan bonus ke Payroll.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Sinkronisasi Gagal',
                        text: msg,
                        confirmButtonColor: '#dc2626'
                    });
                }
            });
        }
    });
}

function openCreateModal() {
    isEditing.value = false;
    editingProgramId.value = null;
    form.reset();
    form.period_month = selectedMonth.value;
    form.period_year = selectedYear.value;
    form.rules = {
        tiers: [
            { min: 1, max: 5, reward: 25000 },
            { min: 6, max: 15, reward: 50000 },
            { min: 16, max: null, reward: 75000 },
        ],
        target_areas: [],
        target_packages: [],
    };
    showModal.value = true;
}

function editProgram(prog) {
    isEditing.value = true;
    editingProgramId.value = prog.id;
    form.title = prog.title;
    form.concept = prog.concept;
    form.period_month = prog.period_month;
    form.period_year = prog.period_year;
    form.target_quota = prog.target_quota;
    form.target_role = prog.target_role;
    form.reward_flat = Number(prog.reward_flat);
    form.reward_bonus_target = Number(prog.reward_bonus_target);
    form.rules = prog.rules || { tiers: [], target_areas: [], target_packages: [] };
    form.description = prog.description || '';
    form.auto_disburse = prog.auto_disburse;
    showModal.value = true;
}

function toggleProgram(prog) {
    const nextStatus = !prog.is_active;
    router.post(`/bonus-target-market/programs/${prog.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: nextStatus ? 'Program Diaktifkan' : 'Program Dinonaktifkan',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }
    });
}

function deleteProgram(prog) {
    Swal.fire({
        title: 'Hapus Program Target?',
        text: `Program '${prog.title}' akan dihapus dan bonus periode ini akan dihitung ulang.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus Program',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(`/bonus-target-market/programs/${prog.id}/destroy`, {}, {
                preserveScroll: true,
                onSuccess: (page) => {
                    const flash = page?.props?.flash;
                    if (flash?.error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: flash.error
                        });
                        return;
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Program Dihapus!',
                        text: flash?.success || `Program '${prog.title}' berhasil dihapus.`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                },
                onError: (errors) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menghapus',
                        text: errors?.message || Object.values(errors)[0] || 'Terjadi kesalahan saat menghapus program.'
                    });
                }
            });
        }
    });
}

function closeModal() {
    showModal.value = false;
}

function addTierRow() {
    if (!form.rules.tiers) form.rules.tiers = [];
    form.rules.tiers.push({ min: 1, max: 10, reward: 25000 });
}

function removeTierRow(idx) {
    form.rules.tiers.splice(idx, 1);
}

function submitProgram() {
    if (isEditing.value && editingProgramId.value) {
        form.post(`/bonus-target-market/programs/${editingProgramId.value}/update`, {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Program Diperbarui!',
                    text: 'Perubahan program target berhasil disimpan.',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            },
        });
    } else {
        form.post('/bonus-target-market/programs', {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Program Diterbitkan!',
                    text: 'Program target pasar baru berhasil dibuat.',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            },
        });
    }
}

function formatCurrency(val) {
    return Number(val || 0).toLocaleString('id-ID');
}

function formatDate(val) {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function getConceptLabel(concept) {
    const map = {
        milestone_volume: 'Tier / Milestone Volume',
        area_expansion: 'Penetrasi Area / ODP',
        package_tier: 'Nilai Paket / Revenue',
        hybrid: 'Hybrid Smart Target',
    };
    return map[concept] || concept;
}

function getConceptBadgeClass(concept) {
    const map = {
        milestone_volume: 'bg-blue-50 text-blue-700 border border-blue-200',
        area_expansion: 'bg-purple-50 text-purple-700 border border-purple-200',
        package_tier: 'bg-cyan-50 text-cyan-700 border border-cyan-200',
        hybrid: 'bg-amber-50 text-amber-800 border border-amber-200',
    };
    return map[concept] || 'bg-gray-100 text-gray-700';
}
</script>
