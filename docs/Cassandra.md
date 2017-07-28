# Cassandra

experimental Cassandra support

create a new keyspace if needed and set it in settings.php
eg. CREATE KEYSPACE emoncms WITH replication = {'class': 'SimpleStrategy', 'replication_factor' : 3};

install Cassandra PHP Driver e.g. http://datastax.github.io/php-driver/ (depends on the DataStax C/C++ Driver for Apache Cassandra)

