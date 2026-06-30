#!/bin/bash
pkill -9 -f "node" 2>/dev/null
pkill -9 -f "sender.js" 2>/dev/null
fuser -k 7070/tcp 2>/dev/null
rm -f /var/run/wa-blast.pid
echo "Node.js stopped"