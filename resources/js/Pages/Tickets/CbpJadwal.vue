<template>
    <AppLayout title="Jadwal CBP" subtitle="Manajemen Jadwal Pencabutan Perangkat">
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
                </div>
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
                                    <div v-if="req.notes" class="text-[10px] text-gray-400 mt-1 border-t border-gray-100 pt-1" :title="req.notes">
                                        Note: <span class="truncate">{{ req.notes }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                        :class="{
                                            'bg-orange-50 text-orange-600 border-orange-200': req.status === 'pending',
                                            'bg-blue-50 text-blue-600 border-blue-200': req.status === 'assigned',
                                            'bg-emerald-50 text-emerald-600 border-emerald-200': req.status === 'completed',
                                            'bg-gray-50 text-gray-600 border-gray-200': req.status === 'canceled'
                                        }">
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
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="req.status === 'pending'" @click="openAssignModal(req)" class="p-1.5 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition-colors" title="Tugaskan Teknisi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        </button>
                                        <button v-if="req.status === 'assigned'" @click="openAssignModal(req)" class="p-1.5 text-orange-600 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg transition-colors" title="Ubah Teknisi">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button type="button" @click="openHistoryModal(req)" class="p-1.5 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors" title="Riwayat CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        <button v-if="req.status === 'pending'" @click="cancelCbp(req)" class="p-1.5 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-lg transition-colors" title="Batalkan CBP">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!requests.data.length">
                                <td colspan="6" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p>Belum ada Jadwal CBP.</p>
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

        <!-- Assign Modal (Ultra Modern) -->
        <Teleport to="body">
            <div v-if="showAssignModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
                <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" @click="closeAssignModal"></div>
                <div class="bg-white rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.2)] w-full max-w-md relative z-10 animate-fade-in-up border border-white/40 overflow-hidden flex flex-col">
                    
                    <!-- Decorative background blur -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-400/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 -left-24 w-40 h-40 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="px-7 py-5 flex items-center justify-between bg-white/60 backdrop-blur-xl border-b border-gray-100 z-10 relative">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30 text-white shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-extrabold text-slate-800 tracking-tight">Tugaskan Teknisi</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Penugasan tim lapangan (CBP)</p>
                            </div>
                        </div>
                        <button @click="closeAssignModal" class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-2.5 rounded-full transition-all hover:rotate-90 duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitAssign" class="flex flex-col relative z-10">
                        <div class="p-7 space-y-6 max-h-[65vh] overflow-y-auto custom-scrollbar">
                            
                            <!-- Customer Info Card -->
                            <div v-if="selectedCbp?.customer" class="relative overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-5 border border-slate-200/60 shadow-sm group hover:border-blue-200 transition-colors">
                                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                </div>
                                <h4 class="text-[11px] font-black text-slate-800 mb-3 border-b border-slate-200 pb-2 flex items-center gap-2 tracking-widest uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Data Pelanggan
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm relative z-10">
                                    <div class="bg-white/60 p-3 rounded-xl border border-white">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Nama</span>
                                        <span class="text-slate-900 font-bold">{{ selectedCbp.customer.name }}</span>
                                    </div>
                                    <div class="bg-white/60 p-3 rounded-xl border border-white">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">ID Pelanggan</span>
                                        <span class="text-slate-900 font-medium font-mono">{{ selectedCbp.customer.customer_code || '-' }}</span>
                                    </div>
                                    <div class="sm:col-span-2 bg-white/60 p-3 rounded-xl border border-white">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Area / Wilayah</span>
                                        <span class="text-slate-900 font-medium">{{ selectedCbp.customer.area_model?.name || selectedCbp.customer.area || '-' }}</span>
                                    </div>
                                    <div class="sm:col-span-2 bg-white/60 p-3 rounded-xl border border-white">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Alamat</span>
                                        <span class="text-slate-900 font-medium leading-relaxed" :title="selectedCbp.customer.address">{{ selectedCbp.customer.address || '-' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Modern Multi-Select Dropdown -->
                            <div>
                                <label class="flex items-center justify-between text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-2.5">
                                    <span>Pilih Teknisi</span>
                                    <span class="text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full lowercase font-medium tracking-normal">Bisa pilih lebih dari 1</span>
                                </label>
                                <div class="relative">
                                    <!-- Trigger Button -->
                                    <div @click="showDropdown = !showDropdown" class="w-full min-h-[56px] px-4 py-3 bg-white border-2 border-slate-200 rounded-2xl text-sm cursor-pointer flex items-center justify-between hover:border-blue-400 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10 transition-all duration-300 shadow-sm">
                                        <div class="flex flex-wrap gap-2 flex-1">
                                            <template v-if="assignForm.technicians.length">
                                                <div v-for="tId in assignForm.technicians" :key="tId" class="inline-flex items-center gap-1.5 bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-800 px-3 py-1.5 rounded-xl text-xs font-bold border border-blue-200/60 shadow-sm animate-fade-in-up">
                                                    <div class="w-4 h-4 rounded-full bg-blue-200 flex items-center justify-center text-[8px] text-blue-700">
                                                        {{ getTechnicianName(tId).charAt(0) }}
                                                    </div>
                                                    {{ getTechnicianName(tId) }}
                                                    <span @click.stop="removeTechnician(tId)" class="hover:bg-blue-200/80 p-0.5 rounded-md cursor-pointer transition-colors ml-0.5 text-blue-500 hover:text-blue-900">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </span>
                                                </div>
                                            </template>
                                            <span v-else class="text-slate-400 flex items-center gap-2 font-medium">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                                Silakan pilih tim teknisi...
                                            </span>
                                        </div>
                                        <div class="ml-2 pl-2 border-l border-slate-200 flex items-center justify-center">
                                            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center transition-transform duration-300" :class="{'rotate-180 bg-blue-50 text-blue-600': showDropdown}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Dropdown List with Animation -->
                                    <transition
                                        enter-active-class="transition duration-200 ease-out"
                                        enter-from-class="transform scale-95 opacity-0 -translate-y-2"
                                        enter-to-class="transform scale-100 opacity-100 translate-y-0"
                                        leave-active-class="transition duration-150 ease-in"
                                        leave-from-class="transform scale-100 opacity-100 translate-y-0"
                                        leave-to-class="transform scale-95 opacity-0 -translate-y-2"
                                    >
                                        <div v-if="showDropdown" class="absolute z-50 w-full mt-2 bg-white/95 backdrop-blur-xl border border-slate-200 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] max-h-64 overflow-y-auto custom-scrollbar p-2">
                                            <div class="space-y-1">
                                                <label v-for="tech in technicians" :key="tech.id" class="flex items-center p-3 rounded-xl cursor-pointer transition-all duration-200 group relative overflow-hidden" :class="assignForm.technicians.includes(tech.id) ? 'bg-blue-50/50' : 'hover:bg-slate-50'">
                                                    <div class="absolute inset-0 bg-blue-100/50 transform scale-x-0 origin-left transition-transform duration-300" :class="{'scale-x-100': assignForm.technicians.includes(tech.id)}"></div>
                                                    
                                                    <div class="relative z-10 flex items-center w-full">
                                                        <div class="flex items-center justify-center w-5 h-5 rounded-md border-2 transition-colors duration-200" :class="assignForm.technicians.includes(tech.id) ? 'bg-blue-600 border-blue-600' : 'bg-white border-slate-300 group-hover:border-blue-400'">
                                                            <svg v-if="assignForm.technicians.includes(tech.id)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                        <div class="ml-3 flex items-center gap-3">
                                                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 transition-colors" :class="{'bg-white text-blue-700 shadow-sm': assignForm.technicians.includes(tech.id)}">
                                                                {{ tech.name.charAt(0) }}
                                                            </div>
                                                            <span class="text-sm font-bold transition-colors" :class="assignForm.technicians.includes(tech.id) ? 'text-blue-900' : 'text-slate-700 group-hover:text-slate-900'">{{ tech.name }}</span>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>
                        </div>

                        <div class="px-7 py-5 bg-slate-50/80 backdrop-blur-md border-t border-slate-100 flex justify-end gap-3 z-10 rounded-b-3xl">
                            <button type="button" @click="closeAssignModal" class="px-6 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-200/50 rounded-xl transition-colors">Batal</button>
                            <button type="submit" :disabled="assignForm.processing || assignForm.technicians.length === 0" class="relative group overflow-hidden bg-blue-600 text-white px-7 py-2.5 rounded-xl text-sm font-bold shadow-[0_8px_20px_-6px_rgba(37,99,235,0.5)] hover:bg-blue-700 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 disabled:cursor-not-allowed">
                                <span class="relative z-10 flex items-center gap-2">
                                    <svg v-if="assignForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ assignForm.processing ? 'Menyimpan...' : 'Tugaskan Teknisi' }}
                                </span>
                                <div class="absolute inset-0 h-full w-full bg-gradient-to-r from-blue-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

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
                                <div class="flex items-center justify-center w-10 h-10 rounded-full border-4 border-white bg-indigo-100 text-indigo-600 shadow shrink-0 md:order-1 md:group-odd:-translate-x-1/2 md:group-even:translate-x-1/2 z-10 relative">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
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
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    requests: Object,
    technicians: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');

function performSearch() {
    router.get('/cbp/jadwal', {
        search: search.value,
    }, { preserveState: true, preserveScroll: true });
}

// Assign Modal
const showAssignModal = ref(false);
const showDropdown = ref(false);
const selectedCbp = ref(null);
const assignForm = useForm({
    technicians: [],
});

function openAssignModal(req) {
    selectedCbp.value = req;
    assignForm.technicians = req.technicians ? req.technicians.map(t => t.id) : [];
    showAssignModal.value = true;
    showDropdown.value = false;
}

function closeAssignModal() {
    showAssignModal.value = false;
    showDropdown.value = false;
    selectedCbp.value = null;
    assignForm.reset();
}

function submitAssign() {
    assignForm.post(`/cbp/${selectedCbp.value.id}/assign`, {
        preserveScroll: true,
        onSuccess: () => closeAssignModal(),
    });
}

function getTechnicianName(id) {
    const tech = props.technicians.find(t => t.id === id);
    return tech ? tech.name : '';
}

function removeTechnician(id) {
    assignForm.technicians = assignForm.technicians.filter(t => t !== id);
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
