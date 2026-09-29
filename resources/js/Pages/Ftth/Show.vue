<template>
    <AppLayout :title="design.name" subtitle="Workspace Perancangan Jaringan FTTH">
        <template #header-actions>
            <div class="flex items-center gap-3">
                <span :class="statusBadge(design.status)" class="px-3 py-1.5 rounded-xl text-xs font-bold border uppercase tracking-wider">
                    {{ statusLabel(design.status) }}
                </span>
                <Link href="/ftth" class="btn-secondary text-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali
                </Link>
            </div>
        </template>

        <!-- Main Layout: Toolbar + Sidebar + Map -->
        <div class="flex flex-col h-[calc(100vh-140px)] min-h-[600px] bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            
            <!-- Workspace Toolbar -->
            <div class="h-14 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between px-4 shrink-0">
                <!-- Modes -->
                <div class="flex items-center gap-1 bg-gray-200/50 p-1 rounded-xl">
                    <button @click="setMode('view')" :class="mode === 'view' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        View
                    </button>
                    <button @click="setMode('device')" :class="mode === 'device' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        + Perangkat
                    </button>
                    <button @click="setMode('cable')" :class="mode === 'cable' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        + Tarik Kabel
                    </button>
                    <button @click="setMode('measure')" :class="mode === 'measure' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        Ukur
                    </button>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center gap-2">
                    <span :class="{'bg-green-100 text-green-700': design.status === 'approved', 'bg-yellow-100 text-yellow-700': design.status === 'review', 'bg-gray-100 text-gray-700': design.status === 'draft'}" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                        {{ design.status }}
                    </span>
                    <a v-if="design.status !== 'draft'" :href="`/ftth/${design.id}/export`" target="_blank" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 border border-indigo-200 px-4 py-1.5 rounded-xl text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Export PDF
                    </a>
                    <button v-if="design.status === 'draft'" @click="openReview" class="btn-primary text-sm px-4 py-1.5">
                        📝 Review & Simpan
                    </button>
                </div>
            </div>

            <div class="flex flex-1 overflow-hidden relative">
                <!-- Sidebar Tools -->
                <div class="w-72 bg-white border-r border-gray-100 flex flex-col z-10 shadow-[4px_0_15px_-3px_rgba(0,0,0,0.05)]">
                    
                    <!-- Context Panel based on Mode -->
                    <div class="flex-1 overflow-y-auto">
                        <!-- VIEW MODE -->
                        <div v-if="mode === 'view'" class="p-4 space-y-4">
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Informasi Rancangan</h3>
                            <div class="space-y-3">
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <p class="text-xs text-gray-500 mb-1">Area</p>
                                    <p class="text-sm font-medium text-gray-900">{{ design.area?.name || '-' }}</p>
                                </div>
                                <div class="bg-gray-50 p-3 rounded-xl border border-gray-100">
                                    <p class="text-xs text-gray-500 mb-1">Total Panjang Jalur</p>
                                    <p class="text-lg font-bold text-blue-600">{{ formatDistance(design.total_distance) }}</p>
                                    <p class="text-[10px] text-gray-400 mt-1">Estimasi Kabel ({{ design.slack_percentage }}% slack): {{ formatDistance(estimatedCable) }}</p>
                                </div>
                            </div>

                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mt-6 mb-3">Layers</h3>
                            <div class="space-y-2">
                                <label v-for="layer in layers" :key="layer.key" class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer">
                                    <input type="checkbox" v-model="layer.visible" @change="toggleLayer(layer)" class="w-4 h-4 rounded border-gray-300 text-blue-600">
                                    <div class="w-4 h-4 rounded-full flex items-center justify-center text-[10px]" :style="{ background: layer.color }"></div>
                                    <span class="text-sm font-medium text-gray-700">{{ layer.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- ADD DEVICE MODE -->
                        <div v-if="mode === 'device'" class="p-4">
                            <div class="bg-blue-50 text-blue-700 p-3 rounded-xl text-xs mb-4 border border-blue-100">
                                Pilih jenis perangkat, lalu <b>klik pada peta</b> untuk menempatkannya.
                            </div>
                            
                            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Jenis Perangkat</h3>
                            <div class="space-y-2">
                                <button v-for="dev in deviceTypes" :key="dev.id" 
                                    @click="selectDeviceType(dev.id)"
                                    :class="selectedDeviceType === dev.id ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'"
                                    class="w-full text-left px-4 py-3 rounded-xl border transition-all flex items-center gap-3">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs text-white" :style="{ background: dev.color }">
                                        {{ dev.icon }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ dev.name }}</p>
                                        <p class="text-[10px] text-gray-500">{{ dev.desc }}</p>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- CABLE MODE -->
                        <div v-if="mode === 'cable'" class="p-4">
                            <div class="bg-blue-50 text-blue-700 p-3 rounded-xl text-xs mb-4 border border-blue-100">
                                Klik titik awal, lalu klik berkali-kali mengikuti jalan untuk menggambar jalur kabel.
                            </div>

                            <div v-if="activeCablePoints.length > 0" class="p-3 bg-gray-50 rounded-xl border border-gray-200 mb-4">
                                <p class="text-xs font-bold text-gray-700 mb-2">Jalur Aktif</p>
                                <p class="text-sm">{{ activeCablePoints.length }} Titik</p>
                                <p class="text-sm text-blue-600 font-bold">{{ formatDistance(activeCableDistance) }}</p>
                                
                                <div class="mt-3 space-y-2">
                                    <button @click="undoCablePoint" class="w-full bg-white border border-gray-300 text-gray-700 rounded-lg py-1.5 text-xs hover:bg-gray-50">↩ Undo Titik Terakhir</button>
                                    <button @click="finishCable" class="w-full btn-primary py-1.5 text-xs">Simpan Jalur</button>
                                    <button @click="cancelCable" class="w-full bg-red-50 text-red-600 border border-red-200 rounded-lg py-1.5 text-xs hover:bg-red-100">Batal / Hapus</button>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-400 text-sm">
                                <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>
                                Klik pada peta untuk memulai jalur kabel
                            </div>
                        </div>

                        <!-- MEASURE MODE -->
                        <div v-if="mode === 'measure'" class="p-4">
                            <div class="bg-orange-50 text-orange-700 p-3 rounded-xl text-xs mb-4 border border-orange-100">
                                Klik berkali-kali pada peta (mengikuti jalan) untuk mengukur jarak akurat.
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Total Jarak</p>
                                <p class="text-2xl font-bold text-gray-900">{{ formatDistance(measureDistance) }}</p>
                                <div v-if="measurePoints.length > 0" class="mt-3 flex gap-2">
                                    <button @click="undoMeasurePoint" class="flex-1 text-xs py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">↩ Undo</button>
                                    <button @click="clearMeasure" class="flex-1 text-xs py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map Container -->
                <div class="flex-1 relative bg-gray-200">
                    <div ref="mapEl" class="absolute inset-0"></div>
                    
                    <!-- Map Type Toggle -->
                    <div class="absolute top-3 right-3 z-[1000] bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200 flex">
                        <button @click="setMapType('street')" :class="mapType === 'street' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 text-xs font-medium transition-colors">Map</button>
                        <button @click="setMapType('satellite')" :class="mapType === 'satellite' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'" class="px-3 py-2 text-xs font-medium transition-colors">Satellite</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Device Modal Content (Triggered after map click) -->
        <div v-if="showDeviceModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="showDeviceModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm animate-fade-in-up">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900">Detail Perangkat</h3>
                    <button @click="showDeviceModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Jenis</label>
                        <input type="text" :value="deviceTypes.find(d => d.id === newDeviceData.type)?.name" disabled class="w-full px-3 py-2 rounded-lg border border-gray-200 bg-gray-50 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nama / Label *</label>
                        <input v-model="newDeviceData.name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 text-sm" placeholder="Contoh: ODP-01">
                    </div>
                    <div v-if="newDeviceData.type === 'odp'">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Kapasitas Port</label>
                        <select v-model="newDeviceData.capacity" class="w-full px-3 py-2 rounded-lg border border-gray-200 focus:border-blue-500 text-sm">
                            <option value="8">8 Port</option>
                            <option value="16">16 Port</option>
                        </select>
                    </div>
                    <button @click="saveDevice" class="w-full btn-primary py-2 text-sm mt-2">Simpan Perangkat</button>
                </div>
            </div>
        </div>
        <!-- Review Modal -->
        <div v-if="showReviewModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" @click="showReviewModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-3xl animate-fade-in-up max-h-[90vh] flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Review & Kebutuhan Material</h3>
                        <p class="text-xs text-gray-500">Kalkulasi otomatis berdasarkan peta perancangan</p>
                    </div>
                    <button @click="showReviewModal = false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    <!-- Infrastructure Summary -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 mb-3 border-b pb-2">Ringkasan Infrastruktur Baru</h4>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-center">
                                <p class="text-xs text-gray-500">Kabel (Net)</p>
                                <p class="text-lg font-bold text-gray-900">{{ formatDistance(design.total_distance) }}</p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-xl border border-blue-200 text-center">
                                <p class="text-xs text-blue-600">Estimasi Kabel ({{ design.slack_percentage }}% slack)</p>
                                <p class="text-lg font-bold text-blue-700">{{ formatDistance(estimatedCable) }}</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-center">
                                <p class="text-xs text-gray-500">Perangkat Pasif</p>
                                <p class="text-lg font-bold text-gray-900">{{ devices.filter(d => ['odc', 'odp'].includes(d.device_type)).length }} unit</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 text-center">
                                <p class="text-xs text-gray-500">Tiang Baru</p>
                                <p class="text-lg font-bold text-gray-900">{{ devices.filter(d => d.device_type === 'tiang').length }} batang</p>
                            </div>
                        </div>
                    </div>

                    <!-- Auto-calculated Materials Table -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 mb-3 border-b pb-2 flex items-center justify-between">
                            <span>Daftar Material</span>
                            <button @click="autoCalculateMaterials" class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1 rounded-lg hover:bg-indigo-100">🔄 Kalkulasi Ulang</button>
                        </h4>
                        
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="py-2 px-3 text-xs font-semibold text-gray-600 border-b">Material</th>
                                    <th class="py-2 px-3 text-xs font-semibold text-gray-600 border-b w-32">Kategori</th>
                                    <th class="py-2 px-3 text-xs font-semibold text-gray-600 border-b text-right w-24">Stok Saat Ini</th>
                                    <th class="py-2 px-3 text-xs font-semibold text-gray-600 border-b text-right w-24">Dibutuhkan</th>
                                    <th class="py-2 px-3 text-xs font-semibold text-gray-600 border-b text-center w-20">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="mat in calculatedMaterials" :key="mat.id" class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="py-2 px-3 text-sm text-gray-900">{{ mat.name }}</td>
                                    <td class="py-2 px-3 text-xs text-gray-500">{{ mat.category || '-' }}</td>
                                    <td class="py-2 px-3 text-sm text-gray-900 text-right">{{ mat.stock }} {{ mat.unit }}</td>
                                    <td class="py-2 px-3">
                                        <input type="number" v-model="mat.quantity" class="w-full px-2 py-1 text-sm text-right border border-gray-300 rounded focus:border-blue-500" min="0">
                                    </td>
                                    <td class="py-2 px-3 text-center">
                                        <span v-if="mat.quantity > mat.stock" class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold">Defisit</span>
                                        <span v-else-if="mat.quantity > 0" class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded font-bold">Cukup</span>
                                        <span v-else class="text-[10px] text-gray-400">-</span>
                                    </td>
                                </tr>
                                <tr v-if="calculatedMaterials.length === 0">
                                    <td colspan="5" class="py-4 text-center text-xs text-gray-500">Klik "Kalkulasi Ulang" atau tambahkan perangkat di peta.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Status Change -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 mb-3 border-b pb-2">Status Perancangan</h4>
                        <select v-model="reviewForm.status" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            <option value="draft">Draft - Sedang digambar</option>
                            <option value="design">Perancangan - Selesai digambar</option>
                            <option value="review">Review - Menunggu persetujuan</option>
                            <option value="approved">Disetujui - Siap dibangun</option>
                            <option value="construction">Pembangunan - Sedang dibangun</option>
                            <option value="completed">Selesai - Telah diaktivasi</option>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 shrink-0 rounded-b-2xl">
                    <button @click="showReviewModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50">Batal</button>
                    <button @click="submitReview" :disabled="reviewForm.processing" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                        <svg v-if="reviewForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Simpan Hasil Review
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    design: Object,
    devices: Array,
    routes: Array,
    materials: Array,
});

// UI State
const mode = ref('view');
const mapType = ref('street');
const mapEl = ref(null);
let map = null;
let tileLayer = null;
let satLayer = null;
let layerGroups = {};
let drawLayer = null; // For temp drawing
let tempMarkers = [];

// Device Types
const deviceTypes = [
    { id: 'olt', name: 'OLT', desc: 'Optical Line Terminal', color: '#ef4444', icon: '🔴' },
    { id: 'odc', name: 'ODC', desc: 'Optical Distribution Cabinet', color: '#f59e0b', icon: '🟠' },
    { id: 'odp', name: 'ODP', desc: 'Optical Distribution Point', color: '#22c55e', icon: '🟢' },
    { id: 'tiang', name: 'Tiang', desc: 'Tiang Telekomunikasi', color: '#64748b', icon: '⚫' },
];

const layers = ref([
    { key: 'olt', label: 'OLT', color: '#ef4444', visible: true },
    { key: 'odc', label: 'ODC', color: '#f59e0b', visible: true },
    { key: 'odp', label: 'ODP', color: '#22c55e', visible: true },
    { key: 'customer', label: 'Pelanggan', color: '#8b5cf6', visible: true },
    { key: 'routes', label: 'Jalur Kabel', color: '#3b82f6', visible: true },
    { key: 'tiang', label: 'Tiang', color: '#64748b', visible: true },
    { key: 'coverage', label: 'Area Coverage', color: '#10b981', visible: false },
]);

const selectedDeviceType = ref('odp');
const showDeviceModal = ref(false);
const newDeviceData = ref({ type: '', name: '', lat: 0, lng: 0, capacity: 8 });

// Review Modal State
const showReviewModal = ref(false);
const calculatedMaterials = ref([]);
const reviewForm = useForm({
    status: props.design.status,
    materials: [],
});

// Measuring State
const isMeasuring = computed(() => mode.value === 'measure');
const measurePoints = ref([]);
const measureDistance = ref(0);
let measureLine = null;
let measureMarkers = [];

// Cable State
const activeCablePoints = ref([]);
const activeCableDistance = ref(0);
let activeCableLine = null;
let activeCableMarkers = [];

// Computed
const estimatedCable = computed(() => {
    const dist = parseFloat(props.design.total_distance) || 0;
    const slack = dist * ((parseFloat(props.design.slack_percentage) || 5) / 100);
    return dist + slack;
});

// Helpers
const formatDistance = (m) => {
    if (!m || m === 0) return '0 m';
    if (m >= 1000) return (m / 1000).toFixed(2) + ' km';
    return Math.round(m) + ' m';
};
const statusBadge = (s) => ({
    'draft': 'bg-gray-100 text-gray-600 border-gray-300',
    'design': 'bg-blue-100 text-blue-700 border-blue-300',
    'review': 'bg-amber-100 text-amber-700 border-amber-300',
}[s] || 'bg-gray-100 text-gray-600 border-gray-300');
const statusLabel = (s) => ({'draft': 'Draft', 'design': 'Perancangan', 'review': 'Review'}[s] || s);

const setMode = (newMode) => {
    mode.value = newMode;
    clearMeasure();
    cancelCable();
};

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

const selectDeviceType = (type) => {
    selectedDeviceType.value = type;
};

// Map Init
onMounted(async () => {
    await nextTick();
    const L = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    map = L.map(mapEl.value, {
        center: [-6.2, 106.8],
        zoom: 13,
        zoomControl: false,
    });
    L.control.zoom({ position: 'bottomright' }).addTo(map);

    tileLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
        attribution: '© Google',
        maxZoom: 24,
        maxNativeZoom: 19,
    }).addTo(map);

    satLayer = L.tileLayer('https://mt1.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
        attribution: '© Google',
        maxZoom: 24,
        maxNativeZoom: 19,
    });

    layers.value.forEach(l => { layerGroups[l.key] = L.layerGroup().addTo(map); });
    drawLayer = L.layerGroup().addTo(map);

    // Map Click Handler
    map.on('click', (e) => {
        if (mode.value === 'device') {
            handleDeviceClick(e.latlng);
        } else if (mode.value === 'measure') {
            addMeasurePoint(e.latlng, L);
        } else if (mode.value === 'cable') {
            addCablePoint(e.latlng, L);
        }
    });

    loadData(L);
});

// Device Placement
const handleDeviceClick = (latlng) => {
    newDeviceData.value = {
        type: selectedDeviceType.value,
        name: `${selectedDeviceType.value.toUpperCase()}-`,
        lat: latlng.lat,
        lng: latlng.lng,
        capacity: 8
    };
    showDeviceModal.value = true;
};

const saveDevice = async () => {
    try {
        await axios.post('/ftth/devices', {
            design_id: props.design.id,
            device_type: newDeviceData.value.type,
            name: newDeviceData.value.name,
            latitude: newDeviceData.value.lat,
            longitude: newDeviceData.value.lng,
            meta: { capacity: newDeviceData.value.capacity }
        });
        showDeviceModal.value = false;
        router.reload(); // Refresh data
    } catch (e) {
        alert('Gagal menyimpan perangkat');
    }
};

// Cable Drawing
const addCablePoint = async (latlng, L) => {
    activeCablePoints.value.push(latlng);
    const marker = L.circleMarker(latlng, { radius: 4, color: '#3b82f6', fillColor: '#fff', fillOpacity: 1, weight: 2 }).addTo(drawLayer);
    activeCableMarkers.push(marker);

    if (activeCablePoints.value.length > 1) {
        if (activeCableLine) drawLayer.removeLayer(activeCableLine);
        activeCableLine = L.polyline(activeCablePoints.value, { color: '#3b82f6', weight: 3 }).addTo(drawLayer);
        
        let total = 0;
        for (let i = 1; i < activeCablePoints.value.length; i++) {
            total += map.distance(activeCablePoints.value[i - 1], activeCablePoints.value[i]);
        }
        activeCableDistance.value = total;
    }
};

const undoCablePoint = () => {
    if (activeCablePoints.value.length === 0) return;
    
    // Remove last marker
    const marker = activeCableMarkers.pop();
    drawLayer.removeLayer(marker);
    activeCablePoints.value.pop();

    // Re-draw line
    if (activeCableLine) drawLayer.removeLayer(activeCableLine);
    activeCableDistance.value = 0;

    if (activeCablePoints.value.length > 1) {
        activeCableLine = L.polyline(activeCablePoints.value, { color: '#3b82f6', weight: 3 }).addTo(drawLayer);
        let total = 0;
        for (let i = 1; i < activeCablePoints.value.length; i++) {
            total += map.distance(activeCablePoints.value[i - 1], activeCablePoints.value[i]);
        }
        activeCableDistance.value = total;
    } else {
        activeCableLine = null;
    }
};

const cancelCable = () => {
    activeCablePoints.value = [];
    activeCableDistance.value = 0;
    drawLayer?.clearLayers();
    activeCableLine = null;
    activeCableMarkers = [];
};

const finishCable = async () => {
    if (activeCablePoints.value.length < 2) return;
    try {
        await axios.post('/ftth/cable-routes', {
            design_id: props.design.id,
            distance: activeCableDistance.value,
            route_points: activeCablePoints.value.map(p => [p.lat, p.lng]),
            cable_type: 'FO',
            core_count: 12
        });
        cancelCable();
        router.reload();
    } catch (e) {
        alert('Gagal menyimpan jalur kabel');
    }
};

// Measuring
const addMeasurePoint = async (latlng, L) => {
    measurePoints.value.push(latlng);
    const marker = L.circleMarker(latlng, { radius: 5, color: '#ea580c', fillColor: '#fff', fillOpacity: 1, weight: 2 }).addTo(drawLayer);
    measureMarkers.push(marker);

    if (measurePoints.value.length > 1) {
        if (measureLine) drawLayer.removeLayer(measureLine);
        measureLine = L.polyline(measurePoints.value, { color: '#ea580c', weight: 3, dashArray: '5, 5' }).addTo(drawLayer);
        
        let total = 0;
        for (let i = 1; i < measurePoints.value.length; i++) {
            total += map.distance(measurePoints.value[i - 1], measurePoints.value[i]);
        }
        measureDistance.value = total;
    }
};

const undoMeasurePoint = () => {
    if (measurePoints.value.length === 0) return;
    
    const marker = measureMarkers.pop();
    drawLayer.removeLayer(marker);
    measurePoints.value.pop();

    if (measureLine) drawLayer.removeLayer(measureLine);
    measureDistance.value = 0;

    if (measurePoints.value.length > 1) {
        measureLine = L.polyline(measurePoints.value, { color: '#ea580c', weight: 3, dashArray: '5, 5' }).addTo(drawLayer);
        let total = 0;
        for (let i = 1; i < measurePoints.value.length; i++) {
            total += map.distance(measurePoints.value[i - 1], measurePoints.value[i]);
        }
        measureDistance.value = total;
    } else {
        measureLine = null;
    }
};

const clearMeasure = () => {
    measurePoints.value = [];
    measureDistance.value = 0;
    measureMarkers.forEach(m => drawLayer?.removeLayer(m));
    measureMarkers = [];
    if (measureLine) { drawLayer?.removeLayer(measureLine); measureLine = null; }
};

const toggleLayer = (layer) => {
    if (layer.visible) layerGroups[layer.key]?.addTo(map);
    else map?.removeLayer(layerGroups[layer.key]);
};

// Load Data
const loadData = (L) => {
    const createIcon = (color, label) => L.divIcon({
        className: 'custom-marker',
        html: `<div style="background:${color};width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:bold;border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,0.3)">${label}</div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
    });

    // Existing OLT
    props.design.area?.olts?.forEach(olt => {
        if (!olt.latitude || !olt.longitude) return;
        L.marker([olt.latitude, olt.longitude], { icon: createIcon('#ef4444', 'OLT') }).bindPopup(`<b>${olt.name}</b>`).addTo(layerGroups['olt']);
        L.circle([olt.latitude, olt.longitude], { radius: 2000, color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.05, weight: 1, dashArray: '5,5' }).addTo(layerGroups['coverage']);
    });
    // Existing ODC
    props.design.area?.odcs?.forEach(odc => {
        if (!odc.latitude || !odc.longitude) return;
        L.marker([odc.latitude, odc.longitude], { icon: createIcon('#f59e0b', 'ODC') }).bindPopup(`<b>${odc.name}</b>`).addTo(layerGroups['odc']);
        L.circle([odc.latitude, odc.longitude], { radius: 500, color: '#f59e0b', fillColor: '#f59e0b', fillOpacity: 0.08, weight: 1, dashArray: '5,5' }).addTo(layerGroups['coverage']);
    });
    // Existing ODP
    props.design.area?.odps?.forEach(odp => {
        if (!odp.latitude || !odp.longitude) return;
        L.marker([odp.latitude, odp.longitude], { icon: createIcon('#22c55e', 'ODP') }).bindPopup(`<b>${odp.name}</b>`).addTo(layerGroups['odp']);
        L.circle([odp.latitude, odp.longitude], { radius: 150, color: '#22c55e', fillColor: '#22c55e', fillOpacity: 0.1, weight: 1 }).addTo(layerGroups['coverage']);
    });

    // Render design devices
    props.devices?.forEach(d => {
        if (!d.latitude || !d.longitude) return;
        const typeInfo = deviceTypes.find(t => t.id === d.device_type) || { color: '#64748b' };
        
        const marker = L.marker([d.latitude, d.longitude], { 
            icon: createIcon(typeInfo.color, d.device_type.toUpperCase().substring(0,3)),
            draggable: true
        }).bindPopup(`<div class="text-sm font-bold">${d.name || d.device_type}</div><div class="text-xs text-gray-500">Rencana Baru</div>`);
        
        marker.on('dragend', async (e) => {
            const pos = e.target.getLatLng();
            try {
                await axios.post(`/ftth/devices/${d.id}/position`, {
                    latitude: pos.lat,
                    longitude: pos.lng
                });
            } catch (err) {
                alert('Gagal mengupdate posisi');
                e.target.setLatLng([d.latitude, d.longitude]);
            }
        });

        marker.addTo(layerGroups[d.device_type] || layerGroups.odp);

        // Draw Coverage
        if (d.device_type === 'odc') {
            L.circle([d.latitude, d.longitude], { radius: 500, color: typeInfo.color, fillColor: typeInfo.color, fillOpacity: 0.08, weight: 1, dashArray: '5,5' }).addTo(layerGroups['coverage']);
        } else if (d.device_type === 'odp') {
            L.circle([d.latitude, d.longitude], { radius: 150, color: typeInfo.color, fillColor: typeInfo.color, fillOpacity: 0.1, weight: 1 }).addTo(layerGroups['coverage']);
        }
    });

    // Render cable routes
    props.routes?.forEach(r => {
        if (!r.route_points) return;
        L.polyline(r.route_points, { color: '#3b82f6', weight: 3 })
         .bindPopup(`${formatDistance(r.distance)}`)
         .addTo(layerGroups.routes);
    });

    // Fit bounds if we have points
    const allPoints = [
        ...(props.devices || []).map(d => [d.latitude, d.longitude]),
        ...(props.routes || []).flatMap(r => r.route_points || [])
    ].filter(p => p && p[0]);

    if (allPoints.length > 0) {
        map.fitBounds(L.latLngBounds(allPoints).pad(0.1));
    }
};

const autoCalculateMaterials = () => {
    calculatedMaterials.value = [];
    
    let cableNeeded = estimatedCable.value;
    let poleNeeded = props.devices?.filter(d => d.device_type === 'tiang').length || 0;
    let odcNeeded = props.devices?.filter(d => d.device_type === 'odc').length || 0;
    let odpNeeded = props.devices?.filter(d => d.device_type === 'odp').length || 0;
    let closureNeeded = odcNeeded + odpNeeded;

    props.materials.forEach(m => {
        let qty = 0;
        const name = m.name.toLowerCase();
        
        if (name.includes('kabel') || name.includes('fo') || name.includes('fiber')) {
            qty = cableNeeded;
        } else if (name.includes('tiang')) {
            qty = poleNeeded;
        } else if (name.includes('odc')) {
            qty = odcNeeded;
        } else if (name.includes('odp')) {
            qty = odpNeeded;
        } else if (name.includes('closure') || name.includes('joint')) {
            qty = closureNeeded;
        } else if (name.includes('klem') || name.includes('claim')) {
            qty = poleNeeded * 2; // Estimasi 2 klem per tiang
        }

        if (qty > 0) {
            calculatedMaterials.value.push({
                id: m.id,
                name: m.name,
                category: m.category,
                unit: m.unit || 'pcs',
                stock: m.stock || 0,
                quantity: Math.ceil(qty)
            });
        }
    });
};

const openReview = () => {
    autoCalculateMaterials();
    showReviewModal.value = true;
};

const submitReview = () => {
    reviewForm.materials = calculatedMaterials.value.map(m => ({
        id: m.id,
        quantity: m.quantity
    }));

    reviewForm.post(`/ftth/${props.design.id}/review`, {
        preserveScroll: true,
        onSuccess: () => {
            showReviewModal.value = false;
        }
    });
};
</script>

<style>
@import 'leaflet/dist/leaflet.css';
.custom-marker { background: transparent !important; border: none !important; }
</style>
