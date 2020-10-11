#!/usr/bin/env python3

import time
from datetime import datetime

import numpy as np
import struct

from contextlib import closing
from urllib.request import urlopen
import urllib.error
import json

import redis
r = redis.Redis(host="localhost", port=6379, db=0)

conf="forecast.conf"

def tsToHuman(ts, fmt="%Y-%m-%d %H:%M:%S"):
    """
    format a timestamp to something readable by a human
    uses the local timezone
    """
    return datetime.fromtimestamp(ts).strftime(fmt)

def getOWMHourly(url):
    """
    va chercher les prévisions horaires dans l'API openweathermap
    ces prévisions sont réalisées sur 48 heures
    celà donne donc 48 valeurs
    """
    with closing(urlopen(url)) as prev:
        all = json.loads(prev.read())
        nb = len(all["hourly"])
        data=np.zeros((nb,4))
        i=0
        for r in all["hourly"]:
            data[i,0]=r["dt"]
            data[i,1]=r["clouds"]
            data[i,2]=r["temp"]
            data[i,3]=r["feels_like"]
            i+=1
    return data

def getOWMDaily(url):
    """
    va chercher les prévisions journalières dans l'API openweathermap
    une prévision journalière OWM est composée de 4 données : matin, jour, soirée et nuit
    on peut ainsi reconstituer des prévisions toutes les 6 heures
    sur 8 jours, celà donne donc 4 * 8 = 32 valeurs
    """

    with closing(urlopen(url)) as prev:
        all = json.loads(prev.read())
        nb = len(all["daily"])
        data=np.zeros((nb*4,4))
        i=0
        for r in all["daily"]:
            data[i,0]=r["dt"]-6*3600
            data[i,1]=r["clouds"]
            data[i,2]=r["temp"]["morn"]
            data[i,3]=r["feels_like"]["morn"]
            i+=1
            data[i,0]=r["dt"]
            data[i,1]=r["clouds"]
            data[i,2]=r["temp"]["day"]
            data[i,3]=r["feels_like"]["day"]
            i+=1
            data[i,0]=r["dt"]+6*3600
            data[i,1]=r["clouds"]
            data[i,2]=r["temp"]["eve"]
            data[i,3]=r["feels_like"]["eve"]
            i+=1
            data[i,0]=r["dt"]+2*6*3600
            data[i,1]=r["clouds"]
            data[i,2]=r["temp"]["night"]
            data[i,3]=r["feels_like"]["night"]
            i+=1
        return data

def formatURL(s):
    """
    s is the setup of the sniffer
    """
    lat=s["lat"]
    lon=s["lon"]
    exclude=["current", "minutely", "hourly", "daily"]
    exclude.remove(s["level"])
    url="{}lat={}&lon={}".format(s["url"],lat,lon)
    url="{}&exclude={}&appid={}".format(url,",".join(exclude),s["key"])
    if "units" in s:
        url="{}&units={}".format(url,s["units"])
    return url

def toRedis(data,feedname):
    """
    data injection in Redis for operation within EmonCMS
    data : numpy array - column 0 : timestamp in seconds - columns 1 to 3 : data values
    feedname : the feed name, will be prefixed by the dataset initial timestamp expressed as a human date
    r : python mplementation of the Redis protocol to interrogate the server
    """
    h,w = data.shape

    if h==0 or w==0 : return

    shape = struct.pack('>II',h,w)
    # cf https://numpy.org/doc/stable/reference/generated/numpy.ndarray.tobytes.html
    # and https://stackoverflow.com/questions/55311399/fastest-way-to-store-a-numpy-array-in-redis
    encoded = shape+data.tobytes()

    # starting timestamp will be the feed number
    ts=int(data[0,0])

    # data injection in node buffer
    buffer = "feed:{}:buffer".format(ts)
    r.set(buffer,encoded)

    # all this will work for emoncms user 1 only
    userid=1
    tag = "weather_forecasts"
    name = "{}_{}".format(tsToHuman(ts,fmt="%Y_%m_%d_%H_%M_%S"),feedname)
    feed = {"id":ts, "tag":tag, "engine":9, "name":name, "datatype":0, "userid":userid, "public":""}
    node = "feed:{}".format(ts)
    if r.exists(buffer):
        # node creation with metadatas
        r.hmset(node, feed)
    else: return "buffer for feed {} is none".format(ts)

    if r.exists(node):
        # feednumber injection in the user feeds list
        key = "user:feeds:{}".format(userid).encode()
        values = r.smembers(key)
        newval = "{}".format(ts).encode()
        if newval in values:
            return "user already owns the feed {}".format(ts)
        else:
            r.sadd(key,newval)

def main():
    with open(conf) as f:
        setup =  json.loads(f.read())
    url=formatURL(setup)
    try:
        if setup["level"] == "daily":
            data = getOWMDaily(url)
            feedname="OWMD"
        if setup["level"] == "hourly":
            data = getOWMHourly(url)
            feedname="OWMH"
    except (urllib.error.URLError, urllib.error.HTTPError) as e:
        print(e.reason)
    else:
        result=toRedis(data,feedname)
        if result: print(result)

if __name__ == "__main__":
    main()
