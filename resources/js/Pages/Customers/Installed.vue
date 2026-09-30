<template>
    <AppLayout title="Pelanggan Instalasi" subtitle="Pelanggan yang sedang diinstalasi">
        
        <!-- Statistik Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard 
                title="Jadwal Pasang" 
                :value="stats?.jadwal_pasang || 0" 
                icon="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" 
                color="blue" 
            />
            <StatCard 
                title="Laporan Pasang" 
                :value="stats?.laporan_pasang || 0" 
                icon="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" 
                color="amber" 
            />
            <StatCard 
                title="Menunggu Audit" 
                :value="stats?.audit || 0" 
                icon="M15 12a3 3 0 11-6 0 3 3 0 016 0z" 
                color="purple" 
            />
            <StatCard 
                title="Selesai Instalasi" 
                :value="stats?.selesai_instalasi || 0" 
                icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" 
                color="emerald" 
            />
        </div>

        <!-- Tabs Menu -->
        <div class="flex flex-wrap gap-2 mb-6">
            <Link href="/customers/installed" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-all flex items-center gap-2', !filters.tab ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/30' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-indigo-600']">
                <span>Semua</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs', !filters.tab ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500']">{{ stats?.semua || 0 }}</span>
            </Link>
            <Link href="/customers/installed?tab=jadwal_pasang" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-all flex items-center gap-2', filters.tab === 'jadwal_pasang' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-blue-600']">
                <span>Jadwal Pasang</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs', filters.tab === 'jadwal_pasang' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500']">{{ stats?.jadwal_pasang || 0 }}</span>
            </Link>
            <Link href="/customers/installed?tab=laporan_pasang" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-all flex items-center gap-2', filters.tab === 'laporan_pasang' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-amber-500']">
                <span>Laporan Pasang</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs', filters.tab === 'laporan_pasang' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500']">{{ stats?.laporan_pasang || 0 }}</span>
            </Link>
            <Link href="/customers/installed?tab=audit" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-all flex items-center gap-2', filters.tab === 'audit' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/30' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-purple-600']">
                <span>Audit</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs', filters.tab === 'audit' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500']">{{ stats?.audit || 0 }}</span>
            </Link>
            <Link href="/customers/installed?tab=selesai_instalasi" :class="['px-4 py-2 rounded-lg font-bold text-sm transition-all flex items-center gap-2', filters.tab === 'selesai_instalasi' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/30' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 hover:text-emerald-600']">
                <span>Selesai Instalasi</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs', filters.tab === 'selesai_instalasi' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500']">{{ stats?.selesai_instalasi || 0 }}</span>
            </Link>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan instalasi..."
            searchRoute="/customers/installed"
            selectable
            v-model:selected="selectedIds"
        >
            
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
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center text-sm font-bold text-gray-900 shrink-0">
                            {{ row.name.charAt(0) }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}?source=instalasi`" class="text-gray-900 font-medium hover:text-blue-400 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-xs text-cyan-400 font-medium">{{ row.package?.name || '-' }}</span>
                </td>
                <td>
                    <StatusBadge :status="getCustomerProgressStatus(row)" />
                </td>
                <td>
                    <span v-if="row.ont" class="text-xs font-mono text-gray-600">{{ row.ont.serial_number }}</span>
                    <span v-else class="text-xs text-gray-600">-</span>
                </td>
                <td>
                    <template v-if="row.ont?.odp?.odc?.olt">
                        <div class="text-xs space-y-0.5">
                            <p class="text-gray-500">{{ row.ont.odp.odc.olt.name }}</p>
                            <p class="text-gray-500">→ {{ row.ont.odp.odc.name }} → {{ row.ont.odp.name }}</p>
                        </div>
                    </template>
                    <span v-else class="text-xs text-gray-600">-</span>
                </td>
                <td>
                    <span v-if="row.ont?.rx_power" :class="signalClass(row.ont.rx_power)" class="text-sm font-mono font-bold">
                        {{ row.ont.rx_power }} dBm
                    </span>
                    <span v-else class="text-gray-600">-</span>
                </td>
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1.5">
                    
                    <!-- 1. Belum Dijadwalkan -->
                    <button v-if="row.status === 'installing' && (!row.technician_schedules || row.technician_schedules.length === 0)" 
                        @click="openAssignModal(row)" 
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Jadwalkan Teknisi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Jadwal Pasang
                    </button>
                    
                    <!-- 2. Menunggu Laporan Teknisi -->
                    <Link v-if="row.status === 'installing' && (row.technician_schedules && row.technician_schedules.length > 0) && (!row.ont || !row.ont.rx_power)" 
                        :href="`/customers/${row.id}?source=instalasi`" 
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Input Laporan Instalasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Input Laporan
                    </Link>


                    <!-- 3. Selesai Pasang, Menunggu Audit Admin -->
                    <div v-if="row.status === 'installing' && row.ont && row.ont.rx_power && !row.is_audited" class="flex gap-1.5">
                        <Link :href="`/customers/${row.id}?source=instalasi`" 
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Review Hasil Pemasangan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Audit
                        </Link>
                    </div>

                    <Link :href="`/customers/${row.id}?source=instalasi`" class="p-1.5 rounded-lg text-gray-400 hover:bg-white hover:text-blue-500 border border-transparent hover:border-gray-200 transition-all shadow-sm hover:shadow" title="Detail Lengkap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </Link>
                    <button @click="confirmDelete(row)" class="p-1.5 rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 border border-transparent hover:border-red-200 transition-all shadow-sm hover:shadow" title="Hapus Pelanggan">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Assign Jadwal Pasang -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-md flex flex-col max-h-[calc(100vh-2rem)] overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white shrink-0">
                    <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Jadwal Pasang Baru
                    </h3>
                    <button @click="showAssignModal = false" class="text-gray-400 hover:text-gray-700 bg-white hover:bg-gray-100 p-1.5 rounded-lg border border-transparent hover:border-gray-200 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <!-- Informasi Hasil Survey -->
                <div class="bg-blue-50/50 px-5 py-3 border-b border-blue-100/50 flex flex-col gap-1.5 text-sm shrink-0">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium text-xs">Pelanggan</span>
                        <span class="font-bold text-slate-800">{{ activeCustomer?.name }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium text-xs">ODP & Jarak Kabel</span>
                        <span class="font-semibold text-slate-700 text-right">
                            {{ activeCustomer?.surveys?.[0]?.odp?.name || 'Tidak diketahui' }} 
                            <span class="text-xs font-normal text-slate-400 mx-1">•</span> 
                            {{ activeCustomer?.surveys?.[0]?.distance_meters ? activeCustomer.surveys[0].distance_meters + ' m' : '-' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-medium text-xs">Paket Internet</span>
                        <span class="font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100/50">{{ activeCustomer?.package?.name || '-' }}</span>
                    </div>
                </div>
                <form @submit.prevent="submitAssign" class="flex flex-col min-h-0">
                    <div class="p-5 space-y-4 overflow-y-auto">
                        <div v-if="Object.keys(assignForm.errors).length > 0" class="bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-4 border border-red-200">
                            <strong>Gagal menyimpan:</strong> Terdapat data yang tidak valid. Silakan periksa kembali isian Anda.
                            <ul class="list-disc ml-5 mt-1">
                                <li v-for="(error, field) in assignForm.errors" :key="field">{{ error }}</li>
                            </ul>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Pilih Teknisi</label>
                            
                            <!-- Invisible Backdrop for dropdown -->
                            <div v-if="showTechDropdown" @click="showTechDropdown = false" class="fixed inset-0 z-[55]"></div>
                            
                            <div class="relative z-[60]">
                                <button type="button" @click="showTechDropdown = !showTechDropdown" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-left text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all flex justify-between items-center h-auto min-h-[44px]">
                                    <span class="truncate pr-4 leading-tight">
                                        {{ assignForm.technician_ids.length > 0 
                                            ? assignForm.technician_ids.map(id => technicians.find(t => t.id === id)?.name).join(', ') 
                                            : '-- Pilih Teknisi --' 
                                        }}
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 shrink-0" :class="{'rotate-180': showTechDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                                <div v-if="assignForm.errors.technician_ids" class="text-red-500 text-xs mt-1">{{ assignForm.errors.technician_ids }}</div>
                                
                                <div v-if="showTechDropdown" class="absolute z-10 w-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-xl max-h-48 overflow-y-auto py-1">
                                    <label v-for="tech in technicians" :key="tech.id" class="flex items-center gap-3 cursor-pointer px-4 py-2.5 hover:bg-slate-50 transition-colors border-b border-gray-50 last:border-0">
                                        <input type="checkbox" :value="tech.id" v-model="assignForm.technician_ids" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4">
                                        <span class="text-sm font-medium text-slate-700">{{ tech.name }}</span>
                                    </label>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Anda dapat memilih lebih dari satu teknisi
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Tanggal Pasang</label>
                                <input v-model="assignForm.scheduled_date" type="date" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all" required />
                                <div v-if="assignForm.errors.scheduled_date" class="text-red-500 text-xs mt-1">{{ assignForm.errors.scheduled_date }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Waktu (Jam)</label>
                                <input v-model="assignForm.scheduled_time" type="time" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-all" required />
                                <div v-if="assignForm.errors.scheduled_time" class="text-red-500 text-xs mt-1">{{ assignForm.errors.scheduled_time }}</div>
                            </div>
                        </div>
                        <div class="space-y-5">
                            <!-- Data ONT Section -->
                            <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Data ONT</label>
                                    <button type="button" @click="assignForm.ont_models.push('')" class="text-blue-600 hover:text-blue-700 hover:bg-blue-50 p-1.5 rounded-md focus:outline-none transition-colors border border-transparent hover:border-blue-200 text-xs font-medium flex items-center gap-1" title="Tambah ONT">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(ont, index) in assignForm.ont_models" :key="'ont-'+index" class="group relative">
                                        <div class="flex gap-2 items-center">
                                            <select v-model="assignForm.ont_models[index]" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-sm transition-all truncate hover:bg-white">
                                                <option value="">-- Pilih ONT --</option>
                                                <option v-for="ontOption in filteredOnts" :key="ontOption.id" :value="ontOption.id">
                                                    {{ ontOption.brand }} {{ ontOption.model || '' }} - SN: {{ ontOption.serial_number }}
                                                </option>
                                            </select>
                                            <button v-if="assignForm.ont_models.length > 1" type="button" @click="assignForm.ont_models.splice(index, 1)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-all focus:outline-none shrink-0" title="Hapus">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                            </button>
                                        </div>
                                        <div v-if="assignForm.ont_models[index] && getSelectedOntDetails(assignForm.ont_models[index])" class="mt-2.5 bg-indigo-50/70 p-3 rounded-lg border border-indigo-100 text-xs text-slate-700 space-y-1.5 shadow-inner">
                                            <div class="flex justify-between"><span class="text-slate-500">Merek:</span> <span class="font-semibold">{{ getSelectedOntDetails(assignForm.ont_models[index]).brand }}</span></div>
                                            <div class="flex justify-between"><span class="text-slate-500">Model:</span> <span class="font-semibold">{{ getSelectedOntDetails(assignForm.ont_models[index]).model || '-' }}</span></div>
                                            <div class="flex justify-between"><span class="text-slate-500">SN:</span> <span class="font-mono font-bold text-indigo-700 bg-indigo-100/50 px-1 rounded">{{ getSelectedOntDetails(assignForm.ont_models[index]).serial_number }}</span></div>
                                            
                                            <template v-if="getSelectedOntDetails(assignForm.ont_models[index]).pppoe_user || getSelectedOntDetails(assignForm.ont_models[index]).access_mode === 'PPPoE' || activeCustomer?.package?.name?.toLowerCase().includes('pppoe')">
                                                <div class="my-1.5 border-t border-indigo-200/60"></div>
                                                <div class="flex justify-between"><span class="text-slate-500">Akun PPPoE:</span> <span class="font-semibold">{{ getSelectedOntDetails(assignForm.ont_models[index]).pppoe_user || '-' }}</span></div>
                                                <div class="flex justify-between"><span class="text-slate-500">Pass PPPoE:</span> <span class="font-semibold">{{ getSelectedOntDetails(assignForm.ont_models[index]).pppoe_password || '-' }}</span></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Material Section -->
                            <div class="bg-white border border-gray-100 rounded-xl p-4 shadow-sm">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Material (Surat Jalan)</label>
                                    <button type="button" @click="assignForm.material_transaction_ids.push('')" class="text-blue-600 hover:text-blue-700 hover:bg-blue-50 p-1.5 rounded-md focus:outline-none transition-colors border border-transparent hover:border-blue-200 text-xs font-medium flex items-center gap-1" title="Tambah Surat Jalan">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(trxId, index) in assignForm.material_transaction_ids" :key="'trx-'+index" class="flex gap-2 items-center group">
                                        <select v-model="assignForm.material_transaction_ids[index]" @change="updateMaterialItems" class="w-full bg-slate-50 border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-sm transition-all hover:bg-white">
                                            <option value="">-- Pilih Surat Jalan / Order --</option>
                                            <option v-for="trx in filteredMaterialTransactions" :key="trx.id" :value="trx.transaction_number">
                                                {{ getTransactionLabel(trx) }}
                                            </option>
                                        </select>
                                        <button v-if="assignForm.material_transaction_ids.length > 1" type="button" @click="removeMaterialTransaction(index)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-all focus:outline-none" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Kolom Rincian -->
                                    <div v-if="assignForm.material_items.length > 0" class="mt-3 p-3.5 bg-slate-50 border border-gray-200 rounded-lg max-h-48 overflow-y-auto">
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2.5 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Rincian Penggunaan Barang (Bisa disesuaikan):
                                        </p>
                                        <ul class="space-y-2">
                                            <li v-for="(item, idx) in assignForm.material_items" :key="item.id" class="flex items-center justify-between bg-white p-2.5 rounded-lg border border-gray-200 shadow-sm hover:border-blue-200 transition-colors">
                                                <div class="flex-1 truncate mr-3">

                                                    <span class="text-xs font-bold text-gray-800">{{ item.name || 'Barang' }}</span>
                                                </div>
                                                <div class="flex gap-1.5 items-center shrink-0">
                                                    <input v-model="item.qty" type="number" step="0.01" min="0" class="w-16 px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-blue-500 text-center font-semibold text-blue-700 bg-blue-50/50" placeholder="Qty">
                                                    <span class="text-[10px] font-medium text-gray-500 w-8 truncate">{{ item.unit }}</span>
                                                    <button type="button" @click="assignForm.material_items.splice(idx, 1)" class="text-gray-400 hover:text-red-500 p-1" title="Hapus">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Catatan (Opsional)</label>
                            <textarea v-model="assignForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-sm transition-all resize-none"></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-slate-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl shrink-0">
                        <button type="button" @click="showAssignModal = false" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 hover:text-slate-900 transition-all shadow-sm">Batal</button>
                        <button type="submit" :disabled="assignForm.processing" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center gap-2">
                            <svg v-if="assignForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ assignForm.processing ? 'Menyimpan...' : 'Tugaskan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Activation Modal -->
        <div v-if="showActivationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-purple-50 to-purple-100/50">
                    <h3 class="text-lg font-bold text-purple-900 flex items-center gap-2">
                        <span class="text-2xl">⚡</span> Aktivasi Pelanggan
                    </h3>
                    <button @click="showActivationModal = false" class="text-gray-500 hover:text-gray-900">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="submitActivation">
                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        <div class="bg-purple-50 text-purple-800 p-4 rounded-xl text-sm mb-4 border border-purple-200 shadow-sm flex gap-3">
                            <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>Pastikan Anda telah memeriksa data fisik dan foto hasil instalasi sebelum melakukan aktivasi.</p>
                        </div>
                        
                        <!-- Ringkasan Data ONT -->
                        <div v-if="activeCustomer" class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Detail Data ONT & Pelanggan</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-4 text-sm">
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Nama Pelanggan</span>
                                    <span class="font-bold text-gray-900">{{ activeCustomer.name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Area/Wilayah</span>
                                    <span class="font-bold text-gray-900">{{ activeCustomer.area || '-' }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Paket Berlangganan</span>
                                    <span class="font-bold text-gray-900">{{ activeCustomer.package?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Nama ODP</span>
                                    <span class="font-bold text-gray-900">{{ activeCustomer.ont?.odp?.name || '-' }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 block text-[10px] uppercase font-semibold">Port ODP</span>
                                    <span class="font-bold text-gray-900">Port {{ activeCustomer.ont?.port_number || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Aktivasi</label>
                            <input v-model="activationForm.activation_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" required />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Username</label>
                                <input v-model="activationForm.pppoe_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="user@isp" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE Password</label>
                                <input v-model="activationForm.pppoe_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="***" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Akses</label>
                                <select v-model="activationForm.access_mode" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all">
                                    <option value="PPPOE">PPPoE</option>
                                    <option value="STATIC">Static IP</option>
                                    <option value="DHCP">DHCP / Dynamic</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">VLAN Mode</label>
                                <select v-model="activationForm.vlan_mode" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all">
                                    <option value="">-- Pilih --</option>
                                    <option value="Route">Route</option>
                                    <option value="Bridge">Bridge</option>
                                    <option value="VLAN">VLAN (Tagged)</option>
                                    <option value="Untagged">Untagged</option>
                                </select>
                            </div>
                            
                            <div v-if="activationForm.vlan_mode === 'VLAN'" class="col-span-full">
                                <label class="block text-sm font-medium text-gray-700 mb-1">No VLAN ID</label>
                                <input v-model="activationForm.vlan_id" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Misal: 100" />
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Akses Login ONT</h4>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IP Login ONT</label>
                                <input v-model="activationForm.ip_login" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="192.168.1.1" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Username ONT</label>
                                    <input v-model="activationForm.login_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="admin" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password ONT</label>
                                    <input v-model="activationForm.login_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="admin" />
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea v-model="activationForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Catatan internal setelah aktivasi..."></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showActivationModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                        <button type="submit" :disabled="activationForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <svg v-if="activationForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ activationForm.processing ? 'Memproses...' : 'Aktivasi Sekarang' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                        Semua data terkait pelanggan ini (jadwal teknisi, data perangkat, status) akan <strong class="text-red-600">dihapus permanen</strong>. Tindakan ini tidak dapat dibatalkan.
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
import { ref, computed, watch } from 'vue';
import { Link, router, useForm , usePage} from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import StatCard from '@/Components/StatCard.vue';

const showTechDropdown = ref(false);

const getCustomerProgressStatus = (customer) => {
    if (customer.status === 'active') return 'active';
    if (customer.is_audited) return 'menunggu_aktivasi';
    if (customer.ont && customer.ont.rx_power) return 'audit';
    if (customer.technician_schedules && customer.technician_schedules.length > 0) return 'laporan_pasang';
    return 'jadwal_pasang';
};

const props = defineProps({ 
    customers: Object, 
    technicians: Array, 
    availableOnts: { type: Array, default: () => [] }, 
    materialTransactions: { type: Array, default: () => [] },
    stats: Object,
    filters: Object 
});



const page = usePage();
const selectedIds = ref([]);

const canDelete = computed(() => {
    try {
        if (!page || !page.props || !page.props.auth || !page.props.auth.user) return false;
        const user = page.props.auth.user;
        if (user.role && typeof user.role === 'string') {
            const roleStr = user.role.toLowerCase().trim();
            if (roleStr === 'admin' || roleStr === 'super admin' || roleStr.includes('admin')) return true;
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
    { key: 'package', label: 'Paket' },
    { key: 'status', label: 'Status' },
    { key: 'ont', label: 'ONT S/N' },
    { key: 'topology', label: 'Topologi' },
    { key: 'signal', label: 'Redaman' },
];

function updateMaterialItems() {
    const validTransactionItemIds = new Set();
    const newItemsToAdd = [];
    
    assignForm.material_transaction_ids.forEach(trxId => {
        if (!trxId) return;
        const trx = props.materialTransactions.find(t => t.transaction_number === trxId);
        if (trx && trx.items) {
            trx.items.forEach(item => {
                const name = item.material && item.material.name ? item.material.name.toLowerCase() : '';
                const category = item.material && item.material.category ? item.material.category.toLowerCase() : '';
                const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;
                
                if (!name.includes('ont') && !name.includes('modem') && !category.includes('ont') && !category.includes('modem') && !isRegistered) {
                    validTransactionItemIds.add(item.id);
                    
                    const exists = assignForm.material_items.find(mi => mi.id === item.id);
                    if (!exists) {
                        let qty = parseFloat(item.quantity) || 0;
                        let unit = item.unit || (item.material ? item.material.unit : 'pcs');
                        
                        if (item.material && item.material.category === 'Isolasi') {
                            if (unit === 'pcs' || unit === 'pcs (utuh)') {
                                qty = qty * (parseFloat(item.material.cm_per_pcs) || 50);
                                unit = 'cm';
                            }
                        } else if (item.material && item.material.category === 'Paku Klem') {
                            if (unit === 'bungkus' || unit === 'pack') {
                                qty = qty * (parseFloat(item.material.pcs_per_pack) || 100);
                                unit = 'pcs';
                            }
                        }
                        
                        newItemsToAdd.push({
                            id: item.id,
                            name: item.material ? item.material.name : 'Unknown',
                            qty: qty,
                            unit: unit
                        });
                    }
                }
            });
        }
    });

    // Filter existing items to keep manual items and valid transaction items
    assignForm.material_items = assignForm.material_items.filter(mi => 
        String(mi.id).startsWith('manual-') || validTransactionItemIds.has(mi.id)
    );

    // Append new items
    assignForm.material_items.push(...newItemsToAdd);
}

function removeMaterialTransaction(index) {
    assignForm.material_transaction_ids.splice(index, 1);
    updateMaterialItems();
}

function addManualMaterial() {
    assignForm.material_items.push({
        id: 'manual-' + Date.now() + '-' + Math.floor(Math.random() * 1000),
        name: '',
        qty: 1,
        unit: 'pcs'
    });
}

function getSelectedOntDetails(val) {
    if (!val) return null;
    return props.availableOnts.find(ont => ont.id === val);
}

function getTransactionLabel(trx) {
    let itemsStr = 'Tidak ada barang';
    if (trx.items && trx.items.length > 0) {
        const nonOntItems = trx.items.filter(item => {
            const name = item.material && item.material.name ? item.material.name.toLowerCase() : '';
            const category = item.material && item.material.category ? item.material.category.toLowerCase() : '';
            const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;
            return !name.includes('ont') && !name.includes('modem') && !category.includes('ont') && !category.includes('modem') && !isRegistered;
        });

        if (nonOntItems.length > 0) {
            itemsStr = nonOntItems.map(item => {
                const name = item.material ? item.material.name : 'Unknown';
                const stock = item.material ? item.material.stock : 0;
                const unit = item.material ? item.material.unit : 'pcs';
                return `${name} (Stok Sisa: ${stock} ${unit})`;
            }).join(', ');
            
            if (itemsStr.length > 60) {
                itemsStr = itemsStr.substring(0, 57) + '...';
            }
        } else {
            itemsStr = 'Hanya ONT/Modem';
        }
    }
    return itemsStr;
}

const status = ref(props.filters?.status || '');

function applyFilter() {
    router.get('/customers/installed', { status: status.value || undefined }, { preserveState: true });
}

function signalClass(rx) {
    if (!rx) return 'text-gray-500';
    if (rx >= -20) return 'text-emerald-400';
    if (rx >= -25) return 'text-green-400';
    if (rx >= -28) return 'text-yellow-400';
    return 'text-red-400';
}

const showAssignModal = ref(false);
const activeCustomer = ref(null);
const assignForm = useForm({
    technician_ids: [],
    scheduled_date: '',
    scheduled_time: '',
    ont_models: [''],
    material_transaction_ids: [''],
    material_items: [],
    notes: '',
});

const filteredOnts = computed(() => {
    if (!activeCustomer.value || !activeCustomer.value.area_id) return [];
    return props.availableOnts.filter(ont => ont.area_id === activeCustomer.value.area_id);
});

const filteredMaterialTransactions = computed(() => {
    if (!activeCustomer.value || !activeCustomer.value.area_id) return [];
    return props.materialTransactions.filter(trx => trx.area_id === activeCustomer.value.area_id);
});

function openAssignModal(customer) {
    activeCustomer.value = customer;
    assignForm.reset();
    assignForm.scheduled_date = new Date().toISOString().split('T')[0];
    assignForm.scheduled_time = '10:00';
    showAssignModal.value = true;
}

function submitAssign() {
    if (!activeCustomer.value || !activeCustomer.value.id) {
        alert('ERROR: Customer tidak ditemukan! activeCustomer.value = ' + JSON.stringify(activeCustomer.value));
        return;
    }
    
    const url = `/customers/${activeCustomer.value.id}/assign-install`;
    console.log('submitAssign URL:', url);
    console.log('submitAssign Data:', JSON.stringify(assignForm.data()));
    
    assignForm.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
            alert('Jadwal pasang berhasil disimpan!');
        },
        onError: (errors) => {
            console.error('assignInstall errors:', errors);
            alert('Error validasi: ' + JSON.stringify(errors));
        },
        onFinish: () => {
            console.log('assignInstall finished, processing:', assignForm.processing);
        }
    });
}

const showActivationModal = ref(false);
const activationForm = useForm({
    activation_date: '',
    pppoe_user: '',
    pppoe_password: '',
    vlan_mode: '',
    vlan_id: '',
    access_mode: 'PPPOE',
    ip_login: '192.168.1.1',
    login_user: 'admin',
    login_password: 'admin',
    notes: ''
});

function openActivationModal(customer) {
    activeCustomer.value = customer;
    activationForm.reset();
    activationForm.activation_date = new Date().toISOString().split('T')[0];
    activationForm.pppoe_user = customer.ont?.pppoe_user || '';
    activationForm.pppoe_password = customer.ont?.pppoe_password || '';
    activationForm.vlan_mode = customer.ont?.vlan_mode || '';
    activationForm.vlan_id = customer.ont?.vlan_id || '';
    activationForm.access_mode = customer.ont?.access_mode || 'PPPOE';
    activationForm.ip_login = customer.ont?.ip_login || '192.168.1.1';
    activationForm.login_user = customer.ont?.login_user || 'admin';
    activationForm.login_password = customer.ont?.login_password || 'admin';
    activationForm.notes = '';
    showActivationModal.value = true;
}

function submitActivation() {
    activationForm.post(`/customers/${activeCustomer.value.id}/activate`, {
        preserveScroll: true,
        onSuccess: () => {
            showActivationModal.value = false;
        }
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
