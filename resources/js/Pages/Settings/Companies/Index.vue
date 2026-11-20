<template>
    <AppLayout title="Manajemen Mitra & Multi-Company" subtitle="Kelola daftar mitra perusahaan, rekening pembayaran mandiri, hak akses modul, dan isolasi data pelanggan">
        <div class="space-y-6 pb-20">
            <!-- ── SUMMARY STATS ──────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl shrink-0 font-bold">
                        🏢
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Total Mitra</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ companies.length }} Perusahaan</h3>
                        <p class="text-[11px] text-indigo-700 dark:text-indigo-400 font-bold mt-0.5">1 Holding Pusat + {{ Math.max(0, companies.length - 1) }} Mitra Cabang</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl shrink-0 font-bold">
                        👥
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Total Pelanggan</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ totalAllCustomers.toLocaleString('id-ID') }}</h3>
                        <p class="text-[11px] text-slate-600 dark:text-slate-300 font-semibold mt-0.5">Akumulasi seluruh mitra</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shrink-0 font-bold">
                        🧾
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Total Tagihan</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ totalAllInvoices.toLocaleString('id-ID') }}</h3>
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-400 font-bold mt-0.5">Transaksi invoice tercatat</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-2xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl shrink-0 font-bold">
                        🛡️
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Mode Isolasi</p>
                        <h3 class="text-lg font-black text-purple-700 dark:text-purple-400 mt-0.5">Aktif (100% Kedap)</h3>
                        <p class="text-[11px] text-slate-600 dark:text-slate-300 font-semibold mt-0.5">Zero Data Leakage antar mitra</p>
                    </div>
                </div>
            </div>

            <!-- ── HEADER & ACTION ────────────────────────────────────── -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 sm:p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-2xs">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Daftar Mitra Perusahaan</span>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            {{ companies.length }} Terdaftar
                        </span>
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-1">
                        Setiap mitra memiliki rekening pembayaran, invoice, dan data pelanggan yang terisolasi secara mandiri.
                    </p>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <!-- Tombol Reset Tampilan ke Holding jika sedang switch -->
                    <button 
                        v-if="active_company_id && active_company_id !== 'all'"
                        @click="switchCompanyView('all')"
                        class="px-3.5 py-2 text-xs font-bold rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition shadow-2xs flex items-center gap-1.5 cursor-pointer"
                        title="Kembalikan tampilan ke seluruh holding"
                    >
                        <span>🔄 Reset ke Holding</span>
                    </button>

                    <button 
                        type="button"
                        @click="openCreateModal"
                        class="btn-primary w-full sm:w-auto text-xs sm:text-sm py-2 px-4 rounded-xl flex items-center justify-center gap-2 font-bold shadow-xs cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Tambah Mitra Baru</span>
                    </button>
                </div>
            </div>

            <!-- ── GRID DAFTAR MITRA ───────────────────────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div 
                    v-for="company in companies" 
                    :key="company.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl border transition-all duration-200 shadow-2xs hover:shadow-md overflow-hidden flex flex-col justify-between"
                    :class="[
                        company.is_holding 
                            ? 'border-indigo-300 dark:border-indigo-700 ring-2 ring-indigo-100 dark:ring-indigo-950/50' 
                            : (company.status === 'active' ? 'border-gray-200 dark:border-gray-700' : 'border-rose-200 dark:border-rose-900 bg-rose-50/20')
                    ]"
                >
                    <!-- Card Header -->
                    <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div 
                                    class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg font-black shrink-0 shadow-2xs"
                                    :class="company.is_holding ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'"
                                >
                                    {{ company.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-base font-black text-gray-900 dark:text-white truncate">
                                            {{ company.name }}
                                        </h4>
                                        <span 
                                            class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                                            :class="company.is_holding ? 'bg-amber-100 dark:bg-amber-950/70 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                        >
                                            {{ company.code }}
                                        </span>
                                        <span 
                                            v-if="company.is_holding"
                                            class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                                        >
                                            ⭐ PUSAT (HOLDING)
                                        </span>
                                    </div>
                                    <p class="text-xs font-medium text-slate-600 dark:text-slate-300 mt-0.5 truncate">
                                        Merek: <strong class="text-gray-900 dark:text-white font-bold">{{ company.brand_name || '-' }}</strong> 
                                        &bull; Telp: <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ company.phone || '-' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <button 
                                    type="button" 
                                    @click="openEditModal(company)"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg transition cursor-pointer"
                                    title="Edit Profil & Pengaturan Mitra"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <span 
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold shrink-0"
                                    :class="company.status === 'active' ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="company.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    {{ company.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Alamat -->
                        <p v-if="company.address" class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-3 line-clamp-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 shrink-0 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ company.address }}</span>
                        </p>
                    </div>

                    <!-- Card Body: Stats & Billing Settings -->
                    <div class="p-5 sm:p-6 space-y-4 text-xs">
                        <!-- Metrik Angka -->
                        <div class="grid grid-cols-3 gap-3 bg-slate-50/90 dark:bg-gray-700/50 p-3.5 rounded-xl border border-slate-200/90 dark:border-gray-700 shadow-2xs">
                            <div class="text-center">
                                <p class="text-[11px] uppercase tracking-wider font-extrabold text-slate-700 dark:text-slate-200">Pelanggan</p>
                                <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ company.customers_count }}</p>
                                <span v-if="company.max_customers" class="text-[10px] font-bold text-slate-600 dark:text-slate-300">Maks. {{ company.max_customers }}</span>
                                <span v-else class="text-[10px] text-emerald-700 dark:text-emerald-400 font-bold">Unlimited</span>
                            </div>
                            <div class="text-center border-x border-slate-200 dark:border-gray-700">
                                <p class="text-[11px] uppercase tracking-wider font-extrabold text-slate-700 dark:text-slate-200">Total Tagihan</p>
                                <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ company.invoices_count }}</p>
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300">Invoice</span>
                            </div>
                            <div class="text-center">
                                <p class="text-[11px] uppercase tracking-wider font-extrabold text-slate-700 dark:text-slate-200">Karyawan</p>
                                <p class="text-base font-black text-gray-900 dark:text-white mt-0.5">{{ company.users_count }}</p>
                                <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300">User Akun</span>
                            </div>
                        </div>

                        <!-- Info Siklus Penagihan & Rekening Bank -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                                <span class="text-slate-700 dark:text-slate-300 font-bold">Siklus Penagihan:</span>
                                <span class="font-extrabold text-gray-900 dark:text-gray-100">
                                    Invoice Tgl {{ company.invoice_issue_date }} &bull; {{ company.billing_cycle === 'calendar' ? 'Kalender Bulanan' : 'Anniversary (Tgl Pasang)' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs py-1.5 border-b border-gray-100 dark:border-gray-700/60">
                                <span class="text-slate-700 dark:text-slate-300 font-bold">Rekening Pembayaran:</span>
                                <div class="text-right">
                                    <span v-if="company.payment_banks && company.payment_banks.length > 0" class="font-extrabold text-emerald-700 dark:text-emerald-400">
                                        {{ company.payment_banks.length }} Rekening Bank Terdaftar
                                    </span>
                                    <span v-else class="text-amber-700 dark:text-amber-400 font-bold italic">Mengikuti Rekening Pusat</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs py-1.5">
                                <span class="text-slate-700 dark:text-slate-300 font-bold">Payment Gateway (Midtrans):</span>
                                <span class="font-extrabold" :class="company.midtrans_server_key ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-300'">
                                    {{ company.midtrans_server_key ? '✅ Aktif (Mandiri)' : '⚪ Belum Dikonfigurasi' }}
                                </span>
                            </div>
                        </div>

                        <!-- Modul yang Diizinkan -->
                        <div>
                            <p class="text-[11px] font-extrabold text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">Modul Diizinkan:</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span 
                                    v-if="company.is_holding"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                                >
                                    Semua Modul Terbuka Penuh (Holding)
                                </span>
                                <span 
                                    v-else 
                                    v-for="mod in (company.allowed_modules || [])" 
                                    :key="mod"
                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600"
                                >
                                    {{ getModuleName(mod) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer: Actions -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                        <button 
                            type="button"
                            @click="switchCompanyView(company.id)"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg border transition shadow-2xs flex items-center gap-1.5 cursor-pointer"
                            :class="active_company_id == company.id 
                                ? 'bg-indigo-600 text-white border-indigo-700' 
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-gray-200 dark:border-gray-600 hover:bg-gray-100'"
                            :title="'Lihat aplikasi dari sudut pandang mitra ' + company.name"
                        >
                            <span v-if="active_company_id == company.id">👁️ Sedang Aktif</span>
                            <span v-else>👁️ Beralih Tampilan</span>
                        </button>

                        <div class="flex items-center gap-1.5">
                            <button 
                                type="button"
                                @click="openEditModal(company)"
                                class="px-3.5 py-1.5 text-xs font-bold rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition shadow-2xs cursor-pointer flex items-center gap-1.5"
                                title="Edit profil perusahaan, kontak, rekening, dan izin modul"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                <span>✏️ Edit Mitra</span>
                            </button>

                            <button 
                                v-if="!company.is_holding"
                                type="button"
                                @click="confirmDeleteCompany(company)"
                                class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition cursor-pointer"
                                title="Nonaktifkan Mitra"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── MODAL TAMBAH MITRA BARU ────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs overflow-y-auto">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-2xl w-full border border-gray-100 dark:border-gray-700 overflow-hidden my-8 animate-scale-in">
                    <!-- Modal Header -->
                    <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-indigo-50/50 to-blue-50/50 dark:from-gray-800 dark:to-gray-800">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <span>🏢 Tambah Mitra Baru</span>
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">Daftarkan perusahaan mitra dan akun admin utama mereka</p>
                        </div>
                        <button @click="showCreateModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-white dark:hover:bg-gray-700">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body / Tabs -->
                    <form @submit.prevent="submitCreateCompany" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Perusahaan / Mitra *</label>
                                <input v-model="createForm.name" type="text" required placeholder="Contoh: PT Sumber Internet Mandiri" class="input-text w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Kode Singkatan (Prefix Unik) *</label>
                                <input v-model="createForm.code" type="text" required placeholder="Contoh: SIM / MTR1" class="input-text w-full text-xs uppercase" />
                                <span class="text-[10px] text-slate-600 dark:text-slate-300 font-medium">Digunakan untuk prefix invoice & kode pelanggan</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Merek / Brand Aplikasi</label>
                                <input v-model="createForm.brand_name" type="text" placeholder="Contoh: SIMNET" class="input-text w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">No. Telepon / WhatsApp Mitra</label>
                                <input v-model="createForm.phone" type="text" placeholder="08123456789" class="input-text w-full text-xs" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Alamat Kantor Mitra</label>
                            <textarea v-model="createForm.address" rows="2" placeholder="Alamat lengkap operasional mitra" class="input-text w-full text-xs"></textarea>
                        </div>

                        <!-- Akun Admin Utama Mitra -->
                        <div class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/60 space-y-3">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                                <span>🔑 Akun Admin Utama Mitra</span>
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Admin *</label>
                                    <input v-model="createForm.admin_name" type="text" required placeholder="Nama Direktur/Admin" class="input-text w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Email Login *</label>
                                    <input v-model="createForm.admin_email" type="email" required placeholder="admin@mitra.com" class="input-text w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Password Login *</label>
                                    <input v-model="createForm.admin_password" type="password" required minlength="6" placeholder="Minimal 6 karakter" class="input-text w-full text-xs" />
                                </div>
                            </div>
                        </div>

                        <!-- Siklus Penagihan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tgl Terbit Invoice</label>
                                <input v-model="createForm.invoice_issue_date" type="number" min="1" max="28" class="input-text w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Siklus Penagihan</label>
                                <select v-model="createForm.billing_cycle" class="input-text w-full text-xs">
                                    <option value="calendar">Kalender Bulanan</option>
                                    <option value="anniversary">Anniversary (Tgl Pasang)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tarif PPN (%)</label>
                                <input v-model="createForm.tax_ppn" type="number" min="0" max="100" class="input-text w-full text-xs" />
                            </div>
                        </div>

                        <!-- Checklist Modul yang Diizinkan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">Pilih Modul yang Boleh Diakses Mitra:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <label 
                                    v-for="mod in available_modules" 
                                    :key="mod.id" 
                                    class="flex items-start gap-2.5 p-2.5 rounded-xl border transition-colors cursor-pointer"
                                    :class="createForm.allowed_modules.includes(mod.id) ? 'bg-indigo-50/70 border-indigo-300 dark:bg-indigo-950/40' : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-700'"
                                >
                                    <input type="checkbox" :value="mod.id" v-model="createForm.allowed_modules" class="rounded mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                    <div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white">{{ mod.name }}</p>
                                        <p class="text-[10px] text-slate-600 dark:text-slate-300 font-medium leading-tight">{{ mod.desc }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="showCreateModal = false" class="btn-secondary text-xs px-4 py-2">Batal</button>
                            <button type="submit" :disabled="isSubmitting" class="btn-primary text-xs px-5 py-2 font-bold shadow-xs">
                                {{ isSubmitting ? 'Mendaftarkan...' : 'Simpan & Daftarkan Mitra' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL EDIT MITRA ──────────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs overflow-y-auto">
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-2xl w-full border border-gray-100 dark:border-gray-700 overflow-hidden my-8 animate-scale-in">
                    <!-- Modal Header -->
                    <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-blue-50/50 to-indigo-50/50 dark:from-gray-800 dark:to-gray-800">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                                <span>✏️ Edit Data & Pengaturan Mitra: {{ editForm.name }}</span>
                            </h3>
                            <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">Perbarui profil perusahaan, kontak, alamat, rekening, gateway, dan izin modul</p>
                        </div>
                        <button @click="showEditModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-white dark:hover:bg-gray-700">
                            ✕
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitEditCompany" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Perusahaan *</label>
                                <input v-model="editForm.name" type="text" required class="input-text w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Kode Singkatan (Prefix) *</label>
                                <input v-model="editForm.code" type="text" required class="input-text w-full text-xs uppercase" />
                                <span class="text-[10px] text-slate-600 dark:text-slate-300 font-medium">Prefix invoice & ID pelanggan mitra</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Brand Aplikasi</label>
                                <input v-model="editForm.brand_name" type="text" placeholder="Contoh: FIBERNET" class="input-text w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Status Kemitraan</label>
                                <select 
                                    v-model="editForm.status" 
                                    :disabled="selectedEditCompany?.is_holding"
                                    class="input-text w-full text-xs"
                                    :class="selectedEditCompany?.is_holding ? 'bg-gray-100 dark:bg-gray-700 cursor-not-allowed opacity-80' : ''"
                                >
                                    <option value="active">Aktif</option>
                                    <option value="suspended">Ditangguhkan (Suspend)</option>
                                    <option value="inactive">Nonaktif</option>
                                </select>
                                <span v-if="selectedEditCompany?.is_holding" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold">Pusat / Holding selalu aktif permanen</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">No. Telepon / WhatsApp Mitra</label>
                                <input v-model="editForm.phone" type="text" placeholder="Contoh: 08123456789" class="input-text w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Email Resmi Mitra</label>
                                <input v-model="editForm.email" type="email" placeholder="Contoh: info@mitra.com" class="input-text w-full text-xs" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Alamat Kantor / Operasional</label>
                            <textarea v-model="editForm.address" rows="2" placeholder="Alamat lengkap operasional kantor mitra" class="input-text w-full text-xs"></textarea>
                        </div>

                        <div v-if="!selectedEditCompany?.is_holding">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Batas Kuota Pelanggan (Maksimal)</label>
                            <input v-model="editForm.max_customers" type="number" min="1" placeholder="Kosongkan jika unlimited" class="input-text w-full text-xs" />
                            <span class="text-[10px] text-slate-600 dark:text-slate-300 font-medium">Kosongkan jika tidak ada batasan kuota pelanggan untuk mitra ini</span>
                        </div>

                        <!-- Rekening Bank Mitra -->
                        <div class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-extrabold uppercase tracking-wider text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                                    <span>🏦 Rekening Bank Mitra (Untuk Invoice)</span>
                                </h4>
                                <button type="button" @click="addBankRow" class="text-xs text-emerald-700 font-bold hover:underline">+ Tambah Rekening</button>
                            </div>

                            <div v-for="(bank, bIdx) in editForm.payment_banks" :key="bIdx" class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-white dark:bg-gray-800 p-2.5 rounded-xl border border-emerald-100 dark:border-emerald-800 relative">
                                <input v-model="bank.bank_name" type="text" placeholder="Nama Bank (cth: BRI)" class="input-text text-xs" />
                                <input v-model="bank.account_number" type="text" placeholder="No. Rekening" class="input-text text-xs" />
                                <div class="flex items-center gap-1">
                                    <input v-model="bank.account_name" type="text" placeholder="Atas Nama PT/Orang" class="input-text text-xs flex-1" />
                                    <button type="button" @click="removeBankRow(bIdx)" class="p-1 text-red-500 hover:bg-red-50 rounded">✕</button>
                                </div>
                            </div>
                            <p v-if="!editForm.payment_banks || editForm.payment_banks.length === 0" class="text-xs text-slate-600 dark:text-slate-300 font-medium italic">Belum ada rekening khusus, invoice akan memakai rekening default holding.</p>
                        </div>

                        <!-- Payment Gateway Mandiri -->
                        <div class="p-4 rounded-2xl bg-blue-50/50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-800 space-y-3">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-blue-900 dark:text-blue-300">
                                ⚡ Payment Gateway Midtrans (Khusus Mitra)
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Server Key Midtrans Mitra</label>
                                    <input v-model="editForm.midtrans_server_key" type="password" placeholder="SB-Mid-server-xxx" class="input-text w-full text-xs" />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">Merchant ID Midtrans</label>
                                    <input v-model="editForm.midtrans_merchant_id" type="text" placeholder="G123456789" class="input-text w-full text-xs" />
                                </div>
                            </div>
                        </div>

                        <!-- Siklus Penagihan -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tgl Terbit Invoice</label>
                                <input v-model="editForm.invoice_issue_date" type="number" min="1" max="28" class="input-text w-full text-xs" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Siklus Penagihan</label>
                                <select v-model="editForm.billing_cycle" class="input-text w-full text-xs">
                                    <option value="calendar">Kalender Bulanan</option>
                                    <option value="anniversary">Anniversary (Tgl Pasang)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tarif PPN (%)</label>
                                <input v-model="editForm.tax_ppn" type="number" min="0" max="100" class="input-text w-full text-xs" />
                            </div>
                        </div>

                        <!-- Checklist Modul -->
                        <div v-if="!selectedEditCompany?.is_holding">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">Modul yang Diizinkan untuk Mitra Ini:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <label 
                                    v-for="mod in available_modules" 
                                    :key="mod.id" 
                                    class="flex items-start gap-2.5 p-2.5 rounded-xl border transition-colors cursor-pointer"
                                    :class="editForm.allowed_modules.includes(mod.id) ? 'bg-indigo-50/70 border-indigo-300 dark:bg-indigo-950/40' : 'bg-gray-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-700'"
                                >
                                    <input type="checkbox" :value="mod.id" v-model="editForm.allowed_modules" class="rounded mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                    <div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white">{{ mod.name }}</p>
                                        <p class="text-[10px] text-slate-600 dark:text-slate-300 font-medium leading-tight">{{ mod.desc }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="showEditModal = false" class="btn-secondary text-xs px-4 py-2">Batal</button>
                            <button type="submit" :disabled="isSubmitting" class="btn-primary text-xs px-5 py-2 font-bold shadow-xs">
                                {{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    companies: { type: Array, default: () => [] },
    active_company_id: { type: [String, Number], default: 'all' },
    available_modules: { type: Array, default: () => [] },
});

const isSubmitting = ref(false);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedEditCompany = ref(null);

const totalAllCustomers = computed(() => {
    return props.companies.reduce((sum, c) => sum + (c.customers_count || 0), 0);
});

const totalAllInvoices = computed(() => {
    return props.companies.reduce((sum, c) => sum + (c.invoices_count || 0), 0);
});

function getModuleName(id) {
    const found = props.available_modules.find(m => m.id === id);
    return found ? found.name : id;
}

// ── CREATE FORM ──
const createForm = ref({
    name: '',
    code: '',
    brand_name: '',
    phone: '',
    email: '',
    address: '',
    max_customers: null,
    allowed_modules: ['billing', 'customers', 'tickets', 'attendance'],
    admin_name: '',
    admin_email: '',
    admin_password: '',
    payment_banks: [],
    invoice_issue_date: 1,
    billing_cycle: 'calendar',
    tax_ppn: 0,
    midtrans_server_key: '',
    midtrans_merchant_id: '',
});

function openCreateModal() {
    createForm.value = {
        name: '',
        code: '',
        brand_name: '',
        phone: '',
        email: '',
        address: '',
        max_customers: null,
        allowed_modules: ['billing', 'customers', 'tickets', 'attendance'],
        admin_name: '',
        admin_email: '',
        admin_password: '',
        payment_banks: [],
        invoice_issue_date: 1,
        billing_cycle: 'calendar',
        tax_ppn: 0,
        midtrans_server_key: '',
        midtrans_merchant_id: '',
    };
    showCreateModal.value = true;
}

function submitCreateCompany() {
    isSubmitting.value = true;
    router.post('/settings/companies', createForm.value, {
        onSuccess: () => {
            showCreateModal.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
}

// ── EDIT FORM ──
const editForm = ref({
    id: null,
    name: '',
    code: '',
    brand_name: '',
    phone: '',
    email: '',
    address: '',
    status: 'active',
    max_customers: null,
    allowed_modules: [],
    payment_banks: [],
    invoice_issue_date: 1,
    billing_cycle: 'calendar',
    tax_ppn: 0,
    midtrans_server_key: '',
    midtrans_merchant_id: '',
});

function openEditModal(company) {
    selectedEditCompany.value = company;
    editForm.value = {
        id: company.id,
        name: company.name,
        code: company.code,
        brand_name: company.brand_name || '',
        phone: company.phone || '',
        email: company.email || '',
        address: company.address || '',
        status: company.status || 'active',
        max_customers: company.max_customers,
        allowed_modules: Array.isArray(company.allowed_modules) ? [...company.allowed_modules] : ['billing', 'customers', 'tickets'],
        payment_banks: Array.isArray(company.payment_banks) ? JSON.parse(JSON.stringify(company.payment_banks)) : [],
        invoice_issue_date: company.invoice_issue_date || 1,
        billing_cycle: company.billing_cycle || 'calendar',
        tax_ppn: company.tax_ppn || 0,
        midtrans_server_key: company.midtrans_server_key || '',
        midtrans_merchant_id: company.midtrans_merchant_id || '',
    };
    showEditModal.value = true;
}

function addBankRow() {
    if (!Array.isArray(editForm.value.payment_banks)) {
        editForm.value.payment_banks = [];
    }
    editForm.value.payment_banks.push({
        bank_name: '',
        account_number: '',
        account_name: '',
    });
}

function removeBankRow(idx) {
    editForm.value.payment_banks.splice(idx, 1);
}

function submitEditCompany() {
    isSubmitting.value = true;
    router.post(`/settings/companies/${editForm.value.id}/update`, editForm.value, {
        onSuccess: () => {
            showEditModal.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
}

function switchCompanyView(id) {
    router.post(`/settings/companies/switch/${id}`, {}, {
        preserveScroll: true,
    });
}

function confirmDeleteCompany(company) {
    if (confirm(`Apakah Anda yakin ingin menonaktifkan mitra "${company.name}"?`)) {
        router.delete(`/settings/companies/${company.id}`);
    }
}
</script>

