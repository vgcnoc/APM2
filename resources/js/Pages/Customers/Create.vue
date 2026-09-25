<template>
    <AppLayout title="Tambah Pelanggan Baru" subtitle="Pendaftaran pelanggan ISP baru (Booking)">
        <div class="max-w-3xl">
            <form @submit.prevent="submit" class="glass-card p-6 space-y-6 animate-fade-in-up">
                <!-- Info Header -->
                <div class="flex items-center gap-3 pb-4 border-b border-white/10">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Data Diri Pelanggan</h3>
                        <p class="text-sm text-gray-500">Isi formulir pendaftaran pelanggan baru</p>
                    </div>
                </div>

                <!-- Form Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="form-label">Nama Lengkap *</label>
                        <input v-model="form.name" type="text" class="form-input" placeholder="Masukkan nama lengkap" required />
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-input" placeholder="email@example.com" />
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="form-label">No. Telepon *</label>
                        <input v-model="form.phone" type="text" class="form-input" placeholder="08xxxxxxxxxx" required />
                        <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Alamat Lengkap *</label>
                        <textarea v-model="form.address" class="form-input" rows="3" placeholder="Jl. ... RT/RW, Kel, Kec, Kota" required></textarea>
                        <p v-if="form.errors.address" class="text-red-400 text-xs mt-1">{{ form.errors.address }}</p>
                    </div>

                    <div>
                        <label class="form-label">Latitude</label>
                        <input v-model="form.latitude" type="number" step="0.00000001" class="form-input" placeholder="-6.2088" />
                    </div>

                    <div>
                        <label class="form-label">Longitude</label>
                        <input v-model="form.longitude" type="number" step="0.00000001" class="form-input" placeholder="106.8456" />
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Paket Internet</label>
                        <select v-model="form.package_id" class="form-select">
                            <option value="">-- Pilih Paket (opsional) --</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                {{ pkg.name }} - {{ pkg.speed_mbps }} Mbps - Rp {{ Number(pkg.price).toLocaleString('id-ID') }}
                            </option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="form-label">Catatan</label>
                        <textarea v-model="form.notes" class="form-input" rows="2" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-white/10">
                    <Link href="/customers/booking" class="btn-ghost">Batal</Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Booking' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    packages: Array,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    latitude: '',
    longitude: '',
    package_id: '',
    notes: '',
});

function submit() {
    form.post('/customers');
}
</script>
