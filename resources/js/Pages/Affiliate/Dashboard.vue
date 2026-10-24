<template>
    <AffiliateLayout title="Dashboard Sales Afiliasi">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 font-sans">
            <!-- Header Banner -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl mb-8 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div v-if="is_employee" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-200 text-xs font-semibold mb-2">
                            <span>💼</span>
                            <span>Akun Karyawan Internal &bull; Fee Booking &amp; Pasif Masuk ke Insentif Slip Gaji</span>
                        </div>
                        <div v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-indigo-300 text-xs font-semibold mb-2">
                            <span>⭐</span>
                            <span>Akun Kemitraan Afiliasi Resmi</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            Halo, {{ profile.name }}!
                        </h1>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <div>
                                <span class="text-[11px] uppercase tracking-wider text-indigo-200 block">Nomor / Kode Afiliasi Saya</span>
                                <span class="text-xl sm:text-2xl font-mono font-extrabold tracking-wide">{{ profile.referral_code }}</span>
                            </div>
                            <button type="button" @click="copyToClipboard(profile.referral_code, 'Nomor afiliasi berhasil disalin.')" class="px-3 py-2 rounded-xl bg-white/15 border border-white/25 hover:bg-white/25 text-xs font-semibold">Salin Nomor</button>
                        </div>
                        <p class="text-slate-300 text-xs sm:text-sm mt-1 max-w-xl">
                            Selamat datang di portal kemitraan sales &amp; afiliasi. Pantau perolehan komisi booking, pasif income bulanan, dan perkembangan tim downline Anda.
                        </p>
                        <div v-if="profile.upline_name" class="mt-2 text-xs text-indigo-300 flex items-center gap-1">
                            <span>Sponsor Upline:</span>
                            <span class="font-bold text-white">{{ profile.upline_name }}</span>
                        </div>
                    </div>

                    <!-- Saldo & Tarik Dana Action -->
                    <div class="bg-white/10 backdrop-blur-md border border-white/15 rounded-2xl p-5 flex flex-col sm:flex-row items-center gap-5">
                        <div>
                            <span class="text-xs text-indigo-200 block">
                                {{ is_employee ? 'Estimasi Insentif Siap Dicairkan' : 'Saldo Komisi Siap Tarik' }}
                            </span>
                            <span class="text-2xl sm:text-3xl font-extrabold text-white">
                                {{ formatRupiah(is_employee ? (pending_payroll_incentives || 0) : profile.balance) }}
                            </span>
                            <span v-if="is_employee" class="text-[11px] text-purple-200 block mt-0.5">
                                Cair bulanan via Slip Gaji (Payroll)
                            </span>
                        </div>
                        <div v-if="is_employee">
                            <button
                                type="button"
                                @click="activeTab = 'commissions'"
                                class="w-full sm:w-auto px-5 py-2.5 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-lg transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <span>Histori Insentif Gaji</span>
                            </button>
                        </div>
                        <button
                            v-else
                            type="button"
                            @click="openWithdrawModal"
                            class="w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-1.5 whitespace-nowrap"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <span>Tarik Saldo</span>
                        </button>
                    </div>
                </div>
            </div>

            <p v-if="copyMessage" role="status" class="mb-5 px-4 py-3 text-sm text-indigo-800 bg-indigo-50 border border-indigo-100 rounded-xl">{{ copyMessage }}</p>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="p-5 rounded-2xl border border-indigo-100 bg-indigo-50">
                    <p class="text-xs font-semibold text-indigo-600">Booking Menunggu Proses</p>
                    <p class="text-2xl font-extrabold text-indigo-950 mt-2">{{ kpi.pending_bookings }}</p>
                </div>
                <div class="p-5 rounded-2xl border border-emerald-100 bg-emerald-50">
                    <p class="text-xs font-semibold text-emerald-700">Pelanggan Aktif Saya</p>
                    <p class="text-2xl font-extrabold text-emerald-950 mt-2">{{ kpi.active_customers }}</p>
                </div>
                <div class="p-5 rounded-2xl border border-slate-200 bg-white">
                    <p class="text-xs font-semibold text-slate-500">Akumulasi Komisi Booking</p>
                    <p class="text-lg font-extrabold text-slate-900 mt-2">{{ formatRupiah(kpi.booking_commissions) }}</p>
                </div>
                <div class="p-5 rounded-2xl border border-slate-200 bg-white">
                    <p class="text-xs font-semibold text-slate-500">Komisi Bulanan &amp; Downline</p>
                    <p class="text-lg font-extrabold text-slate-900 mt-2">{{ formatRupiah(kpi.recurring_commissions) }}</p>
                </div>
            </div>

            <!-- Notice & Alert: Jadwal Pencairan per Tanggal 15 & Pelanggan Belum Bayar (Khusus Mitra Luar) -->
            <div v-if="!is_employee" class="mb-8 space-y-4">
                <!-- Alert 1: Ada Pelanggan Belum Bayar (Fee Tidak Dapat Dicairkan) -->
                <div
                    v-if="payout_info?.has_unpaid_customers"
                    class="bg-rose-50/90 border-2 border-rose-300 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center text-xl shrink-0 mt-0.5">
                                ⚠️
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px] font-extrabold uppercase tracking-wider border border-rose-200 mb-1">
                                    <span>Pencairan Fee Terkunci</span>
                                    <span>&bull;</span>
                                    <span>Tagihan Belum Lunas</span>
                                </div>
                                <h3 class="text-sm sm:text-base font-black text-rose-950">
                                    Fee Komisi Belum Dapat Dicairkan ({{ payout_info.unpaid_customers_count }} Pelanggan Booking Belum Bayar)
                                </h3>
                                <p class="text-xs text-rose-800 mt-1 leading-relaxed">
                                    Khusus akun affiliate, komisi dicairkan per tanggal <b>{{ payout_info.payout_day }}</b>. Namun karena masih ada pelanggan hasil bookingan Anda yang belum melunasi tagihannya, <strong>tolong dibantu melakukan penagihan ke pelanggan di bawah ini</strong> agar fee komisi Anda dapat segera dicairkan.
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0 text-left sm:text-right bg-white/80 p-3 rounded-2xl border border-rose-200">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Tagihan Tertunggak</span>
                            <span class="text-base font-black text-rose-700 block">
                                {{ formatRupiah(payout_info.total_unpaid_amount) }}
                            </span>
                        </div>
                    </div>

                    <!-- List Pelanggan Yang Perlu Ditagih -->
                    <div class="overflow-x-auto rounded-2xl border border-rose-200/80 bg-white">
                        <table class="min-w-full divide-y divide-rose-100 text-xs">
                            <thead class="bg-rose-50/60 text-rose-900 font-bold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-2.5">Pelanggan Hasil Booking</th>
                                    <th class="px-4 py-2.5">Paket</th>
                                    <th class="px-4 py-2.5">Periode / Jatuh Tempo</th>
                                    <th class="px-4 py-2.5 text-right">Nominal Tagihan</th>
                                    <th class="px-4 py-2.5 text-center">Bantu Penagihan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-rose-50">
                                <tr v-for="c in payout_info.unpaid_customers" :key="c.id" class="hover:bg-rose-50/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-extrabold text-slate-900">{{ c.name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">{{ c.customer_code }} &bull; {{ c.phone }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ c.package_name }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-slate-800">{{ c.period_label }}</span>
                                        <span class="text-[10px] text-rose-600 block font-medium">Jatuh Tempo: {{ c.due_date }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-rose-600">
                                        {{ formatRupiah(c.total_unpaid_amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a
                                            v-if="c.wa_reminder_url"
                                            :href="c.wa_reminder_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors"
                                        >
                                            <span>💬 Bantu Tagih via WA</span>
                                        </a>
                                        <span v-else class="text-slate-400 text-xs">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Alert 2: Jadwal Tanggal Pencairan (Jika sebelum tanggal 15) -->
                <div
                    v-else-if="!payout_info?.is_payout_open"
                    class="bg-blue-50/80 border border-blue-200 rounded-2xl p-4 flex items-start gap-3 shadow-2xs"
                >
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold shrink-0">
                        📅
                    </div>
                    <div class="text-xs text-blue-900 leading-relaxed">
                        <strong class="font-bold text-blue-950">Jadwal Pencairan Fee Mitra Luar:</strong>
                        Khusus fee affiliate orang luar dicairkan per tanggal <b>{{ payout_info?.payout_day || 15 }}</b> setiap bulannya (Pencairan berikutnya: <b>{{ payout_info?.next_payout_date }}</b>). Seluruh pelanggan bookingan Anda saat ini telah lunas, penarikan saldo dapat diajukan saat memasuki tanggal {{ payout_info?.payout_day || 15 }}.
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Total Earned -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Pendapatan</span>
                        <span class="text-xl font-extrabold text-emerald-600 mt-0.5 block">
                            {{ formatRupiah(profile.total_earned) }}
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        💰
                    </div>
                </div>

                <!-- Total Pelanggan Aktif -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Total Booking Saya</span>
                        <span class="text-xl font-extrabold text-slate-800 mt-0.5 block">
                            {{ kpi.total_customers }} Pelanggan
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                        👥
                    </div>
                </div>

                <!-- Jaringan Downline -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">Jaringan Tim Mitra</span>
                        <span class="text-xl font-extrabold text-purple-700 mt-0.5 block">
                            {{ kpi.total_downlines }} Mitra
                        </span>
                        <span class="text-[10px] text-slate-400">L1: {{ kpi.downlines_l1_count }} &bull; L2: {{ kpi.downlines_l2_count }}</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                        🚀
                    </div>
                </div>

                <!-- Total Telah Dicairkan -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider block">{{ is_employee ? 'Insentif Menunggu Payroll' : 'Total Dicairkan' }}</span>
                        <span class="text-xl font-extrabold text-slate-800 mt-0.5 block">
                            {{ formatRupiah(is_employee ? pending_payroll_incentives : profile.total_withdrawn) }}
                        </span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl">
                        🏦
                    </div>
                </div>
            </div>

            <!-- Smart Link & Share Referral Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Box 1: Link Ajak Pelanggan Pasang Internet -->
                <div class="bg-gradient-to-br from-indigo-50 to-white p-6 rounded-3xl border border-indigo-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">🎯</span>
                        <h3 class="text-sm font-bold text-slate-900">Kode Rekomendasi Booking Pelanggan</h3>
                    </div>
                    <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                        Bagikan kode afiliasi Anda ke calon pelanggan, lalu daftarkan melalui menu Booking Baru. Komisi mengikuti ketentuan yang berlaku: <strong>Booking {{ formatRupiah(settings.affiliate_commission_booking) }}</strong> dan <strong>bulanan {{ formatRupiah(settings.affiliate_commission_monthly) }}/pelanggan</strong>.
                    </p>

                    <div class="flex items-center gap-2 mb-3">
                        <input
                            type="text"
                            readonly
                            :value="profile.referral_code"
                            class="px-3 py-2 bg-white border border-indigo-200 rounded-xl text-xs font-mono font-bold text-indigo-700 w-32 text-center"
                        />
                        <button
                            type="button"
                            @click="copyToClipboard(profile.referral_code, 'Kode Referral berhasil disalin!')"
                            class="px-3 py-2 bg-white hover:bg-slate-50 border border-indigo-200 text-indigo-700 text-xs font-semibold rounded-xl shadow-2xs transition-colors flex items-center gap-1"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Salin Kode
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            :href="generateWaCustomerShareUrl()"
                            target="_blank"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                        >
                            <span>Share WhatsApp</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- Box 2: Link Ajak Rekan Mitra Afiliasi -->
                <div class="bg-gradient-to-br from-purple-50 to-white p-6 rounded-3xl border border-purple-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-lg">🤝</span>
                        <h3 class="text-sm font-bold text-slate-900">Link Rekrut Tim / Downline Afiliasi</h3>
                    </div>
                    <p class="text-xs text-slate-600 mb-4 leading-relaxed">
                        Ajak teman atau rekan bergabung menjadi sesama mitra marketing. Anda otomatis menjadi Upline mereka dan menikmati <strong>Komisi Pasif {{ formatRupiah(settings.affiliate_commission_upline_1) }}/bulan (Tier 1)</strong> &amp; <strong>{{ formatRupiah(settings.affiliate_commission_upline_2) }}/bulan (Tier 2)</strong>!
                    </p>

                    <div class="flex items-center gap-2 mb-3">
                        <input
                            type="text"
                            readonly
                            :value="profile.referral_link_affiliate"
                            class="px-3 py-2 bg-white border border-purple-200 rounded-xl text-xs font-mono text-purple-700 flex-1 truncate"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="copyToClipboard(profile.referral_link_affiliate, 'Link Pendaftaran Mitra Afiliasi berhasil disalin!')"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Salin Link Ajak Rekan
                        </button>
                        <a
                            :href="generateWaAffiliateShareUrl()"
                            target="_blank"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                        >
                            <span>Share ke Grup WA</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Tab Navigation -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                <div class="p-4 border-b border-slate-100 flex items-center gap-2 overflow-x-auto">
                    <button
                        v-for="t in [
                            { id: 'customers', label: 'Pelanggan Saya (' + customers.length + ')', icon: '👥' },
                            { id: 'downlines', label: 'Jaringan Tim (' + kpi.total_downlines + ')', icon: '🚀' },
                            { id: 'commissions', label: 'Riwayat Komisi', icon: '💰' },
                            { id: 'withdrawals', label: 'Pencairan Dana (' + withdrawals.length + ')', icon: '🏦' },
                            { id: 'bank', label: 'Rekening Bank / E-Wallet', icon: '💳' },
                        ]"
                        :key="t.id"
                        @click="activeTab = t.id"
                        class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap flex items-center gap-1.5"
                        :class="activeTab === t.id ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    >
                        <span>{{ t.icon }}</span>
                        <span>{{ t.label }}</span>
                    </button>
                </div>

                <!-- Tab Content: Pelanggan Saya -->
                <div v-if="activeTab === 'customers'" class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Daftar Pelanggan Internet Binaan Anda</h4>
                            <p class="text-xs text-slate-500">Pelanggan yang Anda daftarkan langsung secara resmi.</p>
                        </div>
                        <a
                            href="/affiliate/booking/create"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>+ Booking Pelanggan Baru</span>
                        </a>
                    </div>

                    <div v-if="customers.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">ID Pelanggan</th>
                                    <th class="px-4 py-3">Nama Pelanggan</th>
                                    <th class="px-4 py-3">Paket Layanan</th>
                                    <th class="px-4 py-3">Tanggal Daftar</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Total Komisi Dihasilkan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in customers" :key="c.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-600">{{ c.customer_code }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ c.name }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ c.package_name }}</td>
                                    <td class="px-4 py-3 text-slate-500">{{ c.registration_date }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize" :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                            {{ c.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-emerald-600">
                                        {{ formatRupiah(c.total_commission) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-slate-400 space-y-3">
                        <span class="text-4xl block">📡</span>
                        <div>
                            <p class="font-medium text-slate-700">Belum Ada Pelanggan Terdaftar</p>
                            <p class="text-xs text-slate-400 mt-1">Bagikan link referral Anda atau daftarkan pelanggan langsung sekarang!</p>
                        </div>
                        <div>
                            <a
                                href="/affiliate/booking/create"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Input Booking Baru Sekarang</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Jaringan & Tim Downline -->
                <div v-if="activeTab === 'downlines'" class="p-6 space-y-6">
                    <div class="p-4 rounded-2xl border border-indigo-100 bg-indigo-50 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs text-indigo-600 font-semibold">Afiliasi Saya</p>
                            <p class="font-bold text-slate-900">{{ profile.name }}</p>
                            <p class="text-sm font-mono text-indigo-700">{{ profile.referral_code }}</p>
                        </div>
                        <button type="button" @click="copyToClipboard(profile.referral_code)" class="px-3 py-2 bg-white border border-indigo-200 rounded-xl text-xs font-semibold text-indigo-700">Salin Nomor</button>
                        <p class="w-full text-xs text-slate-600">Daftar ini hanya menampilkan afiliasi Anda serta downline tingkat 1 dan 2 yang terhubung dengan Anda.</p>
                    </div>
                    <!-- Visual Multi-Tier Diagram Banner -->
                    <div class="bg-gradient-to-br from-indigo-950 via-slate-900 to-purple-950 p-6 rounded-3xl text-white shadow-md relative overflow-hidden border border-indigo-500/20">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                            <div>
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>🌳</span>
                                    <span>Sistem Komisi Multi-Tier (Pasif Income 2 Level &bull; Downline Tanpa Batas)</span>
                                </h3>
                                <p class="text-xs text-indigo-200/80 mt-0.5">
                                    Setiap Sales/Upline bebas memiliki Downline sebanyak-banyaknya tanpa batas kuota (unlimited width). Komisi pasif mengalir otomatis dari 2 Level binaan:
                                </p>
                            </div>
                            <span class="inline-flex self-start sm:self-auto text-[11px] font-bold bg-white/10 px-3 py-1 rounded-full text-indigo-200 border border-white/10">
                                ♾️ Downline Tanpa Batas &bull; 2 Level
                            </span>
                        </div>

                        <!-- Visual Tier Flow Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <!-- Tier 0: Direct -->
                            <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-emerald-500/30">
                                <div class="inline-flex items-center gap-1 text-[10px] uppercase font-bold tracking-wider text-emerald-300 bg-emerald-500/20 px-2 py-0.5 rounded-full mb-2">
                                    🟢 Tingkat 0: Anda (Direct Sales)
                                </div>
                                <div class="text-xl font-extrabold text-white">
                                    {{ formatRupiah(settings.affiliate_commission_monthly) }}
                                    <span class="text-xs font-normal text-slate-300">/ cust / bln</span>
                                </div>
                                <p class="text-[11px] text-slate-300 mt-1.5 leading-relaxed">
                                    Diterima dari setiap pelanggan yang Anda daftarkan secara langsung.
                                </p>
                            </div>

                            <!-- Tier 1: Downline L1 -->
                            <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-indigo-500/30">
                                <div class="inline-flex items-center gap-1 text-[10px] uppercase font-bold tracking-wider text-indigo-300 bg-indigo-500/20 px-2 py-0.5 rounded-full mb-2">
                                    🔵 Tingkat 1: Downline L1
                                </div>
                                <div class="text-xl font-extrabold text-white">
                                    {{ formatRupiah(settings.affiliate_commission_upline_1) }}
                                    <span class="text-xs font-normal text-slate-300">/ cust / bln</span>
                                </div>
                                <p class="text-[11px] text-slate-300 mt-1.5 leading-relaxed">
                                    Pasif income dari pembayaran pelanggan milik mitra binaan langsung Anda.
                                </p>
                            </div>

                            <!-- Tier 2: Downline L2 -->
                            <div class="bg-white/5 backdrop-blur-md rounded-2xl p-4 border border-purple-500/30">
                                <div class="inline-flex items-center gap-1 text-[10px] uppercase font-bold tracking-wider text-purple-300 bg-purple-500/20 px-2 py-0.5 rounded-full mb-2">
                                    🟣 Tingkat 2: Downline L2
                                </div>
                                <div class="text-xl font-extrabold text-white">
                                    {{ formatRupiah(settings.affiliate_commission_upline_2) }}
                                    <span class="text-xs font-normal text-slate-300">/ cust / bln</span>
                                </div>
                                <p class="text-[11px] text-slate-300 mt-1.5 leading-relaxed">
                                    Pasif income dari pelanggan yang diajak oleh mitra di bawah mitra Anda (generasi 2).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tier 1 Downlines -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 flex items-center gap-1.5">
                                <span>⭐ Downline Level 1 (Diajak Langsung)</span>
                                <span class="px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-[10px]">{{ downlinesL1.length }} Mitra</span>
                            </h4>
                            <span class="text-xs text-slate-500">Komisi Upline: {{ formatRupiah(settings.affiliate_commission_upline_1) }} / pelanggan / bulan</span>
                        </div>

                        <div v-if="downlinesL1.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="px-4 py-2.5">Nama Mitra</th>
                                        <th class="px-4 py-2.5">Kode Referral</th>
                                        <th class="px-4 py-2.5">WhatsApp</th>
                                        <th class="px-4 py-2.5">Pelanggan Aktif</th>
                                        <th class="px-4 py-2.5">Bergabung</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="d in downlinesL1" :key="d.id" class="hover:bg-slate-50/60">
                                        <td class="px-4 py-2.5 font-bold text-slate-800">{{ d.name }}</td>
                                        <td class="px-4 py-2.5 font-mono text-purple-600 font-semibold">{{ d.referral_code }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ d.whatsapp || '-' }}</td>
                                        <td class="px-4 py-2.5 font-semibold text-indigo-600">{{ d.total_customers }} Pelanggan</td>
                                        <td class="px-4 py-2.5 text-slate-400">{{ d.joined_at }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="p-6 text-center text-xs text-slate-400 bg-slate-50/50 rounded-2xl border border-slate-100">
                            Belum ada mitra Level 1. Ajak rekan bergabung menggunakan link referral tim Anda.
                        </div>
                    </div>

                    <!-- Tier 2 Downlines -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-purple-700 flex items-center gap-1.5">
                                <span>🚀 Downline Level 2 (Binaan Mitra Anda)</span>
                                <span class="px-2 py-0.5 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-[10px]">{{ downlinesL2.length }} Mitra</span>
                            </h4>
                            <span class="text-xs text-slate-500">Komisi Upline: {{ formatRupiah(settings.affiliate_commission_upline_2) }} / pelanggan / bulan</span>
                        </div>

                        <div v-if="downlinesL2.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="px-4 py-2.5">Nama Mitra</th>
                                        <th class="px-4 py-2.5">Upline Level 1</th>
                                        <th class="px-4 py-2.5">Kode Referral</th>
                                        <th class="px-4 py-2.5">Pelanggan Aktif</th>
                                        <th class="px-4 py-2.5">Bergabung</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="d in downlinesL2" :key="d.id" class="hover:bg-slate-50/60">
                                        <td class="px-4 py-2.5 font-bold text-slate-800">{{ d.name }}</td>
                                        <td class="px-4 py-2.5 text-indigo-600 font-semibold">{{ d.upline_name }}</td>
                                        <td class="px-4 py-2.5 font-mono text-purple-600">{{ d.referral_code }}</td>
                                        <td class="px-4 py-2.5 font-semibold text-purple-600">{{ d.total_customers }} Pelanggan</td>
                                        <td class="px-4 py-2.5 text-slate-400">{{ d.joined_at }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="p-6 text-center text-xs text-slate-400 bg-slate-50/50 rounded-2xl border border-slate-100">
                            Belum ada mitra Level 2.
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Riwayat Komisi -->
                <div v-if="activeTab === 'commissions'" class="p-6">
                    <div v-if="commissions.data && commissions.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Jenis Komisi</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                    <th class="px-4 py-3 text-right">Nominal</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in commissions.data" :key="c.id" class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ c.date }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="{
                                                'bg-indigo-100 text-indigo-800': c.type === 'booking',
                                                'bg-emerald-100 text-emerald-800': c.type === 'monthly_direct',
                                                'bg-purple-100 text-purple-800': c.type === 'upline_tier_1',
                                                'bg-amber-100 text-amber-800': c.type === 'upline_tier_2',
                                                'bg-rose-100 text-rose-800 border border-rose-200': c.type === 'clawback_booking' || c.type === 'clawback_survey',
                                            }"
                                        >
                                            <template v-if="c.type === 'booking'">🎯 Booking Baru</template>
                                            <template v-else-if="c.type === 'monthly_direct'">💰 Pasif Bulanan</template>
                                            <template v-else-if="c.type === 'upline_tier_1'">🚀 Upline Tier 1</template>
                                            <template v-else-if="c.type === 'upline_tier_2'">🌟 Upline Tier 2</template>
                                            <template v-else-if="c.type === 'clawback_booking'">✂️ Potong Booking (&lt; 4 Bln)</template>
                                            <template v-else-if="c.type === 'clawback_survey'">✂️ Potong Survey (&lt; 4 Bln)</template>
                                            <template v-else>{{ c.type }}</template>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 font-medium">{{ c.description }}</td>
                                    <td class="px-4 py-3 text-right font-bold whitespace-nowrap" :class="c.amount < 0 || c.type === 'clawback_booking' || c.type === 'clawback_survey' ? 'text-rose-600' : 'text-emerald-600'">
                                        {{ (c.amount < 0 || c.type === 'clawback_booking' || c.type === 'clawback_survey') ? '-' : '+' }}{{ formatRupiah(Math.abs(c.amount)) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span 
                                            class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full border"
                                            :class="{
                                                'bg-rose-50 text-rose-700 border-rose-200': c.type === 'clawback_booking' || c.type === 'clawback_survey',
                                                'bg-purple-50 text-purple-700 border-purple-200': c.status === 'payroll' && c.type !== 'clawback_booking' && c.type !== 'clawback_survey',
                                                'bg-emerald-50 text-emerald-700 border-emerald-200': c.status === 'approved' && c.type !== 'clawback_booking' && c.type !== 'clawback_survey'
                                            }"
                                        >
                                            {{ (c.type === 'clawback_booking' || c.type === 'clawback_survey') ? (c.status === 'payroll' ? 'Potong Gaji' : 'Dipotong') : (c.status === 'payroll' ? 'Slip Gaji' : c.status) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-slate-400">
                        <span class="text-4xl block mb-2">💳</span>
                        <p class="font-medium text-slate-700">Belum Ada Transaksi Komisi</p>
                        <p class="text-xs text-slate-400 mt-1">Komisi otomatis masuk setiap kali pelanggan Anda mendaftar atau melunasi invoice.</p>
                    </div>
                    <div v-if="commissions.last_page > 1" class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
                        <span>{{ commissions.from }}–{{ commissions.to }} dari {{ commissions.total }} transaksi</span>
                        <div class="flex items-center gap-3">
                            <Link v-if="commissions.prev_page_url" :href="commissions.prev_page_url" preserve-scroll class="px-3 py-2 border border-slate-200 rounded-lg text-indigo-600">Sebelumnya</Link>
                            <span>Halaman {{ commissions.current_page }} / {{ commissions.last_page }}</span>
                            <Link v-if="commissions.next_page_url" :href="commissions.next_page_url" preserve-scroll class="px-3 py-2 border border-slate-200 rounded-lg text-indigo-600">Berikutnya</Link>
                        </div>
                    </div>
                </div>

                <!-- Tab Content: Pencairan Dana (Withdrawals) -->
                <div v-if="activeTab === 'withdrawals'" class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Riwayat Penarikan Dana</h4>
                            <p class="text-xs text-slate-500">Pencairan diproses langsung ke rekening bank / e-wallet Anda.</p>
                        </div>
                        <button
                            type="button"
                            @click="openWithdrawModal"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors"
                        >
                            + Ajukan Penarikan Baru
                        </button>
                    </div>

                    <div v-if="withdrawals.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">No. Referensi</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Rekening Tujuan</th>
                                    <th class="px-4 py-3">Nominal</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                    <th class="px-4 py-3">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="w in withdrawals" :key="w.id" class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-700">{{ w.reference_no }}</td>
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ w.date }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ w.bank_info }}</td>
                                    <td class="px-4 py-3 font-extrabold text-slate-900">{{ formatRupiah(w.amount) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                            :class="{
                                                'bg-amber-100 text-amber-800': w.status === 'pending',
                                                'bg-emerald-100 text-emerald-800': w.status === 'approved',
                                                'bg-rose-100 text-rose-800': w.status === 'rejected',
                                            }"
                                        >
                                            {{ w.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500">
                                        {{ w.notes || '-' }}
                                        <a v-if="w.receipt_proof" :href="w.receipt_proof" target="_blank" class="block text-indigo-600 hover:underline font-semibold mt-0.5">
                                            Lihat Bukti Transfer &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-12 text-slate-400">
                        <span class="text-4xl block mb-2">💸</span>
                        <p class="font-medium text-slate-700">Belum Ada Pengajuan Penarikan</p>
                        <p class="text-xs text-slate-400 mt-1">Saldo yang mencukupi minimal penarikan dapat dicairkan kapan saja.</p>
                    </div>
                </div>

                <!-- Tab Content: Rekening Bank / E-Wallet -->
                <div v-if="activeTab === 'bank'" class="p-6 max-w-lg">
                    <form @submit.prevent="submitBankUpdate" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Bank / E-Wallet
                            </label>
                            <select
                                v-model="bankForm.bank_name"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option v-for="b in bankOptions" :key="b" :value="b">{{ b }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Nomor Rekening / Nomor HP E-Wallet
                            </label>
                            <input
                                v-model="bankForm.bank_account_number"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Nama Pemilik Rekening
                            </label>
                            <input
                                v-model="bankForm.bank_account_holder"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Nomor WhatsApp Konfirmasi
                            </label>
                            <input
                                v-model="bankForm.whatsapp"
                                type="text"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="bankForm.processing"
                                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors"
                            >
                                {{ bankForm.processing ? 'Menyimpan...' : 'Simpan Rekening Pencairan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Modal Tarik Dana ── -->
        <div
            v-if="showWithdrawModal"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="showWithdrawModal = false"
        >
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-100 p-6 sm:p-7">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>💸</span>
                        <span>Tarik Saldo Komisi</span>
                    </h3>
                    <button type="button" @click="showWithdrawModal = false" class="text-slate-400 hover:text-slate-600">
                        ✕
                    </button>
                </div>

                <div class="p-3 bg-indigo-50/70 border border-indigo-100 rounded-2xl mb-4 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Saldo Tersedia:</span>
                        <span class="font-bold text-indigo-700">{{ formatRupiah(profile.balance) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Minimal Penarikan:</span>
                        <span class="font-semibold text-slate-700">{{ formatRupiah(settings.affiliate_min_withdraw) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tujuan Pencairan:</span>
                        <span class="font-semibold text-slate-800">{{ profile.bank_name }} - {{ profile.bank_account_number }}</span>
                    </div>
                </div>

                <!-- Alert jika ada pelanggan belum bayar -->
                <div v-if="payout_info?.has_unpaid_customers" class="p-3.5 bg-rose-50 border border-rose-200 rounded-2xl mb-4 text-xs text-rose-900 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-rose-800">
                        <span>⚠️</span>
                        <span>Pencairan Belum Dapat Dilakukan</span>
                    </div>
                    <p class="leading-relaxed">
                        Terdapat <b>{{ payout_info.unpaid_customers_count }} pelanggan</b> bookingan Anda yang belum membayar tagihan. Tolong dibantu melakukan penagihan ke pelanggan tersebut terlebih dahulu agar komisi Anda dapat dicairkan.
                    </p>
                </div>

                <!-- Alert jika belum tanggal 15 -->
                <div v-else-if="!payout_info?.is_payout_open" class="p-3.5 bg-blue-50 border border-blue-200 rounded-2xl mb-4 text-xs text-blue-900 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold text-blue-800">
                        <span>📅</span>
                        <span>Pencairan per Tanggal {{ payout_info?.payout_day || 15 }}</span>
                    </div>
                    <p class="leading-relaxed">
                        Khusus fee affiliate orang luar dicairkan per tanggal <b>{{ payout_info?.payout_day || 15 }}</b> setiap bulannya (Pencairan berikutnya: <b>{{ payout_info?.next_payout_date }}</b>).
                    </p>
                </div>

                <form @submit.prevent="submitWithdrawal" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Nominal Penarikan (Rp)
                        </label>
                        <input
                            v-model="withdrawForm.amount"
                            type="number"
                            :min="settings.affiliate_min_withdraw"
                            :max="profile.balance"
                            step="1000"
                            required
                            :disabled="payout_info?.has_unpaid_customers || !payout_info?.is_payout_open"
                            placeholder="Contoh: 100000"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm font-bold focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Catatan Tambahan (Opsional)
                        </label>
                        <textarea
                            v-model="withdrawForm.notes"
                            rows="2"
                            :disabled="payout_info?.has_unpaid_customers || !payout_info?.is_payout_open"
                            placeholder="Misal: Tarik komisi periode bulan ini"
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-xs focus:outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                        ></textarea>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showWithdrawModal = false"
                            class="px-4 py-2 border border-slate-300 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="withdrawForm.processing || profile.balance < settings.affiliate_min_withdraw || payout_info?.has_unpaid_customers || !payout_info?.is_payout_open"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-sm transition-colors disabled:opacity-50"
                        >
                            {{ withdrawForm.processing ? 'Memproses...' : 'Konfirmasi Penarikan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AffiliateLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AffiliateLayout from '@/Layouts/AffiliateLayout.vue';

const props = defineProps({
    profile: Object,
    kpi: Object,
    customers: Array,
    downlinesL1: Array,
    downlinesL2: Array,
    commissions: Object,
    withdrawals: Array,
    settings: Object,
    is_employee: Boolean,
    pending_payroll_incentives: Number,
    payout_info: Object,
    tab: String,
});

const activeTab = ref(props.tab || 'customers');
watch(() => props.tab, tab => { activeTab.value = tab || 'customers'; });
const copyMessage = ref('');
const showWithdrawModal = ref(false);

const bankOptions = [
    'BCA',
    'Bank Mandiri',
    'BRI',
    'BNI',
    'Bank Syariah Indonesia (BSI)',
    'Bank Jago',
    'SeaBank',
    'DANA',
    'GoPay',
    'OVO',
    'ShopeePay',
];

const withdrawForm = useForm({
    amount: '',
    notes: '',
});

const bankForm = useForm({
    bank_name: props.profile.bank_name || 'BCA',
    bank_account_number: props.profile.bank_account_number || '',
    bank_account_holder: props.profile.bank_account_holder || '',
    whatsapp: props.profile.whatsapp || '',
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const copyToClipboard = async (text, alertMsg) => {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
        } else {
            const input = document.createElement('textarea');
            input.value = text;
            input.style.position = 'fixed';
            input.style.opacity = '0';
            document.body.appendChild(input);
            try {
                input.select();
                if (!document.execCommand('copy')) throw new Error('copy');
            } finally {
                input.remove();
            }
        }
        copyMessage.value = alertMsg || 'Nomor afiliasi berhasil disalin.';
    } catch {
        copyMessage.value = 'Nomor belum berhasil disalin. Silakan pilih dan salin teks secara manual.';
    }
};

const generateWaCustomerShareUrl = () => {
    const text = encodeURIComponent(
        `Halo! Ingin pasang internet WiFi rumah? Hubungi saya untuk informasi paket dan booking pemasangan.\n\nKode afiliasi saya: ${props.profile.referral_code}\nSaya bantu proses booking sampai pemasangan.`
    );
    return `https://wa.me/?text=${text}`;
};

const generateWaAffiliateShareUrl = () => {
    const text = encodeURIComponent(
        `Halo rekan-rekan! Mau dapat penghasilan tambahan pasif income dari bisnis internet? 💰\n\nYuk gabung jadi Mitra Afiliasi & Sales Resmi ISP:\n${props.profile.referral_link_affiliate}\n\nDapatkan:\n- Komisi Booking Pasang Baru Rp ${new Intl.NumberFormat('id-ID').format(props.settings.affiliate_commission_booking)}\n- Pasif Income Bulanan Rp ${new Intl.NumberFormat('id-ID').format(props.settings.affiliate_commission_monthly)}/pelanggan\n- Komisi Jaringan Tim Downline Bertingkat!\n\nDaftar mandiri langsung aktif!`
    );
    return `https://wa.me/?text=${text}`;
};

const openWithdrawModal = () => {
    withdrawForm.amount = props.profile.balance >= props.settings.affiliate_min_withdraw ? props.profile.balance : '';
    withdrawForm.notes = '';
    showWithdrawModal.value = true;
};

const submitWithdrawal = () => {
    withdrawForm.post(route('affiliate.withdraw'), {
        preserveScroll: true,
        onSuccess: () => {
            showWithdrawModal.value = false;
            withdrawForm.reset();
        },
    });
};

const submitBankUpdate = () => {
    bankForm.post(route('affiliate.bank'), {
        preserveScroll: true,
    });
};
</script>
