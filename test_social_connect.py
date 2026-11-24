import urllib.request
import json
import ssl

ctx = ssl._create_unverified_context()
url = 'https://onlinepuja.live/api/partner/social/connect'
for platform in ['instagram', 'facebook', 'youtube']:
    data = json.dumps({'astrologer_id': 11, 'platform': platform}).encode('utf-8')
    req = urllib.request.Request(url, data=data, headers={
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'User-Agent': 'Dart/3.13 (dart:io)'
    })
    try:
        resp = urllib.request.urlopen(req, context=ctx)
        print(platform, 'SUCCESS:', resp.read().decode('utf-8'))
    except urllib.error.HTTPError as e:
        print(platform, 'HTTP ERROR', e.code, e.read().decode('utf-8'))
