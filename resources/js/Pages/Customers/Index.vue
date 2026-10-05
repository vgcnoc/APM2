<template>
    <AppLayout title="Data Pelanggan" subtitle="Kelola semua data pelanggan ISP">
        <!-- STATS SECTION -->
        <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Pelanggan -->
            <div class="glass-card p-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center">
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
            <div class="glass-card p-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-400 flex items-center justify-center">
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
            <div class="glass-card p-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-yellow-400 flex items-center justify-center">
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
            <div class="glass-card p-5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-red-400 flex items-center justify-center">
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
            searchRoute="/customers"
            selectable
            v-model:selected="selectedIds"
        >
            <!-- Filter Slot -->
            <template #filters>
                <!-- Status -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[150px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Status</span>
                        <select v-model="filterStatus" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                            <option value="">Semua Status</option>
                            <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Paket -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[150px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Paket</span>
                        <select v-model="filterPackage" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                            <option value="">Semua Paket</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Area -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[150px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Area</span>
                        <select v-model="filterArea" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                            <option value="">Semua Area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- ODP -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[150px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">ODP</span>
                        <select v-model="filterOdp" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                            <option value="">Semua ODP</option>
                            <option v-for="odp in odps" :key="odp.id" :value="odp.id">{{ odp.name }}</option>
                        </select>
                    </div>
                </div>

                <!-- Urutkan -->
                <div class="relative flex items-center bg-white border border-gray-200 rounded-lg pl-3 pr-2 py-1 shadow-sm w-full md:w-auto md:min-w-[150px]">
                    <div class="shrink-0 mr-2 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    </div>
                    <div class="flex flex-col flex-1">
                        <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider mb-0.5 leading-none mt-1">Urutkan</span>
                        <select v-model="filterSort" @change="applyFilters" class="form-select border-0 p-0 h-auto text-sm bg-transparent focus:ring-0 text-gray-700 font-medium w-full pb-1">
                            <option value="">Terbaru</option>
                            <option value="terlama">Terlama</option>
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
                <Link href="/customers/create" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Pelanggan
                </Link>
            </template>

            <!-- Table Rows -->
            <template #row="{ row, index }">
                <td class="text-gray-500 text-xs text-center">
                    {{ (customers.current_page - 1) * customers.per_page + index + 1 }}
                </td>
                <td>
                    <div class="flex items-center gap-3">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0', getAvatarColor(row.name)]">
                            {{ row.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <Link :href="`/customers/${row.id}`" class="text-gray-900 font-semibold text-sm hover:text-blue-500 transition-colors">
                                {{ row.name }}
                            </Link>
                            <p class="text-[10px] text-gray-500 font-mono">{{ row.customer_code }}</p>
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
                    <div v-if="row.package" class="flex items-center gap-1.5 text-blue-500 text-xs font-bold whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/></svg>
                        {{ row.package.name }}
                    </div>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
                <td>
                    <div class="flex flex-col gap-1 items-start">
                        <StatusBadge :status="row.status" />
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
                    <span v-if="row.ont" class="px-2 py-1 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded text-[10px] font-bold whitespace-nowrap uppercase">
                        {{ row.ont.odp?.name || '-' }}
                    </span>
                    <span v-else class="text-xs text-gray-400">-</span>
                </td>
            </template>

            <!-- Row Actions -->
            <template #rowActions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <button v-if="row.status === 'active' && row.ont?.pppoe_user && onlineUsernames?.includes(row.ont.pppoe_user)" @click="kickSession(row.ont.pppoe_user)" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-bold text-red-500 bg-red-50 hover:bg-red-500 hover:text-white transition-colors" title="Kick Sesi Online">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        KICK
                    </button>
                    <Link :href="`/customers/${row.id}`" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-blue-500 transition-colors shadow-sm bg-white" title="Lihat">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </Link>
                    <Link :href="`/customers/${row.id}/edit`" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm bg-white" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </Link>
                    <button v-if="!row.user_id" @click="openCreateAccountModal(row)" class="p-1.5 rounded-md border border-gray-200 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors shadow-sm bg-white" title="Buat Akun Login">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </button>
                    <button v-if="row.user_id" @click="openResetModal(row)" class="p-1.5 rounded-md border border-gray-200 text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition-colors shadow-sm bg-white" title="Ubah Password Akun">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </button>
                    <button @click="confirmDelete(row)" class="p-1.5 rounded-md border border-gray-200 text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors shadow-sm bg-white ml-1" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    <button class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm bg-white ml-1" title="Lainnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                    </button>
                </div>
            </template>
        </DataTable>

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
                                <input v-model="accountForm.password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required minlength="8" placeholder="Minimal 8 karakter" />
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru <span class="text-red-500">*</span></label>
                                <input v-model="resetPasswordForm.password" type="text" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-2.5 shadow-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all" minlength="8" placeholder="Kosongkan jika tidak ingin mengubah password" />
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

    </AppLayout>
</template>

<script setup>
import { ref , computed} from 'vue';
import { Link, router , usePage, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    customers: Object,
    packages: Array,
    areas: Array,
    odps: Array,
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

function getAvatarColor(name) {
    const colors = ['bg-indigo-500', 'bg-blue-500', 'bg-emerald-500', 'bg-purple-500', 'bg-pink-500', 'bg-orange-500'];
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

const filterStatus = ref(props.filters?.status || '');
const filterPackage = ref(props.filters?.package_id || '');
const filterArea = ref(props.filters?.area_id || '');
const filterOdp = ref(props.filters?.odp_id || '');
const filterSort = ref(props.filters?.sort_by || '');

function applyFilters() {
    router.get('/customers', {
        status: filterStatus.value || undefined,
        package_id: filterPackage.value || undefined,
        area_id: filterArea.value || undefined,
        odp_id: filterOdp.value || undefined,
        sort_by: filterSort.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterStatus.value = '';
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
    router.post(`/customers/${deletingCustomer.value.id}/delete`, {
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
    accountForm.email = customer.email || '';
    accountForm.password = '';
    showCreateAccountModal.value = true;
}

function submitCreateAccount() {
    accountForm.post(`/customers/${accountCustomer.value.id}/create-account`, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateAccountModal.value = false;
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
    showResetPasswordModal.value = true;
}

function submitResetPassword() {
    resetPasswordForm.post(`/customers/${resetCustomer.value.id}/reset-password`, {
        preserveScroll: true,
        onSuccess: () => {
            showResetPasswordModal.value = false;
        }
    });
}
</script>
