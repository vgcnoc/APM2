<template>
    <AppLayout title="Dashboard" subtitle="Ringkasan statistik jaringan & pelanggan ISP">
        <!-- ═══ Area / Wilayah Smart Filter Bar (Dropdown Selector) ═══ -->
        <div v-can="'dashboard_filter_area'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Left: Title & Active Context Info -->
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-blue-500/20 text-lg shrink-0">
                        🗺️
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-sm font-bold text-gray-900">Filter Wilayah Operasional</h2>
                            <span
                                v-if="selectedAreaId !== 'all'"
                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                📍 {{ currentAreaName }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                🌐 Seluruh Wilayah (Nasional)
                            </span>
                            <span v-if="isFiltering" class="text-[10px] text-blue-600 font-semibold animate-pulse">
                                Memuat data...
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 truncate flex items-center gap-1.5">
                            <span>Menyaring otomatis statistik pelanggan, tagihan, tiket & infrastruktur.</span>
                            <span v-if="selectedAreaId !== 'all'" class="text-blue-600 font-bold">• {{ customerStats?.total_active || 0 }} Pelanggan Aktif</span>
                        </p>
                    </div>
                </div>

                <!-- Right: Sleek Dropdown Selector & Reset -->
                <div class="flex items-center gap-2.5 shrink-0 relative">
                    <!-- Dropdown Container -->
                    <div class="relative" ref="dropdownRef">
                        <button
                            @click="isDropdownOpen = !isDropdownOpen"
                            type="button"
                            :disabled="isFiltering"
                            class="inline-flex items-center justify-between gap-2.5 px-3.5 py-2.5 rounded-xl border text-xs font-bold transition-all shadow-2xs select-none min-w-[200px]"
                            :class="[
                                selectedAreaId !== 'all'
                                    ? 'bg-blue-50/90 border-blue-300 text-blue-800 hover:bg-blue-100'
                                    : 'bg-gray-50 hover:bg-white border-gray-200 text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <span v-if="selectedAreaId === 'all'" class="text-sm">🌐</span>
                                <span v-else class="text-sm">📍</span>
                                <span class="truncate">{{ currentAreaName }}</span>
                            </div>
                            <svg class="w-4 h-4 text-gray-500 shrink-0 transition-transform duration-200" :class="{ 'rotate-180': isDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Floating Popover Menu -->
                        <div
                            v-if="isDropdownOpen"
                            class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50 animate-in fade-in zoom-in-95 duration-150"
                        >
                            <!-- Search box in dropdown if more than 4 areas -->
                            <div v-if="availableAreas && availableAreas.length > 4" class="px-3 pb-2 pt-1 border-b border-gray-100">
                                <div class="relative">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Cari nama wilayah..."
                                        class="w-full text-xs rounded-lg border-gray-200 bg-gray-50 py-1.5 pl-7 pr-3 text-gray-800 focus:bg-white focus:border-blue-500 focus:ring-blue-500"
                                        @click.stop
                                    />
                                    <span class="absolute left-2.5 top-2 text-gray-400 text-xs">🔍</span>
                                    <button
                                        v-if="searchQuery"
                                        @click.stop="searchQuery = ''"
                                        class="absolute right-2 top-1.5 text-gray-400 hover:text-gray-600 text-xs"
                                    >✕</button>
                                </div>
                            </div>

                            <div class="max-h-64 overflow-y-auto px-1 py-1 space-y-0.5">
                                <!-- Option: Semua Wilayah -->
                                <button
                                    @click="selectAndClose('all')"
                                    type="button"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between transition-colors"
                                    :class="selectedAreaId === 'all' ? 'bg-blue-50 font-bold text-blue-700' : 'text-gray-700 hover:bg-gray-50'"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-sm">🌐</span>
                                        <span class="truncate font-semibold">Semua Wilayah (Nasional)</span>
                                    </div>
                                    <span v-if="selectedAreaId === 'all'" class="text-blue-600 font-bold text-sm">✓</span>
                                </button>

                                <div class="my-1 border-t border-gray-100"></div>

                                <!-- Filtered Area Options -->
                                <button
                                    v-for="area in filteredAreas"
                                    :key="area.id"
                                    @click="selectAndClose(area.id)"
                                    type="button"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs font-medium flex items-center justify-between transition-colors"
                                    :class="selectedAreaId == area.id ? 'bg-blue-50 font-bold text-blue-700' : 'text-gray-700 hover:bg-gray-50'"
                                >
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-sm">📍</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold">{{ area.name }}</p>
                                            <p v-if="area.address" class="text-[10px] text-gray-400 truncate">{{ area.address }}</p>
                                        </div>
                                    </div>
                                    <span v-if="selectedAreaId == area.id" class="text-blue-600 font-bold text-sm shrink-0 ml-2">✓</span>
                                </button>

                                <div v-if="filteredAreas.length === 0" class="text-center py-4 text-xs text-gray-400">
                                    Wilayah tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reset to All Button -->
                    <button
                        v-if="selectedAreaId !== 'all'"
                        @click="changeArea('all')"
                        type="button"
                        class="px-3 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center gap-1 active:scale-95"
                        title="Tampilkan Seluruh Wilayah"
                    >
                        <span>✕</span>
                        <span>Reset</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ Presensi Reguler Staf Widget ═══ -->
        <DashboardAttendanceWidget :attendance-data="attendanceData" class="mb-6" />

        <!-- ═══ Stat Cards ═══ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- Pelanggan Aktif -->
            <div v-can="'dashboard_card_stats'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Pelanggan Aktif</p>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ customerStats.total_active }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total {{ customerStats.total_all }} pelanggan</p>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        {{ customerStats.total_all > 0 ? Math.round(customerStats.total_active / customerStats.total_all * 100) : 0 }}%
                    </span>
                </div>
                <div class="mt-3 h-8">
                    <svg class="w-full h-full text-blue-200" viewBox="0 0 120 30" preserveAspectRatio="none">
                        <path d="M0 25 Q20 15 40 20 T80 10 T120 15 V30 H0Z" fill="currentColor" opacity="0.5"/>
                        <path d="M0 25 Q20 15 40 20 T80 10 T120 15" fill="none" stroke="rgb(59,130,246)" stroke-width="1.5"/>
                    </svg>
                </div>
            </div>

            <!-- Pelanggan Sudah Bayar -->
            <div v-can="'dashboard_card_revenue'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Pelanggan Sudah Bayar</p>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ invoiceStats?.paid_this_month || 0 }}</p>
                        <p class="text-xs text-gray-500 mt-1">Bulan Ini</p>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                        {{ ((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) > 0 ? Math.round((invoiceStats?.paid_this_month || 0) / ((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) * 100) : 0 }}%
                    </span>
                </div>
                <div class="mt-3 w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-2 rounded-full transition-all"
                        :style="{ width: (((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) > 0 ? (invoiceStats?.paid_this_month || 0) / ((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) * 100 : 0) + '%' }"></div>
                </div>
            </div>

            <!-- Pelanggan Belum Bayar -->
            <div v-can="'dashboard_card_revenue'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Pelanggan Belum Bayar</p>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ invoiceStats?.unpaid_this_month || 0 }}</p>
                        <p class="text-xs text-gray-500 mt-1">Bulan Ini</p>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded-lg">
                        {{ ((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) > 0 ? Math.round((invoiceStats?.paid_this_month || 0) / ((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) * 100) : 0 }}%
                    </span>
                </div>
                <div class="mt-3 w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-gradient-to-r from-red-400 to-red-600 h-2 rounded-full transition-all"
                        :style="{ width: (((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) > 0 ? (invoiceStats?.paid_this_month || 0) / ((invoiceStats?.paid_this_month || 0) + (invoiceStats?.unpaid_this_month || 0)) * 100 : 0) + '%' }"></div>
                </div>
            </div>

            <!-- Reseller Voucher Belum Bayar -->
            <div v-can="'dashboard_card_revenue'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Reseller Voucher Belum Bayar</p>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">Rp {{ formatCurrency(resellerUnpaidAmount) }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ resellerUnpaidCount || 0 }} tagihan belum lunas</p>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold px-2 py-1 rounded-lg"
                        :class="(resellerUnpaidCount || 0) > 0 ? 'text-amber-700 bg-amber-50 border border-amber-200' : 'text-emerald-700 bg-emerald-50 border border-emerald-200'">
                        {{ (resellerUnpaidCount || 0) > 0 ? (resellerUnpaidCount + ' Invoice') : 'Lunas' }}
                    </span>
                </div>
                <div class="mt-3 flex items-center justify-between pt-2 border-t border-gray-50 text-[11px]">
                    <span class="text-gray-400 font-medium">Kasbon / Piutang</span>
                    <a href="/reseller-billing" class="font-bold text-amber-600 hover:text-amber-700 flex items-center gap-0.5 transition-colors">
                        Penagihan →
                    </a>
                </div>
            </div>
        </div>

        <!-- ═══ Pipeline Pelanggan ═══ -->
        <div v-can="'dashboard_view_stats'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Pipeline Pelanggan
                    </h3>
                    <a href="/customers" class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">
                        Lihat Semua →
                    </a>
                </div>

                <!-- Pipeline Tabs -->
                <div class="flex flex-wrap gap-2 mb-5">
                    <button @click="pipelineTab = 'terbaru'"
                        :class="pipelineTab === 'terbaru' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Terbaru
                    </button>
                    <button v-can="'menu_customers_booking'" @click="pipelineTab = 'booking'"
                        :class="pipelineTab === 'booking' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Booking <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="pipelineTab === 'booking' ? 'bg-white/20' : 'bg-gray-200'">{{ customerStats.total_booking }}</span>
                    </button>
                    <button v-can="'menu_customers_survey'" @click="pipelineTab = 'survey'"
                        :class="pipelineTab === 'survey' ? 'bg-cyan-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Survey <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="pipelineTab === 'survey' ? 'bg-white/20' : 'bg-gray-200'">{{ customerStats.total_survey }}</span>
                    </button>
                    <button v-can="'menu_customers_installed'" @click="pipelineTab = 'instalasi'"
                        :class="pipelineTab === 'instalasi' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Instalasi <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="pipelineTab === 'instalasi' ? 'bg-white/20' : 'bg-gray-200'">{{ customerStats.total_installing }}</span>
                    </button>
                    <button v-can="'menu_customers_activation'" @click="pipelineTab = 'aktivasi'"
                        :class="pipelineTab === 'aktivasi' ? 'bg-purple-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Aktivasi <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="pipelineTab === 'aktivasi' ? 'bg-white/20' : 'bg-gray-200'">{{ customerStats.total_activation }}</span>
                    </button>
                </div>

                <!-- Customer List -->
                <div class="space-y-3 max-h-[340px] overflow-y-auto pr-1">
                    <div v-for="cust in filteredPipeline" :key="cust.id"
                        class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors border border-gray-50">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0"
                                :class="getAvatarColor(cust.initial)">
                                {{ cust.initial }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ cust.name }}</p>
                                <p class="text-[11px] text-gray-400 truncate flex items-center gap-1.5">
                                    <span>{{ cust.package }}</span>
                                    <span v-if="cust.area_name && cust.area_name !== '-'" class="inline-flex items-center text-[10px] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.2 rounded">
                                        📍 {{ cust.area_name }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <p class="text-[10px] text-gray-400 mb-1">{{ cust.created_at }}</p>
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md"
                                :class="getStatusColor(cust.status)">
                                {{ getStatusLabel(cust.status) }}
                            </span>
                        </div>
                    </div>
                    <div v-if="filteredPipeline.length === 0" class="text-center py-8 text-gray-400 text-sm">
                        Tidak ada data untuk tab ini.
                    </div>
                </div>
            </div>

        <!-- ═══ Bottom Row ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Aktivitas Terbaru -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Aktivitas Terbaru
                    </h3>
                    <span class="text-xs text-gray-400 font-medium">Hari Ini</span>
                </div>
                <div class="space-y-3 max-h-[280px] overflow-y-auto">
                    <div v-for="act in recentActivities" :key="act.id"
                        class="flex items-start gap-3 p-2.5 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center text-[10px] font-bold mt-0.5"
                            :class="getActivityColor(act.action)">
                            {{ getActivityIcon(act.action) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-900 font-medium leading-relaxed">
                                <span class="font-bold">{{ act.action }}</span>
                            </p>
                            <p class="text-[11px] text-gray-400 truncate">
                                {{ act.user_name }} · {{ act.entity_type }} · {{ act.created_date }}
                            </p>
                        </div>
                        <span class="text-[10px] text-gray-400 font-mono shrink-0">{{ act.created_at }}</span>
                    </div>
                    <div v-if="recentActivities.length === 0" class="text-center py-6 text-gray-400 text-xs">
                        Belum ada aktivitas.
                    </div>
                </div>
            </div>

            <!-- Distribusi Paket -->
            <div v-can="'dashboard_view_charts'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                        Distribusi Paket Pelanggan
                    </h3>
                    <span class="text-xs text-gray-400 font-medium">Berdasarkan Paket</span>
                </div>

                <div class="flex items-center gap-6">
                    <!-- Donut Chart -->
                    <div class="w-32 h-32 shrink-0 relative">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                            <circle v-for="(seg, idx) in donutSegments" :key="idx"
                                cx="50" cy="50" r="40" fill="none"
                                :stroke="seg.color"
                                stroke-width="12"
                                :stroke-dasharray="seg.dashArray"
                                :stroke-dashoffset="seg.dashOffset"
                                stroke-linecap="round"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold text-gray-900">{{ totalPackageCustomers }}</span>
                            <span class="text-[10px] text-gray-400">Pelanggan</span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="space-y-2 flex-1">
                        <div v-for="(pkg, idx) in packageDistribution.slice(0, 4)" :key="idx"
                            class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: donutColors[idx] }"></span>
                                <span class="text-gray-700 font-medium truncate max-w-[120px]">{{ pkg.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-900 font-bold">{{ pkg.total }}</span>
                                <span class="text-gray-400">({{ totalPackageCustomers > 0 ? (pkg.total / totalPackageCustomers * 100).toFixed(1) : 0 }}%)</span>
                            </div>
                        </div>
                        <div v-if="packageDistribution.length > 4" class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
                                <span class="text-gray-700 font-medium">Lainnya</span>
                            </div>
                            <span class="text-gray-900 font-bold">{{ othersPackageTotal }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 5 Area vs Detail Wilayah Terpilih -->
            <div v-can="'dashboard_card_stats'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <!-- Mode 1: Jika Wilayah Spesifik Dipilih -->
                <template v-if="selectedAreaDetail">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span class="text-lg">📍</span>
                            <span>Wilayah {{ selectedAreaDetail.name }}</span>
                        </h3>
                        <span class="text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded-full">
                            Filter Aktif
                        </span>
                    </div>

                    <div class="space-y-3.5">
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-600 space-y-1">
                            <div class="font-bold text-gray-800 flex items-center gap-1">
                                <span>🏢</span>
                                <span>Alamat / Lokasi:</span>
                            </div>
                            <p class="text-gray-500 leading-relaxed">{{ selectedAreaDetail.address }}</p>
                            <p v-if="selectedAreaDetail.radius_meters > 0" class="text-[11px] text-gray-400">
                                Radius Presensi: {{ selectedAreaDetail.radius_meters }} meter
                            </p>
                        </div>

                        <!-- Mini stats grid -->
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 rounded-xl bg-blue-50/60 border border-blue-100">
                                <span class="text-[10px] font-bold text-blue-600 uppercase">Pelanggan Aktif</span>
                                <div class="text-lg font-black text-blue-900 mt-0.5">
                                    {{ selectedAreaDetail.active_customers }}
                                    <span class="text-xs font-normal text-blue-600">/ {{ selectedAreaDetail.total_customers }}</span>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-emerald-50/60 border border-emerald-100">
                                <span class="text-[10px] font-bold text-emerald-600 uppercase">Infrastruktur ODP</span>
                                <div class="text-lg font-black text-emerald-900 mt-0.5">
                                    {{ selectedAreaDetail.total_odp }}
                                    <span class="text-xs font-normal text-emerald-600">Unit</span>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-purple-50/60 border border-purple-100">
                                <span class="text-[10px] font-bold text-purple-600 uppercase">ODC / Distribusi</span>
                                <div class="text-lg font-black text-purple-900 mt-0.5">
                                    {{ selectedAreaDetail.total_odc }}
                                    <span class="text-xs font-normal text-purple-600">Unit</span>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-xl bg-amber-50/60 border border-amber-100">
                                <span class="text-[10px] font-bold text-amber-600 uppercase">ONT Terpasang</span>
                                <div class="text-lg font-black text-amber-900 mt-0.5">
                                    {{ selectedAreaDetail.total_ont }}
                                    <span class="text-xs font-normal text-amber-600">Unit</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-between border-t border-gray-100 text-xs">
                            <button
                                @click="changeArea('all')"
                                type="button"
                                class="text-gray-500 hover:text-gray-800 font-bold flex items-center gap-1 hover:underline"
                            >
                                ← Tampilkan Semua Area
                            </button>
                            <a
                                :href="'/customers?area_id=' + selectedAreaDetail.id"
                                class="font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1"
                            >
                                Kelola Pelanggan →
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Mode 2: Tampilan Top 5 Area (Semua Wilayah) -->
                <template v-else>
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            Top 5 Area Pelanggan
                        </h3>
                        <span class="text-xs text-gray-400 font-medium">Klik area untuk filter</span>
                    </div>

                    <div class="space-y-3">
                        <div v-for="(area, idx) in topAreas" :key="idx"
                            @click="area.id && changeArea(area.id)"
                            :class="[
                                'flex items-center gap-3 p-1.5 -mx-1.5 rounded-xl transition-colors',
                                area.id ? 'hover:bg-blue-50/70 cursor-pointer group' : ''
                            ]"
                            title="Klik untuk menyaring dashboard ke area ini"
                        >
                            <span class="w-6 h-6 rounded-md flex items-center justify-center text-[10px] font-bold shrink-0"
                                :class="idx < 3 ? 'bg-blue-100 text-blue-700 group-hover:bg-blue-600 group-hover:text-white transition-colors' : 'bg-gray-100 text-gray-600'">
                                {{ idx + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-semibold text-gray-900 group-hover:text-blue-700 transition-colors truncate">
                                        {{ area.name }}
                                        <span v-if="area.id" class="text-[10px] text-blue-500 opacity-0 group-hover:opacity-100 transition-opacity ml-1">📍 Filter</span>
                                    </span>
                                    <span class="text-[10px] text-gray-400 shrink-0 ml-2">{{ area.total }} pelanggan</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full transition-all"
                                        :class="idx === 0 ? 'bg-blue-500' : idx === 1 ? 'bg-blue-400' : idx === 2 ? 'bg-blue-300' : 'bg-blue-200'"
                                        :style="{ width: (totalCustomersInAreas > 0 ? area.total / totalCustomersInAreas * 100 : 0) + '%' }"></div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-gray-600 shrink-0 w-12 text-right">{{ totalCustomersInAreas > 0 ? (area.total / totalCustomersInAreas * 100).toFixed(1) : 0 }}%</span>
                        </div>
                        <div v-if="topAreas.length === 0" class="text-center py-6 text-gray-400 text-xs">
                            Belum ada data area.
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DashboardAttendanceWidget from '@/Components/Attendance/DashboardAttendanceWidget.vue';

const props = defineProps({
    customerStats: Object,
    infraStats: Object,
    ticketStats: Object,
    invoiceStats: Object,
    resellerUnpaidCount: { type: Number, default: 0 },
    resellerUnpaidAmount: { type: Number, default: 0 },
    monthlyRevenue: Number,
    totalRevenue: Number,
    avgMonthlyRevenue: Number,
    totalInvoices: Number,
    newCustomersThisMonth: Number,
    revenueChart: Array,
    recentCustomers: Array,
    recentTickets: Array,
    recentActivities: Array,
    packageDistribution: Array,
    topAreas: Array,
    totalCustomersInAreas: Number,
    attendanceData: { type: Object, default: () => ({}) },
    selectedAreaId: { type: [String, Number], default: 'all' },
    availableAreas: { type: Array, default: () => [] },
    selectedAreaDetail: { type: Object, default: null },
    canViewAllAreas: { type: Boolean, default: true },
    canFilterArea: { type: Boolean, default: true },
});

// ── Area Filter State & Action ────────────────────
const isFiltering = ref(false);
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);
const searchQuery = ref('');

const currentAreaName = computed(() => {
    if (props.selectedAreaId === 'all') return 'Semua Wilayah (Nasional)';
    const found = props.availableAreas?.find((a) => a.id == props.selectedAreaId);
    return found ? found.name : `Wilayah #${props.selectedAreaId}`;
});

const filteredAreas = computed(() => {
    if (!searchQuery.value) return props.availableAreas || [];
    const q = searchQuery.value.toLowerCase().trim();
    return (props.availableAreas || []).filter(
        (a) => a.name?.toLowerCase().includes(q) || (a.address && a.address.toLowerCase().includes(q))
    );
});

const selectAndClose = (areaId) => {
    isDropdownOpen.value = false;
    searchQuery.value = '';
    changeArea(areaId);
};

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isDropdownOpen.value = false;
    }
};

const changeArea = (newAreaId) => {
    if (props.selectedAreaId == newAreaId && !isFiltering.value) return;
    isFiltering.value = true;
    try {
        localStorage.setItem('dashboard_selected_area', String(newAreaId));
    } catch (e) {}

    router.get(
        '/',
        { area_id: newAreaId },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isFiltering.value = false;
            },
        }
    );
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    try {
        const saved = localStorage.getItem('dashboard_selected_area');
        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('area_id') && saved && saved !== String(props.selectedAreaId)) {
            const isValid = saved === 'all' || props.availableAreas?.some((a) => String(a.id) === saved);
            if (isValid) {
                changeArea(saved);
            }
        }
    } catch (e) {}
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// ── Pipeline Tab ──────────────────────────────────
const pipelineTab = ref('terbaru');

const filteredPipeline = computed(() => {
    if (pipelineTab.value === 'terbaru') return props.recentCustomers;
    const statusMap = {
        booking: 'booking',
        survey: 'survey',
        instalasi: 'installing',
        aktivasi: 'menunggu_aktivasi',
    };
    const filterStatus = statusMap[pipelineTab.value];
    return props.recentCustomers.filter(c => c.status === filterStatus);
});

function formatCurrency(val) {
    return Number(val || 0).toLocaleString('id-ID');
}

// ── Donut Chart ───────────────────────────────────
const donutColors = ['#3b82f6', '#06b6d4', '#f59e0b', '#8b5cf6', '#6b7280'];

const totalPackageCustomers = computed(() => props.packageDistribution?.reduce((sum, p) => sum + p.total, 0) || 0);

const othersPackageTotal = computed(() => {
    if (!props.packageDistribution || props.packageDistribution.length <= 4) return 0;
    return props.packageDistribution.slice(4).reduce((sum, p) => sum + p.total, 0);
});

const donutSegments = computed(() => {
    if (!props.packageDistribution?.length || totalPackageCustomers.value === 0) return [];
    const circumference = 2 * Math.PI * 40; // ~251.3
    let offset = 0;
    return props.packageDistribution.slice(0, 5).map((pkg, idx) => {
        const pct = pkg.total / totalPackageCustomers.value;
        const length = pct * circumference;
        const gap = 4; // gap between segments
        const seg = {
            color: donutColors[idx] || donutColors[4],
            dashArray: `${Math.max(0, length - gap)} ${circumference - length + gap}`,
            dashOffset: -offset,
        };
        offset += length;
        return seg;
    });
});

// ── Helpers ───────────────────────────────────────
function getAvatarColor(initial) {
    const colors = {
        A: 'bg-blue-100 text-blue-700', B: 'bg-emerald-100 text-emerald-700', C: 'bg-amber-100 text-amber-700',
        D: 'bg-violet-100 text-violet-700', E: 'bg-rose-100 text-rose-700', F: 'bg-cyan-100 text-cyan-700',
        G: 'bg-indigo-100 text-indigo-700', H: 'bg-pink-100 text-pink-700', I: 'bg-teal-100 text-teal-700',
        J: 'bg-orange-100 text-orange-700', K: 'bg-lime-100 text-lime-700', L: 'bg-sky-100 text-sky-700',
        M: 'bg-fuchsia-100 text-fuchsia-700', N: 'bg-red-100 text-red-700',
    };
    return colors[initial] || 'bg-gray-100 text-gray-700';
}

function getStatusColor(status) {
    const map = {
        booking: 'bg-yellow-100 text-yellow-700',
        survey: 'bg-cyan-100 text-cyan-700',
        installing: 'bg-indigo-100 text-indigo-700',
        active: 'bg-emerald-100 text-emerald-700',
        menunggu_aktivasi: 'bg-purple-100 text-purple-700',
        suspended: 'bg-orange-100 text-orange-700',
        terminated: 'bg-red-100 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
}

function getStatusLabel(status) {
    const map = {
        booking: 'BOOKING', survey: 'SURVEY', installing: 'INSTALASI',
        active: 'AKTIF', menunggu_aktivasi: 'AKTIVASI',
        suspended: 'SUSPENDED', terminated: 'TERMINATED',
    };
    return map[status] || status?.toUpperCase();
}


function getActivityColor(action) {
    if (action?.includes('Login') || action?.includes('login')) return 'bg-emerald-100 text-emerald-600';
    if (action?.includes('ditambahkan') || action?.includes('create') || action?.includes('Tambah')) return 'bg-blue-100 text-blue-600';
    if (action?.includes('diubah') || action?.includes('update') || action?.includes('Ubah')) return 'bg-amber-100 text-amber-600';
    if (action?.includes('dihapus') || action?.includes('delete') || action?.includes('Hapus')) return 'bg-red-100 text-red-600';
    return 'bg-gray-100 text-gray-600';
}

function getActivityIcon(action) {
    if (action?.includes('Login') || action?.includes('login')) return '🔑';
    if (action?.includes('ditambahkan') || action?.includes('create') || action?.includes('Tambah')) return '➕';
    if (action?.includes('diubah') || action?.includes('update') || action?.includes('Ubah')) return '✏️';
    if (action?.includes('dihapus') || action?.includes('delete') || action?.includes('Hapus')) return '🗑️';
    return '📋';
}
</script>
