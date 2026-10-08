<template>
    <AppLayout title="CBP / Cabut Perangkat" subtitle="Manajemen Pencabutan dan Stop Permanen Layanan">
        <div class="space-y-6">
            <!-- Header Actions & Filters -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-2">
                <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3 flex-1">
                    <div class="relative w-full sm:w-80">
                        <input 
                            type="text" 
                            v-model="search" 
                            placeholder="Cari ID CBP, pelanggan..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-all text-sm"
                            @keyup.enter="performSearch"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    
                    <select v-model="filterStatus" @change="performSearch" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-gray-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 text-sm bg-gray-50/50">
                        <option value="">Semua Status</option>
                        <option value="pending">Menunggu Teknisi (Pending)</option>
                        <option value="assigned">Dalam Proses (Assigned)</option>
                        <option value="completed">Selesai (Completed)</option>
                        <option value="canceled">Dibatalkan</option>
                    </select>
                </div>

                <button @click="openModal()" class="bg-gradient-to-r from-red-600 to-rose-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-md shadow-red-500/20 hover:shadow-lg hover:shadow-red-500/30 hover:-translate-y-0.5 transition-all w-full md:w-auto shrink-0 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Form CBP
                </button>
            </div>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-4 flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 animate-fade-in-up">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
            </div>

            <!-- Data Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="p-4 w-40">Nomor CBP</th>
                                <th class="p-4">Pelanggan</th>
                                <th class="p-4">Alasan Cabut</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Teknisi</th>
                                <th class="p-4">Waktu Selesai</th>
                                <th class="p-4 w-24 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <tr v-for="req in requests.data" :key="req.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="p-4 font-mono font-medium text-red-600">{{ req.cbp_number }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-gray-900">{{ req.customer?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-500 flex items-center gap-1 mt-0.5">
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-200">{{ req.customer?.customer_code || '-' }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-gray-700 max-w-xs truncate" :title="req.reason">{{ req.reason || '-' }}</div>
                                </td>
                                <td class="p-4">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border flex items-center gap-1.5 w-max',
                                        req.status === 'pending' ? 'bg-orange-50 text-orange-600 border-orange-200' :
                                        req.status === 'assigned' ? 'bg-blue-50 text-blue-600 border-blue-200' :
                                        req.status === 'completed' ? 'bg-emerald-50 text-emerald-600 border-emerald-200' :
                                        'bg-gray-50 text-gray-600 border-gray-200'
                                    ]">
                                        <span :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            req.status === 'pending' ? 'bg-orange-500 animate-pulse' :
                                            req.status === 'assigned' ? 'bg-blue-500' :
                                            req.status === 'completed' ? 'bg-emerald-500' :
                                            'bg-gray-500'
                                        ]"></span>
                                        {{ req.status === 'pending' ? 'Pending' : req.status === 'assigned' ? 'Ditugaskan' : req.status === 'completed' ? 'Selesai' : 'Dibatalkan' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div v-if="req.technicians && req.technicians.length" class="flex flex-col gap-1">
                                        <div v-for="tech in req.technicians" :key="tech.id" class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center text-[9px] font-bold text-blue-700">
                                                {{ tech.name.charAt(0) }}
                                            </div>
                                            <span class="text-xs font-medium text-gray-700">{{ tech.name }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">Belum ditugaskan</span>
                                </td>
                                <td class="p-4">
                                    <span v-if="req.completed_at" class="text-xs text-gray-600">{{ req.completed_at }}</span>
                                    <span v-else class="text-xs text-gray-400">-</span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="openHistoryModal(req)" class="p-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors" title="Riwayat CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button v-if="req.status === 'pending'" type="button" @click="cancelCbp(req)" class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors" title="Batalkan CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="7" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p>Belum ada data CBP / Cabut Perangkat.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="requests.links?.length > 3" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50/30">
                    <span class="text-sm text-gray-500">Menampilkan {{ requests.from }} - {{ requests.to }} dari {{ requests.total }} data</span>
                    <div class="flex gap-1">
                        <template v-for="link in requests.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" :class="['px-3 py-1.5 text-sm rounded-lg transition-colors', link.active ? 'bg-red-50 text-red-600 font-bold border border-red-200' : 'text-gray-600 hover:bg-gray-100 border border-transparent']" v-html="link.label"></Link>
                            <span v-else class="px-3 py-1.5 text-sm text-gray-400" v-html="link.label"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal (Ultra Modern) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" @click="closeModal"></div>
                <div class="bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.2)] w-full max-w-lg relative z-10 animate-fade-in-up border border-white/40 overflow-hidden flex flex-col max-h-[90vh]">
                    
                    <!-- Decorative background blur -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-rose-400/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 -left-24 w-40 h-40 bg-red-400/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="px-7 py-5 flex items-center justify-between bg-white/60 backdrop-blur-xl border-b border-gray-100 z-10 relative shrink-0">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-lg shadow-rose-500/30 text-white shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Buat Eskalasi CBP</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Penjadwalan cabut perangkat (Stop Permanen)</p>
                            </div>
                        </div>
                        <button @click="closeModal" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2.5 rounded-full transition-all hover:rotate-90 duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="flex flex-col flex-1 overflow-hidden relative z-10">
                        <div class="p-7 overflow-y-auto custom-scrollbar space-y-6">
                            
                            <!-- Notice -->
                            <div class="bg-gradient-to-r from-rose-50 to-red-50/50 border border-rose-200/60 rounded-2xl p-5 flex gap-4 shadow-sm relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                    <svg class="w-24 h-24 text-rose-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.99L19.53 19H4.47L12 5.99zM11 16h2v2h-2zm0-6h2v4h-2z"/></svg>
                                </div>
                                <div class="shrink-0 w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 relative z-10">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="relative z-10">
                                    <h4 class="text-sm font-bold text-rose-900">Peringatan Penting!</h4>
                                    <p class="text-[11px] text-rose-700 mt-1 leading-relaxed">Menyimpan form ini akan mengubah status pelanggan menjadi <strong class="text-rose-900 bg-rose-200/50 px-1 rounded">Stop Permanen</strong>, mengisolir internet, dan menghentikan penagihan bulan berikutnya.</p>
                                </div>
                            </div>

                            <!-- Area Selection -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Filter Wilayah/Area</label>
                                <div class="relative">
                                    <select v-model="selectedArea" class="w-full pl-4 pr-10 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all appearance-none cursor-pointer">
                                        <option value="" disabled>Pilih Area...</option>
                                        <option v-for="area in areas" :key="area.id" :value="area.id">{{ area.name }}</option>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Selection & Info -->
                            <transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="transform opacity-0 -translate-y-4"
                                enter-to-class="transform opacity-100 translate-y-0"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="transform opacity-100 translate-y-0"
                                leave-to-class="transform opacity-0 -translate-y-4"
                            >
                                <div v-if="selectedArea" class="space-y-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Pilih Pelanggan</label>
                                        <div class="relative">
                                            <select v-model="form.customer_id" class="w-full pl-4 pr-10 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all appearance-none cursor-pointer" required>
                                                <option value="" disabled>Pilih Pelanggan Aktif...</option>
                                                <option v-for="cust in filteredCustomers" :key="cust.id" :value="cust.id">{{ cust.customer_code }} - {{ cust.name }}</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Customer Data Display -->
                                    <div v-if="selectedCustomerData" class="relative overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-5 border border-slate-200/60 shadow-sm group hover:border-rose-200 transition-colors">
                                        <h4 class="text-[11px] font-black text-slate-800 mb-3 border-b border-slate-200 pb-2 flex items-center gap-2 tracking-widest uppercase">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Detail Pelanggan
                                        </h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm relative z-10">
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Nama Lengkap</span>
                                                <span class="text-slate-900 font-bold">{{ selectedCustomerData.name }}</span>
                                            </div>
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Paket Aktif</span>
                                                <span class="text-slate-900 font-medium">{{ selectedCustomerData.package?.name || '-' }}</span>
                                            </div>
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">No. HP / WA</span>
                                                <span class="text-slate-900 font-medium font-mono">{{ selectedCustomerData.phone || '-' }}</span>
                                            </div>
                                            <div class="bg-white/60 p-3 rounded-xl border border-white">
                                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Alamat</span>
                                                <span class="text-slate-900 font-medium truncate block" :title="selectedCustomerData.address">{{ selectedCustomerData.address || '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </transition>

                            <!-- Alasan -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-2.5">Alasan Pencabutan / Stop Permanen</label>
                                <textarea v-model="form.reason" rows="3" class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all placeholder-slate-400 resize-none custom-scrollbar" placeholder="Jelaskan secara singkat alasan pelanggan ini berhenti berlangganan..." required></textarea>
                            </div>
                        </div>

                        <div class="px-7 py-5 bg-slate-50/80 backdrop-blur-md border-t border-slate-100 flex justify-end gap-3 shrink-0 rounded-b-3xl">
                            <button type="button" @click="closeModal" class="px-6 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200/50 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="form.processing || !form.customer_id" class="relative group overflow-hidden bg-rose-600 text-white px-7 py-2.5 rounded-xl text-sm font-bold shadow-[0_8px_20px_-6px_rgba(225,29,72,0.5)] hover:bg-rose-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed">
                                <span class="relative z-10 flex items-center gap-2">
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ form.processing ? 'Menyimpan...' : 'Eskalasi CBP' }}
                                </span>
                                <div class="absolute inset-0 h-full w-full bg-gradient-to-r from-rose-500 to-red-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Assign Modal -->
        <Teleport to="body">
            <div v-if="showAssignModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeAssignModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Tugaskan Teknisi</h3>
                        <button @click="closeAssignModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitAssign">
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Pilih Teknisi</label>
                                <select v-model="assignForm.assigned_to" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                                    <option value="" disabled>Pilih teknisi yang bertugas...</option>
                                    <option v-for="tech in technicians" :key="tech.id" :value="tech.id">{{ tech.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeAssignModal" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="assignForm.processing" class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-blue-500/20 hover:bg-blue-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ assignForm.processing ? 'Menyimpan...' : 'Tugaskan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Progress Modal -->
        <Teleport to="body">
            <div v-if="showProgressModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeProgressModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Progress Pencabutan</h3>
                        <button @click="closeProgressModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitProgress">
                        <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Update Status</label>
                                <select v-model="progressForm.status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" required>
                                    <option value="assigned">Masih Dalam Proses</option>
                                    <option value="completed">Selesai Dicabut</option>
                                </select>
                            </div>
                            
                            <div v-if="progressForm.status === 'completed'" class="border-t border-gray-100 pt-4 mt-2">
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider">Perangkat / Material yang Dikembalikan</label>
                                    <button type="button" @click="addMaterial" class="text-[10px] font-bold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah Material
                                    </button>
                                </div>
                                <div v-if="progressForm.materials.length === 0" class="text-[11px] text-gray-400 italic text-center py-2 bg-gray-50 rounded-xl border border-gray-100 border-dashed">
                                    Tidak ada material yang dikembalikan (atau kabel terputus/hilang).
                                </div>
                                <div class="space-y-3">
                                    <div v-for="(mat, idx) in progressForm.materials" :key="idx" class="flex items-start gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                        <div class="flex-1 space-y-2">
                                            <select v-model="mat.material_id" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 focus:border-transparent" required>
                                                <option value="" disabled>Pilih Material/Perangkat...</option>
                                                <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }} (Stok: {{ m.stock }} {{ m.unit }})</option>
                                            </select>
                                            <div class="flex items-center gap-2">
                                                <input type="number" v-model="mat.quantity" step="0.01" min="0.01" class="w-20 px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="Qty" required>
                                                <span class="text-[10px] text-gray-500 font-medium">Qty (Jumlah yang kembali)</span>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeMaterial(idx)" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-2">Otomatis masuk ke stok gudang saat proses selesai.</p>
                            </div>

                            <div class="border-t border-gray-100 pt-4">
                                <label class="block text-[11px] font-bold text-gray-600 uppercase tracking-wider mb-2">Catatan Teknisi (Opsional)</label>
                                <textarea v-model="progressForm.notes" rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" placeholder="Catatan... misal: ONT terbakar, dsb"></textarea>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" @click="closeProgressModal" class="px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="progressForm.processing" class="bg-emerald-600 text-white px-5 py-2 rounded-xl text-sm font-bold shadow-sm shadow-emerald-500/20 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ progressForm.processing ? 'Menyimpan...' : 'Simpan Progress' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <!-- History Modal -->
        <Teleport to="body">
            <div v-if="showHistoryModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeHistoryModal"></div>
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg relative z-10 animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="text-lg font-bold text-gray-900">Riwayat CBP</h3>
                        <button @click="closeHistoryModal" class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar">
                        <div v-if="!selectedHistoryCbp?.audit_logs || selectedHistoryCbp.audit_logs.length === 0" class="text-center py-8 text-gray-500">
                            Tidak ada riwayat ditemukan.
                        </div>
                        <div v-else class="space-y-6 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px md:before:mx-auto md:before:translate-x-0 before:h-full before:w-0.5 before:bg-gradient-to-b before:from-transparent before:via-gray-200 before:to-transparent">
                            <div v-for="log in selectedHistoryCbp.audit_logs" :key="log.id" class="relative flex items-center justify-between md:justify-normal md:odd:flex-row-reverse group is-active">
                                <!-- Icon -->
                                <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-indigo-100 text-indigo-600 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 relative">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <!-- Card -->
                                <div class="w-[calc(100%-4rem)] md:w-[calc(50%-2.5rem)] bg-white p-4 rounded-xl border border-gray-100 shadow-sm relative">
                                    <div class="flex items-center justify-between mb-1">
                                        <div class="font-bold text-gray-900 text-sm">{{ log.action }}</div>
                                        <div class="text-[10px] text-gray-500 font-mono">{{ new Date(log.created_at).toLocaleString('id-ID') }}</div>
                                    </div>
                                    <div class="text-xs text-gray-600 mb-2">{{ log.notes }}</div>
                                    <div class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-50">
                                        <span class="text-[10px] font-medium text-gray-500">Oleh: <span class="text-gray-700 font-bold">{{ log.user_name }}</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Object,
    areas: Array,
    customers: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

function performSearch() {
    router.get('/cbp', {
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
}

// Create Modal
const showModal = ref(false);
const selectedArea = ref('');

const form = useForm({
    customer_id: '',
    reason: '',
});

const filteredCustomers = computed(() => {
    if (!selectedArea.value) return [];
    return props.customers.filter(c => c.area_id === selectedArea.value);
});

const selectedCustomerData = computed(() => {
    if (!form.customer_id) return null;
    return props.customers.find(c => c.id === form.customer_id);
});

function openModal() {
    form.reset();
    selectedArea.value = '';
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    form.reset();
    selectedArea.value = '';
}

function submitCreate() {
    form.post('/cbp', {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}

// History Modal
const showHistoryModal = ref(false);
const selectedHistoryCbp = ref(null);

function openHistoryModal(cbp) {
    selectedHistoryCbp.value = cbp;
    showHistoryModal.value = true;
}

function closeHistoryModal() {
    showHistoryModal.value = false;
    selectedHistoryCbp.value = null;
}

// Cancel
function cancelCbp(cbp) {
    if (confirm('Yakin ingin membatalkan request CBP ini?')) {
        router.post(`/cbp/${cbp.id}/cancel`, {}, {
            preserveScroll: true
        });
    }
}
</script>
