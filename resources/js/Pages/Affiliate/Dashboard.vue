<template>
    <AffiliateLayout :title="currentSection.label" :section="activeTab">
        <div class="sales-dashboard">
            <p v-if="copyMessage" role="status" class="sales-copy-message">{{ copyMessage }}</p>
            <AffiliateOverview v-if="activeTab === 'overview'" :profile="profile" :kpi="kpi" :settings="settings" :customers="customers"
                :is-employee="is_employee" :pending-payroll="pending_payroll_incentives" :payout-info="payout_info"
                :customer-share-url="generateWaCustomerShareUrl()" :affiliate-share-url="generateWaAffiliateShareUrl()"
                @copy="copyToClipboard" @withdraw="openWithdrawModal" />
            <div v-else class="sales-detail-intro">
                <p>RUANG KERJA PRIBADI</p>
                <h1>{{ currentSection.label }}</h1>
                <span>{{ sectionDescriptions[activeTab] }}</span>
            </div>

            <!-- Notice & Alert: Jadwal Pencairan per Tanggal 15 & Pelanggan Belum Bayar (Khusus Mitra Luar) -->
            <div v-if="!is_employee && ['overview', 'withdrawals'].includes(activeTab)" class="mb-8 space-y-4">
                <!-- Alert 1: Ada Pelanggan Menunggak Lewat Jatuh Tempo (Fee Tidak Dapat Dicairkan) -->
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
                                    <span>Ada Tagihan Menunggak</span>
                                </div>
                                <h3 class="text-sm sm:text-base font-black text-rose-950">
                                    Fee Komisi Belum Dapat Dicairkan ({{ payout_info.unpaid_customers_count }} Pelanggan Menunggak Lewat Jatuh Tempo)
                                </h3>
                                <p class="text-xs text-rose-800 mt-1 leading-relaxed">
                                    Khusus akun affiliate, komisi dicairkan per tanggal <b>{{ payout_info.payout_day }}</b>. Terdapat pelanggan hasil bookingan Anda yang <strong>telah melewati tanggal jatuh tempo pembayaran</strong>. Tolong dibantu melakukan penagihan ke pelanggan di bawah ini agar fee komisi Anda dapat segera dicairkan. <em>(Catatan: Pelanggan masa prorata yang belum jatuh tempo tidak dimasukkan ke dalam daftar tunggakan ini).</em>
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

                <!-- Info Ramah: Pelanggan Prorata Berjalan (Belum Waktunya Bayar) -->
                <div
                    v-if="payout_info?.prorata_customers_count > 0 && ['overview'].includes(activeTab)"
                    class="bg-gradient-to-r from-purple-50 via-indigo-50/60 to-white border border-purple-200/90 rounded-3xl p-4 sm:p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg font-bold shrink-0 mt-0.5">
                            ⚡
                        </div>
                        <div>
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 text-[10px] font-extrabold uppercase tracking-wider border border-purple-200 mb-1">
                                <span>{{ payout_info.prorata_customers_count }} Pelanggan Prorata</span>
                                <span>&bull;</span>
                                <span>Aturan Terbit Tgl {{ payout_info.invoice_issue_day || settings.invoice_issue_date || 1 }}</span>
                            </div>
                            <h4 class="text-xs sm:text-sm font-black text-purple-950">
                                Pelanggan Masa Prorata Berjalan (Belum Waktunya Bayar &amp; Jangan Dibantu Tagih)
                            </h4>
                            <p class="text-xs text-purple-900 mt-0.5 leading-relaxed">
                                Sesuai aturan yang dipilih di sistem (invoice terbit tanggal <strong>{{ payout_info.invoice_issue_day || settings.invoice_issue_date || 1 }}</strong>), tagihan prorata pascabayar baru akan diterbitkan pada tanggal tersebut dan jatuh tempo tanggal <strong>{{ payout_info.isolate_day || settings.isolate_day || 12 }}</strong>. Sebelum tanggal terbit dan jatuh tempo, tagihan <strong>belum waktunya dibayar</strong>, <strong>jangan dibantu tagih</strong>, dan <strong>tidak menahan pencairan komisi Anda</strong>.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="activeTab = 'customers'; customerSubTab = 'prorata'"
                        class="px-4 py-2 bg-white hover:bg-purple-100/70 border border-purple-200 text-purple-800 text-xs font-extrabold rounded-xl transition shadow-2xs whitespace-nowrap cursor-pointer self-start sm:self-auto"
                    >
                        Lihat Data Prorata &rarr;
                    </button>
                </div>

                <!-- Alert 2: Jadwal Tanggal Pencairan (Jika sebelum tanggal 15) -->
                <div
                    v-else-if="!payout_info?.is_payout_open && !payout_info?.has_unpaid_customers"
                    class="bg-blue-50/80 border border-blue-200 rounded-2xl p-4 flex items-start gap-3 shadow-2xs"
                >
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold shrink-0">
                        📅
                    </div>
                    <div class="text-xs text-blue-900 leading-relaxed">
                        <strong class="font-bold text-blue-950">Jadwal Pencairan Fee Mitra Luar:</strong>
                        Khusus fee affiliate orang luar dicairkan per tanggal <b>{{ payout_info?.payout_day || 15 }}</b> setiap bulannya (Pencairan berikutnya: <b>{{ payout_info?.next_payout_date }}</b>). Seluruh pelanggan bookingan Anda tidak ada yang menunggak lewat jatuh tempo, penarikan saldo dapat diajukan saat memasuki tanggal {{ payout_info?.payout_day || 15 }}.
                    </div>
                </div>
            </div>

            <div v-if="activeTab !== 'overview'" class="sales-detail bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- Tab Content: Pelanggan Saya (Dengan Sub-Tab Pemisah)      -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'customers'" class="p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
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

                    <!-- Sub-tabs Pemisah: Semua Pelanggan | Sudah Bayar | Belum Bayar | Prorata | Menunggak -->
                    <div class="flex items-center gap-2 mb-5 p-1.5 bg-slate-100 rounded-2xl w-fit flex-wrap">
                        <button
                            type="button"
                            @click="customerSubTab = 'all'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                            :class="customerSubTab === 'all' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>Semua Pelanggan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="customerSubTab === 'all' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-700'">
                                {{ (customers || []).length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="customerSubTab = 'paid'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                            :class="customerSubTab === 'paid' ? 'bg-white text-emerald-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>✓ Sudah Bayar (Lunas)</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="customerSubTab === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'">
                                {{ paidCustomers.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="customerSubTab = 'unpaid'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                            :class="customerSubTab === 'unpaid' ? 'bg-white text-amber-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>⏳ Pelanggan Belum Bayar</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="customerSubTab === 'unpaid' ? 'bg-amber-100 text-amber-800' : 'bg-amber-100/70 text-amber-700'">
                                {{ unpaidCustomersList.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="customerSubTab = 'prorata'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                            :class="customerSubTab === 'prorata' ? 'bg-white text-purple-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>⚡ Pelanggan Prorata</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="customerSubTab === 'prorata' ? 'bg-purple-100 text-purple-800' : 'bg-purple-100/70 text-purple-700'">
                                {{ prorataCustomers.length }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="customerSubTab = 'overdue'"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 whitespace-nowrap cursor-pointer"
                            :class="customerSubTab === 'overdue' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        >
                            <span>⚠️ Menunggak (Lewat Jatuh Tempo)</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="customerSubTab === 'overdue' ? 'bg-rose-100 text-rose-800' : 'bg-rose-100/70 text-rose-700'">
                                {{ overdueCustomers.length }}
                            </span>
                        </button>
                    </div>

                    <!-- Banner jika memilih Sub-tab Belum Bayar -->
                    <div v-if="customerSubTab === 'unpaid' && unpaidCustomersList.length > 0" class="p-4 bg-amber-50 border border-amber-200 rounded-2xl mb-4 text-xs text-amber-950 space-y-1.5 leading-relaxed">
                        <div class="flex items-center gap-1.5 font-bold text-amber-900">
                            <span>⏳</span>
                            <span>Daftar Pelanggan Belum Bayar ({{ unpaidCustomersList.length }} Pelanggan)</span>
                        </div>
                        <p>
                            Pelanggan di bawah ini memiliki tagihan aktif yang belum lunas. Anda dapat menekan tombol <strong>"💬 Bantu Tagih"</strong> untuk mengingatkan pelanggan secara ramah via WhatsApp agar melakukan pembayaran sebelum batas tanggal jatuh tempo (<strong>tanggal {{ payout_info?.isolate_day || settings.isolate_day || 12 }}</strong>).
                        </p>
                    </div>

                    <!-- Banner jika memilih Sub-tab Prorata -->
                    <div v-if="customerSubTab === 'prorata' && prorataCustomers.length > 0" class="p-4 bg-purple-50 border border-purple-200 rounded-2xl mb-4 text-xs text-purple-950 space-y-1.5 leading-relaxed">
                        <div class="flex items-center gap-1.5 font-bold text-purple-900">
                            <span>⚡</span>
                            <span>Aturan Sistem: Tagihan Prorata Pascabayar (Terbit Tgl {{ payout_info?.invoice_issue_day || settings.invoice_issue_date || 1 }}, Jatuh Tempo Tgl {{ payout_info?.isolate_day || settings.isolate_day || 12 }})</span>
                        </div>
                        <p>
                            Pelanggan di bawah ini adalah pelanggan baru pascabayar dalam masa perhitungan pemakaian prorata berjalan. Sesuai aturan sistem, tagihan perdana baru akan resmi terbit dan masa pembayaran pertama dimulai pada <strong>tanggal {{ payout_info?.invoice_issue_day || settings.invoice_issue_date || 1 }} bulan berikutnya</strong>, dengan batas jatuh tempo pada <strong>tanggal {{ payout_info?.isolate_day || settings.isolate_day || 12 }}</strong>. Sebelum tanggal terbit invoice (pembayaran pertama dimulai 1 Desember), <strong>tagihan BELUM TERBIT dan JANGAN DIBANTU TAGIH</strong>. Tagihan masa prorata ini sama sekali <strong>TIDAK MENGUNCI PENCAIRAN SALDO KOMISI</strong> Anda.
                        </p>
                    </div>

                    <!-- Peringatan jika memilih Sub-tab Menunggak -->
                    <div v-if="customerSubTab === 'overdue' && overdueCustomers.length > 0" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl mb-4 text-xs text-rose-900 space-y-1 leading-relaxed">
                        <div class="flex items-center gap-1.5 font-bold text-rose-900">
                            <span>🔒</span>
                            <span>Pencairan Dana Ditangguhkan Sementara (Ada Tagihan Lewat Jatuh Tempo)</span>
                        </div>
                        <p>
                            Pelanggan di bawah ini telah melewati batas jatuh tempo pembayaran dan belum melunasi tagihannya. Sesuai kebijakan SOP Kemitraan, pencairan saldo komisi terkunci hingga tagihan di bawah ini lunas.
                            Gunakan tombol <strong>"💬 Bantu Tagih"</strong> untuk mengingatkan pelanggan secara sopan agar fee komisi Anda dapat segera dicairkan.
                        </p>
                    </div>

                    <!-- State jika tidak ada pelanggan belum bayar -->
                    <div v-if="customerSubTab === 'unpaid' && unpaidCustomersList.length === 0" class="p-8 text-center bg-emerald-50/60 rounded-2xl border border-emerald-100 my-4 space-y-2">
                        <span class="text-3xl block">🎉</span>
                        <h4 class="text-sm font-bold text-emerald-950">Seluruh Pelanggan Telah Membayar Lunas!</h4>
                        <p class="text-xs text-emerald-800 max-w-md mx-auto">
                            Luar biasa! Tidak ada pelanggan yang memiliki tagihan belum lunas saat ini.
                        </p>
                    </div>

                    <!-- State jika tidak ada pelanggan menunggak lewat jatuh tempo -->
                    <div v-else-if="customerSubTab === 'overdue' && overdueCustomers.length === 0" class="p-8 text-center bg-emerald-50/60 rounded-2xl border border-emerald-100 my-4 space-y-2">
                        <span class="text-3xl block">🎉</span>
                        <h4 class="text-sm font-bold text-emerald-950">Seluruh Pelanggan Aman & Tidak Ada Menunggak!</h4>
                        <p class="text-xs text-emerald-800 max-w-md mx-auto">
                            Luar biasa! Tidak ada pelanggan binaan Anda yang menunggak lewat batas jatuh tempo saat ini. Seluruh hak pencairan komisi Anda terbuka tanpa halangan.
                        </p>
                    </div>

                    <!-- State jika tidak ada pelanggan prorata -->
                    <div v-else-if="customerSubTab === 'prorata' && prorataCustomers.length === 0" class="p-8 text-center bg-purple-50/60 rounded-2xl border border-purple-100 my-4 space-y-2">
                        <span class="text-3xl block">⚡</span>
                        <h4 class="text-sm font-bold text-purple-950">Tidak Ada Pelanggan Prorata Berjalan</h4>
                        <p class="text-xs text-purple-800 max-w-md mx-auto">
                            Saat ini belum ada pelanggan baru dalam masa perhitungan prorata berjalan.
                        </p>
                    </div>

                    <!-- State jika tidak ada pelanggan di subtab paid -->
                    <div v-else-if="customerSubTab === 'paid' && paidCustomers.length === 0" class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-100 my-4 space-y-2">
                        <span class="text-3xl block">⏳</span>
                        <h4 class="text-sm font-bold text-slate-800">Belum Ada Pelanggan Lunas</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            Pelanggan aktif yang telah melunasi invoice akan otomatis tercantum pada daftar ini.
                        </p>
                    </div>

                    <!-- Tabel Pelanggan Sesuai Filter Sub-tab -->
                    <div v-else-if="filteredCustomers.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">ID Pelanggan</th>
                                    <th class="px-4 py-3">Nama Pelanggan</th>
                                    <th class="px-4 py-3">Paket Layanan</th>
                                    <th class="px-4 py-3">Tanggal Daftar</th>
                                    <th class="px-4 py-3">Tanggal Aktivasi</th>
                                    <th class="px-4 py-3">Status Tagihan</th>
                                    <th class="px-4 py-3 text-right">
                                        {{ customerSubTab === 'unpaid' || customerSubTab === 'overdue' ? 'Tagihan Belum Bayar' : (customerSubTab === 'prorata' ? 'Estimasi Tagihan' : 'Total Komisi') }}
                                    </th>
                                    <th class="px-4 py-3 text-center">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in filteredCustomers" :key="c.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-600 whitespace-nowrap">{{ c.customer_code }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">{{ c.name }}</div>
                                        <div class="text-[10px] text-slate-500">{{ c.phone }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ c.package_name }}</td>
                                    <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                        {{ c.registration_date || '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div v-if="c.activation_date && c.activation_date !== '-'" class="inline-flex items-center gap-1 font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200 text-[11px]">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>{{ c.activation_date }}</span>
                                        </div>
                                        <span v-else class="text-slate-400 italic text-[11px]">-</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <!-- Status Tagihan -->
                                                <span
                                                    v-if="c.has_overdue_invoice"
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200"
                                                >
                                                    ⚠️ Lewat Jatuh Tempo
                                                </span>
                                                <span
                                                    v-else-if="c.is_prorata && (!c.is_issued || c.is_before_issue_date)"
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200"
                                                >
                                                    ⚡ Prorata (Terbit {{ c.issued_date }})
                                                </span>
                                                <span
                                                    v-else-if="c.is_prorata && c.is_issued"
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200"
                                                >
                                                    ⚡ Prorata Aktif
                                                </span>
                                                <span
                                                    v-else-if="c.has_unpaid_invoice && c.is_issued"
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200"
                                                >
                                                    ⏳ Belum Bayar
                                                </span>
                                                <span
                                                    v-else-if="c.has_unpaid_invoice && (!c.is_issued || c.is_before_issue_date)"
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200"
                                                >
                                                    ⏳ Belum Terbit (Terbit {{ c.issued_date }})
                                                </span>
                                                <span
                                                    v-else-if="c.status === 'active'"
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                                >
                                                    ✓ Lunas
                                                </span>
                                                <span
                                                    v-else
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 capitalize"
                                                >
                                                    {{ c.status }}
                                                </span>

                                                <span v-if="c.is_migrated" class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[9px] font-semibold border border-indigo-200">
                                                    Migrasi
                                                </span>
                                            </div>
                                            <div v-if="c.has_unpaid_invoice" class="text-[10px] text-slate-500 font-medium space-y-0.5">
                                                <div v-if="!c.is_issued && c.issued_date" class="text-purple-700 font-semibold">
                                                    Terbit: {{ c.issued_date }}
                                                </div>
                                                <div v-if="c.unpaid_due_date">
                                                    Jatuh Tempo: <span :class="c.has_overdue_invoice ? 'text-rose-600 font-bold' : 'text-slate-700 font-semibold'">{{ c.unpaid_due_date }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div v-if="c.has_unpaid_invoice && (customerSubTab === 'unpaid' || customerSubTab === 'overdue' || customerSubTab === 'prorata')">
                                            <div class="font-extrabold text-sm" :class="c.has_overdue_invoice ? 'text-rose-600' : (c.is_prorata ? 'text-purple-700' : 'text-amber-700')">
                                                {{ formatRupiah(c.unpaid_amount) }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-medium">
                                                Komisi: {{ formatRupiah(c.total_commission) }}
                                            </div>
                                        </div>
                                        <div v-else>
                                            <div class="font-bold text-emerald-600 text-sm">{{ formatRupiah(c.total_commission) }}</div>
                                            <div v-if="c.status === 'active' && !c.has_unpaid_invoice" class="text-[10px] text-slate-400 font-medium">
                                                +{{ formatRupiah(settings.affiliate_commission_monthly) }}/bln
                                            </div>
                                            <div v-else-if="c.has_unpaid_invoice" class="text-[10px] text-amber-600 font-medium">
                                                Tagihan: {{ formatRupiah(c.unpaid_amount) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <a
                                            v-if="c.can_bantu_tagih && c.wa_reminder_url"
                                            :href="c.wa_reminder_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer"
                                            :title="'Bantu ingatkan pembayaran tagihan via WhatsApp ke ' + c.name"
                                        >
                                            <span>💬 Bantu Tagih</span>
                                        </a>
                                        <span
                                            v-else-if="c.has_unpaid_invoice && (!c.is_issued || c.is_before_issue_date)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-50 text-purple-700 font-bold text-[10px] rounded-lg border border-purple-200 whitespace-nowrap"
                                        >
                                            ⏳ Bayar Mulai {{ c.issued_date || '1 Dec' }}
                                        </span>
                                        <span
                                            v-else-if="c.status === 'active' && !c.has_unpaid_invoice"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold text-[11px] rounded-lg border border-emerald-200"
                                        >
                                            ✓ Lunas
                                        </span>
                                        <span v-else class="text-slate-400 text-xs">-</span>
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

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- Tab Content: Jaringan & Tim Downline                      -->
                <!-- ═══════════════════════════════════════════════════════════ -->
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

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- Tab Content: Pelanggan Berhenti < 4 Bulan (Clawback)       -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'clawback'" class="p-6 space-y-6">
                    <!-- Policy Warning Banner -->
                    <div class="p-5 sm:p-6 bg-rose-50/90 border-2 border-rose-200 rounded-3xl space-y-3">
                        <div class="flex items-start gap-3.5">
                            <div class="w-10 h-10 rounded-2xl bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center text-lg shrink-0 mt-0.5">
                                ✂️
                            </div>
                            <div class="space-y-1">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-900 text-[10px] font-extrabold uppercase tracking-wider border border-rose-200">
                                    <span>Ketentuan SOP Kemitraan</span>
                                    <span>&bull;</span>
                                    <span>Kontrak Minimal 4 Bulan</span>
                                </div>
                                <h3 class="text-sm sm:text-base font-black text-rose-950">
                                    Penarikan Kembali Komisi (Pelanggan Berhenti Sebelum Masa Aktif 4 Bulan)
                                </h3>
                                <p class="text-xs text-rose-800 leading-relaxed">
                                    Setiap pelanggan baru memiliki batas komitmen berlangganan minimal <strong>4 bulan</strong>. Apabila pelanggan yang Anda daftarkan berhenti berlangganan (status <em>terminated</em> atau <em>dibatalkan</em>) sebelum genap 4 bulan, maka komisi booking (Rp 35.000) dan fee survey (Rp 15.000) yang sebelumnya telah dialokasikan <strong>ditarik kembali seutuhnya oleh perusahaan</strong>.
                                </p>
                            </div>
                        </div>

                        <!-- 3 Stat Cards Summary -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                            <div class="bg-white/90 p-4 rounded-2xl border border-rose-200">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Pelanggan Berhenti &lt; 4 Bln</span>
                                <span class="text-xl font-black text-rose-700 block mt-1">
                                    {{ total_clawback_count || clawbacks.length }} Pelanggan
                                </span>
                            </div>
                            <div class="bg-white/90 p-4 rounded-2xl border border-rose-200">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Total Komisi Ditarik Kembali</span>
                                <span class="text-xl font-black text-rose-700 block mt-1">
                                    -{{ formatRupiah(total_clawback_amount || 0) }}
                                </span>
                            </div>
                            <div class="bg-white/90 p-4 rounded-2xl border border-rose-200">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">Saluran Penarikan</span>
                                <span class="text-xs font-bold text-slate-800 block mt-1">
                                    {{ is_employee ? 'Slip Gaji (Potongan Payroll)' : 'Saldo Dompet Afiliasi' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Pelanggan Berhenti < 4 Bulan -->
                    <div v-if="clawbacks && clawbacks.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wider text-left">
                                <tr>
                                    <th class="px-4 py-3">ID & Nama Pelanggan</th>
                                    <th class="px-4 py-3">Paket</th>
                                    <th class="px-4 py-3">Aktivasi & Berhenti</th>
                                    <th class="px-4 py-3">Masa Aktif</th>
                                    <th class="px-4 py-3">Alasan Berhenti</th>
                                    <th class="px-4 py-3 text-right">Komisi Ditarik</th>
                                    <th class="px-4 py-3 text-center">Saluran Potong</th>
                                    <th class="px-4 py-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in clawbacks" :key="c.id" class="hover:bg-rose-50/20 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-extrabold text-slate-900">{{ c.name }}</div>
                                        <div class="text-[10px] font-mono text-indigo-600">{{ c.customer_code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ c.package_name }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        <div><span class="text-slate-400">Aktif:</span> {{ c.activation_date }}</div>
                                        <div class="text-rose-600 font-medium"><span class="text-slate-400">Stop:</span> {{ c.cancel_date }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                                            ⏱️ {{ c.active_duration }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 max-w-xs truncate" :title="c.cancel_reason">
                                        {{ c.cancel_reason }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-rose-600 text-sm whitespace-nowrap">
                                        -{{ formatRupiah(c.clawback_amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="text-[10px] font-bold px-2 py-0.5 rounded-full border whitespace-nowrap"
                                            :class="is_employee ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        >
                                            {{ c.channel }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-200 uppercase tracking-wider">
                                            {{ c.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-14 bg-emerald-50/50 rounded-3xl border border-emerald-100 space-y-3">
                        <span class="text-5xl block">🎉</span>
                        <div>
                            <h4 class="text-base font-bold text-emerald-950">Bagus Sekali! Belum Ada Pelanggan Berhenti Sebelum 4 Bulan</h4>
                            <p class="text-xs text-emerald-800 mt-1 max-w-lg mx-auto">
                                Semua pelanggan yang Anda daftarkan tetap aktif berlangganan atau telah melewati batas minimum kontrak 4 bulan. Seluruh komisi booking Anda aman seutuhnya!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- Tab Content: Riwayat Komisi                                -->
                <!-- ═══════════════════════════════════════════════════════════ -->
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

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- Tab Content: Pencairan Dana (Withdrawals)                  -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'withdrawals'" class="p-6">
                    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Riwayat Penarikan Dana</h4>
                            <p class="text-xs text-slate-500">Pencairan diproses langsung ke rekening bank / e-wallet Anda.</p>
                        </div>
                        <div v-if="!is_employee">
                            <button
                                type="button"
                                @click="openWithdrawModal"
                                :disabled="payout_info?.has_unpaid_customers || !payout_info?.is_payout_open || profile.balance < settings.affiliate_min_withdraw"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
                            >
                                <span v-if="payout_info?.has_unpaid_customers">🔒 Pencairan Terkunci</span>
                                <span v-else-if="!payout_info?.is_payout_open">📅 Buka Tgl {{ payout_info?.payout_day || 15 }}</span>
                                <span v-else>+ Ajukan Penarikan Baru</span>
                            </button>
                        </div>
                    </div>

                    <!-- Notice Lock Jika Ada Pelanggan Belum Bayar -->
                    <div v-if="!is_employee && payout_info?.has_unpaid_customers" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl mb-4 text-xs text-rose-900 flex items-start justify-between gap-3 flex-wrap">
                        <div class="space-y-0.5">
                            <strong class="font-bold text-rose-950 flex items-center gap-1.5">
                                <span>🔒</span> Fitur Penarikan Saldo Terkunci
                            </strong>
                            <p class="text-rose-800">
                                Anda memiliki <b>{{ payout_info.unpaid_customers_count }} pelanggan booking</b> yang belum melunasi tagihannya (Total: {{ formatRupiah(payout_info.total_unpaid_amount) }}).
                                Pelanggan wajib lunas terlebih dahulu sebelum pencairan dana dibuka.
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="customerSubTab = 'unpaid'; $inertia.visit('/affiliate/dashboard?tab=customers')"
                            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition-colors shrink-0"
                        >
                            Bantu Tagih Pelanggan &rarr;
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

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- Tab Content: Rekening Bank / E-Wallet                     -->
                <!-- ═══════════════════════════════════════════════════════════ -->
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

                <!-- ═══════════════════════════════════════════════════════════ -->
                <!-- Tab Content: Panduan Lengkap & Ketentuan Resmi             -->
                <!-- ═══════════════════════════════════════════════════════════ -->
                <div v-if="activeTab === 'guide'" class="p-6 space-y-6">
                    <!-- Header Banner -->
                    <div class="bg-gradient-to-br from-indigo-950 via-slate-900 to-blue-950 p-6 sm:p-7 rounded-3xl text-white shadow-md border border-indigo-500/20">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/30 text-indigo-200 text-[10px] font-extrabold uppercase tracking-wider border border-indigo-400/30">
                                📖 Panduan Resmi Kemitraan
                            </span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-white">
                            Panduan Lengkap Sales & Afiliasi ISP
                        </h2>
                        <p class="text-xs sm:text-sm text-indigo-200/90 mt-1 max-w-2xl leading-relaxed">
                            Pelajari seluruh alur kemitraan dari input booking calon pelanggan, syarat perolehan komisi, ketentuan wajib lunas tagihan, pinalti stop dini &lt; 4 bulan, hingga tata cara pencairan dana ke rekening Anda.
                        </p>
                    </div>

                    <!-- Step Cards Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Step 1: Struktur Komisi -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-3">
                            <div class="flex items-center gap-2 text-indigo-700 font-bold text-xs uppercase tracking-wider">
                                <span class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center text-xs">1</span>
                                <span>Struktur Komisi &amp; Tarif Resmi</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Setiap transaksi pelanggan baru maupun perpanjangan tagihan bulanan memberikan hak komisi bagi Anda:
                            </p>
                            <ul class="text-xs space-y-2 text-slate-700">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold">🎯</span>
                                    <div>
                                        <strong>Fee Booking: {{ formatRupiah(settings.affiliate_commission_booking) }}</strong>
                                        <p class="text-[11px] text-slate-500">Diberikan 1x untuk setiap pelanggan baru yang berhasil aktif (Aktivasi mulai Oktober 2026).</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold">💰</span>
                                    <div>
                                        <strong>Pasif Bulanan Direct: {{ formatRupiah(settings.affiliate_commission_monthly) }} / bln</strong>
                                        <p class="text-[11px] text-slate-500">Mengalir setiap bulan selama pelanggan Anda tetap aktif dan melunasi invoice tagihan.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-purple-600 font-bold">🚀</span>
                                    <div>
                                        <strong>Pasif Downline L1: {{ formatRupiah(settings.affiliate_commission_upline_1) }} / bln</strong>
                                        <p class="text-[11px] text-slate-500">Dari setiap pelanggan aktif milik mitra binaan langsung yang Anda sponsori.</p>
                                    </div>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-amber-600 font-bold">🌟</span>
                                    <div>
                                        <strong>Pasif Downline L2: {{ formatRupiah(settings.affiliate_commission_upline_2) }} / bln</strong>
                                        <p class="text-[11px] text-slate-500">Dari setiap pelanggan aktif milik mitra tingkat kedua.</p>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- Panduan Cakupan Wilayah & Desa BC -->
                        <div class="p-5 rounded-2xl border-2 border-indigo-200 bg-gradient-to-br from-indigo-50/70 via-blue-50/50 to-white space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 text-indigo-800 font-bold text-xs uppercase tracking-wider">
                                    <span class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center text-xs">🧭</span>
                                    <span>Panduan Area &amp; Wilayah Cakupan Desa</span>
                                </div>
                                <span class="text-[10px] font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                                    Penting Saat Booking
                                </span>
                            </div>
                            <h4 class="text-sm font-black text-slate-900">
                                Jangan Salah Pilih Area / BC Saat Menginput Pelanggan
                            </h4>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Agar penugasan teknisi dan survey berjalan lancar, pastikan memilih Area/BC yang sesuai dengan lokasi desa pelanggan:
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                                <div v-for="item in (props.area_coverage || [])" :key="item.name" class="p-3 bg-white rounded-xl border border-indigo-100 shadow-2xs">
                                    <div class="flex items-center gap-2 font-extrabold text-xs text-slate-900">
                                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                        <span>{{ item.name }}</span>
                                        <span v-if="item.description" class="text-[11px] text-slate-400 font-normal">({{ item.description }})</span>
                                    </div>
                                    <div class="mt-2">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Cakupan Desa:</div>
                                        <div v-if="item.villages && item.villages.length > 0" class="flex flex-wrap gap-1">
                                            <span v-for="v in item.villages" :key="v" class="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-[11px] font-semibold rounded-md border border-indigo-100">
                                                🏘️ {{ v }}
                                            </span>
                                        </div>
                                        <span v-else class="text-[11px] text-slate-400 italic">Belum diatur</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Alur Booking Sampai Aktivasi -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-3">
                            <div class="flex items-center gap-2 text-indigo-700 font-bold text-xs uppercase tracking-wider">
                                <span class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center text-xs">2</span>
                                <span>Alur Booking Sampai Aktivasi</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Proses pendaftaran pelanggan baru sangat mudah dan transparan:
                            </p>
                            <div class="space-y-2 text-xs">
                                <div class="p-2.5 bg-white rounded-xl border border-slate-200/80">
                                    <strong class="text-slate-800">Langkah A &bull; Input Booking</strong>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Buka menu <em>Booking Baru</em> atau bagikan Link Referral Anda ke calon pelanggan.</p>
                                </div>
                                <div class="p-2.5 bg-white rounded-xl border border-slate-200/80">
                                    <strong class="text-slate-800">Langkah B &bull; Survey &amp; Instalasi</strong>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Tim teknisi melakukan pengecekan tiang ODP dan pemasangan kabel &amp; ONT WiFi di rumah pelanggan.</p>
                                </div>
                                <div class="p-2.5 bg-white rounded-xl border border-slate-200/80">
                                    <strong class="text-slate-800">Langkah C &bull; Layanan Aktif</strong>
                                    <p class="text-[11px] text-slate-500 mt-0.5">Setelah koneksi internet aktif, komisi booking Rp 35.000 otomatis tercatat ke saldo akun Anda.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Syarat Wajib Lunas Invoice -->
                        <div class="p-5 rounded-2xl border-2 border-rose-200 bg-rose-50/40 space-y-3">
                            <div class="flex items-center gap-2 text-rose-800 font-bold text-xs uppercase tracking-wider">
                                <span class="w-6 h-6 rounded-lg bg-rose-100 flex items-center justify-center text-xs">3</span>
                                <span>Syarat Mutlak: Wajib Lunas Invoice</span>
                            </div>
                            <h4 class="text-sm font-black text-rose-950">
                                Pencairan Saldo Terkunci Apabila Ada Pelanggan Menunggak
                            </h4>
                            <p class="text-xs text-rose-800 leading-relaxed">
                                Perusahaan memberlakukan kebijakan zero-tolerance terhadap tunggakan:
                            </p>
                            <ul class="text-xs space-y-1.5 text-rose-900 list-disc list-inside leading-relaxed">
                                <li>Komisi bulanan hanya diberikan jika pelanggan membayar tagihannya.</li>
                                <li><strong>Apabila terdapat pelanggan binaan Anda yang belum bayar, penarikan dana (withdraw) otomatis TERKUNCI seutuhnya.</strong></li>
                                <li>Mitra dapat membuka tab <strong>"Pelanggan Belum Bayar"</strong> untuk melihat rincian tunggakan dan menekan tombol <strong>"💬 Bantu Tagih via WA"</strong>.</li>
                            </ul>
                        </div>

                        <!-- Step 4: Ketentuan Kontrak Minimal 4 Bulan -->
                        <div class="p-5 rounded-2xl border-2 border-amber-200 bg-amber-50/40 space-y-3">
                            <div class="flex items-center gap-2 text-amber-900 font-bold text-xs uppercase tracking-wider">
                                <span class="w-6 h-6 rounded-lg bg-amber-100 flex items-center justify-center text-xs">4</span>
                                <span>Pinalti Berhenti &lt; 4 Bulan (Clawback)</span>
                            </div>
                            <h4 class="text-sm font-black text-amber-950">
                                Komisi Ditarik Kembali Seutuhnya oleh Perusahaan
                            </h4>
                            <p class="text-xs text-amber-900 leading-relaxed">
                                Pelanggan internet baru terikat komitmen minimal berlangganan 4 bulan penuh:
                            </p>
                            <ul class="text-xs space-y-1.5 text-amber-900 list-disc list-inside leading-relaxed">
                                <li>Jika pelanggan berhenti berlangganan sebelum genap 4 bulan, fee booking (Rp 35.000) dan fee survey (Rp 15.000) <strong>ditarik kembali seutuhnya</strong>.</li>
                                <li><strong>Karyawan Internal:</strong> Otomatis dipotong dari Slip Gaji Payroll bulan berikutnya.</li>
                                <li><strong>Mitra Luar:</strong> Otomatis didebet langsung dari Saldo Dompet Afiliasi Anda.</li>
                                <li>Riwayat pemotongan dapat dicek pada tab <strong>"Pelanggan Berhenti (&lt; 4 Bln)"</strong>.</li>
                            </ul>
                        </div>

                        <!-- Step 5: Tata Cara Pencairan Dana -->
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-3 md:col-span-2">
                            <div class="flex items-center gap-2 text-indigo-700 font-bold text-xs uppercase tracking-wider">
                                <span class="w-6 h-6 rounded-lg bg-indigo-100 flex items-center justify-center text-xs">5</span>
                                <span>Tata Cara &amp; Jadwal Pencairan Dana (Withdrawals)</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                <div class="p-3 bg-white rounded-xl border border-slate-200">
                                    <strong class="text-slate-800 block mb-1">📅 Jadwal Pencairan</strong>
                                    <p class="text-slate-500">Khusus Mitra Luar dicairkan per tanggal <strong>15 setiap bulannya</strong>. Karyawan internal otomatis masuk slip gaji payroll.</p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200">
                                    <strong class="text-slate-800 block mb-1">💵 Minimal Penarikan</strong>
                                    <p class="text-slate-500">Saldo minimal pencairan adalah <strong>Rp 50.000</strong>. Anda dapat mengakumulasikan saldo komisi sesuai kebutuhan.</p>
                                </div>
                                <div class="p-3 bg-white rounded-xl border border-slate-200">
                                    <strong class="text-slate-800 block mb-1">⚡ Transfer Otomatis</strong>
                                    <p class="text-slate-500">Pencairan diproses langsung via payment gateway <strong>Flip for Business</strong> ke rekening bank / e-wallet (DANA, GoPay, OVO, ShopeePay).</p>
                                </div>
                            </div>
                        </div>
                    </div>
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
    clawbacks: Array,
    total_clawback_amount: Number,
    total_clawback_count: Number,
    settings: Object,
    is_employee: Boolean,
    pending_payroll_incentives: Number,
    payout_info: Object,
    area_coverage: Array,
    tab: String,
});

const activeTab = computed(() => affiliateSection('/affiliate/dashboard', props.tab));
const currentSection = computed(() => affiliateSections.find(item => item.id === activeTab.value));

const sectionDescriptions = {
    customers: 'Daftar booking dan pelanggan yang Anda daftarkan langsung, dipisahkan berdasarkan status pembayaran tagihan.',
    downlines: 'Nomor afiliasi Anda dan jaringan downline tingkat 1 dan 2.',
    clawback: 'Daftar pelanggan berhenti sebelum masa aktif 4 bulan dengan penarikan komisi seutuhnya.',
    commissions: 'Rincian komisi booking, bulanan, dan jaringan afiliasi Anda.',
    withdrawals: 'Pantau pengajuan dan status pencairan saldo komisi.',
    bank: 'Kelola rekening tujuan pencairan dan nomor konfirmasi Anda.',
    guide: 'Panduan lengkap alur kerja sales & afiliasi, struktur komisi, SOP penagihan, hingga tata cara pencairan.',
};

const copyMessage = ref('');
const showWithdrawModal = ref(false);
const customerSubTab = ref('all'); // 'all', 'paid', 'unpaid', 'prorata', 'overdue'

const paidCustomers = computed(() => (props.customers || []).filter(c => !c.has_unpaid_invoice && c.status === 'active'));
const unpaidCustomersList = computed(() => (props.customers || []).filter(c => c.has_unpaid_invoice));
const prorataCustomers = computed(() => (props.customers || []).filter(c => c.is_prorata && c.has_unpaid_invoice));
const overdueCustomers = computed(() => (props.customers || []).filter(c => c.has_overdue_invoice));
const unpaidCustomers = unpaidCustomersList; // Backward compat for unpaid count / overdue
const filteredCustomers = computed(() => {
    if (customerSubTab.value === 'paid') return paidCustomers.value;
    if (customerSubTab.value === 'unpaid') return unpaidCustomersList.value;
    if (customerSubTab.value === 'prorata') return prorataCustomers.value;
    if (customerSubTab.value === 'overdue') return overdueCustomers.value;
    return props.customers || [];
});

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
