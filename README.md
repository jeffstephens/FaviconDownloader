MetaFetch
-----------------
MetaFetch can find metadata (and download it) from a URL.
This PHP class handles multiple favicons flavors, including some edge cases like relative URL or embed favicons:

* Absolute URL :
  `<link rel="shortcut icon" href="http://www.domain.com/images/fav.ico" />`
* Absolute URL with relative scheme :
  `<link rel="shortcut icon" href="//www.domain.com/images/fav.ico" />`
* Absolute path :
  `<link rel="shortcut icon" href="/images/fav.ico" />`
* Relative URL :
  `<link rel="shortcut icon" href="../images/fav.ico" />`
* Embed base64-encoded favicon :
  `<link rel="icon" type="image/x-icon" href="data:image/x-icon;base64,AAABAAEAE ... /wAA//8AAA==" />`

It also fetches metadata such as page title and description.

### Install
The easiest way to install MetaFetch is to use Composer from the command line:

```
composer require jeffstephens/meta-fetch
```

Otherwise, just download [MetaFetch.php](https://raw.githubusercontent.com/jeffstephens/meta-fetch/master/src/MetaFetch.php) and require it manually. Tested on PHP 5.3, 5.4, 5.5 & 5.6.

### Example
```php
require 'MetaFetch.php';
use Jeffstephens\MetaFetch\MetaFetch;

// Find & download metadata
$metadata = new MetaFetch('http://stackoverflow.com/questions/19503326/bug-with-chrome-tabs-create-in-a-loop');

if (!$metadata->icoExists) {
    echo "No metadata for ".$metadata->url;
    die(1);
}

echo "Favicon found: {$metadata->icoUrl}\n";
echo "Page title: {$metadata->pageTitle}\n";
echo "Page description: {$metadata->pageDesc}\n";

// Saving favicon to file
$filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'metadata-' . time() . '.' . $metadata->icoType;
file_put_contents($filename, $metadata->icoData);
echo "Saved to $filename\n\n";

echo "Details:\n";
$metadata->debug();
```