<template>
    <div class="apm-shell workspace-ui workspace-admin flex min-h-screen font-sans text-gray-900 dark:text-gray-100 transition-colors duration-200">
        <a href="#workspace-content" class="workspace-skip-link print:hidden">Langsung ke konten</a>
        <div v-show="mobileMenuOpen" class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden transition-opacity" @click="mobileMenuOpen = false"></div>
        <aside
            :class="[
                'apm-sidebar fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md shadow-[2px_0_24px_rgba(0,0,0,0.03)] dark:shadow-[2px_0_24px_rgba(0,0,0,0.4)] transition-all duration-300 print:hidden',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                sidebarOpen ? 'w-64' : 'w-20 lg:w-20 w-64'
            ]"
        >
            <!-- Logo & Brand Header -->
            <div class="workspace-brand flex items-center justify-between px-4 py-4 border-b border-slate-200/80 dark:border-slate-800/80 shrink-0">
                <Link href="/" class="block transition-transform hover:scale-[1.02]">
                    <AppLogo :sidebar-collapsed="!sidebarOpen" context="desktop" :white="isDarkMode" />
                </Link>
                <!-- Desktop Mini Collapse Toggle inside Header -->
                <button
                    v-if="sidebarOpen"
                    type="button"
                    @click="setSidebarOpen(false)"
                    class="hidden lg:flex p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                    title="Ciutkan Sidebar (Ctrl+B)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
                <!-- Mobile Close Button -->
                <button 
                    @click="mobileMenuOpen = false" 
                    class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
                    title="Tutup Menu"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Smart Menu Search Bar (Cerdas) -->
            <div v-if="sidebarOpen" class="px-3 pt-3 pb-2 shrink-0">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari menu..."
                        aria-label="Cari menu"
                        title="Cari menu (Ctrl+K atau /)"
                        class="w-full pl-9 pr-8 py-2 text-xs rounded-xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200/70 dark:border-slate-700/60 text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-800 transition-all shadow-sm"
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        title="Bersihkan pencarian"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <span v-else class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-[10px] font-mono text-slate-400 pointer-events-none">
                        /
                    </span>
                </div>
            </div>
            <div v-else class="px-2 pt-3 pb-2 flex justify-center shrink-0">
                <button
                    type="button"
                    @click="focusSearch"
                    class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all"
                    title="Cari Menu (Ctrl+K)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </div>

            <!-- Navigation Scroll Container -->
            <nav 
                ref="sidebarNav" 
                aria-label="Navigasi aplikasi"
                class="flex-1 overflow-y-auto overscroll-contain px-2.5 py-2 space-y-1 scrollbar-thin"
                @scroll.passive="saveScrollPosition"
            >
                <!-- SEARCH RESULTS VIEW (When User Types Search Query) -->
                <div v-if="isSearching" class="space-y-1 p-1">
                    <div class="flex items-center justify-between px-2 py-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <span>Hasil Pencarian</span>
                        <span class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 text-[10px] font-bold">{{ searchResults.length }}</span>
                    </div>

                    <div v-if="searchResults.length === 0" class="py-10 text-center text-slate-400">
                        <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <p class="text-xs font-medium">Menu tidak ditemukan</p>
                        <p class="text-[10px] text-slate-400 mt-1">Coba kata kunci lain...</p>
                    </div>

                    <template v-for="(res, sidx) in searchResults" :key="sidx">
                        <Link
                            :href="res.href"
                            @click="mobileMenuOpen = false; searchQuery = ''"
                            class="flex items-center gap-2.5 p-2 rounded-xl transition-all group"
                            :class="res.active ? 'bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 shadow-sm' : 'hover:bg-slate-100 dark:hover:bg-slate-800/80'"
                        >
                            <div :class="['w-7 h-7 rounded-lg flex items-center justify-center shrink-0 text-white text-xs font-bold shadow-sm', themeMap[res.theme]?.activeBg || 'bg-blue-600']">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[res.icon] || iconPaths['dashboard']"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold leading-tight" :class="res.active ? 'text-blue-600 dark:text-blue-400' : 'text-slate-900 dark:text-white'">
                                    {{ res.label }}
                                </p>
                                <p class="text-[10px] text-slate-400 truncate mt-0.5">
                                    {{ res.group }} <span v-if="res.parentLabel">› {{ res.parentLabel }}</span>
                                </p>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </Link>
                    </template>
                </div>

                <!-- NORMAL CATEGORY HIERARCHY (When Not Searching) -->
                <template v-else>
                    <div v-for="(grp, gIdx) in structuredGroups" :key="gIdx" class="mb-3">
                        <!-- Group Header (Accordion Toggle) -->
                        <div v-if="sidebarOpen" class="pt-2 pb-0.5">
                            <button
                                type="button"
                                @click.stop="toggleGroup(grp.label)"
                                :aria-expanded="!isGroupCollapsed(grp)"
                                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg group/grp text-left cursor-pointer select-none hover:bg-slate-100/90 dark:hover:bg-slate-800/60 active:scale-[0.99] transition-all duration-150"
                                title="Klik untuk Ciutkan / Bentangkan Kategori"
                            >
                                <div class="flex items-center gap-2 min-w-0">
                                    <span :class="['group-badge', themeMap[grp.theme]?.badge || 'group-badge--blue']">
                                        <span class="group-badge__dot"></span>
                                        {{ grp.label }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-slate-400 group-hover/grp:text-slate-600 dark:group-hover/grp:text-slate-300 transition-colors">
                                    <span class="text-[10px] font-bold opacity-60 group-hover/grp:opacity-100 transition-opacity">{{ grp.items.length }}</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform duration-200"
                                        :class="isGroupCollapsed(grp) ? '-rotate-90 text-slate-400' : 'rotate-0 text-slate-500'"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <div v-else class="my-2 border-t mx-3" :class="themeCollapsedDividers[grp.theme] || 'border-slate-200/60 dark:border-slate-800'"></div>

                        <!-- Group Items List -->
                        <div v-show="!sidebarOpen || !isGroupCollapsed(grp)" class="space-y-0.5 mt-0.5">
                            <template v-for="(item, itemIdx) in grp.items" :key="itemIdx">
                                <!-- Direct Link Item -->
                                <Link
                                    v-if="item.type === 'link'"
                                    :href="item.href"
                                    :aria-label="item.label"
                                    :aria-current="item.active($page.url) ? 'page' : undefined"
                                    @click="mobileMenuOpen = false"
                                    @mouseenter="onFlyoutEnter(item, $event)"
                                    @mouseleave="onFlyoutLeave"
                                    :class="[
                                        'flex items-center gap-2.5 rounded-xl transition-all duration-200 group relative',
                                        sidebarOpen ? 'px-3 py-2 text-xs font-semibold' : 'justify-center p-2.5 mx-auto w-10 h-10',
                                        item.active($page.url)
                                            ? (themeMap[grp.theme]?.activeBg || 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25')
                                            : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100/90 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white'
                                    ]"
                                >
                                    <!-- Icon Box with Category Theme -->
                                    <div
                                        :class="[
                                            'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-105',
                                            item.active($page.url)
                                                ? 'bg-white/20 text-white shadow-inner'
                                                : (themeMap[grp.theme]?.iconBg || 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400')
                                        ]"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[item.icon] || iconPaths['dashboard']"/>
                                        </svg>
                                    </div>

                                    <span v-if="sidebarOpen" class="truncate flex-1 tracking-tight">
                                        {{ item.label }}
                                    </span>

                                    <!-- Active Dot Indicator -->
                                    <span v-if="sidebarOpen && item.active($page.url)" class="w-1.5 h-1.5 rounded-full bg-white shrink-0 animate-pulse"></span>
                                </Link>

                                <!-- Dropdown Item -->
                                <div v-else-if="item.type === 'dropdown'" class="relative">
                                    <button
                                        type="button"
                                        @click.stop="handleToggleDropdown(item)"
                                        :aria-label="item.label"
                                        :aria-expanded="isDropdownOpen(item)"
                                        :data-active="item.active($page.url)"
                                        @mouseenter="onFlyoutEnter(item, $event)"
                                        @mouseleave="onFlyoutLeave"
                                        :class="[
                                            'flex items-center gap-2.5 rounded-xl transition-all duration-200 w-full group relative text-left cursor-pointer',
                                            sidebarOpen ? 'px-3 py-2 text-xs font-semibold' : 'justify-center p-2.5 mx-auto w-10 h-10',
                                            item.active($page.url)
                                                ? (sidebarOpen ? (themeMap[grp.theme]?.activeBg || 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25') : 'bg-blue-600 text-white shadow-md')
                                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100/90 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white'
                                        ]"
                                    >
                                        <!-- Icon Box with Category Theme -->
                                        <div
                                            :class="[
                                                'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-105',
                                                item.active($page.url)
                                                    ? 'bg-white/20 text-white shadow-inner'
                                                    : (themeMap[grp.theme]?.iconBg || 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400')
                                            ]"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[item.icon] || iconPaths['dashboard']"/>
                                            </svg>
                                        </div>

                                        <span v-if="sidebarOpen" class="truncate flex-1 tracking-tight">
                                            {{ item.label }}
                                        </span>

                                        <!-- Badge when collapsed but active child inside -->
                                        <span
                                            v-if="sidebarOpen && !isDropdownOpen(item) && item.active($page.url)"
                                            class="px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-white/25 text-white mr-1 shadow-sm"
                                        >
                                            Aktif
                                        </span>

                                        <svg
                                            v-if="sidebarOpen"
                                            :class="[
                                                'w-3.5 h-3.5 shrink-0 transition-transform duration-200',
                                                item.active($page.url) ? 'text-white' : 'text-slate-400',
                                                isDropdownOpen(item) ? 'rotate-90' : 'rotate-0'
                                            ]"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>

                                    <!-- Submenu Tree Guide (When Dropdown Open) -->
                                    <div
                                        v-show="sidebarOpen && isDropdownOpen(item)"
                                        class="mt-1 ml-6 pl-2.5 border-l-2 border-slate-200/80 dark:border-slate-800 space-y-0.5"
                                    >
                                        <template v-for="(child, childIndex) in item.children" :key="childIndex">
                                            <Link
                                                v-if="!child.permission || hasPermission(child.permission)"
                                                :href="child.href"
                                                :aria-current="child.active($page.url) ? 'page' : undefined"
                                                @click="mobileMenuOpen = false"
                                                :class="[
                                                    'flex items-center gap-2 px-2.5 py-1.5 rounded-lg text-xs transition-all duration-150 relative group/sub',
                                                    child.active($page.url)
                                                        ? (themeMap[grp.theme]?.subActive || 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 font-bold shadow-sm')
                                                        : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100/80 dark:hover:bg-slate-800/50 font-medium'
                                                ]"
                                            >
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full shrink-0 transition-all duration-200"
                                                    :class="child.active($page.url) ? (themeMap[grp.theme]?.dot || 'bg-blue-600') + ' scale-125' : 'bg-slate-300 dark:bg-slate-600 group-hover/sub:bg-slate-400'"
                                                ></span>
                                                <span class="truncate">{{ child.label }}</span>
                                            </Link>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </nav>

            <!-- Bottom User & Quick Session Dock -->
            <div v-if="sidebarOpen" class="p-3 border-t border-slate-200/80 dark:border-slate-800/80 bg-slate-50/70 dark:bg-slate-900/70 shrink-0">
                <div class="flex items-center gap-2.5 p-2 rounded-xl bg-white dark:bg-slate-800/70 border border-slate-200/70 dark:border-slate-700/60 shadow-sm">
                    <!-- User Avatar -->
                    <div class="relative shrink-0">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                            {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full" title="Online"></span>
                    </div>

                    <!-- User Meta -->
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate leading-tight">{{ $page.props.auth?.user?.name || 'Administrator' }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.2 rounded bg-blue-50 dark:bg-blue-950/70 text-blue-600 dark:text-blue-400 truncate max-w-[90px]">
                                {{ $page.props.auth?.user?.role || 'Admin' }}
                            </span>
                            <span v-if="$page.props.auth?.user?.is_on_duty" class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400">● ON</span>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex items-center gap-0.5 shrink-0">
                        <button
                            type="button"
                            @click="toggleTheme"
                            class="p-1.5 text-slate-400 hover:text-amber-500 hover:bg-slate-100 dark:hover:bg-slate-700/80 rounded-lg transition-colors cursor-pointer"
                            :title="isDarkMode ? 'Mode Terang' : 'Mode Gelap'"
                        >
                            <svg v-if="isDarkMode" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            <svg v-else class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                        </button>
                        <button
                            type="button"
                            @click="setSidebarOpen(false)"
                            class="hidden lg:flex p-1.5 text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-700/80 rounded-lg transition-colors cursor-pointer"
                            title="Ciutkan Sidebar"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Dock when Collapsed -->
            <div v-else class="p-2.5 border-t border-slate-200/80 dark:border-slate-800/80 flex flex-col items-center gap-2 shrink-0 bg-slate-50/70 dark:bg-slate-900/70">
                <div class="relative cursor-pointer" :title="$page.props.auth?.user?.name">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                        {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 bg-emerald-500 border-2 border-white dark:border-slate-900 rounded-full"></span>
                </div>
                <button
                    type="button"
                    @click="toggleTheme"
                    class="p-2 text-slate-400 hover:text-amber-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
                    :title="isDarkMode ? 'Mode Terang' : 'Mode Gelap'"
                >
                    <svg v-if="isDarkMode" class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    <svg v-else class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                </button>
                <button
                    type="button"
                    @click="setSidebarOpen(true)"
                    class="hidden lg:flex p-2 text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
                    title="Bentangkan Sidebar"
                >
                    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
            </div>
        </aside>

        <!-- Collapsed Sidebar Floating Flyout Menu (Teleported to Body for zero clipping) -->
        <Teleport to="body">
            <div
                v-if="!sidebarOpen && hoveredFlyout"
                class="workspace-flyout fixed z-[9999] w-64 bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl p-3 animate-fade-in-up"
                :style="{ top: hoveredFlyout.top + 'px', left: '84px' }"
                @mouseenter="cancelFlyoutLeave"
                @mouseleave="onFlyoutLeave"
            >
                <!-- Flyout Header -->
                <div class="flex items-center gap-2.5 pb-2.5 mb-2 border-b border-slate-100 dark:border-slate-800">
                    <div :class="['w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-white font-bold shadow-sm', themeMap[hoveredFlyout.theme || 'blue']?.activeBg || 'bg-blue-600']">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[hoveredFlyout.item.icon] || iconPaths['dashboard']"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ hoveredFlyout.item.label }}</p>
                        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400">Pintasan Menu</span>
                    </div>
                </div>

                <!-- If Direct Link -->
                <Link 
                    v-if="hoveredFlyout.item.type === 'link'"
                    :href="hoveredFlyout.item.href"
                    class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all"
                    @click="hoveredFlyout = null"
                >
                    <span>Buka Halaman</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </Link>

                <!-- If Dropdown with Submenus -->
                <div v-else-if="hoveredFlyout.item.type === 'dropdown'" class="space-y-1 max-h-72 overflow-y-auto overscroll-contain pr-1">
                    <template v-for="(child, ci) in hoveredFlyout.item.children" :key="ci">
                        <Link
                            v-if="!child.permission || hasPermission(child.permission)"
                            :href="child.href"
                            class="flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-all"
                            :class="child.active($page.url) ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/80 hover:text-slate-900 dark:hover:text-white font-medium'"
                            @click="hoveredFlyout = null"
                        >
                            <div class="flex items-center gap-2 truncate">
                                <span class="w-1.5 h-1.5 rounded-full" :class="child.active($page.url) ? 'bg-blue-600 dark:bg-blue-400' : 'bg-slate-300 dark:bg-slate-600'"></span>
                                <span class="truncate">{{ child.label }}</span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </Link>
                    </template>
                </div>
            </div>
        </Teleport>

        <!-- Main Content -->
        <main
            :class="['flex-1 transition-all duration-300 min-w-0 flex flex-col print:m-0 print:p-0 print:ml-0', sidebarOpen ? 'lg:ml-64' : 'lg:ml-20']"
        >
            <!-- Top Bar -->
            <header class="apm-topbar sticky top-0 z-30 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 sm:px-6 py-4 shadow-sm dark:shadow-gray-950/40 flex items-center justify-between gap-4 print:hidden transition-colors duration-200">
                <div class="flex items-center gap-3 min-w-0">
                    <button @click="mobileMenuOpen = true" aria-label="Buka menu utama" :aria-expanded="mobileMenuOpen" class="lg:hidden p-2 -ml-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="min-w-0 workspace-page-heading">
                        <p class="workspace-eyebrow">VIZBILL <span>/</span> Ruang kerja</p>
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white truncate">{{ title }}</h2>
                        <p v-if="subtitle" class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ subtitle }}</p>
                    </div>
                </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <!-- Corak Background Picker -->
                        <PatternPicker />

                        <!-- Theme Toggle Button (Dark / Light Mode) -->
                        <ThemeToggle />

                        <!-- On Duty Toggle -->
                        <div v-if="$page.props.auth?.user?.role !== 'customer'" class="hidden sm:flex items-center gap-2">
                            <span class="text-xs font-bold" :class="$page.props.auth?.user?.is_on_duty ? 'text-green-600' : 'text-gray-400'">
                                {{ $page.props.auth?.user?.is_on_duty ? 'ON DUTY' : 'OFF DUTY' }}
                            </span>
                            <Link href="/profile/toggle-duty" method="post" as="button" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none" :class="$page.props.auth?.user?.is_on_duty ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'" title="Toggle On Duty">
                                <span class="apm-switch-thumb inline-block h-4 w-4 transform rounded-full bg-white transition-transform" :class="$page.props.auth?.user?.is_on_duty ? 'translate-x-6' : 'translate-x-1'"></span>
                            </Link>
                        </div>
                        <!-- User Menu -->
                        <div class="flex items-center gap-3 pl-4 border-l border-gray-200 dark:border-gray-800">
                            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-sm font-bold text-blue-700 dark:text-blue-300">
                                {{ $page.props.auth?.user?.name?.charAt(0) || 'A' }}
                            </div>
                            <div v-if="sidebarOpen" class="hidden md:block">
                                <p class="text-sm font-bold text-gray-900 dark:text-gray-100 leading-tight">{{ $page.props.auth?.user?.name || 'Admin' }}</p>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 capitalize mt-0.5">{{ $page.props.auth?.user?.role || 'admin' }}</p>
                            </div>
                            <Link href="/logout" method="post" as="button" class="ml-2 p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition-colors" title="Logout">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            </Link>
                        </div>
                    </div>
            </header>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mx-6 mt-4 print:hidden">
                <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 animate-fade-in-up">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-sm">{{ $page.props.flash.success }}</span>
                </div>
            </div>

            <!-- Page Content -->
            <div id="workspace-content" tabindex="-1" :class="[fullBleed ? 'workspace-full-bleed p-0 flex-1 w-full max-w-full flex flex-col overflow-hidden' : 'workspace-content p-4 sm:p-6 lg:p-8 flex-1 w-full max-w-full overflow-hidden print:p-0 print:overflow-visible']">
                <slot />
            </div>

            <!-- Application Footer -->
            <footer v-if="!fullBleed" class="workspace-footer mt-auto py-3.5 px-4 sm:px-6 lg:px-8 border-t border-slate-200/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/70 backdrop-blur-md text-xs text-slate-500 dark:text-slate-400 print:hidden transition-colors">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
                    <p class="font-medium">
                        Copyright {{ new Date().getFullYear() }} &copy; <span class="font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">VIRUZS</span> - Smart Billing, Smarter Business.
                    </p>
                    <div class="flex items-center gap-3 text-[11px] text-slate-400 dark:text-slate-500">
                        <span>v2.0</span>
                        <span>·</span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Sistem Aktif
                        </span>
                    </div>
                </div>
            </footer>
        </main>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/Components/AppLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import PatternPicker from '@/Components/PatternPicker.vue';
import { useTheme } from '@/Composables/useTheme';
import { usePermission } from '@/Composables/usePermission';
import { useSidebarState } from '@/Composables/useSidebarState';

defineProps({
    title: { type: String, default: 'Dashboard' },
    subtitle: { type: String, default: '' },
    fullBleed: { type: Boolean, default: false },
});

const page = usePage();
const { can, hasRole } = usePermission();
// State sidebar disimpan di level modul (bertahan antar perpindahan halaman)
const {
    sidebarOpen,
    openDropdowns,
    collapsedGroups,
    scrollTop: sidebarScrollTop,
    setSidebarOpen,
    toggleDropdown,
    ensureDropdownOpen,
    toggleGroup,
    ensureGroupOpen,
    saveScrollTop,
} = useSidebarState();
const mobileMenuOpen = ref(false);
const sidebarNav = ref(null);
const searchInputRef = ref(null);
const searchQuery = ref('');

// Baca tema langsung dari <html> agar ikon tidak berkedip saat render pertama
const { isDarkMode, toggleTheme, initTheme } = useTheme();

const isGroupCollapsed = (grp) => {
    if (isSearching.value) return false;
    return collapsedGroups.value.includes(grp.label);
};

// Dropdown State Helper: label adalah kunci yang tidak berubah saat urutan menu
// berubah akibat pembaruan aplikasi atau perbedaan hak akses pengguna.
const isDropdownOpen = (item) => {
    return openDropdowns.value.includes(item.label);
};

const handleToggleDropdown = (item) => {
    toggleDropdown(item.label);
};

// Collapsed Sidebar Flyout Handling
const hoveredFlyout = ref(null);
let flyoutLeaveTimer = null;

const onFlyoutEnter = (item, event) => {
    if (sidebarOpen.value) return;
    if (flyoutLeaveTimer) clearTimeout(flyoutLeaveTimer);
    const rect = event.currentTarget.getBoundingClientRect();
    hoveredFlyout.value = {
        item,
        theme: item.theme || 'default',
        top: Math.max(12, Math.min(rect.top, window.innerHeight - 340)),
    };
};

const onFlyoutLeave = () => {
    if (flyoutLeaveTimer) clearTimeout(flyoutLeaveTimer);
    flyoutLeaveTimer = setTimeout(() => {
        hoveredFlyout.value = null;
    }, 150);
};

const cancelFlyoutLeave = () => {
    if (flyoutLeaveTimer) clearTimeout(flyoutLeaveTimer);
};

const focusSearch = () => {
    if (!sidebarOpen.value) {
        setSidebarOpen(true);
    }
    setTimeout(() => {
        searchInputRef.value?.focus();
        searchInputRef.value?.select();
    }, 60);
};

const handleGlobalKeydown = (e) => {
    const tag = e.target?.tagName?.toLowerCase();
    const isInput = tag === 'input' || tag === 'textarea' || tag === 'select' || e.target?.isContentEditable;
    if ((e.ctrlKey || e.metaKey) && e.key?.toLowerCase() === 'k') {
        e.preventDefault();
        focusSearch();
    } else if (e.key === '/' && !isInput) {
        e.preventDefault();
        focusSearch();
    } else if (e.key === 'Escape') {
        if (searchQuery.value) {
            searchQuery.value = '';
        }
        if (hoveredFlyout.value) {
            hoveredFlyout.value = null;
        }
    }
};

// Color & Visual Theme Palette Mapping
const themeMap = {
    blue: {
        badge: 'group-badge--blue',
        iconBg: 'bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 border border-sky-200/80 dark:border-sky-800/60',
        activeBg: 'bg-gradient-to-r from-sky-600 to-blue-600 text-white shadow-md shadow-sky-500/25',
        subActive: 'text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/70 font-bold',
        dot: 'bg-sky-500 shadow-sm shadow-sky-400',
    },
    green: {
        badge: 'group-badge--green',
        iconBg: 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200/80 dark:border-emerald-800/60',
        activeBg: 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/25',
        subActive: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/70 font-bold',
        dot: 'bg-emerald-500 shadow-sm shadow-emerald-400',
    },
    rose: {
        badge: 'group-badge--rose',
        iconBg: 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-800/60',
        activeBg: 'bg-gradient-to-r from-rose-600 to-red-600 text-white shadow-md shadow-rose-500/25',
        subActive: 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/70 font-bold',
        dot: 'bg-rose-500 shadow-sm shadow-rose-400',
    },
    purple: {
        badge: 'group-badge--purple',
        iconBg: 'bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200/80 dark:border-purple-800/60',
        activeBg: 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md shadow-purple-500/25',
        subActive: 'text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/70 font-bold',
        dot: 'bg-purple-500 shadow-sm shadow-purple-400',
    },
    orange: {
        badge: 'group-badge--orange',
        iconBg: 'bg-orange-50 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 border border-orange-200/80 dark:border-orange-800/60',
        activeBg: 'bg-gradient-to-r from-orange-500 to-amber-600 text-white shadow-md shadow-orange-500/25',
        subActive: 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/70 font-bold',
        dot: 'bg-orange-500 shadow-sm shadow-orange-400',
    },
    pink: {
        badge: 'group-badge--pink',
        iconBg: 'bg-pink-50 dark:bg-pink-950/60 text-pink-600 dark:text-pink-400 border border-pink-200/80 dark:border-pink-800/60',
        activeBg: 'bg-gradient-to-r from-pink-600 to-rose-600 text-white shadow-md shadow-pink-500/25',
        subActive: 'text-pink-600 dark:text-pink-400 bg-pink-50 dark:bg-pink-950/70 font-bold',
        dot: 'bg-pink-500 shadow-sm shadow-pink-400',
    },
    brown: {
        badge: 'group-badge--brown',
        iconBg: 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200/80 dark:border-amber-800/60',
        activeBg: 'bg-gradient-to-r from-amber-600 to-yellow-600 text-white shadow-md shadow-amber-500/25',
        subActive: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/70 font-bold',
        dot: 'bg-amber-500 shadow-sm shadow-amber-400',
    },
    slate: {
        badge: 'group-badge--slate',
        iconBg: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700',
        activeBg: 'bg-gradient-to-r from-slate-700 to-slate-900 text-white shadow-md shadow-slate-500/25',
        subActive: 'text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 font-bold',
        dot: 'bg-slate-500 shadow-sm shadow-slate-400',
    },
    violet: {
        badge: 'group-badge--violet',
        iconBg: 'bg-violet-50 dark:bg-violet-950/60 text-violet-600 dark:text-violet-400 border border-violet-200/80 dark:border-violet-800/60',
        activeBg: 'bg-gradient-to-r from-violet-600 to-purple-700 text-white shadow-md shadow-violet-500/25',
        subActive: 'text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-950/70 font-bold',
        dot: 'bg-violet-500 shadow-sm shadow-violet-400',
    },
    default: {
        badge: 'group-badge',
        iconBg: 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-200/80 dark:border-blue-800/60',
        activeBg: 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/25',
        subActive: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/70 font-bold',
        dot: 'bg-blue-500 shadow-sm shadow-blue-400',
    }
};

const themeDividers = {
    purple: 'bg-purple-200 dark:bg-purple-500/25',
    rose: 'bg-rose-200 dark:bg-rose-500/25',
    blue: 'bg-sky-200 dark:bg-sky-500/25',
    green: 'bg-emerald-200 dark:bg-emerald-500/25',
    orange: 'bg-orange-200 dark:bg-orange-500/25',
    pink: 'bg-pink-200 dark:bg-pink-500/25',
    brown: 'bg-brown-200 dark:bg-brown-400/25',
    slate: 'bg-slate-300 dark:bg-slate-500/25',
    violet: 'bg-violet-200 dark:bg-violet-500/25',
};

const themeCollapsedDividers = {
    purple: 'border-purple-300 dark:border-purple-500/30',
    rose: 'border-rose-300 dark:border-rose-500/30',
    blue: 'border-sky-300 dark:border-sky-500/30',
    green: 'border-emerald-300 dark:border-emerald-500/30',
    orange: 'border-orange-300 dark:border-orange-500/30',
    pink: 'border-pink-300 dark:border-pink-500/30',
    brown: 'border-brown-300 dark:border-brown-400/30',
    slate: 'border-slate-300 dark:border-slate-500/30',
    violet: 'border-violet-300 dark:border-violet-500/30',
};

const saveScrollPosition = () => {
    if (sidebarNav.value) {
        saveScrollTop(sidebarNav.value.scrollTop);
    }
};

const hasPermission = (permission) => {
    if (!permission) return true;
    return can(permission);
};

const menuItems = [
    // 1. DASHBOARD
    { type: 'link', href: '/', icon: 'dashboard', label: 'Dashboard', active: (url) => (url || '').length > 0 && url === '/', permission: 'menu_dashboard' },
    { type: 'link', href: '/dashboard/realtime', icon: 'zap', label: 'Dashboard Realtime', active: (url) => (url || '').startsWith('/dashboard/realtime'), permission: 'menu_dashboard_realtime' },
    
    // 2. PELANGGAN & LAYANAN
    { type: 'group', label: 'PELANGGAN & LAYANAN', theme: 'blue' },
    {
        type: 'dropdown',
        icon: 'users',
        label: 'Data Pelanggan',
        theme: 'blue',
        active: (url) => {
            const u = url || '';
            const nonDataPrefixes = ['/customers/booking', '/customers/survey', '/customers/installed', '/customers/activation', '/customers/canceled'];
            if (nonDataPrefixes.some(p => u.startsWith(p))) return false;
            return u === '/customers' || u.startsWith('/customers/') || u.startsWith('/customers?');
        },
        permission: ['menu_customers_all', 'menu_customers_online', 'menu_customers_gratis', 'menu_customers_free_home', 'menu_customers_terminated'],
        children: [
            {
                href: '/customers',
                label: 'Semua Pelanggan',
                active: (url) => {
                    const u = url || '';
                    const otherPrefixes = ['/customers/booking', '/customers/survey', '/customers/installed', '/customers/activation', '/customers/canceled', '/customers/gratis', '/customers/free-home', '/customers/online', '/customers/offline', '/customers/connections', '/customers/terminated', '/customers/isolir'];
                    return u === '/customers' || u.startsWith('/customers?') || (u.startsWith('/customers/') && !otherPrefixes.some(p => u.startsWith(p)));
                },
                permission: 'menu_customers_all'
            },
            { href: '/customers/online', label: 'Status Koneksi (Online / Offline)', active: (url) => (url || '').startsWith('/customers/online') || (url || '').startsWith('/customers/offline') || (url || '').startsWith('/customers/connections'), permission: 'menu_customers_online' },
            { href: '/customers/isolir', label: 'Pelanggan Isolir', active: (url) => (url || '').split('?')[0] === '/customers/isolir', permission: 'menu_customers_all' },
            { href: '/customers/gratis', label: 'Pelanggan Gratis & CSR', active: (url) => (url || '').startsWith('/customers/gratis'), permission: 'menu_customers_gratis' },
            { href: '/customers/free-home', label: 'Pelanggan Free Home', active: (url) => (url || '').startsWith('/customers/free-home'), permission: 'menu_customers_free_home' },
            { href: '/customers/terminated', label: 'Stop Permanen (Terminated)', active: (url) => (url || '').startsWith('/customers/terminated'), permission: ['menu_customers_terminated', 'menu_customers_all'] },
        ]
    },
    {
        type: 'link',
        href: '/monitoring-pelanggan',
        icon: 'calendar',
        label: 'Pantauan Pelanggan',
        theme: 'blue',
        active: (url) => (url || '').startsWith('/monitoring-pelanggan'),
        permission: 'menu_monitoring_pelanggan',
    },
    {
        type: 'dropdown',
        icon: 'clipboard-check',
        label: 'Pemasangan Baru (PSB)',
        theme: 'blue',
        active: (url) => ['/customers/booking', '/customers/survey', '/customers/installed', '/customers/activation', '/customers/canceled'].some(path => (url || '').startsWith(path)),
        permission: ['menu_customers_booking', 'menu_customers_survey', 'menu_customers_installed', 'menu_customers_activation'],
        children: [
            { href: '/customers/booking', label: '1. Booking Baru', active: (url) => (url || '').startsWith('/customers/booking'), permission: 'menu_customers_booking' },
            { href: '/customers/survey', label: '2. Survey Lokasi', active: (url) => (url || '').startsWith('/customers/survey'), permission: 'menu_customers_survey' },
            { href: '/customers/installed', label: '3. Instalasi Jaringan', active: (url) => (url || '').startsWith('/customers/installed'), permission: 'menu_customers_installed' },
            { href: '/customers/activation', label: '4. Aktivasi Layanan', active: (url) => (url || '').startsWith('/customers/activation'), permission: 'menu_customers_activation' },
            { href: '/customers/canceled', label: '5. Dibatalkan', active: (url) => (url || '').startsWith('/customers/canceled'), permission: 'menu_customers_booking' },
        ]
    },
    // 3. BILLING & KEUANGAN
    { type: 'group', label: 'BILLING & KEUANGAN', theme: 'green' },
    { type: 'link', href: '/invoices', icon: 'document-text', label: 'Invoice Pelanggan', theme: 'green', active: (url) => (url || '').length > 0 && url === '/invoices', permission: 'menu_invoices' },
    {
        type: 'dropdown',
        icon: 'cash',
        label: 'Penagihan Lapangan',
        theme: 'green',
        active: (url) => ['/customer-billing', '/customer-settlements'].some(path => (url || '').startsWith(path)),
        permission: 'menu_customer_billing',
        children: [
            { href: '/customer-billing', label: 'Terima Setoran', active: (url) => (url || '').startsWith('/customer-billing'), permission: 'menu_customer_billing' },
            { href: '/customer-settlements', label: 'Validasi Setoran', active: (url) => (url || '').startsWith('/customer-settlements'), permission: 'menu_customer_billing' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'chart-pie',
        label: 'Laporan Keuangan',
        theme: 'green',
        active: (url) => ['/financial-reports', '/customer-reports', '/tax-reports'].some(path => (url || '').startsWith(path)),
        permission: 'menu_financial_reports',
        children: [
            { href: '/financial-reports', label: 'Buku Kas Global', active: (url) => (url || '').startsWith('/financial-reports'), permission: 'menu_financial_reports' },
            { href: '/customer-reports', label: 'Laporan Pelanggan', active: (url) => (url || '').startsWith('/customer-reports'), permission: 'menu_financial_reports' },
            { href: '/tax-reports', label: 'Laporan Pajak', active: (url) => (url || '').startsWith('/tax-reports'), permission: 'menu_financial_reports' },
        ]
    },

    // 4. OPERASIONAL & GANGGUAN (TIKET)
    { type: 'group', label: 'OPERASIONAL & GANGGUAN', theme: 'rose' },
    {
        type: 'link',
        href: '/operational/daily-progress',
        icon: 'clipboard-check',
        label: 'Progress Harian Operasional',
        theme: 'rose',
        active: (url) => (url || '').startsWith('/operational/daily-progress'),
        permission: 'menu_operational_progress',
    },
    {
        type: 'link',
        href: '/monitoring-teknisi',
        icon: 'presentation-chart-line',
        label: 'Monitor Kinerja Teknisi',
        theme: 'rose',
        active: (url) => (url || '').startsWith('/monitoring-teknisi'),
        permission: 'menu_monitoring_teknisi',
    },
    {
        type: 'dropdown',
        icon: 'support',
        label: 'Komplain Pelanggan (PGK)',
        theme: 'rose',
        active: (url) => (url || '').startsWith('/pgk'),
        permission: ['menu_pgk', 'menu_pgk_jadwal', 'menu_pgk_laporan'],
        children: [
            { href: '/pgk', label: '1. Komplain Masuk', active: (url) => (url || '').length > 0 && url === '/pgk', permission: 'menu_pgk' },
            { href: '/pgk/jadwal', label: '2. Jadwal Teknisi', active: (url) => (url || '').startsWith('/pgk/jadwal'), permission: 'menu_pgk_jadwal' },
            { href: '/pgk/laporan', label: '3. Laporan Penanganan', active: (url) => (url || '').startsWith('/pgk/laporan'), permission: 'menu_pgk_laporan' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'git-branch',
        label: 'Gangguan Jalur (PGJ)',
        theme: 'rose',
        active: (url) => (url || '').startsWith('/pgj'),
        permission: ['menu_pgj', 'menu_pgj_jadwal', 'menu_pgj_laporan'],
        children: [
            { href: '/pgj', label: '1. Temuan Jalur', active: (url) => (url || '').length > 0 && url === '/pgj', permission: 'menu_pgj' },
            { href: '/pgj/jadwal', label: '2. Jadwal & Material', active: (url) => (url || '').startsWith('/pgj/jadwal'), permission: 'menu_pgj_jadwal' },
            { href: '/pgj/laporan', label: '3. Laporan Restorasi', active: (url) => (url || '').startsWith('/pgj/laporan'), permission: 'menu_pgj_laporan' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'zap',
        label: 'Penanganan Sigap (PGS)',
        theme: 'rose',
        active: (url) => (url || '').startsWith('/pgs'),
        permission: ['menu_pgs', 'menu_pgs_jadwal', 'menu_pgs_laporan'],
        children: [
            { href: '/pgs', label: '1. Temuan & Tindakan', active: (url) => (url || '').length > 0 && url === '/pgs', permission: 'menu_pgs' },
            { href: '/pgs/jadwal', label: '2. Jadwal & Material', active: (url) => (url || '').startsWith('/pgs/jadwal'), permission: 'menu_pgs_jadwal' },
            { href: '/pgs/laporan', label: '3. Laporan Penanganan', active: (url) => (url || '').startsWith('/pgs/laporan'), permission: 'menu_pgs_laporan' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'scissors',
        label: 'Cabut Perangkat (CBP)',
        theme: 'rose',
        active: (url) => (url || '').startsWith('/cbp'),
        permission: ['menu_cbp', 'menu_cbp_jadwal', 'menu_cbp_laporan'],
        children: [
            { href: '/cbp', label: '1. Request Cabut', active: (url) => (url || '').length > 0 && url === '/cbp', permission: 'menu_cbp' },
            { href: '/cbp/jadwal', label: '2. Jadwal CBP', active: (url) => (url || '').startsWith('/cbp/jadwal'), permission: 'menu_cbp_jadwal' },
            { href: '/cbp/laporan', label: '3. Laporan CBP', active: (url) => (url || '').startsWith('/cbp/laporan'), permission: 'menu_cbp_laporan' },
        ]
    },

    // 5. HOTSPOT & VOUCHER
    { type: 'group', label: 'HOTSPOT & VOUCHER', theme: 'purple' },
    { 
        type: 'dropdown',
        icon: 'ticket',
        label: 'Sistem Voucher',
        theme: 'purple',
        active: (url) => (url || '').startsWith('/vouchers'),
        permission: 'menu_vouchers',
        children: [
            { href: '/vouchers', label: 'Data Voucher', active: (url) => (url || '') === '/vouchers' || (url || '').startsWith('/vouchers?'), permission: 'menu_vouchers' },
            { href: '/vouchers/online', label: 'Monitoring Sesi Voucher', active: (url) => (url || '').startsWith('/vouchers/online') || (url || '').startsWith('/vouchers/offline'), permission: 'menu_vouchers_monitoring' },
            { href: '/vouchers/expired', label: 'Voucher Expired', active: (url) => (url || '').startsWith('/vouchers/expired'), permission: 'menu_vouchers' },
            { href: '/vouchers/profiles', label: 'Profil Voucher', active: (url) => (url || '').startsWith('/vouchers/profiles'), permission: 'menu_vouchers_profiles' },
            { href: '/vouchers/free', label: 'Voucher Gratis', active: (url) => (url || '').startsWith('/vouchers/free'), permission: 'menu_vouchers_free' },
        ]
    },

    // 6. MITRA & RESELLER
    { type: 'group', label: 'MITRA & RESELLER', theme: 'green' },
    { type: 'link', href: '/resellers', icon: 'users', label: 'Data Reseller', theme: 'green', active: (url) => (url || '').startsWith('/resellers'), permission: 'menu_resellers' },
    {
        type: 'dropdown',
        icon: 'credit-card',
        label: 'Keuangan Reseller',
        theme: 'green',
        active: (url) => ['/reseller-requests', '/reseller-billing', '/reseller-settlements', '/reseller-reports'].some(path => (url || '').startsWith(path)),
        permission: 'menu_resellers',
        children: [
            { href: '/reseller-requests', label: 'Riwayat Saldo', active: (url) => (url || '').startsWith('/reseller-requests'), permission: 'menu_resellers' },
            { href: '/reseller-billing', label: 'Penagihan Kasbon', active: (url) => (url || '').startsWith('/reseller-billing'), permission: 'menu_resellers' },
            { href: '/reseller-settlements', label: 'Pelunasan Kasbon', active: (url) => (url || '').startsWith('/reseller-settlements'), permission: 'menu_resellers' },
            { href: '/reseller-reports', label: 'Laporan Reseller', active: (url) => (url || '').startsWith('/reseller-reports'), permission: 'menu_resellers' },
        ]
    },

    // 6. MARKETING & AFILIASI
    { type: 'group', label: 'MARKETING & KOMUNIKASI', theme: 'orange' },
    {
        type: 'link',
        href: '/whatsapp-blast',
        icon: 'zap',
        label: '⚡ WhatsApp Blast & QR',
        theme: 'orange',
        active: (url) => (url || '').startsWith('/whatsapp-blast'),
        permission: 'menu_whatsapp_blast',
    },
    {
        type: 'dropdown',
        icon: 'gift',
        label: 'Marketing Afiliasi',
        theme: 'orange',
        active: (url) => (url || '').startsWith('/marketing/affiliates'),
        permission: [
            'menu_marketing_affiliates',
            'menu_affiliate_settings', 'affiliate_tab_settings',
            'menu_affiliate_diagram', 'affiliate_tab_diagram',
            'menu_affiliate_agents', 'affiliate_tab_affiliates',
            'menu_affiliate_commissions', 'affiliate_tab_commissions',
            'menu_affiliate_withdrawals', 'affiliate_tab_withdrawals',
        ],
        children: [
            { href: '/marketing/affiliates?tab=settings', label: 'Pengaturan Komisi', active: (url) => (url || '').includes('/marketing/affiliates') && ((url || '').includes('tab=settings') || (!(url || '').includes('tab=') && hasPermission(['menu_affiliate_settings', 'affiliate_tab_settings']))), permission: ['menu_affiliate_settings', 'affiliate_tab_settings'] },
            { href: '/marketing/affiliates?tab=diagram', label: 'Diagram Multi-Tier', active: (url) => (url || '').includes('/marketing/affiliates') && (url || '').includes('tab=diagram'), permission: ['menu_affiliate_diagram', 'affiliate_tab_diagram'] },
            { href: '/marketing/affiliates?tab=affiliates', label: 'Mitra & Agen Sales', active: (url) => (url || '').includes('/marketing/affiliates') && (url || '').includes('tab=affiliates'), permission: ['menu_affiliate_agents', 'affiliate_tab_affiliates'] },
            { href: '/marketing/affiliates?tab=commissions', label: 'Riwayat Komisi', active: (url) => (url || '').includes('/marketing/affiliates') && (url || '').includes('tab=commissions'), permission: ['menu_affiliate_commissions', 'affiliate_tab_commissions'] },
            { href: '/marketing/affiliates?tab=withdrawals', label: 'Pencairan Komisi', active: (url) => (url || '').includes('/marketing/affiliates') && (url || '').includes('tab=withdrawals'), permission: ['menu_affiliate_withdrawals', 'affiliate_tab_withdrawals'] },
        ]
    },
    {
        type: 'link',
        href: '/affiliate/dashboard',
        icon: 'sparkles',
        label: 'Portal Mitra Saya',
        theme: 'orange',
        active: (url) => (url || '').startsWith('/affiliate'),
        permission: ['menu_affiliate_portal', 'menu_marketing_affiliates_portal', 'affiliate_portal'],
    },

    // 7. INFRASTRUKTUR JARINGAN
    { type: 'group', label: 'INFRASTRUKTUR JARINGAN', theme: 'pink' },
    {
        type: 'dropdown',
        icon: 'globe',
        label: 'Peta & Topologi',
        theme: 'pink',
        active: (url) => ['/network-map', '/network-topology', '/network-radar', '/network-data'].some(path => (url || '').startsWith(path)),
        permission: ['menu_network_map', 'menu_network_topology', 'menu_network_radar', 'menu_network_data', 'menu_network_data_terminated', 'menu_ftth'],
        children: [
            { href: '/network-map', label: '🗺️ GIS Map Jaringan & Rute', active: (url) => (url || '').startsWith('/network-map'), permission: 'menu_network_map' },
            { href: '/network-topology', label: 'Network Topology', active: (url) => (url || '').startsWith('/network-topology'), permission: 'menu_network_topology' },
            { href: '/network-radar', label: 'Radar Tikor', active: (url) => (url || '').startsWith('/network-radar'), permission: 'menu_network_radar' },
            { href: '/network-data', label: 'Data Jaringan', active: (url) => (url || '').startsWith('/network-data') && !(url || '').startsWith('/network-data/terminated'), permission: 'menu_network_data' },
            { href: '/network-data/terminated', label: 'Cabut Perangkat', active: (url) => (url || '').startsWith('/network-data/terminated'), permission: 'menu_network_data_terminated' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'server',
        label: 'Perangkat OSP / FTTH',
        theme: 'pink',
        active: (url) => ['/olts', '/odcs', '/odps', '/onts', '/find-onu', '/infrastructure/work-orders', '/network/capacity-monitoring'].some(path => (url || '').startsWith(path)),
        permission: ['menu_network_capacity', 'menu_infrastructure_work_orders', 'menu_network_olt', 'menu_network_odc', 'menu_network_odp', 'menu_network_ont', 'menu_network_find_onu'],
        children: [
            { href: '/network/capacity-monitoring', label: '📊 Monitoring Kapasitas ODC/ODP', active: (url) => (url || '').startsWith('/network/capacity-monitoring'), permission: ['menu_network_capacity', 'menu_network_odc', 'menu_network_odp'] },
            { href: '/infrastructure/work-orders', label: '⚡ Rollout ODC & ODP', active: (url) => (url || '').startsWith('/infrastructure/work-orders'), permission: ['menu_infrastructure_work_orders', 'menu_network_odc', 'menu_network_odp'] },
            { href: '/olts', label: 'Data OLT', active: (url) => (url || '').startsWith('/olts'), permission: 'menu_network_olt' },
            { href: '/odcs', label: 'Data ODC', active: (url) => (url || '').startsWith('/odcs'), permission: 'menu_network_odc' },
            { href: '/odps', label: 'Data ODP', active: (url) => (url || '').startsWith('/odps'), permission: 'menu_network_odp' },
            { href: '/onts', label: 'Data ONT', active: (url) => (url || '').startsWith('/onts'), permission: 'menu_network_ont' },
            { href: '/find-onu', label: 'Find ONU', active: (url) => (url || '').startsWith('/find-onu'), permission: 'menu_network_find_onu' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'wifi',
        label: 'MikroTik & RADIUS',
        theme: 'pink',
        active: (url) => (url || '').startsWith('/routers') || (url || '').startsWith('/radius'),
        permission: ['menu_routers', 'menu_radius_nas', 'menu_radius_online_users', 'menu_radius_auth_logs'],
        children: [
            { href: '/routers', label: 'Data Router', active: (url) => (url || '').startsWith('/routers'), permission: 'menu_routers' },
            { href: '/radius/nas', label: 'NAS / Router', active: (url) => (url || '').startsWith('/radius/nas'), permission: 'menu_radius_nas' },
            { href: '/radius/online-users', label: 'Pengguna Online', active: (url) => (url || '').startsWith('/radius/online-users'), permission: 'menu_radius_online_users' },
            { href: '/radius/auth-logs', label: 'Log Autentikasi', active: (url) => (url || '').startsWith('/radius/auth-logs'), permission: 'menu_radius_auth_logs' },
        ]
    },

    // 7. LOGISTIK & GUDANG
    { type: 'group', label: 'LOGISTIK & GUDANG', theme: 'brown' },
    {
        type: 'dropdown',
        icon: 'archive',
        label: 'Material & Stok Gudang',
        theme: 'brown',
        active: (url) => ['/materials', '/warehouse-stocks', '/order-toko', '/produk'].some(path => (url || '').startsWith(path)),
        permission: ['menu_materials', 'menu_warehouse_stocks', 'menu_purchase_orders', 'menu_products'],
        children: [
            { href: '/materials', label: 'Data Material & Stok', active: (url) => (url || '').startsWith('/materials'), permission: 'menu_materials' },
            { href: '/warehouse-stocks', label: 'Stok Barang per Area', active: (url) => (url || '').startsWith('/warehouse-stocks'), permission: 'menu_warehouse_stocks' },
            { href: '/produk', label: 'Katalog Produk & Satuan', active: (url) => (url || '').startsWith('/produk'), permission: 'menu_products' },
            { href: '/order-toko', label: 'Order Toko (Restock)', active: (url) => (url || '').startsWith('/order-toko'), permission: 'menu_purchase_orders' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'truck',
        label: 'Sirkulasi & Lapangan',
        theme: 'brown',
        active: (url) => ['/material-transactions', '/material-handovers', '/material-mutations'].some(path => (url || '').startsWith(path)),
        permission: ['menu_material_transactions', 'menu_material_handovers', 'menu_material_mutations'],
        children: [
            { href: '/material-transactions', label: 'Surat Jalan & Pengambilan', active: (url) => (url || '').startsWith('/material-transactions'), permission: 'menu_material_transactions' },
            { href: '/material-handovers', label: 'Serah Terima & Retur', active: (url) => (url || '').startsWith('/material-handovers'), permission: 'menu_material_handovers' },
            { href: '/material-mutations', label: 'Mutasi & Kondisi Barang', active: (url) => (url || '').startsWith('/material-mutations'), permission: 'menu_material_mutations' },
        ]
    },

    // 8. KEPEGAWAIAN & HRD
    { type: 'group', label: 'KEPEGAWAIAN & HRD', theme: 'slate' },
    {
        type: 'dropdown',
        icon: 'briefcase',
        label: 'Karyawan & Shift',
        theme: 'slate',
        active: (url) => ['/employees', '/positions', '/shifts', '/work-schedules'].some(path => (url || '').startsWith(path)),
        permission: ['menu_hr_employees', 'menu_hr_positions', 'menu_shifts', 'menu_work_schedules'],
        children: [
            { href: '/employees', label: 'Data Karyawan', active: (url) => (url || '').startsWith('/employees'), permission: 'menu_hr_employees' },
            { href: '/positions', label: 'Posisi / Jabatan', active: (url) => (url || '').startsWith('/positions'), permission: 'menu_hr_positions' },
            { href: '/shifts', label: 'Shift Kerja', active: (url) => (url || '').startsWith('/shifts'), permission: 'menu_shifts' },
            { href: '/work-schedules', label: 'Jadwal Roster', active: (url) => (url || '').startsWith('/work-schedules'), permission: 'menu_work_schedules' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'badge-check',
        label: 'Sistem Presensi & Absensi',
        theme: 'slate',
        active: (url) => ['/attendances', '/absen-terbaik', '/absen-khusus', '/absen-tugas-luar', '/settings/attendance'].some(path => (url || '').startsWith(path)),
        permission: ['menu_attendances', 'attendance_tab_reguler', 'attendance_tab_best', 'attendance_tab_special', 'attendance_tab_field', 'menu_settings_attendance'],
        children: [
            { href: '/attendances', label: 'Absensi Reguler', active: (url) => (url || '').startsWith('/attendances'), permission: 'attendance_tab_reguler' },
            { href: '/absen-terbaik', label: 'Absen Terbaik', active: (url) => (url || '').startsWith('/absen-terbaik'), permission: 'attendance_tab_best' },
            { href: '/absen-khusus', label: 'Absen Tim Khusus', active: (url) => (url || '').startsWith('/absen-khusus'), permission: 'attendance_tab_special' },
            { href: '/absen-tugas-luar', label: 'Absen Tugas Luar', active: (url) => (url || '').startsWith('/absen-tugas-luar'), permission: 'attendance_tab_field' },
            { href: '/settings/attendance', label: 'Pengaturan Absensi', active: (url) => (url || '').startsWith('/settings/attendance'), permission: 'menu_settings_attendance' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'calculator',
        label: 'Penggajian & Insentif',
        theme: 'slate',
        active: (url) => ['/payroll', '/bonus-target-market', '/insentif-potongan'].some(path => (url || '').startsWith(path)),
        permission: ['menu_payroll', 'payroll_view_own', 'payroll_tab_slip', 'payroll_tab_incentives', 'menu_target_market', 'menu_incentives_deductions'],
        children: [
            { href: '/payroll', label: 'Gaji & Payroll', active: (url) => (url || '').length > 0 && url === '/payroll', permission: ['menu_payroll', 'payroll_view_own', 'payroll_tab_slip'] },
            { href: '/bonus-target-market', label: 'Bonus Target Market', active: (url) => (url || '').startsWith('/bonus-target-market'), permission: 'menu_target_market' },
            { href: '/insentif-potongan', label: 'Histori Insentif', active: (url) => (url || '').startsWith('/insentif-potongan'), permission: ['menu_incentives_deductions', 'menu_payroll', 'payroll_tab_incentives', 'payroll_view_own'] },
        ]
    },

    // 9. PENGATURAN SISTEM
    { type: 'group', label: 'PENGATURAN SISTEM', theme: 'violet' },
    {
        type: 'dropdown',
        icon: 'cog',
        label: 'Konfigurasi Sistem',
        theme: 'violet',
        active: (url) => ['/settings/branding', '/settings/billing', '/settings/payroll', '/settings/resellers', '/settings/api', '/settings/data-migration'].some(path => (url || '').startsWith(path)),
        permission: ['menu_settings_branding', 'menu_settings_billing', 'menu_settings_payroll', 'menu_settings_api', 'menu_settings_roles', 'menu_data_migration'],
        children: [
            { href: '/settings/branding', label: 'Branding & Tampilan', active: (url) => (url || '').startsWith('/settings/branding'), permission: 'menu_settings_branding' },
            { href: '/settings/billing', label: 'Billing / Invoice', active: (url) => (url || '').startsWith('/settings/billing'), permission: 'menu_settings_billing' },
            { href: '/settings/payroll', label: 'Sistem Gaji', active: (url) => (url || '').startsWith('/settings/payroll'), permission: 'menu_settings_payroll' },
            { href: '/settings/resellers', label: 'Pengaturan Reseller', active: (url) => (url || '').startsWith('/settings/resellers'), permission: 'menu_settings_roles' },
            { href: '/settings/api', label: 'API & Integrasi', active: (url) => (url || '').startsWith('/settings/api'), permission: 'menu_settings_api' },
            { href: '/settings/data-migration', label: '⚡ Data Migration Center', active: (url) => (url || '').startsWith('/settings/data-migration'), permission: 'menu_data_migration' },
        ]
    },
    {
        type: 'dropdown',
        icon: 'box',
        label: 'Master Data',
        theme: 'violet',
        active: (url) => ['/internet-packages', '/settings/areas', '/settings/payroll-categories', '/settings/master-fees', '/settings/expense-categories'].some(path => (url || '').startsWith(path)),
        permission: ['menu_internet_packages', 'menu_settings_areas', 'menu_settings_payroll', 'menu_master_fees', 'menu_payroll_categories', 'menu_settings_roles'],
        children: [
            { href: '/internet-packages', label: 'Paket Langganan & Tarif', active: (url) => (url || '').startsWith('/internet-packages'), permission: 'menu_internet_packages' },
            { href: '/settings/areas', label: 'Master Area', active: (url) => (url || '').startsWith('/settings/areas'), permission: 'menu_settings_areas' },
            { href: '/settings/expense-categories', label: 'Kategori Pengeluaran', active: (url) => (url || '').startsWith('/settings/expense-categories'), permission: 'menu_settings_areas' },
            { href: '/settings/master-fees', label: 'Master Fee Teknisi/Sales', active: (url) => (url || '').startsWith('/settings/master-fees'), permission: ['menu_master_fees', 'menu_settings_payroll'] },
            { href: '/settings/payroll-categories', label: 'Kategori Insentif/Potongan', active: (url) => (url || '').startsWith('/settings/payroll-categories'), permission: ['menu_payroll_categories', 'menu_settings_payroll'] },
        ]
    },
    {
        type: 'dropdown',
        icon: 'lock-closed',
        label: 'Hak Akses Pengguna',
        theme: 'violet',
        active: (url) => ['/users', '/settings/roles'].some(path => (url || '').startsWith(path)),
        permission: ['menu_users', 'menu_settings_roles'],
        children: [
            { href: '/users', label: 'Manajemen User', active: (url) => (url || '').startsWith('/users'), permission: 'menu_users' },
            { href: '/settings/roles', label: 'Role & Akses', active: (url) => (url || '').startsWith('/settings/roles'), permission: 'menu_settings_roles' },
        ]
    }
];

const filteredMenuItems = computed(() => {
    try {
        const filtered = [];
        let currentGroup = null;

        menuItems.forEach(item => {
            if (item.type === 'group') {
                currentGroup = item;
            } else {
                let isAllowed = false;
                if (item.type === 'dropdown' && item.children) {
                    const hasChildAccess = item.children.some(c => !c.permission || hasPermission(c.permission));
                    const hasParentAccess = !item.permission || hasPermission(item.permission);
                    isAllowed = hasParentAccess && hasChildAccess;
                } else {
                    isAllowed = !item.permission || hasPermission(item.permission);
                }

                if (isAllowed) {
                    if (currentGroup) {
                        filtered.push(currentGroup);
                        currentGroup = null;
                    }
                    filtered.push(item);
                }
            }
        });

        return filtered;
    } catch (e) {
        console.error("Error in filteredMenuItems:", e);
        return menuItems;
    }
});

const structuredGroups = computed(() => {
    const groups = [];
    let currentGroup = { label: 'MENU UTAMA', theme: 'default', items: [] };

    filteredMenuItems.value.forEach((item, idx) => {
        if (item.type === 'group') {
            if (currentGroup.items.length > 0) {
                groups.push(currentGroup);
            }
            currentGroup = {
                label: item.label,
                theme: item.theme || 'default',
                items: []
            };
        } else {
            currentGroup.items.push({ ...item, _index: idx });
        }
    });

    if (currentGroup.items.length > 0) {
        groups.push(currentGroup);
    }

    return groups;
});

const isSearching = computed(() => searchQuery.value.trim().length > 0);

const searchResults = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return [];

    const results = [];

    structuredGroups.value.forEach(group => {
        group.items.forEach(item => {
            if (item.type === 'link') {
                if (item.label.toLowerCase().includes(q) || group.label.toLowerCase().includes(q)) {
                    results.push({
                        label: item.label,
                        href: item.href,
                        group: group.label,
                        theme: group.theme,
                        icon: item.icon,
                        active: item.active(page.url),
                    });
                }
            } else if (item.type === 'dropdown') {
                const parentMatches = item.label.toLowerCase().includes(q);
                if (Array.isArray(item.children)) {
                    item.children.forEach(child => {
                        if (!child.permission || hasPermission(child.permission)) {
                            if (parentMatches || child.label.toLowerCase().includes(q) || group.label.toLowerCase().includes(q)) {
                                results.push({
                                    label: child.label,
                                    parentLabel: item.label,
                                    href: child.href,
                                    group: group.label,
                                    theme: group.theme,
                                    icon: item.icon,
                                    active: child.active(page.url),
                                });
                            }
                        }
                    });
                }
            }
        });
    });

    return results;
});

// Buka kategori group dan dropdown yang memiliki menu aktif SEBELUM render pertama,
// sehingga tinggi sidebar sudah final saat posisi scroll dipulihkan.
structuredGroups.value.forEach((grp) => {
    const hasActiveItem = grp.items.some(item => {
        if (item.type === 'link') return item.active(page.url);
        if (item.type === 'dropdown') {
            if (item.active(page.url)) return true;
            if (item.children) {
                return item.children.some(c => c.active(page.url));
            }
        }
        return false;
    });
    if (hasActiveItem) {
        ensureGroupOpen(grp.label);
    }
});

filteredMenuItems.value.forEach((item) => {
    if (item.type === 'dropdown' && item.active(page.url)) {
        ensureDropdownOpen(item.label);
    }
});

const restoreSidebarScroll = () => {
    const nav = sidebarNav.value;
    if (!nav) return;
    const target = sidebarScrollTop.value || 0;
    if (nav.scrollTop !== target) {
        nav.scrollTop = target;
    }
};

onMounted(() => {
    // onMounted berjalan sinkron setelah DOM dibuat namun SEBELUM browser melukis frame,
    // jadi pemulihan scroll di sini tidak menimbulkan lompatan yang terlihat.
    restoreSidebarScroll();

    // Pastikan tema tersinkron (mis. jika diubah di tab lain)
    initTheme();

    // Listener pintasan keyboard global (Ctrl+K dan /)
    if (typeof window !== 'undefined') {
        window.addEventListener('keydown', handleGlobalKeydown);
    }
});

onBeforeUnmount(() => {
    // Simpan posisi terakhir tepat sebelum layout diganti halaman berikutnya
    saveScrollPosition();

    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleGlobalKeydown);
    }
});

const iconPaths = {
    search: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
    'x-circle': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    globe: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'document-add': 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'clipboard-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    cog: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    key: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
    'badge-check': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    server: 'M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
    box: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'git-branch': 'M6 3v12M18 9a3 3 0 01-3 3H9m-3 0a3 3 0 003 3h0a3 3 0 003-3m0 0V3',
    wifi: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0',
    'file-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'document-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'credit-card': 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    ticket: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
    'alert-circle': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    settings: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    package: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    zap: 'M13 10V3L4 14h7v7l9-11h-7z',
    signal: 'M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z',
    map: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
    'color-swatch': 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
    archive: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    'shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    briefcase: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    code: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
    'lock-closed': 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    'submenu-dot': 'M9 5l7 7-7 7',
    calculator: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    support: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
    cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    'chart-pie': 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
    scissors: 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879a3 3 0 11-1.414-1.414L10.586 12 7.707 9.121a3 3 0 111.414-1.414L12 10.586m0 0L19 12',
    gift: 'M12 8v13m0-13V4.5a2.5 2.5 0 115 0v3.5m-5 0V4.5a2.5 2.5 0 10-5 0v3.5m-5 0h20a1 1 0 011 1v3a1 1 0 01-1 1H2a1 1 0 01-1-1V9a1 1 0 011-1zm1 5v7a2 2 0 002 2h14a2 2 0 002-2v-7',
    sparkles: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.286L13 21l-2.286-6.857L5 12l5.714-2.286L13 3z',
    'presentation-chart-line': 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
    'desktop-computer': 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    truck: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2',
    home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
};
</script>
