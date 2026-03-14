# deploy.ps1 — Deploy Mia to VPS
# Usage: .\deploy.ps1
# Always run this instead of scp-ing individual files.
#
# CRITICAL: The bot runs from /root/mia-whatsapp-bot/bot.js (pm2)
#           NOT from /var/www/html/mia.ainitravel.com/bot_new.js

$VPS = "root@108.175.12.152"
$WEB = "/var/www/html/mia.ainitravel.com"
$BOT = "/root/mia-whatsapp-bot"

Write-Host "Deploying PHP app..." -ForegroundColor Cyan
scp -r services config views public api *.php "${VPS}:${WEB}/"

Write-Host "Deploying bot (to correct pm2 path)..." -ForegroundColor Cyan
scp bot_new.js "${VPS}:${BOT}/bot.js"
scp bot_client_worker.js "${VPS}:${BOT}/bot_client_worker.js"

Write-Host "Restarting bot..." -ForegroundColor Cyan
ssh $VPS "pm2 restart mia-bot"

Write-Host "Done." -ForegroundColor Green
