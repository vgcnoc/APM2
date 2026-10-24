<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Dialog, DialogPanel, DialogTitle, DialogDescription } from '@headlessui/vue';
import {
    ArrowRightIcon, ArrowUpRightIcon, BuildingOffice2Icon, CheckIcon,
    CreditCardIcon, EyeIcon, EyeSlashIcon, GlobeAltIcon, LockClosedIcon,
    ReceiptPercentIcon, SignalIcon, SparklesIcon, UserGroupIcon, UserIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';
import AppLogo from '@/Components/AppLogo.vue';

const props = defineProps({ status: String, portal: String });
const page = usePage();
const brandName = computed(() => page.props.company_name || page.props.app_name || 'ISP Manager');
const portals = [
    {
        id: 'staff', label: 'Staf & Tim', icon: BuildingOffice2Icon, tone: 'sky',
        description: 'Operasional, billing, dan manajemen jaringan.',
        hint: 'Gunakan akun staf yang telah terdaftar di perusahaan.',
    },
    {
        id: 'reseller', label: 'Mitra Biller', icon: CreditCardIcon, tone: 'mint',
        description: 'Transaksi, pembayaran, dan layanan mitra.',
        hint: 'Masuk menggunakan akun mitra atau reseller yang telah diverifikasi.',
        registration: '/register-reseller',
    },
    {
        id: 'affiliate', label: 'Afiliasi & Sales', icon: UserGroupIcon, tone: 'amber',
        description: 'Booking, jaringan afiliasi, dan komisi Anda.',
        hint: 'Semua karyawan dapat menggunakan akun yang sama di portal ini, termasuk jabatan selain sales.',
        registration: '/register-affiliate',
    },
    {
        id: 'customer', label: 'Pelanggan', icon: UserIcon, tone: 'violet',
        description: 'Paket internet, tagihan, dan layanan Anda.',
        hint: 'Gunakan email yang terdaftar pada akun langganan Anda.',
    },
];
const selectedPortal = ref(portals.find(portal => portal.id === props.portal) || portals[0]);
const activeDialog = ref(null);
const emailInput = ref(null);
const billInput = ref(null);
const showPassword = ref(false);
const form = useForm({ email: '', password: '', remember: false });

const openLogin = (portal) => {
    selectedPortal.value = portal;
    form.clearErrors();
    showPassword.value = false;
    activeDialog.value = 'login';
};
const closeDialog = () => {
    if (form.processing) return;
    billRequest?.abort();
    activeDialog.value = null;
    form.reset('password');
    showPassword.value = false;
};
const submit = () => {
    form.transform(data => ({ ...data, portal: selectedPortal.value.id }));
    form.post('/login', {
        onError: () => { activeDialog.value = 'login'; },
        onFinish: () => form.reset('password'),
    });
};

const searchQuery = ref('');
const isSearching = ref(false);
const searchError = ref('');
const billResult = ref(null);
let billRequest = null;
const openCheckBill = () => {
    searchQuery.value = '';
    searchError.value = '';
    billResult.value = null;
    isSearching.value = false;
    activeDialog.value = 'bill';
};
const formatRupiah = value => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(value || 0);
const handleCheckBill = async () => {
    const q = searchQuery.value.trim();
    searchError.value = '';
    billResult.value = null;
    if (q.length < 3) {
        searchError.value = 'Masukkan minimal 3 karakter ID pelanggan atau nomor WhatsApp.';
        return;
    }
    billRequest?.abort();
    const request = new AbortController();
    billRequest = request;
    isSearching.value = true;
    try {
        const { data } = await axios.get('/public/check-bill', { params: { q }, signal: request.signal });
        if (data.success) billResult.value = data;
        else searchError.value = data.message || 'Data pelanggan tidak ditemukan.';
    } catch (error) {
        if (!axios.isCancel(error)) searchError.value = 'Gagal menghubungi server. Silakan coba kembali.';
    } finally {
        if (billRequest === request) isSearching.value = false;
    }
};

onMounted(() => {
    document.body.classList.add('auth-device-scale');
    document.body.style.zoom = '100%';
});
onUnmounted(() => {
    billRequest?.abort();
    document.body.classList.remove('auth-device-scale');
    document.body.style.zoom = '80%';
});
</script>

<template>
    <Head title="Selamat Datang — Portal Layanan" />

    <div class="access-page">
        <div class="ambient" aria-hidden="true"></div>
        <header class="access-header">
            <AppLogo context="navbar" white />
            <span class="header-label"><span></span> PORTAL LAYANAN</span>
        </header>

        <main class="access-main">
            <section class="welcome" aria-labelledby="welcome-title">
                <p class="eyebrow"><span class="eyebrow-line"></span> SATU PLATFORM. LEBIH TERHUBUNG.</p>
                <h1 id="welcome-title">Akses lebih mudah.<br /><span>Langkah lebih jauh.</span></h1>
                <p class="welcome-description">Kelola layanan, bangun kemitraan, dan kembangkan peluang. Semua dimulai dari satu tempat.</p>

                <div class="connection-art" aria-hidden="true">
                    <div class="orbit orbit-outer"></div>
                    <div class="orbit orbit-inner"></div>
                    <div class="orbit-core"><GlobeAltIcon /></div>
                    <div class="connection-node node-one"><SignalIcon /><span>Operasional<span>Terorganisasi</span></span></div>
                    <div class="connection-node node-two"><UserGroupIcon /><span>Kemitraan<span>Saling bertumbuh</span></span></div>
                    <div class="connection-node node-three"><SparklesIcon /><span>Layanan<span>Lebih sederhana</span></span></div>
                    <div class="art-caption"><span></span> TERHUBUNG DALAM SATU EKOSISTEM</div>
                </div>
            </section>

            <section class="portal-panel" aria-labelledby="portal-title">
                <div class="panel-heading">
                    <p class="eyebrow">AKSES AKUN ANDA</p>
                    <h2 id="portal-title">Selamat datang.</h2>
                    <p>Pilih portal untuk melanjutkan.</p>
                </div>

                <p v-if="status" role="status" class="notice notice-success">{{ status }}</p>

                <div class="portal-list">
                    <button
                        v-for="portal in portals" :key="portal.id" type="button"
                        class="portal-button" :class="`tone-${portal.tone}`"
                        :aria-label="`Masuk sebagai ${portal.label}`" aria-haspopup="dialog"
                        @click="openLogin(portal)"
                    >
                        <span class="portal-icon"><component :is="portal.icon" /></span>
                        <span class="portal-copy"><span class="portal-name">{{ portal.label }}</span><span class="portal-description">{{ portal.description }}</span></span>
                        <ArrowUpRightIcon class="portal-arrow" />
                    </button>
                </div>

                <div class="quick-access">
                    <span class="quick-label">TANPA MASUK AKUN</span>
                    <button type="button" class="bill-button" aria-haspopup="dialog" @click="openCheckBill">
                        <ReceiptPercentIcon /><span>Cek & bayar tagihan</span><ArrowRightIcon />
                    </button>
                </div>
                <p class="panel-note"><LockClosedIcon /> Akses akun sesuai peran Anda.</p>
            </section>
        </main>

        <footer class="access-footer">
            <span>&copy; {{ new Date().getFullYear() }} {{ brandName }}.</span>
            <span>Billing & layanan dalam satu genggaman.</span>
        </footer>
    </div>

    <Dialog
        v-if="activeDialog" :open="true" class="access-dialog"
        :initial-focus="activeDialog === 'login' ? emailInput : billInput" @close="closeDialog"
    >
        <div class="dialog-backdrop" aria-hidden="true"></div>
        <div class="dialog-viewport">
            <DialogPanel class="dialog-panel" :class="activeDialog === 'login' ? `tone-${selectedPortal.tone}` : 'tone-sky'">
                <button type="button" class="close-button" aria-label="Tutup dialog" :disabled="form.processing" @click="closeDialog"><XMarkIcon /></button>

                <template v-if="activeDialog === 'login'">
                    <span class="dialog-icon portal-icon"><component :is="selectedPortal.icon" /></span>
                    <p class="eyebrow">PORTAL {{ selectedPortal.label.toUpperCase() }}</p>
                    <DialogTitle as="h2" class="dialog-title">Masuk ke akun Anda.</DialogTitle>
                    <DialogDescription class="dialog-description">{{ selectedPortal.description }}</DialogDescription>

                    <form class="login-form" @submit.prevent="submit">
                        <div class="field">
                            <label for="login-email">Email</label>
                            <input id="login-email" ref="emailInput" v-model="form.email" type="email" name="email" autocomplete="username" placeholder="nama@perusahaan.com" required :aria-invalid="Boolean(form.errors.email)" :aria-describedby="form.errors.email ? 'email-error' : undefined" :disabled="form.processing" />
                            <p v-if="form.errors.email" id="email-error" class="field-error" role="alert">{{ form.errors.email }}</p>
                        </div>
                        <div class="field">
                            <label for="login-password">Password</label>
                            <div class="password-field">
                                <input id="login-password" v-model="form.password" :type="showPassword ? 'text' : 'password'" name="password" autocomplete="current-password" placeholder="Masukkan password" required :aria-invalid="Boolean(form.errors.password)" :aria-describedby="form.errors.password ? 'password-error' : undefined" :disabled="form.processing" />
                                <button type="button" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" :aria-pressed="showPassword" @click="showPassword = !showPassword"><component :is="showPassword ? EyeSlashIcon : EyeIcon" /></button>
                            </div>
                            <p v-if="form.errors.password" id="password-error" class="field-error" role="alert">{{ form.errors.password }}</p>
                        </div>
                        <p v-if="form.errors.portal" class="field-error" role="alert">{{ form.errors.portal }}</p>
                        <label class="remember-field"><input v-model="form.remember" type="checkbox" name="remember" :disabled="form.processing" /><span>Ingat saya di perangkat ini</span></label>
                        <button type="submit" class="primary-button" :disabled="form.processing"><span v-if="form.processing" class="spinner" aria-hidden="true"></span><span>{{ form.processing ? 'Sedang masuk…' : 'Masuk sekarang' }}</span><ArrowRightIcon v-if="!form.processing" /></button>
                    </form>

                    <p class="portal-hint"><LockClosedIcon /><span>{{ selectedPortal.hint }}</span></p>
                    <p v-if="selectedPortal.registration" class="registration-note">Belum menjadi mitra? <Link :href="selectedPortal.registration">Daftar sekarang <ArrowUpRightIcon /></Link></p>
                    <button type="button" class="back-button" :disabled="form.processing" @click="closeDialog">Kembali ke pilihan portal</button>
                </template>

                <template v-else>
                    <span class="dialog-icon portal-icon"><ReceiptPercentIcon /></span>
                    <p class="eyebrow">LAYANAN PELANGGAN</p>
                    <DialogTitle as="h2" class="dialog-title">Cek tagihan Anda.</DialogTitle>
                    <DialogDescription class="dialog-description">Lihat tagihan dan lanjutkan pembayaran tanpa perlu masuk akun.</DialogDescription>
                    <form class="bill-form field" @submit.prevent="handleCheckBill">
                        <label for="bill-query">ID pelanggan atau nomor WhatsApp</label>
                        <input id="bill-query" ref="billInput" v-model="searchQuery" type="text" autocomplete="off" placeholder="CUST-0012 atau 08123456789" required :disabled="isSearching" :aria-invalid="Boolean(searchError)" :aria-describedby="searchError ? 'bill-error' : undefined" />
                        <button type="submit" class="primary-button" :disabled="isSearching"><span v-if="isSearching" class="spinner" aria-hidden="true"></span>{{ isSearching ? 'Memeriksa…' : 'Periksa tagihan' }}<ArrowRightIcon v-if="!isSearching" /></button>
                    </form>
                    <p v-if="searchError" id="bill-error" class="notice notice-error" role="alert">{{ searchError }}</p>

                    <div v-if="billResult" class="bill-results" aria-live="polite">
                        <div class="customer-summary">
                            <div class="customer-summary-heading"><strong>{{ billResult.customer.name }}</strong><span class="service-badge">{{ billResult.customer.status === 'active' ? 'Aktif' : billResult.customer.status }}</span></div>
                            <span>{{ billResult.customer.customer_code }}</span>
                            <p>{{ billResult.customer.package_name }} <span aria-hidden="true">&middot;</span> {{ billResult.customer.area }}</p>
                        </div>
                        <template v-if="billResult.invoices?.length">
                            <div class="invoice-heading"><span>Belum lunas ({{ billResult.invoices.length }})</span><strong>{{ formatRupiah(billResult.total_unpaid) }}</strong></div>
                            <div class="invoice-list">
                                <article v-for="invoice in billResult.invoices" :key="invoice.id || invoice.invoice_number" class="invoice-item">
                                    <div><strong>{{ invoice.invoice_number }}</strong><p>{{ invoice.period_label || 'Tagihan layanan' }}</p><p>Jatuh tempo: {{ invoice.due_date }}</p><span class="invoice-status">{{ invoice.status }}</span></div>
                                    <div class="invoice-payment"><strong>{{ formatRupiah(invoice.remaining) }}</strong><a v-if="invoice.id" :href="`/payment-gateway/pay/${invoice.id}`">Bayar <ArrowUpRightIcon /></a></div>
                                </article>
                            </div>
                            <p class="bill-guidance">Bayar melalui kanal resmi perusahaan atau loket mitra terdekat. Periksa rincian sebelum mengonfirmasi pembayaran.</p>
                        </template>
                        <div v-else class="notice notice-success paid-notice"><CheckIcon /><span>Tidak ada tagihan tertunggak. Terima kasih!</span></div>
                    </div>
                </template>
            </DialogPanel>
        </div>
    </Dialog>
</template>

<style scoped>
.access-page { --access-text: #f0f5ff; --access-muted: #99a9bf; --access-border: rgba(164, 190, 223, .13); color: var(--access-text); background: #090f1b; min-height: 100svh; display: flex; flex-direction: column; position: relative; overflow: hidden; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
.ambient { position: absolute; inset: 0; pointer-events: none; background: radial-gradient(ellipse at 8% 25%, #15385645, transparent 52%), radial-gradient(ellipse at 96% 80%, #17344829, transparent 42%); }
.access-header, .access-footer { width: min(1264px, 100%); margin: 0 auto; padding: 32px 48px; position: relative; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.header-label { display: flex; align-items: center; gap: 9px; color: #a5b4c9; font-size: 10px; letter-spacing: .16em; font-weight: 600; }
.header-label > span { width: 6px; height: 6px; border-radius: 50%; background: #80c9d3; }
.access-main { width: min(1264px, 100%); margin: auto; padding: 42px 48px 60px; position: relative; display: grid; grid-template-columns: 1fr 452px; align-items: center; gap: 90px; }
.eyebrow { display: flex; gap: 11px; align-items: center; color: #89b7c7; font-size: 10px; font-weight: 650; letter-spacing: .16em; line-height: 1.7; margin: 0; }
.eyebrow-line { width: 26px; height: 1px; background: #70b9cb; }
.welcome h1 { color: #f0f5ff; font-size: clamp(34px, 3.6vw, 49px); line-height: 1.19; letter-spacing: -.045em; font-weight: 650; margin: 24px 0 22px; }
.welcome h1 > span { color: #8fb5c9; }
.welcome-description { color: #9aacc0; line-height: 1.85; max-width: 400px; font-size: 14px; }
.connection-art { position: relative; height: 260px; margin-top: 28px; max-width: 440px; }
.orbit { position: absolute; border: 1px solid #79b5ce15; border-radius: 50%; left: 50%; top: 46%; transform: translate(-50%, -50%); }
.orbit-outer { width: 330px; height: 230px; transform: translate(-50%, -50%) rotate(-25deg); }
.orbit-inner { width: 220px; height: 150px; transform: translate(-50%, -50%) rotate(25deg); border-style: dashed; border-color: #79b5ce20; }
.orbit-core { position: absolute; left: calc(50% - 33px); top: calc(46% - 33px); width: 66px; height: 66px; display: grid; place-items: center; border: 1px solid #8ed2e32e; border-radius: 21px; background: linear-gradient(140deg, #1b3748, #101c2c); box-shadow: 0 0 65px #5cc5df12, inset 0 1px #ffffff0a; color: #96d5e0; }
.orbit-core svg { width: 33px; height: 33px; stroke-width: 1.15; }
.connection-node { position: absolute; display: flex; align-items: center; gap: 10px; padding: 12px 16px; border: 1px solid #8faccc1c; background: #111d2bef; box-shadow: 0 10px 25px #00000012; border-radius: 12px; color: #dce7f4; font-size: 11px; font-weight: 600; }
.connection-node > svg { width: 19px; height: 19px; color: #8cbbcd; stroke-width: 1.5; }
.connection-node > span > span { display: block; font-size: 10px; color: #8299af; font-weight: 400; margin-top: 3px; }
.node-one { left: 2%; top: 20%; }
.node-two { right: 0; top: 38%; }
.node-three { left: 13%; bottom: 13%; }
.art-caption { position: absolute; bottom: -6px; left: 0; right: 0; display: flex; justify-content: center; align-items: center; gap: 8px; font-size: 8px; letter-spacing: .17em; color: #647f97; }
.art-caption > span { width: 4px; height: 4px; background: #719db1; border-radius: 50%; }
.portal-panel { border: 1px solid var(--access-border); border-radius: 24px; padding: 34px 30px 22px; background: linear-gradient(145deg, #152131d9, #101a29ed); box-shadow: 0 28px 80px #00000020, inset 0 1px #ffffff05; }
.panel-heading h2 { color: #f0f5ff; font-size: 29px; letter-spacing: -.035em; font-weight: 620; margin: 10px 0 6px; line-height: 1.3; }
.panel-heading > p:last-child { color: var(--access-muted); font-size: 13px; margin: 0; }
.portal-list { display: grid; gap: 10px; margin-top: 26px; }
.tone-sky { --portal-accent: #92ceee; --portal-tint: #5cb7ef15; --portal-edge: #83c7ed26; }
.tone-mint { --portal-accent: #8ed3bc; --portal-tint: #62d4ab12; --portal-edge: #83d8b726; }
.tone-amber { --portal-accent: #dec28f; --portal-tint: #e8b86412; --portal-edge: #e1be7d26; }
.tone-violet { --portal-accent: #b6ace4; --portal-tint: #afa0ed14; --portal-edge: #b3a3ed26; }
.portal-button { display: flex; align-items: center; text-align: left; gap: 14px; padding: 16px 14px; width: 100%; background: #0c16264d; border: 1px solid #9bbbd41a; border-radius: 13px; transition: background .2s, border-color .2s, transform .2s; }
.portal-button:hover { background: #1a2a3c; border-color: var(--portal-edge); transform: translateX(3px); }
.portal-icon { display: grid; place-items: center; flex-shrink: 0; width: 41px; height: 41px; background: var(--portal-tint); color: var(--portal-accent); border: 1px solid var(--portal-edge); border-radius: 11px; }
.portal-icon svg { width: 21px; height: 21px; stroke-width: 1.5; }
.portal-copy { display: flex; flex: 1; min-width: 0; flex-direction: column; gap: 5px; }
.portal-name { color: #e4edf9; font-size: 13px; font-weight: 600; }
.portal-description { color: #98a9bf; font-size: 10px; line-height: 1.5; }
.portal-arrow { width: 17px; height: 17px; flex-shrink: 0; color: #70869f; transition: color .2s; }
.portal-button:hover .portal-arrow { color: var(--portal-accent); }
.quick-access { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--access-border); display: flex; flex-direction: column; align-items: center; gap: 12px; }
.quick-label { font-size: 8px; font-weight: 600; letter-spacing: .15em; color: #7d91a9; }
.bill-button { display: flex; align-items: center; justify-content: center; width: 100%; gap: 9px; padding: 8px; border-radius: 8px; color: #b7c9dd; font-size: 12px; font-weight: 500; transition: background .2s, color .2s; }
.bill-button:hover { background: #ffffff05; color: #e2f0ff; }
.bill-button svg { width: 17px; height: 17px; }
.bill-button svg:last-child { width: 14px; margin-left: 5px; }
.panel-note { margin: 15px 0 0; color: #7f94ad; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 10px; }
.panel-note svg { width: 12px; height: 12px; }
.access-footer { font-size: 10px; color: #7d91a9; padding-top: 20px; padding-bottom: 26px; }
.access-footer > span:last-child { color: #688096; }
.access-page button:focus-visible, .access-dialog :is(button, a):focus-visible { outline: 2px solid #93c5df; outline-offset: 4px; }
.access-dialog { position: fixed; z-index: 70; inset: 0; color: #e9f1fc; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; color-scheme: dark; }
.dialog-backdrop { position: fixed; inset: 0; background: #030914bd; backdrop-filter: blur(9px); }
.dialog-viewport { position: fixed; inset: 0; overflow-y: auto; display: flex; align-items: center; justify-content: center; padding: 32px 20px; }
.dialog-panel { width: 100%; max-width: 460px; margin: auto; flex-shrink: 0; position: relative; background: linear-gradient(150deg, #182537, #101a29 70%); border: 1px solid #9ebed52e; border-radius: 24px; padding: 32px; box-shadow: 0 30px 100px #00000060; animation: dialog-enter .2s ease-out; }
.close-button { position: absolute; top: 20px; right: 20px; border-radius: 8px; padding: 7px; color: #99acc3; }
.close-button:hover { background: #ffffff08; color: #eef5ff; }
.close-button svg { width: 20px; height: 20px; }
.dialog-icon { width: 46px; height: 46px; border-radius: 13px; margin-bottom: 22px; }
.access-dialog .eyebrow { color: var(--portal-accent); font-size: 9px; }
.access-dialog .dialog-title { color: #f0f5ff; font-size: 26px; font-weight: 620; line-height: 1.25; letter-spacing: -.035em; margin: 10px 0; }
.dialog-description { color: #9aacc2; font-size: 12px; line-height: 1.7; margin: 0; }
.login-form { margin-top: 26px; display: grid; gap: 18px; }
.field { display: flex; flex-direction: column; gap: 8px; }
.field > label { color: #c7d5e6; font-size: 12px; font-weight: 500; }
.field input { width: 100%; min-height: 46px; border: 1px solid #7693b242; background: #091423; border-radius: 10px; padding: 11px 13px; font-size: 13px; color: #f0f5ff; box-shadow: none; transition: border-color .15s, box-shadow .15s; }
.field input::placeholder { color: #7890aa; }
.field input:focus { outline: none; border-color: var(--portal-accent); box-shadow: 0 0 0 3px var(--portal-tint); }
.field input[aria-invalid="true"] { border-color: #ef9ca4; }
.password-field { position: relative; }
.password-field input { padding-right: 48px; }
.password-field > button { position: absolute; right: 7px; top: 5px; padding: 8px; border-radius: 6px; color: #a0b1c6; }
.password-field svg { width: 20px; height: 20px; }
.field-error { color: #f2a4ae; font-size: 11px; line-height: 1.6; margin: 0; }
.remember-field { display: flex; align-items: center; gap: 9px; color: #aabbd0; font-size: 11px; cursor: pointer; }
.remember-field input { width: 16px; height: 16px; background-color: #0b1727; border-color: #5d7794; border-radius: 4px; color: #376d8c; }
.remember-field input:focus { --tw-ring-color: #93c5df; }
.primary-button { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; min-height: 46px; padding: 12px; border: 1px solid #b0d8e44d; border-radius: 10px; background: #a7d0df; color: #102335; font-size: 12px; font-weight: 700; transition: background .2s; }
.primary-button:hover { background: #c3e4ee; }
.primary-button svg { width: 17px; height: 17px; }
.access-dialog button:disabled { opacity: .6; cursor: wait; }
.portal-hint { display: flex; align-items: flex-start; gap: 8px; color: #91a6bf; font-size: 10px; line-height: 1.8; margin: 20px 0; }
.portal-hint svg { width: 14px; height: 14px; flex-shrink: 0; margin-top: 2px; }
.registration-note { text-align: center; font-size: 11px; color: #9aacc2; padding-top: 18px; border-top: 1px solid #abc9df1a; }
.registration-note a { display: inline-flex; align-items: center; gap: 2px; color: var(--portal-accent); font-weight: 600; }
.registration-note svg { width: 13px; height: 13px; }
.back-button { display: block; margin: 20px auto 0; color: #a4b6cc; font-size: 11px; padding: 6px; border-radius: 4px; }
.back-button:hover { color: #f0f5ff; }
.bill-form { margin-top: 24px; }
.bill-form .primary-button { margin-top: 4px; }
.notice { padding: 12px 14px; border-radius: 10px; font-size: 12px; line-height: 1.7; margin-top: 18px; overflow-wrap: anywhere; }
.notice-success { background: #65c99c0e; color: #a1d9be; border: 1px solid #65c99c2e; }
.notice-error { background: #e57a8e0e; color: #f2a4ae; border: 1px solid #e57a8e33; }
.bill-results { margin-top: 22px; font-size: 12px; }
.customer-summary { background: #091423; border: 1px solid #abc9df1a; padding: 15px; border-radius: 11px; }
.customer-summary-heading { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 8px; }
.customer-summary-heading strong { overflow-wrap: anywhere; }
.customer-summary > span { color: #abd1e5; font-size: 11px; }
.customer-summary p { color: #9aacc2; line-height: 1.6; margin-top: 6px; font-size: 11px; }
.service-badge { color: #a6c3d8; background: #97bdd514; padding: 3px 8px; border-radius: 5px; font-size: 10px; flex-shrink: 0; }
.invoice-heading { display: flex; justify-content: space-between; gap: 12px; margin: 20px 0 12px; font-size: 11px; color: #b2c3d6; }
.invoice-heading strong { color: #e9c39a; }
.invoice-list { display: grid; gap: 10px; }
.invoice-item { display: flex; justify-content: space-between; gap: 12px; padding: 14px; border: 1px solid #abc9df1a; border-radius: 10px; }
.invoice-item > div:first-child { min-width: 0; }
.invoice-item strong { font-size: 11px; overflow-wrap: anywhere; }
.invoice-item p { color: #9aacc2; font-size: 10px; line-height: 1.6; margin-top: 4px; }
.invoice-status { font-size: 9px; color: #e9c39a; text-transform: uppercase; display: block; margin-top: 5px; }
.invoice-payment { display: flex; flex-direction: column; align-items: flex-end; gap: 10px; flex-shrink: 0; }
.invoice-payment a { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 6px; background: #a7d0df; color: #102335; font-size: 10px; font-weight: 700; }
.invoice-payment svg { width: 12px; height: 12px; }
.bill-guidance { font-size: 10px; line-height: 1.8; color: #91a6bf; margin: 15px 0 0; }
.paid-notice { display: flex; align-items: center; gap: 10px; }
.paid-notice svg { width: 20px; height: 20px; flex-shrink: 0; }
.spinner { width: 15px; height: 15px; border: 2px solid #10233540; border-top-color: #102335; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes dialog-enter { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 1100px) {
    .access-main { grid-template-columns: 1fr 420px; gap: 50px; padding-left: 36px; padding-right: 36px; }
    .access-header, .access-footer { padding-left: 36px; padding-right: 36px; }
    .welcome h1 { font-size: 37px; }
    .connection-art { transform: scale(.9); transform-origin: left center; }
}
@media (max-width: 850px) {
    .access-header { padding: 26px 28px; }
    .access-main { grid-template-columns: 1fr; max-width: 520px; gap: 30px; padding: 28px 28px 42px; }
    .welcome { text-align: center; }
    .welcome > .eyebrow { justify-content: center; font-size: 9px; }
    .eyebrow-line { display: none; }
    .welcome h1 { margin: 18px 0 14px; font-size: 36px; }
    .welcome-description { margin: 0 auto; font-size: 12px; max-width: 340px; }
    .connection-art { display: none; }
    .access-footer { padding: 20px 28px 26px; font-size: 9px; }
}
@media (max-width: 480px) {
    .access-header { padding: 22px 22px 16px; }
    .header-label { font-size: 8px; gap: 6px; letter-spacing: .1em; }
    .access-main { padding: 22px 20px 32px; gap: 26px; }
    .welcome h1 { font-size: 32px; }
    .welcome-description { font-size: 11px; max-width: 290px; }
    .welcome > .eyebrow { font-size: 8px; }
    .portal-panel { padding: 26px 20px 20px; border-radius: 20px; }
    .panel-heading h2 { font-size: 26px; }
    .portal-list { margin-top: 22px; }
    .portal-button { padding: 14px 12px; gap: 12px; }
    .portal-description { font-size: 10px; }
    .portal-icon { width: 38px; height: 38px; }
    .portal-name { font-size: 12px; }
    .access-footer { flex-direction: column; align-items: center; gap: 6px; text-align: center; }
    .dialog-viewport { padding: 20px 14px; }
    .dialog-panel { padding: 28px 24px; border-radius: 20px; }
    .access-dialog .dialog-title { font-size: 24px; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
}
</style>
