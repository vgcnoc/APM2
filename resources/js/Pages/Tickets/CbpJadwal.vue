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
                                        <button type="button" @click="shareWhatsApp(req)" class="p-1.5 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition-colors" title="Kirim Disposisi ke Kepala Teknisi (WhatsApp)">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        </button>
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

        <!-- Assign Modal (Ultra Modern & Intelligent Dispatch UX) -->
        <Teleport to="body">
            <div v-if="showAssignModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity" @click="closeAssignModal"></div>
                <div class="bg-white rounded-[28px] shadow-[0_25px_70px_-15px_rgba(15,23,42,0.3)] w-full max-w-xl relative z-10 animate-fade-in-up border border-slate-100 overflow-hidden flex flex-col">
                    
                    <!-- Decorative ambient gradient lights -->
                    <div class="absolute -top-24 -right-24 w-56 h-56 bg-gradient-to-br from-blue-500/20 to-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 -left-28 w-56 h-56 bg-gradient-to-tr from-cyan-500/15 to-blue-500/15 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Header -->
                    <div class="px-6 py-5 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex items-center justify-between relative overflow-hidden shrink-0">
                        <div class="absolute -right-10 -bottom-10 w-36 h-36 bg-blue-500/20 rounded-full blur-2xl pointer-events-none"></div>
                        
                        <div class="flex items-center gap-3.5 relative z-10">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/40 text-white ring-2 ring-white/10 shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight flex items-center gap-2">
                                    Tugaskan Tim Teknisi
                                </h3>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-300">
                                    <span class="font-mono font-bold bg-white/15 px-2 py-0.5 rounded-md text-cyan-300">{{ selectedCbp?.cbp_number || '-' }}</span>
                                    <span class="text-slate-400">•</span>
                                    <span class="text-slate-300 font-medium">Penugasan CBP (Cabut Perangkat)</span>
                                </div>
                            </div>
                        </div>

                        <button @click="closeAssignModal" class="relative z-10 text-slate-300 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-xl transition-all duration-200 hover:rotate-90">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitAssign" class="flex flex-col relative z-10 flex-1 overflow-hidden">
                        <div class="p-6 space-y-4 max-h-[68vh] overflow-y-auto custom-scrollbar bg-slate-50/60">
                            
                            <!-- Smart Customer & Target Hardware Dossier Card -->
                            <div v-if="selectedCbp?.customer" class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-3 relative overflow-hidden group">
                                <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-base font-black text-slate-900">{{ selectedCbp.customer.name }}</span>
                                            <span class="font-mono text-xs font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded-md border border-slate-200">
                                                {{ selectedCbp.customer.customer_code || '-' }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 flex items-center gap-1.5 mt-1 font-medium">
                                            <span class="inline-flex items-center gap-1 text-blue-700 font-bold bg-blue-50 px-2 py-0.5 rounded-md border border-blue-100">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                {{ selectedCbp.customer.area_model?.name || selectedCbp.customer.area || '-' }}
                                            </span>
                                            <span class="text-slate-400">•</span>
                                            <span class="truncate max-w-[260px]" :title="selectedCbp.customer.address">{{ selectedCbp.customer.address || '-' }}</span>
                                        </div>
                                    </div>

                                    <!-- Quick WhatsApp Contact Button -->
                                    <a v-if="selectedCbp.customer.phone" 
                                       :href="'https://wa.me/' + formatWaNumber(selectedCbp.customer.phone)" 
                                       target="_blank" 
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-xs font-bold transition-all shadow-sm shrink-0"
                                       title="Hubungi Pelanggan via WhatsApp">
                                        <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                        <span>WA Pelanggan</span>
                                    </a>
                                </div>

                                <!-- ONT Target Extraction Info -->
                                <div v-if="selectedCbp.customer.ont" class="bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-slate-50 border border-blue-200/80 rounded-xl p-3 text-xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-bold text-[10px] tracking-wider text-blue-900 uppercase flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                                            Target Perangkat Cabut (ONT)
                                        </span>
                                        <span v-if="selectedCbp.customer.ont.serial_number" 
                                              @click="copySn(selectedCbp.customer.ont.serial_number)" 
                                              class="text-[10px] text-blue-600 hover:text-blue-800 font-bold cursor-pointer inline-flex items-center gap-1 bg-white px-2 py-0.5 rounded-md border border-blue-200 shadow-2xs">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            {{ copiedSn ? '✓ Tersalin' : 'Salin SN' }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        <div class="bg-white/80 p-2 rounded-lg border border-blue-100/60">
                                            <span class="block text-[9px] font-bold text-slate-400 uppercase">Perangkat</span>
                                            <span class="font-bold text-slate-800 truncate block">{{ selectedCbp.customer.ont.brand || '-' }} {{ selectedCbp.customer.ont.model || '' }}</span>
                                        </div>
                                        <div class="bg-white/80 p-2 rounded-lg border border-blue-100/60">
                                            <span class="block text-[9px] font-bold text-slate-400 uppercase">Serial Number</span>
                                            <span class="font-mono font-bold text-blue-800 truncate block">{{ selectedCbp.customer.ont.serial_number || '-' }}</span>
                                        </div>
                                        <div class="col-span-2 sm:col-span-1 bg-white/80 p-2 rounded-lg border border-blue-100/60">
                                            <span class="block text-[9px] font-bold text-slate-400 uppercase">ODP / Port</span>
                                            <span class="font-bold text-slate-700 truncate block">{{ selectedCbp.customer.ont.odp?.name || selectedCbp.customer.ont.odp || '-' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Alasan Pencabutan Alert -->
                                <div v-if="selectedCbp.reason" class="flex items-start gap-2 bg-amber-50/90 border border-amber-200/80 rounded-xl p-2.5 text-xs text-amber-900">
                                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <div>
                                        <span class="font-bold">Alasan CBP:</span> {{ selectedCbp.reason }}
                                    </div>
                                </div>
                            </div>

                            <!-- Modern Multi-Select Dropdown Section -->
                            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs font-black text-slate-800 uppercase tracking-wider">
                                            PILIH TIM TEKNISI
                                        </label>
                                        <span v-if="assignForm.technicians.length > 0" class="text-[11px] font-bold text-blue-700 bg-blue-50 border border-blue-200 px-2.5 py-0.5 rounded-full animate-fade-in-up">
                                            ✨ {{ assignForm.technicians.length }} Terpilih
                                        </span>
                                        <span v-else class="text-[11px] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full font-medium">
                                            Bisa pilih lebih dari 1
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-xs font-bold">
                                        <button type="button" @click="selectAllTechnicians" class="text-blue-600 hover:text-blue-800 hover:bg-blue-50 px-2 py-1 rounded-lg transition-colors">
                                            Pilih Semua
                                        </button>
                                        <span class="text-slate-300">|</span>
                                        <button type="button" @click="clearAllTechnicians" class="text-slate-500 hover:text-rose-600 hover:bg-rose-50 px-2 py-1 rounded-lg transition-colors">
                                            Reset
                                        </button>
                                    </div>
                                </div>

                                <div class="relative" v-click-outside="() => showDropdown = false">
                                    <!-- Trigger Button -->
                                    <div @click="showDropdown = !showDropdown" 
                                         class="w-full min-h-[54px] px-3.5 py-2.5 bg-slate-50/80 hover:bg-white border-2 border-slate-200 rounded-2xl text-sm cursor-pointer flex items-center justify-between hover:border-blue-400 focus-within:border-blue-500 focus-within:ring-4 focus-within:ring-blue-500/10 transition-all duration-200 shadow-2xs">
                                        <div class="flex flex-wrap gap-1.5 flex-1 items-center">
                                            <template v-if="assignForm.technicians.length">
                                                <div v-for="(tId, idx) in assignForm.technicians" :key="tId" 
                                                     class="inline-flex items-center gap-1.5 bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-900 px-2.5 py-1 rounded-xl text-xs font-bold border border-blue-200/80 shadow-2xs animate-fade-in-up">
                                                    <div class="w-4 h-4 rounded-full bg-gradient-to-tr text-[9px] text-white font-black flex items-center justify-center shrink-0"
                                                         :class="getAvatarGradient(idx)">
                                                        {{ getTechnicianName(tId).charAt(0) }}
                                                    </div>
                                                    <span>{{ getTechnicianName(tId) }}</span>
                                                    <span @click.stop="removeTechnician(tId)" class="hover:bg-blue-200/80 p-0.5 rounded-md cursor-pointer transition-colors text-blue-500 hover:text-blue-900">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </span>
                                                </div>
                                            </template>
                                            <span v-else class="text-slate-400 flex items-center gap-2 font-medium text-xs py-1">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                                Klik untuk memilih anggota teknisi lapangan...
                                            </span>
                                        </div>
                                        <div class="ml-2 pl-2 border-l border-slate-200 flex items-center justify-center">
                                            <div class="w-7 h-7 rounded-full bg-white border border-slate-200 flex items-center justify-center transition-transform duration-300 shadow-2xs" 
                                                 :class="{'rotate-180 bg-blue-50 text-blue-600 border-blue-300': showDropdown}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
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
                                        <div v-if="showDropdown" class="absolute z-50 w-full mt-2 bg-white/98 backdrop-blur-2xl border border-slate-200 rounded-2xl shadow-[0_15px_50px_-10px_rgba(15,23,42,0.2)] max-h-72 overflow-hidden flex flex-col">
                                            <!-- Smart Live Search Bar -->
                                            <div class="p-2.5 border-b border-slate-100 bg-slate-50/70">
                                                <div class="relative">
                                                    <input 
                                                        v-model="techSearch" 
                                                        type="text" 
                                                        placeholder="Ketik untuk mencari nama teknisi..." 
                                                        class="w-full pl-9 pr-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                                                        @click.stop
                                                    />
                                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                    <button v-if="techSearch" @click.stop="techSearch = ''" class="text-slate-400 hover:text-slate-600 absolute right-2.5 top-2.5">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Technician Cards List -->
                                            <div class="p-2 space-y-1.5 overflow-y-auto custom-scrollbar max-h-56">
                                                <div v-for="(tech, idx) in filteredTechnicians" :key="tech.id" 
                                                     @click="toggleTechnician(tech.id)"
                                                     class="flex items-center justify-between p-2.5 rounded-xl cursor-pointer transition-all duration-200 group relative overflow-hidden select-none" 
                                                     :class="isTechnicianSelected(tech.id) ? 'bg-gradient-to-r from-blue-50/90 to-indigo-50/80 border border-blue-200/90 shadow-2xs' : 'hover:bg-slate-50 border border-transparent'">
                                                    
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-black text-white shadow-sm ring-1 ring-white/50 transition-transform group-hover:scale-105 shrink-0 bg-gradient-to-tr"
                                                             :class="getAvatarGradient(idx)">
                                                            {{ tech.name.charAt(0) }}
                                                        </div>
                                                        <div>
                                                            <div class="text-xs font-bold transition-colors leading-tight" 
                                                                 :class="isTechnicianSelected(tech.id) ? 'text-blue-900' : 'text-slate-800 group-hover:text-slate-900'">
                                                                {{ tech.name }}
                                                            </div>
                                                            <div class="flex items-center gap-1.5 mt-0.5 text-[10px] text-slate-500 font-medium">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                                <span>Teknisi Lapangan • Siap</span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Checkbox Indicator -->
                                                    <div class="flex items-center justify-center w-5 h-5 rounded-md border-2 transition-all duration-200 shrink-0" 
                                                         :class="isTechnicianSelected(tech.id) ? 'bg-gradient-to-tr from-blue-600 to-indigo-600 border-blue-600 shadow-sm' : 'bg-white border-slate-300 group-hover:border-blue-400'">
                                                        <svg v-if="isTechnicianSelected(tech.id)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                </div>

                                                <div v-if="!filteredTechnicians || filteredTechnicians.length === 0" class="py-6 text-center text-xs text-slate-400">
                                                    Tidak ada teknisi yang cocok dengan pencarian "{{ techSearch }}".
                                                </div>
                                            </div>
                                        </div>
                                    </transition>
                                </div>
                            </div>

                            <!-- Smart Dispatcher Instructions & Quick Chips -->
                            <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm space-y-2.5">
                                <label class="text-xs font-black text-slate-800 uppercase tracking-wider block">
                                    INSTRUKSI / CATATAN KHUSUS (OPSIONAL)
                                </label>
                                <textarea 
                                    v-model="assignForm.notes" 
                                    rows="2" 
                                    placeholder="Contoh: Bawa tangga 7m, hubungi pelanggan sebelum meluncur..."
                                    class="w-full text-xs rounded-xl border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 placeholder:text-slate-400 transition-all p-3"
                                ></textarea>
                                
                                <!-- Quick Smart Chips to Insert -->
                                <div class="flex items-center gap-1.5 flex-wrap pt-0.5">
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mr-1">Rekomendasi:</span>
                                    <button type="button" 
                                            @click="addInstruction('Bawa tangga & toolkit lengkap')" 
                                            class="text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 rounded-lg px-2 py-0.5 transition-all">
                                        + Tangga & Toolkit
                                    </button>
                                    <button type="button" 
                                            @click="addInstruction('Pastikan ONT & Adaptor diambil lengkap')" 
                                            class="text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 rounded-lg px-2 py-0.5 transition-all">
                                        + ONT & Adaptor
                                    </button>
                                    <button type="button" 
                                            @click="addInstruction('Hubungi pelanggan sebelum ke lokasi')" 
                                            class="text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 rounded-lg px-2 py-0.5 transition-all">
                                        + Hubungi Dulu
                                    </button>
                                    <button type="button" 
                                            @click="addInstruction('Foto kondisi perangkat saat dicabut')" 
                                            class="text-[10px] font-semibold bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 border border-slate-200 rounded-lg px-2 py-0.5 transition-all">
                                        + Foto Bukti
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-white border-t border-slate-100 flex items-center justify-between z-10 shrink-0">
                            <div class="text-xs text-slate-500 font-medium hidden sm:flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" :class="assignForm.technicians.length > 0 ? 'bg-blue-600' : 'bg-slate-300'"></span>
                                <span>{{ assignForm.technicians.length }} personel ditugaskan</span>
                            </div>

                            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                                <button type="button" @click="closeAssignModal" class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                                    Batal
                                </button>
                                <button type="submit" 
                                        :disabled="assignForm.processing || assignForm.technicians.length === 0" 
                                        class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 active:translate-y-0 transition-all disabled:opacity-50 disabled:hover:translate-y-0 disabled:shadow-none disabled:cursor-not-allowed flex items-center gap-2">
                                    <svg v-if="assignForm.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>{{ assignForm.processing ? 'Menyimpan...' : (assignForm.technicians.length > 0 ? `Tugaskan Sekarang (${assignForm.technicians.length})` : 'Tugaskan Teknisi') }}</span>
                                    <svg v-if="!assignForm.processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </button>
                            </div>
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
import { ref, computed } from 'vue';
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

// Click outside directive implementation
const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.body.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.body.removeEventListener('click', el.clickOutsideEvent);
    }
};

// Assign Modal (Smart & Interactive)
const showAssignModal = ref(false);
const showDropdown = ref(false);
const selectedCbp = ref(null);
const techSearch = ref('');
const copiedSn = ref(false);

const assignForm = useForm({
    technicians: [],
    notes: '',
});

const avatarGradients = [
    'from-blue-600 to-indigo-600',
    'from-indigo-600 to-purple-600',
    'from-emerald-600 to-teal-600',
    'from-violet-600 to-fuchsia-600',
    'from-amber-600 to-orange-600',
    'from-cyan-600 to-blue-600',
    'from-rose-600 to-pink-600',
];

function getAvatarGradient(index) {
    return avatarGradients[index % avatarGradients.length];
}

const filteredTechnicians = computed(() => {
    if (!props.technicians) return [];
    if (!techSearch.value.trim()) return props.technicians;
    const q = techSearch.value.toLowerCase();
    return props.technicians.filter(t => t.name.toLowerCase().includes(q));
});

function openAssignModal(req) {
    selectedCbp.value = req;
    assignForm.technicians = req.technicians ? req.technicians.map(t => Number(t.id)) : [];
    assignForm.notes = req.notes || '';
    techSearch.value = '';
    copiedSn.value = false;
    showAssignModal.value = true;
    showDropdown.value = false;
}

function closeAssignModal() {
    showAssignModal.value = false;
    showDropdown.value = false;
    selectedCbp.value = null;
    techSearch.value = '';
    assignForm.reset();
}

function isTechnicianSelected(id) {
    const numId = Number(id);
    return assignForm.technicians.some(t => Number(t) === numId);
}

function toggleTechnician(id) {
    const numId = Number(id);
    const index = assignForm.technicians.findIndex(t => Number(t) === numId);
    if (index === -1) {
        assignForm.technicians.push(numId);
    } else {
        assignForm.technicians.splice(index, 1);
    }
}

function selectAllTechnicians() {
    assignForm.technicians = props.technicians ? props.technicians.map(t => Number(t.id)) : [];
}

function clearAllTechnicians() {
    assignForm.technicians = [];
}

function addInstruction(text) {
    if (!assignForm.notes) {
        assignForm.notes = text;
    } else if (!assignForm.notes.includes(text)) {
        assignForm.notes = assignForm.notes.trim() + '\n• ' + text;
    }
}

function copySn(sn) {
    if (!sn) return;
    navigator.clipboard.writeText(sn);
    copiedSn.value = true;
    setTimeout(() => {
        copiedSn.value = false;
    }, 2000);
}

function formatWaNumber(phone) {
    if (!phone) return '';
    let cleaned = String(phone).replace(/\D/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    return cleaned;
}

function submitAssign() {
    assignForm.post(`/cbp/${selectedCbp.value.id}/assign`, {
        preserveScroll: true,
        onSuccess: () => closeAssignModal(),
    });
}

function getTechnicianName(id) {
    const numId = Number(id);
    const tech = props.technicians?.find(t => Number(t.id) === numId);
    return tech ? tech.name : '';
}

function removeTechnician(id) {
    const numId = Number(id);
    assignForm.technicians = assignForm.technicians.filter(t => Number(t) !== numId);
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

// WhatsApp Dispatch to Lead Technician
function shareWhatsApp(req) {
    const cust = req.customer || {};
    const ont = cust.ont || {};
    let ontInfo = '';
    if (ont.brand || ont.serial_number) {
        ontInfo = `📟 *Perangkat ONT:* ${ont.brand || ''} ${ont.model || ''} (SN: ${ont.serial_number || '-'})` + (ont.odp ? ` | ODP: ${ont.odp.name || ont.odp}` : '') + `\n`;
    }

    const text = `*DISPOSISI CABUT PERANGKAT (CBP)*\n` +
        `--------------------------------------\n` +
        `📌 *No. Tiket:* ${req.cbp_number}\n` +
        `👤 *Pelanggan:* ${cust.name || '-'} (${cust.customer_code || '-'})\n` +
        `📞 *No. HP:* ${cust.phone || '-'}\n` +
        `📍 *Alamat:* ${cust.address || '-'}\n` +
        ontInfo +
        `⚠️ *Alasan:* ${req.reason || '-'}\n` +
        (req.notes ? `📝 *Catatan:* ${req.notes}\n` : '') +
        `--------------------------------------\n` +
        `Kepada: *Kepala Teknisi*\n` +
        `Mohon jadwalkan tim teknisi untuk penarikan/pencabutan perangkat ONT.\n` +
        `Jadwal sistem: ${window.location.origin}/cbp/jadwal`;

    const url = `https://wa.me/?text=${encodeURIComponent(text)}`;
    window.open(url, '_blank');
}
</script>
