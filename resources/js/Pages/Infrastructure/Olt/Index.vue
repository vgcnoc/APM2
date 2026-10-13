<template>
    <AppLayout title="Multi OLT Management" subtitle="Pengelolaan Seluruh Perangkat Optical Line Terminal (OLT) Lintas Wilayah">
        <div class="space-y-6">
            <!-- Nav Tabs Multi OLT -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto">
                <Link href="/olts" class="px-4 py-2.5 rounded-xl text-sm font-bold flex items-center gap-2 transition-all shrink-0 bg-indigo-600 text-white shadow-sm shadow-indigo-500/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span>1. Data & Perangkat OLT</span>
                    <span v-if="stats?.total_olt > 0" class="px-2 py-0.5 text-xs font-black rounded-full bg-white text-indigo-600 ml-1">{{ stats.total_olt }}</span>
                </Link>
                <Link href="/olts/pon-monitor" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>2. Monitoring Port PON</span>
                    <span v-if="stats?.total_pon_ports > 0" class="px-2 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-700 ml-1">{{ stats.total_pon_ports }} PON</span>
                </Link>
                <Link href="/olts/map" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    <span>3. Peta Sebaran OLT</span>
                </Link>
                <Link href="/olts/capacity" class="px-4 py-2.5 rounded-xl text-sm font-medium flex items-center gap-2 transition-all shrink-0 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200">
                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                    <span>4. Kapasitas & Utilisasi</span>
                </Link>
            </div>

            <!-- Stats Overview Cards Multi OLT -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total OLT Terdaftar</div>
                    <div class="text-2xl font-black text-gray-900 mt-1">{{ stats?.total_olt || 0 }} Unit</div>
                    <div class="text-[11px] text-gray-400 mt-1">Multi POP / Shelter</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-sm">
                    <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        OLT Aktif / Online
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ stats?.active_olt || 0 }} Unit</div>
                    <div class="text-[11px] text-gray-400 mt-1">Beroperasi normal</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-indigo-100 shadow-sm">
                    <div class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        Total Port PON
                    </div>
                    <div class="text-2xl font-black text-indigo-700 mt-1">{{ stats?.total_pon_ports || 0 }} Port</div>
                    <div class="text-[11px] text-gray-400 mt-1">Akumulasi seluruh OLT</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-rose-100 shadow-sm">
                    <div class="text-[11px] font-bold text-rose-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        ODC Terkoneksi
                    </div>
                    <div class="text-2xl font-black text-rose-700 mt-1">{{ stats?.total_odcs || 0 }} ODC</div>
                    <div class="text-[11px] text-gray-400 mt-1">Distribusi feeder</div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-sky-100 shadow-sm col-span-2 sm:col-span-1">
                    <div class="text-[11px] font-bold text-sky-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                        Total ONT / Pelanggan
                    </div>
                    <div class="text-2xl font-black text-sky-700 mt-1">{{ stats?.total_onts || 0 }} Pelanggan</div>
                    <div class="text-[11px] text-gray-400 mt-1">Terhubung jaringan OLT</div>
                </div>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 flex-wrap">
                    <div class="relative flex-1 sm:w-64">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari nama OLT, IP, brand, model..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-sm transition-all"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterArea" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-sm bg-gray-50/50">
                        <option value="">Semua Area / Wilayah</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>

                    <select v-model="filterBrand" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-sm bg-gray-50/50">
                        <option value="">Semua Brand OLT</option>
                        <option v-for="b in brands" :key="b" :value="b">{{ b }}</option>
                    </select>

                    <select v-model="filterStatus" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 text-sm bg-gray-50/50">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Non-Aktif</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <button @click="openCreateModal" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-indigo-500/20 hover:shadow-lg hover:shadow-indigo-500/30 hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah OLT Baru</span>
                </button>
            </div>

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                                <th class="p-4">Nama OLT & Hostname</th>
                                <th class="p-4">Area & Lokasi POP</th>
                                <th class="p-4">IP Management & Brand</th>
                                <th class="p-4">Port PON & ODC</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="olt in olts.data" :key="olt.id" class="hover:bg-indigo-50/20 transition-colors group">
                                <td class="p-4">
                                    <div class="font-bold text-gray-900 flex items-center gap-2">
                                        <span>{{ olt.name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono mt-0.5">
                                        {{ olt.hostname || 'Host N/A' }}
                                    </div>
                                    <div v-if="olt.description" class="text-[11px] text-gray-400 mt-1 line-clamp-1 italic">
                                        {{ olt.description }}
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div>
                                        <span v-if="olt.area" class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-xs font-bold border border-blue-100">
                                            {{ olt.area.name }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400 italic">Belum diset area</span>
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                        <span>📍 {{ olt.location || 'Lokasi N/A' }}</span>
                                    </div>
                                    <div v-if="olt.latitude && olt.longitude" class="text-[10px] text-gray-400 font-mono mt-0.5">
                                        Tikor: {{ olt.latitude }}, {{ olt.longitude }}
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="font-mono text-sm font-semibold text-gray-900">
                                        {{ olt.ip_address || '-' }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-0.5">
                                        <strong class="text-indigo-700">{{ olt.brand || 'Brand N/A' }}</strong> 
                                        <span v-if="olt.model" class="text-gray-500 font-mono"> ({{ olt.model }})</span>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="font-bold text-gray-900 flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-black">
                                            {{ olt.total_pon_ports }} Port PON
                                        </span>
                                        <span class="text-xs text-gray-500">· {{ olt.odcs_count || 0 }} ODC</span>
                                    </div>
                                    <div v-if="getOltVlans(olt).length > 0" class="flex flex-wrap gap-1 mt-1.5">
                                        <span v-for="(v, vi) in getOltVlans(olt).slice(0, 3)" :key="vi" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold border" :class="getModeBadgeClass(v.access_mode)">
                                            <span>{{ v.vlan_id }}</span>
                                            <span class="opacity-75 text-[9px]">({{ v.access_mode }})</span>
                                        </span>
                                        <span v-if="getOltVlans(olt).length > 3" class="px-1 py-0.5 rounded text-[10px] bg-gray-100 text-gray-600 font-medium">
                                            +{{ getOltVlans(olt).length - 3 }} VLAN
                                        </span>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider" :class="olt.status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-700 border border-gray-200'">
                                        {{ olt.status === 'active' ? '● Aktif' : '○ Non-Aktif' }}
                                    </span>
                                </td>

                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/olts/${olt.id}`" class="px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-bold transition-colors">
                                            Detail OLT
                                        </Link>
                                        <button @click="openEditModal(olt)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteOlt(olt.id)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="olts.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Belum ada data OLT yang sesuai dengan pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="olts.links && olts.links.length > 3" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <div class="text-xs text-gray-500">
                        Menampilkan {{ olts.from || 0 }} - {{ olts.to || 0 }} dari {{ olts.total || 0 }} OLT
                    </div>
                    <div class="flex gap-1">
                        <template v-for="(link, idx) in olts.links" :key="idx">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="px-3 py-1 text-xs rounded-lg transition-colors border"
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200'"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-xs rounded-lg text-gray-300 border border-transparent"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah/Edit OLT -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto my-auto animate-fade-in-up">
                <div class="sticky top-0 z-10 bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Perangkat OLT' : 'Tambah OLT Baru' }}</h3>
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
                                <span class="text-sm font-semibold text-indigo-900">Spesifikasi OLT</span>
                            </div>
                            <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Area / Wilayah <span class="text-red-500">*</span></label>
                                    <select v-model="form.area_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                        <option value="" disabled>Pilih Area</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <p v-if="form.errors.area_id" class="text-red-500 text-xs mt-1">{{ form.errors.area_id }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama OLT <span class="text-red-500">*</span></label>
                                    <input v-model="form.name" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: OLT-ZTE-PUSAT-01" required />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Hostname (Opsional)</label>
                                    <input v-model="form.hostname" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: olt1.isp.net" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">IP Management Address</label>
                                    <input v-model="form.ip_address" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="192.168.10.1" />
                                    <p v-if="form.errors.ip_address" class="text-red-500 text-xs mt-1">{{ form.errors.ip_address }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Jumlah Port PON <span class="text-red-500">*</span></label>
                                    <input v-model="form.total_pon_ports" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" required />
                                    <p v-if="form.errors.total_pon_ports" class="text-red-500 text-xs mt-1">{{ form.errors.total_pon_ports }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Kapasitas (ONT per PON)</label>
                                    <input v-model="form.pon_capacity" type="number" min="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Default: 64" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Brand / Merk</label>
                                    <input v-model="form.brand" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: ZTE / Huawei / VSOL" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Model</label>
                                    <input v-model="form.model" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="contoh: C320 / MA5608T" />
                                </div>
                            </div>
                        </div>

                        <!-- Lokasi Penempatan -->
                        <div class="border border-green-100 rounded-xl overflow-hidden bg-white">
                            <div class="bg-green-50/50 px-4 py-3 border-b border-green-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span class="text-sm font-semibold text-green-900">Lokasi Penempatan & Tikor</span>
                            </div>
                            <div class="p-4 space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Alamat / Shelter POP</label>
                                    <input v-model="form.location" type="text" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="contoh: Shelter POP Utama Server Lt 2" />
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Latitude</label>
                                        <input v-model="form.latitude" type="number" step="any" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="-6.2088" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Longitude</label>
                                        <input v-model="form.longitude" type="number" step="any" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="106.8456" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Keterangan -->
                        <div class="border border-gray-200 rounded-xl p-4 bg-white space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Status Operasional</label>
                                <select v-model="form.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="active">Aktif (Online)</option>
                                    <option value="inactive">Non-Aktif (Offline)</option>
                                    <option value="maintenance">Maintenance</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Keterangan / Catatan</label>
                                <textarea v-model="form.description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Catatan tambahan mengenai OLT ini..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
                        <button type="button" @click="closeModal" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">Batal</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 shadow-md shadow-indigo-500/20 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Data OLT' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    olts: Object,
    areas: Array,
    brands: Array,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterArea = ref(props.filters?.area_id || '');
const filterBrand = ref(props.filters?.brand || '');
const filterStatus = ref(props.filters?.status || '');

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    area_id: '',
    name: '',
    hostname: '',
    ip_address: '',
    brand: '',
    model: '',
    total_pon_ports: 8,
    pon_capacity: 64,
    location: '',
    latitude: '',
    longitude: '',
    status: 'active',
    description: '',
    pon_vlans: [],
});

const performSearch = () => {
    router.get('/olts', {
        search: search.value,
        area_id: filterArea.value,
        brand: filterBrand.value,
        status: filterStatus.value,
    }, { preserveState: true, replace: true });
};

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    form.area_id = '';
    form.total_pon_ports = 8;
    form.pon_capacity = 64;
    form.status = 'active';
    isModalOpen.value = true;
}

function openEditModal(olt) {
    isEditing.value = true;
    editId.value = olt.id;
    form.area_id = olt.area_id || '';
    form.name = olt.name;
    form.hostname = olt.hostname || '';
    form.ip_address = olt.ip_address || '';
    form.brand = olt.brand || '';
    form.model = olt.model || '';
    form.total_pon_ports = olt.total_pon_ports;
    form.pon_capacity = olt.pon_capacity || 64;
    form.location = olt.location || '';
    form.latitude = olt.latitude || '';
    form.longitude = olt.longitude || '';
    form.status = olt.status;
    form.description = olt.description || '';
    form.pon_vlans = olt.pon_vlans || [];
    form.clearErrors();
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => form.reset(), 300);
}

function submit() {
    if (isEditing.value) {
        form.post(`/olts/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/olts', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteOlt(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data OLT ini? Seluruh data port & ODC terkait akan terpengaruh.')) {
        router.post(`/olts/${id}/delete`);
    }
}

function getModeBadgeClass(mode) {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE': return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'HOTSPOT': return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'STATIC': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        default: return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}

function getOltVlans(olt) {
    if (!olt || !olt.pon_vlans) return [];
    let vlans = olt.pon_vlans;
    if (typeof vlans === 'string') {
        try { vlans = JSON.parse(vlans); } catch (e) { return []; }
    }
    if (!Array.isArray(vlans)) return [];
    const list = [];
    vlans.forEach(p => {
        (p.vlans || []).forEach(v => {
            if (v && v.vlan_id) {
                list.push({
                    port: p.port,
                    name: v.name,
                    vlan_id: v.vlan_id,
                    access_mode: v.access_mode || 'PPPOE'
                });
            }
        });
    });
    return list;
}
</script>
