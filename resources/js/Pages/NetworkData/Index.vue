<template>
    <AppLayout title="Data Jaringan">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Data Jaringan</h2>
                    <p class="text-sm text-gray-500 mt-1 flex items-center gap-2">
                        <span class="text-indigo-600 font-medium">Home</span> 
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span>Data Jaringan</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button @click="refreshData" class="px-4 py-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg text-sm font-medium text-gray-700 shadow-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" :class="{'animate-spin': isRefreshing}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh Data
                    </button>
                    <!-- Mode Tampilan -->
                    <div class="bg-gray-100/80 p-1 rounded-lg flex items-center">
                        <button @click="viewMode = 'card'" :class="viewMode === 'card' ? 'bg-white text-indigo-600 shadow-sm font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Card View</button>
                        <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white text-indigo-600 shadow-sm font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Table View</button>
                        <button @click="viewMode = 'topology'" :class="viewMode === 'topology' ? 'bg-white text-indigo-600 shadow-sm font-semibold' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 rounded-md text-xs transition-all">Topology</button>
                    </div>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden group hover:border-indigo-100 transition-colors">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50/50 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total ODC</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ formatNumber(stats.total_odc) }}</h3>
                    <p class="text-xs text-blue-600 mt-2 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Total ODC Master
                    </p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden group hover:border-indigo-100 transition-colors">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-indigo-50/50 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total ODP</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ formatNumber(stats.total_odp) }}</h3>
                    <p class="text-xs text-indigo-600 mt-2 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        Total ODP Aktif
                    </p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden group hover:border-indigo-100 transition-colors">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-50/50 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Port Tersedia</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ formatNumber(stats.port_tersedia) }}</h3>
                    <p class="text-xs text-emerald-600 mt-2 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Dari {{ formatNumber(stats.total_port) }} Port
                    </p>
                </div>
                <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden group hover:border-indigo-100 transition-colors">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-50/50 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Pelanggan Aktif</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ formatNumber(stats.pelanggan_aktif) }}</h3>
                    <p class="text-xs text-amber-600 mt-2 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Online Users
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
                <div class="flex flex-col md:flex-row gap-4 items-center">
                    <!-- Global Search -->
                    <div class="relative w-full md:w-64 shrink-0">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input v-model="form.search" @input="debouncedSearch" type="text" class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400" placeholder="Cari ODC, ODP, Pelanggan..." />
                    </div>
                    
                    <div class="w-full h-px md:h-8 md:w-px bg-gray-200 hidden md:block"></div>

                    <!-- Filter Selects -->
                    <div class="flex-1 grid grid-cols-2 md:grid-cols-5 gap-3 w-full">
                        <select v-model="form.olt_id" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua Server</option>
                            <option v-for="olt in olts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                        </select>
                        <select v-model="form.odc_id" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua ODC</option>
                            <option v-for="odc in filteredOdcs" :key="odc.id" :value="odc.id">{{ odc.name }}</option>
                        </select>
                        <select v-model="form.odp_id" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua ODP</option>
                            <option v-for="odp in filteredOdpsDropdown" :key="odp.id" :value="odp.id">{{ odp.name }}</option>
                        </select>
                        <select v-model="form.area_id" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                        <select v-model="form.status" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua Status</option>
                            <option value="active">Active (Tersedia)</option>
                            <option value="full">Full (Penuh)</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    
                    <button @click="resetFilters" class="px-3 py-2 text-sm text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100 shrink-0">
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Stop Permanen Section (Compact) -->
            <div v-if="terminated_customers.length > 0" class="bg-red-50/50 border border-red-100 rounded-xl p-4 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-red-900">Stop Permanen</h4>
                        <p class="text-xs text-red-700">Total: {{ terminated_customers.length }} pelanggan yang telah berhenti (Port bisa dicabut)</p>
                    </div>
                </div>
                <button @click="showTerminated = true" class="px-4 py-2 bg-white text-red-600 hover:bg-red-600 hover:text-white border border-red-200 rounded-lg text-xs font-bold transition-colors">
                    Lihat Detail
                </button>
            </div>

            <!-- CARD VIEW -->
            <div v-if="viewMode === 'card'" class="space-y-8">
                <!-- Group by ODC -->
                <div v-for="odcGroup in odpsGroupedByOdc" :key="odcGroup.odc.id" class="animate-fade-in-up">
                    <div class="bg-gray-800 text-white p-4 rounded-t-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 bg-blue-500/20 text-blue-300 rounded text-[10px] font-bold uppercase border border-blue-400/20">SERVER: {{ odcGroup.odc.olt?.name || 'Unknown' }}</span>
                                <h3 class="text-lg font-black tracking-tight">{{ odcGroup.odc.name }}</h3>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-300">
                                <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> {{ odcGroup.odc.address || 'Tanpa Alamat' }}</span>
                                <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> {{ odcGroup.odc.pon_port || 'PON ?' }}</span>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="text-center bg-gray-900/50 p-2 rounded-lg border border-gray-700/50 min-w-[70px]">
                                <span class="block text-xl font-bold text-white">{{ odcGroup.odps.length }}</span>
                                <span class="block text-[9px] text-gray-400 uppercase tracking-widest">ODP</span>
                            </div>
                            <div class="text-center bg-gray-900/50 p-2 rounded-lg border border-gray-700/50 min-w-[70px]">
                                <span class="block text-xl font-bold text-emerald-400">{{ getOdcTotalPorts(odcGroup.odps).available }}</span>
                                <span class="block text-[9px] text-gray-400 uppercase tracking-widest">Free Port</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white border border-gray-200 border-t-0 p-4 rounded-b-xl shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                            <!-- ODP Card -->
                            <div v-for="odp in odcGroup.odps" :key="odp.id" class="border border-gray-200 rounded-xl hover:border-indigo-300 hover:shadow-md transition-all group bg-white flex flex-col relative overflow-hidden">
                                <!-- Status Strip -->
                                <div class="absolute top-0 left-0 w-full h-1" :class="getOdpStatusColor(odp)"></div>
                                
                                <div class="p-4 flex-1">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-bold text-gray-800 text-lg leading-tight">{{ odp.name }}</h4>
                                            <p class="text-[11px] font-medium text-gray-500 mt-0.5">{{ odp.kode_odp || '-' }}</p>
                                        </div>
                                        <div class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider" :class="getOdpStatusBadge(odp)">
                                            {{ odp.status }}
                                        </div>
                                    </div>

                                    <!-- Address & Coord Pills -->
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        <div class="bg-gray-50 border border-gray-200 text-gray-600 text-[10px] px-2 py-1 rounded-md flex items-center gap-1" :title="odp.address">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="truncate max-w-[150px]">{{ odp.address || '-' }}</span>
                                        </div>
                                        <div class="bg-gray-50 border border-gray-200 text-gray-600 text-[10px] px-2 py-1 rounded-md flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            {{ odp.latitude || '-' }}, {{ odp.longitude || '-' }}
                                        </div>
                                        <div class="bg-gray-50 border border-gray-200 text-gray-800 font-bold text-[10px] px-2 py-1 rounded-md flex items-center gap-1">
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ odp.total_ports }} Port
                                        </div>
                                    </div>

                                    <!-- Ports List (Directly in Card) -->
                                    <div class="space-y-1.5 mb-4 max-h-48 overflow-y-auto pr-1 text-xs">
                                        <div v-for="portNum in odp.total_ports" :key="portNum" class="flex items-center gap-2">
                                            <span class="text-gray-400 font-mono w-4 text-right">{{ portNum }}</span>
                                            <template v-if="getPortData(odp, portNum)">
                                                <div class="w-2 h-2 rounded-full shrink-0" :class="getCustomerStatusColor(getPortData(odp, portNum).customer?.status)"></div>
                                                <span v-if="getPortData(odp, portNum).customer" class="font-medium text-gray-700 truncate">
                                                    {{ getPortData(odp, portNum).customer.customer_code }} - {{ getPortData(odp, portNum).customer.name }}
                                                </span>
                                                <span v-else class="font-medium text-gray-400 italic">ONT Inventori</span>
                                            </template>
                                            <template v-else>
                                                <div class="w-2 h-2 rounded-full shrink-0 bg-red-400"></div>
                                            </template>
                                        </div>
                                    </div>

                                    <button @click="openOdpDetail(odp)" class="w-full text-center bg-indigo-50 text-indigo-600 hover:bg-indigo-100 text-xs font-bold py-2 rounded-lg transition-colors flex items-center justify-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Lihat Foto & Detail
                                    </button>

                                    <!-- Stop Permanen List (Specific to this ODP) -->
                                    <div class="mt-4 pt-4 border-t border-gray-100">
                                        <h5 class="text-[11px] font-bold text-gray-800 mb-2">Stop Permanen</h5>
                                        <div class="space-y-1">
                                            <template v-for="cust in getTerminatedCustomersForOdp(odp.id)" :key="cust.id">
                                                <div class="flex items-start gap-1.5 text-[10px]">
                                                    <div class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0 mt-1"></div>
                                                    <span class="text-gray-600">
                                                        <span class="font-medium text-gray-800">{{ cust.customer_code }}</span> - {{ cust.name }} <span class="text-gray-400">[{{ cust.updated_at ? cust.updated_at.substring(0,10) : '' }}]</span>
                                                    </span>
                                                </div>
                                            </template>
                                            <div v-if="getTerminatedCustomersForOdp(odp.id).length === 0" class="text-[10px] text-gray-400 italic">
                                                -
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="odps.length === 0" class="text-center py-12 bg-white rounded-xl border border-gray-200">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-gray-500 font-medium">Tidak ada data jaringan yang cocok dengan filter.</p>
                </div>
            </div>

            <!-- TABLE VIEW -->
            <div v-if="viewMode === 'table'" class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden animate-fade-in-up">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">ODP / Kode</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">ODC / Server</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Kapasitas</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Terpakai</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Tersedia</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="odp in odps" :key="odp.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="font-bold text-gray-900 text-sm">{{ odp.name }}</div>
                                    <div class="text-[11px] text-gray-500">{{ odp.kode_odp || '-' }}</div>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ odp.odc?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ odp.odc?.olt?.name || '-' }}</div>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">{{ odp.total_ports }} Port</td>
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ odp.used_ports }}</td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded text-xs font-bold" :class="Math.max(0, odp.total_ports - odp.used_ports) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                                        {{ Math.max(0, odp.total_ports - odp.used_ports) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider" :class="getOdpStatusBadge(odp)">
                                        {{ odp.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openOdpDetail(odp)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded transition-colors text-xs font-bold">Detail</button>
                                </td>
                            </tr>
                            <tr v-if="odps.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOPOLOGY VIEW -->
            <div v-if="viewMode === 'topology'" class="bg-gray-900 rounded-xl border border-gray-800 p-6 overflow-x-auto min-h-[600px] relative animate-fade-in-up">
                <!-- Legend -->
                <div class="absolute top-4 right-4 bg-gray-800/80 backdrop-blur border border-gray-700 p-3 rounded-lg text-[10px] text-gray-300 font-medium space-y-2 z-10">
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div> Online / Active</div>
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div> Offline / Gangguan</div>
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div> Reservasi / Proses Pasang</div>
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-gray-500"></div> Port Kosong</div>
                </div>

                <div class="font-mono text-xs text-gray-300 leading-relaxed whitespace-pre">
                    <template v-for="serverGroup in groupedForTopology" :key="serverGroup.id">
<span class="text-blue-400 font-bold text-sm">SERVER: {{ serverGroup.name }}</span>
<template v-for="odc in serverGroup.odcs" :key="odc.id">
   │
   ▼
<span class="text-indigo-400 font-bold">ODC {{ odc.name }}</span> <span class="text-gray-500">[{{ getOdcTotalPorts(odc.odps).available }} Port Free]</span>
   │
<template v-for="(odp, odpIndex) in odc.odps" :key="odp.id">
   {{ odpIndex === odc.odps.length - 1 ? '└' : '├' }}──────── <button @click="openOdpDetail(odp)" class="text-gray-200 hover:text-white font-bold bg-gray-800 hover:bg-gray-700 px-2 py-0.5 rounded transition-colors cursor-pointer">ODP {{ odp.name }}</button> <span class="text-gray-500 text-[10px]">{{ Math.max(0, odp.total_ports - odp.used_ports) }} Free / {{ odp.total_ports }} Cap</span>
<template v-for="portNum in odp.total_ports" :key="portNum">
   │              {{ odpIndex === odc.odps.length - 1 ? ' ' : '│' }}
   │              {{ odpIndex === odc.odps.length - 1 ? ' ' : '│' }}  ├── Port {{ portNum.toString().padStart(2, '0') }} <span v-html="renderPortTopology(odp, portNum)"></span>
</template>
   │
</template>
</template>
<br/><br/>
                    </template>
                </div>
            </div>

        </div>

        <!-- DETAIL ODP DRAWER -->
        <Teleport to="body">
            <div v-if="selectedDetailOdp" class="fixed inset-0 z-[100] overflow-hidden">
                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="selectedDetailOdp = null"></div>
                <div class="fixed inset-y-0 right-0 max-w-md w-full flex bg-white shadow-2xl transform transition-transform animate-slide-in-right">
                    <div class="h-full flex flex-col w-full">
                        <!-- Drawer Header -->
                        <div class="px-6 py-4 bg-gray-900 text-white flex items-center justify-between shadow-md z-10">
                            <div>
                                <h2 class="text-lg font-black tracking-tight">{{ selectedDetailOdp.name }}</h2>
                                <p class="text-xs text-gray-400 font-medium flex items-center gap-2 mt-0.5">
                                    <span class="flex items-center gap-1"><svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg> {{ selectedDetailOdp.odc?.name }}</span>
                                </p>
                            </div>
                            <button @click="selectedDetailOdp = null" class="text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 p-2 rounded-full transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="flex-1 overflow-y-auto bg-gray-50">
                            <!-- Info Cards -->
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase">Kapasitas</p>
                                        <p class="text-lg font-black text-gray-800">{{ selectedDetailOdp.total_ports }} Port</p>
                                    </div>
                                    <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase">Tersedia</p>
                                        <p class="text-lg font-black text-emerald-600">{{ Math.max(0, selectedDetailOdp.total_ports - selectedDetailOdp.used_ports) }} Port</p>
                                    </div>
                                </div>

                                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden text-sm">
                                    <div class="px-4 py-3 border-b border-gray-100 flex items-start justify-between">
                                        <div>
                                            <p class="text-xs font-bold text-gray-400 uppercase mb-1">Alamat / Lokasi</p>
                                            <p class="font-medium text-gray-800">{{ selectedDetailOdp.address || '-' }}</p>
                                        </div>
                                        <a v-if="selectedDetailOdp.latitude && selectedDetailOdp.longitude" :href="`https://maps.google.com/?q=${selectedDetailOdp.latitude},${selectedDetailOdp.longitude}`" target="_blank" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </a>
                                    </div>
                                    <div class="px-4 py-3 border-b border-gray-100 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-0.5">Area</p>
                                            <p class="font-medium text-gray-800">{{ selectedDetailOdp.area?.name || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-0.5">Kode ODP</p>
                                            <p class="font-mono text-gray-800 text-xs mt-1">{{ selectedDetailOdp.kode_odp || '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ports List -->
                                <div>
                                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider mb-3 px-1 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        Status Port
                                    </h3>
                                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">
                                        <!-- Perhitungan murni berdasarkan total_ports master data -->
                                        <div v-for="portNum in selectedDetailOdp.total_ports" :key="portNum" class="p-3 flex items-center gap-4 hover:bg-gray-50 transition-colors">
                                            <div class="w-10 text-center flex-shrink-0">
                                                <span class="text-xs font-black text-gray-400">P{{ portNum.toString().padStart(2, '0') }}</span>
                                            </div>
                                            
                                            <div class="flex-1 min-w-0">
                                                <!-- Jika port digunakan -->
                                                <template v-if="getPortData(selectedDetailOdp, portNum)">
                                                    <div v-if="getPortData(selectedDetailOdp, portNum).customer">
                                                        <div class="flex items-center gap-2 mb-0.5">
                                                            <div class="w-2 h-2 rounded-full shrink-0" :class="getCustomerStatusColor(getPortData(selectedDetailOdp, portNum).customer.status)"></div>
                                                            <p class="text-sm font-bold text-gray-900 truncate" :title="getPortData(selectedDetailOdp, portNum).customer.name">
                                                                {{ getPortData(selectedDetailOdp, portNum).customer.name }}
                                                            </p>
                                                        </div>
                                                        <p class="text-[11px] text-gray-500 font-mono">{{ getPortData(selectedDetailOdp, portNum).customer.customer_code || getPortData(selectedDetailOdp, portNum).serial_number }}</p>
                                                    </div>
                                                    <div v-else>
                                                        <div class="flex items-center gap-2 mb-0.5">
                                                            <div class="w-2 h-2 rounded-full shrink-0 bg-gray-400"></div>
                                                            <p class="text-sm font-bold text-gray-600 italic">ONT Inventori (Belum Assign)</p>
                                                        </div>
                                                        <p class="text-[11px] text-gray-400 font-mono">{{ getPortData(selectedDetailOdp, portNum).serial_number }}</p>
                                                    </div>
                                                </template>
                                                <!-- Jika port kosong -->
                                                <template v-else>
                                                    <div class="flex items-center gap-2 py-1">
                                                        <div class="w-2 h-2 rounded-full shrink-0 bg-gray-300"></div>
                                                        <p class="text-sm font-medium text-gray-400">Available</p>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            <!-- Aksi -->
                                            <div v-if="getPortData(selectedDetailOdp, portNum)?.customer" class="shrink-0">
                                                <a :href="`/customers/${getPortData(selectedDetailOdp, portNum).customer.id}`" target="_blank" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition-colors block">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL STOP PERMANEN -->
        <Teleport to="body">
            <div v-if="showTerminated" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showTerminated = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-red-50/30 rounded-t-2xl">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900">Daftar Pelanggan Stop Permanen</h2>
                        </div>
                        <button @click="showTerminated = false" class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-md hover:bg-red-50">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Kode / Nama</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">ODP & Port</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Tgl Pemasangan</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Catatan Stop</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="cust in terminated_customers" :key="cust.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-sm text-gray-900"><a :href="`/customers/${cust.id}`" class="hover:text-indigo-600 hover:underline">{{ cust.name }}</a></div>
                                        <div class="text-[11px] text-gray-500 font-mono">{{ cust.customer_code }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900" v-if="cust.ont">
                                            {{ cust.ont.odp?.name || '-' }}
                                        </div>
                                        <div class="text-xs text-red-600 font-bold" v-if="cust.ont">
                                            Port {{ cust.ont.port_number }} (Cabut/Kosongkan)
                                        </div>
                                        <span v-else class="text-xs text-gray-400">Tidak ada ONT</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ cust.installation_date || '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate" :title="cust.notes">{{ cust.notes || '-' }}</td>
                                </tr>
                                <tr v-if="terminated_customers.length === 0">
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada pelanggan stop permanen.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    olts: Array,
    odcs: Array,
    odps_filter: Array,
    areas: Array,
    odps: Array,
    stats: Object,
    filters: Object,
    terminated_customers: Array
});

const viewMode = ref('card');
const isRefreshing = ref(false);
const showTerminated = ref(false);
const selectedDetailOdp = ref(null);

const form = useForm({
    olt_id: props.filters.olt_id || '',
    odc_id: props.filters.odc_id || '',
    odp_id: props.filters.odp_id || '',
    area_id: props.filters.area_id || '',
    status: props.filters.status || '',
    search: props.filters.search || '',
});

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({ only: ['odps', 'stats', 'terminated_customers'], onFinish: () => isRefreshing.value = false });
};

const applyFilters = () => {
    form.get(route('network-data.index'), { preserveState: true, preserveScroll: true });
};

let debounceTimeout = null;
const debouncedSearch = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
};

const resetFilters = () => {
    form.olt_id = '';
    form.odc_id = '';
    form.odp_id = '';
    form.area_id = '';
    form.status = '';
    form.search = '';
    applyFilters();
};

const filteredOdcs = computed(() => {
    if (!form.olt_id) return props.odcs;
    return props.odcs.filter(o => o.olt_id == form.olt_id);
});

const filteredOdpsDropdown = computed(() => {
    let result = props.odps_filter;
    if (form.odc_id) result = result.filter(o => o.odc_id == form.odc_id);
    else if (form.olt_id) {
        const odcIds = props.odcs.filter(o => o.olt_id == form.olt_id).map(o => o.id);
        result = result.filter(o => odcIds.includes(o.odc_id));
    }
    return result;
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// Grouping for Card View
const odpsGroupedByOdc = computed(() => {
    const groups = {};
    props.odps.forEach(odp => {
        const odcId = odp.odc_id;
        if (!groups[odcId]) {
            groups[odcId] = {
                odc: odp.odc || { id: 'null', name: 'Tanpa ODC' },
                odps: []
            };
        }
        groups[odcId].odps.push(odp);
    });
    return Object.values(groups).sort((a, b) => a.odc.name.localeCompare(b.odc.name));
});

// Grouping for Topology
const groupedForTopology = computed(() => {
    const servers = {};
    props.odps.forEach(odp => {
        const serverName = odp.odc?.olt?.name || 'Tanpa Server';
        if (!servers[serverName]) servers[serverName] = { id: serverName, name: serverName, odcs: {} };
        
        const odcName = odp.odc?.name || 'Tanpa ODC';
        if (!servers[serverName].odcs[odcName]) servers[serverName].odcs[odcName] = { id: odcName, name: odcName, odps: [] };
        
        servers[serverName].odcs[odcName].odps.push(odp);
    });
    
    // Convert to arrays
    return Object.values(servers).map(s => ({
        ...s,
        odcs: Object.values(s.odcs).sort((a, b) => a.name.localeCompare(b.name))
    })).sort((a, b) => a.name.localeCompare(b.name));
});

const getOdcTotalPorts = (odpsArray) => {
    let available = 0;
    odpsArray.forEach(o => { available += Math.max(0, o.total_ports - o.used_ports); });
    return { available };
};

const getOdpStatusColor = (odp) => {
    if (odp.status === 'full') return 'bg-red-500';
    if (odp.status === 'maintenance') return 'bg-amber-500';
    return 'bg-emerald-500'; // active
};

const getOdpStatusBadge = (odp) => {
    if (odp.status === 'full') return 'bg-red-100 text-red-700';
    if (odp.status === 'maintenance') return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700'; // active
};

const getOdpCustomerStats = (odp) => {
    let active = 0;
    let offline = 0;
    if (odp.onts) {
        odp.onts.forEach(ont => {
            if (ont.customer) {
                if (ont.customer.status === 'active') active++;
                else offline++;
            }
        });
    }
    return { active, offline };
};

const getTerminatedCustomersForOdp = (odpId) => {
    return props.terminated_customers.filter(cust => cust.ont && cust.ont.odp_id == odpId);
};

const openOdpDetail = (odp) => {
    selectedDetailOdp.value = odp;
};

const getPortData = (odp, portNum) => {
    if (!odp || !odp.onts) return null;
    return odp.onts.find(o => o.port_number == portNum);
};

const getCustomerStatusColor = (status) => {
    if (status === 'active') return 'bg-emerald-500';
    if (status === 'suspended' || status === 'terminated') return 'bg-red-500';
    if (status === 'installing' || status === 'booking' || status === 'survey') return 'bg-amber-500';
    return 'bg-gray-400';
};

const renderPortTopology = (odp, portNum) => {
    const data = getPortData(odp, portNum);
    if (!data) return `<span class="text-emerald-400 font-bold">➔ Available</span>`;
    
    if (data.customer) {
        const color = data.customer.status === 'active' ? 'text-indigo-300' : 'text-amber-400';
        return `<span class="${color}">➔ ${data.customer.customer_code} - ${data.customer.name}</span>`;
    }
    return `<span class="text-gray-400">➔ Blocked / Inventory</span>`;
};
</script>

<style>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out;
}
.animate-slide-in-right {
    animation: slideInRight 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes slideInRight {
    from { transform: translateX(100%); }
    to { transform: translateX(0); }
}
</style>
