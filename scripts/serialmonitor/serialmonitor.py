#!/usr/bin/python3
# -------------------------------------------------------------
# Serial monitor - can be used standalone or with 
#                  emoncms admin firmware interface
# -------------------------------------------------------------

# 1. Import

import os
import serial
import time
import sys
import select
import glob

# rpi-lgpio (RPi.GPIO on trixie) creates a notification pipe in the working
# directory on import. Run from service-runner that is /, which pi cannot
# write to, and the import fails: use /tmp unless LG_WD is already set.
os.environ.setdefault("LG_WD", "/tmp")

# Check if this is a RaspberryPi and GPIO module is available. Any failure
# only means no reset, it must not stop the serial monitor.
rpi = True
try:
    import RPi.GPIO as GPIO
except Exception as err:
    print("GPIO not available, no reset: "+str(err))
    rpi = False

# Check if redis available for emoncms -> script link
redis = True
try:
    import redis
except ModuleNotFoundError as err:
    redis = False

# -------------------------------------------------------------
# 2. CLI arguments
# -------------------------------------------------------------
device = "/dev/ttyUSB0"
baudrate = 115200

def is_valid_baudrate(arg):
    if not arg.isnumeric(): return False
    baudrate = int(arg)
    if baudrate==9600: return True
    elif baudrate==38400: return True
    elif baudrate==115200: return True
    return False

def is_valid_device(arg):
    if "/dev/tty" in arg: return True
    return False

for i in range(1,3):
    if i<len(sys.argv):
        if is_valid_baudrate(sys.argv[i]):
            baudrate = int(sys.argv[i])
            # print ("baudrate = "+str(baudrate))
        elif is_valid_device(sys.argv[i]):
            device = sys.argv[i]
            # print ("device = "+str(device))

# -------------------------------------------------------------
# 3. Reset 
# -------------------------------------------------------------
# Pin 7 (GPIO4) resets the emonPi1's ATmega. On the emonPi2 and emonPi3 it is
# the LCD push button, held by emonPiLCD, and no reset is needed: skip the
# reset when the pin is in use rather than drive the button line.
# The serial monitor carries on without a reset if GPIO fails for any reason.
def reset():
  try:
    GPIO.setmode(GPIO.BOARD)
    GPIO.setup(7, GPIO.OUT, initial=GPIO.LOW)
  except Exception as err:
    print("Reset skipped, GPIO4 (pin 7) is not available: "+str(err))
    try:
      GPIO.cleanup()
    except Exception:
      pass
    return
  GPIO.output(7, GPIO.HIGH)
  time.sleep(0.32)
  GPIO.output(7, GPIO.LOW)
  GPIO.cleanup()

if device=="/dev/ttyAMA0" and rpi: reset()
# -------------------------------------------------------------
if redis:
    try:
        r = redis.Redis('localhost', 6379)
        # Drop commands queued while the monitor was not running
        r.delete('serialmonitor')
    except:
        pass
# -------------------------------------------------------------
try:
    ser = serial.Serial(device, baudrate)
    ser.reset_input_buffer()
except Exception as err:
    message = "Could not open serial port "+str(device)+" "+str(baudrate)+": "+str(err)
    print(message)
    if redis:
        try:
            r.rpush('serialmonitor-log', message)
        except Exception:
            pass
    sys.exit(0)
# -------------------------------------------------------------


time.sleep(1)

ser.write(("l\n").encode())
r.rpush('serialmonitor-log',"Loading configuration, list (l) command sent:\n")

linestr = ""
# Started from service-runner, stdin is /dev/null: it is always readable and
# at end of file, so select() would return at once on every loop. Stop
# watching stdin once it reaches end of file.
inputs = [sys.stdin, ser]
while True:
    # Check for standard input or serial input (timeout of .2 sec):
    try:
        inp, outp, err = select.select(inputs, [], [], .2)
    except KeyboardInterrupt:
        sys.exit(0)

    # If standard input, write to serial
    if sys.stdin in inp:
        line = sys.stdin.readline()
        if line=="":
            inputs.remove(sys.stdin)
        else:
            line = line.rstrip()
            if line!="":
                ser.write((line+"\r\n").encode())
  
    # If serial input, read from serial    
    if ser in inp:
        while True:
            try:
                if not ser.in_waiting:
                    break
                linestr = linestr + ser.read().decode()
            except UnicodeDecodeError:
                pass
            except serial.SerialException as err:
                if redis:
                    r.rpush('serialmonitor-log', "Serial port error, check that emonhub is stopped: " + str(err))
                sys.exit(1)
            except KeyboardInterrupt:
                sys.exit(0)
            
            if "\r\n" in linestr:
                linestr = linestr.rstrip()
                print (linestr)
                if redis:
                    r.rpush('serialmonitor-log',linestr)
                linestr = ""

    # Redis
    if redis:
        cmd_count = r.llen('serialmonitor')
        if cmd_count:
            cmd = r.lpop('serialmonitor').decode()
            if cmd=="exit":
                sys.exit(0)   
            elif cmd!="":
                ser.write((cmd+"\n").encode())
# -------------------------------------------------------------

