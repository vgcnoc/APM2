<template>
    <AppLayout title="Data Karyawan" subtitle="Kelola data personalia, masa kerja, dan status karyawan perusahaan">
        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Total Karyawan -->
                <div class="glass-card p-5 border-2 border-blue-500/10 hover:border-blue-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Karyawan</p>
                            <p class="text-2xl font-bold text-gray-900">{{ employees.total || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Pegawai Tetap -->
                <div class="glass-card p-5 border-2 border-emerald-500/10 hover:border-emerald-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Pegawai Tetap</p>
                            <p class="text-2xl font-bold text-gray-900">{{ countByType('Tetap') }}</p>
                        </div>
                    </div>
                </div>

                <!-- 3. Kontrak & Lainnya -->
                <div class="glass-card p-5 border-2 border-amber-500/10 hover:border-amber-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Kontrak / PKWT</p>
                            <p class="text-2xl font-bold text-gray-900">{{ countByType('Kontrak') }}</p>
                        </div>
                    </div>
                </div>

                <!-- 4. Rata-Rata Lama Bekerja (NEW) -->
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

            <!-- Filter Bar & Search -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[240px]">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari nama, posisi, email, telepon, No IAK..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                            @keyup.enter="applyFilters"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Action: Migrasi Excel/CSV -->
                        <Link 
                            v-if="can('menu_data_migration')"
                            href="/settings/data-migration" 
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
                            @change="applyFilters" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium"
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
                            @change="applyFilters" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium"
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
                            @change="applyFilters" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium"
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
                            @change="applyFilters" 
                            class="rounded-xl border-gray-200 text-xs py-1.5 px-3 focus:border-blue-500 bg-gray-50 font-medium"
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
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-gray-50/50 transition-colors group">
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

                                <!-- 4. Status Tipe -->
                                <td class="py-4 px-6">
                                    <span :class="employeeTypeBadge(emp.employee_type)" class="px-2.5 py-1 rounded-full text-xs font-medium border inline-block">
                                        {{ emp.employee_type || '-' }}
                                    </span>
                                </td>

                                <!-- 5. Lama Bekerja (KOLOM UTAMA FITUR BARU) -->
                                <td class="py-4 px-6 bg-slate-50/30">
                                    <div v-if="emp.join_date" class="space-y-1">
                                        <!-- Badge Lama Bekerja -->
                                        <span 
                                            :class="getTenureBadgeClass(emp.join_date)" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs shadow-xs"
                                        >
                                            <span>{{ getTenureIcon(emp.join_date) }}</span>
                                            <span>{{ emp.lama_bekerja || calculateTenure(emp.join_date) }}</span>
                                        </span>
                                        <!-- Tanggal Mulai Gabung -->
                                        <div class="text-[11px] text-gray-500 flex items-center gap-1">
                                            <span>Mulai:</span>
                                            <span class="font-medium text-gray-700">{{ formatDate(emp.join_date) }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Belum diset
                                    </div>
                                </td>

                                <!-- 6. Area -->
                                <td class="py-4 px-6">
                                    <p class="text-sm font-semibold text-gray-800">{{ emp.area?.name || '-' }}</p>
                                </td>

                                <!-- 7. Aksi -->
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
                                <td colspan="7" class="py-12 text-center">
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

                        <!-- Metode Pembayaran -->
                        <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-gray-400 uppercase tracking-wider text-[10px] font-semibold mb-1">Metode Pembayaran Gaji</p>
                            <p class="font-semibold text-gray-800 text-sm flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>{{ selectedEmployee.payment_method || '-' }}</span>
                            </p>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                                <select v-model="form.area_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih Area</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                </select>
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

                        <!-- Payment Method -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Informasi Pembayaran
                            </h4>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Metode Pembayaran</label>
                                <select v-model="form.payment_method" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih metode</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Cash">Cash</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                </select>
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
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    employees: Object,
    areas: Array,
    positions: Array,
    avg_tenure: String,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const filterType = ref(props.filters?.employee_type || '');
const filterTenure = ref(props.filters?.tenure || '');
const sortBy = ref(props.filters?.sort || '');

const showModal = ref(false);
const showDetailModal = ref(false);
const selectedEmployee = ref(null);
const isEditing = ref(false);
const editingId = ref(null);
const photoPreview = ref(null);

const form = useForm({
    name: '',
    iak_number: '',
    position: '',
    email: '',
    phone: '',
    area_id: '',
    join_date: '',
    employee_type: '',
    payment_method: '',
    address: '',
    photo: null,
});

const hasActiveFilter = computed(() => {
    return !!(search.value || filterArea.value || filterType.value || filterTenure.value || sortBy.value);
});

const applyFilters = () => {
    router.get('/employees', {
        search: search.value || undefined,
        area_id: filterArea.value || undefined,
        employee_type: filterType.value || undefined,
        tenure: filterTenure.value || undefined,
        sort: sortBy.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    search.value = '';
    filterArea.value = '';
    filterType.value = '';
    filterTenure.value = '';
    sortBy.value = '';
    router.get('/employees', {}, { preserveState: true, preserveScroll: true });
};

// ── Kalkulasi Lama Bekerja (Masa Kerja) ─────────────────────────
const calculateTenure = (joinDateStr) => {
    if (!joinDateStr) return '-';
    try {
        const join = new Date(joinDateStr);
        if (isNaN(join.getTime())) return '-';

        const now = new Date();
        now.setHours(0, 0, 0, 0);
        join.setHours(0, 0, 0, 0);

        if (join > now) {
            return 'Akan Mulai';
        }

        let years = now.getFullYear() - join.getFullYear();
        let months = now.getMonth() - join.getMonth();
        let days = now.getDate() - join.getDate();

        if (days < 0) {
            months--;
            const prevMonthLastDay = new Date(now.getFullYear(), now.getMonth(), 0).getDate();
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
        // If join_date is a formatted string or ISO, ensure YYYY-MM-DD
        if (emp.join_date) {
            form.join_date = emp.join_date.substring(0, 10);
        } else {
            form.join_date = '';
        }
        form.employee_type = emp.employee_type || '';
        form.payment_method = emp.payment_method || '';
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
        photoPreview.value = null;
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
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
    if (!can('hr_employees_delete')) {
        alert('Anda tidak memiliki hak akses untuk menghapus data karyawan.');
        return;
    }
    if (confirm('Apakah Anda yakin ingin menghapus data karyawan ini?')) {
        router.post(`/employees/${id}/delete`);
    }
}
</script>
