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

        <!-- Tabs Menu removed (Moved to sidebar) -->

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari pelanggan instalasi..."
            searchRoute="/customers/installed"
            selectable
            v-model:selected="selectedIds"
        >
            <template #filters>
                <form @submit.prevent="applyFilter" class="flex flex-wrap items-center gap-2">
                    <select v-model="filterState.area" class="form-select w-36 text-sm">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="area" :value="area">
                            {{ area }}
                        </option>
                    </select>

                    <select v-model="filterState.tab" class="form-select w-40 text-sm">
                        <option value="">Semua Status</option>
                        <option value="jadwal_pasang">Jadwal Pasang</option>
                        <option value="laporan_pasang">Laporan Pasang</option>
                        <option value="audit">Audit</option>
                        <option value="selesai_instalasi">Selesai Instalasi</option>
                    </select>
                    
                    <div class="flex items-center gap-1">
                        <input type="date" v-model="filterState.date_from" class="form-input w-36 text-sm" title="Tanggal Dari">
                        <span class="text-xs text-gray-500 font-medium px-1">s/d</span>
                        <input type="date" v-model="filterState.date_to" class="form-input w-36 text-sm" title="Tanggal Sampai">
                    </div>

                    <button type="submit" class="btn-primary py-2 text-sm shadow-sm">
                        Tampilkan
                    </button>
                    
                    <button type="button" @click="resetFilter" class="btn-ghost py-2 text-sm">
                        Reset
                    </button>
                </form>
            </template>
            
            <template #actions>
                <div class="flex items-center gap-2">
                    <button v-if="selectedIds.length > 0" 
                        @click="openPrintModal" 
                        class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-medium text-sm rounded-lg transition-colors border border-indigo-200 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Card
                    </button>
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
                    <button v-if="getCustomerProgressStatus(row) === 'jadwal_pasang' && hasPermission('customers_installed_assign')" 
                        @click="openAssignModal(row)" 
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Jadwalkan Teknisi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Jadwal Pasang
                    </button>
                    <!-- 2. Menunggu Laporan Teknisi -->
                    <template v-if="getCustomerProgressStatus(row) === 'laporan_pasang'">
                        <Link v-if="hasPermission('customers_installed_report')" 
                            :href="`/customers/${row.id}?source=instalasi`" 
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Input Laporan Instalasi">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Input Laporan
                        </Link>
                        <span v-else class="px-3 py-1.5 bg-blue-50 text-blue-500 border border-blue-200 rounded-lg text-xs font-medium flex items-center gap-1 cursor-default" title="Menunggu teknisi mengisi laporan pemasangan">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Menunggu Pemasangan
                        </span>
                    </template>


                    <!-- 3. Selesai Pasang, Menunggu Audit Admin -->
                    <div v-if="getCustomerProgressStatus(row) === 'audit' && hasPermission('customers_installed_audit')" class="flex gap-1.5">
                        <Link :href="`/customers/${row.id}?source=instalasi`" 
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold transition-all shadow-sm" title="Review Hasil Pemasangan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Audit
                        </Link>
                    </div>

                    <button @click="openDetailModal(row)" class="p-1.5 rounded-lg text-gray-400 hover:bg-white hover:text-blue-500 border border-transparent hover:border-gray-200 transition-all shadow-sm hover:shadow" title="Detail Lengkap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <!-- Modal Detail Pelanggan (Instalasi) -->
                    <Teleport to="body">
                        <div v-if="showDetailModal && detailCustomer" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="closeDetailModal"></div>
                            <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto animate-fade-in-up">
                                <div class="sticky top-0 bg-white/90 backdrop-blur-xl border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
                                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <span class="p-1.5 rounded-lg bg-indigo-500/20 text-indigo-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </span>
                                        Detail Instalasi Pelanggan
                                    </h3>
                                    <button @click="closeDetailModal" class="text-gray-500 hover:text-gray-900 transition-colors bg-gray-50 hover:bg-white p-2 rounded-xl">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                                
                                <div class="p-6 space-y-6">
                                    <!-- Info Utama -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-1">
                                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Nama Lengkap</p>
                                            <p class="text-sm text-gray-900 font-medium">{{ detailCustomer.name }}</p>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Telepon / WhatsApp</p>
                                            <p class="text-sm text-gray-900">{{ detailCustomer.phone }}</p>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</p>
                                            <p class="text-sm text-gray-900 font-medium">
                                                <span class="inline-flex px-2 py-1 bg-blue-500/20 text-blue-500 rounded-md border border-blue-500/30">
                                                    {{ detailCustomer.area || '-' }}
                                                </span>
                                            </p>
                                        </div>
                                        <div class="space-y-1">
                                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Paket Langganan</p>
                                            <p class="text-sm text-gray-900 font-medium">
                                                <span class="inline-flex px-2 py-1 bg-purple-500/20 text-purple-500 rounded-md border border-purple-500/30">
                                                    {{ detailCustomer.package?.name || 'Belum dipilih' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Alamat -->
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Alamat Lengkap</p>
                                        <p class="text-sm text-gray-600 leading-relaxed">{{ detailCustomer.address }}</p>
                                        <div v-if="detailCustomer.latitude && detailCustomer.longitude" class="mt-2">
                                            <a :href="`https://www.google.com/maps?q=${detailCustomer.latitude},${detailCustomer.longitude}`" target="_blank" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-900 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                Buka Lokasi di Maps
                                            </a>
                                        </div>
                                    </div>

                                    <hr class="border-gray-100">

                                    <!-- Data Instalasi -->
                                    <div class="space-y-4">
                                        <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            Data Instalasi & Jaringan
                                        </h4>
                                        <div v-if="detailCustomer.ont" class="space-y-6">
                                            <!-- Info Penugasan Pasang -->
                                            <div class="bg-indigo-50/70 p-4 rounded-xl border border-indigo-100">
                                                <p class="text-xs font-semibold text-indigo-800 uppercase tracking-wider mb-2">Info Penugasan Pasang</p>
                                                <div class="flex justify-between items-center py-2 border-b border-indigo-100/50 last:border-0">
                                                    <span class="text-sm text-gray-500 font-medium">Teknisi</span>
                                                    <span class="text-sm font-semibold text-gray-900 text-right">{{ getAssignedTechnicians(detailCustomer, 'installation') }}</span>
                                                </div>
                                                <div class="flex justify-between items-center py-2 border-b border-indigo-100/50 last:border-0">
                                                    <span class="text-sm text-gray-500 font-medium">Tanggal Pasang</span>
                                                    <span class="text-sm font-semibold text-gray-900 text-right">{{ detailCustomer.technician_schedules?.find(s => s.type === 'installation')?.scheduled_date || '-' }}</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Kebutuhan Material -->
                                            <div v-if="hardwareItems.length > 0" class="bg-amber-50/70 p-4 rounded-xl border border-amber-100">
                                                <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-3">Kebutuhan Material (Telah Dipasang)</p>
                                                <div class="space-y-2">
                                                    <div v-for="item in hardwareItems" :key="item.id" class="text-sm text-gray-700 bg-white p-3 rounded-lg border border-amber-200 shadow-sm flex items-center justify-between">
                                                        <div>
                                                            <span class="text-xs text-gray-500 font-medium block mb-1">{{ item.type }}</span>
                                                            <span class="font-bold text-gray-900">{{ item.name }}</span>
                                                        </div>
                                                        <div class="text-emerald-600 font-bold flex items-center gap-1 text-sm">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                            Dipasang
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="space-y-2">
                                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                                        <span class="text-sm text-gray-500 font-medium">Waktu Mulai</span>
                                                        <span class="text-sm font-semibold text-gray-900 text-right">{{ detailCustomer.ont?.start_time || '-' }}</span>
                                                    </div>
                                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                                        <span class="text-sm text-gray-500 font-medium">Waktu Selesai</span>
                                                        <span class="text-sm font-semibold text-gray-900 text-right">{{ detailCustomer.ont?.end_time || '-' }}</span>
                                                    </div>
                                                </div>
                                                <div class="space-y-2">
                                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                                        <span class="text-sm text-gray-500 font-medium">Port ODP</span>
                                                        <span class="text-sm font-semibold text-gray-900 text-right">Port {{ detailCustomer.ont?.port_number || '-' }}</span>
                                                    </div>
                                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                                        <span class="text-sm text-gray-500 font-medium">Serial Number (Auto)</span>
                                                        <span class="text-sm font-semibold text-gray-900 text-right">{{ detailCustomer.ont?.serial_number || '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="pt-4 border-t border-gray-200">
                                                <p class="text-xs text-gray-500 mb-3 uppercase font-semibold">Hasil Dokumentasi Lapangan:</p>
                                                <div class="flex gap-4 overflow-x-auto pb-2">
                                                    <div v-if="detailCustomer.ont?.photo_odp" class="shrink-0 group relative">
                                                        <img :src="`/storage/${detailCustomer.ont.photo_odp}`" class="h-28 w-28 object-cover rounded-lg border border-gray-200" />
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto ODP</div>
                                                    </div>
                                                    <div v-if="detailCustomer.ont?.photo_installation" class="shrink-0 group relative">
                                                        <img :src="`/storage/${detailCustomer.ont.photo_installation}`" class="h-28 w-28 object-cover rounded-lg border border-gray-200" />
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Instalasi</div>
                                                    </div>
                                                    <div v-if="detailCustomer.ont?.photo_ont" class="shrink-0 group relative">
                                                        <img :src="`/storage/${detailCustomer.ont.photo_ont}`" class="h-28 w-28 object-cover rounded-lg border border-gray-200" />
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto ONT</div>
                                                    </div>
                                                    <div v-if="detailCustomer.ont?.photo_customer" class="shrink-0 group relative">
                                                        <img :src="`/storage/${detailCustomer.ont.photo_customer}`" class="h-28 w-28 object-cover rounded-lg border border-gray-200" />
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Selfie</div>
                                                    </div>
                                                    <div v-if="detailCustomer.ont?.photo_redaman" class="shrink-0 group relative">
                                                        <img :src="`/storage/${detailCustomer.ont.photo_redaman}`" class="h-28 w-28 object-cover rounded-lg border border-gray-200" />
                                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-1 rounded-b-lg text-[10px] text-center text-white truncate pointer-events-none">Foto Redaman</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Dokumentasi Foto Instalasi (Legacy) -->
                                            <div v-if="detailCustomer.ont.photos && detailCustomer.ont.photos.length > 0 && !detailCustomer.ont.photo_odp" class="pt-4 border-t border-gray-200">
                                                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-3">Dokumentasi Laporan (Lama)</p>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 sm:grid-cols-3 gap-3">
                                                    <div v-for="(photo, idx) in detailCustomer.ont.photos" :key="idx" class="relative group rounded-lg overflow-hidden border border-gray-200 shadow-sm bg-gray-100 aspect-square">
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

                                        <div v-else-if="detailCustomer.technician_schedules?.length" class="bg-blue-50/50 rounded-xl p-4 border border-blue-100">
                                            <div class="flex gap-3 items-start">
                                                <div class="p-2 bg-blue-100 text-blue-600 rounded-lg shrink-0">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                                <div>
                                                    <h5 class="text-sm font-semibold text-gray-900 mb-1">Telah Dijadwalkan (Belum Ada Laporan)</h5>
                                                    <p class="text-sm text-gray-600">Teknisi: <strong class="text-gray-900">{{ detailCustomer.technician_schedules[0].technician?.name || '-' }}</strong></p>
                                                    <p class="text-sm text-gray-600">Waktu: <strong class="text-gray-900">{{ detailCustomer.technician_schedules[0].scheduled_date }} {{ detailCustomer.technician_schedules[0].scheduled_time }}</strong></p>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="text-sm text-gray-500 italic p-4 bg-gray-50 border border-gray-200 rounded-xl text-center">
                                            Belum ada data instalasi.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="sticky bottom-0 bg-white/90 backdrop-blur-xl border-t border-gray-200 px-6 py-4 flex justify-end items-center">
                                    <button @click="closeDetailModal" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl text-sm font-medium transition-colors">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Teleport>
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
                                    <button type="button" @click="assignForm.material_items.push({id: '', name: '', qty: 1, unit: '', trx_number: ''})" class="text-blue-600 hover:text-blue-700 hover:bg-blue-50 p-1.5 rounded-md focus:outline-none transition-colors border border-transparent hover:border-blue-200 text-xs font-medium flex items-center gap-1" title="Tambah Material">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah Material
                                    </button>
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(mItem, index) in assignForm.material_items" :key="'mat-'+index" class="flex gap-2 items-center group">
                                        <select v-model="assignForm.material_items[index].id" @change="updateMaterialItemDetails(index)" class="flex-1 bg-slate-50 border border-gray-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 shadow-sm transition-all hover:bg-white min-w-0">
                                            <option value="">-- Pilih Material dari Surat Jalan --</option>
                                            <option v-for="item in availableMaterialItems" :key="item.id" :value="item.id">
                                                {{ item.material ? item.material.name : 'Unknown' }} (SJ: {{ item.trx_number }})
                                            </option>
                                        </select>
                                        <div class="flex items-center gap-1.5 shrink-0" v-if="assignForm.material_items[index].id">
                                            <input v-model="assignForm.material_items[index].qty" type="number" step="0.01" min="0" class="w-20 px-2 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 text-center font-semibold text-blue-700 bg-blue-50/50" placeholder="Qty">
                                            <span class="text-xs font-medium text-gray-500 w-12 truncate">{{ assignForm.material_items[index].unit }}</span>
                                        </div>
                                        <button type="button" @click="assignForm.material_items.splice(index, 1)" class="text-red-400 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-all focus:outline-none shrink-0" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                                        </button>
                                    </div>
                                    <div v-if="assignForm.material_items.length === 0" class="text-xs text-gray-400 italic text-center py-2">
                                        Belum ada material yang ditambahkan
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

        <!-- Modal Print Card Preview -->
        <div v-if="isPrintModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto print:!bg-transparent print:!backdrop-blur-none print:!p-0">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden flex flex-col print:!shadow-none print:!max-h-none print:!rounded-none">
                <div class="bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between no-print">
                    <h3 class="text-lg font-bold text-gray-900">Preview Print Card ({{ selectedIds.length }} Item)</h3>
                    <div class="flex gap-2">
                        <button @click="printCards" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print Sekarang
                        </button>
                        <button @click="isPrintModalOpen = false" class="text-gray-400 hover:text-gray-500 p-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                
                <!-- A4 Paper Preview -->
                <div class="p-6 overflow-y-auto bg-gray-300 flex-1 flex justify-center">
                    <div class="bg-white shadow-2xl" style="width: 210mm; min-height: 297mm; padding: 10mm;" id="print-area">
                        <div style="display: flex; flex-wrap: wrap; gap: 6mm; justify-content: center;">
                            <!-- Template Card VIRUZS -->
                            <div v-for="customer in getSelectedCustomerData()" :key="customer.id" class="print-card" style="width: 90mm; position: relative; break-inside: avoid;">
                                <!-- Background Template Image -->
                                <img src="/images/card-template.jpg" alt="Card Template" style="width: 100%; display: block; border-radius: 6px;" />
                                
                                <!-- Overlay: Nama Pelanggan -->
                                <div style="position: absolute; top: 46%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                    {{ customer.name || '-' }}
                                </div>
                                
                                <!-- Overlay: Sales -->
                                <div style="position: absolute; top: 61%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                    {{ customer.sales ? customer.sales.name : '-' }}
                                </div>
                                
                                <!-- Overlay: Tanggal Aktivasi -->
                                <div style="position: absolute; top: 76%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 11px; font-weight: 700; color: #1e1b4b; font-family: 'Segoe UI', Arial, sans-serif; line-height: 1;">
                                    {{ formatDate(customer.created_at) }}
                                </div>
                                
                                <!-- Overlay: ID-O -->
                                <div style="position: absolute; top: 91%; left: 41.5%; right: 3%; transform: translateY(-50%); font-size: 12px; font-weight: 800; color: #4c1d95; font-family: 'Consolas', 'Courier New', monospace; line-height: 1; display: flex; justify-content: space-between; align-items: center;">
                                    <span>{{ customer.ont ? customer.ont.ont_id : '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<style>
@media print {
    @page {
        size: A4;
        margin: 10mm;
    }
    body * {
        visibility: hidden !important;
    }
    #print-area, #print-area * {
        visibility: visible !important;
    }
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: none !important;
        background: white !important;
    }
    .no-print, .no-print * {
        display: none !important;
    }
    .print-card {
        page-break-inside: avoid;
        break-inside: avoid;
        box-shadow: none !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router, useForm , usePage} from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import StatCard from '@/Components/StatCard.vue';

const page = usePage();

const isTeknisi = computed(() => {
    try {
        if (!page || !page.props || !page.props.auth || !page.props.auth.user) return false;
        const user = page.props.auth.user;
        if (user.role && typeof user.role === 'string' && user.role.toLowerCase().trim() === 'teknisi') return true;
        let roles = [];
        if (Array.isArray(user.roles)) roles = user.roles;
        else if (user.roles) roles = Object.values(user.roles);
        return roles.some(r => typeof r === 'string' && r.toLowerCase().trim() === 'teknisi');
    } catch (e) {
        return false;
    }
});
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

const showTechDropdown = ref(false);

const getCustomerProgressStatus = (customer) => {
    if (customer.status === 'active') return 'active';
    if (customer.is_audited) return 'menunggu_aktivasi';
    if (customer.technician_schedules && customer.technician_schedules.some(s => s.type === 'installation' && s.status === 'scheduled')) return 'laporan_pasang';
    if (customer.ont && customer.ont.rx_power) return 'audit';
    return 'jadwal_pasang';
};

const props = defineProps({ 
    customers: Object, 
    technicians: Array, 
    availableOnts: { type: Array, default: () => [] }, 
    materialTransactions: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    stats: Object,
    filters: Object 
});


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

const availableMaterialItems = computed(() => {
    let items = [];
    if (props.materialTransactions) {
        props.materialTransactions.forEach(trx => {
            if (trx.items) {
                trx.items.forEach(item => {
                    const name = item.material && item.material.name ? item.material.name.toLowerCase() : '';
                    const category = item.material && item.material.category ? item.material.category.toLowerCase() : '';
                    const isRegistered = item.is_registered_to_ont === 1 || item.is_registered_to_ont === true;
                    
                    if (!name.includes('ont') && !name.includes('modem') && !category.includes('ont') && !category.includes('modem') && !isRegistered) {
                        items.push({
                            ...item,
                            trx_number: trx.transaction_number,
                        });
                    }
                });
            }
        });
    }
    return items;
});

function updateMaterialItemDetails(index) {
    const selectedId = assignForm.material_items[index].id;
    if (!selectedId) return;
    
    const item = availableMaterialItems.value.find(i => i.id === selectedId);
    if (item) {
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
        } else if (item.material && (item.material.category === 'Kabel Drop' || item.material.category === 'Kabel Drop / Frecon' || item.material.category === 'Kabel Frecon')) {
            if (unit === 'roll' || unit === 'pcs') {
                let mpr = parseFloat(item.material.meter_per_roll);
                if (mpr && mpr > 0) {
                    qty = qty * mpr;
                    unit = 'meter';
                }
            }
        }
        
        assignForm.material_items[index].name = item.material ? item.material.name : 'Unknown';
        assignForm.material_items[index].qty = qty;
        assignForm.material_items[index].unit = unit;
        assignForm.material_items[index].trx_number = item.trx_number;
    }
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
                let stock = item.material ? item.material.stock : 0;
                
                // Get area stock if available
                if (item.material && item.material.stocks && trx.area_id) {
                    const areaStock = item.material.stocks.find(s => parseInt(s.area_id) === parseInt(trx.area_id));
                    if (areaStock) {
                        stock = areaStock.stock;
                    }
                }
                
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

const filterState = ref({
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    area: props.filters?.area || '',
    tab: props.filters?.tab || '',
});

function getTabUrl(tab) {
    const params = new URLSearchParams();
    if (tab) params.append('tab', tab);
    if (filterState.value.date_from) params.append('date_from', filterState.value.date_from);
    if (filterState.value.date_to) params.append('date_to', filterState.value.date_to);
    if (filterState.value.area) params.append('area', filterState.value.area);
    if (props.filters?.search) params.append('search', props.filters.search);
    
    return `/customers/installed?${params.toString()}`;
}

function applyFilter() {
    router.get('/customers/installed', {
        ...filterState.value,
        search: props.filters?.search || undefined
    }, { preserveState: true, preserveScroll: true });
}

function resetFilter() {
    filterState.value.date_from = '';
    filterState.value.date_to = '';
    filterState.value.area = '';
    filterState.value.tab = '';
    applyFilter();
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
    let onts = props.availableOnts.filter(ont => ont.status === 'Sudah Set');
    if (!activeCustomer.value || !activeCustomer.value.area_id) return onts;
    return onts.filter(ont => ont.area_id == activeCustomer.value.area_id);
});

const filteredMaterialTransactions = computed(() => {
    if (!activeCustomer.value || !activeCustomer.value.area_id) return props.materialTransactions;
    return props.materialTransactions.filter(trx => trx.area_id == activeCustomer.value.area_id);
});

function openAssignModal(customer) {
    activeCustomer.value = customer;
    assignForm.reset();
    assignForm.scheduled_date = new Date().toISOString().split('T')[0];
    assignForm.scheduled_time = '10:00';
    showAssignModal.value = true;
}

const showDetailModal = ref(false);
const detailCustomer = ref(null);

function openDetailModal(customer) {
    detailCustomer.value = customer;
    showDetailModal.value = true;
}

function closeDetailModal() {
    showDetailModal.value = false;
    setTimeout(() => detailCustomer.value = null, 300);
}

function submitAssign() {
    if (!activeCustomer.value || !activeCustomer.value.id) {
        alert('ERROR: Customer tidak ditemukan! activeCustomer.value = ' + JSON.stringify(activeCustomer.value));
        return;
    }
    
    // Auto-populate material_transaction_ids based on material_items
    const trxSet = new Set();
    assignForm.material_items.forEach(item => {
        if (item.trx_number) {
            trxSet.add(item.trx_number);
        }
    });
    assignForm.material_transaction_ids = Array.from(trxSet);
    
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

// Print Card Logic
const isPrintModalOpen = ref(false);

function openPrintModal() {
    isPrintModalOpen.value = true;
}

function printCards() {
    window.print();
}

function getSelectedCustomerData() {
    return props.customers.data.filter(customer => selectedIds.value.includes(customer.id));
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${day} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

const hardwareItems = computed(() => {
    if (!detailCustomer.value) return [];
    const schedule = detailCustomer.value.technician_schedules?.find(s => s.type === 'installation');
    if (!schedule || !schedule.notes) return [];
    
    const lines = schedule.notes.split('\n');
    const items = [];
    let idCounter = 0;
    
    lines.forEach(line => {
        if (line.startsWith('ONT: ')) {
            const onts = line.replace('ONT: ', '').split(', ');
            onts.forEach(ont => {
                if(ont.trim()) items.push({ id: idCounter++, type: 'ONT', name: ont.trim() });
            });
        } else if (line.startsWith('Material: ')) {
            const mats = line.replace('Material: ', '').split(', ');
            mats.forEach(mat => {
                if(mat.trim()) items.push({ id: idCounter++, type: 'Material', name: mat.trim() });
            });
        }
    });
    return items;
});

const getAssignedTechnicians = (customer, type) => {
    if (!customer?.technician_schedules) return '-';
    const schedules = customer.technician_schedules.filter(s => s.type === type);
    if (schedules.length === 0) return '-';
    return schedules.map(s => s.technician?.name).filter(Boolean).join(', ') || '-';
};

</script>
