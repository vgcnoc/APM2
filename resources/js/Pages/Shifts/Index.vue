<template>
    <AppLayout title="Shift Kerja" subtitle="Kelola jadwal jam kerja dan pembagian shift karyawan">
        <div class="space-y-6">
            <!-- Header Ringkasan & Statistik -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Shift</div>
                        <div class="text-2xl font-black text-gray-900 mt-0.5">{{ stats?.total_shifts || 0 }}</div>
                        <div class="text-[11px] text-gray-400">Pola jam kerja</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Shift Aktif</div>
                        <div class="text-2xl font-black text-emerald-700 mt-0.5">{{ stats?.active_shifts || 0 }}</div>
                        <div class="text-[11px] text-gray-400">Dapat ditugaskan</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-indigo-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Karyawan Terjadwal</div>
                        <div class="text-2xl font-black text-indigo-700 mt-0.5">{{ stats?.total_assigned_employees || 0 }}</div>
                        <div class="text-[11px] text-gray-400">Dari {{ stats?.total_employees || 0 }} total karyawan</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-purple-600 uppercase tracking-wider">Shift 24 Jam / Malam</div>
                        <div class="text-2xl font-black text-purple-700 mt-0.5">{{ nightShiftCount }}</div>
                        <div class="text-[11px] text-gray-400">Operasional NOC & Lapangan</div>
                    </div>
                </div>
            </div>

            <!-- Toolbar & Filter -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                <div class="flex items-center gap-3 flex-1 flex-wrap">
                    <div class="relative flex-1 sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari nama shift..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm transition-all bg-gray-50/50"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>

                    <select v-model="filterStatus" @change="performSearch" class="px-3.5 py-2.5 rounded-xl border border-gray-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm bg-gray-50/50">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Non-Aktif</option>
                    </select>
                </div>

                <button 
                    @click="openCreateModal" 
                    class="bg-[#1e40af] hover:bg-[#1e3a8a] text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md shadow-blue-900/20 hover:shadow-lg transition-all flex items-center justify-center gap-2 shrink-0 active:scale-95"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Tambah Shift</span>
                </button>
            </div>

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Grid Card Shift Kerja -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div 
                    v-for="shift in shifts.data" 
                    :key="shift.id" 
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-all flex flex-col justify-between group relative overflow-hidden"
                >
                    <!-- Garis Indikator Warna di Atas Card -->
                    <div class="absolute top-0 left-0 right-0 h-1.5" :class="getColorTopBar(shift.color || 'blue')"></div>

                    <div>
                        <!-- Header Card Shift -->
                        <div class="flex items-start justify-between gap-3 pt-1">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-base text-gray-900 group-hover:text-blue-700 transition-colors">
                                        {{ shift.name }}
                                    </h3>
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" 
                                        :class="shift.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ shift.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <p v-if="shift.description" class="text-xs text-gray-500 mt-1 line-clamp-2">
                                    {{ shift.description }}
                                </p>
                            </div>

                            <span v-if="shift.is_night_shift" class="px-2 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 text-[10px] font-bold shrink-0 flex items-center gap-1">
                                🌙 Shift Malam
                            </span>
                        </div>

                        <!-- Jam Masuk & Jam Keluar -->
                        <div class="mt-4 p-3.5 bg-gray-50/80 rounded-xl border border-gray-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500 flex items-center gap-1.5 font-medium">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                    Jam Masuk
                                </span>
                                <span class="font-mono font-black text-sm text-gray-900 bg-white px-2.5 py-0.5 rounded-lg border border-gray-200 shadow-2xs">
                                    {{ shift.start_time }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500 flex items-center gap-1.5 font-medium">
                                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Jam Keluar
                                </span>
                                <span class="font-mono font-black text-sm text-gray-900 bg-white px-2.5 py-0.5 rounded-lg border border-gray-200 shadow-2xs">
                                    {{ shift.end_time }}
                                </span>
                            </div>

                            <div class="pt-2 border-t border-gray-200/60 flex items-center justify-between text-xs">
                                <span class="text-gray-500 font-medium">Durasi Kerja:</span>
                                <span class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">
                                    ⏱️ {{ shift.duration_text }}
                                </span>
                            </div>
                        </div>

                        <!-- Info Karyawan Bertugas -->
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">Karyawan Bertugas:</span>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-800">
                                👥 {{ shift.employees_count || 0 }} Orang
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="mt-5 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                        <button 
                            @click="openAssignModal(shift)" 
                            class="text-xs font-bold text-blue-700 hover:text-blue-900 hover:underline flex items-center gap-1"
                        >
                            <span>Tugaskan Karyawan</span> &rarr;
                        </button>

                        <div class="flex items-center gap-1">
                            <button 
                                @click="openEditModal(shift)" 
                                title="Edit Shift" 
                                class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </button>
                            <button 
                                @click="deleteShift(shift)" 
                                title="Hapus Shift" 
                                class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="shifts.data.length === 0" class="col-span-full bg-white rounded-2xl p-12 text-center text-gray-400 border border-gray-100">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-base font-bold text-gray-700">Belum ada shift kerja yang dibuat</p>
                    <p class="text-xs text-gray-400 mt-1">Klik tombol "Tambah Shift" di atas untuk menambahkan jadwal jam kerja baru.</p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="shifts.links && shifts.links.length > 3" class="flex justify-end gap-1 mt-4">
                <Link
                    v-for="(link, key) in shifts.links"
                    :key="key"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        'px-3.5 py-2 text-xs font-medium rounded-xl transition-all',
                        link.active ? 'bg-[#1e40af] text-white font-bold' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200',
                        !link.url ? 'opacity-40 cursor-not-allowed pointer-events-none' : ''
                    ]"
                />
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL TAMBAH / EDIT SHIFT (SESUAI DESAIN USER)                 -->
        <!-- ============================================================== -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs transition-opacity animate-fade-in">
            <!-- Modal Box -->
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-scale-in border border-gray-100">
                <!-- Modal Header -->
                <div class="px-6 pt-5 pb-3 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900">
                        {{ isEditing ? 'Edit Shift' : 'Tambah Shift' }}
                    </h3>
                    <button 
                        type="button" 
                        @click="closeModal" 
                        class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100 transition-colors"
                        title="Tutup"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form Content -->
                <form @submit.prevent="submitShift" class="px-6 pb-6 pt-1 space-y-4">
                    <!-- Nama Shift -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Nama Shift
                        </label>
                        <div class="relative">
                            <input 
                                v-model="form.name" 
                                type="text" 
                                placeholder="Contoh: Pagi" 
                                required
                                class="w-full px-3.5 py-2.5 bg-white border-2 border-blue-600/80 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-700 focus:ring-3 focus:ring-blue-100 transition-all font-normal shadow-2xs"
                                autofocus
                            />
                        </div>
                        <p v-if="form.errors.name" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.name }}</p>
                    </div>

                    <!-- Jam Masuk & Jam Keluar -->
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Jam Masuk -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Jam Masuk
                            </label>
                            <div class="relative">
                                <input 
                                    v-model="form.start_time" 
                                    type="time" 
                                    required
                                    class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm font-mono text-gray-800 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-3 focus:ring-blue-100 transition-all cursor-pointer"
                                />
                            </div>
                            <p v-if="form.errors.start_time" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.start_time }}</p>
                        </div>

                        <!-- Jam Keluar -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                                Jam Keluar
                            </label>
                            <div class="relative">
                                <input 
                                    v-model="form.end_time" 
                                    type="time" 
                                    required
                                    class="w-full px-3.5 py-2.5 bg-gray-50/70 border border-gray-200 rounded-xl text-sm font-mono text-gray-800 focus:outline-none focus:border-blue-600 focus:bg-white focus:ring-3 focus:ring-blue-100 transition-all cursor-pointer"
                                />
                            </div>
                            <p v-if="form.errors.end_time" class="text-xs text-red-500 mt-1 font-medium">{{ form.errors.end_time }}</p>
                        </div>
                    </div>

                    <!-- Status Aktif Toggle & Keterangan Opsional -->
                    <div class="pt-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600 font-medium">Status Shift</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-700"></div>
                                <span class="ml-2 text-xs font-bold" :class="form.is_active ? 'text-blue-700' : 'text-gray-400'">
                                    {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Preview Durasi -->
                    <div v-if="previewDuration" class="p-2.5 bg-blue-50/70 rounded-xl border border-blue-100 text-xs flex items-center justify-between text-blue-900">
                        <span class="font-medium">Perkiraan Durasi Jam Kerja:</span>
                        <span class="font-bold font-mono">{{ previewDuration }}</span>
                    </div>

                    <!-- Tombol Simpan (Biru Rounded Lebar Penuh) -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="w-full py-3 bg-[#1e40af] hover:bg-[#1e3a8a] text-white font-bold text-sm rounded-xl transition-all shadow-md shadow-blue-900/20 hover:shadow-lg disabled:opacity-50 active:scale-[0.99] flex items-center justify-center gap-2"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL TUGASKAN KARYAWAN KE SHIFT                               -->
        <!-- ============================================================== -->
        <div v-if="isAssignModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs transition-opacity animate-fade-in">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-scale-in border border-gray-100 flex flex-col max-h-[85vh]">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Tugaskan Karyawan
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Pilih karyawan yang bekerja pada shift: <strong class="text-blue-700 font-bold">{{ selectedShift?.name }} ({{ selectedShift?.formatted_time }})</strong>
                        </p>
                    </div>
                    <button type="button" @click="isAssignModalOpen = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-3">
                    <div class="relative">
                        <input 
                            type="text" 
                            v-model="employeeSearch" 
                            placeholder="Cari nama karyawan..." 
                            class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>

                    <div class="divide-y divide-gray-100 max-h-72 overflow-y-auto border border-gray-100 rounded-xl">
                        <label 
                            v-for="emp in filteredEmployeesList" 
                            :key="emp.id" 
                            class="flex items-center justify-between p-3 hover:bg-blue-50/50 cursor-pointer transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <input 
                                    type="checkbox" 
                                    :value="emp.id" 
                                    v-model="assignForm.employee_ids" 
                                    class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                />
                                <div>
                                    <div class="text-xs font-bold text-gray-800">{{ emp.name }}</div>
                                    <div class="text-[11px] text-gray-400">{{ emp.position || 'Staf' }} · {{ emp.branch || 'Kantor Pusat' }}</div>
                                </div>
                            </div>
                            <span v-if="emp.shift_id && emp.shift_id !== selectedShift?.id" class="text-[10px] text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 font-semibold">
                                Shift Lain
                            </span>
                        </label>

                        <div v-if="filteredEmployeesList.length === 0" class="p-4 text-center text-xs text-gray-400">
                            Karyawan tidak ditemukan
                        </div>
                    </div>

                    <div class="text-[11px] text-gray-500 flex justify-between items-center px-1">
                        <span>Total Terpilih: <strong>{{ assignForm.employee_ids.length }}</strong> Karyawan</span>
                        <button type="button" @click="selectAllEmployees" class="text-blue-600 hover:underline font-semibold">
                            {{ assignForm.employee_ids.length === employees.length ? 'Batal Pilih Semua' : 'Pilih Semua' }}
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                    <button 
                        type="button" 
                        @click="isAssignModalOpen = false" 
                        class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-100"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="submitAssign" 
                        :disabled="assignForm.processing" 
                        class="px-5 py-2 bg-[#1e40af] hover:bg-[#1e3a8a] text-white rounded-xl text-xs font-bold shadow-sm disabled:opacity-50"
                    >
                        {{ assignForm.processing ? 'Menyimpan...' : 'Simpan Penugasan' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    shifts: Object,
    stats: Object,
    employees: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || '');

const isModalOpen = ref(false);
const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    start_time: '08:00',
    end_time: '17:00',
    color: 'blue',
    description: '',
    is_active: true,
});

const nightShiftCount = computed(() => {
    return (props.shifts?.data || []).filter(s => s.is_night_shift).length;
});

const previewDuration = computed(() => {
    if (!form.start_time || !form.end_time) return '';
    try {
        const sParts = form.start_time.split(':');
        const eParts = form.end_time.split(':');
        const startMin = (parseInt(sParts[0]) * 60) + parseInt(sParts[1] || 0);
        const endMin = (parseInt(eParts[0]) * 60) + parseInt(eParts[1] || 0);
        let diff = endMin < startMin ? (1440 - startMin) + endMin : endMin - startMin;
        const h = Math.floor(diff / 60);
        const m = diff % 60;
        return m > 0 ? `${h} Jam ${m} Menit` : `${h} Jam`;
    } catch (e) {
        return '';
    }
});

const performSearch = () => {
    router.get('/shifts', {
        search: search.value,
        status: filterStatus.value,
    }, { preserveState: true, replace: true });
};

function openCreateModal() {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    form.clearErrors();
    form.name = '';
    form.start_time = '08:00';
    form.end_time = '17:00';
    form.is_active = true;
    isModalOpen.value = true;
}

function openEditModal(shift) {
    isEditing.value = true;
    editId.value = shift.id;
    form.clearErrors();
    form.name = shift.name;
    form.start_time = shift.start_time;
    form.end_time = shift.end_time;
    form.color = shift.color || 'blue';
    form.description = shift.description || '';
    form.is_active = Boolean(shift.is_active);
    isModalOpen.value = true;
}

function closeModal() {
    isModalOpen.value = false;
    setTimeout(() => {
        form.reset();
        isEditing.value = false;
        editId.value = null;
    }, 200);
}

function submitShift() {
    if (isEditing.value) {
        form.post(`/shifts/${editId.value}/update`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/shifts', {
            onSuccess: () => closeModal(),
        });
    }
}

function deleteShift(shift) {
    if (confirm(`Apakah Anda yakin ingin menghapus shift "${shift.name}"?`)) {
        router.post(`/shifts/${shift.id}/delete`);
    }
}

// =======================
// Modal Assign Karyawan
// =======================
const isAssignModalOpen = ref(false);
const selectedShift = ref(null);
const employeeSearch = ref('');
const assignForm = useForm({
    employee_ids: [],
});

function openAssignModal(shift) {
    selectedShift.value = shift;
    employeeSearch.value = '';
    // Isi dengan ID karyawan yang saat ini ter-assign ke shift ini
    const assigned = (props.employees || [])
        .filter(e => e.shift_id === shift.id)
        .map(e => e.id);
    assignForm.employee_ids = [...assigned];
    isAssignModalOpen.value = true;
}

const filteredEmployeesList = computed(() => {
    const q = (employeeSearch.value || '').toLowerCase();
    if (!q) return props.employees || [];
    return (props.employees || []).filter(e => 
        (e.name || '').toLowerCase().includes(q) || 
        (e.position || '').toLowerCase().includes(q)
    );
});

function selectAllEmployees() {
    if (assignForm.employee_ids.length === (props.employees || []).length) {
        assignForm.employee_ids = [];
    } else {
        assignForm.employee_ids = (props.employees || []).map(e => e.id);
    }
}

function submitAssign() {
    if (!selectedShift.value) return;
    assignForm.post(`/shifts/${selectedShift.value.id}/assign`, {
        onSuccess: () => {
            isAssignModalOpen.value = false;
        }
    });
}

function getColorTopBar(color) {
    switch (color) {
        case 'emerald':
        case 'green':
            return 'bg-emerald-500';
        case 'amber':
        case 'orange':
            return 'bg-amber-500';
        case 'purple':
        case 'indigo':
            return 'bg-purple-600';
        case 'rose':
        case 'red':
            return 'bg-rose-500';
        default:
            return 'bg-blue-600';
    }
}
</script>
