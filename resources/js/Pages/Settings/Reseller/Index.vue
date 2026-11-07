<template>
    <AppLayout title="Pengaturan Reseller" subtitle="Kelola pendaftaran mandiri, persetujuan mitra, dan hak akses voucher">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="p-2 bg-amber-50 text-amber-600 rounded-2xl text-xl">🏪</span>
                        <span>Pengaturan Reseller &amp; Master Biller</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Atur ketentuan pendaftaran mandiri dari login, persetujuan admin, minimal saldo awal, dan hak akses profil voucher
                    </p>
                </div>
                <div>
                    <button
                        type="button"
                        @click="openCreateResellerModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs sm:text-sm rounded-2xl shadow-md shadow-emerald-600/20 transition-all cursor-pointer active:scale-95 whitespace-nowrap"
                    >
                        <span>➕</span>
                        <span>Buat Akun Reseller Baru</span>
                    </button>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Reseller Aktif</span>
                        <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl text-sm font-bold">🤝</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 mt-2 font-mono">{{ stats?.total_resellers || 0 }}</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Akun mitra loket &amp; biller aktif</div>
                </div>

                <div 
                    @click="activeTab = 'pending'"
                    class="bg-white rounded-2xl p-4 sm:p-5 border transition-all cursor-pointer shadow-sm"
                    :class="(stats?.pending_count || 0) > 0 ? 'border-amber-300 ring-2 ring-amber-100 bg-amber-50/20' : 'border-slate-200/80'"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider" :class="(stats?.pending_count || 0) > 0 ? 'text-amber-700' : 'text-slate-500'">Menunggu Approve</span>
                        <span class="p-2 rounded-xl text-sm font-bold" :class="(stats?.pending_count || 0) > 0 ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-slate-100 text-slate-600'">⏳</span>
                    </div>
                    <div class="text-2xl font-black mt-2 font-mono" :class="(stats?.pending_count || 0) > 0 ? 'text-amber-700' : 'text-slate-900'">
                        {{ stats?.pending_count || 0 }}
                    </div>
                    <div class="text-[11px] mt-0.5" :class="(stats?.pending_count || 0) > 0 ? 'text-amber-700 font-bold' : 'text-slate-400'">
                        {{ (stats?.pending_count || 0) > 0 ? 'Perlu tindakan verifikasi admin' : 'Tidak ada permohonan tertunda' }}
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Min. Saldo Reseller</span>
                        <span class="p-2 bg-emerald-50 text-emerald-700 rounded-xl text-sm font-bold">💳</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-emerald-800 mt-2 font-mono">
                        Rp {{ formatRupiah(settingsForm.reseller_min_deposit) }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Syarat modal loket biasa / voucher</div>
                </div>

                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Min. Saldo Biller</span>
                        <span class="p-2 bg-purple-50 text-purple-700 rounded-xl text-sm font-bold">👑</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-purple-900 mt-2 font-mono">
                        Rp {{ formatRupiah(settingsForm.biller_min_deposit) }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5">Syarat modal master biller resmi</div>
                </div>
            </div>

            <!-- Tab Navigation Utama -->
            <div class="flex items-center gap-2 border-b border-slate-200 pb-3 flex-wrap">
                <button
                    type="button"
                    @click="activeTab = 'settings'"
                    class="px-4 py-2.5 rounded-2xl text-xs font-extrabold flex items-center gap-2 transition-all cursor-pointer shadow-xs"
                    :class="activeTab === 'settings' ? 'bg-indigo-600 text-white shadow-indigo-200' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'"
                >
                    <span>⚙️</span>
                    <span>Pengaturan Pendaftaran Mandiri</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'pending'"
                    class="px-4 py-2.5 rounded-2xl text-xs font-extrabold flex items-center gap-2 transition-all cursor-pointer relative shadow-xs"
                    :class="activeTab === 'pending' ? 'bg-amber-500 text-white shadow-amber-200' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'"
                >
                    <span>⏳</span>
                    <span>Menunggu Persetujuan (Pending)</span>
                    <span 
                        v-if="pendingResellers?.length > 0"
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="activeTab === 'pending' ? 'bg-white text-amber-700' : 'bg-amber-100 text-amber-800'"
                    >
                        {{ pendingResellers.length }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'vouchers'"
                    class="px-4 py-2.5 rounded-2xl text-xs font-extrabold flex items-center gap-2 transition-all cursor-pointer shadow-xs"
                    :class="activeTab === 'vouchers' ? 'bg-slate-800 text-white shadow-slate-200' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80'"
                >
                    <span>🏷️</span>
                    <span>Hak Akses Profil Voucher</span>
                </button>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 1: PENGATURAN PENDAFTARAN MANDIRI (RESELLER & BILLER)                 -->
            <!-- ========================================================================= -->
            <div v-if="activeTab === 'settings'" class="space-y-6 animate-fade-in">
                <!-- Info Banner -->
                <div class="p-4 bg-gradient-to-r from-indigo-50 via-sky-50 to-white border border-indigo-200 rounded-3xl flex items-start gap-3.5 shadow-xs">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-lg shrink-0 mt-0.5">
                        💡
                    </div>
                    <div class="text-xs text-indigo-950 space-y-1">
                        <strong class="font-extrabold text-sm block">Konfigurasi Pendaftaran Mandiri Calon Mitra (Halaman Login)</strong>
                        <p class="leading-relaxed text-indigo-900/90">
                            Pengaturan ini khusus mengatur calon mitra yang mendaftar mandiri via link <em>"Daftar sekarang"</em> pada portal Mitra Biller di halaman login. Anda dapat mengaktifkan atau menonaktifkan jalur Reseller dan Biller, mengatur syarat minimal saldo modal awal, mewajibkan persetujuan (approve) Admin, serta menyunting syarat &amp; ketentuan resmi.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="saveRegistrationSettings" class="space-y-6">
                    <!-- Section 1: Ketersediaan Pendaftaran (Reseller & Biller) -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div>
                                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                                    <span>🔘</span>
                                    <span>Jalur Pendaftaran Mandiri yang Diizinkan</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">Tentukan jenis kemitraan yang dapat dipilih calon pendaftar di halaman registrasi</p>
                            </div>
                            
                            <!-- Preset Cepat -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-[11px] font-bold text-slate-400 mr-1">Preset Cepat:</span>
                                <button
                                    type="button"
                                    @click="setRegistrationPreset('all')"
                                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition cursor-pointer"
                                >
                                    ✓ Aktifkan Semua (Reseller &amp; Biller)
                                </button>
                                <button
                                    type="button"
                                    @click="setRegistrationPreset('reseller_only')"
                                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                                >
                                    Hanya Reseller
                                </button>
                                <button
                                    type="button"
                                    @click="setRegistrationPreset('biller_only')"
                                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition cursor-pointer"
                                >
                                    Hanya Biller
                                </button>
                                <button
                                    type="button"
                                    @click="setRegistrationPreset('none')"
                                    class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition cursor-pointer"
                                >
                                    Tutup Semua
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Switch 1: Reseller -->
                            <label 
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                :class="settingsForm.reseller_registration_enabled ? 'border-emerald-500 bg-emerald-50/40 ring-1 ring-emerald-200' : 'border-slate-200 bg-slate-50/50'"
                            >
                                <input
                                    type="checkbox"
                                    v-model="settingsForm.reseller_registration_enabled"
                                    class="w-5 h-5 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 mt-0.5 cursor-pointer"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <strong class="text-sm font-extrabold text-slate-900">Pendaftaran Mitra Reseller</strong>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase" :class="settingsForm.reseller_registration_enabled ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'">
                                            {{ settingsForm.reseller_registration_enabled ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                        Izinkan pendaftaran mandiri calon Mitra Reseller (fokus penjualan voucher hotspot eceran &amp; loket pembayaran warga).
                                    </p>
                                </div>
                            </label>

                            <!-- Switch 2: Biller -->
                            <label 
                                class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                                :class="settingsForm.biller_registration_enabled ? 'border-purple-600 bg-purple-50/40 ring-1 ring-purple-200' : 'border-slate-200 bg-slate-50/50'"
                            >
                                <input
                                    type="checkbox"
                                    v-model="settingsForm.biller_registration_enabled"
                                    class="w-5 h-5 text-purple-600 rounded border-slate-300 focus:ring-purple-500 mt-0.5 cursor-pointer"
                                />
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <strong class="text-sm font-extrabold text-slate-900">Pendaftaran Master Biller</strong>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase" :class="settingsForm.biller_registration_enabled ? 'bg-purple-100 text-purple-800' : 'bg-slate-200 text-slate-600'">
                                            {{ settingsForm.biller_registration_enabled ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                                        Izinkan pendaftaran mandiri calon Master Biller (loket pembayaran resmi tagihan internet, bina downline, &amp; royalti distribusi).
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Section 2: Ketentuan Minimal Pengisian Saldo Awal -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-5">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <span>💰</span>
                                <span>Ketentuan Minimal Pengisian Saldo Modal Awal</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Besaran saldo modal awal yang harus disetorkan calon mitra untuk dapat mengaktifkan akun dan melayani transaksi</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Input Minimal Saldo Reseller (Default 20.000) -->
                            <div class="p-4 rounded-2xl bg-emerald-50/30 border border-emerald-200/80 space-y-2">
                                <label for="reseller_min_deposit" class="block text-xs font-extrabold uppercase tracking-wider text-emerald-900">
                                    Minimal Isi Saldo Reseller (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-emerald-700">Rp</span>
                                    <input
                                        id="reseller_min_deposit"
                                        type="number"
                                        v-model.number="settingsForm.reseller_min_deposit"
                                        min="0"
                                        step="1000"
                                        required
                                        placeholder="20000"
                                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-emerald-300 rounded-xl text-sm font-black font-mono text-emerald-950 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    />
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-emerald-800">
                                    <span>Tampilan Nominal:</span>
                                    <strong class="font-mono font-black text-xs">Rp {{ formatRupiah(settingsForm.reseller_min_deposit) }}</strong>
                                </div>
                                <p class="text-[10.5px] text-slate-500 leading-relaxed">
                                    Default SOP: <strong>Rp 20.000</strong>. Calon reseller wajib mengisi saldo modal awal minimal sejumlah ini.
                                </p>
                            </div>

                            <!-- Input Minimal Saldo Biller (Default 2.000.000) -->
                            <div class="p-4 rounded-2xl bg-purple-50/30 border border-purple-200/80 space-y-2">
                                <label for="biller_min_deposit" class="block text-xs font-extrabold uppercase tracking-wider text-purple-950">
                                    Minimal Isi Saldo Master Biller (Rp) <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-purple-700">Rp</span>
                                    <input
                                        id="biller_min_deposit"
                                        type="number"
                                        v-model.number="settingsForm.biller_min_deposit"
                                        min="0"
                                        step="10000"
                                        required
                                        placeholder="2000000"
                                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-purple-300 rounded-xl text-sm font-black font-mono text-purple-950 focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    />
                                </div>
                                <div class="flex items-center justify-between text-[11px] text-purple-900">
                                    <span>Tampilan Nominal:</span>
                                    <strong class="font-mono font-black text-xs">Rp {{ formatRupiah(settingsForm.biller_min_deposit) }}</strong>
                                </div>
                                <p class="text-[10.5px] text-slate-500 leading-relaxed">
                                    Default SOP: <strong>Rp 2.000.000</strong>. Calon master biller wajib mengisi modal awal minimal sejumlah ini.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Kebijakan Persetujuan (Approve) Admin -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <span>🛡️</span>
                                <span>Kebijakan Persetujuan (Approval) Admin</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Tentukan apakah pendaftaran mandiri memerlukan verifikasi dan persetujuan Admin sebelum akun dapat login</p>
                        </div>

                        <label 
                            class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex items-start gap-3.5"
                            :class="settingsForm.reseller_registration_require_approval ? 'border-amber-400 bg-amber-50/40 ring-1 ring-amber-200' : 'border-slate-200 bg-slate-50/50'"
                        >
                            <input
                                type="checkbox"
                                v-model="settingsForm.reseller_registration_require_approval"
                                class="w-5 h-5 text-amber-600 rounded border-slate-300 focus:ring-amber-500 mt-0.5 cursor-pointer"
                            />
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <strong class="text-sm font-extrabold text-slate-900">Wajibkan Persetujuan (Approve) Admin Sebelum Akun Aktif</strong>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase" :class="settingsForm.reseller_registration_require_approval ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600'">
                                        {{ settingsForm.reseller_registration_require_approval ? 'Wajib Approval' : 'Aktivasi Otomatis' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                    <strong>Direkomendasikan Aktif:</strong> Setiap calon mitra yang mendaftar mandiri akan berstatus <em>Menunggu Persetujuan (Pending)</em>. Mereka tidak dapat langsung login ke portal hingga Admin memverifikasi identitas, memeriksa transfer saldo awal minimal, dan mengklik tombol <strong>Approve &amp; Aktifkan</strong> di tab <em>"Menunggu Persetujuan"</em>.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Section 4: Syarat dan Ketentuan Kemitraan -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border border-slate-200/80 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                                    <span>📜</span>
                                    <span>Syarat &amp; Ketentuan Kemitraan (Terms &amp; Conditions)</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">Teks ini akan ditampilkan kepada calon pendaftar di halaman pendaftaran mandiri dan wajib disetujui</p>
                            </div>
                            <button
                                type="button"
                                @click="resetTermsToDefault"
                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline cursor-pointer"
                            >
                                Kembalikan ke Teks Standar
                            </button>
                        </div>

                        <div>
                            <textarea
                                v-model="settingsForm.reseller_registration_terms"
                                rows="7"
                                required
                                class="w-full text-xs font-mono font-medium p-4 border border-slate-200 rounded-2xl bg-slate-50/60 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 leading-relaxed transition"
                                placeholder="Tuliskan butir-butir syarat dan ketentuan kemitraan..."
                            ></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Calon mitra wajib mencentang persetujuan atas butir-butir ketentuan di atas sebelum formulir pendaftaran dapat dikirim.
                            </p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="settingsForm.processing"
                            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-2xl shadow-md shadow-indigo-500/25 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <svg v-if="settingsForm.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span>{{ settingsForm.processing ? 'Menyimpan Pengaturan...' : '💾 Simpan Pengaturan Pendaftaran Reseller' }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 2: MENUNGGU PERSETUJUAN ADMIN (PENDING APPROVAL)                     -->
            <!-- ========================================================================= -->
            <div v-if="activeTab === 'pending'" class="space-y-6 animate-fade-in">
                <!-- Header Banner Pending -->
                <div class="p-4 bg-amber-50/90 border border-amber-200 rounded-3xl flex items-start gap-3.5 shadow-xs">
                    <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg shrink-0 mt-0.5">
                        ⏳
                    </div>
                    <div class="text-xs text-amber-950 space-y-1 flex-1">
                        <strong class="font-extrabold text-sm block">Daftar Calon Mitra Menunggu Persetujuan ({{ pendingResellers?.length || 0 }} Pendaftar)</strong>
                        <p class="leading-relaxed text-amber-900/90">
                            Calon mitra di bawah ini telah mengisi formulir pendaftaran mandiri melalui halaman login. Silakan verifikasi data calon mitra, pastikan setoran saldo modal awal (<strong>Rp {{ formatRupiah(settingsForm.reseller_min_deposit) }}</strong> untuk Reseller / <strong>Rp {{ formatRupiah(settingsForm.biller_min_deposit) }}</strong> untuk Biller) telah dipenuhi, lalu klik tombol <strong>Approve &amp; Aktifkan</strong>.
                        </p>
                    </div>
                </div>

                <!-- Tabel Pendaftar Pending -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50/80 text-slate-500 font-extrabold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-5 py-4">Calon Mitra / Toko</th>
                                    <th class="px-5 py-4">Kontak (WhatsApp &amp; Email)</th>
                                    <th class="px-5 py-4">Tipe Kemitraan</th>
                                    <th class="px-5 py-4">Wilayah &amp; Alamat</th>
                                    <th class="px-5 py-4">Tgl Daftar</th>
                                    <th class="px-5 py-4 text-center">Tindakan Persetujuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="p in pendingResellers" :key="p.id" class="hover:bg-slate-50/60 transition-colors">
                                    <!-- Calon Mitra & Toko -->
                                    <td class="px-5 py-4">
                                        <div class="font-extrabold text-slate-900 text-sm">{{ p.name }}</div>
                                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">ID Reseller #{{ p.id }}</div>
                                    </td>

                                    <!-- Kontak -->
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-1.5 font-bold text-slate-800">
                                            <a 
                                                v-if="p.clean_phone"
                                                :href="'https://wa.me/' + p.clean_phone" 
                                                target="_blank" 
                                                class="text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1"
                                                title="Chat langsung di WhatsApp"
                                            >
                                                <span>💬</span>
                                                <span>{{ p.phone }}</span>
                                            </a>
                                            <span v-else>{{ p.phone || '-' }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ p.email }}</div>
                                    </td>

                                    <!-- Tipe Kemitraan & Min Deposit -->
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-black inline-flex items-center gap-1 border shadow-2xs" :class="p.tier === 'master' ? 'bg-purple-100 text-purple-800 border-purple-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200'">
                                            <span>{{ p.tier === 'master' ? '👑' : '🏪' }}</span>
                                            <span>{{ p.tier_label }}</span>
                                        </span>
                                        <div class="text-[10.5px] text-slate-500 mt-1">
                                            Min. Modal: <strong class="text-slate-800 font-mono">Rp {{ formatRupiah(p.min_deposit) }}</strong>
                                        </div>
                                    </td>

                                    <!-- Wilayah & Alamat -->
                                    <td class="px-5 py-4 max-w-xs">
                                        <div class="font-bold text-indigo-700 text-xs">{{ p.area }}</div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-2 leading-relaxed">{{ p.address }}</div>
                                    </td>

                                    <!-- Tanggal Daftar -->
                                    <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-medium">
                                        {{ p.created_at }}
                                    </td>

                                    <!-- Tindakan -->
                                    <td class="px-5 py-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Tombol Approve -->
                                            <button
                                                type="button"
                                                @click="openApproveModal(p)"
                                                class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
                                            >
                                                <span>✓</span>
                                                <span>Approve &amp; Aktifkan</span>
                                            </button>

                                            <!-- Tombol Tolak -->
                                            <button
                                                type="button"
                                                @click="openRejectModal(p)"
                                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-xl transition cursor-pointer"
                                            >
                                                <span>✕ Tolak</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Baris Kosong -->
                                <tr v-if="!pendingResellers || pendingResellers.length === 0">
                                    <td colspan="6" class="px-6 py-16 text-center text-slate-500">
                                        <div class="w-14 h-14 rounded-3xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner border border-emerald-100">
                                            🎉
                                        </div>
                                        <div class="text-sm font-black text-slate-900">Tidak Ada Permohonan Menunggu Persetujuan</div>
                                        <div class="text-xs text-slate-400 mt-0.5 max-w-md mx-auto">
                                            Seluruh pendaftaran mitra telah diproses dan aktif. Permohonan mandiri yang baru akan otomatis muncul pada tabel ini.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 3: HAK AKSES PROFIL VOUCHER HOTSPOT (FITUR SEBELUMNYA)                 -->
            <!-- ========================================================================= -->
            <div v-if="activeTab === 'vouchers'" class="space-y-6 animate-fade-in">
                <!-- Toolbar: Role Filter Tabs & Search -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-200/80 space-y-4">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <!-- Role Filter Tabs -->
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <button 
                                type="button" 
                                @click="setRole('reseller')" 
                                class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                                :class="currentRole === 'reseller' ? 'bg-amber-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            >
                                <span>🟠</span>
                                <span>Hanya Reseller</span>
                                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px]" :class="currentRole === 'reseller' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
                                    {{ stats?.total_resellers || 0 }}
                                </span>
                            </button>

                            <button 
                                type="button" 
                                @click="setRole('all')" 
                                class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                                :class="currentRole === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            >
                                <span>👥</span>
                                <span>Semua Pengguna</span>
                            </button>

                            <button 
                                type="button" 
                                @click="setRole('customer')" 
                                class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                                :class="currentRole === 'customer' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            >
                                <span>🏠</span>
                                <span>Pelanggan</span>
                            </button>

                            <button 
                                type="button" 
                                @click="setRole('staff')" 
                                class="px-4 py-2 rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all cursor-pointer"
                                :class="currentRole === 'staff' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            >
                                <span>🛠️</span>
                                <span>Staff / Admin</span>
                            </button>
                        </div>

                        <!-- Search & Quick Add Button -->
                        <div class="flex items-center gap-2 w-full lg:w-auto">
                            <div class="relative w-full lg:w-72">
                                <input 
                                    v-model="search" 
                                    @input="onSearch"
                                    type="text" 
                                    placeholder="Cari nama, email, no HP..." 
                                    class="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition-all font-medium text-slate-800"
                                >
                                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <button 
                                    v-if="search" 
                                    @click="clearSearch" 
                                    class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs font-bold"
                                >
                                    ✕
                                </button>
                            </div>
                            <button 
                                type="button" 
                                @click="openCreateResellerModal" 
                                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all shadow-xs cursor-pointer active:scale-95 whitespace-nowrap shrink-0"
                            >
                                <span>➕</span>
                                <span>Tambah Reseller</span>
                            </button>
                        </div>
                    </div>

                    <!-- Info banner saat filter Reseller aktif -->
                    <div v-if="currentRole === 'reseller'" class="p-3 bg-amber-50/60 border border-amber-200/60 rounded-2xl flex items-center justify-between text-xs text-amber-900">
                        <div class="flex items-center gap-2">
                            <span>💡</span>
                            <span>Menampilkan mitra <strong>Reseller</strong>. Gunakan tombol <strong>Atur Akses</strong> untuk membatasi profil voucher hotspot yang boleh dijual oleh masing-masing reseller.</span>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data Pengguna / Reseller -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">User / Reseller</th>
                                    <th class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Role &amp; Status</th>
                                    <th class="px-6 py-4 text-left text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Akses Profil Voucher</th>
                                    <th class="px-6 py-4 text-right text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/60 transition-colors">
                                    <!-- Kolom User -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-black shadow-sm" :class="getUserAvatarClass(user.role)">
                                                {{ getUserInitial(user.name) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                                                    <span>{{ user.name }}</span>
                                                    <span v-if="user.reseller" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300">
                                                        Mitra Reseller
                                                    </span>
                                                </div>
                                                <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-2">
                                                    <span>{{ user.email }}</span>
                                                    <span v-if="user.phone" class="text-slate-400">• {{ user.phone }}</span>
                                                </div>
                                                <div v-if="user.customer" class="text-[11px] text-indigo-600 font-semibold mt-0.5 flex items-center gap-1">
                                                    <span>Pelanggan: {{ user.customer.name }} ({{ user.customer.customer_code }})</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kolom Role -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 text-[11px] font-black rounded-full uppercase tracking-wider border shadow-xs" :class="getRoleBadgeClass(user.role)">
                                            {{ user.role || 'User' }}
                                        </span>
                                    </td>

                                    <!-- Kolom Hak Akses Profil -->
                                    <td class="px-6 py-4">
                                        <!-- Mode Akses Penuh (Default) -->
                                        <div v-if="!user.voucher_profiles || user.voucher_profiles.length === 0">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                                <span>🌐 Semua Profil (Bebas / Default)</span>
                                            </span>
                                            <p class="text-[11px] text-slate-400 mt-1 italic">Dapat menjual seluruh paket voucher aktif</p>
                                        </div>

                                        <!-- Mode Dibatasi ke Profil Tertentu -->
                                        <div v-else class="space-y-1.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    🔒 Dibatasi ({{ user.voucher_profiles.length }} Profil)
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-1 max-w-lg">
                                                <span 
                                                    v-for="profile in user.voucher_profiles" 
                                                    :key="profile.id" 
                                                    class="px-2 py-0.5 text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg border border-slate-200 transition-colors"
                                                >
                                                    {{ profile.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kolom Aksi -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-bold">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- View Detail Button -->
                                            <button 
                                                type="button"
                                                @click="openViewModal(user)" 
                                                class="p-2 rounded-xl text-sky-600 bg-sky-50 hover:bg-sky-600 hover:text-white border border-sky-200 transition-all shadow-2xs cursor-pointer active:scale-95"
                                                title="Lihat Detail Lengkap Akun & Kemitraan"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>

                                            <!-- Edit Button -->
                                            <button 
                                                type="button"
                                                @click="openEditModal(user)" 
                                                class="p-2 rounded-xl text-amber-600 bg-amber-50 hover:bg-amber-600 hover:text-white border border-amber-200 transition-all shadow-2xs cursor-pointer active:scale-95"
                                                title="Edit Data Pengguna & Kemitraan"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>

                                            <!-- Atur Akses Voucher Button -->
                                            <button 
                                                type="button"
                                                @click="openSettingModal(user)" 
                                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-extrabold bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white border border-indigo-200 hover:border-transparent transition-all shadow-2xs cursor-pointer active:scale-95"
                                                title="Atur Pembatasan Akses Profil Voucher Hotspot"
                                            >
                                                <span>⚙️</span>
                                                <span>Atur Akses</span>
                                            </button>

                                            <!-- Delete Button -->
                                            <button 
                                                type="button"
                                                v-if="user.id !== 1 && user.email !== 'admin@isp.local'"
                                                @click="confirmDeleteUser(user)" 
                                                class="p-2 rounded-xl text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white border border-rose-200 transition-all shadow-2xs cursor-pointer active:scale-95"
                                                title="Hapus Akun Pengguna & Kemitraan"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Baris Kosong -->
                                <tr v-if="!users.data || users.data.length === 0">
                                    <td colspan="4" class="px-6 py-16 text-center text-slate-500 font-medium">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-xl mx-auto mb-3">🔍</div>
                                        <div class="text-sm font-bold text-slate-800">Tidak ada pengguna yang cocok</div>
                                        <div class="text-xs text-slate-400 mt-0.5">Silakan ganti kata kunci pencarian atau ubah tab filter role.</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-slate-100 bg-slate-50/60 flex items-center justify-between text-xs text-slate-500">
                        <div>
                            Menampilkan <strong class="text-slate-800">{{ users.from || 0 }}</strong> - <strong class="text-slate-800">{{ users.to || 0 }}</strong> dari <strong class="text-slate-800">{{ users.total || 0 }}</strong> data
                        </div>
                        <div class="flex items-center gap-1">
                            <template v-for="link in users.links" :key="link.label">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    v-html="link.label" 
                                    class="px-3 py-1.5 border rounded-xl text-xs font-bold transition-all" 
                                    :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-200'"
                                ></Link>
                                <span 
                                    v-else 
                                    v-html="link.label" 
                                    class="px-3 py-1.5 border rounded-xl text-xs text-slate-300 bg-slate-50 border-slate-100 cursor-not-allowed"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 1: ATUR AKSES PROFIL VOUCHER (EXISTING)                             -->
            <!-- ========================================================================= -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[90vh] border border-slate-100">
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-indigo-50 text-indigo-600 rounded-xl text-lg">🔑</span>
                            <div>
                                <h2 class="text-base font-black text-slate-900">Atur Akses Profil Voucher</h2>
                                <p class="text-[11px] text-slate-500">Tentukan profil voucher yang diizinkan untuk akun ini</p>
                            </div>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
                            ✕
                        </button>
                    </div>
                    
                    <div class="px-6 py-3.5 bg-indigo-50/70 border-b border-indigo-100/60 flex items-center justify-between">
                        <div>
                            <div class="text-sm font-extrabold text-indigo-950">{{ selectedUser?.name }}</div>
                            <div class="text-xs text-indigo-700 mt-0.5">{{ selectedUser?.email }}</div>
                        </div>
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200">
                            {{ selectedUser?.role }}
                        </span>
                    </div>

                    <div class="p-6 overflow-y-auto flex-1 space-y-4">
                        <div class="flex items-center justify-between gap-2 pb-2 border-b border-slate-100">
                            <div class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Pilihan Profil Voucher:
                            </div>
                            <div class="flex items-center gap-2">
                                <button 
                                    type="button" 
                                    @click="selectAllProfiles" 
                                    class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline cursor-pointer"
                                >
                                    Pilih Semua
                                </button>
                                <span class="text-slate-300">•</span>
                                <button 
                                    type="button" 
                                    @click="clearAllProfiles" 
                                    class="text-xs font-bold text-rose-600 hover:text-rose-800 underline cursor-pointer"
                                >
                                    Bebaskan (Semua Profil)
                                </button>
                            </div>
                        </div>

                        <div v-if="form.voucher_profile_ids.length === 0" class="p-3.5 rounded-2xl bg-sky-50 border border-sky-200 text-xs text-sky-900 leading-relaxed">
                            <strong class="font-extrabold text-sky-950 block mb-0.5">🌐 Mode Akses Penuh (Default)</strong>
                            Tidak ada profil yang dibatasi. Reseller ini diizinkan menjual <strong>seluruh profil voucher hotspot aktif</strong>. Centang profil di bawah jika ingin membatasi akses.
                        </div>
                        <div v-else class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                            <strong class="font-extrabold text-amber-950 block mb-0.5">🔒 Mode Akses Dibatasi</strong>
                            Reseller ini <strong>hanya diizinkan</strong> menjual <strong>{{ form.voucher_profile_ids.length }}</strong> profil voucher yang Anda centang di bawah.
                        </div>

                        <div class="space-y-2.5 pt-1">
                            <label 
                                v-for="profile in sellableProfiles" 
                                :key="profile.id" 
                                class="flex items-start p-3.5 border-2 rounded-2xl cursor-pointer transition-all"
                                :class="form.voucher_profile_ids.includes(profile.id) ? 'border-indigo-600 bg-indigo-50/40 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                            >
                                <div class="flex items-center h-5 mt-0.5">
                                    <input 
                                        type="checkbox" 
                                        :value="profile.id" 
                                        v-model="form.voucher_profile_ids" 
                                        class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 cursor-pointer"
                                    >
                                </div>
                                <div class="ml-3.5 flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="block text-sm font-extrabold text-slate-900">{{ profile.name }}</span>
                                        <span class="text-xs font-black text-indigo-700 font-mono">Rp {{ formatRupiah(profile.price) }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2 flex-wrap">
                                        <span v-if="profile.duration">Durasi: <strong>{{ profile.duration }}</strong></span>
                                        <span v-if="profile.limit_rate">• Speed: <strong>{{ profile.limit_rate }}</strong></span>
                                        <span v-if="profile.fee_reseller" class="text-emerald-700 font-bold">• Komisi Reseller: Rp {{ formatRupiah(profile.fee_reseller) }}</span>
                                    </div>
                                </div>
                            </label>

                            <div v-if="sellableProfiles.length === 0" class="text-center py-6 text-xs text-slate-400 italic">
                                Belum ada profil voucher yang tersedia di sistem.
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-slate-500">
                            {{ form.voucher_profile_ids.length > 0 ? form.voucher_profile_ids.length + ' profil dipilih' : 'Semua profil diizinkan' }}
                        </span>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="showModal = false" 
                                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-extrabold text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="saveSettings" 
                                class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-xs font-extrabold hover:bg-indigo-700 transition-all shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-50" 
                                :disabled="form.processing"
                            >
                                <svg v-if="form.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 2: KONFIRMASI APPROVE PENDAFTARAN MITRA                             -->
            <!-- ========================================================================= -->
            <div v-if="showApproveModal && selectedPending" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-100">
                    <div class="px-6 py-5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center text-xl">✓</span>
                            <div>
                                <h3 class="text-base font-extrabold">Approve &amp; Aktifkan Akun Mitra</h3>
                                <p class="text-xs text-emerald-100 mt-0.5">Verifikasi pendaftaran dan tentukan saldo awal</p>
                            </div>
                        </div>
                        <button @click="showApproveModal = false" class="text-white/80 hover:text-white text-lg font-bold">✕</button>
                    </div>

                    <form @submit.prevent="submitApprove" class="p-6 space-y-4">
                        <!-- Data Mitra -->
                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Nama Calon Mitra:</span>
                                <strong class="text-slate-800 text-sm">{{ selectedPending.name }}</strong>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Tipe Kemitraan:</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="selectedPending.tier === 'master' ? 'bg-purple-100 text-purple-800' : 'bg-emerald-100 text-emerald-800'">
                                    {{ selectedPending.tier === 'master' ? '👑 Master Biller' : '🏪 Mitra Reseller' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Kontak WhatsApp:</span>
                                <span class="font-mono text-slate-800 font-bold">{{ selectedPending.phone }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Ketentuan Min. Modal:</span>
                                <strong class="text-emerald-700 font-mono">Rp {{ formatRupiah(selectedPending.min_deposit) }}</strong>
                            </div>
                        </div>

                        <!-- Input Saldo Modal Awal -->
                        <div>
                            <label for="approve_initial_balance" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Saldo Awal Aktivasi (Rp)
                            </label>
                            <input
                                id="approve_initial_balance"
                                type="number"
                                v-model.number="approveForm.initial_balance"
                                min="0"
                                step="1000"
                                placeholder="0"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            />
                            <p class="text-[11px] text-slate-500 mt-1">
                                Masukkan nominal saldo modal awal jika calon mitra sudah melakukan transfer (Default: 0 jika saldo diisi nanti via top-up).
                            </p>
                        </div>

                        <!-- Catatan Aktivasi -->
                        <div>
                            <label for="approve_notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Catatan Aktivasi (Opsional)
                            </label>
                            <input
                                id="approve_notes"
                                type="text"
                                v-model="approveForm.notes"
                                placeholder="Contoh: Bukti transfer BCA verified"
                                class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="showApproveModal = false"
                                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="approveForm.processing"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="approveForm.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ approveForm.processing ? 'Mengaktifkan...' : '✓ Setujui & Aktifkan Sekarang' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 3: KONFIRMASI TOLAK PENDAFTARAN MITRA                               -->
            <!-- ========================================================================= -->
            <div v-if="showRejectModal && selectedPending" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
                    <div class="px-6 py-4 bg-rose-600 text-white flex items-center justify-between">
                        <h3 class="text-base font-extrabold flex items-center gap-2">
                            <span>⚠️</span>
                            <span>Tolak Pendaftaran Mitra</span>
                        </h3>
                        <button @click="showRejectModal = false" class="text-white/80 hover:text-white text-lg font-bold">✕</button>
                    </div>

                    <form @submit.prevent="submitReject" class="p-6 space-y-4">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Apakah Anda yakin ingin menolak pendaftaran kemitraan <strong>{{ selectedPending.name }}</strong> ({{ selectedPending.tier_label }})? Data registrasi pending akan dibersihkan dari antrean.
                        </p>

                        <div>
                            <label for="reject_reason" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Alasan Penolakan (Opsional)
                            </label>
                            <textarea
                                id="reject_reason"
                                v-model="rejectForm.reason"
                                rows="3"
                                placeholder="Misal: Nomor WhatsApp tidak aktif / belum memenuhi syarat minimal saldo awal"
                                class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="showRejectModal = false"
                                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="rejectForm.processing"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-rose-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="rejectForm.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ rejectForm.processing ? 'Menolak...' : '✕ Ya, Tolak Pendaftaran' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 4: LIHAT DETAIL PENGGUNA & KEMITRAAN (VIEW)                         -->
            <!-- ========================================================================= -->
            <div v-if="showViewModal && selectedUserForView" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[92vh] border border-slate-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-sky-50 text-sky-600 rounded-xl text-lg">👁️</span>
                            <div>
                                <h2 class="text-base font-black text-slate-900">Detail Akun &amp; Kemitraan</h2>
                                <p class="text-[11px] text-slate-500">Informasi profil pengguna, status kemitraan, dan hak akses voucher</p>
                            </div>
                        </div>
                        <button @click="showViewModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 overflow-y-auto flex-1 space-y-5 text-xs text-slate-700">
                        <!-- Profil Singkat Banner -->
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-50 to-indigo-50/40 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-lg font-black shadow-sm shrink-0" :class="getUserAvatarClass(selectedUserForView.role)">
                                    {{ getUserInitial(selectedUserForView.name) }}
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2 flex-wrap">
                                        <span>{{ selectedUserForView.name }}</span>
                                        <span v-if="selectedUserForView.reseller" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="selectedUserForView.reseller.tier === 'master' ? 'bg-purple-100 text-purple-800 border border-purple-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-300'">
                                            {{ selectedUserForView.reseller.tier === 'master' ? '👑 Master Biller' : '🏪 Mitra Reseller' }}
                                        </span>
                                    </h3>
                                    <p class="text-slate-500 mt-0.5">{{ selectedUserForView.email }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border shadow-2xs" :class="getRoleBadgeClass(selectedUserForView.role)">
                                            Role: {{ selectedUserForView.role }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold" :class="selectedUserForView.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                                            {{ selectedUserForView.is_active ? '● Akun Aktif' : '○ Nonaktif' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button Hubungi WA jika ada phone -->
                            <div v-if="selectedUserForView.phone" class="sm:self-center shrink-0">
                                <a 
                                    :href="getWhatsAppLink(selectedUserForView.phone, selectedUserForView.name)" 
                                    target="_blank" 
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition"
                                >
                                    <span>💬</span>
                                    <span>Chat WhatsApp</span>
                                </a>
                            </div>
                        </div>

                        <!-- Grid 2 Kolom: Detail Akun & Kemitraan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Info Kontak & Akun -->
                            <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-2.5">
                                <h4 class="font-extrabold text-slate-900 text-xs flex items-center gap-1.5 border-b border-slate-100 pb-2">
                                    <span>👤</span>
                                    <span>Data Kontak &amp; Login</span>
                                </h4>
                                <div class="space-y-1.5">
                                    <div class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">ID Pengguna:</span>
                                        <span class="font-mono font-bold text-slate-800">#{{ selectedUserForView.id }}</span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">Email:</span>
                                        <span class="font-bold text-slate-800 select-all">{{ selectedUserForView.email }}</span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">No. WhatsApp / HP:</span>
                                        <span class="font-bold text-slate-800">{{ selectedUserForView.phone || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">Terdaftar Sejak:</span>
                                        <span class="font-bold text-slate-800">{{ formatDate(selectedUserForView.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Info Kemitraan Reseller -->
                            <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-2.5">
                                <h4 class="font-extrabold text-slate-900 text-xs flex items-center gap-1.5 border-b border-slate-100 pb-2">
                                    <span>🏪</span>
                                    <span>Status Kemitraan Loket/Biller</span>
                                </h4>
                                <div v-if="selectedUserForView.reseller || selectedUserForView.customer?.reseller" class="space-y-1.5">
                                    <div class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">Tipe Kemitraan:</span>
                                        <span class="font-black" :class="(selectedUserForView.reseller?.tier || selectedUserForView.customer?.reseller?.tier) === 'master' ? 'text-purple-700' : 'text-emerald-700'">
                                            {{ (selectedUserForView.reseller?.tier || selectedUserForView.customer?.reseller?.tier) === 'master' ? '👑 Master Biller' : '🏪 Mitra Reseller Biasa' }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">Saldo Modal Deposit:</span>
                                        <span class="font-black font-mono text-emerald-700 text-sm">
                                            Rp {{ formatRupiah((selectedUserForView.reseller || selectedUserForView.customer?.reseller)?.balance) }}
                                        </span>
                                    </div>
                                    <div v-if="(selectedUserForView.reseller || selectedUserForView.customer?.reseller)?.referral_code" class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">Kode Referral:</span>
                                        <span class="font-mono font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">
                                            {{ (selectedUserForView.reseller || selectedUserForView.customer?.reseller)?.referral_code }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-slate-50">
                                        <span class="text-slate-400">Status Mitra:</span>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase" :class="(selectedUserForView.reseller || selectedUserForView.customer?.reseller)?.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                                            {{ (selectedUserForView.reseller || selectedUserForView.customer?.reseller)?.status || 'Aktif' }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="py-4 text-center text-slate-400">
                                    <span class="text-2xl block mb-1">ℹ️</span>
                                    <span>User ini belum memiliki data kemitraan reseller resmi.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Pelanggan Terkait (Jika ada) -->
                        <div v-if="selectedUserForView.customer" class="p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100 space-y-2">
                            <h4 class="font-extrabold text-indigo-950 text-xs flex items-center gap-1.5">
                                <span>🏠</span>
                                <span>Pelanggan Internet Rumah Terhubung</span>
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] pt-1">
                                <div>
                                    <span class="text-slate-400 block">Kode Pelanggan:</span>
                                    <strong class="text-slate-900 font-mono">{{ selectedUserForView.customer.customer_code }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Wilayah / Area:</span>
                                    <strong class="text-slate-900">{{ selectedUserForView.customer.areaModel?.name || selectedUserForView.customer.area || '-' }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Paket Langganan:</span>
                                    <strong class="text-slate-900">{{ selectedUserForView.customer.package?.name || '-' }}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-400 block">Status Layanan:</span>
                                    <strong class="text-emerald-700 uppercase">{{ selectedUserForView.customer.status || 'Active' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Akses Profil Voucher -->
                        <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-2.5">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <h4 class="font-extrabold text-slate-900 text-xs flex items-center gap-1.5">
                                    <span>🏷️</span>
                                    <span>Hak Akses Profil Voucher Hotspot</span>
                                </h4>
                                <button 
                                    type="button" 
                                    @click="showViewModal = false; openSettingModal(selectedUserForView)"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer"
                                >
                                    Ubah Akses &rarr;
                                </button>
                            </div>
                            <div v-if="!selectedUserForView.voucher_profiles || selectedUserForView.voucher_profiles.length === 0" class="flex items-center gap-2 text-xs font-bold text-sky-700 bg-sky-50 p-2.5 rounded-xl border border-sky-200">
                                <span>🌐</span>
                                <span>Semua Profil (Bebas / Default) — Dapat menjual seluruh paket voucher aktif tanpa batasan.</span>
                            </div>
                            <div v-else class="space-y-1.5">
                                <div class="text-[11px] text-slate-500">Dibatasi hanya boleh menjual paket berikut:</div>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="vp in selectedUserForView.voucher_profiles" 
                                        :key="vp.id" 
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200 flex items-center gap-1"
                                    >
                                        <span>🎫</span>
                                        <span>{{ vp.name }}</span>
                                        <span v-if="vp.price" class="text-emerald-700 font-mono">({{ formatRupiah(vp.price) }})</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            @click="showViewModal = false"
                            class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 cursor-pointer"
                        >
                            Tutup
                        </button>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="showViewModal = false; openEditModal(selectedUserForView)"
                                class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                            >
                                <span>✏️</span>
                                <span>Edit Akun Ini</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 5: EDIT DATA PENGGUNA & KEMITRAAN RESELLER (EDIT)                   -->
            <!-- ========================================================================= -->
            <div v-if="showEditModal && selectedUserForEdit" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[92vh] border border-slate-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-amber-50 text-amber-600 rounded-xl text-lg">✏️</span>
                            <div>
                                <h2 class="text-base font-black text-slate-900">Edit Akun &amp; Kemitraan</h2>
                                <p class="text-[11px] text-slate-500">Perbarui data login, role, saldo modal, dan status kemitraan</p>
                            </div>
                        </div>
                        <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <form @submit.prevent="submitEditUser" class="flex flex-col flex-1 overflow-hidden">
                        <!-- Body -->
                        <div class="p-6 overflow-y-auto flex-1 space-y-4">
                            <!-- Bagian 1: Data Pengguna -->
                            <div class="space-y-3">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-500 border-b border-slate-100 pb-1">
                                    Informasi Login &amp; Kontak
                                </h4>

                                <div>
                                    <label for="edit_user_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Nama Lengkap <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="edit_user_name"
                                        v-model="editUserForm.name"
                                        type="text"
                                        required
                                        placeholder="Nama Lengkap"
                                        class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                    />
                                    <p v-if="editUserForm.errors.name" class="text-rose-500 text-[11px] mt-1">{{ editUserForm.errors.name }}</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="edit_user_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Email <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            id="edit_user_email"
                                            v-model="editUserForm.email"
                                            type="email"
                                            required
                                            placeholder="email@contoh.com"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        />
                                        <p v-if="editUserForm.errors.email" class="text-rose-500 text-[11px] mt-1">{{ editUserForm.errors.email }}</p>
                                    </div>

                                    <div>
                                        <label for="edit_user_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            No. WhatsApp / HP
                                        </label>
                                        <input
                                            id="edit_user_phone"
                                            v-model="editUserForm.phone"
                                            type="text"
                                            placeholder="08xxxxxxxxxx"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="edit_user_role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Role Akses Sistem <span class="text-rose-500">*</span>
                                        </label>
                                        <select
                                            id="edit_user_role"
                                            v-model="editUserForm.role"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        >
                                            <option value="reseller">Reseller (Mitra)</option>
                                            <option value="customer">Customer (Pelanggan)</option>
                                            <option value="admin">Admin</option>
                                            <option value="teknisi">Teknisi</option>
                                            <option value="cs">Customer Service (CS)</option>
                                            <option value="finance">Finance</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="edit_user_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Ganti Password (Opsional)
                                        </label>
                                        <input
                                            id="edit_user_password"
                                            v-model="editUserForm.password"
                                            type="password"
                                            placeholder="Kosongkan jika tidak diubah"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 pt-1">
                                    <label class="inline-flex items-center gap-2 cursor-pointer select-none text-xs font-bold text-slate-800">
                                        <input
                                            type="checkbox"
                                            v-model="editUserForm.is_active"
                                            class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 border-slate-300"
                                        />
                                        <span>Status Akun Aktif (Dapat Login)</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Bagian 2: Data Kemitraan Reseller / Biller -->
                            <div class="space-y-3 pt-3 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">
                                        Konfigurasi Kemitraan Reseller
                                    </h4>
                                    <span v-if="editUserForm.has_reseller" class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                        Mitra Terdaftar
                                    </span>
                                    <span v-else class="text-[10px] font-bold text-slate-400">
                                        Belum Ada Kemitraan
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="edit_reseller_tier" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Tipe Kemitraan
                                        </label>
                                        <select
                                            id="edit_reseller_tier"
                                            v-model="editUserForm.tier"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        >
                                            <option value="regular">🏪 Mitra Reseller (Voucher &amp; Eceran)</option>
                                            <option value="master">👑 Master Biller (Loket Resmi)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="edit_reseller_status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Status Kemitraan
                                        </label>
                                        <select
                                            id="edit_reseller_status"
                                            v-model="editUserForm.reseller_status"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                        >
                                            <option value="active">Active (Aktif)</option>
                                            <option value="pending">Pending (Menunggu)</option>
                                            <option value="suspended">Suspended (Ditangguhkan)</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label for="edit_reseller_balance" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Saldo Deposit Modal (Rp)
                                    </label>
                                    <input
                                        id="edit_reseller_balance"
                                        v-model.number="editUserForm.balance"
                                        type="number"
                                        min="0"
                                        step="1000"
                                        class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                    />
                                    <p class="text-[11px] text-slate-500 mt-1">
                                        Format nominal: <strong class="text-emerald-700">Rp {{ formatRupiah(editUserForm.balance) }}</strong>. Setiap perubahan saldo akan dicatat otomatis di mutasi saldo kemitraan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editUserForm.processing"
                                class="px-5 py-2 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs rounded-xl shadow-md shadow-amber-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="editUserForm.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ editUserForm.processing ? 'Menyimpan...' : '✓ Simpan Perubahan' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- MODAL 6: BUAT AKUN MITRA RESELLER BARU                                    -->
            <!-- ========================================================================= -->
            <div v-if="showCreateResellerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden flex flex-col max-h-[92vh] border border-slate-100">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50/80">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 bg-emerald-50 text-emerald-600 rounded-xl text-lg">🏪</span>
                            <div>
                                <h2 class="text-base font-black text-slate-900">Buat Akun Reseller Baru</h2>
                                <p class="text-[11px] text-slate-500">Daftarkan akun login mitra loket pembayaran / reseller voucher secara instan</p>
                            </div>
                        </div>
                        <button @click="showCreateResellerModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <form @submit.prevent="submitCreateReseller" class="flex flex-col flex-1 overflow-hidden">
                        <!-- Body -->
                        <div class="p-6 overflow-y-auto flex-1 space-y-4">
                            <!-- Pilihan Tipe Kemitraan -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    Pilih Tipe Kemitraan <span class="text-rose-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label 
                                        class="p-3.5 border-2 rounded-2xl cursor-pointer transition-all flex flex-col items-center text-center select-none"
                                        :class="createResellerForm.tier === 'regular' ? 'border-emerald-500 bg-emerald-50/40 shadow-xs' : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <input type="radio" v-model="createResellerForm.tier" value="regular" class="sr-only" />
                                        <span class="text-2xl mb-1">🏪</span>
                                        <span class="text-xs font-black text-slate-900">Mitra Reseller</span>
                                        <span class="text-[10px] text-slate-500 mt-0.5">Penjualan Voucher Hotspot &amp; Eceran</span>
                                    </label>

                                    <label 
                                        class="p-3.5 border-2 rounded-2xl cursor-pointer transition-all flex flex-col items-center text-center select-none"
                                        :class="createResellerForm.tier === 'master' ? 'border-purple-500 bg-purple-50/40 shadow-xs' : 'border-slate-200 hover:border-slate-300'"
                                    >
                                        <input type="radio" v-model="createResellerForm.tier" value="master" class="sr-only" />
                                        <span class="text-2xl mb-1">👑</span>
                                        <span class="text-xs font-black text-slate-900">Master Biller</span>
                                        <span class="text-[10px] text-slate-500 mt-0.5">Loket Resmi Pembayaran &amp; Downline</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Informasi Akun -->
                            <div class="space-y-3 pt-2 border-t border-slate-100">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-500">
                                    Data Login &amp; Kontak Mitra
                                </h4>

                                <div>
                                    <label for="create_reseller_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        Nama Lengkap / Nama Konter <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="create_reseller_name"
                                        v-model="createResellerForm.name"
                                        type="text"
                                        required
                                        placeholder="Contoh: Konter Berkah Cell / Budi Santoso"
                                        class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-medium"
                                    />
                                    <p v-if="createResellerForm.errors.name" class="text-rose-500 text-[11px] mt-1">{{ createResellerForm.errors.name }}</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label for="create_reseller_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Email Login <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            id="create_reseller_email"
                                            v-model="createResellerForm.email"
                                            type="email"
                                            required
                                            placeholder="reseller@contoh.com"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-medium"
                                        />
                                        <p v-if="createResellerForm.errors.email" class="text-rose-500 text-[11px] mt-1">{{ createResellerForm.errors.email }}</p>
                                    </div>

                                    <div>
                                        <label for="create_reseller_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            No. WhatsApp / HP
                                        </label>
                                        <input
                                            id="create_reseller_phone"
                                            v-model="createResellerForm.phone"
                                            type="text"
                                            placeholder="08xxxxxxxxxx"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-medium"
                                        />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label for="create_reseller_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                                Password Login <span class="text-rose-500">*</span>
                                            </label>
                                            <button
                                                type="button"
                                                @click="generateRandomPassword"
                                                class="text-[10px] text-emerald-600 hover:text-emerald-800 font-bold hover:underline cursor-pointer"
                                            >
                                                🎲 Acak
                                            </button>
                                        </div>
                                        <input
                                            id="create_reseller_password"
                                            v-model="createResellerForm.password"
                                            type="text"
                                            required
                                            placeholder="Minimal 6 karakter"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-mono font-medium"
                                        />
                                        <p v-if="createResellerForm.errors.password" class="text-rose-500 text-[11px] mt-1">{{ createResellerForm.errors.password }}</p>
                                    </div>

                                    <div>
                                        <label for="create_reseller_area" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                            Wilayah / Area Usaha
                                        </label>
                                        <select
                                            id="create_reseller_area"
                                            v-model="createResellerForm.area_id"
                                            class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                        >
                                            <option value="">Semua Wilayah (Pusat)</option>
                                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                                {{ area.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Saldo Modal Awal -->
                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label for="create_reseller_balance" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Saldo Modal Awal (Rp)
                                    </label>
                                    <span class="text-[11px] text-slate-400">
                                        Min rekomendasi: {{ createResellerForm.tier === 'master' ? 'Rp 2.000.000' : 'Rp 20.000' }}
                                    </span>
                                </div>
                                <input
                                    id="create_reseller_balance"
                                    v-model.number="createResellerForm.balance"
                                    type="number"
                                    min="0"
                                    step="1000"
                                    placeholder="0"
                                    class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                />
                                <p class="text-[11px] text-slate-500">
                                    Nominal terbaca: <strong class="text-emerald-700">Rp {{ formatRupiah(createResellerForm.balance) }}</strong>. Saldo ini langsung tersedia di dompet reseller.
                                </p>
                            </div>

                            <!-- Hak Akses Profil Voucher -->
                            <div class="space-y-2 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Hak Akses Paket Voucher
                                    </label>
                                    <button
                                        type="button"
                                        @click="toggleLimitVouchers"
                                        class="text-[11px] text-indigo-600 font-bold hover:underline cursor-pointer"
                                    >
                                        {{ limitVouchers ? '← Izinkan Semua Paket' : '🔒 Batasi Paket Tertentu' }}
                                    </button>
                                </div>

                                <div v-if="!limitVouchers" class="p-3 bg-sky-50 text-sky-800 rounded-xl text-xs border border-sky-200 flex items-center gap-2">
                                    <span>🌐</span>
                                    <span><strong>Bebas Semua Profil:</strong> Mitra dapat menjual seluruh paket voucher hotspot aktif tanpa pembatasan.</span>
                                </div>

                                <div v-else class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                                        <span>Centang paket yang boleh dijual:</span>
                                        <div class="space-x-2">
                                            <button type="button" @click="createResellerForm.voucher_profile_ids = sellableProfiles.map(p => p.id)" class="text-indigo-600 font-bold hover:underline cursor-pointer">Pilih Semua</button>
                                            <span>•</span>
                                            <button type="button" @click="createResellerForm.voucher_profile_ids = []" class="text-rose-600 font-bold hover:underline cursor-pointer">Hapus Semua</button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 bg-slate-50 rounded-xl border border-slate-200">
                                        <label 
                                            v-for="profile in sellableProfiles" 
                                            :key="profile.id"
                                            class="flex items-center gap-2 p-2 rounded-lg bg-white border border-slate-200 cursor-pointer text-xs select-none hover:bg-slate-50"
                                        >
                                            <input 
                                                type="checkbox" 
                                                :value="profile.id" 
                                                v-model="createResellerForm.voucher_profile_ids"
                                                class="rounded text-emerald-600 focus:ring-emerald-500 border-slate-300"
                                            />
                                            <div class="truncate">
                                                <div class="font-bold text-slate-800 truncate">{{ profile.name }}</div>
                                                <div class="text-[10px] text-emerald-600 font-mono">Rp {{ formatRupiah(profile.price) }}</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/80 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="showCreateResellerModal = false"
                                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="createResellerForm.processing"
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-500/20 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="createResellerForm.processing" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ createResellerForm.processing ? 'Memproses...' : '✓ Buat &amp; Aktifkan Akun' }}</span>
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
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const props = defineProps({
    users: Object,
    voucherProfiles: Array,
    pendingResellers: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({
            reseller_registration_enabled: true,
            biller_registration_enabled: true,
            reseller_min_deposit: 20000,
            biller_min_deposit: 2000000,
            reseller_registration_require_approval: true,
            reseller_registration_terms: '',
        }),
    },
    areas: {
        type: Array,
        default: () => [],
    },
    stats: Object,
    filters: Object,
});

const defaultTermsText = `1. Calon mitra wajib mengisi data identitas diri dan lokasi konter/toko/loket usaha secara benar dan valid.
2. Pendaftaran Mitra Reseller (Voucher & Eceran) memiliki ketentuan minimal pengisian saldo modal awal Rp 20.000.
3. Pendaftaran Master Biller (Loket Pembayaran) memiliki ketentuan minimal pengisian saldo modal awal Rp 2.000.000.
4. Setiap pendaftaran mandiri melalui sistem wajib melalui proses verifikasi dan persetujuan (approval) resmi dari Administrator.
5. Akun kemitraan bebas dari iuran bulanan dan saldo modal dapat langsung digunakan melayani transaksi setelah akun disetujui.
6. Mitra wajib menjaga kerahasiaan kredensial akun dan mematuhi etika operasional serta tarif resmi yang berlaku.`;

// Tab State: 'settings', 'pending', 'vouchers'
const activeTab = ref(props.filters?.tab || (props.pendingResellers?.length > 0 ? 'pending' : 'settings'));

// Form Pengaturan Pendaftaran Mandiri
const settingsForm = useForm({
    reseller_registration_enabled: props.settings?.reseller_registration_enabled ?? true,
    biller_registration_enabled: props.settings?.biller_registration_enabled ?? true,
    reseller_min_deposit: props.settings?.reseller_min_deposit ?? 20000,
    biller_min_deposit: props.settings?.biller_min_deposit ?? 2000000,
    reseller_registration_require_approval: props.settings?.reseller_registration_require_approval ?? true,
    reseller_registration_terms: props.settings?.reseller_registration_terms || defaultTermsText,
});

const setRegistrationPreset = (mode) => {
    if (mode === 'all') {
        settingsForm.reseller_registration_enabled = true;
        settingsForm.biller_registration_enabled = true;
    } else if (mode === 'reseller_only') {
        settingsForm.reseller_registration_enabled = true;
        settingsForm.biller_registration_enabled = false;
    } else if (mode === 'biller_only') {
        settingsForm.reseller_registration_enabled = false;
        settingsForm.biller_registration_enabled = true;
    } else if (mode === 'none') {
        settingsForm.reseller_registration_enabled = false;
        settingsForm.biller_registration_enabled = false;
    }
};

const resetTermsToDefault = () => {
    settingsForm.reseller_registration_terms = defaultTermsText;
};

const saveRegistrationSettings = () => {
    settingsForm.post(route('settings.resellers.save-registration-settings'), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Pengaturan Disimpan!',
                text: 'Ketentuan pendaftaran mandiri Reseller & Biller berhasil diperbarui.',
                timer: 3500,
                showConfirmButton: false,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {})[0] || 'Terjadi kesalahan saat menyimpan pengaturan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: msg,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

// Modal Approve & Reject State
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedPending = ref(null);

const approveForm = useForm({
    initial_balance: 0,
    notes: '',
});

const rejectForm = useForm({
    reason: '',
});

const openApproveModal = (partner) => {
    selectedPending.value = partner;
    approveForm.initial_balance = 0;
    approveForm.notes = '';
    showApproveModal.value = true;
};

const submitApprove = () => {
    if (!selectedPending.value) return;

    approveForm.post(route('settings.resellers.approve', selectedPending.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showApproveModal.value = false;
            const approved = selectedPending.value;
            Swal.fire({
                icon: 'success',
                title: 'Pendaftaran Berhasil Disetujui!',
                html: `Akun mitra <b>${approved.name}</b> (${approved.tier_label}) telah aktif dan dapat langsung login.<br><br>` +
                    (approved.clean_phone ? `<a href="https://wa.me/${approved.clean_phone}?text=Halo%20Kak%20${encodeURIComponent(approved.name)},%20selamat!%20Pendaftaran%20kemitraan%20Anda%20telah%20DISETUJUI%20dan%20diaktifkan%20oleh%20Admin.%20Silakan%20login%20ke%20portal%20mitra%20kami.%20Terima%20kasih!" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs"><span>💬 Hubungi Mitra via WA</span></a>` : ''),
                confirmButtonColor: '#059669',
                confirmButtonText: 'Tutup'
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {})[0] || 'Gagal menyetujui pendaftaran.';
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        }
    });
};

const openRejectModal = (partner) => {
    selectedPending.value = partner;
    rejectForm.reason = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!selectedPending.value) return;

    rejectForm.post(route('settings.resellers.reject', selectedPending.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Pendaftaran Ditolak',
                text: 'Permohonan mitra telah ditolak dan dibersihkan dari antrean.',
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const msg = Object.values(err || {})[0] || 'Gagal menolak pendaftaran.';
            Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
        }
    });
};

// =========================================================================
// Hak Akses Voucher Hotspot
// =========================================================================
const sellableProfiles = computed(() => {
    return (props.voucherProfiles || []).filter(p => !p.is_customer_free);
});

const currentRole = ref(props.filters?.role || 'reseller');
const search = ref(props.filters?.search || '');
const showModal = ref(false);
const selectedUser = ref(null);

const form = useForm({
    voucher_profile_ids: []
});

const onSearch = debounce(() => {
    applyFilters();
}, 300);

const clearSearch = () => {
    search.value = '';
    applyFilters();
};

const setRole = (role) => {
    currentRole.value = role;
    applyFilters();
};

const applyFilters = () => {
    router.get(
        route('settings.resellers.index'), 
        { search: search.value, role: currentRole.value, tab: activeTab.value }, 
        { preserveState: true, replace: true }
    );
};

const openSettingModal = (user) => {
    selectedUser.value = user;
    form.voucher_profile_ids = user.voucher_profiles ? user.voucher_profiles.map(p => p.id) : [];
    showModal.value = true;
};

const selectAllProfiles = () => {
    form.voucher_profile_ids = sellableProfiles.value.map(p => p.id);
};

const clearAllProfiles = () => {
    form.voucher_profile_ids = [];
};

const saveSettings = () => {
    if (!selectedUser.value) return;
    
    form.post(route('settings.resellers.update.post', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil Disimpan!',
                text: `Akses profil voucher untuk ${selectedUser.value.name} telah diperbarui.`,
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Gagal menyimpan pengaturan.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstErr,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

const formatRupiah = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const getUserInitial = (name) => {
    return (name || 'U').charAt(0).toUpperCase();
};

const getUserAvatarClass = (role) => {
    const r = (role || '').toLowerCase();
    if (r === 'reseller') return 'bg-amber-100 text-amber-800 border border-amber-300';
    if (r === 'admin' || r === 'superadmin') return 'bg-purple-100 text-purple-800 border border-purple-300';
    if (r === 'teknisi') return 'bg-cyan-100 text-cyan-800 border border-cyan-300';
    if (r === 'cs') return 'bg-blue-100 text-blue-800 border border-blue-300';
    return 'bg-slate-100 text-slate-700 border border-slate-300';
};

const getRoleBadgeClass = (role) => {
    const r = (role || '').toLowerCase();
    if (r === 'reseller') return 'bg-amber-50 text-amber-800 border-amber-300';
    if (r === 'admin' || r === 'superadmin') return 'bg-purple-50 text-purple-800 border-purple-300';
    if (r === 'teknisi') return 'bg-cyan-50 text-cyan-800 border-cyan-300';
    if (r === 'cs') return 'bg-blue-50 text-blue-800 border-blue-300';
    return 'bg-slate-50 text-slate-700 border-slate-300';
};

// =========================================================================
// Aksi Tambahan: View, Edit, Hapus User / Reseller
// =========================================================================
const showViewModal = ref(false);
const selectedUserForView = ref(null);
const showEditModal = ref(false);
const selectedUserForEdit = ref(null);

const editUserForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: 'reseller',
    is_active: true,
    has_reseller: false,
    tier: 'regular',
    balance: 0,
    reseller_status: 'active',
});

const openViewModal = (user) => {
    selectedUserForView.value = user;
    showViewModal.value = true;
};

const openEditModal = (user) => {
    selectedUserForEdit.value = user;
    const r = user.reseller || (user.customer?.reseller);
    editUserForm.name = user.name || '';
    editUserForm.email = user.email || '';
    editUserForm.phone = user.phone || '';
    editUserForm.password = '';
    editUserForm.role = user.role || 'reseller';
    editUserForm.is_active = Boolean(user.is_active ?? true);
    editUserForm.has_reseller = Boolean(r);
    editUserForm.tier = r?.tier || 'regular';
    editUserForm.balance = r ? Number(r.balance) : 0;
    editUserForm.reseller_status = r?.status || 'active';
    editUserForm.clearErrors();
    showEditModal.value = true;
};

const submitEditUser = () => {
    if (!selectedUserForEdit.value) return;

    editUserForm.post(route('settings.resellers.user.update', selectedUserForEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Data Diperbarui!',
                text: `Data pengguna ${editUserForm.name} berhasil disimpan.`,
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Gagal menyimpan perubahan pengguna.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstErr,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};

const confirmDeleteUser = (user) => {
    if (user.id === 1 || user.email === 'admin@isp.local') {
        Swal.fire({
            icon: 'warning',
            title: 'Akun Terproteksi',
            text: 'Akun Super Administrator utama sistem dilindungi dan tidak boleh dihapus.',
            confirmButtonColor: '#4f46e5'
        });
        return;
    }

    Swal.fire({
        title: 'Hapus Akun & Kemitraan?',
        html: `Apakah Anda yakin ingin menghapus akun <b>${user.name}</b> (${user.email})?<br><br>` +
              `<div class="text-xs text-left p-3 bg-rose-50 text-rose-800 rounded-xl border border-rose-200 leading-relaxed">` +
              `⚠️ <b>Perhatian:</b> Hak akses login, profil voucher, dan seluruh data kemitraan reseller terkait akan dibersihkan secara aman dari database.` +
              `</div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus Sekarang',
        cancelButtonText: 'Batal',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('settings.resellers.user.destroy', user.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Akun Dihapus',
                        text: `Akun ${user.name} berhasil dihapus dari sistem.`,
                        timer: 3000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });
                },
                onError: (errors) => {
                    const msg = Object.values(errors || {})[0] || 'Gagal menghapus pengguna.';
                    Swal.fire({ icon: 'error', title: 'Gagal Menghapus', text: msg });
                }
            });
        }
    });
};

const getWhatsAppLink = (phone, name) => {
    if (!phone) return '#';
    let clean = String(phone).replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) clean = '62' + clean.slice(1);
    else if (clean.startsWith('8')) clean = '62' + clean;
    return `https://wa.me/${clean}?text=Halo%20Kak%20${encodeURIComponent(name || '')},%20terkait%20akun%20kemitraan%20Anda%20di%20sistem%20kami...`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    try {
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return dateStr;
    }
};

// =========================================================================
// Buat Akun Mitra Reseller Baru Langsung oleh Admin
// =========================================================================
const showCreateResellerModal = ref(false);
const limitVouchers = ref(false);

const createResellerForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    tier: 'regular',
    balance: 20000,
    area_id: '',
    voucher_profile_ids: [],
});

const generateRandomPassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    let res = '';
    for (let i = 0; i < 8; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    createResellerForm.password = res;
};

const openCreateResellerModal = () => {
    createResellerForm.name = '';
    createResellerForm.email = '';
    createResellerForm.phone = '';
    createResellerForm.password = 'reseller123';
    createResellerForm.tier = 'regular';
    createResellerForm.balance = props.settings?.reseller_min_deposit || 20000;
    createResellerForm.area_id = '';
    createResellerForm.voucher_profile_ids = [];
    createResellerForm.clearErrors();
    limitVouchers.value = false;
    showCreateResellerModal.value = true;
};

const toggleLimitVouchers = () => {
    limitVouchers.value = !limitVouchers.value;
    if (!limitVouchers.value) {
        createResellerForm.voucher_profile_ids = [];
    } else if (createResellerForm.voucher_profile_ids.length === 0) {
        createResellerForm.voucher_profile_ids = sellableProfiles.value.map(p => p.id);
    }
};

const submitCreateReseller = () => {
    createResellerForm.post(route('settings.resellers.user.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateResellerModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Akun Reseller Berhasil Dibuat!',
                html: `Akun mitra <b>${createResellerForm.name}</b> telah aktif.<br>` +
                      `Email: <b>${createResellerForm.email}</b><br>` +
                      `Password: <b>${createResellerForm.password}</b><br><br>` +
                      `<span class="text-xs text-slate-500">Mitra dapat langsung login ke portal kemitraan loket/reseller.</span>`,
                confirmButtonColor: '#059669',
                confirmButtonText: 'Selesai'
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Gagal membuat akun reseller.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Membuat Akun',
                text: firstErr,
                confirmButtonColor: '#4f46e5'
            });
        }
    });
};
</script>
