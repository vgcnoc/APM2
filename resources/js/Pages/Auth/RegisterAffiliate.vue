<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { ArrowLeftIcon, ArrowRightIcon, CheckIcon, EyeIcon, EyeSlashIcon, LockClosedIcon, ShieldCheckIcon, UserGroupIcon, BanknotesIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import AppLogo from '@/Components/AppLogo.vue';

const props = defineProps({ initialRef: String, upline: Object, commissionSettings: Object });
const page = usePage();
const brandName = computed(() => page.props.app_name || page.props.company_name || 'VIZBILL');
const form = useForm({
    name: '', email: '', phone: '', password: '', password_confirmation: '',
    referral_code: props.initialRef || '', bank_name: '', bank_account_number: '',
    bank_account_holder: '', address: '',
});
const bankOptions = ['BCA', 'Bank Mandiri', 'BRI', 'BNI', 'Bank Syariah Indonesia (BSI)', 'Bank Jago', 'SeaBank', 'DANA', 'GoPay', 'OVO', 'ShopeePay'];
const showPassword = ref(false);
const confirmedSponsor = computed(() => props.upline
    && form.referral_code.trim().toUpperCase() === props.upline.code.toUpperCase() ? props.upline : null);
const formatRupiah = value => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(value ?? 0);
const benefits = computed(() => [
    { icon: BanknotesIcon, title: 'Komisi booking', amount: formatRupiah(props.commissionSettings?.booking ?? 35000),
        detail: 'Untuk booking pelanggan sesuai ketentuan program.', tone: 'blue' },
    { icon: ChartBarIcon, title: 'Komisi bulanan', amount: formatRupiah(props.commissionSettings?.monthly ?? 5000),
        detail: 'Per pelanggan yang membayar tagihan setiap bulan.', tone: 'green' },
    { icon: UserGroupIcon, title: 'Komisi jaringan', amount: `${formatRupiah(props.commissionSettings?.upline1 ?? 2000)} / ${formatRupiah(props.commissionSettings?.upline2 ?? 1000)}`,
        detail: 'Komisi level 1 dan level 2 sesuai ketentuan program.', tone: 'amber' },
]);
const submit = () => {
    form.transform(data => ({ ...data, referral_code: data.referral_code.trim().toUpperCase() }));
    form.post(route('register-affiliate.store'), {
        onError: () => requestAnimationFrame(() => document.querySelector('.affiliate-register [aria-invalid="true"]')?.focus()),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
let previousZoom = '';
onMounted(() => {
    previousZoom = document.body.style.zoom;
    document.body.classList.add('auth-device-scale');
    document.body.style.zoom = '100%';
});
onUnmounted(() => {
    document.body.classList.remove('auth-device-scale');
    document.body.style.zoom = previousZoom;
});
</script>

<template>
    <Head title="Daftar Mitra Afiliasi" />
    <div class="affiliate-register">
        <header class="registration-header">
            <Link :href="route('login')" aria-label="Kembali ke portal layanan"><AppLogo context="navbar" white /></Link>
            <Link :href="route('login')" class="back-link"><ArrowLeftIcon /> Kembali ke portal</Link>
        </header>

        <main class="registration-main">
            <section class="partnership-intro" aria-labelledby="partnership-title">
                <p class="eyebrow"><span></span> KEMITRAAN SALES &amp; AFILIASI</p>
                <h1 id="partnership-title">Koneksi baru.<br /><span>Peluang lebih luas.</span></h1>
                <p class="intro-description">Jadilah bagian dari jaringan mitra kami. Bantu pelanggan terhubung, kembangkan tim, dan pantau komisi Anda dalam satu portal.</p>

                <div class="benefit-list">
                    <article v-for="benefit in benefits" :key="benefit.title" class="benefit-item" :class="`tone-${benefit.tone}`">
                        <div class="benefit-icon"><component :is="benefit.icon" /></div>
                        <div class="benefit-copy">
                            <p>{{ benefit.title }}</p><strong>{{ benefit.amount }}</strong><span>{{ benefit.detail }}</span>
                        </div>
                    </article>
                </div>
                <div class="invitation-note">
                    <ShieldCheckIcon />
                    <div><strong>Bergabung melalui undangan.</strong><p>Siapkan kode referral aktif dari pengajak Anda. Setiap pendaftaran akan terhubung ke jaringan pengajak yang terdaftar.</p></div>
                </div>
            </section>

            <section class="registration-panel" aria-labelledby="registration-title">
                <div class="panel-heading">
                    <p class="eyebrow">MULAI KEMITRAAN ANDA</p>
                    <h2 id="registration-title">Daftar sebagai mitra.</h2>
                    <p>Lengkapi data Anda untuk mengakses Portal Sales &amp; Afiliasi.</p>
                </div>
                <div v-if="Object.keys(form.errors).length" class="error-summary" role="alert">Pendaftaran belum berhasil. Periksa kolom yang ditandai di bawah.</div>

                <form @submit.prevent="submit">
                    <fieldset class="form-section referral-section">
                        <legend><span class="section-number">01</span> Undangan kemitraan</legend>
                        <div class="form-field">
                            <label for="referral-code">Kode Referral Pengajak <span class="required">*</span></label>
                            <input id="referral-code" v-model="form.referral_code" class="form-control referral-input" type="text" required maxlength="50" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="Masukkan kode referral pengajak" :aria-invalid="!!form.errors.referral_code" aria-describedby="referral-help referral-error" @input="form.clearErrors('referral_code')" />
                            <p id="referral-help" class="field-help">Wajib menggunakan kode referral yang terdaftar dan aktif. Minta kode kepada pengajak Anda.</p>
                            <p v-if="form.errors.referral_code" id="referral-error" class="field-error">{{ form.errors.referral_code }}</p>
                        </div>
                        <div v-if="confirmedSponsor" class="sponsor-notice" role="status"><CheckIcon /><span>Diundang oleh <strong>{{ confirmedSponsor.name }}</strong><small>{{ confirmedSponsor.code }}</small></span></div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend><span class="section-number">02</span> Informasi akun</legend>
                        <div class="fields-grid">
                            <div class="form-field full-width">
                                <label for="affiliate-name">Nama lengkap <span class="required">*</span></label>
                                <input id="affiliate-name" v-model="form.name" class="form-control" type="text" required maxlength="255" autocomplete="name" placeholder="Nama sesuai identitas" :aria-invalid="!!form.errors.name" aria-describedby="name-error" />
                                <p v-if="form.errors.name" id="name-error" class="field-error">{{ form.errors.name }}</p>
                            </div>
                            <div class="form-field">
                                <label for="affiliate-phone">WhatsApp aktif <span class="required">*</span></label>
                                <input id="affiliate-phone" v-model="form.phone" class="form-control" type="tel" required maxlength="25" autocomplete="tel" placeholder="08xxxxxxxxxx" :aria-invalid="!!form.errors.phone" aria-describedby="phone-error" />
                                <p v-if="form.errors.phone" id="phone-error" class="field-error">{{ form.errors.phone }}</p>
                            </div>
                            <div class="form-field">
                                <label for="affiliate-email">Email akun <span class="required">*</span></label>
                                <input id="affiliate-email" v-model="form.email" class="form-control" type="email" required maxlength="255" autocomplete="email" placeholder="nama@email.com" :aria-invalid="!!form.errors.email" aria-describedby="email-error" />
                                <p v-if="form.errors.email" id="email-error" class="field-error">{{ form.errors.email }}</p>
                            </div>
                            <div class="form-field">
                                <label for="affiliate-password">Kata sandi <span class="required">*</span></label>
                                <div class="password-field">
                                    <input id="affiliate-password" v-model="form.password" class="form-control" :type="showPassword ? 'text' : 'password'" required minlength="6" autocomplete="new-password" placeholder="Minimal 6 karakter" :aria-invalid="!!form.errors.password" aria-describedby="password-error" />
                                    <button type="button" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="showPassword" @click="showPassword = !showPassword"><EyeSlashIcon v-if="showPassword" /><EyeIcon v-else /></button>
                                </div>
                                <p v-if="form.errors.password" id="password-error" class="field-error">{{ form.errors.password }}</p>
                            </div>
                            <div class="form-field">
                                <label for="affiliate-password-confirmation">Konfirmasi kata sandi <span class="required">*</span></label>
                                <input id="affiliate-password-confirmation" v-model="form.password_confirmation" class="form-control" :type="showPassword ? 'text' : 'password'" required minlength="6" autocomplete="new-password" placeholder="Ulangi kata sandi" :aria-invalid="!!form.errors.password_confirmation" aria-describedby="password-confirmation-error" />
                                <p v-if="form.errors.password_confirmation" id="password-confirmation-error" class="field-error">{{ form.errors.password_confirmation }}</p>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend><span class="section-number">03</span> Rekening pencairan</legend>
                        <p class="section-description">Komisi disalurkan ke rekening bank atau e-wallet atas nama Anda.</p>
                        <div class="fields-grid">
                            <div class="form-field">
                                <label for="affiliate-bank">Bank / e-wallet <span class="required">*</span></label>
                                <select id="affiliate-bank" v-model="form.bank_name" class="form-control" required :aria-invalid="!!form.errors.bank_name" aria-describedby="bank-error"><option disabled value="">Pilih bank / e-wallet</option><option v-for="bank in bankOptions" :key="bank" :value="bank">{{ bank }}</option></select>
                                <p v-if="form.errors.bank_name" id="bank-error" class="field-error">{{ form.errors.bank_name }}</p>
                            </div>
                            <div class="form-field">
                                <label for="affiliate-bank-number">Nomor rekening / e-wallet <span class="required">*</span></label>
                                <input id="affiliate-bank-number" v-model="form.bank_account_number" class="form-control" type="text" inputmode="numeric" required maxlength="50" placeholder="Nomor rekening atau HP" :aria-invalid="!!form.errors.bank_account_number" aria-describedby="bank-number-error" />
                                <p v-if="form.errors.bank_account_number" id="bank-number-error" class="field-error">{{ form.errors.bank_account_number }}</p>
                            </div>
                            <div class="form-field full-width">
                                <label for="affiliate-bank-holder">Nama pemilik rekening <span class="required">*</span></label>
                                <input id="affiliate-bank-holder" v-model="form.bank_account_holder" class="form-control" type="text" required maxlength="100" autocomplete="off" placeholder="Sesuai nama di rekening / e-wallet" :aria-invalid="!!form.errors.bank_account_holder" aria-describedby="bank-holder-error" />
                                <p v-if="form.errors.bank_account_holder" id="bank-holder-error" class="field-error">{{ form.errors.bank_account_holder }}</p>
                            </div>
                        </div>
                    </fieldset>
                    <div class="form-actions">
                        <button type="submit" class="register-button" :disabled="form.processing"><span v-if="form.processing" class="spinner" aria-hidden="true"></span>{{ form.processing ? 'Memproses pendaftaran…' : 'Buat akun mitra' }}<ArrowRightIcon v-if="!form.processing" /></button>
                        <p class="secure-note"><LockClosedIcon /> Pastikan data akun dan rekening Anda sudah benar.</p>
                    </div>
                </form>
                <p class="login-note">Sudah memiliki akun? <Link :href="route('login', { portal: 'affiliate' })">Masuk ke Portal Sales <ArrowRightIcon /></Link></p>
            </section>
        </main>
        <footer class="registration-footer"><span>&copy; {{ new Date().getFullYear() }} {{ brandName }}.</span><span>Portal Kemitraan Sales &amp; Afiliasi</span></footer>
    </div>
</template>

<style scoped>
.affiliate-register { min-height: 100vh; color-scheme: dark; background: radial-gradient(ellipse at 0% 20%, #16304766, transparent 48%), #090f19; color: #edf4fc; font-family: Inter, ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
.registration-header, .registration-main, .registration-footer { width: min(1200px, 100%); margin: 0 auto; }
.registration-header { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 34px 40px; }
.back-link { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; color: #acbfd3; }
.back-link:hover { color: #fff; }
.back-link svg { width: 16px; height: 16px; }
.registration-main { display: grid; grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr); align-items: start; gap: 76px; padding: 38px 40px 64px; }
.partnership-intro { padding-top: 38px; position: sticky; top: 36px; }
.eyebrow { display: flex; align-items: center; gap: 9px; color: #8ac6de; font-size: 10px; font-weight: 700; letter-spacing: .15em; }
.eyebrow > span { width: 6px; height: 6px; background: #7cccb7; border-radius: 50%; }
.partnership-intro h1 { color: #f3f7fc; font-size: clamp(34px, 3.7vw, 48px); font-weight: 700; line-height: 1.2; letter-spacing: -.045em; margin: 24px 0 20px; }
.partnership-intro h1 span { color: #8dbbd1; }
.intro-description { color: #9eb1c8; font-size: 14px; line-height: 1.9; max-width: 410px; }
.benefit-list { display: grid; margin-top: 36px; border-top: 1px solid #accde21a; }
.benefit-item { display: flex; align-items: flex-start; gap: 16px; padding: 22px 0; border-bottom: 1px solid #accde21a; }
.benefit-icon { width: 42px; height: 42px; display: grid; place-items: center; border: 1px solid #8dbbd133; background: #8dbbd10c; color: #9bc8dd; border-radius: 12px; flex-shrink: 0; }
.benefit-icon svg { width: 21px; height: 21px; }
.tone-green .benefit-icon { color: #92d6b8; border-color: #92d6b833; background: #92d6b80a; }
.tone-amber .benefit-icon { color: #e4c18e; border-color: #e4c18e33; background: #e4c18e0a; }
.benefit-copy { min-width: 0; }
.benefit-copy p { font-size: 11px; color: #a9bbcf; margin-bottom: 5px; }
.benefit-copy strong { display: block; color: #e7f2ff; font-size: 21px; font-weight: 650; letter-spacing: -.025em; }
.benefit-copy > span { display: block; color: #8fa4bd; font-size: 11px; line-height: 1.7; margin-top: 5px; }
.invitation-note { display: flex; align-items: flex-start; gap: 12px; margin-top: 28px; }
.invitation-note > svg { width: 21px; height: 21px; flex-shrink: 0; color: #8dbbd1; margin-top: 2px; }
.invitation-note strong { font-size: 12px; color: #c8d7e8; font-weight: 600; }
.invitation-note p { color: #8fa4bd; font-size: 11px; line-height: 1.8; margin-top: 6px; }
.registration-panel { min-width: 0; border: 1px solid #b0cce224; border-radius: 24px; background: linear-gradient(145deg, #14202e, #101a28); box-shadow: 0 24px 70px #00000020; padding: 32px; }
.panel-heading h2 { font-size: 28px; font-weight: 650; letter-spacing: -.04em; color: #f3f7fc; margin: 12px 0 8px; line-height: 1.25; }
.panel-heading > p:last-child { font-size: 12px; color: #a2b6cd; line-height: 1.8; }
.registration-panel form { margin-top: 30px; }
.form-section { min-width: 0; margin: 0 0 26px; padding: 0 0 26px; border-bottom: 1px solid #accde21a; }
.form-section legend { display: flex; align-items: center; gap: 10px; padding: 0; margin-bottom: 18px; color: #d9e5f4; font-size: 12px; font-weight: 600; }
.section-number { display: inline-flex; justify-content: center; align-items: center; width: 25px; height: 25px; border-radius: 7px; background: #8dbbd110; border: 1px solid #8dbbd127; color: #9cc4d8; font-size: 10px; font-variant-numeric: tabular-nums; }
.fields-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 16px; }
.full-width { grid-column: 1 / -1; }
.form-field { min-width: 0; }
.form-field label { display: block; font-size: 11px; line-height: 1.6; color: #c8d7e9; font-weight: 500; margin-bottom: 7px; }
.required { color: #8dbbd1; margin-left: 2px; }
.affiliate-register .form-control { display: block; width: 100%; min-width: 0; height: 44px; padding: 10px 12px; background: #0a1523 !important; color: #edf4fc !important; border: 1px solid #a4c0da30 !important; border-radius: 9px; font-family: inherit; font-size: 12px; line-height: 1.5; transition: border-color .15s, box-shadow .15s; }
.affiliate-register .form-control::placeholder { color: #7f96ae !important; opacity: 1; }
.affiliate-register .form-control:focus { outline: none; border-color: #8dbbd1 !important; background: #0e1b2b !important; box-shadow: 0 0 0 3px #8dbbd11f !important; --tw-ring-shadow: 0 0 #0000; }
.affiliate-register .form-control[aria-invalid="true"] { border-color: #e294a2 !important; }
.affiliate-register .form-control:-webkit-autofill { -webkit-text-fill-color: #edf4fc !important; -webkit-box-shadow: 0 0 0 1000px #0a1523 inset !important; caret-color: #edf4fc; }
.affiliate-register select.form-control { padding-right: 32px; }
.affiliate-register .referral-input { text-transform: uppercase; font-family: ui-monospace, monospace; letter-spacing: .04em; border-color: #8dbbd15c !important; }
.referral-input::placeholder { text-transform: none; letter-spacing: 0; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
.field-help { color: #91a8c0; font-size: 11px; line-height: 1.7; margin-top: 8px; }
.field-error { color: #ffb0bc; font-size: 11px; line-height: 1.7; margin: 7px 0 0; }
.sponsor-notice { display: flex; align-items: flex-start; gap: 9px; padding: 11px 12px; margin-top: 12px; border: 1px solid #92d6b82e; border-radius: 9px; background: #92d6b80a; color: #b6dbc9; font-size: 11px; line-height: 1.7; overflow-wrap: anywhere; }
.sponsor-notice svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; }
.sponsor-notice small { display: block; color: #9cbdaf; font-family: ui-monospace, monospace; font-size: 10px; }
.section-description { color: #91a8c0; font-size: 11px; line-height: 1.7; margin: -6px 0 16px; }
.password-field { position: relative; }
.password-field .form-control { padding-right: 42px; }
.password-field button { position: absolute; right: 4px; top: 4px; height: 36px; width: 34px; display: grid; place-items: center; color: #91a8c0; border-radius: 6px; }
.password-field button:hover { color: #e5f1ff; background: #8dbbd114; }
.password-field svg { width: 18px; height: 18px; }
.error-summary { color: #ffb0bc; background: #dd7c8b0d; border: 1px solid #dd7c8b40; border-radius: 9px; font-size: 12px; line-height: 1.7; padding: 12px 14px; margin-top: 20px; }
.register-button { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; min-height: 46px; border: 1px solid #afd2e354; border-radius: 10px; padding: 12px 18px; font-size: 12px; font-weight: 700; color: #112638; background: #a7d0df; transition: background .15s; }
.register-button:hover { background: #c2e3ec; }
.register-button:disabled { opacity: .65; cursor: wait; }
.register-button svg { width: 17px; height: 17px; }
.secure-note { display: flex; align-items: center; justify-content: center; gap: 6px; color: #8ea4bc; font-size: 10px; line-height: 1.7; text-align: center; margin-top: 12px; }
.secure-note svg { width: 13px; height: 13px; flex-shrink: 0; }
.login-note { display: flex; justify-content: center; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 24px; padding-top: 22px; border-top: 1px solid #accde21a; color: #9db1c9; font-size: 11px; }
.login-note a { display: inline-flex; align-items: center; gap: 5px; color: #b7d9eb; font-weight: 600; }
.login-note svg { width: 13px; height: 13px; }
.registration-footer { display: flex; justify-content: space-between; gap: 16px; color: #7890aa; font-size: 10px; line-height: 1.7; padding: 24px 40px; border-top: 1px solid #accde212; }
.affiliate-register :is(button, a):focus-visible { outline: 2px solid #9cc4d8; outline-offset: 4px; }
.spinner { width: 15px; height: 15px; border: 2px solid #11263840; border-top-color: #112638; border-radius: 50%; animation: spin .7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 1050px) { .registration-main { gap: 36px; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); } .registration-panel { padding: 26px; } .partnership-intro h1 { font-size: 36px; } }
@media (max-width: 800px) { .registration-header { padding: 26px 24px; } .registration-main { grid-template-columns: minmax(0, 1fr); gap: 30px; max-width: 620px; padding: 20px 24px 42px; } .partnership-intro { position: static; padding-top: 0; } .partnership-intro h1 { font-size: 38px; margin-top: 18px; } .intro-description { max-width: none; font-size: 13px; } .benefit-list { margin-top: 24px; } .benefit-item { padding: 16px 0; } .invitation-note { display: none; } .registration-footer { padding: 22px 24px; } }
@media (max-width: 480px) { .registration-header { padding: 22px 20px; } .back-link { font-size: 10px; gap: 5px; } .registration-main { padding: 14px 16px 32px; } .partnership-intro { padding: 0 6px; } .partnership-intro h1 { font-size: 34px; } .registration-panel { padding: 24px 20px; border-radius: 20px; } .panel-heading h2 { font-size: 25px; } .fields-grid { grid-template-columns: minmax(0, 1fr); } .form-section { margin-bottom: 22px; padding-bottom: 22px; } .registration-footer { flex-direction: column; gap: 4px; text-align: center; } }
@media (prefers-reduced-motion: reduce) { .spinner { animation: none; } .form-control, .register-button { transition: none; } }
</style>
