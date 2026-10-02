<template>
    <AppLayout title="Data Karyawan" subtitle="Kelola data personalia karyawan perusahaan">
        <div class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="glass-card p-5 border-2 border-blue-500/10 hover:border-blue-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Karyawan</p>
                            <p class="text-2xl font-bold text-gray-900">{{ employees.total || 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5 border-2 border-emerald-500/10 hover:border-emerald-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Pegawai Tetap</p>
                            <p class="text-2xl font-bold text-gray-900">{{ countByType('Tetap') }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5 border-2 border-amber-500/10 hover:border-amber-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 text-amber-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Kontrak</p>
                            <p class="text-2xl font-bold text-gray-900">{{ countByType('Kontrak') }}</p>
                        </div>
                    </div>
                </div>
                <div class="glass-card p-5 border-2 border-purple-500/10 hover:border-purple-500/30 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-100 text-purple-600 rounded-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Freelance</p>
                            <p class="text-2xl font-bold text-gray-900">{{ countByType('Freelance') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3 flex-1">
                    <div class="relative w-full sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari nama, posisi, No IAK..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all text-sm"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <button @click="openModal()" class="btn-primary w-full md:w-auto shrink-0 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Tambah Karyawan
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.error }}</span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Karyawan</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Posisi</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kontak</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Area</th>
                                <th class="py-4 px-6 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="emp in employees.data" :key="emp.id" class="hover:bg-gray-50/50 transition-colors group">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm overflow-hidden shrink-0">
                                            <img v-if="emp.photo" :src="`/storage/${emp.photo}`" class="w-full h-full object-cover" alt="">
                                            <span v-else>{{ emp.name?.charAt(0)?.toUpperCase() }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ emp.name }}</p>
                                            <p v-if="emp.iak_number" class="text-xs text-gray-400 mt-0.5">IAK: {{ emp.iak_number }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ emp.position || '-' }}
                                    </span>
                                    <p v-if="emp.branch" class="text-xs text-gray-400 mt-1">{{ emp.branch }}</p>
                                </td>
                                <td class="py-4 px-6">
                                    <p v-if="emp.phone" class="text-sm text-gray-700">{{ emp.phone }}</p>
                                    <p v-if="emp.email" class="text-xs text-gray-400 mt-0.5">{{ emp.email }}</p>
                                    <p v-if="!emp.phone && !emp.email" class="text-xs text-gray-300">-</p>
                                </td>
                                <td class="py-4 px-6">
                                    <span :class="employeeTypeBadge(emp.employee_type)" class="px-2.5 py-1 rounded-full text-xs font-medium border">
                                        {{ emp.employee_type || '-' }}
                                    </span>
                                    <p v-if="emp.join_date" class="text-[10px] text-gray-400 mt-1">Gabung: {{ formatDate(emp.join_date) }}</p>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <p class="text-sm font-semibold text-gray-900">{{ emp.area?.name || '-' }}</p>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openModal(emp)" title="Edit" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-blue-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                        <button @click="deleteEmployee(emp.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="6" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada data karyawan</p>
                                        <p class="text-xs text-gray-300 mt-1">Klik "Tambah Karyawan" untuk memulai</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="employees.links && employees.data.length > 0" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Menampilkan {{ employees.from }} - {{ employees.to }} dari {{ employees.total }} data</p>
                    <div class="flex gap-1">
                        <Link 
                            v-for="(link, i) in employees.links" 
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                            :class="[
                                link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Form -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Karyawan' : 'Tambah Karyawan Baru' }}</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Photo Upload -->
                        <div class="flex flex-col items-center mb-2">
                            <label class="cursor-pointer group relative">
                                <div class="w-24 h-24 rounded-full border-2 border-dashed border-gray-300 group-hover:border-blue-500 flex items-center justify-center overflow-hidden transition-colors bg-gray-50">
                                    <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" alt="Preview">
                                    <svg v-else class="w-8 h-8 text-gray-300 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <input type="file" accept="image/*" class="hidden" @change="handlePhoto">
                            </label>
                            <p class="text-xs text-blue-500 mt-2">Upload foto karyawan</p>
                        </div>

                        <!-- Name & IAK -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label>
                                <input v-model="form.name" type="text" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nama lengkap">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">No IAK</label>
                                <input v-model="form.iak_number" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nomor IAK">
                            </div>
                        </div>

                        <!-- Position & Email -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Posisi</label>
                                <select v-model="form.position" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih posisi</option>
                                    <option v-for="pos in positions" :key="pos.id" :value="pos.name">{{ pos.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input v-model="form.email" type="email" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="email@contoh.com">
                            </div>
                        </div>

                        <!-- Phone & Area -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                                <input v-model="form.phone" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="08xxxxxxxxxx">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Area</label>
                                <select v-model="form.area_id" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih Area</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Branch & Join Date -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cabang</label>
                                <select v-model="form.branch" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih cabang</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.name">{{ area.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Tanggal Gabung
                                    </span>
                                </label>
                                <input v-model="form.join_date" type="date" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            </div>
                        </div>

                        <!-- Employee Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Tipe Pegawai
                                </span>
                            </label>
                            <select v-model="form.employee_type" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                <option value="">Pilih tipe</option>
                                <option value="Tetap">Tetap</option>
                                <option value="Kontrak">Kontrak</option>
                                <option value="Freelance">Freelance</option>
                                <option value="Magang">Magang</option>
                            </select>
                        </div>

                        <!-- Payment Method -->
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                            <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                Informasi Pembayaran
                            </h4>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Metode Pembayaran</label>
                                <select v-model="form.payment_method" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    <option value="">Pilih metode</option>
                                    <option value="Transfer Bank">Transfer Bank</option>
                                    <option value="Cash">Cash</option>
                                    <option value="E-Wallet">E-Wallet</option>
                                </select>
                            </div>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <textarea v-model="form.address" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 resize-none" placeholder="Alamat lengkap"></textarea>
                        </div>
                    </form>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button type="button" @click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="submit" :disabled="form.processing" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isEditing ? 'Simpan Perubahan' : 'Simpan Karyawan' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    employees: Object,
    areas: Array,
    positions: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const photoPreview = ref(null);

const form = useForm({
    name: '',
    iak_number: '',
    position: '',
    email: '',
    phone: '',
    area_id: '',
    branch: '',
    join_date: '',
    employee_type: '',
    payment_method: '',
    address: '',
    photo: null,
});

const formatNumber = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const countByType = (type) => {
    return props.employees.data?.filter(e => e.employee_type === type).length || 0;
};

const employeeTypeBadge = (type) => {
    const map = {
        'Tetap': 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'Kontrak': 'bg-amber-50 text-amber-700 border-amber-200',
        'Freelance': 'bg-purple-50 text-purple-700 border-purple-200',
        'Magang': 'bg-sky-50 text-sky-700 border-sky-200',
    };
    return map[type] || 'bg-gray-50 text-gray-700 border-gray-200';
};

const performSearch = () => {
    router.get('/employees', { search: search.value }, { preserveState: true, preserveScroll: true });
};

const handlePhoto = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    }
};

function openModal(emp = null) {
    if (emp) {
        isEditing.value = true;
        editingId.value = emp.id;
        form.name = emp.name || '';
        form.iak_number = emp.iak_number || '';
        form.position = emp.position || '';
        form.email = emp.email || '';
        form.phone = emp.phone || '';
        form.area_id = emp.area_id || '';
        form.branch = emp.branch || '';
        form.join_date = emp.join_date || '';
        form.employee_type = emp.employee_type || '';
        form.payment_method = emp.payment_method || '';
        form.address = emp.address || '';
        form.photo = null;
        photoPreview.value = emp.photo ? `/storage/${emp.photo}` : null;
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        photoPreview.value = null;
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    photoPreview.value = null;
}

function submit() {
    if (isEditing.value) {
        form.post(`/employees/${editingId.value}/update`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/employees', {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => closeModal(),
        });
    }
}

function deleteEmployee(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data karyawan ini?')) {
        router.post(`/employees/${id}/delete`);
    }
}
</script>
