<template>
    <AppLayout title="Pemetaan FTTH" subtitle="Workspace pemetaan dan perancangan jaringan fiber">
        <div class="space-y-5">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                <div class="glass-card p-4 border border-blue-500/10 hover:border-blue-500/30 transition-all text-center">
                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12H3l9-9 9 9h-2M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.olts }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">OLT</p>
                </div>
                <div class="glass-card p-4 border border-amber-500/10 hover:border-amber-500/30 transition-all text-center">
                    <div class="p-2 bg-amber-100 text-amber-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.odcs }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">ODC</p>
                </div>
                <div class="glass-card p-4 border border-emerald-500/10 hover:border-emerald-500/30 transition-all text-center">
                    <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 3v12M18 9a3 3 0 01-3 3H9m-3 0a3 3 0 003 3h0a3 3 0 003-3m0 0V3"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.odps }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">ODP</p>
                </div>
                <div class="glass-card p-4 border border-purple-500/10 hover:border-purple-500/30 transition-all text-center">
                    <div class="p-2 bg-purple-100 text-purple-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.customers }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Pelanggan</p>
                </div>
                <div class="glass-card p-4 border border-cyan-500/10 hover:border-cyan-500/30 transition-all text-center">
                    <div class="p-2 bg-cyan-100 text-cyan-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.total_cable_km }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Kabel (Km)</p>
                </div>
                <div class="glass-card p-4 border border-rose-500/10 hover:border-rose-500/30 transition-all text-center">
                    <div class="p-2 bg-rose-100 text-rose-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.materials }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Material</p>
                </div>
                <div class="glass-card p-4 border border-indigo-500/10 hover:border-indigo-500/30 transition-all text-center">
                    <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.areas }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Area</p>
                </div>
                <div class="glass-card p-4 border border-teal-500/10 hover:border-teal-500/30 transition-all text-center">
                    <div class="p-2 bg-teal-100 text-teal-600 rounded-lg w-fit mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ summary.designs }}</p>
                    <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider">Perancangan</p>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center gap-3 flex-wrap">
                    <button @click="showDesignModal = true" class="btn-primary flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Buat Perancangan
                    </button>
                    <select v-model="selectedArea" @change="loadMapData" class="px-3 py-2 rounded-xl border border-gray-200 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <option value="">Semua Area</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                    <button @click="toggleMeasure" :class="isMeasuring ? 'bg-orange-100 text-orange-700 border-orange-300' : 'bg-white text-gray-600 border-gray-200'" class="px-3 py-2 rounded-xl border text-sm flex items-center gap-1.5 transition-colors">
                        📏 Ukur Jarak
                    </button>
                    <button @click="toggleFullscreen" class="px-3 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 flex items-center gap-1.5">
                        ⛶ Fullscreen
                    </button>
                </div>
                <div class="relative w-full md:w-72">
                    <input type="text" v-model="searchQuery" @keyup.enter="searchOnMap" placeholder="🔍 Cari OLT/ODC/ODP/Pelanggan..." class="w-full pl-4 pr-4 py-2 rounded-xl border border-gray-200 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <!-- Map + Panel Layout -->
            <div class="flex gap-0 rounded-2xl overflow-hidden shadow-sm border border-gray-100 bg-white" :class="isFullscreen ? 'fixed inset-0 z-50 rounded-none' : ''" ref="mapContainer">
                <!-- Layer Panel -->
                <div v-show="showLayerPanel" class="w-56 shrink-0 border-r border-gray-100 bg-white overflow-y-auto" :class="isFullscreen ? 'h-screen' : 'h-[600px]'">
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Layers</h4>
                        <button @click="showLayerPanel = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-3 space-y-1">
                        <label v-for="layer in layers" :key="layer.key" class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                            <input type="checkbox" v-model="layer.visible" @change="toggleLayer(layer)" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="w-3 h-3 rounded-full shrink-0" :style="{ background: layer.color }"></span>
                            <span class="text-xs font-medium text-gray-700">{{ layer.label }}</span>
                        </label>
                    </div>

                    <!-- Measurement Results -->
                    <div v-if="measureDistance > 0" class="mx-3 mb-3 p-3 bg-orange-50 border border-orange-200 rounded-xl">
                        <h5 class="text-xs font-bold text-orange-700 uppercase mb-2">📏 Pengukuran</h5>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600">Jarak</span>
                                <span class="font-bold text-gray-900">{{ formatDistance(measureDistance) }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600">Titik</span>
                                <span class="font-bold text-gray-900">{{ measurePoints.length }}</span>
                            </div>
                        </div>
                        <button @click="clearMeasure" class="mt-2 w-full text-xs py-1.5 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors">Reset</button>
                    </div>
                </div>

                <!-- Map -->
                <div class="flex-1 relative">
                    <div ref="mapEl" :class="isFullscreen ? 'h-screen' : 'h-[600px]'" class="w-full bg-gray-200"></div>
                    <!-- Floating Layer Toggle -->
                    <button v-if="!showLayerPanel" @click="showLayerPanel = true" class="absolute top-3 left-3 z-[1000] bg-white shadow-lg rounded-xl p-2.5 border border-gray-200 hover:bg-gray-50 transition-colors" title="Toggle Layers">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </button>
                    <!-- Map Type Toggle -->
                    <div class="absolute top-3 right-3 z-[1000] bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200 flex">
                        <button @click="setMapType('street')" :class="mapType === 'street' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 text-xs font-medium transition-colors">Map</button>
                        <button @click="setMapType('satellite')" :class="mapType === 'satellite' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 text-xs font-medium transition-colors">Satellite</button>
                    </div>
                    <!-- Fullscreen Exit -->
                    <button v-if="isFullscreen" @click="isFullscreen = false" class="absolute bottom-4 right-4 z-[1000] bg-white shadow-lg rounded-xl px-4 py-2 border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50">✕ Keluar Fullscreen</button>
                </div>
            </div>

            <!-- Designs List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Daftar Perancangan FTTH</h3>
                    <span class="text-xs text-gray-400">{{ designs.total || 0 }} rancangan</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase">Nama</th>
                                <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase">Area</th>
                                <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase">PIC</th>
                                <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase text-right">Jarak</th>
                                <th class="py-3 px-6 text-xs font-semibold text-gray-500 uppercase text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="d in designs.data" :key="d.id" class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-6">
                                    <p class="text-sm font-semibold text-gray-900">{{ d.name }}</p>
                                    <p v-if="d.description" class="text-xs text-gray-400 mt-0.5 truncate max-w-[200px]">{{ d.description }}</p>
                                </td>
                                <td class="py-3 px-6"><span class="px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">{{ d.area?.name }}</span></td>
                                <td class="py-3 px-6 text-sm text-gray-600">{{ d.pic || '-' }}</td>
                                <td class="py-3 px-6"><span :class="statusBadge(d.status)" class="px-2.5 py-1 rounded-full text-xs font-medium border">{{ statusLabel(d.status) }}</span></td>
                                <td class="py-3 px-6 text-right text-sm font-semibold text-gray-900">{{ formatDistance(d.total_distance) }}</td>
                                <td class="py-3 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/ftth/${d.id}`" title="Buka Workspace" class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-lg hover:bg-blue-100 transition-colors">
                                            Buka Peta
                                        </Link>
                                        <button @click="openModal(d)" title="Edit" class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button @click="deleteDesign(d.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!designs.data || designs.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-gray-400 text-sm">Belum ada perancangan. Klik "Buat Perancangan" untuk memulai.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="designs.links && designs.data?.length > 0" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-xs text-gray-500">{{ designs.from }} - {{ designs.to }} dari {{ designs.total }}</p>
                    <div class="flex gap-1">
                        <Link v-for="(link, i) in designs.links" :key="i" :href="link.url || '#'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors" :class="[link.active ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100', !link.url && 'opacity-50 cursor-not-allowed']" v-html="link.label" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="fixed bottom-6 right-6 z-[9999] flex items-center gap-3 p-4 rounded-xl bg-emerald-600 text-white shadow-2xl animate-fade-in-up">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium">{{ $page.props.flash.success }}</span>
        </div>

        <!-- Design Modal -->
        <div v-if="showDesignModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="showDesignModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-y-auto max-h-[90vh] animate-fade-in-up">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Perancangan' : 'Buat Perancangan Baru' }}</h3>
                    <button @click="showDesignModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Perancangan *</label>
                        <input v-model="designForm.name" type="text" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Contoh: FTTH BC-1 Tahap 1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Area/Wilayah *</label>
                        <select v-model="designForm.area_id" required class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="">Pilih Area...</option>
                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PIC (Person in Charge)</label>
                        <input v-model="designForm.pic" type="text" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" placeholder="Nama penanggung jawab">
                    </div>
                    <div v-if="isEditing">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select v-model="designForm.status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="draft">Draft</option>
                            <option value="design">Perancangan</option>
                            <option value="review">Review</option>
                            <option value="approved">Disetujui</option>
                            <option value="construction">Pembangunan</option>
                            <option value="completed">Selesai</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cadangan Kabel (%)</label>
                            <input v-model="designForm.slack_percentage" type="number" min="0" max="100" step="0.5" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea v-model="designForm.description" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 resize-none" placeholder="Deskripsi rancangan (opsional)"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-10">
                    <button @click="showDesignModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">Batal</button>
                    <button @click="submitDesign" :disabled="designForm.processing" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="designForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        {{ isEditing ? 'Simpan' : 'Buat Perancangan' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    areas: Array,
    summary: Object,
    designs: Object,
    filters: Object,
});

// State
const mapEl = ref(null);
const mapContainer = ref(null);
let map = null;
let layerGroups = {};
let tileLayer = null;
let satLayer = null;
let measureLine = null;
let measureMarkers = [];

const selectedArea = ref(props.filters?.area_id || '');
const searchQuery = ref('');
const showLayerPanel = ref(true);
const showDesignModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const isFullscreen = ref(false);
const mapType = ref('street');
const isMeasuring = ref(false);
const measurePoints = ref([]);
const measureDistance = ref(0);

// Layers config
const layers = ref([
    { key: 'olt', label: 'OLT', color: '#ef4444', visible: true, icon: '🔴' },
    { key: 'odc', label: 'ODC', color: '#f59e0b', visible: true, icon: '🟠' },
    { key: 'odp', label: 'ODP', color: '#22c55e', visible: true, icon: '🟢' },
    { key: 'customer', label: 'Pelanggan', color: '#8b5cf6', visible: true, icon: '🟣' },
    { key: 'routes', label: 'Jalur Kabel', color: '#3b82f6', visible: true, icon: '🔵' },
]);

// Design form
const designForm = useForm({
    name: '',
    area_id: '',
    description: '',
    pic: '',
    status: 'draft',
    slack_percentage: 5,
});

// Helpers
const formatDistance = (m) => {
    if (!m || m === 0) return '0 m';
    if (m >= 1000) return (m / 1000).toFixed(2) + ' km';
    return Math.round(m) + ' m';
};

const statusBadge = (s) => ({
    'draft': 'bg-gray-50 text-gray-600 border-gray-200',
    'design': 'bg-blue-50 text-blue-700 border-blue-200',
    'review': 'bg-amber-50 text-amber-700 border-amber-200',
    'approved': 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'construction': 'bg-orange-50 text-orange-700 border-orange-200',
    'completed': 'bg-green-50 text-green-700 border-green-200',
}[s] || 'bg-gray-50 text-gray-600 border-gray-200');

const statusLabel = (s) => ({
    'draft': 'Draft',
    'design': 'Perancangan',
    'review': 'Review',
    'approved': 'Disetujui',
    'construction': 'Pembangunan',
    'completed': 'Selesai',
}[s] || s);

// Map init
onMounted(async () => {
    await nextTick();
    const L = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    map = L.map(mapEl.value, {
        center: [-6.2, 106.8],
        zoom: 12,
        zoomControl: true,
    });

    tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);

    satLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© Esri',
        maxZoom: 19,
    });

    // Init layer groups
    layers.value.forEach(l => {
        layerGroups[l.key] = L.layerGroup().addTo(map);
    });

    // Map click for measurement
    map.on('click', (e) => {
        if (!isMeasuring.value) return;
        addMeasurePoint(e.latlng, L);
    });

    loadMapData();
});

// Map type toggle
const setMapType = (type) => {
    mapType.value = type;
    if (type === 'satellite') {
        map.removeLayer(tileLayer);
        satLayer.addTo(map);
    } else {
        map.removeLayer(satLayer);
        tileLayer.addTo(map);
    }
};

// Fullscreen
const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
    nextTick(() => map?.invalidateSize());
};

watch(isFullscreen, () => {
    nextTick(() => map?.invalidateSize());
});

// Layers
const toggleLayer = (layer) => {
    if (layer.visible) {
        layerGroups[layer.key]?.addTo(map);
    } else {
        map?.removeLayer(layerGroups[layer.key]);
    }
};

// Measurement
const toggleMeasure = () => {
    isMeasuring.value = !isMeasuring.value;
    if (!isMeasuring.value) {
        clearMeasure();
    }
};

const addMeasurePoint = async (latlng, L) => {
    measurePoints.value.push(latlng);

    const marker = L.circleMarker(latlng, {
        radius: 6,
        color: '#ea580c',
        fillColor: '#fb923c',
        fillOpacity: 1,
        weight: 2,
    }).addTo(map);
    measureMarkers.push(marker);

    if (measurePoints.value.length > 1) {
        if (measureLine) map.removeLayer(measureLine);
        measureLine = L.polyline(measurePoints.value, {
            color: '#ea580c',
            weight: 3,
            dashArray: '8, 8',
        }).addTo(map);

        // Calculate total distance
        let total = 0;
        for (let i = 1; i < measurePoints.value.length; i++) {
            total += map.distance(measurePoints.value[i - 1], measurePoints.value[i]);
        }
        measureDistance.value = total;
    }
};

const clearMeasure = async () => {
    measurePoints.value = [];
    measureDistance.value = 0;
    measureMarkers.forEach(m => map?.removeLayer(m));
    measureMarkers = [];
    if (measureLine) { map?.removeLayer(measureLine); measureLine = null; }
};

// Load map data
const loadMapData = async () => {
    try {
        const params = {};
        if (selectedArea.value) params.area_id = selectedArea.value;

        const res = await fetch('/ftth/api/map-data?' + new URLSearchParams(params));
        const data = await res.json();

        const L = await import('leaflet');

        // Clear all layers
        Object.values(layerGroups).forEach(g => g.clearLayers());

        const createIcon = (color, label) => L.divIcon({
            className: 'custom-marker',
            html: `<div style="background:${color};width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:700;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3)">${label}</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14],
        });

        // OLTs
        data.olts?.forEach(olt => {
            if (!olt.latitude || !olt.longitude) return;
            L.marker([olt.latitude, olt.longitude], { icon: createIcon('#ef4444', 'OLT') })
                .bindPopup(`<div class="text-sm"><b class="text-red-600">${olt.name}</b><br><span class="text-gray-500">${olt.brand || ''} ${olt.model || ''}</span><br><span class="text-xs text-gray-400">${olt.ip_address || ''}</span><br><span class="text-xs">${olt.total_pon_ports || 0} PON</span></div>`)
                .addTo(layerGroups.olt);
        });

        // ODCs
        data.odcs?.forEach(odc => {
            if (!odc.latitude || !odc.longitude) return;
            L.marker([odc.latitude, odc.longitude], { icon: createIcon('#f59e0b', 'ODC') })
                .bindPopup(`<div class="text-sm"><b class="text-amber-600">${odc.name}</b><br><span class="text-xs text-gray-500">Kapasitas: ${odc.capacity || '-'}</span><br><span class="text-xs text-gray-400">OLT: ${odc.olt?.name || '-'}</span></div>`)
                .addTo(layerGroups.odc);
        });

        // ODPs
        data.odps?.forEach(odp => {
            if (!odp.latitude || !odp.longitude) return;
            L.marker([odp.latitude, odp.longitude], { icon: createIcon('#22c55e', 'ODP') })
                .bindPopup(`<div class="text-sm"><b class="text-green-600">${odp.name}</b><br><span class="text-xs">${odp.used_ports || 0}/${odp.total_ports || 0} port</span><br><span class="text-xs text-gray-400">ODC: ${odp.odc?.name || '-'}</span></div>`)
                .addTo(layerGroups.odp);
        });

        // Customers
        data.customers?.forEach(c => {
            if (!c.latitude || !c.longitude) return;
            L.circleMarker([c.latitude, c.longitude], {
                radius: 5,
                color: '#8b5cf6',
                fillColor: '#a78bfa',
                fillOpacity: 0.8,
                weight: 2,
            }).bindPopup(`<div class="text-sm"><b class="text-purple-600">${c.name}</b><br><span class="text-xs text-gray-500">${c.customer_code}</span><br><span class="text-xs text-gray-400">${c.address || ''}</span></div>`)
                .addTo(layerGroups.customer);
        });

        // Cable routes
        data.routes?.forEach(route => {
            if (!route.route_points || route.route_points.length < 2) return;
            const color = route.route_type === 'existing' ? '#3b82f6' : '#f97316';
            L.polyline(route.route_points, {
                color,
                weight: 3,
                opacity: 0.8,
            }).bindPopup(`<div class="text-sm"><b>${route.cable_type || 'Kabel FO'}</b><br>${route.core_count} Core<br>${formatDistance(route.distance)}</div>`)
                .addTo(layerGroups.routes);
        });

        // Auto-fit bounds
        const allCoords = [
            ...data.olts.filter(o => o.latitude).map(o => [o.latitude, o.longitude]),
            ...data.odcs.filter(o => o.latitude).map(o => [o.latitude, o.longitude]),
            ...data.odps.filter(o => o.latitude).map(o => [o.latitude, o.longitude]),
            ...data.customers.filter(c => c.latitude).map(c => [c.latitude, c.longitude]),
        ];
        if (allCoords.length > 0) {
            map.fitBounds(L.latLngBounds(allCoords).pad(0.1));
        }
    } catch (e) {
        console.error('Failed to load map data:', e);
    }
};

// Search
const searchOnMap = async () => {
    if (!searchQuery.value.trim()) return;
    const q = searchQuery.value.trim().toLowerCase();

    // Search in loaded layers
    let found = false;
    for (const key of ['olt', 'odc', 'odp', 'customer']) {
        layerGroups[key]?.eachLayer((layer) => {
            const popup = layer.getPopup();
            if (popup && popup.getContent().toLowerCase().includes(q)) {
                const latlng = layer.getLatLng();
                map.setView(latlng, 17);
                layer.openPopup();
                found = true;
            }
        });
        if (found) break;
    }
};

// Design CRUD
function openModal(d = null) {
    if (d) {
        isEditing.value = true;
        editingId.value = d.id;
        designForm.name = d.name;
        designForm.area_id = d.area_id;
        designForm.description = d.description || '';
        designForm.pic = d.pic || '';
        designForm.status = d.status || 'draft';
        designForm.slack_percentage = d.slack_percentage || 5;
    } else {
        isEditing.value = false;
        editingId.value = null;
        designForm.reset();
    }
    showDesignModal.value = true;
}

function submitDesign() {
    if (isEditing.value) {
        designForm.post(`/ftth/${editingId.value}/update`, {
            preserveScroll: true,
            onSuccess: () => { showDesignModal.value = false; designForm.reset(); },
        });
    } else {
        designForm.post('/ftth', {
            preserveScroll: true,
            onSuccess: () => { showDesignModal.value = false; designForm.reset(); },
        });
    }
}

function deleteDesign(id) {
    if (confirm('Hapus perancangan ini beserta seluruh datanya?')) {
        router.post(`/ftth/${id}/delete`);
    }
}
</script>

<style>
@import 'leaflet/dist/leaflet.css';
.custom-marker { background: transparent !important; border: none !important; }
</style>
