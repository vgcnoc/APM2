<template>
    <AppLayout :title="isolirOnly ? 'Pelanggan Isolir' : 'Data Pelanggan'" :subtitle="isolirOnly ? 'Daftar pelanggan yang layanannya sedang diisolir' : 'Kelola semua data pelanggan ISP'">
        <!-- STATS SECTION -->
        <div v-if="stats && !isolirOnly" v-can="'customers_card_summary'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Pelanggan -->
            <div 
                @click="setQuickStatus('')" 
                class="glass-card p-5 flex items-center justify-between cursor-pointer transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 group"
                :class="!filterStatus ? 'ring-2 ring-blue-500/40 border-blue-400 bg-blue-50/20 shadow-sm' : 'hover:border-blue-200'"
                title="Tampilkan Semua Pelanggan"
            >
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Pelanggan</p>
                        <h3 class="text-2xl font-bold text-gray-900">{{ stats.total.value }}</h3>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span :class="['text-sm font-semibold', stats.total.growth >= 0 ? 'text-green-500' : 'text-red-500']">
                        <span v-if="stats.total.growth > 0">↗</span>
                        <span v-else-if="stats.total.growth < 0">↘</span>
                        {{ Math.abs(stats.total.growth) }}%
                    </span>
                    <span class="text-[10px] text-gray-400">Dari bulan lalu</span>
                </div>
            </div>

            <!-- Pelanggan Aktif -->
            <div 
                @click="setQuickStatus('active')" 
                class="glass-card p-5 flex items-center justify-between cursor-pointer transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 group"
                :class="filterStatus === 'active' ? 'ring-2 ring-emerald-500/40 border-emerald-400 bg-emerald-50/20 shadow-sm' : 'hover:border-emerald-200'"
                title="Filter Pelanggan Aktif"
            >
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-400 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pelanggan Aktif</p>
                        <h3 class="text-2xl font-bold text-gray-900">{{ stats.active.value }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ stats.active.percentage }}%</p>
                    </div>
                </div>
                <div class="flex flex-col items-end justify-start h-full">
                    <span :class="['text-sm font-semibold mt-1', stats.active.growth >= 0 ? 'text-green-500' : 'text-red-500']">
                        <span v-if="stats.active.growth > 0">↗</span>
                        <span v-else-if="stats.active.growth < 0">↘</span>
                        {{ Math.abs(stats.active.growth) }}%
                    </span>
                </div>
            </div>

            <!-- Pelanggan Pending -->
            <div 
                @click="setQuickStatus('pending')" 
                class="glass-card p-5 flex items-center justify-between cursor-pointer transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 group"
                :class="filterStatus === 'pending' ? 'ring-2 ring-yellow-500/40 border-yellow-400 bg-yellow-50/20 shadow-sm' : 'hover:border-yellow-200'"
                title="Filter Pelanggan Pending (Booking/Survey/Pasang)"
            >
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-yellow-400 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pelanggan Pending</p>
                        <h3 class="text-2xl font-bold text-gray-900">{{ stats.pending.value }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ stats.pending.percentage }}%</p>
                    </div>
                </div>
                <div class="flex flex-col items-end justify-start h-full">
                    <span :class="['text-sm font-semibold mt-1', stats.pending.growth >= 0 ? 'text-green-500' : 'text-red-500']">
                        <span v-if="stats.pending.growth > 0">↗</span>
                        <span v-else-if="stats.pending.growth < 0">↘</span>
                        {{ Math.abs(stats.pending.growth) }}%
                    </span>
                </div>
            </div>

            <!-- Pelanggan Nonaktif -->
            <div 
                @click="setQuickStatus('inactive')"
                class="glass-card p-5 flex items-center justify-between cursor-pointer transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 group"
                :class="filterStatus === 'inactive' ? 'ring-2 ring-red-500/40 border-red-400 bg-red-50/20 shadow-sm' : 'hover:border-red-200'"
                title="Filter Pelanggan Nonaktif (Isolir / Disable)"
            >
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-red-400 flex items-center justify-center group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Pelanggan Nonaktif</p>
                        <h3 class="text-2xl font-bold text-gray-900">{{ stats.inactive.value }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5">{{ stats.inactive.percentage }}%</p>
                    </div>
                </div>
                <div class="flex flex-col items-end justify-start h-full">
                    <span :class="['text-sm font-semibold mt-1', stats.inactive.growth >= 0 ? 'text-green-500' : 'text-red-500']">
                        <span v-if="stats.inactive.growth > 0">↗</span>
                        <span v-else-if="stats.inactive.growth < 0">↘</span>
                        {{ Math.abs(stats.inactive.growth) }}%
                    </span>
                </div>
            </div>
        </div>

        <DataTable
            :columns="columns"
            :data="customers.data"
            :pagination="customers"
            searchPlaceholder="Cari nama, kode pelanggan, telepon, atau alamat..."
            :searchRoute="customerListRoute"
            selectable
            v-model:selected="selectedIds"
        >
            <!-- Filter Slot -->
            <template #filters>
                <!-- Status -->
                <div v-if="!isolirOnly" v-can="'customers_filter_status'" class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[160px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Status</span>
                        <select v-model="filterStatus" @change="applyFilters" class="form-select border-0 pt-0 pb-1 pl-0 !pr-7 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full cursor-pointer truncate">
                            <option value="">Semua Status</option>
                            <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Layanan (Berbayar / Gratis / Free Home) -->
                <div v-can="'customers_filter_status'" class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[165px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Layanan</span>
                        <select v-model="filterServiceStatus" @change="applyFilters" class="form-select border-0 pt-0 pb-1 pl-0 !pr-7 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full cursor-pointer truncate">
                            <option value="">Semua Layanan</option>
                            <option value="berbayar">💳 Berbayar</option>
                            <option value="gratis">🎁 Gratis (CSR)</option>
                            <option value="free_home">🏠 Free Home (Voucher)</option>
                        </select>
                    </div>
                </div>

                <!-- Paket -->
                <div v-can="'customers_filter_package'" class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[170px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Paket</span>
                        <select v-model="filterPackage" @change="applyFilters" class="form-select border-0 pt-0 pb-1 pl-0 !pr-7 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full cursor-pointer truncate">
                            <option value="">Semua Paket</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Area -->
                <div v-can="'customers_filter_area'" class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[155px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Area</span>
                        <select v-model="filterArea" @change="applyFilters" class="form-select border-0 pt-0 pb-1 pl-0 !pr-7 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full cursor-pointer truncate">
                            <option value="">Semua Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- ODP -->
                <div v-can="'customers_filter_area'" class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[155px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">ODP</span>
                        <select v-model="filterOdp" @change="applyFilters" class="form-select border-0 pt-0 pb-1 pl-0 !pr-7 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full cursor-pointer truncate">
                            <option value="">Semua ODP</option>
                            <option v-for="odp in odps" :key="odp.id" :value="odp.id">{{ odp.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Urutkan -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[210px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </div>
                    <div class="flex flex-col flex-1 min-w-0">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Urutkan</span>
                        <select v-model="filterSort" @change="applyFilters" class="form-select border-0 pt-0 pb-1 pl-0 !pr-7 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full cursor-pointer truncate">
                            <option value="">Terbaru (Tgl Daftar)</option>
                            <option value="terlama">Terlama (Tgl Daftar)</option>
                            <option value="nama_asc">Nama (A-Z)</option>
                            <option value="nama_desc">Nama (Z-A)</option>
                        </select>
                    </div>
                </div>

                <!-- Spacer to push reset to right -->
                <div class="hidden xl:block flex-1"></div>

                <!-- Reset Button -->
                <button @click="resetFilters" class="btn-secondary bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium text-sm py-2 px-4 rounded-lg shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Reset
                </button>
            </template>

            <!-- Action Button -->
            <template #actions>
                <div class="flex items-center gap-2">
                    <Link v-can="'menu_customers_gratis'" href="/customers/gratis" class="px-3 py-2 rounded-lg text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-colors flex items-center gap-1.5 shadow-sm">
                        <span>🎁</span> Pelanggan Gratis
                    </Link>
                    <Link v-can="'menu_customers_free_home'" href="/customers/free-home" class="px-3 py-2 rounded-lg text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition-colors flex items-center gap-1.5 shadow-sm">
                        <span>🏠</span> Pelanggan Free Home
                    </Link>
                    <Link v-can="'customers_all_create'" href="/customers/create" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Pelanggan
                    </Link>
                </div>
            </template>

            <!-- Table Rows -->
            <template #row="{ row, index }">
                <td class="text-gray-500 text-xs text-center">
                    {{ (customers.current_page - 1) * customers.per_page + index + 1 }}
                </td>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <div :class="[
                                'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0',
                                row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user) ? 'ring-2 ring-emerald-400/60' : '',
                                getAvatarColor(row.name)
                            ]">
                                {{ row.name.charAt(0).toUpperCase() }}
                            </div>
                            <span v-if="row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user)" class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white ring-1 ring-emerald-300"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <Link :href="`/customers/${row.id}`" 
                                    :class="[
                                        'font-semibold text-sm transition-colors inline-flex items-center gap-1.5',
                                        row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user) 
                                            ? 'text-emerald-600 hover:text-emerald-700 font-bold' 
                                            : 'text-gray-900 hover:text-blue-500'
                                    ]">
                                    <span v-if="row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user)" class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                                    {{ row.name }}
                                </Link>
                                <span v-if="row.service_status === 'gratis'" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200" title="Pelanggan Gratis (Non-Invoice)">
                                    🎁 Gratis
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span :class="row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user) ? 'text-emerald-700/80 font-medium' : 'text-gray-500'" class="text-[10px] font-mono">{{ row.customer_code }}</span>
                                <span v-if="row.registration_date" class="text-[10px] text-indigo-700 bg-indigo-50 border border-indigo-100/70 px-1.5 py-0.5 rounded font-medium" title="Tanggal Daftar">
                                    📅 {{ formatDate(row.registration_date) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex items-center gap-1.5 text-gray-600 text-xs whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        {{ row.phone }}
                    </div>
                </td>
                <td>
                    <div class="flex items-start gap-1.5 text-gray-500 text-xs max-w-[200px]">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span class="truncate" :title="row.address">{{ row.address }}</span>
                    </div>
                </td>
                <td>
                    <span v-if="row.area_model" class="px-2 py-1 bg-blue-50 text-blue-600 border border-blue-100 rounded text-[10px] font-bold whitespace-nowrap uppercase">{{ row.area_model.name }}</span>
                    <span v-else class="px-2 py-1 bg-gray-50 text-gray-500 border border-gray-100 rounded text-[10px] font-bold whitespace-nowrap uppercase">{{ row.area || '-' }}</span>
                </td>
                <td>
                    <span v-if="row.service_status === 'free_home'" class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[10px] font-bold whitespace-nowrap">
                        <span>🏠</span> Free Home
                    </span>
                    <span v-else-if="row.service_status === 'gratis'" class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-bold whitespace-nowrap">
                        <span>🎁</span> Gratis
                    </span>
                    <div v-else-if="row.package" class="flex items-center gap-1.5 text-blue-500 text-xs font-bold whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                        {{ row.package.name }}
                    </div>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td>
                    <div class="flex flex-col gap-1 items-start">
                        <span v-if="row.connection_disabled" class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[10px] font-bold text-rose-700">Disable</span>
                        <StatusBadge v-if="!row.connection_disabled || row.status === 'suspended'" :status="row.status" />
                        <span v-if="row.connection_access_pending" class="text-[10px] font-medium text-amber-600">Perlu sinkronisasi koneksi</span>
                        <span v-if="row.billing_isolation_pending" class="text-[10px] font-medium text-amber-600">Perubahan layanan tertunda</span>
                        <div v-if="row.status === 'active' && row.ont?.pppoe_user">
                            <span v-if="onlineUsernames?.includes(row.ont.pppoe_user)" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online
                            </span>
                            <span v-else class="inline-flex items-center gap-1 text-[10px] font-semibold text-red-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Offline
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <span v-if="row.ont?.odp?.name || (row.surveys && row.surveys.length > 0 && row.surveys[0].odp?.name)" class="px-2 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded text-[10px] font-bold whitespace-nowrap uppercase">
                        {{ row.ont?.odp?.name || (row.surveys && row.surveys.length > 0 ? row.surveys[0].odp?.name : '-') || '-' }}
                    </span>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
            </template>

            <!-- Row Actions -->
            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1.5 whitespace-nowrap">
                    <!-- Tombol Cepat KICK jika Online -->
                    <button 
                        v-if="row.status === 'active' && row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user)" 
                        @click="kickSession(row.ont.pppoe_user)" 
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold text-red-600 bg-red-50 hover:bg-red-600 hover:text-white transition-all shadow-2xs hover:shadow-xs focus:outline-none" 
                        title="Kick Sesi Online"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        KICK
                    </button>

                    <!-- Tombol Dropdown Menu Aksi -->
                    <button 
                        type="button" 
                        @click.stop="toggleDropdown($event, row)" 
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-white hover:bg-slate-50 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-lg transition-all shadow-2xs hover:shadow-xs cursor-pointer text-xs font-semibold focus:outline-none"
                        :class="{ 'ring-2 ring-blue-500 bg-blue-50 text-blue-600 border-blue-300': activeDropdownRow?.id === row.id }"
                        title="Pilihan Aksi"
                    >
                        <span>Aksi</span>
                        <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-blue-600': activeDropdownRow?.id === row.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Action Dropdown Menu Floating -->
        <Teleport to="body">
            <div v-if="activeDropdownRow" class="fixed inset-0 z-[120]" @click="closeDropdown" @contextmenu.prevent="closeDropdown">
                <div 
                    :style="{ top: `${dropdownPos.top}px`, left: `${dropdownPos.left}px` }"
                    class="fixed w-64 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-slate-200/80 py-1.5 z-[121] text-xs divide-y divide-slate-100 animate-fade-in-up"
                    @click.stop
                >
                    <!-- Header Info Pelanggan -->
                    <div class="px-3.5 py-2.5 bg-slate-50/90 -mt-1.5 rounded-t-2xl border-b border-slate-100 mb-1">
                        <div class="flex items-center gap-2.5">
                            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0 shadow-2xs', getAvatarColor(activeDropdownRow.name)]">
                                {{ activeDropdownRow.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 truncate text-xs">{{ activeDropdownRow.name }}</div>
                                <div class="flex items-center gap-1.5 text-[10px] text-slate-500 font-mono mt-0.5">
                                    <span class="font-semibold text-slate-600">{{ activeDropdownRow.customer_code }}</span>
                                    <span>&bull;</span>
                                    <span class="truncate text-blue-600 font-medium">{{ activeDropdownRow.package?.name || (activeDropdownRow.service_status === 'gratis' ? 'Gratis' : (activeDropdownRow.service_status === 'free_home' ? 'Free Home' : '-')) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grup 1: Informasi & Manajemen Data -->
                    <div class="py-1">
                        <!-- Lihat Detail -->
                        <Link 
                            :href="`/customers/${activeDropdownRow.id}`" 
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-blue-50/80 hover:text-blue-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-blue-50 group-hover:bg-blue-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-blue-700">Lihat Detail</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-blue-500 font-normal leading-tight">Informasi lengkap & riwayat</p>
                            </div>
                        </Link>

                        <!-- Edit Data Pelanggan -->
                        <Link 
                            v-can="'customers_all_edit'"
                            :href="`/customers/${activeDropdownRow.id}/edit`" 
                            @click="closeDropdown"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-indigo-50/80 hover:text-indigo-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 group-hover:bg-indigo-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-indigo-700">Edit Pelanggan</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-indigo-500 font-normal leading-tight">Ubah paket, ODP, atau kontak</p>
                            </div>
                        </Link>
                    </div>

                    <!-- Grup 2: Akun Portal & Sesi Jaringan -->
                    <div class="py-1">
                        <!-- Buat Akun Login -->
                        <button 
                            v-can="'customers_all_edit'"
                            v-if="!activeDropdownRow.user_id" 
                            type="button"
                            @click="openCreateAccountModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-emerald-50/80 hover:text-emerald-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-emerald-50 group-hover:bg-emerald-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-emerald-700">Buat Akun Portal</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-emerald-500 font-normal leading-tight">Akses login Client Area</p>
                            </div>
                        </button>

                        <!-- Ubah Password Akun -->
                        <button 
                            v-can="'customers_all_edit'"
                            v-if="activeDropdownRow.user_id" 
                            type="button"
                            @click="openResetModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-amber-50/80 hover:text-amber-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 group-hover:bg-amber-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-slate-800 group-hover:text-amber-700">Ubah Password Akun</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-amber-500 font-normal leading-tight">Reset password login portal</p>
                            </div>
                        </button>

                        <!-- Kick Sesi Online -->
                        <button 
                            v-if="activeDropdownRow.status === 'active' && activeDropdownRow.ont?.pppoe_user && onlineUsernames?.includes(activeDropdownRow.ont.pppoe_user)"
                            type="button"
                            @click="kickSession(activeDropdownRow.ont.pppoe_user); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-orange-50/80 hover:text-orange-700 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-orange-50 group-hover:bg-orange-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-orange-600 group-hover:text-orange-700">Kick Sesi Online</span>
                                <p class="text-[10px] text-slate-400 group-hover:text-orange-500 font-normal leading-tight">Putus koneksi sesi PPPoE</p>
                            </div>
                        </button>
                    </div>

                    <!-- Grup 3: Penonaktifan & Hapus (Danger Zone) -->
                    <div class="py-1">
                        <button
                            v-can="'customers_active_suspend'"
                            v-if="activeDropdownRow.status !== 'terminated' && activeDropdownRow.status !== 'canceled'"
                            type="button"
                            :disabled="connectionAccessBusy"
                            @click="changeConnectionAccess(activeDropdownRow, !activeDropdownRow.connection_disabled); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-slate-700 hover:bg-amber-50/80 transition-colors font-medium group cursor-pointer disabled:opacity-50"
                        >
                            <div class="w-6 h-6 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 3v9M6.3 5.8a8 8 0 1 0 11.4 0" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block">{{ activeDropdownRow.connection_disabled ? 'Enable Koneksi' : 'Disable Koneksi' }}</span>
                                <p class="text-[10px] text-slate-400 font-normal leading-tight">{{ activeDropdownRow.connection_disabled ? 'Izinkan kembali akses internet' : 'Blokir login & putus sesi internet' }}</p>
                            </div>
                        </button>
                        <button v-can="'customers_active_suspend'" v-if="activeDropdownRow.connection_access_pending" type="button" :disabled="connectionAccessBusy"
                            @click="changeConnectionAccess(activeDropdownRow, !!activeDropdownRow.connection_disabled, true); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left text-xs font-semibold text-amber-600 hover:bg-amber-50 disabled:opacity-50">Sinkronkan Ulang Koneksi</button>
                        <!-- Stop Permanen -->
                        <button 
                            v-can="'customers_active_terminate'"
                            v-if="activeDropdownRow.status !== 'terminated'"
                            type="button"
                            @click="openTerminateModal(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-rose-700 hover:bg-rose-50/80 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-rose-50 group-hover:bg-rose-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-rose-700">Stop Permanen</span>
                                <p class="text-[10px] text-rose-400 font-normal leading-tight">Isolir & nonaktifkan sistem</p>
                            </div>
                        </button>

                        <!-- Hapus Pelanggan -->
                        <button 
                            v-can="'customers_delete'"
                            type="button"
                            @click="confirmDelete(activeDropdownRow); closeDropdown()"
                            class="w-full px-3.5 py-2 text-left flex items-center gap-2.5 text-red-600 hover:bg-red-50/80 transition-colors font-medium group cursor-pointer"
                        >
                            <div class="w-6 h-6 rounded-lg bg-red-50 group-hover:bg-red-100/80 flex items-center justify-center shrink-0 transition-colors">
                                <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="font-semibold text-xs block text-red-600">Hapus Pelanggan</span>
                                <p class="text-[10px] text-red-400 font-normal leading-tight">Hapus data beserta ONT</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative glass-card p-6 max-w-md w-full max-h-[90vh] overflow-y-auto animate-fade-in-up">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Hapus Pelanggan?</h3>
                            <p class="text-sm text-gray-500">{{ deletingCustomer?.name }} ({{ deletingCustomer?.customer_code }})</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">
                        Data pelanggan beserta ONT yang terhubung akan dihapus. Tindakan ini tidak dapat dibatalkan.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="showDeleteModal = false" class="btn-ghost">Batal</button>
                        <button @click="deleteCustomer" class="btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Hapus Permanen
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Buat Akun Modal -->
        <Teleport to="body">
            <div v-if="showCreateAccountModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCreateAccountModal = false"></div>
                <div class="relative glass-card w-full max-w-md overflow-hidden animate-fade-in-up">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span class="text-2xl">👤</span> Buat Akun Client Area
                        </h3>
                        <button @click="showCreateAccountModal = false" class="text-gray-500 hover:text-gray-900">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitCreateAccount">
                        <div class="p-6 space-y-4">
                            <div class="bg-blue-50 text-blue-800 p-4 rounded-xl text-sm mb-4 border border-blue-200 shadow-sm flex gap-3">
                                <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p>Buat akun ini agar pelanggan dapat login ke Client Area untuk mengecek tagihan dan laporan gangguan secara mandiri.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username (Email) <span class="text-red-500">*</span></label>
                                <input v-model="accountForm.email" type="email" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required placeholder="email@contoh.com" />
                                <div v-if="accountForm.errors.email" class="text-xs text-red-500 mt-1">{{ accountForm.errors.email }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                                <input v-model="accountForm.password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required minlength="6" placeholder="Minimal 6 karakter" />
                                <div v-if="accountForm.errors.password" class="text-xs text-red-500 mt-1">{{ accountForm.errors.password }}</div>
                            </div>
                        </div>
                        <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                            <button type="button" @click="showCreateAccountModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                            <button type="submit" :disabled="accountForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                <svg v-if="accountForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ accountForm.processing ? 'Menyimpan...' : 'Buat Akun' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Ubah Password Modal -->
        <Teleport to="body">
            <div v-if="showResetPasswordModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showResetPasswordModal = false"></div>
                <div class="relative glass-card w-full max-w-md overflow-hidden animate-fade-in-up">
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-red-900 flex items-center gap-2">
                            <span class="text-2xl">🔑</span> Ubah Password Akun
                        </h3>
                        <button @click="showResetPasswordModal = false" class="text-gray-500 hover:text-gray-900">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitResetPassword">
                        <div class="p-6 space-y-4">
                            <div class="bg-red-50 text-red-800 p-4 rounded-xl text-sm mb-4 border border-red-200 shadow-sm flex gap-3">
                                <svg class="w-6 h-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <p>Aksi ini akan merubah detail akun login pelanggan saat ini. Pastikan Anda memberikan username/password baru kepada pelanggan.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username (Email)</label>
                                <input v-model="resetPasswordForm.email" type="email" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" placeholder="email@contoh.com" />
                                <div v-if="resetPasswordForm.errors.email" class="text-xs text-red-500 mt-1">{{ resetPasswordForm.errors.email }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                                <input v-model="resetPasswordForm.password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" minlength="6" placeholder="Kosongkan jika tidak ingin mengubah password (min. 6 karakter)" />
                                <div v-if="resetPasswordForm.errors.password" class="text-xs text-red-500 mt-1">{{ resetPasswordForm.errors.password }}</div>
                            </div>
                        </div>
                        <div class="p-5 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                            <button type="button" @click="showResetPasswordModal = false" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">Batal</button>
                            <button type="submit" :disabled="resetPasswordForm.processing" class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-lg text-sm font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                                <svg v-if="resetPasswordForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ resetPasswordForm.processing ? 'Menyimpan...' : 'Ubah Password' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Stop Permanen Modal -->
        <Teleport to="body">
            <div v-if="showTerminateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm cursor-pointer" @click="showTerminateModal = false"></div>
                <div class="relative bg-white border border-rose-200 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-up">
                    <div class="p-5 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                        <h3 class="text-base font-bold text-rose-900 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-sm font-bold">⛔</span> Stop Permanen Pelanggan
                        </h3>
                        <button @click="showTerminateModal = false" class="text-gray-400 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitTerminate">
                        <div class="p-6 space-y-4 text-xs">
                            <div class="bg-rose-50 text-rose-800 p-3.5 rounded-xl border border-rose-200 shadow-sm leading-relaxed">
                                Pelanggan <strong>{{ terminatingCustomer?.name }}</strong> ({{ terminatingCustomer?.customer_code }}) akan dipindahkan ke menu <strong>Stop Permanen</strong> dan dinonaktifkan dari sistem serta RADIUS.
                            </div>

                            <!-- Peringatan Auto-Pinalti Kontrak < 4 Bulan -->
                            <div v-if="customerActiveTenure && customerActiveTenure.isUnder4" class="p-3.5 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 text-amber-900 shadow-xs space-y-2 animate-fade-in">
                                <div class="flex items-center gap-1.5 font-bold text-xs text-amber-900">
                                    <span class="text-base">⚠️</span>
                                    <span>Peringatan Kontrak: Masa Aktif Baru {{ customerActiveTenure.text }} (&lt; 4 Bulan)</span>
                                </div>
                                <p class="text-[11px] text-amber-800 leading-relaxed">
                                    Pelanggan berhenti sebelum masa minimal komitmen <strong>4 bulan</strong>.
                                </p>
                                <!-- Pemotongan Fee Booking Afiliasi Otomatis -->
                                <div v-if="customerActiveTenure.hasSales" class="p-2.5 rounded-lg bg-white/90 border border-amber-200 text-[11px] space-y-1">
                                    <div class="font-bold flex items-center justify-between text-rose-800">
                                        <span class="flex items-center gap-1">✂️ Pemotongan Fee Booking Otomatis:</span>
                                        <span class="bg-rose-100 text-rose-700 px-1.5 py-0.2 rounded text-[10px] uppercase font-black">AUTO-POTONG</span>
                                    </div>
                                    <p class="text-slate-600">
                                        Fee booking yang pernah diterima oleh Sales/Afiliator <strong class="text-slate-900">{{ customerActiveTenure.salesName }}</strong> akan <strong>otomatis dipotong</strong> (dari slip gaji payroll jika karyawan, atau saldo dompet jika mitra).
                                    </p>
                                </div>
                                <div v-else class="text-[10px] text-slate-500 italic">
                                    (Pelanggan mendaftar tanpa sales afiliasi, tidak ada fee booking yang dipotong).
                                </div>

                                <!-- Pemotongan Fee Survey Teknisi Otomatis -->
                                <div v-if="customerActiveTenure.hasSurveyor" class="p-2.5 rounded-lg bg-white/90 border border-amber-200 text-[11px] space-y-1">
                                    <div class="font-bold flex items-center justify-between text-rose-800">
                                        <span class="flex items-center gap-1">✂️ Pemotongan Fee Survey Otomatis:</span>
                                        <span class="bg-rose-100 text-rose-700 px-1.5 py-0.2 rounded text-[10px] uppercase font-black">AUTO-POTONG</span>
                                    </div>
                                    <p class="text-slate-600">
                                        Fee survey yang pernah diterima oleh Teknisi Surveyor <strong class="text-slate-900">{{ customerActiveTenure.surveyorName }}</strong> akan <strong>otomatis dipotong</strong> (dari slip gaji payroll jika karyawan, atau saldo dompet jika mitra).
                                    </p>
                                </div>
                                <div v-else class="text-[10px] text-slate-500 italic">
                                    (Pelanggan tidak memiliki riwayat teknisi survey, tidak ada fee survey yang dipotong).
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Tanggal Berhenti <span class="text-rose-500">*</span></label>
                                <input v-model="terminateForm.cancel_date" type="date" required class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-rose-500 focus:border-rose-500" />
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Alasan Berhenti Berlangganan <span class="text-rose-500">*</span></label>
                                <select v-model="terminateForm.cancel_reason" required class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-rose-500 focus:border-rose-500 mb-2">
                                    <option value="">Pilih Alasan...</option>
                                    <option value="Pindah Rumah / Domisili">Pindah Rumah / Domisili</option>
                                    <option value="Kendala Finansial / Biaya">Kendala Finansial / Biaya</option>
                                    <option value="Kecepatan / Gangguan Berulang">Kecepatan / Gangguan Berulang</option>
                                    <option value="Beralih ke Provider Lain">Beralih ke Provider Lain</option>
                                    <option value="Tidak Lagi Membutuhkan Internet">Tidak Lagi Membutuhkan Internet</option>
                                    <option value="Lainnya">Alasan Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Catatan Tambahan</label>
                                <textarea v-model="terminateForm.notes" rows="2" placeholder="Catatan opsional..." class="w-full bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-rose-500 focus:border-rose-500"></textarea>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-2 rounded-b-2xl">
                            <button type="button" @click="showTerminateModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-white border border-gray-300 rounded-lg">Batal</button>
                            <button type="submit" :disabled="terminateForm.processing" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold transition-all disabled:opacity-50 flex items-center gap-1.5 shadow-sm">
                                <span>{{ terminateForm.processing ? 'Menyimpan...' : 'Stop Permanen Sekarang' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router , usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    isolirOnly: { type: Boolean, default: false },
    customers: Object,
    packages: Array,
    areas: Array,
    odps: Array,
    officers: Array,
    stats: Object,
    filters: Object,
    statusOptions: Object,
    onlineUsernames: {
        type: Array,
        default: () => [],
    }
});



const page = usePage();
const selectedIds = ref([]);
const connectionAccessBusy = ref(false);
function changeConnectionAccess(customer, disabled, retry = false) {
    if (connectionAccessBusy.value) return;
    if (!retry && !window.confirm(disabled
        ? `Disable koneksi ${customer.name}? Sesi internet akan diputus dan login diblokir hingga di-enable kembali. Status langganan dan tagihan tetap mengikuti ketentuan yang berlaku.`
        : `Enable kembali koneksi ${customer.name}? Layanan akan mengikuti status langganan pelanggan saat ini.`)) return;
    connectionAccessBusy.value = true;
    router.post(`/customers/${customer.id}/connection-access`, { disabled }, {
        preserveScroll: true,
        onFinish: () => { connectionAccessBusy.value = false; },
    });
}

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
    { key: 'index', label: '#' },
    { key: 'name', label: 'PELANGGAN' },
    { key: 'phone', label: 'KONTAK' },
    { key: 'address', label: 'ALAMAT' },
    { key: 'area', label: 'AREA' },
    { key: 'package', label: 'PAKET' },
    { key: 'status', label: 'STATUS' },
    { key: 'odp', label: 'ODP' },
];

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const clean = String(dateStr).split(' ')[0];
        const parts = clean.split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        return clean;
    } catch (e) {
        return dateStr;
    }
}

function getAvatarColor(name) {
    const colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-pink-500', 'bg-orange-500'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

const filterStatus = ref(props.filters?.status || '');
const filterServiceStatus = ref(props.filters?.service_status || '');
const filterPackage = ref(props.filters?.package_id || '');
const filterArea = ref(props.filters?.area_id || '');
const filterOdp = ref(props.filters?.odp_id || '');
const filterSort = ref(props.filters?.sort_by || '');
const customerListRoute = computed(() => props.isolirOnly ? '/customers/isolir' : '/customers');

function applyFilters() {
    router.get(customerListRoute.value, {
        status: props.isolirOnly ? undefined : filterStatus.value || undefined,
        search: props.filters?.search || undefined,
        service_status: filterServiceStatus.value || undefined,
        package_id: filterPackage.value || undefined,
        area_id: filterArea.value || undefined,
        odp_id: filterOdp.value || undefined,
        sort_by: filterSort.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function setQuickStatus(status) {
    if (filterStatus.value === status) {
        filterStatus.value = '';
    } else {
        filterStatus.value = status;
    }
    applyFilters();
}

function resetFilters() {
    filterStatus.value = '';
    filterServiceStatus.value = '';
    filterPackage.value = '';
    filterArea.value = '';
    filterOdp.value = '';
    filterSort.value = '';
    applyFilters();
}

// ── Delete Modal ───────────────────────────────────────────
const showDeleteModal = ref(false);
const deletingCustomer = ref(null);

function confirmDelete(customer) {
    deletingCustomer.value = customer;
    showDeleteModal.value = true;
}

function deleteCustomer() {
    router.post(`/customers/${deletingCustomer.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingCustomer.value = null;
        },
    });
}

function kickSession(username) {
    if (confirm(`Kick sesi untuk user ${username}?`)) {
        router.post('/radius/online-users/disconnect', { username }, {
            preserveScroll: true
        });
    }
}

// ── Buat Akun Modal ───────────────────────────────────────────
const showCreateAccountModal = ref(false);
const accountCustomer = ref(null);
const accountForm = useForm({
    email: '',
    password: ''
});

function openCreateAccountModal(customer) {
    accountCustomer.value = customer;
    accountForm.email = customer.email || (customer.customer_code ? customer.customer_code.toLowerCase().replace(/[^a-z0-9]/g, '') + '@customer.apm' : '');
    accountForm.password = '';
    accountForm.clearErrors();
    showCreateAccountModal.value = true;
}

function submitCreateAccount() {
    accountForm.post(`/customers/${accountCustomer.value.id}/create-account`, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateAccountModal.value = false;
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            if (typeof window !== 'undefined' && window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal Membuat Akun',
                    html: msg || 'Terjadi kesalahan saat membuat akun.',
                });
            }
        }
    });
}

// ── Ubah Password Modal ───────────────────────────────────────────
const showResetPasswordModal = ref(false);
const resetPasswordForm = useForm({
    email: '',
    password: ''
});
const resetCustomer = ref(null);

function openResetModal(customer) {
    resetCustomer.value = customer;
    resetPasswordForm.email = customer.user ? customer.user.email : '';
    resetPasswordForm.password = '';
    resetPasswordForm.clearErrors();
    showResetPasswordModal.value = true;
}

function submitResetPassword() {
    resetPasswordForm.post(`/customers/${resetCustomer.value.id}/reset-password`, {
        preserveScroll: true,
        onSuccess: () => {
            showResetPasswordModal.value = false;
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            if (typeof window !== 'undefined' && window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Gagal Ubah Password',
                    html: msg || 'Terjadi kesalahan saat mengubah password.',
                });
            }
        }
    });
}

// ── Stop Permanen Modal ───────────────────────────────────────────
const showTerminateModal = ref(false);
const terminatingCustomer = ref(null);
const terminateForm = useForm({
    cancel_date: new Date().toISOString().split('T')[0],
    cancel_reason: '',
    notes: '',
});

const customerActiveTenure = computed(() => {
    if (!terminatingCustomer.value) return null;
    const startStr = terminatingCustomer.value.activation_date || terminatingCustomer.value.registration_date || terminatingCustomer.value.created_at;
    if (!startStr) return null;
    const start = new Date(startStr);
    const end = new Date(terminateForm.cancel_date || new Date());
    const diffTime = Math.max(0, end.getTime() - start.getTime());
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    const months = Math.floor(diffDays / 30);
    const days = diffDays % 30;
    const isUnder4 = months < 4;

    // Deteksi Surveyor Teknisi
    const lastSurvey = terminatingCustomer.value.surveys && terminatingCustomer.value.surveys.length 
        ? terminatingCustomer.value.surveys[terminatingCustomer.value.surveys.length - 1] 
        : null;
    const surveySchedule = (terminatingCustomer.value.technician_schedules || terminatingCustomer.value.technicianSchedules || [])
        .find(s => s.type === 'survey');
    
    const surveyorName = lastSurvey?.surveyor?.name || surveySchedule?.technician?.name || null;
    const hasSurveyor = !!surveyorName;

    return {
        diffDays,
        months,
        days,
        isUnder4,
        text: months > 0 ? `${months} bulan ${days} hari` : `${diffDays} hari`,
        hasSales: !!terminatingCustomer.value.sales_id,
        salesName: terminatingCustomer.value.sales?.name || (terminatingCustomer.value.sales_id ? 'Sales #' + terminatingCustomer.value.sales_id : null),
        hasSurveyor,
        surveyorName,
    };
});

function openTerminateModal(customer) {
    terminatingCustomer.value = customer;
    terminateForm.cancel_date = new Date().toISOString().split('T')[0];
    terminateForm.cancel_reason = '';
    terminateForm.notes = '';
    showTerminateModal.value = true;
}

function submitTerminate() {
    if (!terminatingCustomer.value) return;
    terminateForm.post(`/customers/${terminatingCustomer.value.id}/terminate`, {
        preserveScroll: true,
        onSuccess: () => {
            showTerminateModal.value = false;
        }
    });
}

// ── Floating Action Dropdown Logic ─────────────────────────
const activeDropdownRow = ref(null);
const dropdownPos = ref({ top: 0, left: 0 });

function toggleDropdown(event, row) {
    if (activeDropdownRow.value && activeDropdownRow.value.id === row.id) {
        activeDropdownRow.value = null;
        return;
    }
    const rect = event.currentTarget.getBoundingClientRect();
    const dropdownHeight = 310;
    const dropdownWidth = 256; // w-64 = 256px

    let top = rect.bottom + 6;
    if (top + dropdownHeight > window.innerHeight) {
        if (rect.top > dropdownHeight) {
            top = Math.max(10, rect.top - dropdownHeight - 6);
        } else {
            top = Math.max(10, window.innerHeight - dropdownHeight - 10);
        }
    }

    let left = rect.right - dropdownWidth;
    if (left < 10) {
        left = 10;
    }

    dropdownPos.value = { top, left };
    activeDropdownRow.value = row;
}

function closeDropdown() {
    activeDropdownRow.value = null;
}

function handleWindowChange() {
    if (activeDropdownRow.value) {
        activeDropdownRow.value = null;
    }
}

onMounted(() => {
    window.addEventListener('scroll', handleWindowChange, { passive: true });
    window.addEventListener('resize', handleWindowChange);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleWindowChange);
    window.removeEventListener('resize', handleWindowChange);
});
</script>
