#!/usr/bin/env python3

## Used to run allowed commands from the EmonCMS web interface via a hardcoded whitelist.
# EmonCMS submits action codes (JSON) to redis where this service picks them up
# and maps them to pre-approved scripts. No arbitrary script execution is permitted.
# Used in conjunction with:
# - Admin module to run service-runner-update.sh
# - Serial monitor
# - Others

# Patched in emoncms-docker: default redis.Redis() uses localhost; Docker Compose uses hostname `redis`.

import json
import os
import subprocess
import time
import redis

KEYS = ["service-runner", "emoncms:service-runner"]

# Base directories — override via environment variables when needed
_EMON_DIR    = os.environ.get("OPENENERGYMONITOR_DIR", "/opt/openenergymonitor")
_EMONCMS_DIR = os.environ.get("EMONCMS_DIR", "/var/www/emoncms")
_LOG_DIR     = os.environ.get("EMONCMS_LOG_DIR", "/var/log/emoncms")

# Hardcoded whitelist: action code -> absolute script path.
# Only actions listed here can be executed; any unknown action is rejected.
SCRIPT_WHITELIST = {
    "emoncms-update":        f"{_EMON_DIR}/EmonScripts/update/service-runner-update.sh",
    "emoncms-update-legacy": f"{_EMON_DIR}/emonpi/service-runner-update.sh",
    "firmware-upload":       f"{_EMON_DIR}/EmonScripts/update/atmega_firmware_upload.sh",
    "component-update":      f"{_EMON_DIR}/EmonScripts/update/update_component.sh",
    "components-update":     f"{_EMON_DIR}/EmonScripts/update/update_all_components.sh",
    "service-action":        f"{_EMONCMS_DIR}/scripts/service-action.sh",
    "serialmonitor-start":   f"{_EMONCMS_DIR}/scripts/serialmonitor/start.sh",
}

# Hardcoded whitelist: log name -> absolute log file path.
LOG_WHITELIST = {
    "update": f"{_LOG_DIR}/update.log",
}

_EXPECTED_ARG_COUNT = {
    "emoncms-update":        3,    # e.g. ["all", "emonpi-2022", "/dev/ttyUSB0"]
    "emoncms-update-legacy": 3,    # e.g. ["emoncms", "none", "/dev/ttyAMA0"]
    "firmware-upload":       None, # e.g. ["/dev/ttyUSB0", "emonpi-2022"]
                                   #   or ["/dev/ttyUSB0", "custom", "firmware_abc.hex", "115200", "avr", "autoreset"]
    "component-update":      2,    # e.g. ["/opt/openenergymonitor/EmonScripts", "master"]
    "components-update":     1,    # e.g. ["stable"]
    "service-action":        2,    # e.g. ["emonhub.service", "restart"]
    "serialmonitor-start":   2,    # e.g. ["115200", "/dev/ttyUSB0"]
}

def _validate_args(action: str, args: list) -> bool:
    """Check structural constraints only — content validation is left to the scripts."""
    # Reject any arg containing a null byte
    if any("\x00" in a for a in args):
        return False
    if action == "firmware-upload":
        return len(args) in (2, 6)
    expected = _EXPECTED_ARG_COUNT.get(action)
    if expected is None:
        return False
    return len(args) == expected


def _redis_client():
    return redis.Redis(
        host=os.environ.get("REDIS_HOST", "127.0.0.1"),
        port=int(os.environ.get("REDIS_PORT", "6379")),
    )


def connect_redis():
    while True:
        try:
            server = _redis_client()
            if server.ping():
                print("Connected to redis server", flush=True)
                return server
        except redis.exceptions.ConnectionError:
            print("Unable to connect to redis server, sleeping for 30s", flush=True)
        time.sleep(30)


def main():
    print("Starting service-runner", flush=True)
    server = connect_redis()
    while True:
        try:
            # Get the next item from the queue, blocking until one exists
            packed = server.blpop(KEYS)
            if not packed:
                continue
            raw = packed[1].decode()
        except redis.exceptions.ConnectionError:
            print("Connection to redis server lost, attempting to reconnect", flush=True)
            server = connect_redis()
            continue

        print("Got message:", raw, flush=True)

        try:
            payload  = json.loads(raw)
            action   = payload.get("run")
            args     = payload.get("args", [])
            log_name = payload.get("log")
        except (json.JSONDecodeError, AttributeError) as exc:
            print(f"REJECTED: invalid JSON payload - {exc}", flush=True)
            continue

        if not isinstance(action, str) or action not in SCRIPT_WHITELIST:
            print(f"REJECTED: unknown action '{action}'", flush=True)
            continue

        if not isinstance(args, list) or not all(isinstance(a, str) for a in args):
            print("REJECTED: args must be a list of strings", flush=True)
            continue

        if not _validate_args(action, args):
            print(f"REJECTED: invalid args for action '{action}': {args}", flush=True)
            continue

        script = SCRIPT_WHITELIST[action]
        cmd = [script] + args

        print(f"STARTING: {action} -> {script} {args}", flush=True)

        if log_name is not None:
            if log_name not in LOG_WHITELIST:
                print(f"REJECTED: unknown log name '{log_name}'", flush=True)
                continue
            logfile = LOG_WHITELIST[log_name]
            try:
                with open(logfile, "w") as f:
                    subprocess.call(cmd, stdout=f, stderr=f)
            except Exception as exc:
                print(f"Error running action '{action}': {exc}", flush=True)
                try:
                    with open(logfile, "a") as f:
                        f.write(f"Error running action '{action}': {exc}\n")
                except Exception:
                    pass
                continue
        else:
            try:
                subprocess.call(cmd, stdout=subprocess.DEVNULL, stderr=subprocess.STDOUT)
            except Exception as exc:
                print(f"Error running action '{action}': {exc}", flush=True)
                continue

        print(f"COMPLETE: {action}", flush=True)


if __name__ == "__main__":
    main()
