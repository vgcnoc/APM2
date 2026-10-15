import express from 'express';
import cors from 'cors';
import qrcode from 'qrcode';
import pino from 'pino';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import makeWASocket, {
    DisconnectReason,
    useMultiFileAuthState,
    fetchLatestBaileysVersion,
    Browsers
} from '@whiskeysockets/baileys';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3001;
const SESSIONS_DIR = path.join(__dirname, 'sessions', 'auth_info_baileys');

app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

// Logger Baileys (silent / minimal agar log tidak banjir)
const logger = pino({ level: 'silent' });

// Global state
let sock = null;
let qrRaw = null;
let qrImageBase64 = null;
let connectionState = 'disconnected'; // 'disconnected', 'scan_qr', 'connecting', 'connected'
let connectedUser = null;
let connectedAt = null;
let reconnectTimer = null;

/**
 * Format nomor HP ke format WhatsApp JID Indonesia
 * contoh: 08123456789 -> 628123456789@s.whatsapp.net
 */
function formatToJid(number) {
    if (!number) return null;
    let clean = String(number).replace(/[^0-9]/g, '');
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (clean.startsWith('8')) {
        clean = '62' + clean;
    }
    return clean + '@s.whatsapp.net';
}

/**
 * Inisialisasi WhatsApp Baileys Socket
 */
async function startWASocket() {
    try {
        if (!fs.existsSync(SESSIONS_DIR)) {
            fs.mkdirSync(SESSIONS_DIR, { recursive: true });
        }

        const { state, saveCreds } = await useMultiFileAuthState(SESSIONS_DIR);
        const { version, isLatest } = await fetchLatestBaileysVersion();

        connectionState = 'connecting';
        console.log(`[WA-Gateway] Memulai Baileys v${version.join('.')} (Latest: ${isLatest})...`);

        sock = makeWASocket({
            version,
            logger,
            printQRInTerminal: false,
            auth: state,
            browser: Browsers.macOS('Desktop'),
            syncFullHistory: false,
            generateHighQualityLinkPreview: false,
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 60000,
        });

        // Simpan pembaruan kredensial sesi
        sock.ev.on('creds.update', saveCreds);

        // Pantau perubahan status koneksi
        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            // Jika menerima QR code baru
            if (qr) {
                qrRaw = qr;
                connectionState = 'scan_qr';
                try {
                    qrImageBase64 = await qrcode.toDataURL(qr, { margin: 2, scale: 8 });
                    console.log('[WA-Gateway] QR Code baru siap di-scan.');
                } catch (err) {
                    console.error('[WA-Gateway] Gagal mengonversi QR ke Base64:', err);
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

                console.log(`[WA-Gateway] Koneksi ditutup (Kode: ${statusCode}). Reconnect: ${shouldReconnect}`);

                connectionState = 'disconnected';
                connectedUser = null;
                qrRaw = null;
                qrImageBase64 = null;

                if (statusCode === DisconnectReason.loggedOut) {
                    console.log('[WA-Gateway] Perangkat telah logout. Menghapus sesi lama...');
                    clearSession();
                    setTimeout(startWASocket, 2000);
                } else if (shouldReconnect) {
                    if (reconnectTimer) clearTimeout(reconnectTimer);
                    reconnectTimer = setTimeout(() => {
                        console.log('[WA-Gateway] Mencoba menghubungkan kembali...');
                        startWASocket();
                    }, 5000);
                }
            } else if (connection === 'open') {
                console.log('[WA-Gateway] Terhubung ke WhatsApp!');
                connectionState = 'connected';
                qrRaw = null;
                qrImageBase64 = null;
                connectedAt = new Date().toISOString();

                const jid = sock.user?.id || '';
                const cleanPhone = jid.split(':')[0] || jid.split('@')[0];
                connectedUser = {
                    phone: cleanPhone,
                    name: sock.user?.name || 'WhatsApp Admin',
                    jid: jid,
                };
                console.log(`[WA-Gateway] Sesi aktif untuk nomor: ${cleanPhone}`);
            }
        });
    } catch (error) {
        console.error('[WA-Gateway] Fatal error saat inisialisasi Baileys:', error);
        connectionState = 'disconnected';
        setTimeout(startWASocket, 7000);
    }
}

/**
 * Hapus folder sesi saat logout
 */
function clearSession() {
    try {
        if (fs.existsSync(SESSIONS_DIR)) {
            fs.rmSync(SESSIONS_DIR, { recursive: true, force: true });
            console.log('[WA-Gateway] Folder sesi dibersihkan.');
        }
    } catch (e) {
        console.error('[WA-Gateway] Gagal menghapus folder sesi:', e.message);
    }
}

// ======================== ENDPOINTS REST API ========================

// 1. Health check
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        uptime: Math.round(process.uptime()),
        timestamp: new Date().toISOString(),
    });
});

// 2. Status koneksi & QR code
app.get('/api/status', (req, res) => {
    res.json({
        status: connectionState, // 'connected', 'scan_qr', 'connecting', 'disconnected'
        connected: connectionState === 'connected',
        user: connectedUser,
        qr_image: connectionState === 'scan_qr' ? qrImageBase64 : null,
        qr_raw: connectionState === 'scan_qr' ? qrRaw : null,
        connected_at: connectedAt,
        engine: 'Baileys Multi-Device Engine (Free & Open Source)',
    });
});

// 3. Kirim Pesan (Teks & Gambar)
app.post('/api/send-message', async (req, res) => {
    if (connectionState !== 'connected' || !sock) {
        return res.status(503).json({
            success: false,
            message: 'WhatsApp Gateway belum terhubung. Silakan scan barcode terlebih dahulu.',
        });
    }

    const { phone, message, media_url } = req.body;

    if (!phone || !message) {
        return res.status(400).json({
            success: false,
            message: 'Nomor telepon (phone) dan pesan (message) wajib diisi.',
        });
    }

    const jid = formatToJid(phone);
    if (!jid) {
        return res.status(400).json({
            success: false,
            message: 'Format nomor telepon tidak valid.',
        });
    }

    try {
        let sentMessage = null;

        // Jika ada lampiran gambar
        if (media_url && typeof media_url === 'string' && media_url.trim() !== '') {
            sentMessage = await sock.sendMessage(jid, {
                image: { url: media_url.trim() },
                caption: message,
            });
        } else {
            // Teks saja
            sentMessage = await sock.sendMessage(jid, {
                text: message,
            });
        }

        return res.json({
            success: true,
            messageId: sentMessage?.key?.id,
            timestamp: new Date().toISOString(),
            recipient: jid,
        });
    } catch (error) {
        console.error(`[WA-Gateway] Gagal mengirim pesan ke ${jid}:`, error.message);
        return res.status(500).json({
            success: false,
            message: 'Gagal mengirim pesan: ' + error.message,
        });
    }
});

// 4. Logout / Putuskan koneksi
app.post('/api/logout', async (req, res) => {
    try {
        if (sock) {
            try {
                await sock.logout();
            } catch (ignore) {}
        }
        clearSession();
        connectionState = 'disconnected';
        connectedUser = null;
        qrRaw = null;
        qrImageBase64 = null;

        setTimeout(startWASocket, 1500);

        return res.json({
            success: true,
            message: 'Koneksi WhatsApp berhasil diputuskan. Sesi baru akan segera dibuat.',
        });
    } catch (e) {
        return res.status(500).json({
            success: false,
            message: 'Error saat logout: ' + e.message,
        });
    }
});

// 5. Restart service / socket
app.post('/api/restart', async (req, res) => {
    try {
        if (sock) {
            try {
                sock.end();
            } catch (ignore) {}
        }
        setTimeout(startWASocket, 1000);
        return res.json({
            success: true,
            message: 'Layanan WhatsApp Gateway sedang direstart...',
        });
    } catch (e) {
        return res.status(500).json({
            success: false,
            message: 'Gagal restart: ' + e.message,
        });
    }
});

// Start Express HTTP Server
app.listen(PORT, '0.0.0.0', () => {
    console.log(`[WA-Gateway] Server berjalan di port ${PORT} (http://0.0.0.0:${PORT})`);
    startWASocket();
});
