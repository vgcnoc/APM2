<template>
    <AppLayout title="Aktivasi Pelanggan" :subtitle="currentTab === 'riwayat' ? 'Riwayat pelanggan yang telah berhasil diaktivasi' : 'Pelanggan yang menunggu proses aktivasi layanan'">
        
        <!-- Statistik Cards -->
        <div v-can="'customers_activation_card_stats'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <StatCard 
                title="Menunggu Aktivasi" 
                :value="stats?.aktivasi || 0" 
                icon="M13 10V3L4 14h7v7l9-11h-7z" 
                color="purple" 
                class="cursor-pointer transition-all hover:scale-[1.01]"
                @click="switchTab('menunggu')"
            />
            <StatCard 
                title="Riwayat Aktivasi" 
                :value="stats?.riwayat || 0" 
                icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" 
                color="emerald" 
                class="cursor-pointer transition-all hover:scale-[1.01]"
                @click="switchTab('riwayat')"
            />
        </div>

        <!-- Tabs Status Filter -->
        <div class="flex space-x-1.5 bg-slate-100/80 p-1.5 rounded-xl mb-6 shadow-inner border border-slate-200/60 overflow-x-auto">
            <button 
                type="button"
                @click="switchTab('menunggu')" 
                :class="[
                    'px-5 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 whitespace-nowrap flex-1 flex items-center justify-center gap-2 cursor-pointer', 
                    currentTab === 'menunggu' 
                        ? 'bg-white text-purple-600 shadow-sm ring-1 ring-gray-900/5' 
                        : 'text-gray-500 hover:text-gray-700 hover:bg-white/50'
                ]"
            >
                <span class="w-2 h-2 rounded-full" :class="currentTab === 'menunggu' ? 'bg-purple-500 animate-pulse' : 'bg-gray-400'"></span>
                <span>Menunggu Aktivasi</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs font-semibold', currentTab === 'menunggu' ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-600']">
                    {{ stats?.aktivasi || 0 }}
                </span>
            </button>

            <button 
                type="button"
                @click="switchTab('riwayat')" 
                :class="[
                    'px-5 py-2.5 rounded-lg text-sm font-bold transition-all duration-200 whitespace-nowrap flex-1 flex items-center justify-center gap-2 cursor-pointer', 
                    currentTab === 'riwayat' 
                        ? 'bg-white text-emerald-600 shadow-sm ring-1 ring-gray-900/5' 
                        : 'text-gray-500 hover:text-gray-700 hover:bg-white/50'
                ]"
            >
                <span class="w-2 h-2 rounded-full" :class="currentTab === 'riwayat' ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                <span>Riwayat Aktivasi</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs font-semibold', currentTab === 'riwayat' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600']">
                    {{ stats?.riwayat || 0 }}
                </span>
            </button>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, ID, PPPoE..."
            searchRoute="/customers/activation"
            selectable
            v-model:selected="selectedIds"
        >
            <template #filters>
                <div class="flex flex-wrap items-center gap-2.5 w-full">
                    <select v-model="filterArea" @change="applyFilters" class="form-select w-full sm:w-44 md:w-48 text-sm bg-white">
                        <option value="">Semua Area</option>
                        <option v-for="area in areas" :key="typeof area === 'object' && area !== null ? (area.name || area.id) : area" :value="typeof area === 'object' && area !== null ? (area.name || area.id) : area">
                            {{ typeof area === 'object' && area !== null ? area.name : area }}
                        </option>
                    </select>

                    <!-- Filter Rentang Tanggal -->
                    <div class="flex items-center gap-1.5 bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-600 shadow-2xs">
                        <span class="text-gray-400 font-medium">{{ currentTab === 'riwayat' ? 'Tgl Aktif:' : 'Tgl Daftar:' }}</span>
                        <input v-model="filterDateFrom" @change="applyFilters" type="date" class="border-0 p-0 text-xs text-slate-700 focus:ring-0" />
                        <span class="text-gray-300">s/d</span>
                        <input v-model="filterDateTo" @change="applyFilters" type="date" class="border-0 p-0 text-xs text-slate-700 focus:ring-0" />
                    </div>

                    <button @click="applyFilters" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs rounded-lg transition-colors shadow-2xs cursor-pointer">
                        Filter
                    </button>

                    <button v-if="filterArea || filterDateFrom || filterDateTo" @click="resetFilters" class="px-3.5 py-2 bg-gray-50 text-gray-600 hover:bg-gray-100 font-medium text-xs rounded-lg transition-colors border border-gray-200 whitespace-nowrap cursor-pointer">
                        Reset
                    </button>
                </div>
            </template>
            
            <template #actions>
                <div class="flex items-center gap-2">
                    <button v-if="canDelete && selectedIds.length > 0" 
                        v-can="'customers_delete'"
                        @click="bulkDelete" 
                        class="px-4 py-2 bg-red-50 text-red-600 hover:bg-red-100 font-medium text-sm rounded-lg transition-colors border border-red-200 flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Terpilih ({{ selectedIds.length }})
                    </button>
                </div>
            </template>
            
            <template #row="{ row }">
                <!-- ================= RIWAYAT AKTIVASI ROWS ================= -->
                <template v-if="currentTab === 'riwayat'">
                    <!-- Pelanggan -->
                    <td>
                        <div class="flex items-center gap-3">
                            <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-2xs', getAvatarColor(row.name)]">
                                {{ (row.name || 'P').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <Link :href="`/customers/${row.id}`" class="text-gray-900 font-bold hover:text-blue-600 transition-colors block text-sm">
                                    {{ row.name }}
                                </Link>
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 font-mono">
                                    <span>{{ row.customer_code }}</span>
                                    <span v-if="row.phone">&bull; {{ row.phone }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Paket & Tagihan -->
                    <td>
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span v-if="row.service_status === 'free_home'" class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[10px] font-bold whitespace-nowrap">
                                    <span>🏠</span> Free Home
                                </span>
                                <span v-else-if="row.service_status === 'gratis'" class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-bold whitespace-nowrap">
                                    <span>🎁</span> Gratis
                                </span>
                                <span v-else class="text-xs font-bold text-slate-800">{{ row.package?.name || '-' }}</span>

                                <span :class="['px-1.5 py-0.5 rounded text-[10px] font-semibold border', row.billing_type === 'pascabayar' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-blue-50 text-blue-700 border-blue-200']">
                                    {{ row.billing_type === 'pascabayar' ? 'Pascabayar' : 'Prabayar' }}
                                </span>
                            </div>
                            <p v-if="row.service_status === 'berbayar' && (row.base_amount || row.package?.price)" class="text-[11px] text-slate-500 font-medium">
                                {{ formatRupiah(row.base_amount || row.package?.price) }} / bln
                            </p>
                        </div>
                    </td>

                    <!-- Tanggal Aktivasi -->
                    <td>
                        <div>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ formatDateIndo(row.activation_date) }}</span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Terdaftar: {{ formatDateShort(row.registration_date || row.created_at) }}
                            </p>
                        </div>
                    </td>

                    <!-- Akun / Mode Akses -->
                    <td>
                        <div class="space-y-1">
                            <div v-if="row.ont?.pppoe_user" class="flex items-center gap-1">
                                <span class="text-[10px] text-slate-400">PPPoE:</span>
                                <span class="font-mono font-bold text-xs text-indigo-700 bg-indigo-50/80 px-1.5 py-0.5 rounded border border-indigo-100">
                                    {{ row.ont.pppoe_user }}
                                </span>
                            </div>
                            <div v-else-if="row.ont?.hotspot_user" class="flex items-center gap-1">
                                <span class="text-[10px] text-purple-600 font-semibold">Member:</span>
                                <span class="font-mono font-bold text-xs text-purple-700 bg-purple-50/80 px-1.5 py-0.5 rounded border border-purple-100">
                                    {{ row.ont.hotspot_user }}
                                </span>
                            </div>
                            <div v-else class="text-xs text-slate-400 italic">
                                Belum diset
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-slate-500">
                                <span>{{ row.ont?.access_mode || 'PPPoE' }}</span>
                                <span v-if="row.ont?.vlan_id">&bull; VLAN {{ row.ont.vlan_id }}</span>
                                <span v-if="row.ont?.free_hotspot" class="text-orange-600 font-bold bg-orange-50 px-1 rounded">&bull; HS</span>
                            </div>
                        </div>
                    </td>

                    <!-- Status -->
                    <td>
                        <StatusBadge :status="row.status" />
                    </td>

                    <!-- Topologi -->
                    <td>
                        <template v-if="row.ont?.odp">
                            <div class="text-xs space-y-0.5">
                                <p v-if="row.ont.odp.odc?.olt" class="text-gray-500 text-[11px]">{{ row.ont.odp.odc.olt.name }}</p>
                                <p class="text-gray-700 font-medium text-xs">
                                    <span v-if="row.ont.odp.odc">{{ row.ont.odp.odc.name }} &rarr; </span>
                                    <span>{{ row.ont.odp.name }}</span>
                                    <span v-if="row.ont.port_number" class="text-indigo-600 font-bold ml-1">(Port {{ row.ont.port_number }})</span>
                                </p>
                            </div>
                        </template>
                        <span v-else class="text-xs text-gray-400">-</span>
                    </td>
                </template>

                <!-- ================= MENUNGGU AKTIVASI ROWS ================= -->
                <template v-else>
                    <td>
                        <div class="flex items-center gap-3">
                            <div :class="['w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-2xs', getAvatarColor(row.name)]">
                                {{ (row.name || 'P').charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <Link :href="`/customers/${row.id}`" class="text-gray-900 font-medium hover:text-blue-400 transition-colors block">
                                    {{ row.name }}
                                </Link>
                                <p class="text-xs text-gray-500 font-mono">{{ row.customer_code }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-xs text-cyan-600 font-medium">{{ row.package?.name || '-' }}</span>
                    </td>
                    <td><StatusBadge :status="row.status" /></td>
                    <td>
                        <span v-if="row.ont" class="text-xs font-mono text-gray-700 font-semibold">{{ row.ont.serial_number }}</span>
                        <span v-else class="text-xs text-gray-400">-</span>
                    </td>
                    <td>
                        <template v-if="row.ont?.odp">
                            <div class="text-xs space-y-0.5">
                                <p v-if="row.ont.odp.odc?.olt" class="text-gray-500">{{ row.ont.odp.odc.olt.name }}</p>
                                <p class="text-gray-500">
                                    <span v-if="row.ont.odp.odc">→ {{ row.ont.odp.odc.name }}</span>
                                    → {{ row.ont.odp.name }}
                                </p>
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
            </template>

            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                    <!-- Tab Menunggu: Tombol Aktivasi -->
                    <template v-if="currentTab === 'menunggu'">
                        <button v-if="row.status === 'installing' && row.ont && row.is_audited" 
                            v-can="'customers_activation_activate'" 
                            @click="openActivationModal(row)" 
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg text-xs font-bold transition-all shadow-sm cursor-pointer" 
                            title="Proses Aktivasi Pelanggan">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            <span>Aktivasi</span>
                        </button>

                        <Link :href="`/customers/${row.id}`" class="p-1.5 rounded-lg text-gray-400 hover:bg-white hover:text-blue-500 border border-transparent hover:border-gray-200 transition-all shadow-sm hover:shadow" title="Detail Lengkap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>

                        <button v-if="canDelete" 
                            type="button"
                            @click="confirmDelete(row)" 
                            class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-700 border border-transparent hover:border-red-200 transition-all cursor-pointer" 
                            title="Hapus Pelanggan">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </template>

                    <!-- Tab Riwayat: Tombol Edit Data Aktivasi, Profil, dan Hapus -->
                    <template v-else>
                        <button 
                            type="button"
                            @click="openEditActivationModal(row)"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold transition-all shadow-2xs hover:shadow-xs cursor-pointer"
                            title="Edit Data & Konfigurasi Aktivasi"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Konfigurasi</span>
                        </button>

                        <Link 
                            :href="`/customers/${row.id}`" 
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-semibold transition-all shadow-2xs hover:shadow-xs cursor-pointer"
                            title="Buka Profil Pelanggan"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Profil</span>
                        </Link>

                        <button v-if="canDelete" 
                            type="button"
                            @click="confirmDelete(row)" 
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg text-xs font-semibold transition-all shadow-2xs hover:shadow-xs cursor-pointer" 
                            title="Hapus Pelanggan">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus</span>
                        </button>
                    </template>
                </div>
            </template>
        </DataTable>

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

                        <!-- Status Layanan Pelanggan -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Status Layanan Pelanggan <span class="text-red-500">*</span>
                                </span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', activationForm.service_status === 'berbayar' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', activationForm.service_status === 'berbayar' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                            <div v-if="activationForm.service_status === 'berbayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">Berbayar</span>
                                            <span class="text-[10px] text-gray-500">Tarif bulanan</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="berbayar" v-model="activationForm.service_status" class="sr-only">
                                </label>
                                
                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', activationForm.service_status === 'gratis' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', activationForm.service_status === 'gratis' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                            <div v-if="activationForm.service_status === 'gratis'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">Gratis (Rp 0)</span>
                                            <span class="text-[10px] text-gray-500">Non-invoice</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="gratis" v-model="activationForm.service_status" class="sr-only">
                                </label>

                                <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', activationForm.service_status === 'free_home' ? 'border-amber-600 bg-amber-50/60 ring-1 ring-amber-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', activationForm.service_status === 'free_home' ? 'border-amber-600 bg-amber-600' : 'border-gray-300']">
                                            <div v-if="activationForm.service_status === 'free_home'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-bold text-gray-900 block">Free Home</span>
                                            <span class="text-[10px] text-amber-700 font-semibold">Voucher ONT</span>
                                        </div>
                                    </div>
                                    <input type="radio" value="free_home" v-model="activationForm.service_status" class="sr-only">
                                </label>
                            </div>
                        </div>

                        <!-- Siklus Penagihan Invoice (Ditiadakan jika Free Home) -->
                        <div v-if="activationForm.service_status !== 'free_home'" class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Siklus Penagihan Invoice <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-purple-700 bg-purple-100/70 px-2 py-0.5 rounded-md font-semibold">
                                    Default: {{ formatBillingName(defaultBillingType) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label :class="['relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all', activationForm.billing_type === 'prabayar' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <input type="radio" value="prabayar" v-model="activationForm.billing_type" class="sr-only">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold" :class="activationForm.billing_type === 'prabayar' ? 'text-purple-900' : 'text-gray-900'">Prabayar</span>
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center', activationForm.billing_type === 'prabayar' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                            <div v-if="activationForm.billing_type === 'prabayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-gray-500">Bayar dulu baru pakai (Full 1 bulan di muka)</span>
                                </label>
                                
                                <label :class="['relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all', activationForm.billing_type === 'pascabayar' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                    <input type="radio" value="pascabayar" v-model="activationForm.billing_type" class="sr-only">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold" :class="activationForm.billing_type === 'pascabayar' ? 'text-purple-900' : 'text-gray-900'">Pasca Bayar</span>
                                        <div :class="['w-4 h-4 rounded-full border flex items-center justify-center', activationForm.billing_type === 'pascabayar' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                            <div v-if="activationForm.billing_type === 'pascabayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-gray-500">Pakai dulu baru bayar (Postpaid)</span>
                                </label>
                            </div>

                            <!-- Pilihan Siklus Tanggal Tagihan (Billing Cycle) -->
                            <div class="space-y-1.5 pt-1">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Siklus Tanggal Tagihan
                                    </label>
                                    <span class="text-[10px] text-purple-700 bg-purple-100/70 px-2 py-0.5 rounded-md font-semibold">
                                        Default: {{ defaultBillingCycle === 'anniversary' ? 'Sesuai Tgl Aktivasi' : 'Kalender Serentak' }}
                                    </span>
                                </div>
                                <select v-model="activationForm.billing_cycle" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs text-slate-700 focus:ring-2 focus:ring-purple-500 shadow-sm">
                                    <option value="default">Ikuti Default Sistem ({{ defaultBillingCycle === 'anniversary' ? 'Sesuai Tgl Aktivasi' : 'Kalender Serentak' }})</option>
                                    <option value="anniversary">Sesuai Tanggal Aktivasi (Anniversary)</option>
                                    <option value="calendar">Siklus Kalender (Tanggal Serentak)</option>
                                    <option value="custom_date">Pilih Tanggal Khusus Sendiri</option>
                                </select>
                                <div v-if="activationForm.billing_cycle === 'custom_date'" class="flex items-center gap-2 mt-2 p-2.5 bg-purple-50/50 border border-purple-200 rounded-xl">
                                    <span class="text-xs text-purple-900 font-medium">Tanggal Tagihan Rutin:</span>
                                    <input type="number" v-model="activationForm.billing_day" min="1" max="28" class="w-20 px-2.5 py-1 text-xs border border-purple-300 rounded-lg focus:ring-purple-500 font-bold text-purple-800 bg-white" placeholder="1-28" />
                                    <span class="text-xs text-purple-700">tiap bulan</span>
                                </div>
                            </div>

                            <!-- Pilihan Prorata saat Pascabayar -->
                            <div v-if="activationForm.billing_type === 'pascabayar'" class="p-3.5 bg-purple-50/70 border border-purple-200 rounded-xl space-y-2 animate-fade-in">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center gap-2.5 cursor-pointer">
                                        <input type="checkbox" v-model="activationForm.is_prorata" class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                        <span class="text-xs font-bold text-purple-950">
                                            Hitung Tagihan Pertama secara Prorata
                                        </span>
                                    </label>
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-purple-200/80 text-purple-800 rounded-full">
                                        Hanya 1 Kali
                                    </span>
                                </div>
                                <p class="text-[11px] text-purple-700 leading-relaxed pl-6.5">
                                    Tagihan pertama dihitung proporsional sisa hari aktif hingga tanggal cut-off. 
                                    <strong>Setelah bulan depan, tagihan otomatis normal pascabayar (1 bulan penuh).</strong>
                                </p>
                            </div>
                        </div>

                        <!-- Banner Khusus Free Home Tanpa Tagihan & Siklus Invoice -->
                        <div v-if="activationForm.service_status === 'free_home'" class="p-4 bg-gradient-to-r from-amber-50 via-orange-50 to-yellow-50 border border-amber-200 rounded-xl space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🏠</span>
                                <div>
                                    <h4 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Aktivasi Mode Free Home (Voucher Rumahan ONT)</h4>
                                    <p class="text-[11px] text-amber-800">Pelanggan tidak dikenakan tagihan aktivasi dan tidak membentuk invoice.</p>
                                </div>
                            </div>
                            <p class="text-[11px] text-amber-900 leading-relaxed">
                                Pelanggan menggunakan ONT di rumah khusus untuk koneksi voucher hotspot. Sistem tidak akan membuat invoice tagihan atau piutang bulanan.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Aktivasi</label>
                            <input v-model="activationForm.activation_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" required />
                        </div>

                        <!-- Info Khusus Migrasi -->
                        <div v-if="customerIncentivesPreview.isMigrated" class="p-3 bg-amber-50/80 rounded-xl border border-amber-200/80 text-amber-900 text-xs flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">ℹ️</span>
                                <div>
                                    <span class="font-bold">Pelanggan Hasil Migrasi:</span>
                                    <span class="text-amber-800 ml-1">Insentif internal teknisi & sales dinonaktifkan (hanya fee market affiliate luar yang berlaku).</span>
                                </div>
                            </div>
                            <span v-if="customerIncentivesPreview.hasAny" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-900">Affiliate Luar Aktif</span>
                        </div>

                        <!-- Rincian Insentif Otomatis (Booking, Survey, Pasang) -->
                        <div v-if="customerIncentivesPreview.hasAny" class="bg-gradient-to-br from-emerald-50/90 via-teal-50/50 to-slate-50 p-4 rounded-xl border border-emerald-200 shadow-xs space-y-3">
                            <div class="flex items-center justify-between border-b border-emerald-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs font-bold">
                                        ⚡
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-emerald-950 uppercase tracking-wider">Rincian Insentif Terbentuk</h4>
                                        <p class="text-[10px] text-emerald-700">Fee Booking, Survey, & Pasang otomatis masuk Insentif</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[11px] font-extrabold px-2.5 py-1 rounded-lg bg-emerald-600 text-white shadow-2xs">
                                        Total: Rp {{ formatRupiah(customerIncentivesPreview.totalAmount) }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-2 text-xs">
                                <!-- 1. Fee Booking (Sales) -->
                                <div class="p-2 rounded-lg bg-white/80 border border-emerald-100 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-emerald-900 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            Fee Booking (Sales / Referral)
                                        </span>
                                        <span v-if="customerIncentivesPreview.booking" class="font-mono font-bold text-emerald-700">
                                            Rp {{ formatRupiah(customerIncentivesPreview.booking.amount) }}
                                        </span>
                                        <span v-else class="text-[10px] text-slate-400 font-semibold italic">Rp 0 (Tanpa Sales)</span>
                                    </div>
                                    <div v-if="customerIncentivesPreview.booking" class="flex items-center justify-between text-[11px] text-slate-600 pl-3">
                                        <span>{{ customerIncentivesPreview.booking.name }} <span class="text-[10px] text-slate-400">({{ customerIncentivesPreview.booking.role || 'Sales' }})</span></span>
                                        <span class="text-[10px] text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Siap Ditambahkan</span>
                                    </div>
                                </div>

                                <!-- 2. Fee Survey (Surveyor) -->
                                <div class="p-2 rounded-lg bg-white/80 border border-emerald-100 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-emerald-900 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Fee Survey
                                        </span>
                                        <span v-if="customerIncentivesPreview.surveys.length > 0" class="font-mono font-bold text-emerald-700">
                                            Rp {{ formatRupiah(customerIncentivesPreview.surveys.reduce((sum, s) => sum + s.amount, 0)) }}
                                        </span>
                                        <span v-else class="text-[10px] text-slate-400 font-semibold italic">Rp 0 (Tanpa Survey)</span>
                                    </div>
                                    <div v-if="customerIncentivesPreview.surveys.length > 0" class="space-y-1 pl-3">
                                        <div v-for="s in customerIncentivesPreview.surveys" :key="'srv-' + s.id" class="flex items-center justify-between text-[11px] text-slate-600">
                                            <span>{{ s.name }} <span class="text-[10px] text-slate-400">({{ s.role || 'Surveyor' }})</span></span>
                                            <span class="font-mono text-emerald-700 font-semibold">Rp {{ formatRupiah(s.amount) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. Fee Instalasi / Pasang (Teknisi) -->
                                <div class="p-2 rounded-lg bg-white/80 border border-emerald-100 space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[11px] font-bold text-emerald-900 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Fee Instalasi (Teknisi)
                                        </span>
                                        <span v-if="customerIncentivesPreview.installers.length > 0" class="font-mono font-bold text-emerald-700">
                                            Rp {{ formatRupiah(customerIncentivesPreview.installers.reduce((sum, i) => sum + i.amount, 0)) }}
                                        </span>
                                        <span v-else class="text-[10px] text-slate-400 font-semibold italic">Rp 0 (Belum ada Teknisi)</span>
                                    </div>
                                    <div v-if="customerIncentivesPreview.installers.length > 0" class="space-y-1 pl-3">
                                        <div v-for="t in customerIncentivesPreview.installers" :key="'inst-' + t.id" class="flex items-center justify-between text-[11px] text-slate-600">
                                            <span>{{ t.name }} <span class="text-[10px] text-slate-400">({{ t.role || 'Teknisi' }})</span></span>
                                            <span class="font-mono text-emerald-700 font-semibold">Rp {{ formatRupiah(t.amount) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="text-[10px] text-emerald-800 leading-tight">
                                💡 <em>Setiap teknisi instalasi menerima insentif penuh masing-masing (misal 2 teknisi = masing-masing Rp {{ formatRupiah(props.masterFees?.pasang || props.masterFeePasang || 35000) }}). Seluruh fee booking, survey, dan pasang otomatis masuk ke menu Insentif & Potongan saat aktivasi.</em>
                            </p>
                        </div>

                        <!-- Simulasi Tagihan Invoice Pertama (Hanya Berbayar & Gratis) -->
                        <div v-if="invoiceSimulation && activationForm.service_status !== 'free_home'" class="bg-gradient-to-br from-slate-50 via-purple-50/40 to-slate-50 p-4 rounded-xl border border-purple-200/90 shadow-sm space-y-3">
                            <div class="flex items-center justify-between border-b border-purple-100 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center text-sm shadow-sm font-bold">
                                        🧾
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Simulasi Tagihan Pertama</h4>
                                        <p class="text-[10px] text-gray-500">Estimasi tagihan invoice yang langsung terbentuk</p>
                                    </div>
                                </div>
                                <span :class="[
                                    'text-[10px] font-bold px-2.5 py-0.5 rounded-full border shadow-2xs',
                                    invoiceSimulation.isGratis ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                                    (invoiceSimulation.isProrata ? 'bg-amber-100 text-amber-900 border-amber-300' : 
                                    (invoiceSimulation.billingType === 'prabayar' ? 'bg-purple-100 text-purple-900 border-purple-200' : 'bg-blue-100 text-blue-900 border-blue-200'))
                                ]">
                                    {{ invoiceSimulation.badgeLabel }}
                                </span>
                            </div>

                            <!-- Rincian Biaya -->
                            <div class="space-y-1.5 text-xs">
                                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                    <span class="text-gray-600">Paket Berlangganan</span>
                                    <span class="font-semibold text-gray-900">{{ invoiceSimulation.packageName }} ({{ formatRupiah(invoiceSimulation.basePrice) }}/bln)</span>
                                </div>
                                
                                <div class="flex justify-between items-start py-1 border-b border-gray-100">
                                    <div>
                                        <span class="text-gray-600 block">Periode Pemakaian</span>
                                        <span v-if="invoiceSimulation.formulaDesc" class="text-[10px] text-purple-700 font-mono block mt-0.5">
                                            Rumus: {{ invoiceSimulation.formulaDesc }}
                                        </span>
                                    </div>
                                    <span class="font-semibold text-gray-900 text-right">{{ invoiceSimulation.periodText }}</span>
                                </div>

                                <div class="flex justify-between items-center py-1 border-b border-gray-100">
                                    <span class="text-gray-600">Biaya Pokok</span>
                                    <span class="font-bold text-gray-900">{{ formatRupiah(invoiceSimulation.subtotal) }}</span>
                                </div>

                                <div v-if="invoiceSimulation.totalTaxPercent > 0 && !invoiceSimulation.isGratis" class="flex justify-between items-center py-1 border-b border-gray-100 text-purple-700">
                                    <span class="flex items-center gap-1">
                                        Pajak ({{ invoiceSimulation.taxBreakdownText }})
                                    </span>
                                    <span class="font-semibold">+ {{ formatRupiah(invoiceSimulation.taxAmount) }}</span>
                                </div>

                                <div v-if="invoiceSimulation.isGratis" class="flex justify-between items-center py-1 border-b border-gray-100 text-emerald-600 font-semibold">
                                    <span>Status Layanan</span>
                                    <span>Gratis (Rp 0)</span>
                                </div>

                                <!-- Total Tagihan Pertama -->
                                <div class="flex justify-between items-center pt-2">
                                    <div>
                                        <span class="text-xs font-bold text-gray-800 block">Total Tagihan Pertama</span>
                                        <span class="text-[10px] text-gray-500">Tanggal Isolir: <strong class="text-rose-600 font-semibold">{{ invoiceSimulation.dueDate }}</strong></span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm sm:text-base font-extrabold text-purple-700 bg-purple-100/70 px-3 py-1 rounded-lg border border-purple-200">
                                            {{ formatRupiah(invoiceSimulation.totalAmount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Catatan Bulan Berikutnya jika Prorata -->
                            <div v-if="invoiceSimulation.isProrata" class="bg-amber-50/80 border border-amber-200/80 rounded-lg p-2.5 text-[11px] text-amber-800 flex items-start gap-2">
                                <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div class="leading-relaxed">
                                    Prorata hanya berlaku <strong>1 kali</strong> pada invoice pertama ini. Bulan berikutnya (<span class="font-semibold">{{ invoiceSimulation.nextBillingMonthName }}</span>), tagihan otomatis normal pascabayar penuh sebesar <strong class="text-amber-950">{{ formatRupiah(invoiceSimulation.nextMonthNormalTotal) }}</strong> (terbit setiap tgl {{ invoiceSimulation.issueDateSetting }}).
                                </div>
                            </div>
                        </div>

                        <!-- Banner Bypass PPPoE & Hotspot Radius untuk Free Home -->
                        <div v-if="activationForm.service_status === 'free_home'" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center gap-2 text-slate-800">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">PPPoE & Hotspot Radius Dilewati</span>
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">
                                Karena pelanggan <strong>Free Home</strong> menggunakan voucher hotspot untuk akses internet melalui Wi-Fi ONT rumahan, <strong>akun PPPoE / secret Radius mikrotik tidak dibuat dan tidak disinkronisasi</strong>. ONT siap memancarkan sinyal Wi-Fi login voucher secara stabil.
                            </p>
                        </div>

                        <template v-else>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE/Member Username</label>
                                    <input v-model="activationForm.pppoe_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="user@isp" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">PPPoE/Member Password</label>
                                    <input v-model="activationForm.pppoe_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="***" />
                                </div>
                            </div>
                        </template>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mode Akses (Catatan)</label>
                                <select v-model="activationForm.access_mode" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all">
                                    <option value="PPPOE">PPPoE</option>
                                    <option value="HOTSPOT/MEMBER">Hotspot / Member</option>
                                    <option value="STATIC">Static IP</option>
                                    <option value="DHCP">DHCP / Dynamic</option>
                                    <option value="Bridge">Bridge</option>
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
                        
                        <!-- Hotspot Gratis (Ditiadakan jika Free Home) -->
                        <div v-if="activationForm.service_status !== 'free_home'" class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="activationForm.free_hotspot" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-4 h-4">
                                <span class="text-sm font-semibold text-gray-700">Berikan Gratis 1 User Hotspot</span>
                            </label>

                            <div v-if="activationForm.free_hotspot" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">User Hotspot</label>
                                    <input v-model="activationForm.hotspot_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="user_hotspot" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password Hotspot</label>
                                    <input v-model="activationForm.hotspot_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="***" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">VLAN ID Hotspot</label>
                                    <input v-model="activationForm.hotspot_vlan_id" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Misal: 200" />
                                </div>
                            </div>
                        </div>

                        <!-- Pengaturan Pajak Pelanggan (Ditiadakan jika Free Home) -->
                        <div v-if="activationForm.service_status !== 'free_home'" class="bg-white border rounded-xl p-4 shadow-sm space-y-3 transition-all"
                            :class="activationForm.enable_tax ? 'border-purple-200 ring-1 ring-purple-100 bg-purple-50/20' : 'border-gray-200 bg-gray-50/50'">
                            
                            <div class="flex items-center justify-between">
                                <label for="enable_tax" class="flex items-center gap-2.5 cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        id="enable_tax" 
                                        v-model="activationForm.enable_tax" 
                                        class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500 cursor-pointer transition-colors"
                                    />
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Pengaturan Pajak Pelanggan
                                    </span>
                                </label>
                                
                                <span 
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full border transition-all"
                                    :class="activationForm.enable_tax 
                                        ? 'bg-purple-100 text-purple-700 border-purple-200' 
                                        : 'bg-gray-100 text-gray-500 border-gray-200'"
                                >
                                    {{ activationForm.enable_tax ? 'PPN Aktif (' + (activationForm.tax_ppn || 11) + '%)' : 'Bebas PPN (Nonaktif)' }}
                                </span>
                            </div>

                            <!-- Form Input PPN (Muncul jika enable_tax aktif) -->
                            <div v-if="activationForm.enable_tax" class="pt-2 border-t border-purple-100/60 space-y-2">
                                <p class="text-[11px] text-gray-500">Pajak Pertambahan Nilai (PPN) yang ditambahkan ke invoice tagihan pelanggan:</p>
                                <div class="max-w-xs">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">PPN (%)</label>
                                    <div class="relative">
                                        <input v-model="activationForm.tax_ppn" type="number" step="0.01" min="0" max="100" class="w-full bg-white border border-gray-200 rounded-lg pr-7 pl-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all font-semibold text-purple-700" placeholder="11" />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-semibold">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Informasi ketika Bebas Pajak -->
                            <div v-else class="pt-1.5 flex items-center gap-2 text-xs text-emerald-700 bg-emerald-50/70 p-2.5 rounded-lg border border-emerald-100">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Pelanggan ini <strong>bebas pajak</strong> (invoice tidak dikenakan PPN / 0%).</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                            <textarea v-model="activationForm.notes" rows="2" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Catatan internal setelah aktivasi..."></textarea>
                        </div>
                    </div>
                    <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showActivationModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                        <button v-can="'customers_activation_activate'" type="submit" :disabled="activationForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                            <svg v-if="activationForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ activationForm.processing ? 'Memproses...' : 'Aktivasi Sekarang' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Data & Konfigurasi Aktivasi -->
        <Teleport to="body">
            <div v-if="showEditModal && editCustomer" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
                <div class="bg-white border border-gray-200 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto overflow-hidden animate-fade-in-up">
                    <div class="p-5 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-purple-50 to-purple-100/50">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">⚡</span>
                            <div>
                                <h3 class="text-lg font-bold text-purple-900 leading-tight">Edit Konfigurasi Aktivasi</h3>
                                <p class="text-xs text-purple-700 font-medium">{{ editCustomer.name }} ({{ editCustomer.customer_code }})</p>
                            </div>
                        </div>
                        <button @click="showEditModal = false" class="text-gray-500 hover:text-gray-900">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitEditActivation">
                        <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                            <!-- Detail Data ONT & Pelanggan -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Detail Data ONT & Pelanggan</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 block text-[10px] uppercase font-semibold">Nama Pelanggan</span>
                                        <span class="font-bold text-gray-900">{{ editCustomer.name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 block text-[10px] uppercase font-semibold">Area/Wilayah</span>
                                        <span class="font-bold text-gray-900">{{ editCustomer.area || '-' }}</span>
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <span class="text-gray-500 block text-[10px] uppercase font-semibold">Serial Number ONT</span>
                                        <span class="font-bold text-gray-900 font-mono">{{ editCustomer.ont?.serial_number || editCustomer.ont?.sn || '-' }}</span>
                                    </div>
                                    <div v-if="editCustomer.ont?.odp">
                                        <span class="text-gray-500 block text-[10px] uppercase font-semibold">ODP & Port</span>
                                        <span class="font-bold text-gray-900">{{ editCustomer.ont?.odp?.name || '-' }} (Port {{ editCustomer.ont?.port_number || '-' }})</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Layanan Pelanggan (3 Radio Cards) -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-2">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Status Layanan Pelanggan <span class="text-red-500">*</span>
                                    </span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                    <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', editForm.service_status === 'berbayar' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                        <div class="flex items-center gap-2">
                                            <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', editForm.service_status === 'berbayar' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                                <div v-if="editForm.service_status === 'berbayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-900 block">Berbayar</span>
                                                <span class="text-[10px] text-gray-500">Tarif bulanan</span>
                                            </div>
                                        </div>
                                        <input type="radio" value="berbayar" v-model="editForm.service_status" class="sr-only">
                                    </label>
                                    
                                    <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', editForm.service_status === 'gratis' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                        <div class="flex items-center gap-2">
                                            <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', editForm.service_status === 'gratis' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                                <div v-if="editForm.service_status === 'gratis'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-900 block">Gratis (Rp 0)</span>
                                                <span class="text-[10px] text-gray-500">Non-invoice</span>
                                            </div>
                                        </div>
                                        <input type="radio" value="gratis" v-model="editForm.service_status" class="sr-only">
                                    </label>

                                    <label :class="['flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-all', editForm.service_status === 'free_home' ? 'border-amber-600 bg-amber-50/60 ring-1 ring-amber-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                        <div class="flex items-center gap-2">
                                            <div :class="['w-4 h-4 rounded-full border flex items-center justify-center shrink-0', editForm.service_status === 'free_home' ? 'border-amber-600 bg-amber-600' : 'border-gray-300']">
                                                <div v-if="editForm.service_status === 'free_home'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                            </div>
                                            <div>
                                                <span class="text-xs font-bold text-gray-900 block">Free Home</span>
                                                <span class="text-[10px] text-amber-700 font-semibold">Voucher ONT</span>
                                            </div>
                                        </div>
                                        <input type="radio" value="free_home" v-model="editForm.service_status" class="sr-only">
                                    </label>
                                </div>
                            </div>

                            <!-- Siklus Penagihan Invoice (Ditiadakan jika Free Home) -->
                            <div v-if="editForm.service_status !== 'free_home'" class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-3">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        Siklus Penagihan Invoice <span class="text-red-500">*</span>
                                    </label>
                                    <span class="text-[11px] text-purple-700 bg-purple-100/70 px-2 py-0.5 rounded-md font-semibold">
                                        Default: {{ formatBillingName(defaultBillingType) }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label :class="['relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all', editForm.billing_type === 'prabayar' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                        <input type="radio" value="prabayar" v-model="editForm.billing_type" class="sr-only">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-bold" :class="editForm.billing_type === 'prabayar' ? 'text-purple-900' : 'text-gray-900'">Prabayar</span>
                                            <div :class="['w-4 h-4 rounded-full border flex items-center justify-center', editForm.billing_type === 'prabayar' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                                <div v-if="editForm.billing_type === 'prabayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        <span class="text-[10px] text-gray-500">Bayar dulu baru pakai (Full 1 bulan di muka)</span>
                                    </label>
                                    
                                    <label :class="['relative flex flex-col p-3 rounded-xl border cursor-pointer transition-all', editForm.billing_type === 'pascabayar' ? 'border-purple-600 bg-purple-50/50 ring-1 ring-purple-600' : 'border-gray-200 bg-white hover:border-gray-300']">
                                        <input type="radio" value="pascabayar" v-model="editForm.billing_type" class="sr-only">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-bold" :class="editForm.billing_type === 'pascabayar' ? 'text-purple-900' : 'text-gray-900'">Pasca Bayar</span>
                                            <div :class="['w-4 h-4 rounded-full border flex items-center justify-center', editForm.billing_type === 'pascabayar' ? 'border-purple-600 bg-purple-600' : 'border-gray-300']">
                                                <div v-if="editForm.billing_type === 'pascabayar'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                                            </div>
                                        </div>
                                        <span class="text-[10px] text-gray-500">Pakai dulu baru bayar (Postpaid)</span>
                                    </label>
                                </div>

                                <!-- Pilihan Siklus Tanggal Tagihan (Billing Cycle) -->
                                <div class="space-y-1.5 pt-1">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Siklus Tanggal Tagihan
                                        </label>
                                        <span class="text-[10px] text-purple-700 bg-purple-100/70 px-2 py-0.5 rounded-md font-semibold">
                                            Default: {{ defaultBillingCycle === 'anniversary' ? 'Sesuai Tgl Aktivasi' : 'Kalender Serentak' }}
                                        </span>
                                    </div>
                                    <select v-model="editForm.billing_cycle" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs text-slate-700 focus:ring-2 focus:ring-purple-500 shadow-sm">
                                        <option value="default">Ikuti Default Sistem ({{ defaultBillingCycle === 'anniversary' ? 'Sesuai Tgl Aktivasi' : 'Kalender Serentak' }})</option>
                                        <option value="anniversary">Sesuai Tanggal Aktivasi (Anniversary)</option>
                                        <option value="calendar">Siklus Kalender (Tanggal Serentak)</option>
                                        <option value="custom_date">Pilih Tanggal Khusus Sendiri</option>
                                    </select>
                                    <div v-if="editForm.billing_cycle === 'custom_date'" class="flex items-center gap-2 mt-2 p-2.5 bg-purple-50/50 border border-purple-200 rounded-xl">
                                        <span class="text-xs text-purple-900 font-medium">Tanggal Tagihan Rutin:</span>
                                        <input type="number" v-model="editForm.billing_day" min="1" max="28" class="w-20 px-2.5 py-1 text-xs border border-purple-300 rounded-lg focus:ring-purple-500 font-bold text-purple-800 bg-white" placeholder="1-28" />
                                        <span class="text-xs text-purple-700">tiap bulan</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Banner Khusus Free Home Tanpa Tagihan -->
                            <div v-if="editForm.service_status === 'free_home'" class="p-4 bg-gradient-to-r from-amber-50 via-orange-50 to-yellow-50 border border-amber-200 rounded-xl space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">🏠</span>
                                    <div>
                                        <h4 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Aktivasi Mode Free Home (Voucher Rumahan ONT)</h4>
                                        <p class="text-[11px] text-amber-800">Pelanggan tidak dikenakan tagihan aktivasi dan tidak membentuk invoice.</p>
                                    </div>
                                </div>
                                <p class="text-[11px] text-amber-900 leading-relaxed">
                                    Pelanggan menggunakan ONT di rumah khusus untuk koneksi voucher hotspot. Sistem tidak akan membuat invoice tagihan atau piutang bulanan.
                                </p>
                            </div>

                            <!-- Detail Paket & Tanggal Aktivasi -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Aktivasi <span class="text-red-500">*</span></label>
                                    <input v-model="editForm.activation_date" type="date" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Paket Berlangganan</label>
                                    <select v-model="editForm.package_id" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all">
                                        <option value="">-- Tetap Pakai Paket Saat Ini --</option>
                                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                            {{ pkg.name }} ({{ formatRupiah(pkg.price) }})
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Banner Bypass PPPoE & Hotspot Radius untuk Free Home -->
                            <div v-if="editForm.service_status === 'free_home'" class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                                <div class="flex items-center gap-2 text-slate-800">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-xs font-bold text-slate-900 uppercase tracking-wider">PPPoE & Hotspot Radius Dilewati</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Karena pelanggan <strong>Free Home</strong> menggunakan voucher hotspot untuk akses internet melalui Wi-Fi ONT rumahan, <strong>akun PPPoE / secret Radius mikrotik tidak dibuat dan tidak disinkronisasi</strong>. ONT siap memancarkan sinyal Wi-Fi login voucher secara stabil.
                                </p>
                            </div>

                            <!-- PPPoE / Member Username & Password (Hanya jika bukan Free Home) -->
                            <template v-else>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-sm font-medium text-gray-700">
                                                {{ editForm.access_mode === 'HOTSPOT/MEMBER' ? 'Username Hotspot / Member' : 'PPPoE / Member Username' }}
                                            </label>
                                            <button type="button" @click="generateEditPppoe" class="text-[11px] text-purple-600 hover:text-purple-800 font-semibold flex items-center gap-1 cursor-pointer">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                Generate
                                            </button>
                                        </div>
                                        <input v-model="editForm.pppoe_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all text-sm font-mono" :placeholder="editForm.access_mode === 'HOTSPOT/MEMBER' ? 'username_member' : 'user@isp'" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ editForm.access_mode === 'HOTSPOT/MEMBER' ? 'Password Hotspot / Member' : 'PPPoE / Member Password' }}
                                        </label>
                                        <input v-model="editForm.pppoe_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all text-sm font-mono" placeholder="***" />
                                    </div>
                                </div>
                            </template>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode Akses (Catatan)</label>
                                    <select v-model="editForm.access_mode" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all text-sm">
                                        <option value="PPPOE">PPPoE</option>
                                        <option value="HOTSPOT/MEMBER">Hotspot / Member</option>
                                        <option value="STATIC">Static IP</option>
                                        <option value="DHCP">DHCP / Dynamic</option>
                                        <option value="Bridge">Bridge</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">VLAN Mode</label>
                                    <select v-model="editForm.vlan_mode" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all">
                                        <option value="">-- Pilih --</option>
                                        <option value="Route">Route</option>
                                        <option value="Bridge">Bridge</option>
                                        <option value="VLAN">VLAN (Tagged)</option>
                                        <option value="Untagged">Untagged</option>
                                    </select>
                                </div>
                                
                                <div v-if="editForm.vlan_mode === 'VLAN'" class="col-span-full">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">No VLAN ID</label>
                                    <input v-model="editForm.vlan_id" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Misal: 100" />
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Akses Login ONT</h4>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">IP Login ONT</label>
                                    <input v-model="editForm.ip_login" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="192.168.1.1" />
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Username ONT</label>
                                        <input v-model="editForm.login_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="admin" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password ONT</label>
                                        <input v-model="editForm.login_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="admin" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hotspot Gratis (Ditiadakan jika Free Home) -->
                            <div v-if="editForm.service_status !== 'free_home'" class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="editForm.free_hotspot" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 w-4 h-4">
                                    <span class="text-sm font-semibold text-gray-700">Berikan Gratis 1 User Hotspot</span>
                                </label>

                                <div v-if="editForm.free_hotspot" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">User Hotspot</label>
                                        <input v-model="editForm.hotspot_user" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="user_hotspot" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Hotspot</label>
                                        <input v-model="editForm.hotspot_password" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="***" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">VLAN ID Hotspot</label>
                                        <input v-model="editForm.hotspot_vlan_id" type="text" class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 shadow-sm transition-all" placeholder="Misal: 200" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                            <button type="button" @click="showEditModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                            <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                <svg v-if="editForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
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
import { billingIsolationDate } from '@/Utils/billingIsolation';

const props = defineProps({
    customers: Object,
    packages: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    stats: Object,
    filters: Object,
    taxSettings: Object,
    billingSettings: Object,
    masterFeePasang: { type: Number, default: 35000 },
    masterFees: {
        type: Object,
        default: () => ({ booking: 35000, survey: 15000, pasang: 35000 })
    },
});

const currentTab = ref(props.filters?.tab || 'menunggu');
const filterArea = ref(props.filters?.area || props.filters?.area_id || '');
const filterDateFrom = ref(props.filters?.date_from || '');
const filterDateTo = ref(props.filters?.date_to || '');

watch(() => props.filters, (newFilters) => {
    if (newFilters?.tab) currentTab.value = newFilters.tab;
    filterArea.value = newFilters?.area || newFilters?.area_id || '';
    filterDateFrom.value = newFilters?.date_from || '';
    filterDateTo.value = newFilters?.date_to || '';
}, { deep: true });

function switchTab(tab) {
    currentTab.value = tab;
    router.get('/customers/activation', {
        tab: tab,
        area: filterArea.value || undefined,
        search: props.filters?.search || undefined,
        date_from: filterDateFrom.value || undefined,
        date_to: filterDateTo.value || undefined,
    }, { preserveState: false, preserveScroll: true });
}

function applyFilters() {
    const currentSearch = typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('search') || props.filters?.search || undefined) : props.filters?.search;
    router.get('/customers/activation', {
        tab: currentTab.value,
        search: currentSearch || undefined,
        area: filterArea.value || undefined,
        date_from: filterDateFrom.value || undefined,
        date_to: filterDateTo.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    filterArea.value = '';
    filterDateFrom.value = '';
    filterDateTo.value = '';
    router.get('/customers/activation', {
        tab: currentTab.value
    }, { preserveState: true, preserveScroll: true, replace: true });
}

const buildSearchRoute = () => {
    return '/customers/activation';
};

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

const columns = computed(() => {
    if (currentTab.value === 'riwayat') {
        return [
            { key: 'name', label: 'Pelanggan' },
            { key: 'package', label: 'Paket & Tagihan' },
            { key: 'activation_date', label: 'Tgl Aktivasi' },
            { key: 'account', label: 'Akun / Mode' },
            { key: 'status', label: 'Status' },
            { key: 'topology', label: 'Topologi' },
        ];
    }
    return [
        { key: 'name', label: 'Pelanggan' },
        { key: 'package', label: 'Paket' },
        { key: 'status', label: 'Status' },
        { key: 'ont', label: 'ONT S/N' },
        { key: 'topology', label: 'Topologi' },
        { key: 'signal', label: 'Redaman' },
    ];
});

function getAvatarColor(name) {
    if (!name) return 'bg-indigo-500';
    const colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-pink-500', 'bg-orange-500'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

function formatDateIndo(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    } catch (e) {
        return dateStr;
    }
}

// Edit Activation Modal State
const showEditModal = ref(false);
const editCustomer = ref(null);
const editForm = useForm({
    activation_date: '',
    service_status: 'berbayar',
    billing_type: (page.props.billingSettings?.billing_type === 'pascabayar') ? 'pascabayar' : 'prabayar',
    billing_cycle: 'default',
    billing_day: null,
    package_id: '',
    pppoe_user: '',
    pppoe_password: '',
    vlan_mode: '',
    vlan_id: '',
    access_mode: 'PPPOE',
    ip_login: '192.168.1.1',
    login_user: 'admin',
    login_password: 'admin',
    free_hotspot: false,
    hotspot_user: '',
    hotspot_password: '',
    hotspot_vlan_id: '',
});

function openEditActivationModal(customer) {
    editCustomer.value = customer;
    editForm.activation_date = customer.activation_date || new Date().toISOString().split('T')[0];
    editForm.service_status = customer.service_status || 'berbayar';
    editForm.billing_type = customer.billing_type || defaultBillingType.value;
    editForm.billing_cycle = customer.billing_cycle || 'default';
    editForm.billing_day = customer.billing_day || null;
    editForm.package_id = customer.package_id || '';

    // Normalisasi Mode Akses
    let mode = (customer.ont?.access_mode || customer.package?.access_mode || 'PPPOE').trim();
    const upperMode = mode.toUpperCase();
    if (upperMode.includes('HOTSPOT') || upperMode.includes('MEMBER')) {
        mode = 'HOTSPOT/MEMBER';
    } else if (upperMode === 'PPPOE') {
        mode = 'PPPOE';
    } else if (upperMode === 'STATIC') {
        mode = 'STATIC';
    } else if (upperMode === 'DHCP') {
        mode = 'DHCP';
    } else if (upperMode === 'BRIDGE') {
        mode = 'Bridge';
    }
    editForm.access_mode = mode;

    // Ambil kredensial akun: jika pppoe_user kosong tapi hotspot_user ada (seperti pada pelanggan Hotspot/Member), ambil dari hotspot_user
    editForm.pppoe_user = customer.ont?.pppoe_user || customer.ont?.hotspot_user || '';
    editForm.pppoe_password = customer.ont?.pppoe_password || customer.ont?.hotspot_password || '';

    editForm.vlan_mode = customer.ont?.vlan_mode || '';
    editForm.vlan_id = customer.ont?.vlan_id || '';
    editForm.ip_login = customer.ont?.ip_login || '192.168.1.1';
    editForm.login_user = customer.ont?.login_user || 'admin';
    editForm.login_password = customer.ont?.login_password || 'admin';
    editForm.free_hotspot = !!customer.ont?.free_hotspot;
    editForm.hotspot_user = customer.ont?.hotspot_user || '';
    editForm.hotspot_password = customer.ont?.hotspot_password || '';
    editForm.hotspot_vlan_id = customer.ont?.hotspot_vlan_id || '';
    showEditModal.value = true;
}

const defaultBillingCycle = computed(() => {
    return page.props.billingSettings?.billing_cycle || 'anniversary';
});

function submitEditActivation() {
    if (!editCustomer.value) return;
    if (editForm.access_mode === 'HOTSPOT/MEMBER') {
        editForm.hotspot_user = editForm.pppoe_user;
        editForm.hotspot_password = editForm.pppoe_password;
    }
    editForm.post(`/customers/${editCustomer.value.id}/update-activation`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
}

function generateEditPppoe() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let user = '';
    let pass = '';
    for (let i = 0; i < 6; i++) user += chars.charAt(Math.floor(Math.random() * chars.length));
    for (let i = 0; i < 8; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    const prefix = editForm.access_mode === 'HOTSPOT/MEMBER' ? 'hs_' : 'user_';
    editForm.pppoe_user = prefix + user;
    editForm.pppoe_password = pass;
}

const defaultBillingType = computed(() => {
    const t = page.props.billingSettings?.billing_type;
    return (t === 'pascabayar') ? 'pascabayar' : 'prabayar';
});

const formatBillingName = (type) => {
    if (type === 'pascabayar') return 'Pasca Bayar';
    return 'Prabayar';
};

const formatRupiah = (val) => {
    return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
};

const formatDateShort = (d) => {
    if (!d) return '-';
    try {
        const dateObj = typeof d === 'string' ? new Date(d) : d;
        return dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch (e) {
        return String(d);
    }
};

function signalClass(rx) {
    if (!rx) return 'text-gray-400';
    const num = parseFloat(rx);
    if (isNaN(num)) return 'text-gray-400';
    if (num >= -20) return 'text-emerald-500 font-bold';
    if (num >= -25) return 'text-green-500 font-bold';
    if (num >= -28) return 'text-amber-500 font-bold';
    return 'text-rose-500 font-bold';
}

const activeCustomer = ref(null);
const showActivationModal = ref(false);
const activationForm = useForm({
    activation_date: '',
    billing_type: (page.props.billingSettings?.billing_type === 'pascabayar') ? 'pascabayar' : 'prabayar',
    billing_cycle: 'default',
    billing_day: null,
    is_prorata: true,
    service_status: 'berbayar',
    enable_tax: true,
    pppoe_user: '',
    pppoe_password: '',
    vlan_mode: '',
    vlan_id: '',
    access_mode: 'PPPOE',
    ip_login: '192.168.1.1',
    login_user: 'admin',
    login_password: 'admin',
    notes: '',
    free_hotspot: false,
    hotspot_user: '',
    hotspot_password: '',
    hotspot_vlan_id: '',
    tax_ppn: 11,
    tax_bhp: 0,
    tax_uso: 0,
});

watch(() => activationForm.enable_tax, (val) => {
    if (val && !activationForm.tax_ppn) {
        activationForm.tax_ppn = page.props.taxSettings?.tax_ppn || 11;
    }
});

const customerIncentivesPreview = computed(() => {
    const cust = activeCustomer.value;
    if (!cust) return { booking: null, surveys: [], installers: [], totalAmount: 0, hasAny: false, isMigrated: false };

    const isMigrated = Boolean(cust.is_migrated);
    if (isMigrated) {
        // Khusus migrasi: insentif teknisi (pasang, survey) dan booking karyawan tidak dimunculkan.
        // Hanya fee market affiliate orang luar yang diperbolehkan.
        const masterBooking = Number(props.masterFees?.booking || 35000);
        let booking = null;
        if (cust.sales && cust.sales.role === 'affiliate') {
            const fee = Number(cust.sales.booking_fee) > 0 ? Number(cust.sales.booking_fee) : masterBooking;
            booking = {
                id: cust.sales.id,
                name: cust.sales.name,
                role: 'Mitra Afiliasi Luar',
                amount: fee,
            };
        }
        return {
            booking,
            surveys: [],
            installers: [],
            totalAmount: booking ? booking.amount : 0,
            hasAny: Boolean(booking),
            isMigrated: true
        };
    }

    const masterBooking = Number(props.masterFees?.booking || 35000);
    const masterSurvey = Number(props.masterFees?.survey || 15000);
    const masterPasang = Number(props.masterFees?.pasang || props.masterFeePasang || 35000);

    // 1. Fee Booking (Sales)
    let booking = null;
    if (cust.sales) {
        const fee = Number(cust.sales.booking_fee) > 0 ? Number(cust.sales.booking_fee) : masterBooking;
        booking = {
            id: cust.sales.id,
            name: cust.sales.name,
            role: cust.sales.role || 'Sales',
            amount: fee,
        };
    }

    // 2. Fee Survey (Surveyor)
    const surveyMap = new Map();
    if (Array.isArray(cust.surveys)) {
        cust.surveys.forEach(s => {
            if (s.surveyor && !surveyMap.has(s.surveyor.id)) {
                surveyMap.set(s.surveyor.id, {
                    id: s.surveyor.id,
                    name: s.surveyor.name,
                    role: s.surveyor.role || 'Surveyor',
                    amount: masterSurvey,
                });
            }
        });
    }
    if (Array.isArray(cust.technician_schedules)) {
        cust.technician_schedules.filter(s => s.type === 'survey' && s.technician).forEach(s => {
            if (!surveyMap.has(s.technician_id)) {
                surveyMap.set(s.technician_id, {
                    id: s.technician.id,
                    name: s.technician.name,
                    role: s.technician.role || 'Surveyor',
                    amount: masterSurvey,
                });
            }
        });
    }
    const surveys = Array.from(surveyMap.values());

    // 3. Fee Instalasi / Pasang (Teknisi)
    const installMap = new Map();
    if (Array.isArray(cust.technician_schedules)) {
        cust.technician_schedules.filter(s => s.type === 'installation' && s.technician).forEach(s => {
            if (!installMap.has(s.technician_id)) {
                const fee = Number(s.technician.installation_fee) > 0 ? Number(s.technician.installation_fee) : masterPasang;
                installMap.set(s.technician_id, {
                    id: s.technician.id,
                    name: s.technician.name,
                    role: s.technician.role || 'Teknisi',
                    amount: fee,
                });
            }
        });
    }
    const installers = Array.from(installMap.values());

    let totalAmount = 0;
    if (booking) totalAmount += booking.amount;
    surveys.forEach(s => totalAmount += s.amount);
    installers.forEach(i => totalAmount += i.amount);

    return {
        booking,
        surveys,
        installers,
        totalAmount,
        hasAny: Boolean(booking || surveys.length > 0 || installers.length > 0),
        isMigrated: false
    };
});

const assignedInstallationTechnicians = computed(() => customerIncentivesPreview.value.installers);

const invoiceSimulation = computed(() => {
    const cust = activeCustomer.value;
    if (!cust) return null;

    const basePrice = Number(cust.base_amount || cust.package?.price || 0);
    const isGratis = activationForm.service_status === 'gratis';
    const bType = activationForm.billing_type || 'prabayar';
    const isProrataChecked = bType === 'pascabayar' && Boolean(activationForm.is_prorata);

    const bSettings = page.props.billingSettings || {};
    const issueDateSetting = Math.max(1, Math.min(28, Number(bSettings.invoice_issue_date || 1)));
    const prorataFormula = bSettings.prorata_formula || 'exact_days';

    // Parse activation date safely (local date)
    let actDate = new Date();
    if (activationForm.activation_date) {
        const parts = activationForm.activation_date.split('-');
        if (parts.length === 3) {
            actDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        }
    }

    // Tanggal akhir bulan berjalan (misal 31 Okt 2026):
    const endOfMonthDate = new Date(actDate.getFullYear(), actDate.getMonth() + 1, 0);
    const totalDaysInMonth = endOfMonthDate.getDate();

    let subtotal = basePrice;
    let daysUsed = 0;
    let totalDaysInCycle = totalDaysInMonth;
    let nextCutOffDate = null;
    let dueDate = null;
    let formulaDesc = '';
    let isProrataApplied = false;
    let periodText = '';
    let badgeLabel = 'Prabayar (Bayar di Muka)';

    if (bType === 'pascabayar') {
        // Tanggal cut-off tagihan bulan berikutnya (misal 1 Nov 2026):
        const actYear = actDate.getFullYear();
        const actMonth = actDate.getMonth();
        const actDay = actDate.getDate();

        if (actDay >= issueDateSetting) {
            nextCutOffDate = new Date(actYear, actMonth + 1, issueDateSetting);
        } else {
            nextCutOffDate = new Date(actYear, actMonth, issueDateSetting);
        }

        // Hari aktif dihitung dari tanggal aktivasi sampai akhir bulan berjalan (inklusif):
        daysUsed = Math.max(1, endOfMonthDate.getDate() - actDate.getDate() + 1);

        if (isProrataChecked && basePrice > 0 && daysUsed > 0 && daysUsed < totalDaysInCycle) {
            isProrataApplied = true;
            badgeLabel = 'Pascabayar - Prorata (1x)';
            if (prorataFormula === 'fixed_30') {
                subtotal = Math.round((basePrice / 30) * daysUsed);
                formulaDesc = `(${formatRupiah(basePrice)} / 30 hr) × ${daysUsed} hr`;
            } else if (prorataFormula === 'mid_month') {
                if (actDate.getDate() > 15) {
                    subtotal = Math.round(basePrice / 2);
                    formulaDesc = `Aktivasi > tengah bulan (50%)`;
                } else {
                    subtotal = basePrice;
                    formulaDesc = `Aktivasi awal bulan (100% normal)`;
                }
            } else {
                // exact_days: proporsional hari dalam bulan berjalan
                subtotal = Math.round((basePrice / totalDaysInCycle) * daysUsed);
                formulaDesc = `(${formatRupiah(basePrice)} / ${totalDaysInCycle} hr) × ${daysUsed} hr`;
            }
            periodText = `${formatDateShort(actDate)} s/d ${formatDateShort(endOfMonthDate)} (${daysUsed} hari aktif - Akhir Bulan)`;
        } else {
            badgeLabel = 'Pascabayar Normal (1 Bulan Penuh)';
            subtotal = basePrice;
            periodText = `1 Bulan Penuh (Cut-off tgl ${issueDateSetting} ${nextCutOffDate.toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })})`;
        }

        dueDate = billingIsolationDate(nextCutOffDate, bSettings);
    } else {
        badgeLabel = 'Prabayar (Bayar di Muka)';
        subtotal = basePrice;
        const endDate = new Date(actDate.getFullYear(), actDate.getMonth() + 1, actDate.getDate());
        periodText = `${formatDateShort(actDate)} s/d ${formatDateShort(endDate)} (1 Bulan di Muka)`;
        dueDate = billingIsolationDate(actDate, bSettings);
    }

    if (isGratis) {
        badgeLabel = 'Layanan Gratis (Rp 0)';
    }

    // Taxes (Hanya PPN yang ditagihkan ke invoice pelanggan)
    const isTaxEnabled = activationForm.enable_tax;
    const taxPpn = isTaxEnabled ? Number(activationForm.tax_ppn || 0) : 0;
    const totalTaxPercent = taxPpn;

    let taxAmount = 0;
    let totalAmount = subtotal;

    if (isGratis) {
        subtotal = 0;
        taxAmount = 0;
        totalAmount = 0;
    } else if (totalTaxPercent > 0) {
        taxAmount = Math.round(subtotal * (totalTaxPercent / 100));
        totalAmount = subtotal + taxAmount;
    }

    // Tax breakdown text
    const taxBreakdownText = `PPN ${taxPpn}%`;

    // Next month preview (normal postpaid)
    let nextMonthNormalSubtotal = basePrice;
    let nextMonthNormalTax = totalTaxPercent > 0 ? Math.round(basePrice * (totalTaxPercent / 100)) : 0;
    let nextMonthNormalTotal = isGratis ? 0 : (nextMonthNormalSubtotal + nextMonthNormalTax);
    let nextBillingMonthName = nextCutOffDate ? nextCutOffDate.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) : '';

    return {
        billingType: bType,
        isProrata: isProrataApplied,
        isGratis,
        badgeLabel,
        packageName: cust.package?.name || 'Paket Internet',
        basePrice,
        subtotal,
        formulaDesc,
        daysUsed,
        totalDaysInCycle,
        endOfMonthText: formatDateShort(endOfMonthDate),
        nextCutOffDate: nextCutOffDate ? formatDateShort(nextCutOffDate) : null,
        periodText,
        dueDate: dueDate ? `${formatDateShort(dueDate)} ${bSettings.isolate_time || '00:00'} WIB` : null,
        taxPpn,
        taxBhp: 0,
        taxUso: 0,
        totalTaxPercent,
        taxBreakdownText,
        taxAmount,
        totalAmount,
        issueDateSetting,
        nextBillingMonthName,
        nextMonthNormalTotal,
    };
});

function openActivationModal(customer) {
    activeCustomer.value = customer;
    activationForm.reset();
    activationForm.activation_date = new Date().toISOString().split('T')[0];
    let bType = customer.billing_type || defaultBillingType.value;
    if (bType !== 'pascabayar') bType = 'prabayar';
    activationForm.billing_type = bType;
    activationForm.is_prorata = page.props.billingSettings?.prorata_first_month !== false;
    activationForm.service_status = customer.service_status === 'free_home' ? 'free_home' : (customer.service_status === 'gratis' ? 'gratis' : 'berbayar');

    // Normalisasi Mode Akses
    let mode = (customer.ont?.access_mode || customer.package?.access_mode || 'PPPOE').trim();
    const upperMode = mode.toUpperCase();
    if (upperMode.includes('HOTSPOT') || upperMode.includes('MEMBER')) {
        mode = 'HOTSPOT/MEMBER';
    } else if (upperMode === 'PPPOE') {
        mode = 'PPPOE';
    } else if (upperMode === 'STATIC') {
        mode = 'STATIC';
    } else if (upperMode === 'DHCP') {
        mode = 'DHCP';
    } else if (upperMode === 'BRIDGE') {
        mode = 'Bridge';
    }
    activationForm.access_mode = mode;

    activationForm.pppoe_user = customer.ont?.pppoe_user || customer.ont?.hotspot_user || '';
    activationForm.pppoe_password = customer.ont?.pppoe_password || customer.ont?.hotspot_password || '';
    activationForm.vlan_mode = customer.ont?.vlan_mode || '';
    activationForm.vlan_id = customer.ont?.vlan_id || '';
    activationForm.ip_login = customer.ont?.ip_login || '192.168.1.1';
    activationForm.login_user = customer.ont?.login_user || 'admin';
    activationForm.login_password = customer.ont?.login_password || 'admin';
    activationForm.notes = '';
    activationForm.free_hotspot = !!customer.ont?.free_hotspot;
    activationForm.hotspot_user = customer.ont?.hotspot_user || '';
    activationForm.hotspot_password = customer.ont?.hotspot_password || '';
    activationForm.hotspot_vlan_id = customer.ont?.hotspot_vlan_id || '';
    
    // Default to customer's saved PPN or global tax settings (default 11%)
    const custPpn = customer.tax_ppn !== null && customer.tax_ppn !== undefined ? Number(customer.tax_ppn) : null;
    const globalPpn = Number(page.props.taxSettings?.tax_ppn || 11);

    if (custPpn !== null && custPpn === 0) {
        activationForm.enable_tax = false;
        activationForm.tax_ppn = globalPpn || 11;
    } else if (custPpn !== null && custPpn > 0) {
        activationForm.enable_tax = true;
        activationForm.tax_ppn = custPpn;
    } else {
        const hasGlobalTax = globalPpn > 0;
        activationForm.enable_tax = hasGlobalTax;
        activationForm.tax_ppn = globalPpn || 11;
    }

    showActivationModal.value = true;
}

function submitActivation() {
    if (activationForm.access_mode === 'HOTSPOT/MEMBER') {
        activationForm.hotspot_user = activationForm.pppoe_user;
        activationForm.hotspot_password = activationForm.pppoe_password;
    }
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
