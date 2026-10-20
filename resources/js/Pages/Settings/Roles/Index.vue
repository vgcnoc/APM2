<template>
    <AppLayout title="Manajemen Role & Hak Akses" subtitle="Atur izin akses Menu, Card, Tab, Filter, dan Tombol secara detail">
        <div class="flex flex-col lg:flex-row gap-6 items-start pb-20">
            
            <!-- ── 1. SIDEBAR: DAFTAR ROLE ──────────────────────────── -->
            <div class="w-full lg:w-80 shrink-0 space-y-4 lg:sticky lg:top-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Header Sidebar -->
                    <div class="flex items-center justify-between p-4 bg-gray-50/70 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm">Daftar Role</h3>
                                <p class="text-[11px] text-gray-500">{{ roles.length }} role terdaftar</p>
                            </div>
                        </div>
                        <button @click="openCreateModal" 
                            class="text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold px-3 py-1.5 rounded-lg transition-all shadow-sm flex items-center gap-1 active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah
                        </button>
                    </div>

                    <!-- Role Search Input -->
                    <div class="p-3 border-b border-gray-100 bg-white">
                        <div class="relative">
                            <input v-model="roleSearch" type="text" placeholder="Cari nama role..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border-gray-200 bg-gray-50/50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder-gray-400" />
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Role Items List -->
                    <div class="p-2 space-y-1.5 max-h-[calc(100vh-280px)] overflow-y-auto">
                        <div v-for="role in filteredRoles" :key="role.id"
                            @click="selectRole(role)"
                            :class="[
                                'p-3 rounded-xl cursor-pointer transition-all border duration-150 group relative',
                                selectedRole?.id === role.id
                                    ? 'bg-blue-50/80 border-blue-400 shadow-sm ring-2 ring-blue-100'
                                    : 'bg-white border-gray-100 hover:bg-gray-50/80 hover:border-gray-200'
                            ]">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div :class="[
                                        'w-9 h-9 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold transition-colors shadow-sm',
                                        role.name === 'admin' ? 'bg-amber-500 text-white' :
                                        selectedRole?.id === role.id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'
                                    ]">
                                        {{ role.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <h4 class="font-bold text-gray-900 text-sm capitalize truncate">{{ role.name }}</h4>
                                            <span v-if="role.name === 'admin'" class="inline-flex items-center text-[9px] font-bold uppercase tracking-wider text-amber-700 bg-amber-100/70 px-1.5 py-0.2 rounded border border-amber-200">
                                                Super Admin
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            <span class="font-medium text-gray-700">{{ role.name === 'admin' ? totalCount : role.permissions.length }}</span> dari {{ totalCount }} akses · 
                                            <span class="text-blue-600 font-medium">{{ role.users_count }} user</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                    <button @click.stop="openCloneModal(role)" class="p-1 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-md transition-colors" title="Duplikasi Role (Clone)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                    </button>
                                    <template v-if="role.name !== 'admin'">
                                        <button @click.stop="openEditModal(role)" class="p-1 text-gray-400 hover:text-blue-600 hover:bg-blue-100/60 rounded-md transition-colors" title="Ubah Nama">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button @click.stop="confirmDeleteRole(role)" class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-100/60 rounded-md transition-colors" title="Hapus Role">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Mini Progress Bar on Role Card -->
                            <div class="mt-2.5 w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div :class="[
                                    'h-1.5 rounded-full transition-all duration-300',
                                    role.name === 'admin' ? 'bg-amber-500 w-full' :
                                    selectedRole?.id === role.id ? 'bg-blue-600' : 'bg-gray-400'
                                ]" :style="{ width: role.name === 'admin' ? '100%' : (role.permissions.length / totalCount * 100) + '%' }"></div>
                            </div>
                        </div>

                        <!-- Empty search in sidebar -->
                        <div v-if="filteredRoles.length === 0" class="p-6 text-center text-xs text-gray-400">
                            Role tidak ditemukan
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── 2. MAIN: DETAIL & PENGATURAN HAK AKSES ───────────── -->
            <div class="flex-1 min-w-0 w-full space-y-5">
                <div v-if="selectedRole" class="space-y-4">
                    
                    <!-- ── A. HEADER CARD ──────────────────────────── -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 lg:p-6 transition-all">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div :class="[
                                    'w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg shadow-sm',
                                    selectedRole.name === 'admin' ? 'bg-amber-500 text-white' : 'bg-blue-600 text-white'
                                ]">
                                    {{ selectedRole.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-xl font-extrabold text-gray-900 capitalize">{{ selectedRole.name }}</h3>
                                        <span v-if="selectedRole.name === 'admin'" class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-amber-700 bg-amber-100 px-2 py-0.5 rounded-md border border-amber-200">
                                            Super Admin (Akses Penuh)
                                        </span>
                                        <span v-else-if="hasUnsavedChanges" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full animate-pulse">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Ada perubahan belum disimpan
                                        </span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                            Aktif: <b class="ml-1 font-bold">{{ checkedCount }}</b> / {{ totalCount }} izin
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            Persentase: <b class="ml-1 font-bold">{{ Math.round((checkedCount / totalCount) * 100) }}%</b>
                                        </span>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            Ditugaskan ke: <b class="ml-1 font-bold">{{ selectedRole.users_count }} user</b>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                <button @click="openCloneModal(selectedRole)"
                                    class="px-3 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-all flex items-center gap-1.5 shadow-sm active:scale-95">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                    Duplikasi Role
                                </button>

                                <template v-if="selectedRole.name !== 'admin'">
                                    <button @click="openPresetModal"
                                        type="button"
                                        class="px-3 py-2 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition-all flex items-center gap-1.5 shadow-sm active:scale-95"
                                        title="Pilih dan terapkan template hak akses standar untuk role ini">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        Template Standar
                                    </button>

                                    <button @click="toggleAll" 
                                        class="px-3 py-2 text-xs font-bold rounded-xl border transition-all active:scale-95 flex items-center gap-1.5"
                                        :class="isAllChecked 
                                            ? 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200' 
                                            : 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100'">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ isAllChecked ? 'Nonaktifkan Semua' : 'Aktifkan Semua' }}
                                    </button>

                                    <button @click="resetChanges" v-if="hasUnsavedChanges"
                                        class="px-3 py-2 text-xs font-bold text-gray-600 hover:text-gray-900 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-all">
                                        Reset
                                    </button>

                                    <button @click="savePermissions" :disabled="isSaving || !hasUnsavedChanges"
                                        :class="[
                                            'px-4 py-2 font-bold text-xs rounded-xl transition-all shadow-sm flex items-center gap-2 active:scale-95',
                                            hasUnsavedChanges 
                                                ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-500/20' 
                                                : 'bg-gray-100 text-gray-400 cursor-not-allowed border border-gray-200'
                                        ]">
                                        <svg v-if="isSaving" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                                        <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- ── GRANULAR TYPE BREAKDOWN COUNTERS ────────── -->
                        <div class="mt-4 pt-3 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2 text-center">
                            <div @click="typeFilter = typeFilter === 'menu' ? 'all' : 'menu'" class="p-2 rounded-xl border cursor-pointer transition-all"
                                :class="typeFilter === 'menu' ? 'bg-indigo-50 border-indigo-300 ring-2 ring-indigo-200' : 'bg-gray-50/70 border-gray-100 hover:bg-gray-100/60'">
                                <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">📑 Menu</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ typeCounts.menu.checked }} / {{ typeCounts.menu.total }}</span>
                            </div>
                            <div @click="typeFilter = typeFilter === 'card' ? 'all' : 'card'" class="p-2 rounded-xl border cursor-pointer transition-all"
                                :class="typeFilter === 'card' ? 'bg-emerald-50 border-emerald-300 ring-2 ring-emerald-200' : 'bg-gray-50/70 border-gray-100 hover:bg-gray-100/60'">
                                <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">📊 Card</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ typeCounts.card.checked }} / {{ typeCounts.card.total }}</span>
                            </div>
                            <div @click="typeFilter = typeFilter === 'tab' ? 'all' : 'tab'" class="p-2 rounded-xl border cursor-pointer transition-all"
                                :class="typeFilter === 'tab' ? 'bg-violet-50 border-violet-300 ring-2 ring-violet-200' : 'bg-gray-50/70 border-gray-100 hover:bg-gray-100/60'">
                                <span class="text-[10px] font-bold text-violet-700 uppercase tracking-wider block">📑 Tabs</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ typeCounts.tab.checked }} / {{ typeCounts.tab.total }}</span>
                            </div>
                            <div @click="typeFilter = typeFilter === 'filter' ? 'all' : 'filter'" class="p-2 rounded-xl border cursor-pointer transition-all"
                                :class="typeFilter === 'filter' ? 'bg-sky-50 border-sky-300 ring-2 ring-sky-200' : 'bg-gray-50/70 border-gray-100 hover:bg-gray-100/60'">
                                <span class="text-[10px] font-bold text-sky-700 uppercase tracking-wider block">🔍 Filter</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ typeCounts.filter.checked }} / {{ typeCounts.filter.total }}</span>
                            </div>
                            <div @click="typeFilter = typeFilter === 'button' ? 'all' : 'button'" class="p-2 rounded-xl border cursor-pointer transition-all"
                                :class="typeFilter === 'button' ? 'bg-amber-50 border-amber-300 ring-2 ring-amber-200' : 'bg-gray-50/70 border-gray-100 hover:bg-gray-100/60'">
                                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">⚡ Tombol</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ typeCounts.button.checked }} / {{ typeCounts.button.total }}</span>
                            </div>
                            <div @click="typeFilter = typeFilter === 'field' ? 'all' : 'field'" class="p-2 rounded-xl border cursor-pointer transition-all"
                                :class="typeFilter === 'field' ? 'bg-teal-50 border-teal-300 ring-2 ring-teal-200' : 'bg-gray-50/70 border-gray-100 hover:bg-gray-100/60'">
                                <span class="text-[10px] font-bold text-teal-700 uppercase tracking-wider block">📝 Field</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ typeCounts.field?.checked || 0 }} / {{ typeCounts.field?.total || 0 }}</span>
                            </div>
                            <div @click="typeFilter = typeFilter === 'feature' ? 'all' : 'feature'" class="p-2 rounded-xl border cursor-pointer transition-all"
                                :class="typeFilter === 'feature' ? 'bg-rose-50 border-rose-300 ring-2 ring-rose-200' : 'bg-gray-50/70 border-gray-100 hover:bg-gray-100/60'">
                                <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">🌐 Scope</span>
                                <span class="text-sm font-extrabold text-gray-900">{{ typeCounts.feature.checked }} / {{ typeCounts.feature.total }}</span>
                            </div>
                        </div>

                        <!-- Progress Bar Visual -->
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                                <span>Cakupan Izin Role</span>
                                <span class="font-bold text-gray-800">{{ checkedCount }} / {{ totalCount }} Hak Akses Terpasang</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                <div :class="[
                                    'h-2.5 rounded-full transition-all duration-500 ease-out',
                                    selectedRole.name === 'admin' ? 'bg-amber-500' : 'bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600'
                                ]" :style="{ width: (checkedCount / totalCount * 100) + '%' }"></div>
                            </div>
                        </div>

                        <!-- Super Admin Alert -->
                        <div v-if="selectedRole.name === 'admin'" class="mt-4 flex items-start gap-3 bg-amber-50 text-amber-900 p-3.5 rounded-xl border border-amber-200 text-xs">
                            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <div>
                                <p class="font-bold text-amber-900">Hak Akses Super Admin Terkunci Otomatis (Full Access)</p>
                                <p class="text-amber-800/90 mt-0.5">Role <b>Admin</b> secara permanen memiliki akses bypass ke seluruh modul, controller, menu, dan API demi keamanan sistem.</p>
                            </div>
                        </div>
                    </div>

                    <!-- ── B. SEARCH & FILTER TOOLBAR ──────────────── -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-3.5">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                            <!-- Live Search Input -->
                            <div class="relative flex-1">
                                <input v-model="searchQuery" type="text"
                                    placeholder="Cari izin berdasarkan nama, tipe, atau kode (misal: invoice, tambah, odp, hapus, card)..."
                                    class="w-full pl-9 pr-9 py-2 text-xs md:text-sm rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder-gray-400" />
                                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5 md:top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-2.5 md:top-3 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <!-- Filter & Tool Actions -->
                            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                <!-- Type Filter Pill -->
                                <select v-model="typeFilter" class="text-xs rounded-xl border-gray-200 py-2 pl-3 pr-8 bg-gray-50 font-medium focus:ring-blue-500">
                                    <option value="all">Semua Tipe Izin</option>
                                    <option value="menu">📑 Menu Navigasi</option>
                                    <option value="card">📊 Card & Statistik</option>
                                    <option value="tab">📑 Tab Halaman</option>
                                    <option value="filter">🔍 Filter & Cari</option>
                                    <option value="button">⚡ Tombol Aksi</option>
                                    <option value="field">📝 Field / Kolom</option>
                                    <option value="feature">🌐 Fitur / Scope</option>
                                </select>

                                <!-- Status Filter -->
                                <select v-model="statusFilter" class="text-xs rounded-xl border-gray-200 py-2 pl-3 pr-8 bg-gray-50 font-medium focus:ring-blue-500">
                                    <option value="all">Semua Status</option>
                                    <option value="active">Hanya Aktif</option>
                                    <option value="inactive">Hanya Nonaktif</option>
                                </select>

                                <!-- Expand / Collapse All -->
                                <button @click="expandAll" class="px-2.5 py-2 text-xs font-medium text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors" title="Buka Semua Modul">
                                    Buka Semua
                                </button>
                                <button @click="collapseAll" class="px-2.5 py-2 text-xs font-medium text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors" title="Tutup Semua Modul">
                                    Tutup Semua
                                </button>
                            </div>
                        </div>

                        <!-- Category Tabs (Horizontal Scrollable) -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar border-t border-gray-100 pt-3 text-xs">
                            <button v-for="tab in categoryTabs" :key="tab.id"
                                @click="selectedCategory = tab.id"
                                :class="[
                                    'px-3 py-1.5 rounded-lg font-bold whitespace-nowrap transition-all flex items-center gap-1.5 shrink-0',
                                    selectedCategory === tab.id
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                ]">
                                <span>{{ tab.label }}</span>
                                <span :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px]',
                                    selectedCategory === tab.id ? 'bg-white/25 text-white' : 'bg-gray-200 text-gray-700'
                                ]">
                                    {{ tab.count }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- ── C. PERMISSION GROUPS & GRID ─────────────── -->
                    <div class="space-y-4" :class="{'opacity-75 pointer-events-none': selectedRole.name === 'admin'}">
                        
                        <!-- Empty Search Results -->
                        <div v-if="filteredGroups.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h4 class="font-bold text-gray-800 text-sm">Tidak ada hak akses yang cocok</h4>
                            <p class="text-xs text-gray-500 mt-1">Coba sesuaikan kata kunci pencarian atau ubah filter status/tipe.</p>
                            <button @click="resetFilters" class="mt-3 px-3.5 py-1.5 bg-blue-50 text-blue-600 font-bold text-xs rounded-lg hover:bg-blue-100 transition-colors">
                                Reset Filter
                            </button>
                        </div>

                        <!-- Group Card Loop -->
                        <div v-for="group in filteredGroups" :key="group.group"
                            class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-200 hover:border-gray-200">
                            
                            <!-- Group Card Header -->
                            <div class="w-full flex items-center justify-between px-5 py-3.5 bg-white hover:bg-gray-50/80 transition-colors cursor-pointer select-none"
                                @click="toggleGroup(group.group)">
                                <div class="flex items-center gap-3 min-w-0">
                                    <!-- Icon -->
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                                        :class="getGroupColor(group.icon)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPaths[group.icon] || iconPaths['cog']"/>
                                        </svg>
                                    </div>
                                    <!-- Title & Stats -->
                                    <div class="min-w-0 text-left">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="font-bold text-gray-900 text-sm truncate">{{ group.group }}</h4>
                                            <span :class="[
                                                'text-[10px] font-bold px-2 py-0.5 rounded-full border',
                                                getGroupCheckedCount(group) === group.permissions.length 
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                    : getGroupCheckedCount(group) > 0 
                                                        ? 'bg-blue-50 text-blue-700 border-blue-200' 
                                                        : 'bg-gray-100 text-gray-500 border-gray-200'
                                            ]">
                                                {{ getGroupCheckedCount(group) }} / {{ group.permissions.length }} Aktif
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-0.5">
                                            Klik untuk {{ isGroupOpen(group.group) ? 'menutup rincian' : 'melihat rincian izin' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Right Group Actions -->
                                <div class="flex items-center gap-2 shrink-0 flex-wrap" @click.stop>
                                    <!-- Quick Toggle Group -->
                                    <button v-if="selectedRole.name !== 'admin'" 
                                        @click="toggleGroupAll(group)" 
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg border transition-all active:scale-95"
                                        :class="isGroupAllChecked(group)
                                            ? 'bg-gray-100 hover:bg-gray-200 text-gray-600 border-gray-200'
                                            : 'bg-blue-50 hover:bg-blue-100 text-blue-600 border-blue-200'">
                                        {{ isGroupAllChecked(group) ? 'Batal Semua' : 'Pilih Semua' }}
                                    </button>

                                    <!-- Chevron -->
                                    <button @click="toggleGroup(group.group)" class="p-1 text-gray-400 hover:text-gray-600 rounded-lg">
                                        <svg :class="['w-5 h-5 transition-transform duration-200', isGroupOpen(group.group) ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Group Card Body: 2-Column Responsive Grid -->
                            <div v-show="isGroupOpen(group.group)" class="border-t border-gray-100 p-3.5 bg-gray-50/40">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                                    <div v-for="perm in group.permissions" :key="perm.name"
                                        @click="selectedRole.name !== 'admin' && togglePermissionWithAutoParent(perm, group)"
                                        :class="[
                                            'p-3 rounded-xl border transition-all cursor-pointer flex items-center justify-between gap-3 select-none',
                                            isPermActive(perm.name)
                                                ? 'bg-blue-50/70 border-blue-300 ring-1 ring-blue-100 shadow-sm'
                                                : 'bg-white border-gray-200/80 hover:border-gray-300 hover:bg-white/80'
                                        ]">
                                        
                                        <!-- Left Info -->
                                        <div class="flex items-start gap-2.5 min-w-0">
                                            <!-- Type Badge -->
                                            <span :class="[
                                                'shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase tracking-wide border mt-0.5',
                                                getTypeBadgeClass(perm.type)
                                            ]">
                                                {{ getTypeLabel(perm.type) }}
                                            </span>

                                            <!-- Labels -->
                                            <div class="min-w-0">
                                                <p :class="[
                                                    'text-xs font-bold leading-snug truncate',
                                                    isPermActive(perm.name) ? 'text-blue-900 font-extrabold' : 'text-gray-800'
                                                ]">
                                                    {{ perm.label }}
                                                </p>
                                                <span class="inline-block text-[10px] text-gray-400 font-mono tracking-tight bg-gray-100/70 px-1 py-0.2 rounded mt-0.5 max-w-[200px] truncate" :title="perm.name">
                                                    {{ perm.name }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Right: Toggle Switch -->
                                        <div class="shrink-0" @click.stop>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" 
                                                    :checked="isPermActive(perm.name)"
                                                    @change="togglePermissionWithAutoParent(perm, group)" 
                                                    class="sr-only peer" 
                                                    :disabled="selectedRole?.name === 'admin'">
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ── EMPTY STATE KETIKA BELUM ADA ROLE DIPILIH ──── -->
                <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
                    <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg mb-1">Pilih Role Pengguna</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto">Silakan klik salah satu role di menu sebelah kiri untuk melihat daftar hak akses dan mengaturnya.</p>
                </div>
            </div>
        </div>

        <!-- ── FLOATING SAVE BAR JIKA ADA PERUBAHAN ─────────────── -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform translate-y-10 opacity-0" enter-to-class="transform translate-y-0 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="transform translate-y-0 opacity-100" leave-to-class="transform translate-y-10 opacity-0">
            <div v-if="hasUnsavedChanges && selectedRole?.name !== 'admin'"
                class="fixed bottom-6 inset-x-0 z-40 flex justify-center px-4 pointer-events-none">
                <div class="bg-gray-900/95 backdrop-blur-md text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-gray-700/50 flex items-center justify-between gap-4 w-full max-w-xl pointer-events-auto animate-bounce-subtle">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                        <p class="text-xs font-medium truncate">
                            Ada perubahan izin pada role <b class="text-blue-400 capitalize">{{ selectedRole.name }}</b> yang belum disimpan!
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="resetChanges" class="px-3 py-1.5 text-xs text-gray-300 hover:text-white rounded-lg hover:bg-gray-800 transition-colors">
                            Batal
                        </button>
                        <button @click="savePermissions" :disabled="isSaving" 
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-lg transition-all shadow-md flex items-center gap-1.5">
                            <svg v-if="isSaving" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            {{ isSaving ? 'Menyimpan...' : 'Simpan Sekarang' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── MODAL TAMBAH / EDIT NAMA ROLE ────────────────────── -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-up">
                    <form @submit.prevent="submitForm">
                        <div class="px-6 pt-6 pb-5">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">{{ isEditing ? 'Edit Nama Role' : 'Tambah Role Baru' }}</h3>
                                    <p class="text-xs text-gray-500">Pilih posisi/jabatan atau ketik nama role baru</p>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">Nama Role</label>
                                    <button type="button" @click="isCustomRoleName = !isCustomRoleName" 
                                        class="text-[11px] font-bold text-blue-600 hover:text-blue-700 underline flex items-center gap-1">
                                        {{ isCustomRoleName ? '← Pilih dari Jabatan' : '+ Ketik Nama Kustom' }}
                                    </button>
                                </div>

                                <!-- Mode 1: Select dari Jabatan -->
                                <select v-if="!isCustomRoleName" v-model="formName" required
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="" disabled>-- Pilih Jabatan / Posisi --</option>
                                    <option v-for="pos in positions" :key="pos.id" :value="pos.name.toLowerCase()">
                                        {{ pos.name }}
                                    </option>
                                    <option v-if="isEditing && formName && !positions.find(p => p.name.toLowerCase() === formName)" :value="formName">
                                        {{ formName }} (Lama)
                                    </option>
                                </select>

                                <!-- Mode 2: Input Text Kustom -->
                                <input v-else v-model="formName" type="text" required placeholder="Contoh: koordinator-teknisi, sales-vip, helper-gudang"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />

                                <p class="mt-2 text-[11px] text-gray-500">
                                    {{ isCustomRoleName 
                                        ? 'Ketik nama role baru sesuai kebutuhan organisasi. Format otomatis huruf kecil.' 
                                        : 'Nama role dihubungkan dengan data Jabatan operasional. Izin dapat diatur segera setelah role dibuat.' }}
                                </p>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Batal</button>
                            <button type="submit" :disabled="isSubmitting" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all shadow-sm disabled:opacity-50">
                                {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL KLONING / DUPLIKASI ROLE ───────────────────── -->
        <Teleport to="body">
            <div v-if="showCloneModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showCloneModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-up">
                    <form @submit.prevent="submitCloneForm">
                        <div class="px-6 pt-6 pb-5">
                            <div class="flex items-center gap-3 mb-5">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">Duplikasi Role Akses</h3>
                                    <p class="text-xs text-gray-500">Salin seluruh izin dari role <b class="capitalize text-gray-800">{{ roleToClone?.name }}</b></p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nama Role Baru</label>
                                <input v-model="cloneFormName" required type="text" placeholder="Contoh: koordinator-teknisi"
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm" />
                                <p class="mt-2 text-[11px] text-gray-500">Semua pengaturan Menu, Card, Tab, Filter, dan Tombol dari role sumber akan langsung disalin ke role baru ini.</p>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2.5">
                            <button type="button" @click="showCloneModal = false" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">Batal</button>
                            <button type="submit" :disabled="isSubmitting" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-all shadow-sm disabled:opacity-50">
                                {{ isSubmitting ? 'Menduplikasi...' : 'Duplikasi Sekarang' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL KONFIRMASI HAPUS ROLE ──────────────────────── -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center animate-fade-in-up">
                    <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3.5">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-1">Hapus Role?</h3>
                    <p class="text-xs text-gray-500 mb-5 leading-relaxed">
                        Apakah Anda yakin ingin menghapus role <b class="capitalize text-gray-800">{{ roleToDelete?.name }}</b>?
                        <span v-if="roleToDelete?.users_count > 0" class="block mt-2 text-red-600 font-bold bg-red-50 p-2 rounded-lg border border-red-200">
                            ⚠️ Masih ada {{ roleToDelete.users_count }} pengguna yang terhubung ke role ini!
                        </span>
                    </p>
                    <div class="flex items-center justify-center gap-2.5">
                        <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded-xl transition-colors w-full">Batal</button>
                        <button @click="deleteRole" :disabled="isDeleting" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition-colors w-full flex justify-center items-center">
                            {{ isDeleting ? 'Menghapus...' : 'Hapus' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ── MODAL PILIH TEMPLATE PRESET STANDAR ──────────────── -->
        <Teleport to="body">
            <div v-if="showPresetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPresetModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden animate-fade-in-up">
                    <!-- Modal Header -->
                    <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/80">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-extrabold text-gray-900 text-base">Pilih Template Hak Akses Standar</h3>
                                <p class="text-xs text-gray-500">Terapkan rekomendasi hak akses siap pakai ke role <b class="capitalize text-indigo-600">{{ selectedRole?.name }}</b></p>
                            </div>
                        </div>
                        <button @click="showPresetModal = false" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body / Presets Grid -->
                    <div class="p-5 overflow-y-auto space-y-3 flex-1">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div v-for="preset in availablePresets" :key="preset.key"
                                @click="applySelectedPreset(preset.key)"
                                class="p-3.5 rounded-xl border border-gray-200 hover:border-indigo-400 hover:bg-indigo-50/30 transition-all cursor-pointer group flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg">{{ preset.emoji }}</span>
                                            <h4 class="font-bold text-gray-900 text-sm group-hover:text-indigo-600 transition-colors">{{ preset.title }}</h4>
                                        </div>
                                        <span v-if="isPresetRecommended(preset.key)" class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full border border-emerald-200">
                                            Rekomendasi
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 leading-relaxed">{{ preset.desc }}</p>
                                </div>
                                <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center justify-between text-xs">
                                    <span class="font-extrabold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md">
                                        {{ (rolePresets[preset.key] || []).length }} Hak Akses
                                    </span>
                                    <span class="font-bold text-indigo-600 group-hover:translate-x-0.5 transition-transform flex items-center gap-0.5">
                                        Terapkan →
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                        <button @click="showPresetModal = false" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    roles: Array,
    permissionGroups: Array,
    rolePresets: {
        type: Object,
        default: () => ({})
    },
    positions: Array,
});

// ── State ──────────────────────────────────────────
const selectedRole = ref(null);
const selectedPermissions = ref([]);
const openGroups = ref([]);
const isSaving = ref(false);

// Filter & Search State
const roleSearch = ref('');
const searchQuery = ref('');
const selectedCategory = ref('all');
const typeFilter = ref('all'); // 'all', 'menu', 'card', 'tab', 'filter', 'button', 'feature'
const statusFilter = ref('all');

// Modal State
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const formName = ref('');
const isCustomRoleName = ref(false);
const isSubmitting = ref(false);

// Preset Modal State
const showPresetModal = ref(false);

// Clone Modal State
const showCloneModal = ref(false);
const roleToClone = ref(null);
const cloneFormName = ref('');

// Delete Modal State
const showDeleteModal = ref(false);
const roleToDelete = ref(null);
const isDeleting = ref(false);

// ── Category Definitions ───────────────────────────
const categoryMap = {
    'dashboard': [
        'Dashboard Utama & Statistik',
    ],
    'pelanggan': [
        'Data Pelanggan (Semua & Status)',
        'Pemasangan Baru (PSB) - 1. Booking',
        'Pemasangan Baru (PSB) - 2. Survey',
        'Pemasangan Baru (PSB) - 3. Instalasi',
        'Pemasangan Baru (PSB) - 4. Aktivasi Layanan',
        'Pantauan Pelanggan (Menunggak & Janji Bayar)',
    ],
    'billing': [
        'Billing & Invoice Pelanggan',
        'Penagihan Lapangan & Setoran',
        'Laporan Keuangan & Pajak',
    ],
    'jaringan': [
        'Topologi & Radar Jaringan FTTH',
        'Perangkat OSP (OLT, ODC, ODP, ONT)',
        'Rollout Pembangunan ODC & ODP',
        'MikroTik & RADIUS Billing',
    ],
    'tiket': [
        'Cabut Perangkat (CBP)',
        'Penanganan Khusus (PGK, PGJ, PGS)',
        'Monitoring Kinerja Teknisi',
    ],
    'gudang': [
        'Material & Logistik Gudang',
        'Serah Terima & Request Material Lapangan',
    ],
    'hrd': [
        'Karyawan & SDM (HR)',
        'Presensi & Absensi Karyawan',
        'Penggajian & Insentif (Payroll)',
    ],
    'voucher': [
        'Voucher & Mitra Reseller',
        'Marketing Afiliasi & Komisi',
    ],
    'whatsapp': [
        'WhatsApp Gateway & Smart Blast',
    ],
    'setting': [
        'Master Paket, Area & Pengaturan',
        'Manajemen Pengguna & Role Akses',
    ],
};

// ── Computed Properties ────────────────────────────
const allPermissionNames = computed(() => {
    const names = [];
    props.permissionGroups.forEach(g => {
        g.permissions.forEach(p => names.push(p.name));
    });
    return names;
});

const totalCount = computed(() => allPermissionNames.value.length);

const checkedCount = computed(() => {
    if (selectedRole.value?.name === 'admin') return totalCount.value;
    return selectedPermissions.value.length;
});

const isAllChecked = computed(() => checkedCount.value === totalCount.value);

const hasUnsavedChanges = computed(() => {
    if (!selectedRole.value || selectedRole.value.name === 'admin') return false;
    const current = [...selectedPermissions.value].sort();
    const original = [...(selectedRole.value.permissions || [])].sort();
    return JSON.stringify(current) !== JSON.stringify(original);
});

// Sidebar Filtered Roles
const filteredRoles = computed(() => {
    if (!roleSearch.value.trim()) return props.roles;
    const q = roleSearch.value.toLowerCase();
    return props.roles.filter(r => r.name.toLowerCase().includes(q));
});

// Category Tabs
const categoryTabs = computed(() => {
    return [
        { id: 'all', label: 'Semua Modul', count: totalCount.value },
        { id: 'dashboard', label: 'Dashboard & Statistik', count: countPermissionsByCategory('dashboard') },
        { id: 'pelanggan', label: 'Pelanggan & PSB', count: countPermissionsByCategory('pelanggan') },
        { id: 'billing', label: 'Billing & Kas', count: countPermissionsByCategory('billing') },
        { id: 'jaringan', label: 'Jaringan & OSP', count: countPermissionsByCategory('jaringan') },
        { id: 'tiket', label: 'Tiket & Operasional', count: countPermissionsByCategory('tiket') },
        { id: 'gudang', label: 'Gudang & Stok', count: countPermissionsByCategory('gudang') },
        { id: 'hrd', label: 'HRD & Payroll', count: countPermissionsByCategory('hrd') },
        { id: 'voucher', label: 'Voucher & Reseller', count: countPermissionsByCategory('voucher') },
        { id: 'whatsapp', label: '⚡ WhatsApp Blast', count: countPermissionsByCategory('whatsapp') },
        { id: 'setting', label: 'Master & Pengaturan', count: countPermissionsByCategory('setting') },
    ];
});

const countPermissionsByCategory = (catKey) => {
    const targetGroups = categoryMap[catKey] || [];
    return props.permissionGroups
        .filter(g => targetGroups.includes(g.group) || g.category === catKey)
        .reduce((sum, g) => sum + g.permissions.length, 0);
};

// Type Breakdown Counts for selected role
const typeCounts = computed(() => {
    const types = ['menu', 'card', 'tab', 'filter', 'button', 'field', 'feature'];
    const res = {};
    types.forEach(t => {
        res[t] = { total: 0, checked: 0 };
    });

    props.permissionGroups.forEach(g => {
        g.permissions.forEach(p => {
            const t = p.type || 'button';
            if (res[t]) {
                res[t].total++;
                if (selectedRole.value?.name === 'admin' || selectedPermissions.value.includes(p.name)) {
                    res[t].checked++;
                }
            }
        });
    });

    return res;
});

// Filtered Groups based on search, category, type, and status
const filteredGroups = computed(() => {
    let groups = props.permissionGroups;

    // 1. Filter by category
    if (selectedCategory.value !== 'all') {
        const allowedGroups = categoryMap[selectedCategory.value] || [];
        groups = groups.filter(g => allowedGroups.includes(g.group) || g.category === selectedCategory.value);
    }

    // 2. Filter by search, type or status
    const q = searchQuery.value.toLowerCase().trim();
    const status = statusFilter.value;
    const type = typeFilter.value;

    return groups.map(g => {
        let perms = g.permissions;

        // Type filter
        if (type !== 'all') {
            perms = perms.filter(p => p.type === type);
        }

        // Search query filter
        if (q) {
            perms = perms.filter(p => 
                p.label.toLowerCase().includes(q) || 
                p.name.toLowerCase().includes(q) ||
                (p.type && p.type.toLowerCase().includes(q)) ||
                g.group.toLowerCase().includes(q)
            );
        }

        // Status filter
        if (status === 'active') {
            perms = perms.filter(p => isPermActive(p.name));
        } else if (status === 'inactive') {
            perms = perms.filter(p => !isPermActive(p.name));
        }

        return {
            ...g,
            permissions: perms,
        };
    }).filter(g => g.permissions.length > 0);
});

// ── Methods ────────────────────────────────────────
const selectRole = (role) => {
    selectedRole.value = role;
    selectedPermissions.value = [...role.permissions];
    openGroups.value = props.permissionGroups.map(g => g.group);
};

const isGroupOpen = (groupName) => openGroups.value.includes(groupName);

const toggleGroup = (groupName) => {
    const i = openGroups.value.indexOf(groupName);
    if (i >= 0) openGroups.value.splice(i, 1);
    else openGroups.value.push(groupName);
};

const expandAll = () => {
    openGroups.value = props.permissionGroups.map(g => g.group);
};

const collapseAll = () => {
    openGroups.value = [];
};

const isPermActive = (name) => {
    if (selectedRole.value?.name === 'admin') return true;
    return selectedPermissions.value.includes(name);
};

// Cerdas: If enabling a card/tab/filter/button, automatically enable parent menu if specified
const togglePermissionWithAutoParent = (perm, group) => {
    if (selectedRole.value?.name === 'admin') return;

    const isActive = isPermActive(perm.name);
    if (isActive) {
        // Turn off
        const idx = selectedPermissions.value.indexOf(perm.name);
        if (idx >= 0) selectedPermissions.value.splice(idx, 1);
    } else {
        // Turn on
        selectedPermissions.value.push(perm.name);

        // Auto-enable parent menu if specified and not yet enabled
        const parentMenu = group.menu_parent;
        if (parentMenu && perm.name !== parentMenu && !selectedPermissions.value.includes(parentMenu)) {
            selectedPermissions.value.push(parentMenu);
        }

        // Cerdas: Jika mengaktifkan scope (_view_all, _view_area, _view_own, _view_assigned),
        // otomatis nonaktifkan scope lain pada modul yang sama agar tidak terjadi konflik
        const scopeSuffixes = ['_view_all', '_view_area', '_view_own', '_view_assigned'];
        const matchedSuffix = scopeSuffixes.find(s => perm.name.endsWith(s));
        if (matchedSuffix) {
            const prefix = perm.name.slice(0, -matchedSuffix.length);
            scopeSuffixes.forEach(s => {
                if (s !== matchedSuffix) {
                    const conflictingName = prefix + s;
                    const cIdx = selectedPermissions.value.indexOf(conflictingName);
                    if (cIdx >= 0) {
                        selectedPermissions.value.splice(cIdx, 1);
                    }
                }
            });
        }
    }
};

const toggleAll = () => {
    if (selectedRole.value?.name === 'admin') return;
    if (isAllChecked.value) {
        selectedPermissions.value = [];
    } else {
        selectedPermissions.value = [...allPermissionNames.value];
    }
};

const isGroupAllChecked = (group) => {
    if (selectedRole.value?.name === 'admin') return true;
    return group.permissions.every(p => selectedPermissions.value.includes(p.name));
};

const toggleGroupAll = (group) => {
    if (selectedRole.value?.name === 'admin') return;
    if (isGroupAllChecked(group)) {
        group.permissions.forEach(p => {
            const i = selectedPermissions.value.indexOf(p.name);
            if (i >= 0) selectedPermissions.value.splice(i, 1);
        });
    } else {
        group.permissions.forEach(p => {
            if (!selectedPermissions.value.includes(p.name)) {
                selectedPermissions.value.push(p.name);
            }
        });
    }
};

const getGroupCheckedCount = (group) => {
    if (selectedRole.value?.name === 'admin') return group.permissions.length;
    return group.permissions.filter(p => selectedPermissions.value.includes(p.name)).length;
};

// Template Hak Akses Standar List
const availablePresets = [
    {
        key: 'teknisi',
        emoji: '🔧',
        title: 'Teknisi Lapangan',
        desc: 'Akses PSB instalasi, tiket penanganan gangguan, ODP/ONT lapangan, dan mutasi material.',
    },
    {
        key: 'kepala teknisi',
        emoji: '👷',
        title: 'Kepala Teknisi',
        desc: 'Monitoring teknisi, approval SPK kerja, manajemen ODP/ODC & OLT, serta logistik.',
    },
    {
        key: 'noc',
        emoji: '🌐',
        title: 'NOC (Network Operation)',
        desc: 'Akses penuh topologi, radar FTTH, kapasitas ODP/ODC, OLT, RADIUS MikroTik, dan pemetaan.',
    },
    {
        key: 'maintenance',
        emoji: '🛠️',
        title: 'Maintenance / OSP',
        desc: 'Pemeliharaan jaringan kabel, perbaikan ODP/ODC, data teknis OSP, dan logistik perbaikan.',
    },
    {
        key: 'dispatcher',
        emoji: '📡',
        title: 'Dispatcher / Helpdesk',
        desc: 'Pengaturan penugasan tiket dan jadwal teknisi survei/instalasi secara terpusat.',
    },
    {
        key: 'cs',
        emoji: '🎧',
        title: 'Customer Service (CS)',
        desc: 'Pelayanan tiket kendala pelanggan, input booking registrasi, data tagihan & WhatsApp blast.',
    },
    {
        key: 'admin cs',
        emoji: '👩‍💼',
        title: 'Admin Customer Service',
        desc: 'Pengelolaan booking, aktivasi, billing, broadcast WhatsApp, serta monitoring pelanggan.',
    },
    {
        key: 'sales',
        emoji: '💼',
        title: 'Sales / Marketing',
        desc: 'Pendaftaran booking pelanggan baru, cek kapasitas ODP, voucher gratis, dan komisi afiliasi.',
    },
    {
        key: 'finance',
        emoji: '💰',
        title: 'Finance & Keuangan',
        desc: 'Manajemen invoice tagihan, kas masuk/keluar, penggajian payroll, dan laporan laba rugi.',
    },
    {
        key: 'manager',
        emoji: '📊',
        title: 'General Manager',
        desc: 'Supervisi statistik performa seluruh divisi, HRD karyawan, laporan keuangan, dan operasional.',
    },
    {
        key: 'hrd',
        emoji: '👥',
        title: 'HRD / Personalia',
        desc: 'Manajemen karyawan, data absensi presensi, rekrutmen, dan data kepegawaian internal.',
    },
    {
        key: 'reseller',
        emoji: '🎫',
        title: 'Mitra Reseller / Agen',
        desc: 'Pembuatan dan cetak voucher internet hotspot, serta monitoring pelanggan voucher.',
    },
];

const isPresetRecommended = (presetKey) => {
    if (!selectedRole.value) return false;
    const roleName = selectedRole.value.name.toLowerCase().trim();
    const key = presetKey.toLowerCase().trim();
    return roleName === key || roleName.includes(key) || key.includes(roleName);
};

const openPresetModal = () => {
    if (selectedRole.value?.name === 'admin') return;
    showPresetModal.value = true;
};

const applySelectedPreset = (presetKey) => {
    if (selectedRole.value?.name === 'admin') return;
    const presets = props.rolePresets || {};
    if (!presets[presetKey]) {
        alert(`Template "${presetKey}" tidak ditemukan.`);
        return;
    }
    selectedPermissions.value = [...presets[presetKey]];
    showPresetModal.value = false;
};

const applyPreset = (presetName) => {
    if (selectedRole.value?.name === 'admin') return;
    const key = (presetName || selectedRole.value?.name || '').toLowerCase().trim();
    const presets = props.rolePresets || {};
    
    let matchingKey = Object.keys(presets).find(k => k.toLowerCase() === key);
    if (!matchingKey) {
        if (key.includes('teknisi')) matchingKey = 'teknisi';
        else if (key.includes('cs') || key.includes('customer')) matchingKey = 'cs';
        else if (key.includes('sales')) matchingKey = 'sales';
        else if (key.includes('finance') || key.includes('keuangan')) matchingKey = 'finance';
        else if (key.includes('noc')) matchingKey = 'noc';
        else if (key.includes('reseller')) matchingKey = 'reseller';
        else if (key.includes('manager')) matchingKey = 'manager';
    }

    if (!matchingKey || !presets[matchingKey]) {
        openPresetModal();
        return;
    }

    const count = presets[matchingKey].length;
    if (confirm(`Terapkan template izin standar "${matchingKey}" (${count} hak akses) untuk role "${selectedRole.value?.name}"?\n\nSemua hak akses rekomendasi akan otomatis dicentang. Anda dapat meninjau dan klik "Simpan Perubahan".`)) {
        selectedPermissions.value = [...presets[matchingKey]];
    }
};

const resetChanges = () => {
    if (selectedRole.value) {
        selectedPermissions.value = [...(selectedRole.value.permissions || [])];
    }
};

const resetFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = 'all';
    typeFilter.value = 'all';
    statusFilter.value = 'all';
};

const savePermissions = () => {
    if (!selectedRole.value || selectedRole.value.name === 'admin') return;
    isSaving.value = true;
    router.post(`/settings/roles/${selectedRole.value.id}/update`, {
        name: selectedRole.value.name,
        permissions: selectedPermissions.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            const role = props.roles.find(r => r.id === selectedRole.value.id);
            if (role) {
                role.permissions = [...selectedPermissions.value];
                selectedRole.value = { ...role };
            }
        },
        onFinish: () => isSaving.value = false,
    });
};

// ── Type Badges & Labels ────────────────────────────
const getTypeLabel = (type) => {
    switch (type) {
        case 'menu': return '📑 Menu';
        case 'card': return '📊 Card';
        case 'tab': return '📑 Tab';
        case 'filter': return '🔍 Filter';
        case 'button': return '⚡ Tombol';
        case 'field': return '📝 Field';
        case 'feature': return '🌐 Scope';
        default: return 'Aksi';
    }
};

const getTypeBadgeClass = (type) => {
    switch (type) {
        case 'menu': return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        case 'card': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'tab': return 'bg-violet-50 text-violet-700 border-violet-200';
        case 'filter': return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'button': return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'field': return 'bg-teal-50 text-teal-700 border-teal-200';
        case 'feature': return 'bg-rose-50 text-rose-700 border-rose-200';
        default: return 'bg-gray-50 text-gray-700 border-gray-200';
    }
};

// ── Create/Edit Modal ──────────────────────────────
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    formName.value = '';
    isCustomRoleName.value = false;
    showModal.value = true;
};

const openEditModal = (role) => {
    isEditing.value = true;
    editingId.value = role.id;
    formName.value = role.name;
    const isStandard = props.positions && props.positions.some(p => p.name.toLowerCase() === role.name.toLowerCase());
    isCustomRoleName.value = !isStandard;
    showModal.value = true;
};

const submitForm = () => {
    isSubmitting.value = true;
    const cleanName = formName.value.trim().toLowerCase();
    if (isEditing.value) {
        router.post(`/settings/roles/${editingId.value}/update`, {
            name: cleanName,
            permissions: selectedRole.value?.id === editingId.value ? selectedPermissions.value : undefined,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                if (selectedRole.value?.id === editingId.value) {
                    selectedRole.value.name = cleanName;
                }
            },
            onFinish: () => isSubmitting.value = false,
        });
    } else {
        router.post('/settings/roles', { name: cleanName, permissions: [] }, {
            preserveScroll: true,
            onSuccess: () => showModal.value = false,
            onFinish: () => isSubmitting.value = false,
        });
    }
};

// ── Clone Modal ────────────────────────────────────
const openCloneModal = (role) => {
    roleToClone.value = role;
    cloneFormName.value = `${role.name}-copy`;
    showCloneModal.value = true;
};

const submitCloneForm = () => {
    if (!roleToClone.value) return;
    isSubmitting.value = true;
    router.post(`/settings/roles/${roleToClone.value.id}/clone`, {
        name: cloneFormName.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCloneModal.value = false;
        },
        onFinish: () => isSubmitting.value = false,
    });
};

// ── Delete Modal ────────────────────────────────────
const confirmDeleteRole = (role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const deleteRole = () => {
    if (!roleToDelete.value) return;
    isDeleting.value = true;
    router.post(`/settings/roles/${roleToDelete.value.id}/delete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            if (selectedRole.value?.id === roleToDelete.value.id) {
                selectedRole.value = null;
                selectedPermissions.value = [];
            }
        },
        onFinish: () => {
            isDeleting.value = false;
            roleToDelete.value = null;
        },
    });
};

// ── Helpers & Icons ─────────────────────────────────
const getGroupColor = (icon) => {
    const colors = {
        'dashboard': 'bg-blue-100 text-blue-600',
        'document-add': 'bg-yellow-100 text-yellow-700',
        'clipboard-check': 'bg-cyan-100 text-cyan-700',
        'cog': 'bg-indigo-100 text-indigo-700',
        'key': 'bg-purple-100 text-purple-700',
        'badge-check': 'bg-emerald-100 text-emerald-700',
        'users': 'bg-sky-100 text-sky-700',
        'calendar': 'bg-amber-100 text-amber-700',
        'document-text': 'bg-blue-100 text-blue-700',
        'cash': 'bg-green-100 text-green-700',
        'chart-pie': 'bg-purple-100 text-purple-700',
        'alert-circle': 'bg-red-100 text-red-700',
        'scissors': 'bg-rose-100 text-rose-700',
        'support': 'bg-rose-100 text-rose-700',
        'globe': 'bg-teal-100 text-teal-700',
        'server': 'bg-slate-100 text-slate-700',
        'box': 'bg-amber-100 text-amber-700',
        'git-branch': 'bg-lime-100 text-lime-700',
        'zap': 'bg-yellow-100 text-yellow-700',
        'wifi': 'bg-violet-100 text-violet-700',
        'archive': 'bg-orange-100 text-orange-700',
        'shopping-cart': 'bg-pink-100 text-pink-700',
        'briefcase': 'bg-rose-100 text-rose-700',
        'calculator': 'bg-emerald-100 text-emerald-700',
        'ticket': 'bg-cyan-100 text-cyan-700',
        'gift': 'bg-pink-100 text-pink-700',
        'package': 'bg-sky-100 text-sky-700',
        'map': 'bg-emerald-100 text-emerald-700',
        'lock-closed': 'bg-indigo-100 text-indigo-700',
    };
    return colors[icon] || 'bg-gray-100 text-gray-700';
};

const iconPaths = {
    dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    'document-add': 'M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'clipboard-check': 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    cog: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    key: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
    'badge-check': 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
    users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'document-text': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
    'chart-pie': 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z',
    'alert-circle': 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    support: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
    globe: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    server: 'M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
    box: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    'git-branch': 'M6 3v12M18 9a3 3 0 01-3 3H9m-3 0a3 3 0 003 3h0a3 3 0 003-3m0 0V3',
    zap: 'M13 10V3L4 14h7v7l9-11h-7z',
    wifi: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0',
    archive: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
    'shopping-cart': 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
    briefcase: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    calculator: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    ticket: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
    gift: 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V6a2 2 0 10-2 2h2zm0 0H4a2 2 0 00-2 2v3a2 2 0 002 2h16a2 2 0 002-2v-3a2 2 0 00-2-2h-8zM4 15v4a2 2 0 002 2h12a2 2 0 002-2v-4',
    package: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    map: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
    'lock-closed': 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
    scissors: 'M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879a3 3 0 11-1.414-1.414L10.586 12 7.707 9.121a3 3 0 111.414-1.414L12 10.586m0 0L19 12',
};

// Auto-select first role on mount if none selected
onMounted(() => {
    if (props.roles.length > 0 && !selectedRole.value) {
        const defaultRole = props.roles.find(r => r.name !== 'admin') || props.roles[0];
        selectRole(defaultRole);
    }
});
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

@keyframes bounceSubtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.animate-bounce-subtle {
    animation: bounceSubtle 3s ease-in-out infinite;
}
</style>
