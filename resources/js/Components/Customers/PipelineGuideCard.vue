<template>
    <div class="mb-6 rounded-3xl border transition-all duration-300 shadow-xs overflow-hidden" :class="themeClasses.container">
        
        <!-- Header Bar / Collapsible Toggle -->
        <div 
            @click="toggleCollapse"
            class="p-4 sm:p-5 flex items-center justify-between cursor-pointer select-none transition-colors"
            :class="themeClasses.header"
        >
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-xl shadow-xs shrink-0" :class="themeClasses.iconBox">
                    {{ currentData.icon }}
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-sm sm:text-base font-black tracking-tight" :class="themeClasses.title">
                            {{ currentData.title }}
                        </h2>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider" :class="themeClasses.badge">
                            Panduan & SOP Resmi
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1 sm:line-clamp-none">
                        {{ currentData.subtitle }}
                    </p>
                </div>
            </div>

            <!-- Toggle Arrow & Pill -->
            <div class="flex items-center gap-2 shrink-0">
                <span class="hidden sm:inline text-[11px] font-bold" :class="themeClasses.toggleText">
                    {{ isCollapsed ? 'Buka Panduan' : 'Tutup Panduan' }}
                </span>
                <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-transform duration-300" :class="[themeClasses.toggleBtn, isCollapsed ? '' : 'rotate-180']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Expanded Content Body -->
        <div v-show="!isCollapsed" class="p-4 sm:p-6 border-t border-slate-100 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xs space-y-5 animate-in fade-in duration-200">
            
            <!-- 1. SOP Alur Kerja Step-by-Step -->
            <div class="space-y-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-base">📋</span>
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                        Alur Kerja & Prosedur Standar (SOP)
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div 
                        v-for="(step, sIdx) in currentData.steps" 
                        :key="'step-' + sIdx"
                        class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/70 dark:border-slate-800 space-y-1.5 relative overflow-hidden"
                    >
                        <div class="flex items-center justify-between">
                            <span class="w-6 h-6 rounded-lg text-xs font-black flex items-center justify-center" :class="themeClasses.stepNumber">
                                {{ sIdx + 1 }}
                            </span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Langkah {{ sIdx + 1 }}</span>
                        </div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-slate-100 pt-1">
                            {{ step.title }}
                        </h4>
                        <p class="text-[11px] text-slate-600 dark:text-slate-400 leading-relaxed">
                            {{ step.desc }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- 2. Peringatan Kritis (Warnings & Larangan) -->
            <div class="p-4 sm:p-5 rounded-2xl bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 space-y-2.5">
                <div class="flex items-center gap-2 text-rose-700 dark:text-rose-300">
                    <span class="text-lg animate-bounce">⚠️</span>
                    <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider">
                        Peringatan Kritis & Larangan Keras (Wajib Dipatuhi)
                    </h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                    <div 
                        v-for="(warn, wIdx) in currentData.warnings" 
                        :key="'warn-' + wIdx"
                        class="p-3 rounded-xl bg-white/90 dark:bg-slate-900/80 border border-rose-200/80 dark:border-rose-900/40 text-xs text-slate-800 dark:text-slate-200 leading-relaxed flex items-start gap-2.5 shadow-2xs"
                    >
                        <span class="text-rose-500 font-bold shrink-0 text-sm">❌</span>
                        <div>
                            <strong class="text-rose-900 dark:text-rose-200 block text-[11px] mb-0.5">{{ warn.highlight }}</strong>
                            <span class="text-slate-600 dark:text-slate-300 text-[11px]">{{ warn.text }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Quality Control (QC) & Indikator Keberhasilan -->
            <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5 text-emerald-800 dark:text-emerald-300 font-bold">
                    <span class="text-base">🎯</span>
                    <span class="uppercase tracking-wider text-[11px]">Checklist Keberhasilan Tahap Ini:</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span 
                        v-for="(qc, qIdx) in currentData.qcList" 
                        :key="'qc-' + qIdx"
                        class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1.5 shadow-2xs"
                    >
                        <span class="text-emerald-500 font-bold">✓</span>
                        <span>{{ qc }}</span>
                    </span>
                </div>
            </div>

        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    stage: {
        type: String,
        required: true,
        validator: (value) => ['booking', 'survey', 'installed', 'activation', 'canceled'].includes(value),
    },
});

// State Collapse dengan LocalStorage Persistence agar user experience nyaman
const storageKey = `apm2_guide_collapsed_${props.stage}`;
const isCollapsed = ref(false);

onMounted(() => {
    const saved = localStorage.getItem(storageKey);
    if (saved !== null) {
        isCollapsed.value = saved === 'true';
    }
});

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
    localStorage.setItem(storageKey, String(isCollapsed.value));
}

// Data Konten Panduan Cerdas per Tahap
const guides = {
    booking: {
        icon: '📋',
        title: 'Tahap 1: Data Booking & Verifikasi Calon Pelanggan',
        subtitle: 'Validasi data pemohon, kelengkapan nomor WhatsApp aktif, dan pengecekan coverage area sebelum jadwal survey.',
        steps: [
            {
                title: '1. Validasi Identitas & Kontak',
                desc: 'Pastikan Nama lengkap, Nomor WhatsApp aktif (format 08/62), Alamat jelas, dan Pilihan Paket Internet sudah disepakati.',
            },
            {
                title: '2. Cek Coverage Wilayah',
                desc: 'Verifikasi ketersediaan jalur optik atau tiang APM2 terdekat pada peta GIS atau tabel ODP area terkait.',
            },
            {
                title: '3. Kirim ke Data Survey',
                desc: 'Klik tombol "Kirim ke Survey" untuk menugaskan tim lapangan melakukan pengukuran fisik dan cek port ODP.',
            },
            {
                title: '4. Pembatalan Dini (Jika Batal)',
                desc: 'Jika calon pelanggan membatalkan sebelum survey, alihkan status ke "Dibatalkan" dengan mengisi alasan yang jelas.',
            },
        ],
        warnings: [
            {
                highlight: 'Dilarang Tarik Kabel Sebelum Survey!',
                text: 'Jangan pernah menjadwalkan instalasi atau menarik kabel sebelum tim lapangan memastikan status Feasible (Layak).',
            },
            {
                highlight: 'Wajib No WhatsApp Aktif!',
                text: 'Pastikan no WhatsApp benar karena konfirmasi pendaftaran, survei, dan notifikasi aktivasi dikirim otomatis melalui WA Gateway.',
            },
            {
                highlight: 'Cek Riwayat Tunggakan Pelanggan!',
                text: 'Periksa apakah nama atau alamat calon pelanggan memiliki catatan tunggakan lama atau perangkat belum kembali di masa lalu.',
            },
        ],
        qcList: [
            'Nomor WA aktif & tervalidasi',
            'Paket internet disepakati calon pelanggan',
            'Ditugaskan ke tim survey dalam < 1x24 jam',
        ],
    },

    survey: {
        icon: '🔎',
        title: 'Tahap 2: Data Survey & Kelayakan Jalur ODP',
        subtitle: 'Pengecekan fisik tiang, ketersediaan port ODP kosong, pengukuran redaman optik (OPM), dan penentuan kelayakan teknis.',
        steps: [
            {
                title: '1. Penjadwalan Surveyor',
                desc: 'Tentukan petugas teknisi/surveyor dan tanggal jadwal pada tab "Belum Dijadwalkan". Notifikasi dapat dikirim ke teknisi.',
            },
            {
                title: '2. Pengukuran Lapangan',
                desc: 'Cari ODP terdekat (< 250m), pastikan ada port kosong, dan ukur nilai redaman optik menggunakan OPM (Optical Power Meter).',
            },
            {
                title: '3. Input Hasil & Foto Bukti',
                desc: 'Pilih nama ODP, nomor port, redaman (dBm), estimasi kabel (meter), koordinat GPS, dan unggah foto tampak rumah & box ODP.',
            },
            {
                title: '4. Keputusan Kelayakan',
                desc: 'Tandai "Feasible" jika port & redaman baik agar otomatis masuk antrean Instalasi. Jika ODP penuh, tandai "Unfeasible".',
            },
        ],
        warnings: [
            {
                highlight: 'Dilarang Pakai Port ODP Fiktif / Ganda!',
                text: 'Dilarang keras memakai port ODP yang sudah terpakai pelanggan lain. Ini memicu insiden salah cabut dan komplain pelanggan putus.',
            },
            {
                highlight: 'Batas Redaman ODP Wajib <-24 dBm!',
                text: 'Toleransi redaman port ODP wajib berada di rentang -16 dBm s/d -24 dBm. Jika lebih buruk dari -25 dBm, ODP wajib dilaporkan untuk maintenance.',
            },
            {
                highlight: 'Wajib Foto Rumah & Box ODP!',
                text: 'Foto rumah dan nomor port ODP wajib diunggah ke sistem sebagai bukti fisik agar teknisi instalasi tidak salah tarik kabel.',
            },
        ],
        qcList: [
            'Nomor ODP & port tercatat akurat',
            'Redaman optik terukur OPM (-16 s/d -24 dBm)',
            'Koordinat GPS & foto dokumentasi lengkap',
        ],
    },

    installed: {
        icon: '🛜',
        title: 'Tahap 3: Instalasi Jaringan & Pemasangan Modem ONT',
        subtitle: 'Penarikan kabel dropcore, terminasi konektor, pemasangan modem ONT, dan audit standar kerapihan instalasi.',
        steps: [
            {
                title: '1. Jadwalkan Pasang',
                desc: 'Pilih teknisi instalasi dan tentukan jam kerja pada tab "Jadwal Pasang". Sistem otomatis menyiapkan data ODP & paket.',
            },
            {
                title: '2. Standar Penarikan Kabel',
                desc: 'Tarik kabel dropcore melewati tiang dengan clamp span hook kuat, tidak kendur melintang jalan, dan buat slack loop 2m.',
            },
            {
                title: '3. Pasang ONT & Catat Serial',
                desc: 'Terminasi roset, pasang modem ONT, masukkan nomor seri (SN/MAC) ke sistem, dan hubungkan patchcord ke port ODP resmi.',
            },
            {
                title: '4. Laporan & Verifikasi Audit',
                desc: 'Input panjang kabel riil, redaman ONT, dan unggah 4 foto wajib. Ka. Teknisi mengaudit untuk melanjutkan ke Aktivasi Layanan.',
            },
        ],
        warnings: [
            {
                highlight: 'Dilarang Buka Akses Komersial Sebelum Aktivasi!',
                text: 'Jangan membiarkan internet pelanggan menyala tanpa status aktivasi resmi. Ini menyebabkan piutang bocor dan data billing tidak sinkron.',
            },
            {
                highlight: 'Redaman Sisi Pelanggan Maksimal -25 dBm!',
                text: 'Redaman optik di roset ONT tidak boleh lebih buruk dari -25 dBm (ideal: -18 s/d -22 dBm). Redaman > -27 dBm pasti lemot dan putus-putus.',
            },
            {
                highlight: 'Wajib 4 Foto Bukti & Catat Material!',
                text: 'Foto rumah, ODP, barcode ONT, dan layar OPM wajib diunggah. Seluruh material (kabel, fast connector, ONT) wajib dicatat demi akurasi stok gudang.',
            },
        ],
        qcList: [
            'Kabel kencang, rapi & aman dari tegangan PLN',
            'Lampu PON ONT hijau stabil (LOS mati)',
            'Audit instalasi disetujui oleh Kepala Teknisi',
        ],
    },

    activation: {
        icon: '⚡',
        title: 'Tahap 4: Aktivasi Layanan & Penerbitan Billing',
        subtitle: 'Verifikasi kelayakan audit, aktivasi profil internet di OLT/Router, pencatatan tanggal aktivasi, dan penerbitan invoice pertama.',
        steps: [
            {
                title: '1. Verifikasi Hasil Audit',
                desc: 'Pastikan data instalasi telah disetujui (Approved), data ODP & port sudah benar, dan modem ONT terdeteksi online.',
            },
            {
                title: '2. Validasi Pembayaran Pasang',
                desc: 'Pastikan biaya registrasi/biaya pasang baru (jika berlaku) telah lunas atau terverifikasi kasir sebelum aktivasi.',
            },
            {
                title: '3. Klik Tombol Aktivasi',
                desc: 'Status berubah jadi Active, tanggal aktivasi tercatat, profil PPPoE/Hotspot sinkron ke Router/OLT, dan Invoice pertama terbit.',
            },
            {
                title: '4. Kirim WA Selamat Datang',
                desc: 'Kirim pesan sambutan otomatis via WA Gateway yang memuat ID Pelanggan, rincian paket, jatuh tempo, dan kontak CS resmi.',
            },
        ],
        warnings: [
            {
                highlight: 'Tanggal Aktivasi Menentukan Tagihan Prorata!',
                text: 'Pastikan tanggal aktivasi tepat hari ini. Tanggal ini menjadi dasar perhitungan invoice prorata bulan berjalan dan komisi teknisi/affiliate.',
            },
            {
                highlight: 'Dilarang Aktivasi Jika Audit Belum Lolos!',
                text: 'Jangan mengaktifkan pelanggan yang belum lolos verifikasi audit instalasi untuk mencegah manipulasi data fisik di lapangan.',
            },
            {
                highlight: 'Cek Paket Internet & Insentif Mitra!',
                text: 'Periksa paket pelanggan dan komisi affiliate (jika ada) agar invoice dan reward tercatat otomatis tanpa kesalahan hitung.',
            },
        ],
        qcList: [
            'Profil internet sinkron ke OLT & Mikrotik Router',
            'Invoice pertama (prorata/penuh) terbit otomatis',
            'WhatsApp selamat datang & ID Pelanggan terkirim',
        ],
    },

    canceled: {
        icon: '🚫',
        title: 'Tahap Khusus: Data Pelanggan Dibatalkan & Opsi Pemulihan',
        subtitle: 'Pengarsipan calon pelanggan yang batal mendaftar atau unfeasible teknis dengan riwayat alasan dan opsi pemulihan cepat.',
        steps: [
            {
                title: '1. Pencatatan Alasan Jelas',
                desc: 'Setiap pembatalan wajib memuat alasan akurat (misal: ODP Penuh / Batal Sepihak / Biaya Kemahalan / Di Luar Jangkauan).',
            },
            {
                title: '2. Tarik Material dari Lapangan',
                desc: 'Jika pembatalan terjadi pasca-jadwal pasang, pastikan modem ONT dan sisa kabel dropcore segera dikembalikan ke gudang.',
            },
            {
                title: '3. Pemulihan Data (Reaktivasi)',
                desc: 'Jika di kemudian hari ada penambahan ODP atau tiang baru di area tersebut, gunakan tombol "Pulihkan" untuk memproses ulang.',
            },
            {
                title: '4. Evaluasi Blank Spot Wilayah',
                desc: 'Gunakan statistik pembatalan untuk menganalisis area mana yang paling membutuhkan ekspansi ODP atau tiang baru.',
            },
        ],
        warnings: [
            {
                highlight: 'Hapus Permanen Tidak Bisa Dibatalkan!',
                text: 'Fitur "Hapus Terpilih" menghapus database permanen dan tidak dapat dipulihkan. Selalu utamakan opsi "Pulihkan" untuk calon pelanggan.',
            },
            {
                highlight: 'Pastikan Tidak Ada Perangkat Tertinggal!',
                text: 'Pastikan tidak ada modem ONT atau kabel milik perusahaan yang tertinggal di lokasi calon pelanggan yang dibatalkan.',
            },
            {
                highlight: 'Data Kontak Tetap Berharga!',
                text: 'Simpan nomor kontak pelanggan yang batal karena ODP penuh. Hubungi mereka kembali saat rute ODP baru selesai dibangun.',
            },
        ],
        qcList: [
            'Alasan pembatalan tercatat detail & transparan',
            'Tidak ada perangkat ONT tertinggal di lapangan',
            'Data kontak tersimpan untuk prospek ekspansi baru',
        ],
    },
};

const currentData = computed(() => guides[props.stage] || guides.booking);

// Theme Styling Classes per Stage
const themeClasses = computed(() => {
    switch (props.stage) {
        case 'booking':
            return {
                container: 'bg-blue-50/50 dark:bg-slate-900 border-blue-200 dark:border-blue-900/60',
                header: 'hover:bg-blue-100/40 dark:hover:bg-blue-950/30',
                iconBox: 'bg-blue-500 text-white shadow-blue-500/20',
                title: 'text-blue-950 dark:text-blue-200',
                badge: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200 dark:border-blue-800',
                toggleText: 'text-blue-600 dark:text-blue-400',
                toggleBtn: 'bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300',
                stepNumber: 'bg-blue-500 text-white',
            };
        case 'survey':
            return {
                container: 'bg-indigo-50/50 dark:bg-slate-900 border-indigo-200 dark:border-indigo-900/60',
                header: 'hover:bg-indigo-100/40 dark:hover:bg-indigo-950/30',
                iconBox: 'bg-indigo-500 text-white shadow-indigo-500/20',
                title: 'text-indigo-950 dark:text-indigo-200',
                badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800',
                toggleText: 'text-indigo-600 dark:text-indigo-400',
                toggleBtn: 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300',
                stepNumber: 'bg-indigo-500 text-white',
            };
        case 'installed':
            return {
                container: 'bg-amber-50/50 dark:bg-slate-900 border-amber-200 dark:border-amber-900/60',
                header: 'hover:bg-amber-100/40 dark:hover:bg-amber-950/30',
                iconBox: 'bg-amber-500 text-white shadow-amber-500/20',
                title: 'text-amber-950 dark:text-amber-200',
                badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                toggleText: 'text-amber-600 dark:text-amber-400',
                toggleBtn: 'bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300',
                stepNumber: 'bg-amber-500 text-white',
            };
        case 'activation':
            return {
                container: 'bg-purple-50/50 dark:bg-slate-900 border-purple-200 dark:border-purple-900/60',
                header: 'hover:bg-purple-100/40 dark:hover:bg-purple-950/30',
                iconBox: 'bg-purple-500 text-white shadow-purple-500/20',
                title: 'text-purple-950 dark:text-purple-200',
                badge: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800',
                toggleText: 'text-purple-600 dark:text-purple-400',
                toggleBtn: 'bg-purple-100 dark:bg-purple-900/60 text-purple-700 dark:text-purple-300',
                stepNumber: 'bg-purple-500 text-white',
            };
        case 'canceled':
            return {
                container: 'bg-rose-50/50 dark:bg-slate-900 border-rose-200 dark:border-rose-900/60',
                header: 'hover:bg-rose-100/40 dark:hover:bg-rose-950/30',
                iconBox: 'bg-rose-500 text-white shadow-rose-500/20',
                title: 'text-rose-950 dark:text-rose-200',
                badge: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800',
                toggleText: 'text-rose-600 dark:text-rose-400',
                toggleBtn: 'bg-rose-100 dark:bg-rose-900/60 text-rose-700 dark:text-rose-300',
                stepNumber: 'bg-rose-500 text-white',
            };
        default:
            return {
                container: 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-800',
                header: 'hover:bg-slate-100 dark:hover:bg-slate-800',
                iconBox: 'bg-slate-800 text-white',
                title: 'text-slate-900 dark:text-white',
                badge: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
                toggleText: 'text-slate-600 dark:text-slate-400',
                toggleBtn: 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300',
                stepNumber: 'bg-slate-700 text-white',
            };
    }
});
</script>

