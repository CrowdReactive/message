<?php

namespace spec\Http\Message\Formatter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use PhpSpec\ObjectBehavior;

class CurlCommandFormatterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Message\Formatter\CurlCommandFormatter');
    }

    function it_is_a_formatter()
    {
        $this->shouldImplement('Http\Message\Formatter');
    }

    function it_formats_the_request(RequestInterface $request, UriInterface $uri, StreamInterface $body)
    {
        $request->getUri()->willReturn($uri);
        $request->getBody()->willReturn($body);

        $uri->withFragment('')->shouldBeCalled()->willReturn('http://foo.com/bar');
        $request->getMethod()->willReturn('GET');
        $request->getProtocolVersion()->willReturn('1.1');

        $request->getHeaders()->willReturn(['foo'=>['bar', 'baz']]);
        $request->getHeaderLine('foo')->willReturn('bar, baz');

        $this->formatRequest($request)->shouldReturn('curl \'http://foo.com/bar\' -H \'foo: bar, baz\'');
    }

    function it_formats_post_request(RequestInterface $request, UriInterface $uri, StreamInterface $body)
    {
        $request->getUri()->willReturn($uri);
        $request->getBody()->willReturn($body);

        $body->__toString()->willReturn('body " data'." test' bar");
        $body->getSize()->willReturn(1);
        $body->isSeekable()->willReturn(true);
        $body->rewind()->willReturn(true);

        $uri->withFragment('')->shouldBeCalled()->willReturn('http://foo.com/bar');
        $request->getMethod()->willReturn('POST');
        $request->getProtocolVersion()->willReturn('2.0');

        $request->getHeaders()->willReturn([]);

        $this->formatRequest($request)->shouldReturn("curl 'http://foo.com/bar' --http2 --request POST --data 'body \" data test'\'' bar'");
    }

    function it_does_nothing_for_response(ResponseInterface $response)
    {
        $this->formatResponse($response)->shouldReturn('');
    }

    function it_formats_the_request_with_user_agent(RequestInterface $request, UriInterface $uri, StreamInterface $body)
    {
        $request->getUri()->willReturn($uri);
        $request->getBody()->willReturn($body);

        $uri->withFragment('')->shouldBeCalled()->willReturn('http://foo.com/bar');
        $request->getMethod()->willReturn('GET');
        $request->getProtocolVersion()->willReturn('1.1');
        $uri->withFragment('')->shouldBeCalled()->willReturn('http://foo.com/bar');
        $request->getHeaders()->willReturn(['user-agent'=>['foobar-browser']]);

        $this->formatRequest($request)->shouldReturn("curl 'http://foo.com/bar' -A 'foobar-browser'");
    }

    function it_formats_a_request_with_null_bytes_in_body(RequestInterface $request, UriInterface $uri, StreamInterface $body)
    {
        $body->__toString()->willReturn("--something=nullbyte\0");
        $request->getUri()->willReturn($uri);
        $request->getBody()->willReturn($body);

        $uri->withFragment('')->shouldBeCalled()->willReturn('http://foo.com/bar');
        $request->getMethod()->willReturn('GET');
        $request->getProtocolVersion()->willReturn('1.1');

        $request->getHeaders()->willReturn(['foo'=>['bar', 'baz']]);
        $request->getHeaderLine('foo')->willReturn('bar, baz');

        $this->formatRequest($request)->shouldReturn('curl \'http://foo.com/bar\' -H \'foo: bar, baz\'');
    }
}
