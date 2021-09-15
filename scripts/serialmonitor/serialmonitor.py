#!/usr/bin/python3
# -------------------------------------------------------------
# Serial monitor - can be used standalone or with 
#                  emoncms admin firmware interface
# -------------------------------------------------------------

# 1. Import

import serial
import time
import sys
import select
import glob

# Check if this is a RaspberryPi and GPIO module is available
rpi = True
try: 
    import RPi.GPIO as GPIO
except ModuleNotFoundError as err:
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
def reset():
  GPIO.setmode(GPIO.BOARD)
  GPIO.setup(7, GPIO.OUT)
  GPIO.output(7, GPIO.HIGH)
  time.sleep(0.32)
  GPIO.output(7, GPIO.LOW)
  GPIO.cleanup()

if device=="/dev/ttyAMA0" and rpi: reset()
# -------------------------------------------------------------
if redis:
    try:
        r = redis.Redis('localhost', 6379)
    except:
        pass
# -------------------------------------------------------------
try:
    ser = serial.Serial(device, baudrate)
    ser.reset_input_buffer()
except:
    print("Could not open serial port "+str(device)+" "+str(baudrate))
    sys.exit(0)
# -------------------------------------------------------------
linestr = ""
while True:
    # Check for standard input or serial input (timeout of .2 sec):
    try:
        inp, outp, err = select.select([sys.stdin, ser], [], [], .2)
    except KeyboardInterrupt:
        sys.exit(0)
        
    # If standard input, write to serial
    if sys.stdin in inp:
        line = sys.stdin.readline().rstrip()
        if line!="":
            ser.write((line+"\r\n").encode())
  
    # If serial input, read from serial    
    if ser in inp:
        while ser.in_waiting:
            # linestr = ""
            try:
                linestr = linestr + ser.read().decode()
            except UnicodeDecodeError:
                pass
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
                ser.write((cmd+"\r\n").encode())
# -------------------------------------------------------------

