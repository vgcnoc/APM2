<template>
    <component :is="layoutComponent" title="Data Voucher" subtitle="Kelola dan generate voucher hotspot">
        <div v-if="layoutComponent === ClientAreaLayout" class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Data Voucher</h1>
            <p class="text-sm text-gray-500">Kelola dan lihat semua voucher Anda</p>
        </div>
        <!-- EXECUTIVE KPI CARDS -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4 mb-6">
            <!-- Card 1: Total Voucher -->
            <div 
                @click="filterByStatus('')" 
                :class="statusFilter === '' && !search && !resellerFilter && !areaFilter ? 'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50/20' : 'border-gray-200 bg-white hover:border-indigo-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Voucher</span>
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-gray-900 tracking-tight">
                        {{ Number(stats?.total || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1.5 font-medium">
                        <span>Semua data voucher</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Voucher Tersedia -->
            <div 
                @click="filterByStatus('available')" 
                :class="statusFilter === 'available' ? 'ring-2 ring-blue-500 border-blue-500 bg-blue-50/25' : 'border-gray-200 bg-white hover:border-blue-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">Tersedia</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-blue-900 tracking-tight">
                        {{ Number(stats?.available || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-blue-600 font-semibold mt-0.5 truncate" :title="'Potensi Rp ' + Number(stats?.nominal_available || 0).toLocaleString('id-ID')">
                        Potensi Rp {{ Number(stats?.nominal_available || 0).toLocaleString('id-ID') }}
                    </div>
                </div>
            </div>

            <!-- Card 3: Voucher Digunakan -->
            <div 
                @click="filterByStatus('used')" 
                :class="statusFilter === 'used' ? 'ring-2 ring-emerald-500 border-emerald-500 bg-emerald-50/25' : 'border-gray-200 bg-white hover:border-emerald-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Digunakan</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-emerald-900 tracking-tight">
                        {{ Number(stats?.used || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] font-semibold mt-1 flex items-center gap-1.5 flex-wrap">
                        <button 
                            type="button"
                            @click.stop="filterByStatus('online')"
                            :class="statusFilter === 'online' ? 'bg-emerald-600 text-white shadow-2xs font-bold' : 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'"
                            class="px-2 py-0.5 rounded-md flex items-center gap-1 text-[10px] transition-all cursor-pointer"
                            title="Filter Hanya Voucher Online"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'online' ? 'bg-white' : 'bg-emerald-500 animate-pulse'"></span>
                            {{ Number(stats?.online || 0) }} Online
                        </button>
                        <button 
                            type="button"
                            @click.stop="filterByStatus('offline')"
                            :class="statusFilter === 'offline' ? 'bg-rose-600 text-white shadow-2xs font-bold' : 'bg-rose-100 text-rose-800 hover:bg-rose-200'"
                            class="px-2 py-0.5 rounded-md flex items-center gap-1 text-[10px] transition-all cursor-pointer"
                            title="Filter Hanya Voucher Offline"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="statusFilter === 'offline' ? 'bg-white' : 'bg-rose-500'"></span>
                            {{ Number(stats?.offline || 0) }} Offline
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card 4: Voucher Expired -->
            <div 
                @click="filterByStatus('expired')" 
                :class="statusFilter === 'expired' ? 'ring-2 ring-rose-500 border-rose-500 bg-rose-50/25' : 'border-gray-200 bg-white hover:border-rose-300 hover:shadow-md'"
                class="border rounded-2xl p-4 shadow-xs transition-all duration-200 cursor-pointer relative overflow-hidden group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-rose-700 uppercase tracking-wider">Expired</span>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-rose-900 tracking-tight">
                        {{ Number(stats?.expired || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-rose-600 font-medium mt-0.5">
                        Masa aktif habis
                    </div>
                </div>
            </div>

            <!-- Card 5: Omset Terpakai -->
            <div 
                class="border border-purple-200 bg-gradient-to-br from-purple-50/60 via-white to-indigo-50/40 rounded-2xl p-4 shadow-xs transition-all duration-200 relative overflow-hidden group flex flex-col justify-between col-span-2 sm:col-span-1 lg:col-span-1"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-purple-700 uppercase tracking-wider">Omset Terpakai</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="text-lg sm:text-xl font-black text-purple-950 tracking-tight truncate" :title="'Rp ' + Number(stats?.nominal_used || 0).toLocaleString('id-ID')">
                        Rp {{ Number(stats?.nominal_used || 0).toLocaleString('id-ID') }}
                    </div>
                    <div class="text-[11px] text-purple-700 font-semibold mt-0.5">
                        Voucher berbayar terjual
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTER BAR & ACTIONS -->
        <div class="mb-6 flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <!-- Search Input -->
                <div class="relative flex-1 min-w-[200px]">
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Cari kode/username/nama pembeli/no hp/reseller..." 
                        class="w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-2xs"
                        @keyup.enter="doFilter"
                    >
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Status Filter -->
                <select v-model="statusFilter" @change="doFilter" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="available">Tersedia</option>
                    <option value="used">Digunakan</option>
                    <option v-if="can('voucher_free_filter_connection')" value="online">🟢 Sesi Online</option>
                    <option v-if="can('voucher_free_filter_connection')" value="offline">🔴 Sesi Offline</option>
                    <option value="expired">Expired</option>
                </select>

                <!-- Reseller Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="resellerFilter" @change="doFilter" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Pemilik / Reseller</option>
                    <option value="system">🏢 Kantor / System</option>
                    <option v-for="res in resellers" :key="res.id" :value="res.id">
                        👤 {{ res.customer?.name || ('Reseller #' + res.id) }}
                    </option>
                </select>

                <!-- Area / Wilayah Filter -->
                <select v-if="layoutComponent === AppLayout" v-model="areaFilter" @change="doFilter" class="bg-white border-gray-300 rounded-xl text-sm shadow-2xs focus:ring-indigo-500 focus:border-indigo-500 max-w-[220px]">
                    <option value="">Semua Area / Wilayah</option>
                    <option v-for="area in areas" :key="area.id" :value="area.id">
                        📍 {{ area.name }}
                    </option>
                </select>

                <!-- Reset Button -->
                <button 
                    v-if="search || statusFilter || resellerFilter || areaFilter" 
                    @click="resetFilter" 
                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-2xs"
                    title="Reset Filter"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Reset</span>
                </button>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                <button v-if="selectedVouchers.length > 0" @click="printSelected" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 shadow-sm flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print ({{ selectedVouchers.length }})
                </button>
                <button v-if="selectedVouchers.length > 0" @click="bulkDelete" class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 shadow-sm flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus ({{ selectedVouchers.length }})
                </button>
                <button @click="openGenerateModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center gap-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Generate Voucher
                </button>
            </div>
        </div>

        <!-- VISUAL COLOR INDICATOR LEGEND -->
        <div v-if="can('voucher_free_color_indicator')" class="flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 bg-gray-50/90 rounded-xl border border-gray-200 text-xs shadow-2xs mb-4">
            <div class="flex items-center gap-3 flex-wrap">
                <span class="font-bold text-gray-700 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    Indikator Barisan:
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold text-[11px]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Barisan Hijau = Sesi Online ({{ stats?.online || 0 }})
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md bg-rose-100 text-rose-900 border border-rose-300 font-bold text-[11px]">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Barisan Merah = Sesi Offline ({{ stats?.offline || 0 }})
                </span>
            </div>
            <div class="text-[11px] text-gray-500">
                Status sesi voucher real-time FreeRADIUS
            </div>
        </div>

        <!-- TABLE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-10">
                                <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode / User</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Password</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Profil</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pemilik / Area</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Terpakai Sejak</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aktif Sampai</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Penggunaan</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr 
                            v-for="voucher in vouchers.data" 
                            :key="voucher.id" 
                            :class="[
                                can('voucher_free_color_indicator') 
                                    ? (onlineUsernames.includes(voucher.username) 
                                        ? 'bg-emerald-50/80 hover:bg-emerald-100/90 border-l-4 border-l-emerald-500' 
                                        : 'bg-rose-50/75 hover:bg-rose-100/80 border-l-4 border-l-rose-500')
                                    : 'hover:bg-gray-50'
                            ]"
                            class="transition-colors"
                        >
                            <td class="px-6 py-4">
                                <input type="checkbox" v-model="selectedVouchers" :value="voucher.id" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900 font-mono">{{ voucher.code }}</div>
                                <div class="text-xs text-gray-500" v-if="voucher.username !== voucher.code">User: {{ voucher.username }}</div>
                                
                                <!-- Nama Pembeli & No HP -->
                                <div v-if="voucher.buyer_name" class="mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                                        👤 {{ voucher.buyer_name }}
                                    </span>
                                    <span v-if="voucher.buyer_phone" class="text-[10px] text-gray-500 font-mono">({{ voucher.buyer_phone }})</span>
                                </div>

                                <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                    <span v-if="voucher.is_free" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        🎁 Gratis
                                    </span>
                                    <span v-else class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                        💰 Berbayar
                                    </span>
                                    <span v-if="voucher.payment_method === 'cash'" class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200" title="Bayar tunai di kantor / non-saldo">
                                        💵 Tunai/Kantor
                                    </span>
                                    <span v-else-if="voucher.payment_method === 'balance'" class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-50 text-purple-700 border border-purple-200" title="Potong saldo reseller">
                                        💳 Saldo
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                {{ voucher.password }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div class="font-bold text-gray-900">{{ voucher.profile?.name }}</div>
                                <div v-if="voucher.profile?.duration" class="text-xs text-gray-500 font-medium">{{ voucher.profile.duration }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div v-if="voucher.reseller?.customer?.name" class="flex flex-col items-start gap-1">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="font-bold">{{ voucher.reseller.customer.name }}</span>
                                    </div>
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <span v-if="voucher.reseller?.customer?.area_model?.name || voucher.reseller?.customer?.areaModel?.name || voucher.reseller?.customer?.area" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                            📍 {{ voucher.reseller?.customer?.area_model?.name || voucher.reseller?.customer?.areaModel?.name || voucher.reseller?.customer?.area }}
                                        </span>
                                        <span v-if="voucher.payment_method === 'cash'" class="text-[10px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200" title="Bayar Tunai di Kantor">
                                            💵 Tunai (Kantor)
                                        </span>
                                        <span v-else-if="voucher.payment_method === 'balance'" class="text-[10px] font-medium text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-200" title="Potong Saldo Reseller">
                                            💳 Saldo Reseller
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="flex flex-col items-start gap-1">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span>Kantor / System</span>
                                    </div>
                                    <span v-if="voucher.customer?.area_model?.name || voucher.customer?.areaModel?.name || voucher.customer?.area" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                        📍 {{ voucher.customer?.area_model?.name || voucher.customer?.areaModel?.name || voucher.customer?.area }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="voucher.status === 'available'" class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">TERSEDIA</span>
                                <span v-else-if="voucher.status === 'used'" class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">DIGUNAKAN</span>
                                <span v-else class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">EXPIRED</span>

                                <div class="mt-2 flex flex-col gap-1 items-start">
                                    <span v-if="!voucher.is_active" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold bg-rose-100 text-rose-700 uppercase tracking-widest">
                                        Nonaktif
                                    </span>
                                    <span v-else-if="onlineUsernames.includes(voucher.username)" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-full border border-emerald-300 shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Online
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-800 bg-rose-100 px-2.5 py-0.5 rounded-full border border-rose-300 shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        Offline
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.first_login">
                                    {{ new Date(usageStats[voucher.username].first_login).toLocaleString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.first_login && voucher.profile?.duration">
                                    {{ calculateExpiration(usageStats[voucher.username].first_login, voucher.profile.duration) }}
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div v-if="usageStats[voucher.username]?.total_time !== undefined">
                                    <span :class="usageStats[voucher.username].total_time > 0 ? 'text-indigo-600 font-bold' : ''">
                                        {{ formatSeconds(usageStats[voucher.username].total_time) }} 
                                    </span>
                                    <span class="text-gray-400 text-xs ml-1" v-if="voucher.profile?.duration">
                                        / {{ voucher.profile.duration }}
                                    </span>
                                </div>
                                <div v-else class="text-gray-400">-</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Tombol Kirim WA & Beri Nama Pembeli -->
                                    <button 
                                        type="button"
                                        @click="openBuyerModal(voucher)" 
                                        :class="voucher.buyer_name ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border-emerald-300' : 'text-gray-700 bg-gray-50 hover:bg-gray-100 border-gray-300'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border shadow-2xs transition-all cursor-pointer"
                                        title="Kirim ke WhatsApp & Beri Nama Pembeli"
                                    >
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <span>{{ voucher.buyer_name ? 'Kirim WA' : '+ Nama/WA' }}</span>
                                    </button>
                                    <button 
                                        @click="toggleStatus(voucher)" 
                                        :disabled="toggling === voucher.id"
                                        :class="voucher.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-emerald-600 hover:text-emerald-900'"
                                        class="font-semibold transition-colors disabled:opacity-50 text-xs px-2 py-1"
                                    >
                                        {{ voucher.is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                    <button @click="deleteVoucher(voucher.id)" class="text-red-600 hover:text-red-900 font-semibold text-xs px-2 py-1">Hapus</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="vouchers.data.length === 0">
                            <td colspan="10" class="px-6 py-12 text-center">
                                <p class="text-gray-500 text-base font-medium">Belum ada data voucher.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 flex justify-center gap-2" v-if="vouchers.links && vouchers.links.length > 3">
                <template v-for="(link, idx) in vouchers.links" :key="idx">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1 border rounded text-sm" :class="link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"></Link>
                    <span v-else v-html="link.label" class="px-3 py-1 border rounded bg-gray-100 text-gray-400 border-gray-300 cursor-not-allowed text-sm"></span>
                </template>
            </div>
        </div>

        <!-- MODAL GENERATE VOUCHER (MODERN DUAL-TAB) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/80 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="relative bg-[#111327] border border-indigo-900/80 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden animate-fade-in-up text-gray-200">
                    
                    <!-- MODAL HEADER -->
                    <div class="px-6 py-4 bg-[#111327] border-b border-indigo-950 flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-[#1e213d] flex items-center justify-center border border-indigo-500/20 text-indigo-300 shadow-sm shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-bold tracking-tight text-white">Generator Voucher Hotspot</h2>
                                    <span class="px-2.5 py-0.5 text-[10px] font-semibold bg-indigo-500/20 text-indigo-300 rounded-full border border-indigo-400/30">Mikrotik / RADIUS</span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">Buat voucher kustom satuan atau cetak voucher massal otomatis</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="w-9 h-9 rounded-xl bg-[#1e213d] hover:bg-[#282c50] text-gray-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- TAB SELECTOR -->
                    <div class="bg-[#15172e] border-b border-indigo-950 px-6 pt-3 flex gap-2 shrink-0">
                        <button 
                            type="button" 
                            @click="activeTab = 'single'"
                            :class="activeTab === 'single' ? 'bg-[#222649] text-white border-b-2 border-indigo-500 shadow-sm font-bold' : 'text-gray-400 hover:text-gray-200 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl flex items-center gap-2 transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Single Voucher (Satuan)</span>
                            <span class="px-1.5 py-0.2 bg-indigo-900/60 text-indigo-300 border border-indigo-700/50 text-[10px] rounded-full">Kustom</span>
                        </button>
                        <button 
                            type="button" 
                            @click="activeTab = 'bulk'"
                            :class="activeTab === 'bulk' ? 'bg-[#222649] text-white border-b-2 border-indigo-500 shadow-sm font-bold' : 'text-gray-400 hover:text-gray-200 border-transparent font-medium'"
                            class="px-4 py-2.5 text-xs rounded-t-xl flex items-center gap-2 transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span>Generate Massal (Batch)</span>
                            <span class="px-1.5 py-0.2 bg-gray-800 text-gray-400 text-[10px] rounded-full">Otomatis</span>
                        </button>
                    </div>

                    <!-- MODAL BODY -->
                    <div class="p-6 overflow-y-auto space-y-5">
                        
                        <!-- ================= TAB 1: SINGLE VOUCHER ================= -->
                        <form v-if="activeTab === 'single'" @submit.prevent="submitSingleForm" class="space-y-5">
                            
                            <!-- 1. Pilih Profil Paket -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Pilih Profil Paket Hotspot <span class="text-red-500">*</span>
                                </label>
                                <select v-model="singleForm.voucher_profile_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all" required>
                                    <option value="" disabled>-- Pilih Profil Paket --</option>
                                    <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id">
                                        {{ prof.name }} • Durasi: {{ prof.duration || '-' }} • {{ isReseller ? 'Harga Reseller: Rp' + Number(getProfileResellerCost(prof)).toLocaleString('id-ID') : 'Tarif: Rp' + Number(prof.price).toLocaleString('id-ID') }}
                                    </option>
                                </select>
                            </div>

                            <!-- 2. Jenis Voucher (Berbayar vs Gratis - Khusus Admin/Kantor) -->
                            <div v-if="!isReseller">
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Jenis Tarif Voucher <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label 
                                        :class="singleForm.cost_type === 'paid' ? 'border-indigo-500 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                        class="border rounded-xl p-3 cursor-pointer flex items-center gap-3 transition-all"
                                    >
                                        <input type="radio" value="paid" v-model="singleForm.cost_type" class="text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <div class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                                <span>💰 Harus Bayar (Berbayar)</span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">Sesuai tarif profil</p>
                                        </div>
                                    </label>

                                    <label 
                                        :class="singleForm.cost_type === 'free' ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                        class="border rounded-xl p-3 cursor-pointer flex items-center gap-3 transition-all"
                                    >
                                        <input type="radio" value="free" v-model="singleForm.cost_type" class="text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <div class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
                                                <span>🎁 Voucher Gratis (Free)</span>
                                            </div>
                                            <p class="text-[11px] text-gray-500 mt-0.5">Rp 0 / Bebas Biaya</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- 3. Tipe Kredensial Login -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Format Kredensial Login <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_only'"
                                        :class="singleForm.login_type === 'username_only' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium'"
                                        class="px-3 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Hanya Username</span>
                                    </button>
                                    <button 
                                        type="button" 
                                        @click="singleForm.login_type = 'username_password'"
                                        :class="singleForm.login_type === 'username_password' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium'"
                                        class="px-3 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        <span>Username & Password</span>
                                    </button>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1" v-if="singleForm.login_type === 'username_only'">
                                    ℹ️ Mode Satu Kode: Pengguna login cukup mengetik Username (Password otomatis sama dengan Username).
                                </p>
                            </div>

                            <!-- 4. Input Username & Password (Ketik Manual / Tombol Acak) -->
                            <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200 space-y-4">
                                <!-- Username -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-700">Username / Kode Login <span class="text-red-500">*</span></label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomUsername"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Acak Username
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.username" 
                                            type="text" 
                                            placeholder="Ketik username manual (contoh: user01 / KODEX)" 
                                            class="w-full font-mono text-sm border-gray-300 rounded-xl uppercase pr-10 focus:ring-indigo-500 focus:border-indigo-500" 
                                            required
                                        />
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        </div>
                                    </div>
                                    <div v-if="singleForm.errors.username" class="text-red-500 text-xs mt-1">{{ singleForm.errors.username }}</div>
                                </div>

                                <!-- Password (hanya jika mode username_password) -->
                                <div v-if="singleForm.login_type === 'username_password'">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-gray-700">Password <span class="text-red-500">*</span></label>
                                        <button 
                                            type="button" 
                                            @click="generateRandomPassword"
                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Acak Password
                                        </button>
                                    </div>
                                    <div class="relative">
                                        <input 
                                            v-model="singleForm.password" 
                                            :type="showSinglePassword ? 'text' : 'password'" 
                                            placeholder="Ketik password manual..." 
                                            class="w-full font-mono text-sm border-gray-300 rounded-xl pr-10 focus:ring-indigo-500 focus:border-indigo-500" 
                                            required
                                        />
                                        <button 
                                            type="button" 
                                            @click="showSinglePassword = !showSinglePassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                        >
                                            <svg v-if="!showSinglePassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                        </button>
                                    </div>
                                    <div v-if="singleForm.errors.password" class="text-red-500 text-xs mt-1">{{ singleForm.errors.password }}</div>
                                </div>
                            </div>

                            <!-- Input Nama Pembeli & WhatsApp (Opsional) -->
                            <div class="bg-emerald-50/50 p-3.5 rounded-xl border border-emerald-200 space-y-3">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-900">
                                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                    <span>Data Pembeli (Opsional — Mudah dicari saat lupa kode & kirim WA)</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Pembeli</label>
                                        <input 
                                            v-model="singleForm.buyer_name" 
                                            type="text" 
                                            placeholder="Contoh: Budi Santoso" 
                                            class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" 
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">No. WhatsApp</label>
                                        <input 
                                            v-model="singleForm.buyer_phone" 
                                            type="text" 
                                            placeholder="Contoh: 081234567890" 
                                            class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" 
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Kepemilikan Reseller & Opsi Pembayaran (KOTAK MERAH 2: Khusus Admin/Kantor) -->
                            <div v-if="!isReseller" class="bg-indigo-50/40 p-4 rounded-xl border border-indigo-100 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                        Peruntukan Reseller (Opsional)
                                    </label>
                                    <select v-model="singleForm.reseller_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">-- System / Kantor (Bukan Reseller) --</option>
                                        <option v-for="reseller in resellers" :key="reseller.id" :value="reseller.id">
                                            {{ reseller.customer?.name || ('Reseller #' + reseller.id) }} (Saldo: Rp{{ Number(reseller.balance).toLocaleString('id-ID') }})
                                        </option>
                                    </select>
                                    <div v-if="singleForm.errors.reseller_id" class="text-red-500 text-xs mt-1">{{ singleForm.errors.reseller_id }}</div>
                                </div>

                                <!-- Opsi Pembayaran Reseller jika Berbayar -->
                                <div v-if="singleForm.reseller_id && singleForm.cost_type === 'paid'" class="pt-2 border-t border-indigo-100">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                        Metode Pembayaran Reseller:
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-indigo-200 cursor-pointer text-xs">
                                            <input type="radio" value="balance" v-model="singleForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-gray-800">💳 Potong Saldo</span>
                                                <p class="text-[10px] text-gray-500">Otomatis kurangi saldo</p>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-indigo-200 cursor-pointer text-xs">
                                            <input type="radio" value="cash" v-model="singleForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-emerald-700">💵 Bayar Tunai / Kantor</span>
                                                <p class="text-[10px] text-gray-500">Tanpa potong saldo</p>
                                            </div>
                                        </label>
                                    </div>
                                    <p v-if="singleForm.reseller_payment === 'cash'" class="text-[11px] text-emerald-700 font-medium mt-1.5 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Voucher dicetak atas nama reseller tanpa memotong saldo akunnya (cocok untuk reseller yang bayar cash di kantor).
                                    </p>
                                </div>
                            </div>
                            <!-- Info Akun Reseller (Tampil Khusus Login Reseller) -->
                            <div v-else class="bg-indigo-50/60 border border-indigo-200/80 rounded-xl p-3.5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                        💳
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Akun: {{ currentReseller?.customer?.name || $page.props.auth.user.name }}</p>
                                        <p class="text-[11px] text-indigo-700 font-medium">Biaya voucher otomatis memotong Saldo Reseller Anda</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] uppercase font-bold text-gray-500">Saldo Anda</span>
                                    <p class="text-sm font-extrabold text-indigo-700">Rp{{ Number(currentReseller?.balance || 0).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>

                            <!-- LIVE PREVIEW VOUCHER CARD -->
                            <div v-if="singleForm.username" class="p-3.5 bg-gradient-to-br from-slate-900 to-indigo-950 rounded-xl text-white shadow-md border border-indigo-800/50">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <span class="text-[10px] uppercase font-bold tracking-wider text-indigo-300">Live Preview Voucher</span>
                                    </div>
                                    <span v-if="singleForm.cost_type === 'free'" class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-500 text-white shadow-sm">
                                        GRATIS (Rp 0)
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-500 text-white shadow-sm">
                                        Rp {{ getSelectedProfilePrice(singleForm.voucher_profile_id) }}
                                    </span>
                                </div>
                                <div class="bg-white/10 backdrop-blur-sm p-3 rounded-lg border border-white/10 flex justify-between items-center">
                                    <div>
                                        <p class="text-xs text-indigo-200">Username: <span class="font-mono font-bold text-white text-sm">{{ singleForm.username }}</span></p>
                                        <p v-if="singleForm.login_type === 'username_password'" class="text-xs text-indigo-200">
                                            Password: <span class="font-mono font-bold text-amber-300">{{ singleForm.password || '-' }}</span>
                                        </p>
                                        <p v-else class="text-[11px] text-indigo-300/80 italic">User = Password</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-semibold text-white">{{ getSelectedProfileName(singleForm.voucher_profile_id) }}</p>
                                        <p class="text-[10px] text-indigo-300">{{ getSelectedProfileDuration(singleForm.voucher_profile_id) }}</p>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="hidden" id="submitSingleBtn"></button>
                        </form>

                        <!-- ================= TAB 2: BULK / MASSAL ================= -->
                        <form v-else @submit.prevent="submitBulkForm" class="space-y-4">
                            <!-- 1. Pilih Profil -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Pilih Profil <span class="text-red-500">*</span></label>
                                <select v-model="bulkForm.voucher_profile_id" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="" disabled>-- Pilih Profil --</option>
                                    <option v-for="prof in sellableProfiles" :key="prof.id" :value="prof.id">
                                        {{ prof.name }} ({{ isReseller ? 'Harga Reseller: Rp' + Number(getProfileResellerCost(prof)).toLocaleString('id-ID') : 'Rp' + Number(prof.price).toLocaleString('id-ID') }})
                                    </option>
                                </select>
                            </div>
                            
                            <!-- 2. Jenis Biaya Bulk (Khusus Admin/Kantor) -->
                            <div v-if="!isReseller" class="grid grid-cols-2 gap-3">
                                <label 
                                    :class="bulkForm.cost_type === 'paid' ? 'border-indigo-500 bg-indigo-50/50 ring-2 ring-indigo-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                    class="border rounded-xl p-2.5 cursor-pointer flex items-center gap-2.5 transition-all text-xs"
                                >
                                    <input type="radio" value="paid" v-model="bulkForm.cost_type" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-bold text-gray-900">💰 Harus Bayar (Normal)</span>
                                </label>
                                <label 
                                    :class="bulkForm.cost_type === 'free' ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                    class="border rounded-xl p-2.5 cursor-pointer flex items-center gap-2.5 transition-all text-xs"
                                >
                                    <input type="radio" value="free" v-model="bulkForm.cost_type" class="text-emerald-600 focus:ring-emerald-500">
                                    <span class="font-bold text-gray-900">🎁 Voucher Gratis (Rp 0)</span>
                                </label>
                            </div>

                            <!-- 3. Jumlah Generate & Quick Pick -->
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Jumlah Generate <span class="text-red-500">*</span></label>
                                    <div class="flex gap-1.5">
                                        <button type="button" @click="bulkForm.amount = 10" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">10</button>
                                        <button type="button" @click="bulkForm.amount = 25" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">25</button>
                                        <button type="button" @click="bulkForm.amount = 50" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">50</button>
                                        <button type="button" @click="bulkForm.amount = 100" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">100</button>
                                    </div>
                                </div>
                                <input v-model="bulkForm.amount" type="number" min="1" max="500" class="w-full border-gray-300 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>

                            <!-- 4. Panjang Karakter & Prefix -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Panjang Karakter <span class="text-red-500">*</span></label>
                                    <input v-model="bulkForm.length" type="number" min="4" max="12" class="w-full border-gray-300 rounded-xl text-sm" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Prefix (Opsional)</label>
                                    <input v-model="bulkForm.prefix" type="text" maxlength="4" placeholder="Contoh: VC" class="w-full border-gray-300 rounded-xl text-sm uppercase">
                                </div>
                            </div>

                            <!-- 5. Kombinasi Username & Tipe Login -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Kombinasi <span class="text-red-500">*</span></label>
                                    <select v-model="bulkForm.combination" class="w-full border-gray-300 rounded-xl text-sm" required>
                                        <option value="alphanumeric">Huruf & Angka (A1B2C)</option>
                                        <option value="numeric">Hanya Angka (12345)</option>
                                        <option value="alpha">Hanya Huruf (ABCDE)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tipe Login <span class="text-red-500">*</span></label>
                                    <select v-model="bulkForm.type" class="w-full border-gray-300 rounded-xl text-sm" required>
                                        <option value="vc">Satu Kode (User = Password)</option>
                                        <option value="up">Username & Password Berbeda</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- 6. Reseller Assignment -->
                            <div v-if="!isReseller" class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Generate Untuk Reseller (Opsional)</label>
                                    <select v-model="bulkForm.reseller_id" class="w-full border-gray-300 rounded-xl text-sm">
                                        <option value="">-- System (Tanpa Reseller) --</option>
                                        <option v-for="reseller in resellers" :key="reseller.id" :value="reseller.id">
                                            {{ reseller.customer?.name || ('Reseller #' + reseller.id) }} (Saldo: Rp{{ Number(reseller.balance).toLocaleString('id-ID') }})
                                        </option>
                                    </select>
                                    <div v-if="bulkForm.errors.reseller_id" class="text-red-500 text-xs mt-1">{{ bulkForm.errors.reseller_id }}</div>
                                </div>

                                <div v-if="bulkForm.reseller_id && bulkForm.cost_type === 'paid'" class="pt-2 border-t border-gray-200">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Metode Pembayaran Reseller:</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-gray-200 cursor-pointer text-xs">
                                            <input type="radio" value="balance" v-model="bulkForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-gray-800">💳 Potong Saldo</span>
                                            </div>
                                        </label>
                                        <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-gray-200 cursor-pointer text-xs">
                                            <input type="radio" value="cash" v-model="bulkForm.reseller_payment" class="text-indigo-600 focus:ring-indigo-500">
                                            <div>
                                                <span class="font-bold text-emerald-700">💵 Bayar Tunai di Kantor</span>
                                            </div>
                                        </label>
                                    </div>
                                    <p v-if="bulkForm.reseller_payment === 'cash'" class="text-[11px] text-emerald-700 font-medium mt-1">
                                        ✓ Saldo reseller tidak akan dipotong. Voucher tetap terhubung ke akun reseller ini.
                                    </p>
                                </div>
                            </div>
                            <!-- Info Akun Reseller jika Reseller -->
                            <div v-else class="bg-indigo-50/60 border border-indigo-200/80 rounded-xl p-3.5 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                        💳
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Akun: {{ currentReseller?.customer?.name || $page.props.auth.user.name }}</p>
                                        <p class="text-[11px] text-indigo-700 font-medium">Pembayaran batch otomatis memotong Saldo Reseller Anda</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] uppercase font-bold text-gray-500">Saldo Anda</span>
                                    <p class="text-sm font-extrabold text-indigo-700">Rp{{ Number(currentReseller?.balance || 0).toLocaleString('id-ID') }}</p>
                                </div>
                            </div>
                            
                            <button type="submit" class="hidden" id="submitBulkBtn"></button>
                        </form>

                    </div>

                    <!-- MODAL FOOTER -->
                    <div class="px-6 py-4 border-t border-indigo-950 bg-[#111327] flex justify-between items-center shrink-0">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 rounded-xl border border-indigo-900/80 text-xs font-bold text-gray-300 hover:bg-[#1f2244] hover:text-white transition-colors cursor-pointer">
                            Batal
                        </button>
                        <div class="flex items-center gap-2">
                            <button 
                                v-if="activeTab === 'single'" 
                                type="button" 
                                @click="$el.querySelector('#submitSingleBtn').click()" 
                                :disabled="singleForm.processing || !singleForm.username || !singleForm.voucher_profile_id" 
                                class="px-6 py-2.5 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:brightness-110 active:brightness-95 text-white rounded-xl text-xs font-bold shadow-[0_0_20px_rgba(99,102,241,0.5)] transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <svg v-if="singleForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ singleForm.processing ? 'Menyimpan...' : 'Buat 1 Voucher' }}</span>
                            </button>

                            <button 
                                v-else 
                                type="button" 
                                @click="$el.querySelector('#submitBulkBtn').click()" 
                                :disabled="bulkForm.processing || !bulkForm.voucher_profile_id" 
                                class="px-6 py-2.5 bg-gradient-to-r from-[#4f46e5] to-[#6366f1] hover:brightness-110 active:brightness-95 text-white rounded-xl text-xs font-bold shadow-[0_0_20px_rgba(99,102,241,0.5)] transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <svg v-if="bulkForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ bulkForm.processing ? 'Generating...' : `Generate ${bulkForm.amount} Voucher` }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </Teleport>

        <!-- MODAL BERI NAMA & KIRIM WHATSAPP -->
        <Teleport to="body">
            <div v-if="showBuyerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto">
                <div class="relative bg-white border border-gray-200 rounded-2xl shadow-2xl w-full max-w-lg max-h-[92vh] flex flex-col overflow-hidden animate-fade-in-up">
                    
                    <!-- HEADER -->
                    <div class="px-6 py-4 bg-gradient-to-r from-emerald-800 to-teal-900 text-white flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20 text-emerald-300">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-white flex items-center gap-2">
                                    Kirim Voucher via WhatsApp
                                </h2>
                                <p class="text-xs text-emerald-100">Beri nama pembeli & kirim rincian login ke WhatsApp</p>
                            </div>
                        </div>
                        <button @click="closeBuyerModal" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="p-6 overflow-y-auto space-y-4">
                        <!-- Info Voucher Card -->
                        <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Voucher Dipilih</span>
                                    <div class="text-lg font-mono font-extrabold text-gray-900 mt-0.5">{{ selectedVoucherForBuyer?.code }}</div>
                                    <div v-if="selectedVoucherForBuyer?.password && selectedVoucherForBuyer?.password !== selectedVoucherForBuyer?.code" class="text-xs text-gray-600 font-mono">
                                        Password: <strong class="text-indigo-600">{{ selectedVoucherForBuyer?.password }}</strong>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-100 text-indigo-700">
                                        {{ selectedVoucherForBuyer?.profile?.name || '-' }}
                                    </span>
                                    <div class="text-xs font-semibold text-gray-600 mt-1">
                                        {{ selectedVoucherForBuyer?.profile?.duration || '-' }}
                                    </div>
                                    <div class="text-xs font-bold text-emerald-600 mt-0.5">
                                        Rp {{ Number(selectedVoucherForBuyer?.profile?.price || 0).toLocaleString('id-ID') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Input Nama & WhatsApp -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Nama Pembeli <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model="buyerForm.buyer_name" 
                                    type="text" 
                                    placeholder="Contoh: Budi Santoso" 
                                    class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" 
                                    required
                                />
                                <p class="text-[11px] text-gray-400 mt-1">Jika pembeli lupa kodenya, Anda bisa mencari langsung dengan nama ini di kolom pencarian.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    No. WhatsApp Pembeli
                                </label>
                                <div class="relative">
                                    <input 
                                        v-model="buyerForm.buyer_phone" 
                                        type="text" 
                                        placeholder="Contoh: 081234567890 atau 6281234567890" 
                                        class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500" 
                                    />
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1">Jika diisi, WhatsApp akan otomatis terbuka langsung ke nomor pembeli tersebut.</p>
                            </div>
                        </div>

                        <!-- Preview Pesan WhatsApp -->
                        <div class="bg-emerald-50/60 rounded-xl p-3.5 border border-emerald-200">
                            <div class="flex justify-between items-center mb-1.5">
                                <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                    <span>💬 Preview Pesan WhatsApp</span>
                                </span>
                                <button 
                                    type="button" 
                                    @click="copyWaMessage" 
                                    class="text-[11px] font-bold text-emerald-700 hover:text-emerald-900 flex items-center gap-1 bg-white px-2 py-0.5 rounded border border-emerald-300 transition-colors cursor-pointer"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                                    <span>Salin Teks</span>
                                </button>
                            </div>
                            <pre class="text-[11px] text-gray-700 font-sans whitespace-pre-wrap bg-white/80 p-2.5 rounded-lg border border-emerald-100 max-h-40 overflow-y-auto leading-relaxed">{{ previewWaMessage }}</pre>
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-2 shrink-0">
                        <button type="button" @click="closeBuyerModal" class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-100 transition-colors">
                            Tutup
                        </button>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button 
                                type="button" 
                                @click="saveBuyerOnly" 
                                :disabled="buyerForm.processing"
                                class="flex-1 sm:flex-initial px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all disabled:opacity-50 cursor-pointer"
                            >
                                {{ buyerForm.processing ? 'Menyimpan...' : 'Hanya Simpan Nama' }}
                            </button>
                            <button 
                                type="button" 
                                @click="sendViaWhatsApp" 
                                :disabled="buyerForm.processing"
                                class="flex-1 sm:flex-initial px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-200 hover:shadow-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                <span>Kirim ke WhatsApp</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </Teleport>

    </component>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ClientAreaLayout from '@/Layouts/ClientAreaLayout.vue';
import Swal from 'sweetalert2';
import { usePermission } from '@/Composables/usePermission';

const page = usePage();
const { can, hasRole } = usePermission();

const props = defineProps({
    vouchers: Object,
    profiles: Array,
    resellers: Array,
    currentReseller: Object,
    isResellerPortal: Boolean,
    filters: Object,
    onlineUsernames: Array,
    usageStats: Object,
    areas: Array,
    stats: Object,
});

const isClientArea = computed(() => {
    const role = (page.props.auth?.user?.role || '').toLowerCase();
    const portal = (page.props.auth?.user?.login_portal || '').toLowerCase();
    const url = typeof window !== 'undefined' ? window.location.pathname : '';
    const isAdmin = role === 'admin' || role === 'super admin' || role === 'superadmin' || hasRole('admin') || hasRole('super admin');

    // Jika admin mengakses dari panel utama, wajib AppLayout
    if (isAdmin && portal !== 'reseller' && !url.startsWith('/client-area') && !props.isResellerPortal) {
        return false;
    }

    return role === 'reseller' 
        || role === 'biller'
        || role === 'customer' 
        || portal === 'reseller'
        || Boolean(props.isResellerPortal)
        || url.startsWith('/client-area');
});

const layoutComponent = computed(() => isClientArea.value ? ClientAreaLayout : AppLayout);

const isReseller = computed(() => {
    const role = (page.props.auth?.user?.role || '').toLowerCase();
    const portal = (page.props.auth?.user?.login_portal || '').toLowerCase();
    const url = typeof window !== 'undefined' ? window.location.pathname : '';
    const isAdmin = role === 'admin' || role === 'super admin' || role === 'superadmin' || hasRole('admin') || hasRole('super admin');

    if (isAdmin && portal !== 'reseller' && !url.startsWith('/client-area') && !props.isResellerPortal) {
        return false;
    }

    return isClientArea.value 
        || role === 'reseller' 
        || role === 'biller'
        || portal === 'reseller'
        || Boolean(props.isResellerPortal);
});

const sellableProfiles = computed(() => {
    return (props.profiles || []).filter(p => !p.is_customer_free);
});

const getProfileResellerCost = (prof) => {
    if (!prof) return 0;
    const sellPrice = Number(prof.price || 0);
    const margin = Number(prof.fee_reseller || 0);
    return Math.max(0, sellPrice - margin);
};

const formatSeconds = (seconds) => {
    if (!seconds) return '0 mnt';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h} jam ${m} mnt`;
    return `${m} mnt`;
};

const parseDurationToSeconds = (duration) => {
    if (!duration) return 0;
    const str = duration.toString().toLowerCase().trim();
    if (/^\d+$/.test(str)) return parseInt(str);
    
    let seconds = 0;
    const regex = /(\d+)\s*([wdhms])/g;
    let match;
    const units = { w: 604800, d: 86400, h: 3600, m: 60, s: 1 };
    
    while ((match = regex.exec(str)) !== null) {
        seconds += parseInt(match[1]) * units[match[2]];
    }
    return seconds;
};

const calculateExpiration = (firstLoginIso, durationStr) => {
    if (!firstLoginIso || !durationStr) return '-';
    const seconds = parseDurationToSeconds(durationStr);
    if (!seconds) return '-';
    
    const date = new Date(firstLoginIso);
    date.setSeconds(date.getSeconds() + seconds);
    
    return date.toLocaleString('id-ID', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const resellerFilter = ref(props.filters?.reseller_id || '');
const areaFilter = ref(props.filters?.area_id || '');
const showModal = ref(false);
const activeTab = ref('single');
const showSinglePassword = ref(false);
const selectedVouchers = ref([]);
const toggling = ref(null);

const toggleStatus = (voucher) => {
    const action = voucher.is_active ? 'Nonaktifkan' : 'Aktifkan';
    if (!confirm(`${action} voucher ${voucher.code}?`)) return;
    toggling.value = voucher.id;
    router.post(`/vouchers/${voucher.id}/toggle-status`, {}, {
        preserveScroll: true,
        onFinish: () => (toggling.value = null),
    });
};

// Form Single Voucher
const singleForm = useForm({
    mode: 'single',
    voucher_profile_id: '',
    login_type: 'username_only', // 'username_only' atau 'username_password'
    username: '',
    password: '',
    cost_type: 'paid', // 'paid' atau 'free'
    buyer_name: '',
    buyer_phone: '',
    reseller_id: '',
    reseller_payment: 'balance', // 'balance' atau 'cash'
});

// Form Bulk Voucher
const bulkForm = useForm({
    mode: 'bulk',
    voucher_profile_id: '',
    amount: 10,
    length: 6,
    prefix: '',
    type: 'vc',
    combination: 'alphanumeric',
    cost_type: 'paid', // 'paid' atau 'free'
    reseller_id: '',
    reseller_payment: 'balance', // 'balance' atau 'cash'
});

// Helper Generate Random Strings
function getRandomChars(len = 6, numericOnly = false) {
    const chars = numericOnly ? '0123456789' : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let res = '';
    for (let i = 0; i < len; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return res;
}

function generateRandomUsername() {
    singleForm.username = 'VC' + getRandomChars(5);
}

function generateRandomPassword() {
    singleForm.password = getRandomChars(6);
}

// Helpers Info Profil
function getSelectedProfilePrice(profileId) {
    if (!profileId) return '0';
    const prof = (props.profiles || []).find(p => p.id === profileId);
    if (!prof) return '0';
    if (isReseller.value) {
        return Number(getProfileResellerCost(prof)).toLocaleString('id-ID');
    }
    return Number(prof.price).toLocaleString('id-ID');
}

function getSelectedProfileName(profileId) {
    if (!profileId) return 'Pilih Profil';
    const prof = props.profiles.find(p => p.id === profileId);
    return prof ? prof.name : 'Pilih Profil';
}

function getSelectedProfileDuration(profileId) {
    if (!profileId) return '';
    const prof = props.profiles.find(p => p.id === profileId);
    return prof?.duration ? `Durasi: ${prof.duration}` : '';
}

const doFilter = () => {
    router.get(route('vouchers.index'), { 
        search: search.value, 
        status: statusFilter.value,
        reseller_id: resellerFilter.value,
        area_id: areaFilter.value,
    }, { preserveState: true, replace: true });
};

const filterByStatus = (status) => {
    if (statusFilter.value === status) {
        statusFilter.value = '';
    } else {
        statusFilter.value = status;
    }
    doFilter();
};

const resetFilter = () => {
    search.value = '';
    statusFilter.value = '';
    resellerFilter.value = '';
    areaFilter.value = '';
    doFilter();
};

const openGenerateModal = () => {
    singleForm.reset();
    singleForm.clearErrors();
    singleForm.buyer_name = '';
    singleForm.buyer_phone = '';
    singleForm.voucher_profile_id = sellableProfiles.value[0]?.id || props.profiles[0]?.id || '';
    if (isReseller.value) {
        singleForm.cost_type = 'paid';
        singleForm.reseller_id = props.currentReseller?.id || '';
        singleForm.reseller_payment = 'balance';
    }
    generateRandomUsername();
    
    bulkForm.reset();
    bulkForm.clearErrors();
    bulkForm.voucher_profile_id = sellableProfiles.value[0]?.id || props.profiles[0]?.id || '';
    if (isReseller.value) {
        bulkForm.cost_type = 'paid';
        bulkForm.reseller_id = props.currentReseller?.id || '';
        bulkForm.reseller_payment = 'balance';
    }

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submitSingleForm = () => {
    if (isReseller.value) {
        singleForm.cost_type = 'paid';
        singleForm.reseller_id = props.currentReseller?.id || '';
        singleForm.reseller_payment = 'balance';
    }
    singleForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dibuat',
                text: `Single Voucher ${singleForm.username} berhasil dibuat (${singleForm.cost_type === 'free' ? 'Gratis' : 'Berbayar'}).`,
            });
        }
    });
};

const submitBulkForm = () => {
    if (isReseller.value) {
        bulkForm.cost_type = 'paid';
        bulkForm.reseller_id = props.currentReseller?.id || '';
        bulkForm.reseller_payment = 'balance';
    }
    bulkForm.post(route('vouchers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Generate',
                text: `${bulkForm.amount} Voucher berhasil digenerate (${bulkForm.cost_type === 'free' ? 'Gratis' : 'Berbayar'}).`,
            });
        }
    });
};

const deleteVoucher = (id) => {
    Swal.fire({
        title: 'Hapus Voucher?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.destroy.post', id), {}, {
                onSuccess: () => {
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const bulkDelete = () => {
    Swal.fire({
        title: 'Hapus ' + selectedVouchers.value.length + ' Voucher?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus Semua!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('vouchers.bulk-destroy'), { ids: selectedVouchers.value }, {
                onSuccess: () => {
                    selectedVouchers.value = [];
                    Swal.fire('Terhapus!', 'Voucher berhasil dihapus.', 'success');
                }
            });
        }
    });
};

const printSelected = () => {
    if (selectedVouchers.value.length === 0) return;
    
    // Convert array of IDs to query string parameters: ids[]=1&ids[]=2
    const queryParams = new URLSearchParams();
    selectedVouchers.value.forEach(id => {
        queryParams.append('ids[]', id);
    });
    
    const url = route('vouchers.print') + '?' + queryParams.toString();
    window.open(url, '_blank');
};

const isAllSelected = computed(() => {
    return props.vouchers.data.length > 0 && selectedVouchers.value.length === props.vouchers.data.length;
});

const toggleSelectAll = (e) => {
    if (e.target.checked) {
        selectedVouchers.value = props.vouchers.data.map(v => v.id);
    } else {
        selectedVouchers.value = [];
    }
};

// WhatsApp & Buyer Modal Logic
const showBuyerModal = ref(false);
const selectedVoucherForBuyer = ref(null);

const buyerForm = useForm({
    buyer_name: '',
    buyer_phone: '',
});

const openBuyerModal = (voucher) => {
    selectedVoucherForBuyer.value = voucher;
    buyerForm.buyer_name = voucher.buyer_name || '';
    buyerForm.buyer_phone = voucher.buyer_phone || '';
    showBuyerModal.value = true;
};

const closeBuyerModal = () => {
    showBuyerModal.value = false;
    selectedVoucherForBuyer.value = null;
};

const formatWaPhone = (phone) => {
    if (!phone) return '';
    let cleaned = phone.toString().replace(/\D/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    } else if (cleaned.startsWith('8')) {
        cleaned = '62' + cleaned;
    }
    return cleaned;
};

const previewWaMessage = computed(() => {
    const v = selectedVoucherForBuyer.value;
    if (!v) return '';
    const buyerName = buyerForm.buyer_name ? buyerForm.buyer_name.trim() : 'Kak';
    const profileName = v.profile?.name || 'Paket Hotspot';
    const duration = v.profile?.duration ? ` (${v.profile.duration})` : '';
    const price = Number(v.profile?.price || 0).toLocaleString('id-ID');

    let credText = '';
    if (v.password && v.password !== v.username && v.password !== v.code) {
        credText = `🔑 *Username:* ${v.username}\n🔒 *Password:* ${v.password}`;
    } else {
        credText = `🔑 *Kode Voucher:* ${v.code}\n_(Username dan Password sama)_`;
    }

    return `Halo Kak *${buyerName}*,\nBerikut detail voucher internet WiFi Anda:\n\n📶 *Paket:* ${profileName}${duration}\n💰 *Harga:* Rp ${price}\n\n${credText}\n\n🌐 *Cara Login:*\n1. Hubungkan HP/Laptop ke sinyal WiFi Hotspot\n2. Buka browser (Chrome/Safari)\n3. Masukkan Username & Password di atas lalu klik Login\n\n⚠️ *Penting:* Simpan pesan WhatsApp ini agar kode voucher tidak hilang jika perangkat Anda terputus.\n\nTerima kasih! 🙏`;
});

const copyWaMessage = async () => {
    try {
        await navigator.clipboard.writeText(previewWaMessage.value);
        Swal.fire({
            icon: 'success',
            title: 'Tersalin!',
            text: 'Teks pesan voucher berhasil disalin ke clipboard.',
            timer: 1500,
            showConfirmButton: false
        });
    } catch (e) {
        Swal.fire('Info', 'Silakan salin teks pesan secara manual.', 'info');
    }
};

const saveBuyerOnly = () => {
    if (!selectedVoucherForBuyer.value) return;
    buyerForm.post(route('vouchers.assign-buyer', selectedVoucherForBuyer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeBuyerModal();
            Swal.fire({
                icon: 'success',
                title: 'Tersimpan',
                text: 'Data nama pembeli voucher berhasil diperbarui.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

const sendViaWhatsApp = () => {
    if (!selectedVoucherForBuyer.value) return;
    const v = selectedVoucherForBuyer.value;
    const cleanPhone = formatWaPhone(buyerForm.buyer_phone);
    const message = previewWaMessage.value;
    const waUrl = cleanPhone 
        ? `https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodeURIComponent(message)}`
        : `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;

    // Save buyer name & phone first, then open WhatsApp in new tab
    buyerForm.post(route('vouchers.assign-buyer', v.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeBuyerModal();
            window.open(waUrl, '_blank');
        }
    });
};
</script>
