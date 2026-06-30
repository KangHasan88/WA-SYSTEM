#!/bin/bash
cd /var/www/kurmigo-wa/wa-sender

# Matikan semua proses
pkill -9 -f node 2>/dev/null
pkill -9 -f chrome 2>/dev/null
pkill -9 -f chromium 2>/dev/null
fuser -k 7070/tcp 2>/dev/null
sleep 2

# Hapus session jika ada parameter reset
if [ "$1" == "reset" ]; then
    rm -rf .wwebjs_auth .wwebjs_cache
    rm -rf /tmp/.com.google.Chrome* 2>/dev/null
    rm -rf /tmp/puppeteer* 2>/dev/null
    echo "Session cleared"
fi

# Jalankan Node.js di background
node sender.js > /var/log/wa-blast.log 2>&1 &

echo "Node.js started