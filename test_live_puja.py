import urllib.request
import ssl
import json

ctx = ssl._create_unverified_context()
req = urllib.request.Request(
    'https://onlinepuja.live/api/getPujaList',
    data=b'{}',
    headers={'Content-Type': 'application/json', 'User-Agent': 'Mozilla/5.0'}
)
with urllib.request.urlopen(req, context=ctx) as resp:
    data = json.loads(resp.read().decode('utf-8'))
    items = data.get('recordList', [])
    p31 = next((x for x in items if x.get('id') == 31), None)
    if p31:
        print("TITLE:", p31.get('puja_title'))
        print("SUBTITLE:", p31.get('puja_subtitle'))
        print("PLACE:", p31.get('puja_place'))
        print("DESC_PREFIX:", (p31.get('long_description') or '')[:120])
    else:
        print("Puja 31 not found in getPujaList")
