<template>
    <AppLayout title="Gaji, Insentif & BPJS" subtitle="Manajemen Gaji, BPJS Ketenagakerjaan & Kesehatan, Bonus, dan Pencairan">
        <div class="max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6 font-sans">
            
            <!-- Flash Notification -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-sm font-medium shadow-xs flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-500 font-bold text-base">✓</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-sm font-medium shadow-xs flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-rose-500 font-bold text-base">⚠️</span>
                    <span>{{ $page.props.flash.error }}</span>
                </div>
            </div>

            <!-- Header Filter & Quick Action Bar -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-5 rounded-3xl shadow-sm border border-slate-100">
                <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-2xl text-xl">
                            💼
                        </div>
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900">Periode Gaji &amp; BPJS</h2>
                            <p class="text-xs text-slate-500">Pilih bulan penggajian</p>
                        </div>
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <select v-model="form.month" class="block w-full sm:w-36 border-slate-200 bg-slate-50 focus:bg-white rounded-xl text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 py-2">
                            <option v-for="(m, i) in months" :key="i" :value="i + 1">{{ m }}</option>
                        </select>
                        <select v-model="form.year" class="block w-full sm:w-28 border-slate-200 bg-slate-50 focus:bg-white rounded-xl text-xs font-bold focus:ring-indigo-500 focus:border-indigo-500 py-2">
                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                        </select>
                        <button @click="applyFilter" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xs transition-all">
                            Tampilkan
                        </button>
                    </div>
                </div>

                <!-- Tombol Pengaturan BPJS & Batch Disburse -->
                <div class="flex items-center gap-2 w-full lg:w-auto justify-end flex-wrap">
                    <a
                        v-if="can('payroll_btn_auto_transfer')"
                        :href="route('settings.payroll')"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 border shadow-2xs"
                        :class="activeProvider === 'flip' && flipConfig?.enabled 
                            ? 'bg-orange-50 border-orange-200 text-orange-700 hover:bg-orange-100' 
                            : (activeProvider === 'xendit' && xenditConfig?.enabled 
                                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100' 
                                : 'bg-white hover:bg-slate-50 border-slate-300 text-slate-700')"
                        :title="activeProvider === 'flip' && flipConfig?.enabled 
                            ? 'Auto Transfer Flip Aktif (' + flipConfig.environment + ')' 
                            : (activeProvider === 'xendit' && xenditConfig?.enabled 
                                ? 'Auto Transfer Xendit Aktif (' + xenditConfig.environment + ')' 
                                : 'Atur Flip for Business atau Xendit')"
                    >
                        <span>{{ activeProvider === 'flip' && flipConfig?.enabled ? '🟠' : (activeProvider === 'xendit' && xenditConfig?.enabled ? '⚡' : '⚙️') }}</span>
                        <span>
                            {{ activeProvider === 'flip' && flipConfig?.enabled 
                                ? 'Flip: Aktif (' + flipConfig.environment + ')' 
                                : (activeProvider === 'xendit' && xenditConfig?.enabled 
                                    ? 'Xendit: Aktif (' + xenditConfig.environment + ')' 
                                    : 'Setup Auto Transfer') 
                            }}
                        </span>
                    </a>

                    <button
                        v-if="can('payroll_btn_settings_bpjs')"
                        type="button"
                        @click="showSettingsModal = true"
                        class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 shadow-2xs"
                    >
                        <span>⚙️</span>
                        <span>Tarif BPJS &amp; Jadwal Cair</span>
                    </button>

                    <button
                        v-if="(can('payroll_disburse_all') || can('payroll_disburse')) && totalPendingCount > 0"
                        type="button"
                        @click="disburseAll"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-extrabold shadow-sm transition-all flex items-center gap-1.5"
                    >
                        <span>⚡</span>
                        <span>Cairkan Semua ({{ totalPendingCount }} Staf)</span>
                    </button>
                </div>
            </div>

            <!-- KPI Cards Overview (Payroll & BPJS) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Total Net Salary -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Gaji Bersih (Net)</div>
                        <div class="text-xl font-black text-slate-900 font-mono mt-1">Rp {{ formatRupiah(totalNetSalary) }}</div>
                        <span class="text-[11px] text-slate-400 font-medium">Take Home Pay staf</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                        💰
                    </div>
                </div>

                <!-- 2. Potongan BPJS Karyawan -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-rose-500 uppercase tracking-wider">Iuran BPJS Karyawan</div>
                        <div class="text-xl font-black text-rose-600 font-mono mt-1">Rp {{ formatRupiah(recapBpjs.total_deducted_employee) }}</div>
                        <span class="text-[11px] text-slate-400 font-medium">Dipotong dari gaji staf</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                        📉
                    </div>
                </div>

                <!-- 3. Tunjangan BPJS Perusahaan -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">Tunjangan BPJS Kantor</div>
                        <div class="text-xl font-black text-purple-700 font-mono mt-1">Rp {{ formatRupiah(recapBpjs.total_contributed_company) }}</div>
                        <span class="text-[11px] text-slate-400 font-medium">Beban ditanggung kantor</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                        🏢
                    </div>
                </div>

                <!-- 4. Total Setoran ke Kas BPJS -->
                <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Total Setoran BPJS</div>
                        <div class="text-xl font-black text-emerald-600 font-mono mt-1">Rp {{ formatRupiah(recapBpjs.grand_total_payable) }}</div>
                        <span class="text-[11px] text-emerald-700 font-medium font-mono">{{ recapBpjs.enrolled_kesehatan_count }} KS &bull; {{ recapBpjs.enrolled_tk_count }} TK</span>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        🛡️
                    </div>
                </div>
            </div>

            <!-- DAFTAR PENGGAJIAN KARYAWAN -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <!-- Header List & Search -->
                <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <span>👥</span>
                            <span>Daftar Penggajian &amp; BPJS Karyawan</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Rincian gaji pokok, bonus/insentif, potongan BPJS, rekening transfer, dan pencairan</p>
                    </div>
                    <div class="relative w-full sm:w-72">
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari nama karyawan / jabatan..." 
                            class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 focus:bg-white transition-all"
                        />
                        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs min-w-[1240px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="px-5 py-3.5">Karyawan &amp; Status BPJS</th>
                                <th class="px-5 py-3.5">Rekening Transfer</th>
                                <th class="px-5 py-3.5">Gaji Pokok</th>
                                <th class="px-5 py-3.5">Insentif &amp; Bonus</th>
                                <th class="px-5 py-3.5">Potongan (BPJS &amp; Kasbon)</th>
                                <th class="px-5 py-3.5">Gaji Bersih (Net)</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-4 py-3.5 text-center sticky right-0 bg-slate-50/95 backdrop-blur-xs shadow-[-6px_0_10px_-4px_rgba(0,0,0,0.05)] z-20 min-w-[310px]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-slate-50/70 transition-colors group">
                                <!-- Karyawan & Status BPJS -->
                                <td class="px-5 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-extrabold text-xs shadow-2xs shrink-0 mt-0.5">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 text-sm">{{ user.name }}</div>
                                            <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 capitalize">
                                                    {{ user.role }}
                                                </span>
                                                <span v-if="user.bpjs_kesehatan" class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Terdaftar BPJS Kesehatan">
                                                    🛡️ BPJS KS
                                                </span>
                                                <span v-if="user.bpjs_ketenagakerjaan" class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200" title="Terdaftar BPJS Ketenagakerjaan">
                                                    🛡️ BPJS TK
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Rekening Transfer -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div v-if="user.bank_account_number">
                                        <span class="font-bold text-slate-800">{{ user.bank_name || 'Bank' }}</span>
                                        <div class="text-[11px] font-mono text-indigo-700 font-bold">{{ user.bank_account_number }}</div>
                                        <div class="text-[10px] text-slate-400">a.n {{ user.bank_account_holder }}</div>
                                    </div>
                                    <span v-else class="text-[11px] text-slate-400 italic">Belum disetel</span>
                                </td>

                                <!-- Gaji Pokok -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <button 
                                        type="button"
                                        @click="openBpjsBankModal(user)" 
                                        class="group flex items-center gap-1.5 text-left transition-transform hover:scale-[1.02]"
                                        title="Klik untuk ubah Gaji Pokok & BPJS"
                                    >
                                        <span class="font-extrabold font-mono" :class="user.base_salary > 0 ? 'text-slate-900' : 'text-amber-600'">
                                            Rp {{ formatRupiah(user.base_salary) }}
                                        </span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <span v-if="user.base_salary <= 0" class="text-[10px] text-amber-600 font-medium block">
                                        Klik untuk isi gaji
                                    </span>
                                </td>

                                <!-- Insentif & Bonus -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-emerald-600 font-mono">
                                        + Rp {{ formatRupiah(user.total_auto_incentive + user.total_manual_incentive) }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 whitespace-nowrap">
                                        <span>Auto: +{{ formatRupiah(user.total_auto_incentive) }}</span>
                                        <span v-if="user.total_manual_incentive > 0" class="text-emerald-700 font-bold ml-1">
                                            &bull; Bonus: +{{ formatRupiah(user.total_manual_incentive) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Potongan (BPJS & Kasbon) -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-rose-600 font-mono">
                                        - Rp {{ formatRupiah(user.total_deduction + (user.bpjs?.total_employee || 0)) }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5 flex flex-col gap-0.5 whitespace-nowrap">
                                        <span v-if="user.bpjs?.total_employee > 0" class="text-rose-700 font-semibold">
                                            ↳ BPJS: -Rp {{ formatRupiah(user.bpjs.total_employee) }}
                                        </span>
                                        <span v-if="user.total_deduction > 0" class="text-slate-500">
                                            ↳ Kasbon/Lain: -Rp {{ formatRupiah(user.total_deduction) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Gaji Bersih (Net) -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="inline-flex items-center px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-100 shadow-2xs">
                                        <span class="text-xs font-black text-indigo-700 font-mono">
                                            Rp {{ formatRupiah(user.net_salary) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span v-if="user.is_paid" class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border border-emerald-300">
                                        <span>✓</span>
                                        <span>SUDAH CAIR</span>
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border border-amber-300">
                                        <span>⏳</span>
                                        <span>MENUNGGU</span>
                                    </span>

                                    <!-- Status Flip jika ada -->
                                    <div v-if="user.disbursement_channel === 'flip'" class="mt-1">
                                        <span 
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-extrabold"
                                            :class="{
                                                'bg-orange-50 text-orange-700 border border-orange-200': user.disbursement_status === 'COMPLETED',
                                                'bg-amber-50 text-amber-700 border border-amber-200': user.disbursement_status === 'PENDING',
                                                'bg-rose-50 text-rose-700 border border-rose-200': user.disbursement_status === 'FAILED',
                                            }"
                                            :title="user.disbursement_error || ('Flip Ref: ' + user.disbursement_id)"
                                        >
                                            <span>🟠 Flip: {{ user.disbursement_status || 'PENDING' }}</span>
                                        </span>
                                    </div>

                                    <!-- Status Xendit jika ada -->
                                    <div v-if="user.disbursement_channel === 'xendit'" class="mt-1">
                                        <span 
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-extrabold"
                                            :class="{
                                                'bg-indigo-50 text-indigo-700 border border-indigo-200': user.disbursement_status === 'COMPLETED',
                                                'bg-amber-50 text-amber-700 border border-amber-200': user.disbursement_status === 'PENDING',
                                                'bg-rose-50 text-rose-700 border border-rose-200': user.disbursement_status === 'FAILED',
                                            }"
                                            :title="user.disbursement_error || ('Xendit Ref: ' + user.disbursement_id)"
                                        >
                                            <span>⚡ Xendit: {{ user.disbursement_status || 'PENDING' }}</span>
                                        </span>
                                    </div>
                                    <div v-if="user.is_paid && user.payment_date" class="text-[9px] text-slate-400 mt-0.5">
                                        {{ new Date(user.payment_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-4 whitespace-nowrap text-right sticky right-0 bg-white group-hover:bg-slate-50 transition-colors shadow-[-6px_0_10px_-4px_rgba(0,0,0,0.05)] z-10 min-w-[310px]">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Tombol Lihat Slip Gaji -->
                                        <button
                                            v-if="can('payroll_btn_print_slip')"
                                            type="button"
                                            @click="openSlipModal(user)"
                                            class="px-2.5 py-1 text-slate-700 hover:text-indigo-700 bg-slate-100 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 rounded-lg text-xs font-bold transition-colors shadow-2xs"
                                            title="Lihat Rincian Slip Gaji"
                                        >
                                            📄 Slip
                                        </button>

                                        <!-- Tombol Edit Gaji Pokok, BPJS & Rekening -->
                                        <button
                                            v-if="can('payroll_btn_update_bpjs_bank') || can('payroll_btn_settings_bpjs') || can('payroll_manage')"
                                            type="button"
                                            @click="openBpjsBankModal(user)"
                                            class="px-2.5 py-1 text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg text-xs font-bold transition-colors shadow-2xs flex items-center gap-1"
                                            title="Atur Gaji Pokok, BPJS & Rekening"
                                        >
                                            ⚙️ Gaji/BPJS
                                        </button>

                                        <!-- Tombol Bonus & Potongan -->
                                        <template v-if="!user.is_paid">
                                            <button 
                                                v-if="can('payroll_incentives') || can('payroll_manage')"
                                                type="button"
                                                @click="openManualModal(user, 'incentive')" 
                                                class="px-2 py-1 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-xs font-bold transition-colors shadow-2xs"
                                                title="Tambah Bonus Manual"
                                            >
                                                +Bonus
                                            </button>
                                            <button 
                                                v-if="can('payroll_deductions') || can('payroll_incentives') || can('payroll_manage')"
                                                type="button"
                                                @click="openManualModal(user, 'deduction')" 
                                                class="px-2 py-1 text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg text-xs font-bold transition-colors shadow-2xs"
                                                title="Tambah Potongan Kasbon"
                                            >
                                                -Kasbon
                                            </button>
                                            <button 
                                                v-if="can('payroll_disburse')"
                                                type="button"
                                                @click="disburse(user)" 
                                                class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition-all active:scale-95"
                                                title="Cairkan Gaji Staf Ini"
                                            >
                                                ⚡ Cairkan
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    Tidak ada data karyawan ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── MODAL 1: RINCIAN & SLIP GAJI KARYAWAN ── -->
            <div v-if="showSlipModal && selectedSlipUser" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showSlipModal = false">
                <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-100 font-sans print:shadow-none print:max-w-full">
                    <!-- Slip Header -->
                    <div class="p-6 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-300">Slip Gaji &amp; Komisi Resmi</span>
                            <h3 class="text-lg font-black text-white mt-0.5">Periode: {{ months[month - 1] }} {{ year }}</h3>
                            <p class="text-xs text-slate-300">{{ selectedSlipUser.name }} &bull; {{ selectedSlipUser.role }}</p>
                        </div>
                        <button type="button" @click="showSlipModal = false" class="text-white/60 hover:text-white p-1">✕</button>
                    </div>

                    <!-- Slip Body -->
                    <div class="p-6 space-y-4 text-xs">
                        <!-- Rekening Penerima -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex justify-between items-center">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Rekening Tujuan:</span>
                                <div class="font-extrabold text-slate-800 text-sm">
                                    {{ selectedSlipUser.bank_name || 'Bank Belum Diatur' }} - {{ selectedSlipUser.bank_account_number || '-' }}
                                </div>
                                <span class="text-[11px] text-slate-500">a.n {{ selectedSlipUser.bank_account_holder }}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase" :class="selectedSlipUser.is_paid ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                {{ selectedSlipUser.is_paid ? 'Sudah Ditransfer' : 'Menunggu Transfer' }}
                            </span>
                        </div>

                        <!-- 1. Penerimaan / Pendapatan -->
                        <div class="space-y-1.5 border border-slate-100 rounded-2xl p-4 bg-white">
                            <span class="font-extrabold text-xs text-slate-900 uppercase tracking-wider block mb-2">A. Penerimaan (Gaji &amp; Bonus)</span>
                            <div class="flex justify-between text-slate-700">
                                <span>Gaji Pokok:</span>
                                <span class="font-bold font-mono">Rp {{ formatRupiah(selectedSlipUser.base_salary) }}</span>
                            </div>
                            <div class="flex justify-between text-slate-700">
                                <span>Insentif Booking &amp; Pasif Afiliasi Otomatis:</span>
                                <span class="font-bold text-emerald-600 font-mono">+ Rp {{ formatRupiah(selectedSlipUser.total_auto_incentive) }}</span>
                            </div>
                            <div v-if="selectedSlipUser.total_manual_incentive > 0" class="flex justify-between text-slate-700">
                                <span>Bonus Tambahan:</span>
                                <span class="font-bold text-emerald-600 font-mono">+ Rp {{ formatRupiah(selectedSlipUser.total_manual_incentive) }}</span>
                            </div>
                            <div class="flex justify-between font-extrabold text-slate-900 pt-2 border-t border-slate-100">
                                <span>Total Penghasilan Kotor:</span>
                                <span class="font-mono text-emerald-700">Rp {{ formatRupiah(selectedSlipUser.base_salary + selectedSlipUser.total_incentive) }}</span>
                            </div>
                        </div>

                        <!-- 2. Potongan (BPJS & Kasbon) -->
                        <div class="space-y-1.5 border border-slate-100 rounded-2xl p-4 bg-white">
                            <span class="font-extrabold text-xs text-slate-900 uppercase tracking-wider block mb-2">B. Potongan (Iuran BPJS &amp; Kasbon)</span>
                            <div v-if="selectedSlipUser.bpjs?.has_kesehatan" class="flex justify-between text-slate-700">
                                <span>BPJS Kesehatan Karyawan (1%):</span>
                                <span class="font-bold text-rose-600 font-mono">- Rp {{ formatRupiah(selectedSlipUser.bpjs.kesehatan_employee) }}</span>
                            </div>
                            <div v-if="selectedSlipUser.bpjs?.has_tk" class="flex justify-between text-slate-700">
                                <span>BPJS Ketenagakerjaan JHT Karyawan (2%):</span>
                                <span class="font-bold text-rose-600 font-mono">- Rp {{ formatRupiah(selectedSlipUser.bpjs.tk_jht_employee) }}</span>
                            </div>
                            <div v-if="selectedSlipUser.bpjs?.has_tk" class="flex justify-between text-slate-700">
                                <span>BPJS Ketenagakerjaan JP Karyawan (1%):</span>
                                <span class="font-bold text-rose-600 font-mono">- Rp {{ formatRupiah(selectedSlipUser.bpjs.tk_jp_employee) }}</span>
                            </div>
                            <div v-if="selectedSlipUser.total_deduction > 0" class="flex justify-between text-slate-700">
                                <span>Potongan Kasbon / Lainnya:</span>
                                <span class="font-bold text-rose-600 font-mono">- Rp {{ formatRupiah(selectedSlipUser.total_deduction) }}</span>
                            </div>
                            <div class="flex justify-between font-extrabold text-slate-900 pt-2 border-t border-slate-100">
                                <span>Total Potongan:</span>
                                <span class="font-mono text-rose-700">- Rp {{ formatRupiah(selectedSlipUser.total_deduction + (selectedSlipUser.bpjs?.total_employee || 0)) }}</span>
                            </div>
                        </div>

                        <!-- 3. Tunjangan BPJS Perusahaan (Informasi Tambahan) -->
                        <div v-if="selectedSlipUser.bpjs?.total_company > 0" class="p-3 bg-purple-50/70 border border-purple-200/80 rounded-2xl space-y-1 text-[11px] text-purple-900">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-purple-700 block">C. Ditanggung Perusahaan (Benefit Tambahan):</span>
                            <div class="flex justify-between">
                                <span>BPJS Kesehatan Kantor (4%):</span>
                                <span class="font-bold font-mono">Rp {{ formatRupiah(selectedSlipUser.bpjs.kesehatan_company) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>BPJS TK Kantor (JHT 3.7% + JP 2% + JKK 0.24% + JKM 0.3%):</span>
                                <span class="font-bold font-mono">Rp {{ formatRupiah(selectedSlipUser.bpjs.tk_jht_company + selectedSlipUser.bpjs.tk_jp_company + selectedSlipUser.bpjs.tk_jkk_company + selectedSlipUser.bpjs.tk_jkm_company) }}</span>
                            </div>
                            <div class="flex justify-between font-bold pt-1 border-t border-purple-200">
                                <span>Total Iuran BPJS Dibayar Kantor ke Kas BPJS:</span>
                                <span class="font-mono">Rp {{ formatRupiah(selectedSlipUser.bpjs.grand_total_bpjs) }}</span>
                            </div>
                        </div>

                        <!-- 4. Gaji Bersih Diterima (Take Home Pay) -->
                        <div class="p-4 bg-indigo-50 border-2 border-indigo-200 rounded-2xl flex justify-between items-center text-sm font-black">
                            <span class="text-indigo-950 uppercase">Gaji Bersih Diterima (Take Home Pay):</span>
                            <span class="text-xl text-indigo-700 font-mono">Rp {{ formatRupiah(selectedSlipUser.net_salary) }}</span>
                        </div>
                    </div>

                    <!-- Slip Footer -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                        <button type="button" @click="showSlipModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-100">
                            Tutup
                        </button>
                        <button
                            v-if="can('payroll_btn_print_slip')"
                            type="button"
                            @click="printSlip"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                        >
                            <span>🖨️</span>
                            <span>Cetak Slip</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── MODAL 2: EDIT BPJS & REKENING KARYAWAN ── -->
            <div v-if="showBpjsBankModal && selectedBpjsUser" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showBpjsBankModal = false">
                <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Pengaturan Gaji Pokok, BPJS &amp; Rekening</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Karyawan: <strong>{{ selectedBpjsUser.name }}</strong></p>
                        </div>
                        <button type="button" @click="showBpjsBankModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitBpjsBank" class="space-y-4">
                        <!-- Gaji Pokok & Insentif Rate -->
                        <div class="p-3.5 bg-indigo-50/70 border border-indigo-200 rounded-2xl space-y-3">
                            <span class="text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                                💵 Gaji Pokok &amp; Rate Insentif
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Gaji Pokok Bulanan (Rp)</label>
                                    <input v-model.number="bpjsBankForm.base_salary" type="number" min="0" step="50000" placeholder="Contoh: 3000000" class="w-full text-xs px-3 py-2 border border-indigo-300 rounded-xl font-bold font-mono focus:ring-indigo-500 focus:border-indigo-500">
                                    <span class="text-[10px] text-indigo-700 font-bold mt-0.5 block">Rp {{ formatRupiah(bpjsBankForm.base_salary || 0) }}</span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Rate Insentif Otomatis (Rp)</label>
                                    <input v-model.number="bpjsBankForm.incentive_rate" type="number" min="0" step="1000" placeholder="Per tindakan" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl font-mono">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Rp {{ formatRupiah(bpjsBankForm.incentive_rate || 0) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- BPJS Kesehatan -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="bpjsBankForm.bpjs_kesehatan" class="w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500">
                                <span class="text-xs font-bold text-slate-800">Daftarkan BPJS Kesehatan (1% Karyawan / 4% Kantor)</span>
                            </label>
                            <div v-if="bpjsBankForm.bpjs_kesehatan">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nomor Kartu BPJS Kesehatan</label>
                                <input v-model="bpjsBankForm.bpjs_kesehatan_no" type="text" placeholder="Contoh: 0001234567890" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl font-mono">
                            </div>
                        </div>

                        <!-- BPJS Ketenagakerjaan -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="bpjsBankForm.bpjs_ketenagakerjaan" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <span class="text-xs font-bold text-slate-800">Daftarkan BPJS Ketenagakerjaan (JHT, JP, JKK, JKM)</span>
                            </label>
                            <div v-if="bpjsBankForm.bpjs_ketenagakerjaan">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nomor KPJ BPJS Ketenagakerjaan</label>
                                <input v-model="bpjsBankForm.bpjs_tk_no" type="text" placeholder="Contoh: 1902837482" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl font-mono">
                            </div>
                        </div>

                        <!-- Rekening Bank -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl space-y-3">
                            <span class="text-xs font-bold text-slate-800 block">Rekening Bank untuk Auto-Transfer</span>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Bank / E-Wallet</label>
                                <input v-model="bpjsBankForm.bank_name" type="text" placeholder="BCA / BRI / Mandiri / BNI / DANA" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nomor Rekening</label>
                                <input v-model="bpjsBankForm.bank_account_number" type="text" placeholder="Nomor Rekening Staf" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl font-mono">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Atas Nama Pemilik Rekening</label>
                                <input v-model="bpjsBankForm.bank_account_holder" type="text" placeholder="Sesuai Buku Tabungan" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl">
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" @click="showBpjsBankModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="bpjsBankForm.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                                {{ bpjsBankForm.processing ? 'Menyimpan...' : 'Simpan Data' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── MODAL 3: PENGATURAN TARIF BPJS & JADWAL CAIR ── -->
            <div v-if="showSettingsModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showSettingsModal = false">
                <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Pengaturan Penggajian &amp; Tarif BPJS</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Atur tanggal pencairan gaji dan persentase iuran BPJS standar</p>
                        </div>
                        <button type="button" @click="showSettingsModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitSettings" class="space-y-4 text-xs">
                        <!-- Tanggal Cair -->
                        <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-2xl flex items-center justify-between">
                            <div>
                                <span class="font-bold text-indigo-900 block">Jadwal Tanggal Penggajian Bulanan</span>
                                <span class="text-[11px] text-indigo-700">Tanggal rutin gajian karyawan</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-slate-600">Tiap Tgl:</span>
                                <input type="number" v-model="settingForm.disbursement_date" min="1" max="31" class="w-16 px-2 py-1.5 bg-white border border-indigo-300 rounded-lg text-center font-bold text-sm">
                            </div>
                        </div>

                        <!-- Tarif BPJS Kesehatan -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block">1. Tarif BPJS Kesehatan</span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">Dipotong Karyawan (%)</label>
                                    <input type="number" step="0.1" v-model="settingForm.bpjs_kesehatan_employee_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">Ditanggung Perusahaan (%)</label>
                                    <input type="number" step="0.1" v-model="settingForm.bpjs_kesehatan_company_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                            </div>
                        </div>

                        <!-- Tarif BPJS Ketenagakerjaan -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2">
                            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block">2. Tarif BPJS Ketenagakerjaan</span>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">JHT Karyawan (%)</label>
                                    <input type="number" step="0.1" v-model="settingForm.bpjs_tk_jht_employee_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">JHT Kantor (%)</label>
                                    <input type="number" step="0.1" v-model="settingForm.bpjs_tk_jht_company_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">JP Karyawan (%)</label>
                                    <input type="number" step="0.1" v-model="settingForm.bpjs_tk_jp_employee_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">JP Kantor (%)</label>
                                    <input type="number" step="0.1" v-model="settingForm.bpjs_tk_jp_company_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">JKK Kantor (%)</label>
                                    <input type="number" step="0.01" v-model="settingForm.bpjs_tk_jkk_company_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 font-semibold mb-1">JKM Kantor (%)</label>
                                    <input type="number" step="0.01" v-model="settingForm.bpjs_tk_jkm_company_pct" class="w-full px-3 py-1.5 border border-slate-300 rounded-lg font-bold">
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" @click="showSettingsModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="settingForm.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors">
                                {{ settingForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── MODAL 4: TAMBAH INSENTIF MANUAL / POTONGAN ── -->
            <div v-if="showManualModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="closeManualModal">
                <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-900">
                            {{ modalType === 'incentive' ? 'Tambah Bonus / Insentif Manual' : 'Tambah Potongan Manual' }}
                        </h3>
                        <button @click="closeManualModal" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <form @submit.prevent="submitManual" class="space-y-4 text-xs">
                        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                            <p class="text-xs font-bold text-slate-700">Karyawan: {{ selectedUser?.name }}</p>
                        </div>
                        <div>
                            <label class="block text-slate-700 font-bold mb-1">Tanggal</label>
                            <input type="date" v-model="manualForm.date" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl" required>
                        </div>
                        <div>
                            <label class="block text-slate-700 font-bold mb-1">Nominal (Rp)</label>
                            <input type="number" v-model="manualForm.amount" min="1" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl font-bold text-sm" placeholder="Contoh: 50000" required>
                        </div>
                        <div>
                            <label class="block text-slate-700 font-bold mb-1">Keterangan / Alasan</label>
                            <input list="category-list" v-model="manualForm.description" @input="handleDescriptionChange" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl" placeholder="Pilih atau ketik keterangan..." required>
                            <datalist id="category-list">
                                <template v-if="modalType === 'incentive'">
                                    <option v-for="cat in (payrollCategories || []).filter(c => c.type === 'incentive' && c.mode === 'manual')" :key="cat.id" :value="cat.name"></option>
                                </template>
                                <template v-else>
                                    <option v-for="cat in (payrollCategories || []).filter(c => c.type === 'deduction' && c.mode === 'manual')" :key="cat.id" :value="cat.name"></option>
                                </template>
                            </datalist>
                        </div>

                        <div class="pt-2 flex justify-end gap-2">
                            <button type="button" @click="closeManualModal" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold rounded-xl hover:bg-slate-50">
                                Batal
                            </button>
                            <button type="submit" :disabled="manualForm.processing" class="px-5 py-2 text-white font-bold rounded-xl shadow-xs" :class="modalType === 'incentive' ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-rose-600 hover:bg-rose-700'">
                                {{ manualForm.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const { can, hasRole } = usePermission();

const props = defineProps({
    month: Number,
    year: Number,
    users: Array,
    settings: Object,
    activeProvider: String,
    flipConfig: Object,
    xenditConfig: Object,
    disbursementConfig: Object,
    recapBpjs: Object,
    payrollCategories: Array
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);
const searchQuery = ref('');

const filteredUsers = computed(() => {
    if (!searchQuery.value) return props.users || [];
    const q = searchQuery.value.toLowerCase().trim();
    return (props.users || []).filter(u => 
        (u.name && u.name.toLowerCase().includes(q)) || 
        (u.role && u.role.toLowerCase().includes(q)) ||
        (u.bank_name && u.bank_name.toLowerCase().includes(q)) ||
        (u.bank_account_number && u.bank_account_number.includes(q))
    );
});

const totalNetSalary = computed(() => {
    return (props.users || []).reduce((sum, u) => sum + (Number(u.net_salary) || 0), 0);
});

const totalPaidCount = computed(() => {
    return (props.users || []).filter(u => u.is_paid).length;
});

const totalPendingCount = computed(() => {
    return (props.users || []).filter(u => !u.is_paid).length;
});

const form = useForm({
    month: props.month,
    year: props.year
});

const settingForm = useForm({
    disbursement_date: props.settings?.disbursement_date || 25,
    bpjs_kesehatan_employee_pct: props.settings?.bpjs_kesehatan_employee_pct || 1.0,
    bpjs_kesehatan_company_pct: props.settings?.bpjs_kesehatan_company_pct || 4.0,
    bpjs_tk_jht_employee_pct: props.settings?.bpjs_tk_jht_employee_pct || 2.0,
    bpjs_tk_jht_company_pct: props.settings?.bpjs_tk_jht_company_pct || 3.7,
    bpjs_tk_jp_employee_pct: props.settings?.bpjs_tk_jp_employee_pct || 1.0,
    bpjs_tk_jp_company_pct: props.settings?.bpjs_tk_jp_company_pct || 2.0,
    bpjs_tk_jkk_company_pct: props.settings?.bpjs_tk_jkk_company_pct || 0.24,
    bpjs_tk_jkm_company_pct: props.settings?.bpjs_tk_jkm_company_pct || 0.30,
});

const showSettingsModal = ref(false);

const applyFilter = () => {
    form.get(route('payroll.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const submitSettings = () => {
    settingForm.post(route('payroll.settings.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showSettingsModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Pengaturan Disimpan!',
                text: 'Tarif BPJS & jadwal cair berhasil diperbarui.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {})[0] || 'Gagal menyimpan pengaturan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: msg,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID').format(number || 0);
};

// ── Slip Gaji Modal ──
const showSlipModal = ref(false);
const selectedSlipUser = ref(null);

const openSlipModal = (user) => {
    selectedSlipUser.value = user;
    showSlipModal.value = true;
};

const printSlip = () => {
    window.print();
};

// ── BPJS & Bank Modal ──
const showBpjsBankModal = ref(false);
const selectedBpjsUser = ref(null);
const bpjsBankForm = useForm({
    base_salary: 0,
    incentive_rate: 0,
    bpjs_kesehatan: false,
    bpjs_kesehatan_no: '',
    bpjs_ketenagakerjaan: false,
    bpjs_tk_no: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_holder: '',
});

const openBpjsBankModal = (user) => {
    selectedBpjsUser.value = user;
    bpjsBankForm.base_salary = user.base_salary || 0;
    bpjsBankForm.incentive_rate = user.auto_incentive_rate || 0;
    bpjsBankForm.bpjs_kesehatan = Boolean(user.bpjs_kesehatan);
    bpjsBankForm.bpjs_kesehatan_no = user.bpjs_kesehatan_no || '';
    bpjsBankForm.bpjs_ketenagakerjaan = Boolean(user.bpjs_ketenagakerjaan);
    bpjsBankForm.bpjs_tk_no = user.bpjs_tk_no || '';
    bpjsBankForm.bank_name = user.bank_name || '';
    bpjsBankForm.bank_account_number = user.bank_account_number || '';
    bpjsBankForm.bank_account_holder = user.bank_account_holder || user.name;
    showBpjsBankModal.value = true;
};

const submitBpjsBank = () => {
    if (!selectedBpjsUser.value) return;
    const userName = selectedBpjsUser.value.name;
    bpjsBankForm.post(route('payroll.update-bpjs-bank', selectedBpjsUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showBpjsBankModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Data Disimpan!',
                text: `Data BPJS & Rekening ${userName} berhasil diperbarui.`,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {})[0] || 'Gagal menyimpan data BPJS & Rekening.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: msg,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

// ── Manual Incentive / Deduction Modal ──
const showManualModal = ref(false);
const modalType = ref('incentive');
const selectedUser = ref(null);
const manualForm = useForm({
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
});

const openManualModal = (user, type = 'incentive') => {
    selectedUser.value = user;
    modalType.value = type;
    manualForm.reset();
    showManualModal.value = true;
};

const closeManualModal = () => {
    showManualModal.value = false;
    selectedUser.value = null;
};

const handleDescriptionChange = () => {
    const category = (props.payrollCategories || []).find(c => c.name === manualForm.description && c.type === modalType.value);
    if (category && category.default_amount > 0) {
        manualForm.amount = category.default_amount;
    }
};

const submitManual = () => {
    const isInc = modalType.value === 'incentive';
    const userName = selectedUser.value?.name || 'Karyawan';
    const r = isInc 
        ? route('payroll.incentive.store', selectedUser.value.id)
        : route('payroll.deduction.store', selectedUser.value.id);

    const payload = {
        amount: manualForm.amount,
        description: manualForm.description,
        [isInc ? 'incentive_date' : 'deduction_date']: manualForm.date
    };

    router.post(r, payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeManualModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: isInc ? 'Bonus Berhasil Ditambahkan!' : 'Potongan Berhasil Ditambahkan!',
                text: `${isInc ? 'Bonus' : 'Potongan'} berhasil dicatat untuk ${userName}.`,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {})[0] || 'Gagal menambahkan data.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: msg,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

// ── Disburse Single ──
const disburse = (user) => {
    const bpjsInfo = user.bpjs?.total_employee > 0 
        ? `<div class="mt-2 text-xs text-rose-600 font-semibold bg-rose-50 p-2.5 rounded-xl border border-rose-100">↳ Potongan iuran BPJS staf: <b>-Rp ${formatRupiah(user.bpjs.total_employee)}</b></div>` 
        : '';
    
    const activeProv = props.activeProvider || (props.flipConfig?.enabled ? 'flip' : (props.xenditConfig?.enabled ? 'xendit' : 'none'));
    let transferNotice = '';

    if (activeProv === 'flip' && props.flipConfig?.enabled) {
        transferNotice = `<div class="mt-2 text-xs text-orange-800 bg-orange-50 p-2.5 rounded-xl border border-orange-200 text-left"><b>🟠 Auto Transfer Flip Aktif:</b> Otomatis ditransfer ke <b>${user.bank_name || 'Bank'}</b> (${user.bank_account_number || 'Belum diatur'}) a.n <b>${user.bank_account_holder || user.name}</b> via Flip (${props.flipConfig.environment}).</div>`;
    } else if (activeProv === 'xendit' && props.xenditConfig?.enabled) {
        transferNotice = `<div class="mt-2 text-xs text-indigo-800 bg-indigo-50 p-2.5 rounded-xl border border-indigo-200 text-left"><b>⚡ Auto Transfer Xendit Aktif:</b> Otomatis ditransfer ke <b>${user.bank_name || 'Bank'}</b> (${user.bank_account_number || 'Belum diatur'}) a.n <b>${user.bank_account_holder || user.name}</b> via Xendit (${props.xenditConfig.environment}).</div>`;
    }

    Swal.fire({
        title: 'Cairkan Gaji & Insentif?',
        html: `
            <div class="text-left text-xs text-slate-600 space-y-2 mt-2">
                <p>Cairkan penggajian bulan <b>${months[props.month - 1]} ${props.year}</b> untuk staf:</p>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="font-extrabold text-slate-900 text-sm">${user.name}</div>
                    <div class="text-slate-500 capitalize text-xs">${user.role}</div>
                    <div class="mt-2 text-sm font-black text-indigo-700 font-mono">
                        Net Diterima: Rp ${formatRupiah(user.net_salary)}
                    </div>
                </div>
                ${bpjsInfo}
                ${transferNotice}
                <div class="text-[11px] text-amber-800 bg-amber-50 p-2 rounded-xl border border-amber-200">
                    ⚠️ Pengeluaran akan otomatis dicatat di Buku Kas Umum.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '⚡ Ya, Cairkan Sekarang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('payroll.disburse', user.id), {
                month: props.month,
                year: props.year,
                provider: activeProv,
                use_flip: activeProv === 'flip',
                use_xendit: activeProv === 'xendit'
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Gaji Berhasil Dicairkan!',
                        text: `Pencairan gaji untuk ${user.name} berhasil diproses.`,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err || {})[0] || 'Gagal memproses pencairan gaji.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Pencairan Gagal',
                        text: msg,
                        confirmButtonColor: '#dc2626'
                    });
                }
            });
        }
    });
};

// ── Disburse Batch (All) ──
const disburseAll = () => {
    const activeProv = props.activeProvider || (props.flipConfig?.enabled ? 'flip' : (props.xenditConfig?.enabled ? 'xendit' : 'none'));
    let transferNotice = '';

    if (activeProv === 'flip' && props.flipConfig?.enabled) {
        transferNotice = `<div class="mt-2 text-xs text-orange-800 bg-orange-50 p-2.5 rounded-xl border border-orange-200 text-left"><b>🟠 Auto Transfer Flip Aktif:</b> Semua staf dengan rekening valid akan ditransfer otomatis via Flip (${props.flipConfig.environment}).</div>`;
    } else if (activeProv === 'xendit' && props.xenditConfig?.enabled) {
        transferNotice = `<div class="mt-2 text-xs text-indigo-800 bg-indigo-50 p-2.5 rounded-xl border border-indigo-200 text-left"><b>⚡ Auto Transfer Xendit Aktif:</b> Semua staf dengan rekening valid akan ditransfer otomatis via Xendit (${props.xenditConfig.environment}).</div>`;
    }

    Swal.fire({
        title: `Cairkan Gaji ${totalPendingCount.value} Staf Sekaligus?`,
        html: `
            <div class="text-left text-xs text-slate-600 space-y-2 mt-2">
                <p>Anda akan mencairkan penggajian periode <b>${months[props.month - 1]} ${props.year}</b> untuk seluruh <b>${totalPendingCount.value} staf</b> yang belum cair.</p>
                <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <span class="text-xs font-bold text-emerald-800">Total Pengeluaran Kas (Net):</span>
                    <div class="text-lg font-black text-emerald-700 font-mono mt-0.5">
                        Rp ${formatRupiah(totalNetSalary.value)}
                    </div>
                </div>
                ${transferNotice}
                <div class="text-[11px] text-amber-800 bg-amber-50 p-2 rounded-xl border border-amber-200">
                    ⚠️ Pengeluaran akan otomatis dicatat di Buku Kas Umum untuk masing-masing staf.
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `⚡ Ya, Cairkan Semua (${totalPendingCount.value} Staf)`,
        cancelButtonText: 'Batal',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('payroll.disburse-all'), {
                month: props.month,
                year: props.year,
                provider: activeProv,
                use_flip: activeProv === 'flip',
                use_xendit: activeProv === 'xendit'
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Pencairan Massal Selesai!',
                        text: `Gaji untuk ${totalPendingCount.value} staf berhasil dicairkan.`,
                        showConfirmButton: false,
                        timer: 3500,
                        timerProgressBar: true
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err || {})[0] || 'Gagal memproses pencairan massal.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Pencairan Massal Gagal',
                        text: msg,
                        confirmButtonColor: '#dc2626'
                    });
                }
            });
        }
    });
};
</script>
