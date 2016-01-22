# vanqard/pagepeekr

This is a small client package wrapped around GuzzleHttp in order to simplify
retrieving thumbnails of web pages as JPEGS via the PagePeeker.com service

## Installation

Recommended installation is via composer

    php composer.phar require vanqard/pagepeekr

## Usage

After the package has been installed, usage is reasonable simple.

First, build up an array of parameters like this.
(Note that only the sourceUrl parameter is required)

    $params = [
        'sourceUrl' => 'https://phpbrilliance.com',
        // Optional parameters
        'targetFileName' => '/public/images/phpbrilliance_thumbnail.jpg',
        'thumbnailSize' => 't|s|m|l|x',
        'pollInterval' => 5 // I don't recommend changing the default
    ];

Then instantiate the client with the parameter array

    $client = new \Vanqard\PagePeekr\Client($params);

And request the thumbnail from PagePeeker.com like so

    $filename = $client->fetchThumbNail();

The `$filename` variable is populated with the path to the downloaded thumbnail.

## Still to do

 * Unit tests
 * Added flexibility

## Warning

This code is far from being production ready. Please do feel free to experiment with it.

## Security

If you find any security issues with this package, please contact the author directly here: [Thunder Raven-Stoker](mailt:thunder@vanqard.com)
