<template>
    <AppLayout title="Pengaturan Billing & Invoice" subtitle="Konfigurasi otomatisasi siklus tagihan, jatuh tempo, dan isolir layanan pelanggan">
        <div class="max-w-6xl flex flex-col lg:flex-row gap-6">
            <!-- Form Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:w-2/3">
                <div class="p-6 sm:p-8">
                    <form @submit.prevent="submit" class="space-y-8">
                        
                        <!-- Tipe Billing -->
                        <div>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Siklus Penagihan</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-300 hover:shadow-md" :class="form.billing_type === 'prabayar' ? 'border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600' : 'border-gray-200 bg-white'">
                                    <input type="radio" name="billing_type" value="prabayar" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-bold" :class="form.billing_type === 'prabayar' ? 'text-indigo-900' : 'text-gray-900'">Prabayar</span>
                                            <span class="mt-1 flex items-center text-xs" :class="form.billing_type === 'prabayar' ? 'text-indigo-700' : 'text-gray-500'">Bayar dulu baru pakai (Prepaid)</span>
                                        </span>
                                    </span>
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_type === 'prabayar' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                            <svg v-if="form.billing_type === 'prabayar'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-300 hover:shadow-md" :class="form.billing_type === 'pascabayar' ? 'border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600' : 'border-gray-200 bg-white'">
                                    <input type="radio" name="billing_type" value="pascabayar" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-bold" :class="form.billing_type === 'pascabayar' ? 'text-indigo-900' : 'text-gray-900'">Pasca Bayar</span>
                                            <span class="mt-1 flex items-center text-xs" :class="form.billing_type === 'pascabayar' ? 'text-indigo-700' : 'text-gray-500'">Pakai dulu baru bayar (Postpaid)</span>
                                        </span>
                                    </span>
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_type === 'pascabayar' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                            <svg v-if="form.billing_type === 'pascabayar'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </div>
                                </label>
                                
                                <label class="relative flex cursor-pointer rounded-xl border p-4 shadow-sm focus:outline-none transition-all duration-200 hover:border-indigo-300 hover:shadow-md" :class="form.billing_type === 'prorata' ? 'border-indigo-600 bg-indigo-50/30 ring-1 ring-indigo-600' : 'border-gray-200 bg-white'">
                                    <input type="radio" name="billing_type" value="prorata" v-model="form.billing_type" class="sr-only">
                                    <span class="flex flex-1">
                                        <span class="flex flex-col">
                                            <span class="block text-sm font-bold" :class="form.billing_type === 'prorata' ? 'text-indigo-900' : 'text-gray-900'">Prorata</span>
                                            <span class="mt-1 flex items-center text-xs" :class="form.billing_type === 'prorata' ? 'text-indigo-700' : 'text-gray-500'">Hitungan per hari (Cut-off date)</span>
                                        </span>
                                    </span>
                                    <div class="flex items-center">
                                        <div class="w-5 h-5 rounded-full border flex items-center justify-center transition-colors" :class="form.billing_type === 'prorata' ? 'border-indigo-600 bg-indigo-600' : 'border-gray-300'">
                                            <svg v-if="form.billing_type === 'prorata'" class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div v-if="form.errors.billing_type" class="mt-1 text-sm text-red-600">{{ form.errors.billing_type }}</div>
                        </div>
                        
                        <hr class="border-gray-100 border-dashed">

                        <!-- Timeline Pengaturan -->
                        <div>
                            <div class="flex items-center gap-2 mb-6">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Timeline & Penjadwalan</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                <!-- Tanggal Terbit -->
                                <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                    <label class="block text-sm font-bold text-gray-900 mb-1">Tanggal Terbit Invoice</label>
                                    <p class="text-[11px] text-gray-500 mb-3 leading-relaxed">Tanggal rutin di setiap bulannya dimana sistem akan membuat invoice baru.</p>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <input type="number" v-model="form.invoice_issue_date" min="1" max="28" class="form-input block w-full rounded-lg border-gray-300 pl-10 pr-12 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 text-xs font-medium">Tiap bln</span>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.invoice_issue_date" class="mt-1 text-sm text-red-600">{{ form.errors.invoice_issue_date }}</div>
                                </div>

                                <!-- Jatuh Tempo -->
                                <div class="bg-gray-50/50 p-4 rounded-xl border border-gray-100">
                                    <label class="block text-sm font-bold text-gray-900 mb-1">Batas Jatuh Tempo</label>
                                    <p class="text-[11px] text-gray-500 mb-3 leading-relaxed">Lama waktu (hari) yang diberikan kepada pelanggan untuk melunasi sejak terbit.</p>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <input type="number" v-model="form.due_date_days" min="0" class="form-input block w-full rounded-lg border-gray-300 pl-10 pr-16 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-medium" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 text-xs font-medium">Hari</span>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.due_date_days" class="mt-1 text-sm text-red-600">{{ form.errors.due_date_days }}</div>
                                </div>

                                <!-- Isolir -->
                                <div class="bg-red-50/30 p-4 rounded-xl border border-red-100 md:col-span-2">
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-sm font-bold text-gray-900 mb-1">Masa Tenggang & Isolir Otomatis</label>
                                            <p class="text-[11px] text-gray-500 mb-3 leading-relaxed max-w-xl">Layanan internet pelanggan akan di-suspend / isolir secara otomatis apabila menunggak sekian hari setelah melewati batas jatuh tempo.</p>
                                            
                                            <div class="flex items-center gap-3">
                                                <div class="relative w-40">
                                                    <input type="number" v-model="form.isolate_days" min="0" class="form-input block w-full rounded-lg border-red-200 pl-4 pr-16 focus:border-red-500 focus:ring-red-500 sm:text-sm font-medium bg-white" />
                                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                        <span class="text-red-500 text-xs font-medium">Hari lewat</span>
                                                    </div>
                                                </div>
                                                <span class="text-xs text-gray-500 italic" v-if="form.isolate_days == 0">Langsung isolir di hari H jatuh tempo</span>
                                            </div>
                                            <div v-if="form.errors.isolate_days" class="mt-1 text-sm text-red-600">{{ form.errors.isolate_days }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                            <Transition name="fade">
                                <p v-if="form.recentlySuccessful" class="text-sm font-medium text-emerald-600 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Pengaturan berhasil disimpan.
                                </p>
                                <span v-else></span>
                            </Transition>
                            
                            <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center px-6 py-2.5 border border-transparent text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-sm shadow-indigo-200 disabled:opacity-75 disabled:cursor-wait">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Konfigurasi' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Simulation Panel -->
            <div class="lg:w-1/3">
                <div class="bg-gradient-to-br from-indigo-900 to-indigo-800 rounded-2xl shadow-lg border border-indigo-700 text-white p-6 sticky top-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm">
                            <svg class="w-5 h-5 text-indigo-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Simulasi AI</h3>
                            <p class="text-xs text-indigo-200">Berdasarkan pengaturan saat ini</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                <span class="text-xs font-medium text-indigo-100 uppercase tracking-wider">Terbit Tagihan</span>
                            </div>
                            <p class="text-sm font-semibold pl-5">Tgl {{ form.invoice_issue_date }} {{ nextMonthName }}</p>
                            <p class="text-[10px] text-indigo-300 pl-5 mt-0.5">Sistem men-generate invoice pelanggan</p>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-xl p-4">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                                <span class="text-xs font-medium text-indigo-100 uppercase tracking-wider">Jatuh Tempo</span>
                            </div>
                            <p class="text-sm font-semibold pl-5">Tgl {{ dueDateFormatted }}</p>
                            <p class="text-[10px] text-indigo-300 pl-5 mt-0.5">Batas akhir pembayaran tanpa denda/suspend</p>
                        </div>

                        <div class="bg-white/10 backdrop-blur-md border border-red-500/30 rounded-xl p-4 relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-16 h-16 bg-red-500/10 rounded-bl-full"></div>
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></div>
                                <span class="text-xs font-medium text-red-200 uppercase tracking-wider">Isolir Layanan</span>
                            </div>
                            <p class="text-sm font-bold text-white pl-5">Tgl {{ isolateDateFormatted }}</p>
                            <p class="text-[10px] text-red-200 pl-5 mt-0.5">Internet pelanggan otomatis terputus</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-5 border-t border-indigo-700/50">
                        <p class="text-[10px] text-indigo-300 leading-relaxed">
                            <span class="font-semibold text-white">Catatan:</span> Simulasi ini mengasumsikan siklus penagihan bulan depan. Sistem cron akan berjalan otomatis setiap harinya pada pukul 00:01 waktu server.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    billing_type: String,
    invoice_issue_date: String,
    due_date_days: String,
    isolate_days: String,
});

const form = useForm({
    billing_type: props.billing_type || 'prabayar',
    invoice_issue_date: props.invoice_issue_date || '1',
    due_date_days: props.due_date_days || '7',
    isolate_days: props.isolate_days || '3',
});

// Simulation Logic
const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const today = new Date();
// We'll simulate for the next month
const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

const nextMonthName = computed(() => {
    return monthNames[nextMonth.getMonth()] + ' ' + nextMonth.getFullYear();
});

const issueDateObj = computed(() => {
    let day = parseInt(form.invoice_issue_date) || 1;
    if(day > 28) day = 28;
    return new Date(nextMonth.getFullYear(), nextMonth.getMonth(), day);
});

const dueDateObj = computed(() => {
    const d = new Date(issueDateObj.value);
    d.setDate(d.getDate() + (parseInt(form.due_date_days) || 0));
    return d;
});

const dueDateFormatted = computed(() => {
    return `${dueDateObj.value.getDate()} ${monthNames[dueDateObj.value.getMonth()]} ${dueDateObj.value.getFullYear()}`;
});

const isolateDateObj = computed(() => {
    const d = new Date(dueDateObj.value);
    d.setDate(d.getDate() + (parseInt(form.isolate_days) || 0));
    return d;
});

const isolateDateFormatted = computed(() => {
    return `${isolateDateObj.value.getDate()} ${monthNames[isolateDateObj.value.getMonth()]} ${isolateDateObj.value.getFullYear()}`;
});

function submit() {
    form.post('/settings/billing', {
        preserveScroll: true,
    });
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
