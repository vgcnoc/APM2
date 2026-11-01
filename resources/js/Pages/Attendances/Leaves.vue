<template>
    <AppLayout title="Izin, Sakit & Alpha">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            
            <!-- HEADER & TOMBOL AKSI UTAMA -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-100 dark:border-slate-700/60">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </span>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white tracking-tight">
                            Izin, Sakit &amp; Alpha
                        </h1>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Pencatatan manual dispensasi surat dokter, izin tidak masuk &amp; mangkir kerja khusus HRD &amp; Finance
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button 
                        @click="syncPayroll"
                        :disabled="isSyncing"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 shadow-xs transition-all cursor-pointer disabled:opacity-50"
                        title="Sinkronkan potongan izin & mangkir ke slip gaji periode terpilih"
                    >
                        <svg :class="{'animate-spin': isSyncing}" class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>{{ isSyncing ? 'Menyinkronkan...' : 'Sinkronkan Payroll' }}</span>
                    </button>

                    <button 
                        @click="openCreateModal"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm hover:shadow transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>+ Input Ketidakhadiran</span>
                    </button>
                </div>
            </div>

            <!-- RINGKASAN KARTU STATISTIK BULAN INI -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/60 shadow-xs">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Kasus</p>
                    <p class="text-xl font-black text-slate-800 dark:text-white mt-1">{{ stats.total_records }} <span class="text-xs font-normal text-slate-400">Hari</span></p>
                </div>
                <div class="bg-emerald-50/60 dark:bg-emerald-950/20 p-4 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 shadow-xs">
                    <p class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sakit (Dokter)</p>
                    <p class="text-xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ stats.sick_with_cert }} <span class="text-xs font-normal text-emerald-500">Hari (Rp 0)</span></p>
                </div>
                <div class="bg-amber-50/60 dark:bg-amber-950/20 p-4 rounded-2xl border border-amber-100 dark:border-amber-900/30 shadow-xs">
                    <p class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Sakit (Tanpa Surat)</p>
                    <p class="text-xl font-black text-amber-700 dark:text-amber-300 mt-1">{{ stats.sick_no_cert }} <span class="text-xs font-normal text-amber-500">Hari</span></p>
                </div>
                <div class="bg-blue-50/60 dark:bg-blue-950/20 p-4 rounded-2xl border border-blue-100 dark:border-blue-900/30 shadow-xs">
                    <p class="text-[11px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Izin Tidak Masuk</p>
                    <p class="text-xl font-black text-blue-700 dark:text-blue-300 mt-1">{{ stats.permit }} <span class="text-xs font-normal text-blue-500">Hari</span></p>
                </div>
                <div class="bg-rose-50/60 dark:bg-rose-950/20 p-4 rounded-2xl border border-rose-100 dark:border-rose-900/30 shadow-xs">
                    <p class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Alpha / Mangkir</p>
                    <p class="text-xl font-black text-rose-700 dark:text-rose-300 mt-1">{{ stats.alpha }} <span class="text-xs font-normal text-rose-500">Hari</span></p>
                </div>
                <div class="bg-purple-50/60 dark:bg-purple-950/20 p-4 rounded-2xl border border-purple-100 dark:border-purple-900/30 shadow-xs">
                    <p class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider">Total Denda</p>
                    <p class="text-lg font-black text-purple-700 dark:text-purple-300 mt-1">Rp {{ formatRupiah(stats.total_penalty) }}</p>
                </div>
            </div>

            <!-- FILTER & PENCARIAN -->
            <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-100 dark:border-slate-700/60 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <!-- Filter Bulan -->
                    <select v-model="filterForm.month" @change="applyFilter" class="text-xs font-bold py-2 px-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        <option v-for="(mName, idx) in months" :key="idx" :value="idx + 1">{{ mName }}</option>
                    </select>

                    <!-- Filter Tahun -->
                    <select v-model="filterForm.year" @change="applyFilter" class="text-xs font-bold py-2 px-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                    </select>

                    <!-- Filter Tipe -->
                    <select v-model="filterForm.type" @change="applyFilter" class="text-xs font-bold py-2 px-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200">
                        <option value="all">Semua Jenis Ketidakhadiran</option>
                        <option value="sick_with_cert">Sakit (Surat Dokter)</option>
                        <option value="sick_no_cert">Sakit (Tanpa Surat)</option>
                        <option value="permit">Izin Tidak Masuk</option>
                        <option value="alpha">Alpha / Mangkir</option>
                        <option value="leave">Cuti Tahunan</option>
                    </select>
                </div>

                <!-- Input Pencarian -->
                <div class="relative w-full md:w-72">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        type="text" 
                        v-model="filterForm.search" 
                        @keyup.enter="applyFilter"
                        placeholder="Cari nama / posisi..." 
                        class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- TABEL DATA KETIDAKHADIRAN -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700/60 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                        <thead class="bg-slate-50/80 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tanggal</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Karyawan</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Jenis Ketidakhadiran</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Bukti Surat / Dokumen</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keterangan / Alasan</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Denda / Potongan</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Dicatat Oleh</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 bg-white dark:bg-slate-800">
                            <tr v-for="item in leaves.data" :key="item.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-700/30 transition-colors">
                                <!-- Tanggal -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs font-bold text-slate-700 dark:text-slate-200">
                                    {{ formatDateIndo(item.date) }}
                                </td>

                                <!-- Karyawan -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-black text-xs flex items-center justify-center shrink-0">
                                            {{ (item.employee?.name || 'K').substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-extrabold text-slate-800 dark:text-slate-100">{{ item.employee?.name || '-' }}</p>
                                            <p class="text-[10px] text-slate-400">{{ item.employee?.position || '-' }} &bull; {{ item.employee?.branch || item.employee?.area?.name || 'Pusat' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Jenis Ketidakhadiran -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span 
                                        :class="getTypeBadgeClass(item.type)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border"
                                    >
                                        {{ getTypeName(item.type) }}
                                    </span>
                                </td>

                                <!-- Bukti Surat Dokter -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div v-if="item.attachment" class="flex items-center gap-2">
                                        <button 
                                            @click="previewImage = '/storage/' + item.attachment"
                                            type="button"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-[11px] font-bold hover:bg-emerald-100 cursor-pointer transition-all"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Lihat Surat
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-slate-400 italic">Tanpa Berkas</span>
                                </td>

                                <!-- Keterangan / Alasan -->
                                <td class="px-5 py-4 text-xs text-slate-600 dark:text-slate-300 max-w-xs">
                                    <p class="truncate" :title="item.reason">{{ item.reason || '-' }}</p>
                                </td>

                                <!-- Nominal Denda -->
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <p v-if="item.penalty_amount > 0" class="text-xs font-black text-rose-600 dark:text-rose-400">
                                        - Rp {{ formatRupiah(item.penalty_amount) }}
                                    </p>
                                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400">
                                        Bebas Denda
                                    </span>
                                </td>

                                <!-- Dicatat Oleh -->
                                <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">
                                    <p class="font-bold text-slate-700 dark:text-slate-300">{{ item.recorded_by?.name || 'Admin' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ formatDateTime(item.created_at) }}</p>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <button 
                                        @click="deleteLeave(item)"
                                        type="button"
                                        class="p-1.5 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                        title="Hapus dan pulihkan jadwal"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>

                            <!-- Baris Kosong -->
                            <tr v-if="leaves.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="font-bold text-sm text-slate-600 dark:text-slate-300">Belum ada catatan ketidakhadiran</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Semua karyawan hadir atau belum ada data sakit/izin yang diinput pada periode ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="leaves.links && leaves.links.length > 3" class="px-5 py-4 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                    <p class="text-xs text-slate-500">
                        Menampilkan {{ leaves.from || 0 }} - {{ leaves.to || 0 }} dari {{ leaves.total }} data
                    </p>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, i) in leaves.links" 
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700',
                                !link.url ? 'opacity-40 pointer-events-none' : '',
                                'px-3 py-1.5 text-xs rounded-lg transition-colors'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

            <!-- MODAL FORM INPUT KETIDAKHADIRAN -->
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-800 rounded-3xl max-w-lg w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden z-10">
                    
                    <!-- Modal Header -->
                    <div class="px-6 py-4 bg-slate-50/70 dark:bg-slate-800/80 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            </span>
                            <h3 class="text-base font-black text-slate-800 dark:text-white">Input Ketidakhadiran Baru</h3>
                        </div>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitLeave" class="px-6 py-5 overflow-y-auto space-y-4 flex-1">
                        <!-- Karyawan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                                Karyawan <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                v-model="form.employee_id" 
                                required
                                class="w-full text-xs font-bold py-2.5 px-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="" disabled>-- Pilih Karyawan --</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }} &bull; {{ emp.position || 'Staff' }} ({{ emp.branch || 'Pusat' }})
                                </option>
                            </select>
                            <p v-if="form.errors.employee_id" class="text-xs text-rose-500 mt-1">{{ form.errors.employee_id }}</p>
                        </div>

                        <!-- Pilihan Jenis Ketidakhadiran -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                                Jenis Ketidakhadiran <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label 
                                    v-for="opt in typeOptions" 
                                    :key="opt.value"
                                    :class="[
                                        form.type === opt.value ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/30 text-indigo-900 dark:text-indigo-200 ring-1 ring-indigo-600' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/30 text-slate-700 dark:text-slate-300',
                                        'flex items-start gap-2.5 p-2.5 rounded-xl border cursor-pointer transition-all'
                                    ]"
                                >
                                    <input type="radio" v-model="form.type" :value="opt.value" @change="onTypeChanged" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                                    <div>
                                        <p class="text-xs font-bold leading-tight">{{ opt.label }}</p>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">{{ opt.desc }}</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.type" class="text-xs text-rose-500 mt-1">{{ form.errors.type }}</p>
                        </div>

                        <!-- Rentang Tanggal -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Tanggal Mulai <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    v-model="form.start_date" 
                                    required
                                    class="w-full text-xs py-2 px-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                                />
                                <p v-if="form.errors.start_date" class="text-xs text-rose-500 mt-1">{{ form.errors.start_date }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    Tanggal Selesai <span class="text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <input 
                                    type="date" 
                                    v-model="form.end_date" 
                                    :min="form.start_date"
                                    class="w-full text-xs py-2 px-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                                />
                                <p v-if="form.errors.end_date" class="text-xs text-rose-500 mt-1">{{ form.errors.end_date }}</p>
                            </div>
                        </div>

                        <!-- Nominal Denda Per Hari -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                                    Nominal Denda Per Hari (Rp)
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">Bisa diubah bila ada kebijakan</span>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-slate-400">Rp</span>
                                <input 
                                    type="number" 
                                    v-model="form.penalty_amount" 
                                    min="0"
                                    step="1000"
                                    class="w-full pl-9 pr-3 py-2 text-xs font-bold border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200"
                                />
                            </div>
                            <p v-if="form.penalty_amount > 0" class="text-[11px] text-rose-500 font-semibold mt-1">
                                Total perkiraan denda: Rp {{ formatRupiah(form.penalty_amount * calculateDaysCount) }} ({{ calculateDaysCount }} Hari)
                            </p>
                            <p v-else class="text-[11px] text-emerald-600 font-semibold mt-1">
                                Bebas denda potongan (Rp 0)
                            </p>
                        </div>

                        <!-- Upload Berkas Surat Dokter / Bukti -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Surat Dokter / Dokumen Bukti <span class="text-slate-400 font-normal">(Foto JPG/PNG/PDF)</span>
                            </label>
                            <input 
                                type="file" 
                                @change="handleFileChange"
                                accept="image/*,application/pdf"
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/40 dark:file:text-indigo-300 cursor-pointer"
                            />
                            <p v-if="form.errors.attachment" class="text-xs text-rose-500 mt-1">{{ form.errors.attachment }}</p>
                        </div>

                        <!-- Keterangan / Alasan -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Keterangan / Alasan Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <textarea 
                                v-model="form.reason" 
                                rows="2" 
                                required
                                placeholder="Contoh: Sakit demam berdarah opname di RSUD, Izin menghadiri wisuda keluarga, dll"
                                class="w-full text-xs py-2 px-3 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-indigo-500 focus:border-indigo-500"
                            ></textarea>
                            <p v-if="form.errors.reason" class="text-xs text-rose-500 mt-1">{{ form.errors.reason }}</p>
                        </div>

                        <!-- Modal Actions -->
                        <div class="pt-2 flex items-center justify-end gap-2.5">
                            <button 
                                type="button" 
                                @click="showModal = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Ketidakhadiran' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL PREVIEW SURAT DOKTER / GAMBAR -->
            <div v-if="previewImage" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="previewImage = null"></div>
                <div class="relative max-w-3xl w-full max-h-[90vh] flex flex-col items-center justify-center z-10">
                    <button 
                        @click="previewImage = null"
                        class="absolute -top-10 right-0 text-white hover:text-slate-200 bg-black/50 p-1.5 rounded-full"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <img :src="previewImage" alt="Surat Dokter" class="max-h-[80vh] w-auto rounded-2xl shadow-2xl object-contain bg-white dark:bg-slate-900 border border-slate-700" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    leaves: Object,
    stats: Object,
    employees: Array,
    defaultSettings: Object,
    filters: Object,
});

const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

const filterForm = ref({
    month: props.filters.month,
    year: props.filters.year,
    type: props.filters.type || 'all',
    search: props.filters.search || '',
});

const applyFilter = () => {
    router.get(route('attendance-leaves.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const showModal = ref(false);
const previewImage = ref(null);
const isSyncing = ref(false);

const typeOptions = [
    { value: 'sick_with_cert', label: 'Sakit (Surat Dokter)', desc: 'Bebas denda (Rp 0), dispensasi sah' },
    { value: 'sick_no_cert', label: 'Sakit (Tanpa Surat)', desc: `Denda Rp ${new Intl.NumberFormat('id-ID').format(props.defaultSettings?.sick_no_cert_amount || 20000)} / hari` },
    { value: 'permit', label: 'Izin Tidak Masuk', desc: `Denda Rp ${new Intl.NumberFormat('id-ID').format(props.defaultSettings?.permit_amount || 25000)} / hari` },
    { value: 'alpha', label: 'Alpha / Mangkir Kerja', desc: `Denda Rp ${new Intl.NumberFormat('id-ID').format(props.defaultSettings?.alpha_amount || 35000)} / hari` },
    { value: 'leave', label: 'Cuti Tahunan / Khusus', desc: 'Bebas denda (Rp 0), hak cuti resmi' },
];

const getTodayDate = () => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const form = useForm({
    employee_id: '',
    start_date: getTodayDate(),
    end_date: '',
    type: 'sick_with_cert',
    reason: '',
    penalty_amount: 0,
    attachment: null,
});

const openCreateModal = () => {
    form.reset();
    form.start_date = getTodayDate();
    form.end_date = '';
    form.type = 'sick_with_cert';
    form.penalty_amount = 0;
    form.clearErrors();
    showModal.value = true;
};

const onTypeChanged = () => {
    if (form.type === 'sick_with_cert' || form.type === 'leave') {
        form.penalty_amount = 0;
    } else if (form.type === 'sick_no_cert') {
        form.penalty_amount = props.defaultSettings?.sick_no_cert_amount || 20000;
    } else if (form.type === 'permit') {
        form.penalty_amount = props.defaultSettings?.permit_amount || 25000;
    } else if (form.type === 'alpha') {
        form.penalty_amount = props.defaultSettings?.alpha_amount || 35000;
    }
};

const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
        form.attachment = e.target.files[0];
    }
};

const calculateDaysCount = computed(() => {
    if (!form.start_date) return 1;
    if (!form.end_date) return 1;
    const s = new Date(form.start_date);
    const e = new Date(form.end_date);
    const diffTime = Math.abs(e - s);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    return isNaN(diffDays) || diffDays < 1 ? 1 : diffDays;
});

const submitLeave = () => {
    form.post(route('attendance-leaves.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Dicatat!',
                text: 'Data ketidakhadiran berhasil disimpan dan jadwal kerja otomatis disesuaikan.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            const firstErr = Object.values(errors)[0] || 'Terjadi kesalahan saat menyimpan data.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal Menyimpan',
                text: firstErr,
            });
        }
    });
};

const deleteLeave = (item) => {
    Swal.fire({
        title: 'Hapus Catatan?',
        text: `Hapus catatan ketidakhadiran ${item.employee?.name} pada tanggal ${formatDateIndo(item.date)}? Jadwal kerja dan potongan presensi akan dipulihkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
    }).then((res) => {
        if (res.isConfirmed) {
            router.delete(route('attendance-leaves.destroy', item.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: 'Catatan ketidakhadiran berhasil dihapus dan jadwal dipulihkan.',
                        timer: 1600,
                        showConfirmButton: false,
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err)[0] || 'Gagal menghapus data.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: msg,
                    });
                }
            });
        }
    });
};

const syncPayroll = () => {
    isSyncing.value = true;
    router.post(route('attendance-leaves.sync-payroll'), {
        month: filterForm.value.month,
        year: filterForm.value.year,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSyncing.value = false;
        },
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Payroll Disinkronkan!',
                text: 'Potongan denda ketidakhadiran periode ini berhasil diperbarui di slip gaji.',
                timer: 1800,
                showConfirmButton: false,
            });
        },
        onError: (err) => {
            const msg = Object.values(err)[0] || 'Gagal menyinkronkan data ke payroll.';
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: msg,
            });
        }
    });
};

const formatRupiah = (num) => {
    return new Intl.NumberFormat('id-ID').format(num || 0);
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getTypeName = (type) => {
    switch (type) {
        case 'sick_with_cert': return 'Sakit (Surat Dokter)';
        case 'sick_no_cert': return 'Sakit (Tanpa Surat)';
        case 'permit': return 'Izin Tidak Masuk';
        case 'alpha': return 'Alpha / Mangkir';
        case 'leave': return 'Cuti Tahunan';
        default: return type;
    }
};

const getTypeBadgeClass = (type) => {
    switch (type) {
        case 'sick_with_cert':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
        case 'sick_no_cert':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
        case 'permit':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800';
        case 'alpha':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800';
        case 'leave':
            return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800';
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
    }
};
</script>
