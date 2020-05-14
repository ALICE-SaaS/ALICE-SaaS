<?php
declare(strict_types=1);

namespace App\Actions\User;

define("AUTH_URL", 'https://test-auth.navigatep.com/');

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use App\Exceptions;
use Psr\Log\LoggerInterface;
use App\Classes\TokenProcessor;
use Psr\Http\Message\ResponseInterface as Response;
use App\Actions\Action;

class SignInAction extends Action
{
    /**
     * @param LoggerInterface $logger
     * @param TokenProcessor $tokenProcessor
     */

    public function __construct(LoggerInterface $logger, TokenProcessor $tokenProcessor)
    {
        $this->tokenProcessor = $tokenProcessor;
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $login = $_POST["login"];
        $password = $_POST["password"];

        $client = new GuzzleClient(['base_uri' => AUTH_URL, 'verify' => APP_ROOT . '/cacert.pem']);

 
        $data = ['app' => 'vm', 'login' =>$login, 'password' => $password];
        
        try{
            $response = $client->post('api/authenticate', [
                'json' => $data
            ]);

            $payload = json_decode($response->getBody()->getContents());
            
            if ($response->getStatusCode() == 201 && property_exists($payload, 'token') && $payload->type == 'auth') {
                $token = $this->tokenProcessor->decode($payload->token);
                $token->building = 5240;
                $token->redexp = null;
                $token->iat = null;
                $token->exp = null;

                $new_token = $this->tokenProcessor->create($token, 60*10, false);

                return $this->respondWithData(['token' => $new_token, 'tokenDecoded' => $token]);
            }
            else {
                throw new Exceptions\InternalServerErrorException();
            }
        }
        catch(ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            throw new Exceptions\BadRequestException($response->error->userMessage);
        }
        catch(ServerException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());
            throw new Exceptions\InternalServerErrorException($response->error->userMessage);
        }    

        return $this->respondWithData([]);        
    }
}
