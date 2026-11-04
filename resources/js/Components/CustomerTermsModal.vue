<template>
    <div v-if="show" class="fixed inset-0 z-[130] flex items-center justify-center p-2 sm:p-4 bg-slate-900/75 backdrop-blur-xs animate-fade-in">
        <div class="bg-white w-full max-w-4xl max-h-[96vh] rounded-3xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-indigo-500/20 border border-indigo-400/30 flex items-center justify-center text-indigo-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs sm:text-sm font-bold tracking-tight">Syarat &amp; Ketentuan Berlangganan (TTD Digital)</h3>
                            <span :class="['px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider', stageBadgeClass]">
                                {{ stage }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-300 truncate max-w-xs sm:max-w-md">
                            {{ customerData?.name || 'Pelanggan' }} <span v-if="customerData?.customer_code">({{ customerData.customer_code }})</span>
                        </p>
                    </div>
                </div>

                <button 
                    type="button" 
                    @click="closeModal"
                    class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 transition-colors cursor-pointer"
                    title="Tutup"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="py-16 flex flex-col items-center justify-center text-slate-400 gap-3">
                <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs font-semibold text-slate-600">Memuat data syarat &amp; ketentuan...</span>
            </div>

            <!-- Content Area (Scrollable) -->
            <div v-else class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-5 bg-slate-50/50">
                <!-- Status Banner: Already Signed -->
                <div v-if="existingAgreement && !isReSigning" class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/80 rounded-2xl p-4 sm:p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase mb-1">
                                    Sudah Ditandatangani
                                </div>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-800">
                                    Dokumen S&amp;K Sah ({{ existingAgreement.agreement_number }})
                                </h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Ditandatangani oleh <strong>{{ existingAgreement.customer_name }}</strong> (Pelanggan) &amp; 
                                    <strong>{{ existingAgreement.employee_name }}</strong> (Petugas) pada {{ formatDate(existingAgreement.created_at) }}.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a 
                                :href="`/agreements/${existingAgreement.id}/print`" 
                                target="_blank"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs hover:shadow flex items-center gap-1.5 cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                <span>Cetak / Lihat Dokumen</span>
                            </a>

                            <button 
                                type="button" 
                                @click="startReSigning"
                                class="px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-semibold transition-all cursor-pointer"
                            >
                                TTD Ulang
                            </button>
                        </div>
                    </div>

                    <!-- Signature Preview Box -->
                    <div class="mt-4 pt-4 border-t border-emerald-200/60 grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="p-3 bg-white/90 rounded-xl border border-emerald-100 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Petugas / Teknisi</div>
                            <img v-if="existingAgreement.employee_signature" :src="existingAgreement.employee_signature" class="h-16 mx-auto object-contain" alt="TTD Petugas" />
                            <div class="text-xs font-bold text-slate-700 mt-1 truncate">{{ existingAgreement.employee_name }}</div>
                        </div>
                        <div class="p-3 bg-white/90 rounded-xl border border-emerald-100 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Pelanggan</div>
                            <img v-if="existingAgreement.customer_signature" :src="existingAgreement.customer_signature" class="h-16 mx-auto object-contain" alt="TTD Pelanggan" />
                            <div class="text-xs font-bold text-slate-700 mt-1 truncate">{{ existingAgreement.customer_name }}</div>
                        </div>
                    </div>
                </div>

                <!-- Re-signing notice -->
                <div v-if="isReSigning" class="bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-2xl text-xs font-medium flex items-center justify-between">
                    <span>Sedang memperbarui tanda tangan. Dokumen sebelumnya akan ditimpa dengan draf baru ini.</span>
                    <button type="button" @click="isReSigning = false" class="text-amber-700 font-bold hover:underline cursor-pointer">Batal</button>
                </div>

                <!-- Form & Clauses (Only visible if not signed or re-signing) -->
                <div v-if="!existingAgreement || isReSigning" class="space-y-5">
                    <!-- Ringkasan Info Pelanggan -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div>
                                <span class="text-slate-400 text-[11px] block">Pelanggan</span>
                                <span class="font-bold text-slate-800">{{ customerData?.name || '-' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Paket Langganan</span>
                                <span class="font-bold text-slate-800">{{ customerData?.package_name || '-' }} ({{ customerData?.package_speed || '-' }})</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Tarif Bulanan</span>
                                <span class="font-bold text-indigo-600">Rp {{ formatNumber(customerData?.price) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 text-[11px] block">Wilayah / Area</span>
                                <span class="font-bold text-slate-800">{{ customerData?.area || '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dokumen Klausul Syarat & Ketentuan (Scrollable Box) -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Isi Perjanjian &amp; Klausul S&amp;K</span>
                                <span class="text-[10px] text-slate-400">(Teks otomatis disesuaikan data pelanggan)</span>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-500">Scroll ke bawah untuk membaca</span>
                        </div>
                        <div class="p-4 sm:p-5 max-h-64 overflow-y-auto text-xs text-slate-700 leading-relaxed terms-box space-y-3 font-sans" v-html="renderedContent"></div>
                    </div>

                    <!-- Checkbox Persetujuan -->
                    <div class="p-3.5 bg-indigo-50/70 rounded-2xl border border-indigo-100 flex items-start gap-3">
                        <input 
                            type="checkbox" 
                            id="agreeCheckbox" 
                            v-model="hasAgreed"
                            class="mt-0.5 w-4 h-4 text-indigo-600 rounded-md border-indigo-300 focus:ring-indigo-500 cursor-pointer"
                        />
                        <label for="agreeCheckbox" class="text-xs text-indigo-950 font-medium cursor-pointer leading-tight select-none">
                            Saya dan Pelanggan menyatakan telah membaca, memahami, dan menyetujui seluruh klausul Syarat &amp; Ketentuan Berlangganan di atas tanpa paksaan dari pihak mana pun.
                        </label>
                    </div>

                    <!-- Dual Signature Pads Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- 1. Pad Tanda Tangan Petugas / Karyawan -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span>Tanda Tangan Petugas / Teknisi</span>
                                    </h5>
                                    <p class="text-[10px] text-slate-400">Goreskan tanda tangan manual di kotak</p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="clearEmployeeCanvas"
                                    class="text-[11px] font-bold text-rose-500 hover:text-rose-700 hover:bg-rose-50 px-2 py-1 rounded-lg transition-colors cursor-pointer"
                                >
                                    Hapus
                                </button>
                            </div>

                            <!-- Input Nama Petugas -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Petugas</label>
                                <input 
                                    type="text" 
                                    v-model="employeeName" 
                                    class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    placeholder="Nama Karyawan..." 
                                    required 
                                />
                            </div>

                            <!-- Canvas Petugas -->
                            <div class="relative bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 overflow-hidden touch-none">
                                <canvas 
                                    ref="employeeCanvasRef" 
                                    class="w-full h-36 block cursor-crosshair bg-white"
                                    @mousedown="startDrawingEmployee"
                                    @mousemove="drawEmployee"
                                    @mouseup="stopDrawingEmployee"
                                    @mouseleave="stopDrawingEmployee"
                                    @touchstart.passive="false"
                                    @touchmove.passive="false"
                                    @touchend.passive="false"
                                ></canvas>
                                <div v-if="!employeeHasDrawn" class="absolute inset-0 pointer-events-none flex items-center justify-center text-slate-300 text-xs font-medium italic">
                                    Sentuh / gambar tanda tangan di sini
                                </div>
                            </div>
                        </div>

                        <!-- 2. Pad Tanda Tangan Pelanggan -->
                        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span>Tanda Tangan Pelanggan</span>
                                    </h5>
                                    <p class="text-[10px] text-slate-400">Pelanggan menandatangani langsung di HP</p>
                                </div>
                                <button 
                                    type="button" 
                                    @click="clearCustomerCanvas"
                                    class="text-[11px] font-bold text-rose-500 hover:text-rose-700 hover:bg-rose-50 px-2 py-1 rounded-lg transition-colors cursor-pointer"
                                >
                                    Hapus
                                </button>
                            </div>

                            <!-- Input Nama Pelanggan -->
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nama Pelanggan</label>
                                <input 
                                    type="text" 
                                    v-model="customerName" 
                                    class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500" 
                                    placeholder="Nama Pelanggan..." 
                                    required 
                                />
                            </div>

                            <!-- Canvas Pelanggan -->
                            <div class="relative bg-slate-50 rounded-xl border-2 border-dashed border-slate-200 overflow-hidden touch-none">
                                <canvas 
                                    ref="customerCanvasRef" 
                                    class="w-full h-36 block cursor-crosshair bg-white"
                                    @mousedown="startDrawingCustomer"
                                    @mousemove="drawCustomer"
                                    @mouseup="stopDrawingCustomer"
                                    @mouseleave="stopDrawingCustomer"
                                    @touchstart.passive="false"
                                    @touchmove.passive="false"
                                    @touchend.passive="false"
                                ></canvas>
                                <div v-if="!customerHasDrawn" class="absolute inset-0 pointer-events-none flex items-center justify-center text-slate-300 text-xs font-medium italic">
                                    Pelanggan tanda tangan manual di sini
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Tambahan (Opsional) -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                            Catatan Tambahan Saat TTD (Opsional)
                        </label>
                        <input 
                            type="text" 
                            v-model="agreementNotes" 
                            class="w-full text-xs bg-white border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Contoh: Disepakati pembayaran via transfer sebelum aktivasi"
                        />
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-5 py-3.5 bg-slate-100/90 border-t border-slate-200/80 flex items-center justify-between shrink-0">
                <button 
                    type="button" 
                    @click="closeModal"
                    class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-200 text-xs font-semibold transition-colors cursor-pointer"
                >
                    Tutup
                </button>

                <div v-if="!existingAgreement || isReSigning" class="flex items-center gap-2">
                    <button 
                        type="button" 
                        @click="submitAgreement"
                        :disabled="isSubmitting || !hasAgreed || !employeeHasDrawn || !customerHasDrawn"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold transition-all shadow-md hover:shadow-lg flex items-center gap-2 cursor-pointer"
                    >
                        <svg v-if="isSubmitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ isSubmitting ? 'Menyimpan TTD...' : 'Simpan & Sahkan Perjanjian S&K' }}</span>
                    </button>
                </div>

                <div v-else>
                    <a 
                        :href="`/agreements/${existingAgreement.id}/print`" 
                        target="_blank"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Cetak Dokumen S&amp;K</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    customerId: {
        type: [Number, String],
        required: true,
    },
    stage: {
        type: String,
        default: 'booking', // booking, survey, installation
    },
});

const emit = defineEmits(['update:show', 'signed']);

const loading = ref(false);
const isSubmitting = ref(false);
const isReSigning = ref(false);

const customerData = ref(null);
const templateData = ref(null);
const renderedContent = ref('');
const existingAgreement = ref(null);

const employeeName = ref('');
const customerName = ref('');
const agreementNotes = ref('');
const hasAgreed = ref(false);

// Canvas References
const employeeCanvasRef = ref(null);
const customerCanvasRef = ref(null);
const employeeHasDrawn = ref(false);
const customerHasDrawn = ref(false);

let employeeCtx = null;
let customerCtx = null;
let isDrawingEmployee = false;
let isDrawingCustomer = false;

const stageBadgeClass = computed(() => {
    switch (props.stage) {
        case 'booking': return 'bg-blue-500/20 text-blue-300 border border-blue-400/30';
        case 'survey': return 'bg-amber-500/20 text-amber-300 border border-amber-400/30';
        case 'installation': return 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30';
        default: return 'bg-indigo-500/20 text-indigo-300 border border-indigo-400/30';
    }
});

function formatNumber(num) {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function loadData() {
    if (!props.customerId) return;
    loading.value = true;
    isReSigning.value = false;
    hasAgreed.value = false;

    try {
        const response = await axios.get(`/customers/${props.customerId}/agreements/current?stage=${props.stage}`);
        const data = response.data;

        customerData.value = data.customer;
        templateData.value = data.template;
        renderedContent.value = data.rendered_content;
        existingAgreement.value = data.existing_agreement;

        employeeName.value = data.current_user?.name || 'Petugas Lapangan';
        customerName.value = data.customer?.name || '';

        if (!existingAgreement.value) {
            nextTick(() => {
                initCanvases();
            });
        }
    } catch (e) {
        console.error('Failed to load terms data:', e);
        alert('Gagal memuat data syarat & ketentuan: ' + (e.response?.data?.message || e.message));
    } finally {
        loading.value = false;
    }
}

function startReSigning() {
    isReSigning.value = true;
    hasAgreed.value = false;
    nextTick(() => {
        initCanvases();
    });
}

function closeModal() {
    emit('update:show', false);
}

// ── Canvas Setup & Touch Handling ─────────────────────────────
function setupCanvas(canvas) {
    if (!canvas) return null;
    const rect = canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    
    // Set actual canvas pixel size
    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;

    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.strokeStyle = '#0f172a';
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    return ctx;
}

function initCanvases() {
    if (employeeCanvasRef.value) {
        employeeCtx = setupCanvas(employeeCanvasRef.value);
        attachTouchListeners(employeeCanvasRef.value, 'employee');
        clearEmployeeCanvas();
    }
    if (customerCanvasRef.value) {
        customerCtx = setupCanvas(customerCanvasRef.value);
        attachTouchListeners(customerCanvasRef.value, 'customer');
        clearCustomerCanvas();
    }
}

function getPos(canvas, event) {
    const rect = canvas.getBoundingClientRect();
    const clientX = event.clientX !== undefined ? event.clientX : event.touches?.[0]?.clientX;
    const clientY = event.clientY !== undefined ? event.clientY : event.touches?.[0]?.clientY;
    return {
        x: clientX - rect.left,
        y: clientY - rect.top,
    };
}

// Touch Event Handlers for Mobile (Prevents page scroll during signature)
function attachTouchListeners(canvas, type) {
    if (!canvas || canvas._hasTouchListeners) return;
    canvas._hasTouchListeners = true;

    const onTouchStart = (e) => {
        e.preventDefault();
        const pos = getPos(canvas, e);
        if (type === 'employee') {
            isDrawingEmployee = true;
            employeeHasDrawn.value = true;
            employeeCtx?.beginPath();
            employeeCtx?.moveTo(pos.x, pos.y);
        } else {
            isDrawingCustomer = true;
            customerHasDrawn.value = true;
            customerCtx?.beginPath();
            customerCtx?.moveTo(pos.x, pos.y);
        }
    };

    const onTouchMove = (e) => {
        e.preventDefault();
        const pos = getPos(canvas, e);
        if (type === 'employee' && isDrawingEmployee) {
            employeeCtx?.lineTo(pos.x, pos.y);
            employeeCtx?.stroke();
        } else if (type === 'customer' && isDrawingCustomer) {
            customerCtx?.lineTo(pos.x, pos.y);
            customerCtx?.stroke();
        }
    };

    const onTouchEnd = (e) => {
        e.preventDefault();
        if (type === 'employee') {
            isDrawingEmployee = false;
        } else {
            isDrawingCustomer = false;
        }
    };

    canvas.addEventListener('touchstart', onTouchStart, { passive: false });
    canvas.addEventListener('touchmove', onTouchMove, { passive: false });
    canvas.addEventListener('touchend', onTouchEnd, { passive: false });
    canvas.addEventListener('touchcancel', onTouchEnd, { passive: false });
}

// Mouse Event Handlers
function startDrawingEmployee(e) {
    isDrawingEmployee = true;
    employeeHasDrawn.value = true;
    const pos = getPos(employeeCanvasRef.value, e);
    employeeCtx?.beginPath();
    employeeCtx?.moveTo(pos.x, pos.y);
}
function drawEmployee(e) {
    if (!isDrawingEmployee) return;
    const pos = getPos(employeeCanvasRef.value, e);
    employeeCtx?.lineTo(pos.x, pos.y);
    employeeCtx?.stroke();
}
function stopDrawingEmployee() {
    isDrawingEmployee = false;
}
function clearEmployeeCanvas() {
    if (!employeeCanvasRef.value || !employeeCtx) return;
    const rect = employeeCanvasRef.value.getBoundingClientRect();
    employeeCtx.clearRect(0, 0, rect.width, rect.height);
    employeeCtx.beginPath();
    employeeHasDrawn.value = false;
}

function startDrawingCustomer(e) {
    isDrawingCustomer = true;
    customerHasDrawn.value = true;
    const pos = getPos(customerCanvasRef.value, e);
    customerCtx?.beginPath();
    customerCtx?.moveTo(pos.x, pos.y);
}
function drawCustomer(e) {
    if (!isDrawingCustomer) return;
    const pos = getPos(customerCanvasRef.value, e);
    customerCtx?.lineTo(pos.x, pos.y);
    customerCtx?.stroke();
}
function stopDrawingCustomer() {
    isDrawingCustomer = false;
}
function clearCustomerCanvas() {
    if (!customerCanvasRef.value || !customerCtx) return;
    const rect = customerCanvasRef.value.getBoundingClientRect();
    customerCtx.clearRect(0, 0, rect.width, rect.height);
    customerCtx.beginPath();
    customerHasDrawn.value = false;
}

// Submit Agreement & Signatures
async function submitAgreement() {
    if (!employeeHasDrawn.value || !customerHasDrawn.value) {
        alert('Kedua tanda tangan (Petugas & Pelanggan) wajib dibubuhkan sebelum menyimpan.');
        return;
    }
    if (!hasAgreed.value) {
        alert('Silakan centang persetujuan syarat dan ketentuan terlebih dahulu.');
        return;
    }

    isSubmitting.value = true;

    try {
        const employeeSig = employeeCanvasRef.value?.toDataURL('image/png');
        const customerSig = customerCanvasRef.value?.toDataURL('image/png');

        const payload = {
            stage: props.stage,
            terms_template_id: templateData.value?.id,
            title: templateData.value?.title || 'Surat Perjanjian & Syarat Ketentuan Berlangganan',
            content_snapshot: renderedContent.value,
            customer_name: customerName.value,
            customer_signature: customerSig,
            employee_name: employeeName.value,
            employee_signature: employeeSig,
            notes: agreementNotes.value,
        };

        const res = await axios.post(`/customers/${props.customerId}/agreements`, payload);
        
        if (res.data.success) {
            existingAgreement.value = res.data.agreement;
            isReSigning.value = false;
            emit('signed', res.data.agreement);
            alert('Surat Perjanjian & Tanda Tangan Digital berhasil disimpan.');
        } else {
            alert('Gagal: ' + res.data.message);
        }
    } catch (e) {
        console.error('Error saving agreement:', e);
        alert('Gagal menyimpan tanda tangan: ' + (e.response?.data?.message || e.message));
    } finally {
        isSubmitting.value = false;
    }
}

watch(() => props.show, (newVal) => {
    if (newVal) {
        loadData();
    }
});

watch(() => props.customerId, () => {
    if (props.show) {
        loadData();
    }
});
</script>

<style scoped>
.terms-box :deep(h3) {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0f172a;
    margin-top: 0.75rem;
    margin-bottom: 0.25rem;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.2rem;
    text-transform: uppercase;
}
.terms-box :deep(ol) {
    padding-left: 1.25rem;
    margin-top: 0.25rem;
    margin-bottom: 0.5rem;
}
.terms-box :deep(li) {
    margin-bottom: 0.25rem;
    color: #334155;
}
.terms-box :deep(p) {
    margin-top: 0.25rem;
    margin-bottom: 0.5rem;
    color: #334155;
}
</style>

