<template>
    <AppLayout title="Pengaturan Gaji">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Pengaturan Sistem Gaji</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola preferensi dan pengaturan sistem penggajian karyawan</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Pengaturan Sistem Gaji -->
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Pengaturan Sistem Gaji
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Tanggal Cutoff (Periode Gaji)
                            </label>
                            <input type="number" v-model="form.payroll_cutoff_date" min="1" max="28" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <p class="text-xs text-gray-500 mt-1">Tanggal akhir periode perhitungan gaji (misal: 25 = periode tgl 26 bulan lalu s/d tgl 25 bulan ini)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Tanggal Gajian
                            </label>
                            <input type="number" v-model="form.payroll_payment_date" min="1" max="28" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                            <p class="text-xs text-gray-500 mt-1">Tanggal pembayaran gaji setiap bulan</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Komponen Gaji Otomatis</label>
                        <p class="text-xs text-gray-500 mb-3">Pilih komponen yang otomatis dihitung saat generate slip gaji</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Insentif -->
                            <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="insentif" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-900">Insentif</span>
                            </label>
                            <!-- Potongan -->
                            <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="potongan" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-900">Potongan</span>
                            </label>
                            <!-- Lembur -->
                            <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="lembur" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-900">Lembur</span>
                            </label>
                            <!-- Tunjangan -->
                            <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50 transition-colors">
                                <div class="relative flex items-center">
                                    <input type="checkbox" value="tunjangan" v-model="form.payroll_components" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-900">Tunjangan</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan/Keterangan Slip Gaji</label>
                        <textarea v-model="form.payroll_note" rows="3" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Catatan yang akan tampil di slip gaji..."></textarea>
                    </div>
                </div>

                <!-- Pengaturan Pencairan Insentif -->
                <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Pengaturan Pencairan Insentif
                    </h2>
                    <p class="text-xs text-gray-500 mb-4">Atur jadwal pencairan insentif karyawan</p>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mode Pencairan</label>
                        <select v-model="form.payroll_incentive_mode" class="block w-full border-gray-300 rounded-xl shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="Bulanan (sekali sebulan)">Bulanan (sekali sebulan)</option>
                            <option value="Mingguan (sekali seminggu)">Mingguan (sekali seminggu)</option>
                            <option value="Harian (langsung cair)">Harian (langsung cair)</option>
                        </select>
                    </div>

                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-3">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <h4 class="text-sm font-bold text-blue-900">Info Mode Pencairan:</h4>
                            <p class="text-xs text-blue-700 mt-1">
                                <template v-if="form.payroll_incentive_mode === 'Bulanan (sekali sebulan)'">
                                    Insentif dicairkan 1 kali sebulan bersamaan dengan gaji.
                                </template>
                                <template v-else-if="form.payroll_incentive_mode === 'Mingguan (sekali seminggu)'">
                                    Insentif diakumulasi dan dicairkan setiap hari Jumat / akhir pekan.
                                </template>
                                <template v-else>
                                    Insentif dicairkan pada hari yang sama setelah transaksi selesai.
                                </template>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan -->
                <div class="bg-gray-50 rounded-2xl border border-gray-100 p-6">
                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2 mb-3">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        Ringkasan Pengaturan:
                    </h4>
                    <ul class="text-sm text-gray-600 space-y-1 ml-6 list-disc marker:text-gray-400">
                        <li>Cutoff: Setiap tanggal <strong class="text-gray-900">{{ form.payroll_cutoff_date }}</strong></li>
                        <li>Gajian: Setiap tanggal <strong class="text-gray-900">{{ form.payroll_payment_date }}</strong></li>
                        <li>Pencairan Insentif: <strong class="text-gray-900">{{ form.payroll_incentive_mode.split(' ')[0] }}</strong></li>
                        <li>Komponen: <span class="capitalize text-gray-900">{{ form.payroll_components.join(', ') || 'Tidak ada' }}</span></li>
                    </ul>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 inline-flex justify-center items-center py-2.5 px-6 border border-transparent shadow-sm text-sm font-bold rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all disabled:opacity-50">
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    payroll_cutoff_date: String,
    payroll_payment_date: String,
    payroll_components: Array,
    payroll_note: String,
    payroll_incentive_mode: String,
});

const form = useForm({
    payroll_cutoff_date: props.payroll_cutoff_date,
    payroll_payment_date: props.payroll_payment_date,
    payroll_components: props.payroll_components || [],
    payroll_note: props.payroll_note,
    payroll_incentive_mode: props.payroll_incentive_mode,
});

const submit = () => {
    form.post(route('settings.payroll.update'), {
        preserveScroll: true,
    });
};
</script>
