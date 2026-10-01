# Time series engine history

Summary of how the Emoncms feed engines developed, followed by the write load investigation that shaped them. For the engines in use now, see [Fixed interval](Fixed-interval.md) and [Variable interval](Variable-interval.md).

## MySQL, 2011 to 2013

The first engine stored each feed in its own MySQL table, with one field for the timestamp and one for the value. Graph data was selected with a query that picked out every nth row.

After around 5 months, a request for 1000 datapoints out of 2.5 million took nearly 12 seconds. An index on the time column, with repeated queries from PHP at fixed time steps, gave a 10x improvement. As emoncms.org grew, the MySQL query queue added delays that made historical data requests slow again.

In hindsight, these problems came largely from schema and engine choices, such as the missing time index and MyISAM table locks, rather than from limits of MySQL. Flat files were kept for their lower disk use, write deferral and simplicity on SD card systems.

## Timestore, 2013

In May 2013 Mike Stirling suggested Timestore, a time series database he had written in C. Timestore stored fixed interval data with pre-computed average layers. It more than halved disk use, and request times on the busy emoncms.org server fell from over a minute to 196 ms. Timestore became the main engine in July 2013.

## PHPTimeSeries, 2013

Converting the existing MySQL data to Timestore was slow on an already loaded server. MySQL MyISAM stores a fixed length row table as a plain binary file, so a PHP engine could read and write these files directly after moving them to a new folder. This engine became [PHPTimeSeries](Variable-interval.md). It gave a large improvement over MySQL without converting the data.

## Redis and SSDs, 2013

Input and feed meta data (last time and value) moved from MySQL to Redis, an in-memory database. This removed a large share of the remaining MySQL disk load. Moving emoncms.org to a server with SSDs gave a further increase in capacity. Ynyr Edwards suggested both Redis and the SSD server, and helped add Redis to Emoncms.

## PHPFiwa and PHPFina, 2014

A few months later Timestore began to freeze for seconds at a time. A port of Timestore to PHP did not have the problem and handled over 10 times the emoncms.org post rate. The cause of the freezes was not investigated further, as a direct PHP implementation proved simple to write. The port became two engines, released in Emoncms v8 in February 2014:

- PHPFiwa, fixed interval with averaging. This kept the Timestore average layers.
- [PHPFina](Fixed-interval.md), fixed interval with no averaging.

Timestore and the PHP port were removed in Emoncms 8.5 in March 2015.

Fixed interval storage is the same approach as RRDtool (1999) and the Graphite Whisper format (2008), and the Timestore average layers match RRD consolidation. RRDtool and Whisper are round-robin: files have a fixed size and old data is overwritten. PHPFina keeps all data.

## Low write, 2014 onwards

Emoncms on a Raspberry Pi writes to an SD card, which wears with each write. The 2014 write load investigation, described below, found:

- PHPFiwa wrote to every average layer file on each datapoint, about 5 times the write load of PHPFina.
- Buffering writes in memory and writing them less often reduced the write load by more than any other change.

As a result:

- The PHPFina npoints meta file was removed in July 2014. The number of datapoints is calculated from the data file size.
- RedisBuffer and the `feedwriter` service were added in Emoncms 8.6 in July 2015. Feed data is held in Redis and written to disk every few minutes.
- The emonSD data partition was formatted ext2 with 1 KiB blocks.
- PHPFiwa was disabled for new feeds in October 2015. Averaging was added to PHPFina as a step at read time in April 2016, and PHPFiwa was removed in March 2021.

The write load was measured again on an emonPi in 2026. See the write load re-assessment below.

## Later changes, 2016 onwards

| Date | Change |
|---|---|
| April 2016 | PHPFina averaging at read time, and `daily`, `weekly` and `monthly` intervals aligned to the user timezone |
| May 2017 | PHPFina time of use requests (`get_data_DMY_time_of_day`) |
| January 2019 | `scalerange` for editing PHPFina data ranges, by Alexandre Cuer |
| March 2021 | PHPFiwa and the histogram engine removed |
| October 2021 | PHPFina `get_data_combined` read method added |
| July 2024 | Sync code moved into each engine |
| August 2025 | `get_sha256sum` for checking replicated feeds |

## Why not an external time series database

Dedicated time series databases such as InfluxDB (2013) and TimescaleDB (2017) appeared during and after this work. Emoncms keeps its own engines for three reasons:

1. Long term maintainability. Feed storage is central to Emoncms. With the engines and file format maintained in the project, changes are made to suit Emoncms and do not depend on another project's direction.
2. Post processing. Many post processing tasks read a whole feed in sequence. Reading a flat file of floats in one block is faster than fetching the same data through a database query layer.
3. Footprint and write pattern. Both databases run as separate services with larger memory needs, and both write a log before applying each change. PHPFina appends to files with no forced writes, so the kernel or RedisBuffer can defer them. This suits a Raspberry Pi with an SD card.

The feed module supports multiple engines through a common interface (`engine_methods`), and users can add engines for their own use. Each engine in core adds to the maintenance of the project, so pull requests that add further engines are unlikely to be accepted. Compatibility of custom engines with future changes to core is not guaranteed.

## Current engines

- [PHPFina](Fixed-interval.md) is the recommended engine for new feeds.
- [PHPTimeSeries](Variable-interval.md) remains for feeds converted from MySQL and for existing variable interval feeds.
- The MySQL engine remains in the code.

In 2026 emoncms.org held about 23,000 active feeds on 4 SSDs in RAID 10.

## Write load investigation, 2014

```{note}
The 2014 investigation counted bytes submitted to the disk, not flash page writes. The 2026 re-assessment measured an emonPi in use and corrects some of the 2014 conclusions.
```

A PHPFina or PHPTimeSeries datapoint uses 4 or 9 bytes, but each write to disk costs at least one filesystem block plus an inode update. The 2014 investigation measured this write load on SD cards, with `/proc/diskstats` and 5 minute `iostat` averages (`kB_wrtn/s`). All figures below are in kB_wrtn/s.

### Single writes

A 4 byte append caused about 1024 bytes of writes on vFAT with 512 byte blocks: one block for the data and one for the inode. Posting every 5 seconds on ext4 with 4096 byte blocks gave about 1.8 kB_wrtn/s, about 9 times the vFAT figure. The larger block size explains most of the difference.

### Typical Emoncms installation

Write load on an emonPi image (`2014-05-22-emonpi-mqttdev.img`) as feeds were added:

| Configuration | kB_wrtn/s |
|---|---|
| Base image | 0.15 |
| With `listener.py` | 0.38 |
| 8 PHPFiwa feeds at 10 s | 12.2 |
| 12 PHPFiwa feeds at 10 s | 15.7 |
| Add 25 PHPFiwa feeds at 60 s | 24.7 |
| Add 2 PHPFina feeds at 10 s | 26.4 |
| Add 5 PHPFina and 2 PHPFiwa feeds | 30.8 |
| Add 11 MySQL daily kWh feeds | 34.1 |

A second Pi with MySQL histogram and daily kWh feeds averaged 197 kB_wrtn/s. Removing those feeds brought it down to between 40 and 165 kB_wrtn/s. MySQL feeds caused a large write load as they grew, possibly from index maintenance.

Files written per datapoint:

| Engine | Files written per datapoint |
|---|---|
| PHPFiwa | 4 data files, 1 meta file, 5 inodes |
| PHPFina | 1 data file, 1 meta file, 2 inodes |
| PHPTimeSeries | 1 data file, 1 inode |

Inodes are 128 or 256 bytes and several share one inode table block, so the cost per file is lower than this table suggests.

### Write buffering

A cut down version of Emoncms ([emon-py](https://github.com/emoncms/development/tree/master/experimental/emon-py)) tested PHPFina without the npoints file, and buffering writes in memory. Each test used 25 feeds at 60 s and 20 feeds at 10 s. The commit time is how often the buffer was written to disk.

| Commit time | vFAT | ext4 |
|---|---|---|
| 1 s | 1.35 | 5.90 |
| 60 s | 0.43 | 3.22 |
| 5 minutes | 0.12 | 0.77 |
| 10 minutes | 0.06 | 0.39 |
| 30 minutes | 0.03 | 0.14 |

Ext2 with 4096 byte blocks, which has no journal, gave the same results as ext4. With 1024 byte blocks, ext4 gave 1.4 kB_wrtn/s and ext2 about 1.1 kB_wrtn/s.

### Conclusions, 2014

- PHPFina without the npoints file cut the write load on ext4 from about 31 to about 6 kB_wrtn/s. This is the expected 5 times reduction from dropping the PHPFiwa average layers.
- FAT gave a further 4 times reduction. The difference was attributed to journalling (the ext2 results below contradict this).
- A 60 second commit time cut the write load 10 times on ext4 and 80 times on FAT. A 30 minute commit time cut it 238 times on ext4 and over 1000 times on FAT.
- Proposed next steps were PHPFina without the npoints file, write buffering, and a data partition separate from a read only OS partition.

Open questions at the time:

- Is the lower write load of FAT worth the higher risk of corruption on power failure?
- Could ext4 with no journal and a long delayed allocation time replace buffering in the application?
- The ext2 results suggested the journal was a smaller cost than first thought. Was ext4 with 1024 byte blocks worth using for the protection of the journal?


## Write load re-assessment, 2026

In 2026 the write load was measured again on an emonPi with about 92 live feeds, an ext2 1 KiB data partition and RedisBuffer writing every 300 seconds. The measurements used block layer counters and kernel tracepoints, which show each write request sent to the card.

### What still holds

- Flush interval is the main lever. Write load scales with the number of feed files and the number of flushes, not with the size of the data.
- Removing the PHPFiwa layers and the npoints file was a real reduction, as fewer files are written per flush.
- MySQL feeds are expensive. InnoDB forces each commit to disk, so its writes cannot be deferred.
- Inode writes are shared between files. 92 feeds caused 25 inode writes per flush, against 99 data writes.

### What has changed

- Flash wear depends on pages programmed inside the card, not on bytes sent to it. A 1 KiB write and a 4 KiB write to the same page may cost the same. The 2014 figures count bytes, so they overstate the benefit of smaller blocks and of FAT.
- The 2014 ext2 result showed that the journal was not a major source of write load. The choice of ext2 for the emonSD data partition still rested partly on avoiding the journal.
- FAT rewrites its allocation table on every allocation, which concentrates wear in a small area of the card. It also has no Unix permissions. It is not a good choice for the data partition.
- Kernel writeback settings (`vm.dirty_expire_centisecs`, `vm.dirty_writeback_centisecs`) defer writes in the same way as an application buffer. PHPFina and PHPTimeSeries never call `fsync`, so the kernel is free to hold their writes. At the same interval these settings give the same feed write count as RedisBuffer, and they also cover other writers such as logs.
- Ext4 with default settings (`commit=5`) and no buffer would write far more than the current setup, as the inode is journalled every 5 seconds. Ext4 needs a long `commit=` interval for this workload.

### Measured results

- Total card writes were 375 MB per day. Reducing emonhub and script logging to error level cut this to about 85 MB per day. Logging, not feed data, was the largest writer.
- Feed data payload was about 2.7 MB per day. Feed partition writes were about 50 MB per day, around 18 times the payload.
- At 50 MB per day the measured card would last several hundred years at a write amplification of 1, and around 100 years at 5. Wear is unlikely to be the first failure. Power loss corruption, power supply or Pi failure are more likely.

### SD cards

| Card | Rating | Derived endurance |
|---|---|---|
| SanDisk High Endurance 32 GB (measured unit) | 2,500 hours of Full HD video | about 900 cycles, 29 TB |
| SanDisk Industrial 16 GB, `SDSDQAF3-016G-I` (shipped) | MLC, 3,000 cycles | 48 TB |

Vendor TBW figures are capacity multiplied by cycles. They assume sequential writes and make no allowance for write amplification inside the card. The shipped industrial card is MLC, not SLC, which is about 3 times the cycles of consumer TLC.

### Writes or bytes

Block layer counters show bytes and write requests but not the page programs inside the card. If the card charges one page program per write regardless of size, write count matters more than bytes.

The August 2026 desk audit proposed ext4 with 4 KiB blocks, `commit=600`, `vm.dirty_expire_centisecs=60000` and no RedisBuffer. Compared with the current setup:

| | Current (300 s) | Proposal (600 s) |
|---|---|---|
| Writes per flush | 124 | about 112 |
| Flushes per day | 288 | 144 |
| Writes per day | 36k | about 16k |
| Bytes per day | 50 MB | about 70 MB |

The proposal is about 2 times better by write count and 1.4 times worse by bytes. This difference is smaller than the uncertainty in write amplification.

### RedisBuffer or kernel writeback

At equal intervals both give the same feed write count and the same data at risk.

- Kernel writeback covers every writer, including the root partition. RedisBuffer covers feeds only.
- Any `sync` or `fsync` defeats kernel writeback, as does memory pressure. Log2ram sync, backups and apt all call these. Other processes cannot change RedisBuffer timing.
- RedisBuffer adds a lock protocol, a read path merge and extra engine methods. Averaged queries and CSV export miss data still held in Redis. Page cache batching needs none of this, as reads already see dirty pages.

To switch, raise `vm.dirty_writeback_centisecs` together with `vm.dirty_expire_centisecs`, then repeat the trace and check for about 124 writes per cycle.

### Power loss

Each low write measure adds to the data at risk on a power cut. Data is held for up to 300 seconds in Redis and about 30 seconds in the page cache. Ext2 has no journal and needs a full `e2fsck` after an unclean shutdown. The same trade applies to kernel writeback tuning.

### Next step: write-ahead log

The flush interval currently sets both the write count and the data at risk. A write-ahead log separates the two.

- Inputs are queued in Redis and written every 60 seconds to one sequential log file on the card, and to per-feed files in tmpfs.
- Feed files on the card are updated from tmpfs once an hour.
- On start, the log is replayed into tmpfs. PHPFina writes are positional, so replay is idempotent.

Modelled on the measured system:

| | Current | Write-ahead log |
|---|---|---|
| Feed file writes per day | 36k (50 MB) | 3k (4 MB) |
| Log writes per day | 0 | about 3k (9 MB) |
| Total | 36k, 50 MB | about 6k, 13 MB |
| Data at risk | 300 s | 60 s |

This is about 6 times fewer writes and 4 times fewer bytes, with a fifth of the data at risk. It reduces wear on either measure, so the open question on writes or bytes does not affect it.

Design rules:

- Fixed 12 byte records (feed id, time, float32) with a sequence number or CRC per batch, so a torn tail is discarded on replay.
- `fdatasync` the log after each append for a hard 60 second bound. Without it, the page cache adds about 30 seconds.
- Flush order: append tmpfs data to feed files, `syncfs` the partition, then truncate the log. Any other order can lose data.
- Reads combine the feed file and the tmpfs continuation in the engine. This replaces the RedisBuffer read merge and fixes the averaged query and CSV gap.
- Memory use is about 110 KB per hour for 92 feeds.

Ext4 suits this design. The journal protects metadata and the log protects data, so `commit=` can be long.

### Open questions

- What fraction of card wear comes from page programs rather than bytes? An `fio` test across write sizes, or wear counters read from an industrial card with `sdmon`, would answer this.
- What writes to the root partition once per flush?
