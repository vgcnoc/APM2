<template>
    <AppLayout title="Manajemen User" subtitle="Kelola pengguna dan hak akses aplikasi">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans py-2 px-4 sm:px-6 lg:px-8">
            <!-- Flash message banner -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-xs">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 shadow-xs">
                <span>⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2.5">
                        <span class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 shadow-xs text-xl">
                            👥
                        </span>
                        <span>Manajemen User</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1 font-medium">Kelola akun staf, teknisi, hak akses sistem, dan parameter komisi payroll</p>
                </div>
                <div class="flex items-center gap-2">
                    <button 
                        @click="openCreateModal" 
                        class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-sm hover:shadow transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Tambah User</span>
                    </button>
                </div>
            </div>

            <!-- KPI Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total User -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-blue-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Pengguna</p>
                        <p class="mt-1 text-2xl font-black text-slate-900 font-mono">{{ stats.total || 0 }}</p>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Akun terdaftar di sistem</p>
                    </div>
                    <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-blue-100">
                        👥
                    </div>
                </div>

                <!-- User Aktif -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-emerald-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Pengguna Aktif</p>
                        <p class="mt-1 text-2xl font-black text-emerald-600 font-mono">{{ stats.active || 0 }}</p>
                        <p class="text-[11px] text-emerald-600 font-bold mt-0.5">Dapat login & beraktivitas</p>
                    </div>
                    <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-emerald-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>

                <!-- On Duty -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-green-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Sedang On Duty</p>
                        <p class="mt-1 text-2xl font-black text-green-600 font-mono">{{ stats.on_duty || 0 }}</p>
                        <p class="text-[11px] text-green-600 font-bold mt-0.5 flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span>Siap menerima tiket & penugasan</span>
                        </p>
                    </div>
                    <div class="w-11 h-11 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-green-100">
                        ⚡
                    </div>
                </div>

                <!-- Teknisi & Lapangan -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 flex items-center justify-between hover:border-amber-300 transition-colors">
                    <div>
                        <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Tim Lapangan</p>
                        <p class="mt-1 text-2xl font-black text-amber-600 font-mono">{{ stats.teknisi || 0 }}</p>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Teknisi & Maintenance</p>
                    </div>
                    <div class="w-11 h-11 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center text-xl shadow-xs border border-amber-100">
                        🛠️
                    </div>
                </div>
            </div>

            <!-- Filters & Actions Bar -->
            <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-slate-200/80 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1 md:max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input 
                        v-model="search" 
                        @input="onSearch"
                        type="text" 
                        placeholder="Cari nama atau email..." 
                        class="w-full pl-10 pr-9 py-2 text-xs sm:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-medium text-slate-800 placeholder-slate-400"
                    >
                    <button 
                        v-if="search" 
                        @click="resetSearch" 
                        type="button" 
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer text-xs"
                    >
                        ✕
                    </button>
                </div>
                
                <!-- Filter Dropdowns & Reset -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Filter Role -->
                    <select 
                        v-model="filterRole" 
                        @change="performSearch"
                        class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 capitalize cursor-pointer"
                    >
                        <option value="">Semua Role</option>
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>

                    <!-- Filter Status -->
                    <select 
                        v-model="filterStatus" 
                        @change="performSearch"
                        class="px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                        <option value="on_duty">Sedang On Duty</option>
                    </select>

                    <button 
                        v-if="search || filterRole || filterStatus" 
                        @click="resetAllFilters" 
                        class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset</span>
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">User</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Area Utama</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Status On Duty</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Status Akun</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-black text-slate-500 uppercase tracking-wider">Bergabung</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-black text-slate-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/70 transition-colors">
                                <!-- User Info -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-700 font-extrabold flex items-center justify-center border border-blue-100 shadow-2xs text-base">
                                            {{ (user.name || 'U').charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-extrabold text-slate-900">{{ user.name }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="px-6 py-4">
                                    <span :class="getRoleBadgeClass(user.role)" class="px-3 py-1 rounded-xl text-xs font-black uppercase border tracking-wider">
                                        {{ user.role }}
                                    </span>
                                </td>

                                <!-- Area -->
                                <td class="px-6 py-4 text-xs font-semibold text-slate-700">
                                    <span v-if="user.area" class="px-2.5 py-1 bg-slate-100 rounded-lg text-slate-800 font-bold border border-slate-200">
                                        {{ user.area.name }}
                                    </span>
                                    <span v-else class="text-slate-400 italic">Semua Area</span>
                                </td>

                                <!-- Status On Duty -->
                                <td class="px-6 py-4 text-center">
                                    <span 
                                        v-if="user.is_on_duty" 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        ON DUTY
                                    </span>
                                    <span 
                                        v-else 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200"
                                    >
                                        OFF DUTY
                                    </span>
                                </td>

                                <!-- Toggle Aktif Akun -->
                                <td class="px-6 py-4 text-center">
                                    <button 
                                        type="button"
                                        @click="toggleActive(user)" 
                                        :class="[user.is_active ? 'bg-blue-600' : 'bg-slate-200', 'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500']"
                                        :title="user.is_active ? 'Klik untuk nonaktifkan user' : 'Klik untuk aktifkan user'"
                                    >
                                        <span class="sr-only">Toggle Status</span>
                                        <span 
                                            aria-hidden="true" 
                                            :class="[user.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"
                                        ></span>
                                    </button>
                                </td>

                                <!-- Bergabung -->
                                <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                                    {{ new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-1">
                                        <button 
                                            @click="openEditModal(user)" 
                                            class="text-indigo-600 hover:text-indigo-900 p-2 hover:bg-indigo-50 rounded-xl transition-all cursor-pointer"
                                            title="Edit User"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button 
                                            @click="deleteUser(user)" 
                                            class="text-rose-600 hover:text-rose-900 p-2 hover:bg-rose-50 rounded-xl transition-all cursor-pointer"
                                            title="Hapus User"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center text-slate-500">
                                    <div class="max-w-xs mx-auto space-y-3">
                                        <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-2xl">
                                            🔍
                                        </div>
                                        <div class="font-extrabold text-slate-800 text-sm">Tidak ada data pengguna ditemukan</div>
                                        <p class="text-xs text-slate-400">
                                            {{ search || filterRole || filterStatus ? 'Coba sesuaikan kata kunci pencarian atau filter role.' : 'Belum ada pengguna yang ditambahkan.' }}
                                        </p>
                                        <div class="pt-1">
                                            <button 
                                                v-if="search || filterRole || filterStatus" 
                                                @click="resetAllFilters" 
                                                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer"
                                            >
                                                Tampilkan Semua
                                            </button>
                                            <button 
                                                v-else 
                                                @click="openCreateModal" 
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl transition-all cursor-pointer"
                                            >
                                                + Tambah User Baru
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="users.links?.length > 3" class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <span class="text-xs font-semibold text-slate-600">
                        Menampilkan <span class="font-bold text-slate-900">{{ users.from || 0 }}</span> - <span class="font-bold text-slate-900">{{ users.to || 0 }}</span> dari <span class="font-bold text-slate-900">{{ users.total || 0 }}</span> user
                    </span>
                    <div class="flex gap-1 overflow-x-auto">
                        <Link 
                            v-for="(link, i) in users.links" 
                            :key="i" 
                            :href="link.url || '#'" 
                            class="px-3.5 py-1.5 text-xs rounded-xl transition-colors border"
                            :class="[
                                link.active ? 'bg-blue-600 border-blue-600 text-white font-bold shadow-xs' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                            v-html="link.label"
                        >
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Modal (Teleported to body) -->
        <Teleport to="body">
            <div 
                v-if="isModalOpen" 
                class="fixed inset-0 z-[100] overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
                role="dialog" 
                aria-modal="true"
            >
                <div class="fixed inset-0" @click="closeModal"></div>
                
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[92vh] overflow-y-auto border border-slate-100 z-10 animate-fade-in-up">
                    <!-- Modal Header -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 sticky top-0 z-20 backdrop-blur-md">
                        <div>
                            <h3 class="text-base font-extrabold text-slate-900">
                                {{ isEditing ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ isEditing ? 'Perbarui informasi akun, role akses, dan payroll' : 'Buat akun login baru dan atur hak akses pengguna' }}
                            </p>
                        </div>
                        <button 
                            @click="closeModal" 
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-5">
                        <!-- Employee Quick Picker (Create mode) -->
                        <div v-if="!isEditing && employees && employees.length > 0" class="p-3.5 bg-blue-50/70 border border-blue-200/80 rounded-2xl space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-black text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>👤</span>
                                    <span>Sinkron dari Data Karyawan (Opsional)</span>
                                </label>
                                <span class="text-[10px] text-blue-600 font-bold">Auto-fill otomatis</span>
                            </div>
                            <select 
                                v-model="selectedEmployeeId" 
                                @change="onEmployeeSelect"
                                class="w-full text-xs font-semibold px-3 py-2 bg-white border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-800"
                            >
                                <option value="">-- Input Bebas / User Non-Karyawan --</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }} ({{ emp.position || 'Staff' }} - {{ emp.email || 'tanpa email' }})
                                </option>
                            </select>
                        </div>

                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Nama Lengkap <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="form.name" 
                                    type="text" 
                                    required 
                                    placeholder="Contoh: Budi Prasetyo"
                                    class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs sm:text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                >
                                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Email Akun <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    required 
                                    placeholder="user@isp.local"
                                    class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs sm:text-sm font-mono text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                >
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <!-- Role & Primary Area -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Role / Hak Akses <span class="text-rose-500">*</span>
                                </label>
                                <select 
                                    v-model="form.role" 
                                    required
                                    class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs sm:text-sm font-bold text-slate-800 capitalize focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                >
                                    <option value="" disabled>-- Pilih Role --</option>
                                    <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                                </select>
                                <p v-if="form.errors.role" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.role }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Area Utama
                                </label>
                                <select 
                                    v-model="form.area_id" 
                                    class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                >
                                    <option :value="null">Semua Area (Global)</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                </select>
                                <p v-if="form.errors.area_id" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.area_id }}</p>
                            </div>
                        </div>

                        <!-- Multi-Area Access -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Akses Multi-Area (Opsional)</label>
                            <p class="text-[11px] text-slate-500 mb-2">Pilih area tambahan yang dapat diakses oleh user ini. Jika kosong, user hanya dapat melihat area utamanya saja.</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-2.5 border border-slate-200 rounded-xl bg-slate-50/80">
                                <label v-for="area in areas" :key="area.id" class="flex items-center gap-2 cursor-pointer p-1.5 hover:bg-white rounded-lg transition-colors">
                                    <input 
                                        type="checkbox" 
                                        :value="area.id" 
                                        v-model="form.accessible_areas" 
                                        class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                                    >
                                    <span class="text-xs font-medium text-slate-700">{{ area.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Password & Active Switch -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Password <span v-if="!isEditing" class="text-rose-500">*</span>
                                </label>
                                <input 
                                    v-model="form.password" 
                                    type="password" 
                                    :required="!isEditing" 
                                    class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" 
                                    placeholder="Minimal 8 karakter"
                                >
                                <p v-if="isEditing" class="text-[11px] text-slate-400 mt-1">Biarkan kosong jika tidak ingin merubah password.</p>
                                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500 font-medium">{{ form.errors.password }}</p>
                            </div>

                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between mt-2">
                                <div>
                                    <label for="modal_is_active" class="block text-xs font-bold text-slate-800 cursor-pointer">Status Aktif</label>
                                    <p class="text-[11px] text-slate-500">Izin login ke aplikasi</p>
                                </div>
                                <input 
                                    id="modal_is_active" 
                                    type="checkbox" 
                                    v-model="form.is_active" 
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded cursor-pointer"
                                >
                            </div>
                        </div>

                        <!-- Gaji & Komisi (Payroll) -->
                        <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-2xl space-y-3">
                            <div class="font-extrabold text-xs uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                <span>💰</span>
                                <span>Parameter Penggajian & Komisi</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-slate-600">Gaji Pokok (Rp)</label>
                                        <span class="text-[11px] font-mono text-blue-600 font-bold">Rp {{ formatCurrency(form.base_salary) }}</span>
                                    </div>
                                    <input type="number" v-model.number="form.base_salary" min="0" step="1000" class="w-full border-slate-300 rounded-xl text-xs font-mono font-bold px-3 py-2">
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-slate-600">Insentif Penagihan (Rp)</label>
                                        <span class="text-[11px] font-mono text-blue-600 font-bold">Rp {{ formatCurrency(form.incentive_rate) }}</span>
                                    </div>
                                    <input type="number" v-model.number="form.incentive_rate" min="0" step="500" class="w-full border-slate-300 rounded-xl text-xs font-mono font-bold px-3 py-2">
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-slate-600">Fee Market / Booking (Rp)</label>
                                        <span class="text-[11px] font-mono text-blue-600 font-bold">Rp {{ formatCurrency(form.booking_fee) }}</span>
                                    </div>
                                    <input type="number" v-model.number="form.booking_fee" min="0" step="500" class="w-full border-slate-300 rounded-xl text-xs font-mono font-bold px-3 py-2">
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-bold text-slate-600">Fee Pasang / Instalasi (Rp)</label>
                                        <span class="text-[11px] font-mono text-blue-600 font-bold">Rp {{ formatCurrency(form.installation_fee) }}</span>
                                    </div>
                                    <input type="number" v-model.number="form.installation_fee" min="0" step="500" class="w-full border-slate-300 rounded-xl text-xs font-mono font-bold px-3 py-2">
                                </div>
                            </div>
                        </div>

                        <!-- BPJS & Rekening Bank -->
                        <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-2xl space-y-3">
                            <div class="font-extrabold text-xs uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                                <span>🛡️</span>
                                <span>BPJS & Rekening Transfer Payroll</span>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- BPJS Kesehatan -->
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" v-model="form.bpjs_kesehatan" class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                                        <span class="text-xs font-bold text-slate-800">BPJS Kesehatan</span>
                                    </label>
                                    <div v-if="form.bpjs_kesehatan">
                                        <input v-model="form.bpjs_kesehatan_no" type="text" placeholder="No. Kartu BPJS Kesehatan" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-lg">
                                    </div>
                                </div>

                                <!-- BPJS Ketenagakerjaan -->
                                <div class="p-3 bg-white rounded-xl border border-slate-200 space-y-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" v-model="form.bpjs_ketenagakerjaan" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                                        <span class="text-xs font-bold text-slate-800">BPJS Ketenagakerjaan</span>
                                    </label>
                                    <div v-if="form.bpjs_ketenagakerjaan">
                                        <input v-model="form.bpjs_tk_no" type="text" placeholder="No. KPJ / Ketenagakerjaan" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-lg">
                                    </div>
                                </div>
                            </div>

                            <!-- Rekening Bank -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Bank / E-Wallet</label>
                                    <input v-model="form.bank_name" type="text" placeholder="BCA / Mandiri / BRI" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">No. Rekening</label>
                                    <input v-model="form.bank_account_number" type="text" placeholder="1234567890" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-lg font-mono">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Atas Nama</label>
                                    <input v-model="form.bank_account_holder" type="text" placeholder="Nama Pemilik" class="w-full text-xs px-3 py-1.5 border border-slate-200 rounded-lg">
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-2 flex justify-end gap-2.5 border-t border-slate-100">
                            <button 
                                type="button" 
                                @click="closeModal" 
                                class="px-5 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition-all cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="px-5 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-95 rounded-xl shadow-xs transition-all disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan User') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

function debounce(func, timeout = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { func.apply(this, args); }, timeout);
    };
}

const props = defineProps({
    users: Object,
    stats: Object,
    areas: Array,
    roles: Array,
    employees: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterRole = ref(props.filters?.role || '');
const filterStatus = ref(props.filters?.status || '');
const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedEmployeeId = ref('');

const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    role: 'teknisi',
    area_id: null,
    accessible_areas: [],
    base_salary: 0,
    incentive_rate: 0,
    booking_fee: 0,
    installation_fee: 0,
    is_active: true,
    bpjs_kesehatan: false,
    bpjs_kesehatan_no: '',
    bpjs_ketenagakerjaan: false,
    bpjs_tk_no: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_holder: '',
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value || 0);
};

const getRoleBadgeClass = (role) => {
    switch ((role || '').toLowerCase()) {
        case 'admin':
        case 'super admin':
        case 'manager':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'teknisi':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'maintenance':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'noc':
            return 'bg-teal-50 text-teal-700 border-teal-200';
        case 'cs':
            return 'bg-pink-50 text-pink-700 border-pink-200';
        case 'sales':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'reseller':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
};

const performSearch = () => {
    router.get('/users', {
        search: search.value || undefined,
        role: filterRole.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const onSearch = debounce(() => {
    performSearch();
}, 300);

const resetSearch = () => {
    search.value = '';
    performSearch();
};

const resetAllFilters = () => {
    search.value = '';
    filterRole.value = '';
    filterStatus.value = '';
    router.get('/users', {}, { preserveState: true, replace: true });
};

const onEmployeeSelect = () => {
    if (!selectedEmployeeId.value) return;
    const emp = (props.employees || []).find(e => e.id === Number(selectedEmployeeId.value));
    if (emp) {
        form.name = emp.name;
        if (emp.email) form.email = emp.email;
        if (emp.position) {
            const pos = emp.position.toLowerCase();
            form.role = (props.roles || []).includes(pos) ? pos : (pos.includes('teknisi') ? 'teknisi' : 'admin');
        }
        if (emp.area_id) form.area_id = emp.area_id;
        if (emp.base_salary) form.base_salary = emp.base_salary;
    }
};

function openCreateModal() {
    isEditing.value = false;
    selectedEmployeeId.value = '';
    form.reset();
    form.clearErrors();
    form.id = null;
    form.role = 'teknisi';
    form.is_active = true;
    isModalOpen.value = true;
}

function openEditModal(user) {
    isEditing.value = true;
    selectedEmployeeId.value = '';
    form.reset();
    form.clearErrors();
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.area_id = user.area_id;
    form.accessible_areas = user.accessible_areas || [];
    form.base_salary = Number(user.base_salary || 0);
    form.incentive_rate = Number(user.incentive_rate || 0);
    form.booking_fee = Number(user.booking_fee || 0);
    form.installation_fee = Number(user.installation_fee || 0);
    form.password = '';
    form.is_active = Boolean(user.is_active);
    form.bpjs_kesehatan = Boolean(user.bpjs_kesehatan);
    form.bpjs_kesehatan_no = user.bpjs_kesehatan_no || '';
    form.bpjs_ketenagakerjaan = Boolean(user.bpjs_ketenagakerjaan);
    form.bpjs_tk_no = user.bpjs_tk_no || '';
    form.bank_name = user.bank_name || '';
    form.bank_account_number = user.bank_account_number || '';
    form.bank_account_holder = user.bank_account_holder || '';
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => {
        form.reset();
        form.clearErrors();
        isEditing.value = false;
    }, 200);
}

function submit() {
    const url = isEditing.value && form.id
        ? `/users/${form.id}/update`
        : '/users';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Berhasil!',
                text: isEditing.value ? 'Data user berhasil diperbarui.' : 'User baru berhasil ditambahkan.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Periksa kembali formulir input.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstErr,
                confirmButtonColor: '#2563eb'
            });
        }
    });
}

function toggleActive(user) {
    router.post(`/users/${user.id}/toggle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Status Diperbarui',
                text: `Status user ${user.name} berhasil diperbarui.`,
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        },
        onError: (err) => {
            const firstErr = Object.values(err || {})[0] || 'Gagal memperbarui status user.';
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Gagal',
                text: firstErr,
                showConfirmButton: false,
                timer: 2500,
            });
        }
    });
}

function deleteUser(user) {
    Swal.fire({
        title: 'Hapus User?',
        html: `Apakah Anda yakin ingin menghapus akun <strong>"${user.name}"</strong> (${user.email})?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus User',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(`/users/${user.id}/delete`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Berhasil Dihapus',
                        text: `User ${user.name} berhasil dihapus.`,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                },
                onError: (err) => {
                    const firstErr = Object.values(err || {})[0] || 'Tidak dapat menghapus user.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menghapus',
                        text: firstErr,
                        confirmButtonColor: '#e11d48'
                    });
                }
            });
        }
    });
}
</script>
