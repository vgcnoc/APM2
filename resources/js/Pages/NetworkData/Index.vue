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
                <div class="flex items-center gap-2 flex-wrap">
                    <Link v-if="can('menu_network_data_terminated') || can('menu_cbp')" :href="route('network-data.terminated')" class="px-3.5 py-2 bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 rounded-lg text-sm font-bold shadow-xs flex items-center gap-1.5 transition-colors">
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        <span>Cabut Perangkat (Stop)</span>
                        <span v-if="stats.proses_cbp > 0" class="bg-rose-600 text-white text-[10px] px-1.5 py-0.2 rounded-full font-black">
                            {{ stats.proses_cbp }}
                        </span>
                    </Link>
                    <Link href="/network-topology" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        Lihat Topology
                    </Link>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
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
                    <div class="flex items-baseline justify-between flex-wrap gap-1">
                        <h3 class="text-3xl font-black text-gray-800">{{ formatNumber(stats.pelanggan_aktif) }}</h3>
                        <span v-if="stats.pelanggan_gratis > 0" class="text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full flex items-center gap-1 shadow-xs" title="Pelanggan Gratis Aktif">
                            <span>🎁</span> {{ stats.pelanggan_gratis }} Gratis
                        </span>
                    </div>
                    <p class="text-xs text-amber-600 mt-2 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Online Users
                    </p>
                </div>
                <div @click="showTerminated = true" class="bg-white p-5 rounded-xl border border-rose-100 shadow-[0_2px_10px_-3px_rgba(244,63,94,0.08)] relative overflow-hidden group hover:border-rose-300 transition-all cursor-pointer">
                    <div class="absolute -right-4 -top-4 w-16 h-16 bg-rose-50 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
                    <p class="text-[10px] font-bold text-rose-500 uppercase tracking-wider mb-1 flex items-center justify-between">
                        <span>Proses CBP & Stop</span>
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                    </p>
                    <div class="flex items-baseline gap-1.5">
                        <h3 class="text-3xl font-black text-rose-600">{{ formatNumber(stats.proses_cbp || 0) }}</h3>
                        <span class="text-xs font-semibold text-gray-400">/ {{ formatNumber(stats.stop_permanen || 0) }} Stop</span>
                    </div>
                    <p class="text-xs text-rose-600 mt-2 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Belum Cabut Perangkat
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
                    <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 w-full">
                        <select v-model="form.olt_id" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua Server</option>
                            <option v-for="olt in olts" :key="olt.id" :value="olt.id">{{ olt.name }}</option>
                        </select>
                        <select v-model="form.odc_id" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua ODC</option>
                            <option v-for="odc in filteredOdcs" :key="odc.id" :value="odc.id">{{ formatOdcDropdownLabel(odc) }}</option>
                        </select>
                        <select v-model="form.odp_id" @change="applyFilters" class="w-full border-gray-200 rounded-lg text-sm text-gray-700 py-2 focus:ring-1 focus:ring-indigo-500">
                            <option value="">Semua ODP</option>
                            <option v-for="odp in filteredOdpsDropdown" :key="odp.id" :value="odp.id">{{ formatOdpDropdownLabel(odp) }}</option>
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

            <!-- Anchor for smooth scrolling -->
            <div id="network-data-container" class="scroll-mt-6"></div>

            <!-- CARD VIEW -->
            <div v-if="viewMode === 'card'" class="space-y-8">
                <div v-for="unit in paginatedOdcUnits" :key="unit.key" class="animate-fade-in-up">
                    <!-- CONNECTED ODC SPLIT PAIR FRAME (Berdampingan Induk <-> Lanjutan) -->
                    <div
                        v-if="unit.isSplitPair"
                        class="rounded-2xl border-2 border-purple-400/90 bg-gradient-to-br from-emerald-50/50 via-white to-purple-50/60 p-3.5 md:p-4 shadow-md space-y-4"
                    >
                        <!-- Top Relationship Header Banner -->
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-[#111827] via-[#1e1b4b] to-[#2e1065] text-white shadow-sm border border-purple-500/30">
                            <div class="flex items-center flex-wrap gap-2 text-xs">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-400/20 border border-amber-300/40 text-amber-200 font-black uppercase tracking-wider text-[10px]">
                                    🔗 PASANGAN ODC SPLIT SALING BERHUBUNGAN
                                </span>

                                <template v-for="(item, idx) in unit.items" :key="'odc-chip-' + item.odc.id">
                                    <span v-if="idx > 0" class="text-amber-300 font-black px-1 text-xs" title="Terhubung Splitter / Kabel Lanjutan">
                                        ━━⚡ SPLIT━━&gt;
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold text-[11px] border"
                                        :class="idx === 0 ? 'bg-emerald-500/25 border-emerald-400/50 text-emerald-100' : 'bg-purple-500/25 border-purple-400/50 text-purple-100'"
                                    >
                                        <span>{{ idx === 0 ? '🟢 INDUK:' : '🟣 LANJUTAN:' }}</span>
                                        <strong class="text-white">{{ item.odc.name }}</strong>
                                        <span class="text-[10px] opacity-80">(PON {{ item.odc.pon_port || '?' }})</span>
                                    </span>
                                </template>
                            </div>

                            <div class="flex items-center flex-wrap gap-2 shrink-0 text-[11px] font-bold">
                                <span class="px-2.5 py-1 rounded-lg bg-white/10 border border-white/15 text-purple-100">
                                    {{ getOdcUnitTotalOdps(unit.items) }} Total ODP
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 border border-emerald-400/40 text-emerald-200">
                                    {{ getOdcUnitFreePorts(unit.items) }} Free Port Gabungan
                                </span>
                            </div>
                        </div>

                        <!-- Side-by-Side ODC Cards -->
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 xl:gap-6 relative items-stretch">
                            <!-- Floating Center Optical Connector Badge (Desktop XL) -->
                            <div
                                v-if="unit.items.length === 2"
                                class="hidden xl:flex absolute left-1/2 top-16 -translate-x-1/2 z-20 pointer-events-none"
                            >
                                <div class="px-3 py-1.5 rounded-full bg-gradient-to-r from-emerald-600 via-indigo-600 to-purple-600 text-white text-[10px] font-black shadow-lg border-2 border-white flex items-center gap-1.5 tracking-wider">
                                    <span>INDUK</span>
                                    <span>⇄</span>
                                    <span>LANJUTAN</span>
                                </div>
                            </div>

                            <template v-for="(item, idx) in unit.items" :key="item.odc.id">
                                <!-- Mobile/Tablet vertical connector -->
                                <div v-if="idx > 0" class="flex xl:hidden items-center justify-center -my-2 z-10">
                                    <span class="px-3 py-1 rounded-full bg-gradient-to-r from-emerald-600 to-purple-600 text-white text-[10px] font-black shadow border border-white">
                                        ⬇ TERHUBUNG SPLIT DARI ODC INDUK ⬇
                                    </span>
                                </div>

                                <OdcGroupCard
                                    :odc-group="item"
                                    :split-info="item.splitInfo"
                                    :is-inside-split-pair="unit.items.length >= 2"
                                    :pon-vlans="getPonVlans(item.odc)"
                                    :get-odp-split-info-fn="getOdpSplitInfo"
                                    :get-odp-pon-vlans-fn="getOdpPonVlans"
                                    :cbp-requests="cbp_requests"
                                    @open-photo="openOdpDetail"
                                />
                            </template>
                        </div>
                    </div>

                    <!-- NORMAL / STANDALONE ODC GROUP -->
                    <OdcGroupCard
                        v-else
                        :odc-group="unit.items[0]"
                        :split-info="unit.items[0].splitInfo"
                        :is-inside-split-pair="false"
                        :pon-vlans="getPonVlans(unit.items[0].odc)"
                        :get-odp-split-info-fn="getOdpSplitInfo"
                        :get-odp-pon-vlans-fn="getOdpPonVlans"
                        :cbp-requests="cbp_requests"
                        @open-photo="openOdpDetail"
                    />
                </div>

                <!-- Pagination Footer Card View -->
                <div v-if="odcDisplayUnits.length > 0" class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center flex-wrap gap-4 text-xs text-gray-600">
                        <div>
                            Menampilkan <span class="font-bold text-gray-900">{{ cardPaginationInfo.from }}</span> -
                            <span class="font-bold text-gray-900">{{ cardPaginationInfo.to }}</span> dari
                            <span class="font-bold text-gray-900">{{ cardPaginationInfo.total }}</span> ODC
                            <span class="text-gray-400 ml-1">({{ odps.length }} total ODP)</span>
                        </div>
                        <div class="flex items-center gap-1.5 border-l border-gray-200 pl-4">
                            <span class="text-gray-500 font-medium">Tampilkan:</span>
                            <select v-model="cardPerPage" class="border border-gray-200 rounded-lg text-xs py-1 px-2.5 bg-gray-50 text-gray-700 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option :value="3">3 Grup ODC</option>
                                <option :value="5">5 Grup ODC</option>
                                <option :value="10">10 Grup ODC</option>
                                <option :value="15">15 Grup ODC</option>
                                <option value="all">Semua ODC</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="totalCardPages > 1" class="flex items-center gap-1">
                        <button
                            @click="goToCardPage(cardCurrentPage - 1)"
                            :disabled="cardCurrentPage === 1"
                            class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors flex items-center gap-1"
                            :class="cardCurrentPage === 1 ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50' : 'border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 bg-white'"
                            title="Halaman Sebelumnya"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            <span>Prev</span>
                        </button>

                        <template v-for="p in getPageNumbers(cardCurrentPage, totalCardPages)" :key="'card-' + p">
                            <span v-if="p === '...'" class="px-2 py-1 text-gray-400 text-xs">...</span>
                            <button
                                v-else
                                @click="goToCardPage(p)"
                                class="min-w-[32px] h-8 px-2 rounded-lg text-xs font-bold transition-all"
                                :class="p === cardCurrentPage ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200'"
                            >
                                {{ p }}
                            </button>
                        </template>

                        <button
                            @click="goToCardPage(cardCurrentPage + 1)"
                            :disabled="cardCurrentPage === totalCardPages"
                            class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors flex items-center gap-1"
                            :class="cardCurrentPage === totalCardPages ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50' : 'border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 bg-white'"
                            title="Halaman Selanjutnya"
                        >
                            <span>Next</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <div v-if="odcDisplayUnits.length === 0" class="text-center py-12 bg-white rounded-xl border border-gray-200">
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
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">VLAN & Mode Akses</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Area</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Kapasitas</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Terpakai</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Tersedia</th>
                                <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(row, rIdx) in paginatedOdpsTable"
                                :key="row.odp.id"
                                class="transition-colors"
                                :class="[
                                    row.odpSplit.role === 'induk'
                                        ? 'bg-emerald-50/35 hover:bg-emerald-50/70 border-l-4 border-l-emerald-500'
                                        : row.odpSplit.role === 'lanjutan'
                                            ? 'bg-purple-50/35 hover:bg-purple-50/70 border-l-4 border-l-purple-500'
                                            : row.odpSplit.isSplit
                                                ? 'bg-amber-50/25 hover:bg-amber-50/60 border-l-4 border-l-amber-400'
                                                : 'hover:bg-gray-50'
                                ]"
                            >
                                <!-- ODP / Kode + Relasi Split 2 -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            v-if="row.odpSplit.role === 'lanjutan' && rIdx > 0 && paginatedOdpsTable[rIdx - 1]?.odpSplit?.rootId === row.odpSplit.rootId"
                                            class="text-purple-600 font-black text-sm leading-none"
                                            title="Unit Lanjutan dari ODP Induk tepat di atasnya"
                                        >
                                            ↳
                                        </span>
                                        <div class="font-bold text-gray-900 text-sm">{{ row.odp.name }}</div>
                                        <span v-if="getOdpCbpCount(row.odp) > 0" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-rose-100 text-rose-700 border border-rose-300 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                            ⏳ {{ getOdpCbpCount(row.odp) }} CBP
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-500">{{ row.odp.kode_odp || '-' }}</div>

                                    <!-- Indikator Pasangan ODP Split 2 -->
                                    <div v-if="row.odpSplit.isSplit" class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <span
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider"
                                            :class="[
                                                row.odpSplit.role === 'induk'
                                                    ? 'bg-emerald-600 text-white'
                                                    : row.odpSplit.role === 'lanjutan'
                                                        ? 'bg-purple-600 text-white'
                                                        : 'bg-amber-500 text-white'
                                            ]"
                                        >
                                            <span v-if="row.odpSplit.role === 'induk'">🟢 SPLIT 2 • INDUK</span>
                                            <span v-else-if="row.odpSplit.role === 'lanjutan'">🟣 SPLIT 2 • LANJUTAN</span>
                                            <span v-else>⚡ SPLIT 2</span>
                                        </span>
                                        <span
                                            v-if="row.odpSplit.partnerLabel"
                                            class="text-[10px] font-semibold px-1.5 py-0.5 rounded border bg-white"
                                            :class="row.odpSplit.role === 'induk' ? 'border-emerald-200 text-emerald-800' : 'border-purple-200 text-purple-800'"
                                        >
                                            {{ row.odpSplit.role === 'induk' ? '↱ Lanjutan:' : '↳ Induk:' }}
                                            <strong>{{ row.odpSplit.partnerLabel }}</strong>
                                        </span>
                                    </div>
                                </td>

                                <!-- ODC / Server + Relasi ODC Split -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">{{ row.odp.odc?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-500">{{ row.odp.odc?.olt?.name || '-' }} (PON {{ row.odp.odc?.pon_port || '?' }})</div>
                                    <div v-if="row.odcSplit.isSplit" class="mt-1 flex items-center gap-1 flex-wrap">
                                        <span
                                            class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[9px] font-bold uppercase"
                                            :class="[
                                                row.odcSplit.role === 'induk'
                                                    ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                                                    : row.odcSplit.role === 'lanjutan'
                                                        ? 'bg-purple-100 text-purple-800 border border-purple-200'
                                                        : 'bg-amber-100 text-amber-800 border border-amber-200'
                                            ]"
                                        >
                                            <span v-if="row.odcSplit.role === 'induk'">🟢 ODC INDUK</span>
                                            <span v-else-if="row.odcSplit.role === 'lanjutan'">🟣 ODC LANJUTAN</span>
                                            <span v-else>⚡ ODC SPLIT</span>
                                        </span>
                                        <span v-if="row.odcSplit.partnerLabel" class="text-[10px] text-gray-600">
                                            ({{ row.odcSplit.role === 'induk' ? '↱' : '↳' }} {{ row.odcSplit.partnerLabel }})
                                        </span>
                                    </div>
                                </td>

                                <!-- VLAN & Mode Akses -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <div v-if="getOdpPonVlans(row.odp).length > 0" class="flex flex-wrap gap-1 max-w-xs">
                                        <div v-for="(vlan, vIdx) in getOdpPonVlans(row.odp)" :key="vIdx" class="inline-flex items-center gap-1 bg-white border border-gray-200 px-1.5 py-0.5 rounded text-[10px] shadow-xs">
                                            <span class="font-bold text-indigo-700">VLAN {{ vlan.vlan_id || vlan }}</span>
                                            <span v-if="vlan.name" class="text-gray-600 text-[9px]">({{ vlan.name }})</span>
                                            <span :class="['px-1.5 py-0.2 text-[8px] font-bold rounded-full uppercase', getModeBadgeClass(getVlanAccessMode(vlan))]">
                                                {{ getVlanAccessMode(vlan) }}
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">-</span>
                                </td>

                                <!-- Area -->
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900">{{ row.odp.area?.name || '-' }}</td>

                                <!-- Kapasitas -->
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-bold text-gray-800">
                                    {{ row.odp.total_ports }} Port
                                </td>

                                <!-- Terpakai -->
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    <div>{{ row.odp.used_ports }}</div>
                                    <div v-if="getOdpGratisCount(row.odp) > 0" class="text-[10px] font-bold text-emerald-700 flex items-center gap-0.5 mt-0.5" title="Pelanggan Gratis di ODP ini">
                                        <span>🎁 {{ getOdpGratisCount(row.odp) }} Gratis</span>
                                    </div>
                                </td>

                                <!-- Tersedia -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded text-xs font-bold" :class="Math.max(0, row.odp.total_ports - row.odp.used_ports) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'">
                                        {{ Math.max(0, row.odp.total_ports - row.odp.used_ports) }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-3 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider" :class="getOdpStatusBadge(row.odp)">
                                        {{ row.odp.status }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openOdpDetail(row.odp)" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded transition-colors text-xs font-bold">Lihat Foto</button>
                                </td>
                            </tr>
                            <tr v-if="sortedOdpsForTable.length === 0">
                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer Table View -->
                <div v-if="sortedOdpsForTable.length > 0" class="p-3.5 border-t border-gray-200 bg-gray-50/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center flex-wrap gap-4 text-gray-600">
                        <div>
                            Menampilkan <span class="font-bold text-gray-900">{{ tablePaginationInfo.from }}</span> -
                            <span class="font-bold text-gray-900">{{ tablePaginationInfo.to }}</span> dari
                            <span class="font-bold text-gray-900">{{ tablePaginationInfo.total }}</span> ODP
                        </div>
                        <div class="flex items-center gap-1.5 border-l border-gray-200 pl-4">
                            <span class="text-gray-500 font-medium">Tampilkan:</span>
                            <select v-model="tablePerPage" class="border border-gray-200 rounded-lg text-xs py-1 px-2.5 bg-white text-gray-700 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                <option :value="10">10 ODP</option>
                                <option :value="25">25 ODP</option>
                                <option :value="50">50 ODP</option>
                                <option :value="100">100 ODP</option>
                                <option value="all">Semua ODP</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="totalTablePages > 1" class="flex items-center gap-1">
                        <button
                            @click="goToTablePage(tableCurrentPage - 1)"
                            :disabled="tableCurrentPage === 1"
                            class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors flex items-center gap-1"
                            :class="tableCurrentPage === 1 ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50' : 'border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 bg-white'"
                            title="Halaman Sebelumnya"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            <span>Prev</span>
                        </button>

                        <template v-for="p in getPageNumbers(tableCurrentPage, totalTablePages)" :key="'table-' + p">
                            <span v-if="p === '...'" class="px-2 py-1 text-gray-400 text-xs">...</span>
                            <button
                                v-else
                                @click="goToTablePage(p)"
                                class="min-w-[32px] h-8 px-2 rounded-lg text-xs font-bold transition-all"
                                :class="p === tableCurrentPage ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200'"
                            >
                                {{ p }}
                            </button>
                        </template>

                        <button
                            @click="goToTablePage(tableCurrentPage + 1)"
                            :disabled="tableCurrentPage === totalTablePages"
                            class="px-2.5 py-1.5 rounded-lg border text-xs font-medium transition-colors flex items-center gap-1"
                            :class="tableCurrentPage === totalTablePages ? 'border-gray-200 text-gray-300 cursor-not-allowed bg-gray-50' : 'border-gray-200 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 bg-white'"
                            title="Halaman Selanjutnya"
                        >
                            <span>Next</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TOPOLOGY VIEW -->
            <div v-if="viewMode === 'topology'" class="bg-gray-900 rounded-xl border border-gray-800 p-6 overflow-x-auto min-h-[600px] relative animate-fade-in-up">
                <!-- Legend -->
                <div class="absolute top-4 right-4 bg-gray-800/80 backdrop-blur border border-gray-700 p-3 rounded-lg text-[10px] text-gray-300 font-medium space-y-2 z-10">
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-emerald-500"></div> Online / Active</div>
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-red-500"></div> Offline / Gangguan</div>
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></div> ⏳ Proses CBP (Belum Dicabut)</div>
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div> Reservasi / Proses Pasang</div>
                    <div class="flex items-center gap-2"><div class="w-2.5 h-2.5 rounded-full bg-gray-500"></div> Port Kosong</div>
                    <div class="pt-1 border-t border-gray-700 flex items-center gap-2 text-emerald-300">🟢 Induk Split &harr; 🟣 Lanjutan Split</div>
                </div>

                <div class="font-mono text-xs text-gray-300 leading-relaxed whitespace-pre">
                    <template v-for="serverGroup in groupedForTopology" :key="serverGroup.id">
<span class="text-blue-400 font-bold text-sm">SERVER: {{ serverGroup.name }}</span>
<template v-for="odc in serverGroup.odcs" :key="odc.id">
   │
   ▼
<span v-if="odc.splitInfo?.role === 'lanjutan'" class="text-purple-400 font-bold">   ↳ [SPLIT LANJUTAN] </span><span class="text-indigo-400 font-bold">ODC {{ odc.name }}</span> <span class="text-gray-400 text-[11px]">[PON {{ odc.pon_port || '?' }}]</span> <span v-if="odc.splitInfo?.role === 'induk'" class="bg-emerald-900/80 text-emerald-300 border border-emerald-500/50 px-1.5 py-0.2 rounded text-[10px] font-bold">🟢 ODC SPLIT INDUK ━━&gt; Lanjutan: {{ odc.splitInfo.partnerLabel }}</span><span v-else-if="odc.splitInfo?.role === 'lanjutan'" class="bg-purple-900/80 text-purple-200 border border-purple-500/50 px-1.5 py-0.2 rounded text-[10px] font-bold">🟣 ODC SPLIT LANJUTAN &lt;━━ Induk: {{ odc.splitInfo.partnerLabel }}</span><span v-else-if="odc.splitInfo?.isSplit" class="bg-amber-900/80 text-amber-200 border border-amber-500/50 px-1.5 py-0.2 rounded text-[10px] font-bold">⚡ TIPE ODC SPLIT</span> <span v-if="getPonVlans(odc.odc).length > 0" class="text-purple-300 text-[11px]">[VLAN: {{ getPonVlans(odc.odc).map(v => getVlanDisplayId(v) + ' ' + getVlanAccessMode(v)).join(', ') }}]</span> <span class="text-gray-500">[{{ getOdcTotalPorts(odc.odps).available }} Port Free]</span>
   │<template v-if="odc.odps.length === 0">
   └──────── <span class="text-gray-500 italic">(Belum ada distribusi ODP pada ODC ini)</span>
</template>
<template v-for="(odp, odpIndex) in odc.odps" :key="odp.id">
   {{ odpIndex === odc.odps.length - 1 ? '└' : '├' }}──────── <span v-if="getOdpSplitInfo(odp).role === 'lanjutan'" class="text-purple-400 font-bold">↳ </span><button @click="openOdpDetail(odp)" class="text-gray-200 hover:text-white font-bold bg-gray-800 hover:bg-gray-700 px-2 py-0.5 rounded transition-colors cursor-pointer">ODP {{ odp.name }}</button> <span v-if="getOdpSplitInfo(odp).role === 'induk'" class="bg-emerald-900/80 text-emerald-300 border border-emerald-500/50 px-1.5 py-0.2 rounded text-[10px] font-bold">🟢 SPLIT 2 INDUK ━━&gt; {{ getOdpSplitInfo(odp).partnerLabel }}</span><span v-else-if="getOdpSplitInfo(odp).role === 'lanjutan'" class="bg-purple-900/80 text-purple-200 border border-purple-500/50 px-1.5 py-0.2 rounded text-[10px] font-bold">🟣 SPLIT 2 LANJUTAN &lt;━━ {{ getOdpSplitInfo(odp).partnerLabel }}</span><span v-else-if="getOdpSplitInfo(odp).isSplit" class="bg-amber-900/80 text-amber-200 border border-amber-500/50 px-1.5 py-0.2 rounded text-[10px] font-bold">⚡ SPLIT 2</span> <span v-if="getOdpPonVlans(odp).length > 0" class="text-indigo-300 text-[10px]">[VLAN: {{ getOdpPonVlans(odp).map(v => getVlanDisplayId(v) + ' ' + getVlanAccessMode(v)).join(', ') }}]</span> <span class="text-gray-500 text-[10px]">{{ Math.max(0, odp.total_ports - odp.used_ports) }} Free / {{ odp.total_ports }} Cap</span>
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

        <!-- FOTO ODP LIGHTBOX -->
        <Teleport to="body">
            <div v-if="selectedDetailOdp" class="fixed inset-0 z-[100] flex items-center justify-center p-4 md:p-8">
                <div class="absolute inset-0 bg-black/90 backdrop-blur-sm transition-opacity" @click="selectedDetailOdp = null"></div>

                <button @click="selectedDetailOdp = null" class="absolute top-4 right-4 md:top-6 md:right-6 text-gray-400 hover:text-white bg-black/50 hover:bg-black/80 p-2 rounded-full transition-all z-10">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div class="relative w-full max-w-5xl max-h-[90vh] flex flex-col items-center justify-center animate-fade-in-up">
                    <template v-if="selectedDetailOdp.photo">
                        <img :src="`/storage/${selectedDetailOdp.photo}`" alt="Foto ODP" class="max-w-full max-h-[82vh] object-contain rounded-lg shadow-2xl" />

                        <div class="mt-4 flex items-center flex-wrap justify-center gap-3">
                            <span class="px-4 py-2 rounded-full bg-white/10 text-white text-xs font-bold border border-white/20 backdrop-blur">
                                {{ selectedDetailOdp.name }}
                                <template v-if="getOdpSplitInfo(selectedDetailOdp).isSplit">
                                    • {{ getOdpSplitInfo(selectedDetailOdp).role === 'induk' ? '🟢 SPLIT 2 INDUK' : getOdpSplitInfo(selectedDetailOdp).role === 'lanjutan' ? '🟣 SPLIT 2 LANJUTAN' : '⚡ SPLIT 2' }}
                                </template>
                            </span>
                            <a :href="`/storage/${selectedDetailOdp.photo}`" target="_blank" class="px-6 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-full font-medium transition-colors flex items-center gap-2 backdrop-blur text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Buka / Download Original
                            </a>
                        </div>
                    </template>
                    <template v-else>
                        <div class="bg-gray-800/80 backdrop-blur border border-gray-700 rounded-2xl p-10 md:p-16 text-center max-w-md w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                            <div class="w-24 h-24 bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-500">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Foto Belum Tersedia</h3>
                            <p class="text-gray-400 text-sm">Foto untuk ODP <span class="font-bold text-gray-300">{{ selectedDetailOdp.name }}</span> belum diupload ke sistem.</p>
                        </div>
                    </template>
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
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Status Proses CBP</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Tgl / Catatan</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
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
                                        <div class="text-xs text-rose-600 font-bold" v-if="cust.ont">
                                            Port {{ cust.ont.port_number }} (Belum dicabut)
                                        </div>
                                        <span v-else class="text-xs text-gray-400">Tidak ada ONT</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="cust.latest_cbp_request && ['pending', 'assigned'].includes(cust.latest_cbp_request.status)" class="space-y-1">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                                ⏳ {{ cust.latest_cbp_request.cbp_number }}
                                            </span>
                                            <div class="text-[10px] text-slate-700 font-medium">
                                                {{ cust.latest_cbp_request.status === 'assigned' ? '🔧 Teknisi Ditugaskan' : '⏳ Menunggu Jadwal' }}
                                                <span v-if="cust.latest_cbp_request.technicians?.length" class="text-indigo-700 font-bold block">
                                                    ({{ cust.latest_cbp_request.technicians.map(t => t.name).join(', ') }})
                                                </span>
                                            </div>
                                        </div>
                                        <div v-else-if="cust.latest_cbp_request && cust.latest_cbp_request.status === 'completed'">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                ✅ Selesai Dicabut
                                            </span>
                                        </div>
                                        <div v-else>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                                ⚠️ Belum Ada Tiket CBP
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <div class="font-medium text-gray-800">{{ cust.updated_at ? cust.updated_at.substring(0, 10) : '-' }}</div>
                                        <div class="text-[11px] text-gray-500 max-w-xs truncate" :title="cust.notes">{{ cust.notes || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <Link :href="route('cbp.jadwal')" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs transition">
                                            Jadwal CBP &rarr;
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="terminated_customers.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada pelanggan stop permanen.</td>
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
import { ref, computed, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermission } from '@/Composables/usePermission';
import OdcGroupCard from './Partials/OdcGroupCard.vue';

const { can } = usePermission();

const props = defineProps({
    olts: { type: Array, default: () => [] },
    odcs: { type: Array, default: () => [] },
    display_odcs: { type: Array, default: () => [] },
    odps_filter: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    odps: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    terminated_customers: { type: Array, default: () => [] },
    cbp_requests: { type: Array, default: () => [] },
});

const viewMode = ref('card');
const isRefreshing = ref(false);
const showTerminated = ref(false);
const selectedDetailOdp = ref(null);

const form = useForm({
    olt_id: props.filters?.olt_id || '',
    odc_id: props.filters?.odc_id || '',
    odp_id: props.filters?.odp_id || '',
    area_id: props.filters?.area_id || '',
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

// ================= SMART LOOKUP MAPS UNTUK RELASI SPLIT ODC & ODP =================
const allKnownOdcsMap = computed(() => {
    const map = new Map();
    (props.odcs || []).forEach(o => { if (o?.id) map.set(Number(o.id), o); });
    (props.display_odcs || []).forEach(o => { if (o?.id) map.set(Number(o.id), { ...(map.get(Number(o.id)) || {}), ...o }); });
    (props.odps || []).forEach(odp => {
        if (odp?.odc?.id) {
            map.set(Number(odp.odc.id), { ...(map.get(Number(odp.odc.id)) || {}), ...odp.odc });
        }
    });
    return map;
});

const allKnownOdpsMap = computed(() => {
    const map = new Map();
    (props.odps_filter || []).forEach(o => { if (o?.id) map.set(Number(o.id), o); });
    (props.odps || []).forEach(o => { if (o?.id) map.set(Number(o.id), { ...(map.get(Number(o.id)) || {}), ...o }); });
    return map;
});

const getOdcSplitInfo = (odc) => {
    if (!odc || !odc.id || odc.id === 'null') {
        return { isSplit: false, role: null, roleLabel: null, partnerLabel: null, rootId: null };
    }

    const id = Number(odc.id);
    const parentId = odc.parent_odc_id ? Number(odc.parent_odc_id) : (odc.parent?.id ? Number(odc.parent.id) : null);
    const parentObj = odc.parent || (parentId ? allKnownOdcsMap.value.get(parentId) : null);

    const childrenMap = new Map();
    if (Array.isArray(odc.children)) {
        odc.children.forEach(c => { if (c?.id) childrenMap.set(Number(c.id), c); });
    }
    allKnownOdcsMap.value.forEach(candidate => {
        if (Number(candidate.parent_odc_id) === id) {
            childrenMap.set(Number(candidate.id), candidate);
        }
    });
    const childrenList = Array.from(childrenMap.values());

    const isSplit = Boolean(
        odc.is_split ||
        odc.type === 'Split' ||
        parentId ||
        childrenList.length > 0
    );

    if (!isSplit) {
        return { isSplit: false, role: null, roleLabel: null, partnerLabel: null, rootId: null };
    }

    if (parentId) {
        return {
            isSplit: true,
            role: 'lanjutan',
            roleLabel: 'LANJUTAN',
            partnerLabel: parentObj?.name || `ODC #${parentId}`,
            rootId: parentId,
        };
    }

    if (childrenList.length > 0) {
        return {
            isSplit: true,
            role: 'induk',
            roleLabel: 'INDUK',
            partnerLabel: childrenList.map(c => c.name).join(', '),
            rootId: id,
        };
    }

    return {
        isSplit: true,
        role: 'split_standalone',
        roleLabel: 'SPLIT',
        partnerLabel: null,
        rootId: id,
    };
};

const getOdpSplitInfo = (odp) => {
    if (!odp || !odp.id) {
        return { isSplit: false, role: null, roleLabel: null, partnerLabel: null, rootId: null };
    }

    const id = Number(odp.id);
    const parentId = odp.parent_odp_id ? Number(odp.parent_odp_id) : (odp.parent?.id ? Number(odp.parent.id) : null);
    const parentObj = odp.parent || (parentId ? allKnownOdpsMap.value.get(parentId) : null);

    const childrenMap = new Map();
    if (Array.isArray(odp.children)) {
        odp.children.forEach(c => { if (c?.id) childrenMap.set(Number(c.id), c); });
    }
    allKnownOdpsMap.value.forEach(candidate => {
        if (Number(candidate.parent_odp_id) === id) {
            childrenMap.set(Number(candidate.id), candidate);
        }
    });
    const childrenList = Array.from(childrenMap.values());

    const isSplit = Boolean(
        odp.is_split ||
        odp.type === 'Split 2' ||
        odp.type === 'Split' ||
        parentId ||
        childrenList.length > 0
    );

    if (!isSplit) {
        return { isSplit: false, role: null, roleLabel: null, partnerLabel: null, rootId: null };
    }

    if (parentId) {
        return {
            isSplit: true,
            role: 'lanjutan',
            roleLabel: 'LANJUTAN',
            partnerLabel: parentObj?.name || `ODP #${parentId}`,
            rootId: parentId,
        };
    }

    if (childrenList.length > 0) {
        return {
            isSplit: true,
            role: 'induk',
            roleLabel: 'INDUK',
            partnerLabel: childrenList.map(c => c.name).join(', '),
            rootId: id,
        };
    }

    return {
        isSplit: true,
        role: 'split_standalone',
        roleLabel: 'SPLIT 2',
        partnerLabel: null,
        rootId: id,
    };
};

const getExpandedOdcPartnerIds = (odcId) => {
    if (!odcId) return [];
    const target = allKnownOdcsMap.value.get(Number(odcId));
    const info = getOdcSplitInfo(target);
    if (!info.isSplit || !info.rootId) return [Number(odcId)];

    const ids = new Set([Number(odcId), Number(info.rootId)]);
    allKnownOdcsMap.value.forEach(o => {
        if (Number(o.parent_odc_id) === Number(info.rootId)) {
            ids.add(Number(o.id));
        }
    });
    return Array.from(ids);
};

watch(() => form.olt_id, (newVal) => {
    if (form.odc_id) {
        const odcExists = props.odcs?.some(o => o.id == form.odc_id && (!newVal || o.olt_id == newVal));
        if (!odcExists) {
            form.odc_id = '';
            form.odp_id = '';
        }
    }
});

watch(() => form.odc_id, (newVal) => {
    if (form.odp_id) {
        const validOdcIds = newVal ? getExpandedOdcPartnerIds(newVal) : [];
        const odpExists = props.odps_filter?.some(o => o.id == form.odp_id && (!newVal || validOdcIds.includes(Number(o.odc_id))));
        if (!odpExists) {
            form.odp_id = '';
        }
    }
});

watch(() => props.filters, (newFilters) => {
    if (!newFilters) return;
    form.olt_id = newFilters.olt_id || '';
    form.odc_id = newFilters.odc_id || '';
    form.odp_id = newFilters.odp_id || '';
    form.area_id = newFilters.area_id || '';
    form.status = newFilters.status || '';
    form.search = newFilters.search || '';
}, { deep: true });

const refreshData = () => {
    isRefreshing.value = true;
    router.reload({
        only: ['odps', 'display_odcs', 'stats', 'terminated_customers', 'cbp_requests'],
        onFinish: () => { isRefreshing.value = false; }
    });
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
    router.get(route('network-data.index'), {}, { preserveState: true, preserveScroll: true });
};

const filteredOdcs = computed(() => {
    if (!form.olt_id) return props.odcs || [];
    return (props.odcs || []).filter(o => o.olt_id == form.olt_id);
});

const filteredOdpsDropdown = computed(() => {
    let result = props.odps_filter || [];
    if (form.odc_id) {
        const validOdcIds = getExpandedOdcPartnerIds(form.odc_id);
        result = result.filter(o => validOdcIds.includes(Number(o.odc_id)));
    } else if (form.olt_id) {
        const odcIds = (props.odcs || []).filter(o => o.olt_id == form.olt_id).map(o => Number(o.id));
        result = result.filter(o => odcIds.includes(Number(o.odc_id)));
    }
    return result;
});

const formatOdcDropdownLabel = (odc) => {
    const info = getOdcSplitInfo(odc);
    if (info.role === 'induk') return `🟢 ${odc.name} [SPLIT - INDUK]`;
    if (info.role === 'lanjutan') return `   ↳ 🟣 ${odc.name} [SPLIT - LANJUTAN]`;
    if (info.isSplit) return `⚡ ${odc.name} [SPLIT]`;
    return odc.name;
};

const formatOdpDropdownLabel = (odp) => {
    const info = getOdpSplitInfo(odp);
    if (info.role === 'induk') return `🟢 ${odp.name} [SPLIT 2 - INDUK]`;
    if (info.role === 'lanjutan') return `   ↳ 🟣 ${odp.name} [SPLIT 2 - LANJUTAN]`;
    if (info.isSplit) return `⚡ ${odp.name} [SPLIT 2]`;
    return odp.name;
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

// ================= CARD VIEW GROUPING (ODC SPLIT BERDAMPINGAN) =================
const odcDisplayUnits = computed(() => {
    const groupsMap = new Map();

    // 1. Seed dari display_odcs agar pasangan ODC Split (termasuk yang 0 ODP) selalu tampil utuh
    (props.display_odcs || []).forEach(odc => {
        const key = String(odc.id);
        if (!groupsMap.has(key)) {
            groupsMap.set(key, {
                odc,
                odps: [],
                splitInfo: getOdcSplitInfo(odc),
            });
        }
    });

    // 2. Masukkan seluruh ODP ke masing-masing ODC
    (props.odps || []).forEach(odp => {
        const key = odp.odc_id ? String(odp.odc_id) : 'null';
        if (!groupsMap.has(key)) {
            const odcObj = odp.odc || { id: 'null', name: 'Tanpa ODC' };
            groupsMap.set(key, {
                odc: odcObj,
                odps: [],
                splitInfo: getOdcSplitInfo(odcObj),
            });
        }
        groupsMap.get(key).odps.push(odp);
    });

    // 3. Pasangkan ODC Split (Induk + Lanjutan) ke dalam satu Unit Berdampingan
    const familyMap = new Map();
    groupsMap.forEach((group, key) => {
        const sInfo = group.splitInfo;
        const familyKey = (sInfo.isSplit && sInfo.rootId)
            ? `split-odc-${sInfo.rootId}`
            : `single-odc-${key}`;

        if (!familyMap.has(familyKey)) {
            familyMap.set(familyKey, []);
        }
        familyMap.get(familyKey).push(group);
    });

    const units = [];
    familyMap.forEach((items, familyKey) => {
        // Pastikan Unit Induk di urutan pertama (kiri) dan Unit Lanjutan di urutan kedua (kanan)
        items.sort((a, b) => {
            const aRank = a.odc.parent_odc_id ? 1 : 0;
            const bRank = b.odc.parent_odc_id ? 1 : 0;
            if (aRank !== bRank) return aRank - bRank;
            return Number(a.odc.id || 0) - Number(b.odc.id || 0);
        });

        units.push({
            key: familyKey,
            isSplitPair: items.length > 1,
            sortName: items[0]?.odc?.name || '',
            items,
        });
    });

    units.sort((a, b) => a.sortName.localeCompare(b.sortName, undefined, { numeric: true, sensitivity: 'base' }));
    return units;
});

const getOdcUnitTotalOdps = (items) => {
    return items.reduce((sum, item) => sum + (item.odps?.length || 0), 0);
};

const getOdcUnitFreePorts = (items) => {
    return items.reduce((sum, item) => {
        const avail = (item.odps || []).reduce((s, o) => s + Math.max(0, Number(o.total_ports || 0) - Number(o.used_ports || 0)), 0);
        return sum + avail;
    }, 0);
};

// ================= PAGINASI CARD VIEW & TABLE VIEW =================
const cardPerPage = ref(5);
const cardCurrentPage = ref(1);

const tablePerPage = ref(25);
const tableCurrentPage = ref(1);

const totalCardPages = computed(() => {
    if (cardPerPage.value === 'all') return 1;
    const perPage = Number(cardPerPage.value) || 5;
    return Math.max(1, Math.ceil(odcDisplayUnits.value.length / perPage));
});

const paginatedOdcUnits = computed(() => {
    if (cardPerPage.value === 'all') return odcDisplayUnits.value;
    const perPage = Number(cardPerPage.value) || 5;
    const start = (cardCurrentPage.value - 1) * perPage;
    return odcDisplayUnits.value.slice(start, start + perPage);
});

const cardPaginationInfo = computed(() => {
    const allUnits = odcDisplayUnits.value;
    const totalOdcsCount = allUnits.reduce((sum, u) => sum + u.items.length, 0);
    if (totalOdcsCount === 0) return { from: 0, to: 0, total: 0 };
    if (cardPerPage.value === 'all') return { from: 1, to: totalOdcsCount, total: totalOdcsCount };

    const perPage = Number(cardPerPage.value) || 5;
    const startUnitIdx = (cardCurrentPage.value - 1) * perPage;
    const beforeCount = allUnits.slice(0, startUnitIdx).reduce((sum, u) => sum + u.items.length, 0);
    const currentCount = paginatedOdcUnits.value.reduce((sum, u) => sum + u.items.length, 0);

    return {
        from: beforeCount + 1,
        to: beforeCount + currentCount,
        total: totalOdcsCount,
    };
});

// ================= TABLE VIEW SORTING & SPLIT-SAFE PAGINATION =================
const sortedOdpsForTable = computed(() => {
    const rows = (props.odps || []).map(odp => {
        const odcSplit = getOdcSplitInfo(odp.odc);
        const odpSplit = getOdpSplitInfo(odp);

        const rootOdc = odcSplit.rootId ? allKnownOdcsMap.value.get(Number(odcSplit.rootId)) : odp.odc;
        const rootOdp = odpSplit.rootId ? allKnownOdpsMap.value.get(Number(odpSplit.rootId)) : odp;

        return {
            odp,
            odcSplit,
            odpSplit,
            odcRootName: rootOdc?.name || odp.odc?.name || 'ZZZ',
            odcRoleRank: odp.odc?.parent_odc_id ? 1 : 0,
            odpRootName: rootOdp?.name || odp.name || '',
            odpRoleRank: odp.parent_odp_id ? 1 : 0,
        };
    });

    rows.sort((a, b) => {
        // 1. Grup ODC Split berdampingan
        const odcNameCmp = a.odcRootName.localeCompare(b.odcRootName, undefined, { numeric: true, sensitivity: 'base' });
        if (odcNameCmp !== 0) return odcNameCmp;
        if (a.odcRoleRank !== b.odcRoleRank) return a.odcRoleRank - b.odcRoleRank;
        const odcIdCmp = Number(a.odp.odc_id || 0) - Number(b.odp.odc_id || 0);
        if (odcIdCmp !== 0) return odcIdCmp;

        // 2. Grup ODP Split 2 berdampingan (Induk tepat di atas Lanjutan)
        if (a.odpSplit.rootId && b.odpSplit.rootId && a.odpSplit.rootId === b.odpSplit.rootId) {
            if (a.odpRoleRank !== b.odpRoleRank) return a.odpRoleRank - b.odpRoleRank;
            return Number(a.odp.id) - Number(b.odp.id);
        }

        const odpNameCmp = a.odpRootName.localeCompare(b.odpRootName, undefined, { numeric: true, sensitivity: 'base' });
        if (odpNameCmp !== 0) return odpNameCmp;
        if (a.odpRoleRank !== b.odpRoleRank) return a.odpRoleRank - b.odpRoleRank;
        return Number(a.odp.id) - Number(b.odp.id);
    });

    return rows;
});

const tablePages = computed(() => {
    const rows = sortedOdpsForTable.value;
    if (rows.length === 0) return [[]];
    if (tablePerPage.value === 'all') return [rows];

    const perPage = Number(tablePerPage.value) || 25;
    const pages = [];
    let current = [];

    let i = 0;
    while (i < rows.length) {
        const row = rows[i];
        const group = [row];

        // Jika baris ini adalah ODP Split Induk yang diikuti langsung oleh Lanjutannya, jaga agar tidak terpotong halaman!
        if (row.odpSplit.isSplit && row.odpSplit.rootId) {
            while (
                i + 1 < rows.length &&
                rows[i + 1].odpSplit.isSplit &&
                rows[i + 1].odpSplit.rootId === row.odpSplit.rootId
            ) {
                i++;
                group.push(rows[i]);
            }
        }

        if (current.length > 0 && current.length + group.length > perPage) {
            pages.push(current);
            current = [...group];
        } else {
            current.push(...group);
        }
        i++;
    }

    if (current.length > 0) {
        pages.push(current);
    }

    return pages;
});

const totalTablePages = computed(() => Math.max(1, tablePages.value.length));

const paginatedOdpsTable = computed(() => {
    const idx = Math.min(Math.max(0, tableCurrentPage.value - 1), tablePages.value.length - 1);
    return tablePages.value[idx] || [];
});

const tablePaginationInfo = computed(() => {
    const total = sortedOdpsForTable.value.length;
    if (total === 0) return { from: 0, to: 0, total: 0 };
    if (tablePerPage.value === 'all') return { from: 1, to: total, total };

    const idx = Math.min(Math.max(0, tableCurrentPage.value - 1), tablePages.value.length - 1);
    const beforeCount = tablePages.value.slice(0, idx).reduce((sum, p) => sum + p.length, 0);
    const currentCount = (tablePages.value[idx] || []).length;

    return {
        from: beforeCount + 1,
        to: beforeCount + currentCount,
        total,
    };
});

const getPageNumbers = (current, total) => {
    const curr = Number(current);
    const tot = Number(total);
    if (tot <= 7) {
        return Array.from({ length: tot }, (_, i) => i + 1);
    }
    const pages = [];
    if (curr <= 4) {
        for (let i = 1; i <= 5; i++) pages.push(i);
        pages.push('...');
        pages.push(tot);
    } else if (curr >= tot - 3) {
        pages.push(1);
        pages.push('...');
        for (let i = tot - 4; i <= tot; i++) pages.push(i);
    } else {
        pages.push(1);
        pages.push('...');
        pages.push(curr - 1);
        pages.push(curr);
        pages.push(curr + 1);
        pages.push('...');
        pages.push(tot);
    }
    return pages;
};

const scrollToContent = () => {
    const el = document.getElementById('network-data-container');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

const goToCardPage = (page) => {
    if (page === '...') return;
    const target = Number(page);
    if (isNaN(target) || target < 1 || target > totalCardPages.value) return;
    cardCurrentPage.value = target;
    scrollToContent();
};

const goToTablePage = (page) => {
    if (page === '...') return;
    const target = Number(page);
    if (isNaN(target) || target < 1 || target > totalTablePages.value) return;
    tableCurrentPage.value = target;
    scrollToContent();
};

watch([() => props.odps, () => props.display_odcs, () => form.olt_id, () => form.odc_id, () => form.odp_id, () => form.area_id, () => form.status, () => form.search], () => {
    cardCurrentPage.value = 1;
    tableCurrentPage.value = 1;
});

watch(cardPerPage, () => {
    cardCurrentPage.value = 1;
});

watch(tablePerPage, () => {
    tableCurrentPage.value = 1;
});

watch(totalCardPages, (newTotal) => {
    if (cardCurrentPage.value > newTotal) {
        cardCurrentPage.value = Math.max(1, newTotal);
    }
});

watch(totalTablePages, (newTotal) => {
    if (tableCurrentPage.value > newTotal) {
        tableCurrentPage.value = Math.max(1, newTotal);
    }
});

// ================= TOPOLOGY VIEW GROUPING (SPLIT ADJACENT) =================
const groupedForTopology = computed(() => {
    const servers = {};

    const ensureOdcEntry = (odcObj) => {
        const serverName = odcObj?.olt?.name || 'Tanpa Server';
        if (!servers[serverName]) servers[serverName] = { id: serverName, name: serverName, odcs: {} };

        const odcKey = odcObj?.id ? String(odcObj.id) : (odcObj?.name || 'Tanpa ODC');
        if (!servers[serverName].odcs[odcKey]) {
            const splitInfo = getOdcSplitInfo(odcObj);
            const rootOdc = splitInfo.rootId ? allKnownOdcsMap.value.get(Number(splitInfo.rootId)) : odcObj;
            servers[serverName].odcs[odcKey] = {
                id: odcKey,
                name: odcObj?.name || 'Tanpa ODC',
                odc: odcObj,
                pon_port: odcObj?.pon_port,
                splitInfo,
                rootName: rootOdc?.name || odcObj?.name || '',
                roleRank: odcObj?.parent_odc_id ? 1 : 0,
                odps: [],
            };
        }
        return servers[serverName].odcs[odcKey];
    };

    (props.display_odcs || []).forEach(odc => {
        ensureOdcEntry(odc);
    });

    (props.odps || []).forEach(odp => {
        const odcEntry = ensureOdcEntry(odp.odc || { id: 'null', name: 'Tanpa ODC' });
        odcEntry.odps.push(odp);
    });

    return Object.values(servers).map(s => {
        const sortedOdcs = Object.values(s.odcs).map(odcEntry => {
            const sortedOdps = [...odcEntry.odps].sort((a, b) => {
                const aSplit = getOdpSplitInfo(a);
                const bSplit = getOdpSplitInfo(b);
                if (aSplit.rootId && bSplit.rootId && aSplit.rootId === bSplit.rootId) {
                    const aRank = a.parent_odp_id ? 1 : 0;
                    const bRank = b.parent_odp_id ? 1 : 0;
                    if (aRank !== bRank) return aRank - bRank;
                    return Number(a.id) - Number(b.id);
                }
                const aRoot = (aSplit.rootId ? allKnownOdpsMap.value.get(Number(aSplit.rootId))?.name : a.name) || a.name || '';
                const bRoot = (bSplit.rootId ? allKnownOdpsMap.value.get(Number(bSplit.rootId))?.name : b.name) || b.name || '';
                return aRoot.localeCompare(bRoot, undefined, { numeric: true, sensitivity: 'base' });
            });

            return { ...odcEntry, odps: sortedOdps };
        }).sort((a, b) => {
            if (a.splitInfo?.rootId && b.splitInfo?.rootId && a.splitInfo.rootId === b.splitInfo.rootId) {
                if (a.roleRank !== b.roleRank) return a.roleRank - b.roleRank;
                return Number(a.odc?.id || 0) - Number(b.odc?.id || 0);
            }
            const nameCmp = a.rootName.localeCompare(b.rootName, undefined, { numeric: true, sensitivity: 'base' });
            if (nameCmp !== 0) return nameCmp;
            return a.roleRank - b.roleRank;
        });

        return { ...s, odcs: sortedOdcs };
    }).sort((a, b) => a.name.localeCompare(b.name));
});

const getPonVlans = (odcItem) => {
    if (!odcItem) return [];
    let olt = odcItem.olt;
    if (!olt && odcItem.olt_id && props.olts) {
        olt = props.olts.find(o => String(o.id) === String(odcItem.olt_id));
    }
    if (!olt || !odcItem.pon_port) return [];
    let pVlans = olt.pon_vlans;
    if (typeof pVlans === 'string') {
        try {
            pVlans = JSON.parse(pVlans);
        } catch (e) {
            return [];
        }
    }
    if (!Array.isArray(pVlans)) return [];
    const found = pVlans.find(p => String(p.port) === String(odcItem.pon_port));
    return found && Array.isArray(found.vlans) ? found.vlans : [];
};

const getOdpPonVlans = (odpItem) => {
    if (!odpItem) return [];
    return getPonVlans(odpItem.odc);
};

const getVlanDisplayId = (vlan) => {
    if (!vlan) return '-';
    if (typeof vlan === 'object') {
        return vlan.vlan_id || vlan.id || vlan.vlan || (vlan.name ? vlan.name : '-');
    }
    return String(vlan);
};

const getVlanAccessMode = (vlan) => {
    if (vlan && vlan.access_mode) return vlan.access_mode;
    const name = (vlan?.name || '').toUpperCase();
    if (name.includes('HOTSPOT')) return 'Hotspot';
    if (name.includes('PPPOE')) return 'PPPOE';
    if (name.includes('STATIC')) return 'Static';
    if (name.includes('DHCP')) return 'DHCP';
    return 'PPPOE';
};

const getModeBadgeClass = (mode) => {
    switch ((mode || '').toUpperCase()) {
        case 'PPPOE':
            return 'bg-purple-100 text-purple-800 border border-purple-200';
        case 'HOTSPOT':
            return 'bg-amber-100 text-amber-800 border border-amber-200';
        case 'STATIC':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'DHCP':
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'BRIDGE':
            return 'bg-teal-100 text-teal-800 border border-teal-200';
        case 'TAGGED':
            return 'bg-indigo-100 text-indigo-800 border border-indigo-200';
        case 'UNTAGGED':
            return 'bg-gray-100 text-gray-800 border border-gray-200';
        default:
            return 'bg-slate-100 text-slate-800 border border-slate-200';
    }
};

const getOdcTotalPorts = (odpsArray) => {
    let available = 0;
    (odpsArray || []).forEach(o => { available += Math.max(0, o.total_ports - o.used_ports); });
    return { available };
};

const getOdpStatusBadge = (odp) => {
    if (odp.status === 'full') return 'bg-red-100 text-red-700';
    if (odp.status === 'maintenance') return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
};

const openOdpDetail = (odp) => {
    selectedDetailOdp.value = odp;
};

const getPortData = (odp, portNum) => {
    if (!odp) return null;

    let portData = odp.onts ? odp.onts.find(o => o.port_number == portNum) : null;
    if (portData) return portData;

    if (odp.surveys) {
        const survey = [...odp.surveys]
            .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
            .find(s => s.port_number == portNum && s.customer && ['survey', 'installing'].includes(s.customer.status));

        if (survey) {
            return {
                customer: survey.customer,
                is_reservation: true,
                start_time: survey.created_at,
                updated_at: survey.updated_at
            };
        }
    }

    return null;
};

const getCbpForCustomer = (custOrId) => {
    if (!custOrId) return null;
    const customerId = typeof custOrId === 'object' ? custOrId.id : custOrId;
    if (typeof custOrId === 'object' && custOrId.latest_cbp_request && ['pending', 'assigned'].includes(custOrId.latest_cbp_request.status)) {
        return custOrId.latest_cbp_request;
    }
    if (props.cbp_requests) {
        return props.cbp_requests.find(cbp => cbp.customer_id === customerId && ['pending', 'assigned'].includes(cbp.status)) || null;
    }
    return null;
};

const getOdpCbpCount = (odp) => {
    if (!odp || !odp.onts) return 0;
    let count = 0;
    odp.onts.forEach(ont => {
        if (ont.customer && getCbpForCustomer(ont.customer)) {
            count++;
        }
    });
    return count;
};

const getOdpGratisCount = (odp) => {
    if (!odp || !odp.onts) return 0;
    return odp.onts.filter(o => o.customer && o.customer.service_status === 'gratis').length;
};

const renderPortTopology = (odp, portNum) => {
    const data = getPortData(odp, portNum);
    if (!data) return `<span class="text-emerald-400 font-bold">➔ Available</span>`;

    if (data.customer) {
        const cbp = getCbpForCustomer(data.customer);
        const color = data.customer.status === 'active' ? 'text-indigo-300' : 'text-amber-400';
        const isGratis = data.customer.service_status === 'gratis';
        const gratisBadge = isGratis ? ' <span class="bg-emerald-900/90 border border-emerald-400 text-emerald-200 font-black text-[9px] px-1.5 py-0.2 rounded ml-1.5 shadow-xs">🎁 GRATIS</span>' : '';
        let html = `<span class="${color}">➔ ${data.customer.customer_code} - ${data.customer.name}${gratisBadge}</span>`;
        if (cbp) {
            const statusLabel = cbp.status === 'assigned' ? 'Teknisi Ditugaskan' : 'Menunggu Jadwal';
            html += ` <span class="bg-rose-900/90 border border-rose-500 text-rose-200 font-black text-[10px] px-1.5 py-0.5 rounded ml-2">⏳ PROSES CBP (${cbp.cbp_number || 'CBP'} - ${statusLabel}) - Belum Selesai Cabut</span>`;
        } else if (data.customer.status === 'terminated') {
            html += ` <span class="bg-amber-900/90 border border-amber-500 text-amber-200 font-bold text-[10px] px-1.5 py-0.5 rounded ml-2">⛔ Stop Permanen (Perlu Cabut Port)</span>`;
        }
        return html;
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
