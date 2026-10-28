<template>
    <AppLayout title="Data Booking" subtitle="Calon pelanggan yang baru mendaftar">
        
        <!-- Panduan & SOP Penggunaan Fitur -->
        <PipelineGuideCard stage="booking" />
        
        <!-- Statistik -->
        <div v-can="'customers_booking_card_stats'" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <!-- Total Booking -->
            <div @click="filterStatus = ''; applyFilters()" class="glass-card p-6 animate-fade-in-up cursor-pointer hover:shadow-md hover:scale-[1.01] transition-all" :class="!filterStatus ? 'ring-2 ring-blue-500 border-blue-400 bg-blue-50/20' : ''" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Total Pendaftar</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.total }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Menunggu Survey -->
            <div @click="filterStatus = 'booking'; applyFilters()" class="glass-card p-6 animate-fade-in-up cursor-pointer hover:shadow-md hover:scale-[1.01] transition-all" :class="filterStatus === 'booking' ? 'ring-2 ring-yellow-500 border-yellow-400 bg-yellow-50/20' : ''" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Baru (Menunggu Survey)</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.baru }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-yellow-50 text-yellow-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Telah Disurvey -->
            <div @click="filterStatus = 'survey'; applyFilters()" class="glass-card p-6 animate-fade-in-up cursor-pointer hover:shadow-md hover:scale-[1.01] transition-all" :class="filterStatus === 'survey' ? 'ring-2 ring-green-500 border-green-400 bg-green-50/20' : ''" style="animation-delay: 0.3s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Terkirim (Data Survey)</p>
                        <h3 class="text-3xl font-bold text-gray-900">{{ stats.disurvey }}</h3>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-green-50 text-green-600 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, telepon..."
            searchRoute="/customers/booking"
            selectable
            v-model:selected="selectedIds"
        >
            <template #filters>
                <div v-can="'customers_booking_filter_area'" class="flex flex-wrap items-center gap-2.5 w-full">
                    <input type="date" v-model="filterDate" @change="applyFilters" class="form-input w-full sm:w-40 text-sm bg-white" title="Tanggal Daftar" />
                    <select v-model="filterArea" @change="applyFilters" class="form-select w-full sm:w-44 md:w-48 text-sm bg-white">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="typeof area === 'object' && area !== null ? area.id : area" :value="typeof area === 'object' && area !== null ? area.name : area">
                            {{ typeof area === 'object' && area !== null ? area.name : area }}
                        </option>
                    </select>
                    <select v-model="filterStatus" @change="applyFilters" class="form-select w-full sm:w-44 md:w-48 text-sm bg-white">
                        <option value="">Semua Status</option>
                        <option value="booking">Baru (Booking)</option>
                        <option value="survey">Disurvey</option>
                        <option value="installing">Proses Pasang</option>
                        <option value="active">Aktif</option>
                    </select>
                    <button @click="applyFilters" class="px-3.5 py-2 bg-blue-50 text-blue-600 font-medium rounded-lg hover:bg-blue-100 transition-colors text-sm border border-blue-200 whitespace-nowrap">
                        Tampilkan
                    </button>
                    <button @click="resetFilters" class="px-3.5 py-2 bg-gray-50 text-gray-600 font-medium rounded-lg hover:bg-gray-100 transition-colors text-sm border border-gray-200 whitespace-nowrap">
                        Reset
                    </button>
                </div>
            </template>

            <template #actions>
                <div class="flex items-center gap-2">
                    <button v-can="'customers_booking_delete'" v-if="canDelete && selectedIds.length > 0" 
                        @click="confirmDeleteAll" 
                        class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-xl transition-colors border border-red-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Terpilih ({{ selectedIds.length }})
                    </button>
                    <Link v-can="'customers_booking_create'" href="/customers/create" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Booking Baru
                    </Link>
                </div>
            </template>

            <template #row="{ row }">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center text-sm font-bold text-gray-900 shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-gray-900 font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td class="text-gray-500">{{ row.phone }}</td>
                <td class="max-w-[250px] truncate text-gray-500 text-xs">{{ row.address }}</td>
                <td>
                    <span v-if="row.service_status === 'free_home'" class="inline-flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-700 rounded-md text-xs font-bold border border-amber-200">
                        <span>🏠</span> Free Home
                    </span>
                    <span v-else-if="row.service_status === 'gratis'" class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-50 text-emerald-700 rounded-md text-xs font-bold border border-emerald-200">
                        <span>🎁</span> Gratis
                    </span>
                    <span v-else-if="row.package" class="inline-flex px-2 py-1 bg-purple-50 text-purple-600 rounded-md text-xs font-medium border border-purple-100">
                        {{ row.package.name }}
                    </span>
                    <span v-else class="text-xs text-gray-400 italic">-</span>
                </td>
                <td class="text-xs text-gray-700 font-medium">
                    <span v-if="row.sales" class="inline-flex items-center gap-1.5 flex-wrap">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>{{ row.sales.name }}</span>
                        <span v-if="(row.sales.job_position || row.sales.role) && (row.sales.job_position || row.sales.role).toLowerCase() !== 'sales'" class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold uppercase tracking-wider">
                            {{ row.sales.job_position || row.sales.role }}
                        </span>
                    </span>
                    <span v-else class="text-gray-400 italic">-</span>
                </td>
                <td class="text-xs text-gray-500">{{ formatDate(row.registration_date || row.created_at) }}</td>
                <td>
                    <div v-if="row.area" class="text-xs font-medium text-gray-700 bg-gray-100 px-2 py-1 rounded-md inline-block">
                        {{ row.area }}
                    </div>
                    <span v-else class="text-xs text-gray-400 italic">Belum ada</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                    <!-- Status / Aksi Utama -->
                    <button v-can="'customers_booking_request_survey'" 
                        v-if="row.status === 'booking'" 
                        @click="requestSurvey(row)" 
                        class="btn-primary py-1 px-2.5 text-xs flex items-center gap-1 shadow-2xs hover:shadow-xs" 
                        title="Pindah ke Data Survey">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                        </svg>
                        <span>Kirim Survey</span>
                    </button>
                    <span v-else class="px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg text-xs font-semibold flex items-center gap-1" title="Sudah Masuk Proses Selanjutnya">
                        <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Terkirim</span>
                    </span>

                    <!-- Aksi Cepat: View Detail Booking -->
                    <button 
                        v-can="'customers_booking_view'"
                        type="button"
                        @click="viewCustomer(row)"
                        class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white border border-blue-200/80 flex items-center justify-center transition-all shadow-2xs hover:shadow-xs focus:outline-none cursor-pointer"
                        title="Lihat Detail Booking"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>

                    <!-- Aksi Cepat: Edit Data Booking -->
                    <Link 
                        v-can="'customers_booking_edit'"
                        :href="`/customers/${row.id}/edit`"
                        class="w-7 h-7 rounded-lg bg-amber-50 hover:bg-amber-600 text-amber-600 hover:text-white border border-amber-200/80 flex items-center justify-center transition-all shadow-2xs hover:shadow-xs focus:outline-none cursor-pointer"
                        title="Edit Data Booking"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </Link>

                    <!-- Aksi Cepat: Cetak / Print Booking -->
                    <a 
                        v-can="'customers_booking_print'"
                        :href="`/customers/${row.id}/print-booking`"
                        target="_blank"
                        class="w-7 h-7 rounded-lg bg-purple-50 hover:bg-purple-600 text-purple-600 hover:text-white border border-purple-200/80 flex items-center justify-center transition-all shadow-2xs hover:shadow-xs focus:outline-none cursor-pointer"
                        title="Cetak Formulir / Bukti Booking"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                    </a>

                    <!-- Aksi Cepat: Hapus Data Booking -->
                    <button 
                        v-can="'customers_booking_delete'"
                        v-if="canDelete"
                        type="button"
                        @click="confirmDelete(row)"
                        class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-600 text-red-600 hover:text-white border border-red-200/80 flex items-center justify-center transition-all shadow-2xs hover:shadow-xs focus:outline-none cursor-pointer"
                        title="Hapus Booking"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>

                    <!-- Tombol Dropdown Menu Aksi -->
                    <button 
                        type="button" 
                        @click.stop="toggleDropdown($event, row)" 
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-lg transition-all shadow-2xs hover:shadow-xs cursor-pointer text-xs font-semibold focus:outline-none"
                        :class="{ 'ring-2 ring-blue-500 bg-blue-50 text-blue-600 border-blue-300': activeDropdownRow?.id === row.id }"
                        title="Pilihan Aksi"
                    >
                        <span>Aksi</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-blue-600': activeDropdownRow?.id === row.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Action Dropdown Menu Floating -->
        <Teleport to="body">
            <div v-if="activeDropdownRow" class="fixed inset-0 z-[120]" @click="closeDropdown" @contextmenu.prevent="closeDropdown">
                <div 
                    :style="{ top: `${dropdownPos.top}px`, left: `${dropdownPos.left}px` }"
                    class="fixed w-72 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-200/80 py-1.5 z-[121] text-xs divide-y divide-slate-100 animate-fade-in-up"
                    @click.stop
                >
                    <!-- Header Info Pelanggan -->
                    <div class="px-3.5 py-2.5 bg-slate-50/90 -mt-1.5 rounded-t-2xl border-b border-slate-100 mb-1">
                        <div class="flex items-center gap-2.5">
                            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-2xs', getAvatarColor(activeDropdownRow.name || 'P')]">
                                {{ (activeDropdownRow.name || 'P').charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 truncate text-xs">{{ activeDropdownRow.name }}</div>
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 truncate mt-0.5">
                                    <span class="font-mono text-slate-600 font-semibold">{{ activeDropdownRow.phone || '-' }}</span>
                                    <span>&bull;</span>
                                    <span class="truncate">{{ activeDropdownRow.area || 'Tanpa Area' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grup 1: Informasi & Manajemen Data Booking -->
                    <div class="py-1">
                        <!-- Lihat Detail Booking -->
                        <button 
                            v-can="'customers_booking_view'"
                            type="button"
                            @click="viewCustomer(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-blue-50/80 hover:text-blue-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-blue-50 group-hover:bg-blue-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-blue-700">Lihat Detail Booking</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-blue-500 font-normal leading-tight">Data pendaftaran & koordinat lokasi</p>
                            </div>
                        </button>

                        <!-- Edit Booking -->
                        <Link 
                            v-can="'customers_booking_edit'"
                            :href="`/customers/${activeDropdownRow.id}/edit`" 
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-amber-50/80 hover:text-amber-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 group-hover:bg-amber-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-amber-700">Edit Data Booking</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-amber-500 font-normal leading-tight">Koreksi paket, kontak, atau lokasi</p>
                            </div>
                        </Link>

                        <!-- Cetak Formulir Booking -->
                        <a 
                            v-can="'customers_booking_print'"
                            :href="`/customers/${activeDropdownRow.id}/print-booking`" 
                            target="_blank"
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-purple-50/80 hover:text-purple-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-purple-50 group-hover:bg-purple-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-purple-700">Cetak Formulir Booking</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-purple-500 font-normal leading-tight">Bukti registrasi & SPK pemasangan</p>
                            </div>
                        </a>

                        <!-- Pindah ke Data Survey (Jika status booking) -->
                        <button 
                            v-can="'customers_booking_request_survey'"
                            v-if="activeDropdownRow.status === 'booking'"
                            type="button"
                            @click="requestSurvey(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-indigo-50/80 hover:text-indigo-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 group-hover:bg-indigo-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-indigo-700">Kirim ke Data Survey</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-indigo-500 font-normal leading-tight">Teruskan untuk dijadwalkan survey</p>
                            </div>
                        </button>
                    </div>

                    <!-- Grup 2: Tindakan Pembatalan & Hapus -->
                    <div class="py-1">
                        <!-- Batalkan Booking -->
                        <button 
                            v-can="'customers_booking_delete'"
                            type="button"
                            @click="openCancelModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-orange-50/80 hover:text-orange-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-orange-50 group-hover:bg-orange-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-orange-600 group-hover:text-orange-700">Batalkan Booking</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-orange-500 font-normal leading-tight">Pindahkan ke daftar batal</p>
                            </div>
                        </button>

                        <!-- Hapus Data Booking -->
                        <button 
                            v-can="'customers_booking_delete'" 
                            v-if="canDelete"
                            type="button"
                            @click="confirmDelete(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-red-50/80 hover:text-red-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-red-50 group-hover:bg-red-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-red-600 group-hover:text-red-700">Hapus Booking</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-red-500 font-normal leading-tight">Hapus permanen dari database</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
        <!-- Modal Detail Booking -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedCustomer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="closeViewModal"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <div class="sticky top-0 bg-white/90 backdrop-blur-xl border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-blue-500/20 text-blue-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                            Detail Form Booking
                        </h3>
                        <button @click="closeViewModal" class="text-gray-500 hover:text-gray-900 transition-colors bg-gray-50 hover:bg-white p-2 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- Info Utama -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nama Lengkap</p>
                                <p class="text-sm text-gray-900 font-medium">{{ selectedCustomer.name }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Kode Pelanggan</p>
                                <p class="text-sm text-blue-400 font-mono">{{ selectedCustomer.customer_code }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <span class="inline-flex px-2 py-1 bg-blue-500/20 text-blue-400 rounded-md border border-blue-500/30">
                                        {{ selectedCustomer.area || '-' }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nomor HP / WA</p>
                                <p class="text-sm text-gray-900">{{ selectedCustomer.phone }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Tanggal Daftar</p>
                                <p class="text-sm text-gray-900 font-medium">📅 {{ formatDate(selectedCustomer.registration_date || selectedCustomer.created_at) }}</p>
                            </div>
                        </div>

                        <hr class="border-white/5">

                        <!-- Alamat & Paket -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-6">
                                <div class="space-y-1">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Alamat Lengkap</p>
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ selectedCustomer.address }}</p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                                <div class="space-y-1">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Titik Koordinat Lokasi</p>
                                    <div v-if="selectedCustomer.latitude && selectedCustomer.longitude" class="flex flex-col items-start gap-2 mt-1">
                                        <div class="flex flex-wrap items-center gap-1.5 font-mono text-xs">
                                            <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">Lat: {{ selectedCustomer.latitude }}</span>
                                            <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">Long: {{ selectedCustomer.longitude }}</span>
                                        </div>
                                        <a :href="`https://www.google.com/maps?q=${selectedCustomer.latitude},${selectedCustomer.longitude}`" target="_blank" class="text-xs bg-white hover:bg-gray-100 border border-gray-200 text-gray-900 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            Buka di Maps
                                        </a>
                                    </div>
                                    <p v-else class="text-sm text-gray-500">-</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Paket / Layanan</p>
                                    <p class="text-sm text-gray-900 font-medium">
                                        <span v-if="selectedCustomer.service_status === 'free_home'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-500/20 text-amber-700 rounded-md border border-amber-500/30 font-bold">
                                            <span>🏠</span> Free Home (Voucher Rumahan)
                                        </span>
                                        <span v-else-if="selectedCustomer.service_status === 'gratis'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-500/20 text-emerald-700 rounded-md border border-emerald-500/30 font-bold">
                                            <span>🎁</span> Gratis (Non-Invoice)
                                        </span>
                                        <span v-else class="inline-flex px-2 py-1 bg-purple-500/20 text-purple-600 rounded-md border border-purple-500/30">
                                            {{ selectedCustomer.package?.name || 'Belum dipilih' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="border-white/5">

                        <!-- Catatan Khusus -->
                        <!-- Foto KTP & Catatan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-4">
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Foto KTP</p>
                                <div v-if="selectedCustomer.identity_photo" class="mt-2 relative group overflow-hidden rounded-xl border border-gray-200">
                                    <img :src="`/storage/${selectedCustomer.identity_photo}`" alt="Foto KTP" class="w-full h-auto max-h-48 object-cover transition-transform duration-300 group-hover:scale-105" />
                                    <div class="absolute inset-0 bg-gray-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <a :href="`/storage/${selectedCustomer.identity_photo}`" target="_blank" class="px-3 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-xs font-medium text-gray-900 transition-colors">Lihat Penuh</a>
                                    </div>
                                </div>
                                <div v-else class="bg-gray-50 rounded-xl p-4 border border-gray-200 text-sm text-gray-500 flex items-center justify-center min-h-[100px] italic">
                                    Tidak ada foto KTP
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Catatan Tambahan</p>
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 text-sm text-gray-600 min-h-[100px]">
                                    {{ selectedCustomer.notes || 'Tidak ada catatan.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sticky bottom-0 bg-white/90 backdrop-blur-xl border-t border-gray-200 px-6 py-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <!-- Tombol Cetak dari Modal -->
                            <a 
                                v-can="'customers_booking_print'"
                                :href="`/customers/${selectedCustomer.id}/print-booking`" 
                                target="_blank"
                                class="px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Cetak Formulir (Print)
                            </a>

                            <!-- Tombol Edit dari Modal -->
                            <Link 
                                v-can="'customers_booking_edit'"
                                :href="`/customers/${selectedCustomer.id}/edit`" 
                                class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Edit Data
                            </Link>
                        </div>
                        <button @click="closeViewModal" class="px-5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl text-xs font-medium transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus Satu Data -->
        <Teleport to="body">
            <div v-if="showDeleteModal && selectedCustomerToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="showDeleteModal = false"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Hapus Data Booking?</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Apakah Anda yakin ingin menghapus data booking <b>{{ selectedCustomerToDelete.name }}</b>? Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteModal = false" class="btn-secondary w-full">Batal</button>
                        <button @click="deleteCustomer" :disabled="isDeleting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center">
                            <svg v-if="isDeleting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus Semua Data -->
        <Teleport to="body">
            <div v-if="showDeleteAllModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-red-900/60 backdrop-blur-sm cursor-pointer" @click="showDeleteAllModal = false"></div>
                <div class="relative bg-white border border-red-200 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-red-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">
                        Hapus {{ selectedIds.length }} Data Terpilih?
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Data terpilih akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showDeleteAllModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">Batal</button>
                        <button @click="deleteAllBooking" :disabled="isDeletingAll" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-red-200">
                            <svg v-if="isDeletingAll" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Hapus Semua</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Batal Booking -->
        <Teleport to="body">
            <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-orange-900/60 backdrop-blur-sm cursor-pointer" @click="showCancelModal = false"></div>
                <div class="relative bg-white border border-orange-200 rounded-2xl shadow-2xl w-full max-w-md p-6 animate-fade-in-up">
                    <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-orange-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2 text-center">
                        Batalkan Booking?
                    </h3>
                    <p class="text-sm text-gray-500 mb-4 text-center">
                        Pelanggan <strong>{{ cancelCustomer?.name }}</strong> akan dipindahkan ke menu "Dibatalkan" dengan status <span class="font-semibold text-rose-600">Batal Booking</span>.
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pembatalan <span class="text-red-500">*</span></label>
                        <input type="date" v-model="cancelDate" class="w-full rounded-xl border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200 transition-all text-sm" required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pembatalan <span class="text-red-500">*</span></label>
                        <textarea v-model="cancelReason" rows="3" class="w-full rounded-xl border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200 transition-all text-sm" placeholder="Masukkan alasan pembatalan booking..." required></textarea>
                    </div>
                    <div class="flex items-center justify-center gap-3">
                        <button @click="showCancelModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm rounded-xl transition-colors w-full">Kembali</button>
                        <button @click="submitCancel" :disabled="!cancelReason || !cancelDate || isCanceling" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium text-sm rounded-xl transition-colors w-full flex justify-center items-center shadow-sm shadow-orange-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg v-if="isCanceling" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span v-else>Ya, Batalkan</span>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PipelineGuideCard from '@/Components/Customers/PipelineGuideCard.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ customers: Object, filters: Object, areas: Array, stats: Object });
const page = usePage();

// WA Modal removed
const selectedIds = ref([]);

const canDelete = computed(() => {
    try {
        if (!page || !page.props || !page.props.auth || !page.props.auth.user) return false;
        
        const user = page.props.auth.user;
        
        // 1. Check Role String (insensitive and trimmed)
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) {
                return true;
            }
        }
        
        // 2. Check Roles Array (Spatie)
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        for (let r of roles) {
            if (typeof r === 'string') {
                const rStr = r.toLowerCase().trim();
                if (rStr === 'admin' || rStr === 'super admin' || rStr.includes('admin')) return true;
            }
        }
        
        // 3. Check Permissions (Spatie)
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        return perms.includes('menu_customers_booking') || perms.includes('customers_booking_delete') || perms.includes('customers_delete');
    } catch (e) {
        console.error("Auth check error:", e);
        return false;
    }
});

const filterDate = ref(props.filters?.date || '');
const filterArea = ref(props.filters?.area || props.filters?.area_id || '');
const filterStatus = ref(props.filters?.status || '');

watch(() => props.filters, (newFilters) => {
    filterDate.value = newFilters?.date || '';
    filterArea.value = newFilters?.area || newFilters?.area_id || '';
    filterStatus.value = newFilters?.status || '';
}, { deep: true });

function applyFilters() {
    const currentSearch = typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('search') || undefined) : undefined;
    router.get('/customers/booking', {
        search: currentSearch,
        date: filterDate.value || undefined,
        area: filterArea.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterDate.value = '';
    filterArea.value = '';
    filterStatus.value = '';
    const currentSearch = typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('search') || undefined) : undefined;
    router.get('/customers/booking', {
        search: currentSearch,
    }, { preserveState: true, preserveScroll: true });
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const clean = String(dateStr).split(' ')[0];
        const parts = clean.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return clean;
    } catch (e) {
        return dateStr;
    }
}

const columns = [
    { key: 'name', label: 'Nama Pelanggan' },
    { key: 'phone', label: 'Telepon' },
    { key: 'address', label: 'Alamat' },
    { key: 'package', label: 'Paket Langganan' },
    { key: 'sales', label: 'Sales' },
    { key: 'date', label: 'Tgl Daftar' },
    { key: 'area', label: 'Area / Wilayah' },
];

const showViewModal = ref(false);
const selectedCustomer = ref(null);

function viewCustomer(customer) {
    selectedCustomer.value = customer;
    showViewModal.value = true;
}

function closeViewModal() {
    showViewModal.value = false;
    setTimeout(() => {
        selectedCustomer.value = null;
    }, 300);
}



function requestSurvey(row) {
    if (confirm('Pindahkan pelanggan ini ke Data Survey untuk dijadwalkan?')) {
        // Kirim request ke backend untuk ubah status ke 'survey'
        router.post(`/customers/${row.id}/request-survey`, {}, {
            preserveScroll: true
        });
    }
}

// Fitur Hapus Satu Data
const showDeleteModal = ref(false);
const selectedCustomerToDelete = ref(null);
const isDeleting = ref(false);

function confirmDelete(customer) {
    selectedCustomerToDelete.value = customer;
    showDeleteModal.value = true;
}

function deleteCustomer() {
    if (!selectedCustomerToDelete.value) return;
    
    isDeleting.value = true;
    router.post(`/customers/${selectedCustomerToDelete.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedCustomerToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
}

// Fitur Hapus Semua Data Booking
const showDeleteAllModal = ref(false);
const isDeletingAll = ref(false);

function confirmDeleteAll() {
    showDeleteAllModal.value = true;
}

function deleteAllBooking() {
    isDeletingAll.value = true;
    router.post('/customers/bulk-destroy', {
        ids: selectedIds.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteAllModal.value = false;
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeletingAll.value = false;
        }
    });
}

// ── Batal Booking ───────────────────────────────────────────────
const showCancelModal = ref(false);
const cancelCustomer = ref(null);
const cancelReason = ref('');
const cancelDate = ref(new Date().toISOString().split('T')[0]);
const isCanceling = ref(false);

function openCancelModal(row) {
    cancelCustomer.value = row;
    cancelReason.value = '';
    cancelDate.value = new Date().toISOString().split('T')[0];
    showCancelModal.value = true;
}

function submitCancel() {
    if (!cancelCustomer.value || !cancelReason.value || !cancelDate.value) return;
    
    isCanceling.value = true;
    router.post(`/customers/${cancelCustomer.value.id}/cancel`, {
        cancel_stage: 'booking',
        cancel_reason: cancelReason.value,
        cancel_date: cancelDate.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
            cancelCustomer.value = null;
            cancelReason.value = '';
            selectedIds.value = [];
        },
        onFinish: () => {
            isCanceling.value = false;
        }
    });
}

// ── Floating Action Dropdown Logic ─────────────────────────
const activeDropdownRow = ref(null);
const dropdownPos = ref({ top: 0, left: 0 });

function toggleDropdown(event, row) {
    if (activeDropdownRow.value && activeDropdownRow.value.id === row.id) {
        activeDropdownRow.value = null;
        return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    const dropdownHeight = 310;
    const dropdownWidth = 288; // w-72 = 288px

    let top = rect.bottom + 6;
    if (top + dropdownHeight > window.innerHeight) {
        if (rect.top > dropdownHeight) {
            top = Math.max(10, rect.top - dropdownHeight - 6);
        } else {
            top = Math.max(10, window.innerHeight - dropdownHeight - 10);
        }
    }

    let left = rect.right - dropdownWidth;
    if (left < 10) {
        left = 10;
    }

    dropdownPos.value = { top, left };
    activeDropdownRow.value = row;
}

function closeDropdown() {
    activeDropdownRow.value = null;
}

function handleWindowChange() {
    if (activeDropdownRow.value) {
        activeDropdownRow.value = null;
    }
}

function getAvatarColor(name) {
    if (!name) return 'bg-blue-500';
    const colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-pink-500', 'bg-amber-500'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

onMounted(() => {
    window.addEventListener('scroll', handleWindowChange, { passive: true });
    window.addEventListener('resize', handleWindowChange);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleWindowChange);
    window.removeEventListener('resize', handleWindowChange);
});
</script>
