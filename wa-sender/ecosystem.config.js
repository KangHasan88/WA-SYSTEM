module.exports = {
  apps: [{
    name: 'wa-blast',
    script: 'sender.js',
    cwd: '/var/www/kurmigo-wa/wa-sender',
    instances: 1,
    exec_mode: 'fork',
    watch: false,
    autorestart: true,
    restart_delay: 5000,
    max_restarts: 10,
    env: {
      NODE_ENV: 'production',
      PORT: 7070
    },
    error_file: '/var/log/wa-blast-error.log',
    out_file: '/var/log/wa-blast-out.log',
    log_file: '/var/log/wa-blast.log',
    time: true
  }]
};
