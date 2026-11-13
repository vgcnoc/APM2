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
                        v-if="can('payroll_manage') || can('payroll_incentives') || can('payroll_incentives_delete')"
                        type="button"
                        @click="cleanOrphanedIncentives"
                        class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5 shadow-2xs cursor-pointer"
                        title="Bersihkan insentif otomatis yang data pelanggannya sudah tidak ada"
                    >
                        <span>🧹</span>
                        <span>Bersihkan Insentif Tanpa Pelanggan</span>
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
                    <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap w-full sm:w-auto">
                        <select 
                            v-if="availableRoles.length > 1"
                            v-model="filterRole" 
                            class="py-2 px-3 border border-slate-200 rounded-xl text-xs bg-slate-50 focus:bg-white text-slate-700 font-semibold cursor-pointer"
                        >
                            <option value="">Semua Jabatan ({{ props.users?.length || 0 }})</option>
                            <option v-for="r in availableRoles" :key="r" :value="r" class="capitalize">
                                {{ r }}
                            </option>
                        </select>
                        <div class="relative w-full sm:w-64">
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Cari nama karyawan..." 
                                class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 focus:bg-white transition-all"
                            />
                            <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                        </div>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs min-w-[1080px]">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="px-5 py-3.5">Karyawan &amp; Status BPJS</th>
                                <th class="px-5 py-3.5">Rekening Transfer</th>
                                <th class="px-5 py-3.5">Gaji Pokok</th>
                                <th class="px-5 py-3.5">Insentif &amp; Bonus</th>
                                <th class="px-5 py-3.5">Potongan (BPJS, Absensi &amp; Kasbon)</th>
                                <th class="px-5 py-3.5">Gaji Bersih (Net)</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-right whitespace-nowrap min-w-[280px]">Aksi</th>
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
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-emerald-600 font-mono">
                                            + Rp {{ formatRupiah(user.total_incentive) }}
                                        </span>
                                        <button 
                                            v-if="(user.incentives_list && user.incentives_list.length > 0) || (user.deductions_list && user.deductions_list.length > 0)"
                                            type="button" 
                                            @click="openDetailsModal(user)"
                                            class="text-[10px] text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-1.5 py-0.5 rounded border border-indigo-200 font-bold transition-colors cursor-pointer"
                                            title="Lihat Rincian & Kelola"
                                        >
                                            🔍 Rincian
                                        </button>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-0.5 whitespace-nowrap">
                                        <span>Auto: +{{ formatRupiah(user.total_auto_incentive) }}</span>
                                        <span v-if="user.total_manual_incentive > 0" class="text-emerald-700 font-bold ml-1">
                                            &bull; Bonus: +{{ formatRupiah(user.total_manual_incentive) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Potongan (BPJS, Absensi & Kasbon) -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="font-bold text-rose-600 font-mono">
                                        - Rp {{ formatRupiah(user.total_deduction + (user.bpjs?.total_employee || 0)) }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 mt-0.5 flex flex-col gap-0.5 whitespace-nowrap">
                                        <span v-if="user.bpjs?.total_employee > 0" class="text-rose-700 font-semibold">
                                            ↳ BPJS: -Rp {{ formatRupiah(user.bpjs.total_employee) }}
                                        </span>
                                        <span v-if="user.attendance_deduction > 0" class="text-amber-700 font-semibold">
                                            ↳ Absensi: -Rp {{ formatRupiah(user.attendance_deduction) }}
                                        </span>
                                        <span v-if="user.kasbon_deduction > 0" class="text-rose-600 font-semibold">
                                            ↳ Kasbon: -Rp {{ formatRupiah(user.kasbon_deduction) }}
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
                                <td class="px-5 py-4 whitespace-nowrap text-right min-w-[280px]">
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

            <!-- ── MODAL 1: RINCIAN & SLIP GAJI KARYAWAN (MODERN, RESPONSIF & CANGGIH) ── -->
            <div v-if="showSlipModal && selectedSlipUser" class="fixed inset-0 z-50 overflow-y-auto flex items-start sm:items-center justify-center p-2 sm:p-4 md:p-6 bg-slate-950/80 backdrop-blur-md print:static print:h-auto print:max-h-none print:overflow-visible print:block print:p-0 print:bg-white" @click.self="showSlipModal = false">
                <div id="slip-modal-card" class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full my-auto flex flex-col max-h-[90vh] overflow-hidden border border-slate-200/90 font-sans print:shadow-none print:max-w-full print:rounded-none print:border-none print:max-h-none print:h-auto print:overflow-visible print:block print:m-0">
                    
                    <!-- 1. Executive Slip Header & Corporate Branding -->
                    <div class="p-5 sm:p-7 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-white relative overflow-hidden border-b border-indigo-900/40 print:bg-slate-900 print:text-white shrink-0">
                        <!-- Decorative ambient glow -->
                        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none no-print"></div>

                        <!-- Top Brand Row -->
                        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <!-- Company Logo & Name -->
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-white p-1.5 border border-white/20 shadow-md flex items-center justify-center shrink-0">
                                    <img v-if="$page.props.company_logo" :src="$page.props.company_logo" alt="Logo" class="max-w-full max-h-full object-contain">
                                    <div v-else class="w-full h-full rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-700 flex items-center justify-center font-black text-lg text-white">
                                        {{ ($page.props.company_name || 'VB').slice(0, 2).toUpperCase() }}
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-300 bg-indigo-500/20 px-2.5 py-0.5 rounded-full border border-indigo-400/30">
                                            Slip Gaji &amp; Komisi Resmi
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400 font-mono">VIZBILL PAYROLL</span>
                                    </div>
                                    <h2 class="text-lg sm:text-xl font-black text-white tracking-tight mt-0.5">
                                        {{ $page.props.company_name || 'VIZBILL NETWORK' }}
                                    </h2>
                                    <p class="text-[11px] text-slate-300 font-medium">
                                        {{ $page.props.company_address || 'Pusat Layanan Internet Broadband & IT Solution' }}
                                        <span v-if="$page.props.company_phone" class="text-slate-400"> &bull; Telp: {{ $page.props.company_phone }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Document Meta & Close Button -->
                            <div class="flex sm:flex-col items-end justify-between sm:justify-start gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="text-right">
                                        <div class="text-[9px] uppercase font-bold text-slate-400 tracking-wider">No. Ref Dokumen</div>
                                        <div class="text-xs font-black font-mono text-indigo-300 bg-indigo-950/90 px-2.5 py-1 rounded-lg border border-indigo-500/30 whitespace-nowrap shrink-0">
                                            SLIP/{{ year }}{{ String(month).padStart(2, '0') }}/{{ selectedSlipUser.employee_code || ('EMP-' + selectedSlipUser.id) }}
                                        </div>
                                    </div>
                                    <button type="button" @click="showSlipModal = false" class="text-white/60 hover:text-white p-2 rounded-xl hover:bg-white/10 transition-colors no-print cursor-pointer">✕</button>
                                </div>
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs border border-white/15 text-white text-xs font-bold whitespace-nowrap">
                                    <span>📅</span>
                                    <span>Periode: {{ months[month - 1] }} {{ year }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Employee Identity Strip in Header -->
                        <div class="relative z-10 mt-5 pt-4 border-t border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-400 text-white flex items-center justify-center font-black text-lg shadow-inner ring-2 ring-white/30 shrink-0">
                                    {{ selectedSlipUser.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-base sm:text-lg font-black text-white truncate">{{ selectedSlipUser.name }}</h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-indigo-500/30 text-indigo-200 border border-indigo-400/30">
                                            {{ selectedSlipUser.position_name || selectedSlipUser.role }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-slate-300 font-medium mt-1 flex-wrap">
                                        <span class="flex items-center gap-1">
                                            <span class="text-slate-400">ID:</span>
                                            <strong class="font-mono text-white">{{ selectedSlipUser.employee_code || ('EMP-' + selectedSlipUser.id) }}</strong>
                                        </span>
                                        <span>&bull;</span>
                                        <span class="flex items-center gap-1">
                                            <span>📍</span>
                                            <span>{{ selectedSlipUser.area_name || 'Kantor Pusat' }}</span>
                                        </span>
                                        <span v-if="selectedSlipUser.phone">&bull;</span>
                                        <span v-if="selectedSlipUser.phone" class="flex items-center gap-1">
                                            <span>📞</span>
                                            <span class="font-mono">{{ selectedSlipUser.phone }}</span>
                                        </span>
                                        <span v-if="selectedSlipUser.join_date">&bull;</span>
                                        <span v-if="selectedSlipUser.join_date" class="text-slate-400">
                                            Bergabung: {{ selectedSlipUser.join_date }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Pembayaran & Quick Net Pill -->
                            <div class="shrink-0 flex items-center gap-2 flex-wrap">
                                <div class="px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-xs border border-white/15 text-white flex items-center gap-1.5">
                                    <span class="text-[10px] text-indigo-200 font-bold uppercase tracking-wider">Bersih:</span>
                                    <span class="text-sm font-black font-mono text-emerald-300">Rp {{ formatRupiah(selectedSlipUser.net_salary) }}</span>
                                </div>
                                <div 
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase tracking-wider flex items-center gap-2 border shadow-sm shrink-0"
                                    :class="selectedSlipUser.is_paid 
                                        ? 'bg-emerald-500/20 text-emerald-300 border-emerald-400/40' 
                                        : 'bg-amber-500/20 text-amber-300 border-amber-400/40'"
                                >
                                    <span class="w-2 h-2 rounded-full" :class="selectedSlipUser.is_paid ? 'bg-emerald-400' : 'bg-amber-400 animate-ping'"></span>
                                    <span>{{ selectedSlipUser.is_paid ? 'LUNAS' : 'MENUNGGU TRANSFER' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Bank Account & Transfer Strip -->
                    <div class="px-6 pt-5 pb-1 shrink-0">
                        <div 
                            class="p-4 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all"
                            :class="selectedSlipUser.bank_name && selectedSlipUser.bank_account_number 
                                ? 'bg-gradient-to-r from-slate-50 via-indigo-50/30 to-slate-50 border-slate-200' 
                                : 'bg-amber-50/90 border-amber-200'"
                        >
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div 
                                    class="w-11 h-11 rounded-2xl flex items-center justify-center font-black text-xl shrink-0 border shadow-2xs"
                                    :class="selectedSlipUser.bank_name && selectedSlipUser.bank_account_number 
                                        ? 'bg-white text-indigo-700 border-indigo-200' 
                                        : 'bg-amber-100 text-amber-800 border-amber-300'"
                                >
                                    <span v-if="selectedSlipUser.bank_name && selectedSlipUser.bank_account_number">🏛️</span>
                                    <span v-else>⚠️</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider" :class="selectedSlipUser.bank_name && selectedSlipUser.bank_account_number ? 'text-indigo-800' : 'text-amber-800'">
                                            Rekening Tujuan Transfer:
                                        </span>
                                        <span v-if="selectedSlipUser.bank_name" class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-indigo-100 text-indigo-900 border border-indigo-200">
                                            {{ selectedSlipUser.bank_name }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <template v-if="selectedSlipUser.bank_name && selectedSlipUser.bank_account_number">
                                            <span class="text-base sm:text-lg font-black font-mono text-slate-900 tracking-wide">
                                                {{ selectedSlipUser.bank_account_number }}
                                            </span>
                                            <button 
                                                type="button" 
                                                @click="copyAccountNumber(selectedSlipUser.bank_account_number)"
                                                class="no-print text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2 py-0.5 rounded-lg text-[10px] font-bold border border-indigo-200 transition-colors flex items-center gap-1 cursor-pointer"
                                                title="Salin Nomor Rekening"
                                            >
                                                <span>📋</span>
                                                <span>Salin</span>
                                            </button>
                                        </template>
                                        <template v-else-if="selectedSlipUser.bank_name">
                                            <span class="text-xs font-bold text-amber-800">Nomor Rekening Belum Diisi</span>
                                        </template>
                                        <template v-else>
                                            <span class="text-xs font-bold text-amber-900">Belum Mengatur Rekening Bank Penerima</span>
                                        </template>
                                    </div>
                                    <span class="text-xs block mt-0.5" :class="selectedSlipUser.bank_name && selectedSlipUser.bank_account_number ? 'text-slate-500' : 'text-amber-700'">
                                        a.n <strong>{{ selectedSlipUser.bank_account_holder || selectedSlipUser.name }}</strong>
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0 self-end sm:self-center no-print">
                                <button 
                                    v-if="can('payroll_btn_settings_bpjs') || can('payroll_manage')"
                                    type="button"
                                    @click="openBpjsBankModal(selectedSlipUser)"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                                    :class="selectedSlipUser.bank_name && selectedSlipUser.bank_account_number 
                                        ? 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 shadow-2xs' 
                                        : 'bg-amber-600 hover:bg-amber-700 text-white shadow-xs'"
                                >
                                    <span>✏️</span>
                                    <span>{{ selectedSlipUser.bank_name && selectedSlipUser.bank_account_number ? 'Ubah Data Bank' : 'Atur Rekening Sekarang' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Slip Body (2 Columns Responsive Layout) -->
                    <div class="p-6 space-y-5 overflow-y-auto flex-1 print:overflow-visible print:max-h-none print:h-auto print:p-0 print:space-y-4">
                        <!-- Two Columns Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 print:grid-cols-2 gap-5 items-stretch">
                            <!-- KOLOM KIRI: A. PENERIMAAN RESMI -->
                            <div class="bg-gradient-to-b from-emerald-50/70 via-white to-white border border-emerald-200/90 rounded-2xl p-5 shadow-2xs flex flex-col justify-between print:overflow-visible print:max-h-none">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between pb-3 border-b border-emerald-100">
                                        <div class="flex items-center gap-2">
                                            <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs font-black shadow-xs">A</span>
                                            <h4 class="font-black text-xs sm:text-sm text-emerald-950 uppercase tracking-wide">Penerimaan (Gaji &amp; Bonus)</h4>
                                        </div>
                                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Penghasilan</span>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">Gaji Pokok Bulanan:</span>
                                            <span class="font-black font-mono text-slate-900 text-sm">Rp {{ formatRupiah(selectedSlipUser.base_salary) }}</span>
                                        </div>
                                        <div class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">Insentif &amp; Komisi Otomatis:</span>
                                            <span class="font-black text-emerald-600 font-mono text-sm">+ Rp {{ formatRupiah(selectedSlipUser.total_auto_incentive) }}</span>
                                        </div>
                                        <div v-if="selectedSlipUser.total_manual_incentive > 0" class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">Bonus Tambahan / Manual:</span>
                                            <span class="font-black text-emerald-600 font-mono text-sm">+ Rp {{ formatRupiah(selectedSlipUser.total_manual_incentive) }}</span>
                                        </div>
                                    </div>

                                    <!-- Rincian Item Insentif -->
                                    <div v-if="selectedSlipUser.incentives_list && selectedSlipUser.incentives_list.length > 0" class="pt-3 border-t border-emerald-100">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">
                                                Rincian Insentif ({{ selectedSlipUser.incentives_list.length }} Item):
                                            </span>
                                            <span class="text-[10px] font-mono text-emerald-700 font-bold">
                                                +Rp {{ formatRupiah(selectedSlipUser.total_incentive) }}
                                            </span>
                                        </div>
                                        <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 divide-y divide-emerald-100/60 print:max-h-none print:overflow-visible">
                                            <div v-for="(inc, idx) in selectedSlipUser.incentives_list" :key="inc.id || idx" class="flex justify-between items-start gap-2 text-[11px] pt-1.5 first:pt-0">
                                                <div class="flex items-start gap-1.5 flex-1 min-w-0">
                                                    <span class="text-emerald-500 font-bold shrink-0 mt-0.5">●</span>
                                                    <span class="break-words font-medium text-slate-700 leading-relaxed">{{ cleanDescription(inc.description) }}</span>
                                                </div>
                                                <span class="font-bold text-emerald-600 font-mono shrink-0 whitespace-nowrap pl-2">+Rp {{ formatRupiah(inc.amount) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtotal Kotor -->
                                <div class="pt-4 mt-4 border-t-2 border-emerald-200/90 flex justify-between items-center">
                                    <span class="font-black text-slate-900 text-xs uppercase tracking-wide">Total Penghasilan Kotor:</span>
                                    <span class="font-black font-mono text-emerald-700 text-base">
                                        Rp {{ formatRupiah(selectedSlipUser.base_salary + selectedSlipUser.total_incentive) }}
                                    </span>
                                </div>
                            </div>

                            <!-- KOLOM KANAN: B. POTONGAN RESMI -->
                            <div class="bg-gradient-to-b from-rose-50/70 via-white to-white border border-rose-200/90 rounded-2xl p-5 shadow-2xs flex flex-col justify-between print:overflow-visible print:max-h-none">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between pb-3 border-b border-rose-100">
                                        <div class="flex items-center gap-2">
                                            <span class="w-7 h-7 rounded-lg bg-rose-600 text-white flex items-center justify-center text-xs font-black shadow-xs">B</span>
                                            <h4 class="font-black text-xs sm:text-sm text-rose-950 uppercase tracking-wide">Potongan &amp; Iuran</h4>
                                        </div>
                                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-rose-100 text-rose-800">Pengurang</span>
                                    </div>

                                    <div class="space-y-2 text-xs">
                                        <div v-if="selectedSlipUser.bpjs?.has_kesehatan" class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">BPJS Kesehatan Karyawan (1%):</span>
                                            <span class="font-black text-rose-600 font-mono text-sm">- Rp {{ formatRupiah(selectedSlipUser.bpjs.kesehatan_employee) }}</span>
                                        </div>
                                        <div v-if="selectedSlipUser.bpjs?.has_tk" class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">BPJS TK JHT Karyawan (2%):</span>
                                            <span class="font-black text-rose-600 font-mono text-sm">- Rp {{ formatRupiah(selectedSlipUser.bpjs.tk_jht_employee) }}</span>
                                        </div>
                                        <div v-if="selectedSlipUser.bpjs?.has_tk" class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">BPJS TK JP Karyawan (1%):</span>
                                            <span class="font-black text-rose-600 font-mono text-sm">- Rp {{ formatRupiah(selectedSlipUser.bpjs.tk_jp_employee) }}</span>
                                        </div>
                                        <div v-if="selectedSlipUser.attendance_deduction > 0" class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">Potongan Absensi / Kehadiran:</span>
                                            <span class="font-black text-rose-600 font-mono text-sm">- Rp {{ formatRupiah(selectedSlipUser.attendance_deduction) }}</span>
                                        </div>
                                        <div v-if="selectedSlipUser.kasbon_deduction > 0" class="flex justify-between items-center py-1">
                                            <span class="text-slate-600 font-medium">Potongan Kasbon / Lainnya:</span>
                                            <span class="font-black text-rose-600 font-mono text-sm">- Rp {{ formatRupiah(selectedSlipUser.kasbon_deduction) }}</span>
                                        </div>

                                        <!-- Kosong jika tidak ada sama sekali -->
                                        <div 
                                            v-if="!selectedSlipUser.bpjs?.has_kesehatan && !selectedSlipUser.bpjs?.has_tk && !selectedSlipUser.attendance_deduction && !selectedSlipUser.kasbon_deduction && (!selectedSlipUser.deductions_list || selectedSlipUser.deductions_list.length === 0)"
                                            class="py-5 text-center text-slate-400 italic text-xs flex flex-col items-center justify-center gap-1.5"
                                        >
                                            <span class="text-emerald-500 font-bold text-sm">✓ Nihil</span>
                                            <span>Tidak ada potongan iuran BPJS, absensi, atau kasbon pada periode ini.</span>
                                        </div>
                                    </div>

                                    <!-- Rincian Item Potongan -->
                                    <div v-if="selectedSlipUser.deductions_list && selectedSlipUser.deductions_list.length > 0" class="pt-3 border-t border-rose-100">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-[10px] font-extrabold text-rose-900 uppercase tracking-wider">
                                                Rincian Potongan ({{ selectedSlipUser.deductions_list.length }} Item):
                                            </span>
                                            <span class="text-[10px] font-mono text-rose-700 font-bold">
                                                -Rp {{ formatRupiah(selectedSlipUser.total_deduction) }}
                                            </span>
                                        </div>
                                        <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 divide-y divide-rose-100/60 print:max-h-none print:overflow-visible">
                                            <div v-for="(ded, idx) in selectedSlipUser.deductions_list" :key="ded.id || idx" class="flex justify-between items-start gap-2 text-[11px] pt-1.5 first:pt-0">
                                                <div class="flex items-start gap-1.5 flex-1 min-w-0">
                                                    <span class="text-rose-500 font-bold shrink-0 mt-0.5">●</span>
                                                    <span class="break-words font-medium text-slate-700 leading-relaxed">{{ cleanDescription(ded.description) }}</span>
                                                </div>
                                                <span class="font-bold text-rose-600 font-mono shrink-0 whitespace-nowrap pl-2">-Rp {{ formatRupiah(ded.amount) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subtotal Potongan -->
                                <div class="pt-4 mt-4 border-t-2 border-rose-200/90 flex justify-between items-center">
                                    <span class="font-black text-slate-900 text-xs uppercase tracking-wide">Total Potongan:</span>
                                    <span 
                                        class="font-black font-mono text-base"
                                        :class="(selectedSlipUser.total_deduction + (selectedSlipUser.bpjs?.total_employee || 0)) > 0 ? 'text-rose-700' : 'text-slate-500'"
                                    >
                                        <template v-if="(selectedSlipUser.total_deduction + (selectedSlipUser.bpjs?.total_employee || 0)) > 0">
                                            - Rp {{ formatRupiah(selectedSlipUser.total_deduction + (selectedSlipUser.bpjs?.total_employee || 0)) }}
                                        </template>
                                        <template v-else>
                                            Rp 0
                                        </template>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- C. TUNJANGAN BPJS PERUSAHAAN (BENEFIT RESMI) -->
                        <div v-if="selectedSlipUser.bpjs?.total_company > 0" class="p-4 bg-purple-50/70 border border-purple-200/80 rounded-2xl space-y-2 text-xs text-purple-950 print:bg-slate-50 print:border-slate-300">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold uppercase tracking-wider text-[11px] text-purple-900 print:text-slate-800 flex items-center gap-1.5">
                                    <span>🛡️</span>
                                    <span>C. Benefit Ditanggung Kantor (Disetor Langsung ke Kas BPJS):</span>
                                </span>
                                <span class="text-[10px] font-bold text-purple-700 print:text-slate-600">Di Luar Gaji Bersih</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1 pt-1 text-[11px]">
                                <div class="flex justify-between">
                                    <span class="text-purple-800 print:text-slate-700">BPJS Kesehatan Kantor (4%):</span>
                                    <span class="font-bold font-mono text-purple-950 print:text-slate-900">Rp {{ formatRupiah(selectedSlipUser.bpjs.kesehatan_company) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-purple-800 print:text-slate-700">BPJS TK Kantor (JHT, JP, JKK, JKM):</span>
                                    <span class="font-bold font-mono text-purple-950 print:text-slate-900">Rp {{ formatRupiah(selectedSlipUser.bpjs.tk_jht_company + selectedSlipUser.bpjs.tk_jp_company + selectedSlipUser.bpjs.tk_jkk_company + selectedSlipUser.bpjs.tk_jkm_company) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between font-extrabold pt-2 border-t border-purple-200/70 print:border-slate-300 text-xs">
                                <span class="text-purple-900 print:text-slate-800">Total Iuran BPJS Kantor &amp; Karyawan:</span>
                                <span class="font-mono text-purple-950 print:text-slate-900">Rp {{ formatRupiah(selectedSlipUser.bpjs.grand_total_bpjs) }}</span>
                            </div>
                        </div>

                        <!-- HERO CARD: TAKE HOME PAY (CANGGIH & MODERN) -->
                        <div class="p-5 sm:p-6 bg-gradient-to-r from-slate-950 via-indigo-950 to-slate-900 text-white rounded-3xl shadow-xl border border-indigo-400/40 relative overflow-hidden print:bg-white print:text-slate-900 print:border-2 print:border-indigo-600 print:shadow-none print:p-4">
                            <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none no-print"></div>
                            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black uppercase tracking-widest text-indigo-300 print:text-indigo-900">Gaji Bersih Diterima (Take Home Pay)</span>
                                        <span class="text-amber-400 text-sm no-print">✨</span>
                                    </div>
                                    <div class="mt-2 text-xs sm:text-sm font-bold italic text-amber-200 print:text-slate-800 font-serif">
                                        ## {{ terbilangRupiah(selectedSlipUser.net_salary) }} ##
                                    </div>
                                    <p class="text-[10px] text-slate-400 print:text-slate-600 mt-1">
                                        Total penerimaan kotor setelah dikurangi seluruh potongan dan iuran resmi periode berjalan.
                                    </p>
                                </div>
                                <div class="text-left md:text-right shrink-0">
                                    <div class="text-[10px] uppercase font-bold text-indigo-300 print:text-slate-500 tracking-wider">Total Bersih</div>
                                    <div class="text-2xl sm:text-3xl font-black font-mono text-white print:text-indigo-900 tracking-tight drop-shadow-sm">
                                        Rp {{ formatRupiah(selectedSlipUser.net_salary) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DIGITAL VERIFICATION SEAL & OFFICIAL SIGNATURES (A4 PRINT & PREVIEW) -->
                        <div class="pt-5 mt-2 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center text-xs print:pt-4 print:mt-4 print:border-t-2 print:border-slate-300">
                            <!-- Segel Digital QR -->
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 flex flex-col items-center justify-center text-center print:border-slate-300 print:bg-white">
                                <div class="w-14 h-14 bg-white p-1 rounded-xl border border-slate-300 shadow-2xs flex items-center justify-center mb-1.5 print:shadow-none">
                                    <svg class="w-11 h-11 text-slate-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="7" height="7" rx="1.5" stroke="currentColor"/>
                                        <rect x="14" y="3" width="7" height="7" rx="1.5" stroke="currentColor"/>
                                        <rect x="3" y="14" width="7" height="7" rx="1.5" stroke="currentColor"/>
                                        <path d="M14 14h3v3h-3z" fill="currentColor"/>
                                        <path d="M20 14v3h-3" stroke="currentColor"/>
                                        <path d="M14 20h6" stroke="currentColor"/>
                                        <circle cx="6.5" cy="6.5" r="1.5" fill="currentColor"/>
                                        <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
                                        <circle cx="6.5" cy="17.5" r="1.5" fill="currentColor"/>
                                    </svg>
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-700">Digital Verified</span>
                                <span class="text-[8px] font-mono text-slate-400">SLIP/{{ year }}{{ String(month).padStart(2, '0') }}/{{ selectedSlipUser.employee_code || ('EMP-' + selectedSlipUser.id) }}</span>
                            </div>

                            <!-- Tanda Tangan Karyawan -->
                            <div class="flex flex-col justify-between py-1">
                                <span class="text-[11px] font-bold text-slate-600 uppercase">Penerima (Karyawan),</span>
                                <div class="h-14 flex items-end justify-center">
                                    <div class="w-36 border-b border-dashed border-slate-400"></div>
                                </div>
                                <span class="font-extrabold text-slate-800 text-xs">( {{ selectedSlipUser.name }} )</span>
                            </div>

                            <!-- Tanda Tangan Finance & HRD -->
                            <div class="flex flex-col justify-between py-1">
                                <span class="text-[11px] font-bold text-slate-600 uppercase">Finance &amp; HRD Division,</span>
                                <div class="h-14 flex items-center justify-center relative">
                                    <span class="text-[10px] font-black uppercase text-indigo-700/70 border-2 border-indigo-500/40 px-2 py-0.5 rounded-lg -rotate-6 select-none shadow-2xs print:border-slate-400 print:text-slate-700">
                                        VERIFIED &amp; APPROVED
                                    </span>
                                </div>
                                <span class="font-extrabold text-slate-800 text-xs">( Management Finance )</span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Slip Footer & Action Toolbar (Canggih & Sticky di Bawah) -->
                    <div class="sticky bottom-0 z-30 p-4 bg-slate-50/95 backdrop-blur-md border-t border-slate-200/90 flex flex-wrap items-center justify-between gap-3 no-print shrink-0 shadow-lg">
                        <div class="flex items-center gap-2 flex-wrap">
                            <!-- Kirim ke WhatsApp -->
                            <button
                                type="button"
                                @click="sendPayslipWhatsApp(selectedSlipUser)"
                                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-2 cursor-pointer"
                                title="Kirim rincian slip via WhatsApp ke karyawan"
                            >
                                <span class="text-base">💬</span>
                                <span>Kirim ke WhatsApp</span>
                            </button>

                            <!-- Salin Teks -->
                            <button
                                type="button"
                                @click="copyPayslipSummary(selectedSlipUser)"
                                class="px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold text-xs rounded-xl shadow-2xs transition-all flex items-center gap-2 cursor-pointer"
                                title="Salin format teks resmi ke clipboard"
                            >
                                <span class="text-base">📋</span>
                                <span>Salin Rincian</span>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="showSlipModal = false" 
                                class="px-4 py-2.5 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-100 cursor-pointer"
                            >
                                Tutup
                            </button>
                            <button
                                v-if="can('payroll_btn_print_slip')"
                                type="button"
                                @click="printSlip"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-2 cursor-pointer"
                            >
                                <span class="text-base">🖨️</span>
                                <span>Cetak Lembar Resmi (PDF)</span>
                            </button>
                        </div>
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

            <!-- ── MODAL 5: RINCIAN & KELOLA INSENTIF & POTONGAN ── -->
            <div v-if="showDetailsModal && selectedDetailsUser" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" @click.self="showDetailsModal = false">
                <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Rincian Insentif &amp; Potongan</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Karyawan: <strong>{{ selectedDetailsUser.name }}</strong> ({{ months[month - 1] }} {{ year }})</p>
                        </div>
                        <button type="button" @click="showDetailsModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="space-y-4 max-h-[60vh] overflow-y-auto text-xs pr-1">
                        <!-- Daftar Insentif -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-extrabold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span>🎁</span> Insentif &amp; Bonus (+Rp {{ formatRupiah(selectedDetailsUser.total_incentive) }})
                                </span>
                            </div>
                            <div v-if="selectedDetailsUser.incentives_list && selectedDetailsUser.incentives_list.length > 0" class="divide-y divide-slate-100 border border-slate-100 rounded-2xl overflow-hidden bg-slate-50/50">
                                <div v-for="inc in selectedDetailsUser.incentives_list" :key="inc.id" class="p-3 flex items-center justify-between gap-2 hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 text-xs">{{ cleanDescription(inc.description) }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2">
                                            <span class="px-1.5 py-0.2 rounded font-bold capitalize text-[9px]" :class="inc.type === 'manual' ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">{{ inc.type }}</span>
                                            <span>📅 {{ inc.incentive_date ? new Date(inc.incentive_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-' }}</span>
                                            <span class="font-bold" :class="inc.status === 'paid' ? 'text-emerald-600' : 'text-amber-600'">{{ inc.status === 'paid' ? 'Sudah Cair' : 'Pending' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="font-bold text-emerald-600 font-mono text-xs">+Rp {{ formatRupiah(inc.amount) }}</span>
                                        <button 
                                            v-if="inc.status === 'pending' && (can('payroll_incentives') || can('payroll_incentives_delete') || can('payroll_manage'))"
                                            @click="deleteIncentiveItem(inc)"
                                            type="button" 
                                            class="text-rose-500 hover:text-rose-700 p-1 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Insentif Ini"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-4 text-center text-slate-400 border border-dashed border-slate-200 rounded-2xl">
                                Belum ada insentif tercatat pada periode ini.
                            </div>
                        </div>

                        <!-- Daftar Potongan -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-extrabold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                                    <span>📉</span> Potongan Absensi &amp; Kasbon (-Rp {{ formatRupiah(selectedDetailsUser.total_deduction) }})
                                </span>
                            </div>
                            <div v-if="selectedDetailsUser.deductions_list && selectedDetailsUser.deductions_list.length > 0" class="divide-y divide-slate-100 border border-slate-100 rounded-2xl overflow-hidden bg-slate-50/50">
                                <div v-for="ded in selectedDetailsUser.deductions_list" :key="ded.id" class="p-3 flex items-center justify-between gap-2 hover:bg-slate-50">
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-800 text-xs">{{ cleanDescription(ded.description) }}</div>
                                        <div class="text-[10px] text-slate-400 mt-0.5 flex items-center gap-2">
                                            <span class="px-1.5 py-0.2 rounded font-bold capitalize text-[9px]" :class="ded.type === 'manual' ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">{{ ded.type || 'manual' }}</span>
                                            <span>📅 {{ ded.deduction_date ? new Date(ded.deduction_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-' }}</span>
                                            <span class="font-bold" :class="ded.status === 'applied' ? 'text-emerald-600' : 'text-amber-600'">{{ ded.status === 'applied' ? 'Sudah Diterapkan' : 'Pending' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span class="font-bold text-rose-600 font-mono text-xs">-Rp {{ formatRupiah(ded.amount) }}</span>
                                        <button 
                                            v-if="ded.status === 'pending' && (can('payroll_deductions') || can('payroll_incentives') || can('payroll_incentives_delete') || can('payroll_manage'))"
                                            @click="deleteDeductionItem(ded)"
                                            type="button" 
                                            class="text-rose-500 hover:text-rose-700 p-1 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                            title="Hapus Potongan Ini"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-4 text-center text-slate-400 border border-dashed border-slate-200 rounded-2xl">
                                Tidak ada potongan absensi atau kasbon tercatat pada periode ini.
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end">
                        <button type="button" @click="showDetailsModal = false" class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const page = usePage();
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
const filterRole = ref('');

const availableRoles = computed(() => {
    const roles = (props.users || []).map(u => u.role).filter(Boolean);
    return Array.from(new Set(roles));
});

const filteredUsers = computed(() => {
    let list = props.users || [];
    if (filterRole.value) {
        list = list.filter(u => u.role === filterRole.value);
    }
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(u => 
            (u.name && u.name.toLowerCase().includes(q)) || 
            (u.role && u.role.toLowerCase().includes(q)) ||
            (u.bank_name && u.bank_name.toLowerCase().includes(q)) ||
            (u.bank_account_number && u.bank_account_number.includes(q))
        );
    }
    return list;
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

const totalPendingNetSalary = computed(() => {
    return (props.users || []).filter(u => !u.is_paid).reduce((sum, u) => sum + (Number(u.net_salary) || 0), 0);
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

// ── Slip Gaji Modal & Helper Canggih ──
const showSlipModal = ref(false);
const selectedSlipUser = ref(null);

const openSlipModal = (user) => {
    selectedSlipUser.value = user;
    showSlipModal.value = true;
};

const printSlip = () => {
    // Reset scroll positions before printing so nothing is scrolled out or clipped
    const slipCard = document.getElementById('slip-modal-card');
    if (slipCard) {
        slipCard.scrollTop = 0;
        const scrollables = slipCard.querySelectorAll('.overflow-y-auto');
        scrollables.forEach(el => el.scrollTop = 0);
    }
    setTimeout(() => {
        window.print();
    }, 60);
};

const terbilangRupiah = (val) => {
    const angka = Math.floor(Math.abs(Number(val) || 0));
    if (angka === 0) return 'Nol Rupiah';

    const bilangan = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

    const konversi = (n) => {
        if (n < 12) {
            return bilangan[n];
        } else if (n < 20) {
            return konversi(n - 10) + ' Belas';
        } else if (n < 100) {
            return konversi(Math.floor(n / 10)) + ' Puluh' + (n % 10 !== 0 ? ' ' + konversi(n % 10) : '');
        } else if (n < 200) {
            return 'Seratus' + (n - 100 !== 0 ? ' ' + konversi(n - 100) : '');
        } else if (n < 1000) {
            return konversi(Math.floor(n / 100)) + ' Ratus' + (n % 100 !== 0 ? ' ' + konversi(n % 100) : '');
        } else if (n < 2000) {
            return 'Seribu' + (n - 1000 !== 0 ? ' ' + konversi(n - 1000) : '');
        } else if (n < 1000000) {
            return konversi(Math.floor(n / 1000)) + ' Ribu' + (n % 1000 !== 0 ? ' ' + konversi(n % 1000) : '');
        } else if (n < 1000000000) {
            return konversi(Math.floor(n / 1000000)) + ' Juta' + (n % 1000000 !== 0 ? ' ' + konversi(n % 1000000) : '');
        } else if (n < 1000000000000) {
            return konversi(Math.floor(n / 1000000000)) + ' Miliar' + (n % 1000000000 !== 0 ? ' ' + konversi(n % 1000000000) : '');
        } else {
            return konversi(Math.floor(n / 1000000000000)) + ' Triliun' + (n % 1000000000000 !== 0 ? ' ' + konversi(n % 1000000000000) : '');
        }
    };

    return konversi(angka).trim() + ' Rupiah';
};

const copyAccountNumber = (accNumber) => {
    if (!accNumber) return;
    navigator.clipboard.writeText(String(accNumber)).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Nomor Rekening Disalin!',
            text: `${accNumber} berhasil disalin ke clipboard.`,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
};

const copyPayslipSummary = (user) => {
    if (!user) return;
    const company = page.props.company_name || 'VIZBILL INTERNET';
    const period = `${months[props.month - 1]} ${props.year}`;
    const refCode = `SLIP/${props.year}${String(props.month).padStart(2, '0')}/${user.employee_code || ('EMP-' + user.id)}`;
    const statusText = user.is_paid ? 'LUNAS (Sudah Ditransfer)' : 'MENUNGGU PROSES TRANSFER';
    const bankText = (user.bank_name && user.bank_account_number)
        ? `${user.bank_name} - ${user.bank_account_number} (a.n ${user.bank_account_holder || user.name})`
        : 'Belum Mengatur Rekening';

    let summary = `═══════════════════════════════════════\n`;
    summary += `    SLIP GAJI & KOMISI RESMI\n`;
    summary += `    ${company.toUpperCase()}\n`;
    summary += `═══════════════════════════════════════\n`;
    summary += `No. Dokumen : ${refCode}\n`;
    summary += `Periode     : ${period}\n`;
    summary += `Status      : ${statusText}\n`;
    summary += `───────────────────────────────────────\n`;
    summary += `IDENTITAS KARYAWAN:\n`;
    summary += `Nama    : ${user.name}\n`;
    summary += `NIK/ID  : ${user.employee_code || '-'}\n`;
    summary += `Jabatan : ${user.position_name || user.role}\n`;
    summary += `Wilayah : ${user.area_name || '-'}\n`;
    summary += `Rekening: ${bankText}\n`;
    summary += `───────────────────────────────────────\n`;
    summary += `A. PENERIMAAN:\n`;
    summary += `• Gaji Pokok             : Rp ${formatRupiah(user.base_salary)}\n`;
    summary += `• Insentif & Komisi Auto : Rp ${formatRupiah(user.total_auto_incentive)}\n`;
    if (user.total_manual_incentive > 0) {
        summary += `• Bonus Tambahan         : Rp ${formatRupiah(user.total_manual_incentive)}\n`;
    }
    summary += `  >> Subtotal Kotor      : Rp ${formatRupiah(user.base_salary + user.total_incentive)}\n`;
    summary += `───────────────────────────────────────\n`;
    summary += `B. POTONGAN:\n`;
    if (user.bpjs?.has_kesehatan) {
        summary += `• BPJS Kesehatan (1%)    : Rp ${formatRupiah(user.bpjs.kesehatan_employee)}\n`;
    }
    if (user.bpjs?.has_tk) {
        summary += `• BPJS TK JHT (2%)       : Rp ${formatRupiah(user.bpjs.tk_jht_employee)}\n`;
        summary += `• BPJS TK JP (1%)        : Rp ${formatRupiah(user.bpjs.tk_jp_employee)}\n`;
    }
    if (user.attendance_deduction > 0) {
        summary += `• Potongan Absensi       : Rp ${formatRupiah(user.attendance_deduction)}\n`;
    }
    if (user.kasbon_deduction > 0) {
        summary += `• Kasbon / Lainnya       : Rp ${formatRupiah(user.kasbon_deduction)}\n`;
    }
    const totPot = user.total_deduction + (user.bpjs?.total_employee || 0);
    summary += `  >> Subtotal Potongan   : Rp ${formatRupiah(totPot)}\n`;
    summary += `───────────────────────────────────────\n`;
    summary += `GAJI BERSIH (TAKE HOME PAY):\n`;
    summary += `Rp ${formatRupiah(user.net_salary)}\n`;
    summary += `Terbilang: ## ${terbilangRupiah(user.net_salary)} ##\n`;
    summary += `═══════════════════════════════════════\n`;
    summary += `Dokumen resmi diterbitkan sistem penggajian ${company}.`;

    navigator.clipboard.writeText(summary).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Rincian Slip Disalin!',
            text: 'Format teks rapi siap dikirim atau disimpan.',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });
    });
};

const sendPayslipWhatsApp = (user) => {
    if (!user) return;
    const rawPhone = user.phone ? String(user.phone).replace(/[^0-9]/g, '') : '';
    let targetPhone = rawPhone;
    if (targetPhone.startsWith('0')) {
        targetPhone = '62' + targetPhone.slice(1);
    }

    const company = page.props.company_name || 'VIZBILL INTERNET';
    const period = `${months[props.month - 1]} ${props.year}`;
    const refCode = `SLIP/${props.year}${String(props.month).padStart(2, '0')}/${user.employee_code || ('EMP-' + user.id)}`;
    const statusText = user.is_paid ? '✅ LUNAS (Sudah Ditransfer)' : '⏳ MENUNGGU PROSES TRANSFER';

    let text = `*SLIP GAJI & KOMISI RESMI*\n`;
    text += `*${company.toUpperCase()}*\n`;
    text += `No: \`${refCode}\`\n`;
    text += `Periode: *${period}*\n`;
    text += `Status: *${statusText}*\n\n`;
    text += `Halo *${user.name}*,\nBerikut adalah rincian gaji & komisi Anda untuk periode ${period}:\n\n`;
    text += `*📋 DATA KARYAWAN*\n`;
    text += `• ID/NIK: ${user.employee_code || '-'}\n`;
    text += `• Posisi/Role: ${user.position_name || user.role}\n`;
    text += `• Area: ${user.area_name || '-'}\n`;
    if (user.bank_name && user.bank_account_number) {
        text += `• Rekening: ${user.bank_name} \`${user.bank_account_number}\` (a.n ${user.bank_account_holder || user.name})\n`;
    }
    text += `\n*💵 PENERIMAAN (GAJI & BONUS)*\n`;
    text += `• Gaji Pokok: *Rp ${formatRupiah(user.base_salary)}*\n`;
    text += `• Komisi / Insentif: *+Rp ${formatRupiah(user.total_auto_incentive)}*\n`;
    if (user.total_manual_incentive > 0) {
        text += `• Bonus Tambahan: *+Rp ${formatRupiah(user.total_manual_incentive)}*\n`;
    }
    text += `*Total Penghasilan Kotor: Rp ${formatRupiah(user.base_salary + user.total_incentive)}*\n\n`;

    const totPot = user.total_deduction + (user.bpjs?.total_employee || 0);
    text += `*🔻 POTONGAN*\n`;
    if (user.bpjs?.has_kesehatan) {
        text += `• BPJS Kesehatan (1%): -Rp ${formatRupiah(user.bpjs.kesehatan_employee)}\n`;
    }
    if (user.bpjs?.has_tk) {
        text += `• BPJS Ketenagakerjaan (3%): -Rp ${formatRupiah(user.bpjs.tk_jht_employee + user.bpjs.tk_jp_employee)}\n`;
    }
    if (user.attendance_deduction > 0) {
        text += `• Potongan Absensi: -Rp ${formatRupiah(user.attendance_deduction)}\n`;
    }
    if (user.kasbon_deduction > 0) {
        text += `• Potongan Kasbon / Lainnya: -Rp ${formatRupiah(user.kasbon_deduction)}\n`;
    }
    if (totPot === 0) {
        text += `• Tidak ada potongan (Rp 0)\n`;
    }
    text += `*Total Potongan: -Rp ${formatRupiah(totPot)}*\n\n`;

    text += `*💰 GAJI BERSIH DITERIMA (TAKE HOME PAY)*\n`;
    text += `*Rp ${formatRupiah(user.net_salary)}*\n`;
    text += `_Terbilang: ${terbilangRupiah(user.net_salary)}_\n\n`;
    text += `_Slip gaji ini resmi digenerate otomatis oleh sistem ${company}._`;

    const encoded = encodeURIComponent(text);
    const waUrl = targetPhone ? `https://wa.me/${targetPhone}?text=${encoded}` : `https://wa.me/?text=${encoded}`;
    window.open(waUrl, '_blank');
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
            // Update reactive objects in place immediately
            if (selectedBpjsUser.value) {
                selectedBpjsUser.value.bank_name = bpjsBankForm.bank_name;
                selectedBpjsUser.value.bank_account_number = bpjsBankForm.bank_account_number;
                selectedBpjsUser.value.bank_account_holder = bpjsBankForm.bank_account_holder || selectedBpjsUser.value.name;
                selectedBpjsUser.value.base_salary = bpjsBankForm.base_salary;
                selectedBpjsUser.value.auto_incentive_rate = bpjsBankForm.incentive_rate;
                selectedBpjsUser.value.bpjs_kesehatan = bpjsBankForm.bpjs_kesehatan;
                selectedBpjsUser.value.bpjs_kesehatan_no = bpjsBankForm.bpjs_kesehatan_no;
                selectedBpjsUser.value.bpjs_ketenagakerjaan = bpjsBankForm.bpjs_ketenagakerjaan;
                selectedBpjsUser.value.bpjs_tk_no = bpjsBankForm.bpjs_tk_no;
            }
            if (selectedSlipUser.value && selectedSlipUser.value.id === selectedBpjsUser.value?.id) {
                selectedSlipUser.value.bank_name = bpjsBankForm.bank_name;
                selectedSlipUser.value.bank_account_number = bpjsBankForm.bank_account_number;
                selectedSlipUser.value.bank_account_holder = bpjsBankForm.bank_account_holder || selectedSlipUser.value.name;
                selectedSlipUser.value.base_salary = bpjsBankForm.base_salary;
            }
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
    const now = new Date();
    const isCurrentPeriod = (now.getMonth() + 1 === props.month && now.getFullYear() === props.year);
    manualForm.date = isCurrentPeriod ? now.toISOString().split('T')[0] : `${props.year}-${String(props.month).padStart(2, '0')}-01`;
    showManualModal.value = true;
};

const closeManualModal = () => {
    showManualModal.value = false;
    selectedUser.value = null;
};

// ── Details Modal & Item Actions ──
const showDetailsModal = ref(false);
const selectedDetailsUser = ref(null);

const openDetailsModal = (user) => {
    selectedDetailsUser.value = user;
    showDetailsModal.value = true;
};

const cleanDescription = (desc) => {
    if (!desc) return '-';
    return String(desc).replace(/\s*\([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}\)/gi, '').trim();
};

const deleteIncentiveItem = (inc) => {
    Swal.fire({
        title: 'Hapus Insentif?',
        text: `Hapus insentif "${cleanDescription(inc.description)}" sebesar Rp ${formatRupiah(inc.amount)}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('payroll.incentive.destroy', inc.id), {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedDetailsUser.value?.incentives_list) {
                        selectedDetailsUser.value.incentives_list = selectedDetailsUser.value.incentives_list.filter(i => i.id !== inc.id);
                        selectedDetailsUser.value.total_incentive = (selectedDetailsUser.value.total_incentive || 0) - Number(inc.amount || 0);
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Insentif Dihapus',
                        showConfirmButton: false,
                        timer: 2500
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err || {})[0] || 'Gagal menghapus insentif.';
                    Swal.fire('Gagal', msg, 'error');
                }
            });
        }
    });
};

const deleteDeductionItem = (ded) => {
    Swal.fire({
        title: 'Hapus Potongan?',
        text: `Hapus potongan "${cleanDescription(ded.description)}" sebesar Rp ${formatRupiah(ded.amount)}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('payroll.deduction.destroy', ded.id), {
                preserveScroll: true,
                onSuccess: () => {
                    if (selectedDetailsUser.value?.deductions_list) {
                        selectedDetailsUser.value.deductions_list = selectedDetailsUser.value.deductions_list.filter(d => d.id !== ded.id);
                        selectedDetailsUser.value.total_deduction = (selectedDetailsUser.value.total_deduction || 0) - Number(ded.amount || 0);
                    }
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Potongan Dihapus',
                        showConfirmButton: false,
                        timer: 2500
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err || {})[0] || 'Gagal menghapus potongan.';
                    Swal.fire('Gagal', msg, 'error');
                }
            });
        }
    });
};

const cleanOrphanedIncentives = () => {
    Swal.fire({
        title: 'Bersihkan Insentif Tanpa Pelanggan?',
        text: 'Sistem akan memeriksa dan menghapus data insentif otomatis pending yang datanya terputus (pelanggan sudah tidak ada di data pelanggan). Data manual / bonus staf tetap aman.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '🧹 Ya, Bersihkan Sekarang',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('payroll.clean-orphaned'), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Pembersihan Selesai',
                        showConfirmButton: false,
                        timer: 3000
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err || {})[0] || 'Gagal membersihkan data insentif.';
                    Swal.fire('Gagal', msg, 'error');
                }
            });
        }
    });
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
                    <span class="text-xs font-bold text-emerald-800">Total Pengeluaran Kas (Net Belum Cair):</span>
                    <div class="text-lg font-black text-emerald-700 font-mono mt-0.5">
                        Rp ${formatRupiah(totalPendingNetSalary.value)}
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

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 6mm 8mm;
    }
    html, body {
        height: auto !important;
        min-height: 100% !important;
        overflow: visible !important;
        background: white !important;
    }
    body * {
        visibility: hidden !important;
    }
    #slip-modal-card, #slip-modal-card * {
        visibility: visible !important;
    }
    .fixed, [class*="fixed"] {
        position: static !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        background: transparent !important;
        padding: 0 !important;
    }
    #slip-modal-card {
        position: relative !important;
        left: auto !important;
        top: auto !important;
        width: 100% !important;
        max-width: 100% !important;
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
        display: block !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 14px !important;
        box-shadow: none !important;
        background: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        page-break-inside: auto !important;
    }
    #slip-modal-card .overflow-y-auto,
    #slip-modal-card [class*="overflow-y-auto"],
    #slip-modal-card [class*="max-h-"] {
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
