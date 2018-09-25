<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class Usuario extends Controller
{
    public function usuario(Request $request){
        return $request->user();
    }

    public function login(Request $request){
        $data = $request->all();

        $validacao = Validator::make($data, [
            'email'     => 'required|string|email|max:255',
            'password'  => 'required|string',
        ]);

        if ($validacao->fails()) {
            return $validacao->errors();
        } else {
            $authorization = array(
                'email'     => $data['email'],
                'password'  => $data['password']
            );
            if(Auth::attempt($authorization)){
                $user = auth()->user();
                $user->token = $user->createToken($user->email)->accessToken;
                $user->image = asset($user->image);

                return $user;
            } else {
                return ['status' => false];
            }
        }
    }

    public function cadastro(Request $request){
        $data = $request->all();

        $validacao = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validacao->fails()) {
            return $validacao->errors();
        }

        $dir_imagem = 'uploads'.DIRECTORY_SEPARATOR.'users'.DIRECTORY_SEPARATOR.'perfil'.DIRECTORY_SEPARATOR.'default.png';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'image' => $dir_imagem
        ]);

        $user->token = $user->createToken($user->email)->accessToken;
        $user->image = asset($user->image);

        return $user;
    }

    public function perfil(Request $request){
        $user = $request->user();
        $data = $request->all();

        if (isset($data['password'])) {
            $validacao = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)
                ],
                'password' => 'required|string|min:6|confirmed',
            ]);

            //Cancela o update
            if ($validacao->fails()) { return $validacao->errors(); }

            //Só atualiza o registro de senha dentro do if
            $user->password = bcrypt($data['password']);
        } else {
            $validacao = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id)
                ]
            ]);

            //Cancela o update
            if ($validacao->fails()) { return $validacao->errors(); }
        }

        if (isset($data['image'])) {

            Validator::extend('base64image', function($attribute, $value, $params, $valadator){
                $explode = explode(',', $value);
                $allow = ['png', 'jpg', 'svg', 'jpeg', 'gif', 'tiff'];
                $format = str_replace(
                    [
                        'data:image/',
                        ';',
                        'base64'
                    ],
                    [
                        ''
                    ],
                    $explode[0]
                );

                //verifica formato do arquivo
                if (!in_array($format, $allow)) {
                    return false;
                }

                //verifica formato base64
                if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
                    return false;
                }

                return true;
            });

            $validacao = Validator::make($data, [
                'image' => 'base64image'
            ], ['base64image' => 'Imagem inválida']);

            if ($validacao->fails()) {
                return $validacao->errors();
            }

            $time = time();
            $dir_pai = 'uploads'.DIRECTORY_SEPARATOR.'users'.DIRECTORY_SEPARATOR.'perfil';
            $dir_imagem = $dir_pai.DIRECTORY_SEPARATOR.'perfil_id_'.$user->id;

            $aux = explode('/', $data['image']);
            $aux = explode(';', $aux[1]);
            $extensao = $aux[0];

            $url_imagem = $dir_imagem.DIRECTORY_SEPARATOR.$time.'.'.$extensao;

            $file = str_replace('data:image/'.$extensao.';base64,', '', $data['image']);
            $file = base64_decode($file);

            if ($user->image) {
                if (file_exists($user->image) && strpos( $user->image, 'default') == false) {
                    unlink($user->image);
                }
            }

            if (!file_exists($dir_imagem)) {
                mkdir($dir_imagem, 0700, true);
            }

            file_put_contents($url_imagem, $file);
            $user->image = $url_imagem;
        }

        //Caso nao tenha sido cancelado, persiste os novos dados do usuario
        $user->name = $data['name'];
        $user->email = $data['email'];

        //Persiste os novos dados
        $user->save();

        $user->image = asset($user->image);
        $user->token = $user->createToken($user->email)->accessToken;

        return $user;
    }
}
