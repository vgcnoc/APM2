<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close"></div>
            <div class="relative bg-[#f4f6f8] rounded-xl shadow-2xl max-w-[420px] w-full max-h-[90vh] overflow-hidden flex flex-col animate-fade-in-up">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-[#f4f6f8]">
                    <h3 class="text-[16px] font-bold text-gray-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        Pilih Admin/CS On Duty
                    </h3>
                    <button @click="close" class="text-gray-500 hover:text-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-4 overflow-y-auto custom-scrollbar">
                    <p class="text-[13px] text-gray-500 mb-4 font-medium leading-relaxed">
                        Kirim konfirmasi {{ customerRow?.status }} pelanggan <span class="font-bold text-gray-700">{{ customerRow?.name }}</span> ke admin/CS yang sedang on duty:
                    </p>

                    <div class="space-y-2 mb-4">
                        <div v-if="$page.props.officers && $page.props.officers.length > 0">
                            <button v-for="officer in $page.props.officers" :key="officer.id" @click="sendToOfficer(officer)" class="w-full flex items-center justify-between px-4 py-3 rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-sm transition-all text-left bg-white group">
                                <div>
                                    <h4 class="text-[14px] font-bold text-gray-800 group-hover:text-blue-700">{{ officer.name }}</h4>
                                    <p class="text-[12px] text-gray-400 mt-0.5">{{ officer.phone || 'Tidak ada nomor' }}</p>
                                </div>
                                <div class="bg-[#244186] text-white px-3 py-1 rounded-full text-[11px] font-bold flex items-center gap-1.5 shadow-sm">
                                    <span class="w-2 h-2 rounded-full bg-green-400 border border-[#244186]"></span>
                                    On Duty
                                </div>
                            </button>
                        </div>
                        <div v-else class="text-center p-6 text-sm text-gray-500 bg-white rounded-xl border border-gray-200">
                            Tidak ada petugas aktif.
                        </div>
                    </div>

                    <!-- Preview Pesan -->
                    <div class="bg-gray-100 rounded-xl p-4 border border-gray-200 shadow-inner">
                        <h4 class="text-[12px] font-bold text-gray-800 mb-2">Preview Pesan:</h4>
                        <div class="text-[12.5px] text-gray-600 whitespace-pre-wrap leading-relaxed font-sans">
                            {{ previewMessage }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    show: Boolean,
    customerRow: Object
});

const emit = defineEmits(['close']);

function close() {
    emit('close');
}

const previewMessage = computed(() => {
    if (!props.customerRow) return '';
    const row = props.customerRow;
    let title = 'Informasi Pelanggan:';
    if (row.status === 'booking') title = 'Konfirmasi Booking Baru:';
    else if (row.status === 'survey' || row.status === 'jadwal_pasang') title = 'Permintaan Jadwal Pemasangan:';
    else if (row.status === 'laporan_pasang' || row.status === 'menunggu_aktivasi') title = 'Permintaan Aktivasi Pelanggan:';
    
    // Format Rp
    const rpFormat = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format;
    
    return `${title}

Nama: ${row.name || '-'}
No HP: ${row.phone || '-'}
Alamat: ${row.address || '-'}
Paket: ${row.package ? row.package.name : '-'}
Biaya Pasang: ${row.installation_fee ? rpFormat(row.installation_fee) : '-'}
Cabang: ${row.area_model ? row.area_model.name : (row.area || '-')}
Petugas: ${row.user ? row.user.name : '-'}
Tanggal: ${row.created_at ? new Date(row.created_at).toLocaleString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '-'}

Mohon segera diproses. Terima kasih.`;
});

function sendToOfficer(officer) {
    if (!officer.phone) {
        alert('Petugas ini belum memiliki nomor telepon yang terdaftar.');
        return;
    }

    const message = previewMessage.value;
    
    let phone = officer.phone.trim();
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }
    
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    let url = '';
    
    if (isMobile) {
        url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
    } else {
        url = `https://web.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(message)}`;
    }
    
    window.open(url, '_blank');
    close();
}
</script>
