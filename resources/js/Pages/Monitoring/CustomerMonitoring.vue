<template>
    <AppLayout title="Pantauan Pelanggan" subtitle="Monitoring pelanggan menunggak, janji bayar, dan stop sementara layanan">
        <!-- 4 Top KPI Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Card 1: Sering Menunggak -->
            <div 
                @click="switchTab('menunggak')"
                :class="[
                    'bg-white rounded-2xl p-5 border shadow-sm hover:shadow-md transition-all relative overflow-hidden group cursor-pointer',
                    tab === 'menunggak' ? 'border-rose-400 ring-2 ring-rose-200/70 shadow-rose-50' : 'border-gray-100 hover:border-rose-200'
                ]"
                title="Klik untuk membuka tab Sering Menunggak"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pelanggan Menunggak</span>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-rose-600">{{ stats.total_menunggak_count }}</span>
                    <span class="text-xs font-semibold text-gray-400">Pelanggan</span>
                </div>
                <div class="mt-2 text-xs font-semibold text-gray-600 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Total Nominal:</span>
                    <span class="font-bold text-rose-600">{{ formatRupiah(stats.total_nominal_menunggak) }}</span>
                </div>
            </div>

            <!-- Card 2: Janji Bayar -->
            <div 
                @click="switchTab('janji_bayar')"
                :class="[
                    'bg-white rounded-2xl p-5 border shadow-sm hover:shadow-md transition-all relative overflow-hidden group cursor-pointer',
                    tab === 'janji_bayar' ? 'border-amber-400 ring-2 ring-amber-200/70 shadow-amber-50' : 'border-gray-100 hover:border-amber-200'
                ]"
                title="Klik untuk membuka tab Janji Bayar"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Komitmen Janji Bayar</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-amber-600">{{ stats.total_janji_bayar_count }}</span>
                    <span class="text-xs font-semibold text-gray-400">Invoice</span>
                </div>
                <div class="mt-2 text-[11px] font-medium text-gray-500 flex items-center gap-2 pt-2 border-t border-gray-50">
                    <span class="text-rose-600 font-bold" title="Terlewat / Ingkar Janji">⚠️ {{ stats.janji_terlewat_count }} Terlewat</span>
                    <span>•</span>
                    <span class="text-amber-600 font-bold" title="Jatuh Tempo Hari Ini">🔔 {{ stats.janji_hari_ini_count }} Hari Ini</span>
                    <span>•</span>
                    <span class="text-emerald-600 font-bold" title="Mendatang">📅 {{ stats.janji_mendatang_count }}</span>
                </div>
            </div>

            <!-- Card 3: Stop Sementara (Isolir) -->
            <div 
                @click="switchTab('stop_sementara')"
                :class="[
                    'bg-white rounded-2xl p-5 border shadow-sm hover:shadow-md transition-all relative overflow-hidden group cursor-pointer',
                    tab === 'stop_sementara' ? 'border-orange-400 ring-2 ring-orange-200/70 shadow-orange-50' : 'border-gray-100 hover:border-orange-200'
                ]"
                title="Klik untuk membuka tab Stop Sementara (Isolir)"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Stop Sementara (Isolir)</span>
                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center border border-orange-100 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-orange-600">{{ stats.stop_sementara_count }}</span>
                    <span class="text-xs font-semibold text-gray-400">Pelanggan</span>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Karena Tunggakan:</span>
                    <span class="font-bold text-orange-600">{{ stats.stop_karena_tunggakan_count }} Pelanggan</span>
                </div>
            </div>

            <!-- Card 4: Total Piutang Terancam -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Piutang Terancam</span>
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-indigo-900">{{ formatRupiah(Number(stats.total_nominal_menunggak || 0)) }}</span>
                </div>
                <div class="mt-2 text-xs font-medium text-gray-500 flex items-center justify-between pt-2 border-t border-gray-50">
                    <span>Janji Bayar Aktif:</span>
                    <span class="font-bold text-indigo-600">{{ formatRupiah(Number(stats.total_nominal_janji_bayar || 0)) }}</span>
                </div>
            </div>
        </div>

        <!-- Tab Header Section -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            <div class="flex border-b border-gray-200 px-4 pt-3 overflow-x-auto gap-2">
                <!-- Tab 1: Sering Menunggak -->
                <button
                    @click="switchTab('menunggak')"
                    :class="[
                        'px-4 py-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all whitespace-nowrap',
                        tab === 'menunggak'
                            ? 'border-rose-600 text-rose-600'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Sering Menunggak
                    <span :class="['px-2 py-0.5 rounded-full text-xs font-extrabold', tab === 'menunggak' ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-600']">
                        {{ stats.total_menunggak_count }}
                    </span>
                </button>

                <!-- Tab 2: Janji Bayar -->
                <button
                    @click="switchTab('janji_bayar')"
                    :class="[
                        'px-4 py-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all whitespace-nowrap',
                        tab === 'janji_bayar'
                            ? 'border-amber-600 text-amber-600'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Janji Bayar
                    <span :class="['px-2 py-0.5 rounded-full text-xs font-extrabold', tab === 'janji_bayar' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600']">
                        {{ stats.total_janji_bayar_count }}
                    </span>
                </button>

                <!-- Tab 3: Stop Sementara -->
                <button
                    @click="switchTab('stop_sementara')"
                    :class="[
                        'px-4 py-3 text-sm font-bold flex items-center gap-2 border-b-2 transition-all whitespace-nowrap',
                        tab === 'stop_sementara'
                            ? 'border-orange-600 text-orange-600'
                            : 'border-transparent text-gray-500 hover:text-gray-800'
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Stop Sementara (Isolir)
                    <span :class="['px-2 py-0.5 rounded-full text-xs font-extrabold', tab === 'stop_sementara' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600']">
                        {{ stats.stop_sementara_count }}
                    </span>
                </button>
            </div>

            <!-- Filters Bar -->
            <div class="p-4 bg-slate-50/60 border-b border-gray-100 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3 flex-1">
                    <!-- Search Input -->
                    <div class="relative min-w-[240px] flex-1 sm:flex-initial">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input
                            v-model="localFilters.search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari nama, kode, HP, alamat..."
                            class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-300 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                        />
                    </div>

                    <!-- Area Filter -->
                    <select
                        v-model="localFilters.area_id"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-gray-300 bg-white py-2 px-3 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm cursor-pointer"
                    >
                        <option value="">Semua Area</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>

                    <!-- Contextual Filter Tab 1: Severity -->
                    <select
                        v-if="tab === 'menunggak'"
                        v-model="localFilters.severity"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-gray-300 bg-white py-2 px-3 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 shadow-sm cursor-pointer"
                    >
                        <option value="all">Semua Tunggakan</option>
                        <option value="1_month">Menunggak 1 Bulan</option>
                        <option value="2_months">Menunggak 2 Bulan</option>
                        <option value="3_plus">Menunggak ≥ 3 Bulan (Kritis)</option>
                    </select>

                    <!-- Contextual Filter Tab 2: Promise Status -->
                    <select
                        v-if="tab === 'janji_bayar'"
                        v-model="localFilters.promise_filter"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-gray-300 bg-white py-2 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 shadow-sm cursor-pointer"
                    >
                        <option value="all">Semua Status Janji</option>
                        <option value="overdue">⚠️ Terlewat (Ingkar Janji)</option>
                        <option value="today">🔔 Jatuh Tempo Hari Ini</option>
                        <option value="upcoming">📅 Janji Mendatang</option>
                    </select>

                    <!-- Contextual Filter Tab 3: Stop Reason -->
                    <select
                        v-if="tab === 'stop_sementara'"
                        v-model="localFilters.stop_reason"
                        @change="applyFilters"
                        class="text-xs rounded-xl border border-gray-300 bg-white py-2 px-3 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 shadow-sm cursor-pointer"
                    >
                        <option value="all">Semua Alasan Stop</option>
                        <option value="tunggakan">Karena Nunggak Tagihan</option>
                        <option value="manual">Cuti / Stop Manual</option>
                    </select>

                    <!-- Tombol Terapkan & Reset -->
                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                    </button>
                    <button
                        v-if="hasActiveFilter"
                        @click="resetFilters"
                        class="px-3 py-2 text-gray-500 hover:text-gray-800 text-xs font-medium transition-colors"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- TABLE CONTENT -->
            <div class="overflow-x-auto">
                <!-- ================= TAB 1: SERING MENUNGGAK ================= -->
                <table v-if="tab === 'menunggak'" class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50/75 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 sm:pl-6">Pelanggan</th>
                            <th class="px-3 py-3.5">Kontak / WA</th>
                            <th class="px-3 py-3.5">Paket & Area</th>
                            <th class="px-3 py-3.5 text-center">Tingkat Tunggakan</th>
                            <th class="px-3 py-3.5">Total Piutang</th>
                            <th class="px-3 py-3.5">Jatuh Tempo Tertua</th>
                            <th class="px-3 py-3.5">Status Layanan</th>
                            <th class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="row in items.data" :key="row.id" class="hover:bg-slate-50/70 transition-colors">
                            <!-- Pelanggan -->
                            <td class="py-3.5 pl-4 pr-3 sm:pl-6">
                                <div class="font-bold text-gray-900">{{ row.name }}</div>
                                <div class="text-xs font-mono text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded text-[11px] font-semibold text-gray-700">{{ row.customer_code }}</span>
                                </div>
                                <div class="text-[11px] text-gray-400 truncate max-w-[200px] mt-0.5" :title="row.address">{{ row.address || '-' }}</div>
                            </td>

                            <!-- Kontak / WA -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs font-medium">
                                <div v-if="row.phone" class="flex items-center gap-1.5">
                                    <span class="text-gray-700">{{ row.phone }}</span>
                                    <button
                                        @click="openWhatsAppReminder(row, 'menunggak')"
                                        class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                        title="Kirim Pesan Penagihan WhatsApp"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                    </button>
                                </div>
                                <span v-else class="text-gray-400 italic">Tidak ada nomor</span>
                            </td>

                            <!-- Paket & Area -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <div class="font-bold text-gray-800">{{ row.package ? row.package.name : '-' }}</div>
                                <div class="text-[11px] text-gray-500">{{ row.area_model ? row.area_model.name : '-' }}</div>
                            </td>

                            <!-- Tingkat Tunggakan -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-center">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-black shadow-sm',
                                        row.unpaid_count >= 3
                                            ? 'bg-rose-100 text-rose-700 border border-rose-300 animate-pulse'
                                            : row.unpaid_count == 2
                                            ? 'bg-orange-100 text-orange-700 border border-orange-200'
                                            : 'bg-amber-50 text-amber-700 border border-amber-200'
                                    ]"
                                >
                                    {{ row.unpaid_count }} Bulan Nunggak
                                </span>
                            </td>

                            <!-- Total Piutang -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs font-black text-rose-600">
                                {{ formatRupiah(row.total_unpaid_amount) }}
                            </td>

                            <!-- Jatuh Tempo Tertua -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <div v-if="row.invoices && row.invoices.length > 0">
                                    <span class="font-bold text-gray-800">{{ formatDate(row.invoices[0].due_date) }}</span>
                                    <div class="text-[10px] text-rose-600 font-semibold mt-0.5">
                                        Telat {{ getDaysOverdue(row.invoices[0].due_date) }} hari
                                    </div>
                                </div>
                                <span v-else class="text-gray-400">-</span>
                            </td>

                            <!-- Status Layanan -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <span v-if="row.status === 'active'" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                                <span v-else-if="row.status === 'suspended'" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                    Isolir
                                </span>
                                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">
                                    {{ row.status }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="whitespace-nowrap py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Atur Janji Bayar -->
                                    <button
                                        @click="openPromiseModal(row)"
                                        class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold rounded-lg border border-amber-200 text-xs transition-colors flex items-center gap-1"
                                        title="Catat Janji Bayar"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Janji
                                    </button>

                                    <!-- Tombol Isolir / Stop Sementara -->
                                    <button
                                        v-if="row.status === 'active'"
                                        @click="confirmSuspend(row)"
                                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg border border-rose-200 text-xs transition-colors flex items-center gap-1"
                                        title="Isolir Layanan Sekarang"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Isolir
                                    </button>

                                    <!-- Buka Isolir jika sedang suspended -->
                                    <button
                                        v-else-if="row.status === 'suspended'"
                                        @click="confirmUnsuspend(row)"
                                        class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg border border-emerald-200 text-xs transition-colors flex items-center gap-1"
                                        title="Buka Isolir / Aktifkan Layanan"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Buka Isolir
                                    </button>

                                    <!-- Link ke Invoice -->
                                    <Link
                                        :href="`/invoices?search=${encodeURIComponent(row.customer_code)}`"
                                        class="p-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg border border-gray-200 transition-colors"
                                        title="Lihat Rincian Tagihan"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!items.data || items.data.length === 0">
                            <td colspan="8" class="text-center py-12 text-gray-500">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-sm font-bold text-gray-800">Tidak ada pelanggan menunggak!</p>
                                <p class="text-xs text-gray-400 mt-0.5">Semua pelanggan tertib membayar tagihan tepat waktu.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- ================= TAB 2: JANJI BAYAR ================= -->
                <table v-else-if="tab === 'janji_bayar'" class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50/75 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 sm:pl-6">Pelanggan</th>
                            <th class="px-3 py-3.5">Invoice & Paket</th>
                            <th class="px-3 py-3.5">Nominal Tagihan</th>
                            <th class="px-3 py-3.5">Tanggal Janji Bayar</th>
                            <th class="px-3 py-3.5">Status Janji</th>
                            <th class="px-3 py-3.5">Status Internet</th>
                            <th class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="inv in items.data" :key="inv.id" class="hover:bg-slate-50/70 transition-colors">
                            <!-- Pelanggan -->
                            <td class="py-3.5 pl-4 pr-3 sm:pl-6">
                                <div class="font-bold text-gray-900">{{ inv.customer?.name || '-' }}</div>
                                <div class="text-xs font-mono text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded text-[11px] font-semibold text-gray-700">{{ inv.customer?.customer_code || '-' }}</span>
                                    <span v-if="inv.customer?.phone" class="text-gray-500">{{ inv.customer?.phone }}</span>
                                </div>
                                <div class="text-[11px] text-gray-400 truncate max-w-[200px] mt-0.5" :title="inv.customer?.address">{{ inv.customer?.address || '-' }}</div>
                            </td>

                            <!-- Invoice & Paket -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <div class="font-bold font-mono text-gray-800">{{ inv.invoice_number }}</div>
                                <div class="text-[11px] text-gray-500">
                                    {{ inv.customer?.package?.name || 'Paket Reguler' }} • Periode {{ inv.period_month }}/{{ inv.period_year }}
                                </div>
                            </td>

                            <!-- Nominal Tagihan -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs font-black text-gray-900">
                                {{ formatRupiah(inv.amount) }}
                            </td>

                            <!-- Tanggal Janji Bayar -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <div class="font-bold text-gray-800">{{ formatDate(inv.promise_date) }}</div>
                                <div v-if="inv.notes" class="text-[11px] text-gray-500 italic truncate max-w-[150px] mt-0.5" :title="inv.notes">
                                    "{{ inv.notes }}"
                                </div>
                            </td>

                            <!-- Status Janji -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <span
                                    v-if="isPromiseOverdue(inv.promise_date)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-700 border border-rose-300 animate-pulse"
                                >
                                    ⚠️ Terlewat (Ingkar)
                                </span>
                                <span
                                    v-else-if="isPromiseToday(inv.promise_date)"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800 border border-amber-300"
                                >
                                    🔔 Jatuh Tempo Hari Ini
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                >
                                    📅 Mendatang ({{ getPromiseCountdown(inv.promise_date) }})
                                </span>
                            </td>

                            <!-- Status Internet -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <span v-if="inv.customer?.status === 'active'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-orange-50 text-orange-700 border border-orange-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span>
                                    Isolir
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="whitespace-nowrap py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- WhatsApp Follow Up -->
                                    <button
                                        v-if="inv.customer?.phone"
                                        @click="openWhatsAppReminder(inv, 'janji_bayar')"
                                        class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                        title="Chat Pengingat Janji Bayar"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                    </button>

                                    <!-- Ubah / Perbarui Janji -->
                                    <button
                                        @click="openPromiseModalForInvoice(inv)"
                                        class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold rounded-lg border border-amber-200 text-xs transition-colors"
                                        title="Ubah Tanggal Janji Bayar"
                                    >
                                        Ubah Janji
                                    </button>

                                    <!-- Tombol Isolir jika ingkar -->
                                    <button
                                        v-if="inv.customer?.status === 'active' && isPromiseOverdue(inv.promise_date)"
                                        @click="confirmSuspend(inv.customer)"
                                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg border border-rose-200 text-xs transition-colors"
                                        title="Isolir Karena Ingkar Janji"
                                    >
                                        Isolir
                                    </button>

                                    <!-- Link Invoice -->
                                    <Link
                                        :href="`/invoices?search=${encodeURIComponent(inv.invoice_number)}`"
                                        class="p-1.5 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg border border-gray-200 transition-colors"
                                        title="Buka Invoice"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!items.data || items.data.length === 0">
                            <td colspan="7" class="text-center py-12 text-gray-500">
                                <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 mx-auto flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <p class="text-sm font-bold text-gray-800">Tidak ada data janji bayar</p>
                                <p class="text-xs text-gray-400 mt-0.5">Janji bayar yang diatur pada invoice akan muncul di tab ini.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- ================= TAB 3: STOP SEMENTARA ================= -->
                <table v-else-if="tab === 'stop_sementara'" class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50/75 text-xs font-bold uppercase tracking-wider text-gray-500">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 sm:pl-6">Pelanggan</th>
                            <th class="px-3 py-3.5">Kontak / WA</th>
                            <th class="px-3 py-3.5">Paket & Area</th>
                            <th class="px-3 py-3.5">Periode Stop</th>
                            <th class="px-3 py-3.5">Alasan / Status Tunggakan</th>
                            <th class="py-3.5 pl-3 pr-4 sm:pr-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <tr v-for="cust in items.data" :key="cust.id" class="hover:bg-slate-50/70 transition-colors">
                            <!-- Pelanggan -->
                            <td class="py-3.5 pl-4 pr-3 sm:pl-6">
                                <div class="font-bold text-gray-900">{{ cust.name }}</div>
                                <div class="text-xs font-mono text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <span class="bg-gray-100 px-1.5 py-0.5 rounded text-[11px] font-semibold text-gray-700">{{ cust.customer_code }}</span>
                                </div>
                                <div class="text-[11px] text-gray-400 truncate max-w-[200px] mt-0.5" :title="cust.address">{{ cust.address || '-' }}</div>
                            </td>

                            <!-- Kontak / WA -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs font-medium">
                                <div v-if="cust.phone" class="flex items-center gap-1.5">
                                    <span class="text-gray-700">{{ cust.phone }}</span>
                                    <button
                                        @click="openWhatsAppReminder(cust, 'stop_sementara')"
                                        class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"
                                        title="Chat WhatsApp Reaktivasi"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                    </button>
                                </div>
                                <span v-else class="text-gray-400 italic">Tidak ada nomor</span>
                            </td>

                            <!-- Paket & Area -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <div class="font-bold text-gray-800">{{ cust.package ? cust.package.name : '-' }}</div>
                                <div class="text-[11px] text-gray-500">{{ cust.area_model ? cust.area_model.name : '-' }}</div>
                            </td>

                            <!-- Periode Stop -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <div v-if="cust.suspend_start">
                                    <span class="font-bold text-gray-800">{{ formatDate(cust.suspend_start) }}</span>
                                    <span v-if="cust.suspend_end" class="text-gray-500"> s/d {{ formatDate(cust.suspend_end) }}</span>
                                </div>
                                <div v-else class="text-gray-500 font-medium">
                                    Sejak {{ formatDate(cust.updated_at) }}
                                </div>
                            </td>

                            <!-- Alasan / Status Tunggakan -->
                            <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                <div v-if="cust.unpaid_count > 0" class="flex flex-col gap-0.5">
                                    <span class="inline-flex items-center gap-1 font-bold text-rose-600">
                                        ⚠️ Isolir Tagihan ({{ cust.unpaid_count }} invoice)
                                    </span>
                                    <span class="text-[11px] text-gray-600">
                                        Total: <strong class="text-rose-600">{{ formatRupiah(cust.total_unpaid_amount) }}</strong>
                                    </span>
                                </div>
                                <div v-else class="text-blue-700 font-semibold bg-blue-50 px-2 py-0.5 rounded inline-block">
                                    Cuti / Permintaan Manual
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="whitespace-nowrap py-3.5 pl-3 pr-4 sm:pr-6 text-right text-xs">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Buka Isolir / Aktifkan Kembali -->
                                    <button
                                        @click="confirmUnsuspend(cust)"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-sm text-xs transition-all flex items-center gap-1.5"
                                        title="Buka Isolir & Aktifkan Kembali Koneksi Internet"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Buka Isolir
                                    </button>

                                    <!-- Cabut Perangkat / CBP -->
                                    <Link
                                        :href="`/cbp?customer_id=${cust.id}`"
                                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl border border-rose-200 text-xs transition-colors flex items-center gap-1"
                                        title="Buat Tiket Cabut Perangkat (CBP)"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        CBP
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="!items.data || items.data.length === 0">
                            <td colspan="6" class="text-center py-12 text-gray-500">
                                <div class="w-12 h-12 rounded-full bg-orange-50 text-orange-600 mx-auto flex items-center justify-center mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-sm font-bold text-gray-800">Tidak ada pelanggan stop sementara</p>
                                <p class="text-xs text-gray-400 mt-0.5">Semua layanan internet pelanggan berjalan aktif normal.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div v-if="items.links && items.links.length > 3" class="px-5 py-4 border-t border-gray-100 bg-white flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs text-gray-500">
                    Menampilkan <span class="font-bold text-gray-800">{{ items.from || 0 }}</span> - <span class="font-bold text-gray-800">{{ items.to || 0 }}</span> dari <span class="font-bold text-gray-800">{{ items.total }}</span> data
                </div>
                <div class="flex flex-wrap gap-1 justify-center sm:justify-end">
                    <template v-for="(link, p) in items.links" :key="p">
                        <div v-if="link.url === null" class="px-3 py-1.5 text-xs font-medium text-gray-300 border border-gray-100 rounded-lg bg-gray-50" v-html="link.label" />
                        <Link v-else :href="link.url" class="px-3 py-1.5 text-xs font-semibold border rounded-lg hover:bg-indigo-50 focus:border-indigo-500 focus:text-indigo-600 transition-colors shadow-sm" :class="{ 'bg-indigo-600 border-indigo-600 text-white hover:bg-indigo-700': link.active, 'border-gray-200 text-gray-600 bg-white': !link.active }" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>

        <!-- ================= MODAL JANJI BAYAR ================= -->
        <Teleport to="body">
            <div v-if="showPromiseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" @click="closePromiseModal"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden p-6 border border-gray-100 animate-fade-in-up">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Atur Janji Bayar</h3>
                                <p class="text-xs text-gray-500 font-medium">{{ selectedCustomer?.name }} ({{ selectedCustomer?.customer_code }})</p>
                            </div>
                        </div>
                        <button @click="closePromiseModal" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitPromise" class="space-y-4">
                        <!-- Invoice Selection (jika dari customer dengan multi-invoice) -->
                        <div v-if="customerInvoices.length > 1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Pilih Tagihan</label>
                            <select v-model="promiseForm.invoice_id" class="w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 p-2.5 font-semibold focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option v-for="inv in customerInvoices" :key="inv.id" :value="inv.id">
                                    {{ inv.invoice_number }} — Rp {{ Number(inv.amount).toLocaleString('id-ID') }} (Due: {{ formatDate(inv.due_date) }})
                                </option>
                            </select>
                        </div>

                        <!-- Tanggal Janji Bayar -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Tanggal Janji Bayar</label>
                            <input
                                v-model="promiseForm.promise_date"
                                type="date"
                                :min="todayDate"
                                required
                                class="w-full text-sm font-semibold rounded-xl border-gray-300 bg-gray-50/50 p-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Batas waktu yang dijanjikan pelanggan untuk melunasi tagihan.</p>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Catatan / Alasan (Opsional)</label>
                            <textarea
                                v-model="promiseForm.notes"
                                rows="2"
                                placeholder="Contoh: Menunggu gajian tanggal 15, janji via telepon..."
                                class="w-full text-xs rounded-xl border-gray-300 bg-gray-50/50 p-2.5 focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                            ></textarea>
                        </div>

                        <!-- Checkbox Auto Unsuspend -->
                        <div class="flex items-center gap-2 pt-1">
                            <input
                                v-model="promiseForm.auto_unsuspend"
                                id="auto_unsuspend"
                                type="checkbox"
                                class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                            />
                            <label for="auto_unsuspend" class="text-xs text-gray-700 font-medium cursor-pointer">
                                Buka isolir otomatis agar internet aktif sampai tanggal janji
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                            <button
                                type="button"
                                @click="closePromiseModal"
                                class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="promiseForm.processing || !promiseForm.promise_date"
                                class="px-5 py-2 text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white rounded-xl shadow-sm transition-all disabled:opacity-50"
                            >
                                {{ promiseForm.processing ? 'Menyimpan...' : 'Simpan Janji Bayar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tab: { type: String, default: 'menunggak' },
    items: Object,
    stats: Object,
    areas: Array,
    company: Object,
    filters: Object,
});

const todayDate = new Date().toISOString().split('T')[0];

const localFilters = reactive({
    search: props.filters?.search || '',
    area_id: props.filters?.area_id || '',
    severity: props.filters?.severity || 'all',
    promise_filter: props.filters?.promise_filter || 'all',
    stop_reason: props.filters?.stop_reason || 'all',
});

const hasActiveFilter = computed(() => {
    return localFilters.search || localFilters.area_id || localFilters.severity !== 'all' || localFilters.promise_filter !== 'all' || localFilters.stop_reason !== 'all';
});

const switchTab = (newTab) => {
    router.get(route('monitoring-pelanggan.index'), {
        tab: newTab,
        search: localFilters.search,
        area_id: localFilters.area_id,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const applyFilters = () => {
    router.get(route('monitoring-pelanggan.index'), {
        tab: props.tab,
        search: localFilters.search,
        area_id: localFilters.area_id,
        severity: localFilters.severity,
        promise_filter: localFilters.promise_filter,
        stop_reason: localFilters.stop_reason,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.search = '';
    localFilters.area_id = '';
    localFilters.severity = 'all';
    localFilters.promise_filter = 'all';
    localFilters.stop_reason = 'all';
    applyFilters();
};

// ================= MODAL JANJI BAYAR =================
const showPromiseModal = ref(false);
const selectedCustomer = ref(null);
const customerInvoices = ref([]);

const promiseForm = useForm({
    invoice_id: null,
    promise_date: '',
    notes: '',
    auto_unsuspend: true,
});

const openPromiseModal = (customer) => {
    selectedCustomer.value = customer;
    customerInvoices.value = customer.invoices || [];
    
    const targetInvoice = customerInvoices.value[0];
    promiseForm.invoice_id = targetInvoice ? targetInvoice.id : null;
    promiseForm.promise_date = targetInvoice?.promise_date || '';
    promiseForm.notes = targetInvoice?.notes || '';
    promiseForm.auto_unsuspend = true;

    showPromiseModal.value = true;
};

const openPromiseModalForInvoice = (invoice) => {
    selectedCustomer.value = invoice.customer;
    customerInvoices.value = [invoice];
    promiseForm.invoice_id = invoice.id;
    promiseForm.promise_date = invoice.promise_date || '';
    promiseForm.notes = invoice.notes || '';
    promiseForm.auto_unsuspend = true;

    showPromiseModal.value = true;
};

const closePromiseModal = () => {
    showPromiseModal.value = false;
    selectedCustomer.value = null;
    customerInvoices.value = [];
};

const submitPromise = () => {
    if (!promiseForm.invoice_id) {
        Swal.fire('Error', 'Invoice tidak ditemukan.', 'error');
        return;
    }

    promiseForm.post(route('monitoring-pelanggan.set-promise', { invoice: promiseForm.invoice_id }), {
        preserveScroll: true,
        onSuccess: () => {
            closePromiseModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: 'Janji bayar berhasil disimpan.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
        },
    });
};

// ================= AKSI ISOLIR & BUKA ISOLIR =================
const confirmSuspend = (customer) => {
    Swal.fire({
        title: 'Stop Sementara Layanan?',
        html: `Apakah Anda yakin ingin mengisolir layanan pelanggan <b>${customer.name}</b> (${customer.customer_code})? Koneksi internet akan dinonaktifkan otomatis di RADIUS.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Isolir Sekarang',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('monitoring-pelanggan.suspend', { customer: customer.id }), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: `Pelanggan ${customer.name} berhasil di-Stop Sementara.`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};

const confirmUnsuspend = (customer) => {
    Swal.fire({
        title: 'Buka Isolir Pelanggan?',
        html: `Apakah Anda ingin mengaktifkan kembali internet <b>${customer.name}</b> (${customer.customer_code})? Akun RADIUS akan disinkronkan kembali ke paket reguler.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Aktifkan Kembali',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.post(route('monitoring-pelanggan.unsuspend', { customer: customer.id }), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: `Layanan internet ${customer.name} berhasil diaktifkan kembali.`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                    });
                }
            });
        }
    });
};

// ================= WHATSAPP MESSAGING =================
const openWhatsAppReminder = (row, type) => {
    const customer = type === 'janji_bayar' ? row.customer : row;
    if (!customer || !customer.phone) {
        Swal.fire('Info', 'Nomor telepon pelanggan tidak tersedia.', 'info');
        return;
    }

    let phone = customer.phone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    } else if (phone.startsWith('8')) {
        phone = '62' + phone;
    }

    const companyName = props.company?.name || 'Layanan Internet';
    let text = '';

    if (type === 'menunggak') {
        const total = formatRupiah(row.total_unpaid_amount);
        text = `Halo Bpk/Ibu *${customer.name}* (${customer.customer_code}),\n\nKami dari *${companyName}* menginformasikan bahwa tagihan internet Anda terdapat tunggakan sebanyak *${row.unpaid_count} bulan* dengan total sebesar *${total}*.\n\nMohon untuk segera melakukan pembayaran agar layanan internet tetap aktif dan tidak mengalami isolir otomatis.\n\nTerima kasih atas kerja samanya. 🙏`;
    } else if (type === 'janji_bayar') {
        const promiseDateStr = formatDate(row.promise_date);
        const amountStr = formatRupiah(row.amount);
        text = `Halo Bpk/Ibu *${customer.name}* (${customer.customer_code}),\n\nKami dari *${companyName}* ingin mengonfirmasi terkait komitmen janji bayar tagihan internet Anda sebesar *${amountStr}* pada tanggal *${promiseDateStr}*.\n\nMohon konfirmasi jika pembayaran telah dilakukan. Terima kasih. 🙏`;
    } else if (type === 'stop_sementara') {
        text = `Halo Bpk/Ibu *${customer.name}* (${customer.customer_code}),\n\nKami dari *${companyName}* menginformasikan bahwa saat ini layanan internet Anda berstatus *Stop Sementara (Isolir)*.\n\nUntuk mengaktifkan kembali layanan internet Anda, silakan hubungi kami atau selesaikan administrasi tagihan. Terima kasih. 🙏`;
    }

    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const url = isMobile
        ? `https://wa.me/${phone}?text=${encodeURIComponent(text)}`
        : `https://web.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`;

    window.open(url, '_blank');
};

// ================= UTILITIES & HELPERS =================
const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

const formatDate = (val) => {
    if (!val) return '-';
    return new Date(val).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};

const getDaysOverdue = (dueDateStr) => {
    if (!dueDateStr) return 0;
    const due = new Date(dueDateStr);
    const now = new Date();
    const diffTime = now - due;
    return Math.max(0, Math.floor(diffTime / (1000 * 60 * 60 * 24)));
};

const isPromiseOverdue = (promiseDateStr) => {
    if (!promiseDateStr) return false;
    const promise = new Date(promiseDateStr).setHours(0,0,0,0);
    const today = new Date().setHours(0,0,0,0);
    return promise < today;
};

const isPromiseToday = (promiseDateStr) => {
    if (!promiseDateStr) return false;
    const promise = new Date(promiseDateStr).setHours(0,0,0,0);
    const today = new Date().setHours(0,0,0,0);
    return promise === today;
};

const getPromiseCountdown = (promiseDateStr) => {
    if (!promiseDateStr) return '';
    const promise = new Date(promiseDateStr).setHours(0,0,0,0);
    const today = new Date().setHours(0,0,0,0);
    const diff = Math.ceil((promise - today) / (1000 * 60 * 60 * 24));
    return `H-${diff}`;
};
</script>
