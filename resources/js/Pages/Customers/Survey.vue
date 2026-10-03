<template>
    <AppLayout title="Data Survey" subtitle="Manajemen jadwal & pelaporan hasil survey ODP">
        
        <!-- Statistik Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard 
                title="Belum Dijadwalkan" 
                :value="stats?.jadwalkan || 0" 
                icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" 
                color="blue" 
            />
            <StatCard 
                title="Menunggu Laporan" 
                :value="stats?.laporan || 0" 
                icon="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" 
                color="indigo" 
            />
            <StatCard 
                title="Ready Install (Feasible)" 
                :value="stats?.ready || 0" 
                icon="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" 
                color="emerald" 
            />
            <StatCard 
                title="Unfeasible" 
                :value="stats?.unfeasible || 0" 
                icon="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" 
                color="rose" 
            />
        </div>

        <!-- Tabs removed (Moved to sidebar) -->

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan..."
            :searchRoute="buildSearchRoute()"
            selectable
            v-model:selected="selectedIds"
        >
            <template #filters>
                <!-- Filter Teknisi -->
                <select v-model="selectedTechnician" class="form-select bg-white border border-gray-200 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
                    <option value="">Semua Teknisi / Surveyor</option>
                    <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                </select>
                
                <!-- Filter Tanggal -->
                <input type="date" v-model="selectedDate" class="form-input bg-white border border-gray-200 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 w-full sm:w-auto" />
                
                <!-- Filter Area / Wilayah -->
                <select v-model="selectedArea" class="form-select bg-white border border-gray-200 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
                    <option value="">Semua Area / Wilayah</option>
                    <option v-for="area in areas" :key="area" :value="area">{{ area }}</option>
                </select>

                <!-- Filter Status -->
                <select v-model="selectedStatus" class="form-select bg-white border border-gray-200 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
                    <option value="semua">Semua Status</option>
                    <option value="jadwalkan">Belum Dijadwalkan</option>
                    <option value="laporan">Menunggu Laporan</option>
                    <option value="ready">Ready Install</option>
                    <option value="unfeasible">Unfeasible</option>
                </select>

                <!-- Tombol Tampilkan & Reset -->
                <div class="flex items-center gap-2">
                    <button @click="applyFilters" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-gray-900 rounded-lg text-sm font-medium transition-colors shadow-[0_0_10px_rgba(37,99,235,0.3)] whitespace-nowrap">
                        Tampilkan
                    </button>
                    <button @click="resetFilters" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-900 rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                        Reset
                    </button>
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
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-gray-900 shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-gray-900 font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500">{{ row.address }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span v-if="row.surveys?.length" class="text-xs text-cyan-400 font-mono">
                        {{ row.surveys[0]?.odp?.name || '-' }}
                    </span>
                    <span v-else class="text-xs text-gray-600">Belum survey</span>
                </td>
                <td>
                    <span v-if="row.surveys?.length && row.surveys[0]?.distance_meters" class="text-xs text-gray-700 font-medium">
                        {{ row.surveys[0].distance_meters }} m
                    </span>
                    <span v-else class="text-gray-600 text-xs">-</span>
                </td>
                <td>
                    <span v-if="row.surveys?.length && row.surveys[0]?.port_available" class="badge badge-active">Tersedia</span>
                    <span v-else-if="row.surveys?.length" class="badge badge-terminated">Penuh</span>
                    <span v-else class="text-gray-600 text-xs">-</span>
                </td>
                <td>
                    <StatusBadge v-if="row.surveys?.length" :status="row.surveys[0]?.feasibility || 'feasible'" />
                    <span v-else class="text-gray-600 text-xs">-</span>
                </td>
                <td>
                    <div v-if="row.surveys?.length">
                        <span class="text-xs text-gray-600">{{ row.surveys[0].surveyor?.name || '-' }}</span>
                        <p class="text-[10px] text-gray-500">Selesai</p>
                    </div>
                    <div v-else-if="row.technician_schedules?.length">
                        <span class="text-xs text-blue-400">{{ row.technician_schedules[0].technician?.name || '-' }}</span>
                        <p class="text-[10px] text-blue-500/70">{{ row.technician_schedules[0].scheduled_date }} {{ row.technician_schedules[0].scheduled_time }}</p>
                    </div>
                    <span v-else class="text-gray-600 text-xs">Belum ada jadwal</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-2">
                    <template v-if="row.status === 'survey'">
                        <!-- Belum Dijadwalkan -->
                        <button v-if="!row.surveys?.length && !row.technician_schedules?.length && hasPermission('customers_survey_assign')" @click="openAssignModal(row)" class="btn-primary py-1.5 px-3 text-xs">
                            Jadwalkan
                        </button>
                        
                        <!-- Sudah Dijadwalkan, tapi belum disurvey -->
                        <template v-else-if="!row.surveys?.length && row.technician_schedules?.length">
                            <!-- Jika punya akses Isi Laporan (Teknisi) -->
                            <button v-if="hasPermission('customers_survey_report')" @click="openReportModal(row)" class="btn-success py-1.5 px-3 text-xs">
                                Isi Laporan
                            </button>
                            <!-- Jika tidak punya akses Isi Laporan (CS/Admin tanpa akses) -->
                            <span v-else class="px-3 py-1.5 bg-blue-50 text-blue-500 border border-blue-200 rounded-lg text-xs font-medium flex items-center gap-1 cursor-default" title="Menunggu teknisi mengisi laporan survey">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Menunggu Survey
                            </span>
                            
                            <!-- Tombol Reschedule (jika punya akses) -->
                            <button v-if="hasPermission('customers_survey_reschedule')" @click="openRescheduleModal(row)" class="flex items-center gap-1 px-2.5 py-1.5 bg-amber-50 text-amber-600 border border-amber-200 hover:bg-amber-100 hover:text-amber-700 rounded-lg text-xs font-medium transition-colors" title="Reschedule: Ganti tanggal, waktu, atau petugas survey">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Reschedule
                            </button>
                        </template>

                        <!-- Sudah Disurvey & Feasible -->
                        <button v-else-if="row.surveys?.length && row.surveys[0]?.feasibility === 'feasible' && hasPermission('customers_survey_mark_ready')" @click="openInstallModal(row)" class="flex items-center gap-1 px-3 py-1.5 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/30 hover:text-emerald-300 rounded-lg text-xs font-bold shadow-[0_0_10px_rgba(16,185,129,0.2)] transition-colors" title="Laporan Selesai: Klik untuk menjadwalkan instalasi di menu Pasang/Aktif">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            Ready Install
                        </button>

                        <!-- Sudah Disurvey & Not Feasible -->
                        <div v-else-if="row.surveys?.length && row.surveys[0]?.feasibility === 'not_feasible'" class="flex items-center gap-1 px-3 py-1.5 bg-rose-500/20 text-rose-400 border border-rose-500/30 rounded-lg text-xs font-bold cursor-not-allowed" title="Pelanggan tidak layak/tidak tercover jaringan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Unfeasible
                        </div>
                    </template>
                    <template v-else>
                        <div class="mr-2">
                            <span class="px-3 py-1.5 bg-gray-100 text-gray-500 rounded-lg text-xs font-bold border border-gray-200 flex items-center gap-1" title="Telah masuk proses Pemasangan/Aktif">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Selesai
                            </span>
                        </div>
                    </template>
                    <button @click="openViewModal(row)" class="p-2 text-gray-500 hover:text-gray-900 hover:bg-white rounded-lg transition-colors border border-transparent hover:border-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <button @click="confirmDelete(row)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-200" title="Hapus Pelanggan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </DataTable>

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
                                </label>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5">* Anda dapat memilih lebih dari satu teknisi</p>
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
                                </label>
                            </div>
                            <p class="text-[10px] text-amber-500/70 mt-1.5">* Anda dapat memilih lebih dari satu teknisi</p>
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
        <div v-if="showReportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Buat Laporan Survey</h3>
                    <button @click="showReportModal = false" class="text-gray-500 hover:text-gray-900">
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
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-medium text-gray-500">Pilih ODP (Jika Feasible)</label>
                                <button type="button" @click="findNearestOdp" :disabled="isFindingOdp" class="text-xs text-blue-400 hover:text-blue-300 flex items-center gap-1 disabled:opacity-50 transition-colors">
                                    <svg v-if="isFindingOdp" class="animate-spin w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <svg v-else class="w-3 h-3 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    {{ isFindingOdp ? 'Mencari Lokasi...' : 'Radar ODP Terdekat' }}
                                </button>
                            </div>
                            <select v-model="reportForm.odp_id" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih ODP --</option>
                                <template v-if="sortedOdps.length === 0">
                                    <option value="" disabled>Tidak ada ODP di area ini</option>
                                </template>
                                <template v-else>
                                    <option v-for="odp in sortedOdps" :key="odp.id" :value="odp.id" :disabled="(odp.total_ports - odp.used_ports) <= 0">
                                        {{ odp.name }} 
                                        {{ odp.distance !== undefined && odp.distance < 9999999 ? `(${Math.round(odp.distance)}m)` : `(${odp.odc?.name})` }} 
                                        - {{ (odp.total_ports - odp.used_ports) <= 0 ? 'FULL' : `Sisa ${Math.max(0, odp.total_ports - odp.used_ports)} Port` }}
                                    </option>
                                </template>
                            </select>
                            <p v-if="nearestOdpMsg" :class="nearestOdpMsg.includes('⚠️') ? 'text-yellow-400' : 'text-emerald-400'" class="mt-1 text-[10px]">{{ nearestOdpMsg }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Jarak Kabel (Meter)</label>
                            <input v-model="reportForm.distance_meters" type="number" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500" placeholder="150" />
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
                                        <input v-model="photo.label" type="text" class="w-full bg-transparent border-b border-gray-300 px-1 py-1.5 text-sm font-medium text-gray-900 focus:border-blue-500 focus:outline-none placeholder-gray-400 transition-colors" placeholder="Label Foto" />
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
                                    <button v-if="index > 2" type="button" @click="removePhoto(index)" class="p-1 text-gray-500 hover:text-red-400 transition-colors">
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
                        <button type="button" @click="showReportModal = false" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm">Batal</button>
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
                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Paket Langganan</p>
                                <p class="text-sm text-gray-900 font-medium">
                                    <span class="inline-flex px-2 py-1 bg-purple-500/20 text-purple-500 rounded-md border border-purple-500/30">
                                        {{ selectedCustomer.package?.name || 'Belum dipilih' }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div class="space-y-1">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Alamat Lengkap</p>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ selectedCustomer.address }}</p>
                            <div v-if="selectedCustomer.latitude && selectedCustomer.longitude" class="mt-2">
                                <a :href="`https://www.google.com/maps?q=${selectedCustomer.latitude},${selectedCustomer.longitude}`" target="_blank" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-900 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5">
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
                                        <p class="text-sm text-gray-900 font-medium">{{ selectedCustomer.surveys[0].distance_meters ? `${selectedCustomer.surveys[0].distance_meters} Meter` : '-' }}</p>
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
                                        <p class="text-sm text-gray-600">Teknisi: <strong class="text-gray-900">{{ selectedCustomer.technician_schedules[0].technician?.name || '-' }}</strong></p>
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

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import StatCard from '@/Components/StatCard.vue';

const props = defineProps({ customers: Object, availableOdps: Array, technicians: Array, areas: Array, stats: Object, filters: Object });

const page = usePage();
const hasPermission = (permission) => {
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
        
        if (perms.includes(permission)) return true;
        
        return false;
    } catch (e) {
        return false;
    }
};
const selectedIds = ref([]);

const canDelete = computed(() => {
    try {
        if (!page || !page.props || !page.props.auth || !page.props.auth.user) return false;
        
        const user = page.props.auth.user;
        
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) {
                return true;
            }
        }
        
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        
        for (let r of roles) {
            if (typeof r === 'string') {
                const rStr = r.toLowerCase().trim();
                if (rStr === 'admin' || rStr === 'super admin' || rStr.includes('admin')) return true;
            }
        }
        
        let perms = [];
        if (Array.isArray(user.permissions)) perms = user.permissions;
        else if (user.permissions) perms = Object.values(user.permissions);
        
        return perms.includes('menu_customers_survey') || perms.includes('customers_survey_delete') || perms.includes('customers_delete');
    } catch (e) {
        return false;
    }
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
    { key: 'name', label: 'Pelanggan' },
    { key: 'odp', label: 'ODP Terdekat' },
    { key: 'distance', label: 'Jarak Kabel' },
    { key: 'port', label: 'Port ODP' },
    { key: 'feasibility', label: 'Kelayakan' },
    { key: 'surveyor', label: 'Surveyor / Jadwal' },
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

const selectedTechnician = ref(props.filters.technician_id || '');
const selectedDate = ref(props.filters.date || '');
const selectedArea = ref(props.filters.area || '');
const selectedStatus = ref(props.filters.tab || 'semua');

const buildSearchRoute = () => {
    let route = '/customers/survey';
    let query = [];
    if (selectedStatus.value && selectedStatus.value !== 'semua') query.push('tab=' + selectedStatus.value);
    if (selectedTechnician.value) query.push('technician_id=' + selectedTechnician.value);
    if (selectedDate.value) query.push('date=' + selectedDate.value);
    if (selectedArea.value) query.push('area=' + encodeURIComponent(selectedArea.value));
    if (query.length) route += '?' + query.join('&');
    return route;
};

const getTabUrl = (tab) => {
    let query = [];
    if (tab && tab !== 'semua') query.push('tab=' + tab);
    if (props.filters.search) query.push('search=' + encodeURIComponent(props.filters.search));
    if (selectedTechnician.value) query.push('technician_id=' + selectedTechnician.value);
    if (selectedDate.value) query.push('date=' + selectedDate.value);
    if (selectedArea.value) query.push('area=' + encodeURIComponent(selectedArea.value));
    return '/customers/survey' + (query.length ? '?' + query.join('&') : '');
};

function applyFilters() {
    router.get('/customers/survey', {
        search: props.filters.search,
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
    selectedStatus.value = 'semua';
    router.get('/customers/survey', {}, { preserveState: true, preserveScroll: true });
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
    assignForm.scheduled_date = new Date().toISOString().split('T')[0];
    assignForm.scheduled_time = '10:00';
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
const isFindingOdp = ref(false);
const nearestOdpMsg = ref('');
const sortedOdps = ref([...props.availableOdps]);

const reportForm = useForm({
    surveyor_id: '',
    odp_id: '',
    distance_meters: '',
    port_available: true,
    feasibility: 'feasible',
    notes: '',
    photos: []
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
    
    if (!navigator.geolocation) {
        alert("Geolocation tidak didukung oleh browser Anda");
        isFindingOdp.value = false;
        return;
    }
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const userLat = position.coords.latitude;
            const userLon = position.coords.longitude;
            
            // Calculate distance for filtered ODPs
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
                
                nearestOdpMsg.value = `📍 ODP Terdekat: ${nearest.name} berjarak ${Math.round(nearest.distance)} meter.`;
            } else {
                nearestOdpMsg.value = `⚠️ Tidak ada ODP dalam radius 5km.`;
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

function openReportModal(customer) {
    activeCustomer.value = customer;
    reportForm.reset();
    
    // Filter ODPs based on customer's area
    const customerAreaId = customer.area_id;
    const filteredOdps = props.availableOdps.filter(odp => {
        if (!customerAreaId) return false; // Prevent choosing ODP if customer has no area
        return Number(odp.area_id) === Number(customerAreaId);
    });
        
    sortedOdps.value = [...filteredOdps];
    nearestOdpMsg.value = '';
    
    // Auto-select surveyor if already scheduled
    const schedule = customer.technician_schedules?.find(s => s.type === 'survey' && s.status === 'scheduled');
    if (schedule) {
        reportForm.surveyor_id = schedule.technician_id;
    }
    
    // Default documentation photos
    reportForm.photos = [
        { label: 'Foto Selfie Pelanggan & Petugas', file: null },
        { label: 'Foto Rumah Pelanggan', file: null },
        { label: 'Foto Jalan', file: null }
    ];
    
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
    reportForm.post(`/customers/${activeCustomer.value.id}/store-survey`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showReportModal.value = false;
        }
    });
}

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
</script>
