<template>
    <AppLayout title="Data Karyawan" subtitle="Kelola data personalia, masa kerja, dan status karyawan perusahaan">
        <div class="space-y-6">
            <!-- Scope Badge Alert jika scope dibatasi -->
            <div v-if="scopeLevel === 'own'" class="flex items-center justify-between p-3.5 bg-blue-50 border border-blue-200 rounded-2xl text-blue-800 text-xs shadow-xs animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="text-base">🔒</span>
                    <div>
                        <span class="font-bold">Mode Scope: Biodata Diri Sendiri (Restricted)</span>
                        <p class="text-blue-600 text-[11px] mt-0.5">Sesuai hak akses role Anda, Anda hanya dapat melihat dan mengakses biodata profil Anda sendiri.</p>
                    </div>
                </div>
            </div>
            <div v-else-if="scopeLevel === 'area'" class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs shadow-xs animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="text-base">🏢</span>
                    <div>
                        <span class="font-bold">Mode Scope: Karyawan Per Area Cabang</span>
                        <p class="text-emerald-600 text-[11px] mt-0.5">Menampilkan seluruh data karyawan yang ditempatkan pada wilayah kerja/cabang operasional Anda.</p>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Total Karyawan -->
                <div 
                    @click="setStatusFilter('all')" 
                    class="glass-card p-5 border-2 transition-all cursor-pointer group"
                    :class="statusFilter === 'all' ? 'border-blue-500 bg-blue-50/30 shadow-md ring-2 ring-blue-500/20' : 'border-blue-500/10 hover:border-blue-500/30'"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-xl group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Karyawan</p>
                            <p class="text-2xl font-bold text-gray-900">{{ counts?.all ?? employees.total ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Karyawan Aktif -->
                <div 
                    @click="setStatusFilter('active')" 
                    class="glass-card p-5 border-2 transition-all cursor-pointer group"
                    :class="statusFilter === 'active' ? 'border-emerald-500 bg-emerald-50/30 shadow-md ring-2 ring-emerald-500/20' : 'border-emerald-500/10 hover:border-emerald-500/30'"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Karyawan Aktif</p>
                            <p class="text-2xl font-bold text-emerald-700">{{ counts?.active ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- 3. Karyawan Resign / Nonaktif -->
                <div 
                    @click="setStatusFilter('resigned')" 
                    class="glass-card p-5 border-2 transition-all cursor-pointer group"
                    :class="statusFilter === 'resigned' ? 'border-rose-500 bg-rose-50/30 shadow-md ring-2 ring-rose-500/20' : 'border-rose-500/10 hover:border-rose-500/30'"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-rose-100 text-rose-600 rounded-xl group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Resign / Nonaktif</p>
                            <p class="text-2xl font-bold text-rose-700">{{ counts?.resigned ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- 4. Rata-Rata Lama Bekerja -->
                <div class="glass-card p-5 border-2 border-indigo-500/10 hover:border-indigo-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-indigo-100 text-indigo-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Rata-Rata Masa Kerja</p>
                            <p class="text-2xl font-bold text-indigo-900">{{ avg_tenure || '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Tabs: Aktif, Resign, Semua -->
            <div class="flex items-center gap-2 p-1.5 bg-gray-100/90 rounded-2xl w-fit border border-gray-200 shadow-xs">
                <button 
                    type="button" 
                    @click="setStatusFilter('active')"
                    :class="statusFilter === 'active' ? 'bg-white text-emerald-800 font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all cursor-pointer"
                >
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs"></span>
                    <span>Karyawan Aktif</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold" :class="statusFilter === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700'">
                        {{ counts?.active ?? 0 }}
                    </span>
                </button>

                <button 
                    type="button" 
                    @click="setStatusFilter('resigned')"
                    :class="statusFilter === 'resigned' ? 'bg-white text-rose-800 font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all cursor-pointer"
                >
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-xs"></span>
                    <span>Resign / Nonaktif</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold" :class="statusFilter === 'resigned' ? 'bg-rose-100 text-rose-800' : 'bg-gray-200 text-gray-700'">
                        {{ counts?.resigned ?? 0 }}
                    </span>
                </button>

                <button 
                    type="button" 
                    @click="setStatusFilter('all')"
                    :class="statusFilter === 'all' ? 'bg-white text-blue-800 font-bold shadow-xs' : 'text-gray-600 hover:text-gray-900 font-medium'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm flex items-center gap-2 transition-all cursor-pointer"
                >
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-xs"></span>
                    <span>Semua Status</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold" :class="statusFilter === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-200 text-gray-700'">
                        {{ counts?.all ?? 0 }}
                    </span>
                </button>
            </div>

            <!-- Filter Bar & Search -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[240px]">
                        <input 
                            type="text" 
                            v-model="search" 
                            @input="debouncedApplyFilters"
                            @keyup.enter="applyFiltersNow"
                            placeholder="Cari nama, posisi, email, telepon, No IAK..." 
                            class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm bg-white font-medium text-gray-800 placeholder-gray-400"
                        >
                        <button 
                            type="button"
                            @click="applyFiltersNow"
                            class="absolute left-3 top-2.5 text-gray-400 hover:text-blue-600 transition-colors cursor-pointer"
                            title="Cari"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                        <button 
                            v-if="search" 
                            @click="clearSearch" 
                            type="button" 
                            class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer p-0.5 rounded-full hover:bg-gray-100"
                            title="Hapus pencarian"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Action: Hapus Terpilih (muncul jika ada yang dicentang) -->
                        <button 
                            v-if="selectedIds.length > 0 && can(['hr_employees_delete_all', 'hr_employees_delete'])"
                            @click="confirmDeleteSelected"
                            type="button"
                            class="inline-flex items-center justify-center gap-1.5 py-2.5 px-3.5 rounded-xl border border-rose-300 bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-sm transition-all shadow-xs animate-fade-in"
                        >
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus Terpilih ({{ selectedIds.length }})</span>
                        </button>

                        <!-- Action: Hapus Semua -->
                        <button 
                            v-if="scopeLevel !== 'own' && can(['hr_employees_delete_all', 'hr_employees_delete'])"
                            @click="openDeleteAllModal"
                            type="button"
                            class="inline-flex items-center justify-center gap-1.5 py-2.5 px-3.5 rounded-xl border border-red-200 bg-red-50/70 text-red-700 hover:bg-red-100/90 font-semibold text-sm transition-all shadow-xs"
                            title="Hapus Semua Data Karyawan"
                        >
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus Semua</span>
                        </button>

                        <!-- Action: Migrasi Excel/CSV -->
                        <Link 
                            v-if="can('menu_data_migration')"
                            href="/settings/data-migration?category=employees" 
                            class="inline-flex items-center justify-center gap-2 py-2.5 px-3.5 rounded-xl border border-indigo-200 bg-indigo-50/70 text-indigo-700 hover:bg-indigo-100/80 font-semibold text-sm transition-all shadow-xs"
                            title="Migrasi Data Karyawan Massal dari Excel / CSV"
                        >
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span>Migrasi Excel/CSV</span>
                        </Link>

                        <!-- Action: Tambah Karyawan -->
                        <button v-if="can('hr_employees_create')" @click="openModal()" class="btn-primary shrink-0 flex items-center justify-center gap-2 py-2.5 px-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            <span>Tambah Karyawan</span>
                        </button>
                    </div>
                </div>

                <!-- Sub-filters: Masa Kerja, Tipe Pegawai, Area, Urutan -->
                <div class="flex flex-wrap items-center gap-2.5 pt-2 border-t border-gray-100 text-xs">
                    <!-- Filter Lama Bekerja (Tenure) -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 font-semibold uppercase text-[10px] tracking-wider">⏱️ Masa Kerja:</span>
                        <select 
                            v-model="filterTenure" 
                            @change="applyFiltersNow" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium text-gray-800"
                        >
                            <option value="">Semua Masa Kerja</option>
                            <option value="senior">⭐ Senior (> 3 Tahun)</option>
                            <option value="middle">🔷 Menengah (1 - 3 Tahun)</option>
                            <option value="junior">🔹 Junior (3 - 12 Bulan)</option>
                            <option value="new">🌱 Baru (< 3 Bulan)</option>
                            <option value="unset">⚪ Belum Diset</option>
                        </select>
                    </div>

                    <!-- Filter Tipe Pegawai -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Tipe:</span>
                        <select 
                            v-model="filterType" 
                            @change="applyFiltersNow" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium text-gray-800"
                        >
                            <option value="">Semua Tipe</option>
                            <option value="Tetap">Tetap</option>
                            <option value="Kontrak">Kontrak</option>
                            <option value="Freelance">Freelance</option>
                            <option value="Magang">Magang</option>
                        </select>
                    </div>

                    <!-- Filter Area -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Area:</span>
                        <select 
                            v-model="filterArea" 
                            @change="applyFiltersNow" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium text-gray-800"
                        >
                            <option value="">Semua Area</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <!-- Sorting -->
                    <div class="flex items-center gap-1.5 ml-auto">
                        <span class="text-gray-500 font-semibold uppercase text-[10px] tracking-wider">Urutkan:</span>
                        <select 
                            v-model="sortBy" 
                            @change="applyFiltersNow" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium text-gray-800"
                        >
                            <option value="">Terbaru Ditambahkan</option>
                            <option value="senior">⏱️ Paling Lama Bekerja (Senior)</option>
                            <option value="newest_joined">🌱 Paling Baru Bergabung</option>
                            <option value="name_asc">🔤 Nama A - Z</option>
                        </select>
                    </div>

                    <!-- Reset Filter Button -->
                    <button 
                        v-if="hasActiveFilter" 
                        type="button" 
                        @click="resetFilters" 
                        class="px-2.5 py-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded-lg text-xs font-semibold transition-colors flex items-center gap-1"
                    >
                        <span>&times; Reset Filter</span>
                    </button>
                </div>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-4 w-12 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        :indeterminate.prop="isPartiallySelected"
                                        @change="toggleSelectAll" 
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer"
                                        title="Pilih Semua di Halaman Ini"
                                    />
                                </th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Karyawan</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Posisi</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kontak</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-700 uppercase tracking-wider bg-slate-100/50">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Lama Bekerja</span>
                                    </div>
                                </th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Area</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Gaji Pokok</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="emp in employees.data" :key="emp.id" :class="{'bg-rose-50/40': selectedIds.includes(emp.id)}" class="hover:bg-gray-50/50 transition-colors group">
                                <!-- Checkbox Selection -->
                                <td class="py-4 px-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :value="emp.id" 
                                        v-model="selectedIds" 
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer"
                                    />
                                </td>
                                <!-- 1. Karyawan Info -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm overflow-hidden shrink-0 shadow-sm">
                                            <img v-if="emp.photo" :src="`/storage/${emp.photo}`" class="w-full h-full object-cover" alt="">
                                            <span v-else>{{ emp.name?.charAt(0)?.toUpperCase() }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ emp.name }}</p>
                                            <p v-if="emp.iak_number" class="text-xs text-gray-400 mt-0.5">IAK: {{ emp.iak_number }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- 2. Posisi -->
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ emp.position || '-' }}
                                    </span>
                                </td>

                                <!-- 3. Kontak -->
                                <td class="py-4 px-6">
                                    <p v-if="emp.phone" class="text-sm text-gray-700 font-medium">{{ emp.phone }}</p>
                                    <p v-if="emp.email" class="text-xs text-gray-400 mt-0.5">{{ emp.email }}</p>
                                    <p v-if="!emp.phone && !emp.email" class="text-xs text-gray-300">-</p>
                                </td>

                                <!-- 4. Status Tipe & Status Kerja (Aktif / Resign) -->
                                <td class="py-4 px-6">
                                    <div class="space-y-1.5">
                                        <!-- Status Resign vs Aktif Badge -->
                                        <div v-if="emp.status === 'resigned'">
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1 shadow-xs">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                <span>Resign</span>
                                            </span>
                                            <div v-if="emp.resign_date" class="text-[11px] text-rose-600 font-medium mt-0.5">
                                                Tgl: {{ formatDate(emp.resign_date) }}
                                            </div>
                                            <div v-if="emp.resign_reason" class="text-[10px] text-gray-500 truncate max-w-[140px]" :title="emp.resign_reason">
                                                {{ emp.resign_reason }}
                                            </div>
                                        </div>
                                        <div v-else>
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>Aktif</span>
                                            </span>
                                        </div>

                                        <!-- Tipe Pegawai -->
                                        <div>
                                            <span :class="employeeTypeBadge(emp.employee_type)" class="px-2 py-0.5 rounded-md text-[10px] font-medium border inline-block">
                                                {{ emp.employee_type || '-' }}
                                            </span>
                                        </div>

                                        <!-- Bank / Payment -->
                                        <div v-if="emp.bank_account_number" class="text-[11px] font-mono text-slate-600 flex items-center gap-1">
                                            <span class="font-bold text-slate-700">{{ emp.bank_name || 'Bank' }}:</span>
                                            <span class="text-blue-700 font-semibold">{{ emp.bank_account_number }}</span>
                                        </div>
                                        <div v-else-if="emp.payment_method === 'Cash'" class="text-[11px] text-emerald-600 font-medium">
                                            💵 Tunai (Cash)
                                        </div>
                                    </div>
                                </td>

                                <!-- 5. Lama Bekerja (Masa Kerja) -->
                                <td class="py-4 px-6 bg-slate-50/30">
                                    <div v-if="emp.join_date" class="space-y-1">
                                        <!-- Badge Lama Bekerja -->
                                        <span 
                                            :class="emp.status === 'resigned' ? 'bg-gray-100 text-gray-700 border-gray-300' : getTenureBadgeClass(emp.join_date)" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs shadow-xs border"
                                        >
                                            <span>{{ emp.status === 'resigned' ? '🛑' : getTenureIcon(emp.join_date) }}</span>
                                            <span>{{ emp.lama_bekerja || calculateTenure(emp.join_date, emp.resign_date) }}</span>
                                        </span>
                                        <!-- Tanggal Mulai Gabung -->
                                        <div class="text-[11px] text-gray-500 flex items-center gap-1">
                                            <span>Mulai:</span>
                                            <span class="font-medium text-gray-700">{{ formatDate(emp.join_date) }}</span>
                                        </div>
                                        <div v-if="emp.status === 'resigned' && emp.resign_date" class="text-[10px] text-rose-500 font-medium">
                                            s.d {{ formatDate(emp.resign_date) }}
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Belum diset
                                    </div>
                                </td>

                                <!-- 6. Area -->
                                <td class="py-4 px-6">
                                    <p class="text-sm font-semibold text-gray-800">{{ emp.area?.name || '-' }}</p>
                                    <div v-if="emp.allowed_attendance_areas && emp.allowed_attendance_areas.length > 0" class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs" :title="'Kunci Absen Reguler: ' + (emp.allowed_attendance_areas_text || 'Area Terpilih')">
                                            <span>🔒</span>
                                            <span class="truncate max-w-[120px]">{{ emp.allowed_attendance_areas_text || 'Terkunci Area' }}</span>
                                        </span>
                                    </div>
                                    <div v-else-if="emp.area_id" class="mt-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-500 border border-slate-200" title="Bebas Absen di Semua Cabang">
                                            <span>🌐</span>
                                            <span>Semua Area</span>
                                        </span>
                                    </div>
                                </td>

                                <!-- 7. Gaji Pokok -->
                                <td class="py-4 px-6 text-right">
                                    <p class="text-sm font-black text-slate-800 font-mono">
                                        Rp {{ formatNumber(emp.base_salary || 0) }}
                                    </p>
                                    <p v-if="emp.payment_method" class="text-[11px] text-slate-400 mt-0.5">
                                        {{ emp.payment_method }}
                                    </p>
                                </td>

                                <!-- 8. Aksi -->
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Aksi View / Detail Karyawan -->
                                        <button 
                                            v-if="can(['hr_employees_view', 'menu_hr_employees'])"
                                            @click="openDetail(emp)" 
                                            title="Lihat Detail Karyawan (View)" 
                                            class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>

                                        <!-- Aksi Update / Edit Data Karyawan -->
                                        <button 
                                            v-if="can(['hr_employees_update', 'hr_employees_edit'])"
                                            @click="openModal(emp)" 
                                            title="Update Data Karyawan (Update)" 
                                            class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>

                                        <!-- Aksi: Aktifkan Kembali (jika status resign) -->
                                        <button 
                                            v-if="emp.status === 'resigned' && can(['hr_employees_reactivate', 'admin'])"
                                            @click="confirmReactivate(emp)" 
                                            title="Aktifkan Kembali Karyawan" 
                                            class="p-1.5 text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors border border-emerald-200"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>

                                        <!-- Aksi: Proses Resign (jika masih aktif) -->
                                        <button 
                                            v-if="emp.status !== 'resigned' && can(['hr_employees_resign', 'admin'])"
                                            @click="openResignModal(emp)" 
                                            title="Proses Resign Karyawan" 
                                            class="p-1.5 text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors border border-rose-200"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                        </button>

                                        <!-- Aksi Hapus Karyawan -->
                                        <button 
                                            v-if="can('hr_employees_delete')"
                                            @click="deleteEmployee(emp.id)" 
                                            title="Hapus Karyawan" 
                                            class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="9" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada data karyawan yang cocok</p>
                                        <p class="text-xs text-gray-300 mt-1">Coba sesuaikan filter pencarian atau klik "Tambah Karyawan"</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links && employees.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Menampilkan {{ employees.from }} - {{ employees.to }} dari {{ employees.total }} data</p>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in employees.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Detail Karyawan (View) -->
        <div v-if="showDetailModal && selectedEmployee" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeDetail"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in-up border border-gray-100">
                <!-- Header Card -->
                <div class="relative px-6 pt-6 pb-5 bg-gradient-to-r from-blue-600 via-indigo-600 to-indigo-700 text-white">
                    <button @click="closeDetail" class="absolute top-4 right-4 text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white/10 ring-4 ring-white/20 flex items-center justify-center text-white font-bold text-2xl overflow-hidden shrink-0 shadow-lg">
                            <img v-if="selectedEmployee.photo" :src="`/storage/${selectedEmployee.photo}`" class="w-full h-full object-cover" alt="">
                            <span v-else>{{ selectedEmployee.name?.charAt(0)?.toUpperCase() }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg font-bold text-white truncate">{{ selectedEmployee.name }}</h3>
                                <span v-if="selectedEmployee.iak_number" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-white/20 text-white tracking-wider">
                                    IAK: {{ selectedEmployee.iak_number }}
                                </span>
                            </div>
                            <p class="text-sm text-blue-100 mt-0.5">{{ selectedEmployee.position || 'Staf Karyawan' }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span :class="employeeTypeBadge(selectedEmployee.employee_type)" class="px-2.5 py-0.5 rounded-full text-[11px] font-medium border bg-white text-gray-800">
                                    {{ selectedEmployee.employee_type || 'Karyawan' }}
                                </span>
                                <span class="text-xs text-blue-100 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span>{{ selectedEmployee.area?.name || 'Semua Area' }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Status Resigned Banner (jika karyawan sudah resign) -->
                    <div v-if="selectedEmployee.status === 'resigned'" class="p-4 rounded-xl bg-gradient-to-r from-rose-50 to-orange-50 border border-rose-200">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-xl bg-rose-100 text-rose-600 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </div>
                            <div class="text-xs space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-600 text-white uppercase tracking-wider">Status: Resign</span>
                                    <span class="text-rose-700 font-semibold">Tgl: {{ formatDate(selectedEmployee.resign_date) }}</span>
                                </div>
                                <p v-if="selectedEmployee.resign_reason" class="text-gray-700">
                                    <span class="font-bold text-gray-900">Alasan:</span> {{ selectedEmployee.resign_reason }}
                                </p>
                                <p v-if="selectedEmployee.resign_notes" class="text-gray-600 italic">
                                    <span class="font-bold not-italic text-gray-900">Catatan / Serah Terima:</span> "{{ selectedEmployee.resign_notes }}"
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Highlight Masa Kerja (Lama Bekerja) -->
                    <div class="p-4 rounded-xl bg-gradient-to-r from-blue-50/80 to-indigo-50/80 border border-blue-100/80">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-xl bg-white shadow-xs text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <span class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold">Lama Bekerja (Masa Kerja)</span>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span v-if="selectedEmployee.join_date" :class="getTenureBadgeClass(selectedEmployee.join_date)" class="px-2.5 py-0.5 rounded-md text-xs font-semibold inline-flex items-center gap-1">
                                            <span>{{ getTenureIcon(selectedEmployee.join_date) }}</span>
                                            <span>{{ selectedEmployee.lama_bekerja || calculateTenure(selectedEmployee.join_date) }}</span>
                                        </span>
                                        <span v-else class="text-xs text-gray-400 italic">Belum diset</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="selectedEmployee.join_date" class="text-right">
                                <span class="text-[10px] text-gray-400 block uppercase">Mulai Bergabung</span>
                                <span class="text-xs font-semibold text-gray-700">{{ formatDate(selectedEmployee.join_date) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Grid Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <!-- Kontak Telepon -->
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-gray-400 uppercase tracking-wider text-[10px] font-semibold mb-1">Nomor Telepon / WA</p>
                            <p class="font-semibold text-gray-800 text-sm flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ selectedEmployee.phone || '-' }}</span>
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-gray-400 uppercase tracking-wider text-[10px] font-semibold mb-1">Email Karyawan</p>
                            <p class="font-semibold text-gray-800 text-sm truncate flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span class="truncate">{{ selectedEmployee.email || '-' }}</span>
                            </p>
                        </div>

                        <!-- Gaji Pokok -->
                        <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200/80 sm:col-span-2 flex items-center justify-between">
                            <div>
                                <p class="text-emerald-700 uppercase tracking-wider text-[10px] font-bold mb-0.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Gaji Pokok (Base Salary)</span>
                                </p>
                                <p class="text-lg font-black text-emerald-900 font-mono">
                                    Rp {{ formatNumber(selectedEmployee.base_salary || 0) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200 inline-block">
                                    {{ selectedEmployee.employee_type || 'Pegawai Tetap' }}
                                </span>
                            </div>
                        </div>

                        <!-- Metode Pembayaran -->
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 sm:col-span-2">
                            <p class="text-gray-400 uppercase tracking-wider text-[10px] font-semibold mb-1">Metode Pembayaran Gaji</p>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-gray-800 text-sm flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <span>{{ selectedEmployee.payment_method || 'Belum diatur' }}</span>
                                    <span v-if="selectedEmployee.bank_name" class="ml-1 px-2 py-0.5 rounded-md text-xs font-bold bg-blue-100 text-blue-800">
                                        {{ selectedEmployee.bank_name }}
                                    </span>
                                </p>
                                <div v-if="selectedEmployee.bank_account_number" class="text-right">
                                    <span class="font-mono font-bold text-sm text-blue-700 bg-white px-2.5 py-1 rounded-lg border border-blue-200 inline-block">
                                        {{ selectedEmployee.bank_account_number }}
                                    </span>
                                    <span v-if="selectedEmployee.bank_account_holder" class="block text-[11px] text-gray-500 mt-0.5">
                                        a.n {{ selectedEmployee.bank_account_holder }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Area Cabang -->
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-gray-400 uppercase tracking-wider text-[10px] font-semibold mb-1">Wilayah / Area Penempatan</p>
                            <p class="font-semibold text-gray-800 text-sm">
                                {{ selectedEmployee.area?.name || 'Semua Area' }}
                            </p>
                        </div>
                    </div>

                    <!-- Alamat Domisili -->
                    <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                        <p class="text-gray-400 uppercase tracking-wider text-[10px] font-semibold mb-1">Alamat Tinggal / Domisili</p>
                        <p class="text-xs text-gray-700 leading-relaxed">
                            {{ selectedEmployee.address || 'Belum ada informasi alamat domisili.' }}
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50/70 border-t border-gray-100 flex items-center justify-between">
                    <button @click="closeDetail" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-100 text-sm font-semibold transition-colors">
                        Tutup
                    </button>
                    <button 
                        v-if="can(['hr_employees_update', 'hr_employees_edit'])"
                        @click="openEditFromDetail" 
                        class="btn-primary py-2 px-4 text-sm flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                        <span>Update Data Karyawan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Form (Tambah / Edit Karyawan) -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Karyawan' : 'Tambah Karyawan Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Photo Upload -->
                        <div class="flex flex-col items-center mb-2">
                            <label class="cursor-pointer group relative">
                                <div class="w-24 h-24 rounded-full border-2 border-dashed border-gray-300 group-hover:border-blue-500 flex items-center justify-center overflow-hidden transition-colors bg-gray-50">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" alt="Preview">
                                    <svg v-else class="w-8 h-8 text-gray-300 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <input type="file" accept="image/*" class="hidden" @change="handlePhoto">
                            </label>
                            <p class="text-xs text-blue-500 mt-2">Upload foto karyawan</p>
                        </div>

                        <!-- Name & IAK -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label>
                                <input v-model="form.name" type="text" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nama lengkap">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">No IAK</label>
                                <input v-model="form.iak_number" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nomor IAK">
                            </div>
                        </div>

                        <!-- Position & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Posisi</label>
                                <select v-model="form.position" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih posisi</option>
                                    <option v-for="pos in positions" :key="pos.id" :value="pos.name">{{ pos.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input v-model="form.email" type="email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="email@contoh.com">
                            </div>
                        </div>

                        <!-- Phone & Area -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                                <input v-model="form.phone" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="08xxxxxxxxxx">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Area Penempatan Utama</label>
                                <select v-model="form.area_id" @change="onPrimaryAreaChange" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih Area</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- KUNCI ABSEN REGULER PER KARYAWAN (Strict Employee Area Lock) -->
                        <div class="p-4 rounded-2xl bg-amber-50/70 border border-amber-200 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <label class="block text-xs font-black text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="text-sm">🔒</span>
                                    <span>Kunci Absen Reguler Per Area</span>
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button 
                                        type="button" 
                                        @click="lockToPrimaryArea" 
                                        class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white text-amber-800 border border-amber-300 hover:bg-amber-100 transition shadow-2xs cursor-pointer"
                                        title="Kunci absensi hanya bisa di area penempatan utama karyawan ini"
                                    >
                                        🔒 Kunci ke Penempatan
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="form.allowed_attendance_areas = []" 
                                        class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition shadow-2xs cursor-pointer"
                                        title="Karyawan bebas absen reguler di semua cabang"
                                    >
                                        🌐 Bebas Semua Cabang
                                    </button>
                                </div>
                            </div>
                            <p class="text-[11px] text-amber-800 leading-relaxed">
                                Tentukan cabang mana saja karyawan ini diperbolehkan presensi WFO. <br>
                                <em>Contoh: Karyawan A hanya di BC 1, Karyawan B hanya di BC 2. Jika tidak dicentang, karyawan mengikuti aturan umum.</em>
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-1">
                                <label 
                                    v-for="area in areas" 
                                    :key="'allow-area-' + area.id"
                                    class="flex items-center gap-2 p-2.5 rounded-xl bg-white border cursor-pointer transition select-none"
                                    :class="(form.allowed_attendance_areas || []).includes(area.id) ? 'border-amber-400 bg-amber-50/60 shadow-2xs ring-1 ring-amber-300' : 'border-slate-200 hover:border-slate-300'"
                                >
                                    <input 
                                        type="checkbox" 
                                        :value="area.id" 
                                        v-model="form.allowed_attendance_areas"
                                        class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer"
                                    />
                                    <span class="text-xs font-bold" :class="(form.allowed_attendance_areas || []).includes(area.id) ? 'text-amber-900' : 'text-slate-700'">
                                        {{ area.name }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- FITUR LAMA BEKERJA: Tanggal Gabung & Live Preview Masa Kerja -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                            <div>
                                <label class="block text-sm font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span>Tanggal Mulai Bekerja (Join Date)</span>
                                </label>
                                <input 
                                    v-model="form.join_date" 
                                    type="date" 
                                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white text-sm"
                                >
                            </div>

                            <!-- Live Tenure Preview Banner -->
                            <div v-if="form.join_date" class="p-3 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between text-xs animate-fade-in-up">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">{{ getTenureIcon(form.join_date) }}</span>
                                    <div>
                                        <span class="text-gray-500">Estimasi Masa Kerja:</span>
                                        <strong class="text-gray-900 ml-1 font-black">{{ calculateTenure(form.join_date) }}</strong>
                                    </div>
                                </div>
                                <span :class="getTenureBadgeClass(form.join_date)" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                    {{ getTenureLevelLabel(form.join_date) }}
                                </span>
                            </div>
                            <p v-else class="text-[11px] text-gray-500 italic">
                                * Masukkan tanggal bergabung agar sistem otomatis menghitung lama bekerja karyawan.
                            </p>
                        </div>

                        <!-- Employee Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Tipe Pegawai
                                </span>
                            </label>
                            <select v-model="form.employee_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                <option value="">Pilih tipe</option>
                                <option value="Tetap">Tetap</option>
                                <option value="Kontrak">Kontrak</option>
                                <option value="Freelance">Freelance</option>
                                <option value="Magang">Magang</option>
                            </select>
                        </div>

                        <!-- Payment Method Section -->
                        <div class="bg-slate-50 p-4 sm:p-5 rounded-2xl border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    <span>Informasi Pembayaran Gaji</span>
                                </h4>
                                <span v-if="form.payment_method" class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                    :class="form.payment_method === 'Transfer Bank' ? 'bg-blue-100 text-blue-700' : (form.payment_method === 'E-Wallet' ? 'bg-cyan-100 text-cyan-700' : 'bg-emerald-100 text-emerald-700')">
                                    {{ form.payment_method }}
                                </span>
                            </div>

                            <!-- Input Gaji Pokok -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span>Gaji Pokok Bulanan (Rp)</span>
                                    </span>
                                    <span v-if="form.base_salary" class="font-mono text-emerald-700 font-bold text-xs bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        Rp {{ formatNumber(form.base_salary) }}
                                    </span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-xs font-bold text-slate-400">Rp</span>
                                    <input 
                                        v-model.number="form.base_salary" 
                                        type="number" 
                                        min="0" 
                                        step="10000"
                                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 bg-white text-sm font-mono font-bold text-slate-800" 
                                        placeholder="0"
                                    >
                                </div>
                            </div>

                            <!-- Dropdown Metode Pembayaran Utama -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Metode Pembayaran</label>
                                <select 
                                    v-model="form.payment_method" 
                                    @change="onPaymentMethodChange"
                                    class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white text-sm"
                                >
                                    <option value="">Pilih metode</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="E-Wallet">E-Wallet (DANA, GoPay, OVO, ShopeePay)</option>
                                    <option value="Cash">Cash (Tunai)</option>
                                </select>
                            </div>

                            <!-- Pilihan Bank & E-Wallet (Tampil untuk Transfer Bank maupun E-Wallet) -->
                            <div v-if="form.payment_method === 'Transfer Bank' || form.payment_method === 'E-Wallet'" class="p-3.5 bg-white rounded-xl border border-slate-200 shadow-xs space-y-3.5 animate-fade-in-up">
                                
                                <!-- Baris Bank Populer -->
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-blue-500 inline-block"></span>
                                            <span>Pilihan Bank</span>
                                        </span>
                                        <span v-if="popularBanks.includes(form.bank_name) && !isCustom" class="text-[10px] text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded">
                                            Terpilih: {{ form.bank_name }}
                                        </span>
                                    </label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button 
                                            v-for="b in popularBanks" 
                                            :key="b" 
                                            type="button"
                                            @click="selectBank(b)"
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium transition-all"
                                            :class="form.bank_name === b && !isCustom ? 'bg-blue-600 text-white shadow-xs ring-2 ring-blue-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                        >
                                            {{ b }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Baris E-Wallet Populer (DANA, GoPay, OVO, ShopeePay) -->
                                <div class="pt-2 border-t border-slate-100">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-cyan-500 inline-block"></span>
                                            <span>Pilihan E-Wallet (DANA & GoPay)</span>
                                        </span>
                                        <span v-if="popularEWallets.includes(form.bank_name) && !isCustom" class="text-[10px] text-cyan-600 font-bold bg-cyan-50 px-2 py-0.5 rounded">
                                            Terpilih: {{ form.bank_name }}
                                        </span>
                                    </label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button 
                                            v-for="w in popularEWallets" 
                                            :key="w" 
                                            type="button"
                                            @click="selectEWallet(w)"
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium transition-all flex items-center gap-1.5"
                                            :class="form.bank_name === w && !isCustom ? (w === 'DANA' ? 'bg-blue-500 text-white shadow-xs ring-2 ring-blue-300' : (w === 'GoPay' ? 'bg-emerald-600 text-white shadow-xs ring-2 ring-emerald-300' : 'bg-cyan-600 text-white shadow-xs ring-2 ring-cyan-300')) : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                        >
                                            <span v-if="w === 'DANA'" class="w-2 h-2 rounded-full bg-white inline-block"></span>
                                            <span v-else-if="w === 'GoPay'" class="w-2 h-2 rounded-full bg-white inline-block"></span>
                                            <span v-else-if="w === 'OVO'" class="w-2 h-2 rounded-full bg-white inline-block"></span>
                                            <span v-else-if="w === 'ShopeePay'" class="w-2 h-2 rounded-full bg-white inline-block"></span>
                                            <span>{{ w }}</span>
                                        </button>
                                        <button 
                                            type="button"
                                            @click="toggleCustom"
                                            class="px-2.5 py-1 text-xs rounded-lg font-medium border border-dashed transition-all"
                                            :class="isCustom || (!popularBanks.includes(form.bank_name) && !popularEWallets.includes(form.bank_name) && form.bank_name) ? 'border-indigo-500 text-indigo-600 bg-indigo-50' : 'border-slate-300 text-slate-500 hover:bg-slate-50'"
                                        >
                                            + Lainnya
                                        </button>
                                    </div>

                                    <!-- Input jika bank / e-wallet lain dipilih -->
                                    <input 
                                        v-if="isCustom || (!popularBanks.includes(form.bank_name) && !popularEWallets.includes(form.bank_name) && form.bank_name)"
                                        v-model="form.bank_name" 
                                        type="text" 
                                        class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 mt-2" 
                                        placeholder="Ketik nama bank atau e-wallet (cth: Bank Jateng, CIMB Niaga, LinkAja, dll)"
                                    >
                                </div>

                                <!-- Input Nomor Rekening / Nomor DANA / GoPay -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2.5 border-t border-slate-100">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1 flex items-center justify-between">
                                            <span>{{ getAccountNumberLabel(form.bank_name) }} *</span>
                                            <span v-if="form.bank_name" class="text-[10px] px-1.5 py-0.2 rounded font-bold uppercase"
                                                :class="popularEWallets.includes(form.bank_name) ? 'bg-cyan-100 text-cyan-800' : 'bg-blue-100 text-blue-800'">
                                                {{ form.bank_name }}
                                            </span>
                                        </label>
                                        <input 
                                            v-model="form.bank_account_number" 
                                            type="text" 
                                            class="w-full px-3.5 py-2 text-xs font-mono font-bold tracking-wider rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white" 
                                            :placeholder="getAccountNumberPlaceholder(form.bank_name)"
                                        >
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="text-xs font-semibold text-slate-700">{{ getAccountHolderLabel(form.bank_name) }}</label>
                                            <button 
                                                v-if="form.name && form.bank_account_holder !== form.name"
                                                type="button" 
                                                @click="form.bank_account_holder = form.name" 
                                                class="text-[10px] text-blue-600 hover:underline font-semibold"
                                            >
                                                Pakai Nama Karyawan
                                            </button>
                                        </div>
                                        <input 
                                            v-model="form.bank_account_holder" 
                                            type="text" 
                                            class="w-full px-3.5 py-2 text-xs rounded-xl border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 bg-white" 
                                            :placeholder="popularEWallets.includes(form.bank_name) ? 'Nama terdaftar di akun ' + form.bank_name : 'Sesuai buku tabungan'"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Opsi Khusus Cash -->
                            <div v-if="form.payment_method === 'Cash'" class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2.5 animate-fade-in-up">
                                <span class="text-lg">💵</span>
                                <div>
                                    <p class="font-bold">Pembayaran Gaji Tunai (Cash)</p>
                                    <p class="text-[11px] text-emerald-600">Gaji diserahkan langsung secara fisik tanpa transfer rekening bank atau e-wallet.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <textarea v-model="form.address" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 resize-none" placeholder="Alamat lengkap"></textarea>
                        </div>
                    </form>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="submit" :disabled="form.processing" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Karyawan' }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Floating Bulk Action Bar -->
        <div 
            v-if="selectedIds.length > 0"
            class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900/95 text-white backdrop-blur-md px-5 py-3 rounded-2xl shadow-2xl border border-slate-700 flex items-center gap-4 animate-fade-in-up"
        >
            <div class="flex items-center gap-2 text-sm font-medium">
                <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center">
                    {{ selectedIds.length }}
                </span>
                <span>karyawan dipilih</span>
            </div>
            <div class="h-4 w-px bg-slate-700"></div>
            <button 
                @click="clearSelection" 
                type="button"
                class="text-xs text-slate-300 hover:text-white transition-colors"
            >
                Batalkan
            </button>
            <button 
                v-if="can(['hr_employees_delete_all', 'hr_employees_delete'])"
                @click="confirmDeleteSelected" 
                type="button"
                class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-sm flex items-center gap-1.5"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span>Hapus Terpilih ({{ selectedIds.length }})</span>
            </button>
        </div>

        <!-- Modal Konfirmasi Hapus Semua / Hapus Terpilih -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-red-950/60 backdrop-blur-sm" @click="closeDeleteModal"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-up border border-red-100">
                <div class="p-6 text-center">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-red-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        {{ deleteModalMode === 'selected' ? `Hapus ${selectedIds.length} Karyawan Terpilih?` : `Hapus SEMUA Data Karyawan?` }}
                    </h3>
                    
                    <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                        <template v-if="deleteModalMode === 'selected'">
                            Sebanyak <strong class="text-gray-900">{{ selectedIds.length }} data karyawan</strong> yang dipilih akan dihapus secara permanen dari sistem.
                        </template>
                        <template v-else>
                            Seluruh data karyawan yang ada di sistem (<strong class="text-rose-600">{{ employees.total || employees.data.length }} karyawan</strong>) akan dihapus secara permanen.
                        </template>
                        Tindakan ini tidak dapat dibatalkan.
                    </p>

                    <!-- Opsi hapus akun login user -->
                    <div class="mb-5 p-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-left">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="deleteLinkedUsers" 
                                class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4 mt-0.5 cursor-pointer" 
                            />
                            <div class="text-xs">
                                <span class="font-semibold text-gray-800">Hapus juga Akun Login User terkait</span>
                                <p class="text-gray-500 mt-0.5">Akun user dengan email karyawan ini juga akan dihapus (akun Super Admin tetap aman).</p>
                            </div>
                        </label>
                    </div>

                    <!-- Konfirmasi teks jika mode ALL -->
                    <div v-if="deleteModalMode === 'all'" class="mb-5 text-left">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            Ketik <span class="font-mono text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">HAPUS SEMUA</span> untuk konfirmasi:
                        </label>
                        <input 
                            v-model="deleteConfirmText" 
                            type="text" 
                            placeholder="HAPUS SEMUA"
                            class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-300 focus:border-rose-500 focus:ring focus:ring-rose-200 text-center uppercase font-mono font-bold"
                        />
                    </div>

                    <div class="flex items-center justify-center gap-3">
                        <button 
                            @click="closeDeleteModal" 
                            type="button"
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition-colors w-full"
                        >
                            Batal
                        </button>
                        <button 
                            @click="executeBulkDelete" 
                            :disabled="isDeleting || (deleteModalMode === 'all' && deleteConfirmText !== 'HAPUS SEMUA')"
                            type="button"
                            class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-md shadow-rose-200"
                        >
                            <svg v-if="isDeleting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isDeleting ? 'Menghapus...' : (deleteModalMode === 'selected' ? 'Ya, Hapus Terpilih' : 'Ya, Hapus Semua') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Proses Resign Karyawan -->
        <div v-if="showResignModal && resignTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeResignModal"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in-up border border-gray-100">
                <!-- Header -->
                <div class="px-6 py-5 bg-gradient-to-r from-rose-600 via-rose-700 to-amber-700 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-white/10 rounded-2xl backdrop-blur-xs">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Proses Resign Karyawan</h3>
                            <p class="text-xs text-rose-100">Form pemberhentian / pengunduran diri karyawan</p>
                        </div>
                    </div>
                    <button @click="closeResignModal" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Target Employee Info -->
                <div class="p-5 border-b border-gray-100 bg-amber-50/60">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-amber-600 text-white font-bold flex items-center justify-center text-base shrink-0 shadow-sm overflow-hidden">
                            <img v-if="resignTarget.photo" :src="`/storage/${resignTarget.photo}`" class="w-full h-full object-cover">
                            <span v-else>{{ resignTarget.name?.charAt(0)?.toUpperCase() }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-bold text-gray-900 text-base leading-tight truncate">{{ resignTarget.name }}</h4>
                            <p class="text-xs text-gray-600 mt-0.5">
                                <span class="font-semibold text-rose-700">{{ resignTarget.position || 'Staf' }}</span> • Area: {{ resignTarget.area?.name || '-' }}
                            </p>
                            <p v-if="resignTarget.join_date" class="text-[11px] text-gray-500 mt-0.5">
                                Mulai kerja: <span class="font-semibold">{{ formatDate(resignTarget.join_date) }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="submitResign" class="p-6 space-y-4">
                    <!-- Tanggal Resign -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Tanggal Efektif Resign / Terakhir Kerja <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            v-model="resignForm.resign_date" 
                            required 
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-200"
                        />
                    </div>

                    <!-- Alasan Resign -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Alasan Resign / Status Berhenti <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            v-model="resignForm.resign_reason" 
                            required 
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-200"
                        >
                            <option value="" disabled>Pilih Alasan Resign...</option>
                            <option value="Mengundurkan Diri (Pribadi)">Mengundurkan Diri (Pribadi)</option>
                            <option value="Mendapat Tawaran Pekerjaan Lain">Mendapat Tawaran Pekerjaan Lain</option>
                            <option value="Habis Masa Kontrak (PKWT)">Habis Masa Kontrak (PKWT)</option>
                            <option value="Alasan Kesehatan / Keluarga">Alasan Kesehatan / Keluarga</option>
                            <option value="Pemberhentian / PHK">Pemberhentian / PHK</option>
                            <option value="Pindah Domisili / Kota">Pindah Domisili / Kota</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- Catatan Tambahan / Serah Terima -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Catatan Tambahan & Serah Terima Aset (Opsional)
                        </label>
                        <textarea 
                            v-model="resignForm.resign_notes" 
                            rows="3" 
                            placeholder="Contoh: Serah terima laptop, inventaris teknisi, dan ID card selesai..." 
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-200 placeholder:text-gray-400"
                        ></textarea>
                    </div>

                    <!-- Security & Automatic Options -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Tindakan Otomatis Sistem:</p>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="resignForm.deactivate_user" 
                                class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 mt-0.5 cursor-pointer" 
                            />
                            <div class="text-xs text-slate-700">
                                <span class="font-semibold text-gray-900">Nonaktifkan Akun Login Pengguna</span>
                                <p class="text-[11px] text-gray-500">Cabut akses login aplikasi dan status on-duty pengguna yang terhubung.</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="resignForm.clean_schedules" 
                                class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 mt-0.5 cursor-pointer" 
                            />
                            <div class="text-xs text-slate-700">
                                <span class="font-semibold text-gray-900">Bersihkan Jadwal Shift Mendatang</span>
                                <p class="text-[11px] text-gray-500">Hapus jadwal roster shift kerja mulai dari tanggal resign ke depan.</p>
                            </div>
                        </label>
                    </div>

                    <!-- Action buttons -->
                    <div class="pt-3 flex items-center justify-end gap-2 border-t border-gray-100">
                        <button 
                            type="button" 
                            @click="closeResignModal" 
                            class="px-4 py-2.5 border border-gray-200 text-gray-600 rounded-xl hover:bg-gray-100 text-sm font-semibold transition-colors"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="isSubmittingResign" 
                            class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer"
                        >
                            <svg v-if="isSubmittingResign" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span>Konfirmasi Resign Karyawan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    employees: Object,
    areas: Array,
    positions: Array,
    avg_tenure: String,
    counts: Object,
    filters: Object,
    scopeLevel: {
        type: String,
        default: 'all',
    },
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const filterType = ref(props.filters?.employee_type || '');
const filterTenure = ref(props.filters?.tenure || '');
const sortBy = ref(props.filters?.sort || '');
const statusFilter = ref(props.filters?.status || 'active');

const setStatusFilter = (val) => {
    statusFilter.value = val;
    applyFiltersNow();
};

const showModal = ref(false);
const showDetailModal = ref(false);
const selectedEmployee = ref(null);
const isEditing = ref(false);
const editingId = ref(null);
const photoPreview = ref(null);

// Resignation State & Methods
const showResignModal = ref(false);
const resignTarget = ref(null);
const isSubmittingResign = ref(false);
const resignForm = ref({
    resign_date: new Date().toISOString().substring(0, 10),
    resign_reason: '',
    resign_notes: '',
    deactivate_user: true,
    clean_schedules: true,
});

function openResignModal(emp) {
    if (!can(['hr_employees_resign', 'admin'])) {
        alert('Anda tidak memiliki hak akses untuk memproses resign karyawan.');
        return;
    }
    resignTarget.value = emp;
    resignForm.value = {
        resign_date: new Date().toISOString().substring(0, 10),
        resign_reason: 'Mengundurkan Diri (Pribadi)',
        resign_notes: '',
        deactivate_user: true,
        clean_schedules: true,
    };
    showResignModal.value = true;
}

function closeResignModal() {
    showResignModal.value = false;
    resignTarget.value = null;
    isSubmittingResign.value = false;
}

function submitResign() {
    if (!resignTarget.value) return;
    if (!can(['hr_employees_resign', 'admin'])) {
        alert('Anda tidak memiliki hak akses untuk memproses resign karyawan.');
        return;
    }
    isSubmittingResign.value = true;
    router.post(`/employees/${resignTarget.value.id}/resign`, resignForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            closeResignModal();
        },
        onFinish: () => {
            isSubmittingResign.value = false;
        }
    });
}

function confirmReactivate(emp) {
    if (!can(['hr_employees_reactivate', 'admin'])) {
        alert('Anda tidak memiliki hak akses untuk mengaktifkan kembali karyawan.');
        return;
    }
    if (confirm(`Apakah Anda yakin ingin mengaktifkan kembali karyawan "${emp.name}"? Status akan kembali Aktif dan akses login akun user terkait akan dipulihkan.`)) {
        router.post(`/employees/${emp.id}/reactivate`, {}, {
            preserveScroll: true,
        });
    }
}

// Bulk Delete & Delete All state
const selectedIds = ref([]);
const showDeleteModal = ref(false);
const deleteModalMode = ref('selected'); // 'selected' or 'all'
const deleteLinkedUsers = ref(false);
const deleteConfirmText = ref('');
const isDeleting = ref(false);

const isAllSelected = computed(() => {
    const list = props.employees?.data || [];
    return list.length > 0 && list.every(e => selectedIds.value.includes(e.id));
});

const isPartiallySelected = computed(() => {
    const list = props.employees?.data || [];
    return selectedIds.value.length > 0 && !isAllSelected.value;
});

function toggleSelectAll() {
    const list = props.employees?.data || [];
    if (isAllSelected.value) {
        const pageIds = list.map(e => e.id);
        selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id));
    } else {
        const newIds = new Set([...selectedIds.value, ...list.map(e => e.id)]);
        selectedIds.value = Array.from(newIds);
    }
}

function clearSelection() {
    selectedIds.value = [];
}

function confirmDeleteSelected() {
    if (!can(['hr_employees_delete_all', 'hr_employees_delete'])) {
        alert('Anda tidak memiliki hak akses untuk menghapus data karyawan.');
        return;
    }
    if (selectedIds.value.length === 0) return;
    deleteModalMode.value = 'selected';
    deleteLinkedUsers.value = false;
    deleteConfirmText.value = '';
    showDeleteModal.value = true;
}

function openDeleteAllModal() {
    if (!can(['hr_employees_delete_all', 'hr_employees_delete'])) {
        alert('Anda tidak memiliki hak akses untuk menghapus data karyawan.');
        return;
    }
    if (selectedIds.value.length > 0) {
        deleteModalMode.value = 'selected';
    } else {
        deleteModalMode.value = 'all';
    }
    deleteLinkedUsers.value = false;
    deleteConfirmText.value = '';
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    deleteConfirmText.value = '';
    isDeleting.value = false;
}

function executeBulkDelete() {
    if (deleteModalMode.value === 'all' && deleteConfirmText.value !== 'HAPUS SEMUA') {
        return;
    }

    isDeleting.value = true;
    router.post('/employees/bulk-destroy', {
        all: deleteModalMode.value === 'all',
        ids: deleteModalMode.value === 'selected' ? selectedIds.value : [],
        delete_users: deleteLinkedUsers.value,
        search: search.value,
        area_id: filterArea.value,
        employee_type: filterType.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            selectedIds.value = [];
        },
        onError: () => {
            isDeleting.value = false;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
}

const popularBanks = ['BCA', 'Bank Mandiri', 'BRI', 'BNI', 'BSI', 'Bank Jago', 'SeaBank'];
const popularEWallets = ['DANA', 'GoPay', 'OVO', 'ShopeePay'];
const isCustom = ref(false);

const getAccountNumberLabel = (bankName) => {
    if (!bankName) return 'Nomor Rekening / No. HP';
    if (bankName === 'DANA') return 'Nomor DANA (No. HP)';
    if (bankName === 'GoPay') return 'Nomor GoPay (No. HP)';
    if (bankName === 'OVO') return 'Nomor OVO (No. HP)';
    if (bankName === 'ShopeePay') return 'Nomor ShopeePay (No. HP)';
    if (popularEWallets.includes(bankName)) return `Nomor ${bankName} (No. HP)`;
    return `Nomor Rekening ${bankName}`;
};

const getAccountNumberPlaceholder = (bankName) => {
    if (popularEWallets.includes(bankName) || bankName === 'DANA' || bankName === 'GoPay') {
        return 'Contoh: 081234567890';
    }
    return 'Contoh: 1234567890';
};

const getAccountHolderLabel = (bankName) => {
    if (popularEWallets.includes(bankName)) {
        return `Nama Akun ${bankName || 'E-Wallet'} (a.n)`;
    }
    return 'Nama Pemilik Rekening (a.n)';
};

const selectBank = (bank) => {
    isCustom.value = false;
    form.payment_method = 'Transfer Bank';
    form.bank_name = bank;
    if (!form.bank_account_holder && form.name) {
        form.bank_account_holder = form.name;
    }
};

const selectEWallet = (wallet) => {
    isCustom.value = false;
    form.payment_method = 'E-Wallet';
    form.bank_name = wallet;
    if (!form.bank_account_holder && form.name) {
        form.bank_account_holder = form.name;
    }
};

const toggleCustom = () => {
    isCustom.value = !isCustom.value;
    if (isCustom.value) {
        form.bank_name = '';
    }
};

const onPaymentMethodChange = () => {
    if (form.payment_method === 'Transfer Bank') {
        if (!form.bank_name || popularEWallets.includes(form.bank_name)) {
            form.bank_name = 'BCA';
            isCustom.value = false;
        }
        if (!form.bank_account_holder && form.name) {
            form.bank_account_holder = form.name;
        }
    } else if (form.payment_method === 'E-Wallet') {
        if (!form.bank_name || popularBanks.includes(form.bank_name)) {
            form.bank_name = 'DANA';
            isCustom.value = false;
        }
        if (!form.bank_account_holder && form.name) {
            form.bank_account_holder = form.name;
        }
    }
};

const form = useForm({
    name: '',
    iak_number: '',
    position: '',
    email: '',
    phone: '',
    area_id: '',
    allowed_attendance_areas: [],
    join_date: '',
    employee_type: '',
    base_salary: 0,
    payment_method: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_holder: '',
    address: '',
    photo: null,
});

const onPrimaryAreaChange = () => {
    if (form.area_id && (!form.allowed_attendance_areas || form.allowed_attendance_areas.length === 0)) {
        form.allowed_attendance_areas = [Number(form.area_id)];
    }
};

const lockToPrimaryArea = () => {
    if (form.area_id) {
        form.allowed_attendance_areas = [Number(form.area_id)];
    } else {
        form.allowed_attendance_areas = [];
    }
};

const hasActiveFilter = computed(() => {
    return !!(search.value || filterArea.value || filterType.value || filterTenure.value || sortBy.value || (statusFilter.value && statusFilter.value !== 'active'));
});

let debounceTimer = null;

const applyFilters = (options = {}) => {
    const params = {};
    const trimmed = (search.value || '').trim();
    if (trimmed) params.search = trimmed;
    if (filterArea.value) params.area_id = filterArea.value;
    if (filterType.value) params.employee_type = filterType.value;
    if (filterTenure.value) params.tenure = filterTenure.value;
    if (sortBy.value) params.sort = sortBy.value;
    if (statusFilter.value && statusFilter.value !== 'active') params.status = statusFilter.value;

    if (!options.keepPage) {
        params.page = 1;
    }

    router.get('/employees', params, { 
        preserveState: true, 
        preserveScroll: true,
        replace: true,
    });
};

const debouncedApplyFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        applyFilters();
    }, 350);
};

const applyFiltersNow = () => {
    clearTimeout(debounceTimer);
    applyFilters();
};

const clearSearch = () => {
    clearTimeout(debounceTimer);
    search.value = '';
    applyFilters();
};

const resetFilters = () => {
    clearTimeout(debounceTimer);
    search.value = '';
    filterArea.value = '';
    filterType.value = '';
    filterTenure.value = '';
    sortBy.value = '';
    statusFilter.value = 'active';
    router.get('/employees', {}, { preserveState: true, preserveScroll: true, replace: true });
};

watch(() => props.filters, (newFilters) => {
    search.value = newFilters?.search || '';
    filterArea.value = newFilters?.area_id || '';
    filterType.value = newFilters?.employee_type || '';
    filterTenure.value = newFilters?.tenure || '';
    sortBy.value = newFilters?.sort || '';
    statusFilter.value = newFilters?.status || 'active';
}, { deep: true });

// ── Kalkulasi Lama Bekerja (Masa Kerja) ─────────────────────────
const calculateTenure = (joinDateStr, endDateStr = null) => {
    if (!joinDateStr) return '-';
    try {
        const join = new Date(joinDateStr);
        if (isNaN(join.getTime())) return '-';

        const end = endDateStr ? new Date(endDateStr) : new Date();
        end.setHours(0, 0, 0, 0);
        join.setHours(0, 0, 0, 0);

        if (join > end) {
            return 'Akan Mulai';
        }

        let years = end.getFullYear() - join.getFullYear();
        let months = end.getMonth() - join.getMonth();
        let days = end.getDate() - join.getDate();

        if (days < 0) {
            months--;
            const prevMonthLastDay = new Date(end.getFullYear(), end.getMonth(), 0).getDate();
            days += prevMonthLastDay;
        }
        if (months < 0) {
            years--;
            months += 12;
        }

        const parts = [];
        if (years > 0) parts.push(`${years} Tahun`);
        if (months > 0) parts.push(`${months} Bulan`);
        if (parts.length === 0 || (years === 0 && months === 0)) {
            parts.push(`${Math.max(1, days)} Hari`);
        }
        return parts.join(' ');
    } catch (e) {
        return '-';
    }
};

const getTenureLevel = (joinDateStr) => {
    if (!joinDateStr) return 'unset';
    try {
        const join = new Date(joinDateStr);
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        join.setHours(0, 0, 0, 0);

        if (join > now) return 'future';

        const diffMonths = (now.getFullYear() - join.getFullYear()) * 12 + (now.getMonth() - join.getMonth());
        if (diffMonths >= 36) return 'senior';
        if (diffMonths >= 12) return 'middle';
        if (diffMonths >= 3) return 'junior';
        return 'new';
    } catch (e) {
        return 'unset';
    }
};

const getTenureBadgeClass = (joinDateStr) => {
    const level = getTenureLevel(joinDateStr);
    switch (level) {
        case 'senior':
            return 'bg-amber-100 text-amber-900 border border-amber-300 font-bold';
        case 'middle':
            return 'bg-indigo-100 text-indigo-900 border border-indigo-200 font-semibold';
        case 'junior':
            return 'bg-blue-100 text-blue-900 border border-blue-200 font-medium';
        case 'new':
            return 'bg-emerald-100 text-emerald-900 border border-emerald-200 font-medium';
        case 'future':
            return 'bg-purple-100 text-purple-900 border border-purple-200 font-medium';
        default:
            return 'bg-gray-100 text-gray-600 border border-gray-200';
    }
};

const getTenureIcon = (joinDateStr) => {
    const level = getTenureLevel(joinDateStr);
    switch (level) {
        case 'senior': return '⭐';
        case 'middle': return '🔷';
        case 'junior': return '🔹';
        case 'new': return '🌱';
        case 'future': return '⏳';
        default: return '⏱️';
    }
};

const getTenureLevelLabel = (joinDateStr) => {
    const level = getTenureLevel(joinDateStr);
    switch (level) {
        case 'senior': return 'Senior (> 3 Thn)';
        case 'middle': return 'Menengah (1 - 3 Thn)';
        case 'junior': return 'Junior (3 - 12 Bln)';
        case 'new': return 'Karyawan Baru (< 3 Bln)';
        case 'future': return 'Akan Mulai';
        default: return 'Belum Diset';
    }
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch (e) {
        return date;
    }
};

const formatNumber = (val) => {
    return new Intl.NumberFormat('id-ID').format(Number(val) || 0);
};

const countByType = (type) => {
    return props.employees.data?.filter(e => e.employee_type === type).length || 0;
};

const employeeTypeBadge = (type) => {
    const map = {
        'Tetap': 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Kontrak': 'bg-amber-50 text-amber-700 border-amber-200',
        'Freelance': 'bg-purple-50 text-purple-700 border-purple-200',
        'Magang': 'bg-sky-50 text-sky-700 border-sky-200',
    };
    return map[type] || 'bg-gray-50 text-gray-700 border-gray-200';
};

const handlePhoto = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

function openDetail(emp) {
    selectedEmployee.value = emp;
    showDetailModal.value = true;
}

function closeDetail() {
    showDetailModal.value = false;
    selectedEmployee.value = null;
}

function openEditFromDetail() {
    const emp = selectedEmployee.value;
    closeDetail();
    if (emp) {
        openModal(emp);
    }
}

function openModal(emp = null) {
    if (emp) {
        if (!can(['hr_employees_update', 'hr_employees_edit'])) {
            alert('Anda tidak memiliki hak akses untuk mengubah data karyawan.');
            return;
        }
        isEditing.value = true;
        editingId.value = emp.id;
        form.name = emp.name || '';
        form.iak_number = emp.iak_number || '';
        form.position = emp.position || '';
        form.email = emp.email || '';
        form.phone = emp.phone || '';
        form.area_id = emp.area_id || '';
        form.allowed_attendance_areas = Array.isArray(emp.allowed_attendance_areas) 
            ? emp.allowed_attendance_areas.map(Number) 
            : (emp.area_id ? [Number(emp.area_id)] : []);
        // If join_date is a formatted string or ISO, ensure YYYY-MM-DD
        if (emp.join_date) {
            form.join_date = emp.join_date.substring(0, 10);
        } else {
            form.join_date = '';
        }
        form.employee_type = emp.employee_type || '';
        form.base_salary = emp.base_salary ? Number(emp.base_salary) : 0;
        form.payment_method = emp.payment_method || '';
        form.bank_name = emp.bank_name || '';
        form.bank_account_number = emp.bank_account_number || '';
        form.bank_account_holder = emp.bank_account_holder || emp.name || '';
        isCustom.value = !!(emp.bank_name && !popularBanks.includes(emp.bank_name) && !popularEWallets.includes(emp.bank_name));
        form.address = emp.address || '';
        form.photo = null;
        photoPreview.value = emp.photo ? `/storage/${emp.photo}` : null;
    } else {
        if (!can('hr_employees_create')) {
            alert('Anda tidak memiliki hak akses untuk menambah karyawan baru.');
            return;
        }
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.base_salary = 0;
        form.allowed_attendance_areas = [];
        form.bank_name = '';
        form.bank_account_number = '';
        form.bank_account_holder = '';
        isCustom.value = false;
        photoPreview.value = null;
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    form.base_salary = 0;
    form.bank_name = '';
    form.bank_account_number = '';
    form.bank_account_holder = '';
    isCustom.value = false;
    photoPreview.value = null;
}

function submit() {
    if (isEditing.value) {
        if (!can(['hr_employees_update', 'hr_employees_edit'])) {
            alert('Anda tidak memiliki hak akses untuk memperbarui data karyawan.');
            return;
        }
        form.post(`/employees/${editingId.value}/update`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    } else {
        if (!can('hr_employees_create')) {
            alert('Anda tidak memiliki hak akses untuk menambah karyawan baru.');
            return;
        }
        form.post('/employees', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    }
}

function deleteEmployee(id) {
    if (!can(['hr_employees_delete_all', 'hr_employees_delete'])) {
        alert('Anda tidak memiliki hak akses untuk menghapus data karyawan.');
        return;
    }
    if (window.confirm('Apakah Anda yakin ingin menghapus data karyawan ini?')) {
        router.post(`/employees/${id}/delete`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = selectedIds.value.filter(i => i !== id);
            }
        });
    }
}
</script>
