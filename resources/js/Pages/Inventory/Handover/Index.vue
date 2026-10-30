<template>
    <AppLayout title="Serah Terima & Permintaan Material" subtitle="Alur otomatis serah terima pengembalian gudang dan approval permintaan material / ganti ONT">
        <div class="space-y-6 pb-20">

            <!-- ── 0. BREADCRUMB & ALUR CEPAT ─────────────────────────── -->
            <div class="bg-gradient-to-r from-amber-500/10 via-indigo-500/10 to-emerald-500/10 border border-amber-200/60 p-4 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="text-2xl shrink-0 mt-0.5">💡</span>
                    <div class="text-xs">
                        <p class="font-extrabold text-gray-900 text-sm">Alur Otomatis Serah Terima & Kondisi Barang</p>
                        <p class="text-gray-600 mt-0.5 leading-relaxed">
                            Sisa material dari pelaksanaan <b>Instalasi PSB, PGS, PGK, PGJ, Rollout, dan CBP</b> otomatis masuk ke antrean ini. 
                            Petugas Gudang / Ka. Teknisi cukup klik tombol <span class="font-bold text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded">✅ Terima & Cek Kondisi</span> untuk memilih kondisi fisik (<b>🟢 Layak Pakai</b>, <b>🟡 Tidak Layak</b>, atau <b>🔴 Rusak</b>) dan memasukkan stok ke gudang.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── 1. STATISTIC CARDS ─────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div @click="switchTab('returns')" 
                    class="bg-white p-5 rounded-2xl border transition-all cursor-pointer shadow-sm hover:shadow-md"
                    :class="currentTab === 'returns' ? 'border-amber-300 ring-2 ring-amber-200 bg-amber-50/20' : 'border-gray-100 hover:border-gray-200'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Menunggu Serah Terima</p>
                            <h3 class="text-3xl font-black text-gray-900 mt-1">{{ summary.pending_returns }}</h3>
                            <p class="text-xs text-amber-600 font-medium mt-0.5">Pengembalian perlu diterima Gudang/Ka.Teknisi</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xl shadow-sm">
                            📥
                        </div>
                    </div>
                </div>

                <div @click="switchTab('requests')" 
                    class="bg-white p-5 rounded-2xl border transition-all cursor-pointer shadow-sm hover:shadow-md"
                    :class="currentTab === 'requests' ? 'border-indigo-300 ring-2 ring-indigo-200 bg-indigo-50/20' : 'border-gray-100 hover:border-gray-200'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-indigo-700 uppercase tracking-wider">Permintaan Menunggu Approval</p>
                            <h3 class="text-3xl font-black text-gray-900 mt-1">{{ summary.pending_requests }}</h3>
                            <p class="text-xs text-indigo-600 font-medium mt-0.5">Tambahan material & ganti ONT (Ka. Teknisi)</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xl shadow-sm">
                            ⚡
                        </div>
                    </div>
                </div>

                <div @click="switchTab('history')" 
                    class="bg-white p-5 rounded-2xl border transition-all cursor-pointer shadow-sm hover:shadow-md"
                    :class="currentTab === 'history' ? 'border-emerald-300 ring-2 ring-emerald-200 bg-emerald-50/20' : 'border-gray-100 hover:border-gray-200'">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Total Riwayat Selesai</p>
                            <h3 class="text-3xl font-black text-emerald-600 mt-1">{{ summary.total_received }}</h3>
                            <p class="text-xs text-emerald-600 font-medium mt-0.5">{{ summary.received_today }} barang diterima hari ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xl shadow-sm">
                            ✅
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── 2. TABS & TOP ACTIONS ─────────────────────────────── -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <!-- Nav Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-2 md:pb-0">
                    <button @click="switchTab('returns')" 
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap"
                        :class="currentTab === 'returns' ? 'bg-amber-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                        <span>📥</span> Serah Terima Pengembalian Gudang
                        <span v-if="summary.pending_returns > 0" class="px-2 py-0.5 rounded-full text-[11px] font-black"
                            :class="currentTab === 'returns' ? 'bg-white text-amber-700' : 'bg-amber-100 text-amber-800'">
                            {{ summary.pending_returns }}
                        </span>
                    </button>

                    <button @click="switchTab('requests')" 
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap"
                        :class="currentTab === 'requests' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                        <span>⚡</span> Permintaan Material & Ganti ONT
                        <span v-if="summary.pending_requests > 0" class="px-2 py-0.5 rounded-full text-[11px] font-black"
                            :class="currentTab === 'requests' ? 'bg-white text-indigo-700' : 'bg-indigo-100 text-indigo-800'">
                            {{ summary.pending_requests }}
                        </span>
                    </button>

                    <button @click="switchTab('history')" 
                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center gap-2 transition-all whitespace-nowrap"
                        :class="currentTab === 'history' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'">
                        <span>📜</span> Riwayat Selesai
                        <span v-if="summary.total_received > 0" class="px-2 py-0.5 rounded-full text-[11px] font-black"
                            :class="currentTab === 'history' ? 'bg-white text-emerald-700' : 'bg-emerald-100 text-emerald-800'">
                            {{ summary.total_received }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ── 3. FILTERS BAR ─────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <div>
                    <input type="text" v-model="filterSearch" @keyup.enter="applyFilters" placeholder="Cari No Trx, Teknisi, No Tiket..." 
                        class="w-full text-xs py-2 px-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <select v-model="filterSource" @change="applyFilters" class="w-full text-xs py-2 px-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="">Semua Sumber Tugas</option>
                        <option value="instalasi">🏠 Instalasi PSB</option>
                        <option value="pgs">⚡ PGS (Gangguan Saluran)</option>
                        <option value="pgk">🛠️ PGK (Gangguan Khusus)</option>
                        <option value="pgj">🌐 PGJ (Gangguan Jalur)</option>
                        <option value="rollout_odc_odp">🏗️ Rollout ODC & ODP</option>
                        <option value="cbp">✂️ Cabut Perangkat (CBP)</option>
                        <option value="manual">📝 Manual / Lapangan</option>
                    </select>
                </div>
                <div>
                    <select v-model="filterCondition" @change="applyFilters" class="w-full text-xs py-2 px-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="">Semua Kondisi Barang</option>
                        <option value="Layak Pakai">🟢 Layak Pakai</option>
                        <option value="Tidak Layak">🟡 Tidak Layak</option>
                        <option value="Rusak">🔴 Rusak</option>
                    </select>
                </div>
                <div>
                    <select v-model="filterArea" @change="applyFilters" class="w-full text-xs py-2 px-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 font-medium">
                        <option value="">Semua Area Gudang</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="applyFilters" class="flex-1 py-2 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-xl transition">
                        Terapkan
                    </button>
                    <button @click="resetFilters" class="py-2 px-3 text-xs font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                        Reset
                    </button>
                </div>
            </div>

            <!-- ── 4. MAIN DATA TABLE ─────────────────────────────────── -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/70 border-b border-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                <th class="py-3.5 px-4">No. Transaksi & Tgl</th>
                                <th class="py-3.5 px-4">Sumber Operasional</th>
                                <th class="py-3.5 px-4">Teknisi Lapangan</th>
                                <th class="py-3.5 px-4">Rincian Material & Kondisi</th>
                                <th class="py-3.5 px-4">Status & Verifikator</th>
                                <th class="py-3.5 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <tr v-for="trx in transactions.data" :key="trx.id" class="hover:bg-slate-50/50 transition">
                                <!-- No Trx -->
                                <td class="py-4 px-4">
                                    <div class="font-bold text-gray-900">{{ trx.transaction_number }}</div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">{{ formatDate(trx.date || trx.created_at) }}</div>
                                    <div v-if="trx.areaModel" class="inline-flex items-center gap-1 text-[10px] text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded mt-1 font-semibold">
                                        📍 {{ trx.areaModel.name }}
                                    </div>
                                </td>

                                <!-- Sumber Operasional -->
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                                            :class="getSourceBadgeClass(trx.source_type)">
                                            {{ getSourceLabel(trx.source_type) }}
                                        </span>
                                    </div>
                                    <div class="font-bold text-gray-900 text-xs">
                                        {{ trx.source_reference || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 truncate max-w-xs mt-0.5" :title="trx.purpose">
                                        {{ trx.purpose }}
                                    </div>
                                </td>

                                <!-- Teknisi -->
                                <td class="py-4 px-4">
                                    <div class="font-bold text-gray-900">{{ trx.technician_name || trx.user?.name || '-' }}</div>
                                    <div class="text-[11px] text-gray-400">Pengaju</div>
                                </td>

                                <!-- Rincian Material & Kondisi -->
                                <td class="py-4 px-4">
                                    <div class="space-y-1.5 max-w-md">
                                        <!-- ONT Replacement Info if any -->
                                        <div v-if="trx.request_type === 'ont_replacement'" class="p-2 rounded-xl bg-purple-50 border border-purple-200 text-purple-900 mb-2">
                                            <div class="font-black text-[11px] flex items-center gap-1">
                                                <span>🔄</span> Pergantian ONT
                                            </div>
                                            <div v-if="trx.old_ont" class="text-[11px] mt-0.5">
                                                ONT Lama: <b>{{ trx.old_ont.serial_number }}</b> ({{ trx.old_ont.brand }})
                                            </div>
                                            <div v-if="trx.replacement_ont" class="text-[11px] text-emerald-700 font-bold">
                                                ONT Baru: {{ trx.replacement_ont.serial_number }} ({{ trx.replacement_ont.brand }})
                                            </div>
                                        </div>

                                        <!-- Items List -->
                                        <div v-for="item in trx.items" :key="item.id" class="flex items-center justify-between gap-2 p-1.5 bg-gray-50 rounded-lg border border-gray-100">
                                            <div class="min-w-0">
                                                <span class="font-bold text-gray-800">{{ item.material?.name || item.material_name || 'Material' }}</span>
                                                <span class="text-gray-500 ml-1">({{ formatQty(item.quantity) }} {{ item.unit || item.material?.unit }})</span>
                                            </div>
                                            <div class="shrink-0">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                    :class="getConditionBadgeClass(item.condition)">
                                                    {{ item.condition || 'Layak Pakai' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="trx.notes" class="text-[11px] text-gray-500 italic mt-1">
                                        Catatan: "{{ trx.notes }}"
                                    </div>
                                </td>

                                <!-- Status & Verifikator -->
                                <td class="py-4 px-4">
                                    <div class="mb-1">
                                        <!-- Status Handover -->
                                        <template v-if="trx.request_type === 'return_handover'">
                                            <span v-if="trx.handover_status === 'pending_handover'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                                ⏳ Menunggu Diterima Gudang
                                            </span>
                                            <span v-else-if="trx.handover_status === 'received'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                ✅ Diterima di Gudang
                                            </span>
                                            <span v-else-if="trx.handover_status === 'rejected'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                                                ❌ Ditolak / Revisi
                                            </span>
                                        </template>

                                        <!-- Status Request -->
                                        <template v-else>
                                            <span v-if="trx.status === 'pending'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-800 border border-indigo-200 animate-pulse">
                                                ⏳ Menunggu Ka. Teknisi
                                            </span>
                                            <span v-else-if="trx.status === 'approved'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                ✅ Disetujui Ka. Teknisi
                                            </span>
                                            <span v-else-if="trx.status === 'rejected'" class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-200">
                                                ❌ Ditolak Ka. Teknisi
                                            </span>
                                        </template>
                                    </div>

                                    <div v-if="trx.receiver" class="text-[11px] text-gray-700 mt-1">
                                        Oleh: <b>{{ trx.receiver.name }}</b>
                                        <div class="text-[10px] text-gray-400">{{ formatDateTime(trx.received_at) }}</div>
                                    </div>
                                    <div v-if="trx.received_notes" class="text-[10px] text-amber-800 mt-0.5">
                                        Ket: {{ trx.received_notes }}
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                        <!-- Aksi Serah Terima (Pending Handover) -->
                                        <template v-if="trx.request_type === 'return_handover' && trx.handover_status === 'pending_handover'">
                                            <button @click="openReceiveModal(trx)" class="px-3.5 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm transition active:scale-95 flex items-center gap-1.5">
                                                <span>✅</span> Terima & Cek Kondisi
                                            </button>
                                            <button @click="openRejectModal(trx, 'handover')" class="px-2.5 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl border border-rose-200 transition">
                                                Tolak
                                            </button>
                                        </template>

                                        <!-- Aksi Approval Request (Pending Request) -->
                                        <template v-else-if="trx.request_type !== 'return_handover' && trx.status === 'pending'">
                                            <button @click="approveRequest(trx)" class="px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition active:scale-95 flex items-center gap-1">
                                                <span>✅</span> Setujui & Keluarkan
                                            </button>
                                            <button @click="openRejectModal(trx, 'request')" class="px-2.5 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl border border-rose-200 transition">
                                                Tolak
                                            </button>
                                        </template>

                                        <!-- View / Sudah Selesai -->
                                        <template v-else>
                                            <span class="text-xs text-gray-400 italic">Selesai</span>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="transactions.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    <div class="text-3xl mb-2">📦</div>
                                    <p class="font-bold">Tidak ada data serah terima / permintaan</p>
                                    <p class="text-xs text-gray-500 mt-1">Gunakan tombol di atas untuk mengajukan serah terima atau request material baru.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="transactions.links && transactions.data.length > 0" class="p-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">Menampilkan {{ transactions.from }} - {{ transactions.to }} dari {{ transactions.total }} data</p>
                    <div class="flex gap-1">
                        <Link v-for="(link, i) in transactions.links" :key="i" :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
                            :class="[
                                link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                !link.url && 'opacity-50 cursor-not-allowed'
                            ]"
                            v-html="link.label" />
                    </div>
                </div>
            </div>

            <!-- ── MODAL 1: TERIMA SERAH TERIMA FISIK KE GUDANG ───────── -->
            <div v-if="showReceiveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="showReceiveModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-black text-gray-900 flex items-center gap-2">
                                <span class="p-1.5 rounded-xl bg-emerald-100 text-emerald-700">📥</span>
                                Konfirmasi Penerimaan Serah Terima Gudang
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">Verifikasi fisik barang, jumlah, dan kondisi sebelum masuk stok gudang</p>
                        </div>
                        <button @click="showReceiveModal = false" class="text-gray-400 hover:text-gray-600 p-2">✕</button>
                    </div>

                    <form @submit.prevent="submitReceiveForm" class="p-6 space-y-4">
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between text-xs">
                            <div>
                                <span class="text-gray-500 font-bold uppercase tracking-wider block">No. Transaksi</span>
                                <span class="text-sm font-black text-gray-900">{{ activeTrx?.transaction_number }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-500 font-bold uppercase tracking-wider block">Pengembali</span>
                                <span class="font-bold text-indigo-700">{{ activeTrx?.technician_name }}</span>
                            </div>
                        </div>

                        <!-- Verifikasi Per Item -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2 uppercase">Daftar Barang & Kondisi Fisik <span class="text-red-500">*</span></label>
                            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                                <div v-for="(it, idx) in receiveForm.items" :key="it.id" class="p-3 rounded-2xl border border-gray-200 bg-white shadow-sm space-y-2">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-black text-gray-900">{{ it.material_name }}</span>
                                        <span class="text-gray-500">Satuan: {{ it.unit }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Jumlah Fisik Diterima</label>
                                            <input type="number" step="0.01" min="0.01" v-model="it.quantity" required
                                                class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 font-bold">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Pilih Status Kondisi Barang</label>
                                            <div class="grid grid-cols-3 gap-1">
                                                <button type="button" @click="it.condition = 'Layak Pakai'"
                                                    :class="it.condition === 'Layak Pakai' ? 'bg-emerald-600 text-white font-black shadow-sm ring-2 ring-emerald-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                                    class="py-2 px-1 rounded-xl text-[10px] font-bold text-center transition">
                                                    🟢 Layak
                                                </button>
                                                <button type="button" @click="it.condition = 'Tidak Layak'"
                                                    :class="it.condition === 'Tidak Layak' ? 'bg-amber-600 text-white font-black shadow-sm ring-2 ring-amber-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                                    class="py-2 px-1 rounded-xl text-[10px] font-bold text-center transition">
                                                    🟡 Tdk Layak
                                                </button>
                                                <button type="button" @click="it.condition = 'Rusak'"
                                                    :class="it.condition === 'Rusak' ? 'bg-rose-600 text-white font-black shadow-sm ring-2 ring-rose-300' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                                    class="py-2 px-1 rounded-xl text-[10px] font-bold text-center transition">
                                                    🔴 Rusak
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Penerimaan -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Catatan Verifikasi / Berita Acara</label>
                            <input type="text" v-model="receiveForm.notes" placeholder="Cth: Diterima lengkap sesuai fisik dan masuk rak A2" 
                                class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                            <button type="button" @click="showReceiveModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl">Batal</button>
                            <button type="submit" :disabled="receiveForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-sm">
                                Konfirmasi & Masukkan Stok
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── MODAL 2: AJUKAN PENGEMBALIAN BARU ───────────────────── -->
            <div v-if="showReturnModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="showReturnModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="text-base font-black text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-xl bg-amber-100 text-amber-700">📥</span>
                            Ajukan Serah Terima Pengembalian Material
                        </h3>
                        <button @click="showReturnModal = false" class="text-gray-400 hover:text-gray-600 p-2">✕</button>
                    </div>

                    <form @submit.prevent="submitReturnForm" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Sumber Operasional <span class="text-red-500">*</span></label>
                                <select v-model="returnForm.source_type" required class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl font-bold">
                                    <option value="installation">🏠 Instalasi Pelanggan</option>
                                    <option value="pgs">⚡ PGS Sigap</option>
                                    <option value="pgk">🛠️ PGK Komplain</option>
                                    <option value="pgj">🌐 PGJ Jalur</option>
                                    <option value="rollout_odc">🏗️ Rollout ODC</option>
                                    <option value="rollout_odp">🏗️ Rollout ODP</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">No. Tiket / Pelanggan <span class="text-red-500">*</span></label>
                                <input type="text" v-model="returnForm.source_reference" required placeholder="Cth: TKT-0012 / Budi" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl font-bold">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Nama Teknisi <span class="text-red-500">*</span></label>
                                <input type="text" v-model="returnForm.technician_name" required placeholder="Nama teknisi pengembali" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Area Gudang Tujuan</label>
                                <select v-model="returnForm.area_id" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl">
                                    <option value="">Gudang Utama (Pusat)</option>
                                    <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Items to return -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-gray-700 uppercase">Material yang Dikembalikan <span class="text-red-500">*</span></label>
                                <button type="button" @click="addReturnItem" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">+ Tambah Barang</button>
                            </div>

                            <div class="space-y-2">
                                <div v-for="(it, idx) in returnForm.items" :key="idx" class="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <select v-model="it.material_id" required class="flex-1 text-xs px-2.5 py-1.5 border border-gray-200 rounded-lg font-bold">
                                            <option value="" disabled>-- Pilih Material --</option>
                                            <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }} ({{ m.unit }})</option>
                                        </select>
                                        <button v-if="returnForm.items.length > 1" type="button" @click="returnForm.items.splice(idx, 1)" class="text-rose-500 font-bold p-1">✕</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" step="0.01" min="0.01" v-model="it.quantity" placeholder="Qty" required class="text-xs px-2.5 py-1.5 border border-gray-200 rounded-lg font-bold">
                                        <select v-model="it.condition" required class="text-xs px-2.5 py-1.5 border border-gray-200 rounded-lg font-bold">
                                            <option value="Layak Pakai">🟢 Layak Pakai</option>
                                            <option value="Tidak Layak">🟡 Tidak Layak</option>
                                            <option value="Rusak">🔴 Rusak</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Catatan / Alasan Retur</label>
                            <input type="text" v-model="returnForm.notes" placeholder="Cth: Sisa penarikan kabel dropcore / ONT lama dicabut rusak" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl">
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                            <button type="button" @click="showReturnModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl">Batal</button>
                            <button type="submit" :disabled="returnForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-sm">
                                Ajukan Serah Terima
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── MODAL 3: PERMINTAAN MATERIAL / GANTI ONT ────────────── -->
            <div v-if="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="showRequestModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="text-base font-black text-gray-900 flex items-center gap-2">
                            <span class="p-1.5 rounded-xl bg-indigo-100 text-indigo-700">⚡</span>
                            Permintaan Material / Ganti ONT ke Ka. Teknisi
                        </h3>
                        <button @click="showRequestModal = false" class="text-gray-400 hover:text-gray-600 p-2">✕</button>
                    </div>

                    <form @submit.prevent="submitRequestForm" class="p-6 space-y-4">
                        <!-- Tipe Request: Material Tambahan atau Pergantian ONT -->
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition"
                                :class="requestForm.request_type === 'additional_material' ? 'bg-indigo-50 border-indigo-300 text-indigo-900 font-bold' : 'bg-white border-gray-200 text-gray-600'">
                                <input type="radio" value="additional_material" v-model="requestForm.request_type" class="text-indigo-600">
                                <span class="text-xs">📦 Material Tambahan</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 rounded-xl border cursor-pointer transition"
                                :class="requestForm.request_type === 'ont_replacement' ? 'bg-purple-50 border-purple-300 text-purple-900 font-bold' : 'bg-white border-gray-200 text-gray-600'">
                                <input type="radio" value="ont_replacement" v-model="requestForm.request_type" class="text-purple-600">
                                <span class="text-xs">🔄 Pergantian ONT</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Sumber Operasional <span class="text-red-500">*</span></label>
                                <select v-model="requestForm.source_type" required class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl font-bold">
                                    <option value="installation">🏠 Instalasi Pelanggan</option>
                                    <option value="pgs">⚡ PGS Sigap</option>
                                    <option value="pgk">🛠️ PGK Komplain</option>
                                    <option value="pgj">🌐 PGJ Jalur</option>
                                    <option value="rollout_odc">🏗️ Rollout ODC</option>
                                    <option value="rollout_odp">🏗️ Rollout ODP</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">No. Tiket / Pelanggan <span class="text-red-500">*</span></label>
                                <input type="text" v-model="requestForm.source_reference" required placeholder="Cth: TKT-0012 / Pelanggan" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl font-bold">
                            </div>
                        </div>

                        <!-- Khusus Pergantian ONT -->
                        <div v-if="requestForm.request_type === 'ont_replacement'" class="space-y-3 p-3 bg-purple-50/50 border border-purple-200 rounded-2xl">
                            <div>
                                <label class="block text-xs font-bold text-purple-900 mb-1 uppercase">Pilih ONT Baru dari Gudang <span class="text-red-500">*</span></label>
                                <select v-model="requestForm.replacement_ont_id" required class="w-full text-xs px-3 py-2 border border-purple-200 rounded-xl font-bold">
                                    <option value="" disabled>-- Pilih Unit ONT Gudang --</option>
                                    <option v-for="o in availableOnts" :key="o.id" :value="o.id">
                                        SN: {{ o.serial_number }} ({{ o.brand }} {{ o.model }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Khusus Material Tambahan -->
                        <div v-else>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-gray-700 uppercase">Material yang Diminta <span class="text-red-500">*</span></label>
                                <button type="button" @click="addRequestItem" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">+ Tambah Barang</button>
                            </div>

                            <div class="space-y-2">
                                <div v-for="(it, idx) in requestForm.items" :key="idx" class="flex items-center gap-2 p-2.5 bg-gray-50 border border-gray-200 rounded-xl">
                                    <select v-model="it.material_id" required class="flex-1 text-xs px-2.5 py-1.5 border border-gray-200 rounded-lg font-bold">
                                        <option value="" disabled>-- Pilih Material --</option>
                                        <option v-for="m in materials" :key="m.id" :value="m.id">{{ m.name }} (Stok: {{ m.stock }} {{ m.unit }})</option>
                                    </select>
                                    <input type="number" step="0.01" min="0.01" v-model="it.quantity" placeholder="Qty" required class="w-24 text-xs px-2.5 py-1.5 border border-gray-200 rounded-lg font-bold">
                                    <button v-if="requestForm.items.length > 1" type="button" @click="requestForm.items.splice(idx, 1)" class="text-rose-500 font-bold p-1">✕</button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Alasan Kebutuhan Lapangan <span class="text-red-500">*</span></label>
                            <input type="text" v-model="requestForm.notes" required placeholder="Cth: Jarak tarikan kabel melebihi estimasi / ONT lama LOS" class="w-full text-xs px-3 py-2 border border-gray-200 rounded-xl font-medium">
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex justify-end gap-2">
                            <button type="button" @click="showRequestModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl">Batal</button>
                            <button type="submit" :disabled="requestForm.processing" class="px-5 py-2 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm">
                                Ajukan ke Kepala Teknisi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── MODAL 4: PENOLAKAN ─────────────────────────────────── -->
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="showRejectModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-fade-in-up p-6 space-y-4">
                    <h3 class="text-base font-black text-gray-900">Tolak {{ rejectTargetType === 'handover' ? 'Serah Terima' : 'Permintaan Material' }}</h3>
                    <p class="text-xs text-gray-500">Berikan alasan penolakan agar teknisi lapangan dapat melakukan revisi.</p>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1 uppercase">Alasan Penolakan <span class="text-red-500">*</span></label>
                        <textarea v-model="rejectReason" rows="3" required placeholder="Tulis alasan..." class="w-full text-xs p-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-rose-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 rounded-xl">Batal</button>
                        <button type="button" @click="submitReject" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl">Konfirmasi Tolak</button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    transactions: Object,
    materials: Array,
    areas: Array,
    availableOnts: Array,
    currentTab: String,
    filters: Object,
    summary: Object,
});

const filterSearch = ref(props.filters.search || '');
const filterSource = ref(props.filters.source_type || '');
const filterCondition = ref(props.filters.condition || '');
const filterArea = ref(props.filters.area_id || '');

const switchTab = (tab) => {
    router.get('/material-handovers', {
        tab,
        search: filterSearch.value,
        source_type: filterSource.value,
        condition: filterCondition.value,
        area_id: filterArea.value,
    }, { preserveState: true, preserveScroll: true });
};

const applyFilters = () => {
    router.get('/material-handovers', {
        tab: props.currentTab,
        search: filterSearch.value,
        source_type: filterSource.value,
        condition: filterCondition.value,
        area_id: filterArea.value,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    filterSearch.value = '';
    filterSource.value = '';
    filterCondition.value = '';
    filterArea.value = '';
    switchTab(props.currentTab);
};

// ── Modals & Actions ──────────────────────────────────────────────────────────
const showReceiveModal = ref(false);
const activeTrx = ref(null);
const receiveForm = useForm({
    items: [],
    notes: '',
});

const openReceiveModal = (trx) => {
    activeTrx.value = trx;
    receiveForm.notes = '';
    receiveForm.items = (trx.items || []).map(it => ({
        id: it.id,
        material_name: it.material?.name || it.material_name || 'Item',
        quantity: it.quantity,
        unit: it.unit || it.material?.unit || 'pcs',
        condition: it.condition || 'Layak Pakai',
    }));
    showReceiveModal.value = true;
};

const submitReceiveForm = () => {
    receiveForm.post(`/material-handovers/${activeTrx.value.id}/receive`, {
        preserveScroll: true,
        onSuccess: () => {
            showReceiveModal.value = false;
        }
    });
};

// Reject Modal
const showRejectModal = ref(false);
const rejectTargetTrx = ref(null);
const rejectTargetType = ref('handover');
const rejectReason = ref('');

const openRejectModal = (trx, type) => {
    rejectTargetTrx.value = trx;
    rejectTargetType.value = type;
    rejectReason.value = '';
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!rejectReason.value) {
        alert('Silakan tuliskan alasan penolakan');
        return;
    }
    const url = rejectTargetType.value === 'handover'
        ? `/material-handovers/${rejectTargetTrx.value.id}/reject-handover`
        : `/material-handovers/${rejectTargetTrx.value.id}/reject-request`;

    router.post(url, { reason: rejectReason.value }, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
        }
    });
};

// Approve Request directly
const approveRequest = (trx) => {
    if (confirm(`Setujui permintaan ${trx.transaction_number} dan potong stok dari Inventory Gudang?`)) {
        router.post(`/material-handovers/${trx.id}/approve-request`, {}, {
            preserveScroll: true,
        });
    }
};

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatQty = (num) => {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
};

const getSourceLabel = (src) => {
    const map = {
        installation: '🏠 Instalasi PSB',
        pgs: '⚡ PGS Sigap',
        pgk: '🛠️ PGK Komplain',
        pgj: '🌐 PGJ Jalur',
        maintenance: '🛠️ Pemeliharaan Jalur',
        rollout_odc: '🏗️ Rollout ODC',
        rollout_odp: '🏗️ Rollout ODP',
        manual: 'Manual',
    };
    return map[src] || src || 'Umum';
};

const getSourceBadgeClass = (src) => {
    const map = {
        installation: 'bg-blue-100 text-blue-800',
        pgs: 'bg-amber-100 text-amber-800',
        pgk: 'bg-rose-100 text-rose-800',
        pgj: 'bg-purple-100 text-purple-800',
        maintenance: 'bg-teal-100 text-teal-800',
        rollout_odc: 'bg-emerald-100 text-emerald-800',
        rollout_odp: 'bg-teal-100 text-teal-800',
    };
    return map[src] || 'bg-gray-100 text-gray-800';
};

const getConditionBadgeClass = (cond) => {
    if (cond === 'Layak Pakai') return 'bg-emerald-100 text-emerald-800';
    if (cond === 'Tidak Layak') return 'bg-amber-100 text-amber-800';
    if (cond === 'Rusak') return 'bg-rose-100 text-rose-800';
    return 'bg-gray-100 text-gray-700';
};
</script>
