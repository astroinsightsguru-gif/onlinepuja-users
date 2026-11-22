#!/usr/bin/env bash
sed -i 's|^\* \* \* \* \* nice|*/15 * * * * nice|' /var/spool/cron/crontabs/tripcosmosco
sed -i 's/sleep 120/sleep 600/' /opt/vmstudio/bin/xbrain-worker.sh
systemctl restart xbrain-worker
echo "=== CRONTAB ==="
cat /var/spool/cron/crontabs/tripcosmosco
echo "=== XBRAIN SCRIPT ==="
grep sleep /opt/vmstudio/bin/xbrain-worker.sh
