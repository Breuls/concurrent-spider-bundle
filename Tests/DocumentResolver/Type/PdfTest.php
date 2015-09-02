<?php

namespace Simgroep\ConcurrentSpiderBundle\Tests\DocumentResolver\TypePdf;

use PHPUnit_Framework_TestCase;
use Simgroep\ConcurrentSpiderBundle\DocumentResolver\Type\Pdf;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Mock version of json_encode
 *
 * Used for testing an error situation.
 *
 * @param mixed $value
 * @return bool|string
 */
function json_encode($value)
{
    if ($value === 'return-false') {
        return false;
    }
    return \json_encode($value);
}

/**
 * Date function mock for returning the same date string
 * Fixing problem with generating not the same datetime each call
 *
 * @return string
 */
function date()
{
    return '2015-06-18T23:49:41Z';
}

class PdfTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function retrieveValidDataFromPdfFile()
    {

        $document = $this->getMockBuilder('Smalot\PdfParser\Document')
                ->setMethods(['getText'])
                ->getMock();
        $document->expects($this->once())
                ->method('getText')
                ->will($this->returnValue('Dummy Text Occure There!'));

        $pdfType = $this->getMockBuilder('Smalot\PdfParser\Parser')
                ->disableOriginalConstructor()
                ->setMethods(['getText', 'parseContent'])
                ->getMock();
        $pdfType->expects($this->once())
                ->method('parseContent')
                ->will($this->returnValue($document));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
                ->disableOriginalConstructor()
                ->setMethods(['getBody', 'getLastModified'])
                ->getMock();
        $response->expects($this->once())
                ->method('getLastModified')
                ->will($this->returnValue('2015-06-18T23:49:41Z'));
        $response->expects($this->once())
                ->method('getBody')
                ->with(true);

        $uri = $this->getMockBuilder('VDB\Uri\Uri')
                ->disableOriginalConstructor()
                ->setMethods(['toString'])
                ->getMock();
        $uri->expects($this->exactly(2))
                ->method('toString')
                ->will($this->returnValue('http://blabdummy.de/dummydir/dummyfile.pdf'));

        $crawler = new Crawler ('', 'http://blabdummy.de/dummydir/dummyfile.pdf');

        $resource = $this
                ->getMockBuilder('VDB\Spider\Resource')
                ->disableOriginalConstructor()
                ->setMethods(['getCrawler', 'getResponse', 'getUri', 'getBody'])
                ->getMock();
        $resource
                ->expects($this->exactly(2))
                ->method('getCrawler')
                ->will($this->returnValue($crawler));
        $resource->expects($this->exactly(2))
                ->method('getResponse')
                ->will($this->returnValue($response));
        $resource->expects($this->exactly(2))
                ->method('getUri')
                ->will($this->returnValue($uri));

        $type = new Pdf($pdfType);
        $data = $type->getData($resource);

        //change that to: $this->assertEquals($expectedData, $data);
        $this->assertEquals(11, count($data['document']));
        $expectedKeys = ['id', 'url', 'content', 'title', 'tstamp', 'contentLength', 'lastModified', 'date', 'publishedDate', 'SIM_archief', 'SIM.simfaq'];
        foreach ($expectedKeys as $expectedKey) {
            $this->assertArrayHasKey($expectedKey, $data['document']);
        }

        $this->assertEquals('dummyfile.pdf', $data['document']['title']);
        $this->assertNotEmpty($data, $data['document']['content']);
    }

    /**
     * @test
     * @expectedException \Simgroep\ConcurrentSpiderBundle\InvalidContentException
     * @expectedExceptionMessage PDF didn't contain enough content (minimal chars is 3)
     */
    public function throwExceptionOnLessThenMinimalContentLength()
    {

        $document = $this->getMockBuilder('Smalot\PdfParser\Document')
                ->setMethods(['getText'])
                ->getMock();
        $document->expects($this->once())
                ->method('getText')
                ->will($this->returnValue(''));

        $pdfType = $this->getMockBuilder('Smalot\PdfParser\Parser')
                ->disableOriginalConstructor()
                ->setMethods(['getText', 'parseContent'])
                ->getMock();
        $pdfType->expects($this->once())
                ->method('parseContent')
                ->will($this->returnValue($document));

        $response = $this->getMockBuilder('Guzzle\Http\Message\Response')
                ->disableOriginalConstructor()
                ->setMethods(['getBody'])
                ->getMock();
        $response->expects($this->once())
                ->method('getBody')
                ->with(true);

        $resource = $this
                ->getMockBuilder('VDB\Spider\Resource')
                ->disableOriginalConstructor()
                ->setMethods(['getResponse'])
                ->getMock();
        $resource->expects($this->once())
                ->method('getResponse')
                ->will($this->returnValue($response));

        $type = new Pdf($pdfType);
        $data = $type->getData($resource);

        //change that to: $this->assertEquals($expectedData, $data);
        $this->assertEquals(11, count($data['document']));
        $expectedKeys = ['id', 'url', 'content', 'title', 'tstamp', 'contentLength', 'lastModified', 'date', 'publishedDate', 'SIM_archief', 'SIM.simfaq'];
        foreach ($expectedKeys as $expectedKey) {
            $this->assertArrayHasKey($expectedKey, $data['document']);
        }

        $this->assertEquals('dummyfile.pdf', $data['document']['title']);
        $this->assertNotEmpty($data, $data['document']['content']);
    }

}
