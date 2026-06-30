// ============================================
// WA BLAST SENDER - PRODUCTION VERSION (DENGAN MEDIA)
// Flexible Number Format: +628xxx, 628xxx, 08xxx
// Company: Kurmigo Group
// ============================================

const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();

// ============================================
// COMPANY CONFIGURATION
// ============================================
const COMPANY_NAME = 'Kurmigo Group';
const COMPANY_WEBSITE = 'https://wa.kurmigo.id';

// ============================================
// MEDIA STORAGE CONFIGURATION - SIMPAN LANGSUNG KE LARAVEL PUBLIC
// ============================================
// SEBELUMNYA: const MEDIA_STORAGE_PATH = path.join(__dirname, 'storage', 'wa-media');
// SESUDAH: langsung ke folder public Laravel
const MEDIA_STORAGE_PATH = '/var/www/kurmigo-wa/public/storage/wa-media';

// Buat folder jika belum ada
if (!fs.existsSync(MEDIA_STORAGE_PATH)) {
    fs.mkdirSync(MEDIA_STORAGE_PATH, { recursive: true });
    console.log(`📁 Created media storage: ${MEDIA_STORAGE_PATH}`);
}

// ============================================
// ALLOWED MIME TYPES FOR SECURITY
// ============================================
const ALLOWED_MIME_TYPES = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'video/mp4', 'video/3gp',
    'audio/mpeg', 'audio/ogg', 'audio/mp4', 'audio/aac',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain'
];

const MAX_FILE_SIZE = 16 * 1024 * 1024; // 16MB

// ============================================
// WEBHOOK CONFIGURATION
// ============================================
const WEBHOOK_URL = 'https://wa.kurmigo.id/wa-webhook/incoming';
const WEBHOOK_SECRET = 'kurmigo_secret_key_2024';

// ============================================
// CORS MIDDLEWARE
// ============================================
app.use((req, res, next) => {
    const allowedOrigins = [
        'http://localhost:8090',
        'http://localhost:3000',
        'http://127.0.0.1:8090',
        'http://127.0.0.1:3000',
        'http://31.97.106.123:3000',
        'https://wa.kurmigo.id',
        'http://wa.kurmigo.id',
        'http://wa.kurmigo.id:3000',
        'http://*.kurmigo.id',
    ];
    
    const origin = req.headers.origin;
    
    if (origin) {
        const isAllowed = allowedOrigins.some(allowed => {
            if (allowed.includes('*')) {
                const pattern = allowed.replace(/\*/g, '.*');
                const regex = new RegExp(`^${pattern}$`);
                return regex.test(origin);
            }
            return allowed === origin;
        });
        
        if (isAllowed) {
            res.header('Access-Control-Allow-Origin', origin);
        } else {
            res.header('Access-Control-Allow-Origin', origin);
        }
    } else {
        res.header('Access-Control-Allow-Origin', '*');
    }
    
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    res.header('Access-Control-Allow-Credentials', 'true');
    res.header('Access-Control-Max-Age', '86400');
    
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    
    next();
});

app.use(express.json({ limit: '20mb' }));
app.use(express.urlencoded({ extended: true, limit: '20mb' }));

app.use((req, res, next) => {
    req.url = req.url.replace(/^\/{2,}/, '/');
    next();
});

// ============================================
// ANTI SPAM CONFIGURATION
// ============================================
const ANTI_SPAM_CONFIG = {
    minDelay: 3000,
    maxDelay: 7000,
    maxMessagesPerHour: 100,
    messageCounter: 0,
    lastResetTime: Date.now()
};

// ============================================
// GLOBAL VARIABLES
// ============================================
let isReady = false;
let currentQR = null;
let reconnectAttempts = 0;
const maxReconnectAttempts = 10;
let client = null;

// ============================================
// FORMAT NUMBER
// ============================================
function formatNumber(number) {
    let rawNumber = number.toString().trim();
    let cleanNumber = rawNumber.replace(/\D/g, '');
    
    while (cleanNumber.startsWith('6262')) {
        cleanNumber = '62' + cleanNumber.substring(4);
    }
    
    if (cleanNumber.startsWith('0')) {
        cleanNumber = '62' + cleanNumber.substring(1);
    }
    
    if (!cleanNumber.startsWith('62')) {
        cleanNumber = '62' + cleanNumber;
    }
    
    const numberWithoutPrefix = cleanNumber.substring(2);
    if (numberWithoutPrefix.length < 9) {
        throw new Error(`Nomor terlalu pendek: ${rawNumber}`);
    }
    
    return cleanNumber;
}

// ============================================
// GET EXTENSION FROM MIME
// ============================================
function getExtensionFromMime(mimetype) {
    const mimeMap = {
        'image/jpeg': '.jpg',
        'image/png': '.png',
        'image/gif': '.gif',
        'image/webp': '.webp',
        'video/mp4': '.mp4',
        'video/3gp': '.3gp',
        'audio/mpeg': '.mp3',
        'audio/ogg': '.ogg',
        'audio/mp4': '.m4a',
        'application/pdf': '.pdf',
        'application/msword': '.doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document': '.docx',
        'application/vnd.ms-excel': '.xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': '.xlsx',
        'text/plain': '.txt'
    };
    return mimeMap[mimetype] || '.bin';
}

// ============================================
// SAVE MEDIA TO DISK (LANGSUNG KE PUBLIC LARAVEL)
// ============================================
async function saveMediaToDisk(media, messageId) {
    try {
        const timestamp = Date.now();
        const originalFilename = media.filename || `media_${timestamp}`;
        const ext = getExtensionFromMime(media.mimetype);
        const safeFilename = `${timestamp}_${messageId.substring(0, 8)}${ext}`;
        
        // Format folder: YYYY-MM-DD (pakai strip)
        const dateFolder = new Date().toISOString().slice(0, 10);
        const mediaDir = path.join(MEDIA_STORAGE_PATH, dateFolder);
        
        if (!fs.existsSync(mediaDir)) {
            fs.mkdirSync(mediaDir, { recursive: true });
            console.log(`📁 Created directory: ${mediaDir}`);
        }
        
        const filePath = path.join(mediaDir, safeFilename);
        const relativePath = `/storage/wa-media/${dateFolder}/${safeFilename}`;
        
        let mediaData;
        if (media.data) {
            if (Buffer.isBuffer(media.data)) {
                mediaData = media.data;
            } else if (typeof media.data === 'string') {
                if (media.data.startsWith('data:')) {
                    const base64Data = media.data.split(',')[1];
                    mediaData = Buffer.from(base64Data, 'base64');
                } else {
                    mediaData = Buffer.from(media.data, 'base64');
                }
            } else {
                mediaData = Buffer.from(media.data);
            }
        } else {
            console.log('⚠️ No media data found');
            return null;
        }
        
        if (mediaData.length > MAX_FILE_SIZE) {
            console.log(`❌ File too large: ${mediaData.length} bytes (max ${MAX_FILE_SIZE})`);
            return null;
        }
        
        fs.writeFileSync(filePath, mediaData);
        console.log(`✅ Media saved: ${filePath} (${mediaData.length} bytes)`);
        console.log(`✅ Public URL: ${relativePath}`);
        
        return {
            path: relativePath,
            absolutePath: filePath,
            size: mediaData.length,
            mime: media.mimetype,
            filename: originalFilename,
            thumbnail: null
        };
        
    } catch (error) {
        console.error('❌ Error saving media:', error.message);
        return null;
    }
}

// ============================================
// CREATE CLIENT
// ============================================
function cleanupSession() {
    const sessionPath = path.join(__dirname, '.wwebjs_auth');
    const cachePath = path.join(__dirname, '.wwebjs_cache');
    
    try {
        if (fs.existsSync(sessionPath)) {
            const lockFile = path.join(sessionPath, 'SingletonLock');
            if (fs.existsSync(lockFile)) fs.unlinkSync(lockFile);
            const socketFile = path.join(sessionPath, 'SingletonSocket');
            if (fs.existsSync(socketFile)) fs.unlinkSync(socketFile);
        }
        if (fs.existsSync(cachePath)) {
            const cacheLock = path.join(cachePath, 'SingletonLock');
            if (fs.existsSync(cacheLock)) fs.unlinkSync(cacheLock);
        }
    } catch (err) {
        console.log('⚠️ Cleanup error:', err.message);
    }
}

function getChromePath() {
    const os = require('os');
    const platform = os.platform();
    
    if (platform === 'win32') {
        const winPaths = [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            process.env.LOCALAPPDATA + '\\Google\\Chrome\\Application\\chrome.exe'
        ];
        for (const p of winPaths) {
            if (fs.existsSync(p)) return p;
        }
    }
    
    if (platform === 'darwin') {
        const macPaths = [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Chromium.app/Contents/MacOS/Chromium'
        ];
        for (const p of macPaths) {
            if (fs.existsSync(p)) return p;
        }
    }
    
    const linuxPaths = [
        '/usr/bin/google-chrome-stable',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium-browser',
        '/usr/bin/chromium',
        '/snap/bin/chromium',
    ];
    
    for (const p of linuxPaths) {
        if (fs.existsSync(p)) {
            console.log(`✅ Using browser: ${p}`);
            return p;
        }
    }
    
    console.log('⚠️ No browser found! Trying default');
    return '/usr/bin/google-chrome-stable';
}

function createClient() {
    cleanupSession();
    
    return new Client({
        authStrategy: new LocalAuth({
            dataPath: path.join(__dirname, '.wwebjs_auth'),
            clientId: 'wa-blast-client-' + Date.now()
        }),
        puppeteer: {
            executablePath: getChromePath(),
            headless: true,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--disable-software-rasterizer',
                '--disable-features=IsolateOrigins,site-per-process',
                '--disable-blink-features=AutomationControlled',
                '--disable-extensions',
                '--disable-background-networking',
                '--disable-default-apps',
                '--disable-sync',
                '--disable-translate',
                '--hide-scrollbars',
                '--metrics-recording-only',
                '--mute-audio',
                '--no-default-browser-check',
                '--no-pings',
                '--no-first-run',
                '--no-zygote'
            ],
            defaultViewport: null,
            protocolTimeout: 180000,
            timeout: 180000,
            ignoreHTTPSErrors: true,
            handleSIGINT: false,
            handleSIGTERM: false,
            handleSIGHUP: false
        }
    });
}

// ============================================
// SEND TO LARAVEL WEBHOOK
// ============================================
async function sendToWebhook(payload) {
    try {
        const fetch = await import('node-fetch');
        console.log(`📤 Sending webhook to: ${WEBHOOK_URL}`);
        
        const response = await fetch.default(WEBHOOK_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        if (response.ok) {
            const result = await response.json();
            console.log('✅ Message forwarded to Laravel');
        } else {
            console.log(`❌ Failed to forward message: HTTP ${response.status}`);
        }
    } catch (err) {
        console.error('❌ Error sending webhook:', err.message);
    }
}

// ============================================
// GET REAL PHONE NUMBER FROM CONTACT
// ============================================
async function getRealPhoneNumber(message) {
    try {
        const contact = await message.getContact();
        if (contact && contact.number) {
            let number = contact.number;
            number = number.replace(/\D/g, '');
            if (number.startsWith('0')) {
                number = '62' + number.substring(1);
            }
            if (!number.startsWith('62')) {
                number = '62' + number;
            }
            console.log(`📇 Contact number found: ${number}`);
            return number;
        }
    } catch (err) {
        console.log(`Could not get contact info: ${err.message}`);
    }
    return null;
}

// ============================================
// INCOMING MESSAGE HANDLER
// ============================================
async function handleIncomingMessage(message) {
    if (message.fromMe || message.isGroup) return;
    
    const ignoredTypes = ['status_v3', 'e2e_notification', 'notification_c', 'call_log', 'revoked', 'chatstate'];
    if (ignoredTypes.includes(message.type)) return;
    
    console.log(`\n📨 Raw from: ${message.from}`);
    console.log(`Message body: ${message.body || '(media message)'}`);
    console.log(`Message type: ${message.type}`);
    console.log(`Has media: ${message.hasMedia}`);
    
    let realNumber = await getRealPhoneNumber(message);
    
    let fromNumber = message.from;
    
    if (realNumber) {
        fromNumber = realNumber;
        console.log(`✅ Using contact number: ${fromNumber}`);
    } else {
        fromNumber = fromNumber.replace('@c.us', '');
        fromNumber = fromNumber.replace('@lid', '');
        
        if (fromNumber.match(/^\d+$/)) {
            if (fromNumber.length > 14) {
                console.log(`⚠️ Possible LID (${fromNumber.length} digits): ${fromNumber}`);
                console.log(`⏭️ Skipping LID message - cannot reply to this number`);
                return;
            }
            if (fromNumber.startsWith('0')) {
                fromNumber = '62' + fromNumber.substring(1);
            }
            if (!fromNumber.startsWith('62')) {
                fromNumber = '62' + fromNumber;
            }
        }
    }
    
    if (!fromNumber.startsWith('62') || fromNumber.length < 10 || fromNumber.length > 14) {
        console.log(`⏭️ Skipping invalid number: ${fromNumber}`);
        return;
    }
    
    console.log(`✅ Final number: ${fromNumber}`);
    
    const payload = {
        from: fromNumber,
        from_name: message.notifyName || null,
        message: message.body || '',
        messageId: message.id.id,
        timestamp: message.timestamp,
        type: message.type,
        hasMedia: message.hasMedia,
        isForwarded: message.isForwarded
    };
    
    if (message.hasMedia) {
        try {
            const media = await message.downloadMedia();
            if (media && media.data) {
                const savedMedia = await saveMediaToDisk(media, message.id.id);
                
                if (savedMedia) {
                    payload.media = {
                        url: savedMedia.path,
                        mime: savedMedia.mime,
                        size: savedMedia.size,
                        filename: savedMedia.filename,
                        thumbnail: savedMedia.thumbnail
                    };
                    console.log(`📎 Media saved: ${savedMedia.filename} (${savedMedia.size} bytes)`);
                } else {
                    payload.media = {
                        mime: media.mimetype,
                        size: media.data ? media.data.length : 0,
                        filename: media.filename || 'unknown'
                    };
                    console.log(`📎 Media info only (not saved): ${media.mimetype}`);
                }
            } else {
                console.log(`⚠️ Media download returned empty data`);
                payload.media = {
                    mime: media.mimetype,
                    filename: media.filename || 'unknown'
                };
            }
        } catch (err) {
            console.error('Error downloading media:', err.message);
            payload.mediaError = err.message;
        }
    }
    
    await sendToWebhook(payload);
}

function initClient() {
    if (client) {
        try {
            client.destroy();
        } catch (e) {}
    }
    
    client = createClient();
    
    client.on('qr', qr => {
        currentQR = qr;
        isReady = false;
        console.log('\n📱 SCAN QR CODE - ' + COMPANY_NAME);
        qrcode.generate(qr, { small: true });
        console.log('QR juga tersedia di: /wa-qr\n');
    });
    
    client.on('ready', () => {
        console.log(`\n✅ ${COMPANY_NAME} - WhatsApp Ready!`);
        isReady = true;
        currentQR = null;
        reconnectAttempts = 0;
    });
    
    client.on('message', handleIncomingMessage);
    
    client.on('disconnected', async (reason) => {
        console.log(`📴 ${COMPANY_NAME} - Disconnected:`, reason);
        isReady = false;
        currentQR = null;
        
        if (reconnectAttempts < maxReconnectAttempts) {
            reconnectAttempts++;
            console.log(`🔄 Reconnect attempt ${reconnectAttempts}/${maxReconnectAttempts} in 10 seconds...`);
            setTimeout(() => initClient(), 10000);
        } else {
            console.log(`❌ ${COMPANY_NAME} - Max reconnect attempts reached. Please restart manually.`);
        }
    });
    
    client.on('auth_failure', msg => {
        console.error(`❌ ${COMPANY_NAME} - Auth failure:`, msg);
        isReady = false;
    });
    
    console.log(`🔄 ${COMPANY_NAME} - Initializing...`);
    client.initialize();
}

// ============================================
// API ENDPOINTS
// ============================================

app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok',
        company: COMPANY_NAME,
        ready: isReady,
        uptime: process.uptime()
    });
});

app.get('/wa-status', (req, res) => {
    res.json({
        company: COMPANY_NAME,
        connected: isReady,
        status: isReady ? 'connected' : 'disconnected',
        qr: (!isReady && currentQR) ? currentQR : null,
        timestamp: new Date().toISOString()
    });
});

app.get('/wa-proxy/wa-status', (req, res) => {
    res.json({
        company: COMPANY_NAME,
        connected: isReady,
        status: isReady ? 'connected' : 'disconnected',
        qr: (!isReady && currentQR) ? currentQR : null,
        timestamp: new Date().toISOString(),
        proxy: true
    });
});

app.post('/wa-proxy/send', async (req, res) => {
    const { number, message, image_url } = req.body;
    
    if (!number || !message) {
        return res.status(400).json({ 
            status: 'error', 
            error: 'Parameter number dan message wajib diisi' 
        });
    }
    
    if (!isReady) {
        return res.status(503).json({ 
            status: 'error', 
            error: 'WhatsApp client belum siap. Tunggu koneksi stabil.' 
        });
    }
    
    let formattedNumber;
    try {
        formattedNumber = formatNumber(number);
    } catch (e) {
        return res.json({ status: 'invalid', error: e.message });
    }
    
    try {
        const chatId = formattedNumber + '@c.us';
        
        if (image_url && image_url.trim()) {
            const media = await MessageMedia.fromUrl(image_url, { 
                unsafeMime: true,
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                }
            });
            await client.sendMessage(chatId, media, { caption: message });
        } else {
            await client.sendMessage(chatId, message);
        }
        
        ANTI_SPAM_CONFIG.messageCounter++;
        
        res.json({ 
            status: 'success', 
            number: formattedNumber,
            sentAt: new Date().toISOString(),
            has_image: !!image_url
        });
    } catch (error) {
        res.json({ status: 'error', error: error.message });
    }
});

app.post('/send-media', async (req, res) => {
    const { number, message, media_base64, media_mime, media_filename } = req.body;
    
    if (!number) {
        return res.status(400).json({ status: 'error', error: 'Parameter number wajib diisi' });
    }
    
    if (!isReady) {
        return res.status(503).json({ status: 'error', error: 'WhatsApp client belum siap' });
    }
    
    let formattedNumber;
    try {
        formattedNumber = formatNumber(number);
    } catch (e) {
        return res.json({ status: 'invalid', error: e.message });
    }
    
    try {
        const chatId = formattedNumber + '@c.us';
        
        if (media_base64 && media_mime) {
            const media = new MessageMedia(media_mime, media_base64, media_filename || 'file');
            await client.sendMessage(chatId, media, { caption: message || '' });
        } else {
            await client.sendMessage(chatId, message || '');
        }
        
        res.json({ 
            status: 'success', 
            number: formattedNumber,
            sentAt: new Date().toISOString()
        });
    } catch (error) {
        console.error('Error sending media:', error);
        res.json({ status: 'error', error: error.message });
    }
});

app.get('/wa-qr', (req, res) => {
    if (!isReady && currentQR) {
        res.json({ qr: currentQR, status: 'waiting' });
    } else if (isReady) {
        res.json({ qr: null, status: 'connected' });
    } else {
        res.json({ qr: null, status: 'disconnected' });
    }
});

app.post('/send', async (req, res) => {
    const { number, message, image_url } = req.body;
    
    if (!number || !message) {
        return res.status(400).json({ 
            status: 'error', 
            error: 'Parameter number dan message wajib diisi' 
        });
    }
    
    if (!isReady) {
        return res.status(503).json({ 
            status: 'error', 
            error: 'WhatsApp client belum siap. Tunggu koneksi stabil.' 
        });
    }
    
    let formattedNumber;
    try {
        formattedNumber = formatNumber(number);
    } catch (e) {
        return res.json({ status: 'invalid', error: e.message });
    }
    
    try {
        const chatId = formattedNumber + '@c.us';
        
        if (image_url && image_url.trim()) {
            const media = await MessageMedia.fromUrl(image_url, { 
                unsafeMime: true,
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                }
            });
            await client.sendMessage(chatId, media, { caption: message });
        } else {
            await client.sendMessage(chatId, message);
        }
        
        ANTI_SPAM_CONFIG.messageCounter++;
        
        res.json({ 
            status: 'success', 
            number: formattedNumber,
            sentAt: new Date().toISOString(),
            has_image: !!image_url
        });
    } catch (error) {
        res.json({ status: 'error', error: error.message });
    }
});

app.get('/', (req, res) => {
    res.json({
        name: 'WA Blast Sender',
        company: COMPANY_NAME,
        website: COMPANY_WEBSITE,
        status: isReady ? 'connected' : 'disconnected',
        endpoints: {
            status: '/wa-status',
            proxy_status: '/wa-proxy/wa-status',
            send: '/send (POST)',
            send_media: '/send-media (POST)',
            proxy_send: '/wa-proxy/send (POST)',
            qr: '/wa-qr',
            health: '/health'
        }
    });
});

// ============================================
// RATE LIMIT RESET
// ============================================
setInterval(() => {
    ANTI_SPAM_CONFIG.messageCounter = 0;
    ANTI_SPAM_CONFIG.lastResetTime = Date.now();
    console.log('🔄 Rate limit counter direset (1 jam berlalu)');
}, 3600000);

// ============================================
// START SERVER
// ============================================
const PORT = process.env.PORT || 7070;
const HOST = process.env.HOST || '127.0.0.1';

app.listen(PORT, HOST, () => {
    console.log(`\n🚀 ${COMPANY_NAME} - WA Blast Server`);
    console.log(`✅ Running on: http://${HOST}:${PORT}`);
    console.log(`🔒 Public access disabled; use Laravel proxy/API gateway`);
    console.log(`📨 Webhook URL: ${WEBHOOK_URL}`);
    console.log(`📁 Media storage: ${MEDIA_STORAGE_PATH}\n`);
});

initClient();

// ============================================
// GRACEFUL SHUTDOWN
// ============================================
process.on('SIGINT', async () => {
    console.log('\n\n🛑 Shutting down gracefully...');
    if (client) {
        try {
            await client.destroy();
            console.log('✅ WhatsApp client closed');
        } catch (err) {
            console.log('⚠️ Error closing client:', err.message);
        }
    }
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('\n\n🛑 SIGTERM received, shutting down...');
    if (client) {
        await client.destroy();
    }
    process.exit(0);
});

process.on('uncaughtException', (error) => {
    console.error('❌ Uncaught Exception:', error);
});

process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Unhandled Rejection:', reason);
});
