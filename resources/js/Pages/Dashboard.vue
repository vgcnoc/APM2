<template>
    <AppLayout title="Dashboard" subtitle="Ringkasan statistik jaringan & pelanggan ISP">
        <!-- ═══ Stat Cards ═══ -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- Pelanggan Aktif -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
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

            <!-- ODP Tersedia -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">ODP Tersedia</p>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ infraStats.odp_active }}</p>
                        <p class="text-xs text-gray-500 mt-1">Dari {{ infraStats.odp_total }} ODP</p>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                        {{ infraStats.odp_total > 0 ? Math.round(infraStats.odp_active / infraStats.odp_total * 100) : 100 }}%
                    </span>
                </div>
                <div class="mt-3 w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-2 rounded-full transition-all"
                        :style="{ width: (infraStats.odp_total > 0 ? infraStats.odp_active / infraStats.odp_total * 100 : 100) + '%' }"></div>
                </div>
            </div>

            <!-- Tiket Gangguan -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-red-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Tiket Gangguan</p>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ ticketStats.open }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ ticketStats.in_progress }} sedang dikerjakan</p>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold px-2 py-1 rounded-lg"
                        :class="ticketStats.open > 0 ? 'text-red-600 bg-red-50' : 'text-emerald-600 bg-emerald-50'">
                        {{ ticketStats.open > 0 ? ticketStats.open + ' open' : '0%' }}
                    </span>
                </div>
                <div class="mt-3 h-8">
                    <svg class="w-full h-full text-red-200" viewBox="0 0 120 30" preserveAspectRatio="none">
                        <path d="M0 20 Q30 25 60 15 T120 20 V30 H0Z" fill="currentColor" opacity="0.5"/>
                        <path d="M0 20 Q30 25 60 15 T120 20" fill="none" stroke="rgb(239,68,68)" stroke-width="1.5"/>
                    </svg>
                </div>
            </div>

            <!-- Pendapatan Bulan Ini -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-8 h-8 rounded-xl bg-violet-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <p class="text-sm font-medium text-gray-500">Pendapatan Bulan Ini</p>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 mt-2">Rp {{ formatCurrency(monthlyRevenue) }}</p>
                        <p class="text-xs text-gray-500 mt-1">Dari {{ totalInvoices }} transaksi</p>
                    </div>
                    <span class="inline-flex items-center text-xs font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded-lg">0%</span>
                </div>
                <div class="mt-3 h-8">
                    <svg class="w-full h-full text-violet-200" viewBox="0 0 120 30" preserveAspectRatio="none">
                        <path d="M0 22 Q15 18 30 20 T60 12 T90 18 T120 14 V30 H0Z" fill="currentColor" opacity="0.5"/>
                        <path d="M0 22 Q15 18 30 20 T60 12 T90 18 T120 14" fill="none" stroke="rgb(139,92,246)" stroke-width="1.5"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- ═══ Revenue Chart + Pipeline ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">
            <!-- Revenue Chart -->
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Pendapatan 6 Bulan Terakhir
                    </h3>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg font-medium">6 Bulan Terakhir</span>
                        <span class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg font-medium">Semua Layanan</span>
                    </div>
                </div>

                <!-- Chart Area -->
                <div class="relative h-52 mb-4">
                    <!-- Y-axis labels -->
                    <div class="absolute left-0 top-0 bottom-0 w-20 flex flex-col justify-between text-[10px] text-gray-400 font-medium pr-2 text-right">
                        <span>Rp {{ formatShort(maxRevenue) }}</span>
                        <span>Rp {{ formatShort(maxRevenue * 0.75) }}</span>
                        <span>Rp {{ formatShort(maxRevenue * 0.5) }}</span>
                        <span>Rp {{ formatShort(maxRevenue * 0.25) }}</span>
                        <span>Rp 0</span>
                    </div>
                    <!-- Bars -->
                    <div class="ml-20 h-full flex items-end gap-2">
                        <div v-for="(item, idx) in revenueChart" :key="idx"
                            class="flex-1 flex flex-col items-center justify-end h-full relative group">
                            <!-- Grid line -->
                            <div class="absolute inset-0 border-b border-dashed border-gray-100"></div>
                            <!-- Tooltip -->
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 hidden group-hover:block bg-gray-800 text-white text-[10px] px-2 py-1 rounded-md whitespace-nowrap z-10 font-medium">
                                Rp {{ formatCurrency(item.total) }}
                            </div>
                            <!-- Bar -->
                            <div class="w-full max-w-[40px] rounded-t-lg transition-all duration-700 ease-out relative z-10"
                                :class="idx === revenueChart.length - 1 ? 'bg-gradient-to-t from-blue-600 to-blue-400' : 'bg-gradient-to-t from-blue-200 to-blue-100'"
                                :style="{ height: getBarHeight(item.total) + '%', minHeight: '4px' }">
                            </div>
                        </div>
                    </div>
                </div>
                <!-- X-axis labels -->
                <div class="ml-20 flex gap-2">
                    <div v-for="(item, idx) in revenueChart" :key="'label'+idx" class="flex-1 text-center">
                        <span class="text-[10px] text-gray-400 font-medium">{{ getMonthLabel(item.period_month) }} {{ item.period_year }}</span>
                    </div>
                </div>

                <!-- Revenue Summary -->
                <div class="grid grid-cols-3 gap-4 mt-6 pt-5 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Total Pendapatan</p>
                        <p class="text-lg font-bold text-gray-900">Rp {{ formatCurrency(totalRevenue) }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            <span class="text-emerald-500">↑ 0%</span> vs periode sebelumnya
                        </p>
                    </div>
                    <div class="text-center border-x border-gray-100">
                        <p class="text-xs text-gray-500 mb-1">Rata-rata Bulanan</p>
                        <p class="text-lg font-bold text-gray-900">Rp {{ formatCurrency(avgMonthlyRevenue) }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ totalInvoices }} transaksi</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500 mb-1">Pelanggan Baru</p>
                        <p class="text-lg font-bold text-gray-900">{{ newCustomersThisMonth }}</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">
                            <span class="text-emerald-500">↑ 0%</span> vs periode sebelumnya
                        </p>
                    </div>
                </div>
            </div>

            <!-- Pipeline Pelanggan -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
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
                    <button @click="pipelineTab = 'booking'"
                        :class="pipelineTab === 'booking' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Booking <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="pipelineTab === 'booking' ? 'bg-white/20' : 'bg-gray-200'">{{ customerStats.total_booking }}</span>
                    </button>
                    <button @click="pipelineTab = 'survey'"
                        :class="pipelineTab === 'survey' ? 'bg-cyan-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Survey <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="pipelineTab === 'survey' ? 'bg-white/20' : 'bg-gray-200'">{{ customerStats.total_survey }}</span>
                    </button>
                    <button @click="pipelineTab = 'instalasi'"
                        :class="pipelineTab === 'instalasi' ? 'bg-indigo-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-colors">
                        Instalasi <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px]" :class="pipelineTab === 'instalasi' ? 'bg-white/20' : 'bg-gray-200'">{{ customerStats.total_installing }}</span>
                    </button>
                    <button @click="pipelineTab = 'aktivasi'"
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
                                <p class="text-[11px] text-gray-400 truncate">{{ cust.package }}</p>
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
        </div>

        <!-- ═══ Tickets + Infra Status ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Tiket Gangguan Terbaru -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tiket Gangguan Terbaru
                    </h3>
                    <a href="/tickets" class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">Lihat Semua →</a>
                </div>

                <div v-if="recentTickets.length > 0" class="overflow-x-auto -mx-2">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left px-3 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">No. Tiket</th>
                                <th class="text-left px-3 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pelanggan</th>
                                <th class="text-left px-3 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kategori</th>
                                <th class="text-left px-3 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Prioritas</th>
                                <th class="text-left px-3 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="text-left px-3 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Teknisi</th>
                                <th class="text-left px-3 py-2.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider">Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ticket in recentTickets" :key="ticket.id" class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                <td class="px-3 py-3 font-mono text-xs text-blue-600 font-medium">{{ ticket.ticket_number }}</td>
                                <td class="px-3 py-3 text-xs text-gray-900 font-medium">{{ ticket.customer_name }}</td>
                                <td class="px-3 py-3 text-xs text-gray-600 capitalize">{{ ticket.category?.replace('_', ' ') }}</td>
                                <td class="px-3 py-3">
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-md" :class="priorityClass(ticket.priority)">{{ ticket.priority }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-md" :class="ticketStatusClass(ticket.status)">{{ ticket.status?.replace('_', ' ') }}</span>
                                </td>
                                <td class="px-3 py-3 text-xs text-gray-600">{{ ticket.assignee_name || '-' }}</td>
                                <td class="px-3 py-3 text-xs text-gray-400">{{ ticket.created_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-10">
                    <div class="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-700 mb-1">Tidak ada tiket terbuka</p>
                    <p class="text-xs text-gray-400 mb-4">Semua layanan berjalan normal saat ini.</p>
                    <a href="/tickets" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Buat Tiket Baru
                    </a>
                </div>
            </div>

            <!-- Status Infrastruktur -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                        Status Infrastruktur
                    </h3>
                    <a href="/network-data" class="text-xs text-blue-600 hover:text-blue-700 font-medium flex items-center gap-1">Lihat Detail →</a>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- OLT -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                                </div>
                                <span class="text-xs font-bold text-gray-700">OLT Online</span>
                            </div>
                            <div class="w-10 h-10 relative">
                                <svg viewBox="0 0 36 36" class="w-10 h-10 -rotate-90">
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#3b82f6" stroke-width="3"
                                        :stroke-dasharray="97.4"
                                        :stroke-dashoffset="97.4 - (infraStats.olt_total > 0 ? infraStats.olt_active / infraStats.olt_total : 1) * 97.4"
                                        stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xl font-bold text-gray-900">{{ infraStats.olt_active }} / {{ infraStats.olt_total }}</p>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                            <span class="text-[10px] text-gray-500">Semua Normal</span>
                        </div>
                    </div>

                    <!-- ODC -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <span class="text-xs font-bold text-gray-700">ODC Aktif</span>
                            </div>
                            <div class="w-10 h-10 relative">
                                <svg viewBox="0 0 36 36" class="w-10 h-10 -rotate-90">
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#f59e0b" stroke-width="3"
                                        :stroke-dasharray="97.4"
                                        :stroke-dashoffset="97.4 - (infraStats.odc_total > 0 ? infraStats.odc_active / infraStats.odc_total : 1) * 97.4"
                                        stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xl font-bold text-gray-900">{{ infraStats.odc_active }} / {{ infraStats.odc_total }}</p>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                            <span class="text-[10px] text-gray-500">Normal</span>
                        </div>
                    </div>

                    <!-- ODP -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <span class="text-xs font-bold text-gray-700">ODP Aktif</span>
                            </div>
                            <div class="w-10 h-10 relative">
                                <svg viewBox="0 0 36 36" class="w-10 h-10 -rotate-90">
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#10b981" stroke-width="3"
                                        :stroke-dasharray="97.4"
                                        :stroke-dashoffset="97.4 - (infraStats.odp_total > 0 ? infraStats.odp_active / infraStats.odp_total : 1) * 97.4"
                                        stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xl font-bold text-gray-900">{{ infraStats.odp_active }} / {{ infraStats.odp_total }}</p>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                            <span class="text-[10px] text-gray-500">Normal</span>
                        </div>
                    </div>

                    <!-- ONT -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0"/></svg>
                                </div>
                                <span class="text-xs font-bold text-gray-700">ONT Online</span>
                            </div>
                            <div class="w-10 h-10 relative">
                                <svg viewBox="0 0 36 36" class="w-10 h-10 -rotate-90">
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#8b5cf6" stroke-width="3"
                                        :stroke-dasharray="97.4"
                                        :stroke-dashoffset="97.4 - (infraStats.ont_total > 0 ? infraStats.ont_active / infraStats.ont_total : 1) * 97.4"
                                        stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xl font-bold text-gray-900">{{ infraStats.ont_active }} / {{ infraStats.ont_total }}</p>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full"></span>
                            <span class="text-[10px] text-gray-500">Normal</span>
                        </div>
                    </div>
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
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
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

            <!-- Top 5 Area -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Top 5 Area Pelanggan
                    </h3>
                    <span class="text-xs text-gray-400 font-medium">Semua Area</span>
                </div>

                <div class="space-y-3">
                    <div v-for="(area, idx) in topAreas" :key="idx"
                        class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-md flex items-center justify-center text-[10px] font-bold shrink-0"
                            :class="idx < 3 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'">
                            {{ idx + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-gray-900 truncate">{{ area.name }}</span>
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
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    customerStats: Object,
    infraStats: Object,
    ticketStats: Object,
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

// ── Revenue Chart ─────────────────────────────────
const maxRevenue = computed(() => {
    if (!props.revenueChart?.length) return 5000000;
    const max = Math.max(...props.revenueChart.map(r => r.total));
    return max > 0 ? max : 5000000;
});

function getBarHeight(val) {
    return maxRevenue.value > 0 ? Math.max(3, (val / maxRevenue.value) * 90) : 3;
}

function getMonthLabel(m) {
    const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return months[m] || '';
}

function formatCurrency(val) {
    return Number(val || 0).toLocaleString('id-ID');
}

function formatShort(val) {
    if (val >= 1000000) return (val / 1000000).toFixed(1) + ' Jt';
    if (val >= 1000) return (val / 1000).toFixed(0) + ' Rb';
    return val?.toFixed(0) || '0';
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

function priorityClass(p) {
    const map = {
        low: 'bg-gray-100 text-gray-600',
        medium: 'bg-blue-100 text-blue-600',
        high: 'bg-orange-100 text-orange-600',
        critical: 'bg-red-100 text-red-600',
    };
    return map[p] || map.medium;
}

function ticketStatusClass(s) {
    const map = {
        open: 'bg-red-100 text-red-600',
        in_progress: 'bg-amber-100 text-amber-600',
        resolved: 'bg-emerald-100 text-emerald-600',
        closed: 'bg-gray-100 text-gray-600',
    };
    return map[s] || map.open;
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
