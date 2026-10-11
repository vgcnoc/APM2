<template>
    <AppLayout title="Data ODC">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Manajemen ODC</h2>
                    <p class="text-gray-500 text-sm mt-1">Kelola data Optical Distribution Cabinet (ODC)</p>
                </div>
                <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah ODC
                </button>
            </div>

            <!-- Filters -->
            <div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input v-model="filterSearch" type="text" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari nama atau lokasi ODC..." />
                    </div>
                </div>
                <div class="sm:w-48">
                    <select v-model="filterArea" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                    </select>
                </div>
                <div class="sm:w-48">
                    <select v-model="filterOlt" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua OLT</option>
                        <option v-for="olt in olts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                    </select>
                </div>
                <div class="sm:w-48">
                    <select v-model="filterStatus" class="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama ODC</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Area / Wilayah</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Induk OLT</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Kapasitas</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="odc in odcs.data" :key="odc.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ odc.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span v-if="odc.area" class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-bold border border-blue-100">{{ odc.area.name }}</span>
                                <span v-else class="text-xs text-gray-400 italic">Belum diset</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="font-bold text-gray-900">{{ odc.olt ? odc.olt.name : '-' }}</div>
                                <div v-if="odc.pon_port" class="mt-1">
                                    <span class="bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded text-[11px] font-bold border border-indigo-200">
                                        PON {{ odc.pon_port }}
                                    </span>
                                    <div v-if="getPonVlans(odc).length > 0" class="flex flex-wrap gap-1 mt-1">
                                        <span v-for="(v, vi) in getPonVlans(odc)" :key="vi" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                            <span>{{ v.vlan_id }}</span>
                                            <span class="opacity-75">· {{ v.name }}</span>
                                            <span class="px-1 py-0.2 rounded text-[9px] bg-white/70 font-bold uppercase">{{ v.access_mode }}</span>
                                        </span>
                                    </div>
                                    <div v-else class="text-[11px] text-gray-400 italic mt-0.5">
                                        (Belum diset VLAN di PON {{ odc.pon_port }})
                                    </div>
                                </div>
                                <div v-else class="text-[11px] text-gray-400 italic mt-0.5">
                                    Port PON belum dipilih
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ odc.capacity }} Port</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="odc.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ odc.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <Link :href="`/odcs/${odc.id}`" class="text-blue-600 hover:text-blue-900 font-medium mr-3">Detail</Link>
                                <button @click="openEditModal(odc)" class="text-indigo-600 hover:text-indigo-900 font-medium mr-3">Edit</button>
                                <button @click="deleteOdc(odc.id)" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="odcs.data.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                                Belum ada data ODC.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Tambah/Edit ODC -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto my-auto">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit ODC' : 'Tambah ODC Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit">
                    <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto bg-gray-50/30">
                        
                        <!-- Informasi Dasar -->
                        <div class="border border-indigo-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-indigo-50/50 px-4 py-3 border-b border-indigo-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                <span class="text-sm font-semibold text-indigo-900">Informasi Dasar</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div v-if="form.type !== 'Split'">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODC</label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ODC-01" />
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
                                <div :class="form.type === 'Split' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Induk OLT</label>
                                    <select v-model="form.olt_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required :disabled="!form.area_id">
                                        <option value="">Pilih OLT Induk</option>
                                        <option v-for="olt in filteredOlts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                                    </select>
                                    <p v-if="form.errors.olt_id" class="text-red-500 text-xs mt-1">{{ form.errors.olt_id }}</p>
                                </div>
                                <div v-if="form.olt_id" :class="form.type === 'Split' ? 'col-span-2' : ''">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Port PON (Opsional)</label>
                                    <select v-model="form.pon_port" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Pilih Port PON (Opsional)</option>
                                        <option v-for="port in selectedOltPonPorts" :key="port" :value="port">PON {{ port }}</option>
                                    </select>
                                    <p v-if="form.errors.pon_port" class="text-red-500 text-xs mt-1">{{ form.errors.pon_port }}</p>
                                </div>

                                <!-- Preview Sinkronisasi VLAN & Mode Akses dari OLT -->
                                <div v-if="form.olt_id && form.pon_port" class="col-span-1 sm:col-span-2 bg-blue-50/70 border border-blue-200 rounded-xl p-3.5 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-blue-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Sinkronisasi VLAN & Mode Akses dari OLT (PON {{ form.pon_port }})
                                        </span>
                                        <span class="text-[11px] text-blue-700 font-semibold">{{ currentModalPonVlans.length }} VLAN Terdeteksi</span>
                                    </div>
                                    <div v-if="currentModalPonVlans.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                                        <span v-for="(v, vi) in currentModalPonVlans" :key="vi" class="px-2 py-1 rounded-lg text-xs font-semibold border flex items-center gap-1.5 bg-white shadow-2xs" :class="getModeBadgeClass(v.access_mode)">
                                            <span class="font-mono font-bold">{{ v.vlan_id }}</span>
                                            <span class="text-gray-700">· {{ v.name }}</span>
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border bg-gray-50">{{ v.access_mode }}</span>
                                        </span>
                                    </div>
                                    <div v-else class="text-xs text-blue-600/80 italic">
                                        Belum ada VLAN diset di OLT untuk PON {{ form.pon_port }}. ODC ini akan otomatis tersinkron begitu VLAN diset di OLT.
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Tipe ODC</label>
                                    <select v-model="form.type" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="Normal">Normal</option>
                                        <option value="Split">Split</option>
                                    </select>
                                    <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas (Port)</label>
                                    <input v-model="form.capacity" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                    <p v-if="form.errors.capacity" class="text-red-500 text-xs mt-1">{{ form.errors.capacity }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Split -->
                        <div v-if="form.type === 'Split'" class="space-y-4">
                            <!-- Alert Split -->
                            <div class="bg-indigo-50 text-indigo-800 px-4 py-3 rounded-lg border border-indigo-200 text-sm flex items-start gap-3">
                                <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div>
                                    <p><span class="font-bold">Split:</span> ODC dibagi menjadi 2 unit dengan konfigurasi khusus.</p>
                                    <p class="text-xs mt-1 opacity-80" :class="totalSplitPorts > form.capacity ? 'text-red-600 font-bold' : ''">
                                        Total Kapasitas Unit: {{ totalSplitPorts }} / {{ form.capacity }} 
                                        <span v-if="totalSplitPorts > form.capacity">(Melebihi kapasitas utama!)</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Tabs -->
                            <div class="flex gap-2 bg-gray-100 p-1 rounded-lg">
                                <button type="button" @click="activeTab = 0" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 0 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODC Induk (Kapasitas: {{ form.split_units[0].ratio }})
                                </button>
                                <button type="button" @click="activeTab = 1" 
                                    class="flex-1 py-2 text-sm font-semibold rounded-md transition-all"
                                    :class="activeTab === 1 ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700'">
                                    ODC Lanjutan (Kapasitas: {{ form.split_units[1].ratio }})
                                </button>
                            </div>

                            <!-- Tab Content -->
                            <div class="border border-indigo-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-white px-4 py-3 border-b border-indigo-100 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span class="text-sm font-semibold text-indigo-900">
                                        {{ activeTab === 0 ? 'ODC Induk' : 'ODC Lanjutan' }}
                                    </span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama ODC Unit</label>
                                            <input v-model="form.split_units[activeTab].name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas</label>
                                            <input v-model="form.split_units[activeTab].ratio" type="number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat ODC</label>
                                        <textarea v-model="form.split_units[activeTab].location" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Alamat lokasi ODC"></textarea>
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
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODC</label>
                                        <input type="file" @change="e => form.split_units[activeTab].photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" accept="image/*" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- UI untuk Normal -->
                        <div v-if="form.type === 'Normal'" class="space-y-6">
                            <!-- Lokasi -->
                            <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-green-50/50 px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-green-900">Lokasi & Koordinat</span>
                                </div>
                                <div class="p-4 space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat ODC</label>
                                        <textarea v-model="form.location" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Masukkan alamat lengkap"></textarea>
                                        <p v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</p>
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
                                </div>
                            </div>
                            
                            <!-- Detail -->
                            <div class="border border-purple-100 rounded-xl overflow-hidden bg-white">
                                <div class="bg-purple-50/50 px-4 py-3 border-b border-purple-100 flex items-center gap-2">
                                    <span class="text-sm font-semibold text-purple-900">Detail & Status</span>
                                </div>
                                <div class="p-4 grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Status</label>
                                        <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="maintenance">Maintenance</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Foto ODC</label>
                                        <input type="file" @change="e => form.photo = e.target.files[0]" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" accept="image/*" />
                                        <p v-if="form.errors.photo" class="text-red-500 text-xs mt-1">{{ form.errors.photo }}</p>
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
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            Simpan ODC
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    odcs: Object,
    olts: Array,
    areas: Array,
    filters: Object
});

const filterSearch = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const filterOlt = ref(props.filters?.olt_id || '');
const filterStatus = ref(props.filters?.status || '');

let filterTimeout = null;
watch([filterSearch, filterArea, filterOlt, filterStatus], ([search, area, olt, status]) => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/odcs', { search, area_id: area, olt_id: olt, status }, {
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
    olt_id: '',
    pon_port: '',
    area_id: '',
    type: 'Normal',
    capacity: 144,
    location: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: '',
    photo: null,
    split_units: [
        {
            name: 'ODC Induk',
            ratio: 72,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODC Induk',
            cable_pull: 'Individu',
            photo: null
        },
        {
            name: 'ODC Lanjutan',
            ratio: 72,
            location: '',
            latitude: '',
            longitude: '',
            start_point: '',
            end_point: 'ODC Lanjutan',
            cable_pull: 'Individu',
            photo: null
        }
    ]
});

// Initialize default ratio when type changes to Split
watch(() => form.type, (newType) => {
    if (newType === 'Split') {
        const half = Math.floor(form.capacity / 2);
        form.split_units[0].ratio = half;
        form.split_units[1].ratio = half;
    }
});

const selectedOltPonPorts = computed(() => {
    if (!form.olt_id) return [];
    const olt = props.olts.find(o => o.id === form.olt_id);
    if (!olt || !olt.total_pon_ports) return [];
    return Array.from({ length: olt.total_pon_ports }, (_, i) => i + 1);
});

// Watch changes to olt_id to reset pon_port if necessary
watch(() => form.olt_id, (newVal) => {
    if (newVal) {
        const olt = props.olts.find(o => o.id === newVal);
        if (olt && olt.total_pon_ports && form.pon_port > olt.total_pon_ports) {
            form.pon_port = '';
        }
    } else {
        form.pon_port = '';
    }
});

const filteredOlts = computed(() => {
    if (!form.area_id) return [];
    return props.olts.filter(o => o.area_id == form.area_id);
});

watch(() => form.area_id, (newVal) => {
    // Reset OLT if the selected OLT doesn't belong to the new Area
    if (form.olt_id) {
        const olt = props.olts.find(o => o.id === form.olt_id);
        if (olt && olt.area_id != newVal) {
            form.olt_id = '';
        }
    }
});

const totalSplitPorts = computed(() => {
    return Number(form.split_units[0].ratio || 0) + Number(form.split_units[1].ratio || 0);
});

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    activeTab.value = 0;
    isModalOpen.value = true;
}

function openEditModal(odc) {
    isEditing.value = true;
    editId.value = odc.id;
    form.name = odc.name;
    form.olt_id = odc.olt_id;
    form.pon_port = odc.pon_port || '';
    form.area_id = odc.area_id || '';
    form.type = odc.type || 'Normal';
    form.capacity = odc.capacity;
    form.location = odc.location;
    form.latitude = odc.latitude;
    form.longitude = odc.longitude;
    form.status = odc.status;
    form.description = odc.description;
    form.photo = null;
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

function getPonVlans(odc) {
    if (!odc || !odc.olt || !odc.pon_port || !odc.olt.pon_vlans) return [];
    let vlans = odc.olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    
    const ponData = vlans.find(p => p.port == odc.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
}

const currentModalPonVlans = computed(() => {
    if (!form.olt_id || !form.pon_port) return [];
    const olt = props.olts.find(o => o.id === form.olt_id);
    if (!olt || !olt.pon_vlans) return [];
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const ponData = vlans.find(p => p.port == form.pon_port);
    if (!ponData || !ponData.vlans || !Array.isArray(ponData.vlans)) return [];
    return ponData.vlans.map(v => ({
        name: v.name,
        vlan_id: v.vlan_id,
        access_mode: getVlanAccessMode(v)
    }));
});

function submit() {
    if (isEditing.value) {
        form.post(`/odcs/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form._method = 'post';
        form.post('/odcs', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOdc(id) {
    if (confirm('Apakah Anda yakin ingin menghapus ODC ini?')) {
        router.post(`/odcs/${id}/delete`);
    }
}
</script>
