# Installation

To use Solr as Moodle's search engine you need the PHP Apache Solr extension and a Solr server.

## PHP Apache Solr extension

You need PHP Apache Solr extension installed. Use PECL Solr Extension 1.x for Solr Server 3.x, or use PECL Solr 2.x for Solr Server 4.0+.

You can download the official latest versions from [http://pecl.php.net/package/solr](http://pecl.php.net/package/solr)

### Linux

    sudo apt-get install libxml2-dev libcurl4-openssl-dev
    sudo service apache2ctl restart
    sudo echo `"extension=solr.so" > /etc/php5/apache2/conf.d/solr.ini`
    sudo echo `"extension=solr.so" > /etc/php5/cli/conf.d/solr.ini`

### OSX using macports

    sudo port install apache-solr4
    sudo port install php54-solr

### Windows

Install the pecl package as usual. Sorry it has not been tested yet.


## Solr server

The following snippet (feel free to copy & paste into a .sh script with execution permissions) will download Apache Solr 5.4.1
in the current directory and create an index in it named 'moodle'. It basically downloads it, starts the server and creates an
index to add data to.


    #!/bin/bash

    set -e

    SOLRVERSION=5.4.1
    SOLRNAME=solr-$SOLRVERSION
    SOLRTAR=$SOLRNAME'.tgz'

    CORENAME=moodle

    if [ -d $SOLRNAME ]; then
        echo "Error: Directory $SOLRNAME already exists, remove it before starting the setup again."
        exit 1
    fi

    if [ ! -f $SOLRTAR ]; then
        wget http://apache.mirror.digitalpacific.com.au/lucene/solr/$SOLRVERSION/$SOLRTAR
    fi
    tar -xvzf $SOLRTAR

    cd $SOLRNAME

    bin/solr start
    bin/solr create -c $CORENAME

    After setting it up and creating the index use "/yourdirectory/solrdir/bin/solr start" and "/yourdirectory/solrdir/bin/solr stop" from CLI to start and stop the server.

# Setup

1. Go to __Site administration -> Plugins -> Search -> Manage global search__
2. Enable global search, select 'Solr' as search engine and tick all search components checkboxes
3. Go to __Site administration -> Plugins -> Search -> Solr__
4. Set 'Host name' to localhost and 'Collection name' to 'moodle' (the name of the index in Solr)
5. The following script will add the required fields used by Moodle to the Solr index you just created.

`
    php search/engine/solr/cli/setup_schema.php
`

# Indexing

Once global search is enabled a new task will be running through cron, although you can force documents indexing by:

* Go to __Reports -> Search__
* Tick 'Recreate index' checkbox and press 'Save changes'

or

* Execute the following task from CLI

`
    php admin/tool/task/cli/schedule_task.php --execute="\\core\\task\\global_search_task"
`

# Querying

1. Add 'Global search' block somewhere
2. Search stuff
