<template>
    <AppLayout title="Paket Internet" subtitle="Kelola daftar paket internet dan harga">
        <!-- HEADER -->
        <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="w-full sm:w-96 relative">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Cari paket internet..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    @keyup.enter="doSearch"
                >
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <button @click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah Paket
            </button>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Paket</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Type / Limit</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Harga</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="pkg in packages.data" :key="pkg.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-3 h-3 rounded-full shadow-inner" :style="{ backgroundColor: pkg.color || '#4f46e5' }"></div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ pkg.name }}</div>
                                        <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                            <span>{{ pkg.speed_mbps }} Mbps</span>
                                            <span v-if="pkg.is_promo" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">PROMO</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="w-fit inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border" 
                                          :class="getAccessModeClass(pkg.access_mode)">
                                        {{ pkg.access_mode.toUpperCase() }}
                                    </span>
                                    <span class="text-[11px] text-gray-500 font-mono">{{ pkg.rate_limit || 'Default Profile' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold" :class="pkg.is_promo ? 'text-rose-600' : 'text-emerald-600'">
                                    Rp {{ formatPrice(pkg.is_promo ? pkg.promo_price : pkg.price) }}
                                </div>
                                <div v-if="pkg.is_promo" class="text-[10px] text-gray-400 line-through">Rp {{ formatPrice(pkg.price) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="pkg.is_active" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktif
                                </span>
                                <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-50 text-gray-700 border border-gray-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span>
                                    Nonaktif
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="openEditModal(pkg)" class="text-indigo-600 hover:text-indigo-900 mr-4 font-semibold">Edit</button>
                                <button @click="deletePackage(pkg.id)" class="text-red-600 hover:text-red-900 font-semibold">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="packages.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-500 text-base font-medium">Belum ada data paket internet.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2" v-if="packages.links && packages.links.length > 3">
                <template v-for="(link, idx) in packages.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded text-sm" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"></Link>
                    <span v-else v-html="link.label" class="px-3 py-1 border rounded bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed text-sm"></span>
                </template>
            </div>
        </div>

        <!-- FULLSCREEN MODAL FORM -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm overflow-hidden">
            <div class="bg-gray-50 w-full h-full flex flex-col sm:w-[95%] sm:h-[95%] sm:rounded-2xl shadow-2xl relative overflow-hidden">
                
                <!-- Modal Header -->
                <div class="flex-shrink-0 bg-white px-6 py-4 border-b flex justify-between items-center z-10 shadow-sm">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ editMode ? 'Edit Paket Internet' : 'Konfigurasi Paket Baru' }}</h2>
                        <p class="text-xs text-gray-500 mt-1">ISP Package Configuration Tool</p>
                    </div>
                    <button @click="closeModal" class="p-2 rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="flex-1 overflow-y-auto overflow-x-hidden p-0 sm:p-6 flex flex-col lg:flex-row gap-6">
                    
                    <!-- LEFT COLUMN: FORM -->
                    <div class="flex-1 space-y-6 max-w-4xl p-6 sm:p-0">
                        <form id="packageForm" @submit.prevent="showSummaryModal = true">
                            
                            <!-- A. INFORMASI PAKET -->
                            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
                                <div class="flex items-center gap-2 mb-4 border-b pb-2">
                                    <div class="p-1.5 bg-indigo-100 text-indigo-700 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">A. Informasi Paket</h3>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Paket <span class="text-red-500">*</span></label>
                                        <input v-model="form.name" type="text" @input="form.name = form.name.toUpperCase()" class="input-text uppercase" required placeholder="Contoh: INTERNET 20 MBPS">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Type Akses <span class="text-red-500">*</span></label>
                                        <select v-model="form.access_mode" class="input-text" required>
                                            <option value="pppoe">PPPoE</option>
                                            <option value="hotspot">Hotspot</option>
                                            <option value="voucher">Voucher</option>
                                            <option value="static_ip">Static IP</option>
                                            <option value="lainnya">Lainnya</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Kecepatan (Mbps) <span class="text-red-500">*</span></label>
                                        <div class="relative">
                                            <input v-model="form.speed_mbps" type="number" class="input-text pr-12" required min="1" placeholder="20">
                                            <span class="absolute right-3 top-2 text-xs font-bold text-gray-400">Mbps</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Warna Label</label>
                                        <div class="flex items-center gap-3">
                                            <input v-model="form.color" type="color" class="w-10 h-10 p-1 bg-white border border-gray-300 rounded cursor-pointer">
                                            <div class="text-xs font-mono text-gray-500">{{ form.color }}</div>
                                        </div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Deskripsi Singkat</label>
                                        <input v-model="form.description" type="text" class="input-text" placeholder="Keterangan singkat paket ini...">
                                    </div>
                                </div>
                            </div>

                            <!-- B. KONFIGURASI MIKROTIK -->
                            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mt-6">
                                <div class="flex items-center gap-2 mb-4 border-b pb-2">
                                    <div class="p-1.5 bg-blue-100 text-blue-700 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">B. Konfigurasi MikroTik</h3>
                                </div>

                                <div class="space-y-4">
                                    <!-- Rate Limit -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="flex items-center text-xs font-bold text-gray-700">
                                                Rate Limit
                                                <span class="ml-1 text-gray-400 group relative cursor-help">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <div class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-1 w-48 p-2 bg-gray-800 text-white text-[10px] rounded shadow-lg z-10 font-normal">Format limitasi bandwidth MikroTik. Kosongkan untuk pakai default profil.</div>
                                                </span>
                                            </label>
                                            <button type="button" @click="openRateLimitGen" class="text-[10px] bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-2.5 py-1.5 rounded flex items-center gap-1.5 transition-colors border border-indigo-200 shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                Advanced Generator
                                            </button>
                                        </div>
                                        <div class="flex gap-2">
                                            <input v-model="form.rate_limit" type="text" class="input-text flex-1 font-mono text-sm" placeholder="Contoh: 4M/4M 0/0 0/0 0/0 8 0/0">
                                            <button type="button" @click="copyText(form.rate_limit)" class="px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors" title="Copy">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Toggles for Custom Lists -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                        <div class="p-3 border rounded-lg bg-gray-50/50">
                                            <label class="flex items-center cursor-pointer">
                                                <div class="relative">
                                                    <input type="checkbox" v-model="form.is_mikrotik_group_custom" class="sr-only">
                                                    <div class="block w-10 h-6 rounded-full transition-colors" :class="form.is_mikrotik_group_custom ? 'bg-indigo-500' : 'bg-gray-300'"></div>
                                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="form.is_mikrotik_group_custom ? 'transform translate-x-4' : ''"></div>
                                                </div>
                                                <div class="ml-3 text-xs font-bold text-gray-700">MikroTik Group Custom</div>
                                            </label>
                                            <div v-if="form.is_mikrotik_group_custom" class="mt-3">
                                                <input v-model="form.mikrotik_group" type="text" class="input-text text-sm" placeholder="Nama profile/group...">
                                            </div>
                                        </div>

                                        <div class="p-3 border rounded-lg bg-gray-50/50">
                                            <label class="flex items-center cursor-pointer">
                                                <div class="relative">
                                                    <input type="checkbox" v-model="form.is_mikrotik_address_list_custom" class="sr-only">
                                                    <div class="block w-10 h-6 rounded-full transition-colors" :class="form.is_mikrotik_address_list_custom ? 'bg-indigo-500' : 'bg-gray-300'"></div>
                                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="form.is_mikrotik_address_list_custom ? 'transform translate-x-4' : ''"></div>
                                                </div>
                                                <div class="ml-3 text-xs font-bold text-gray-700">Address List Custom</div>
                                            </label>
                                            <div v-if="form.is_mikrotik_address_list_custom" class="mt-3">
                                                <input v-model="form.mikrotik_address_list" type="text" class="input-text text-sm" placeholder="Nama address list...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- C. LIMITASI & MASA AKTIF -->
                            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mt-6">
                                <div class="flex items-center gap-2 mb-4 border-b pb-2">
                                    <div class="p-1.5 bg-orange-100 text-orange-700 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">C. Limitasi & Masa Aktif</h3>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Masa Aktif <span class="text-red-500">*</span></label>
                                        <div class="flex">
                                            <input v-model="form.active_period" type="number" min="1" class="input-text rounded-r-none border-r-0 w-20 text-center" required>
                                            <select v-model="form.active_period_unit" class="input-text rounded-l-none flex-1 bg-gray-50 border-gray-300">
                                                <option value="Hari">Hari</option>
                                                <option value="Minggu">Minggu</option>
                                                <option value="Bulan">Bulan</option>
                                                <option value="Tahun">Tahun</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Shared Device <span class="text-red-500">*</span></label>
                                        <div class="flex items-center gap-2">
                                            <input v-model="form.shared_device" type="number" min="1" class="input-text w-20 text-center" required>
                                            <span class="text-sm text-gray-500 font-medium">Perangkat (HP/Laptop)</span>
                                        </div>
                                    </div>
                                    
                                    <div class="sm:col-span-2 pt-2">
                                        <label class="flex items-center p-3 border rounded-lg bg-gray-50/50 cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" v-model="form.is_active" class="sr-only">
                                                <div class="block w-10 h-6 rounded-full transition-colors" :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-300'"></div>
                                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="form.is_active ? 'transform translate-x-4' : ''"></div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-xs font-bold text-gray-800">Paket Aktif</div>
                                                <div class="text-[10px] text-gray-500">Pelanggan bisa berlangganan paket ini.</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- D. HARGA & PEMBAGIAN KEUNTUNGAN -->
                            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mt-6">
                                <div class="flex items-center gap-2 mb-4 border-b pb-2">
                                    <div class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">D. Harga & Keuntungan</h3>
                                </div>

                                <div class="space-y-5">
                                    <!-- Toggle Promo -->
                                    <div class="flex items-center justify-between p-3 border border-rose-100 bg-rose-50/30 rounded-lg">
                                        <div>
                                            <div class="text-xs font-bold text-rose-800">Mode Promo / Diskon</div>
                                            <div class="text-[10px] text-rose-600">Aktifkan untuk memberikan harga coret</div>
                                        </div>
                                        <label class="flex items-center cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" v-model="form.is_promo" class="sr-only">
                                                <div class="block w-10 h-6 rounded-full transition-colors" :class="form.is_promo ? 'bg-rose-500' : 'bg-gray-300'"></div>
                                                <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="form.is_promo ? 'transform translate-x-4' : ''"></div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div :class="form.is_promo ? 'opacity-70' : ''">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Harga Jual Normal <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-2.5 text-sm font-bold text-gray-400">Rp</span>
                                                <input v-model="form.price" type="number" min="0" class="input-text pl-10 text-lg font-bold" required>
                                            </div>
                                        </div>
                                        <div v-if="form.is_promo">
                                            <label class="block text-xs font-bold text-rose-700 mb-1">Harga Promo (Saat Ini) <span class="text-red-500">*</span></label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-2.5 text-sm font-bold text-rose-400">Rp</span>
                                                <input v-model="form.promo_price" type="number" min="0" class="input-text border-rose-300 focus:border-rose-500 focus:ring-rose-500 pl-10 text-lg font-bold text-rose-700" required>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="border-gray-100">

                                    <div class="grid grid-cols-1 gap-4 bg-cyan-50/40 p-4 rounded-xl border border-cyan-100">
                                        <div>
                                            <label class="flex items-center gap-1.5 text-[13px] font-bold text-slate-700 mb-2">
                                                Fee Reseller/Mitra 
                                                <svg class="w-4 h-4 text-slate-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
                                                <span class="text-red-500 font-bold">*Req</span>
                                            </label>
                                            <div class="flex shadow-sm">
                                                <span class="inline-flex items-center px-3 text-sm text-gray-700 bg-gray-50 border border-r-0 border-gray-200 rounded-l-md font-medium">Rp</span>
                                                <input v-model="form.fee_reseller" type="number" min="0" placeholder="min : 0" class="input-text rounded-l-none flex-1 border-gray-200" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="p-3 rounded-lg border flex justify-between items-center transition-colors" 
                                         :class="marginBersih < 0 ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-100'">
                                        <div class="text-xs font-bold" :class="marginBersih < 0 ? 'text-red-700' : 'text-emerald-800'">
                                            Estimasi Margin Bersih:
                                            <div v-if="marginBersih < 0" class="text-[10px] text-red-500 font-normal mt-0.5">⚠️ Pembagian fee melebihi harga jual</div>
                                        </div>
                                        <div class="text-lg font-black" :class="marginBersih < 0 ? 'text-red-600' : 'text-emerald-600'">
                                            Rp {{ formatPrice(marginBersih) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden submit button triggered by form submit event -->
                            <button type="submit" class="hidden"></button>
                        </form>
                    </div>

                    <!-- RIGHT COLUMN: PREVIEW -->
                    <div class="w-full lg:w-80 flex-shrink-0 relative">
                        <div class="sticky top-6">
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3 px-2">E. Preview Visual</h3>
                            
                            <!-- Package Card Preview -->
                            <div class="bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 overflow-hidden relative group">
                                <div class="h-2 w-full" :style="{ backgroundColor: form.color || '#4f46e5' }"></div>
                                
                                <div v-if="form.is_promo" class="absolute top-4 right-4 bg-rose-500 text-white text-[9px] font-black px-2 py-1 rounded shadow-sm transform rotate-3">
                                    PROMO
                                </div>

                                <div class="p-5">
                                    <div class="flex items-center gap-2 text-[10px] font-bold text-gray-500 uppercase mb-2">
                                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: form.color || '#4f46e5' }"></span>
                                        {{ form.access_mode }}
                                    </div>
                                    <h4 class="text-xl font-black text-gray-900 leading-tight mb-1" :style="{ color: form.color || '#4f46e5' }">{{ form.name || 'NAMA PAKET' }}</h4>
                                    <p class="text-xs text-gray-500 mb-5 min-h-[16px]">{{ form.description || 'Deskripsi akan tampil di sini...' }}</p>
                                    
                                    <div class="flex items-baseline gap-1 mb-6">
                                        <span class="text-2xl font-black text-gray-900">Rp{{ formatPrice(currentActivePrice) }}</span>
                                        <span class="text-xs text-gray-500 font-medium">/ {{ form.active_period }} {{ form.active_period_unit }}</span>
                                    </div>
                                    
                                    <div v-if="form.is_promo" class="text-xs text-gray-400 line-through mb-4 -mt-4">
                                        Rp {{ formatPrice(form.price || 0) }}
                                    </div>

                                    <ul class="space-y-3 mb-6">
                                        <li class="flex items-center gap-3 text-sm text-gray-700">
                                            <div class="p-1 rounded-full bg-blue-50 text-blue-500"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
                                            <span class="font-bold">{{ form.speed_mbps || 0 }} Mbps</span>
                                        </li>
                                        <li class="flex items-center gap-3 text-sm text-gray-700">
                                            <div class="p-1 rounded-full bg-orange-50 text-orange-500"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>
                                            <span>Maks. <strong>{{ form.shared_device }}</strong> Perangkat</span>
                                        </li>
                                        <li v-if="form.rate_limit" class="flex items-center gap-3 text-sm text-gray-700">
                                            <div class="p-1 rounded-full bg-purple-50 text-purple-500"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg></div>
                                            <span class="font-mono text-xs">{{ form.rate_limit }}</span>
                                        </li>
                                    </ul>

                                    <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                        <div class="text-[10px] font-bold text-gray-400">STATUS</div>
                                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded text-[10px] font-bold" 
                                              :class="form.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="form.is_active ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                            {{ form.is_active ? 'ACTIVE' : 'INACTIVE' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex-shrink-0 bg-white p-4 sm:px-6 border-t shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex justify-between items-center z-10">
                    <div class="hidden sm:block text-xs text-gray-500 font-medium">
                        Pastikan data diisi dengan benar sebelum menyimpan.
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <button type="button" @click="closeModal" class="flex-1 sm:flex-none px-6 py-2.5 border border-gray-300 text-gray-700 font-bold text-sm rounded-lg hover:bg-gray-50 transition-colors">Batal</button>
                        <button type="button" @click="openSummary" class="flex-1 sm:flex-none px-6 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-all"
                                :disabled="!isFormValid || marginBersih < 0">
                            {{ editMode ? 'Lanjutkan Perubahan' : 'Lanjutkan Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- SUMMARY CONFIRMATION MODAL -->
        <div v-if="showSummaryModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/70 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-white font-bold text-lg">Ringkasan Paket</h3>
                    <button @click="showSummaryModal = false" class="text-indigo-200 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-3">
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-sm text-gray-500">Nama Paket</span>
                        <span class="text-sm font-bold text-gray-900">{{ form.name }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-sm text-gray-500">Type Akses</span>
                        <span class="text-sm font-bold text-gray-900 uppercase">{{ form.access_mode }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-sm text-gray-500">Masa Aktif</span>
                        <span class="text-sm font-bold text-gray-900">{{ form.active_period }} {{ form.active_period_unit }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-sm text-gray-500">Shared Device</span>
                        <span class="text-sm font-bold text-gray-900">{{ form.shared_device }} Perangkat</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-sm text-gray-500">Rate Limit</span>
                        <span class="text-sm font-bold text-gray-900 font-mono">{{ form.rate_limit || 'Default' }}</span>
                    </div>
                    <div class="flex justify-between pt-2">
                        <span class="text-sm font-bold text-gray-700">Harga Jual</span>
                        <span class="text-sm font-black text-gray-900">Rp {{ formatPrice(currentActivePrice) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-bold text-gray-700">Margin Bersih</span>
                        <span class="text-sm font-black text-emerald-600">Rp {{ formatPrice(marginBersih) }}</span>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex gap-3">
                    <button type="button" @click="showSummaryModal = false" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-bold hover:bg-white">Kembali</button>
                    <button type="button" @click="submitForm" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 flex items-center justify-center gap-2" :disabled="form.processing">
                        <span v-if="form.processing">Menyimpan...</span>
                        <span v-else>Konfirmasi Simpan</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- RATE LIMIT GENERATOR DIALOG -->
        <div v-if="showRateLimitGen" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm overflow-y-auto">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-indigo-100 my-8">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <h4 class="font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Advanced Rate Limit Generator
                    </h4>
                    <button @click="showRateLimitGen = false" class="text-indigo-100 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-6">
                    <!-- Basic Limit -->
                    <div class="mb-6">
                        <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span class="w-5 h-px bg-gray-300"></span> Max Limit (Dasar)
                        </h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Max Download (Tx)</label>
                                <div class="relative">
                                    <input v-model="rlGen.maxDl" type="text" placeholder="Contoh: 10M" class="input-text w-full pl-9 font-mono">
                                    <svg class="w-4 h-4 text-emerald-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Max Upload (Rx)</label>
                                <div class="relative">
                                    <input v-model="rlGen.maxUl" type="text" placeholder="Contoh: 5M" class="input-text w-full pl-9 font-mono">
                                    <svg class="w-4 h-4 text-blue-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Burst Config -->
                    <div class="mb-6 bg-slate-50 border border-slate-200 rounded-xl p-4 transition-colors hover:border-indigo-200">
                        <div class="flex items-center justify-between mb-4">
                            <h5 class="text-xs font-bold text-indigo-700 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Konfigurasi Burst (Opsional)
                            </h5>
                            <label class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="rlGen.useBurst" class="sr-only">
                                    <div class="block w-10 h-6 rounded-full transition-colors" :class="rlGen.useBurst ? 'bg-indigo-500' : 'bg-gray-300'"></div>
                                    <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="rlGen.useBurst ? 'transform translate-x-4' : ''"></div>
                                </div>
                            </label>
                        </div>

                        <div v-if="rlGen.useBurst" class="space-y-4 animate-fadeIn">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Burst Limit DL/UL</label>
                                    <div class="flex gap-2">
                                        <input v-model="rlGen.burstDl" type="text" placeholder="DL (15M)" class="input-text w-full font-mono text-xs">
                                        <input v-model="rlGen.burstUl" type="text" placeholder="UL (8M)" class="input-text w-full font-mono text-xs">
                                    </div>
                                    <p class="text-[9px] text-gray-500 mt-1 leading-tight">Kecepatan maksimal saat burst aktif</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Burst Threshold DL/UL</label>
                                    <div class="flex gap-2">
                                        <input v-model="rlGen.threshDl" type="text" placeholder="DL (8M)" class="input-text w-full font-mono text-xs">
                                        <input v-model="rlGen.threshUl" type="text" placeholder="UL (4M)" class="input-text w-full font-mono text-xs">
                                    </div>
                                    <p class="text-[9px] text-gray-500 mt-1 leading-tight">Batas rata-rata untuk memicu burst</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Burst Time DL/UL (detik)</label>
                                    <div class="flex gap-2">
                                        <input v-model="rlGen.timeDl" type="number" placeholder="DL (16)" class="input-text w-full font-mono text-xs">
                                        <input v-model="rlGen.timeUl" type="number" placeholder="UL (16)" class="input-text w-full font-mono text-xs">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Prioritas (1-8)</label>
                                    <input v-model="rlGen.priority" type="number" min="1" max="8" class="input-text w-full font-mono text-xs">
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-xs text-gray-400 italic">Burst tidak diaktifkan.</div>
                    </div>

                    <!-- Limit At Config -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-5 h-px bg-gray-300"></span> Limit At / GARANSI MINIMAL
                            </h5>
                            <label class="flex items-center cursor-pointer">
                                <div class="relative">
                                    <input type="checkbox" v-model="rlGen.useLimitAt" class="sr-only">
                                    <div class="block w-8 h-5 rounded-full transition-colors" :class="rlGen.useLimitAt ? 'bg-emerald-500' : 'bg-gray-300'"></div>
                                    <div class="dot absolute left-1 top-0.5 bg-white w-4 h-4 rounded-full transition-transform" :class="rlGen.useLimitAt ? 'transform translate-x-3' : ''"></div>
                                </div>
                            </label>
                        </div>
                        
                        <div v-if="rlGen.useLimitAt" class="grid grid-cols-2 gap-4 animate-fadeIn bg-emerald-50/50 p-3 border border-emerald-100 rounded-lg">
                            <div>
                                <label class="block text-[10px] font-bold text-emerald-700 mb-1">Min. Download (Limit At)</label>
                                <input v-model="rlGen.limitAtDl" type="text" placeholder="Contoh: 2M" class="input-text w-full text-xs font-mono border-emerald-200 focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-emerald-700 mb-1">Min. Upload (Limit At)</label>
                                <input v-model="rlGen.limitAtUl" type="text" placeholder="Contoh: 1M" class="input-text w-full text-xs font-mono border-emerald-200 focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-900 rounded-xl border border-gray-800 relative overflow-hidden group shadow-inner">
                        <div class="relative z-10">
                            <div class="text-[10px] text-gray-400 mb-1 font-bold tracking-wider">PREVIEW MIKROTIK RULE:</div>
                            <div class="font-mono text-lg font-bold text-green-400 break-all">{{ generatedRateLimit }}</div>
                        </div>
                        <button @click="copyText(generatedRateLimit)" class="absolute top-1/2 -translate-y-1/2 right-4 p-2 bg-gray-700/80 hover:bg-gray-600 text-gray-200 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity z-20" title="Copy">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-6 pt-0 flex gap-3 mt-4 border-t border-gray-100 pt-5">
                    <button type="button" @click="showRateLimitGen = false" class="flex-1 py-2.5 rounded-lg text-sm font-bold border border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors">Batal</button>
                    <button type="button" @click="applyRateLimit" class="flex-[2] py-2.5 rounded-lg text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all">Gunakan Konfigurasi Ini</button>
                </div>
            </div>
        </div>
        
        <!-- Toast Notification -->
        <div v-if="toastMsg" class="fixed bottom-4 right-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-bold flex items-center gap-2 z-[100] transition-opacity">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ toastMsg }}
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    packages: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const doSearch = () => {
    router.get(route('internet-packages.index'), { search: search.value }, { preserveState: true, replace: true });
};

const formatPrice = (price) => {
    return parseFloat(price || 0).toLocaleString('id-ID');
};

const getAccessModeClass = (mode) => {
    switch(mode) {
        case 'hotspot': return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'pppoe': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'voucher': return 'bg-teal-50 text-teal-700 border-teal-200';
        case 'static_ip': return 'bg-sky-50 text-sky-700 border-sky-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
};

// Toast
const toastMsg = ref('');
const showToast = (msg) => {
    toastMsg.value = msg;
    setTimeout(() => toastMsg.value = '', 2000);
};

const copyText = (text) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    showToast('Teks disalin!');
};

// Generator Logic
const showRateLimitGen = ref(false);
const rlGen = ref({ 
    maxDl: '10M', maxUl: '5M',
    useBurst: false, burstDl: '15M', burstUl: '8M', threshDl: '8M', threshUl: '4M', timeDl: 16, timeUl: 16, priority: 8,
    useLimitAt: false, limitAtDl: '2M', limitAtUl: '1M'
});

const generatedRateLimit = computed(() => {
    const rx = rlGen.value.maxUl || '0';
    const tx = rlGen.value.maxDl || '0';
    
    // Default format Rx/Tx
    let rate = `${rx}/${tx}`;
    
    // Burst
    if (rlGen.value.useBurst) {
        const brx = rlGen.value.burstUl || '0';
        const btx = rlGen.value.burstDl || '0';
        
        const thrx = rlGen.value.threshUl || '0';
        const thtx = rlGen.value.threshDl || '0';
        
        const trx = rlGen.value.timeUl || '0';
        const ttx = rlGen.value.timeDl || '0';
        
        rate += ` ${brx}/${btx} ${thrx}/${thtx} ${trx}/${ttx}`;
    } else {
        rate += ` 0/0 0/0 0/0`;
    }
    
    // Priority
    const prio = rlGen.value.priority || 8;
    rate += ` ${prio}`;
    
    // Limit At
    if (rlGen.value.useLimitAt) {
        const minrx = rlGen.value.limitAtUl || '0';
        const mintx = rlGen.value.limitAtDl || '0';
        rate += ` ${minrx}/${mintx}`;
    } else {
        rate += ` 0/0`;
    }
    
    return rate;
});

const openRateLimitGen = () => {
    if (form.rate_limit) {
        const parts = form.rate_limit.split(' ');
        if (parts[0]) {
            const rxTx = parts[0].split('/');
            if (rxTx.length === 2) {
                rlGen.value.maxUl = rxTx[0];
                rlGen.value.maxDl = rxTx[1];
            }
        }
        if (parts[1] && parts[1] !== '0/0') {
            rlGen.value.useBurst = true;
            const b = parts[1].split('/'); if (b.length == 2) { rlGen.value.burstUl = b[0]; rlGen.value.burstDl = b[1]; }
            if (parts[2]) { const th = parts[2].split('/'); if (th.length == 2) { rlGen.value.threshUl = th[0]; rlGen.value.threshDl = th[1]; } }
            if (parts[3]) { const tm = parts[3].split('/'); if (tm.length == 2) { rlGen.value.timeUl = tm[0]; rlGen.value.timeDl = tm[1]; } }
        } else {
            rlGen.value.useBurst = false;
        }
        if (parts[4]) rlGen.value.priority = parseInt(parts[4]) || 8;
        if (parts[5] && parts[5] !== '0/0') {
            rlGen.value.useLimitAt = true;
            const l = parts[5].split('/'); if (l.length == 2) { rlGen.value.limitAtUl = l[0]; rlGen.value.limitAtDl = l[1]; }
        } else {
            rlGen.value.useLimitAt = false;
        }
    } else {
        rlGen.value.maxDl = form.speed_mbps ? `${form.speed_mbps}M` : '10M';
        rlGen.value.maxUl = form.speed_mbps ? `${Math.floor(form.speed_mbps/2)}M` : '5M';
        rlGen.value.useBurst = false;
        rlGen.value.useLimitAt = false;
        rlGen.value.priority = 8;
    }
    showRateLimitGen.value = true;
};

const applyRateLimit = () => {
    form.rate_limit = generatedRateLimit.value;
    showRateLimitGen.value = false;
};


// Form Logic
const showModal = ref(false);
const showSummaryModal = ref(false);
const editMode = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    access_mode: 'pppoe',
    speed_mbps: '',
    color: '#4f46e5',
    description: '',
    price: '',
    is_promo: false,
    promo_price: '',
    fee_admin: 0,
    fee_reseller: 0,
    fee_partner: 0,
    is_mikrotik_group_custom: false,
    mikrotik_group: '',
    is_mikrotik_address_list_custom: false,
    mikrotik_address_list: '',
    shared_device: 1,
    rate_limit: '',
    active_period: 1,
    active_period_unit: 'Bulan',
    is_active: true
});

const currentActivePrice = computed(() => {
    return form.is_promo ? (form.promo_price || 0) : (form.price || 0);
});

const marginBersih = computed(() => {
    const sellPrice = parseFloat(currentActivePrice.value) || 0;
    const admin = parseFloat(form.fee_admin) || 0;
    const res = parseFloat(form.fee_reseller) || 0;
    const part = parseFloat(form.fee_partner) || 0;
    return sellPrice - (admin + res + part);
});

const isFormValid = computed(() => {
    if (!form.name || !form.speed_mbps || !form.price) return false;
    if (form.is_promo && !form.promo_price) return false;
    return true;
});

const openCreateModal = () => {
    editMode.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (pkg) => {
    editMode.value = true;
    editingId.value = pkg.id;
    
    // Assign fields
    Object.keys(form.data()).forEach(key => {
        if (pkg.hasOwnProperty(key)) {
            form[key] = pkg[key];
        }
    });
    
    // Handle booleans that might come as 1/0
    form.is_promo = !!pkg.is_promo;
    form.is_mikrotik_group_custom = !!pkg.is_mikrotik_group_custom;
    form.is_mikrotik_address_list_custom = !!pkg.is_mikrotik_address_list_custom;
    form.is_active = !!pkg.is_active;
    
    // Set defaults if null
    if (!form.color) form.color = '#4f46e5';
    if (!form.shared_device) form.shared_device = 1;
    if (!form.active_period_unit) form.active_period_unit = 'Bulan';
    
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => form.reset(), 300);
};

const openSummary = () => {
    // Trigger HTML5 validation first
    const formEl = document.getElementById('packageForm');
    if (formEl && !formEl.checkValidity()) {
        formEl.reportValidity();
        return;
    }
    showSummaryModal.value = true;
};

const submitForm = () => {
    if (editMode.value) {
        form.post(route('internet-packages.update.post', editingId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showSummaryModal.value = false;
                closeModal();
            }
        });
    } else {
        form.post(route('internet-packages.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showSummaryModal.value = false;
                closeModal();
            }
        });
    }
};

const deletePackage = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus paket ini?')) {
        router.post(route('internet-packages.destroy.post', id));
    }
};
</script>

<style scoped>
.input-text {
    @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white;
}
.input-text:focus {
    @apply outline-none ring-2 ring-opacity-20;
}
/* Toggle Switch specific styling */
.dot {
    transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
}
</style>
