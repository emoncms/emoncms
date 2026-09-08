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
    "sync-run":              "/opt/emoncms/modules/sync/emoncms-sync.sh",
    "postprocess-run":       "/opt/emoncms/modules/postprocess/postprocess.sh",
    "backup-export":         "/opt/emoncms/modules/backup/emoncms-export.sh",
    "backup-import":         "/opt/emoncms/modules/backup/emoncms-import.sh",
    "backup-usb-import":     "/opt/emoncms/modules/backup/usb-import.sh",
    # Backup to an attached drive. One action per argument shape rather than a
    # single action for the whole script, so that the expected argument count
    # below stays a meaningful check on each of them.
    "backup-drive-sync":     "/opt/emoncms/modules/backup/drive-backup.sh",
    "backup-drive-verify":   "/opt/emoncms/modules/backup/drive-backup.sh",
    "backup-drive-setpath":  "/opt/emoncms/modules/backup/drive-backup.sh",
    "backup-drive-mount":    "/opt/emoncms/modules/backup/drive-backup.sh",
    "backup-drive-schedule": "/opt/emoncms/modules/backup/drive-backup.sh",
    "backup-drive-restore":  "/opt/emoncms/modules/backup/drive-restore.sh",
}

# Hardcoded whitelist: log name -> absolute log file path.
LOG_WHITELIST = {
    "update": f"{_LOG_DIR}/update.log",
    "sync":        f"{_LOG_DIR}/sync.log",
    "postprocess": f"{_LOG_DIR}/postprocess.log",
    "exportbackup": f"{_LOG_DIR}/exportbackup.log",
    "importbackup": f"{_LOG_DIR}/importbackup.log",
    "usbimport":    f"{_LOG_DIR}/usbimport.log",
    "drivebackup":       f"{_LOG_DIR}/drivebackup.log",
    "drivebackupverify": f"{_LOG_DIR}/drivebackup-verify.log",
    "driverestore":      f"{_LOG_DIR}/driverestore.log",
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
    "sync-run":              0,    # no args
    "postprocess-run":       0,    # no args
    "backup-export":         0,    # no args
    "backup-import":         0,    # no args
    "backup-usb-import":     0,    # no args
    "backup-drive-sync":     0,    # no args
    "backup-drive-verify":   1,    # ["--verify"]
    "backup-drive-setpath":  2,    # ["--set-path", "/media/backup"]
    "backup-drive-mount":    None, # ["--mount", "/dev/disk/by-id/usb-..."]
                                   #   or ["--format-mount", "/dev/disk/by-id/usb-...", "--confirm-erase"]
    "backup-drive-schedule": 1,    # ["--enable-schedule"] or ["--disable-schedule"]
    "backup-drive-restore":  None, # ["--yes"], and any of --delete,
                                   #   ["--sql", "<snapshot>"]
}

def _validate_args(action: str, args: list) -> bool:
    """Check structural constraints only — content validation is left to the scripts."""
    # Reject any arg containing a null byte
    if any("\x00" in a for a in args):
        return False
    if action == "firmware-upload":
        return len(args) in (2, 6)
    # Both take a variable number of arguments, so the shape is checked here
    # instead. The backup scripts validate the values themselves: a mountpoint
    # or drive has to be one their own discovery just reported, and a snapshot
    # name one that is present on the backup drive.
    if action == "backup-drive-schedule":
        return args in (["--enable-schedule"], ["--disable-schedule"])
    if action == "backup-drive-mount":
        if args[:1] == ["--mount"]:
            return len(args) == 2
        if args[:1] == ["--format-mount"]:
            return len(args) == 3 and args[2] == "--confirm-erase"
        return False
    if action == "backup-drive-restore":
        if not args or args[0] != "--yes":
            return False
        rest = args[1:]
        if rest[:1] == ["--delete"]:
            rest = rest[1:]
        return rest == [] or (len(rest) == 2 and rest[0] == "--sql")
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
