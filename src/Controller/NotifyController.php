<?php
 /*
 *@file 
 * Contains \Drupal\napay\Controller\NotifyController
 */

  namespace Drupal\napay\Controller;
  use Drupal\Core\Controller\ControllerBase;
  use Symfony\Component\HttpFoundation\Request;
  use Psr\Log\LoggerInterface;
  use Symfony\Component\DependencyInjection\ContainerInterface;
  use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
  use Symfony\Component\HttpFoundation\Response;
  use Drupal\node\Entity\Node; 
  use Symfony\Component\HttpFoundation\BinaryFileResponse;
  use Ramsey\Uuid\Uuid;

  class NotifyController extends ControllerBase implements ContainerInjectionInterface {
	  
   /**
   * The http request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
	  
  protected $request;
	  
   /**
   * The http response.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
	  
  protected $response;  
	  
  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  
  protected $logger;
   
  
  /**
   * Constructs a new NotifyController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */

   
  public function __construct(LoggerInterface $logger) {
  
    $this->logger = $logger;
    }  

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.default')
    );
    }
  



	  
    
	
  /**
   *  process() Cette fonction reçois la requête envoyé par le mobile gateway
   *  pour créer l'utilisateur ou encore la commande
   */
   
  public function process(){
	$request = Request::createFromGlobals();
	if ($request->isMethod('POST'))
	{
		$this->logger->info('dans le bloc nouvelle formile ');
		$numero = $request->request->get('numero');
		$body = $request->request->get('body');
		$montant = $request->request->get('montant');

		
		// Create node object with attached file.
		$node = Node::create([
		  'type'        => 'notification',
		  'title'       => $montant." de ".$numero,
		  'body'        => $body,
		  'field_amount' => $montant,
		  'field_telephone_number' => $numero,
		  'field_user_id' => $userid,

		]);
		$node->save();
		
		      $response = new Response(json_encode(['success' => 1])) ;
			// $this->createOrder($user->id(),$montant);
			$response->headers->set('Content-Type', 'application/json');
			return $response; 
		
		

	}
        else {
            
 
	    
	    
            $this->logger->info('test rest');
           		$response = new Response(json_encode(['success' => 3])) ;
			// $this->createOrder($user->id(),$montant);
			$response->headers->set('Content-Type', 'application/json');
			return $response; 
        }
	}

public function generateUid()
{
   return Uuid::uuid4();
}


  /**
   *  Callbackapi() Cette fonction reçois la requête envoyé par le mobile gateway
   *  pour créer l'utilisateur ou encore la commande
   */
   
public function Callbackapi(){


  $client = \Drupal::httpClient();
  $body = "
  {
    'providerCallbackHost': 'https://pharmaciemayamaya.dd:8443/momo'
  }
  ";

  $request = $client->post('https://sandbox.momodeveloper.mtn.com/v1_0/apiuser', [
    'headers' => [
      'X-Reference-Id' => '395513f0-12b2-4a9a-8f92-c27c5cbf12a9',
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
  ],
    'body' => $body,
  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  //\Drupal::state()->set('my_data', 'calling');
  //$data = \Drupal::state()->get('my_data');


  $retour = new Response(json_encode($response)) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function GetUserApi(){


  $client = \Drupal::httpClient();
 

  $request = $client->get('https://sandbox.momodeveloper.mtn.com/v1_0/apiuser/395513f0-12b2-4a9a-8f92-c27c5cbf12a9', [
    'headers' => [
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  //\Drupal::state()->set('my_data', 'calling');
  //$data = \Drupal::state()->get('my_data');


  $retour = new Response(json_encode($response)) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function apiKey(){


  $client = \Drupal::httpClient();
 

  $request = $client->post('https://sandbox.momodeveloper.mtn.com/v1_0/apiuser/395513f0-12b2-4a9a-8f92-c27c5cbf12a9/apikey', [
    'headers' => [
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

   $api = json_decode($response);
   $api->{'apiKey'};
   $this->logger->info("Momo Api Key ".$api->{'apiKey'});

  \Drupal::state()->set('momoapi', $api->{'apiKey'});
  //$data = \Drupal::state()->get('my_data');


  $retour = new Response(json_encode($response)) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function motoken(){


  $client = \Drupal::httpClient();
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("395513f0-12b2-4a9a-8f92-c27c5cbf12a9:" . $pass);
 

  $request = $client->post('https://sandbox.momodeveloper.mtn.com/collection/token/', [
    'headers' => [
      'Authorization' => "Basic $auth",
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

   $api = json_decode($response);

   //"access_token": "string",
   //"token_type": "string",
   //"expires_in": 0
 

  \Drupal::state()->set('accesstoken', $api->{'access_token'});

  $temps = time() + $api->{'expires_in'};
  \Drupal::state()->set('expires', $temps);

  $this->logger->info("Le token ".$api->{'access_token'});
  $this->logger->info("Le token expires ".$temps);

  //$data = \Drupal::state()->get('my_data');


  $retour = new Response(json_encode($response)) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function requestToPay(){
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("395513f0-12b2-4a9a-8f92-c27c5cbf12a9:" . $pass);
 
 

  $request = $client->post('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay', [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
      'X-Reference-Id' => '9338e2f1-59d2-48d3-8736-3b53fc118f29',
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  'body' => json_encode([
    "amount" => "10.0",
    "currency" => "EUR",
    "externalId" => "516127822",
    "payer" => [
      "partyIdType" => "MSISDN",
      "partyId" => "064781414"
    ],
    "payerMessage" => "Test to pay",
    "payeeNote" => "Salut"
    ])

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  $temps = \Drupal::state()->get('expires');


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);


  $retour = new Response(json_encode( $response )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

/**
 * This operation is used to get the status of a request to pay. 
 * X-Reference-Id that was passed in the post is used as reference to the request.
 */

public function requestToPayStatus(){
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("395513f0-12b2-4a9a-8f92-c27c5cbf12a9:" . $pass);
 
 

  $request = $client->get('https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay/9338e2f1-59d2-48d3-8736-3b53fc118f29', [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  $temps = \Drupal::state()->get('expires');


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);


  $retour = new Response(json_encode( $response )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function accountBalance(){
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("395513f0-12b2-4a9a-8f92-c27c5cbf12a9:" . $pass);
 
 

  $request = $client->get('https://sandbox.momodeveloper.mtn.com/collection/v1_0/account/balance', [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  $temps = \Drupal::state()->get('expires');


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);
  $this->logger->info("Le body ".$response );



  $retour = new Response(json_encode( $response )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

public function accountActive(){
 

  $client = \Drupal::httpClient();
  $token = \Drupal::state()->get('accesstoken');
  $pass = \Drupal::state()->get('momoapi');
  $auth = base64_encode("395513f0-12b2-4a9a-8f92-c27c5cbf12a9:" . $pass);
 
 

  $request = $client->get('https://sandbox.momodeveloper.mtn.com/collection/v1_0/accountholder/msisdn/064781414/active', [
    'headers' => [
      'Authorization' => "Bearer $token",
      'Ocp-Apim-Subscription-Key' => '9d5e32f537d8413d9c861600aab5331b',
      'X-Target-Environment' => 'sandbox',
      'Content-Type' => 'application/json',
  ],

  ]);
  
  $statusCode = $request->getStatusCode();
  $this->logger->info("Code Status ".$statusCode);
  $response = $request->getBody();

  $temps = \Drupal::state()->get('expires');


  $restant = time() - $temps;


  $this->logger->info("Le token expires ".$restant);
  $this->logger->info("Le body ".$response );



  $retour = new Response(json_encode( $response )) ;
  $retour->headers->set('Content-Type', 'application/json');
  return $retour; 

}

}
 
