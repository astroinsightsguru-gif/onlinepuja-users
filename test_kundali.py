import urllib.request
import json
import ssl

ctx = ssl._create_unverified_context()
payload = json.dumps({
    'year': 2026,
    'month': 10,
    'day': 12,
    'hour': 8,
    'min': 0,
    'lat': 25.3176,
    'lon': 82.9739,
    'tzone': 5.5
}).encode('utf-8')

for endpoint in ['basic', 'chart', 'dasha', 'dosha']:
    req = urllib.request.Request(
        f'https://onlinepuja.live/api/kundali/{endpoint}',
        data=payload,
        headers={'Content-Type': 'application/json', 'Accept': 'application/json'}
    )
    try:
        res = urllib.request.urlopen(req, context=ctx)
        print(endpoint, 'STATUS:', res.status)
    except urllib.error.HTTPError as e:
        print(endpoint, 'FAILED:', e.code, e.read().decode('utf-8')[:150])
