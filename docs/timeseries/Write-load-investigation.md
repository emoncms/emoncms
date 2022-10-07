## Investigation into effect of minimum IO size on write performance and potential for improvement by buffering writes

A single PHPFina (PHP Fixed Interval No averaging) or PHPTimeSeries datapoint in emoncms uses between 4 and 9 bytes. The write load on the disk however is a bit more complicated than that. Most filesystems and disk's have a minimum IO size that is much larger than 4-9 bytes, on a FAT filesystem the minimum IO size is 512 bytes this means that if you try and write 4 bytes the operation will actually cause 512 bytes of write load. But its not just the datafile that gets written to, every file has inode meta data which can also result in a further 512 bytes of write load. A single 4 byte write can therefore cause 1kb of write load.

By buffering writes for as long as we can in memory and then writing in larger blocks its possible to reduce the write load significantly. The following investigation looks at the performance of the ext4 and fat filesystem for individual datapoint writes and then the current version of emoncms before going on to look at how emoncms could be improved by buffering writes.

### SD Card formatted with vFAT

    Filesystem block size:      sudo blockdev --getbsz /dev/sdb1    512 bytes
    Physical disk block size:   sudo blockdev --getpbsz /dev/sdb1   512 bytes
    Sector size:                sudo blockdev --getss /dev/sdb1     512 bytes
    Minimum IO size:            sudo blockdev --getiomin /dev/sdb1  512 bytes

Sectors written to date:

    awk '/sdb1/ {print $10}' /proc/diskstats    1 (512 bytes)

**Write Test**

Create a time series data file and write 4 bytes to it:

    <?php

    $fh = fopen("/media/user/disk/feed.1.dat","a");
    fwrite($fh,pack("f",250.3));
    fclose($fh);
    
Sectors written to date:

    awk '/sdb1/ {print $10}' /proc/diskstats    5 (+4 2048 bytes)

Append another 4 bytes to the time series data file

    awk '/sdb1/ {print $10}' /proc/diskstats    8 (+3 1536 bytes)

Append another 4 bytes to the time series data file

    awk '/sdb1/ {print $10}' /proc/diskstats    10 (+2 1024 bytes)

Append another 4 bytes to the time series data file

    awk '/sdb1/ {print $10}' /proc/diskstats    12 (+2 1024 bytes)

... keeps going at +1024 bytes per 4 byte data append with the occasional 1 or 3 sector 1536 byte update.

### SD Card formatted with Ext4

Ext4 is the filesystem that often comes as standard with newer linux distributions, including the latest raspbian distro. Ext4 features journalling and delayed allocation (blocks are not allocated immediately when the program writes instead allocation is delayed and the file kept in cache until the write point)

    Filesystem block size:      sudo blockdev --getbsz /dev/sdb1    4096 bytes
    Physical disk block size:   sudo blockdev --getpbsz /dev/sdb1   512 bytes
    Sector size:                sudo blockdev --getss /dev/sdb1     512 bytes
    Minimum IO size:            sudo blockdev --getiomin /dev/sdb1  512 bytes

Its harder to test the write load caused by individual writes with ext4 due to both the journalling and delayed allocation features of ext4 the /proc/diskstats output was inconsistent. I found I could get a more stable reading of write load by posting at a regular interval, once every 5s and then measuring the 5 minute write load using iostat in kB_wrtn/s. 4 bytes every 5 seconds for 5 minutes is a data file increase of 240 bytes. On the vFAT filesystem we would expect this to result in 61440 bytes being written 204.8 bytes per second (In a test on vFAT I measured: 60416 bytes in 5min).

    <?php

    while(true)
    {
        $fh = fopen("/media/user/data/feed.1.dat","a");
        fwrite($fh,pack("f",250.3));
        fclose($fh);
        sleep(5);
    }
    
The first 5 minutes showed 548 kb being written and 1.83 kb_wrtn/s (1874 bytes per second, 9x the vFat value). The second 5 minutes showed 524 kb_wrtn and 1.75 kb_wrtn/s.
The third 5 mins showed 540kb_wrtn and 1.80 kB_wrtn/s.

8x vFAT could be explained by the ext4 filessytem block size being 4096 vs 512. A single 4 byte update to a file would result in both 4096 bytes due to the data file update but also 4096 for the file descriptor, However the exact write load is more complicated than this as its dependent on the post rate timing relative to the filesystem delayed allocation timing and journalling.

### Typical emoncms load writing

This test investigates typical write rates experienced by an emoncms installation as you add feeds of different types. The test uses the emonpi test image that can be found here:

1) Install 2014-05-22-emonpi-mqttdev.img on SD Card.

2) Test write rate out of the box.

    0.43,0.00,0.28,0.00,0.00,0.28,0.00,0.39,0.00

3) Start listener.py, test write rate

    0.70,0.15,0.13,0.59,0.13,0.43,0.98,0.15,0.13

4) Configure 8 feeds PHPFiwa power feeds 10s each

    11.83,12.47,12.48,12.22,12.38,12.52,11.84,11.90,12.03,12.96,11.81,12.28

Power, temperature: PHPFiwa
Accumulating: PHPFina

5) Configure 12 feeds PHPFiwa power feeds 10s each

    15.38,15.88,15.79,16.05
    15.30,15.36,15.76,15.15,15.98,15.95,16.02

6) +13 60s Temperature feeds and +12 60s battery voltage feeds all PHPFIWA (25x 60s PHPFiwa, 12x 10s PHPFiwa)

    24.30,25.72,24.90,24.68,24.38,24.79,24.22,24.82

7) +2x PHPFina (25x 60s PHPFiwa, 12x 10s PHPFiwa, 2x PHPFina 10s)

    25.57,25.81,25.60,26.83,26.70,28.20,26.05

8) Emoncms upgrade

    25.07,25.93,25.55,25.57,26.23,26.68,25.86, 26.04

9) 5 more PHPFINA, + 2 more PHPFiwa (25x 60s PHPFiwa, 14x 10s PHPFiwa, 7x PHPFina 10s)

    30.41,30.22,30.12,30.94,30.79,30.74,32.03

10) 9 daily kwh/d feeds + 2x power to kwh feeds

    34.35,34.02,33.48,33.62,34.66,33.94,34.36

11) Added 4x histograms

    34.01,34.25,31.90,31.61,30.26,29.43,31.49,30.47,29.79

12) Interestingly my other pi which was running at an average of 197 kb/s seemed to have significant mysql load which after removing histograms and daily kwh feeds reduced significantly to: 

    40.18,165.19,40.20,164.07,40.15,163.09,39.58,163.08,40.04,168.98,38.74

This suggests that histogram and kwhd feeds in mysql may cause a large amount of load the more they grow, possibly due to the load caused by needing to maintain indexes (given that adding histograms and kwhd feeds present little change on the new system) and would suggest it best not to use mysql feeds on SD cards.

### Developing a minimal version of emoncms that is write efficient

- A 10s PHPFiwa feed writes to 4 datafiles and 1 metadata file per datapoint + 5 file inodes
- A 10s PHPFina feed writes to 1 datafile and 1 metadata file per datapoint + 2 file inodes
- A 10s PHPTimeSeries feed writes to 1 datafile file per datapoint + 1 file inode

PHPFina and PHPFiwa can be rewritten to not require the npoints datafile as npoints can be calculated from the filesize stored in the files inode file descriptor.

A minimal version of emoncms that is write efficient could use either PHPFina without the npoints meta data file or PHPTimeSeries both requiring two writes per datapoint (data & inode). This could immediately provide a 5x write load reduction compared to PHPFiwa.

A cut down version of emoncms has been developed to test this and the idea of write buffering so that emoncms can write in larger blocks. The following tests on both a FAT filesystem and Ext4 show the results: 

[Source code: Cut down version of emoncms](https://github.com/emoncms/development/tree/master/experimental/emon-py)

### Adding write buffering (vFAT)

    TEST 1 1s COMMIT TIME, 25X 60s FEEDS, 20X 10s FEEDS
    Write load 5 min average: 1.52,1.10,1.19,1.18,1.40,1.70 kb_wrtn/s
    
    TEST 2 60s COMMIT TIME, 25X 60s FEEDS, 20X 10s FEEDS
    Bytes written every 60 seconds: 788, 456, 604 bytes
    Write load 5 min average: 0.46,0.37,0.46 kb_wrtn/s
    
    TEST 3 5 minutes COMMIT TIME, 25X 60s FEEDS, 20X 10s FEEDS
    Bytes written every 5 minutes: 2924, 2912, 2692 bytes
    Write load 5 min average: 0.12,0.12,0.12 kb_wrtn/s
    
    TEST 4 10 minutes COMMIT TIME, 25X 60s FEEDS, 20X 10s FEEDS
    Bytes written every 10 minutes: 5944, 5636, 5860 bytes
    Write load 10 min average: 0.05,0.08,0.06 kb_wrtn/s
    
    TEST 5 30 minutes COMMIT TIME, 25X 60s FEEDS, 20X 10s FEEDS
    Bytes written every 30 minutes: 17184, 17184 bytes
    Write load 30 min average: 0.03,0.03 kb_wrtn/s

### Adding write buffering (Ext4)

    TEST 1 1s COMMIT TIME, 25x 60s FEEDS, 20x 10s FEEDS
    Write load 5 min average: 5.95,6.07,5.67 kb_wrtn/s
    
    TEST 2 60s COMMIT TIME, 25x 60s FEEDS, 20x 10s FEEDS
    Bytes written every 60 seconds: 692, 568, 616 bytes
    Write load 5 min average: 3.28,3.17,3.20 kb_wrtn/s
    
    TEST 3 5 minutes COMMIT TIME, 25x 60s FEEDS, 20x 10s FEEDS
    Bytes written every 5 minutes: 2976, 2916, 2852 bytes
    Write load 5 min average: 0.77,0.79,0.75 kb_wrtn/s
    
    TEST 4 10 minutes COMMIT TIME, 25x 60s FEEDS, 20x 10s FEEDS
    Bytes written every 10 minutes: 5956, 5700, 5844 bytes
    Write load 10 min average: 0.39,0.09,0.69,0.39 kb_wrtn/s
    
    TEST 5 30 minutes COMMIT TIME, 25x 60s FEEDS, 20x 10s FEEDS
    Bytes written every 30 minutes: 17396,17460,17084,17276,17564,17240 bytes
    Write load 30 min average: 0.14,0.13,0.12,0.14,0.16 kb_wrtn/s
    
Update 7th July 14: Testing on Ext2 which is a non-journaling file system show the same results as obtained on Ext4 when the block size is 4096 bytes.

Testing dropping the block size on the Ext4 partition to 1024 bytes instead of 4096 bytes results in a write load of just under half: 1.4 kb_wrtn/s.

Testing dropping the block size on the Ext2 partition to 1024 bytes instead of 4096 bytes results in a write load of: 1.09,1.12 kb_wrtn/s.


### Conclusion

It appears that just using PHPFina (Without the npoints metafile) reduces the write load from ~31 kb\_wrtn/s to ~ 6kb\_wrtn/s on an Ext4 filesystem. This is very close to the 5x reduction that we might expect from not writing to all the additional average layers and associated inodes that PHPFiwa requires.

Using the FAT filesystem provides another ~4x reduction or 20x reduction overall. An additional overhead is expected due to the way Journalling works. Journalling records all disk writes to a journal first before actually writing to the data location on the disk. This reduces the likelihood of corruption in the event of a crash or power failure but at an additional write cost.

Slowing the commit rate down to once every 60 seconds provides a write reduction of 10x on Ext4 or 80x on FAT vs our baseline.

Slowing the commit rate to once every half an hour provides a significant 238x reduction on Ext4 or more than 1000x reduction on FAT. 
Adding write buffering to emoncms complicates the implementation but looks like a useful avenue to investigate. As a more immediate step however for SD Card based emoncms installations it would good to use the reduced load version of PHPFina. Possibly with a two partition setup where the os and emoncms runs in a readonly partition while the data is stored on a write partition formatted with the FAT filesystem.

### Further development questions

Is the reduced write load and longer SD card lifespan that might result from using the FAT filesystem worth the increased chance of data corruption from power failure that Ext4 might prevent?

It would be interesting to compare the performance of the FAT filesystem + 5 min application based commit time with the EXT4 filesystem with journalling turned off and filesystem delayed allocation set to 5 min instead of write buffering in the application.

The Ext2 test results suggest that journaling on Ext4 isnt as large a source of write load initially thought. Its possible to improve Ext4 and Ext2 performance for emoncms by reducing the blocksize to 1024 bytes, its still more write intensive than FAT but perhaps the difference is small enough to warrant using Ext4 @ 1024 bytes for the additional protection provided by journaling?
