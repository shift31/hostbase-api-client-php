# Hostbase API Client for PHP

## Basic usage (if you're using Composer)

- In your project, run `composer require shift31/hostbase-api-client dev-master`
- Example script:

```php
<?php

require_once('vendor/autoload.php');

use Shift31\HostbaseClient;

/* The constructor will accept username and password parameters,
if you setup basic auth yourself, or for when I get around to implementing it. */
$client = new HostbaseClient("http://your.hostbase.server");

/* Optionally return arrays instead of objects */
//$client->decodeJsonAsArray();


// perform a search, limiting to 100 servers, retrieving just FQDNs
print_r($client->search('your.domain', 100, false));

// perform the same search, retrieving all data
print_r($client->search('your.domain', 100, true));
```

For an example of adding/updating hosts, check out the PuppetDB importer: https://github.com/shift31/hostbase-importer-puppetdb