<template>
    <AffiliateLayout :title="currentSection.label" :section="activeTab">
        <div class="sales-dashboard">
            <p v-if="copyMessage" role="status" class="sales-copy-message">{{ copyMessage }}</p>
            <AffiliateOverview v-if="activeTab === 'overview'" :profile="profile" :kpi="kpi" :settings="settings" :customers="customers"
                :is-employee="is_employee" :pending-payroll="pending_payroll_incentives" :customer-share-url="generateWaCustomerShareUrl()" :affiliate-share-url="generateWaAffiliateShareUrl()"
                @copy="copyToClipboard" @withdraw="openWithdrawModal" />
            <div v-else class="sales-detail-intro"><p>RUANG KERJA PRIBADI</p><h1>{{ currentSection.label }}</h1><span>{{ sectionDescriptions[activeTab] }}</span></div>

            <!-- Notice & Alert: Jadwal Pencairan per Tanggal 15 & Pelanggan Belum Bayar (Khusus Mitra Luar) -->
            <div v-if="!is_employee && ['overview', 'withdrawals'].includes(activeTab)" class="mb-8 space-y-4">
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

            <div v-if="activeTab !== 'overview'" class="sales-detail bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">

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
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize" :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                                {{ c.status }}
                                            </span>
                                            <span v-if="c.is_migrated" class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[9px] font-semibold border border-indigo-200">
                                                Migrasi
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="font-bold text-emerald-600">{{ formatRupiah(c.total_commission) }}</div>
                                        <div v-if="c.status === 'active'" class="text-[10px] text-slate-400 font-medium">
                                            +{{ formatRupiah(settings.affiliate_commission_monthly) }}/bln
                                        </div>
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
                                <div v-if="downlines_l1_summary?.total_active_customers" class="mt-2.5 pt-2 border-t border-white/10 text-xs font-semibold text-emerald-300 flex justify-between">
                                    <span>{{ downlines_l1_summary.total_active_customers }} Pelanggan Aktif:</span>
                                    <span>{{ formatRupiah(downlines_l1_summary.total_commission) }}/bln</span>
                                </div>
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
                                <div v-if="downlines_l2_summary?.total_active_customers" class="mt-2.5 pt-2 border-t border-white/10 text-xs font-semibold text-purple-300 flex justify-between">
                                    <span>{{ downlines_l2_summary.total_active_customers }} Pelanggan Aktif:</span>
                                    <span>{{ formatRupiah(downlines_l2_summary.total_commission) }}/bln</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Alert: Ketentuan Perhitungan Pelanggan Aktif Migrasi & Komisi -->
                    <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-2xl text-xs text-blue-900 flex items-start gap-3 shadow-xs">
                        <span class="text-lg leading-none shrink-0 mt-0.5">ℹ️</span>
                        <div class="leading-relaxed">
                            <strong class="font-bold text-blue-950">Perhitungan Pelanggan Aktif &amp; Komisi Pasif Downline:</strong>
                            <p class="mt-0.5 text-blue-800">
                                Sesuai ketentuan, pelanggan aktif hasil migrasi yang dihitung komisi pasif adalah pelanggan yang <strong>sudah aktivasi pada bulan Oktober 2026</strong>. Komisi upline mengalir otomatis sebesar <strong>Rp 2.000 / pelanggan / bulan</strong> untuk Downline Level 1 dan <strong>Rp 1.000 / pelanggan / bulan</strong> untuk Downline Level 2.
                            </p>
                        </div>
                    </div>

                    <!-- Tier 1 Downlines -->
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 flex items-center gap-1.5">
                                <span>⭐ Downline Level 1 (Diajak Langsung)</span>
                                <span class="px-2 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-[10px]">{{ downlinesL1.length }} Mitra</span>
                            </h4>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-slate-500 font-medium">Komisi: <strong class="text-indigo-700 font-bold">Rp 2.000</strong> / pelanggan / bulan</span>
                                <span v-if="downlines_l1_summary?.total_commission" class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-xs">
                                    Total: {{ formatRupiah(downlines_l1_summary.total_commission) }} / bln
                                </span>
                            </div>
                        </div>

                        <div v-if="downlinesL1.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="px-4 py-2.5">Nama Mitra</th>
                                        <th class="px-4 py-2.5">Kode Referral</th>
                                        <th class="px-4 py-2.5">WhatsApp</th>
                                        <th class="px-4 py-2.5">Pelanggan Aktif (Okt 2026)</th>
                                        <th class="px-4 py-2.5 text-center">Tarif Komisi</th>
                                        <th class="px-4 py-2.5 text-right">Total Komisi Upline</th>
                                        <th class="px-4 py-2.5">Bergabung</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="d in downlinesL1" :key="d.id" class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-4 py-2.5 font-bold text-slate-800">{{ d.name }}</td>
                                        <td class="px-4 py-2.5 font-mono text-purple-600 font-semibold">{{ d.referral_code }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ d.whatsapp || '-' }}</td>
                                        <td class="px-4 py-2.5 font-semibold text-indigo-600">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-indigo-50 border border-indigo-200 text-indigo-700 font-extrabold">
                                                {{ d.active_customers }} Pelanggan
                                            </span>
                                            <span v-if="(d.total_customers ?? 0) > (d.active_customers ?? 0)" class="text-[10px] text-slate-400 block mt-0.5">
                                                (dari {{ d.total_customers }} total)
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-bold text-[11px]">
                                                Rp 2.000 / plg
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-bold text-emerald-600">
                                            <div class="text-sm font-extrabold text-emerald-600">
                                                {{ formatRupiah(d.commission_monthly ?? (d.active_customers * (settings.affiliate_commission_upline_1 || 2000))) }}
                                            </div>
                                            <span class="text-[10px] font-normal text-slate-400 block">
                                                ({{ d.active_customers }} × Rp 2.000) / bln
                                            </span>
                                        </td>
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
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-purple-700 flex items-center gap-1.5">
                                <span>🚀 Downline Level 2 (Binaan Mitra Anda)</span>
                                <span class="px-2 py-0.5 rounded-full bg-purple-50 border border-purple-200 text-purple-700 text-[10px]">{{ downlinesL2.length }} Mitra</span>
                            </h4>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="text-slate-500 font-medium">Komisi: <strong class="text-purple-700 font-bold">Rp 1.000</strong> / pelanggan / bulan</span>
                                <span v-if="downlines_l2_summary?.total_commission" class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-xs">
                                    Total: {{ formatRupiah(downlines_l2_summary.total_commission) }} / bln
                                </span>
                            </div>
                        </div>

                        <div v-if="downlinesL2.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                    <tr>
                                        <th class="px-4 py-2.5">Nama Mitra</th>
                                        <th class="px-4 py-2.5">Upline Level 1</th>
                                        <th class="px-4 py-2.5">Kode Referral</th>
                                        <th class="px-4 py-2.5">Pelanggan Aktif (Okt 2026)</th>
                                        <th class="px-4 py-2.5 text-center">Tarif Komisi</th>
                                        <th class="px-4 py-2.5 text-right">Total Komisi Upline</th>
                                        <th class="px-4 py-2.5">Bergabung</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="d in downlinesL2" :key="d.id" class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-4 py-2.5 font-bold text-slate-800">{{ d.name }}</td>
                                        <td class="px-4 py-2.5 text-indigo-600 font-semibold">{{ d.upline_name }}</td>
                                        <td class="px-4 py-2.5 font-mono text-purple-600">{{ d.referral_code }}</td>
                                        <td class="px-4 py-2.5 font-semibold text-purple-600">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-purple-50 border border-purple-200 text-purple-700 font-extrabold">
                                                {{ d.active_customers }} Pelanggan
                                            </span>
                                            <span v-if="(d.total_customers ?? 0) > (d.active_customers ?? 0)" class="text-[10px] text-slate-400 block mt-0.5">
                                                (dari {{ d.total_customers }} total)
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 border border-purple-200 font-bold text-[11px]">
                                                Rp 1.000 / plg
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-bold text-emerald-600">
                                            <div class="text-sm font-extrabold text-emerald-600">
                                                {{ formatRupiah(d.commission_monthly ?? (d.active_customers * (settings.affiliate_commission_upline_2 || 1000))) }}
                                            </div>
                                            <span class="text-[10px] font-normal text-slate-400 block">
                                                ({{ d.active_customers }} × Rp 1.000) / bln
                                            </span>
                                        </td>
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
                        <button v-if="!is_employee"
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
                            <p class="text-xs text-slate-400 mt-1">{{ is_employee ? 'Insentif karyawan disalurkan melalui slip gaji.' : 'Pengajuan mengikuti jadwal pencairan dan ketentuan saldo minimum.' }}</p>
                    </div>
                </div>

                <!-- Tab Content: Rekening Bank / E-Wallet -->
                <div v-if="activeTab === 'bank'" class="p-6 max-w-lg">
                    <div v-if="Object.keys(bankForm.errors).length" role="alert" class="sales-form-errors"><p v-for="(error, key) in bankForm.errors" :key="key">{{ error }}</p></div>
                    <form @submit.prevent="submitBankUpdate" class="space-y-4">
                        <div>
                            <label for="affiliate-bank" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Bank / E-Wallet
                            </label>
                            <select
                                id="affiliate-bank"
                                v-model="bankForm.bank_name"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option v-for="b in bankOptions" :key="b" :value="b">{{ b }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="affiliate-account-number" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Nomor Rekening / Nomor HP E-Wallet
                            </label>
                            <input
                                id="affiliate-account-number"
                                v-model="bankForm.bank_account_number"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                            />
                        </div>

                        <div>
                            <label for="affiliate-account-holder" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Nama Pemilik Rekening
                            </label>
                            <input
                                id="affiliate-account-holder"
                                v-model="bankForm.bank_account_holder"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label for="affiliate-whatsapp" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                                Nomor WhatsApp Konfirmasi
                            </label>
                            <input
                                id="affiliate-whatsapp"
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
        <Dialog :open="showWithdrawModal" class="sales-withdraw-dialog affiliate-workspace" @close="closeWithdrawModal">
            <div class="sales-withdraw-backdrop" aria-hidden="true"></div>
            <div class="sales-withdraw-viewport">
            <DialogPanel class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-100 p-6 sm:p-7">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <DialogTitle as="h3" class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>Tarik Saldo Komisi</span>
                    </DialogTitle>
                    <button type="button" aria-label="Tutup pencairan" :disabled="withdrawForm.processing" @click="closeWithdrawModal" class="text-slate-400 hover:text-slate-600">
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

                <div v-if="Object.keys(withdrawForm.errors).length" role="alert" class="sales-form-errors"><p v-for="(error, key) in withdrawForm.errors" :key="key">{{ error }}</p></div>
                <form @submit.prevent="submitWithdrawal" class="space-y-4">
                    <div>
                        <label for="affiliate-withdraw-amount" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Nominal Penarikan (Rp)
                        </label>
                        <input
                            id="affiliate-withdraw-amount"
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
                        <label for="affiliate-withdraw-notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1">
                            Catatan Tambahan (Opsional)
                        </label>
                        <textarea
                            id="affiliate-withdraw-notes"
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
                            :disabled="withdrawForm.processing"
                            @click="closeWithdrawModal"
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
            </DialogPanel>
            </div>
        </Dialog>
    </AffiliateLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';
import AffiliateLayout from '@/Layouts/AffiliateLayout.vue';
import AffiliateOverview from '@/Components/AffiliateOverview.vue';
import { affiliateSections, affiliateSection } from '@/Composables/affiliateNavigation';

const props = defineProps({
    profile: Object,
    kpi: Object,
    customers: Array,
    downlinesL1: Array,
    downlinesL2: Array,
    downlines_l1_summary: Object,
    downlines_l2_summary: Object,
    commissions: Object,
    withdrawals: Array,
    settings: Object,
    is_employee: Boolean,
    pending_payroll_incentives: Number,
    payout_info: Object,
    tab: String,
});

const activeTab = computed(() => affiliateSection('/affiliate/dashboard', props.tab));
const currentSection = computed(() => affiliateSections.find(item => item.id === activeTab.value));
const sectionDescriptions = {
    customers: 'Daftar booking dan pelanggan yang Anda daftarkan langsung.',
    downlines: 'Nomor afiliasi Anda dan jaringan downline tingkat 1 dan 2.',
    commissions: 'Rincian komisi booking, bulanan, dan jaringan afiliasi Anda.',
    withdrawals: 'Pantau pengajuan dan status pencairan saldo komisi.',
    bank: 'Kelola rekening tujuan pencairan dan nomor konfirmasi Anda.',
};
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
    withdrawForm.clearErrors();
    withdrawForm.amount = props.profile.balance >= props.settings.affiliate_min_withdraw ? props.profile.balance : '';
    withdrawForm.notes = '';
    showWithdrawModal.value = true;
};

const closeWithdrawModal = () => {
    if (!withdrawForm.processing) showWithdrawModal.value = false;
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

<style scoped>
.sales-dashboard { min-width: 0; }
.sales-copy-message { margin-bottom: 20px; padding: 13px 16px; border: 1px solid #72b5a440; border-radius: 10px; background: #72b5a40b; color: var(--sales-accent); font-size: 12px; }
.sales-detail-intro { margin-bottom: 26px; }
.sales-detail-intro > p { color: var(--sales-muted); font-size: 9px; letter-spacing: .14em; font-weight: 600; }
.sales-detail-intro h1 { color: var(--sales-text); font-size: 25px; font-weight: 650; letter-spacing: -.035em; margin: 10px 0; }
.sales-detail-intro > span { color: var(--sales-muted); font-size: 12px; line-height: 1.8; }
.sales-detail { min-width: 0; }
.sales-detail table { min-width: 680px; }
.sales-detail th { font-size: 10px; font-weight: 600; letter-spacing: .035em; padding-top: 14px; padding-bottom: 14px; }
.sales-detail td { padding-top: 17px; padding-bottom: 17px; }
.sales-detail > div > .flex.items-center.justify-between { flex-wrap: wrap; gap: 16px; }
.sales-detail :is(a, button)[class*="bg-indigo-600"] { background: #316da8; color: #fff; }
.sales-form-errors { border: 1px solid #db8b9c55; color: #c25e75; background: #db8b9c0b; padding: 12px 15px; border-radius: 10px; font-size: 12px; line-height: 1.8; margin-bottom: 20px; }
html.dark .sales-form-errors { color: #eea9b9; }
.sales-withdraw-dialog { position: fixed; inset: 0; z-index: 70; background: transparent; min-height: 0; }
.sales-withdraw-backdrop { position: fixed; inset: 0; background: #030a16b3; backdrop-filter: blur(6px); }
.sales-withdraw-viewport { position: fixed; inset: 0; padding: 24px 16px; overflow-y: auto; display: flex; align-items: center; justify-content: center; }
.sales-withdraw-viewport > div { margin: auto; flex-shrink: 0; }
@media (max-width: 600px) {
    .sales-detail > .p-6 { padding: 20px 16px; }
    .sales-detail-intro h1 { font-size: 23px; }
}
</style>
