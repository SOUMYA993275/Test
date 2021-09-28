<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\SuperadminModel;
use Exception;
use \Firebase\JWT\JWT;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
Header('Access-Control-Allow-Origin: *'); //for allow any domain, insecure
Header('Access-Control-Allow-Headers: *'); //for allow any headers, insecure
Header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE'); //method allowed

class Admin extends BaseController
{
    use ResponseTrait;

    private function getKey()
    {
        return "my_application_secret";
    }
    
    public function login()
    {
        $rules = [
            "email" => "required|valid_email|min_length[6]",
            "password" => "required",
        ];

        $messages = [
            "email" => [
                "required" => "Email required",
                "valid_email" => "Email address is not in format"
            ],
            "password" => [
                "required" => "password is required"
            ],
        ];

        if (!$this->validate($rules, $messages)) {

            $response = [
                'status' => 422,
                'error' => true,
                'message' => $this->validator->getErrors(),
            ];

            return $this->respondCreated($response);
            
        } else {
            $Admin = new SuperadminModel();
            $email = $this->request->getVar("email");
            $userdata = $Admin->get_all_data($email);
            // print_r($userdata);
            // exit;
            if (!empty($userdata)) {
                if ($this->request->getVar("password") == $userdata[0]->password) {
                    
                    $key = $this->getKey();

                    $iat = time(); // current timestamp value
                    $nbf = $iat + 10;
                    $exp = $iat + 3600;

                    $payload = array(
                        "iss" => "Test API",
                        "aud" => "Restrict",
                        "iat" => $iat, // issued at
                        "nbf" => $nbf, //not before in seconds
                        "exp" => $exp, // expire time in seconds
                        "data" => $userdata,
                    );

                    $token = JWT::encode($payload, $key);

                    $response = [
                        'statusCode' => 200,
                        'error' => false,
                        'messages' => 'Log In successfully',
                        'data' => [
                            'user' => $userdata,
                            'token' => $token
                        ]
                    ];
                    return $this->respondCreated($response);
                 } else {

                    $response = [
                        'status' => 402,
                        'error' => true,
                        'messages' => 'Invalid Credentials',
                    ];
                    return $this->respondCreated($response);
                }
            } else {
                $response = [
                    'status' => 402,
                    'error' => true,
                    'messages' => 'User not found',
                ];
                return $this->respondCreated($response);
            }
        }
    }

    public function details()
    {
        $key = $this->getKey();
        $authHeader = $this->request->getHeader("Authorization");
        $authHeader = $authHeader->getValue();
        $token = $authHeader;

        try {
            $decoded = JWT::decode($token, $key, array("HS256"));

            if ($decoded) {

                $response = [
                    'status' => 200,
                    'error' => false,
                    'messages' => 'User details',
                    'data' => [
                        'profile' => $decoded
                    ]
                ];
                return $this->respondCreated($response);
            }
        } catch (Exception $ex) {
          
            $response = [
                'status' => 401,
                'error' => true,
                'messages' => 'Failed to Authenticate Token',
            ];
            return $this->respondCreated($response);
        }
    }
}
