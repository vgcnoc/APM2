<template>
    <AppLayout title="Template Syarat & Ketentuan" subtitle="Kelola draf surat perjanjian, klausul berlangganan, dan tanda tangan digital">
        <div class="max-w-[1600px] w-full mx-auto space-y-6 font-sans">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Header Info Card -->
            <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-medium mb-3">
                            <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                            Dokumen Hukum & Kepatuhan Berlangganan
                        </div>
                        <h2 class="text-xl md:text-2xl font-bold tracking-tight">Template Syarat &amp; Ketentuan Berlangganan</h2>
                        <p class="text-xs md:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                            Template ini otomatis digunakan saat pendaftaran di menu <strong>Booking</strong>, verifikasi lapangan di <strong>Survey</strong>, dan serah terima pemasangan di <strong>Instalasi</strong>. Tanda tangan digital ditangkap langsung lewat layar HP atau PC.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <button 
                            type="button" 
                            @click="showPreviewModal = true"
                            class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all flex items-center gap-2 border border-white/10 cursor-pointer shadow-sm"
                        >
                            <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <span>Live Preview Dokumen</span>
                        </button>

                        <button 
                            type="button" 
                            @click="confirmResetToDefault"
                            class="px-4 py-2.5 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-200 text-xs font-bold transition-all flex items-center gap-2 border border-red-500/30 cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset ke Standar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Form Edit Template -->
            <form @submit.prevent="submitForm" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Kolom Kiri: Editor Utama (Col span 2) -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Judul Dokumen Perjanjian <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    v-model="form.title" 
                                    class="w-full text-sm font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                                    placeholder="Contoh: SURAT PERJANJIAN & SYARAT KETENTUAN BERLANGGANAN LAYANAN INTERNET"
                                    required
                                />
                            </div>

                            <!-- Variabel Tag Buttons -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Sisipkan Variabel Otomatis Pelanggan
                                    </label>
                                    <span class="text-[11px] text-slate-400">Klik tag untuk memasukkan ke posisi kursor</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 p-3 bg-slate-50 border border-slate-200/70 rounded-2xl">
                                    <button 
                                        v-for="tok in tokens" 
                                        :key="tok.token"
                                        type="button"
                                        @click="insertToken(tok.token)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white hover:bg-indigo-50 text-indigo-700 hover:text-indigo-800 border border-indigo-200/80 hover:border-indigo-300 rounded-lg text-xs font-mono font-medium shadow-2xs hover:shadow-xs transition-all cursor-pointer"
                                        :title="tok.label + ' (Contoh: ' + tok.example + ')'"
                                    >
                                        <span class="text-[10px] text-indigo-400 font-sans font-bold">+</span>
                                        <span>{{ tok.token }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Text Editor / Content -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                        Isi Klausul Syarat &amp; Ketentuan (Format HTML / Teks) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="text-[11px] text-slate-400">
                                        Mendukung tag HTML standar: &lt;h3&gt;, &lt;p&gt;, &lt;strong&gt;, &lt;ol&gt;, &lt;li&gt;
                                    </div>
                                </div>
                                <textarea 
                                    ref="contentInputRef"
                                    v-model="form.content" 
                                    rows="18" 
                                    class="w-full font-mono text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-2xl p-4 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all leading-relaxed"
                                    placeholder="Tuliskan pasal-pasal perjanjian syarat & ketentuan berlangganan..."
                                    required
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Pengaturan & Konfigurasi Signature (Col span 1) -->
                    <div class="space-y-6">
                        <!-- Card Pengaturan Kewajiban Tanda Tangan -->
                        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm space-y-5">
                            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                <span>Konfigurasi Penandatangan</span>
                            </h3>

                            <div class="space-y-4">
                                <!-- Switch TTD Karyawan / Petugas -->
                                <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                                    <div class="pr-3">
                                        <div class="text-xs font-bold text-slate-800">Wajib TTD Petugas / Karyawan</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Teknisi / Admin yang bertugas wajib menandatangani di HP</div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.requires_employee_signature" class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>

                                <!-- Switch TTD Pelanggan -->
                                <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                                    <div class="pr-3">
                                        <div class="text-xs font-bold text-slate-800">Wajib TTD Pelanggan</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Pelanggan menandatangani langsung di layar smartphone / tablet</div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.requires_customer_signature" class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>

                                <!-- Switch Status Aktif -->
                                <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                                    <div class="pr-3">
                                        <div class="text-xs font-bold text-slate-800">Status Template Aktif</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">Tampilkan pada pilihan aksi pelanggan</div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Catatan / Keterangan Internal
                                </label>
                                <textarea 
                                    v-model="form.notes" 
                                    rows="3" 
                                    class="w-full text-xs text-slate-800 bg-slate-50 border border-slate-200 rounded-2xl p-3 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                                    placeholder="Catatan versi atau revisi..."
                                ></textarea>
                            </div>
                        </div>

                        <!-- Card Integrasi Menu Aksi -->
                        <div class="bg-indigo-50/60 rounded-3xl p-5 border border-indigo-100/80 space-y-3">
                            <h4 class="text-xs font-bold text-indigo-950 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <span>Aksi Terintegrasi Otomatis</span>
                            </h4>
                            <p class="text-[11px] text-indigo-900/80 leading-relaxed">
                                Template ini langsung aktif dan dapat diakses pada menu:
                            </p>
                            <ul class="text-xs space-y-2 text-indigo-950 font-medium">
                                <li class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    <span><strong>Aksi Booking:</strong> TTD S&K saat registrasi</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span><strong>Aksi Survey:</strong> TTD kelayakan & persetujuan jalur kabel</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span><strong>Aksi Instalasi:</strong> TTD serah terima perangkat ONT & sinyal</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="pt-2">
                            <button 
                                type="submit" 
                                :disabled="isSubmitting"
                                class="w-full py-3.5 px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider transition-all shadow-md hover:shadow-lg disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <svg v-if="isSubmitting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan Template' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Modal Live Preview -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">
            <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-fade-in-up">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Pratinjau Hasil Render Surat Perjanjian S&amp;K</h3>
                            <p class="text-[11px] text-slate-400">Variabel disimulasikan dengan contoh data pelanggan nyata</p>
                        </div>
                    </div>
                    <button 
                        type="button" 
                        @click="showPreviewModal = false"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-6 bg-slate-50/40">
                    <!-- Lembar Dokumen Kertas -->
                    <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xs max-w-3xl mx-auto space-y-6">
                        <!-- Header Preview -->
                        <div class="text-center border-b border-slate-200 pb-5">
                            <div class="text-xs uppercase font-bold tracking-widest text-indigo-600 mb-1">{{ props.companyName || 'VIZBILL ISP' }}</div>
                            <h2 class="text-base font-bold text-slate-900 uppercase">{{ form.title }}</h2>
                            <p class="text-[11px] text-slate-500 mt-1">Nomor Dokumen: SK-BKG-202611-0001 (Simulasi)</p>
                        </div>

                        <!-- Rendered Clauses -->
                        <div class="prose prose-sm max-w-none text-xs text-slate-700 leading-relaxed terms-preview-content" v-html="renderedPreviewContent"></div>

                        <!-- Mock Signature Section -->
                        <div class="pt-6 border-t border-slate-200 grid grid-cols-2 gap-6 text-center">
                            <div class="p-4 border border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-8">Pihak Petugas / Teknisi</div>
                                <div class="h-14 flex items-center justify-center text-xs font-serif text-slate-400 italic">
                                    [ Tanda Tangan Digital Petugas ]
                                </div>
                                <div class="text-xs font-bold text-slate-800 border-t border-slate-300 pt-1 mt-2">Super Admin (Teknisi)</div>
                            </div>
                            <div class="p-4 border border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-8">Pihak Pelanggan</div>
                                <div class="h-14 flex items-center justify-center text-xs font-serif text-slate-400 italic">
                                    [ Tanda Tangan Digital Pelanggan ]
                                </div>
                                <div class="text-xs font-bold text-slate-800 border-t border-slate-300 pt-1 mt-2">Budi Santoso (Pelanggan)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/70 flex justify-end">
                    <button 
                        type="button" 
                        @click="showPreviewModal = false"
                        class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition-all cursor-pointer"
                    >
                        Tutup Pratinjau
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    currentTemplate: Object,
    allTemplates: Array,
    tokens: Array,
    companyName: String,
});

const isSubmitting = ref(false);
const showPreviewModal = ref(false);
const contentInputRef = ref(null);

const form = useForm({
    title: props.currentTemplate?.title || 'SURAT PERJANJIAN BERLANGGANAN INTERNET PT VIRUZS GLOBAL CONNECTION DENGAN PELANGGAN',
    type: props.currentTemplate?.type || 'general',
    content: props.currentTemplate?.content || '',
    requires_employee_signature: props.currentTemplate?.requires_employee_signature ?? true,
    requires_customer_signature: props.currentTemplate?.requires_customer_signature ?? true,
    is_active: props.currentTemplate?.is_active ?? true,
    notes: props.currentTemplate?.notes || '',
});

function insertToken(token) {
    if (!contentInputRef.value) {
        form.content += ' ' + token + ' ';
        return;
    }
    const el = contentInputRef.value;
    const start = el.selectionStart || 0;
    const end = el.selectionEnd || 0;
    const text = form.content;
    form.content = text.substring(0, start) + token + text.substring(end);
    
    setTimeout(() => {
        el.focus();
        el.selectionStart = el.selectionEnd = start + token.length;
    }, 50);
}

const renderedPreviewContent = computed(() => {
    let raw = form.content || '';
    const sampleData = {
        '{nama_pelanggan}': 'Siti Fatimah',
        '{nomor_layanan}': 'CUS-002568',
        '{id_pelanggan}': 'CUS-002568',
        '{nik_ktp}': '3202123456780001',
        '{telepon}': '085718293547',
        '{email}': 'siti.fatimah@example.com',
        '{alamat}': 'Kp. Sukamulya RT 01/02 Kec. Cikembar Sukabumi',
        '{area}': 'Wilayah BC 1',
        '{paket}': 'INTERNET HEBAT 20 MBPS',
        '{kecepatan}': '20 Mbps',
        '{tarif}': 'Rp 117.118',
        '{biaya_pasang}': 'Rp 120.000,-',
        '{biaya_registrasi}': 'Rp 120.000,-',
        '{client_area_url}': 'https://bill.viruzs.my.id',
        '{link_aplikasi}': 'https://bill.viruzs.my.id',
        '{url_aplikasi}': 'https://bill.viruzs.my.id',
        '{app_url}': 'https://bill.viruzs.my.id',
        '{tanggal}': '05 November 2026',
        '{perusahaan}': props.companyName || 'PT VIRUZS GLOBAL CONNECTION',
        '{petugas}': 'Super Admin',
    };

    for (const [key, val] of Object.entries(sampleData)) {
        raw = raw.split(key).join(val);
    }
    return raw;
});

function submitForm() {
    isSubmitting.value = true;
    form.post('/settings/terms-conditions', {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}

function confirmResetToDefault() {
    if (confirm('Apakah Anda yakin ingin mengembalikan isi Syarat & Ketentuan ke standar awal bawaan sistem? Perubahan yang belum disimpan akan hilang.')) {
        router.post('/settings/terms-conditions/reset', {}, {
            preserveScroll: true,
            onSuccess: () => {
                location.reload();
            }
        });
    }
}
</script>

<style scoped>
.terms-preview-content :deep(h3) {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1e293b;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 0.25rem;
}
.terms-preview-content :deep(ol) {
    padding-left: 1.25rem;
    margin-bottom: 0.75rem;
}
.terms-preview-content :deep(li) {
    margin-bottom: 0.35rem;
    color: #334155;
}
.terms-preview-content :deep(p) {
    margin-bottom: 0.5rem;
    color: #334155;
}
</style>

