<?php
namespace Vanqard\PagePeekr;

use Vanqard\PagePeekr\Exception\ClientException;

/**
 * Class definition for the PagePeekr Client
 *
 * Example usage
 *
 * $params = [
 *    'sourceUrl' => "https://phpbrilliance.com",
 *    'targetFileName' => '/public/img/phpbrilliance.jpg',
 *    'pollInterval' => 5
 * ];
 *
 * try {
 *   $client = new \Vanqard\PagePeekr\Client($params);
 *   $client->fetchThumbNail();
 * } catch (ClientException $e) {
 *   // handle error
 * }
 *
 * @author Thunder Raven-Stoker
 * @license MIT
 * @copyright 2016 Thunder Raven-Stoker <thunder@vanqard.com>
 */
class Client
{
  /**
   * @var string
   */
  private $sourceUrl;

  /**
   * @var string
   */
  private $thumbnailSize = "l";

  /**
   * @var string
   */
  private $targetFileName = null;

  /**
   * @var integer
   */
  private $pollInterval = 5;

  /**
   * @var \GuzzleHttp\Client
   */
  private $guzzleClient;

  /**
   * @var string
   */
  private $pagePeekerBaseUri = "http://free.pagepeeker.com";

  /**
   * @var string
   */
  private $pagePeekerRequestPath = "/v2/thumbs.php";

  /**
   * @var string
   */
  private $pagePeekerStatusPath = "/v2/thumbs_ready.php";


  public function __construct(array $params = [])
  {
    if (!array_key_exists('sourceUrl', $params)) {
      throw new ClientException('sourceUrl is a required parameter');
    }

    $this->setSourceUrl($params['sourceUrl']);
    unset($params['sourceUrl']);

    $this->init($params);
  }

  /**
   * Setter for the sourceUrl parameter - validates the incoming source url
   *
   * @param string $sourceUrl
   * @throws ClientException
   * @return self
   */
  public function setSourceUrl($sourceUrl)
  {
    if (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !stristr($sourceUrl, 'http')) {
      throw new ClientException('sourceUrl is not a valid web address');
    }

    $this->sourceUrl = $sourceUrl;
    return $this;
  }

  /**
   * Initialises the client based on the incoming parameter array
   *
   * @return void
   */
  private function init(array $params)
  {
    if (!array_key_exists('targetFileName', $params)) {
      $params['targetFileName'] = null;
    }

    if (!empty($params)) {
      foreach ($params as $key => $value) {
        if (property_exists($this, $key)) {
          $this->{$key} = $value;
        }
      }
    }

    $this->guzzleClient = new \GuzzleHttp\Client(['base_uri' => $this->pagePeekerBaseUri]);
  }

  /**
   * Getter for the target filename
   *
   * Will autogenerate a filename based on the sourceUrl
   * when one has not been supplied
   *
   * @return string;
   */
  private function getTargetFileName()
  {
    if (is_null($this->targetFileName)) {
      $sourceParts = parse_url($this->sourceUrl);
      $targetFileName = preg_replace('#^(https://|http://)#','', $this->sourceUrl);
      $targetFileName = preg_replace('/[^a-zA-Z0-9]+/', '', $targetFileName);
      $targetFileName .= '.jpg';

      $this->targetFileName = $targetFileName;
    }

    return $this->targetFileName;
  }

  /**
   * Triggers the thumbnail download process
   *
   * @return string|false The path to the thumbnail if available
   */
  public function fetchThumbNail()
  {
    // Send initial request
    $response = $this->sendRequest($this->pagePeekerRequestPath, $this->getRequestParameters());

    if ($this->pollApi()) {
      if ($this->downloadThumbnail()) {
        return $this->getTargetFileName();
      }
    }

    return false;
  }

  /**
   * Performs the thumbnail download process once PagePeeker has advised
   * that the thumbnail is ready for download
   *
   * @return integer number of bytes written to the filesystem
   */
  private function downloadThumbnail()
  {
    $response = $this->sendRequest($this->pagePeekerRequestPath, $this->getRequestParameters());
    $imageData = $response->getBody();
    return file_put_contents($this->getTargetFileName(), $imageData);
  }

  /**
   * Returns an array of parameters to send on to PagePeeker
   *
   * @return array
   */
  private function getRequestParameters()
  {
    return [
      "size" => $this->thumbnailSize,
      "url" => $this->sourceUrl
    ];
  }

  /**
   * Polls the PagePeeker API until the thumbnail is ready
   *
   * @TODO - provide internal timeout function
   * @return boolean - true when the thumbnail is ready for download
   */
  private function pollApi()
  {

    $pollInterval = intval($this->pollInterval);
    if ($pollInterval < 5) $pollInterval = 5;

    $requestParameters = $this->getRequestParameters();

    do {
      $res = $this->sendRequest($this->pagePeekerStatusPath, $requestParameters);
      $body = json_decode($res->getBody());

      if ($body->IsReady) {
        break;
      }

      sleep($pollInterval);
    } while ($body->IsReady == 0);

    return true;
  }

  /**
   * Send the API request via the GuzzleHttp client
   * @throws ClientException
   *
   * @return \GuzzleHttp\Psr7\Response|false
   */
  private function sendRequest($path, $params)
  {
    try {
        $res = $this->guzzleClient->request('GET', $path, ['query' => $params]);
        return $res;

    } catch (Exception $e) {
        throw new ClientException($e->getMessage());
    }

    return false;
  }
}
