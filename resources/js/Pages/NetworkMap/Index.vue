<template>
    <AppLayout title="GIS Map Jaringan & Rute" subtitle="Peta Cerdas Pelanggan, ODC, ODP, Area & Routing Turn-by-Turn" :full-bleed="true">
        <div class="relative w-full h-[calc(100vh-68px)] overflow-hidden bg-slate-900 select-none">
            
            <!-- ═══ 1. TOP FLOATING CONTROL BAR ═══ -->
            <div class="absolute top-3 left-3 right-3 z-[1000] flex flex-wrap items-center justify-between gap-2 pointer-events-none">
                <!-- Search & Quick Navigation Bar (Hidden when Routing Drawer is open) -->
                <div
                    v-if="!isRoutingActive"
                    class="relative pointer-events-auto w-full sm:w-80 md:w-96 shadow-xl rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200/80 transition-all"
                >
                    <div class="flex items-center px-3.5 py-2">
                        <svg class="w-4 h-4 text-indigo-500 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="searchQuery"
                            @focus="isSearchFocused = true"
                            type="text"
                            placeholder="Cari Pelanggan, ODP, ODC, atau Area..."
                            class="w-full text-xs sm:text-sm bg-transparent border-0 p-0 text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none"
                        />
                        <button
                            v-if="searchQuery"
                            @click="searchQuery = ''"
                            class="p-1 text-slate-400 hover:text-slate-600 rounded-full"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown -->
                    <div
                        v-if="isSearchFocused && searchResults.length > 0"
                        class="absolute left-0 right-0 top-full mt-1.5 max-h-72 overflow-y-auto bg-white rounded-xl shadow-2xl border border-slate-200 divide-y divide-slate-100 z-50 text-xs"
                    >
                        <div
                            v-for="item in searchResults"
                            :key="item.type + '-' + item.id"
                            @click="selectSearchResult(item)"
                            class="px-3 py-2.5 hover:bg-indigo-50/70 cursor-pointer flex items-center justify-between transition-colors"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span
                                    :class="item.badgeClass"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-bold shrink-0 uppercase"
                                >
                                    {{ item.typeLabel }}
                                </span>
                                <div class="truncate">
                                    <p class="font-semibold text-slate-800 truncate">{{ item.title }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ item.subtitle }}</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-indigo-600 font-medium shrink-0 ml-2">Fokus ➔</span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons: Tools & Navigation -->
                <div class="pointer-events-auto flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 scrollbar-none">
                    <!-- Basemap Switcher -->
                    <div class="relative">
                        <button
                            @click="togglePanel('basemap')"
                            class="px-3 py-2 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200/80 shadow-md hover:bg-white text-slate-700 font-semibold text-xs flex items-center gap-1.5 transition-all"
                            title="Ganti Jenis Peta"
                        >
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <span class="hidden md:inline">{{ activeBasemapLabel }}</span>
                        </button>

                        <!-- Basemap Menu -->
                        <div
                            v-if="openPanel === 'basemap'"
                            class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-1.5 z-50 text-xs"
                        >
                            <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilihan Layer Peta</div>
                            <button
                                v-for="mapType in basemaps"
                                :key="mapType.id"
                                @click="changeBasemap(mapType.id)"
                                :class="activeBasemap === mapType.id ? 'bg-indigo-50 text-indigo-700 font-bold' : 'text-slate-700 hover:bg-slate-50'"
                                class="w-full text-left px-3 py-2 flex items-center justify-between transition-colors"
                            >
                                <span>{{ mapType.name }}</span>
                                <span v-if="activeBasemap === mapType.id" class="text-indigo-600">✓</span>
                            </button>
                        </div>
                    </div>

                    <!-- Layer Filter Switcher -->
                    <div class="relative">
                        <button
                            @click="togglePanel('layers')"
                            class="px-3 py-2 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200/80 shadow-md hover:bg-white text-slate-700 font-semibold text-xs flex items-center gap-1.5 transition-all"
                            title="Filter Objek Peta"
                        >
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span class="hidden md:inline">Filter Layer</span>
                        </button>

                        <!-- Layer Toggles Dropdown -->
                        <div
                            v-if="openPanel === 'layers'"
                            class="absolute right-0 top-full mt-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 p-3 z-50 text-xs space-y-2.5"
                        >
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Visibilitas Lapisan</div>
                            
                            <label class="flex items-center justify-between cursor-pointer hover:bg-slate-50 p-1 rounded">
                                <span class="flex items-center gap-2 text-slate-700 font-medium">
                                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Pelanggan ({{ customers.length }})
                                </span>
                                <input v-model="layers.customers" @change="refreshMarkers" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            </label>

                            <label class="flex items-center justify-between cursor-pointer hover:bg-slate-50 p-1 rounded">
                                <span class="flex items-center gap-2 text-slate-700 font-medium">
                                    <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span> ODP ({{ odps.length }})
                                </span>
                                <input v-model="layers.odps" @change="refreshMarkers" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            </label>

                            <label class="flex items-center justify-between cursor-pointer hover:bg-slate-50 p-1 rounded">
                                <span class="flex items-center gap-2 text-slate-700 font-medium">
                                    <span class="w-3 h-3 rounded bg-blue-600 inline-block"></span> ODC ({{ odcs.length }})
                                </span>
                                <input v-model="layers.odcs" @change="refreshMarkers" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            </label>

                            <label class="flex items-center justify-between cursor-pointer hover:bg-slate-50 p-1 rounded">
                                <span class="flex items-center gap-2 text-slate-700 font-medium">
                                    <span class="w-3 h-3 rounded-full border border-purple-500 bg-purple-100 inline-block"></span> Area / Wilayah ({{ areas.length }})
                                </span>
                                <input v-model="layers.areas" @change="toggleAreasLayer" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            </label>

                            <label class="flex items-center justify-between cursor-pointer hover:bg-slate-50 p-1 rounded">
                                <span class="flex items-center gap-2 text-slate-700 font-medium">
                                    <span class="w-3 h-1 bg-blue-400 inline-block"></span> Jalur Fiber ODC-ODP-User
                                </span>
                                <input v-model="layers.fiberLines" @change="toggleFiberLines" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            </label>

                            <label class="flex items-center justify-between cursor-pointer hover:bg-slate-50 p-1 rounded">
                                <span class="flex items-center gap-2 text-slate-700 font-medium">
                                    <span class="w-3 h-3 rounded-full bg-slate-600 inline-block"></span> Marker Cluster (Grup)
                                </span>
                                <input v-model="layers.cluster" @change="refreshMarkers" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                            </label>

                            <!-- Area Filter Selector -->
                            <div class="pt-2 border-t border-slate-100">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Filter Wilayah Tertentu:</label>
                                <select
                                    v-model="selectedAreaFilter"
                                    @change="onAreaFilterChange"
                                    class="w-full text-xs rounded-lg border-slate-200 py-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                >
                                    <option value="all">Semua Wilayah</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Smart Routing Drawer Toggle -->
                    <button
                        v-if="hasPermission('network_map_routing')"
                        @click="toggleRoutingDrawer"
                        :class="isRoutingActive ? 'bg-indigo-600 text-white shadow-indigo-500/30' : 'bg-white/95 text-slate-700 hover:bg-white'"
                        class="px-3.5 py-2 rounded-xl backdrop-blur-md border border-slate-200/80 shadow-md font-semibold text-xs flex items-center gap-1.5 transition-all"
                    >
                        <svg class="w-4 h-4" :class="isRoutingActive ? 'text-white' : 'text-indigo-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        <span>Rute Jalan</span>
                        <span v-if="activeRoute" class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    </button>

                    <!-- Smart Closest ODP Finder -->
                    <button
                        v-if="hasPermission('network_map_find_odp')"
                        @click="toggleOdpFinder"
                        :class="isOdpFinderActive ? 'bg-emerald-600 text-white shadow-emerald-500/30' : 'bg-white/95 text-slate-700 hover:bg-white'"
                        class="px-3.5 py-2 rounded-xl backdrop-blur-md border border-slate-200/80 shadow-md font-semibold text-xs flex items-center gap-1.5 transition-all"
                        title="Analisis ODP Terdekat & Cek Port Kosong"
                    >
                        <svg class="w-4 h-4" :class="isOdpFinderActive ? 'text-white' : 'text-emerald-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                            <circle cx="12" cy="12" r="4" stroke-width="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M2 12h4m12 0h4" />
                        </svg>
                        <span class="hidden sm:inline">Cari ODP</span>
                    </button>

                    <!-- Fiber Cable Ruler Tool -->
                    <button
                        @click="toggleRulerTool"
                        :class="isRulerActive ? 'bg-amber-500 text-white shadow-amber-500/30' : 'bg-white/95 text-slate-700 hover:bg-white'"
                        class="p-2 rounded-xl backdrop-blur-md border border-slate-200/80 shadow-md font-semibold text-xs flex items-center transition-all"
                        title="Penggaris Jarak & Estimasi Redaman dBm"
                    >
                        <svg class="w-4 h-4" :class="isRulerActive ? 'text-white' : 'text-amber-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21.3 8.7L8.7 21.3c-1 1-2.5 1-3.4 0l-2.6-2.6c-1-1-1-2.5 0-3.4L15.3 2.7c1-1 2.5-1 3.4 0l2.6 2.6c1 1 1 2.5 0 3.4zM14.5 5.5l2 2M11.5 8.5l2 2M8.5 11.5l2 2M5.5 14.5l2 2" />
                        </svg>
                    </button>

                    <!-- GPS Location Button -->
                    <button
                        @click="locateMe"
                        :disabled="isLocating"
                        class="p-2 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200/80 shadow-md hover:bg-white text-slate-700 transition-all"
                        title="Pusatkan ke Lokasi Saya"
                    >
                        <svg class="w-4 h-4 text-blue-600" :class="{ 'animate-spin': isLocating }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>

                    <!-- Fullscreen Toggle -->
                    <button
                        @click="toggleFullScreen"
                        class="p-2 rounded-xl bg-white/95 backdrop-blur-md border border-slate-200/80 shadow-md hover:bg-white text-slate-700 transition-all hidden sm:flex"
                        title="Mode Layar Penuh"
                    >
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0 0l-5-5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- ═══ 2. LEAFLET MAP ELEMENT ═══ -->
            <div ref="mapContainer" class="w-full h-full z-0"></div>

            <!-- ═══ 3. BOTTOM FLOATING HUD (KPI STATS & LEGEND) ═══ -->
            <div class="absolute bottom-3 left-3 right-3 sm:right-auto z-[990] pointer-events-none">
                <div class="pointer-events-auto bg-slate-900/90 backdrop-blur-md border border-slate-700/60 shadow-2xl rounded-2xl p-3 text-white max-w-sm sm:max-w-md transition-all">
                    <!-- KPI Pills Grid -->
                    <div class="grid grid-cols-4 gap-2 text-center pb-2 border-b border-slate-800">
                        <div class="bg-slate-800/70 p-1.5 rounded-xl border border-slate-700/40">
                            <div class="text-[10px] text-slate-400 font-medium">Pelanggan</div>
                            <div class="text-sm font-black text-emerald-400">{{ filteredCustomersCount }}</div>
                        </div>
                        <div class="bg-slate-800/70 p-1.5 rounded-xl border border-slate-700/40">
                            <div class="text-[10px] text-slate-400 font-medium">ODP</div>
                            <div class="text-sm font-black text-amber-400">{{ filteredOdpsCount }}</div>
                        </div>
                        <div class="bg-slate-800/70 p-1.5 rounded-xl border border-slate-700/40">
                            <div class="text-[10px] text-slate-400 font-medium">ODC</div>
                            <div class="text-sm font-black text-blue-400">{{ filteredOdcsCount }}</div>
                        </div>
                        <div class="bg-slate-800/70 p-1.5 rounded-xl border border-slate-700/40">
                            <div class="text-[10px] text-slate-400 font-medium">Wilayah</div>
                            <div class="text-sm font-black text-purple-400">{{ filteredAreasCount }}</div>
                        </div>
                    </div>

                    <!-- Legend & Quick Status Toggle -->
                    <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-300">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]"></span>
                                <span>Online {{ connectionCounts.online }}</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-[0_0_8px_#f43f5e]"></span>
                                <span>Offline {{ connectionCounts.offline }}</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                                <span>Belum diketahui {{ connectionCounts.unknown }}</span>
                            </span>
                        </div>
                        <button
                            @click="showLegendModal = true"
                            class="text-[10px] text-indigo-400 hover:text-indigo-300 font-semibold underline cursor-pointer"
                        >
                            Panduan Simbol
                        </button>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-800 flex flex-wrap items-center justify-between gap-2 text-[10px] text-slate-400" aria-live="polite">
                        <span>{{ connectionError || (isRefreshingConnections ? 'Memeriksa koneksi…' : connectionUpdatedAt ? `Diperbarui ${connectionUpdatedAt} · otomatis 30 detik` : 'Menunggu data koneksi') }}</span>
                        <button type="button" @click="refreshConnections" :disabled="isRefreshingConnections" class="text-indigo-300 hover:text-white disabled:opacity-50">Perbarui</button>
                    </div>
                </div>
            </div>

            <!-- ═══ 4. RULER MEASUREMENT FLOATING OVERLAY ═══ -->
            <div
                v-if="isRulerActive"
                class="absolute top-16 left-1/2 -translate-x-1/2 z-[1000] bg-slate-900/95 backdrop-blur-md border border-amber-500/50 shadow-2xl rounded-2xl px-4 py-2.5 text-white flex items-center gap-4 animate-fade-in"
            >
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                    <span class="text-xs font-bold text-amber-400">Penggaris Fiber Aktif</span>
                </div>
                <div class="h-4 w-px bg-slate-700"></div>
                <div class="text-xs">
                    <span class="text-slate-400">Jarak:</span>
                    <span class="font-bold text-white ml-1 font-mono">{{ formatDistance(rulerDistanceMeters) }}</span>
                </div>
                <div class="text-xs">
                    <span class="text-slate-400">Est. Loss:</span>
                    <span class="font-bold text-rose-400 ml-1 font-mono">-{{ rulerLossDb.toFixed(2) }} dB</span>
                </div>
                <button
                    @click="clearRuler"
                    class="px-2.5 py-1 bg-rose-500/20 text-rose-300 hover:bg-rose-500/40 rounded-lg text-xs font-semibold transition-colors"
                >
                    Reset
                </button>
                <button
                    @click="isRulerActive = false"
                    class="text-slate-400 hover:text-white"
                >
                    ✕
                </button>
            </div>

            <!-- ═══ 5. SMART TURN-BY-TURN ROUTING DRAWER ═══ -->
            <transition
                enter-active-class="transform transition ease-in-out duration-300"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transform transition ease-in-out duration-300"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <div
                    v-if="isRoutingActive"
                    class="absolute top-0 bottom-0 left-0 w-full sm:w-96 z-[1010] bg-white shadow-2xl border-r border-slate-200 flex flex-col"
                >
                    <!-- Routing Header -->
                    <div class="p-4 bg-gradient-to-r from-indigo-600 to-blue-600 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            <div>
                                <h3 class="font-bold text-sm leading-tight">Navigasi Rute Cerdas</h3>
                                <p class="text-[11px] text-indigo-100 font-normal">Turn-by-turn OSRM Road Routing</p>
                            </div>
                        </div>
                        <button
                            @click="closeRoutingDrawer"
                            class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Origin & Destination Inputs -->
                    <div class="p-4 bg-slate-50 border-b border-slate-200 space-y-3">
                        <!-- Origin Input -->
                        <div class="relative">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Titik Awal (Origin)
                                </span>
                                <button @click="setOriginToCurrentLocation" class="text-indigo-600 hover:text-indigo-800 text-[10px] lowercase font-semibold">
                                    📍 Lokasi Saya
                                </button>
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    :value="routeOrigin.name || (routeOrigin.lat ? `${routeOrigin.lat.toFixed(5)}, ${routeOrigin.lng.toFixed(5)}` : '')"
                                    readonly
                                    placeholder="Pilih di peta atau klik 'Lokasi Saya'..."
                                    class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 text-slate-800 focus:ring-indigo-500"
                                />
                                <button
                                    @click="startPickingOrigin"
                                    :class="isPickingOrigin ? 'bg-amber-500 text-white ring-2 ring-amber-300' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-300'"
                                    class="p-2 rounded-lg text-xs font-semibold shrink-0 transition-all"
                                    title="Klik pada peta untuk menentukan titik"
                                >
                                    🗺️
                                </button>
                            </div>
                        </div>

                        <!-- Reverse Route Button -->
                        <div class="flex justify-center -my-1">
                            <button
                                @click="reverseRoute"
                                class="p-1 rounded-full bg-white border border-slate-300 shadow-xs text-slate-500 hover:text-indigo-600 hover:border-indigo-400 transition-all"
                                title="Tukar Titik Awal & Tujuan"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </button>
                        </div>

                        <!-- Destination Input -->
                        <div class="relative">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1 flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span> Titik Tujuan (Destination)
                                </span>
                                <span v-if="isPickingDest" class="text-amber-600 font-bold text-[10px] animate-pulse">Klik titik di peta...</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    :value="routeDest.name || (routeDest.lat ? `${routeDest.lat.toFixed(5)}, ${routeDest.lng.toFixed(5)}` : '')"
                                    readonly
                                    placeholder="Pilih di peta atau klik dari popup..."
                                    class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 text-slate-800 focus:ring-indigo-500"
                                />
                                <button
                                    @click="startPickingDest"
                                    :class="isPickingDest ? 'bg-amber-500 text-white ring-2 ring-amber-300' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-300'"
                                    class="p-2 rounded-lg text-xs font-semibold shrink-0 transition-all"
                                    title="Klik pada peta untuk menentukan tujuan"
                                >
                                    🗺️
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 pt-1">
                            <button
                                @click="calculateRoute"
                                :disabled="!routeOrigin.lat || !routeDest.lat || isCalculatingRoute"
                                class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-indigo-500/25 flex items-center justify-center gap-2 transition-all cursor-pointer"
                            >
                                <svg v-if="isCalculatingRoute" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ isCalculatingRoute ? 'Menghitung Rute...' : '🚀 Mulai Hitung Rute' }}</span>
                            </button>
                            <button
                                v-if="activeRoute"
                                @click="clearRoute"
                                class="p-2.5 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-xl text-xs font-bold border border-rose-200 transition-colors"
                                title="Hapus Rute"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Route Result & Turn-by-Turn Steps -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-4">
                        <div v-if="activeRoute" class="space-y-3">
                            <!-- Trip Summary Card -->
                            <div class="bg-gradient-to-br from-indigo-50 to-blue-50 border border-indigo-100 rounded-2xl p-3.5 shadow-xs">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-bold text-indigo-900">Ringkasan Perjalanan</span>
                                    <span v-if="activeRoute.is_fallback" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700">
                                        Straight-Line (Fallback)
                                    </span>
                                    <span v-else class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700">
                                        Road Navigation
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-center">
                                    <div class="bg-white p-2 rounded-xl border border-indigo-100/80">
                                        <div class="text-[10px] text-slate-500 font-medium">Jarak Tempuh</div>
                                        <div class="text-base font-black text-slate-800">{{ activeRoute.distance_text }}</div>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-indigo-100/80">
                                        <div class="text-[10px] text-slate-500 font-medium">Estimasi Waktu</div>
                                        <div class="text-base font-black text-indigo-600">{{ activeRoute.duration_text }}</div>
                                    </div>
                                </div>

                                <!-- External Google Maps Direct Navigation -->
                                <a
                                    :href="getGoogleMapsNavigationUrl()"
                                    target="_blank"
                                    class="mt-3 w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-sm transition-all"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span>Buka di Google Maps Langsung</span>
                                </a>
                            </div>

                            <!-- Steps Breakdown -->
                            <div v-if="activeRoute.steps && activeRoute.steps.length > 0">
                                <div class="text-xs font-bold text-slate-700 mb-2 flex items-center justify-between">
                                    <span>Petunjuk Jalan ({{ activeRoute.steps.length }} Langkah)</span>
                                </div>
                                <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                    <div
                                        v-for="(step, idx) in activeRoute.steps"
                                        :key="idx"
                                        class="flex items-start gap-2.5 p-2 rounded-xl bg-white border border-slate-100 shadow-xs text-xs"
                                    >
                                        <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-[10px] shrink-0 mt-0.5">
                                            {{ idx + 1 }}
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-slate-800 font-medium leading-snug">{{ step.instruction }}</p>
                                            <p class="text-[10px] text-slate-400 mt-0.5 font-mono">
                                                {{ formatDistance(step.distance) }} • {{ Math.round(step.duration) }} dtk
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-8 text-slate-400 space-y-2">
                            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
                                🗺️
                            </div>
                            <p class="text-xs text-slate-600 font-medium">Belum ada rute aktif</p>
                            <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                                Tentukan titik awal dan tujuan dengan klik di peta, atau pilih dari popup Pelanggan, ODP, atau ODC.
                            </p>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- ═══ 6. SMART CLOSEST ODP FINDER DRAWER ═══ -->
            <transition
                enter-active-class="transform transition ease-in-out duration-300"
                enter-from-class="-translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transform transition ease-in-out duration-300"
                leave-from-class="translate-x-0"
                leave-to-class="-translate-x-full"
            >
                <div
                    v-if="isOdpFinderActive"
                    class="absolute top-0 bottom-0 left-0 w-full sm:w-96 z-[1010] bg-white shadow-2xl border-r border-slate-200 flex flex-col"
                >
                    <!-- Header -->
                    <div class="p-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="2" />
                                <circle cx="12" cy="12" r="4" stroke-width="2" />
                            </svg>
                            <div>
                                <h3 class="font-bold text-sm leading-tight">Smart ODP Recommender</h3>
                                <p class="text-[11px] text-emerald-100 font-normal">Cek Kapasitas & Jarak Terdekat</p>
                            </div>
                        </div>
                        <button
                            @click="isOdpFinderActive = false"
                            class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Target Location Picker -->
                    <div class="p-4 bg-slate-50 border-b border-slate-200 space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">
                                Titik Survei / Calon Pelanggan:
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    :value="finderTarget.name || (finderTarget.lat ? `${finderTarget.lat.toFixed(5)}, ${finderTarget.lng.toFixed(5)}` : '')"
                                    readonly
                                    placeholder="Klik di peta atau pilih pelanggan..."
                                    class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 text-slate-800 focus:ring-emerald-500"
                                />
                                <button
                                    @click="startPickingFinderTarget"
                                    :class="isPickingFinderTarget ? 'bg-amber-500 text-white ring-2 ring-amber-300' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-300'"
                                    class="p-2 rounded-lg text-xs font-semibold shrink-0 transition-all"
                                    title="Pilih Titik di Peta"
                                >
                                    🎯
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="findNearbyOdps"
                                :disabled="!finderTarget.lat || isLoadingNearby"
                                class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-emerald-500/25 flex items-center justify-center gap-2 transition-all cursor-pointer"
                            >
                                <svg v-if="isLoadingNearby" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span>{{ isLoadingNearby ? 'Menganalisis ODP...' : '⚡ Cari ODP Terdekat' }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Ranked ODP Results -->
                    <div class="flex-1 overflow-y-auto p-4 space-y-3">
                        <div v-if="nearbyOdps.length > 0" class="space-y-3">
                            <div class="text-xs font-bold text-slate-700 flex items-center justify-between">
                                <span>Daftar ODP Terdekat ({{ nearbyOdps.length }})</span>
                            </div>

                            <div
                                v-for="(odp, idx) in nearbyOdps"
                                :key="odp.id"
                                class="p-3 rounded-2xl bg-white border border-slate-200/90 shadow-xs hover:border-emerald-300 transition-all space-y-2"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold flex items-center justify-center">
                                                #{{ idx + 1 }}
                                            </span>
                                            <span class="font-bold text-slate-800 text-xs">{{ odp.name }}</span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">{{ odp.address || 'Tanpa alamat rinci' }}</p>
                                    </div>
                                    <span
                                        :class="odp.available_ports > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase shrink-0"
                                    >
                                        {{ odp.available_ports > 0 ? `Sisa ${odp.available_ports} Port` : 'PORT FULL' }}
                                    </span>
                                </div>

                                <!-- Progress Capacity -->
                                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                                    <div class="flex justify-between text-[10px] text-slate-600 mb-1">
                                        <span>Penggunaan Port: {{ odp.used_ports }}/{{ odp.total_ports }}</span>
                                        <span class="font-bold">{{ Math.round((odp.used_ports / (odp.total_ports || 1)) * 100) }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div
                                            :style="{ width: `${Math.min(100, Math.round((odp.used_ports / (odp.total_ports || 1)) * 100))}%` }"
                                            :class="odp.available_ports > 0 ? 'bg-emerald-500' : 'bg-rose-500'"
                                            class="h-1.5 rounded-full transition-all"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Metrics: Distance & Fiber Attenuation Loss -->
                                <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                    <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                                        <div class="text-[9px] text-slate-400">Jarak Garis Lurus</div>
                                        <div class="font-bold text-slate-800 font-mono">{{ formatDistance(odp.distance_meters) }}</div>
                                    </div>
                                    <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100">
                                        <div class="text-[9px] text-slate-400">Est. Redaman Kabel</div>
                                        <div class="font-bold text-rose-600 font-mono">-{{ ((odp.distance_meters / 1000) * 0.35).toFixed(2) }} dB</div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2 pt-1">
                                    <button
                                        @click="focusMapToCoords(odp.latitude, odp.longitude, 18)"
                                        class="flex-1 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition-colors"
                                    >
                                        🔍 Lihat di Peta
                                    </button>
                                    <button
                                        @click="createRouteToOdp(odp)"
                                        class="flex-1 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-[11px] font-bold transition-colors flex items-center justify-center gap-1"
                                    >
                                        <span>🚗 Rute ke Sini</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-slate-400 space-y-2">
                            <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                                🎯
                            </div>
                            <p class="text-xs text-slate-600 font-medium">Pilih titik survei terlebih dahulu</p>
                            <p class="text-[11px] text-slate-400 max-w-xs mx-auto">
                                Klik tombol 🎯 lalu tentukan lokasi di peta untuk menemukan ODP dengan sinyal terdekat dan port tersedia.
                            </p>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- ═══ 7. SYMBOL LEGEND MODAL ═══ -->
            <div
                v-if="showLegendModal"
                class="fixed inset-0 z-[2000] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
            >
                <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-md w-full p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🗺️</span>
                            <h3 class="font-bold text-slate-900 text-sm">Panduan Simbol & Layer GIS</h3>
                        </div>
                        <button @click="showLegendModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded bg-blue-600 text-white flex items-center justify-center text-[10px] font-bold shrink-0">ODC</span>
                            <div>
                                <p class="font-semibold text-slate-800">ODC (Optical Distribution Cabinet)</p>
                                <p class="text-slate-500 text-[11px]">Kabinet induk fiber optik yang melayani puluhan ODP.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold shrink-0">ODP</span>
                            <div>
                                <p class="font-semibold text-slate-800">ODP Port Tersedia (Hijau / Kuning)</p>
                                <p class="text-slate-500 text-[11px]">ODP yang memiliki slot port kosong untuk pelanggan baru.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-rose-500 text-white flex items-center justify-center text-[10px] font-bold shrink-0">ODP</span>
                            <div>
                                <p class="font-semibold text-slate-800">ODP Port Penuh (Merah)</p>
                                <p class="text-slate-500 text-[11px]">Semua port splitter sudah terpakai (Full Capacity).</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full bg-emerald-500 border-2 border-white shadow-xs shrink-0"></span>
                            <div>
                                <p class="font-semibold text-slate-800">Pelanggan Online (Denyut Hijau)</p>
                                <p class="text-slate-500 text-[11px]">Sesi PPPoE/Hotspot aktif pada NAS terkait, dari API atau accounting RADIUS terbaru.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full bg-rose-500 border-2 border-white shadow-xs shrink-0"></span>
                            <div>
                                <p class="font-semibold text-slate-800">Pelanggan Offline (Denyut Merah)</p>
                                <p class="text-slate-500 text-[11px]">API berhasil dibaca tetapi sesi pelanggan tidak ditemukan. Penyebab putus perlu diperiksa terpisah.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full bg-slate-400 border-2 border-white shadow-xs shrink-0"></span>
                            <div>
                                <p class="font-semibold text-slate-800">Status Belum Diketahui (Abu-abu)</p>
                                <p class="text-slate-500 text-[11px]">API tidak terjangkau, data kedaluwarsa, atau username/NAS belum dipetakan. Status langganan ditampilkan terpisah.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-8 h-1 bg-blue-500 border-dashed border-t-2 border-blue-600 shrink-0"></span>
                            <div>
                                <p class="font-semibold text-slate-800">Kabel Feeder (ODC ➔ ODP)</p>
                                <p class="text-slate-500 text-[11px]">Jalur kabel distribusi utama.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="w-8 h-1 bg-emerald-400 border-dotted border-t-2 border-emerald-500 shrink-0"></span>
                            <div>
                                <p class="font-semibold text-slate-800">Kabel Drop Core (ODP ➔ Pelanggan)</p>
                                <p class="text-slate-500 text-[11px]">Jalur kabel sambungan ke rumah pelanggan.</p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button
                            @click="showLegendModal = false"
                            class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import { connectionPresentation } from '@/Utils/networkConnectionStatus';

// ═══ PROPS DARI BACKEND ═══
const props = defineProps({
    odcs: { type: Array, default: () => [] },
    odps: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    initialFocus: { type: Object, default: () => ({}) },
});

// ═══ ROLE & PERMISSION AUTH ═══
const page = usePage();
const authUser = computed(() => page.props.auth?.user || {});
const userRole = computed(() => authUser.value?.role?.name || authUser.value?.role || '');
const isAdmin = computed(() => ['admin', 'superadmin', 'super-admin'].includes(String(userRole.value).toLowerCase()));
const permissions = computed(() => page.props.auth?.permissions || authUser.value?.permissions || []);

const hasPermission = (perm) => {
    if (isAdmin.value) return true;
    if (Array.isArray(permissions.value)) {
        return permissions.value.includes(perm);
    }
    return false;
};

// ═══ REFS & STATE ═══
const mapContainer = ref(null);
let map = null;
let L = null;

// Layer Groups
let tileLayer = null;
let clusterGroup = null;
let odpClusterGroup = null;
let odcLayerGroup = null;
let odpLayerGroup = null;
let customerLayerGroup = null;
let areaLayerGroup = null;
let areaBadgeLayerGroup = null;
let fiberLinesLayerGroup = null;
let routeLayerGroup = null;
let rulerLayerGroup = null;
let userLocationMarker = null;
const customerMarkers = new Map();
let resizeObserver = null;
let connectionTimer = null;
let expiryTimer = null;
let connectionRequest = null;
let disposed = false;
const connectionRows = ref({});
const connectionClock = ref(Date.now());
const isRefreshingConnections = ref(false);
const connectionUpdatedAt = ref('');
const connectionError = ref('');
const customerConnection = customer => connectionPresentation(connectionRows.value[customer.id], connectionClock.value);
const connectionCounts = computed(() => {
    const counts = { online: 0, offline: 0, unknown: 0 };
    props.customers.forEach(customer => {
        if (selectedAreaFilter.value === 'all' || customer.area_id == selectedAreaFilter.value) counts[customerConnection(customer).status]++;
    });
    return counts;
});

const updateConnectionMarkers = () => {
    connectionClock.value = Date.now();
    customerMarkers.forEach(({ marker, customer, status }) => {
        const current = customerConnection(customer);
        if (status !== current.status) {
            marker.setIcon(createCustomerIcon(current.status));
            customerMarkers.set(customer.id, { marker, customer, status: current.status });
        }
        if (marker.isPopupOpen()) marker.setPopupContent(() => customerPopup(customer));
    });
};

const refreshConnections = async () => {
    if (disposed || document.hidden || isRefreshingConnections.value) return;
    isRefreshingConnections.value = true;
    const request = new AbortController();
    connectionRequest = request;
    try {
        const { data } = await axios.get('/network-map/connections', { signal: request.signal, timeout: 25000 });
        if (disposed) return;
        if (!Array.isArray(data.customers)) throw new Error('Invalid connection snapshot');
        connectionRows.value = Object.fromEntries(data.customers.map(row => [row.id, row]));
        connectionUpdatedAt.value = new Date(data.generated_at).toLocaleTimeString('id-ID');
        connectionError.value = '';
        updateConnectionMarkers();
    } catch (error) {
        if (!axios.isCancel(error) && !disposed) {
            connectionError.value = 'Pembaruan gagal · mencoba kembali otomatis';
            updateConnectionMarkers();
        }
    } finally {
        if (connectionRequest === request) { isRefreshingConnections.value = false; connectionRequest = null; }
    }
};
const onConnectionVisibility = () => {
    if (!document.hidden) { updateConnectionMarkers(); refreshConnections(); }
};

// UI Panels
const openPanel = ref(null); // 'basemap' | 'layers' | null
const showLegendModal = ref(false);

const togglePanel = (panelName) => {
    openPanel.value = openPanel.value === panelName ? null : panelName;
};

// Search & Filter
const searchQuery = ref('');
const isSearchFocused = ref(false);
const selectedAreaFilter = ref('all');

const layers = ref({
    customers: true,
    odps: true,
    odcs: true,
    areas: true,
    fiberLines: true,
    cluster: true,
});

// Filtered Counts for HUD
const filteredCustomersCount = computed(() => {
    if (selectedAreaFilter.value === 'all') return props.customers.length;
    return props.customers.filter(c => c.area_id == selectedAreaFilter.value).length;
});
const filteredOdpsCount = computed(() => {
    if (selectedAreaFilter.value === 'all') return props.odps.length;
    return props.odps.filter(o => o.area_id == selectedAreaFilter.value).length;
});
const filteredOdcsCount = computed(() => {
    if (selectedAreaFilter.value === 'all') return props.odcs.length;
    return props.odcs.filter(o => o.area_id == selectedAreaFilter.value).length;
});
const filteredAreasCount = computed(() => {
    if (selectedAreaFilter.value === 'all') return props.areas.length;
    return props.areas.filter(a => a.id == selectedAreaFilter.value).length;
});

const onAreaFilterChange = () => {
    refreshMarkers();
    toggleAreasLayer();
    toggleFiberLines();
};

// Basemaps
const activeBasemap = ref('osm');
const basemaps = [
    { id: 'osm', name: 'OpenStreetMap (Standar)', url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', attribution: '&copy; OSM' },
    { id: 'google_sat', name: 'Google Satellite (Satelit)', url: 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', attribution: '&copy; Google' },
    { id: 'carto_dark', name: 'Dark Mode (NOC Command)', url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', attribution: '&copy; CartoDB' },
    { id: 'topo', name: 'Topografi / Kontur', url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', attribution: '&copy; OpenTopo' },
];

const activeBasemapLabel = computed(() => {
    const found = basemaps.find(b => b.id === activeBasemap.value);
    return found ? found.name.split(' ')[0] : 'Peta';
});

// User GPS Location
const myLocation = ref(null);
const isLocating = ref(false);

// Routing State
const isRoutingActive = ref(false);
const isPickingOrigin = ref(false);
const isPickingDest = ref(false);
const isCalculatingRoute = ref(false);
const routeOrigin = ref({ lat: null, lng: null, name: '' });
const routeDest = ref({ lat: null, lng: null, name: '' });
const activeRoute = ref(null);

// Smart ODP Finder State
const isOdpFinderActive = ref(false);
const isPickingFinderTarget = ref(false);
const isLoadingNearby = ref(false);
const finderTarget = ref({ lat: null, lng: null, name: '' });
const nearbyOdps = ref([]);

// Ruler Tool State
const isRulerActive = ref(false);
const rulerPoints = ref([]);
const rulerDistanceMeters = ref(0);
const rulerLossDb = computed(() => {
    // 0.35 dB/km for fiber + 0.1 dB splice loss per point
    const fiberLoss = (rulerDistanceMeters.value / 1000) * 0.35;
    const spliceLoss = Math.max(0, rulerPoints.value.length - 1) * 0.1;
    return fiberLoss + spliceLoss;
});

// ═══ COMPUTED SEARCH RESULTS ═══
const searchResults = computed(() => {
    if (!searchQuery.value || searchQuery.value.trim().length < 2) return [];
    const q = searchQuery.value.toLowerCase().trim();
    const results = [];

    // Search Customers
    props.customers.forEach(c => {
        if (
            (c.name && c.name.toLowerCase().includes(q)) ||
            (c.customer_code && c.customer_code.toLowerCase().includes(q)) ||
            (c.phone && c.phone.includes(q))
        ) {
            results.push({
                type: 'customer',
                id: c.id,
                title: c.name,
                subtitle: `${c.customer_code} • ${c.area_name || 'Umum'} • ${c.phone || '-'}`,
                lat: c.latitude,
                lng: c.longitude,
                typeLabel: 'Pelanggan',
                badgeClass: 'bg-emerald-100 text-emerald-800',
            });
        }
    });

    // Search ODP
    props.odps.forEach(o => {
        if (o.name && o.name.toLowerCase().includes(q)) {
            results.push({
                type: 'odp',
                id: o.id,
                title: o.name,
                subtitle: `Port: ${o.used_ports}/${o.total_ports} • ${o.area?.name || 'Umum'}`,
                lat: o.latitude,
                lng: o.longitude,
                typeLabel: 'ODP',
                badgeClass: 'bg-amber-100 text-amber-800',
            });
        }
    });

    // Search ODC
    props.odcs.forEach(c => {
        if (c.name && c.name.toLowerCase().includes(q)) {
            results.push({
                type: 'odc',
                id: c.id,
                title: c.name,
                subtitle: `Kapasitas: ${c.capacity || '-'} • ${c.odps_count || 0} ODP`,
                lat: c.latitude,
                lng: c.longitude,
                typeLabel: 'ODC',
                badgeClass: 'bg-blue-100 text-blue-800',
            });
        }
    });

    // Search Areas
    props.areas.forEach(a => {
        if (a.name && a.name.toLowerCase().includes(q)) {
            results.push({
                type: 'area',
                id: a.id,
                title: a.name,
                subtitle: `${a.total_customers || 0} Pelanggan • ${a.total_odp || 0} ODP`,
                lat: a.latitude,
                lng: a.longitude,
                typeLabel: 'Area',
                badgeClass: 'bg-purple-100 text-purple-800',
            });
        }
    });

    return results.slice(0, 15);
});

// ═══ HELPER FUNCTIONS ═══
const formatDistance = (meters) => {
    if (!meters || meters <= 0) return '0 m';
    if (meters >= 1000) {
        return (meters / 1000).toFixed(2) + ' km';
    }
    return Math.round(meters) + ' m';
};

const calculateDistanceMeters = (lat1, lon1, lat2, lon2) => {
    const R = 6371e3;
    const φ1 = (lat1 * Math.PI) / 180;
    const φ2 = (lat2 * Math.PI) / 180;
    const Δφ = ((lat2 - lat1) * Math.PI) / 180;
    const Δλ = ((lon2 - lon1) * Math.PI) / 180;

    const a =
        Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
};

// ═══ LEAFLET INITIALIZATION ═══
onMounted(async () => {
    // 1. Expose Window Helpers for Leaflet Popups
    window.networkMapSetRouteDest = (lat, lng, name) => {
        routeDest.value = { lat: parseFloat(lat), lng: parseFloat(lng), name };
        isRoutingActive.value = true;
        if (!routeOrigin.value.lat && myLocation.value) {
            routeOrigin.value = { lat: myLocation.value.lat, lng: myLocation.value.lng, name: 'Lokasi Saya' };
        }
        if (routeOrigin.value.lat && routeDest.value.lat) {
            calculateRoute();
        }
    };

    window.networkMapSetRouteOrigin = (lat, lng, name) => {
        routeOrigin.value = { lat: parseFloat(lat), lng: parseFloat(lng), name };
        isRoutingActive.value = true;
    };

    window.networkMapFindNearbyOdp = (lat, lng, name) => {
        finderTarget.value = { lat: parseFloat(lat), lng: parseFloat(lng), name };
        isOdpFinderActive.value = true;
        findNearbyOdps();
    };

    window.networkMapCopyCoords = (lat, lng) => {
        const text = `${lat}, ${lng}`;
        navigator.clipboard.writeText(text);
        alert(`Koordinat ${text} disalin ke clipboard!`);
    };

    // 2. Dynamic Import Leaflet & MarkerCluster
    const LeafletModule = await import('leaflet');
    L = LeafletModule.default || LeafletModule;
    window.L = L;

    await import('leaflet/dist/leaflet.css');
    await import('leaflet.markercluster/dist/MarkerCluster.css');
    await import('leaflet.markercluster/dist/MarkerCluster.Default.css');
    await import('leaflet.markercluster');
    if (disposed) return;

    // 3. Determine Initial Map Center
    let defaultLat = -6.998482;
    let defaultLng = 106.717579;
    let defaultZoom = 13;

    if (props.initialFocus && props.initialFocus.lat && props.initialFocus.lng) {
        defaultLat = parseFloat(props.initialFocus.lat);
        defaultLng = parseFloat(props.initialFocus.lng);
        defaultZoom = parseInt(props.initialFocus.zoom) || 16;
    } else if (props.odcs.length > 0) {
        defaultLat = parseFloat(props.odcs[0].latitude);
        defaultLng = parseFloat(props.odcs[0].longitude);
    } else if (props.odps.length > 0) {
        defaultLat = parseFloat(props.odps[0].latitude);
        defaultLng = parseFloat(props.odps[0].longitude);
    }

    // 4. Create Leaflet Map Instance
    map = L.map(mapContainer.value, {
        center: [defaultLat, defaultLng],
        zoom: defaultZoom,
        zoomControl: false, // will put custom zoom in controls or keep neat
    });

    // Custom Zoom Control at Bottom Right
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    // Initial Tile Layer
    changeBasemap(activeBasemap.value);

    // Initialize Layer Groups
    odcLayerGroup = L.layerGroup().addTo(map);
    odpLayerGroup = L.layerGroup();
    customerLayerGroup = L.layerGroup();
    areaLayerGroup = L.layerGroup().addTo(map);
    areaBadgeLayerGroup = L.layerGroup().addTo(map);
    fiberLinesLayerGroup = L.layerGroup().addTo(map);
    routeLayerGroup = L.layerGroup().addTo(map);
    rulerLayerGroup = L.layerGroup().addTo(map);

    // Customer Cluster Group
    clusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 16,
        spiderfyDistanceMultiplier: 1.5,
    });
    map.addLayer(clusterGroup);

    // ODP Cluster Group (Clustering ODP pins at lower zooms to avoid clutter)
    odpClusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 40,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 14,
        spiderfyDistanceMultiplier: 1.4,
        iconCreateFunction: (cluster) => {
            const count = cluster.getChildCount();
            return L.divIcon({
                html: `
                    <div class="relative group cursor-pointer" style="width: 32px; height: 32px;">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-amber-500 to-yellow-600 border-2 border-white shadow-lg flex items-center justify-center text-white font-black text-xs transform hover:scale-110 transition-transform">
                            ${count}
                        </div>
                    </div>
                `,
                className: 'custom-odp-cluster',
                iconSize: [32, 32],
                iconAnchor: [16, 16],
            });
        }
    });
    map.addLayer(odpClusterGroup);

    // 5. Map Click & Zoom Event Handlers
    map.on('click', (e) => {
        handleMapClick(e.latlng.lat, e.latlng.lng);
    });

    map.on('zoomend', () => {
        updateAreaBadgesVisibility();
    });

    // 6. Draw All Layers
    refreshMarkers();
    toggleAreasLayer();
    toggleFiberLines();

    // 7. Auto fit bounds to visible assets (Priority to infrastructure to prevent outlier coordinates skew)
    if (!props.initialFocus || !props.initialFocus.lat) {
        const infraPoints = [];
        props.odcs.forEach(o => { if (o.latitude && o.longitude) infraPoints.push([parseFloat(o.latitude), parseFloat(o.longitude)]); });
        props.odps.forEach(o => { if (o.latitude && o.longitude) infraPoints.push([parseFloat(o.latitude), parseFloat(o.longitude)]); });

        if (infraPoints.length > 0) {
            const infraBounds = L.latLngBounds(infraPoints);
            const infraCenter = infraBounds.getCenter();

            // Include customers within 30km from infrastructure center to filter out rogue/test outlier coordinates
            const validPoints = [...infraPoints];
            props.customers.forEach(c => {
                if (c.latitude && c.longitude) {
                    const cLat = parseFloat(c.latitude);
                    const cLng = parseFloat(c.longitude);
                    const dist = calculateDistanceMeters(infraCenter.lat, infraCenter.lng, cLat, cLng);
                    if (dist <= 30000) {
                        validPoints.push([cLat, cLng]);
                    }
                }
            });

            const bounds = L.latLngBounds(validPoints);
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
        } else if (props.customers.length > 0) {
            const custPoints = props.customers.filter(c => c.latitude && c.longitude).map(c => [parseFloat(c.latitude), parseFloat(c.longitude)]);
            if (custPoints.length > 0) {
                map.fitBounds(L.latLngBounds(custPoints), { padding: [50, 50], maxZoom: 15 });
            } else {
                locateMe(false);
            }
        } else {
            locateMe(false);
        }
    }

    // Auto Invalidate Size
    resizeObserver = new ResizeObserver(() => {
        if (map) requestAnimationFrame(() => map.invalidateSize());
    });
    if (mapContainer.value) resizeObserver.observe(mapContainer.value);
    refreshConnections();
    connectionTimer = setInterval(refreshConnections, 30000);
    expiryTimer = setInterval(updateConnectionMarkers, 5000);
    document.addEventListener('visibilitychange', onConnectionVisibility);
});

onUnmounted(() => {
    disposed = true;
    connectionRequest?.abort();
    clearInterval(connectionTimer);
    clearInterval(expiryTimer);
    resizeObserver?.disconnect();
    document.removeEventListener('visibilitychange', onConnectionVisibility);
    customerMarkers.clear();
    delete window.networkMapSetRouteDest;
    delete window.networkMapSetRouteOrigin;
    delete window.networkMapFindNearbyOdp;
    delete window.networkMapCopyCoords;

    if (map) {
        map.remove();
        map = null;
    }
});

// ═══ BASEMAP CHANGER ═══
const changeBasemap = (typeId) => {
    activeBasemap.value = typeId;
    openPanel.value = null;

    if (tileLayer && map) {
        map.removeLayer(tileLayer);
    }

    const selected = basemaps.find(b => b.id === typeId) || basemaps[0];
    tileLayer = L.tileLayer(selected.url, {
        attribution: selected.attribution,
        maxZoom: 20,
        subdomains: ['a', 'b', 'c'],
    }).addTo(map);
};

// ═══ MARKER ICONS FACTORY ═══
const createOdcIcon = (capacity, odpsCount) => {
    return L.divIcon({
        className: 'custom-odc-marker',
        html: `
            <div class="relative group cursor-pointer" style="width: 36px; height: 36px;">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 border-2 border-white shadow-lg flex items-center justify-center text-white transform hover:scale-110 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                </div>
                <div class="absolute -bottom-1 -right-1 px-1 bg-amber-400 text-slate-900 font-black text-[9px] rounded-full border border-white shadow-xs">
                    ${odpsCount || 0}
                </div>
            </div>
        `,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
        popupAnchor: [0, -20],
    });
};

const createOdpIcon = (used, total) => {
    const available = Math.max(0, (total || 8) - (used || 0));
    let colorClass = 'from-emerald-500 to-teal-600';
    let ringClass = 'border-emerald-200';

    if (available === 0) {
        colorClass = 'from-rose-600 to-red-700';
        ringClass = 'border-rose-200';
    } else if (available === 1) {
        colorClass = 'from-amber-500 to-yellow-600';
        ringClass = 'border-amber-200';
    }

    return L.divIcon({
        className: 'custom-odp-marker',
        html: `
            <div class="relative group cursor-pointer" style="width: 30px; height: 30px;">
                <div class="w-7 h-7 rounded-full bg-gradient-to-tr ${colorClass} border-2 border-white shadow-md flex items-center justify-center text-white font-bold text-[9px] transform hover:scale-110 transition-transform">
                    ${used || 0}/${total || 8}
                </div>
            </div>
        `,
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        popupAnchor: [0, -16],
    });
};

const createCustomerIcon = (status) => {
    return L.divIcon({
        className: 'custom-customer-marker',
        html: `<span class="gis-connection-dot gis-connection-${status}"><span></span></span>`,
        iconSize: [24, 24], iconAnchor: [12, 12], popupAnchor: [0, -12],
    });
};

// ═══ REFRESH MAP MARKERS ═══
const refreshMarkers = () => {
    if (!map || !L || !clusterGroup) return;

    clusterGroup.clearLayers();
    if (odpClusterGroup) odpClusterGroup.clearLayers();
    odcLayerGroup.clearLayers();
    odpLayerGroup.clearLayers();
    customerLayerGroup.clearLayers();
    customerMarkers.clear();

    // Mapping koordinat ODC untuk cek tumpukan dengan ODP
    const odcCoords = [];
    props.odcs.forEach(odc => {
        if (odc.latitude && odc.longitude) {
            odcCoords.push([parseFloat(odc.latitude), parseFloat(odc.longitude)]);
        }
    });

    // Mapping koordinat ODP untuk cek tumpukan titik dengan pelanggan
    const odpCoordsMap = new Map();
    props.odps.forEach(odp => {
        if (odp.latitude && odp.longitude) {
            odpCoordsMap.set(odp.id, [parseFloat(odp.latitude), parseFloat(odp.longitude)]);
        }
    });

    // 1. Plot ODCs (Infrastruktur Utama - Core distribution)
    if (layers.value.odcs) {
        props.odcs.forEach(odc => {
            if (selectedAreaFilter.value !== 'all' && odc.area_id != selectedAreaFilter.value) return;

            const marker = L.marker([parseFloat(odc.latitude), parseFloat(odc.longitude)], {
                icon: createOdcIcon(odc.capacity, odc.odps_count),
                title: odc.name,
                zIndexOffset: 100, // Menjamin ODC selalu berada di atas ODP
            });

            marker.bindPopup(`
                <div class="p-1 font-sans text-xs min-w-[220px]">
                    <div class="flex items-center justify-between pb-1.5 mb-2 border-b border-slate-100">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase">ODC Core</span>
                        <span class="text-[10px] text-slate-400 font-mono">${odc.capacity || 'Kapasitas N/A'}</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">${odc.name}</h4>
                    <p class="text-[11px] text-slate-500 mb-2">${odc.address || 'Alamat tidak diatur'}</p>
                    <div class="bg-slate-50 p-2 rounded-lg border border-slate-100 text-[11px] space-y-1 mb-2.5">
                        <div class="flex justify-between text-slate-600">
                            <span>ODP Terhubung:</span>
                            <span class="font-bold text-slate-800">${odc.odps_count || 0} ODP</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Wilayah:</span>
                            <span class="font-medium text-slate-800">${odc.area?.name || 'Umum'}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 pt-1">
                        <button onclick="window.networkMapSetRouteDest(${odc.latitude}, ${odc.longitude}, '${odc.name}')" class="py-1 px-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[11px] font-semibold text-center transition-colors">
                            🚗 Buat Rute
                        </button>
                        <button onclick="window.networkMapCopyCoords(${odc.latitude}, ${odc.longitude})" class="py-1 px-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-semibold text-center transition-colors">
                            📋 Salin Tikor
                        </button>
                    </div>
                </div>
            `);

            odcLayerGroup.addLayer(marker);
        });
    }

    // 2. Plot ODPs (Titik Distribusi Tiang - Mendukung clustering agar tidak bertumpuk di zoom jauh)
    if (layers.value.odps) {
        props.odps.forEach(odp => {
            if (selectedAreaFilter.value !== 'all' && odp.area_id != selectedAreaFilter.value) return;

            let oLat = parseFloat(odp.latitude);
            let oLng = parseFloat(odp.longitude);

            // Jitter / offset (~15m) jika koordinat ODP persis menempel pada titik ODC
            odcCoords.forEach(([odcLat, odcLng]) => {
                if (Math.abs(oLat - odcLat) < 0.00008 && Math.abs(oLng - odcLng) < 0.00008) {
                    oLat += 0.00018;
                    oLng += 0.00018;
                }
            });

            const marker = L.marker([oLat, oLng], {
                icon: createOdpIcon(odp.used_ports, odp.total_ports),
                title: odp.name,
                zIndexOffset: 50,
            });

            const availPorts = Math.max(0, (odp.total_ports || 8) - (odp.used_ports || 0));

            marker.bindPopup(`
                <div class="p-1 font-sans text-xs min-w-[220px]">
                    <div class="flex items-center justify-between pb-1.5 mb-2 border-b border-slate-100">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold ${availPorts > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'} uppercase">
                            ${availPorts > 0 ? `Tersedia ${availPorts} Port` : 'PORT PENUH'}
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">${odp.used_ports}/${odp.total_ports}</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-sm mb-1">${odp.name}</h4>
                    <p class="text-[11px] text-slate-500 mb-2">${odp.address || 'Alamat belum diatur'}</p>
                    <div class="bg-slate-50 p-2 rounded-lg border border-slate-100 text-[11px] space-y-1 mb-2.5">
                        <div class="flex justify-between text-slate-600">
                            <span>ODC Induk:</span>
                            <span class="font-medium text-slate-800">${odp.odc?.name || 'Langsung ke OLT'}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Pelanggan Aktif:</span>
                            <span class="font-bold text-slate-800">${odp.customer_count || odp.used_ports || 0} user</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 pt-1">
                        <button onclick="window.networkMapSetRouteDest(${odp.latitude}, ${odp.longitude}, '${odp.name}')" class="py-1 px-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[11px] font-semibold text-center transition-colors">
                            🚗 Buat Rute
                        </button>
                        <button onclick="window.networkMapFindNearbyOdp(${odp.latitude}, ${odp.longitude}, '${odp.name}')" class="py-1 px-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-semibold text-center transition-colors">
                            🎯 Sekitar Sini
                        </button>
                    </div>
                </div>
            `);

            if (layers.value.cluster && odpClusterGroup) {
                odpClusterGroup.addLayer(marker);
            } else {
                odpLayerGroup.addLayer(marker);
            }
        });
    }

    // 3. Plot Customers (Titik Pelanggan)
    if (layers.value.customers) {
        props.customers.forEach(cust => {
            if (selectedAreaFilter.value !== 'all' && cust.area_id != selectedAreaFilter.value) return;

            let cLat = parseFloat(cust.latitude);
            let cLng = parseFloat(cust.longitude);

            // Jitter / offset (~10 meter) jika titik pelanggan persis sama dengan titik ODP-nya
            if (cust.odp_id && odpCoordsMap.has(cust.odp_id)) {
                const odpCoords = odpCoordsMap.get(cust.odp_id);
                if (Math.abs(cLat - odpCoords[0]) < 0.00003 && Math.abs(cLng - odpCoords[1]) < 0.00003) {
                    cLat += 0.00010;
                    cLng += 0.00010;
                }
            }

            const marker = L.marker([cLat, cLng], {
                icon: createCustomerIcon(customerConnection(cust).status),
                title: cust.name,
            });
            marker.bindPopup(() => customerPopup(cust), { autoPanPaddingTopLeft: [12, 80], autoPanPaddingBottomRight: [12, 150] });
            customerMarkers.set(cust.id, { marker, customer: cust, status: customerConnection(cust).status });

            if (layers.value.cluster) {
                clusterGroup.addLayer(marker);
            } else {
                customerLayerGroup.addLayer(marker);
            }
        });
    }

    // Kelola visibilitas layer group pada Leaflet map
    if (layers.value.odcs) {
        if (!map.hasLayer(odcLayerGroup)) map.addLayer(odcLayerGroup);
    } else {
        if (map.hasLayer(odcLayerGroup)) map.removeLayer(odcLayerGroup);
    }

    if (layers.value.odps) {
        if (layers.value.cluster) {
            if (odpClusterGroup && !map.hasLayer(odpClusterGroup)) map.addLayer(odpClusterGroup);
            if (map.hasLayer(odpLayerGroup)) map.removeLayer(odpLayerGroup);
        } else {
            if (odpClusterGroup && map.hasLayer(odpClusterGroup)) map.removeLayer(odpClusterGroup);
            if (!map.hasLayer(odpLayerGroup)) map.addLayer(odpLayerGroup);
        }
    } else {
        if (odpClusterGroup && map.hasLayer(odpClusterGroup)) map.removeLayer(odpClusterGroup);
        if (map.hasLayer(odpLayerGroup)) map.removeLayer(odpLayerGroup);
    }

    if (layers.value.customers) {
        if (layers.value.cluster) {
            if (!map.hasLayer(clusterGroup)) map.addLayer(clusterGroup);
            if (map.hasLayer(customerLayerGroup)) map.removeLayer(customerLayerGroup);
        } else {
            if (map.hasLayer(clusterGroup)) map.removeLayer(clusterGroup);
            if (!map.hasLayer(customerLayerGroup)) map.addLayer(customerLayerGroup);
        }
    } else {
        if (map.hasLayer(clusterGroup)) map.removeLayer(clusterGroup);
        if (map.hasLayer(customerLayerGroup)) map.removeLayer(customerLayerGroup);
    }
};

const escapePopup = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
const customerPopup = cust => {
    const connection = customerConnection(cust);
    const cLat = Number(cust.latitude);
    const cLng = Number(cust.longitude);
    const routeName = escapePopup(JSON.stringify(String(cust.name)));
    return `
                <div class="p-1 font-sans text-xs min-w-[220px]">
                    <div class="flex items-center justify-between pb-1.5 mb-2 border-b border-slate-100">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold ${connection.badge} uppercase">
                            ${connection.label}
                        </span>
                        <span class="text-[10px] text-slate-400 font-mono">${escapePopup(cust.customer_code)}</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-sm mb-0.5">${escapePopup(cust.name)}</h4>
                    <p class="text-[11px] text-slate-500 mb-2">${escapePopup(cust.address || 'Alamat pelanggan')}</p>
                    <p class="text-[10px] text-slate-500 mb-1">${escapePopup(connection.message)}</p>
                    <p class="text-[10px] text-slate-500 mb-2">Sumber: ${connection.source} · Diperiksa: ${escapePopup(connection.checkedAt)}</p>
                    <div class="bg-slate-50 p-2 rounded-lg border border-slate-100 text-[11px] space-y-1 mb-2.5">
                        <div class="flex justify-between text-slate-600">
                            <span>Paket:</span>
                            <span class="font-bold text-indigo-600">${escapePopup(cust.package_name || 'Standard')} (${escapePopup(cust.package_speed || '-')})</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>ODP Terhubung:</span>
                            <span class="font-medium text-slate-800">${escapePopup(cust.odp_name || 'Belum Terpasang')}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Wilayah:</span>
                            <span class="text-slate-700">${escapePopup(cust.area_name || 'Umum')}</span>
                        </div>
                        <div class="flex justify-between text-slate-600"><span>Status langganan:</span><span>${escapePopup(cust.status || '—')}</span></div>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 pt-1">
                        <button onclick="window.networkMapSetRouteDest(${cLat}, ${cLng}, ${routeName})" class="py-1 px-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-[11px] font-semibold text-center transition-colors">
                            🚗 Buat Rute
                        </button>
                        <button onclick="window.networkMapFindNearbyOdp(${cLat}, ${cLng}, ${routeName})" class="py-1 px-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-semibold text-center transition-colors">
                            🎯 ODP Terdekat
                        </button>
                    </div>
                </div>
            `;
};

// Update visibilitas badge wilayah berdasarkan level zoom (Hanya tampil saat zoom >= 13 agar tidak menumpuk saat zoom jauh)
const updateAreaBadgesVisibility = () => {
    if (!map || !areaBadgeLayerGroup) return;
    if (layers.value.areas && map.getZoom() >= 13) {
        if (!map.hasLayer(areaBadgeLayerGroup)) map.addLayer(areaBadgeLayerGroup);
    } else {
        if (map.hasLayer(areaBadgeLayerGroup)) map.removeLayer(areaBadgeLayerGroup);
    }
};

// ═══ AREA GEOFENCE & BOUNDARIES ═══
const toggleAreasLayer = () => {
    if (!map || !L || !areaLayerGroup || !areaBadgeLayerGroup) return;
    areaLayerGroup.clearLayers();
    areaBadgeLayerGroup.clearLayers();

    if (!layers.value.areas) return;

    props.areas.forEach(area => {
        if (!area.latitude || !area.longitude) return;
        if (selectedAreaFilter.value !== 'all' && area.id != selectedAreaFilter.value) return;

        const radius = area.radius_meters || 1500;

        // Draw soft geofence circle
        const circle = L.circle([parseFloat(area.latitude), parseFloat(area.longitude)], {
            radius: radius,
            color: '#8b5cf6',
            fillColor: '#8b5cf6',
            fillOpacity: 0.05,
            weight: 1.5,
            dashArray: '5, 5',
        });

        circle.bindTooltip(`📍 Wilayah: <b>${area.name}</b>`, {
            sticky: true,
            className: 'area-circle-tooltip',
        });

        // Centroid Badge Marker (Dengan anti-tumpukan offset jika ada area dengan koordinat identik)
        let bLat = parseFloat(area.latitude);
        let bLng = parseFloat(area.longitude);
        const duplicateIndex = props.areas.findIndex(other => other.id !== area.id && other.latitude && Math.abs(parseFloat(other.latitude) - bLat) < 0.001 && Math.abs(parseFloat(other.longitude) - bLng) < 0.001);
        if (duplicateIndex !== -1 && area.id > props.areas[duplicateIndex].id) {
            bLat += 0.0018; // offset ~200m agar badge tidak menutupi nama area lain
            bLng += 0.0018;
        }

        const areaBadge = L.marker([bLat, bLng], {
            icon: L.divIcon({
                className: 'area-centroid-badge',
                html: `
                    <div class="px-2.5 py-0.5 bg-slate-900/85 backdrop-blur-md border border-purple-400/60 rounded-full shadow-lg text-center transform -translate-x-1/2 -translate-y-1/2 pointer-events-none whitespace-nowrap">
                        <span class="text-[10px] font-bold text-purple-200">📍 ${area.name}</span>
                    </div>
                `,
                iconSize: [100, 22],
                iconAnchor: [50, 11],
            }),
            interactive: false,
        });

        areaLayerGroup.addLayer(circle);
        areaBadgeLayerGroup.addLayer(areaBadge);
    });

    updateAreaBadgesVisibility();
};

// ═══ FIBER TOPOLOGY LINES (ODC -> ODP -> Pelanggan) ═══
const toggleFiberLines = () => {
    if (!map || !L || !fiberLinesLayerGroup) return;
    fiberLinesLayerGroup.clearLayers();

    if (!layers.value.fiberLines) return;

    // 1. Feeder Lines: ODC to ODP
    const odcCoordsMap = new Map();
    props.odcs.forEach(odc => {
        if (odc.latitude && odc.longitude) {
            odcCoordsMap.set(odc.id, [parseFloat(odc.latitude), parseFloat(odc.longitude)]);
        }
    });

    const odpCoordsMap = new Map();
    props.odps.forEach(odp => {
        if (odp.latitude && odp.longitude) {
            const odpCoords = [parseFloat(odp.latitude), parseFloat(odp.longitude)];
            odpCoordsMap.set(odp.id, odpCoords);

            if (odp.odc_id && odcCoordsMap.has(odp.odc_id)) {
                const odcCoords = odcCoordsMap.get(odp.odc_id);
                const poly = L.polyline([odcCoords, odpCoords], {
                    color: '#3b82f6',
                    weight: 2.5,
                    opacity: 0.75,
                    dashArray: '6, 6',
                });
                fiberLinesLayerGroup.addLayer(poly);
            }
        }
    });

    // 2. Drop Lines: ODP to Customer
    props.customers.forEach(cust => {
        if (cust.latitude && cust.longitude && cust.odp_id && odpCoordsMap.has(cust.odp_id)) {
            const odpCoords = odpCoordsMap.get(cust.odp_id);
            let custCoords = [parseFloat(cust.latitude), parseFloat(cust.longitude)];

            // Jika koordinat pelanggan sama dengan ODP, sesuaikan titik akhir garis dengan offset marker
            if (Math.abs(custCoords[0] - odpCoords[0]) < 0.00002 && Math.abs(custCoords[1] - odpCoords[1]) < 0.00002) {
                custCoords = [custCoords[0] + 0.00010, custCoords[1] + 0.00010];
            }

            const dropPoly = L.polyline([odpCoords, custCoords], {
                color: '#10b981',
                weight: 1.8,
                opacity: 0.65,
                dashArray: '4, 4',
            });
            fiberLinesLayerGroup.addLayer(dropPoly);
        }
    });
};

// ═══ MAP CLICK HANDLER (PICKING OR RULER) ═══
const handleMapClick = (lat, lng) => {
    // A. Handle Picking Origin for Route
    if (isPickingOrigin.value) {
        routeOrigin.value = { lat, lng, name: `Tikor (${lat.toFixed(5)}, ${lng.toFixed(5)})` };
        isPickingOrigin.value = false;
        return;
    }

    // B. Handle Picking Destination for Route
    if (isPickingDest.value) {
        routeDest.value = { lat, lng, name: `Tikor (${lat.toFixed(5)}, ${lng.toFixed(5)})` };
        isPickingDest.value = false;
        return;
    }

    // C. Handle Picking Finder Target
    if (isPickingFinderTarget.value) {
        finderTarget.value = { lat, lng, name: `Titik Survei (${lat.toFixed(5)}, ${lng.toFixed(5)})` };
        isPickingFinderTarget.value = false;
        findNearbyOdps();
        return;
    }

    // D. Handle Ruler Measurement Tool
    if (isRulerActive.value) {
        addRulerPoint(lat, lng);
    }
};

// ═══ RULER TOOL FUNCTIONS ═══
const toggleRulerTool = () => {
    isRulerActive.value = !isRulerActive.value;
    if (!isRulerActive.value) {
        clearRuler();
    }
};

const addRulerPoint = (lat, lng) => {
    rulerPoints.value.push([lat, lng]);

    // Recalculate distance
    let total = 0;
    for (let i = 1; i < rulerPoints.value.length; i++) {
        const p1 = rulerPoints.value[i - 1];
        const p2 = rulerPoints.value[i];
        total += calculateDistanceMeters(p1[0], p1[1], p2[0], p2[1]);
    }
    rulerDistanceMeters.value = total;

    // Render polyline & point markers
    rulerLayerGroup.clearLayers();

    if (rulerPoints.value.length > 1) {
        L.polyline(rulerPoints.value, {
            color: '#f59e0b',
            weight: 3,
            dashArray: '5, 5',
        }).addTo(rulerLayerGroup);
    }

    rulerPoints.value.forEach((pt, idx) => {
        L.circleMarker(pt, {
            radius: 5,
            color: '#ffffff',
            fillColor: '#f59e0b',
            fillOpacity: 1,
            weight: 2,
        }).addTo(rulerLayerGroup);
    });
};

const clearRuler = () => {
    rulerPoints.value = [];
    rulerDistanceMeters.value = 0;
    if (rulerLayerGroup) rulerLayerGroup.clearLayers();
};

// ═══ SMART ROUTING FUNCTIONS ═══
const toggleRoutingDrawer = () => {
    isRoutingActive.value = !isRoutingActive.value;
    if (isRoutingActive.value && !routeOrigin.value.lat && myLocation.value) {
        routeOrigin.value = { lat: myLocation.value.lat, lng: myLocation.value.lng, name: 'Lokasi Saya' };
    }
    nextTick(() => {
        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 320);
    });
};

const closeRoutingDrawer = () => {
    isRoutingActive.value = false;
    nextTick(() => {
        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 320);
    });
};

const setOriginToCurrentLocation = () => {
    if (myLocation.value) {
        routeOrigin.value = { lat: myLocation.value.lat, lng: myLocation.value.lng, name: 'Lokasi Saya' };
    } else {
        locateMe(true);
    }
};

const startPickingOrigin = () => {
    isPickingOrigin.value = true;
    isPickingDest.value = false;
};

const startPickingDest = () => {
    isPickingDest.value = true;
    isPickingOrigin.value = false;
};

const reverseRoute = () => {
    const temp = { ...routeOrigin.value };
    routeOrigin.value = { ...routeDest.value };
    routeDest.value = temp;
    if (activeRoute.value) {
        calculateRoute();
    }
};

const calculateRoute = async () => {
    if (!routeOrigin.value.lat || !routeDest.value.lat) return;

    isCalculatingRoute.value = true;
    try {
        const response = await axios.post('/network-map/route', {
            origin_lat: routeOrigin.value.lat,
            origin_lng: routeOrigin.value.lng,
            dest_lat: routeDest.value.lat,
            dest_lng: routeDest.value.lng,
        });

        if (response.data && (response.data.success || response.data.status === 'success' || response.data.status === 'fallback')) {
            activeRoute.value = response.data;
            renderRouteOnMap(response.data.geometry);
        } else {
            alert('Tidak dapat menemukan rute jalan ke titik tersebut.');
        }
    } catch (err) {
        console.error('Failed to calculate route:', err);
        alert('Gagal menghitung rute OSRM. Pastikan koneksi internet aktif.');
    } finally {
        isCalculatingRoute.value = false;
    }
};

const renderRouteOnMap = (geometry) => {
    if (!map || !L || !routeLayerGroup) return;
    routeLayerGroup.clearLayers();

    if (!geometry) return;

    const routePoly = L.geoJSON(geometry, {
        style: {
            color: '#4f46e5',
            weight: 5,
            opacity: 0.85,
            lineCap: 'round',
            lineJoin: 'round',
        },
    }).addTo(routeLayerGroup);

    // Start Marker
    L.circleMarker([routeOrigin.value.lat, routeOrigin.value.lng], {
        radius: 7,
        color: '#ffffff',
        fillColor: '#10b981',
        fillOpacity: 1,
        weight: 2,
    }).addTo(routeLayerGroup);

    // End Marker
    L.circleMarker([routeDest.value.lat, routeDest.value.lng], {
        radius: 7,
        color: '#ffffff',
        fillColor: '#ef4444',
        fillOpacity: 1,
        weight: 2,
    }).addTo(routeLayerGroup);

    map.fitBounds(routePoly.getBounds(), { padding: [60, 60] });
};

const clearRoute = () => {
    activeRoute.value = null;
    routeOrigin.value = { lat: null, lng: null, name: '' };
    routeDest.value = { lat: null, lng: null, name: '' };
    if (routeLayerGroup) routeLayerGroup.clearLayers();
};

const getGoogleMapsNavigationUrl = () => {
    if (!routeOrigin.value.lat || !routeDest.value.lat) return '#';
    return `https://www.google.com/maps/dir/?api=1&origin=${routeOrigin.value.lat},${routeOrigin.value.lng}&destination=${routeDest.value.lat},${routeDest.value.lng}&travelmode=driving`;
};

// ═══ SMART ODP FINDER FUNCTIONS ═══
const toggleOdpFinder = () => {
    isOdpFinderActive.value = !isOdpFinderActive.value;
};

const startPickingFinderTarget = () => {
    isPickingFinderTarget.value = true;
};

const findNearbyOdps = async () => {
    if (!finderTarget.value.lat || !finderTarget.value.lng) return;

    isLoadingNearby.value = true;
    try {
        const response = await axios.post('/network-map/nearby-odps', {
            lat: finderTarget.value.lat,
            lng: finderTarget.value.lng,
            limit: 6,
        });

        if (response.data && (response.data.success || response.data.status === 'success')) {
            nearbyOdps.value = response.data.nearby_odps || response.data.odps || [];
        }
    } catch (err) {
        console.error('Failed to get nearby ODPs:', err);
    } finally {
        isLoadingNearby.value = false;
    }
};

const createRouteToOdp = (odp) => {
    routeOrigin.value = { ...finderTarget.value };
    routeDest.value = { lat: parseFloat(odp.latitude), lng: parseFloat(odp.longitude), name: odp.name };
    isOdpFinderActive.value = false;
    isRoutingActive.value = true;
    calculateRoute();
};

// ═══ LOCATE ME (GPS) ═══
const locateMe = (flyTo = true) => {
    if (!navigator.geolocation) {
        alert('Geolocation tidak didukung oleh browser Anda.');
        return;
    }

    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            isLocating.value = false;
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            myLocation.value = { lat, lng };

            if (!userLocationMarker) {
                userLocationMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'user-gps-marker',
                        html: `
                            <div class="relative flex items-center justify-center" style="width: 28px; height: 28px;">
                                <div class="absolute w-7 h-7 rounded-full bg-blue-500/20 animate-ping"></div>
                                <div class="relative w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow-md"></div>
                            </div>
                        `,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                    }),
                    title: 'Lokasi Anda',
                }).addTo(map);
            } else {
                userLocationMarker.setLatLng([lat, lng]);
            }

            if (flyTo && map) {
                map.flyTo([lat, lng], 16, { duration: 1.2 });
            }
        },
        (err) => {
            isLocating.value = false;
            console.warn('Geolocation warning:', err.message);
        },
        { enableHighAccuracy: true, timeout: 8000 }
    );
};

// ═══ FOCUS TO COORDS & SEARCH SELECTION ═══
const focusMapToCoords = (lat, lng, zoom = 17) => {
    if (!map) return;
    map.flyTo([parseFloat(lat), parseFloat(lng)], zoom, { duration: 1.2 });
};

const selectSearchResult = (item) => {
    isSearchFocused.value = false;
    searchQuery.value = item.title;
    focusMapToCoords(item.lat, item.lng, 17);
};

// ═══ FULLSCREEN TOGGLE ═══
const toggleFullScreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
};
</script>

<style>
.gis-connection-dot { --gis-dot: #94a3b8; position: relative; display: grid; place-items: center; width: 24px; height: 24px; color: var(--gis-dot); }
.gis-connection-online { --gis-dot: #10b981; }
.gis-connection-offline { --gis-dot: #f43f5e; }
.gis-connection-dot > span { display: block; width: 13px; height: 13px; border: 2px solid #fff; border-radius: 50%; background: var(--gis-dot); box-shadow: 0 1px 5px #0007; position: relative; }
.gis-connection-online::before, .gis-connection-offline::before { content: ''; position: absolute; width: 16px; height: 16px; border: 2px solid var(--gis-dot); background: var(--gis-dot); opacity: .35; border-radius: 50%; animation: gis-connection-pulse 2s ease-out infinite; pointer-events: none; }
@keyframes gis-connection-pulse { from { transform: scale(.6); opacity: .55; } to { transform: scale(1.8); opacity: 0; } }
@media (prefers-reduced-motion: reduce) { .gis-connection-dot::before { animation: none; opacity: .2; } }
/* Leaflet Popup Styling */
.leaflet-popup-content-wrapper {
    border-radius: 1rem !important;
    padding: 4px !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
    border: 1px solid rgba(226, 232, 240, 0.8) !important;
}

.leaflet-popup-content {
    margin: 8px 10px !important;
    line-height: 1.4 !important;
}

.leaflet-container {
    font-family: inherit !important;
}

/* Custom Area Circle Tooltip */
.area-circle-tooltip {
    background: rgba(15, 23, 42, 0.88) !important;
    backdrop-filter: blur(8px) !important;
    border: 1px solid rgba(168, 85, 247, 0.5) !important;
    color: #f3e8ff !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    border-radius: 9999px !important;
    padding: 3px 10px !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3) !important;
}
.area-circle-tooltip::before {
    border-top-color: rgba(15, 23, 42, 0.88) !important;
}

/* Custom ODP Cluster */
.custom-odp-cluster {
    background: transparent !important;
    border: none !important;
}
</style>
