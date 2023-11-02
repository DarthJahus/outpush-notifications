import requests
import time
from datetime import datetime, timedelta, timezone
import json

try:
    with open("config.json", 'r', encoding="utf-8") as _f:
        __data = json.loads(_f.read())
        _f.close()

    if __data["test"]: __article_data = __data["article_data"]
except Exception as e:
    print(e)
    exit()

# login process
__login_endpoint = "https://publisher-api.pushmaster-in.xyz/v1/auth/login"
__login_data = {"email": __data["email"], "password": __data["password"]}
_req = requests.post(__login_endpoint, json=__login_data)
print(_req.json())
if _req.status_code != 200:
    print("Error: %s" % _req.text)
else:
    __token = _req.json()["tokens"]["access"]["token"]
    __token_expiry = datetime.strptime(_req.json()["tokens"]["access"]["expires"], '%Y-%m-%dT%H:%M:%S.%fZ')

__headers = {"Authorization": "Bearer %s" % __token}

# campaign creation
__campaign_endpoint = "https://publisher-api.pushmaster-in.xyz/v1/campaigns/"
__campaign_data = {
    "websiteUrl": __data["website"],
    "scheduleDate": (datetime.now(timezone.utc) + timedelta(minutes = 120)).isoformat(),
    "campaignName": __data["name"],
    "notification": {
        "title": __article_data["title"][:45],
        "body": __article_data["meta"][:45],
        "url": __article_data["url"],
        "imageUrl": __article_data["thumb"],
        "iconUrl": __data["favicon"]
    }
}
print(__campaign_data)
_req = requests.post(__campaign_endpoint, headers=__headers, json=__campaign_data)
if _req.status_code != 200:
    print("Error with campaign: %s" % _req.text)
else:
    print(_req.text)
