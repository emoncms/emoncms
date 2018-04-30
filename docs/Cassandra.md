# Apache Cassandra

Experimental Apache Cassandra support.

This document describes how to enable the use of Apache Cassandra as a storage engine for feeds.

Cassandra is an open-source NoSQL distributed database, designed to handle a large amount of data across many nodes in a highly available cluster with no single point of failure.

Feed data is stored in a single table in a dedicated keyspace, the row key is the (feed_id,day) pair to limit row size i.e. PRIMARY KEY((feed_id,day), time))

The keyspace must be created by the user, the table is created by the engine.

## Install Cassandra

Install Cassandra following the instructions at http://cassandra.apache.org/download/

## Install Cassandra PHP Driver

Install Cassandra PHP Driver following the instructions at http://datastax.github.io/php-driver/ (depends on the DataStax C/C++ Driver for Apache Cassandra)

## Create a new keyspace

Create a new keyspace that will hold emoncms feed data using cqlsh

cqlsh> CREATE KEYSPACE emoncms WITH replication = {'class': 'SimpleStrategy', 'replication_factor' : 1};

Change replication strategy and replication factor to suit your needs. The sample command is only for testing, do not use these settings in a multi datacenter distributed installation.

## Configure emoncms

Copy default.settings.php to settings.php if not done already. Configure the newly created keyspace in settings.php in the feedsettings section

        'cassandra'=>array(
            'keyspace' => 'emoncms'
        )



