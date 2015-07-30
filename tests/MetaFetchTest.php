<?php
/**
 * This file is part of MetaFetch.
 *
 * @package MetaFetch
 */

namespace Jeffstephens\MetaFetch;

class MetaFetchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test settings
     */
    protected function setUp()
    {
        $this->options = array(
            'httpProxy' => $_ENV['fiddler-proxy'],
            'sslVerify' => false,
        );
    }

    /**
     * @covers \Jeffstephens\MetaFetch\MetaFetch::urlType
     */
    public function testUrlType()
    {
        $this->assertEquals(MetaFetch::URL_TYPE_ABSOLUTE, MetaFetch::urlType('http://www.domain.com/images/fav.ico'));
        $this->assertEquals(MetaFetch::URL_TYPE_ABSOLUTE_SCHEME, MetaFetch::urlType('//www.domain.com/images/fav.ico'));
        $this->assertEquals(MetaFetch::URL_TYPE_ABSOLUTE_PATH, MetaFetch::urlType('/images/fav.ico'));
        $this->assertEquals(MetaFetch::URL_TYPE_RELATIVE, MetaFetch::urlType('../images/fav.ico'));
    }

    /**
     * @covers \Jeffstephens\MetaFetch\MetaFetch::getExtensionFromMimeType
     */
    public function testGetExtMime()
    {
        $this->assertEquals('ico', MetaFetch::getExtensionFromMimeType('image/x-icon'));
        $this->assertEquals('png', MetaFetch::getExtensionFromMimeType('image/png'));
        $this->assertEquals('gif', MetaFetch::getExtensionFromMimeType('image/gif'));
        $this->assertEquals('jpg', MetaFetch::getExtensionFromMimeType('image/jpeg'));
        $this->assertEquals('jpg', MetaFetch::getExtensionFromMimeType('image/jpg'));
    }

    /**
     * Constructor test
     *
     * @covers \Jeffstephens\MetaFetch\MetaFetch::__construct
     * @expectedException \InvalidArgumentException
     */
    public function testConstruct()
    {
        $fav = new MetaFetch('//example.org', $this->options);
    }

    /**
     * Website without favicon
     */
    public function testNoFavicon()
    {
        $fav = new MetaFetch('http://example.org/', $this->options);
        $this->assertNull($fav->icoData);
        $this->assertNotNull($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(404, $fav->debugInfo['favicon_download_metadata']['http_code']);
    }

    /**
     * Website using default favicon (/favicon.ico)
     */
    public function testDefaultFavicon()
    {
        $fav = new MetaFetch('https://getcomposer.org/', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('https://getcomposer.org/favicon.ico', $fav->icoUrl);
        $this->assertEquals('default', $fav->findMethod);
    }

    /**
     * Custom favicon URL (head.link), absolute URL
     */
    public function testHeadAbsoluteFull()
    {
        $fav = new MetaFetch('https://code.google.com/p/chromium/issues/detail?id=236848', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('https://ssl.gstatic.com/codesite/ph/images/phosting.ico', $fav->icoUrl);
        $this->assertEquals('head absolute', $fav->findMethod);
    }

    /**
     * Custom favicon URL (head.link), absolute URL with relative scheme
     */
    public function testHeadAbsoluteScheme()
    {
        $fav = new MetaFetch('http://stackoverflow.com/questions/19503326/bug-with-chrome-tabs-create-in-a-loop', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertNotNull($fav->pageTitle);
        $this->assertNotNull($fav->pageDesc);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('http://cdn.sstatic.net/stackoverflow/img/favicon.ico?v=5bcec08ba0c5', $fav->icoUrl);
        $this->assertEquals('head absolute_scheme', $fav->findMethod);
    }

    /**
     * Custom favicon URL (head.link), absolute path
     */
    public function testHeadAbsolutePath()
    {
        $fav = new MetaFetch('http://www.koreus.com/', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('http://www.koreus.com/favicon.png', $fav->icoUrl);
        $this->assertEquals('head absolute_path without base href', $fav->findMethod);
    }

    /**
     * Custom favicon URL (head.link), absolute path using base href
     */
    public function testHeadAbsolutePathWithBase()
    {
        $fav = new MetaFetch('http://localhost/FaviconDownloader/weird-cases/absolute_path-with-base.html', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('https://jquery.org/jquery-wp-content/themes/jquery.org/i/favicon.ico', $fav->icoUrl);
        $this->assertEquals('head absolute_path with base href', $fav->findMethod);
    }

    /**
     * Custom favicon URL (head.link), relative path
     */
    public function testHeadRelativePath()
    {
        $fav = new MetaFetch('http://book.cakephp.org/2.0/en/core-utility-libraries/app.html', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('http://book.cakephp.org/2.0/en/core-utility-libraries/../_static/favicon.ico', $fav->icoUrl);
        $this->assertEquals('head relative without base href', $fav->findMethod);
    }

    /**
     * Custom favicon URL (head.link), relative path using base href
     */
    public function testHeadRelativePathWithBase()
    {
        $fav = new MetaFetch('http://localhost/FaviconDownloader/weird-cases/relative-with-base.html', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('https://www.npmjs.com/static/images/touch-icons/favicon.ico', $fav->icoUrl);
        $this->assertEquals('head relative with base href', $fav->findMethod);
    }

    /**
     * Failover (head.link URL leading to 404, and existing default favicon)
     */
    public function testFailover()
    {
        $fav = new MetaFetch('http://localhost/FaviconDownloader/weird-cases/failover.html', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertTrue(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('http://localhost/favicon.ico', $fav->icoUrl);
        $this->assertEquals('default', $fav->findMethod);
    }

    /**
     * URL with fragment (javascript hash)
     */
    public function testFragment()
    {
        $fav = new MetaFetch('http://www.cmsmadesimple.fr/forum/viewtopic.php?pid=34728#p34728', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertEquals(200, $fav->debugInfo['favicon_download_metadata']['http_code']);
        $this->assertEquals('http://www.cmsmadesimple.fr/forum/favicon.ico', $fav->icoUrl);
        $this->assertEquals('head relative without base href', $fav->findMethod);
    }

    /**
     * Embed favicon, base64 encoded
     */
    public function testEmbedBase64()
    {
        $fav = new MetaFetch('http://localhost/FaviconDownloader/weird-cases/base64-embed-favicon.html', $this->options);
        $this->assertNotNull($fav->icoData);
        $this->assertEmpty($fav->errors);
        $this->assertFalse(isset($fav->debugInfo['failover']));
        $this->assertFalse(isset($fav->debugInfo['favicon_download_metadata']));
        $this->assertEquals('head base64', $fav->findMethod);
    }

    /**
     * Unknown host
     */
    public function testNoHost()
    {
        $fav = new MetaFetch('http://no-subdomain.no-hostname.fr/');
        $this->assertNull($fav->icoData);
        $this->assertNull($fav->pageTitle);
        $this->assertNull($fav->pageDesc);
        $this->assertNotEmpty($fav->errors);
        $this->assertEquals(CURLE_COULDNT_RESOLVE_HOST, $fav->debugInfo['document_curl_errno']);
    }
}
