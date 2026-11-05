<template>
    <AppLayout title="Invoice Proyek & Jasa B2B" subtitle="Penagihan komersial proyek eksternal, penarikan kabel instansi/vendor, dan integrasi kas">
        <div class="space-y-6">

            <!-- 1. STATS SUMMARY CARDS -->
            <div v-if="$can('project_invoices_card_stats')" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Proyek -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider mb-1">Total Proyek</p>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white">{{ stats.total_projects }} <span class="text-xs font-normal text-gray-500">Proyek</span></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>

                <!-- Total Nilai Tagihan -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider mb-1">Total Nilai Tagihan</p>
                        <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ formatCurrency(stats.total_billed) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                    </div>
                </div>

                <!-- Kas Diterima (Lunas) -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider mb-1">Kas Masuk Diterima</p>
                        <h3 class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(stats.total_received) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- Piutang Belum Lunas -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider mb-1">Piutang Belum Lunas</p>
                        <h3 class="text-xl font-black text-rose-600 dark:text-rose-400">{{ formatCurrency(stats.total_unpaid) }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
            </div>

            <!-- 2. ACTION & FILTER BAR -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 sm:p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Pencarian Cepat -->
                    <div class="relative flex-1 max-w-md">
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari No. Invoice, Nama Klien, No. PO/SPK, Judul..."
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <!-- Tombol Aksi Kanan -->
                    <div class="flex items-center gap-2.5 shrink-0">
                        <button
                            v-if="$can('project_invoices_create')"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            + Buat Invoice Proyek
                        </button>
                    </div>
                </div>

                <!-- Dropdown Filter Bar -->
                <div class="flex flex-wrap items-center gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <!-- Status Filter -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Status:</span>
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="text-xs py-1.5 pl-2.5 pr-8 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white font-medium"
                        >
                            <option value="all">Semua Status</option>
                            <option value="unpaid">Belum Lunas</option>
                            <option value="partial">Sebagian / DP</option>
                            <option value="paid">Lunas</option>
                            <option value="draft">Draf</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Scheme Filter -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Skema:</span>
                        <select
                            v-model="selectedScheme"
                            @change="applyFilters"
                            class="text-xs py-1.5 pl-2.5 pr-8 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white font-medium"
                        >
                            <option value="all">Semua Skema</option>
                            <option value="full">Penuh (100%)</option>
                            <option value="termin_dp">Termin 1 (DP)</option>
                            <option value="termin_progress">Termin Progres</option>
                            <option value="termin_final">Pelunasan</option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Tgl:</span>
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="text-xs py-1 px-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white"
                        />
                        <span class="text-xs text-gray-400">-</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="text-xs py-1 px-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white"
                        />
                        <button
                            @click="applyFilters"
                            class="px-2.5 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg font-medium"
                        >
                            Terapkan
                        </button>
                    </div>

                    <!-- Reset Filter -->
                    <button
                        v-if="hasActiveFilters"
                        @click="resetFilters"
                        class="text-xs text-rose-500 hover:underline font-medium ml-auto"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- 3. TABEL DATA INVOICE PROYEK -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/75 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4">No. Invoice & Tanggal</th>
                                <th class="py-3.5 px-4">Klien / Perusahaan</th>
                                <th class="py-3.5 px-4">Proyek & Pekerjaan</th>
                                <th class="py-3.5 px-4 text-right">Nilai Tagihan</th>
                                <th class="py-3.5 px-4 text-center">Status Bayar</th>
                                <th class="py-3.5 px-4 text-right">Sisa Piutang</th>
                                <th class="py-3.5 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <tr v-if="invoices.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-gray-400 dark:text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="font-medium">Belum ada data Invoice Proyek.</p>
                                    <p class="text-xs text-gray-400 mt-1">Klik tombol "+ Buat Invoice Proyek" untuk membuat tagihan baru.</p>
                                </td>
                            </tr>
                            <tr
                                v-for="inv in invoices.data"
                                :key="inv.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <!-- No Invoice & Tgl -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-blue-600 dark:text-blue-400 font-mono text-[13px]">{{ inv.invoice_number }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Terbit: {{ formatDate(inv.issue_date) }}</div>
                                    <div v-if="inv.due_date" class="text-[11px] text-rose-500 dark:text-rose-400 font-medium">Tempo: {{ formatDate(inv.due_date) }}</div>
                                </td>

                                <!-- Klien / Perusahaan -->
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ inv.client_name }}</div>
                                    <div v-if="inv.client_pic" class="text-xs text-gray-500 dark:text-gray-400">PIC: {{ inv.client_pic }}</div>
                                    <div v-if="inv.client_phone" class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-0.5">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.173.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.072.043.419-.101.824z"/></svg>
                                        {{ inv.client_phone }}
                                    </div>
                                    <span v-if="inv.po_spk_number" class="inline-block mt-1 px-2 py-0.5 text-[10.5px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                                        PO: {{ inv.po_spk_number }}
                                    </span>
                                </td>

                                <!-- Proyek & Pekerjaan -->
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="font-semibold text-gray-900 dark:text-white line-clamp-2">{{ inv.project_title }}</div>
                                    <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                        <span class="text-[11px] px-2 py-0.5 bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded font-medium">
                                            {{ inv.items ? inv.items.length : 0 }} Item
                                        </span>
                                        <span class="text-[11px] px-2 py-0.5 bg-purple-50 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300 rounded font-medium">
                                            {{ getSchemeLabel(inv.payment_scheme) }}
                                        </span>
                                        <span v-if="inv.project_location" class="text-[11px] text-gray-400 truncate">
                                            📍 {{ inv.project_location }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Nilai Tagihan -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="font-black text-gray-900 dark:text-white text-base">{{ formatCurrency(inv.total_amount) }}</div>
                                    <div v-if="inv.tax_ppn_amount > 0" class="text-[11px] text-gray-500">PPN: +{{ formatCurrency(inv.tax_ppn_amount) }}</div>
                                    <div v-if="inv.tax_pph23_amount > 0" class="text-[11px] text-rose-500">PPh: -{{ formatCurrency(inv.tax_pph23_amount) }}</div>
                                </td>

                                <!-- Status Bayar -->
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        :class="getStatusBadgeClass(inv.status)"
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                                    >
                                        {{ getStatusLabel(inv.status) }}
                                    </span>
                                    <div v-if="inv.paid_amount > 0" class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 mt-1">
                                        Masuk: {{ formatCurrency(inv.paid_amount) }}
                                    </div>
                                </td>

                                <!-- Sisa Piutang -->
                                <td class="py-3.5 px-4 text-right font-semibold">
                                    <span v-if="inv.remaining_amount > 0" class="text-rose-600 dark:text-rose-400">
                                        {{ formatCurrency(inv.remaining_amount) }}
                                    </span>
                                    <span v-else class="text-emerald-600 dark:text-emerald-400 text-xs">
                                        ✓ Lunas Penuh
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Cetak Invoice -->
                                        <a
                                            :href="`/project-invoices/${inv.id}/print`"
                                            target="_blank"
                                            title="Cetak Invoice Proyek (Print/PDF)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/40 rounded-lg transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>

                                        <!-- Cetak Kwitansi -->
                                        <a
                                            v-if="inv.paid_amount > 0"
                                            :href="`/project-invoices/${inv.id}/receipt`"
                                            target="_blank"
                                            title="Cetak Kwitansi Pembayaran"
                                            class="p-1.5 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/40 rounded-lg transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </a>

                                        <!-- Catat Bayar -->
                                        <button
                                            v-if="inv.status !== 'paid' && inv.status !== 'cancelled' && $can('project_invoices_pay')"
                                            @click="openPaymentModal(inv)"
                                            title="Catat Pembayaran (Masuk Kas)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/40 rounded-lg transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        </button>

                                        <!-- Edit -->
                                        <button
                                            v-if="$can('project_invoices_edit')"
                                            @click="openEditModal(inv)"
                                            title="Edit Invoice"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/40 rounded-lg transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>

                                        <!-- Hapus -->
                                        <button
                                            v-if="$can('project_invoices_delete')"
                                            @click="confirmDelete(inv)"
                                            title="Hapus Invoice"
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/40 rounded-lg transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links && invoices.links.length > 3" class="px-4 py-3 bg-gray-50/50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500">
                        Menampilkan {{ invoices.from || 0 }} - {{ invoices.to || 0 }} dari {{ invoices.total }} data
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, idx) in invoices.links"
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg font-medium transition',
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- 4. MODAL BUAT / EDIT INVOICE PROYEK -->
            <div v-if="showFormModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-4xl shadow-2xl border border-gray-100 dark:border-gray-700 max-h-[92vh] flex flex-col">
                    
                    <!-- Header Modal -->
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between shrink-0">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ isEditMode ? 'Edit Invoice Proyek: ' + form.invoice_number : 'Buat Invoice Proyek & Jasa Baru' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Lengkapi data klien B2B, rincian pekerjaan, dan skema penagihan.</p>
                        </div>
                        <button @click="showFormModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Body Modal Form -->
                    <form @submit.prevent="submitInvoiceForm" class="p-6 overflow-y-auto space-y-6 flex-1">
                        
                        <!-- SECTION 1: KLIEN & KONTRAK -->
                        <div>
                            <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-3">1. Informasi Klien & Legalitas</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Perusahaan / Instansi / Klien <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.client_name"
                                        type="text"
                                        required
                                        placeholder="Contoh: PT. Telkom Akses / Dinas Kominfo"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama PIC / Kontak Person</label>
                                    <input
                                        v-model="form.client_pic"
                                        type="text"
                                        placeholder="Contoh: Bpk. Hendra Wijaya"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No. WhatsApp / Telepon</label>
                                    <input
                                        v-model="form.client_phone"
                                        type="text"
                                        placeholder="Contoh: 08123456789"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Email Klien</label>
                                    <input
                                        v-model="form.client_email"
                                        type="email"
                                        placeholder="procurement@company.com"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No. PO / SPK / Kontrak</label>
                                    <input
                                        v-model="form.po_spk_number"
                                        type="text"
                                        placeholder="Contoh: PO-089/X/2026"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Alamat Kantor Klien</label>
                                    <input
                                        v-model="form.client_address"
                                        type="text"
                                        placeholder="Jl. Merdeka No. 123, Bandung"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">NPWP Klien (Opsional)</label>
                                    <input
                                        v-model="form.client_npwp"
                                        type="text"
                                        placeholder="00.000.000.0-000.000"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: DETAIL PROYEK -->
                        <div>
                            <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-3">2. Detail Pekerjaan & Waktu</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Judul / Uraian Singkat Proyek <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.project_title"
                                        type="text"
                                        required
                                        placeholder="Contoh: Jasa Penarikan Kabel FO 24 Core 1.5 KM PT. XYZ"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Lokasi Pengerjaan</label>
                                    <input
                                        v-model="form.project_location"
                                        type="text"
                                        placeholder="Contoh: Ruas Jl. Raya Sukabumi - Cianjur"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Terbit Invoice <span class="text-rose-500">*</span></label>
                                    <input
                                        v-model="form.issue_date"
                                        type="date"
                                        required
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Jatuh Tempo</label>
                                    <input
                                        v-model="form.due_date"
                                        type="date"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Skema Pembayaran</label>
                                    <select
                                        v-model="form.payment_scheme"
                                        class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                                    >
                                        <option value="full">Pembayaran Penuh (100%)</option>
                                        <option value="termin_dp">Termin 1 (Uang Muka / DP)</option>
                                        <option value="termin_progress">Termin Progres Pengerjaan</option>
                                        <option value="termin_final">Termin Pelunasan (Final)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 3: RINCIAN ITEM PEKERJAAN & MATERIAL (DYNAMIC ROWS) -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">3. Rincian Pekerjaan, Jasa & Material</h4>
                                <button
                                    type="button"
                                    @click="addItemRow"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/40 hover:bg-blue-100 text-blue-600 dark:text-blue-300 text-xs font-semibold rounded-lg transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah Baris
                                </button>
                            </div>

                            <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-gray-100 dark:bg-gray-700/80 font-bold text-gray-600 dark:text-gray-300">
                                        <tr>
                                            <th class="py-2.5 px-3">Uraian Pekerjaan / Material</th>
                                            <th class="py-2.5 px-3 w-24 text-center">Volume / Qty</th>
                                            <th class="py-2.5 px-3 w-28 text-center">Satuan</th>
                                            <th class="py-2.5 px-3 w-36 text-right">Harga Satuan (Rp)</th>
                                            <th class="py-2.5 px-3 w-36 text-right">Total (Rp)</th>
                                            <th class="py-2.5 px-2 w-10 text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <tr v-for="(item, idx) in form.items" :key="idx" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                            <td class="p-2">
                                                <input
                                                    v-model="item.description"
                                                    type="text"
                                                    required
                                                    placeholder="Deskripsi pekerjaan/material..."
                                                    class="w-full text-xs px-2.5 py-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white"
                                                />
                                            </td>
                                            <td class="p-2">
                                                <input
                                                    v-model.number="item.qty"
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    required
                                                    class="w-full text-xs px-2 py-1.5 text-center bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white"
                                                />
                                            </td>
                                            <td class="p-2">
                                                <select
                                                    v-model="item.unit"
                                                    class="w-full text-xs px-2 py-1.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white"
                                                >
                                                    <option value="Meter">Meter</option>
                                                    <option value="Titik">Titik</option>
                                                    <option value="Core">Core</option>
                                                    <option value="Roll">Roll</option>
                                                    <option value="Unit">Unit</option>
                                                    <option value="Paket">Paket</option>
                                                    <option value="Hari">Hari</option>
                                                    <option value="Bulan">Bulan</option>
                                                    <option value="Set">Set</option>
                                                    <option value="Jasa">Jasa</option>
                                                </select>
                                            </td>
                                            <td class="p-2">
                                                <input
                                                    v-model.number="item.unit_price"
                                                    type="number"
                                                    min="0"
                                                    required
                                                    class="w-full text-xs px-2 py-1.5 text-right bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white font-mono"
                                                />
                                            </td>
                                            <td class="p-2 text-right font-mono font-bold text-gray-900 dark:text-white">
                                                {{ formatCurrency((item.qty || 0) * (item.unit_price || 0)) }}
                                            </td>
                                            <td class="p-2 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeItemRow(idx)"
                                                    :disabled="form.items.length <= 1"
                                                    class="text-rose-500 hover:text-rose-700 disabled:opacity-20"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- SECTION 4: KALKULASI TOTAL & PAJAK -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-100 dark:border-gray-700">
                            <!-- Left: Bank & Notes -->
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Rekening Tujuan Pembayaran Resmi</label>
                                    <input
                                        v-model="form.payment_bank_info"
                                        type="text"
                                        class="w-full text-xs px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan / Ketentuan Khusus</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Syarat garansi, termin berikutnya, atau klausul teknis..."
                                        class="w-full text-xs px-3 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg dark:text-white"
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Right: Financial Breakdown -->
                            <div class="space-y-2 text-xs">
                                <div class="flex justify-between py-1 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-400">Subtotal Item:</span>
                                    <span class="font-bold text-gray-900 dark:text-white font-mono">{{ formatCurrency(calculatedSubtotal) }}</span>
                                </div>
                                <div class="flex items-center justify-between py-1 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-400">Potongan Diskon (Rp):</span>
                                    <input
                                        v-model.number="form.discount"
                                        type="number"
                                        min="0"
                                        class="w-32 text-right text-xs px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded font-mono"
                                    />
                                </div>
                                <div class="flex items-center justify-between py-1 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-400">Pajak PPN (%):</span>
                                    <div class="flex items-center gap-2">
                                        <select
                                            v-model.number="form.tax_ppn_percent"
                                            class="text-xs px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded"
                                        >
                                            <option :value="0">0% (Tanpa PPN)</option>
                                            <option :value="11">11%</option>
                                            <option :value="12">12%</option>
                                        </select>
                                        <span class="text-xs font-mono font-medium text-emerald-600">+{{ formatCurrency(calculatedPpn) }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between py-1 border-b border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-400">PPh 23 Jasa (%):</span>
                                    <div class="flex items-center gap-2">
                                        <select
                                            v-model.number="form.tax_pph23_percent"
                                            class="text-xs px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded"
                                        >
                                            <option :value="0">0% (Tanpa PPh)</option>
                                            <option :value="2">2% (Jasa Teknik/Konstruksi)</option>
                                        </select>
                                        <span class="text-xs font-mono font-medium text-rose-500">-{{ formatCurrency(calculatedPph) }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between py-2 bg-blue-50 dark:bg-blue-900/30 px-3 rounded-lg text-sm font-black text-blue-900 dark:text-blue-300">
                                    <span>Grand Total:</span>
                                    <span class="font-mono">{{ formatCurrency(calculatedGrandTotal) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                @click="showFormModal = false"
                                class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition disabled:opacity-50"
                            >
                                {{ isEditMode ? 'Simpan Perubahan' : 'Terbitkan Invoice Proyek' }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- 5. MODAL CATAT PEMBAYARAN (KAS UMUM) -->
            <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-lg shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Catat Pembayaran Proyek</h3>
                            <p class="text-xs text-gray-500">Invoice: {{ activeInvoice?.invoice_number }}</p>
                        </div>
                        <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPayment" class="p-6 space-y-4">
                        <!-- Info Card -->
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-xl space-y-1.5 text-xs text-blue-900 dark:text-blue-200">
                            <div class="flex justify-between">
                                <span>Klien / Instansi:</span>
                                <strong class="font-semibold">{{ activeInvoice?.client_name }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Tagihan Proyek:</span>
                                <strong class="font-mono">{{ formatCurrency(activeInvoice?.total_amount) }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Sudah Terbayar:</span>
                                <strong class="font-mono text-emerald-600">{{ formatCurrency(activeInvoice?.paid_amount) }}</strong>
                            </div>
                            <div class="flex justify-between pt-1 border-t border-blue-200 dark:border-blue-700 font-bold">
                                <span>Sisa Tagihan (Piutang):</span>
                                <strong class="font-mono text-rose-600">{{ formatCurrency(activeInvoice?.remaining_amount) }}</strong>
                            </div>
                        </div>

                        <!-- Nominal Pembayaran -->
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Total Yang Diterima (Rp) <span class="text-rose-500">*</span></label>
                                <button
                                    type="button"
                                    @click="paymentForm.paid_amount = activeInvoice?.total_amount"
                                    class="text-[11px] text-blue-600 hover:underline font-semibold"
                                >
                                    Set Lunas 100%
                                </button>
                            </div>
                            <input
                                v-model.number="paymentForm.paid_amount"
                                type="number"
                                required
                                min="1"
                                class="w-full text-base font-bold font-mono px-3.5 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Masukkan nominal akumulasi yang sudah diterima (jika lunas masukkan total tagihan, jika DP masukkan nominal DP).</p>
                        </div>

                        <!-- Tanggal Bayar -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Pembayaran <span class="text-rose-500">*</span></label>
                            <input
                                v-model="paymentForm.paid_at"
                                type="date"
                                required
                                class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                            />
                        </div>

                        <!-- Metode Bayar -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Metode Pembayaran</label>
                            <select
                                v-model="paymentForm.payment_method"
                                class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                            >
                                <option value="transfer">Transfer Bank Resmi</option>
                                <option value="cash">Kas Tunai</option>
                                <option value="giro">Bilyet Giro / Cek</option>
                                <option value="qris">QRIS / Payment Gateway</option>
                            </select>
                        </div>

                        <!-- Catatan / No Ref -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">No. Referensi Transfer / Catatan</label>
                            <input
                                v-model="paymentForm.payment_notes"
                                type="text"
                                placeholder="Ref Bank / Berita Acara Transfer..."
                                class="w-full text-sm px-3.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl dark:text-white"
                            />
                        </div>

                        <!-- Checkbox Auto Kas -->
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl border border-emerald-200 dark:border-emerald-800 flex items-start gap-2.5">
                            <input
                                v-model="paymentForm.auto_sync_kas"
                                type="checkbox"
                                id="autoSyncKas"
                                class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500"
                            />
                            <label for="autoSyncKas" class="text-xs text-emerald-900 dark:text-emerald-200 leading-snug">
                                <strong>Otomatis catat ke Buku Kas Umum (General Ledger)</strong><br>
                                <span class="text-[11px] text-emerald-700 dark:text-emerald-300">Pemasukan akan langsung muncul di laporan keuangan kas dengan kategori Jasa & Proyek Kerjasama.</span>
                            </label>
                        </div>

                        <!-- Footer -->
                        <div class="flex justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                @click="showPaymentModal = false"
                                class="px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="paymentForm.processing"
                                class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm disabled:opacity-50"
                            >
                                Simpan Pembayaran
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
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    invoices: Object,
    filters: Object,
    stats: Object,
    defaultBank: String,
});

// Filters
const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || 'all');
const selectedScheme = ref(props.filters.scheme || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const hasActiveFilters = computed(() => {
    return searchQuery.value !== '' || selectedStatus.value !== 'all' || selectedScheme.value !== 'all' || dateFrom.value !== '' || dateTo.value !== '';
});

const applyFilters = () => {
    router.get('/project-invoices', {
        search: searchQuery.value,
        status: selectedStatus.value,
        scheme: selectedScheme.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = 'all';
    selectedScheme.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

// Form Modal State
const showFormModal = ref(false);
const isEditMode = ref(false);
const editingInvoiceId = ref(null);

const form = useForm({
    invoice_number: '',
    client_name: '',
    client_pic: '',
    client_phone: '',
    client_email: '',
    client_address: '',
    client_npwp: '',
    po_spk_number: '',
    project_title: '',
    project_location: '',
    issue_date: new Date().toISOString().substring(0, 10),
    due_date: '',
    payment_scheme: 'full',
    discount: 0,
    tax_ppn_percent: 0,
    tax_pph23_percent: 0,
    payment_bank_info: props.defaultBank || 'BANK BRI 410101025028534 a/n Deri Gantinayasa',
    notes: '',
    items: [
        { description: '', qty: 1, unit: 'Meter', unit_price: 0 }
    ],
});

const addItemRow = () => {
    form.items.push({ description: '', qty: 1, unit: 'Meter', unit_price: 0 });
};

const removeItemRow = (idx) => {
    if (form.items.length > 1) {
        form.items.splice(idx, 1);
    }
};

// Realtime calculations
const calculatedSubtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + ((Number(item.qty) || 0) * (Number(item.unit_price) || 0)), 0);
});

const calculatedPpn = computed(() => {
    const afterDiscount = Math.max(0, calculatedSubtotal.value - (Number(form.discount) || 0));
    const percent = Number(form.tax_ppn_percent) || 0;
    return percent > 0 ? (afterDiscount * percent) / 100 : 0;
});

const calculatedPph = computed(() => {
    const afterDiscount = Math.max(0, calculatedSubtotal.value - (Number(form.discount) || 0));
    const percent = Number(form.tax_pph23_percent) || 0;
    return percent > 0 ? (afterDiscount * percent) / 100 : 0;
});

const calculatedGrandTotal = computed(() => {
    const afterDiscount = Math.max(0, calculatedSubtotal.value - (Number(form.discount) || 0));
    return Math.max(0, afterDiscount + calculatedPpn.value - calculatedPph.value);
});

const openCreateModal = () => {
    isEditMode.value = false;
    editingInvoiceId.value = null;
    form.reset();
    form.issue_date = new Date().toISOString().substring(0, 10);
    form.payment_bank_info = props.defaultBank || 'BANK BRI 410101025028534 a/n Deri Gantinayasa';
    form.items = [{ description: '', qty: 1, unit: 'Meter', unit_price: 0 }];
    showFormModal.value = true;
};

const openEditModal = (inv) => {
    isEditMode.value = true;
    editingInvoiceId.value = inv.id;
    form.invoice_number = inv.invoice_number;
    form.client_name = inv.client_name;
    form.client_pic = inv.client_pic || '';
    form.client_phone = inv.client_phone || '';
    form.client_email = inv.client_email || '';
    form.client_address = inv.client_address || '';
    form.client_npwp = inv.client_npwp || '';
    form.po_spk_number = inv.po_spk_number || '';
    form.project_title = inv.project_title;
    form.project_location = inv.project_location || '';
    form.issue_date = inv.issue_date ? inv.issue_date.substring(0, 10) : '';
    form.due_date = inv.due_date ? inv.due_date.substring(0, 10) : '';
    form.payment_scheme = inv.payment_scheme || 'full';
    form.discount = inv.discount || 0;
    form.tax_ppn_percent = inv.tax_ppn_percent || 0;
    form.tax_pph23_percent = inv.tax_pph23_percent || 0;
    form.payment_bank_info = inv.payment_bank_info || props.defaultBank;
    form.notes = inv.notes || '';
    
    if (inv.items && inv.items.length > 0) {
        form.items = inv.items.map(i => ({
            description: i.description,
            qty: Number(i.qty),
            unit: i.unit,
            unit_price: Number(i.unit_price),
        }));
    } else {
        form.items = [{ description: '', qty: 1, unit: 'Meter', unit_price: 0 }];
    }

    showFormModal.value = true;
};

const submitInvoiceForm = () => {
    if (isEditMode.value) {
        form.put(`/project-invoices/${editingInvoiceId.value}`, {
            onSuccess: () => {
                showFormModal.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Invoice Proyek berhasil diperbarui.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            },
            onError: (err) => {
                const msg = Object.values(err)[0] || 'Gagal menyimpan invoice.';
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg });
            }
        });
    } else {
        form.post('/project-invoices', {
            onSuccess: () => {
                showFormModal.value = false;
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Invoice Proyek baru berhasil diterbitkan.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            },
            onError: (err) => {
                const msg = Object.values(err)[0] || 'Gagal membuat invoice.';
                Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg });
            }
        });
    }
};

// Payment Modal State
const showPaymentModal = ref(false);
const activeInvoice = ref(null);

const paymentForm = useForm({
    paid_amount: 0,
    paid_at: new Date().toISOString().substring(0, 10),
    payment_method: 'transfer',
    payment_notes: '',
    auto_sync_kas: true,
});

const openPaymentModal = (inv) => {
    activeInvoice.value = inv;
    paymentForm.paid_amount = inv.total_amount;
    paymentForm.paid_at = new Date().toISOString().substring(0, 10);
    paymentForm.payment_method = 'transfer';
    paymentForm.payment_notes = '';
    paymentForm.auto_sync_kas = true;
    showPaymentModal.value = true;
};

const submitPayment = () => {
    if (!activeInvoice.value) return;

    paymentForm.post(`/project-invoices/${activeInvoice.value.id}/payment`, {
        onSuccess: () => {
            showPaymentModal.value = false;
            Swal.fire({
                icon: 'success',
                title: 'Pembayaran Dicatat!',
                text: 'Pembayaran proyek berhasil disimpan dan tercatat di Kas Umum.',
                timer: 2500,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            const msg = Object.values(err)[0] || 'Gagal mencatat pembayaran.';
            Swal.fire({ icon: 'error', title: 'Terjadi Kesalahan', text: msg });
        }
    });
};

const confirmDelete = (inv) => {
    Swal.fire({
        title: 'Hapus Invoice Proyek?',
        text: `Apakah Anda yakin ingin menghapus invoice ${inv.invoice_number} untuk ${inv.client_name}? Data kas terkait juga akan disesuaikan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/project-invoices/${inv.id}`, {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Invoice Proyek telah dihapus.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            });
        }
    });
};

// Helpers
const formatCurrency = (val) => {
    const num = Number(val) || 0;
    return 'Rp ' + num.toLocaleString('id-ID');
};

const formatDate = (val) => {
    if (!val) return '-';
    const date = new Date(val);
    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusLabel = (st) => {
    const labels = {
        paid: 'Lunas',
        partial: 'Termin / DP',
        unpaid: 'Belum Lunas',
        draft: 'Draf',
        cancelled: 'Dibatalkan',
    };
    return labels[st] || st;
};

const getStatusBadgeClass = (st) => {
    const classes = {
        paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
        partial: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300',
        unpaid: 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300',
        draft: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        cancelled: 'bg-gray-200 text-gray-500 line-through',
    };
    return classes[st] || 'bg-gray-100 text-gray-800';
};

const getSchemeLabel = (sc) => {
    const schemes = {
        full: 'Penuh (100%)',
        termin_dp: 'Termin DP',
        termin_progress: 'Termin Progres',
        termin_final: 'Pelunasan',
    };
    return schemes[sc] || 'Penuh';
};
</script>

