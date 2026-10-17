<template>
    <AppLayout title="Data ODP">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ODP</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data Optical Distribution Point (ODP)</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Tombol Hapus Terpilih jika ada yang dicentang -->
                    <button 
                        v-if="selectedIds.length > 0"
                        @click="openDeleteModal('selected')"
                        type="button"
                        class="px-3.5 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-semibold text-sm rounded-lg border border-red-200 shadow-sm flex items-center gap-2 transition-all"
                    >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Terpilih ({{ selectedIds.length }})</span>
                    </button>

                    <!-- Tombol Hapus Semua -->
                    <button 
                        @click="openDeleteModal('all')"
                        type="button"
                        class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-lg shadow-sm flex items-center gap-2 transition-all"
                        title="Hapus Seluruh Data ODP"
                    >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Hapus Semua</span>
                    </button>

                    <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Tambah ODP
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input v-model="filterSearch" type="text" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari nama atau alamat ODP..." />
                    </div>
                </div>
                <div class="sm:w-48">
                    <select v-model="filterArea" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                    </select>
                </div>
                <div class="sm:w-48">
                    <select v-model="filterOdc" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua ODC</option>
                        <option v-for="odc in odcs" :key="odc.id" :value="odc.id">{{ odc.name }}</option>
                    </select>
                </div>
                <div class="sm:w-48">
                    <select v-model="filterStatus" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="full">Full (Penuh)</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input v-model="filterAvailableOnly" type="checkbox" class="form-checkbox h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                        <span class="ml-2 text-sm text-gray-700 font-medium">Hanya Port Tersedia</span>
                    </label>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="w-12 px-4 py-4 text-center">
                                <input 
                                    type="checkbox" 
                                    :checked="isAllSelected" 
                                    @change="toggleSelectAll" 
                                    class="rounded border-gray-300 text-red-600 focus:ring-red-500 h-4 w-4 cursor-pointer" 
                                    title="Pilih Semua ODP di Halaman Ini"
                                />
                            </th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama ODP</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Induk ODC</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Port</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="odp in odps.data" :key="odp.id" :class="['hover:bg-gray-50/50 transition-colors', selectedIds.includes(odp.id) ? 'bg-red-50/30' : '']">
                            <td class="w-12 px-4 py-4 text-center">
                                <input 
                                    type="checkbox" 
                                    :value="odp.id" 
                                    v-model="selectedIds" 
                                    class="rounded border-gray-300 text-red-600 focus:ring-red-500 h-4 w-4 cursor-pointer" 
                                />
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ odp.name }}
                                <span v-if="odp.is_split" class="ml-2 px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">Split</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span v-if="odp.area" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-bold border border-blue-100">{{ odp.area.name }}</span>
                                <span v-else class="text-xs text-gray-400 italic">Belum diset</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="font-bold text-gray-900">{{ odp.odc ? odp.odc.name : '-' }}</div>
                                <div v-if="odp.odc" class="mt-1">
                                    <div class="text-[11px] text-gray-500 flex flex-wrap items-center gap-1.5">
                                        <span class="font-medium text-indigo-700 bg-indigo-50 px-1.5 py-0.2 rounded border border-indigo-100">
                                            OLT: {{ odp.odc.olt ? odp.odc.olt.name : '-' }}
                                        </span>
                                        <span v-if="odp.odc.pon_port" class="font-semibold text-indigo-700 bg-indigo-50 px-1.5 py-0.2 rounded border border-indigo-100">
                                            PON {{ odp.odc.pon_port }}
                                        </span>
                                        <span v-if="odp.odc.splitter_ratio" class="font-semibold text-purple-700 bg-purple-50 px-1.5 py-0.2 rounded border border-purple-100">
                                            Splitter {{ odp.odc.splitter_ratio }}
                                        </span>
                                    </div>
                                    <div v-if="getOdpPonVlans(odp).length > 0" class="flex flex-wrap gap-1 mt-1">
                                        <span v-for="(v, vi) in getOdpPonVlans(odp)" :key="vi" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                            <span>{{ v.vlan_id }}</span>
                                            <span class="opacity-75">· {{ v.name }}</span>
                                            <span class="px-1 py-0.2 rounded text-[9px] bg-white/70 font-bold uppercase">{{ v.access_mode }}</span>
                                        </span>
                                    </div>
                                    <div v-else-if="odp.odc.pon_port" class="text-[10px] text-gray-400 italic mt-0.5">
                                        (Belum diset VLAN di PON {{ odp.odc.pon_port }})
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ odp.used_ports }} / {{ odp.total_ports }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="odp.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ odp.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <Link :href="`/odps/${odp.id}`" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                <button @click="openEditModal(odp)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                <button @click="deleteOdp(odp.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="odps.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500 text-sm">
                                Belum ada data ODP.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="odps.links && odps.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-gray-500">Menampilkan {{ odps.from || 0 }} - {{ odps.to || 0 }} dari {{ odps.total || 0 }} data ODP</p>
                    <div class="flex gap-1 flex-wrap">
                        <Link 
                            v-for="(link, i) in odps.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-40 cursor-not-allowed pointer-events-none'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit ODP -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit ODP' : 'Tambah ODP Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        
                        <!-- Informasi Dasar -->
                        <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-white px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span class="text-sm font-semibold text-purple-900">Informasi Dasar</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-if="form.type !== 'Split 2'">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODP</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ODP-01-ABC" />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Area / Wilayah</label>
                                    <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="">Pilih Area</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                                </div>
                                <div :class="form.type === 'Split 2' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Induk ODC</label>
                                    <select v-model="form.odc_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required :disabled="!form.area_id">
                                        <option value="">Pilih ODC Induk</option>
                                        <option v-for="odc in filteredOdcs" :key="odc.id" :value="odc.id">{{ odc.name }} ({{ odc.capacity }} Port)</option>
                                    </select>
                                    <p v-if="form.errors.odc_id" class="text-red-500 text-xs mt-1">{{ form.errors.odc_id }}</p>
                                </div>

                                <!-- Preview Sinkronisasi VLAN & Mode Akses dari OLT melalui ODC -->
                                <div v-if="selectedOdcInfo" class="col-span-1 sm:col-span-2 bg-purple-50/70 border border-purple-200 rounded-xl p-3.5 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-purple-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Sinkronisasi VLAN & Mode Akses dari OLT (via {{ selectedOdcInfo.name }})
                                        </span>
                                        <span class="text-[11px] text-purple-700 font-semibold">{{ currentModalOdpVlans.length }} VLAN Terdeteksi</span>
                                    </div>
                                    <div class="text-[11px] text-purple-800">
                                        Induk OLT: <strong>{{ selectedOdcInfo.olt ? selectedOdcInfo.olt.name : '-' }}</strong>
                                        <span v-if="selectedOdcInfo.pon_port"> · Port <strong>PON {{ selectedOdcInfo.pon_port }}</strong></span>
                                    </div>
                                    <div v-if="currentModalOdpVlans.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                                        <span v-for="(v, vi) in currentModalOdpVlans" :key="vi" class="px-2 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1.5 bg-white shadow-2xs" :class="getModeBadgeClass(v.access_mode)">
                                            <span class="font-mono font-bold">{{ v.vlan_id }}</span>
                                            <span class="text-gray-700">· {{ v.name }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border bg-gray-50">{{ v.access_mode }}</span>
                                        </span>
                                    </div>
                                    <div v-else class="text-xs text-purple-600/80 italic">
                                        Belum ada VLAN diset di OLT untuk ODC ini. ODP akan otomatis tersinkron begitu VLAN diset di OLT.
                                    </div>
                                </div>
                                
                                <div v-if="isBranchMismatch" class="col-span-2 bg-orange-50 border border-orange-200 rounded-lg p-3 flex gap-3">
                                    <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <div>
                                        <h4 class="text-sm font-bold text-orange-800">Cabang ODP tidak sesuai dengan ODC!</h4>
                                        <p class="text-xs text-orange-700 mt-1">ODC terdaftar di cabang yang berbeda dengan cabang ODP yang Anda pilih. Pastikan data cabang sudah benar.</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tipe ODP</label>
                                    <select v-model="form.type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="Normal">Normal</option>
                                        <option value="Split 2">Split 2</option>
                                    </select>
                                    <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas Port</label>
                                    <input v-model="form.total_ports" type="number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                    <p v-if="form.errors.total_ports" class="text-red-500 text-xs mt-1">{{ form.errors.total_ports }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Split 2 -->
                        <div v-if="form.type === 'Split 2'" class="space-y-4">
                            <!-- Alert Split 2 -->
                            <div class="bg-purple-50 text-purple-800 px-4 py-3 rounded-lg border border-purple-200 text-sm flex items-start gap-3">
                                <svg class="w-5 h-5 text-purple-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p><span class="font-bold">Split 2:</span> ODP dibagi menjadi 2 unit dengan konfigurasi khusus.</p>
                                    <p class="text-xs mt-1 opacity-80" :class="totalSplitPorts > form.total_ports ? 'text-red-600 font-bold' : ''">
                                        Total Port Unit: {{ totalSplitPorts }} / {{ form.total_ports }} 
                                        <span v-if="totalSplitPorts > form.total_ports">(Melebihi kapasitas utama!)</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="flex gap-2 bg-gray-100 p-1 rounded-lg">
                                <button type="button" @click="activeTab = 0" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 0 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODP Induk 1:{{ form.split_units[0].ratio }}
                                </button>
                                <button type="button" @click="activeTab = 1" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 1 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODP Lanjutan 1:{{ form.split_units[1].ratio }}
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <span class="text-sm font-semibold text-purple-900">
                                        {{ activeTab === 0 ? 'ODP Induk' : 'ODP Lanjutan' }} 1:{{ form.split_units[activeTab].ratio }}
                                    </span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODP Unit</label>
                                            <input v-model="form.split_units[activeTab].name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Rasio</label>
                                            <input v-model="form.split_units[activeTab].ratio" type="number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat</label>
                                        <textarea v-model="form.split_units[activeTab].address" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Alamat lokasi ODP"></textarea>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Titik Koordinat</label>
                                            <button type="button" @click="getLocationForSplit(activeTab)" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Auto GPS
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input v-model="form.split_units[activeTab].latitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Latitude" />
                                            <input v-model="form.split_units[activeTab].longitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Longitude" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Titik Awal</label>
                                            <input v-model="form.split_units[activeTab].start_point" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Titik Akhir Kabel</label>
                                            <input v-model="form.split_units[activeTab].end_point" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Penarikan Kabel</label>
                                        <select v-model="form.split_units[activeTab].cable_pull" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="Individu">Individu</option>
                                            <option value="Bersama">Bersama</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODP</label>
                                        <input type="file" @change="e => form.split_units[activeTab].photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Normal -->
                        <div v-if="form.type === 'Normal'" class="space-y-6">
                            <!-- Alamat -->
                            <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-green-900">Alamat</span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat lokasi ODP</label>
                                        <textarea v-model="form.address" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                        <p v-if="form.errors.address" class="text-red-500 text-xs mt-1">{{ form.errors.address }}</p>
                                    </div>
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Titik Koordinat</label>
                                            <button type="button" @click="getLocation" class="px-3 py-1.5 bg-white border border-gray-200 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-50 flex items-center gap-1 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Auto GPS
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input v-model="form.latitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Latitude" />
                                            <input v-model="form.longitude" type="text" class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Longitude" />
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODP</label>
                                        <div v-if="form.existing_photo" class="mb-3">
                                            <a :href="`/storage/${form.existing_photo}`" target="_blank" class="block w-40 overflow-hidden rounded-lg border border-gray-200 group relative">
                                                <img :src="`/storage/${form.existing_photo}`" alt="Foto ODP" class="w-full h-24 object-cover" />
                                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold uppercase text-center">
                                                    Lihat / Download
                                                </div>
                                            </a>
                                            <p class="text-[10px] text-gray-500 mt-1">Upload file baru untuk mengganti foto ini.</p>
                                        </div>
                                        <input type="file" @change="e => form.photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        <p v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detail ODP -->
                            <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-purple-900">Detail ODP</span>
                                </div>
                                <div class="p-4 grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Status</label>
                                        <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="full">Full</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Keterangan Tambahan</label>
                                        <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <div class="sticky bottom-0 bg-white p-6 border-t border-gray-100 flex justify-end rounded-b-2xl shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 mr-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-[#1e40af] rounded-lg hover:bg-blue-900 disabled:opacity-50">
                            Simpan ODP
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Semua / Terpilih -->
        <Teleport to="body">
            <div v-if="showDeleteConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm animate-fade-in">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-red-100">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center mx-auto border-4 border-red-50 shadow-inner">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ deleteConfirmMode === 'selected' ? `Hapus ${selectedIds.length} Data ODP Terpilih?` : 'Hapus Seluruh Data ODP?' }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                {{ deleteConfirmMode === 'selected'
                                    ? `Tindakan ini akan menghapus ${selectedIds.length} data ODP yang Anda pilih secara permanen.`
                                    : `Tindakan ini akan menghapus SEMUA data ODP (${odps.total || odps.data.length} ODP) yang ada pada sistem secara permanen!` 
                                }}
                            </p>
                        </div>

                        <div class="bg-amber-50 p-3 rounded-xl border border-amber-200 text-left text-xs text-amber-800 space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-amber-900">
                                <span>🛡️</span> Perlindungan Data Pelanggan & Modem:
                            </div>
                            <p class="text-[11px] leading-relaxed">
                                Perangkat ONT yang tertaut ke ODP yang dihapus tidak akan terhapus, melainkan statusnya otomatis dialihkan menjadi <span class="font-bold">"Belum Set / Baru Input"</span> agar data modem & pelanggan tetap aman.
                            </p>
                        </div>

                        <!-- Konfirmasi Text HAPUS untuk Hapus Semua -->
                        <div v-if="deleteConfirmMode === 'all'" class="text-left space-y-1.5 pt-1">
                            <label class="block text-xs font-semibold text-gray-700">
                                Ketik <span class="font-bold text-red-600 font-mono">HAPUS</span> untuk konfirmasi:
                            </label>
                            <input 
                                v-model="deleteConfirmInput" 
                                type="text" 
                                placeholder="Ketik HAPUS" 
                                class="w-full px-3 py-2 border border-red-300 rounded-lg text-sm text-center uppercase tracking-widest font-mono font-bold focus:ring-2 focus:ring-red-500 focus:outline-none"
                            />
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button 
                                type="button" 
                                @click="closeDeleteModal" 
                                class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm rounded-xl transition-colors"
                            >
                                Batal
                            </button>
                            <button 
                                type="button" 
                                @click="executeBulkDelete" 
                                :disabled="isDeleting || (deleteConfirmMode === 'all' && deleteConfirmInput.toUpperCase() !== 'HAPUS')"
                                class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-bold text-sm rounded-xl transition-colors shadow-md shadow-red-200 flex items-center justify-center gap-2"
                            >
                                <svg v-if="isDeleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ isDeleting ? 'Menghapus...' : (deleteConfirmMode === 'selected' ? 'Hapus Terpilih' : 'Ya, Hapus Semua') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odps: Object,
    odcs: Array,
    areas: Array,
    filters: Object
});

const filterSearch = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const filterOdc = ref(props.filters?.odc_id || '');
const filterStatus = ref(props.filters?.status || '');
const filterAvailableOnly = ref(props.filters?.available_only === 'true' || props.filters?.available_only === true);

let filterTimeout = null;
watch([filterSearch, filterArea, filterOdc, filterStatus, filterAvailableOnly], ([search, area, odc, status, availableOnly]) => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/odps', { search, area_id: area, odc_id: odc, status, available_only: availableOnly || undefined }, {
            preserveState: true,
            replace: true
        });
    }, 300);
});

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);
const activeTab = ref(0);

const form = useForm({
    name: '',
    odc_id: '',
    area_id: '',
    type: 'Normal',
    total_ports: 8,
    address: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: '',
    photo: null,
    existing_photo: null,
    split_units: [
        {
            name: 'ODP Induk 1:4',
            ratio: 4,
            address: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODP Induk 1:4',
            cable_pull: 'Individu',
            photo: null
        },
        {
            name: 'ODP Lanjutan 1:4',
            ratio: 4,
            address: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODP Lanjutan 1:4',
            cable_pull: 'Individu',
            photo: null
        }
    ]
});

// Initialize default ratio when type changes to Split 2
watch(() => form.type, (newType) => {
    if (newType === 'Split 2') {
        const half = Math.floor(form.total_ports / 2);
        form.split_units[0].ratio = half;
        form.split_units[1].ratio = half;
        form.split_units[0].name = `ODP Induk 1:${half}`;
        form.split_units[1].name = `ODP Lanjutan 1:${half}`;
        form.split_units[0].end_point = `ODP Induk 1:${half}`;
        form.split_units[1].end_point = `ODP Lanjutan 1:${half}`;
    }
});

// Watch ratio changes to update names dynamically (only if they still match the default pattern)
watch(() => form.split_units[0].ratio, (newRatio, oldRatio) => {
    if (form.split_units[0].name === `ODP Induk 1:${oldRatio}` || !form.split_units[0].name) {
        form.split_units[0].name = `ODP Induk 1:${newRatio}`;
    }
    if (form.split_units[0].end_point === `ODP Induk 1:${oldRatio}` || !form.split_units[0].end_point) {
        form.split_units[0].end_point = `ODP Induk 1:${newRatio}`;
    }
});

watch(() => form.split_units[1].ratio, (newRatio, oldRatio) => {
    if (form.split_units[1].name === `ODP Lanjutan 1:${oldRatio}` || !form.split_units[1].name) {
        form.split_units[1].name = `ODP Lanjutan 1:${newRatio}`;
    }
    if (form.split_units[1].end_point === `ODP Lanjutan 1:${oldRatio}` || !form.split_units[1].end_point) {
        form.split_units[1].end_point = `ODP Lanjutan 1:${newRatio}`;
    }
});

const totalSplitPorts = computed(() => {
    return Number(form.split_units[0].ratio || 0) + Number(form.split_units[1].ratio || 0);
});

const filteredOdcs = computed(() => {
    if (!form.area_id) return [];
    return props.odcs.filter(o => o.area_id == form.area_id);
});

watch(() => form.area_id, (newVal) => {
    if (form.odc_id) {
        const odc = props.odcs.find(o => o.id === form.odc_id);
        if (odc && odc.area_id != newVal) {
            form.odc_id = '';
        }
    }
});

const isBranchMismatch = computed(() => {
    if (!form.odc_id || !form.area_id) return false;
    const odc = props.odcs.find(o => o.id === form.odc_id);
    if (!odc || !odc.area_id) return false;
    return odc.area_id !== form.area_id;
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.photo = null;
    form.existing_photo = null;
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function openEditModal(odp) {
    isEditing.value = true;
    editId.value = odp.id;
    form.name = odp.name;
    form.odc_id = odp.odc_id;
    form.area_id = odp.area_id;
    form.type = odp.type || 'Normal';
    form.total_ports = odp.total_ports;
    form.address = odp.address;
    form.latitude = odp.latitude;
    form.longitude = odp.longitude;
    form.status = odp.status;
    form.description = odp.description;
    form.photo = null; // Clear file input
    form.existing_photo = odp.photo || null;
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.latitude = position.coords.latitude.toFixed(8);
                form.longitude = position.coords.longitude.toFixed(8);
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
}

function getLocationForSplit(tabIndex) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.split_units[tabIndex].latitude = position.coords.latitude.toFixed(8);
                form.split_units[tabIndex].longitude = position.coords.longitude.toFixed(8);
            },
            (error) => {
                alert('Gagal mendapatkan lokasi: ' + error.message);
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda.');
    }
}

function getVlanAccessMode(vlan) {
    if (vlan.access_mode) return vlan.access_mode;
    const name = (vlan.name || '').toLowerCase();
    if (name.includes('pppoe')) return 'PPPOE';
    if (name.includes('hotspot')) return 'Hotspot';
    if (name.includes('static')) return 'Static';
    if (name.includes('dhcp')) return 'DHCP';
    if (name.includes('bridge')) return 'Bridge';
    if (name.includes('trunk') || name.includes('tagged')) return 'Tagged';
    if (name.includes('access') || name.includes('untagged')) return 'Untagged';
    return 'PPPOE';
}

function getModeBadgeClass(mode) {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'HOTSPOT':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'STATIC':
        case 'STATIC IP':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'DHCP':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'BRIDGE':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200';
        case 'TAGGED':
        case 'TRUNK':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'UNTAGGED':
        case 'ACCESS':
            return 'bg-gray-100 text-gray-700 border-gray-200';
        default:
            return 'bg-blue-50 text-blue-700 border-blue-200';
    }
}

function getOdpPonVlans(odp) {
    if (!odp || !odp.odc || !odp.odc.pon_port || !odp.odc.olt || !odp.odc.olt.pon_vlans) return [];
    let vlans = odp.odc.olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    
    const ponData = vlans.find(p => p.port == odp.odc.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
}

const selectedOdcInfo = computed(() => {
    if (!form.odc_id) return null;
    return props.odcs.find(o => o.id === form.odc_id);
});

const currentModalOdpVlans = computed(() => {
    if (!selectedOdcInfo.value || !selectedOdcInfo.value.pon_port || !selectedOdcInfo.value.olt || !selectedOdcInfo.value.olt.pon_vlans) return [];
    let vlans = selectedOdcInfo.value.olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const ponData = vlans.find(p => p.port == selectedOdcInfo.value.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
});

function submit() {
    if (isEditing.value) {
        form.post(`/odps/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/odps', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOdp(id) {
    if (confirm('Apakah Anda yakin ingin menghapus ODP ini?')) {
        router.post(`/odps/${id}/delete`);
    }
}

// ── Bulk Delete / Hapus Semua ODP ────────────────────────────
const selectedIds = ref([]);

const isAllSelected = computed(() => {
    if (!props.odps?.data || props.odps.data.length === 0) return false;
    return props.odps.data.every(odp => selectedIds.value.includes(odp.id));
});

function toggleSelectAll() {
    if (!props.odps?.data) return;
    if (isAllSelected.value) {
        const pageIds = props.odps.data.map(o => o.id);
        selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id));
    } else {
        const newIds = props.odps.data.map(o => o.id);
        selectedIds.value = Array.from(new Set([...selectedIds.value, ...newIds]));
    }
}

const showDeleteConfirmModal = ref(false);
const deleteConfirmMode = ref('all');
const deleteConfirmInput = ref('');
const isDeleting = ref(false);

function openDeleteModal(mode = 'all') {
    deleteConfirmMode.value = mode;
    deleteConfirmInput.value = '';
    showDeleteConfirmModal.value = true;
}

function closeDeleteModal() {
    showDeleteConfirmModal.value = false;
    deleteConfirmInput.value = '';
    isDeleting.value = false;
}

function executeBulkDelete() {
    if (deleteConfirmMode.value === 'all' && deleteConfirmInput.value.toUpperCase() !== 'HAPUS') {
        alert('Ketik HAPUS untuk konfirmasi hapus semua data ODP.');
        return;
    }

    isDeleting.value = true;
    router.post('/odps/bulk-destroy', {
        all: deleteConfirmMode.value === 'all',
        ids: deleteConfirmMode.value === 'selected' ? selectedIds.value : [],
        search: filterSearch.value,
        area_id: filterArea.value,
        odc_id: filterOdc.value,
        status: filterStatus.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            selectedIds.value = [];
        },
        onFinish: () => {
            isDeleting.value = false;
        }
    });
}
</script>
