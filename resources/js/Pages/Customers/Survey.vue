<template>
    <AppLayout title="Data Survey" subtitle="Manajemen jadwal & pelaporan hasil survey ODP">

        <!-- Panduan & SOP Penggunaan Fitur -->
        <PipelineGuideCard stage="survey" />
        
        <!-- Statistik Cards -->
        <div v-can="'customers_survey_card_stats'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard 
                title="Belum Dijadwalkan" 
                :value="stats?.jadwalkan || 0" 
                icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" 
                color="blue" 
                class="cursor-pointer transition-all hover:scale-[1.01]"
                @click="selectedStatus = 'jadwalkan'; applyFilters()"
            />
            <StatCard 
                title="Menunggu Laporan" 
                :value="stats?.laporan || 0" 
                icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" 
                color="indigo" 
                class="cursor-pointer transition-all hover:scale-[1.01]"
                @click="selectedStatus = 'laporan'; applyFilters()"
            />
            <StatCard 
                title="Ready Install (Feasible)" 
                :value="stats?.ready || 0" 
                icon="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" 
                color="emerald" 
                class="cursor-pointer transition-all hover:scale-[1.01]"
                @click="selectedStatus = 'ready'; applyFilters()"
            />
            <StatCard 
                title="Unfeasible" 
                :value="stats?.unfeasible || 0" 
                icon="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" 
                color="rose" 
                class="cursor-pointer transition-all hover:scale-[1.01]"
                @click="selectedStatus = 'unfeasible'; applyFilters()"
            />
        </div>

        <!-- Tabs Status Filter -->
        <div class="flex space-x-1 bg-gray-100/70 p-1.5 rounded-xl mb-6 overflow-x-auto shadow-inner border border-gray-200/60">
            <button @click="selectedStatus = 'semua'; applyFilters()" :class="['px-4 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 whitespace-nowrap flex-1 text-center', selectedStatus === 'semua' ? 'bg-white text-blue-600 shadow-sm ring-1 ring-gray-900/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50']">
                Semua Status
            </button>
            <button @click="selectedStatus = 'jadwalkan'; applyFilters()" :class="['px-4 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 whitespace-nowrap flex-1 text-center', selectedStatus === 'jadwalkan' ? 'bg-white text-blue-600 shadow-sm ring-1 ring-gray-900/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50']">
                Belum Dijadwalkan
            </button>
            <button @click="selectedStatus = 'laporan'; applyFilters()" :class="['px-4 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 whitespace-nowrap flex-1 text-center', selectedStatus === 'laporan' ? 'bg-white text-blue-600 shadow-sm ring-1 ring-gray-900/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50']">
                Menunggu Laporan
            </button>
            <button @click="selectedStatus = 'ready'; applyFilters()" :class="['px-4 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 whitespace-nowrap flex-1 text-center', selectedStatus === 'ready' ? 'bg-white text-emerald-600 shadow-sm ring-1 ring-gray-900/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50']">
                Ready Install (Feasible)
            </button>
            <button @click="selectedStatus = 'unfeasible'; applyFilters()" :class="['px-4 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 whitespace-nowrap flex-1 text-center', selectedStatus === 'unfeasible' ? 'bg-white text-rose-600 shadow-sm ring-1 ring-gray-900/5' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50']">
                Unfeasible
            </button>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan..."
            searchRoute="/customers/survey"
            selectable
            v-model:selected="selectedIds"
        >
            <template #filters>
                <div v-can="'customers_survey_filter'" class="flex flex-col md:flex-row md:items-center gap-2 md:gap-3 w-full">
                    <!-- Filter Teknisi -->
                    <select v-model="selectedTechnician" @change="applyFilters" class="w-full md:w-auto px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-all pr-8">
                        <option value="">Semua Teknisi / Surveyor</option>
                        <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                    </select>
                    
                    <!-- Filter Tanggal -->
                    <input type="date" v-model="selectedDate" @change="applyFilters" class="w-full md:w-auto px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-all" />
                    
                    <!-- Filter Area / Wilayah -->
                    <select v-model="selectedArea" @change="applyFilters" class="w-full md:w-auto px-3 py-2 border border-gray-200 rounded-xl text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-all pr-8">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="area in areas" :key="typeof area === 'object' && area !== null ? area.id : area" :value="typeof area === 'object' && area !== null ? area.name : area">
                            {{ typeof area === 'object' && area !== null ? area.name : area }}
                        </option>
                    </select>

                    <!-- Tombol Tampilkan & Reset -->
                    <div class="flex items-center gap-2 w-full md:w-auto mt-2 md:mt-0">
                        <button @click="applyFilters" class="w-full md:w-auto px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-xl transition-colors shadow-sm shadow-blue-200 whitespace-nowrap">
                            Tampilkan
                        </button>
                        <button @click="resetFilters" class="w-full md:w-auto px-4 py-2 bg-white text-gray-700 hover:bg-gray-50 border border-gray-200 font-medium text-sm rounded-xl transition-colors shadow-sm whitespace-nowrap">
                            Reset
                        </button>
                    </div>
                </div>
            </template>
            
            <template #actions>
                <div class="flex items-center gap-2">
                    <button v-if="canDelete && selectedIds.length > 0" 
                        @click="bulkDelete" 
                        class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-lg transition-colors border border-red-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Terpilih ({{ selectedIds.length }})
                    </button>
                </div>
            </template>
            
            <template #row="{ row }">
                <!-- Aksi Pelanggan (Sebelum Nama Pelanggan) -->
                <td class="whitespace-nowrap">
                    <div class="flex items-center justify-start gap-1.5 whitespace-nowrap">
                        <template v-if="row.status === 'survey'">
                            <!-- Belum Dijadwalkan -->
                            <button v-if="!row.surveys?.length && !row.technician_schedules?.length && hasPermission('customers_survey_assign')" 
                                @click="openAssignModal(row)" 
                                class="btn-primary py-1.5 px-3 text-xs flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>Jadwalkan</span>
                            </button>
                            
                            <!-- Sudah Dijadwalkan, tapi belum disurvey -->
                            <template v-else-if="!row.surveys?.length && row.technician_schedules?.length">
                                <!-- Jika punya akses Isi Laporan atau ditugaskan pada survey ini -->
                                <button v-if="hasPermission('customers_survey_report') || isUserAssigned(row)" 
                                    @click="openReportModal(row)" 
                                    class="btn-success py-1.5 px-3 text-xs flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    <span>Isi Laporan</span>
                                </button>
                                <!-- Jika tidak punya akses Isi Laporan (CS/Admin tanpa akses) -->
                                <span v-else class="px-2.5 py-1.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-lg text-xs font-medium flex items-center gap-1 cursor-default" title="Menunggu teknisi mengisi laporan survey">
                                    <svg class="w-3.5 h-3.5 animate-spin text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span>Menunggu Survey</span>
                                </span>
                            </template>

                            <!-- Sudah Disurvey & Feasible: Warna ORANGE sesuai permintaan user -->
                            <button v-else-if="row.surveys?.length && row.surveys[0]?.feasibility === 'feasible' && hasPermission('customers_survey_mark_ready')" 
                                @click="openInstallModal(row)" 
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-xs transition-colors cursor-pointer" 
                                title="Laporan Selesai: Klik untuk menjadwalkan instalasi di menu Pasang/Aktif">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                <span>Ready Install</span>
                            </button>

                            <!-- Sudah Disurvey & Not Feasible -->
                            <div v-else-if="row.surveys?.length && row.surveys[0]?.feasibility === 'not_feasible'" 
                                class="flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-xs font-bold cursor-not-allowed" 
                                title="Pelanggan tidak layak/tidak tercover jaringan">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Unfeasible</span>
                            </div>
                        </template>
                        <template v-else>
                            <span class="px-2.5 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-bold border border-emerald-200 flex items-center gap-1" title="Telah masuk proses Pemasangan/Aktif">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>Selesai</span>
                            </span>
                        </template>

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
                </td>

                <!-- Pelanggan -->
                <td class="min-w-[200px] max-w-[280px] whitespace-normal">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-xs">
                            {{ (row.name || 'P').charAt(0).toUpperCase() }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <Link :href="`/customers/${row.id}`" class="text-gray-900 font-bold hover:text-blue-600 transition-colors text-sm truncate max-w-[180px] block" :title="row.name">
                                    {{ row.name }}
                                </Link>
                                <span v-if="row.customer_code" class="text-[10px] font-mono px-1.5 py-0.2 bg-gray-100 text-gray-600 rounded">
                                    {{ row.customer_code }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 line-clamp-2 mt-0.5 leading-snug break-words" :title="row.address">
                                {{ row.address }}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="whitespace-nowrap">
                    <span v-if="row.surveys?.length" class="text-xs text-cyan-600 font-mono font-semibold">
                        {{ row.surveys[0]?.odp?.name || '-' }}
                    </span>
                    <span v-else class="text-xs text-gray-400 italic">Belum survey</span>
                </td>
                <td class="whitespace-nowrap text-center">
                    <span v-if="row.surveys?.length && row.surveys[0]?.distance_meters" class="text-xs text-gray-700 font-medium">
                        {{ Math.round(row.surveys[0].distance_meters) }} m
                    </span>
                    <span v-else class="text-gray-400 text-xs">-</span>
                </td>
                <td class="whitespace-nowrap">
                    <span v-if="row.surveys?.length && row.surveys[0]?.port_available" class="badge badge-active">Tersedia</span>
                    <span v-else-if="row.surveys?.length" class="badge badge-terminated">Penuh</span>
                    <span v-else class="text-gray-400 text-xs">-</span>
                </td>
                <td class="whitespace-nowrap">
                    <StatusBadge v-if="row.surveys?.length" :status="row.surveys[0]?.feasibility || 'feasible'" />
                    <span v-else class="text-gray-400 text-xs">-</span>
                </td>
                <td class="whitespace-nowrap">
                    <div v-if="row.technician_schedules && row.technician_schedules.length > 0">
                        <span class="text-xs font-semibold block truncate max-w-[140px]" :class="row.surveys?.length ? 'text-gray-700' : 'text-blue-600'" :title="row.technician_schedules.map(s => s.technician?.name).filter(Boolean).join(', ')">
                            {{ row.technician_schedules.map(s => s.technician?.name).filter(Boolean).join(', ') || (row.surveys?.[0]?.surveyor?.name || '-') }}
                        </span>
                        <p class="text-[10px]" :class="row.surveys?.length ? 'text-emerald-600 font-medium' : 'text-blue-500/70'">
                            {{ row.surveys?.length ? 'Selesai Survey' : `${row.technician_schedules[0].scheduled_date || ''} ${row.technician_schedules[0].scheduled_time || ''}` }}
                        </p>
                    </div>
                    <div v-else-if="row.surveys && row.surveys.length > 0">
                        <span class="text-xs text-gray-700 font-medium block truncate max-w-[140px]">{{ row.surveys[0].surveyor?.name || '-' }}</span>
                        <p class="text-[10px] text-emerald-600 font-medium">Selesai Survey</p>
                    </div>
                    <span v-else class="text-gray-400 text-xs italic">Belum ada jadwal</span>
                </td>
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
                                    <span class="truncate">{{ activeDropdownRow.address || 'Tanpa Alamat' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grup 1: Aksi Utama & Informasi Survey -->
                    <div class="py-1">
                        <!-- Lihat Laporan / Detail -->
                        <button 
                            type="button"
                            @click="openViewModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-blue-50/80 hover:text-blue-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-blue-50 group-hover:bg-blue-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-blue-700">Lihat Detail & Laporan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-blue-500 font-normal leading-tight">Data survey, ODP & foto bukti</p>
                            </div>
                        </button>

                        <!-- Buka Radar ODP Terdekat (Peta) -->
                        <a 
                            v-if="activeDropdownRow.latitude && activeDropdownRow.longitude"
                            :href="`/network-radar?lat=${activeDropdownRow.latitude}&lng=${activeDropdownRow.longitude}`" 
                            target="_blank"
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-indigo-50/80 hover:text-indigo-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 group-hover:bg-indigo-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-indigo-700">Radar ODP Terdekat</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-indigo-500 font-normal leading-tight">Buka radar peta rute kabel</p>
                            </div>
                        </a>

                        <!-- Jadwalkan Survey (Jika belum dijadwalkan) -->
                        <button 
                            v-if="activeDropdownRow.status === 'survey' && !activeDropdownRow.surveys?.length && !activeDropdownRow.technician_schedules?.length && hasPermission('customers_survey_assign')"
                            type="button"
                            @click="openAssignModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-blue-50/80 hover:text-blue-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-blue-50 group-hover:bg-blue-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-blue-700">Jadwalkan Survey</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-blue-500 font-normal leading-tight">Tugaskan teknisi & tetapkan waktu</p>
                            </div>
                        </button>

                        <!-- Isi Laporan (Jika sudah dijadwalkan) -->
                        <button 
                            v-if="activeDropdownRow.status === 'survey' && !activeDropdownRow.surveys?.length && activeDropdownRow.technician_schedules?.length && (hasPermission('customers_survey_report') || isUserAssigned(activeDropdownRow))"
                            type="button"
                            @click="openReportModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-emerald-50/80 hover:text-emerald-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 group-hover:bg-emerald-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-emerald-700">Isi Laporan Survey</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-emerald-500 font-normal leading-tight">Input ODP, jarak kabel & foto survey</p>
                            </div>
                        </button>

                        <!-- Reschedule Survey (Jika sudah dijadwalkan) -->
                        <button 
                            v-if="activeDropdownRow.status === 'survey' && !activeDropdownRow.surveys?.length && activeDropdownRow.technician_schedules?.length && hasPermission('customers_survey_reschedule')"
                            type="button"
                            @click="openRescheduleModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-amber-50/80 hover:text-amber-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 group-hover:bg-amber-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-amber-700">Reschedule Jadwal</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-amber-500 font-normal leading-tight">Ubah tanggal atau petugas survey</p>
                            </div>
                        </button>

                        <!-- Proses Ready Install (Jika survey selesai & Layak) -->
                        <button 
                            v-if="activeDropdownRow.status === 'survey' && activeDropdownRow.surveys?.length && activeDropdownRow.surveys[0]?.feasibility === 'feasible' && hasPermission('customers_survey_mark_ready')"
                            type="button"
                            @click="openInstallModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-orange-50/80 hover:text-orange-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-orange-50 group-hover:bg-orange-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-orange-700">Proses Ready Install</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-orange-500 font-normal leading-tight">Kirim ke antrian Pemasangan</p>
                            </div>
                        </button>

                        <!-- Edit Laporan Survey (Jika ada survey) -->
                        <button 
                            v-if="hasPermission('customers_survey_edit') && activeDropdownRow.surveys?.length"
                            type="button"
                            @click="openEditReportModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-sky-50/80 hover:text-sky-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-sky-50 group-hover:bg-sky-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-sky-700">Edit Laporan Survey</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-sky-500 font-normal leading-tight">Koreksi data ODP, port, atau foto</p>
                            </div>
                        </button>

                        <!-- Rollback Survey (ulangi pengisian laporan dari awal) -->
                        <button
                            v-if="activeDropdownRow.status === 'survey' && activeDropdownRow.surveys?.length && hasPermission('customers_survey_rollback', 'customers_survey_edit')"
                            type="button"
                            @click="rollbackSurvey(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-amber-50/80 hover:text-amber-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 group-hover:bg-amber-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-amber-700">Rollback Survey</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-amber-500 font-normal leading-tight">Arsipkan laporan dan isi ulang dari awal</p>
                            </div>
                        </button>
                    </div>

                    <!-- Grup 2: Tindakan Pembatalan & Hapus -->
                    <div v-if="canCancel || canDelete" class="py-1">
                        <!-- Batalkan Pelanggan -->
                        <button 
                            v-if="canCancel"
                            type="button"
                            @click="openCancelModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-orange-50/80 hover:text-orange-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-orange-50 group-hover:bg-orange-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-orange-600 group-hover:text-orange-700">Batalkan Pelanggan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-orange-500 font-normal leading-tight">Batalkan proses pendaftaran survey</p>
                            </div>
                        </button>

                        <!-- Hapus Data Pelanggan -->
                        <button 
                            v-if="canDelete"
                            type="button"
                            @click="confirmDelete(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-red-50/80 hover:text-red-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-red-50 group-hover:bg-red-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-red-600 group-hover:text-red-700">Hapus Pelanggan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-red-500 font-normal leading-tight">Hapus permanen dari database</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Assign Jadwal -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Jadwalkan Survey</h3>
                    <button @click="showAssignModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitAssign">
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-2">Pilih Teknisi / Surveyor</label>
                            <div class="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-slate-50/50">
                                <label v-for="tech in technicians" :key="tech.id" class="flex items-center gap-3 cursor-pointer px-3 py-2 hover:bg-white rounded-md transition-colors border border-transparent hover:border-gray-100 hover:shadow-sm">
                                    <input type="checkbox" :value="tech.id" v-model="assignForm.technician_ids" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                    <span class="text-sm font-medium text-gray-700">{{ tech.name }}</span>
                                    <span class="text-xs text-gray-400 font-normal">({{ tech.role || 'Karyawan' }})</span>
                                </label>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5">* Anda dapat memilih lebih dari satu teknisi/karyawan (tidak dibatasi area)</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Survey</label>
                                <input v-model="assignForm.scheduled_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" required />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Waktu (Jam)</label>
                                <input v-model="assignForm.scheduled_time" type="time" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" required />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Catatan (Opsional)</label>
                            <textarea v-model="assignForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="assignForm.processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ assignForm.processing ? 'Menyimpan...' : 'Tugaskan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Reschedule Survey -->
        <div v-if="showRescheduleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-amber-100 text-amber-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                        Reschedule Survey
                    </h3>
                    <button @click="showRescheduleModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitReschedule">
                    <div class="p-5 space-y-4">
                        <!-- Info Pelanggan -->
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                            <p class="text-xs text-amber-700 font-medium">Pelanggan: <strong class="text-amber-900">{{ rescheduleCustomer?.name }}</strong></p>
                            <p v-if="rescheduleCustomer?.technician_schedules?.length" class="text-xs text-amber-600 mt-1">
                                Jadwal saat ini: {{ rescheduleCustomer.technician_schedules[0].scheduled_date }} {{ rescheduleCustomer.technician_schedules[0].scheduled_time }} — {{ rescheduleCustomer.technician_schedules[0].technician?.name }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-2">Ganti Petugas / Surveyor</label>
                            <div class="grid grid-cols-1 gap-2 max-h-48 overflow-y-auto border border-amber-200 rounded-lg p-2 bg-amber-50/30">
                                <label v-for="tech in technicians" :key="tech.id" class="flex items-center gap-3 cursor-pointer px-3 py-2 hover:bg-white rounded-md transition-colors border border-transparent hover:border-amber-100 hover:shadow-sm">
                                    <input type="checkbox" :value="tech.id" v-model="rescheduleForm.technician_ids" class="rounded border-amber-300 text-amber-600 focus:ring-amber-500 w-4 h-4">
                                    <span class="text-sm font-medium text-gray-700">{{ tech.name }}</span>
                                    <span class="text-xs text-gray-400 font-normal">({{ tech.role || 'Karyawan' }})</span>
                                </label>
                            </div>
                            <p class="text-[10px] text-amber-500/70 mt-1.5">* Anda dapat memilih lebih dari satu teknisi/karyawan (tidak dibatasi area)</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal Baru</label>
                                <input v-model="rescheduleForm.scheduled_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-amber-500" required />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">Waktu Baru</label>
                                <input v-model="rescheduleForm.scheduled_time" type="time" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-amber-500" required />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Catatan / Alasan Reschedule</label>
                            <textarea v-model="rescheduleForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-amber-500" placeholder="Contoh: Petugas berhalangan, jadwal bentrok, dll."></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showRescheduleModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="rescheduleForm.processing" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium transition-colors shadow-sm focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                            {{ rescheduleForm.processing ? 'Menyimpan...' : 'Simpan Reschedule' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Laporan Survey -->
        <div v-if="showReportModal" @click.self="closeReportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ isEditingSurvey ? 'Edit Laporan Survey' : 'Buat Laporan Survey' }}</h3>
                        <p v-if="activeCustomer" class="text-xs text-gray-500 mt-0.5">
                            Pelanggan: <span class="font-semibold text-gray-800">{{ activeCustomer.name }}</span> <span v-if="activeCustomer.address">({{ activeCustomer.address }})</span>
                        </p>
                    </div>
                    <button type="button" @click="closeReportModal" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitReport">
                    <!-- General Error Alert -->
                    <div v-if="Object.keys(reportForm.errors).length > 0" class="mx-5 mt-5 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                <p class="text-xs font-bold text-red-800">Terdapat kesalahan:</p>
                                <ul class="list-disc list-inside text-xs text-red-600 mt-1">
                                    <li v-for="(error, key) in reportForm.errors" :key="key">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Petugas / Surveyor Pelaksana <span class="text-red-500">*</span></label>
                            <select v-model="reportForm.surveyor_id" required class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500">
                                <option value="" disabled>-- Pilih Petugas Surveyor --</option>
                                <option v-for="tech in technicians" :key="tech.id" :value="tech.id">
                                    {{ tech.name }} ({{ tech.role || 'Karyawan' }})
                                </option>
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1">Bebas memilih karyawan dari seluruh area</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-medium text-gray-500">Pilih ODP (Jika Feasible)</label>
                                <button type="button" @click="findNearestOdp" :disabled="isFindingOdp" class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1 disabled:opacity-50 transition-colors">
                                    <svg v-if="isFindingOdp" class="animate-spin w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg v-else class="w-3 h-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ isFindingOdp ? 'Mencari Lokasi...' : 'Radar ODP Terdekat' }}
                                </button>
                            </div>
                            <select v-model="reportForm.odp_id" :required="reportForm.feasibility === 'feasible'" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih ODP --</option>
                                <template v-if="sortedOdps.length === 0">
                                    <option value="" disabled>⚠️ Tidak ada ODP di area {{ activeCustomer?.area || 'ini' }}</option>
                                </template>
                                <template v-else>
                                    <option v-for="odp in sortedOdps" :key="odp.id" :value="odp.id" :disabled="(odp.total_ports - odp.used_ports) <= 0">
                                        {{ odp.name }} 
                                        {{ odp.distance !== undefined && odp.distance < 9999999 ? `(${Math.round(odp.distance)}m)` : `(${odp.odc?.name || 'ODC'})` }} 
                                        - {{ (odp.total_ports - odp.used_ports) <= 0 ? 'FULL' : `Sisa ${Math.max(0, odp.total_ports - odp.used_ports)} Port` }}
                                    </option>
                                </template>
                            </select>
                            
                            <!-- Alert jika tidak ada ODP di area pelanggan -->
                            <div v-if="sortedOdps.length === 0" class="mt-2 p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <p class="font-bold text-amber-900">Belum Ada ODP di Area {{ activeCustomer?.area || 'Pelanggan' }}:</p>
                                    <p class="text-[11px] text-amber-800/90 mt-0.5 leading-relaxed">
                                        Tidak ada unit ODP yang terdaftar di area ini. ODP dari wilayah lain (seperti BC 3) <b>tidak ditampilkan</b> agar tidak terjadi salah penarikan jalur fiber optik. Silakan daftarkan ODP untuk area ini terlebih dahulu, atau pilih <b>Unfeasible (Tidak Layak)</b> jika jaringan belum tersedia.
                                    </p>
                                </div>
                            </div>
                            <p v-if="nearestOdpMsg" :class="nearestOdpMsg.includes('⚠️') ? 'text-yellow-600' : 'text-emerald-600'" class="mt-1 text-[10px]">{{ nearestOdpMsg }}</p>
                        </div>
                        
                        <div v-if="reportForm.odp_id">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Pilih Port ODP (Reservasi)</label>
                            <select v-model="reportForm.port_number" :required="reportForm.feasibility === 'feasible'" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih Port --</option>
                                <option v-for="port in availablePortNumbers" :key="port" :value="port">
                                    Port {{ port }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jarak Kabel (Meter)</label>
                            <input v-model="reportForm.distance_meters" type="number" :required="reportForm.feasibility === 'feasible'" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" placeholder="150" />
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-900">Dokumentasi Survey</label>
                                <button type="button" @click="addPhoto" class="px-2 py-1 text-xs bg-blue-600/20 text-blue-400 hover:bg-blue-600 hover:text-gray-900 rounded transition-colors flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Tambah
                                </button>
                            </div>
                            
                            <div class="space-y-3">
                                <div v-for="(photo, index) in reportForm.photos" :key="index" class="flex items-start gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm transition-all hover:border-blue-200 hover:bg-blue-50/30">
                                    <div class="flex-1 space-y-3">
                                        <input v-model="photo.label" type="text" class="w-full bg-transparent border-b border-gray-300 px-1 py-1.5 text-sm font-medium text-gray-900 focus:border-blue-500 focus:outline-none placeholder-gray-400 transition-colors" placeholder="Label Foto (Opsional)" />
                                        <input type="file" @change="handlePhotoChange(index, $event)" accept="image/*" class="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-gray-300 file:text-xs file:font-medium file:bg-white file:text-gray-700 hover:file:bg-gray-50 cursor-pointer transition-colors" />
                                    </div>
                                    <div v-if="photo.previewUrl" class="w-16 h-16 rounded-lg overflow-hidden shrink-0 group relative border border-gray-200 shadow-sm">
                                        <img :src="photo.previewUrl" class="w-full h-full object-cover" />
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-1 transition-opacity">
                                            <a :href="photo.previewUrl" target="_blank" class="text-white hover:text-blue-300 p-1" title="Lihat Penuh">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </a>
                                            <a :href="photo.previewUrl" :download="photo.file?.name || 'download'" class="text-white hover:text-blue-300 p-1" title="Download">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                    <button v-if="index > 3" type="button" @click="removePhoto(index)" class="p-1 text-gray-500 hover:text-red-400 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Hasil Kelayakan <span class="text-red-500">*</span></label>
                            <div class="flex gap-4 mt-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="reportForm.feasibility" value="feasible" class="text-blue-500 bg-white border-gray-200 focus:ring-blue-500" required>
                                    <span class="text-sm text-gray-600">Feasible (Layak)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="reportForm.feasibility" value="not_feasible" class="text-blue-500 bg-white border-gray-200 focus:ring-blue-500" required>
                                    <span class="text-sm text-gray-600">Unfeasible (Tidak Layak)</span>
                                </label>
                            </div>
                            <p v-if="reportForm.errors.feasibility" class="text-red-500 text-xs mt-1">{{ reportForm.errors.feasibility }}</p>
                        </div>

                        <div class="pt-4 border-t border-gray-200">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Keterangan Tambahan</label>
                            <textarea v-model="reportForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="closeReportModal" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="reportForm.processing" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ reportForm.processing ? 'Menyimpan...' : 'Simpan Laporan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Detail Survey -->
        <Teleport to="body">
            <div v-if="showViewModal && selectedCustomer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="closeViewModal"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <div class="sticky top-0 bg-white/90 backdrop-blur-xl border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-indigo-500/20 text-indigo-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </span>
                            Detail Survey Pelanggan
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
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Telepon / WhatsApp</p>
                                <p class="text-sm text-gray-900">{{ selectedCustomer.phone }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <span class="inline-flex px-2 py-1 bg-blue-500/20 text-blue-500 rounded-md border border-blue-500/30">
                                        {{ selectedCustomer.area || '-' }}
                                    </span>
                                </p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Paket / Layanan</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <span v-if="selectedCustomer.service_status === 'free_home'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-500/20 text-amber-700 rounded-md border border-amber-500/30 font-bold">
                                        <span>🏠</span> Free Home
                                    </span>
                                    <span v-else-if="selectedCustomer.service_status === 'gratis'" class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-500/20 text-emerald-700 rounded-md border border-emerald-500/30 font-bold">
                                        <span>🎁</span> Gratis
                                    </span>
                                    <span v-else class="inline-flex px-2 py-1 bg-purple-500/20 text-purple-500 rounded-md border border-purple-500/30">
                                        {{ selectedCustomer.package?.name || 'Belum dipilih' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div class="space-y-1">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Alamat Lengkap</p>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ selectedCustomer.address }}</p>
                            <div v-if="selectedCustomer.latitude || selectedCustomer.longitude" class="mt-2 flex flex-col gap-1.5">
                                <div class="flex flex-wrap items-center gap-1.5 font-mono text-xs">
                                    <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">Lat: {{ selectedCustomer.latitude || '-' }}</span>
                                    <span class="bg-gray-100 text-gray-800 px-2 py-0.5 rounded border border-gray-200">Long: {{ selectedCustomer.longitude || '-' }}</span>
                                </div>
                                <a v-if="selectedCustomer.latitude && selectedCustomer.longitude" :href="`https://www.google.com/maps?q=${selectedCustomer.latitude},${selectedCustomer.longitude}`" target="_blank" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-900 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5 self-start">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    Buka Lokasi di Maps
                                </a>
                            </div>
                        </div>

                        <hr class="border-gray-100">

                        <!-- Status Survey / Laporan -->
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Hasil / Status Survey
                            </h4>
                            
                            <div v-if="selectedCustomer.surveys?.length" class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Kelayakan</p>
                                        <StatusBadge :status="selectedCustomer.surveys[0].feasibility" />
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">ODP Terhubung</p>
                                        <p class="text-sm text-gray-900 font-medium">{{ selectedCustomer.surveys[0].odp?.name || '-' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Jarak Kabel</p>
                                        <p class="text-sm text-gray-900 font-medium">{{ selectedCustomer.surveys[0].distance_meters ? `${Math.round(selectedCustomer.surveys[0].distance_meters)} Meter` : '-' }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Surveyor</p>
                                        <p class="text-sm text-gray-900 font-medium">{{ selectedCustomer.surveys[0].surveyor?.name || '-' }}</p>
                                    </div>
                                </div>
                                <div v-if="selectedCustomer.surveys[0].notes" class="mt-4 space-y-1">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Catatan</p>
                                    <p class="text-sm text-gray-700 italic">{{ selectedCustomer.surveys[0].notes }}</p>
                                </div>
                                
                                <!-- Dokumentasi Foto -->
                                <div v-if="selectedCustomer.surveys[0].photos && selectedCustomer.surveys[0].photos.length > 0" class="mt-5 border-t border-gray-200 pt-4">
                                    <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Dokumentasi Survey</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 sm:grid-cols-3 gap-3">
                                        <div v-for="(photo, idx) in selectedCustomer.surveys[0].photos" :key="idx" class="relative group rounded-lg overflow-hidden border border-gray-200 shadow-sm bg-gray-100 aspect-square">
                                            <img :src="`/storage/${photo.path}`" :alt="photo.label" class="w-full h-full object-cover" />
                                            
                                            <!-- Overlay -->
                                            <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-between p-2">
                                                <p class="text-xs text-white font-medium truncate drop-shadow-md">{{ photo.label }}</p>
                                                <div class="flex gap-2">
                                                    <a :href="`/storage/${photo.path}`" target="_blank" class="flex-1 bg-white/20 hover:bg-white/40 text-white rounded p-1.5 flex justify-center items-center backdrop-blur-sm transition-colors" title="Lihat Penuh">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    </a>
                                                    <a :href="`/storage/${photo.path}`" download class="flex-1 bg-blue-600/80 hover:bg-blue-600 text-white rounded p-1.5 flex justify-center items-center backdrop-blur-sm transition-colors" title="Download">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else-if="selectedCustomer.technician_schedules?.length" class="bg-blue-50/50 rounded-xl p-4 border border-blue-100">
                                <div class="flex gap-3 items-start">
                                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-semibold text-gray-900 mb-1">Telah Dijadwalkan (Belum Ada Laporan)</h5>
                                        <p class="text-sm text-gray-600">Teknisi: <strong class="text-gray-900">{{ selectedCustomer.technician_schedules?.map(s => s.technician?.name).filter(Boolean).join(', ') || '-' }}</strong></p>
                                        <p class="text-sm text-gray-600">Waktu: <strong class="text-gray-900">{{ selectedCustomer.technician_schedules[0].scheduled_date }} {{ selectedCustomer.technician_schedules[0].scheduled_time }}</strong></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-else class="text-sm text-gray-500 italic p-4 bg-gray-50 border border-gray-200 rounded-xl text-center">
                                Belum ada tindakan atau laporan survey.
                            </div>
                        </div>

                    </div>
                    
                    <div class="sticky bottom-0 bg-white/90 backdrop-blur-xl border-t border-gray-200 px-6 py-4 flex justify-end">
                        <button @click="closeViewModal" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl text-sm font-medium transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    <!-- Modal Konfirmasi Ready Install -->
        <Teleport to="body">
            <div v-if="showInstallModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showInstallModal = false"></div>
                <div class="relative glass-card p-6 max-w-md w-full max-h-[90vh] overflow-y-auto animate-fade-in-up border border-emerald-500/30">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Lanjutkan ke Instalasi?</h3>
                            <p class="text-sm text-emerald-400 font-medium">Pelanggan: {{ installCustomer?.name }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        Laporan survey untuk pelanggan ini menyatakan <strong class="text-gray-900">layak (feasible)</strong>. Anda akan diarahkan ke menu <strong>Pasang / Aktif</strong> untuk menjadwalkan teknisi instalasi jaringan.
                    </p>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200/50">
                        <button @click="showInstallModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm">Batal</button>
                        <button @click="proceedToInstall" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Konfirmasi Hapus -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl p-6 max-w-sm w-full max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Hapus Pelanggan?</h3>
                            <p class="text-sm text-red-500 font-medium">{{ deleteCustomer?.name }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                        Semua data terkait pelanggan ini (jadwal survey, hasil survey, jadwal teknisi) akan <strong class="text-red-600">dihapus permanen</strong>. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm">Batal</button>
                        <button @click="executeDelete" :disabled="isDeleting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Batal Pelanggan -->
        <Teleport to="body">
            <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-orange-900/60 backdrop-blur-sm cursor-pointer" @click="showCancelModal = false"></div>
                <div class="relative bg-white border border-orange-200 rounded-2xl shadow-2xl w-full max-w-md p-6 animate-fade-in-up">
                    <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-orange-50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2 text-center">
                        Batalkan Survey?
                    </h3>
                    <p class="text-sm text-gray-500 mb-4 text-center">
                        Pelanggan <strong>{{ cancelCustomer?.name }}</strong> akan dipindahkan ke menu "Dibatalkan" dengan status <span class="font-semibold text-rose-600">Batal Survey</span>.
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pembatalan <span class="text-red-500">*</span></label>
                        <input type="date" v-model="cancelDate" class="w-full rounded-xl border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200 transition-all text-sm" required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pembatalan <span class="text-red-500">*</span></label>
                        <textarea v-model="cancelReason" rows="3" class="w-full rounded-xl border-gray-300 focus:border-orange-500 focus:ring focus:ring-orange-200 transition-all text-sm" placeholder="Masukkan alasan pembatalan survey..." required></textarea>
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PipelineGuideCard from '@/Components/Customers/PipelineGuideCard.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import StatCard from '@/Components/StatCard.vue';

const props = defineProps({ 
    customers: Object, 
    availableOdps: Array, 
    technicians: Array, 
    areas: Array, 
    stats: Object, 
    filters: Object,
    openCustomer: Object,
});

const page = usePage();
const hasPermission = (...permissions) => {
    try {
        const user = page.props.auth?.user;
        if (!user) return false;
        if (user.role === 'admin' || user.role === 'Super Admin') return true;
        
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        if (roles.includes('admin') || roles.includes('Super Admin')) return true;

        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        return permissions.some(p => perms.includes(p));
    } catch (e) {
        return false;
    }
};

const isUserAssigned = (customer) => {
    const currentUserId = page.props.auth?.user?.id;
    if (!currentUserId || !customer?.technician_schedules) return false;
    return customer.technician_schedules.some(s => s.technician_id === currentUserId);
};
const selectedIds = ref([]);

const canDelete = computed(() => {
    return hasPermission('customers_survey_delete', 'customers_delete');
});

const canCancel = computed(() => {
    return hasPermission('customers_survey_cancel', 'customers_booking_delete', 'customers_delete');
});

function bulkDelete() {
    if (confirm(`Hapus ${selectedIds.value.length} data terpilih secara permanen?`)) {
        router.post('/customers/bulk-destroy', { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => selectedIds.value = []
        });
    }
}

const columns = [
    { key: 'actions', label: 'Aksi', class: 'whitespace-nowrap min-w-[170px]' },
    { key: 'name', label: 'Pelanggan', class: 'min-w-[200px] max-w-[280px]' },
    { key: 'odp', label: 'ODP Terdekat', class: 'whitespace-nowrap' },
    { key: 'distance', label: 'Jarak Kabel', class: 'whitespace-nowrap text-center' },
    { key: 'port', label: 'Port ODP', class: 'whitespace-nowrap' },
    { key: 'feasibility', label: 'Kelayakan', class: 'whitespace-nowrap' },
    { key: 'surveyor', label: 'Surveyor / Jadwal', class: 'whitespace-nowrap min-w-[130px]' },
];

const showViewModal = ref(false);
const selectedCustomer = ref(null);

function openViewModal(customer) {
    selectedCustomer.value = customer;
    showViewModal.value = true;
}

function closeViewModal() {
    showViewModal.value = false;
    setTimeout(() => {
        selectedCustomer.value = null;
    }, 300);
}

const selectedTechnician = ref(props.filters?.technician_id || '');
const selectedDate = ref(props.filters?.date || '');
const selectedArea = ref(props.filters?.area || props.filters?.area_id || '');
const selectedStatus = ref(props.filters?.tab || 'semua');

watch(() => props.filters, (newFilters) => {
    selectedTechnician.value = newFilters?.technician_id || '';
    selectedDate.value = newFilters?.date || '';
    selectedArea.value = newFilters?.area || newFilters?.area_id || '';
    selectedStatus.value = newFilters?.tab || 'semua';
}, { deep: true });

const buildSearchRoute = () => {
    return '/customers/survey';
};

const getTabUrl = (tab) => {
    let query = [];
    if (tab && tab !== 'semua') query.push('tab=' + tab);
    if (props.filters?.search) query.push('search=' + encodeURIComponent(props.filters.search));
    if (selectedTechnician.value) query.push('technician_id=' + selectedTechnician.value);
    if (selectedDate.value) query.push('date=' + selectedDate.value);
    if (selectedArea.value) query.push('area=' + encodeURIComponent(selectedArea.value));
    return '/customers/survey' + (query.length ? '?' + query.join('&') : '');
};

function applyFilters() {
    const currentSearch = typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('search') || props.filters?.search || undefined) : props.filters?.search;
    router.get('/customers/survey', {
        search: currentSearch || undefined,
        tab: selectedStatus.value === 'semua' ? undefined : selectedStatus.value,
        technician_id: selectedTechnician.value || undefined,
        date: selectedDate.value || undefined,
        area: selectedArea.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    selectedTechnician.value = '';
    selectedDate.value = '';
    selectedArea.value = '';
    router.get('/customers/survey', {
        tab: selectedStatus.value === 'semua' ? undefined : selectedStatus.value,
    }, { preserveState: true, preserveScroll: true });
}

// Assignment Modal Logic
const showAssignModal = ref(false);
const activeCustomer = ref(null);
const assignForm = useForm({
    technician_ids: [],
    scheduled_date: '',
    scheduled_time: '',
    notes: ''
});

function openAssignModal(customer) {
    activeCustomer.value = customer;
    assignForm.reset();
    
    // Pre-populate with existing survey schedule if any
    const existing = (customer.technician_schedules || []).filter(s => s.type === 'survey');
    if (existing.length > 0) {
        assignForm.technician_ids = existing.map(s => s.technician_id).filter(Boolean);
        assignForm.scheduled_date = existing[0].scheduled_date || new Date().toISOString().split('T')[0];
        assignForm.scheduled_time = (existing[0].scheduled_time || '10:00').substring(0, 5);
        assignForm.notes = existing[0].notes || '';
    } else {
        assignForm.technician_ids = [];
        assignForm.scheduled_date = new Date().toISOString().split('T')[0];
        assignForm.scheduled_time = '10:00';
        assignForm.notes = '';
    }
    showAssignModal.value = true;
}

function submitAssign() {
    assignForm.post(`/customers/${activeCustomer.value.id}/assign-survey`, {
        onSuccess: () => {
            showAssignModal.value = false;
        }
    });
}

// Reschedule Modal Logic
const showRescheduleModal = ref(false);
const rescheduleCustomer = ref(null);
const rescheduleForm = useForm({
    technician_ids: [],
    scheduled_date: '',
    scheduled_time: '',
    notes: ''
});

function openRescheduleModal(customer) {
    rescheduleCustomer.value = customer;
    rescheduleForm.reset();
    
    // Pre-fill with existing schedule data
    const schedules = customer.technician_schedules?.filter(s => s.type === 'survey');
    if (schedules && schedules.length > 0) {
        rescheduleForm.technician_ids = schedules.map(s => s.technician_id);
        rescheduleForm.scheduled_date = schedules[0].scheduled_date || new Date().toISOString().split('T')[0];
        rescheduleForm.scheduled_time = schedules[0].scheduled_time || '10:00';
    } else {
        rescheduleForm.scheduled_date = new Date().toISOString().split('T')[0];
        rescheduleForm.scheduled_time = '10:00';
    }
    
    showRescheduleModal.value = true;
}

function submitReschedule() {
    rescheduleForm.post(`/customers/${rescheduleCustomer.value.id}/reschedule-survey`, {
        onSuccess: () => {
            showRescheduleModal.value = false;
        }
    });
}

// Report Modal Logic
const showReportModal = ref(false);
const isEditingSurvey = ref(false);
const isFindingOdp = ref(false);
const nearestOdpMsg = ref('');
const sortedOdps = ref([...props.availableOdps]);

const reportForm = useForm({
    surveyor_id: '',
    odp_id: '',
    port_number: '',
    distance_meters: '',
    port_available: true,
    feasibility: 'feasible',
    notes: '',
    photos: []
});

const selectedOdp = computed(() => {
    return sortedOdps.value.find(o => o.id === reportForm.odp_id);
});

const availablePortNumbers = computed(() => {
    if (!selectedOdp.value) return [];
    let ports = [];
    let usedPortNumbers = [];
    if (selectedOdp.value.ports) {
        usedPortNumbers = selectedOdp.value.ports.filter(p => ['used', 'reserved', 'fault', 'stop'].includes(p.status)).map(p => p.port_number);
    }
    for (let i = 1; i <= selectedOdp.value.total_ports; i++) {
        if (!usedPortNumbers.includes(i)) {
            ports.push(i);
        }
    }
    return ports;
});

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // metres
    const p1 = lat1 * Math.PI/180;
    const p2 = lat2 * Math.PI/180;
    const dp = (lat2-lat1) * Math.PI/180;
    const dl = (lon2-lon1) * Math.PI/180;

    const a = Math.sin(dp/2) * Math.sin(dp/2) +
              Math.cos(p1) * Math.cos(p2) *
              Math.sin(dl/2) * Math.sin(dl/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c; // in metres
}

function findNearestOdp() {
    isFindingOdp.value = true;
    nearestOdpMsg.value = '';
    
    if (sortedOdps.value.length === 0) {
        nearestOdpMsg.value = `⚠️ Tidak ada ODP terdaftar di area ${activeCustomer.value?.area || 'pelanggan ini'}.`;
        isFindingOdp.value = false;
        return;
    }

    if (!navigator.geolocation) {
        alert("Geolocation tidak didukung oleh browser Anda");
        isFindingOdp.value = false;
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const userLat = position.coords.latitude;
            const userLon = position.coords.longitude;
            
            // Calculate distance for filtered ODPs in the same area
            const odpsWithDistance = sortedOdps.value.map(odp => {
                let distance = 9999999;
                if (odp.latitude && odp.longitude) {
                    distance = calculateDistance(userLat, userLon, parseFloat(odp.latitude), parseFloat(odp.longitude));
                }
                return { ...odp, distance };
            });
            
            // Sort by distance
            odpsWithDistance.sort((a, b) => a.distance - b.distance);
            sortedOdps.value = odpsWithDistance;
            
            // Auto select nearest if reasonable (e.g. < 5km)
            const nearest = odpsWithDistance[0];
            if (nearest && nearest.distance < 5000) {
                reportForm.odp_id = nearest.id;
                
                // Pre-fill distance_meters if it's empty
                if (!reportForm.distance_meters) {
                    reportForm.distance_meters = Math.round(nearest.distance);
                }
                
                nearestOdpMsg.value = `📍 ODP Terdekat di Area ${activeCustomer.value?.area || ''}: ${nearest.name} berjarak ${Math.round(nearest.distance)} meter.`;
            } else {
                nearestOdpMsg.value = `⚠️ Tidak ada ODP terdekat dalam radius 5km di area ini.`;
            }
            
            isFindingOdp.value = false;
        },
        (error) => {
            alert("Gagal mendapatkan lokasi. Pastikan izin GPS diberikan pada browser.");
            isFindingOdp.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
}

function closeReportModal() {
    showReportModal.value = false;
    try {
        const url = new URL(window.location.href);
        if (url.searchParams.has('open_report')) {
            url.searchParams.delete('open_report');
            window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
        }
    } catch (e) {
        // ignore
    }
}

function openReportModal(customer) {
    if (!customer) return;
    isEditingSurvey.value = false;
    activeCustomer.value = customer;
    reportForm.reset();
    
    // Filter ODPs strictly based on customer's area
    const customerAreaId = customer.area_id;
    const customerAreaName = (customer.area || customer.area_model?.name || '').toLowerCase().trim();

    const filteredOdps = (props.availableOdps || []).filter(odp => {
        if (!customerAreaId && !customerAreaName) return false;

        const odpAreaId = odp.area_id ?? odp.odc?.area_id;
        const odpAreaName = (odp.area?.name || odp.area_name || odp.odc?.area?.name || '').toLowerCase().trim();

        if (customerAreaId && odpAreaId && Number(odpAreaId) === Number(customerAreaId)) {
            return true;
        }
        if (customerAreaName && odpAreaName && odpAreaName === customerAreaName) {
            return true;
        }
        return false;
    });
        
    // STRICT: Only show ODPs belonging to the customer's area. Do NOT fallback to other areas!
    sortedOdps.value = filteredOdps;
    nearestOdpMsg.value = '';
    
    // Auto-select surveyor if already scheduled
    const schedule = customer.technician_schedules?.find(s => s.type === 'survey' && s.status === 'scheduled');
    if (schedule) {
        reportForm.surveyor_id = schedule.technician_id;
    } else if (page.props.auth?.user?.id) {
        reportForm.surveyor_id = page.props.auth.user.id;
    }
    
    // Default documentation photos
    reportForm.photos = [
        { label: 'Foto Selfie Pelanggan & Petugas', file: null },
        { label: 'Foto Rumah Pelanggan', file: null },
        { label: 'Foto Jalan', file: null },
        { label: 'Foto ODP', file: null }
    ];
    
    showReportModal.value = true;
}

function openEditReportModal(customer) {
    if (!customer) return;
    isEditingSurvey.value = true;
    activeCustomer.value = customer;
    reportForm.reset();
    
    // Filter ODPs strictly based on customer's area (or keep current ODP if already assigned)
    const customerAreaId = customer.area_id;
    const customerAreaName = (customer.area || customer.area_model?.name || '').toLowerCase().trim();
    const survey = customer.surveys && customer.surveys.length > 0 ? customer.surveys[0] : null;
    const existingOdpId = survey?.odp_id;

    const filteredOdps = (props.availableOdps || []).filter(odp => {
        // Keep currently selected ODP if already saved on this survey
        if (existingOdpId && Number(odp.id) === Number(existingOdpId)) {
            return true;
        }
        if (!customerAreaId && !customerAreaName) return false;

        const odpAreaId = odp.area_id ?? odp.odc?.area_id;
        const odpAreaName = (odp.area?.name || odp.area_name || odp.odc?.area?.name || '').toLowerCase().trim();

        if (customerAreaId && odpAreaId && Number(odpAreaId) === Number(customerAreaId)) {
            return true;
        }
        if (customerAreaName && odpAreaName && odpAreaName === customerAreaName) {
            return true;
        }
        return false;
    });

    // STRICT: Only show ODPs belonging to customer's area
    sortedOdps.value = filteredOdps;
    nearestOdpMsg.value = '';

    if (survey) {
        reportForm.surveyor_id = survey.surveyor_id;
        reportForm.odp_id = survey.odp_id || '';
        reportForm.feasibility = survey.feasibility;
        reportForm.port_number = survey.port_number || '';
        reportForm.distance_meters = survey.distance_meters ? Math.round(survey.distance_meters) : '';
        reportForm.port_available = survey.port_available;
        reportForm.notes = survey.notes || '';
        
        if (survey.photos && survey.photos.length > 0) {
            reportForm.photos = survey.photos.map(p => ({
                label: p.label,
                file: null,
                previewUrl: p.path ? '/storage/' + p.path : null
            }));
        } else {
            reportForm.photos = [
                { label: 'Foto Selfie Pelanggan & Petugas', file: null },
                { label: 'Foto Rumah Pelanggan', file: null },
                { label: 'Foto Jalan', file: null },
                { label: 'Foto ODP', file: null }
            ];
        }
    } else if (page.props.auth?.user?.id) {
        reportForm.surveyor_id = page.props.auth.user.id;
    }
    showReportModal.value = true;
}

function addPhoto() {
    reportForm.photos.push({ label: 'Foto Tambahan', file: null });
}

function removePhoto(index) {
    reportForm.photos.splice(index, 1);
}

function handlePhotoChange(index, event) {
    const file = event.target.files[0];
    reportForm.photos[index].file = file;
    if (file) {
        reportForm.photos[index].previewUrl = URL.createObjectURL(file);
    } else {
        reportForm.photos[index].previewUrl = null;
    }
}

function submitReport() {
    const routeName = isEditingSurvey.value 
        ? `/customers/${activeCustomer.value.id}/update-survey` 
        : `/customers/${activeCustomer.value.id}/store-survey`;
        
    reportForm.transform((data) => ({
        ...data,
        photos: (data.photos || []).filter(p => p.file || p.path)
    })).post(routeName, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeReportModal();
        }
    });
}

function rollbackSurvey(customer) {
    if (!customer?.id) return;

    const confirmed = window.confirm(
        `Ulangi laporan survey untuk ${customer.name}?\n\nLaporan, pilihan ODP/port, jarak, redaman, dan foto sebelumnya akan diarsipkan. Jadwal serta petugas survey tetap dipertahankan agar dapat langsung mengisi laporan baru.`
    );
    if (!confirmed) return;

    router.post(`/customers/${customer.id}/rollback-survey`, {}, {
        preserveScroll: true,
    });
}

function checkAutoOpenReport() {
    const cust = props.openCustomer || (props.customers?.data?.find(c => c.id == (new URLSearchParams(window.location.search)).get('open_report')));
    if (cust && hasPermission('customers_survey_report')) {
        if (cust.surveys && cust.surveys.length > 0) {
            openEditReportModal(cust);
        } else {
            openReportModal(cust);
        }
    }
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
    const dropdownHeight = 430;
    const dropdownWidth = 288; // w-72 = 288px

    let top = rect.bottom + 6;
    if (top + dropdownHeight > window.innerHeight) {
        if (rect.top > dropdownHeight) {
            top = Math.max(10, rect.top - dropdownHeight - 6);
        } else {
            top = Math.max(10, window.innerHeight - dropdownHeight - 10);
        }
    }

    let left = rect.left;
    if (left + dropdownWidth > window.innerWidth - 10) {
        left = Math.max(10, window.innerWidth - dropdownWidth - 10);
    }
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
    checkAutoOpenReport();
    window.addEventListener('scroll', handleWindowChange, { passive: true });
    window.addEventListener('resize', handleWindowChange);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleWindowChange);
    window.removeEventListener('resize', handleWindowChange);
});

watch(() => props.openCustomer, (newCust) => {
    if (newCust && hasPermission('customers_survey_report')) {
        if (newCust.surveys && newCust.surveys.length > 0) {
            openEditReportModal(newCust);
        } else {
            openReportModal(newCust);
        }
    }
});

// Ready Install Modal Logic
const showInstallModal = ref(false);
const installCustomer = ref(null);

function openInstallModal(customer) {
    installCustomer.value = customer;
    showInstallModal.value = true;
}

function proceedToInstall() {
    showInstallModal.value = false;
    router.post(`/customers/${installCustomer.value.id}/mark-installing`, {}, {
        preserveScroll: true
    });
}

// Delete Logic
const showDeleteModal = ref(false);
const deleteCustomer = ref(null);
const isDeleting = ref(false);

function confirmDelete(customer) {
    deleteCustomer.value = customer;
    showDeleteModal.value = true;
}

function executeDelete() {
    isDeleting.value = true;
    router.post(`/customers/${deleteCustomer.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
}

// Batal Pelanggan Logic
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
        cancel_stage: 'survey',
        cancel_reason: cancelReason.value,
        cancel_date: cancelDate.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
            cancelCustomer.value = null;
            cancelReason.value = '';
        },
        onFinish: () => {
            isCanceling.value = false;
        }
    });
}
</script>
